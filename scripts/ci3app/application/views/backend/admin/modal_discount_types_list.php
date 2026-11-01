<style>
.discount-type-card { background: #fff; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s; border-left: 4px solid #667eea; }
.discount-type-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
.discount-type-name { font-size: 14px; font-weight: 600; color: #2d3748; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; text-transform: uppercase; margin-right: 10px; }
.status-active { background: #d1fae5; color: #065f46; }
.status-inactive { background: #f3f4f6; color: #6b7280; }
.action-btns { display: flex; gap: 8px; }
.btn-modern { border-radius: 8px; padding: 8px 16px; font-weight: 500; transition: all 0.3s; border: none; }
.btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
</style>

<div class="modal-dialog modal-md">
    <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px 8px 0 0;">
            <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.9;">&times;</button>
            <h4 class="modal-title"><i class="fa fa-tags"></i> <?php echo get_phrase('manage_discount_types'); ?></h4>
        </div>

<div class="modal-body" style="max-height: 500px; overflow-y: auto; background: #f9fafb; padding: 24px;">
    <div style="text-align: right; margin-bottom: 20px;">
        <button class="btn btn-primary btn-modern" onclick="addNewDiscountType()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="fa fa-plus-circle"></i> <?php echo get_phrase('add_new_discount_type'); ?>
        </button>
    </div>
    
    <?php
    $discount_types = $this->db->order_by('discount_type_id', 'DESC')->get('discount_types')->result_array();
    if(count($discount_types) > 0):
        foreach($discount_types as $type):
    ?>
    <div class="discount-type-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; margin-bottom: 4px;">
                    <span class="status-badge <?php echo $type['is_active'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $type['is_active'] == 1 ? get_phrase('active') : get_phrase('inactive'); ?>
                    </span>
                    <span class="discount-type-name"><?php echo ($type['icon'] ?? '') . ' ' . $type['name']; ?></span>
                </div>
                <?php if(!empty($type['description'])): ?>
                <div style="font-size: 11px; color: #6b7280; margin-left: 0px; padding-left: 0px;"><?php echo $type['description']; ?></div>
                <?php endif; ?>
            </div>
            <div class="action-btns">
                <button class="btn btn-xs btn-primary" onclick="editDiscountType(<?php echo $type['discount_type_id']; ?>)" title="<?php echo get_phrase('edit'); ?>" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-danger" onclick="deleteDiscountType(<?php echo $type['discount_type_id']; ?>)" title="<?php echo get_phrase('delete'); ?>" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php 
        endforeach;
    else:
    ?>
    <div class="empty-state">
        <i class="fa fa-inbox"></i>
        <h4><?php echo get_phrase('no_discount_types_found'); ?></h4>
        <p><?php echo get_phrase('click_add_new_to_create_discount_type'); ?></p>
    </div>
    <?php endif; ?>
</div>

        <div class="modal-footer" style="background: #f9fafb;">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
        </div>
    </div>
</div>

<script>
function addNewDiscountType() {
    loadModalContent('modal_ajax', '<?php echo site_url("admin/discount_type_form/add"); ?>', '<i class="fa fa-tag"></i> <?php echo get_phrase("add_discount_type"); ?>');
}

function editDiscountType(id) {
    loadModalContent('modal_ajax', '<?php echo site_url("admin/discount_type_form/"); ?>' + id, '<i class="fa fa-edit"></i> <?php echo get_phrase("edit_discount_type"); ?>');
}

function deleteDiscountType(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_delete'); ?>',
        '<?php echo get_phrase('are_you_sure_you_want_to_delete_this_discount_type'); ?>',
        function() {
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>', 'loading');
            $.post('<?php echo site_url("admin/delete_discount_type"); ?>', {id: id}, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if(data.status === 'success') {
                    showAjaxModal_alert(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(data.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}
</script>
