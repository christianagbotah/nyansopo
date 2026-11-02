<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Budget Widget -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium mb-1">Active Budgets</p>
                <h3 class="text-3xl font-bold" id="widget_budget_count">0</h3>
                <p class="text-blue-100 text-xs mt-2">Total: <span id="widget_budget_amount">GHS 0</span></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fa fa-chart-pie text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Expense Widget -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-medium mb-1">This Month</p>
                <h3 class="text-3xl font-bold" id="widget_expense_count">0</h3>
                <p class="text-green-100 text-xs mt-2">Spent: <span id="widget_expense_amount">GHS 0</span></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fa fa-credit-card text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Widget -->
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-sm font-medium mb-1">Pending Approvals</p>
                <h3 class="text-3xl font-bold" id="widget_pending_count">0</h3>
                <p class="text-orange-100 text-xs mt-2">Requires Action</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fa fa-clock text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Access Widget -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm font-medium mb-1">Quick Access</p>
                <h3 class="text-xl font-bold">Financial</h3>
                <p class="text-purple-100 text-xs mt-2">Management</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fa fa-bolt text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Expenses -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-bold text-gray-800">Recent Expenses</h4>
        <a href="<?php echo base_url('expense_management'); ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All →</a>
    </div>
    <div id="widget_recent_expenses" class="space-y-3"></div>
</div>

<script>
$(document).ready(function() {
    loadFinancialWidgets();
});

function loadFinancialWidgets() {
    $.ajax({
        url: '<?php echo base_url(); ?>financial_dashboard/get_widgets',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const data = response.data;
                
                $('#widget_budget_count').text(data.budget_summary.total_budgets);
                $('#widget_budget_amount').text('GHS ' + parseFloat(data.budget_summary.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
                
                $('#widget_expense_count').text(data.expense_summary.monthly_expenses);
                $('#widget_expense_amount').text('GHS ' + parseFloat(data.expense_summary.monthly_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
                
                $('#widget_pending_count').text(data.pending_approvals.pending_count);
                
                renderRecentExpenses(data.recent_expenses);
            }
        }
    });
}

function renderRecentExpenses(expenses) {
    const container = $('#widget_recent_expenses');
    container.empty();
    
    if (expenses.length === 0) {
        container.html('<p class="text-gray-500 text-center py-4">No recent expenses</p>');
        return;
    }
    
    expenses.forEach(exp => {
        const html = `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex-1">
                    <p class="font-medium text-gray-800">${exp.description}</p>
                    <p class="text-sm text-gray-500">${exp.category} • ${exp.expense_date}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-800">GHS ${parseFloat(exp.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                </div>
            </div>
        `;
        container.append(html);
    });
}
</script>
