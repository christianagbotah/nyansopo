<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Financial Integration Controller
 * Enterprise-Grade Financial System Integration
 * Connects: Fee Collection → Financial Analytics → Accounts & Bookkeeping
 * 
 * @package SchoolManager
 * @author Senior Software Engineer & Financial Analyst
 * @version 1.0
 */
class Financial_integration extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->load->library('session');
        $this->load->database();
        
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
    }

    // ==================== UNIFIED FINANCIAL DASHBOARD ====================
    public function dashboard() {
        $page_data['page_name'] = 'financial_dashboard_unified';
        $page_data['page_title'] = get_phrase('unified_financial_dashboard');
        $this->load->view('backend/main', $page_data);
    }

    // ==================== REAL-TIME ANALYTICS API ====================
    
    /**
     * Get comprehensive financial overview
     * Combines data from all three systems
     */
    public function get_financial_overview() {
        $period = $this->input->get('period') ?: 'today';
        $dates = $this->calculate_period_dates($period);
        
        $data = [
            'fee_collection' => $this->get_fee_collection_metrics($dates),
            'analytics' => $this->get_analytics_metrics($dates),
            'accounts' => $this->get_accounts_metrics($dates),
            'integration_status' => $this->get_integration_health(),
            'period' => $period,
            'dates' => $dates
        ];
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    /**
     * Fee Collection Metrics for Analytics
     * Includes daily fees, invoice payments, and bus fares
     */
    private function get_fee_collection_metrics($dates) {
        // Daily fees
        $this->db->select('
            COUNT(DISTINCT student_id) as students_paid,
            COUNT(*) as total_transactions,
            SUM(feeding_amount) as feeding_total,
            SUM(breakfast_amount) as breakfast_total,
            SUM(classes_amount) as classes_total,
            SUM(water_amount) as water_total,
            SUM(transport_amount) as transport_total,
            SUM(total_amount) as grand_total,
            AVG(total_amount) as avg_transaction,
            SUM(CASE WHEN payment_type = "arrears" THEN total_amount ELSE 0 END) as arrears_collected,
            SUM(CASE WHEN payment_type = "advance" THEN total_amount ELSE 0 END) as advance_collected,
            SUM(CASE WHEN payment_method = 1 THEN total_amount ELSE 0 END) as cash_total,
            SUM(CASE WHEN payment_method = 2 THEN total_amount ELSE 0 END) as momo_total,
            SUM(CASE WHEN payment_method = 3 THEN total_amount ELSE 0 END) as bank_total
        ');
        $this->db->where('payment_date >=', $dates['start']);
        $this->db->where('payment_date <=', $dates['end']);
        $metrics = $this->db->get('daily_fee_transactions')->row_array();
        
        // Invoice payments
        $this->db->select('SUM(amount) as invoice_total, COUNT(*) as invoice_count');
        $this->db->where('timestamp >=', $dates['start']);
        $this->db->where('timestamp <=', $dates['end']);
        $invoice_data = $this->db->get('payment')->row();
        $metrics['invoice_total'] = $invoice_data ? $invoice_data->invoice_total : 0;
        $metrics['invoice_count'] = $invoice_data ? $invoice_data->invoice_count : 0;
        
        // Update grand total to include invoice payments
        $metrics['grand_total'] = $metrics['grand_total'] + $metrics['invoice_total'];
        $metrics['total_transactions'] = $metrics['total_transactions'] + $metrics['invoice_count'];
        
        // Get outstanding arrears
        $this->db->select('SUM(feeding_arrears + breakfast_arrears + classes_arrears + water_arrears + transport_arrears) as total_arrears');
        $arrears = $this->db->get('daily_fee_wallet')->row();
        $metrics['outstanding_arrears'] = $arrears ? $arrears->total_arrears : 0;
        
        // Get prepaid balances
        $this->db->select('SUM(feeding_balance + breakfast_balance + classes_balance + water_balance + transport_balance) as total_prepaid');
        $prepaid = $this->db->get('daily_fee_wallet')->row();
        $metrics['prepaid_balance'] = $prepaid ? $prepaid->total_prepaid : 0;
        
        // Calculate collection efficiency
        $total_collectible = $metrics['outstanding_arrears'] + $metrics['grand_total'];
        $metrics['collection_rate'] = $total_collectible > 0 ? 
            round(($metrics['grand_total'] / $total_collectible) * 100, 2) : 100;
        
        return $metrics;
    }

    /**
     * Analytics Metrics
     */
    private function get_analytics_metrics($dates) {
        // Revenue trends
        $trends = $this->get_revenue_trends($dates);
        
        // Top collectors
        $this->db->select('a.name, COUNT(*) as transactions, SUM(t.total_amount) as total');
        $this->db->from('daily_fee_transactions t');
        $this->db->join('admin a', 'a.admin_id = t.collected_by');
        $this->db->where('t.payment_date >=', $dates['start']);
        $this->db->where('t.payment_date <=', $dates['end']);
        $this->db->group_by('t.collected_by');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(5);
        $top_collectors = $this->db->get()->result_array();
        
        // Revenue by class
        $this->db->select('c.name as class_name, SUM(t.total_amount) as total');
        $this->db->from('daily_fee_transactions t');
        $this->db->join('student s', 's.student_id = t.student_id');
        $this->db->join('enroll e', 'e.student_id = s.student_id');
        $this->db->join('class c', 'c.class_id = e.class_id');
        $this->db->where('t.payment_date >=', $dates['start']);
        $this->db->where('t.payment_date <=', $dates['end']);
        
        // Handle both 'year' and 'session' column names
        if ($this->db->field_exists('year', 'enroll')) {
            $this->db->where('e.year', get_settings('running_year'));
        } elseif ($this->db->field_exists('session', 'enroll')) {
            $this->db->where('e.session', get_settings('running_session'));
        }
        
        if ($this->db->field_exists('term', 'enroll')) {
            $this->db->where('e.term', get_settings('running_term'));
        }
        
        $this->db->group_by('c.class_id');
        $this->db->order_by('total', 'DESC');
        $by_class = $this->db->get()->result_array();
        
        return [
            'trends' => $trends,
            'top_collectors' => $top_collectors,
            'revenue_by_class' => $by_class,
            'growth_rate' => $this->calculate_growth_rate($dates)
        ];
    }

    /**
     * Accounts Metrics
     */
    private function get_accounts_metrics($dates) {
        // Get journal entries created from fee collection
        $this->db->select('COUNT(*) as auto_entries, SUM(total_debit) as total_amount');
        $this->db->where('source_type', 'fee_collection');
        $this->db->where('entry_date >=', date('Y-m-d', $dates['start']));
        $this->db->where('entry_date <=', date('Y-m-d', $dates['end']));
        $journal_stats = $this->db->get('journal_entries')->row_array();
        
        // Get account balances
        $this->db->select('account_type, SUM(current_balance) as balance');
        $this->db->where('is_active', 1);
        $this->db->group_by('account_type');
        $balances = $this->db->get('chart_of_accounts')->result_array();
        
        $account_balances = [];
        foreach ($balances as $bal) {
            $account_balances[$bal['account_type']] = $bal['balance'];
        }
        
        return [
            'auto_journal_entries' => $journal_stats['auto_entries'] ?? 0,
            'auto_entries_amount' => $journal_stats['total_amount'] ?? 0,
            'account_balances' => $account_balances,
            'cash_position' => $account_balances['asset'] ?? 0
        ];
    }

    /**
     * Integration Health Check
     */
    private function get_integration_health() {
        $health = [
            'fee_to_analytics' => true,
            'fee_to_accounts' => true,
            'sync_status' => 'healthy',
            'last_sync' => time(),
            'pending_entries' => 0
        ];
        
        // Check for unsynced transactions
        $this->db->where('synced_to_accounts', 0);
        $this->db->or_where('synced_to_accounts IS NULL');
        $unsynced = $this->db->count_all_results('daily_fee_transactions');
        
        if ($unsynced > 0) {
            $health['sync_status'] = 'pending';
            $health['pending_entries'] = $unsynced;
            $health['fee_to_accounts'] = false;
        }
        
        return $health;
    }

    // ==================== AUTO-CREATE JOURNAL ENTRIES ====================
    
    /**
     * Sync all revenue sources to accounts
     * Invoices and Daily Fees (includes transport)
     */
    public function sync_all_revenue() {
        $this->load->library('Financial_integration_hooks');
        
        try {
            $results = [
                'invoice_payments' => 0,
                'daily_fees' => 0,
                'total_amount' => 0,
                'errors' => []
            ];
            
            // Sync invoice payments
            $this->db->where('synced_to_accounts', 0);
            $this->db->or_where('synced_to_accounts IS NULL');
            $payments = $this->db->get('payment')->result();
            
            foreach ($payments as $payment) {
                $result = $this->financial_integration_hooks->sync_invoice_payment($payment->payment_id);
                if ($result['status'] == 'success') {
                    $results['invoice_payments']++;
                    $results['total_amount'] += $payment->amount;
                } elseif ($result['status'] == 'error') {
                    $results['errors'][] = "Invoice Payment {$payment->payment_id}: {$result['message']}";
                }
            }
            
            // Sync daily fees (includes feeding, classes, water, breakfast, transport)
            $this->db->where('synced_to_accounts', 0);
            $this->db->or_where('synced_to_accounts IS NULL');
            $daily_fees = $this->db->get('daily_fee_transactions')->result();
            
            foreach ($daily_fees as $fee) {
                $result = $this->financial_integration_hooks->sync_daily_fee($fee->transaction_id);
                if ($result['status'] == 'success') {
                    $results['daily_fees']++;
                    $results['total_amount'] += $fee->total_amount;
                } elseif ($result['status'] == 'error') {
                    $results['errors'][] = "Daily Fee {$fee->transaction_id}: {$result['message']}";
                }
            }
            
            $total_synced = $results['invoice_payments'] + $results['daily_fees'];
            
            if ($total_synced > 0) {
                $message = get_phrase('synced') . " {$total_synced} " . get_phrase('transactions') . " (" . get_phrase('invoices') . ": {$results['invoice_payments']}, " . get_phrase('daily_fees') . ": {$results['daily_fees']}) - " . get_phrase('total') . ": GHS " . number_format($results['total_amount'], 2);
                echo json_encode(['status' => 'success', 'message' => $message, 'details' => $results]);
            } else {
                echo json_encode(['status' => 'success', 'message' => get_phrase('no_transactions_to_sync')]);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('sync_failed') . ': ' . $e->getMessage()]);
        }
    }
    
    /**
     * Automatically create journal entries from fee transactions
     * Called via AJAX or cron job
     */
    public function auto_create_journal_entries() {
        $this->db->trans_start();
        
        try {
            // Get unsynced transactions
            $this->db->where('synced_to_accounts', 0);
            $this->db->or_where('synced_to_accounts IS NULL');
            $this->db->order_by('payment_date', 'ASC');
            $transactions = $this->db->get('daily_fee_transactions')->result_array();
            
            $created_count = 0;
            $total_amount = 0;
            
            foreach ($transactions as $trans) {
                $result = $this->create_journal_entry_from_transaction($trans);
                if ($result['status'] == 'success') {
                    $created_count++;
                    $total_amount += $trans['total_amount'];
                    
                    // Mark as synced
                    $this->db->where('transaction_id', $trans['transaction_id']);
                    $this->db->update('daily_fee_transactions', [
                        'synced_to_accounts' => 1,
                        'journal_entry_id' => $result['entry_id'],
                        'synced_at' => time()
                    ]);
                }
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => "$created_count journal entries created successfully",
                'entries_created' => $created_count,
                'total_amount' => $total_amount
            ]);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create journal entry from a single transaction
     */
    private function create_journal_entry_from_transaction($trans) {
        // Get student info
        $student = $this->db->get_where('student', ['student_id' => $trans['student_id']])->row();
        
        // Determine accounts based on payment method
        $cash_account = $this->get_account_by_code('1010'); // Cash
        $momo_account = $this->get_account_by_code('1020'); // Mobile Money
        $bank_account = $this->get_account_by_code('1030'); // Bank
        
        $debit_account = null;
        switch ($trans['payment_method']) {
            case 1: $debit_account = $cash_account; break;
            case 2: $debit_account = $momo_account; break;
            case 3: $debit_account = $bank_account; break;
            default: $debit_account = $cash_account;
        }
        
        // Revenue accounts
        $feeding_revenue = $this->get_account_by_code('4010'); // Feeding Revenue
        $breakfast_revenue = $this->get_account_by_code('4020'); // Breakfast Revenue
        $classes_revenue = $this->get_account_by_code('4030'); // Classes Revenue
        $water_revenue = $this->get_account_by_code('4040'); // Water Revenue
        $transport_revenue = $this->get_account_by_code('4050'); // Transport Revenue
        
        // Create journal entry
        $entry_number = 'JE-FC-' . date('Ymd') . '-' . $trans['transaction_id'];
        $description = "Fee Collection - {$student->name} ({$trans['receipt_number']})";
        
        $entry_data = [
            'entry_number' => $entry_number,
            'entry_date' => date('Y-m-d', $trans['payment_date']),
            'description' => $description,
            'source_type' => 'fee_collection',
            'source_id' => $trans['transaction_id'],
            'created_by' => $trans['collected_by'],
            'status' => 'posted',
            'created_at' => time()
        ];
        
        $this->db->insert('journal_entries', $entry_data);
        $entry_id = $this->db->insert_id();
        
        // Create journal lines
        $lines = [];
        
        // Debit: Cash/Bank/MoMo (Asset increases)
        $lines[] = [
            'entry_id' => $entry_id,
            'account_id' => $debit_account['account_id'],
            'debit_amount' => $trans['total_amount'],
            'credit_amount' => 0,
            'description' => $description
        ];
        
        // Credits: Revenue accounts (Revenue increases)
        if ($trans['feeding_amount'] > 0) {
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $feeding_revenue['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $trans['feeding_amount'],
                'description' => 'Feeding fees'
            ];
        }
        
        if ($trans['breakfast_amount'] > 0) {
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $breakfast_revenue['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $trans['breakfast_amount'],
                'description' => 'Breakfast fees'
            ];
        }
        
        if ($trans['classes_amount'] > 0) {
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $classes_revenue['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $trans['classes_amount'],
                'description' => 'Classes fees'
            ];
        }
        
        if ($trans['water_amount'] > 0) {
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $water_revenue['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $trans['water_amount'],
                'description' => 'Water fees'
            ];
        }
        
        if ($trans['transport_amount'] > 0) {
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $transport_revenue['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $trans['transport_amount'],
                'description' => 'Transport fees'
            ];
        }
        
        $this->db->insert_batch('journal_entry_lines', $lines);
        
        // Update account balances
        $this->update_account_balance($debit_account['account_id'], $trans['total_amount'], 'debit');
        if ($trans['feeding_amount'] > 0) $this->update_account_balance($feeding_revenue['account_id'], $trans['feeding_amount'], 'credit');
        if ($trans['breakfast_amount'] > 0) $this->update_account_balance($breakfast_revenue['account_id'], $trans['breakfast_amount'], 'credit');
        if ($trans['classes_amount'] > 0) $this->update_account_balance($classes_revenue['account_id'], $trans['classes_amount'], 'credit');
        if ($trans['water_amount'] > 0) $this->update_account_balance($water_revenue['account_id'], $trans['water_amount'], 'credit');
        if ($trans['transport_amount'] > 0) $this->update_account_balance($transport_revenue['account_id'], $trans['transport_amount'], 'credit');
        
        // Update journal entry totals
        $this->db->where('entry_id', $entry_id);
        $this->db->update('journal_entries', [
            'total_debit' => $trans['total_amount'],
            'total_credit' => $trans['total_amount']
        ]);
        
        return ['status' => 'success', 'entry_id' => $entry_id];
    }

    /**
     * Get account by code
     */
    private function get_account_by_code($code) {
        $account = $this->db->get_where('chart_of_accounts', ['account_code' => $code])->row_array();
        if (!$account) {
            // Create default account if not exists
            $account = $this->create_default_account($code);
        }
        return $account;
    }

    /**
     * Update account balance
     */
    private function update_account_balance($account_id, $amount, $type) {
        $account = $this->db->get_where('chart_of_accounts', ['account_id' => $account_id])->row();
        
        if ($type == 'debit') {
            // Assets and Expenses increase with debit
            if (in_array($account->account_type, ['asset', 'expense'])) {
                $new_balance = $account->current_balance + $amount;
            } else {
                $new_balance = $account->current_balance - $amount;
            }
        } else {
            // Liabilities, Equity, Revenue increase with credit
            if (in_array($account->account_type, ['liability', 'equity', 'revenue'])) {
                $new_balance = $account->current_balance + $amount;
            } else {
                $new_balance = $account->current_balance - $amount;
            }
        }
        
        $this->db->where('account_id', $account_id);
        $this->db->update('chart_of_accounts', ['current_balance' => $new_balance]);
    }

    /**
     * Create default accounts if they don't exist
     */
    private function create_default_account($code) {
        $defaults = [
            '1010' => ['name' => 'Cash on Hand', 'type' => 'asset'],
            '1020' => ['name' => 'Mobile Money Account', 'type' => 'asset'],
            '1030' => ['name' => 'Bank Account', 'type' => 'asset'],
            '4010' => ['name' => 'Feeding Fee Revenue', 'type' => 'revenue'],
            '4020' => ['name' => 'Breakfast Fee Revenue', 'type' => 'revenue'],
            '4030' => ['name' => 'Classes Fee Revenue', 'type' => 'revenue'],
            '4040' => ['name' => 'Water Fee Revenue', 'type' => 'revenue'],
            '4050' => ['name' => 'Transport Fee Revenue', 'type' => 'revenue']
        ];
        
        if (isset($defaults[$code])) {
            $data = [
                'account_code' => $code,
                'account_name' => $defaults[$code]['name'],
                'account_type' => $defaults[$code]['type'],
                'current_balance' => 0,
                'is_active' => 1,
                'created_at' => time()
            ];
            $this->db->insert('chart_of_accounts', $data);
            return $this->db->get_where('chart_of_accounts', ['account_code' => $code])->row_array();
        }
        
        return null;
    }

    // ==================== HELPER METHODS ====================
    
    private function calculate_period_dates($period) {
        switch ($period) {
            case 'today':
                return [
                    'start' => strtotime(date('Y-m-d 00:00:00')),
                    'end' => strtotime(date('Y-m-d 23:59:59'))
                ];
            case 'week':
                return [
                    'start' => strtotime('monday this week 00:00:00'),
                    'end' => strtotime('sunday this week 23:59:59')
                ];
            case 'month':
                return [
                    'start' => strtotime(date('Y-m-01 00:00:00')),
                    'end' => strtotime(date('Y-m-t 23:59:59'))
                ];
            case 'quarter':
                $month = date('n');
                $quarter_start = floor(($month - 1) / 3) * 3 + 1;
                return [
                    'start' => strtotime(date('Y') . '-' . $quarter_start . '-01 00:00:00'),
                    'end' => strtotime(date('Y') . '-' . ($quarter_start + 2) . '-' . date('t', strtotime(date('Y') . '-' . ($quarter_start + 2) . '-01')) . ' 23:59:59')
                ];
            case 'year':
                return [
                    'start' => strtotime(date('Y-01-01 00:00:00')),
                    'end' => strtotime(date('Y-12-31 23:59:59'))
                ];
            default:
                return [
                    'start' => strtotime(date('Y-m-d 00:00:00')),
                    'end' => strtotime(date('Y-m-d 23:59:59'))
                ];
        }
    }

    private function get_revenue_trends($dates) {
        $days = min(ceil(($dates['end'] - $dates['start']) / 86400), 30);
        $trends = [];
        
        for ($i = 0; $i < $days; $i++) {
            $day_start = $dates['start'] + ($i * 86400);
            $day_end = $day_start + 86399;
            
            $this->db->select('SUM(total_amount) as amount');
            $this->db->where('payment_date >=', $day_start);
            $this->db->where('payment_date <=', $day_end);
            $result = $this->db->get('daily_fee_transactions')->row();
            
            $trends[] = [
                'date' => date('M d', $day_start),
                'amount' => $result ? (float)$result->amount : 0
            ];
        }
        
        return $trends;
    }

    private function calculate_growth_rate($dates) {
        $current_total = $this->db->select('SUM(total_amount) as total')
            ->where('payment_date >=', $dates['start'])
            ->where('payment_date <=', $dates['end'])
            ->get('daily_fee_transactions')->row()->total ?? 0;
        
        $duration = $dates['end'] - $dates['start'];
        $prev_start = $dates['start'] - $duration;
        $prev_end = $dates['start'] - 1;
        
        $prev_total = $this->db->select('SUM(total_amount) as total')
            ->where('payment_date >=', $prev_start)
            ->where('payment_date <=', $prev_end)
            ->get('daily_fee_transactions')->row()->total ?? 0;
        
        if ($prev_total > 0) {
            return round((($current_total - $prev_total) / $prev_total) * 100, 2);
        }
        
        return 0;
    }

    // ==================== INVOICE BILLING INTEGRATION ====================
    
    /**
     * Sync single invoice payment
     */
    public function sync_invoice_payment($payment_id = null) {
        $this->load->library('Financial_integration_hooks');
        
        if (!$payment_id) {
            $payment_id = $this->input->post('payment_id');
        }
        
        $result = $this->financial_integration_hooks->sync_invoice_payment($payment_id);
        echo json_encode($result);
    }
    
    /**
     * Sync single daily fee transaction
     */
    public function sync_daily_fee($transaction_id = null) {
        $this->load->library('Financial_integration_hooks');
        
        if (!$transaction_id) {
            $transaction_id = $this->input->post('transaction_id');
        }
        
        $result = $this->financial_integration_hooks->sync_daily_fee($transaction_id);
        echo json_encode($result);
    }
    
}
