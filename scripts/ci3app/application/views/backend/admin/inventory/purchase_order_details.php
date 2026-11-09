<?php if(!isset($ajax_load)): ?>
<!-- Purchase Order Details View -->
<?php include('_readable_header.php'); ?>
<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
<?php endif; ?>

    <div class="mb-8">
        <h2 style="font-size: 2.5rem !important;" class="font-bold text-gray-900 mb-4">Purchase Order #<?php echo $po['id']; ?></h2>
        <a href="<?php echo site_url('inventory/purchase_orders'); ?>" style="font-size: 1.125rem !important;" class="text-blue-600 hover:text-blue-800">
            <i class="fa fa-arrow-left mr-2"></i> Back to Purchase Orders
        </a>
    </div>

    <!-- PO Header Information -->
    <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Purchase Date:</strong> <?php echo date('d M Y', strtotime($po['purchase_date'])); ?></p>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Supplier:</strong> <?php echo $po['supplier_name'] ?: 'N/A'; ?></p>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Reference Number:</strong> <?php echo $po['reference_number'] ?: 'N/A'; ?></p>
            </div>
            <div>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Status:</strong> 
                    <?php 
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'received' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    $statusColor = $statusColors[$po['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $statusColor; ?>"><?php echo strtoupper($po['status']); ?></span>
                </p>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Created By:</strong> <?php echo $po['created_by_name']; ?></p>
                <?php if($po['status'] === 'received'): ?>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Received By:</strong> <?php echo $po['received_by_name']; ?></p>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 mb-2"><strong>Received Date:</strong> <?php echo date('d M Y, h:i A', strtotime($po['received_date'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if($po['notes']): ?>
        <div class="mt-4 pt-4 border-t border-blue-200">
            <p style="font-size: 1.125rem !important;" class="text-gray-700"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($po['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Purchase Items -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900">Purchase Items</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-semibold text-gray-700">Product</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-center font-semibold text-gray-700">Quantity</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-semibold text-gray-700">Cost Price</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-semibold text-gray-700">Total Cost</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($po['items'] as $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-900"><?php echo $item['product_name']; ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-center text-gray-900 font-semibold"><?php echo $item['quantity']; ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900"><?php echo $currency; ?> <?php echo number_format($item['cost_price'], 2); ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900 font-bold"><?php echo $currency; ?> <?php echo number_format($item['total_cost'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <?php if(isset($po['subtotal']) && $po['subtotal'] > 0): ?>
                    <!-- Subtotal row -->
                    <tr>
                        <td colspan="3" style="font-size: 1.125rem !important;" class="px-6 py-3 text-right font-semibold text-gray-700">Subtotal:</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-3 text-right text-gray-900 font-semibold"><?php echo $currency; ?> <?php echo number_format($po['subtotal'], 2); ?></td>
                    </tr>
                    
                    <?php if(isset($po['discount_amount']) && $po['discount_amount'] > 0): ?>
                    <!-- Discount row -->
                    <tr>
                        <td colspan="3" style="font-size: 1.125rem !important;" class="px-6 py-3 text-right text-gray-700">
                            <span class="font-semibold">Discount</span>
                            <?php if(isset($po['discount_type']) && $po['discount_type'] === 'percentage'): ?>
                                <span class="text-sm text-gray-600">(<?php echo number_format($po['discount_value'], 2); ?>%)</span>
                            <?php elseif(isset($po['discount_type']) && $po['discount_type'] === 'fixed'): ?>
                                <span class="text-sm text-gray-600">(Fixed)</span>
                            <?php endif; ?>
                            :
                        </td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-3 text-right text-red-600 font-semibold">- <?php echo $currency; ?> <?php echo number_format($po['discount_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Total row -->
                    <tr class="border-t-2 border-gray-300">
                        <td colspan="3" style="font-size: 1.375rem !important;" class="px-6 py-4 text-right font-bold text-gray-900">TOTAL AMOUNT:</td>
                        <td style="font-size: 1.375rem !important;" class="px-6 py-4 text-right font-bold text-green-700"><?php echo $currency; ?> <?php echo number_format($po['final_total'] ?? $po['total_amount'], 2); ?></td>
                    </tr>
                    <?php else: ?>
                    <!-- Legacy format: just show total -->
                    <tr class="bg-green-50 font-bold">
                        <td colspan="3" style="font-size: 1.25rem !important;" class="px-6 py-4 text-right text-gray-900">TOTAL AMOUNT:</td>
                        <td style="font-size: 1.25rem !important;" class="px-6 py-4 text-right text-green-700"><?php echo $currency; ?> <?php echo number_format($po['total_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Stock Movements (if received) -->
    <?php if($po['status'] === 'received' && !empty($po['stock_movements'])): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900">Stock Adjustments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-semibold text-gray-700">Product</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-center font-semibold text-gray-700">Quantity Added</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-semibold text-gray-700">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($po['stock_movements'] as $movement): ?>
                    <tr class="hover:bg-gray-50">
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-900"><?php echo $movement['product_name']; ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-center text-green-600 font-bold">+<?php echo $movement['quantity']; ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-900"><?php echo date('d M Y, h:i A', strtotime($movement['movement_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payments Section -->
    <?php if($po['status'] === 'received'): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900">Payments</h3>
            <button onclick="showAddPaymentModal()" style="font-size: 1.125rem !important;" class="px-4 py-2 font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700">
                <i class="fa fa-plus mr-2"></i> Add Payment
            </button>
        </div>
        <div class="overflow-x-auto" id="payments-table">
            <?php if(!empty($po['payments'])): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-semibold text-gray-700 w-1/6">Date</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-semibold text-gray-700 w-1/5">Method</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-semibold text-gray-700 w-1/5">Amount</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-semibold text-gray-700 w-1/4">Reference</th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-center font-semibold text-gray-700 w-1/6">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($po['payments'] as $payment): ?>
                    <tr class="hover:bg-gray-50" id="payment-row-<?php echo $payment['id']; ?>">
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-900"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-900"><?php echo $payment['method_name']; ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900 font-semibold"><?php echo $currency; ?> <?php echo number_format($payment['amount'], 2); ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-700"><?php echo $payment['reference_number'] ?: 'N/A'; ?></td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-center">
                            <button onclick="deletePayment(<?php echo $payment['id']; ?>)" class="px-3 py-1.5 text-sm font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                                <i class="fa fa-trash mr-1"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-900">Total Paid:</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900 font-bold"><?php echo $currency; ?> <?php echo number_format($po['amount_paid'] ?? 0, 2); ?></td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-900">Balance:</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold <?php echo ($po['final_total'] ?? $po['total_amount']) - ($po['amount_paid'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo $currency; ?> <?php echo number_format(($po['final_total'] ?? $po['total_amount']) - ($po['amount_paid'] ?? 0), 2); ?>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            <?php else: ?>
            <div class="px-6 py-8 text-center text-gray-500">
                <i class="fa fa-info-circle mr-2"></i>No payments recorded yet. Click "Add Payment" to record a payment.
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <?php if($po['status'] === 'pending'): ?>
    <div class="flex gap-4">
        <button onclick="markPOAsReceived(<?php echo $po['id']; ?>)" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700">
            <i class="fa fa-check mr-2"></i> Mark as Received
        </button>
        <button onclick="cancelPO(<?php echo $po['id']; ?>)" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700">
            <i class="fa fa-times mr-2"></i> Cancel Order
        </button>
    </div>

    <script>
    function markPOAsReceived(poId) {
        showCustomConfirm(
            'Mark this purchase order as received? This will update stock levels and cannot be undone.',
            function() {
                // User clicked Yes - proceed with cost update method selection
                showAjaxModal_alert(
                    '<div style="text-align:left;"><p style="margin-bottom:15px;font-weight:600;">Cost price update method:</p>' +
                    '<select id="cost_update_method" class="form-control" style="width:100%;padding:10px;border-radius:8px;">' +
                    '<option value="replace">1 - Replace with new cost</option>' +
                    '<option value="weighted_average">2 - Weighted average</option>' +
                    '<option value="keep_existing">3 - Keep existing</option>' +
                    '</select></div>',
                    'info',
                    false,
                    false
                );
                
                // Add custom footer with Proceed button
                setTimeout(function() {
                    $('#modal_alert .modal-footer').html(
                        '<button type="button" class="btn modern-btn modern-btn-primary" onclick="proceedMarkAsReceived(' + poId + ')">Proceed</button>' +
                        '<button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>'
                    );
                }, 100);
            }
            // User clicked No - do nothing
        );
    }
    
    function proceedMarkAsReceived(poId) {
        const method = $('#cost_update_method').val();
        $('#modal_alert').modal('hide');
        
        showAjaxModal_alert('Processing...', 'Loading', false, false);
        
        $.ajax({
            url: '<?php echo site_url('inventory/update_purchase_status'); ?>',
            type: 'POST',
            data: {
                po_id: poId,
                status: 'received',
                cost_update_method: method
            },
            success: function(response) {
                const data = JSON.parse(response);
                if(data.status === 'success') {
                    showAjaxModal_alert('Purchase order marked as received! Stock levels have been updated.', 'Success', true, true);
                } else {
                    showAjaxModal_alert('Error: ' + data.message, 'Error', false, true);
                }
            },
            error: function() {
                showAjaxModal_alert('An error occurred while processing the request.', 'Error', false, true);
            }
        });
    }

    function cancelPO(poId) {
        showCustomConfirm(
            'Cancel this purchase order? This action cannot be undone.',
            function() {
                // User clicked Yes - proceed with cancellation
                showAjaxModal_alert('Processing...', 'Loading', false, false);
                
                $.ajax({
                    url: '<?php echo site_url('inventory/update_purchase_status'); ?>',
                    type: 'POST',
                    data: {
                        po_id: poId,
                        status: 'cancelled'
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if(data.status === 'success') {
                            showAjaxModal_alert('Purchase order cancelled.', 'Success', true, true);
                        } else {
                            showAjaxModal_alert('Error: ' + data.message, 'Error', false, true);
                        }
                    },
                    error: function() {
                        showAjaxModal_alert('An error occurred while processing the request.', 'Error', false, true);
                    }
                });
            }
            // User clicked No - do nothing
        );
    }
    
    function showAddPaymentModal() {
        $.ajax({
            url: '<?php echo site_url('inventory/payment_form/' . $po['id']); ?>',
            success: function(html) {
                $('#createModal_content').html(html);
                $('#createModal').modal('show');
            }
        });
    }
    
    // Reload payments table after adding payment
    window.reloadPaymentsTable = function() {
        $.ajax({
            url: '<?php echo site_url('inventory/get_purchase_payments/' . $po['id']); ?>',
            success: function(html) {
                $('#payments-table').html(html);
            },
            error: function() {
                location.reload(); // Fallback
            }
        });
    };
    
    function deletePayment(paymentId) {
        showCustomConfirm(
            'Delete this payment? This will update the purchase order balance.',
            function() {
                // User clicked Yes
                showAjaxModal_alert('Deleting payment...', 'loading', false, false);
                
                $.ajax({
                    url: '<?php echo site_url('inventory/delete_purchase_payment'); ?>',
                    type: 'POST',
                    data: { payment_id: paymentId },
                    dataType: 'json',
                    success: function(data) {
                        if(data.status === 'success') {
                            showAjaxModal_alert(data.message, 'success', false);
                            
                            // Reload just the payments table section via AJAX
                            setTimeout(function() {
                                $.ajax({
                                    url: '<?php echo site_url('inventory/get_purchase_payments/' . $po['id']); ?>',
                                    success: function(html) {
                                        $('#payments-table').html(html);
                                        $('#modal_alert').modal('hide');
                                    },
                                    error: function() {
                                        location.reload(); // Fallback to full reload
                                    }
                                });
                            }, 1000);
                        } else {
                            showAjaxModal_alert('Error: ' + data.message, 'error');
                        }
                    },
                    error: function() {
                        showAjaxModal_alert('An error occurred while deleting the payment.', 'error');
                    }
                });
            }
        );
    }
    </script>
    <?php endif; ?>

<?php if(!isset($ajax_load)): ?>
</div>
<?php endif; ?>
