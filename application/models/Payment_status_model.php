<?php
/**
 * Payment Status Detection Model
 * 
 * Determines FEEDING payment status for students by checking both direct payments
 * and prepaid balance usage for a specific date.
 * 
 * This model specifically tracks FEEDING payments (lunch/dining), not other fee types
 * like classes, transport, water, or breakfast.
 * 
 * @package     Models
 * @author      School Manager System
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_status_model extends CI_Model {

    /**
     * Check if student has FEEDING payment status for a given date
     * 
     * @param int $student_id Student ID
     * @param string $date Date in YYYY-MM-DD format
     * @return array ['has_payment' => bool, 'payment_type' => string|null]
     */
    public function getPaymentStatus($student_id, $date) {
        // Convert date to Unix timestamp (midnight)
        $timestamp = strtotime($date . ' 00:00:00');
        
        // Check for direct payment
        $has_direct_payment = $this->hasDirectPayment($student_id, $timestamp);
        
        // Check for prepaid usage
        $has_prepaid_usage = $this->hasPrepaidUsage($student_id, $timestamp);
        
        // Determine payment status and type
        if ($has_direct_payment) {
            return [
                'has_payment' => true,
                'payment_type' => 'Direct Payment',
                'payment_date' => $timestamp,
                'source_table' => 'daily_fee_transactions'
            ];
        } elseif ($has_prepaid_usage) {
            return [
                'has_payment' => true,
                'payment_type' => 'Prepaid Balance',
                'payment_date' => $timestamp,
                'source_table' => 'daily_charge_log'
            ];
        } else {
            return [
                'has_payment' => false,
                'payment_type' => null,
                'payment_date' => $timestamp,
                'source_table' => null
            ];
        }
    }

    /**
     * Get payment status for multiple students
     * 
     * @param array $student_ids Array of student IDs
     * @param string $date Date in YYYY-MM-DD format
     * @return array Keyed by student_id with payment status data
     */
    public function getBulkPaymentStatus($student_ids, $date) {
        if (empty($student_ids)) {
            return [];
        }
        
        // Convert date to Unix timestamp (midnight)
        $timestamp = strtotime($date . ' 00:00:00');
        
        // Initialize result array
        $result = [];
        foreach ($student_ids as $student_id) {
            $result[$student_id] = [
                'has_payment' => false,
                'payment_type' => null,
                'payment_date' => $timestamp,
                'source_table' => null
            ];
        }
        
        // Get direct payments for all students
        $direct_payments = $this->getDirectPaymentsBulk($student_ids, $timestamp);
        foreach ($direct_payments as $student_id) {
            $result[$student_id] = [
                'has_payment' => true,
                'payment_type' => 'Direct Payment',
                'payment_date' => $timestamp,
                'source_table' => 'daily_fee_transactions'
            ];
        }
        
        // Get prepaid usage for students without direct payment
        $students_without_direct = array_diff($student_ids, $direct_payments);
        if (!empty($students_without_direct)) {
            $prepaid_usage = $this->getPrepaidUsageBulk($students_without_direct, $timestamp);
            foreach ($prepaid_usage as $student_id) {
                $result[$student_id] = [
                    'has_payment' => true,
                    'payment_type' => 'Prepaid Balance',
                    'payment_date' => $timestamp,
                    'source_table' => 'daily_charge_log'
                ];
            }
        }
        
        return $result;
    }

    /**
     * Check if direct FEEDING payment exists for student on date
     * 
     * @param int $student_id Student ID
     * @param int $timestamp Unix timestamp (midnight)
     * @return bool
     */
    private function hasDirectPayment($student_id, $timestamp) {
        $query = $this->db->select('id')
            ->from('daily_fee_transactions')
            ->where('student_id', $student_id)
            ->where('payment_date', $timestamp)
            ->where('feeding_amount >', 0)
            ->limit(1)
            ->get();
        
        return $query->num_rows() > 0;
    }

    /**
     * Check if prepaid FEEDING usage exists for student on date
     * 
     * A student has "prepaid usage" only if:
     * 1. They have a charge log entry for feeding on that date
     * 2. The charge was covered by prepaid balance (check audit log for balance deduction)
     * 
     * @param int $student_id Student ID
     * @param int $timestamp Unix timestamp (midnight)
     * @return bool
     */
    private function hasPrepaidUsage($student_id, $timestamp) {
        // Get the charge log entry for this date
        $charge_log = $this->db->select('feeding_charged')
            ->from('daily_charge_log')
            ->where('student_id', $student_id)
            ->where('charge_date', $timestamp)
            ->where('feeding_charged >', 0)
            ->limit(1)
            ->get();
        
        if ($charge_log->num_rows() == 0) {
            return false; // No charge = no prepaid usage
        }
        
        $feeding_charged = $charge_log->row()->feeding_charged;
        
        // Check if there's an audit log entry showing balance was deducted (not arrears added)
        // When prepaid is used: balance_before > balance_after
        // When charge goes to arrears: arrears_after > arrears_before
        $audit = $this->db->select('balance_before, balance_after, arrears_before, arrears_after')
            ->from('daily_fee_audit_log')
            ->where('student_id', $student_id)
            ->where('action_type', 'charge')
            ->where('fee_type', 'feeding')
            ->where('created_at >=', $timestamp)
            ->where('created_at <', $timestamp + 86400) // Same day
            ->limit(1)
            ->get();
        
        if ($audit->num_rows() > 0) {
            $audit_row = $audit->row();
            $balance_deducted = $audit_row->balance_before - $audit_row->balance_after;
            
            // If any balance was deducted, prepaid was used
            if ($balance_deducted > 0) {
                return true;
            }
        }
        
        // No audit entry or no balance deduction = no prepaid usage
        return false;
    }

    /**
     * Get students with direct FEEDING payments (bulk query)
     * 
     * @param array $student_ids Array of student IDs
     * @param int $timestamp Unix timestamp (midnight)
     * @return array Array of student IDs with direct feeding payments
     */
    private function getDirectPaymentsBulk($student_ids, $timestamp) {
        $query = $this->db->select('student_id')
            ->from('daily_fee_transactions')
            ->where_in('student_id', $student_ids)
            ->where('payment_date', $timestamp)
            ->where('feeding_amount >', 0)
            ->get();
        
        $result = [];
        foreach ($query->result() as $row) {
            $result[] = $row->student_id;
        }
        
        return $result;
    }

    /**
     * Get students with prepaid FEEDING usage (bulk query)
     * 
     * This checks if students had prepaid balance deducted by checking audit logs.
     * 
     * @param array $student_ids Array of student IDs
     * @param int $timestamp Unix timestamp (midnight)
     * @return array Array of student IDs with prepaid feeding usage
     */
    private function getPrepaidUsageBulk($student_ids, $timestamp) {
        // Get students who have charges on this date
        $charged_students = $this->db->select('student_id')
            ->from('daily_charge_log')
            ->where_in('student_id', $student_ids)
            ->where('charge_date', $timestamp)
            ->where('feeding_charged >', 0)
            ->get()
            ->result_array();
        
        if (empty($charged_students)) {
            return [];
        }
        
        $charged_student_ids = array_column($charged_students, 'student_id');
        
        // Check audit logs to see which students had balance deducted (not arrears added)
        $this->db->select('student_id');
        $this->db->from('daily_fee_audit_log');
        $this->db->where_in('student_id', $charged_student_ids);
        $this->db->where('action_type', 'charge');
        $this->db->where('fee_type', 'feeding');
        $this->db->where('created_at >=', $timestamp);
        $this->db->where('created_at <', $timestamp + 86400); // Same day
        $this->db->where('balance_before >', 'balance_after', FALSE); // Balance was deducted
        $this->db->group_by('student_id');
        
        $query = $this->db->get();
        
        $result = [];
        foreach ($query->result() as $row) {
            $result[] = $row->student_id;
        }
        
        return $result;
    }
}
