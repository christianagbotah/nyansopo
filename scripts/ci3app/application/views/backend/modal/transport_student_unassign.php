<style>
@keyframes warningPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
}

.warning-badge {
    animation: warningPulse 2s ease-in-out infinite;
}
</style>

<div class="modal-header" style="border-bottom: none; padding: 25px 30px 15px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); position: relative;">
    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.9; font-size: 32px; font-weight: 300; text-shadow: none; position: absolute; right: 20px; top: 15px; transition: all 0.3s ease;" onmouseover="this.style.opacity='1'; this.style.transform='rotate(90deg)'" onmouseout="this.style.opacity='0.9'; this.style.transform='rotate(0)'">&times;</button>
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="entypo-attention" style="color: white; font-size: 28px;"></i>
        </div>
        <h4 class="modal-title" style="color: white; font-weight: 700; font-size: 22px; margin: 0;"><?php echo get_phrase('confirm_unassign'); ?></h4>
    </div>
</div>
<div class="modal-body" style="padding: 30px; background: white;">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #f093fb15 0%, #f5576c15 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;" class="warning-badge">
            <i class="entypo-cancel-circled" style="color: #f5576c; font-size: 48px;"></i>
        </div>
    </div>
    <p style="text-align: center; color: #2c3e50; font-size: 16px; margin-bottom: 20px; line-height: 1.6;">
        <?php echo get_phrase('are_you_sure_unassign'); ?> <strong style="color: #f5576c;"><?php echo urldecode($student_name); ?></strong> <?php echo get_phrase('from_transport'); ?>?
    </p>
    <div style="background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%); padding: 16px; border-radius: 12px; border-left: 4px solid #f5576c; display: flex; align-items: center; gap: 12px;">
        <i class="entypo-info-circled" style="color: #f5576c; font-size: 24px; flex-shrink: 0;"></i>
        <p style="margin: 0; color: #c0392b; font-size: 14px; font-weight: 500;"><?php echo get_phrase('this_action_cannot_be_undone'); ?></p>
    </div>
</div>
<div class="modal-footer" style="border-top: 1px solid #ecf0f1; padding: 20px 30px; background: #f8f9fa; display: flex; justify-content: flex-end; gap: 12px;">
    <button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; border: 2px solid #bdc3c7; background: white; color: #7f8c8d; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#95a5a6'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#bdc3c7'; this.style.transform='translateY(0)'"><?php echo get_phrase('cancel'); ?></button>
    <button type="button" class="btn btn-danger" onclick="confirmUnassign(<?php echo $student_id; ?>)" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; border: none; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(245, 87, 108, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(245, 87, 108, 0.4)'">
        <i class="entypo-cancel" style="margin-right: 6px;"></i>
        <?php echo get_phrase('unassign'); ?>
    </button>
</div>

<script>
function confirmUnassign(studentId) {
    $.ajax({
        url: '<?php echo site_url("admin/unassign_student_from_transport"); ?>',
        type: 'POST',
        data: { student_id: studentId },
        success: function(response) {
            $('#modal_ajax').modal('hide');
            showAjaxModal_alert('<?php echo get_phrase("student_unassigned_successfully"); ?>', 'Success');
            setTimeout(function() {
                location.reload();
            }, 2000);
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'Error');
        }
    });
}
</script>