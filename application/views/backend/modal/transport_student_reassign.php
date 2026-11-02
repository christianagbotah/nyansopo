<style>
.modern-modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modern-select {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: white;
}

.modern-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}
</style>

<div class="modal-header" style="border-bottom: none; padding: 25px 30px 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); position: relative;">
    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.9; font-size: 32px; font-weight: 300; text-shadow: none; position: absolute; right: 20px; top: 15px; transition: all 0.3s ease;" onmouseover="this.style.opacity='1'; this.style.transform='rotate(90deg)'" onmouseout="this.style.opacity='0.9'; this.style.transform='rotate(0)'">&times;</button>
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="entypo-shuffle" style="color: white; font-size: 24px;"></i>
        </div>
        <h4 class="modal-title" style="color: white; font-weight: 700; font-size: 22px; margin: 0;"><?php echo get_phrase('reassign_student'); ?></h4>
    </div>
</div>
<div class="modal-body" style="padding: 30px; background: white;">
    <div style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); padding: 16px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid #667eea;">
        <p style="margin: 0; color: #2c3e50; font-size: 15px;"><?php echo get_phrase('select_new_route_for'); ?> <strong style="color: #667eea;"><?php echo urldecode($student_name); ?></strong></p>
    </div>
    <div class="form-group" style="margin-bottom: 0;">
        <label style="font-weight: 600; color: #2c3e50; margin-bottom: 10px; font-size: 14px; display: block;">
            <i class="entypo-location" style="color: #667eea; margin-right: 6px;"></i>
            <?php echo get_phrase('select_new_route'); ?>
        </label>
        <select class="form-control modern-select" id="new_transport" required>
            <option value=""><?php echo get_phrase('select_route'); ?></option>
            <?php
            $transports = $this->db->get('transport')->result_array();
            foreach ($transports as $transport_option):
                if ($transport_option['transport_id'] != $transport_id):
            ?>
                <option value="<?php echo $transport_option['transport_id']; ?>"><?php echo $transport_option['route_name']; ?></option>
            <?php
                endif;
            endforeach;
            ?>
        </select>
    </div>
</div>
<div class="modal-footer" style="border-top: 1px solid #ecf0f1; padding: 20px 30px; background: #f8f9fa; display: flex; justify-content: flex-end; gap: 12px;">
    <button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; border: 2px solid #bdc3c7; background: white; color: #7f8c8d; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#95a5a6'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#bdc3c7'; this.style.transform='translateY(0)'"><?php echo get_phrase('cancel'); ?></button>
    <button type="button" class="btn btn-primary" onclick="confirmReassign(<?php echo $student_id; ?>)" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; border: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)'">
        <i class="entypo-shuffle" style="margin-right: 6px;"></i>
        <?php echo get_phrase('reassign'); ?>
    </button>
</div>

<script>
function confirmReassign(studentId) {
    var newTransportId = $('#new_transport').val();
    if (!newTransportId) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_route"); ?>', 'Error');
        return;
    }

    $.ajax({
        url: '<?php echo site_url("admin/reassign_student_transport"); ?>',
        type: 'POST',
        data: { student_id: studentId, transport_id: newTransportId },
        success: function(response) {
            $('#modal_ajax').modal('hide');
            showAjaxModal_alert('<?php echo get_phrase("student_reassigned_successfully"); ?>', 'Success');
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