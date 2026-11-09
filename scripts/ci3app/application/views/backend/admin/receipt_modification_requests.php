<style>
.requests-container { padding: 24px; background: #f8f9fa; min-height: 100vh; }
.request-card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.request-card.pending { border-color: #f59e0b; }
.request-card.approved { border-color: #10b981; }
.request-card.rejected { border-color: #ef4444; }
.request-card.revoked { border-color: #6b7280; }
.request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.request-title { font-size: 18px; font-weight: 700; color: #1a202c; }
.request-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.badge-revoked { background: #e5e7eb; color: #374151; }
.request-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
.detail-item { }
.detail-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.detail-value { font-size: 15px; font-weight: 600; color: #1a202c; }
.request-actions { display: flex; gap: 12px; margin-top: 16px; }
.btn-action { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-approve { background: #10b981; color: white; }
.btn-approve:hover { background: #059669; }
.btn-reject { background: #ef4444; color: white; }
.btn-reject:hover { background: #dc2626; }
.btn-view { background: #3b82f6; color: white; }
.btn-view:hover { background: #2563eb; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
.filter-bar { background: white; padding: 16px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 12px; align-items: center; }
.filter-select { padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
</style>

<div class="requests-container">
    <div class="filter-bar">
        <label style="font-weight: 600; color: #374151;"><?php echo get_phrase('filter_by_status'); ?>:</label>
        <select id="statusFilter" class="filter-select" onchange="filterRequests()">
            <option value="all"><?php echo get_phrase('all'); ?></option>
            <option value="pending" selected><?php echo get_phrase('pending'); ?></option>
            <option value="approved"><?php echo get_phrase('approved'); ?></option>
            <option value="rejected"><?php echo get_phrase('rejected'); ?></option>
            <option value="revoked"><?php echo get_phrase('revoked'); ?></option>
        </select>
    </div>

    <div id="requestsList">
        <?php if(empty($requests)): ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <div><?php echo get_phrase('no_modification_requests_found'); ?></div>
            </div>
        <?php else: ?>
            <?php foreach($requests as $request): 
                $payments = json_decode($request['original_data'], true);
                $payment = is_array($payments) && !empty($payments) ? $payments[0] : null;
                if(!$payment) continue;
                $student = $this->db->where('student_id', $payment['student_id'])->get('student')->row();
                $requester = $this->db->where('admin_id', $request['requested_by'])->get('admin')->row();
                $total_amount = is_array($payments) ? array_sum(array_column($payments, 'amount')) : 0;
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
                        <div class="detail-label"><?php echo get_phrase('receipt_number'); ?></div>
                        <div class="detail-value">#<?php echo $request['receipt_code']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('amount'); ?></div>
                        <div class="detail-value">GHS <?php echo number_format($total_amount, 2); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('requested_by'); ?></div>
                        <div class="detail-value"><?php echo $requester->name; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('requested_date'); ?></div>
                        <div class="detail-value"><?php echo date('d M Y, H:i', $request['requested_at']); ?></div>
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <div class="detail-label"><?php echo get_phrase('reason'); ?></div>
                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-top: 4px;">
                        <?php echo $request['reason']; ?>
                    </div>
                </div>

                <?php if($request['status'] == 'pending' && $this->session->userdata('user_type') == 1): ?>
                <div class="request-actions">
                    <button class="btn-action btn-view" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-eye"></i> <?php echo get_phrase('view_details'); ?>
                    </button>
                    <button class="btn-action btn-approve" onclick="approveRequest(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-check"></i> <?php echo get_phrase('approve'); ?>
                    </button>
                    <button class="btn-action btn-reject" onclick="rejectRequest(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-times"></i> <?php echo get_phrase('reject'); ?>
                    </button>
                </div>
                <?php elseif($request['status'] == 'approved' && $this->session->userdata('user_type') == 1): ?>
                <div class="request-actions">
                    <button class="btn-action btn-view" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-eye"></i> <?php echo get_phrase('view_details'); ?>
                    </button>
                    <button class="btn-action btn-reject" onclick="revokeApproval(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-undo"></i> <?php echo get_phrase('revoke_approval'); ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="request-actions">
                    <button class="btn-action btn-view" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                        <i class="fa fa-eye"></i> <?php echo get_phrase('view_details'); ?>
                    </button>
                </div>
                <?php endif; ?>

                <?php if($request['status'] == 'rejected' && $request['rejection_reason']): ?>
                <div style="margin-top: 12px;">
                    <div class="detail-label"><?php echo get_phrase('rejection_reason'); ?></div>
                    <div style="padding: 12px; background: #fee2e2; border-radius: 8px; margin-top: 4px; color: #991b1b;">
                        <?php echo $request['rejection_reason']; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterRequests() {
    const status = $('#statusFilter').val();
    if(status === 'all') {
        $('.request-card').show();
    } else {
        $('.request-card').hide();
        $(`.request-card[data-status="${status}"]`).show();
    }
}

function viewRequestDetails(requestId) {
    loadModalContent('detailsModal', '<?php echo site_url("admin/receipt_modification_details/"); ?>' + requestId, 
        '<i class="fa fa-info-circle"></i> <?php echo get_phrase("request_details"); ?>');
}

function approveRequest(requestId) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_approval"); ?>',
        '<?php echo get_phrase("approve_modification_request_confirm"); ?>',
        function() {
            // Disable all action buttons immediately
            $('.btn-action').prop('disabled', true).css('opacity', '0.5');
            
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/approve_receipt_modification"); ?>',
                type: 'POST',
                data: { request_id: requestId },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else if(response.already_processed) {
                    // Silently reload if already processed
                    location.reload();
                } else {
                    showAjaxModal_alert(response.message, 'error');
                    $('.btn-action').prop('disabled', false).css('opacity', '1');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
                $('.btn-action').prop('disabled', false).css('opacity', '1');
            });
        },
        '<?php echo get_phrase("approve"); ?>',
        'success'
    );
}

function rejectRequest(requestId) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_rejection"); ?>',
        '<div style="margin-bottom: 16px;"><?php echo get_phrase("enter_rejection_reason"); ?>:</div><textarea id="rejectionReason" class="form-control" rows="3" placeholder="<?php echo get_phrase("rejection_reason"); ?>" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;"></textarea>',
        function() {
            const reason = $('#rejectionReason').val().trim();
            if(!reason) {
                showAjaxModal_alert('<?php echo get_phrase("rejection_reason_required"); ?>', 'warning');
                return;
            }
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/reject_receipt_modification"); ?>',
                type: 'POST',
                data: { request_id: requestId, reason: reason },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => {
                        $('.close').click();
                        refreshRequestsList();
                    }, 1500);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("reject"); ?>',
        'danger'
    );
}

function revokeApproval(requestId) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_revoke"); ?>',
        '<?php echo get_phrase("revoke_approval_warning"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/revoke_receipt_approval"); ?>',
                type: 'POST',
                data: { request_id: requestId },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => {
                        $('.close').click();
                        refreshRequestsList();
                    }, 1500);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("revoke"); ?>',
        'danger'
    );
}

function refreshRequestsList() {
    $.ajax({
        url: '<?php echo site_url("admin/get_receipt_modification_requests"); ?>',
        type: 'GET',
        dataType: 'html'
    }).done(function(response) {
        $('#requestsList').html(response);
        filterRequests();
    }).fail(function() {
        console.error('Failed to refresh requests list');
    });
}
</script>
