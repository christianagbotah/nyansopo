<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expense_management extends CI_Controller {

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
            
        $page_data['page_name'] = 'expense_management';
        $page_data['page_title'] = get_phrase('expense_management');
        $this->load->view('backend/main', $page_data);
    }

    public function get_expenses() {
        $status = $this->input->get('status');
        $category = $this->input->get('category');
        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');

        $this->db->select('e.*, ec.name as category_name, u1.name as requested_by_name, u2.name as approved_by_name');
        $this->db->from('expenses_enhanced e');
        $this->db->join('expense_categories_enhanced ec', 'e.category_id = ec.id');
        $this->db->join('admin u1', 'e.requested_by = u1.admin_id', 'left');
        $this->db->join('admin u2', 'e.approved_by = u2.admin_id', 'left');
        
        if ($status) $this->db->where('e.status', $status);
        if ($category) $this->db->where('e.category_id', $category);
        if ($from_date) $this->db->where('e.expense_date >=', $from_date);
        if ($to_date) $this->db->where('e.expense_date <=', $to_date);
        
        $this->db->order_by('e.created_at', 'DESC');
        $expenses = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $expenses]);
    }

    public function create() {
        $config['upload_path'] = './uploads/expenses/';
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $config['max_size'] = 5120;
        $config['file_name'] = 'expense_' . time();
        
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }
        
        $this->load->library('upload', $config);
        
        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            if ($this->upload->do_upload('attachment')) {
                $attachment = $this->upload->data('file_name');
            }
        }

        $data = [
            'expense_date' => $this->input->post('expense_date'),
            'description' => $this->input->post('description'),
            'category_id' => $this->input->post('category_id'),
            'vendor' => $this->input->post('vendor'),
            'amount' => $this->input->post('amount'),
            'payment_method' => $this->input->post('payment_method'),
            'reference_number' => $this->input->post('reference_number'),
            'attachment' => $attachment,
            'notes' => $this->input->post('notes'),
            'status' => 'pending',
            'requested_by' => $this->session->userdata('admin_id')
        ];

        if ($this->db->insert('expenses_enhanced', $data)) {
            $this->log_audit('expense', 'create', $this->db->insert_id(), null, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('expense_created_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function update($expense_id) {
        $old = $this->db->where('id', $expense_id)->get('expenses_enhanced')->row_array();
        
        $config['upload_path'] = './uploads/expenses/';
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $config['max_size'] = 5120;
        $config['file_name'] = 'expense_' . time();
        
        $this->load->library('upload', $config);
        
        $attachment = $old['attachment'];
        if (!empty($_FILES['attachment']['name'])) {
            if ($this->upload->do_upload('attachment')) {
                if ($old['attachment'] && file_exists('./uploads/expenses/' . $old['attachment'])) {
                    unlink('./uploads/expenses/' . $old['attachment']);
                }
                $attachment = $this->upload->data('file_name');
            }
        }

        $data = [
            'expense_date' => $this->input->post('expense_date'),
            'description' => $this->input->post('description'),
            'category_id' => $this->input->post('category_id'),
            'vendor' => $this->input->post('vendor'),
            'amount' => $this->input->post('amount'),
            'payment_method' => $this->input->post('payment_method'),
            'reference_number' => $this->input->post('reference_number'),
            'attachment' => $attachment,
            'notes' => $this->input->post('notes')
        ];

        if ($this->db->where('id', $expense_id)->update('expenses_enhanced', $data)) {
            $this->log_audit('expense', 'update', $expense_id, $old, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('expense_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function approve($expense_id) {
        $expense = $this->db->where('id', $expense_id)->get('expenses_enhanced')->row_array();
        
        $data = [
            'status' => 'approved',
            'approved_by' => $this->session->userdata('admin_id'),
            'approved_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->where('id', $expense_id)->update('expenses_enhanced', $data)) {
            $this->log_audit('expense', 'approve', $expense_id, null, $data);
            
            // Link to budget and update utilization
            $this->load->library('budget_integration');
            $this->budget_integration->link_expense_to_budget($expense_id);
            
            // Send email notification
            $this->load->library('email_notification');
            $expense['approved_by_name'] = $this->session->userdata('name');
            $this->email_notification->expense_approved($expense);
            
            echo json_encode(['status' => 'success', 'message' => get_phrase('expense_approved_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function reject($expense_id) {
        $data = [
            'status' => 'rejected',
            'approved_by' => $this->session->userdata('admin_id'),
            'approved_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $this->input->post('rejection_reason')
        ];

        if ($this->db->where('id', $expense_id)->update('expenses_enhanced', $data)) {
            $this->log_audit('expense', 'reject', $expense_id, null, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('expense_rejected_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function delete($expense_id) {
        $expense = $this->db->where('id', $expense_id)->get('expenses_enhanced')->row_array();
        
        if ($expense['attachment'] && file_exists('./uploads/expenses/' . $expense['attachment'])) {
            unlink('./uploads/expenses/' . $expense['attachment']);
        }
        
        if ($this->db->where('id', $expense_id)->delete('expenses_enhanced')) {
            $this->log_audit('expense', 'delete', $expense_id, $expense, null);
            echo json_encode(['status' => 'success', 'message' => get_phrase('expense_deleted_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function get_categories() {
        $categories = $this->db->where('is_active', 1)
                               ->order_by('name', 'ASC')
                               ->get('expense_categories_enhanced')
                               ->result_array();
        echo json_encode(['status' => 'success', 'data' => $categories]);
    }

    public function get_statistics() {
        $year = $this->input->get('year') ?: date('Y');
        $month = $this->input->get('month');

        $this->db->select('
            COUNT(*) as total_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as total_approved_amount,
            SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as total_pending_amount
        ');
        $this->db->where('YEAR(expense_date)', $year);
        if ($month) $this->db->where('MONTH(expense_date)', $month);
        
        $stats = $this->db->get('expenses_enhanced')->row_array();
        
        // Category breakdown
        $this->db->select('ec.name as category, SUM(e.amount) as total, COUNT(*) as count');
        $this->db->from('expenses_enhanced e');
        $this->db->join('expense_categories_enhanced ec', 'e.category_id = ec.id');
        $this->db->where('e.status', 'approved');
        $this->db->where('YEAR(e.expense_date)', $year);
        if ($month) $this->db->where('MONTH(e.expense_date)', $month);
        $this->db->group_by('e.category_id');
        $this->db->order_by('total', 'DESC');
        
        $stats['by_category'] = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $stats]);
    }

    public function export_expenses() {
        
        $this->db->select('e.expense_date, e.description, ec.name as category, e.vendor, e.amount, e.status');
        $this->db->from('expenses_enhanced e');
        $this->db->join('expense_categories_enhanced ec', 'e.category_id = ec.id');
        $this->db->order_by('e.expense_date', 'DESC');
        $expenses = $this->db->get()->result_array();
        
        $data = [];
        foreach ($expenses as $expense) {
            $data[] = [
                $expense['expense_date'],
                $expense['description'],
                $expense['category'],
                $expense['vendor'] ?: '-',
                'GHS ' . number_format($expense['amount'], 2),
                ucfirst($expense['status'])
            ];
        }
        
        $headers = ['Date', 'Description', 'Category', 'Vendor', 'Amount', 'Status'];
        export_to_excel($data, 'Expenses_' . date('Y-m-d'), $headers);
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
