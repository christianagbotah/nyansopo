<!-- Purchase Orders Interface -->
<?php include('_readable_header.php'); ?>

<style>
/* DataTables Buttons Styling */
.dt-buttons {
    margin-bottom: 1rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.dt-buttons .btn {
    padding: 0.625rem 1.25rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dt-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.dt-buttons .btn-secondary {
    background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
    color: white;
}

.dt-buttons .btn-success {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
}

.dt-buttons .btn-danger {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
}

.dt-buttons .btn-info {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    color: white;
}
</style>

<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <div class="mb-12">
        <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight">Purchase Orders</h1>
        <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Manage inventory purchases and stock replenishment</p>
    </div>

    <!-- Action Buttons -->
    <div class="mb-8 flex gap-4">
        <button onclick="openPurchaseModal()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="px-6 py-3 font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <i class="fa fa-plus mr-2"></i> New Purchase Order
        </button>
        <button onclick="exportPurchaseOrders()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="px-6 py-3 font-semibold rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            <i class="fa fa-download mr-2"></i> Export to CSV
        </button>
    </div>

    <!-- Status Filter Tabs - Combined in Single Row for Desktop -->
    <div class="mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Order Status Section -->
            <div>
                <h3 style="font-size: 1.25rem !important;" class="font-bold text-gray-700 mb-3">Order Status</h3>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="filterByStatus('')" id="tab_all" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-blue-600 text-white status-tab">
                        All Orders
                    </button>
                    <button onclick="filterByStatus('pending')" id="tab_pending" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 status-tab">
                        Pending
                    </button>
                    <button onclick="filterByStatus('received')" id="tab_received" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 status-tab">
                        Received
                    </button>
                    <button onclick="filterByStatus('cancelled')" id="tab_cancelled" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 status-tab">
                        Cancelled
                    </button>
                </div>
            </div>
            <!-- Payment Status Section -->
            <div>
                <h3 style="font-size: 1.25rem !important;" class="font-bold text-gray-700 mb-3">Payment Status</h3>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="filterByPaymentStatus('')" id="payment_tab_all" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-green-600 text-white payment-status-tab">
                        All Payments
                    </button>
                    <button onclick="filterByPaymentStatus('unpaid')" id="payment_tab_unpaid" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 payment-status-tab">
                        <i class="fa fa-circle text-red-500 mr-1"></i> Unpaid
                    </button>
                    <button onclick="filterByPaymentStatus('partially_paid')" id="payment_tab_partially_paid" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 payment-status-tab">
                        <i class="fa fa-circle text-yellow-500 mr-1"></i> Partial
                    </button>
                    <button onclick="filterByPaymentStatus('fully_paid')" id="payment_tab_fully_paid" style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 payment-status-tab">
                        <i class="fa fa-circle text-green-500 mr-1"></i> Paid
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-6 mb-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
            <div>
                <label style="font-size: 1.125rem !important;" class="block font-semibold text-gray-700 mb-3">Start Date</label>
                <div class="relative">
                    <input type="text" id="filter_start_date" placeholder="dd/mm/yyyy" value="<?php echo date('01/m/Y'); ?>" style="font-size: 1.125rem !important; min-height: 3.5rem !important; padding-right: 3rem;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" readonly>
                    <i class="fa fa-calendar absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl pointer-events-none"></i>
                </div>
            </div>
            <div>
                <label style="font-size: 1.125rem !important;" class="block font-semibold text-gray-700 mb-3">End Date</label>
                <div class="relative">
                    <input type="text" id="filter_end_date" placeholder="dd/mm/yyyy" value="<?php echo date('d/m/Y'); ?>" style="font-size: 1.125rem !important; min-height: 3.5rem !important; padding-right: 3rem;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" readonly>
                    <i class="fa fa-calendar absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl pointer-events-none"></i>
                </div>
            </div>
            <div>
                <label style="font-size: 1.125rem !important;" class="block font-semibold text-gray-700 mb-3">Supplier</label>
                <select id="filter_supplier" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Suppliers</option>
                    <?php 
                    $suppliers = $this->db->get_where('inventory_suppliers', ['status' => 1])->result();
                    foreach($suppliers as $supplier): 
                    ?>
                    <option value="<?php echo $supplier->id; ?>"><?php echo $supplier->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="loadPurchaseOrders()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-4 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-xl p-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 1.125rem !important;" class="font-semibold text-blue-700 mb-3">Total Orders</p>
                    <p style="font-size: 2rem !important;" class="font-bold text-blue-900" id="total_orders_count">0</p>
                </div>
                <div class="bg-blue-500 rounded-full p-4">
                    <i class="fa fa-shopping-cart text-white text-3xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 border-l-4 border-green-500 rounded-xl p-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 1.125rem !important;" class="font-semibold text-green-700 mb-3">Total Amount</p>
                    <p style="font-size: 2rem !important;" class="font-bold text-green-900" id="total_purchase_amount"><sup style="font-size: 0.5em; vertical-align: super;"><?php echo $currency; ?></sup> 0.00</p>
                </div>
                <div class="bg-green-500 rounded-full p-4">
                    <i class="fa fa-dollar-sign text-white text-3xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border-l-4 border-yellow-500 rounded-xl p-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 1.125rem !important;" class="font-semibold text-yellow-700 mb-3">Pending Orders</p>
                    <p style="font-size: 2rem !important;" class="font-bold text-yellow-900" id="pending_orders_count">0</p>
                </div>
                <div class="bg-yellow-500 rounded-full p-4">
                    <i class="fa fa-clock text-white text-3xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-xl p-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 1.125rem !important;" class="font-semibold text-purple-700 mb-3">Pending Value</p>
                    <p style="font-size: 2rem !important;" class="font-bold text-purple-900" id="pending_amount"><sup style="font-size: 0.5em; vertical-align: super;"><?php echo $currency; ?></sup> 0.00</p>
                </div>
                <div class="bg-purple-500 rounded-full p-4">
                    <i class="fa fa-hourglass-half text-white text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="purchase_orders_table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">PO ID</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Date</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Supplier</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Items</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Total Amount (<?php echo $currency; ?>)</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Amount Paid (<?php echo $currency; ?>)</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Balance (<?php echo $currency; ?>)</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Payment Status</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Order Status</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="purchase_orders_tbody">
                    <tr><td colspan="10" style="font-size: 1.25rem !important;" class="px-8 py-10 text-center text-gray-500">Loading purchase orders...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* SweetAlert2 button fix - ensure buttons are visible */
.swal2-popup {
    z-index: 10000 !important;
}
.swal2-container {
    z-index: 9999 !important;
}
.swal2-actions {
    display: flex !important;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
}
.swal2-confirm,
.swal2-cancel {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    padding: 10px 24px !important;
    font-size: 16px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
}
.swal2-styled.swal2-confirm {
    background-color: #3085d6 !important;
    border: none !important;
}
.swal2-styled.swal2-cancel {
    background-color: #d33 !important;
    border: none !important;
}
.swal2-html-container {
    margin: 1em 1em 0.3em !important;
}
</style>

<script>
// Helper function to format date as dd/mm/yyyy
function formatDateDisplay(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

let currentStatusFilter = '';
let currentPaymentStatusFilter = '';

$(document).ready(function() {
    // Initialize datepickers with dd/mm/yyyy format
    $('#filter_start_date, #filter_end_date').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        orientation: 'bottom auto'
    });
    
    // Dates are already set in PHP with dd/mm/yyyy format
    loadPurchaseOrders();
});

function filterByStatus(status) {
    currentStatusFilter = status;
    $('.status-tab').removeClass('bg-blue-600 text-white').addClass('bg-gray-200 text-gray-700');
    $('#tab_' + (status || 'all')).removeClass('bg-gray-200 text-gray-700').addClass('bg-blue-600 text-white');
    loadPurchaseOrders();
}

function filterByPaymentStatus(paymentStatus) {
    currentPaymentStatusFilter = paymentStatus;
    $('.payment-status-tab').removeClass('bg-green-600 text-white').addClass('bg-gray-200 text-gray-700');
    $('#payment_tab_' + (paymentStatus || 'all')).removeClass('bg-gray-200 text-gray-700').addClass('bg-green-600 text-white');
    loadPurchaseOrders();
}

function loadPurchaseOrders() {
    $.get('<?php echo site_url('inventory/get_purchase_orders'); ?>', {
        start_date: $('#filter_start_date').val(),
        end_date: $('#filter_end_date').val(),
        supplier_id: $('#filter_supplier').val(),
        status: currentStatusFilter,
        payment_status: currentPaymentStatusFilter
    }, function(response) {
        const data = JSON.parse(response);
        if(data.status === 'success') {
            renderPurchaseOrdersTable(data.data.orders);
            updateSummary(data.data.summary);
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Error loading purchase orders: ' + data.message,
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    }).fail(function() {
        Swal.fire({
            title: 'Error!',
            text: 'Failed to load purchase orders. Please refresh the page and try again.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    });
}

function renderPurchaseOrdersTable(orders) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#purchase_orders_table')) {
        $('#purchase_orders_table').DataTable().destroy();
    }
    
    // Store purchase orders globally for menu access
    allPurchaseOrders = orders || [];
    
    let html = '';
    const currency = '<?php echo $currency; ?>';
    
    // Don't add "no data" row - let DataTable handle it
    orders.forEach(po => {
        const statusColors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'received': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        const statusColor = statusColors[po.status] || 'bg-gray-100 text-gray-800';
        
        // Payment status colors
        const paymentStatusColors = {
            'unpaid': 'bg-red-100 text-red-800',
            'partially_paid': 'bg-yellow-100 text-yellow-800',
            'fully_paid': 'bg-green-100 text-green-800'
        };
        const paymentStatusColor = paymentStatusColors[po.payment_status] || 'bg-gray-100 text-gray-800';
        
        // Calculate amounts
        const amountPaid = parseFloat(po.amount_paid || 0);
        const totalAmount = parseFloat(po.total_amount);
        const balance = totalAmount - amountPaid;
        
        // Payment status label
        const paymentStatusLabel = po.payment_status === 'unpaid' ? 'UNPAID' : 
                                   po.payment_status === 'partially_paid' ? 'PARTIAL' : 
                                   'PAID';
        
        html += `
            <tr class="hover:bg-blue-50 transition-colors">
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900 font-semibold">${po.po_code || '#' + po.id}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900">${formatDateDisplay(po.purchase_date)}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900 font-medium">${po.supplier_name || 'N/A'}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-center text-gray-600">${po.items_count}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-semibold text-gray-900">${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-semibold text-green-600">${amountPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-semibold ${balance > 0 ? 'text-red-600' : 'text-gray-600'}">${balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-8 py-5 text-center">
                    <span style="font-size: 1rem !important;" class="px-3 py-1 rounded-full font-semibold ${paymentStatusColor}">${paymentStatusLabel}</span>
                </td>
                <td class="px-8 py-5 text-center">
                    <span style="font-size: 1rem !important;" class="px-3 py-1 rounded-full font-semibold ${statusColor}">${po.status.toUpperCase()}</span>
                </td>
                <td class="px-8 py-5 text-center relative">
                    <button onclick="toggleActionMenu(${po.id}, event)" class="inline-flex items-center justify-center w-10 h-10 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fa fa-ellipsis-v text-xl"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    $('#purchase_orders_tbody').html(html);
    
    // Initialize DataTable
    $('#purchase_orders_table').DataTable({
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "lengthChange": true,
        "info": true,
        "autoWidth": false,
        "order": [[1, 'desc']],
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>Brt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        "buttons": [
            {
                extend: 'colvis',
                text: '<i class="fa fa-columns mr-2"></i>Columns',
                className: 'btn btn-secondary'
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel mr-2"></i>Excel',
                className: 'btn btn-success',
                title: 'Purchase Orders - ' + $('#filter_start_date').val() + ' to ' + $('#filter_end_date').val(),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                    format: {
                        body: function(data, row, column, node) {
                            // Remove currency symbol from amount columns (4 and 5)
                            if(column === 4 || column === 5) {
                                return $(node).text().replace(/GHC|<?php echo $currency; ?>/g, '').trim();
                            }
                            return $(node).text();
                        }
                    }
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf mr-2"></i>PDF',
                className: 'btn btn-danger',
                title: 'Purchase Orders',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                    format: {
                        body: function(data, row, column, node) {
                            // Remove currency symbol from amount columns (4 and 5)
                            if(column === 4 || column === 5) {
                                return $(node).text().replace(/GHC|<?php echo $currency; ?>/g, '').trim();
                            }
                            return $(node).text();
                        }
                    }
                },
                customize: function(doc) {
                    doc.content[1].table.widths = ['8%', '12%', '18%', '8%', '12%', '12%', '10%', '10%', '10%'];
                    doc.styles.tableHeader.fillColor = '#8B5CF6';
                    doc.styles.tableHeader.color = '#FFFFFF';
                    // Right align numeric columns (quantity and amount columns)
                    doc.content[1].table.body.forEach(function(row, index) {
                        if(index > 0) { // Skip header row
                            row[3].alignment = 'right'; // Quantity column
                            row[4].alignment = 'right'; // Total amount column
                            row[5].alignment = 'right'; // Amount paid column
                        }
                    });
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print mr-2"></i>Print',
                className: 'btn btn-info',
                title: 'Purchase Orders',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                    format: {
                        body: function(data, row, column, node) {
                            // Remove currency symbol from amount columns (4 and 5)
                            if(column === 4 || column === 5) {
                                return $(node).text().replace(/GHC|<?php echo $currency; ?>/g, '').trim();
                            }
                            return $(node).text();
                        }
                    }
                },
                customize: function(win) {
                    $(win.document.body).css('font-size', '10pt').css('color', '#000000');
                    $(win.document.body).find('table').addClass('compact').css('font-size', 'inherit').css('color', '#000000');
                    $(win.document.body).find('h1').css('color', '#000000');
                    // Right align numeric columns
                    $(win.document.body).find('table tbody td:nth-child(4), table tbody td:nth-child(5), table tbody td:nth-child(6)').css('text-align', 'right');
                    $(win.document.body).find('table thead th:nth-child(4), table thead th:nth-child(5), table thead th:nth-child(6)').css('text-align', 'right');
                }
            }
        ],
        "language": {
            "search": "Search orders:",
            "lengthMenu": "Show _MENU_ orders",
            "info": "Showing _START_ to _END_ of _TOTAL_ orders",
            "infoEmpty": "Showing 0 to 0 of 0 orders",
            "infoFiltered": "(filtered from _MAX_ total orders)",
            "zeroRecords": "No purchase orders found",
            "emptyTable": "No orders available"
        },
        "columnDefs": [
            { "orderable": false, "targets": [9] }
        ]
    });
}

function updateSummary(summary) {
    const currency = '<?php echo $currency; ?>';
    $('#total_orders_count').text((summary.total_count || 0).toLocaleString('en-US'));
    $('#total_purchase_amount').html('<sup style="font-size: 0.5em; vertical-align: super;">' + currency + '</sup> ' + parseFloat(summary.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#pending_orders_count').text((summary.pending_count || 0).toLocaleString('en-US'));
    $('#pending_amount').html('<sup style="font-size: 0.5em; vertical-align: super;">' + currency + '</sup> ' + parseFloat(summary.pending_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

function openPurchaseModal() {
    loadModalContent('createModal', '<?php echo site_url('inventory/purchase_order_form'); ?>', '<i class="fa fa-plus"></i> New Purchase Order');
}

// Note: addPurchaseItem, removePurchaseItem, renderPurchaseItems are now handled within the modal form loaded via loadModalContent

// Note: submitPurchaseOrder is now handled within the modal form loaded via loadModalContent

function viewPODetails(poId) {
    loadModalContent('detailsModal', '<?php echo site_url('inventory/purchase_order_details/'); ?>' + poId, '<i class="fa fa-shopping-cart"></i> Purchase Order Details');
}

function editPurchaseOrder(poId) {
    loadModalContent('editModal', '<?php echo site_url('inventory/purchase_order_edit_form/'); ?>' + poId, '<i class="fa fa-edit"></i> Edit Purchase Order');
}

function deletePurchaseOrder(poId) {
    Swal.fire({
        title: 'Delete Purchase Order?',
        text: 'This action cannot be undone. All stock movements and financial records will be reversed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Prepare CSRF data
            var csrfData = {};
            csrfData['<?= $this->security->get_csrf_token_name() ?>'] = '<?= $this->security->get_csrf_hash() ?>';
            
            $.ajax({
                url: '<?php echo site_url('inventory/delete_purchase_order'); ?>',
                type: 'POST',
                data: $.extend({ 
                    po_id: poId
                }, csrfData),
                dataType: 'json',
                success: function(data) {
                    if(data.status === 'success') {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Purchase order has been deleted successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                        loadPurchaseOrders();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Delete error:', xhr.responseText);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error deleting purchase order. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }
    });
}

function recordPayment(poId) {
    loadModalContent('modal_ajax', '<?php echo site_url('modal/popup/inventory/modal_record_purchase_payment/'); ?>' + poId, '<i class="fa fa-money-bill"></i> Record Payment');
}

function exportPurchaseOrders() {
    const params = new URLSearchParams({
        start_date: $('#filter_start_date').val(),
        end_date: $('#filter_end_date').val(),
        supplier_id: $('#filter_supplier').val(),
        status: currentStatusFilter
    });
    window.location.href = '<?php echo site_url('inventory/export_purchase_orders'); ?>?' + params.toString();
}

// Floating action menu container (created once, reused for all rows)
let actionMenuContainer = null;
let currentMenuPoId = null;

// Action menu functions
function toggleActionMenu(poId, event) {
    event.stopPropagation();
    
    // Get button position
    const button = event.currentTarget;
    const buttonRect = button.getBoundingClientRect();
    
    // If clicking the same button, close the menu
    if (currentMenuPoId === poId && actionMenuContainer && !actionMenuContainer.classList.contains('hidden')) {
        hideActionMenu();
        return;
    }
    
    // Get purchase order data
    const po = allPurchaseOrders.find(p => p.id == poId);
    if (!po) return;
    
    const balance = parseFloat(po.total_amount || 0) - parseFloat(po.amount_paid || 0);
    
    // Create or update menu container
    if (!actionMenuContainer) {
        actionMenuContainer = document.createElement('div');
        actionMenuContainer.id = 'floating_action_menu';
        actionMenuContainer.className = 'fixed z-[9999] w-56 rounded-lg bg-white shadow-2xl border border-gray-200';
        document.body.appendChild(actionMenuContainer);
    }
    
    // Build menu content
    let menuHTML = '<div class="py-1">';
    menuHTML += `
        <button onclick="viewPODetails(${po.id}); hideActionMenu();" style="font-size: 1.125rem !important;" class="w-full text-left px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors flex items-center">
            <i class="fa fa-eye mr-3 w-5"></i> View Details
        </button>
    `;
    if (po.status !== 'cancelled' && balance > 0) {
        menuHTML += `
            <button onclick="recordPayment(${po.id}); hideActionMenu();" style="font-size: 1.125rem !important;" class="w-full text-left px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors flex items-center">
                <i class="fa fa-money-bill mr-3 w-5"></i> Record Payment
            </button>
        `;
    }
    menuHTML += `
        <button onclick="editPurchaseOrder(${po.id}); hideActionMenu();" style="font-size: 1.125rem !important;" class="w-full text-left px-4 py-3 text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 transition-colors flex items-center">
            <i class="fa fa-edit mr-3 w-5"></i> Edit
        </button>
        <button onclick="deletePurchaseOrder(${po.id}); hideActionMenu();" style="font-size: 1.125rem !important;" class="w-full text-left px-4 py-3 text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors flex items-center">
            <i class="fa fa-trash mr-3 w-5"></i> Delete
        </button>
    `;
    menuHTML += '</div>';
    
    actionMenuContainer.innerHTML = menuHTML;
    
    // Position menu near the button
    const menuWidth = 224; // 56 * 4 = 224px (w-56)
    const menuHeight = 200; // Approximate height
    
    // Calculate position (align to right of button, below it)
    // Use pageY/pageX instead of scrollY/scrollX for better accuracy
    let top = buttonRect.bottom + 5;
    let left = buttonRect.right - menuWidth;
    
    // Adjust if menu goes off screen
    if (left < 10) left = 10;
    if (left + menuWidth > window.innerWidth - 10) {
        left = window.innerWidth - menuWidth - 10;
    }
    if (top + menuHeight > window.innerHeight - 10) {
        top = buttonRect.top - menuHeight - 5;
    }
    
    actionMenuContainer.style.top = top + 'px';
    actionMenuContainer.style.left = left + 'px';
    actionMenuContainer.classList.remove('hidden');
    
    currentMenuPoId = poId;
}

function hideActionMenu() {
    if (actionMenuContainer) {
        actionMenuContainer.classList.add('hidden');
        currentMenuPoId = null;
    }
}

// Store all purchase orders for menu access
let allPurchaseOrders = [];

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (actionMenuContainer && 
        !actionMenuContainer.contains(event.target) && 
        !event.target.closest('button[onclick*="toggleActionMenu"]')) {
        hideActionMenu();
    }
});

// Close on scroll
window.addEventListener('scroll', function() {
    if (actionMenuContainer && !actionMenuContainer.classList.contains('hidden')) {
        hideActionMenu();
    }
}, true);
</script>
