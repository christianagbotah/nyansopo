<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // Run monthly bill reminders
    public function monthly_bill_reminders() {
        $automation = $this->db->where('trigger_event', 'monthly_bill_reminder')
            ->where('is_active', 1)
            ->get('sms_automations')->row();
        
        if (!$automation) {
            echo json_encode(['status' => 'error', 'message' => 'No active bill reminder automation found']);
            return;
        }
        
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        $currency = get_settings('currency');
        $school_name = get_settings('system_name');
        
        $parents_data = [];
        $students = $this->db->select('s.student_id, s.name, s.parent_id, p.phone, p.name as parent_name')
            ->from('student s')
            ->join('parent p', 's.parent_id = p.parent_id')
            ->join('enroll e', 's.student_id = e.student_id')
            ->where('e.year', $running_year)
            ->where('e.term', $running_term)
            ->where('e.mute', '0')
            ->where('p.phone !=', '')
            ->get()->result_array();
        
        foreach ($students as $student) {
            $balance = $this->db->select('SUM(due) as total_due')
                ->where('student_id', $student['student_id'])
                ->where('due >', 0)
                ->get('invoice')->row();
            
            $owing = $balance ? floatval($balance->total_due) : 0;
            
            if ($owing > 0) {
                $parent_id = $student['parent_id'];
                if (!isset($parents_data[$parent_id])) {
                    $parents_data[$parent_id] = [
                        'phone' => $student['phone'],
                        'parent_name' => $student['parent_name'],
                        'students' => []
                    ];
                }
                $parents_data[$parent_id]['students'][] = [
                    'name' => $student['name'],
                    'owing' => $owing
                ];
            }
        }
        
        if (empty($parents_data)) {
            echo json_encode(['status' => 'success', 'message' => 'No outstanding balances found']);
            return;
        }
        
        $personalizedRecipients = [];
        foreach ($parents_data as $parent_id => $data) {
            $child_word = count($data['students']) > 1 ? 'children' : 'child';
            $bill_word = count($data['students']) > 1 ? 'bills' : 'bill';
            $message = "Bill Reminder from " . $school_name . ". Dear cherished parent, kindly be reminded of your " . $child_word . "'s outstanding " . $bill_word . ": ";
            
            $bills = [];
            $total_owing = 0;
            foreach ($data['students'] as $student) {
                $bills[] = $student['name'] . ": " . $currency . number_format($student['owing'], 2);
                $total_owing += $student['owing'];
            }
            $message .= implode(", ", $bills) . ". Total: " . $currency . number_format($total_owing, 2) . ". Please settle outstanding fees. Thank you.";
            
            $personalizedRecipients[] = [
                'Recipient' => $data['phone'],
                'Content' => $message
            ];
            
            // Log each message
            $this->db->insert('sms_automation_logs', [
                'automation_id' => $automation->id,
                'recipient_phone' => $data['phone'],
                'recipient_name' => $data['parent_name'],
                'message' => $message,
                'status' => 'pending',
                'sent_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $result = $this->sms_model->send_sms_batch_personalized($personalizedRecipients);
        
        // Update logs status
        $status = ($result === 0) ? 'sent' : 'failed';
        $this->db->where('automation_id', $automation->id)
            ->where('status', 'pending')
            ->where('sent_at >=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->update('sms_automation_logs', ['status' => $status]);
        
        // Update automation last_run
        $this->db->where('id', $automation->id)->update('sms_automations', [
            'last_run' => date('Y-m-d H:i:s')
        ]);
        
        $sent_count = count($personalizedRecipients);
        echo json_encode([
            'status' => $result === 0 ? 'success' : 'error',
            'message' => $result === 0 ? "Bill reminders sent to $sent_count parent(s)" : "Failed to send reminders. Status: $result",
            'count' => $sent_count
        ]);
    }
}
