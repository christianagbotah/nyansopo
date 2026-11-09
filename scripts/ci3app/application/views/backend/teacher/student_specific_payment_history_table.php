<?php
/**
 * Student-Specific Payment History Table — AJAX fragment (teacher portal)
 * Data: $student_id
 * Rendered into the teacher's student payment history page via
 * Teacher::get_payment_history_for_ssph($student_id)
 */
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : '';
$student = $this->db->get_where('student', array('student_id' => $student_id))->row();
?>
<?php if (!empty($student)): ?>
<div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; background:linear-gradient(135deg,rgba(102,126,234,.08),rgba(118,75,162,.08)); border:1px solid rgba(102,126,234,.22); border-radius:10px; padding:12px 16px;">
    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;">
        <?php echo strtoupper(substr($student->name, 0, 1)); ?>
    </div>
    <div>
        <div style="font-weight:800;font-size:14.5px;color:#111827;"><?php echo htmlspecialchars($student->name); ?></div>
        <div style="font-size:11.5px;color:#6b7280;"><?php echo htmlspecialchars($student->student_code ?: ''); ?> — payment history (<?php echo htmlspecialchars($running_term . ' / ' . $running_year); ?>)</div>
    </div>
</div>
<?php endif; ?>

<table class="table table-bordered" id="student_payments">
    <thead>
        <tr style="background:#111827;color:#fff;">
            <th><div><?php echo get_phrase('title'); ?></div></th>
            <th><div><?php echo get_phrase('description'); ?></div></th>
            <th><div><?php echo get_phrase('method'); ?></div></th>
            <th><div><?php echo get_phrase('amount'); ?></div></th>
            <th><div><?php echo get_phrase('date'); ?></div></th>
            <th><div><?php echo get_phrase('options'); ?></div></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $payments = $this->db->where('student_id', $student_id)
            ->where('payment_type', 'income')
            ->where('can_delete !=', 'trash')
            ->order_by('timestamp', 'desc')
            ->get('payment')
            ->result_array();
        $total_paid = 0;
        ?>
        <?php if (empty($payments)): ?>
        <tr><td colspan="6" style="text-align:center;padding:26px;color:#6b7280;font-weight:600;">No payment records found for this student.</td></tr>
        <?php else: foreach ($payments as $row):
            // Filter the invoice code (mirror admin logic)
            $no_invoice = 0;
            if (!empty($row['invoice_code']) && $row['invoice_code'][0] == '0') {
                $in_code = '_' . $row['invoice_code'];
            } elseif ($row['invoice_code'] == '' || $row['invoice_code'] == null) {
                $no_invoice = 1;
            } elseif ($row['invoice_code'] == 0) {
                $in_code = 900000000 + $row['invoice_id'];
            } else {
                $in_code = $row['invoice_code'];
            }
            if ($no_invoice) continue;
            $total_paid += (float)$row['amount'];
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['description']); ?></td>
            <td>
                <?php
                if ($row['method'] == 1) echo get_phrase('cash');
                elseif ($row['method'] == 2) echo get_phrase('check');
                elseif ($row['method'] == 3) echo get_phrase('card');
                elseif ($row['method'] == 4) echo 'Mobile Money';
                else echo htmlspecialchars($row['method']);
                ?>
            </td>
            <td style="font-weight:700;color:#059669;"><?php echo $currency; ?> <?php echo number_format((float)$row['amount'], 2); ?></td>
            <td><?php echo !empty($row['timestamp']) ? date('d M, Y', $row['timestamp']) : '—'; ?></td>
            <td align="center">
                <a href="#" class="btn btn-primary btn-sm" onclick="invoice_view_modal('<?php echo $in_code; ?>')">
                    <i class="entypo-credit-card"></i>&nbsp;<?php echo get_phrase('view_invoice'); ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:#d1fae5;font-weight:800;color:#065f46;">
            <td colspan="3">TOTAL PAID</td>
            <td><?php echo $currency; ?> <?php echo number_format($total_paid, 2); ?></td>
            <td colspan="2"></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<script type="text/javascript">
    $(document).ready(function() {
        $('#student_payments').dataTable();
    });

    function invoice_view_modal(invoice_code) {
        invoice_code = invoice_code.toString();
        if (invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        }
        showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/'); ?>' + invoice_code + '/0');
    }
</script>
