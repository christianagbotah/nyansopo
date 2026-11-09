<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Conflicts Controller
 * 
 * Handles the conflict resolution UI for the multi-location bidirectional sync system.
 * Allows administrators to view and manually resolve sync conflicts.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 5 - Sync Conflict Resolution UI
 */
class Sync_conflicts extends CI_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Load dependencies
        $this->load->model('Sync_conflict_model');
        $this->load->model('Location_registry_model');
        $this->load->library('Conflict_resolver');
        $this->load->library('Audit_logger');
    }
    
    /**
     * Conflict list page
     */
    public function index() {
        // Get filters
        $filters = [
            'table_name' => $this->input->get('table'),
            'status' => $this->input->get('status') ?: 'pending'
        ];
        
        // Get conflicts
        $page_data['conflicts'] = $this->Sync_conflict_model->get_pending_conflicts($filters, 100);
        $page_data['counts_by_table'] = $this->Sync_conflict_model->get_conflict_counts_by_table();
        $page_data['stats'] = $this->Sync_conflict_model->get_conflict_stats();
        $page_data['filters'] = $filters;
        $page_data['page_name'] = 'sync_conflicts_list';
        $page_data['page_title'] = get_phrase('sync_conflicts');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * View conflict detail
     * 
     * @param int $id Conflict ID
     */
    public function view($id) {
        $conflict = $this->Sync_conflict_model->get_conflict($id);
        
        if (!$conflict) {
            $this->session->set_flashdata('error_message', 'Conflict not found');
            redirect(site_url('sync_conflicts'));
        }
        
        // Get location info
        $page_data['local_location'] = $this->Location_registry_model->get_location_by_device_id($conflict->local_device_id);
        $page_data['remote_location'] = $this->Location_registry_model->get_location_by_device_id($conflict->remote_device_id);
        
        // Calculate differences
        $page_data['diff'] = $this->calculate_diff($conflict->local_data, $conflict->remote_data);
        
        $page_data['conflict'] = $conflict;
        $page_data['page_name'] = 'sync_conflict_detail';
        $page_data['page_title'] = get_phrase('conflict_detail');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Show merge interface for manual conflict resolution
     * 
     * @param int $id Conflict ID
     */
    public function merge_view($id) {
        $conflict = $this->Sync_conflict_model->get_conflict($id);
        
        if (!$conflict) {
            $this->session->set_flashdata('error_message', 'Conflict not found');
            redirect(site_url('sync_conflicts'));
        }
        
        if ($conflict->status !== 'pending') {
            $this->session->set_flashdata('error_message', 'This conflict has already been resolved');
            redirect(site_url('sync_conflicts/view/' . $id));
        }
        
        // Get location info
        $page_data['local_location'] = $this->Location_registry_model->get_location_by_device_id($conflict->local_device_id);
        $page_data['remote_location'] = $this->Location_registry_model->get_location_by_device_id($conflict->remote_device_id);
        
        // Calculate differences
        $page_data['diff'] = $this->calculate_diff($conflict->local_data, $conflict->remote_data);
        
        $page_data['conflict'] = $conflict;
        $page_data['page_name'] = 'sync_conflict_merge';
        $page_data['page_title'] = get_phrase('merge_conflict');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Resolve conflict - keep local version
     * 
     * @param int $id Conflict ID
     */
    public function keep_local($id) {
        $conflict = $this->Sync_conflict_model->get_conflict($id);
        
        if (!$conflict) {
            echo json_encode(['success' => false, 'message' => 'Conflict not found']);
            return;
        }
        
        // Apply local version
        $this->apply_version($conflict->table_name, $conflict->record_id, $conflict->local_data);
        
        // Mark conflict as resolved
        $this->Sync_conflict_model->resolve_conflict(
            $id,
            'local_wins',
            $this->session->userdata('admin_id')
        );
        
        // Log the resolution
        $this->Audit_logger->log(
            $conflict->table_name,
            $conflict->record_id,
            'CONFLICT',
            $conflict->local_device_id,
            $conflict->remote_device_id,
            $conflict->remote_data,
            $conflict->local_data,
            'push',
            $this->session->userdata('admin_id'),
            0,
            'success'
        );
        
        echo json_encode(['success' => true, 'message' => 'Local version applied']);
    }
    
    /**
     * Resolve conflict - keep remote version
     * 
     * @param int $id Conflict ID
     */
    public function keep_remote($id) {
        $conflict = $this->Sync_conflict_model->get_conflict($id);
        
        if (!$conflict) {
            echo json_encode(['success' => false, 'message' => 'Conflict not found']);
            return;
        }
        
        // Apply remote version
        $this->apply_version($conflict->table_name, $conflict->record_id, $conflict->remote_data);
        
        // Mark conflict as resolved
        $this->Sync_conflict_model->resolve_conflict(
            $id,
            'remote_wins',
            $this->session->userdata('admin_id')
        );
        
        // Log the resolution
        $this->Audit_logger->log(
            $conflict->table_name,
            $conflict->record_id,
            'CONFLICT',
            $conflict->remote_device_id,
            $conflict->local_device_id,
            $conflict->local_data,
            $conflict->remote_data,
            'pull',
            $this->session->userdata('admin_id'),
            0,
            'success'
        );
        
        echo json_encode(['success' => true, 'message' => 'Remote version applied']);
    }
    
    /**
     * Resolve conflict - merge versions
     * 
     * @param int $id Conflict ID
     */
    public function merge($id) {
        $conflict = $this->Sync_conflict_model->get_conflict($id);
        
        if (!$conflict) {
            echo json_encode(['success' => false, 'message' => 'Conflict not found']);
            return;
        }
        
        // Get merged data from POST
        $merged_data = json_decode($this->input->post('merged_data'), true);
        
        if (empty($merged_data)) {
            echo json_encode(['success' => false, 'message' => 'No merged data provided']);
            return;
        }
        
        // Apply merged version
        $this->apply_version($conflict->table_name, $conflict->record_id, $merged_data);
        
        // Mark conflict as resolved
        $this->Sync_conflict_model->resolve_conflict(
            $id,
            'merged',
            $this->session->userdata('admin_id'),
            $merged_data
        );
        
        // Log the resolution
        $this->Audit_logger->log(
            $conflict->table_name,
            $conflict->record_id,
            'CONFLICT',
            $this->get_device_id(),
            null,
            ['local' => $conflict->local_data, 'remote' => $conflict->remote_data],
            $merged_data,
            'push',
            $this->session->userdata('admin_id'),
            0,
            'success'
        );
        
        echo json_encode(['success' => true, 'message' => 'Merged version applied']);
    }
    
    /**
     * Ignore conflict
     * 
     * @param int $id Conflict ID
     */
    public function ignore($id) {
        $this->Sync_conflict_model->ignore_conflict(
            $id,
            $this->session->userdata('admin_id')
        );
        
        echo json_encode(['success' => true, 'message' => 'Conflict ignored']);
    }
    
    /**
     * Bulk resolve conflicts
     */
    public function bulk_resolve() {
        $ids = $this->input->post('ids');
        $resolution = $this->input->post('resolution');
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No conflicts selected']);
            return;
        }
        
        $count = 0;
        foreach ($ids as $id) {
            switch ($resolution) {
                case 'local_wins':
                    $conflict = $this->Sync_conflict_model->get_conflict($id);
                    if ($conflict) {
                        $this->apply_version($conflict->table_name, $conflict->record_id, $conflict->local_data);
                        $this->Sync_conflict_model->resolve_conflict($id, 'local_wins', $this->session->userdata('admin_id'));
                        $count++;
                    }
                    break;
                case 'remote_wins':
                    $conflict = $this->Sync_conflict_model->get_conflict($id);
                    if ($conflict) {
                        $this->apply_version($conflict->table_name, $conflict->record_id, $conflict->remote_data);
                        $this->Sync_conflict_model->resolve_conflict($id, 'remote_wins', $this->session->userdata('admin_id'));
                        $count++;
                    }
                    break;
                case 'ignore':
                    $this->Sync_conflict_model->ignore_conflict($id, $this->session->userdata('admin_id'));
                    $count++;
                    break;
            }
        }
        
        echo json_encode(['success' => true, 'message' => "{$count} conflicts resolved"]);
    }
    
    /**
     * Get conflict statistics (AJAX)
     */
    public function stats() {
        $stats = $this->Sync_conflict_model->get_conflict_stats();
        
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    /**
     * Apply a version to the database
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param array $data Data to apply
     */
    private function apply_version($table, $record_id, $data) {
        $primary_key = $this->get_primary_key($table);
        
        if (!$primary_key) {
            return false;
        }
        
        // Prepare data for update
        $update_data = $data;
        unset($update_data[$primary_key]);
        $update_data['sync_status'] = 'PENDING';
        $update_data['last_modified_at'] = date('Y-m-d H:i:s');
        $update_data['last_modified_by'] = $this->session->userdata('admin_id');
        
        return $this->db->where($primary_key, $record_id)
                       ->update($table, $update_data);
    }
    
    /**
     * Calculate differences between two records
     * 
     * @param array $local Local data
     * @param array $remote Remote data
     * @return array Differences
     */
    private function calculate_diff($local, $remote) {
        $diff = [];
        
        $all_keys = array_unique(array_merge(array_keys($local), array_keys($remote)));
        
        foreach ($all_keys as $key) {
            // Skip sync-related columns
            if (in_array($key, ['sync_status', 'last_modified_at', 'last_modified_by', 'device_id', 'version', 'retry_count', 'sync_error'])) {
                continue;
            }
            
            $local_val = $local[$key] ?? null;
            $remote_val = $remote[$key] ?? null;
            
            if ($local_val !== $remote_val) {
                $diff[$key] = [
                    'local' => $local_val,
                    'remote' => $remote_val,
                    'is_different' => true
                ];
            } else {
                $diff[$key] = [
                    'local' => $local_val,
                    'remote' => $remote_val,
                    'is_different' => false
                ];
            }
        }
        
        return $diff;
    }
    
    /**
     * Get primary key for table
     * 
     * @param string $table Table name
     * @return string|null Primary key
     */
    private function get_primary_key($table) {
        $query = $this->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        $result = $query->row_array();
        return $result ? $result['Column_name'] : null;
    }
    
    /**
     * Get device ID
     * 
     * @return string
     */
    private function get_device_id() {
        $setting = $this->db->get_where('settings', ['type' => 'device_id'])->row();
        return $setting ? $setting->description : 'local-server-001';
    }
}
