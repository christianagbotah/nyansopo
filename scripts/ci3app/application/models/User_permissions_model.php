<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User Permissions Model
 * 
 * Handles all database operations for user permissions management.
 * Provides methods for granting, revoking, checking, and querying permissions.
 * 
 * @package    School Manager
 * @subpackage Models
 * @category   Permissions
 * @author     System
 * @version    1.0
 */
class User_permissions_model extends CI_Model {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Grant permission to a user
     * 
     * @param int $user_id User ID
     * @param string $user_type User type (teacher, admin, accountant, etc.)
     * @param string $module_name Module name
     * @param string $permission_type Permission type (view, add, edit, delete)
     * @param int $granted_by Admin ID who is granting the permission
     * @param string $notes Optional notes
     * @return array ['success' => bool, 'message' => string, 'permission_id' => int]
     */
    public function grant_permission($user_id, $user_type, $module_name, $permission_type, $granted_by, $notes = '') {
        // Validate inputs
        if (empty($user_id) || empty($user_type) || empty($module_name) || empty($permission_type)) {
            return [
                'success' => false,
                'message' => 'Missing required parameters'
            ];
        }

        // Check if user exists
        if (!$this->user_exists($user_id, $user_type)) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Check if module exists and is active
        $module = $this->get_module($module_name);
        if (!$module || $module->is_active != 1) {
            return [
                'success' => false,
                'message' => 'Module not found or inactive'
            ];
        }

        // Check if permission type is valid for this module
        $available_permissions = explode(',', $module->available_permissions);
        if (!in_array($permission_type, $available_permissions)) {
            return [
                'success' => false,
                'message' => 'Invalid permission type for this module'
            ];
        }

        // Check if permission already exists and is active
        $existing = $this->check_permission($user_id, $user_type, $module_name, $permission_type);
        if ($existing) {
            return [
                'success' => false,
                'message' => 'User already has this permission'
            ];
        }

        // Check if there's a revoked permission we can reactivate
        $revoked_permission = $this->get_revoked_permission($user_id, $user_type, $module_name, $permission_type);
        
        if ($revoked_permission) {
            // Reactivate the revoked permission
            $data = [
                'status' => 'active',
                'granted_by' => $granted_by,
                'granted_at' => date('Y-m-d H:i:s'),
                'revoked_by' => NULL,
                'revoked_at' => NULL,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->where('permission_id', $revoked_permission->permission_id);
            $this->db->update('user_permissions', $data);
            
            return [
                'success' => true,
                'message' => 'Permission reactivated successfully',
                'permission_id' => $revoked_permission->permission_id
            ];
        }

        // Create new permission
        $data = [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'module_name' => $module_name,
            'permission_type' => $permission_type,
            'status' => 'active',
            'granted_by' => $granted_by,
            'granted_at' => date('Y-m-d H:i:s'),
            'notes' => $notes
        ];

        $this->db->insert('user_permissions', $data);
        $permission_id = $this->db->insert_id();

        return [
            'success' => true,
            'message' => 'Permission granted successfully',
            'permission_id' => $permission_id
        ];
    }

    /**
     * Revoke permission from a user
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $module_name Module name
     * @param string $permission_type Permission type
     * @param int $revoked_by Admin ID who is revoking the permission
     * @return array ['success' => bool, 'message' => string]
     */
    public function revoke_permission($user_id, $user_type, $module_name, $permission_type, $revoked_by) {
        // Check if permission exists and is active
        $permission = $this->db->get_where('user_permissions', [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'module_name' => $module_name,
            'permission_type' => $permission_type,
            'status' => 'active'
        ])->row();

        if (!$permission) {
            return [
                'success' => false,
                'message' => 'Active permission not found'
            ];
        }

        // Revoke the permission
        $data = [
            'status' => 'revoked',
            'revoked_by' => $revoked_by,
            'revoked_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('permission_id', $permission->permission_id);
        $this->db->update('user_permissions', $data);

        return [
            'success' => true,
            'message' => 'Permission revoked successfully'
        ];
    }

    /**
     * Check if user has a specific permission
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $module_name Module name
     * @param string $permission_type Permission type
     * @return bool TRUE if user has permission, FALSE otherwise
     */
    public function check_permission($user_id, $user_type, $module_name, $permission_type) {
        $query = $this->db->get_where('user_permissions', [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'module_name' => $module_name,
            'permission_type' => $permission_type,
            'status' => 'active'
        ]);

        return $query->num_rows() > 0;
    }

    /**
     * Get all permissions for a user
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param bool $active_only Whether to return only active permissions
     * @return array Array of permission records
     */
    public function get_user_permissions($user_id, $user_type, $active_only = true) {
        $this->db->select('up.*, pm.module_display_name, a1.name as granted_by_name, a2.name as revoked_by_name');
        $this->db->from('user_permissions up');
        $this->db->join('permission_modules pm', 'pm.module_name = up.module_name', 'left');
        $this->db->join('admin a1', 'a1.admin_id = up.granted_by', 'left');
        $this->db->join('admin a2', 'a2.admin_id = up.revoked_by', 'left');
        $this->db->where('up.user_id', $user_id);
        $this->db->where('up.user_type', $user_type);
        
        if ($active_only) {
            $this->db->where('up.status', 'active');
        }
        
        $this->db->order_by('up.module_name', 'ASC');
        $this->db->order_by('up.permission_type', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Get all users with permissions for a specific module
     * 
     * @param string $module_name Module name
     * @param bool $active_only Whether to return only active permissions
     * @return array Array of users with permissions
     */
    public function get_module_users($module_name, $active_only = true) {
        $this->db->select('up.*', FALSE);
        $this->db->select('CASE 
                            WHEN up.user_type = "teacher" THEN t.name
                            WHEN up.user_type = "admin" THEN a.name
                            WHEN up.user_type = "accountant" THEN acc.name
                            ELSE up.user_type
                          END as user_name', FALSE);
        $this->db->select('CASE 
                            WHEN up.user_type = "teacher" THEN t.email
                            WHEN up.user_type = "admin" THEN a.email
                            WHEN up.user_type = "accountant" THEN acc.email
                            ELSE NULL
                          END as user_email', FALSE);
        $this->db->from('user_permissions up');
        $this->db->join('teacher t', 't.teacher_id = up.user_id AND up.user_type = "teacher"', 'left');
        $this->db->join('admin a', 'a.admin_id = up.user_id AND up.user_type = "admin"', 'left');
        $this->db->join('accountant acc', 'acc.accountant_id = up.user_id AND up.user_type = "accountant"', 'left');
        $this->db->where('up.module_name', $module_name);
        
        if ($active_only) {
            $this->db->where('up.status', 'active');
        }
        
        $this->db->order_by('up.user_type', 'ASC');
        $this->db->order_by('user_name', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Bulk grant permissions to multiple users
     * 
     * @param array $users Array of ['user_id' => int, 'user_type' => string]
     * @param string $module_name Module name
     * @param array $permission_types Array of permission types
     * @param int $granted_by Admin ID
     * @return array ['success' => bool, 'granted' => int, 'failed' => int, 'messages' => array]
     */
    public function bulk_grant_permissions($users, $module_name, $permission_types, $granted_by) {
        $granted = 0;
        $failed = 0;
        $messages = [];

        foreach ($users as $user) {
            foreach ($permission_types as $permission_type) {
                $result = $this->grant_permission(
                    $user['user_id'],
                    $user['user_type'],
                    $module_name,
                    $permission_type,
                    $granted_by
                );

                if ($result['success']) {
                    $granted++;
                } else {
                    $failed++;
                    $messages[] = "User {$user['user_id']} ({$user['user_type']}): {$result['message']}";
                }
            }
        }

        return [
            'success' => $granted > 0,
            'granted' => $granted,
            'failed' => $failed,
            'messages' => $messages
        ];
    }

    /**
     * Bulk revoke permissions from multiple users
     * 
     * @param array $users Array of ['user_id' => int, 'user_type' => string]
     * @param string $module_name Module name
     * @param array $permission_types Array of permission types
     * @param int $revoked_by Admin ID
     * @return array ['success' => bool, 'revoked' => int, 'failed' => int, 'messages' => array]
     */
    public function bulk_revoke_permissions($users, $module_name, $permission_types, $revoked_by) {
        $revoked = 0;
        $failed = 0;
        $messages = [];

        foreach ($users as $user) {
            foreach ($permission_types as $permission_type) {
                $result = $this->revoke_permission(
                    $user['user_id'],
                    $user['user_type'],
                    $module_name,
                    $permission_type,
                    $revoked_by
                );

                if ($result['success']) {
                    $revoked++;
                } else {
                    $failed++;
                    $messages[] = "User {$user['user_id']} ({$user['user_type']}): {$result['message']}";
                }
            }
        }

        return [
            'success' => $revoked > 0,
            'revoked' => $revoked,
            'failed' => $failed,
            'messages' => $messages
        ];
    }

    /**
     * Get permission audit log
     * 
     * @param int|null $user_id User ID (optional)
     * @param string|null $user_type User type (optional)
     * @param string|null $module_name Module name (optional)
     * @param int $limit Maximum number of records
     * @return array Array of audit records
     */
    public function get_audit_log($user_id = null, $user_type = null, $module_name = null, $limit = 100) {
        $this->db->select('up.*', FALSE);
        $this->db->select('pm.module_display_name', FALSE);
        $this->db->select('CASE 
                            WHEN up.user_type = "teacher" THEN t.name
                            WHEN up.user_type = "admin" THEN a.name
                            WHEN up.user_type = "accountant" THEN acc.name
                            ELSE up.user_type
                          END as user_name', FALSE);
        $this->db->select('a1.name as granted_by_name', FALSE);
        $this->db->select('a2.name as revoked_by_name', FALSE);
        $this->db->from('user_permissions up');
        $this->db->join('permission_modules pm', 'pm.module_name = up.module_name', 'left');
        $this->db->join('teacher t', 't.teacher_id = up.user_id AND up.user_type = "teacher"', 'left');
        $this->db->join('admin a', 'a.admin_id = up.user_id AND up.user_type = "admin"', 'left');
        $this->db->join('accountant acc', 'acc.accountant_id = up.user_id AND up.user_type = "accountant"', 'left');
        $this->db->join('admin a1', 'a1.admin_id = up.granted_by', 'left');
        $this->db->join('admin a2', 'a2.admin_id = up.revoked_by', 'left');

        if ($user_id !== null) {
            $this->db->where('up.user_id', $user_id);
        }

        if ($user_type !== null) {
            $this->db->where('up.user_type', $user_type);
        }

        if ($module_name !== null) {
            $this->db->where('up.module_name', $module_name);
        }

        $this->db->order_by('up.granted_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Get all available modules
     * 
     * @param bool $active_only Whether to return only active modules
     * @return array Array of module records
     */
    public function get_all_modules($active_only = true) {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }

        $this->db->order_by('module_display_name', 'ASC');
        return $this->db->get('permission_modules')->result_array();
    }

    /**
     * Get module by name
     * 
     * @param string $module_name Module name
     * @return object|null Module record or NULL
     */
    public function get_module($module_name) {
        return $this->db->get_where('permission_modules', ['module_name' => $module_name])->row();
    }

    /**
     * Get permission statistics
     * 
     * @return array Statistics array
     */
    public function get_statistics() {
        // Total active permissions
        $total_active = $this->db->where('status', 'active')
            ->count_all_results('user_permissions');

        // Total revoked permissions
        $total_revoked = $this->db->where('status', 'revoked')
            ->count_all_results('user_permissions');

        // Permissions by module
        $this->db->select('module_name, COUNT(*) as count');
        $this->db->from('user_permissions');
        $this->db->where('status', 'active');
        $this->db->group_by('module_name');
        $by_module = $this->db->get()->result_array();

        // Permissions by user type
        $this->db->select('user_type, COUNT(*) as count');
        $this->db->from('user_permissions');
        $this->db->where('status', 'active');
        $this->db->group_by('user_type');
        $by_user_type = $this->db->get()->result_array();

        return [
            'total_active' => $total_active,
            'total_revoked' => $total_revoked,
            'by_module' => $by_module,
            'by_user_type' => $by_user_type
        ];
    }

    /**
     * Get users who can be granted permissions (teachers, specific admins, etc.)
     * 
     * @param string $user_type User type to filter
     * @return array Array of users
     */
    public function get_grantable_users($user_type = 'teacher') {
        switch ($user_type) {
            case 'teacher':
                $this->db->select('teacher_id as user_id, name, email, "teacher" as user_type');
                $this->db->from('teacher');
                $this->db->order_by('name', 'ASC');
                break;

            case 'admin':
                $this->db->select('admin_id as user_id, name, email, "admin" as user_type, level');
                $this->db->from('admin');
                $this->db->where_in('level', [2, 3, 4]); // Exclude super admin level 1
                $this->db->order_by('name', 'ASC');
                break;

            case 'accountant':
                $this->db->select('accountant_id as user_id, name, email, "accountant" as user_type');
                $this->db->from('accountant');
                $this->db->order_by('name', 'ASC');
                break;

            default:
                return [];
        }

        return $this->db->get()->result_array();
    }

    /**
     * Check if user exists in the specified user type table
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool TRUE if user exists
     */
    private function user_exists($user_id, $user_type) {
        $table_map = [
            'teacher' => 'teacher',
            'admin' => 'admin',
            'accountant' => 'accountant',
            'librarian' => 'librarian'
        ];

        if (!isset($table_map[$user_type])) {
            return false;
        }

        $table = $table_map[$user_type];
        $id_field = $user_type . '_id';

        $count = $this->db->where($id_field, $user_id)
            ->count_all_results($table);

        return $count > 0;
    }

    /**
     * Get revoked permission (for possible reactivation)
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $module_name Module name
     * @param string $permission_type Permission type
     * @return object|null Revoked permission or NULL
     */
    private function get_revoked_permission($user_id, $user_type, $module_name, $permission_type) {
        return $this->db->get_where('user_permissions', [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'module_name' => $module_name,
            'permission_type' => $permission_type,
            'status' => 'revoked'
        ])->row();
    }

    /**
     * Delete a permission permanently (use with caution)
     * 
     * @param int $permission_id Permission ID
     * @return bool TRUE on success
     */
    public function delete_permission($permission_id) {
        $this->db->where('permission_id', $permission_id);
        return $this->db->delete('user_permissions');
    }

    /**
     * Get user's permission summary grouped by module
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return array Permissions grouped by module
     */
    public function get_user_permission_summary($user_id, $user_type) {
        $permissions = $this->get_user_permissions($user_id, $user_type, true);
        
        $summary = [];
        foreach ($permissions as $perm) {
            $module = $perm['module_name'];
            if (!isset($summary[$module])) {
                $summary[$module] = [
                    'module_name' => $module,
                    'module_display_name' => $perm['module_display_name'],
                    'permissions' => []
                ];
            }
            $summary[$module]['permissions'][] = $perm['permission_type'];
        }

        return array_values($summary);
    }
}
