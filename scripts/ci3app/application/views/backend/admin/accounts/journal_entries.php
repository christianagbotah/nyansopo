<?php
include APPPATH . 'views/backend/components/enterprise_ui_components.php';
$currency = get_settings('currency');
?>

<!-- Page Header -->
<?php render_page_header(
    get_phrase('journal_entries'),
    get_phrase('record_financial_transactions_with_double_entry_bookkeeping')
); ?>

<!-- Action Bar -->
<div class="flex justify-between items-center mb-6">
    <div class="flex gap-2">
        <button onclick="filterByStatus('all')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('all'); ?>
        </button>
        <button onclick="filterByStatus('draft')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('draft'); ?>
        </button>
        <button onclick="filterByStatus('posted')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <?php echo get_phrase('posted'); ?>
        </button>
    </div>
    <button onclick="loadModalContent('addJournalModal', '<?php echo site_url('accounts/journal_entries/form'); ?>', '<i class=\\\"fa fa-plus\\\"></i> <?php echo get_phrase('new_journal_entry'); ?>')" 
            class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-lg">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <?php echo get_phrase('new_journal_entry'); ?>
    </button>
</div>

<!-- Journal Entries Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table id="journalTable" class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 font-semibold"><?php echo get_phrase('entry_no'); ?></th>
                        <th class="px-6 py-4 font-semibold"><?php echo get_phrase('date'); ?></th>
                        <th class="px-6 py-4 font-semibold"><?php echo get_phrase('description'); ?></th>
                        <th class="px-6 py-4 font-semibold text-right"><?php echo get_phrase('amount'); ?></th>
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
let journalTable;

$(document).ready(function() {
    journalTable = $('#journalTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo site_url("accounts/journal_entries/get_data"); ?>',
            type: 'POST'
        },
        columns: [
            { 
                data: 'entry_number',
                render: function(data) {
                    return '<span class="font-mono font-semibold text-gray-900">' + data + '</span>';
                }
            },
            { data: 'entry_date' },
            { data: 'description' },
            { 
                data: 'total_debit',
                className: 'text-right',
                render: function(data) {
                    return '<span class="font-semibold text-gray-900"><?php echo $currency; ?>' + parseFloat(data).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</span>';
                }
            },
            {
                data: 'status',
                className: 'text-center',
                render: function(data) {
                    const badges = {
                        'draft': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Draft</span>',
                        'posted': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Posted</span>',
                        'void': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Void</span>'
                    };
                    return badges[data] || data;
                }
            },
            {
                data: 'entry_id',
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    let actions = `<div class="flex items-center justify-center gap-1">
                        <button onclick="viewEntry(${data})" class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>`;
                    if(row.status === 'draft') {
                        actions += `<button onclick="postEntry(${data})" class="p-2 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg" title="Post">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>`;
                    }
                    actions += `</div>`;
                    return actions;
                }
            }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });
});

function filterByStatus(status) {
    if(status === 'all') {
        journalTable.column(4).search('').draw();
    } else {
        journalTable.column(4).search(status).draw();
    }
}

function viewEntry(id) {
    loadModalContent('detailsModal', '<?php echo site_url("accounts/journal_entries/view/"); ?>' + id, '<i class="fa fa-eye"></i> <?php echo get_phrase("journal_entry_details"); ?>');
}

function postEntry(id) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_post"); ?>',
        '<?php echo get_phrase("post_journal_entry_confirmation"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("posting"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("accounts/journal_entries/post/"); ?>' + id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => journalTable.ajax.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("post"); ?>',
        'primary'
    );
}
</script>
