<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance enterprise model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Attendance_enterprise_model extends MY_Model {
     function get_user_permissions() {
        $login_type = $this->session->userdata('login_type');
        
        if($login_type == 'admin') {
            $level = $this->db->get_where('admin', ['admin_id' => $this->session->userdata('admin_id')])->row()->level;
            return [
                'can_mark_attendance' => ($level == 1 || $level == 2),
                'can_collect_fees' => true
            ];
        }
        
        if($login_type == 'teacher') {
            $mode = $this->db->get_where('settings', ['type' => 'teacher_fee_collection_mode'])->row()->description;
            return [
                'can_mark_attendance' => true,
                'can_collect_fees' => ($mode != 'restricted')
            ];
        }
        
        return ['can_mark_attendance' => false, 'can_collect_fees' => false];
    }
    public function get_class_students($class_id, $section_id, $date) {
        $timestamp = strtotime($date);
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $this->db->select('student.*, enroll.class_id, enroll.section_id, MIN(enroll.transport_id) as transport_id');
        $this->db->from('student');
        $this->db->join('enroll', 'enroll.student_id = student.student_id');
        $this->db->where('enroll.class_id', $class_id);
        if($section_id > 0) {
            $this->db->where('enroll.section_id', $section_id);
        }
        $this->db->where('enroll.year', $year);
        $this->db->where('enroll.term', $term);
        $this->db->where('enroll.mute', '0'); // Only fetch non-muted students
        $this->db->group_by('student.student_id'); // Prevent duplicates
        $this->db->order_by('student.name', 'asc');
        $students = $this->db->get()->result_array();
        
        foreach($students as &$student) {
            $attendance = $this->db->get_where('attendance', [
                'student_id' => $student['student_id'],
                'timestamp' => $timestamp,
                'class_id' => $class_id
            ])->row();
            
            $student['attendance_status'] = $attendance ? $attendance->status : 0;
            $student['attendance_id'] = $attendance ? $attendance->attendance_id : null;
            
            $wallet = $this->Daily_fee_model->get_student_wallet($student['student_id']);
            $student['wallet'] = $wallet;
            
            $this->load->model('Discount_model');
            $student['discounts'] = [];
            $fee_types = ['feeding', 'breakfast', 'classes', 'water'];
            foreach($fee_types as $type) {
                $discount = $this->Discount_model->get_student_discount_for_date(
                    $student['student_id'], $year, $term, $class_id, $type, $timestamp
                );
                if($discount) {
                    $student['discounts'][$type] = $discount;
                }
            }
        }
        
        return $students;
    }
    
    public function get_daily_fee_rates($class_id) {
        $rate = $this->db->get_where('daily_fee_rates', ['class_id' => $class_id])->row_array();
        if(!$rate) {
            return [
                'feeding_rate' => 0,
                'feeding_enabled' => 0,
                'breakfast_rate' => 0,
                'breakfast_enabled' => 0,
                'classes_rate' => 0,
                'classes_enabled' => 0,
                'water_rate' => 0,
                'water_enabled' => 0
            ];
        }
        return $rate;
    }
    
    function get_attendance_records($class_id, $section_id, $date) {
        $this->db->where('class_id', $class_id);
        $this->db->where('section_id', $section_id);
        $this->db->where('timestamp', strtotime($date));
        $query = $this->db->get('attendance');
        
        $records = [];
        foreach($query->result_array() as $row) {
            $records[$row['student_id']] = $row;
        }
        return $records;
    }
    
    public function save_attendance_and_fees($class_id, $section_id, $date, $students) {
        $this->db->trans_start();
        
        // Validate students parameter
        if (!is_array($students) || empty($students)) {
            return ['status' => 'error', 'message' => 'Invalid students data'];
        }
        
        $timestamp = strtotime($date);
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        $issuer_id = $this->session->userdata('login_user_id');
        $issuer_role = $this->session->userdata('login_type');
        
        $this->load->model('Daily_fee_model');
        
        foreach($students as $student_id => $data) {
            // Validate data structure
            if (!is_array($data) || !isset($data['status'])) {
                continue; // Skip invalid entries
            }
            
            $status = $data['status'];
            
            $existing = $this->db->get_where('attendance', [
                'student_id' => $student_id,
                'timestamp' => $timestamp,
                'class_id' => $class_id
            ])->row();
            
            $attendance_update = [
                'status' => $status,
                'marked_by' => $issuer_id,
                'marked_by_role' => $issuer_role
            ];
            
            // Add permission reason if status is 6 (Excused)
            if ($status == 6 && isset($data['permission_reason']) && !empty($data['permission_reason'])) {
                $attendance_update['permission_reason'] = $data['permission_reason'];
            } elseif ($status != 6) {
                // Clear permission reason if status is not Excused
                $attendance_update['permission_reason'] = null;
            }
            
            if($existing) {
                // Check if status is changing from present/late to absent
                $old_status = $existing->status;
                $new_status = $status;
                $status_changed_to_absent = in_array($old_status, [1, 3]) && in_array($new_status, [2, 4, 5, 6]); // 2=absent, 4=sick-home, 5=sick-clinic, 6=excused
                
                $this->db->where('attendance_id', $existing->attendance_id);
                $this->db->update('attendance', $attendance_update);
                
                // If changing to absent, reverse charges and payments, then recalculate wallet
                if ($status_changed_to_absent) {
                    // Get the payment transaction for this date
                    $existing_payment = $this->db->get_where('daily_fee_transactions', [
                        'student_id' => $student_id,
                        'payment_date' => $timestamp
                    ])->row();
                    
                    // Get charge log for this date
                    $charge_log = $this->db->get_where('daily_charge_log', [
                        'student_id' => $student_id,
                        'charge_date' => $timestamp
                    ])->row();
                    
                    // Reverse charges - add back to wallet as prepaid balance
                    if ($charge_log && $charge_log->total_charged > 0) {
                        $wallet = $this->Daily_fee_model->get_student_wallet($student_id);
                        
                        // Reverse feeding charge
                        if ($charge_log->feeding_charged > 0) {
                            if ($wallet['feeding_arrears'] >= $charge_log->feeding_charged) {
                                $wallet['feeding_arrears'] -= $charge_log->feeding_charged;
                            } else if ($wallet['feeding_arrears'] > 0) {
                                $remainder = $charge_log->feeding_charged - $wallet['feeding_arrears'];
                                $wallet['feeding_arrears'] = 0;
                                $wallet['feeding_balance'] += $remainder;
                            } else {
                                $wallet['feeding_balance'] += $charge_log->feeding_charged;
                            }
                        }
                        
                        // Reverse breakfast charge
                        if ($charge_log->breakfast_charged > 0) {
                            if ($wallet['breakfast_arrears'] >= $charge_log->breakfast_charged) {
                                $wallet['breakfast_arrears'] -= $charge_log->breakfast_charged;
                            } else if ($wallet['breakfast_arrears'] > 0) {
                                $remainder = $charge_log->breakfast_charged - $wallet['breakfast_arrears'];
                                $wallet['breakfast_arrears'] = 0;
                                $wallet['breakfast_balance'] += $remainder;
                            } else {
                                $wallet['breakfast_balance'] += $charge_log->breakfast_charged;
                            }
                        }
                        
                        // Reverse classes charge
                        if ($charge_log->classes_charged > 0) {
                            if ($wallet['classes_arrears'] >= $charge_log->classes_charged) {
                                $wallet['classes_arrears'] -= $charge_log->classes_charged;
                            } else if ($wallet['classes_arrears'] > 0) {
                                $remainder = $charge_log->classes_charged - $wallet['classes_arrears'];
                                $wallet['classes_arrears'] = 0;
                                $wallet['classes_balance'] += $remainder;
                            } else {
                                $wallet['classes_balance'] += $charge_log->classes_charged;
                            }
                        }
                        
                        // Reverse water charge
                        if ($charge_log->water_charged > 0) {
                            if ($wallet['water_arrears'] >= $charge_log->water_charged) {
                                $wallet['water_arrears'] -= $charge_log->water_charged;
                            } else if ($wallet['water_arrears'] > 0) {
                                $remainder = $charge_log->water_charged - $wallet['water_arrears'];
                                $wallet['water_arrears'] = 0;
                                $wallet['water_balance'] += $remainder;
                            } else {
                                $wallet['water_balance'] += $charge_log->water_charged;
                            }
                        }
                        
                        // Reverse transport charge
                        if ($charge_log->transport_charged > 0) {
                            if ($wallet['transport_arrears'] >= $charge_log->transport_charged) {
                                $wallet['transport_arrears'] -= $charge_log->transport_charged;
                            } else if ($wallet['transport_arrears'] > 0) {
                                $remainder = $charge_log->transport_charged - $wallet['transport_arrears'];
                                $wallet['transport_arrears'] = 0;
                                $wallet['transport_balance'] += $remainder;
                            } else {
                                $wallet['transport_balance'] += $charge_log->transport_charged;
                            }
                        }
                        
                        // Update wallet with reversed charges (now prepaid balance)
                        $this->db->where('student_id', $student_id)->update('daily_fee_wallet', [
                            'feeding_balance' => $wallet['feeding_balance'],
                            'feeding_arrears' => $wallet['feeding_arrears'],
                            'breakfast_balance' => $wallet['breakfast_balance'],
                            'breakfast_arrears' => $wallet['breakfast_arrears'],
                            'classes_balance' => $wallet['classes_balance'],
                            'classes_arrears' => $wallet['classes_arrears'],
                            'water_balance' => $wallet['water_balance'],
                            'water_arrears' => $wallet['water_arrears'],
                            'transport_balance' => $wallet['transport_balance'],
                            'transport_arrears' => $wallet['transport_arrears'],
                            'last_updated' => time()
                        ]);
                        
                        log_message('info', "Reversed charges for student {$student_id} - marked absent, charges added to prepaid balance");
                    }
                    
                    // Delete charge log for this date (will be recreated if marked present again)
                    $this->db->where('student_id', $student_id)
                             ->where('charge_date', $timestamp)
                             ->delete('daily_charge_log');
                    
                    // KEEP the payment transaction (never delete)
                    // Payment stays as prepaid balance for future use
                    
                    // Reset payment status in attendance record
                    $this->db->where('student_id', $student_id)
                             ->where('timestamp', $timestamp)
                             ->update('attendance', ['payment_status' => null]);
                }
            } else {
                $attendance_update['student_id'] = $student_id;
                $attendance_update['class_id'] = $class_id;
                $attendance_update['section_id'] = $section_id;
                $attendance_update['timestamp'] = $timestamp;
                $attendance_update['year'] = $year;
                $attendance_update['term'] = $term;
                $this->db->insert('attendance', $attendance_update);
            }
            
            // Process daily charges ONLY when student is marked PRESENT or LATE
            // AND charges haven't been applied yet (checked by process_daily_charges internally)
            if($status == 1 || $status == 3) {
                // Use transport_direction if provided, otherwise don't pass transport_status
                // In cashier-only mode, transport fields are not visible so parameter won't be sent
                $options = [];
                if (isset($data['transport_direction'])) {
                    $options['transport_status'] = $data['transport_direction'];
                }
                $this->Daily_fee_model->process_daily_charges($student_id, $timestamp, $class_id, $year, $term, null, $options);
            }
            
            // Handle bus attendance for transport students
            // IMPORTANT: Only process if transport_direction is explicitly provided in the data
            // In cashier-only mode, these fields are not visible so parameters won't be sent
            if (isset($data['transport_direction'])) {
                $transport_id = $data['transport_id'] ?? null;
                $transport_direction = $data['transport_direction'];
                $transport_boarded = isset($data['transport_boarded']) ? $data['transport_boarded'] : 0;
                
                if($transport_id && $transport_direction != 'none') {
                    // Check if bus_attendance table exists
                    if($this->db->table_exists('bus_attendance')) {
                        $bus_attendance_data = [
                            'student_id' => $student_id,
                            'transport_id' => $transport_id,
                            'attendance_date' => $timestamp,
                            'direction' => $transport_direction,
                            'status' => $transport_boarded ? 'boarded' : 'expected',
                            'marked_by' => $issuer_id,
                            'marked_at' => time()
                        ];
                        
                        // Check for existing record
                        $existing_bus = $this->db->get_where('bus_attendance', [
                            'student_id' => $student_id,
                            'attendance_date' => $timestamp
                        ])->row();
                        
                        if($existing_bus) {
                            $this->db->where('id', $existing_bus->id);
                            $this->db->update('bus_attendance', $bus_attendance_data);
                        } else {
                            $this->db->insert('bus_attendance', $bus_attendance_data);
                        }
                    }
                }
            }
            
            // CRITICAL: Handle breakfast opt-in/opt-out changes
            // Check if breakfast preference changed and reverse charge if opting out
            // IMPORTANT: Only process if breakfast_opted is explicitly provided in the data
            if (isset($data['breakfast_opted'])) {
                $breakfast_opted = intval($data['breakfast_opted']);
                $prefs = $this->Daily_fee_model->get_student_preferences($student_id);
                $old_breakfast_pref = $prefs['breakfast_subscribed'];
                
                // If opting OUT (was 1, now 0), reverse any breakfast charge for today
                if ($old_breakfast_pref == 1 && $breakfast_opted == 0) {
                $today_log = $this->db->get_where('daily_charge_log', [
                    'student_id' => $student_id,
                    'charge_date' => $timestamp
                ])->row();
                
                if ($today_log && $today_log->breakfast_charged > 0) {
                    $wallet = $this->Daily_fee_model->get_student_wallet($student_id);
                    $amt = $today_log->breakfast_charged;
                    
                    // Check if there's a payment transaction for today
                    $existing_payment = $this->db->get_where('daily_fee_transactions', [
                        'student_id' => $student_id,
                        'payment_date' => $timestamp
                    ])->row();
                    
                    // Reverse wallet charge
                    $balance_before = $wallet['breakfast_balance'];
                    $arrears_before = $wallet['breakfast_arrears'];
                    
                    if ($wallet['breakfast_arrears'] >= $amt) {
                        $wallet['breakfast_arrears'] -= $amt;
                    } else if ($wallet['breakfast_arrears'] > 0) {
                        $remainder = $amt - $wallet['breakfast_arrears'];
                        $wallet['breakfast_arrears'] = 0;
                        $wallet['breakfast_balance'] += $remainder;
                    } else {
                        $wallet['breakfast_balance'] += $amt;
                    }
                    
                    // Update wallet
                    $this->db->where('student_id', $student_id)->update('daily_fee_wallet', [
                        'breakfast_balance' => $wallet['breakfast_balance'],
                        'breakfast_arrears' => $wallet['breakfast_arrears'],
                        'last_updated' => time()
                    ]);
                    
                    // Update charge log
                    $this->db->where('student_id', $student_id)
                             ->where('charge_date', $timestamp)
                             ->update('daily_charge_log', [
                                 'breakfast_charged' => 0,
                                 'total_charged' => $today_log->total_charged - $amt
                             ]);
                    
                    // Update attendance table
                    $this->db->where('student_id', $student_id)
                             ->where('timestamp', $timestamp)
                             ->update('attendance', ['breakfast_charged' => 0]);
                    
                    // CRITICAL: Update payment transaction if exists
                    if ($existing_payment && $existing_payment->breakfast_amount > 0) {
                        $new_total = $existing_payment->total_amount - $existing_payment->breakfast_amount;
                        
                        if ($new_total > 0) {
                            // Update transaction to remove breakfast payment
                            $this->db->where('id', $existing_payment->id)->update('daily_fee_transactions', [
                                'breakfast_amount' => 0,
                                'total_amount' => $new_total,
                                'modified_at' => time()
                            ]);
                        } else {
                            // No other payments - KEEP transaction as prepaid balance
                            // The money stays in wallet for future use
                            log_message('info', "Keeping transaction {$existing_payment->id} as prepaid balance - breakfast opt-out");
                            
                            // Reset payment status in attendance
                            $this->db->where('student_id', $student_id)
                                     ->where('timestamp', $timestamp)
                                     ->update('attendance', ['payment_status' => null]);
                        }
                    }
                }
            }
            
            // If opting IN (was 0, now 1) AND student is present/late, apply breakfast charge
            if ($old_breakfast_pref == 0 && $breakfast_opted == 1 && ($status == 1 || $status == 3)) {
                $today_log = $this->db->get_where('daily_charge_log', [
                    'student_id' => $student_id,
                    'charge_date' => $timestamp
                ])->row();
                
                // Only charge if not already charged
                if ($today_log && ($today_log->breakfast_charged == 0 || $today_log->breakfast_charged === null)) {
                    $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
                    $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
                    
                    $rates = $this->Daily_fee_model->get_class_rates($class_id, $year, $term);
                    
                    if (is_fee_module_enabled('breakfast') && $rates['breakfast_enabled'] && $rates['breakfast_rate'] > 0) {
                        $this->load->model('Discount_model');
                        $amt = $rates['breakfast_rate'];
                        $disc = $this->Discount_model->get_student_discount_for_date($student_id, $year, $term, $class_id, 'breakfast', $timestamp);
                        
                        if ($disc['has_discount']) {
                            if ($disc['discount_type'] == 'percentage') {
                                $amt = max(0, $amt - ($amt * $disc['discount_value'] / 100));
                            } else {
                                $amt = max(0, $amt - min($disc['discount_value'], $amt));
                            }
                        }
                        
                        if ($amt > 0) {
                            $wallet = $this->Daily_fee_model->get_student_wallet($student_id);
                            
                            // Apply charge to wallet
                            $balance_before = $wallet['breakfast_balance'];
                            $arrears_before = $wallet['breakfast_arrears'];
                            
                            if ($wallet['breakfast_balance'] >= $amt) {
                                $wallet['breakfast_balance'] -= $amt;
                            } else if ($wallet['breakfast_balance'] > 0) {
                                $arrears_amount = $amt - $wallet['breakfast_balance'];
                                $wallet['breakfast_balance'] = 0;
                                $wallet['breakfast_arrears'] += $arrears_amount;
                            } else {
                                $wallet['breakfast_arrears'] += $amt;
                            }
                            
                            // Update wallet
                            $this->db->where('student_id', $student_id)->update('daily_fee_wallet', [
                                'breakfast_balance' => $wallet['breakfast_balance'],
                                'breakfast_arrears' => $wallet['breakfast_arrears'],
                                'last_updated' => time()
                            ]);
                            
                            // Update charge log
                            $this->db->where('student_id', $student_id)
                                     ->where('charge_date', $timestamp)
                                     ->update('daily_charge_log', [
                                         'breakfast_charged' => $amt,
                                         'total_charged' => $today_log->total_charged + $amt
                                     ]);
                            
                            // Update attendance table
                            $this->db->where('student_id', $student_id)
                                     ->where('timestamp', $timestamp)
                                     ->update('attendance', ['breakfast_charged' => $amt]);
                        }
                    }
                }
            }
            
            // Update breakfast preference
            if ($old_breakfast_pref != $breakfast_opted) {
                $this->db->where('student_id', $student_id);
                $this->db->update('student_daily_fee_preferences', [
                    'breakfast_subscribed' => $breakfast_opted,
                    'updated_at' => time()
                ]);
            }
        } // End of if (isset($data['breakfast_opted']))
            
            // Process payments if any
            $total_paid = floatval($data['feeding_paid'] ?? 0) + floatval($data['breakfast_paid'] ?? 0) + 
                         floatval($data['classes_paid'] ?? 0) + floatval($data['water_paid'] ?? 0) + 
                         floatval($data['transport_paid'] ?? 0);
            
            // Check if there's an existing payment for this date
            $existing_payment = $this->db->get_where('daily_fee_transactions', [
                'student_id' => $student_id,
                'payment_date' => $timestamp
            ])->row();
            
            if($total_paid > 0) {
                $wallet = $this->Daily_fee_model->get_student_wallet($student_id);
                $total_arrears = $wallet['feeding_arrears'] + $wallet['breakfast_arrears'] + 
                                $wallet['classes_arrears'] + $wallet['water_arrears'] + $wallet['transport_arrears'];
                
                $payment_type = $total_arrears > 0 ? ($total_paid >= $total_arrears ? 'mixed' : 'arrears') : 'advance';
                $water_opted = isset($data['water_opted']) ? intval($data['water_opted']) : 1;
                $breakfast_opted = isset($data['breakfast_opted']) ? intval($data['breakfast_opted']) : 0;
                
                $payment_data = [
                    'student_id' => $student_id,
                    'payment_date' => $timestamp,
                    'feeding_amount' => floatval($data['feeding_paid'] ?? 0),
                    'breakfast_amount' => floatval($data['breakfast_paid'] ?? 0),
                    'classes_amount' => floatval($data['classes_paid'] ?? 0),
                    'water_amount' => floatval($data['water_paid'] ?? 0),
                    'transport_amount' => floatval($data['transport_paid'] ?? 0),
                    'payment_type' => $payment_type,
                    'payment_method' => 1,
                    'collected_by' => $issuer_id,
                    'collection_point' => 'attendance_portal',
                    'breakfast_opted' => $breakfast_opted,
                    'water_opted' => $water_opted
                ];
                
                if($existing_payment) {
                    $this->Daily_fee_model->update_payment($existing_payment->id, $payment_data);
                } else {
                    $this->Daily_fee_model->process_payment($payment_data);
                }
                
                $payment_status = $total_arrears > 0 ? ($total_paid >= $total_arrears ? 'paid' : 'partial') : 'advance';
                $attendance_payment_update = ['payment_status' => $payment_status];
                
                if($breakfast_opted == 1) {
                    $attendance_payment_update['breakfast_opted'] = 1;
                }
                
                $this->db->where('student_id', $student_id);
                $this->db->where('timestamp', $timestamp);
                $this->db->update('attendance', $attendance_payment_update);
            } else if($existing_payment) {
                // Payment changed to 0 - KEEP transaction as prepaid balance
                // The money stays in wallet for future use
                log_message('info', "Keeping transaction {$existing_payment->id} as prepaid balance - payment changed to zero");
                
                // Reset payment status in attendance record
                $this->db->where('student_id', $student_id)
                         ->where('timestamp', $timestamp)
                         ->update('attendance', ['payment_status' => null]);
            }
        }
        
        $this->db->trans_complete();
        
        if($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('attendance_saved_successfully')];
    }
    
    public function get_daily_stats($date) {
        $timestamp = strtotime($date);
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        // Total expected = all enrolled students for current term
        $total = $this->db->where(['year' => $year, 'term' => $term])->count_all_results('enroll');
        
        // Actual attendance counts for the date
        $present = $this->db->where(['timestamp' => $timestamp, 'status' => 1])->count_all_results('attendance');
        $absent = $this->db->where(['timestamp' => $timestamp, 'status' => 2])->count_all_results('attendance');
        $late = $this->db->where(['timestamp' => $timestamp, 'status' => 3])->count_all_results('attendance');
        
        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0
        ];
    }
    
    public function get_teacher_assigned_classes($teacher_id) {
        $this->db->select('class.*');
        $this->db->from('class');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->order_by('name', 'asc');
        $this->db->order_by('name_numeric', 'asc');
        return $this->db->get()->result_array();
    }
    
    
    function mark_all_present($class_id, $section_id, $date) {
        $students = $this->get_class_students($class_id, $section_id);
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        foreach($students as $student) {
            $this->db->replace('attendance', [
                'student_id' => $student['student_id'],
                'class_id' => $class_id,
                'section_id' => $section_id,
                'timestamp' => strtotime($date),
                'status' => 1,
                'year' => $year,
                'term' => $term
            ]);
        }
        
        return ['status' => 'success', 'message' => 'All students marked present'];
    }
    
    /**
     * PERFORMANCE OPTIMIZATION: Batch query to fetch students with details
     * Replaces N individual student queries with a single JOIN query
     * 
     * @param int $class_id Class ID to filter by
     * @param int $section_id Section ID to filter by
     * @param string $year Academic year
     * @param string $term Academic term
     * @param array|null $student_ids Optional array of specific student IDs (for per-student filtering)
     * @return array Students data with all required details
     */
    public function get_students_with_details($class_id, $section_id, $year, $term, $student_ids = null) {
        $this->db->select('s.student_id, s.name, s.birthday, s.sex, e.roll, e.class_id, e.section_id');
        $this->db->from('enroll e');
        $this->db->join('student s', 's.student_id = e.student_id');
        
        // Per-student filtering mode: use student_ids array instead of class/section
        if ($student_ids !== null && is_array($student_ids) && count($student_ids) > 0) {
            $this->db->where_in('s.student_id', $student_ids);
        } else {
            // Class/section filtering mode (original behavior)
            $this->db->where('e.class_id', $class_id);
            if ($section_id > 0) {
                $this->db->where('e.section_id', $section_id);
            }
        }
        
        $this->db->where('e.year', $year);
        if ($term) {
            $this->db->where('e.term', $term);
        }
        $this->db->where('e.mute', '0'); // Only active students
        $this->db->group_by('s.student_id'); // Prevent duplicates
        $this->db->order_by('e.roll', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * PERFORMANCE OPTIMIZATION: Batch query to fetch attendance records
     * Replaces N×D individual queries (N students × D days) with a single query
     * 
     * @param int $class_id Class ID to filter by
     * @param int $section_id Section ID to filter by
     * @param int $month Month (1-12)
     * @param int $year Year (YYYY)
     * @param string $term Academic term
     * @param array|null $student_ids Optional array of specific student IDs (for per-student filtering)
     * @return array Attendance records with student_id, status, timestamp
     */
    public function get_attendance_batch($class_id, $section_id, $month, $year, $term, $student_ids = null) {
        $this->db->select('student_id, status, timestamp, class_id, section_id');
        $this->db->from('attendance');
        
        // Per-student filtering mode: use student_ids array instead of class/section
        if ($student_ids !== null && is_array($student_ids) && count($student_ids) > 0) {
            $this->db->where_in('student_id', $student_ids);
        } else {
            // Class/section filtering mode (original behavior)
            $this->db->where('class_id', $class_id);
            if ($section_id > 0) {
                $this->db->where('section_id', $section_id);
            }
        }
        
        // Date range filtering - fetch all records for the specified month
        $start_timestamp = strtotime("$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00");
        $end_timestamp = strtotime(date("Y-m-t", $start_timestamp) . " 23:59:59");
        
        $this->db->where('timestamp >=', $start_timestamp);
        $this->db->where('timestamp <=', $end_timestamp);
        
        if ($term) {
            $this->db->where('term', $term);
        }
        
        return $this->db->get()->result_array();
    }
    
    /**
     * PERFORMANCE OPTIMIZATION: Autocomplete search for per-student selector
     * Supports Select2 AJAX search with pagination
     * 
     * @param string $search_term Student name search term
     * @param int $limit Maximum results to return (default: 50)
     * @return array Students in Select2 format (id, text, class_id, section_id)
     */
    public function search_students($search_term, $limit = 50) {
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $this->db->select('s.student_id as id, s.name as text, e.class_id, e.section_id, c.name as class_name');
        $this->db->from('student s');
        $this->db->join('enroll e', 'e.student_id = s.student_id');
        $this->db->join('class c', 'c.class_id = e.class_id', 'left');
        $this->db->like('s.name', $search_term);
        // REMOVED: $this->db->where('s.active', 1); - 'active' column doesn't exist in student table
        $this->db->where('e.mute', '0'); // Filter out muted students via enrollment
        $this->db->where('e.year', $year);
        if ($term) {
            $this->db->where('e.term', $term);
        }
        $this->db->group_by('s.student_id'); // Prevent duplicates from multiple enrollments
        $this->db->order_by('s.name', 'ASC');
        $this->db->limit($limit);
        
        $results = $this->db->get()->result_array();
        
        // Enhance display text with class name for better UX
        foreach ($results as &$result) {
            if (!empty($result['class_name'])) {
                $result['text'] = $result['text'] . ' (' . $result['class_name'] . ')';
            }
        }
        
        return $results;
    }

    /**
	 * Get attendance records by student IDs with batch query optimization
	 * Returns attendance counts grouped by student and status
	 * 
	 * @param array $student_ids Array of student IDs
	 * @param int $start_timestamp Start date timestamp
	 * @param int $end_timestamp End date timestamp
	 * @param int|null $status Optional status filter (1=present, 2=absent, 3=late, 4=sick-home, 5=sick-clinic)
	 * @return array Multi-dimensional array [student_id][status] = count
	 */
	public function get_attendance_by_student_ids($student_ids, $start_timestamp, $end_timestamp, $status = null) {
		if(empty($student_ids)) {
			return [];
		}
		
		// Build query to count attendance by student and status
		$this->db->select('student_id, status, COUNT(*) as count');
		$this->db->from('attendance');
		$this->db->where_in('student_id', $student_ids);
		$this->db->where('timestamp >=', $start_timestamp);
		$this->db->where('timestamp <=', $end_timestamp);
		
		if($status) {
			$this->db->where('status', $status);
		}
		
		$this->db->group_by(['student_id', 'status']);
		$results = $this->db->get()->result_array();
		
		// Transform into nested array: [student_id][status] = count
		$data = [];
		foreach($results as $row) {
			if(!isset($data[$row['student_id']])) {
				$data[$row['student_id']] = [];
			}
			$data[$row['student_id']][$row['status']] = intval($row['count']);
		}
		
		return $data;
	}
}

	
