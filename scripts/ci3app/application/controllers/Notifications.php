<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->database();
        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    function get_unread_count() {
        $user_id = $this->session->userdata('login_user_id');
        $count = $this->db->where('user_id', $user_id)
                          ->where('is_read', 0)
                          ->count_all_results('notifications');
        echo json_encode(['count' => $count]);
    }
    
    function get_notifications() {
        $user_id = $this->session->userdata('login_user_id');
        
        $notifications = $this->db->where('user_id', $user_id)
                                   ->order_by('created_at', 'DESC')
                                   ->limit(10)
                                   ->get('notifications')
                                   ->result_array();
        
        $unread_count = 0;
        foreach($notifications as &$notif) {
            $notif['time_ago'] = $this->time_ago($notif['created_at']);
            if($notif['is_read'] == 0) $unread_count++;
        }
        
        echo json_encode([
            'status' => 'success',
            'count' => $unread_count,
            'notifications' => $notifications
        ]);
    }
    
    function get_notification_details($notification_id) {
        $user_id = $this->session->userdata('login_user_id');
        $user_type = $this->session->userdata('login_type');
        
        $notification = $this->db->where('notification_id', $notification_id)
                                 ->where('user_id', $user_id)
                                 ->get('notifications')
                                 ->row_array();
        
        if($notification) {
            $notification['time_ago'] = $this->time_ago($notification['created_at']);
            
            // Mark as read if not already
            if ($notification['is_read'] == 0) {
                $this->db->where('notification_id', $notification_id)
                         ->where('user_id', $user_id)
                         ->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
            }
            
            // Determine route URL based on notification type
            $route_url = null;
            $action_label = 'View Details';
            $data = $notification['data'] ? json_decode($notification['data'], true) : array();
            
            switch ($notification['type']) {
                case 'admission':
                    if (isset($data['student_id'])) {
                        $route_url = site_url($user_type . '/student_information/' . $data['student_id']);
                        $action_label = 'View Student Profile';
                    }
                    break;
                    
                case 'payment':
                    if (isset($data['invoice_code'])) {
                        $route_url = site_url($user_type . '/invoice_view/' . $data['invoice_code']);
                        $action_label = 'View Invoice';
                    } elseif (isset($data['student_id'])) {
                        $route_url = site_url($user_type . '/student_payment/' . $data['student_id']);
                        $action_label = 'View Payments';
                    }
                    break;
                    
                case 'discount_approval':
                    $route_url = site_url($user_type . '/discount_approvals');
                    $action_label = 'Review Discounts';
                    break;
                    
                case 'invoice_delete_approval':
                case 'invoice_edit_approval':
                case 'invoice_modification':
                    $route_url = site_url($user_type . '/manageRequestApproval');
                    $action_label = 'Review Requests';
                    break;
                    
                case 'invoice_modification_decision':
                    if (isset($data['request_id'])) {
                        $route_url = site_url($user_type . '/view_invoice_request/' . $data['request_id']);
                        $action_label = 'View Request Status';
                    }
                    break;
                    
                case 'payroll':
                case 'payroll_approval':
                case 'payroll_submission':
                case 'payroll_rejection':
                    if (isset($data['pay_id'])) {
                        $route_url = site_url($user_type . '/view_payslip/' . $data['pay_id']);
                        $action_label = 'View Payslip';
                    } else {
                        $route_url = site_url($user_type . '/payroll_pending_approvals');
                        $action_label = 'Review Payroll';
                    }
                    break;
                    
                case 'receipt_modification':
                case 'receipt_modification_request':
                case 'receipt_approval':
                    if (isset($data['request_id'])) {
                        $route_url = site_url($user_type . '/view_receipt_request/' . $data['request_id']);
                        $action_label = 'View Request';
                    } else {
                        $route_url = site_url($user_type . '/receipt_modification_requests');
                        $action_label = 'View Requests';
                    }
                    break;
                    
                case 'attendance':
                    if (isset($data['class_id'])) {
                        $route_url = site_url($user_type . '/manage_attendance/' . $data['class_id']);
                        $action_label = 'View Attendance';
                    } else {
                        $route_url = site_url($user_type . '/manage_attendance');
                        $action_label = 'Manage Attendance';
                    }
                    break;
                    
                case 'transport':
                    if (isset($data['transport_id'])) {
                        $route_url = site_url($user_type . '/transport_route/' . $data['transport_id']);
                        $action_label = 'View Route';
                    } else {
                        $route_url = site_url($user_type . '/transport');
                        $action_label = 'View Transport';
                    }
                    break;
                    
                case 'inventory':
                    if (isset($data['product_id'])) {
                        $route_url = site_url($user_type . '/inventory/product_details/' . $data['product_id']);
                        $action_label = 'View Product';
                    } else {
                        $route_url = site_url($user_type . '/inventory');
                        $action_label = 'View Inventory';
                    }
                    break;
                    
                case 'exam':
                case 'marks':
                    if (isset($data['class_id']) && isset($data['exam_id'])) {
                        $route_url = site_url($user_type . '/marks/' . $data['exam_id'] . '/' . $data['class_id']);
                        $action_label = 'View Marks';
                    }
                    break;
                    
                default:
                    // Generic fallback
                    if (isset($data['link'])) {
                        $route_url = site_url($data['link']);
                    }
                    break;
            }
            
            echo json_encode([
                'status' => 'success',
                'notification' => $notification,
                'route_url' => $route_url,
                'action_label' => $action_label
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Notification not found'
            ]);
        }
    }
    
    function mark_as_read($notification_id) {
        $user_id = $this->session->userdata('login_user_id');
        $this->db->where('notification_id', $notification_id)
                 ->where('user_id', $user_id)
                 ->update('notifications', ['is_read' => 1]);
        echo json_encode(['status' => 'success']);
    }
    
    function mark_all_read() {
        $user_id = $this->session->userdata('login_user_id');
        $this->db->where('user_id', $user_id)
                 ->where('is_read', 0)
                 ->update('notifications', ['is_read' => 1]);
        echo json_encode(['status' => 'success']);
    }
    
    private function time_ago($timestamp) {
        $diff = time() - $timestamp;
        if($diff < 60) return 'Just now';
        if($diff < 3600) return floor($diff/60) . ' min ago';
        if($diff < 86400) return floor($diff/3600) . ' hours ago';
        if($diff < 604800) return floor($diff/86400) . ' days ago';
        return date('M d, Y', $timestamp);
    }
}
