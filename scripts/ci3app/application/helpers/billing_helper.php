<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Billing System Helper Functions
 */

// Get invoice summary with discounts
function get_invoice_summary_data($student_id, $invoice_code) {
    $CI =& get_instance();
    return $CI->db->query(
        "SELECT * FROM invoice_summary 
         WHERE student_id = ? AND invoice_code = ?",
        [$student_id, $invoice_code]
    )->row_array();
}

// Calculate discount amount
function calculate_discount($amount, $method, $value) {
    return ($method == 'percentage') ? ($amount * $value / 100) : $value;
}

// Get discount types
function get_discount_types() {
    $CI =& get_instance();
    return $CI->db->get_where('discount_types', ['is_active' => 1])->result_array();
}

// Check if discount requires approval
function discount_requires_approval($discount_type, $amount) {
    $CI =& get_instance();
    $type = $CI->db->get_where('discount_types', ['code' => $discount_type])->row();
    
    if (!$type) return false;
    
    return $type->requires_approval == 1 && $amount >= $type->approval_threshold;
}

// Get SMS log for student
function get_student_sms_log($student_id, $limit = 10) {
    $CI =& get_instance();
    return $CI->db->where('student_id', $student_id)
                   ->order_by('sent_at', 'DESC')
                   ->limit($limit)
                   ->get('sms_log')
                   ->result_array();
}

// Format currency
function format_currency($amount) {
    $CI =& get_instance();
    $currency = $CI->db->get_where('settings', ['type' => 'currency'])->row()->description;
    return $currency . ' ' . number_format($amount, 2);
}

// Get payment status badge
function get_payment_status_badge($status) {
    $badges = [
        'paid' => '<span class="label label-success">Paid</span>',
        'partial' => '<span class="label label-warning">Partial</span>',
        'unpaid' => '<span class="label label-danger">Unpaid</span>'
    ];
    return $badges[$status] ?? $badges['unpaid'];
}

// Get discount summary for invoice
function get_invoice_discounts($student_id, $invoice_code) {
    $CI =& get_instance();
    return $CI->db->where('student_id', $student_id)
                   ->where('invoice_code', $invoice_code)
                   ->where('status', 'approved')
                   ->get('invoice_discounts')
                   ->result_array();
}

// Calculate total discount for invoice
function get_total_discount($student_id, $invoice_code) {
    $CI =& get_instance();
    $result = $CI->db->select_sum('discount_amount')
                     ->where('student_id', $student_id)
                     ->where('invoice_code', $invoice_code)
                     ->where('status', 'approved')
                     ->get('invoice_discounts')
                     ->row();
    return $result->discount_amount ?? 0;
}
