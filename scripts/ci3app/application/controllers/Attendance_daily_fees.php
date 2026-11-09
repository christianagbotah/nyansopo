<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Integrated Attendance and Daily Fees Controller
 * Handles attendance marking with automatic daily fee charging
 */
class Attendance_daily_fees extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Check if user is logged in
        if ($this->session->userdata('admin_id') == '') {
            redirect(site_url('login'), 'refresh');
        }
        
        // All logged-in users can mark attendance, but only authorized users can collect fees
        $this->can_collect_fees = $this->Daily_fee_model->can_collect_daily_fees();
        
        // Conductors cannot mark classroom attendance
        if ($this->session->userdata('login_type') == 'conductor') {
            $this->can_mark_attendance = false;
        } else {
            $this->can_mark_attendance = true;
        }
    }

    /**
     * Mark attendance with integrated daily fee charging
     * Called by teachers from classroom
     */
    public function mark_attendance_with_fees() {
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        $sem = $this->input->post('sem');
        $attendance_date = strtotime($this->input->post('attendance_date'));
        $students_present = $this->input->post('students_present'); // Array of student IDs
        $breakfast_opted = $this->input->post('breakfast_opted'); // Array of student IDs who took breakfast
        
        if (!is_array($students_present)) $students_present = [];
        if (!is_array($breakfast_opted)) $breakfast_opted = [];
        
        $marked_by = $this->session->userdata('admin_id');
        $marked_by_role = $this->session->userdata('login_type');
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($students_present as $student_id) {
            // Check if attendance already marked for this date
            $existing = $this->db->get_where('attendance', [
                'student_id' => $student_id,
                'class_id' => $class_id,
                'section_id' => $section_id,
                'timestamp' => $attendance_date,
                'year' => $year,
                'term' => $term,
                'sem' => $sem
            ])->row();
            
            if ($existing) {
                // Update existing attendance
                $attendance_id = $existing->attendance_id;
                $this->db->where('attendance_id', $attendance_id);
                $this->db->update('attendance', [
                    'status' => 1, // Present
                    'breakfast_opted' => in_array($student_id, $breakfast_opted) ? 1 : 0,
                    'marked_by' => $marked_by,
                    'marked_by_role' => $marked_by_role
                ]);
            } else {
                // Insert new attendance record
                $attendance_data = [
                    'student_id' => $student_id,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'year' => $year,
                    'term' => $term,
                    'sem' => $sem,
                    'timestamp' => $attendance_date,
                    'status' => 1, // Present
                    'breakfast_opted' => in_array($student_id, $breakfast_opted) ? 1 : 0,
                    'marked_by' => $marked_by,
                    'marked_by_role' => $marked_by_role
                ];
                
                $this->db->insert('attendance', $attendance_data);
                $attendance_id = $this->db->insert_id();
            }
            
            // Process daily charges (auto-deduct from wallet or add to arrears)
            $charges = $this->Daily_fee_model->process_daily_charges(
                $student_id,
                $attendance_date,
                $class_id,
                $year,
                $term,
                $sem,
                [
                    'breakfast_opted' => in_array($student_id, $breakfast_opted),
                    'transport_status' => 'none' // Will be updated by bus conductor
                ]
            );
            
            // Update attendance record with charges
            $this->db->where('attendance_id', $attendance_id);
            $this->db->update('attendance', [
                'feeding_charged' => $charges['feeding_charged'],
                'breakfast_charged' => $charges['breakfast_charged'],
                'classes_charged' => $charges['classes_charged'],
                'water_charged' => $charges['water_charged'],
                'payment_status' => 'unpaid' // Will be updated when payment is made
            ]);
            
            $success_count++;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('attendance_marked_successfully') . ". $success_count " . get_phrase('students_marked'),
            'success_count' => $success_count
        ]);
    }

    /**
     * Get students for attendance marking with their wallet status
     */
    public function get_students_for_attendance() {
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        $sem = $this->input->post('sem');
        $attendance_date = strtotime($this->input->post('attendance_date'));
        
        // Get enrolled students
        $this->db->select('s.student_id, s.name, s.student_code, s.photo');
        $this->db->from('student s');
        $this->db->join('enroll e', 's.student_id = e.student_id');
        $this->db->where('e.class_id', $class_id);
        $this->db->where('e.section_id', $section_id);
        $this->db->where('e.year', $year);
        if ($term) $this->db->where('e.term', $term);
        if ($sem) $this->db->where('e.sem', $sem);
        $this->db->where('e.mute', 0);
        $this->db->order_by('s.name', 'ASC');
        
        $students = $this->db->get()->result_array();
        
        // Enrich with wallet info and attendance status
        foreach ($students as &$student) {
            // Get wallet status
            $outstanding = $this->Daily_fee_model->get_student_outstanding($student['student_id']);
            $student['total_balance'] = $outstanding['total_balance'];
            $student['total_arrears'] = $outstanding['total_arrears'];
            $student['net_position'] = $outstanding['net_position'];
            
            // Get preferences
            $prefs = $this->Daily_fee_model->get_student_preferences($student['student_id']);
            $student['breakfast_subscribed'] = $prefs['breakfast_subscribed'];
            
            // Check if already marked present today
            $attendance = $this->db->get_where('attendance', [
                'student_id' => $student['student_id'],
                'timestamp' => $attendance_date,
                'class_id' => $class_id,
                'section_id' => $section_id
            ])->row();
            
            $student['already_marked'] = $attendance ? true : false;
            $student['attendance_status'] = $attendance ? $attendance->status : 0;
            $student['breakfast_opted'] = $attendance ? $attendance->breakfast_opted : 0;
        }
        
        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    }

    /**
     * Get daily fee rates for a class (for display purposes)
     */
    public function get_class_daily_rates() {
        $class_id = $this->input->post('class_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        $sem = $this->input->post('sem');
        
        $rates = $this->Daily_fee_model->get_class_rates($class_id, $year, $term, $sem);
        
        echo json_encode([
            'status' => 'success',
            'rates' => $rates
        ]);
    }

    /**
     * View attendance register with daily fee tracking
     */
    public function view_attendance_register() {
        $class_id = $this->input->get('class_id');
        $section_id = $this->input->get('section_id');
        $year = $this->input->get('year');
        $term = $this->input->get('term');
        $sem = $this->input->get('sem');
        $month = $this->input->get('month') ?? date('m');
        
        $page_data['class_id'] = $class_id;
        $page_data['section_id'] = $section_id;
        $page_data['year'] = $year;
        $page_data['term'] = $term;
        $page_data['sem'] = $sem;
        $page_data['month'] = $month;
        $page_data['page_name'] = 'attendance_register_with_fees';
        $page_data['page_title'] = get_phrase('attendance_register_with_daily_fees');
        
        $this->load->view('backend/index', $page_data);
    }
}
