<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Adapter for Offline/Online Switching
 * Handles data storage in IndexedDB (offline) or MySQL (online)
 */
class Db_adapter {
    
    protected $CI;
    protected $mode; // 'online' or 'offline'
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->mode = 'online'; // Default to online
    }
    
    /**
     * Set storage mode
     */
    public function set_mode($mode) {
        $this->mode = in_array($mode, ['online', 'offline']) ? $mode : 'online';
    }
    
    /**
     * Get current mode
     */
    public function get_mode() {
        return $this->mode;
    }
    
    /**
     * Save attendance record
     */
    public function save_attendance($data) {
        if ($this->mode === 'online') {
            return $this->save_attendance_online($data);
        } else {
            return $this->save_attendance_offline($data);
        }
    }
    
    /**
     * Get attendance records
     */
    public function get_attendance($filters = []) {
        if ($this->mode === 'online') {
            return $this->get_attendance_online($filters);
        } else {
            return $this->get_attendance_offline($filters);
        }
    }
    
    /**
     * Sync offline data to online database
     */
    public function sync_to_online() {
        // Return sync instructions for JavaScript to handle
        return [
            'status' => 'pending',
            'message' => 'Sync will be handled by JavaScript IndexedDB'
        ];
    }
    
    /**
     * Online: Save to MySQL
     */
    private function save_attendance_online($data) {
        $this->CI->db->trans_start();
        
        foreach ($data as $record) {
            $this->CI->db->replace('attendance', $record);
        }
        
        $this->CI->db->trans_complete();
        
        return [
            'status' => $this->CI->db->trans_status() ? 'success' : 'error',
            'message' => $this->CI->db->trans_status() ? 'Saved to database' : 'Database error'
        ];
    }
    
    /**
     * Offline: Return data for IndexedDB storage
     */
    private function save_attendance_offline($data) {
        // Return data to be stored in IndexedDB by JavaScript
        return [
            'status' => 'offline',
            'data' => $data,
            'message' => 'Store in IndexedDB'
        ];
    }
    
    /**
     * Online: Get from MySQL
     */
    private function get_attendance_online($filters) {
        $this->CI->db->select('*');
        $this->CI->db->from('attendance');
        
        if (!empty($filters['class_id'])) {
            $this->CI->db->where('class_id', $filters['class_id']);
        }
        
        if (!empty($filters['date'])) {
            $this->CI->db->where('DATE(timestamp)', $filters['date']);
        }
        
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Offline: Return instruction to get from IndexedDB
     */
    private function get_attendance_offline($filters) {
        return [
            'status' => 'offline',
            'filters' => $filters,
            'message' => 'Retrieve from IndexedDB'
        ];
    }
}
