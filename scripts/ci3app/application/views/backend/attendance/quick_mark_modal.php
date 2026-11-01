<style>
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; width: 100%; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 42px; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
</style>

<div style="padding: 20px;">
    <div class="row">
        <div class="col-md-6">
            <label class="form-label"><?php echo get_phrase('select_class'); ?></label>
            <select id="quick_class" class="form-control">
                <option value=""><?php echo get_phrase('select'); ?></option>
                <?php getFullClassList($teacher_id ?? ''); ?>
            </select>
        </div>
        
        <div class="col-md-6">
            <label class="form-label"><?php echo get_phrase('date'); ?></label>
            <input type="date" id="quick_date" class="form-control" value="<?php echo $date; ?>">
        </div>
    </div>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <button onclick="proceedToMark()" class="btn-modern btn-primary-modern">
                <i class="fa fa-arrow-right"></i> <?php echo get_phrase('proceed'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function proceedToMark() {
    const classId = $('#quick_class').val();
    const date = $('#quick_date').val();
    
    if(!classId) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_class'); ?>', 'error');
        return;
    }
    
    $('.close').click();
    navigation('<?php echo site_url('attendance/mark'); ?>?class_id=' + classId + '&date=' + date);
}
</script>
