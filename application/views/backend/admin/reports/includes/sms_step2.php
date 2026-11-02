<?php
/**
 * SMS Debtors Wizard — Step 2: Preview (AJAX fragment)
 * Data: $students (comma-separated IDs); message via GET 'msg'
 */
$ids = array_filter(array_map('trim', explode(',', $students)));
$message = $this->input->get('msg');

$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';

$previews = array();
$no_phone = array();
foreach ($ids as $student_id) {
    $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
    if (!$student) continue;

    $parent = getStudentParentByStudentId($student_id);
    if (!$parent || empty($parent->phone)) {
        $no_phone[] = $student->name;
        continue;
    }

    $this->db->select('SUM(due) as total_due');
    $this->db->where('student_id', $student_id);
    $this->db->where('due >', 0);
    $due_row = $this->db->get('invoice')->row();
    $total_due = $due_row ? (float)$due_row->total_due : 0;

    $personalized = str_replace(
        array('{name}', '{class}', '{amount}', '{school}'),
        array($student->name, getStudentCurrentClassByStudentId($student_id), number_format($total_due, 2), $school_name),
        $message
    );

    $previews[] = array(
        'student' => $student->name,
        'phone' => $parent->phone,
        'parent' => $parent->name,
        'content' => $personalized
    );
}
?>
<style>
.sms-wiz { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }
.sms-steps { display: flex; gap: 8px; margin-bottom: 16px; }
.sms-step-dot { flex: 1; text-align: center; font-size: 11px; font-weight: 700; padding: 7px 4px; border-radius: 8px; background: #f3f4f6; color: #9ca3af; }
.sms-step-dot.active { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.sms-step-dot.done { background: #d1fae5; color: #065f46; }
.sms-prev-list { max-height: 300px; overflow-y: auto; display: grid; gap: 10px; }
.sms-prev { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #f9fafb; }
.sms-prev .to { font-size: 12px; font-weight: 700; color: #4338ca; margin-bottom: 5px; }
.sms-prev .to span { color: #6b7280; font-weight: 500; }
.sms-bubble { background: #eef2ff; border-radius: 10px; padding: 9px 12px; font-size: 12.5px; line-height: 1.5; color: #1e1b4b; white-space: pre-wrap; }
.sms-warn { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 9px 12px; font-size: 12.5px; margin-top: 10px; }
.sms-foot { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 2px solid #e5e7eb; }
.sms-btn { border: none; border-radius: 9px; padding: 10px 22px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
.sms-btn-ghost { background: #fff; color: #374151; border: 2px solid #d1d5db; }
.sms-btn-go { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.sms-btn-go:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(16,185,129,.4); }
.sms-btn-go:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.sms-total { margin-right: auto; align-self: center; font-size: 12.5px; font-weight: 700; color: #065f46; }
</style>

<div class="sms-wiz">
    <div class="sms-steps">
        <div class="sms-step-dot done"><i class="fa fa-check"></i> 1 · Compose</div>
        <div class="sms-step-dot active">2 · Preview</div>
        <div class="sms-step-dot">3 · Result</div>
    </div>

    <div style="font-size:13px;font-weight:700;margin-bottom:8px;"><i class="fa fa-eye" style="color:#667eea;"></i> Personalised previews</div>

    <?php if (empty($previews) && empty($no_phone)): ?>
    <div class="sms-warn">No valid recipients. Please go back and select students.</div>
    <?php else: ?>
    <div class="sms-prev-list">
        <?php foreach ($previews as $p): ?>
        <div class="sms-prev">
            <div class="to"><i class="fa fa-user"></i> <?php echo htmlspecialchars($p['student']); ?> <span>→ <?php echo htmlspecialchars($p['parent']); ?> (<?php echo htmlspecialchars($p['phone']); ?>)</span></div>
            <div class="sms-bubble"><?php echo htmlspecialchars($p['content']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($no_phone)): ?>
    <div class="sms-warn">
        <i class="fa fa-exclamation-triangle"></i> Skipped (no parent phone on file): <?php echo htmlspecialchars(implode(', ', $no_phone)); ?>
    </div>
    <?php endif; ?>

    <div class="sms-foot">
        <div class="sms-total"><i class="fa fa-paper-plane"></i> <?php echo count($previews); ?> SMS will be sent</div>
        <button type="button" class="sms-btn sms-btn-ghost" onclick="smsBackTo1()"><i class="fa fa-arrow-left"></i> Back</button>
        <button type="button" class="sms-btn sms-btn-go" id="smsSendBtn" onclick="smsStep3()" <?php echo empty($previews) ? 'disabled' : ''; ?>>
            <i class="fa fa-paper-plane"></i> Send <?php echo count($previews); ?> SMS
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function smsBackTo1() {
    jQuery.ajax({
        url: '<?php echo site_url('admin/getReceivablesSMSStep/1/'); ?>' + (window.__smsWizard ? window.__smsWizard.ids.join(',') : ''),
        type: 'GET',
        success: function(html) {
            jQuery('#smsWizardHolder').html(html);
            if (window.__smsWizard) {
                jQuery('#smsMsg').val(window.__smsWizard.msg);
                smsCount();
            }
        }
    });
}
function smsStep3() {
    var w = window.__smsWizard || { ids: [], msg: jQuery('#smsMsg').val() };
    var $btn = jQuery('#smsSendBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
    jQuery.post('<?php echo site_url('admin/send_receivables_sms'); ?>',
        {
            student_ids: w.ids,
            message: w.msg,
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        function(resp) {
            if (resp.status === 'success') {
                jQuery('#smsWizardHolder').load('<?php echo site_url('admin/getReceivablesSMSStep/3'); ?>', function() {
                    jQuery('#smsResTitle').html('<i class="fa fa-check-circle" style="color:#10b981;"></i> SMS Sent!');
                    jQuery('#smsResBody').html('<strong>' + resp.sent + '</strong> message(s) submitted to the SMS gateway.');
                    if (resp.skipped && resp.skipped.length) {
                        jQuery('#smsResSkip').html('<i class="fa fa-exclamation-triangle"></i> Skipped: ' + resp.skipped.join(', ')).show();
                    }
                });
            } else {
                alert(resp.message || 'Sending failed. Please try again.');
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Retry');
            }
        },
        'json'
    ).fail(function() {
        alert('Connection error. Please try again.');
        $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Retry');
    });
}
</script>
