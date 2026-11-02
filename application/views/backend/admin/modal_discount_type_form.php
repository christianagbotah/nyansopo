<style>
.modern-form-group { margin-bottom: 20px; }
.modern-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block; }
.modern-input { border: 2px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 14px; transition: all 0.3s; height: 48px; }
.modern-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); outline: none; }
.modern-textarea { border: 2px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 14px; transition: all 0.3s; resize: vertical; min-height: 100px; }
.modern-textarea:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); outline: none; }
.modern-select { border: 2px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 14px; transition: all 0.3s; height: 48px; }
.modern-select:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); outline: none; }
</style>

<?php echo form_open('admin/save_discount_type', array('id' => 'discount-type-form')); ?>
    <div class="modal-body" style="background: #f9fafb; padding: 24px;">
        <?php if($discount_type): ?>
            <input type="hidden" name="id" value="<?php echo $discount_type->discount_type_id; ?>">
        <?php endif; ?>
        <?php if(isset($category_id) && $category_id): ?>
            <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
        <?php else: ?>
            <div class="modern-form-group">
                <label class="modern-label"><?php echo get_phrase('discount_category'); ?> <span class="text-danger">*</span></label>
                <select name="category_id" class="form-control modern-select" required>
                    <option value="">Select Category</option>
                    <?php 
                    $categories = $this->db->get('discount_categories')->result();
                    foreach($categories as $cat): 
                    ?>
                        <option value="<?php echo $cat->category_id; ?>" <?php echo ($discount_type && $discount_type->category_id == $cat->category_id) ? 'selected' : ''; ?>>
                            <?php echo $cat->name; ?> (<?php echo ucfirst($cat->code); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <div class="modern-form-group">
            <label class="modern-label"><?php echo get_phrase('discount_type_name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control modern-input" value="<?php echo $discount_type ? $discount_type->name : ''; ?>" placeholder="<?php echo get_phrase('enter_discount_type_name'); ?>" required>
        </div>
        
        <div class="modern-form-group">
            <label class="modern-label"><?php echo get_phrase('icon'); ?> <span style="font-size: 11px; color: #9ca3af;">(Emoji)</span></label>
            <input type="text" id="icon-input" name="icon" class="form-control modern-input" value="<?php echo $discount_type ? $discount_type->icon : ''; ?>" placeholder="Click emoji below" maxlength="4" style="cursor: pointer;">
            <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                <span onclick="$('#icon-input').val('💰')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">💰</span>
                <span onclick="$('#icon-input').val('🎓')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">🎓</span>
                <span onclick="$('#icon-input').val('👨‍👩‍👧‍👦')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">👨‍👩‍👧‍👦</span>
                <span onclick="$('#icon-input').val('⚡')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">⚡</span>
                <span onclick="$('#icon-input').val('🎁')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">🎁</span>
                <span onclick="$('#icon-input').val('💳')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">💳</span>
                <span onclick="$('#icon-input').val('🏆')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">🏆</span>
                <span onclick="$('#icon-input').val('📚')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">📚</span>
                <span onclick="$('#icon-input').val('🌟')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">🌟</span>
                <span onclick="$('#icon-input').val('💝')" style="font-size: 28px; cursor: pointer; padding: 8px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.15)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'">💝</span>
            </div>
        </div>
        
        <div class="modern-form-group">
            <label class="modern-label"><?php echo get_phrase('description'); ?></label>
            <textarea name="description" class="form-control modern-textarea" rows="3" placeholder="<?php echo get_phrase('enter_description_optional'); ?>"><?php echo $discount_type ? $discount_type->description : ''; ?></textarea>
        </div>
        
        <div class="modern-form-group">
            <label class="modern-label"><?php echo get_phrase('discount_method'); ?> <span class="text-danger">*</span></label>
            <select name="discount_method" class="form-control modern-select" required>
                <option value="percentage" <?php echo ($discount_type && isset($discount_type->default_method) && $discount_type->default_method == 'percentage') ? 'selected' : ''; ?>><?php echo get_phrase('percentage'); ?> (%)</option>
                <option value="fixed" <?php echo ($discount_type && isset($discount_type->default_method) && $discount_type->default_method == 'fixed') ? 'selected' : ''; ?>><?php echo get_phrase('fixed_amount'); ?></option>
            </select>
        </div>
        
        <div class="modern-form-group">
            <label class="modern-label"><?php echo get_phrase('discount_value'); ?> <span class="text-danger">*</span></label>
            <input type="number" name="discount_value" class="form-control modern-input" value="<?php echo ($discount_type && isset($discount_type->default_value)) ? $discount_type->default_value : '0'; ?>" min="0" step="0.01" placeholder="<?php echo get_phrase('enter_discount_value'); ?>" required>
            <small class="text-muted"><?php echo get_phrase('enter_percentage_0_100_or_fixed_amount'); ?></small>
        </div>
        
        <div class="modern-form-group">
            <label class="modern-label"><?php echo get_phrase('status'); ?></label>
            <select name="is_active" class="form-control modern-select">
                <option value="1" <?php echo ($discount_type && $discount_type->is_active == 1) ? 'selected' : ''; ?>><?php echo get_phrase('active'); ?></option>
                <option value="0" <?php echo ($discount_type && $discount_type->is_active == 0) ? 'selected' : ''; ?>><?php echo get_phrase('inactive'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="form-group" style="margin-top: 20px; text-align: right;">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px;"><?php echo get_phrase('save'); ?></button>
    </div>
<?php echo form_close(); ?>

<script>
$('#discount-type-form').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('processing'); ?>', 'loading');
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false
    }).done(function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if(data.status === 'success') {
            showAjaxModal_alert(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(data.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});
</script>
