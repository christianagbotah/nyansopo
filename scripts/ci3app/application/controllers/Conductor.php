<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conductor extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    /**
     * Mark boarding page for conductors (afternoon OUT boarding)
     */
    public function mark_boarding() {
        $user_id = $this->session->userdata('login_user_id');
        $user_type = $this->session->userdata('login_type');
        
        // Verify conductor access
        if ($user_type == 'admin') {
            $user = $this->db->get_where('admin', ['admin_id' => $user_id])->row();
            if (!$user || $user->level != 5) {
                show_error('Access denied. Only conductors can access this page.');
                return;
            }
            $page_data['conductor'] = $user;
        } else {
            show_error('Access denied.');
            return;
        }
        
        // Get routes
        $page_data['routes'] = $this->db->get('transport_routes')->result_array();
        $page_data['page_name'] = 'conductor/mark_boarding';
        $page_data['page_title'] = get_phrase('mark_bus_boarding');
        $this->load->view('backend/main', $page_data);
    }
    
    public function daily_log() {
        $page_data['routes'] = $this->db->get('transport')->result_array();
        $page_data['page_name'] = 'daily_log';
        $page_data['page_title'] = get_phrase('daily_transport_log');
        $this->load->view('backend/main', $page_data);
    }
    
    public function get_daily_students() {
        $route_id = $this->input->get('route_id');
        
        $this->db->select('s.student_id, s.code as student_code, s.name, c.name as class, t.transport_id, t.route_name, 
                          COALESCE(tdl.boarded_bus, "no") as boarded_bus, COALESCE(tdl.paid_fare, "no") as paid_fare')
            ->from('students s')
            ->join('enroll e', 'e.student_id = s.id')
            ->join('classes c', 'c.id = e.class_id')
            ->join('transport t', 't.transport_id = e.transport_id')
            ->join('transport_daily_log tdl', 'tdl.student_id = s.id AND tdl.log_date = "' . date('Y-m-d') . '"', 'left')
            ->where('e.transport_id IS NOT NULL')
            ->where('e.transport_id >', 0);
        
        if ($route_id) {
            $this->db->where('t.transport_id', $route_id);
        }
        
        $students = $this->db->get()->result_array();
        echo json_encode(['data' => $students]);
    }
    
    public function save_daily_log() {
        $data = [
            'student_id' => $this->input->post('student_id'),
            'transport_id' => $this->input->post('transport_id'),
            'log_date' => $this->input->post('log_date'),
            'boarded_bus' => $this->input->post('boarded_bus'),
            'paid_fare' => $this->input->post('paid_fare'),
            'conductor_id' => $this->session->userdata('user_id')
        ];
        
        $existing = $this->db->get_where('transport_daily_log', [
            'student_id' => $data['student_id'],
            'log_date' => $data['log_date']
        ])->row();
        
        if ($existing) {
            $this->db->where('log_id', $existing->log_id)->update('transport_daily_log', $data);
        } else {
            $this->db->insert('transport_daily_log', $data);
        }
        
        echo json_encode(['status' => 'success', 'message' => get_phrase('log_saved_successfully')]);
    }
}
