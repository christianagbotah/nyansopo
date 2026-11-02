<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

$discount_query = $this->db->where('invoice_code', $invoice_code)
    ->where('status', 'approved')
    ->get('invoice_discounts');

$pending_query = $this->db->where('invoice_code', $invoice_code)
    ->where('status', 'pending')
    ->get('invoice_discounts');

if($discount_query->num_rows() > 0) {
    $total_discount = 0;
    ?>
    <div class="bg-green-50 border border-green-200 rounded-lg p-5">
        <div class="flex items-center mb-3">
            <i class="fa fa-tag text-green-600 text-2xl mr-3"></i>
            <h3 class="text-xl font-bold text-green-700"><?php echo get_phrase('discount_applied'); ?></h3>
        </div>
        
        <?php foreach($discount_query->result_array() as $disc): 
            $total_discount += $disc['discount_amount'];
            $profile_name = '';
            if(!empty($disc['profile_id'])) {
                $profile = $this->db->where('profile_id', $disc['profile_id'])->get('discount_profiles')->row();
                $profile_name = $profile ? $profile->profile_name : '';
            }
        ?>
        <div class="mb-2 text-green-700">
            <span class="font-semibold"><?php echo $profile_name ? $profile_name : ucwords(str_replace('_', ' ', $disc['discount_type'] ?? 'Discount')); ?>:</span>
            <?php 
            if($disc['discount_method'] == 'percentage') {
                echo $disc['discount_value'] . '%';
            } else {
                echo $currency . ' ' . number_format($disc['discount_value'], 2);
            }
            ?>
            = <strong><?php echo $currency . ' ' . number_format($disc['discount_amount'], 2); ?></strong>
            <?php if(!empty($disc['reason'])): ?>
                <span class="text-sm text-green-600 ml-2">(<?php echo $disc['reason']; ?>)</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="border-t border-green-300 mt-3 pt-3">
            <span class="text-lg font-bold text-green-800">
                <?php echo get_phrase('total_discount'); ?>: <?php echo $currency . ' ' . number_format($total_discount, 2); ?>
            </span>
        </div>
    </div>
    <?php
}

if($pending_query->num_rows() > 0) {
    $total_pending = 0;
    ?>
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-5 mt-3">
        <div class="flex items-center mb-3">
            <i class="fa fa-clock text-yellow-600 text-2xl mr-3"></i>
            <h3 class="text-xl font-bold text-yellow-700"><?php echo get_phrase('discount_pending_approval'); ?></h3>
        </div>
        
        <?php foreach($pending_query->result_array() as $pdisc): 
            $total_pending += $pdisc['discount_amount'];
            $profile_name = '';
            if(!empty($pdisc['profile_id'])) {
                $profile = $this->db->where('profile_id', $pdisc['profile_id'])->get('discount_profiles')->row();
                $profile_name = $profile ? $profile->profile_name : '';
            }
        ?>
        <div class="mb-2 text-yellow-700">
            <span class="font-semibold"><?php echo $profile_name ? $profile_name : ucwords(str_replace('_', ' ', $pdisc['discount_type'] ?? 'Discount')); ?>:</span>
            <?php 
            if($pdisc['discount_method'] == 'percentage') {
                echo $pdisc['discount_value'] . '%';
            } else {
                echo $currency . ' ' . number_format($pdisc['discount_value'], 2);
            }
            ?>
            = <strong><?php echo $currency . ' ' . number_format($pdisc['discount_amount'], 2); ?></strong>
            <?php if(!empty($pdisc['reason'])): ?>
                <span class="text-sm text-yellow-600 ml-2">(<?php echo $pdisc['reason']; ?>)</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="border-t border-yellow-300 mt-3 pt-3">
            <span class="text-lg font-bold text-yellow-800">
                <?php echo get_phrase('total_pending'); ?>: <?php echo $currency . ' ' . number_format($total_pending, 2); ?>
            </span>
        </div>
    </div>
    <?php
}
?>
