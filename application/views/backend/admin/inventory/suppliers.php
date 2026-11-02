<!-- Suppliers Management -->
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

/* Fix for AdminLTE auto min-height */
.content-wrapper {
    min-height: auto !important;
    height: auto !important;
}

/* Ensure table and containers don't clip the popup menu */
.inventory-content .bg-white,
.inventory-content .overflow-x-auto,
.inventory-content table {
    overflow: visible !important;
}

/* Ensure popup menu floats above all content */
.supplier-action-popup-menu {
    position: fixed !important;
    z-index: 99999 !important;
}
</style>

<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <div class="mb-12 flex items-center justify-between">
        <div>
            <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight">Suppliers</h1>
            <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Manage product suppliers and vendors</p>
        </div>
        <button onclick="openAddSupplierModal()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="inline-flex items-center px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Supplier
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="suppliers_table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Supplier Name</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Contact Person</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Phone</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Email</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Products</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-center font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="suppliers_tbody">
                    <tr><td colspan="7" style="font-size: 1.25rem !important;" class="px-8 py-10 text-center text-gray-500">Loading suppliers...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() { 
    loadSuppliers();
    
    // Force remove AdminLTE auto-calculated min-height
    function removeAutoMinHeight() {
        $('.content-wrapper').css('min-height', '');
        $('.content-wrapper').attr('style', function(i, style) {
            if (style) {
                return style.replace(/min-height\s*:\s*[^;]+;?/gi, '');
            }
        });
    }
    
    // Run immediately and on window resize
    removeAutoMinHeight();
    $(window).on('resize', removeAutoMinHeight);
    
    // Run after any DOM changes
    setInterval(removeAutoMinHeight, 1000);
});

function loadSuppliers() {
    $.get('<?php echo site_url('inventory/get_suppliers'); ?>', function(response) {
        renderSuppliersTable(JSON.parse(response));
    });
}

function renderSuppliersTable(suppliers) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#suppliers_table')) {
        $('#suppliers_table').DataTable().destroy();
    }
    
    let html = '';
    // Don't add "no data" row - let DataTable handle it
    suppliers.forEach(supplier => {
        const statusBadge = supplier.status == 1 
            ? '<span style="font-size: 1rem !important;" class="inline-flex px-3 py-1.5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>'
            : '<span style="font-size: 1rem !important;" class="inline-flex px-3 py-1.5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>';
        
        html += `
            <tr class="hover:bg-blue-50 transition-colors">
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 font-semibold text-gray-900 leading-relaxed">${supplier.name}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${supplier.contact_person || '-'}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${supplier.phone || '-'}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${supplier.email || '-'}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right text-gray-900 font-medium">${supplier.product_count || 0}</td>
                <td class="px-8 py-5 text-center">${statusBadge}</td>
                <td class="px-8 py-5 text-center">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 1.125rem; padding: 0.5rem 1rem;">
                            <i class="fa fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" style="font-size: 1.125rem;">
                            <li><a href="javascript:void(0);" onclick="viewSupplierDetails(${supplier.id})" style="padding: 0.5rem 1.5rem; display: block;"><i class="fa fa-eye text-primary"></i> View Details</a></li>
                            <li role="separator" class="divider" style="height: 1px; margin: 0.5rem 0; background-color: #e5e7eb;"></li>
                            <li><a href="javascript:void(0);" onclick="editSupplier(${supplier.id})" style="padding: 0.5rem 1.5rem; display: block;"><i class="fa fa-edit text-info"></i> Edit Supplier</a></li>
                            <li role="separator" class="divider" style="height: 1px; margin: 0.5rem 0; background-color: #e5e7eb;"></li>
                            ${supplier.status == 1 ? 
                                '<li><a href="javascript:void(0);" onclick="toggleSupplierStatus(' + supplier.id + ', 1)" style="padding: 0.5rem 1.5rem; display: block;"><i class="fa fa-ban text-warning"></i> Deactivate</a></li>' : 
                                '<li><a href="javascript:void(0);" onclick="toggleSupplierStatus(' + supplier.id + ', 0)" style="padding: 0.5rem 1.5rem; display: block;"><i class="fa fa-check-circle text-success"></i> Activate</a></li>'
                            }
                            <li role="separator" class="divider" style="height: 1px; margin: 0.5rem 0; background-color: #e5e7eb;"></li>
                            <li><a href="javascript:void(0);" onclick="deleteSupplier(${supplier.id})" style="padding: 0.5rem 1.5rem; display: block; color: #dc2626;"><i class="fa fa-trash"></i> Delete</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    });
    
    $('#suppliers_tbody').html(html);
    
    // Initialize DataTable
    $('#suppliers_table').DataTable({
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "lengthChange": true,
        "info": true,
        "autoWidth": false,
        "order": [[0, 'asc']],
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>Brt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        "buttons": [
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel mr-2"></i>Excel',
                className: 'btn btn-success',
                title: 'Suppliers',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: {
                        body: function(data, row, column, node) {
                            // Clean status badges - extract text only
                            if(column === 5) {
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
                title: 'Suppliers',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: {
                        body: function(data, row, column, node) {
                            // Clean status badges
                            if(column === 5) {
                                return $(node).text().trim();
                            }
                            return $(node).text();
                        }
                    }
                },
                customize: function(doc) {
                    doc.content[1].table.widths = ['20%', '15%', '20%', '15%', '15%', '15%'];
                    doc.styles.tableHeader.fillColor = '#3B82F6';
                    doc.styles.tableHeader.color = '#FFFFFF';
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print mr-2"></i>Print',
                className: 'btn btn-info',
                title: 'Suppliers',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: {
                        body: function(data, row, column, node) {
                            // Clean status badges
                            if(column === 5) {
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
                }
            }
        ],
        "language": {
            "search": "Search suppliers:",
            "lengthMenu": "Show _MENU_ suppliers",
            "info": "Showing _START_ to _END_ of _TOTAL_ suppliers",
            "infoEmpty": "Showing 0 to 0 of 0 suppliers",
            "infoFiltered": "(filtered from _MAX_ total suppliers)",
            "zeroRecords": "No suppliers found",
            "emptyTable": "No suppliers available"
        },
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
}

function openAddSupplierModal() {
    loadModalContent('createModal', '<?php echo site_url('inventory/supplier_form'); ?>', '<i class="fa fa-plus"></i> Add Supplier');
}

function editSupplier(id) {
    // Clean up any existing modals first
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('#createModal').modal('hide');
    
    loadModalContent('createModal', '<?php echo site_url('inventory/supplier_form/'); ?>' + id, '<i class="fa fa-edit"></i> Edit Supplier');
}

function viewSupplierDetails(id) {
    loadModalContent('detailsModal', '<?php echo site_url('inventory/supplier_details/'); ?>' + id, '<i class="fa fa-info-circle"></i> Supplier Details');
}

function toggleSupplierStatus(id, currentStatus) {
    const action = currentStatus == 1 ? 'deactivate' : 'activate';
    const actionText = currentStatus == 1 ? 'Deactivate' : 'Activate';
    
    showConfirmModal('Confirm ' + actionText, 'Are you sure you want to ' + action + ' this supplier?', function() {
        showAjaxModal_alert(actionText + 'ing...', 'loading');
        $.post('<?php echo site_url('inventory/toggle_supplier_status'); ?>', { supplier_id: id }, function(response) {
            const data = JSON.parse(response);
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success');
                setTimeout(() => loadSuppliers(), 2000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    }, actionText, currentStatus == 1 ? 'warning' : 'success');
}

function deleteSupplier(id) {
    showConfirmModal('Confirm Delete', 'Are you sure you want to delete this supplier?', function() {
        showAjaxModal_alert('Deleting...', 'loading');
        $.get('<?php echo site_url('inventory/delete_supplier/'); ?>' + id, function(response) {
            const data = JSON.parse(response);
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success');
                setTimeout(() => loadSuppliers(), 2000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        });
    }, 'Delete', 'danger');
}

// ============================================
// SUPPLIER ACTION POPUP MENU FUNCTIONALITY
// ============================================

var currentSupplierId = null;
var currentSupplierData = null;
var $supplierActionPopupMenu = null;

$(function() {
    $supplierActionPopupMenu = $('#supplierActionPopupMenu');
    initSupplierPopupMenus();
});

function initSupplierPopupMenus() {
    // Close popup when clicking outside
    $(document).off('click.supplierPopup').on('click.supplierPopup', function(e) {
        if (!$(e.target).closest('.supplier-action-popup-btn, .supplier-action-popup-menu').length) {
            closeSupplierPopupMenus();
        }
    });
    
    // Bind action buttons
    $('#supplierActionViewBtn').off('click').on('click', function() {
        closeSupplierPopupMenus();
        viewSupplierDetails(currentSupplierId);
    });
    
    $('#supplierActionEditBtn').off('click').on('click', function() {
        closeSupplierPopupMenus();
        editSupplier(currentSupplierId);
    });
    
    $('#supplierActionActivateBtn').off('click').on('click', function() {
        closeSupplierPopupMenus();
        toggleSupplierStatus(currentSupplierId, 0);
    });
    
    $('#supplierActionDeactivateBtn').off('click').on('click', function() {
        closeSupplierPopupMenus();
        toggleSupplierStatus(currentSupplierId, 1);
    });
    
    $('#supplierActionDeleteBtn').off('click').on('click', function() {
        closeSupplierPopupMenus();
        deleteSupplier(currentSupplierId);
    });
}

function closeSupplierPopupMenus() {
    if ($supplierActionPopupMenu) $supplierActionPopupMenu.removeClass('show');
    $('.supplier-action-popup-btn').removeClass('active');
}

function toggleSupplierActionMenu(btn, event, supplierId) {
    event.stopPropagation();
    event.preventDefault();
    
    var btnElement = btn instanceof jQuery ? btn[0] : btn;
    var $btn = $(btnElement);
    var $row = $btn.closest('tr');
    var $dataSpan = $row.find('.supplier-action-data');
    
    currentSupplierId = $dataSpan.data('supplier-id');
    currentSupplierData = {
        name: $dataSpan.data('supplier-name'),
        status: $dataSpan.data('status')
    };
    
    // Show/hide activate/deactivate buttons based on status
    if (currentSupplierData.status == 1) {
        $('#supplierActionActivateBtn').hide();
        $('#supplierActionDeactivateBtn').show();
    } else {
        $('#supplierActionActivateBtn').show();
        $('#supplierActionDeactivateBtn').hide();
    }
    
    // If menu is already open, close it
    if ($supplierActionPopupMenu && $supplierActionPopupMenu.hasClass('show')) {
        closeSupplierPopupMenus();
        return;
    }
    
    closeSupplierPopupMenus();
    
    // Position the menu
    var btnRect = btnElement.getBoundingClientRect();
    
    // Use fixed values for menu dimensions
    var menuWidth = 200;
    var menuHeight = 240;
    
    // Calculate position relative to viewport (using fixed positioning)
    var left = btnRect.right + 5;
    var top = btnRect.top;
    
    // Adjust if menu would go off right edge - show on left side instead
    if (left + menuWidth > window.innerWidth) {
        left = btnRect.left - menuWidth - 5;
    }
    
    // Adjust if menu would go off bottom edge - position above or at top of visible area
    if (top + menuHeight > window.innerHeight) {
        // Try positioning above the button
        var topAbove = btnRect.top - menuHeight - 5;
        if (topAbove >= 0) {
            top = topAbove;
        } else {
            // If can't fit above, position at top of viewport with small margin
            top = 10;
        }
    }
    
    // Ensure left position is within viewport
    left = Math.max(10, Math.min(left, window.innerWidth - menuWidth - 10));
    
    // Ensure top position is within viewport
    top = Math.max(10, Math.min(top, window.innerHeight - menuHeight - 10));
    
    $supplierActionPopupMenu.css({
        left: left + 'px',
        top: top + 'px'
    }).addClass('show');
    
    $btn.addClass('active');
}

</script>
