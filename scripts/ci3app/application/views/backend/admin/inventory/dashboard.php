<!-- Enterprise Inventory Dashboard -->
<link href="<?php echo base_url('assets/css/inventory-readable.css'); ?>" rel="stylesheet">

<div class="p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <!-- Page Header -->
    <div class="mb-12">
        <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight">Inventory Dashboard</h1>
        <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Real-time stock levels and movement tracking</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 mb-12">
        <!-- Total Products Card - Links to Products Page -->
        <a href="<?php echo site_url('inventory/products'); ?>" class="block bg-white border-l-4 border-blue-500 rounded-xl p-8 shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Total Products</p>
                    <p style="font-size: 3rem !important;" class="font-bold text-gray-900" id="total_items">0</p>
                </div>
                <div class="p-4 bg-blue-50 rounded-xl">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Low Stock Alerts Card - Links to Products Page -->
        <a href="<?php echo site_url('inventory/products'); ?>" class="block bg-white border-l-4 border-orange-500 rounded-xl p-8 shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Low Stock Alerts</p>
                    <p style="font-size: 3rem !important;" class="font-bold text-orange-600" id="low_stock">0</p>
                </div>
                <div class="p-4 bg-orange-50 rounded-xl">
                    <svg class="w-12 h-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Stock Value Card - Links to Products Page -->
        <a href="<?php echo site_url('inventory/products'); ?>" class="block bg-white border-l-4 border-green-500 rounded-xl p-8 shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Stock Value</p>
                    <p style="font-size: 2rem !important;" class="font-bold text-gray-900" id="stock_value"><sup style="font-size: 0.6em; vertical-align: super;"><?php echo $currency; ?></sup> 0</p>
                </div>
                <div class="p-4 bg-green-50 rounded-xl">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Sales Card - Links to Sales Page -->
        <a href="<?php echo site_url('inventory/sales'); ?>" class="block bg-white border-l-4 border-purple-500 rounded-xl p-8 shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Sales (30 Days)</p>
                    <p style="font-size: 3rem !important;" class="font-bold text-gray-900" id="recent_sales">0</p>
                </div>
                <div class="p-4 bg-purple-50 rounded-xl">
                    <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Returns Card - Links to Returns Page -->
        <a href="<?php echo site_url('inventory/returns'); ?>" class="block bg-white border-l-4 border-red-500 rounded-xl p-8 shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Returns (30 Days)</p>
                    <p style="font-size: 3rem !important;" class="font-bold text-gray-900" id="returns_count">0</p>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mt-2">Refunded: <span id="returns_amount" class="font-semibold">0</span></p>
                </div>
                <div class="p-4 bg-red-50 rounded-xl">
                    <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Purchase Orders Card - Links to Purchase Orders Page -->
        <a href="<?php echo site_url('inventory/purchase_orders'); ?>" class="block bg-white border-l-4 border-indigo-500 rounded-xl p-8 shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Pending Orders</p>
                    <p style="font-size: 3rem !important;" class="font-bold text-gray-900" id="pending_po_count">0</p>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mt-2">Value: <span id="pending_po_value" class="font-semibold">0</span></p>
                </div>
                <div class="p-4 bg-indigo-50 rounded-xl">
                    <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Data Tables -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-orange-100">
                <h2 style="font-size: 1.75rem !important;" class="font-bold text-gray-900">Low Stock Alerts</h2>
            </div>
            <div class="p-8">
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th style="font-size: 1.125rem !important;" class="text-left font-bold text-gray-700 uppercase tracking-wider pb-5">Product</th>
                                <th style="font-size: 1.125rem !important;" class="text-right font-bold text-gray-700 uppercase tracking-wider pb-5">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="low_stock_list">
                            <tr><td colspan="2" style="font-size: 1.125rem !important;" class="py-8 text-center text-gray-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
                <h2 style="font-size: 1.75rem !important;" class="font-bold text-gray-900">Recent Stock Movements</h2>
            </div>
            <div class="p-8">
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th style="font-size: 1.125rem !important;" class="text-left font-bold text-gray-700 uppercase tracking-wider pb-5">Product</th>
                                <th style="font-size: 1.125rem !important;" class="text-right font-bold text-gray-700 uppercase tracking-wider pb-5">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="recent_movements">
                            <tr><td colspan="2" style="font-size: 1.125rem !important;" class="py-8 text-center text-gray-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('assets/js/inventory-utils.js'); ?>"></script>
<script>
$(document).ready(function() {
    loadDashboardStats();
    loadLowStockItems();
    loadRecentMovements();
    loadReturnsStats();
    loadPurchaseOrdersStats();
});

function loadDashboardStats() {
    $('#total_items, #low_stock, #stock_value, #recent_sales')
        .html('<div class="h-8 bg-gray-200 rounded animate-pulse"></div>');
    
    $.get('<?php echo site_url('inventory/get_stats'); ?>')
        .done(function(response) {
            const data = JSON.parse(response);
            const currency = data.currency || '<?php echo $currency; ?>';
            $('#total_items').text(data.total_products || 0);
            $('#low_stock').text(data.low_stock_count || 0);
            $('#stock_value').html('<sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + (parseFloat(data.stock_value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
            $('#recent_sales').text(data.recent_sales || 0);
        })
        .fail(handleAjaxError);
}

function loadLowStockItems() {
    showTableSkeleton('#low_stock_list', 2, 3);
    
    $.get('<?php echo site_url('inventory/get_low_stock'); ?>')
        .done(function(response) {
            const items = JSON.parse(response);
            let html = '';
            
            if(items.length === 0) {
                html = '<tr><td colspan="2" style="font-size: 1.125rem !important;" class="py-8 text-center text-gray-500">All stocked up - No items below reorder level</td></tr>';
            } else {
                items.slice(0, 5).forEach(item => {
                    html += `
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="py-5">
                                <p style="font-size: 1.125rem !important;" class="font-semibold text-gray-900 leading-relaxed">${item.name}</p>
                                <p style="font-size: 1rem !important;" class="text-gray-600 mt-1">${item.category_name || 'Uncategorized'}</p>
                            </td>
                            <td class="py-5 text-right">
                                ${getStockIndicator(item.quantity, item.reorder_level)}
                            </td>
                        </tr>
                    `;
                });
            }
            
            $('#low_stock_list').html(html);
        })
        .fail(handleAjaxError);
}

function loadRecentMovements() {
    showTableSkeleton('#recent_movements', 2, 3);
    
    $.get('<?php echo site_url('inventory/get_movements'); ?>')
        .done(function(response) {
            const movements = JSON.parse(response);
            let html = '';
            
            if(movements.length === 0) {
                html = '<tr><td colspan="2" style="font-size: 1.125rem !important;" class="py-8 text-center text-gray-500">No movements yet - Stock movements will appear here</td></tr>';
            } else {
                // Group movements by product, movement_type, and date to avoid duplicates
                const groupedMovements = {};
                
                movements.forEach(mov => {
                    const dateKey = new Date(mov.movement_date).toLocaleDateString('en-GB');
                    const key = `${mov.product_id}-${mov.movement_type}-${dateKey}`;
                    
                    if (!groupedMovements[key]) {
                        groupedMovements[key] = {
                            product_id: mov.product_id,
                            product_name: mov.product_name,
                            movement_type: mov.movement_type,
                            quantity: 0,
                            movement_date: mov.movement_date,
                            dateKey: dateKey,
                            notes: mov.notes || '',
                            count: 0
                        };
                    }
                    
                    groupedMovements[key].quantity += parseInt(mov.quantity);
                    groupedMovements[key].count += 1;
                    
                    // If multiple movements, indicate that in notes
                    if (groupedMovements[key].count > 1) {
                        groupedMovements[key].notes = `${groupedMovements[key].count} movements combined`;
                    }
                });
                
                // Convert to array and sort by date
                const sortedMovements = Object.values(groupedMovements)
                    .sort((a, b) => new Date(b.movement_date) - new Date(a.movement_date))
                    .slice(0, 5);
                
                sortedMovements.forEach(mov => {
                    // 'in' movements increase stock (positive): purchases, returns, manual stock-in
                    // 'out' movements decrease stock (negative): sales, manual stock-out
                    const isPositive = mov.movement_type === 'in';
                    const badgeClass = isPositive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const sign = isPositive ? '+' : '-';
                    
                    // Show notes if available to clarify the movement
                    const notesText = mov.notes ? `<p style="font-size: 0.875rem !important;" class="text-gray-500 mt-1">${mov.notes}</p>` : '';
                    
                    html += `
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="py-5">
                                <p style="font-size: 1.125rem !important;" class="font-semibold text-gray-900 leading-relaxed">${mov.product_name}</p>
                                <p style="font-size: 1rem !important;" class="text-gray-600 mt-1">${mov.dateKey}</p>
                                ${notesText}
                            </td>
                            <td class="py-5 text-right">
                                <span style="font-size: 1rem !important;" class="inline-flex items-center px-3 py-1.5 rounded-full font-semibold ${badgeClass}">
                                    ${sign}${mov.quantity}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            }
            
            $('#recent_movements').html(html);
        })
        .fail(handleAjaxError);
}

function loadReturnsStats() {
    $('#returns_count, #returns_amount')
        .html('<div class="h-6 bg-gray-200 rounded animate-pulse"></div>');
    
    $.get('<?php echo site_url('inventory/get_return_statistics'); ?>', {
        start_date: getDateDaysAgo(30),
        end_date: getCurrentDate()
    })
        .done(function(response) {
            const data = JSON.parse(response);
            const currency = data.currency || '<?php echo $currency; ?>';
            $('#returns_count').text(data.total_returns || 0);
            $('#returns_amount').html('<sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + (parseFloat(data.total_refund_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
        })
        .fail(function() {
            $('#returns_count').text('0');
            $('#returns_amount').text('0');
        });
}

function loadPurchaseOrdersStats() {
    $('#pending_po_count, #pending_po_value')
        .html('<div class="h-6 bg-gray-200 rounded animate-pulse"></div>');
    
    $.get('<?php echo site_url('inventory/get_purchase_statistics'); ?>', {
        status: 'pending'
    })
        .done(function(response) {
            const data = JSON.parse(response);
            const currency = data.currency || '<?php echo $currency; ?>';
            $('#pending_po_count').text(data.pending_count || 0);
            $('#pending_po_value').html('<sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + (parseFloat(data.pending_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
        })
        .fail(function() {
            $('#pending_po_count').text('0');
            $('#pending_po_value').text('0');
        });
}

// Helper functions
function getDateDaysAgo(days) {
    const date = new Date();
    date.setDate(date.getDate() - days);
    return date.toISOString().split('T')[0];
}

function getCurrentDate() {
    return new Date().toISOString().split('T')[0];
}
</script>
