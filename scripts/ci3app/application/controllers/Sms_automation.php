<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sms_automation extends CI_Controller {

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
            
        $page_data['page_name'] = 'sms_automation';
        $page_data['page_title'] = get_phrase('sms_automation');
        $this->load->view('backend/index', $page_data);
    }

    public function modal($param1 = '') {
        $page_data['param1'] = $param1;
        $this->load->view('backend/modal/sms_automation_form', $page_data);
    }

    public function test_modal($automation_id) {
        $page_data['automation_id'] = $automation_id;
        $this->load->view('backend/modal/sms_test_form', $page_data);
    }

    public function logs_modal($automation_id) {
        $logs = $this->db->select('sal.*, sa.name as automation_name')
            ->from('sms_automation_logs sal')
            ->join('sms_automations sa', 'sal.automation_id = sa.id')
            ->where('sal.automation_id', $automation_id)
            ->order_by('sal.sent_at', 'DESC')
            ->limit(100)
            ->get()->result_array();
        
        $page_data['logs'] = $logs;
        $this->load->view('backend/modal/sms_logs', $page_data);
    }

    public function save() {
        $automation_id = $this->input->post('automation_id');
        
        $data = [
            'name' => $this->input->post('name'),
            'trigger_event' => $this->input->post('trigger_event'),
            'recipients' => $this->input->post('recipients'),
            'message_template' => $this->input->post('message_template'),
            'trigger_days' => $this->input->post('trigger_days') ?: 0,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];

        if ($automation_id) {
            $old = $this->db->where('id', $automation_id)->get('sms_automations')->row_array();
            if ($this->db->where('id', $automation_id)->update('sms_automations', $data)) {
                $this->log_audit('sms_automation', 'update', $automation_id, $old, $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('automation_updated_successfully')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
            }
        } else {
            $data['created_by'] = $this->session->userdata('admin_id');
            $data['created_at'] = date('Y-m-d H:i:s');
            
            if ($this->db->insert('sms_automations', $data)) {
                $this->log_audit('sms_automation', 'create', $this->db->insert_id(), null, $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('automation_created_successfully')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
            }
        }
    }

    public function get_automations() {
        $this->db->select('sa.*, COUNT(sal.id) as total_sent');
        $this->db->from('sms_automations sa');
        $this->db->join('sms_automation_logs sal', 'sa.id = sal.automation_id', 'left');
        $this->db->group_by('sa.id');
        $this->db->order_by('sa.created_at', 'DESC');
        $automations = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $automations]);
    }

    public function create() {
        $data = [
            'name' => $this->input->post('name'),
            'trigger_event' => $this->input->post('trigger_event'),
            'recipients' => $this->input->post('recipients'),
            'message_template' => $this->input->post('message_template'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'created_by' => $this->session->userdata('admin_id')
        ];

        if ($this->db->insert('sms_automations', $data)) {
            $this->log_audit('sms_automation', 'create', $this->db->insert_id(), null, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('automation_created_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function update($automation_id) {
        $old = $this->db->where('id', $automation_id)->get('sms_automations')->row_array();
        
        $data = [
            'name' => $this->input->post('name'),
            'trigger_event' => $this->input->post('trigger_event'),
            'recipients' => $this->input->post('recipients'),
            'message_template' => $this->input->post('message_template'),
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];

        if ($this->db->where('id', $automation_id)->update('sms_automations', $data)) {
            $this->log_audit('sms_automation', 'update', $automation_id, $old, $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('automation_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function toggle_status($automation_id) {
        $automation = $this->db->where('id', $automation_id)->get('sms_automations')->row();
        $new_status = $automation->is_active ? 0 : 1;
        
        if ($this->db->where('id', $automation_id)->update('sms_automations', ['is_active' => $new_status])) {
            $this->log_audit('sms_automation', 'toggle_status', $automation_id, ['is_active' => $automation->is_active], ['is_active' => $new_status]);
            echo json_encode(['status' => 'success', 'message' => get_phrase('status_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function delete($automation_id) {
        $old = $this->db->where('id', $automation_id)->get('sms_automations')->row_array();
        
        if ($this->db->where('id', $automation_id)->delete('sms_automations')) {
            $this->log_audit('sms_automation', 'delete', $automation_id, $old, null);
            echo json_encode(['status' => 'success', 'message' => get_phrase('automation_deleted_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }

    public function get_logs($automation_id = null) {
        $this->db->select('sal.*, sa.name as automation_name');
        $this->db->from('sms_automation_logs sal');
        $this->db->join('sms_automations sa', 'sal.automation_id = sa.id');
        
        if ($automation_id) {
            $this->db->where('sal.automation_id', $automation_id);
        }
        
        $this->db->order_by('sal.sent_at', 'DESC');
        $this->db->limit(100);
        $logs = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'data' => $logs]);
    }

    public function get_statistics() {
        $this->db->select('
            COUNT(*) as total_automations,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_automations
        ');
        $automation_stats = $this->db->get('sms_automations')->row_array();
        
        $this->db->select('
            COUNT(*) as total_sent,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
        ');
        $log_stats = $this->db->get('sms_automation_logs')->row_array();
        
        // Recent activity
        $this->db->select('DATE(sent_at) as date, COUNT(*) as count');
        $this->db->where('sent_at >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->group_by('DATE(sent_at)');
        $this->db->order_by('date', 'ASC');
        $activity = $this->db->get('sms_automation_logs')->result_array();
        
        $stats = array_merge($automation_stats, $log_stats);
        $stats['activity'] = $activity;
        $stats['success_rate'] = $log_stats['total_sent'] > 0 ? 
            round(($log_stats['successful'] / $log_stats['total_sent']) * 100, 2) : 0;
        
        echo json_encode(['status' => 'success', 'data' => $stats]);
    }

    public function test_automation($automation_id) {
        $automation = $this->db->where('id', $automation_id)->get('sms_automations')->row_array();
        
        if (!$automation) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('automation_not_found')]);
            return;
        }
        
        $test_phone = $this->input->post('test_phone');
        $test_message = $this->replace_placeholders($automation['message_template'], [
            'student_name' => 'John Doe',
            'class' => 'Grade 5',
            'date' => date('Y-m-d'),
            'amount' => '500.00'
        ]);
        
        // Log test message
        $log_data = [
            'automation_id' => $automation_id,
            'recipient_phone' => $test_phone,
            'recipient_name' => 'Test User',
            'message' => $test_message,
            'status' => 'sent'
        ];
        
        $this->db->insert('sms_automation_logs', $log_data);
        
        echo json_encode([
            'status' => 'success', 
            'message' => get_phrase('test_message_sent'),
            'preview' => $test_message
        ]);
    }

    private function replace_placeholders($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    public function get_available_placeholders() {
        $placeholders = [
            'student_name' => 'Student full name',
            'class' => 'Student class',
            'date' => 'Current date',
            'amount' => 'Payment amount',
            'invoice_code' => 'Invoice number',
            'balance' => 'Outstanding balance',
            'term' => 'Current term',
            'parent_name' => 'Parent name',
            'exam_name' => 'Exam name',
            'score' => 'Exam score',
            'grade' => 'Exam grade'
        ];
        
        echo json_encode(['status' => 'success', 'data' => $placeholders]);
    }

    public function get_trigger_events() {
        $events = [
            'payment_received' => 'Payment Received',
            'invoice_created' => 'Invoice Created',
            'exam_result_published' => 'Exam Result Published',
            'attendance_marked' => 'Attendance Marked',
            'monthly_bill_reminder' => 'Monthly Bill Reminder (Scheduled)'
        ];
        
        echo json_encode(['status' => 'success', 'data' => $events]);
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
