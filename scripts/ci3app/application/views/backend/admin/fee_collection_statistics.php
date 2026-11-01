<?php
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$currency = get_settings('currency') ?: '₵';
$collectors = $this->db->where_in('level', [1,2,3])->order_by('name')->get('admin')->result_array();
?>

<link href="<?php echo base_url(); ?>assets/cdn/css/flowbite.min.css" rel="stylesheet" />
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white; }
    .stats-header, .filter-card, .chart-card { page-break-inside: avoid; }
}
.print-only { display: none; }
.print-header { display: none; }
@media print {
    .print-header { display: block; text-align: center; margin-bottom: 20px; }
    .print-header h2 { margin: 0; font-size: 24px; }
    .print-header p { margin: 5px 0; color: #666; }
}

.stats-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    color: white;
}

.kpi-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e8e8e8;
    transition: all 0.3s ease;
}

.kpi-card:hover {
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
    transform: translateY(-2px);
}

.kpi-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 16px;
}

.kpi-value {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
    margin: 8px 0;
}

.kpi-label {
    font-size: 13px;
    color: #7f8c8d;
    font-weight: 600;
}

.filter-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.filter-card label {
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
    color: #2c3e50;
    font-size: 14px;
}

.filter-card .form-control {
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    transition: all 0.3s ease;
    height: 42px;
}

.filter-card .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.filter-card .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 14px;
    height: 42px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.filter-card .btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    transform: translateY(-2px);
}

.stats-header .btn {
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
}

.stats-header .btn-light {
    background: white;
    color: #667eea;
    border: 2px solid white;
}

.stats-header .btn-light:hover {
    background: rgba(255,255,255,0.9);
    transform: translateY(-2px);
}

.stats-header .btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
}

.stats-header .btn-success:hover {
    background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
    transform: translateY(-2px);
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.progress-bar-custom {
    height: 8px;
    background: #e8e8e8;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.transaction-row:hover {
    background: #f8f9fa;
}

.badge-arrears { background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-advance { background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-mixed { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Header -->
        <div class="stats-header no-print">
            <div class="row">
                <div class="col-md-8">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-bar-chart" style="font-size: 32px;"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0; font-weight: 700; font-size: 28px; color: white;">Fee Collection Statistics</h2>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 15px; color: white;">Comprehensive analytics and reports</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card no-print">
            <div class="row">
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">Date From</label>
                    <div style="position: relative;">
                        <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fa fa-calendar" style="color: #7f8c8d;"></i>
                        </div>
                        <input type="text" id="date_from" class="form-control" style="padding-left: 40px;" placeholder="Select date" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">Date To</label>
                    <div style="position: relative;">
                        <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fa fa-calendar" style="color: #7f8c8d;"></i>
                        </div>
                        <input type="text" id="date_to" class="form-control" style="padding-left: 40px;" placeholder="Select date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">Class</label>
                    <select id="class_filter" class="form-control">
                        <option value="">All Classes</option>
                        <?php echo getFullClassList(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">Collector</label>
                    <select id="collector_filter" class="form-control">
                        <option value="">All Collectors</option>
                        <?php foreach($collectors as $collector): ?>
                            <option value="<?php echo $collector['admin_id']; ?>"><?php echo $collector['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">Payment Method</label>
                    <select id="payment_method_filter" class="form-control">
                        <option value="">All Methods</option>
                        <option value="1">Cash</option>
                        <option value="2">Mobile Money</option>
                        <option value="3">Cheque</option>
                        <option value="4">Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">&nbsp;</label>
                    <button onclick="loadStatistics()" class="btn btn-primary btn-block">
                        <i class="fa fa-search"></i> Load Data
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <i class="fa fa-dollar"></i>
                    </div>
                    <div class="kpi-label">Total Collected</div>
                    <div class="kpi-value" id="kpi_total"><?php echo $currency; ?> 0.00</div>
                    <div style="font-size: 12px; color: #27ae60;" id="kpi_growth">
                        <i class="fa fa-arrow-up"></i> 0% vs previous
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                        <i class="fa fa-line-chart"></i>
                    </div>
                    <div class="kpi-label">Collection Rate</div>
                    <div class="kpi-value" id="kpi_rate">0%</div>
                    <div style="font-size: 12px; color: #7f8c8d;" id="kpi_arrears_cleared"><?php echo $currency; ?> 0 arrears cleared</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="kpi-label">Outstanding Arrears</div>
                    <div class="kpi-value" id="kpi_arrears"><?php echo $currency; ?> 0.00</div>
                    <div style="font-size: 12px; color: #e74c3c;">Requires attention</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white;">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="kpi-label">Students Paid</div>
                    <div class="kpi-value" id="kpi_students">0</div>
                    <div style="font-size: 12px; color: #7f8c8d;" id="kpi_transactions">0 transactions</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row" style="margin-top: 24px;">
            <div class="col-md-6">
                <div class="chart-card">
                    <h4 style="margin: 0 0 20px 0; font-weight: 600;">Fee Type Breakdown</h4>
                    <canvas id="feeBreakdownChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <h4 style="margin: 0 0 20px 0; font-weight: 600;">Payment Type Distribution</h4>
                    <canvas id="paymentTypeChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Collection by Class & Collector -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-card">
                    <h4 style="margin: 0 0 20px 0; font-weight: 600;">Collection by Class</h4>
                    <div id="class_breakdown"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <h4 style="margin: 0 0 20px 0; font-weight: 600;">Collection by Collector</h4>
                    <div id="collector_breakdown"></div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="chart-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0; font-weight: 600;">Recent Transactions</h4>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" id="transaction_search" placeholder="Search..." class="form-control no-print" style="width: 250px;">
                    <button onclick="printTable()" class="btn btn-light no-print" style="height: 42px;">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <button onclick="exportTableToExcel()" class="btn btn-success no-print" style="height: 42px;">
                        <i class="fa fa-file-excel"></i> Export
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="transactions_table">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th class="text-right">Feeding</th>
                            <th class="text-right">Classes</th>
                            <th class="text-right">Transport</th>
                            <th class="text-right">Total</th>
                            <th>Method</th>
                            <th>Collector</th>
                        </tr>
                    </thead>
                    <tbody id="transactions_body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>
<script>
let feeChart, paymentChart;
let isLoading = false;

// Initialize datepicker
$(document).ready(function() {
    $('#date_from, #date_to').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
});

function loadStatistics() {
    if(isLoading) return;
    isLoading = true;
    
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    const classId = $('#class_filter').val();
    const collectorId = $('#collector_filter').val();
    const paymentMethod = $('#payment_method_filter').val();
    
    showAjaxModal_alert('Loading statistics...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('fee_collection/get_statistics_data'); ?>',
        data: { date_from: dateFrom, date_to: dateTo, class_id: classId, collector_id: collectorId, payment_method: paymentMethod },
        dataType: 'json',
        success: function(data) {
            isLoading = false;
            if(data.status === 'success') {
                updateKPIs(data.summary);
                updateCharts(data.summary);
                updateBreakdowns(data.summary);
                updateTransactionsTable(data.transactions);
                showAjaxModal_alert('Data loaded successfully', 'success', false);
                setTimeout(() => $('.close')[0].click(), 1000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        },
        error: function() {
            isLoading = false;
            showAjaxModal_alert('Failed to load statistics', 'error');
        }
    });
}

function updateKPIs(summary) {
    const currency = '<?php echo $currency; ?>';
    $('#kpi_total').text(currency + ' ' + parseFloat(summary.total_collected).toLocaleString('en-GH', {minimumFractionDigits: 2}));
    $('#kpi_rate').text(summary.kpis.collection_rate.toFixed(1) + '%');
    $('#kpi_arrears').text(currency + ' ' + parseFloat(summary.outstanding_arrears).toLocaleString('en-GH', {minimumFractionDigits: 2}));
    $('#kpi_students').text(summary.kpis.students_paid);
    $('#kpi_transactions').text(summary.transaction_count + ' transactions');
    $('#kpi_arrears_cleared').text(currency + ' ' + parseFloat(summary.arrears_cleared).toLocaleString('en-GH', {minimumFractionDigits: 0}) + ' arrears cleared');
    
    const growth = summary.kpis.growth_rate;
    const growthHtml = growth >= 0 
        ? `<i class="fa fa-arrow-up"></i> ${growth.toFixed(1)}% vs previous`
        : `<i class="fa fa-arrow-down"></i> ${Math.abs(growth).toFixed(1)}% vs previous`;
    $('#kpi_growth').html(growthHtml).css('color', growth >= 0 ? '#27ae60' : '#e74c3c');
}

function updateCharts(summary) {
    // Destroy existing charts properly
    if(feeChart) {
        feeChart.destroy();
        feeChart = null;
    }
    if(paymentChart) {
        paymentChart.destroy();
        paymentChart = null;
    }
    
    // Small delay to ensure canvas is cleared
    setTimeout(function() {
        // Fee Breakdown Chart
        const feeCanvas = document.getElementById('feeBreakdownChart');
        if(feeCanvas) {
            const feeCtx = feeCanvas.getContext('2d');
            feeChart = new Chart(feeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Feeding', 'Breakfast', 'Classes', 'Water', 'Transport'],
                    datasets: [{
                        data: [
                            summary.feeding_total,
                            summary.breakfast_total,
                            summary.classes_total,
                            summary.water_total,
                            summary.transport_total
                        ],
                        backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#06b6d4', '#8b5cf6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': <?php echo $currency; ?> ' + context.parsed.toLocaleString('en-GH', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Payment Type Chart
        const paymentCanvas = document.getElementById('paymentTypeChart');
        if(paymentCanvas) {
            const paymentCtx = paymentCanvas.getContext('2d');
            paymentChart = new Chart(paymentCtx, {
                type: 'bar',
                data: {
                    labels: ['Arrears', 'Advance', 'Mixed'],
                    datasets: [{
                        label: 'Amount (₵)',
                        data: [
                            summary.by_payment_type.arrears || 0,
                            summary.by_payment_type.advance || 0,
                            summary.by_payment_type.mixed || 0
                        ],
                        backgroundColor: ['#ef4444', '#10b981', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '<?php echo $currency; ?> ' + context.parsed.y.toLocaleString('en-GH', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: function(value) { return '<?php echo $currency; ?> ' + value; } } }
                    }
                }
            });
        }
    }, 100);
}

function updateBreakdowns(summary) {
    // By Class
    let classHtml = '';
    const sortedClasses = Object.entries(summary.by_class).sort((a, b) => b[1] - a[1]);
    const maxClass = sortedClasses[0] ? sortedClasses[0][1] : 1;
    sortedClasses.forEach(([className, amount]) => {
        const percentage = (amount / maxClass) * 100;
        classHtml += `
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-weight: 600; font-size: 13px;">${className}</span>
                    <span style="font-weight: 700; color: #2c3e50;"><?php echo $currency; ?> ${parseFloat(amount).toLocaleString('en-GH', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: ${percentage}%"></div>
                </div>
            </div>
        `;
    });
    $('#class_breakdown').html(classHtml || '<p style="text-align: center; color: #7f8c8d; padding: 40px 0;">No data available</p>');
    
    // By Collector
    let collectorHtml = '';
    const sortedCollectors = Object.entries(summary.by_collector).sort((a, b) => b[1] - a[1]);
    const maxCollector = sortedCollectors[0] ? sortedCollectors[0][1] : 1;
    sortedCollectors.forEach(([collectorName, amount]) => {
        const percentage = (amount / maxCollector) * 100;
        collectorHtml += `
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-weight: 600; font-size: 13px;">${collectorName}</span>
                    <span style="font-weight: 700; color: #2c3e50;"><?php echo $currency; ?> ${parseFloat(amount).toLocaleString('en-GH', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: ${percentage}%; background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);"></div>
                </div>
            </div>
        `;
    });
    $('#collector_breakdown').html(collectorHtml || '<p style="text-align: center; color: #7f8c8d; padding: 40px 0;">No data available</p>');
}

function updateTransactionsTable(transactions) {
    let html = '';
    const paymentMethods = {1: 'Cash', 2: 'Mobile Money', 3: 'Cheque', 4: 'Bank Transfer'};
    transactions.forEach(t => {
        const paymentTypeBadge = {
            'arrears': '<span class="badge badge-arrears">Arrears</span>',
            'advance': '<span class="badge badge-advance">Advance</span>',
            'mixed': '<span class="badge badge-mixed">Mixed</span>'
        };
        
        html += `
            <tr class="transaction-row">
                <td>${new Date(t.payment_date * 1000).toLocaleDateString()}</td>
                <td>
                    <div style="font-weight: 600;">${t.student_name}</div>
                    <div style="font-size: 11px; color: #7f8c8d;">${t.student_code}</div>
                </td>
                <td>${t.class_name}</td>
                <td>${paymentTypeBadge[t.payment_type] || paymentTypeBadge['advance']}</td>
                <td class="text-right">${parseFloat(t.feeding_amount).toFixed(2)}</td>
                <td class="text-right">${parseFloat(t.classes_amount).toFixed(2)}</td>
                <td class="text-right">${parseFloat(t.transport_amount).toFixed(2)}</td>
                <td class="text-right" style="font-weight: 700;"><?php echo $currency; ?> ${parseFloat(t.total_amount).toFixed(2)}</td>
                <td>${paymentMethods[t.payment_method] || 'N/A'}</td>
                <td>${t.collector_name || 'N/A'}</td>
            </tr>
        `;
    });
    $('#transactions_body').html(html || '<tr><td colspan="10" style="text-align: center; padding: 40px; color: #7f8c8d;">No transactions found</td></tr>');
}

function exportTableToExcel() {
    const wb = XLSX.utils.book_new();
    
    // Get table data
    const table = document.getElementById('transactions_table');
    const rows = [];
    
    // Add headers
    const headers = ['Date', 'Student Code', 'Student Name', 'Class', 'Type', 'Feeding', 'Classes', 'Transport', 'Total', 'Method', 'Collector'];
    rows.push(headers);
    
    // Add data rows
    const tbody = table.querySelector('tbody');
    const trs = tbody.querySelectorAll('tr');
    trs.forEach(tr => {
        if (tr.style.display !== 'none') {
            const row = [];
            const tds = tr.querySelectorAll('td');
            if (tds.length > 0) {
                row.push(tds[0].textContent.trim()); // Date
                const studentInfo = tds[1].querySelectorAll('div');
                row.push(studentInfo[1].textContent.trim()); // Student Code
                row.push(studentInfo[0].textContent.trim()); // Student Name
                row.push(tds[2].textContent.trim()); // Class
                row.push(tds[3].textContent.trim()); // Type
                row.push(tds[4].textContent.trim()); // Feeding
                row.push(tds[5].textContent.trim()); // Classes
                row.push(tds[6].textContent.trim()); // Transport
                row.push(tds[7].textContent.trim()); // Total
                row.push(tds[8].textContent.trim()); // Method
                row.push(tds[9].textContent.trim()); // Collector
                rows.push(row);
            }
        }
    });
    
    const ws = XLSX.utils.aoa_to_sheet(rows);
    
    // Set column widths
    ws['!cols'] = [
        {wch: 12}, {wch: 15}, {wch: 25}, {wch: 15}, {wch: 10},
        {wch: 10}, {wch: 10}, {wch: 12}, {wch: 12}, {wch: 15}, {wch: 20}
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Transactions');
    XLSX.writeFile(wb, `fee_collection_statistics_${$('#date_from').val()}_to_${$('#date_to').val()}.xlsx`);
}

function printTable() {
    const printContent = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h2>Fee Collection Statistics Report</h2>
            <p>Period: ${$('#date_from').val()} to ${$('#date_to').val()}</p>
            <p>Generated: ${new Date().toLocaleString()}</p>
        </div>
        <div style="margin: 20px;">
            <h3>Summary</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Total Collected:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${$('#kpi_total').text()}</td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Collection Rate:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${$('#kpi_rate').text()}</td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Outstanding Arrears:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${$('#kpi_arrears').text()}</td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Students Paid:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${$('#kpi_students').text()}</td></tr>
            </table>
            <h3>Transactions</h3>
            ${$('#transactions_table').prop('outerHTML')}
        </div>
    `;
    
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Fee Collection Report</title>');
    printWindow.document.write('<style>body{font-family:Arial,sans-serif;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f4f4f4;} .text-right{text-align:right;} .badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:11px;} .badge-arrears{background:#fee2e2;color:#991b1b;} .badge-advance{background:#d1fae5;color:#065f46;} .badge-mixed{background:#fef3c7;color:#92400e;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function exportToExcel() {
    exportTableToExcel();
}

function printReport() {
    printTable();
}

$('#transaction_search').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#transactions_body tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

// Don't auto-load on page ready - wait for user to click Load Data button
// $(document).ready(function() {
//     loadStatistics();
// });
</script>
