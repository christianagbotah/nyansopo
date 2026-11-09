<style>
@import url('<?php echo base_url(); ?>assets/cdn/fonts/inter.css');
* { font-family: 'Inter', sans-serif; }
.modern-container { max-width: 1600px; margin: 0 auto; padding: 24px; }
.page-header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(245, 158, 11, 0.2); }
.page-title { font-size: 32px; font-weight: 700; color: white; margin: 0 0 8px 0; letter-spacing: -0.5px; }
.page-subtitle { font-size: 16px; color: rgba(255,255,255,0.9); margin: 0; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px; }
.stat-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; border-left: 4px solid; transition: all 0.3s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.stat-card.total { border-left-color: #3b82f6; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%); }
.stat-card.pending { border-left-color: #f59e0b; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%); }
.stat-card.approved { border-left-color: #10b981; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); }
.stat-card.rejected { border-left-color: #ef4444; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%); }
.stat-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.stat-value { font-size: 32px; font-weight: 700; color: #111827; margin-bottom: 4px; }
.stat-meta { font-size: 13px; color: #9ca3af; }
.data-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.card-title { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
.btn-modern { padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
.filter-bar { background: #f9fafb; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
.filter-bar .form-group { margin-bottom: 0; }
.expense-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
.table-modern thead th { background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%); padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
.table-modern tbody tr { transition: all 0.2s; }
.table-modern tbody tr:hover { background: #f9fafb; }
.table-modern tbody td { padding: 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
</style>

<div class="modern-container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">💰 Expense Management</h1>
                <p class="page-subtitle">Track and manage all school expenses with approval workflow</p>
            </div>
            <button onclick="showAddExpenseModal()" class="btn-modern btn-primary">
                <i class="fa fa-plus"></i> Add Expense
            </button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-label">📊 Total Expenses</div>
            <div class="stat-value" id="totalExpenses">GHS 0.00</div>
            <div class="stat-meta">This Month</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-label">⏳ Pending Approval</div>
            <div class="stat-value" id="pendingExpenses">GHS 0.00</div>
            <div class="stat-meta"><span id="pendingCount">0</span> items</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-label">✅ Approved</div>
            <div class="stat-value" id="approvedExpenses">GHS 0.00</div>
            <div class="stat-meta"><span id="approvedCount">0</span> items</div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-label">❌ Rejected</div>
            <div class="stat-value" id="rejectedExpenses">GHS 0.00</div>
            <div class="stat-meta"><span id="rejectedCount">0</span> items</div>
        </div>
    </div>

    <div class="data-card">
        <div class="filter-bar">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="date" id="startDate" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <input type="date" id="endDate" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Category</label>
                        <select id="categoryFilter" class="form-control">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select id="statusFilter" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
            <button onclick="applyFilters()" class="btn-modern btn-primary">
                <i class="fa fa-filter"></i> Apply Filters
            </button>
            <button onclick="exportExpenses()" class="btn-modern btn-success">
                <i class="fa fa-file-excel"></i> Export
            </button>
        </div>

        <table class="table-modern" id="expensesTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Vendor</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="expensesBody"></tbody>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New Expense</h4>
            </div>
            <form id="addExpenseForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date *</label>
                                <input type="date" name="expense_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor/Supplier</label>
                                <input type="text" name="vendor" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Number</label>
                                <input type="text" name="reference_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Attach Receipt/Invoice</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadExpenses();
    loadStats();
    loadCategories();
});

function loadExpenses() {
    showAjaxModal_alert('Loading...', 'loading');
    $.ajax({
        url: '<?php echo site_url("admin/expense_management/get_expenses"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        $('.close')[0].click();
        if(response.status === 'success') {
            renderExpenses(response.expenses);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function renderExpenses(expenses) {
    let html = '';
    expenses.forEach(function(exp) {
        let statusBadge = `<span class="expense-badge badge-${exp.status}">${exp.status.toUpperCase()}</span>`;
        let actions = '';
        
        if(exp.status === 'pending') {
            actions = `
                <button onclick="approveExpense(${exp.id})" class="btn btn-sm btn-success" title="Approve">
                    <i class="fa fa-check"></i>
                </button>
                <button onclick="rejectExpense(${exp.id})" class="btn btn-sm btn-danger" title="Reject">
                    <i class="fa fa-times"></i>
                </button>
            `;
        }
        
        actions += `
            <button onclick="viewExpense(${exp.id})" class="btn btn-sm btn-info" title="View">
                <i class="fa fa-eye"></i>
            </button>
        `;
        
        html += `
            <tr>
                <td>${exp.expense_date}</td>
                <td>${exp.description}</td>
                <td>${exp.category_name}</td>
                <td>${exp.vendor || '-'}</td>
                <td style="text-align: right;">GHS ${parseFloat(exp.amount).toFixed(2)}</td>
                <td>${statusBadge}</td>
                <td>${exp.requested_by}</td>
                <td>${actions}</td>
            </tr>
        `;
    });
    $('#expensesBody').html(html || '<tr><td colspan="8" class="text-center">No expenses found</td></tr>');
}

function loadStats() {
    $.ajax({
        url: '<?php echo site_url("admin/expense_management/get_stats"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('#totalExpenses').text('GHS ' + response.stats.total.toFixed(2));
            $('#pendingExpenses').text('GHS ' + response.stats.pending.toFixed(2));
            $('#pendingCount').text(response.stats.pending_count);
            $('#approvedExpenses').text('GHS ' + response.stats.approved.toFixed(2));
            $('#approvedCount').text(response.stats.approved_count);
            $('#rejectedExpenses').text('GHS ' + response.stats.rejected.toFixed(2));
            $('#rejectedCount').text(response.stats.rejected_count);
        }
    });
}

function loadCategories() {
    $.ajax({
        url: '<?php echo site_url("admin/expense_management/get_categories"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            let options = '<option value="">Select Category</option>';
            response.categories.forEach(function(cat) {
                options += `<option value="${cat.id}">${cat.name}</option>`;
            });
            $('select[name="category_id"]').html(options);
            $('#categoryFilter').html('<option value="">All Categories</option>' + options);
        }
    });
}

function showAddExpenseModal() {
    $('#addExpenseModal').modal('show');
}

$('#addExpenseForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('Submitting...', 'loading');
    
    var formData = new FormData(this);
    
    $.ajax({
        url: '<?php echo site_url("admin/expense_management/create"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
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

function approveExpense(id) {
    showConfirmModal(
        'Approve Expense',
        'Are you sure you want to approve this expense?',
        function() {
            showAjaxModal_alert('Approving...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/expense_management/approve/"); ?>' + id,
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
        'Approve',
        'success'
    );
}

function rejectExpense(id) {
    showConfirmModal(
        'Reject Expense',
        'Are you sure you want to reject this expense?',
        function() {
            showAjaxModal_alert('Rejecting...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/expense_management/reject/"); ?>' + id,
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
        'Reject',
        'danger'
    );
}

function applyFilters() {
    // Implement filter logic
    loadExpenses();
}

function exportExpenses() {
    window.location.href = '<?php echo site_url("admin/expense_management/export"); ?>';
}
</script>
