<div class="row">
    <div class="col-md-12">
        <!-- Modern Page Header with Gradient -->
        <div class="panel" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); margin-bottom: 30px;">
            <div class="panel-body" style="padding: 30px;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 style="margin: 0; color: white; font-weight: 700; font-size: 32px;">
                            <i class="fa fa-credit-card mr-3"></i><?php echo get_phrase('student_credits'); ?>
                        </h2>
                        <p style="margin: 10px 0 0 0; opacity: 0.95; font-size: 16px; color: rgba(255,255,255,0.9);">
                            Manage student overpayments, prepaid balances and credit transfers
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-light btn-lg" onclick="showCreditTransferModal()" style="margin-right: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <i class="fa fa-exchange mr-2"></i>Transfer Credit
                        </button>
                        <button class="btn btn-warning btn-lg" onclick="refreshStatistics()" style="box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <i class="fa fa-sync mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Credit Statistics Cards with Stacked Layout -->
<div class="row" style="margin-bottom: 30px;">
    <div class="col-lg-3 col-md-6" style="margin-bottom: 20px;">
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); transition: transform 0.3s ease; height: 180px; display: flex; flex-direction: column; justify-content: space-between;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.5px;">ACTIVE CREDITS</div>
                <div style="color: white; font-size: 36px; font-weight: 700; line-height: 1; margin-bottom: 8px;"><sup style="font-size: 16px; vertical-align: super; margin-right: 2px;">GH₵</sup><?php echo number_format($credit_statistics['total_active_credits'], 0); ?></div>
                <div style="color: rgba(255,255,255,0.75); font-size: 12px;">Available balance</div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-money-bill-wave" style="font-size: 22px; color: white;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6" style="margin-bottom: 20px;">
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); transition: transform 0.3s ease; height: 180px; display: flex; flex-direction: column; justify-content: space-between;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.5px;">STUDENTS</div>
                <div style="color: white; font-size: 36px; font-weight: 700; line-height: 1; margin-bottom: 8px;"><?php echo $credit_statistics['students_with_credits']; ?></div>
                <div style="color: rgba(255,255,255,0.75); font-size: 12px;">With credit balances</div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-users" style="font-size: 22px; color: white;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6" style="margin-bottom: 20px;">
        <div style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); transition: transform 0.3s ease; height: 180px; display: flex; flex-direction: column; justify-content: space-between;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.5px;">CREDITS APPLIED</div>
                <div style="color: white; font-size: 36px; font-weight: 700; line-height: 1; margin-bottom: 8px;"><sup style="font-size: 16px; vertical-align: super; margin-right: 2px;">GH₵</sup><?php echo number_format($credit_statistics['total_credits_applied'], 0); ?></div>
                <div style="color: rgba(255,255,255,0.75); font-size: 12px;">Used on invoices</div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-check-circle" style="font-size: 22px; color: white;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6" style="margin-bottom: 20px;">
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); transition: transform 0.3s ease; height: 180px; display: flex; flex-direction: column; justify-content: space-between;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.5px;">UTILIZATION RATE</div>
                <div style="color: white; font-size: 36px; font-weight: 700; line-height: 1; margin-bottom: 8px;"><?php echo number_format($credit_statistics['utilization_rate'], 1); ?>%</div>
                <div style="color: rgba(255,255,255,0.75); font-size: 12px;">Credits usage efficiency</div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-chart-line" style="font-size: 22px; color: white;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Students with Credits Table - Modern Design -->
<div class="row">
    <div class="col-md-12">
        <div class="panel" style="border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); border: none;">
            <div class="panel-heading" style="background: white; border-bottom: 3px solid #667eea; padding: 20px; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h3 style="margin: 0; color: #667eea; font-weight: 700;">
                    <i class="fa fa-list mr-2"></i>Students with Credit Balances
                </h3>
                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">View and manage all students with prepaid credit balances</p>
            </div>
            <div class="panel-body" style="padding: 25px;">
                <div class="table-responsive">
                    <table class="table table-hover" id="creditsTable" style="border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <th style="padding: 15px; font-weight: 600;">Student Code</th>
                                <th style="padding: 15px; font-weight: 600;">Student Name</th>
                                <th style="padding: 15px; font-weight: 600;">Class</th>
                                <th style="padding: 15px; font-weight: 600;">Credit Balance</th>
                                <th style="padding: 15px; font-weight: 600;">Credit Records</th>
                                <th style="padding: 15px; font-weight: 600;">Last Credit Date</th>
                                <th style="padding: 15px; font-weight: 600; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($students_with_credits)): ?>
                                <?php foreach($students_with_credits as $student): ?>
                                <tr>
                                    <td><strong><?php echo $student['student_code']; ?></strong></td>
                                    <td><?php echo ucwords(strtolower($student['name'])); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $student['class_name'] ?? 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success" style="font-size: 12px;">
                                            <sup style="font-size: 9px; vertical-align: super;">GH₵</sup><?php echo number_format($student['total_credit'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo $student['credit_count']; ?> records</span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($student['last_credit_date'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info" onclick="showCreditHistory(<?php echo $student['student_id']; ?>)" title="View History">
                                                <i class="fa fa-history"></i>
                                            </button>
                                            <button class="btn btn-warning" onclick="adjustCredit(<?php echo $student['student_id']; ?>)" title="Adjust Credit">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-success" onclick="transferCredit(<?php echo $student['student_id']; ?>)" title="Transfer Credit">
                                                <i class="fa fa-exchange"></i>
                                            </button>
                                            <button class="btn btn-primary" onclick="transferToDailyFees(<?php echo $student['student_id']; ?>)" title="Transfer to Daily Fees">
                                                <i class="fa fa-utensils"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div style="padding: 20px;">
                                            <i class="fa fa-info-circle fa-3x" style="color: #ccc;"></i>
                                            <h4 style="color: #999;">No students with credit balances</h4>
                                            <p style="color: #999;">Credits will appear here when students make overpayments</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Credit History Modal -->
<div id="creditHistoryModalContainer"></div>

<!-- Credit Adjustment Modal -->
<div id="creditAdjustModalContainer"></div>

<!-- Credit Transfer Modal -->
<div id="creditTransferModalContainer"></div>

<script>
$(document).ready(function() {
    // Initialize DataTable with modern styling
    $('#creditsTable').DataTable({
        "order": [[ 3, "desc" ]], // Sort by credit balance descending
        "pageLength": 25,
        "responsive": true,
        "language": {
            "search": "Search students:",
            "lengthMenu": "Show _MENU_ students per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ students",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next →",
                "previous": "← Previous"
            }
        }
    });
});

// Refresh statistics
function refreshStatistics() {
    showAjaxModal_alert('Refreshing data...', 'loading');
    location.reload();
}

// Show credit history
function showCreditHistory(studentId) {
    loadModalContent('createModal', '<?=site_url("admin/credit_history_modal/")?>' + studentId, '<i class="fa fa-history"></i> Credit History');
}

// Adjust credit
function adjustCredit(studentId) {
    loadModalContent('createModal', '<?=site_url("admin/credit_adjust_modal/")?>' + studentId, '<i class="fa fa-edit"></i> Adjust Student Credit');
}

// Transfer credit
function transferCredit(studentId) {
    loadModalContent('createModal', '<?=site_url("admin/credit_transfer_modal/")?>' + studentId, '<i class="fa fa-exchange"></i> Transfer Credit');
}

// Show credit transfer modal
function showCreditTransferModal() {
    loadModalContent('createModal', '<?=site_url("admin/credit_transfer_modal")?>', '<i class="fa fa-exchange"></i> Transfer Credit');
}

// Transfer to daily fees
function transferToDailyFees(studentId) {
    loadModalContent('createModal', '<?=site_url("admin/credit_to_daily_fees_modal/")?>' + studentId, '<i class="fa fa-utensils"></i> Transfer to Daily Fees');
}
</script>
