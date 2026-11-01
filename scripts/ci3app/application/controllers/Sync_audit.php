<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Audit Controller
 * 
 * Handles the audit trail UI for the multi-location bidirectional sync system.
 * Allows administrators to view sync operation history, track data provenance,
 * and revert accidental changes.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 9, 10 - Sync Audit Trail and Revert Capability
 */
class Sync_audit extends CI_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Check admin level (only admin level < 4 can access)
        $admin_level = $this->session->userdata('admin_level');
        if ($admin_level >= 4) {
            $this->session->set_flashdata('error_message', 'You do not have permission to access audit logs');
            redirect(site_url('admin/dashboard'), 'refresh');
        }
        
        // Load dependencies
        $this->load->library('Audit_logger');
        $this->load->model('Location_registry_model');
    }
    
    /**
     * Audit log list page
     */
    public function index() {
        // Get filters from query string
        $filters = [
            'table_name' => $this->input->get('table'),
            'record_id' => $this->input->get('record_id'),
            'operation' => $this->input->get('operation'),
            'device_id' => $this->input->get('device_id'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'status' => $this->input->get('status'),
            'sync_direction' => $this->input->get('sync_direction')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return !empty($value);
        });
        
        // Pagination
        $page = $this->input->get('page') ?: 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        // Get logs
        $page_data['logs'] = $this->audit_logger->get_logs($filters, $per_page, $offset);
        $page_data['total_count'] = $this->audit_logger->count_logs($filters);
        $page_data['current_page'] = $page;
        $page_data['per_page'] = $per_page;
        $page_data['total_pages'] = ceil($page_data['total_count'] / $per_page);
        
        // Get filter options
        $page_data['tables'] = $this->get_sync_tables();
        $page_data['locations'] = $this->Location_registry_model->get_all_locations();
        $page_data['operations'] = ['INSERT', 'UPDATE', 'DELETE', 'CONFLICT', 'REVERT'];
        $page_data['statuses'] = ['success', 'failed', 'conflict'];
        $page_data['directions'] = ['push', 'pull'];
        
        $page_data['filters'] = $filters;
        $page_data['page_name'] = 'sync_audit_list';
        $page_data['page_title'] = get_phrase('sync_audit_log');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * View audit log detail
     * 
     * @param int $id Audit log ID
     */
    public function view($id) {
        $log = $this->audit_logger->get_by_id($id);
        
        if (!$log) {
            $this->session->set_flashdata('error_message', 'Audit log entry not found');
            redirect(site_url('sync_audit'));
        }
        
        // Decode JSON values
        $log->old_value_decoded = $log->old_value ? json_decode($log->old_value, true) : null;
        $log->new_value_decoded = $log->new_value ? json_decode($log->new_value, true) : null;
        
        // Get location info
        $page_data['source_location'] = $this->Location_registry_model->get_location_by_device_id($log->source_device_id);
        $page_data['target_location'] = $log->target_device_id ? 
            $this->Location_registry_model->get_location_by_device_id($log->target_device_id) : null;
        
        // Get user info
        if ($log->synced_by) {
            $page_data['synced_by_user'] = $this->db->get_where('admin', ['admin_id' => $log->synced_by])->row();
        } else {
            $page_data['synced_by_user'] = null;
        }
        
        // Calculate differences
        if ($log->old_value_decoded && $log->new_value_decoded) {
            $page_data['diff'] = $this->calculate_diff($log->old_value_decoded, $log->new_value_decoded);
        } else {
            $page_data['diff'] = [];
        }
        
        // Get related audit entries (history for same record)
        $page_data['related_logs'] = $this->audit_logger->get_by_record($log->table_name, $log->record_id, 10);
        
        // Check if revert is possible
        $page_data['can_revert'] = in_array($log->operation, ['UPDATE', 'DELETE']) && 
                                   $log->old_value_decoded !== null &&
                                   $log->status === 'success';
        
        $page_data['log'] = $log;
        $page_data['page_name'] = 'sync_audit_detail';
        $page_data['page_title'] = get_phrase('audit_log_detail');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Revert a sync operation
     * 
     * @param int $id Audit log ID
     */
    public function revert($id) {
        // Check if POST request
        if (!$this->input->post('confirm')) {
            echo json_encode(['success' => false, 'message' => 'Confirmation required']);
            return;
        }
        
        // Get audit log entry
        $log = $this->audit_logger->get_by_id($id);
        
        if (!$log) {
            echo json_encode(['success' => false, 'message' => 'Audit log entry not found']);
            return;
        }
        
        // Check if revert is allowed
        if (!in_array($log->operation, ['UPDATE', 'DELETE'])) {
            echo json_encode(['success' => false, 'message' => 'Only UPDATE and DELETE operations can be reverted']);
            return;
        }
        
        if ($log->status !== 'success') {
            echo json_encode(['success' => false, 'message' => 'Only successful operations can be reverted']);
            return;
        }
        
        // Perform revert
        $result = $this->audit_logger->revert($id, $this->session->userdata('admin_id'));
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Export audit logs for compliance reporting
     */
    public function export() {
        // Get filters from query string
        $filters = [
            'table_name' => $this->input->get('table'),
            'record_id' => $this->input->get('record_id'),
            'operation' => $this->input->get('operation'),
            'device_id' => $this->input->get('device_id'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'status' => $this->input->get('status'),
            'sync_direction' => $this->input->get('sync_direction')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return !empty($value);
        });
        
        // Get format (csv or json)
        $format = $this->input->get('format') ?: 'csv';
        
        // Export logs
        $export_data = $this->audit_logger->export($filters, $format);
        
        // Set headers and output
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sync_audit_log_' . date('Y-m-d_His') . '.csv"');
            echo $export_data;
        } elseif ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="sync_audit_log_' . date('Y-m-d_His') . '.json"');
            echo $export_data;
        }
    }
    
    /**
     * Get audit statistics (AJAX)
     */
    public function stats() {
        // Get date range
        $date_from = $this->input->get('date_from') ?: date('Y-m-d', strtotime('-7 days'));
        $date_to = $this->input->get('date_to') ?: date('Y-m-d');
        
        // Get operation counts
        $operation_counts = $this->db->select('operation, COUNT(*) as count')
                                    ->where('synced_at >=', $date_from)
                                    ->where('synced_at <=', $date_to . ' 23:59:59')
                                    ->group_by('operation')
                                    ->get('sync_audit_log')
                                    ->result_array();
        
        // Get status counts
        $status_counts = $this->db->select('status, COUNT(*) as count')
                                 ->where('synced_at >=', $date_from)
                                 ->where('synced_at <=', $date_to . ' 23:59:59')
                                 ->group_by('status')
                                 ->get('sync_audit_log')
                                 ->result_array();
        
        // Get direction counts
        $direction_counts = $this->db->select('sync_direction, COUNT(*) as count')
                                    ->where('synced_at >=', $date_from)
                                    ->where('synced_at <=', $date_to . ' 23:59:59')
                                    ->group_by('sync_direction')
                                    ->get('sync_audit_log')
                                    ->result_array();
        
        // Get top tables
        $top_tables = $this->db->select('table_name, COUNT(*) as count')
                              ->where('synced_at >=', $date_from)
                              ->where('synced_at <=', $date_to . ' 23:59:59')
                              ->group_by('table_name')
                              ->order_by('count', 'DESC')
                              ->limit(10)
                              ->get('sync_audit_log')
                              ->result_array();
        
        // Get total count
        $total_count = $this->db->where('synced_at >=', $date_from)
                               ->where('synced_at <=', $date_to . ' 23:59:59')
                               ->count_all_results('sync_audit_log');
        
        $stats = [
            'total_count' => $total_count,
            'operation_counts' => $operation_counts,
            'status_counts' => $status_counts,
            'direction_counts' => $direction_counts,
            'top_tables' => $top_tables,
            'date_from' => $date_from,
            'date_to' => $date_to
        ];
        
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    /**
     * Get sync tables
     * 
     * @return array
     */
    private function get_sync_tables() {
        $query = $this->db->select('table_name')
                         ->where('sync_enabled', 1)
                         ->order_by('table_name', 'ASC')
                         ->get('sync_metadata');
        
        return array_column($query->result_array(), 'table_name');
    }
    
    /**
     * Calculate differences between two records
     * 
     * @param array $old Old data
     * @param array $new New data
     * @return array Differences
     */
    private function calculate_diff($old, $new) {
        $diff = [];
        
        $all_keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        
        foreach ($all_keys as $key) {
            // Skip sync-related columns
            if (in_array($key, ['sync_status', 'last_modified_at', 'last_modified_by', 'device_id', 'version', 'retry_count', 'sync_error'])) {
                continue;
            }
            
            $old_val = $old[$key] ?? null;
            $new_val = $new[$key] ?? null;
            
            if ($old_val !== $new_val) {
                $diff[$key] = [
                    'old' => $old_val,
                    'new' => $new_val,
                    'is_different' => true
                ];
            } else {
                $diff[$key] = [
                    'old' => $old_val,
                    'new' => $new_val,
                    'is_different' => false
                ];
            }
        }
        
        return $diff;
    }
}
