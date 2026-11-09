<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
class Modal extends CI_Controller {


	function __construct()
    {
        parent::__construct();
		$this->load->database();
		$this->load->library('session');
        $this->load->library('csvimport');

		/*cache control*/
		$this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    }

	/***default functin, redirects to login page if no admin logged in yet***/
	public function index()
	{

	}


	/*
	*	$page_name		=	The name of page
	*/
	function popup($page_name = '' , $param2 = '' , $param3 = '', $param4 = '', $param5 = '', $param6 = '')
	{
		$account_type		=	$this->session->userdata('login_type');
		$page_data['param2']		=	$param2;
		$page_data['param3']		=	$param3;
		$page_data['param4']		=	$param4;
		$page_data['param5']		=	$param5;
		$page_data['param6']		=	$param6;
		
		// Handle modal_view_receipts specially
		if($page_name == 'modal_view_receipts') {
			$page_data['student_id'] = $param2;
		}
		
		// Handle inventory modal pages - combine inventory + modal name
		if($page_name == 'inventory' && !empty($param2) && strpos($param2, 'modal_') === 0) {
			$page_name = 'inventory/' . $param2;
			// Shift parameters
			$page_data['param2'] = $param3;
			$page_data['param3'] = $param4;
			$page_data['param4'] = $param5;
			$page_data['param5'] = $param6;
		}
		
		// Handle modal_non_teaching_staff_edit - always load from admin folder
		if($page_name == 'modal_non_teaching_staff_edit') {
			$this->load->view( 'backend/admin/'.$page_name.'.php' ,$page_data);
		}
		// Handle inventory modals with subfolder path
		else if(strpos($page_name, 'inventory/') === 0) {
			$this->load->view( 'backend/'.$account_type.'/'.$page_name.'.php' ,$page_data);
		}
		else {
			$this->load->view( 'backend/'.$account_type.'/'.$page_name.'.php' ,$page_data);
		}

		echo '<script src="'. base_url(). 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">

			$(function() {
				$(".select2").select2({
					theme: "classic"
					});
				
			})
			
		</script>';
	}

	function popup_receipt($page_name = '' , $param1 = '' , $param2 = '', $param3 = '' , $param4 = '' , $param5 = '' , $param6 = '' , $param7 = '')
	{
		$account_type		=	$this->session->userdata('login_type');
		$page_data['param1']		=	$param1;
		$page_data['param2']		=	$param2;
		$page_data['param3']		=	$param3;
		$page_data['param4']		=	$param4;
		$page_data['param5']		=	$param5;
		$page_data['param6']		=	$param6;
		$page_data['param7']		=	$param7;
		$this->load->view( 'backend/'.$account_type.'/'.$page_name.'.php' ,$page_data);

		echo '<script src="'. base_url(). 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">

			$(function() {
				$(".select2").select2({
					theme: "classic"
					});
				
			})
			
		</script>';
	}

	function popup_2($page_name = '' , $param2 = '') {
		$account_type		=	$this->session->userdata('login_type');

		//decode the url from showing %20
		$page_data['param2'] = urldecode($param2);
		$this->load->view( 'backend/'.$account_type.'/'.$page_name.'.php' ,$page_data);

		echo '<script src="'. base_url(). 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">

			$(function() {
				$(".select2").select2({
					theme: "classic"
					});
				
			})
			
		</script>';
	}

	function popup_idle_user($page_name = '' , $param2 = '' , $param3 = '', $param4 = '')
	{
		$page_data['param2']		=	$param2;
		$page_data['param3']		=	$param3;
		$page_data['param4']		=	$param4;
		$this->load->view( 'backend/'.$page_name.'.php' ,$page_data);

		echo '<script src="'. base_url(). 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">

			$(function() {
				$(".select2").select2({
					theme: "classic"
					});
				
			})
			
		</script>';
	}

	function popup_professional($page_name = '' , $param2 = '' , $param3 = '', $param4 = '')
	{
		$account_type		=	$this->session->userdata('login_type');
		$page_data['param2']		=	$param2;
		$page_data['param3']		=	$param3;
		$page_data['param4']		=	$param4;
		$this->load->view( 'backend/'.$account_type.'/'.$page_name.'.php' ,$page_data);
	}

	/**
	 * Display daily revenue modal
	 * 
	 * @param int $timestamp Unix timestamp for the date
	 */
	function popup_daily_revenue($timestamp = null)
	{
		if ($timestamp === null) {
			$timestamp = strtotime(date('d-m-Y'));
		}
		
		$page_data['timestamp'] = $timestamp;
		$page_data['fee_type'] = $this->input->get('fee_type') ?? 'all';
		$page_data['class_id'] = $this->input->get('class_id') ?? 'all';
		
		$this->load->view('backend/admin/modal_daily_revenue', $page_data);
	}

	/**
	 * Display payroll edit modal
	 * 
	 * @param int $pay_id The payroll ID to edit
	 */
	function popup_payroll_edit($pay_id = null)
	{
		if ($pay_id === null) {
			echo '<div class="alert alert-danger">Payroll ID is required.</div>';
			return;
		}
		
		$page_data['pay_id'] = $pay_id;
		$account_type = $this->session->userdata('login_type');
		$this->load->view('backend/' . $account_type . '/payroll_edit_form', $page_data);
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">
			$(function() {
				$(".select2").select2({
					theme: "classic"
				});
			})
		</script>';
	}

	/**
	 * Display add pension provider modal
	 */
	function modal_pension_provider_add()
	{
		$this->load->view('backend/modal/modal_pension_provider_add');
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">
			$(function() {
				$(".select2").select2({
					theme: "classic"
				});
			})
		</script>';
	}

	/**
	 * Display edit pension provider modal
	 * 
	 * @param int $provider_id The provider ID to edit
	 */
	function modal_pension_provider_edit($provider_id = null)
	{
		if ($provider_id === null) {
			echo '<div class="alert alert-danger">' . get_phrase('provider_id_required') . '</div>';
			return;
		}
		
		$page_data['provider_id'] = $provider_id;
		$this->load->view('backend/modal/modal_pension_provider_edit', $page_data);
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>
		<script type="text/javascript">
			$(function() {
				$(".select2").select2({
					theme: "classic"
				});
			})
		</script>';
	}

	/**
	 * Display daily fee reconciliation adjust balance modal
	 * 
	 * @param int $student_id The student ID
	 */
	function daily_fee_reconciliation_adjust($student_id = null)
	{
		if ($student_id === null) {
			echo '<div class="alert alert-danger">Student ID required</div>';
			return;
		}
		
		$this->load->model('Daily_fee_model');
		
		$page_data['student_id'] = $student_id;
		$page_data['student'] = $this->db->get_where('student', array('student_id' => $student_id))->row();
		
		// Get active fee modules
		$active_modules = array();
		if (is_fee_module_enabled('feeding')) $active_modules[] = 'feeding';
		if (is_fee_module_enabled('breakfast')) $active_modules[] = 'breakfast';
		if (is_fee_module_enabled('classes')) $active_modules[] = 'classes';
		if (is_fee_module_enabled('water')) $active_modules[] = 'water';
		if (is_fee_module_enabled('transport')) $active_modules[] = 'transport';
		$page_data['active_modules'] = $active_modules;
		
		$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		$page_data['running_year'] = $running_year;
		$page_data['running_term'] = $running_term;
		
		$this->load->view('backend/modal/daily_fee_reconciliation_adjust', $page_data);
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>';
	}

	/**
	 * Display daily fee reconciliation bulk adjust modal
	 */
	function daily_fee_reconciliation_bulk_adjust()
	{
		// Get active fee modules
		$active_modules = array();
		if (is_fee_module_enabled('feeding')) $active_modules[] = 'feeding';
		if (is_fee_module_enabled('breakfast')) $active_modules[] = 'breakfast';
		if (is_fee_module_enabled('classes')) $active_modules[] = 'classes';
		if (is_fee_module_enabled('water')) $active_modules[] = 'water';
		if (is_fee_module_enabled('transport')) $active_modules[] = 'transport';
		$page_data['active_modules'] = $active_modules;
		
		$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		$page_data['running_year'] = $running_year;
		$page_data['running_term'] = $running_term;
		
		$this->load->view('backend/modal/daily_fee_reconciliation_bulk_adjust', $page_data);
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>';
	}

	/**
	 * Display new bulk reconciliation modal with student selection
	 */
	function daily_fee_bulk_reconciliation()
	{
		// Get active fee modules
		$active_modules = array();
		if (is_fee_module_enabled('feeding')) $active_modules[] = 'feeding';
		if (is_fee_module_enabled('breakfast')) $active_modules[] = 'breakfast';
		if (is_fee_module_enabled('classes')) $active_modules[] = 'classes';
		if (is_fee_module_enabled('water')) $active_modules[] = 'water';
		if (is_fee_module_enabled('transport')) $active_modules[] = 'transport';
		$page_data['active_modules'] = $active_modules;
		
		$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		$page_data['running_year'] = $running_year;
		$page_data['running_term'] = $running_term;
		
		$this->load->view('backend/modal/daily_fee_bulk_reconciliation', $page_data);
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>';
	}

	/**
	 * Display daily fee reconciliation history modal
	 * 
	 * @param int $student_id The student ID
	 */
	function daily_fee_reconciliation_history($student_id = null)
	{
		if ($student_id === null) {
			echo '<div class="alert alert-danger">Student ID required</div>';
			return;
		}
		
		$page_data['student_id'] = $student_id;
		$page_data['student'] = $this->db->get_where('student', array('student_id' => $student_id))->row();
		$this->load->view('backend/modal/daily_fee_reconciliation_history', $page_data);
		
		echo '<script src="' . base_url() . 'assets/js/neon-custom-ajax.js"></script>';
	}
}
