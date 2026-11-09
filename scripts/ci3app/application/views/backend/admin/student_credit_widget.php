<?php
// Only show credit widget if student_id is set and valid
if(isset($student_id) && !empty($student_id)):
    // Load Credit_model
    $this->load->model('Credit_model');
    
    // Get student credit balance
    $total_credit = $this->Credit_model->get_student_total_credit($student_id);
    
    if($total_credit > 0):
?>
<div class="alert alert-success" style="margin: 15px 0; padding: 15px; border-left: 4px solid #10b981;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <i class="fa fa-gift" style="font-size: 24px; color: #10b981; margin-right: 10px;"></i>
            <strong style="font-size: 16px;">Credit Balance Available</strong>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 24px; font-weight: bold; color: #10b981;">
                GH₵ <?php echo number_format($total_credit, 2); ?>
            </div>
            <small style="color: #6b7280;">Available for future invoices</small>
        </div>
    </div>
</div>
<?php 
    endif;
endif;
?>
