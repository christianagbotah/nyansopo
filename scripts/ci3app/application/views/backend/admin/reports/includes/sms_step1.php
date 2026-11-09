<?php
/**
 * SMS Debtors Wizard — Step 1: Compose (AJAX fragment)
 * Data: $students (comma-separated student IDs, optional)
 * If empty, loads all current debtors (running year/term).
 */
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');

$preselect = array();
if (!empty($students)) {
    $preselect = array_filter(array_map('trim', explode(',', $students)));
}

// All students with outstanding dues this term
$debtors = $this->db->select('i.student_id, SUM(i.due) as total_due, MIN(s.name) as name, MIN(s.student_code) as student_code')
    ->from('invoice i')
    ->join('student s', 's.student_id = i.student_id')
    ->where('i.year', $running_year)
    ->where('i.term', $running_term)
    ->where('i.due >', 0)
    ->group_by('i.student_id')
    ->order_by('total_due', 'DESC')
    ->get()
    ->result_array();

$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$default_msg = "Dear Parent, kindly note that {name} ({class}) has an outstanding school fee balance of GHS {amount}. Please settle at your earliest convenience. Thank you - {school}.";
?>
<style>
.sms-wiz { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }
.sms-steps { display: flex; gap: 8px; margin-bottom: 16px; }
.sms-step-dot { flex: 1; text-align: center; font-size: 11px; font-weight: 700; padding: 7px 4px; border-radius: 8px; background: #f3f4f6; color: #9ca3af; }
.sms-step-dot.active { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.sms-step-dot.done { background: #d1fae5; color: #065f46; }
.sms-area { width: 100%; min-height: 120px; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 13.5px; font-family: inherit; resize: vertical; background: #f9fafb; }
.sms-area:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); background: #fff; }
.sms-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 4px; }
.sms-chip { font-size: 11.5px; font-weight: 700; color: #4338ca; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 20px; padding: 5px 12px; cursor: pointer; }
.sms-chip:hover { background: #6366f1; color: #fff; }
.sms-counter { font-size: 11.5px; color: #6b7280; margin-top: 6px; text-align: right; }
.sms-list { max-height: 230px; overflow-y: auto; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-top: 8px; }
.sms-stu { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-bottom: 1px solid #f3f4f6; cursor: pointer; font-size: 13px; }
.sms-stu:hover { background: #f9fafb; }
.sms-stu input { accent-color: #6366f1; width: 15px; height: 15px; }
.sms-stu .amt { margin-left: auto; font-weight: 800; color: #b91c1c; white-space: nowrap; }
.sms-foot { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 2px solid #e5e7eb; }
.sms-btn { border: none; border-radius: 9px; padding: 10px 22px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
.sms-btn-ghost { background: #fff; color: #374151; border: 2px solid #d1d5db; }
.sms-btn-go { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.sms-btn-go:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(102,126,234,.4); }
.sms-total { margin-right: auto; align-self: center; font-size: 12.5px; font-weight: 700; color: #4338ca; }
</style>

<div class="sms-wiz">
    <div class="sms-steps">
        <div class="sms-step-dot active">1 · Compose</div>
        <div class="sms-step-dot">2 · Preview</div>
        <div class="sms-step-dot">3 · Result</div>
    </div>

    <div style="font-size: 13px; font-weight: 700; margin-bottom: 6px;"><i class="fa fa-comment-dots" style="color:#667eea;"></i> Message <span style="color:#e53e3e;">*</span></div>
    <textarea id="smsMsg" class="sms-area" placeholder="Type your reminder message..."><?php echo htmlspecialchars($default_msg); ?></textarea>

    <div class="sms-chips">
        <span class="sms-chip" onclick="smsInsert('{name}')">{name} student</span>
        <span class="sms-chip" onclick="smsInsert('{class}')">{class} class</span>
        <span class="sms-chip" onclick="smsInsert('{amount}')">{amount} due</span>
        <span class="sms-chip" onclick="smsInsert('{school}')">{school} name</span>
    </div>
    <div class="sms-counter"><span id="smsChars">0</span> characters · <span id="smsParts">1</span> SMS part(s) · personalised per parent</div>

    <div style="font-size: 13px; font-weight: 700; margin-top: 14px;">
        <i class="fa fa-users" style="color:#667eea;"></i> Recipients — debtors (<?php echo $running_term; ?> / <?php echo $running_year; ?>)
    </div>
    <?php if (empty($debtors)): ?>
    <div style="text-align:center;padding:26px;color:#6b7280;background:#f9fafb;border:1.5px dashed #d1d5db;border-radius:10px;margin-top:8px;">
        <i class="fa fa-check-circle" style="font-size:26px;color:#10b981;"></i>
        <div style="font-weight:700;margin-top:6px;">No outstanding dues this term</div>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:8px;margin-top:8px;">
        <span class="sms-chip" onclick="smsToggleAll(true)">Select all</span>
        <span class="sms-chip" onclick="smsToggleAll(false)">Clear</span>
    </div>
    <div class="sms-list" id="smsList">
        <?php foreach ($debtors as $d): $sel = empty($preselect) || in_array((string)$d['student_id'], $preselect, true); ?>
        <label class="sms-stu">
            <input type="checkbox" class="sms-stu-chk" value="<?php echo $d['student_id']; ?>" <?php echo $sel ? 'checked' : ''; ?>>
            <span><?php echo htmlspecialchars($d['name']); ?> <span style="color:#9ca3af;">(<?php echo htmlspecialchars($d['student_code']); ?>)</span></span>
            <span class="amt">GHS <?php echo number_format((float)$d['total_due'], 2); ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <div class="sms-foot">
        <div class="sms-total"><i class="fa fa-paper-plane"></i> <span id="smsSelCount">0</span> parent(s) selected</div>
        <button type="button" class="sms-btn sms-btn-ghost" data-dismiss="modal">Cancel</button>
        <button type="button" class="sms-btn sms-btn-go" onclick="smsStep2()"><i class="fa fa-arrow-right"></i> Preview</button>
    </div>
    <?php endif; ?>
</div>

<script>
function smsInsert(token) {
    var ta = document.getElementById('smsMsg');
    var pos = ta.selectionStart || ta.value.length;
    ta.value = ta.value.substring(0, pos) + token + ta.value.substring(ta.selectionEnd || pos);
    ta.focus();
    smsCount();
}
function smsCount() {
    var len = jQuery('#smsMsg').val().length;
    jQuery('#smsChars').text(len);
    jQuery('#smsParts').text(Math.max(1, Math.ceil(len / 160)));
    jQuery('#smsSelCount').text(jQuery('.sms-stu-chk:checked').length);
}
function smsToggleAll(on) {
    jQuery('.sms-stu-chk').prop('checked', on);
    smsCount();
}
jQuery('.sms-stu-chk').on('change', smsCount);
jQuery('#smsMsg').on('input', smsCount);
smsCount();

function smsStep2() {
    var ids = [];
    jQuery('.sms-stu-chk:checked').each(function() { ids.push(this.value); });
    var msg = jQuery('#smsMsg').val().trim();
    if (ids.length === 0) { alert('Please select at least one student.'); return; }
    if (msg === '') { alert('Please write a message.'); return; }
    window.__smsWizard = { ids: ids, msg: msg };
    jQuery.ajax({
        url: '<?php echo site_url('admin/getReceivablesSMSStep/2/'); ?>' + ids.join(','),
        type: 'GET',
        data: { msg: msg },
        success: function(html) {
            jQuery('#smsWizardHolder').html(html);
        },
        error: function() {
            alert('Could not load the preview. Please try again.');
        }
    });
}
</script>
