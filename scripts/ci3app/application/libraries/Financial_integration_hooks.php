<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Financial Integration Hooks Library
 * Automatically syncs all revenue sources to accounts
 * 
 * @package SchoolManager
 * @author Senior Financial Software Engineer
 * @version 1.0
 */
class Financial_integration_hooks {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Hook for invoice payment
     * Called after payment is recorded
     */
    public function sync_invoice_payment($payment_id) {
        try {
            $payment = $this->CI->db->get_where('payment', ['payment_id' => $payment_id])->row();
            if (!$payment || $payment->synced_to_accounts == 1) {
                return ['status' => 'skipped', 'message' => 'Already synced or not found'];
            }
            
            $invoice = $this->CI->db->get_where('invoice', ['invoice_id' => $payment->invoice_id])->row();
            $student = $this->CI->db->get_where('student', ['student_id' => $invoice->student_id])->row();
            
            // Determine account based on payment method
            $debit_account = $this->get_payment_method_account($payment->payment_method);
            $credit_account = $this->get_account_by_code('4060'); // Tuition Revenue
            
            // Create journal entry
            $entry_number = 'JE-INV-' . date('Ymd') . '-' . $payment_id;
            $invoice_ref = $invoice->invoice_code;
            $description = "Invoice Payment - {$student->name} (Invoice #{$invoice_ref})";
            
            $entry_data = [
                'entry_number' => $entry_number,
                'entry_date' => date('Y-m-d', $payment->timestamp),
                'description' => $description,
                'source_type' => 'invoice_payment',
                'source_id' => $payment_id,
                'created_by' => $this->CI->session->userdata('admin_id') ?: 1,
                'status' => 'posted',
                'total_debit' => $payment->amount,
                'total_credit' => $payment->amount,
            ];
            
            $this->CI->db->insert('journal_entries', $entry_data);
            $entry_id = $this->CI->db->insert_id();
            
            // Create journal lines
            $lines = [
                [
                    'entry_id' => $entry_id,
                    'account_id' => $debit_account['account_id'],
                    'debit_amount' => $payment->amount,
                    'credit_amount' => 0,
                    'description' => $description
                ],
                [
                    'entry_id' => $entry_id,
                    'account_id' => $credit_account['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount,
                    'description' => 'Tuition fees'
                ]
            ];
            
            $this->CI->db->insert_batch('journal_entry_lines', $lines);
            
            // Update account balances
            $this->update_account_balance($debit_account['account_id'], $payment->amount, 'debit');
            $this->update_account_balance($credit_account['account_id'], $payment->amount, 'credit');
            
            // Mark as synced
            $this->CI->db->where('payment_id', $payment_id);
            $this->CI->db->update('payment', [
                'synced_to_accounts' => 1,
                'journal_entry_id' => $entry_id,
                'synced_at' => time()
            ]);
            
            // Log integration
            $this->log_integration('invoice_to_accounts', 'payment', $payment_id, 'journal_entries', $entry_id, 'success');
            
            return ['status' => 'success', 'entry_id' => $entry_id];
            
        } catch (Exception $e) {
            $this->log_integration('invoice_to_accounts', 'payment', $payment_id, 'journal_entries', null, 'failed', $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hook for daily fee transaction - STAGE 1: Payment Collection
     * Called after fee is collected - Records as UNEARNED REVENUE
     */
    public function sync_daily_fee($transaction_id) {
        try {
            $trans = $this->CI->db->get_where('daily_fee_transactions', ['id' => $transaction_id])->row();
            if (!$trans) {
                return ['status' => 'error', 'message' => 'Transaction not found'];
            }
            
            // Check if already synced
            if (isset($trans->synced_to_accounts) && $trans->synced_to_accounts == 1) {
                return ['status' => 'skipped', 'message' => 'Already synced'];
            }
            
            $student = $this->CI->db->get_where('student', ['student_id' => $trans->student_id])->row();
            
            // Determine debit account (Cash/Bank/MoMo)
            $debit_account = $this->get_payment_method_account($trans->payment_method);
            
            // Get unearned revenue account (LIABILITY)
            $unearned_account = $this->get_account_by_code('2040');
            
            // Create journal entry: DR Cash, CR Unearned Revenue
            $entry_number = 'JE-PREPAY-' . date('Ymd') . '-' . $transaction_id;
            $description = "Prepaid Daily Fees - {$student->name}";
            
            $entry_data = [
                'entry_number' => $entry_number,
                'entry_date' => date('Y-m-d', $trans->payment_date),
                'description' => $description,
                'source_type' => 'daily_fee_prepayment',
                'source_id' => $transaction_id,
                'created_by' => $trans->collected_by,
                'status' => 'posted',
                'total_debit' => $trans->total_amount,
                'total_credit' => $trans->total_amount,
                
            ];
            
            $this->CI->db->insert('journal_entries', $entry_data);
            $entry_id = $this->CI->db->insert_id();
            
            // Create journal lines
            $lines = [
                // DR: Cash/Bank/MoMo
                [
                    'entry_id' => $entry_id,
                    'account_id' => $debit_account['account_id'],
                    'debit_amount' => $trans->total_amount,
                    'credit_amount' => 0,
                    'description' => 'Cash received'
                ],
                // CR: Unearned Revenue (Liability)
                [
                    'entry_id' => $entry_id,
                    'account_id' => $unearned_account['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $trans->total_amount,
                    'description' => 'Unearned revenue - service not yet delivered'
                ]
            ];
            
            $this->CI->db->insert_batch('journal_entry_lines', $lines);
            
            // Update account balances
            $this->update_account_balance($debit_account['account_id'], $trans->total_amount, 'debit');
            $this->update_account_balance($unearned_account['account_id'], $trans->total_amount, 'credit');
            
            // Mark as synced
            $this->CI->db->where('id', $transaction_id);
            $this->CI->db->update('daily_fee_transactions', [
                'synced_to_accounts' => 1,
                'journal_entry_id' => $entry_id,
                'synced_at' => time()
            ]);
            
            $this->log_integration('daily_fee_prepayment', 'daily_fee_transactions', $transaction_id, 'journal_entries', $entry_id, 'success');
            
            return ['status' => 'success', 'entry_id' => $entry_id, 'type' => 'prepayment'];
            
        } catch (Exception $e) {
            $this->log_integration('daily_fee_prepayment', 'daily_fee_transactions', $transaction_id, 'journal_entries', null, 'failed', $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * STAGE 2: Revenue Recognition
     * Called when attendance is marked - Recognizes EARNED REVENUE
     */
    public function recognize_daily_fee_revenue($student_id, $charges, $attendance_date) {
        try {
            $total_charged = 0;
            foreach ($charges as $amount) {
                $total_charged += $amount;
            }
            
            if ($total_charged == 0) {
                return ['status' => 'skipped', 'message' => 'No charges to recognize'];
            }
            
            $student = $this->CI->db->get_where('student', ['student_id' => $student_id])->row();
            
            // Get unearned revenue account
            $unearned_account = $this->get_account_by_code('2040');
            
            // Create journal entry: DR Unearned Revenue, CR Revenue
            $entry_number = 'JE-REVENUE-' . date('Ymd', $attendance_date) . '-' . $student_id;
            $description = "Revenue Recognized - {$student->name} - " . date('Y-m-d', $attendance_date);
            
            $entry_data = [
                'entry_number' => $entry_number,
                'entry_date' => date('Y-m-d', $attendance_date),
                'description' => $description,
                'source_type' => 'daily_fee_revenue',
                'source_id' => $student_id,
                'created_by' => $this->CI->session->userdata('admin_id') ?: 1,
                'status' => 'posted',
                'total_debit' => $total_charged,
                'total_credit' => $total_charged,
            ];
            
            $this->CI->db->insert('journal_entries', $entry_data);
            $entry_id = $this->CI->db->insert_id();
            
            // Create journal lines
            $lines = [];
            
            // DR: Unearned Revenue
            $lines[] = [
                'entry_id' => $entry_id,
                'account_id' => $unearned_account['account_id'],
                'debit_amount' => $total_charged,
                'credit_amount' => 0,
                'description' => 'Revenue recognition - service delivered'
            ];
            
            // CR: Revenue accounts by fee type
            $revenue_accounts = [
                'feeding_charged' => '4200',
                'breakfast_charged' => '4210',
                'classes_charged' => '4220',
                'water_charged' => '4230',
                'transport_charged' => '4240'
            ];
            
            foreach ($charges as $fee_type => $amount) {
                if ($amount > 0 && isset($revenue_accounts[$fee_type])) {
                    $revenue_account = $this->get_account_by_code($revenue_accounts[$fee_type]);
                    $lines[] = [
                        'entry_id' => $entry_id,
                        'account_id' => $revenue_account['account_id'],
                        'debit_amount' => 0,
                        'credit_amount' => $amount,
                        'description' => ucfirst(str_replace('_charged', '', $fee_type)) . ' revenue'
                    ];
                    $this->update_account_balance($revenue_account['account_id'], $amount, 'credit');
                }
            }
            
            $this->CI->db->insert_batch('journal_entry_lines', $lines);
            
            // Update unearned revenue balance
            $this->update_account_balance($unearned_account['account_id'], $total_charged, 'debit');
            
            $this->log_integration('daily_fee_revenue', 'attendance', $student_id, 'journal_entries', $entry_id, 'success');
            
            return ['status' => 'success', 'entry_id' => $entry_id, 'type' => 'revenue_recognition'];
            
        } catch (Exception $e) {
            $this->log_integration('daily_fee_revenue', 'attendance', $student_id, 'journal_entries', null, 'failed', $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Reverse invoice payment journal entry
     * Called when payment is deleted or modified
     */
    public function reverse_invoice_payment($payment_id) {
        try {
            $payment = $this->CI->db->get_where('payment', ['payment_id' => $payment_id])->row();
            if (!$payment) {
                return ['status' => 'error', 'message' => 'Payment not found'];
            }
            
            // Check if payment was synced
            if (!isset($payment->synced_to_accounts) || $payment->synced_to_accounts != 1 || !$payment->journal_entry_id) {
                return ['status' => 'skipped', 'message' => 'Payment was not synced to accounts'];
            }
            
            $original_entry = $this->CI->db->get_where('journal_entries', ['entry_id' => $payment->journal_entry_id])->row();
            if (!$original_entry) {
                return ['status' => 'error', 'message' => 'Original journal entry not found'];
            }
            
            // Create reversal entry
            $reversal_number = 'JE-REV-' . date('Ymd') . '-' . $payment_id;
            $description = "REVERSAL: " . $original_entry->description;
            
            $reversal_data = [
                'entry_number' => $reversal_number,
                'entry_date' => date('Y-m-d'),
                'entry_type' => 'reversal',
                'reference_type' => 'payment_reversal',
                'reference_id' => $payment_id,
                'description' => $description,
                'source_type' => 'payment_reversal',
                'source_id' => $payment_id,
                'created_by' => $this->CI->session->userdata('admin_id') ?: 1,
                'status' => 'posted',
                'total_debit' => $original_entry->total_credit,
                'total_credit' => $original_entry->total_debit,
                'created_at' => time()
            ];
            
            $this->CI->db->insert('journal_entries', $reversal_data);
            $reversal_entry_id = $this->CI->db->insert_id();
            
            // Get original lines and reverse them
            $original_lines = $this->CI->db->get_where('journal_entry_lines', ['entry_id' => $payment->journal_entry_id])->result();
            
            $reversal_lines = [];
            foreach ($original_lines as $line) {
                $reversal_lines[] = [
                    'entry_id' => $reversal_entry_id,
                    'account_id' => $line->account_id,
                    'debit_amount' => $line->credit_amount,
                    'credit_amount' => $line->debit_amount,
                    'description' => 'Reversal: ' . $line->description
                ];
                
                // Reverse account balances
                if ($line->debit_amount > 0) {
                    $this->update_account_balance($line->account_id, $line->debit_amount, 'credit');
                }
                if ($line->credit_amount > 0) {
                    $this->update_account_balance($line->account_id, $line->credit_amount, 'debit');
                }
            }
            
            $this->CI->db->insert_batch('journal_entry_lines', $reversal_lines);
            
            // Mark payment as unsynced
            $this->CI->db->where('payment_id', $payment_id)->update('payment', [
                'synced_to_accounts' => 0,
                'journal_entry_id' => NULL
            ]);
            
            $this->log_integration('payment_reversal', 'payment', $payment_id, 'journal_entries', $reversal_entry_id, 'success');
            
            return ['status' => 'success', 'reversal_entry_id' => $reversal_entry_id];
            
        } catch (Exception $e) {
            $this->log_integration('payment_reversal', 'payment', $payment_id, 'journal_entries', null, 'failed', $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    
    /**
     * Get payment method account
     */
    private function get_payment_method_account($method) {
        switch ($method) {
            case 1:
            case 'cash':
                return $this->get_account_by_code('1010'); // Cash
            case 2:
            case 'mobile_money':
                return $this->get_account_by_code('1020'); // Mobile Money
            case 3:
            case 'bank':
                return $this->get_account_by_code('1030'); // Bank
            default:
                return $this->get_account_by_code('1010'); // Default to Cash
        }
    }
    
    /**
     * Get account by code
     */
    private function get_account_by_code($code) {
        $account = $this->CI->db->get_where('chart_of_accounts', ['account_code' => $code])->row_array();
        if (!$account) {
            $account = $this->create_default_account($code);
        }
        return $account;
    }
    
    /**
     * Create default account
     */
    private function create_default_account($code) {
        $defaults = [
            '1010' => ['name' => 'Cash on Hand', 'type' => 'asset'],
            '1020' => ['name' => 'Mobile Money Account', 'type' => 'asset'],
            '1030' => ['name' => 'Bank Account', 'type' => 'asset'],
            '1200' => ['name' => 'Inventory Asset', 'type' => 'asset'],
            '2010' => ['name' => 'Accounts Payable', 'type' => 'liability'],
            '4060' => ['name' => 'Tuition Fee Revenue', 'type' => 'revenue'],
            '4200' => ['name' => 'Daily Feeding Fee Revenue', 'type' => 'revenue'],
            '4210' => ['name' => 'Daily Breakfast Fee Revenue', 'type' => 'revenue'],
            '4220' => ['name' => 'Daily Classes Fee Revenue', 'type' => 'revenue'],
            '4230' => ['name' => 'Daily Water Fee Revenue', 'type' => 'revenue'],
            '4240' => ['name' => 'Daily Transport Fee Revenue', 'type' => 'revenue'],
            '4900' => ['name' => 'Sales Returns & Allowances', 'type' => 'revenue'],
            '5100' => ['name' => 'Cost of Goods Sold', 'type' => 'expense']
        ];
        
        if (isset($defaults[$code])) {
            $data = [
                'account_code' => $code,
                'account_name' => $defaults[$code]['name'],
                'account_type' => $defaults[$code]['type'],
                'current_balance' => 0,
                'is_active' => 1,
                'created_at' => time()
            ];
            $this->CI->db->insert('chart_of_accounts', $data);
            return $this->CI->db->get_where('chart_of_accounts', ['account_code' => $code])->row_array();
        }
        
        return null;
    }
    
    /**
     * Update account balance
     */
    private function update_account_balance($account_id, $amount, $type) {
        $account = $this->CI->db->get_where('chart_of_accounts', ['account_id' => $account_id])->row();
        
        if ($type == 'debit') {
            if (in_array($account->account_type, ['asset', 'expense'])) {
                $new_balance = $account->current_balance + $amount;
            } else {
                $new_balance = $account->current_balance - $amount;
            }
        } else {
            if (in_array($account->account_type, ['liability', 'equity', 'revenue'])) {
                $new_balance = $account->current_balance + $amount;
            } else {
                $new_balance = $account->current_balance - $amount;
            }
        }
        
        $this->CI->db->where('account_id', $account_id);
        $this->CI->db->update('chart_of_accounts', ['current_balance' => $new_balance]);
    }
    
    /**
     * Record refund transaction
     * Called when inventory return is processed
     * 
     * @param array $refund_data Array with keys:
     *   - transaction_type: 'inventory_refund'
     *   - amount: Refund amount
     *   - student_id: Student receiving refund
     *   - reference: Return reference (e.g., "Return #123 for Sale #456")
     *   - date: Refund date (timestamp or Y-m-d)
     *   - payment_method: 1=Cash, 2=Account Credit, 3=Original Method
     *   - notes: Additional notes
     * @return array ['status' => 'success'|'error', 'message' => string, 'entry_id' => int]
     */
    public function record_refund($refund_data) {
        try {
            // Validate required fields
            if (empty($refund_data['amount']) || empty($refund_data['reference'])) {
                return ['status' => 'error', 'message' => 'Missing required refund data'];
            }
            
            $return_id = isset($refund_data['return_id']) ? $refund_data['return_id'] : null;
            $amount = $refund_data['amount'];
            $student_id = isset($refund_data['student_id']) ? $refund_data['student_id'] : null;
            $reference = $refund_data['reference'];
            $refund_date = isset($refund_data['date']) ? $refund_data['date'] : date('Y-m-d');
            $payment_method = isset($refund_data['payment_method']) ? $refund_data['payment_method'] : 1;
            $notes = isset($refund_data['notes']) ? $refund_data['notes'] : '';
            $customer_name = isset($refund_data['customer_name']) ? $refund_data['customer_name'] : 'Walk-in Customer';
            
            // Convert timestamp to date if needed
            if (is_numeric($refund_date) && $refund_date > 10000) {
                $refund_date = date('Y-m-d', $refund_date);
            }
            
            // Get customer name - either from student or use provided name
            if ($student_id) {
                $student = $this->CI->db->get_where('student', ['student_id' => $student_id])->row();
                if ($student) {
                    $customer_name = $student->name;
                }
            }
            
            // Determine credit account based on payment method
            $credit_account = $this->get_payment_method_account($payment_method);
            
            // Get Sales Returns & Allowances account (Contra-Revenue)
            $debit_account = $this->get_account_by_code('4900');
            
            // Create journal entry: DR Sales Returns & Allowances, CR Cash/Bank/MoMo
            $entry_number = 'JE-REFUND-' . date('Ymd', strtotime($refund_date)) . '-' . ($return_id ?: uniqid());
            $description = "Inventory Refund - {$customer_name} - {$reference}";
            if ($notes) {
                $description .= " - {$notes}";
            }
            
            $entry_data = [
                'entry_number' => $entry_number,
                'entry_date' => $refund_date,
                'description' => $description,
                'source_type' => 'inventory_refund',
                'source_id' => $return_id ?: 0,
                'created_by' => $this->CI->session->userdata('admin_id') ?: 1,
                'status' => 'posted',
                'total_debit' => $amount,
                'total_credit' => $amount,
            ];
            
            $this->CI->db->insert('journal_entries', $entry_data);
            $entry_id = $this->CI->db->insert_id();
            
            // Create journal lines
            $lines = [
                // DR: Sales Returns & Allowances (Contra-Revenue)
                [
                    'entry_id' => $entry_id,
                    'account_id' => $debit_account['account_id'],
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => 'Sales return - ' . $reference
                ],
                // CR: Cash/Bank/MoMo
                [
                    'entry_id' => $entry_id,
                    'account_id' => $credit_account['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => 'Refund issued'
                ]
            ];
            
            $this->CI->db->insert_batch('journal_entry_lines', $lines);
            
            // Update account balances
            $this->update_account_balance($debit_account['account_id'], $amount, 'debit');
            $this->update_account_balance($credit_account['account_id'], $amount, 'credit');
            
            // If refund method is account credit (2), add to student's credit balance
            if ($payment_method == 2 && $student_id) {
                // Check if account_balance column exists
                if ($this->CI->db->field_exists('account_balance', 'student')) {
                    $this->CI->db->where('student_id', $student_id);
                    $this->CI->db->set('account_balance', 'account_balance + ' . $amount, FALSE);
                    $this->CI->db->update('student');
                } else {
                    // Log that account credit tracking is not available
                    log_message('info', 'Account credit refund requested but account_balance column does not exist in student table');
                }
            }
            
            // Log integration
            $this->log_integration('inventory_refund', 'inventory_returns', $return_id, 'journal_entries', $entry_id, 'success');
            
            return ['status' => 'success', 'entry_id' => $entry_id, 'message' => 'Refund recorded successfully'];
            
        } catch (Exception $e) {
            $return_id = isset($refund_data['return_id']) ? $refund_data['return_id'] : null;
            $this->log_integration('inventory_refund', 'inventory_returns', $return_id, 'journal_entries', null, 'failed', $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Record expense transaction
     * Called when purchase order is marked as received
     * 
     * @param array $expense_data Array with keys:
     *   - transaction_type: 'inventory_purchase'
     *   - amount: Purchase amount
     *   - supplier_id: Supplier ID (optional)
     *   - reference: Purchase reference (e.g., "Purchase Order #123")
     *   - date: Purchase date (timestamp or Y-m-d)
     *   - category: 'inventory' or 'cogs'
     *   - notes: Additional notes
     * @return array ['status' => 'success'|'error', 'message' => string, 'entry_id' => int]
     */
    public function record_expense($expense_data) {
        try {
            // Validate required fields
            if (empty($expense_data['amount']) || empty($expense_data['reference'])) {
                return ['status' => 'error', 'message' => 'Missing required expense data'];
            }
            
            $purchase_id = isset($expense_data['purchase_id']) ? $expense_data['purchase_id'] : null;
            $amount = $expense_data['amount'];
            $supplier_id = isset($expense_data['supplier_id']) ? $expense_data['supplier_id'] : null;
            $reference = $expense_data['reference'];
            $purchase_date = isset($expense_data['date']) ? $expense_data['date'] : date('Y-m-d');
            $category = isset($expense_data['category']) ? $expense_data['category'] : 'inventory';
            $notes = isset($expense_data['notes']) ? $expense_data['notes'] : '';
            
            // Convert timestamp to date if needed
            if (is_numeric($purchase_date) && $purchase_date > 10000) {
                $purchase_date = date('Y-m-d', $purchase_date);
            }
            
            // Get supplier name if provided
            $supplier_name = 'Supplier';
            if ($supplier_id) {
                $supplier = $this->CI->db->get_where('inventory_suppliers', ['id' => $supplier_id])->row();
                if ($supplier) {
                    $supplier_name = $supplier->name;
                }
            }
            
            // Determine debit account based on category
            if ($category == 'cogs') {
                $debit_account = $this->get_account_by_code('5100'); // Cost of Goods Sold
            } else {
                $debit_account = $this->get_account_by_code('1200'); // Inventory Asset
            }
            
            // Credit account: Cash or Accounts Payable
            // For now, we'll use Cash (1010) - can be enhanced to support Accounts Payable
            $credit_account = $this->get_account_by_code('1010'); // Cash
            
            // Create journal entry: DR Inventory/COGS, CR Cash/Accounts Payable
            $entry_number = 'JE-PURCHASE-' . date('Ymd', strtotime($purchase_date)) . '-' . ($purchase_id ?: uniqid());
            $description = "Inventory Purchase - {$supplier_name} - {$reference}";
            if ($notes) {
                $description .= " - {$notes}";
            }
            
            $entry_data = [
                'entry_number' => $entry_number,
                'entry_date' => $purchase_date,
                'description' => $description,
                'source_type' => 'inventory_purchase',
                'source_id' => $purchase_id,
                'created_by' => $this->CI->session->userdata('admin_id') ?: 1,
                'status' => 'posted',
                'total_debit' => $amount,
                'total_credit' => $amount,
            ];
            
            $this->CI->db->insert('journal_entries', $entry_data);
            $entry_id = $this->CI->db->insert_id();
            
            // Create journal lines
            $lines = [
                // DR: Inventory Asset or COGS
                [
                    'entry_id' => $entry_id,
                    'account_id' => $debit_account['account_id'],
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => ($category == 'cogs' ? 'Cost of goods sold' : 'Inventory purchased')
                ],
                // CR: Cash (or Accounts Payable in future)
                [
                    'entry_id' => $entry_id,
                    'account_id' => $credit_account['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => 'Payment for purchase'
                ]
            ];
            
            $this->CI->db->insert_batch('journal_entry_lines', $lines);
            
            // Update account balances
            $this->update_account_balance($debit_account['account_id'], $amount, 'debit');
            $this->update_account_balance($credit_account['account_id'], $amount, 'credit');
            
            // Log integration
            $this->log_integration('inventory_purchase', 'inventory_purchases', $purchase_id, 'journal_entries', $entry_id, 'success');
            
            return ['status' => 'success', 'entry_id' => $entry_id, 'message' => 'Expense recorded successfully'];
            
        } catch (Exception $e) {
            $purchase_id = isset($expense_data['purchase_id']) ? $expense_data['purchase_id'] : null;
            $this->log_integration('inventory_purchase', 'inventory_purchases', $purchase_id, 'journal_entries', null, 'failed', $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Void/Reverse a refund transaction
     * Called when a return is voided to reverse the financial entry
     */
    public function void_refund($data) {
        try {
            // Get the original refund journal entry
            $this->CI->db->where('source_type', 'inventory_refund');
            $this->CI->db->where('source_id', $data['return_id']);
            $refund_entry = $this->CI->db->get('journal_entries')->row();
            
            if($refund_entry) {
                // Create a reversal entry (without amount column)
                $reversal_data = [
                    'entry_date' => $data['void_date'],
                    'entry_type' => 'reversal',
                    'source_type' => 'inventory_refund_void',
                    'source_id' => $data['return_id'],
                    'description' => 'VOID: ' . $refund_entry->description . ' - ' . $data['notes'],
                    'created_by' => $data['voided_by'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $this->CI->db->insert('journal_entries', $reversal_data);
                $reversal_entry_id = $this->CI->db->insert_id();
                
                // Delete original refund journal entry lines
                $this->CI->db->where('entry_id', $refund_entry->id);
                $this->CI->db->delete('journal_entry_lines');
                
                // Delete original refund journal entry
                $this->CI->db->where('id', $refund_entry->id);
                $this->CI->db->delete('journal_entries');
            }
            
            return ['status' => 'success', 'message' => 'Refund voided successfully'];
            
        } catch (Exception $e) {
            log_message('error', 'Void refund failed: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Log integration
     */
    private function log_integration($type, $source_table, $source_id, $target_table, $target_id, $status, $error = null) {
        $data = [
            'integration_type' => $type,
            'source_table' => $source_table,
            'source_id' => $source_id,
            'target_table' => $target_table,
            'target_id' => $target_id,
            'status' => $status,
            'error_message' => $error,
            'created_at' => time(),
            'processed_at' => time()
        ];
        $this->CI->db->insert('financial_integration_log', $data);
    }
}