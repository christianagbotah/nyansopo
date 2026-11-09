<div class="row">
    <div class="col-md-12">
        
        <!-- Statistics Cards -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-3">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="entypo-docs"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalExpenses">0</div>
                        <div class="stat-label"><?php echo get_phrase('total_expenses'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="entypo-clock"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="pendingExpenses">0</div>
                        <div class="stat-label"><?php echo get_phrase('pending_approval'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="entypo-check"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="approvedAmount">GHS 0</div>
                        <div class="stat-label"><?php echo get_phrase('approved_amount'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-danger">
                    <div class="stat-icon"><i class="entypo-cancel"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="rejectedExpenses">0</div>
                        <div class="stat-label"><?php echo get_phrase('rejected'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Panel -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="entypo-credit-card"></i> <?php echo get_phrase('expense_management'); ?></span>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?php echo base_url('expense_management/export_expenses'); ?>" class="btn btn-info btn-sm">
                            <i class="entypo-download"></i> <?php echo get_phrase('export'); ?>
                        </a>
                        <button class="btn btn-success btn-sm" onclick="showCreateExpenseModal()">
                            <i class="entypo-plus"></i> <?php echo get_phrase('add_expense'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="padding: 20px;">
                
                <!-- Filters -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-2">
                        <select id="filterStatus" class="form-control" onchange="loadExpenses()">
                            <option value=""><?php echo get_phrase('all_status'); ?></option>
                            <option value="pending"><?php echo get_phrase('pending'); ?></option>
                            <option value="approved"><?php echo get_phrase('approved'); ?></option>
                            <option value="rejected"><?php echo get_phrase('rejected'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterCategory" class="form-control" onchange="loadExpenses()"></select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filterFromDate" class="form-control" onchange="loadExpenses()">
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filterToDate" class="form-control" onchange="loadExpenses()">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="searchExpense" class="form-control" placeholder="<?php echo get_phrase('search'); ?>..." onkeyup="filterExpenses()">
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="expensesTable">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('date'); ?></th>
                                <th><?php echo get_phrase('description'); ?></th>
                                <th><?php echo get_phrase('category'); ?></th>
                                <th><?php echo get_phrase('vendor'); ?></th>
                                <th><?php echo get_phrase('amount'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('requested_by'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="expensesTableBody"></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="expenseModalTitle"><?php echo get_phrase('add_expense'); ?></h4>
            </div>
            <form id="expenseForm" onsubmit="saveExpense(event)" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="expense_id" name="expense_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('expense_date'); ?> *</label>
                                <input type="date" class="form-control" name="expense_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('category'); ?> *</label>
                                <select class="form-control" name="category_id" required id="categorySelect"></select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?> *</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('vendor'); ?></label>
                                <input type="text" class="form-control" name="vendor">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('amount'); ?> *</label>
                                <input type="number" step="0.01" class="form-control" name="amount" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('payment_method'); ?></label>
                                <select class="form-control" name="payment_method">
                                    <option value="cash"><?php echo get_phrase('cash'); ?></option>
                                    <option value="bank_transfer"><?php echo get_phrase('bank_transfer'); ?></option>
                                    <option value="cheque"><?php echo get_phrase('cheque'); ?></option>
                                    <option value="mobile_money"><?php echo get_phrase('mobile_money'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('reference_number'); ?></label>
                                <input type="text" class="form-control" name="reference_number">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('attachment'); ?> (PDF, JPG, PNG - Max 5MB)</label>
                        <input type="file" class="form-control" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('notes'); ?></label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
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

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('reject_expense'); ?></h4>
            </div>
            <form id="rejectForm" onsubmit="rejectExpense(event)">
                <div class="modal-body">
                    <input type="hidden" id="reject_expense_id">
                    <div class="form-group">
                        <label><?php echo get_phrase('rejection_reason'); ?> *</label>
                        <textarea class="form-control" name="rejection_reason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo get_phrase('reject'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-right: 15px;
}

.stat-primary .stat-icon { background: #e3f2fd; color: #1976d2; }
.stat-warning .stat-icon { background: #fff3e0; color: #f57c00; }
.stat-success .stat-icon { background: #e8f5e9; color: #388e3c; }
.stat-danger .stat-icon { background: #ffebee; color: #d32f2f; }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
}

.expense-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3e0; color: #f57c00; }
.status-approved { background: #e8f5e9; color: #388e3c; }
.status-rejected { background: #ffebee; color: #d32f2f; }

.table-hover tbody tr:hover {
    background: #f8f9fa;
    cursor: pointer;
}

.action-btn {
    padding: 4px 8px;
    margin: 0 2px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-approve { background: #4caf50; color: white; }
.btn-approve:hover { background: #388e3c; }

.btn-reject { background: #f44336; color: white; }
.btn-reject:hover { background: #d32f2f; }
</style>

<script>
let expensesData = [];
let categoriesData = [];

$(document).ready(function() {
    loadCategories();
    loadExpenses();
    loadStatistics();
});

function loadCategories() {
    $.ajax({
        url: '<?php echo base_url(); ?>expense_management/get_categories',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                categoriesData = response.data;
                
                let options = '<option value=""><?php echo get_phrase('all_categories'); ?></option>';
                response.data.forEach(cat => {
                    options += `<option value="${cat.id}">${cat.name}</option>`;
                });
                
                $('#filterCategory').html(options);
                $('#categorySelect').html(options.replace('<?php echo get_phrase('all_categories'); ?>', '<?php echo get_phrase('select_category'); ?>'));
            }
        }
    });
}

function loadExpenses() {
    const params = {
        status: $('#filterStatus').val(),
        category: $('#filterCategory').val(),
        from_date: $('#filterFromDate').val(),
        to_date: $('#filterToDate').val()
    };
    
    $.ajax({
        url: '<?php echo base_url(); ?>expense_management/get_expenses',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                expensesData = response.data;
                renderExpenses(expensesData);
            }
        }
    });
}

function renderExpenses(expenses) {
    const tbody = $('#expensesTableBody');
    tbody.empty();
    
    if (expenses.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center text-muted"><?php echo get_phrase('no_expenses_found'); ?></td></tr>');
        return;
    }
    
    expenses.forEach(exp => {
        const row = `
            <tr>
                <td>${exp.expense_date}</td>
                <td>${exp.description}</td>
                <td>${exp.category_name}</td>
                <td>${exp.vendor || '-'}</td>
                <td><strong>GHS ${parseFloat(exp.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><span class="expense-status status-${exp.status}">${exp.status}</span></td>
                <td>${exp.requested_by_name}</td>
                <td>
                    ${exp.status === 'pending' ? `
                        <button class="action-btn btn-approve" onclick="approveExpense(${exp.id})" title="<?php echo get_phrase('approve'); ?>">
                            <i class="entypo-check"></i>
                        </button>
                        <button class="action-btn btn-reject" onclick="showRejectModal(${exp.id})" title="<?php echo get_phrase('reject'); ?>">
                            <i class="entypo-cancel"></i>
                        </button>
                    ` : ''}
                    <button class="action-btn" style="background: #2196f3; color: white;" onclick="editExpense(${exp.id})" title="<?php echo get_phrase('edit'); ?>">
                        <i class="entypo-pencil"></i>
                    </button>
                    ${exp.attachment ? `
                        <a href="<?php echo base_url(); ?>uploads/expenses/${exp.attachment}" target="_blank" class="action-btn" style="background: #9c27b0; color: white;" title="<?php echo get_phrase('view_attachment'); ?>">
                            <i class="entypo-attach"></i>
                        </a>
                    ` : ''}
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function filterExpenses() {
    const search = $('#searchExpense').val().toLowerCase();
    const filtered = expensesData.filter(e => 
        e.description.toLowerCase().includes(search) ||
        e.vendor?.toLowerCase().includes(search) ||
        e.category_name.toLowerCase().includes(search)
    );
    renderExpenses(filtered);
}

function loadStatistics() {
    $.ajax({
        url: '<?php echo base_url(); ?>expense_management/get_statistics',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const stats = response.data;
                $('#totalExpenses').text(stats.total_count);
                $('#pendingExpenses').text(stats.pending_count);
                $('#approvedAmount').text('GHS ' + parseFloat(stats.total_approved_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#rejectedExpenses').text(stats.rejected_count);
            }
        }
    });
}

function showCreateExpenseModal() {
    $('#expenseForm')[0].reset();
    $('#expense_id').val('');
    $('#expenseModalTitle').text('<?php echo get_phrase('add_expense'); ?>');
    $('#expenseModal').modal('show');
}

function saveExpense(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    const expenseId = $('#expense_id').val();
    const url = expenseId ? 
        '<?php echo base_url(); ?>expense_management/update/' + expenseId :
        '<?php echo base_url(); ?>expense_management/create';
    
    const formData = new FormData($('#expenseForm')[0]);
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => {
                    loadExpenses();
                    loadStatistics();
                }, 2000);
            }
        }
    });
}

function editExpense(expenseId) {
    const expense = expensesData.find(e => e.id == expenseId);
    if (!expense) return;
    
    $('#expense_id').val(expense.id);
    $('[name="expense_date"]').val(expense.expense_date);
    $('[name="description"]').val(expense.description);
    $('[name="category_id"]').val(expense.category_id);
    $('[name="vendor"]').val(expense.vendor);
    $('[name="amount"]').val(expense.amount);
    $('[name="payment_method"]').val(expense.payment_method);
    $('[name="reference_number"]').val(expense.reference_number);
    $('[name="notes"]').val(expense.notes);
    
    $('#expenseModalTitle').text('<?php echo get_phrase('edit_expense'); ?>');
    $('#expenseModal').modal('show');
}

function approveExpense(expenseId) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_approval'); ?>',
        '<?php echo get_phrase('are_you_sure'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('approving'); ?>...', 'loading');
            $.ajax({
                url: '<?php echo base_url(); ?>expense_management/approve/' + expenseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    showAjaxModal_alert(response.message, response.status);
                    if (response.status === 'success') {
                        setTimeout(() => {
                            loadExpenses();
                            loadStatistics();
                        }, 2000);
                    }
                }
            });
        },
        '<?php echo get_phrase('approve'); ?>',
        'success'
    );
}

function showRejectModal(expenseId) {
    $('#reject_expense_id').val(expenseId);
    $('#rejectForm')[0].reset();
    $('#rejectModal').modal('show');
}

function rejectExpense(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('rejecting'); ?>...', 'loading');
    
    const expenseId = $('#reject_expense_id').val();
    
    $.ajax({
        url: '<?php echo base_url(); ?>expense_management/reject/' + expenseId,
        type: 'POST',
        data: $('#rejectForm').serialize(),
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => {
                    loadExpenses();
                    loadStatistics();
                }, 2000);
            }
        }
    });
}
</script>
