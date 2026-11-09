<?php include APPPATH . 'views/backend/components/enterprise_ui_components.php'; ?>

<?php render_page_header(
    get_phrase('bank_accounts'),
    get_phrase('manage_your_bank_accounts_and_balances'),
    '',
    '<button class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 rounded-lg shadow-lg" onclick="showAddBankModal()"><i class="fa fa-plus mr-2"></i>' . get_phrase('add_bank_account') . '</button>'
); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="table-responsive">
        <table id="bankAccountsTable" class="table table-hover" style="width:100%">
            <thead class="bg-light">
                <tr>
                    <th class="border-0"><?php echo get_phrase('bank_name'); ?></th>
                    <th class="border-0"><?php echo get_phrase('account_number'); ?></th>
                    <th class="border-0"><?php echo get_phrase('account_name'); ?></th>
                    <th class="border-0 text-right"><?php echo get_phrase('balance'); ?></th>
                    <th class="border-0 text-center"><?php echo get_phrase('status'); ?></th>
                    <th class="border-0 text-center"><?php echo get_phrase('actions'); ?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div id="addBankModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('add_bank_account'); ?></h4>
            </div>
            <form id="addBankForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo get_phrase('bank_name'); ?></label>
                        <input type="text" name="bank_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('account_number'); ?></label>
                        <input type="text" name="account_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('account_name'); ?></label>
                        <input type="text" name="account_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('branch'); ?></label>
                        <input type="text" name="branch" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('opening_balance'); ?></label>
                        <input type="number" name="opening_balance" class="form-control" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo get_phrase('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#bankAccountsTable').DataTable({
        ajax: '<?php echo site_url("accounts/bank_accounts/get_data"); ?>',
        columns: [
            { data: 'bank_name' },
            { data: 'account_number' },
            { data: 'account_name' },
            { data: 'balance', render: $.fn.dataTable.render.number(',', '.', 2) },
            { data: 'status' },
            { data: 'actions', orderable: false }
        ]
    });

    $('#addBankForm').submit(function(e) {
        e.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('<?php echo get_phrase("creating"); ?>...', 'loading');
        
        $.ajax({
            url: '<?php echo site_url("accounts/bank_accounts/create"); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
        });
    });
});

function showAddBankModal() {
    $('#addBankModal').modal('show');
}
</script>
