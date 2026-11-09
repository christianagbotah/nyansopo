<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-shield"></i> <?php echo get_phrase('approval_requests'); ?>
                </div>
            </div>
            <div class="panel-body">
                
                <!-- Approval Requests Table -->
                <div class="table-responsive">
                    <table id="approval_requests_table" class="table table-striped table-bordered" style="width:100%">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th><?php echo get_phrase('id'); ?></th>
                                <th><?php echo get_phrase('type'); ?></th>
                                <th><?php echo get_phrase('record'); ?></th>
                                <th><?php echo get_phrase('student'); ?></th>
                                <th><?php echo get_phrase('requested_by'); ?></th>
                                <th><?php echo get_phrase('requested_at'); ?></th>
                                <th><?php echo get_phrase('reason'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="requests_tbody">
                            <tr>
                                <td colspan="9" class="text-center">
                                    <i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase('loading'); ?>...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-check-circle"></i> <?php echo get_phrase('handle_approval_request'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approval_request_id">
                
                <div class="form-group">
                    <label><?php echo get_phrase('request_details'); ?></label>
                    <div id="request_details" class="well"></div>
                </div>
                
                <div class="form-group">
                    <label><?php echo get_phrase('action'); ?> <span class="text-danger">*</span></label>
                    <select id="approval_action" class="form-control" required>
                        <option value="">-- <?php echo get_phrase('select_action'); ?> --</option>
                        <option value="approve" class="text-success"><?php echo get_phrase('approve'); ?></option>
                        <option value="reject" class="text-danger"><?php echo get_phrase('reject'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?php echo get_phrase('notes'); ?></label>
                    <textarea id="approval_notes" class="form-control" rows="3" placeholder="<?php echo get_phrase('enter_notes_optional'); ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="submitApproval()">
                    <i class="fa fa-check"></i> <?php echo get_phrase('submit'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadApprovalRequests();
    
    // Refresh every 30 seconds
    setInterval(loadApprovalRequests, 30000);
});

function loadApprovalRequests() {
    $.ajax({
        url: '<?php echo site_url('approval_requests/get_pending_requests'); ?>',
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
            showAjaxModal_alert('<?php echo get_phrase('error_loading_requests'); ?>', 'error');
        }
    });
}

function displayRequests(requests) {
    var tbody = $('#requests_tbody');
    tbody.empty();
    
    if(requests.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-inbox"></i> <?php echo get_phrase('no_pending_requests'); ?></td></tr>');
        return;
    }
    
    requests.forEach(function(req) {
        var typeLabel = getTypeLabel(req.request_type);
        var statusBadge = getStatusBadge(req.status);
        var actionBtn = req.status === 'pending' ? 
            '<button class="btn btn-sm btn-primary" onclick="openApprovalModal(' + req.id + ', \'' + req.request_type + '\', \'' + req.record_code + '\', \'' + req.student_name + '\', \'' + req.requested_by_name + '\', \'' + req.reason + '\')">' +
            '<i class="fa fa-gavel"></i> <?php echo get_phrase('handle'); ?></button>' :
            '<span class="text-muted"><?php echo get_phrase('handled'); ?></span>';
        
        var row = '<tr>' +
            '<td>' + req.id + '</td>' +
            '<td>' + typeLabel + '</td>' +
            '<td><strong>' + req.record_code + '</strong></td>' +
            '<td>' + req.student_name + '</td>' +
            '<td>' + req.requested_by_name + '</td>' +
            '<td>' + formatDateTime(req.requested_at) + '</td>' +
            '<td><small>' + req.reason + '</small></td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' + actionBtn + '</td>' +
            '</tr>';
        
        tbody.append(row);
    });
}

function getTypeLabel(type) {
    var labels = {
        'invoice_edit': '<span class="label label-info"><i class="fa fa-edit"></i> <?php echo get_phrase('invoice_edit'); ?></span>',
        'invoice_delete': '<span class="label label-danger"><i class="fa fa-trash"></i> <?php echo get_phrase('invoice_delete'); ?></span>',
        'payment_edit': '<span class="label label-warning"><i class="fa fa-edit"></i> <?php echo get_phrase('payment_edit'); ?></span>',
        'payment_delete': '<span class="label label-danger"><i class="fa fa-trash"></i> <?php echo get_phrase('payment_delete'); ?></span>'
    };
    return labels[type] || type;
}

function getStatusBadge(status) {
    var badges = {
        'pending': '<span class="label label-warning"><i class="fa fa-clock-o"></i> <?php echo get_phrase('pending'); ?></span>',
        'approved': '<span class="label label-success"><i class="fa fa-check"></i> <?php echo get_phrase('approved'); ?></span>',
        'rejected': '<span class="label label-danger"><i class="fa fa-times"></i> <?php echo get_phrase('rejected'); ?></span>'
    };
    return badges[status] || status;
}

function formatDateTime(datetime) {
    var date = new Date(datetime);
    return date.toLocaleString();
}

function openApprovalModal(id, type, recordCode, studentName, requestedBy, reason) {
    $('#approval_request_id').val(id);
    
    var details = '<table class="table table-condensed">' +
        '<tr><th><?php echo get_phrase('type'); ?>:</th><td>' + getTypeLabel(type) + '</td></tr>' +
        '<tr><th><?php echo get_phrase('record'); ?>:</th><td><strong>' + recordCode + '</strong></td></tr>' +
        '<tr><th><?php echo get_phrase('student'); ?>:</th><td>' + studentName + '</td></tr>' +
        '<tr><th><?php echo get_phrase('requested_by'); ?>:</th><td>' + requestedBy + '</td></tr>' +
        '<tr><th><?php echo get_phrase('reason'); ?>:</th><td>' + reason + '</td></tr>' +
        '</table>';
    
    $('#request_details').html(details);
    $('#approval_action').val('');
    $('#approval_notes').val('');
    
    $('#approvalModal').modal('show');
}

function submitApproval() {
    var requestId = $('#approval_request_id').val();
    var action = $('#approval_action').val();
    var notes = $('#approval_notes').val();
    
    if(!action) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_action'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('processing'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('approval_requests/handle_request'); ?>',
        type: 'POST',
        data: {
            request_id: requestId,
            action: action,
            notes: notes
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                $('#approvalModal').modal('hide');
                setTimeout(function() {
                    loadApprovalRequests();
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_processing_request'); ?>', 'error');
        }
    });
}
</script>

<style>
.panel-heading {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.label {
    font-size: 12px;
    padding: 5px 10px;
}

.well {
    background-color: #f9f9f9;
    border: 1px solid #e3e3e3;
}
</style>
