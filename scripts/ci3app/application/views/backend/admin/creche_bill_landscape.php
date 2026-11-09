<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Bills Report - Landscape</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; color: #2c3e50; background: #f5f5f5; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 20px; }
        page { display: flex; flex-direction: column; width: 210mm; height: 148mm; margin: 0 auto 20px auto; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 8mm; padding-bottom: 20mm; page-break-after: always; position: relative; overflow: hidden; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .scrollable-content { flex: 1; overflow: hidden; }
        .header { border-bottom: 3px solid #3498db; padding-bottom: 6px; margin-bottom: 8px; }
        .school-logo { max-height: 60px; width: auto; }
        .school-name { font-size: 17px; font-weight: bold; color: #2c3e50; text-align: center; margin-bottom: 2px; }
        .school-details { font-size: 12px; color: #34495e; text-align: center; line-height: 1.4; }
        .report-badge { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .student-info { background: linear-gradient(135deg, #ecf0f1, #d5dbdb); padding: 5px; margin-bottom: 6px; border-radius: 3px; }
        .student-info table { width: 100%; font-size: 11px; }
        .student-info td { padding: 1px 3px; }
        .student-name { font-weight: bold; text-transform: uppercase; color: #2c3e50; font-size: 12px; }
        .section-title { font-size: 11px; font-weight: bold; text-align: center; margin: 4px 0 3px 0; background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 2px; border-radius: 2px; text-transform: uppercase; }
        .bills-container { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 50px; }
        .arrears-section, .next-term-section { min-height: auto; }
        .bills-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; font-size: 10px; }
        .bills-table th { background: linear-gradient(135deg, #34495e, #2c3e50); color: white; padding: 2px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .bills-table td { padding: 2px; border: 1px solid #bdc3c7; }
        .bills-table .amount-cell { text-align: right; font-weight: 600; }
        .arrears-row { background: linear-gradient(135deg, #fadbd8, #f5b7b1); color: #c0392b; }
        .new-bill-row { background: linear-gradient(135deg, #d5f4e6, #a8e6cf); color: #27ae60; }
        .total-row { background: linear-gradient(135deg, #ecf0f1, #d5dbdb); font-weight: bold; }
        .grand-total-row { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; font-weight: bold; font-size: 11px; }
        .footer-note { position: absolute; bottom: 8mm; left: 8mm; right: 8mm; padding: 4px; background: #ecf0f1; border-left: 3px solid #e74c3c; font-size: 9px; font-style: italic; }
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
            page { box-shadow: none; margin: 0 auto; padding: 6mm; padding-bottom: 20mm; width: 210mm; height: 148mm; } 
            page { display: flex !important; }
            @page { size: A5 landscape; margin: 0; }
            
            /* Plain text print styles - no backgrounds */
            .header { border-bottom: 2px solid #000 !important; background: white !important; color: #000 !important; }
            .school-name { color: #000 !important; background: white !important; padding: 3px 6px !important; border-radius: 0 !important; }
            .school-details { color: #000 !important; }
            .report-badge { background: white !important; color: #000 !important; border: 2px solid #000 !important; font-weight: bold !important; }
            .student-info { background: white !important; border: 2px solid #000 !important; }
            .student-info strong { background: white !important; padding: 2px 3px !important; border-radius: 0 !important; }
            .student-name { background: white !important; padding: 2px 4px !important; border-radius: 0 !important; }
            .section-title { background: white !important; color: #000 !important; border: 2px solid #000 !important; border-bottom-width: 2px !important; font-weight: bold !important; padding: 3px 6px !important; }
            .bills-table th { background: white !important; color: #000 !important; border: 1px solid #000 !important; font-weight: bold !important; }
            .bills-table td { background: white !important; color: #000 !important; border: 1px solid #000 !important; }
            .arrears-row { background: white !important; color: #000 !important; font-weight: 600 !important; }
            .new-bill-row { background: white !important; color: #000 !important; }
            .total-row { background: white !important; color: #000 !important; font-weight: bold !important; border-top: 2px solid #000 !important; }
            .grand-total-row { background: white !important; color: #000 !important; font-weight: bold !important; border: 2px solid #000 !important; }
            
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
            
            .footer-note { background: white !important; border-left: 3px solid #000 !important; color: #000 !important; border: 2px solid #000 !important; }
            .discount-info-box { background: white !important; border: 2px solid #000 !important; border-left-width: 3px !important; }
            .discount-applies-to { background: white !important; color: #000 !important; border: 1px solid #000 !important; }
            
            /* Force print color adjust */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
        .discount-info-box { margin: 6px 0; padding: 4px; background: linear-gradient(135deg, #fff3cd, #ffe8a1); border-left: 3px solid #f39c12; border-radius: 3px; font-size: 9px; }
        .discount-applies-to { display: inline-block; background: #3498db; color: white; padding: 1px 4px; border-radius: 2px; font-size: 8px; margin-right: 3px; }
    </style>
</head>
<body>

<?php
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$system_title = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
$address = $this->db->get_where('settings', array('type' => 'address'))->row()->description;
$phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
$location_row = $this->db->get_where('settings', array('type' => 'location'))->row();
$location = $location_row ? $location_row->description : '';
$box_number_row = $this->db->get_where('settings', array('type' => 'box_number'))->row();
$box_number = $box_number_row ? $box_number_row->description : '';
$digital_address_row = $this->db->get_where('settings', array('type' => 'digital_address'))->row();
$digital_address = $digital_address_row ? $digital_address_row->description : '';
$logo_row = $this->db->get_where('settings', array('type' => 'logo'))->row();
$logo = $logo_row ? $logo_row->description : '';
$running_term_row = $this->db->get_where('settings', array('type' => 'running_term'))->row();
$running_term = $running_term_row ? $running_term_row->description : '';
$running_year_row = $this->db->get_where('settings', array('type' => 'running_year'))->row();
$running_year = $running_year_row ? $running_year_row->description : '';

$students_data = isset($students_data) ? $students_data : array();

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
    $class_display = $class_name;
    if(!empty($class_numeric)) {
        $class_parts = explode(' ', trim($class_name));
        $section_name = end($class_parts);
        $class_name_prefix = implode(' ', array_slice($class_parts, 0, -1));
        $class_display = $class_name_prefix . ' ' . $class_numeric . ' ' . $section_name;
    }
?>

<page>
    <div class="content-wrapper">
        <div class="scrollable-content">
            <!-- Header -->
            <div class="header" style="display: flex; align-items: center; justify-content: space-between;">
                <img src="<?php echo base_url();?>uploads/school_logo.png" alt="School Logo" class="school-logo">
                <div style="flex: 1; text-align: center;">
                    <div class="school-name"><?php echo strtoupper($system_name); ?></div>
                    <div class="school-details">
                        <?php echo $location; ?> | <?php echo $phone; ?><br>
                        <?php echo $box_number; ?> | <?php echo $digital_address; ?>
                    </div>
                </div>
                <span class="report-badge">Terminal Bill</span>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <table>
                    <tr>
                        <td style="width: 60%;"><strong>Name:</strong> <span class="student-name"><?php echo strtoupper($student->name); ?></span></td>
                        <td style="width: 40%;"><strong>Student ID:</strong> <?php echo $student->student_code; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Class:</strong> <?php echo $class_display; ?></td>
                        <td><strong>Term | Year:</strong> <?php echo 'Term '.$next_term . ', ' . $next_year; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Two Column Layout for Bills -->
            <div class="bills-container">
                <!-- Left Column: Arrears -->
                <div class="arrears-section">
                    <?php
                    $arrears_total = 0;
                    
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
                    
                    // Always show arrears section (even if 0)
                    ?>
                    <div class="section-title">Outstanding Arrears</div>
                    <table class="bills-table">
                        <thead>
                            <tr>
                                <th style="width: 70%;">Description</th>
                                <th style="width: 30%; white-space: nowrap; text-align: right;">Amount (GHC)</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                    if($has_arrears || $feeding_owe > 0 || $classes_owe > 0 || $transport_owe > 0):
                        if(!empty($invoices_owe)):
                            foreach($invoices_owe as $inv_row):
                                // Skip invoices with zero balance (fully paid)
                                if($inv_row['due'] <= 0) continue;
                                
                                if($inv_row['term'] != $next_term || $inv_row['year'] != $next_year):
                                    $rows_counter++;
                        ?>
                                <tr class="arrears-row">
                                    <td><?php echo $inv_row['title'] . ' - [T' . $inv_row['term'] . ', ' . $inv_row['year'] . ']'; ?></td>
                                    <td class="amount-cell"><?php echo number_format($inv_row['due'], 2); ?></td>
                                </tr>
                        <?php
                                    $invoice_arrears_total += $inv_row['due'];
                                endif;
                            endforeach;
                        endif;
                    
                    if($feeding_owe > 0):
                        $rows_counter++;
                    ?>
                            <tr class="arrears-row">
                                <td>Feeding Fees (Arrears)</td>
                                <td class="amount-cell"><?php echo number_format($feeding_owe, 2); ?></td>
                            </tr>
                    <?php endif; ?>

                    <?php if($classes_owe > 0):
                        $rows_counter++;
                    ?>
                            <tr class="arrears-row">
                                <td>Extra Classes (Arrears)</td>
                                <td class="amount-cell"><?php echo number_format($classes_owe, 2); ?></td>
                            </tr>
                    <?php endif; ?>
                    
                    <?php if($transport_owe > 0):
                        $rows_counter++;
                    ?>
                            <tr class="arrears-row">
                                <td>Transport (Arrears)</td>
                                <td class="amount-cell"><?php echo number_format($transport_owe, 2); ?></td>
                            </tr>
                    <?php 
                    endif;
                    
                    $arrears_total = $invoice_arrears_total + $feeding_owe + $classes_owe + $transport_owe;
                    
                    else:
                        // No arrears - show "No Outstanding Arrears" message
                    ?>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 8px; color: #27ae60; font-weight: 600; background: #d5f4e6;">
                                    No Outstanding Arrears
                                </td>
                            </tr>
                    <?php
                        $arrears_total = 0;
                    endif;
                    ?>
                            <tr class="total-row">
                                <td style="text-align: right;"><strong>Total Arrears:</strong></td>
                                <td class="amount-cell"><strong><?php echo number_format($arrears_total, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Right Column: Next Term Bills -->
                <div class="next-term-section">
                    <?php
                    $invoices_next_term = $student_data['invoices_next_term'];
                    $invoice_next_term_total = 0;
                    $next_term_subtotal = 0;
                    $next_term_discount_total = 0;
                    
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
                                <th style="width: 65%;">Description</th>
                                <th style="width: 35%; white-space: nowrap; text-align: right;">Amount (GHC)</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                    foreach($invoices_next_term as $next_row):
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
                        $invoice_next_term_total += $next_row['due'];
                    endforeach;
                    ?>
                    
                    <?php if($next_term_discount_total > 0): ?>
                            <tr style="background: #f9f9f9;">
                                <td style="text-align: right;"><strong>Subtotal:</strong></td>
                                <td class="amount-cell"><strong><?php echo number_format($next_term_subtotal, 2); ?></strong></td>
                            </tr>
                            <tr style="background: #d5f4e6; color: #27ae60; font-weight: 600;">
                                <td style="text-align: right;">Discount Applied:</td>
                                <td class="amount-cell">-<?php echo number_format($next_term_discount_total, 2); ?></td>
                            </tr>
                    <?php endif; ?>
                    
                    <?php 
                    // Show credit applied to next term bills
                    $next_term_credit = isset($student_data['next_term_credit_applied']) ? $student_data['next_term_credit_applied'] : 0;
                    if($next_term_credit > 0): 
                    ?>
                            <tr style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; font-weight: 600;">
                                <td style="text-align: right; font-size: 10px;"><strong>LESS: Prepaid Credit Applied:</strong></td>
                                <td class="amount-cell" style="font-size: 10px;"><strong>-<?php echo number_format($next_term_credit, 2); ?></strong></td>
                            </tr>
                    <?php endif; ?>
                    
                            <tr class="total-row">
                                <td style="text-align: right;"><strong>Total Next Term:</strong></td>
                                <td class="amount-cell"><strong><?php echo number_format($invoice_next_term_total, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Table (Spans Both Columns) -->
            <table class="bills-table" style="margin-top: 6px;">
                <tbody>
                    <?php
                    $feeding_next = 0;
                    $classes_next = 0;
                    $transport_next = 0;
                    $grand_total = $arrears_total + $invoice_next_term_total + $feeding_next + $classes_next + $transport_next;
                    ?>
                    
                    <?php if($feeding_next > 0): ?>
                    <tr class="new-bill-row">
                        <td style="width: 65%;">Feeding Fees (Next Term)</td>
                        <td class="amount-cell" style="width: 35%;"><?php echo number_format($feeding_next, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if($classes_next > 0): ?>
                    <tr class="new-bill-row">
                        <td>Extra Classes (Next Term)</td>
                        <td class="amount-cell"><?php echo number_format($classes_next, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if($transport_next > 0): ?>
                    <tr class="new-bill-row">
                        <td>Transport (Next Term)</td>
                        <td class="amount-cell"><?php echo number_format($transport_next, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr class="grand-total-row">
                        <td style="text-align: right; font-size: 11px;"><strong>TOTAL AMOUNT DUE:</strong></td>
                        <td class="amount-cell" style="font-size: 11px;"><strong><?php echo number_format($grand_total, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="footer-note">
        <?php 
        // Get only the first phone number (handle both | and / separators)
        $phone_parts = preg_split('/[|\/]/', $phone);
        $first_phone = trim($phone_parts[0]);
        ?>
        <?php if($arrears_total > 0): ?>
            <strong>Note:</strong> Please settle all outstanding arrears (GHC <?php echo number_format($arrears_total, 2); ?>)
            <?php 
            $next_term_total = $invoice_next_term_total + $feeding_next + $classes_next + $transport_next;
            if($next_term_total > 0): 
            ?>
                and at least part of next term bills (GHC <?php echo number_format($next_term_total, 2); ?>)
            <?php endif; ?>
            before the next term begins. For inquiries, contact the accounts office on <?php echo $first_phone; ?>.
        <?php else: ?>
            <strong>Note:</strong> Please settle at least part of next term bills (GHC <?php echo number_format($invoice_next_term_total + $feeding_next + $classes_next + $transport_next, 2); ?>) before the term begins. For inquiries, contact the accounts office on <?php echo $first_phone; ?>.
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
let itemsPerPage = 2; // Maximum 2 bills per page for landscape
let totalPages = 1;
let allPages = [];

window.onload = function() {
    initPagination();
};

function initPagination() {
    allPages = document.querySelectorAll('page');
    totalPages = Math.ceil(allPages.length / itemsPerPage);
    
    if(totalPages <= 1) {
        document.querySelector('.pagination-controls').style.display = 'none';
    }
    
    showPage(1);
}

function showPage(pageNum) {
    currentPage = pageNum;
    
    allPages.forEach((page, index) => {
        const pageNumber = Math.floor(index / itemsPerPage) + 1;
        if(pageNumber === currentPage) {
            page.classList.add('active');
        } else {
            page.classList.remove('active');
        }
    });
    
    updatePaginationControls();
}

function updatePaginationControls() {
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, allPages.length);
    
    document.getElementById('pageInfo').textContent = 
        `Page ${currentPage} of ${totalPages} (Showing ${startIndex}-${endIndex} of ${allPages.length} students)`;
    
    document.getElementById('btnPrev').disabled = (currentPage === 1);
    document.getElementById('btnNext').disabled = (currentPage === totalPages);
}

function previousPage() {
    if(currentPage > 1) {
        showPage(currentPage - 1);
    }
}

function nextPage() {
    if(currentPage < totalPages) {
        showPage(currentPage + 1);
    }
}
</script>

</body>
</html>
