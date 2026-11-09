<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounts & Finance Controller
 * Professional Financial Management System
 * 
 * @package SchoolManager
 * @subpackage Controllers
 * @category Finance
 * @author Senior Software Engineer
 * @version 2.0
 */
class Accounts extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load libraries first
        $this->load->library(['session', 'form_validation']);
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Load required models
    }

    // ==================== DASHBOARD ====================
    public function index() {
        $this->dashboard();
    }

    public function dashboard() {
        $page_data['page_name'] = 'accounts/dashboard';
        $page_data['page_title'] = get_phrase('accounts_dashboard');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        // Get dashboard metrics
        $page_data['metrics'] = $this->Accounts_model->get_dashboard_metrics();
        
        $this->load->view('backend/main', $page_data);
    }
    
    public function get_dashboard_data() {
        header('Content-Type: application/json');
        $data = $this->Accounts_model->get_dashboard_metrics();
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    // ==================== CHART OF ACCOUNTS ====================
    public function chart_of_accounts($action = '', $id = '') {
        if ($action == 'create') {
            $this->form_validation->set_rules('account_code', 'Account Code', 'required|is_unique[chart_of_accounts.account_code]');
            $this->form_validation->set_rules('account_name', 'Account Name', 'required');
            $this->form_validation->set_rules('account_type', 'Account Type', 'required');
            
            if ($this->form_validation->run()) {
                $result = $this->Accounts_model->create_account($this->input->post());
                echo json_encode($result);
            } else {
                echo json_encode(['status' => 'error', 'message' => validation_errors()]);
            }
            return;
        }
        
        if ($action == 'update' && $id) {
            $result = $this->Accounts_model->update_account($id, $this->input->post());
            echo json_encode($result);
            return;
        }
        
        if ($action == 'delete' && $id) {
            $result = $this->Accounts_model->delete_account($id);
            echo json_encode($result);
            return;
        }
        
        if ($action == 'get_data') {
            $accounts = $this->Accounts_model->get_all_accounts();
            $data = [];
            foreach($accounts as $account) {
                $data[] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'account_type' => $account['account_type'],
                    'balance' => $account['current_balance'],
                    'status' => $account['is_active'],
                    'actions' => '<button class="btn btn-sm btn-info action-btn" onclick="editAccount('.$account['account_id'].')"><i class="fa fa-edit"></i></button> <button class="btn btn-sm btn-danger action-btn" onclick="deleteAccount('.$account['account_id'].')"><i class="fa fa-trash"></i></button>'
                ];
            }
            echo json_encode(['data' => $data]);
            return;
        }
        
        // Default view
        $page_data['page_name'] = 'accounts/chart_of_accounts';
        $page_data['page_title'] = get_phrase('chart_of_accounts');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['accounts'] = $this->Accounts_model->get_all_accounts();
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== JOURNAL ENTRIES ====================
    public function journal_entries($action = '', $id = '') {
        if ($action == 'create') {
            $result = $this->Accounts_model->create_journal_entry($this->input->post());
            echo json_encode($result);
            return;
        }
        
        if ($action == 'post' && $id) {
            $result = $this->Accounts_model->post_journal_entry($id);
            echo json_encode($result);
            return;
        }
        
        if ($action == 'void' && $id) {
            $result = $this->Accounts_model->void_journal_entry($id);
            echo json_encode($result);
            return;
        }
        
        if ($action == 'get_data') {
            $entries = $this->Accounts_model->get_journal_entries($this->input->get());
            $data = [];
            foreach($entries as $entry) {
                $status_badge = $entry['status'] == 'posted' ? '<span class="status-posted">POSTED</span>' : ($entry['status'] == 'void' ? '<span class="status-void">VOID</span>' : '<span class="status-draft">DRAFT</span>');
                $actions = $entry['status'] == 'draft' ? '<button class="btn btn-sm btn-success action-btn" onclick="postEntry('.$entry['entry_id'].')"><i class="fa fa-check"></i> Post</button>' : '';
                $actions .= $entry['status'] == 'posted' ? '<button class="btn btn-sm btn-warning action-btn" onclick="voidEntry('.$entry['entry_id'].')"><i class="fa fa-ban"></i> Void</button>' : '';
                $actions .= '<button class="btn btn-sm btn-info action-btn" onclick="viewEntry('.$entry['entry_id'].')"><i class="fa fa-eye"></i></button>';
                
                $data[] = [
                    'entry_no' => $entry['entry_number'],
                    'entry_date' => date('d M Y', strtotime($entry['entry_date'])),
                    'description' => $entry['description'],
                    'amount' => $entry['total_debit'],
                    'status' => $status_badge,
                    'actions' => $actions
                ];
            }
            echo json_encode(['data' => $data]);
            return;
        }
        
        // Default view
        $page_data['page_name'] = 'accounts/journal_entries';
        $page_data['page_title'] = get_phrase('journal_entries');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== BANK ACCOUNTS ====================
    public function bank_accounts($action = '', $id = '') {
        if ($action == 'create') {
            $result = $this->Accounts_model->create_bank_account($this->input->post());
            echo json_encode($result);
            return;
        }
        
        if ($action == 'update' && $id) {
            $result = $this->Accounts_model->update_bank_account($id, $this->input->post());
            echo json_encode($result);
            return;
        }
        
        if ($action == 'get_data') {
            $accounts = $this->Accounts_model->get_bank_accounts();
            $data = [];
            foreach($accounts as $account) {
                $data[] = [
                    'bank_name' => $account['bank_name'],
                    'account_number' => $account['account_number'],
                    'account_name' => $account['account_name'],
                    'balance' => $account['current_balance'],
                    'status' => $account['is_active'],
                    'actions' => '<button class="btn btn-sm btn-info action-btn" onclick="editBankAccount('.$account['bank_account_id'].')"><i class="fa fa-edit"></i></button> <button class="btn btn-sm btn-primary action-btn" onclick="viewTransactions('.$account['bank_account_id'].')"><i class="fa fa-list"></i></button>'
                ];
            }
            echo json_encode(['data' => $data]);
            return;
        }
        
        // Default view
        $page_data['page_name'] = 'accounts/bank_accounts';
        $page_data['page_title'] = get_phrase('bank_accounts');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== BUDGETS ====================
    public function budgets($action = '', $id = '') {
        if ($action == 'create') {
            $result = $this->Accounts_model->create_budget($this->input->post());
            echo json_encode($result);
            return;
        }
        
        if ($action == 'approve' && $id) {
            $result = $this->Accounts_model->approve_budget($id);
            echo json_encode($result);
            return;
        }
        
        if ($action == 'get_data') {
            $budgets = $this->Accounts_model->get_budgets();
            $data = [];
            foreach($budgets as $budget) {
                $utilized = $this->db->select_sum('actual_amount')->where('budget_id', $budget['budget_id'])->get('budget_lines')->row()->actual_amount ?? 0;
                $remaining = $budget['total_amount'] - $utilized;
                $status_badge = $budget['status'] == 'approved' ? '<span class="badge badge-success">Approved</span>' : '<span class="badge badge-warning">Draft</span>';
                $actions = $budget['status'] == 'draft' ? '<button class="btn btn-sm btn-success action-btn" onclick="approveBudget('.$budget['budget_id'].')"><i class="fa fa-check"></i></button>' : '';
                $actions .= '<button class="btn btn-sm btn-info action-btn" onclick="viewBudget('.$budget['budget_id'].')"><i class="fa fa-eye"></i></button>';
                
                $data[] = [
                    'budget_name' => $budget['budget_name'],
                    'fiscal_year' => $budget['fiscal_year'],
                    'total_amount' => $budget['total_amount'],
                    'utilized' => $utilized,
                    'remaining' => $remaining,
                    'status' => $status_badge,
                    'actions' => $actions
                ];
            }
            echo json_encode(['data' => $data]);
            return;
        }
        
        // Default view
        $page_data['page_name'] = 'accounts/budgets';
        $page_data['page_title'] = get_phrase('budget_management');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== REPORTS ====================
    public function reports($report_type = 'balance_sheet') {
        $page_data['page_name'] = 'accounts/reports';
        $page_data['page_title'] = get_phrase('financial_reports');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['report_type'] = $report_type;
        
        // Get report data based on type
        switch ($report_type) {
            case 'balance_sheet':
                $page_data['report_data'] = $this->Accounts_model->get_balance_sheet($this->input->get());
                break;
            case 'income_statement':
                $page_data['report_data'] = $this->Accounts_model->get_income_statement($this->input->get());
                break;
            case 'cash_flow':
                $page_data['report_data'] = $this->Accounts_model->get_cash_flow($this->input->get());
                break;
            case 'trial_balance':
                $page_data['report_data'] = $this->Accounts_model->get_trial_balance($this->input->get());
                break;
            case 'general_ledger':
                $page_data['report_data'] = $this->Accounts_model->get_general_ledger($this->input->get());
                break;
        }
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== FISCAL YEAR ====================
    public function fiscal_year($action = '', $id = '') {
        if ($action == 'create') {
            $result = $this->Accounts_model->create_fiscal_year($this->input->post());
            echo json_encode($result);
            return;
        }
        
        if ($action == 'close' && $id) {
            $result = $this->Accounts_model->close_fiscal_year($id);
            echo json_encode($result);
            return;
        }
        
        // Default view
        $page_data['page_name'] = 'accounts/fiscal_year';
        $page_data['page_title'] = get_phrase('fiscal_year_management');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['fiscal_years'] = $this->Accounts_model->get_fiscal_years();
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== BANK RECONCILIATION ====================
    public function bank_reconciliation($bank_account_id = '') {
        if ($this->input->method() == 'post') {
            $result = $this->Accounts_model->reconcile_transactions($this->input->post());
            echo json_encode($result);
            return;
        }
        
        $page_data['page_name'] = 'accounts/bank_reconciliation';
        $page_data['page_title'] = get_phrase('bank_reconciliation');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['bank_accounts'] = $this->Accounts_model->get_bank_accounts();
        
        if ($bank_account_id) {
            $page_data['transactions'] = $this->Accounts_model->get_unreconciled_transactions($bank_account_id);
        }
        
        $this->load->view('backend/main', $page_data);
    }

    // ==================== AUDIT TRAIL ====================
    public function audit_trail() {
        $page_data['page_name'] = 'accounts/audit_trail';
        $page_data['page_title'] = get_phrase('audit_trail');
        $page_data['account_type'] = $this->session->userdata('login_type');
        
        if ($this->input->get('get_data')) {
            $audit_logs = $this->Accounts_model->get_audit_trail($this->input->get());
            echo json_encode(['data' => $audit_logs]);
            return;
        }
        
        $this->load->view('backend/main', $page_data);
    }
}
