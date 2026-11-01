<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Attendance_enterprise extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        $this->load->model('Attendance_enterprise_model');
    }
    
    // Dashboard
    function index() {
        $login_type = $this->session->userdata('login_type');
        if($login_type != 'admin' && $login_type != 'teacher')
            redirect(site_url('login'));
        
        $page_data['page_name'] = 'attendance/dashboard';
        $page_data['page_title'] = get_phrase('attendance_enterprise');
        $this->load->view('backend/index', $page_data);
    }
    
    // Mark attendance interface
    function mark() {
        $login_type = $this->session->userdata('login_type');
        if($login_type != 'admin' && $login_type != 'teacher')
            redirect(site_url('login'));
        
        $page_data['page_name'] = 'attendance/mark_attendance';
        $page_data['page_title'] = get_phrase('mark_attendance');
        $this->load->view('backend/index', $page_data);
    }
    
    // Get dashboard statistics (AJAX)
    function get_stats() {
        $date = $this->input->post('date') ?: date('Y-m-d');
        $stats = $this->Attendance_enterprise_model->get_daily_stats($date);
        echo json_encode(['status' => 'success', 'data' => $stats]);
    }
    
    // Get teacher's assigned classes (AJAX)
    function get_teacher_classes() {
        $teacher_id = $this->session->userdata('teacher_id');
        $classes = $this->Attendance_enterprise_model->get_teacher_assigned_classes($teacher_id);
        echo json_encode(['status' => 'success', 'data' => $classes]);
    }
    
    // Get student data for attendance marking (AJAX)
    function get_student_data() {
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $date = $this->input->post('date');
        
        $students = $this->Attendance_enterprise_model->get_class_students($class_id, $section_id);
        $attendance = $this->Attendance_enterprise_model->get_attendance_records($class_id, $section_id, $date);
        $fee_rates = $this->Attendance_enterprise_model->get_daily_fee_rates($class_id);
        
        echo json_encode([
            'status' => 'success',
            'students' => $students,
            'attendance' => $attendance,
            'fee_rates' => $fee_rates
        ]);
    }
    
    // Save attendance and fees (AJAX)
    function save() {
        $data = $this->input->post();
        $result = $this->Attendance_enterprise_model->save_attendance_and_fees($data);
        echo json_encode($result);
    }
    
    // Quick mark all present
    function quick_mark_present() {
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $date = $this->input->post('date');
        
        $result = $this->Attendance_enterprise_model->mark_all_present($class_id, $section_id, $date);
        echo json_encode($result);
    }
    
    // Barcode scanner
    function barcode_scanner() {
        $login_type = $this->session->userdata('login_type');
        if($login_type != 'admin' && $login_type != 'teacher')
            redirect(site_url('login'));
        
        $page_data['page_name'] = 'attendance/barcode_scanner';
        $page_data['page_title'] = get_phrase('barcode_scanner_attendance');
        $this->load->view('backend/index', $page_data);
    }
    
    // Get student by barcode (AJAX)
    function get_student_by_code() {
        $code = $this->input->post('code');
        $student = $this->db->get_where('student', ['student_code' => $code])->row_array();
        
        if($student) {
            echo json_encode(['status' => 'success', 'student' => $student]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        }
    }
}
