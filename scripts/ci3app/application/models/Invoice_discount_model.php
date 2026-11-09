<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Invoice discount model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Invoice_discount_model extends MY_Model {

    /**
     * Get active invoice discount for student
     * 
     * @param int $student_id Student ID
     * @param string $fee_type Fee type name (School Fees, Admission Fees, etc.)
     * @param int $year Academic year
     * @param int $term Academic term
     * @return object|null Discount object or null
     */
    public function get_invoice_discount($student_id, $fee_type, $year, $term) {
        return $this->db
            ->select('id.*, dt.name as type_name, dt.icon')
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->where('id.student_id', $student_id)
            ->where('dc.code', 'invoice')
            ->where('dt.name', $fee_type)
            ->where('id.year', $year)
            ->where('id.term', $term)
            ->where('id.status', 'approved')
            ->get()
            ->row();
    }

    /**
     * Apply discount to invoice amount
     * 
     * @param float $original_amount Original invoice amount
     * @param object $discount Discount object
     * @return array ['discounted_amount' => float, 'discount_applied' => float, 'discount_info' => string]
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
            $discount_info = $discount->discount_value . '% discount';
        } else {
            $discount_applied = min($discount->discount_value, $original_amount);
            $discount_info = 'GH₵' . number_format($discount->discount_value, 2) . ' discount';
        }

        $discounted_amount = max(0, $original_amount - $discount_applied);

        return [
            'discounted_amount' => $discounted_amount,
            'discount_applied' => $discount_applied,
            'discount_info' => $discount_info,
            'discount_icon' => $discount->icon ?? '🎁'
        ];
    }

    /**
     * Get all invoice discounts for student (for display)
     * 
     * @param int $student_id Student ID
     * @param int $year Academic year
     * @param int $term Academic term
     * @return array Array of discount objects
     */
    public function get_student_invoice_discounts($student_id, $year, $term) {
        return $this->db
            ->select('id.*, dt.name as type_name, dt.icon, dc.name as category_name')
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->where('id.student_id', $student_id)
            ->where('dc.code', 'invoice')
            ->where('id.year', $year)
            ->where('id.term', $term)
            ->where('id.status', 'approved')
            ->get()
            ->result();
    }

    /**
     * Calculate discounted invoice for student
     * 
     * @param int $student_id Student ID
     * @param array $invoice_items Array of invoice items ['school_fees' => amount, 'admission_fees' => amount, ...]
     * @param int $year Academic year
     * @param int $term Academic term
     * @return array Invoice items with discounts applied
     */
    public function calculate_discounted_invoice($student_id, $invoice_items, $year, $term) {
        $result = [];
        $fee_type_map = [
            'school_fees' => 'School Fees',
            'admission_fees' => 'Admission Fees',
            'pta_fees' => 'PTA Fees',
            'examination_fees' => 'Examination Fees',
            'other_fees' => 'Other Fees'
        ];

        foreach ($invoice_items as $item_key => $amount) {
            $fee_type = $fee_type_map[$item_key] ?? null;
            
            if ($fee_type && $amount > 0) {
                $discount = $this->get_invoice_discount($student_id, $fee_type, $year, $term);
                $result[$item_key] = $this->apply_discount($amount, $discount);
                $result[$item_key]['original_amount'] = $amount;
            } else {
                $result[$item_key] = [
                    'original_amount' => $amount,
                    'discounted_amount' => $amount,
                    'discount_applied' => 0,
                    'discount_info' => null
                ];
            }
        }

        return $result;
    }

    /**
     * Check if student has 100% discount on specific fee type
     * 
     * @param int $student_id Student ID
     * @param string $fee_type Fee type name
     * @param int $year Academic year
     * @param int $term Academic term
     * @return bool True if has 100% discount
     */
    public function has_full_discount($student_id, $fee_type, $year, $term) {
        return $this->db
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->where('id.student_id', $student_id)
            ->where('dc.code', 'invoice')
            ->where('dt.name', $fee_type)
            ->where('id.year', $year)
            ->where('id.term', $term)
            ->where('id.discount_method', 'percentage')
            ->where('id.discount_value', 100)
            ->where('id.status', 'approved')
            ->count_all_results() > 0;
    }
}
