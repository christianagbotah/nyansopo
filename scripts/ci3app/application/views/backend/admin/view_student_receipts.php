<?php
/**
 * View Student Receipts — AJAX modal fragment
 * Loaded by Admin::view_student_receipts($student_id)
 * Data: $student_id, $student_info (student row or null)
 * Table content is fetched via Admin::get_student_receipts() (POST student_id/year/term)
 */
$vsr_student = $student_info;
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$vsr_years = array();
$yq = $this->db->select('year')->distinct()->order_by('year', 'DESC')->get('payment')->result_array();
foreach ($yq as $y) { if (!empty($y['year'])) $vsr_years[] = $y['year']; }
if (!in_array($running_year, $vsr_years)) array_unshift($vsr_years, $running_year);
?>
<style>
.vsr-wrap { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }
.vsr-head {
    display: flex; align-items: center; gap: 14px; background: linear-gradient(135deg, rgba(102,126,234,.08), rgba(118,75,162,.08));
    border: 1px solid rgba(102,126,234,.22); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px;
}
.vsr-avatar {
    width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; font-size: 18px; font-weight: 800; flex-shrink: 0;
}
.vsr-head .nm { font-weight: 800; font-size: 15.5px; color: #111827; }
.vsr-head .cd { font-size: 12px; color: #6b7280; margin-top: 2px; }
.vsr-filters { display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 10px; align-items: end; margin-bottom: 14px; }
@media (max-width: 640px) { .vsr-filters { grid-template-columns: 1fr 1fr; } }
.vsr-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; margin-bottom: 5px; }
.vsr-field select {
    width: 100%; background: #f9fafb; border: 1.5px solid #e2e8f0; border-radius: 8px;
    padding: 9px 10px; font-size: 13px; color: #1a202c;
}
.vsr-field select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.vsr-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none;
    border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
}
.vsr-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(102,126,234,.35); }
.vsr-count { font-size: 12px; font-weight: 700; color: #4338ca; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 20px; padding: 8px 14px; white-space: nowrap; }
.vsr-table-holder { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
.vsr-table-holder table { margin: 0; }
</style>

<div class="vsr-wrap">
    <?php if ($vsr_student): ?>
    <div class="vsr-head">
        <div class="vsr-avatar"><?php echo strtoupper(substr($vsr_student->name, 0, 1)); ?></div>
        <div>
            <div class="nm"><?php echo htmlspecialchars($vsr_student->name); ?></div>
            <div class="cd"><i class="fa fa-id-card"></i> <?php echo htmlspecialchars($vsr_student->student_code ?: '—'); ?></div>
        </div>
    </div>

    <div class="vsr-filters">
        <div class="vsr-field">
            <label>Year</label>
            <select id="vsrYear">
                <?php foreach ($vsr_years as $yr): ?>
                <option value="<?php echo htmlspecialchars($yr); ?>" <?php echo $yr == $running_year ? 'selected' : ''; ?>><?php echo htmlspecialchars($yr); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vsr-field">
            <label>Term</label>
            <select id="vsrTerm">
                <?php foreach (array('1' => 'Term 1', '2' => 'Term 2', '3' => 'Term 3') as $tv => $tl): ?>
                <option value="<?php echo $tv; ?>" <?php echo $tv == $running_term ? 'selected' : ''; ?>><?php echo $tl; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="button" class="vsr-btn" onclick="vsrLoad()"><i class="fa fa-filter"></i> Filter</button>
        <div class="vsr-count" id="vsrCount"><i class="fa fa-receipt"></i> —</div>
    </div>

    <div class="vsr-table-holder" id="vsrTable">
        <div style="text-align:center; padding: 40px; color: #6b7280;">
            <i class="fa fa-spinner fa-spin" style="font-size: 26px; color: #667eea;"></i>
            <div style="margin-top: 10px; font-weight: 600;">Loading receipts…</div>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align:center; padding: 40px 20px; color: #6b7280;">
        <i class="fa fa-user-slash" style="font-size: 40px; color: #9ca3af;"></i>
        <div style="margin-top: 12px; font-weight: 700; font-size: 15px; color: #374151;">No student selected</div>
        <div style="font-size: 13px; margin-top: 4px;">Open this modal from a student's payment profile to view their receipt history.</div>
    </div>
    <?php endif; ?>
</div>

<script>
function vsrLoad() {
    var studentId = '<?php echo intval($student_id); ?>';
    if (!studentId) return;
    jQuery('#vsrTable').html('<div style="text-align:center;padding:34px;color:#6b7280;"><i class="fa fa-spinner fa-spin" style="font-size:24px;color:#667eea;"></i></div>');
    jQuery.ajax({
        url: '<?php echo site_url('admin/get_student_receipts'); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            year: jQuery('#vsrYear').val(),
            term: jQuery('#vsrTerm').val(),
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                jQuery('#vsrTable').html(resp.html);
                jQuery('#vsrCount').html('<i class="fa fa-receipt"></i> ' + resp.count + ' receipt' + (resp.count == 1 ? '' : 's'));
            } else {
                jQuery('#vsrTable').html('<div style="text-align:center;padding:30px;color:#b91c1c;font-weight:600;">' + (resp.message || 'Could not load receipts') + '</div>');
            }
        },
        error: function() {
            jQuery('#vsrTable').html('<div style="text-align:center;padding:30px;color:#b91c1c;font-weight:600;">Connection error. Please try again.</div>');
        }
    });
}

// Override the row-level print button target (server HTML calls printReceipt(payment_id))
function printReceipt(paymentId) {
    window.open('<?php echo site_url('admin/receipt_by_payment/'); ?>' + paymentId, '_blank');
}

<?php if ($vsr_student): ?>
vsrLoad();
<?php endif; ?>
</script>
