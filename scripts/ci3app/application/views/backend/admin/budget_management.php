<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="entypo-chart-bar"></i> <?php echo get_phrase('budget_management'); ?></span>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?php echo base_url('budget_management/export_budgets'); ?>" class="btn btn-info btn-sm">
                            <i class="entypo-download"></i> <?php echo get_phrase('export'); ?>
                        </a>
                        <button class="btn btn-success btn-sm" onclick="showCreateBudgetModal()">
                            <i class="entypo-plus"></i> <?php echo get_phrase('create_budget'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="padding: 20px;">
                
                <!-- Filter Section -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <select id="filterStatus" class="form-control" onchange="loadBudgets()">
                            <option value=""><?php echo get_phrase('all_status'); ?></option>
                            <option value="draft"><?php echo get_phrase('draft'); ?></option>
                            <option value="approved"><?php echo get_phrase('approved'); ?></option>
                            <option value="active"><?php echo get_phrase('active'); ?></option>
                            <option value="closed"><?php echo get_phrase('closed'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterYear" class="form-control" onchange="loadBudgets()">
                            <option value=""><?php echo get_phrase('all_years'); ?></option>
                            <?php populate_academic_year('yes'); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" id="searchBudget" class="form-control" placeholder="<?php echo get_phrase('search_budgets'); ?>..." onkeyup="filterBudgets()">
                    </div>
                </div>

                <!-- Budgets Grid -->
                <div id="budgetsContainer" class="row"></div>

            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Budget Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="budgetModalTitle"><?php echo get_phrase('create_budget'); ?></h4>
            </div>
            <form id="budgetForm" onsubmit="saveBudget(event)">
                <div class="modal-body">
                    <input type="hidden" id="budget_id" name="budget_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('budget_name'); ?> *</label>
                                <input type="text" class="form-control" name="budget_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('fiscal_year'); ?> *</label>
                                <select class="form-control" name="fiscal_year" required>
                                    <?php populate_academic_year('yes'); ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('start_date'); ?> *</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('end_date'); ?> *</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                        </div>
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

<!-- Budget Details Modal -->
<div class="modal fade" id="budgetDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" style="width: 95%;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title" id="budgetDetailsTitle"></h4>
            </div>
            <div class="modal-body" id="budgetDetailsContent" style="padding: 30px;"></div>
        </div>
    </div>
</div>

<!-- Budget Line Modal -->
<div class="modal fade" id="budgetLineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('add_budget_line'); ?></h4>
            </div>
            <form id="budgetLineForm" onsubmit="saveBudgetLine(event)">
                <div class="modal-body">
                    <input type="hidden" id="line_budget_id" name="budget_id">
                    <input type="hidden" id="budget_line_id" name="budget_line_id">
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('account'); ?> *</label>
                        <select class="form-control select2" name="account_id" required id="account_select"></select>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('budgeted_amount'); ?> *</label>
                        <input type="number" step="0.01" class="form-control" name="budgeted_amount" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('notes'); ?></label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
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

<style>
.budget-card {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.budget-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.budget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.budget-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.budget-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-draft { background: #f0f0f0; color: #666; }
.status-approved { background: #e3f2fd; color: #1976d2; }
.status-active { background: #e8f5e9; color: #388e3c; }
.status-closed { background: #ffebee; color: #d32f2f; }

.budget-amount {
    font-size: 24px;
    font-weight: 700;
    color: #667eea;
    margin: 10px 0;
}

.budget-progress {
    margin: 15px 0;
}

.progress {
    height: 8px;
    border-radius: 10px;
    background: #f0f0f0;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}

.budget-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #666;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.budget-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.btn-action {
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-view { background: #667eea; color: white; }
.btn-view:hover { background: #5568d3; }

.btn-edit { background: #f59e0b; color: white; }
.btn-edit:hover { background: #d97706; }

.btn-delete { background: #ef4444; color: white; }
.btn-delete:hover { background: #dc2626; }

.budget-line-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.line-info {
    flex: 1;
}

.line-account {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
}

.line-code {
    font-size: 12px;
    color: #666;
}

.line-amounts {
    display: flex;
    gap: 30px;
    align-items: center;
}

.amount-box {
    text-align: center;
}

.amount-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.amount-value {
    font-size: 16px;
    font-weight: 700;
}

.amount-budgeted { color: #667eea; }
.amount-actual { color: #10b981; }
.amount-variance { color: #f59e0b; }

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.summary-item {
    text-align: center;
}

.summary-label {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.summary-value {
    font-size: 28px;
    font-weight: 700;
}
</style>

<script>
let budgetsData = [];
let accountsData = [];

$(document).ready(function() {
    loadBudgets();
    loadAccounts();
});

function loadBudgets() {
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo base_url(); ?>budget_management/get_budgets',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('.close')[0].click();
            if (response.status === 'success') {
                budgetsData = response.data;
                renderBudgets(budgetsData);
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_loading_data'); ?>', 'error');
        }
    });
}

function renderBudgets(budgets) {
    const container = $('#budgetsContainer');
    container.empty();
    
    if (budgets.length === 0) {
        container.html('<div class="col-md-12"><p class="text-center text-muted"><?php echo get_phrase('no_budgets_found'); ?></p></div>');
        return;
    }
    
    budgets.forEach(budget => {
        const utilPct = budget.utilization_pct || 0;
        const progressColor = utilPct > 90 ? 'danger' : (utilPct > 75 ? 'warning' : 'success');
        
        const card = `
            <div class="col-md-4">
                <div class="budget-card">
                    <div class="budget-header">
                        <div class="budget-title">${budget.budget_name}</div>
                        <span class="budget-status status-${budget.status}">${budget.status}</span>
                    </div>
                    
                    <div class="budget-amount">GHS ${parseFloat(budget.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    
                    <div class="budget-progress">
                        <div class="progress">
                            <div class="progress-bar progress-bar-${progressColor}" style="width: ${utilPct}%"></div>
                        </div>
                        <small style="color: #666;">${utilPct}% <?php echo get_phrase('utilized'); ?></small>
                    </div>
                    
                    <div class="budget-meta">
                        <span><i class="entypo-calendar"></i> ${budget.fiscal_year}</span>
                        <span><i class="entypo-user"></i> ${budget.created_by_name || 'Admin'}</span>
                    </div>
                    
                    <div class="budget-actions">
                        <button class="btn-action btn-view" onclick="viewBudgetDetails(${budget.budget_id})">
                            <i class="entypo-eye"></i> <?php echo get_phrase('view'); ?>
                        </button>
                        <button class="btn-action btn-edit" onclick="editBudget(${budget.budget_id})">
                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
                        </button>
                        ${budget.status === 'draft' ? `
                        <button class="btn-action btn-delete" onclick="deleteBudget(${budget.budget_id})">
                            <i class="entypo-trash"></i> <?php echo get_phrase('delete'); ?>
                        </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        container.append(card);
    });
}

function filterBudgets() {
    const search = $('#searchBudget').val().toLowerCase();
    const status = $('#filterStatus').val();
    const year = $('#filterYear').val();
    
    const filtered = budgetsData.filter(b => {
        const matchSearch = b.budget_name.toLowerCase().includes(search);
        const matchStatus = !status || b.status === status;
        const matchYear = !year || b.fiscal_year === year;
        return matchSearch && matchStatus && matchYear;
    });
    
    renderBudgets(filtered);
}

function showCreateBudgetModal() {
    $('#budgetForm')[0].reset();
    $('#budget_id').val('');
    $('#budgetModalTitle').text('<?php echo get_phrase('create_budget'); ?>');
    $('#budgetModal').modal('show');
}

function saveBudget(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    const budgetId = $('#budget_id').val();
    const url = budgetId ? 
        '<?php echo base_url(); ?>budget_management/update/' + budgetId :
        '<?php echo base_url(); ?>budget_management/create';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: $('#budgetForm').serialize(),
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => loadBudgets(), 2000);
            }
        }
    });
}

function editBudget(budgetId) {
    const budget = budgetsData.find(b => b.budget_id == budgetId);
    if (!budget) return;
    
    $('#budget_id').val(budget.budget_id);
    $('[name="budget_name"]').val(budget.budget_name);
    $('[name="fiscal_year"]').val(budget.fiscal_year);
    $('[name="start_date"]').val(budget.start_date);
    $('[name="end_date"]').val(budget.end_date);
    $('[name="notes"]').val(budget.notes);
    
    $('#budgetModalTitle').text('<?php echo get_phrase('edit_budget'); ?>');
    $('#budgetModal').modal('show');
}

function deleteBudget(budgetId) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_delete'); ?>',
        '<?php echo get_phrase('are_you_sure'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>...', 'loading');
            $.ajax({
                url: '<?php echo base_url(); ?>budget_management/delete/' + budgetId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    showAjaxModal_alert(response.message, response.status);
                    if (response.status === 'success') {
                        setTimeout(() => loadBudgets(), 2000);
                    }
                }
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}

function viewBudgetDetails(budgetId) {
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo base_url(); ?>budget_management/get_budget_summary/' + budgetId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('.close')[0].click();
            if (response.status === 'success') {
                renderBudgetDetails(response.data);
            }
        }
    });
}

function renderBudgetDetails(budget) {
    $('#budgetDetailsTitle').html(`
        <i class="entypo-chart-bar"></i> ${budget.budget_name} 
        <span class="budget-status status-${budget.status}" style="margin-left: 15px;">${budget.status}</span>
    `);
    
    let html = `
        <div class="summary-card">
            <h4 style="margin-top: 0;"><?php echo get_phrase('budget_summary'); ?></h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label"><?php echo get_phrase('total_budgeted'); ?></div>
                    <div class="summary-value">GHS ${parseFloat(budget.total_budgeted || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php echo get_phrase('actual_spent'); ?></div>
                    <div class="summary-value">GHS ${parseFloat(budget.total_actual || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php echo get_phrase('variance'); ?></div>
                    <div class="summary-value">GHS ${parseFloat(budget.total_variance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php echo get_phrase('period'); ?></div>
                    <div class="summary-value" style="font-size: 16px;">${budget.start_date} to ${budget.end_date}</div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4><?php echo get_phrase('budget_lines'); ?></h4>
            <button class="btn btn-primary btn-sm" onclick="showAddLineModal(${budget.budget_id})">
                <i class="entypo-plus"></i> <?php echo get_phrase('add_line'); ?>
            </button>
        </div>
        
        <div id="budgetLinesContainer">
    `;
    
    if (budget.lines && budget.lines.length > 0) {
        budget.lines.forEach(line => {
            const variance = parseFloat(line.budgeted_amount) - parseFloat(line.actual_amount);
            html += `
                <div class="budget-line-item">
                    <div class="line-info">
                        <div class="line-account">${line.account_name}</div>
                        <div class="line-code">${line.account_code}</div>
                    </div>
                    <div class="line-amounts">
                        <div class="amount-box">
                            <div class="amount-label"><?php echo get_phrase('budgeted'); ?></div>
                            <div class="amount-value amount-budgeted">GHS ${parseFloat(line.budgeted_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="amount-box">
                            <div class="amount-label"><?php echo get_phrase('actual'); ?></div>
                            <div class="amount-value amount-actual">GHS ${parseFloat(line.actual_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="amount-box">
                            <div class="amount-label"><?php echo get_phrase('variance'); ?></div>
                            <div class="amount-value amount-variance">GHS ${variance.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-warning" onclick="editBudgetLine(${line.budget_line_id})"><i class="entypo-pencil"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteBudgetLine(${line.budget_line_id})"><i class="entypo-trash"></i></button>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += '<p class="text-center text-muted"><?php echo get_phrase('no_budget_lines_found'); ?></p>';
    }
    
    html += '</div>';
    
    $('#budgetDetailsContent').html(html);
    $('#budgetDetailsModal').modal('show');
}

function loadAccounts() {
    $.ajax({
        url: '<?php echo base_url(); ?>budget_management/get_accounts',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                accountsData = response.data;
                const select = $('#account_select');
                select.empty();
                response.data.forEach(acc => {
                    select.append(`<option value="${acc.account_id}">${acc.account_code} - ${acc.account_name}</option>`);
                });
            }
        }
    });
}

function showAddLineModal(budgetId) {
    $('#budgetLineForm')[0].reset();
    $('#line_budget_id').val(budgetId);
    $('#budget_line_id').val('');
    $('#budgetLineModal').modal('show');
}

function saveBudgetLine(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    const lineId = $('#budget_line_id').val();
    const url = lineId ? 
        '<?php echo base_url(); ?>budget_management/update_line/' + lineId :
        '<?php echo base_url(); ?>budget_management/add_line';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: $('#budgetLineForm').serialize(),
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => {
                    const budgetId = $('#line_budget_id').val();
                    viewBudgetDetails(budgetId);
                    loadBudgets();
                }, 2000);
            }
        }
    });
}

function deleteBudgetLine(lineId) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_delete'); ?>',
        '<?php echo get_phrase('are_you_sure'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>...', 'loading');
            $.ajax({
                url: '<?php echo base_url(); ?>budget_management/delete_line/' + lineId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    showAjaxModal_alert(response.message, response.status);
                    if (response.status === 'success') {
                        setTimeout(() => {
                            const budgetId = $('#line_budget_id').val();
                            viewBudgetDetails(budgetId);
                            loadBudgets();
                        }, 2000);
                    }
                }
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}
</script>
