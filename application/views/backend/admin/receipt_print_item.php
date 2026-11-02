<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;

$this->db->select_sum('due');
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$this->db->where('can_delete !=', 'trash');
$balance_owe = $this->db->get('payment')->row()->due;

$total_amount_payable = $balance_owe + $total_amount_paid;

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0', 'year' => $running_year))->row()->class_id;
$class = getFullClassName($class_id);
?>

<div class="receipt-item">
    <div class="receipt-header">
        <div>
            <div class="receipt-code"><?php echo $receipt_code; ?></div>
            <div style="font-size: 12px; color: #6b7280;"><?php echo $student_name; ?></div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 12px; color: #6b7280;"><?php echo date('d/m/Y H:i', $date); ?></div>
            <div style="font-size: 12px; color: #6b7280;"><?php echo $class; ?></div>
        </div>
    </div>
    
    <div class="receipt-details">
        <?php
        $items = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id, 'can_delete !=' => 'trash'))->result_array();
        foreach($items as $item):
        ?>
        <div class="receipt-label"><?php echo $item['title']; ?>:</div>
        <div class="receipt-value"><?php echo $currency . ' ' . number_format($item['amount'], 2); ?></div>
        <?php endforeach; ?>
        
        <div class="receipt-label">Amount Paid:</div>
        <div class="receipt-value" style="font-weight: 700;"><?php echo $currency . ' ' . number_format($total_amount_paid, 2); ?></div>
        
        <div class="receipt-label">Total Payable:</div>
        <div class="receipt-value"><?php echo $currency . ' ' . number_format($total_amount_payable, 2); ?></div>
        
        <div class="receipt-label">Balance:</div>
        <div class="<?php echo $balance_owe > 0 ? 'balance-negative' : 'balance-positive'; ?>"><?php echo $currency . ' ' . number_format($balance_owe, 2); ?></div>
    </div>
</div>
