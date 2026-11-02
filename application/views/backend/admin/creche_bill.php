<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Bills Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #2c3e50; background: #f5f5f5; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 20px; }
        page { display: flex; flex-direction: column; width: 148mm; height: 210mm; margin: 0 auto 20px auto; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 10mm; padding-bottom: 25mm; page-break-after: always; position: relative; overflow: hidden; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .scrollable-content { flex: 1; overflow: hidden; }
        .header { border-bottom: 3px solid #3498db; padding-bottom: 8px; margin-bottom: 10px; }
        .school-logo { max-height: 60px; width: auto; }
        .school-name { font-size: 15px; font-weight: bold; color: #2c3e50; text-align: center; margin-bottom: 3px; }
        .school-details { font-size: 11px; color: #34495e; text-align: center; line-height: 1.4; }
        .report-badge { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .student-info { background: linear-gradient(135deg, #ecf0f1, #d5dbdb); padding: 7px; margin-bottom: 8px; border-radius: 4px; }
        .student-info table { width: 100%; font-size: 11px; }
        .student-info td { padding: 2px 4px; }
        .student-name { font-weight: bold; text-transform: uppercase; color: #2c3e50; font-size: 13px; }
        .section-title { font-size: 12px; font-weight: bold; text-align: center; margin: 6px 0 4px 0; background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 4px; border-radius: 3px; text-transform: uppercase; }
        .bills-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 11px; }
        .bills-table th { background: linear-gradient(135deg, #34495e, #2c3e50); color: white; padding: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .bills-table td { padding: 3px 4px; border: 1px solid #bdc3c7; }
        .bills-table .amount-cell { text-align: right; font-weight: 600; }
        .arrears-row { background: linear-gradient(135deg, #fadbd8, #f5b7b1); color: #c0392b; }
        .new-bill-row { background: linear-gradient(135deg, #d5f4e6, #a8e6cf); color: #27ae60; }
        .total-row { background: linear-gradient(135deg, #ecf0f1, #d5dbdb); font-weight: bold; }
        .grand-total-row { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; font-weight: bold; font-size: 12px; }
        .footer-note { position: absolute; bottom: 10mm; left: 10mm; right: 10mm; padding: 6px; background: #ecf0f1; border-left: 3px solid #e74c3c; font-size: 10px; font-style: italic; }
        .print-btn { display: none; }
        .pagination-controls { display: none; }
        @media screen { 
            body { padding: 20px; }
            .print-btn { display: block; text-align: center; margin: 15px 0; position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%); z-index: 1000; } 
            .btn { padding: 12px 24px; margin: 0 6px; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.25); text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; } 
            .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.3); }
            .btn:active { transform: translateY(0); box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
            .btn-print { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; } 
            .btn-print:hover { background: linear-gradient(135deg, #2ecc71, #27ae60); }
            .btn-close { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
            .btn-close:hover { background: linear-gradient(135deg, #c0392b, #e74c3c); }
            .pagination-controls { display: flex; align-items: center; justify-content: center; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; background: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); gap: 12px; }
            .btn-nav { padding: 8px 16px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; border-radius: 4px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
            .btn-nav:hover:not(:disabled) { background: linear-gradient(135deg, #2980b9, #3498db); transform: translateY(-1px); }
            .btn-nav:disabled { opacity: 0.4; cursor: not-allowed; }
            .page-info { font-size: 13px; font-weight: 600; color: #2c3e50; min-width: 120px; text-align: center; }
            page { display: none; }
            page.active { display: flex; }
        }
        @media print { 
            body { margin: 0; padding: 0; background: white; display: block; } 
            page { box-shadow: none; margin: 0 auto; padding: 8mm; padding-bottom: 25mm; width: 148mm; height: 210mm; } 
            
            /* Remove all gradients and colors for black & white printing */
            .header { 
                border-bottom: 3px solid #000 !important; 
                background: white !important; 
                color: #000 !important;
            }
            .school-name { 
                color: #000 !important; 
                background: white !important;
                padding: 4px 8px !important;
                border-radius: 0 !important;
            }
            .school-details { color: #000 !important; }
            .report-badge { 
                background: white !important; 
                color: #000 !important; 
                border: 2px solid #000 !important;
                font-weight: bold !important;
            }
            .student-info { 
                background: white !important; 
                border: 2px solid #000 !important;
            }
            .student-info strong {
                background: white !important;
                padding: 2px 4px !important;
                border-radius: 0 !important;
            }
            .student-name {
                background: white !important;
                padding: 3px 6px !important;
                border-radius: 0 !important;
            }
            .section-title { 
                background: white !important; 
                color: #000 !important; 
                border: 2px solid #000 !important;
                border-bottom-width: 2px !important;
                font-weight: bold !important;
                padding: 5px 8px !important;
            }
            .bills-table th { 
                background: white !important; 
                color: #000 !important; 
                border: 1px solid #000 !important;
                font-weight: bold !important;
            }
            .bills-table td { 
                border: 1px solid #000 !important;
                background: white !important;
            }
            .arrears-row td { 
                background: white !important; 
                color: #000 !important;
                font-style: italic;
            }
            .new-bill-row td { 
                background: white !important; 
                color: #000 !important;
            }
            .total-row { 
                background: white !important; 
                border-top: 2px solid #000 !important;
                border-bottom: 2px solid #000 !important;
            }
            .total-row td {
                background: white !important;
            }
            .grand-total-row { 
                background: white !important; 
                color: #000 !important; 
                border: 2px solid #000 !important;
                font-weight: bold !important;
            }
            .grand-total-row td {
                background: white !important;
            }
            
            /* Credit and Net Due rows for print */
            tr[style*="background: linear-gradient(135deg, #d4edda"] {
                background: white !important;
                border: 2px solid #000 !important;
            }
            tr[style*="background: linear-gradient(135deg, #d4edda"] td {
                background: white !important;
                color: #000 !important;
                font-weight: bold !important;
            }
            tr[style*="background: linear-gradient(135deg, #3498db"] {
                background: white !important;
                border: 2px solid #000 !important;
            }
            tr[style*="background: linear-gradient(135deg, #3498db"] td {
                background: white !important;
                color: #000 !important;
                font-weight: bold !important;
            }
            
            .footer-note { 
                background: white !important; 
                border: 2px solid #000 !important;
                border-left-width: 3px !important;
            }
            .footer-note strong {
                background: white !important;
                padding: 2px 5px !important;
                border-radius: 0 !important;
            }
            
            /* Discount info styling for print */
            div[style*="background: #d4edda"] {
                background: white !important;
                border: 2px solid #000 !important;
                border-left-width: 3px !important;
            }
            div[style*="background: #d4edda"] div[style*="font-weight: 700"] {
                background: white !important;
                padding: 3px 6px !important;
                border-radius: 0 !important;
            }
            div[style*="background: #d4edda"] * {
                color: #000 !important;
            }
            div[style*="background: #d4edda"] strong {
                background: white !important;
                padding: 2px 4px !important;
                border-radius: 0 !important;
            }
            
            /* Make text darker and more readable */
            body, td, th, div, span, p {
                color: #000 !important;
            }
            
            /* Ensure borders are visible */
            .bills-table {
                border-collapse: collapse !important;
            }
            
            page { display: flex !important; }
            @page { size: A5 portrait; margin: 0; }
            
            /* Force print color adjust */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
<?php
// Get data from controller
$students_data = isset($students_data) ? $students_data : array();
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
$location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
$box_number = $this->db->get_where('settings', array('type' => 'box_number'))->row()->description;
$digital_address = $this->db->get_where('settings', array('type' => 'digital_address'))->row()->description;
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

/*
 * DATA SOURCES AND DISCOUNT HANDLING:
 * 
 * 1. BILLED INVOICE DATA (from 'invoice' table):
 *    - Fetched when fee_category is 'all' or 'billed_invoice'
 *    - The 'due' field already includes applied discounts
 *    - Discounts are stored in 'invoice_discounts' table and applied via 'invoice_discount_items'
 *    - System automatically calculates: due = amount - discount_amount
 *    - Both arrears (past terms) and next term invoices come from this table
 * 
 * 2. DAILY FEES DATA (from 'daily_fee_wallet' table):
 *    - Fetched when fee_category is 'all' or 'daily_fees'
 *    - Includes: feeding_arrears, classes_arrears, transport_arrears
 *    - These are accumulated amounts from daily fee transactions
 *    - Discounts for daily fees are handled separately via discount profiles
 * 
 * 3. FEE CATEGORY FILTER:
 *    - 'all': Shows both invoice items and daily fee arrears
 *    - 'billed_invoice': Shows only invoice items (term bills)
 *    - 'daily_fees': Shows only daily fee wallet arrears
 */

foreach($students_data as $student_data):
    $student = $student_data['student'];
    $class_name = $student_data['class_name'];
    $class_numeric = isset($student_data['class_numeric']) ? $student_data['class_numeric'] : '';
    $invoices_owe = $student_data['invoices_owe'];
    $invoices_next_term = $student_data['invoices_next_term'];
    $feeding_owe = $student_data['feeding_owe'];
    $classes_owe = $student_data['classes_owe'];
    $transport_owe = $student_data['transport_owe'];
    $next_term = $student_data['next_term'];
    $next_year = $student_data['next_year'];
    $fee_category = $student_data['fee_category'];
    
    // Prepare class display with numeric
    // Format: "NURSERY 2 LILY" where "NURSERY" is class name, "2" is from name_numeric, and "LILY" is section
    $class_display = $class_name;
    if(!empty($class_numeric)) {
        // Extract section name from class_name (last part after space)
        $class_parts = explode(' ', trim($class_name));
        $section_name = end($class_parts);
        // Extract class name prefix (everything before the last space)
        $class_name_prefix = implode(' ', array_slice($class_parts, 0, -1));
        // Combine class name + numeric + section name
        $class_display = $class_name_prefix . ' ' . $class_numeric . ' ' . $section_name;
    }
    
    // Fetch discounts for arrears invoices
    $discount_items_map_owe = array();
    if(!empty($student_data['invoice_code_owe'])) {
        $discount_query_owe = $this->db->where('invoice_code', $student_data['invoice_code_owe'])
            ->where('status', 'approved')
            ->get('invoice_discounts');
        if($discount_query_owe->num_rows() > 0) {
            foreach($discount_query_owe->result_array() as $disc) {
                $disc_items = $this->db->where('discount_id', $disc['discount_id'])->get('invoice_discount_items')->result_array();
                foreach($disc_items as $ditem) {
                    $discount_items_map_owe[$ditem['item_title']] = $ditem;
                }
            }
        }
    }
    
    // Fetch discounts for next term invoices
    $discount_items_map_next = array();
    if(!empty($student_data['invoice_code_next'])) {
        $discount_query_next = $this->db->where('invoice_code', $student_data['invoice_code_next'])
            ->where('status', 'approved')
            ->get('invoice_discounts');
        if($discount_query_next->num_rows() > 0) {
            foreach($discount_query_next->result_array() as $disc) {
                $disc_items = $this->db->where('discount_id', $disc['discount_id'])->get('invoice_discount_items')->result_array();
                foreach($disc_items as $ditem) {
                    $discount_items_map_next[$ditem['item_title']] = $ditem;
                }
            }
        }
    }
?>
<page>
    <div class="content-wrapper">
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50px; vertical-align: top;">
                    <img src="<?php echo base_url(); ?>uploads/school_logo.png" class="school-logo" alt="Logo">
                </td>
                <td style="vertical-align: top;">
                    <div class="school-name"><?php echo strtoupper($system_name); ?></div>
                    <div class="school-details">
                        <?php echo $location; ?> | <?php echo $system_phone; ?><br>
                        <?php echo $box_number; ?> | <?php echo $digital_address; ?>
                    </div>
                </td>
                <td style="width: 100px; text-align: right; vertical-align: top;">
                    <div class="report-badge">Terminal Bills</div>
                    <div style="font-size: 9px; margin-top: 3px; font-weight: 600; color: #2c3e50;">
                        Term <?php echo $next_term; ?> | <?php echo $next_year; ?>
                    </div>
                    <div style="font-size: 9px; margin-top: 2px; font-weight: 600;">
                        <?php echo date('d M, Y'); ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td style="width: 25%;"><strong>Student:</strong></td>
                <td class="student-name"><?php echo strtoupper($student->name); ?></td>
            </tr>
            <tr>
                <td><strong>ID:</strong></td>
                <td><?php echo $student->student_code; ?></td>
            </tr>
            <tr>
                <td><strong>Class:</strong></td>
                <td><?php echo $class_display; ?></td>
            </tr>
        </table>
    </div>

    <?php
    $invoice_arrears_total = 0;
    $rows_counter = 0;
    $has_arrears = false;
    
    if(!empty($invoices_owe)):
        foreach($invoices_owe as $inv_row):
            if($inv_row['term'] != $next_term || $inv_row['year'] != $next_year):
                $has_arrears = true;
                break;
            endif;
        endforeach;
    endif;
    
    if($has_arrears || $feeding_owe > 0 || $classes_owe > 0 || $transport_owe > 0):
    ?>
    <div class="section-title">Outstanding Arrears</div>
    <table class="bills-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="width: 95px; white-space: nowrap;">Amount (<?php echo $currency; ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if(!empty($invoices_owe)):
                foreach($invoices_owe as $inv_row):
                    // Skip invoices with zero balance (fully paid)
                    if($inv_row['due'] <= 0) continue;
                    
                    if($inv_row['term'] != $next_term || $inv_row['year'] != $next_year):
                        $rows_counter++;
                        // Get original amount before discount
                        $item_discount = isset($discount_items_map_owe[$inv_row['title']]) ? $discount_items_map_owe[$inv_row['title']]['discount_amount'] : 0;
                        $original_amount = $inv_row['amount'] + $item_discount;
            ?>
            <tr class="arrears-row">
                <td><?php echo $inv_row['title'].' [Term '.$inv_row['term'].' | '.$inv_row['year'].']'; ?></td>
                <td class="amount-cell"><?php echo number_format($inv_row['due'], 2); ?></td>
            </tr>
            <?php
                        $invoice_arrears_total += $inv_row['due'];
                    endif;
                endforeach;
            endif;
            
            if($feeding_owe > 0):
            ?>
            <tr class="arrears-row">
                <td>Feeding Fee</td>
                <td class="amount-cell"><?php echo number_format($feeding_owe, 2); ?></td>
            </tr>
            <?php endif; if($classes_owe > 0): ?>
            <tr class="arrears-row">
                <td>Classes Fee</td>
                <td class="amount-cell"><?php echo number_format($classes_owe, 2); ?></td>
            </tr>
            <?php endif; if($transport_owe > 0): ?>
            <tr class="arrears-row">
                <td>Transport Fare</td>
                <td class="amount-cell"><?php echo number_format($transport_owe, 2); ?></td>
            </tr>
            <?php
            endif;
            $arrears_total = $invoice_arrears_total + $feeding_owe + $classes_owe + $transport_owe;
            ?>
            <tr class="total-row">
                <td><strong>Total Arrears</strong></td>
                <td class="amount-cell"><strong><?php echo number_format($arrears_total, 2); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php else: $arrears_total = 0; endif; ?>

    <?php
    $invoice_next_term_total = 0;
    $next_term_subtotal = 0; // Subtotal before discount
    $next_term_discount_total = 0; // Total discount for next term
    if(!empty($invoices_next_term)):
    ?>
    <div class="section-title">Next Term Bills</div>
    
    <?php
    // Check if there are any discounts for next term
    $has_next_term_discount = false;
    foreach($invoices_next_term as $next_row):
        $item_discount_next = isset($discount_items_map_next[$next_row['title']]) ? $discount_items_map_next[$next_row['title']]['discount_amount'] : 0;
        if($item_discount_next > 0) {
            $has_next_term_discount = true;
            break;
        }
    endforeach;
    ?>
    
    <table class="bills-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="width: 95px; white-space: nowrap;">Amount (<?php echo $currency; ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($invoices_next_term as $next_row): 
                // Skip invoices with zero balance (fully paid)
                if($next_row['due'] <= 0) continue;
                
                // Get original amount BEFORE credit was applied
                $credit_on_item = floatval($next_row['credit_applied'] ?? 0);
                $original_amount_before_credit = $next_row['amount'] + $credit_on_item;
                
                // Get discount
                $item_discount_next = isset($discount_items_map_next[$next_row['title']]) ? $discount_items_map_next[$next_row['title']]['discount_amount'] : 0;
                $original_amount_next = $original_amount_before_credit + $item_discount_next;
                $next_term_subtotal += $original_amount_next;
                $next_term_discount_total += $item_discount_next;
            ?>
            <tr class="new-bill-row">
                <td><?php echo $next_row['title']; ?></td>
                <td class="amount-cell"><?php echo number_format($original_amount_next, 2); ?></td>
            </tr>
            <?php
                // Use the DUE amount for total (already has credit applied)
                $invoice_next_term_total += $next_row['due'];
            endforeach;
            ?>
            <?php if($next_term_discount_total > 0): ?>
            <tr class="total-row">
                <td><strong>Subtotal (Before Discount)</strong></td>
                <td class="amount-cell"><strong><?php echo number_format($next_term_subtotal, 2); ?></strong></td>
            </tr>
            <tr style="background: #d4edda; color: #28a745;">
                <td><strong>Discount</strong></td>
                <td class="amount-cell"><strong>- <?php echo number_format($next_term_discount_total, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
            <?php 
            // Show credit applied to next term bills
            $next_term_credit = isset($student_data['next_term_credit_applied']) ? $student_data['next_term_credit_applied'] : 0;
            if($next_term_credit > 0): 
            ?>
            <tr style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32;">
                <td><strong>LESS: Prepaid Credit Applied</strong></td>
                <td class="amount-cell"><strong>- <?php echo number_format($next_term_credit, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td><strong>Total Next Term Bills</strong></td>
                <td class="amount-cell"><strong><?php echo number_format($invoice_next_term_total, 2); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>

    <table class="bills-table" style="margin-top: 8px;">
        <tr class="grand-total-row">
            <td><strong>TOTAL AMOUNT DUE</strong></td>
            <td class="amount-cell" style="width: 70px;"><strong><?php echo number_format($arrears_total + $invoice_next_term_total, 2); ?></strong></td>
        </tr>
    </table>

    </div>

    <div class="footer-note">
        <?php 
        // Get only the first phone number (handle both | and / separators)
        $phone_parts = preg_split('/[|\/]/', $system_phone);
        $first_phone = trim($phone_parts[0]);
        ?>
        <?php if($arrears_total > 0): ?>
        <strong>Note:</strong> Please settle all outstanding arrears (<?php echo numfmt_format_currency($fmt, $arrears_total, $currency); ?>) and at least part of next term bills (<?php echo numfmt_format_currency($fmt, $invoice_next_term_total, $currency); ?>) before the next term begins. For inquiries, contact the accounts office on <?php echo $first_phone; ?>.
        <?php else: ?>
        <strong>Note:</strong> Please settle at least part of the next term bills (<?php echo numfmt_format_currency($fmt, $invoice_next_term_total, $currency); ?>) before the term begins. For inquiries, contact the accounts office on <?php echo $first_phone; ?>.
        <?php endif; ?>
    </div>
</page>
<?php endforeach; ?>

<div class="pagination-controls">
    <button onclick="previousPage()" class="btn-nav" id="btnPrev">‹ Previous</button>
    <span class="page-info" id="pageInfo">Page 1 of 1</span>
    <button onclick="nextPage()" class="btn-nav" id="btnNext">Next ›</button>
</div>

<div class="print-btn">
    <button onclick="window.print()" class="btn btn-print">🖨 Print Bills</button>
    <button onclick="window.close()" class="btn btn-close">✖ Close</button>
</div>

<script>
let currentPage = 1;
let itemsPerPage = 3; // Maximum 3 bills per view
let totalPages = 1;
let allPages = [];

window.onload = function() {
    initPagination();
};

function initPagination() {
    allPages = document.querySelectorAll('page');
    totalPages = Math.ceil(allPages.length / itemsPerPage);
    
    // Hide all pages initially
    allPages.forEach(page => page.classList.remove('active'));
    
    // Show first set of pages
    showPage(1);
    updatePageInfo();
}

function showPage(pageNum) {
    currentPage = pageNum;
    
    // Hide all pages
    allPages.forEach(page => page.classList.remove('active'));
    
    // Calculate which pages to show
    const startIdx = (pageNum - 1) * itemsPerPage;
    const endIdx = Math.min(startIdx + itemsPerPage, allPages.length);
    
    // Show pages for current page
    for (let i = startIdx; i < endIdx; i++) {
        allPages[i].classList.add('active');
    }
    
    updatePageInfo();
}

function updatePageInfo() {
    const startStudent = ((currentPage - 1) * itemsPerPage) + 1;
    const endStudent = Math.min(currentPage * itemsPerPage, allPages.length);
    
    document.getElementById('pageInfo').textContent = 
        `Students ${startStudent}-${endStudent} of ${allPages.length}`;
    
    // Update button states
    document.getElementById('btnPrev').disabled = currentPage === 1;
    document.getElementById('btnNext').disabled = currentPage === totalPages;
}

function previousPage() {
    if (currentPage > 1) {
        showPage(currentPage - 1);
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        showPage(currentPage + 1);
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        previousPage();
    } else if (e.key === 'ArrowRight') {
        nextPage();
    }
});
</script>
</body>
</html>