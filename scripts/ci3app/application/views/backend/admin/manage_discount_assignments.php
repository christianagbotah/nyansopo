<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.enterprise-container { padding: 24px; background: #f8f9fa; min-height: 100vh; }
.enterprise-header { background: white; color: #1a202c; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #667eea; }
.enterprise-header h1 { margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: #667eea; }
.enterprise-header p { margin: 0; color: #6b7280; font-size: 16px; }
.header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
.view-toggle { background: rgba(102, 126, 234, 0.1); border-radius: 10px; padding: 4px; display: flex; gap: 4px; }
.toggle-btn { background: transparent; border: none; color: #667eea; padding: 8px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s; font-size: 16px; }
.toggle-btn:hover { background: rgba(102, 126, 234, 0.2); }
.toggle-btn.active { background: #667eea; color: white; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
@media (min-width: 768px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.active { border-color: #10b981; }
.stat-card.inactive { border-color: #ef4444; }
.stat-card.total { border-color: #667eea; }
.stat-card.profiles { border-color: #f59e0b; }
.stat-value { font-size: 36px; font-weight: 700; margin: 8px 0; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.toolbar { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.toolbar-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
.search-box { flex: 1; min-width: 300px; }
.search-box input { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; }
.filter-select { padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; min-width: 180px; }
.btn-enterprise { padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-secondary { background: #6b7280; color: white; }
.modern-modal-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px 12px 0 0; }
.data-table { background: white; border-radius: 12px; overflow-x: auto; -webkit-overflow-scrolling: touch; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.table { width: 100%; border-collapse: collapse; min-width: 1000px; }
.table thead { background: #f9fafb; }
.table th { padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
.table td { padding: 16px; border-bottom: 1px solid #f3f4f6; }
.table tbody tr:hover { background: #f9fafb; }
.badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-warning { background: #fef3c7; color: #92400e; }
.action-btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
.action-btn:hover { transform: scale(1.05); }
.checkbox-cell { width: 40px; }
.checkbox-cell input { width: 18px; height: 18px; cursor: pointer; }
.modern-switch-wrapper { display: inline-flex; align-items: center; justify-content: center; }
.modern-switch { position: relative; display: inline-block; width: 56px; height: 28px; cursor: pointer; }
.modern-switch input { opacity: 0; width: 0; height: 0; }
.modern-slider { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%); border-radius: 34px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
.modern-slider-button { position: absolute; height: 22px; width: 22px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.modern-slider-button::before { content: "✕"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; color: #e74c3c; font-weight: bold; opacity: 1; transition: opacity 0.3s; }
.modern-switch input:checked + .modern-slider { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 0 10px rgba(16, 185, 129, 0.3); }
.modern-switch input:checked + .modern-slider .modern-slider-button { transform: translateX(28px); }
.modern-switch input:checked + .modern-slider .modern-slider-button::before { content: "✓"; color: #10b981; opacity: 1; }
.modern-switch:hover .modern-slider { box-shadow: 0 0 8px rgba(0,0,0,0.2); }
.modern-switch input:checked:hover + .modern-slider { box-shadow: 0 0 12px rgba(16, 185, 129, 0.5); }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; grid-column: 1 / -1; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; display: block; }
.assignments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; }
.assignment-card { background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; transition: all 0.3s; height: 100%; display: flex; flex-direction: column; }
.assignment-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); border-color: #667eea; }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 2px solid #f3f4f6; }
.card-student { flex: 1; }
.card-student-name { font-size: 18px; font-weight: 700; color: #1a202c; margin: 0 0 4px 0; }
.card-student-code { font-size: 13px; color: #6b7280; }
.card-body { flex: 1; margin-bottom: 16px; }
.card-info-item { display: flex; align-items: center; margin-bottom: 12px; font-size: 14px; color: #4a5568; }
.card-info-item i { width: 24px; margin-right: 10px; color: #a0aec0; font-size: 16px; }
.card-actions { display: flex; align-items: center; gap: 8px; padding-top: 16px; border-top: 2px solid #f3f4f6; }
.card-btn { padding: 10px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex: 1; }
.card-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
.card-btn-edit { background: #667eea; color: white; }
.card-btn-delete { background: #ef4444; color: white; }

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .enterprise-container { padding: 12px; }
    .enterprise-header { padding: 20px; }
    .enterprise-header h1 { font-size: 24px; }
    .toolbar { padding: 16px; }
    .toolbar-row { flex-direction: column; align-items: stretch; }
    .search-box { min-width: 100%; }
    .filter-select { width: 100%; }
    .btn-enterprise { width: 100%; justify-content: center; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .stat-card { padding: 16px; }
    .stat-value { font-size: 28px; }
    .assignments-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .enterprise-header h1 { font-size: 20px; }
    .enterprise-header p { font-size: 14px; }
    .stat-card { padding: 12px; }
    .stat-value { font-size: 24px; }
    .stat-label { font-size: 12px; }
}
</style>

<div class="enterprise-container">
    <div class="enterprise-header">
        <div class="header-flex">
            <div>
                <h1><i class="fa fa-users-cog"></i> <?php echo get_phrase('manage_discount_assignments'); ?></h1>
                <p><?php echo get_phrase('view_edit_and_manage_all_student_discount_assignments'); ?></p>
            </div>
            <div class="view-toggle">
                <button class="toggle-btn active" id="tableViewBtn" onclick="switchView('table')">
                    <i class="fa fa-table"></i>
                </button>
                <button class="toggle-btn" id="gridViewBtn" onclick="switchView('grid')">
                    <i class="fa fa-th"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-label"><?php echo get_phrase('total_assignments'); ?></div>
            <div class="stat-value" id="stat-total">0</div>
        </div>
        <div class="stat-card active">
            <div class="stat-label"><?php echo get_phrase('active_discounts'); ?></div>
            <div class="stat-value" id="stat-active">0</div>
        </div>
        <div class="stat-card inactive">
            <div class="stat-label"><?php echo get_phrase('inactive_discounts'); ?></div>
            <div class="stat-value" id="stat-inactive">0</div>
        </div>
        <div class="stat-card profiles">
            <div class="stat-label"><?php echo get_phrase('unique_students'); ?></div>
            <div class="stat-value" id="stat-students">0</div>
        </div>
    </div>

    <div class="toolbar">
        <div class="toolbar-row">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="<?php echo get_phrase('search_by_student_name_code_or_profile'); ?>">
            </div>
            <select id="filterClass" class="filter-select">
                <option value=""><?php echo get_phrase('all_classes'); ?></option>
                <?php getFullClassList(); ?>
            </select>
            <select id="filterProfile" class="filter-select">
                <option value=""><?php echo get_phrase('all_profiles'); ?></option>
                <?php if(isset($profiles) && is_array($profiles)):
                    foreach($profiles as $profile): ?>
                    <option value="<?php echo $profile['profile_name']; ?>"><?php echo $profile['profile_name']; ?></option>
                <?php 
                    endforeach;
                endif;
                ?>
            </select>
            <select id="filterStatus" class="filter-select">
                <option value=""><?php echo get_phrase('all_status'); ?></option>
                <option value="1"><?php echo get_phrase('active'); ?></option>
                <option value="0"><?php echo get_phrase('inactive'); ?></option>
            </select>
            <button class="btn-enterprise btn-secondary" onclick="bulkToggle()">
                <i class="fa fa-exchange"></i> <?php echo get_phrase('bulk_toggle_status'); ?>
            </button>
            <button class="btn-enterprise btn-success" onclick="openAssignModal()">
                <i class="fa fa-user-tag"></i> <?php echo get_phrase('assign_students'); ?>
            </button>
            <button class="btn-enterprise btn-primary" onclick="openBulkModal()" style="display:none;">
                <i class="fa fa-users"></i> <?php echo get_phrase('bulk_assign_class'); ?>
            </button>
        </div>
    </div>

    <div class="data-table" id="tableView">
        <table class="table" id="assignmentsTable">
            <thead>
                <tr>
                    <th class="checkbox-cell"><input type="checkbox" id="selectAll"></th>
                    <th><?php echo get_phrase('student'); ?></th>
                    <th><?php echo get_phrase('class'); ?></th>
                    <th><?php echo get_phrase('profile'); ?></th>
                    <th><?php echo get_phrase('discount_value'); ?></th>
                    <th><?php echo get_phrase('assigned_by'); ?></th>
                    <th><?php echo get_phrase('status'); ?></th>
                    <th><?php echo get_phrase('actions'); ?></th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fa fa-spinner fa-spin"></i>
                        <div><?php echo get_phrase('loading_data'); ?>...</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div id="gridView" style="display: none;">
        <div class="assignments-grid" id="gridContainer">
            <div class="empty-state">
                <i class="fa fa-spinner fa-spin"></i>
                <div><?php echo get_phrase('loading_data'); ?>...</div>
            </div>
        </div>
    </div>
</div>

<script>
let allData = [];
let filteredData = [];
const billItemsMap = <?php echo json_encode($bill_items_map); ?>;

function getTypeDisplay(item) {
    if(item.discount_category === 'invoice') {
        if(item.bill_item_ids === '*') {
            return 'All Invoice Items';
        } else if(item.bill_item_ids) {
            return item.bill_item_ids.split(',').map(id => {
                const title = billItemsMap[id] || 'Unknown';
                return title.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
            }).join(', ');
        }
    } else if(item.discount_category === 'daily_fees' && item.discount_type) {
        return item.discount_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    return 'N/A';
}

$(document).ready(function() {
    // Collapse sidebar on page load
    if (!$('body').hasClass('sidebar-collapse')) {
        $('body').addClass('sidebar-collapse');
    }
    
    loadData();
    
    $('#searchInput').on('input', filterData);
    $('#filterClass, #filterProfile, #filterStatus').on('change', filterData);
    
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('assignmentsView') || 'table';
    switchView(savedView);
});

function loadData() {
    $.ajax({
        url: '<?php echo site_url('admin/manage_discount_assignments/get_data'); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            allData = response.data;
            filteredData = allData;
            updateStats();
            renderTable();
            if($('#gridView').is(':visible')) {
                renderGrid();
            }
        }
    }).fail(function() {
        $('#tableBody').html(`
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div>Failed to load data</div>
                </td>
            </tr>
        `);
    });
}

function updateStats() {
    const total = allData.length;
    const active = allData.filter(d => d.is_active == 1).length;
    const inactive = total - active;
    const uniqueStudents = [...new Set(allData.map(d => d.student_id))].length;
    
    $('#stat-total').text(total);
    $('#stat-active').text(active);
    $('#stat-inactive').text(inactive);
    $('#stat-students').text(uniqueStudents);
}

function filterData() {
    const search = $('#searchInput').val().toLowerCase();
    const classFilter = $('#filterClass').val();
    const profileFilter = $('#filterProfile').val();
    const statusFilter = $('#filterStatus').val();
    
    filteredData = allData.filter(item => {
        const matchSearch = !search || 
            item.student_name.toLowerCase().includes(search) ||
            item.student_code.toLowerCase().includes(search) ||
            item.profile_name.toLowerCase().includes(search);
        const matchClass = !classFilter || item.class_name.includes(classFilter);
        const matchProfile = !profileFilter || item.profile_name === profileFilter;
        const matchStatus = statusFilter === '' || item.is_active == statusFilter;
        
        return matchSearch && matchClass && matchProfile && matchStatus;
    });
    
    renderTable();
    if($('#gridView').is(':visible')) {
        renderGrid();
    }
}

function renderTable() {
    $('#selectAll').prop('checked', false);
    
    if(filteredData.length === 0) {
        $('#tableBody').html(`
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <div><?php echo get_phrase('no_assignments_found'); ?></div>
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    filteredData.forEach(item => {
        const statusBadge = item.is_active == 1 
            ? '<span class="badge badge-success"><i class="fa fa-check-circle"></i> Active</span>'
            : '<span class="badge badge-danger"><i class="fa fa-times-circle"></i> Inactive</span>';
        
        const approvalBadge = item.status === 'approved' 
            ? '<span class="badge badge-success"><i class="fa fa-check"></i> Approved</span>'
            : item.status === 'pending'
            ? '<span class="badge badge-warning"><i class="fa fa-clock-o"></i> Pending</span>'
            : '<span class="badge badge-danger"><i class="fa fa-times"></i> Rejected</span>';
        
        const checked = item.is_active == 1 ? 'checked' : '';
        const toggleSwitch = `
            <div class="modern-switch-wrapper">
                <label class="modern-switch">
                    <input type="checkbox" ${checked} onchange="toggleStatus(${item.assignment_id})">
                    <span class="modern-slider">
                        <span class="modern-slider-button"></span>
                    </span>
                </label>
            </div>
        `;
        
        const actionButtons = `
            <button class="action-btn" style="background: #667eea; color: white; margin-right: 4px;" onclick="editAssignment(${item.assignment_id})">
                <i class="fa fa-edit"></i>
            </button>
            <button class="action-btn" style="background: #ef4444; color: white;" onclick="deleteAssignment(${item.assignment_id})">
                <i class="fa fa-trash"></i>
            </button>
        `;
        
        html += `
            <tr>
                <td class="checkbox-cell"><input type="checkbox" class="row-checkbox" value="${item.assignment_id}"></td>
                <td>
                    <div style="font-weight: 600;">${item.student_name}</div>
                    <div style="font-size: 12px; color: #6b7280;">${item.student_code}</div>
                </td>
                <td><span class="badge badge-info">${item.class_name}</span></td>
                <td>
                    <div style="font-weight: 600;">${item.profile_name}</div>
                    <div style="font-size: 12px; color: #6b7280;">${getTypeDisplay(item)}</div>
                </td>
                <td>
                    <strong>${item.discount_method === 'percentage' ? item.discount_value + '%' : 'GHS ' + parseFloat(item.discount_value).toFixed(2)}</strong>
                    <div style="font-size: 11px; color: #6b7280;">${item.discount_method === 'percentage' ? 'Percentage' : 'Fixed'} (Profile Default)</div>
                </td>
                <td style="font-size: 13px;">${item.assigned_by_name}</td>
                <td>
                    ${toggleSwitch}
                    <div style="margin-top: 8px;">${approvalBadge}</div>
                </td>
                <td>
                    <div style="display: flex; gap: 4px;">
                        ${actionButtons}
                    </div>
                </td>
            </tr>
        `;
    });
    $('#tableBody').html(html);
}

function renderGrid() {
    if(filteredData.length === 0) {
        $('#gridContainer').html(`
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <div><?php echo get_phrase('no_assignments_found'); ?></div>
            </div>
        `);
        return;
    }
    
    let html = '';
    filteredData.forEach(item => {
        const checked = item.is_active == 1 ? 'checked' : '';
        const statusBadge = item.is_active == 1 
            ? '<span class="badge badge-success"><i class="fa fa-check-circle"></i> Active</span>'
            : '<span class="badge badge-danger"><i class="fa fa-times-circle"></i> Inactive</span>';
        
        const approvalBadge = item.status === 'approved' 
            ? '<span class="badge badge-success"><i class="fa fa-check"></i> Approved</span>'
            : item.status === 'pending'
            ? '<span class="badge badge-warning"><i class="fa fa-clock-o"></i> Pending</span>'
            : '<span class="badge badge-danger"><i class="fa fa-times"></i> Rejected</span>';
        
        html += `
            <div class="assignment-card">
                <div class="card-header">
                    <div class="card-student">
                        <h4 class="card-student-name">${item.student_name}</h4>
                        <div class="card-student-code">${item.student_code}</div>
                    </div>
                    ${statusBadge}
                </div>
                <div class="card-body">
                    <div class="card-info-item">
                        <i class="fa fa-graduation-cap"></i>
                        <span><strong>Class:</strong> ${item.class_name}</span>
                    </div>
                    <div class="card-info-item">
                        <i class="fa fa-tag"></i>
                        <span><strong>Profile:</strong> ${item.profile_name}</span>
                    </div>
                    <div class="card-info-item">
                        <i class="fa fa-list"></i>
                        <span><strong>Type:</strong> ${getTypeDisplay(item)}</span>
                    </div>
                    <div class="card-info-item">
                        <i class="fa fa-percent"></i>
                        <span><strong>Value:</strong> ${item.discount_method === 'percentage' ? item.discount_value + '%' : 'GHS ' + parseFloat(item.discount_value).toFixed(2)}</span>
                    </div>
                    <div class="card-info-item">
                        <i class="fa fa-user"></i>
                        <span><strong>Assigned by:</strong> ${item.assigned_by_name}</span>
                    </div>
                    <div class="card-info-item">
                        <i class="fa fa-check-circle"></i>
                        <span><strong>Approval:</strong> ${approvalBadge}</span>
                    </div>
                </div>
                <div class="card-actions">
                    <div class="modern-switch-wrapper">
                        <label class="modern-switch">
                            <input type="checkbox" ${checked} onchange="toggleStatus(${item.assignment_id})">
                            <span class="modern-slider">
                                <span class="modern-slider-button"></span>
                            </span>
                        </label>
                    </div>
                    <button class="card-btn card-btn-edit" onclick="editAssignment(${item.assignment_id})">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="card-btn card-btn-delete" onclick="deleteAssignment(${item.assignment_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    $('#gridContainer').html(html);
}

function switchView(view) {
    localStorage.setItem('assignmentsView', view);
    
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

function toggleStatus(id) {
    showAjaxModal_alert('Processing...', 'loading');
    $.ajax({
        url: '<?php echo site_url('admin/manage_discount_assignments/toggle_status'); ?>',
        type: 'POST',
        data: {assignment_id: id},
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(() => loadData(), 500);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function deleteAssignment(id) {
    showConfirmModal(
        'Confirm Delete',
        'Are you sure you want to permanently delete this assignment?',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/manage_discount_assignments/delete'); ?>',
                type: 'POST',
                data: {assignment_id: id},
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                    setTimeout(() => loadData(), 500);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            });
        },
        'Delete',
        'danger'
    );
}

function editAssignment(id) {
    loadModalContent('modal_ajax', '<?php echo site_url('admin/edit_student_discount_modal/'); ?>' + id, '<i class="fa fa-edit"></i> <?php echo get_phrase('edit_assignment'); ?>');
}

function bulkToggle() {
    const selected = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
    if(selected.length === 0) {
        showAjaxModal_alert('Please select at least one assignment', 'error');
        return;
    }
    
    showAjaxModal_alert('Processing...', 'loading');
    $.ajax({
        url: '<?php echo site_url('admin/manage_discount_assignments/bulk_toggle'); ?>',
        type: 'POST',
        data: {ids: selected},
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(() => loadData(), 500);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}
function openAssignModal() {
    loadModalContent('modal_ajax', '<?php echo site_url('admin/assign_student_discount_modal'); ?>', '<i class="fa fa-user-tag"></i> <?php echo get_phrase('assign_students_to_discount'); ?>');
}

function openBulkModal() {
    loadModalContent('modal_ajax', '<?php echo site_url('admin/bulk_assign_by_class_modal'); ?>', '<i class="fa fa-users"></i> <?php echo get_phrase('bulk_assign_by_class'); ?>');
}

function submitBulkAssign() {
    showConfirmModal(
        'Confirm Bulk Assignment',
        'Assign discount to all students in selected class/section?',
        function() {
            $('.close').click();
            showAjaxModal_alert('Processing...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/bulk_assign_by_class/assign'); ?>',
                type: 'POST',
                data: $('#bulkAssignForm').serialize(),
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => loadData(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            });
        },
        'Assign',
        'primary'
    );
}
</script>
