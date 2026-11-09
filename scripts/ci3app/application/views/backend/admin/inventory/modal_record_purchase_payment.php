<?php
/**
 * Purchase Order Payment Recording Modal
 * 
 * This modal allows users to record payments against purchase orders.
 * It displays purchase order details, validates payment amounts, and submits via AJAX.
 * 
 * @param int $purchase_id Purchase order ID passed via URL parameter
 */

// Get purchase order details
$purchase_id = $param2;

// Test if we can even get here
error_log("Modal loading for purchase ID: " . $purchase_id);

try {
    $purchase = $this->db->get_where('inventory_purchases', array('id' => $purchase_id))->row();
    
    if (!$purchase) {
        echo '<div class="alert alert-danger">Purchase order not found.</div>';
        return;
    }
    
    error_log("Purchase found: " . print_r($purchase, true));
    
    // Get supplier details
    $supplier = $this->db->get_where('inventory_suppliers', array('id' => $purchase->supplier_id))->row();
    $supplier_name = $supplier ? $supplier->name : 'Unknown Supplier';
    
    // Calculate outstanding balance
    $outstanding_balance = $purchase->total_amount - $purchase->amount_paid;
    
    // Get currency
    $currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
    $currency = $currency_row ? $currency_row->description : 'GHC';
    
    error_log("Currency: " . $currency . ", Balance: " . $outstanding_balance);
    
} catch (Exception $e) {
    error_log("Error in modal: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading payment form: ' . $e->getMessage() . '</div>';
    return;
}
?>

<div class="grid grid-cols-1">
    <section class="px-4 md:px-15">
        <div class="font-extrabold text-2xl text-gray-500 text-right">RECORD PAYMENT</div>
        <div class="flex flex-col p-5 w-full max-w-full border-t-8 bg-white shadow-md border border-t-blue-500 rounded-xl">
            <div class="flex flex-col gap-6 w-full max-w-full p-4">
                
                <!-- Purchase Order Details Section -->
                <div class="grid grid-cols-1 gap-4 border-b border-b-gray-300 pb-4">
                    <div class="text-gray-600 font-mono font-bold text-xl">PURCHASE ORDER DETAILS</div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 text-gray-600 font-mono font-bold text-xl border-b border-b-gray-400 pb-3">ORDER INFORMATION</div>
                    
                    <div class="text-xl font-semibold text-gray-400 dark:text-white">PURCHASE ORDER #:</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white uppercase"><?php echo $purchase->id; ?></div>

                    <div class="text-xl font-semibold text-gray-400 dark:text-white">SUPPLIER:</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white uppercase"><?php echo $supplier_name; ?></div>

                    <div class="text-xl font-semibold text-gray-400 dark:text-white">PURCHASE DATE:</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white uppercase"><?php echo date('d M Y', strtotime($purchase->purchase_date)); ?></div>

                    <div class="text-xl font-semibold text-gray-400 dark:text-white">TOTAL AMOUNT:</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-white uppercase"><?php echo $currency . ' ' . number_format($purchase->total_amount, 2); ?></div>

                    <div class="text-xl font-semibold text-gray-400 dark:text-white">AMOUNT PAID:</div>
                    <div class="text-xl font-semibold text-green-600 dark:text-white uppercase"><?php echo $currency . ' ' . number_format($purchase->amount_paid, 2); ?></div>

                    <div class="text-xl font-semibold text-gray-500 dark:text-white">OUTSTANDING BALANCE:</div>
                    <div class="text-xl font-semibold text-red-600 dark:text-white uppercase" id="outstanding_balance_display"><?php echo $currency . ' ' . number_format($outstanding_balance, 2); ?></div>
                </div>
                
                <hr>
                
                <!-- Payment Form Section -->
                <div class="grid grid-cols-1 gap-4">
                    <?php echo form_open(site_url('inventory/record_purchase_payment'), array('class' => 'validate flex flex-col gap-5', 'id' => 'purchase_payment_form')); ?>
                        
                        <div class="flex items-center w-full">
                            <label for="payment_date" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">PAYMENT DATE</label>
                            <div class="relative w-full">
                                <input type="text" 
                                       name="payment_date" 
                                       id="payment_date" 
                                       value="<?php echo date('d/m/Y'); ?>"
                                       data-validate="required" 
                                       data-message-required="<?php echo get_phrase('field_required'); ?>" 
                                       class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                       placeholder="dd/mm/yyyy"
                                       autocomplete="off"
                                       required="true">
                                <i class="fa fa-calendar absolute right-4 top-5 text-gray-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="flex flex-col w-full">
                            <label for="amount" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">PAYMENT AMOUNT</label>
                            <input type="number" 
                                   name="amount" 
                                   id="amount" 
                                   min="0.01" 
                                   step="0.01"
                                   max="<?php echo $outstanding_balance; ?>"
                                   value="<?php echo number_format($outstanding_balance, 2, '.', ''); ?>"
                                   data-validate="required" 
                                   data-message-required="<?php echo get_phrase('amount_required'); ?>" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                   placeholder="0.00" 
                                   autofocus 
                                   required="true">
                            <small class="text-gray-500 mt-1 block">Maximum: <?php echo $currency . ' ' . number_format($outstanding_balance, 2); ?></small>
                        </div>

                        <div class="flex items-center w-full">
                            <label for="payment_method_id" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">PAYMENT METHOD</label>
                            <select id="payment_method_id" 
                                    name="payment_method_id" 
                                    data-validate="required" 
                                    data-message-required="<?php echo get_phrase('field_required'); ?>" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                    required="true" 
                                    onchange="showPaymentMethodDetails($(this).val())">
                                <option value="">Select Payment Method</option>
                                <?php
                                    $payment_methods = get_payment_methods();
                                    if(!empty($payment_methods)) {
                                        foreach($payment_methods as $method) {
                                            echo '<option value="' . $method->id . '">' . $method->name . '</option>';
                                        }
                                    } else {
                                        // Fallback if database table doesn't exist
                                        echo '<option value="1">Cash</option>';
                                        echo '<option value="2">Cheque</option>';
                                        echo '<option value="3">Mobile Money</option>';
                                        echo '<option value="4">Bank Transfer</option>';
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="flex flex-col w-full">
                            <label for="reference_number" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">REFERENCE NUMBER</label>
                            <input type="text" 
                                   name="reference_number" 
                                   id="reference_number" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                   placeholder="Transaction/Cheque Reference (Optional)">
                            <small class="text-gray-500 mt-1 block">Optional: Enter transaction ID, cheque number, or reference</small>
                        </div>

                        <!-- Mobile Money Transaction ID (conditional) -->
                        <div class="flex items-center w-full momo_transaction_details" style="display: none">
                            <label for="momo_transaction_id" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">MOBILE MONEY TRANSACTION ID</label>
                            <input type="text" 
                                   name="momo_transaction_id" 
                                   id="momo_transaction_id" 
                                   minlength="10" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                   placeholder="60560211080">
                        </div>

                        <!-- Cheque Details (conditional) -->
                        <div class="flex items-center w-full cheque_details" style="display: none">
                            <label for="bank_name" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">BANK NAME</label>
                            <input type="text" 
                                   name="bank_name" 
                                   id="bank_name" 
                                   minlength="3" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                   placeholder="Ecobank">
                        </div>
                        <div class="flex items-center w-full cheque_details" style="display: none">
                            <label for="cheque_number" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">CHEQUE NUMBER</label>
                            <input type="text" 
                                   name="cheque_number" 
                                   id="cheque_number" 
                                   minlength="6" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                   placeholder="00112345678">
                        </div>

                        <div class="flex items-center w-full">
                            <label for="notes" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">NOTES</label>
                            <textarea name="notes" 
                                      id="notes" 
                                      rows="3" 
                                      class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                                      placeholder="Additional notes (Optional)"></textarea>
                        </div>

                        <input type="hidden" name="purchase_id" value="<?php echo $purchase_id; ?>">

                        <div class="flex gap-4 justify-end mt-8">
                            <button type="button" 
                                    onclick="$('#modal_ajax').modal('hide');" 
                                    class="px-8 py-3 font-semibold rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors text-xl">
                                CANCEL
                            </button>
                            <?php echo get_button('submit', 'RECORD PAYMENT', 'font-semibold text-xl'); ?>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
$(function(e) {
    // Initialize datepicker with dd/mm/yyyy format
    $('#payment_date').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        endDate: '0d', // Cannot select future dates
        orientation: 'bottom auto'
    });
    
    // Prevent modal from closing on ESC key or backdrop click
    $('#modal_ajax').modal({
        backdrop: 'static',
        keyboard: false
    });

    const outstandingBalance = <?php echo $outstanding_balance; ?>;
    const currency = '<?php echo $currency; ?>';

    // Validate payment amount on input
    $('#amount').on('input', function() {
        const amount = parseFloat($(this).val());
        
        if (amount > outstandingBalance) {
            $(this).addClass('border-red-500');
            $(this).after('<span class="text-red-500 text-sm error-message">Amount cannot exceed outstanding balance</span>');
        } else {
            $(this).removeClass('border-red-500');
            $('.error-message').remove();
        }
    });

    // Payment method conditional fields
    function showPaymentMethodDetails(methodId) {
        // Hide all conditional fields
        $('.momo_transaction_details').slideUp('slow');
        $('.cheque_details').slideUp('slow');
        
        // Remove required attributes
        $('#momo_transaction_id').removeAttr('required');
        $('#bank_name').removeAttr('required');
        $('#cheque_number').removeAttr('required');

        // Show relevant fields based on payment method
        // Assuming: 1=Cash, 2=Cheque, 3=Mobile Money, 4=Bank Transfer
        if (methodId == 3) { // Mobile Money
            $('.momo_transaction_details').slideDown('slow');
            $('#momo_transaction_id').attr('required', 'required');
        } else if (methodId == 2) { // Cheque
            $('.cheque_details').slideDown('slow');
            $('#bank_name').attr('required', 'required');
            $('#cheque_number').attr('required', 'required');
        }
    }

    // Make function globally accessible
    window.showPaymentMethodDetails = showPaymentMethodDetails;

    // Form submission
    $('#purchase_payment_form').submit(function(event) {
        event.preventDefault();
        
        // Validate amount
        const amount = parseFloat($('#amount').val());
        if (amount <= 0) {
            showAjaxModal_alert('Payment amount must be greater than zero', 'Error');
            return;
        }
        
        if (amount > outstandingBalance) {
            showAjaxModal_alert('Payment amount cannot exceed outstanding balance of ' + currency + ' ' + outstandingBalance.toFixed(2), 'Error');
            return;
        }

        // Validate payment date (dd/mm/yyyy format)
        const paymentDateStr = $('#payment_date').val();
        if (!paymentDateStr) {
            showAjaxModal_alert('Please select a payment date', 'Error');
            return;
        }
        
        // Parse dd/mm/yyyy date
        const dateParts = paymentDateStr.split('/');
        if (dateParts.length !== 3) {
            showAjaxModal_alert('Invalid date format. Please use dd/mm/yyyy', 'Error');
            return;
        }
        
        const paymentDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (paymentDate > today) {
            showAjaxModal_alert('Payment date cannot be in the future', 'Error');
            return;
        }

        // Button locking - Disable submit button immediately to prevent double submissions
        const $submitButton = $('#purchase_payment_form button[type="submit"]');
        const originalButtonHtml = $submitButton.html();
        $submitButton.prop('disabled', true)
                     .addClass('opacity-50 cursor-not-allowed bg-gray-400')
                     .html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
        
        // Show processing message
        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px;">Recording Payment...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
        
        // Submit via AJAX
        $.ajax({
            url: '<?php echo site_url('inventory/record_purchase_payment'); ?>',
            type: 'POST',
            dataType: 'json',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        })
        .done(function(data) {
            if (data.status === 'success') {
                // Success
                showAjaxModal_alert('Payment recorded successfully!', 'success', false);
                
                setTimeout(() => {
                    $('#modal_alert').modal('hide');
                    $('#modal_ajax').modal('hide');
                    
                    // Refresh purchase orders list if the function exists
                    if (typeof loadPurchaseOrders === 'function') {
                        loadPurchaseOrders();
                    }
                    
                    // Refresh payment history if viewing details
                    if (typeof loadPaymentHistory === 'function') {
                        loadPaymentHistory(<?php echo $purchase_id; ?>);
                    }
                }, 2000);
            } else {
                // Error - Re-enable button
                $submitButton.prop('disabled', false)
                             .removeClass('opacity-50 cursor-not-allowed bg-gray-400')
                             .html(originalButtonHtml);
                
                $('#modal_alert').modal('hide');
                setTimeout(() => {
                    showAjaxModal_alert(data.message || 'Failed to record payment. Please try again.', 'Error');
                }, 300);
            }
        })
        .fail(function(xhr, status, error) {
            // Error - Re-enable button
            $submitButton.prop('disabled', false)
                         .removeClass('opacity-50 cursor-not-allowed bg-gray-400')
                         .html(originalButtonHtml);
            
            $('#modal_alert').modal('hide');
            setTimeout(() => {
                showAjaxModal_alert('An error occurred: ' + error, 'Error');
            }, 300);
        });
    });
});
</script>
