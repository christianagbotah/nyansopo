<?php
/**
 * GES Lesson Note System - Pending Lesson Notes for Approval (Modern UI)
 * Requirements: 9.2, 9.8, 9.9
 */
?>

<style>
/* Modern Pending Approval Styles */
.pending-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 24px;
}

.pending-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    color: #fff !important;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.pending-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #fff !important;
}

.pending-header p {
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

.stat-card.warning::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.stat-card.info::before {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.stat-card.success::before {
    background: linear-gradient(90deg, #10b981, #059669);
}

.stat-card.danger::before {
    background: linear-gradient(90deg, #ef4444, #dc2626);
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

.stat-icon.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}

.stat-icon.info {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
}

.stat-icon.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}

.stat-icon.danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}

@media (max-width: 1200px) {
    .filter-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
}

.filter-group {
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

.filter-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
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

.modern-btn-success {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #fff;
}

.modern-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    color: #fff;
}

.modern-btn-danger {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #fff;
}

.modern-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    color: #fff;
}

.modern-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
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

.bulk-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.selected-count {
    font-size: 14px;
    font-weight: 600;
    color: #667eea;
    margin-left: auto;
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

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.hod-reviewed {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.approved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.declined {
    background: #fee2e2;
    color: #991b1b;
}

.modern-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
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

.modern-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
}

.modern-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

@media (max-width: 768px) {
    .pending-header h1 {
        font-size: 24px;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .bulk-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .selected-count {
        margin-left: 0;
        text-align: center;
    }
}
</style>

<div class="pending-container">
    <!-- Header -->
    <div class="pending-header">
        <h1>
            <i class="entypo-doc-text"></i>
            <?php echo get_phrase('lesson_notes_approval'); ?>
        </h1>
        <p><?php echo get_phrase('review_and_approve_teacher_lesson_notes'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="entypo-clock"></i>
            </div>
            <div class="stat-value"><?php echo isset($counts['pending']) ? $counts['pending'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('pending_review'); ?></div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="entypo-eye"></i>
            </div>
            <div class="stat-value"><?php echo isset($counts['hod_reviewed']) ? $counts['hod_reviewed'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('hod_reviewed'); ?></div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="entypo-check"></i>
            </div>
            <div class="stat-value"><?php echo isset($counts['approved']) ? $counts['approved'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('approved'); ?></div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon danger">
                <i class="entypo-cancel"></i>
            </div>
            <div class="stat-value"><?php echo isset($counts['declined']) ? $counts['declined'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('declined'); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="modern-filters">
        <form method="get" action="<?php echo site_url('admin/lesson_notes_pending'); ?>" id="filterForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label><?php echo get_phrase('status'); ?></label>
                    <select name="status" class="form-control">
                        <option value=""><?php echo get_phrase('all'); ?></option>
                        <option value="pending" <?php echo ($filters['status'] == 'pending') ? 'selected' : ''; ?>><?php echo get_phrase('pending'); ?></option>
                        <option value="hod_reviewed" <?php echo ($filters['status'] == 'hod_reviewed') ? 'selected' : ''; ?>><?php echo get_phrase('hod_reviewed'); ?></option>
                        <option value="approved" <?php echo ($filters['status'] == 'approved') ? 'selected' : ''; ?>><?php echo get_phrase('approved'); ?></option>
                        <option value="declined" <?php echo ($filters['status'] == 'declined') ? 'selected' : ''; ?>><?php echo get_phrase('declined'); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><?php echo get_phrase('teacher'); ?></label>
                    <select name="teacher_id" class="form-control">
                        <option value=""><?php echo get_phrase('all'); ?></option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher->teacher_id; ?>" <?php echo ($filters['teacher_id'] == $teacher->teacher_id) ? 'selected' : ''; ?>>
                                <?php echo $teacher->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><?php echo get_phrase('class'); ?></label>
                    <select name="class_id" id="filter_class_id" class="form-control">
                        <option value=""><?php echo get_phrase('all'); ?></option>
                        <?php getFullClassList('', $filters['class_id']); ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><?php echo get_phrase('subject'); ?></label>
                    <select name="subject_id" id="filter_subject_id" class="form-control">
                        <option value=""><?php echo get_phrase('all'); ?></option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject->subject_id; ?>" <?php echo ($filters['subject_id'] == $subject->subject_id) ? 'selected' : ''; ?>>
                                <?php echo $subject->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><?php echo get_phrase('week'); ?></label>
                    <select name="week_number" class="form-control">
                        <option value=""><?php echo get_phrase('all'); ?></option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($filters['week_number'] == $i) ? 'selected' : ''; ?>>
                                <?php echo get_phrase('week') . ' ' . $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="filter-actions">
                <button type="submit" class="modern-btn modern-btn-primary">
                    <i class="entypo-search"></i>
                    <?php echo get_phrase('filter'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Lesson Notes Table -->
    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title">
                <i class="entypo-list"></i>
                <?php echo get_phrase('lesson_notes_list'); ?>
            </div>
        </div>
        <div class="modern-card-body">
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <button class="modern-btn modern-btn-success" onclick="bulkApprove()" id="bulk-approve-btn" disabled>
                    <i class="entypo-check"></i> <?php echo get_phrase('bulk_approve'); ?>
                </button>
                <button class="modern-btn modern-btn-danger" onclick="bulkDecline()" id="bulk-decline-btn" disabled>
                    <i class="entypo-cancel"></i> <?php echo get_phrase('bulk_decline'); ?>
                </button>
                <span class="selected-count" id="selected-count">0 <?php echo get_phrase('selected'); ?></span>
            </div>

            <table class="modern-table" id="lesson-notes-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="select-all" class="modern-checkbox"></th>
                        <th><?php echo get_phrase('teacher'); ?></th>
                        <th><?php echo get_phrase('subject'); ?></th>
                        <th><?php echo get_phrase('class'); ?></th>
                        <th><?php echo get_phrase('title'); ?></th>
                        <th><?php echo get_phrase('week'); ?></th>
                        <th><?php echo get_phrase('term'); ?></th>
                        <th><?php echo get_phrase('status'); ?></th>
                        <th><?php echo get_phrase('submitted_date'); ?></th>
                        <th><?php echo get_phrase('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lesson_notes)): ?>
                        <?php foreach ($lesson_notes as $note): ?>
                            <tr>
                                <td><input type="checkbox" class="lesson-note-checkbox modern-checkbox" value="<?php echo $note->lesson_note_id; ?>"></td>
                                <td><strong><?php echo $note->teacher_name; ?></strong></td>
                                <td><?php echo $note->subject_name; ?></td>
                                <td><?php echo $note->class_name; ?></td>
                                <td><?php echo $note->title; ?></td>
                                <td><?php echo $note->week_number; ?></td>
                                <td><?php echo $note->term; ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($note->status) {
                                        case 'pending':
                                            $status_class = 'pending';
                                            break;
                                        case 'hod_reviewed':
                                            $status_class = 'hod-reviewed';
                                            break;
                                        case 'approved':
                                            $status_class = 'approved';
                                            break;
                                        case 'declined':
                                            $status_class = 'declined';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $note->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($note->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo site_url('admin/lesson_note_review/' . $note->lesson_note_id); ?>" class="modern-btn modern-btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                        <i class="entypo-eye"></i> <?php echo get_phrase('review'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 40px;"><?php echo get_phrase('no_lesson_notes_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Decline Modal -->
<div class="modal fade" id="bulkDeclineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <h4 class="modal-title"><?php echo get_phrase('bulk_decline_lesson_notes'); ?></h4>
                <button type="button" class="close modern-close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 8px;"><?php echo get_phrase('feedback'); ?> *</label>
                    <textarea id="bulk-decline-feedback" class="modern-textarea" rows="4" required placeholder="<?php echo get_phrase('enter_feedback_for_teachers'); ?>"></textarea>
                    <p class="help-block" style="margin-top: 8px; font-size: 13px; color: #6b7280;"><?php echo get_phrase('this_feedback_will_be_sent_to_all_selected_teachers'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn" style="background: #e5e7eb; color: #374151;" data-dismiss="modal"><?php echo get_phrase('cancel'); ?></button>
                <button type="button" class="modern-btn modern-btn-danger" onclick="confirmBulkDecline()"><?php echo get_phrase('decline_selected'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#lesson-notes-table').DataTable({
        "order": [[8, "desc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [0, 9] }
        ]
    });

    // Select all checkbox
    $('#select-all').on('change', function() {
        $('.lesson-note-checkbox').prop('checked', this.checked);
        updateBulkButtons();
    });

    // Individual checkbox
    $(document).on('change', '.lesson-note-checkbox', function() {
        updateBulkButtons();
    });

    // Dynamic subject loading based on class selection
    $('#filter_class_id').on('change', function() {
        var classId = $(this).val();
        var subjectDropdown = $('#filter_subject_id');
        
        if (classId) {
            // Show loading state
            subjectDropdown.html('<option value=""><?php echo get_phrase('loading'); ?>...</option>');
            subjectDropdown.prop('disabled', true);
            
            // Fetch subjects for selected class
            $.ajax({
                url: '<?php echo site_url('admin/get_subjects_by_class'); ?>',
                type: 'POST',
                data: { class_id: classId },
                dataType: 'json',
                success: function(response) {
                    subjectDropdown.html('<option value=""><?php echo get_phrase('all'); ?></option>');
                    
                    if (response.success && response.subjects.length > 0) {
                        $.each(response.subjects, function(index, subject) {
                            subjectDropdown.append(
                                $('<option></option>')
                                    .attr('value', subject.subject_id)
                                    .text(subject.name)
                            );
                        });
                    } else {
                        subjectDropdown.append('<option value=""><?php echo get_phrase('no_subjects_for_class'); ?></option>');
                    }
                    
                    subjectDropdown.prop('disabled', false);
                },
                error: function() {
                    subjectDropdown.html('<option value=""><?php echo get_phrase('error_loading_subjects'); ?></option>');
                    subjectDropdown.prop('disabled', false);
                }
            });
        } else {
            // Reset to show all subjects
            subjectDropdown.html('<option value=""><?php echo get_phrase('all'); ?></option>');
            <?php foreach ($subjects as $subject): ?>
                subjectDropdown.append('<option value="<?php echo $subject->subject_id; ?>"><?php echo addslashes($subject->name); ?></option>');
            <?php endforeach; ?>
            subjectDropdown.prop('disabled', false);
        }
    });

    updateBulkButtons();
});

function updateBulkButtons() {
    var selectedCount = $('.lesson-note-checkbox:checked').length;
    $('#selected-count').text(selectedCount + ' <?php echo get_phrase('selected'); ?>');
    
    if (selectedCount > 0) {
        $('#bulk-approve-btn').prop('disabled', false);
        $('#bulk-decline-btn').prop('disabled', false);
    } else {
        $('#bulk-approve-btn').prop('disabled', true);
        $('#bulk-decline-btn').prop('disabled', true);
    }
}

function bulkApprove() {
    var selectedIds = [];
    $('.lesson-note-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_lesson_notes'); ?>', 'Warning', false, true);
        return;
    }

    if (selectedIds.length > 50) {
        showAjaxModal_alert('<?php echo get_phrase('maximum_50_records_allowed'); ?>', 'Warning', false, true);
        return;
    }

    showCustomConfirm(
        '<?php echo get_phrase('are_you_sure_approve_selected'); ?>',
        function() {
            // On Yes - proceed with bulk approval
            $.ajax({
                url: '<?php echo site_url('admin/lesson_notes_bulk_approve'); ?>',
                type: 'POST',
                data: { lesson_note_ids: selectedIds },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAjaxModal_alert(response.message, 'Success', true, true);
                    } else {
                        showAjaxModal_alert(response.message, 'Error', false, true);
                    }
                },
                error: function() {
                    showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'Error', false, true);
                }
            });
        }
        // On No - do nothing (modal closes automatically)
    );
}

function bulkDecline() {
    var selectedIds = [];
    $('.lesson-note-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_lesson_notes'); ?>', 'Warning', false, true);
        return;
    }

    if (selectedIds.length > 50) {
        showAjaxModal_alert('<?php echo get_phrase('maximum_50_records_allowed'); ?>', 'Warning', false, true);
        return;
    }

    $('#bulkDeclineModal').modal('show');
}

function confirmBulkDecline() {
    var feedback = $('#bulk-decline-feedback').val().trim();
    
    if (feedback === '') {
        showAjaxModal_alert('<?php echo get_phrase('feedback_required'); ?>', 'Warning', false, true);
        return;
    }

    var selectedIds = [];
    $('.lesson-note-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });

    $.ajax({
        url: '<?php echo site_url('admin/lesson_notes_bulk_decline'); ?>',
        type: 'POST',
        data: { 
            lesson_note_ids: selectedIds,
            feedback: feedback
        },
        dataType: 'json',
        success: function(response) {
            $('#bulkDeclineModal').modal('hide');
            if (response.status === 'success') {
                showAjaxModal_alert(response.message, 'Success', true, true);
            } else {
                showAjaxModal_alert(response.message, 'Error', false, true);
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'Error', false, true);
        }
    });
}
</script>
