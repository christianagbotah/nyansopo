<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Discount extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        
        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }

    public function index() {
        $page_data['page_name'] = 'discount_management';
        $page_data['page_title'] = 'Discount Management';
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    public function get_categories() {
        $categories = $this->db->where('is_active', 1)->get('discount_categories')->result();
        echo json_encode($categories);
    }

    public function get_types_by_category($category_id) {
        $types = $this->db->where('category_id', $category_id)->where('is_active', 1)->order_by('name', 'ASC')->get('discount_types')->result();
        echo json_encode($types);
    }

    public function get_students() {
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $students = $this->db->select('s.student_id, s.name, s.student_code')->from('student s')->join('enroll e', 's.student_id = e.student_id')->where('e.year', $running_year)->where('e.term', $running_term)->where('s.mute', 0)->order_by('s.name', 'ASC')->get()->result();
        echo json_encode($students);
    }

    public function create() {
        $student_id = $this->input->post('student_id');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        $current_user_id = $this->session->userdata('login_user_id');
        
        $user = $this->db->get_where('admin', ['admin_id' => $current_user_id])->row();
        $is_super_admin = ($user && $user->level == 1);
        
        $data = [
            'student_id' => $student_id,
            'discount_type' => $this->input->post('discount_type'),
            'discount_category' => $this->input->post('discount_category'),
            'discount_method' => $this->input->post('discount_method'),
            'discount_value' => $this->input->post('discount_value'),
            'discount_amount' => 0,
            'reason' => $this->input->post('reason'),
            'status' => $is_super_admin ? 'approved' : 'pending',
            'applied_by' => $current_user_id,
            'created_by' => $current_user_id,
            'created_at' => time(),
            'invoice_code' => ''
        ];
        
        if($is_super_admin) {
            $data['approved_by'] = $current_user_id;
            $data['approved_at'] = time();
        }
        
        $this->db->insert('invoice_discounts', $data);
        
        $message = $is_super_admin ? 'Discount created and approved' : 'Discount created, pending approval';
        echo json_encode(['status' => 'success', 'message' => $message]);
    }

    public function approve() {
        $discount_id = $this->input->post('discount_id');
        $current_user_id = $this->session->userdata('login_user_id');
        
        $user = $this->db->get_where('admin', ['admin_id' => $current_user_id])->row();
        if(!$user || $user->level != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Only super admin can approve']);
            return;
        }
        
        $this->db->where('discount_id', $discount_id)->update('invoice_discounts', ['status' => 'approved', 'approved_by' => $current_user_id, 'approved_at' => time()]);
        echo json_encode(['status' => 'success', 'message' => 'Discount approved']);
    }

    public function reject() {
        $discount_id = $this->input->post('discount_id');
        $rejection_reason = $this->input->post('rejection_reason');
        $current_user_id = $this->session->userdata('login_user_id');
        
        $user = $this->db->get_where('admin', ['admin_id' => $current_user_id])->row();
        if(!$user || $user->level != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Only super admin can reject']);
            return;
        }
        
        $this->db->where('discount_id', $discount_id)->update('invoice_discounts', ['status' => 'rejected', 'rejection_reason' => $rejection_reason, 'approved_by' => $current_user_id, 'approved_at' => time()]);
        echo json_encode(['status' => 'success', 'message' => 'Discount rejected']);
    }

    public function get_pending_discounts() {
        $current_user_id = $this->session->userdata('login_user_id');
        $user = $this->db->get_where('admin', ['admin_id' => $current_user_id])->row();
        
        if(!$user || $user->level != 1) {
            echo json_encode([]);
            return;
        }
        
        $discounts = $this->db->select('id.*, s.name as student_name, s.student_code, a.name as created_by_name')->from('invoice_discounts id')->join('student s', 'id.student_id = s.student_id')->join('admin a', 'id.created_by = a.admin_id')->where('id.status', 'pending')->order_by('id.created_at', 'DESC')->get()->result();
        echo json_encode($discounts);
    }

    public function unassign() {
        $discount_id = $this->input->post('discount_id');
        $current_user_id = $this->session->userdata('login_user_id');
        
        $user = $this->db->get_where('admin', ['admin_id' => $current_user_id])->row();
        $is_super_admin = ($user && $user->level == 1);
        
        if($is_super_admin) {
            $this->db->where('discount_id', $discount_id)->delete('invoice_discounts');
            echo json_encode(['status' => 'success', 'message' => 'Discount removed']);
        } else {
            $this->db->where('discount_id', $discount_id)->update('invoice_discounts', ['status' => 'pending_removal', 'created_at' => time()]);
            echo json_encode(['status' => 'success', 'message' => 'Removal request sent for approval']);
        }
    }

    public function profiles() {
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $page_data['profiles'] = $this->db->where('is_active', 1)->get('discount_profiles')->result_array();
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['page_name'] = 'discount_profiles';
        $page_data['page_title'] = 'Discount Profiles';
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    public function assign_students() {
        $page_data['profiles'] = $this->db->where('is_active', 1)->get('discount_profiles')->result_array();
        $page_data['running_year'] = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $page_data['running_term'] = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        $page_data['page_name'] = 'assign_student_discounts';
        $page_data['page_title'] = 'Assign Student Discounts';
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    public function do_assign() {
        $student_ids = $this->input->post('student_ids');
        $profile_ids = $this->input->post('profile_ids');
        $year = $this->input->post('year');
        $term = $this->input->post('term');
        $current_user_id = $this->session->userdata('login_user_id');
        
        $user = $this->db->get_where('admin', ['admin_id' => $current_user_id])->row();
        $is_super_admin = ($user && $user->level == 1);
        
        if(empty($student_ids) || empty($profile_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select students and profiles']);
            return;
        }
        
        // Validate profile_ids exist
        $this->db->where_in('profile_id', $profile_ids);
        $valid_profiles = $this->db->get('discount_profiles')->result_array();
        if(count($valid_profiles) != count($profile_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid profile selected']);
            return;
        }
        
        $assigned = 0;
        foreach($student_ids as $student_id) {
            foreach($profile_ids as $profile_id) {
                // Check if already assigned
                $exists = $this->db->where('student_id', $student_id)
                    ->where('profile_id', $profile_id)
                    ->where('year', $year)
                    ->where('term', $term)
                    ->where('is_active', 1)
                    ->get('student_discount_assignments')->row();
                
                if(!$exists) {
                    $assignment_data = [
                        'student_id' => $student_id,
                        'profile_id' => $profile_id,
                        'year' => $year,
                        'term' => $term,
                        'assigned_by' => $current_user_id,
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'is_active' => 1
                    ];
                    
                    if($is_super_admin) {
                        $assignment_data['status'] = 'approved';
                        $assignment_data['approved_by'] = $current_user_id;
                        $assignment_data['approved_at'] = date('Y-m-d H:i:s');
                    } else {
                        $assignment_data['status'] = 'pending';
                    }
                    
                    $this->db->insert('student_discount_assignments', $assignment_data);
                    $assigned++;
                }
            }
        }
        
        $message = $is_super_admin ? "$assigned discount(s) assigned and approved" : "$assigned discount(s) assigned, pending approval";
        echo json_encode(['status' => 'success', 'message' => $message]);
    }

    public function student_list() {
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['page_name'] = 'student_discount_list';
        $page_data['page_title'] = 'Students with Discounts';
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
}
