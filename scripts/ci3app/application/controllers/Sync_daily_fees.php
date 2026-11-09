<?php
/**
 * Manual Sync Utility for Daily Fee Transactions
 * Use this to sync existing transactions that were created before integration
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Sync_daily_fees extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Security: Only allow admin access
        if ($this->session->userdata('admin_id') == '') {
            redirect(site_url('login'), 'refresh');
        }
        
        $this->load->library('Financial_integration_hooks');
        $this->load->database();
    }
    
    /**
     * Sync all unsynced transactions
     */
    public function sync_all() {
        // Check if accounting module is available
        if (!$this->db->table_exists('journal_entries')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Accounting module not available'
            ]);
            return;
        }
        
        // Get unsynced transactions
        $this->db->where('synced_to_accounts', 0);
        $this->db->or_where('synced_to_accounts IS NULL', null, false);
        $unsynced = $this->db->get('daily_fee_transactions')->result();
        
        $results = [
            'total' => count($unsynced),
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        foreach ($unsynced as $trans) {
            try {
                $result = $this->financial_integration_hooks->sync_daily_fee($trans->id);
                
                if ($result['status'] == 'success') {
                    $results['synced']++;
                } elseif ($result['status'] == 'skipped') {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Transaction {$trans->id}: " . ($result['message'] ?? 'Unknown error');
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Transaction {$trans->id}: " . $e->getMessage();
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => "Synced {$results['synced']} of {$results['total']} transactions",
            'results' => $results
        ]);
    }
    
    /**
     * Sync specific transaction by ID
     */
    public function sync_one($transaction_id) {
        if (!$transaction_id) {
            echo json_encode(['status' => 'error', 'message' => 'Transaction ID required']);
            return;
        }
        
        try {
            $result = $this->financial_integration_hooks->sync_daily_fee($transaction_id);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * View sync status
     */
    public function status() {
        $page_data['total'] = $this->db->count_all('daily_fee_transactions');
        
        $page_data['synced'] = $this->db->where('synced_to_accounts', 1)
                                        ->count_all_results('daily_fee_transactions');
        
        $page_data['unsynced'] = $page_data['total'] - $page_data['synced'];
        
        // Recent synced transactions
        $page_data['recent_synced'] = $this->db->select('id, transaction_code, total_amount, synced_at')
                                                ->where('synced_to_accounts', 1)
                                                ->order_by('synced_at', 'DESC')
                                                ->limit(10)
                                                ->get('daily_fee_transactions')
                                                ->result_array();
        
        // Unsynced transactions
        $page_data['unsynced_list'] = $this->db->select('id, transaction_code, total_amount, created_at')
                                                ->where('synced_to_accounts', 0)
                                                ->or_where('synced_to_accounts IS NULL', null, false)
                                                ->order_by('created_at', 'DESC')
                                                ->limit(10)
                                                ->get('daily_fee_transactions')
                                                ->result_array();
        
        $page_data['page_name'] = 'daily_fee_sync_status';
        $page_data['page_title'] = 'Daily Fee Accounting Sync Status';
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Check integration health
     */
    public function health_check() {
        $health = [
            'status' => 'healthy',
            'checks' => []
        ];
        
        // Check 1: Tables exist
        $required_tables = ['journal_entries', 'journal_entry_lines', 'chart_of_accounts', 'daily_fee_transactions'];
        foreach ($required_tables as $table) {
            $health['checks']['table_' . $table] = $this->db->table_exists($table) ? 'OK' : 'MISSING';
            if (!$this->db->table_exists($table)) {
                $health['status'] = 'unhealthy';
            }
        }
        
        // Check 2: Columns exist
        if ($this->db->field_exists('synced_to_accounts', 'daily_fee_transactions')) {
            $health['checks']['column_synced_to_accounts'] = 'OK';
        } else {
            $health['checks']['column_synced_to_accounts'] = 'MISSING';
            $health['status'] = 'unhealthy';
        }
        
        // Check 3: Revenue accounts exist
        $accounts = $this->db->where_in('account_code', ['4100', '4110', '4120', '4130', '4140'])
                             ->count_all_results('chart_of_accounts');
        $health['checks']['revenue_accounts'] = $accounts == 5 ? 'OK' : "INCOMPLETE ($accounts/5)";
        if ($accounts < 5) {
            $health['status'] = 'degraded';
        }
        
        // Check 4: Recent sync activity
        $recent_syncs = $this->db->where('synced_to_accounts', 1)
                                 ->where('synced_at >', time() - 86400)
                                 ->count_all_results('daily_fee_transactions');
        $health['checks']['recent_sync_activity'] = "$recent_syncs in last 24h";
        
        // Check 5: Failed syncs
        $failed = $this->db->where('status', 'failed')
                          ->where('integration_type', 'daily_fee_to_accounts')
                          ->where('created_at >', time() - 86400)
                          ->count_all_results('financial_integration_log');
        $health['checks']['failed_syncs_24h'] = $failed;
        if ($failed > 10) {
            $health['status'] = 'degraded';
        }
        
        echo json_encode($health);
    }
}
