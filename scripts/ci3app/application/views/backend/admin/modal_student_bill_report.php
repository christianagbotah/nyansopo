<?php
/**
 * MODAL: Student Bill Report - Cumulative Owings
 * Shows ALL invoice items across all terms/years with cumulative balances
 */

$student_id = $param2;

// Get student info
$student = $this->db->get_where('student', array('student_id' => $student_id))->row();
$enrollment = $this->crud_model->getStudentCurrentEnrollmentStatusRow($student_id);
$class_info = $this->db->get_where('class', array('class_id' => $enrollment->class_id))->row();
$section_info = $this->db->get_where('section', array('section_id' => $enrollment->section_id))->row();

// Get currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get ALL invoices for this student (across all terms/years)
$this->db->select('title, SUM(amount) as total_amount, SUM(amount_paid) as total_paid, SUM(due) as total_due');
$this->db->where('student_id', $student_id);
$this->db->group_by('title');
$this->db->order_by('title', 'ASC');
$invoice_items = $this->db->get('invoice')->result();

// Calculate grand totals
$grand_total_amount = 0;
$grand_total_paid = 0;
$grand_total_due = 0;

foreach($invoice_items as $item) {
    $grand_total_amount += $item->total_amount;
    $grand_total_paid += $item->total_paid;
    $grand_total_due += $item->total_due;
}
?>

<!-- Student Header -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; margin-bottom: 20px; color: white;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <div style="flex-shrink: 0;">
            <img src="<?php echo $this->crud_model->get_image_url('student', $student_id, $student->sex); ?>" 
                 style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid white; object-fit: cover;">
        </div>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 8px 0; color: white; font-size: 20px; font-weight: 700;"><?php echo $student->name; ?></h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 13px;">
                <div><i class="fa fa-id-card"></i> <strong>ID:</strong> <?php echo $student->student_code; ?></div>
                <div><i class="fa fa-school"></i> <strong>Class:</strong> <?php echo $class_info->name . ' ' . $class_info->name_numeric . ' ' . $section_info->name; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Bill Summary -->
<div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
        <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
            <div style="color: #64748b; font-size: 12px; margin-bottom: 5px;">Total Billed</div>
            <div style="color: #1e293b; font-size: 18px; font-weight: 700;"><?php echo $currency . ' ' . number_format($grand_total_amount, 2); ?></div>
        </div>
        <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
            <div style="color: #64748b; font-size: 12px; margin-bottom: 5px;">Total Paid</div>
            <div style="color: #10b981; font-size: 18px; font-weight: 700;"><?php echo $currency . ' ' . number_format($grand_total_paid, 2); ?></div>
        </div>
        <div style="text-align: center; padding: 10px; background: white; border-radius: 6px;">
            <div style="color: #64748b; font-size: 12px; margin-bottom: 5px;">Balance Due</div>
            <div style="color: <?php echo $grand_total_due > 0 ? '#ef4444' : '#10b981'; ?>; font-size: 18px; font-weight: 700;"><?php echo $currency . ' ' . number_format($grand_total_due, 2); ?></div>
        </div>
    </div>
</div>

<!-- Invoice Details -->
<?php if(count($invoice_items) > 0): ?>
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 10px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Item</th>
                    <th style="padding: 10px; text-align: right; font-size: 12px; color: #64748b; font-weight: 600;">Total Billed</th>
                    <th style="padding: 10px; text-align: right; font-size: 12px; color: #64748b; font-weight: 600;">Total Paid</th>
                    <th style="padding: 10px; text-align: right; font-size: 12px; color: #64748b; font-weight: 600;">Balance</th>
                    <th style="padding: 10px; text-align: center; font-size: 12px; color: #64748b; font-weight: 600;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($invoice_items as $item): 
                    $status = 'unpaid';
                    if($item->total_due == 0) $status = 'paid';
                    elseif($item->total_paid > 0 && $item->total_due > 0) $status = 'partial';
                ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; font-size: 13px; color: #1e293b; font-weight: 600;"><?php echo $item->title; ?></td>
                        <td style="padding: 10px; text-align: right; font-size: 13px; color: #1e293b; font-weight: 600;"><?php echo $currency . ' ' . number_format($item->total_amount, 2); ?></td>
                        <td style="padding: 10px; text-align: right; font-size: 13px; color: #10b981; font-weight: 600;"><?php echo $currency . ' ' . number_format($item->total_paid, 2); ?></td>
                        <td style="padding: 10px; text-align: right; font-size: 13px; color: <?php echo $item->total_due > 0 ? '#ef4444' : '#10b981'; ?>; font-weight: 600;"><?php echo $currency . ' ' . number_format($item->total_due, 2); ?></td>
                        <td style="padding: 10px; text-align: center;">
                            <?php if($status == 'paid'): ?>
                                <span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">PAID</span>
                            <?php elseif($status == 'partial'): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">PARTIAL</span>
                            <?php else: ?>
                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">UNPAID</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div style="background: #f8fafc; padding: 30px; border-radius: 8px; text-align: center;">
        <i class="fa fa-file-invoice" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
        <p style="color: #64748b; font-size: 14px; margin: 0;">No invoices found for this student in the current term.</p>
    </div>
<?php endif; ?>

<!-- Action Buttons -->
<div style="margin-top: 20px; text-align: center; padding-top: 15px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: center;">
    <button type="button" onclick="printBillReport(<?php echo $student_id; ?>)" class="btn btn-primary" style="padding: 10px 25px; font-weight: 600;">
        <i class="fa fa-print"></i> Print Bill Report
    </button>
    <?php if($grand_total_due > 0): ?>
    <button type="button" onclick="takePayment(<?php echo $student_id; ?>)" class="btn btn-success" style="padding: 10px 25px; font-weight: 600;">
        <i class="fa fa-money-bill-wave"></i> Take Payment
    </button>
    <?php endif; ?>
</div>

<script>
function printBillReport(studentId) {
    window.open('<?php echo site_url('admin/student_bill_report/'); ?>' + studentId, '_blank');
}

function takePayment(studentId) {
    $('#modal_ajax').modal('hide');
    setTimeout(function() {
        showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + studentId, 'take_payment');
    }, 300);
}
</script>
