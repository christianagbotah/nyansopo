<?php
/**
 * GES Lesson Note System - Curriculum Strands Management (Modern UI)
 * Requirements: 8.1, 8.5
 */
?>

<style>
/* Modern Curriculum Management Styles */
.curriculum-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.curriculum-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    color: #fff !important;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.curriculum-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #fff !important;
}

.curriculum-header p {
    font-size: 16px;
    margin: 0;
    opacity: 0.95;
    color: #fff !important;
}

.curriculum-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.stat-card-icon.purple {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.stat-card-icon.blue {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
}

.stat-card-icon.green {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #fff;
}

.stat-card-icon.orange {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}

.stat-card-value {
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-card-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.curriculum-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.curriculum-card-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.curriculum-card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 12px;
}

.curriculum-card-body {
    padding: 24px;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    line-height: 1.5;
}

.modern-btn i {
    font-size: 16px;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.modern-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.modern-btn-success {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
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
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.modern-btn-sm i {
    font-size: 14px;
    margin: 0;
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
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.modern-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
    color: #4b5563;
}

.modern-table tbody tr:hover {
    background: #f9fafb;
}

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

.subject-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    background: #eff6ff;
    color: #2563eb;
}

.class-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    background: #f0fdf4;
    color: #16a34a;
}

.modern-btn-sm {
    padding: 6px 12px;
    font-size: 13px;
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.modern-btn-sm i {
    font-size: 14px;
    margin: 0;
}

.action-buttons {
    display: flex;
    gap: 6px;
    justify-content: flex-end;
}

/* Modal styles are in application/views/backend/modal.php */

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
    .curriculum-header h1 {
        font-size: 24px;
    }
    
    .curriculum-stats {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="curriculum-container">
    <!-- Header -->
    <div class="curriculum-header">
        <h1>
            <i class="entypo-book"></i>
            <?php echo get_phrase('curriculum_strands_management'); ?>
        </h1>
        <p><?php echo get_phrase('manage_curriculum_strands_for_ges_lesson_notes'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="curriculum-stats">
        <div class="stat-card">
            <div class="stat-card-icon purple">
                <i class="entypo-book-open"></i>
            </div>
            <div class="stat-card-value"><?php echo count($strands); ?></div>
            <div class="stat-card-label"><?php echo get_phrase('total_strands'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-icon blue">
                <i class="entypo-graduation-cap"></i>
            </div>
            <div class="stat-card-value"><?php echo count(array_unique(array_column($strands, 'subject_id'))); ?></div>
            <div class="stat-card-label"><?php echo get_phrase('subjects_covered'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-icon green">
                <i class="entypo-users"></i>
            </div>
            <div class="stat-card-value"><?php echo count(array_unique(array_column($strands, 'class_level'))); ?></div>
            <div class="stat-card-label"><?php echo get_phrase('class_levels'); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-icon orange">
                <i class="entypo-chart-line"></i>
            </div>
            <div class="stat-card-value"><?php echo count($sub_strands ?? []); ?></div>
            <div class="stat-card-label"><?php echo get_phrase('sub_strands'); ?></div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="curriculum-card">
        <div class="curriculum-card-header">
            <div class="curriculum-card-title">
                <i class="entypo-list"></i>
                <?php echo get_phrase('all_strands'); ?>
            </div>
            <button class="modern-btn modern-btn-primary" onclick="showAddStrandModal()">
                <i class="entypo-plus"></i>
                <?php echo get_phrase('add_new_strand'); ?>
            </button>
        </div>
        
        <div class="curriculum-card-body">
            <?php if (!empty($strands)): ?>
                <table class="modern-table" id="strands-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('subject'); ?></th>
                            <th><?php echo get_phrase('class_level'); ?></th>
                            <th><?php echo get_phrase('strand_name'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th style="width: 60px; text-align: center;"><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($strands as $strand): ?>
                            <tr>
                                <td>
                                    <span class="subject-badge"><?php echo $strand->subject_name; ?></span>
                                </td>
                                <td>
                                    <span class="class-badge"><?php echo $strand->class_name . ' ' . $strand->class_numeric . ' ' . $strand->section_name; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $strand->name; ?></strong>
                                </td>
                                <td><?php echo substr($strand->description, 0, 100) . (strlen($strand->description) > 100 ? '...' : ''); ?></td>
                                <td style="text-align: center;">
                                    <div class="dropdown">
                                        <button class="inline-flex items-center p-2.5 text-xl font-medium text-white bg-blue-600 rounded-full hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg" type="button" data-toggle="dropdown">
                                            <i class="entypo-dot-3"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right" style="font-size: 13px; min-width: 160px; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); border: none; padding: 6px;">
                                            <li>
                                                <a href="javascript:void(0);" onclick="showEditStrandModal(<?php echo $strand->strand_id; ?>)" style="color: #3c763d; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
                                                    <i class="entypo-pencil" style="font-size: 14px;"></i>
                                                    <span><?php echo get_phrase('edit'); ?></span>
                                                </a>
                                            </li>
                                            <li class="divider" style="margin: 4px 0;"></li>
                                            <li>
                                                <a href="javascript:void(0);" onclick="confirmDeleteStrand(<?php echo $strand->strand_id; ?>)" style="color: #d9534f; padding: 8px 12px; display: flex; align-items: center; gap: 8px; border-radius: 5px; transition: all 0.2s;">
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
                        <i class="entypo-book"></i>
                    </div>
                    <div class="empty-state-title"><?php echo get_phrase('no_strands_yet'); ?></div>
                    <div class="empty-state-text"><?php echo get_phrase('click_add_button_to_create_first_strand'); ?></div>
                    <button class="modern-btn modern-btn-primary" onclick="showAddStrandModal()">
                        <i class="entypo-plus"></i>
                        <?php echo get_phrase('add_first_strand'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal is loaded from application/views/backend/modal.php -->

<script>
var strandsData = <?php echo json_encode($strands); ?>;

function showAddStrandModal() {
    $('#strandModalTitle').text('<?php echo get_phrase('add_new_strand'); ?>');
    $('#strandForm').attr('action', '<?php echo site_url('admin/curriculum_strands/create'); ?>');
    $('#class_level').val('');
    $('#subject_id').html('<option value=""><?php echo get_phrase('select_class_first'); ?></option>');
    $('#strand_name').val('');
    $('#strand_description').val('');
    $('#strandModal').modal('show');
}

function showEditStrandModal(strandId) {
    var strand = strandsData.find(s => s.strand_id == strandId);
    if (!strand) {
        console.error('Strand not found:', strandId);
        showNotification('error', 'Strand not found');
        return;
    }

    console.log('Editing strand:', strand);

    $('#strandModalTitle').text('<?php echo get_phrase('edit_strand'); ?>');
    $('#strandForm').attr('action', '<?php echo site_url('admin/curriculum_strands/update/'); ?>' + strandId);
    
    // Set class first
    $('#class_level').val(strand.class_level);
    
    // Set strand name and description immediately
    $('#strand_name').val(strand.name);
    $('#strand_description').val(strand.description);
    
    // Load subjects for the class, then set the subject
    if (strand.class_level) {
        $.ajax({
            url: '<?php echo site_url('admin/get_subjects_by_class'); ?>',
            type: 'POST',
            data: { class_id: strand.class_level },
            dataType: 'json',
            success: function(response) {
                var subjectDropdown = $('#subject_id');
                subjectDropdown.html('<option value=""><?php echo get_phrase('select_subject'); ?></option>');
                
                if (response.success && response.subjects.length > 0) {
                    $.each(response.subjects, function(index, subject) {
                        subjectDropdown.append(
                            $('<option></option>')
                                .attr('value', subject.subject_id)
                                .text(subject.name)
                        );
                    });
                    // Set the selected subject
                    subjectDropdown.val(strand.subject_id);
                } else {
                    console.warn('No subjects found for class:', strand.class_level);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading subjects:', error);
                showNotification('error', 'Failed to load subjects');
            }
        });
    } else {
        console.warn('No class_level found in strand data');
        $('#subject_id').html('<option value=""><?php echo get_phrase('select_class_first'); ?></option>');
    }
    
    $('#strandModal').modal('show');
}

function confirmDeleteStrand(strandId) {
    if (confirm('<?php echo get_phrase('are_you_sure_delete_strand'); ?>')) {
        // Show loading in both modal and toast
        showAjaxModal_alert('<?php echo get_phrase('deleting'); ?>...', 'Loading');
        showNotification('info', '<?php echo get_phrase('deleting'); ?>...');
        
        $.ajax({
            url: '<?php echo site_url('admin/curriculum_strands/delete/'); ?>' + strandId,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success in both modal and toast
                    showAjaxModal_alert(response.message || '<?php echo get_phrase('strand_deleted_successfully'); ?>', 'Success', true);
                    showNotification('success', response.message || '<?php echo get_phrase('strand_deleted_successfully'); ?>');
                    
                    // Page will reload automatically from showAjaxModal_alert
                } else {
                    // Show error in both modal and toast
                    showAjaxModal_alert(response.message || '<?php echo get_phrase('failed_to_delete_strand'); ?>', 'Error');
                    showNotification('error', response.message || '<?php echo get_phrase('failed_to_delete_strand'); ?>');
                }
            },
            error: function(xhr, status, error) {
                // Show error in both modal and toast
                var errorMsg = '<?php echo get_phrase('error_occurred'); ?>: ' + error;
                showAjaxModal_alert(errorMsg, 'Error');
                showNotification('error', errorMsg);
            }
        });
    }
}

$(document).ready(function() {
    <?php if (!empty($strands)): ?>
    $('#strands-table').DataTable({
        "order": [[0, "asc"], [1, "asc"]],
        "pageLength": 25,
        "language": {
            "search": "Search strands:",
            "lengthMenu": "Show _MENU_ strands per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ strands",
            "infoEmpty": "No strands available",
            "infoFiltered": "(filtered from _MAX_ total strands)"
        }
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
