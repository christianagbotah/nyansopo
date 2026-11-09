<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Finance Integration Helper
 * Automatically syncs invoices/payments to accounts and ledger
 */

if (!function_exists('sync_payment_to_accounts')) {
    function sync_payment_to_accounts($payment_id) {
        $CI =& get_instance();
        $CI->load->model('Finance_model');
        return $CI->Finance_model->sync_payment_to_accounts($payment_id);
    }
}

if (!function_exists('sync_invoice_to_ledger')) {
    function sync_invoice_to_ledger($invoice_code, $student_id) {
        $CI =& get_instance();
        $CI->load->model('Finance_model');
        return $CI->Finance_model->update_student_ledger_for_invoice($invoice_code, $student_id);
    }
}

if (!function_exists('sync_payment_to_ledger')) {
    function sync_payment_to_ledger($payment_id) {
        $CI =& get_instance();
        $CI->load->model('Finance_model');
        return $CI->Finance_model->update_student_ledger_for_payment($payment_id);
    }
}

if (!function_exists('sync_discount_to_ledger')) {
    function sync_discount_to_ledger($invoice_code, $student_id, $discount_amount) {
        $CI =& get_instance();
        $CI->load->model('Finance_model');
        return $CI->Finance_model->update_student_ledger_for_discount($invoice_code, $student_id, $discount_amount);
    }
}

if (!function_exists('get_student_ledger_balance')) {
    function get_student_ledger_balance($student_id) {
        $CI =& get_instance();
        $CI->load->model('Finance_model');
        return $CI->Finance_model->get_student_ledger_balance($student_id);
    }
}
