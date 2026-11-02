<?php
/**
 * Termly Bill — printable standalone page
 * Data: $invoice_code, $bill_data (from Admin::get_student_termly_bill_data)
 */
$b = $bill_data;
$st = $b['student'];
$currency_row = $this->db->get_where('settings', array('type' => 'system_currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$phone_row = $this->db->get_where('settings', array('type' => 'phone'))->row();
$system_phone = $phone_row ? $phone_row->description : '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Termly Bill — <?php echo htmlspecialchars($invoice_code); ?></title>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #111827; background: #e5e7eb; font-size: 13px; }
        .sheet { max-width: 800px; margin: 24px auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow: hidden; }
        .head { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 24px 30px; display: flex; justify-content: space-between; align-items: center; }
        .head img { max-height: 56px; background: #fff; border-radius: 8px; padding: 4px; }
        .head h1 { font-size: 20px; letter-spacing: .5px; }
        .head .sub { font-size: 11.5px; opacity: .92; margin-top: 3px; }
        .badge { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 10px; padding: 8px 14px; text-align: right; }
        .badge .k { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: .85; }
        .badge .v { font-family: 'Courier New', monospace; font-weight: 800; font-size: 15px; }
        .body { padding: 26px 30px; }
        .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .chip { border: 1px solid #e5e7eb; border-radius: 8px; padding: 9px 11px; background: #f9fafb; }
        .chip .k { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; }
        .chip .v { font-size: 13.5px; font-weight: 700; margin-top: 3px; }
        h2.sec { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #4338ca; margin: 18px 0 8px; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; text-align: left; padding: 9px 11px; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; }
        th.r, td.r { text-align: right; }
        td { padding: 9px 11px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        tr.tot td { font-weight: 800; border-top: 2px solid #111827; border-bottom: none; }
        tr.arrears td { background: #fef2f2; color: #b91c1c; font-weight: 700; }
        tr.grand td { background: #d1fae5; font-size: 15px; font-weight: 800; color: #065f46; border-top: 2px solid #10b981; border-bottom: none; }
        .disc { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 9px 12px; font-size: 12.5px; color: #1e3a8a; margin-bottom: 6px; }
        .foot { border-top: 2px dashed #d1d5db; padding: 18px 30px 24px; display: flex; justify-content: space-between; align-items: flex-end; }
        .foot .note { font-size: 11px; color: #6b7280; line-height: 1.6; max-width: 400px; }
        .sig { text-align: center; }
        .sig .line { border-top: 1.5px solid #9ca3af; margin-top: 36px; padding-top: 5px; font-size: 11px; color: #6b7280; width: 190px; }
        .toolbar { text-align: center; padding: 12px; }
        .toolbar button { background: #111827; color: #fff; border: none; border-radius: 8px; padding: 11px 26px; font-size: 14px; font-weight: 700; cursor: pointer; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div style="display:flex;align-items:center;gap:14px;">
                <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="logo" onerror="this.style.display='none'">
                <div>
                    <h1><?php echo htmlspecialchars($school_name); ?></h1>
                    <div class="sub">Student Termly Bill<?php if ($system_phone): ?> · <?php echo htmlspecialchars($system_phone); ?><?php endif; ?></div>
                </div>
            </div>
            <div class="badge">
                <div class="k">Invoice Code</div>
                <div class="v"><?php echo htmlspecialchars($invoice_code); ?></div>
            </div>
        </div>

        <div class="body">
            <div class="meta">
                <div class="chip"><div class="k">Student</div><div class="v"><?php echo $st ? htmlspecialchars($st->name) : '—'; ?></div></div>
                <div class="chip"><div class="k">Student Code</div><div class="v"><?php echo $st ? htmlspecialchars($st->student_code ?: '—') : '—'; ?></div></div>
                <div class="chip"><div class="k">Class / Section</div><div class="v"><?php echo htmlspecialchars(($b['class']->name ?? '—') . ' ' . ($b['class']->name_numeric ?? '')); ?> / <?php echo htmlspecialchars($b['section']->name ?? '—'); ?></div></div>
                <div class="chip"><div class="k"><?php echo $b['term_label']; ?> / Year</div><div class="v"><?php echo htmlspecialchars($b['term'] . ' / ' . $b['year']); ?></div></div>
            </div>

            <h2 class="sec">Current <?php echo $b['term_label']; ?> Bill Items</h2>
            <table>
                <thead><tr><th>Item</th><th>Description</th><th class="r">Amount</th><th class="r">Paid</th><th class="r">Due</th></tr></thead>
                <tbody>
                    <?php foreach ($b['current_items'] as $it): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($it['title']); ?></td>
                        <td style="color:#6b7280;font-size:12px;"><?php echo htmlspecialchars($it['description'] ?: '—'); ?></td>
                        <td class="r"><?php echo number_format((float)$it['amount'], 2); ?></td>
                        <td class="r" style="color:#059669;"><?php echo number_format((float)$it['amount_paid'], 2); ?></td>
                        <td class="r" style="font-weight:700;color:<?php echo $it['due'] > 0 ? '#b91c1c' : '#059669'; ?>;"><?php echo number_format((float)$it['due'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="tot">
                        <td colspan="2">Current <?php echo $b['term_label']; ?> Total</td>
                        <td class="r"><?php echo $currency; ?> <?php echo number_format((float)$b['current_total'], 2); ?></td>
                        <td class="r"><?php echo number_format((float)$b['current_paid'], 2); ?></td>
                        <td class="r"><?php echo number_format((float)$b['current_due'], 2); ?></td>
                    </tr>
                    <?php if ($b['arrears'] > 0): ?>
                    <tr class="arrears">
                        <td colspan="4"><i class="fa fa-exclamation-triangle"></i> Arrears carried from previous terms</td>
                        <td class="r"><?php echo $currency; ?> <?php echo number_format((float)$b['arrears'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="grand">
                        <td colspan="4">GRAND TOTAL PAYABLE</td>
                        <td class="r"><?php echo $currency; ?> <?php echo number_format((float)$b['grand_total'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if (!empty($b['discount_details'])): ?>
            <h2 class="sec">Approved Discounts</h2>
            <?php foreach ($b['discount_details'] as $d): ?>
            <div class="disc"><strong><?php echo $currency; ?> <?php echo number_format((float)$d['discount_amount'], 2); ?></strong> off — applies to: <?php echo htmlspecialchars($d['applies_to']); ?></div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($b['creation_timestamp']): ?>
            <div style="margin-top:14px;font-size:11.5px;color:#6b7280;">Bill generated: <?php echo date('d M Y', $b['creation_timestamp']); ?></div>
            <?php endif; ?>
        </div>

        <div class="foot">
            <div class="note">
                This bill includes arrears from previous terms where applicable. Please settle all outstanding
                amounts at the school accounts office. Receipts are issued for every payment.
            </div>
            <div class="sig"><div class="line">Accounts Officer Signature</div></div>
        </div>
    </div>

    <div class="toolbar">
        <button onclick="window.print()"><i class="fa fa-print"></i> Print Bill</button>
    </div>
</body>
</html>
