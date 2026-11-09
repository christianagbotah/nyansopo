<div class="modern-container">
    <div class="row">
        <div class="col-md-12">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fa fa-percent"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalProfiles">0</div>
                        <div class="stat-label"><?php echo get_phrase('total_profiles'); ?></div>
                        <div class="stat-breakdown" id="profileBreakdown" style="font-size: 11px; color: #718096; margin-top: 4px;">
                            <div style="color: #11998e; margin-bottom: 2px;">● <span id="activeCount">0</span> Active</div>
                            <div style="color: #95a5a6;">● <span id="inactiveCount">0</span> Inactive</div>
                        </div>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="activeProfiles">0</div>
                        <div class="stat-label"><?php echo get_phrase('active_profiles'); ?></div>
                    </div>
                </div>
                <?php if(!isset($restricted_mode) || !$restricted_mode): ?>
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fa fa-file-text"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="invoiceProfiles">0</div>
                        <div class="stat-label"><?php echo get_phrase('invoice_discounts'); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="dailyProfiles">0</div>
                        <div class="stat-label"><?php echo get_phrase('daily_fees_discounts'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Main Panel -->
            <div class="modern-panel">
                <div class="panel-header">
                    <div class="header-left">
                        <h2 class="panel-title">
                            <i class="fa fa-percent"></i>
                            <?php echo get_phrase('discount_profiles'); ?>
                        </h2>
                    </div>
                    <div class="header-right">
                        <div class="view-toggle">
                            <button class="toggle-btn active" id="tableViewBtn" onclick="switchView('table')">
                                <i class="fa fa-table"></i>
                            </button>
                            <button class="toggle-btn" id="gridViewBtn" onclick="switchView('grid')">
                                <i class="fa fa-th"></i>
                            </button>
                        </div>
                        <button class="btn-create" onclick="showCreateModal()">
                            <i class="fa fa-plus"></i>
                            <span><?php echo get_phrase('create_profile'); ?></span>
                        </button>
                    </div>
                </div>
                
                <div class="panel-body">
                <!-- Filter Section -->
                <div class="row" style="margin-bottom: 20px;">
                    <?php if(!isset($restricted_mode) || !$restricted_mode): ?>
                    <div class="col-md-3">
                        <div class="modern-select-wrapper">
                            <i class="fa fa-filter filter-icon"></i>
                            <select id="categoryFilter" class="modern-select" onchange="filterProfiles()">
                                <option value="">All Categories</option>
                                <option value="invoice">📄 Invoice Discounts</option>
                                <option value="daily_fees">📅 Daily Fees Discounts</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="modern-select-wrapper">
                            <i class="fa fa-toggle-on filter-icon"></i>
                            <select id="statusFilter" class="modern-select" onchange="filterProfiles()">
                                <option value="">All Status</option>
                                <option value="1">✅ Active Only</option>
                                <option value="0">❌ Inactive Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="<?php echo (isset($restricted_mode) && $restricted_mode) ? 'col-md-9' : 'col-md-6'; ?>">
                        <div class="modern-search-wrapper">
                            <i class="fa fa-search search-icon"></i>
                            <input type="text" id="searchFilter" class="modern-search" placeholder="Search profiles..." onkeyup="filterProfiles()">
                            <i class="fa fa-times clear-search" onclick="clearSearch()" style="display:none;"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Table View -->
                <div id="tableView" class="table-responsive">
                    <table id="discountProfilesTable" class="table table-striped table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th><i class="fa fa-hashtag"></i> <?php echo get_phrase('id'); ?></th>
                                <th><i class="fa fa-tag"></i> <?php echo get_phrase('profile_name'); ?></th>
                                <th><i class="fa fa-list"></i> <?php echo get_phrase('discount_type'); ?></th>
                                <th><i class="fa fa-folder"></i> <?php echo get_phrase('category'); ?></th>
                                <th><i class="fa fa-calculator"></i> <?php echo get_phrase('method'); ?></th>
                                <th><i class="fa fa-dollar"></i> <?php echo get_phrase('value'); ?></th>
                                <th><i class="fa fa-toggle-on"></i> <?php echo get_phrase('status'); ?></th>
                                <th><i class="fa fa-cogs"></i> <?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                
                <!-- Grid View -->
                <div id="gridView" style="display: none;">
                    <div class="profiles-grid" id="profilesGrid">
                        <!-- Grid cards will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Get theme color from CSS variables */
:root {
    --theme-primary: <?php echo get_settings('theme_color') ?: '#2c3e50'; ?>;
    --theme-secondary: <?php echo get_settings('theme_color_2') ?: '#34495e'; ?>;
}

/* Modern Container */
.modern-container {
    padding: 0;
    background: transparent;
    width: 100%;
    max-width: 100%;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
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
    flex-shrink: 0;
}

.stat-primary .stat-icon { background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%); }
.stat-success .stat-icon { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stat-info .stat-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stat-warning .stat-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
    margin-bottom: 3px;
}

.stat-label {
    font-size: 11px;
    font-weight: 500;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Modern Panel */
.modern-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    overflow: hidden;
}

.panel-header {
    background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%);
    padding: 24px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.panel-title {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.panel-title i {
    font-size: 28px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.view-toggle {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    padding: 4px;
    display: flex;
    gap: 4px;
}

.toggle-btn {
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.7);
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 16px;
}

.toggle-btn:hover {
    color: white;
    background: rgba(255,255,255,0.1);
}

.toggle-btn.active {
    background: white;
    color: var(--theme-primary);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-create {
    background: white;
    color: var(--theme-primary);
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.panel-body {
    padding: 30px;
}

/* DataTable Modern Styling */
#discountProfilesTable {
    border: none !important;
}

#discountProfilesTable thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #495057;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 12px;
    border: none;
}

#discountProfilesTable tbody td {
    padding: 16px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

#discountProfilesTable tbody tr {
    transition: all 0.2s;
}

#discountProfilesTable tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
}

.table-responsive {
    border-radius: 12px;
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    max-width: 100%;
}

/* Ensure table doesn't collapse */
.table-responsive table {
    min-width: 800px;
}

/* Mobile horizontal scroll styling */
@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -15px;
        border-radius: 0;
    }
    
    .table-responsive table {
        min-width: 1000px;
    }
}

/* Action Buttons */
.btn-xs {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%);
}

.btn-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.btn-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.btn-xs:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Labels */
.label {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.label-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.label-warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.label-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

/* Grid View Cards */
.profiles-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    min-height: 300px;
}

@media (max-width: 1400px) {
    .profiles-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .profiles-grid {
        grid-template-columns: 1fr;
    }
}

.profile-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.profile-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    border-color: var(--theme-primary);
}

.profile-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f3f5;
}

.profile-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
    line-height: 1.4;
}

.profile-card-badge {
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-invoice { 
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.badge-daily { 
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.profile-card-body {
    flex: 1;
    margin-bottom: 20px;
}

.profile-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-size: 13px;
    color: #4a5568;
}

.profile-info-item i {
    width: 24px;
    margin-right: 10px;
    color: #a0aec0;
    font-size: 14px;
}

.profile-card-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 20px;
    border-top: 2px solid #f1f3f5;
}

.profile-card-actions .btn {
    flex: 1;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px;
    border: none;
    transition: all 0.2s;
}

.profile-card-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    color: #a0aec0;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
    display: block;
}

.empty-state p {
    font-size: 16px;
    margin: 0;
}

/* Modern Filter Styles */
.modern-select-wrapper,
.modern-search-wrapper {
    position: relative;
}

.modern-select,
.modern-search {
    width: 100%;
    padding: 12px 40px 12px 45px;
    border: 2px solid #e0e6ed;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
    color: #2c3e50;
}

.modern-select:focus,
.modern-search:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.filter-icon,
.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
    font-size: 16px;
    pointer-events: none;
    z-index: 1;
}

.clear-search {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
    cursor: pointer;
    font-size: 14px;
    transition: color 0.2s;
}

.clear-search:hover {
    color: #e74c3c;
}

.modern-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%2395a5a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"%3e%3cpolyline points="6 9 12 15 18 9"%3e%3c/polyline%3e%3c/svg%3e');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    cursor: pointer;
}

.modern-search::placeholder {
    color: #bdc3c7;
}

/* Modern Switch UX */
.modern-switch-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.modern-switch {
    position: relative;
    display: inline-block;
    width: 56px;
    height: 28px;
    cursor: pointer;
}

.modern-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.modern-slider {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
    border-radius: 34px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.modern-slider-button {
    position: absolute;
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.modern-slider-button::before {
    content: "✕";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    color: #e74c3c;
    font-weight: bold;
    opacity: 0;
    transition: opacity 0.3s;
}

.modern-switch input:checked + .modern-slider {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    box-shadow: 0 0 10px rgba(17, 153, 142, 0.3);
}

.modern-switch input:checked + .modern-slider .modern-slider-button {
    transform: translateX(28px);
}

.modern-switch input:checked + .modern-slider .modern-slider-button::before {
    content: "✓";
    color: #11998e;
    opacity: 1;
}

.modern-switch input:not(:checked) + .modern-slider .modern-slider-button::before {
    opacity: 1;
}

.modern-switch:hover .modern-slider {
    box-shadow: 0 0 8px rgba(0,0,0,0.2);
}

.modern-switch input:checked:hover + .modern-slider {
    box-shadow: 0 0 12px rgba(17, 153, 142, 0.5);
}

/* Modern Multi-Select */
.grid-cols-2 > div {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.modern-multiselect-container {
    background: #f8f9fa;
    border: 2px solid #e0e6ed;
    border-radius: 10px;
    padding: 12px;
    min-height: 200px;
    max-height: 380px;
    overflow-y: auto;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.modern-multiselect-container:focus-within {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.multiselect-placeholder {
    color: #bdc3c7;
    text-align: center;
    padding: 40px 20px;
    font-size: 14px;
}

.multiselect-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    margin-bottom: 8px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.multiselect-item:hover {
    border-color: #3498db;
    background: #f0f8ff;
    transform: translateX(4px);
}

.multiselect-item.selected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
}

.multiselect-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-right: 12px;
    cursor: pointer;
    accent-color: #667eea;
}

.multiselect-item label {
    flex: 1;
    cursor: pointer;
    margin: 0;
    font-size: 13px;
    font-weight: 500;
}

.multiselect-item.selected label {
    color: white;
}

.multiselect-select-all {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.multiselect-select-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(17, 153, 142, 0.3);
}

.multiselect-selected-count {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

/* ============================================
   MODAL FORM IMPROVEMENTS
   ============================================ */
.modal-form-label {
    display: inline-block;
    margin-bottom: 0;
    margin-right: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    line-height: 1.5;
    min-width: 120px;
    vertical-align: middle;
}

.modal-form-label i {
    margin-right: 6px;
    font-size: 13px;
}

.modal-form-input,
.modal-form-select,
.modal-form-textarea {
    background-color: #f9fafb;
    border: 1px solid #e2e8f0;
    color: #1a202c;
    font-size: 13px;
    line-height: 1.4;
    border-radius: 6px;
    display: inline-block;
    width: calc(100% - 130px);
    padding: 8px 10px;
    transition: all 0.2s;
    vertical-align: middle;
}

.modal-form-textarea {
    display: block;
    width: 100%;
    margin-top: 8px;
}

.modal-form-input:focus,
.modal-form-select:focus,
.modal-form-textarea:focus {
    outline: none;
    border-color: #4299e1;
    background-color: #fff;
    box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.1);
}

.modal-form-group {
    margin-bottom: 16px;
    display: block;
    clear: both;
}

.modal-required {
    color: #e53e3e;
    margin-left: 2px;
}

.modal-form-footer {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 2px solid #e2e8f0;
}
</style>

<script>
var table; // Global variable
var billItemsMap = {}; // Store bill items for display
var restrictedMode = <?php echo (isset($restricted_mode) && $restricted_mode) ? 'true' : 'false'; ?>;
var userRole = '<?php echo isset($account_type) ? $account_type : ''; ?>'; // Track user role
var allowedCategory = '<?php echo isset($allowed_category) ? $allowed_category : ''; ?>';

// Load bill items for display mapping
function loadBillItemsMap(callback) {
    $.ajax({
        url: '<?php echo site_url('admin/get_bill_item'); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success' && response.data) {
            response.data.forEach(function(item) {
                billItemsMap[item.id] = item.title;
            });
        }
        if(callback) callback();
    }).fail(function(xhr, status, error) {
        if(callback) callback();
    });
}

$(document).ready(function() {
    // Load bill items first, then initialize table
    loadBillItemsMap(function() {
        // Load stats
        loadStats();
    
    // Initialize DataTable with simple data
    table = $('#discountProfilesTable').DataTable({
        data: <?php echo json_encode($profiles); ?>,
        columns: [
            { data: 'profile_id', width: '5%' },
            { data: 'profile_name', width: '20%' },
            { 
                data: null,
                width: '15%',
                render: function(data, type, row) {
                    if (row.discount_category === 'invoice') {
                        var billItemIds = row.bill_item_ids || 'N/A';
                        if (billItemIds === '*') {
                            return '<span class="label label-success">All Invoice Items</span>';
                        } else if (billItemIds === 'N/A') {
                            return 'N/A';
                        } else {
                            // Convert IDs to readable names using the map
                            return billItemIds.split(',').map(function(id) {
                                var itemName = billItemsMap[id.trim()] || id.trim();
                                return '<span class="label label-info" style="margin: 2px;">' + 
                                    itemName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + 
                                    '</span>';
                            }).join(' ');
                        }
                    } else {
                        var discountType = row.discount_type || 'N/A';
                        if (discountType === 'N/A') {
                            return 'N/A';
                        }
                        return discountType.split(',').map(function(type) {
                            return '<span class="label label-warning" style="margin: 2px;">' + 
                                type.trim().replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + 
                                '</span>';
                        }).join(' ');
                    }
                }
            },
            { 
                data: 'discount_category',
                width: '10%',
                render: function(data) {
                    return data === 'invoice' ? '<span class="label label-info">Invoice</span>' : '<span class="label label-warning">Daily Fees</span>';
                }
            },
            { 
                data: 'discount_method',
                width: '10%',
                render: function(data) {
                    return data === 'percentage' ? '📊 Percentage' : '💰 Fixed';
                },
                defaultContent: '📊 Percentage'
            },
            { 
                data: 'discount_value',
                width: '10%',
                render: function(data, type, row) {
                    if(row.discount_method === 'percentage') {
                        return data + '%';
                    } else {
                        return 'GHS ' + parseFloat(data).toFixed(2);
                    }
                },
                defaultContent: '0'
            },
            { 
                data: 'is_active',
                width: '10%',
                render: function(data, type, row) {
                    const checked = data == 1 ? 'checked' : '';
                    return `<div class="modern-switch-wrapper">
                        <label class="modern-switch">
                            <input type="checkbox" ${checked} onchange="toggleStatus(${row.profile_id})" data-status="${data}">
                            <span class="modern-slider">
                                <span class="modern-slider-button"></span>
                            </span>
                        </label>
                    </div>`;
                }
            },
            { 
                data: null,
                width: '20%',
                className: 'text-right',
                render: function(data, type, row) {
                    // For cashiers, hide edit/delete buttons for invoice category profiles
                    if(userRole === 'cashier' && row.discount_category === 'invoice') {
                        return `
                            <div style="text-align: right;">
                                <span class="label label-default" style="padding: 6px 12px;">
                                    <i class="fa fa-lock"></i> Invoice Only
                                </span>
                            </div>
                        `;
                    }
                    
                    return `
                        <div style="text-align: right;">
                            <button class="btn btn-xs btn-primary" onclick="editProfile(${row.profile_id})" title="Edit">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-danger" onclick="deleteProfile(${row.profile_id})" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });
    
        // Set default view
        var savedView = localStorage.getItem('profilesView') || 'table';
        switchView(savedView);
        
        // Auto-trigger updateDiscountTypes on modal show if restricted mode
        if (restrictedMode) {
            $('#createModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    if ($('#discount_category').val() === 'daily_fees') {
                        updateDiscountTypes();
                    }
                }, 100);
            });
        }
    });
});

function filterProfiles() {
    var category = $('#categoryFilter').val();
    var status = $('#statusFilter').val();
    var search = $('#searchFilter').val().toLowerCase();
    
    // Filter DataTable
    if(table) {
        $.fn.dataTable.ext.search = [];
        
        // Add category filter
        if(category !== '') {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var rowData = table.row(dataIndex).data();
                    return rowData.discount_category === category;
                }
            );
        }
        
        // Add status filter
        if(status !== '') {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var rowData = table.row(dataIndex).data();
                    return rowData.is_active == status;
                }
            );
        }
        
        // Apply search and redraw
        table.search(search).draw();
    }
    
    // Filter Grid
    $('.profile-card').each(function() {
        var card = $(this);
        var profileCategory = card.find('.badge-invoice').length > 0 ? 'invoice' : 'daily_fees';
        var profileName = card.find('.profile-card-title').text().toLowerCase();
        var isActive = card.find('.modern-switch input').is(':checked');
        var profileStatus = isActive ? '1' : '0';
        
        var categoryMatch = !category || profileCategory === category;
        var statusMatch = !status || profileStatus === status;
        var searchMatch = !search || profileName.includes(search);
        
        if(categoryMatch && statusMatch && searchMatch) {
            card.show();
        } else {
            card.hide();
        }
    });
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
        loadGridView();
    }
}

function loadGridView() {
    $.ajax({
        url: '<?php echo site_url('admin/discount_profiles'); ?>',
        type: 'GET',
        dataType: 'json',
        data: { ajax: 1 }
    }).done(function(response) {
        var grid = $('#profilesGrid');
        grid.empty();
        
        if(response.profiles && response.profiles.length > 0) {
            response.profiles.forEach(function(profile) {
                var categoryBadge = profile.discount_category === 'invoice' ? 
                    '<span class="profile-card-badge badge-invoice">📄 Invoice</span>' :
                    '<span class="profile-card-badge badge-daily">📅 Daily Fees</span>';
                
                var readableType;
                if (profile.discount_category === 'invoice') {
                    var billItemIds = profile.bill_item_ids || 'N/A';
                    if (billItemIds === '*') {
                        readableType = '<span class="label label-success">All Invoice Items</span>';
                    } else if (billItemIds === 'N/A') {
                        readableType = 'N/A';
                    } else {
                        readableType = billItemIds.split(',').map(function(id) {
                            var itemName = billItemsMap[id.trim()] || id.trim();
                            return '<span class="label label-info" style="margin: 2px;">' + 
                                itemName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + 
                                '</span>';
                        }).join(' ');
                    }
                } else {
                    var discountType = profile.discount_type || 'N/A';
                    if (discountType === 'N/A') {
                        readableType = 'N/A';
                    } else {
                        readableType = discountType.split(',').map(function(type) {
                            return '<span class="label label-warning" style="margin: 2px;">' + 
                                type.trim().replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + 
                                '</span>';
                        }).join(' ');
                    }
                }
                var methodIcon = profile.discount_method === 'percentage' ? '📊' : '💰';
                var methodText = profile.discount_method === 'percentage' ? 'Percentage' : 'Fixed';
                var valueText = profile.discount_method === 'percentage' ? profile.discount_value + '%' : 'GHS ' + parseFloat(profile.discount_value).toFixed(2);
                var isActive = profile.is_active == 1;
                var checked = isActive ? 'checked' : '';
                var statusBadge = isActive ? '<span class="label label-success"><i class="fa fa-check-circle"></i> Active</span>' : '<span class="label label-default"><i class="fa fa-times-circle"></i> Inactive</span>';
                
                var card = `
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h4 class="profile-card-title">${profile.profile_name}</h4>
                            ${categoryBadge}
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-info-item">
                                <i class="fa fa-list"></i>
                                <span><strong>Type:</strong> ${readableType}</span>
                            </div>
                            <div class="profile-info-item">
                                <i class="fa fa-calculator"></i>
                                <span><strong>Method:</strong> ${methodIcon} ${methodText}</span>
                            </div>
                            <div class="profile-info-item">
                                <i class="fa fa-dollar"></i>
                                <span><strong>Value:</strong> ${valueText}</span>
                            </div>
                            <div class="profile-info-item">
                                <i class="fa fa-toggle-on"></i>
                                <span><strong>Status:</strong> ${statusBadge}</span>
                            </div>
                            <div class="profile-info-item">
                                <i class="fa fa-align-left"></i>
                                <span>${profile.description || 'No description'}</span>
                            </div>
                        </div>
                        <div class="profile-card-actions">
                            ${userRole === 'cashier' && profile.discount_category === 'invoice' ? `
                                <span class="label label-default" style="padding: 8px 16px; display: block; text-align: center;">
                                    <i class="fa fa-lock"></i> Invoice Only - No Access
                                </span>
                            ` : `
                            <div class="modern-switch-wrapper">
                                <label class="modern-switch">
                                    <input type="checkbox" ${checked} onchange="toggleStatus(${profile.profile_id})">
                                    <span class="modern-slider">
                                        <span class="modern-slider-button"></span>
                                    </span>
                                </label>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="editProfile(${profile.profile_id})">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProfile(${profile.profile_id})">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                            `}
                        </div>
                    </div>
                `;
                grid.append(card);
            });
        } else {
            grid.html('<div class="empty-state"><i class="fa fa-inbox"></i><p>No profiles found</p></div>');
        }
    });
}

function handleFormSubmit(e) {
    e.preventDefault();
    showAjaxModal_alert('<?php echo get_phrase('processing'); ?>', 'loading');
    
    var profile_id = $('#profile_id').val();
    var url = profile_id ? 
        '<?php echo site_url('admin/discount_profiles/update/'); ?>' + profile_id :
        '<?php echo site_url('admin/discount_profiles/create'); ?>';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: $('#profileForm').serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('#createModal').modal('hide');
            showAjaxModal_alert(response.message, 'success', false);
            // Reload data without page refresh
            setTimeout(() => reloadProfilesData(), 500);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
}

function reloadProfilesData() {
    // Reload stats
    loadStats();
    
    // Reload table
    $.ajax({
        url: '<?php echo site_url('admin/discount_profiles'); ?>',
        type: 'GET',
        data: { ajax: 1 },
        dataType: 'json'
    }).done(function(response) {
        if(response.profiles) {
            // Update DataTable
            if(table) {
                table.clear();
                table.rows.add(response.profiles);
                table.draw();
            }
            
            // Update Grid if visible
            if($('#gridView').is(':visible')) {
                loadGridView();
            }
        }
    });
}

function loadStats() {
    $.ajax({
        url: '<?php echo site_url('admin/discount_profiles/stats'); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('#totalProfiles').text(response.data.total || 0);
            $('#activeCount').text(response.data.active || 0);
            $('#inactiveCount').text(response.data.inactive || 0);
            $('#activeProfiles').text(response.data.active || 0);
            $('#invoiceProfiles').text(response.data.invoice || 0);
            $('#dailyProfiles').text(response.data.daily_fees || 0);
        }
    });
}

function showCreateModal() {
    var categoryOptions = '';
    if (restrictedMode) {
        // Only show daily_fees for cashiers/conductors
        categoryOptions = `
            <option value="daily_fees" selected>📅 <?php echo get_phrase('daily_fees'); ?></option>
        `;
    } else {
        // Show all categories for admins
        categoryOptions = `
            <option value="">Select category...</option>
            <option value="invoice">📄 <?php echo get_phrase('invoice'); ?></option>
            <option value="daily_fees">📅 <?php echo get_phrase('daily_fees'); ?></option>
        `;
    }
    
    var formHtml = `
        <form id="profileForm" method="post" action="<?php echo site_url($account_type . '/discount_profiles/create'); ?>" class="space-y-6">
            <input type="hidden" id="profile_id" name="profile_id">
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="modal-form-group">
                        <label class="modal-form-label">
                            <i class="fa fa-tag text-primary"></i> <?php echo get_phrase('profile_name'); ?>
                            <span class="modal-required">*</span>
                        </label>
                        <input type="text" name="profile_name" id="profile_name" 
                               class="modal-form-input" 
                               placeholder="e.g., Staff Children Discount" required>
                    </div>
                    
                    <div class="modal-form-group">
                        <label class="modal-form-label">
                            <i class="fa fa-folder text-primary"></i> <?php echo get_phrase('category'); ?>
                            <span class="modal-required">*</span>
                        </label>
                        <select name="discount_category" id="discount_category" 
                                class="modal-form-select" 
                                ${restrictedMode ? 'disabled' : ''} 
                                required onchange="updateDiscountTypes()">
                            ${categoryOptions}
                        </select>
                        ${restrictedMode ? '<input type="hidden" name="discount_category" value="daily_fees">' : ''}
                    </div>
                    
                    <div class="modal-form-group">
                        <label class="modal-form-label">
                            <i class="fa fa-calculator text-primary"></i> <?php echo get_phrase('method'); ?>
                            <span class="modal-required">*</span>
                        </label>
                        <select name="discount_method" id="discount_method" 
                                class="modal-form-select" 
                                required>
                            <option value="percentage">📊 Percentage (%)</option>
                            <option value="fixed">💰 Fixed Amount (GHS)</option>
                        </select>
                    </div>
                    
                    <div class="modal-form-group">
                        <label class="modal-form-label">
                            <i class="fa fa-dollar text-success"></i> <?php echo get_phrase('value'); ?>
                            <span class="modal-required">*</span>
                        </label>
                        <input type="number" name="discount_value" id="discount_value" step="0.01" min="0"
                               class="modal-form-input" 
                               placeholder="e.g., 50 or 100.00" required>
                    </div>
                    
                    <div>
                        <label class="modal-form-label">
                            <i class="fa fa-align-left text-muted"></i> <?php echo get_phrase('description'); ?>
                        </label>
                        <textarea name="description" id="description" rows="3" 
                                  class="modal-form-textarea" 
                                  placeholder="Optional description..."></textarea>
                    </div>
                </div>
                
                <div>
                    <label class="modal-form-label">
                        <i class="fa fa-list text-success"></i> <?php echo get_phrase('type'); ?>
                        <span class="modal-required">*</span>
                    </label>
                    <div id="discount_type_container" class="modern-multiselect-container">
                        <div class="multiselect-placeholder">Select category first...</div>
                    </div>
                </div>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
                <div class="flex gap-3 justify-end">
                    <button type="button" data-dismiss="modal" 
                            class="text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 font-semibold rounded-lg text-lg px-10 py-3.5">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" 
                            class="text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-semibold rounded-lg text-lg px-10 py-3.5">
                        <i class="fa fa-save"></i> Save
                    </button>
                </div>
            </div>
        </form>
    `;
    
    showModalWithContent('createModal', '<i class="fa fa-plus-circle"></i> <?php echo get_phrase('create_discount_profile'); ?>', formHtml);
    
    // Attach form handler after modal is shown
    $('#createModal').on('shown.bs.modal', function() {
        $('#profileForm').off('submit').on('submit', handleFormSubmit);
    });
}

function editProfile(profile_id) {
    $.ajax({
        url: '<?php echo site_url($account_type . '/discount_profiles/get_data'); ?>',
        type: 'GET',
        data: { profile_id: profile_id },
        dataType: 'json'
    }).done(function(data) {
        
        var categoryOptions = '';
        if (restrictedMode) {
            // Only show daily_fees for cashiers/conductors
            categoryOptions = `
                <option value="daily_fees" selected>📅 <?php echo get_phrase('daily_fees'); ?></option>
            `;
        } else {
            // Show all categories for admins
            categoryOptions = `
                <option value="">Select category...</option>
                <option value="invoice" ${data.discount_category === 'invoice' ? 'selected' : ''}>📄 <?php echo get_phrase('invoice'); ?></option>
                <option value="daily_fees" ${data.discount_category === 'daily_fees' ? 'selected' : ''}>📅 <?php echo get_phrase('daily_fees'); ?></option>
            `;
        }
        
        var formHtml = `
            <form id="profileForm" method="post" action="<?php echo site_url($account_type . '/discount_profiles/update'); ?>" class="space-y-6">
                <input type="hidden" id="profile_id" name="profile_id" value="${data.profile_id}">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="modal-form-group">
                            <label class="modal-form-label">
                                <i class="fa fa-tag text-primary"></i> <?php echo get_phrase('profile_name'); ?>
                                <span class="modal-required">*</span>
                            </label>
                            <input type="text" name="profile_name" id="profile_name" value="${data.profile_name}"
                                   class="modal-form-input" 
                                   required>
                        </div>
                        
                        <div class="modal-form-group">
                            <label class="modal-form-label">
                                <i class="fa fa-folder text-primary"></i> <?php echo get_phrase('category'); ?>
                                <span class="modal-required">*</span>
                            </label>
                            <select name="discount_category" id="discount_category" 
                                    class="modal-form-select" 
                                    ${restrictedMode ? 'disabled' : ''} 
                                    required onchange="updateDiscountTypes()">
                                ${categoryOptions}
                            </select>
                            ${restrictedMode ? '<input type="hidden" name="discount_category" value="daily_fees">' : ''}
                        </div>
                        
                        <div class="modal-form-group">
                            <label class="modal-form-label">
                                <i class="fa fa-calculator text-primary"></i> <?php echo get_phrase('method'); ?>
                                <span class="modal-required">*</span>
                            </label>
                            <select name="discount_method" id="discount_method" 
                                    class="modal-form-select" 
                                    required>
                                <option value="percentage" ${data.discount_method === 'percentage' ? 'selected' : ''}>📊 Percentage (%)</option>
                                <option value="fixed" ${data.discount_method === 'fixed' ? 'selected' : ''}>💰 Fixed Amount (GHS)</option>
                            </select>
                        </div>
                        
                        <div class="modal-form-group">
                            <label class="modal-form-label">
                                <i class="fa fa-dollar text-success"></i> <?php echo get_phrase('value'); ?>
                                <span class="modal-required">*</span>
                            </label>
                            <input type="number" name="discount_value" id="discount_value" step="0.01" min="0" value="${data.discount_value || 0}"
                                   class="modal-form-input" 
                                   placeholder="e.g., 50 or 100.00" required>
                        </div>
                        
                        <div class="modal-form-group">
                            <label class="modal-form-label">
                                <i class="fa fa-align-left text-muted"></i> <?php echo get_phrase('description'); ?>
                            </label>
                            <textarea name="description" id="description" rows="3" 
                                      class="modal-form-textarea">${data.description || ''}</textarea>
                        </div>
                    </div>
                    
                    <div>
                        <label class="modal-form-label">
                            <i class="fa fa-list text-success"></i> <?php echo get_phrase('type'); ?>
                            <span class="modal-required">*</span>
                        </label>
                        <div id="discount_type_container" class="modern-multiselect-container">
                            <div class="multiselect-placeholder">Select category first...</div>
                        </div>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex gap-3 justify-end">
                        <button type="button" data-dismiss="modal" 
                                class="text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 font-semibold rounded-lg text-lg px-10 py-3.5">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button type="submit" 
                                class="text-white bg-gradient-to-r from-purple-500 via-purple-600 to-purple-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-purple-300 font-semibold rounded-lg text-lg px-10 py-3.5">
                            <i class="fa fa-save"></i> Update
                        </button>
                    </div>
                </div>
            </form>
        `;
        
        showModalWithContent('createModal', '<i class="fa fa-edit"></i> <?php echo get_phrase('edit_discount_profile'); ?>', formHtml);
        
        // Remove any previous event handlers to prevent stacking
        $('#createModal').off('shown.bs.modal');
        
        // Populate discount types after modal is shown
        $('#createModal').on('shown.bs.modal', function() {
            updateDiscountTypes();
            setTimeout(function() {
                // Clear all checkboxes first
                $('.multiselect-item input[type="checkbox"]').prop('checked', false);
                $('.multiselect-item').removeClass('selected');
                
                // Get the correct field based on category
                var selectedValues = data.discount_category === 'invoice' ? data.bill_item_ids : data.discount_type;
                
                // Handle "*" for all invoice items
                if (selectedValues === '*') {
                    selectedValues = 'all_invoice_items';
                }
                
                if (selectedValues) {
                    var types = selectedValues.split(',');
                    types.forEach(function(type) {
                        var trimmedType = type.trim();
                        $('.multiselect-item input[value="' + trimmedType + '"]').prop('checked', true).closest('.multiselect-item').addClass('selected');
                    });
                    updateHiddenField();
                    updateSelectAllButton();
                }
            }, 800);
            $('#profileForm').off('submit').on('submit', handleFormSubmit);
        });
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('failed_to_load_data'); ?>', 'error');
    });
}

function deleteProfile(profile_id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_delete'); ?>',
        '<?php echo get_phrase('are_you_sure_delete_profile'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/discount_profiles/delete'); ?>',
                type: 'POST',
                data: { profile_id: profile_id },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => reloadProfilesData(), 500);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}

function toggleStatus(profile_id) {
    showAjaxModal_alert('<?php echo get_phrase('updating'); ?>', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/discount_profiles/toggle_status'); ?>',
        type: 'POST',
        data: { profile_id: profile_id },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(() => reloadProfilesData(), 500);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
}

function updateDiscountTypes() {
    var category = $('#discount_category').val();
    var container = $('#discount_type_container');
    
    container.html('<div class="multiselect-placeholder">Loading...</div>');
    
    if (!category) {
        container.html('<div class="multiselect-placeholder">Select category first...</div>');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url('admin/get_discount_types_for_profiles'); ?>',
        type: 'GET',
        data: { category: category },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success' && response.data && response.data.length > 0) {
            var html = '<button type="button" class="multiselect-select-all" onclick="toggleSelectAll()">Select All</button>';
            html += '<div class="multiselect-items">';
            
            response.data.forEach(function(item) {
                html += '<div class="multiselect-item">';
                html += '<input type="checkbox" value="' + item.value + '" onchange="toggleItemByCheckbox(this); updateHiddenField(); updateSelectAllButton();">';
                html += '<label onclick="toggleItemByLabel(event)">' + item.label + '</label>';
                html += '</div>';
            });
            
            html += '</div>';
            container.html(html);
        } else {
            container.html('<div class="multiselect-placeholder">No items available</div>');
        }
    }).fail(function() {
        container.html('<div class="multiselect-placeholder">Error loading types</div>');
    });
}

function toggleItemByCheckbox(checkbox) {
    $(checkbox).closest('.multiselect-item').toggleClass('selected', checkbox.checked);
}

function toggleItemByLabel(event) {
    event.stopPropagation();
    var item = $(event.target).closest('.multiselect-item');
    var checkbox = item.find('input[type="checkbox"]');
    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    item.toggleClass('selected', checkbox.prop('checked'));
}

function toggleItem(element) {
    var checkbox = $(element).find('input[type="checkbox"]');
    checkbox.prop('checked', !checkbox.prop('checked'));
    $(element).toggleClass('selected', checkbox.prop('checked'));
    updateHiddenField();
    updateSelectAllButton();
}

function updateSelectAllButton() {
    var total = $('.multiselect-item input[type="checkbox"]').length;
    var checked = $('.multiselect-item input[type="checkbox"]:checked').length;
    var btn = $('.multiselect-select-all');
    btn.text(checked === total ? 'Deselect All' : 'Select All');
}

function toggleSelectAll() {
    var allChecked = $('.multiselect-item input[type="checkbox"]:checked').length === $('.multiselect-item input[type="checkbox"]').length;
    $('.multiselect-item input[type="checkbox"]').prop('checked', !allChecked);
    $('.multiselect-item').toggleClass('selected', !allChecked);
    
    // Update button text
    var btn = $('.multiselect-select-all');
    btn.text(allChecked ? 'Select All' : 'Deselect All');
    
    updateHiddenField();
}

function updateHiddenField() {
    var selected = [];
    $('.multiselect-item input[type="checkbox"]:checked').each(function() {
        selected.push($(this).val());
    });
    
    // Remove ALL existing discount_type inputs
    $('input[name="discount_type"]').remove();
    $('input[name="discount_type[]"]').remove();
    $('#discount_type_hidden').remove();
    
    // Add single hidden field with comma-separated values
    $('<input>').attr({
        type: 'hidden',
        name: 'discount_type',
        id: 'discount_type_hidden',
        value: selected.join(',')
    }).appendTo('#profileForm');
}

function clearSearch() {
    $('#searchFilter').val('');
    $('.clear-search').hide();
    filterProfiles();
}

// Show/hide clear button
$('#searchFilter').on('input', function() {
    if($(this).val().length > 0) {
        $('.clear-search').show();
    } else {
        $('.clear-search').hide();
    }
});
</script>
