<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-shield"></i> Pending Discount Modification Requests
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table id="requestsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Student</th>
                                <th>Discount Type</th>
                                <th>Action</th>
                                <th>Requested By</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsBody">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <i class="fa fa-spinner fa-spin"></i> Loading requests...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-check-circle"></i> Approve Modification Request</h4>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <input type="hidden" id="approve_request_id" name="request_id">
                    
                    <div class="form-group">
                        <label>Approval Valid For (Hours)</label>
                        <input type="number" class="form-control" name="hours_valid" value="24" min="1" max="168" required>
                        <small class="text-muted">User must complete the action within this time frame</small>
                    </div>
                    
                    <div id="requestDetails" class="well well-sm"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitApproval()">
                    <i class="fa fa-check"></i> Approve Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modern-modal-header-danger">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-times-circle"></i> Reject Modification Request</h4>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <input type="hidden" id="reject_request_id" name="request_id">
                    
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="4" required 
                            placeholder="Explain why this request is being rejected..."></textarea>
                    </div>
                    
                    <div id="rejectRequestDetails" class="well well-sm"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitRejection()">
                    <i class="fa fa-times"></i> Reject Request
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadPendingRequests();
    
    // Refresh every 30 seconds
    setInterval(loadPendingRequests, 30000);
});

function loadPendingRequests() {
    $.ajax({
        url: '<?php echo site_url("discount_modification/get_pending_requests"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                displayRequests(response.data);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to load requests', 'error');
        }
    });
}

function displayRequests(requests) {
    var tbody = $('#requestsBody');
    tbody.empty();
    
    if(requests.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center text-muted">No pending requests</td></tr>');
        return;
    }
    
    requests.forEach(function(request) {
        var actionBadge = getActionBadge(request.action_type);
        var discountType = request.discount_table === 'student_discount_assignments' ? 'Pre-Assignment' : 'Invoice Discount';
        var requestDate = new Date(request.created_at * 1000).toLocaleString();
        
        var row = '<tr>' +
            '<td><strong>#' + request.request_id + '</strong></td>' +
            '<td>' + request.student_name + '<br><small class="text-muted">' + request.student_code + '</small></td>' +
            '<td>' + discountType + '</td>' +
            '<td>' + actionBadge + '</td>' +
            '<td>' + request.requester_name + '<br><small class="text-muted">' + request.requester_email + '</small></td>' +
            '<td><small>' + request.request_reason + '</small></td>' +
            '<td><small>' + requestDate + '</small></td>' +
            '<td>' +
                '<button class="btn btn-success btn-sm" onclick="showApproveModal(' + request.request_id + ', \'' + escapeHtml(JSON.stringify(request)) + '\')">' +
                    '<i class="fa fa-check"></i> Approve' +
                '</button> ' +
                '<button class="btn btn-danger btn-sm" onclick="showRejectModal(' + request.request_id + ', \'' + escapeHtml(JSON.stringify(request)) + '\')">' +
                    '<i class="fa fa-times"></i> Reject' +
                '</button>' +
            '</td>' +
        '</tr>';
        
        tbody.append(row);
    });
}

function getActionBadge(action) {
    var badges = {
        'edit': '<span class="label label-info"><i class="fa fa-edit"></i> Edit</span>',
        'delete': '<span class="label label-danger"><i class="fa fa-trash"></i> Delete</span>',
        'activate': '<span class="label label-success"><i class="fa fa-check"></i> Activate</span>',
        'deactivate': '<span class="label label-warning"><i class="fa fa-ban"></i> Deactivate</span>'
    };
    return badges[action] || action;
}

function showApproveModal(requestId, requestJson) {
    var request = JSON.parse(requestJson);
    
    $('#approve_request_id').val(requestId);
    
    var details = '<strong>Request Details:</strong><br>' +
        'Student: ' + request.student_name + ' (' + request.student_code + ')<br>' +
        'Action: ' + request.action_type.toUpperCase() + '<br>' +
        'Requested by: ' + request.requester_name + '<br>' +
        'Reason: ' + request.request_reason;
    
    $('#requestDetails').html(details);
    $('#approveModal').modal('show');
}

function showRejectModal(requestId, requestJson) {
    var request = JSON.parse(requestJson);
    
    $('#reject_request_id').val(requestId);
    
    var details = '<strong>Request Details:</strong><br>' +
        'Student: ' + request.student_name + ' (' + request.student_code + ')<br>' +
        'Action: ' + request.action_type.toUpperCase() + '<br>' +
        'Requested by: ' + request.requester_name + '<br>' +
        'Reason: ' + request.request_reason;
    
    $('#rejectRequestDetails').html(details);
    $('#rejectModal').modal('show');
}

function submitApproval() {
    var formData = $('#approveForm').serialize();
    
    showAjaxModal_alert('Processing approval...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("discount_modification/approve_request"); ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                $('#approveModal').modal('hide');
                setTimeout(function() {
                    loadPendingRequests();
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to approve request', 'error');
        }
    });
}

function submitRejection() {
    if(!$('#rejectForm textarea[name="reason"]').val()) {
        showAjaxModal_alert('Please provide a rejection reason', 'warning');
        return;
    }
    
    var formData = $('#rejectForm').serialize();
    
    showAjaxModal_alert('Processing rejection...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("discount_modification/reject_request"); ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                $('#rejectModal').modal('hide');
                setTimeout(function() {
                    loadPendingRequests();
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to reject request', 'error');
        }
    });
}

function escapeHtml(text) {
    return text.replace(/'/g, "\\'");
}
</script>

<style>
.modern-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modern-modal-header-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.modern-modal-header .close,
.modern-modal-header-danger .close {
    color: white;
    opacity: 0.8;
}

.modern-modal-header .close:hover,
.modern-modal-header-danger .close:hover {
    opacity: 1;
}
</style>
