<style>
.requests-container { padding: 24px; background: #f8f9fa; min-height: 100vh; }
.request-card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.request-card.pending { border-color: #f59e0b; }
.request-card.approved { border-color: #10b981; }
.request-card.rejected, .request-card.declined { border-color: #ef4444; }
.request-card.revoked { border-color: #6b7280; }
.request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.request-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-rejected, .badge-declined { background: #fee2e2; color: #991b1b; }
.badge-revoked { background: #e5e7eb; color: #374151; }
.request-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
.detail-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.detail-value { font-size: 15px; font-weight: 600; color: #1a202c; word-break: break-word; }
.request-actions { display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap; }
.btn-action { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; white-space: nowrap; }
.btn-approve { background: #10b981; color: white; }
.btn-approve:hover { background: #059669; }
.btn-reject { background: #ef4444; color: white; }
.btn-reject:hover { background: #dc2626; }
.btn-view { background: #3b82f6; color: white; }
.btn-view:hover { background: #2563eb; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
.filter-bar { background: white; padding: 16px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.filter-select { padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; min-width: 150px; }
.bulk-actions-bar { background: white; padding: 16px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: none; align-items: center; gap: 12px; flex-wrap: wrap; }
.bulk-actions-bar.active { display: flex; }
.checkbox-cell { width: 40px; display: flex; align-items: center; justify-content: center; }
.checkbox-cell input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }

@media (max-width: 768px) {
    .requests-container { padding: 12px; }
    .request-card { padding: 16px; }
    .request-title { font-size: 16px; }
    .request-details { grid-template-columns: 1fr; gap: 12px; }
    .filter-bar { flex-direction: column; align-items: stretch; }
    .filter-bar label { margin-left: 0 !important; }
    .filter-select { width: 100%; }
    .btn-action { padding: 8px 16px; font-size: 14px; }
}
</style>

<div class="requests-container">
    <div class="filter-bar">
        <label style="font-weight: 600; color: #374151;"><?php echo get_phrase('filter_by_type'); ?>:</label>
        <select id="typeFilter" class="filter-select" onchange="filterRequests()">
            <option value="all"><?php echo get_phrase('all'); ?></option>
            <option value="receipt"><?php echo get_phrase('receipts'); ?></option>
            <option value="invoice"><?php echo get_phrase('invoices'); ?></option>
        </select>
        
        <label style="font-weight: 600; color: #374151; margin-left: 20px;"><?php echo get_phrase('filter_by_status'); ?>:</label>
        <select id="statusFilter" class="filter-select" onchange="filterRequests()">
            <option value="all"><?php echo get_phrase('all'); ?></option>
            <option value="pending" selected><?php echo get_phrase('pending'); ?></option>
            <option value="approved"><?php echo get_phrase('approved'); ?></option>
            <option value="rejected"><?php echo get_phrase('rejected'); ?></option>
            <option value="revoked"><?php echo get_phrase('revoked'); ?></option>
        </select>
    </div>

    <div class="bulk-actions-bar" id="bulkActionsBar">
        <label style="font-weight: 600; color: #374151;">
            <span id="selectedCount">0</span> <?php echo get_phrase('selected'); ?>
        </label>
        <button class="btn-action" onclick="selectAll()" style="margin: 0; background: #3b82f6; color: white;">
            <i class="fa fa-check-square"></i> <?php echo get_phrase('select_all'); ?>
        </button>
        <button class="btn-action" onclick="deselectAll()" style="margin: 0; background: #6b7280; color: white;">
            <i class="fa fa-square"></i> <?php echo get_phrase('deselect_all'); ?>
        </button>
        <button class="btn-action btn-approve" onclick="bulkApprove()" style="margin: 0;">
            <i class="fa fa-check"></i> <?php echo get_phrase('bulk_approve'); ?>
        </button>
        <button class="btn-action btn-reject" onclick="bulkReject()" style="margin: 0;">
            <i class="fa fa-times"></i> <?php echo get_phrase('bulk_reject'); ?>
        </button>
    </div>

    <div id="requestsList">
        <?php 
        $all_requests = array_merge(
            isset($requests) ? $requests : [],
            isset($invoice_requests) ? $invoice_requests : []
        );
        
        if(empty($all_requests)): ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <div><?php echo get_phrase('no_modification_requests_found'); ?></div>
            </div>
        <?php else: 
            foreach($all_requests as $request): 
                $is_receipt = isset($request['receipt_code']);
                
                if($is_receipt) {
                    $payments = json_decode($request['original_data'], true);
                    $payment = is_array($payments) && !empty($payments) ? $payments[0] : null;
                    if(!$payment) continue;
                    $student = $this->db->where('student_id', $payment['student_id'])->get('student')->row();
                    $total_amount = is_array($payments) ? array_sum(array_column($payments, 'amount')) : 0;
                } else {
                    $student = $this->db->where('student_id', $request['student_id'])->get('student')->row();
                }
                
                $requester = $this->db->where('admin_id', $request['requested_by'])->get('admin')->row();
            ?>
            <div class="request-card <?php echo $request['status']; ?>" data-status="<?php echo $request['status']; ?>" data-type="<?php echo $is_receipt ? 'receipt' : 'invoice'; ?>" data-request-id="<?php echo $request['request_id']; ?>" data-is-receipt="<?php echo $is_receipt ? '1' : '0'; ?>">
                <div class="request-header">
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <?php if($request['status'] == 'pending'): 
                            $user_level = $this->session->userdata('user_type');
                            $is_super_admin = ($user_level == 1);
                            if($is_super_admin): 
                        ?>
                        <div class="checkbox-cell">
                            <input type="checkbox" class="request-checkbox">
                        </div>
                        <?php endif; endif; ?>
                        <span style="background: <?php echo $is_receipt ? '#3b82f6' : '#8b5cf6'; ?>; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                            <i class="fa fa-<?php echo $is_receipt ? 'receipt' : 'file-invoice'; ?>"></i> <?php echo $is_receipt ? 'Receipt' : 'Invoice'; ?>
                        </span>
                        <span style="background: <?php echo $request['request_type'] == 'edit' ? '#10b981' : '#ef4444'; ?>; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                            <i class="fa fa-<?php echo $request['request_type'] == 'edit' ? 'edit' : 'trash-alt'; ?>"></i> <?php echo ucfirst($request['request_type']); ?>
                        </span>
                        <span style="color: #6b7280; font-size: 14px; font-weight: 600;">
                            #<?php echo $request['request_id']; ?>
                        </span>
                    </div>
                    <span class="request-badge badge-<?php echo $request['status']; ?>">
                        <?php echo $request['status']; ?>
                    </span>
                </div>

                <div class="request-details">
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('student'); ?></div>
                        <div class="detail-value"><?php echo $student ? $student->name : 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo $is_receipt ? get_phrase('receipt_number') : get_phrase('invoice_number'); ?></div>
                        <div class="detail-value">#<?php echo $is_receipt ? $request['receipt_code'] : $request['invoice_code']; ?></div>
                    </div>
                    <?php if($is_receipt): ?>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('amount'); ?></div>
                        <div class="detail-value">GHS <?php echo number_format($total_amount, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <div class="detail-label"><?php echo get_phrase('requested_by'); ?></div>
                        <div class="detail-value"><?php echo $requester ? $requester->name : 'N/A'; ?></div>
                    </div>
                </div>

                <?php 
                $reason = $is_receipt ? (isset($request['reason']) ? $request['reason'] : '') : (isset($request['request_reason']) ? $request['request_reason'] : '');
                ?>
                <div style="margin-top: 12px; display: flex; <?php echo $reason ? 'justify-content: space-between;' : 'justify-content: flex-end;'; ?> align-items: center; gap: 20px; flex-wrap: wrap;">
                    <?php if($reason): ?>
                    <div style="flex: 1; display: flex; align-items: center; gap: 12px; min-width: 250px; flex-wrap: wrap;">
                        <div class="detail-label" style="margin-bottom: 0; white-space: nowrap;"><?php echo get_phrase('reason'); ?>:</div>
                        <div style="padding: 8px 12px; background: #f9fafb; border-radius: 8px; flex: 1; min-width: 200px;">
                            <?php echo $reason; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; gap: 12px; align-items: center; flex-shrink: 0; flex-wrap: wrap;">
                        <button class="btn-action btn-view" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>, '<?php echo $is_receipt ? 'receipt' : 'invoice'; ?>')">
                            <i class="fa fa-eye"></i> <?php echo get_phrase('view_details'); ?>
                        </button>
                        <?php if($request['status'] == 'pending'): 
                            $user_level = $this->session->userdata('user_type');
                            $is_super_admin = ($user_level == 1);
                            if($is_super_admin): 
                        ?>
                        <button class="btn-action btn-approve" onclick="<?php echo $is_receipt ? 'approveReceiptRequest' : 'approveInvoiceRequest'; ?>(<?php echo $request['request_id']; ?>)">
                            <i class="fa fa-check"></i> <?php echo get_phrase('approve'); ?>
                        </button>
                        <button class="btn-action btn-reject" onclick="<?php echo $is_receipt ? 'rejectReceiptRequest' : 'declineInvoiceRequest'; ?>(<?php echo $request['request_id']; ?>)">
                            <i class="fa fa-times"></i> <?php echo get_phrase($is_receipt ? 'reject' : 'decline'); ?>
                        </button>
                        <?php endif; endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterRequests() {
    const status = $('#statusFilter').val();
    const type = $('#typeFilter').val();
    
    $('.request-card').each(function() {
        const cardStatus = $(this).data('status');
        const cardType = $(this).data('type');
        
        const statusMatch = status === 'all' || cardStatus === status;
        const typeMatch = type === 'all' || cardType === type;
        
        if(statusMatch && typeMatch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    updateBulkActions();
}

function updateBulkActions() {
    const checked = $('.request-checkbox:checked').length;
    $('#selectedCount').text(checked);
    if(checked > 0) {
        $('#bulkActionsBar').addClass('active');
    } else {
        $('#bulkActionsBar').removeClass('active');
    }
}

function selectAll() {
    $('.request-card:visible .request-checkbox').prop('checked', true);
    updateBulkActions();
}

function deselectAll() {
    $('.request-checkbox').prop('checked', false);
    updateBulkActions();
}

function bulkApprove() {
    if(window.processingBulkApprove) return;
    window.processingBulkApprove = true;
    
    const selected = [];
    $('.request-checkbox:checked').each(function() {
        const card = $(this).closest('.request-card');
        selected.push({
            id: card.data('request-id'),
            is_receipt: card.data('is-receipt') == 1
        });
    });
    
    if(selected.length === 0) {
        window.processingBulkApprove = false;
        return;
    }
    
    showConfirmModal(
        '<?php echo get_phrase("confirm_bulk_approval"); ?>',
        '<?php echo get_phrase("approve_selected_requests_confirm"); ?> (' + selected.length + ')',
        function() {
            if(window.bulkApproveCallbackExecuting) return;
            window.bulkApproveCallbackExecuting = true;
            
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/bulk_approve_modifications"); ?>',
                type: 'POST',
                data: { requests: JSON.stringify(selected) },
                dataType: 'json'
            }).done(function(response) {
                window.processingBulkApprove = false;
                window.bulkApproveCallbackExecuting = false;
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadPageContent(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                window.processingBulkApprove = false;
                window.bulkApproveCallbackExecuting = false;
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("approve"); ?>',
        'success'
    );
}

function bulkReject() {
    if(window.processingBulkReject) return;
    window.processingBulkReject = true;
    
    const selected = [];
    $('.request-checkbox:checked').each(function() {
        const card = $(this).closest('.request-card');
        selected.push({
            id: card.data('request-id'),
            is_receipt: card.data('is-receipt') == 1
        });
    });
    
    if(selected.length === 0) {
        window.processingBulkReject = false;
        return;
    }
    
    showConfirmModal(
        '<?php echo get_phrase("confirm_bulk_rejection"); ?>',
        '<?php echo get_phrase("reject_selected_requests_confirm"); ?> (' + selected.length + ')',
        function() {
            if(window.bulkRejectCallbackExecuting) return;
            window.bulkRejectCallbackExecuting = true;
            
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/bulk_reject_modifications"); ?>',
                type: 'POST',
                data: { requests: JSON.stringify(selected) },
                dataType: 'json'
            }).done(function(response) {
                window.processingBulkReject = false;
                window.bulkRejectCallbackExecuting = false;
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadPageContent(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                window.processingBulkReject = false;
                window.bulkRejectCallbackExecuting = false;
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("reject"); ?>',
        'danger'
    );
}

function viewRequestDetails(requestId, type) {
    if(type === 'receipt') {
        loadModalContent('detailsModal', 
            '<?php echo site_url("admin/receipt_modification_details/"); ?>' + requestId, 
            '<i class="fa fa-receipt"></i> Receipt Modification Details');
    } else {
        loadModalContent('detailsModal', 
            '<?php echo site_url("admin/invoice_modification_details/"); ?>' + requestId, 
            '<i class="fa fa-file-invoice"></i> Invoice Modification Details');
    }
}

function approveReceiptRequest(requestId) {
    // Prevent multiple simultaneous calls
    if(window.processingReceiptApproval) return;
    window.processingReceiptApproval = true;
    
    showConfirmModal(
        '<?php echo get_phrase("confirm_approval"); ?>',
        '<?php echo get_phrase("approve_modification_request_confirm"); ?>',
        function() {
            // Prevent callback from executing twice
            if(window.receiptCallbackExecuting) return;
            window.receiptCallbackExecuting = true;
            
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/approve_receipt_modification"); ?>',
                type: 'POST',
                data: { request_id: requestId },
                dataType: 'json'
            }).done(function(response) {
                window.processingReceiptApproval = false;
                window.receiptCallbackExecuting = false;
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadPageContent(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                window.processingReceiptApproval = false;
                window.receiptCallbackExecuting = false;
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("approve"); ?>',
        'success'
    );
}

function rejectReceiptRequest(requestId) {
    if(window.processingReceiptRejection) return;
    window.processingReceiptRejection = true;
    
    showConfirmModal(
        '<?php echo get_phrase("confirm_rejection"); ?>',
        '<div style="margin-bottom: 16px;"><?php echo get_phrase("enter_rejection_reason"); ?>:</div><textarea id="rejectionReason" class="form-control" rows="3" placeholder="<?php echo get_phrase("rejection_reason"); ?>" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;"></textarea>',
        function() {
            if(window.receiptRejectCallbackExecuting) return;
            window.receiptRejectCallbackExecuting = true;
            
            const reason = $('#rejectionReason').val().trim();
            if(!reason) {
                window.processingReceiptRejection = false;
                window.receiptRejectCallbackExecuting = false;
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
                window.processingReceiptRejection = false;
                window.receiptRejectCallbackExecuting = false;
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadPageContent(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                window.processingReceiptRejection = false;
                window.receiptRejectCallbackExecuting = false;
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("reject"); ?>',
        'danger'
    );
}
function approveInvoiceRequest(requestId) {
    // Prevent multiple simultaneous calls
    if(window.processingApproval) return;
    window.processingApproval = true;
    
    showConfirmModal(
        '<?php echo get_phrase("confirm_approval"); ?>',
        '<?php echo get_phrase("approve_invoice_modification_confirm"); ?>',
        function() {
            // Prevent callback from executing twice
            if(window.callbackExecuting) return;
            window.callbackExecuting = true;
            
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/review_invoice_modification/"); ?>' + requestId + '/approve',
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                window.processingApproval = false;
                window.callbackExecuting = false;
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadPageContent(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                window.processingApproval = false;
                window.callbackExecuting = false;
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("approve"); ?>',
        'success'
    );
}

function declineInvoiceRequest(requestId) {
    if(window.processingDecline) return;
    window.processingDecline = true;
    
    showConfirmModal(
        '<?php echo get_phrase("confirm_decline"); ?>',
        '<?php echo get_phrase("decline_invoice_modification_confirm"); ?>',
        function() {
            if(window.declineCallbackExecuting) return;
            window.declineCallbackExecuting = true;
            
            $('.close')[0].click();
            showAjaxModal_alert('<?php echo get_phrase("processing"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/review_invoice_modification/"); ?>' + requestId + '/decline',
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                window.processingDecline = false;
                window.declineCallbackExecuting = false;
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadPageContent(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                window.processingDecline = false;
                window.declineCallbackExecuting = false;
                showAjaxModal_alert('<?php echo get_phrase("error_occurred"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("decline"); ?>',
        'danger'
    );
}

$(document).ready(function() {
    filterRequests();
    
    // Event delegation for dynamically loaded content
    $(document).on('change', '.request-checkbox', updateBulkActions);
});

// AJAX reload function
function reloadPageContent() {
    const currentStatus = $('#statusFilter').val();
    const currentType = $('#typeFilter').val();
    
    $.ajax({
        url: window.location.href,
        type: 'GET',
        success: function(response) {
            const newContent = $(response).find('.requests-container').html();
            if(newContent) {
                $('.requests-container').html(newContent);
                $('#statusFilter').val(currentStatus);
                $('#typeFilter').val(currentType);
                filterRequests();
            } else {
                location.reload();
            }
        },
        error: function() {
            location.reload();
        }
    });
}
</script>
