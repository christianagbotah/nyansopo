<?php
/**
 * Edit Discount Profile Modal (AJAX fragment)
 * Loaded by Admin::edit_discount_profile_modal($id) / Cashier::edit_discount_profile_modal($id)
 * Receives: $profile, $bill_items, $classes
 */
$modal_base = $this->uri->segment(1);
if (empty($modal_base) || !in_array($modal_base, array('admin', 'cashier'))) {
    $modal_base = 'admin';
}
$is_restricted = ($this->session->userdata('user_type') == 4);
$currency = get_settings('system_currency') ?: 'GHS';

$p = isset($profile) && is_array($profile) ? $profile : array();
$sel_types = array();
if (!empty($p['discount_type'])) {
    $sel_types = array_filter(array_map('trim', explode(',', $p['discount_type'])));
}
$sel_bill_items = array();
if (!empty($p['bill_item_ids'])) {
    $sel_bill_items = ($p['bill_item_ids'] === '*') ? array('all_invoice_items') : array_filter(array_map('trim', explode(',', $p['bill_item_ids'])));
}
$category = !empty($p['discount_category']) ? $p['discount_category'] : 'daily_fees';

$fee_types = array(
    'feeding'   => array('Feeding (Lunch)', 'fa-utensils'),
    'breakfast' => array('Breakfast', 'fa-mug-hot'),
    'classes'   => array('Classes Fee', 'fa-school'),
    'water'     => array('Water', 'fa-tint'),
    'transport' => array('Transport', 'fa-bus')
);
?>
<style>
.dpm-wrap { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }
.dpm-wrap * { box-sizing: border-box; }
.dpm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 22px; }
@media (max-width: 768px) { .dpm-grid { grid-template-columns: 1fr; } }
.dpm-field { margin-bottom: 14px; }
.dpm-label { display: block; font-size: 12.5px; font-weight: 700; color: #374151; margin-bottom: 6px; }
.dpm-label i { width: 16px; margin-right: 5px; color: #6366f1; }
.dpm-required { color: #e53e3e; }
.dpm-input, .dpm-select, .dpm-textarea {
    width: 100%; background: #f9fafb; border: 1.5px solid #e2e8f0; color: #1a202c;
    font-size: 13.5px; border-radius: 8px; padding: 9px 12px; transition: all .2s;
}
.dpm-input:focus, .dpm-select:focus, .dpm-textarea:focus {
    outline: none; border-color: #6366f1; background: #fff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
}
.dpm-textarea { resize: vertical; min-height: 74px; }
.dpm-hint { font-size: 11.5px; color: #6b7280; margin-top: 4px; }
.dpm-scope-box { border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f9fafb; padding: 12px; max-height: 300px; overflow-y: auto; }
.dpm-scope-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
.dpm-scope-count { background: #6366f1; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dpm-check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
@media (max-width: 768px) { .dpm-check-grid { grid-template-columns: 1fr; } }
.dpm-check {
    display: flex; align-items: center; gap: 8px; background: #fff;
    border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;
    cursor: pointer; transition: all .15s; font-size: 12.5px; font-weight: 600; color: #374151;
}
.dpm-check:hover { border-color: #a5b4fc; }
.dpm-check input { accent-color: #6366f1; width: 15px; height: 15px; cursor: pointer; margin: 0; }
.dpm-check.dpm-checked {
    background: linear-gradient(135deg, rgba(99, 102, 241, .08), rgba(139, 92, 246, .08));
    border-color: #6366f1; color: #4338ca;
}
.dpm-check.dpm-wildcard { grid-column: 1 / -1; border-color: rgba(16, 185, 129, .5); color: #065f46; background: rgba(16, 185, 129, .05); }
.dpm-check.dpm-wildcard.dpm-checked { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-color: transparent; }
.dpm-preview { margin-top: 4px; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; background: linear-gradient(135deg, rgba(102, 126, 234, .08), rgba(118, 75, 162, .08)); border: 1px solid rgba(102, 126, 234, .25); color: #4c1d95; }
.dpm-preview strong { font-size: 14px; }
.dpm-footer { display: flex; gap: 10px; justify-content: flex-end; align-items: center; margin-top: 8px; padding-top: 16px; border-top: 2px solid #e2e8f0; }
.dpm-btn { display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; font-weight: 700; font-size: 14px; border-radius: 10px; padding: 11px 22px; transition: all .2s; }
.dpm-btn-cancel { background: #fff; color: #374151; border: 2px solid #d1d5db; }
.dpm-btn-cancel:hover { background: #f3f4f6; }
.dpm-btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; box-shadow: 0 4px 14px rgba(102, 126, 234, .4); }
.dpm-btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, .5); }
.dpm-btn-save:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.dpm-alert { border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 600; margin-bottom: 12px; display: none; }
.dpm-alert.dpm-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; display: block; }
.dpm-alert.dpm-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; display: block; }
.dpm-meta { font-size: 11.5px; color: #6b7280; margin-bottom: 12px; }
</style>

<div class="dpm-wrap">
    <div class="dpm-alert" id="dpmAlert"></div>
    <div class="dpm-meta">
        <i class="fa fa-info-circle"></i> Editing profile #<?php echo intval($p['profile_id']); ?>.
        Existing student assignments keep their original snapshot until reassigned.
    </div>

    <form id="dpmForm" autocomplete="off">
        <input type="hidden" name="profile_id" value="<?php echo intval($p['profile_id']); ?>">
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">

        <div class="dpm-grid">
            <!-- LEFT COLUMN -->
            <div>
                <div class="dpm-field">
                    <label class="dpm-label"><i class="fa fa-tag"></i> <?php echo get_phrase('profile_name'); ?> <span class="dpm-required">*</span></label>
                    <input type="text" name="profile_name" id="dpmName" class="dpm-input" value="<?php echo htmlspecialchars($p['profile_name'] ?? ''); ?>" required>
                </div>

                <div class="dpm-field">
                    <label class="dpm-label"><i class="fa fa-folder"></i> <?php echo get_phrase('category'); ?> <span class="dpm-required">*</span></label>
                    <select name="discount_category" id="dpmCategory" class="dpm-select" onchange="dpmToggleCategory()" <?php echo $is_restricted ? 'disabled' : ''; ?>>
                        <option value="daily_fees" <?php echo $category == 'daily_fees' ? 'selected' : ''; ?>>📅 <?php echo get_phrase('daily_fees'); ?> (Lunch, Breakfast, Classes, Water, Transport)</option>
                        <?php if (!$is_restricted): ?>
                            <option value="invoice" <?php echo $category == 'invoice' ? 'selected' : ''; ?>>📄 <?php echo get_phrase('invoice'); ?> (Termly Bill Items)</option>
                        <?php endif; ?>
                    </select>
                    <?php if ($is_restricted): ?>
                        <input type="hidden" name="discount_category" value="daily_fees">
                        <div class="dpm-hint">Cashiers can only manage daily-fee discounts.</div>
                    <?php endif; ?>
                </div>

                <div class="dpm-field">
                    <label class="dpm-label"><i class="fa fa-calculator"></i> <?php echo get_phrase('method'); ?> <span class="dpm-required">*</span></label>
                    <select name="discount_method" id="dpmMethod" class="dpm-select" onchange="dpmUpdatePreview()">
                        <option value="percentage" <?php echo ($p['discount_method'] ?? '') == 'percentage' ? 'selected' : ''; ?>>📊 Percentage (%)</option>
                        <option value="fixed" <?php echo ($p['discount_method'] ?? '') == 'fixed' ? 'selected' : ''; ?>>💰 Fixed Amount (<?php echo $currency; ?>)</option>
                    </select>
                </div>

                <div class="dpm-field">
                    <label class="dpm-label"><i class="fa fa-dollar"></i> <?php echo get_phrase('value'); ?> <span class="dpm-required">*</span></label>
                    <input type="number" name="discount_value" id="dpmValue" class="dpm-input" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($p['discount_value'] ?? ''); ?>" required oninput="dpmUpdatePreview()">
                    <div class="dpm-hint">Percentage: 100 = full discount, 50 = half.</div>
                    <div class="dpm-preview" id="dpmPreview" style="display:none;"></div>
                </div>

                <div class="dpm-field">
                    <label class="dpm-label"><i class="fa fa-align-left"></i> <?php echo get_phrase('description'); ?></label>
                    <textarea name="description" id="dpmDescription" class="dpm-textarea" placeholder="Optional notes..."><?php echo htmlspecialchars($p['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- RIGHT COLUMN : scope -->
            <div>
                <div class="dpm-field">
                    <label class="dpm-label"><i class="fa fa-list"></i> <?php echo get_phrase('applies_to'); ?> <span class="dpm-required">*</span></label>

                    <!-- DAILY FEES SCOPE -->
                    <div class="dpm-scope-box" id="dpmDailyScope">
                        <div class="dpm-scope-head">
                            <span style="font-size:12px;font-weight:700;color:#374151;"><i class="fa fa-calendar-day" style="color:#6366f1;"></i> Fee Types Affected</span>
                            <span class="dpm-scope-count" id="dpmDailyCount">0 selected</span>
                        </div>
                        <div class="dpm-check-grid">
                            <label class="dpm-check dpm-wildcard <?php echo in_array('all_daily_fees', $sel_types) ? 'dpm-checked' : ''; ?>">
                                <input type="checkbox" name="discount_type[]" value="all_daily_fees" <?php echo in_array('all_daily_fees', $sel_types) ? 'checked' : ''; ?> onchange="dpmWildcard(this, 'daily'); dpmUpdateCount('daily'); dpmUpdatePreview();">
                                <span><i class="fa fa-star"></i> ALL daily fees (lunch + breakfast + classes + water + transport)</span>
                            </label>
                            <?php foreach ($fee_types as $key => $meta): ?>
                                <label class="dpm-check <?php echo in_array($key, $sel_types) ? 'dpm-checked' : ''; ?>">
                                    <input type="checkbox" name="discount_type[]" value="<?php echo $key; ?>" <?php echo in_array($key, $sel_types) ? 'checked' : ''; ?> onchange="dpmSingle(this, 'daily'); dpmUpdateCount('daily'); dpmUpdatePreview();">
                                    <span><i class="fa <?php echo $meta[1]; ?>"></i> <?php echo $meta[0]; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- INVOICE SCOPE -->
                    <div class="dpm-scope-box" id="dpmInvoiceScope" style="display:none;">
                        <div class="dpm-scope-head">
                            <span style="font-size:12px;font-weight:700;color:#374151;"><i class="fa fa-file-invoice" style="color:#6366f1;"></i> Bill Items Affected</span>
                            <span class="dpm-scope-count" id="dpmInvoiceCount">0 selected</span>
                        </div>
                        <div class="dpm-check-grid">
                            <label class="dpm-check dpm-wildcard <?php echo in_array('all_invoice_items', $sel_bill_items) ? 'dpm-checked' : ''; ?>">
                                <input type="checkbox" name="discount_type[]" value="all_invoice_items" <?php echo in_array('all_invoice_items', $sel_bill_items) ? 'checked' : ''; ?> onchange="dpmWildcard(this, 'invoice'); dpmUpdateCount('invoice');">
                                <span><i class="fa fa-star"></i> ALL invoice / bill items</span>
                            </label>
                            <?php if (!empty($bill_items)) : foreach ($bill_items as $item): ?>
                                <label class="dpm-check <?php echo in_array((string)$item['id'], $sel_bill_items, true) ? 'dpm-checked' : ''; ?>">
                                    <input type="checkbox" name="discount_type[]" value="<?php echo $item['id']; ?>" <?php echo in_array((string)$item['id'], $sel_bill_items, true) ? 'checked' : ''; ?> onchange="dpmSingle(this, 'invoice'); dpmUpdateCount('invoice');">
                                    <span><i class="fa fa-receipt"></i> <?php echo htmlspecialchars($item['title']); ?></span>
                                </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <div class="dpm-field">
                    <div class="dpm-preview">
                        <i class="fa fa-info-circle"></i> Assigned students keep their existing snapshot until you re-assign the profile.
                    </div>
                </div>
            </div>
        </div>

        <div class="dpm-footer">
            <button type="button" class="dpm-btn dpm-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?></button>
            <button type="submit" class="dpm-btn dpm-btn-save" id="dpmSaveBtn"><i class="fa fa-save"></i> <?php echo get_phrase('update_profile'); ?></button>
        </div>
    </form>
</div>

<script>
var dpmFeeNames = {feeding: 'Lunch (Feeding)', breakfast: 'Breakfast', classes: 'Classes Fee', water: 'Water', transport: 'Transport', all_daily_fees: 'ALL daily fees'};

function dpmWildcard(box, scope) {
    var container = (scope === 'daily') ? '#dpmDailyScope' : '#dpmInvoiceScope';
    if (box.checked) {
        jQuery(container + ' input[type=checkbox]').each(function() {
            if (this !== box) { this.checked = false; jQuery(this).closest('.dpm-check').removeClass('dpm-checked'); }
        });
        jQuery(box).closest('.dpm-check').addClass('dpm-checked');
    } else {
        jQuery(box).closest('.dpm-check').removeClass('dpm-checked');
    }
}
function dpmSingle(box, scope) {
    var container = (scope === 'daily') ? '#dpmDailyScope' : '#dpmInvoiceScope';
    var wildcard = jQuery(container + ' .dpm-wildcard input[type=checkbox]')[0];
    if (box.checked && wildcard && wildcard.checked) {
        wildcard.checked = false;
        jQuery(wildcard).closest('.dpm-check').removeClass('dpm-checked');
    }
    jQuery(box).closest('.dpm-check').toggleClass('dpm-checked', box.checked);
}
function dpmUpdateCount(scope) {
    var container = (scope === 'daily') ? '#dpmDailyScope' : '#dpmInvoiceScope';
    var n = jQuery(container + ' input[type=checkbox]:checked').length;
    jQuery((scope === 'daily') ? '#dpmDailyCount' : '#dpmInvoiceCount').text(n + ' selected');
    dpmUpdatePreview();
}
function dpmToggleCategory() {
    var cat = jQuery('#dpmCategory').val();
    jQuery('#dpmDailyScope').toggle(cat === 'daily_fees');
    jQuery('#dpmInvoiceScope').toggle(cat === 'invoice');
}
function dpmUpdatePreview() {
    var method = jQuery('#dpmMethod').val();
    var value = parseFloat(jQuery('#dpmValue').val());
    var scopes = [];
    jQuery('#dpmDailyScope input[type=checkbox]:checked').each(function() { scopes.push(this.value); });
    var cat = jQuery('#dpmCategory').val() || 'daily_fees';
    var $p = jQuery('#dpmPreview');
    if (isNaN(value) || scopes.length === 0) { $p.hide(); return; }
    var scopeTxt;
    if (scopes.indexOf('all_daily_fees') !== -1) scopeTxt = 'ALL daily fees';
    else if (cat === 'invoice') scopeTxt = scopes.length + ' bill item(s)';
    else scopeTxt = scopes.map(function(s) { return dpmFeeNames[s] || s; }).join(' + ');
    var txt = '<i class="fa fa-magic"></i> Students pay <strong>' +
        (method === 'percentage' ? (100 - value) + '% of ' : 'a reduced rate on ') + scopeTxt +
        '</strong> (' + (method === 'percentage' ? value + '% off' : '<?php echo $currency; ?> ' + value.toFixed(2) + ' off per day') + ')';
    $p.html(txt).show();
}
function dpmShowAlert(msg, type) {
    jQuery('#dpmAlert').removeClass('dpm-error dpm-success').addClass(type === 'error' ? 'dpm-error' : 'dpm-success').html(msg);
}

// Initialize visibility + counts
dpmToggleCategory();
dpmUpdateCount('daily');
dpmUpdateCount('invoice');
dpmUpdatePreview();

jQuery('#dpmForm').on('submit', function(e) {
    e.preventDefault();
    var scopes = [];
    jQuery('#dpmForm input[name="discount_type[]"]:checked').each(function() { scopes.push(this.value); });
    if (scopes.length === 0) { dpmShowAlert('Please select at least one fee type / bill item.', 'error'); return; }
    var $btn = jQuery('#dpmSaveBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    jQuery.ajax({
        url: '<?php echo site_url($modal_base . "/discount_profiles/update"); ?>',
        type: 'POST',
        data: jQuery('#dpmForm').serialize(),
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                dpmShowAlert('<i class="fa fa-check-circle"></i> ' + resp.message, 'success');
                if (typeof reloadProfilesData === 'function') { reloadProfilesData(); }
                setTimeout(function() { jQuery('#createModal').modal('hide'); }, 900);
            } else {
                dpmShowAlert(resp.message || 'Could not update the profile.', 'error');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> <?php echo get_phrase("update_profile"); ?>');
            }
        },
        error: function() {
            dpmShowAlert('Server error while saving. Please try again.', 'error');
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> <?php echo get_phrase("update_profile"); ?>');
        }
    });
});
</script>
