<!-- Receipt Modification Status Checker -->
<script>
// Check receipt modification status and update buttons
function checkReceiptModificationStatus(receiptCode, buttonContainerId) {
    $.ajax({
        url: '<?php echo site_url("admin/get_receipt_modification_status"); ?>',
        type: 'POST',
        data: { receipt_code: receiptCode },
        dataType: 'json',
        success: function(response) {
            if(response.has_request) {
                updateReceiptButtons(buttonContainerId, response.status, receiptCode);
            }
        }
    });
}

// Update receipt buttons based on modification status
function updateReceiptButtons(containerId, status, receiptCode) {
    const container = $('#' + containerId);
    if(!container.length) return;
    
    // Find edit/delete buttons
    const editBtn = container.find('[onclick*="edit"], [onclick*="modify"]').first();
    const deleteBtn = container.find('[onclick*="delete"]').first();
    
    if(status === 'pending') {
        // Disable buttons and show pending status
        if(editBtn.length) {
            editBtn.prop('disabled', true)
                .css({'opacity': '0.6', 'cursor': 'not-allowed'})
                .html('<i class="fa fa-clock-o"></i> Pending Approval');
        }
        if(deleteBtn.length) {
            deleteBtn.prop('disabled', true)
                .css({'opacity': '0.6', 'cursor': 'not-allowed'});
        }
    } else if(status === 'rejected') {
        // Show rejected status
        if(editBtn.length) {
            editBtn.removeClass('btn-warning btn-info')
                .addClass('btn-danger')
                .html('<i class="fa fa-times-circle"></i> Request Rejected');
        }
        if(deleteBtn.length) {
            deleteBtn.prop('disabled', false)
                .css({'opacity': '1', 'cursor': 'pointer'});
        }
    }
}

// Initialize status checking for all receipts on page
function initReceiptStatusChecking() {
    $('[data-receipt-code]').each(function() {
        const receiptCode = $(this).data('receipt-code');
        const containerId = $(this).attr('id');
        if(receiptCode && containerId) {
            checkReceiptModificationStatus(receiptCode, containerId);
        }
    });
}

// Auto-initialize on page load
$(document).ready(function() {
    initReceiptStatusChecking();
});
</script>
