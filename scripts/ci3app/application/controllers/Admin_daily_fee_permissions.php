<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Controller for Managing Daily Fee Collection Permissions
 * Allows admin to grant/revoke fee collection privileges to teachers
 */
class Admin_daily_fee_permissions extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Only admin and super_admin can manage permissions
        if ($this->session->userdata('admin_id') == '' || 
            !in_array($this->session->userdata('login_type'), ['admin', 'super_admin'])) {
            redirect(site_url('login'), 'refresh');
        }
    }

    /**
     * View page to manage teacher permissions
     */
    public function manage_teacher_permissions() {
        // Get all teachers
        $this->db->select('admin_id, name, email, can_collect_daily_fees');
        $this->db->where('level', 'teacher');
        $this->db->order_by('name', 'ASC');
        $teachers = $this->db->get('admin')->result_array();
        
        $page_data['teachers'] = $teachers;
        $page_data['page_name'] = 'manage_teacher_fee_permissions';
        $page_data['page_title'] = get_phrase('manage_teacher_fee_collection_permissions');
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * Grant fee collection permission to a teacher
     */
    public function grant_permission() {
        $teacher_id = $this->input->post('teacher_id');
        
        // Verify it's a teacher
        $teacher = $this->db->get_where('admin', ['admin_id' => $teacher_id, 'level' => 'teacher'])->row();
        
        if (!$teacher) {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('teacher_not_found')
            ]);
            return;
        }
        
        // Grant permission
        $this->db->where('admin_id', $teacher_id);
        $this->db->update('admin', [
            'can_collect_daily_fees' => 1,
            'collection_point' => 'classroom'
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('permission_granted_successfully')
        ]);
    }

    /**
     * Revoke fee collection permission from a teacher
     */
    public function revoke_permission() {
        $teacher_id = $this->input->post('teacher_id');
        
        // Verify it's a teacher
        $teacher = $this->db->get_where('admin', ['admin_id' => $teacher_id, 'level' => 'teacher'])->row();
        
        if (!$teacher) {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('teacher_not_found')
            ]);
            return;
        }
        
        // Revoke permission
        $this->db->where('admin_id', $teacher_id);
        $this->db->update('admin', [
            'can_collect_daily_fees' => 0
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('permission_revoked_successfully')
        ]);
    }

    /**
     * Bulk grant permissions to multiple teachers
     */
    public function bulk_grant_permissions() {
        $teacher_ids = $this->input->post('teacher_ids'); // Array of teacher IDs
        
        if (!is_array($teacher_ids) || empty($teacher_ids)) {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('no_teachers_selected')
            ]);
            return;
        }
        
        $this->db->where_in('admin_id', $teacher_ids);
        $this->db->where('level', 'teacher');
        $this->db->update('admin', [
            'can_collect_daily_fees' => 1,
            'collection_point' => 'classroom'
        ]);
        
        $affected = $this->db->affected_rows();
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('permissions_granted_to') . " $affected " . get_phrase('teachers')
        ]);
    }

    /**
     * Bulk revoke permissions from multiple teachers
     */
    public function bulk_revoke_permissions() {
        $teacher_ids = $this->input->post('teacher_ids'); // Array of teacher IDs
        
        if (!is_array($teacher_ids) || empty($teacher_ids)) {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('no_teachers_selected')
            ]);
            return;
        }
        
        $this->db->where_in('admin_id', $teacher_ids);
        $this->db->where('level', 'teacher');
        $this->db->update('admin', [
            'can_collect_daily_fees' => 0
        ]);
        
        $affected = $this->db->affected_rows();
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('permissions_revoked_from') . " $affected " . get_phrase('teachers')
        ]);
    }

    /**
     * Get teacher permission status
     */
    public function get_teacher_permission_status() {
        $teacher_id = $this->input->post('teacher_id');
        
        $teacher = $this->db->select('admin_id, name, email, can_collect_daily_fees, collection_point')
                           ->where('admin_id', $teacher_id)
                           ->where('level', 'teacher')
                           ->get('admin')
                           ->row_array();
        
        if (!$teacher) {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('teacher_not_found')
            ]);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'teacher' => $teacher,
            'has_permission' => $teacher['can_collect_daily_fees'] == 1
        ]);
    }
}
