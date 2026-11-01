<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budget_management extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function index() {
        if ($this->session->userdata('admin_login') != 1)
            redirect(base_url(), 'refresh');
            
        $page_data['page_name'] = 'budget_management';
        $page_data['page_title'] = get_phrase('budget_management');
        $this->load->view('backend/index', $page_data);
    }

    public function get_budgets() {
        $this->db->select('b.*, CONCAT(u.name) as created_by_name');
        $this->db->from('budgets b');
        $this->db->join('admin u', 'b.created_by = u.admin_id', 'left');
        $this->db->order_by('b.created_at', 'DESC');
        $budgets = $this->db->get()->result_array();
        
        foreach ($budgets as &$budget) {
            $budget['utilization_pct'] = $budget['total_amount'] > 0 ? 
                round(($this->get_actual_spent($budget['budget_id']) / $budget['total_amount']) * 100, 2) : 0;
        }
        
        echo json_encode(['status' => 'success', 'data' => $budgets]);
    }

    public function create() {
        $data = [
            'budget_name' => $this->input->post('budget_name'),
            'fiscal_year' => $this->input->post('fiscal_year'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'total_amount' => 0,
            'status' => 'draft',
            'notes' => $this->input->post('notes'),
            'created_by' => $this->session->userdata('admin_id')
        ];

        if ($this->db->insert('budgets', $data)) {
            $this->log_audit('budget', 'create', $this->db->insert_id(), null, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_created_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function update($budget_id) {
        $old = $this->db->where('budget_id', $budget_id)->get('budgets')->row_array();
        $data = [
            'budget_name' => $this->input->post('budget_name'),
            'fiscal_year' => $this->input->post('fiscal_year'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'notes' => $this->input->post('notes')
        ];

        if ($this->db->where('budget_id', $budget_id)->update('budgets', $data)) {
            $this->log_audit('budget', 'update', $budget_id, $old, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function delete($budget_id) {
        $old = $this->db->where('budget_id', $budget_id)->get('budgets')->row_array();
        if ($this->db->where('budget_id', $budget_id)->delete('budgets')) {
            $this->log_audit('budget', 'delete', $budget_id, $old, null);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_deleted_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function get_budget_lines($budget_id) {
        $this->db->select('bl.*, coa.account_name, coa.account_code, coa.account_type');
        $this->db->from('budget_lines bl');
        $this->db->join('chart_of_accounts coa', 'bl.account_id = coa.account_id');
        $this->db->where('bl.budget_id', $budget_id);
        $lines = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $lines]);
    }

    public function add_line() {
        $data = [
            'budget_id' => $this->input->post('budget_id'),
            'account_id' => $this->input->post('account_id'),
            'budgeted_amount' => $this->input->post('budgeted_amount'),
            'notes' => $this->input->post('notes')
        ];

        if ($this->db->insert('budget_lines', $data)) {
            $this->update_budget_total($data['budget_id']);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_line_added_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function update_line($line_id) {
        $data = [
            'account_id' => $this->input->post('account_id'),
            'budgeted_amount' => $this->input->post('budgeted_amount'),
            'notes' => $this->input->post('notes')
        ];

        $line = $this->db->where('budget_line_id', $line_id)->get('budget_lines')->row();
        
        if ($this->db->where('budget_line_id', $line_id)->update('budget_lines', $data)) {
            $this->update_budget_total($line->budget_id);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_line_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function delete_line($line_id) {
        $line = $this->db->where('budget_line_id', $line_id)->get('budget_lines')->row();
        
        if ($this->db->where('budget_line_id', $line_id)->delete('budget_lines')) {
            $this->update_budget_total($line->budget_id);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_line_deleted_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    private function update_budget_total($budget_id) {
        $total = $this->db->select_sum('budgeted_amount')
                          ->where('budget_id', $budget_id)
                          ->get('budget_lines')
                          ->row()->budgeted_amount;
        
        $this->db->where('budget_id', $budget_id)->update('budgets', ['total_amount' => $total ?: 0]);
    }

    private function get_actual_spent($budget_id) {
        return $this->db->select_sum('actual_amount')
                        ->where('budget_id', $budget_id)
                        ->get('budget_lines')
                        ->row()->actual_amount ?: 0;
    }

    public function approve($budget_id) {
        $data = [
            'status' => 'approved',
            'approved_by' => $this->session->userdata('admin_id'),
            'approved_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->where('budget_id', $budget_id)->update('budgets', $data)) {
            $this->log_audit('budget', 'approve', $budget_id, null, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('budget_approved_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function get_accounts() {
        $accounts = $this->db->where('is_active', 1)
                             ->order_by('account_code', 'ASC')
                             ->get('chart_of_accounts')
                             ->result_array();
        echo json_encode(['status' => 'success', 'data' => $accounts]);
    }

    public function get_budget_summary($budget_id) {
        $budget = $this->db->where('budget_id', $budget_id)->get('budgets')->row_array();
        $lines = $this->db->select('bl.*, coa.account_name, coa.account_code')
                          ->from('budget_lines bl')
                          ->join('chart_of_accounts coa', 'bl.account_id = coa.account_id')
                          ->where('bl.budget_id', $budget_id)
                          ->get()->result_array();
        
        $budget['lines'] = $lines;
        $budget['total_budgeted'] = array_sum(array_column($lines, 'budgeted_amount'));
        $budget['total_actual'] = array_sum(array_column($lines, 'actual_amount'));
        $budget['total_variance'] = $budget['total_budgeted'] - $budget['total_actual'];
        
        echo json_encode(['status' => 'success', 'data' => $budget]);
    }

    public function export_budgets() {
        
        $budgets = $this->db->select('budget_name, fiscal_year, start_date, end_date, total_amount, status')
                            ->order_by('created_at', 'DESC')
                            ->get('budgets')->result_array();
        
        $data = [];
        foreach ($budgets as $budget) {
            $data[] = [
                $budget['budget_name'],
                $budget['fiscal_year'],
                $budget['start_date'],
                $budget['end_date'],
                'GHS ' . number_format($budget['total_amount'], 2),
                ucfirst($budget['status'])
            ];
        }
        
        $headers = ['Budget Name', 'Fiscal Year', 'Start Date', 'End Date', 'Total Amount', 'Status'];
        export_to_excel($data, 'Budgets_' . date('Y-m-d'), $headers);
    }

    private function log_audit($module, $action, $record_id, $old_values, $new_values) {
        $this->db->insert('financial_audit_trail', [
            'module' => $module,
            'action' => $action,
            'record_id' => $record_id,
            'old_values' => json_encode($old_values),
            'new_values' => json_encode($new_values),
            'user_id' => $this->session->userdata('admin_id'),
            'user_type' => 'admin',
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ]);
    }
}
