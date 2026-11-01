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

class Student extends CI_Controller
{


    function __construct()
    {
        parent::__construct();
		    $this->load->database();
        $this->load->library('session');
        $this->load->model('stripe_model');
        $this->load->model('paypal_model');
        $this->load->model(array('Ajaxdataload_model' => 'ajaxload'));
        /*cache control*/
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        //load encryption library
        $this->load->library('encryption');

        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }

    /***default functin, redirects to login page if no admin logged in yet***/
    public function index()
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        if ($this->session->userdata('student_login') == 1)
            redirect(site_url('student/dashboard'));
    }

    /***ADMIN DASHBOARD***/
    function dashboard()
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        $page_data['page_name']  = 'dashboard';
        $page_data['page_title'] = get_phrase('student_dashboard');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }


    /****MANAGE TEACHERS*****/
    function teacher_list($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        if ($param1 == 'personal_profile') {
            $page_data['personal_profile']   = true;
            $page_data['current_teacher_id'] = $param2;
        }
        $page_data['teachers']   = $this->db->get('teacher')->result_array();
        $page_data['page_name']  = 'teacher';
        $page_data['page_title'] = get_phrase('manage_teacher');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }


    /***********************************************************************************************************/



    /****MANAGE SUBJECTS*****/
    function subject($param1 = '', $param2 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        $student_profile         = $this->db->get_where('student', array(
            'student_id' => $this->session->userdata('student_id')
        ))->row();
        $student_class_id        = $this->db->get_where('enroll' , array(
            'student_id' => $student_profile->student_id,
                'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description,
                'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->class_id;
        $page_data['subjects']   = $this->db->get_where('subject', array(
            'class_id' => $student_class_id,
                'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description,
                'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->result_array();
        $page_data['page_name']  = 'subject';
        $page_data['page_title'] = get_phrase('manage_subject');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }



    function student_marksheet($student_id = '') {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        if($student_id != $this->session->userdata('login_user_id')) {
            $this->session->set_flashdata('error_message', get_phrase('no_direct_script_access_allowed'));
            redirect(site_url('student/dashboard'));
        }

        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;

        $this->db->where('can_delete !=', 'trash');
        $amount_due   = $this->db->get_where('invoice', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term, 'due  >' => '0'));

        $amount_due_array = $amount_due->result_array();
        $explode_running_year = explode('-', $running_year);
        $first_digit_running_year = $explode_running_year[0];

        if($amount_due->num_rows() > 0) {

            foreach($amount_due_array as $row2) {
                $invoice_year = $row2['year'];
                $explode_invoice_year = explode('-', $invoice_year);
                $first_digit_invoice_year = $explode_invoice_year[0];

                $invoice_term = $row2['term'];

                /**check and see if both running year and term are lesser than both invoice year and term
                ***If that is true, then it is probably a future billing, so allow else deny
                **/
                if($first_digit_invoice_year <= $first_digit_running_year && $invoice_term <= $running_term) {
                    $page_data['amount_due_array'] = $amount_due_array;
                    $page_data['page_title'] = 'Payment Status: You owe. Clear your debts first';
                    $page_data['page']       = 'exam marks';
                    $page_data['student_id'] = $student_id;
                    $this->load->view('backend/student/payment_error', $page_data);
                }else{
                    $class_id     = $this->db->get_where('enroll' , array(
                        'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
                    ))->row()->class_id;
                    $section_id     = $this->db->get_where('enroll' , array('class_id' => $class_id, 
                        'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
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
            }
        }else{
            $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->class_id;
        $section_id     = $this->db->get_where('enroll' , array('class_id' => $class_id, 
            'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
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
        
        $page_data['page_title'] =   get_phrase('result_sheet_for') . ' ' . $student_name . ' (' . ' ' . $class_name . ' '. $class_name_numeric.')';
        $page_data['student_id'] =   $student_id;
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['exam_id']    =   $exam_id;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
        }

         
    }

    function student_results_sheet($student_id) {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;

        $this->db->where('can_delete !=', 'trash');
        $amount_due   = $this->db->get_where('invoice', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term, 'due  >' => '0'));

        $amount_due_array = $amount_due->result_array();
        $explode_running_year = explode('-', $running_year);
        $first_digit_running_year = $explode_running_year[0];

        if($amount_due->num_rows() > 0) {

            foreach($amount_due_array as $row2) {
                $invoice_year = $row2['year'];
                $explode_invoice_year = explode('-', $invoice_year);
                $first_digit_invoice_year = $explode_invoice_year[0];

                $invoice_term = $row2['term'];

                /**check and see if both running year and term are lesser than both invoice year and term
                ***If that is true, then it is probably a future billing, so allow else deny
                **/
                if($first_digit_invoice_year <= $first_digit_running_year && $invoice_term <= $running_term) {
                    $page_data['amount_due_array'] = $amount_due_array;
                    $page_data['page_title'] = 'Payment Status: You owe. Clear your debts first';
                    $page_data['page']       = 'exam marks';
                    $page_data['student_id'] = $student_id;
                    $this->load->view('backend/student/payment_error', $page_data);
                }else{
                    $class_id     = $this->db->get_where('enroll' , array(
                        'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
                    ))->row()->class_id;
                    $section_id     = $this->db->get_where('enroll' , array('class_id' => $class_id, 
                        'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
                    ))->row()->section_id;
                    $exam_id     = $this->db->get_where('mark' , array('class_id' => $class_id, 'section_id' => $section_id, 'student_id' => $student_id, 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
                    ))->row()->exam_id;

                    $student_name = $this->db->get_where('student' , array('student_id' => $student_id))->row()->name;
                    $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
                    $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

                    $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
                    if($raw_score == 'Yes') {
                        if($class_name == 'JHSS'){
                        $page_data['page_name'] = 'student_raw_score_results_sheet';
                    }else{
                        $page_data['page_name'] = 'student_results_sheet'; };
                    }elseif($raw_score == 'No') {
                        $page_data['page_name'] = 'student_results_sheet';
                    }
                    
                    //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $data['class_id']))->row()->name;
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
            }
        }else{
            $data['class_id']    = $this->input->post('class_id');
            $data['exam_id']     = $this->input->post('exam_id');
           // $exam_id             = $this->db->get_where('exam' , array('exam_id' => $data['exam_id']))->row()->exam_id;
            $data['year']        = $this->input->post('year');
            $data['term']        = $this->input->post('term');
            $data['student_id']  = $this->input->post('student_id');
            $student_name = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;
            $class_name   = $this->db->get_where('class' , array('class_id' => $data['class_id']))->row()->name;
            $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $data['class_id']))->row()->name_numeric;

           $section_id     = $this->db->get_where('enroll' , array('class_id' => $data['class_id'], 
                'student_id' => $data['student_id'] , 'year' => $data['year'], 'term' => $data['term']))->row()->section_id;
            $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
            if($raw_score == 'Yes') {
                if($class_name == 'JHSS'){
                $page_data['page_name'] = 'student_raw_score_results_sheet';
            }else{
                $page_data['page_name'] = 'student_results_sheet'; };
            }elseif($raw_score == 'No') {
                $page_data['page_name'] = 'student_results_sheet';
            }

            $page_data['page_title'] =   get_phrase('result_sheet_for') . ' ' . $student_name . ' (' . ' ' . $class_name . ' '. $class_name_numeric.')';
            $page_data['student_id'] =   $data['student_id'];
            $page_data['class_id']   =   $data['class_id'];
            $page_data['exam_id']    =   $data['exam_id'];
            $page_data['section_id'] =   $section_id;
            $page_data['year']       =   $data['year'];
            $page_data['term']       =   $data['term'];

               

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data); 
           }
    }

    function student_marksheet_print_view($student_id , $exam_id) {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        // Fetch year, term, and sem from exam record instead of system settings
        $exam_record = $this->db->get_where('exam', array('exam_id' => $exam_id))->row();
        $running_year = $exam_record->year;
        $running_term = isset($exam_record->term) ? $exam_record->term : '';
        $running_sem = isset($exam_record->sem) ? $exam_record->sem : '';

        $this->db->where('can_delete !=', 'trash');
        $amount_due   = $this->db->get_where('invoice', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term, 'due  >' => '0'));

        $amount_due_array = $amount_due->result_array();
        $explode_running_year = explode('-', $running_year);
        $first_digit_running_year = $explode_running_year[0];

        if($amount_due->num_rows() > 0) {

            foreach($amount_due_array as $row2) {
                $invoice_year = $row2['year'];
                $explode_invoice_year = explode('-', $invoice_year);
                $first_digit_invoice_year = $explode_invoice_year[0];

                $invoice_term = $row2['term'];

                /**check and see if both running year and term are lesser than both invoice year and term
                ***If that is true, then it is probably a future billing, so allow else deny
                **/
                if($first_digit_invoice_year <= $first_digit_running_year && $invoice_term <= $running_term) {
                    $page_data['amount_due_array'] = $amount_due_array;
                    $page_data['page_title'] = 'Payment Status: You owe. Clear your debts first';
                    $page_data['page']       = 'exam marks';
                    $page_data['student_id'] = $student_id;
                    $this->load->view('backend/student/payment_error', $page_data);
                }else{
                    $class_id = $this->db->get_where('enroll' , array(
                        'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
                    ))->row()->class_id;

                    $section_id     = $this->db->get_where('enroll' , array(
                        'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
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
                                $this->load->view('backend/student/student_raw_score_marksheet_print_view', $page_data);
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
                        redirect(site_url('student/student_marksheet'), 'refreh');
                    }
                }
            }

            }else{
                $class_id = $this->db->get_where('enroll' , array(
                    'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
                ))->row()->class_id;

                $section_id     = $this->db->get_where('enroll' , array(
                    'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
                ))->row()->section_id;

                $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
                $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

                $page_data['student_id'] =   $student_id;
                $page_data['class_id']   =   $class_id;
                $page_data['section_id'] =   $section_id;
                $page_data['exam_id']    =   $exam_id;

                if($exam_id != '' || $exam_id != '' && $class_id != "" || $class_id != "" && $section_id != '' || $section_id != '') {
                    $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
                    $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;
                    
                    if($raw_score == 'Yes') {
                            if($class_name == 'JHSS'){
                            $this->load->view('backend/student/student_raw_score_marksheet_print_view', $page_data);
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
                    redirect(site_url('student/student_marksheet'), 'refreh');
                }
        }
    }

    function student_results_sheet_print_view($student_id , $exam_id, $class_id, $term, $year) {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;

        $this->db->where('can_delete !=', 'trash');
        $amount_due   = $this->db->get_where('invoice', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term, 'due  >' => '0'));

        $amount_due_array = $amount_due->result_array();
        $explode_running_year = explode('-', $running_year);
        $first_digit_running_year = $explode_running_year[0];

        if($amount_due->num_rows() > 0) {

            foreach($amount_due_array as $row2) {
                $invoice_year = $row2['year'];
                $explode_invoice_year = explode('-', $invoice_year);
                $first_digit_invoice_year = $explode_invoice_year[0];

                $invoice_term = $row2['term'];

                /**check and see if both running year and term are lesser than both invoice year and term
                ***If that is true, then it is probably a future billing, so allow else deny
                **/
                if($first_digit_invoice_year <= $first_digit_running_year && $invoice_term <= $running_term) {
                    $page_data['amount_due_array'] = $amount_due_array;
                    $page_data['page_title'] = 'Payment Status: You owe. Clear your debts first';
                    $page_data['page']       = 'exam marks';
                    $page_data['student_id'] = $student_id;
                    $this->load->view('backend/student/payment_error', $page_data);
                }else{
                    $class_id = $this->db->get_where('enroll' , array(
                        'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
                    ))->row()->class_id;

                    $section_id     = $this->db->get_where('enroll' , array(
                        'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
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
                        if($raw_score == 'Yes') {
                                if($class_name == 'JHSS'){
                                $this->load->view('backend/student/student_raw_score_results_sheet_print_view', $page_data);
                            }else{
                            $this->load->view('backend/student/student_results_sheet_print_view', $page_data); 
                            }
                        }elseif($raw_score == 'No') {
                            $this->load->view('backend/student/student_results_sheet_print_view', $page_data);
                        }
                    }else{
                        $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
                        redirect(site_url('student/student_marksheet'), 'refreh');
                    }
                }
            }
        }else{
                $section_id     = $this->db->get_where('enroll' , array(
                    'student_id' => $student_id , 'year' => $year, 'term' => $term, 'class_id' => $class_id
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
                    $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
                    if($raw_score == 'Yes') {
                            if($class_name == 'JHSS'){
                            $this->load->view('backend/student/student_raw_score_results_sheet_print_view', $page_data);
                        }else{
                        $this->load->view('backend/student/student_results_sheet_print_view', $page_data); 
                        }
                    }elseif($raw_score == 'No') {
                        $this->load->view('backend/student/student_results_sheet_print_view', $page_data);
                    }
                }else{
                    $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
                    redirect(site_url('student/student_results_sheet'), 'refreh');
                }
            }
    }

    //for creche
    function student_marksheet_creche($student_id = '') {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        $this->db->cache_on();

        $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->row()->class_id;
        $section_id     = $this->db->get_where('enroll' , array('class_id' => $class_id, 
            'student_id' => $student_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
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


    function student_results_sheet_creche() {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        $this->db->cache_on();
        
        $data['class_id']    = $this->input->post('class_id');
        $data['exam_id']     = $this->input->post('exam_id');
       // $exam_id             = $this->db->get_where('exam' , array('exam_id' => $data['exam_id']))->row()->exam_id;
        $data['year']        = $this->input->post('year');
        $data['term']        = $this->input->post('term');
        $data['student_id']  = $this->input->post('student_id');
        $student_name = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;
        $class_name   = $this->db->get_where('class' , array('class_id' => $data['class_id']))->row()->name;
        $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $data['class_id']))->row()->name_numeric;

       $section_id     = $this->db->get_where('enroll' , array('class_id' => $data['class_id'], 
            'student_id' => $data['student_id'] , 'year' => $data['year'], 'term' => $data['term']))->row()->section_id;

            $page_data['page_name'] = 'student_results_sheet_creche';
    

        //add section A or B if the class has more than one section
        $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $data['class_id']))->row()->name;
        $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
        $sec_name = '';
        if($class_has_more_sections > 1) {
            $sec_name = $section_name;
        }
        
        $page_data['page_title'] =   get_phrase('result_sheet_for') . ' ' . $student_name . ' (' . ' ' . $class_name . ' '. $class_name_numeric.$sec_name.')';
        $page_data['student_id'] =   $data['student_id'];
        $page_data['class_id']   =   $data['class_id'];
        $page_data['exam_id']    =   $data['exam_id'];
        $page_data['section_id'] =   $section_id;
        $page_data['year']       =   $data['year'];
        $page_data['term']       =   $data['term'];

           

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data); 
        
    }

    function student_marksheet_print_view_creche($student_id , $exam_id) {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        $this->db->cache_on();

        // Fetch year, term, and sem from exam record instead of system settings
        $exam_record = $this->db->get_where('exam', array('exam_id' => $exam_id))->row();
        $running_year = $exam_record->year;
        $running_term = isset($exam_record->term) ? $exam_record->term : '';
        $running_sem = isset($exam_record->sem) ? $exam_record->sem : '';

        $class_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
        ))->row()->class_id;

        $section_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
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
                $this->load->view('backend/student/student_marksheet_print_view_creche', $page_data);
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('student/student_marksheet'), 'refreh');
        }
    }

    //bulk marksheet printing
    function student_marksheet_bulk_print_view_creche($class_id, $section_id) {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        $this->db->cache_on();

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

                $this->load->view('backend/student/student_marksheet_bulk_print_view_creche', $page_data);
            
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('student/student_information'), 'refreh');
        }
    }//bulk marksheet printing ends

    
    function student_results_sheet_print_view_creche($student_id , $exam_id, $class_id, $term, $year) {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        //enable database cache
        $this->db->cache_on();

        $section_id     = $this->db->get_where('enroll' , array(
            'student_id' => $student_id , 'year' => $year, 'term' => $term, 'class_id' => $class_id
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
            $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
                $this->load->view('backend/student/student_results_sheet_print_view_creche', $page_data);
        }else{
            $this->session->set_flashdata('error_message', get_phrase('data_not_found!'));
            redirect(site_url('student/student_results_sheet'), 'refreh');
        }
    } //eend of creche


    /**********MANAGING CLASS ROUTINE******************/
    function class_routine($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        $student_profile         = $this->db->get_where('student', array(
            'student_id' => $this->session->userdata('student_id')
        ))->row();
        $page_data['class_id']   = $this->db->get_where('enroll' , array(
            'student_id' => $student_profile->student_id,
                'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
        ))->row()->class_id;
        $page_data['student_id'] = $student_profile->student_id;
        $page_data['page_name']  = 'class_routine';
        $page_data['page_title'] = get_phrase('class_time_table');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function class_routine_print_view($class_id , $section_id, $student_id)
    {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');
        $page_data['class_id']   =   $class_id;
        $page_data['section_id'] =   $section_id;
        $page_data['student_id'] =   $student_id;
        $this->load->view('backend/student/class_routine_print_view' , $page_data);
    }

    // ACADEMIC SYLLABUS
    function academic_syllabus($student_id = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(base_url());

        $page_data['page_name']  = 'academic_syllabus';
        $page_data['page_title'] = get_phrase('academic_syllabus');
        $page_data['student_id']   = $student_id;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
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

    /******MANAGE BILLING / INVOICES WITH STATUS*****/
    function invoice($param1 = '', $param2 = '', $param3 = '')
    {
        //if($this->session->userdata('student_login')!=1)redirect(base_url() );
        if ($param1 == 'make_payment') {
            $invoice_id      = $this->input->post('invoice_id');
            $system_settings = $this->db->get_where('settings', array(
                'type' => 'paypal_email'
            ))->row();
            $invoice_details = $this->db->get_where('invoice', array(
                'invoice_id' => $invoice_id
            ))->row();

            /****TRANSFERRING USER TO PAYPAL TERMINAL****/
            $this->paypal->add_field('rm', 2);
            $this->paypal->add_field('no_note', 0);
            $this->paypal->add_field('item_name', $invoice_details->title);
            $this->paypal->add_field('amount', $invoice_details->amount);
            $this->paypal->add_field('custom', $invoice_details->invoice_id);
            $this->paypal->add_field('business', $system_settings->description);
            $this->paypal->add_field('notify_url', site_url('invoice/paypal_ipn'));
            $this->paypal->add_field('cancel_return', site_url('invoice/paypal_cancel'));
            $this->paypal->add_field('return', site_url('invoice/paypal_success'));

            $this->paypal->submit_paypal_post();
            // submit the fields to paypal
        }
        if ($param1 == 'paypal_ipn') {
            if ($this->paypal->validate_ipn() == true) {
                $ipn_response = '';
                foreach ($_POST as $key => $value) {
                    $value = urlencode(stripslashes($value));
                    $ipn_response .= "\n$key=$value";
                }
                $data['payment_details']   = $ipn_response;
                $data['payment_timestamp'] = strtotime(date("m/d/Y"));
                $data['payment_method']    = 'paypal';
                $data['status']            = 'paid';
                $invoice_id                = $_POST['custom'];
                $this->db->where('invoice_id', $invoice_id);
                $this->db->update('invoice', $data);

                $data2['method']       =   'paypal';
                $data2['invoice_id']   =   $_POST['custom'];
                $data2['timestamp']    =   strtotime(date("m/d/Y"));
                $data2['payment_type'] =   'income';
                $data2['title']        =   $this->db->get_where('invoice' , array('invoice_id' => $data2['invoice_id']))->row()->title;
                $data2['description']  =   $this->db->get_where('invoice' , array('invoice_id' => $data2['invoice_id']))->row()->description;
                $data2['student_id']   =   $this->db->get_where('invoice' , array('invoice_id' => $data2['invoice_id']))->row()->student_id;
                $data2['amount']       =   $this->db->get_where('invoice' , array('invoice_id' => $data2['invoice_id']))->row()->amount;
                $this->db->insert('payment' , $data2);
            }
        }
        if ($param1 == 'paypal_cancel') {
            $this->session->set_flashdata('flash_message', get_phrase('payment_cancelled'));
            redirect(site_url('student/invoice/'));
        }
        if ($param1 == 'paypal_success') {
            $this->session->set_flashdata('flash_message', get_phrase('payment_successfull'));
            redirect(site_url('student/invoice/'));
        }
        $student_profile         = $this->db->get_where('student', array(
            'student_id'   => $this->session->userdata('student_id')
        ))->row();
        $student_id              = $student_profile->student_id;

        $this->db->where('can_delete !=', 'trash');
        $page_data['invoices']   = $this->db->get_where('invoice', array(
            'student_id' => $student_id
        ))->result_array();
        $page_data['page_name']  = 'invoice';
        $page_data['page_title'] = get_phrase('manage_invoice/payment');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function paypal_checkout($student_id = '') {
      if ($this->session->userdata('student_login') != 1)
          redirect('login');

        $invoice_id = $this->input->post('invoice_id');
        $page_data['student_details'] = $this->db->get_where('student', array('student_id' => $student_id))->row();
        $page_data['invoice_details'] = $this->db->get_where('invoice', array(
            'invoice_id' => $invoice_id
        ))->row();
        $this->load->view('backend/paypal_checkout', $page_data);
    }
    function stripe_checkout($student_id = ''){
      if ($this->session->userdata('student_login') != 1)
          redirect('login');

          $invoice_id = $this->input->post('invoice_id');
          $page_data['student_details'] = $this->db->get_where('student', array('student_id' => $student_id))->row();
          $page_data['invoice_details'] = $this->db->get_where('invoice', array(
              'invoice_id' => $invoice_id
          ))->row();
          $this->load->view('backend/stripe_checkout', $page_data);
    }

    function pay($gateway = '', $invoice_id = '') {

      if ($gateway == 'stripe') {
            $student_id = $this->input->post('student_id');
            $payment_success = $this->stripe_model->pay($invoice_id);
            if ($payment_success == true) {
                $this->session->set_flashdata('flash_message', get_phrase('payment_successfull'));
                redirect(site_url('student/invoice/'.$student_id));
            } else {
                $this->session->set_flashdata('error_message', get_phrase('payment_failed'));
                redirect(site_url('student/invoice/'.$student_id));
            }
        }
        else if ($gateway == 'paypal') {
            $this->paypal_model->pay($invoice_id);
            $this->session->set_flashdata('flash_message', get_phrase('payment_successfull'));
        }
    }
    /**********MANAGE LIBRARY / BOOKS********************/
    function book($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        $page_data['books']      = $this->db->get('book')->result_array();
        $page_data['page_name']  = 'book';
        $page_data['page_title'] = get_phrase('book_list');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }
    /**********MANAGE TRANSPORT / VEHICLES / ROUTES********************/
    function transport($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        $page_data['transports'] = $this->db->get('transport')->result_array();
        $page_data['page_name']  = 'transport';
        $page_data['page_title'] = get_phrase('manage_transport');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }
    /**********MANAGE DORMITORY / HOSTELS / ROOMS ********************/
    function dormitory($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        $page_data['dormitories'] = $this->db->get('dormitory')->result_array();
        $page_data['page_name']   = 'dormitory';
        $page_data['page_title']  = get_phrase('manage_dormitory');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }

    /**********WATCH NOTICEBOARD AND EVENT ********************/
    function noticeboard($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        $page_data['notices']    = $this->db->get_where('noticeboard',array('status'=>1))->result_array();
        $page_data['page_name']  = 'noticeboard';
        $page_data['page_title'] = get_phrase('noticeboard');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }

    /**********MANAGE DOCUMENT / home work FOR A SPECIFIC CLASS or ALL*******************/
    function document($do = '', $document_id = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect('login');

        $page_data['page_name']  = 'manage_document';
        $page_data['page_title'] = get_phrase('manage_documents');
        $page_data['documents']  = $this->db->get('document')->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    /* private messaging */

    function message($param1 = 'message_home', $param2 = '', $param3 = '') {
        if ($this->session->userdata('student_login') != 1)
            redirect(base_url());

        $max_size = 4097152;
        if ($param1 == 'send_new') {
            // Folder creation
            if (!file_exists('uploads/private_messaging_attached_file/')) {
              $oldmask = umask(0);  // helpful when used in linux server
              mkdir ('uploads/private_messaging_attached_file/', 0777);
            }
            if ($_FILES['attached_file_on_messaging']['name'] != "") {
              if($_FILES['attached_file_on_messaging']['size'] > $max_size){
                $this->session->set_flashdata('error_message' , get_phrase('file_size_can_not_be_larger_that_4_Megabyte'));
                redirect(site_url('student/message/message_new/'));
              }
              else{
                $file_path = 'uploads/private_messaging_attached_file/'.$_FILES['attached_file_on_messaging']['name'];
                move_uploaded_file($_FILES['attached_file_on_messaging']['tmp_name'], $file_path);
              }
            }
            $message_thread_code = $this->crud_model->send_new_private_message();
            $this->session->set_flashdata('flash_message', get_phrase('message_sent!'));
            redirect(site_url('student/message/message_read/' . $message_thread_code));

        }

        if ($param1 == 'send_reply') {

            //making folder
            if (!file_exists('uploads/private_messaging_attached_file/')) {
              $oldmask = umask(0);  // helpful when used in linux server
              mkdir ('uploads/private_messaging_attached_file/', 0777);
            }
            if ($_FILES['attached_file_on_messaging']['name'] != "") {
              if($_FILES['attached_file_on_messaging']['size'] > $max_size){
                $this->session->set_flashdata('error_message' , get_phrase('file_size_can_not_be_larger_that_4_Megabyte'));
                  redirect(site_url('student/message/message_read/' . $param2));
              }
              else{
                $file_path = 'uploads/private_messaging_attached_file/'.$_FILES['attached_file_on_messaging']['name'];
                move_uploaded_file($_FILES['attached_file_on_messaging']['tmp_name'], $file_path);
              }
            }
            $this->crud_model->send_reply_message($param2);  //$param2 = message_thread_code
            $this->session->set_flashdata('flash_message', get_phrase('message_sent!'));
            redirect(site_url('student/message/message_read/' . $param2));
        }

        if ($param1 == 'message_read') {
            $page_data['current_message_thread_code'] = $param2;  // $param2 = message_thread_code
            $this->crud_model->mark_thread_messages_read($param2);
        }

         if ($param1 == 'delete') {
            $this->db->where('message_thread_code', $param2);
            $this->db->delete('message_thread');
            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('student/message'));
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
      if ($this->session->userdata('student_login') != 1)
          redirect(site_url('login'));
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
            $this->session->set_flashdata('error_message' , get_phrase('file_size_can_not_be_larger_that_4_Megabyte'));
              redirect(site_url('student/group_message/group_message_read/' . $param2));

          }
          else{
            $file_path = 'uploads/group_messaging_attached_file/'.$_FILES['attached_file_on_messaging']['name'];
            move_uploaded_file($_FILES['attached_file_on_messaging']['tmp_name'], $file_path);
          }
        }

        $this->crud_model->send_reply_group_message($param2);  //$param2 = message_thread_code
        $this->session->set_flashdata('flash_message', get_phrase('message_sent!'));
          redirect(site_url('student/group_message/group_message_read/' . $param2));
      }

      if ($param1 == 'delete') {
        $this->db->where('group_message_thread_code', $param2);
        $this->db->delete('group_message_thread');
        $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
        redirect(site_url('student/group_message'));
     }
      $page_data['message_inner_page_name']   = $param1;
      $page_data['page_name']                 = 'group_message';
      $page_data['page_title']                = get_phrase('group_messaging');
      

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    /******MANAGE OWN PROFILE AND CHANGE PASSWORD***/
    function manage_profile($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        if ($param1 == 'update_profile_info') {
            $data['name']        = strtoupper($this->input->post('name'));
            $data['email']       = strtolower($this->input->post('email'));

            $this->load->helper('email');
              if(!valid_email($data['email'])) {
                $this->session->set_flashdata('error_message' , 'Invalid Email Found!');
                redirect(site_url('student/manage_profile'));
              }
            if ($this->input->post('phone') != null) {
                $data['phone']    = $this->input->post('phone');
            }
            if ($this->input->post('address') != null) {
                $data['address']  = $this->input->post('address');
            }
            if ($this->input->post('birthday') != null) {
               $data['birthday'] = $this->input->post('birthday');
            }
            if ($this->input->post('sex') != null) {
                $data['sex']      = $this->input->post('sex');
            }

            $validation = email_validation_for_edit($data['email'], $this->session->userdata('student_id'), 'student');
            if($validation == 1){

                $this->db->where('student_id', $this->session->userdata('student_id'));
                $this->db->update('student', $data);
                move_uploaded_file($_FILES['userfile']['tmp_name'], 'uploads/student_image/' . $this->session->userdata('student_id') . '.jpg');
                $this->session->set_flashdata('flash_message', get_phrase('account_updated'));
            }
            else{
                $this->session->set_flashdata('error_message', get_phrase('this_email_id_is_not_available'));
            }

            redirect(site_url('student/manage_profile/'));
        }
        if ($param1 == 'change_password') {
            $data['password']             = $this->input->post('password');
            $data['new_password']         = password_hash($this->input->post('new_password'), PASSWORD_BCRYPT);
            $data['confirm_new_password'] = $this->input->post('confirm_new_password');
            
            $current_password = $this->db->get_where('student', array(
                'student_id' => $this->session->userdata('student_id')
            ))->row()->password;
            if (password_verify($data['password'], $current_password) == 1 && password_verify($data['confirm_new_password'], $data['new_password']) == 1) {
                $this->db->where('student_id', $this->session->userdata('student_id'));
                $this->db->update('student', array(
                    'password' => $data['new_password']
                ));
                $this->session->set_flashdata('flash_message', get_phrase('password_updated_successfully'));
            } else {
                $this->session->set_flashdata('error_message', get_phrase('password_mismatch!'));
            }
            redirect(site_url('student/manage_profile/'));
        }
        $page_data['page_name']  = 'manage_profile';
        $page_data['page_title'] = get_phrase('manage_profile');
        $page_data['edit_data']  = $this->db->get_where('student', array(
            'student_id' => $this->session->userdata('student_id')
        ))->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    /*****************SHOW STUDY MATERIAL / for students of a specific class*******************/
    function study_material($task = "", $document_id = "")
    {
        if ($this->session->userdata('student_login') != 1)
        {
            $this->session->set_userdata('last_page' , current_url());
            redirect(base_url());
        }

        $data['study_material_info']    = $this->crud_model->select_study_material_info_for_student();
        $data['page_name']              = 'study_material';
        $data['page_title']             = get_phrase('study_material');
        $this->load->view('backend/main', $data);
    }

    // MANAGE BOOK REQUESTS
    function book_request($param1 = "", $param2 = "")
    {
        if ($this->session->userdata('student_login') != 1)
        {
            $this->session->set_userdata('last_page', current_url());
            redirect(base_url());
        }

        if ($param1 == "create")
        {
            $this->crud_model->create_book_request();
            $this->session->set_flashdata('flash_message', get_phrase('your_request_wass_successful'));
            redirect(site_url('student/book_request/'));
        }

        $data['page_name']  = 'book_request';
        $data['page_title'] = get_phrase('book_request');
        $this->load->view('backend/main', $data);
    }

    function book_request_add($param1 = "", $param2 = "")
    {
        if ($this->session->userdata('student_login') != 1)
        {
            $this->session->set_userdata('last_page', current_url());
            redirect(base_url());
        }

        if ($param1 == "create")
        {
            $this->crud_model->create_book_request();
            $this->session->set_flashdata('flash_message', get_phrase('your_request_wass_successful'));
            redirect(site_url('student/book_request/'));
        }

        $data['page_name']  = 'book_request_add';
        $data['page_title'] = get_phrase('request_for_a_book');
        $this->load->view('backend/main', $data);
    }

    function pay_with_payumoney($param1 = "", $param2 = ""){
        $page_data['page_name']  = 'pay_with_payumoney';
        $page_data['page_title'] = get_phrase('pay_with_payumoney');
        $page_data['student_id'] = $param1;
        $page_data['invoice_id'] = $param2;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function manage_attendance(){
      if ($this->session->userdata('student_login') != 1)
      {
          $this->session->set_userdata('last_page', current_url());
          redirect(base_url());
      }
      $page_data['month']      = date('m');
      $page_data['page_name']  = 'manage_attendance';
      $page_data['page_title'] = get_phrase('manage_attendance');
      

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function attendance_report_selector(){
        $running_year 		              = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
        $running_term                    = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
        $student_name                   = $this->db->get_where('student', array('student_id' => $this->session->userdata('login_user_id')))->row()->name;
        $checker = array(
          'student_id' => $this->session->userdata('login_user_id'),
          'year'       => $running_year,
          'term'       => $running_term
        );
        $month = $this->input->post('month');
        $sessional_year = $this->input->post('sessional_year');
        $term           = $this->input->post('term');

        $class_id                       = $this->db->get_where('enroll', $checker)->row()->class_id;
        $section_id                     = $this->db->get_where('enroll', $checker)->row()->section_id;
        $class_name                     = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
        $class_name_numeric             = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
        $section_name                   = $this->db->get_where('section', array('section_id' => $section_id))->row()->name;
        $page_data['class_id']          = $class_id;
        $page_data['section_id']        = $section_id;
        $page_data['month']             = $month;
        $page_data['sessional_year']    = $sessional_year;
        $page_data['term']              = $term;
        $page_data['student_id']        = $this->session->userdata('login_user_id');
        $page_data['page_name']         = 'attendance_report_view';
        $page_data['page_title']        = get_phrase('attendance_report_of') . ' ' . $student_name;
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function attendance_report_print_view($class_id ='' , $section_id = '' , $month = '', $sessional_year = '', $student_id = '', $term = '') {
      if ($this->session->userdata('student_login') != 1)
      {
          $this->session->set_userdata('last_page', current_url());
          redirect(base_url());
      }

     $page_data['class_id']          = $class_id;
     $page_data['section_id']        = $section_id;
     $page_data['month']             = $month;
     $page_data['sessional_year']    = $sessional_year;
     $page_data['student_id']        = $student_id;
     $page_data['term']              = $term;
     $this->load->view('backend/student/attendance_report_print_view' , $page_data);
 }

 function get_teachers() {
        if ($this->session->userdata('student_login') != 1)
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
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));

        $columns = array(
            0 => 'book_id',
            1 => 'name',
            2 => 'author',
            3 => 'description',
            4 => 'price',
            5 => 'class',
            6 => 'download',
            7 => 'book_id'
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

                $nestedData['book_id'] = $row->book_id;
                $nestedData['name'] = $row->name;
                $nestedData['author'] = $row->author;
                $nestedData['description'] = $row->description;
                $nestedData['price'] = $row->price;
                $nestedData['class'] = $this->db->get_where('class', array('class_id' => $row->class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $row->class_id))->row()->name_numeric;
                $nestedData['download'] = $download;

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

    function online_exam($param1 = '', $param2 = '') {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        if ($param1 == '') {
            $page_data['data'] = 'active';
            $page_data['exams'] = $this->crud_model->available_exams($this->session->userdata('login_user_id'));
        }

        $page_data['page_name'] = 'online_exam';
        $page_data['page_title'] = get_phrase('online_exams');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function online_exam_result($param1 = '', $param2 = '') {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        if ($param1 == '') {
            $page_data['data'] = 'result';
            $page_data['exams'] = $this->crud_model->available_exams($this->session->userdata('login_user_id'));
        }

        $page_data['page_name'] = 'online_exam_result';
        $page_data['page_title'] = get_phrase('online_exam_results');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function take_online_exam($online_exam_code) {
        if ($this->session->userdata('student_login') != 1)
            redirect(site_url('login'));
        $online_exam_id = $this->db->get_where('online_exam', array('code' => $online_exam_code))->row()->online_exam_id;
        $student_id = $this->session->userdata('login_user_id');

        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;

        $this->db->where('can_delete !=', 'trash');
        $amount_due   = $this->db->get_where('invoice', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term))->row()->due;
        if($amount_due > 0) {
            $page_data['page_title'] = 'Payment Status: You owe GHS'.$amount_due;
            $page_data['amount_due'] = $amount_due;
            $page_data['page'] = 'take online exam';
            $this->load->view('backend/student/payment_error', $page_data);
        }else{
            // check if the student has already taken the exam
            $check = array('student_id' => $student_id, 'online_exam_id' => $online_exam_id);
            $taken = $this->db->where($check)->get('online_exam_result')->num_rows();

            $this->crud_model->change_online_exam_status_to_attended_for_student($online_exam_id);

            $status = $this->crud_model->check_availability_for_student($online_exam_id);

            if ($status == 'submitted') {
                $page_data['page_name']  = 'page_not_found';
            }
            else{
                $page_data['page_name']  = 'online_exam_take';
            }
            $page_data['page_title'] = get_phrase('online_exam');
            $page_data['online_exam_id'] = $online_exam_id;
            $page_data['student_id'] = $student_id;
            $page_data['exam_info'] = $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id));
            

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
        }
        
    }


    function submit_online_exam($online_exam_id = ""){

        $answer_script = array();
        $question_bank = $this->db->get_where('question_bank', array('online_exam_id' => $online_exam_id))->result_array();

        foreach ($question_bank as $question) {

          $correct_answers  = $this->crud_model->get_correct_answer($question['question_bank_id']);
          $container_2 = array();
          if (isset($_POST[$question['question_bank_id']])) {

              foreach ($this->input->post($question['question_bank_id']) as $row) {
                  $submitted_answer = "";
                  if ($question['type'] == 'true_false') {
                      $submitted_answer = $row;
                  }
                  elseif($question['type'] == 'fill_in_the_blanks'){
                    $suitable_words = array();
                    $suitable_words_array = explode(',', $row);
                    foreach ($suitable_words_array as $key) {
                      array_push($suitable_words, strtolower($key));
                    }
                    $submitted_answer = json_encode(array_map('trim',$suitable_words));
                  }
                  else{
                      array_push($container_2, strtolower($row));
                      $submitted_answer = json_encode($container_2);
                  }
                  $container = array(
                      "question_bank_id" => $question['question_bank_id'],
                      "submitted_answer" => $submitted_answer,
                      "correct_answers"  => $correct_answers
                  );
              }
          }
          else {
              $container = array(
                  "question_bank_id" => $question['question_bank_id'],
                  "submitted_answer" => "",
                  "correct_answers"  => $correct_answers
              );
          }

          array_push($answer_script, $container);
        }
        $this->crud_model->submit_online_exam($online_exam_id, json_encode($answer_script));
        redirect(site_url('student/online_exam'));
    }

    //reload take online page to check of update in time
    function get_exam_ends_timestamp($exam_ends_timestamp, $online_exam_id) {
        
        $this->crud_model->load_exam_ends_timestamp($exam_ends_timestamp, $online_exam_id);   
    }

    //verify if selected book for request is available or not
    function verify_book($book_id) {
        $this->crud_model->verify_book($book_id);
    }

    //mobile money checkout
    function mobile_money_checkout($student_id){
      if ($this->session->userdata('student_login') != 1)
          redirect('login');

          $data['invoice_id'] = $this->input->post('invoice_id');
          $data['student_id'] = $student_id;

          //validate form
          $this->form_validation->set_rules('t_id', 'Transaction ID', 'required|trim|max_length[10]|min_length[10]');
          $this->form_validation->set_rules('mo_a_paid', 'Amount Paid', 'required|trim');
          if($this->form_validation->run() === FALSE) {

           $this->session->set_flashdata('error_message', get_phrase('make_sure_all_input_fields_are_filled.'));
           redirect(site_url('student/invoice/'.$student_id));

          }

          $data['invoice_code'] = $this->db->get_where('invoice', array('invoice_id' => $data['invoice_id'], 'student_id' => $student_id))->row()->invoice_code;
          $data['t_id'] = $this->input->post('t_id');
          $data['mo_a_paid'] = $this->input->post('mo_a_paid');
          $data['date_time'] = $this->input->post('mo_timestamp');
          $data['timestamp'] = strtotime($this->input->post('mo_timestamp'));
          $data['method'] = 4;
          $data['description'] = $this->input->post('description');
          $data['title'] = $this->input->post('title');
          $data['year']         =   $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
          $data['term']         =   $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
          $data['payment_type'] =   'income';

            $this->db->insert('mobile_money_payment', $data);

            $this->session->set_flashdata('flash_message', get_phrase('payment_submitted_successfully_and_currently_pending_for_approval.'));
            redirect(site_url('student/invoice/'.$student_id));
    }

    /**
     * Student Lesson Notes
     * Display approved lesson notes for the student's class and subjects
     * Requirements: 15.1-15.7
     */
    function lesson_notes() {
        if ($this->session->userdata('student_login') != 1) {
            redirect(site_url('login'));
        }

        $this->load->model('Lesson_note_model');

        $student_id = $this->session->userdata('student_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Get student's current class
        $enrollment = $this->db->get_where('enroll', array(
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term
        ))->row();

        if (!$enrollment) {
            $this->session->set_flashdata('error_message', get_phrase('no_enrollment_found'));
            redirect(site_url('student/dashboard'));
        }

        $class_id = $enrollment->class_id;

        // Get student's subjects for this class
        $this->db->select('subject_id');
        $this->db->from('subject');
        $this->db->where('class_id', $class_id);
        $this->db->where('year', $running_year);
        $this->db->where('status', 1);
        $subjects = $this->db->get()->result();
        $subject_ids = array_column($subjects, 'subject_id');

        // Get filters
        $filter_subject = $this->input->get('subject_id');
        $filter_term = $this->input->get('term') ?: $running_term;
        $filter_week = $this->input->get('week_number');

        // Get approved lesson notes
        $lesson_notes = $this->Lesson_note_model->get_approved_for_students($class_id, $subject_ids);

        // Apply additional filters
        if ($filter_subject) {
            $lesson_notes = array_filter($lesson_notes, function($ln) use ($filter_subject) {
                return $ln->subject_id == $filter_subject;
            });
        }
        if ($filter_term) {
            $lesson_notes = array_filter($lesson_notes, function($ln) use ($filter_term) {
                return $ln->term == $filter_term;
            });
        }
        if ($filter_week) {
            $lesson_notes = array_filter($lesson_notes, function($ln) use ($filter_week) {
                return $ln->week_number == $filter_week;
            });
        }

        // Group by subject
        $grouped_notes = array();
        foreach ($lesson_notes as $note) {
            if (!isset($grouped_notes[$note->subject_name])) {
                $grouped_notes[$note->subject_name] = array();
            }
            $grouped_notes[$note->subject_name][] = $note;
        }

        $page_data['lesson_notes'] = $lesson_notes;
        $page_data['grouped_notes'] = $grouped_notes;
        $page_data['subjects'] = $subjects;
        $page_data['class_id'] = $class_id;
        $page_data['filter_subject'] = $filter_subject;
        $page_data['filter_term'] = $filter_term;
        $page_data['filter_week'] = $filter_week;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['page_name'] = 'lesson_notes';
        $page_data['page_title'] = get_phrase('lesson_notes');
        $page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
    }

    /**
     * View single lesson note (student access)
     * Requirements: 15.5, 15.7
     */
    function lesson_note_view($lesson_note_id = '') {
        if ($this->session->userdata('student_login') != 1) {
            redirect(site_url('login'));
        }

        if (empty($lesson_note_id)) {
            redirect(site_url('student/lesson_notes'));
        }

        $this->load->model('Lesson_note_model');

        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);

        if (!$lesson_note || $lesson_note->status != 'approved') {
            $this->session->set_flashdata('error_message', get_phrase('lesson_note_not_found'));
            redirect(site_url('student/lesson_notes'));
        }

        // Verify student has access to this class
        $student_id = $this->session->userdata('student_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        $enrollment = $this->db->get_where('enroll', array(
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term
        ))->row();

        if (!$enrollment || $enrollment->class_id != $lesson_note->class_id) {
            $this->session->set_flashdata('error_message', get_phrase('access_denied'));
            redirect(site_url('student/lesson_notes'));
        }

        // Hide internal fields (feedback, revision history details)
        $page_data['lesson_note'] = $lesson_note;
        $page_data['page_name'] = 'lesson_note_view';
        $page_data['page_title'] = $lesson_note->title;
        $page_data['account_type'] = $this->session->userdata('login_type');

        $this->load->view('backend/main', $page_data);
    }
}
