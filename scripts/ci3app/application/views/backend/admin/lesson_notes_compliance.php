<?php
/**
 * GES Lesson Note System - Lesson Notes Compliance Report (Modern UI)
 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5
 */
?>

<style>
/* Modern Compliance Report Styles */
.compliance-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 24px;
}

.compliance-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    color: #fff !important;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.compliance-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #fff !important;
}

.compliance-header p {
    font-size: 16px;
    margin: 0;
    opacity: 0.95;
    color: #fff !important;
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
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
    padding: 12px 14px;
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
}

.modern-btn-success {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #fff;
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

.stat-card.info::before {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.stat-card.success::before {
    background: linear-gradient(90deg, #10b981, #059669);
}

.stat-card.warning::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
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

.stat-icon.info {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
}

.stat-icon.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}

.stat-icon.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.success {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.danger {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.info {
    background: #dbeafe;
    color: #1e40af;
}

.progress-modern {
    height: 24px;
    background: #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.progress-bar-modern {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    transition: width 0.3s ease;
}

.progress-bar-modern.success {
    background: linear-gradient(90deg, #10b981, #059669);
}

.progress-bar-modern.warning {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.progress-bar-modern.danger {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.alert-modern {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.alert-modern.danger {
    background: #fee2e2;
    border-left: 4px solid #dc2626;
}

.alert-modern.warning {
    background: #fef3c7;
    border-left: 4px solid #d97706;
}

.alert-modern-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.alert-modern-content {
    flex: 1;
}

.alert-modern-title {
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 14px;
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

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .compliance-header h1 {
        font-size: 24px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="compliance-container">
    <!-- Header -->
    <div class="compliance-header">
        <h1>
            <i class="entypo-chart-bar"></i>
            <?php echo get_phrase('lesson_notes_compliance_report'); ?>
        </h1>
        <p><?php echo get_phrase('monitor_teacher_compliance_and_submission_rates'); ?></p>
    </div>

    <!-- Filters -->
    <div class="modern-filters">
        <form method="get" id="filterForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label><?php echo get_phrase('term'); ?></label>
                    <select name="term" class="form-control">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($filters['term'] == $i) ? 'selected' : ''; ?>>
                                <?php echo get_phrase('term') . ' ' . $i; ?>
                            </option>
                        <?php endfor; ?>
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
            </div>

            <div class="filter-actions">
                <button type="submit" class="modern-btn modern-btn-primary">
                    <i class="entypo-search"></i>
                    <?php echo get_phrase('filter'); ?>
                </button>
                
                <div class="btn-group">
                    <button type="button" class="modern-btn modern-btn-success dropdown-toggle" data-toggle="dropdown">
                        <i class="entypo-export"></i>
                        <?php echo get_phrase('export'); ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="<?php echo site_url('admin/lesson_notes_compliance_export/pdf?' . http_build_query($filters)); ?>"><i class="entypo-doc-text"></i> <?php echo get_phrase('export_pdf'); ?></a></li>
                        <li><a href="<?php echo site_url('admin/lesson_notes_compliance_export/excel?' . http_build_query($filters)); ?>"><i class="entypo-doc"></i> <?php echo get_phrase('export_excel'); ?></a></li>
                        <li><a href="<?php echo site_url('admin/lesson_notes_compliance_export/csv?' . http_build_query($filters)); ?>"><i class="entypo-database"></i> <?php echo get_phrase('export_csv'); ?></a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="entypo-docs"></i>
            </div>
            <div class="stat-value"><?php echo isset($submission_stats['total_expected']) ? $submission_stats['total_expected'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('expected_submissions'); ?></div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="entypo-check"></i>
            </div>
            <div class="stat-value"><?php echo isset($submission_stats['total_submitted']) ? $submission_stats['total_submitted'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('total_submitted'); ?></div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="entypo-gauge"></i>
            </div>
            <div class="stat-value"><?php echo isset($submission_stats['compliance_rate']) ? number_format($submission_stats['compliance_rate'], 1) : 0; ?>%</div>
            <div class="stat-label"><?php echo get_phrase('compliance_rate'); ?></div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon danger">
                <i class="entypo-attention"></i>
            </div>
            <div class="stat-value"><?php echo isset($submission_stats['missing_submissions']) ? $submission_stats['missing_submissions'] : 0; ?></div>
            <div class="stat-label"><?php echo get_phrase('missing_submissions'); ?></div>
        </div>
    </div>

    <!-- Main Content -->
    
    <!-- Compliance by Teacher -->
    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title">
                <i class="entypo-users"></i>
                <?php echo get_phrase('compliance_by_teacher'); ?>
            </div>
        </div>
        <div class="modern-card-body">
            <table class="modern-table" id="compliance-table">
                <thead>
                    <tr>
                        <th><?php echo get_phrase('teacher'); ?></th>
                        <th><?php echo get_phrase('subjects'); ?></th>
                        <th><?php echo get_phrase('expected'); ?></th>
                        <th><?php echo get_phrase('submitted'); ?></th>
                        <th><?php echo get_phrase('approved'); ?></th>
                        <th><?php echo get_phrase('declined'); ?></th>
                        <th><?php echo get_phrase('compliance_rate'); ?></th>
                        <th><?php echo get_phrase('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($compliance_data)): ?>
                        <?php foreach ($compliance_data as $data): ?>
                            <tr>
                                <td><strong><?php echo $data->teacher_name; ?></strong></td>
                                <td><?php echo $data->subjects_count; ?></td>
                                <td><?php echo $data->expected_notes; ?></td>
                                <td><?php echo $data->submitted_notes; ?></td>
                                <td><?php echo $data->approved_notes; ?></td>
                                <td><?php echo $data->declined_notes; ?></td>
                                <td>
                                    <div class="progress-modern">
                                        <div class="progress-bar-modern <?php echo ($data->compliance_rate >= 80) ? 'success' : (($data->compliance_rate >= 50) ? 'warning' : 'danger'); ?>" 
                                             style="width: <?php echo min($data->compliance_rate, 100); ?>%;">
                                            <?php echo number_format($data->compliance_rate, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($data->submitted_notes == 0): ?>
                                        <span class="status-badge danger"><?php echo get_phrase('no_submissions'); ?></span>
                                    <?php elseif ($data->compliance_rate < 50): ?>
                                        <span class="status-badge danger"><?php echo get_phrase('low_compliance'); ?></span>
                                    <?php elseif ($data->compliance_rate < 80): ?>
                                        <span class="status-badge warning"><?php echo get_phrase('moderate_compliance'); ?></span>
                                    <?php else: ?>
                                        <span class="status-badge success"><?php echo get_phrase('good_compliance'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 40px;"><?php echo get_phrase('no_compliance_data_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Teachers with Zero Submissions -->
    <?php if (!empty($zero_submission_teachers)): ?>
        <div class="alert-modern danger">
            <div class="alert-modern-icon">
                <i class="entypo-attention"></i>
            </div>
            <div class="alert-modern-content">
                <div class="alert-modern-title">
                    <?php echo get_phrase('teachers_with_zero_submissions'); ?>
                </div>
                <table class="modern-table" style="margin-top: 12px;">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('teacher'); ?></th>
                            <th><?php echo get_phrase('subjects'); ?></th>
                            <th><?php echo get_phrase('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zero_submission_teachers as $teacher): ?>
                            <tr>
                                <td><strong><?php echo $teacher->name; ?></strong></td>
                                <td><?php echo $teacher->subjects_count; ?></td>
                                <td>
                                    <a href="<?php echo site_url('admin/lesson_notes_pending?teacher_id=' . $teacher->teacher_id); ?>" 
                                       class="modern-btn modern-btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                        <?php echo get_phrase('view_details'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Teachers with High Decline Rates -->
    <?php if (!empty($high_decline_teachers)): ?>
        <div class="alert-modern warning">
            <div class="alert-modern-icon">
                <i class="entypo-attention"></i>
            </div>
            <div class="alert-modern-content">
                <div class="alert-modern-title">
                    <?php echo get_phrase('teachers_with_high_decline_rates'); ?>
                </div>
                <table class="modern-table" style="margin-top: 12px;">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('teacher'); ?></th>
                            <th><?php echo get_phrase('submitted'); ?></th>
                            <th><?php echo get_phrase('declined'); ?></th>
                            <th><?php echo get_phrase('decline_rate'); ?></th>
                            <th><?php echo get_phrase('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($high_decline_teachers as $teacher): ?>
                            <tr>
                                <td><strong><?php echo $teacher->name; ?></strong></td>
                                <td><?php echo $teacher->submitted_notes; ?></td>
                                <td><?php echo $teacher->declined_notes; ?></td>
                                <td><?php echo number_format($teacher->decline_rate, 1); ?>%</td>
                                <td>
                                    <a href="<?php echo site_url('admin/lesson_notes_pending?teacher_id=' . $teacher->teacher_id . '&status=declined'); ?>" 
                                       class="modern-btn modern-btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                        <?php echo get_phrase('view_declined_notes'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Compliance Trend Chart -->
<div class="modern-card">
    <div class="modern-card-header">
        <div class="modern-card-title">
            <i class="entypo-chart-line"></i>
            <?php echo get_phrase('compliance_trend'); ?>
        </div>
    </div>
    <div class="modern-card-body">
        <div id="compliance-trend-chart" style="height: 300px;"></div>
    </div>
</div>

<!-- Weekly Breakdown -->
<div class="modern-card">
    <div class="modern-card-header">
        <div class="modern-card-title">
            <i class="entypo-calendar"></i>
            <?php echo get_phrase('weekly_breakdown'); ?>
        </div>
    </div>
    <div class="modern-card-body">
        <table class="modern-table" id="weekly-breakdown-table">
            <thead>
                <tr>
                    <th><?php echo get_phrase('week'); ?></th>
                    <th><?php echo get_phrase('expected'); ?></th>
                    <th><?php echo get_phrase('submitted'); ?></th>
                    <th><?php echo get_phrase('approved'); ?></th>
                    <th><?php echo get_phrase('pending'); ?></th>
                    <th><?php echo get_phrase('declined'); ?></th>
                    <th><?php echo get_phrase('compliance_rate'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($weekly_breakdown)): ?>
                    <?php foreach ($weekly_breakdown as $week): ?>
                        <tr>
                            <td><strong><?php echo get_phrase('week') . ' ' . $week->week_number; ?></strong></td>
                            <td><?php echo $week->expected; ?></td>
                            <td><?php echo $week->submitted; ?></td>
                            <td><?php echo $week->approved; ?></td>
                            <td><?php echo $week->pending; ?></td>
                            <td><?php echo $week->declined; ?></td>
                            <td>
                                <div class="progress-modern">
                                    <div class="progress-bar-modern <?php echo ($week->compliance_rate >= 80) ? 'success' : (($week->compliance_rate >= 50) ? 'warning' : 'danger'); ?>" 
                                         style="width: <?php echo min($week->compliance_rate, 100); ?>%;">
                                        <?php echo number_format($week->compliance_rate, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 40px;"><?php echo get_phrase('no_weekly_data_found'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Drill-down Modal -->
<div class="modal fade" id="drillDownModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <h4 class="modal-title" id="drillDownModalTitle" style="color: #fff;"><?php echo get_phrase('teacher_details'); ?></h4>
                <button type="button" class="close modern-close" data-dismiss="modal" style="color: #fff; opacity: 1;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="drillDownModalBody">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn modern-btn-primary" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
$(document).ready(function() {
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

    $('#compliance-table').DataTable({
        "order": [[6, "desc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });

    $('#weekly-breakdown-table').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 12,
        "searching": false
    });

    // Initialize trend chart
    initTrendChart();

    // Drill-down click handlers
    $(document).on('click', '.drill-down-teacher', function(e) {
        e.preventDefault();
        var teacherId = $(this).data('teacher-id');
        var teacherName = $(this).data('teacher-name');
        loadTeacherDrillDown(teacherId, teacherName);
    });
});

function initTrendChart() {
    var ctx = document.getElementById('compliance-trend-chart').getContext('2d');
    
    var trendData = <?php echo json_encode($trend_data ?? []); ?>;
    var labels = [];
    var complianceRates = [];
    var submissionCounts = [];
    
    if (trendData && trendData.length > 0) {
        trendData.forEach(function(item) {
            labels.push('<?php echo get_phrase('term'); ?> ' + item.term);
            complianceRates.push(item.compliance_rate);
            submissionCounts.push(item.submitted);
        });
    } else {
        // Default empty data
        labels = ['<?php echo get_phrase('term'); ?> 1', '<?php echo get_phrase('term'); ?> 2', '<?php echo get_phrase('term'); ?> 3'];
        complianceRates = [0, 0, 0];
        submissionCounts = [0, 0, 0];
    }
    
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '<?php echo get_phrase('compliance_rate'); ?> (%)',
                    data: complianceRates,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: '<?php echo get_phrase('submissions'); ?>',
                    data: submissionCounts,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: '<?php echo get_phrase('compliance_rate'); ?> (%)'
                    },
                    min: 0,
                    max: 100
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: '<?php echo get_phrase('submissions'); ?>'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

function loadTeacherDrillDown(teacherId, teacherName) {
    $('#drillDownModalTitle').text('<?php echo get_phrase('teacher_details'); ?>: ' + teacherName);
    $('#drillDownModalBody').html('<div class="text-center"><i class="entypo-spin entypo-cog"></i> <?php echo get_phrase('loading'); ?>...</div>');
    $('#drillDownModal').modal('show');
    
    $.ajax({
        url: '<?php echo site_url('admin/lesson_notes_compliance_drilldown'); ?>',
        type: 'GET',
        data: { teacher_id: teacherId },
        success: function(response) {
            $('#drillDownModalBody').html(response);
        },
        error: function() {
            $('#drillDownModalBody').html('<div class="alert alert-danger"><?php echo get_phrase('error_loading_details'); ?></div>');
        }
    });
}
</script>
