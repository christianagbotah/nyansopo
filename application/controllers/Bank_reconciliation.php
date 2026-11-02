<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_reconciliation extends CI_Controller {

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
            
        $page_data['page_name'] = 'bank_reconciliation';
        $page_data['page_title'] = get_phrase('bank_reconciliation');
        $this->load->view('backend/main', $page_data);
    }

    public function get_bank_accounts() {
        $accounts = $this->db->where('is_active', 1)
                             ->order_by('account_name', 'ASC')
                             ->get('bank_accounts')
                             ->result_array();
        echo json_encode(['status' => 'success', 'data' => $accounts]);
    }

    public function get_reconciliations() {
        $bank_account_id = $this->input->get('bank_account_id');
        
        $this->db->select('br.*, ba.account_name, ba.bank_name, u1.name as reconciled_by_name, u2.name as reviewed_by_name');
        $this->db->from('bank_reconciliations br');
        $this->db->join('bank_accounts ba', 'br.bank_account_id = ba.bank_account_id');
        $this->db->join('admin u1', 'br.reconciled_by = u1.admin_id', 'left');
        $this->db->join('admin u2', 'br.reviewed_by = u2.admin_id', 'left');
        
        if ($bank_account_id) {
            $this->db->where('br.bank_account_id', $bank_account_id);
        }
        
        $this->db->order_by('br.created_at', 'DESC');
        $reconciliations = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $reconciliations]);
    }

    public function create() {
        $data = [
            'bank_account_id' => $this->input->post('bank_account_id'),
            'reconciliation_date' => $this->input->post('reconciliation_date'),
            'statement_date' => $this->input->post('statement_date'),
            'statement_balance' => $this->input->post('statement_balance'),
            'book_balance' => $this->input->post('book_balance'),
            'difference' => $this->input->post('statement_balance') - $this->input->post('book_balance'),
            'notes' => $this->input->post('notes'),
            'status' => 'pending',
            'reconciled_by' => $this->session->userdata('admin_id')
        ];

        if ($this->db->insert('bank_reconciliations', $data)) {
            $reconciliation_id = $this->db->insert_id();
            $this->log_audit('bank_reconciliation', 'create', $reconciliation_id, null, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('reconciliation_created_successfully'), 'reconciliation_id' => $reconciliation_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function update($reconciliation_id) {
        $old = $this->db->where('id', $reconciliation_id)->get('bank_reconciliations')->row_array();
        
        $data = [
            'reconciliation_date' => $this->input->post('reconciliation_date'),
            'statement_date' => $this->input->post('statement_date'),
            'statement_balance' => $this->input->post('statement_balance'),
            'book_balance' => $this->input->post('book_balance'),
            'difference' => $this->input->post('statement_balance') - $this->input->post('book_balance'),
            'notes' => $this->input->post('notes')
        ];

        if ($this->db->where('id', $reconciliation_id)->update('bank_reconciliations', $data)) {
            $this->log_audit('bank_reconciliation', 'update', $reconciliation_id, $old, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('reconciliation_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function complete($reconciliation_id) {
        $reconciliation = $this->db->where('id', $reconciliation_id)->get('bank_reconciliations')->row_array();
        
        // Count reconciled items
        $reconciled_count = $this->db->where('reconciliation_id', $reconciliation_id)
                                     ->where('matched', 1)
                                     ->count_all_results('reconciliation_items');
        
        // Sum reconciled amount
        $reconciled_amount = $this->db->select_sum('amount')
                                      ->where('reconciliation_id', $reconciliation_id)
                                      ->where('matched', 1)
                                      ->get('reconciliation_items')
                                      ->row()->amount;
        
        $data = [
            'status' => 'completed',
            'reconciled_items_count' => $reconciled_count,
            'reconciled_amount' => $reconciled_amount ?: 0
        ];

        if ($this->db->where('id', $reconciliation_id)->update('bank_reconciliations', $data)) {
            $this->log_audit('bank_reconciliation', 'complete', $reconciliation_id, $reconciliation, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('reconciliation_completed_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function delete($reconciliation_id) {
        $old = $this->db->where('id', $reconciliation_id)->get('bank_reconciliations')->row_array();
        
        if ($this->db->where('id', $reconciliation_id)->delete('bank_reconciliations')) {
            $this->log_audit('bank_reconciliation', 'delete', $reconciliation_id, $old, null);
            echo json_encode(['status' => 'success', 'message' => get_phrase('reconciliation_deleted_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function get_reconciliation_items($reconciliation_id) {
        $items = $this->db->where('reconciliation_id', $reconciliation_id)
                          ->order_by('transaction_date', 'DESC')
                          ->get('reconciliation_items')
                          ->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $items]);
    }

    public function add_item() {
        $data = [
            'reconciliation_id' => $this->input->post('reconciliation_id'),
            'transaction_id' => $this->input->post('transaction_id'),
            'transaction_type' => $this->input->post('transaction_type'),
            'transaction_date' => $this->input->post('transaction_date'),
            'description' => $this->input->post('description'),
            'debit' => $this->input->post('debit') ?: 0,
            'credit' => $this->input->post('credit') ?: 0,
            'amount' => $this->input->post('amount'),
            'matched' => $this->input->post('matched') ? 1 : 0
        ];

        if ($this->db->insert('reconciliation_items', $data)) {
            echo json_encode(['status' => 'success', 'message' => get_phrase('item_added_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function toggle_match($item_id) {
        $item = $this->db->where('id', $item_id)->get('reconciliation_items')->row();
        $new_status = $item->matched ? 0 : 1;
        
        if ($this->db->where('id', $item_id)->update('reconciliation_items', ['matched' => $new_status])) {
            echo json_encode(['status' => 'success', 'message' => get_phrase('match_status_updated')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function delete_item($item_id) {
        if ($this->db->where('id', $item_id)->delete('reconciliation_items')) {
            echo json_encode(['status' => 'success', 'message' => get_phrase('item_deleted_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function get_statistics() {
        $this->db->select('
            COUNT(*) as total_reconciliations,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = "reviewed" THEN 1 ELSE 0 END) as reviewed_count,
            SUM(ABS(difference)) as total_differences
        ');
        $stats = $this->db->get('bank_reconciliations')->row_array();
        
        // Get bank account balances
        $this->db->select('SUM(current_balance) as total_balance');
        $this->db->where('is_active', 1);
        $balance_stats = $this->db->get('bank_accounts')->row_array();
        
        $stats['total_balance'] = $balance_stats['total_balance'] ?: 0;
        
        echo json_encode(['status' => 'success', 'data' => $stats]);
    }

    public function get_unmatched_transactions($reconciliation_id) {
        $items = $this->db->where('reconciliation_id', $reconciliation_id)
                          ->where('matched', 0)
                          ->get('reconciliation_items')
                          ->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $items]);
    }

    public function auto_match($reconciliation_id) {
        // Get all unmatched items
        $items = $this->db->where('reconciliation_id', $reconciliation_id)
                          ->where('matched', 0)
                          ->get('reconciliation_items')
                          ->result_array();
        
        $matched_count = 0;
        
        // Simple matching logic: match by amount and date
        foreach ($items as $item) {
            $match = $this->db->where('reconciliation_id', $reconciliation_id)
                              ->where('matched', 0)
                              ->where('id !=', $item['id'])
                              ->where('amount', $item['amount'])
                              ->where('transaction_date', $item['transaction_date'])
                              ->where('transaction_type !=', $item['transaction_type'])
                              ->get('reconciliation_items')
                              ->row();
            
            if ($match) {
                $this->db->where('id', $item['id'])->update('reconciliation_items', ['matched' => 1, 'matched_with_id' => $match->id]);
                $this->db->where('id', $match->id)->update('reconciliation_items', ['matched' => 1, 'matched_with_id' => $item['id']]);
                $matched_count++;
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => get_phrase('auto_matched') . ': ' . $matched_count, 'matched_count' => $matched_count]);
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
