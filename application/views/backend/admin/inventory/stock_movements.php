<!-- Stock Movements -->
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
    <div class="mb-12 flex items-center justify-between">
        <div>
            <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight">Stock Movements</h1>
            <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Track inventory in and out transactions</p>
        </div>
        <div class="flex space-x-4">
            <button onclick="openStockInModal()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="inline-flex items-center px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Stock In
            </button>
            <button onclick="openStockOutModal()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="inline-flex items-center px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
                Stock Out
            </button>
        </div>
    </div>

    <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-6 mb-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
            <div>
                <label style="font-size: 1.125rem !important;" class="block font-semibold text-gray-700 mb-3">Product</label>
                <select id="filter_product" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Products</option>
                    <?php foreach($this->db->get('inventory_products')->result() as $p): ?>
                    <option value="<?php echo $p->id; ?>"><?php echo $p->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size: 1.125rem !important;" class="block font-semibold text-gray-700 mb-3">Start Date</label>
                <input type="date" id="start_date" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label style="font-size: 1.125rem !important;" class="block font-semibold text-gray-700 mb-3">End Date</label>
                <input type="date" id="end_date" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button onclick="loadMovements()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-gray-300 font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="movements_table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Date</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Product</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Type</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Quantity</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Notes</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">By</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="movements_tbody">
                    <tr><td colspan="6" style="font-size: 1.25rem !important;" class="px-8 py-10 text-center text-gray-500">Loading movements...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let movementsTable;

$(document).ready(function() {
    const today = new Date().toISOString().split('T')[0];
    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    $('#start_date').val(firstDay);
    $('#end_date').val(today);
    loadMovements();
});

function loadMovements() {
    $.get('<?php echo site_url('inventory/get_movements'); ?>', {
        product_id: $('#filter_product').val(),
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val()
    }, function(response) {
        renderMovementsTable(JSON.parse(response));
    });
}

function renderMovementsTable(movements) {
    // Destroy existing DataTable if it exists
    if (movementsTable) {
        movementsTable.destroy();
    }
    
    let html = '';
    if(movements.length === 0) {
        html = '<tr><td colspan="6" style="font-size: 1.25rem !important;" class="px-8 py-16 text-center text-gray-500">No movements found</td></tr>';
    } else {
        movements.forEach(mov => {
            const isIn = mov.movement_type === 'in';
            const badgeClass = isIn ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const typeText = isIn ? 'Stock In' : 'Stock Out';
            const sign = isIn ? '+' : '-';
            
            html += `
                <tr class="hover:bg-blue-50 transition-colors">
                    <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-900">${new Date(mov.movement_date).toLocaleDateString('en-GB')}</td>
                    <td style="font-size: 1.25rem !important;" class="px-8 py-5 font-semibold text-gray-900 leading-relaxed">${mov.product_name}</td>
                    <td class="px-8 py-5 text-center">
                        <span style="font-size: 1.125rem !important;" class="inline-flex px-3 py-1.5 font-semibold rounded-full ${badgeClass}">${typeText}</span>
                    </td>
                    <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold">${sign}${mov.quantity}</td>
                    <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${mov.notes || '-'}</td>
                    <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${mov.performed_by_name || 'System'}</td>
                </tr>
            `;
        });
    }
    $('#movements_tbody').html(html);
    
    // Initialize DataTable with export buttons
    if (movements.length > 0) {
        movementsTable = $('#movements_table').DataTable({
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
                    title: 'Stock Movements - ' + $('#start_date').val() + ' to ' + $('#end_date').val(),
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5],
                        format: {
                            body: function(data, row, column, node) {
                                // Clean quantity column (remove + or - sign for export)
                                if(column === 3) {
                                    return $(node).text().replace(/[\+\-]/g, '').trim();
                                }
                                // Clean type badges - extract text only
                                if(column === 2) {
                                    return $(node).text().trim();
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
                    title: 'Stock Movements',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5],
                        format: {
                            body: function(data, row, column, node) {
                                // Clean quantity column
                                if(column === 3) {
                                    return $(node).text().replace(/[\+\-]/g, '').trim();
                                }
                                // Clean type badges
                                if(column === 2) {
                                    return $(node).text().trim();
                                }
                                return $(node).text();
                            }
                        }
                    },
                    customize: function(doc) {
                        doc.content[1].table.widths = ['12%', '25%', '12%', '12%', '27%', '12%'];
                        doc.styles.tableHeader.fillColor = '#6B7280';
                        doc.styles.tableHeader.color = '#FFFFFF';
                        // Right align quantity column
                        doc.content[1].table.body.forEach(function(row, index) {
                            if(index > 0) { // Skip header row
                                row[3].alignment = 'right'; // Quantity column
                            }
                        });
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print mr-2"></i>Print',
                    className: 'btn btn-info',
                    title: 'Stock Movements',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5],
                        format: {
                            body: function(data, row, column, node) {
                                // Clean quantity column
                                if(column === 3) {
                                    return $(node).text().replace(/[\+\-]/g, '').trim();
                                }
                                // Clean type badges
                                if(column === 2) {
                                    return $(node).text().trim();
                                }
                                return $(node).text();
                            }
                        }
                    },
                    customize: function(win) {
                        $(win.document.body).css('font-size', '10pt').css('color', '#000000');
                        $(win.document.body).find('table').addClass('compact').css('font-size', 'inherit').css('color', '#000000');
                        $(win.document.body).find('h1').css('color', '#000000');
                        // Right align quantity column
                        $(win.document.body).find('table tbody td:nth-child(4)').css('text-align', 'right');
                        $(win.document.body).find('table thead th:nth-child(4)').css('text-align', 'right');
                    }
                }
            ],
            "language": {
                "search": "Search movements:",
                "lengthMenu": "Show _MENU_ movements",
                "info": "Showing _START_ to _END_ of _TOTAL_ movements",
                "infoEmpty": "Showing 0 to 0 of 0 movements",
                "infoFiltered": "(filtered from _MAX_ total movements)",
                "zeroRecords": "No stock movements found",
                "emptyTable": "No movements available"
            }
        });
    }
}

function openStockInModal() {
    loadModalContent('createModal', '<?php echo site_url('inventory/stock_in_form'); ?>', '<i class="fa fa-arrow-down"></i> Stock In');
}

function openStockOutModal() {
    loadModalContent('createModal', '<?php echo site_url('inventory/stock_out_form'); ?>', '<i class="fa fa-arrow-up"></i> Stock Out');
}
</script>
