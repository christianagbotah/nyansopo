<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900"><?php echo get_phrase('advanced_reports'); ?></h1>
        <div class="flex gap-2">
            <input type="date" id="startDate" class="px-4 py-2 border rounded-lg" value="<?php echo date('Y-m-01'); ?>">
            <input type="date" id="endDate" class="px-4 py-2 border rounded-lg" value="<?php echo date('Y-m-t'); ?>">
            <button onclick="loadReports()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="mdi mdi-refresh mr-2"></i><?php echo get_phrase('load'); ?>
            </button>
        </div>
    </div>

    <!-- Financial Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6" id="summaryCards">
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
            <p class="text-gray-500 text-sm mb-2"><?php echo get_phrase('total_revenue'); ?></p>
            <h3 class="text-2xl font-bold text-gray-800" id="totalRevenue">GHS 0.00</h3>
        </div>
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm mb-2"><?php echo get_phrase('total_collected'); ?></p>
            <h3 class="text-2xl font-bold text-gray-800" id="totalCollected">GHS 0.00</h3>
        </div>
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
            <p class="text-gray-500 text-sm mb-2"><?php echo get_phrase('total_expenses'); ?></p>
            <h3 class="text-2xl font-bold text-gray-800" id="totalExpenses">GHS 0.00</h3>
        </div>
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
            <p class="text-gray-500 text-sm mb-2"><?php echo get_phrase('net_income'); ?></p>
            <h3 class="text-2xl font-bold text-gray-800" id="netIncome">GHS 0.00</h3>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold"><?php echo get_phrase('cash_flow'); ?></h3>
                <button onclick="exportReport('cash_flow')" class="text-sm text-blue-600 hover:underline">
                    <i class="mdi mdi-download"></i> Export
                </button>
            </div>
            <canvas id="cashFlowChart" height="100"></canvas>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold"><?php echo get_phrase('expense_by_category'); ?></h3>
                <button onclick="exportReport('category_expenses')" class="text-sm text-blue-600 hover:underline">
                    <i class="mdi mdi-download"></i> Export
                </button>
            </div>
            <canvas id="categoryChart" height="100"></canvas>
        </div>
    </div>

    <!-- Aging Report -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold"><?php echo get_phrase('aging_report'); ?></h3>
                <button onclick="exportReport('aging')" class="text-sm text-blue-600 hover:underline">
                    <i class="mdi mdi-download"></i> Export
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-base" id="agingTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('student'); ?></th>
                            <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('class'); ?></th>
                            <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('invoice'); ?></th>
                            <th class="px-4 py-3 text-right font-semibold"><?php echo get_phrase('amount_due'); ?></th>
                            <th class="px-4 py-3 text-right font-semibold"><?php echo get_phrase('days_overdue'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
let cashFlowChart, categoryChart;

$(document).ready(function() {
    loadReports();
});

function loadReports() {
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    
    loadFinancialSummary(startDate, endDate);
    loadCashFlow(startDate, endDate);
    loadCategoryExpenses(startDate, endDate);
    loadAgingReport();
}

function loadFinancialSummary(startDate, endDate) {
    $.get('<?php echo site_url('advanced_reports/financial_summary'); ?>', {
        start_date: startDate,
        end_date: endDate
    }, function(response) {
        const data = typeof response === 'string' ? JSON.parse(response) : response;
        if(data.status === 'success') {
            $('#totalRevenue').text('GHS ' + formatNumber(data.data.revenue.total_billed));
            $('#totalCollected').text('GHS ' + formatNumber(data.data.revenue.total_collected));
            $('#totalExpenses').text('GHS ' + formatNumber(data.data.expenses.approved_amount));
            
            const netIncome = parseFloat(data.data.revenue.total_collected) - parseFloat(data.data.expenses.approved_amount);
            $('#netIncome').text('GHS ' + formatNumber(netIncome));
        }
    });
}

function loadCashFlow(startDate, endDate) {
    $.get('<?php echo site_url('advanced_reports/cash_flow'); ?>', {
        start_date: startDate,
        end_date: endDate
    }, function(response) {
        const data = typeof response === 'string' ? JSON.parse(response) : response;
        if(data.status === 'success') {
            renderCashFlowChart(data.data);
        }
    });
}

function renderCashFlowChart(data) {
    const ctx = document.getElementById('cashFlowChart');
    if(cashFlowChart) cashFlowChart.destroy();
    
    cashFlowChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.period),
            datasets: [{
                label: 'Inflow',
                data: data.map(d => d.inflow),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true
            }, {
                label: 'Outflow',
                data: data.map(d => d.outflow),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function loadCategoryExpenses(startDate, endDate) {
    $.get('<?php echo site_url('advanced_reports/category_expenses'); ?>', {
        start_date: startDate,
        end_date: endDate
    }, function(response) {
        const data = typeof response === 'string' ? JSON.parse(response) : response;
        if(data.status === 'success') {
            renderCategoryChart(data.data);
        }
    });
}

function renderCategoryChart(data) {
    const ctx = document.getElementById('categoryChart');
    if(categoryChart) categoryChart.destroy();
    
    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.category),
            datasets: [{
                data: data.map(d => d.total_amount),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function loadAgingReport() {
    $.get('<?php echo site_url('advanced_reports/aging_report'); ?>', function(response) {
        const data = typeof response === 'string' ? JSON.parse(response) : response;
        if(data.status === 'success') {
            let html = '';
            data.data.forEach(row => {
                html += `<tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3">${row.student_name}<br><small class="text-gray-500">${row.student_code}</small></td>
                    <td class="px-4 py-3">${row.class_name}</td>
                    <td class="px-4 py-3">${row.invoice_code}</td>
                    <td class="px-4 py-3 text-right font-semibold text-red-600">GHS ${formatNumber(row.due)}</td>
                    <td class="px-4 py-3 text-right text-red-600">${row.days_overdue} days</td>
                </tr>`;
            });
            $('#agingTable tbody').html(html || '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No overdue invoices</td></tr>');
        }
    });
}

function exportReport(type) {
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    window.location.href = `<?php echo site_url('advanced_reports/export'); ?>/${type}?start_date=${startDate}&end_date=${endDate}`;
}

function formatNumber(num) {
    if(isNaN(num) || num === null) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>
