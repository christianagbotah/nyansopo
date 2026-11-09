<?php $currency = get_settings('currency'); ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0 font-size-18"><?php echo get_phrase('financial_reports'); ?></h4>
            <div class="btn-group">
                <button class="btn btn-success" onclick="exportReport('excel')">
                    <i class="mdi mdi-file-excel"></i> <?php echo get_phrase('export_excel'); ?>
                </button>
                <button class="btn btn-danger" onclick="exportReport('pdf')">
                    <i class="mdi mdi-file-pdf"></i> <?php echo get_phrase('export_pdf'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills nav-justified bg-light" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#collection-summary" onclick="loadReport('collection_summary')">
                            <i class="mdi mdi-chart-line"></i> <?php echo get_phrase('collection_summary'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#outstanding-class" onclick="loadReport('outstanding_by_class')">
                            <i class="mdi mdi-school"></i> <?php echo get_phrase('outstanding_by_class'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#payment-methods" onclick="loadReport('payment_methods')">
                            <i class="mdi mdi-credit-card"></i> <?php echo get_phrase('payment_methods'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#defaulters" onclick="loadReport('defaulters')">
                            <i class="mdi mdi-alert"></i> <?php echo get_phrase('defaulters'); ?>
                        </a>
                    </li>
                </ul>

                <div class="row mt-4 mb-3">
                    <div class="col-md-4">
                        <select id="report-year" class="form-control">
                            <?php
                            $current_year = get_settings('running_year');
                            for ($i = -2; $i <= 1; $i++):
                                $year_parts = explode('-', $current_year);
                                $year = ($year_parts[0] + $i) . '-' . ($year_parts[1] + $i);
                            ?>
                            <option value="<?php echo $year; ?>" <?php echo $i == 0 ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="report-term" class="form-control">
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3" selected>Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary btn-block" onclick="refreshReport()">
                            <i class="mdi mdi-refresh"></i> <?php echo get_phrase('refresh'); ?>
                        </button>
                    </div>
                </div>

                <div class="tab-content">
                    <div class="tab-pane active" id="collection-summary">
                        <div class="row">
                            <div class="col-md-8">
                                <canvas id="collectionChart" height="300"></canvas>
                            </div>
                            <div class="col-md-4">
                                <div id="collection-stats"></div>
                            </div>
                        </div>
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered" id="collection-table"></table>
                        </div>
                    </div>

                    <div class="tab-pane" id="outstanding-class">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="outstandingClassChart" height="300"></canvas>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="outstanding-class-table"></table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="payment-methods">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="paymentMethodsChart" height="300"></canvas>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="payment-methods-table"></table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="defaulters">
                        <div class="alert alert-warning">
                            <i class="mdi mdi-information"></i> <?php echo get_phrase('defaulters_report_info'); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="defaulters-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo get_phrase('student'); ?></th>
                                        <th><?php echo get_phrase('class'); ?></th>
                                        <th><?php echo get_phrase('contact'); ?></th>
                                        <th class="text-right"><?php echo get_phrase('outstanding'); ?></th>
                                        <th><?php echo get_phrase('invoices'); ?></th>
                                        <th><?php echo get_phrase('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
let currentReport = 'collection_summary';
let charts = {};
const currency = '<?php echo $currency; ?>';

$(document).ready(function() {
    loadReport('collection_summary');
});

function loadReport(type) {
    currentReport = type;
    const year = $('#report-year').val();
    const term = $('#report-term').val();
    
    showAjaxModal_alert('<?php echo get_phrase('loading_report'); ?>...', 'loading');
    
    $.get('<?php echo site_url('finance/get_report_data/'); ?>' + type, {year, term}, function(data) {
        $('.close')[0].click();
        renderReport(type, data);
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('failed_to_load_report'); ?>', 'error');
    });
}

function renderReport(type, data) {
    switch(type) {
        case 'collection_summary':
            renderCollectionSummary(data);
            break;
        case 'outstanding_by_class':
            renderOutstandingByClass(data);
            break;
        case 'payment_methods':
            renderPaymentMethods(data);
            break;
        case 'defaulters':
            renderDefaulters(data);
            break;
    }
}

function renderCollectionSummary(data) {
    // Chart
    const ctx = document.getElementById('collectionChart').getContext('2d');
    if (charts.collection) charts.collection.destroy();
    
    charts.collection = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.month),
            datasets: [{
                label: '<?php echo get_phrase('total_collected'); ?>',
                data: data.map(d => d.total_collected),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => currency + formatNumber(value)
                    }
                }
            }
        }
    });
    
    // Stats
    const total = data.reduce((sum, d) => sum + parseFloat(d.total_collected), 0);
    const avg = total / data.length;
    const statsHtml = `
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="text-white-50"><?php echo get_phrase('total_collected'); ?></h6>
                <h3>${currency}${formatNumber(total)}</h3>
            </div>
        </div>
        <div class="card bg-info text-white mt-2">
            <div class="card-body">
                <h6 class="text-white-50"><?php echo get_phrase('average_monthly'); ?></h6>
                <h3>${currency}${formatNumber(avg)}</h3>
            </div>
        </div>
    `;
    $('#collection-stats').html(statsHtml);
    
    // Table
    let tableHtml = `
        <thead class="thead-light">
            <tr>
                <th><?php echo get_phrase('month'); ?></th>
                <th class="text-right"><?php echo get_phrase('amount'); ?></th>
                <th class="text-center"><?php echo get_phrase('transactions'); ?></th>
                <th class="text-right"><?php echo get_phrase('average'); ?></th>
            </tr>
        </thead>
        <tbody>
    `;
    data.forEach(d => {
        tableHtml += `
            <tr>
                <td>${d.month}</td>
                <td class="text-right font-weight-bold">${currency}${formatNumber(d.total_collected)}</td>
                <td class="text-center">${d.transaction_count}</td>
                <td class="text-right">${currency}${formatNumber(d.average_transaction)}</td>
            </tr>
        `;
    });
    tableHtml += '</tbody>';
    $('#collection-table').html(tableHtml);
}

function renderOutstandingByClass(data) {
    const ctx = document.getElementById('outstandingClassChart').getContext('2d');
    if (charts.outstanding) charts.outstanding.destroy();
    
    charts.outstanding = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(d => d.class_name),
            datasets: [{
                data: data.map(d => d.total_outstanding),
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    let tableHtml = `
        <thead class="thead-light">
            <tr>
                <th><?php echo get_phrase('class'); ?></th>
                <th class="text-center"><?php echo get_phrase('students'); ?></th>
                <th class="text-right"><?php echo get_phrase('outstanding'); ?></th>
            </tr>
        </thead>
        <tbody>
    `;
    data.forEach(d => {
        tableHtml += `
            <tr>
                <td><strong>${d.class_name}</strong></td>
                <td class="text-center">${d.student_count}</td>
                <td class="text-right text-danger font-weight-bold">${currency}${formatNumber(d.total_outstanding)}</td>
            </tr>
        `;
    });
    tableHtml += '</tbody>';
    $('#outstanding-class-table').html(tableHtml);
}

function renderPaymentMethods(data) {
    const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
    if (charts.methods) charts.methods.destroy();
    
    charts.methods = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.payment_method.toUpperCase()),
            datasets: [{
                data: data.map(d => d.total_amount),
                backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    let tableHtml = `
        <thead class="thead-light">
            <tr>
                <th><?php echo get_phrase('method'); ?></th>
                <th class="text-center"><?php echo get_phrase('count'); ?></th>
                <th class="text-right"><?php echo get_phrase('amount'); ?></th>
            </tr>
        </thead>
        <tbody>
    `;
    data.forEach(d => {
        tableHtml += `
            <tr>
                <td><span class="badge badge-primary">${d.payment_method.toUpperCase()}</span></td>
                <td class="text-center">${d.transaction_count}</td>
                <td class="text-right font-weight-bold">${currency}${formatNumber(d.total_amount)}</td>
            </tr>
        `;
    });
    tableHtml += '</tbody>';
    $('#payment-methods-table').html(tableHtml);
}

function renderDefaulters(data) {
    let html = '';
    data.forEach((d, i) => {
        html += `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <strong>${d.name}</strong><br>
                    <small class="text-muted">${d.student_code}</small>
                </td>
                <td>${d.class_name}</td>
                <td>
                    <small>${d.phone || '-'}<br>${d.parent_email || '-'}</small>
                </td>
                <td class="text-right text-danger font-weight-bold">${currency}${formatNumber(d.total_outstanding)}</td>
                <td class="text-center"><span class="badge badge-warning">${d.invoice_count}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewStatement(${d.student_id})">
                        <i class="mdi mdi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="sendReminder(${d.student_id})">
                        <i class="mdi mdi-email"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#defaulters-table tbody').html(html || '<tr><td colspan="7" class="text-center"><?php echo get_phrase('no_defaulters'); ?></td></tr>');
}

function refreshReport() {
    loadReport(currentReport);
}

function exportReport(format) {
    const year = $('#report-year').val();
    const term = $('#report-term').val();
    window.open(`<?php echo site_url('finance/export_report/'); ?>${currentReport}/${format}?year=${year}&term=${term}`, '_blank');
}

function viewStatement(studentId) {
    window.open('<?php echo site_url('finance/student_statement/'); ?>' + studentId, '_blank');
}

function sendReminder(studentId) {
    showConfirmModal(
        '<?php echo get_phrase('send_reminder'); ?>',
        '<?php echo get_phrase('send_payment_reminder_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('sending'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/send_reminder/'); ?>' + studentId, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
            });
        },
        '<?php echo get_phrase('send'); ?>',
        'primary'
    );
}

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>
