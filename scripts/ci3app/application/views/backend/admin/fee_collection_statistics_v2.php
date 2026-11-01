<?php
// Get initial data
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$classes = $this->db->order_by('name')->get('class')->result_array();
$collectors = $this->db->where_in('level', [1,2,3])->order_by('name')->get('admin')->result_array();
?>

<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white; }
}
.print-only { display: none; }

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

.badge-arrears { background: #fee2e2; color: #991b1b; }
.badge-advance { background: #d1fae5; color: #065f46; }
.badge-mixed { background: #fef3c7; color: #92400e; }
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
                <div class="col-md-4" style="text-align: right;">
                    <button onclick="window.print()" class="btn btn-light" style="margin-right: 8px;">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <button onclick="exportToExcel()" class="btn btn-success">
                        <i class="fa fa-file-excel-o"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card no-print">
            <div class="row">
                <div class="col-md-3">
                    <label style="font-weight: 600; margin-bottom: 8px;">Date From</label>
                    <input type="date" id="date_from" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-3">
                    <label style="font-weight: 600; margin-bottom: 8px;">Date To</label>
                    <input type="date" id="date_to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <label style="font-weight: 600; margin-bottom: 8px;">Class</label>
                    <select id="class_filter" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?></option>
                        <?php endforeach; ?>
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
                        <i class="fa fa-money"></i>
                    </div>
                    <div class="kpi-label">Total Collected</div>
                    <div class="kpi-value" id="kpi_total">GH₵ 0.00</div>
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
                    <div style="font-size: 12px; color: #7f8c8d;" id="kpi_arrears_cleared">GH₵ 0 arrears cleared</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="kpi-label">Outstanding Arrears</div>
                    <div class="kpi-value" id="kpi_arrears">GH₵ 0.00</div>
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
                <input type="text" id="transaction_search" placeholder="Search..." class="form-control no-print" style="width: 300px;">
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
<script>
let feeChart, paymentChart;

function loadStatistics() {
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    const classId = $('#class_filter').val();
    const collectorId = $('#collector_filter').val();
    
    showAjaxModal_alert('Loading statistics...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('fee_collection/get_statistics_data'); ?>',
        data: { date_from: dateFrom, date_to: dateTo, class_id: classId, collector_id: collectorId },
        dataType: 'json',
        success: function(data) {
            if(data.status === 'success') {
                updateKPIs(data.summary);
                updateCharts(data.summary);
                updateBreakdowns(data.summary);
                updateTransactionsTable(data.transactions);
                $('.close')[0].click();
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to load statistics', 'error');
        }
    });
}

function updateKPIs(summary) {
    $('#kpi_total').text('GH₵ ' + parseFloat(summary.total_collected).toLocaleString('en-GH', {minimumFractionDigits: 2}));
    $('#kpi_rate').text(summary.kpis.collection_rate.toFixed(1) + '%');
    $('#kpi_arrears').text('GH₵ ' + parseFloat(summary.outstanding_arrears).toLocaleString('en-GH', {minimumFractionDigits: 2}));
    $('#kpi_students').text(summary.kpis.students_paid);
    $('#kpi_transactions').text(summary.transaction_count + ' transactions');
    $('#kpi_arrears_cleared').text('GH₵ ' + parseFloat(summary.arrears_cleared).toLocaleString('en-GH', {minimumFractionDigits: 0}) + ' arrears cleared');
    
    const growth = summary.kpis.growth_rate;
    const growthHtml = growth >= 0 
        ? `<i class="fa fa-arrow-up"></i> ${growth.toFixed(1)}% vs previous`
        : `<i class="fa fa-arrow-down"></i> ${Math.abs(growth).toFixed(1)}% vs previous`;
    $('#kpi_growth').html(growthHtml).css('color', growth >= 0 ? '#27ae60' : '#e74c3c');
}

function updateCharts(summary) {
    // Fee Breakdown Chart
    if(feeChart) feeChart.destroy();
    const feeCtx = document.getElementById('feeBreakdownChart').getContext('2d');
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
                            return context.label + ': GH₵ ' + context.parsed.toLocaleString('en-GH', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
    
    // Payment Type Chart
    if(paymentChart) paymentChart.destroy();
    const paymentCtx = document.getElementById('paymentTypeChart').getContext('2d');
    paymentChart = new Chart(paymentCtx, {
        type: 'bar',
        data: {
            labels: ['Arrears', 'Advance', 'Mixed'],
            datasets: [{
                label: 'Amount (GH₵)',
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
                            return 'GH₵ ' + context.parsed.y.toLocaleString('en-GH', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(value) { return 'GH₵ ' + value; } } }
            }
        }
    });
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
                    <span style="font-weight: 700; color: #2c3e50;">GH₵ ${parseFloat(amount).toLocaleString('en-GH', {minimumFractionDigits: 2})}</span>
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
                    <span style="font-weight: 700; color: #2c3e50;">GH₵ ${parseFloat(amount).toLocaleString('en-GH', {minimumFractionDigits: 2})}</span>
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
                <td class="text-right" style="font-weight: 700;">GH₵ ${parseFloat(t.total_amount).toFixed(2)}</td>
                <td>${t.collector_name || 'N/A'}</td>
            </tr>
        `;
    });
    $('#transactions_body').html(html || '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #7f8c8d;">No transactions found</td></tr>');
}

function exportToExcel() {
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    const classId = $('#class_filter').val();
    const collectorId = $('#collector_filter').val();
    window.location.href = `<?php echo site_url('fee_collection/export_statistics'); ?>?date_from=${dateFrom}&date_to=${dateTo}&class_id=${classId}&collector_id=${collectorId}`;
}

$('#transaction_search').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#transactions_body tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

$(document).ready(function() {
    loadStatistics();
});
</script>
