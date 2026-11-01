<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Permission_debug extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('User_permissions_model');
        $this->load->helper('permission');
    }
    
    public function check($teacher_id = null) {
        // Allow super admin only
        if ($this->session->userdata('admin_login') != 1 || !is_super_admin()) {
            die('Access denied. Super admin only.');
        }
        
        if (!$teacher_id) {
            echo "<h2>Permission Debug Tool</h2>";
            echo "<p>Usage: /permission_debug/check/{teacher_id}</p>";
            echo "<hr>";
            
            // List all teachers
            $teachers = $this->db->select('teacher_id, name, email')->from('teacher')->get()->result_array();
            echo "<h3>Available Teachers:</h3>";
            echo "<ul>";
            foreach ($teachers as $t) {
                echo "<li><a href='" . site_url('permission_debug/check/' . $t['teacher_id']) . "'>";
                echo "ID: {$t['teacher_id']} - {$t['name']} ({$t['email']})</a></li>";
            }
            echo "</ul>";
            return;
        }
        
        echo "<style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #4CAF50; color: white; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .success { color: green; font-weight: bold; }
            .error { color: red; font-weight: bold; }
            .warning { color: orange; font-weight: bold; }
            .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        </style>";
        
        echo "<h2>Permission Debug for Teacher ID: $teacher_id</h2>";
        echo "<p><a href='" . site_url('permission_debug/check') . "'>← Back to teacher list</a></p>";
        
        // Check if tables exist
        echo "<h3>1. Database Tables Check</h3>";
        $tables = ['user_permissions', 'permission_modules'];
        foreach ($tables as $table) {
            if ($this->db->table_exists($table)) {
                echo "<p class='success'>✓ Table '$table' exists</p>";
            } else {
                echo "<p class='error'>✗ Table '$table' does NOT exist. Run permissions_module_sql.sql</p>";
            }
        }
        
        // Check teacher exists
        echo "<h3>2. Teacher Information</h3>";
        $teacher = $this->db->get_where('teacher', ['teacher_id' => $teacher_id])->row();
        if ($teacher) {
            echo "<div class='info'>";
            echo "<p><strong>Name:</strong> {$teacher->name}</p>";
            echo "<p><strong>Email:</strong> {$teacher->email}</p>";
            echo "<p><strong>Teacher ID:</strong> {$teacher->teacher_id}</p>";
            echo "</div>";
        } else {
            echo "<p class='error'>✗ Teacher ID $teacher_id not found!</p>";
            return;
        }
        
        // Check available modules
        echo "<h3>3. Available Permission Modules</h3>";
        $modules = $this->db->get_where('permission_modules', ['is_active' => 1])->result_array();
        if (count($modules) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Module Name</th><th>Display Name</th><th>Available Permissions</th></tr>";
            foreach ($modules as $mod) {
                echo "<tr>";
                echo "<td>" . (isset($mod['id']) ? $mod['id'] : 'N/A') . "</td>";
                echo "<td>" . (isset($mod['module_name']) ? $mod['module_name'] : 'N/A') . "</td>";
                echo "<td>" . (isset($mod['display_name']) ? $mod['display_name'] : 'N/A') . "</td>";
                echo "<td>" . (isset($mod['available_permissions']) ? $mod['available_permissions'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No modules found. Run permissions_module_sql.sql</p>";
        }
        
        // Check permissions for this teacher
        echo "<h3>4. Granted Permissions</h3>";
        $perms = $this->db->get_where('user_permissions', [
            'user_id' => $teacher_id,
            'user_type' => 'teacher',
            'status' => 'active'
        ])->result_array();
        
        if (count($perms) > 0) {
            echo "<p class='success'>Found " . count($perms) . " active permission(s)</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Module</th><th>Permission Type</th><th>Status</th><th>Granted By</th><th>Date</th></tr>";
            foreach ($perms as $p) {
                $granter = $this->db->get_where('admin', ['admin_id' => $p['granted_by']])->row();
                $granter_name = $granter ? $granter->name : 'Unknown';
                
                echo "<tr>";
                echo "<td>" . (isset($p['permission_id']) ? $p['permission_id'] : (isset($p['id']) ? $p['id'] : 'N/A')) . "</td>";
                echo "<td>" . (isset($p['module_name']) ? $p['module_name'] : 'N/A') . "</td>";
                echo "<td>" . (isset($p['permission_type']) ? $p['permission_type'] : 'N/A') . "</td>";
                echo "<td>" . (isset($p['status']) ? $p['status'] : 'N/A') . "</td>";
                echo "<td>{$granter_name} (#{$p['granted_by']})</td>";
                echo "<td>" . (isset($p['granted_at']) ? $p['granted_at'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ NO ACTIVE PERMISSIONS found for this teacher</p>";
            echo "<p>Grant permissions at: <a href='" . site_url('user_permissions/manage_user/teacher/' . $teacher_id) . "'>User Permissions Manager</a></p>";
        }
        
        // Test permission helper functions
        echo "<h3>5. Permission Helper Function Tests</h3>";
        
        if (function_exists('user_has_permission')) {
            echo "<p class='success'>✓ user_has_permission() function exists</p>";
            
            $test_modules = ['head_teacher_remarks', 'teacher_remarks_templates'];
            $test_permissions = ['view', 'add', 'edit', 'delete', 'manage'];
            
            echo "<table>";
            echo "<tr><th>Module</th><th>Permission</th><th>Result</th></tr>";
            
            foreach ($test_modules as $module) {
                foreach ($test_permissions as $perm) {
                    $result = user_has_permission($teacher_id, 'teacher', $module, $perm);
                    $class = $result ? 'success' : 'error';
                    $text = $result ? '✓ GRANTED' : '✗ NOT GRANTED';
                    
                    echo "<tr>";
                    echo "<td>{$module}</td>";
                    echo "<td>{$perm}</td>";
                    echo "<td class='$class'>$text</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ user_has_permission() function NOT found. Helper not loaded?</p>";
        }
        
        // Direct model check
        echo "<h3>6. Direct Model Check</h3>";
        $direct_check_htr = $this->User_permissions_model->check_permission($teacher_id, 'teacher', 'head_teacher_remarks', 'view');
        $direct_check_trt = $this->User_permissions_model->check_permission($teacher_id, 'teacher', 'teacher_remarks_templates', 'view');
        
        echo "<p><strong>head_teacher_remarks (view):</strong> ";
        echo $direct_check_htr ? "<span class='success'>✓ GRANTED</span>" : "<span class='error'>✗ NOT GRANTED</span>";
        echo "</p>";
        
        echo "<p><strong>teacher_remarks_templates (view):</strong> ";
        echo $direct_check_trt ? "<span class='success'>✓ GRANTED</span>" : "<span class='error'>✗ NOT GRANTED</span>";
        echo "</p>";
        
        // Check what would appear in navigation
        echo "<h3>7. Navigation Display Test</h3>";
        echo "<div class='info'>";
        echo "<p><strong>Would 'Report Management' section appear?</strong> ";
        if ($direct_check_htr || $direct_check_trt) {
            echo "<span class='success'>YES</span></p>";
            
            if ($direct_check_htr) {
                echo "<p>✓ 'Head Teacher Remarks' menu item would show</p>";
            }
            if ($direct_check_trt) {
                echo "<p>✓ 'Teacher Remarks Templates' menu item would show</p>";
            }
        } else {
            echo "<span class='error'>NO - No permissions granted</span></p>";
        }
        echo "</div>";
        
        // Session check
        echo "<h3>8. Session Information</h3>";
        echo "<p><strong>Current logged in user:</strong> ";
        echo $this->session->userdata('login_type') . " (ID: " . $this->session->userdata('login_user_id') . ")</p>";
        
        if ($this->session->userdata('login_type') == 'teacher') {
            $session_teacher_id = $this->session->userdata('teacher_id');
            echo "<p><strong>Session teacher_id:</strong> $session_teacher_id</p>";
            
            if ($session_teacher_id == $teacher_id) {
                echo "<p class='success'>✓ This is YOUR account. Menu should show if permissions granted.</p>";
            } else {
                echo "<p class='warning'>⚠ You are checking a different teacher's permissions.</p>";
            }
        }
        
        // Raw SQL check
        echo "<h3>9. Raw Database Query</h3>";
        $sql = "SELECT * FROM user_permissions WHERE user_id = ? AND user_type = 'teacher' AND status = 'active'";
        $query = $this->db->query($sql, [$teacher_id]);
        echo "<p><strong>Query:</strong> <code>" . str_replace('?', $teacher_id, $sql) . "</code></p>";
        echo "<p><strong>Rows found:</strong> " . $query->num_rows() . "</p>";
        
        if ($query->num_rows() > 0) {
            echo "<pre>";
            print_r($query->result_array());
            echo "</pre>";
        }
    }
}
