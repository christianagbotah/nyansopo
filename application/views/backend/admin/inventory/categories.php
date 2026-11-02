<!-- Enterprise Categories Management -->
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
            <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight">Product Categories</h1>
            <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Organize inventory items by category</p>
        </div>
        <button onclick="openAddCategoryModal()" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="inline-flex items-center px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Category
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="categories_table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Category Name</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-left font-bold text-gray-700 uppercase tracking-wider">Description</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Products</th>
                        <th style="font-size: 1.25rem !important;" class="px-8 py-5 text-right font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="categories_tbody">
                    <tr><td colspan="4" style="font-size: 1.25rem !important;" class="px-8 py-10 text-center text-gray-500">Loading categories...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
var categoriesTable;

$(document).ready(function() { 
    loadCategories(); 
});

function loadCategories() {
    $.get('<?php echo site_url('inventory/get_categories'); ?>', function(response) {
        renderCategoriesTable(JSON.parse(response));
    });
}

function renderCategoriesTable(categories) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#categories_table')) {
        $('#categories_table').DataTable().destroy();
    }
    
    let html = '';
    // Don't add "no data" row - let DataTable handle it
    categories.forEach(category => {
        html += `
            <tr class="hover:bg-blue-50 transition-colors">
                <td class="px-8 py-5">
                    <div style="font-size: 1.25rem !important;" class="font-semibold text-gray-900 leading-relaxed">${category.name}</div>
                </td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-gray-600">${category.description || '-'}</td>
                <td style="font-size: 1.25rem !important;" class="px-8 py-5 text-right text-gray-900 font-medium">${category.product_count || 0}</td>
                <td class="px-8 py-5 text-right">
                    <button onclick="editCategory(${category.id})" style="font-size: 1.25rem !important;" class="font-semibold text-blue-600 hover:text-blue-900 mr-4 transition-colors">Edit</button>
                    <button onclick="deleteCategory(${category.id})" style="font-size: 1.25rem !important;" class="font-semibold text-red-600 hover:text-red-900 transition-colors">Delete</button>
                </td>
            </tr>
        `;
    });
    
    $('#categories_tbody').html(html);
    
    // Initialize DataTable
    categoriesTable = $('#categories_table').DataTable({
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "lengthChange": true,
        "info": true,
        "autoWidth": false,
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
                title: 'Product Categories',
                exportOptions: {
                    columns: [0, 1, 2]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf mr-2"></i>PDF',
                className: 'btn btn-danger',
                title: 'Product Categories',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2]
                },
                customize: function(doc) {
                    doc.content[1].table.widths = ['30%', '50%', '20%'];
                    doc.styles.tableHeader.fillColor = '#3B82F6';
                    doc.styles.tableHeader.color = '#FFFFFF';
                    // Right align products count column
                    doc.content[1].table.body.forEach(function(row, index) {
                        if(index > 0) { // Skip header row
                            row[2].alignment = 'right'; // Products count column
                        }
                    });
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print mr-2"></i>Print',
                className: 'btn btn-info',
                title: 'Product Categories',
                exportOptions: {
                    columns: [0, 1, 2]
                },
                customize: function(win) {
                    $(win.document.body).css('font-size', '10pt').css('color', '#000000');
                    $(win.document.body).find('table').addClass('compact').css('font-size', 'inherit').css('color', '#000000');
                    $(win.document.body).find('h1').css('color', '#000000');
                    // Right align products count column
                    $(win.document.body).find('table tbody td:nth-child(3)').css('text-align', 'right');
                    $(win.document.body).find('table thead th:nth-child(3)').css('text-align', 'right');
                }
            }
        ],
        "language": {
            "search": "Search categories:",
            "lengthMenu": "Show _MENU_ categories",
            "info": "Showing _START_ to _END_ of _TOTAL_ categories",
            "infoEmpty": "Showing 0 to 0 of 0 categories",
            "infoFiltered": "(filtered from _MAX_ total categories)",
            "zeroRecords": "No categories found",
            "emptyTable": "No categories available",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": 3 }
        ]
    });
}

function openAddCategoryModal() {
    loadModalContent('category_modal', '<?php echo site_url('inventory/category_form'); ?>', '<i class="fa fa-plus"></i> Add Category');
}

function editCategory(id) {
    loadModalContent('category_modal', '<?php echo site_url('inventory/category_form/'); ?>' + id, '<i class="fa fa-edit"></i> Edit Category');
}

function deleteCategory(id) {
    showConfirmModal('Confirm Delete', 'Are you sure you want to delete this category?', function() {
        showAjaxModal_alert('Deleting...', 'loading');
        $.get('<?php echo site_url('inventory/delete_category/'); ?>' + id, function(response) {
            const data = JSON.parse(response);
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success', false);
                setTimeout(() => loadCategories(), 2000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        });
    }, 'Delete', 'danger');
}
</script>
