<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Attendance Privilege Helper
 * 
 * Helper functions for managing teacher attendance monitoring privileges
 * 
 * @package    School Manager
 * @subpackage Helpers
 * @category   Attendance
 * @author     System
 * @version    1.0
 */

/**
 * Check if user can access attendance monitoring
 * 
 * This function determines if a user (admin or teacher) has permission
 * to access the school-wide attendance monitoring page.
 * 
 * @param string $user_type - 'admin' or 'teacher'
 * @param int $user_id - User ID
 * @return bool TRUE if user has access, FALSE otherwise
 */
function can_access_attendance_monitoring($user_type, $user_id) {
    $CI =& get_instance();
    
    // Validate inputs
    if (empty($user_type) || empty($user_id)) {
        return false;
    }
    
    // Admin level 1 and 2 always have access
    if ($user_type === 'admin') {
        $admin = $CI->db->select('level')
            ->from('admin')
            ->where('admin_id', $user_id)
            ->get()
            ->row();
        
        if ($admin && ($admin->level == 1 || $admin->level == 2)) {
            return true;
        }
    }
    
    // Check teacher privilege
    if ($user_type === 'teacher') {
        return has_teacher_privilege($user_id, 'attendance_monitoring');
    }
    
    return false;
}

/**
 * Check if teacher has specific privilege
 * 
 * Checks if a teacher has an active privilege of the specified type.
 * 
 * @param int $teacher_id - Teacher ID
 * @param string $privilege_type - Type of privilege (e.g., 'attendance_monitoring')
 * @return bool TRUE if teacher has active privilege, FALSE otherwise
 */
function has_teacher_privilege($teacher_id, $privilege_type) {
    $CI =& get_instance();
    
    // Validate inputs
    if (empty($teacher_id) || empty($privilege_type)) {
        return false;
    }
    
    // Check for active privilege
    $privilege = $CI->db->select('id')
        ->from('teacher_privileges')
        ->where('teacher_id', $teacher_id)
        ->where('privilege_type', $privilege_type)
        ->where('status', 'active')
        ->get()
        ->row();
    
    return !empty($privilege);
}

/**
 * Get all privileges for a teacher
 * 
 * Retrieves all active privileges for a specific teacher with details
 * about who granted them and when.
 * 
 * @param int $teacher_id - Teacher ID
 * @return array Array of privilege records with granter information
 */
function get_teacher_privileges($teacher_id) {
    $CI =& get_instance();
    
    // Validate input
    if (empty($teacher_id)) {
        return [];
    }
    
    // Get all active privileges with granter details
    $privileges = $CI->db->select('tp.*, a.name as granted_by_name, a.email as granted_by_email')
        ->from('teacher_privileges tp')
        ->join('admin a', 'a.admin_id = tp.granted_by', 'left')
        ->where('tp.teacher_id', $teacher_id)
        ->where('tp.status', 'active')
        ->order_by('tp.granted_at', 'DESC')
        ->get()
        ->result_array();
    
    return $privileges;
}

/**
 * Get privilege details by ID
 * 
 * Retrieves detailed information about a specific privilege record.
 * 
 * @param int $privilege_id - Privilege ID
 * @return object|null Privilege record or NULL if not found
 */
function get_privilege_details($privilege_id) {
    $CI =& get_instance();
    
    // Validate input
    if (empty($privilege_id)) {
        return null;
    }
    
    // Get privilege with granter and revoker details
    $privilege = $CI->db->select('tp.*, 
                                   a1.name as granted_by_name, 
                                   a1.email as granted_by_email,
                                   a2.name as revoked_by_name,
                                   a2.email as revoked_by_email,
                                   t.name as teacher_name,
                                   t.email as teacher_email')
        ->from('teacher_privileges tp')
        ->join('admin a1', 'a1.admin_id = tp.granted_by', 'left')
        ->join('admin a2', 'a2.admin_id = tp.revoked_by', 'left')
        ->join('teacher t', 't.teacher_id = tp.teacher_id', 'left')
        ->where('tp.id', $privilege_id)
        ->get()
        ->row();
    
    return $privilege;
}

/**
 * Get all teachers with their privilege status
 * 
 * Retrieves a list of all teachers with information about whether they
 * have the specified privilege type.
 * 
 * @param string $privilege_type - Type of privilege to check (default: 'attendance_monitoring')
 * @return array Array of teachers with privilege status
 */
function get_teachers_with_privilege_status($privilege_type = 'attendance_monitoring') {
    $CI =& get_instance();
    
    // Get all teachers with teacher_code
    $teachers = $CI->db->select('teacher_id, name, email, phone, teacher_code')
        ->from('teacher')
        ->order_by('name', 'ASC')
        ->get()
        ->result_array();
    
    // Add privilege status to each teacher
    foreach ($teachers as &$teacher) {
        $privilege = $CI->db->select('tp.id, tp.granted_at, tp.granted_by, tp.notes, a.name as granted_by_name')
            ->from('teacher_privileges tp')
            ->join('admin a', 'a.admin_id = tp.granted_by', 'left')
            ->where('tp.teacher_id', $teacher['teacher_id'])
            ->where('tp.privilege_type', $privilege_type)
            ->where('tp.status', 'active')
            ->get()
            ->row();
        
        $teacher['has_privilege'] = !empty($privilege);
        $teacher['privilege_id'] = $privilege ? $privilege->id : null;
        $teacher['privilege_granted_at'] = $privilege ? $privilege->granted_at : null;
        $teacher['privilege_granted_by'] = $privilege ? $privilege->granted_by : null;
        $teacher['granted_by_name'] = $privilege ? $privilege->granted_by_name : null;
        $teacher['privilege_notes'] = $privilege ? $privilege->notes : null;
    }
    
    return $teachers;
}

/**
 * Get privilege audit log
 * 
 * Retrieves the complete history of privilege grants and revocations
 * for a specific teacher or all teachers.
 * 
 * @param int|null $teacher_id - Teacher ID (optional, null for all teachers)
 * @param int $limit - Maximum number of records to return (default: 100)
 * @return array Array of privilege history records
 */
function get_privilege_audit_log($teacher_id = null, $limit = 100) {
    $CI =& get_instance();
    
    $CI->db->select('tp.*, 
                     t.name as teacher_name,
                     t.email as teacher_email,
                     a1.name as granted_by_name,
                     a2.name as revoked_by_name')
        ->from('teacher_privileges tp')
        ->join('teacher t', 't.teacher_id = tp.teacher_id', 'left')
        ->join('admin a1', 'a1.admin_id = tp.granted_by', 'left')
        ->join('admin a2', 'a2.admin_id = tp.revoked_by', 'left');
    
    if ($teacher_id !== null) {
        $CI->db->where('tp.teacher_id', $teacher_id);
    }
    
    $audit_log = $CI->db->order_by('tp.granted_at', 'DESC')
        ->limit($limit)
        ->get()
        ->result_array();
    
    return $audit_log;
}

/**
 * Check if privilege can be granted
 * 
 * Validates if a privilege can be granted to a teacher.
 * Checks for existing active privileges to prevent duplicates.
 * 
 * @param int $teacher_id - Teacher ID
 * @param string $privilege_type - Type of privilege
 * @return array ['can_grant' => bool, 'message' => string]
 */
function can_grant_privilege($teacher_id, $privilege_type) {
    $CI =& get_instance();
    
    // Check if teacher exists
    $teacher = $CI->db->select('teacher_id')
        ->from('teacher')
        ->where('teacher_id', $teacher_id)
        ->get()
        ->row();
    
    if (!$teacher) {
        return [
            'can_grant' => false,
            'message' => 'Teacher not found'
        ];
    }
    
    // Check for existing active privilege
    $existing = $CI->db->select('id')
        ->from('teacher_privileges')
        ->where('teacher_id', $teacher_id)
        ->where('privilege_type', $privilege_type)
        ->where('status', 'active')
        ->get()
        ->row();
    
    if ($existing) {
        return [
            'can_grant' => false,
            'message' => 'Teacher already has this privilege'
        ];
    }
    
    return [
        'can_grant' => true,
        'message' => 'Privilege can be granted'
    ];
}

/**
 * Format privilege status for display
 * 
 * Returns a formatted HTML badge for privilege status.
 * 
 * @param string $status - 'active' or 'revoked'
 * @return string HTML badge
 */
function format_privilege_status($status) {
    $badges = [
        'active' => '<span class="badge badge-success">Active</span>',
        'revoked' => '<span class="badge badge-danger">Revoked</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * Get privilege statistics
 * 
 * Returns statistics about privilege usage.
 * 
 * @param string $privilege_type - Type of privilege (default: 'attendance_monitoring')
 * @return array Statistics array
 */
function get_privilege_statistics($privilege_type = 'attendance_monitoring') {
    $CI =& get_instance();
    
    // Total teachers
    $total_teachers = $CI->db->count_all('teacher');
    
    // Teachers with active privilege
    $active_privileges = $CI->db->where('privilege_type', $privilege_type)
        ->where('status', 'active')
        ->count_all_results('teacher_privileges');
    
    // Total privileges granted (including revoked)
    $total_granted = $CI->db->where('privilege_type', $privilege_type)
        ->count_all_results('teacher_privileges');
    
    // Revoked privileges
    $revoked_privileges = $CI->db->where('privilege_type', $privilege_type)
        ->where('status', 'revoked')
        ->count_all_results('teacher_privileges');
    
    return [
        'total_teachers' => $total_teachers,
        'active_privileges' => $active_privileges,
        'total_granted' => $total_granted,
        'revoked_privileges' => $revoked_privileges,
        'percentage_with_privilege' => $total_teachers > 0 ? round(($active_privileges / $total_teachers) * 100, 2) : 0
    ];
}
