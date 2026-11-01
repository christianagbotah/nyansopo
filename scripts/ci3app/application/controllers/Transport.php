<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transport extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->database();
        
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    public function management() {
        $page_data['page_name'] = 'transport_management';
        $page_data['page_title'] = get_phrase('transport_management');
        $this->load->view('backend/index', $page_data);
    }
    
    public function get_daily_operations() {
        $today = strtotime(date('Y-m-d'));
        $route_filter = $this->input->get('route');
        $status_filter = $this->input->get('status');
        
        $this->db->select('s.student_id, s.name, s.student_code, s.transport_id, c.name as class_name, t.route_name, t.route_fare');
        $this->db->from('student s');
        $this->db->join('enroll e', 's.student_id = e.student_id');
        $this->db->join('class c', 'e.class_id = c.class_id');
        $this->db->join('transport t', 's.transport_id = t.transport_id', 'left');
        $this->db->where('e.year', get_settings('running_year'));
        $this->db->where('e.term', get_settings('running_term'));
        $this->db->where('s.transport_id IS NOT NULL');
        
        if ($route_filter) {
            $this->db->where('s.transport_id', $route_filter);
        }
        
        $students = $this->db->get()->result_array();
        
        $stats = ['total' => count($students), 'recorded' => 0, 'boarded_in' => 0, 'boarded_out' => 0];
        
        foreach ($students as &$student) {
            $attendance = $this->db->get_where('bus_attendance', [
                'student_id' => $student['student_id'],
                'attendance_date' => $today
            ])->row_array();
            
            $student['recorded'] = !empty($attendance);
            $student['direction'] = $attendance['transport_direction'] ?? '';
            $student['boarded_in'] = ($attendance['boarded_in'] ?? 0) == 1;
            $student['boarded_out'] = ($attendance['boarded_out'] ?? 0) == 1;
            
            if ($student['recorded']) $stats['recorded']++;
            if ($student['boarded_in']) $stats['boarded_in']++;
            if ($student['boarded_out']) $stats['boarded_out']++;
            
            if ($status_filter === 'recorded' && !$student['recorded']) {
                unset($student);
            } elseif ($status_filter === 'not_recorded' && $student['recorded']) {
                unset($student);
            } elseif ($status_filter === 'boarded' && !($student['boarded_in'] || $student['boarded_out'])) {
                unset($student);
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'students' => array_values($students),
            'stats' => $stats
        ]);
    }
    
    public function get_reconciliation() {
        $date = $this->input->get('date') ?: date('Y-m-d');
        $collector = $this->input->get('collector');
        $date_timestamp = strtotime($date);
        
        $this->db->select('ba.*, s.name as student_name, s.student_code, u.name as collector_name');
        $this->db->from('bus_attendance ba');
        $this->db->join('student s', 'ba.student_id = s.student_id');
        $this->db->join('users u', 'ba.collected_by = u.user_id');
        $this->db->where('ba.attendance_date', $date_timestamp);
        
        if ($collector) {
            $this->db->where('ba.collected_by', $collector);
        }
        
        $this->db->order_by('ba.payment_time', 'DESC');
        $transactions = $this->db->get()->result_array();
        
        $summary = ['total' => 0, 'prepaid' => 0, 'cash' => 0, 'count' => count($transactions)];
        
        foreach ($transactions as $t) {
            $summary['total'] += $t['total_fare'];
            if ($t['payment_source'] === 'prepaid') {
                $summary['prepaid'] += $t['prepaid_deducted'];
            } else {
                $summary['cash'] += $t['cash_collected'];
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'transactions' => $transactions,
            'summary' => $summary
        ]);
    }
    
    public function get_history() {
        $student_search = $this->input->get('student');
        $from = strtotime($this->input->get('from') ?: date('Y-m-d', strtotime('-7 days')));
        $to = strtotime($this->input->get('to') ?: date('Y-m-d'));
        
        $this->db->select('ba.*, s.name as student_name, s.student_code, t.route_name, u.name as collector_name');
        $this->db->from('bus_attendance ba');
        $this->db->join('student s', 'ba.student_id = s.student_id');
        $this->db->join('transport t', 'ba.route_id = t.transport_id', 'left');
        $this->db->join('users u', 'ba.collected_by = u.user_id', 'left');
        $this->db->where('ba.attendance_date >=', $from);
        $this->db->where('ba.attendance_date <=', $to);
        
        if ($student_search) {
            $this->db->group_start();
            $this->db->like('s.name', $student_search);
            $this->db->or_like('s.student_code', $student_search);
            $this->db->group_end();
        }
        
        $this->db->order_by('ba.attendance_date', 'DESC');
        $history = $this->db->get()->result_array();
        
        echo json_encode([
            'status' => 'success',
            'history' => $history
        ]);
    }
    
    public function get_morning_students() {
        $today = strtotime(date('Y-m-d'));
        
        $this->db->select('s.student_id, s.name, s.student_code, s.transport_id, c.name as class_name, t.route_name, t.route_fare');
        $this->db->from('student s');
        $this->db->join('enroll e', 's.student_id = e.student_id');
        $this->db->join('class c', 'e.class_id = c.class_id');
        $this->db->join('transport t', 's.transport_id = t.transport_id');
        $this->db->where('e.year', get_settings('running_year'));
        $this->db->where('e.term', get_settings('running_term'));
        $this->db->where('s.transport_id IS NOT NULL');
        $this->db->order_by('s.name');
        
        $students = $this->db->get()->result_array();
        
        foreach ($students as &$student) {
            $attendance = $this->db->get_where('bus_attendance', [
                'student_id' => $student['student_id'],
                'attendance_date' => $today
            ])->row_array();
            
            $student['recorded'] = !empty($attendance);
            $student['direction'] = $attendance['transport_direction'] ?? '';
        }
        
        echo json_encode(['status' => 'success', 'students' => $students]);
    }
    
    public function get_student_wallet($student_id) {
        $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row_array();
        echo json_encode($wallet ?: ['transport_balance' => 0, 'transport_arrears' => 0]);
    }
    
    public function get_morning_stats() {
        $today = strtotime(date('Y-m-d'));
        
        $recorded = $this->db->where('attendance_date', $today)->count_all_results('bus_attendance');
        
        $total_students = $this->db->query("
            SELECT COUNT(*) as count FROM student s
            JOIN enroll e ON s.student_id = e.student_id
            WHERE e.year = ? AND e.term = ? AND s.transport_id IS NOT NULL
        ", [get_settings('running_year'), get_settings('running_term')])->row()->count;
        
        $collected = $this->db->query("
            SELECT SUM(total_fare) as total FROM bus_attendance
            WHERE attendance_date = ? AND payment_status = 'paid'
        ", [$today])->row()->total ?? 0;
        
        echo json_encode([
            'status' => 'success',
            'stats' => [
                'collected' => floatval($collected),
                'students' => $recorded,
                'pending' => $total_students - $recorded
            ]
        ]);
    }
    
    public function get_wallets() {
        $filter = $this->input->get('filter') ?: 'all';
        $search = $this->input->get('search');
        
        $this->db->select('s.student_id, s.name, s.student_code, s.transport_id, c.name as class_name, t.route_name, w.transport_balance, w.transport_arrears');
        $this->db->from('student s');
        $this->db->join('enroll e', 's.student_id = e.student_id');
        $this->db->join('class c', 'e.class_id = c.class_id');
        $this->db->join('transport t', 's.transport_id = t.transport_id', 'left');
        $this->db->join('daily_fee_wallet w', 's.student_id = w.student_id', 'left');
        $this->db->where('e.year', get_settings('running_year'));
        $this->db->where('e.term', get_settings('running_term'));
        
        if ($search) {
            $this->db->group_start();
            $this->db->like('s.name', $search);
            $this->db->or_like('s.student_code', $search);
            $this->db->group_end();
        }
        
        if ($filter === 'positive') {
            $this->db->where('w.transport_balance >', 0);
        } elseif ($filter === 'negative') {
            $this->db->where('w.transport_balance <', 0);
        } elseif ($filter === 'zero') {
            $this->db->where('w.transport_balance', 0);
        }
        
        $this->db->order_by('s.name');
        $wallets = $this->db->get()->result_array();
        
        foreach ($wallets as &$wallet) {
            $wallet['transport_balance'] = $wallet['transport_balance'] ?? 0;
            $wallet['transport_arrears'] = $wallet['transport_arrears'] ?? 0;
        }
        
        echo json_encode([
            'status' => 'success',
            'wallets' => $wallets
        ]);
    }
    
    public function morning_collection() {
        $page_data['page_name'] = 'morning_transport_collection_enhanced';
        $page_data['page_title'] = get_phrase('morning_transport_collection');
        $this->load->view('backend/index', $page_data);
    }
}
