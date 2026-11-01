<?php $currency = get_settings('currency'); ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo get_phrase('payment_plans'); ?></h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo get_phrase('manage_installment_payment_plans'); ?></p>
        </div>
        <button onclick="loadModalContent('createModal', '<?php echo site_url('finance/payment_plans/form'); ?>', '<i class=\"fa fa-plus\"></i> <?php echo get_phrase('create_payment_plan'); ?>')" 
                class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <?php echo get_phrase('create_payment_plan'); ?>
        </button>
    </div>
</div>

<!-- Payment Plans Table Card -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table id="payment_plans_table" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('student'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('invoice_code'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('total_amount'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('installments'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('frequency'); ?></th>
                        <th scope="col" class="px-6 py-4 font-semibold"><?php echo get_phrase('start_date'); ?></th>
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
let paymentPlansTable;

$(document).ready(function() {
    initializeDataTable();
});

function initializeDataTable() {
    paymentPlansTable = $('#payment_plans_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo site_url('finance/payment_plans/get_data'); ?>',
            type: 'POST'
        },
        columns: [
            { 
                data: 'student_name',
                render: function(data, type, row) {
                    return `<div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-semibold">
                                ${data.charAt(0)}
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${data}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${row.student_code || ''}</p>
                        </div>
                    </div>`;
                }
            },
            { 
                data: 'invoice_code',
                render: function(data) {
                    return '<span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">' + data + '</span>';
                }
            },
            { 
                data: 'total_amount',
                render: function(data) {
                    return '<span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $currency; ?>' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { 
                data: 'installments',
                render: function(data, type, row) {
                    return '<span class="text-sm text-gray-600 dark:text-gray-400">' + (row.paid_installments || 0) + ' / ' + data + '</span>';
                }
            },
            { 
                data: 'frequency',
                render: function(data) {
                    const badges = {
                        'weekly': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Weekly</span>',
                        'monthly': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Monthly</span>',
                        'quarterly': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Quarterly</span>'
                    };
                    return badges[data] || data;
                }
            },
            { 
                data: 'start_date',
                render: function(data) {
                    return '<span class="text-sm text-gray-600 dark:text-gray-400">' + new Date(data).toLocaleDateString() + '</span>';
                }
            },
            {
                data: 'status',
                className: 'text-center',
                render: function(data) {
                    const badges = {
                        'active': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>',
                        'completed': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Completed</span>',
                        'cancelled': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Cancelled</span>'
                    };
                    return badges[data] || data;
                }
            },
            {
                data: 'plan_id',
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    return `<div class="flex items-center justify-center gap-1">
                        <button onclick="viewInstallments(${data})" 
                                class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors" 
                                title="<?php echo get_phrase('view_installments'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="deletePlan(${data})" 
                                class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
                                title="<?php echo get_phrase('delete'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[5, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: '<div class="text-center py-8"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="mt-2 text-sm text-gray-500">No payment plans found</p></div>',
            processing: '<div class="flex items-center justify-center py-8"><svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>'
        }
    });
}

function viewInstallments(planId) {
    loadModalContent('detailsModal', '<?php echo site_url('finance/payment_plans/get_installments/'); ?>' + planId, '<i class="fa fa-list"></i> <?php echo get_phrase('installment_schedule'); ?>');
}

function deletePlan(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_delete'); ?>',
        '<?php echo get_phrase('delete_payment_plan_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url('finance/payment_plans/delete/'); ?>' + id,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => paymentPlansTable.ajax.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}

// Handle form submission when modal loads
$(document).on('submit', '#createForm', function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('creating'); ?>...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});
</script>
