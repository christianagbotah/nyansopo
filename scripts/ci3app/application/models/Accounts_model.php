<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounts model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Accounts_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    // ==================== DASHBOARD METRICS ====================
    public function get_dashboard_metrics() {
        $metrics = [];
        
        // Total Assets
        $metrics['total_assets'] = $this->db->select_sum('current_balance')
            ->where('account_type', 'asset')
            ->where('is_active', 1)
            ->get('chart_of_accounts')->row()->current_balance ?? 0;
        
        // Total Liabilities
        $metrics['total_liabilities'] = $this->db->select_sum('current_balance')
            ->where('account_type', 'liability')
            ->where('is_active', 1)
            ->get('chart_of_accounts')->row()->current_balance ?? 0;
        
        // Total Revenue (Current Month)
        $metrics['monthly_revenue'] = $this->db->select_sum('credit_amount')
            ->join('chart_of_accounts', 'chart_of_accounts.account_id = journal_entry_lines.account_id')
            ->where('chart_of_accounts.account_type', 'revenue')
            ->where('MONTH(journal_entries.entry_date)', date('m'))
            ->where('YEAR(journal_entries.entry_date)', date('Y'))
            ->where('journal_entries.status', 'posted')
            ->join('journal_entries', 'journal_entries.entry_id = journal_entry_lines.entry_id')
            ->get('journal_entry_lines')->row()->credit_amount ?? 0;
        
        // Total Expenses (Current Month)
        $metrics['monthly_expenses'] = $this->db->select_sum('debit_amount')
            ->join('chart_of_accounts', 'chart_of_accounts.account_id = journal_entry_lines.account_id')
            ->where('chart_of_accounts.account_type', 'expense')
            ->where('MONTH(journal_entries.entry_date)', date('m'))
            ->where('YEAR(journal_entries.entry_date)', date('Y'))
            ->where('journal_entries.status', 'posted')
            ->join('journal_entries', 'journal_entries.entry_id = journal_entry_lines.entry_id')
            ->get('journal_entry_lines')->row()->debit_amount ?? 0;
        
        // Net Income
        $metrics['net_income'] = $metrics['monthly_revenue'] - $metrics['monthly_expenses'];
        
        // Bank Balance
        $metrics['bank_balance'] = $this->db->select_sum('current_balance')
            ->where('is_active', 1)
            ->get('bank_accounts')->row()->current_balance ?? 0;
        
        // Pending Journal Entries
        $metrics['pending_entries'] = $this->db->where('status', 'draft')
            ->count_all_results('journal_entries');
        
        // Budget Utilization
        $current_year = date('Y');
        $budget = $this->db->where('fiscal_year', $current_year)
            ->where('status', 'active')
            ->get('budgets')->row();
        
        if ($budget) {
            $metrics['budget_total'] = $budget->total_amount;
            $metrics['budget_used'] = $this->db->select_sum('actual_amount')
                ->where('budget_id', $budget->budget_id)
                ->get('budget_lines')->row()->actual_amount ?? 0;
            $metrics['budget_percentage'] = ($metrics['budget_total'] > 0) 
                ? ($metrics['budget_used'] / $metrics['budget_total']) * 100 
                : 0;
        } else {
            $metrics['budget_total'] = 0;
            $metrics['budget_used'] = 0;
            $metrics['budget_percentage'] = 0;
        }
        
        return $metrics;
    }

    // ==================== CHART OF ACCOUNTS ====================
    public function get_all_accounts($filters = []) {
        $this->db->select('chart_of_accounts.*, parent.account_name as parent_name')
            ->from('chart_of_accounts')
            ->join('chart_of_accounts as parent', 'parent.account_id = chart_of_accounts.parent_account_id', 'left')
            ->order_by('chart_of_accounts.account_code', 'ASC');
        
        if (!empty($filters['account_type'])) {
            $this->db->where('chart_of_accounts.account_type', $filters['account_type']);
        }
        
        if (isset($filters['is_active'])) {
            $this->db->where('chart_of_accounts.is_active', $filters['is_active']);
        }
        
        return $this->db->get()->result_array();
    }

    public function create_account($data) {
        $this->db->trans_start();
        
        $account_data = [
            'account_code' => $data['account_code'],
            'account_name' => $data['account_name'],
            'account_type' => $data['account_type'],
            'account_category' => $data['account_category'] ?? null,
            'parent_account_id' => $data['parent_account_id'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => 1
        ];
        
        $this->db->insert('chart_of_accounts', $account_data);
        $account_id = $this->db->insert_id();
        
        // Log audit trail
        $this->log_audit('chart_of_accounts', $account_id, 'create', null, $account_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('account_created_successfully')];
    }

    public function update_account($id, $data) {
        $this->db->trans_start();
        
        $old_data = $this->db->where('account_id', $id)->get('chart_of_accounts')->row_array();
        
        $account_data = [
            'account_name' => $data['account_name'],
            'account_category' => $data['account_category'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        $this->db->where('account_id', $id)->update('chart_of_accounts', $account_data);
        
        // Log audit trail
        $this->log_audit('chart_of_accounts', $id, 'update', $old_data, $account_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('account_updated_successfully')];
    }

    public function delete_account($id) {
        // Check if account has transactions
        $has_transactions = $this->db->where('account_id', $id)
            ->count_all_results('journal_entry_lines') > 0;
        
        if ($has_transactions) {
            return ['status' => 'error', 'message' => get_phrase('cannot_delete_account_with_transactions')];
        }
        
        $this->db->trans_start();
        
        $old_data = $this->db->where('account_id', $id)->get('chart_of_accounts')->row_array();
        $this->db->where('account_id', $id)->delete('chart_of_accounts');
        
        // Log audit trail
        $this->log_audit('chart_of_accounts', $id, 'delete', $old_data, null);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('account_deleted_successfully')];
    }

    // ==================== JOURNAL ENTRIES ====================
    public function create_journal_entry($data) {
        $this->db->trans_start();
        
        // Generate entry number
        $entry_number = $this->generate_entry_number();
        
        $entry_data = [
            'entry_number' => $entry_number,
            'entry_date' => $data['entry_date'],
            'entry_type' => $data['entry_type'] ?? 'general',
            'description' => $data['description'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'status' => 'draft',
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        $this->db->insert('journal_entries', $entry_data);
        $entry_id = $this->db->insert_id();
        
        // Insert journal entry lines
        $total_debit = 0;
        $total_credit = 0;
        
        foreach ($data['lines'] as $line) {
            $line_data = [
                'entry_id' => $entry_id,
                'account_id' => $line['account_id'],
                'debit_amount' => $line['debit_amount'] ?? 0,
                'credit_amount' => $line['credit_amount'] ?? 0,
                'description' => $line['description'] ?? null
            ];
            
            $this->db->insert('journal_entry_lines', $line_data);
            
            $total_debit += $line['debit_amount'] ?? 0;
            $total_credit += $line['credit_amount'] ?? 0;
        }
        
        // Update totals
        $this->db->where('entry_id', $entry_id)->update('journal_entries', [
            'total_debit' => $total_debit,
            'total_credit' => $total_credit
        ]);
        
        // Log audit trail
        $this->log_audit('journal_entries', $entry_id, 'create', null, $entry_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('journal_entry_created'), 'entry_id' => $entry_id];
    }

    public function post_journal_entry($entry_id) {
        $this->db->trans_start();
        
        // Get entry
        $entry = $this->db->where('entry_id', $entry_id)->get('journal_entries')->row_array();
        
        if (!$entry || $entry['status'] != 'draft') {
            return ['status' => 'error', 'message' => get_phrase('invalid_entry')];
        }
        
        // Validate balanced entry
        if ($entry['total_debit'] != $entry['total_credit']) {
            return ['status' => 'error', 'message' => get_phrase('entry_not_balanced')];
        }
        
        // Update entry status
        $this->db->where('entry_id', $entry_id)->update('journal_entries', [
            'status' => 'posted',
            'posted_by' => $this->session->userdata('admin_id'),
            'posted_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update account balances
        $lines = $this->db->where('entry_id', $entry_id)->get('journal_entry_lines')->result_array();
        
        foreach ($lines as $line) {
            $account = $this->db->where('account_id', $line['account_id'])->get('chart_of_accounts')->row_array();
            
            $balance_change = 0;
            
            // Calculate balance change based on account type
            if (in_array($account['account_type'], ['asset', 'expense'])) {
                $balance_change = $line['debit_amount'] - $line['credit_amount'];
            } else {
                $balance_change = $line['credit_amount'] - $line['debit_amount'];
            }
            
            $new_balance = $account['current_balance'] + $balance_change;
            
            $this->db->where('account_id', $line['account_id'])->update('chart_of_accounts', [
                'current_balance' => $new_balance
            ]);
        }
        
        // Log audit trail
        $this->log_audit('journal_entries', $entry_id, 'post', $entry, ['status' => 'posted']);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('entry_posted_successfully')];
    }

    public function void_journal_entry($entry_id) {
        $this->db->trans_start();
        
        $entry = $this->db->where('entry_id', $entry_id)->get('journal_entries')->row_array();
        
        if (!$entry || $entry['status'] != 'posted') {
            return ['status' => 'error', 'message' => get_phrase('invalid_entry')];
        }
        
        // Reverse account balances
        $lines = $this->db->where('entry_id', $entry_id)->get('journal_entry_lines')->result_array();
        
        foreach ($lines as $line) {
            $account = $this->db->where('account_id', $line['account_id'])->get('chart_of_accounts')->row_array();
            
            $balance_change = 0;
            
            if (in_array($account['account_type'], ['asset', 'expense'])) {
                $balance_change = $line['credit_amount'] - $line['debit_amount'];
            } else {
                $balance_change = $line['debit_amount'] - $line['credit_amount'];
            }
            
            $new_balance = $account['current_balance'] + $balance_change;
            
            $this->db->where('account_id', $line['account_id'])->update('chart_of_accounts', [
                'current_balance' => $new_balance
            ]);
        }
        
        // Update entry status
        $this->db->where('entry_id', $entry_id)->update('journal_entries', [
            'status' => 'void'
        ]);
        
        // Log audit trail
        $this->log_audit('journal_entries', $entry_id, 'void', $entry, ['status' => 'void']);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('entry_voided_successfully')];
    }

    public function get_journal_entries($filters = []) {
        $this->db->select('journal_entries.*, admin.name as created_by_name')
            ->from('journal_entries')
            ->join('admin', 'admin.admin_id = journal_entries.created_by', 'left')
            ->order_by('journal_entries.entry_date', 'DESC');
        
        if (!empty($filters['status'])) {
            $this->db->where('journal_entries.status', $filters['status']);
        }
        
        if (!empty($filters['start_date'])) {
            $this->db->where('journal_entries.entry_date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('journal_entries.entry_date <=', $filters['end_date']);
        }
        
        return $this->db->get()->result_array();
    }

    // ==================== HELPER FUNCTIONS ====================
    private function generate_entry_number() {
        $year = date('Y');
        $month = date('m');
        $prefix = 'JE-' . $year . $month;
        
        $last_entry = $this->db->select('entry_number')
            ->like('entry_number', $prefix, 'after')
            ->order_by('entry_id', 'DESC')
            ->limit(1)
            ->get('journal_entries')->row();
        
        if ($last_entry) {
            $last_number = intval(substr($last_entry->entry_number, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return $prefix . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }

    private function log_audit($table_name, $record_id, $action, $old_values, $new_values) {
        $audit_data = [
            'table_name' => $table_name,
            'record_id' => $record_id,
            'action' => $action,
            'old_values' => json_encode($old_values),
            'new_values' => json_encode($new_values),
            'user_id' => $this->session->userdata('admin_id'),
            'user_type' => $this->session->userdata('login_type'),
            'ip_address' => $this->input->ip_address()
        ];
        
        $this->db->insert('finance_audit_trail', $audit_data);
    }

    // ==================== BANK ACCOUNTS ====================
    public function get_bank_accounts() {
        return $this->db->select('bank_accounts.*, chart_of_accounts.account_name as chart_account_name')
            ->from('bank_accounts')
            ->join('chart_of_accounts', 'chart_of_accounts.account_id = bank_accounts.chart_account_id', 'left')
            ->where('bank_accounts.is_active', 1)
            ->order_by('bank_accounts.account_name', 'ASC')
            ->get()->result_array();
    }

    public function create_bank_account($data) {
        $this->db->trans_start();
        
        $bank_data = [
            'account_name' => $data['account_name'],
            'bank_name' => $data['bank_name'],
            'account_number' => $data['account_number'],
            'account_type' => $data['account_type'],
            'currency' => $data['currency'] ?? 'GHS',
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'chart_account_id' => $data['chart_account_id'] ?? null,
            'branch' => $data['branch'] ?? null,
            'swift_code' => $data['swift_code'] ?? null,
            'notes' => $data['notes'] ?? null
        ];
        
        $this->db->insert('bank_accounts', $bank_data);
        $bank_account_id = $this->db->insert_id();
        
        $this->log_audit('bank_accounts', $bank_account_id, 'create', null, $bank_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('bank_account_created')];
    }

    public function update_bank_account($id, $data) {
        $this->db->trans_start();
        
        $old_data = $this->db->where('bank_account_id', $id)->get('bank_accounts')->row_array();
        
        $bank_data = [
            'account_name' => $data['account_name'],
            'bank_name' => $data['bank_name'],
            'branch' => $data['branch'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        $this->db->where('bank_account_id', $id)->update('bank_accounts', $bank_data);
        
        $this->log_audit('bank_accounts', $id, 'update', $old_data, $bank_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('bank_account_updated')];
    }

    // ==================== BUDGETS ====================
    public function get_budgets() {
        return $this->db->select('budgets.*, admin.name as created_by_name')
            ->from('budgets')
            ->join('admin', 'admin.admin_id = budgets.created_by', 'left')
            ->order_by('budgets.fiscal_year', 'DESC')
            ->get()->result_array();
    }

    public function create_budget($data) {
        $this->db->trans_start();
        
        $budget_data = [
            'budget_name' => $data['budget_name'],
            'fiscal_year' => $data['fiscal_year'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_amount' => $data['total_amount'] ?? 0,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        $this->db->insert('budgets', $budget_data);
        $budget_id = $this->db->insert_id();
        
        // Insert budget lines if provided
        if (!empty($data['lines'])) {
            foreach ($data['lines'] as $line) {
                $line_data = [
                    'budget_id' => $budget_id,
                    'account_id' => $line['account_id'],
                    'budgeted_amount' => $line['budgeted_amount'],
                    'notes' => $line['notes'] ?? null
                ];
                
                $this->db->insert('budget_lines', $line_data);
            }
        }
        
        $this->log_audit('budgets', $budget_id, 'create', null, $budget_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('budget_created'), 'budget_id' => $budget_id];
    }

    public function approve_budget($id) {
        $this->db->trans_start();
        
        $budget = $this->db->where('budget_id', $id)->get('budgets')->row_array();
        
        if (!$budget || $budget['status'] != 'draft') {
            return ['status' => 'error', 'message' => get_phrase('invalid_budget')];
        }
        
        $this->db->where('budget_id', $id)->update('budgets', [
            'status' => 'approved',
            'approved_by' => $this->session->userdata('admin_id'),
            'approved_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->log_audit('budgets', $id, 'approve', $budget, ['status' => 'approved']);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('budget_approved')];
    }

    // ==================== FISCAL YEAR ====================
    public function get_fiscal_years() {
        return $this->db->order_by('start_date', 'DESC')->get('fiscal_years')->result_array();
    }

    public function create_fiscal_year($data) {
        $this->db->trans_start();
        
        // Set all other years as not current
        $this->db->update('fiscal_years', ['is_current' => 0]);
        
        $fiscal_data = [
            'year_name' => $data['year_name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'open',
            'is_current' => 1,
            'notes' => $data['notes'] ?? null
        ];
        
        $this->db->insert('fiscal_years', $fiscal_data);
        $fiscal_year_id = $this->db->insert_id();
        
        // Create 12 monthly periods
        $start = new DateTime($data['start_date']);
        $end = new DateTime($data['end_date']);
        
        for ($i = 1; $i <= 12; $i++) {
            $period_start = clone $start;
            $period_start->modify('+' . ($i - 1) . ' months');
            $period_end = clone $period_start;
            $period_end->modify('+1 month -1 day');
            
            if ($period_end > $end) {
                $period_end = $end;
            }
            
            $period_data = [
                'fiscal_year_id' => $fiscal_year_id,
                'period_name' => $period_start->format('F Y'),
                'period_number' => $i,
                'start_date' => $period_start->format('Y-m-d'),
                'end_date' => $period_end->format('Y-m-d'),
                'status' => 'open'
            ];
            
            $this->db->insert('fiscal_periods', $period_data);
        }
        
        $this->log_audit('fiscal_years', $fiscal_year_id, 'create', null, $fiscal_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('fiscal_year_created')];
    }

    public function close_fiscal_year($id) {
        $this->db->trans_start();
        
        $fiscal_year = $this->db->where('fiscal_year_id', $id)->get('fiscal_years')->row_array();
        
        if (!$fiscal_year || $fiscal_year['status'] != 'open') {
            return ['status' => 'error', 'message' => get_phrase('invalid_fiscal_year')];
        }
        
        // Close all periods
        $this->db->where('fiscal_year_id', $id)->update('fiscal_periods', [
            'status' => 'closed',
            'closed_by' => $this->session->userdata('admin_id'),
            'closed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Close fiscal year
        $this->db->where('fiscal_year_id', $id)->update('fiscal_years', [
            'status' => 'closed',
            'closed_by' => $this->session->userdata('admin_id'),
            'closed_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->log_audit('fiscal_years', $id, 'close', $fiscal_year, ['status' => 'closed']);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('fiscal_year_closed')];
    }

    // ==================== BANK RECONCILIATION ====================
    public function get_unreconciled_transactions($bank_account_id) {
        return $this->db->select('*')
            ->from('bank_transactions')
            ->where('bank_account_id', $bank_account_id)
            ->where('is_reconciled', 0)
            ->order_by('transaction_date', 'DESC')
            ->get()->result_array();
    }

    public function reconcile_transactions($data) {
        $this->db->trans_start();
        
        foreach ($data['transaction_ids'] as $transaction_id) {
            $this->db->where('transaction_id', $transaction_id)->update('bank_transactions', [
                'is_reconciled' => 1,
                'reconciled_by' => $this->session->userdata('admin_id'),
                'reconciled_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => get_phrase('operation_failed')];
        }
        
        return ['status' => 'success', 'message' => get_phrase('transactions_reconciled')];
    }

    // ==================== FINANCIAL REPORTS ====================
    public function get_balance_sheet($filters = []) {
        $as_of_date = $filters['as_of_date'] ?? date('Y-m-d');
        
        $data = [];
        
        // Assets
        $data['assets'] = $this->db->select('account_name, current_balance')
            ->where('account_type', 'asset')
            ->where('is_active', 1)
            ->get('chart_of_accounts')->result_array();
        $data['total_assets'] = array_sum(array_column($data['assets'], 'current_balance'));
        
        // Liabilities
        $data['liabilities'] = $this->db->select('account_name, current_balance')
            ->where('account_type', 'liability')
            ->where('is_active', 1)
            ->get('chart_of_accounts')->result_array();
        $data['total_liabilities'] = array_sum(array_column($data['liabilities'], 'current_balance'));
        
        // Equity
        $data['equity'] = $this->db->select('account_name, current_balance')
            ->where('account_type', 'equity')
            ->where('is_active', 1)
            ->get('chart_of_accounts')->result_array();
        $data['total_equity'] = array_sum(array_column($data['equity'], 'current_balance'));
        
        $data['as_of_date'] = $as_of_date;
        
        return $data;
    }

    public function get_income_statement($filters = []) {
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-t');
        
        $data = [];
        
        // Revenue
        $data['revenue'] = $this->db->select('chart_of_accounts.account_name, SUM(journal_entry_lines.credit_amount - journal_entry_lines.debit_amount) as amount')
            ->from('journal_entry_lines')
            ->join('chart_of_accounts', 'chart_of_accounts.account_id = journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entries.entry_id = journal_entry_lines.entry_id')
            ->where('chart_of_accounts.account_type', 'revenue')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.entry_date >=', $start_date)
            ->where('journal_entries.entry_date <=', $end_date)
            ->group_by('chart_of_accounts.account_id')
            ->get()->result_array();
        $data['total_revenue'] = array_sum(array_column($data['revenue'], 'amount'));
        
        // Expenses
        $data['expenses'] = $this->db->select('chart_of_accounts.account_name, SUM(journal_entry_lines.debit_amount - journal_entry_lines.credit_amount) as amount')
            ->from('journal_entry_lines')
            ->join('chart_of_accounts', 'chart_of_accounts.account_id = journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entries.entry_id = journal_entry_lines.entry_id')
            ->where('chart_of_accounts.account_type', 'expense')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.entry_date >=', $start_date)
            ->where('journal_entries.entry_date <=', $end_date)
            ->group_by('chart_of_accounts.account_id')
            ->get()->result_array();
        $data['total_expenses'] = array_sum(array_column($data['expenses'], 'amount'));
        
        $data['net_income'] = $data['total_revenue'] - $data['total_expenses'];
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        
        return $data;
    }

    public function get_cash_flow($filters = []) {
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-t');
        
        $data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'operating_activities' => [],
            'investing_activities' => [],
            'financing_activities' => []
        ];
        
        return $data;
    }

    public function get_trial_balance($filters = []) {
        $as_of_date = $filters['as_of_date'] ?? date('Y-m-d');
        
        $accounts = $this->db->select('account_code, account_name, account_type, current_balance')
            ->where('is_active', 1)
            ->order_by('account_code', 'ASC')
            ->get('chart_of_accounts')->result_array();
        
        $data = [
            'accounts' => [],
            'total_debit' => 0,
            'total_credit' => 0,
            'as_of_date' => $as_of_date
        ];
        
        foreach ($accounts as $account) {
            $balance = $account['current_balance'];
            
            if (in_array($account['account_type'], ['asset', 'expense'])) {
                $debit = $balance > 0 ? $balance : 0;
                $credit = $balance < 0 ? abs($balance) : 0;
            } else {
                $credit = $balance > 0 ? $balance : 0;
                $debit = $balance < 0 ? abs($balance) : 0;
            }
            
            $data['accounts'][] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'debit' => $debit,
                'credit' => $credit
            ];
            
            $data['total_debit'] += $debit;
            $data['total_credit'] += $credit;
        }
        
        return $data;
    }

    public function get_general_ledger($filters = []) {
        $account_id = $filters['account_id'] ?? null;
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-t');
        
        if (!$account_id) {
            return [];
        }
        
        $transactions = $this->db->select('journal_entries.entry_date, journal_entries.entry_number, journal_entries.description, journal_entry_lines.debit_amount, journal_entry_lines.credit_amount')
            ->from('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.entry_id = journal_entry_lines.entry_id')
            ->where('journal_entry_lines.account_id', $account_id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.entry_date >=', $start_date)
            ->where('journal_entries.entry_date <=', $end_date)
            ->order_by('journal_entries.entry_date', 'ASC')
            ->get()->result_array();
        
        $account = $this->db->where('account_id', $account_id)->get('chart_of_accounts')->row_array();
        
        return [
            'account' => $account,
            'transactions' => $transactions,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    // ==================== AUDIT TRAIL ====================
    public function get_audit_trail($filters = []) {
        $this->db->select('finance_audit_trail.*, admin.name as user_name')
            ->from('finance_audit_trail')
            ->join('admin', 'admin.admin_id = finance_audit_trail.user_id', 'left')
            ->order_by('finance_audit_trail.created_at', 'DESC')
            ->limit(1000);
        
        if (!empty($filters['table_name'])) {
            $this->db->where('finance_audit_trail.table_name', $filters['table_name']);
        }
        
        if (!empty($filters['start_date'])) {
            $this->db->where('DATE(finance_audit_trail.created_at) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('DATE(finance_audit_trail.created_at) <=', $filters['end_date']);
        }
        
        return $this->db->get()->result_array();
    }
}
