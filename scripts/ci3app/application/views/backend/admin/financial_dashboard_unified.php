<!-- Tailwind CSS CDN -->
<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<style>
.shadow-3xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>
<!-- Flowbite CDN -->
<link href="<?php echo base_url(); ?>assets/cdn/css/flowbite.min.css" rel="stylesheet" />
<script src="<?php echo base_url(); ?>assets/cdn/js/flowbite.min.js"></script>
<!-- Chart.js -->
<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<!-- ApexCharts -->
<script src="<?php echo base_url(); ?>assets/cdn/js/apexcharts.min.js"></script>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6 mt-8">
    
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    Unified Financial Dashboard
                </h1>
                <p class="text-slate-600 mt-2">Real-time financial intelligence across all systems</p>
            </div>
            
            <!-- Period Selector -->
            <div class="flex items-center gap-3">
                <select id="periodSelector" class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                </select>
                
                <button onclick="refreshDashboard()" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Integration Health Status -->
    <div id="healthStatus" class="mb-6 p-4 bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                <span class="font-semibold text-slate-700">System Integration Status</span>
            </div>
            <div class="flex items-center gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-slate-600">Fee Collection</span>
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-600">Analytics</span>
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-600">Accounts</span>
                    <span id="accountsStatus" class="w-2 h-2 bg-green-500 rounded-full"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        
        <!-- Total Revenue -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-2xl hover:shadow-3xl p-8 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-base font-semibold bg-white/20 px-4 py-2 rounded-full shadow-md" id="revenueGrowth">+0%</span>
            </div>
            <h3 class="text-base font-semibold text-white opacity-90 mb-2">Total Revenue</h3>
            <p class="text-4xl font-bold text-white mb-1" id="totalRevenue"><?php echo get_settings('currency'); ?> 0.00</p>
            <p class="text-sm text-white opacity-75 mt-3" id="revenueTransactions">0 transactions</p>
            <a href="<?php echo site_url('admin/income_dashboard'); ?>" class="text-xs text-white opacity-75 hover:opacity-100 mt-2 inline-block">View Details →</a>
        </div>

        <!-- Total Expenditure -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl shadow-2xl hover:shadow-3xl p-8 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-base font-semibold bg-white/20 px-4 py-2 rounded-full shadow-md" id="expGrowth">+0%</span>
            </div>
            <h3 class="text-base font-semibold text-white opacity-90 mb-2">Total Expenditure</h3>
            <p class="text-4xl font-bold text-white mb-1" id="totalExpenditure"><?php echo get_settings('currency'); ?> 0.00</p>
            <p class="text-sm text-white opacity-75 mt-3" id="expTransactions">0 transactions</p>
            <a href="<?php echo site_url('admin/expenditure_dashboard'); ?>" class="text-xs text-white opacity-75 hover:opacity-100 mt-2 inline-block">View Details →</a>
        </div>

        <!-- Net Profit/Loss -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-2xl hover:shadow-3xl p-8 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <span class="text-base font-semibold bg-white/20 px-4 py-2 rounded-full shadow-md" id="profitMargin">0%</span>
            </div>
            <h3 class="text-base font-semibold text-white opacity-90 mb-2">Net Profit/Loss</h3>
            <p class="text-4xl font-bold text-white mb-1" id="netProfit"><?php echo get_settings('currency'); ?> 0.00</p>
            <p class="text-sm text-white opacity-75 mt-3" id="profitStatus">This Month</p>
        </div>

        <!-- Collection Rate -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-2xl hover:shadow-3xl p-8 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold" id="collectionRate">0%</div>
                </div>
            </div>
            <h3 class="text-base font-semibold text-white opacity-90 mb-2">Collection Efficiency</h3>
            <div class="w-full bg-white/20 rounded-full h-3 mt-4 shadow-inner">
                <div id="collectionBar" class="bg-white h-3 rounded-full transition-all duration-500 shadow-md" style="width: 0%"></div>
            </div>
            <p class="text-sm text-white opacity-75 mt-3" id="collectionDetails">0 students paid</p>
        </div>

        <!-- Outstanding Arrears -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl shadow-2xl hover:shadow-3xl p-8 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-base font-semibold bg-white/20 px-4 py-2 rounded-full shadow-md">Pending</span>
            </div>
            <h3 class="text-base font-semibold text-white opacity-90 mb-2">Outstanding Arrears</h3>
            <p class="text-4xl font-bold text-white mb-1" id="outstandingArrears"><?php echo get_settings('currency'); ?> 0.00</p>
            <p class="text-sm text-white opacity-75 mt-3" id="arrearsCollected"><?php echo get_settings('currency'); ?> 0.00 collected</p>
        </div>

        <!-- Cash Position -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-2xl hover:shadow-3xl p-8 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <span class="text-base font-semibold bg-white/20 px-4 py-2 rounded-full shadow-md">Live</span>
            </div>
            <h3 class="text-base font-semibold text-white opacity-90 mb-2">Cash Position</h3>
            <p class="text-4xl font-bold text-white mb-1" id="cashPosition"><?php echo get_settings('currency'); ?> 0.00</p>
            <p class="text-sm text-white opacity-75 mt-3" id="prepaidBalance"><?php echo get_settings('currency'); ?> 0.00 prepaid</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Revenue Trends Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-2xl hover:shadow-3xl p-8 border border-slate-200 transition-shadow duration-300">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800">Revenue Trends</h2>
                <div class="flex gap-2">
                    <button class="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg font-medium">Daily</button>
                    <button class="px-3 py-1 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Weekly</button>
                    <button class="px-3 py-1 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Monthly</button>
                </div>
            </div>
            <div id="revenueTrendsChart" style="height: 350px;"></div>
        </div>

        <!-- Top Collectors -->
        <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl p-8 border border-slate-200 transition-shadow duration-300">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Top Collectors</h2>
            <div id="topCollectorsList" class="space-y-4">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <!-- Revenue Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- By Fee Type -->
        <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl p-8 border border-slate-200 transition-shadow duration-300">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Revenue by Fee Type</h2>
            <div id="feeTypeChart" style="height: 300px;"></div>
        </div>

        <!-- By Payment Method -->
        <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl p-8 border border-slate-200 transition-shadow duration-300">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Payment Methods</h2>
            <div id="paymentMethodChart" style="height: 300px;"></div>
        </div>
    </div>

    <!-- Journal Entries & Sync Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Auto Journal Entries -->
        <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl p-8 border border-slate-200 transition-shadow duration-300">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800">Accounting Integration</h2>
                <button id="syncBtn" onclick="syncToAccounts()" class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sync Now
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl">
                    <div>
                        <p class="text-sm text-slate-600">Auto Journal Entries</p>
                        <p class="text-2xl font-bold text-slate-800" id="autoEntries">0</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl">
                    <div>
                        <p class="text-sm text-slate-600">Synced Amount</p>
                        <p class="text-2xl font-bold text-slate-800" id="syncedAmount">GHS 0.00</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl">
                    <div>
                        <p class="text-sm text-slate-600">Pending Sync</p>
                        <p class="text-2xl font-bold text-slate-800" id="pendingSync">0</p>
                    </div>
                    <div class="p-3 bg-amber-100 rounded-xl">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue by Class -->
        <div class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl p-8 border border-slate-200 transition-shadow duration-300">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Revenue by Class</h2>
            <div id="revenueByClassList" class="space-y-3 max-h-80 overflow-y-auto">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>
</div>

<script>
let dashboardData = null;
let charts = {};
const currency = '<?php echo get_settings('currency'); ?>';

// Initialize dashboard
$(document).ready(function() {
    loadDashboard();
    
    $('#periodSelector').change(function() {
        loadDashboard();
    });
});

function loadDashboard() {
    const period = $('#periodSelector').val();
    
    $.ajax({
        url: '<?php echo base_url(); ?>financial_integration/get_financial_overview',
        type: 'GET',
        data: { period: period },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                dashboardData = response.data;
                renderDashboard();
            } else {
                console.error('Failed to load data:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

function renderDashboard() {
    if (!dashboardData) {
        console.error('No dashboard data');
        return;
    }
    
    const fee = dashboardData.fee_collection || {};
    const analytics = dashboardData.analytics || {};
    const accounts = dashboardData.accounts || {};
    const health = dashboardData.integration_status || {};
    
    // Load expenditure data
    $.ajax({
        url: '<?php echo site_url('admin/get_expenditure_stats'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(expData) {
            $('#totalExpenditure').text(currency + ' ' + formatNumber(expData.month || 0));
            $('#expTransactions').text((expData.month_count || 0) + ' transactions');
            $('#expGrowth').text(((expData.change_percent || 0) >= 0 ? '+' : '') + (expData.change_percent || 0) + '%');
            
            // Calculate profit/loss
            const revenue = fee.grand_total || 0;
            const expenditure = expData.month || 0;
            const profit = revenue - expenditure;
            const margin = revenue > 0 ? ((profit / revenue) * 100).toFixed(1) : 0;
            
            $('#netProfit').text(currency + ' ' + formatNumber(profit));
            $('#netProfit').css('color', profit >= 0 ? '#fff' : '#fee2e2');
            $('#profitMargin').text(margin + '%');
            $('#profitStatus').text(profit >= 0 ? 'Profit This Month' : 'Loss This Month');
        }
    });
    
    // Update key metrics
    $('#totalRevenue').text(currency + ' ' + formatNumber(fee.grand_total || 0));
    $('#revenueTransactions').text((fee.total_transactions || 0) + ' transactions');
    $('#revenueGrowth').text(((analytics.growth_rate || 0) >= 0 ? '+' : '') + (analytics.growth_rate || 0) + '%');
    
    $('#collectionRate').text((fee.collection_rate || 0).toFixed(1) + '%');
    $('#collectionBar').css('width', (fee.collection_rate || 0) + '%');
    $('#collectionDetails').text((fee.students_paid || 0) + ' students paid');
    
    $('#outstandingArrears').text(currency + ' ' + formatNumber(fee.outstanding_arrears || 0));
    $('#arrearsCollected').text(currency + ' ' + formatNumber(fee.arrears_collected || 0) + ' collected');
    
    $('#cashPosition').text(currency + ' ' + formatNumber(accounts.cash_position || 0));
    $('#prepaidBalance').text(currency + ' ' + formatNumber(fee.prepaid_balance || 0) + ' prepaid');
    
    // Update integration status
    $('#accountsStatus').removeClass('bg-green-500 bg-amber-500 bg-red-500');
    if (health.fee_to_accounts) {
        $('#accountsStatus').addClass('bg-green-500');
    } else {
        $('#accountsStatus').addClass('bg-amber-500');
    }
    
    $('#autoEntries').text(accounts.auto_journal_entries || 0);
    $('#syncedAmount').text(currency + ' ' + formatNumber(accounts.auto_entries_amount || 0));
    $('#pendingSync').text(health.pending_entries || 0);
    
    // Render charts
    renderRevenueTrends(analytics.trends || []);
    renderTopCollectors(analytics.top_collectors || []);
    renderFeeTypeChart(fee);
    renderPaymentMethodChart(fee);
    renderRevenueByClass(analytics.revenue_by_class || []);
}

function renderRevenueTrends(trends) {
    const options = {
        series: [{
            name: 'Revenue',
            data: trends.map(t => t.amount)
        }],
        chart: {
            type: 'area',
            height: 350,
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
            }
        },
        colors: ['#3B82F6'],
        xaxis: {
            categories: trends.map(t => t.date)
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return currency + ' ' + formatNumber(val);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return currency + ' ' + formatNumber(val);
                }
            }
        }
    };
    
    if (charts.revenueTrends) {
        charts.revenueTrends.destroy();
    }
    charts.revenueTrends = new ApexCharts(document.querySelector("#revenueTrendsChart"), options);
    charts.revenueTrends.render();
}

function renderTopCollectors(collectors) {
    let html = '';
    collectors.forEach((collector, index) => {
        const colors = ['blue', 'emerald', 'purple', 'amber', 'rose'];
        const color = colors[index % colors.length];
        html += `
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-${color}-50 to-${color}-100 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-${color}-500 rounded-full flex items-center justify-center text-white font-bold">
                        ${index + 1}
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">${collector.name}</p>
                        <p class="text-xs text-slate-600">${collector.transactions} transactions</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-slate-800">${currency} ${formatNumber(collector.total)}</p>
                </div>
            </div>
        `;
    });
    $('#topCollectorsList').html(html || '<p class="text-center text-slate-500">No data available</p>');
}

function renderFeeTypeChart(fee) {
    const options = {
        series: [
            fee.feeding_total || 0,
            fee.breakfast_total || 0,
            fee.classes_total || 0,
            fee.water_total || 0,
            fee.transport_total || 0
        ],
        chart: {
            type: 'donut',
            height: 300
        },
        labels: ['Feeding', 'Breakfast', 'Classes', 'Water', 'Transport'],
        colors: ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444'],
        legend: {
            position: 'bottom'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%'
                }
            }
        }
    };
    
    if (charts.feeType) {
        charts.feeType.destroy();
    }
    charts.feeType = new ApexCharts(document.querySelector("#feeTypeChart"), options);
    charts.feeType.render();
}

function renderPaymentMethodChart(fee) {
    const options = {
        series: [{
            data: [fee.cash_total || 0, fee.momo_total || 0, fee.bank_total || 0]
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                horizontal: true,
                distributed: true
            }
        },
        colors: ['#10B981', '#3B82F6', '#8B5CF6'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return currency + ' ' + formatNumber(val);
            }
        },
        xaxis: {
            categories: ['Cash', 'Mobile Money', 'Bank Transfer']
        },
        legend: { show: false }
    };
    
    if (charts.paymentMethod) {
        charts.paymentMethod.destroy();
    }
    charts.paymentMethod = new ApexCharts(document.querySelector("#paymentMethodChart"), options);
    charts.paymentMethod.render();
}

function renderRevenueByClass(classes) {
    let html = '';
    const grandTotal = (dashboardData.fee_collection && dashboardData.fee_collection.grand_total) || 1;
    classes.forEach((cls, index) => {
        const percentage = (cls.total / grandTotal) * 100;
        html += `
            <div class="p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-semibold text-slate-700">${cls.class_name}</span>
                    <span class="font-bold text-slate-800">${currency} ${formatNumber(cls.total)}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2 rounded-full" style="width: ${percentage}%"></div>
                </div>
            </div>
        `;
    });
    $('#revenueByClassList').html(html || '<p class="text-center text-slate-500">No data available</p>');
}

function syncToAccounts() {
    showConfirmModal(
        'Confirm Sync',
        'Are you sure you want to sync all unsynced transactions to accounts?',
        function() {
            showAjaxModal_alert('Syncing transactions...', 'loading');
            $.ajax({
                url: '<?php echo base_url(); ?>financial_integration/sync_all_revenue',
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if (response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => loadDashboard(), 2000);
                } else {
                    showAjaxModal_alert(response.message || 'Sync failed', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred during sync', 'error');
            });
        },
        'Sync Now',
        'primary'
    );
}

function refreshDashboard() {
    loadDashboard();
}

function formatNumber(num) {
    return parseFloat(num || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
