<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * User Notification Preferences Model
 * 
 * Manages SMS opt-in/opt-out preferences for payroll approval notifications
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.6, 4.6
 */
class User_notification_preferences extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get user SMS preference
     * Returns whether SMS notifications are enabled for the user
     * 
     * Requirement 2.2: Display current SMS notification status
     * Requirement 2.6: Default new users to SMS notifications disabled
     * 
     * @param int $user_id User ID (admin_id)
     * @return bool True if SMS enabled, false if disabled or not set
     */
    public function get_user_sms_preference($user_id) {
        // Query the user_notification_preferences table
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('user_notification_preferences');
        
        // If no preference record exists, return false (default: disabled)
        if ($query->num_rows() == 0) {
            return false;
        }
        
        // Return the sms_enabled value (0 or 1)
        $preference = $query->row();
        return (bool)$preference->sms_enabled;
    }
    
    /**
     * Set user SMS preference
     * Updates user's SMS notification opt-in/opt-out setting
     * 
     * Requirements: 2.3, 2.4, 4.6
     * 
     * @param int $user_id User ID (admin_id)
     * @param bool $enabled True to enable SMS, false to disable
     * @return array Result with success status and message
     */
    public function set_user_sms_preference($user_id, $enabled) {
        // Requirement 2.3: Validate phone number exists before enabling SMS
        if ($enabled) {
            $phone_valid = $this->validate_phone_number($user_id);
            if (!$phone_valid) {
                return array(
                    'success' => false,
                    'message' => 'Cannot enable SMS notifications. Please add a valid phone number to your profile first.'
                );
            }
        }
        
        // Check if preference record already exists
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('user_notification_preferences');
        
        $preference_data = array(
            'user_id' => $user_id,
            'sms_enabled' => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($query->num_rows() > 0) {
            // UPDATE existing record
            $this->db->where('user_id', $user_id);
            $result = $this->db->update('user_notification_preferences', array(
                'sms_enabled' => $enabled ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        } else {
            // INSERT new record
            $result = $this->db->insert('user_notification_preferences', $preference_data);
        }
        
        if ($result) {
            $status_text = $enabled ? 'enabled' : 'disabled';
            return array(
                'success' => true,
                'message' => 'SMS notifications ' . $status_text . ' successfully.'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update SMS notification preferences. Please try again.'
            );
        }
    }
    
    /**
     * Validate phone number exists for user
     * Checks admin table for non-null, non-empty phone number
     * 
     * Requirement 2.3: Validate phone number exists before enabling SMS
     * 
     * @param int $user_id User ID (admin_id)
     * @return bool True if valid phone exists, false otherwise
     */
    public function validate_phone_number($user_id) {
        // Query admin table for user's phone number
        $this->db->select('phone');
        $this->db->where('admin_id', $user_id);
        $query = $this->db->get('admin');
        
        if ($query->num_rows() == 0) {
            return false;
        }
        
        $user = $query->row();
        
        // Check if phone is not null and not empty
        if (empty($user->phone) || trim($user->phone) == '') {
            return false;
        }
        
        // Phone exists and is not empty
        return true;
    }
    
    /**
     * Get SMS-enabled users by role
     * Returns users who have SMS notifications enabled and match the specified roles
     * 
     * Requirement 4.6: Filter recipients based on SMS preferences
     * 
     * @param array $roles Array of role names or single role string
     * @return array Array of user records with id, name, email, phone, role
     */
    public function get_sms_enabled_users_by_role($roles) {
        // Normalize roles to array
        if (!is_array($roles)) {
            $roles = array($roles);
        }
        
        // JOIN admin table with user_notification_preferences
        // Filter by roles and sms_enabled = 1
        $this->db->select('admin.admin_id as user_id, admin.name, admin.email, admin.phone, admin.level as role');
        $this->db->from('admin');
        $this->db->join('user_notification_preferences', 'admin.admin_id = user_notification_preferences.user_id', 'inner');
        $this->db->where('user_notification_preferences.sms_enabled', 1);
        $this->db->where('admin.active_status', 1); // Only active users
        $this->db->where_in('admin.level', $roles);
        
        $query = $this->db->get();
        
        return $query->result_array();
    }
    
    /**
     * Initialize default preferences for new user
     * Creates preference record with sms_enabled = 0 (disabled by default)
     * 
     * Requirement 2.6: Default new users to SMS notifications disabled
     * 
     * @param int $user_id User ID (admin_id)
     * @return bool Success status
     */
    public function initialize_default_preferences($user_id) {
        // Check if preferences already exist
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('user_notification_preferences');
        
        // Only initialize if no record exists
        if ($query->num_rows() == 0) {
            $preference_data = array(
                'user_id' => $user_id,
                'sms_enabled' => 0, // Default: SMS disabled
                'updated_at' => date('Y-m-d H:i:s')
            );
            
            return $this->db->insert('user_notification_preferences', $preference_data);
        }
        
        // Already initialized, return true
        return true;
    }
    
    /**
     * Get all users with their SMS preference status
     * For admin settings/management page
     * 
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array Array of user records with preference status
     */
    public function get_all_users_with_preferences($limit = null, $offset = 0) {
        $this->db->select('admin.admin_id as user_id, admin.name, admin.email, admin.phone, admin.level as role, 
                          COALESCE(user_notification_preferences.sms_enabled, 0) as sms_enabled,
                          user_notification_preferences.updated_at as preference_updated_at');
        $this->db->from('admin');
        $this->db->join('user_notification_preferences', 'admin.admin_id = user_notification_preferences.user_id', 'left');
        $this->db->where('admin.active_status', 1); // Only active users
        $this->db->order_by('admin.name', 'ASC');
        
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
        $query = $this->db->get();
        
        return $query->result_array();
    }
    
    /**
     * Count total users with SMS enabled
     * For statistics/monitoring
     * 
     * @return int Count of users with SMS enabled
     */
    public function count_sms_enabled_users() {
        $this->db->where('sms_enabled', 1);
        return $this->db->count_all_results('user_notification_preferences');
    }
    
    /**
     * Bulk initialize preferences for existing users
     * Utility method for migration - creates default preferences for users who don't have them
     * 
     * @return array Result with count of initialized records
     */
    public function bulk_initialize_missing_preferences() {
        // Get all admin users who don't have preference records
        $this->db->select('admin.admin_id');
        $this->db->from('admin');
        $this->db->join('user_notification_preferences', 'admin.admin_id = user_notification_preferences.user_id', 'left');
        $this->db->where('user_notification_preferences.preference_id IS NULL');
        $this->db->where('admin.active_status', 1);
        
        $query = $this->db->get();
        $users_without_preferences = $query->result_array();
        
        $initialized_count = 0;
        
        foreach ($users_without_preferences as $user) {
            $preference_data = array(
                'user_id' => $user['admin_id'],
                'sms_enabled' => 0, // Default: SMS disabled
                'updated_at' => date('Y-m-d H:i:s')
            );
            
            if ($this->db->insert('user_notification_preferences', $preference_data)) {
                $initialized_count++;
            }
        }
        
        return array(
            'total_users_found' => count($users_without_preferences),
            'initialized_count' => $initialized_count
        );
    }
}
