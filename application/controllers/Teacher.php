<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// *************************************************************************
// *                                                                       *
// * Lisofts School Manager                                                 *
// * Copyright (c) Lightworld Technologies Limited. All Rights Reserved    *
// *                                                                       *
// *************************************************************************
// * @author : Lightworldtech                                              *
// * date        : August 11, 2019                                         *
// * description : For managing different levels of schools                *
// * Email   : softmail@lisofts.com                                         *
// * Website : https://www.lisofts.com                                      *
// * Support : https://www.support.lisofts.com                              *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * This software is furnished under a license and may be used and copied *
// * only  in  accordance  with  the  terms  of such  license and with the *
// * inclusion of the above copyright notice.                              *
// *                                                                       *
// *************************************************************************

class Teacher extends CI_Controller
{


    function __construct()
    {
        parent::__construct();
		$this->load->database();
        $this->load->library('session');
        $this->load->model(array('Ajaxdataload_model' => 'ajaxload'));
        
        /*cache control*/
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        //load encryption library
        $this->load->library('encryption');

        //email control
        $this->load->helper('email');

        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }

    /***default functin, redirects to login page if no teacher logged in yet***/
    public function index()
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        if ($this->session->userdata('teacher_login') == 1)
            redirect(site_url('teacher/dashboard'));
    }

    /***TEACHER DASHBOARD***/
    function dashboard($param = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());
        $page_data['page_name']  = 'dashboard';
        $page_data['page_title'] = get_phrase('teacher_dashboard');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['running_year'] = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $page_data['running_term'] = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        
        if($param == 'ajax') {
            $this->load->view('backend/main', $page_data); 
        } else {
            $this->load->view('backend/index', $page_data);
        }
    }

    // Get notifications (AJAX)
    function get_notifications() {
        echo json_encode(['status' => 'success', 'count' => 0, 'notifications' => []]);
    }

    // Sync method (AJAX)
    function sync() {
        echo json_encode(['status' => 'success', 'message' => 'Synced']);
    }


    /*ENTRY OF A NEW STUDENT*/


    /****MANAGE STUDENTS CLASSWISE*****/

	function student_information($class_id = '')
	{
		if ($this->session->userdata('teacher_login') != 1)
            redirect('login');

        //enable database cache
        //$this->db->cache_on();

        //This will help us not to have a change in the class list if a different class is selected for a particular student

        $this->session->set_userdata('class_id_for_result_archives', $class_id);

		$page_data['page_name']  	= 'student_information';
		$page_data['page_title'] 	= get_phrase('student_information'). " - ".$this->crud_model->get_class_name($class_id).' '.$this->crud_model->get_class_name_numeric($class_id).$this->db->get_where('section', array('class_id' => $class_id))->row()->name;
		$page_data['class_id'] 	= $class_id;

		$page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
	}

  function student_profile($student_id)
  {
    if ($this->session->userdata('teacher_login') != 1) {
      redirect(base_url());
    }
    
    //enable database cache
    //$this->db->cache_on();

    $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
    $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

    $enroll_info = $this->db->get_where('enroll', array(
      'student_id' => $student_id, 
      'year' => $running_year, 
      'mute' => '0', 
      'term' => $running_term
    ));

    if ($enroll_info->num_rows() == 0) {
      show_error('Student not found or not enrolled in current term');
      return;
    }

    $class_id = $enroll_info->row()->class_id;
    $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
    $section_id = $this->db->get_where('enroll', array(
      'class_id' => $class_id, 
      'mute' => '0',
      'student_id' => $student_id, 
      'year' => $running_year, 
      'term' => $running_term
    ))->row()->section_id;

    $exam_query = $this->db->get_where('mark', array(
      'class_id' => $class_id, 
      'section_id' => $section_id, 
      'student_id' => $student_id, 
      'year' => $running_year, 
      'term' => $running_term
    ));
    $exam_id = $exam_query->num_rows() > 0 ? $exam_query->row()->exam_id : 0;

    // Get other students in the same class for the dropdown
    $other_students = $this->db->get_where('enroll', array(
      'year' => $running_year,  
      'mute' => '0', 
      'term' => $running_term, 
      'class_id' => $class_id
    ))->result_array();

    $raw_score = $this->db->get_where('settings', array('type' => 'raw_score'))->row()->description;
    
    if ($raw_score == 'Yes') {
      if ($class_name == 'JHSS') {
        $page_data['page_name'] = 'student_profile_raw_score';
      } else {
        $page_data['page_name'] = 'student_profile';
      }
    } elseif ($raw_score == 'No') {
      $page_data['page_name'] = 'student_profile';
    }

    $page_data['class_id'] = $class_id;
    $page_data['section_id'] = $section_id;
    $page_data['exam_id'] = $exam_id;    
    $page_data['page_title'] = get_phrase('student_profile');
    $page_data['student_id'] = $student_id;
    $page_data['other_students'] = $other_students;
    $page_data['account_type'] = $this->session->userdata('login_type');
    $page_data['is_teacher_view'] = true; // Flag to restrict sensitive tabs
    
    $this->load->view('backend/main', $page_data);
  }

	// Exam Reports Archives Page
	function exam_reports() {
		if ($this->session->userdata('teacher_login') != 1)
			redirect('login');
		
		$page_data['page_name'] = 'exam_reports';
		$page_data['page_title'] = get_phrase('exam_reports_archives');
		$page_data['account_type'] = 'teacher';
		$this->load->view('backend/main', $page_data);
	}

	function student_marksheet($student_id = '') {
        if ($this->session->userdata('teacher_login') != 1)
            redirect('login');

        //enable database cache
        //$this->db->cache_on();

         $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->class_id;
        $section_id     = $this->db->get_where('enroll' , array('class_id' => $class_id, 
            'student_id' => $student_id , 'mute' => '0', 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->section_id;
        $exam_id     = $this->db->get_where('mark' , array('class_id' => $class_id, 'section_id' => $section_id, 'student_id' => $student_id, 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->exam_id;

        $student_name = $this->db->get_where('student' , array('student_id' => $student_id))->row()->name;
        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

        $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
        if($raw_score == 'Yes') {
            if($class_name == 'JHSS'){
            $page_data['page_name'] = 'student_raw_score_marksheet';
        }else{
            $page_data['page_name'] = 'student_marksheet'; };
        }elseif($raw_score == 'No') {
            $page_data['page_name'] = 'student_marksheet';
        }
        
        //add section A or B if the class has more than one section
        $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
        $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
        $sec_name = '';
        if($class_has_more_sections > 1) {
            $sec_name = $section_name;
        }
        
        $page_data['page_title'] =   get_phrase('result_sheet_for') . ' ' . $student_name . ' (' . ' ' . $class_name . ' '. $class_name_numeric.$sec_name.')';
        $page_data['student_id'] =   $student_id;
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    

    function student_marksheet_print_view($student_id , $exam_id) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect('login');

        //enable database cache
        //$this->db->cache_on();

        // Fetch year and term from exam record instead of system settings
        $exam_record = $this->db->get_where('exam', array('exam_id' => $exam_id))->row();
        $running_year = $exam_record->year;
        $running_term = isset($exam_record->term) ? $exam_record->term : '';
        $running_sem = isset($exam_record->sem) ? $exam_record->sem : '';

        $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $running_year, 'term' => $running_term
        ))->row()->class_id;

        $section_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $running_year, 'term' => $running_term
        ))->row()->section_id;

        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

        $page_data['student_id'] =   $student_id;
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['running_sem'] = $running_sem;

        if($exam_id != '' || $exam_id != '' && $class_id != "" || $class_id != "" && $section_id != '' || $section_id != '') {
            $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;

            $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;

            if($raw_score == 'Yes') {
                    if($class_name == 'JHSS'){
                    $this->load->view('backend/teacher/student_raw_score_marksheet_print_view', $page_data);
                }else{
                    //load the chosen exam report style

                    if($terminal_report_style == 'style_1') {
                        $this->load->view('backend/admin/student_marksheet_print_view', $page_data);
                    } elseif($terminal_report_style == 'style_2') {
                        $this->load->view('backend/admin/student_marksheet_print_view_2', $page_data);
                    } else {
                        $this->load->view('backend/admin/student_marksheet_print_view_3', $page_data);
                    } 
                }
            }elseif($raw_score == 'No') {
                //load the chosen exam report style

                if($terminal_report_style == 'style_1') {
                    $this->load->view('backend/admin/student_marksheet_print_view', $page_data);
                } elseif($terminal_report_style == 'style_2') {
                    $this->load->view('backend/admin/student_marksheet_print_view_2', $page_data);
                } else {
                    $this->load->view('backend/admin/student_marksheet_print_view_3', $page_data);
                }
            }
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('teacher/student_marksheet'), 'refreh');
        }
    }

    


//for creche
    function student_marksheet_creche($student_id = '') {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        //$this->db->cache_on();

        $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->class_id;
        $section_id     = $this->db->get_where('enroll' , array('class_id' => $class_id, 
            'student_id' => $student_id , 'mute' => '0', 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->section_id;
        $exam_id     = $this->db->get_where('mark' , array('class_id' => $class_id, 'section_id' => $section_id, 'student_id' => $student_id, 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->exam_id;

        $student_name = $this->db->get_where('student' , array('student_id' => $student_id))->row()->name;
        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

            $page_data['page_name'] = 'student_marksheet_creche';

        //add section A or B if the class has more than one section
        $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
        $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
        $sec_name = '';
        if($class_has_more_sections > 1) {
            $sec_name = $section_name;
        }
        
        $page_data['page_title'] =   get_phrase('result_sheet_for') . ' ' . $student_name . ' (' . ' ' . $class_name . ' '. $class_name_numeric.$sec_name.')';
        $page_data['student_id'] =   $student_id;
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }


    

    function student_marksheet_print_view_creche($student_id , $exam_id) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        //$this->db->cache_on();

        // Fetch year and term from exam record instead of system settings
        $exam_record = $this->db->get_where('exam', array('exam_id' => $exam_id))->row();
        $running_year = $exam_record->year;
        $running_term = isset($exam_record->term) ? $exam_record->term : '';
        $running_sem = isset($exam_record->sem) ? $exam_record->sem : '';

        $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $running_year, 'term' => $running_term
        ))->row()->class_id;

        $section_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $running_year, 'term' => $running_term
        ))->row()->section_id;

        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

        $page_data['student_id'] =   $student_id;
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['running_sem'] = $running_sem;

        if($exam_id != '' || $exam_id != '' && $class_id != "" || $class_id != "" && $section_id != '' || $section_id != '') {
                $this->load->view('backend/admin/student_marksheet_print_view_creche', $page_data);
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('teacher/student_marksheet'), 'refreh');
        }
    }

    //bulk marksheet printing
    function student_marksheet_bulk_print_view_creche($class_id, $section_id) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        //$this->db->cache_on();

        $exam_id = $this->input->post('exam_id');


        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        $page_data['year']       =   $this->db->get_where('exam', array('exam_id' => $exam_id))->row()->year;
        $page_data['term']       =   $this->db->get_where('exam', array('exam_id' => $exam_id))->row()->term;


        if($exam_id != '' || $exam_id != '' && $class_id != "" || $class_id != "" && $section_id != '' || $section_id != '') {
            $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;

                $this->load->view('backend/admin/student_marksheet_bulk_print_view_creche', $page_data);
            
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('teacher/student_information'), 'refreh');
        }
    }//bulk marksheet printing ends

    
    function student_results_sheet_print_view_creche($student_id , $exam_id, $class_id, $term, $year) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        //$this->db->cache_on();

        $section_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'mute' => '0', 'year' => $year, 'term' => $term, 'class_id' => $class_id
        ))->row()->section_id;

        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

        $page_data['student_id'] =   $student_id;
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        $page_data['term']       =   $term;
        $page_data['year']       =   $year;

        

        if($exam_id != '' || $exam_id != '' && $class_id != "" || $class_id != "" && $section_id != '' || $section_id != '') {

                $this->load->view('backend/admin/student_results_sheet_print_view_creche', $page_data);
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('teacher/student_results_sheet'), 'refreh');
        }
    } //eend of creche


    function get_class_section($class_id)
    {
        $sections = $this->db->get_where('section' , array(
            'class_id' => $class_id
        ))->result_array();
        foreach ($sections as $row) {
            echo '<option value="' . $row['section_id'] . '">' . $row['name'] . '</option>';
        }
    }

    function get_class_subject($class_id)
    {
        $year       = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $term       = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
        $subject = $this->db->get_where('subject' , array(
            'class_id' => $class_id ,'teacher_id'=> $this->session->userdata('teacher_id'), 'year' => $year, 'term' => $term
        ))->result_array();
        foreach ($subject as $row) {
            echo '<option value="' . $row['subject_id'] . '">' . $row['name'] . '</option>';
        }
    }
    /****MANAGE TEACHERS*****/
    function teacher_list($param1 = '', $param2 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());

        if ($param1 == 'personal_profile') {
            $page_data['personal_profile']   = true;
            $page_data['current_teacher_id'] = $param2;
        }
        $page_data['teachers']   = $this->db->get('teacher')->result_array();
        $page_data['page_name']  = 'teacher';
        $page_data['page_title'] = get_phrase('teacher_list');
        

            $page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
    }



    /****MANAGE SUBJECTS*****/
    function subject($param1 = '', $param2 = '' , $param3 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());
        if ($param1 == 'create') {
            $data['name']       = ucwords(strtolower($this->input->post('name')));
            $status    = $this->input->post('status');
            if($status == 1) {
                $data['status'] = 1;
            }else{
                $data['status'] = 0;
            }
            $data['class_id']   = $this->input->post('class_id');
            $data['year']       = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;

            $class_name = $this->crud_model->get_class_name($data['class_id']);

            if($class_name == 'JHSS') {

                $data['sem']       = $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description;
            } else {
                $data['term']       = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            }
            
            if ($this->input->post('teacher_id') != null) {
                $data['teacher_id'] = $this->input->post('teacher_id');
            }

            $this->db->insert('subject', $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('subject_added_successfully')]);
            return;
        }
        if ($param1 == 'do_update') {
            $data['name']       = ucwords(strtolower($this->input->post('name')));
             $status    = $this->input->post('status');
            if($status == 1) {
                $data['status'] = 1;
            }else{
                $data['status'] = 0;
            }
            $data['class_id']   = $this->input->post('class_id');
            $data['teacher_id'] = $this->input->post('teacher_id');
            $data['year']       = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            
            $class_name = $this->crud_model->get_class_name($data['class_id']);

            if($class_name == 'JHSS') {

                $data['sem']       = $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description;
            } else {
                $data['term']       = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            }

            $this->db->where('subject_id', $param2);
            $this->db->update('subject', $data);


            if($class_name == 'JHSS') {

                //update subject status in the mark table
                $subject_status = $this->db->get_where('subject', array('name' => $data['name'], 'subject_id' => $param2, 'class_id' => $data['class_id'], 'year' => $data['year'], 'sem' => $data['sem']))->row()->status;
                $section_id = $this->db->get_where('section', array('class_id' => $data['class_id']))->row()->section_id;

                $this->db->where('subject_id', $param2);
                $this->db->where('class_id', $data['class_id']);
                $this->db->where('year', $data['year']);
                $this->db->where('section_id', $section_id);
                $this->db->where('sem', $data['sem']);
                $this->db->update('mark', array('status' => $subject_status));

            } else {

                //update subject status in the mark table
                $subject_status = $this->db->get_where('subject', array('name' => $data['name'], 'subject_id' => $param2, 'class_id' => $data['class_id'], 'year' => $data['year'], 'term' => $data['term']))->row()->status;
                $section_id = $this->db->get_where('section', array('class_id' => $data['class_id']))->row()->section_id;

                $this->db->where('subject_id', $param2);
                $this->db->where('class_id', $data['class_id']);
                $this->db->where('year', $data['year']);
                $this->db->where('section_id', $section_id);
                $this->db->where('term', $data['term']);
                $this->db->update('mark', array('status' => $subject_status));
            }
            
            echo json_encode(['status' => 'success', 'message' => get_phrase('subject_updated_successfully')]);
            return;
        } else if ($param1 == 'edit') {
            $page_data['edit_data'] = $this->db->get_where('subject', array(
                'subject_id' => $param2
            ))->result_array();
        }
        if ($param1 == 'delete') {
            $this->db->where('subject_id', $param2);
            $this->db->delete('subject');

            $this->db->where('subject_id', $param2);
            $this->db->delete('mark', $param2);
            $this->session->set_flashdata('flash_message' , get_phrase('selected_subject_was_deleted_successfully'));
            redirect(site_url('teacher/subject/' . $param3));
        }
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        $running_sem = $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description;

        $page_data['class_id']   = $param1;
        $class_name = $this->crud_model->get_class_name($page_data['class_id']);
        $class_name_numeric = $this->crud_model->get_class_name_numeric($page_data['class_id']);
        
        //adding sections to class names
        //add section A or B if the class has more than one section
        $section_name = $this->db->get_where('section', array('class_id' => $page_data['class_id']))->row()->name;
        $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
        $sec_name = '';
        if($class_has_more_sections > 1) {
            $sec_name = $section_name;
        }
        
        //run query for creche 1
        if($param2 == 'creche') {
             $page_data['subjects']   = $this->db->get_where('subject_creche' , array('class_id' => $param1, 'year' => $running_year, 'teacher_id' => $this->session->userdata('teacher_id'), 'term' => $running_term))->result_array();
             $page_data['subjects_rows']   = $this->db->get_where('subject_creche' , array('class_id' => $param1, 'year' => $running_year, 'term' => $running_term))->num_rows();
        } else {

            if($class_name == 'JHSS') {
                $page_data['subjects']   = $this->db->get_where('subject' , array('class_id' => $param1, 'year' => $running_year, 'teacher_id' => $this->session->userdata('teacher_id'), 'sem' => $running_sem))->result_array();
                $page_data['subjects_rows']   = $this->db->get_where('subject' , array('class_id' => $param1, 'year' => $running_year, 'sem' => $running_sem))->num_rows();
            } else {

                $page_data['subjects']   = $this->db->get_where('subject' , array('class_id' => $param1, 'year' => $running_year, 'teacher_id' => $this->session->userdata('teacher_id'), 'term' => $running_term))->result_array();
                $page_data['subjects_rows']   = $this->db->get_where('subject' , array('class_id' => $param1, 'year' => $running_year, 'term' => $running_term))->num_rows();
            }

            
        }
        
        //load subjects for creche
        if($param2 == 'creche') {
            $page_data['page_name']  = 'subject_creche';
        } else {
            $page_data['page_name']  = 'subject';
        }
        
        $page_data['year']  = $running_year;

        if($class_name == 'JHSS') {
            $page_data['sem']  = $running_sem;
        } else {
            $page_data['term']  = $running_term;
        }
        
        $page_data['class_name'] = $class_name;
        $page_data['page_title'] = get_phrase('manage_subject_for').' '.$class_name.' '.$class_name_numeric.$sec_name;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }



    /****MANAGE EXAM MARKS*****/
    function marks_manage()
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());
        $page_data['page_name']  =   'marks_manage';
        $page_data['page_title'] = get_phrase('manage_exam_marks');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    //creche    
    function marks_manage_view_creche($exam_id = '' , $class_id = '' , $section_id = '' , $subject_id = '', $category_id = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        $page_data['exam_id']    =   $exam_id;
        $page_data['class_id']   =   $class_id;
        $page_data['subject_id'] =   $subject_id;
        $page_data['category_id'] =   $category_id;
        $page_data['section_id'] =   $section_id;
        $page_data['page_name']  =   'marks_manage_view_creche';
        $page_data['page_title'] = get_phrase('manage_exam_marks');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
    
     function marks_manage_view_creche2($exam_id = '' , $class_id = '' , $section_id = '' , $subject_id = '', $category_id = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        $page_data['exam_id']    =   $exam_id;
        $page_data['class_id']   =   $class_id;
        $page_data['subject_id'] =   $subject_id;
        $page_data['category_id'] =   $category_id;
        $page_data['section_id'] =   $section_id;
        $page_data['page_name']  =   'marks_manage_view_creche2';
        $page_data['page_title'] = get_phrase('manage_exam_marks');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function marks_manage_view($exam_id = '' , $class_id = '' , $section_id = '' , $subject_id = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());
        $page_data['exam_id']    =   $exam_id;
        $page_data['class_id']   =   $class_id;
        $page_data['subject_id'] =   $subject_id;
        $page_data['section_id'] =   $section_id;
        $page_data['page_name']  =   'marks_manage_view';
        $page_data['page_title'] = get_phrase('manage_exam_marks');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    
    //creche marks update 1
    function marks_update_creche($exam_id = '' , $class_id = '' , $section_id = '' , $subject_id = '', $category_id = '')
    {
        if ($class_id != '' && $exam_id != '') {
            $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            $marks_of_students = $this->db->get_where('mark' , array(
                'exam_id' => $exam_id,
                    'class_id' => $class_id,
                        'section_id' => $section_id,
                            'year' => $running_year,
                                'term' => $running_term,
                                    'subject_id' => $subject_id
            ))->result_array();

            foreach($marks_of_students as $row) {
                $assess = trim($this->input->post('assess_'.$row['mark_id']));

                $data_array = array(
                   'test1' => $assess
                            );
                //$comment = $this->input->post('comment_'.$row['mark_id']);
                $this->db->where('mark_id' , $row['mark_id']);
                $this->db->update('mark' , $data_array);

            }


            echo json_encode(['status' => 'success', 'message' => get_phrase('assessment_updated')]);
            return;
        }
        else{
            $this->session->set_flashdata('error_message' , get_phrase('select_all_the_fields'));
            $page_data['page_name']  =   'marks_manage';
            $page_data['page_title'] = get_phrase('manage_exam_marks');
            

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
        }
    }

    function marks_update($exam_id = '' , $class_id = '' , $section_id = '' , $subject_id = '', $category_id = '')
    {
        if ($class_id != '' && $exam_id != '') {
        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
        $marks_of_students = $this->db->get_where('mark' , array(
            'exam_id' => $exam_id,
                'class_id' => $class_id,
                    'section_id' => $section_id,
                        'year' => $running_year,
                            'term' => $running_term,
                                'subject_id' => $subject_id
        ))->result_array();
        foreach($marks_of_students as $row) {
            $test1 = $this->input->post('test1_'.$row['mark_id']);
            $group_work = $this->input->post('group_work_'.$row['mark_id']);
            $test2 = $this->input->post('test2_'.$row['mark_id']);
            $project = $this->input->post('project_'.$row['mark_id']);
            $sub_total = $this->input->post('sub_total_'.$row['mark_id']);
            $term_exam = $this->input->post('term_exam_'.$row['mark_id']);
            $class_score = $this->input->post('class_score_'.$row['mark_id']);
            $exam_score = $this->input->post('exam_score_'.$row['mark_id']);
            $comment = $this->input->post('comment_'.$row['mark_id']);
            $obtained_marks = (float)$class_score + (float)$exam_score;

            $data_array = array(
                'class_score' => $class_score,
                    'exam_score' => $exam_score, 
                        'mark_obtained' => $obtained_marks , 
                            'comment' => $comment,
                                'test1' => $test1,
                                    'group_work' => $group_work,
                                        'test2' => $test2,
                                            'project' => $project,
                                                'sub_total' => $sub_total,
                                                    'term_exam' => $term_exam
                        );
            //$comment = $this->input->post('comment_'.$row['mark_id']);
            $this->db->where('mark_id' , $row['mark_id']);
            $this->db->update('mark' , $data_array);



        //insert or update aggregation table
            $aggregation_marks = $this->db->get_where('aggregation', array(
                'exam_id' => $exam_id,
                    'class_id' => $class_id,
                        'section_id' => $section_id,
                            'year' => $running_year,
                                'term' => $running_term,
                                    'student_id' => $row['student_id']
            ))->num_rows();

            if($aggregation_marks < 1){
 
                    $data['student_id'] = $row['student_id'];
                    $data['exam_id'] = $row['exam_id'];
                    $data['class_id'] = $row['class_id'];
                    $data['year'] = $row['year'];
                    $data['section_id'] = $row['section_id'];
                    $data['term'] = $running_term;

                    $this->db->select_sum('mark_obtained');
                    $this->db->from('mark');
                    $this->db->where('student_id', $row['student_id']);
                    $this->db->where('class_id', $class_id);
                    $this->db->where('section_id', $section_id);
                    $this->db->where('exam_id', $exam_id);
                    $this->db->where('year', $running_year);
                    $this->db->where('term', $running_term);
                    $aggregate_mark = $this->db->get()->result_array();

                    foreach ($aggregate_mark as $mark) {
                        $data['aggregate_mark'] = $mark['mark_obtained'];
                        $this->db->insert('aggregation', $data);
                    }
                      
            }else{
                    $this->db->select_sum('mark_obtained');
                    $this->db->from('mark');
                    $this->db->where('student_id', $row['student_id']);
                    $this->db->where('class_id', $class_id);
                    $this->db->where('section_id', $section_id);
                    $this->db->where('exam_id', $exam_id);
                    $this->db->where('year', $running_year);
                    $this->db->where('term', $running_term);
                    $aggregate_mark = $this->db->get()->result_array();

                    foreach ($aggregate_mark as $mark) {
                        $data2['aggregate_mark'] = $mark['mark_obtained'];
                        $this->db->where('student_id', $row['student_id']);
                        $this->db->where('class_id', $class_id);
                        $this->db->where('section_id', $section_id);
                        $this->db->where('exam_id', $exam_id);
                        $this->db->where('year', $running_year);
                        $this->db->where('term', $running_term);
                        $this->db->update('aggregation', $data2);
                    }

                    /*===========================================
                        ==============================================\
                        to be deleted
                        =====================================*/
                        //do some quick corrections here
                        $this->db->select('exam_id');
                        $this->db->distinct();
                        $this->db->from('mark');
                        $this->db->where('student_id', $row['student_id']);
                        $ids = $this->db->get()->result_array();

                        foreach($ids as $exam) {
                            $this->db->select_sum('mark_obtained');
                            $this->db->from('mark');
                            $this->db->where('student_id', $row['student_id']);
                            $this->db->where('exam_id', $exam['exam_id']);
                            $total = $this->db->get()->row()->mark_obtained;

                        //update
                            $this->db->where('student_id', $row['student_id']);
                            $this->db->where('exam_id', $exam['exam_id']);
                            $this->db->set('aggregate_mark', $total);
                            $this->db->update('aggregation');
                        }

                        /*===========================================
                        ==============================================\
                        to be deleted
                        =====================================*/
            }



        }

            

        echo json_encode(['status' => 'success', 'message' => get_phrase('marks_updated')]);
        return;
    }
    else{
        $this->session->set_flashdata('error_message' , get_phrase('select_all_the_fields'));
        $page_data['page_name']  =   'marks_manage';
        $page_data['page_title'] = get_phrase('manage_exam_marks');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
}

    function marks_get_subject($class_id)
    {
        $page_data['class_id'] = $class_id;

        $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
        $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
        
        if($class_name == 'CRECHE') { //select from creche subject table
            $this->load->view('backend/teacher/marks_get_subject_creche' , $page_data);
        } else {
            $this->load->view('backend/teacher/marks_get_subject' , $page_data);
        }
    }

    // PORTFOLIO ASSESSMENT METHODS
    function portfolio_assessment_manage() {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');
        $page_data['page_name'] = 'portfolio_assessment_manage';
        $page_data['page_title'] = get_phrase('manage_portfolio_assessment');
        $this->load->view('backend/main', $page_data);
    }

    function portfolio_assessment_manage_view($exam_id = '', $class_id = '', $section_id = '', $subject_id = '', $week = '') {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');
        
        echo '<script type="text/javascript">showAjaxModal_alert("Loading Data. Please wait...", "Loading");</script>';

        $page_data['exam_id'] = $exam_id;
        $page_data['class_id'] = $class_id;
        $page_data['subject_id'] = $subject_id;
        $page_data['week'] = $week;
        $page_data['section_id'] = $section_id;
        $page_data['page_name'] = 'portfolio_assessment_manage_view';
        $page_data['page_title'] = get_phrase('portfolio_assessment_marks');
        $this->load->view('backend/main', $page_data);
    }

    function portfolio_assessment_selector() {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');

        $data['exam_id'] = $this->input->post('exam_id');
        $data['class_id'] = $this->input->post('class_id');
        $data['section_id'] = $this->input->post('section_id');
        $data['subject_id'] = $this->input->post('subject_id');
        $data['week'] = $this->input->post('week');
        $data['year'] = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $data['term'] = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        $class_name = $this->crud_model->get_class_name($data['class_id']);

        if ($data['subject_id'] == '') {
            $this->session->set_flashdata('error_message', get_phrase('no_subject_was_found_for_class:_' . $this->crud_model->get_class_name($data['class_id']) . ' ' . $this->crud_model->get_class_name_numeric($data['class_id']) . '_please_create_subjects_for_this_class'));
            redirect(site_url('teacher/portfolio_assessment_manage?subjr=1'), 'refresh');
        }

        if ($data['class_id'] != '' && $data['exam_id'] != '') {

            $explode_week = explode('-', $data['week'])[0] . explode('-', $data['week'])[1];

            $days_of_week_array = array(
                strtotime($explode_week),
                strtotime($explode_week . ' + 1 day'),
                strtotime($explode_week . ' + 2 days'),
                strtotime($explode_week . ' + 3 days'),
                strtotime($explode_week . ' + 4 days'),
            );

            $data_array = array(
                'exam_id' => $data['exam_id'],
                'class_id' => $data['class_id'],
                'subject_id' => $data['subject_id'],
                'week' => $data['week'],
                'year' => $data['year'],
                'term' => $data['term'],
            );
            $query = $this->db->get_where('portfolio_assessment', $data_array);

            $students = $this->db->get_where('enroll', array(
                'class_id' => $data['class_id'], 'mute' => '0', 'section_id' => $data['section_id'], 'year' => $data['year'], 'term' => $data['term'],
            ));

            if ($query->num_rows() < 1) {
                if ($students->num_rows() < 1) {
                    $this->session->set_flashdata('error_message', get_phrase('seems_students_attendance_for_term_' . $data['term'] . '_has_not_been_marked_yet._please_mark_students_attendance_first_to_enroll_students_for_this_term_before_you_proceed.'));
                    redirect(site_url('teacher/portfolio_assessment_manage?term=' . $data['term'] . '&error=1'), 'refresh');
                } else {
                    $array_students = $students->result_array();
                    foreach ($array_students as $row) {
                        $data['student_id'] = $row['student_id'];
                        for ($d = 0; $d < count($days_of_week_array); $d++) {
                            $data['timestamp'] = $days_of_week_array[$d];
                            $this->db->insert('portfolio_assessment', $data);
                        }
                    }
                }
            } elseif ($query->num_rows() > 0) {
                $sIds = [];
                foreach ($query->result_array() as $st) {
                    array_push($sIds, $st['student_id']);
                }

                $this->db->where_not_in('student_id', $sIds);
                $students = $this->db->get_where('enroll', array(
                    'class_id' => $data['class_id'], 'mute' => '0', 'section_id' => $data['section_id'], 'year' => $data['year'], 'term' => $data['term'],
                ))->result_array();

                foreach ($students as $row) {
                    $data_array_s = array(
                        'exam_id' => $data['exam_id'],
                        'class_id' => $data['class_id'],
                        'subject_id' => $data['subject_id'],
                        'week' => $data['week'],
                        'year' => $data['year'],
                        'term' => $data['term'],
                        'student_id' => $row['student_id'],
                    );

                    $st_rows = $this->db->get_where('portfolio_assessment', $data_array_s)->num_rows();

                    if ($st_rows == 0) {
                        $data['student_id'] = $row['student_id'];
                        for ($d = 0; $d < count($days_of_week_array); $d++) {
                            $data['timestamp'] = $days_of_week_array[$d];
                            $this->db->insert('portfolio_assessment', $data);
                        }
                    }
                }
            }

            redirect(site_url('teacher/portfolio_assessment_manage_view/' . $data['exam_id'] . '/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['subject_id'] . '/' . $data['week']), 'refresh');

        } else {
            $this->session->set_flashdata('error_message', get_phrase('select_all_the_fields'));
            $page_data['page_name'] = 'portfolio_assessment_manage';
            $page_data['page_title'] = get_phrase('manage_portfolio_assessment');
            $this->load->view('backend/main', $page_data);
        }
    }

    function portfolio_assessment_update($timestamps, $st_ids, $ass_ids) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');

        $class_id = $this->input->post('class_id');
        $exam_id = $this->input->post('exam_id');
        $section_id = $this->input->post('section_id');
        $subject_id = $this->input->post('subject_id');
        $week = $this->input->post('week');

        if ($class_id != '' && $exam_id != '') {

            $list_of_students = explode('-', $st_ids);
            $assessment_ids = explode('-', $ass_ids);
            $dates_array = explode('-', $timestamps);

            $batchData = array();
            $assessment_counter = 0;

            for ($st = 0; $st < sizeof($list_of_students); $st++) {
                for ($ts = 0; $ts < sizeof($dates_array); $ts++) {
                    $data['code'] = strtoupper(strtolower(trim($this->input->post('code_' . $dates_array[$ts]))));
                    $data['strand_score'] = trim($this->input->post('strand_' . $assessment_ids[$assessment_counter]));

                    if ($data['strand_score'] == '' || empty($data['strand_score'])) {
                        $data['strand_score'] = 0;
                    }

                    $batchData[] = array(
                        'assessment_id' => $assessment_ids[$assessment_counter],
                        'student_id' => $list_of_students[$st],
                        'timestamp' => $dates_array[$ts],
                        'subject_id' => $subject_id,
                        'class_id' => $class_id,
                        'code' => $data['code'],
                        'strand_score' => round($data['strand_score'], 2),
                    );

                    $assessment_counter++;
                }
            }

            $this->db->update_batch('portfolio_assessment', $batchData, 'assessment_id');

            $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
            $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

            $exam_id2 = $this->db->get_where('mark', array('class_id' => $class_id, 'year' => $running_year, 'term' => $running_term))->row()->exam_id;

            $this->db->select('student_id');
            $this->db->distinct();
            $this->db->where('class_id', $class_id);
            $this->db->where('exam_id', $exam_id2);
            $this->db->where('year', $running_year);
            $this->db->where('term', $running_term);
            $students_ids = $this->db->get('mark')->result_array();

            $this->db->select('timestamp');
            $this->db->distinct();
            $this->db->where('strand_score >', '0');
            $this->db->where('class_id', $class_id);
            $this->db->where('subject_id', $subject_id);
            $this->db->where('year', $running_year);
            $this->db->where('term', $running_term);
            $num_rows = $this->db->get('portfolio_assessment')->num_rows();

            foreach ($students_ids as $id) {
                $this->db->select_sum('strand_score');
                $this->db->where('strand_score >', '0');
                $this->db->where('class_id', $class_id);
                $this->db->where('subject_id', $subject_id);
                $this->db->where('student_id', $id['student_id']);
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $total_score = $this->db->get('portfolio_assessment')->row()->strand_score;

                if ($total_score != NULL) {
                    $total_score = $total_score * 10;
                    $average_score = (floatval($total_score) / floatval($num_rows));
                    $percentage_average = $average_score * 0.2;
                    $percentage_average = round($percentage_average, 2);
                } else {
                    $percentage_average = $total_score;
                }

                $this->db->where('class_id', $class_id);
                $this->db->where('subject_id', $subject_id);
                $this->db->where('student_id', $id['student_id']);
                $this->db->where('exam_id', $exam_id2);
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $this->db->set('test1', $percentage_average);
                $this->db->update('mark');
            }

            $ajax['success'] = 1;

        } else {
            $ajax['success'] = 0;
        }

        echo json_encode($ajax);
    }

    // ASSESSMENT GRAPH
    function assessment_graph($param1 = "") {

        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');

        if ($param1 == 'generate') {
            $errors = [];
            $ajaxMessage = [];

            $pageData['class_id'] = $this->input->post('search_by_class');
            $pageData['student_id'] = $this->input->post('search_by_student');
            $pageData['graph_type'] = $this->input->post('search_by_type');
            $pageData['view_type'] = $this->input->post('search_by_view');
            $pageData['subject_id'] = $this->input->post('search_by_subject');

            if ($pageData['class_id'] == '') {
                $errors['class_error'] = "Class is required";
            }
            if ($pageData['student_id'] == '') {
                $errors['student_error'] = "Student is required";
            }
            if ($pageData['subject_id'] == '') {
                $errors['subject_error'] = "subject is required";
            }

            if (!empty($errors)) {
                //error occured
                echo json_encode($errors);
                return false;

            } else {
                //no error occured
                $pageData['data_source'] = $this->input->post('search_by_data_source');
                if ($pageData['data_source'] == 1) {
                    //portfolio assessment selected so get start and end weeks
                    $pageData['start_week'] = $this->input->post('start_week');
                    $pageData['end_week'] = $this->input->post('end_week');

                    if ($pageData['student_id'] == '0' && $pageData['subject_id'] != '0') {
                        //all students for a single subject
                        $pageData['data'] = $this->graphassessment_model->getPortfolioAssessmentData(
                            $pageData['class_id'],
                            $pageData['student_id'],
                            $pageData['subject_id'],
                            $pageData['start_week'],
                            $pageData['end_week'],
                            'student_name'
                        );

                        $pageData['page_name'] = 'assessment_graph/assessment_graph_view_all_students_single_subject';

                    } else if ($pageData['subject_id'] == '0' && $pageData['student_id'] != '0') {
                        //all subjects for a single student
                        $pageData['data'] = $this->graphassessment_model->getPortfolioAssessmentData(
                            $pageData['class_id'],
                            $pageData['student_id'],
                            $pageData['subject_id'],
                            $pageData['start_week'],
                            $pageData['end_week'],
                            'subject_name',
                        );

                        $pageData['page_name'] = 'assessment_graph/assessment_graph_view_all_subjects_single_student';

                    } else if ($pageData['student_id'] == '0' && $pageData['subject_id'] == '0') {

                        $pageData['page_name'] = 'assessment_graph/assessment_graph_view_all_subjects_all_student';

                    } else {
                        //single subject for single student
                        $pageData['data'] = $this->graphassessment_model->getPortfolioAssessmentData(
                            $pageData['class_id'],
                            $pageData['student_id'],
                            $pageData['subject_id'],
                            $pageData['start_week'],
                            $pageData['end_week'],
                            '',
                            'yes'
                        );

                        $pageData['page_name'] = 'assessment_graph/assessment_graph_view_single_subject_single_student';

                    }

                } else {
                    //terminal exams selected so get the term and year
                    $pageData['term'] = $this->input->post('search_by_term');
                    $pageData['year'] = $this->input->post('search_by_year');
                }

                $ajaxMessage['data'] = $this->load->view('backend/teacher/' . $pageData['page_name'], $pageData, true);

                $ajaxMessage['message'] = 'success';
                echo json_encode($ajaxMessage);
                return false;
            }

        } else {

            $pageData['page_title'] = 'ASSESSMENT GRAPH VIEW';
            $pageData['page_name'] = 'assessment_graph/assessment_graph';
            $this->load->view('backend/main', $pageData);
        }

    }

    function getAllPortfolioAssessmentStudentsData($class_id = '', $week = '') {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');
            
        $this->graphassessment_model->getAllStudentsData($class_id, $week);
    }

    // CUMULATIVE REPORTS
    function cummulative_reports($student_id) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');
            
        $this->db->select('year, class_id');
        $this->db->where('class_id IS NOT NULL');
        $this->db->distinct();
        $data['all_years'] = $this->db->get_where('enroll', array('student_id' => $student_id))->result_array();
        $data['student_id'] = $student_id;

        $this->load->view('backend/teacher/cummulative_reports', $data);
    }

    // bulk cummulative reports
    function bulk_cummulative_reports($class_id) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'), 'refresh');
            
        $data['selected_class_id'] = $class_id;

        $this->load->view('backend/teacher/cummulative_reports_bulk', $data);
    }


    // ACADEMIC SYLLABUS
    function academic_syllabus($class_id = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());
        // detect the first class
        if ($class_id == '')
            $class_id           =   $this->db->get('class')->first_row()->class_id;

        $page_data['page_name']  = 'academic_syllabus';
        $page_data['page_title'] = get_phrase('academic_syllabus');
        $page_data['class_id']   = $class_id;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function upload_academic_syllabus()
    {
        $data['academic_syllabus_code'] =   substr(md5(rand(0, 1000000)), 0, 7);
        $data['title']                  =   strtoupper($this->input->post('title'));
        $data['description']            =   $this->input->post('description');
        $data['class_id']               =   $this->input->post('class_id');
        if ($this->input->post('subject_id') != null) {
           $data['subject_id']          =   $this->input->post('subject_id');
        }
        $data['uploader_type']          =   $this->session->userdata('login_type');
        $data['uploader_id']            =   $this->session->userdata('login_user_id');
        $data['year']                   =   $this->db->get_where('settings',array('type'=>'running_year'))->row()->description;
        $data['timestamp']              =   strtotime(date("Y-m-d H:i:s"));
        //uploading file using codeigniter upload library
        $files = $_FILES['file_name'];
        $this->load->library('upload');
        $config['upload_path']   =  'uploads/syllabus/';
        $config['allowed_types'] =  '*';
        $_FILES['file_name']['name']     = $files['name'];
        $_FILES['file_name']['type']     = $files['type'];
        $_FILES['file_name']['tmp_name'] = $files['tmp_name'];
        $_FILES['file_name']['size']     = $files['size'];
        $this->upload->initialize($config);
        $this->upload->do_upload('file_name');

        $data['file_name'] = $_FILES['file_name']['name'];

        $this->db->insert('academic_syllabus', $data);
        echo json_encode(['status' => 'success', 'message' => get_phrase('syllabus_uploaded')]);
        return;

    }

    function download_academic_syllabus($academic_syllabus_code)
    {
        $file_name = $this->db->get_where('academic_syllabus', array(
            'academic_syllabus_code' => $academic_syllabus_code
        ))->row()->file_name;
        $data = file_get_contents("uploads/syllabus/" . $file_name);
        $name = $file_name;

        force_download($name, $data);
    }

    /*****BACKUP / RESTORE / DELETE DATA PAGE**********/
    function backup_restore($operation = '', $type = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());

        if ($operation == 'create') {
            $this->crud_model->create_backup($type);
        }
        if ($operation == 'restore') {
            $this->crud_model->restore_backup();
            $this->session->set_flashdata('backup_message', 'Backup Restored');
            redirect(site_url('teacher/backup_restore'));
        }
        if ($operation == 'delete') {
            $this->crud_model->truncate($type);
            $this->session->set_flashdata('backup_message', 'Data removed');
            redirect(site_url('teacher/backup_restore'));
        }

        $page_data['page_info']  = 'Create backup / restore from backup';
        $page_data['page_name']  = 'backup_restore';
        $page_data['page_title'] = get_phrase('manage_backup_restore');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    /******MANAGE OWN PROFILE AND CHANGE PASSWORD***/
    function manage_profile($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        if ($param1 == 'update_profile_info') {
            $data['name']        = strtoupper($this->input->post('name'));
            $data['email']       = strtolower($this->input->post('email'));

            $this->load->helper('email');
              if(!valid_email($data['email'])) {
                $this->session->set_flashdata('error_message' , 'Invalid Email Found!');
                redirect(site_url('teacher/manage_profile'));
              }

            $validation = email_validation_for_edit($data['email'], $this->session->userdata('teacher_id'), 'teacher');
            if ($validation == 1) {
                $this->db->where('teacher_id', $this->session->userdata('teacher_id'));
                $this->db->update('teacher', $data);
                move_uploaded_file($_FILES['userfile']['tmp_name'], 'uploads/teacher_image/' . $this->session->userdata('teacher_id') . '.jpg');
                echo json_encode(['status' => 'success', 'message' => get_phrase('account_updated')]);
            }
            else{
                echo json_encode(['status' => 'error', 'message' => get_phrase('this_email_id_is_not_available')]);
            }
            return;
        }
        if ($param1 == 'change_password') {
            $data['password']             = $this->input->post('password');
            $data['new_password']         = password_hash($this->input->post('new_password'), PASSWORD_BCRYPT);
            $data['confirm_new_password'] = $this->input->post('confirm_new_password');
            
            $current_password = $this->db->get_where('teacher', array(
                'teacher_id' => $this->session->userdata('teacher_id')
            ))->row()->password;
            if (password_verify($data['password'], $current_password) == 1 && password_verify($data['confirm_new_password'], $data['new_password']) == 1) {
                $this->db->where('teacher_id', $this->session->userdata('teacher_id'));
                $this->db->update('teacher', array(
                    'password' => $data['new_password']
                ));
                
                $message = 'You have changed your password. Your current password is: '.$this->input->post('new_password');

                $phone = $this->db->get_where('teacher', array(
                'teacher_id' => $this->session->userdata('teacher_id')
            ))->row()->phone;
                $data_sms['phone'] = $phone;

                $email = $this->db->get_where('teacher', array(
                'teacher_id' => $this->session->userdata('teacher_id')
            ))->row()->email;

                //send sms first
                $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
                if($active_service != 'disabled') {
                    $this->sms_model->send_sms($message, $data_sms['phone']);
                }
                

                //send email now
                $this->email_model->password_changed_email($this->input->post('new_password'), 'teacher', $email);

                echo json_encode(['status' => 'success', 'message' => get_phrase('password_updated_successfully'), 'redirect' => site_url('login')]);
                
                //destroy current session
                $this->session->sess_destroy();
                return;
            } else {
                echo json_encode(['status' => 'error', 'message' => get_phrase('password_mismatch!')]);
                return;
            }
        }
        $page_data['page_name']  = 'manage_profile';
        $page_data['page_title'] = get_phrase('manage_profile');
        $page_data['edit_data']  = $this->db->get_where('teacher', array(
            'teacher_id' => $this->session->userdata('teacher_id')
        ))->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

/**********MANAGING CLASS ROUTINE******************/
    function class_routine($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        if ($param1 == 'create') {

            if($this->input->post('class_id') != null){
               $data['class_id']       = $this->input->post('class_id');
            }

            $data['section_id']     = $this->input->post('section_id');
            $data['subject_id']     = $this->input->post('subject_id');

            // 12 AM for starting time
            if ($this->input->post('time_start') == 12 && $this->input->post('starting_ampm') == 1) {
                $data['time_start'] = 24;
            }
            // 12 PM for starting time
            else if ($this->input->post('time_start') == 12 && $this->input->post('starting_ampm') == 2) {
                $data['time_start'] = 12;
            }
            // otherwise for starting time
            else{
                $data['time_start']     = (int)$this->input->post('time_start') + (12 * ((int)$this->input->post('starting_ampm') - 1));
            }
            // 12 AM for ending time
            if ($this->input->post('time_end') == 12 && $this->input->post('ending_ampm') == 1) {
                $data['time_end'] = 24;
            }
            // 12 PM for ending time
            else if ($this->input->post('time_end') == 12 && $this->input->post('ending_ampm') == 2) {
                $data['time_end'] = 12;
            }
            // otherwise for ending time
            else{
                $data['time_end']       = (int)$this->input->post('time_end') + (12 * ((int)$this->input->post('ending_ampm') - 1));
            }

            $data['time_start_min'] = $this->input->post('time_start_min');
            $data['time_end_min']   = $this->input->post('time_end_min');
            $data['day']            = $this->input->post('day');
            $data['year']           = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            $data['term']           = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            // checking duplication
            $array = array(
               'section_id'    => $data['section_id'],
               'class_id'      => $data['class_id'],
               'time_start'    => $data['time_start'],
               'time_end'      => $data['time_end'],
               'time_start_min'=> $data['time_start_min'],
               'time_end_min'  => $data['time_end_min'],
               'day'           => $data['day'],
               'year'          => $data['year'],
               'term'          => $data['term']
            );
            $validation = duplication_of_class_routine_on_create($array);
            if ($validation == 1) {
                $this->db->insert('class_routine', $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('data_added_successfully')]);
            }
            else{
                echo json_encode(['status' => 'error', 'message' => get_phrase('time_conflicts')]);
            }
            return;
        }
        if ($param1 == 'do_update') {
            $data['class_id']       = $this->input->post('class_id');
            if($this->input->post('section_id') != '') {
                $data['section_id'] = $this->input->post('section_id');
            }
            $data['subject_id']     = $this->input->post('subject_id');

            // 12 AM for starting time
            if ($this->input->post('time_start') == 12 && $this->input->post('starting_ampm') == 1) {
                $data['time_start'] = 24;
            }
            // 12 PM for starting time
            else if ($this->input->post('time_start') == 12 && $this->input->post('starting_ampm') == 2) {
                $data['time_start'] = 12;
            }
            // otherwise for starting time
            else{
                $data['time_start']     = (int)$this->input->post('time_start') + (12 * ((int)$this->input->post('starting_ampm') - 1));
            }
            // 12 AM for ending time
            if ($this->input->post('time_end') == 12 && $this->input->post('ending_ampm') == 1) {
                $data['time_end'] = 24;
            }
            // 12 PM for ending time
            else if ($this->input->post('time_end') == 12 && $this->input->post('ending_ampm') == 2) {
                $data['time_end'] = 12;
            }
            // otherwise for ending time
            else{
                $data['time_end']       = (int)$this->input->post('time_end') + (12 * ((int)$this->input->post('ending_ampm') - 1));
            }

            $data['time_start_min'] = $this->input->post('time_start_min');
            $data['time_end_min']   = $this->input->post('time_end_min');
            $data['day']            = $this->input->post('day');
            $data['year']           = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            $data['term']           = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            if ($data['subject_id'] != '') {
            // checking duplication
            $array = array(
               'section_id'    => $data['section_id'],
               'class_id'      => $data['class_id'],
               'time_start'    => $data['time_start'],
               'time_end'      => $data['time_end'],
               'time_start_min'=> $data['time_start_min'],
               'time_end_min'  => $data['time_end_min'],
               'day'           => $data['day'],
               'year'          => $data['year'],
               'term'          => $data['term']
            );
            $validation = duplication_of_class_routine_on_edit($array, $param2);

            if ($validation == 1) {
                $this->db->where('class_routine_id', $param2);
                $this->db->update('class_routine', $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('data_updated')]);
            }
            else{
                echo json_encode(['status' => 'error', 'message' => get_phrase('time_conflicts')]);
            }
          }
          else{
            echo json_encode(['status' => 'error', 'message' => get_phrase('subject_is_not_found')]);
          }
          return;
        }
        else if ($param1 == 'edit') {
            $page_data['edit_data'] = $this->db->get_where('class_routine', array(
                'class_routine_id' => $param2
            ))->result_array();
        }
        if ($param1 == 'delete') {
            $class_id = $this->db->get_where('class_routine' , array('class_routine_id' => $param2))->row()->class_id;
            $this->db->where('class_routine_id', $param2);
            $this->db->delete('class_routine');
            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('teacher/class_routine_view/' . $class_id));
        }

    }

    function class_routine_add($class_id)
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        $page_data['page_name']  = 'class_routine_add';
        $page_data['class_id']  = $class_id;
        $page_data['page_title'] = get_phrase('add_class_time_table');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function class_routine_view($class_id)
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        $page_data['page_name']  = 'class_routine_view';
        $page_data['class_id']  =   $class_id;
        $page_data['page_title'] = get_phrase('class_time_table');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function class_routine_print_view($class_id , $section_id, $student_id = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect('login');
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['student_id'] =   $student_id;
        $this->load->view('backend/teacher/class_routine_print_view' , $page_data);
    }

    function get_class_section_subject($class_id)
    {
        $page_data['class_id'] = $class_id;
        $this->load->view('backend/teacher/class_routine_section_subject_selector' , $page_data);
    }

    function section_subject_edit($class_id , $class_routine_id)
    {
        $page_data['class_id']          =   $class_id;
        $page_data['class_routine_id']  =   $class_routine_id;
        $this->load->view('backend/teacher/class_routine_section_subject_edit' , $page_data);
    }

    /******* STUDENT ID BARCODE GENERATION AND PRINTING ***/
    //new code
    function print_id($id){
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        $data['id'] = $id;
        $this->load->view('backend/teacher/print_id', $data);
    }


    function create_barcode($student_id)
    {

        return $this->Barcode_model->create_barcode($student_id);

    }

    // STUDENT PROMOTION
    function student_promotion($param1 = '' , $param2 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        if($param1 == 'promote') {
            $running_year  =   $this->input->post('running_year');
            $running_term  =   $this->input->post('running_term');
            $running_sem  =   $this->input->post('running_sem');
            $from_class_id =   $this->input->post('promotion_from_class_id');

            $class_name = $this->db->get_where('class', array('class_id' => $from_class_id))->row()->name;

            if($class_name == 'JHSS') {
                $students_of_promotion_class =   $this->db->get_where('enroll' , array(
                    'class_id' => $from_class_id , 'mute' => '0', 'year' => $running_year, 'sem' => $running_sem
                ))->result_array();

                foreach($students_of_promotion_class as $row) {
                    $sections = $this->db->get_where('section', array('class_id' => $this->input->post('promotion_status_'.$row['student_id'])))->row_array();
                    $enroll_data['enroll_code']     =   substr(md5(rand(0, 1000000)), 0, 7);
                    $enroll_data['student_id']      =   $row['student_id'];
                    $enroll_data['class_id']        =   $this->input->post('promotion_status_'.$row['student_id']);
                    $enroll_data['section_id']      =   $sections['section_id'];
                    $enroll_data['year']            =   $this->input->post('promotion_year');
                    $enroll_data['sem']             =   $this->input->post('next_sem');
                    $enroll_data['date_added']      =   strtotime(date("Y-m-d H:i:s"));
                    $this->db->insert('enroll' , $enroll_data);
                }

            } else {
                $students_of_promotion_class =   $this->db->get_where('enroll' , array(
                    'class_id' => $from_class_id , 'mute' => '0', 'year' => $running_year, 'term' => $running_term
                ))->result_array();

                foreach($students_of_promotion_class as $row) {
                    $sections = $this->db->get_where('section', array('class_id' => $this->input->post('promotion_status_'.$row['student_id'])))->row_array();
                    $enroll_data['enroll_code']     =   substr(md5(rand(0, 1000000)), 0, 7);
                    $enroll_data['student_id']      =   $row['student_id'];
                    $enroll_data['class_id']        =   $this->input->post('promotion_status_'.$row['student_id']);
                    $enroll_data['section_id']      =   $sections['section_id'];
                    $enroll_data['year']            =   $this->input->post('promotion_year');
                    $enroll_data['term']            =   $this->input->post('next_term');
                    $enroll_data['date_added']      =   strtotime(date("Y-m-d H:i:s"));
                    $this->db->insert('enroll' , $enroll_data);
                }
            }
            
            $this->session->set_flashdata('flash_message' , get_phrase('selected_students_were_promoted_successfully'));
            redirect(site_url('teacher/student_promotion'));
        }

        $page_data['page_title']    = get_phrase('student_promotion');
        $page_data['page_name']  = 'student_promotion';
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function get_students_to_promote($class_id_from , $class_id_to , $running_year, $promotion_year, $running_term, $class_name)
    {   
        if($class_name == 'JHSS') {
            $page_data['running_sem']       =   $running_term;
            $page_data['class_name']        =   $class_name;
            $page_data['class_id_from']     =   $class_id_from;
            $page_data['class_id_to']       =   $class_id_to;
            $page_data['running_year']      =   $running_year;
            $page_data['promotion_year']    =   $promotion_year;
            $this->load->view('backend/teacher/student_promotion_selector' , $page_data);

        } else {
            $page_data['running_term']      =   $running_term;
            $page_data['class_name']        =   $class_name;
            $page_data['class_id_from']     =   $class_id_from;
            $page_data['class_id_to']       =   $class_id_to;
            $page_data['running_year']      =   $running_year;
            $page_data['promotion_year']    =   $promotion_year;
            $this->load->view('backend/teacher/student_promotion_selector' , $page_data);
        }
        
    }



    /****** DAILY ATTENDANCE *****************/
    function manage_attendance($class_id = '')
    {
        if($this->session->userdata('teacher_login')!=1)
            redirect(base_url() );

        $class_name = $this->db->get_where('class' , array(
            'class_id' => $class_id
        ))->row()->name;
        $class_name_numeric = $this->db->get_where('class' , array(
            'class_id' => $class_id
        ))->row()->name_numeric;
        $page_data['page_name']  =  'manage_attendance';
        $page_data['class_id']   =  $class_id;
        $page_data['page_title'] =  get_phrase('manage_attendance_of') . ' ' . $class_name.' '.$class_name_numeric;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function manage_attendance_view($class_id = '' , $section_id = '' , $timestamp = '')
    {
        if($this->session->userdata('teacher_login')!=1)
            redirect(site_url('login'));

        $class_name = $this->db->get_where('class' , array(
            'class_id' => $class_id
        ))->row()->name;
        $class_name_numeric = $this->db->get_where('class' , array(
            'class_id' => $class_id
        ))->row()->name_numeric;
        $page_data['class_id'] = $class_id;
        $page_data['timestamp'] = $timestamp;
        $page_data['page_name'] = 'manage_attendance_view';
        $section_name = $this->db->get_where('section' , array(
            'section_id' => $section_id
        ))->row()->name;
        $page_data['section_id'] = $section_id;
        $page_data['page_title'] = get_phrase('manage_attendance_of') . ' ' . $class_name .' '.$class_name_numeric. ' | ' . get_phrase('section') . ' ' . $section_name;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
    function get_section($class_id) {
          $page_data['class_id'] = $class_id;
          $this->load->view('backend/teacher/manage_attendance_section_holder' , $page_data);
    }

    //select all students in a particular class for this date and enter them into the attendance table in the database
    function attendance_selector($from_scanner='no', $class_id='', $section_id='', $year='', $term='', $sem='') {

        $class_name = $this->crud_model->get_class_name($class_id);

        if($class_name == 'JHSS') {
            //for JHS
            if($from_scanner == 'yes') { //requesting coming from barcode scanner page

                $data['class_id']   = $class_id;
                $data['year']       = $year;
                $data['sem']       = $sem;
                $data['timestamp']  = strtotime(date('d-m-Y'));//we won't send this from the scanner page.
                $data['section_id'] = $section_id;

            } else { //teacher taking normal attendance

                $data['class_id']   = $this->input->post('class_id');
                $data['year']       = $this->input->post('year');
                $data['sem']       = $this->input->post('sem');
                $data['timestamp']  = strtotime($this->input->post('timestamp'));
                $data['section_id'] = $this->input->post('section_id');
            }

            //check the attendance table and see if these records match any
            $query = $this->db->get_where('attendance' ,array(
                'class_id'=>$data['class_id'],
                    'section_id'=>$data['section_id'],
                        'year'=>$data['year'],
                            'sem'=>$data['sem'],
                                'timestamp'=>$data['timestamp']
            ));

            if($query->num_rows() < 1) {//no record match found

                /**Run a query from the enroll table with current year and current sem
                *if no match record is found, it means no student has been enrolled for this sem
                *thus, we need to do new enrollment for this sem, else, we continue with normal daily attendance mgt
                **/
                    $students_old = $this->db->get_where('enroll' , array(
                        'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'sem' => $data['sem']
                    ));
                    $students_old_array = $students_old->result_array();

                    if($students_old->num_rows() < 1) { //no record match found
                        //Let's do new enrollment into a new sem with same year

                        /**if sem is 1 and the running year is the same as the year retrieved from the enroll table, it means user is **trying to enroll students to sem 1 instead of doing promotion in the previous sem. inform the user to contact *the system administrator for help**/
                        $running_sem = $data['sem'];
                        $running_year = $this->db->get_where('enroll' , array(
                            'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'sem' => $running_sem))->row()->year;

                        if($running_sem == 1 && $running_year == $data['year']) {
                            $this->session->set_flashdata('error_message', get_phrase('make_sure_you_promote_students_during_semester_2._please_contact_the_administrator_for_assistance'));
                            redirect(site_url($this->session->userdata('login_type').'/manage_attendance'));
                        }
                        
                        /**if not, let's now enroll them into new sem by creating new enrollment for them from the 
                        *previous sem and insert them into the enroll table
                        *and subsequently entering their details into the attendance table
                        **/

                        $students_new = $this->db->get_where('enroll' , array(
                        'class_id' => $data['class_id'] , 'section_id' => $data['section_id'] , 'year' => $data['year'], 'sem' => $data['sem']-1
                        ))->result_array();

                        foreach($students_new as $row) {
                            $enroll_data['enroll_code']= substr(md5(rand(0, 1000000)), 0, 7);
                            $enroll_data['class_id']   = $data['class_id'];
                            $enroll_data['year']       = $data['year'];
                            $enroll_data['sem']        = $data['sem'];
                            $enroll_data['section_id'] = $data['section_id'];
                            $enroll_data['student_id'] = $row['student_id'];
                            $enroll_data['date_added'] =   strtotime(date("Y-m-d H:i:s"));
                            
                            $this->db->insert('enroll' , $enroll_data);

                            $attn_data['class_id']   = $data['class_id'];
                            $attn_data['year']       = $data['year'];
                            $attn_data['sem']        = $data['sem'];
                            $attn_data['timestamp']  = $data['timestamp'];
                            $attn_data['section_id'] = $data['section_id'];
                            $attn_data['student_id'] = $row['student_id'];

                            $this->db->insert('attendance' , $attn_data);

                            $this->db->where('student_id' , $row['student_id']);
                            $this->db->update('enroll', array('status_attendance' => 'close'));

                        }
                    }elseif($students_old->num_rows() > 0) {
                            //it is a normal daily attendance management. pull data from enroll table and insert them to att. tb
                            foreach($students_old_array as $row) {

                            $attn_data['class_id']   = $data['class_id'];
                            $attn_data['year']       = $data['year'];
                            $attn_data['sem']       = $data['sem'];
                            $attn_data['timestamp']  = $data['timestamp'];
                            $attn_data['section_id'] = $data['section_id'];
                            $attn_data['student_id'] = $row['student_id'];

                            $this->db->insert('attendance' , $attn_data);

                            $this->db->where('student_id' , $row['student_id']);
                            $this->db->update('enroll', array('status_attendance' => 'close'));
                            
                        }
                    }

            }elseif ($query->num_rows() > 0) {
                /**if we find some rows, it means these rows are the recent attendance marked and want to be updated. But let's find out if there are additional students that were enrolled within the sem and needs
                *to be added to the attendance register for further management processes
                **/
                 $students_lagged = $this->db->get_where('enroll' , array(
                    'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'sem' => $data['sem'], 'status_attendance' => 'open' 
                ))->result_array();
                foreach($students_lagged as $row) {
                    $attn_data['class_id']   = $data['class_id'];
                    $attn_data['year']       = $data['year'];
                    $attn_data['sem']        = $data['sem'];
                    $attn_data['timestamp']  = $data['timestamp'];
                    $attn_data['section_id'] = $data['section_id'];
                    $attn_data['student_id'] = $row['student_id'];
                    $this->db->insert('attendance' , $attn_data);

                    $this->db->where('student_id' , $row['student_id']);
                    $this->db->update('enroll', array('status_attendance' => 'close'));
                }
               
            }

        } else {
            //for others
            if($from_scanner == 'yes') { //requesting coming from barcode scanner page

                $data['class_id']   = $class_id;
                $data['year']       = $year;
                $data['term']       = $term;
                $data['timestamp']  = strtotime(date('d-m-Y'));//we won't send this from the scanner page.
                $data['section_id'] = $section_id;

            } else { //teacher taking normal attendance

                $data['class_id']   = $this->input->post('class_id');
                $data['year']       = $this->input->post('year');
                $data['term']       = $this->input->post('term');
                $data['timestamp']  = strtotime($this->input->post('timestamp'));
                $data['section_id'] = $this->input->post('section_id');
            }

            //check the attendance table and see if these records match any
            $query = $this->db->get_where('attendance' ,array(
                'class_id'=>$data['class_id'],
                    'section_id'=>$data['section_id'],
                        'year'=>$data['year'],
                            'term'=>$data['term'],
                                'timestamp'=>$data['timestamp']
            ));

            if($query->num_rows() < 1) {//no record match found

                /**Run a query from the enroll table with current year and current term
                *if no match record is found, it means no student has been enrolled for this term
                *thus, we need to do new enrollment for this term, else, we continue with normal daily attendance mgt
                **/
                    $students_old = $this->db->get_where('enroll' , array(
                        'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'term' => $data['term']
                    ));
                    $students_old_array = $students_old->result_array();

                    if($students_old->num_rows() < 1) { //no record match found
                        //Let's do new enrollment into a new term with same year

                        /**if term is 1 and the running year is the same as the year retrieved from the enroll table, it means user is **trying to enroll students to term 1 instead of doing promotion in the previous term. inform the user to contact *the system administrator for help**/
                        $running_term = $data['term'];
                        $running_year = $this->db->get_where('enroll' , array(
                            'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'term' => $running_term))->row()->year;

                        if($running_term == 1 && $running_year == $data['year']) {
                            $this->session->set_flashdata('error_message', get_phrase('make_sure_you_promote_students_during_term_3._please_contact_the_administrator_for_assistance'));
                            redirect(site_url($this->session->userdata('login_type').'/manage_attendance'));
                        }
                        
                        /**if not, let's now enroll them into new term by creating new enrollment for them from the 
                        *previous term and insert them into the enroll table
                        *and subsequently entering their details into the attendance table
                        **/

                        $students_new = $this->db->get_where('enroll' , array(
                        'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'term' => $data['term']-1
                        ))->result_array();

                        foreach($students_new as $row) {
                            $enroll_data['enroll_code']= substr(md5(rand(0, 1000000)), 0, 7);
                            $enroll_data['class_id']   = $data['class_id'];
                            $enroll_data['year']       = $data['year'];
                            $enroll_data['term']       = $data['term'];
                            $enroll_data['section_id'] = $data['section_id'];
                            $enroll_data['student_id'] = $row['student_id'];
                            $enroll_data['date_added'] =   strtotime(date("Y-m-d H:i:s"));
                            
                            $this->db->insert('enroll' , $enroll_data);

                            $attn_data['class_id']   = $data['class_id'];
                            $attn_data['year']       = $data['year'];
                            $attn_data['term']       = $data['term'];
                            $attn_data['timestamp']  = $data['timestamp'];
                            $attn_data['section_id'] = $data['section_id'];
                            $attn_data['student_id'] = $row['student_id'];

                            $this->db->insert('attendance' , $attn_data);

                            $this->db->where('student_id' , $row['student_id']);
                            $this->db->update('enroll', array('status_attendance' => 'close'));

                        }
                    }elseif($students_old->num_rows() > 0) {
                            //it is a normal daily attendance management. pull data from enroll table and insert them to att. tb
                            foreach($students_old_array as $row) {

                            $attn_data['class_id']   = $data['class_id'];
                            $attn_data['year']       = $data['year'];
                            $attn_data['term']       = $data['term'];
                            $attn_data['timestamp']  = $data['timestamp'];
                            $attn_data['section_id'] = $data['section_id'];
                            $attn_data['student_id'] = $row['student_id'];

                            $this->db->insert('attendance' , $attn_data);

                            $this->db->where('student_id' , $row['student_id']);
                            $this->db->update('enroll', array('status_attendance' => 'close'));
                            
                        }
                    }

            }elseif ($query->num_rows() > 0) {
                /**if we find some rows, it means these rows are the recent attendance marked and want to be updated. But let's find out if there are additional students that were enrolled within the term and needs
                *to be added to the attendance register for further management processes
                **/
                 $students_lagged = $this->db->get_where('enroll' , array(
                    'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'term' => $data['term'], 'status_attendance' => 'open' 
                ))->result_array();
                foreach($students_lagged as $row) {
                    $attn_data['class_id']   = $data['class_id'];
                    $attn_data['year']       = $data['year'];
                    $attn_data['term']       = $data['term'];
                    $attn_data['timestamp']  = $data['timestamp'];
                    $attn_data['section_id'] = $data['section_id'];
                    $attn_data['student_id'] = $row['student_id'];
                    $this->db->insert('attendance' , $attn_data);

                    $this->db->where('student_id' , $row['student_id']);
                    $this->db->update('enroll', array('status_attendance' => 'close'));
                }
               
            }
        }
        

        

        redirect(site_url($this->session->userdata('login_type').'/manage_attendance_view/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['timestamp']),'refresh');
    }

    function attendance_update($class_id = '' , $section_id = '' , $timestamp = '')
    {
        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $owner_email = $this->db->get_where('settings' , array('type' => 'system_email'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
        $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
        $attendance_of_students = $this->db->get_where('attendance' , array(
            'class_id'=>$class_id,'section_id'=>$section_id,'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$timestamp
        ))->result_array();
        foreach($attendance_of_students as $row) {
            $attendance_status = $this->input->post('status_'.$row['attendance_id']);
            $this->db->where('attendance_id' , $row['attendance_id']);
            $this->db->update('attendance' , array('status' => $attendance_status));

        // Daily fees are now handled by the new daily_fees_transactions system
        // This is managed in the Admin controller's attendance methods
        // No need to insert/update here as it's handled centrally
        //END OF DAILY FEES ENTRIES


        //insert or update transport_fare

        $fare_rows = $this->db->get_where('transport_fare' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$timestamp
        ));
        $st_fare_array = $fare_rows->result_array();


        if($fare_rows->num_rows() > 0) { //already exists, thus, update
           foreach($st_fare_array as $fare) {
             $fare_data2['amount_paid'] = $this->input->post('transport_paid_'.$row['student_id']);
             $fare_data2['due'] = $this->input->post('transport_owe_'.$row['student_id']);

             $this->db->where('student_id' , $row['student_id']);
             $this->db->where('timestamp' , $timestamp);
             $this->db->update('transport_fare', $fare_data2);
           }
        } else {
            //new entry
             $fare_data['amount_paid'] = $this->input->post('transport_paid_'.$row['student_id']);
             $fare_data['due'] = $this->input->post('transport_owe_'.$row['student_id']);
             $fare_data['student_id'] = $row['student_id'];
             $fare_data['year'] = $running_year;
             $fare_data['term'] = $running_term;
             $fare_data['class_id'] = $class_id;
             $fare_data['section_id'] = $section_id;
             $fare_data['timestamp'] = $timestamp;

             $this->db->insert('transport_fare', $fare_data); //insert
        } //END OF TRANSPORT FARE ENTRIES

            if ($attendance_status == 2) {

                if ($active_sms_service != 'disabled') {
                    $student_name   = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;
                    $parent_id      = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->parent_id;
                    $message        = 'Your child ' . $student_name . ' is absent from school today. Please let us know what is wrong. Thank You.';
                    if($parent_id != null && $parent_id != 0){
                        $receiver_phone = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->phone;
                        if($receiver_phone != '' || $receiver_phone != null){
                            $this->sms_model->send_sms($message,$receiver_phone);
                             $this->session->set_flashdata('flash_message' , get_phrase('notification_was_sent_to_the_parents_of_the_absentees_successfully.'));
                        }
                        else{
                            $this->session->set_flashdata('error_message' , get_phrase('parent\'s_phone_number_is_not_found._it_seems_you_did_not_add_phone_numbers_to_some_parents\'_details'));
                        }
                    }
                    else{
                        $this->session->set_flashdata('error_message' , get_phrase('no_parent_was_not_found_for_some_students.'));
                    }
                }

                //send email alert
                $student_name   = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;
                    $parent_id      = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->parent_id;
                    $message        = 'Your child ' . $student_name . ' is absent from school today. Please let us know what is wrong. Thank You.';
                    if($parent_id != null && $parent_id != 0){
                        $receiver_email = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->email;
                        if($receiver_email != '' || $receiver_email != null){
                            send_email($receiver_email, 'Student Absent Notification', $message, $owner_email);
                           $this->email_model->do_email($message, 'Student Absent Notification', $receiver_email, $owner_email);
                             $this->session->set_flashdata('flash_message' , get_phrase('notification_was_sent_to_the_parents_of_the_absentees_successfully.'));
                        }
                        else{
                            $this->session->set_flashdata('error_message' , get_phrase('parent\'s_email_is_not_found._it_seems_you_did_not_add_phone_numbers_to_some_parents\'_details'));
                        }
                    }
                    else{
                        $this->session->set_flashdata('error_message' , get_phrase('no_parent_was_not_found_for_some_students.'));
                    }
            }
        }
        $this->session->set_flashdata('flash_message' , get_phrase('attendance_updated'));
        redirect(site_url('teacher/manage_attendance_view/'.$class_id.'/'.$section_id.'/'.$timestamp) );
    }


    /****** DAILY ATTENDANCE *****************/
    function manage_attendance2($date='',$month='',$year='',$class_id='' , $section_id = '' , $session = '')
    {
        if($this->session->userdata('teacher_login')!=1)
            redirect(site_url('login') );

        $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;


        if($_POST)
        {
            // Loop all the students of $class_id
            $this->db->where('class_id' , $class_id);
            if($section_id != '') {
                $this->db->where('section_id' , $section_id);
            }
            //$session = base64_decode( urldecode( $session ) );
            $this->db->where('year' , $session);
            $this->db->where('mute', '0');
            $students = $this->db->get('enroll')->result_array();
            foreach ($students as $row)
            {
                $attendance_status  =   $this->input->post('status_' . $row['student_id']);

                $this->db->where('student_id' , $row['student_id']);
                $this->db->where('date' , $date);
                $this->db->where('year' , $year);
                $this->db->where('class_id' , $row['class_id']);
                if($row['section_id'] != '' && $row['section_id'] != 0) {
                    $this->db->where('section_id' , $row['section_id']);
                }
                $this->db->where('session' , $session);

                $this->db->update('attendance' , array('status' => $attendance_status));

                if ($attendance_status == 2) {

                    if ($active_sms_service != 'disabled') {
                        $student_name   = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;
                        $parent_id      = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->parent_id;
                        $receiver_phone = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->phone;
                        $message        = 'Your child' . ' ' . $student_name . 'is absent today.';
                        $this->sms_model->send_sms($message,$receiver_phone);
                    }
                }

            }

            $this->session->set_flashdata('flash_message' , get_phrase('data_updated'));
            redirect(site_url('teacher/manage_attendance/'.$date.'/'.$month.'/'.$year.'/'.$class_id.'/'.$section_id.'/'.$session) );
        }
        $page_data['date']       =  $date;
        $page_data['month']      =  $month;
        $page_data['year']       =  $year;
        $page_data['class_id']   =  $class_id;
        $page_data['section_id'] =  $section_id;
        $page_data['session']    =  $session;

        $page_data['page_name']  =  'manage_attendance';
        $page_data['page_title'] =  get_phrase('manage_daily_attendance');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
    function attendance_selector2()
    {
        //$session = $this->input->post('session');
        //$encoded_session = urlencode( base64_encode( $session ) );
        redirect(site_url('teacher/manage_attendance/'.$this->input->post('date').'/'.
                    $this->input->post('month').'/'.
                        $this->input->post('year').'/'.
                            $this->input->post('class_id').'/'.
                                $this->input->post('section_id').'/'.
                                    $this->input->post('session')) );
    }
        ///////ATTENDANCE REPORT /////
     function attendance_report() {
         $page_data['month']        = date('m');
         $page_data['page_name']    = 'attendance_report';
         $page_data['page_title']   = get_phrase('attendance_report');
         $this->load->view('backend/main',$page_data);
     }
     function attendance_report_view($class_id = '', $section_id = '', $month = '', $sessional_year = '', $term = '')
     {
         if($this->session->userdata('teacher_login')!=1)
            redirect(base_url() );

        $class_name                     = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
        $class_name_numeric             = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
        $section_name                   = $this->db->get_where('section', array('section_id' => $section_id))->row()->name;
        $page_data['class_id']          = $class_id;
        $page_data['section_id']        = $section_id;
        $page_data['month']             = $month;
        $page_data['sessional_year']    = $sessional_year;
        $page_data['term']              = $term;
        $page_data['page_name']         = 'attendance_report_view';
        $page_data['page_title']        = get_phrase('attendance_report_of') . ' ' . $class_name .' '.$class_name_numeric.' : ' . get_phrase('section') . ' ' . $section_name;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
     }
     function attendance_report_print_view($class_id ='' , $section_id = '' , $month = '', $sessional_year = '', $term = '') {
          if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());

        $page_data['class_id']          = $class_id;
        $page_data['section_id']        = $section_id;
        $page_data['month']             = $month;
        $page_data['sessional_year']    = $sessional_year;
        $page_data['term']              = $term;
        $this->load->view('backend/teacher/attendance_report_print_view' , $page_data);
    }

    function attendance_report_selector()
    {   if($this->input->post('class_id') == '' || $this->input->post('sessional_year') == '') {
            $this->session->set_flashdata('error_message' , get_phrase('please_make_sure_class_and_sessional_year_are_selected'));
            redirect(site_url('teacher/attendance_report'));
        }
        $data['class_id']       = $this->input->post('class_id');
        $data['section_id']     = $this->input->post('section_id');
        $data['month']          = $this->input->post('month');
        $data['sessional_year'] = $this->input->post('sessional_year');
        $data['term']           = $this->input->post('term');
        redirect(site_url('teacher/attendance_report_view/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['month'] . '/' . $data['sessional_year']). '/'. $data['term']);
    }

    /**********MANAGE LIBRARY / BOOKS********************/
    function book($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect('login');

        $page_data['books']      = $this->db->get('book')->result_array();
        $page_data['page_name']  = 'book';
        $page_data['page_title'] = get_phrase('manage_library_books');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }
    /**********MANAGE TRANSPORT / VEHICLES / ROUTES********************/
    function transport($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect('login');

        $page_data['transports'] = $this->db->get('transport')->result_array();
        $page_data['page_name']  = 'transport';
        $page_data['page_title'] = get_phrase('manage_transport');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }

    /***MANAGE EVENT / NOTICEBOARD, WILL BE SEEN BY ALL ACCOUNTS DASHBOARD**/
    function noticeboard($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(base_url());

        if ($param1 == 'create') {
            $data['notice_title']     = strtoupper($this->input->post('notice_title'));
            $data['notice']           = $this->input->post('notice');
            $data['create_timestamp'] = strtotime($this->input->post('create_timestamp'));
            $this->db->insert('noticeboard', $data);
            redirect(site_url('teacher/noticeboard/'));
        }
        if ($param1 == 'do_update') {
            $data['notice_title']     = $this->input->post('notice_title');
            $data['notice']           = $this->input->post('notice');
            $data['create_timestamp'] = strtotime($this->input->post('create_timestamp'));
            $this->db->where('notice_id', $param2);
            $this->db->update('noticeboard', $data);
            $this->session->set_flashdata('flash_message', get_phrase('notice_updated'));
            redirect(site_url('teacher/noticeboard/'));
        } else if ($param1 == 'edit') {
            $page_data['edit_data'] = $this->db->get_where('noticeboard', array(
                'notice_id' => $param2
            ))->result_array();
        }
        if ($param1 == 'delete') {
            $this->db->where('notice_id', $param2);
            $this->db->delete('noticeboard');
            redirect(site_url('teacher/noticeboard/'));
        }
        $page_data['page_name']  = 'noticeboard';
        $page_data['page_title'] = get_phrase('manage_noticeboard');
        $page_data['notices']    = $this->db->get_where('noticeboard',array('status'=>1))->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }


    /**********MANAGE DOCUMENT / home work FOR A SPECIFIC CLASS or ALL*******************/
    function document($do = '', $document_id = '')
    {
        if ($this->session->userdata('teacher_login') != 1)
            redirect('login');
        if ($do == 'upload') {
            move_uploaded_file($_FILES["userfile"]["tmp_name"], "uploads/document/" . $_FILES["userfile"]["name"]);
            $data['document_name'] = $this->input->post('document_name');
            $data['file_name']     = $_FILES["userfile"]["name"];
            $data['file_size']     = $_FILES["userfile"]["size"];
            $this->db->insert('document', $data);
            redirect(site_url('teacher/manage_document'));
        }
        if ($do == 'delete') {
            $this->db->where('document_id', $document_id);
            $this->db->delete('document');
            redirect(site_url('teacher/manage_document'));
        }
        $page_data['page_name']  = 'manage_document';
        $page_data['page_title'] = get_phrase('manage_documents');
        $page_data['documents']  = $this->db->get('document')->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    /*********MANAGE STUDY MATERIAL************/
    function study_material($task = "", $document_id = "")
    {
        if ($this->session->userdata('teacher_login') != 1)
        {
            $this->session->set_userdata('last_page' , current_url());
            redirect(base_url());
        }

        if ($task == "create")
        {
            try {
                $this->crud_model->save_study_material_info();
                echo json_encode(['status' => 'success', 'message' => get_phrase('study_material_info_saved_successfuly')]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        if ($task == "update")
        {
            try {
                $this->crud_model->update_study_material_info($document_id);
                echo json_encode(['status' => 'success', 'message' => get_phrase('study_material_info_updated_successfuly')]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        if ($task == "delete")
        {
            $this->crud_model->delete_study_material_info($document_id);
            $this->session->set_flashdata('flash_message' , get_phrase('study_material_deleted_successfully'));
            redirect(site_url('teacher/study_material/'));
        }

        $data['study_material_info']    = $this->crud_model->select_study_material_info_for_teacher();
        $data['page_name']              = 'study_material';
        $data['page_title']             = get_phrase('study_material');
        $this->load->view('backend/main', $data);
    }

    /* private messaging */

    function message($param1 = 'message_home', $param2 = '', $param3 = '') {
        if ($this->session->userdata('teacher_login') != 1)
        {
            $this->session->set_userdata('last_page' , current_url());
            redirect(base_url());
        }

        $max_size = 4097152;
        if ($param1 == 'send_new') {

            // Folder creation
            if (!file_exists('uploads/private_messaging_attached_file/')) {
              $oldmask = umask(0);  // helpful when used in linux server
              mkdir ('uploads/private_messaging_attached_file/', 0777);
            }
            if ($_FILES['attached_file_on_messaging']['name'] != "") {
              if($_FILES['attached_file_on_messaging']['size'] > $max_size){
                echo json_encode(['status' => 'error', 'message' => get_phrase('file_size_can_not_be_larger_that_4_Megabyte')]);
                return;
              }
              else{
                $file_path = 'uploads/private_messaging_attached_file/'.$_FILES['attached_file_on_messaging']['name'];
                move_uploaded_file($_FILES['attached_file_on_messaging']['tmp_name'], $file_path);
              }
            }
            $message_thread_code = $this->crud_model->send_new_private_message();
            echo json_encode(['status' => 'success', 'message' => get_phrase('message_sent!'), 'redirect' => site_url('teacher/message/message_read/'.$message_thread_code)]);
            return;
        }

        if ($param1 == 'send_reply') {

            if (!file_exists('uploads/private_messaging_attached_file/')) {
              $oldmask = umask(0);  // helpful when used in linux server
              mkdir ('uploads/private_messaging_attached_file/', 0777);
            }
            if ($_FILES['attached_file_on_messaging']['name'] != "") {
              if($_FILES['attached_file_on_messaging']['size'] > $max_size){
                echo json_encode(['status' => 'error', 'message' => get_phrase('file_size_can_not_be_larger_that_4_Megabyte')]);
                return;
              }
              else{
                $file_path = 'uploads/private_messaging_attached_file/'.$_FILES['attached_file_on_messaging']['name'];
                move_uploaded_file($_FILES['attached_file_on_messaging']['tmp_name'], $file_path);
              }
            }
            $this->crud_model->send_reply_message($param2);  //$param2 = message_thread_code
            echo json_encode(['status' => 'success', 'message' => get_phrase('message_sent!')]);
            return;
        }

        if ($param1 == 'message_read') {
            $page_data['current_message_thread_code'] = $param2;  // $param2 = message_thread_code
            $this->crud_model->mark_thread_messages_read($param2);
        }

         if ($param1 == 'delete') {
            $this->db->where('message_thread_code', $param2);
            $this->db->delete('message_thread');
            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('teacher/message'));
        }

        $page_data['message_inner_page_name']   = $param1;
        $page_data['page_name']                 = 'message';
        $page_data['page_title']                = get_phrase('private_messaging');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    //GROUP MESSAGE
    function group_message($param1 = "group_message_home", $param2 = ""){
      if ($this->session->userdata('teacher_login') != 1)
          redirect(base_url());
      $max_size = 4097152;

      if ($param1 == 'group_message_read') {
        $page_data['current_message_thread_code'] = $param2;
      }
      else if($param1 == 'send_reply'){
        if (!file_exists('uploads/group_messaging_attached_file/')) {
          $oldmask = umask(0);  // helpful when used in linux server
          mkdir ('uploads/group_messaging_attached_file/', 0777);
        }
        if ($_FILES['attached_file_on_messaging']['name'] != "") {
          if($_FILES['attached_file_on_messaging']['size'] > $max_size){
            echo json_encode(['status' => 'error', 'message' => get_phrase('file_size_can_not_be_larger_that_4_Megabyte')]);
            return;
          }
          else{
            $file_path = 'uploads/group_messaging_attached_file/'.$_FILES['attached_file_on_messaging']['name'];
            move_uploaded_file($_FILES['attached_file_on_messaging']['tmp_name'], $file_path);
          }
        }

        $this->crud_model->send_reply_group_message($param2);  //$param2 = message_thread_code
        echo json_encode(['status' => 'success', 'message' => get_phrase('message_sent!')]);
        return;
      }

      if ($param1 == 'delete') {
        $this->db->where('group_message_thread_code', $param2);
        $this->db->delete('group_message_thread');
        $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
        redirect(site_url('teacher/group_message'));
     }

      $page_data['message_inner_page_name']   = $param1;
      $page_data['page_name']                 = 'group_message';
      $page_data['page_title']                = get_phrase('group_messaging');
      

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    // MANAGE QUESTION PAPERS
    function question_paper($param1 = "", $param2 = "")
    {
        if ($this->session->userdata('teacher_login') != 1)
        {
            $this->session->set_userdata('last_page', current_url());
            redirect(base_url());
        }

        if ($param1 == "create")
        {
            $this->crud_model->create_question_paper();
            $this->session->set_flashdata('flash_message', get_phrase('data_created_successfully'));
            redirect(site_url('teacher/question_paper'));
        }

        if ($param1 == "update")
        {
            $this->crud_model->update_question_paper($param2);
            $this->session->set_flashdata('flash_message', get_phrase('data_updated_successfully'));
            redirect(site_url('teacher/question_paper'));
        }

        if ($param1 == "delete")
        {
            $this->crud_model->delete_question_paper($param2);
            $this->session->set_flashdata('flash_message', get_phrase('data_deleted_successfully'));
            redirect(site_url('teacher/question_paper'));
        }

        $data['page_name']  = 'question_paper';
        $data['page_title'] = get_phrase('question_paper');
        $this->load->view('backend/main', $data);
    }

    // Details of searched student
    function student_details(){
      if ($this->session->userdata('teacher_login') != 1)
          redirect(base_url());

      $student_identifier = $this->input->post('student_identifier');
      $query_by_code = $this->db->get_where('student', array('student_code' => $student_identifier));

      if ($query_by_code->num_rows() == 0) {
        $this->db->like('name', $student_identifier);
        $query_by_name = $this->db->get('student');
        if ($query_by_name->num_rows() == 0) {
          $this->session->set_flashdata('error_message' , get_phrase('no_student_found'));
            redirect(site_url('teacher/dashboard'));
        }
        else{
          $page_data['student_information'] = $query_by_name->result_array();
        }
      }
      else{
        $page_data['student_information'] = $query_by_code->result_array();
      }
      $page_data['page_name']  	= 'search_result';
  		$page_data['page_title'] 	= get_phrase('search_result');
  		

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function get_teachers() {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        $columns = array(
            0 => 'teacher_id',
            1 => 'photo',
            2 => 'name',
            3 => 'email',
            4 => 'phone',
            5 => 'teacher_id'
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->all_teachers_count();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {            
            $teachers = $this->ajaxload->all_teachers($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value']; 
            $teachers =  $this->ajaxload->teacher_search($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->teacher_search_count($search);
        }

        $data = array();
        if(!empty($teachers)) {
            foreach ($teachers as $row) {

                $photo = '<img src="'.$this->crud_model->get_image_url('teacher', $row->teacher_id, $row->sex).'" class="img-circle" width="30" />';

                $nestedData['teacher_id'] = $row->teacher_id;
                $nestedData['photo'] = $photo;
                $nestedData['name'] = $row->name;
                $nestedData['email'] = '<a href="mailto:'.$row->email.'">'.$row->email.'</a>';
                $nestedData['phone'] = $row->phone;
                
                $data[] = $nestedData;
            }
        }
          
        $json_data = array(
            "draw"            => intval($this->input->post('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );
            
        echo json_encode($json_data);
    }

    function get_books() {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

         $columns = array(
            0 => 'book_id',
            1 => 'name',
            2 => 'author',
            3 => 'description',
            4 => 'price',
            5 => 'class',
            6 => 'download',
            7 => 'options',
            8 => 'book_id'
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->all_books_count();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $books = $this->ajaxload->all_books($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $books =  $this->ajaxload->book_search($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->book_search_count($search);
        }

        $data = array();
        if(!empty($books)) {
            foreach ($books as $row) {
                if ($row->file_name == null)
                    $download = '';
                else
                    $download = '<a href="'.site_url("uploads/document/$row->file_name").'" class="btn btn-blue btn-icon icon-left"><i class="entypo-download"></i>'.get_phrase('download').'</a>';

                $options = '<div class="btn-group">'.get_action_button().'<ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="book_edit_modal('.$row->book_id.')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;'.get_phrase('edit').'</a></li><li class="divider"></li><li><a href="#" onclick="book_delete_confirm('.$row->book_id.')" style="color: red;"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';

                $nestedData['book_id'] = $row->book_id;
                $nestedData['name'] = $row->name;
                $nestedData['author'] = $row->author;
                $nestedData['description'] = $row->description;
                $nestedData['price'] = $row->price;
                $nestedData['class'] = $this->db->get_where('class', array('class_id' => $row->class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $row->class_id))->row()->name_numeric;
                $nestedData['download'] = $download;
                $nestedData['options'] = $options;

                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        echo json_encode($json_data);
    }

    // online exam
    function manage_online_exam($param1 = "", $param2 = ""){
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        if ($param1 == '') {
            $match = array('status !=' => 'expired', 'running_year' => $running_year, 'term' => $running_term);
            $page_data['status'] = 'active';
            $this->db->order_by("exam_date", "dsc");
            $page_data['online_exams'] = $this->db->where($match)->get('online_exam')->result_array();
        }

        if ($param1 == 'expired') {
            $match = array('status' => 'expired', 'running_year' => $running_year, 'term' => $running_term);
            $page_data['status'] = 'expired';
            $this->db->order_by("exam_date", "dsc");
            $page_data['online_exams'] = $this->db->where($match)->get('online_exam')->result_array();
        }

        if ($param1 == 'create') {
            if ($this->input->post('class_id') > 0 && $this->input->post('section_id') > 0 && $this->input->post('subject_id') > 0) {
                $this->crud_model->create_online_exam();
                echo json_encode(['status' => 'success', 'message' => get_phrase('data_added_successfully')]);
            }
            else {
                echo json_encode(['status' => 'error', 'message' => get_phrase('make_sure_to_select_valid_class_').','.get_phrase('_section_and_subject')]);
            }
            return;
        }
        if ($param1 == 'edit') {
            if ($this->input->post('class_id') > 0 && $this->input->post('section_id') > 0 && $this->input->post('subject_id') > 0) {
                $this->crud_model->update_online_exam();
                echo json_encode(['status' => 'success', 'message' => get_phrase('data_updated_successfully')]);
            }
            else{
                echo json_encode(['status' => 'error', 'message' => get_phrase('make_sure_to_select_valid_class_').','.get_phrase('_section_and_subject')]);
            }
            return;
        }
        if ($param1 == 'delete') {
            $this->db->where('online_exam_id', $param2);
            $this->db->delete('online_exam');
            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('teacher/manage_online_exam'));
        }
        $page_data['page_name'] = 'manage_online_exam';
        $page_data['page_title'] = get_phrase('manage_online_exam');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function online_exam_questions_print_view($online_exam_id, $answers) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        $page_data['online_exam_id'] = $online_exam_id;
        $page_data['answers'] = $answers;
        $page_data['page_title'] = get_phrase('questions_print');
        $this->load->view('backend/teacher/online_exam_questions_print_view', $page_data);
    }

    function create_online_exam(){
        $page_data['page_name'] = 'add_online_exam';
        $page_data['page_title'] = get_phrase('add_an_online_exam');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function update_online_exam($param1 = ""){
        $page_data['online_exam_id'] = $param1;
        $page_data['page_name'] = 'edit_online_exam';
        $page_data['page_title'] = get_phrase('update_online_exam');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function manage_online_exam_status($online_exam_id = "", $status = ""){
        $this->crud_model->manage_online_exam_status($online_exam_id, $status);
        redirect(site_url('teacher/manage_online_exam'));
    }

    function load_question_type($type, $online_exam_id) {
        $page_data['question_type'] = $type;
        $page_data['online_exam_id'] = $online_exam_id;
        $this->load->view('backend/teacher/online_exam_add_'.$type, $page_data);
    }

    function manage_online_exam_question($online_exam_id = "", $task = "", $type = ""){
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        if ($task == 'add') {
            if ($type == 'multiple_choice') {
                $this->crud_model->add_multiple_choice_question_to_online_exam($online_exam_id);
            }
            elseif ($type == 'true_false') {
                $this->crud_model->add_true_false_question_to_online_exam($online_exam_id);
            }
            elseif ($type == 'fill_in_the_blanks') {
                $this->crud_model->add_fill_in_the_blanks_question_to_online_exam($online_exam_id);
            }
            redirect(site_url('teacher/manage_online_exam_question/'.$online_exam_id));
        }

        $page_data['online_exam_id'] = $online_exam_id;
        $page_data['page_name'] = 'manage_online_exam_question';
        $page_data['page_title'] = $this->db->get_where('online_exam', array('online_exam_id'=>$online_exam_id))->row()->title;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function update_online_exam_question($question_id = "", $task = "", $online_exam_id = "") {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        $online_exam_id = $this->db->get_where('question_bank', array('question_bank_id' => $question_id))->row()->online_exam_id;
        $type = $this->db->get_where('question_bank', array('question_bank_id' => $question_id))->row()->type;
        if ($task == "update") {
            if ($type == 'multiple_choice') {
                $this->crud_model->update_multiple_choice_question($question_id);
            }
            elseif($type == 'true_false'){
                $this->crud_model->update_true_false_question($question_id);
            }
            elseif($type == 'fill_in_the_blanks'){
                $this->crud_model->update_fill_in_the_blanks_question($question_id);
            }
            redirect(site_url('teacher/manage_online_exam_question/'.$online_exam_id));
        }
        $page_data['question_id'] = $question_id;
        $page_data['page_name'] = 'update_online_exam_question';
        $page_data['page_title'] = get_phrase('update_question');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function delete_question_from_online_exam($question_id){
        $online_exam_id = $this->db->get_where('question_bank', array('question_bank_id' => $question_id))->row()->online_exam_id;
        $this->crud_model->delete_question_from_online_exam($question_id);
        $this->session->set_flashdata('flash_message' , get_phrase('question_deleted'));
        redirect(site_url('teacher/manage_online_exam_question/'.$online_exam_id));
    }

    function manage_multiple_choices_options() {
        $page_data['number_of_options'] = $this->input->post('number_of_options');
        $this->load->view('backend/teacher/manage_multiple_choices_options', $page_data);
    }

    function get_sections_for_ssph($class_id) {
        $sections = $this->db->get_where('section', array('class_id' => $class_id))->result_array();
        $options = '';
        foreach ($sections as $row) {
            $options .= '<option value="'.$row['section_id'].'">'.$row['name'].'</option>';
        }
        echo '<select class="" name="section_id" id="section_id">'.$options.'</select>';
    }

    function get_students_for_ssph($class_id, $section_id) {
        $enrolls = $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', 'section_id' => $section_id))->result_array();
        $options = '';
        foreach ($enrolls as $row) {
            $name = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name;
            $options .= '<option value="'.$row['student_id'].'">'.$name.'</option>';
        }
        echo '<select class="" name="student_id" id="student_id">'.$options.'</select>';
    }

    function get_payment_history_for_ssph($student_id) {
        $page_data['student_id'] = $student_id;
        $this->load->view('backend/teacher/student_specific_payment_history_table', $page_data);
    }

    function view_online_exam_result($online_exam_id){
        $page_data['page_name'] = 'view_online_exam_results';
        $page_data['page_title'] = get_phrase('result');
        $page_data['online_exam_id'] = $online_exam_id;
        $this->load->view('backend/main',$page_data);
    }

    function get_class_section_selector($class_id){
        $page_data['class_id'] = $class_id;
        $this->load->view('backend/teacher/get_class_section_selector', $page_data);
    }

    function get_class_subject_selector($class_id){
        $page_data['class_id'] = $class_id;
        $this->load->view('backend/teacher/get_class_subject_selector', $page_data);
    }

    //print students info
    function student_information_print($class_id, $running_year, $running_term) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        $page_data['class_id'] = $class_id;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['page_name']    = 'student_information_print';
        $this->load->view('backend/teacher/student_information_print', $page_data);

    }

    //bulk marksheet printing
    function student_marksheet_bulk_print_view($class_id, $section_id) {
        if ($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));

        $exam_id = $this->input->post('exam_id');

        $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        $page_data['year']       =   $this->db->get_where('exam', array('exam_id' => $exam_id))->row()->year;
        $page_data['term']       =   $this->db->get_where('exam', array('exam_id' => $exam_id))->row()->term;

        if($exam_id != '' || $exam_id != '' && $class_id != "" || $class_id != "" && $section_id != '' || $section_id != '') {
            $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;

            $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;

            if($raw_score == 'Yes') {
                    if($class_name == 'JHSS'){
                    $this->load->view('backend/teacher/student_raw_score_marksheet_bulk_print_view', $page_data);
                }else{
                    //load the chosen exam report style
                
                    if($terminal_report_style == 'style_1') {
                        $this->load->view('backend/admin/student_marksheet_bulk_print_view', $page_data);
                    } elseif($terminal_report_style == 'style_2') {
                        $this->load->view('backend/admin/student_marksheet_bulk_print_view_2', $page_data);
                    } else {
                        $this->load->view('backend/admin/student_marksheet_bulk_print_view_3', $page_data);
                    }
                }
            }elseif($raw_score == 'No') {
                //load the chosen exam report style
                
                if($terminal_report_style == 'style_1') {
                    $this->load->view('backend/admin/student_marksheet_bulk_print_view', $page_data);
                } elseif($terminal_report_style == 'style_2') {
                    $this->load->view('backend/admin/student_marksheet_bulk_print_view_2', $page_data);
                } else {
                    $this->load->view('backend/admin/student_marksheet_bulk_print_view_3', $page_data);
                }
            }
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('teacher/student_information'), 'refreh');
        }
    }//bulk marksheet printing ends

    //for creche subject update according to category
   function update_subjects_creche($cat_id, $class_id) {
       $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
       $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
        
       $subjects = $this->db->get_where('subject_creche', array('category_id' => $cat_id, 'class_id' => $class_id, 'year' => $running_year, 'term' => $running_term))->result_array();
       
       foreach($subjects as $row) {
           echo '<option value="'. $row['subject_id'].'">'.$row['name'].'</option>';
       }
   }

   function payslip_preview($staffCode, $month, $year, $employment_category) {

        $this->load->model('Payroll_statutory_model');
        
        $staffData = $this->payroll_model->getPayrollFormByStaffCode($staffCode, $month, $year);
        
        $pageData['staffPayrollData'] = $staffData;
        $pageData['staffCode'] = $staffCode;
        $pageData['payMonth'] = $month;
        $pageData['payYear'] = $year;
        $pageData['employmentCategory'] = $employment_category;
        $pageData['statutory_rates'] = $this->Payroll_statutory_model->get_all_settings();

        $this->load->view('backend/admin/payslip_preview', $pageData);

    }

    function payslipList($teacher_code) {

        $pageData['staffPayrollData'] = $this->payroll_model->getStaffPayroll($teacher_code);
        $pageData['page_name'] = 'payslip_list';
        $pageData['page_title'] = 'Staffs Payslip List';
        $this->load->view('backend/index', $pageData);

    }

    // Daily Fee Payment Report
    function daily_payment_report($class_id = '') {
        if($this->session->userdata('teacher_login') != 1)
            redirect(site_url('login'));
        
        $page_data['class_id'] = $class_id;
        $page_data['page_name'] = 'daily_payment_report';
        $page_data['page_title'] = get_phrase('daily_payment_report');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    // Get students with payments (AJAX)
    function get_students_with_payments() {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $class_id = $this->input->post('class_id');
        
        $start_timestamp = strtotime($start_date . ' 00:00:00');
        $end_timestamp = strtotime($end_date . ' 00:00:00');
        
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        
        $this->db->select('SUM(t.feeding_amount) as feeding_paid, SUM(t.breakfast_amount) as breakfast_paid, SUM(t.classes_amount) as classes_paid, SUM(t.water_amount) as water_paid, SUM(t.transport_amount) as transport_paid, s.name, s.student_code');
        $this->db->from('daily_fee_transactions t');
        $this->db->join('student s', 's.student_id = t.student_id');
        $this->db->join('enroll e', 'e.student_id = t.student_id AND e.year = "' . $running_year . '" AND e.term = ' . $running_term);
        $this->db->where('t.payment_date >=', $start_timestamp);
        $this->db->where('t.payment_date <=', $end_timestamp + 86399);
        
        if (!empty($class_id)) {
            $this->db->where('e.class_id', $class_id);
        } else {
            // Empty class = report across ALL classes this teacher is
            // associated with (class-teacher of the class or teaches a subject in it)
            $teacher_id = $this->session->userdata('teacher_id');
            $my_class_ids = array();
            
            $ct = $this->db->select('class_id')->where('teacher_id', $teacher_id)->get('class')->result_array();
            foreach ($ct as $r) { $my_class_ids[] = $r['class_id']; }
            
            $st = $this->db->select('class_id')
                ->where(array('teacher_id' => $teacher_id, 'year' => $running_year, 'term' => $running_term))
                ->get('subject')->result_array();
            foreach ($st as $r) { $my_class_ids[] = $r['class_id']; }
            
            $my_class_ids = array_unique($my_class_ids);
            if (empty($my_class_ids)) {
                echo json_encode(['status' => 'success', 'data' => array()]);
                return;
            }
            $this->db->where_in('e.class_id', $my_class_ids);
        }
        $this->db->where('(t.feeding_amount > 0 OR t.breakfast_amount > 0 OR t.classes_amount > 0 OR t.water_amount > 0 OR t.transport_amount > 0)');
        $this->db->group_by('t.student_id');
        $this->db->order_by('s.name', 'ASC');
        
        $result = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $result]);
    }

    function marks_selector_creche() {
        $data['exam_id'] = $this->input->post('exam_id');
        $data['class_id'] = $this->input->post('class_id');
        $data['section_id'] = $this->input->post('section_id');
        $data['subject_id'] = $this->input->post('subject_id');
        $data2['category_id'] = $this->input->post('category_id');
        $data['year'] = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $data['term'] = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        if ($data['subject_id'] == '') {
            echo 'no subject';
            return false;
        }

        if ($data['class_id'] != '' && $data['exam_id'] != '') {
            $query = $this->db->get_where('mark', array(
                'exam_id' => $data['exam_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'subject_id' => $data['subject_id'],
                'year' => $data['year'],
                'term' => $data['term'],
            ));

            $students = $this->db->get_where('enroll', array(
                'class_id' => $data['class_id'], 'mute' => '0', 'section_id' => $data['section_id'], 'year' => $data['year'], 'term' => $data['term'],
            ));

            if ($query->num_rows() < 1) {
                if ($students->num_rows() < 1) {
                    echo 'no enrollment';
                    return false;
                } else {
                    $array_students = $students->result_array();
                    foreach ($array_students as $row) {
                        $data['student_id'] = $row['student_id'];
                        $this->db->insert('mark', $data);

                        $this->db->where('student_id', $row['student_id']);
                        $this->db->update('enroll', array('status' => 'close'));

                        $subject_status = $this->db->get_where('subject_creche', array('subject_id' => $data['subject_id'], 'class_id' => $data['class_id'], 'year' => $data['year'], 'term' => $data['term']))->row()->status;

                        $this->db->where('subject_id', $data['subject_id']);
                        $this->db->where('class_id', $data['class_id']);
                        $this->db->where('year', $data['year']);
                        $this->db->where('term', $data['term']);
                        $this->db->where('section_id', $data['section_id']);
                        $this->db->update('mark', array('status' => $subject_status));
                    }
                }
            } elseif ($query->num_rows() > 0) {
                $sIds = [];
                foreach($query->result_array() as $st) {
                    array_push($sIds, $st['student_id']);
                }

                $this->db->where_not_in('student_id', $sIds);
                $students = $this->db->get_where('enroll', array(
                    'class_id' => $data['class_id'], 'mute' => '0', 'section_id' => $data['section_id'], 'year' => $data['year'], 'term' => $data['term']
                ))->result_array();

                foreach ($students as $row) {
                    $data['student_id'] = $row['student_id'];
                    $this->db->insert('mark', $data);

                    $this->db->where('student_id', $row['student_id']);
                    $this->db->update('enroll', array('status' => 'close'));

                    $subject_status = $this->db->get_where('subject', array('subject_id' => $data['subject_id'], 'class_id' => $data['class_id'], 'year' => $data['year'], 'term' => $data['term']))->row()->status;

                    $this->db->where('subject_id', $data['subject_id']);
                    $this->db->where('class_id', $data['class_id']);
                    $this->db->where('year', $data['year']);
                    $this->db->where('term', $data['term']);
                    $this->db->where('section_id', $data['section_id']);
                    $this->db->update('mark', array('status' => $subject_status));
                }
            }

            if ($data2['category_id'] == '0') {
                echo site_url('teacher/marks_manage_view_creche2/' . $data['exam_id'] . '/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['subject_id'] . '/' . $data2['category_id']);
            } else {
                echo site_url('teacher/marks_manage_view_creche/' . $data['exam_id'] . '/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['subject_id'] . '/' . $data2['category_id']);
            }
        }
    }


    // ============================================
    // GES LESSON NOTE SYSTEM METHODS
    // ============================================

    /**
     * Display list of teacher's lesson notes
     * Requirements: 1.1, 11.1
     */
    function lesson_notes() {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        $this->load->model('Lesson_note_model');

        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Get filters from GET
        $filters = array(
            'status' => $this->input->get('status'),
            'subject_id' => $this->input->get('subject_id'),
            'class_id' => $this->input->get('class_id'),
            'term' => $this->input->get('term') ?: $running_term,
            'week_number' => $this->input->get('week_number')
        );

        $page_data['lesson_notes'] = $this->Lesson_note_model->get_lesson_notes_by_teacher($teacher_id, $filters);
        $page_data['teacher_id'] = $teacher_id;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['filters'] = $filters;

        // Get status counts for summary cards
        $page_data['status_counts'] = $this->Lesson_note_model->get_status_counts(array('teacher_id' => $teacher_id));

        // Get teacher's subjects and classes for filters
        $page_data['subjects'] = $this->db->query(
            "SELECT DISTINCT s.subject_id, s.name 
             FROM subject s 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY s.name",
            array($teacher_id, $running_year, $running_term)
        )->result();

        $page_data['classes'] = $this->db->query(
            "SELECT DISTINCT c.class_id, c.name, c.name_numeric 
             FROM class c 
             JOIN subject s ON s.class_id = c.class_id 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY c.name_numeric",
            array($teacher_id, $running_year, $running_term)
        )->result();

        $page_data['page_name'] = 'lesson_notes';
        $page_data['page_title'] = get_phrase('lesson_notes');
        $page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
    }


    /**
     * Display lesson note progress dashboard
     * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8
     */
    function lesson_note_progress() {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        $this->load->model('Lesson_note_model');

        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Get term and year from filters
        $term = $this->input->get('term') ?: $running_term;
        $year = $this->input->get('year') ?: $running_year;

        // Get progress data
        $page_data['progress_data'] = $this->Lesson_note_model->get_completion_progress($teacher_id, $term, $year);
        $page_data['missing_notes'] = $this->Lesson_note_model->get_missing_lesson_notes($teacher_id, $term, $year);

        $page_data['teacher_id'] = $teacher_id;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['selected_term'] = $term;
        $page_data['selected_year'] = $year;

        $page_data['page_name'] = 'lesson_note_progress';
        $page_data['page_title'] = get_phrase('lesson_note_progress');
        $page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
    }

    /**
     * Create a new lesson note
     * Requirements: 1.1, 1.9, 7.2
     */
    function lesson_note_create() {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        $this->load->model(array('Lesson_note_model', 'Curriculum_model'));

        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Handle form submission
        if ($this->input->post('submit')) {
            $this->_save_lesson_note($teacher_id);
            return;
        }

        // Check if teacher is a class teacher (form teacher)
        $is_class_teacher = $this->db->get_where('class', array('teacher_id' => $teacher_id))->num_rows() > 0;
        $page_data['is_class_teacher'] = $is_class_teacher;

        // Get classes where teacher is assigned as class teacher
        $class_teacher_classes = $this->db->query(
            "SELECT c.class_id, c.name, c.name_numeric 
             FROM class c 
             WHERE c.teacher_id = ?
             ORDER BY c.name, c.name_numeric",
            array($teacher_id)
        )->result();

        // Get classes and subjects where teacher teaches (subject teacher)
        $subject_teacher_data = $this->db->query(
            "SELECT DISTINCT c.class_id, c.name, c.name_numeric, s.subject_id, s.name as subject_name
             FROM class c 
             JOIN subject s ON s.class_id = c.class_id 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY c.name, c.name_numeric, s.name",
            array($teacher_id, $running_year, $running_term)
        )->result();

        // Build class-subject mapping for subject teachers
        $classes_with_subjects = array();
        $subjects_by_class = array();
        foreach ($subject_teacher_data as $row) {
            if (!isset($classes_with_subjects[$row->class_id])) {
                $classes_with_subjects[$row->class_id] = array(
                    'class_id' => $row->class_id,
                    'name' => $row->name,
                    'name_numeric' => $row->name_numeric
                );
            }
            if (!isset($subjects_by_class[$row->class_id])) {
                $subjects_by_class[$row->class_id] = array();
            }
            $subjects_by_class[$row->class_id][] = array(
                'subject_id' => $row->subject_id,
                'name' => $row->subject_name
            );
        }

        $page_data['class_teacher_classes'] = $class_teacher_classes;
        $page_data['classes_with_subjects'] = array_values($classes_with_subjects);
        $page_data['subjects_by_class'] = $subjects_by_class;

        // For backward compatibility - all subjects the teacher teaches
        $page_data['subjects'] = $this->db->query(
            "SELECT DISTINCT s.subject_id, s.name, s.class_id
             FROM subject s 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY s.name",
            array($teacher_id, $running_year, $running_term)
        )->result();

        $page_data['classes'] = $this->db->query(
            "SELECT DISTINCT c.class_id, c.name, c.name_numeric 
             FROM class c 
             JOIN subject s ON s.class_id = c.class_id 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY c.name_numeric",
            array($teacher_id, $running_year, $running_term)
        )->result();

        // Get core competencies
        $page_data['core_competencies'] = $this->db->get('core_competencies')->result();

        // Get teaching resources master
        $page_data['teaching_resources'] = $this->db->get('teaching_resources_master')->result();

        // Get assessment methods master
        $page_data['assessment_methods'] = $this->db->get('assessment_methods_master')->result();

        $page_data['page_name'] = 'lesson_note_create';
        $page_data['page_title'] = get_phrase('create_lesson_note');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['edit_mode'] = false;
        $page_data['lesson_note'] = null;

        $this->load->view('backend/main', $page_data);
    }

    /**
     * Edit an existing lesson note
     * Requirements: 1.1, 1.9
     */
    function lesson_note_edit($lesson_note_id = '') {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        if (empty($lesson_note_id)) {
            redirect(site_url('teacher/lesson_notes'));
        }

        $this->load->model(array('Lesson_note_model', 'Curriculum_model'));

        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Get the lesson note
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);

        // Verify ownership
        if (!$lesson_note || $lesson_note->teacher_id != $teacher_id) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_not_found'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Only allow editing of drafts and revision_requested
        if (!in_array($lesson_note->status, array('draft', 'revision_requested'))) {
            $this->session->set_flashdata('error_message', get_phrase('cannot_edit_submitted_lesson_note'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Handle form submission
        if ($this->input->post('submit')) {
            $this->_save_lesson_note($teacher_id, $lesson_note_id);
            return;
        }

        // Get teacher's subjects and classes
        $page_data['subjects'] = $this->db->query(
            "SELECT DISTINCT s.subject_id, s.name 
             FROM subject s 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY s.name",
            array($teacher_id, $running_year, $running_term)
        )->result();

        $page_data['classes'] = $this->db->query(
            "SELECT DISTINCT c.class_id, c.name, c.name_numeric 
             FROM class c 
             JOIN subject s ON s.class_id = c.class_id 
             WHERE s.teacher_id = ? AND s.year = ? AND s.term = ?
             ORDER BY c.name_numeric",
            array($teacher_id, $running_year, $running_term)
        )->result();

        // Get core competencies
        $page_data['core_competencies'] = $this->db->get('core_competencies')->result();

        // Get teaching resources master
        $page_data['teaching_resources'] = $this->db->get('teaching_resources_master')->result();

        // Get assessment methods master
        $page_data['assessment_methods'] = $this->db->get('assessment_methods_master')->result();

        $page_data['page_name'] = 'lesson_note_create';
        $page_data['page_title'] = get_phrase('edit_lesson_note');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['edit_mode'] = true;
        $page_data['lesson_note'] = $lesson_note;
        $page_data['lesson_note_id'] = $lesson_note_id;

        $this->load->view('backend/main', $page_data);
    }

    /**
     * Delete a lesson note
     * Requirements: 1.1
     */
    function lesson_note_delete($lesson_note_id = '') {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        if (empty($lesson_note_id)) {
            redirect(site_url('teacher/lesson_notes'));
        }

        $this->load->model('Lesson_note_model');

        $teacher_id = $this->session->userdata('teacher_id');
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);

        // Verify ownership
        if (!$lesson_note || $lesson_note->teacher_id != $teacher_id) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_not_found'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Only allow deletion of drafts
        if ($lesson_note->status != 'draft') {
            $this->session->set_flashdata('error_message', get_phrase('can_only_delete_drafts'));
            redirect(site_url('teacher/lesson_notes'));
        }

        $result = $this->Lesson_note_model->delete_lesson_note($lesson_note_id);

        if ($result) {
            $this->session->set_flashdata('flash_message', get_phrase('lesson_note_deleted_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('failed_to_delete_lesson_note'));
        }

        redirect(site_url('teacher/lesson_notes'));
    }

    /**
     * Copy a lesson note
     * Requirements: 11.2, 11.3, 11.4, 11.5, 11.6, 11.7
     */
    function lesson_note_copy($source_id = '') {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        if (empty($source_id)) {
            redirect(site_url('teacher/lesson_notes'));
        }

        $this->load->model('Lesson_note_model');

        $teacher_id = $this->session->userdata('teacher_id');
        $source = $this->Lesson_note_model->get_lesson_note($source_id);

        // Verify ownership
        if (!$source || $source->teacher_id != $teacher_id) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_not_found'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Create copy with current week and date
        $new_data = array(
            'week_number' => $this->input->get('week_number') ?: date('W'),
            'term' => $this->db->get_where('settings', array('type' => 'running_term'))->row()->description,
            'lesson_date' => date('Y-m-d')
        );

        $new_id = $this->Lesson_note_model->copy_lesson_note($source_id, $new_data);

        if ($new_id) {
            $this->session->set_flashdata('flash_message', get_phrase('lesson_note_copied_successfully'));
            redirect(site_url('teacher/lesson_note_edit/' . $new_id));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('failed_to_copy_lesson_note'));
            redirect(site_url('teacher/lesson_notes'));
        }
    }

    /**
     * Get curriculum strands via AJAX
     * Requirements: 2.1
     */
    function get_curriculum_strands() {
        $class_id = $this->input->get('class_id');
        $subject_id = $this->input->get('subject_id');

        if (empty($class_id) || empty($subject_id)) {
            echo json_encode(array('status' => 'error', 'message' => 'Missing parameters'));
            return;
        }

        $this->load->model('Curriculum_model');

        // Get class name numeric for class level
        $class = $this->db->get_where('class', array('class_id' => $class_id))->row();
        $class_level = $class ? $class->name_numeric : null;

        $strands = $this->Curriculum_model->get_strands_by_subject_class($subject_id, $class_level);

        echo json_encode(array('status' => 'success', 'data' => $strands));
    }

    /**
     * Get curriculum sub-strands via AJAX
     * Requirements: 2.2
     */
    function get_curriculum_sub_strands() {
        $strand_id = $this->input->get('strand_id');

        if (empty($strand_id)) {
            echo json_encode(array('status' => 'error', 'message' => 'Missing strand_id'));
            return;
        }

        $this->load->model('Curriculum_model');
        $sub_strands = $this->Curriculum_model->get_sub_strands_by_strand($strand_id);

        echo json_encode(array('status' => 'success', 'data' => $sub_strands));
    }

    /**
     * Get curriculum content standards via AJAX
     * Requirements: 2.3
     */
    function get_curriculum_content_standards() {
        $sub_strand_id = $this->input->get('sub_strand_id');

        if (empty($sub_strand_id)) {
            echo json_encode(array('status' => 'error', 'message' => 'Missing sub_strand_id'));
            return;
        }

        $this->load->model('Curriculum_model');
        $content_standards = $this->Curriculum_model->get_content_standards_by_sub_strand($sub_strand_id);

        echo json_encode(array('status' => 'success', 'data' => $content_standards));
    }

    /**
     * Get curriculum learning indicators via AJAX
     * Requirements: 2.4
     */
    function get_curriculum_learning_indicators() {
        $content_standard_id = $this->input->get('content_standard_id');

        if (empty($content_standard_id)) {
            echo json_encode(array('status' => 'error', 'message' => 'Missing content_standard_id'));
            return;
        }

        $this->load->model('Curriculum_model');
        $indicators = $this->Curriculum_model->get_learning_indicators_by_content_standard($content_standard_id);

        echo json_encode(array('status' => 'success', 'data' => $indicators));
    }

    /**
     * Internal method to save lesson note (create or update)
     */
    private function _save_lesson_note($teacher_id, $lesson_note_id = null) {
        $this->load->model('Lesson_note_model');

        // Collect form data
        $data = array(
            'teacher_id' => $teacher_id,
            'class_id' => $this->input->post('class_id'),
            'subject_id' => $this->input->post('subject_id'),
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'week_number' => $this->input->post('week_number'),
            'term' => $this->input->post('term'),
            'lesson_date' => $this->input->post('lesson_date'),
            'strand_id' => $this->input->post('strand_id') ?: null,
            'sub_strand_id' => $this->input->post('sub_strand_id') ?: null,
            'content_standard_id' => $this->input->post('content_standard_id') ?: null,
            'lesson_objectives' => $this->input->post('lesson_objectives'),
            'lesson_activities' => $this->input->post('lesson_activities'),
            'lesson_content' => $this->input->post('lesson_content'),
            'status' => $this->input->post('save_action') == 'submit' ? 'pending' : 'draft'
        );

        // Handle file upload
        if (!empty($_FILES['lesson_file']['name'])) {
            $upload_result = $this->_upload_lesson_file();
            if ($upload_result['success']) {
                $data['file_path'] = $upload_result['file_path'];
                $data['file_name'] = $upload_result['file_name'];
                $data['file_type'] = $upload_result['file_type'];
            }
        }

        // Determine if create or update
        if ($lesson_note_id) {
            $result = $this->Lesson_note_model->update_lesson_note($lesson_note_id, $data, $teacher_id);
        } else {
            $lesson_note_id = $this->Lesson_note_model->create_lesson_note($data);
            $result = $lesson_note_id ? true : false;
        }

        if ($result && $lesson_note_id) {
            // Save related data
            $this->Lesson_note_model->save_learning_indicators($lesson_note_id, $this->input->post('learning_indicators'));
            $this->Lesson_note_model->save_core_competencies($lesson_note_id, $this->input->post('core_competencies'));

            // Save teaching resources
            $resources = $this->_prepare_resources_data();
            $this->Lesson_note_model->save_teaching_resources($lesson_note_id, $resources);

            // Save assessment methods
            $assessments = $this->_prepare_assessments_data();
            $this->Lesson_note_model->save_assessment_methods($lesson_note_id, $assessments);

            // Save reference materials
            $references = $this->_prepare_references_data();
            $this->Lesson_note_model->save_reference_materials($lesson_note_id, $references);

            $this->session->set_flashdata('flash_message', get_phrase('lesson_note_saved_successfully'));
            echo json_encode(array('status' => 'success', 'redirect' => site_url('teacher/lesson_notes')));
        } else {
            echo json_encode(array('status' => 'error', 'message' => get_phrase('failed_to_save_lesson_note')));
        }
    }

    /**
     * Upload lesson note file
     */
    private function _upload_lesson_file() {
        $config = array(
            'upload_path' => './uploads/lesson_notes/',
            'allowed_types' => 'pdf|doc|docx|ppt|pptx|xls|xlsx|jpg|jpeg|png|gif',
            'max_size' => 10240, // 10MB
            'encrypt_name' => TRUE
        );

        $this->load->library('upload', $config);

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }

        if ($this->upload->do_upload('lesson_file')) {
            $upload_data = $this->upload->data();
            return array(
                'success' => true,
                'file_path' => 'uploads/lesson_notes/' . $upload_data['file_name'],
                'file_name' => $_FILES['lesson_file']['name'],
                'file_type' => $upload_data['file_ext']
            );
        }

        return array('success' => false, 'error' => $this->upload->display_errors());
    }

    /**
     * Prepare resources data from form
     */
    private function _prepare_resources_data() {
        $resources = array();
        $resource_names = $this->input->post('resource_name');
        $resource_details = $this->input->post('resource_details');
        $resource_quantities = $this->input->post('resource_quantity');
        $resource_custom = $this->input->post('resource_custom');

        if (is_array($resource_names)) {
            foreach ($resource_names as $i => $name) {
                if (!empty($name)) {
                    $resources[] = array(
                        'resource_name' => $name,
                        'resource_details' => $resource_details[$i] ?? null,
                        'quantity' => $resource_quantities[$i] ?? null,
                        'is_custom' => isset($resource_custom[$i]) ? 1 : 0
                    );
                }
            }
        }

        return $resources;
    }

    /**
     * Prepare assessments data from form
     */
    private function _prepare_assessments_data() {
        $assessments = array();
        $method_names = $this->input->post('assessment_method');
        $method_notes = $this->input->post('assessment_notes');
        $method_custom = $this->input->post('assessment_custom');

        if (is_array($method_names)) {
            foreach ($method_names as $i => $name) {
                if (!empty($name)) {
                    $assessments[] = array(
                        'method_name' => $name,
                        'notes' => $method_notes[$i] ?? null,
                        'is_custom' => isset($method_custom[$i]) ? 1 : 0
                    );
                }
            }
        }

        return $assessments;
    }

    /**
     * Prepare references data from form
     */
    private function _prepare_references_data() {
        $references = array();
        $ref_titles = $this->input->post('reference_title');
        $ref_authors = $this->input->post('reference_author');
        $ref_publishers = $this->input->post('reference_publisher');
        $ref_years = $this->input->post('reference_year');
        $ref_pages = $this->input->post('reference_pages');
        $ref_urls = $this->input->post('reference_url');
        $ref_types = $this->input->post('reference_type');

        if (is_array($ref_titles)) {
            foreach ($ref_titles as $i => $title) {
                if (!empty($title)) {
                    $references[] = array(
                        'title' => $title,
                        'author' => $ref_authors[$i] ?? null,
                        'publisher' => $ref_publishers[$i] ?? null,
                        'year' => $ref_years[$i] ?? null,
                        'page_numbers' => $ref_pages[$i] ?? null,
                        'url' => $ref_urls[$i] ?? null,
                        'reference_type' => $ref_types[$i] ?? 'supplementary'
                    );
                }
            }
        }

        return $references;
    }

    /**
     * View Lesson Note Details with Revision History
     * Requirements: 18.5, 18.6, 18.7
     */
    function lesson_note_view($lesson_note_id = '') {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        if (empty($lesson_note_id)) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_id_required'));
            redirect(site_url('teacher/lesson_notes'));
        }

        $this->load->model('Lesson_note_model');

        $teacher_id = $this->session->userdata('teacher_id');
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);

        if (!$lesson_note) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_not_found'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Verify ownership
        if ($lesson_note->teacher_id != $teacher_id) {
            $this->session->set_flashdata('error_message', get_phrase('access_denied'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Get revision history
        $page_data['revision_history'] = $this->Lesson_note_model->get_revision_history($lesson_note_id);
        $page_data['lesson_note'] = $lesson_note;
        $page_data['page_name'] = 'lesson_note_view';
        $page_data['page_title'] = get_phrase('lesson_note_details');
        $page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
    }

    /**
     * Print Lesson Note (GES-Compliant Format)
     * Requirements: 14.1-14.9
     */
    function lesson_note_print($lesson_note_id = '') {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        if (empty($lesson_note_id)) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_id_required'));
            redirect(site_url('teacher/lesson_notes'));
        }

        $this->load->model('Lesson_note_model');

        $teacher_id = $this->session->userdata('teacher_id');
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);

        if (!$lesson_note) {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_not_found'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Verify ownership or approved status
        if ($lesson_note->teacher_id != $teacher_id && $lesson_note->status != 'approved') {
            $this->session->set_flashdata('error_message', get_phrase('access_denied'));
            redirect(site_url('teacher/lesson_notes'));
        }

        // Get school information
        $page_data['school_name'] = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
        $page_data['school_address'] = $this->db->get_where('settings', array('type' => 'address'))->row()->description;
        $page_data['school_phone'] = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
        $page_data['school_email'] = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
        $page_data['running_year'] = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;

        $page_data['lesson_note'] = $lesson_note;
        $page_data['revision_history'] = $this->Lesson_note_model->get_revision_history($lesson_note_id);

        // Load print view (no main template)
        $this->load->view('backend/teacher/lesson_note_print', $page_data);
    }

    /**
     * View all lesson note notifications page (legacy)
     * Requirements: 17.6, 20.4
     * @deprecated Use notifications() at line 3665 instead
     */
    function notifications_legacy() {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }

        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        
        // Get all notifications
        $notifications = $this->Lesson_note_model->get_notifications($teacher_id, 'teacher', false);
        
        // Format notifications
        foreach ($notifications as &$notification) {
            $notification->time_ago = $this->time_ago($notification->created_at);
            
            // Generate link
            if ($notification->reference_type == 'lesson_note' && !empty($notification->reference_id)) {
                $notification->link = site_url('teacher/lesson_note_view/' . $notification->reference_id);
            } else {
                $notification->link = '#';
            }
        }
        
        $page_data['notifications'] = $notifications;
        $page_data['page_name'] = 'notifications';
        $page_data['page_title'] = get_phrase('notifications');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        $this->load->view('backend/main', $page_data);
    }

    /**
     * Helper function to convert timestamp to "time ago" format
     */
    private function time_ago($timestamp) {
        $time = strtotime($timestamp);
        $time_difference = time() - $time;
        
        if ($time_difference < 1) {
            return 'Just now';
        }
        
        $condition = array(
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        );
        
        foreach ($condition as $secs => $str) {
            $d = $time_difference / $secs;
            
            if ($d >= 1) {
                $t = round($d);
                return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
            }
        }
    }
    
    /**
     * Get lesson note notifications for teacher
     * AJAX endpoint for notification system
     * 
     * Requirements: 17.6, 17.7, 20.4
     */
    function get_lesson_note_notifications() {
        if ($this->session->userdata('teacher_login') != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        $limit = $this->input->get('limit') ?? 10;
        
        // Get notifications
        $notifications = $this->Lesson_note_model->get_notifications_for_user($teacher_id, 'teacher', $limit);
        
        // Get unread count
        $unread_count = $this->Lesson_note_model->get_unread_notification_count($teacher_id, 'teacher');
        
        // Add URLs to notifications
        foreach ($notifications as &$notification) {
            $notification->url = $this->generate_notification_url($notification);
        }
        
        echo json_encode([
            'status' => 'success',
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
    }
    
    /**
     * Mark notification as read
     * 
     * Requirements: 17.7
     */
    function mark_notification_read() {
        if ($this->session->userdata('teacher_login') != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $this->load->model('Lesson_note_model');
        
        $notification_id = $this->input->post('notification_id');
        $teacher_id = $this->session->userdata('teacher_id');
        
        if (empty($notification_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Notification ID required']);
            return;
        }
        
        $result = $this->Lesson_note_model->mark_notification_read($notification_id, $teacher_id, 'teacher');
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark notification as read']);
        }
    }
    
    /**
     * Mark all notifications as read
     * 
     * Requirements: 17.7
     */
    function mark_all_notifications_read() {
        if ($this->session->userdata('teacher_login') != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        
        $result = $this->Lesson_note_model->mark_all_notifications_read($teacher_id, 'teacher');
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark notifications as read']);
        }
    }
    
    /**
     * View all notifications page
     * 
     * Requirements: 17.7
     */
    function notifications() {
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }
        
        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        
        // Get all notifications (paginated)
        $page = $this->input->get('page') ?? 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $notifications = $this->Lesson_note_model->get_notifications_for_user($teacher_id, 'teacher', $per_page, $offset);
        $total_count = $this->Lesson_note_model->get_notification_count($teacher_id, 'teacher');
        
        // Add URLs to notifications
        foreach ($notifications as &$notification) {
            $notification->url = $this->generate_notification_url($notification);
        }
        
        $page_data['notifications'] = $notifications;
        $page_data['total_count'] = $total_count;
        $page_data['current_page'] = $page;
        $page_data['total_pages'] = ceil($total_count / $per_page);
        $page_data['page_name'] = 'notifications';
        $page_data['page_title'] = get_phrase('notifications');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Generate URL for notification based on type
     * 
     * @param object $notification
     * @return string
     */
    private function generate_notification_url($notification) {
        $base = base_url();
        
        switch ($notification->reference_type) {
            case 'lesson_note_approved':
            case 'lesson_note_declined':
            case 'lesson_note_revision':
                return $base . 'teacher/lesson_note_view/' . $notification->reference_id;
            
            default:
                return $base . 'teacher/lesson_notes';
        }
    }



    // ========================================================================
    // LESSON NOTE TEMPLATES MANAGEMENT
    // ========================================================================
    
    /**
     * Display lesson note templates list
     */
    public function lesson_note_templates() {
        if ($this->session->userdata('login_type') != 'teacher') {
            redirect(base_url(), 'refresh');
        }
        
        $page_data['page_name'] = 'lesson_note_templates';
        $page_data['page_title'] = get_phrase('lesson_note_templates');
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Get lesson note templates (AJAX)
     */
    public function get_lesson_note_templates() {
        $teacher_id = $this->session->userdata('login_user_id');
        $school_id = $this->session->userdata('school_id');
        
        // Get templates created by this teacher or shared templates
        $this->db->select('t.*, s.name as subject_name, c.name as class_name, 
                          CONCAT(te.name, " ", te.surname) as teacher_name');
        $this->db->from('lesson_note_templates t');
        $this->db->join('subject s', 't.subject_id = s.subject_id', 'left');
        $this->db->join('class c', 't.class_id = c.id', 'left');
        $this->db->join('teacher te', 't.created_by = te.teacher_id', 'left');
        $this->db->where('t.school_id', $school_id);
        $this->db->group_start();
        $this->db->where('t.created_by', $teacher_id);
        $this->db->or_where('t.is_shared', 1);
        $this->db->group_end();
        $this->db->order_by('t.created_at', 'DESC');
        
        $templates = $this->db->get()->result_array();
        
        echo json_encode([
            'status' => 'success',
            'templates' => $templates
        ]);
    }
    
    /**
     * Create new template form
     */
    public function lesson_note_template_create() {
        if ($this->session->userdata('login_type') != 'teacher') {
            redirect(base_url(), 'refresh');
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $school_id = $this->session->userdata('school_id');
        
        // Load necessary data
        $this->load->model('Lesson_note_template_model');
        
        // Get teacher's subjects
        $this->db->distinct();
        $this->db->select('s.*');
        $this->db->from('subject s');
        $this->db->join('teacher_subject ts', 'ts.subject_id = s.subject_id');
        $this->db->where('ts.teacher_id', $teacher_id);
        $this->db->where('s.school_id', $school_id);
        $this->db->order_by('s.name', 'ASC');
        $page_data['subjects'] = $this->db->get()->result();
        
        // Get teacher's classes
        $page_data['classes'] = $this->get_teacher_classes($teacher_id);
        
        // Get all tags
        $page_data['tags'] = $this->Lesson_note_template_model->get_all_tags();
        
        $page_data['page_name'] = 'lesson_note_template_form';
        $page_data['page_title'] = get_phrase('create_template');
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Edit template form
     */
    public function lesson_note_template_edit($template_id) {
        if ($this->session->userdata('login_type') != 'teacher') {
            redirect(base_url(), 'refresh');
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $school_id = $this->session->userdata('school_id');
        
        $this->load->model('Lesson_note_template_model');
        
        // Get template and verify access
        $template = $this->Lesson_note_template_model->get_template($template_id, $teacher_id);
        
        if (!$template || $template->teacher_id != $teacher_id) {
            $this->session->set_flashdata('error_message', get_phrase('template_not_found'));
            redirect('teacher/lesson_note_templates', 'refresh');
        }
        
        $page_data['template'] = $template;
        
        // Get teacher's subjects
        $this->db->distinct();
        $this->db->select('s.*');
        $this->db->from('subject s');
        $this->db->join('teacher_subject ts', 'ts.subject_id = s.subject_id');
        $this->db->where('ts.teacher_id', $teacher_id);
        $this->db->where('s.school_id', $school_id);
        $this->db->order_by('s.name', 'ASC');
        $page_data['subjects'] = $this->db->get()->result();
        
        // Get teacher's classes
        $page_data['classes'] = $this->get_teacher_classes($teacher_id);
        
        // Get all tags
        $page_data['tags'] = $this->Lesson_note_template_model->get_all_tags();
        
        $page_data['page_name'] = 'lesson_note_template_form';
        $page_data['page_title'] = get_phrase('edit_template');
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Save template (create or update)
     */
    public function save_lesson_note_template() {
        if ($this->session->userdata('login_type') != 'teacher') {
            redirect(base_url(), 'refresh');
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $template_id = $this->input->post('template_id');
        
        $this->load->model('Lesson_note_template_model');
        
        // Prepare data
        $data = [
            'teacher_id' => $teacher_id,
            'template_name' => $this->input->post('template_name'),
            'description' => $this->input->post('description'),
            'subject_id' => $this->input->post('subject_id') ?: null,
            'class_id' => $this->input->post('class_id') ?: null,
            'teaching_methods' => array_filter(explode("\n", $this->input->post('teaching_methods') ?? '')),
            'learning_activities' => array_filter(explode("\n", $this->input->post('learning_activities') ?? '')),
            'assessment_methods' => array_filter(explode("\n", $this->input->post('assessment_methods') ?? '')),
            'resources' => array_filter(explode("\n", $this->input->post('resources') ?? '')),
            'differentiation_strategies' => $this->input->post('differentiation_strategies'),
            'homework_assignment' => $this->input->post('homework_assignment'),
            'reflection_notes' => $this->input->post('reflection_notes'),
            'is_public' => $this->input->post('is_public') ? 1 : 0,
            'tag_ids' => $this->input->post('tag_ids') ?? []
        ];
        
        if ($template_id) {
            // Update existing template
            $success = $this->Lesson_note_template_model->update_template($template_id, $data, $teacher_id);
            
            if ($success) {
                $this->session->set_flashdata('flash_message', get_phrase('template_updated_successfully'));
            } else {
                $this->session->set_flashdata('error_message', get_phrase('failed_to_update_template'));
            }
        } else {
            // Create new template
            $new_template_id = $this->Lesson_note_template_model->create_template($data);
            
            if ($new_template_id) {
                $this->session->set_flashdata('flash_message', get_phrase('template_created_successfully'));
            } else {
                $this->session->set_flashdata('error_message', get_phrase('failed_to_create_template'));
            }
        }
        
        redirect('teacher/lesson_note_templates', 'refresh');
    }
    
    /**
     * Delete template
     */
    public function delete_lesson_note_template() {
        $teacher_id = $this->session->userdata('login_user_id');
        $template_id = $this->input->post('template_id');
        
        // Verify ownership before deleting
        $this->db->where('template_id', $template_id);
        $this->db->where('created_by', $teacher_id);
        $this->db->delete('lesson_note_templates');
        
        if ($this->db->affected_rows() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => get_phrase('template_deleted_successfully')
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('template_not_found_or_no_permission')
            ]);
        }
    }
    
    /**
     * Use template to create new lesson note
     */
    public function use_lesson_note_template() {
        $teacher_id = $this->session->userdata('login_user_id');
        $school_id = $this->session->userdata('school_id');
        $template_id = $this->input->post('template_id');
        
        // Get template
        $template = $this->db->get_where('lesson_note_templates', [
            'template_id' => $template_id,
            'school_id' => $school_id
        ])->row_array();
        
        if (!$template) {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('template_not_found')
            ]);
            return;
        }
        
        // Create new lesson note from template
        $lesson_data = [
            'school_id' => $school_id,
            'teacher_id' => $teacher_id,
            'template_id' => $template_id,
            'subject_id' => $template['subject_id'],
            'class_id' => $template['class_id'],
            'topic' => $template['topic_template'],
            'objectives' => $template['objectives_template'],
            'introduction' => $template['introduction_template'],
            'main_content' => $template['content_template'],
            'conclusion' => $template['conclusion_template'],
            'assessment_methods' => $template['assessment_template'],
            'homework' => $template['homework_template'],
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('lesson_notes', $lesson_data);
        $lesson_note_id = $this->db->insert_id();
        
        // Update template usage statistics
        $this->db->where('template_id', $template_id);
        $this->db->set('usage_count', 'usage_count + 1', FALSE);
        $this->db->set('last_used_at', date('Y-m-d H:i:s'));
        $this->db->update('lesson_note_templates');
        
        echo json_encode([
            'status' => 'success',
            'lesson_note_id' => $lesson_note_id,
            'message' => get_phrase('lesson_note_created_from_template')
        ]);
    }
    
    /**
     * Get lesson note templates with filters (AJAX)
     * Enhanced version with full filtering support
     */
    public function get_lesson_note_templates_ajax() {
        if ($this->session->userdata('login_type') != 'teacher') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $this->load->model('Lesson_note_template_model');
        
        $filters = [
            'search' => $this->input->get('search'),
            'subject_id' => $this->input->get('subject_id'),
            'class_id' => $this->input->get('class_id'),
            'tag_id' => $this->input->get('tag_id'),
            'favorites_only' => $this->input->get('favorites_only'),
            'sort_by' => $this->input->get('sort_by') ?? 'usage_count',
            'sort_order' => $this->input->get('sort_order') ?? 'DESC'
        ];
        
        $templates = $this->Lesson_note_template_model->get_teacher_templates($teacher_id, $filters);
        
        echo json_encode([
            'status' => 'success',
            'templates' => $templates
        ]);
    }
    
    /**
     * Toggle template favorite (AJAX)
     */
    public function toggle_template_favorite() {
        if ($this->session->userdata('login_type') != 'teacher') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $template_id = $this->input->post('template_id');
        
        if (!$template_id) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_template')]);
            return;
        }
        
        $this->load->model('Lesson_note_template_model');
        $is_favorited = $this->Lesson_note_template_model->toggle_favorite($template_id, $teacher_id);
        
        echo json_encode([
            'status' => 'success',
            'is_favorited' => $is_favorited,
            'message' => $is_favorited ? get_phrase('added_to_favorites') : get_phrase('removed_from_favorites')
        ]);
    }
    
    /**
     * Duplicate template (AJAX)
     */
    public function duplicate_template() {
        if ($this->session->userdata('login_type') != 'teacher') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $template_id = $this->input->post('template_id');
        
        if (!$template_id) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_template')]);
            return;
        }
        
        $this->load->model('Lesson_note_template_model');
        $new_template_id = $this->Lesson_note_template_model->duplicate_template($template_id, $teacher_id);
        
        if ($new_template_id) {
            echo json_encode([
                'status' => 'success',
                'new_template_id' => $new_template_id,
                'message' => get_phrase('template_duplicated_successfully')
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('failed_to_duplicate_template')
            ]);
        }
    }
    
    /**
     * Delete template (AJAX) - Enhanced version
     */
    public function delete_template() {
        if ($this->session->userdata('login_type') != 'teacher') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $template_id = $this->input->post('template_id');
        
        if (!$template_id) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_template')]);
            return;
        }
        
        $this->load->model('Lesson_note_template_model');
        $success = $this->Lesson_note_template_model->delete_template($template_id, $teacher_id);
        
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => get_phrase('template_deleted_successfully')
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('failed_to_delete_template')
            ]);
        }
    }
    
    /**
     * Apply template to lesson note creation
     */
    public function apply_template_to_lesson_note() {
        if ($this->session->userdata('login_type') != 'teacher') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $teacher_id = $this->session->userdata('login_user_id');
        $template_id = $this->input->post('template_id');
        
        if (!$template_id) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_template')]);
            return;
        }
        
        $this->load->model('Lesson_note_template_model');
        $template = $this->Lesson_note_template_model->get_template($template_id, $teacher_id);
        
        if (!$template) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('template_not_found')]);
            return;
        }
        
        // Increment usage count
        $this->Lesson_note_template_model->increment_usage($template_id);
        
        // Return template data for form population
        echo json_encode([
            'status' => 'success',
            'template' => [
                'teaching_methods' => json_decode($template->teaching_methods ?? '[]', true),
                'learning_activities' => json_decode($template->learning_activities ?? '[]', true),
                'assessment_methods' => json_decode($template->assessment_methods ?? '[]', true),
                'resources' => json_decode($template->resources ?? '[]', true),
                'differentiation_strategies' => $template->differentiation_strategies,
                'homework_assignment' => $template->homework_assignment,
                'reflection_notes' => $template->reflection_notes
            ],
            'message' => get_phrase('template_applied_successfully')
        ]);
    }

	// AJAX endpoint to get students by class for exam reports
	function get_students_by_class() {
		$class_id = $this->input->post('class_id');
		$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		$enrolls = $this->db->get_where('enroll', array('class_id' => $class_id, 'year' => $running_year, 'term' => $running_term, 'mute' => '0'))->result_array();
		echo '<option value="">'.get_phrase('select').'</option>';
		foreach($enrolls as $enroll) {
			$student = $this->db->get_where('student', array('student_id' => $enroll['student_id']))->row();
			echo '<option value="'.$student->student_id.'">'.$student->name.' ('.$student->student_code.')</option>';
		}
	}

	function get_exams_by_class() {
		$class_id = $this->input->post('class_id');
		
		// Get all exams with marks for this class, ordered by date descending
		$this->db->select('exam.exam_id, exam.name, exam.year, exam.term, exam.date');
		$this->db->from('exam');
		$this->db->join('mark', 'mark.exam_id = exam.exam_id', 'inner');
		$this->db->where('mark.class_id', $class_id);
		$this->db->group_by('exam.exam_id');
		$this->db->order_by('exam.year', 'DESC');
		$this->db->order_by('exam.term', 'DESC');
		$this->db->order_by('exam.date', 'DESC');
		$exams = $this->db->get()->result_array();
		
		echo '<option value="">'.get_phrase('select_exam').'</option>';
		foreach($exams as $exam) {
			$exam_label = $exam['name'] . ' - ' . $exam['year'] . ' Term ' . $exam['term'];
			echo '<option value="'.$exam['exam_id'].'">'.$exam_label.'</option>';
		}
	}

	function get_class_name_ajax() {
		$class_id = $this->input->post('class_id');
		if($class_id) {
			$class_name = $this->crud_model->get_class_name($class_id);
			echo $class_name;
		} else {
			echo '';
		}
	}

	function generate_bulk_marksheet($class_id = '', $exam_id = '') {
		if ($this->session->userdata('teacher_login') != 1)
			redirect('login');
			
		if(empty($class_id) || empty($exam_id)) {
			show_error('Class ID and Exam ID are required');
			return;
		}
		
		// Get exam details to determine year/term
		$exam_record = $this->db->get_where('exam', array('exam_id' => $exam_id))->row();
		$year = $exam_record->year;
		$term = isset($exam_record->term) ? $exam_record->term : null;
		$sem = isset($exam_record->sem) ? $exam_record->sem : null;
		
		// Get all students in this class for this year/term
		if($sem) {
			$enrolls = $this->db->get_where('enroll', array(
				'class_id' => $class_id,
				'year' => $year,
				'sem' => $sem,
				'mute' => '0'
			))->result_array();
		} else {
			$enrolls = $this->db->get_where('enroll', array(
				'class_id' => $class_id,
				'year' => $year,
				'term' => $term,
				'mute' => '0'
			))->result_array();
		}
		
		// Get class name to determine report style
		$class_name = $this->crud_model->get_class_name($class_id);
		$raw_score = $this->db->get_where('settings', array('type' => 'raw_score'))->row()->description;
		$terminal_report_style = $this->db->get_where('settings', array('type' => 'terminal_report_style'))->row()->description;
		
		// Determine which bulk print view to use
		if ($class_name == 'CRECHE') {
			$page_name = 'student_marksheet_bulk_print_view_creche';
		} elseif ($raw_score == 'Yes') {
			if ($class_name == 'JHSS') {
				$page_name = 'student_raw_score_marksheet_bulk_print_view';
			} else {
				$page_name = ($terminal_report_style == 'style_2') ? 'student_marksheet_bulk_print_view_2' : 'student_marksheet_bulk_print_view';
			}
		} else {
			$page_name = ($terminal_report_style == 'style_2') ? 'student_marksheet_bulk_print_view_2' : 'student_marksheet_bulk_print_view';
		}
		
		// Prepare page data
		$page_data['enrolls'] = $enrolls;
		$page_data['class_id'] = $class_id;
		$page_data['exam_id'] = $exam_id;
		$page_data['year'] = $year;
		$page_data['term'] = $term;
		$page_data['sem'] = $sem;
		$page_data['page_name'] = $page_name;
		
		// Also need section_id for the bulk print views
		// Get first section for this class (most schools have one section per class)
		$section_row = $this->db->get_where('section', array('class_id' => $class_id))->row();
		$page_data['section_id'] = $section_row ? $section_row->section_id : 0;
		
		// Load the bulk print view
		$this->load->view('backend/teacher/'.$page_name, $page_data);
	}

	function load_marksheet_content() {
		$student_id = $this->input->post('student_id');
		$exam_id = $this->input->post('exam_id');
		$class_id = $this->input->post('class_id');
		
		// Pass exam_id to student_marksheet so it can fetch the correct year/term
		$this->student_marksheet($student_id, 'yes');
	}
}

