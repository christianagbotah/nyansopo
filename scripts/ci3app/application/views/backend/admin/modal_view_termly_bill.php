<?php
/**
 * Termly Bill — modal fragment
 * Data: $invoice_code, $bill_data (from Admin::get_student_termly_bill_data)
 */
$b = $bill_data;
$st = $b['student'];
$currency_row = $this->db->get_where('settings', array('type' => 'system_currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : '';
?>
<style>
.tb-wrap { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }
.tb-student {
    display: flex; gap: 14px; align-items: center;
    background: linear-gradient(135deg, rgba(102,126,234,.08), rgba(118,75,162,.08));
    border: 1px solid rgba(102,126,234,.22); border-radius: 12px; padding: 14px 16px; margin-bottom: 14px;
}
.tb-student .avatar {
    width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
    display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px;
}
.tb-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
@media (max-width: 640px) { .tb-meta { grid-template-columns: 1fr 1fr; } }
.tb-chip { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 10px; }
.tb-chip .k { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; }
.tb-chip .v { font-size: 13px; font-weight: 700; color: #111827; margin-top: 2px; }
.tb-sec { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; color: #4338ca; margin: 14px 0 7px; }
table.tb-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
table.tb-tbl th { background: #111827; color: #fff; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; }
table.tb-tbl th.r, table.tb-tbl td.r { text-align: right; }
table.tb-tbl td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
table.tb-tbl tr.tot td { font-weight: 800; border-top: 2px solid #111827; border-bottom: none; }
table.tb-tbl tr.arrears td { background: #fef2f2; color: #b91c1c; font-weight: 700; }
table.tb-tbl tr.grand td { background: linear-gradient(135deg, #ecfdf5, #d1fae5); font-size: 14.5px; font-weight: 800; color: #065f46; border-top: 2px solid #10b981; border-bottom: none; }
.tb-disc { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px 14px; font-size: 12.5px; color: #1e3a8a; margin-top: 8px; }
.tb-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 2px solid #e5e7eb; }
.tb-btn { border: none; border-radius: 9px; padding: 10px 20px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
.tb-btn-print { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.tb-btn-print:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(102,126,234,.35); }
.tb-btn-close { background: #fff; color: #374151; border: 2px solid #d1d5db; }
</style>

<div class="tb-wrap">
    <div class="tb-student">
        <div class="avatar"><?php echo $st ? strtoupper(substr($st->name, 0, 1)) : '?'; ?></div>
        <div>
            <div style="font-weight: 800; font-size: 15.5px;"><?php echo $st ? htmlspecialchars($st->name) : '—'; ?></div>
            <div style="font-size: 12px; color: #6b7280;"><?php echo $st ? htmlspecialchars($st->student_code ?: '') : ''; ?> · Invoice <?php echo htmlspecialchars($invoice_code); ?></div>
        </div>
    </div>

    <div class="tb-meta">
        <div class="tb-chip"><div class="k">Class / Section</div><div class="v"><?php echo htmlspecialchars(($b['class']->name ?? '—') . ' ' . ($b['class']->name_numeric ?? '') . ' · ' . ($b['section']->name ?? '—')); ?></div></div>
        <div class="tb-chip"><div class="k"><?php echo $b['term_label']; ?> / Year</div><div class="v"><?php echo htmlspecialchars($b['term'] . ' / ' . $b['year']); ?></div></div>
        <div class="tb-chip"><div class="k">Status</div><div class="v"><?php echo htmlspecialchars(ucfirst($b['status'] ?: 'unpaid')); ?></div></div>
    </div>

    <div class="tb-sec"><i class="fa fa-file-invoice"></i> Current <?php echo $b['term_label']; ?> Bill Items</div>
    <table class="tb-tbl">
        <thead>
            <tr><th>Item</th><th class="r">Amount</th><th class="r">Paid</th><th class="r">Due</th></tr>
        </thead>
        <tbody>
            <?php foreach ($b['current_items'] as $it): ?>
            <tr>
                <td><?php echo htmlspecialchars($it['title']); ?><?php if (!empty($it['description'])): ?><div style="font-size:11px;color:#6b7280;"><?php echo htmlspecialchars($it['description']); ?></div><?php endif; ?></td>
                <td class="r"><?php echo number_format((float)$it['amount'], 2); ?></td>
                <td class="r" style="color:#059669;"><?php echo number_format((float)$it['amount_paid'], 2); ?></td>
                <td class="r" style="color:<?php echo $it['due'] > 0 ? '#b91c1c' : '#059669'; ?>;font-weight:700;"><?php echo number_format((float)$it['due'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="tot">
                <td>Current <?php echo $b['term_label']; ?> Total</td>
                <td class="r"><?php echo $currency; ?> <?php echo number_format((float)$b['current_total'], 2); ?></td>
                <td class="r"><?php echo number_format((float)$b['current_paid'], 2); ?></td>
                <td class="r"><?php echo number_format((float)$b['current_due'], 2); ?></td>
            </tr>
            <?php if ($b['arrears'] > 0): ?>
            <tr class="arrears">
                <td colspan="3"><i class="fa fa-exclamation-triangle"></i> Arrears from previous terms</td>
                <td class="r"><?php echo $currency; ?> <?php echo number_format((float)$b['arrears'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand">
                <td colspan="3">GRAND TOTAL PAYABLE</td>
                <td class="r"><?php echo $currency; ?> <?php echo number_format((float)$b['grand_total'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($b['discount_details'])): ?>
    <div class="tb-sec"><i class="fa fa-percent"></i> Approved Discounts</div>
    <?php foreach ($b['discount_details'] as $d): ?>
    <div class="tb-disc">
        <strong><?php echo $currency; ?> <?php echo number_format((float)$d['discount_amount'], 2); ?></strong> off —
        applies to: <?php echo htmlspecialchars($d['applies_to']); ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="tb-actions">
        <button type="button" class="tb-btn tb-btn-close" data-dismiss="modal">Close</button>
        <a class="tb-btn tb-btn-print" href="<?php echo site_url('admin/print_termly_bill/' . $invoice_code); ?>" target="_blank">
            <i class="fa fa-print"></i> Print Full Bill
        </a>
    </div>
</div>
