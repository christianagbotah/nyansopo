<?php
$school_name = get_settings('system_name');
$school_address = get_settings('address');
$school_phone = get_settings('phone');
$school_email = get_settings('system_email');
$currency = get_settings('currency');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - <?php echo $receipt['receipt_number']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }
        .receipt-container { max-width: 800px; margin: 20px auto; padding: 40px; border: 2px solid #007bff; }
        .header { text-align: center; border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #007bff; font-size: 28px; margin-bottom: 5px; }
        .header p { color: #666; font-size: 12px; }
        .receipt-title { background: #007bff; color: white; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 30px; }
        .info-section { display: table; width: 100%; margin-bottom: 30px; }
        .info-left, .info-right { display: table-cell; width: 50%; vertical-align: top; }
        .info-right { text-align: right; }
        .info-label { font-weight: bold; color: #007bff; display: inline-block; width: 120px; }
        .info-value { color: #333; }
        .amount-section { background: #f8f9fa; border: 2px dashed #007bff; padding: 20px; margin: 30px 0; text-align: center; }
        .amount-label { font-size: 16px; color: #666; margin-bottom: 10px; }
        .amount-value { font-size: 36px; font-weight: bold; color: #007bff; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .details-table th { background: #007bff; color: white; padding: 12px; text-align: left; }
        .details-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 2px solid #007bff; }
        .signature-section { display: table; width: 100%; margin-top: 60px; }
        .signature-box { display: table-cell; width: 50%; text-align: center; }
        .signature-line { border-top: 2px solid #333; display: inline-block; width: 200px; margin-top: 50px; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 120px; color: rgba(0,123,255,0.05); z-index: -1; font-weight: bold; }
        @media print {
            .receipt-container { border: none; padding: 20px; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="watermark">PAID</div>
    
    <div class="receipt-container">
        <div class="header">
            <h1><?php echo strtoupper($school_name); ?></h1>
            <p><?php echo $school_address; ?></p>
            <p>Tel: <?php echo $school_phone; ?> | Email: <?php echo $school_email; ?></p>
        </div>

        <div class="receipt-title">OFFICIAL PAYMENT RECEIPT</div>

        <div class="info-section">
            <div class="info-left">
                <p><span class="info-label">Receipt No:</span> <strong><?php echo $receipt['receipt_number']; ?></strong></p>
                <p><span class="info-label">Date:</span> <?php echo date('d M Y, h:i A', strtotime($receipt['received_date'])); ?></p>
                <p><span class="info-label">Payment Method:</span> <?php echo strtoupper(str_replace('_', ' ', $receipt['payment_method'])); ?></p>
                <?php if ($receipt['payment_reference']): ?>
                <p><span class="info-label">Reference:</span> <?php echo $receipt['payment_reference']; ?></p>
                <?php endif; ?>
            </div>
            <div class="info-right">
                <p><span class="info-label">Student Name:</span> <?php echo $receipt['student_name']; ?></p>
                <p><span class="info-label">Student ID:</span> <?php echo $receipt['student_code']; ?></p>
                <p><span class="info-label">Class:</span> <?php echo $receipt['class_name']; ?></p>
                <?php if ($receipt['invoice_code']): ?>
                <p><span class="info-label">Invoice:</span> <?php echo $receipt['invoice_code']; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="amount-section">
            <div class="amount-label">AMOUNT RECEIVED</div>
            <div class="amount-value"><?php echo $currency . ' ' . number_format($receipt['amount'], 2); ?></div>
            <p style="margin-top: 10px; font-style: italic; color: #666;">
                (<?php echo ucwords($this->numberToWords($receipt['amount'])); ?> Only)
            </p>
        </div>

        <?php if ($receipt['notes']): ?>
        <table class="details-table">
            <tr>
                <th>Notes / Description</th>
            </tr>
            <tr>
                <td><?php echo nl2br(htmlspecialchars($receipt['notes'])); ?></td>
            </tr>
        </table>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Received By:</strong> <?php echo $receipt['received_by_name']; ?></p>
            <p style="font-size: 11px; color: #666; margin-top: 20px;">
                This is a computer-generated receipt and is valid without signature. 
                For any queries, please contact the accounts department.
            </p>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Received By</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Authorized Signature</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto-print when loaded directly
            if (window.location.href.indexOf('print') > -1) {
                setTimeout(function() { window.print(); }, 500);
            }
        };
    </script>
</body>
</html>
