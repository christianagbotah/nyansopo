<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Automated Alerts for Daily Fees
 * Critical for proactive financial management
 */

if (!function_exists('check_low_balance_alerts')) {
    function check_low_balance_alerts($threshold = 20) {
        $CI =& get_instance();
        
        $CI->db->select('student_id, feeding_balance, classes_balance, transport_balance');
        $CI->db->where("(feeding_balance < $threshold AND feeding_balance > 0) OR (classes_balance < $threshold AND classes_balance > 0) OR (transport_balance < $threshold AND transport_balance > 0)");
        $low_balance_students = $CI->db->get('daily_fee_wallet')->result_array();
        
        return $low_balance_students;
    }
}

if (!function_exists('check_high_arrears_alerts')) {
    function check_high_arrears_alerts($threshold = 100) {
        $CI =& get_instance();
        
        $CI->db->select('student_id, feeding_arrears, classes_arrears, transport_arrears');
        $CI->db->where("(feeding_arrears + classes_arrears + transport_arrears) > $threshold");
        $high_arrears_students = $CI->db->get('daily_fee_wallet')->result_array();
        
        return $high_arrears_students;
    }
}

if (!function_exists('get_daily_collection_target_status')) {
    function get_daily_collection_target_status($date, $target_amount) {
        $CI =& get_instance();
        $date_timestamp = is_numeric($date) ? $date : strtotime($date);
        
        $CI->db->select_sum('total_amount');
        $CI->db->where('DATE(FROM_UNIXTIME(payment_date))', date('Y-m-d', $date_timestamp));
        $collected = $CI->db->get('daily_fee_transactions')->row()->total_amount ?? 0;
        
        $percentage = $target_amount > 0 ? ($collected / $target_amount) * 100 : 0;
        
        return [
            'target' => $target_amount,
            'collected' => $collected,
            'remaining' => $target_amount - $collected,
            'percentage' => round($percentage, 2),
            'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 80 ? 'on_track' : 'below_target')
        ];
    }
}

if (!function_exists('get_uncollected_transport_fares')) {
    function get_uncollected_transport_fares($date) {
        $CI =& get_instance();
        $date_timestamp = is_numeric($date) ? $date : strtotime($date);
        
        // Students who boarded but haven't paid
        $CI->db->where('attendance_date', $date_timestamp);
        $CI->db->where('payment_status', 'pending');
        $CI->db->where('transport_direction !=', 'none');
        $unpaid = $CI->db->get('bus_attendance')->result_array();
        
        $total_uncollected = array_sum(array_column($unpaid, 'total_fare'));
        
        return [
            'count' => count($unpaid),
            'total_amount' => $total_uncollected,
            'students' => $unpaid
        ];
    }
}
