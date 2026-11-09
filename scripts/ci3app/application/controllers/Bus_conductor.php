<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bus Conductor Controller
 * Handles transport attendance and fare collection
 */
class Bus_conductor extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Check if user is logged in as conductor
        if ($this->session->userdata('admin_id') == '' || $this->session->userdata('login_type') != 'conductor') {
            redirect(site_url('login'), 'refresh');
        }
    }

    /**
     * Dashboard for bus conductor
     */
    public function index() {
        $page_data['page_name'] = 'conductor_dashboard';
        $page_data['page_title'] = get_phrase('bus_conductor_dashboard');
        $this->load->view('backend/main', $page_data);
    }

    /**
     * Mark bus attendance (IN/OUT)
     */
    public function mark_bus_attendance() {
        $route_id = $this->input->post('route_id');
        $attendance_date = strtotime($this->input->post('attendance_date'));
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        $sem = $this->input->post('sem');
        $direction = $this->input->post('direction'); // 'in' or 'out'
        $students_boarded = $this->input->post('students_boarded'); // Array of student IDs
        
        if (!is_array($students_boarded)) $students_boarded = [];
        
        $conductor_id = $this->session->userdata('admin_id');
        $success_count = 0;
        
        foreach ($students_boarded as $student_id) {
            // Get or create bus attendance record for today
            $bus_attendance = $this->db->get_where('bus_attendance', [
                'student_id' => $student_id,
                'attendance_date' => $attendance_date,
                'route_id' => $route_id
            ])->row();
            
            if ($bus_attendance) {
                // Update existing record
                $update_data = [
                    'conductor_id' => $conductor_id
                ];
                
                if ($direction == 'in') {
                    $update_data['boarded_in'] = 1;
                    $update_data['in_time'] = date('H:i');
                } else {
                    $update_data['boarded_out'] = 1;
                    $update_data['out_time'] = date('H:i');
                }
                
                $this->db->where('id', $bus_attendance->id);
                $this->db->update('bus_attendance', $update_data);
                
                $bus_attendance_id = $bus_attendance->id;
            } else {
                // Create new record
                $insert_data = [
                    'student_id' => $student_id,
                    'route_id' => $route_id,
                    'attendance_date' => $attendance_date,
                    'boarded_in' => $direction == 'in' ? 1 : 0,
                    'boarded_out' => $direction == 'out' ? 1 : 0,
                    'in_time' => $direction == 'in' ? date('H:i') : null,
                    'out_time' => $direction == 'out' ? date('H:i') : null,
                    'conductor_id' => $conductor_id,
                    'year' => $year,
                    'term' => $term,
                    'sem' => $sem,
                    'created_at' => time()
                ];
                
                $this->db->insert('bus_attendance', $insert_data);
                $bus_attendance_id = $this->db->insert_id();
            }
            
            // Get updated record to determine transport status
            $updated_record = $this->db->get_where('bus_attendance', ['id' => $bus_attendance_id])->row();
            
            $transport_status = 'none';
            if ($updated_record->boarded_in && $updated_record->boarded_out) {
                $transport_status = 'both';
            } elseif ($updated_record->boarded_in) {
                $transport_status = 'in';
            } elseif ($updated_record->boarded_out) {
                $transport_status = 'out';
            }
            
            // Get student's class for rate calculation
            $student = $this->db->select('e.class_id')
                               ->from('enroll e')
                               ->where('e.student_id', $student_id)
                               ->where('e.year', $year)
                               ->where('e.term', $term)
                               ->where('e.sem', $sem)
                               ->get()->row();
            
            if ($student) {
                // Get transport rates
                $rates = $this->Daily_fee_model->get_class_rates($student->class_id, $year, $term, $sem);
                
                $transport_charge = 0;
                switch ($transport_status) {
                    case 'in':
                        $transport_charge = $rates['transport_in_rate'];
                        break;
                    case 'out':
                        $transport_charge = $rates['transport_out_rate'];
                        break;
                    case 'both':
                        $transport_charge = $rates['transport_both_rate'];
                        break;
                }
                
                // Update bus attendance with charge
                $this->db->where('id', $bus_attendance_id);
                $this->db->update('bus_attendance', [
                    'amount_charged' => $transport_charge
                ]);
                
                // Apply transport charge to student wallet
                if ($transport_charge > 0) {
                    $wallet = $this->Daily_fee_model->get_student_wallet($student_id);
                    $prefs = $this->Daily_fee_model->get_student_preferences($student_id);
                    
                    // Process transport charge
                    $this->Daily_fee_model->process_daily_charges(
                        $student_id,
                        $attendance_date,
                        $student->class_id,
                        $year,
                        $term,
                        $sem,
                        ['transport_status' => $transport_status]
                    );
                    
                    // Update main attendance record if exists
                    $this->db->where('student_id', $student_id);
                    $this->db->where('timestamp', $attendance_date);
                    $this->db->update('attendance', [
                        'transport_status' => $transport_status,
                        'transport_charged' => $transport_charge
                    ]);
                }
            }
            
            $success_count++;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('bus_attendance_marked_successfully') . ". $success_count " . get_phrase('students_marked'),
            'success_count' => $success_count
        ]);
    }

    /**
     * Get students assigned to a route
     */
    public function get_route_students() {
        $route_id = $this->input->post('route_id');
        $attendance_date = strtotime($this->input->post('attendance_date'));
        
        // Get students assigned to this route
        $this->db->select('s.student_id, s.name, s.student_code, s.photo, tr.pickup_point');
        $this->db->from('student s');
        $this->db->join('transport_route tr', 's.student_id = tr.student_id');
        $this->db->where('tr.route_id', $route_id);
        $this->db->order_by('tr.pickup_point', 'ASC');
        $this->db->order_by('s.name', 'ASC');
        
        $students = $this->db->get()->result_array();
        
        // Enrich with today's bus attendance status
        foreach ($students as &$student) {
            $bus_attendance = $this->db->get_where('bus_attendance', [
                'student_id' => $student['student_id'],
                'attendance_date' => $attendance_date,
                'route_id' => $route_id
            ])->row();
            
            $student['boarded_in'] = $bus_attendance ? $bus_attendance->boarded_in : 0;
            $student['boarded_out'] = $bus_attendance ? $bus_attendance->boarded_out : 0;
            $student['in_time'] = $bus_attendance ? $bus_attendance->in_time : null;
            $student['out_time'] = $bus_attendance ? $bus_attendance->out_time : null;
            $student['amount_charged'] = $bus_attendance ? $bus_attendance->amount_charged : 0;
            
            // Get wallet status
            $outstanding = $this->Daily_fee_model->get_student_outstanding($student['student_id']);
            $student['transport_balance'] = $outstanding['wallet']['transport_balance'];
            $student['transport_arrears'] = $outstanding['wallet']['transport_arrears'];
        }
        
        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    }

    /**
     * ENTERPRISE-GRADE: Collect transport fare directly on bus (TRANSPORT ONLY)
     * Supports both CREATE and UPDATE modes to prevent duplicates
     * Calculates fare based on direction (in/out/both)
     */
    public function collect_transport_fare() {
        // Verify conductor can only collect transport
        if (!$this->Daily_fee_model->can_collect_fee_type('transport')) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('permission_denied')]);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $transport_direction = $this->input->post('transport_direction') ?: 'both';
        $payment_method = $this->input->post('payment_method') ?? 1;
        $payment_date = $this->input->post('payment_date') ?? time();
        
        $conductor_id = $this->session->userdata('admin_id');
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Get student's route and calculate fare based on direction
        $enroll = $this->db->get_where('enroll', [
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term
        ])->row();
        
        if (!$enroll || !$enroll->transport_id) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('student_not_assigned_to_route')]);
            return;
        }
        
        $route = $this->db->get_where('transport', ['transport_id' => $enroll->transport_id])->row();
        
        if (!$route) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('route_not_found')]);
            return;
        }
        
        $route_fare = $route->route_fare;
        $amount = 0;
        
        // Calculate amount based on direction
        switch ($transport_direction) {
            case 'in':
            case 'out':
                $amount = $route_fare;
                break;
            case 'both':
                $amount = $route_fare * 2;
                break;
            case 'none':
                $amount = 0;
                break;
        }
        
        if ($amount <= 0 && $transport_direction != 'none') {
            echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_transport_fare')]);
            return;
        }
        
        // Apply transport discount if exists
        $discount_info = $this->Discount_model->get_daily_fee_discount(
            $student_id, 
            $running_year, 
            $running_term, 
            $enroll->class_id, 
            'transport'
        );
        
        if ($discount_info['has_discount'] && $amount > 0) {
            if ($discount_info['discount_type'] == 'percentage') {
                $discount_amount = $amount * ($discount_info['discount_value'] / 100);
            } else {
                $discount_amount = min($discount_info['discount_value'], $amount);
            }
            $amount = max(0, $amount - $discount_amount);
        }
        
        $payment_data = [
            'student_id' => $student_id,
            'payment_date' => $payment_date,
            'feeding_amount' => 0,
            'breakfast_amount' => 0,
            'classes_amount' => 0,
            'water_amount' => 0,
            'transport_amount' => $amount,
            'payment_method' => $payment_method,
            'collected_by' => $conductor_id,
            'collection_point' => 'bus',
            'payment_type' => 'current',
            'modified_by' => $conductor_id,
            'notes' => 'Transport: ' . strtoupper($transport_direction)
        ];
        
        // ENTERPRISE: Check if payment already exists for this date (PREVENT DUPLICATES)
        $existing_payment = $this->db->get_where('daily_fee_transactions', [
            'student_id' => $student_id,
            'payment_date' => $payment_date
        ])->row();
        
        if($existing_payment) {
            // UPDATE existing payment (editing mode)
            // Only update transport amount, keep other fees unchanged
            $payment_data['feeding_amount'] = $existing_payment->feeding_amount;
            $payment_data['breakfast_amount'] = $existing_payment->breakfast_amount;
            $payment_data['classes_amount'] = $existing_payment->classes_amount;
            $payment_data['water_amount'] = $existing_payment->water_amount;
            
            $result = $this->Daily_fee_model->update_payment($existing_payment->transaction_id, $payment_data);
            $message = get_phrase('payment_updated_successfully') . ' - ' . strtoupper($transport_direction);
        } else {
            // CREATE new payment
            $result = $this->Daily_fee_model->process_payment($payment_data);
            $message = get_phrase('payment_received_successfully') . ' - ' . strtoupper($transport_direction);
        }
        
        echo json_encode([
            'status' => $result['status'],
            'message' => $message,
            'amount' => $amount,
            'direction' => $transport_direction,
            'transaction_code' => $result['transaction_code'] ?? $result['transaction_id'] ?? null,
            'mode' => $existing_payment ? 'updated' : 'created'
        ]);
    }

    /**
     * View bus attendance report
     */
    public function bus_attendance_report() {
        $page_data['page_name'] = 'bus_attendance_report';
        $page_data['page_title'] = get_phrase('bus_attendance_report');
        $this->load->view('backend/main', $page_data);
    }
}

    /**
     * Daily Fees Discount Profiles Management (Restricted to daily_fees category only)
     */
    public function discount_profiles($param1 = '') {
        // Only allow daily_fees category for conductors
        $allowed_category = 'daily_fees';
        
        if($param1 == 'stats') {
            $total = $this->db->where('discount_category', $allowed_category)->count_all_results('discount_profiles');
            $active = $this->db->where('discount_category', $allowed_category)->where('is_active', 1)->count_all_results('discount_profiles');
            $inactive = $this->db->where('discount_category', $allowed_category)->where('is_active', 0)->count_all_results('discount_profiles');
            
            echo json_encode([
                'status' => 'success', 
                'data' => [
                    'total' => $total, 
                    'active' => $active, 
                    'inactive' => $inactive, 
                    'invoice' => 0,  // Always 0 for conductors
                    'daily_fees' => $total
                ]
            ]);
            return;
        }
        
        if($this->input->get('ajax')) {
            $profiles = $this->db->where('discount_category', $allowed_category)->get('discount_profiles')->result_array();
            echo json_encode(['status' => 'success', 'profiles' => $profiles]);
            return;
        }
        
        if($param1 == 'get_data') {
            $profile_id = $this->input->get('profile_id');
            $profile = $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->get('discount_profiles')->row_array();
            echo json_encode($profile);
            return;
        }
        
        if($param1 == 'create') {
            $discount_types = $this->input->post('discount_type');
            
            if (is_array($discount_types)) {
                $discount_types = array_filter($discount_types);
                $discount_type_str = implode(',', $discount_types);
            } else {
                $discount_type_str = trim($discount_types);
            }
            
            $data = [
                'profile_name' => $this->input->post('profile_name'),
                'discount_category' => $allowed_category,  // Force daily_fees
                'discount_method' => $this->input->post('discount_method') ?: 'percentage',
                'discount_value' => $this->input->post('discount_value') ?: 0,
                'description' => $this->input->post('description'),
                'is_active' => 1,
                'created_by' => $this->session->userdata('admin_id'),
                'discount_type' => $discount_type_str ?: NULL,
                'bill_item_ids' => NULL
            ];
            
            try {
                $this->db->insert('discount_profiles', $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('profile_created_successfully')]);
            } catch (Exception $e) {
                $error = $this->db->error();
                if($error['code'] == 1062) {
                    echo json_encode(['status' => 'error', 'message' => 'A profile with this name already exists.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $error['message']]);
                }
            }
            return;
        }
        
        if($param1 == 'update') {
            $profile_id = $this->input->post('profile_id');
            $discount_types = $this->input->post('discount_type');
            
            if (is_array($discount_types)) {
                $discount_types = array_filter($discount_types);
                $discount_type_str = implode(',', $discount_types);
            } else {
                $discount_type_str = trim($discount_types);
            }
            
            $data = [
                'profile_name' => $this->input->post('profile_name'),
                'discount_method' => $this->input->post('discount_method') ?: 'percentage',
                'discount_value' => $this->input->post('discount_value') ?: 0,
                'description' => $this->input->post('description'),
                'discount_type' => $discount_type_str ?: NULL
            ];
            
            try {
                $this->db->where('profile_id', $profile_id)
                         ->where('discount_category', $allowed_category)
                         ->update('discount_profiles', $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('profile_updated_successfully')]);
            } catch (Exception $e) {
                $error = $this->db->error();
                if($error['code'] == 1062) {
                    echo json_encode(['status' => 'error', 'message' => 'A profile with this name already exists.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $error['message']]);
                }
            }
            return;
        }
        
        if($param1 == 'delete') {
            $profile_id = $this->input->post('profile_id');
            $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->delete('discount_profiles');
            echo json_encode(['status' => 'success', 'message' => get_phrase('profile_deleted_successfully')]);
            return;
        }
        
        if($param1 == 'toggle_status') {
            $profile_id = $this->input->post('profile_id');
            $current = $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->get('discount_profiles')->row();
            if($current) {
                $new_status = $current->is_active ? 0 : 1;
                $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->update('discount_profiles', ['is_active' => $new_status]);
                echo json_encode(['status' => 'success', 'message' => $new_status ? get_phrase('profile_activated') : get_phrase('profile_deactivated')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Profile not found']);
            }
            return;
        }
        
        // Load view with daily_fees profiles only
        $page_data['profiles'] = $this->db->where('discount_category', $allowed_category)->get('discount_profiles')->result_array();
        $page_data['restricted_mode'] = true;  // Flag to hide category dropdown
        $page_data['allowed_category'] = $allowed_category;
        $page_data['page_name'] = 'discount_profiles';
        $page_data['page_title'] = get_phrase('daily_fees_discount_profiles');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
}
