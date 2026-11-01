<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Fee Reconciliation Helper
 * Critical for financial audit and cash management
 */

if (!function_exists('get_daily_reconciliation_report')) {
    function get_daily_reconciliation_report($date, $collector_id = null) {
        $CI =& get_instance();
        $date_timestamp = is_numeric($date) ? $date : strtotime($date);
        
        // Get all transactions for the day
        $CI->db->where('DATE(FROM_UNIXTIME(payment_date))', date('Y-m-d', $date_timestamp));
        if ($collector_id) $CI->db->where('collected_by', $collector_id);
        $transactions = $CI->db->get('daily_fee_transactions')->result_array();
        
        $summary = [
            'total_cash' => 0,
            'total_transactions' => count($transactions),
            'by_fee_type' => [
                'feeding' => 0,
                'breakfast' => 0,
                'classes' => 0,
                'water' => 0,
                'transport' => 0
            ],
            'by_collector' => [],
            'by_payment_method' => [
                'cash' => 0,
                'mobile_money' => 0,
                'bank_transfer' => 0
            ]
        ];
        
        foreach ($transactions as $txn) {
            $summary['total_cash'] += $txn['total_amount'];
            $summary['by_fee_type']['feeding'] += $txn['feeding_amount'];
            $summary['by_fee_type']['breakfast'] += $txn['breakfast_amount'];
            $summary['by_fee_type']['classes'] += $txn['classes_amount'];
            $summary['by_fee_type']['water'] += $txn['water_amount'];
            $summary['by_fee_type']['transport'] += $txn['transport_amount'];
            
            if (!isset($summary['by_collector'][$txn['collected_by']])) {
                $summary['by_collector'][$txn['collected_by']] = 0;
            }
            $summary['by_collector'][$txn['collected_by']] += $txn['total_amount'];
            
            // Get payment method name from database
            $CI->load->helper('payment_method');
            $method_name = get_payment_method_name($txn['payment_method']);
            $method_key = strtolower(str_replace(' ', '_', $method_name));
            
            if (!isset($summary['by_payment_method'][$method_key])) {
                $summary['by_payment_method'][$method_key] = 0;
            }
            $summary['by_payment_method'][$method_key] += $txn['total_amount'];
        }
        
        return $summary;
    }
}

if (!function_exists('get_collector_cash_handover_report')) {
    function get_collector_cash_handover_report($collector_id, $date) {
        $CI =& get_instance();
        $date_timestamp = is_numeric($date) ? $date : strtotime($date);
        
        $CI->db->where('collected_by', $collector_id);
        $CI->db->where('DATE(FROM_UNIXTIME(payment_date))', date('Y-m-d', $date_timestamp));
        $CI->db->where('payment_method', 1); // Cash only
        $transactions = $CI->db->get('daily_fee_transactions')->result_array();
        
        $total_cash = array_sum(array_column($transactions, 'total_amount'));
        
        return [
            'collector_id' => $collector_id,
            'date' => date('Y-m-d', $date_timestamp),
            'total_cash_collected' => $total_cash,
            'transaction_count' => count($transactions),
            'transactions' => $transactions
        ];
    }
}

if (!function_exists('detect_financial_anomalies')) {
    function detect_financial_anomalies($date) {
        $CI =& get_instance();
        $date_timestamp = is_numeric($date) ? $date : strtotime($date);
        $anomalies = [];
        
        // 1. Check for duplicate receipts
        $CI->db->select('receipt_number, COUNT(*) as count');
        $CI->db->where('DATE(FROM_UNIXTIME(payment_date))', date('Y-m-d', $date_timestamp));
        $CI->db->where('receipt_number IS NOT NULL');
        $CI->db->group_by('receipt_number');
        $CI->db->having('count > 1');
        $duplicates = $CI->db->get('daily_fee_transactions')->result_array();
        
        if (!empty($duplicates)) {
            $anomalies[] = [
                'type' => 'duplicate_receipts',
                'severity' => 'high',
                'count' => count($duplicates),
                'details' => $duplicates
            ];
        }
        
        // 2. Check for unusually large transactions
        $CI->db->where('DATE(FROM_UNIXTIME(payment_date))', date('Y-m-d', $date_timestamp));
        $CI->db->where('total_amount > 500'); // Threshold
        $large_txns = $CI->db->get('daily_fee_transactions')->result_array();
        
        if (!empty($large_txns)) {
            $anomalies[] = [
                'type' => 'large_transactions',
                'severity' => 'medium',
                'count' => count($large_txns),
                'details' => $large_txns
            ];
        }
        
        // 3. Check for negative balances (shouldn't happen)
        $CI->db->where('feeding_balance < 0 OR classes_balance < 0 OR transport_balance < 0');
        $negative_balances = $CI->db->get('daily_fee_wallet')->result_array();
        
        if (!empty($negative_balances)) {
            $anomalies[] = [
                'type' => 'negative_balances',
                'severity' => 'critical',
                'count' => count($negative_balances),
                'details' => $negative_balances
            ];
        }
        
        return $anomalies;
    }
}

if (!function_exists('get_arrears_aging_report')) {
    function get_arrears_aging_report() {
        $CI =& get_instance();
        
        $CI->db->select('student_id, feeding_arrears, breakfast_arrears, classes_arrears, water_arrears, transport_arrears, last_updated');
        $CI->db->where('(feeding_arrears > 0 OR breakfast_arrears > 0 OR classes_arrears > 0 OR water_arrears > 0 OR transport_arrears > 0)');
        $wallets = $CI->db->get('daily_fee_wallet')->result_array();
        
        $aging = [
            '0-7_days' => ['count' => 0, 'amount' => 0],
            '8-14_days' => ['count' => 0, 'amount' => 0],
            '15-30_days' => ['count' => 0, 'amount' => 0],
            '30+_days' => ['count' => 0, 'amount' => 0]
        ];
        
        $now = time();
        foreach ($wallets as $wallet) {
            $total_arrears = $wallet['feeding_arrears'] + $wallet['breakfast_arrears'] + 
                           $wallet['classes_arrears'] + $wallet['water_arrears'] + $wallet['transport_arrears'];
            
            $days_old = floor(($now - $wallet['last_updated']) / 86400);
            
            if ($days_old <= 7) {
                $aging['0-7_days']['count']++;
                $aging['0-7_days']['amount'] += $total_arrears;
            } elseif ($days_old <= 14) {
                $aging['8-14_days']['count']++;
                $aging['8-14_days']['amount'] += $total_arrears;
            } elseif ($days_old <= 30) {
                $aging['15-30_days']['count']++;
                $aging['15-30_days']['amount'] += $total_arrears;
            } else {
                $aging['30+_days']['count']++;
                $aging['30+_days']['amount'] += $total_arrears;
            }
        }
        
        return $aging;
    }
}
