<style>
.approval-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.approval-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6; }
.approval-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.approval-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
.info-item { padding: 10px; background: #f9fafb; border-radius: 8px; }
.info-label { font-size: 12px; color: #6b7280; margin-bottom: 5px; }
.info-value { font-size: 14px; font-weight: 600; color: #1f2937; }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-unlock-alt"></i> <?php echo get_phrase('unlock_approval_requests'); ?></h3>
            </div>
            <div class="panel-body">
                <ul class="nav nav-tabs" style="margin-bottom: 20px;">
                    <li class="active"><a href="#pending" data-toggle="tab"><i class="fa fa-clock-o"></i> Pending</a></li>
                    <li><a href="#approved" data-toggle="tab"><i class="fa fa-check"></i> Approved</a></li>
                    <li><a href="#rejected" data-toggle="tab"><i class="fa fa-times"></i> Rejected</a></li>
                </ul>

                <div class="tab-content">
                    <!-- Pending Requests -->
                    <div class="tab-pane active" id="pending">
                        <?php
                        $pending = $this->db->where('status', 'pending')->order_by('created_at', 'DESC')->get('unlock_requests')->result_array();
                        if (empty($pending)): ?>
                            <div class="alert alert-info"><i class="fa fa-info-circle"></i> No pending requests</div>
                        <?php else: foreach ($pending as $request): 
                            $record = $this->db->get_where($request['record_type'], [$request['record_type'].'_id' => $request['record_id']])->row_array();
                            $requester = $this->db->get_where('admin', ['admin_id' => $request['requested_by']])->row();
                        ?>
                            <div class="approval-card">
                                <div class="approval-header">
                                    <div>
                                        <h4 style="margin: 0;"><i class="fa fa-lock"></i> <?php echo ucfirst($request['record_type']); ?> #<?php echo $request['record_id']; ?></h4>
                                        <small>Requested by: <?php echo $requester->name; ?></small>
                                    </div>
                                    <span class="approval-badge badge-pending">PENDING</span>
                                </div>
                                <div class="approval-info">
                                    <div class="info-item">
                                        <div class="info-label">Action</div>
                                        <div class="info-value"><?php echo strtoupper($request['action_type']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Requested</div>
                                        <div class="info-value"><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Reason</div>
                                        <div class="info-value"><?php echo $request['reason']; ?></div>
                                    </div>
                                </div>
                                <div style="margin-top: 15px; display: flex; gap: 10px;">
                                    <button onclick="approveRequest(<?php echo $request['id']; ?>)" class="btn btn-success"><i class="fa fa-check"></i> Approve</button>
                                    <button onclick="rejectRequest(<?php echo $request['id']; ?>)" class="btn btn-danger"><i class="fa fa-times"></i> Reject</button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Approved Requests -->
                    <div class="tab-pane" id="approved">
                        <?php
                        $approved = $this->db->where('status', 'approved')->order_by('handled_at', 'DESC')->limit(20)->get('unlock_requests')->result_array();
                        if (empty($approved)): ?>
                            <div class="alert alert-info"><i class="fa fa-info-circle"></i> No approved requests</div>
                        <?php else: foreach ($approved as $request): 
                            $requester = $this->db->get_where('admin', ['admin_id' => $request['requested_by']])->row();
                            $handler = $this->db->get_where('admin', ['admin_id' => $request['handled_by']])->row();
                        ?>
                            <div class="approval-card">
                                <div class="approval-header">
                                    <div>
                                        <h4 style="margin: 0;"><?php echo ucfirst($request['record_type']); ?> #<?php echo $request['record_id']; ?></h4>
                                        <small>By: <?php echo $requester->name; ?> | Approved by: <?php echo $handler->name; ?></small>
                                    </div>
                                    <span class="approval-badge badge-approved">APPROVED</span>
                                </div>
                                <p><strong>Reason:</strong> <?php echo $request['reason']; ?></p>
                                <small class="text-muted">Approved: <?php echo date('M d, Y H:i', strtotime($request['handled_at'])); ?></small>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Rejected Requests -->
                    <div class="tab-pane" id="rejected">
                        <?php
                        $rejected = $this->db->where('status', 'rejected')->order_by('handled_at', 'DESC')->limit(20)->get('unlock_requests')->result_array();
                        if (empty($rejected)): ?>
                            <div class="alert alert-info"><i class="fa fa-info-circle"></i> No rejected requests</div>
                        <?php else: foreach ($rejected as $request): 
                            $requester = $this->db->get_where('admin', ['admin_id' => $request['requested_by']])->row();
                            $handler = $this->db->get_where('admin', ['admin_id' => $request['handled_by']])->row();
                        ?>
                            <div class="approval-card">
                                <div class="approval-header">
                                    <div>
                                        <h4 style="margin: 0;"><?php echo ucfirst($request['record_type']); ?> #<?php echo $request['record_id']; ?></h4>
                                        <small>By: <?php echo $requester->name; ?> | Rejected by: <?php echo $handler->name; ?></small>
                                    </div>
                                    <span class="approval-badge badge-rejected">REJECTED</span>
                                </div>
                                <p><strong>Reason:</strong> <?php echo $request['reason']; ?></p>
                                <?php if ($request['handler_notes']): ?>
                                    <p><strong>Notes:</strong> <?php echo $request['handler_notes']; ?></p>
                                <?php endif; ?>
                                <small class="text-muted">Rejected: <?php echo date('M d, Y H:i', strtotime($request['handled_at'])); ?></small>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveRequest(id) {
    showConfirmModal(
        'Approve Unlock Request',
        'Are you sure you want to approve this unlock request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/handle_unlock_request/approve/'); ?>' + id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Approve',
        'success'
    );
}

function rejectRequest(id) {
    showConfirmModal(
        'Reject Unlock Request',
        'Are you sure you want to reject this unlock request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/handle_unlock_request/reject/'); ?>' + id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Reject',
        'danger'
    );
}
</script>
