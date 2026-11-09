<?php
/**
 * Fee System Bridge Helper
 * 
 * Provides compatibility functions to work with both old and new fee systems
 * Old: feeding_fee_payment, classes_fee_payment, transport_fare_payment
 * New: daily_fee_transactions, daily_fee_wallet, bus_attendance
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Get total collected for a fee type in a date range
 * Queries both old and new systems
 */
function get_fee_collected($fee_type, $start_date, $end_date, $year = null, $term = null) {
    $CI =& get_instance();
    $total = 0;
    
    // Query new system
    $amount_field = $fee_type . '_amount';
    $new_total = $CI->db->select_sum($amount_field)
        ->where('payment_date >=', $start_date)
        ->where('payment_date <=', $end_date)
        ->get('daily_fee_transactions')->row()->$amount_field ?? 0;
    $total += $new_total;
    
    // Query old system (if exists)
    $old_table = $fee_type . '_fee_payment';
    if ($CI->db->table_exists($old_table)) {
        $old_query = $CI->db->select_sum('amount')
            ->where('day_timestamp >=', $start_date)
            ->where('day_timestamp <=', $end_date);
        if ($year) $old_query->where('year', $year);
        if ($term) $old_query->where('term', $term);
        $old_total = $old_query->get($old_table)->row()->amount ?? 0;
        $total += $old_total;
    }
    
    return $total;
}

/**
 * Get student arrears from both systems
 */
function get_student_arrears($student_id, $fee_type = 'all') {
    $CI =& get_instance();
    $arrears = 0;
    
    // Check new system
    $wallet = $CI->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
    if ($wallet) {
        if ($fee_type == 'all') {
            $arrears += $wallet->feeding_arrears + $wallet->classes_arrears + 
                       $wallet->transport_arrears + $wallet->breakfast_arrears + $wallet->water_arrears;
        } else {
            $arrears += $wallet->{$fee_type . '_arrears'} ?? 0;
        }
    }
    
    // Check old system
    if ($fee_type == 'all' || $fee_type == 'feeding') {
        $old_feeding = $CI->db->select_sum('due')
            ->where('student_id', $student_id)
            ->get('feeding_fee_payment')->row()->due ?? 0;
        $arrears += $old_feeding;
    }
    
    if ($fee_type == 'all' || $fee_type == 'classes') {
        $old_classes = $CI->db->select_sum('due')
            ->where('student_id', $student_id)
            ->get('classes_fee_payment')->row()->due ?? 0;
        $arrears += $old_classes;
    }
    
    if ($fee_type == 'all' || $fee_type == 'transport') {
        $old_transport = $CI->db->select_sum('due')
            ->where('student_id', $student_id)
            ->get('transport_fare_payment')->row()->due ?? 0;
        $arrears += $old_transport;
    }
    
    return $arrears;
}

/**
 * Get payment receipts from both systems for a date
 */
function get_payment_receipts_for_date($date_timestamp, $fee_type = 'all', $class_id = null, $student_name = null) {
    $CI =& get_instance();
    $payments = [];
    
    // Get from new system
    $CI->db->select('t.*, student.name as student_name, student.student_code, class.name as class_name, class.name_numeric');
    $CI->db->from('daily_fee_transactions t');
    $CI->db->join('student', 'student.student_id = t.student_id');
    $CI->db->join('enroll', 'enroll.student_id = t.student_id');
    $CI->db->join('class', 'class.class_id = enroll.class_id');
    $CI->db->where('t.payment_date >=', $date_timestamp);
    $CI->db->where('t.payment_date <', $date_timestamp + 86400);
    if ($class_id) $CI->db->where('enroll.class_id', $class_id);
    if ($student_name) $CI->db->like('student.name', $student_name);
    
    $transactions = $CI->db->get()->result_array();
    
    // Expand into individual items
    foreach ($transactions as $trans) {
        if ($trans['feeding_amount'] > 0 && ($fee_type == 'all' || $fee_type == 'feeding')) {
            $payments[] = array_merge($trans, ['title' => 'Feeding Fee', 'amount' => $trans['feeding_amount']]);
        }
        if ($trans['classes_amount'] > 0 && ($fee_type == 'all' || $fee_type == 'classes')) {
            $payments[] = array_merge($trans, ['title' => 'Classes Fee', 'amount' => $trans['classes_amount']]);
        }
        if ($trans['transport_amount'] > 0 && ($fee_type == 'all' || $fee_type == 'transport')) {
            $payments[] = array_merge($trans, ['title' => 'Transport Fare', 'amount' => $trans['transport_amount']]);
        }
        if ($trans['breakfast_amount'] > 0 && ($fee_type == 'all' || $fee_type == 'breakfast')) {
            $payments[] = array_merge($trans, ['title' => 'Breakfast', 'amount' => $trans['breakfast_amount']]);
        }
        if ($trans['water_amount'] > 0 && ($fee_type == 'all' || $fee_type == 'water')) {
            $payments[] = array_merge($trans, ['title' => 'Water', 'amount' => $trans['water_amount']]);
        }
    }
    
    // Get from old system (if needed)
    $old_tables = [
        'feeding' => 'feeding_fee_payment',
        'classes' => 'classes_fee_payment',
        'transport' => 'transport_fare_payment'
    ];
    
    foreach ($old_tables as $type => $table) {
        if ($fee_type != 'all' && $fee_type != $type) continue;
        
        if ($CI->db->table_exists($table)) {
            $CI->db->select("$table.*, student.name as student_name, student.student_code, class.name as class_name, class.name_numeric");
            $CI->db->from($table);
            $CI->db->join('student', "student.student_id = $table.student_id");
            $CI->db->join('class', "class.class_id = $table.class_id");
            $CI->db->where("$table.day_timestamp", $date_timestamp);
            $CI->db->where("$table.amount >", 0);
            if ($class_id) $CI->db->where("$table.class_id", $class_id);
            if ($student_name) $CI->db->like('student.name', $student_name);
            
            $old_payments = $CI->db->get()->result_array();
            foreach ($old_payments as $old) {
                $old['title'] = ucfirst($type) . ' Fee';
                $payments[] = $old;
            }
        }
    }
    
    return $payments;
}

/**
 * Check which system to use for new transactions
 * Returns 'new' or 'old'
 */
function get_active_fee_system() {
    $CI =& get_instance();
    
    // Check if new system tables exist and are being used
    if ($CI->db->table_exists('daily_fee_transactions') && 
        $CI->db->table_exists('daily_fee_wallet')) {
        
        // Check if there are recent transactions in new system
        $recent = $CI->db->where('payment_date >', time() - (30 * 86400))
            ->count_all_results('daily_fee_transactions');
        
        if ($recent > 0) {
            return 'new';
        }
    }
    
    // Default to old system
    return 'old';
}
