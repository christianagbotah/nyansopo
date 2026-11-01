<!-- Enterprise Sales History -->
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

/* Modern filter styling */
.modern-filters {
    background: transparent;
    border-radius: 1rem;
    padding: 0;
    margin-bottom: 2rem;
}

.filter-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.filter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #4B5563;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.75rem;
    display: block;
}

/* Uniform height for all filter inputs */
.filter-input-height {
    min-height: 3.25rem !important;
    height: 3.25rem !important;
    font-size: 1.125rem !important;
    border: 2px solid #E5E7EB !important;
    transition: all 0.2s ease !important;
}

.filter-input-height:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

/* Select2 modern styling */
.select2-container .select2-selection--single {
    min-height: 3.25rem !important;
    height: 3.25rem !important;
    display: flex !important;
    align-items: center !important;
    font-size: 1.125rem !important;
    padding: 0 1rem !important;
    border: 2px solid #E5E7EB !important;
    border-radius: 0.5rem !important;
    transition: all 0.2s ease !important;
}

.select2-container .select2-selection--single:hover {
    border-color: #9CA3AF !important;
}

.select2-container--open .select2-selection--single,
.select2-container--focus .select2-selection--single {
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 2.75rem !important;
    padding-left: 0 !important;
    font-size: 1.125rem !important;
    color: #1F2937 !important;
}

.select2-container .select2-selection--single .select2-selection__placeholder {
    color: #9CA3AF !important;
}

.select2-container .select2-selection--single .select2-selection__arrow {
    height: 3.25rem !important;
    right: 0.75rem !important;
}

.select2-container--open .select2-dropdown {
    font-size: 1.125rem !important;
    border: 2px solid #667eea !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    margin-top: 0.25rem !important;
}

.select2-results__option {
    padding: 0.75rem 1rem !important;
    font-size: 1.125rem !important;
    transition: all 0.15s ease !important;
}

.select2-results__option--highlighted {
    background-color: #667eea !important;
}

/* Modern button styling */
.modern-filter-btn {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    font-weight: 600;
    font-size: 1.125rem;
    padding: 0 2rem;
    border-radius: 0.5rem;
    min-height: 3.25rem;
    height: 3.25rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.modern-filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.modern-filter-btn:active {
    transform: translateY(0);
}

/* Date input with icon */
.date-input-wrapper {
    position: relative;
}

.date-input-wrapper .calendar-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
    font-size: 1.25rem;
    pointer-events: none;
    transition: color 0.2s ease;
}

.date-input-wrapper input:focus + .calendar-icon {
    color: #667eea;
}

/* Responsive grid */
@media (max-width: 768px) {
    .modern-filters {
        padding: 1.5rem;
    }
    
    .filter-card {
        padding: 1rem;
    }
}
</style>

<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <div class="mb-8">
        <h1 style="font-size: 2.5rem !important; font-weight: 700; line-height: 1.2;" class="text-gray-900 tracking-tight">Sales History</h1>
        <p style="font-size: 1.125rem !important; margin-top: 0.75rem;" class="text-gray-600">View and manage all sales transactions with advanced filtering</p>
    </div>

    <!-- Modern Filter Section -->
    <div class="modern-filters mb-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Start Date Filter -->
            <div class="filter-card">
                <label class="filter-label">
                    <i class="fa fa-calendar-alt mr-2"></i>Start Date
                </label>
                <div class="date-input-wrapper">
                    <input type="text" id="start_date" placeholder="dd/mm/yyyy" class="filter-input-height block w-full rounded-lg focus:outline-none" readonly>
                    <i class="fa fa-calendar calendar-icon"></i>
                </div>
            </div>
            
            <!-- End Date Filter -->
            <div class="filter-card">
                <label class="filter-label">
                    <i class="fa fa-calendar-alt mr-2"></i>End Date
                </label>
                <div class="date-input-wrapper">
                    <input type="text" id="end_date" placeholder="dd/mm/yyyy" class="filter-input-height block w-full rounded-lg focus:outline-none" readonly>
                    <i class="fa fa-calendar calendar-icon"></i>
                </div>
            </div>
            
            <!-- Customer Filter -->
            <div class="filter-card lg:col-span-2">
                <label class="filter-label">
                    <i class="fa fa-user mr-2"></i>Customer
                </label>
                <select id="filter_customer" class="w-full">
                    <option value="">All Customers</option>
                    <option value="0">Walk-in Customers</option>
                    <?php 
                    // Get students who made purchases
                    $customers = $this->db->query(
                        "SELECT DISTINCT s.student_id, s.name 
                        FROM student s 
                        INNER JOIN inventory_sales isales ON s.student_id = isales.student_id 
                        ORDER BY s.name"
                    )->result();
                    foreach($customers as $customer): 
                    ?>
                    <option value="<?php echo $customer->student_id; ?>"><?php echo $customer->name; ?></option>
                    <?php endforeach; ?>
                    
                    <?php 
                    // Get walk-in customers with custom names
                    $walkin_customers = $this->db->query(
                        "SELECT DISTINCT customer_name 
                        FROM inventory_sales 
                        WHERE (student_id IS NULL OR student_id = 0) 
                        AND customer_name IS NOT NULL 
                        AND customer_name != '' 
                        ORDER BY customer_name"
                    )->result();
                    foreach($walkin_customers as $walkin): 
                    ?>
                    <option value="walkin_<?php echo $walkin->customer_name; ?>"><?php echo $walkin->customer_name; ?> (Walk-in)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Apply Button -->
            <div class="filter-card flex items-end">
                <button onclick="loadSales()" class="modern-filter-btn w-full">
                    <i class="fa fa-filter"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Gross Sales Card -->
        <!-- Total Sales Card -->
        <div onclick="showSalesBreakdown()" class="group bg-white rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 cursor-pointer border-l-4 border-green-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-50 rounded-lg p-3">
                    <i class="fa fa-dollar-sign text-green-600 text-2xl"></i>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-3 py-1 rounded-full">REVENUE</span>
            </div>
            <h3 style="font-size: 1.125rem !important;" class="font-semibold text-gray-600 mb-4">Total Sales</h3>
            <p style="font-size: 2.5rem !important; line-height: 1.1;" class="font-bold text-gray-900" id="total_sales">
                <sup style="font-size: 1.25rem !important;" class="font-normal text-gray-500"><?php echo $currency; ?></sup> 0.00
            </p>
        </div>
        
        <!-- Transactions Card -->
        <div onclick="showTransactionsBreakdown()" class="group bg-white rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 cursor-pointer border-l-4 border-purple-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-50 rounded-lg p-3">
                    <i class="fa fa-shopping-cart text-purple-600 text-2xl"></i>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">COUNT</span>
            </div>
            <h3 style="font-size: 1.125rem !important;" class="font-semibold text-gray-600 mb-4">Transactions</h3>
            <p style="font-size: 2.5rem !important; line-height: 1.1;" class="font-bold text-gray-900" id="total_transactions">0</p>
        </div>
        
        <!-- Items Sold Card -->
        <div class="group bg-white rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 border-l-4 border-blue-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-50 rounded-lg p-3">
                    <i class="fa fa-box text-blue-600 text-2xl"></i>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">PRODUCTS</span>
            </div>
            <h3 style="font-size: 1.125rem !important;" class="font-semibold text-gray-600 mb-4">Items Sold</h3>
            <p style="font-size: 2.5rem !important; line-height: 1.1;" class="font-bold text-gray-900" id="items_sold">0</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="sales_table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Sale ID</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Date</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Customer</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Items</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="sales_tbody">
                    <tr><td colspan="6" style="font-size: 1.25rem !important;" class="px-8 py-10 text-center text-gray-500">Loading sales...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

$(document).ready(function() {
    // Initialize datepickers with dd/mm/yyyy format
    $('#start_date, #end_date').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        orientation: 'bottom auto'
    });
    
    // Initialize Select2 for customer dropdown with search
    $('#filter_customer').select2({
        placeholder: 'All Customers',
        allowClear: true,
        width: '100%'
    });
    
    // Set default date values in dd/mm/yyyy format
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#start_date').val(formatDateDisplay(firstDay));
    $('#end_date').val(formatDateDisplay(today));
    
    loadSales();
});

function loadSales() {
    $.get('<?php echo site_url('inventory/get_sales'); ?>', {
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val(),
        customer: $('#filter_customer').val()
    }, function(response) {
        const data = JSON.parse(response);
        renderSalesTable(data.sales);
        updateSummary(data.summary);
    });
}

function renderSalesTable(sales) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#sales_table')) {
        $('#sales_table').DataTable().destroy();
    }
    
    let html = '';
    const currency = '<?php echo $currency; ?>';
    
    sales.forEach(sale => {
        const customerName = sale.customer_name || 'Walk-in Customer';
        
        // Build actions cell
        const actionsCell = `
            <button onclick="editSale(${sale.id})" style="font-size: 1.125rem !important;" class="font-semibold text-blue-600 hover:text-blue-900 transition-colors mr-3" title="Edit Sale">
                <i class="fa fa-edit mr-1"></i>Edit
            </button>
            <button onclick="viewSaleDetails(${sale.id})" style="font-size: 1.125rem !important;" class="font-semibold text-green-600 hover:text-green-900 transition-colors" title="View Details">
                <i class="fa fa-eye mr-1"></i>View
            </button>
        `;
        
        html += `
            <tr class="hover:bg-blue-50 transition-colors">
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900 font-semibold">#${sale.id}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900">${new Date(sale.sale_date).toLocaleDateString('en-GB')}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900 font-medium">${customerName}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${sale.item_count} item(s)</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-semibold text-gray-900"><sup style="font-size: 0.6em; vertical-align: super;">${currency}</sup> ${parseFloat(sale.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-8 py-5 text-center">${actionsCell}</td>
            </tr>
        `;
    });
    
    $('#sales_tbody').html(html);
    
    // Initialize DataTable
    $('#sales_table').DataTable({
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "lengthChange": true,
        "info": true,
        "autoWidth": false,
        "order": [[0, 'desc']],
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
                title: 'Sales History - ' + $('#start_date').val() + ' to ' + $('#end_date').val(),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                    format: {
                        body: function(data, row, column, node) {
                            // Remove currency symbol and HTML tags
                            if(column === 4) {
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
                title: 'Sales History',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                    format: {
                        body: function(data, row, column, node) {
                            // Remove currency symbol and HTML tags
                            if(column === 4) {
                                return $(node).text().replace(/GHC|<?php echo $currency; ?>/g, '').trim();
                            }
                            return $(node).text();
                        }
                    }
                },
                customize: function(doc) {
                    doc.content[1].table.widths = ['15%', '20%', '25%', '15%', '25%'];
                    doc.styles.tableHeader.fillColor = '#3B82F6';
                    doc.styles.tableHeader.color = '#FFFFFF';
                    // Right align amount column
                    doc.content[1].table.body.forEach(function(row, index) {
                        if(index > 0) { // Skip header row
                            row[4].alignment = 'right';
                        }
                    });
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print mr-2"></i>Print',
                className: 'btn btn-info',
                title: 'Sales History',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                    format: {
                        body: function(data, row, column, node) {
                            // Remove currency symbol and HTML tags
                            if(column === 4) {
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
                    // Right align amount columns
                    $(win.document.body).find('table tbody td:nth-child(5)').css('text-align', 'right');
                    $(win.document.body).find('table thead th:nth-child(5)').css('text-align', 'right');
                }
            }
        ],
        "language": {
            "search": "Search sales:",
            "lengthMenu": "Show _MENU_ sales",
            "info": "Showing _START_ to _END_ of _TOTAL_ sales",
            "infoEmpty": "Showing 0 to 0 of 0 sales",
            "infoFiltered": "(filtered from _MAX_ total sales)",
            "zeroRecords": "No sales found",
            "emptyTable": "No sales available"
        },
        "columnDefs": [
            { "orderable": false, "targets": [5] }
        ]
    });
}

function updateSummary(summary) {
    const currency = '<?php echo $currency; ?>';
    
    // Format total sales
    const formattedTotal = parseFloat(summary.total || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Format count with commas (no decimals)
    const formattedCount = parseInt(summary.count || 0).toLocaleString('en-US');
    
    // Format items sold
    const formattedItems = parseInt(summary.items_sold || 0).toLocaleString('en-US');
    
    $('#total_sales').html('<sup style="font-size: 1.25rem !important;" class="font-normal text-gray-500">' + currency + '</sup> ' + formattedTotal);
    $('#total_transactions').text(formattedCount);
    $('#items_sold').text(formattedItems);
}

function printReceiptNow(saleId, currency, school, slogan) {
    if(!window.currentSale || !window.currentItems) { 
        showAjaxModal_alert('No sale data loaded. Please select a sale to print.', 'Warning', false, true); 
        return; 
    }
    
    const sale = window.currentSale;
    const items = window.currentItems;
    const customer = sale.customer_name || 'Walk-in Customer';
    const date = new Date(sale.sale_date);
    const dateFormatted = date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
    
    let itemsHtml = '';
    items.forEach(function(item) {
        itemsHtml += '<tr><td>' + item.product_name + '</td><td style="text-align:center">' + item.quantity + '</td><td style="text-align:right">' + currency + ' ' + parseFloat(item.total_price).toFixed(2) + '</td></tr>';
    });
    
    const printWindow = window.open('', '', 'width=400,height=600');
    const html = '<html><head><title>Receipt</title>' +
    '<style>' +
    'body{font-family:Courier,monospace;width:80mm;padding:10px;margin:0 auto}' +
    '@page{margin:0;size:80mm auto}' +
    '.header{text-align:center;margin-bottom:15px;border-bottom:2px dashed #000;padding-bottom:10px}' +
    '.header h2{font-size:18px;margin:5px 0}' +
    '.header p{font-size:11px;margin:2px 0}' +
    '.info{margin:10px 0;font-size:11px}' +
    '.info div{display:flex;justify-content:space-between;margin:3px 0}' +
    '.items{margin:10px 0}' +
    '.items table{width:100%;border-collapse:collapse}' +
    '.items th{text-align:left;border-bottom:1px solid #000;padding:5px 0;font-size:11px}' +
    '.items td{padding:5px 0;font-size:11px}' +
    '.total{margin-top:10px;padding-top:10px;border-top:2px solid #000}' +
    '.total div{display:flex;justify-content:space-between;font-size:14px;font-weight:bold;margin:5px 0}' +
    '.footer{text-align:center;margin-top:15px;padding-top:10px;border-top:2px dashed #000;font-size:10px}' +
    '</style></head><body>' +
    '<div class="header"><h2>' + school + '</h2><p>SALES RECEIPT</p><p>Receipt #' + saleId + '</p></div>' +
    '<div class="info">' +
    '<div><span>Date:</span><span>' + dateFormatted + '</span></div>' +
    '<div><span>Customer:</span><span>' + customer + '</span></div>' +
    '</div>' +
    '<div class="items"><table><thead><tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Amount</th></tr></thead><tbody>' +
    itemsHtml +
    '</tbody></table></div>' +
    '<div class="total"><div><span>TOTAL</span><span>' + currency + ' ' + parseFloat(sale.total_amount).toFixed(2) + '</span></div></div>' +
    '<div class="footer"><p>Thank you for your purchase!</p><p>' + slogan + '</p></div>' +
    '</body></html>';
    
    printWindow.document.write(html);
    printWindow.document.close();
    setTimeout(function() { printWindow.print(); }, 500);
}

function viewSaleDetails(id) {
    loadModalContent('detailsModal', '<?php echo site_url('inventory/sale_details/'); ?>' + id, '<i class="fa fa-receipt"></i> Sale Details');
}

function editSale(id) {
    loadModalContent('createModal', '<?php echo site_url('inventory/sale_form/'); ?>' + id, '<i class="fa fa-edit"></i> Edit Sale');
}

function showItemsSoldBreakdown() {
    $.get('<?php echo site_url('inventory/get_items_sold_breakdown'); ?>', {
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val(),
        customer: $('#filter_customer').val()
    }, function(response) {
        const data = JSON.parse(response);
        let html = '<div class="mb-4 flex justify-between"><input type="text" id="search_items" placeholder="Search products..." class="flex-1 px-4 py-2 border rounded mr-2" style="font-size: 1.125rem;"><button onclick="printModalContent()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700" style="font-size: 1.125rem;"><i class="fa fa-print mr-2"></i>Print</button></div>';
        html += '<table class="min-w-full"><thead class="bg-gray-100"><tr><th class="px-4 py-3 text-left" style="font-size: 1.125rem;">Product</th><th class="px-4 py-3 text-right" style="font-size: 1.125rem;">Quantity Sold</th></tr></thead><tbody id="items_tbody">';
        data.forEach(item => {
            html += '<tr class="border-b item-row"><td class="px-4 py-3" style="font-size: 1.125rem;">' + item.product_name + '</td><td class="px-4 py-3 text-right font-bold" style="font-size: 1.125rem;">' + item.total_quantity + '</td></tr>';
        });
        html += '</tbody></table>';
        showModalWithContent('detailsModal', '<i class="fa fa-box"></i> Items Sold Breakdown', html);
        $('#search_items').on('keyup', function() {
            const val = $(this).val().toLowerCase();
            $('#items_tbody .item-row').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
            });
        });
    });
}

function showSalesBreakdown() {
    $.get('<?php echo site_url('inventory/get_sales'); ?>', {
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val(),
        customer: $('#filter_customer').val()
    }, function(response) {
        const data = JSON.parse(response);
        let html = '<div class="mb-4 flex justify-between"><input type="text" id="search_sales" placeholder="Search..." class="flex-1 px-4 py-2 border rounded mr-2" style="font-size: 1.125rem;"><button onclick="printModalContent()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700" style="font-size: 1.125rem;"><i class="fa fa-print mr-2"></i>Print</button></div>';
        html += '<table class="min-w-full"><thead class="bg-gray-100"><tr><th class="px-4 py-3 text-left" style="font-size: 1.125rem;">Date</th><th class="px-4 py-3 text-left" style="font-size: 1.125rem;">Customer</th><th class="px-4 py-3 text-right" style="font-size: 1.125rem;">Amount</th></tr></thead><tbody id="sales_tbody">';
        const currency = '<?php echo $currency; ?>';
        data.sales.forEach(sale => {
            const customerName = sale.customer_name || 'Walk-in Customer';
            html += '<tr class="border-b sale-row"><td class="px-4 py-3" style="font-size: 1.125rem;">' + new Date(sale.sale_date).toLocaleDateString('en-GB') + '</td><td class="px-4 py-3" style="font-size: 1.125rem;">' + customerName + '</td><td class="px-4 py-3 text-right font-bold" style="font-size: 1.125rem;"><sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + parseFloat(sale.total_amount).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
        showModalWithContent('detailsModal', '<i class="fa fa-chart-line"></i> Sales Breakdown', html);
        $('#search_sales').on('keyup', function() {
            const val = $(this).val().toLowerCase();
            $('#sales_tbody .sale-row').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
            });
        });
    });
}

function showTransactionsBreakdown() {
    $.get('<?php echo site_url('inventory/get_sales'); ?>', {
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val(),
        customer: $('#filter_customer').val()
    }, function(response) {
        const data = JSON.parse(response);
        let html = '<div class="mb-4 flex justify-between"><input type="text" id="search_trans" placeholder="Search..." class="flex-1 px-4 py-2 border rounded mr-2" style="font-size: 1.125rem;"><button onclick="printModalContent()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700" style="font-size: 1.125rem;"><i class="fa fa-print mr-2"></i>Print</button></div>';
        html += '<table class="min-w-full"><thead class="bg-gray-100"><tr><th class="px-4 py-3 text-left" style="font-size: 1.125rem;">Date</th><th class="px-4 py-3 text-left" style="font-size: 1.125rem;">Customer</th><th class="px-4 py-3 text-center" style="font-size: 1.125rem;">Items</th><th class="px-4 py-3 text-right" style="font-size: 1.125rem;">Amount</th></tr></thead><tbody id="trans_tbody">';
        const currency = '<?php echo $currency; ?>';
        data.sales.forEach(sale => {
            const customerName = sale.customer_name || 'Walk-in Customer';
            html += '<tr class="border-b trans-row hover:bg-gray-50 cursor-pointer" onclick="viewSaleDetails(' + sale.id + ')"><td class="px-4 py-3" style="font-size: 1.125rem;">' + new Date(sale.sale_date).toLocaleDateString('en-GB') + '</td><td class="px-4 py-3" style="font-size: 1.125rem;">' + customerName + '</td><td class="px-4 py-3 text-center" style="font-size: 1.125rem;">' + sale.item_count + '</td><td class="px-4 py-3 text-right font-bold" style="font-size: 1.125rem;"><sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + parseFloat(sale.total_amount).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
        showModalWithContent('detailsModal', '<i class="fa fa-shopping-cart"></i> Transactions Breakdown', html);
        $('#search_trans').on('keyup', function() {
            const val = $(this).val().toLowerCase();
            $('#trans_tbody .trans-row').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
            });
        });
    });
}

function printModalContent() {
    const title = $('#detailsModal .modal-title').text();
    const content = $('#detailsModal .modal-body').html();
    const startDate = new Date($('#start_date').val()).toLocaleDateString('en-GB', {day: '2-digit', month: 'long', year: 'numeric'});
    const endDate = new Date($('#end_date').val()).toLocaleDateString('en-GB', {day: '2-digit', month: 'long', year: 'numeric'});
    const dateRange = 'Period: ' + startDate + ' to ' + endDate;
    
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>' + title + '</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
    printWindow.document.write('th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }');
    printWindow.document.write('th { background-color: #f3f4f6; font-weight: bold; }');
    printWindow.document.write('button, input { display: none; }');
    printWindow.document.write('.text-right { text-align: right; }');
    printWindow.document.write('.text-center { text-align: center; }');
    printWindow.document.write('.date-range { color: #666; margin: 10px 0 20px 0; font-size: 14px; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write('<h2>' + title + '</h2>');
    printWindow.document.write('<div class="date-range">' + dateRange + '</div>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>
