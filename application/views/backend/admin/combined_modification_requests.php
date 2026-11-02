<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Combine and sort all requests
$combined = [];

foreach($receipt_requests as $req) {
    $req['mod_type'] = 'receipt';
    $combined[] = $req;
}

foreach($invoice_requests as $req) {
    $req['mod_type'] = 'invoice';
    $combined[] = $req;
}

// Sort by any available timestamp column DESC
usort($combined, function($a, $b) {
    // Try multiple possible timestamp columns
    $time_a = 0;
    $time_b = 0;
    
    foreach(['requested_at', 'created_at', 'timestamp', 'date'] as $col) {
        if(isset($a[$col]) && !$time_a) $time_a = is_numeric($a[$col]) ? $a[$col] : strtotime($a[$col]);
        if(isset($b[$col]) && !$time_b) $time_b = is_numeric($b[$col]) ? $b[$col] : strtotime($b[$col]);
    }
    
    return $time_b - $time_a;
});

if(empty($combined)): ?>
<div class="text-center py-20">
    <i class="fa fa-clipboard-check fa-5x text-gray-300 mb-4"></i>
    <p class="text-2xl text-gray-500 font-semibold">No modification requests found</p>
</div>
<?php else: ?>
<div class="space-y-4">
    <?php foreach($combined as $request): 
        $is_receipt = $request['mod_type'] == 'receipt';
        $requester = $this->db->where('admin_id', $request['requested_by'])->get('admin')->row();
        
        if($is_receipt) {
            $payment = $this->db->where('payment_id', $request['payment_id'])->get('payment')->row();
            $student = $this->db->where('student_id', $payment->student_id)->get('student')->row();
        } else {
            $student = $this->db->where('student_id', $request['student_id'])->get('student')->row();
        }
        
        $status_colors = [
            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'approved' => 'bg-green-100 text-green-800 border-green-300',
            'declined' => 'bg-red-100 text-red-800 border-red-300'
        ];
        $status_color = $status_colors[$request['status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
    ?>
    <div class="modification-item border-2 rounded-lg p-6 <?php echo $status_color; ?> transition-all hover:shadow-lg" data-type="<?php echo $request['mod_type']; ?>">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-3">
                <?php if($is_receipt): ?>
                <div class="bg-purple-600 text-white p-3 rounded-lg">
                    <i class="fa fa-receipt fa-2x"></i>
                </div>
                <?php else: ?>
                <div class="bg-blue-600 text-white p-3 rounded-lg">
                    <i class="fa fa-file-invoice fa-2x"></i>
                </div>
                <?php endif; ?>
                <div>
                    <h3 class="text-xl font-bold text-gray-800">
                        <?php echo $is_receipt ? 'Receipt' : 'Invoice'; ?> <?php echo ucfirst($request['request_type']); ?> Request
                    </h3>
                    <p class="text-sm text-gray-600">
                        <?php if($is_receipt): ?>
                        Receipt: <span class="font-semibold">#<?php echo $payment->receipt_code; ?></span>
                        <?php else: ?>
                        Invoice: <span class="font-semibold">#<?php echo $request['invoice_code']; ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <span class="px-4 py-2 rounded-full font-bold text-sm uppercase <?php echo $status_color; ?> border-2">
                <?php echo $request['status']; ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-sm text-gray-600 mb-1"><i class="fa fa-user"></i> Student</p>
                <p class="font-semibold text-gray-800"><?php echo $student->name; ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1"><i class="fa fa-user-tie"></i> Requested By</p>
                <p class="font-semibold text-gray-800"><?php echo $requester->name; ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1"><i class="fa fa-calendar"></i> Requested At</p>
                <p class="font-semibold text-gray-800"><?php 
                    $timestamp = null;
                    foreach(['requested_at', 'created_at', 'timestamp', 'date'] as $col) {
                        if(isset($request[$col])) {
                            $timestamp = $request[$col];
                            break;
                        }
                    }
                    if($timestamp) {
                        $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
                        echo date('M d, Y h:i A', $time);
                    } else {
                        echo 'N/A';
                    }
                ?></p>
            </div>
            <?php if($request['status'] != 'pending'): 
                $reviewer = $this->db->where('admin_id', $request['reviewed_by'])->get('admin')->row();
            ?>
            <div>
                <p class="text-sm text-gray-600 mb-1"><i class="fa fa-user-check"></i> Reviewed By</p>
                <p class="font-semibold text-gray-800"><?php echo $reviewer->name; ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php if($request['request_type'] == 'edit'): ?>
        <div class="bg-white bg-opacity-50 rounded-lg p-4 mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fa fa-edit"></i> Changes:</p>
            <?php 
            if($is_receipt) {
                $old_data = json_decode($request['old_data'], true);
                $new_data = json_decode($request['new_data'], true);
            ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-600 mb-1">Old Amount</p>
                    <p class="font-bold text-red-600"><?php echo $currency . number_format($old_data['amount'], 2); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 mb-1">New Amount</p>
                    <p class="font-bold text-green-600"><?php echo $currency . number_format($new_data['amount'], 2); ?></p>
                </div>
            </div>
            <?php } else {
                $items = json_decode($request['new_data'], true);
            ?>
            <div class="space-y-2">
                <?php foreach($items as $item): ?>
                <div class="flex justify-between items-center bg-white p-2 rounded">
                    <span class="text-sm"><?php echo $item['description']; ?></span>
                    <span class="font-bold"><?php echo $currency . number_format($item['amount'], 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php } ?>
        </div>
        <?php endif; ?>

        <?php if($request['reason']): ?>
        <div class="bg-white bg-opacity-50 rounded-lg p-4 mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fa fa-comment"></i> Reason:</p>
            <p class="text-gray-800"><?php echo $request['reason']; ?></p>
        </div>
        <?php endif; ?>

        <?php if($request['status'] == 'pending'): ?>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="<?php echo $is_receipt ? 'reviewReceiptModification' : 'reviewInvoiceModification'; ?>(<?php echo $is_receipt ? $request['request_id'] : $request['request_id']; ?>, 'approve')" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-lg transition-all">
                <i class="fa fa-check"></i> Approve
            </button>
            <button type="button" onclick="<?php echo $is_receipt ? 'reviewReceiptModification' : 'reviewInvoiceModification'; ?>(<?php echo $is_receipt ? $request['request_id'] : $request['request_id']; ?>, 'decline')" class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 rounded-lg transition-all">
                <i class="fa fa-times"></i> Decline
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function reviewReceiptModification(requestId, action) {
    const actionText = action === 'approve' ? 'approve' : 'decline';
    showConfirmModal(
        'Confirm ' + actionText.charAt(0).toUpperCase() + actionText.slice(1),
        'Are you sure you want to ' + actionText + ' this receipt modification request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/review_receipt_modification/'); ?>' + requestId + '/' + action,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        actionText.charAt(0).toUpperCase() + actionText.slice(1),
        action === 'approve' ? 'success' : 'danger'
    );
}

function reviewInvoiceModification(requestId, action) {
    const actionText = action === 'approve' ? 'approve' : 'decline';
    showConfirmModal(
        'Confirm ' + actionText.charAt(0).toUpperCase() + actionText.slice(1),
        'Are you sure you want to ' + actionText + ' this invoice modification request?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/review_invoice_modification/'); ?>' + requestId + '/' + action,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        actionText.charAt(0).toUpperCase() + actionText.slice(1),
        action === 'approve' ? 'success' : 'danger'
    );
}
</script>
