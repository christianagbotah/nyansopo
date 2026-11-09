<!-- 
    Daily Fee Discount Badge Component
    Enterprise-grade UX component for displaying active discounts
    Usage: Include this in attendance marking and fee collection views
-->

<?php if(isset($discount) && $discount): ?>
<div class="discount-badge-container" style="display: inline-block; margin-left: 8px;">
    <span class="badge badge-success discount-badge" 
          style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                 padding: 4px 10px; 
                 border-radius: 12px; 
                 font-size: 11px; 
                 font-weight: 600;
                 box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
                 animation: pulse-glow 2s infinite;"
          data-toggle="tooltip" 
          data-placement="top" 
          title="<?php echo $discount->reason ?? 'Active discount'; ?>">
        <i class="fa fa-gift"></i>
        <?php 
            if($discount->discount_method == 'percentage') {
                echo $discount->discount_value . '% OFF';
            } else {
                echo 'GH₵' . number_format($discount->discount_value, 2) . ' OFF';
            }
        ?>
    </span>
</div>

<style>
@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }
    50% {
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.6);
    }
}

.discount-badge:hover {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}
</style>
<?php endif; ?>
