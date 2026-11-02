<style>
.summary-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
.summary-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; }
.summary-body { padding: 24px; }
.info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
.info-label { color: #6b7280; font-weight: 600; }
.info-value { color: #111827; font-weight: 600; }
.amount-large { font-size: 32px; font-weight: 700; }
.status-badge { padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
.status-paid { background: #d1fae5; color: #065f46; }
.status-partial { background: #fef3c7; color: #92400e; }
.status-unpaid { background: #fee2e2; color: #991b1b; }
.items-table { width: 100%; margin-top: 20px; }
.items-table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; color: #374151; }
.items-table td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
.action-btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
</style>

<?php
$student_id = $this->uri->segment(3);
$invoice_code = $this->uri->segment(4);

$summary = $this->db->get_where('invoice_summary', [
    'student_id' => $student_id,
    'invoice_code' => $invoice_code
])->row();

$items = $this->db->get_where('invoice', [
    'student_id' => $student_id,
    'invoice_code' => $invoice_code
])->result_array();

$discounts = $this->db->select('d.*, dt.name as type_name, dt.icon')
    ->from('invoice_discounts d')
    ->join('discount_types dt', 'd.discount_type = dt.code', 'left')
    ->where('d.student_id', $student_id)
    ->where('d.invoice_code', $invoice_code)
    ->get()->result_array();
?>

<div class="summary-card">
    <div class="summary-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0 0 8px 0;"><?php echo $summary->student_name; ?></h2>
                <p style="margin: 0; opacity: 0.9;"><?php echo $summary->student_code; ?> • <?php echo $summary->class_name; ?></p>
            </div>
            <span class="status-badge status-<?php echo $summary->payment_status; ?>">
                <?php echo strtoupper($summary->payment_status); ?>
            </span>
        </div>
    </div>
    
    <div class="summary-body">
        <div class="info-row">
            <span class="info-label">Invoice Code:</span>
            <span class="info-value"><?php echo $invoice_code; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Academic Period:</span>
            <span class="info-value">Term <?php echo $summary->term; ?>, <?php echo $summary->year; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Invoice Date:</span>
            <span class="info-value"><?php echo date('M d, Y', $summary->invoice_date); ?></span>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: right;">Amount</th>
                    <th style="text-align: right;">Paid</th>
                    <th style="text-align: right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?php echo $item['title']; ?></td>
                    <td style="text-align: right;">GHS <?php echo number_format($item['amount'], 2); ?></td>
                    <td style="text-align: right;">GHS <?php echo number_format($item['amount_paid'], 2); ?></td>
                    <td style="text-align: right;">GHS <?php echo number_format($item['due'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if(!empty($discounts)): ?>
        <div style="margin-top: 20px; padding: 16px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
            <h4 style="margin: 0 0 12px 0; color: #065f46;">💰 Discounts Applied</h4>
            <?php foreach($discounts as $discount): ?>
            <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                <span><?php echo $discount['icon']; ?> <?php echo $discount['type_name']; ?></span>
                <span style="font-weight: 600; color: #059669;">-GHS <?php echo number_format($discount['discount_amount'], 2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="font-size: 18px; font-weight: 600;">Subtotal:</span>
                <span style="font-size: 18px; font-weight: 600;">GHS <?php echo number_format($summary->total_amount + $summary->total_discount, 2); ?></span>
            </div>
            <?php if($summary->total_discount > 0): ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #059669;">
                <span style="font-size: 16px; font-weight: 600;">Total Discount:</span>
                <span style="font-size: 16px; font-weight: 600;">-GHS <?php echo number_format($summary->total_discount, 2); ?></span>
            </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="font-size: 18px; font-weight: 600;">Total Amount:</span>
                <span class="amount-large" style="color: #667eea;">GHS <?php echo number_format($summary->total_amount, 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="font-size: 16px;">Amount Paid:</span>
                <span style="font-size: 16px; color: #10b981;">GHS <?php echo number_format($summary->total_paid, 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 16px; background: #fef3c7; border-radius: 8px;">
                <span style="font-size: 20px; font-weight: 700;">Balance Due:</span>
                <span style="font-size: 24px; font-weight: 700; color: #dc2626;">GHS <?php echo number_format($summary->total_due, 2); ?></span>
            </div>
        </div>
        
        <div style="margin-top: 24px; display: flex; gap: 12px;">
            <button onclick="window.print()" class="action-btn btn-primary">
                <i class="fa fa-print"></i> Print Invoice
            </button>
            <?php if($summary->total_due > 0): ?>
            <button onclick="sendReminder()" class="action-btn btn-success">
                <i class="fa fa-sms"></i> Send Payment Reminder
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function sendReminder() {
    showConfirmModal(
        'Send Payment Reminder',
        'Send SMS reminder to parent about outstanding balance?',
        function() {
            showAjaxModal_alert('Sending...', 'loading');
            $.ajax({
                url: '<?php echo base_url(); ?>admin/send_payment_reminder',
                type: 'POST',
                data: {
                    student_id: <?php echo $student_id; ?>,
                    invoice_code: '<?php echo $invoice_code; ?>'
                },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Send',
        'success'
    );
}
</script>
