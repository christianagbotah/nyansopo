<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Invoice Print - <?php echo get_settings('system_name'); ?></title>
    <?php
    $currency = get_settings('currency');
    $school_name = get_settings('system_name');
    $school_address = get_settings('address');
    $school_phone = get_settings('phone');
    $school_email = get_settings('system_email');
    $school_location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
    $theme_color = $this->db->get_where('settings', array('type' => 'theme_color'))->row()->description;
    
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
    ?>
    <style>
        @media print {
            @page {
                margin: 8mm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .page-break {
                page-break-after: always;
            }
            .no-print {
                display: none !important;
            }
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
                overflow: visible !important;
                page-break-inside: avoid;
                display: block;
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
            .invoice-header {
                background: <?php echo $theme_color; ?> !important;
                background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
            }
            .invoice-header::after {
                display: none !important;
            }
            .invoice-table thead th {
                background: <?php echo $theme_color; ?> !important;
                background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
            }
            .final-total-row {
                background: <?php echo $theme_color; ?> !important;
                background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
            }
            .discount-info {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding: 0;
            margin: 0;
        }

        .invoice-container {
            max-width: 580px;
            margin: 0 auto 10px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 0;
            overflow: hidden;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .invoice-header {
            background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
            color: white;
            padding: 8px;
            position: relative;
        }

        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: white;
            border-radius: 10px 10px 0 0;
        }

        .school-logo {
            max-height: 40px;
            background: white;
            padding: 4px;
            border-radius: 50%;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: white;
        }

        .invoice-meta {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            background: #f8f9fa;
            border-bottom: 2px solid <?php echo $theme_color; ?>;
        }

        .meta-box {
            flex: 1;
        }

        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 11px;
            font-weight: 600;
            color: #212529;
        }

        .invoice-body {
            padding: 8px;
            flex: 1;
            overflow: auto;
        }

        .party-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .party-box {
            flex: 1;
            padding: 5px 8px;
            background: #f8f9fa;
            border-radius: 4px;
            margin: 0 4px;
        }

        .party-box:first-child {
            margin-left: 0;
        }

        .party-box:last-child {
            margin-right: 0;
        }

        .party-title {
            font-size: 11px;
            text-transform: uppercase;
            color: <?php echo $theme_color; ?>;
            font-weight: 700;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }

        .party-info {
            font-size: 13px;
            line-height: 1.4;
            color: #495057;
        }

        .party-info strong {
            color: #212529;
            display: block;
            font-size: 14px;
            margin-bottom: 1px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            margin-bottom: 6px;
        }

        .invoice-table thead th {
            background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 600;
        }

        .invoice-table thead th:first-child {
            border-radius: 6px 0 0 0;
        }

        .invoice-table thead th:last-child {
            border-radius: 0 6px 0 0;
            text-align: right;
        }

        .invoice-table tbody td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        .invoice-table tbody tr:hover {
            background: #f8f9fa;
        }

        .invoice-table tfoot td {
            padding: 8px;
            font-weight: 600;
            font-size: 15px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            background: #f8f9fa;
            border-top: 2px solid <?php echo $theme_color; ?>;
        }

        .discount-row {
            color: #28a745;
        }

        .final-total-row {
            background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
            color: white;
            font-size: 16px;
        }

        .invoice-footer {
            padding: 10px 15px;
            background: #f8f9fa;
            border-top: 2px solid <?php echo $theme_color; ?>;
            text-align: center;
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .status-partial {
            background: #fff3cd;
            color: #856404;
        }

        .discount-info {
            background: #d4edda;
            border-left: 3px solid #28a745;
            padding: 8px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .discount-info-title {
            font-weight: 700;
            color: #155724;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .discount-item {
            font-size: 13px;
            color: #155724;
            margin: 3px 0;
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin: 5px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .currency {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa fa-print"></i> Print All Invoices
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fa fa-times"></i> Close
        </button>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
            <strong><?php echo $total_count; ?></strong> invoice(s) ready to print
        </div>
    </div>

    <?php
    $currency = get_settings('currency');
    $school_name = get_settings('system_name');
    $school_address = get_settings('address');
    $school_phone = get_settings('phone');
    $school_email = get_settings('system_email');
    $school_location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    
    $invoice_count = 0;
    $grand_total_amount = 0;
    $grand_total_paid = 0;
    $grand_total_due = 0;

    foreach($invoices as $invoice):
        $invoice_code = $invoice['invoice_code'];
        $student_id = $invoice['student_id'];
        
        // Get student details
        $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
        
        // Get class details
        $this->db->order_by('enroll_id', 'desc');
        $this->db->limit(1);
        $class_id = $this->db->get_where('enroll', array('student_id' => $student_id))->row()->class_id;
        $class = $this->db->get_where('class', array('class_id' => $class_id))->row();
        
        // Get invoice items
        $this->db->where('invoice_code', $invoice_code);
        $this->db->where('student_id', $student_id);
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $invoice_items = $this->db->get('invoice')->result_array();
        
        // Get first invoice for metadata
        $first_invoice = $invoice_items[0];
        
        // Check for discounts
        $discount_query = $this->db->get_where('invoice_discounts', array(
            'invoice_code' => $invoice_code,
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
        
        // Calculate totals
        $total_amount = 0;
        $total_paid = 0;
        $total_due = 0;
        $total_before_discount = 0;
        
        foreach($invoice_items as $item) {
            $total_amount += $item['amount'];
            $total_paid += $item['amount_paid'];
            $total_due += $item['due'];
            $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
            $total_before_discount += ($item['amount'] + $item_discount);
        }
        
        $grand_total_amount += $total_amount;
        $grand_total_paid += $total_paid;
        $grand_total_due += $total_due;
        
        // Determine status
        if($total_due <= 0) {
            $status = 'paid';
            $status_text = 'Paid';
        } elseif($total_paid > 0) {
            $status = 'partial';
            $status_text = 'Partial';
        } else {
            $status = 'unpaid';
            $status_text = 'Unpaid';
        }
        
        $invoice_count++;
    ?>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <?php if(file_exists('uploads/school_logo.png')): ?>
                        <img src="<?php echo base_url(); ?>uploads/school_logo.png" alt="School Logo" class="school-logo">
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <h1 class="invoice-title">Invoice</h1>
                    <div style="font-size: 18px; margin-top: 5px; opacity: 0.9; color: white;">
                        #<?php echo $invoice_code; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Information -->
        <div class="invoice-meta">
            <div class="meta-box">
                <div class="meta-label">Invoice Date</div>
                <div class="meta-value"><?php echo date('d M, Y', $first_invoice['creation_timestamp']); ?></div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Academic Period</div>
                <div class="meta-value">
                    <?php echo $year; ?> | Term <?php echo $term; ?>
                </div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    <span class="status-badge status-<?php echo $status; ?>"><?php echo $status_text; ?></span>
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
                        <?php echo $class->name . ' ' . $class->name_numeric; ?><?php if(!empty($student->phone)): ?><br>Phone: <?php echo $student->phone; ?><?php endif; ?>
                    </div>
                </div>
                <div class="party-box">
                    <div class="party-title">Payment To</div>
                    <div class="party-info">
                        <strong><?php echo $school_name; ?></strong>
                        <?php echo $school_location; ?> | <?php echo $school_phone; ?>
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

            <!-- Invoice Items Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invoice_items as $item): 
                        $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
                        $original_amount = $item['amount'] + $item_discount;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $item['title']; ?></strong>
                            <?php if(!empty($item['description'])): ?>
                            <br><small style="color: #6c757d; display: block;"><?php echo $item['description']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php echo numfmt_format_currency($fmt, $original_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php if($total_discount > 0): ?>
                    <tr class="total-row">
                        <td>Subtotal (Before Discount)</td>
                        <td class="text-right">
                            <?php echo numfmt_format_currency($fmt, $total_before_discount, $currency); ?>
                        </td>
                    </tr>
                    <tr class="discount-row">
                        <td>Discount</td>
                        <td class="text-right">
                            - <?php echo numfmt_format_currency($fmt, $total_discount, $currency); ?>
                        </td>
                    </tr>
                    <tr class="final-total-row">
                        <td>Total Amount Due</td>
                        <td class="text-right">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr class="final-total-row">
                        <td>Total Amount Due</td>
                        <td class="text-right">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <span style="font-weight: 500; font-size: 14px;">Thank You For Choosing <?php echo ucwords(strtolower($school_name)); ?></span>
            <p style="color: #6c757d; margin-top: 10px; font-size: 11px;">
                This is a computer-generated invoice and does not require a physical signature.
            </p>
        </div>
    </div>

    <?php if($invoice_count < count($invoices)): ?>
        <div class="page-break"></div>
    <?php endif; ?>

    <?php endforeach; ?>

    <!-- Summary Page -->
    <div class="invoice-container no-print" style="margin-top: 30px; border: 2px solid #667eea;">
        <div class="invoice-header">
            <div style="text-align: center;">
                <h1 class="invoice-title">Bulk Print Summary</h1>
            </div>
        </div>
        <div style="padding: 20px;">
            <table style="width: 100%; font-size: 13px;">
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td style="padding: 10px 0;">Total Invoices Printed:</td>
                    <td style="padding: 10px 0; text-align: right;"><strong><?php echo $invoice_count; ?></strong></td>
                </tr>
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td style="padding: 10px 0;">Academic Period:</td>
                    <td style="padding: 10px 0; text-align: right;"><strong>Term <?php echo $term; ?>, <?php echo $year; ?></strong></td>
                </tr>
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td style="padding: 10px 0;">Filter Applied:</td>
                    <td style="padding: 10px 0; text-align: right;"><strong><?php echo ucfirst($filter); ?></strong></td>
                </tr>
                <tr style="border-bottom: 2px solid #667eea;">
                    <td style="padding: 10px 0; font-size: 15px; font-weight: bold; color: #667eea;">Grand Total Amount:</td>
                    <td style="padding: 10px 0; text-align: right; font-size: 15px; font-weight: bold; color: #667eea;"><?php echo numfmt_format_currency($fmt, $grand_total_amount, $currency); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td style="padding: 10px 0; color: #28a745;">Total Paid:</td>
                    <td style="padding: 10px 0; text-align: right; color: #28a745; font-weight: bold;"><?php echo numfmt_format_currency($fmt, $grand_total_paid, $currency); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; font-size: 15px; font-weight: bold; color: #dc3545;">Total Outstanding:</td>
                    <td style="padding: 10px 0; text-align: right; font-size: 15px; font-weight: bold; color: #dc3545;"><?php echo numfmt_format_currency($fmt, $grand_total_due, $currency); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); }
        
        // Close window after printing
        window.onafterprint = function() {
            // Optionally close window after printing
            // window.close();
        }
    </script>
</body>
</html>
