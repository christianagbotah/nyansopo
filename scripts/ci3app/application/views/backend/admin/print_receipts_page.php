<?php
$transaction_ids = $this->input->post('transaction_ids');
if(empty($transaction_ids)) {
    echo '<p>No transactions selected</p>';
    exit;
}

// Get transactions data
$this->db->where_in('transaction_id', $transaction_ids);
$transactions = $this->db->get('daily_fee_transactions')->result();

// Get school info
$school_name = $this->db->get_where('settings', ['type' => 'system_name'])->row()->description;
$school_address = $this->db->get_where('settings', ['type' => 'address'])->row()->description;
$school_phone = $this->db->get_where('settings', ['type' => 'phone'])->row()->description;
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipts</title>
    <style>
        body{font-family:Arial;margin:20px;}
        .receipt{page-break-after:always;border:2px solid #000;padding:20px;margin-bottom:30px;}
        .header{text-align:center;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:15px;}
        .logo{max-width:80px;}
        h2{margin:5px 0;}
        .info{font-size:12px;}
        .details{margin:15px 0;}
        .row{display:flex;justify-content:space-between;margin:8px 0;}
        .label{font-weight:bold;}
        .total{border-top:2px solid #000;padding-top:10px;margin-top:15px;font-size:18px;font-weight:bold;}
        .footer{text-align:center;margin-top:20px;font-size:11px;border-top:1px solid #ccc;padding-top:10px;}
        table{width:100%;border-collapse:collapse;margin:15px 0;}
        th,td{border:1px solid #000;padding:8px;text-align:left;}
        th{background:#f3f4f6;}
    </style>
</head>
<body>
<?php foreach($transactions as $t): 
    $student = $this->db->get_where('student', ['student_id' => $t->student_id])->row();
    $enroll = $this->db->select('class_id, section_id')
        ->where('student_id', $t->student_id)
        ->where('year', $this->db->get_where('settings', ['type' => 'running_year'])->row()->description)
        ->where('term', $this->db->get_where('settings', ['type' => 'running_term'])->row()->description)
        ->get('enroll')->row();
    
    $class_name = '';
    $section_name = '';
    if($enroll) {
        $class = $this->db->get_where('class', ['class_id' => $enroll->class_id])->row();
        $section = $this->db->get_where('section', ['section_id' => $enroll->section_id])->row();
        $class_name = $class->name . ' ' . $class->name_numeric;
        $section_name = $section ? $section->name : '';
    }
    
    $payment_method = get_payment_method_name($t->payment_method);
    $collector = $this->db->get_where('admin', ['admin_id' => $t->collected_by])->row();
?>
<div class="receipt">
    <div class="header">
        <h2><?php echo $school_name; ?></h2>
        <div class="info"><?php echo $school_address; ?><br><?php echo $school_phone; ?></div>
    </div>
    <h3 style="text-align:center;margin:15px 0;">DAILY FEE RECEIPT</h3>
    <div class="details">
        <div class="row"><span class="label">Receipt No:</span><span>#<?php echo $t->transaction_id; ?></span></div>
        <div class="row"><span class="label">Date:</span><span><?php echo date('d-m-Y', $t->payment_date); ?></span></div>
        <div class="row"><span class="label">Student:</span><span><?php echo $student->name; ?> (<?php echo $student->student_code; ?>)</span></div>
        <div class="row"><span class="label">Class:</span><span><?php echo $class_name . ($section_name ? ' - ' . $section_name : ''); ?></span></div>
        <div class="row"><span class="label">Payment Method:</span><span><?php echo $payment_method; ?></span></div>
    </div>
    <table>
        <tr><th>Fee Type</th><th style="text-align:right;">Amount (<?php echo $currency; ?>)</th></tr>
        <?php if($t->feeding_amount > 0): ?>
        <tr><td>Feeding</td><td style="text-align:right;"><?php echo number_format($t->feeding_amount, 2); ?></td></tr>
        <?php endif; ?>
        <?php if($t->breakfast_amount > 0): ?>
        <tr><td>Breakfast</td><td style="text-align:right;"><?php echo number_format($t->breakfast_amount, 2); ?></td></tr>
        <?php endif; ?>
        <?php if($t->classes_amount > 0): ?>
        <tr><td>Classes</td><td style="text-align:right;"><?php echo number_format($t->classes_amount, 2); ?></td></tr>
        <?php endif; ?>
        <?php if($t->water_amount > 0): ?>
        <tr><td>Water</td><td style="text-align:right;"><?php echo number_format($t->water_amount, 2); ?></td></tr>
        <?php endif; ?>
        <?php if($t->transport_amount > 0): ?>
        <tr><td>Transport</td><td style="text-align:right;"><?php echo number_format($t->transport_amount, 2); ?></td></tr>
        <?php endif; ?>
    </table>
    <div class="total">
        <div class="row"><span>TOTAL PAID:</span><span><?php echo $currency . ' ' . number_format($t->total_amount, 2); ?></span></div>
    </div>
    <div class="footer">
        Collected by: <?php echo $collector ? $collector->name : 'N/A'; ?><br>
        Thank you for your payment!
    </div>
</div>
<?php endforeach; ?>
<script>
window.onload = function() {
    window.print();
};
</script>
</body>
</html>
