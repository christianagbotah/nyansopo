<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.modern-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
.modern-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25); }
.modern-header h2 { margin: 0 0 8px 0; font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; color: white; }
.modern-header h2 i { color: white; }
.modern-header p { margin: 0; opacity: 0.95; font-size: 15px; }
.modern-actions { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.modern-btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.modern-btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.modern-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.modern-btn-secondary { background: white; color: #374151; border: 2px solid #e5e7eb; }
.modern-btn-secondary:hover { border-color: #10b981; color: #10b981; }
.info-card { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 12px; margin-bottom: 24px; }
.info-card strong { color: #1e40af; display: block; margin-bottom: 8px; font-size: 15px; }
.info-card ul { margin: 0; padding-left: 20px; color: #1e3a8a; }
.info-card li { margin: 4px 0; }
.rules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
.rule-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #10b981; transition: all 0.3s; }
.rule-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
.rule-value { font-size: 32px; font-weight: 800; color: #10b981; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.rule-detail { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.rule-label { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.rule-value-text { font-size: 14px; font-weight: 600; color: #1f2937; }
.badge-all { display: inline-block; background: white; color: #6b7280; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 2px solid #e5e7eb; }
.rule-actions { margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb; display: flex; gap: 8px; }
.btn-delete { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; }
.btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
.btn-edit { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; }
.btn-edit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
.empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.empty-state i { font-size: 64px; color: #d1d5db; margin-bottom: 16px; }
.empty-state h3 { color: #6b7280; font-size: 18px; font-weight: 600; margin: 0; }
</style>

<div class="modern-container">
    <div class="modern-header">
        <h2><i class="fa fa-cog"></i><?php echo $profile['profile_name']; ?></h2>
        <p><?php echo get_phrase('manage_discount_rules'); ?></p>
    </div>

    <div class="modern-actions">
        <button class="modern-btn modern-btn-primary" onclick="showCreateRuleModal()">
            <i class="fa fa-plus"></i><?php echo get_phrase('add_rule'); ?>
        </button>
        <button class="modern-btn modern-btn-secondary" onclick="$('.close').click()">
            <i class="fa fa-times"></i><?php echo get_phrase('close'); ?>
        </button>
    </div>

    <div class="info-card">
        <strong><i class="fa fa-info-circle"></i> <?php echo get_phrase('how_rules_work'); ?></strong>
        <ul>
            <li>Leave Class empty = applies to ALL classes</li>
            <li>Leave Category empty = applies to ALL fee categories</li>
            <li>Specific Class + Specific Category = applies only to that combination</li>
        </ul>
    </div>

    <?php if(empty($rules)): ?>
    <div class="empty-state">
        <i class="fa fa-inbox"></i>
        <h3><?php echo get_phrase('no_rules_defined'); ?></h3>
    </div>
    <?php else: ?>
    <div class="rules-grid">
        <?php foreach($rules as $rule): ?>
        <div class="rule-card">
            <div class="rule-value">
                <i class="fa fa-<?php echo $profile['discount_method'] == 'percentage' ? 'percent' : 'dollar'; ?>"></i><?php echo $rule['discount_value']; ?><?php echo $profile['discount_method'] == 'percentage' ? '%' : ' GHS'; ?>
            </div>
            <div class="rule-detail">
                <div class="rule-label"><?php echo get_phrase('class'); ?>:</div>
                <div class="rule-value-text">
                    <?php 
                    if($rule['class_id']) {
                        $class = $this->db->where('class_id', $rule['class_id'])->get('class')->row_array();
                        if($class) {
                            echo $class['name'] . ' ' . $class['name_numeric'] . ' ' . $this->crud_model->get_class_section($rule['class_id']);
                        } else {
                            echo 'N/A';
                        }
                    } else {
                        echo '<span class="badge-all">All Classes</span>';
                    }
                    ?>
                </div>
            </div>
            <div class="rule-actions">
                <button class="btn-edit" onclick="editRule(<?php echo $rule['rule_id']; ?>, '<?php echo $rule['class_id']; ?>', '<?php echo $rule['bill_category_id']; ?>', '<?php echo $rule['discount_value']; ?>')">
                    <i class="fa fa-edit"></i> <?php echo get_phrase('edit'); ?>
                </button>
                <button class="btn-delete" onclick="deleteRule(<?php echo $rule['rule_id']; ?>)">
                    <i class="fa fa-trash"></i> <?php echo get_phrase('delete'); ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>



<script>
var profileId = '<?php echo $profile['profile_id']; ?>';

function showCreateRuleModal() {
    var classOpts = '<option value=""><?php echo get_phrase("all_classes"); ?></option><?php echo getFullClassList("", ""); ?>';
    
    var catOpts = '<option value=""><?php echo get_phrase("all_categories"); ?></option>';
    <?php if(!empty($categories)): foreach($categories as $cat): ?>
    catOpts += '<option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['name']; ?></option>';
    <?php endforeach; endif; ?>
    
    var content = `
        <style>
            .modern-form-group { margin-bottom: 20px; }
            .modern-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 8px; letter-spacing: 0.3px; }
            .modern-select, .modern-input { width: 100%; padding: 13px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; transition: all 0.2s; background: #ffffff; color: #1a202c; font-weight: 500; }
            .modern-select:focus, .modern-input:focus { border-color: #10b981; outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); background: #f0fdf4; }
            .modern-btn { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 13px 26px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
            .modern-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4); }
            .modern-btn i { margin-right: 6px; }
            .select2-container { width: 100% !important; }
            .select2-container--default .select2-selection--multiple { border: 2px solid #e2e8f0 !important; border-radius: 8px !important; min-height: 50px !important; padding: 6px 10px !important; background: #ffffff !important; font-size: 16px !important; }
            .select2-container--default .select2-selection--single { border: 2px solid #e2e8f0 !important; border-radius: 8px !important; height: 50px !important; padding: 10px 16px !important; background: #ffffff !important; font-size: 16px !important; }
            .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 30px !important; color: #1a202c !important; font-size: 16px !important; }
            .select2-container--default .select2-selection--single .select2-selection__arrow { height: 48px !important; }
            .select2-container--default.select2-container--focus .select2-selection--multiple,
            .select2-container--default.select2-container--focus .select2-selection--single { border-color: #10b981 !important; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important; }
            .select2-container--default .select2-selection--multiple .select2-selection__choice { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; border: none !important; color: white !important; border-radius: 6px !important; padding: 6px 12px !important; margin: 4px !important; font-size: 15px !important; }
            .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: white !important; margin-right: 6px !important; }
            .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { color: #fee !important; }
            small { font-size: 13px !important; }
        </style>
        <?php echo form_open('admin/discount_profile_rules/'.$profile['profile_id'].'/create', array('id' => 'ruleForm')); ?>
            <div class="row">
                <div class="col-md-6"><div class="modern-form-group"><label class="modern-label"><i class="fa fa-graduation-cap" style="margin-right:5px;color:#10b981;"></i><?php echo get_phrase('classes'); ?></label><select name="class_ids[]" class="modern-select select2" multiple>${classOpts}</select><small style="color:#6b7280;font-size:11px;margin-top:4px;display:block;">Leave empty for all</small></div></div>
                <div class="col-md-6"><div class="modern-form-group"><label class="modern-label"><i class="fa fa-folder" style="margin-right:5px;color:#10b981;"></i><?php echo get_phrase('bill_item'); ?></label><select name="bill_category_id" class="modern-select select2">${catOpts}</select></div></div>
            </div>
            <div class="modern-form-group"><label class="modern-label"><i class="fa fa-<?php echo $profile['discount_method'] == 'percentage' ? 'percent' : 'dollar'; ?>" style="margin-right:5px;color:#10b981;"></i><?php echo get_phrase('discount_value'); ?> <?php echo $profile['discount_method'] == 'percentage' ? '(%)' : '(GHS)'; ?></label><input type="number" name="discount_value" class="modern-input" step="0.01" min="0" <?php echo $profile['discount_method'] == 'percentage' ? 'max="100"' : ''; ?> placeholder="<?php echo $profile['discount_method'] == 'percentage' ? 'Enter percentage' : 'Enter amount'; ?>" required></div>
            <button type="submit" class="modern-btn"><i class="fa fa-save"></i><?php echo get_phrase('save'); ?></button>
        <?php echo form_close(); ?>
    `;
    showModalWithContent('createModal', '<i class="fa fa-plus"></i> <?php echo get_phrase("add_rule"); ?>: <?php echo $profile['profile_name']; ?>', content);
    setTimeout(() => {
        $('select[name="class_ids[]"]').select2({
            placeholder: '<?php echo get_phrase("select_classes"); ?>',
            allowClear: true,
            width: '100%'
        });
        $('select[name="bill_category_id"]').select2({
            placeholder: '<?php echo get_phrase("select_category"); ?>',
            allowClear: true,
            width: '100%'
        });
    }, 200);
    attachRuleFormHandler();
}

var profileId = '<?php echo $profile['profile_id']; ?>';

function editRule(ruleId, classId, categoryId, discountValue) {
    var content = `
        <style>
            .modern-form-group { margin-bottom: 20px; }
            .modern-label { display: block; font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 8px; letter-spacing: 0.3px; }
            .modern-select, .modern-input { width: 100%; padding: 13px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; transition: all 0.2s; background: #ffffff; color: #1a202c; font-weight: 500; }
            .modern-select:focus, .modern-input:focus { border-color: #10b981; outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); background: #f0fdf4; }
            .modern-btn { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 13px 26px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
            .modern-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4); }
            .modern-btn i { margin-right: 6px; }
        </style>
        <?php echo form_open('admin/discount_profile_rules/'.$profile['profile_id'].'/update', array('id' => 'ruleEditForm')); ?>
            <input type="hidden" name="rule_id" value="${ruleId}">
            <div class="row">
                <div class="col-md-6">
                    <div class="modern-form-group">
                        <label class="modern-label"><i class="fa fa-graduation-cap" style="margin-right:5px;color:#10b981;"></i><?php echo get_phrase('class'); ?></label>
                        <select name="class_id" class="modern-select select2">
                            <option value=""><?php echo get_phrase('all_classes'); ?></option>
                            <?php echo getFullClassList('', '${classId}'); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="modern-form-group">
                        <label class="modern-label"><i class="fa fa-folder" style="margin-right:5px;color:#10b981;"></i><?php echo get_phrase('bill_item'); ?></label>
                        <select name="bill_category_id" class="modern-select select2">
                            <option value=""><?php echo get_phrase('all_categories'); ?></option>
                            <?php if(!empty($categories)): foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" \${categoryId == '<?php echo $cat['category_id']; ?>' ? 'selected' : ''}><?php echo $cat['name']; ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modern-form-group">
                <label class="modern-label"><i class="fa fa-<?php echo $profile['discount_method'] == 'percentage' ? 'percent' : 'dollar'; ?>" style="margin-right:5px;color:#10b981;"></i><?php echo get_phrase('discount_value'); ?> <?php echo $profile['discount_method'] == 'percentage' ? '(%)' : '(GHS)'; ?></label>
                <input type="number" name="discount_value" class="modern-input" step="0.01" min="0" <?php echo $profile['discount_method'] == 'percentage' ? 'max="100"' : ''; ?> value="${discountValue}" placeholder="<?php echo $profile['discount_method'] == 'percentage' ? 'Enter percentage' : 'Enter amount'; ?>" required>
            </div>
            <button type="submit" class="modern-btn"><i class="fa fa-save"></i><?php echo get_phrase('update'); ?></button>
        <?php echo form_close(); ?>
    `;
    showModalWithContent('createModal', '<i class="fa fa-edit"></i> <?php echo get_phrase("edit_rule"); ?>: <?php echo $profile['profile_name']; ?>', content);
    setTimeout(() => $('.select2').select2(), 100);
    attachEditFormHandler();
}

function attachEditFormHandler() {
    $('#ruleEditForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $('#createModal').modal('hide');
        
        setTimeout(() => {
            showAjaxModal_alert('<?php echo get_phrase("updating"); ?>...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("admin/discount_profile_rules/"); ?>' + profileId + '/update',
                type: 'POST',
                data: formData,
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadRulesContent(), 500);
                } else {
                    showAjaxModal_alert(response.message || 'Failed to update rule', 'error');
                }
            }).fail(function(xhr) {
                var errorMsg = 'An error occurred while updating the rule';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAjaxModal_alert(errorMsg, 'error');
            });
        }, 300);
    });
}

function attachRuleFormHandler() {
    $('#ruleForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $('#createModal').modal('hide');
        
        setTimeout(() => {
            showAjaxModal_alert('<?php echo get_phrase("saving"); ?>...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("admin/discount_profile_rules/"); ?>' + profileId + '/create',
                type: 'POST',
                data: formData,
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadRulesContent(), 500);
                } else {
                    showAjaxModal_alert(response.message || 'Failed to save rule', 'error');
                }
            }).fail(function(xhr) {
                var errorMsg = 'An error occurred while saving the rule';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAjaxModal_alert(errorMsg, 'error');
            });
        }, 300);
    });
}

function deleteRule(id) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_delete"); ?>',
        '<?php echo get_phrase("are_you_sure"); ?>',
        function() {
            setTimeout(() => {
                showAjaxModal_alert('<?php echo get_phrase("deleting"); ?>...', 'loading');
                
                $.ajax({
                    url: '<?php echo site_url("admin/discount_profile_rules/"); ?>' + profileId + '/delete',
                    type: 'POST',
                    data: {rule_id: id},
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success', false);
                        setTimeout(() => reloadRulesContent(), 500);
                    } else {
                        showAjaxModal_alert(response.message || 'Failed to delete rule', 'error');
                    }
                }).fail(function(xhr) {
                    var errorMsg = 'An error occurred while deleting the rule';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showAjaxModal_alert(errorMsg, 'error');
                });
            }, 300);
        },
        '<?php echo get_phrase("delete"); ?>',
        'danger'
    );
}

function reloadRulesContent() {
    loadModalContent('detailsModal', '<?php echo site_url("admin/discount_profile_rules/"); ?>' + profileId, '<i class="fa fa-cog"></i> Rules');
}


</script>
