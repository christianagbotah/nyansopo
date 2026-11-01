<?php
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
$user_level = $this->db->get_where('admin', ['admin_id' => $this->session->userdata('login_user_id')])->row()->level;
?>

<div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
    <div class="bg-gradient-to-r from-orange-50 to-red-50 border-l-4 border-orange-500 p-4 mb-6 rounded-lg">
        <div class="flex items-start">
            <i class="fa fa-exclamation-triangle text-2xl text-orange-600 mr-3 mt-1"></i>
            <div>
                <h4 class="text-lg font-bold text-gray-800 mb-2">Bulk Invoice Modification</h4>
                <p class="text-sm text-gray-700">
                    You are about to modify <strong><?php echo count($invoice_codes); ?> invoice(s)</strong>.
                    <?php if($user_level != 1): ?>
                    This action requires <strong>Super Admin approval</strong>.
                    <?php else: ?>
                    As a Super Admin, changes will be applied <strong>immediately</strong>.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <form id="bulk_modification_form">
        <input type="hidden" name="invoice_codes" value='<?php echo json_encode($invoice_codes); ?>'>
        
        <div class="mb-6">
            <label class="block text-lg font-bold text-gray-800 mb-3">
                <i class="fa fa-tasks text-blue-600"></i> Select Action
            </label>
            <div class="grid grid-cols-2 gap-4">
                <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 transition-all">
                    <input type="radio" name="action_type" value="edit" class="w-5 h-5 text-blue-600" checked>
                    <div class="ml-3">
                        <div class="text-base font-bold text-gray-800"><i class="fa fa-edit text-blue-600"></i> Edit Invoices</div>
                        <div class="text-xs text-gray-600">Modify invoice amounts and details</div>
                    </div>
                </label>
                <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-red-500 transition-all">
                    <input type="radio" name="action_type" value="delete" class="w-5 h-5 text-red-600">
                    <div class="ml-3">
                        <div class="text-base font-bold text-gray-800"><i class="fa fa-trash text-red-600"></i> Delete Invoices</div>
                        <div class="text-xs text-gray-600">Permanently remove selected invoices</div>
                    </div>
                </label>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-lg font-bold text-gray-800 mb-2">
                <i class="fa fa-comment-alt text-purple-600"></i> Reason for Modification <span class="text-red-600">*</span>
            </label>
            <textarea name="reason" rows="3" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="Provide a detailed reason for this modification..."></textarea>
        </div>

        <div id="edit_section" class="mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-lg">
                <p class="text-sm text-gray-700">
                    <i class="fa fa-info-circle text-blue-600"></i> 
                    <strong>Note:</strong> You can modify amounts and descriptions for each invoice item below.
                </p>
            </div>

            <?php foreach($invoices as $invoice_code => $items): ?>
            <div class="bg-white border-2 border-gray-200 rounded-lg p-4 mb-4 shadow-sm">
                <h5 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fa fa-file-invoice text-blue-600 mr-2"></i>
                    Invoice #<?php echo $invoice_code; ?>
                    <span class="ml-auto text-sm text-gray-600"><?php echo count($items); ?> item(s)</span>
                </h5>
                
                <div class="space-y-3">
                    <?php foreach($items as $item): ?>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <input type="hidden" name="items[<?php echo $invoice_code; ?>][<?php echo $item['invoice_id']; ?>][invoice_id]" value="<?php echo $item['invoice_id']; ?>">
                        
                        <div class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-4">
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Item Title</label>
                                <input type="text" value="<?php echo $item['title']; ?>" readonly class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm">
                            </div>
                            <div class="col-span-3">
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Amount (<?php echo $currency; ?>)</label>
                                <input type="number" step="0.01" name="items[<?php echo $invoice_code; ?>][<?php echo $item['invoice_id']; ?>][amount]" value="<?php echo $item['amount']; ?>" class="w-full px-3 py-2 border-2 border-gray-300 rounded focus:border-blue-500 text-sm font-bold">
                            </div>
                            <div class="col-span-3">
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Date</label>
                                <input type="date" name="items[<?php echo $invoice_code; ?>][<?php echo $item['invoice_id']; ?>][date]" value="<?php echo date('Y-m-d', $item['creation_timestamp']); ?>" class="w-full px-3 py-2 border-2 border-gray-300 rounded focus:border-blue-500 text-sm">
                            </div>
                            <div class="col-span-2 text-center">
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Paid</label>
                                <span class="inline-block px-3 py-2 bg-green-100 text-green-800 rounded font-bold text-sm"><?php echo number_format($item['amount_paid'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <label class="text-xs font-semibold text-gray-600 mb-1 block">Description</label>
                            <input type="text" name="items[<?php echo $invoice_code; ?>][<?php echo $item['invoice_id']; ?>][description]" value="<?php echo $item['description']; ?>" class="w-full px-3 py-2 border-2 border-gray-300 rounded focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="delete_section" class="mb-6" style="display:none;">
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-start">
                    <i class="fa fa-exclamation-circle text-2xl text-red-600 mr-3 mt-1"></i>
                    <div>
                        <h4 class="text-lg font-bold text-red-800 mb-2">Warning: Permanent Deletion</h4>
                        <p class="text-sm text-gray-700 mb-3">
                            You are about to <strong>permanently delete <?php echo count($invoice_codes); ?> invoice(s)</strong>. 
                            This action cannot be undone.
                        </p>
                        <div class="mt-4 p-3 bg-white rounded border border-red-300">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" id="delete_confirm" class="w-5 h-5 text-red-600 mr-3">
                                <span class="text-sm font-bold text-gray-800">I understand this action is permanent</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t-2 border-gray-200">
            <button type="button" onclick="$('.close').click()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-lg transition-all">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="submit" id="submit_bulk_modification" class="px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-bold rounded-lg shadow-lg transition-all">
                <i class="fa fa-check"></i> <?php echo $user_level == 1 ? 'Apply Changes' : 'Submit Request'; ?>
            </button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $('input[name="action_type"]').on('change', function() {
        if($(this).val() === 'delete') {
            $('#edit_section').slideUp(300);
            $('#delete_section').slideDown(300);
            $('#submit_bulk_modification').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        } else {
            $('#delete_section').slideUp(300);
            $('#edit_section').slideDown(300);
            $('#submit_bulk_modification').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
        }
    });

    $('#delete_confirm').on('change', function() {
        if($(this).is(':checked')) {
            $('#submit_bulk_modification').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
        } else {
            $('#submit_bulk_modification').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        }
    });

    $('#bulk_modification_form').on('submit', function(e) {
        e.preventDefault();
        
        const actionType = $('input[name="action_type"]:checked').val();
        const reason = $('textarea[name="reason"]').val().trim();
        
        if(!reason) {
            showAjaxModal_alert('Please provide a reason for this modification', 'error');
            return;
        }
        
        if(actionType === 'delete' && !$('#delete_confirm').is(':checked')) {
            showAjaxModal_alert('Please confirm that you understand this action is permanent', 'error');
            return;
        }
        
        $('.close').click();
        showAjaxModal_alert('Processing bulk modification...', 'loading');
        
        $.ajax({
            url: '<?php echo site_url('admin/bulkModifyInvoices'); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => {
                    $('.close').click();
                    if(typeof loadBulkInvoices === 'function') {
                        loadBulkInvoices();
                    } else {
                        location.reload();
                    }
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        }).fail(function(xhr) {
            showAjaxModal_alert('An error occurred: ' + xhr.responseText, 'error');
        });
    });
});
</script>
