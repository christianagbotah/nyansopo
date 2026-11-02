<?php
/**
 * Bulk Receipts Print — standalone printable page
 * Data: $payment_ids (array of payment ids)
 * Renders one thermal-style receipt per payment, each on its own page.
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';

$payments = array();
if (!empty($payment_ids) && is_array($payment_ids)) {
    $payments = $this->db->select('p.*, s.name AS student_name, s.student_code')
        ->from('payment p')
        ->join('student s', 's.student_id = p.student_id', 'left')
        ->where_in('p.payment_id', $payment_ids)
        ->order_by('p.timestamp', 'DESC')
        ->get()
        ->result_array();
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Receipts Print</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 13px; color: #111827; background: #e5e7eb; }
        .rcpt {
            max-width: 680px; margin: 20px auto; background: #fff; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12); overflow: hidden; page-break-after: always;
        }
        .rcpt-head { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        .rcpt-head h2 { font-size: 16px; }
        .rcpt-head .code { font-family: 'Courier New', monospace; font-weight: 800; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 8px; padding: 6px 12px; font-size: 13px; }
        .rcpt-body { padding: 20px 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        td { padding: 6px 8px; border-bottom: 1px dashed #e5e7eb; }
        .amount { text-align: right; font-size: 20px; font-weight: 800; color: #059669; }
        .rcpt-foot { border-top: 2px dashed #d1d5db; padding: 12px 24px 18px; display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; }
        .sig { border-top: 1.5px solid #9ca3af; margin-top: 24px; padding-top: 4px; width: 150px; text-align: center; }
        .toolbar { text-align: center; padding: 14px; }
        .toolbar button { background: #111827; color: #fff; border: none; border-radius: 8px; padding: 12px 28px; font-size: 14px; font-weight: 700; cursor: pointer; }
        .empty { text-align: center; padding: 60px; background: #fff; border-radius: 12px; max-width: 500px; margin: 40px auto; color: #6b7280; font-weight: 700; }
        @media print { body { background: #fff; } .rcpt { box-shadow: none; margin: 0 auto; border-radius: 0; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Print <?php echo count($payments); ?> Receipt(s)</button></div>

    <?php if (empty($payments)): ?>
    <div class="empty">No payments were selected for printing.</div>
    <?php else: foreach ($payments as $p): ?>
    <div class="rcpt">
        <div class="rcpt-head">
            <h2><?php echo htmlspecialchars($school_name); ?></h2>
            <div class="code">#<?php echo htmlspecialchars($p['receipt_code'] ?: $p['payment_id']); ?></div>
        </div>
        <div class="rcpt-body">
            <table>
                <tr><td><strong>Date:</strong> <?php echo !empty($p['timestamp']) ? date('d M, Y', $p['timestamp']) : '—'; ?></td>
                    <td style="text-align:right;"><strong>Invoice:</strong> <?php echo htmlspecialchars($p['invoice_code'] ?: '—'); ?></td></tr>
                <tr><td><strong>Student:</strong> <?php echo htmlspecialchars($p['student_name'] ?: '—'); ?></td>
                    <td style="text-align:right;"><strong>ID:</strong> <?php echo htmlspecialchars($p['student_code'] ?: '—'); ?></td></tr>
                <tr><td><strong>Method:</strong> <?php echo htmlspecialchars(ucfirst($p['payment_method'] ?? '')); ?></td>
                    <td style="text-align:right;"><strong>Title:</strong> <?php echo htmlspecialchars($p['title'] ?: 'Fee Payment'); ?></td></tr>
            </table>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:14px 18px;">
                <span style="font-weight:700; color:#065f46;">AMOUNT PAID</span>
                <span class="amount"><?php echo $currency; ?> <?php echo number_format((float)$p['amount'], 2); ?></span>
            </div>
        </div>
        <div class="rcpt-foot">
            <div>Thank you for your payment</div>
            <div class="sig">Authorised Signature</div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</body>
</html>
