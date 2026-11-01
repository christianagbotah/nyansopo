<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Permission Helper
 * 
 * Helper functions for checking user permissions throughout the application.
 * Provides convenient methods to validate user access to modules and actions.
 * 
 * @package    School Manager
 * @subpackage Helpers
 * @category   Permissions
 * @author     System
 * @version    1.0
 */

/**
 * Check if user has specific permission
 * 
 * Main permission checking function. Checks if a user has permission
 * to perform a specific action on a module. Checks both:
 * 1. Role-based permissions (user_permission table)
 * 2. Individual permissions (user_permissions table)
 * 
 * @param int $user_id User ID
 * @param string $user_type User type (teacher, admin, accountant, etc.)
 * @param string $module_name Module name
 * @param string $permission_type Permission type (view, add, edit, delete)
 * @return bool TRUE if user has permission, FALSE otherwise
 */
function user_has_permission($user_id, $user_type, $module_name, $permission_type) {
    $CI =& get_instance();
    
    // Validate inputs
    if (empty($user_id) || empty($user_type) || empty($module_name) || empty($permission_type)) {
        return false;
    }
    
    // Super admin (level 1) has all permissions
    if ($user_type === 'admin') {
        $admin = $CI->db->select('level')
            ->from('admin')
            ->where('admin_id', $user_id)
            ->get()
            ->row();
        
        if ($admin && $admin->level == 1) {
            return true;
        }
        
        // Check role-based permissions (existing user_permission table)
        // This checks if the user's level has this permission
        $user_level = $admin->level;
        $role_permission = check_role_based_permission($user_type, $user_level, $module_name, $permission_type);
        if ($role_permission) {
            return true;
        }
    }
    
    // Load model if not already loaded
    if (!isset($CI->user_permissions_model)) {
        $CI->load->model('User_permissions_model', 'user_permissions_model');
    }
    
    // Check individual permission in new user_permissions table
    return $CI->user_permissions_model->check_permission($user_id, $user_type, $module_name, $permission_type);
}

/**
 * Check role-based permission from existing user_permission table
 * 
 * Checks if a user's role (type and level) has a specific permission.
 * This integrates with the existing permission system.
 * 
 * @param string $user_type User type (admin, teacher, etc.)
 * @param int $user_level User level (for admins: 1, 2, 3, 4)
 * @param string $module_name Module name
 * @param string $permission_type Permission type
 * @return bool TRUE if role has permission
 */
function check_role_based_permission($user_type, $user_level, $module_name, $permission_type) {
    $CI =& get_instance();
    
    // Map module_name and permission_type to permission_title format
    // Example: "teacher_remarks_templates" + "add" = "Can add teacher remarks templates"
    $permission_titles = generate_permission_titles($module_name, $permission_type);
    
    // Check if any matching permission exists in user_permission table
    $CI->db->where('user_type', $user_type);
    $CI->db->where('user_level', $user_level);
    $CI->db->where('permission_status', 1); // Active permissions
    $CI->db->where_in('permission_title', $permission_titles);
    
    $result = $CI->db->get('user_permission');
    
    return $result->num_rows() > 0;
}

/**
 * Generate possible permission title variations
 * 
 * Converts module_name and permission_type to permission_title format
 * used in the existing user_permission table.
 * 
 * @param string $module_name Module name
 * @param string $permission_type Permission type
 * @return array Array of possible permission titles
 */
function generate_permission_titles($module_name, $permission_type) {
    // Convert module_name to readable format
    $module_readable = str_replace('_', ' ', $module_name);
    
    // Map permission types to action verbs
    $action_map = [
        'view' => ['view', 'access', 'see'],
        'add' => ['add', 'create', 'insert'],
        'edit' => ['edit', 'update', 'modify'],
        'delete' => ['delete', 'remove'],
        'manage' => ['manage', 'administer']
    ];
    
    $actions = isset($action_map[$permission_type]) ? $action_map[$permission_type] : [$permission_type];
    
    $titles = [];
    foreach ($actions as $action) {
        // Generate variations like:
        // "Can add teacher remarks templates"
        // "Can create teacher remarks templates"
        $titles[] = "Can $action $module_readable";
    }
    
    return $titles;
}

/**
 * Check if current logged-in user has permission
 * 
 * Convenience function that automatically gets user info from session.
 * 
 * @param string $module_name Module name
 * @param string $permission_type Permission type
 * @return bool TRUE if current user has permission
 */
function current_user_has_permission($module_name, $permission_type) {
    $CI =& get_instance();
    
    // Get current user info from session
    $login_type = $CI->session->userdata('login_type');
    $user_id = $CI->session->userdata('login_user_id');
    
    if (empty($login_type) || empty($user_id)) {
        return false;
    }
    
    return user_has_permission($user_id, $login_type, $module_name, $permission_type);
}

/**
 * Check if user can view a module
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @return bool TRUE if user can view
 */
function user_can_view($user_id, $user_type, $module_name) {
    return user_has_permission($user_id, $user_type, $module_name, 'view');
}

/**
 * Check if user can add to a module
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @return bool TRUE if user can add
 */
function user_can_add($user_id, $user_type, $module_name) {
    return user_has_permission($user_id, $user_type, $module_name, 'add');
}

/**
 * Check if user can edit in a module
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @return bool TRUE if user can edit
 */
function user_can_edit($user_id, $user_type, $module_name) {
    return user_has_permission($user_id, $user_type, $module_name, 'edit');
}

/**
 * Check if user can delete in a module
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @return bool TRUE if user can delete
 */
function user_can_delete($user_id, $user_type, $module_name) {
    return user_has_permission($user_id, $user_type, $module_name, 'delete');
}

/**
 * Check if user can manage a module (full access)
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @return bool TRUE if user can manage
 */
function user_can_manage($user_id, $user_type, $module_name) {
    return user_has_permission($user_id, $user_type, $module_name, 'manage');
}

/**
 * Check if current user can view a module
 * 
 * @param string $module_name Module name
 * @return bool TRUE if current user can view
 */
function can_view($module_name) {
    return current_user_has_permission($module_name, 'view');
}

/**
 * Check if current user can add to a module
 * 
 * @param string $module_name Module name
 * @return bool TRUE if current user can add
 */
function can_add($module_name) {
    return current_user_has_permission($module_name, 'add');
}

/**
 * Check if current user can edit in a module
 * 
 * @param string $module_name Module name
 * @return bool TRUE if current user can edit
 */
function can_edit($module_name) {
    return current_user_has_permission($module_name, 'edit');
}

/**
 * Check if current user can delete in a module
 * 
 * @param string $module_name Module name
 * @return bool TRUE if current user can delete
 */
function can_delete($module_name) {
    return current_user_has_permission($module_name, 'delete');
}

/**
 * Check if current user can manage a module
 * 
 * @param string $module_name Module name
 * @return bool TRUE if current user can manage
 */
function can_manage($module_name) {
    return current_user_has_permission($module_name, 'manage');
}

/**
 * Check if user has ANY of the specified permissions
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @param array $permission_types Array of permission types
 * @return bool TRUE if user has at least one permission
 */
function has_any_permission($user_id, $user_type, $module_name, $permission_types) {
    foreach ($permission_types as $permission_type) {
        if (user_has_permission($user_id, $user_type, $module_name, $permission_type)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has ALL of the specified permissions
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @param string $module_name Module name
 * @param array $permission_types Array of permission types
 * @return bool TRUE if user has all permissions
 */
function has_all_permissions($user_id, $user_type, $module_name, $permission_types) {
    foreach ($permission_types as $permission_type) {
        if (!user_has_permission($user_id, $user_type, $module_name, $permission_type)) {
            return false;
        }
    }
    return true;
}

/**
 * Get all permissions for a user
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @return array Array of permission records
 */
function get_user_permissions($user_id, $user_type) {
    $CI =& get_instance();
    
    if (!isset($CI->user_permissions_model)) {
        $CI->load->model('User_permissions_model', 'user_permissions_model');
    }
    
    return $CI->user_permissions_model->get_user_permissions($user_id, $user_type, true);
}

/**
 * Get modules that user has access to
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @return array Array of unique module names
 */
function get_user_modules($user_id, $user_type) {
    $permissions = get_user_permissions($user_id, $user_type);
    $modules = [];
    
    foreach ($permissions as $perm) {
        if (!in_array($perm['module_name'], $modules)) {
            $modules[] = $perm['module_name'];
        }
    }
    
    return $modules;
}

/**
 * Get user's permission summary grouped by module
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @return array Permissions grouped by module
 */
function get_user_permission_summary($user_id, $user_type) {
    $CI =& get_instance();
    
    if (!isset($CI->user_permissions_model)) {
        $CI->load->model('User_permissions_model', 'user_permissions_model');
    }
    
    return $CI->user_permissions_model->get_user_permission_summary($user_id, $user_type);
}

/**
 * Format permission type for display
 * 
 * Returns a formatted HTML badge for permission type.
 * 
 * @param string $permission_type Permission type
 * @return string HTML badge
 */
function format_permission_badge($permission_type) {
    $badges = [
        'view' => '<span class="badge badge-info">View</span>',
        'add' => '<span class="badge badge-success">Add</span>',
        'edit' => '<span class="badge badge-warning">Edit</span>',
        'delete' => '<span class="badge badge-danger">Delete</span>',
        'manage' => '<span class="badge badge-primary">Manage</span>',
        'approve' => '<span class="badge badge-success">Approve</span>',
        'monitor' => '<span class="badge badge-info">Monitor</span>'
    ];
    
    return isset($badges[$permission_type]) ? $badges[$permission_type] : 
           '<span class="badge badge-secondary">' . ucfirst($permission_type) . '</span>';
}

/**
 * Get permission icon
 * 
 * Returns an icon class for a permission type.
 * 
 * @param string $permission_type Permission type
 * @return string Icon class
 */
function get_permission_icon($permission_type) {
    $icons = [
        'view' => 'fa fa-eye',
        'add' => 'fa fa-plus',
        'edit' => 'fa fa-edit',
        'delete' => 'fa fa-trash',
        'manage' => 'fa fa-cog',
        'approve' => 'fa fa-check',
        'monitor' => 'fa fa-desktop'
    ];
    
    return isset($icons[$permission_type]) ? $icons[$permission_type] : 'fa fa-lock';
}

/**
 * Check module access and redirect if no permission
 * 
 * Use this at the top of controller methods to enforce permissions.
 * Redirects to dashboard with error message if permission is denied.
 * 
 * @param string $module_name Module name
 * @param string $permission_type Permission type
 * @param bool $ajax Whether this is an AJAX request
 * @return void
 */
function require_permission($module_name, $permission_type, $ajax = false) {
    $CI =& get_instance();
    
    // Check if user is logged in
    if (!$CI->session->userdata('login_type')) {
        if ($ajax) {
            echo json_encode([
                'success' => false,
                'message' => 'Please login to continue'
            ]);
            exit;
        } else {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    // Check permission
    if (!current_user_has_permission($module_name, $permission_type)) {
        if ($ajax) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to perform this action'
            ]);
            exit;
        } else {
            $CI->session->set_flashdata('error_message', 'You do not have permission to access this module');
            $login_type = $CI->session->userdata('login_type');
            redirect(site_url($login_type . '/dashboard'), 'refresh');
        }
    }
}

/**
 * Check if user is super admin
 * 
 * @param int|null $admin_id Admin ID (if null, uses current session)
 * @return bool TRUE if user is super admin (level 1)
 */
function is_super_admin($admin_id = null) {
    $CI =& get_instance();
    
    if ($admin_id === null) {
        // Get from session
        if ($CI->session->userdata('login_type') !== 'admin') {
            return false;
        }
        $admin_id = $CI->session->userdata('login_user_id');
    }
    
    $admin = $CI->db->select('level')
        ->from('admin')
        ->where('admin_id', $admin_id)
        ->get()
        ->row();
    
    return ($admin && $admin->level == 1);
}

/**
 * Check if user can grant permissions
 * 
 * Only super admins (level 1) can grant permissions.
 * 
 * @return bool TRUE if user can grant permissions
 */
function can_grant_permissions() {
    return is_super_admin();
}

/**
 * Get permission label
 * 
 * Returns a human-readable label for permission type.
 * 
 * @param string $permission_type Permission type
 * @return string Label
 */
function get_permission_label($permission_type) {
    $labels = [
        'view' => 'View/Access',
        'add' => 'Add/Create',
        'edit' => 'Edit/Update',
        'delete' => 'Delete/Remove',
        'manage' => 'Full Management',
        'approve' => 'Approve',
        'monitor' => 'Monitor'
    ];
    
    return isset($labels[$permission_type]) ? $labels[$permission_type] : ucfirst($permission_type);
}

/**
 * Get available permission types for a module
 * 
 * @param string $module_name Module name
 * @return array Array of permission types
 */
function get_module_permissions($module_name) {
    $CI =& get_instance();
    
    $module = $CI->db->select('available_permissions')
        ->from('permission_modules')
        ->where('module_name', $module_name)
        ->where('is_active', 1)
        ->get()
        ->row();
    
    if (!$module) {
        return ['view', 'add', 'edit', 'delete']; // Default permissions
    }
    
    return explode(',', $module->available_permissions);
}

/**
 * Check module access or admin access
 * 
 * Returns true if user is admin with proper level OR has the specific permission.
 * Useful for backward compatibility with existing admin-only checks.
 * 
 * @param string $module_name Module name
 * @param string $permission_type Permission type
 * @param int $required_admin_level Admin level required (1 or 2)
 * @return bool TRUE if user has access
 */
function has_admin_or_permission($module_name, $permission_type, $required_admin_level = 1) {
    $CI =& get_instance();
    
    // Check if user is admin with required level
    if ($CI->session->userdata('admin_login') == 1) {
        $admin_id = $CI->session->userdata('admin_id');
        $admin = $CI->db->select('level')
            ->from('admin')
            ->where('admin_id', $admin_id)
            ->get()
            ->row();
        
        if ($admin && $admin->level <= $required_admin_level) {
            return true;
        }
    }
    
    // Check if user has specific permission
    return current_user_has_permission($module_name, $permission_type);
}

/**
 * Get permission status badge
 * 
 * @param string $status Status (active, revoked)
 * @return string HTML badge
 */
function format_permission_status($status) {
    $badges = [
        'active' => '<span class="badge badge-success">Active</span>',
        'revoked' => '<span class="badge badge-danger">Revoked</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : 
           '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Log permission action (for audit trail)
 * 
 * @param string $action Action performed
 * @param int $user_id User ID affected
 * @param string $user_type User type
 * @param string $module_name Module name
 * @param string $permission_type Permission type
 * @param int $performed_by Admin who performed the action
 * @return bool TRUE on success
 */
function log_permission_action($action, $user_id, $user_type, $module_name, $permission_type, $performed_by) {
    $CI =& get_instance();
    
    // You can extend this to log to a separate audit table if needed
    // For now, the user_permissions table itself serves as an audit log
    
    return true;
}
