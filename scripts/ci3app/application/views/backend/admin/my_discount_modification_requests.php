<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-list"></i> My Discount Modification Requests
                </div>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Note:</strong> Once your request is approved, you have a limited time to complete the action. 
                    After the time expires, you'll need to request approval again.
                </div>
                
                <div class="table-responsive">
                    <table id="myRequestsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Student</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Requested On</th>
                                <th>Approved By</th>
                                <th>Time Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="myRequestsBody">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <i class="fa fa-spinner fa-spin"></i> Loading your requests...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-info-circle"></i> Request Details</h4>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadMyRequests();
    
    // Refresh every 30 seconds
    setInterval(loadMyRequests, 30000);
});

function loadMyRequests() {
    $.ajax({
        url: '<?php echo site_url("discount_modification/my_requests"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                displayMyRequests(response.data);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to load requests', 'error');
        }
    });
}

function displayMyRequests(requests) {
    var tbody = $('#myRequestsBody');
    tbody.empty();
    
    if(requests.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center text-muted">You have no modification requests</td></tr>');
        return;
    }
    
    requests.forEach(function(request) {
        var statusBadge = getStatusBadge(request.status);
        var actionBadge = getActionBadge(request.action_type);
        var requestDate = new Date(request.created_at * 1000).toLocaleString();
        var approverName = request.approver_name || 'N/A';
        var timeRemaining = getTimeRemaining(request);
        var actions = getActionButtons(request);
        
        var studentInfo = 'N/A';
        if(request.discount_details) {
            studentInfo = request.discount_details.student_name || 'Unknown';
        }
        
        var row = '<tr>' +
            '<td><strong>#' + request.request_id + '</strong></td>' +
            '<td>' + studentInfo + '</td>' +
            '<td>' + actionBadge + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td><small>' + requestDate + '</small></td>' +
            '<td>' + approverName + '</td>' +
            '<td>' + timeRemaining + '</td>' +
            '<td>' + actions + '</td>' +
        '</tr>';
        
        tbody.append(row);
    });
}

function getStatusBadge(status) {
    var badges = {
        'pending': '<span class="label label-warning"><i class="fa fa-clock-o"></i> Pending</span>',
        'approved': '<span class="label label-success"><i class="fa fa-check"></i> Approved</span>',
        'rejected': '<span class="label label-danger"><i class="fa fa-times"></i> Rejected</span>',
        'expired': '<span class="label label-default"><i class="fa fa-hourglass-end"></i> Expired</span>'
    };
    return badges[status] || status;
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

function getTimeRemaining(request) {
    if(request.status !== 'approved' || !request.approval_expires_at) {
        return 'N/A';
    }
    
    if(request.executed_at) {
        return '<span class="text-success"><i class="fa fa-check"></i> Executed</span>';
    }
    
    var now = Math.floor(Date.now() / 1000);
    var remaining = request.approval_expires_at - now;
    
    if(remaining <= 0) {
        return '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Expired</span>';
    }
    
    var hours = Math.floor(remaining / 3600);
    var minutes = Math.floor((remaining % 3600) / 60);
    
    var color = hours < 2 ? 'text-danger' : (hours < 6 ? 'text-warning' : 'text-success');
    
    return '<span class="' + color + '"><i class="fa fa-clock-o"></i> ' + hours + 'h ' + minutes + 'm</span>';
}

function getActionButtons(request) {
    var buttons = '<button class="btn btn-info btn-xs" onclick="viewDetails(' + request.request_id + ')">' +
        '<i class="fa fa-eye"></i> View' +
        '</button>';
    
    if(request.status === 'approved' && !request.executed_at && request.time_remaining > 0) {
        buttons += ' <button class="btn btn-success btn-xs" onclick="executeAction(' + request.request_id + ', ' + request.discount_id + ', \'' + request.discount_table + '\', \'' + request.action_type + '\')">' +
            '<i class="fa fa-play"></i> Execute Now' +
            '</button>';
    }
    
    return buttons;
}

function viewDetails(requestId) {
    showAjaxModal_alert('Loading details...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("discount_modification/my_requests"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                var request = response.data.find(r => r.request_id == requestId);
                if(request) {
                    displayRequestDetails(request);
                    $('.close').click(); // Close loading modal
                    $('#detailsModal').modal('show');
                }
            }
        }
    });
}

function displayRequestDetails(request) {
    var html = '<div class="row">' +
        '<div class="col-md-6">' +
            '<h5><strong>Request Information</strong></h5>' +
            '<table class="table table-condensed">' +
                '<tr><td><strong>Request ID:</strong></td><td>#' + request.request_id + '</td></tr>' +
                '<tr><td><strong>Action:</strong></td><td>' + request.action_type.toUpperCase() + '</td></tr>' +
                '<tr><td><strong>Status:</strong></td><td>' + getStatusBadge(request.status) + '</td></tr>' +
                '<tr><td><strong>Requested On:</strong></td><td>' + new Date(request.created_at * 1000).toLocaleString() + '</td></tr>' +
            '</table>' +
        '</div>' +
        '<div class="col-md-6">' +
            '<h5><strong>Approval Information</strong></h5>' +
            '<table class="table table-condensed">' +
                '<tr><td><strong>Approved By:</strong></td><td>' + (request.approver_name || 'N/A') + '</td></tr>' +
                '<tr><td><strong>Approved At:</strong></td><td>' + (request.approved_at ? new Date(request.approved_at * 1000).toLocaleString() : 'N/A') + '</td></tr>' +
                '<tr><td><strong>Expires At:</strong></td><td>' + (request.approval_expires_at ? new Date(request.approval_expires_at * 1000).toLocaleString() : 'N/A') + '</td></tr>' +
                '<tr><td><strong>Executed At:</strong></td><td>' + (request.executed_at ? new Date(request.executed_at * 1000).toLocaleString() : 'Not yet executed') + '</td></tr>' +
            '</table>' +
        '</div>' +
    '</div>' +
    '<div class="row">' +
        '<div class="col-md-12">' +
            '<h5><strong>Request Reason</strong></h5>' +
            '<div class="well well-sm">' + request.request_reason + '</div>';
    
    if(request.rejection_reason) {
        html += '<h5><strong>Rejection Reason</strong></h5>' +
            '<div class="alert alert-danger">' + request.rejection_reason + '</div>';
    }
    
    html += '</div></div>';
    
    $('#detailsContent').html(html);
}

function executeAction(requestId, discountId, table, actionType) {
    showConfirmModal(
        'Execute Action',
        'Are you sure you want to execute this ' + actionType + ' action? This cannot be undone.',
        function() {
            showAjaxModal_alert('Executing action...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("discount_modification/execute_modification"); ?>',
                type: 'POST',
                data: {
                    request_id: requestId,
                    discount_id: discountId,
                    table: table,
                    action_type: actionType
                },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                        setTimeout(function() {
                            loadMyRequests();
                        }, 2000);
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('Failed to execute action', 'error');
                }
            });
        },
        'Execute',
        'danger'
    );
}
</script>

<style>
.modern-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modern-modal-header .close {
    color: white;
    opacity: 0.8;
}

.modern-modal-header .close:hover {
    opacity: 1;
}
</style>
