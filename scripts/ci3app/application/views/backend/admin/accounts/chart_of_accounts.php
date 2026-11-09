<?php
include APPPATH . 'views/backend/components/enterprise_ui_components.php';
$currency = get_settings('currency');
?>

<!-- Page Header -->
<?php render_page_header(
    get_phrase('chart_of_accounts'),
    get_phrase('manage_your_financial_accounts_structure')
); ?>

<!-- Action Bar -->
<div class="flex justify-between items-center mb-6">
    <div class="flex gap-2">
        <button onclick="filterByType('all')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" id="filter-all">
            <?php echo get_phrase('all_accounts'); ?>
        </button>
        <button onclick="filterByType('asset')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('assets'); ?>
        </button>
        <button onclick="filterByType('liability')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('liabilities'); ?>
        </button>
        <button onclick="filterByType('revenue')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('revenue'); ?>
        </button>
        <button onclick="filterByType('expense')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('expenses'); ?>
        </button>
    </div>
    <button onclick="loadModalContent('createModal', '<?php echo site_url('accounts/chart_of_accounts/form'); ?>', '<i class=\\\"fa fa-plus\\\"></i> <?php echo get_phrase('add_account'); ?>')" 
            class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-lg">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <?php echo get_phrase('add_account'); ?>
    </button>
</div>

<!-- Accounts Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table id="accountsTable" class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 font-semibold"><?php echo get_phrase('account_code'); ?></th>
                        <th class="px-6 py-4 font-semibold"><?php echo get_phrase('account_name'); ?></th>
                        <th class="px-6 py-4 font-semibold"><?php echo get_phrase('type'); ?></th>
                        <th class="px-6 py-4 font-semibold text-right"><?php echo get_phrase('balance'); ?></th>
                        <th class="px-6 py-4 font-semibold text-center"><?php echo get_phrase('status'); ?></th>
                        <th class="px-6 py-4 font-semibold text-center"><?php echo get_phrase('actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
let accountsTable;
const currency = '<?php echo $currency; ?>';

$(document).ready(function() {
    accountsTable = $('#accountsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo site_url("accounts/chart_of_accounts/get_data"); ?>',
            type: 'POST'
        },
        columns: [
            { 
                data: 'account_code',
                render: function(data) {
                    return '<span class="font-mono font-semibold text-gray-900">' + data + '</span>';
                }
            },
            { data: 'account_name' },
            { 
                data: 'account_type',
                render: function(data) {
                    const badges = {
                        'asset': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Asset</span>',
                        'liability': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Liability</span>',
                        'equity': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Equity</span>',
                        'revenue': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Revenue</span>',
                        'expense': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Expense</span>'
                    };
                    return badges[data] || data;
                }
            },
            { 
                data: 'balance',
                className: 'text-right',
                render: function(data) {
                    return '<span class="font-semibold text-gray-900">' + currency + parseFloat(data).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</span>';
                }
            },
            {
                data: 'status',
                className: 'text-center',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>' :
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>';
                }
            },
            {
                data: 'account_id',
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    return `<div class="flex items-center justify-center gap-1">
                        <button onclick="editAccount(${data})" class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="deleteAccount(${data})" class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });
});

function filterByType(type) {
    if(type === 'all') {
        accountsTable.column(2).search('').draw();
    } else {
        accountsTable.column(2).search(type).draw();
    }
}

function editAccount(id) {
    loadModalContent('createModal', '<?php echo site_url("accounts/chart_of_accounts/form/"); ?>' + id, '<i class="fa fa-edit"></i> <?php echo get_phrase("edit_account"); ?>');
}

function deleteAccount(id) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_delete"); ?>',
        '<?php echo get_phrase("delete_account_confirmation"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("deleting"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("accounts/chart_of_accounts/delete/"); ?>' + id,
                type: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => accountsTable.ajax.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("delete"); ?>',
        'danger'
    );
}
</script>
