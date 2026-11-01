<?php
// Student Termly Bill Controller Methods
// Add these methods to Admin.php controller

/**
 * Display termly bill for a student (arrears + current term bills)
 */
function student_termly_bill($invoice_code = '') {
    if(empty($invoice_code)) {
        show_error('Invoice code is required');
        return;
    }
    
    $page_data['invoice_code'] = $invoice_code;
    $page_data['bill_data'] = $this->get_student_termly_bill_data($invoice_code);
    $this->load->view('backend/admin/modal_view_termly_bill', $page_data);
}

/**
 * Print termly bill for a student
 */
function print_termly_bill($invoice_code = '') {
    if(empty($invoice_code)) {
        show_error('Invoice code is required');
        return;
    }
    
    $page_data['invoice_code'] = $invoice_code;
    $page_data['bill_data'] = $this->get_student_termly_bill_data($invoice_code);
    $this->load->view('backend/admin/print_termly_bill', $page_data);
}

/**
 * Get termly bill data (arrears + current term bills)
 */
private function get_student_termly_bill_data($invoice_code) {
    // Get current term invoice details
    $current_invoice = $this->db->get_where('invoice', array('invoice_code' => $invoice_code))->row();
    
    if(!$current_invoice) {
        show_error('Invoice not found');
        return;
    }
    
    $student_id = $current_invoice->student_id;
    $current_year = $current_invoice->year;
    $current_term = isset($current_invoice->term) ? $current_invoice->term : $current_invoice->sem;
    
    // Get all current term invoice items
    $this->db->select('title, description, amount, amount_paid, due');
    $this->db->where('invoice_code', $invoice_code);
    $this->db->where('student_id', $student_id);
    $this->db->order_by('title', 'ASC');
    $current_items = $this->db->get('invoice')->result_array();
    
    // Calculate current term totals
    $current_total = 0;
    $current_paid = 0;
    $current_due = 0;
    
    foreach($current_items as $item) {
        $current_total += $item['amount'];
        $current_paid += $item['amount_paid'];
        $current_due += $item['due'];
    }
    
    // Get arrears (all previous unpaid amounts)
    // This includes all invoices before this term/year
    $this->db->select('SUM(due) as total_arrears');
    $this->db->from('invoice');
    $this->db->where('student_id', $student_id);
    $this->db->where('invoice_code !=', $invoice_code);
    
    // Get invoices from previous terms/years
    $this->db->group_start();
    $this->db->where('year <', $current_year);
    $this->db->or_group_start();
    $this->db->where('year', $current_year);
    if(isset($current_invoice->term)) {
        $this->db->where('term <', $current_term);
    } else {
        $this->db->where('sem <', $current_term);
    }
    $this->db->group_end();
    $this->db->group_end();
    
    $arrears_result = $this->db->get()->row();
    $arrears = $arrears_result ? $arrears_result->total_arrears : 0;
    
    // Get student info
    $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
    
    // Get enrollment info
    $this->db->where('student_id', $student_id);
    $this->db->where('year', $current_year);
    if(isset($current_invoice->term)) {
        $this->db->where('term', $current_term);
    } else {
        $this->db->where('sem', $current_term);
    }
    $enroll = $this->db->get('enroll')->row();
    
    $class = null;
    $section = null;
    if($enroll) {
        $class = $this->db->get_where('class', array('class_id' => $enroll->class_id))->row();
        $section = $this->db->get_where('section', array('section_id' => $enroll->section_id))->row();
    }
    
    // Check for discounts on current invoice
    $discount_query = $this->db->get_where('invoice_discounts', array(
        'invoice_code' => $invoice_code,
        'status' => 'approved'
    ));
    
    $total_discount = 0;
    $discount_details = array();
    
    if($discount_query->num_rows() > 0) {
        foreach($discount_query->result_array() as $disc) {
            $total_discount += $disc['discount_amount'];
            $profile = $this->db->where('profile_id', $disc['profile_id'])->get('discount_profiles')->row();
            $applies_to = 'All Bill Items';
            if($profile && $profile->bill_item_ids !== '*') {
                $bill_item_ids = explode(',', $profile->bill_item_ids);
                $bill_items = $this->db->where_in('id', $bill_item_ids)->get('bill_item')->result_array();
                $applies_to = implode(', ', array_column($bill_items, 'title'));
            }
            $disc['applies_to'] = $applies_to;
            $discount_details[] = $disc;
        }
    }
    
    // Calculate grand total
    $grand_total = $arrears + $current_total;
    
    return array(
        'student' => $student,
        'class' => $class,
        'section' => $section,
        'invoice_code' => $invoice_code,
        'year' => $current_year,
        'term' => $current_term,
        'term_label' => isset($current_invoice->term) ? 'Term' : 'Semester',
        'creation_timestamp' => $current_invoice->creation_timestamp,
        'arrears' => $arrears,
        'current_items' => $current_items,
        'current_total' => $current_total,
        'current_paid' => $current_paid,
        'current_due' => $current_due,
        'discount_details' => $discount_details,
        'total_discount' => $total_discount,
        'grand_total' => $grand_total,
        'status' => $current_invoice->status
    );
}
