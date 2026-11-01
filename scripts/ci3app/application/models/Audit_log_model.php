<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit Log Model
 * 
 * Provides immutable audit trail functionality for tracking all payroll operations
 * and other critical system actions. This model captures who did what, when, and
 * stores before/after data for compliance and investigation purposes.
 * 
 * Note: This model extends CI_Model directly (not MY_Model) to prevent sync
 * operations on audit logs.
 * 
 * @package    SchoolManager
 * @subpackage Models
 * @category   Audit
 * @author     School Manager Dev Team
 */
class Audit_log_model extends CI_Model
{
    /**
     * Table name
     * 
     * @var string
     */
    protected $table = 'audit_logs';
    
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Log an action
     * 
     * Creates an immutable audit log entry capturing the user, action, timing,
     * and before/after data for compliance and investigation purposes.
     * 
     * @param string $module Module name (e.g., 'payroll', 'attendance', 'finance')
     * @param string $action Action performed (e.g., 'create', 'update', 'delete', 'approve')
     * @param int $record_id ID of the affected record
     * @param mixed $before_data Data before the action (array or object)
     * @param mixed $after_data Data after the action (array or object)
     * @return bool True on success, false on failure
     */
    public function log_action($module, $action, $record_id, $before_data = null, $after_data = null)
    {
        try {
            // Get user information from session
            $user_id = $this->session->userdata('admin_id') 
                      ?: $this->session->userdata('teacher_id') 
                      ?: $this->session->userdata('accountant_id')
                      ?: null;
            
            // Capture IP address
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
            
            // Capture user agent
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN';
            
            // Serialize data as JSON
            $before_json = $before_data ? json_encode($before_data) : null;
            $after_json = $after_data ? json_encode($after_data) : null;
            
            // Prepare log data
            $log_data = [
                'user_id' => $user_id,
                'module' => $module,
                'action' => $action,
                'record_id' => $record_id,
                'before_data' => $before_json,
                'after_data' => $after_json,
                'ip_address' => $ip_address,
                'user_agent' => substr($user_agent, 0, 255), // Truncate to fit VARCHAR(255)
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert log entry
            $this->db->insert($this->table, $log_data);
            
            return $this->db->affected_rows() > 0;
            
        } catch (Exception $e) {
            // Log error but don't break the main operation
            log_message('error', 'Audit log failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get logs by module
     * 
     * Retrieves audit logs filtered by module name with pagination support.
     * Optimized to use idx_module_created composite index for better performance.
     * 
     * @param string $module Module name to filter by
     * @param int $limit Maximum number of records to return (default: 100)
     * @param int $offset Starting offset for pagination (default: 0)
     * @param string $date_from Optional start date for filtering (Y-m-d format)
     * @param string $date_to Optional end date for filtering (Y-m-d format)
     * @return array Array of audit log records
     */
    public function get_logs_by_module($module, $limit = 100, $offset = 0, $date_from = null, $date_to = null)
    {
        // Select only necessary columns (supports covering index usage)
        $this->db->select('al.log_id, al.user_id, al.module, al.action, al.record_id, 
                          al.ip_address, al.created_at, 
                          COALESCE(a.name, t.name, acc.name) as user_name', false);
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        $this->db->where('al.module', $module);
        
        // OPTIMIZATION: Use direct datetime comparison instead of DATE() function
        // This allows the idx_module_created composite index to be fully utilized
        if ($date_from) {
            $this->db->where('al.created_at >=', $date_from . ' 00:00:00');
        }
        if ($date_to) {
            $this->db->where('al.created_at <=', $date_to . ' 23:59:59');
        }
        
        $this->db->order_by('al.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result();
    }
    
    /**
     * Get logs by record
     * 
     * Retrieves the complete chronological history of changes for a specific record.
     * 
     * @param string $module Module name
     * @param int $record_id Record ID
     * @return array Array of audit log records in chronological order
     */
    public function get_logs_by_record($module, $record_id)
    {
        $this->db->select('al.*, COALESCE(a.name, t.name, acc.name) as user_name');
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        $this->db->where('al.module', $module);
        $this->db->where('al.record_id', $record_id);
        $this->db->order_by('al.created_at', 'ASC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get user activity
     * 
     * Retrieves a user's action history within a specified date range.
     * Optimized to use idx_user_created composite index.
     * 
     * @param int $user_id User ID
     * @param string $start_date Start date (Y-m-d format, default: 30 days ago)
     * @param string $end_date End date (Y-m-d format, default: today)
     * @param int $limit Maximum number of records (default: 100)
     * @return array Array of audit log records
     */
    public function get_user_activity($user_id, $start_date = null, $end_date = null, $limit = 100)
    {
        // Default date range: last 30 days
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        // Select only necessary columns for better performance
        $this->db->select('al.log_id, al.module, al.action, al.record_id, 
                          al.ip_address, al.created_at');
        $this->db->from($this->table . ' al');
        $this->db->where('al.user_id', $user_id);
        
        // OPTIMIZATION: Use direct datetime comparison for index usage
        $this->db->where('al.created_at >=', $start_date . ' 00:00:00');
        $this->db->where('al.created_at <=', $end_date . ' 23:59:59');
        
        $this->db->order_by('al.created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }
    
    /**
     * Get recent logs
     * 
     * Retrieves the most recent audit logs across all modules.
     * 
     * @param int $limit Maximum number of records (default: 50)
     * @return array Array of recent audit log records
     */
    public function get_recent_logs($limit = 50)
    {
        $this->db->select('al.*, COALESCE(a.name, t.name, acc.name) as user_name');
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        $this->db->order_by('al.created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }
    
    /**
     * Count logs by module
     * 
     * Returns the total number of audit log entries for a specific module.
     * 
     * @param string $module Module name
     * @return int Total count
     */
    public function count_by_module($module)
    {
        $this->db->where('module', $module);
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * Count logs by user
     * 
     * Returns the total number of actions performed by a user within a date range.
     * 
     * @param int $user_id User ID
     * @param string $start_date Start date (optional)
     * @param string $end_date End date (optional)
     * @return int Total count
     */
    public function count_by_user($user_id, $start_date = null, $end_date = null)
    {
        $this->db->where('user_id', $user_id);
        
        // Task 17.4: OPTIMIZATION - Use direct datetime comparison for index usage
        if ($start_date) {
            $this->db->where('created_at >=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $this->db->where('created_at <=', $end_date . ' 23:59:59');
        }
        
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * Search logs
     * 
     * Performs a flexible search across audit logs with multiple filter options.
     * Task 17.4: Optimized to efficiently use composite indexes.
     * 
     * @param array $filters Associative array of filter criteria
     *   - module: Module name
     *   - action: Action type
     *   - user_id: User ID
     *   - record_id: Record ID
     *   - start_date: Start date (Y-m-d format)
     *   - end_date: End date (Y-m-d format)
     *   - ip_address: IP address
     * @param int $limit Maximum records (default: 100)
     * @param int $offset Pagination offset (default: 0)
     * @return array Array of matching audit log records
     */
    public function search_logs($filters = [], $limit = 100, $offset = 0)
    {
        $this->db->select('al.*, COALESCE(a.name, t.name, acc.name) as user_name');
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        
        // Task 17.4: Apply filters in order to maximize index usage
        // idx_module_action will be used if module is specified
        if (!empty($filters['module'])) {
            $this->db->where('al.module', $filters['module']);
            if (!empty($filters['action'])) {
                $this->db->where('al.action', $filters['action']);
            }
        }
        
        // idx_user_id_created will be used if user_id is specified
        if (!empty($filters['user_id'])) {
            $this->db->where('al.user_id', $filters['user_id']);
        }
        
        if (!empty($filters['record_id'])) {
            $this->db->where('al.record_id', $filters['record_id']);
        }
        
        // Task 17.4: Use direct datetime comparison instead of DATE() function
        // This allows index usage on created_at column
        if (!empty($filters['start_date'])) {
            $this->db->where('al.created_at >=', $filters['start_date'] . ' 00:00:00');
        }
        if (!empty($filters['end_date'])) {
            $this->db->where('al.created_at <=', $filters['end_date'] . ' 23:59:59');
        }
        
        if (!empty($filters['ip_address'])) {
            $this->db->where('al.ip_address', $filters['ip_address']);
        }
        
        $this->db->order_by('al.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result();
    }
    
    /**
     * Get action statistics
     * 
     * Returns statistics about actions performed within a date range.
     * Task 17.4: Optimized with proper date range filtering for index usage.
     * 
     * @param string $start_date Start date (Y-m-d format, default: 30 days ago)
     * @param string $end_date End date (Y-m-d format, default: today)
     * @return array Array with action counts grouped by module and action
     */
    public function get_action_statistics($start_date = null, $end_date = null)
    {
        // Default date range: last 30 days
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        // Task 17.4: Use direct datetime comparison for index usage
        $this->db->select('module, action, COUNT(*) as count');
        $this->db->from($this->table);
        $this->db->where('created_at >=', $start_date . ' 00:00:00');
        $this->db->where('created_at <=', $end_date . ' 23:59:59');
        $this->db->group_by(['module', 'action']);
        $this->db->order_by('count', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get filtered logs (for Task 13.2)
     * 
     * Get logs with filters and pagination for DataTables.
     * Task 17.4: Optimized query structure for composite index usage.
     * 
     * @param string $module Module filter
     * @param int $user_id User ID filter
     * @param string $date_from Date from filter (Y-m-d format)
     * @param string $date_to Date to filter (Y-m-d format)
     * @param int $start Pagination start
     * @param int $length Pagination length
     * @param string $search Search term
     * @return array Array of audit log records
     */
    public function get_logs_filtered($module = null, $user_id = null, $date_from = null, $date_to = null, $start = 0, $length = 50, $search = '')
    {
        $this->db->select('al.*, COALESCE(a.name, t.name, acc.name) as user_name');
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        
        // Task 17.4: Apply filters in optimal order for index usage
        if (!empty($module)) {
            $this->db->where('al.module', $module);
        }
        if (!empty($user_id)) {
            $this->db->where('al.user_id', $user_id);
        }
        
        // Task 17.4: Use direct datetime comparison instead of DATE() function
        // This allows the created_at index to be fully utilized
        if (!empty($date_from)) {
            $this->db->where('al.created_at >=', $date_from . ' 00:00:00');
        }
        if (!empty($date_to)) {
            $this->db->where('al.created_at <=', $date_to . ' 23:59:59');
        }
        
        // Apply search
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('al.module', $search);
            $this->db->or_like('al.action', $search);
            $this->db->or_like('al.record_id', $search);
            $this->db->or_like('al.ip_address', $search);
            $this->db->or_like('COALESCE(a.name, t.name, acc.name)', $search);
            $this->db->group_end();
        }
        
        $this->db->order_by('al.created_at', 'DESC');
        $this->db->limit($length, $start);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get count of filtered logs (for Task 13.2)
     * 
     * Task 17.4: Optimized with direct datetime comparison for index usage.
     * 
     * @param string $module Module filter
     * @param int $user_id User ID filter
     * @param string $date_from Date from filter (Y-m-d format)
     * @param string $date_to Date to filter (Y-m-d format)
     * @param string $search Search term
     * @return int Total count
     */
    public function get_logs_count($module = null, $user_id = null, $date_from = null, $date_to = null, $search = '')
    {
        $this->db->select('COUNT(*) as count');
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        
        // Task 17.4: Apply filters in optimal order
        if (!empty($module)) {
            $this->db->where('al.module', $module);
        }
        if (!empty($user_id)) {
            $this->db->where('al.user_id', $user_id);
        }
        
        // Task 17.4: Use direct datetime comparison for index usage
        if (!empty($date_from)) {
            $this->db->where('al.created_at >=', $date_from . ' 00:00:00');
        }
        if (!empty($date_to)) {
            $this->db->where('al.created_at <=', $date_to . ' 23:59:59');
        }
        
        // Apply search
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('al.module', $search);
            $this->db->or_like('al.action', $search);
            $this->db->or_like('al.record_id', $search);
            $this->db->or_like('al.ip_address', $search);
            $this->db->or_like('COALESCE(a.name, t.name, acc.name)', $search);
            $this->db->group_end();
        }
        
        $result = $this->db->get()->row();
        return $result ? $result->count : 0;
    }
    
    /**
     * Get log by ID (for Task 13.4)
     * 
     * @param int $log_id Log ID
     * @return array|null Log data or null if not found
     */
    public function get_log_by_id($log_id)
    {
        $this->db->select('al.*, COALESCE(a.name, t.name, acc.name) as user_name');
        $this->db->from($this->table . ' al');
        $this->db->join('admin a', 'al.user_id = a.admin_id', 'left');
        $this->db->join('teacher t', 'al.user_id = t.teacher_id', 'left');
        $this->db->join('accountant acc', 'al.user_id = acc.accountant_id', 'left');
        $this->db->where('al.log_id', $log_id);
        
        return $this->db->get()->row_array();
    }
    
    /**
     * Get all unique modules (for filter dropdown)
     * 
     * @return array Array of unique module names
     */
    public function get_all_modules()
    {
        $this->db->distinct();
        $this->db->select('module');
        $this->db->from($this->table);
        $this->db->order_by('module', 'ASC');
        
        $results = $this->db->get()->result_array();
        return array_column($results, 'module');
    }
}
