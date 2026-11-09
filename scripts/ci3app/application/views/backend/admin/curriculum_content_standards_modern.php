<?php
/**
 * GES Lesson Note System - Curriculum Content Standards Management (Modern UI)
 * Requirements: 8.3, 8.7
 */
?>

<style>
/* Reuse modern styles from sub-strands page */
.content-standards-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 24px;
}

.content-standards-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    color: #fff !important;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.content-standards-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #fff !important;
}

.content-standards-header p {
    font-size: 16px;
    margin: 0;
    opacity: 0.95;
    color: #fff !important;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
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
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-card.purple::before {
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card.blue::before {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.stat-card.green::before {
    background: linear-gradient(90deg, #10b981, #059669);
}

.stat-card.orange::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.stat-icon.purple {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.stat-icon.blue {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
}

.stat-icon.green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}

.stat-icon.orange {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.modern-filters {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter-row {
    display: flex;
    gap: 16px;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.filter-group select {
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    height: 44px;
}

.filter-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: #fff;
}

.modern-btn-info {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
}

.modern-btn-danger {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #fff;
}

.modern-btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.modern-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 24px;
}

.modern-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modern-card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modern-card-body {
    padding: 24px;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead th {
    background: #f9fafb;
    padding: 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}

.modern-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
    color: #4b5563;
    vertical-align: middle;
}

.modern-table tbody tr:hover {
    background: #f9fafb;
}

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

.code-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    background: #fef3c7;
    color: #92400e;
    font-family: 'Courier New', monospace;
}

.sub-strand-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    background: #eff6ff;
    color: #2563eb;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 12px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 600;
    background: #f0fdf4;
    color: #16a34a;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.modern-modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modern-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 24px 30px;
    border-radius: 16px 16px 0 0;
}

.modern-modal-header .modal-title {
    color: #fff;
    font-weight: 600;
}

.modern-close {
    color: white !important;
    opacity: 0.9 !important;
    font-size: 32px !important;
    font-weight: 300 !important;
    text-shadow: none !important;
    transition: all 0.3s ease !important;
}

.modern-close:hover {
    opacity: 1 !important;
    transform: rotate(90deg) !important;
}

.modern-input,
.modern-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
}

.modern-input:focus,
.modern-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 16px;
}

.empty-state-title {
    font-size: 20px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.empty-state-text {
    font-size: 15px;
    color: #6b7280;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .content-standards-header h1 {
        font-size: 24px;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="content-standards-container">
    <!-- Header -->
    <div class="content-standards-header">
        <h1>
            <i class="entypo-list-add"></i>
            <?php echo get_phrase('curriculum_content_standards'); ?>
        </h1>
        <p><?php echo get_phrase('manage_content_standards_under_sub_strands'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card purple">
            <div class="stat-icon purple">
                <i class="entypo-doc-text"></i>
            </div>
            <div class="stat-value"><?php echo count($content_standards); ?></div>
            <div class="stat-label"><?php echo get_phrase('total_content_standards'); ?></div>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon blue">
                <i class="entypo-flow-branch"></i>
            </div>
            <div class="stat-value"><?php echo count($sub_strands); ?></div>
            <div class="stat-label"><?php echo get_phrase('sub_strands'); ?></div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon green">
                <i class="entypo-light-bulb"></i>
            </div>
            <div class="stat-value"><?php echo array_sum(array_column($content_standards, 'learning_indicators_count')); ?></div>
            <div class="stat-label"><?php echo get_phrase('learning_indicators'); ?></div>
        </div>

        <div class="stat-card orange">
            <div class="stat-icon orange">
                <i class="entypo-graduation-cap"></i>
            </div>
            <div class="stat-value"><?php echo count(array_unique(array_column($sub_strands, 'strand_name'))); ?></div>
            <div class="stat-label"><?php echo get_phrase('parent_strands'); ?></div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="modern-filters">
        <form method="get" action="<?php echo site_url('admin/curriculum_content_standards'); ?>">
            <div class="filter-row">
                <div class="filter-group" style="flex: 2;">
                    <label><?php echo get_phrase('filter_by_sub_strand'); ?></label>
                    <select name="sub_strand_id" class="form-control" onchange="this.form.submit()">
                        <option value=""><?php echo get_phrase('all_sub_strands'); ?></option>
                        <?php foreach ($sub_strands as $sub_strand): ?>
                            <option value="<?php echo $sub_strand->id; ?>" <?php echo ($selected_sub_strand == $sub_strand->id) ? 'selected' : ''; ?>>
                                <?php echo $sub_strand->name . ' (' . $sub_strand->strand_name . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="button" class="modern-btn modern-btn-primary" onclick="showAddContentStandardModal()">
                        <i class="entypo-plus"></i>
                        <?php echo get_phrase('add_new_content_standard'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Content Standards Table -->
    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title">
                <i class="entypo-list"></i>
                <?php echo get_phrase('all_content_standards'); ?>
            </div>
        </div>
        <div class="modern-card-body">
            <?php if (!empty($content_standards)): ?>
                <table class="modern-table" id="content-standards-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('code'); ?></th>
                            <th><?php echo get_phrase('sub_strand'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th style="text-align: center;"><?php echo get_phrase('learning_indicators'); ?></th>
                            <th style="width: 60px; text-align: center;"><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($content_standards as $standard): ?>
                            <tr>
                                <td>
                                    <span class="code-badge"><?php echo $standard->code; ?></span>
                                </td>
                                <td>
                                    <span class="sub-strand-badge"><?php echo $standard->sub_strand_name; ?></span>
                                </td>
                                <td><?php echo substr($standard->description, 0, 100) . (strlen($standard->description) > 100 ? '...' : ''); ?></td>
                                <td style="text-align: center;">
                                    <span class="count-badge"><?php echo $standard->learning_indicators_count ?? 0; ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="dropdown">
                                        <button class="inline-flex items-center p-2.5 text-xl font-medium text-white bg-blue-600 rounded-full hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg" type="button" data-toggle="dropdown">
                                            <i class="entypo-dot-3"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right" style="font-size: 13px; min-width: 160px; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); border: none; padding: 6px;">
                                            <li>
                                                <a href="javascript:void(0);" onclick="showEditContentStandardModal(<?php echo $standard->content_standard_id; ?>)" style="color: #3c763d; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                    <i class="entypo-pencil" style="font-size: 14px;"></i>
                                                    <span><?php echo get_phrase('edit'); ?></span>
                                                </a>
                                            </li>
                                            <li class="divider" style="margin: 4px 0;"></li>
                                            <li>
                                                <a href="javascript:void(0);" onclick="confirmDeleteContentStandard(<?php echo $standard->content_standard_id; ?>)" style="color: #d9534f; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                    <i class="entypo-trash" style="font-size: 14px;"></i>
                                                    <span><?php echo get_phrase('delete'); ?></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="entypo-list-add"></i>
                    </div>
                    <div class="empty-state-title"><?php echo get_phrase('no_content_standards_yet'); ?></div>
                    <div class="empty-state-text"><?php echo get_phrase('click_add_button_to_create_first_content_standard'); ?></div>
                    <button class="modern-btn modern-btn-primary" onclick="showAddContentStandardModal()">
                        <i class="entypo-plus"></i>
                        <?php echo get_phrase('add_first_content_standard'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals loaded via showAjaxModal() from application/views/backend/modal.php -->

<script>
function showAddContentStandardModal() {
    var subStrandId = '<?php echo $selected_sub_strand; ?>';
    var url = '<?php echo site_url('admin/modal_content_standard_add'); ?>';
    if (subStrandId) {
        url += '?sub_strand_id=' + subStrandId;
    }
    showAjaxModal(url);
}

function showEditContentStandardModal(standardId) {
    showAjaxModal('<?php echo site_url('admin/modal_content_standard_edit/'); ?>' + standardId);
}

function confirmDeleteContentStandard(standardId) {
    showConfirmModal(
        '<?php echo get_phrase('delete_content_standard'); ?>',
        '<?php echo get_phrase('are_you_sure_delete_content_standard'); ?><br><small><?php echo get_phrase('this_will_also_delete_related_learning_indicators'); ?></small>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>...', 'Loading');
            
            $.ajax({
                url: '<?php echo site_url('admin/curriculum_content_standards/delete/'); ?>' + standardId,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAjaxModal_alert(response.message, 'Success', true);
                    } else {
                        showAjaxModal_alert(response.message, 'Error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('<?php echo get_phrase('failed_to_delete_content_standard'); ?>', 'Error');
                }
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}

$(document).ready(function() {
    <?php if (!empty($content_standards)): ?>
    $('#content-standards-table').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [4] }
        ]
    });
    <?php endif; ?>
});
</script>


<style>
/* Modern Dropdown Menu Hover Effects */
.dropdown-menu li a:hover {
    background-color: #f3f4f6 !important;
    transform: translateX(2px);
}

.dropdown-menu li a {
    transition: all 0.2s ease !important;
}

.dropdown-menu {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
