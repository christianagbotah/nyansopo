<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 30px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
        .receipt { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #3b82f6; }
        .school-info { flex: 2; text-align: center; }
        .school-name { font-size: 28px; font-weight: bold; color: #1e40af; margin-bottom: 5px; }
        .school-details { font-size: 12px; color: #666; line-height: 1.6; }
        .qr-code { width: 100px; height: 100px; }
        .receipt-title { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 15px; text-align: center; font-size: 20px; font-weight: bold; border-radius: 5px; margin-bottom: 25px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .info-item { display: flex; justify-content: space-between; padding: 8px 0; }
        .info-label { font-weight: 600; color: #475569; }
        .info-value { color: #1e293b; }
        .items-table { width: 100%; border-collapse: collapse; margin: 25px 0; }
        .items-table thead { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; }
        .items-table th { padding: 15px; text-align: left; font-weight: 600; }
        .items-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; }
        .items-table tbody tr:hover { background: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .grand-total { display: flex; justify-content: space-between; font-size: 24px; font-weight: bold; color: #1e40af; padding-top: 15px; border-top: 2px solid #3b82f6; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px dashed #cbd5e1; color: #64748b; font-size: 13px; }
        .thank-you { font-size: 18px; font-weight: 600; color: #1e40af; margin-bottom: 10px; }
        .print-btn { background: #10b981; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; margin-right: 10px; }
        .close-btn { background: #ef4444; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; }
        @media print { body { background: white; padding: 0; } .receipt { box-shadow: none; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" class="print-btn">🖨️ Print</button>
        <button onclick="window.close()" class="close-btn">✖ Close</button>
    </div>

    <div class="receipt">
        <div class="header">
            <div class="school-info">
                <div class="school-name"><?php echo isset($school) ? strtoupper($school) : 'SCHOOL NAME'; ?></div>
                <div class="school-details">
                    <?php if(isset($address) && $address): echo $address; ?><br><?php endif; ?>
                    <?php if(isset($phone) && $phone): ?>Tel: <?php echo $phone; ?><?php endif; ?>
                </div>
            </div>
            <div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Receipt-<?php echo isset($sale->id) ? $sale->id : '0'; ?>" class="qr-code">
            </div>
        </div>

        <div class="receipt-title">
            SALES RECEIPT #<?php echo isset($sale->id) ? str_pad($sale->id, 6, '0', STR_PAD_LEFT) : '000000'; ?>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Date & Time:</span>
                <span class="info-value"><?php echo isset($sale->sale_date) ? date('d M Y, h:i A', strtotime($sale->sale_date)) : date('d M Y, h:i A'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Customer:</span>
                <span class="info-value"><?php echo isset($sale->customer_name) && $sale->customer_name ? $sale->customer_name : 'Walk-in Customer'; ?></span>
            </div>
            <?php if(isset($sale->customer_phone) && $sale->customer_phone): ?>
            <div class="info-item">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo $sale->customer_phone; ?></span>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="info-label">Served By:</span>
                <span class="info-value"><?php echo isset($sale->served_by_name) ? $sale->served_by_name : 'Staff'; ?></span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($items) && is_array($items)): $i = 1; foreach($items as $item): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $item->product_name; ?></td>
                    <td class="text-center"><?php echo $item->quantity; ?></td>
                    <td class="text-right"><?php echo isset($currency) ? $currency : 'GH₵'; ?> <?php echo number_format($item->unit_price, 2); ?></td>
                    <td class="text-right"><?php echo isset($currency) ? $currency : 'GH₵'; ?> <?php echo number_format($item->total_price, 2); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="grand-total">
                <span>TOTAL:</span>
                <span><?php echo isset($currency) ? $currency : 'GH₵'; ?> <?php echo isset($sale->total_amount) ? number_format($sale->total_amount, 2) : '0.00'; ?></span>
            </div>
        </div>

        <div class="footer">
            <div class="thank-you">Thank You For Your Purchase!</div>
            <div>Printed: <?php echo date('d M Y, h:i A'); ?></div>
        </div>
    </div>
</body>
</html>
