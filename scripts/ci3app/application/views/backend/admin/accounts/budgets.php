<?php include APPPATH . 'views/backend/components/enterprise_ui_components.php'; ?>

<style>
.budget-progress { height: 24px; border-radius: 12px; background: #e5e7eb; overflow: hidden; }
.budget-progress-bar { height: 100%; background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%); transition: width 0.3s; }
.budget-line-row { background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 8px; border-left: 3px solid #3b82f6; }
</style>

<?php render_page_header(
    get_phrase('budget_management'),
    get_phrase('plan_and_track_your_financial_budgets'),
    '',
    '<button class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-pink-600 to-yellow-500 hover:from-pink-700 hover:to-yellow-600 rounded-lg shadow-lg" onclick="showAddBudgetModal()"><i class="fa fa-plus mr-2"></i>' . get_phrase('create_budget') . '</button>'
); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="table-responsive">
        <table id="budgetsTable" class="table table-hover" style="width:100%">
            <thead class="bg-light">
                <tr>
                    <th class="border-0"><?php echo get_phrase('budget_name'); ?></th>
                    <th class="border-0"><?php echo get_phrase('fiscal_year'); ?></th>
                    <th class="border-0 text-right"><?php echo get_phrase('total_amount'); ?></th>
                    <th class="border-0 text-right"><?php echo get_phrase('utilized'); ?></th>
                    <th class="border-0 text-right"><?php echo get_phrase('remaining'); ?></th>
                    <th class="border-0 text-center"><?php echo get_phrase('status'); ?></th>
                    <th class="border-0 text-center"><?php echo get_phrase('actions'); ?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div id="addBudgetModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('create_budget'); ?></h4>
            </div>
            <form id="addBudgetForm" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('budget_name'); ?></label>
                                <input type="text" name="budget_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('fiscal_year'); ?></label>
                                <select name="fiscal_year_id" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select_fiscal_year'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('start_date'); ?></label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('end_date'); ?></label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?></label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <h5><?php echo get_phrase('budget_lines'); ?></h5>
                    <div id="budgetLines">
                        <div class="budget-line budget-line-row">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="small text-muted mb-1">Account</label>
                                    <select name="account_id[]" class="form-control form-control-sm" required>
                                        <option value=""><?php echo get_phrase('select_account'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="small text-muted mb-1">Budgeted Amount</label>
                                    <input type="number" name="amount[]" class="form-control form-control-sm" placeholder="0.00" step="0.01" required>
                                </div>
                                <div class="col-md-1 text-center">
                                    <label class="small text-muted mb-1">&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-sm d-block" onclick="removeBudgetLine(this)"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" onclick="addBudgetLine()">
                        <i class="fa fa-plus"></i> <?php echo get_phrase('add_line'); ?>
                    </button>
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
    var table = $('#budgetsTable').DataTable({
        ajax: '<?php echo site_url("accounts/budgets/get_data"); ?>',
        columns: [
            { data: 'budget_name' },
            { data: 'fiscal_year' },
            { data: 'total_amount', render: $.fn.dataTable.render.number(',', '.', 2) },
            { data: 'utilized', render: $.fn.dataTable.render.number(',', '.', 2) },
            { data: 'remaining', render: $.fn.dataTable.render.number(',', '.', 2) },
            { data: 'status' },
            { data: 'actions', orderable: false }
        ]
    });

    $('#addBudgetForm').submit(function(e) {
        e.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('<?php echo get_phrase("creating"); ?>...', 'loading');
        
        $.ajax({
            url: '<?php echo site_url("accounts/budgets/create"); ?>',
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

function showAddBudgetModal() {
    $('#addBudgetModal').modal('show');
}

function addBudgetLine() {
    var line = $('.budget-line:first').clone();
    line.find('input, select').val('');
    $('#budgetLines').append(line);
}

function removeBudgetLine(btn) {
    if ($('.budget-line').length > 1) {
        $(btn).closest('.budget-line').remove();
    }
}

function approveBudget(id) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_approve"); ?>',
        '<?php echo get_phrase("approve_budget_confirm"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("approving"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("accounts/budgets/approve/"); ?>' + id,
                type: 'POST',
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
        },
        '<?php echo get_phrase("approve"); ?>',
        'success'
    );
}
</script>
