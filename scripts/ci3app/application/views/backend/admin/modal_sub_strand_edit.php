<div class="modal-content modern-modal-content">
    <div class="modal-header modern-modal-header">
        <button type="button" class="close modern-close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" style="color: #fff; font-weight: 600;">
            <i class="entypo-pencil"></i> <?php echo get_phrase('edit_sub_strand'); ?>
        </h4>
    </div>
    
    <?php echo form_open('admin/curriculum_sub_strands/update/' . $sub_strand->sub_strand_id, array('id' => 'editSubStrandForm')); ?>
        <div class="modal-body">
            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px;">
                    <?php echo get_phrase('parent_strand'); ?> <span style="color: red;">*</span>
                </label>
                <select name="strand_id" class="form-control modern-input" required>
                    <option value=""><?php echo get_phrase('select_strand'); ?></option>
                    <?php foreach ($strands as $strand): ?>
                        <option value="<?php echo $strand->strand_id; ?>" <?php echo ($sub_strand->strand_id == $strand->strand_id) ? 'selected' : ''; ?>>
                            <?php 
                            // Build display text with available information
                            $display = $strand->name;
                            
                            if (!empty($strand->subject_name)) {
                                $display .= ' - ' . $strand->subject_name;
                            }
                            
                            // Build class info
                            $class_info = '';
                            if (!empty($strand->class_name)) {
                                $class_info = $strand->class_name;
                            }
                            if (!empty($strand->class_numeric)) {
                                $class_info .= ' ' . $strand->class_numeric;
                            }
                            if (!empty($strand->section_name)) {
                                $class_info .= ' ' . $strand->section_name;
                            }
                            
                            if (!empty($class_info)) {
                                $display .= ' - ' . $class_info;
                            }
                            
                            echo $display;
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted" style="font-size: 12px; color: #6b7280; margin-top: 4px; display: block;">
                    <?php echo get_phrase('select_the_parent_strand_for_this_sub_strand'); ?>
                </small>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px;">
                    <?php echo get_phrase('sub_strand_name'); ?> <span style="color: red;">*</span>
                </label>
                <input type="text" name="name" class="form-control modern-input" 
                       value="<?php echo $sub_strand->name; ?>" required>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px;">
                    <?php echo get_phrase('description'); ?>
                </label>
                <textarea name="description" class="form-control modern-textarea" rows="4"><?php echo $sub_strand->description; ?></textarea>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="modern-btn" style="background: #e5e7eb; color: #374151;" data-dismiss="modal">
                <?php echo get_phrase('close'); ?>
            </button>
            <button type="submit" class="modern-btn modern-btn-primary">
                <i class="entypo-check"></i> <?php echo get_phrase('update'); ?>
            </button>
        </div>
    <?php echo form_close(); ?>
</div>

<script>
$(document).ready(function() {
    $('#editSubStrandForm').on('submit', function(e) {
        e.preventDefault();
        
        showAjaxModal_alert('<?php echo get_phrase('updating'); ?>...', 'Loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modal_ajax').modal('hide');
                    showAjaxModal_alert(response.message, 'Success', true);
                } else {
                    showAjaxModal_alert(response.message, 'Error');
                }
            },
            error: function(xhr) {
                showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>: ' + xhr.responseText, 'Error');
            }
        });
    });
});
</script>

<style>
.modern-input,
.modern-textarea {
    width: 100%;
    padding: 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
    min-height: 48px;
}

.modern-textarea {
    min-height: 120px;
    line-height: 1.6;
}

.modern-input:focus,
.modern-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
}
</style>
