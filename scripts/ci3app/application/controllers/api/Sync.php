<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync API Controller
 * Handles bidirectional sync between local and server databases
 */
class Sync extends CI_Controller {
    
    private $device_id;
    private $auth_token;
    
    public function __construct() {
        parent::__construct();
        $this->load->library('Offline_sync');
        $this->load->database();
        
        // Authenticate device
        $this->authenticate();
    }
    
    /**
     * Authenticate device using token
     */
    private function authenticate() {
        $this->device_id = $this->input->get_request_header('X-Device-ID');
        $this->auth_token = $this->input->get_request_header('X-Auth-Token');
        
        if(!$this->device_id || !$this->auth_token) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Verify device
        $device = $this->Sync_model->verify_device($this->device_id, $this->auth_token);
        if(!$device) {
            $this->output->set_status_header(403);
            echo json_encode(['error' => 'Invalid device']);
            exit;
        }
    }
    
    /**
     * PUSH: Upload local changes to server
     */
    public function push() {
        $data = json_decode($this->input->raw_input_stream, true);
        
        if(!$data || !isset($data['queue'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            return;
        }
        
        $this->db->trans_start();
        
        $results = [];
        foreach($data['queue'] as $item) {
            try {
                $result = $this->Sync_model->process_push_item($item, $this->device_id);
                $results[] = $result;
            } catch(Exception $e) {
                $results[] = [
                    'id' => $item['id'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        $this->db->trans_complete();
        
        // Update device last sync
        $this->Sync_model->update_device_sync($this->device_id);
        
        echo json_encode([
            'status' => 'success',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * PULL: Download server changes to local
     */
    public function pull() {
        $last_sync = $this->input->post('last_sync');
        $tables = $this->input->post('tables') ?: [];
        
        if(empty($tables)) {
            $tables = $this->Sync_model->get_syncable_tables();
        }
        
        $changes = [];
        foreach($tables as $table) {
            $records = $this->Sync_model->get_changes_since($table, $last_sync, $this->device_id);
            if(!empty($records)) {
                $changes[$table] = $records;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'changes' => $changes,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Register new device
     */
    public function register_device() {
        $device_name = $this->input->post('device_name');
        $school_id = $this->input->post('school_id');
        
        $result = $this->Sync_model->register_device($device_name, $school_id);
        
        echo json_encode($result);
    }
    
    /**
     * Get sync status
     */
    public function status() {
        $status = $this->Sync_model->get_sync_status($this->device_id);
        echo json_encode($status);
    }
    
    /**
     * Resolve conflict
     */
    public function resolve_conflict() {
        $conflict_id = $this->input->post('conflict_id');
        $resolution = $this->input->post('resolution');
        $merged_data = $this->input->post('merged_data');
        
        $result = $this->Sync_model->resolve_conflict($conflict_id, $resolution, $merged_data);
        
        echo json_encode($result);
    }
    
    /**
     * BATCH_PUSH: Upload multiple records at once with atomic insert/update handling
     * 
     * This method handles the critical edge case where a record is inserted offline
     * and updated (one or more times) before the first sync occurs.
     * 
     * Uses REPLACE INTO for atomic insert/update handling to ensure that whether
     * the record exists remotely or not, it will be inserted or updated correctly.
     * 
     * @return JSON response with per-record success/failure status
     * 
     * Validates Requirements 2.3, 2.4
     */
    public function batch_push() {
        // Get input data
        $data = json_decode($this->input->raw_input_stream, true);
        
        // Validate input
        if(!$data || !isset($data['records']) || !is_array($data['records'])) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid data format. Expected {records: [...]}'
                ]));
            return;
        }
        
        // Start transaction for atomicity
        $this->db->trans_start();
        
        $results = [];
        $success_count = 0;
        $failed_count = 0;
        
        // Process each record
        foreach($data['records'] as $record) {
            try {
                // Validate record structure
                if(!isset($record['table_name']) || !isset($record['data'])) {
                    $results[] = [
                        'table' => $record['table_name'] ?? 'unknown',
                        'record_id' => $record['record_id'] ?? null,
                        'status' => 'failed',
                        'error' => 'Missing required fields (table_name, data)'
                    ];
                    $failed_count++;
                    continue;
                }
                
                $table = $record['table_name'];
                $record_data = $record['data'];
                $record_id = $record['record_id'] ?? null;
                
                // Use REPLACE INTO for atomic insert/update
                // This handles the edge case where a record is inserted and updated
                // offline before first sync - whether the record exists or not,
                // REPLACE INTO will insert or update it correctly
                $result = $this->process_batch_record($table, $record_data, $record_id);
                
                $results[] = [
                    'table' => $table,
                    'record_id' => $record_id,
                    'status' => 'success',
                    'operation' => $result['operation']
                ];
                $success_count++;
                
            } catch(Exception $e) {
                // Log error
                log_message('error', "Batch push failed for {$table}:{$record_id} - {$e->getMessage()}");
                
                $results[] = [
                    'table' => $record['table_name'] ?? 'unknown',
                    'record_id' => $record['record_id'] ?? null,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failed_count++;
            }
        }
        
        // Complete transaction
        $this->db->trans_complete();
        
        // Check if transaction succeeded
        if ($this->db->trans_status() === FALSE) {
            // Transaction failed - rollback occurred
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Transaction failed - all changes rolled back',
                    'results' => $results
                ]));
            return;
        }
        
        // Update device last sync timestamp
        $this->Sync_model->update_device_sync($this->device_id);
        
        // Return results
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $failed_count === 0 ? 'success' : 'partial',
                'message' => sprintf(
                    'Processed %d records: %d succeeded, %d failed',
                    count($data['records']),
                    $success_count,
                    $failed_count
                ),
                'summary' => [
                    'total' => count($data['records']),
                    'success' => $success_count,
                    'failed' => $failed_count
                ],
                'results' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ]));
    }
    
    /**
     * Process a single record using REPLACE INTO for atomic insert/update
     * 
     * REPLACE INTO works as follows:
     * 1. If record with primary key exists: DELETE old record, INSERT new record
     * 2. If record doesn't exist: INSERT new record
     * 
     * This ensures atomic insert/update behavior and handles the critical edge case
     * where a record is inserted offline and updated before first sync.
     * 
     * @param string $table Table name
     * @param array $data Record data
     * @param mixed $record_id Primary key value
     * @return array Result with operation type
     */
    private function process_batch_record($table, $data, $record_id) {
        // Get primary key for this table
        $primary_key = $this->get_table_primary_key($table);
        
        // Check if record exists to determine operation type (for logging)
        $exists = false;
        if ($record_id !== null) {
            $exists = $this->db->where($primary_key, $record_id)
                ->count_all_results($table) > 0;
        }
        
        // Use REPLACE INTO for atomic insert/update
        // This is the professional solution for handling records that may or may not exist
        $this->db->replace($table, $data);
        
        return [
            'operation' => $exists ? 'updated' : 'inserted'
        ];
    }
    
    /**
     * Get primary key column name for a table
     * 
     * @param string $table Table name
     * @return string Primary key column name
     */
    private function get_table_primary_key($table) {
        // Common primary key patterns in this codebase
        $primary_keys = [
            'student' => 'student_id',
            'enroll' => 'enroll_id',
            'parent' => 'parent_id',
            'class' => 'class_id',
            'section' => 'section_id',
            'subject' => 'subject_id',
            'teacher' => 'teacher_id',
            'admin' => 'admin_id',
            'invoice' => 'invoice_id',
            'payment' => 'payment_id',
            'daily_fee_transactions' => 'transaction_id',
            'daily_fee_wallet' => 'wallet_id',
            'discount_profiles' => 'profile_id',
            'student_discount_assignments' => 'assignment_id',
            'attendance' => 'attendance_id',
            'exam' => 'exam_id',
            'exam_marks' => 'mark_id',
            'grade' => 'grade_id'
        ];
        
        // Return mapped primary key or default to 'id'
        return $primary_keys[$table] ?? 'id';
    }
}
