<style>
@import url('<?php echo base_url(); ?>assets/cdn/fonts/inter.css');
* { font-family: 'Inter', sans-serif; }
.modern-container { max-width: 1600px; margin: 0 auto; padding: 24px; }
.page-header { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(6, 182, 212, 0.2); }
.page-title { font-size: 32px; font-weight: 700; color: white; margin: 0 0 8px 0; letter-spacing: -0.5px; }
.page-subtitle { font-size: 16px; color: rgba(255,255,255,0.9); margin: 0; }
.budget-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; margin-bottom: 20px; transition: all 0.3s; }
.budget-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.budget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f3f4f6; }
.budget-title { font-size: 20px; font-weight: 700; color: #111827; }
.budget-period { font-size: 14px; color: #6b7280; }
.budget-progress-container { margin: 20px 0; }
.budget-progress-bar { height: 32px; background: #e5e7eb; border-radius: 16px; overflow: hidden; position: relative; }
.budget-progress-fill { height: 100%; background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%); transition: width 0.5s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; }
.budget-progress-fill.warning { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }
.budget-progress-fill.danger { background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%); }
.budget-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; }
.budget-stat { text-align: center; padding: 16px; background: #f9fafb; border-radius: 12px; }
.budget-stat-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
.budget-stat-value { font-size: 24px; font-weight: 700; color: #111827; }
.budget-line { background: #f9fafb; padding: 16px; border-radius: 12px; margin-bottom: 12px; border-left: 4px solid #3b82f6; }
.budget-line-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.budget-line-name { font-weight: 600; color: #111827; }
.budget-line-amount { font-weight: 700; color: #3b82f6; }
.budget-line-progress { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.budget-line-progress-fill { height: 100%; background: #3b82f6; transition: width 0.3s; }
.btn-modern { padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
.status-badge { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.status-draft { background: #e5e7eb; color: #374151; }
.status-active { background: #d1fae5; color: #065f46; }
.status-completed { background: #dbeafe; color: #1e40af; }
.status-exceeded { background: #fee2e2; color: #991b1b; }
</style>

<div class="modern-container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">📊 Budget Planning & Tracking</h1>
                <p class="page-subtitle">Plan, monitor, and control your school's financial budgets</p>
            </div>
            <button onclick="showCreateBudgetModal()" class="btn-modern btn-primary">
                <i class="fa fa-plus"></i> Create Budget
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="budget-stat">
                <div class="budget-stat-label">Total Budgets</div>
                <div class="budget-stat-value" id="totalBudgets">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="budget-stat">
                <div class="budget-stat-label">Active Budgets</div>
                <div class="budget-stat-value" id="activeBudgets">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="budget-stat">
                <div class="budget-stat-label">Total Allocated</div>
                <div class="budget-stat-value" id="totalAllocated">GHS 0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="budget-stat">
                <div class="budget-stat-label">Total Utilized</div>
                <div class="budget-stat-value" id="totalUtilized">GHS 0</div>
            </div>
        </div>
    </div>

    <div id="budgetsList"></div>
</div>

<!-- Create Budget Modal -->
<div id="createBudgetModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Create New Budget</h4>
            </div>
            <form id="createBudgetForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Budget Name *</label>
                                <input type="text" name="budget_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fiscal Year *</label>
                                <select name="fiscal_year" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php 
                                    $current_year = date('Y');
                                    for($i = $current_year - 1; $i <= $current_year + 2; $i++) {
                                        echo "<option value='$i'>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date *</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Budget Lines</h5>
                    <div id="budgetLinesContainer">
                        <div class="budget-line-item mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label>Category *</label>
                                    <select name="category[]" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <option value="salaries">Salaries & Wages</option>
                                        <option value="utilities">Utilities</option>
                                        <option value="supplies">Supplies & Materials</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="transport">Transportation</option>
                                        <option value="food">Food & Catering</option>
                                        <option value="equipment">Equipment</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label>Allocated Amount *</label>
                                    <input type="number" name="amount[]" class="form-control" step="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-block" onclick="removeBudgetLine(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" onclick="addBudgetLine()">
                        <i class="fa fa-plus"></i> Add Line
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadBudgets();
    loadStats();
});

function loadBudgets() {
    showAjaxModal_alert('Loading...', 'loading');
    $.ajax({
        url: '<?php echo site_url("admin/budget_planning/get_budgets"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        $('.close')[0].click();
        if(response.status === 'success') {
            renderBudgets(response.budgets);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function renderBudgets(budgets) {
    let html = '';
    budgets.forEach(function(budget) {
        let percentage = (budget.utilized / budget.total_amount * 100).toFixed(1);
        let progressClass = percentage > 90 ? 'danger' : (percentage > 75 ? 'warning' : '');
        let statusClass = budget.status === 'active' ? 'status-active' : 
                         budget.status === 'completed' ? 'status-completed' : 
                         budget.status === 'exceeded' ? 'status-exceeded' : 'status-draft';
        
        html += `
            <div class="budget-card">
                <div class="budget-header">
                    <div>
                        <div class="budget-title">${budget.budget_name}</div>
                        <div class="budget-period">${budget.start_date} to ${budget.end_date}</div>
                    </div>
                    <span class="status-badge ${statusClass}">${budget.status.toUpperCase()}</span>
                </div>
                
                <div class="budget-progress-container">
                    <div class="budget-progress-bar">
                        <div class="budget-progress-fill ${progressClass}" style="width: ${Math.min(percentage, 100)}%">
                            ${percentage}% Utilized
                        </div>
                    </div>
                </div>
                
                <div class="budget-stats">
                    <div class="budget-stat">
                        <div class="budget-stat-label">Allocated</div>
                        <div class="budget-stat-value">GHS ${parseFloat(budget.total_amount).toFixed(2)}</div>
                    </div>
                    <div class="budget-stat">
                        <div class="budget-stat-label">Utilized</div>
                        <div class="budget-stat-value">GHS ${parseFloat(budget.utilized).toFixed(2)}</div>
                    </div>
                    <div class="budget-stat">
                        <div class="budget-stat-label">Remaining</div>
                        <div class="budget-stat-value">GHS ${parseFloat(budget.remaining).toFixed(2)}</div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Budget Lines:</h6>
                    ${renderBudgetLines(budget.lines)}
                </div>
                
                <div class="mt-3">
                    <button onclick="viewBudgetDetails(${budget.id})" class="btn-modern btn-primary">
                        <i class="fa fa-eye"></i> View Details
                    </button>
                    <button onclick="editBudget(${budget.id})" class="btn-modern btn-warning">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    ${budget.status === 'draft' ? `
                        <button onclick="activateBudget(${budget.id})" class="btn-modern btn-success">
                            <i class="fa fa-check"></i> Activate
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    });
    $('#budgetsList').html(html || '<p class="text-center text-muted">No budgets created yet</p>');
}

function renderBudgetLines(lines) {
    let html = '';
    lines.forEach(function(line) {
        let percentage = (line.utilized / line.amount * 100).toFixed(1);
        html += `
            <div class="budget-line">
                <div class="budget-line-header">
                    <span class="budget-line-name">${line.category_name}</span>
                    <span class="budget-line-amount">GHS ${parseFloat(line.amount).toFixed(2)}</span>
                </div>
                <div class="budget-line-progress">
                    <div class="budget-line-progress-fill" style="width: ${Math.min(percentage, 100)}%"></div>
                </div>
                <small class="text-muted">Utilized: GHS ${parseFloat(line.utilized).toFixed(2)} (${percentage}%)</small>
            </div>
        `;
    });
    return html;
}

function loadStats() {
    $.ajax({
        url: '<?php echo site_url("admin/budget_planning/get_stats"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('#totalBudgets').text(response.stats.total_budgets);
            $('#activeBudgets').text(response.stats.active_budgets);
            $('#totalAllocated').text('GHS ' + parseFloat(response.stats.total_allocated).toFixed(2));
            $('#totalUtilized').text('GHS ' + parseFloat(response.stats.total_utilized).toFixed(2));
        }
    });
}

function showCreateBudgetModal() {
    $('#createBudgetModal').modal('show');
}

function addBudgetLine() {
    let template = $('.budget-line-item:first').clone();
    template.find('input, select').val('');
    $('#budgetLinesContainer').append(template);
}

function removeBudgetLine(btn) {
    if($('.budget-line-item').length > 1) {
        $(btn).closest('.budget-line-item').remove();
    }
}

$('#createBudgetForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('Creating...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/budget_planning/create"); ?>',
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
        showAjaxModal_alert('An error occurred', 'error');
    });
});

function activateBudget(id) {
    showConfirmModal(
        'Activate Budget',
        'Are you sure you want to activate this budget? Once activated, it will start tracking expenses.',
        function() {
            showAjaxModal_alert('Activating...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/budget_planning/activate/"); ?>' + id,
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
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Activate',
        'success'
    );
}
</script>
