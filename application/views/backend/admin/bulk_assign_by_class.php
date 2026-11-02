<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.bulk-container { max-width: 800px; margin: 0 auto; padding: 24px; }
.bulk-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25); }
.bulk-header h1 { margin: 0 0 8px 0; font-size: 32px; font-weight: 700; }
.bulk-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.form-group { margin-bottom: 24px; }
.form-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 8px; }
.form-select { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; }
.btn-bulk { padding: 16px 32px; border-radius: 10px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; }
.btn-bulk:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.info-box { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin-bottom: 24px; }
</style>

<div class="bulk-container">
    <div class="bulk-header">
        <h1><i class="fa fa-users"></i> <?php echo get_phrase('bulk_assign_by_class'); ?></h1>
        <p><?php echo get_phrase('assign_discount_profile_to_entire_class_or_section'); ?></p>
    </div>

    <div class="bulk-card">
        <div class="info-box">
            <i class="fa fa-info-circle"></i> <strong>Note:</strong> This will assign the selected discount profile to all students in the selected class/section who don't already have it.
        </div>

        <?php echo form_open('admin/bulk_assign_by_class/assign', ['id' => 'bulkForm']); ?>
            <div class="form-group">
                <label class="form-label"><i class="fa fa-school"></i> <?php echo get_phrase('select_class'); ?></label>
                <select name="class_id" id="class_id" class="form-select" required>
                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                    <?php foreach($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>">
                            <?php echo $class['name'].' '.$class['name_numeric']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa fa-th-large"></i> <?php echo get_phrase('select_section'); ?> (Optional)</label>
                <select name="section_id" id="section_id" class="form-select">
                    <option value=""><?php echo get_phrase('all_sections'); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa fa-tag"></i> <?php echo get_phrase('select_discount_profile'); ?></label>
                <select name="profile_id" class="form-select" required>
                    <option value=""><?php echo get_phrase('select_profile'); ?></option>
                    <?php foreach($profiles as $profile): ?>
                        <option value="<?php echo $profile['profile_id']; ?>">
                            <?php echo $profile['profile_name']; ?> (<?php echo $profile['discount_type']; ?> - <?php echo $profile['discount_value']; ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-bulk">
                <i class="fa fa-check"></i> <?php echo get_phrase('assign_to_class'); ?>
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<script>
$('#class_id').on('change', function() {
    const classId = $(this).val();
    if(classId) {
        $.ajax({
            url: '<?php echo site_url('admin/get_sections_by_class'); ?>/' + classId,
            type: 'GET',
            dataType: 'json'
        }).done(function(sections) {
            let options = '<option value="">All Sections</option>';
            sections.forEach(s => {
                options += `<option value="${s.section_id}">${s.name}</option>`;
            });
            $('#section_id').html(options);
        });
    }
});

$('#bulkForm').submit(function(e) {
    e.preventDefault();
    
    showConfirmModal(
        'Confirm Bulk Assignment',
        'Are you sure you want to assign this discount to all students in the selected class/section?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $('#bulkForm').serialize(),
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            });
        },
        'Assign',
        'primary'
    );
});
</script>
