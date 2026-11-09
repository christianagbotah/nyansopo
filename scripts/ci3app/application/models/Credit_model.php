<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Credit model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Credit_model extends MY_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Process overpayment and create credit
     */
    public function process_overpayment($payment_data) {
        $this->db->trans_start();
        
        $student_id = $payment_data['student_id'];
        $total_paid = $payment_data['amount'];
        $invoice_id = $payment_data['invoice_id'] ?? null;
        $receipt_code = $payment_data['receipt_code'];
        
        // Get invoice details
        if ($invoice_id) {
            $invoice = $this->db->get_where('invoice', ['invoice_id' => $invoice_id])->row();
            if (!$invoice) {
                $this->db->trans_rollback();
                return ['status' => 'error', 'message' => 'Invoice not found'];
            }
            
            $amount_due = $invoice->amount - $invoice->amount_paid - ($invoice->credit_applied ?? 0);
            
            if ($total_paid > $amount_due) {
                // Overpayment detected
                $overpayment = $total_paid - $amount_due;
                
                // Update invoice - mark as fully paid
                $this->db->where('invoice_id', $invoice_id)
                         ->update('invoice', [
                             'amount_paid' => $invoice->amount_paid + $amount_due,
                             'due' => 0,
                             'status' => 'paid'
                         ]);
                
                // Create credit record
                $credit_data = [
                    'student_id' => $student_id,
                    'credit_amount' => $overpayment,
                    'source_payment_id' => $payment_data['payment_id'] ?? null,
                    'source_receipt_code' => $receipt_code,
                    'source_invoice_code' => $invoice->invoice_code,
                    'created_by' => $this->session->userdata('login_user_id'),
                    'notes' => "Overpayment from invoice #{$invoice->invoice_code} - Receipt #{$receipt_code}"
                ];
                
                $this->db->insert('student_credits', $credit_data);
                $credit_id = $this->db->insert_id();
                
                // Log the credit creation
                $this->log_credit_action('credit_created', $student_id, $overpayment, $invoice_id, 'payment', [
                    'credit_id' => $credit_id,
                    'source' => 'overpayment',
                    'invoice_code' => $invoice->invoice_code,
                    'receipt_code' => $receipt_code
                ]);
                
                $this->db->trans_complete();
                
                return [
                    'status' => 'success',
                    'overpayment' => $overpayment,
                    'credit_id' => $credit_id,
                    'message' => "Overpayment of GH₵ " . number_format($overpayment, 2) . " credited to student account"
                ];
            }
        }
        
        $this->db->trans_complete();
        return ['status' => 'no_overpayment'];
    }
    
    /**
     * Get student's available credits
     */
    public function get_student_credits($student_id, $active_only = true) {
        $this->db->select('*');
        $this->db->where('student_id', $student_id);
        
        if ($active_only) {
            $this->db->where('status', 'active');
            $this->db->where('remaining_amount >', 0);
        }
        
        return $this->db->order_by('created_at', 'ASC')
                        ->get('student_credits')
                        ->result_array();
    }
    
    /**
     * Apply credits to new invoice (FIFO - First In, First Out)
     */
    public function apply_credits_to_invoice($invoice_id) {
        $this->db->trans_start();
        
        // Get invoice details
        $invoice = $this->db->get_where('invoice', ['invoice_id' => $invoice_id])->row();
        if (!$invoice) {
            $this->db->trans_rollback();
            return ['status' => 'error', 'message' => 'Invoice not found'];
        }
        
        $student_id = $invoice->student_id;
        $invoice_amount = $invoice->amount;
        
        // Get available credits (oldest first - FIFO)
        $credits = $this->get_student_credits($student_id, true);
        
        $total_credit_applied = 0;
        $remaining_invoice_amount = $invoice_amount;
        
        foreach ($credits as $credit) {
            if ($remaining_invoice_amount <= 0) break;
            
            $available_credit = $credit['remaining_amount'];
            $credit_to_apply = min($available_credit, $remaining_invoice_amount);
            
            if ($credit_to_apply > 0) {
                // Apply credit to invoice
                $this->db->insert('credit_applications', [
                    'credit_id' => $credit['credit_id'],
                    'invoice_id' => $invoice_id,
                    'invoice_code' => $invoice->invoice_code,
                    'applied_amount' => $credit_to_apply,
                    'applied_by' => $this->session->userdata('login_user_id'),
                    'notes' => "Auto-applied to invoice #{$invoice->invoice_code}"
                ]);
                
                // Update credit record
                $new_applied_amount = $credit['applied_amount'] + $credit_to_apply;
                $this->db->where('credit_id', $credit['credit_id'])
                         ->update('student_credits', [
                             'applied_amount' => $new_applied_amount,
                             'status' => ($new_applied_amount >= $credit['credit_amount']) ? 'fully_applied' : 'active'
                         ]);
                
                $total_credit_applied += $credit_to_apply;
                $remaining_invoice_amount -= $credit_to_apply;
                
                // Log the credit application
                $this->log_credit_action('credit_applied', $student_id, $credit_to_apply, $invoice_id, 'invoice', [
                    'credit_id' => $credit['credit_id'],
                    'invoice_code' => $invoice->invoice_code
                ]);
            }
        }
        
        // Update invoice with applied credits
        if ($total_credit_applied > 0) {
            $new_due = max(0, $invoice_amount - $invoice->amount_paid - $total_credit_applied);
            $this->db->where('invoice_id', $invoice_id)
                     ->update('invoice', [
                         'credit_applied' => $total_credit_applied,
                         'due' => $new_due
                     ]);
        }
        
        $this->db->trans_complete();
        
        return [
            'status' => 'success',
            'credit_applied' => $total_credit_applied,
            'new_due_amount' => $remaining_invoice_amount,
            'message' => $total_credit_applied > 0 ? 
                "Credit of GH₵ " . number_format($total_credit_applied, 2) . " applied to invoice" : 
                "No credits available"
        ];
    }
    
    /**
     * Get student's total available credit
     */
    public function get_student_total_credit($student_id) {
        $this->db->select_sum('remaining_amount');
        $this->db->where('student_id', $student_id);
        $this->db->where('status', 'active');
        
        $result = $this->db->get('student_credits')->row();
        return $result->remaining_amount ?? 0;
    }
    
    /**
     * Get credit history for student
     */
    public function get_credit_history($student_id) {
        $this->db->select('sc.*, ca.applied_amount as application_amount, ca.applied_at, ca.invoice_code as applied_to_invoice');
        $this->db->from('student_credits sc');
        $this->db->join('credit_applications ca', 'sc.credit_id = ca.credit_id', 'left');
        $this->db->where('sc.student_id', $student_id);
        $this->db->order_by('sc.created_at', 'DESC');
        $this->db->order_by('ca.applied_at', 'DESC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Manual credit adjustment (for admin use)
     */
    public function adjust_credit($student_id, $amount, $reason, $admin_id) {
        $this->db->trans_start();
        
        if ($amount > 0) {
            // Add credit
            $credit_data = [
                'student_id' => $student_id,
                'credit_amount' => $amount,
                'remaining_amount' => $amount,
                'applied_amount' => 0,
                'status' => 'active',
                'source_type' => 'adjustment',
                'created_by' => $admin_id,
                'notes' => "Manual adjustment: " . $reason,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('student_credits', $credit_data);
            $credit_id = $this->db->insert_id();
            
            $this->log_credit_action('credit_adjusted', $student_id, $amount, $credit_id, 'adjustment', [
                'reason' => $reason,
                'admin_id' => $admin_id,
                'type' => 'addition'
            ]);
            
            $message = "Credit of GH₵ " . number_format($amount, 2) . " added successfully";
        } else {
            // Reduce credit
            $result = $this->reduce_student_credits($student_id, abs($amount), $reason);
            
            if ($result['status'] !== 'success') {
                $this->db->trans_rollback();
                return $result;
            }
            
            $message = $result['message'];
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => 'Adjustment failed due to database error'];
        }
        
        return ['status' => 'success', 'message' => $message];
    }
    
    /**
     * Reduce student credits (for refunds, adjustments)
     */
    private function reduce_student_credits($student_id, $amount, $reason) {
        $credits = $this->get_student_credits($student_id, true);
        $remaining_to_reduce = $amount;
        $total_reduced = 0;
        
        if (empty($credits)) {
            return [
                'status' => 'error',
                'message' => 'No active credits available to reduce'
            ];
        }
        
        foreach ($credits as $credit) {
            if ($remaining_to_reduce <= 0) break;
            
            $available = $credit['remaining_amount'];
            $to_reduce = min($available, $remaining_to_reduce);
            
            if ($to_reduce > 0) {
                $new_applied = $credit['applied_amount'] + $to_reduce;
                $new_remaining = $credit['remaining_amount'] - $to_reduce;
                
                $this->db->where('credit_id', $credit['credit_id'])
                         ->update('student_credits', [
                             'applied_amount' => $new_applied,
                             'remaining_amount' => $new_remaining,
                             'status' => ($new_remaining <= 0) ? 'fully_applied' : 'active',
                             'notes' => $credit['notes'] . " | Reduced: " . $reason
                         ]);
                
                $remaining_to_reduce -= $to_reduce;
                $total_reduced += $to_reduce;
                
                $this->log_credit_action('credit_reduced', $student_id, -$to_reduce, $credit['credit_id'], 'reduction', [
                    'reason' => $reason,
                    'reduction' => true
                ]);
            }
        }
        
        if ($total_reduced < $amount) {
            return [
                'status' => 'error',
                'message' => 'Insufficient credits. Only GH₵ ' . number_format($total_reduced, 2) . ' available'
            ];
        }
        
        return [
            'status' => 'success',
            'reduced_amount' => $total_reduced,
            'message' => "Credit reduced by GH₵ " . number_format($total_reduced, 2)
        ];
    }
    
    /**
     * Get all students with credits (for admin overview)
     */
    public function get_students_with_credits() {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        $this->db->select('s.student_id, s.name, s.student_code, 
                          cl.name as class_name, cl.name_numeric, sec.name as section_name,
                          SUM(sc.remaining_amount) as total_credit,
                          COUNT(sc.credit_id) as credit_count,
                          MAX(sc.created_at) as last_credit_date');
        $this->db->from('student s');
        $this->db->join('student_credits sc', 's.student_id = sc.student_id');
        $this->db->join('enroll e', 's.student_id = e.student_id AND e.year = "' . $running_year . '" AND e.term = "' . $running_term . '" AND e.mute = "0"', 'left');
        $this->db->join('class cl', 'e.class_id = cl.class_id', 'left');
        $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
        $this->db->where('sc.status', 'active');
        $this->db->where('sc.remaining_amount >', 0);
        $this->db->group_by('s.student_id, s.name, s.student_code, cl.name, cl.name_numeric, sec.name');
        $this->db->order_by('total_credit', 'DESC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get system-wide credit statistics
     */
    public function get_credit_statistics() {
        // Total active credits
        $this->db->select_sum('remaining_amount');
        $this->db->where('status', 'active');
        $total_active = $this->db->get('student_credits')->row()->remaining_amount ?? 0;
        
        // Total credits ever created
        $this->db->select_sum('credit_amount');
        $total_created = $this->db->get('student_credits')->row()->credit_amount ?? 0;
        
        // Total credits applied
        $this->db->select_sum('applied_amount');
        $total_applied = $this->db->get('student_credits')->row()->applied_amount ?? 0;
        
        // Number of students with credits
        $this->db->select('COUNT(DISTINCT student_id) as count');
        $this->db->where('status', 'active');
        $this->db->where('remaining_amount >', 0);
        $students_with_credits = $this->db->get('student_credits')->row()->count ?? 0;
        
        return [
            'total_active_credits' => $total_active,
            'total_credits_created' => $total_created,
            'total_credits_applied' => $total_applied,
            'students_with_credits' => $students_with_credits,
            'utilization_rate' => $total_created > 0 ? ($total_applied / $total_created) * 100 : 0
        ];
    }
    
    /**
     * Log credit system actions for audit trail
     */
    private function log_credit_action($action, $student_id, $amount, $reference_id, $reference_type, $details = []) {
        $log_data = [
            'student_id' => $student_id,
            'action' => $action,
            'amount' => $amount,
            'reference_id' => $reference_id,
            'reference_type' => $reference_type,
            'performed_by' => $this->session->userdata('login_user_id'),
            'details' => json_encode($details)
        ];
        
        $this->db->insert('credit_system_logs', $log_data);
    }
    
    /**
     * Check if credit system is enabled
     */
    public function is_credit_system_enabled() {
        $setting = $this->db->get_where('settings', ['type' => 'credit_system_enabled'])->row();
        return $setting && $setting->description == '1';
    }
    
    /**
     * Transfer credit between students (admin function)
     */
    public function transfer_credit($from_student_id, $to_student_id, $amount, $reason, $admin_id) {
        if (!$this->is_credit_system_enabled()) {
            return ['status' => 'error', 'message' => 'Credit system is disabled'];
        }
        
        // Check if from_student has enough credit
        $available_credit = $this->get_student_total_credit($from_student_id);
        if ($available_credit < $amount) {
            return ['status' => 'error', 'message' => 'Insufficient credit balance. Available: GH₵ ' . number_format($available_credit, 2)];
        }
        
        $this->db->trans_start();
        
        // Reduce credit from source student
        $reduction_result = $this->reduce_student_credits($from_student_id, $amount, "Transfer to student ID: {$to_student_id} - {$reason}");
        
        if ($reduction_result['status'] !== 'success') {
            $this->db->trans_rollback();
            return $reduction_result;
        }
        
        // Add credit to destination student
        $credit_data = [
            'student_id' => $to_student_id,
            'credit_amount' => $amount,
            'remaining_amount' => $amount,
            'applied_amount' => 0,
            'status' => 'active',
            'source_type' => 'transfer',
            'created_by' => $admin_id,
            'notes' => "Transfer from student ID: {$from_student_id} - {$reason}",
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('student_credits', $credit_data);
        $new_credit_id = $this->db->insert_id();
        
        // Log the transfer action
        $this->log_credit_action('credit_transferred', $from_student_id, -$amount, $new_credit_id, 'transfer', [
            'from_student_id' => $from_student_id,
            'to_student_id' => $to_student_id,
            'reason' => $reason,
            'admin_id' => $admin_id
        ]);
        
        $this->log_credit_action('credit_received', $to_student_id, $amount, $new_credit_id, 'transfer', [
            'from_student_id' => $from_student_id,
            'to_student_id' => $to_student_id,
            'reason' => $reason,
            'admin_id' => $admin_id
        ]);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => 'Transfer failed due to database error'];
        }
        
        return [
            'status' => 'success',
            'message' => "Credit of GH₵ " . number_format($amount, 2) . " transferred successfully"
        ];
    }

    /**
     * Transfer credit to daily fee prepaid account
     * 
     * @param int $student_id Student ID
     * @param float $amount Amount to transfer
     * @param string $fee_type Type: feeding, breakfast, classes, water, transport
     * @param string $reason Reason for transfer
     * @param int $admin_id Admin performing the transfer
     * @return array Status and message
     */
    public function transfer_credit_to_daily_fees($student_id, $amount, $fee_type, $reason, $admin_id) {
        $this->db->trans_start();
        
        // Validate fee type
        $valid_fee_types = ['feeding', 'breakfast', 'classes', 'water', 'transport'];
        if (!in_array($fee_type, $valid_fee_types)) {
            $this->db->trans_rollback();
            return [
                'status' => 'error',
                'message' => 'Invalid fee type. Must be one of: ' . implode(', ', $valid_fee_types)
            ];
        }
        
        // Validate amount
        $amount = floatval($amount);
        if ($amount <= 0) {
            $this->db->trans_rollback();
            return ['status' => 'error', 'message' => 'Amount must be greater than zero'];
        }
        
        // Get student's total available credit
        $total_credit = $this->get_student_total_credit($student_id);
        
        if ($total_credit < $amount) {
            $this->db->trans_rollback();
            return [
                'status' => 'error',
                'message' => 'Insufficient credit balance. Available: GH₵ ' . number_format($total_credit, 2)
            ];
        }
        
        // Get running year and term
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        // Get or create daily fee wallet for student
        $wallet = $this->db->get_where('daily_fee_wallet', [
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term
        ])->row();
        
        if (!$wallet) {
            // Create new wallet record
            $wallet_data = [
                'student_id' => $student_id,
                'year' => $running_year,
                'term' => $running_term,
                'feeding_balance' => 0,
                'breakfast_balance' => 0,
                'classes_balance' => 0,
                'water_balance' => 0,
                'transport_balance' => 0,
                'feeding_arrears' => 0,
                'breakfast_arrears' => 0,
                'classes_arrears' => 0,
                'water_arrears' => 0,
                'transport_arrears' => 0,
                'last_updated' => time()
            ];
            $this->db->insert('daily_fee_wallet', $wallet_data);
            $wallet_id = $this->db->insert_id();
        } else {
            $wallet_id = $wallet->id;
        }
        
        // Update the appropriate balance field
        $balance_field = $fee_type . '_balance';
        $this->db->where('id', $wallet_id)
                 ->set($balance_field, "$balance_field + $amount", FALSE)
                 ->set('last_updated', time())
                 ->update('daily_fee_wallet');
        
        // Deduct from student credits (FIFO - oldest first)
        $credits = $this->get_student_credits($student_id, true);
        $remaining_to_deduct = $amount;
        $credits_used = [];
        
        foreach ($credits as $credit) {
            if ($remaining_to_deduct <= 0) break;
            
            $available = $credit['remaining_amount'];
            $deduct_from_this = min($available, $remaining_to_deduct);
            
            if ($deduct_from_this > 0) {
                $new_applied = $credit['applied_amount'] + $deduct_from_this;
                $new_status = ($new_applied >= $credit['credit_amount']) ? 'fully_applied' : 'active';
                
                $this->db->where('credit_id', $credit['credit_id'])
                         ->update('student_credits', [
                             'applied_amount' => $new_applied,
                             'status' => $new_status
                         ]);
                
                $credits_used[] = [
                    'credit_id' => $credit['credit_id'],
                    'amount_used' => $deduct_from_this
                ];
                
                $remaining_to_deduct -= $deduct_from_this;
            }
        }
        
        // Log the transfer in credit_system_logs
        $this->log_credit_action(
            'credit_transferred_to_daily_fees',
            $student_id,
            $amount,
            null,
            'daily_fees',
            [
                'fee_type' => $fee_type,
                'wallet_id' => $wallet_id,
                'year' => $running_year,
                'term' => $running_term,
                'reason' => $reason,
                'credits_used' => $credits_used,
                'performed_by' => $admin_id
            ]
        );
        
        // Create a transaction record in daily_fee_transactions
        $transaction_data = [
            'student_id' => $student_id,
            'year' => $running_year,
            'term' => $running_term,
            'date' => date('Y-m-d'),
            'timestamp' => time(),
            'receipt_code' => 'CREDIT-TRANSFER-' . time(),
            'payment_method' => 'Credit Transfer',
            'feeding_amount' => ($fee_type == 'feeding') ? $amount : 0,
            'breakfast_amount' => ($fee_type == 'breakfast') ? $amount : 0,
            'classes_amount' => ($fee_type == 'classes') ? $amount : 0,
            'water_amount' => ($fee_type == 'water') ? $amount : 0,
            'transport_amount' => ($fee_type == 'transport') ? $amount : 0,
            'total_amount' => $amount,
            'notes' => "Credit transfer: $reason",
            'recorded_by' => $admin_id,
            'sync_status' => 'PENDING'
        ];
        $this->db->insert('daily_fee_transactions', $transaction_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['status' => 'error', 'message' => 'Transaction failed'];
        }
        
        $fee_type_label = ucfirst($fee_type);
        return [
            'status' => 'success',
            'message' => "Successfully transferred GH₵ " . number_format($amount, 2) . " to $fee_type_label prepaid account",
            'amount_transferred' => $amount,
            'fee_type' => $fee_type,
            'new_credit_balance' => $total_credit - $amount
        ];
    }

}