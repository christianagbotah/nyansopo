<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Get daily fee statistics for a specific date
 * Returns payments, outstanding, and payables
 */
function get_daily_fee_stats_by_date($date_timestamp) {
    $CI =& get_instance();
    $currency = $CI->db->get_where('settings', ['type' => 'currency'])->row()->description;
    
    // Get payments for the date
    $payments = $CI->db->select('
        SUM(feeding_amount) as feeding_paid,
        SUM(classes_amount) as classes_paid,
        SUM(transport_amount) as transport_paid,
        SUM(breakfast_amount) as breakfast_paid,
        SUM(water_amount) as water_paid
    ')
    ->where('payment_date >=', $date_timestamp)
    ->where('payment_date <', $date_timestamp + 86400)
    ->get('daily_fee_transactions')->row();
    
    // Get outstanding from attendance charges not yet paid
    $running_year = $CI->db->get_where('settings', ['type' => 'running_year'])->row()->description;
    $running_term = $CI->db->get_where('settings', ['type' => 'running_term'])->row()->description;
    
    $outstanding = $CI->db->select('
        SUM(feeding_charged) as feeding_owe,
        SUM(classes_charged) as classes_owe,
        SUM(transport_charged) as transport_owe,
        SUM(breakfast_charged) as breakfast_owe,
        SUM(water_charged) as water_owe
    ')
    ->where('attendance_date', $date_timestamp)
    ->where('payment_status', 'unpaid')
    ->where('year', $running_year)
    ->where('term', $running_term)
    ->get('attendance')->row();
    
    // Get payables (prepaid balances - negative arrears means credit)
    $payables = $CI->db->query("
        SELECT 
            SUM(CASE WHEN feeding_balance > 0 THEN feeding_balance ELSE 0 END) as feeding_payable,
            SUM(CASE WHEN classes_balance > 0 THEN classes_balance ELSE 0 END) as classes_payable,
            SUM(CASE WHEN transport_balance > 0 THEN transport_balance ELSE 0 END) as transport_payable,
            SUM(CASE WHEN breakfast_balance > 0 THEN breakfast_balance ELSE 0 END) as breakfast_payable,
            SUM(CASE WHEN water_balance > 0 THEN water_balance ELSE 0 END) as water_payable
        FROM daily_fee_wallet
        JOIN enroll ON daily_fee_wallet.student_id = enroll.student_id
        WHERE enroll.year = '$running_year' AND enroll.term = '$running_term'
    ")->row();
    
    $stats = [
        'date_chosen' => date('l, F d, Y', $date_timestamp),
        'timestamp' => $date_timestamp
    ];
    
    if (is_fee_module_enabled('feeding')) {
        $stats['total_feeding_paid'] = $currency . number_format($payments->feeding_paid ?? 0, 2);
        $stats['total_feeding_owe'] = $currency . number_format($outstanding->feeding_owe ?? 0, 2);
        $stats['total_feeding_payable'] = $currency . number_format($payables->feeding_payable ?? 0, 2);
    }
    
    if (is_fee_module_enabled('classes')) {
        $stats['total_classes_paid'] = $currency . number_format($payments->classes_paid ?? 0, 2);
        $stats['total_classes_owe'] = $currency . number_format($outstanding->classes_owe ?? 0, 2);
        $stats['total_classes_payable'] = $currency . number_format($payables->classes_payable ?? 0, 2);
    }
    
    if (is_fee_module_enabled('transport')) {
        $stats['total_fare_paid'] = $currency . number_format($payments->transport_paid ?? 0, 2);
        $stats['total_fare_owe'] = $currency . number_format($outstanding->transport_owe ?? 0, 2);
        $stats['total_transport_payable'] = $currency . number_format($payables->transport_payable ?? 0, 2);
    }
    
    return $stats;
}

/**
 * Get daily fee statistics for a term
 */
function get_daily_fee_stats_by_term($year, $term) {
    $CI =& get_instance();
    $currency = $CI->db->get_where('settings', ['type' => 'currency'])->row()->description;
    
    // Get year start and end timestamps
    $year_start = strtotime($year . '-01-01');
    $year_end = strtotime($year . '-12-31 23:59:59');
    
    // Get payments for the term
    $payments = $CI->db->select('
        SUM(feeding_amount) as feeding_paid,
        SUM(classes_amount) as classes_paid,
        SUM(transport_amount) as transport_paid
    ')
    ->where('payment_date >=', $year_start)
    ->where('payment_date <=', $year_end)
    ->get('daily_fee_transactions')->row();
    
    // Get outstanding from wallet
    $outstanding = $CI->db->select('
        SUM(feeding_arrears) as feeding_owe,
        SUM(classes_arrears) as classes_owe,
        SUM(transport_arrears) as transport_owe
    ')
    ->join('enroll', 'daily_fee_wallet.student_id = enroll.student_id')
    ->where('enroll.year', $year)
    ->where('enroll.term', $term)
    ->get('daily_fee_wallet')->row();
    
    // Get payables
    $payables = $CI->db->query("
        SELECT 
            SUM(CASE WHEN feeding_balance > 0 THEN feeding_balance ELSE 0 END) as feeding_payable,
            SUM(CASE WHEN classes_balance > 0 THEN classes_balance ELSE 0 END) as classes_payable,
            SUM(CASE WHEN transport_balance > 0 THEN transport_balance ELSE 0 END) as transport_payable
        FROM daily_fee_wallet
        JOIN enroll ON daily_fee_wallet.student_id = enroll.student_id
        WHERE enroll.year = '$year' AND enroll.term = '$term'
    ")->row();
    
    $stats = [
        'duration_chosen' => 'Term ' . $term . ', ' . $year,
        'year' => $year,
        'term' => $term
    ];
    
    if (is_fee_module_enabled('feeding')) {
        $stats['total_feeding_paid'] = $currency . number_format($payments->feeding_paid ?? 0, 2);
        $stats['total_feeding_owe'] = $currency . number_format($outstanding->feeding_owe ?? 0, 2);
        $stats['total_feeding_payable'] = $currency . number_format($payables->feeding_payable ?? 0, 2);
    }
    
    if (is_fee_module_enabled('classes')) {
        $stats['total_classes_paid'] = $currency . number_format($payments->classes_paid ?? 0, 2);
        $stats['total_classes_owe'] = $currency . number_format($outstanding->classes_owe ?? 0, 2);
        $stats['total_classes_payable'] = $currency . number_format($payables->classes_payable ?? 0, 2);
    }
    
    if (is_fee_module_enabled('transport')) {
        $stats['total_fare_paid'] = $currency . number_format($payments->transport_paid ?? 0, 2);
        $stats['total_fare_owe'] = $currency . number_format($outstanding->transport_owe ?? 0, 2);
        $stats['total_transport_payable'] = $currency . number_format($payables->transport_payable ?? 0, 2);
    }
    
    return $stats;
}
