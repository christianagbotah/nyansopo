<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Transport Choice Controller
 * Handles parent/admin daily transport direction choices (IN/OUT/BOTH/NONE)
 */
class Daily_transport extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        
        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    /**
     * Set transport choice AND collect payment (done in morning by teacher/cashier/conductor)
     * This replaces set_choice - payment is collected immediately when choice is made
     */
    function set_choice_and_collect() {
        $user_id = $this->session->userdata('login_user_id');
        $user_type = $this->session->userdata('login_type');
        
        // Check if user can collect fees
        $can_collect = false;
        $collection_point = 'office';
        
        if ($user_type == 'admin') {
            $user = $this->db->get_where('admin', ['admin_id' => $user_id])->row();
            $can_collect = $user && $user->can_collect_daily_fees == 1;
            $collection_point = $user->collection_point ?? 'office';
        } elseif ($user_type == 'teacher') {
            $user = $this->db->get_where('teacher', ['teacher_id' => $user_id])->row();
            $can_collect = $user && $user->can_collect_daily_fees == 1;
            $collection_point = $user->collection_point ?? 'classroom';
        }
        
        if (!$can_collect) {
            echo json_encode(['status' => 'error', 'message' => 'No permission to collect fees']);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $choice_date = strtotime($this->input->post('choice_date') ?: date('Y-m-d'));
        $direction = $this->input->post('transport_direction'); // none, in, out, both
        $notes = $this->input->post('notes');
        
        // Validate direction
        if (!in_array($direction, ['none', 'in', 'out', 'both'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid transport direction']);
            return;
        }
        
        // Get student's route
        $student = $this->db->get_where('student', ['student_id' => $student_id])->row();
        if (!$student || !$student->transport_id) {
            echo json_encode(['status' => 'error', 'message' => 'Student not assigned to any route']);
            return;
        }
        
        // Get route fare
        $route = $this->db->get_where('transport_routes', ['route_id' => $student->transport_id])->row();
        if (!$route) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid route']);
            return;
        }
        
        // Calculate fare based on direction
        $in_fare = 0;
        $out_fare = 0;
        $total_fare = 0;
        
        if ($direction == 'in') {
            $in_fare = $route->route_fare;
            $total_fare = $in_fare;
        } elseif ($direction == 'out') {
            $out_fare = $route->route_fare;
            $total_fare = $out_fare;
        } elseif ($direction == 'both') {
            $in_fare = $route->route_fare;
            $out_fare = $route->route_fare;
            $total_fare = $in_fare + $out_fare;
        }
        
        // Check if bus attendance already exists for this date
        $existing = $this->db->get_where('bus_attendance', [
            'student_id' => $student_id,
            'attendance_date' => $choice_date
        ])->row();
        
        $bus_data = [
            'transport_direction' => $direction,
            'in_fare' => $in_fare,
            'out_fare' => $out_fare,
            'total_fare' => $total_fare,
            'payment_status' => $total_fare > 0 ? 'paid' : 'unpaid',
            'paid_amount' => $total_fare,
            'payment_time' => time(),
            'collected_by' => $user_id,
            'collected_by_role' => $user_type,
            'collection_point' => $collection_point
        ];
        
        if ($existing) {
            // Update existing record
            $this->db->where('id', $existing->id);
            $this->db->update('bus_attendance', $bus_data);
        } else {
            // Create new record
            $bus_data['student_id'] = $student_id;
            $bus_data['route_id'] = $student->transport_id;
            $bus_data['attendance_date'] = $choice_date;
            $bus_data['year'] = get_settings('running_year');
            $bus_data['term'] = get_settings('running_term');
            $bus_data['created_at'] = time();
            $this->db->insert('bus_attendance', $bus_data);
        }
        
        // Also save to daily_transport_choices for tracking
        $choice_data = [
            'student_id' => $student_id,
            'choice_date' => $choice_date,
            'transport_direction' => $direction,
            'choice_made_at' => time(),
            'choice_made_by' => $user_type,
            'notes' => $notes
        ];
        
        $choice_exists = $this->db->get_where('daily_transport_choices', [
            'student_id' => $student_id,
            'choice_date' => $choice_date
        ])->row();
        
        if ($choice_exists) {
            $this->db->where('id', $choice_exists->id);
            $this->db->update('daily_transport_choices', $choice_data);
        } else {
            $this->db->insert('daily_transport_choices', $choice_data);
        }
        
        $message = $direction == 'none' ? 
            get_phrase('transport_not_used_today') : 
            get_phrase('transport_payment_collected') . ' GHS ' . number_format($total_fare, 2);
        
        echo json_encode(['status' => 'success', 'message' => $message, 'amount' => $total_fare]);
    }
    
    /**
     * Get transport choice for a student for a specific date
     */
    function get_choice($student_id, $date) {
        $choice_date = strtotime($date);
        $choice = $this->db->get_where('daily_transport_choices', [
            'student_id' => $student_id,
            'choice_date' => $choice_date
        ])->row();
        
        if ($choice) {
            echo json_encode(['status' => 'success', 'direction' => $choice->transport_direction]);
        } else {
            echo json_encode(['status' => 'success', 'direction' => 'none']);
        }
    }
    
    /**
     * Mark bus boarding (for conductors)
     */
    function mark_boarding() {
        $user_type = $this->session->userdata('login_type');
        $user_id = $this->session->userdata('login_user_id');
        
        // Check if user is conductor
        if ($user_type == 'admin') {
            $user = $this->db->get_where('admin', ['admin_id' => $user_id])->row();
            if (!$user || $user->level != 5) {
                echo json_encode(['status' => 'error', 'message' => 'Only conductors can mark boarding']);
                return;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $direction = $this->input->post('direction'); // 'in' or 'out'
        $route_id = $this->input->post('route_id');
        $date = strtotime(date('Y-m-d'));
        
        // Get route fare
        $route = $this->db->get_where('transport_routes', ['route_id' => $route_id])->row();
        if (!$route) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid route']);
            return;
        }
        
        // Get or create bus attendance record
        $attendance = $this->db->get_where('bus_attendance', [
            'student_id' => $student_id,
            'attendance_date' => $date
        ])->row();
        
        if ($attendance) {
            // Update existing record
            $update = [];
            if ($direction == 'in') {
                $update['boarded_in'] = 1;
                $update['in_time'] = date('H:i');
                if ($attendance->in_fare == 0) {
                    $update['in_fare'] = $route->route_fare;
                }
            } else {
                // AFTERNOON OUT - Check if already paid
                $update['boarded_out'] = 1;
                $update['out_time'] = date('H:i');
                
                // If OUT fare not paid, bill as owing
                if ($attendance->out_fare == 0) {
                    $update['out_fare'] = $route->route_fare;
                    // Mark as unpaid since they didn't pay in morning
                    $update['payment_status'] = 'unpaid';
                }
            }
            
            // Calculate total fare
            $in_fare = $direction == 'in' ? ($update['in_fare'] ?? $attendance->in_fare) : $attendance->in_fare;
            $out_fare = $direction == 'out' ? ($update['out_fare'] ?? $attendance->out_fare) : $attendance->out_fare;
            $update['total_fare'] = $in_fare + $out_fare;
            
            // Determine transport direction based on actual boarding
            $boarded_in = $direction == 'in' ? 1 : $attendance->boarded_in;
            $boarded_out = $direction == 'out' ? 1 : $attendance->boarded_out;
            if ($boarded_in && $boarded_out) {
                $update['transport_direction'] = 'both';
            } elseif ($boarded_in) {
                $update['transport_direction'] = 'in';
            } elseif ($boarded_out) {
                $update['transport_direction'] = 'out';
            }
            
            $update['conductor_id'] = $user_id;
            
            $this->db->where('id', $attendance->id);
            $this->db->update('bus_attendance', $update);
            
            // If OUT boarding without payment, add to transport arrears
            if ($direction == 'out' && $attendance->out_fare == 0) {
                $this->add_transport_arrears($student_id, $route->route_fare, $date);
            }
        } else {
            // Create new record
            $data = [
                'student_id' => $student_id,
                'route_id' => $route_id,
                'attendance_date' => $date,
                'transport_direction' => $direction,
                'boarded_in' => $direction == 'in' ? 1 : 0,
                'boarded_out' => $direction == 'out' ? 1 : 0,
                'in_time' => $direction == 'in' ? date('H:i') : null,
                'out_time' => $direction == 'out' ? date('H:i') : null,
                'in_fare' => $direction == 'in' ? $route->route_fare : 0,
                'out_fare' => $direction == 'out' ? $route->route_fare : 0,
                'total_fare' => $route->route_fare,
                'payment_status' => 'unpaid',
                'conductor_id' => $user_id,
                'year' => get_settings('running_year'),
                'term' => get_settings('running_term'),
                'created_at' => time()
            ];
            
            $this->db->insert('bus_attendance', $data);
        }
        
        echo json_encode(['status' => 'success', 'message' => get_phrase('boarding_marked_successfully')]);
    }
    
    /**
     * Add transport arrears when student boards without payment
     */
    private function add_transport_arrears($student_id, $amount, $date) {
        // Update or create wallet record
        $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
        
        if ($wallet) {
            $this->db->where('student_id', $student_id);
            $this->db->set('transport_arrears', 'transport_arrears + ' . $amount, FALSE);
            $this->db->set('last_updated', time());
            $this->db->update('daily_fee_wallet');
        } else {
            // Use INSERT IGNORE to handle duplicate student_id gracefully
            $this->db->query("
                INSERT IGNORE INTO daily_fee_wallet (student_id, transport_arrears, last_updated)
                VALUES (?, ?, ?)
            ", [$student_id, $amount, time()]);
            
            // If insert was ignored (duplicate), update instead
            if ($this->db->affected_rows() == 0) {
                $this->db->where('student_id', $student_id);
                $this->db->set('transport_arrears', 'transport_arrears + ' . $amount, FALSE);
                $this->db->set('last_updated', time());
                $this->db->update('daily_fee_wallet');
            }
        }
        
        // Log the arrears
        $this->db->insert('daily_fee_audit_log', [
            'student_id' => $student_id,
            'action_type' => 'charge',
            'fee_type' => 'transport',
            'amount' => $amount,
            'arrears_before' => $wallet ? $wallet->transport_arrears : 0,
            'arrears_after' => ($wallet ? $wallet->transport_arrears : 0) + $amount,
            'performed_by' => $this->session->userdata('login_user_id'),
            'performed_by_role' => 'conductor',
            'notes' => 'Boarded OUT without morning payment on ' . date('Y-m-d', $date),
            'created_at' => time()
        ]);
    }
    
    /**
     * Get students for a route with payment status (for conductor's boarding list)
     */
    function get_route_students_with_payment($route_id, $direction) {
        $today = strtotime(date('Y-m-d'));
        
        $students = $this->db->query("
            SELECT s.student_id, s.name, s.student_code, 
                   e.class_id, c.name as class_name,
                   ba.transport_direction, ba.in_fare, ba.out_fare, 
                   ba.payment_status, ba.paid_amount
            FROM student s
            JOIN enroll e ON s.student_id = e.student_id
            JOIN class c ON e.class_id = c.class_id
            LEFT JOIN bus_attendance ba ON s.student_id = ba.student_id 
                AND ba.attendance_date = ?
            WHERE s.transport_id = ?
            AND e.year = ?
            AND e.term = ?
            AND e.mute = 0
            ORDER BY s.name
        ", [$today, $route_id, get_settings('running_year'), get_settings('running_term')])->result_array();
        
        // Add payment status flags
        foreach ($students as &$student) {
            $student['in_paid'] = $student['in_fare'] > 0;
            $student['out_paid'] = $student['out_fare'] > 0;
            $student['both_paid'] = $student['transport_direction'] == 'both' && $student['paid_amount'] > 0;
        }
        
        echo json_encode(['status' => 'success', 'students' => $students]);
    }
}
