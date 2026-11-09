<?php if(!isset($ajax_load)): ?>
<!-- Return Details View - Modern Enhanced UI -->
<?php include('_readable_header.php'); ?>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.animate-fade-up { animation: fadeInUp 0.5s ease-out; }
.animate-scale-in { animation: scaleIn 0.4s ease-out; }

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.875rem;
}

.info-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
</style>

<div class="inventory-content p-4 sm:p-6 md:p-8 lg:p-10 xl:p-12" style="margin-top: 70px;">
<?php endif; ?>

    <!-- Header with Breadcrumb & Actions -->
    <div class="mb-8 animate-fade-up">
        <nav class="text-sm mb-4" style="font-size: 1rem !important;">
            <a href="<?php echo site_url('inventory/returns'); ?>" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors">
                <i class="fa fa-undo mr-1"></i>Returns
            </a>
            <i class="fa fa-chevron-right mx-2 text-gray-400 text-xs"></i>
            <span class="text-gray-900 font-semibold">Return #<?php echo $return['id']; ?></span>
        </nav>
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 style="font-size: 2rem !important;" class="font-bold text-gray-900 flex items-center">
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-3 mr-4 shadow-lg">
                    <i class="fa fa-undo text-white" style="font-size: 1.5rem;"></i>
                </div>
                Return Details #<?php echo $return['id']; ?>
            </h2>
            
            <div class="flex gap-3">
                <?php if(!isset($ajax_load)): ?>
                <a href="<?php echo site_url('inventory/returns'); ?>" style="font-size: 1.125rem !important; min-height: 3rem !important;" class="px-5 py-2 font-semibold rounded-xl text-gray-700 bg-white border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50 transition-all shadow-md inline-flex items-center">
                    <i class="fa fa-arrow-left mr-2"></i>Back to Returns
                </a>
                <?php endif; ?>
                <button onclick="window.print()" style="font-size: 1.125rem !important; min-height: 3rem !important;" class="px-5 py-2 font-semibold rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg inline-flex items-center">
                    <i class="fa fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
    </div>

    <!-- Return Header Information - Enhanced Card -->
    <div class="info-card bg-gradient-to-br from-red-50 via-white to-red-50 border-2 border-red-200 rounded-2xl p-6 md:p-8 mb-6 shadow-xl animate-scale-in" style="animation-delay: 0.1s;">
        <div class="flex items-center mb-6">
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-full p-3 mr-4">
                <i class="fa fa-info-circle text-white text-2xl"></i>
            </div>
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900">Return Information</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-4 border border-red-100 shadow-sm">
                <p style="font-size: 0.875rem !important;" class="text-gray-500 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-calendar text-red-500 mr-2"></i>Return Date
                </p>
                <p style="font-size: 1.25rem !important;" class="text-gray-900 font-bold">
                    <?php echo date('d M Y', strtotime($return['return_date'])); ?>
                </p>
                <p style="font-size: 0.875rem !important;" class="text-gray-600 mt-1">
                    <?php echo date('h:i A', strtotime($return['return_date'])); ?>
                </p>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-red-100 shadow-sm">
                <p style="font-size: 0.875rem !important;" class="text-gray-500 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-receipt text-blue-500 mr-2"></i>Original Sale ID
                </p>
                <p style="font-size: 1.25rem !important;" class="text-gray-900 font-bold">
                    #<?php echo $return['original_sale_id']; ?>
                </p>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-red-100 shadow-sm">
                <p style="font-size: 0.875rem !important;" class="text-gray-500 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-user text-purple-500 mr-2"></i>Customer
                </p>
                <p style="font-size: 1.25rem !important;" class="text-gray-900 font-bold">
                    <?php echo $return['customer_name'] ?: 'Walk-in Customer'; ?>
                </p>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-red-100 shadow-sm">
                <p style="font-size: 0.875rem !important;" class="text-gray-500 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-tag text-orange-500 mr-2"></i>Return Reason
                </p>
                <span class="status-badge bg-orange-100 text-orange-800">
                    <?php echo $return['return_reason']; ?>
                </span>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-red-100 shadow-sm">
                <p style="font-size: 0.875rem !important;" class="text-gray-500 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-credit-card text-green-500 mr-2"></i>Refund Method
                </p>
                <span class="status-badge bg-green-100 text-green-800">
                    <?php 
                    $methods = [1 => 'Cash', 2 => 'Mobile Money', 3 => 'Bank Transfer', 4 => 'Store Credit'];
                    echo $methods[$return['refund_method']] ?? 'Unknown';
                    ?>
                </span>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-red-100 shadow-sm">
                <p style="font-size: 0.875rem !important;" class="text-gray-500 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-user-shield text-indigo-500 mr-2"></i>Processed By
                </p>
                <p style="font-size: 1.25rem !important;" class="text-gray-900 font-bold">
                    <?php echo $return['processed_by_name']; ?>
                </p>
            </div>
        </div>
        
        <?php if($return['return_notes']): ?>
        <div class="mt-6 pt-6 border-t-2 border-red-100">
            <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4">
                <p style="font-size: 0.875rem !important;" class="text-yellow-800 uppercase tracking-wide font-semibold mb-2">
                    <i class="fa fa-sticky-note mr-2"></i>Additional Notes
                </p>
                <p style="font-size: 1.125rem !important;" class="text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($return['return_notes'])); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Returned Items Table - Enhanced -->
    <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-xl overflow-hidden mb-6 animate-scale-in" style="animation-delay: 0.2s;">
        <div class="bg-gradient-to-r from-blue-50 via-blue-100 to-blue-50 px-6 py-5 border-b-2 border-blue-200">
            <div class="flex items-center justify-between">
                <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 flex items-center">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-2 mr-3">
                        <i class="fa fa-box-open text-white"></i>
                    </div>
                    Returned Items
                </h3>
                <span class="status-badge bg-blue-100 text-blue-800">
                    <?php echo count($return['items']); ?> Item<?php echo count($return['items']) != 1 ? 's' : ''; ?>
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-tag mr-2 text-gray-500"></i>Product
                        </th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-center font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-boxes mr-2 text-gray-500"></i>Quantity
                        </th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-dollar-sign mr-2 text-gray-500"></i>Unit Price
                        </th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-money-bill-wave mr-2 text-gray-500"></i>Refund Amount
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($return['items'] as $index => $item): ?>
                    <tr class="hover:bg-blue-50 transition-all animate-fade-up" style="animation-delay: <?php echo 0.1 * $index; ?>s;">
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold mr-3">
                                    <?php echo substr($item['product_name'], 0, 1); ?>
                                </div>
                                <span class="text-gray-900 font-semibold"><?php echo $item['product_name']; ?></span>
                            </div>
                        </td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-center">
                            <span class="status-badge bg-green-100 text-green-800">
                                <?php echo $item['quantity_returned']; ?>
                            </span>
                        </td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900 font-semibold">
                            <sup style="font-size: 0.7em;"><?php echo $currency; ?></sup> <?php echo number_format($item['unit_price'], 2); ?>
                        </td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900 font-bold">
                            <sup style="font-size: 0.7em;"><?php echo $currency; ?></sup> <?php echo number_format($item['refund_amount'], 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gradient-to-r from-green-50 via-green-100 to-green-50 border-t-2 border-green-200">
                    <tr>
                        <td colspan="3" style="font-size: 1.5rem !important;" class="px-6 py-5 text-right text-gray-900 font-bold uppercase tracking-wide">
                            <i class="fa fa-calculator mr-2 text-green-600"></i>Total Refund:
                        </td>
                        <td style="font-size: 1.5rem !important;" class="px-6 py-5 text-right font-bold text-green-700">
                            <sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> <?php echo number_format($return['total_refund_amount'], 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Original Sale Information - Enhanced -->
    <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-xl overflow-hidden mb-6 animate-scale-in" style="animation-delay: 0.3s;">
        <div class="bg-gradient-to-r from-purple-50 via-purple-100 to-purple-50 px-6 py-5 border-b-2 border-purple-200">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 flex items-center">
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-2 mr-3">
                    <i class="fa fa-shopping-cart text-white"></i>
                </div>
                Original Sale Information
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                    <p style="font-size: 0.875rem !important;" class="text-blue-700 uppercase tracking-wide font-semibold mb-2">
                        <i class="fa fa-hashtag mr-1"></i>Sale ID
                    </p>
                    <p style="font-size: 1.25rem !important;" class="text-gray-900 font-bold">
                        #<?php echo $return['sale_id']; ?>
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                    <p style="font-size: 0.875rem !important;" class="text-green-700 uppercase tracking-wide font-semibold mb-2">
                        <i class="fa fa-calendar mr-1"></i>Sale Date
                    </p>
                    <p style="font-size: 1.125rem !important;" class="text-gray-900 font-bold">
                        <?php echo date('d M Y', strtotime($return['sale_date'])); ?>
                    </p>
                    <p style="font-size: 0.875rem !important;" class="text-gray-600">
                        <?php echo date('h:i A', strtotime($return['sale_date'])); ?>
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                    <p style="font-size: 0.875rem !important;" class="text-orange-700 uppercase tracking-wide font-semibold mb-2">
                        <i class="fa fa-money-bill-wave mr-1"></i>Original Amount
                    </p>
                    <p style="font-size: 1.25rem !important;" class="text-gray-900 font-bold">
                        <sup style="font-size: 0.6em;"><?php echo $currency; ?></sup> <?php echo number_format($return['sale_total'], 2); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movements - Enhanced -->
    <?php if(!empty($return['stock_movements'])): ?>
    <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-xl overflow-hidden mb-6 animate-scale-in" style="animation-delay: 0.4s;">
        <div class="bg-gradient-to-r from-teal-50 via-teal-100 to-teal-50 px-6 py-5 border-b-2 border-teal-200">
            <div class="flex items-center justify-between">
                <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 flex items-center">
                    <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg p-2 mr-3">
                        <i class="fa fa-exchange-alt text-white"></i>
                    </div>
                    Stock Adjustments
                </h3>
                <span class="status-badge bg-teal-100 text-teal-800">
                    <i class="fa fa-check-circle mr-1"></i>Items Restocked
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-tag mr-2 text-gray-500"></i>Product
                        </th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-center font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-plus-circle mr-2 text-gray-500"></i>Quantity Added
                        </th>
                        <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fa fa-clock mr-2 text-gray-500"></i>Date & Time
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($return['stock_movements'] as $index => $movement): ?>
                    <tr class="hover:bg-teal-50 transition-all animate-fade-up" style="animation-delay: <?php echo 0.1 * $index; ?>s;">
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-teal-400 to-teal-600 rounded-lg flex items-center justify-center text-white font-bold mr-3">
                                    <?php echo substr($movement['product_name'], 0, 1); ?>
                                </div>
                                <span class="text-gray-900 font-semibold"><?php echo $movement['product_name']; ?></span>
                            </div>
                        </td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-center">
                            <span class="status-badge bg-green-100 text-green-800">
                                <i class="fa fa-arrow-up mr-1"></i>+<?php echo $movement['quantity']; ?>
                            </span>
                        </td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-900">
                            <div class="flex flex-col">
                                <span class="font-semibold"><?php echo date('d M Y', strtotime($movement['movement_date'])); ?></span>
                                <span class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($movement['movement_date'])); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<?php if(!isset($ajax_load)): ?>
</div>
<?php endif; ?>
