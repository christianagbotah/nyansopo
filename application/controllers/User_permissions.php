<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * User Permissions Controller
 * 
 * Controller for managing user permissions in the system.
 * Allows super admin to grant, revoke, and manage permissions for users.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Permissions
 * @author     System
 * @version    1.0
 */
class User_permissions extends MY_Controller {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->load->database();
        $this->load->library('session');
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Load required models and helpers
        $this->load->model('User_permissions_model');
        $this->load->helper('permission');
        $this->load->library('form_validation');
        
        // Only super admin (level 1) can access permission management
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->select('level')->from('admin')->where('admin_id', $admin_id)->get()->row();
        
        if (!$admin || $admin->level != 1) {
            $this->session->set_flashdata('error_message', 'Only super administrators can manage permissions');
            redirect(site_url('admin/dashboard'), 'refresh');
        }
        
        // Cache control
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    /**
     * Main index page - permissions management dashboard
     */
    public function index() {
        $page_data['page_name'] = 'user_permissions';
        $page_data['page_title'] = get_phrase('user_permissions_management');
        
        // Get all modules
        $page_data['modules'] = $this->User_permissions_model->get_all_modules(true);
        
        // Get statistics
        $page_data['statistics'] = $this->User_permissions_model->get_statistics();
        
        // Get recent activity (audit log)
        $page_data['recent_activity'] = $this->User_permissions_model->get_audit_log(null, null, null, 10);
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * Manage permissions for a specific user
     */
    public function manage_user($user_type = '', $user_id = '') {
        if (empty($user_type) || empty($user_id)) {
            $this->session->set_flashdata('error_message', 'Invalid user specified');
            redirect(site_url('user_permissions'), 'refresh');
        }
        
        $page_data['page_name'] = 'user_permissions_manage';
        $page_data['page_title'] = get_phrase('manage_user_permissions');
        $page_data['user_type'] = $user_type;
        $page_data['user_id'] = $user_id;
        
        // Get user info
        $page_data['user_info'] = $this->get_user_info($user_type, $user_id);
        
        if (!$page_data['user_info']) {
            $this->session->set_flashdata('error_message', 'User not found');
            redirect(site_url('user_permissions'), 'refresh');
        }
        
        // Get all modules
        $page_data['modules'] = $this->User_permissions_model->get_all_modules(true);
        
        // Get user's current permissions
        $page_data['user_permissions'] = $this->User_permissions_model->get_user_permissions($user_id, $user_type, true);
        
        // Create permission matrix
        $page_data['permission_matrix'] = $this->build_permission_matrix($page_data['modules'], $page_data['user_permissions']);
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * List users by type with their permissions
     */
    public function list_users($user_type = 'teacher') {
        $page_data['page_name'] = 'user_permissions_list';
        $page_data['page_title'] = get_phrase('users_permissions_list');
        $page_data['selected_user_type'] = $user_type;
        
        // Get users of specified type
        $page_data['users'] = $this->User_permissions_model->get_grantable_users($user_type);
        
        // Get permission summary for each user
        foreach ($page_data['users'] as &$user) {
            $user['permissions_count'] = count(
                $this->User_permissions_model->get_user_permissions($user['user_id'], $user_type, true)
            );
            $user['modules'] = $this->User_permissions_model->get_user_permission_summary($user['user_id'], $user_type);
        }
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * Module overview - shows all users with access to a module
     */
    public function module_overview($module_name = '') {
        if (empty($module_name)) {
            $this->session->set_flashdata('error_message', 'Invalid module specified');
            redirect(site_url('user_permissions'), 'refresh');
        }
        
        $page_data['page_name'] = 'user_permissions_module';
        $page_data['page_title'] = get_phrase('module_permissions_overview');
        $page_data['module_name'] = $module_name;
        
        // Get module info
        $page_data['module'] = $this->User_permissions_model->get_module($module_name);
        
        if (!$page_data['module']) {
            $this->session->set_flashdata('error_message', 'Module not found');
            redirect(site_url('user_permissions'), 'refresh');
        }
        
        // Get all users with permissions for this module
        $page_data['module_users'] = $this->User_permissions_model->get_module_users($module_name, true);
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * Grant permission (AJAX)
     */
    public function grant_permission() {
        $user_id = $this->input->post('user_id');
        $user_type = $this->input->post('user_type');
        $module_name = $this->input->post('module_name');
        $permission_type = $this->input->post('permission_type');
        $notes = $this->input->post('notes');
        
        $granted_by = $this->session->userdata('admin_id');
        
        $result = $this->User_permissions_model->grant_permission(
            $user_id,
            $user_type,
            $module_name,
            $permission_type,
            $granted_by,
            $notes
        );
        
        echo json_encode($result);
    }

    /**
     * Revoke permission (AJAX)
     */
    public function revoke_permission() {
        $user_id = $this->input->post('user_id');
        $user_type = $this->input->post('user_type');
        $module_name = $this->input->post('module_name');
        $permission_type = $this->input->post('permission_type');
        
        $revoked_by = $this->session->userdata('admin_id');
        
        $result = $this->User_permissions_model->revoke_permission(
            $user_id,
            $user_type,
            $module_name,
            $permission_type,
            $revoked_by
        );
        
        echo json_encode($result);
    }

    /**
     * Bulk grant permissions (AJAX)
     */
    public function bulk_grant() {
        $users = $this->input->post('users'); // Array of ['user_id' => x, 'user_type' => y]
        $module_name = $this->input->post('module_name');
        $permission_types = $this->input->post('permission_types'); // Array
        
        $granted_by = $this->session->userdata('admin_id');
        
        $result = $this->User_permissions_model->bulk_grant_permissions(
            $users,
            $module_name,
            $permission_types,
            $granted_by
        );
        
        echo json_encode($result);
    }

    /**
     * Bulk revoke permissions (AJAX)
     */
    public function bulk_revoke() {
        $users = $this->input->post('users'); // Array of ['user_id' => x, 'user_type' => y]
        $module_name = $this->input->post('module_name');
        $permission_types = $this->input->post('permission_types'); // Array
        
        $revoked_by = $this->session->userdata('admin_id');
        
        $result = $this->User_permissions_model->bulk_revoke_permissions(
            $users,
            $module_name,
            $permission_types,
            $revoked_by
        );
        
        echo json_encode($result);
    }

    /**
     * Grant all permissions for a module to a user (AJAX)
     */
    public function grant_all_module_permissions() {
        $user_id = $this->input->post('user_id');
        $user_type = $this->input->post('user_type');
        $module_name = $this->input->post('module_name');
        
        $granted_by = $this->session->userdata('admin_id');
        
        // Get available permissions for the module
        $module = $this->User_permissions_model->get_module($module_name);
        if (!$module) {
            echo json_encode([
                'success' => false,
                'message' => 'Module not found'
            ]);
            return;
        }
        
        $permission_types = explode(',', $module->available_permissions);
        $granted = 0;
        $failed = 0;
        
        foreach ($permission_types as $permission_type) {
            $result = $this->User_permissions_model->grant_permission(
                $user_id,
                $user_type,
                $module_name,
                trim($permission_type),
                $granted_by
            );
            
            if ($result['success']) {
                $granted++;
            } else {
                $failed++;
            }
        }
        
        echo json_encode([
            'success' => $granted > 0,
            'message' => "$granted permissions granted, $failed failed",
            'granted' => $granted,
            'failed' => $failed
        ]);
    }

    /**
     * Revoke all permissions for a module from a user (AJAX)
     */
    public function revoke_all_module_permissions() {
        $user_id = $this->input->post('user_id');
        $user_type = $this->input->post('user_type');
        $module_name = $this->input->post('module_name');
        
        $revoked_by = $this->session->userdata('admin_id');
        
        // Get user's current permissions for the module
        $user_permissions = $this->User_permissions_model->get_user_permissions($user_id, $user_type, true);
        
        $revoked = 0;
        $failed = 0;
        
        foreach ($user_permissions as $perm) {
            if ($perm['module_name'] === $module_name) {
                $result = $this->User_permissions_model->revoke_permission(
                    $user_id,
                    $user_type,
                    $module_name,
                    $perm['permission_type'],
                    $revoked_by
                );
                
                if ($result['success']) {
                    $revoked++;
                } else {
                    $failed++;
                }
            }
        }
        
        echo json_encode([
            'success' => $revoked > 0,
            'message' => "$revoked permissions revoked, $failed failed",
            'revoked' => $revoked,
            'failed' => $failed
        ]);
    }

    /**
     * Get user permissions (AJAX)
     */
    public function get_user_permissions() {
        $user_id = $this->input->get('user_id');
        $user_type = $this->input->get('user_type');
        $active_only = $this->input->get('active_only') !== 'false';
        
        $permissions = $this->User_permissions_model->get_user_permissions($user_id, $user_type, $active_only);
        
        echo json_encode([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Get grantable users by type (AJAX)
     */
    public function get_users_by_type() {
        $user_type = $this->input->get('user_type');
        
        $users = $this->User_permissions_model->get_grantable_users($user_type);
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get audit log (AJAX)
     */
    public function get_audit_log() {
        $user_id = $this->input->get('user_id');
        $user_type = $this->input->get('user_type');
        $module_name = $this->input->get('module_name');
        $limit = $this->input->get('limit') ?: 50;
        
        $audit_log = $this->User_permissions_model->get_audit_log($user_id, $user_type, $module_name, $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $audit_log
        ]);
    }

    /**
     * Get permission statistics (AJAX)
     */
    public function get_statistics() {
        $statistics = $this->User_permissions_model->get_statistics();
        
        echo json_encode([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Check if user has permission (AJAX)
     */
    public function check_permission() {
        $user_id = $this->input->get('user_id');
        $user_type = $this->input->get('user_type');
        $module_name = $this->input->get('module_name');
        $permission_type = $this->input->get('permission_type');
        
        $has_permission = $this->User_permissions_model->check_permission(
            $user_id,
            $user_type,
            $module_name,
            $permission_type
        );
        
        echo json_encode([
            'success' => true,
            'has_permission' => $has_permission
        ]);
    }

    /**
     * Search users for permission assignment (AJAX)
     */
    public function search_users() {
        $search_term = $this->input->get('q');
        $user_type = $this->input->get('user_type') ?: 'teacher';
        
        $users = $this->User_permissions_model->get_grantable_users($user_type);
        
        // Filter by search term
        if (!empty($search_term)) {
            $users = array_filter($users, function($user) use ($search_term) {
                return stripos($user['name'], $search_term) !== false || 
                       stripos($user['email'], $search_term) !== false;
            });
        }
        
        // Format for Select2
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user['user_id'],
                'text' => $user['name'] . ' (' . $user['email'] . ')',
                'user_type' => $user['user_type']
            ];
        }
        
        echo json_encode([
            'results' => $results
        ]);
    }

    /**
     * Export permissions report
     */
    public function export_report() {
        $format = $this->input->get('format') ?: 'csv';
        $user_type = $this->input->get('user_type');
        $module_name = $this->input->get('module_name');
        
        // Get audit log data
        $data = $this->User_permissions_model->get_audit_log(null, $user_type, $module_name, 1000);
        
        if ($format === 'csv') {
            $this->export_csv($data);
        } elseif ($format === 'json') {
            $this->export_json($data);
        }
    }

    /**
     * Helper: Get user info based on user type
     */
    private function get_user_info($user_type, $user_id) {
        $table_map = [
            'teacher' => ['table' => 'teacher', 'id_field' => 'teacher_id'],
            'admin' => ['table' => 'admin', 'id_field' => 'admin_id'],
            'accountant' => ['table' => 'accountant', 'id_field' => 'accountant_id'],
            'librarian' => ['table' => 'librarian', 'id_field' => 'librarian_id']
        ];
        
        if (!isset($table_map[$user_type])) {
            return null;
        }
        
        $config = $table_map[$user_type];
        $user = $this->db->select('*')
            ->from($config['table'])
            ->where($config['id_field'], $user_id)
            ->get()
            ->row_array();
        
        if ($user) {
            $user['user_type'] = $user_type;
            $user['user_id'] = $user_id;
        }
        
        return $user;
    }

    /**
     * Helper: Build permission matrix for UI
     */
    private function build_permission_matrix($modules, $user_permissions) {
        $matrix = [];
        
        // Create a lookup array for faster searching
        $perm_lookup = [];
        foreach ($user_permissions as $perm) {
            $key = $perm['module_name'] . '|' . $perm['permission_type'];
            $perm_lookup[$key] = true;
        }
        
        foreach ($modules as $module) {
            $available_perms = explode(',', $module['available_permissions']);
            $module_perms = [];
            
            foreach ($available_perms as $perm_type) {
                $perm_type = trim($perm_type);
                $key = $module['module_name'] . '|' . $perm_type;
                $module_perms[$perm_type] = isset($perm_lookup[$key]);
            }
            
            $matrix[$module['module_name']] = [
                'module' => $module,
                'permissions' => $module_perms
            ];
        }
        
        return $matrix;
    }

    /**
     * Helper: Export data as CSV
     */
    private function export_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="permissions_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['User', 'User Type', 'Module', 'Permission Type', 'Status', 'Granted By', 'Granted At', 'Revoked By', 'Revoked At']);
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, [
                $row['user_name'],
                $row['user_type'],
                $row['module_display_name'],
                $row['permission_type'],
                $row['status'],
                $row['granted_by_name'],
                $row['granted_at'],
                $row['revoked_by_name'],
                $row['revoked_at']
            ]);
        }
        
        fclose($output);
    }

    /**
     * Helper: Export data as JSON
     */
    private function export_json($data) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="permissions_report_' . date('Y-m-d') . '.json"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}
