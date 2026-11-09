<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_transport_billing extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function auto_billing() {
        $page_data['page_name'] = 'auto_billing';
        $page_data['page_title'] = get_phrase('auto_billing_management');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/index', $page_data);
    }
    
    public function get_billing_records() {
        $records = $this->db->select('tab.*, s.name as student_name, t.route_name')
            ->from('transport_auto_billing tab')
            ->join('students s', 's.id = tab.student_id')
            ->join('transport t', 't.transport_id = tab.transport_id')
            ->order_by('tab.billing_date', 'DESC')
            ->limit(100)
            ->get()->result_array();
        
        echo json_encode(['data' => $records]);
    }
    
    public function get_billing_stats() {
        $today = date('Y-m-d');
        
        $today_count = $this->db->where('billing_date', $today)->count_all_results('transport_auto_billing');
        $confirmed = $this->db->where('billing_status', 'confirmed')->count_all_results('transport_auto_billing');
        $reversed = $this->db->where('billing_status', 'reversed')->count_all_results('transport_auto_billing');
        
        echo json_encode([
            'today' => $today_count,
            'confirmed' => $confirmed,
            'reversed' => $reversed
        ]);
    }
}
