<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" style="border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <div class="panel-heading" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 20px;">
                <h3 style="margin: 0; color: white; font-weight: 600;">
                    <i class="fa fa-clock"></i> Pending Discount Approvals
                </h3>
                <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 13px;">
                    Review and approve/reject discount requests
                </p>
            </div>

            <div class="panel-body" style="padding: 25px;">
                <div class="table-responsive">
                    <table class="table table-hover" id="pending-discounts-table">
                        <thead style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                            <tr>
                                <th>Student</th>
                                <th>Discount Type</th>
                                <th>Category</th>
                                <th>Method</th>
                                <th>Value</th>
                                <th>Period</th>
                                <th>Requested By</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-times-circle"></i> Reject Discount</h4>
            </div>
            <form id="rejectForm">
                <input type="hidden" name="discount_id" id="reject_discount_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadPendingDiscounts();
});

function loadPendingDiscounts() {
    $.get('<?php echo site_url("discount/get_pending_discounts"); ?>', function(data) {
        let html = '';
        if(data && data.length > 0) {
            data.forEach(item => {
                const categoryBadge = item.discount_category === 'invoice' ? 
                    '<span class="badge" style="background: #667eea;">Invoice</span>' : 
                    '<span class="badge" style="background: #764ba2;">Daily Fees</span>';
                
                const method = item.discount_method === 'percentage' ? 'Percentage' : 'Fixed Amount';
                const value = item.discount_method === 'percentage' ? item.discount_value + '%' : 'GH₵ ' + item.discount_value;
                
                html += `<tr>
                    <td>${item.student_name} (${item.student_code})</td>
                    <td>${item.icon || ''} ${item.discount_type_name}</td>
                    <td>${categoryBadge}</td>
                    <td>${method}</td>
                    <td><strong>${value}</strong></td>
                    <td>${item.year} - Term ${item.term}</td>
                    <td>${item.created_by_name}</td>
                    <td>${item.reason || '-'}</td>
                    <td>
                        <button class="btn btn-xs btn-success" onclick="approveDiscount(${item.discount_id})">
                            <i class="fa fa-check"></i> Approve
                        </button>
                        <button class="btn btn-xs btn-danger" onclick="showRejectModal(${item.discount_id})">
                            <i class="fa fa-times"></i> Reject
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="9" class="text-center">No pending approvals</td></tr>';
        }
        $('#pending-discounts-table tbody').html(html);
    }, 'json');
}

function approveDiscount(discount_id) {
    showConfirmModal(
        'Approve Discount',
        'Are you sure you want to approve this discount?',
        function() {
            showAjaxModal_alert('Approving...', 'loading');
            $.post('<?php echo site_url("discount/approve"); ?>', {discount_id: discount_id}, function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }, 'json');
        },
        'Approve',
        'success'
    );
}

function showRejectModal(discount_id) {
    $('#reject_discount_id').val(discount_id);
    $('#rejectModal').modal('show');
}

$('#rejectForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('Rejecting...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("discount/reject"); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    });
});
</script>
