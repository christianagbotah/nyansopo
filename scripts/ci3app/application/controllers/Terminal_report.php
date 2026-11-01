<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Terminal Report Builder
 * GES-compliant end-term report generation with graphical presentation
 */
class Terminal_report extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        
        if($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'));
        }
    }
    
    public function index() {
        $page_data['page_name'] = 'terminal_report_builder';
        $page_data['page_title'] = 'Terminal Report Builder';
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Get student report data
     */
    public function get_student_report() {
        $student_id = $this->input->post('student_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        
        $student = $this->db->select('s.*, c.name as class_name')
            ->from('student s')
            ->join('class c', 'c.class_id = s.class_id')
            ->where('s.student_id', $student_id)
            ->get()->row_array();
        
        if(!$student) {
            echo json_encode(['status' => 'error', 'message' => 'Student not found']);
            return;
        }
        
        // Get subjects with scores
        $subjects = $this->db->select('
            s.name as subject_name,
            pa.term_average as portfolio_score,
            sba.total_sba as sba_score,
            em.total_score as exam_score,
            (COALESCE(pa.term_average, 0) + COALESCE(sba.total_sba, 0) + COALESCE(em.total_score, 0)) as total_score
        ')
        ->from('class_subject cs')
        ->join('subject s', 's.subject_id = cs.subject_id')
        ->join('portfolio_aggregates pa', 'pa.subject_id = cs.subject_id AND pa.student_id = ' . $student_id, 'left')
        ->join('sba_components sba', 'sba.subject_id = cs.subject_id AND sba.student_id = ' . $student_id, 'left')
        ->join('exam_marks em', 'em.subject_id = cs.subject_id AND em.student_id = ' . $student_id, 'left')
        ->where('cs.class_id', $student['class_id'])
        ->get()->result_array();
        
        // Get attendance
        $attendance = $this->db->select('
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_days
        ')
        ->from('attendance')
        ->where(['student_id' => $student_id, 'year' => $year, 'term' => $term])
        ->get()->row_array();
        
        echo json_encode([
            'status' => 'success',
            'student' => $student,
            'subjects' => $subjects,
            'attendance' => $attendance
        ]);
    }
    
    /**
     * Save report remarks and generate
     */
    public function generate_report() {
        $data = [
            'student_id' => $this->input->post('student_id'),
            'year' => $this->input->post('year'),
            'term' => $this->input->post('term'),
            'attendance_present' => $this->input->post('attendance_present'),
            'attendance_total' => $this->input->post('attendance_total'),
            'conduct' => $this->input->post('conduct'),
            'attitude' => $this->input->post('attitude'),
            'interest' => $this->input->post('interest'),
            'teacher_remark' => $this->input->post('teacher_remark'),
            'headmaster_remark' => $this->input->post('headmaster_remark'),
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->replace('terminal_reports', $data);
        
        echo json_encode(['status' => 'success', 'message' => 'Report generated successfully']);
    }
    
    /**
     * Print report
     */
    public function print_report($student_id, $year, $term) {
        $page_data['student_id'] = $student_id;
        $page_data['year'] = $year;
        $page_data['term'] = $term;
        $this->load->view('backend/admin/terminal_report_print_enhanced', $page_data);
    }
    
    /**
     * Print student bill
     */
    public function print_bill($student_id, $year, $term) {
        $page_data['student_id'] = $student_id;
        $page_data['year'] = $year;
        $page_data['term'] = $term;
        $this->load->view('backend/admin/student_bill_print', $page_data);
    }
    
    /**
     * Bulk print reports for entire class
     */
    public function bulk_print() {
        $class_id = $this->input->get('class_id');
        $year = $this->input->get('year');
        $term = $this->input->get('term');
        
        $page_data['class_id'] = $class_id;
        $page_data['year'] = $year;
        $page_data['term'] = $term;
        $this->load->view('backend/admin/terminal_report_bulk_print', $page_data);
    }
    
    /**
     * Get student financial statement (smart arrears calculation)
     */
    public function get_student_bill() {
        $student_id = $this->input->post('student_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        
        // Get latest invoice for current year/term (next term bill)
        $latest_invoice = $this->db->select('*')
            ->from('invoice')
            ->where('student_id', $student_id)
            ->where('year', $year)
            ->where('term', $term)
            ->order_by('creation_timestamp', 'DESC')
            ->limit(1)
            ->get()->row();
        
        if(!$latest_invoice) {
            echo json_encode(['status' => 'error', 'message' => 'No invoice found for this term']);
            return;
        }
        
        // Get all payments
        $total_paid = $this->db->select('SUM(amount) as total')
            ->from('payment')
            ->where('student_id', $student_id)
            ->get()->row()->total ?? 0;
        
        // Get all invoices BEFORE current year/term (old arrears)
        $old_invoices_total = $this->db->select('SUM(amount) as total')
            ->from('invoice')
            ->where('student_id', $student_id)
            ->where('(year < "' . $year . '" OR (year = "' . $year . '" AND term < "' . $term . '"))')
            ->get()->row()->total ?? 0;
        
        // Calculate arrears (old bills - payments)
        $arrears = max(0, $old_invoices_total - $total_paid);
        
        // Current term bill
        $current_bill = $latest_invoice->amount;
        
        // Total owing
        $total_owing = $arrears + $current_bill;
        
        echo json_encode([
            'status' => 'success',
            'arrears' => $arrears,
            'current_bill' => $current_bill,
            'total_owing' => $total_owing,
            'total_paid' => $total_paid,
            'latest_invoice' => $latest_invoice
        ]);
    }
}
