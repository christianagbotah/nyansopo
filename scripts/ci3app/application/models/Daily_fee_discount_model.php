<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily fee discount model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Daily_fee_discount_model extends MY_Model {

    /**
     * Get active daily fee discount for student
     */
    public function get_daily_fee_discount($student_id, $fee_type, $year, $term) {
        return $this->db
            ->where('student_id', $student_id)
            ->where('discount_category', 'daily_fees')
            ->where('discount_type', $fee_type)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'approved')
            ->get('invoice_discounts')
            ->row();
    }

    /**
     * Apply discount to fee amount
     */
    public function apply_discount($original_amount, $discount) {
        if (!$discount || $original_amount <= 0) {
            return [
                'discounted_amount' => $original_amount,
                'discount_applied' => 0,
                'discount_info' => null
            ];
        }

        $discount_applied = 0;
        
        if ($discount->discount_method == 'percentage') {
            $discount_applied = ($original_amount * $discount->discount_value) / 100;
        } else {
            $discount_applied = min($discount->discount_value, $original_amount);
        }

        $discounted_amount = max(0, $original_amount - $discount_applied);

        return [
            'discounted_amount' => $discounted_amount,
            'discount_applied' => $discount_applied,
            'discount_info' => $discount->discount_method == 'percentage' 
                ? $discount->discount_value . '% discount' 
                : 'GH₵' . number_format($discount->discount_value, 2) . ' discount'
        ];
    }

    /**
     * Get all daily fee discounts for student
     */
    public function get_student_daily_discounts($student_id, $year, $term) {
        return $this->db
            ->where('student_id', $student_id)
            ->where('discount_category', 'daily_fees')
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'approved')
            ->get('invoice_discounts')
            ->result();
    }

    /**
     * Check if student has 100% discount on specific fee type
     */
    public function has_full_discount($student_id, $fee_type, $year, $term) {
        $discount = $this->db
            ->where('student_id', $student_id)
            ->where('discount_category', 'daily_fees')
            ->where('discount_type', $fee_type)
            ->where('year', $year)
            ->where('term', $term)
            ->where('discount_method', 'percentage')
            ->where('discount_value', 100)
            ->where('status', 'approved')
            ->get('invoice_discounts')
            ->row();
            
        return !empty($discount);
    }
}
