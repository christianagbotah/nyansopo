<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Fee Analytics & Forecasting
 * For financial planning and revenue projections
 */

if (!function_exists('get_revenue_forecast')) {
    function get_revenue_forecast($year, $term) {
        $CI =& get_instance();
        
        // Get total enrolled students
        $CI->db->where(['year' => $year, 'term' => $term, 'mute' => 0]);
        $total_students = $CI->db->count_all_results('enroll');
        
        // Get average daily rates across all classes
        $CI->db->select_avg('feeding_rate');
        $CI->db->select_avg('classes_rate');
        $CI->db->select_avg('transport_both_rate');
        $CI->db->where(['year' => $year, 'term' => $term]);
        $avg_rates = $CI->db->get('daily_fee_rates')->row_array();
        
        // Calculate school days in term (assume 60 days per term)
        $school_days = 60;
        
        $forecast = [
            'feeding_revenue' => $total_students * ($avg_rates['feeding_rate'] ?? 0) * $school_days,
            'classes_revenue' => $total_students * ($avg_rates['classes_rate'] ?? 0) * $school_days,
            'transport_revenue' => $total_students * 0.7 * ($avg_rates['transport_both_rate'] ?? 0) * $school_days, // 70% use transport
            'total_projected' => 0
        ];
        
        $forecast['total_projected'] = $forecast['feeding_revenue'] + $forecast['classes_revenue'] + $forecast['transport_revenue'];
        
        return $forecast;
    }
}

if (!function_exists('get_collection_efficiency')) {
    function get_collection_efficiency($year, $term) {
        $CI =& get_instance();
        
        // Total billed (from attendance records)
        $CI->db->select_sum('feeding_charged');
        $CI->db->select_sum('classes_charged');
        $CI->db->select_sum('transport_charged');
        $CI->db->join('enroll', 'daily_charge_log.student_id = enroll.student_id');
        $CI->db->where(['enroll.year' => $year, 'enroll.term' => $term]);
        $billed = $CI->db->get('daily_charge_log')->row_array();
        
        $total_billed = ($billed['feeding_charged'] ?? 0) + ($billed['classes_charged'] ?? 0) + ($billed['transport_charged'] ?? 0);
        
        // Total collected
        $CI->db->select_sum('total_amount');
        $CI->db->join('enroll', 'daily_fee_transactions.student_id = enroll.student_id');
        $CI->db->where(['enroll.year' => $year, 'enroll.term' => $term]);
        $collected = $CI->db->get('daily_fee_transactions')->row()->total_amount ?? 0;
        
        $efficiency = $total_billed > 0 ? ($collected / $total_billed) * 100 : 0;
        
        return [
            'total_billed' => $total_billed,
            'total_collected' => $collected,
            'outstanding' => $total_billed - $collected,
            'collection_rate' => round($efficiency, 2)
        ];
    }
}

if (!function_exists('get_payment_behavior_analysis')) {
    function get_payment_behavior_analysis($student_id) {
        $CI =& get_instance();
        
        // Get payment history
        $CI->db->where('student_id', $student_id);
        $CI->db->order_by('payment_date', 'DESC');
        $CI->db->limit(10);
        $payments = $CI->db->get('daily_fee_transactions')->result_array();
        
        if (empty($payments)) {
            return ['status' => 'no_history', 'risk_level' => 'unknown'];
        }
        
        // Calculate payment frequency
        $payment_dates = array_column($payments, 'payment_date');
        $intervals = [];
        for ($i = 0; $i < count($payment_dates) - 1; $i++) {
            $intervals[] = ($payment_dates[$i] - $payment_dates[$i + 1]) / 86400; // days
        }
        
        $avg_interval = !empty($intervals) ? array_sum($intervals) / count($intervals) : 0;
        
        // Get current arrears
        $wallet = $CI->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row_array();
        $total_arrears = ($wallet['feeding_arrears'] ?? 0) + ($wallet['classes_arrears'] ?? 0) + ($wallet['transport_arrears'] ?? 0);
        
        // Risk assessment
        $risk_level = 'low';
        if ($total_arrears > 100) $risk_level = 'high';
        elseif ($total_arrears > 50) $risk_level = 'medium';
        
        return [
            'avg_payment_interval_days' => round($avg_interval, 1),
            'total_payments' => count($payments),
            'current_arrears' => $total_arrears,
            'risk_level' => $risk_level,
            'payment_type_preference' => $payments[0]['payment_type'] ?? 'mixed'
        ];
    }
}
