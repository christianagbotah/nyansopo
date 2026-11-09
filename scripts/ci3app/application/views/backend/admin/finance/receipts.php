<?php $currency = get_settings('currency'); ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo get_phrase('receipts_management'); ?></h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo get_phrase('manage_and_track_payment_receipts'); ?></p>
        </div>
        <button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/0'); ?>', 'take_payment')" 
                class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <?php echo get_phrase('generate_receipt'); ?>
        </button>
    </div>
</div>

<!-- Filters Card -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php echo get_phrase('search_receipt'); ?>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="search-receipt" 
                           class="pl-10 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                           placeholder="<?php echo get_phrase('receipt_number'); ?>">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php echo get_phrase('payment_method'); ?>
                </label>
                <select id="filter-method" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value=""><?php echo get_phrase('all_methods'); ?></option>
                    <option value="cash"><?php echo get_phrase('cash'); ?></option>
                    <option value="momo"><?php echo get_phrase('mobile_money'); ?></option>
                    <option value="cheque"><?php echo get_phrase('cheque'); ?></option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php echo get_phrase('date'); ?>
                </label>
                <input type="date" id="filter-date" 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div class="flex items-end">
                <button onclick="applyFilters()" 
                        class="w-full px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <?php echo get_phrase('apply_filters'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipts Table Card -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table id="receipts-table" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('receipt_no'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('student'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('amount'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('method'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('date'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center"><?php echo get_phrase('status'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center"><?php echo get_phrase('actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- DataTable will populate this -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let receiptsTable;

$(document).ready(function() {
    initializeDataTable();
});

function initializeDataTable() {
    receiptsTable = $('#receipts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo site_url('finance/receipts/get_data'); ?>',
            type: 'POST',
            data: function(d) {
                d.search_receipt = $('#search-receipt').val();
                d.filter_method = $('#filter-method').val();
                d.filter_date = $('#filter-date').val();
            }
        },
        columns: [
            { 
                data: 'receipt_number',
                render: function(data) {
                    return '<span class="font-mono font-semibold text-gray-900 dark:text-white">' + data + '</span>';
                }
            },
            { 
                data: 'student_name',
                render: function(data, type, row) {
                    return `<div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
                                ${data.charAt(0)}
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${data}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${row.student_code}</p>
                        </div>
                    </div>`;
                }
            },
            { 
                data: 'amount',
                render: function(data) {
                    return '<span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $currency; ?>' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { 
                data: 'payment_method',
                render: function(data) {
                    const badges = {
                        'cash': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Cash</span>',
                        'momo': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Mobile Money</span>',
                        'cheque': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Cheque</span>'
                    };
                    return badges[data] || data;
                }
            },
            { 
                data: 'received_date',
                render: function(data) {
                    return '<span class="text-sm text-gray-600 dark:text-gray-400">' + new Date(data).toLocaleDateString() + '</span>';
                }
            },
            {
                data: null,
                className: 'text-center',
                render: function(data, type, row) {
                    let badges = '';
                    if (row.is_printed == 1) {
                        badges += '<span class="inline-flex items-center px-2 py-1 mr-1 text-xs font-medium text-green-700 bg-green-100 rounded dark:bg-green-900 dark:text-green-300" title="Printed"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"/></svg></span>';
                    }
                    if (row.is_emailed == 1) {
                        badges += '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded dark:bg-blue-900 dark:text-blue-300" title="Emailed"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg></span>';
                    }
                    return badges || '<span class="text-gray-400">-</span>';
                }
            },
            {
                data: 'receipt_id',
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    return `<div class="flex items-center justify-center gap-1">
                        <button onclick="viewReceipt(${data})" 
                                class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors" 
                                title="<?php echo get_phrase('view'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="printReceiptDirect(${data})" 
                                class="p-2 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors" 
                                title="<?php echo get_phrase('print'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </button>
                        <button onclick="emailReceipt(${data})" 
                                class="p-2 text-purple-600 hover:text-purple-700 hover:bg-purple-50 rounded-lg transition-colors" 
                                title="<?php echo get_phrase('email'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: '<div class="text-center py-8"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="mt-2 text-sm text-gray-500">No receipts found</p></div>',
            processing: '<div class="flex items-center justify-center py-8"><svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>'
        }
    });
}

function applyFilters() {
    receiptsTable.ajax.reload();
}

function viewReceipt(receiptId) {
    loadModalContent('previewModal', '<?php echo site_url('finance/receipts/print/'); ?>' + receiptId, '<i class="fa fa-eye"></i> <?php echo get_phrase('receipt_preview'); ?>');
}

function printReceiptDirect(receiptId) {
    window.open('<?php echo site_url('finance/receipts/print/'); ?>' + receiptId, '_blank');
}

function emailReceipt(receiptId) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_email'); ?>',
        '<?php echo get_phrase('send_receipt_to_parent_email'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('sending'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/receipts/email/'); ?>' + receiptId, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
                if(data.status === 'success') {
                    setTimeout(() => receiptsTable.ajax.reload(), 2000);
                }
            });
        },
        '<?php echo get_phrase('send'); ?>',
        'primary'
    );
}
</script>
