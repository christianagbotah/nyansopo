<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get filter parameters
$start_date = $this->input->post('start_date');
$end_date = $this->input->post('end_date');
$receipt_code = $this->input->post('receipt_code');
$class_id = $this->input->post('class_id');
$student_id = $this->input->post('student_id');

// Build query - Group by receipt_code and sum amounts
$this->db->select('payment.receipt_code, SUM(payment.amount) as total_amount, MAX(payment.timestamp) as timestamp, MAX(payment.payment_method) as payment_method, student.name as student_name, student.student_code, class.name as class_name, class.name_numeric, payment.student_id');
$this->db->from('payment');
$this->db->join('student', 'student.student_id = payment.student_id');
$this->db->join('enroll', 'enroll.student_id = student.student_id AND enroll.mute = "0"', 'left');
$this->db->join('class', 'class.class_id = enroll.class_id', 'left');

// Apply filters
if (!empty($start_date)) {
    $start_timestamp = strtotime($start_date);
    $this->db->where('payment.day_timestamp >=', $start_timestamp);
}
if (!empty($end_date)) {
    $end_timestamp = strtotime($end_date . ' 23:59:59');
    $this->db->where('payment.day_timestamp <=', $end_timestamp);
}
if (!empty($receipt_code)) {
    $this->db->like('payment.receipt_code', $receipt_code);
}
if (!empty($class_id)) {
    $this->db->where('enroll.class_id', $class_id);
}
if (!empty($student_id)) {
    $this->db->where('payment.student_id', $student_id);
}

$this->db->group_by('payment.receipt_code');
$this->db->order_by('timestamp', 'DESC');

$receipts = $this->db->get()->result_array();

// Calculate total payment
$total_payment = 0;
foreach ($receipts as $receipt) {
    $total_payment += $receipt['total_amount'];
}
$total_receipts = count($receipts);

?>

<!-- Total Payment Card -->
<div style="background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
    <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.08); border-radius: 50%;"></div>
    <div style="display: flex; justify-content: space-around; align-items: center; position: relative; z-index: 1;">
        <div style="text-align: center;">
            <div style="color: rgba(255,255,255,0.95); font-size: 1.25rem; margin-bottom: 12px; font-weight: 500;">Total Payment Received</div>
            <div style="color: white; font-size: 2.5rem; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo $currency . ' ' . number_format($total_payment, 2); ?></div>
        </div>
        <div style="width: 2px; height: 80px; background: rgba(255,255,255,0.3);"></div>
        <div style="text-align: center;">
            <div style="color: rgba(255,255,255,0.95); font-size: 1.25rem; margin-bottom: 12px; font-weight: 500;">Total Receipts</div>
            <div style="color: white; font-size: 2.5rem; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo number_format($total_receipts); ?></div>
        </div>
    </div>
</div>

<style>
#receipts_table {
    border: none !important;
    border-collapse: separate;
    border-spacing: 0;
}
#receipts_table thead tr {
    background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%);
}
#receipts_table thead th {
    color: white !important;
    border: none !important;
    padding: 16px !important;
    font-size: 1rem !important;
    font-weight: 600;
}
#receipts_table tbody tr {
    border: none !important;
    transition: all 0.2s;
}
#receipts_table tbody tr:hover {
    background-color: #f3f4f6 !important;
    transform: scale(1.01);
}
#receipts_table tbody td {
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 12px 16px !important;
}
.table-responsive::-webkit-scrollbar {
    height: 8px;
}
.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.table-responsive:hover::-webkit-scrollbar-thumb {
    background: transparent;
}
</style>

<div class="table-responsive">
    <table class="table" id="receipts_table">
        <thead>
            <tr>
                <!-- <th style="width: 5%;">#</th> -->
                <th style="width: 13%;">Date</th>
                <th style="width: 10%; text-align: left;">Receipt Code</th>
                <th style="width: 15%;">Student Name</th>
                <th style="width: 10%;">Student ID</th>
                <th style="width: 10%;">Class</th>
                <th style="width: 12%; text-align: right;">Amount</th>
                <th style="width: 10%;">Payment Method</th>
                <th style="width: 15%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($receipts) > 0): ?>
                <?php $i = 1; foreach ($receipts as $receipt): 
                    $this->db->select_sum('due');
                    $this->db->where('receipt_code', $receipt['receipt_code']);
                    $total_due = $this->db->get('payment')->row()->due;
                    $total_fees_owe = $receipt['total_amount'] + $total_due;
                    ?>
                    <tr>
                        <!-- <td><?php //echo $i++; ?></td> -->
                        <td style="color: #6b7280;"><?php echo date('d M, Y H:i:s', $receipt['timestamp']); ?></td>
                        <td style="text-align: left; color: #7e22ce; font-weight: 600;"><?php echo $receipt['receipt_code']; ?></td>
                        <td style="color: #1f2937; font-weight: 500;"><?php echo $receipt['student_name']; ?></td>
                        <td style="color: #6b7280;"><?php echo $receipt['student_code']; ?></td>
                        <td style="color: #6b7280;"><?php echo $receipt['class_name'] . ' ' . $receipt['name_numeric']; ?></td>
                        <td style="text-align: right; color: #059669; font-weight: 600;"><?php echo $currency . ' ' . number_format($receipt['total_amount'], 2); ?></td>
                        <td><span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 12px; font-size: 0.875rem; font-weight: 600;"><?php echo ucfirst($receipt['payment_method']); ?></span></td>
                        <td>
                            <a href="<?php echo site_url('admin/receipt/' . $receipt['receipt_code'] . '/' . $receipt['student_id'] . '/' . $receipt['total_amount'] . '/' . $receipt['timestamp'] . '/' . $total_fees_owe); ?>" 
                               class="btn btn-sm btn-info" target="_blank" style="border-radius: 8px; margin-right: 5px;">
                                <i class="fa fa-eye"></i> View
                            </a>
                            <button onclick="requestModificationByReceipt('<?php echo $receipt['receipt_code']; ?>')" 
                               class="btn btn-sm btn-warning" style="border-radius: 8px;">
                                <i class="fa fa-edit"></i> Modify
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- <tr>
                    <td colspan="9" class="text-center">No receipts found</td>
                </tr> -->
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    $('#receipts_table').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});

function requestModificationByReceipt(receiptCode) {
    $.get('<?php echo site_url("admin/get_payment_id_by_receipt/"); ?>' + receiptCode, function(response) {
        var data = JSON.parse(response);
        if(data.status === 'success') {
            loadModalContent('createModal', 
                '<?php echo site_url("admin/receipt_modification_modal/"); ?>' + data.payment_id, 
                '<i class="fa fa-edit"></i> Request Receipt Modification');
        } else {
            showAjaxModal_alert('Receipt not found', 'error');
        }
    });
}
</script>
