<!DOCTYPE html>
<html>
<head>
    <title>Fee Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; padding: 20px; }
        .receipt { width: 80mm; margin: 0 auto; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 8px; }
        .header img { display: block; margin: 0 auto 5px auto; max-height: 40px; }
        .header h3 { font-size: 16px; margin: 3px 0; }
        .header p { font-size: 10px; margin: 2px 0; }
        .info { margin: 8px 0; font-size: 11px; }
        .info-row { display: flex; justify-content: space-between; margin: 3px 0; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .items { margin: 8px 0; }
        .item-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 11px; }
        .total-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }
        .footer { text-align: center; font-size: 9px; margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; background: #f9fafb; padding: 8px; border-radius: 6px; }
        .button-container { text-align: center; margin-top: 30px; }
        .button-container button { padding: 10px 30px; cursor: pointer; font-size: 14px; margin: 0 5px; }
        @media print {
            body { padding: 5mm; }
            .no-print { display: none; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="School Logo">
            <h3><?php echo strtoupper($school_name); ?></h3>
            <p>FEE PAYMENT RECEIPT</p>
        </div>
        
        <div class="info">
            <div class="info-row"><span>Date:</span><span><?php echo $date; ?></span></div>
            <div class="info-row"><span>Student:</span><span><?php echo $student->name; ?></span></div>
            <div class="info-row"><span>ID:</span><span><?php echo $student->student_code; ?></span></div>
            <div class="info-row"><span>Class:</span><span><?php echo $class->name . ' ' . $class->name_numeric . (isset($section) && $section ? ' - ' . $section->name : ''); ?></span></div>
            <div class="info-row"><span>Receipt#:</span><span><?php echo $receipt_code; ?></span></div>
            <?php if(isset($payment_method)): ?>
            <div class="info-row"><span>Method:</span><span><?php echo $payment_method; ?></span></div>
            <?php endif; ?>
        </div>
        
        <div class="divider"></div>
        
        <div class="items">
            <?php foreach($payments as $p): ?>
            <div class="item-row">
                <span><?php echo $p['label']; ?></span>
                <span><?php echo $currency . ' ' . number_format($p['amount'], 2); ?></span>
            </div>
            <?php if(isset($p['owing']) && $p['owing'] > 0): ?>
            <div class="item-row" style="font-size: 10px; color: #666;">
                <span>&nbsp;&nbsp;Balance Due (<?php echo $p['label']; ?>):</span>
                <span><?php echo $currency . ' ' . number_format($p['owing'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="total-row">
            <span>TOTAL PAID:</span>
            <span><?php echo $currency . ' ' . number_format($total, 2); ?></span>
        </div>
        
        <div class="footer">
            <p>Thank you for your payment</p>
            <p style="color: #6b7280;">Printed: <?php echo date('d M Y H:i'); ?></p>
        </div>
    </div>
    
    <div class="no-print button-container">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
