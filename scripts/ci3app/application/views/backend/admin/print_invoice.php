<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $param2; ?></title>
    <?php
    // Load the invoice view content
    $invoice_data = $this->db->get_where('invoice', array('invoice_code' => $param2));
    $edit_data = $invoice_data->result_array();
    $currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
    $currency = $currency_row ? $currency_row->description : 'GHS';
    $system_name_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
    $system_name = $system_name_row ? $system_name_row->description : 'School Management System';
    $theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
    $theme_color = $theme_color_row ? $theme_color_row->description : '667eea';
    
    // Ensure theme color has # prefix
    if(strpos($theme_color, '#') !== 0) {
        $theme_color = '#' . $theme_color;
    }
    
    // Create gradient colors from theme color
    function adjustBrightness($hex, $steps) {
        $hex = str_replace('#', '', $hex);
        if(strlen($hex) == 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
    $gradient_light = adjustBrightness($theme_color, 20);
    $gradient_dark = adjustBrightness($theme_color, -30);
    
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    $first_invoice = $invoice_data->row();
    $student_id = $first_invoice->student_id;
    $param3 = $first_invoice->year;
    $param4 = isset($first_invoice->sem) ? $first_invoice->sem : $first_invoice->term;
    $inv_status = ucwords($first_invoice->status);

    $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
    $this->db->order_by('enroll_id', 'desc');
    $this->db->limit(1);
    $enroll_data = $this->db->get_where('enroll', array('student_id' => $student_id))->row();
    $class_id = $enroll_data->class_id;
    $section_id = $enroll_data->section_id;
    $class = $this->db->get_where('class', array('class_id' => $class_id))->row();
    $section = $this->db->get_where('section', array('section_id' => $section_id))->row();

    // Check for discounts
    $discount_query = $this->db->get_where('invoice_discounts', array(
        'invoice_code' => $param2,
        'status' => 'approved'
    ));
    $total_discount = 0;
    $discount_details = array();
    $discount_items_map = array();
    if($discount_query->num_rows() > 0) {
        foreach($discount_query->result_array() as $disc) {
            $total_discount += $disc['discount_amount'];
            // Get profile to check bill_item_ids
            $profile = $this->db->where('profile_id', $disc['profile_id'])->get('discount_profiles')->row();
            $applies_to = 'All Bill Items';
            if($profile && $profile->bill_item_ids !== '*') {
                $bill_item_ids = explode(',', $profile->bill_item_ids);
                $bill_items = $this->db->where_in('id', $bill_item_ids)->get('bill_item')->result_array();
                $applies_to = implode(', ', array_column($bill_items, 'title'));
            }
            $disc['applies_to'] = $applies_to;
            $discount_details[] = $disc;
            
            // Get per-item discount details
            $disc_items = $this->db->where('discount_id', $disc['discount_id'])->get('invoice_discount_items')->result_array();
            foreach($disc_items as $ditem) {
                $discount_items_map[$ditem['item_title']] = $ditem;
            }
        }
    }
    
    // Check for pending discounts
    $pending_discount_query = $this->db->get_where('invoice_discounts', array(
        'invoice_code' => $param2,
        'status' => 'pending'
    ));
    $has_pending_discount = $pending_discount_query->num_rows() > 0;
    $pending_discount_amount = 0;
    if($has_pending_discount) {
        foreach($pending_discount_query->result_array() as $pdisc) {
            $pending_discount_amount += $pdisc['discount_amount'];
        }
    }
    ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; padding: 0; }
        .invoice-container { max-width: 580px; margin: 0 auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); border-radius: 0; overflow: hidden; width: 100%; display: flex; flex-direction: column; min-height: 100vh; }
        .invoice-body { padding: 8px; flex: 1; overflow: auto; }
        .invoice-footer { padding: 10px 15px; background: #f8f9fa; text-align: center; flex-shrink: 0; }
        @media print {
            body { background: white; padding: 0; display: block; }
            .invoice-container { 
                box-shadow: none; 
                border-radius: 0; 
                page-break-inside: avoid; 
                display: block; 
                min-height: auto;
                transform-origin: top left;
            }
            /* Dynamic scaling for many items */
            @supports (zoom: 1) {
                .invoice-container {
                    zoom: 0.95;
                }
            }
            @supports not (zoom: 1) {
                .invoice-container {
                    transform: scale(0.95);
                }
            }
            .invoice-body { overflow: visible; }
            .invoice-footer { page-break-inside: avoid; }
        }
        .invoice-header { background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; padding: 8px; position: relative; flex-shrink: 0; }
        .invoice-header::after { content: ''; position: absolute; bottom: -10px; left: 0; right: 0; height: 10px; background: white; border-radius: 10px 10px 0 0; }
        .school-logo { max-height: 40px; background: white; padding: 4px; border-radius: 50%; }
        .invoice-title { font-size: 20px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .invoice-meta { display: flex; justify-content: space-between; padding: 10px 15px; background: #f8f9fa; border-bottom: 2px solid <?php echo $theme_color; ?>; }
        .meta-box { flex: 1; }
        .meta-label { font-size: 9px; text-transform: uppercase; color: #6c757d; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
        .meta-value { font-size: 11px; font-weight: 600; color: #212529; }
        .invoice-body { padding: 8px; flex: 1; }
        .party-details { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .party-box { flex: 1; padding: 5px 8px; background: #f8f9fa; border-radius: 4px; margin: 0 4px; }
        .party-box:first-child { margin-left: 0; }
        .party-box:last-child { margin-right: 0; }
        .party-title { font-size: 11px; text-transform: uppercase; color: <?php echo $theme_color; ?>; font-weight: 700; margin-bottom: 2px; letter-spacing: 0.3px; }
        .party-info { font-size: 13px; line-height: 1.4; color: #495057; }
        .party-info strong { color: #212529; display: block; font-size: 14px; margin-bottom: 1px; }
        .invoice-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; margin-bottom: 6px; }
        .invoice-table thead th { background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; padding: 8px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
        .invoice-table thead th:first-child { border-radius: 6px 0 0 0; }
        .invoice-table thead th:last-child { border-radius: 0 6px 0 0; text-align: right; }
        .invoice-table tbody td { padding: 8px; border-bottom: 1px solid #e9ecef; font-size: 14px; }
        .invoice-table tfoot td { padding: 8px; font-weight: 600; font-size: 15px; }
        .total-row { background: #f8f9fa; border-top: 2px solid <?php echo $theme_color; ?>; }
        .discount-row { color: #28a745; }
        .final-total-row { background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; font-size: 16px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .status-partial { background: #fff3cd; color: #856404; }
        .invoice-footer { padding: 10px 15px; background: #f8f9fa; border-top: 2px solid <?php echo $theme_color; ?>; text-align: center; }
        .print-btn { background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; border: none; padding: 8px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s; margin: 20px 0; }
        .print-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        .discount-info { background: #d4edda; border-left: 3px solid #28a745; padding: 8px; margin: 20px 0; border-radius: 4px; }
        .discount-info-title { font-weight: 700; color: #155724; margin-bottom: 6px; font-size: 14px; }
        .discount-item { font-size: 13px; color: #155724; margin: 3px 0; }
        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; border-radius: 0; }
            .print-btn { display: none; }
            .invoice-header { background: <?php echo $theme_color; ?> !important; background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: white !important; }
            .invoice-table thead th { background: <?php echo $theme_color; ?> !important; background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: white !important; }
            .final-total-row { background: <?php echo $theme_color; ?> !important; background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: white !important; }
            .discount-info { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" class="print-btn">
            <i class="entypo-print"></i> Print Invoice
        </button>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <img src="<?php echo base_url(); ?>uploads/school_logo.png" class="school-logo" alt="School Logo">
                </div>
                <div style="text-align: right;">
                    <h1 class="invoice-title" style="color: white;">Invoice</h1>
                    <div style="font-size: 18px; margin-top: 5px; opacity: 0.9; color: white;">
                        #<?php echo $param2; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Information -->
        <div class="invoice-meta">
            <div class="meta-box">
                <div class="meta-label">Invoice Date</div>
                <div class="meta-value"><?php echo date('d M, Y', $first_invoice->creation_timestamp); ?></div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Academic Period</div>
                <div class="meta-value">
                    <?php echo $param3; ?> | 
                    <?php echo isset($first_invoice->sem) ? 'Sem ' . $param4 : 'Term ' . $param4; ?>
                </div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    <span class="status-badge status-<?php echo strtolower($inv_status); ?>">
                        <?php echo $inv_status; ?>
                    </span>
                </div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Date Printed</div>
                <div class="meta-value"><?php echo date('d M, Y H:i'); ?></div>
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- Party Details -->
            <div class="party-details">
                <div class="party-box">
                    <div class="party-title">Billed To</div>
                    <div class="party-info">
                        <strong><?php echo $student->name; ?></strong>
                        ID: <?php echo $student->student_code; ?><br>
                        <?php echo $class->name . ' ' . $class->name_numeric . ' ' . $section->name; ?><?php if(!empty($student->phone)): ?><br>Phone: <?php echo $student->phone; ?><?php endif; ?>
                    </div>
                </div>
                <div class="party-box">
                    <div class="party-title">Payment To</div>
                    <div class="party-info">
                        <strong><?php echo $system_name; ?></strong>
                        <?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?> | <?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?>
                    </div>
                </div>
            </div>

            <!-- Discount Information -->
            <?php if($total_discount > 0): ?>
            <div class="discount-info">
                <div class="discount-info-title">
                    <i class="entypo-tag"></i> Discount Applied
                </div>
                <?php foreach($discount_details as $disc): ?>
                <div class="discount-item">
                    • <?php echo isset($disc['discount_type']) ? ucwords(str_replace('_', ' ', $disc['discount_type'])) : 'Discount'; ?>: 
                    <?php echo $disc['discount_method'] == 'percentage' ? $disc['discount_value'] . '%' : numfmt_format_currency($fmt, $disc['discount_value'], $currency); ?>
                    = <?php echo numfmt_format_currency($fmt, $disc['discount_amount'], $currency); ?>
                    <br>&nbsp;&nbsp;<strong>Applies To:</strong> <?php echo $disc['applies_to']; ?>
                    <?php if(!empty($disc['reason'])): ?>
                    <br>&nbsp;&nbsp;Reason: <?php echo $disc['reason']; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if($has_pending_discount): ?>
            <div style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 8px; margin: 8px 0; border-radius: 4px;">
                <div style="font-weight: 700; color: #856404; margin-bottom: 6px; font-size: 10px;">
                    <i class="entypo-info-circled"></i> Discount Pending Approval
                </div>
                <div style="font-size: 9px; color: #856404;">
                    A discount of <?php echo numfmt_format_currency($fmt, $pending_discount_amount, $currency); ?> is awaiting approval.
                </div>
            </div>
            <?php endif; ?>

            <!-- Invoice Items -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_amount = 0;
                    $total_before_discount = 0;
                    foreach ($edit_data as $row):
                        $total_amount += $row['amount'];
                        // Get original amount before discount
                        $item_discount = isset($discount_items_map[$row['title']]) ? $discount_items_map[$row['title']]['discount_amount'] : 0;
                        $original_amount = $row['amount'] + $item_discount;
                        $total_before_discount += $original_amount;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $row['title']; ?></strong>
                            <?php if(!empty($row['description'])): ?>
                            <br><small style="color: #6c757d; display: block;"><?php echo $row['description']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $original_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php if($total_discount > 0): ?>
                    <tr class="total-row">
                        <td>Subtotal (Before Discount)</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_before_discount, $currency); ?>
                        </td>
                    </tr>
                    <tr class="discount-row">
                        <td>Discount</td>
                        <td style="text-align: right;">
                            - <?php echo numfmt_format_currency($fmt, $total_discount, $currency); ?>
                        </td>
                    </tr>
                    <tr class="final-total-row">
                        <td>Total Amount Due</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr class="final-total-row">
                        <td>Total Amount Due</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <span style="font-weight: 500; font-size: 14px;">Thank You For Choosing <?php echo ucwords(strtolower($system_name)); ?></span>
            <p style="color: #6c757d; margin-top: 10px; font-size: 11px;">
                This is a computer-generated invoice and does not require a physical signature.
            </p>
        </div>
    </div>

    <script>
        // Auto-trigger print dialog after page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
