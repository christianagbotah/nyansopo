<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Portfolio_enterprise extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        $base_path = APPPATH . 'modules/AcademicAssessmentEngine/';
        require_once $base_path . 'models/Portfolio_model.php';
        require_once $base_path . 'services/Portfolio_computation_service.php';
        require_once $base_path . 'middleware/Portfolio_permission_middleware.php';
        
        $this->portfolio_model = new Portfolio_model();
        $this->portfolio_computation_service = new Portfolio_computation_service();
        $this->portfolio_permission_middleware = new Portfolio_permission_middleware();
        
        $login_type = $this->session->userdata('login_type');
        if($login_type != 'admin' && $login_type != 'teacher') {
            redirect(site_url('login'));
        }
    }
    
    public function index() {
        $page_data['page_name'] = 'portfolio_assessment/dashboard';
        $page_data['page_title'] = 'Portfolio Assessment (Enterprise)';
        $this->load->view('backend/index', $page_data);
    }
    
    public function manage() {
        $class_id = $this->input->get('class_id');
        $subject_id = $this->input->get('subject_id');
        $week = $this->input->get('week');
        $year = $this->input->get('year') ?? $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        
        // Get class category
        $class = $this->db->get_where('class', ['class_id' => $class_id])->row();
        $category = $class->category ?? '';
        
        // Determine term/semester based on category
        if($category == 'JHS') { // JHS uses semester
            $term_sem = $this->db->get_where('settings', ['type' => 'running_sem'])->row()->description;
            $term_type = 'semester';
        } else {
            $term_sem = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
            $term_type = 'term';
        }
        
        $page_data['class_id'] = $class_id;
        $page_data['subject_id'] = $subject_id;
        $page_data['week'] = $week;
        $page_data['year'] = $year;
        $page_data['term_sem'] = $term_sem;
        $page_data['term_type'] = $term_type;
        $page_data['category'] = $category;
        $page_data['page_name'] = 'portfolio_assessment/manage';
        $page_data['page_title'] = 'Manage Portfolio';
        $this->load->view('backend/index', $page_data);
    }
    
    public function create_header() {
        $data = [
            'class_id' => $this->input->post('class_id'),
            'subject_id' => $this->input->post('subject_id'),
            'teacher_id' => $this->session->userdata('login_user_id'),
            'year' => $this->input->post('year'),
            'term' => $this->input->post('term'),
            'semester' => $this->input->post('semester'),
            'week_number' => $this->input->post('week_number'),
            'strand_topic' => $this->input->post('strand_topic'),
            'assessment_date' => $this->input->post('assessment_date'),
            'max_score' => $this->input->post('max_score') ?? 10,
            'status' => 'draft',
            'created_by' => $this->session->userdata('login_user_id')
        ];
        
        $header_id = $this->portfolio_model->create_header($data);
        echo json_encode(['status' => 'success', 'header_id' => $header_id]);
    }
    
    public function save_scores() {
        $header_id = $this->input->post('header_id');
        $scores = $this->input->post('scores');
        
        $this->db->trans_start();
        
        $this->portfolio_model->save_scores($header_id, $scores);
        
        // Get header details
        $header = $this->db->get_where('portfolio_headers', ['header_id' => $header_id])->row();
        
        // Compute and sync for each student
        $students = array_unique(array_column($scores, 'student_id'));
        foreach($students as $student_id) {
            $this->portfolio_computation_service->compute_term_average(
                $student_id, $header->subject_id, $header->class_id, 
                $header->year, $header->term, $header->semester
            );
            
            $this->portfolio_computation_service->sync_to_sba(
                $student_id, $header->subject_id, $header->class_id, 
                $header->year, $header->term, $header->semester
            );
        }
        
        $this->db->trans_complete();
        
        echo json_encode(['status' => 'success', 'message' => 'Scores saved and SBA updated']);
    }
    
    public function get_students() {
        $class_id = $this->input->get('class_id');
        $year = $this->input->get('year');
        
        $students = $this->db->select('s.student_id, s.name, s.student_code')
            ->from('enroll e')
            ->join('student s', 's.student_id = e.student_id')
            ->where('e.class_id', $class_id)
            ->where('e.year', $year)
            ->order_by('s.name', 'ASC')
            ->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $students]);
    }
    
    public function sba_management() {
        $page_data['page_name'] = 'portfolio_assessment/sba_management';
        $page_data['page_title'] = 'SBA Management';
        $this->load->view('backend/index', $page_data);
    }
    
    public function get_sba_data() {
        $class_id = $this->input->get('class_id');
        $subject_id = $this->input->get('subject_id');
        $year = $this->input->get('year');
        $term = $this->input->get('term');
        
        $data = $this->db->select('sba.*, s.name as student_name, pa.term_average')
            ->from('sba_components sba')
            ->join('student s', 's.student_id = sba.student_id')
            ->join('portfolio_aggregates pa', 'pa.student_id = sba.student_id AND pa.subject_id = sba.subject_id AND pa.year = sba.year AND pa.term = sba.term', 'left')
            ->where('sba.class_id', $class_id)
            ->where('sba.subject_id', $subject_id)
            ->where('sba.year', $year)
            ->where('sba.term', $term)
            ->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    }
    
    public function batch_sync_sba() {
        $class_id = $this->input->post('class_id');
        $subject_id = $this->input->post('subject_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        
        $class = $this->db->get_where('class', ['class_id' => $class_id])->row();
        $semester = ($class->category == 'JHS') ? $this->db->get_where('settings', ['type' => 'running_sem'])->row()->description : null;
        
        $results = $this->portfolio_computation_service->batch_sync_class($class_id, $subject_id, $year, $term, $semester);
        
        echo json_encode(['status' => 'success', 'message' => 'Batch sync completed', 'results' => $results]);
    }
}
