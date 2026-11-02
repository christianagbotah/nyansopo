<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auto-Lock Helper Functions
 * For Invoice & Payment Security System
 */

if (!function_exists('lock_invoice')) {
    /**
     * Lock an invoice
     */
    function lock_invoice($invoice_id, $reason = 'Auto-locked for security') {
        $CI =& get_instance();
        
        $CI->db->where('invoice_id', $invoice_id);
        $CI->db->update('invoice', [
            'can_edit' => 'declined',
            'can_delete' => 'declined',
            'locked_at' => date('Y-m-d H:i:s'),
            'locked_reason' => $reason
        ]);
        
        log_audit('invoice', $invoice_id, 'lock', $reason);
    }
}

if (!function_exists('lock_payment')) {
    /**
     * Lock a payment
     */
    function lock_payment($payment_id, $reason = 'Payment records are locked for security') {
        $CI =& get_instance();
        
        $CI->db->where('payment_id', $payment_id);
        $CI->db->update('payment', [
            'can_edit' => 'declined',
            'can_delete' => 'declined',
            'locked_at' => date('Y-m-d H:i:s'),
            'locked_reason' => $reason
        ]);
        
        log_audit('payment', $payment_id, 'lock', $reason);
    }
}

if (!function_exists('lock_payments_by_receipt')) {
    /**
     * Lock all payments with a specific receipt code
     */
    function lock_payments_by_receipt($receipt_code, $reason = 'Payment records are locked for security') {
        $CI =& get_instance();
        
        $CI->db->where('receipt_code', $receipt_code);
        $CI->db->update('payment', [
            'can_edit' => 'declined',
            'can_delete' => 'declined',
            'locked_at' => date('Y-m-d H:i:s'),
            'locked_reason' => $reason
        ]);
    }
}

if (!function_exists('is_auto_lock_enabled')) {
    /**
     * Check if auto-lock is enabled
     */
    function is_auto_lock_enabled() {
        $CI =& get_instance();
        $setting = $CI->db->get_where('settings', ['type' => 'auto_lock_enabled'])->row();
        return $setting && $setting->description == 'yes';
    }
}

if (!function_exists('log_audit')) {
    /**
     * Log audit trail
     */
    function log_audit($record_type, $record_id, $action, $notes = null) {
        $CI =& get_instance();
        
        // Check if table exists
        if (!$CI->db->table_exists('invoice_payment_audit_log')) {
            return;
        }
        
        $CI->db->insert('invoice_payment_audit_log', [
            'record_type' => $record_type,
            'record_id' => $record_id,
            'action' => $action,
            'performed_by' => $CI->session->userdata('login_user_id'),
            'notes' => $notes,
            'ip_address' => $CI->input->ip_address()
        ]);
    }
}
