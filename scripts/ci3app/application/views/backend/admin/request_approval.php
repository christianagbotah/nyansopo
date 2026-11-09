<?php
    $allRequests = $this->crud_model->getAllRequests();
    $user_level = $this->session->userdata('user_type');
    
    // Count statistics
    $pending_count = 0;
    $approved_count = 0;
    $declined_count = 0;
    
    foreach($allRequests as $request) {
        if($request['approval_status'] == 'Pending') $pending_count++;
        elseif($request['approval_status'] == 'Approved') $approved_count++;
        elseif($request['approval_status'] == 'Declined') $declined_count++;
    }
?>

<style>
.modern-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.modern-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.stat-card {
    padding: 24px;
    border-left: 4px solid;
}
.stat-card.pending { border-color: #f59e0b; }
.stat-card.approved { border-color: #10b981; }
.stat-card.declined { border-color: #ef4444; }
.badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.badge.pending { background: #fef3c7; color: #92400e; }
.badge.approved { background: #d1fae5; color: #065f46; }
.badge.declined { background: #fee2e2; color: #991b1b; }
.action-btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.action-btn.approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}
.action-btn.approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}
.action-btn.decline {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}
.action-btn.decline:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}
.request-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid #e5e7eb;
    transition: all 0.3s;
}
.request-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}
.filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
}
.filter-tab {
    padding: 12px 24px;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
}
.filter-tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}
.filter-tab:hover {
    color: #667eea;
}
</style>

<div class="row" style="margin-top: 20px;">
    <!-- Statistics Cards -->
    <div class="col-md-4">
        <div class="modern-card stat-card pending">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="color: #6b7280; margin: 0; font-size: 14px;">PENDING</p>
                    <h2 style="margin: 8px 0 0 0; color: #f59e0b; font-weight: 700;"><?= $pending_count ?></h2>
                </div>
                <div style="width: 60px; height: 60px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-clock" style="font-size: 28px; color: #f59e0b;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="modern-card stat-card approved">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="color: #6b7280; margin: 0; font-size: 14px;">APPROVED</p>
                    <h2 style="margin: 8px 0 0 0; color: #10b981; font-weight: 700;"><?= $approved_count ?></h2>
                </div>
                <div style="width: 60px; height: 60px; background: #d1fae5; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-check-circle" style="font-size: 28px; color: #10b981;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="modern-card stat-card declined">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="color: #6b7280; margin: 0; font-size: 14px;">DECLINED</p>
                    <h2 style="margin: 8px 0 0 0; color: #ef4444; font-weight: 700;"><?= $declined_count ?></h2>
                </div>
                <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-times-circle" style="font-size: 28px; color: #ef4444;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 30px;">
    <div class="col-md-12">
        <div class="modern-card" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="margin: 0; color: #1f2937; font-weight: 700;">
                    <i class="fa fa-list-alt"></i> Approval Requests
                </h3>
                <div style="display: flex; gap: 12px;">
                    <input type="text" id="searchInput" placeholder="Search requests..." 
                           style="padding: 10px 16px; border: 1px solid #e5e7eb; border-radius: 8px; width: 300px;">
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All (<?= count($allRequests) ?>)</button>
                <button class="filter-tab" data-filter="Pending">Pending (<?= $pending_count ?>)</button>
                <button class="filter-tab" data-filter="Approved">Approved (<?= $approved_count ?>)</button>
                <button class="filter-tab" data-filter="Declined">Declined (<?= $declined_count ?>)</button>
            </div>
            
            <!-- Requests List -->
            <div id="requestsList">
                <?php if(empty($allRequests)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                        <i class="fa fa-inbox" style="font-size: 64px; margin-bottom: 16px;"></i>
                        <p style="font-size: 18px; margin: 0;">No requests found</p>
                    </div>
                <?php else: ?>
                    <?php foreach($allRequests as $request): 
                        $request_issuer = $this->crud_model->getAdminInfoById($request['request_issuer_id']);
                        $issuer_name = $request_issuer ? $request_issuer->name : 'Unknown';
                        
                        $statusClass = strtolower($request['approval_status']);
                        $statusIcon = $request['approval_status'] == 'Pending' ? 'clock' : 
                                     ($request['approval_status'] == 'Approved' ? 'check-circle' : 'times-circle');
                    ?>
                    <div class="request-card" data-status="<?= $request['approval_status'] ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div style="display: flex; align-items: start; gap: 16px;">
                                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fa fa-file-invoice" style="color: white; font-size: 20px;"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                            <h4 style="margin: 0; color: #1f2937; font-weight: 600;">
                                                Request #<?= $request['request_id'] ?>
                                            </h4>
                                            <span class="badge <?= $statusClass ?>">
                                                <i class="fa fa-<?= $statusIcon ?>"></i> <?= $request['approval_status'] ?>
                                            </span>
                                        </div>
                                        <p style="color: #6b7280; margin: 0 0 8px 0; font-size: 14px;">
                                            <i class="fa fa-user"></i> <strong>Requested by:</strong> <?= $issuer_name ?>
                                        </p>
                                        <p style="color: #374151; margin: 0; font-size: 15px;">
                                            <?= $request['request_description'] ?>
                                        </p>
                                        <p style="color: #9ca3af; margin: 8px 0 0 0; font-size: 13px;">
                                            <i class="fa fa-calendar"></i> <?= date('F j, Y g:i A', strtotime($request['request_created_timestamp'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                                <?php if($request['approval_status'] == 'Pending' && $user_level == 1): ?>
                                    <button class="action-btn approve" onclick="handleRequest(<?= $request['request_id'] ?>, 'Approved')">
                                        <i class="fa fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn decline" onclick="handleRequest(<?= $request['request_id'] ?>, 'Declined')">
                                        <i class="fa fa-times"></i> Decline
                                    </button>
                                <?php elseif($request['approval_status'] != 'Pending'): ?>
                                    <div style="text-align: right;">
                                        <p style="margin: 0; color: #6b7280; font-size: 13px;">
                                            <?= $request['approval_status'] ?> on
                                        </p>
                                        <p style="margin: 0; color: #374151; font-size: 14px; font-weight: 600;">
                                            <?= date('M j, Y', strtotime($request['approval_timestamp'])) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Filter functionality
    $('.filter-tab').click(function() {
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');
        
        const filter = $(this).data('filter');
        
        if(filter === 'all') {
            $('.request-card').show();
        } else {
            $('.request-card').hide();
            $('.request-card[data-status="' + filter + '"]').show();
        }
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('.request-card').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});

function handleRequest(requestId, status) {
    const actionText = status === 'Approved' ? 'approve' : 'decline';
    const actionColor = status === 'Approved' ? 'success' : 'danger';
    
    showConfirmModal(
        'Confirm ' + status,
        'Are you sure you want to ' + actionText + ' this request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            
            $.ajax({
                url: '<?= site_url('admin/manageRequestApproval/manage/') ?>' + requestId + '/' + status,
                type: 'POST',
                dataType: 'json'
            })
            .done(function(data) {
                if(data.status === 'success') {
                    showAjaxModal_alert(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(data.message, 'error');
                }
            })
            .fail(function() {
                showAjaxModal_alert('An error occurred. Please try again.', 'error');
            });
        },
        status,
        actionColor
    );
}
</script>
