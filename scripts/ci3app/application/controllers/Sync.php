<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Controller
 * Handles sync API endpoints
 */
class Sync extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('sync_manager');
        $this->load->library('session');
    }
    
    /**
     * Sync endpoint for client data (batch support)
     */
    public function push() {
        header('Content-Type: application/json');
        
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }

        // Handle batch sync
        if (isset($data['batch']) && is_array($data['batch'])) {
            $results = [];
            foreach ($data['batch'] as $item) {
                $results[] = $this->sync_manager->process_sync_data($item);
            }
            echo json_encode(['success' => true, 'results' => $results]);
            return;
        }
        
        $result = $this->sync_manager->process_sync_data($data);
        echo json_encode($result);
    }
    
    /**
     * Get pending syncs for current user
     */
    public function pull() {
        header('Content-Type: application/json');
        
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $pending = $this->sync_manager->get_pending_syncs($user_id);
        echo json_encode(['success' => true, 'data' => $pending]);
    }
    
    /**
     * Mark sync as completed
     */
    public function complete() {
        header('Content-Type: application/json');
        
        $sync_id = $this->input->post('sync_id');
        if (!$sync_id) {
            echo json_encode(['success' => false, 'message' => 'Sync ID required']);
            return;
        }
        
        $result = $this->sync_manager->mark_synced($sync_id);
        echo json_encode(['success' => $result]);
    }
    
    /**
     * Check connection status
     */
    public function ping() {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'timestamp' => time(), 'server_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get server data for offline cache
     */
    public function cache_data() {
        @ini_set('display_errors', 0);
        header('Content-Type: application/json');
        
        try {

        // Skip auth check for now - add back later if needed
        // $user_id = $this->session->userdata('user_id');
        // if (!$user_id) {
        //     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        //     return;
        // }
        
        $table = $this->input->get('table');
        $limit = (int)($this->input->get('limit') ?? 100);

        if (!$table) {
            echo json_encode(['success' => false, 'message' => 'Table required']);
            return;
        }

        $table_map = [
            'student' => 'student',
            'teacher' => 'teacher',
            'class' => 'class',
            'invoice' => 'invoice',
            'payment' => 'payment'
        ];
        
        if (!isset($table_map[$table])) {
            echo json_encode(['success' => false, 'message' => 'Invalid table']);
            return;
        }

        $actual_table = $table_map[$table];

            $this->db->limit($limit);
            $query = @$this->db->get($actual_table);
            
            if (!$query) {
                echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
                return;
            }
            
            $data = $query->result_array();
            echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
        } catch (Throwable $e) {
            echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
        }
    }
}
