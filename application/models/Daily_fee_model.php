<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Fee Management Model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * Handles all daily fee operations including wallet management, 
 * auto-charging, payment processing, and balance tracking.
 * 
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Daily_fee_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Check if current user can collect daily fees
     * Enhanced to support classroom, cashier, and hybrid modes
     * 
     * @param string|null $fee_type The fee type being collected (feeding, breakfast, classes, water, transport)
     * @param string|null $collection_point Where the collection is happening ('attendance_portal', 'cashier_portal', 'conductor_portal')
     * @return bool
     */
    public function can_collect_daily_fees($fee_type = null, $collection_point = null) {
        $user_id = $this->session->userdata('admin_id');
        $teacher_id = $this->session->userdata('teacher_id');
        $role = $this->session->userdata('login_type');
        
        // Get school-wide collection mode
        $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
        
        // Conductors can ONLY collect transport fare
        if ($role == 'conductor') {
            return $fee_type === 'transport' || $fee_type === null;
        }
        
        // TEACHER PERMISSIONS
        if ($role == 'teacher') {
            // Get teacher fee collection mode
            $teacher_mode = get_settings('teacher_fee_collection_mode') ?: 'restricted';
            
            // Restricted teachers cannot collect any fees
            if ($teacher_mode == 'restricted') {
                return false;
            }
            
            // All allowed - can collect all fee types
            if ($teacher_mode == 'all_allowed') {
                // But still check collection mode
                if ($collection_mode == 'cashier' && $collection_point != 'attendance_portal') {
                    return false; // Cashier mode only - teachers can only collect during attendance
                }
                return true;
            }
            
            // Selective - check specific fee type permissions
            if ($teacher_mode == 'selective') {
                // Check collection mode first
                if ($collection_mode == 'cashier' && $collection_point != 'attendance_portal') {
                    return false;
                }
                
                // Get teacher's assigned fee collection permissions
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                
                $this->db->where('teacher_id', $teacher_id);
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $assignments = $this->db->get('fee_collection_assignments')->row();
                
                if (!$assignments) {
                    return false;
                }
                
                // If no specific fee type requested, check if any type is allowed
                if ($fee_type === null) {
                    return ($assignments->can_collect_feeding == 1 || 
                            $assignments->can_collect_breakfast == 1 || 
                            $assignments->can_collect_classes == 1 || 
                            $assignments->can_collect_water == 1 || 
                            $assignments->can_collect_transport == 1);
                }
                
                // Check specific fee type
                $column_map = [
                    'feeding' => 'can_collect_feeding',
                    'breakfast' => 'can_collect_breakfast',
                    'classes' => 'can_collect_classes',
                    'water' => 'can_collect_water',
                    'transport' => 'can_collect_transport'
                ];
                
                $column = $column_map[$fee_type] ?? null;
                if ($column && isset($assignments->$column)) {
                    return $assignments->$column == 1;
                }
                
                return false;
            }
            
            return false;
        }
        
        // ADMIN PERMISSIONS
        if ($role == 'admin') {
            // Check database for explicit permission
            $user = $this->db->get_where('admin', ['admin_id' => $user_id])->row();
            
            if (!$user || $user->can_collect_daily_fees != 1) {
                return false;
            }
            
            // In cashier mode, only allow collection at cashier portal
            if ($collection_mode == 'cashier' && $collection_point == 'attendance_portal') {
                return false;
            }
            
            // In classroom mode, only allow collection at attendance portal
            if ($collection_mode == 'classroom' && $collection_point == 'cashier_portal') {
                return false;
            }
            
            // Hybrid mode allows both
            return true;
        }
        
        return false;
    }

    /**
     * Get user's collection point based on role
     */
    public function get_user_collection_point() {
        $user_id = $this->session->userdata('admin_id');
        $user = $this->db->get_where('admin', ['admin_id' => $user_id])->row();
        return $user ? $user->collection_point : 'office';
    }
    
    /**
     * Check if user can collect specific fee type
     * 
     * @param string $fee_type The fee type (feeding, breakfast, classes, water, transport)
     * @param string|null $collection_point Where the collection is happening
     * @return bool
     */
    public function can_collect_fee_type($fee_type, $collection_point = null) {
        $role = $this->session->userdata('login_type');
        
        // Conductors can ONLY collect transport
        if ($role == 'conductor') {
            return $fee_type === 'transport';
        }
        
        // Use the enhanced can_collect_daily_fees method
        return $this->can_collect_daily_fees($fee_type, $collection_point);
    }
    
    /**
     * Get all fee collection permissions for current user
     * Returns array of fee types the user can collect
     * 
     * @param string|null $collection_point Where the collection is happening
     * @return array
     */
    public function get_fee_collection_permissions($collection_point = null) {
        $role = $this->session->userdata('login_type');
        $teacher_id = $this->session->userdata('teacher_id');
        $admin_id = $this->session->userdata('admin_id');
        
        $permissions = [
            'can_collect_fees' => false,
            'can_collect_feeding' => false,
            'can_collect_breakfast' => false,
            'can_collect_classes' => false,
            'can_collect_water' => false,
            'can_collect_transport' => false
        ];
        
        // Conductors only collect transport
        if ($role == 'conductor') {
            $permissions['can_collect_fees'] = true;
            $permissions['can_collect_transport'] = true;
            return $permissions;
        }
        
        // Teachers
        if ($role == 'teacher') {
            $teacher_mode = get_settings('teacher_fee_collection_mode') ?: 'restricted';
            $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
            
            // Check collection mode restrictions
            if ($collection_mode == 'cashier' && $collection_point != 'attendance_portal') {
                return $permissions; // All false
            }
            
            if ($teacher_mode == 'restricted') {
                return $permissions; // All false
            }
            
            if ($teacher_mode == 'all_allowed') {
                $permissions['can_collect_fees'] = true;
                $permissions['can_collect_feeding'] = true;
                $permissions['can_collect_breakfast'] = true;
                $permissions['can_collect_classes'] = true;
                $permissions['can_collect_water'] = true;
                $permissions['can_collect_transport'] = true;
                return $permissions;
            }
            
            if ($teacher_mode == 'selective') {
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                
                $this->db->where('teacher_id', $teacher_id);
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $assignments = $this->db->get('fee_collection_assignments')->row();
                
                if ($assignments) {
                    $permissions['can_collect_feeding'] = $assignments->can_collect_feeding == 1;
                    $permissions['can_collect_breakfast'] = $assignments->can_collect_breakfast == 1;
                    $permissions['can_collect_classes'] = $assignments->can_collect_classes == 1;
                    $permissions['can_collect_water'] = $assignments->can_collect_water == 1;
                    $permissions['can_collect_transport'] = $assignments->can_collect_transport == 1;
                    $permissions['can_collect_fees'] = (
                        $permissions['can_collect_feeding'] || 
                        $permissions['can_collect_breakfast'] || 
                        $permissions['can_collect_classes'] || 
                        $permissions['can_collect_water'] || 
                        $permissions['can_collect_transport']
                    );
                }
                return $permissions;
            }
            
            return $permissions;
        }
        
        // Admins
        if ($role == 'admin') {
            $user = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
            
            if ($user && $user->can_collect_daily_fees == 1) {
                $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
                
                // Check collection mode restrictions
                if ($collection_mode == 'cashier' && $collection_point == 'attendance_portal') {
                    return $permissions; // Not allowed in attendance portal
                }
                if ($collection_mode == 'classroom' && $collection_point == 'cashier_portal') {
                    return $permissions; // Not allowed in cashier portal
                }
                
                $permissions['can_collect_fees'] = true;
                $permissions['can_collect_feeding'] = true;
                $permissions['can_collect_breakfast'] = true;
                $permissions['can_collect_classes'] = true;
                $permissions['can_collect_water'] = true;
                $permissions['can_collect_transport'] = true;
            }
            return $permissions;
        }
        
        return $permissions;
    }

    /**
     * Get student's wallet balance and arrears
     */
    public function get_student_wallet($student_id) {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // First, try to get wallet by student_id, year, and term
        $wallet = $this->db->get_where('daily_fee_wallet', [
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term
        ])->row_array();
        
        if ($wallet) {
            return $wallet;
        }
        
        // Check if a wallet exists for this student_id (regardless of year/term)
        // This handles the case where the table has UNIQUE constraint on student_id
        $existing_wallet = $this->db->get_where('daily_fee_wallet', [
            'student_id' => $student_id
        ])->row_array();
        
        if ($existing_wallet) {
            // Update the year and term to current, reset balances for new term
            $this->db->where('student_id', $student_id);
            $this->db->update('daily_fee_wallet', [
                'year' => $running_year,
                'term' => $running_term,
                'last_updated' => time()
            ]);
            
            // Return updated wallet
            return $this->db->get_where('daily_fee_wallet', [
                'student_id' => $student_id
            ])->row_array();
        }
        
        // No wallet exists, create new one
        $this->db->insert('daily_fee_wallet', [
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term,
            'feeding_balance' => 0,
            'feeding_arrears' => 0,
            'breakfast_balance' => 0,
            'breakfast_arrears' => 0,
            'classes_balance' => 0,
            'classes_arrears' => 0,
            'water_balance' => 0,
            'water_arrears' => 0,
            'transport_balance' => 0,
            'transport_arrears' => 0,
            'last_updated' => time()
        ]);
        return $this->get_student_wallet($student_id);
    }

    /**
     * Get daily fee rates for a class
     */
    public function get_class_rates($class_id, $year, $term = null, $sem = null) {
        $this->db->where('class_id', $class_id);
        $this->db->where('year', $year);
        
        if ($term !== null) $this->db->where('term', $term);
        //if ($sem !== null) $this->db->where('sem', $sem);
        
        $this->db->order_by('id', 'DESC');
        $rates = $this->db->get('daily_fee_rates', 1)->row_array();
        
        if (!$rates) {
            // Return default rates if not configured
            return [
                'feeding_rate' => 0.00,
                'breakfast_rate' => 0.00,
                'classes_rate' => 0.00,
                'water_rate' => 0.00,
                'breakfast_enabled' => 0,
                'water_enabled' => 0
            ];
        }
        
        return $rates;
    }

    /**
     * Get student preferences for daily fees
     */
    public function get_student_preferences($student_id) {
        $prefs = $this->db->get_where('student_daily_fee_preferences', ['student_id' => $student_id])->row_array();
        
        if (!$prefs) {
            // Create default preferences
            $this->db->insert('student_daily_fee_preferences', [
                'student_id' => $student_id,
                'breakfast_subscribed' => 0,
                'water_subscribed' => 1,
                'auto_deduct_enabled' => 1,
                'updated_at' => time()
            ]);
            return $this->get_student_preferences($student_id);
        }
        
        return $prefs;
    }

    /**
     * ENTERPRISE-GRADE: Process daily charges when attendance is marked
     * Handles: rates, discounts, wallet deduction/arrears, accounting
     */
    public function process_daily_charges($student_id, $attendance_date, $class_id, $year, $term = null, $sem = null, $options = []) {
        // Normalize to midnight timestamp
        $charge_date = strtotime(date('Y-m-d', $attendance_date));
        
        // Check if already charged for this date
        $existing = $this->db->get_where('daily_charge_log', [
            'student_id' => $student_id,
            'charge_date' => $charge_date
        ])->row();
        
        // CRITICAL: Prevent double billing
        // Only prevent if feeding OR classes were already charged (indicates attendance was marked)
        if ($existing && (
            $existing->feeding_charged > 0 || 
            $existing->classes_charged > 0
        )) {
            return; // Attendance already processed
        }
        
        // ADDITIONAL CHECK: If transport is being charged now and was already charged before
        // Skip the transport charge to prevent double deduction
        $skip_transport = false;
        if ($existing && $existing->transport_charged > 0 && isset($options['transport_status']) && $options['transport_status'] !== 'none') {
            $skip_transport = true;
        }
        
        $wallet = $this->get_student_wallet($student_id);
        $rates = $this->get_class_rates($class_id, $year, $term, $sem);
        $prefs = $this->get_student_preferences($student_id);
        
        // Load Discount_model for discount calculations
        $this->load->model('Discount_model');
        
        $charges = [
            'feeding_charged' => 0.00,
            'breakfast_charged' => 0.00,
            'classes_charged' => 0.00,
            'water_charged' => 0.00,
            'transport_charged' => 0.00
        ];
        
        $discount_records = []; // Track discount applications
        
        // 1. FEEDING CHARGE (Lunch - Mandatory)
        if (is_fee_module_enabled('feeding') && $rates['feeding_rate'] > 0) {
            $original_amount = $rates['feeding_rate'];
            $discount_info = $this->Discount_model->get_student_discount_for_date($student_id, $year, $term, $class_id, 'feeding', $charge_date);
            
            if ($discount_info['has_discount']) {
                if ($discount_info['discount_type'] == 'percentage') {
                    $discount_amount = $original_amount * ($discount_info['discount_value'] / 100);
                } else {
                    $discount_amount = min($discount_info['discount_value'], $original_amount);
                }
                $final_amount = max(0, $original_amount - $discount_amount);
                
                $charges['feeding_charged'] = $final_amount;
                $discount_records[] = [
                    'fee_type' => 'feeding',
                    'profile_id' => $discount_info['profile_id'],
                    'original_amount' => $original_amount,
                    'discount_percentage' => $discount_info['discount_type'] == 'percentage' ? $discount_info['discount_value'] : 0,
                    'discount_amount' => $discount_amount,
                    'final_amount' => $final_amount
                ];
            } else {
                $charges['feeding_charged'] = $original_amount;
            }
            
            $this->apply_charge($student_id, 'feeding', $charges['feeding_charged'], $wallet, $prefs['auto_deduct_enabled']);
        }
        
        // 2. BREAKFAST CHARGE (Optional - only if subscribed)
        if (is_fee_module_enabled('breakfast') && $rates['breakfast_enabled'] && $prefs['breakfast_subscribed']) {
            $original_amount = $rates['breakfast_rate'];
            $discount_info = $this->Discount_model->get_student_discount_for_date($student_id, $year, $term, $class_id, 'breakfast', $charge_date);
            
            if ($discount_info['has_discount']) {
                if ($discount_info['discount_type'] == 'percentage') {
                    $discount_amount = $original_amount * ($discount_info['discount_value'] / 100);
                } else {
                    $discount_amount = min($discount_info['discount_value'], $original_amount);
                }
                $final_amount = max(0, $original_amount - $discount_amount);
                
                $charges['breakfast_charged'] = $final_amount;
                $discount_records[] = [
                    'fee_type' => 'breakfast',
                    'profile_id' => $discount_info['profile_id'],
                    'original_amount' => $original_amount,
                    'discount_percentage' => $discount_info['discount_type'] == 'percentage' ? $discount_info['discount_value'] : 0,
                    'discount_amount' => $discount_amount,
                    'final_amount' => $final_amount
                ];
            } else {
                $charges['breakfast_charged'] = $original_amount;
            }
            
            $this->apply_charge($student_id, 'breakfast', $charges['breakfast_charged'], $wallet, $prefs['auto_deduct_enabled']);
        }
        
        // 3. CLASSES CHARGE (Mandatory)
        if (is_fee_module_enabled('classes') && $rates['classes_rate'] > 0) {
            $original_amount = $rates['classes_rate'];
            $discount_info = $this->Discount_model->get_student_discount_for_date($student_id, $year, $term, $class_id, 'classes', $charge_date);
            
            if ($discount_info['has_discount']) {
                if ($discount_info['discount_type'] == 'percentage') {
                    $discount_amount = $original_amount * ($discount_info['discount_value'] / 100);
                } else {
                    $discount_amount = min($discount_info['discount_value'], $original_amount);
                }
                $final_amount = max(0, $original_amount - $discount_amount);
                
                $charges['classes_charged'] = $final_amount;
                $discount_records[] = [
                    'fee_type' => 'classes',
                    'profile_id' => $discount_info['profile_id'],
                    'original_amount' => $original_amount,
                    'discount_percentage' => $discount_info['discount_type'] == 'percentage' ? $discount_info['discount_value'] : 0,
                    'discount_amount' => $discount_amount,
                    'final_amount' => $final_amount
                ];
            } else {
                $charges['classes_charged'] = $original_amount;
            }
            
            $this->apply_charge($student_id, 'classes', $charges['classes_charged'], $wallet, $prefs['auto_deduct_enabled']);
        }
        
        // 4. WATER CHARGE (Weekly - charge on first attendance of week)
        if (is_fee_module_enabled('water') && $rates['water_enabled'] && $prefs['water_subscribed']) {
            $week_start = strtotime('monday this week', $attendance_date);
            $week_end = strtotime('sunday this week', $attendance_date);
            
            // Check if already charged THIS WEEK (individual basis)
            $already_charged = $this->db->where('student_id', $student_id)
                ->where('charge_date >=', $week_start)
                ->where('charge_date <=', $week_end)
                ->where('water_charged >', 0)
                ->get('daily_charge_log')->num_rows();
            
            if (!$already_charged) {
                    $original_amount = $rates['water_rate'];
                    $discount_info = $this->Discount_model->get_student_discount_for_date($student_id, $year, $term, $class_id, 'water', $charge_date);
                    
                    if ($discount_info['has_discount']) {
                        if ($discount_info['discount_type'] == 'percentage') {
                            $discount_amount = $original_amount * ($discount_info['discount_value'] / 100);
                        } else {
                            $discount_amount = min($discount_info['discount_value'], $original_amount);
                        }
                        $final_amount = max(0, $original_amount - $discount_amount);
                        
                        $charges['water_charged'] = $final_amount;
                        $discount_records[] = [
                            'fee_type' => 'water',
                            'profile_id' => $discount_info['profile_id'],
                            'original_amount' => $original_amount,
                            'discount_percentage' => $discount_info['discount_type'] == 'percentage' ? $discount_info['discount_value'] : 0,
                            'discount_amount' => $discount_amount,
                            'final_amount' => $final_amount
                        ];
                    } else {
                        $charges['water_charged'] = $original_amount;
                    }
                    
                    $this->apply_charge($student_id, 'water', $charges['water_charged'], $wallet, $prefs['auto_deduct_enabled']);
            }
        }
        
        // 5. TRANSPORT CHARGE (Optional - only if confirmed)
        // Skip if already charged for this date to prevent double deduction
        if (!$skip_transport && is_fee_module_enabled('transport') && isset($options['transport_status']) && $options['transport_status'] !== 'none' && $options['transport_status'] !== 'pending') {
            $enroll = $this->db->get_where('enroll', ['student_id' => $student_id, 'year' => $year, 'term' => $term])->row();
            if ($enroll && $enroll->transport_id) {
                $route = $this->db->get_where('transport', ['transport_id' => $enroll->transport_id])->row();
                $route_fare = $route ? $route->route_fare : 0;
                
                $transport_charge = 0;
                switch ($options['transport_status']) {
                    case 'in':
                    case 'out':
                        $transport_charge = $route_fare;
                        break;
                    case 'both':
                        $transport_charge = $route_fare * 2;
                        break;
                }
                
                if ($transport_charge > 0) {
                    $original_amount = $transport_charge;
                    $discount_info = $this->Discount_model->get_student_discount_for_date($student_id, $year, $term, $class_id, 'transport', $charge_date);
                    
                    if ($discount_info['has_discount']) {
                        if ($discount_info['discount_type'] == 'percentage') {
                            $discount_amount = $original_amount * ($discount_info['discount_value'] / 100);
                        } else {
                            $discount_amount = min($discount_info['discount_value'], $original_amount);
                        }
                        $final_amount = max(0, $original_amount - $discount_amount);
                        
                        $charges['transport_charged'] = $final_amount;
                        $discount_records[] = [
                            'fee_type' => 'transport',
                            'profile_id' => $discount_info['profile_id'],
                            'original_amount' => $original_amount,
                            'discount_percentage' => $discount_info['discount_type'] == 'percentage' ? $discount_info['discount_value'] : 0,
                            'discount_amount' => $discount_amount,
                            'final_amount' => $final_amount
                        ];
                    } else {
                        $charges['transport_charged'] = $original_amount;
                    }
                    
                    $this->apply_charge($student_id, 'transport', $charges['transport_charged'], $wallet, $prefs['auto_deduct_enabled']);
                }
            }
        }
        
        // Log the charge to prevent double-charging
        $total_charged = array_sum($charges);
        if ($total_charged > 0) {
            if ($existing) {
                // Update existing record (e.g., transport was charged earlier, now add attendance fees)
                $this->db->where('student_id', $student_id);
                $this->db->where('charge_date', $charge_date);
                $this->db->update('daily_charge_log', [
                    'feeding_charged' => $charges['feeding_charged'],
                    'breakfast_charged' => $charges['breakfast_charged'],
                    'classes_charged' => $charges['classes_charged'],
                    'water_charged' => $charges['water_charged'],
                    'transport_charged' => max($existing->transport_charged, $charges['transport_charged']),
                    'total_charged' => $existing->transport_charged + $total_charged,
                    'charged_at' => time()
                ]);
            } else {
                // Insert new record
                $this->db->insert('daily_charge_log', [
                    'student_id' => $student_id,
                    'charge_date' => $charge_date,
                    'feeding_charged' => $charges['feeding_charged'],
                    'breakfast_charged' => $charges['breakfast_charged'],
                    'classes_charged' => $charges['classes_charged'],
                    'water_charged' => $charges['water_charged'],
                    'transport_charged' => $charges['transport_charged'],
                    'total_charged' => $total_charged,
                    'charged_at' => time()
                ]);
            }
            
            // Record discount applications for tracking
            foreach ($discount_records as $discount) {
                record_discount_safely($this, [
                    'student_id' => $student_id,
                    'profile_id' => $discount['profile_id'],
                    'discount_category' => 'daily_fees',
                    'reference_type' => 'daily_fee_charge',
                    'reference_id' => $charge_date,
                    'bill_item_type' => $discount['fee_type'],
                    'original_amount' => $discount['original_amount'],
                    'discount_percentage' => $discount['discount_percentage'],
                    'discount_amount' => $discount['discount_amount'],
                    'final_amount' => $discount['final_amount'],
                    'year' => $year,
                    'term' => $term
                ]);
            }
            
            // STAGE 2: Recognize revenue when service delivered
            $this->recognize_revenue($student_id, $charges, $attendance_date);
        }
        
        return $charges;
    }

    /**
     * Apply charge to student wallet (deduct from balance or add to arrears)
     */
    private function apply_charge($student_id, $fee_type, $amount, &$wallet, $auto_deduct = true) {
        $balance_field = $fee_type . '_balance';
        $arrears_field = $fee_type . '_arrears';
        
        $balance_before = $wallet[$balance_field];
        $arrears_before = $wallet[$arrears_field];
        
        if ($auto_deduct && $wallet[$balance_field] >= $amount) {
            // Sufficient prepaid - deduct fully from balance
            $wallet[$balance_field] -= $amount;
            $this->db->where('student_id', $student_id);
            $this->db->update('daily_fee_wallet', [
                $balance_field => $wallet[$balance_field],
                'last_updated' => time()
            ]);
            $payment_status = 'paid';
        } else if ($auto_deduct && $wallet[$balance_field] > 0) {
            // Partial prepaid - use what's available, charge rest to arrears
            $prepaid_used = $wallet[$balance_field];
            $arrears_amount = $amount - $prepaid_used;
            
            $wallet[$balance_field] = 0;
            $wallet[$arrears_field] += $arrears_amount;
            
            $this->db->where('student_id', $student_id);
            $this->db->update('daily_fee_wallet', [
                $balance_field => $wallet[$balance_field],
                $arrears_field => $wallet[$arrears_field],
                'last_updated' => time()
            ]);
            $payment_status = 'partial';
        } else {
            // No prepaid or auto_deduct disabled - charge fully to arrears
            $wallet[$arrears_field] += $amount;
            $this->db->where('student_id', $student_id);
            $this->db->update('daily_fee_wallet', [
                $arrears_field => $wallet[$arrears_field],
                'last_updated' => time()
            ]);
            $payment_status = 'unpaid';
        }
        
        // Audit log
        $this->log_audit($student_id, 'charge', $fee_type, $amount, $balance_before, $wallet[$balance_field], 
                        $arrears_before, $wallet[$arrears_field], 'Daily charge applied');
        
        return $payment_status;
    }

    /**
     * Reverse a charge from wallet (when student opts out)
     */
    private function reverse_charge($student_id, $fee_type, $amount, &$wallet) {
        if ($amount <= 0) return;
        
        $balance_field = $fee_type . '_balance';
        $arrears_field = $fee_type . '_arrears';
        
        $balance_before = $wallet[$balance_field];
        $arrears_before = $wallet[$arrears_field];
        
        // Reverse the charge: reduce arrears or increase balance
        if ($wallet[$arrears_field] >= $amount) {
            // Had arrears, reduce them
            $wallet[$arrears_field] -= $amount;
        } else if ($wallet[$arrears_field] > 0) {
            // Partial arrears, clear them and add remainder to balance
            $remainder = $amount - $wallet[$arrears_field];
            $wallet[$arrears_field] = 0;
            $wallet[$balance_field] += $remainder;
        } else {
            // No arrears, add to balance (refund)
            $wallet[$balance_field] += $amount;
        }
        
        // Update database
        $this->db->where('student_id', $student_id);
        $this->db->update('daily_fee_wallet', [
            $balance_field => $wallet[$balance_field],
            $arrears_field => $wallet[$arrears_field],
            'last_updated' => time()
        ]);
        
        // Audit log
        $this->log_audit($student_id, 'charge_reversal', $fee_type, $amount, 
                        $balance_before, $wallet[$balance_field],
                        $arrears_before, $wallet[$arrears_field], 
                        'Charge reversed - student opted out');
    }

    /**
     * ENTERPRISE-GRADE: Update existing payment transaction
     * Handles: wallet reversal, new wallet application, audit trail, accounting sync
     * Used when editing same-day payments
     */
    public function update_payment($transaction_id, $new_data) {
        // Get existing transaction
        $old_transaction = $this->db->get_where('daily_fee_transactions', [
            'id' => $transaction_id
        ])->row_array();
        
        if (!$old_transaction) {
            return ['status' => 'error', 'message' => 'Transaction not found'];
        }
        
        $student_id = $old_transaction['student_id'];
        $modified_by = $new_data['modified_by'] ?? $this->session->userdata('admin_id');
        
        // CRITICAL: Update breakfast and water preferences when cashier toggles switches
        $prefs_update = [];
        $retroactive_breakfast_charge = false;
        $retroactive_breakfast_reversal = false;
        $retroactive_water_charge = false;
        $retroactive_water_reversal = false;
        $existing_prefs = $this->db->get_where('student_daily_fee_preferences', ['student_id' => $student_id])->row();
        
        log_message('debug', 'UPDATE_PAYMENT PREFS CHECK: student=' . $student_id . ', existing_prefs=' . ($existing_prefs ? 'YES' : 'NO') . ', breakfast_opted_in_data=' . (isset($new_data['breakfast_opted']) ? ($new_data['breakfast_opted'] ? 'YES' : 'NO') : 'NOT_SET'));
        
        if (isset($new_data['breakfast_opted'])) {
            $old_breakfast_pref = $existing_prefs ? $existing_prefs->breakfast_subscribed : 0;
            $new_breakfast_pref = $new_data['breakfast_opted'] == 1 ? 1 : 0;
            $prefs_update['breakfast_subscribed'] = $new_breakfast_pref;
            log_message('debug', 'UPDATE_PAYMENT BREAKFAST PREF: old=' . $old_breakfast_pref . ', new=' . $new_breakfast_pref);
            
            // OPT-IN: Charge retroactively
            if ($old_breakfast_pref == 0 && $new_breakfast_pref == 1) {
                $retroactive_breakfast_charge = true;
                log_message('debug', 'UPDATE_PAYMENT BREAKFAST RETROACTIVE CHARGE FLAG SET');
            }
            
            // OPT-OUT: Reverse charge retroactively
            if ($old_breakfast_pref == 1 && $new_breakfast_pref == 0) {
                $retroactive_breakfast_reversal = true;
                log_message('debug', 'UPDATE_PAYMENT BREAKFAST RETROACTIVE REVERSAL FLAG SET');
            }
        }
        
        if (isset($new_data['water_opted'])) {
            $old_water_pref = $existing_prefs ? $existing_prefs->water_subscribed : 1;
            $new_water_pref = $new_data['water_opted'] == 1 ? 1 : 0;
            $prefs_update['water_subscribed'] = $new_water_pref;
            log_message('debug', 'UPDATE_PAYMENT WATER PREF: old=' . $old_water_pref . ', new=' . $new_water_pref);
            
            // OPT-IN: Charge retroactively
            if ($old_water_pref == 0 && $new_water_pref == 1) {
                $retroactive_water_charge = true;
                log_message('debug', 'UPDATE_PAYMENT WATER RETROACTIVE CHARGE FLAG SET');
            }
            
            // OPT-OUT: Reverse charge retroactively
            if ($old_water_pref == 1 && $new_water_pref == 0) {
                $retroactive_water_reversal = true;
                log_message('debug', 'UPDATE_PAYMENT WATER RETROACTIVE REVERSAL FLAG SET');
            }
        }
        
        if (!empty($prefs_update)) {
            $prefs_update['updated_at'] = time();
            
            // Check if preference record exists, if not create it
            $existing_prefs = $this->db->get_where('student_daily_fee_preferences', ['student_id' => $student_id])->row();
            
            if ($existing_prefs) {
                // Update existing
                $this->db->where('student_id', $student_id);
                $this->db->update('student_daily_fee_preferences', $prefs_update);
            } else {
                // Create new with defaults
                $prefs_update['student_id'] = $student_id;
                $prefs_update['auto_deduct_enabled'] = 1;
                $this->db->insert('student_daily_fee_preferences', $prefs_update);
            }
        }
        
        // Calculate differences
        $fee_types = ['feeding', 'breakfast', 'classes', 'water', 'transport'];
        $changes = [];
        $has_changes = false;
        
        foreach ($fee_types as $type) {
            $amount_field = $type . '_amount';
            $old_amount = floatval($old_transaction[$amount_field] ?? 0);
            $new_amount = floatval($new_data[$amount_field] ?? 0);
            
            if ($old_amount != $new_amount) {
                $has_changes = true;
                $changes[$type] = [
                    'old' => $old_amount,
                    'new' => $new_amount,
                    'diff' => $new_amount - $old_amount
                ];
            }
        }
        
        // CRITICAL: Also consider preference changes as "changes"
        if ($retroactive_breakfast_charge || $retroactive_water_charge || $retroactive_breakfast_reversal || $retroactive_water_reversal) {
            $has_changes = true;
            log_message('debug', 'UPDATE_PAYMENT: has_changes set to TRUE due to retroactive flags');
        }
        
        // CRITICAL: Check if payment_method changed
        if (isset($new_data['payment_method']) && $new_data['payment_method'] != $old_transaction['payment_method']) {
            $has_changes = true;
            log_message('debug', 'UPDATE_PAYMENT: has_changes set to TRUE due to payment_method change from ' . $old_transaction['payment_method'] . ' to ' . $new_data['payment_method']);
        }
        
        if (!$has_changes) {
            return ['status' => 'success', 'message' => 'No changes detected'];
        }
        
        // Start transaction
        $this->db->trans_start();
        
        // CRITICAL FIX: Handle OPT-OUT reversals FIRST, regardless of amount changes
        if ($retroactive_breakfast_reversal || $retroactive_water_reversal) {
            $payment_date = $new_data['payment_date'] ?? $old_transaction['payment_date'];
            $target_date = strtotime(date('Y-m-d', $payment_date));
            
            $attendance_record = $this->db->get_where('attendance', [
                'student_id' => $student_id,
                'timestamp' => $target_date
            ])->row();
            
            if ($attendance_record) {
                $today_log = $this->db->get_where('daily_charge_log', [
                    'student_id' => $student_id,
                    'charge_date' => $target_date
                ])->row();
                
                if ($today_log) {
                    // Get FRESH wallet
                    $wallet = $this->get_student_wallet($student_id);
                    
                    // Handle breakfast opt-out
                    if ($retroactive_breakfast_reversal && $today_log->breakfast_charged > 0) {
                        $amt = $today_log->breakfast_charged;
                        
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
                                 ->where('charge_date', $target_date)
                                 ->update('daily_charge_log', [
                                     'breakfast_charged' => 0,
                                     'total_charged' => $today_log->total_charged - $amt
                                 ]);
                        
                        // Update attendance table
                        $this->db->where('student_id', $student_id)
                                 ->where('timestamp', $target_date)
                                 ->update('attendance', ['breakfast_charged' => 0]);
                        
                        // Audit log
                        $this->log_audit($student_id, 'charge_reversal', 'breakfast', $amt,
                            $balance_before, $wallet['breakfast_balance'],
                            $arrears_before, $wallet['breakfast_arrears'],
                            'Breakfast charge reversed - student opted out');
                        
                        log_message('debug', 'BREAKFAST OPT-OUT REVERSAL: amount=' . $amt . 
                            ', new_balance=' . $wallet['breakfast_balance'] . 
                            ', new_arrears=' . $wallet['breakfast_arrears']);
                    }
                    
                    // Handle water opt-out
                    if ($retroactive_water_reversal && $today_log->water_charged > 0) {
                        $amt = $today_log->water_charged;
                        
                        $balance_before = $wallet['water_balance'];
                        $arrears_before = $wallet['water_arrears'];
                        
                        if ($wallet['water_arrears'] >= $amt) {
                            $wallet['water_arrears'] -= $amt;
                        } else if ($wallet['water_arrears'] > 0) {
                            $remainder = $amt - $wallet['water_arrears'];
                            $wallet['water_arrears'] = 0;
                            $wallet['water_balance'] += $remainder;
                        } else {
                            $wallet['water_balance'] += $amt;
                        }
                        
                        $this->db->where('student_id', $student_id)->update('daily_fee_wallet', [
                            'water_balance' => $wallet['water_balance'],
                            'water_arrears' => $wallet['water_arrears'],
                            'last_updated' => time()
                        ]);
                        
                        $this->db->where('student_id', $student_id)
                                 ->where('charge_date', $target_date)
                                 ->update('daily_charge_log', [
                                     'water_charged' => 0,
                                     'total_charged' => $today_log->total_charged - $amt
                                 ]);
                        
                        $this->db->where('student_id', $student_id)
                                 ->where('timestamp', $target_date)
                                 ->update('attendance', ['water_charged' => 0]);
                        
                        $this->log_audit($student_id, 'charge_reversal', 'water', $amt,
                            $balance_before, $wallet['water_balance'],
                            $arrears_before, $wallet['water_arrears'],
                            'Water charge reversed - student opted out');
                    }
                }
            }
        }
        
        // STEP 1: RETROACTIVE CHARGING (if only preference changes, no wallet operations needed)
        if (($retroactive_breakfast_charge || $retroactive_water_charge) && empty($changes)) {
            log_message('debug', 'UPDATE_PAYMENT: Processing retroactive charges (preference-only change)');
            $payment_date = $new_data['payment_date'] ?? $old_transaction['payment_date'];
            $target_date = strtotime(date('Y-m-d', $payment_date));
            log_message('debug', 'UPDATE_PAYMENT: target_date=' . date('Y-m-d', $target_date) . ', timestamp=' . $target_date);
            
            // ✅ Check attendance table (source of truth)
            $attendance_record = $this->db->get_where('attendance', [
                'student_id' => $student_id,
                'timestamp' => $target_date
            ])->row();
            
            log_message('debug', 'UPDATE_PAYMENT: attendance_exists=' . ($attendance_record ? 'YES' : 'NO'));
            
            if ($attendance_record) {
                // Get charge log for updating
                $today_log = $this->db->get_where('daily_charge_log', ['student_id' => $student_id, 'charge_date' => $target_date])->row();
                log_message('debug', 'UPDATE_PAYMENT: charge_log found=' . ($today_log ? 'YES' : 'NO'));
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                $enroll = $this->db->get_where('enroll', ['student_id' => $student_id, 'year' => $running_year, 'term' => $running_term])->row();
                log_message('debug', 'UPDATE_PAYMENT: enroll found=' . ($enroll ? 'YES' : 'NO'));
                
                if ($enroll) {
                    $rates = $this->get_class_rates($enroll->class_id, $running_year, $running_term);
                    log_message('debug', 'UPDATE_PAYMENT: rates loaded, breakfast_rate=' . ($rates['breakfast_rate'] ?? 0) . ', breakfast_enabled=' . ($rates['breakfast_enabled'] ?? 0));
                    $this->load->model('Discount_model');
                    log_message('debug', 'UPDATE_PAYMENT: Discount_model loaded');
                    $wallet = $this->get_student_wallet($student_id);
                    log_message('debug', 'UPDATE_PAYMENT: wallet loaded');
                    $retro_charges = [];
                    log_message('debug', 'UPDATE_PAYMENT: Starting breakfast check, flag=' . ($retroactive_breakfast_charge ? 'YES' : 'NO') . ', module_enabled=' . (is_fee_module_enabled('breakfast') ? 'YES' : 'NO') . ', rate_enabled=' . ($rates['breakfast_enabled'] ?? 0) . ', already_charged=' . ($today_log->breakfast_charged ?? 'NULL'));
                    
                    if ($retroactive_breakfast_charge && is_fee_module_enabled('breakfast') && $rates['breakfast_enabled'] && $today_log && ($today_log->breakfast_charged == 0 || $today_log->breakfast_charged === null)) {
                        log_message('debug', 'UPDATE_PAYMENT: Charging breakfast retroactively');
                        $amt = $rates['breakfast_rate'];
                        $disc = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $enroll->class_id, 'breakfast', $target_date);
                        if ($disc['has_discount']) {
                            $amt = max(0, $amt - ($disc['discount_type'] == 'percentage' ? $amt * $disc['discount_value'] / 100 : min($disc['discount_value'], $amt)));
                        }
                        if ($amt > 0) {
                            $retro_charges['breakfast'] = $amt;
                            $this->apply_charge($student_id, 'breakfast', $amt, $wallet, 1);
                        }
                    }
                    
                    if ($retroactive_water_charge && is_fee_module_enabled('water') && $rates['water_enabled'] && $today_log && ($today_log->water_charged == 0 || $today_log->water_charged === null)) {

                        $week_start = strtotime('monday this week', $payment_date);
                        $week_end = strtotime('sunday this week', $payment_date);
                        $charged = $this->db->where('student_id', $student_id)->where('charge_date >=', $week_start)->where('charge_date <=', $week_end)->where('water_charged >', 0)->get('daily_charge_log')->num_rows();
                        
                        if (!$charged) {
                            $amt = $rates['water_rate'];
                            $disc = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $enroll->class_id, 'water', $target_date);
                            if ($disc['has_discount']) {
                                $amt = max(0, $amt - ($disc['discount_type'] == 'percentage' ? $amt * $disc['discount_value'] / 100 : min($disc['discount_value'], $amt)));
                            }
                            if ($amt > 0) {
                                $retro_charges['water'] = $amt;
                                $this->apply_charge($student_id, 'water', $amt, $wallet, 1);
                            }
                        }
                    }
                    
                    if (!empty($retro_charges)) {
                        log_message('debug', 'UPDATE_PAYMENT RETROACTIVE: Applying charges - breakfast=' . ($retro_charges['breakfast'] ?? 0) . ', water=' . ($retro_charges['water'] ?? 0));
                        $upd = ['total_charged' => $today_log->total_charged + array_sum($retro_charges), 'charged_at' => time()];
                        if (isset($retro_charges['breakfast'])) $upd['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $upd['water_charged'] = $retro_charges['water'];
                        log_message('debug', 'UPDATE_PAYMENT: Updating daily_charge_log');
                        $this->db->where('student_id', $student_id)->where('charge_date', $target_date)->update('daily_charge_log', $upd);
                        
                        // CRITICAL: Also update attendance table
                        $att_upd = [];
                        if (isset($retro_charges['breakfast'])) $att_upd['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $att_upd['water_charged'] = $retro_charges['water'];
                        if (!empty($att_upd)) {
                            log_message('debug', 'UPDATE_PAYMENT: Updating attendance table with: ' . json_encode($att_upd));
                            $this->db->where('student_id', $student_id)->where('timestamp', $target_date)->update('attendance', $att_upd);
                            log_message('debug', 'UPDATE_PAYMENT: Attendance table updated, affected_rows=' . $this->db->affected_rows());
                        }
                        
                        $rev = [];
                        if (isset($retro_charges['breakfast'])) $rev['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $rev['water_charged'] = $retro_charges['water'];
                        $this->recognize_revenue($student_id, $rev, $target_date);
                        log_message('debug', 'UPDATE_PAYMENT: Retroactive charging complete');
                    }
                    
                    // ✅ Handle OPT-OUT reversals (FIRST occurrence only)
                    if ($retroactive_breakfast_reversal && $today_log && $today_log->breakfast_charged > 0) {
                        log_message('debug', 'UPDATE_PAYMENT: Reversing breakfast charge (opt-out)');
                        $amt = $today_log->breakfast_charged;
                        $this->reverse_charge($student_id, 'breakfast', $amt, $wallet);
                        
                        $this->db->where('student_id', $student_id)->where('charge_date', $target_date)
                                 ->update('daily_charge_log', [
                                     'breakfast_charged' => 0,
                                     'total_charged' => $today_log->total_charged - $amt,
                                     'charged_at' => time()
                                 ]);
                        
                        $this->db->where('student_id', $student_id)->where('timestamp', $target_date)
                                 ->update('attendance', ['breakfast_charged' => 0]);
                        
                        log_message('debug', 'UPDATE_PAYMENT: Breakfast charge reversed, amount=' . $amt);
                    }
                    
                    if ($retroactive_water_reversal && $today_log && $today_log->water_charged > 0) {
                        log_message('debug', 'UPDATE_PAYMENT: Reversing water charge (opt-out)');
                        $amt = $today_log->water_charged;
                        $this->reverse_charge($student_id, 'water', $amt, $wallet);
                        
                        $this->db->where('student_id', $student_id)->where('charge_date', $target_date)
                                 ->update('daily_charge_log', [
                                     'water_charged' => 0,
                                     'total_charged' => $today_log->total_charged - $amt,
                                     'charged_at' => time()
                                 ]);
                        
                        $this->db->where('student_id', $student_id)->where('timestamp', $target_date)
                                 ->update('attendance', ['water_charged' => 0]);
                        
                        log_message('debug', 'UPDATE_PAYMENT: Water charge reversed, amount=' . $amt);
                    }
                }
            } else {
                log_message('debug', 'UPDATE_PAYMENT: No attendance record found, skipping charge operations');
            }
            
            // CRITICAL: Update payment_method even if only preference changes
            if (isset($new_data['payment_method']) && $new_data['payment_method'] != $old_transaction['payment_method']) {
                $this->db->where('id', $transaction_id);
                $this->db->update('daily_fee_transactions', [
                    'payment_method' => $new_data['payment_method'],
                    'modified_at' => time(),
                    'modified_by' => $modified_by
                ]);
                log_message('debug', 'UPDATE_PAYMENT: Updated payment_method from ' . $old_transaction['payment_method'] . ' to ' . $new_data['payment_method']);
            }
            
            // Complete transaction and return early (no wallet operations needed)
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['status' => 'error', 'message' => 'Failed to update payment'];
            }
            
            return [
                'status' => 'success',
                'message' => 'Preferences updated and retroactive charges applied',
                'changes' => [],
                'old_total' => floatval($old_transaction['total_amount']),
                'new_total' => floatval($old_transaction['total_amount'])
            ];
        }
        
        // STEP 2: Reverse old wallet entries (only if there are amount changes)
        $wallet = $this->get_student_wallet($student_id);
        $payment_type = $old_transaction['payment_type'] ?? 'mixed';
        
        foreach ($fee_types as $type) {
            $old_amount = floatval($old_transaction[$type . '_amount'] ?? 0);
            if ($old_amount > 0) {
                $this->reverse_payment_wallet($student_id, $type, $old_amount, $wallet, $payment_type);
            }
        }
        
        // STEP 3: Apply new wallet entries
        $wallet = $this->get_student_wallet($student_id); // Refresh wallet
        foreach ($fee_types as $type) {
            $amount_field = $type . '_amount';
            
            // CRITICAL FIX: Preserve old amount if not provided in update
            if (isset($new_data[$amount_field])) {
                $new_amount = floatval($new_data[$amount_field]);
            } else {
                // Use old amount if not provided (prevents data loss)
                $new_amount = floatval($old_transaction[$amount_field] ?? 0);
                log_message('info', 'UPDATE_PAYMENT: ' . $type . ' amount not provided, preserving old value: ' . $new_amount);
            }
            
            if ($new_amount > 0) {
                $this->apply_payment($student_id, $type, $new_amount, $wallet, $modified_by, $payment_type);
            }
        }
        
        // STEP 3: Update transaction record
        $new_total = 0;
        $update_data = [];
        foreach ($fee_types as $type) {
            $amount_field = $type . '_amount';
            $update_data[$amount_field] = floatval($new_data[$amount_field] ?? 0);
            $new_total += $update_data[$amount_field];
        }
        
        $update_data['total_amount'] = $new_total;
        $update_data['modified_at'] = time();
        $update_data['modified_by'] = $modified_by;
        $update_data['modification_count'] = ($old_transaction['modification_count'] ?? 0) + 1;
        
        // Update payment_method if provided
        if (isset($new_data['payment_method'])) {
            $update_data['payment_method'] = $new_data['payment_method'];
            log_message('debug', 'UPDATE_PAYMENT: Updating payment_method to ' . $new_data['payment_method']);
        } else {
            log_message('debug', 'UPDATE_PAYMENT: payment_method NOT in new_data. Keys: ' . implode(', ', array_keys($new_data)));
        }
        
        $this->db->where('id', $transaction_id);
        $this->db->update('daily_fee_transactions', $update_data);
        
        // STEP 4: Create audit trail
        $change_summary = [];
        foreach ($changes as $type => $change) {
            if ($change['diff'] != 0) {
                $change_summary[] = ucfirst($type) . ': ' . number_format($change['old'], 2) . ' → ' . number_format($change['new'], 2);
            }
        }
        
        $this->db->insert('daily_fee_audit_log', [
            'student_id' => $student_id,
            'action_type' => 'update_payment',
            'fee_type' => 'multiple',
            'amount' => $new_total,
            'balance_before' => 0,
            'balance_after' => 0,
            'arrears_before' => 0,
            'arrears_after' => 0,
            'performed_by' => $modified_by,
            'performed_by_role' => $this->session->userdata('login_type'),
            'notes' => 'Payment updated: ' . implode('; ', $change_summary),
            'created_at' => time()
        ]);
        
        // STEP 5: RETROACTIVE CHARGING - If cashier opts breakfast/water and attendance was already marked
        if ($retroactive_breakfast_charge || $retroactive_water_charge) {
            $payment_date = $new_data['payment_date'] ?? $old_transaction['payment_date'];
            $target_date = strtotime(date('Y-m-d', $payment_date));
            $today_log = $this->db->get_where('daily_charge_log', ['student_id' => $student_id, 'charge_date' => $target_date])->row();
            
            if ($today_log) {
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                $enroll = $this->db->get_where('enroll', ['student_id' => $student_id, 'year' => $running_year, 'term' => $running_term])->row();
                
                if ($enroll) {
                    $rates = $this->get_class_rates($enroll->class_id, $running_year, $running_term);
                    $this->load->model('Discount_model');
                    $wallet = $this->get_student_wallet($student_id); // Refresh wallet after payments
                    $retro_charges = [];
                    
                    if ($retroactive_breakfast_charge && is_fee_module_enabled('breakfast') && $rates['breakfast_enabled'] && $today_log && ($today_log->breakfast_charged == 0 || $today_log->breakfast_charged === null)) {
                        $amt = $rates['breakfast_rate'];
                        $disc = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $enroll->class_id, 'breakfast', $target_date);
                        if ($disc['has_discount']) {
                            $amt = max(0, $amt - ($disc['discount_type'] == 'percentage' ? $amt * $disc['discount_value'] / 100 : min($disc['discount_value'], $amt)));
                        }
                        if ($amt > 0) {
                            $retro_charges['breakfast'] = $amt;
                            $this->apply_charge($student_id, 'breakfast', $amt, $wallet, 1);
                        }
                    }
                    
                    if ($retroactive_water_charge && is_fee_module_enabled('water') && $rates['water_enabled'] && $today_log && ($today_log->water_charged == 0 || $today_log->water_charged === null)) {
                        $week_start = strtotime('monday this week', $payment_date);
                        $week_end = strtotime('sunday this week', $payment_date);
                        $charged = $this->db->where('student_id', $student_id)->where('charge_date >=', $week_start)->where('charge_date <=', $week_end)->where('water_charged >', 0)->get('daily_charge_log')->num_rows();
                        
                        if (!$charged) {
                            $amt = $rates['water_rate'];
                            $disc = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $enroll->class_id, 'water', $target_date);
                            if ($disc['has_discount']) {
                                $amt = max(0, $amt - ($disc['discount_type'] == 'percentage' ? $amt * $disc['discount_value'] / 100 : min($disc['discount_value'], $amt)));
                            }
                            if ($amt > 0) {
                                $retro_charges['water'] = $amt;
                                $this->apply_charge($student_id, 'water', $amt, $wallet, 1);
                            }
                        }
                    }
                    
                    if (!empty($retro_charges)) {
                        log_message('debug', 'UPDATE_PAYMENT RETROACTIVE: Applying charges - breakfast=' . ($retro_charges['breakfast'] ?? 0) . ', water=' . ($retro_charges['water'] ?? 0));
                        $upd = ['total_charged' => $today_log->total_charged + array_sum($retro_charges), 'charged_at' => time()];
                        if (isset($retro_charges['breakfast'])) $upd['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $upd['water_charged'] = $retro_charges['water'];
                        log_message('debug', 'UPDATE_PAYMENT: Updating daily_charge_log');
                        $this->db->where('student_id', $student_id)->where('charge_date', $target_date)->update('daily_charge_log', $upd);
                        
                        // CRITICAL: Also update attendance table
                        $att_upd = [];
                        if (isset($retro_charges['breakfast'])) $att_upd['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $att_upd['water_charged'] = $retro_charges['water'];
                        if (!empty($att_upd)) {
                            log_message('debug', 'UPDATE_PAYMENT: Updating attendance table with: ' . json_encode($att_upd));
                            $this->db->where('student_id', $student_id)->where('timestamp', $target_date)->update('attendance', $att_upd);
                            log_message('debug', 'UPDATE_PAYMENT: Attendance table updated, affected_rows=' . $this->db->affected_rows());
                        }
                        
                        $rev = [];
                        if (isset($retro_charges['breakfast'])) $rev['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $rev['water_charged'] = $retro_charges['water'];
                        $this->recognize_revenue($student_id, $rev, $target_date);
                        log_message('debug', 'UPDATE_PAYMENT: Retroactive charging complete');
                    }
                }
            }
        }
        
        // STEP 6: Reverse old accounting entry and create new one
        if ($this->db->table_exists('journal_entries')) {
            $this->reverse_payment_accounting($transaction_id);
            $this->sync_payment_to_accounts($transaction_id);
        }
        
        // Complete transaction
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => 'Failed to update payment'];
        }
        
        return [
            'status' => 'success',
            'message' => 'Payment updated successfully',
            'changes' => $changes,
            'old_total' => floatval($old_transaction['total_amount']),
            'new_total' => $new_total
        ];
    }
    
    /**
     * Reverse payment from wallet (used when updating/deleting payments)
     */
    private function reverse_payment_wallet($student_id, $fee_type, $amount, &$wallet, $payment_type = 'mixed') {
        $balance_field = $fee_type . '_balance';
        $arrears_field = $fee_type . '_arrears';
        
        $balance_before = $wallet[$balance_field];
        $arrears_before = $wallet[$arrears_field];
        
        // Reverse the payment logic
        if ($payment_type == 'advance') {
            // Was added to balance, so subtract it
            $wallet[$balance_field] -= $amount;
        } elseif ($payment_type == 'arrears') {
            // Was used to clear arrears, so restore arrears
            $wallet[$arrears_field] += $amount;
            // If there was overflow to balance, remove it
            if ($wallet[$balance_field] > 0) {
                $overflow = min($wallet[$balance_field], $amount);
                $wallet[$balance_field] -= $overflow;
            }
        } else {
            // Mixed: reverse the smart allocation
            // If balance exists, it came from payment, so remove it
            if ($wallet[$balance_field] >= $amount) {
                $wallet[$balance_field] -= $amount;
            } else {
                // Part went to balance, part cleared arrears
                $to_balance = $wallet[$balance_field];
                $to_arrears = $amount - $to_balance;
                $wallet[$balance_field] = 0;
                $wallet[$arrears_field] += $to_arrears;
            }
        }
        
        // Update wallet
        $this->db->where('student_id', $student_id);
        $this->db->update('daily_fee_wallet', [
            $balance_field => max(0, $wallet[$balance_field]),
            $arrears_field => max(0, $wallet[$arrears_field]),
            'last_updated' => time()
        ]);
        
        // Audit log
        $this->log_audit($student_id, 'payment_reversal', $fee_type, $amount, 
                        $balance_before, $wallet[$balance_field],
                        $arrears_before, $wallet[$arrears_field], 
                        'Payment reversed for update');
    }
    
    /**
     * Reverse accounting entries for a payment (used when updating/deleting)
     */
    private function reverse_payment_accounting($transaction_id) {
        if (!$this->db->table_exists('journal_entries')) return;
        
        // Find journal entries for this transaction
        $entries = $this->db->get_where('journal_entries', [
            'source_type' => 'daily_fee_payment',
            'source_id' => $transaction_id
        ])->result_array();
        
        foreach ($entries as $entry) {
            // Get entry lines
            $lines = $this->db->get_where('journal_entry_lines', [
                'entry_id' => $entry['entry_id']
            ])->result_array();
            
            // Reverse each line (swap debit/credit)
            foreach ($lines as $line) {
                $this->update_account_balance(
                    $line['account_id'],
                    $line['credit_amount'], // Reverse: credit becomes debit
                    $line['debit_amount']   // Reverse: debit becomes credit
                );
            }
            
            // Mark entry as reversed
            $this->db->where('entry_id', $entry['entry_id']);
            $this->db->update('journal_entries', [
                'status' => 'reversed',
                'reversed_at' => time()
            ]);
        }
    }

    /**
     * Process payment (from cashier, teacher, or conductor)
     */
    public function process_payment($data) {
        // CRITICAL DEBUG: Write to file to confirm this is called
        // file_put_contents('c:/wamp64/www/schoolmanager/debug_payment.txt', date('Y-m-d H:i:s') . " - process_payment called\n" . print_r($data, true) . "\n\n", FILE_APPEND);
        // log_message('debug', '========== PROCESS_PAYMENT CALLED ==========');
        // log_message('debug', 'DATA: ' . json_encode($data));
        
        $student_id = $data['student_id'];
        $wallet = $this->get_student_wallet($student_id);
        $payment_type = $data['payment_type'] ?? 'current';
        
        // Generate transaction code
        $transaction_code = 'DFT' . date('Ymd') . substr(md5(time() . $student_id), 0, 6);
        
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Get student's residence type from enrollment for this year/term
        $enrollment = $this->db->get_where('enroll', [
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term
        ])->row();
        $residence_type = $enrollment->residence_type ?? null;
        
        $transaction = [
            'transaction_code' => $transaction_code,
            'student_id' => $student_id,
            'payment_date' => $data['payment_date'] ?? time(),
            'feeding_amount' => $data['feeding_amount'] ?? 0,
            'breakfast_amount' => $data['breakfast_amount'] ?? 0,
            'classes_amount' => $data['classes_amount'] ?? 0,
            'water_amount' => $data['water_amount'] ?? 0,
            'transport_amount' => $data['transport_amount'] ?? 0,
            'total_amount' => 0,
            'payment_type' => $payment_type,
            'payment_method' => $data['payment_method'] ?? 1,
            'collected_by' => $data['collected_by'],
            'collection_point' => $data['collection_point'] ?? 'office',
            'receipt_number' => $data['receipt_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'year' => $running_year,
            'term' => $running_term,
            'residence_type' => $residence_type,
            'created_at' => time()
        ];
        
        // Calculate total
        $transaction['total_amount'] = $transaction['feeding_amount'] + $transaction['breakfast_amount'] + 
                                      $transaction['classes_amount'] + $transaction['water_amount'] + 
                                      $transaction['transport_amount'];
        
        // CRITICAL: Store breakfast and water preferences when cashier toggles switches
        $prefs_update = [];
        $retroactive_breakfast_charge = false;
        $retroactive_water_charge = false;
        $existing_prefs = $this->db->get_where('student_daily_fee_preferences', ['student_id' => $student_id])->row();
        
        log_message('debug', 'PREFS CHECK: student=' . $student_id . ', existing_prefs=' . ($existing_prefs ? 'YES' : 'NO') . ', breakfast_opted_in_data=' . (isset($data['breakfast_opted']) ? ($data['breakfast_opted'] ? 'YES' : 'NO') : 'NOT_SET'));
        
        if (isset($data['breakfast_opted'])) {
            $old_breakfast_pref = $existing_prefs ? $existing_prefs->breakfast_subscribed : 0;
            $new_breakfast_pref = $data['breakfast_opted'] == 1 ? 1 : 0;
            $prefs_update['breakfast_subscribed'] = $new_breakfast_pref;
            log_message('debug', 'BREAKFAST PREF: old=' . $old_breakfast_pref . ', new=' . $new_breakfast_pref);
            if ($old_breakfast_pref == 0 && $new_breakfast_pref == 1) {
                $retroactive_breakfast_charge = true;
                log_message('debug', 'BREAKFAST RETROACTIVE FLAG SET TO TRUE');
            }
        }
        
        if (isset($data['water_opted'])) {
            $old_water_pref = $existing_prefs ? $existing_prefs->water_subscribed : 1;
            $new_water_pref = $data['water_opted'] == 1 ? 1 : 0;
            $prefs_update['water_subscribed'] = $new_water_pref;
            log_message('debug', 'WATER PREF: old=' . $old_water_pref . ', new=' . $new_water_pref);
            if ($old_water_pref == 0 && $new_water_pref == 1) {
                $retroactive_water_charge = true;
                log_message('debug', 'WATER RETROACTIVE FLAG SET TO TRUE');
            }
        }
        
        if (!empty($prefs_update)) {
            $prefs_update['updated_at'] = time();
            if ($existing_prefs) {
                $this->db->where('student_id', $student_id)->update('student_daily_fee_preferences', $prefs_update);
            } else {
                $prefs_update['student_id'] = $student_id;
                $prefs_update['auto_deduct_enabled'] = 1;
                $this->db->insert('student_daily_fee_preferences', $prefs_update);
            }
        }
        
        // Apply payments to wallet with payment type
        $fee_types = ['feeding', 'breakfast', 'classes', 'water', 'transport'];
        foreach ($fee_types as $type) {
            $amount_field = $type . '_amount';
            if ($transaction[$amount_field] > 0) {
                $this->apply_payment($student_id, $type, $transaction[$amount_field], $wallet, $data['collected_by'], $payment_type);
            }
        }
        
        // RETROACTIVE CHARGING: If cashier opts breakfast/water and attendance was already marked
        // This must happen AFTER payments are applied to wallet so there's balance to deduct from
        if ($retroactive_breakfast_charge || $retroactive_water_charge) {
            $payment_date = isset($data['payment_date']) ? $data['payment_date'] : strtotime('today');
            $target_date = strtotime(date('Y-m-d', $payment_date));
            
            // ✅ FIX: Check attendance table (source of truth) instead of daily_charge_log
            $attendance_record = $this->db->get_where('attendance', [
                'student_id' => $student_id,
                'timestamp' => $target_date
            ])->row();
            
            log_message('debug', 'RETROACTIVE CHECK: student=' . $student_id . ', target_date=' . date('Y-m-d', $target_date) . ', attendance_exists=' . ($attendance_record ? 'YES' : 'NO') . ', breakfast_flag=' . ($retroactive_breakfast_charge ? 'YES' : 'NO') . ', water_flag=' . ($retroactive_water_charge ? 'YES' : 'NO'));
            
            // Only proceed if attendance was ACTUALLY marked
            if ($attendance_record) {
                log_message('debug', 'RETROACTIVE: Attendance confirmed, proceeding with retroactive charges');
                
                // Get charge log for updating
                $today_log = $this->db->get_where('daily_charge_log', ['student_id' => $student_id, 'charge_date' => $target_date])->row();
            
                // DEBUG: Log what we found
                log_message('debug', 'RETROACTIVE LOG FOUND: breakfast_charged=' . ($today_log ? $today_log->breakfast_charged : 'NULL') . ', water_charged=' . ($today_log ? $today_log->water_charged : 'NULL'));
                
                $enroll = $this->db->get_where('enroll', ['student_id' => $student_id, 'year' => $running_year, 'term' => $running_term])->row();
                if ($enroll) {
                    $rates = $this->get_class_rates($enroll->class_id, $running_year, $running_term);
                    log_message('debug', 'RETROACTIVE RATES: breakfast_rate=' . ($rates['breakfast_rate'] ?? 0) . ', breakfast_enabled=' . ($rates['breakfast_enabled'] ?? 0) . ', water_rate=' . ($rates['water_rate'] ?? 0) . ', water_enabled=' . ($rates['water_enabled'] ?? 0));
                    
                    $this->load->model('Discount_model');
                    $wallet = $this->get_student_wallet($student_id); // Refresh wallet after payments
                    $retro_charges = [];
                    
                    if ($retroactive_breakfast_charge && is_fee_module_enabled('breakfast') && $rates['breakfast_enabled'] && $today_log && ($today_log->breakfast_charged == 0 || $today_log->breakfast_charged === null)) {
                        log_message('debug', 'RETROACTIVE BREAKFAST: Charging breakfast');
                        $amt = $rates['breakfast_rate'];
                        $disc = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $enroll->class_id, 'breakfast', $target_date);
                        if ($disc['has_discount']) {
                            $amt = max(0, $amt - ($disc['discount_type'] == 'percentage' ? $amt * $disc['discount_value'] / 100 : min($disc['discount_value'], $amt)));
                        }
                        log_message('debug', 'RETROACTIVE BREAKFAST: Final amount=' . $amt);
                        if ($amt > 0) {
                            $retro_charges['breakfast'] = $amt;
                            $this->apply_charge($student_id, 'breakfast', $amt, $wallet, 1);
                        }
                    } else {
                        log_message('debug', 'RETROACTIVE BREAKFAST SKIPPED: retroactive_flag=' . ($retroactive_breakfast_charge ? 'YES' : 'NO') . ', module_enabled=' . (is_fee_module_enabled('breakfast') ? 'YES' : 'NO') . ', rate_enabled=' . ($rates['breakfast_enabled'] ?? 0) . ', already_charged=' . ($today_log->breakfast_charged ?? 'NULL'));
                    }
                    
                    if ($retroactive_water_charge && is_fee_module_enabled('water') && $rates['water_enabled'] && $today_log && ($today_log->water_charged == 0 || $today_log->water_charged === null)) {
                        $week_start = strtotime('monday this week', $payment_date);
                        $week_end = strtotime('sunday this week', $payment_date);
                        $charged = $this->db->where('student_id', $student_id)->where('charge_date >=', $week_start)->where('charge_date <=', $week_end)->where('water_charged >', 0)->get('daily_charge_log')->num_rows();
                        
                        if (!$charged) {
                            $amt = $rates['water_rate'];
                            $disc = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $enroll->class_id, 'water', $target_date);
                            if ($disc['has_discount']) {
                                $amt = max(0, $amt - ($disc['discount_type'] == 'percentage' ? $amt * $disc['discount_value'] / 100 : min($disc['discount_value'], $amt)));
                            }
                            if ($amt > 0) {
                                $retro_charges['water'] = $amt;
                                $this->apply_charge($student_id, 'water', $amt, $wallet, 1);
                            }
                        }
                    }
                    
                    if (!empty($retro_charges)) {
                        log_message('debug', 'RETROACTIVE UPDATE: Updating charge log with breakfast=' . ($retro_charges['breakfast'] ?? 0) . ', water=' . ($retro_charges['water'] ?? 0));
                        $upd = ['total_charged' => $today_log->total_charged + array_sum($retro_charges), 'charged_at' => time()];
                        if (isset($retro_charges['breakfast'])) $upd['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $upd['water_charged'] = $retro_charges['water'];
                        $this->db->where('student_id', $student_id)->where('charge_date', $target_date)->update('daily_charge_log', $upd);
                        
                        // CRITICAL: Also update attendance table
                        $att_upd = [];
                        if (isset($retro_charges['breakfast'])) $att_upd['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $att_upd['water_charged'] = $retro_charges['water'];
                        if (!empty($att_upd)) {
                            $this->db->where('student_id', $student_id)->where('timestamp', $target_date)->update('attendance', $att_upd);
                        }
                        
                        $rev = [];
                        if (isset($retro_charges['breakfast'])) $rev['breakfast_charged'] = $retro_charges['breakfast'];
                        if (isset($retro_charges['water'])) $rev['water_charged'] = $retro_charges['water'];
                        $this->recognize_revenue($student_id, $rev, $target_date);
                    } else {
                        log_message('debug', 'RETROACTIVE: No charges to apply');
                    }
                } else {
                    log_message('debug', 'RETROACTIVE: No enrollment found');
                }
            } else {
                log_message('debug', 'RETROACTIVE SKIPPED: No attendance marked for this date');
            }
        }
        
        // Insert transaction record
        $this->db->insert('daily_fee_transactions', $transaction);
        $transaction_id = $this->db->insert_id();
        
        // ACCOUNTING INTEGRATION: Sync to accounting module
        $this->sync_payment_to_accounts($transaction_id);
        
        return [
            'status' => 'success',
            'transaction_code' => $transaction_code,
            'receipt_number' => $transaction['receipt_number'] ?? $transaction_code,
            'transaction_id' => $transaction_id
        ];
    }

    /**
     * Apply payment to wallet (smart allocation based on payment type)
     */
    private function apply_payment($student_id, $fee_type, $amount, &$wallet, $collected_by, $payment_type = 'mixed') {
        $balance_field = $fee_type . '_balance';
        $arrears_field = $fee_type . '_arrears';
        
        $balance_before = $wallet[$balance_field];
        $arrears_before = $wallet[$arrears_field];
        
        if ($payment_type == 'advance') {
            // Advance payment: Add directly to prepaid balance
            $wallet[$balance_field] += $amount;
        } elseif ($payment_type == 'arrears') {
            // Arrears payment: Clear arrears only
            if ($amount >= $wallet[$arrears_field]) {
                $remaining = $amount - $wallet[$arrears_field];
                $wallet[$arrears_field] = 0;
                $wallet[$balance_field] += $remaining;
            } else {
                $wallet[$arrears_field] -= $amount;
            }
        } else {
            // Mixed/Current: Clear arrears first, then add to balance
            if ($wallet[$arrears_field] > 0) {
                if ($amount >= $wallet[$arrears_field]) {
                    $remaining = $amount - $wallet[$arrears_field];
                    $wallet[$arrears_field] = 0;
                    $wallet[$balance_field] += $remaining;
                } else {
                    $wallet[$arrears_field] -= $amount;
                }
            } else {
                $wallet[$balance_field] += $amount;
            }
        }
        
        // Update wallet
        $this->db->where('student_id', $student_id);
        $this->db->update('daily_fee_wallet', [
            $balance_field => $wallet[$balance_field],
            $arrears_field => $wallet[$arrears_field],
            'last_updated' => time()
        ]);
        
        // Audit log
        $this->log_audit($student_id, 'payment', $fee_type, $amount, $balance_before, $wallet[$balance_field],
                        $arrears_before, $wallet[$arrears_field], 'Payment received - ' . $payment_type, $collected_by);
    }

    /**
     * Get student outstanding summary
     */
    public function get_student_outstanding($student_id) {
        $wallet = $this->get_student_wallet($student_id);
        
        $total_balance = $wallet['feeding_balance'] + $wallet['breakfast_balance'] + 
                        $wallet['classes_balance'] + $wallet['water_balance'] + $wallet['transport_balance'];
        
        $total_arrears = $wallet['feeding_arrears'] + $wallet['breakfast_arrears'] + 
                        $wallet['classes_arrears'] + $wallet['water_arrears'] + $wallet['transport_arrears'];
        
        return [
            'wallet' => $wallet,
            'total_balance' => $total_balance,
            'total_arrears' => $total_arrears,
            'net_position' => $total_balance - $total_arrears
        ];
    }

    /**
     * Recalculate wallet balances from a specific date forward
     * Used when backdated transactions are added/modified
     */
    public function recalculate_wallet_from_date($student_id, $from_date) {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Get all transactions from the date forward, ordered by date
        $this->db->where('student_id', $student_id)
                 ->where('payment_date >=', $from_date)
                 ->order_by('payment_date', 'ASC')
                 ->order_by('created_at', 'ASC');
        $transactions = $this->db->get('daily_fee_transactions')->result_array();
        
        // Get all charges from the date forward
        $this->db->where('student_id', $student_id)
                 ->where('charge_date >=', $from_date)
                 ->order_by('charge_date', 'ASC')
                 ->order_by('charged_at', 'ASC');
        $charges = $this->db->get('daily_charge_log')->result_array();
        
        // Get all top-ups from the date forward (from audit log)
        $this->db->where('student_id', $student_id)
                 ->where('created_at >=', $from_date)
                 ->where('action_type', 'topup')
                 ->order_by('created_at', 'ASC');
        $topups = $this->db->get('daily_fee_audit_log')->result_array();
        
        // Get wallet state just before the from_date
        $wallet = $this->get_wallet_state_before_date($student_id, $from_date);
        
        // Merge and sort all events chronologically
        $events = [];
        foreach ($transactions as $t) {
            $events[] = ['type' => 'payment', 'date' => $t['payment_date'], 'time' => $t['created_at'] ?? $t['payment_date'], 'data' => $t];
        }
        foreach ($charges as $c) {
            $events[] = ['type' => 'charge', 'date' => $c['charge_date'], 'time' => $c['charged_at'] ?? $c['charge_date'], 'data' => $c];
        }
        foreach ($topups as $topup) {
            $events[] = ['type' => 'topup', 'date' => $topup['created_at'], 'time' => $topup['created_at'], 'data' => $topup];
        }
        
        usort($events, function($a, $b) {
            if ($a['date'] == $b['date']) {
                return $a['time'] - $b['time'];
            }
            return $a['date'] - $b['date'];
        });
        
        // Replay all events to recalculate balances
        foreach ($events as $event) {
            if ($event['type'] == 'payment') {
                $this->replay_payment($wallet, $event['data']);
            } elseif ($event['type'] == 'charge') {
                $this->replay_charge($wallet, $event['data']);
            } elseif ($event['type'] == 'topup') {
                $this->replay_topup($wallet, $event['data']);
            }
        }
        
        // Update wallet with final calculated state
        $this->db->where('student_id', $student_id)
                 ->where('year', $running_year)
                 ->where('term', $running_term)
                 ->update('daily_fee_wallet', [
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
        
        return $wallet;
    }
    
    /**
     * Get wallet state just before a specific date
     */
    private function get_wallet_state_before_date($student_id, $before_date) {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Start with zero balances
        $wallet = [
            'feeding_balance' => 0, 'feeding_arrears' => 0,
            'breakfast_balance' => 0, 'breakfast_arrears' => 0,
            'classes_balance' => 0, 'classes_arrears' => 0,
            'water_balance' => 0, 'water_arrears' => 0,
            'transport_balance' => 0, 'transport_arrears' => 0
        ];
        
        // Get all transactions before the date
        $this->db->where('student_id', $student_id)
                 ->where('payment_date <', $before_date)
                 ->order_by('payment_date', 'ASC');
        $transactions = $this->db->get('daily_fee_transactions')->result_array();
        
        // Get all charges before the date
        $this->db->where('student_id', $student_id)
                 ->where('charge_date <', $before_date)
                 ->order_by('charge_date', 'ASC');
        $charges = $this->db->get('daily_charge_log')->result_array();
        
        // Get all top-ups before the date
        $this->db->where('student_id', $student_id)
                 ->where('created_at <', $before_date)
                 ->where('action_type', 'topup')
                 ->order_by('created_at', 'ASC');
        $topups = $this->db->get('daily_fee_audit_log')->result_array();
        
        // Replay all events to get state
        $events = [];
        foreach ($transactions as $t) {
            $events[] = ['type' => 'payment', 'date' => $t['payment_date'], 'data' => $t];
        }
        foreach ($charges as $c) {
            $events[] = ['type' => 'charge', 'date' => $c['charge_date'], 'data' => $c];
        }
        foreach ($topups as $topup) {
            $events[] = ['type' => 'topup', 'date' => $topup['created_at'], 'data' => $topup];
        }
        
        usort($events, function($a, $b) {
            return $a['date'] - $b['date'];
        });
        
        foreach ($events as $event) {
            if ($event['type'] == 'payment') {
                $this->replay_payment($wallet, $event['data']);
            } elseif ($event['type'] == 'charge') {
                $this->replay_charge($wallet, $event['data']);
            } elseif ($event['type'] == 'topup') {
                $this->replay_topup($wallet, $event['data']);
            }
        }
        
        return $wallet;
    }
    
    /**
     * Replay a payment transaction on wallet state
     */
    private function replay_payment(&$wallet, $transaction) {
        $fee_types = ['feeding', 'breakfast', 'classes', 'water', 'transport'];
        $payment_type = $transaction['payment_type'] ?? 'mixed';
        
        foreach ($fee_types as $type) {
            $amount = $transaction[$type . '_amount'] ?? 0;
            if ($amount <= 0) continue;
            
            $balance_field = $type . '_balance';
            $arrears_field = $type . '_arrears';
            
            if ($payment_type == 'advance') {
                $wallet[$balance_field] += $amount;
            } elseif ($payment_type == 'arrears') {
                if ($amount >= $wallet[$arrears_field]) {
                    $remaining = $amount - $wallet[$arrears_field];
                    $wallet[$arrears_field] = 0;
                    $wallet[$balance_field] += $remaining;
                } else {
                    $wallet[$arrears_field] -= $amount;
                }
            } else {
                // Mixed/Current
                if ($wallet[$arrears_field] > 0) {
                    if ($amount >= $wallet[$arrears_field]) {
                        $remaining = $amount - $wallet[$arrears_field];
                        $wallet[$arrears_field] = 0;
                        $wallet[$balance_field] += $remaining;
                    } else {
                        $wallet[$arrears_field] -= $amount;
                    }
                } else {
                    $wallet[$balance_field] += $amount;
                }
            }
        }
    }
    
    /**
     * Replay a charge on wallet state
     */
    private function replay_charge(&$wallet, $charge) {
        $fee_types = ['feeding', 'breakfast', 'classes', 'water', 'transport'];
        
        foreach ($fee_types as $type) {
            $amount = $charge[$type . '_charged'] ?? 0;
            if ($amount <= 0) continue;
            
            $balance_field = $type . '_balance';
            $arrears_field = $type . '_arrears';
            
            if ($wallet[$balance_field] >= $amount) {
                // Sufficient balance - deduct from balance
                $wallet[$balance_field] -= $amount;
            } else {
                // Insufficient balance - use available balance then add remainder to arrears
                $shortfall = $amount - $wallet[$balance_field];
                $wallet[$balance_field] = 0;
                $wallet[$arrears_field] += $shortfall;
            }
        }
    }

    /**
     * Replay a top-up on wallet state
     */
    private function replay_topup(&$wallet, $topup) {
        $fee_type = $topup['fee_type'] ?? 'transport';
        $amount = $topup['amount'] ?? 0;
        
        if ($amount == 0) return;
        
        $balance_field = $fee_type . '_balance';
        
        // Add top-up amount to balance (can be negative for reconciliation reductions)
        $wallet[$balance_field] += $amount;
    }

    /**
     * Log audit trail
     */
    private function log_audit($student_id, $action_type, $fee_type, $amount, $balance_before, $balance_after, 
                               $arrears_before, $arrears_after, $notes = '', $performed_by = null) {
        $login_type = $this->session->userdata('login_type');
        if (!$performed_by) {
            $performed_by = $login_type == 'teacher' ? $this->session->userdata('teacher_id') : $this->session->userdata('admin_id');
        }
        $this->db->insert('daily_fee_audit_log', [
            'student_id' => $student_id,
            'action_type' => $action_type,
            'fee_type' => $fee_type,
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'arrears_before' => $arrears_before,
            'arrears_after' => $arrears_after,
            'performed_by' => $performed_by,
            'performed_by_role' => $login_type,
            'notes' => $notes,
            'created_at' => time()
        ]);
    }

    /**
     * Delete a payment transaction and reverse wallet entries
     * Used when cashier removes all amounts from a transaction
     */
    public function delete_payment($transaction_id) {
        // Get existing transaction
        $transaction = $this->db->get_where('daily_fee_transactions', [
            'id' => $transaction_id
        ])->row_array();
        
        if (!$transaction) {
            return ['status' => 'error', 'message' => 'Transaction not found'];
        }
        
        $student_id = $transaction['student_id'];
        $payment_type = $transaction['payment_type'] ?? 'mixed';
        
        // Start transaction
        $this->db->trans_start();
        
        // Reverse wallet entries
        $wallet = $this->get_student_wallet($student_id);
        $fee_types = ['feeding', 'breakfast', 'classes', 'water', 'transport'];
        
        foreach ($fee_types as $type) {
            $amount = floatval($transaction[$type . '_amount'] ?? 0);
            if ($amount > 0) {
                $this->reverse_payment_wallet($student_id, $type, $amount, $wallet, $payment_type);
            }
        }
        
        // Create audit log
        $this->db->insert('daily_fee_audit_log', [
            'student_id' => $student_id,
            'action_type' => 'delete_payment',
            'fee_type' => 'multiple',
            'amount' => $transaction['total_amount'],
            'balance_before' => 0,
            'balance_after' => 0,
            'arrears_before' => 0,
            'arrears_after' => 0,
            'performed_by' => $this->session->userdata('admin_id'),
            'performed_by_role' => $this->session->userdata('login_type'),
            'notes' => 'Transaction deleted - receipt: ' . $transaction['receipt_number'],
            'created_at' => time()
        ]);
        
        // Reverse accounting entry if exists
        if ($this->db->table_exists('journal_entries')) {
            $this->reverse_payment_accounting($transaction_id);
        }
        
        // Delete the transaction
        $this->db->where('id', $transaction_id)->delete('daily_fee_transactions');
        
        // Complete transaction
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => 'Failed to delete payment'];
        }
        
        return [
            'status' => 'success',
            'message' => 'Payment deleted successfully'
        ];
    }

    /**
     * Get payment history for student
     */
    public function get_payment_history($student_id, $limit = 10) {
        return $this->db->where('student_id', $student_id)
                       ->order_by('created_at', 'DESC')
                       ->limit($limit)
                       ->get('daily_fee_transactions')
                       ->result_array();
    }
    
    /**
     * Update wallet when transaction is updated (for same-day updates)
     */
    public function update_wallet_from_transaction($student_id, $feeding, $breakfast, $classes, $water, $transport) {
        // This method is called when updating an existing transaction
        // We don't modify wallet here because the original payment already updated it
        // The wallet reflects the cumulative state, not individual transactions
        return true;
    }
    
    /**
     * Record transport decision and process payment
     */
    public function record_transport_decision($student_id, $direction, $collected_by, $collection_point = 'office', $attendance_date = null) {
        $today = $attendance_date ? strtotime(date('Y-m-d', $attendance_date)) : strtotime(date('Y-m-d'));
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Get student's route info
        $enroll = $this->db->get_where('enroll', ['student_id' => $student_id, 'year' => $running_year, 'term' => $running_term])->row();
        if (!$enroll || !$enroll->transport_id) {
            return ['status' => 'error', 'message' => 'Student not assigned to any route'];
        }
        
        $route = $this->db->get_where('transport', ['transport_id' => $enroll->transport_id])->row();
        $route_fare = $route->route_fare ?? 0;
        
        // Calculate fare
        $fare = 0;
        $in_fare = 0;
        $out_fare = 0;
        
        switch($direction) {
            case 'in':
                $fare = $route_fare;
                $in_fare = $route_fare;
                break;
            case 'out':
                $fare = $route_fare;
                $out_fare = $route_fare;
                break;
            case 'both':
                $fare = $route_fare * 2;
                $in_fare = $route_fare;
                $out_fare = $route_fare;
                break;
            case 'none':
                $fare = 0;
                break;
        }
        
        // Just record intention - NO DEDUCTION YET
        // Deduction happens when conductor confirms boarding
        $payment_status = 'pending';
        $payment_source = 'pending';
        $cash_collected = 0;
        $prepaid_deducted = 0;
        
        // Check if sufficient balance exists (for information only)
        $wallet = $this->get_student_wallet($student_id);
        $has_sufficient_balance = ($wallet['transport_balance'] >= $fare);
        
        // Record in bus_attendance
        $data = [
            'student_id' => $student_id,
            'route_id' => $enroll->transport_id,
            'attendance_date' => $today,
            'transport_direction' => $direction,
            'in_fare' => $in_fare,
            'out_fare' => $out_fare,
            'total_fare' => $fare,
            'payment_status' => $payment_status,
            'paid_amount' => $fare,
            'payment_time' => time(),
            'collected_by' => $collected_by,
            'collected_by_role' => $this->session->userdata('login_type'),
            'collection_point' => $collection_point,
            'payment_source' => $payment_source,
            'cash_collected' => $cash_collected,
            'prepaid_deducted' => $prepaid_deducted,
            'year' => $running_year,
            'term' => $running_term,
            'created_at' => time()
        ];
        
        // Check if already exists
        $existing = $this->db->get_where('bus_attendance', ['student_id' => $student_id, 'attendance_date' => $today])->row();
        if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update('bus_attendance', $data);
        } else {
            $this->db->insert('bus_attendance', $data);
        }
        
        return [
            'status' => 'success',
            'fare' => $fare,
            'payment_status' => $payment_status,
            'has_sufficient_balance' => $has_sufficient_balance,
            'message' => 'Transport intention recorded - Deduction will happen when student boards'
        ];
    }
    
    // ==================== ACCOUNTING INTEGRATION ====================
    
    /**
     * ENTERPRISE-GRADE: Recognize revenue when service is delivered (attendance marked)
     * Handles 3 scenarios:
     * 1. Prepaid: DR Unearned Revenue, CR Revenue
     * 2. Cash on delivery: DR Cash, CR Revenue
     * 3. Credit/Arrears: DR Accounts Receivable, CR Revenue
     */
    private function recognize_revenue($student_id, $charges, $attendance_date) {
        $total_charged = array_sum($charges);
        if ($total_charged == 0) return;
        
        if (!$this->db->table_exists('journal_entries')) return;
        
        $wallet = $this->get_student_wallet($student_id);
        
        // Determine payment scenario for each fee type
        foreach ($charges as $fee_type => $amount) {
            if ($amount <= 0) continue;
            
            $fee_type_clean = str_replace('_charged', '', $fee_type);
            $balance_field = $fee_type_clean . '_balance';
            $arrears_field = $fee_type_clean . '_arrears';
            
            $revenue_account = $this->get_revenue_account($fee_type);
            if (!$revenue_account) continue;
            
            $entry_number = 'JE-REV-' . strtoupper($fee_type_clean) . '-' . date('Ymd', $attendance_date) . '-' . $student_id;
            
            // Check if entry already exists
            $existing = $this->db->get_where('journal_entries', ['entry_number' => $entry_number])->row();
            if ($existing) continue;
            
            $entry_data = [
                'entry_number' => $entry_number,
                'entry_date' => date('Y-m-d', $attendance_date),
                'description' => ucfirst($fee_type_clean) . ' fee revenue - Student #' . $student_id,
                'source_type' => 'daily_fee_revenue',
                'source_id' => $student_id,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'posted',
            ];
            
            $this->db->insert('journal_entries', $entry_data);
            $entry_id = $this->db->insert_id();
            
            $lines = [];
            
            // Determine debit account based on payment status
            if ($wallet[$arrears_field] > 0) {
                // SCENARIO 3: Credit/Arrears - Student owes money
                // DR: Accounts Receivable (1200)
                $ar_account = $this->get_account_by_code('1200');
                if ($ar_account) {
                    $lines[] = [
                        'entry_id' => $entry_id,
                        'account_id' => $ar_account['account_id'],
                        'debit_amount' => $amount,
                        'credit_amount' => 0,
                        'description' => 'Arrears - ' . ucfirst($fee_type_clean)
                    ];
                }
            } else {
                // SCENARIO 1: Prepaid - Student has balance
                // DR: Unearned Revenue (2040)
                $unearned_account = $this->get_account_by_code('2040');
                if ($unearned_account) {
                    $lines[] = [
                        'entry_id' => $entry_id,
                        'account_id' => $unearned_account['account_id'],
                        'debit_amount' => $amount,
                        'credit_amount' => 0,
                        'description' => 'Revenue recognition - Prepaid'
                    ];
                }
            }
            
            // CR: Revenue account (always)
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $revenue_account['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => ucfirst($fee_type_clean) . ' revenue earned'
            ];
            
            if (!empty($lines)) {
                $this->db->insert_batch('journal_entry_lines', $lines);
                
                // Update account balances
                foreach ($lines as $line) {
                    $this->update_account_balance($line['account_id'], $line['debit_amount'], $line['credit_amount']);
                }
            }
        }
    }
    
    /**
     * Sync daily fee payment to accounting module
     * Creates journal entries for revenue recognition
     */
    private function sync_payment_to_accounts($transaction_id) {
        // Check if accounting integration is enabled
        if (!$this->db->table_exists('journal_entries')) {
            return ['status' => 'skipped', 'message' => 'Accounting module not available'];
        }
        
        // Check if already synced
        if ($this->db->field_exists('synced_to_accounts', 'daily_fee_transactions')) {
            $trans = $this->db->get_where('daily_fee_transactions', ['id' => $transaction_id])->row();
            if ($trans && $trans->synced_to_accounts == 1) {
                return ['status' => 'skipped', 'message' => 'Already synced'];
            }
        }
        
        // Load financial integration hooks
        $this->load->library('Financial_integration_hooks');
        
        // Sync to accounting
        $result = $this->financial_integration_hooks->sync_daily_fee($transaction_id);
        
        return $result;
    }
    
    /**
     * Get account by code
     */
    private function get_account_by_code($code) {
        $account = $this->db->get_where('chart_of_accounts', ['account_code' => $code, 'is_active' => 1])->row_array();
        return $account ? ['account_id' => $account['account_id'], 'account_name' => $account['account_name']] : null;
    }
    
    /**
     * Update account balance after journal entry
     */
    private function update_account_balance($account_id, $debit, $credit) {
        $account = $this->db->get_where('chart_of_accounts', ['account_id' => $account_id])->row();
        if (!$account) return;
        
        $net_change = 0;
        
        // Asset & Expense: Debit increases, Credit decreases
        if (in_array($account->account_type, ['asset', 'expense'])) {
            $net_change = $debit - $credit;
        }
        // Liability, Equity & Revenue: Credit increases, Debit decreases
        else {
            $net_change = $credit - $debit;
        }
        
        $new_balance = $account->current_balance + $net_change;
        
        $this->db->where('account_id', $account_id);
        $this->db->update('chart_of_accounts', ['current_balance' => $new_balance]);
    }
    
    /**
     * Get revenue account for fee type
     */
    private function get_revenue_account($fee_type) {
        $fee_type = str_replace('_charged', '', $fee_type);
        $mapping = [
            'feeding' => '4200',
            'breakfast' => '4210',
            'classes' => '4220',
            'water' => '4230',
            'transport' => '4240'
        ];
        
        $code = $mapping[$fee_type] ?? null;
        return $code ? $this->get_account_by_code($code) : null;
    }
    
    /**
     * Get cash account based on payment method
     */
    private function get_cash_account($payment_method) {
        $mapping = [
            1 => '1010', // Cash
            2 => '1020', // Bank
            3 => '1030'  // Mobile Money
        ];
        
        $code = $mapping[$payment_method] ?? '1010';
        return $this->get_account_by_code($code);
    }
    
    /**
     * Sync daily fee charge to student ledger
     * Records the charge as a debit to student account
     */
    private function sync_charge_to_ledger($student_id, $fee_type, $amount, $charge_date) {
        // Check if student ledger exists
        if (!$this->db->table_exists('student_ledger')) {
            return ['status' => 'skipped'];
        }
        
        $this->load->model('Finance_model');
        
        $current_balance = $this->Finance_model->get_student_ledger_balance($student_id);
        $new_balance = $current_balance + $amount;
        
        $this->db->insert('student_ledger', [
            'student_id' => $student_id,
            'transaction_date' => date('Y-m-d', $charge_date),
            'transaction_type' => 'invoice', // FIX: Changed from 'daily_fee_charge' to valid ENUM value
            'reference_type' => 'daily_fee',
            'reference_id' => $fee_type . '_' . date('Ymd', $charge_date),
            'description' => ucfirst($fee_type) . ' fee charged',
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'balance' => $new_balance,
            'year' => get_settings('running_year'),
            'term' => get_settings('running_term'),
            'created_by' => $this->session->userdata('admin_id') ?: 1,
            'created_at' => time()
        ]);
        
        return ['status' => 'success', 'ledger_id' => $this->db->insert_id()];
    }
    
    /**
     * Sync daily fee payment to student ledger
     * Records the payment as a credit to student account
     */
    private function sync_payment_to_ledger($student_id, $fee_type, $amount, $payment_date) {
        // Check if student ledger exists
        if (!$this->db->table_exists('student_ledger')) {
            return ['status' => 'skipped'];
        }
        
        $this->load->model('Finance_model');
        
        $current_balance = $this->Finance_model->get_student_ledger_balance($student_id);
        $new_balance = $current_balance - $amount;
        
        $this->db->insert('student_ledger', [
            'student_id' => $student_id,
            'transaction_date' => date('Y-m-d', $payment_date),
            'transaction_type' => 'payment', // FIX: Changed from 'daily_fee_payment' to valid ENUM value
            'reference_type' => 'daily_fee',
            'reference_id' => $fee_type . '_' . date('Ymd', $payment_date),
            'description' => ucfirst($fee_type) . ' fee payment',
            'debit_amount' => 0,
            'credit_amount' => $amount,
            'balance' => $new_balance,
            'year' => get_settings('running_year'),
            'term' => get_settings('running_term'),
            'created_by' => $this->session->userdata('admin_id') ?: 1,
            'created_at' => time()
        ]);
        
        return ['status' => 'success', 'ledger_id' => $this->db->insert_id()];
    }
    
    // ==================== END ACCOUNTING INTEGRATION ====================
    
    /**
     * ENTERPRISE-GRADE: Mark student as boarded and charge transport
     * This is the ONLY place where transport is charged
     * Can be done by: Conductor, Cashier, Admin, Teacher
     * Direction: in, out, both, none
     */
    public function mark_student_boarded($student_id, $direction, $marked_by, $attendance_date = null) {
        $today = $attendance_date ? strtotime(date('Y-m-d', $attendance_date)) : strtotime(date('Y-m-d'));
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Get enrollment and route info
        $enroll = $this->db->get_where('enroll', [
            'student_id' => $student_id, 
            'year' => $running_year, 
            'term' => $running_term
        ])->row();
        
        if (!$enroll || !$enroll->transport_id) {
            return ['status' => 'error', 'message' => 'Student not assigned to any route'];
        }
        
        $route = $this->db->get_where('transport', ['transport_id' => $enroll->transport_id])->row();
        if (!$route) {
            return ['status' => 'error', 'message' => 'Transport route not found'];
        }
        
        $route_fare = $route->route_fare ?? 0;
        
        // Check for existing record
        $bus_record = $this->db->get_where('bus_attendance', [
            'student_id' => $student_id, 
            'attendance_date' => $today
        ])->row();
        
        // Calculate new fares based on direction
        $in_fare = ($direction == 'in' || $direction == 'both') ? $route_fare : 0;
        $out_fare = ($direction == 'out' || $direction == 'both') ? $route_fare : 0;
        $total_fare = $in_fare + $out_fare;
        
        // Handle 'none' direction
        if ($direction == 'none') {
            if ($bus_record) {
                // Reverse previous charge if any
                if ($bus_record->transport_charged > 0) {
                    $this->reverse_transport_charge($student_id, $bus_record->transport_charged);
                }
                
                $this->db->where('id', $bus_record->id)->update('bus_attendance', [
                    'transport_direction' => 'none',
                    'in_fare' => 0,
                    'out_fare' => 0,
                    'total_fare' => 0,
                    'boarded_in' => 0,
                    'boarded_out' => 0,
                    'conductor_id' => $marked_by,
                    'transport_charged' => 0
                ]);
                
                $this->update_charge_log($student_id, $today, 0);
            }
            return ['status' => 'success', 'message' => 'Marked as not using transport', 'fare' => 0];
        }
        
        // Apply discount
        $this->load->model('Discount_model');
        $original_fare = $total_fare;
        $discount_info = $this->Discount_model->get_student_discount_for_date(
            $student_id, $running_year, $running_term, $enroll->class_id, 'transport', $today
        );
        
        if ($discount_info['has_discount']) {
            $discount_amount = $discount_info['discount_type'] == 'percentage' 
                ? $original_fare * ($discount_info['discount_value'] / 100)
                : min($discount_info['discount_value'], $original_fare);
            $total_fare = max(0, $original_fare - $discount_amount);
            
            record_discount_safely($this, [
                'student_id' => $student_id,
                'profile_id' => $discount_info['profile_id'],
                'discount_category' => 'daily_fees',
                'reference_type' => 'transport_boarding',
                'reference_id' => $today,
                'bill_item_type' => 'transport',
                'original_amount' => $original_fare,
                'discount_percentage' => $discount_info['discount_type'] == 'percentage' ? $discount_info['discount_value'] : 0,
                'discount_amount' => $discount_amount,
                'final_amount' => $total_fare,
                'year' => $running_year,
                'term' => $running_term
            ]);
        }
        
        // Get previous charge safely (using amount_charged field)
        $previous_charge = 0;
        if ($bus_record) {
            $previous_charge = $bus_record->amount_charged ?? 0;
        }
        
        // Get wallet and reverse previous charge if updating
        $wallet = $this->get_student_wallet($student_id);
        
        if ($previous_charge > 0) {
            $this->reverse_transport_charge($student_id, $previous_charge);
            $wallet = $this->get_student_wallet($student_id); // Refresh wallet
        }
        
        // Apply new charge
        $balance_before = $wallet['transport_balance'];
        $arrears_before = $wallet['transport_arrears'];
        
        if ($wallet['transport_balance'] >= $total_fare) {
            // Fully paid from prepaid balance
            $wallet['transport_balance'] -= $total_fare;
            $payment_status = 'paid';
            $payment_source = 'prepaid';
            $prepaid_deducted = $total_fare;
            $cash_collected = 0;
        } else if ($wallet['transport_balance'] > 0) {
            // Partial prepaid, rest goes to arrears (student owes)
            $prepaid_used = $wallet['transport_balance'];
            $arrears_amount = $total_fare - $prepaid_used;
            $wallet['transport_balance'] = 0;
            $wallet['transport_arrears'] += $arrears_amount;
            $payment_status = 'partial';
            $payment_source = 'mixed';
            $prepaid_deducted = $prepaid_used;
            $cash_collected = 0; // No cash collected, student owes the rest
        } else {
            // No prepaid balance, all goes to arrears (student owes)
            $wallet['transport_arrears'] += $total_fare;
            $payment_status = 'unpaid';
            $payment_source = 'arrears';
            $prepaid_deducted = 0;
            $cash_collected = 0; // No cash collected, student owes everything
        }
        
        // Update wallet
        $this->db->where('student_id', $student_id)->update('daily_fee_wallet', [
            'transport_balance' => $wallet['transport_balance'],
            'transport_arrears' => $wallet['transport_arrears'],
            'last_updated' => time()
        ]);
        
        // Audit log
        $this->log_audit($student_id, 'charge', 'transport', $total_fare, $balance_before, 
            $wallet['transport_balance'], $arrears_before, $wallet['transport_arrears'], 
            'Transport charge - ' . $direction . ($previous_charge > 0 ? ' (updated)' : ''), $marked_by);
        
        // Update or insert bus_attendance (using actual table structure)
        $bus_data = [
            'transport_direction' => $direction,
            'in_fare' => $in_fare,
            'out_fare' => $out_fare,
            'total_fare' => $original_fare,
            'amount_charged' => $total_fare,
            'boarded_in' => ($direction == 'in' || $direction == 'both') ? 1 : 0,
            'boarded_out' => ($direction == 'out' || $direction == 'both') ? 1 : 0,
            'in_time' => ($direction == 'in' || $direction == 'both') ? date('H:i') : null,
            'out_time' => ($direction == 'out' || $direction == 'both') ? date('H:i') : null,
            'conductor_id' => $marked_by,
            'payment_status' => $payment_status,
            'payment_source' => $payment_source,
            'prepaid_deducted' => $prepaid_deducted,
            'cash_collected' => $cash_collected,
            'paid_amount' => $total_fare,
            'payment_time' => time(),
            'collected_by' => $marked_by,
            'collected_by_role' => $this->session->userdata('login_type')
        ];
        
        
        if ($bus_record) {
            $this->db->where('id', $bus_record->id)->update('bus_attendance', $bus_data);
        } else {
            $bus_data['student_id'] = $student_id;
            $bus_data['route_id'] = $enroll->transport_id;
            $bus_data['attendance_date'] = $today;
            $bus_data['collection_point'] = 'portal';
            $bus_data['year'] = $running_year;
            $bus_data['term'] = $running_term;
            $bus_data['created_at'] = time();
            $this->db->insert('bus_attendance', $bus_data);
        }
        
        // Update charge log
        $this->update_charge_log($student_id, $today, $total_fare);
        
        // Update attendance table if exists
        $this->update_attendance_transport($student_id, $today, $total_fare);
        
        // Update daily_fee_transactions if exists for today
        $this->update_transaction_transport($student_id, $today, $total_fare);
        
        // Recognize revenue
        $this->recognize_revenue($student_id, ['transport_charged' => $total_fare], $today);
        
        return [
            'status' => 'success', 
            'message' => 'Transport updated - ' . strtoupper($direction), 
            'fare' => $total_fare,
            'payment_status' => $payment_status
        ];
    }
    
    /**
     * Reverse transport charge from wallet
     */
    private function reverse_transport_charge($student_id, $amount) {
        $wallet = $this->get_student_wallet($student_id);
        
        // Reverse the charge: restore balance or reduce arrears
        if ($wallet['transport_arrears'] >= $amount) {
            $wallet['transport_arrears'] -= $amount;
        } else {
            $remaining = $amount - $wallet['transport_arrears'];
            $wallet['transport_arrears'] = 0;
            $wallet['transport_balance'] += $remaining;
        }
        
        $this->db->where('student_id', $student_id)->update('daily_fee_wallet', [
            'transport_balance' => $wallet['transport_balance'],
            'transport_arrears' => $wallet['transport_arrears'],
            'last_updated' => time()
        ]);
    }
    
    /**
     * Update charge log with latest transport charge
     */
    private function update_charge_log($student_id, $charge_date, $transport_charged) {
        $existing_log = $this->db->get_where('daily_charge_log', [
            'student_id' => $student_id,
            'charge_date' => $charge_date
        ])->row();
        
        if ($existing_log) {
            $this->db->where('student_id', $student_id)
                     ->where('charge_date', $charge_date)
                     ->update('daily_charge_log', [
                         'transport_charged' => $transport_charged,
                         'total_charged' => $existing_log->feeding_charged + $existing_log->breakfast_charged + 
                                           $existing_log->classes_charged + $existing_log->water_charged + $transport_charged,
                         'charged_at' => time()
                     ]);
        } else {
            $this->db->insert('daily_charge_log', [
                'student_id' => $student_id,
                'charge_date' => $charge_date,
                'feeding_charged' => 0,
                'breakfast_charged' => 0,
                'classes_charged' => 0,
                'water_charged' => 0,
                'transport_charged' => $transport_charged,
                'total_charged' => $transport_charged,
                'charged_at' => time()
            ]);
        }
    }
    
    /**
     * Update attendance table transport charge
     */
    private function update_attendance_transport($student_id, $attendance_date, $transport_charged) {
        $attendance = $this->db->get_where('attendance', [
            'student_id' => $student_id,
            'timestamp' => $attendance_date
        ])->row();
        
        if ($attendance) {
            $this->db->where('student_id', $student_id)
                     ->where('timestamp', $attendance_date)
                     ->update('attendance', ['transport_charged' => $transport_charged]);
        }
    }
    
    /**
     * Update daily_fee_transactions table transport amount
     */
    private function update_transaction_transport($student_id, $payment_date, $transport_amount) {
        $transaction = $this->db->where('student_id', $student_id)
                                ->where('payment_date', $payment_date)
                                ->order_by('created_at', 'DESC')
                                ->get('daily_fee_transactions', 1)
                                ->row();
        
        if ($transaction) {
            $new_total = $transaction->feeding_amount + $transaction->breakfast_amount + 
                        $transaction->classes_amount + $transaction->water_amount + $transport_amount;
            
            $this->db->where('id', $transaction->id)->update('daily_fee_transactions', [
                'transport_amount' => $transport_amount,
                'total_amount' => $new_total,
                'modified_at' => time()
            ]);
        }
    }
    
    /**
     * Get total daily revenue for a specific date
     * Includes: daily fees, inventory sales, and invoice payments
     * 
     * @param int $timestamp Unix timestamp for the date
     * @return float Total revenue amount
     */
    public function get_daily_revenue_by_date($timestamp) {
        // Handle invalid timestamp
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            log_message('info', 'Invalid timestamp provided: ' . $timestamp);
            return 0;
        }
        
        $date_start = strtotime(date('Y-m-d 00:00:00', $timestamp));
        $date_end = strtotime(date('Y-m-d 23:59:59', $timestamp));
        $date_string = date('Y-m-d', $timestamp);
        
        $total_revenue = 0;
        
        // 1. Daily fee transactions
        $this->db->select_sum('total_amount');
        $this->db->where('payment_date >=', $date_start);
        $this->db->where('payment_date <=', $date_end);
        $result = $this->db->get('daily_fee_transactions');
        $total_revenue += $result->row()->total_amount ?? 0;
        
        // 2. Inventory sales
        if ($this->db->table_exists('inventory_sales')) {
            $this->db->select_sum('total_amount');
            $this->db->where('DATE(sale_date)', $date_string);
            $result = $this->db->get('inventory_sales');
            $total_revenue += $result->row()->total_amount ?? 0;
        }
        
        // 3. Invoice payments (from payment table - uses 'timestamp' column as unix timestamp)
        $this->db->select_sum('amount');
        $this->db->where('payment_type', 'income');
        $this->db->where('timestamp >=', $date_start);
        $this->db->where('timestamp <=', $date_end);
        $this->db->where('can_delete !=', 'trash');
        $result = $this->db->get('payment');
        $total_revenue += $result->row()->amount ?? 0;
        
        return $total_revenue;
    }
    
    /**
     * Get detailed payment records for a specific date
     * 
     * @param int $timestamp Unix timestamp for the date
     * @param string|null $fee_type Filter by fee type (feeding, breakfast, classes, water, transport)
     * @param int|null $class_id Filter by class ID
     * @return array Array of payment records with student and class information
     */
    public function get_daily_revenue_details($timestamp, $fee_type = null, $class_id = null) {
        $running_year = get_settings('running_year');
        
        $date_start = strtotime(date('Y-m-d 00:00:00', $timestamp));
        $date_end = strtotime(date('Y-m-d 23:59:59', $timestamp));
        
        $this->db->select('dft.*, s.name as student_name, s.student_code, c.name as class_name, c.name_numeric, c.class_id, sec.name as section_name');
        $this->db->from('daily_fee_transactions dft');
        $this->db->join('student s', 's.student_id = dft.student_id');
        $this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = "' . $running_year . '"', 'left');
        $this->db->join('class c', 'c.class_id = e.class_id', 'left');
        $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
        $this->db->where('dft.payment_date >=', $date_start);
        $this->db->where('dft.payment_date <=', $date_end);
        
        // Apply fee type filter - only show records where that fee type has amount > 0
        if ($fee_type && $fee_type !== 'all') {
            $this->db->where('dft.' . $fee_type . '_amount >', 0);
        }
        
        // Apply class filter
        if ($class_id && $class_id !== 'all') {
            $this->db->where('e.class_id', $class_id);
        }
        
        // Group by id to prevent duplicates
        $this->db->group_by('dft.id');
        $this->db->order_by('s.name', 'ASC');
        
        return $this->db->get()->result_array();
    }
}
