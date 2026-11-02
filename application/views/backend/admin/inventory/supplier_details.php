<!-- Supplier Details View -->
<?php include('_readable_header.php'); ?>

<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <!-- Page Header -->
    <div class="mb-12 flex items-center justify-between">
        <div>
            <button onclick="navigation('<?php echo site_url('inventory/suppliers'); ?>')" style="font-size: 1.125rem !important;" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold mb-4 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Suppliers
            </button>
            <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight" id="supplier_name">Loading...</h1>
            <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Supplier information and purchase history</p>
        </div>
        <div class="flex gap-4">
            <button onclick="editSupplier()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="inline-flex items-center px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Supplier
            </button>
        </div>
    </div>

    <!-- Supplier Information Card -->
    <div class="bg-white rounded-xl shadow-lg mb-8 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
            <h2 style="font-size: 1.75rem !important;" class="font-bold text-gray-900">Supplier Information</h2>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mb-2">Contact Person</p>
                    <p style="font-size: 1.25rem !important;" class="font-semibold text-gray-900" id="contact_person">-</p>
                </div>
                <div>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mb-2">Status</p>
                    <span id="status_badge"></span>
                </div>
                <div>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mb-2">Phone</p>
                    <p style="font-size: 1.25rem !important;" class="font-semibold text-gray-900" id="phone">-</p>
                </div>
                <div>
                    <p style="font-size: 1rem !important;" class="text-gray-600 mb-2">Email</p>
                    <p style="font-size: 1.25rem !important;" class="font-semibold text-gray-900" id="email">-</p>
                </div>
                <div class="md:col-span-2">
                    <p style="font-size: 1rem !important;" class="text-gray-600 mb-2">Address</p>
                    <p style="font-size: 1.25rem !important;" class="font-semibold text-gray-900" id="address">-</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white border-l-4 border-blue-500 rounded-xl p-8 shadow-lg">
            <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Total Products</p>
            <p style="font-size: 3rem !important;" class="font-bold text-gray-900" id="product_count">0</p>
        </div>
        <div class="bg-white border-l-4 border-green-500 rounded-xl p-8 shadow-lg">
            <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Total Purchases</p>
            <p style="font-size: 2rem !important;" class="font-bold text-gray-900" id="total_purchases"><sup style="font-size: 0.6em; vertical-align: super;"><?php echo $currency; ?></sup> 0</p>
        </div>
        <div class="bg-white border-l-4 border-purple-500 rounded-xl p-8 shadow-lg">
            <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Purchase Orders</p>
            <p style="font-size: 3rem !important;" class="font-bold text-gray-900" id="po_count">0</p>
        </div>
        <div class="bg-white border-l-4 border-orange-500 rounded-xl p-8 shadow-lg">
            <p style="font-size: 0.875rem !important;" class="font-semibold text-gray-600 uppercase tracking-wider mb-3">Last Purchase</p>
            <p style="font-size: 1.5rem !important;" class="font-bold text-gray-900" id="last_purchase">-</p>
        </div>
    </div>

    <!-- Purchase Order History -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-indigo-100">
            <h2 style="font-size: 1.75rem !important;" class="font-bold text-gray-900">Purchase Order History</h2>
        </div>
        <div class="p-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">PO ID</th>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">Date</th>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">Reference</th>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-700 uppercase tracking-wider">Items</th>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-center font-bold text-gray-700 uppercase tracking-wider">Status</th>
                            <th style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="po_history_tbody">
                        <tr><td colspan="7" style="font-size: 1.125rem !important;" class="px-6 py-10 text-center text-gray-500">Loading purchase orders...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const supplierId = <?php echo isset($supplier_id) ? $supplier_id : 'null'; ?>;

$(document).ready(function() {
    if (supplierId) {
        loadSupplierDetails();
        loadPurchaseHistory();
    } else {
        showAjaxModal_alert('Invalid supplier ID', 'error');
        setTimeout(() => navigation('<?php echo site_url('inventory/suppliers'); ?>'), 2000);
    }
});

function loadSupplierDetails() {
    $.get('<?php echo site_url('inventory/get_supplier_details/'); ?>' + supplierId, function(response) {
        const data = JSON.parse(response);
        
        if (data.status === 'error') {
            showAjaxModal_alert(data.message, 'error');
            setTimeout(() => navigation('<?php echo site_url('inventory/suppliers'); ?>'), 2000);
            return;
        }
        
        const supplier = data.supplier;
        const currency = data.currency || '<?php echo $currency; ?>';
        
        // Update header
        $('#supplier_name').text(supplier.name);
        
        // Update information
        $('#contact_person').text(supplier.contact_person || '-');
        $('#phone').text(supplier.phone || '-');
        $('#email').text(supplier.email || '-');
        $('#address').text(supplier.address || '-');
        
        // Update status badge
        const statusBadge = supplier.status == 1 
            ? '<span style="font-size: 1.125rem !important;" class="inline-flex px-3 py-1.5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>'
            : '<span style="font-size: 1.125rem !important;" class="inline-flex px-3 py-1.5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>';
        $('#status_badge').html(statusBadge);
        
        // Update statistics
        $('#product_count').text(supplier.product_count || 0);
        $('#total_purchases').html('<sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + (parseFloat(supplier.total_purchase_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
        $('#po_count').text(supplier.purchase_order_count || 0);
        $('#last_purchase').text(supplier.last_purchase_date ? new Date(supplier.last_purchase_date).toLocaleDateString('en-GB') : '-');
    }).fail(function() {
        showAjaxModal_alert('Failed to load supplier details', 'error');
    });
}

function loadPurchaseHistory() {
    $.get('<?php echo site_url('inventory/get_purchase_orders'); ?>', { supplier_id: supplierId }, function(response) {
        const data = JSON.parse(response);
        const orders = data.orders || [];
        const currency = data.currency || '<?php echo $currency; ?>';
        
        let html = '';
        if (orders.length === 0) {
            html = '<tr><td colspan="7" style="font-size: 1.125rem !important;" class="px-6 py-10 text-center text-gray-500">No purchase orders found</td></tr>';
        } else {
            orders.forEach(po => {
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'received': 'bg-green-100 text-green-800',
                    'cancelled': 'bg-red-100 text-red-800'
                };
                const statusBadge = `<span style="font-size: 1rem !important;" class="inline-flex px-3 py-1.5 font-semibold rounded-full ${statusColors[po.status] || 'bg-gray-100 text-gray-800'}">${po.status.charAt(0).toUpperCase() + po.status.slice(1)}</span>`;
                
                html += `
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 font-semibold text-gray-900">#${po.id}</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-600">${new Date(po.purchase_date).toLocaleDateString('en-GB')}</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-gray-600">${po.reference_number || '-'}</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right text-gray-900">${po.items_count || 0}</td>
                        <td style="font-size: 1.125rem !important;" class="px-6 py-4 text-right font-semibold text-gray-900">${currency} ${parseFloat(po.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="viewPODetails(${po.id})" style="font-size: 1rem !important;" class="font-semibold text-blue-600 hover:text-blue-900 transition-colors">View</button>
                        </td>
                    </tr>
                `;
            });
        }
        $('#po_history_tbody').html(html);
    }).fail(function() {
        $('#po_history_tbody').html('<tr><td colspan="7" style="font-size: 1.125rem !important;" class="px-6 py-10 text-center text-red-500">Failed to load purchase orders</td></tr>');
    });
}

function editSupplier() {
    loadModalContent('createModal', '<?php echo site_url('inventory/supplier_form/'); ?>' + supplierId, '<i class="fa fa-edit"></i> Edit Supplier');
}

function viewPODetails(poId) {
    navigation('<?php echo site_url('inventory/purchase_order_details/'); ?>' + poId);
}
</script>
