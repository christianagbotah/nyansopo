<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Portfolio_permission_middleware {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    public function check_access($subject_id, $class_id) {
        $login_type = $this->CI->session->userdata('login_type');
        $user_id = $this->CI->session->userdata('login_user_id');
        
        // Admin/Academic office - full access
        if($login_type == 'admin') {
            $admin_level = $this->CI->db->get_where('admin', ['admin_id' => $user_id])->row();
            if($admin_level && in_array($admin_level->level, [1, 2])) {
                return ['allowed' => true, 'role' => 'admin'];
            }
        }
        
        // Teacher - check subject assignment
        if($login_type == 'teacher') {
            // Subject teacher
            $subject_teacher = $this->CI->db->get_where('teacher_subject_assignment', [
                'teacher_id' => $user_id,
                'subject_id' => $subject_id,
                'class_id' => $class_id
            ])->row();
            
            if($subject_teacher) {
                return ['allowed' => true, 'role' => 'subject_teacher'];
            }
            
            // Class teacher
            $class_teacher = $this->CI->db->get_where('teacher_class_assignment', [
                'teacher_id' => $user_id,
                'class_id' => $class_id
            ])->row();
            
            if($class_teacher) {
                return ['allowed' => true, 'role' => 'class_teacher'];
            }
        }
        
        return ['allowed' => false, 'role' => null];
    }
}
