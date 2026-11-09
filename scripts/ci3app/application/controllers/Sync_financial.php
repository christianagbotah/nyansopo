<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Financial Operations Sync Controller
 * Handles sync for admissions, invoices, payments, expenses
 * Follows pattern from Sync_daily_fees.php
 */
class Sync_financial extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        $this->load->database();
    }
    
    // Sync all unsynced admissions
    public function sync_all_admissions() {
        $this->db->where('sync_status', 'PENDING');
        $unsynced = $this->db->get('student')->result();
        
        $results = ['total' => count($unsynced), 'synced' => 0, 'failed' => 0];
        
        foreach ($unsynced as $student) {
            $this->db->trans_start();
            
            // Update related records
            $this->db->where('student_id', $student->student_id)
                     ->update('invoice', ['sync_status' => 'SYNCED']);
            $this->db->where('student_id', $student->student_id)
                     ->update('student', ['sync_status' => 'SYNCED']);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status()) {
                $results['synced']++;
            } else {
                $results['failed']++;
            }
        }
        
        echo json_encode(['status' => 'success', 'results' => $results]);
    }
    
    // Sync all unsynced invoices
    public function sync_all_invoices() {
        $this->db->where('sync_status', 'PENDING');
        $unsynced = $this->db->get('invoice')->result();
        
        $results = ['total' => count($unsynced), 'synced' => 0, 'failed' => 0];
        
        foreach ($unsynced as $invoice) {
            $this->db->where('invoice_id', $invoice->invoice_id)
                     ->update('invoice', ['sync_status' => 'SYNCED']);
            
            if ($this->db->affected_rows() > 0) {
                $results['synced']++;
            } else {
                $results['failed']++;
            }
        }
        
        echo json_encode(['status' => 'success', 'results' => $results]);
    }
    
    // Sync all unsynced payments
    public function sync_all_payments() {
        $this->db->where('sync_status', 'PENDING');
        $unsynced = $this->db->get('payment')->result();
        
        $results = ['total' => count($unsynced), 'synced' => 0, 'failed' => 0];
        
        foreach ($unsynced as $payment) {
            $this->db->where('payment_id', $payment->payment_id)
                     ->update('payment', ['sync_status' => 'SYNCED']);
            
            if ($this->db->affected_rows() > 0) {
                $results['synced']++;
            } else {
                $results['failed']++;
            }
        }
        
        echo json_encode(['status' => 'success', 'results' => $results]);
    }
    
    // Overall sync status
    public function status() {
        $tables = ['student', 'invoice', 'payment', 'daily_fee_transactions'];
        $status = [];
        
        foreach ($tables as $table) {
            $total = $this->db->count_all($table);
            $synced = $this->db->where('sync_status', 'SYNCED')->count_all_results($table);
            $status[$table] = ['total' => $total, 'synced' => $synced, 'pending' => $total - $synced];
        }
        
        echo json_encode(['status' => 'success', 'data' => $status]);
    }
    
    // Health check
    public function health_check() {
        $health = ['status' => 'healthy', 'checks' => []];
        
        $tables = ['student', 'invoice', 'payment', 'daily_fee_transactions'];
        foreach ($tables as $table) {
            $health['checks'][$table] = $this->db->table_exists($table) ? 'OK' : 'MISSING';
            if (!$this->db->table_exists($table)) {
                $health['status'] = 'unhealthy';
            }
        }
        
        echo json_encode($health);
    }
}
