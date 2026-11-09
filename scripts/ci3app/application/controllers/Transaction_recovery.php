<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transaction Recovery Controller
 * 
 * Handles the recovery of deleted daily fee transactions
 */
class Transaction_recovery extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        
        // Set execution time limit
        set_time_limit(300);
        ini_set('max_execution_time', 300);
    }

    /**
     * Main recovery interface
     */
    public function index() {
        try {
            $data['page_title'] = 'Transaction Recovery';
            $data['stats'] = $this->get_recovery_stats();
            
            // Debug: Log stats
            log_message('debug', 'Recovery stats: ' . json_encode($data['stats']));
            
            $this->load->view('backend/admin/transaction_recovery', $data);
        } catch (Exception $e) {
            log_message('error', 'Recovery index error: ' . $e->getMessage());
            show_error('Error loading recovery page: ' . $e->getMessage());
        }
    }

    /**
     * Get recovery statistics
     */
    public function get_stats() {
        $stats = $this->get_recovery_stats();
        header('Content-Type: application/json');
        echo json_encode($stats);
    }

    /**
     * Process the recovery
     */
    public function process() {
        // Disable output buffering for real-time updates
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Configuration
        $target_date = '2026-05-04';
        $target_timestamp = strtotime($target_date . ' 00:00:00');
        $start_timestamp = $target_timestamp;
        $end_timestamp = $target_timestamp + 86399;
        
        echo "=== TRANSACTION RECOVERY STARTED ===\n";
        echo "Target Date: $target_date\n";
        echo "Timestamp: $target_timestamp\n\n";
        flush();
        
        // Get settings
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        // Find students needing recovery
        $query = "
            SELECT 
                je.entry_id AS journal_entry_id,
                je.source_id AS transaction_id,
                je.entry_number,
                je.total_debit AS total_amount,
                je.created_by AS collected_by,
                je.updated_at AS payment_time,
                TRIM(SUBSTRING_INDEX(je.description, ' - ', -1)) AS student_name,
                s.student_id,
                s.student_code
            FROM journal_entries je
            INNER JOIN student s ON s.name = TRIM(SUBSTRING_INDEX(je.description, ' - ', -1))
            WHERE je.entry_date = ?
            AND je.source_type = 'daily_fee_prepayment'
            AND NOT EXISTS(
                SELECT 1 FROM daily_fee_transactions dft 
                WHERE dft.id = je.source_id
            )
            ORDER BY je.entry_id ASC
        ";
        
        $students = $this->db->query($query, [$target_date])->result_array();
        
        echo "Found " . count($students) . " students needing recovery\n\n";
        flush();
        
        if (count($students) == 0) {
            echo "No students to recover. Exiting.\n";
            return;
        }
        
        $recovered_count = 0;
        $students_to_recalculate = [];
        
        foreach ($students as $student_data) {
            $student_id = $student_data['student_id'];
            $transaction_id = $student_data['transaction_id'];
            $journal_entry_id = $student_data['journal_entry_id'];
            
            // CRITICAL: Check for duplicates before inserting
            // Check 1: Does this transaction ID already exist?
            $id_exists = $this->db->get_where('daily_fee_transactions', ['id' => $transaction_id])->row();
            
            // Check 2: Does this student already have a transaction on this date?
            $student_exists = $this->db->get_where('daily_fee_transactions', [
                'student_id' => $student_id,
                'payment_date' => $target_timestamp
            ])->row();
            
            if ($id_exists || $student_exists) {
                echo "Processing: {$student_data['student_name']} (ID: $student_id) - SKIPPED (already exists)\n";
                flush();
                continue; // Skip this student
            }
            
            echo "Processing: {$student_data['student_name']} (ID: $student_id)\n";
            flush();
            
            // Get fee breakdown from audit log (source of truth)
            $fee_data = $this->get_fee_breakdown($student_id, $start_timestamp, $end_timestamp);
            $fee_breakdown = $fee_data['breakdown'];
            $audit_total = $fee_data['total'];
            
            // Insert transaction with audit log amounts
            $transaction_data = [
                'id' => $transaction_id,
                'transaction_code' => 'RECOVERED-' . $student_data['entry_number'],
                'student_id' => $student_id,
                'payment_date' => $target_timestamp,
                'feeding_amount' => $fee_breakdown['feeding'],
                'breakfast_amount' => $fee_breakdown['breakfast'],
                'classes_amount' => $fee_breakdown['classes'],
                'water_amount' => $fee_breakdown['water'],
                'transport_amount' => $fee_breakdown['transport'],
                'total_amount' => $audit_total,
                'payment_type' => 'advance',
                'payment_method' => 1,
                'collected_by' => $student_data['collected_by'],
                'collection_point' => 'cashier_portal',
                'receipt_number' => $student_data['entry_number'],
                'journal_entry_id' => $journal_entry_id,
                'notes' => 'Recovered on ' . date('Y-m-d H:i:s'),
                'year' => $running_year,
                'term' => $running_term,
                'created_at' => strtotime($student_data['payment_time']),
                'modified_at' => time()
            ];
            
            if ($this->db->insert('daily_fee_transactions', $transaction_data)) {
                echo "  ✓ Transaction inserted (ID: $transaction_id)\n";
                flush();
                $recovered_count++;
                $students_to_recalculate[] = $student_id;
            } else {
                echo "  ✗ Failed to insert transaction\n";
                flush();
            }
        }
        
        echo "\n=== Recalculating Wallets ===\n";
        flush();
        
        $recalc_count = 0;
        foreach ($students_to_recalculate as $student_id) {
            echo "Recalculating wallet for student ID: $student_id...";
            flush();
            
            try {
                $this->Daily_fee_model->recalculate_wallet_from_date($student_id, $target_timestamp);
                echo " ✓\n";
                flush();
                $recalc_count++;
            } catch (Exception $e) {
                echo " ✗ Error: " . $e->getMessage() . "\n";
                flush();
            }
        }
        
        echo "\n=== RECOVERY SUMMARY ===\n";
        echo "Transactions inserted: $recovered_count\n";
        echo "Wallets recalculated: $recalc_count\n";
        echo "\nRecovery complete!\n";
        flush();
    }

    /**
     * Get fee breakdown for a student from AUDIT LOG (source of truth)
     * Returns breakdown and total amount from audit log
     */
    private function get_fee_breakdown($student_id, $start_timestamp, $end_timestamp) {
        $fee_breakdown = [
            'feeding' => 0,
            'breakfast' => 0,
            'classes' => 0,
            'water' => 0,
            'transport' => 0
        ];
        
        // Get LAST entry for each fee type from audit log
        foreach (['feeding', 'breakfast', 'classes', 'water', 'transport'] as $fee_type) {
            $audit = $this->db->query("
                SELECT amount 
                FROM daily_fee_audit_log 
                WHERE student_id = ? 
                AND fee_type = ?
                AND action_type = 'payment'
                AND created_at BETWEEN ? AND ?
                ORDER BY created_at DESC, id DESC 
                LIMIT 1
            ", [$student_id, $fee_type, $start_timestamp, $end_timestamp])->row();
            
            if ($audit) {
                $fee_breakdown[$fee_type] = $audit->amount;
            }
        }
        
        // Calculate total from breakdown (audit log is source of truth)
        $audit_total = array_sum($fee_breakdown);
        
        return [
            'breakdown' => $fee_breakdown,
            'total' => $audit_total
        ];
    }

    /**
     * Get recovery statistics
     */
    private function get_recovery_stats() {
        $target_date = '2026-05-04';
        $target_timestamp = strtotime($target_date . ' 00:00:00');
        
        // Total journal entries
        $total_journal = $this->db->query("
            SELECT 
                COUNT(*) as total_entries,
                SUM(total_debit) as total_amount
            FROM journal_entries 
            WHERE entry_date = '$target_date' 
            AND source_type = 'daily_fee_prepayment'
        ")->row();
        
        // Existing transactions
        $existing = $this->db->query("
            SELECT 
                COUNT(*) as existing_count,
                SUM(total_amount) as existing_amount
            FROM daily_fee_transactions 
            WHERE payment_date = $target_timestamp
        ")->row();
        
        // Missing transactions
        $missing = $this->db->query("
            SELECT 
                COUNT(*) as missing_count,
                SUM(je.total_debit) as missing_amount
            FROM journal_entries je
            WHERE je.entry_date = '$target_date'
            AND je.source_type = 'daily_fee_prepayment'
            AND NOT EXISTS(
                SELECT 1 FROM daily_fee_transactions dft 
                WHERE dft.id = je.source_id
            )
        ")->row();
        
        return [
            'total_entries' => $total_journal->total_entries ?? 0,
            'total_amount' => $total_journal->total_amount ?? 0,
            'existing_count' => $existing->existing_count ?? 0,
            'existing_amount' => $existing->existing_amount ?? 0,
            'missing_count' => $missing->missing_count ?? 0,
            'missing_amount' => $missing->missing_amount ?? 0
        ];
    }
}
