<?php
// Get all payments for this receipt
$payments = json_decode($request['original_data'], true);
$payment = $payments[0]; // Use first payment for display
$student = $this->db->where('student_id', $payment['student_id'])->get('student')->row();
// Get running year and term
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
// Get class from enroll table
$enroll = $this->db->where('student_id', $payment['student_id'])
                   ->where('year', $running_year)
                   ->where('term', $running_term)
                   ->get('enroll')->row();
$class = $this->db->where('class_id', $enroll->class_id)->get('class')->row();
$section = $this->db->where('section_id', $enroll->section_id)->get('section')->row();
$requester = $this->db->where('admin_id', $request['requested_by'])->get('admin')->row();
// Get theme colors
$theme = $this->db->get_where('settings', array('type' => 'app_theme'))->row()->description ?? 'default';
$themes = array(
    'default' => array('primary' => '#667eea', 'secondary' => '#764ba2'),
    'ocean' => array('primary' => '#2E3192', 'secondary' => '#1BFFFF'),
    'sunset' => array('primary' => '#f12711', 'secondary' => '#f5af19'),
    'forest' => array('primary' => '#134E5E', 'secondary' => '#71B280'),
    'purple' => array('primary' => '#5f27cd', 'secondary' => '#341f97'),
    'crimson' => array('primary' => '#c0392b', 'secondary' => '#e74c3c'),
    'teal' => array('primary' => '#16a085', 'secondary' => '#1abc9c'),
    'midnight' => array('primary' => '#2c3e50', 'secondary' => '#34495e'),
);
if($theme === 'custom') {
    $primary = $this->db->get_where('settings', array('type' => 'theme_primary'))->row()->description ?? '#667eea';
    $secondary = $this->db->get_where('settings', array('type' => 'theme_secondary'))->row()->description ?? '#764ba2';
} else {
    $primary = $themes[$theme]['primary'] ?? '#667eea';
    $secondary = $themes[$theme]['secondary'] ?? '#764ba2';
}
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

$status_colors = [
    'pending' => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e'],
    'approved' => ['bg' => '#d1fae5', 'border' => '#10b981', 'text' => '#065f46'],
    'rejected' => ['bg' => '#fee2e2', 'border' => '#ef4444', 'text' => '#991b1b'],
    'revoked' => ['bg' => '#e5e7eb', 'border' => '#6b7280', 'text' => '#374151']
];
$status = $request['status'];

// Calculate total amount for all payments in this receipt
$total_amount = array_sum(array_column($payments, 'amount'));
?>

<style>
.detail-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid <?php echo $primary; ?>;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.detail-box {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    padding: 16px;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
}
.detail-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.detail-label {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    font-weight: 600;
}
.detail-value {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}
</style>

<div style="padding: 24px; background: #f8f9fa;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, <?php echo $primary; ?> 0%, <?php echo $secondary; ?> 100%); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="background: rgba(255,255,255,0.2); padding: 16px; border-radius: 50%; backdrop-filter: blur(10px);">
                <i class="fa fa-info-circle" style="font-size: 28px; color: white;"></i>
            </div>
            <div style="flex: 1;">
                <h3 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700; color: white;">
                    <i class="fa fa-info-circle"></i> Request Details
                </h3>
                <div style="opacity: 0.95; font-size: 14px; color: white;">
                    <i class="fa fa-<?php echo $request['request_type'] == 'edit' ? 'edit' : 'trash-alt'; ?>"></i> 
                    <?php echo ucfirst($request['request_type']); ?> Request #<?php echo $request['request_id']; ?> • 
                    <i class="fa fa-clock"></i> <?php echo date('d M Y, H:i', $request['requested_at']); ?>
                </div>
            </div>
            <div style="background: <?php echo $status_colors[$status]['bg']; ?>; color: <?php echo $status_colors[$status]['text']; ?>; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-transform: uppercase; font-size: 14px; border: 2px solid <?php echo $status_colors[$status]['border']; ?>;">
                <?php echo $status; ?>
            </div>
        </div>
    </div>

    <!-- Receipt Info Card -->
    <div class="detail-card">
        <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #1f2937;">
            <i class="fa fa-receipt" style="color: #667eea;"></i> Receipt Information
        </h4>
        <div class="detail-grid">
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-hashtag"></i> Receipt Number</div>
                <div class="detail-value" style="color: #667eea;">#<?php echo $request['receipt_code']; ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-money-bill-wave"></i> Total Amount</div>
                <div class="detail-value" style="color: #10b981;"><?php echo $currency . ' ' . number_format($total_amount, 2); ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-list"></i> Payment Items</div>
                <div class="detail-value"><?php echo count($payments); ?> item(s)</div>
            </div>
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-calendar"></i> Payment Date</div>
                <div class="detail-value"><?php echo date('d M Y', $payment['timestamp']); ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-credit-card"></i> Payment Method</div>
                <div class="detail-value"><?php 
                    $method_map = ['cash' => 'Cash', 'momo' => 'Mobile Money', 'cheque' => 'Cheque'];
                    echo isset($method_map[$payment['payment_method']]) ? $method_map[$payment['payment_method']] : ucfirst($payment['payment_method']);
                ?></div>
            </div>
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="detail-card" style="border-left-color: #10b981;">
        <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #1f2937;">
            <i class="fa fa-user-graduate" style="color: #10b981;"></i> Student Information
        </h4>
        <div class="detail-grid">
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-user"></i> Student Name</div>
                <div class="detail-value"><?php echo $student->name; ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-id-card"></i> Student Code</div>
                <div class="detail-value"><?php echo $student->student_code; ?></div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    <i class="fa fa-school"></i> <?php echo $class->name . ' ' . $class->name_numeric . ' ' . $section->name; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Info Card -->
    <div class="detail-card" style="border-left-color: #f59e0b;">
        <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #1f2937;">
            <i class="fa fa-user-shield" style="color: #f59e0b;"></i> Request Information
        </h4>
        <div class="detail-grid">
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-user"></i> Requested By</div>
                <div class="detail-value"><?php echo $requester->name; ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label"><i class="fa fa-clock"></i> Request Date</div>
                <div class="detail-value"><?php echo date('d M Y, H:i', $request['requested_at']); ?></div>
            </div>
        </div>
    </div>

    <!-- Reason Card -->
    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);">
        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #92400e;">
            <i class="fa fa-comment-dots"></i> REASON FOR MODIFICATION
        </h4>
        <div style="color: #78350f; font-size: 15px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></div>
    </div>

    <?php if($request['request_type'] == 'edit' && $request['new_data']): ?>
    <?php 
    $new_data = json_decode($request['new_data'], true);
    $new_amount = isset($new_data['amount']) ? $new_data['amount'] : $payment['amount'];
    $new_method = isset($new_data['payment_method']) ? $new_data['payment_method'] : $payment['payment_method'];
    ?>
    <!-- New Values Card -->
    <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #10b981; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);">
        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #065f46;">
            <i class="fa fa-arrow-right"></i> PROPOSED NEW VALUES
        </h4>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #065f46; font-weight: 600; margin-bottom: 4px;">NEW AMOUNT</div>
                <div style="font-size: 18px; font-weight: 700; color: #047857;"><?php echo $currency . ' ' . number_format($new_amount, 2); ?></div>
            </div>
            <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #065f46; font-weight: 600; margin-bottom: 4px;">NEW METHOD</div>
                <div style="font-size: 18px; font-weight: 700; color: #047857;"><?php 
                    if($new_method) {
                        $method_map = ['cash' => 'Cash', 'momo' => 'Mobile Money', 'cheque' => 'Cheque'];
                        echo isset($method_map[$new_method]) ? $method_map[$new_method] : ucfirst($new_method);
                    } else {
                        echo 'Not specified';
                    }
                ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="detail-card" style="border-left-color: #667eea; margin-top: 24px;">
        <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #1f2937;">
            <i class="fa fa-history" style="color: #667eea;"></i> Request Timeline
        </h4>
        <div style="position: relative; padding-left: 40px;">
            <!-- Request Created -->
            <div style="position: relative; padding-bottom: 30px;">
                <div style="position: absolute; left: -40px; top: 0; width: 28px; height: 28px; border-radius: 50%; background: <?php echo $primary; ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; z-index: 1;">
                    <i class="fa fa-plus"></i>
                </div>
                <div style="position: absolute; left: -26px; top: 28px; bottom: 0; width: 2px; background: #e5e7eb;"></div>
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="font-weight: 700; color: #1a202c; margin-bottom: 4px;">Request Created</div>
                    <div style="font-size: 14px; color: #6b7280;">
                        <?php echo date('F d, Y \a\t H:i', $request['requested_at']); ?> by <?php echo $requester->name; ?>
                    </div>
                </div>
            </div>
            
            <?php if($request['status'] != 'pending'): 
                $approver = $this->db->where('admin_id', $request['approved_by'])->get('admin')->row();
                $approver_name = $approver ? $approver->name : 'Unknown';
                $status_config = [
                    'approved' => ['icon' => 'check', 'color' => '#10b981'],
                    'rejected' => ['icon' => 'times', 'color' => '#ef4444'],
                    'revoked' => ['icon' => 'undo', 'color' => '#f59e0b']
                ];
                $config = $status_config[$request['status']] ?? ['icon' => 'info', 'color' => '#6b7280'];
            ?>
            <!-- Request Reviewed -->
            <div style="position: relative;">
                <div style="position: absolute; left: -40px; top: 0; width: 28px; height: 28px; border-radius: 50%; background: <?php echo $config['color']; ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; z-index: 1;">
                    <i class="fa fa-<?php echo $config['icon']; ?>"></i>
                </div>
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="font-weight: 700; color: #1a202c; margin-bottom: 4px;">
                        Request <?php echo ucfirst($request['status']); ?>
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        <?php echo date('F d, Y \a\t H:i', $request['approved_at']); ?> by <?php echo $approver_name; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if($this->session->userdata('user_type') == 1 && $request['status'] == 'pending'): ?>
<div style="padding: 0 24px 24px 24px; background: #f8f9fa; display: flex; gap: 12px; justify-content: flex-end;">
    <button onclick="approveRequest(<?php echo $request['request_id']; ?>)" class="btn btn-success" style="padding: 12px 24px; font-weight: 600;">
        <i class="fa fa-check-circle"></i> Approve Request
    </button>
    <button onclick="rejectRequest(<?php echo $request['request_id']; ?>)" class="btn btn-danger" style="padding: 12px 24px; font-weight: 600;">
        <i class="fa fa-times-circle"></i> Reject Request
    </button>
</div>
<?php endif; ?>

<script>
function approveRequest(requestId) {
    showConfirmModal(
        'Approve Request',
        'Are you sure you want to approve this receipt modification request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/approve_receipt_modification/"); ?>' + requestId,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                        setTimeout(() => {
                            $('.close').click();
                            location.reload();
                        }, 2000);
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('An error occurred', 'error');
                }
            });
        },
        'Approve',
        'success'
    );
}

function rejectRequest(requestId) {
    showConfirmModal(
        'Reject Request',
        'Are you sure you want to reject this receipt modification request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/reject_receipt_modification/"); ?>' + requestId,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                        setTimeout(() => {
                            $('.close').click();
                            location.reload();
                        }, 2000);
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('An error occurred', 'error');
                }
            });
        },
        'Reject',
        'danger'
    );
}
</script>
