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
class Accountant extends CI_Controller
{
    
    
	function __construct()
	{
		parent::__construct();
		$this->load->database();
        $this->load->library('session');
        $this->load->model(array('Ajaxdataload_model' => 'ajaxload'));
		
       /*cache control*/
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');

        //load encryption library
        $this->load->library('encryption');
		
    }
    
    /***default functin, redirects to login page if no accountant logged in yet***/
    public function index()
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));
        if ($this->session->userdata('accountant_login') == 1)
            redirect(site_url('accountant/dashboard'));
    }
    
    /***ACCOUNTANT DASHBOARD***/
    function dashboard($param = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        if($param == 'search') {
            $page_data['term']  = $this->input->post('term');
            $page_data['year']  = $this->input->post('year');
            $page_data['date']  = strtotime($this->input->post('date_sel'));

            $page_data['page_name']  = 'dashboard';
            $page_data['page_title'] = get_phrase('accountant_dashboard');
            $page_data['search']     = $param;
            

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
        }else{
            $page_data['page_name']  = 'dashboard';
            $page_data['page_title'] = get_phrase('accountant_dashboard');
            

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);   
        }
        
    }

    /**********WATCH NOTICEBOARD AND EVENT ********************/
    function noticeboard($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect('login');

        $page_data['notices']    = $this->db->get_where('noticeboard',array('status'=>1))->result_array();
        $page_data['page_name']  = 'noticeboard';
        $page_data['page_title'] = get_phrase('noticeboard');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);

    }
    
    /******MANAGE BILLING / INVOICES WITH STATUS*****/
    function invoice_create($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;


        if ($param1 == 'create') 
        {


            //generate sequential invoice number;
            $this->db->select('invoice_code');
            $this->db->order_by('invoice_code', 'desc');
            $this->db->limit(1);
            $inv_query = $this->db->get('invoice');
            $inv_id    = $inv_query->row()->invoice_code;

            if($inv_query->num_rows() > 0) {
                $data['invoice_code']       = $inv_id + 1;
            }else{
                $data['invoice_code']       = $invoice_code_f;
            }
            
            //invoice (code) validation for duplicate
             $code_validation = invoice_code_validation_insert($data['invoice_code']);
             while(!$code_validation){
                 //while invoice code validation fails, keep generating different ones
                $this->db->select('invoice_code');
                $this->db->order_by('invoice_code', 'desc');
                $this->db->limit(1);
                $inv_query = $this->db->get('invoice');
                $inv_id    = $inv_query->row()->invoice_code;
                $data['invoice_code']       = $inv_id + 1;
                $code_validation = invoice_code_validation_insert($data['invoice_code']);
             }
             //invoice validation ends

           /** $data['student_id']         = $this->input->post('student_id');
            $data['term']               = $this->input->post('term');
            $data['title']              = strtoupper($this->input->post('title'));
            $data['amount']             = $this->input->post('amount');
            $data['amount_paid']        = 0;
            $data['due']                = $data['amount'] - $data['amount_paid'];
            $data['status']             = 'unpaid';
            $data['creation_timestamp'] = strtotime($this->input->post('date'));
            $data['year']               = $this->input->post('year');
            if ($this->input->post('description') != null) {
                $data['description']    = $this->input->post('description');
            }**/


            //get the serialized values from the ajax request and process them
            //if $param2 is empty, use the default id prefix...
            if($param2 == '' || $param2 == null) {
                $ids_array = array('16484_1565043896');
            } else {
                $ids_array = explode('-', $param2);
            }
            for($j = 0; $j < count($ids_array); $j++) {

                $data['invoice_code']       = $data['invoice_code'];
                $data['student_id']         = $_REQUEST['student_id'];
                $data['term']               = $_REQUEST['term'];
                $data['title']              = strtoupper($_REQUEST[$ids_array[$j].'_title']);
                $data['amount']             = $_REQUEST[$ids_array[$j].'_amount'];
                $data['amount_paid']        = 0;
                $data['due']                = $data['amount'] - $data['amount_paid'];
                $data['status']             = 'unpaid';
                $data['creation_timestamp'] = strtotime($_REQUEST[$ids_array[$j].'_date']);
                $data['year']               = $_REQUEST['year'];

                if ($_REQUEST[$ids_array[$j].'_description'] != null) {
                $data['description']    = $_REQUEST[$ids_array[$j].'_description'];
                }

                //Do insertion
                $this->db->insert('invoice', $data);
            }

             //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');
            echo 'Success';
        }
    }

    //Mass invoice creation using ajax
    function mass_invoice_create($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;


        if ($param1 == 'create') 
        {

            foreach($_REQUEST['student_id'] as $student_id) {
                //generate sequential invoice number;
                $this->db->select('invoice_code');
                $this->db->order_by('invoice_code', 'desc');
                $this->db->limit(1);
                $inv_query = $this->db->get('invoice');
                $inv_id    = $inv_query->row()->invoice_code;

                if($inv_query->num_rows() > 0) {
                    $data['invoice_code']       = $inv_id + 1;
                }else{
                    $data['invoice_code']       = $invoice_code_f;
                }
                
                //invoice (code) validation for duplicate
                 $code_validation = invoice_code_validation_insert($data['invoice_code']);
                 while(!$code_validation){
                     //while invoice code validation fails, keep generating different ones
                    $this->db->select('invoice_code');
                    $this->db->order_by('invoice_code', 'desc');
                    $this->db->limit(1);
                    $inv_query = $this->db->get('invoice');
                    $inv_id    = $inv_query->row()->invoice_code;
                    $data['invoice_code']       = $inv_id + 1;
                    $code_validation = invoice_code_validation_insert($data['invoice_code']);
                 }
                 //invoice validation ends

               /** $data['student_id']         = $this->input->post('student_id');
                $data['term']               = $this->input->post('term');
                $data['title']              = strtoupper($this->input->post('title'));
                $data['amount']             = $this->input->post('amount');
                $data['amount_paid']        = 0;
                $data['due']                = $data['amount'] - $data['amount_paid'];
                $data['status']             = 'unpaid';
                $data['creation_timestamp'] = strtotime($this->input->post('date'));
                $data['year']               = $this->input->post('year');
                if ($this->input->post('description') != null) {
                    $data['description']    = $this->input->post('description');
                }**/


                //get the serialized values from the ajax request and process them
                //if $param2 is empty, use the default id prefix...
                if($param2 == '' || $param2 == null) {
                    $ids_array = array('177_1565053816');
                } else {
                    $ids_array = explode('-', $param2);
                }
                for($j = 0; $j < count($ids_array); $j++) {

                    $data['invoice_code']       = $data['invoice_code'];
                    $data['student_id']         = $student_id;
                    $data['term']               = $_REQUEST['term'];
                    $data['title']              = strtoupper($_REQUEST[$ids_array[$j].'_title']);
                    $data['amount']             = $_REQUEST[$ids_array[$j].'_amount'];
                    $data['amount_paid']        = 0;
                    $data['due']                = $data['amount'] - $data['amount_paid'];
                    $data['status']             = 'unpaid';
                    $data['creation_timestamp'] = strtotime($_REQUEST[$ids_array[$j].'_date']);
                    $data['year']               = $_REQUEST['year'];

                    if ($_REQUEST[$ids_array[$j].'_description'] != null) {
                    $data['description']    = $_REQUEST[$ids_array[$j].'_description'];
                    }

                    //Do insertion
                    $this->db->insert('invoice', $data);
                }
            }

             //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');

            echo 'Success';
        }
    }

    function invoice($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));
        
        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;

       /** if ($param1 == 'create') {
            //generate sequential invoice number;
            $this->db->select('invoice_code');
            $this->db->order_by('invoice_code', 'desc');
            $this->db->limit(1);
            $inv_query = $this->db->get('invoice');
            $inv_id    = $inv_query->row()->invoice_code;

            if($inv_query->num_rows() > 0) {
                $data['invoice_code']       = $inv_id + 1;
            }else{
                $data['invoice_code']       = $invoice_code_f;
            }
            
            //invoice (code) validation for duplicate
             $code_validation = invoice_code_validation_insert($data['invoice_code']);
             while(!$code_validation){
                 //while invoice code validation fails, keep generating different ones
                $this->db->select('invoice_code');
                $this->db->order_by('invoice_code', 'desc');
                $this->db->limit(1);
                $inv_query = $this->db->get('invoice');
                $inv_id    = $inv_query->row()->invoice_code;
                $data['invoice_code']       = $inv_id + 1;
                $code_validation = invoice_code_validation_insert($data['invoice_code']);
             }
             //invoice validation ends

            $data['student_id']         = $this->input->post('student_id');
            $data['term']               = $this->input->post('term');
            $data['title']              = strtoupper($this->input->post('title'));
            $data['amount']             = $this->input->post('amount');
            $data['amount_paid']        = 0;
            $data['due']                = $data['amount'] - $data['amount_paid'];
            $data['status']             = 'unpaid';
            $data['creation_timestamp'] = strtotime($this->input->post('date'));
            $data['year']               = $this->input->post('year');
            if ($this->input->post('description') != null) {
                $data['description']    = $this->input->post('description');
            }

            $this->db->insert('invoice', $data);
           /** $invoice_id = $this->db->insert_id();

            $data2['invoice_id']        =   $invoice_id;
            $data2['invoice_code']      =   $data['invoice_code'];
            $data2['student_id']        =   $this->input->post('student_id');
            $data2['term']              =   $this->input->post('term');
            $data2['title']             =   strtoupper($this->input->post('title'));
            $data2['payment_type']      =  'income';
            $data2['method']            =   $this->input->post('method');
            $data2['amount']            =   $this->input->post('amount_paid');
            $data2['timestamp']         =   strtotime($this->input->post('date'));
            $data2['year']              =  $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            if ($this->input->post('description') != null) {
                $data2['description']    = $this->input->post('description');
            }
            $this->db->insert('payment' , $data2); 

            $this->session->set_flashdata('flash_message' , get_phrase('data_added_successfully'));
            redirect(site_url('accountant/student_invoice'));
        } **/

       /** if ($param1 == 'create_mass_invoice') {
            foreach ($this->input->post('student_id') as $id) {
                //generate sequential invoice number;
            $this->db->select('invoice_code');
            $this->db->order_by('invoice_code', 'desc');
            $this->db->limit(1);
            $inv_query = $this->db->get('invoice');
            $inv_id    = $inv_query->row()->invoice_code;

            if($inv_query->num_rows() > 0) {
                $data['invoice_code']       = $inv_id + 1;
            }else{
                $data['invoice_code']       = $invoice_code_f;
            }
            
            //invoice (code) validation for duplicate
             $code_validation = invoice_code_validation_insert($data['invoice_code']);
             while(!$code_validation){
                 //while invoice code validation fails, keep generating different ones
                $this->db->select('invoice_code');
                $this->db->order_by('invoice_code', 'desc');
                $this->db->limit(1);
                $inv_query = $this->db->get('invoice');
                $inv_id    = $inv_query->row()->invoice_code;
                $data['invoice_code']       = $inv_id + 1;
                $code_validation = invoice_code_validation_insert($data['invoice_code']);
             }
             //invoice validation ends

                $data['student_id']         = $id;
                $data['title']              = strtoupper($this->input->post('title'));
                $data['description']        = $this->input->post('description');
                $data['amount']             = $this->input->post('amount');
                $data['term']               = $this->input->post('term');
                $data['amount_paid']        = 0;
                $data['due']                = $data['amount'] - $data['amount_paid'];
                $data['status']           = 'unpaid';
                $data['creation_timestamp'] = strtotime($this->input->post('date'));
                $data['year']               = $this->input->post('year');

                $this->db->insert('invoice', $data);
               /** $invoice_id = $this->db->insert_id();

                $data2['invoice_id']        =   $invoice_id;
                $data2['invoice_code']      =   $data['invoice_code'];
                $data2['student_id']        =   $id;
                $data2['title']             =   strtoupper($this->input->post('title'));
                $data2['description']       =   $this->input->post('description');
                $data2['term']              =   $this->input->post('term');
                $data2['payment_type']      =  'income';
                $data2['method']            =   $this->input->post('method');
                $data2['amount']            =   $this->input->post('amount_paid');
                $data2['timestamp']         =   strtotime($this->input->post('date'));
                $data2['year']              =   $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;

                $this->db->insert('payment' , $data2); 
            }

            $this->session->set_flashdata('flash_message' , get_phrase('data_added_successfully'));
            redirect(site_url('accountant/student_invoice'));
        } **/

        if ($param1 == 'do_update') {
            $data['student_id']         = $this->input->post('student_id');
            $data['title']              = strtoupper($this->input->post('title'));
            $data['description']        = $this->input->post('description');
            $data['amount']             = $this->input->post('amount');
            $data['amount_paid']        = $this->input->post('amount_paid');
            $data['due']                = $data['amount'] - $data['amount_paid'];
            $data['status']             = strtolower($this->input->post('status'));
            $data['creation_timestamp'] = strtotime($this->input->post('date'));

            $this->db->where('invoice_id', $param2);
            $this->db->update('invoice', $data);

             //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');

            $this->session->set_flashdata('flash_message' , get_phrase('data_updated'));
            redirect(site_url('accountant/income'));
        } else if ($param1 == 'edit') {
            $page_data['edit_data'] = $this->db->get_where('invoice', array(
                'invoice_id' => $param2
            ))->result_array();

             //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');
        }
        if ($param1 == 'take_payment') {
            $id = $this->input->post('id');
            $ids = explode('-', $id);
            $inv_ids_array = array();

            $item = array();
            $amount = array();
            $total_amount_paid = 0;

            for($inv = 0; $inv < count($ids); $inv++) {
                $inv_ids_array[$inv] = $ids[$inv];
            }

            //generate receipt No
            $this->db->select('receipt_code');
            $this->db->order_by('receipt_code', 'desc');
            $this->db->limit(1);
            $rec_query = $this->db->get('payment');
            $rec_id    = $rec_query->row()->receipt_code;
            
            if($rec_query->num_rows() > 0) {
                $receipt_code  = $rec_id + 1;
            } else {
                $receipt_code  = 100001; //default receipt no;
            }

            $print_receipt =   $this->input->post('print_receipt'); //check if user wants to print receipt or not

            $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;

            for($vi = 0; $vi < count($inv_ids_array); $vi++) {

                $data['invoice_id']   =   $this->input->post('invoice_id_'.$inv_ids_array[$vi]);
                $data['invoice_code'] =   $this->input->post('invoice_code');
                $data['receipt_code'] =   $receipt_code;
                $data['student_id']   =   $this->input->post('student_id');
                $data['term']         =   $this->input->post('invoice_term');
                $data['title']        =   strtoupper($this->input->post('title_'.$inv_ids_array[$vi]));
                $data['description']  =   $this->input->post('description_'.$inv_ids_array[$vi]);
                $data['payment_type'] =   'income';
                $data['method']       =   $this->input->post('method_'.$inv_ids_array[$vi]);
                $data['amount']       =   $this->input->post('amount_'.$inv_ids_array[$vi]);
                $data['timestamp']    =   strtotime(date('d-m-Y H:i:s'));
                $data['day_timestamp']    =   strtotime(date('d-m-Y'));
                $data['year']         =   $this->input->post('invoice_year');



                if($data['amount'] != '' || $data['amount'] != null) { //do insert or update only if amount is not empty
                    $this->db->insert('payment' , $data);

                    $status['status']   =   strtolower($this->input->post('status_'.$data['invoice_id']));
                    $this->db->where('invoice_id' , $inv_ids_array[$vi]);
                    $this->db->update('invoice' , array('status' => $status['status'], 'payment_timestamp' => $data['timestamp'], 'payment_method' => $data['method']));

                    $data2['amount_paid']   =   $this->input->post('amount_'.$inv_ids_array[$vi]);
                    $data2['status']        =   strtolower($this->input->post('status_'.$inv_ids_array[$vi]));
                    $this->db->where('invoice_id' , $inv_ids_array[$vi]);
                    $this->db->set('amount_paid', 'amount_paid + ' . $data2['amount_paid'], FALSE);
                    $this->db->set('due', 'due - ' . $data2['amount_paid'], FALSE);
                    $this->db->update('invoice');

                    //get items/titles and amount paid for each
                    $item[$vi] = $data['title'];
                    $amount[$vi] = $data['amount'];
                    $total_amount_paid = $total_amount_paid + $amount[$vi];
                }
            }


              //prepare a text message and send to the parent of the child about the payment
             // sms sending configurations
                $payment_details  = $this->db->get_where('invoice', array('student_id' => $data['student_id'], 'invoice_id' => $data['invoice_id'] , 'invoice_code' => $data['invoice_code']))->row();
                $balance   = $payment_details->due;
                $balance_msg = '';
                $title     = $data['title'];
                $student_name      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;

                //get total for this invoice code
                    $this->db->select_sum('amount');
                    $this->db->from('invoice');
                    $this->db->where('invoice_code', $data['invoice_code']);
                    $this->db->where('student_id', $data['student_id']);
                    $this->db->where('term', $data['term']);
                    $this->db->where('year', $data['year']);
                    $amount_total_array = $this->db->get()->result_array();

                    $amount_counter = 0;
                    foreach($amount_total_array as $arow) {
                        $amount_counter += $arow['amount'];
                    }

                //get total balance for this invoice code
                    $this->db->select_sum('amount_paid');
                    $this->db->from('invoice');
                    $this->db->where('invoice_code', $data['invoice_code']);
                    $this->db->where('student_id', $data['student_id']);
                    $this->db->where('term', $data['term']);
                    $this->db->where('year', $data['year']);
                    $amount_due_array = $this->db->get()->result_array();

                    $due_counter = 0;
                    foreach($amount_due_array as $arow) {
                        $due_counter += $arow['amount_paid'];
                    }

                    $balance_msg = '';
                    $title     = $data['title'];
                    $student_name      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;

                    $due_balance = $amount_counter - $due_counter;

                    if($due_balance == 0) {
                        $balance_msg = 'full payment';
                    }elseif($due_balance > 0) {
                        $balance_msg = 'part payment';
                    }elseif($due_balance < 0) {
                        $balance_msg = 'over payment';
                    }

                    $date = date('d M, Y H:i:s');
                    $time     = date('d-m-Y H:i:s');
                    $date_time = strtotime($time);

                    
                    $message  = 'Payment of '.numfmt_format_currency($fmt, $total_amount_paid, $currency).' was received on '.$date. ' as '.$balance_msg. ' of '.$student_name.'\'S bill for term '.$data['term'].'.  Total balance due is '. numfmt_format_currency($fmt, $amount_counter - $due_counter, $currency).'. INVOICE#: '.$data['invoice_code'];

                if($active_sms_service != 'disabled') {

                    $parent_id      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->parent_id;
                    $parent_name      = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->name;

                    if($parent_id != null && $parent_id != 0){
                        $receiver_phone = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->phone;
                        if($receiver_phone != '' || $receiver_phone != null){
                            $this->sms_model->send_sms($message,$receiver_phone);
                             $this->session->set_flashdata('flash_message' , get_phrase('payment_notification_was_sent_to_'.$parent_name.'_successfully.'));
                        }
                        else{
                            $this->session->set_flashdata('error_message' , get_phrase('parent\'s_phone_number_is_not_found._it_seems_you_did_not_add_phone_numbers_to_some_parents\'_details'));
                        }
                    }
                    else{
                        $this->session->set_flashdata('error_message' , get_phrase('no_parent_was_registered_for_this_student.'));
                    }
                } //End of SMS



            //prepare an email message and send to the parent of the child about the payment
            // email sending configurations
                //school's info



            $system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
            $system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
            $system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
            $system_slogan = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
            $cashier = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
            $student_name = $this->db->get_where('student', array('student_id' => $data['student_id']))->row()->name;
            $class_id        = $this->db->get_where('enroll', array('student_id' => $data['student_id'], 'year' => $data['year'], 'term' => $data['term']))->row()->class_id;
            $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
            $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

            //get receipt details
            
            $receipt_code =    $data['receipt_code'];
            $invoice_code =    $data['invoice_code'];
            $student_id   =    $data['student_id'];
            $year         =    $data['year'];
            $term         =    $data['term'];

            //add section A or B if the class has more than one section
            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
            $sec_name = '';
            if($class_has_more_sections > 1) {
                $sec_name = $section_name;
            }

            $class = '';
            if($class_name == 'CRECHE') {
                $class = $class_name;
            } else {
                $class = $class_name. ' '. $class_name_numeric.$sec_name;
            }


        $msg_email = '
        <center>
        <div>
            <table style="border: 1px solid #d0cccc; padding: 10px;">
            <tr>
            <td align="center">
            <div><img src="'. base_url('uploads/school_logo.png').'" style="max-height : 60px;"></div><br>
            <div><strong>'. $system_name.'</strong></div>
            <div><strong>'. $system_phone .' | '. $system_mail.'</strong></div><br>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th align="right">Received From:</th>
                            <th></th>
                            <th align="left">'. $student_name .'</th>
                        </tr>
                        <tr>
                            <th align="right">Student\'s Class:</th>
                            <th></th>
                            <th align="left">'. $class .'</th>
                        </tr>
                        <tr>
                            <th align="right">Receipt Date:</th>
                            <th></th>
                            <th align="left">'. $date. '</th>
                        </tr>
                        <tr>
                            <th align="right">Receipt No:</th>
                            <th></th>
                            <th align="left">'. $data['receipt_code']. '</th>
                        </tr>

                    </thead>
                </table>
            </div><br>
            <table>
                <thead>
                    <tr >
                        <th width="40" align="left">S/No</th>
                        <th width="180" align="left">ITEM</th>
                        <th></th>
                        <th width="120" align="right">AMOUNT</th>
                    </tr>
                    <tr><th colspan="4"><hr></th></tr>
                </thead>
                <tbody>';
   // $msg_email .= $this->crud_model->load_receipt($receipt_code, $invoice_code, $student_id, $year, $term);
    $msg_email .='
                    <tr></tr>
                    <tr></tr>
                    <tr></tr>
                    <tr><td colspan="4"><hr></td></tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td align="right">Sub-total:</td>
                        <td align="right">'.$total_amount_paid.'</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td align="right">Tax:</td>
                        <td align="right">0</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td align="right">Net Amount Payable:</td>
                        <td align="right" style="font-weight: bolder;">'.numfmt_format_currency($fmt, $total_amount_paid, $currency).'</td>
                    </tr>

                    <tr><td><br></td></tr>
                    <tr><td colspan="4">
                        <strong>Cashier: </strong>'. ucwords(strtolower($cashier)).'
                        <hr>
                    </td></tr>
                    <tr><td colspan="4" align="center">
                        <p>'. $system_slogan."!!!".'</p>
                        <p><strong>Thank You</strong></p>
                    </td></tr>
                </tbody>
            </table>

            <div style="margin-left: 450px;">
                
            </div>
        </div>
        </td>
        </tr>
        </table>
        </center>';
                $owner_email = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;

                $student_name   = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;
                    $parent_id      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->parent_id;
                    $parent_name      = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->name;

                    if($parent_id != null && $parent_id != 0){
                        $receiver_email = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->email;
                        if($receiver_email != '' || $receiver_email != null){
                         // $this->email_model->do_email($msg_email, 'Payment Notification', $receiver_email, $owner_email);
                             $this->session->set_flashdata('flash_message' , get_phrase('payment_notification_was_sent_to_'.$parent_name.'_successfully.'));
                        }
                        else{
                            $this->session->set_flashdata('error_message' , get_phrase('parent\'s_email_not_found._it_seems_you_did_not_add_phone_numbers_to_some_parents\'_details'));
                        }
                    }
                    else{
                        $this->session->set_flashdata('error_message' , get_phrase('no_parent_was_not_found_for_some_students.'));
                    } //End of Email

            //clear the cached database
            $this->db->cache_delete();
            //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');

            $this->session->set_flashdata('flash_message' , get_phrase('payment_successful'));
           

            if($print_receipt == 1) { //load receipt or go back to the invoices page
                redirect(site_url('accountant/receipt/'. $receipt_code. '/'. $invoice_code .'/'. $student_id .'/'. $year .'/'. $term .'/'.$total_amount_paid .'/'. $date_time));
            } else {
                 redirect(site_url('accountant/income'));
            }
            
        }

        if ($param1 == 'delete') {
            $this->db->where('invoice_code', $param2);
            //$this->db->delete('invoice');

            //delete from payment table as well
            $this->db->where('invoice_code', $param2);
           // $this->db->delete('payment');

             //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');

            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('accountant/income'));
        }
        $page_data['page_name']  = 'invoice';
        $page_data['page_title'] = get_phrase('manage_invoice/payment');
        $this->db->order_by('creation_timestamp', 'desc');
        $page_data['invoices'] = $this->db->get('invoice')->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    /**********ACCOUNTING********************/
    function income($param1 = 'invoices', $param2 = '', $param3 = '') {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        
        $page_data['page_name'] = 'income';
        $page_data['page_title'] = get_phrase('student_payments');
        
        if($param2 == 'invoice_search') {
            $page_data['inner'] = 'invoices_loaded';
            $year = $param3;
            $term = $param4;
            $page_data['invoices'] = $this->crud_model->load_invoice($year, $term);
            $page_data['syear'] = $year;
            $page_data['sterm'] = $term;

            $this->load->view('backend/accountant/invoices_loaded', $page_data);
        } else {
            $page_data['inner'] = $param1;
            $page_data['invoices'] = $this->crud_model->load_invoice();
            $page_data['syear'] = get_settings('running_year');
            $page_data['sterm'] = get_settings('running_term');

            

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
        }
    }

     function get_invoices() {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $running_year  = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
        $running_term  = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;

        $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
        $current_user     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');

        $columns = array(
            0 => 'invoice_code',
            1 => 'student',
            2 => 'term',
            3 => 'total',
            4 => 'paid',
            5 => 'status',
            6 => 'date',
            7 => 'options',
            8 => 'invoice_id',
            9 => 'checked',
            10 => 'class'
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->all_invoices_count();
        $totalFiltered = $totalData;

        /**if(empty($this->input->post('search')['value'])) {            
            $invoices = $this->ajaxload->all_invoices($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value']; 
            $invoices =  $this->ajaxload->invoice_search($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->invoice_search_count($search);
        } **/

         $invoices = $this->ajaxload->all_invoices($limit,$start,$order,$dir);

        $data = array();
        if(!empty($invoices)) {
            foreach ($invoices as $row) {

                if($row->invoice_code[0] == 0) {
                        $in_code = '_'.$row->invoice_code;
                } else {
                    $in_code = $row->invoice_code;
                }

                //amount due
                $this->db->select_sum('due');
                $a_due = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->due;

                //total amount
                $this->db->select_sum('amount');
                $total_amount = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->amount;

                //total amount paid
                $this->db->select_sum('amount_paid');
                $amount_paid = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->amount_paid;

                //year and term
                $year = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->year;
                //year and term
                $term = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->term;

                //creation date
                $creation_timestamp = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->creation_timestamp;

                //student_id
                $student_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->student_id;

                //current_class id
                $class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year, 'term' => $running_term))->row()->class_id;
                $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

                //add section A or B if the class has more than one section
                $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                $sec_name = '';
                if($class_has_more_sections > 1) {
                    $sec_name = $section_name;
                }
                $student_class = $class_name.' '. $class_name_numeric.$sec_name;


                //invoice_id
                $invoice_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->invoice_id;
                

                if ($a_due == 0) {
                    $status = '<button class="btn btn-success btn-xs">'.get_phrase('paid').'</button>';
                    $payment_text = 'View Receipts';
                }elseif ($a_due < 0) {
                    $status = '<button class="btn btn-warning btn-xs">'.get_phrase('over_paid').'</button>';
                    $payment_text = 'View Receipts';
                } else {
                    $status = '<button class="btn btn-danger btn-xs">'.get_phrase('unpaid').'</button>';
                    
                    $payment_text = 'Take Payment';
                }

                $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$in_code.')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;'.get_phrase($payment_text).'</a></li><li class="divider"></li>';
                    
                $bulk_invoice_sel = '
                        <input type="checkbox" class="checkbox" onclick="boxChecked()" name="invoices_sel[]" value="'.$row->invoice_code.'">
                        
                        ';

                $options = '<div class="btn-group"><button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                                    Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$in_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a></li>

                                    <li><a href="#" onclick="bulk_invoice_view_modal('.$student_id.')" style="color: black;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_bulk_invoice').'</a></li><li class="divider"></li>

                                    <li class="divider"></li><li><a href="#" onclick="invoice_edit_modal('.$invoice_id.')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;'.get_phrase('edit').'</a></li><li class="divider"></li><li><a href="#" onclick="invoice_delete_confirm('.$in_code.')" style="color: red;"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';

                $nestedData['checked']      = $bulk_invoice_sel;
                $nestedData['invoice_code'] = $row->invoice_code;
                $nestedData['student'] = $this->crud_model->get_type_name_by_id('student',$student_id);
                $nestedData['class']      = $student_class;
                $nestedData['term'] =  '<strong>'.$year.'|'.$term.'</strong>';
                $nestedData['total'] = '<strong>'.numfmt_format_currency($fmt, $total_amount, $currency).'</strong>';
                $nestedData['paid']  = '<strong>'.numfmt_format_currency($fmt, $amount_paid, $currency).'</strong>';
                $nestedData['status'] = $status;
                $nestedData['date'] = date('d M, Y', $creation_timestamp);
                $nestedData['options'] = $options;
                $nestedData['invoice_id'] = $row->invoice_id;
                
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

    function get_payments() {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $columns = array(
            0 => 'payment_id',
            1 => 'title',
            2 => 'description',
            3 => 'method',
            4 => 'amount',
            5 => 'date',
            6 => 'options',
            7 => 'payment_id'
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->all_payments_count();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $payments = $this->ajaxload->all_payments($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $payments =  $this->ajaxload->payment_search($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->payment_search_count($search);
        }

        $data = array();
        if(!empty($payments)) {
            foreach ($payments as $row) {

                if ($row->method == 1)
                    $method = get_phrase('cash');
                else if ($row->method == 2)
                    $method = get_phrase('cheque');
                else if ($row->method == 3)
                    $method = get_phrase('card');
                else
                    $method = 'Mobile Money';

                if($row->invoice_code[0] == 0 && $row->invoice_code != null) {
                        $in_code = '_'.$row->invoice_code;
                } else if($row->invoice_code == '' || $row->invoice_code != null) {
                        $no_invoice = 1;
                        $empty_inv_code_counter++;
                }else {
                    $in_code = $row->invoice_code;
                }


                $options = '<a href="#" class="btn btn-primary btn-sm" onclick="invoice_view_modal(\''.$in_code.'\')"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a> &nbsp;

                <a href="#" class="btn btn-danger btn-sm" onclick="bulk_invoice_view_modal('.$student_id.')"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_bulk_invoice').'</a>';

                $nestedData['payment_id'] = $row->payment_id;
                $nestedData['title'] = $row->title;
                $nestedData['description'] = $row->description;
                $nestedData['method'] = $method;
                $nestedData['amount'] = numfmt_format_currency($fmt, $row->amount, $currency);
                $nestedData['date'] = date('d M, Y', $row->timestamp);

                if($no_invoice != 1):
                $nestedData['options'] = $options;
                    else:
                       $nestedData['options'] = 'No Invoice'; 
                 endif;


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

    function student_invoice($param1 = '' , $param2 = '' , $param3 = '') {

        if ($this->session->userdata('accountant_login') != 1)
            redirect('login');
        $page_data['page_name']  = 'student_payment';
        $page_data['page_title'] = get_phrase('create_student_invoice');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data); 
    }

    function expense($param1 = '' , $param2 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect('login');
        if ($param1 == 'create') {
            $data['title']               =   $this->input->post('title');
            $data['expense_category_id'] =   $this->input->post('expense_category_id');
            if ($this->input->post('description') != null) {
               $data['description']         =   $this->input->post('description');
            }
            
            $data['payment_type']        =   'expense';
            $data['method']              =   $this->input->post('method');
            $data['amount']              =   $this->input->post('amount');
            $data['timestamp']           =   strtotime($this->input->post('timestamp'));
            $data['term']                =   $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            $data['year']                =   $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            $this->db->insert('payment' , $data);
            $this->session->set_flashdata('flash_message' , get_phrase('data_added_successfully'));
            redirect(site_url('accountant/expense'));
        }

        if ($param1 == 'edit') {
            $data['title']               =   $this->input->post('title');
            $data['expense_category_id'] =   $this->input->post('expense_category_id');
            if ($this->input->post('description') != null) {
               $data['description']         =   $this->input->post('description');
            }
            $data['payment_type']        =   'expense';
            $data['method']              =   $this->input->post('method');
            $data['amount']              =   $this->input->post('amount');
            $data['timestamp']           =   strtotime($this->input->post('timestamp'));
            $data['term']                =   $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            $data['year']                =   $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
            $this->db->where('payment_id' , $param2);
            $this->db->update('payment' , $data);
            $this->session->set_flashdata('flash_message' , get_phrase('data_updated'));
            redirect(site_url('accountant/expense'));
        }

        if ($param1 == 'delete') {
            $this->db->where('payment_id' , $param2);
            $this->db->delete('payment');
            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('accountant/expense'));
        }

        $page_data['page_name']  = 'expense';
        $page_data['page_title'] = get_phrase('expenses');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data); 
    }

    function expense_category($param1 = '' , $param2 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect('login');
        if ($param1 == 'create') {
            $data['name']   =   $this->input->post('name');
            $this->db->insert('expense_category' , $data);
            $this->session->set_flashdata('flash_message' , get_phrase('data_added_successfully'));
            redirect(site_url('accountant/expense_category'));
        }
        if ($param1 == 'edit') {
            $data['name']   =   $this->input->post('name');
            $this->db->where('expense_category_id' , $param2);
            $this->db->update('expense_category' , $data);
            $this->session->set_flashdata('flash_message' , get_phrase('data_updated'));
            redirect(site_url('accountant/expense_category'));
        }
        if ($param1 == 'delete') {
            $this->db->where('expense_category_id' , $param2);
            $this->db->delete('expense_category');
            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('accountant/expense_category'));
        }

        $page_data['page_name']  = 'expense_category';
        $page_data['page_title'] = get_phrase('expense_category');
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
    
    // MANAGE OWN PROFILE AND CHANGE PASSWORD
    function manage_profile($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        if ($param1 == 'update_profile_info') {
            $data['name']  = strtoupper($this->input->post('name'));
            $data['email'] = strtolower($this->input->post('email'));
            $validation = email_validation_for_edit($data['email'], $this->session->userdata('accountant_id'), 'accountant');
            if ($validation == 1) {
                $this->db->where('accountant_id', $this->session->userdata('accountant_id'));
                $this->db->update('accountant', $data);
                $this->session->set_flashdata('flash_message', get_phrase('account_updated'));
            }
            else{
                $this->session->set_flashdata('error_message', get_phrase('this_email_id_is_not_available'));
            }
            redirect(site_url('accountant/manage_profile'));
        }

        if ($param1 == 'change_password') {
            $data['password']             = $this->input->post('password');
            $data['new_password']         = password_hash($this->input->post('new_password'), PASSWORD_BCRYPT);
            $data['confirm_new_password'] = $this->input->post('confirm_new_password');
            
            $current_password = $this->db->get_where('accountant', array(
                'accountant_id' => $this->session->userdata('accountant_id')
            ))->row()->password;
            if (password_verify($data['password'], $current_password) == 1 && password_verify($data['confirm_new_password'], $data['new_password']) == 1) {
                $this->db->where('accountant_id', $this->session->userdata('accountant_id'));
                $this->db->update('accountant', array(
                    'password' => $data['new_password']
                ));
                $this->session->set_flashdata('flash_message', get_phrase('password_updated_successfully'));
            } else {
                $this->session->set_flashdata('flash_message', get_phrase('password_mismatch!'));
            }
            redirect(site_url('accountant/manage_profile'));
        }
        
        $page_data['page_name']  = 'manage_profile';
        $page_data['page_title'] = get_phrase('manage_profile');
        $page_data['edit_data']  = $this->db->get_where('accountant', array(
            'accountant_id' => $this->session->userdata('accountant_id')
        ))->result_array();
        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function get_expenses() {
        if ($this->session->userdata('accountant_login') != 1)
            redirect(site_url('login'));

        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $columns = array(
            0 => 'payment_id',
            1 => 'title',
            2 => 'category',
            3 => 'method',
            4 => 'amount',
            5 => 'date',
            6 => 'options',
            7 => 'payment_id'
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->all_expenses_count();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $expenses = $this->ajaxload->all_expenses($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $expenses =  $this->ajaxload->expense_search($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->expense_search_count($search);
        }

        $data = array();
        if(!empty($expenses)) {
            foreach ($expenses as $row) {
                $category = $this->db->get_where('expense_category', array('expense_category_id' => $row->expense_category_id))->row()->name;
                if ($row->method == 1)
                    $method = get_phrase('cash');
                else if ($row->method == 2)
                    $method = get_phrase('cheque');
                else if ($row->method == 3)
                    $method = get_phrase('card');
                else
                    $method = get_phrase('mobile_money');
                $options = '<div class="btn-group"><button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                                    Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="expense_edit_modal('.$row->payment_id.')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;'.get_phrase('edit').'</a></li><li class="divider"></li><li><a href="#" onclick="expense_delete_confirm('.$row->payment_id.')" style="color: red;"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';

                $nestedData['payment_id'] = $row->payment_id;
                $nestedData['title'] = $row->title;
                $nestedData['category'] = $category;
                $nestedData['method'] = $method;
                $nestedData['amount'] = numfmt_format_currency($fmt, $row->amount, $currency);
                $nestedData['date'] = date('d M,Y', $row->timestamp);
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

    function get_sections_for_ssph($class_id) {
        $sections = $this->db->get_where('section', array('class_id' => $class_id))->result_array();
        $options = '';
        foreach ($sections as $row) {
            $options .= '<option value="'.$row['section_id'].'">'.$row['name'].'</option>';
        }
        echo '<select class="" name="section_id" id="section_id">'.$options.'</select>';
    }

    function get_students_for_ssph($class_id, $section_id) {
        $enrolls = $this->db->get_where('enroll', array('class_id' => $class_id, 'section_id' => $section_id))->result_array();
        $options = '';
        foreach ($enrolls as $row) {
            $name = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name;
            $options .= '<option value="'.$row['student_id'].'">'.$name.'</option>';
        }
        echo '<select class="" name="student_id" id="student_id">'.$options.'</select>';
    }

    function get_payment_history_for_ssph($student_id) {
        $page_data['student_id'] = $student_id;
        $this->load->view('backend/admin/student_specific_payment_history_table', $page_data);
    }

    function get_class_students_mass($class_id)
    {
        $students = $this->db->get_where('enroll' , array(
            'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ));

         if($students->num_rows() < 1) {echo "<h4 style='color: red;'>No record was found for the selected class for Term ".$this->db->get_where('settings' , array('type' => 'running_term'))->row()->description." </h4>";
        }
         $student_array = $students->result_array();
        echo '<div class="form-group">
                <label class="col-sm-3 control-label">' . get_phrase('students') . '</label>
                <div class="col-sm-9">';
        foreach ($student_array as $row) {
             $name = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;
            echo '<div class="checkbox">
                    <label><input type="checkbox" class="check" name="student_id[]" value="' . $row['student_id'] . '">' . $name .'</label>
                </div>';
        }
        echo '<br><button type="button" class="btn btn-success" onClick="select()">'.get_phrase('select_all').'</button>';
        echo '<button style="margin-left: 5px;" type="button" class="btn btn-danger" onClick="unselect()"> '.get_phrase('select_none').' </button>';
        echo '</div></div><br><br>';

        echo '<div class="form-group">
                <div class="col-sm-4 col-sm-offset-4">
                    <button type="submit" class="btn btn-info submit2">'. get_phrase('add_invoice').'</button>
                </div>
             </div>';
    }

    function get_class_students($class_id)
    {
        $students = $this->db->get_where('enroll' , array(
            'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
        ))->result_array();
       
        foreach ($students as $row) {
            $name = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;
            echo '<option value="' . $row['student_id'] . '">' . $name . '</option>';
        }
    }

    function modal_mobile_money_checkout($param = '', $invoice_id, $student_id = '', $page_name = '', $timestamp = '') {
        if($this->session->userdata('accountant_login') != 1) redirect(site_url('login')); 

        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        if($param == 'view') {
            $page_data['student_id'] = $student_id;
            $page_data['invoice_id'] = $invoice_id;
            $page_data['page_name'] = $page_name;
            $page_data['timestamp'] = $timestamp;
            $this->load->view('backend/accountant/modal_mobile_money_payment', $page_data);   
        }
        
        if($param == 'confirm_t_id') {

            $this->form_validation->set_rules('confirm_t_id', 'Transaction ID', 'required|trim|max_length[10]|min_length[10]');
            if($this->form_validation->run() === FALSE) {
                $this->load->view('backend/accountant/modal_mobile_money_payment', $page_data);
            }else {
               //do payment
                $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;

                $data['invoice_id']   =   $this->input->post('invoice_id');
                $data['invoice_code'] =   $this->input->post('invoice_code');
                $data['student_id']   =   $this->input->post('student_id');
                $data['term']         =   $this->input->post('term');
                $data['title']        =   strtoupper($this->input->post('title'));
                $data['description']  =   $this->input->post('description');
                $data['payment_type'] =   'income';
                $data['method']       =   4;
                $data['amount']       =   $this->input->post('amount_paid');
                $data['timestamp']    =   $this->input->post('timestamp');
                $data['year']         =   $this->input->post('year');
                $this->db->insert('payment' , $data);

                $status['status']   =   strtolower($this->input->post('status'));
                $this->db->where('invoice_id' , $invoice_id);
                $this->db->update('invoice' , array('status' => $status['status'], 'payment_timestamp' => $data['timestamp'], 'payment_method' => $data['method']));

                $data2['amount_paid']   =   $this->input->post('amount_paid');
                $data2['status']        =   strtolower($this->input->post('status'));
                $this->db->where('invoice_id' , $invoice_id);
                $this->db->set('amount_paid', 'amount_paid + ' . $data2['amount_paid'], FALSE);
                $this->db->set('due', 'due - ' . $data2['amount_paid'], FALSE);
                $this->db->update('invoice');

                //prepare a text message and send to the parent of the child about the payment
                 // sms sending configurations
                    $payment_details  = $this->db->get_where('invoice', array('student_id' => $data['student_id'], 'invoice_id' => $data['invoice_id'] , 'invoice_code' => $data['invoice_code']))->row();
                    $balance   = $payment_details->due;
                    $balance_msg = '';
                    $title     = $data['title'];
                    $student_name      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;

                    //get total balance for this invoice code
                    $this->db->select_sum('amount');
                    $this->db->from('invoice');
                    $this->db->where('invoice_code', $data['invoice_code']);
                    $this->db->where('student_id', $data['student_id']);
                    $this->db->where('term', $data['term']);
                    $this->db->where('year', $data['year']);
                    $amount_total_array = $this->db->get()->result_array();

                    $amount_counter = 0;
                    foreach($amount_total_array as $arow) {
                        $amount_counter += $arow['amount'];
                    }

                //get total balance for this invoice code
                    $this->db->select_sum('amount_paid');
                    $this->db->from('invoice');
                    $this->db->where('invoice_code', $data['invoice_code']);
                    $this->db->where('student_id', $data['student_id']);
                    $this->db->where('term', $data['term']);
                    $this->db->where('year', $data['year']);
                    $amount_due_array = $this->db->get()->result_array();

                    $due_counter = 0;
                    foreach($amount_due_array as $arow) {
                        $due_counter += $arow['amount_paid'];
                    }

                    $balance   = $payment_details->due;
                    $balance_msg = '';
                    $title     = $data['title'];
                    $student_name      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;

                    if($balance == 0) {
                        $balance_msg = 'full payment';
                    }elseif($balance > 0) {
                        $balance_msg = 'part payment';
                    }elseif($balance < 0) {
                        $balance_msg = 'over payment';
                    }

                    $date     = date('d M, Y ');
                   // $time     = $this->input->post('payment_time');

                    $message  = 'Payment of '.numfmt_format_currency($fmt, $data['amount'], $currency).' was received on '.$date. ' as '.$balance_msg. ' of '.$student_name.'\'s '.$title.' for term '.$data['term'].'. Balance for '.$title.' is '. numfmt_format_currency($fmt, $balance, $currency).' and total balance for INVOICE#: '.$data['invoice_code'].' is '. numfmt_format_currency($fmt, $amount_counter - $due_counter, $currency);

                    if($active_sms_service != 'disabled') {

                        $parent_id      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->parent_id;
                        $parent_name      = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->name;
                        if($parent_id != null && $parent_id != 0){
                            $receiver_phone = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->phone;
                            if($receiver_phone != '' || $receiver_phone != null){
                                $this->sms_model->send_sms($message,$receiver_phone);
                                 $this->session->set_flashdata('flash_message' , get_phrase('payment_notification_was_sent_to_'.$parent_name.'_successfully.'));
                            }
                            else{
                                $this->session->set_flashdata('error_message' , get_phrase('parent\'s_phone_number_is_not_found._it_seems_you_did_not_add_phone_numbers_to_some_parents\'_details'));
                            }
                        }
                        else{
                            $this->session->set_flashdata('error_message' , get_phrase('no_parent_was_registered_for_this_student.'));
                        }
                    } //End of SMS

                //prepare an email message and send to the parent of the child about the payment
                // email sending configurations
                    //school's info
                    $owner_email = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;

                    $student_name   = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->name;
                        $parent_id      = $this->db->get_where('student' , array('student_id' => $data['student_id']))->row()->parent_id;
                        $parent_name      = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->name;

                        if($parent_id != null && $parent_id != 0){
                            $receiver_email = $this->db->get_where('parent' , array('parent_id' => $parent_id))->row()->email;
                            if($receiver_email != '' || $receiver_email != null){
                              $this->email_model->do_email($message, 'Payment Notification', $receiver_email, $owner_email);
                                 $this->session->set_flashdata('flash_message' , get_phrase('payment_notification_was_sent_to_'.$parent_name.'_successfully.'));
                            }
                            else{
                                $this->session->set_flashdata('error_message' , get_phrase('parent\'s_email_not_found._it_seems_you_did_not_add_phone_numbers_to_some_parents\'_details'));
                            }
                        }
                        else{
                            $this->session->set_flashdata('error_message' , get_phrase('no_parent_was_not_found_for_some_students.'));
                        } //End of Email

                    //delete the payment request from mobile_money_payment table
                    $this->db->where('invoice_id' , $invoice_id);
                    $this->db->where('timestamp' , $data['timestamp']);
                    $this->db->delete('mobile_money_payment');

                $this->session->set_flashdata('flash_message' , get_phrase('payment_successful'));
                redirect(site_url('accountant/'.$page_name));
                }
            
        }
    }

    //Transaction ID confirmation via ajax
    function confirm_transaction($student_id, $invoice_id, $parent_t_id, $admin_t_id) {
        if($this->session->userdata('accountant_login') != 1) redirect(site_url('login')); 
        $this->crud_model->confirm_transaction($student_id, $invoice_id, $parent_t_id, $admin_t_id);
    }

    //receipt
   function receipt($receipt_code, $invoice_code, $student_id, $year, $term, $total_amount_paid, $date_time) {
    $data['receipt_code'] = $receipt_code;
    $data['invoice_code'] = $invoice_code;
    $data['student_id'] = $student_id;
    $data['year'] = $year;
    $data['term'] = $term;
    $data['total_amount_paid'] = $total_amount_paid;
    $data['date'] = $date_time;

    $this->load->view('backend/admin/receipt', $data);
   }

   //MANAGING BULK STUDENTS RECEIPTS VIEW
    function cft_student_receipt($param1 = '') {
        if($this->session->userdata('accountant_login') != 1) redirect(site_url('login'));

        if($param1 == 'find') {
            $data['ids_selected'] = $this->input->post('bulk_ids');
            $data['class_id'] = $this->input->post('class_id');
            $data['timestamp'] = strtotime($this->input->post('att_date'));

            $this->load->view('backend/accountant/print_bulk_receipts', $data);

        }else{

        $page_data['page_title'] = get_phrase('print_classes,_feeding_fees_and_transport_fare_receipts');
        $page_data['page_name'] = 'classes_feeding_trs_fees';

        

            $page_data['account_type'] = $this->session->userdata('login_type');
                     $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
        }

    }

    //bulk invoice deletion
    function bulk_invoice_delete() {
        $counter = 0;
        $invoice_array = array();
        $invoice_array = $this->input->post('invoices_sel');
        for($i=0; $i < count($invoice_array); $i++) {

            $this->db->where('invoice_code', $invoice_array[$i]);
            $this->db->delete('invoice');

            //delete from payment table as well
            $this->db->where('invoice_code', $invoice_array[$i]);
            $this->db->delete('payment');

            //clear the cached database
            $this->db->cache_delete();

            $counter++;

        }

         $this->session->set_flashdata('flash_message' , get_phrase($counter .' invoices_deleted'));
            redirect(site_url('accountant/income'));
    }

}
