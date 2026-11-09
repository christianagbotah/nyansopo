<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Advanced_reports extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session', 'advanced_reporting']);
        
        if ($this->session->userdata('admin_login') != 1)
            redirect(base_url(), 'refresh');
    }

    public function index() {
        $page_data['page_name'] = 'advanced_reports';
        $page_data['page_title'] = get_phrase('advanced_reports');
        $this->load->view('backend/index', $page_data);
    }

    public function financial_summary() {
        $start_date = $this->input->get('start_date') ?: date('Y-m-01');
        $end_date = $this->input->get('end_date') ?: date('Y-m-t');
        
        $data = $this->advanced_reporting->financial_summary($start_date, $end_date);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function cash_flow() {
        $start_date = $this->input->get('start_date') ?: date('Y-m-01', strtotime('-6 months'));
        $end_date = $this->input->get('end_date') ?: date('Y-m-t');
        $interval = $this->input->get('interval') ?: 'month';
        
        $data = $this->advanced_reporting->cash_flow_report($start_date, $end_date, $interval);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function aging_report() {
        $data = $this->advanced_reporting->aging_report();
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function category_expenses() {
        $start_date = $this->input->get('start_date') ?: date('Y-01-01');
        $end_date = $this->input->get('end_date') ?: date('Y-12-31');
        
        $data = $this->advanced_reporting->category_expense_report($start_date, $end_date);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function payment_methods() {
        $start_date = $this->input->get('start_date') ?: date('Y-m-01');
        $end_date = $this->input->get('end_date') ?: date('Y-m-t');
        
        $data = $this->advanced_reporting->payment_method_analysis($start_date, $end_date);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function student_profile($student_id) {
        $data = $this->advanced_reporting->student_financial_profile($student_id);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function export($report_type) {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        
        switch ($report_type) {
            case 'aging':
                $data = $this->advanced_reporting->aging_report();
                break;
            case 'cash_flow':
                $data = $this->advanced_reporting->cash_flow_report($start_date, $end_date);
                break;
            case 'category_expenses':
                $data = $this->advanced_reporting->category_expense_report($start_date, $end_date);
                break;
            default:
                return;
        }
        
        $this->advanced_reporting->export_report($report_type, $data, ucfirst($report_type) . '_Report_' . date('Y-m-d'));
    }
}
