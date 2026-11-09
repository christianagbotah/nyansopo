<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Examination extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Examination_model');
        $this->load->library('Aggregate_calculator');
        
        $login_type = $this->session->userdata('login_type');
        if($login_type != 'admin' && $login_type != 'teacher') {
            redirect(site_url('login'));
        }
    }
    
    public function index() {
        $page_data['page_name'] = 'examination/dashboard';
        $page_data['page_title'] = 'Examination Management';
        $this->load->view('backend/index', $page_data);
    }
    
    public function get_dashboard_data() {
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        
        try {
            $stats = $this->Examination_model->get_dashboard_stats($running_year, $running_term);
            $recent_exams = $this->Examination_model->get_recent_exams($running_year, $running_term);
            
            // Get exam progress for each recent exam
            foreach($recent_exams as &$exam) {
                $progress_data = $this->Examination_model->get_exam_progress($exam['exam_id']);
                $exam['progress'] = $progress_data['progress'] ?? 0;
            }
            
            echo json_encode([
                'status' => 'success',
                'stats' => $stats,
                'recent_exams' => $recent_exams
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to load dashboard data: ' . $e->getMessage()
            ]);
        }
    }
    
    public function setup_exam() {
        $page_data['page_name'] = 'examination/setup_exam';
        $page_data['page_title'] = 'Setup Examination';
        $this->load->view('backend/index', $page_data);
    }
    
    public function create_exam() {
        $data = [
            'exam_name' => $this->input->post('exam_name'),
            'exam_type' => $this->input->post('exam_type'),
            'class_id' => $this->input->post('class_id'),
            'year' => $this->input->post('year'),
            'term' => $this->input->post('term'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'created_by' => $this->session->userdata('login_user_id')
        ];
        
        $exam_id = $this->Examination_model->create_exam($data);
        
        if($exam_id) {
            echo json_encode(['status' => 'success', 'message' => 'Exam created successfully', 'exam_id' => $exam_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create exam']);
        }
    }
    
    public function get_exams() {
        $class_id = $this->input->get('class_id');
        $year = $this->input->get('year');
        $term = $this->input->get('term');
        
        $exams = $this->Examination_model->get_exams($class_id, $year, $term);
        echo json_encode(['status' => 'success', 'data' => $exams]);
    }
    
    public function record_marks($exam_id = null, $subject_id = null) {
        $page_data['exam_id'] = $exam_id;
        $page_data['subject_id'] = $subject_id;
        $page_data['page_name'] = 'examination/record_marks';
        $page_data['page_title'] = 'Record Marks';
        $this->load->view('backend/index', $page_data);
    }
    
    public function get_exam_subjects() {
        $exam_id = $this->input->get('exam_id');
        $this->db->select('s.subject_id, s.name');
        $this->db->from('enterprise_exam_subjects es');
        $this->db->join('subject s', 's.subject_id = es.subject_id');
        $this->db->where('es.exam_id', $exam_id);
        $subjects = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $subjects]);
    }
    
    public function get_students_for_marks() {
        $exam_id = $this->input->get('exam_id');
        $subject_id = $this->input->get('subject_id');
        
        $exam = $this->Examination_model->get_exam($exam_id);
        
        $this->db->select('s.student_id, s.name, m.class_score, m.exam_score, m.total_score, m.grade, m.remark');
        $this->db->from('enroll e');
        $this->db->join('student s', 's.student_id = e.student_id');
        $this->db->join('enterprise_student_marks m', 'm.student_id = s.student_id AND m.exam_id = '.$exam_id.' AND m.subject_id = '.$subject_id, 'left');
        $this->db->where('e.class_id', $exam['class_id']);
        $this->db->where('e.year', $exam['year']);
        $this->db->order_by('s.name', 'ASC');
        $students = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $students]);
    }
    
    public function save_marks() {
        $result = $this->Examination_model->save_student_marks($this->input->post());
        echo json_encode($result);
    }
    
    public function broadsheet($exam_id = null) {
        if(!$exam_id) {
            // Redirect to exam selection or dashboard
            redirect(site_url('examination'));
            return;
        }
        $exam = $this->Examination_model->get_exam($exam_id);
        $exam['class_name'] = $this->db->get_where('class', ['class_id' => $exam['class_id']])->row()->name;
        
        // Get subjects
        $this->db->select('s.subject_id, s.name');
        $this->db->from('enterprise_exam_subjects es');
        $this->db->join('subject s', 's.subject_id = es.subject_id');
        $this->db->where('es.exam_id', $exam_id);
        $subjects = $this->db->get()->result_array();
        
        // Get students with marks
        $this->db->select('s.student_id, s.name');
        $this->db->from('enroll e');
        $this->db->join('student s', 's.student_id = e.student_id');
        $this->db->where('e.class_id', $exam['class_id']);
        $this->db->where('e.year', $exam['year']);
        $this->db->order_by('s.name', 'ASC');
        $students = $this->db->get()->result_array();
        
        // Get all marks
        foreach($students as &$student) {
            $marks = $this->db->select('subject_id, total_score')
                              ->where('student_id', $student['student_id'])
                              ->where('exam_id', $exam_id)
                              ->get('enterprise_student_marks')
                              ->result_array();
            
            $student['marks'] = [];
            $total = 0;
            foreach($marks as $mark) {
                $student['marks'][$mark['subject_id']] = $mark['total_score'];
                $total += $mark['total_score'];
            }
            $student['total'] = $total;
            $student['average'] = count($marks) > 0 ? $total / count($marks) : 0;
        }
        
        // Sort by total and assign positions
        usort($students, fn($a, $b) => $b['total'] - $a['total']);
        foreach($students as $key => &$student) {
            $student['position'] = $key + 1;
        }
        
        $page_data['exam'] = $exam;
        $page_data['subjects'] = $subjects;
        $page_data['students'] = $students;
        $page_data['page_name'] = 'examination/broadsheet';
        $page_data['page_title'] = 'Broadsheet';
        $this->load->view('backend/index', $page_data);
    }
    
    public function add_exam_subjects() {
        $exam_id = $this->input->post('exam_id');
        $subject_ids = $this->input->post('subject_ids');
        
        $this->db->trans_start();
        
        foreach($subject_ids as $subject_id) {
            $data = [
                'exam_id' => $exam_id,
                'subject_id' => $subject_id,
                'total_marks' => 100,
                'pass_mark' => 50
            ];
            $this->db->insert('enterprise_exam_subjects', $data);
        }
        
        $this->db->trans_complete();
        
        if($this->db->trans_status()) {
            echo json_encode(['status' => 'success', 'message' => 'Subjects added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add subjects']);
        }
    }
    
    public function get_all_subjects() {
        $subjects = $this->db->select('subject_id, name, subject_type')
                             ->order_by('subject_type', 'ASC')
                             ->order_by('name', 'ASC')
                             ->get('subject')
                             ->result_array();
        echo json_encode(['status' => 'success', 'data' => $subjects]);
    }
    
    public function debug_data() {
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        
        // Get all exams
        $all_exams = $this->db->select('*')->get('exam')->result_array();
        
        // Get settings
        $settings = $this->db->where_in('type', ['running_year', 'running_term'])->get('settings')->result_array();
        
        echo json_encode([
            'current_settings' => [
                'year' => $running_year,
                'term' => $running_term
            ],
            'all_settings' => $settings,
            'all_exams' => $all_exams,
            'exam_count' => count($all_exams)
        ]);
    }
    
    public function analytics($exam_id = null) {
        if(!$exam_id) {
            redirect(site_url('examination'));
            return;
        }
        
        $page_data['exam_id'] = $exam_id;
        $page_data['page_name'] = 'examination/analytics';
        $page_data['page_title'] = 'Examination Analytics';
        $this->load->view('backend/index', $page_data);
    }
    
    public function get_analytics() {
        $exam_id = $this->input->get('exam_id');
        
        // Stats
        $exam = $this->Examination_model->get_exam($exam_id);
        $total_students = $this->db->where('class_id', $exam['class_id'])->where('year', $exam['year'])->count_all_results('enroll');
        
        $marks = $this->db->select('AVG(total_score) as avg, MAX(total_score) as max')
                          ->where('exam_id', $exam_id)
                          ->get('enterprise_student_marks')
                          ->row_array();
        
        $pass_count = $this->db->where('exam_id', $exam_id)->where('total_score >=', 50)->count_all_results('enterprise_student_marks');
        $total_marks = $this->db->where('exam_id', $exam_id)->count_all_results('enterprise_student_marks');
        
        $stats = [
            'total_students' => $total_students,
            'pass_rate' => $total_marks > 0 ? round(($pass_count / $total_marks) * 100, 1) : 0,
            'avg_score' => round($marks['avg'], 1),
            'top_score' => round($marks['max'], 1)
        ];
        
        // Grade distribution
        $grades = $this->db->select('grade, COUNT(*) as count')
                           ->where('exam_id', $exam_id)
                           ->group_by('grade')
                           ->get('enterprise_student_marks')
                           ->result_array();
        
        $grade_data = ['labels' => [], 'values' => []];
        foreach($grades as $g) {
            $grade_data['labels'][] = $g['grade'];
            $grade_data['values'][] = $g['count'];
        }
        
        // Subject performance
        $subjects = $this->db->select('s.name, AVG(m.total_score) as avg')
                             ->from('enterprise_student_marks m')
                             ->join('subject s', 's.subject_id = m.subject_id')
                             ->where('m.exam_id', $exam_id)
                             ->group_by('m.subject_id')
                             ->get()
                             ->result_array();
        
        $subject_data = ['labels' => [], 'values' => []];
        foreach($subjects as $s) {
            $subject_data['labels'][] = $s['name'];
            $subject_data['values'][] = round($s['avg'], 1);
        }
        
        echo json_encode([
            'status' => 'success',
            'stats' => $stats,
            'charts' => [
                'grades' => $grade_data,
                'subjects' => $subject_data
            ]
        ]);
    }
    
    public function waec_result($student_id, $exam_id) {
        // Get student info
        $student = $this->db->get_where('student', ['student_id' => $student_id])->row_array();
        $exam = $this->Examination_model->get_exam($exam_id);
        $school = $this->db->get('settings')->result_array();
        
        // Format results
        $results = $this->aggregate_calculator->format_for_report($student_id, $exam_id);
        
        // Prepare data
        $page_data = array_merge($results, [
            'student_name' => $student['name'],
            'index_number' => $student['student_code'] ?? 'N/A',
            'exam_session' => $exam['exam_name'],
            'year' => $exam['year'],
            'school_name' => $this->get_setting('system_name'),
            'school_address' => $this->get_setting('address'),
            'school_phone' => $this->get_setting('phone'),
            'school_email' => $this->get_setting('system_email'),
            'head_name' => $this->get_setting('head_teacher_name') ?? 'Head of School',
            'student_id' => $student_id,
            'exam_id' => $exam_id
        ]);
        
        $this->load->view('backend/examination/waec_result', $page_data);
    }
    
    public function download_result_pdf($student_id, $exam_id) {
        $this->load->library('pdf');
        
        // Get HTML content
        ob_start();
        $this->waec_result($student_id, $exam_id);
        $html = ob_get_clean();
        
        // Generate PDF
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        
        $student = $this->db->get_where('student', ['student_id' => $student_id])->row_array();
        $filename = 'WAEC_Result_' . str_replace(' ', '_', $student['name']) . '.pdf';
        
        $this->pdf->stream($filename, ['Attachment' => 1]);
    }
    
    private function get_setting($key) {
        $setting = $this->db->get_where('settings', ['type' => $key])->row();
        return $setting ? $setting->description : '';
    }
}
