<?php $currency = get_settings('currency'); ?>

<div class="p-6 space-y-6 mt-16 md:mt-20">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900"><?php echo get_phrase('finance_dashboard'); ?></h1>
        <div class="flex gap-2">
            <button onclick="exportDashboard('excel')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center">
                <i class="mdi mdi-file-excel mr-2"></i><?php echo get_phrase('export_excel'); ?>
            </button>
            <button onclick="exportDashboard('pdf')" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center">
                <i class="mdi mdi-file-pdf mr-2"></i><?php echo get_phrase('export_pdf'); ?>
            </button>
            <button onclick="refreshDashboard()" id="refreshBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                <i class="mdi mdi-refresh mr-2" id="refreshIcon"></i><?php echo get_phrase('refresh'); ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="overview-cards">
        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-base mb-2"><?php echo get_phrase('total_billed'); ?></p>
                    <h3 class="text-3xl font-bold text-gray-800" id="total-billed">
                        <span class="inline-block w-16 h-8 bg-gray-200 rounded animate-pulse"></span>
                    </h3>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-base mb-2"><?php echo get_phrase('total_collected'); ?></p>
                    <h3 class="text-3xl font-bold text-gray-800" id="total-collected">
                        <span class="inline-block w-16 h-8 bg-gray-200 rounded animate-pulse"></span>
                    </h3>
                    <p class="text-sm text-green-600 mt-2 font-semibold" id="collection-rate">0%</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-base mb-2"><?php echo get_phrase('outstanding'); ?></p>
                    <h3 class="text-3xl font-bold text-gray-800" id="total-outstanding">
                        <span class="inline-block w-16 h-8 bg-gray-200 rounded animate-pulse"></span>
                    </h3>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-base mb-2"><?php echo get_phrase('overdue_invoices'); ?></p>
                    <h3 class="text-3xl font-bold text-gray-800" id="overdue-count">
                        <span class="inline-block w-16 h-8 bg-gray-200 rounded animate-pulse"></span>
                    </h3>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4"><?php echo get_phrase('payment_trends'); ?></h3>
            <canvas id="paymentTrendsChart" height="100"></canvas>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4"><?php echo get_phrase('outstanding_analysis'); ?></h3>
            <canvas id="outstandingChart" height="100"></canvas>
        </div>
    </div>

    <!-- Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4"><?php echo get_phrase('top_defaulters'); ?></h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-base" id="defaulters-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('student'); ?></th>
                                <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('class'); ?></th>
                                <th class="px-4 py-3 text-right font-semibold"><?php echo get_phrase('outstanding'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">
                                <div class="inline-block w-6 h-6 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4"><?php echo get_phrase('recent_transactions'); ?></h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-base" id="transactions-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('student'); ?></th>
                                <th class="px-4 py-3 text-right font-semibold"><?php echo get_phrase('amount'); ?></th>
                                <th class="px-4 py-3 text-left font-semibold"><?php echo get_phrase('date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">
                                <div class="inline-block w-6 h-6 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4"><?php echo get_phrase('quick_actions'); ?></h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?php echo site_url('finance/receipts'); ?>" class="flex items-center justify-center px-6 py-4 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                <i class="mdi mdi-receipt text-2xl mr-2"></i>
                <span class="font-medium"><?php echo get_phrase('receipts'); ?></span>
            </a>
            <a href="<?php echo site_url('finance/payment_plans'); ?>" class="flex items-center justify-center px-6 py-4 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition">
                <i class="mdi mdi-calendar-clock text-2xl mr-2"></i>
                <span class="font-medium"><?php echo get_phrase('payment_plans'); ?></span>
            </a>
            <a href="<?php echo site_url('finance/credit_notes'); ?>" class="flex items-center justify-center px-6 py-4 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition">
                <i class="mdi mdi-note-text text-2xl mr-2"></i>
                <span class="font-medium"><?php echo get_phrase('credit_notes'); ?></span>
            </a>
            <a href="<?php echo site_url('finance/reports'); ?>" class="flex items-center justify-center px-6 py-4 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition">
                <i class="mdi mdi-chart-line text-2xl mr-2"></i>
                <span class="font-medium"><?php echo get_phrase('reports'); ?></span>
            </a>
        </div>
    </div>
</div>

<style>
.stat-card {
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.6s;
    opacity: 0;
}
.stat-card:hover::before {
    animation: shine 0.8s;
}
@keyframes shine {
    0% { left: -50%; opacity: 0; }
    50% { opacity: 1; }
    100% { left: 150%; opacity: 0; }
}
</style>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
let paymentTrendsChart, outstandingChart;
const currency = '<?php echo $currency; ?>';
let isLoading = false;

$(document).ready(function() {
    loadDashboardData();
});

function loadDashboardData() {
    if(isLoading) return;
    isLoading = true;
    
    $.ajax({
        url: '<?php echo site_url('finance/get_dashboard_data'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(data && data.overview) {
                updateOverviewCards(data.overview);
                updateCollectionRate(data.collection_rate);
                renderPaymentTrends(data.payment_trends);
                renderOutstandingAnalysis(data.outstanding_analysis);
                renderDefaultersTable(data.top_defaulters);
                renderTransactionsTable(data.recent_transactions);
            }
            isLoading = false;
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('failed_to_load_dashboard_data'); ?>', 'error');
            isLoading = false;
        }
    });
}

function updateOverviewCards(overview) {
    $('#total-billed').html(currency + formatNumber(overview.total_billed));
    $('#total-collected').html(currency + formatNumber(overview.total_collected));
    $('#total-outstanding').html(currency + formatNumber(overview.total_outstanding));
    $('#overdue-count').html(overview.overdue_invoices);
}

function updateCollectionRate(rate) {
    $('#collection-rate').html(rate + '% <?php echo get_phrase('collected'); ?>');
}

function renderPaymentTrends(trends) {
    const ctx = document.getElementById('paymentTrendsChart');
    if(!ctx) return;
    
    if(paymentTrendsChart) {
        paymentTrendsChart.destroy();
        paymentTrendsChart = null;
    }
    
    paymentTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trends.map(t => t.payment_date),
            datasets: [{
                label: '<?php echo get_phrase('daily_collection'); ?>',
                data: trends.map(t => t.daily_collection),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return currency + formatNumber(value);
                        }
                    }
                }
            }
        }
    });
}

function renderOutstandingAnalysis(analysis) {
    const ctx = document.getElementById('outstandingChart');
    if(!ctx) return;
    
    if(outstandingChart) {
        outstandingChart.destroy();
        outstandingChart = null;
    }
    
    outstandingChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['<?php echo get_phrase('low'); ?>', '<?php echo get_phrase('medium'); ?>', '<?php echo get_phrase('high'); ?>'],
            datasets: [{
                data: [analysis.low_outstanding, analysis.medium_outstanding, analysis.high_outstanding],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function renderDefaultersTable(defaulters) {
    let html = '';
    defaulters.forEach(d => {
        html += `<tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-3">
                <a href="<?php echo site_url('finance/student_statement/'); ?>${d.student_id}" class="text-blue-600 hover:underline">
                    ${d.name}<br><small class="text-gray-500">${d.student_code}</small>
                </a>
            </td>
            <td class="px-4 py-3">${d.class_name}</td>
            <td class="px-4 py-3 text-right font-semibold text-red-600">${currency}${formatNumber(d.total_outstanding)}</td>
        </tr>`;
    });
    $('#defaulters-table tbody').html(html || '<tr><td colspan="3" class="px-4 py-8 text-center text-gray-400"><?php echo get_phrase('no_data'); ?></td></tr>');
}

function renderTransactionsTable(transactions) {
    let html = '';
    transactions.forEach(t => {
        html += `<tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-3">
                ${t.student_name}<br><small class="text-gray-500">${t.student_code}</small>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-green-600">${currency}${formatNumber(t.amount)}</td>
            <td class="px-4 py-3 text-sm text-gray-500">${formatDate(t.payment_date)}</td>
        </tr>`;
    });
    $('#transactions-table tbody').html(html || '<tr><td colspan="3" class="px-4 py-8 text-center text-gray-400"><?php echo get_phrase('no_data'); ?></td></tr>');
}

function refreshDashboard() {
    showAjaxModal_alert('<?php echo get_phrase('refreshing'); ?>...', 'loading');
    setTimeout(() => location.reload(), 1000);
}

function exportDashboard(format) {
    showAjaxModal_alert('<?php echo get_phrase('preparing_export'); ?>...', 'loading');
    window.location.href = '<?php echo site_url('finance/export_dashboard'); ?>/' + format;
    setTimeout(() => $('.close')[0].click(), 2000);
}

function formatNumber(num) {
    if(isNaN(num) || num === null || num === undefined) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString();
}
</script>
