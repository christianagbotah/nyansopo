<?php
include APPPATH . 'views/backend/components/enterprise_ui_components.php';
$currency = get_settings('currency');
?>

<!-- Page Header -->
<?php render_page_header(
    get_phrase('accounts_dashboard'),
    get_phrase('comprehensive_financial_overview_and_analytics')
); ?>

<!-- Primary Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <?php render_stat_card(
        get_phrase('total_assets'),
        $currency . number_format($metrics['total_assets'], 2),
        '<svg class="w-6 h-6 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'blue'
    ); ?>
    
    <?php render_stat_card(
        get_phrase('total_liabilities'),
        $currency . number_format($metrics['total_liabilities'], 2),
        '<svg class="w-6 h-6 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
        'red'
    ); ?>
    
    <?php render_stat_card(
        get_phrase('monthly_revenue'),
        $currency . number_format($metrics['monthly_revenue'], 2),
        '<svg class="w-6 h-6 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
        'green'
    ); ?>
    
    <?php render_stat_card(
        get_phrase('monthly_expenses'),
        $currency . number_format($metrics['monthly_expenses'], 2),
        '<svg class="w-6 h-6 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>',
        'yellow'
    ); ?>
</div>

<!-- Secondary Metrics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-2"><?php echo get_phrase('net_income'); ?></p>
                <h3 class="text-2xl font-bold text-gray-900"><?php echo $currency . number_format($metrics['net_income'], 2); ?></h3>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-2"><?php echo get_phrase('bank_balance'); ?></p>
                <h3 class="text-2xl font-bold text-gray-900"><?php echo $currency . number_format($metrics['bank_balance'], 2); ?></h3>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-2"><?php echo get_phrase('pending_entries'); ?></p>
                <h3 class="text-2xl font-bold text-gray-900"><?php echo $metrics['pending_entries']; ?></h3>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Budget Utilization -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo get_phrase('budget_utilization'); ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div class="md:col-span-2">
                <div class="relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full <?php echo $metrics['budget_percentage'] > 90 ? 'text-red-600 bg-red-200' : ($metrics['budget_percentage'] > 75 ? 'text-yellow-600 bg-yellow-200' : 'text-green-600 bg-green-200'); ?>">
                                <?php echo number_format($metrics['budget_percentage'], 1); ?>% <?php echo get_phrase('utilized'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-4 text-xs flex rounded-full bg-gray-200">
                        <div style="width:<?php echo min($metrics['budget_percentage'], 100); ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center <?php echo $metrics['budget_percentage'] > 90 ? 'bg-red-500' : ($metrics['budget_percentage'] > 75 ? 'bg-yellow-500' : 'bg-green-500'); ?> transition-all duration-500"></div>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500"><?php echo get_phrase('budget_used'); ?></p>
                <p class="text-xl font-bold text-gray-900"><?php echo $currency . number_format($metrics['budget_used'], 2); ?></p>
                <p class="text-xs text-gray-400"><?php echo get_phrase('of'); ?> <?php echo $currency . number_format($metrics['budget_total'], 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo get_phrase('quick_actions'); ?></h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?php echo site_url('accounts/journal_entries'); ?>" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 text-blue-700 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all duration-200 group">
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium text-sm text-center"><?php echo get_phrase('create_entry'); ?></span>
            </a>
            
            <a href="<?php echo site_url('accounts/chart_of_accounts'); ?>" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-green-50 to-green-100 text-green-700 rounded-lg hover:from-green-100 hover:to-green-200 transition-all duration-200 group">
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="font-medium text-sm text-center"><?php echo get_phrase('chart_of_accounts'); ?></span>
            </a>
            
            <a href="<?php echo site_url('accounts/reports/balance_sheet'); ?>" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 text-purple-700 rounded-lg hover:from-purple-100 hover:to-purple-200 transition-all duration-200 group">
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-medium text-sm text-center"><?php echo get_phrase('balance_sheet'); ?></span>
            </a>
            
            <a href="<?php echo site_url('accounts/budgets'); ?>" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 text-yellow-700 rounded-lg hover:from-yellow-100 hover:to-yellow-200 transition-all duration-200 group">
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span class="font-medium text-sm text-center"><?php echo get_phrase('budgets'); ?></span>
            </a>
        </div>
    </div>
</div>
