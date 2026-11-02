<!-- Edit Purchase Order Form -->
<style>
#editModal .modal-dialog {
    max-width: 1000px !important;
}
</style>

<div class="p-6">
    <form id="editPurchaseOrderForm">
        <input type="hidden" name="po_id" value="<?php echo $purchase_order['id']; ?>">
        
        <!-- Purchase Order Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Purchase Date -->
            <div>
                <label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Purchase Date <span class="text-red-500">*</span></label>
                <input type="date" name="purchase_date" value="<?php echo $purchase_order['purchase_date']; ?>" required 
                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                    style="font-size: 1.125rem !important; min-height: 3rem !important;">
            </div>
            
            <!-- Supplier -->
            <div>
                <label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Supplier</label>
                <select name="supplier_id" 
                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                    style="font-size: 1.125rem !important; min-height: 3rem !important;">
                    <option value="">Select Supplier (Optional)</option>
                    <?php foreach($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier->id; ?>" <?php echo ($purchase_order['supplier_id'] == $supplier->id) ? 'selected' : ''; ?>>
                        <?php echo $supplier->name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Reference Number -->
            <div>
                <label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Reference Number</label>
                <input type="text" name="reference_number" value="<?php echo $purchase_order['reference_number']; ?>" 
                    placeholder="Optional" 
                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                    style="font-size: 1.125rem !important; min-height: 3rem !important;">
            </div>
        </div>
        
        <!-- Items Section -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900">Purchase Items</h3>
                <button type="button" onclick="addEditItem()" 
                    class="px-4 py-2 font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                    style="font-size: 1rem !important;">
                    <i class="fa fa-plus mr-2"></i> Add Item
                </button>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div id="editItemsContainer">
                    <?php foreach($purchase_order['items'] as $index => $item): ?>
                    <div class="edit-item-row bg-white border border-gray-300 rounded-lg p-4 mb-3" data-index="<?php echo $index; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                            <!-- Product -->
                            <div class="md:col-span-2">
                                <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Product</label>
                                <select name="items[<?php echo $index; ?>][product_id]" required 
                                    class="item-product block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                    style="font-size: 1rem !important;">
                                    <?php foreach($products as $product): ?>
                                    <option value="<?php echo $product->id; ?>" <?php echo ($item['product_id'] == $product->id) ? 'selected' : ''; ?>>
                                        <?php echo $product->name; ?> (<?php echo $product->sku; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Quantity -->
                            <div>
                                <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Quantity</label>
                                <input type="number" name="items[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>" 
                                    min="1" required 
                                    class="item-quantity block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                    style="font-size: 1rem !important;"
                                    onchange="calculateEditItemTotal(<?php echo $index; ?>)">
                            </div>
                            
                            <!-- Cost Price -->
                            <div>
                                <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Cost Price</label>
                                <input type="number" name="items[<?php echo $index; ?>][cost_price]" value="<?php echo $item['cost_price']; ?>" 
                                    min="0" step="0.01" required 
                                    class="item-cost-price block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                    style="font-size: 1rem !important;"
                                    onchange="calculateEditItemTotal(<?php echo $index; ?>)">
                            </div>
                            
                            <!-- Total & Remove Button -->
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Total</label>
                                    <div style="font-size: 1.125rem !important;" class="item-total px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg font-semibold text-gray-900">
                                        <sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> <?php echo number_format($item['total_cost'], 2); ?>
                                    </div>
                                </div>
                                <button type="button" onclick="removeEditItem(<?php echo $index; ?>)" 
                                    class="px-3 py-2 font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors"
                                    style="font-size: 1rem !important; height: 42px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Grand Total Section with Discount -->
                <div class="mt-4 pt-4 border-t border-gray-300">
                    <!-- Subtotal, Discount, Final Total Table -->
                    <table class="w-full">
                        <tbody>
                            <!-- Subtotal Row -->
                            <tr>
                                <td class="text-right py-2 pr-4">
                                    <span style="font-size: 1.125rem !important;" class="font-semibold text-gray-700">Subtotal:</span>
                                </td>
                                <td class="text-right py-2">
                                    <span style="font-size: 1.125rem !important;" class="font-semibold text-gray-900" id="editSubtotal">
                                        <sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> <?php echo number_format($purchase_order['subtotal'] ?? $purchase_order['total_amount'], 2); ?>
                                    </span>
                                </td>
                            </tr>
                            
                            <!-- Discount Row -->
                            <tr>
                                <td class="text-right py-2 pr-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <span style="font-size: 1.125rem !important;" class="font-semibold text-gray-700">Discount:</span>
                                        <select name="discount_type" id="editDiscountType" onchange="calculateEditDiscount()" 
                                            class="px-2 py-1 border border-gray-300 rounded" 
                                            style="font-size: 0.875rem !important;">
                                            <option value="">None</option>
                                            <option value="percentage" <?php echo ($purchase_order['discount_type'] == 'percentage') ? 'selected' : ''; ?>>%</option>
                                            <option value="fixed" <?php echo ($purchase_order['discount_type'] == 'fixed') ? 'selected' : ''; ?>>Fixed</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="text-right py-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <input type="number" name="discount_value" id="editDiscountValue" 
                                            value="<?php echo $purchase_order['discount_value'] ?? 0; ?>"
                                            min="0" step="0.01" placeholder="0" 
                                            onchange="calculateEditDiscount()"
                                            class="w-24 px-2 py-1 border border-gray-300 rounded text-right" 
                                            style="font-size: 1rem !important;">
                                        <span style="font-size: 1.125rem !important;" class="font-semibold text-red-600 w-32" id="editDiscountAmount">
                                            -<sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> <?php echo number_format($purchase_order['discount_amount'] ?? 0, 2); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Final Total Row -->
                            <tr class="border-t-2 border-gray-400">
                                <td class="text-right py-3 pr-4">
                                    <span style="font-size: 1.5rem !important;" class="font-bold text-gray-900">Total:</span>
                                </td>
                                <td class="text-right py-3">
                                    <span style="font-size: 1.75rem !important;" class="font-bold text-green-600" id="editGrandTotal">
                                        <sup style="font-size: 0.5em;"><?php echo $currency; ?></sup> <?php echo number_format($purchase_order['final_total'] ?? $purchase_order['total_amount'], 2); ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Record Payment Toggle -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <label style="font-size: 1.25rem !important;" class="font-semibold text-gray-900">Record Payment Now</label>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mt-1">Mark this purchase as paid immediately</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="record_payment" id="editRecordPayment" class="sr-only peer" value="1">
                    <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            
            <!-- Payment Details (Initially Hidden) -->
            <div id="editPaymentDetails" style="display: none;" class="mt-4 pt-4 border-t border-blue-300">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Payment Amount</label>
                        <input type="number" name="payment_amount" step="0.01" min="0" 
                            placeholder="Enter payment amount" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                            style="font-size: 1rem !important;">
                        <p style="font-size: 0.875rem !important;" class="text-gray-500 mt-1">
                            Balance Due: <span id="editBalanceDue" class="font-semibold">GHC 0.00</span>
                        </p>
                    </div>
                    
                    <div>
                        <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Payment Method</label>
                        <select name="payment_method" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                            style="font-size: 1rem !important;">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Payment Reference</label>
                        <input type="text" name="payment_reference" placeholder="Transaction ID, Cheque No., etc." 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                            style="font-size: 1rem !important;">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="mb-6">
            <label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Notes</label>
            <textarea name="notes" rows="3" 
                placeholder="Add any additional notes here..." 
                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                style="font-size: 1.125rem !important;"><?php echo $purchase_order['notes']; ?></textarea>
        </div>
        
        <!-- Form Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeEditModal()" 
                class="px-6 py-3 font-semibold rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors" 
                style="font-size: 1.125rem !important;">
                Cancel
            </button>
            <button type="submit" 
                class="px-6 py-3 font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" 
                style="font-size: 1.125rem !important;">
                <i class="fa fa-save mr-2"></i> Update Purchase Order
            </button>
        </div>
    </form>
</div>

<script>
let editItemIndex = <?php echo count($purchase_order['items']); ?>;

// Function to close edit modal properly
function closeEditModal() {
    $('#editModal').modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '');
}

// Payment toggle
$('#editRecordPayment').change(function() {
    if($(this).is(':checked')) {
        $('#editPaymentDetails').slideDown();
        updateBalanceDue();
    } else {
        $('#editPaymentDetails').slideUp();
    }
});

function updateBalanceDue() {
    // Calculate balance due
    const grandTotalText = $('#editGrandTotal').text().replace(/[^0-9.]/g, '');
    const grandTotal = parseFloat(grandTotalText) || 0;
    const amountPaid = <?php echo $purchase_order['amount_paid'] ?? 0; ?>;
    const balanceDue = grandTotal - amountPaid;
    
    $('#editBalanceDue').text('GHC ' + balanceDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

function calculateEditDiscount() {
    const subtotal = parseFloat(calculateEditSubtotal());
    const discountType = $('#editDiscountType').val();
    const discountValue = parseFloat($('#editDiscountValue').val()) || 0;
    
    let discountAmount = 0;
    
    if(discountType === 'percentage' && discountValue > 0) {
        if(discountValue > 100) {
            $('#editDiscountValue').val(100);
            discountAmount = subtotal;
        } else {
            discountAmount = (subtotal * discountValue) / 100;
        }
    } else if(discountType === 'fixed' && discountValue > 0) {
        if(discountValue > subtotal) {
            $('#editDiscountValue').val(subtotal.toFixed(2));
            discountAmount = subtotal;
        } else {
            discountAmount = discountValue;
        }
    }
    
    const finalTotal = subtotal - discountAmount;
    
    // Update displays
    $('#editSubtotal').html('<sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#editDiscountAmount').html('-<sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> ' + discountAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#editGrandTotal').html('<sup style="font-size: 0.5em;"><?php echo $currency; ?></sup> ' + finalTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    
    // Update balance due if payment section is visible
    if($('#editPaymentDetails').is(':visible')) {
        updateBalanceDue();
    }
}

function calculateEditSubtotal() {
    let subtotal = 0;
    
    $('.edit-item-row').each(function() {
        const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
        const costPrice = parseFloat($(this).find('.item-cost-price').val()) || 0;
        subtotal += (quantity * costPrice);
    });
    
    return subtotal;
}

function addEditItem() {
    const html = `
        <div class="edit-item-row bg-white border border-gray-300 rounded-lg p-4 mb-3" data-index="${editItemIndex}">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <!-- Product -->
                <div class="md:col-span-2">
                    <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Product</label>
                    <select name="items[${editItemIndex}][product_id]" required 
                        class="item-product block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                        style="font-size: 1rem !important;">
                        <option value="">Select Product</option>
                        <?php foreach($products as $product): ?>
                        <option value="<?php echo $product->id; ?>">
                            <?php echo $product->name; ?> (<?php echo $product->sku; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Quantity -->
                <div>
                    <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Quantity</label>
                    <input type="number" name="items[${editItemIndex}][quantity]" value="1" 
                        min="1" required 
                        class="item-quantity block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                        style="font-size: 1rem !important;"
                        onchange="calculateEditItemTotal(${editItemIndex})">
                </div>
                
                <!-- Cost Price -->
                <div>
                    <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Cost Price</label>
                    <input type="number" name="items[${editItemIndex}][cost_price]" value="0" 
                        min="0" step="0.01" required 
                        class="item-cost-price block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                        style="font-size: 1rem !important;"
                        onchange="calculateEditItemTotal(${editItemIndex})">
                </div>
                
                <!-- Total & Remove Button -->
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label style="font-size: 1rem !important;" class="block font-semibold text-gray-700 mb-2">Total</label>
                        <div style="font-size: 1.125rem !important;" class="item-total px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg font-semibold text-gray-900">
                            <sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> 0.00
                        </div>
                    </div>
                    <button type="button" onclick="removeEditItem(${editItemIndex})" 
                        class="px-3 py-2 font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors"
                        style="font-size: 1rem !important; height: 42px;">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $('#editItemsContainer').append(html);
    editItemIndex++;
    calculateEditDiscount();
}

function removeEditItem(index) {
    $(`.edit-item-row[data-index="${index}"]`).remove();
    calculateEditDiscount();
}

function calculateEditItemTotal(index) {
    const row = $(`.edit-item-row[data-index="${index}"]`);
    const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
    const costPrice = parseFloat(row.find('.item-cost-price').val()) || 0;
    const total = quantity * costPrice;
    
    row.find('.item-total').html('<sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    
    calculateEditDiscount();
}

$('#editPurchaseOrderForm').submit(function(e) {
    e.preventDefault();
    
    // Collect all items
    const items = [];
    $('.edit-item-row').each(function() {
        const productId = $(this).find('.item-product').val();
        const quantity = parseFloat($(this).find('.item-quantity').val());
        const costPrice = parseFloat($(this).find('.item-cost-price').val());
        
        if(productId && quantity > 0 && costPrice >= 0) {
            items.push({
                product_id: productId,
                quantity: quantity,
                cost_price: costPrice
            });
        }
    });
    
    if(items.length === 0) {
        Swal.fire({
            title: 'Error!',
            text: 'Please add at least one item to the purchase order.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        return;
    }
    
    // Validate payment details if payment is being recorded
    if($('#editRecordPayment').is(':checked')) {
        const finalTotal = parseFloat(calculateEditSubtotal()) - (parseFloat($('#editDiscountValue').val()) || 0);
        if(finalTotal <= 0) {
            Swal.fire({
                title: 'Error!',
                text: 'Cannot record payment for zero or negative total.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            return;
        }
    }
    
    // Prepare form data
    const formData = new FormData(this);
    formData.append('items', JSON.stringify(items));
    
    // Show loading indicator
    Swal.fire({
        title: 'Updating...',
        text: 'Please wait while we update the purchase order.',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Submit via AJAX
    $.ajax({
        url: '<?php echo site_url('inventory/update_purchase_order'); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                // Close the modal immediately
                $('#editModal').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                
                // Show success message with any additional info
                let messageText = 'Purchase order updated successfully!';
                if(response.message && response.message !== 'success') {
                    messageText = response.message;
                }
                
                Swal.fire({
                    title: 'Success!',
                    html: messageText,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Reload the purchase orders table
                    if(typeof loadPurchaseOrders === 'function') {
                        loadPurchaseOrders();
                    } else {
                        location.reload();
                    }
                });
            } else {
                // Don't close modal on error - let user try again
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to update purchase order.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while updating the purchase order: ' + error,
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    });
});
</script>
