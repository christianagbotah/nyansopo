<?php
// Extended methods for Finance_model - Append these to Finance_model.php

// ==================== CREDIT NOTES (Extended) ====================
public function get_credit_notes_datatable() {
    $this->load->library('datatables');
    $status = $this->input->post('status');
    
    $this->datatables->select('cn.*, s.name as student_name, s.student_code, u.name as approved_by_name')
        ->from('credit_notes cn')
        ->join('student s', 's.student_id = cn.student_id')
        ->join('users u', 'u.user_id = cn.approved_by', 'left')
        ->where('cn.status', $status);
    
    echo $this->datatables->generate();
}

public function cancel_credit_note($credit_note_id) {
    $this->db->where('credit_note_id', $credit_note_id)->update('credit_notes', ['status' => 'cancelled']);
    $this->clear_cache();
    echo json_encode(['status' => 'success', 'message' => get_phrase('credit_note_cancelled')]);
}

public function apply_credit_note($credit_note_id) {
    $this->db->trans_start();
    
    try {
        $credit_note = $this->db->where('credit_note_id', $credit_note_id)->get('credit_notes')->row_array();
        
        if ($credit_note['status'] != 'approved') {
            throw new Exception('Credit note must be approved first');
        }
        
        // Apply credit to student's outstanding invoices
        $invoices = $this->db->where('student_id', $credit_note['student_id'])
            ->where('status', 'due')
            ->where('due >', 0)
            ->order_by('creation_timestamp', 'ASC')
            ->get('invoice')->result_array();
        
        $remaining_credit = $credit_note['amount'];
        
        foreach ($invoices as $invoice) {
            if ($remaining_credit <= 0) break;
            
            $apply_amount = min($remaining_credit, $invoice['due']);
            $new_paid = $invoice['amount_paid'] + $apply_amount;
            $new_due = $invoice['due'] - $apply_amount;
            $new_status = $new_due <= 0 ? 'paid' : 'due';
            
            $this->db->where('invoice_id', $invoice['invoice_id'])->update('invoice', [
                'amount_paid' => $new_paid,
                'due' => $new_due,
                'status' => $new_status
            ]);
            
            $remaining_credit -= $apply_amount;
        }
        
        $this->db->where('credit_note_id', $credit_note_id)->update('credit_notes', ['status' => 'applied']);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        $this->clear_cache();
        echo json_encode(['status' => 'success', 'message' => get_phrase('credit_note_applied_successfully')]);
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
    }
}

// ==================== FEE STRUCTURES (Extended) ====================
public function get_fee_structures_datatable() {
    $this->load->library('datatables');
    
    $this->datatables->select('fs.*, c.name as class_name')
        ->from('fee_structures fs')
        ->join('class c', 'c.class_id = fs.class_id');
    
    if ($class_id = $this->input->post('class_id')) {
        $this->datatables->where('fs.class_id', $class_id);
    }
    if ($year = $this->input->post('year')) {
        $this->datatables->where('fs.academic_year', $year);
    }
    
    echo $this->datatables->generate();
}

public function clone_fee_structure($structure_id) {
    $structure = $this->db->where('structure_id', $structure_id)->get('fee_structures')->row_array();
    
    unset($structure['structure_id']);
    $structure['created_by'] = $this->session->userdata('user_id');
    $structure['created_at'] = date('Y-m-d H:i:s');
    
    $this->db->insert('fee_structures', $structure);
    echo json_encode(['status' => 'success', 'message' => get_phrase('fee_structure_cloned')]);
}

public function deactivate_fee_structure($structure_id) {
    $this->db->where('structure_id', $structure_id)->update('fee_structures', ['is_active' => 0]);
    echo json_encode(['status' => 'success', 'message' => get_phrase('fee_structure_deactivated')]);
}

// ==================== REPORTS ====================
public function get_report_data($type) {
    $year = $this->input->get('year') ?: get_settings('running_year');
    $term = $this->input->get('term') ?: get_settings('running_term');
    
    switch ($type) {
        case 'collection_summary':
            return $this->get_collection_summary_report($year, $term);
        case 'outstanding_by_class':
            return $this->get_outstanding_by_class_report($year, $term);
        case 'payment_methods':
            return $this->get_payment_methods_report($year, $term);
        case 'defaulters':
            return $this->get_defaulters_report($year, $term);
        default:
            return [];
    }
}

private function get_collection_summary_report($year, $term) {
    $query = "SELECT 
        DATE_FORMAT(FROM_UNIXTIME(day_timestamp), '%Y-%m') as month,
        SUM(amount) as total_collected,
        COUNT(*) as transaction_count,
        AVG(amount) as average_transaction
    FROM payment 
    WHERE year = ? AND term = ? AND can_delete != 'trash'
    GROUP BY DATE_FORMAT(FROM_UNIXTIME(day_timestamp), '%Y-%m')
    ORDER BY month";
    
    return $this->db->query($query, [$year, $term])->result_array();
}

private function get_outstanding_by_class_report($year, $term) {
    $query = "SELECT 
        c.name as class_name,
        COUNT(DISTINCT i.student_id) as student_count,
        SUM(i.amount) as total_billed,
        SUM(i.amount_paid) as total_paid,
        SUM(i.due) as total_outstanding
    FROM invoice i
    JOIN student s ON i.student_id = s.student_id
    JOIN enroll e ON s.student_id = e.student_id
    JOIN class c ON e.class_id = c.class_id
    WHERE i.year = ? AND i.term = ? AND i.can_delete != 'trash'
    GROUP BY c.class_id
    ORDER BY total_outstanding DESC";
    
    return $this->db->query($query, [$year, $term])->result_array();
}

private function get_payment_methods_report($year, $term) {
    $query = "SELECT 
        payment_method,
        COUNT(*) as transaction_count,
        SUM(amount) as total_amount
    FROM payment 
    WHERE year = ? AND term = ? AND can_delete != 'trash'
    GROUP BY payment_method
    ORDER BY total_amount DESC";
    
    return $this->db->query($query, [$year, $term])->result_array();
}

private function get_defaulters_report($year, $term) {
    $query = "SELECT 
        s.student_id, s.name, s.student_code, s.phone, s.parent_email,
        c.name as class_name,
        SUM(i.amount) as total_billed,
        SUM(i.amount_paid) as total_paid,
        SUM(i.due) as total_outstanding,
        COUNT(i.invoice_id) as invoice_count
    FROM student s
    JOIN enroll e ON s.student_id = e.student_id
    JOIN class c ON e.class_id = c.class_id
    JOIN invoice i ON s.student_id = i.student_id
    WHERE i.year = ? AND i.term = ? AND i.status = 'due' AND i.due > 0
    GROUP BY s.student_id
    HAVING total_outstanding > 0
    ORDER BY total_outstanding DESC";
    
    return $this->db->query($query, [$year, $term])->result_array();
}

public function export_report($type, $format) {
    $data = $this->get_report_data($type);
    
    if ($format == 'excel') {
        $this->load->library('excel');
        // Excel export logic
    } elseif ($format == 'pdf') {
        $this->load->library('pdf');
        // PDF export logic
    }
}

// ==================== STUDENT STATEMENTS ====================
public function download_statement($student_id, $format) {
    $statement = $this->get_student_statement($student_id);
    
    if ($format == 'pdf') {
        $this->load->library('pdf');
        $html = $this->load->view('backend/admin/finance/statement_pdf', ['statement' => $statement], true);
        $this->pdf->loadHtml($html);
        $this->pdf->render();
        $this->pdf->stream("statement_{$student_id}.pdf");
    }
}
