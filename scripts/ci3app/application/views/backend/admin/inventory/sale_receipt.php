<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Receipt - <?php echo $receipt_code; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }

        /* Screen view container */
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .school-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .school-phone {
            font-size: 12px;
            color: #666;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
        }

        .receipt-code {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .receipt-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .meta-item {
            font-size: 13px;
        }

        .meta-label {
            color: #666;
            display: inline-block;
            min-width: 100px;
        }

        .meta-value {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #333;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }

        .items-table tr:last-child td {
            border-bottom: 2px solid #333;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .customers-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
        }

        .customers-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .customer-name {
            font-size: 12px;
            font-weight: bold;
        }

        .customer-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
            font-size: 11px;
        }

        .customer-item {
            padding: 5px;
            background: white;
            border: 1px solid #ddd;
        }

        .totals-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #333;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.grand {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 10px;
        }

        .receipt-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #333;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .thank-you {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .print-button:hover {
            background: #5568d3;
        }

        .print-buttons-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn-print {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .btn-print-a5 {
            background: #667eea;
            color: white;
        }

        .btn-print-a5:hover {
            background: #5568d3;
        }

        .btn-print-thermal {
            background: #10b981;
            color: white;
        }

        .btn-print-thermal:hover {
            background: #059669;
        }

        /* Default: A5 Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .receipt-container {
                box-shadow: none;
                padding: 20px;
                max-width: 148mm; /* A5 width */
                margin: 0 auto;
            }

            .print-buttons-container {
                display: none;
            }

            .receipt-header {
                padding-bottom: 15px;
                margin-bottom: 15px;
            }

            .school-name {
                font-size: 20px;
            }

            .school-address,
            .school-phone {
                font-size: 11px;
            }

            .receipt-title {
                font-size: 16px;
            }

            .receipt-code {
                font-size: 12px;
            }

            .receipt-meta {
                display: flex;
                margin-bottom: 15px;
                padding-bottom: 10px;
            }

            .meta-item {
                font-size: 11px;
            }

            .items-table th {
                padding: 8px;
                font-size: 11px;
            }

            .items-table td {
                padding: 8px;
                font-size: 11px;
            }

            .customers-section {
                margin: 15px 0;
                padding: 10px;
            }

            .customers-title {
                font-size: 11px;
            }

            .customer-name {
                font-size: 11px;
            }

            .customer-list {
                display: grid;
                font-size: 11px;
            }

            .customer-item {
                padding: 4px;
            }

            .totals-section {
                margin-top: 15px;
                padding-top: 10px;
            }

            .total-row {
                padding: 6px 0;
                font-size: 12px;
            }

            .total-row.grand {
                font-size: 16px;
                padding-top: 10px;
            }

            .receipt-footer {
                margin-top: 20px;
                padding-top: 15px;
                font-size: 10px;
            }

            .thank-you {
                font-size: 13px;
            }

            .page-break {
                page-break-after: always;
            }
        }

        /* THERMAL PRINTER OPTIMIZATION - 80mm paper width */
        @media print {
            body.thermal-print {
                background: white;
                padding: 0;
                margin: 0;
            }

            body.thermal-print .receipt-container {
                box-shadow: none;
                padding: 8px;
                max-width: 80mm; /* Standard thermal paper width */
                margin: 0;
                font-size: 11px;
            }

            body.thermal-print .receipt-header {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }

            body.thermal-print .school-name {
                font-size: 12px;
                margin-bottom: 3px;
            }

            body.thermal-print .school-address,
            body.thermal-print .school-phone {
                font-size: 8px;
                margin-bottom: 2px;
            }

            body.thermal-print .receipt-title {
                font-size: 11px;
                margin-top: 8px;
            }

            body.thermal-print .receipt-code {
                font-size: 9px;
                margin-top: 3px;
            }

            body.thermal-print .receipt-meta {
                display: block;
                margin-bottom: 10px;
                padding-bottom: 8px;
            }

            body.thermal-print .meta-item {
                font-size: 9px;
                margin-bottom: 3px;
            }

            body.thermal-print .meta-label {
                min-width: auto;
                font-size: 9px;
            }

            body.thermal-print .items-table th {
                padding: 4px 2px;
                font-size: 9px;
            }

            body.thermal-print .items-table td {
                padding: 4px 2px;
                font-size: 10px;
            }

            body.thermal-print .customers-section {
                margin: 10px 0;
                padding: 6px;
                background: none;
                border-left: 2px solid #333;
            }

            body.thermal-print .customers-title {
                font-size: 8px;
                margin-bottom: 4px;
                text-transform: uppercase;
            }

            body.thermal-print .customer-name {
                font-size: 9px;
                font-weight: bold;
            }

            body.thermal-print .customer-list {
                display: block;
                font-size: 8px;
            }

            body.thermal-print .customer-item {
                padding: 2px;
                margin-bottom: 2px;
                background: none;
                border: none;
                border-bottom: 1px dotted #999;
            }

            body.thermal-print .totals-section {
                margin-top: 10px;
                padding-top: 8px;
            }

            body.thermal-print .total-row {
                padding: 4px 0;
                font-size: 10px;
            }

            body.thermal-print .total-row.grand {
                font-size: 14px;
                padding-top: 8px;
                margin-top: 5px;
            }

            body.thermal-print .receipt-footer {
                margin-top: 15px;
                padding-top: 10px;
                font-size: 8px;
            }

            body.thermal-print .thank-you {
                font-size: 12px;
                margin-bottom: 5px;
            }

            /* Hide SKU column on thermal print to save space */
            body.thermal-print .items-table th:nth-child(2),
            body.thermal-print .items-table td:nth-child(2) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-buttons-container">
        <button class="btn-print btn-print-a5" onclick="printA5()">
            <i class="fa fa-print"></i> Print A5
        </button>
        <button class="btn-print btn-print-thermal" onclick="printThermal()">
            <i class="fa fa-receipt"></i> Print Thermal
        </button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="school-name"><?php echo $school_name; ?></div>
            <div class="school-address"><?php echo $school_address; ?></div>
            <div class="school-phone">Tel: <?php echo $school_phone; ?></div>
            <div class="receipt-title">Sales Receipt</div>
            <div class="receipt-code">Receipt No: <?php echo $receipt_code; ?></div>
        </div>

        <!-- Meta Information -->
        <div class="receipt-meta">
            <div>
                <div class="meta-item">
                    <span class="meta-label">Sale ID:</span>
                    <span class="meta-value"><?php echo '#' . $sales[0]->id; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Date:</span>
                    <span class="meta-value"><?php echo date('d M Y, g:i A', strtotime($sales[0]->sale_date)); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Served By:</span>
                    <span class="meta-value"><?php echo $served_by; ?></span>
                </div>
            </div>
            <div>
                <div class="meta-item">
                    <span class="meta-label">No. of Customers:</span>
                    <span class="meta-value"><?php echo count($sales); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Transaction Type:</span>
                    <span class="meta-value"><?php echo count($sales) > 1 ? 'Batch Sale' : 'Single Sale'; ?></span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach($items as $item): 
                    $item_total = $item->unit_price * $item->quantity;
                    $subtotal += $item_total;
                ?>
                <tr>
                    <td><?php echo $item->product_name; ?></td>
                    <td><?php echo $item->sku; ?></td>
                    <td class="text-center"><?php echo $item->quantity; ?></td>
                    <td class="text-right"><?php echo $currency; ?> <?php echo number_format($item->unit_price, 2); ?></td>
                    <td class="text-right"><?php echo $currency; ?> <?php echo number_format($item_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Customers Section -->
        <?php if(count($sales) > 1): ?>
        <div class="customers-section">
            <div class="customers-title">Customers in this Transaction (<?php echo count($sales); ?>):</div>
            <div class="customer-list">
                <?php foreach($sales as $sale): ?>
                <div class="customer-item">
                    <span class="customer-name">
                    <?php 
                    // Display customer name with class and section info for students
                    $display_name = $sale->customer_name ?: 'Walk-in Customer';
                    
                    // Add class and section info if student has enrollment
                    if($sale->student_id && isset($sale->class_name) && $sale->class_name) {
                        $class_text = trim($sale->class_name . ' ' . (isset($sale->name_numeric) ? $sale->name_numeric : ''));
                        
                        // Add section if available
                        if(isset($sale->section_name) && $sale->section_name) {
                            $class_text .= ' (' . $sale->section_name . ')';
                        }
                        
                        $display_name .= ' - ' . $class_text;
                    }
                    
                    echo $display_name;
                    ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="customers-section">
            <div class="customers-title">Customer:</div>
            <div class="customer-name">
                <?php 
                // Display customer name with class and section info for students
                $sale = $sales[0];
                $display_name = $sale->customer_name ?: 'Walk-in Customer';
                
                // Add class and section info if student has enrollment
                if($sale->student_id && $sale->class_name) {
                    $class_text = trim($sale->class_name . ' ' . $sale->name_numeric);
                    
                    // Add section if available
                    if($sale->section_name) {
                        $class_text .= ' (' . $sale->section_name . ')';
                    }
                    
                    $display_name .= ' - ' . $class_text;
                }
                
                echo $display_name;
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span>Subtotal (per customer):</span>
                <span><?php echo $currency; ?> <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Number of Customers:</span>
                <span>× <?php echo count($sales); ?></span>
            </div>
            <div class="total-row grand">
                <span>GRAND TOTAL:</span>
                <span><?php echo $currency; ?> <?php echo number_format($subtotal * count($sales), 2); ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="thank-you">Thank You for Your Purchase!</div>
            <div>This is a computer-generated receipt</div>
            <div style="margin-top: 10px;">For inquiries, please contact us at <?php echo $school_phone; ?></div>
            <div style="margin-top: 20px; font-size: 10px;">
                Printed on: <?php echo date('d M Y, g:i A'); ?>
            </div>
        </div>
    </div>

    <script>
        // Print A5 format (default)
        function printA5() {
            document.body.classList.remove('thermal-print');
            window.print();
        }
        
        // Print Thermal format (80mm)
        function printThermal() {
            document.body.classList.add('thermal-print');
            window.print();
            // Remove class after print dialog closes
            setTimeout(function() {
                document.body.classList.remove('thermal-print');
            }, 1000);
        }
    </script>
</body>
</html>
