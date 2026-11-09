<style>
.requests-container { padding: 24px; background: #f8f9fa; min-height: 100vh; }
.request-card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.request-card.pending { border-color: #3b82f6; }
.request-card.approved { border-color: #10b981; }
.request-card.declined { border-color: #ef4444; }
.request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.request-title { font-size: 18px; font-weight: 700; color: #1a202c; }
.request-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
.badge-pending { background: #dbeafe; color: #1e40af; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-declined { background: #fee2e2; color: #991b1b; }
.request-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
.detail-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.detail-value { font-size: 15px; font-weight: 600; color: #1a202c; }
.request-actions { display: flex; gap: 12px; margin-top: 16px; }
.btn-action { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-approve { background: #10b981; color: white; }
.btn-approve:hover { background: #059669; }
.btn-decline { background: #ef4444; color: white; }
.btn-decline:hover { background: #dc2626; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
.filter-bar { background: white; padding: 16px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 12px; align-items: center; }
.filter-select { padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
</style>

<div class="requests-container">
    <div class="filter-bar">
        <label style="font-weight: 600; color: #374151;"><?php echo get_phrase('filter_by_status'); ?>:</label>
        <select id="invoiceStatusFilter" class="filter-select" onchange="filterInvoiceRequests()">
            <option value="all"><?php echo get_phrase('all'); ?></option>
            <option value="pending" selected><?php echo get_phrase('pending'); ?></option>
            <option value="approved"><?php echo get_phrase('approved'); ?></option>
            <option value="declined"><?php echo get_phrase('declined'); ?></option>
        </select>
    </div>

    <div id="invoiceRequestsList">
        <?php if(empty($invoice_requests)): ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <div><?php echo get_phrase('no_modification_requests_found'); ?></div>
            </div>
        <?php else: ?>
            <?php foreach($invoice_requests as $request): 
                $student = $this->db->where('student_id', $request['student_id'])->get('student')->row();
                $requester = $this->db->where('admin_id', $request['requested_by'])->get('admin')->row();
            ?>
            <div class="request-card <?php echo $request['status']; ?>" data-status="<?php echo $request['status']; ?>">
                <div class="request-header">
                    <div class="request-title">
                        <i class="fa fa-<?php echo $request['request_type'] == 'edit' ? 'edit' : 'trash'; ?>"></i>
                        <?php echo ucfirst($request['request_type']); ?> Request #<?php echo $request['request_id']; ?>
                    </div>
                    <span class="request-badge badge-<?php echo $request['status']; ?>">
                        <?php echo $request['status']; ?>
                    </span>
                </div>

                <div class="request-details">
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('student'); ?></div>
                        <div class="detail-value"><?php echo $student->name; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('invoice_number'); ?></div>
                        <div class="detail-value">#<?php echo $request['invoice_code']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('requested_by'); ?></div>
                        <div class="detail-value"><?php echo $requester->name; ?></div>
                    </div>
                </div>

                <?php if($request['reason']): ?>
                <div style="margin-top: 12px;">
                    <div class="detail-label"><?php echo get_phrase('reason'); ?></div>
                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-top: 4px;">
                        <?php echo $request['reason']; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($request['status'] == 'pending'): ?>
                <div class="request-actions">
                    <button class="btn-action btn-approve" onclick="approveInvoiceRequest(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-check"></i> <?php echo get_phrase('approve'); ?>
                    </button>
                    <button class="btn-action btn-decline" onclick="declineInvoiceRequest(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-times"></i> <?php echo get_phrase('decline'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterInvoiceRequests() {
    const status = $('#invoiceStatusFilter').val();
    if(status === 'all') {
        $('#invoiceRequestsList .request-card').show();
    } else {
        $('#invoiceRequestsList .request-card').hide();
        $(`#invoiceRequestsList .request-card[data-status="${status}"]`).show();
    }
}

function approveInvoiceRequest(requestId) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_approval"); ?>',
        '<?php echo get_phrase("approve_invoice_modification_confirm"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/review_invoice_modification/"); ?>' + requestId + '/approve',
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("approve"); ?>',
        'success'
    );
}

function declineInvoiceRequest(requestId) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_decline"); ?>',
        '<?php echo get_phrase("decline_invoice_modification_confirm"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/review_invoice_modification/"); ?>' + requestId + '/decline',
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("decline"); ?>',
        'danger'
    );
}

// Auto-filter on page load
$(document).ready(function() {
    filterInvoiceRequests();
});
</script>
