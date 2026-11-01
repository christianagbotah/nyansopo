<!-- Modern Discount Profiles Page -->
<style>
* { box-sizing: border-box; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

/* Container */
.enterprise-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 24px;
}

/* Page Header */
.page-hero {
    background: white;
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.page-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: 50%;
    transform: translate(50%, -50%);
}

.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.hero-left h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-left p {
    margin: 0;
    color: #6b7280;
    font-size: 16px;
}

.hero-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-modern {
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-secondary:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-card.primary::before { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
.stat-card.success::before { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }
.stat-card.info::before { background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); }
.stat-card.warning::before { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.stat-card.primary .stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-card.success .stat-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-card.info .stat-icon { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.stat-card.warning .stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-detail {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
    font-size: 13px;
    color: #6b7280;
}

/* Main Content Card */
.content-card {
    background: white;
    border-radius: 20px;
    padding: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 24px 28px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1a202c;
    display: flex;
    align-items: center;
    gap: 10px;
}

.view-switcher {
    background: #f3f4f6;
    border-radius: 10px;
    padding: 4px;
    display: flex;
    gap: 4px;
}

.view-btn {
    background: transparent;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    color: #6b7280;
    font-size: 16px;
}

.view-btn.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Filters */
.filters-bar {
    padding: 20px 28px;
    background: #f9fafb;
    border-bottom: 1px solid #f3f4f6;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.filter-group {
    position: relative;
}

.filter-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 14px;
    pointer-events: none;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 12px 16px 12px 40px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s;
    background: white;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Table View */
.table-container {
    padding: 28px;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

.modern-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}

.modern-table tbody tr {
    transition: all 0.2s;
    border-bottom: 1px solid #f3f4f6;
}

.modern-table tbody tr:hover {
    background: #f9fafb;
}

.modern-table td {
    padding: 16px;
    font-size: 14px;
    color: #4b5563;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-purple {
    background: #ede9fe;
    color: #5b21b6;
}

/* Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #e5e7eb;
    transition: all 0.3s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: all 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

input:checked + .slider {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

input:checked + .slider:before {
    transform: translateX(24px);
}

/* Action Buttons */
.action-btn {
    padding: 8px 12px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.btn-edit {
    background: #667eea;
    color: white;
}

.btn-edit:hover {
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-assign {
    background: #10b981;
    color: white;
}

.btn-assign:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Grid View */
.profiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    padding: 28px;
}

.profile-card {
    background: white;
    border: 2px solid #f3f4f6;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.3s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.profile-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f3f4f6;
}

.card-name {
    font-size: 18px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px 0;
}

.card-body {
    flex: 1;
    margin-bottom: 16px;
}

.info-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-size: 14px;
    color: #4b5563;
}

.info-icon {
    width: 32px;
    color: #9ca3af;
    font-size: 15px;
}

.card-footer {
    display: flex;
    gap: 8px;
    padding-top: 16px;
    border-top: 2px solid #f3f4f6;
}

.card-footer .action-btn {
    flex: 1;
    justify-content: center;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-title {
    font-size: 20px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.empty-text {
    font-size: 14px;
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
    .enterprise-wrapper {
        padding: 16px;
    }
    
    .page-hero {
        padding: 24px;
    }
    
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .hero-left h1 {
        font-size: 24px;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .profiles-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-table {
        font-size: 13px;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 12px 8px;
    }
}

@media (max-width: 576px) {
    .stat-value {
        font-size: 28px;
    }
    
    .btn-modern {
        width: 100%;
        justify-content: center;
    }
}

/* Loading State */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="enterprise-wrapper">
    <!-- Page Hero -->
    <div class="page-hero">
        <div class="hero-content">
            <div class="hero-left">
                <h1><i class="fa fa-percent"></i> <?php echo get_phrase('discount_profiles'); ?></h1>
                <p><?php echo get_phrase('manage_discount_profiles_and_assignments'); ?></p>
            </div>
            <div class="hero-actions">
                <button class="btn-modern btn-secondary" onclick="location.href='<?php echo site_url('admin/manage_discount_assignments'); ?>'">
                    <i class="fa fa-users"></i>
                    <span><?php echo get_phrase('manage_assignments'); ?></span>
                </button>
                <button class="btn-modern btn-primary" onclick="showCreateModal()">
                    <i class="fa fa-plus"></i>
                    <span><?php echo get_phrase('create_profile'); ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stat-card primary">
            <div class="stat-header">
                <div>
                    <div class="stat-value" id="totalProfiles">0</div>
                    <div class="stat-label"><?php echo get_phrase('total_profiles'); ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fa fa-percent"></i>
                </div>
            </div>
            <div class="stat-detail" id="profileBreakdown">
                <span style="color: #10b981;">● <strong id="activeCount">0</strong> Active</span> • 
                <span style="color: #9ca3af;">● <strong id="inactiveCount">0</strong> Inactive</span>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <div class="stat-value" id="activeProfiles">0</div>
                    <div class="stat-label"><?php echo get_phrase('active_profiles'); ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-header">
                <div>
                    <div class="stat-value" id="invoiceProfiles">0</div>
                    <div class="stat-label"><?php echo get_phrase('invoice_discounts'); ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fa fa-file-invoice"></i>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <div class="stat-value" id="dailyProfiles">0</div>
                    <div class="stat-label"><?php echo get_phrase('daily_fees_discounts'); ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fa fa-calendar-day"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card">
        <!-- Card Header -->
        <div class="card-header">
            <div class="card-title">
                <i class="fa fa-list"></i>
                <?php echo get_phrase('all_profiles'); ?>
            </div>
            <div class="view-switcher">
                <button class="view-btn active" id="tableViewBtn" onclick="switchView('table')">
                    <i class="fa fa-table"></i>
                </button>
                <button class="view-btn" id="gridViewBtn" onclick="switchView('grid')">
                    <i class="fa fa-th"></i>
                </button>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="filters-bar">
            <div class="filters-grid">
                <div class="filter-group">
                    <i class="fa fa-search filter-icon"></i>
                    <input type="text" class="filter-input" id="searchFilter" placeholder="Search profiles..." onkeyup="filterProfiles()">
                </div>
                <div class="filter-group">
                    <i class="fa fa-folder filter-icon"></i>
                    <select class="filter-select" id="categoryFilter" onchange="filterProfiles()">
                        <option value="">All Categories</option>
                        <option value="invoice">📄 Invoice Discounts</option>
                        <option value="daily_fees">📅 Daily Fees Discounts</option>
                    </select>
                </div>
                <div class="filter-group">
                    <i class="fa fa-toggle-on filter-icon"></i>
                    <select class="filter-select" id="statusFilter" onchange="filterProfiles()">
                        <option value="">All Status</option>
                        <option value="1">✅ Active Only</option>
                        <option value="0">❌ Inactive Only</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div id="tableView" class="table-container">
            <table class="modern-table" id="discountProfilesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><i class="fa fa-tag"></i> Profile Name</th>
                        <th><i class="fa fa-list"></i> Type</th>
                        <th><i class="fa fa-folder"></i> Category</th>
                        <th><i class="fa fa-calculator"></i> Method</th>
                        <th><i class="fa fa-dollar"></i> Value</th>
                        <th><i class="fa fa-toggle-on"></i> Status</th>
                        <th><i class="fa fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa fa-spinner fa-spin"></i></div>
                                <div class="empty-title"><?php echo get_phrase('loading'); ?>...</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Grid View -->
        <div id="gridView" style="display: none;">
            <div class="profiles-grid" id="profilesGrid">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa fa-spinner fa-spin"></i></div>
                    <div class="empty-title"><?php echo get_phrase('loading'); ?>...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let allProfiles = [];
let filteredProfiles = [];
let dataTable;
const billItemsMap = <?php echo json_encode($bill_items_map ?? []); ?>;

$(document).ready(function() {
    loadStats();
    loadProfiles();
    
    // Load saved view preference
    const savedView = localStorage.getItem('profilesView') || 'table';
    switchView(savedView);
});

function loadStats() {
    $.ajax({
        url: '<?php echo site_url('admin/get_discount_stats'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                $('#totalProfiles').text(response.data.total || 0);
                $('#activeProfiles').text(response.data.active || 0);
                $('#inactiveProfiles').text(response.data.inactive || 0);
                $('#invoiceProfiles').text(response.data.invoice || 0);
                $('#dailyProfiles').text(response.data.daily_fees || 0);
                $('#activeCount').text(response.data.active || 0);
                $('#inactiveCount').text(response.data.inactive || 0);
            }
        }
    });
}

function loadProfiles() {
    $.ajax({
        url: '<?php echo site_url('admin/discount_profiles'); ?>',
        type: 'GET',
        data: { ajax: 1 },
        dataType: 'json',
        success: function(response) {
            if(response.profiles) {
                allProfiles = response.profiles;
                filteredProfiles = allProfiles;
                renderTable();
                if($('#gridView').is(':visible')) {
                    renderGrid();
                }
            }
        },
        error: function() {
            showEmptyState('Failed to load profiles');
        }
    });
}

function renderTable() {
    const tbody = $('#tableBody');
    
    if(filteredProfiles.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa fa-inbox"></i></div>
                        <div class="empty-title">No profiles found</div>
                        <div class="empty-text">Try adjusting your filters or create a new profile</div>
                    </div>
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    filteredProfiles.forEach((profile, index) => {
        const statusBadge = profile.is_active == 1 
            ? '<span class="badge badge-success"><i class="fa fa-check"></i> Active</span>'
            : '<span class="badge badge-danger"><i class="fa fa-times"></i> Inactive</span>';
        
        const categoryBadge = profile.discount_category === 'invoice'
            ? '<span class="badge badge-info">📄 Invoice</span>'
            : '<span class="badge badge-warning">📅 Daily Fees</span>';
        
        const methodBadge = profile.discount_method === 'percentage'
            ? '<span class="badge badge-purple"><i class="fa fa-percent"></i> Percentage</span>'
            : '<span class="badge badge-info"><i class="fa fa-dollar"></i> Fixed</span>';
        
        const value = profile.discount_method === 'percentage'
            ? profile.discount_value + '%'
            : 'GHS ' + parseFloat(profile.discount_value).toFixed(2);
        
        let typeDisplay = getTypeDisplay(profile);
        
        const toggleChecked = profile.is_active == 1 ? 'checked' : '';
        
        html += `
            <tr>
                <td><strong>${index + 1}</strong></td>
                <td>
                    <div style="font-weight: 600;">${profile.profile_name}</div>
                    <div style="font-size: 12px; color: #9ca3af;">${typeDisplay}</div>
                </td>
                <td>${typeDisplay}</td>
                <td>${categoryBadge}</td>
                <td>${methodBadge}</td>
                <td><strong style="font-size: 16px;">${value}</strong></td>
                <td>
                    <label class="switch">
                        <input type="checkbox" ${toggleChecked} onchange="toggleStatus(${profile.profile_id})">
                        <span class="slider"></span>
                    </label>
                </td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <button class="action-btn btn-edit" onclick="editProfile(${profile.profile_id})" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="action-btn btn-assign" onclick="assignStudents(${profile.profile_id})" title="Assign">
                            <i class="fa fa-user-plus"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteProfile(${profile.profile_id})" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

function renderGrid() {
    const grid = $('#profilesGrid');
    
    if(filteredProfiles.length === 0) {
        grid.html(`
            <div class="empty-state">
                <div class="empty-icon"><i class="fa fa-inbox"></i></div>
                <div class="empty-title">No profiles found</div>
                <div class="empty-text">Try adjusting your filters or create a new profile</div>
            </div>
        `);
        return;
    }
    
    let html = '';
    filteredProfiles.forEach(profile => {
        const statusBadge = profile.is_active == 1 
            ? '<span class="badge badge-success"><i class="fa fa-check"></i> Active</span>'
            : '<span class="badge badge-danger"><i class="fa fa-times"></i> Inactive</span>';
        
        const categoryBadge = profile.discount_category === 'invoice'
            ? '<span class="badge badge-info">📄 Invoice</span>'
            : '<span class="badge badge-warning">📅 Daily Fees</span>';
        
        const value = profile.discount_method === 'percentage'
            ? profile.discount_value + '%'
            : 'GHS ' + parseFloat(profile.discount_value).toFixed(2);
        
        let typeDisplay = getTypeDisplay(profile);
        
        html += `
            <div class="profile-card">
                <div class="card-top">
                    <div>
                        <h3 class="card-name">${profile.profile_name}</h3>
                        ${categoryBadge}
                    </div>
                    ${statusBadge}
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <i class="fa fa-list info-icon"></i>
                        <span><strong>Type:</strong> ${typeDisplay}</span>
                    </div>
                    <div class="info-row">
                        <i class="fa fa-calculator info-icon"></i>
                        <span><strong>Method:</strong> ${profile.discount_method === 'percentage' ? 'Percentage' : 'Fixed Amount'}</span>
                    </div>
                    <div class="info-row">
                        <i class="fa fa-dollar info-icon"></i>
                        <span><strong>Value:</strong> <strong style="font-size: 16px; color: #667eea;">${value}</strong></span>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="action-btn btn-edit" onclick="editProfile(${profile.profile_id})">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    <button class="action-btn btn-assign" onclick="assignStudents(${profile.profile_id})">
                        <i class="fa fa-user-plus"></i> Assign
                    </button>
                    <button class="action-btn btn-delete" onclick="deleteProfile(${profile.profile_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    grid.html(html);
}

function getTypeDisplay(profile) {
    if(profile.discount_category === 'invoice') {
        if(profile.bill_item_ids === '*') {
            return 'All Invoice Items';
        } else if(profile.bill_item_ids) {
            return profile.bill_item_ids.split(',').map(id => {
                const title = billItemsMap[id] || 'Unknown';
                return title.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
            }).join(', ');
        }
    } else if(profile.discount_category === 'daily_fees' && profile.discount_type) {
        return profile.discount_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    return 'N/A';
}

function filterProfiles() {
    const search = $('#searchFilter').val().toLowerCase();
    const category = $('#categoryFilter').val();
    const status = $('#statusFilter').val();
    
    filteredProfiles = allProfiles.filter(profile => {
        const matchSearch = !search || 
            profile.profile_name.toLowerCase().includes(search) ||
            getTypeDisplay(profile).toLowerCase().includes(search);
        const matchCategory = !category || profile.discount_category === category;
        const matchStatus = status === '' || profile.is_active == status;
        
        return matchSearch && matchCategory && matchStatus;
    });
    
    renderTable();
    if($('#gridView').is(':visible')) {
        renderGrid();
    }
}

function switchView(view) {
    localStorage.setItem('profilesView', view);
    
    if(view === 'table') {
        $('#tableView').show();
        $('#gridView').hide();
        $('#tableViewBtn').addClass('active');
        $('#gridViewBtn').removeClass('active');
    } else {
        $('#tableView').hide();
        $('#gridView').show();
        $('#tableViewBtn').removeClass('active');
        $('#gridViewBtn').addClass('active');
        renderGrid();
    }
}

function showCreateModal() {
    loadModalContent('createModal', '<?php echo site_url('admin/create_discount_profile_modal'); ?>', '<i class="fa fa-plus"></i> <?php echo get_phrase('create_discount_profile'); ?>');
}

function editProfile(id) {
    loadModalContent('createModal', '<?php echo site_url('admin/edit_discount_profile_modal/'); ?>' + id, '<i class="fa fa-edit"></i> <?php echo get_phrase('edit_discount_profile'); ?>');
}

function assignStudents(id) {
    loadModalContent('modal_ajax', '<?php echo site_url('admin/assign_students_to_profile_modal/'); ?>' + id, '<i class="fa fa-user-plus"></i> <?php echo get_phrase('assign_students'); ?>');
}

function toggleStatus(id) {
    showAjaxModal_alert('Processing...', 'loading');
    $.ajax({
        url: '<?php echo site_url('admin/toggle_discount_profile_status'); ?>',
        type: 'POST',
        data: { profile_id: id },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success', false);
                setTimeout(() => {
                    loadStats();
                    loadProfiles();
                }, 500);
            } else {
                showAjaxModal_alert(response.message, 'error');
                loadProfiles(); // Reload to reset toggle
            }
        },
        error: function() {
            showAjaxModal_alert('An error occurred', 'error');
            loadProfiles();
        }
    });
}

function deleteProfile(id) {
    showConfirmModal(
        'Confirm Delete',
        'Are you sure you want to delete this discount profile? This action cannot be undone.',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/delete_discount_profile'); ?>',
                type: 'POST',
                data: { profile_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success', false);
                        setTimeout(() => {
                            loadStats();
                            loadProfiles();
                        }, 500);
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('An error occurred', 'error');
                }
            });
        },
        'Delete',
        'danger'
    );
}

function reloadProfilesData() {
    loadStats();
    loadProfiles();
}
</script>
