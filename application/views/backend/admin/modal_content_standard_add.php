<div class="modal-content modern-modal-content">
    <div class="modal-header modern-modal-header">
        <button type="button" class="close modern-close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" style="color: #fff; font-weight: 600;">
            <i class="entypo-plus-circled"></i> <?php echo get_phrase('add_content_standard'); ?>
        </h4>
    </div>
    
    <?php echo form_open('admin/curriculum_content_standards/create', array('id' => 'addContentStandardForm')); ?>
        <div class="modal-body">
            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px;">
                    <?php echo get_phrase('sub_strand'); ?> <span style="color: red;">*</span>
                </label>
                <select name="sub_strand_id" class="form-control modern-input" required>
                    <option value=""><?php echo get_phrase('select_sub_strand'); ?></option>
                    <?php foreach ($sub_strands as $sub_strand): ?>
                        <option value="<?php echo $sub_strand->sub_strand_id; ?>" <?php echo ($selected_sub_strand == $sub_strand->sub_strand_id) ? 'selected' : ''; ?>>
                            <?php echo $sub_strand->name . ' (' . $sub_strand->strand_name . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px;">
                    <?php echo get_phrase('code'); ?> <span style="color: red;">*</span>
                </label>
                <input type="text" name="code" class="form-control modern-input" 
                       placeholder="e.g., B4.1.1.1" required>
                <small class="text-muted" style="font-size: 12px; color: #6b7280; margin-top: 4px; display: block;">
                    <?php echo get_phrase('unique_code_for_content_standard'); ?>
                </small>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px;">
                    <?php echo get_phrase('description'); ?> <span style="color: red;">*</span>
                </label>
                <textarea name="description" class="form-control modern-textarea" rows="4" 
                          placeholder="<?php echo get_phrase('enter_detailed_description'); ?>" required></textarea>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="modern-btn" style="background: #e5e7eb; color: #374151;" data-dismiss="modal">
                <?php echo get_phrase('close'); ?>
            </button>
            <button type="submit" class="modern-btn modern-btn-primary">
                <i class="entypo-check"></i> <?php echo get_phrase('save'); ?>
            </button>
        </div>
    <?php echo form_close(); ?>
</div>

<script>
$(document).ready(function() {
    $('#addContentStandardForm').on('submit', function(e) {
        e.preventDefault();
        
        showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'Loading');
        
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
