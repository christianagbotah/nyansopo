<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Finance extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_fees_structure() {
        $term = $this->input->post('term');
        $year = $this->input->post('year');
        $class_ids = $this->input->post('class_ids');

        if(!$term || !$year) {
            echo json_encode(['status' => 'error', 'message' => 'Term and year are required']);
            return;
        }

        // Define class order as per getFullClassList helper
        $class_order = ['CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS'];
        
        $this->db->select('c.class_id, c.name as class_name, c.name_numeric, s.name as section_name, COUNT(DISTINCT e.student_id) as student_count');
        $this->db->from('class c');
        $this->db->join('bill_item_history bih', 'bih.class_id = c.class_id AND bih.year = "'.$year.'" AND bih.term = "'.$term.'"', 'inner');
        $this->db->join('enroll e', 'e.class_id = c.class_id AND e.year = "'.$year.'" AND e.term = "'.$term.'"', 'left');
        $this->db->join('section s', 's.class_id = c.class_id', 'left');
        
        if(!empty($class_ids) && is_array($class_ids)) {
            $this->db->where_in('c.class_id', $class_ids);
        }
        
        $this->db->group_by('c.class_id, s.section_id');
        $classes = $this->db->get()->result_array();

        // Sort classes according to the defined order
        usort($classes, function($a, $b) use ($class_order) {
            $a_order = array_search($a['class_name'], $class_order);
            $b_order = array_search($b['class_name'], $class_order);
            
            // If class names are not in the predefined order, sort them alphabetically
            if($a_order === false) $a_order = 999;
            if($b_order === false) $b_order = 999;
            
            // First sort by class name order
            if($a_order != $b_order) {
                return $a_order - $b_order;
            }
            
            // Then sort by numeric value
            return intval($a['name_numeric']) - intval($b['name_numeric']);
        });

        $result = [];
        foreach($classes as $class) {
            $this->db->select('bi.title, bi.description, bih.bill_item_amount as amount');
            $this->db->from('bill_item_history bih');
            $this->db->join('bill_item bi', 'bi.id = bih.bill_item_id');
            $this->db->where('bih.class_id', $class['class_id']);
            $this->db->where('bih.year', $year);
            $this->db->where('bih.term', $term);
            $this->db->group_by('bih.bill_item_id');
            $items = $this->db->get()->result_array();

            if(empty($items)) continue;

            $total = 0;
            foreach($items as &$item) {
                $item['amount'] = floatval($item['amount']);
                $total += $item['amount'];
            }

            $full_class_name = $class['class_name'];
            if($class['section_name']) {
                $full_class_name = $class['class_name'] . ' ' . $class['name_numeric'] . ' ' . $class['section_name'];
            } else {
                $full_class_name = $class['class_name'] . ' ' . $class['name_numeric'];
            }

            $result[] = [
                'class_id' => $class['class_id'],
                'class_name' => $full_class_name,
                'student_count' => intval($class['student_count']),
                'items' => $items,
                'total_amount' => $total
            ];
        }

        echo json_encode(['status' => 'success', 'data' => $result]);
    }

    public function export_fees_structure() {
        $term = $this->input->post('term');
        $year = $this->input->post('year');
        $class_ids = $this->input->post('class_ids');

        if(!$term || !$year) {
            echo json_encode(['status' => 'error', 'message' => 'Term and year are required']);
            return;
        }

        $this->db->select('c.name as class_name, c.name_numeric');
        $this->db->from('class c');
        if(!empty($class_ids) && is_array($class_ids)) {
            $this->db->where_in('c.class_id', $class_ids);
        }
        $this->db->order_by('c.name_numeric', 'ASC');
        $classes = $this->db->get()->result_array();

        $export_data = [];
        foreach($classes as $class) {
            $this->db->select('bi.title as bill_item_title, bi.description as bill_item_description, bih.bill_item_amount');
            $this->db->from('bill_item_history bih');
            $this->db->join('bill_item bi', 'bi.id = bih.bill_item_id');
            $this->db->join('class c2', 'c2.class_id = bih.class_id');
            $this->db->where('c2.name', $class['class_name']);
            $this->db->where('bih.year', $year);
            $this->db->where('bih.term', $term);
            $this->db->group_by('bih.bill_item_id');
            $items = $this->db->get()->result_array();

            foreach($items as $item) {
                $export_data[] = [
                    'Class' => $class['class_name'],
                    'Item Title' => $item['bill_item_title'],
                    'Description' => $item['bill_item_description'] ?: 'N/A',
                    'Amount' => floatval($item['bill_item_amount']),
                    'Term' => $term,
                    'Year' => $year
                ];
            }
        }

        echo json_encode(['status' => 'success', 'data' => $export_data]);
    }

    public function store_fees_structure_data() {
        try {
            $term = $this->input->post('term');
            $year = $this->input->post('year');
            $class_ids = $this->input->post('class_ids');
            $data = $this->input->post('data');
            
            // Generate unique token
            $token = md5(uniqid(rand(), true));
            
            // Store in session
            $this->session->set_userdata('fees_structure_' . $token, [
                'term' => $term,
                'year' => $year,
                'class_ids' => $class_ids,
                'data' => $data,
                'timestamp' => time()
            ]);
            
            echo json_encode([
                'success' => true,
                'token' => $token
            ]);
        } catch (Exception $e) {
            log_message('error', 'Error in store_fees_structure_data: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to store data'
            ]);
        }
    }
    
    public function fees_structure_view() {
        try {
            $token = $this->input->get('token');
            
            if (empty($token)) {
                throw new Exception('No data token provided');
            }
            
            // Retrieve from session
            $session_data = $this->session->userdata('fees_structure_' . $token);
            
            if (empty($session_data)) {
                throw new Exception('Data not found or expired');
            }
            
            // Clear the session data after retrieving it (one-time use)
            $this->session->unset_userdata('fees_structure_' . $token);
            
            $page_data['term'] = $session_data['term'];
            $page_data['year'] = $session_data['year'];
            $page_data['class_ids'] = $session_data['class_ids'];
            $page_data['data'] = $session_data['data'];
            
            $this->load->view('backend/admin/fees_structure_modal', $page_data);
        } catch (Exception $e) {
            log_message('error', 'Error in fees_structure_view: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Error loading fees structure: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
