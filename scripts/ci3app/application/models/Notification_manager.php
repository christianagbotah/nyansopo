<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Notification Manager Model
 * 
 * Manages in-app notifications - storage, retrieval, read status
 */
class Notification_manager extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create new in-app notification
     * 
     * @param int $user_id Recipient user ID
     * @param string $module Module name (e.g., 'payroll')
     * @param string $event_type Event type (e.g., 'approval')
     * @param string $reference_id Reference identifier (e.g., pay_id)
     * @param string $title Notification title
     * @param string $message Notification message
     * @return int|false Notification ID or false on failure
     */
    public function create_notification($user_id, $module, $event_type, $reference_id, $title, $message) {
        // Map module_eventtype to notification type for routing
        // Format: module_eventtype (e.g., payroll_approval, payroll_submission, payroll_rejection)
        $notification_type = $module . '_' . $event_type;
        
        // Prepare data JSON with additional context
        $data_json = json_encode(array(
            'module' => $module,
            'event_type' => $event_type,
            'reference_id' => $reference_id,
            'pay_id' => $reference_id // For backward compatibility with routing logic
        ));
        
        $data = array(
            'user_id' => $user_id,
            'user_type' => 'admin', // Default user type for payroll notifications
            'type' => $notification_type,
            'title' => $title,
            'message' => $message,
            'data' => $data_json,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        );
        
        $this->db->insert('notifications', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get notifications for user
     * 
     * @param int $user_id User ID
     * @param int $limit Maximum records to return
     * @param bool $unread_only Return only unread notifications
     * @return array Notification records
     */
    public function get_user_notifications($user_id, $limit = 10, $unread_only = false) {
        $this->db->where('user_id', $user_id);
        
        if ($unread_only) {
            $this->db->where('is_read', 0);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get('notifications');
        $notifications = $query->result_array();
        
        // Format for display
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = $this->format_time_ago($notification['created_at']);
        }
        
        return $notifications;
    }
    
    /**
     * Get unread notification count
     * 
     * @param int $user_id User ID
     * @return int Count of unread notifications
     */
    public function get_unread_count($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results('notifications');
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for security)
     * @return bool Success status
     */
    public function mark_as_read($notification_id, $user_id) {
        $this->db->where('notification_id', $notification_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('notifications', array('is_read' => 1, 'read_at' => date('Y-m-d H:i:s')));
    }
    
    /**
     * Mark all notifications as read for user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function mark_all_as_read($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->update('notifications', array('is_read' => 1, 'read_at' => date('Y-m-d H:i:s')));
    }
    
    /**
     * Format timestamp as human-readable relative time
     * 
     * @param string $datetime Datetime string
     * @return string Formatted time (e.g., "2 minutes ago")
     */
    private function format_time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y \a\t g:i A', $time);
        }
    }
}
