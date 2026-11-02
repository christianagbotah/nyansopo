<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fef2f2; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.container { max-width:1400px; margin:0 auto; padding:24px; }
.header { background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius:12px; padding:32px; margin-bottom:32px; box-shadow:0 4px 20px rgba(220,38,38,0.2); color:white; display:flex; justify-content:space-between; align-items:center; }
.header-left h1 { font-size:32px; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:12px; }
.header-left p { font-size:14px; opacity:0.95; }
.btn { padding:12px 24px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
.btn-white { background:white; color:#dc2626; }
.btn-white:hover { background:#fee2e2; transform:translateY(-2px); box-shadow:0 4px 12px rgba(255,255,255,0.3); }
.stats-bar { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:24px; }
.stat-box { background:white; border-radius:10px; padding:16px; box-shadow:0 2px 10px rgba(0,0,0,0.06); display:flex; align-items:center; gap:12px; }
.stat-icon { width:42px; height:42px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:20px; }
.stat-icon.red { background:#fee2e2; color:#dc2626; }
.stat-icon.blue { background:#dbeafe; color:#3b82f6; }
.stat-icon.green { background:#d1fae5; color:#10b981; }
.stat-icon.purple { background:#ede9fe; color:#8b5cf6; }
.stat-content h3 { font-size:24px; font-weight:700; color:#111827; margin-bottom:2px; }
.stat-content p { font-size:12px; color:#6b7280; font-weight:600; }
.main-card { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:20px; border-bottom:2px solid #f3f4f6; }
.card-title { font-size:20px; font-weight:700; color:#111827; display:flex; align-items:center; gap:12px; }
.search-bar { display:flex; gap:12px; align-items:center; }
.search-input { padding:10px 16px; border:1px solid #e5e7eb; border-radius:8px; font-size:14px; width:250px; }
.search-input:focus { outline:none; border-color:#dc2626; }
.btn-primary { background:#dc2626; color:white; }
.btn-primary:hover { background:#b91c1c; transform:translateY(-2px); box-shadow:0 4px 12px rgba(220,38,38,0.3); }
.view-toggle { display:flex; gap:4px; background:#f3f4f6; padding:4px; border-radius:8px; }
.view-btn { padding:8px 12px; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s; background:transparent; color:#6b7280; }
.view-btn.active { background:white; color:#dc2626; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
.view-btn:hover { color:#dc2626; }
/* Grid View */
.categories-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:16px; }
@media (max-width: 1400px) { .categories-grid { grid-template-columns:repeat(4, 1fr); } }
@media (max-width: 1100px) { .categories-grid { grid-template-columns:repeat(3, 1fr); } }
@media (max-width: 768px) { .categories-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 480px) { .categories-grid { grid-template-columns:1fr; } }
.category-card { background:#fafafa; border:2px solid #e5e7eb; border-radius:10px; padding:16px; transition:all 0.3s; position:relative; overflow:hidden; }
.category-card:hover { border-color:#dc2626; background:white; transform:translateY(-2px); box-shadow:0 6px 16px rgba(220,38,38,0.12); }
.category-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#dc2626; transform:scaleY(0); transition:transform 0.3s; }
.category-card:hover::before { transform:scaleY(1); }
.category-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
.category-icon { width:44px; height:44px; border-radius:10px; background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color:white; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 3px 10px rgba(220,38,38,0.3); }
.category-actions { display:flex; gap:6px; }
.icon-btn { width:32px; height:32px; border:none; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px; transition:all 0.2s; }
.icon-btn.edit { background:#dbeafe; color:#3b82f6; }
.icon-btn.edit:hover { background:#3b82f6; color:white; }
.icon-btn.delete { background:#fee2e2; color:#dc2626; }
.icon-btn.delete:hover { background:#dc2626; color:white; }
.category-name { font-size:16px; font-weight:700; color:#111827; margin-bottom:6px; }
.category-desc { font-size:12px; color:#6b7280; margin-bottom:12px; line-height:1.4; min-height:34px; }
.category-stats { display:flex; flex-direction:column; gap:8px; padding-top:12px; border-top:1px solid #e5e7eb; }
.stat-item { display:flex; align-items:center; gap:6px; font-size:12px; color:#6b7280; }
.stat-item i { color:#dc2626; font-size:11px; }
.stat-item strong { color:#111827; font-weight:600; }
/* Table View */
.categories-table { width:100%; border-collapse:collapse; display:none; }
.categories-table thead th { background:#fef2f2; color:#991b1b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; padding:14px 16px; text-align:left; border-bottom:2px solid #fecaca; }
.categories-table tbody td { padding:14px 16px; border-bottom:1px solid #fee2e2; font-size:14px; color:#111827; }
.categories-table tbody tr:hover { background:#fef2f2; }
.categories-table .cat-icon { width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color:white; display:flex; align-items:center; justify-content:center; font-size:18px; }
.categories-table .action-btns { display:flex; gap:8px; }
.empty-state { text-align:center; padding:60px 20px; }
.empty-state i { font-size:64px; color:#e5e7eb; margin-bottom:16px; }
.empty-state h3 { font-size:20px; color:#6b7280; margin-bottom:8px; }
.empty-state p { font-size:14px; color:#9ca3af; }
/* Pagination */
.pagination-container { display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:20px; border-top:1px solid #e5e7eb; }
.pagination-info { font-size:14px; color:#6b7280; }
.pagination-controls { display:flex; gap:4px; align-items:center; }
.page-btn { min-width:36px; height:36px; border:1px solid #e5e7eb; background:white; border-radius:6px; font-size:14px; font-weight:500; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; }
.page-btn:hover:not(:disabled) { border-color:#dc2626; color:#dc2626; }
.page-btn.active { background:#dc2626; color:white; border-color:#dc2626; }
.page-btn:disabled { opacity:0.5; cursor:not-allowed; }
.page-btn.nav-btn { padding:0 12px; }
@media (max-width: 768px) { .stats-bar { grid-template-columns:repeat(2, 1fr); } .categories-grid { grid-template-columns:1fr; } .search-bar { flex-direction:column; width:100%; } .search-input { width:100%; } .pagination-container { flex-direction:column; gap:12px; } }
</style>

<div class="container">
    <div class="header">
        <div class="header-left">
            <h1><i class="fa fa-folder-open"></i> Expense Categories</h1>
            <p>Organize and manage your expenditure categories</p>
        </div>
        <div style="display:flex; gap:12px;">
            <button class="btn btn-white" onclick="showBulkModal()">
                <i class="fa fa-layer-group"></i> Bulk Create
            </button>
            <button class="btn btn-white" onclick="showCreateModal()">
                <i class="fa fa-plus"></i> New Category
            </button>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-icon red"><i class="fa fa-folder"></i></div>
            <div class="stat-content">
                <h3 id="totalCategories">0</h3>
                <p>Total Categories</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon blue"><i class="fa fa-check-circle"></i></div>
            <div class="stat-content">
                <h3 id="activeCategories">0</h3>
                <p>Active Categories</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon green"><i class="fa fa-receipt"></i></div>
            <div class="stat-content">
                <h3 id="totalExpenses">0</h3>
                <p>Total Expenses</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon purple"><i class="fa fa-chart-line"></i></div>
            <div class="stat-content">
                <h3 id="mostUsed" style="font-size:18px;">-</h3>
                <p>Most Used</p>
            </div>
        </div>
    </div>

    <div class="main-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa fa-list"></i> All Categories
            </div>
            <div class="search-bar">
                <div class="view-toggle">
                    <button class="view-btn active" id="gridViewBtn" onclick="toggleView('grid')">
                        <i class="fa fa-th"></i> Grid
                    </button>
                    <button class="view-btn" id="tableViewBtn" onclick="toggleView('table')">
                        <i class="fa fa-table"></i> Table
                    </button>
                </div>
                <input type="text" class="search-input" id="searchInput" placeholder="Search categories..." onkeyup="filterCategories()">
                <button class="btn btn-primary" onclick="loadCategories()">
                    <i class="fa fa-sync"></i> Refresh
                </button>
            </div>
        </div>
        <div class="categories-grid" id="categoriesGrid">
            <div class="empty-state">
                <i class="fa fa-spinner fa-spin"></i>
                <h3>Loading categories...</h3>
            </div>
        </div>
        <table class="categories-table" id="categoriesTable">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th style="width:60px;">Icon</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th style="width:120px;">Expenses</th>
                    <th style="width:150px;">Total Amount</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody id="categoriesTableBody">
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="pagination-container" id="paginationContainer" style="display:none;">
            <div class="pagination-info" id="paginationInfo">Showing 1-10 of 0 categories</div>
            <div class="pagination-controls" id="paginationControls"></div>
        </div>
    </div>
</div>

<script>
var currentView = 'grid';
var categoriesData = [];
var filteredData = [];
var currentPage = 1;
var itemsPerPage = 10;

$(document).ready(function() {
    loadCategories();
});

function loadCategories() {
    $.ajax({
        url: '<?php echo site_url("admin/get_expense_categories"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                categoriesData = response.data;
                filteredData = response.data;
                currentPage = 1;
                renderCategories(response.data);
                updateStats(response.data);
            } else {
                $('#categoriesGrid').html('<div class="empty-state"><i class="fa fa-exclamation-circle"></i><h3>Failed to load</h3><p>' + response.message + '</p></div>');
            }
        },
        error: function() {
            $('#categoriesGrid').html('<div class="empty-state"><i class="fa fa-exclamation-triangle"></i><h3>Error loading categories</h3><p>Please try again</p></div>');
        }
    });
}

function updateStats(categories) {
    // Calculate stats from categories data
    let total = categories.length;
    let active = 0;
    let totalExpenses = 0;
    let mostUsed = { name: '-', count: 0 };
    
    categories.forEach(function(cat) {
        if(cat.expense_count > 0) {
            active++;
            totalExpenses += parseInt(cat.expense_count);
            
            if(cat.expense_count > mostUsed.count) {
                mostUsed = { name: cat.name, count: cat.expense_count };
            }
        }
    });
    
    $('#totalCategories').text(total);
    $('#activeCategories').text(active);
    $('#totalExpenses').text(totalExpenses);
    $('#mostUsed').text(mostUsed.name);
}

function toggleView(view) {
    currentView = view;
    
    // Update button states
    if(view === 'grid') {
        $('#gridViewBtn').addClass('active');
        $('#tableViewBtn').removeClass('active');
        $('#categoriesGrid').show();
        $('#categoriesTable').hide();
    } else {
        $('#tableViewBtn').addClass('active');
        $('#gridViewBtn').removeClass('active');
        $('#categoriesTable').show();
        $('#categoriesGrid').hide();
    }
    
    // Re-render with current data
    renderCategories(filteredData);
}

function renderCategories(categories) {
    filteredData = categories;
    
    if(categories.length === 0) {
        $('#categoriesGrid').html('<div class="empty-state"><i class="fa fa-folder-open"></i><h3>No categories yet</h3><p>Create your first expense category to get started</p></div>');
        $('#categoriesTableBody').html('<tr><td colspan="7" class="empty-state"><i class="fa fa-folder-open"></i><h3>No categories yet</h3><p>Create your first expense category to get started</p></td></tr>');
        $('#paginationContainer').hide();
        return;
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(categories.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, categories.length);
    const pageData = categories.slice(startIndex, endIndex);
    
    if(currentView === 'grid') {
        renderGridView(pageData, startIndex);
    } else {
        renderTableView(pageData, startIndex);
    }
    
    // Render pagination
    renderPagination(categories.length, totalPages, startIndex + 1, endIndex);
}

function renderPagination(totalItems, totalPages, startItem, endItem) {
    if(totalPages <= 1) {
        $('#paginationContainer').hide();
        return;
    }
    
    $('#paginationContainer').show();
    $('#paginationInfo').text('Showing ' + startItem + '-' + endItem + ' of ' + totalItems + ' categories');
    
    let html = '';
    
    // Previous button
    html += '<button class="page-btn nav-btn" onclick="goToPage(' + (currentPage - 1) + ')" ' + (currentPage === 1 ? 'disabled' : '') + '>';
    html += '<i class="fa fa-chevron-left"></i>';
    html += '</button>';
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if(endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if(startPage > 1) {
        html += '<button class="page-btn" onclick="goToPage(1)">1</button>';
        if(startPage > 2) {
            html += '<span style="padding:0 8px;color:#9ca3af;">...</span>';
        }
    }
    
    for(let i = startPage; i <= endPage; i++) {
        html += '<button class="page-btn ' + (i === currentPage ? 'active' : '') + '" onclick="goToPage(' + i + ')">' + i + '</button>';
    }
    
    if(endPage < totalPages) {
        if(endPage < totalPages - 1) {
            html += '<span style="padding:0 8px;color:#9ca3af;">...</span>';
        }
        html += '<button class="page-btn" onclick="goToPage(' + totalPages + ')">' + totalPages + '</button>';
    }
    
    // Next button
    html += '<button class="page-btn nav-btn" onclick="goToPage(' + (currentPage + 1) + ')" ' + (currentPage === totalPages ? 'disabled' : '') + '>';
    html += '<i class="fa fa-chevron-right"></i>';
    html += '</button>';
    
    $('#paginationControls').html(html);
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if(page < 1 || page > totalPages) return;
    
    currentPage = page;
    renderCategories(filteredData);
    
    // Scroll to top of list
    $('html, body').animate({
        scrollTop: $('.main-card').offset().top - 20
    }, 300);
}

function renderGridView(categories, startIndex) {
    let html = '';
    categories.forEach(function(cat, index) {
        html += `
            <div class="category-card" data-name="${cat.name.toLowerCase()}">
                <div class="category-header">
                    <div class="category-icon"><i class="fa fa-${cat.icon || 'folder'}"></i></div>
                    <div class="category-actions">
                        <button class="icon-btn edit" onclick="editCategory(${cat.expense_category_id})" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="icon-btn delete" onclick="deleteCategory(${cat.expense_category_id})" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="category-name">${cat.name}</div>
                <div class="category-desc">${cat.description || 'No description provided'}</div>
                <div class="category-stats">
                    <div class="stat-item">
                        <i class="fa fa-receipt"></i>
                        <span><strong>${cat.expense_count || 0}</strong> expenses</span>
                    </div>
                    <div class="stat-item">
                        <i class="fa fa-money-bill-wave"></i>
                        <span><strong><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?> ${parseFloat(cat.total_amount || 0).toFixed(2)}</strong></span>
                    </div>
                </div>
            </div>
        `;
    });
    $('#categoriesGrid').html(html);
}

function renderTableView(categories, startIndex) {
    let html = '';
    categories.forEach(function(cat, index) {
        const rowNum = startIndex + index + 1;
        html += `
            <tr data-name="${cat.name.toLowerCase()}">
                <td>${rowNum}</td>
                <td><div class="cat-icon"><i class="fa fa-${cat.icon || 'folder'}"></i></div></td>
                <td><strong>${cat.name}</strong></td>
                <td>${cat.description || 'No description provided'}</td>
                <td><span class="badge" style="background:#dbeafe;color:#3b82f6;padding:4px 10px;border-radius:12px;font-weight:600;">${cat.expense_count || 0}</span></td>
                <td><strong><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?> ${parseFloat(cat.total_amount || 0).toFixed(2)}</strong></td>
                <td>
                    <div class="action-btns">
                        <button class="icon-btn edit" onclick="editCategory(${cat.expense_category_id})" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="icon-btn delete" onclick="deleteCategory(${cat.expense_category_id})" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#categoriesTableBody').html(html);
}

function filterCategories() {
    const search = $('#searchInput').val().toLowerCase();
    
    // Filter the data
    filteredData = categoriesData.filter(function(cat) {
        return cat.name.toLowerCase().includes(search);
    });
    
    // Reset to first page
    currentPage = 1;
    
    // Re-render with filtered data
    renderCategories(filteredData);
}

function showCreateModal() {
    showAjaxModal('<?php echo site_url("admin/expense_category_form"); ?>', 'large');
}

function showBulkModal() {
    showAjaxModal('<?php echo site_url("admin/expense_category_bulk"); ?>', 'xlarge');
}

function editCategory(id) {
    showAjaxModal('<?php echo site_url("admin/expense_category_form/"); ?>' + id, 'large');
}

function deleteCategory(id) {
    showConfirmModal(
        'Delete Category',
        'Are you sure you want to delete this category? This action cannot be undone.',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/delete_expense_category/"); ?>' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                },
                error: function(err) {
                    showAjaxModal_alert('An error occurred: ' + err.responseText, 'error');
                }
            });
        },
        'Delete',
        'danger'
    );
}
</script>
