<?php
/**
 * Complete Transaction Sync Handlers
 * Add to Admin.php - Handles all related tables atomically
 */

// Sync Complete Admission Transaction
public function sync_admission_transaction() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $this->db->trans_start();
    
    try {
        // 1. Insert Student
        $student = $data['student'];
        $student['sync_status'] = 'SYNCED';
        $student['last_modified_at'] = date('Y-m-d H:i:s');
        $this->db->insert('student', $student);
        $student_id = $this->db->insert_id();
        
        // 2. Insert Invoice
        $invoice = $data['invoice'];
        $invoice['student_id'] = $student_id;
        $invoice['sync_status'] = 'SYNCED';
        $invoice['creation_timestamp'] = time();
        $this->db->insert('invoice', $invoice);
        $invoice_id = $this->db->insert_id();
        
        // 3. Insert Invoice Items
        if (!empty($data['invoice_items'])) {
            foreach ($data['invoice_items'] as $item) {
                $item['invoice_id'] = $invoice_id;
                $this->db->insert('invoice_items', $item);
            }
        }
        
        // 4. Insert Discount Assignments
        if (!empty($data['discount_assignments'])) {
            foreach ($data['discount_assignments'] as $discount) {
                $discount['student_id'] = $student_id;
                $discount['assigned_at'] = date('Y-m-d H:i:s');
                $this->db->insert('student_discount_assignments', $discount);
            }
        }
        
        // 5. Create Daily Fee Wallet
        if (!empty($data['daily_fee_wallet'])) {
            $wallet = $data['daily_fee_wallet'];
            $wallet['student_id'] = $student_id;
            
            // Check if wallet already exists for this student
            $existing = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
            if ($existing) {
                // Update existing wallet
                $this->db->where('student_id', $student_id);
                $this->db->update('daily_fee_wallet', $wallet);
            } else {
                // Insert new wallet
                $this->db->insert('daily_fee_wallet', $wallet);
            }
        }
        
        $this->db->trans_complete();
        
        echo json_encode([
            'status' => 'success',
            'student_id' => $student_id,
            'invoice_id' => $invoice_id
        ]);
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Sync Complete Payment Transaction
public function sync_payment_transaction() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $this->db->trans_start();
    
    try {
        // 1. Insert Payment
        $payment = $data['payment'];
        $payment['sync_status'] = 'SYNCED';
        $payment['timestamp'] = time();
        $this->db->insert('payment', $payment);
        $payment_id = $this->db->insert_id();
        
        // 2. Update Invoice
        if (!empty($data['invoice_update'])) {
            $invoice_update = $data['invoice_update'];
            $this->db->where('invoice_id', $invoice_update['invoice_id']);
            $this->db->update('invoice', [
                'amount_paid' => $invoice_update['amount_paid'],
                'due' => $invoice_update['due'],
                'status' => $invoice_update['status'],
                'payment_timestamp' => time()
            ]);
        }
        
        // 3. Update Wallet (if applicable)
        if (!empty($data['wallet_update'])) {
            $wallet = $data['wallet_update'];
            $this->db->where('student_id', $wallet['student_id']);
            $this->db->update('daily_fee_wallet', $wallet);
        }
        
        $this->db->trans_complete();
        
        echo json_encode(['status' => 'success', 'payment_id' => $payment_id]);
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Sync Complete Daily Fee Transaction
public function sync_daily_fee_transaction() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $this->db->trans_start();
    
    try {
        // 1. Insert Daily Fee Transaction
        $transaction = $data['transaction'];
        $transaction['transaction_code'] = 'TXN' . time() . rand(1000, 9999);
        $transaction['created_at'] = time();
        $this->db->insert('daily_fee_transactions', $transaction);
        $transaction_id = $this->db->insert_id();
        
        // 2. Update Wallet
        if (!empty($data['wallet_update'])) {
            $wallet = $data['wallet_update'];
            $this->db->where('student_id', $wallet['student_id']);
            
            // Get current wallet
            $current = $this->db->get('daily_fee_wallet')->row_array();
            
            if ($current) {
                // Update existing
                $this->db->where('student_id', $wallet['student_id']);
                $this->db->update('daily_fee_wallet', [
                    'feeding_arrears' => $current['feeding_arrears'] + ($wallet['feeding_arrears'] ?? 0),
                    'breakfast_arrears' => $current['breakfast_arrears'] + ($wallet['breakfast_arrears'] ?? 0),
                    'classes_arrears' => $current['classes_arrears'] + ($wallet['classes_arrears'] ?? 0),
                    'water_arrears' => $current['water_arrears'] + ($wallet['water_arrears'] ?? 0),
                    'transport_arrears' => $current['transport_arrears'] + ($wallet['transport_arrears'] ?? 0)
                ]);
            } else {
                // Create new - use INSERT IGNORE to handle race conditions
                $wallet['student_id'] = $transaction['student_id'];
                $this->db->query("
                    INSERT IGNORE INTO daily_fee_wallet (student_id, feeding_arrears, breakfast_arrears, classes_arrears, water_arrears, transport_arrears, last_updated)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $wallet['student_id'],
                    $wallet['feeding_arrears'] ?? 0,
                    $wallet['breakfast_arrears'] ?? 0,
                    $wallet['classes_arrears'] ?? 0,
                    $wallet['water_arrears'] ?? 0,
                    $wallet['transport_arrears'] ?? 0,
                    time()
                ]);
            }
        }
        
        // 3. Update Attendance (if applicable)
        if (!empty($data['attendance_update'])) {
            $attendance = $data['attendance_update'];
            $this->db->where('student_id', $attendance['student_id']);
            $this->db->where('DATE(timestamp)', $attendance['date']);
            $this->db->update('attendance', [
                'feeding_charged' => 1,
                'breakfast_charged' => 1,
                'classes_charged' => 1
            ]);
        }
        
        $this->db->trans_complete();
        
        echo json_encode(['status' => 'success', 'transaction_id' => $transaction_id]);
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
