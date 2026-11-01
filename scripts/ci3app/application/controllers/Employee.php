<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Employee Self-Service Portal Controller (Task 16)
 * 
 * Provides employees with access to their payroll information
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1-3.8, 4.1-4.7, 5.1-5.7
 */
class Employee extends MY_Controller {
	
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->library('session');
		
		// Cache control
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
		
		// Check if user is logged in as employee
		if ($this->session->userdata('employee_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}
	}
	
	/**
	 * Task 16.2: Employee Portal Homepage
	 * 
	 * Display welcome screen with navigation options
	 * Requirements: 2.2, 2.3
	 */
	public function portal() {
		$employee_code = $this->session->userdata('employee_code');
		$employment_category = $this->session->userdata('employment_category');
		
		// Get employee details
		$staff_data = $this->get_employee_details($employee_code, $employment_category);
		
		$page_data['staff_name'] = $staff_data['name'];
		$page_data['staff_code'] = $employee_code;
		$page_data['employment_category'] = $employment_category;
		
		$this->load->view('backend/employee/employee_portal', $page_data);
	}
	
	/**
	 * Task 16.3: View Payslips
	 * 
	 * Display list of all payslips for logged-in employee
	 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8
	 */
	public function view_payslips() {
		$employee_code = $this->session->userdata('employee_code');
		$employment_category = $this->session->userdata('employment_category');
		
		// Get employee details
		$staff_data = $this->get_employee_details($employee_code, $employment_category);
		
		// Get all payslips for this employee
		$this->db->select('pay_id, month, year, gross_salary, total_deductions, net_salary, approval_status as status, created_at');
		$this->db->from('pay_salary');
		$this->db->where('employee_code', $employee_code);
		$this->db->where('employment_category', $employment_category);
		$this->db->order_by('year', 'DESC');
		$this->db->order_by('month', 'DESC');
		$payslips = $this->db->get()->result_array();
		
		$page_data['payslips'] = $payslips;
		$page_data['staff_name'] = $staff_data['name'];
		
		$this->load->view('backend/employee/employee_payslips', $page_data);
	}
	
	/**
	 * Task 16.4: Download Payslip as PDF
	 * 
	 * Generate PDF payslip for download
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
	 */
	public function download_payslip($pay_id) {
		$employee_code = $this->session->userdata('employee_code');
		$employment_category = $this->session->userdata('employment_category');
		
		// Get payslip data
		$this->db->select('*');
		$this->db->from('pay_salary');
		$this->db->where('pay_id', $pay_id);
		$this->db->where('employee_code', $employee_code);
		$this->db->where('employment_category', $employment_category);
		$payslip = $this->db->get()->row_array();
		
		if (!$payslip) {
			show_404();
			return;
		}
		
		// Get employee details
		$staff_data = $this->get_employee_details($employee_code, $employment_category);
		
		// Get school information
		$school_name = $this->db->get_where('settings', ['type' => 'system_name'])->row()->description;
		
		// Prepare data for view
		$page_data['payslip'] = $payslip;
		$page_data['staff_name'] = $staff_data['name'];
		$page_data['staff_code'] = $employee_code;
		$page_data['school_name'] = $school_name;
		
		// Generate filename
		$month_name = date('F', mktime(0, 0, 0, $payslip['month'], 1));
		$filename = str_replace(' ', '_', $staff_data['name']) . '_' . $month_name . '_' . $payslip['year'] . '_payslip.pdf';
		
		// For now, generate HTML-based "PDF" (can be enhanced with TCPDF later)
		$this->load->view('backend/employee/payslip_pdf', $page_data);
	}
	
	/**
	 * Task 16.4: Download All Payslips as ZIP
	 * 
	 * Generate ZIP file with all payslips
	 * Requirements: 4.6, 4.7
	 */
	public function download_all_payslips() {
		$employee_code = $this->session->userdata('employee_code');
		$employment_category = $this->session->userdata('employment_category');
		
		// Get all payslips
		$this->db->select('*');
		$this->db->from('pay_salary');
		$this->db->where('employee_code', $employee_code);
		$this->db->where('employment_category', $employment_category);
		$this->db->order_by('year', 'DESC');
		$this->db->order_by('month', 'DESC');
		$payslips = $this->db->get()->result_array();
		
		if (empty($payslips)) {
			show_error('No payslips available to download.');
			return;
		}
		
		// Get employee details
		$staff_data = $this->get_employee_details($employee_code, $employment_category);
		
		// Create ZIP file
		$this->load->library('zip');
		
		foreach ($payslips as $payslip) {
			// Generate payslip HTML
			$month_name = date('F', mktime(0, 0, 0, $payslip['month'], 1));
			$filename = $month_name . '_' . $payslip['year'] . '_payslip.html';
			
			// Simple HTML content
			$content = $this->generate_payslip_html($payslip, $staff_data['name']);
			$this->zip->add_data($filename, $content);
		}
		
		// Download ZIP
		$zip_filename = str_replace(' ', '_', $staff_data['name']) . '_All_Payslips.zip';
		$this->zip->download($zip_filename);
	}
	
	/**
	 * Task 16.5: Payment History View
	 * 
	 * Display year-to-date summary and monthly breakdown
	 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7
	 */
	public function payment_history() {
		$employee_code = $this->session->userdata('employee_code');
		$employment_category = $this->session->userdata('employment_category');
		
		// Get employee details
		$staff_data = $this->get_employee_details($employee_code, $employment_category);
		
		// Get current year or selected year
		$year = $this->input->get('year') ?: date('Y');
		
		// Get YTD summary
		$this->db->select('
			SUM(gross_salary) as ytd_gross,
			SUM(total_deductions) as ytd_deductions,
			SUM(net_salary) as ytd_net,
			COUNT(*) as month_count
		');
		$this->db->from('pay_salary');
		$this->db->where('employee_code', $employee_code);
		$this->db->where('employment_category', $employment_category);
		$this->db->where('year', $year);
		$ytd_summary = $this->db->get()->row_array();
		
		// Get monthly breakdown
		$this->db->select('month, year, gross_salary, total_deductions, net_salary, approval_status as status');
		$this->db->from('pay_salary');
		$this->db->where('employee_code', $employee_code);
		$this->db->where('employment_category', $employment_category);
		$this->db->where('year', $year);
		$this->db->order_by('month', 'ASC');
		$monthly_breakdown = $this->db->get()->result_array();
		
		// Calculate average
		$avg_monthly_pay = 0;
		if ($ytd_summary['month_count'] > 0) {
			$avg_monthly_pay = $ytd_summary['ytd_net'] / $ytd_summary['month_count'];
		}
		
		$page_data['staff_name'] = $staff_data['name'];
		$page_data['year'] = $year;
		$page_data['ytd_summary'] = $ytd_summary;
		$page_data['monthly_breakdown'] = $monthly_breakdown;
		$page_data['avg_monthly_pay'] = $avg_monthly_pay;
		
		$this->load->view('backend/employee/payment_history', $page_data);
	}
	
	/**
	 * Helper: Get employee details based on employment category
	 */
	private function get_employee_details($employee_code, $employment_category) {
		if ($employment_category == 'teacher') {
			$this->db->select('name, teacher_code as code');
			$this->db->from('teacher');
			$this->db->where('teacher_code', $employee_code);
		} else if ($employment_category == 'administrator') {
			$this->db->select('name, admin_code as code');
			$this->db->from('admin');
			$this->db->where('admin_code', $employee_code);
		} else if ($employment_category == 'non_teaching_staff') {
			$this->db->select('name, staff_code as code');
			$this->db->from('non_teaching_staff');
			$this->db->where('staff_code', $employee_code);
		}
		
		$result = $this->db->get()->row_array();
		
		if (!$result) {
			return ['name' => 'Unknown', 'code' => $employee_code];
		}
		
		return $result;
	}
	
	/**
	 * Helper: Generate simple payslip HTML
	 */
	private function generate_payslip_html($payslip, $staff_name) {
		$month_name = date('F', mktime(0, 0, 0, $payslip['month'], 1));
		
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Payslip</title></head><body>';
		$html .= '<h2>Payslip - ' . $month_name . ' ' . $payslip['year'] . '</h2>';
		$html .= '<p><strong>Staff Name:</strong> ' . $staff_name . '</p>';
		$html .= '<p><strong>Gross Salary:</strong> GH¢ ' . number_format($payslip['gross_salary'], 2) . '</p>';
		$html .= '<p><strong>Total Deductions:</strong> GH¢ ' . number_format($payslip['total_deductions'], 2) . '</p>';
		$html .= '<p><strong>Net Salary:</strong> GH¢ ' . number_format($payslip['net_salary'], 2) . '</p>';
		$html .= '</body></html>';
		
		return $html;
	}
}
