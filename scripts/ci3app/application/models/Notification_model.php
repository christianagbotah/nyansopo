<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Notification_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    // Create a new notification
    function create_notification($user_id, $type, $title, $message, $data = null) {
        $notification_data = array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'is_read' => 0,
            'created_at' => time()
        );
        
        $this->db->insert('notifications', $notification_data);
        return $this->db->insert_id();
    }

    // Get unread notifications for a user
    function get_unread_notifications($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('notifications')->result_array();
    }

    // Get unread count
    function get_unread_count($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results('notifications');
    }

    // Mark notification as read
    function mark_as_read($notification_id) {
        $this->db->where('notification_id', $notification_id);
        $this->db->update('notifications', array('is_read' => 1));
    }

    // Mark all notifications as read for a user
    function mark_all_as_read($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->update('notifications', array('is_read' => 1));
    }

    // Log admission details
    function log_admission($student_id, $admitted_by, $class_id, $section_id, $residence_type, $bill_items, $total_amount) {
        $log_data = array(
            'student_id' => $student_id,
            'admitted_by' => $admitted_by,
            'class_id' => $class_id,
            'section_id' => $section_id,
            'residence_type' => $residence_type,
            'bill_items' => json_encode($bill_items),
            'total_bill_amount' => $total_amount,
            'admission_date' => strtotime('today'),
            'created_at' => time()
        );
        
        $this->db->insert('admission_logs', $log_data);
        return $this->db->insert_id();
    }

    // Get admissions for a specific date
    function get_admissions_by_date($date) {
        $this->db->select('al.*, s.name as student_name, s.student_code, c.name as class_name, c.name_numeric, sec.name as section_name, a.name as admitted_by_name');
        $this->db->from('admission_logs al');
        $this->db->join('student s', 's.student_id = al.student_id');
        $this->db->join('class c', 'c.class_id = al.class_id');
        $this->db->join('section sec', 'sec.section_id = al.section_id');
        $this->db->join('admin a', 'a.admin_id = al.admitted_by');
        $this->db->where('al.admission_date', $date);
        $this->db->order_by('al.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    // Send admission notification to super admins
    function notify_super_admins_admission($student_name, $class_name, $bill_items, $total_amount) {
        // Get all super admins (level = 1)
        $this->db->where('level', 1);
        $super_admins = $this->db->get('admin')->result_array();
        
        $title = 'New Student Admission';
        $message = "Student {$student_name} has been admitted to {$class_name}. Total Bill: GH₵ " . number_format($total_amount, 2);
        
        $data = array(
            'student_name' => $student_name,
            'class_name' => $class_name,
            'bill_items' => $bill_items,
            'total_amount' => $total_amount
        );
        
        foreach ($super_admins as $admin) {
            $this->create_notification($admin['admin_id'], 'admission', $title, $message, $data);
            
            // Send email if admin has email
            if (!empty($admin['email'])) {
                $this->send_admission_email($admin['email'], $admin['name'], $student_name, $class_name, $bill_items, $total_amount);
            }
            
            // Send SMS if admin has phone
            if (!empty($admin['phone'])) {
                $this->send_admission_sms($admin['phone'], $student_name, $class_name, $total_amount);
            }
        }
    }

    // Send admission email
    private function send_admission_email($email, $admin_name, $student_name, $class_name, $bill_items, $total_amount) {
        $system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
        
        $subject = "New Student Admission - {$student_name}";
        
        $bill_list = '';
        foreach ($bill_items as $item) {
            $bill_list .= "<tr><td>{$item['title']}</td><td style='text-align:right'>GH₵ " . number_format($item['amount'], 2) . "</td></tr>";
        }
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #667eea;'>New Student Admission</h2>
            <p>Dear {$admin_name},</p>
            <p>A new student has been admitted to {$system_name}.</p>
            <h3>Student Details:</h3>
            <p><strong>Name:</strong> {$student_name}<br>
            <strong>Class:</strong> {$class_name}</p>
            <h3>Bill Items:</h3>
            <table style='border-collapse: collapse; width: 100%; max-width: 500px;'>
                <thead>
                    <tr style='background: #f3f4f6;'>
                        <th style='padding: 10px; text-align: left; border: 1px solid #e5e7eb;'>Item</th>
                        <th style='padding: 10px; text-align: right; border: 1px solid #e5e7eb;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {$bill_list}
                    <tr style='background: #667eea; color: white; font-weight: bold;'>
                        <td style='padding: 10px; border: 1px solid #e5e7eb;'>TOTAL</td>
                        <td style='padding: 10px; text-align: right; border: 1px solid #e5e7eb;'>GH₵ " . number_format($total_amount, 2) . "</td>
                    </tr>
                </tbody>
            </table>
            <p style='margin-top: 20px;'>Best regards,<br>{$system_name}</p>
        </body>
        </html>
        ";
        
        $this->load->library('email');
        $this->email->from($this->db->get_where('settings', array('type' => 'system_email'))->row()->description, $system_name);
        $this->email->to($email);
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->send();
    }

    // Send admission SMS
    private function send_admission_sms($phone, $student_name, $class_name, $total_amount) {
        $system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
        $message = "New Admission Alert: {$student_name} admitted to {$class_name}. Total Bill: GH₵ " . number_format($total_amount, 2) . " - {$system_name}";
        
        // Use existing SMS model
        $this->load->model('sms_model');
        $this->sms_model->send_sms($message, array($phone));
    }
}
