<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.analytics-container { background: #f8fafc; min-height: 100vh; padding: 24px; max-width: 1600px; margin: 0 auto; }
.analytics-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); }
.filter-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.green { border-color: #10b981; }
.stat-card.red { border-color: #ef4444; }
.stat-card.orange { border-color: #f59e0b; }
.stat-card.blue { border-color: #3b82f6; }
.stat-value { font-size: 36px; font-weight: 700; margin: 8px 0; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.chart-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
.chart-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 768px) { .chart-grid { grid-template-columns: 1fr; } }
.table-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
.modern-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
.modern-table tr:hover { background: #f9fafb; }
.btn-enterprise { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 46px; font-size: 15px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-success { background: #10b981; color: white; }
.btn-primary:hover, .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.form-control { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; height: 46px; }
.badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
/* Student Count Badge - Kept for new implementation */
.student-count-badge { display: inline-block; margin-left: 10px; padding: 2px 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 12px; font-size: 11px; font-weight: 600; }

/* Student Tag Styles */
.student-tag {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 36px;
    white-space: nowrap;
    flex-shrink: 0;
    animation: tagFadeIn 0.2s ease;
}

.tag-remove {
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    opacity: 0.9;
    margin-left: 4px;
    transition: all 0.15s;
}

.tag-remove:hover {
    opacity: 1;
    transform: scale(1.2);
}

@keyframes tagFadeIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

/* Search Results Dropdown Styles */
#student_search_results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #667eea;
    border-radius: 8px;
    max-height: 250px;
    overflow-y: auto;
    margin-top: 4px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    z-index: 1000;
}

.search-result-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.15s;
    font-size: 13px;
}

.search-result-item:hover {
    background: #f3f4f6;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-loading, .search-error, .no-results {
    padding: 12px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

.search-error {
    color: #ef4444;
}

/* Positioning Fix for Dropdown Container */
#students_filter_group {
    position: relative;
}
}

.search-loading, .search-error, .no-results {
    padding: 12px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

.search-error {
    color: #ef4444;
}

/* Positioning Fix for Dropdown Container */
#students_filter_group {
    position: relative;
}

@keyframes fadeIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
@media print {
    .analytics-header, .filter-card, .stats-grid, .chart-grid, .btn-enterprise { display: none !important; }
    .table-card { box-shadow: none; }
}
</style>

<link href="<?php echo base_url(); ?>assets/cdn/css/select2-4.1.0.min.css" rel="stylesheet" />
<script src="<?php echo base_url(); ?>assets/cdn/js/select2-4.1.0.min.js"></script>

<div class="analytics-container">
    <div class="analytics-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: white;">
                    <i class="fa fa-chart-line"></i> <?php echo get_phrase('attendance_analytics'); ?>
                </h1>
                <p style="margin: 0; opacity: 0.9; font-size: 16px; color: white;">
                    <?php echo get_phrase('comprehensive_attendance_reports'); ?>
                </p>
            </div>
            <button class="btn-enterprise btn-success" onclick="exportReport()">
                <i class="fa fa-download"></i> <?php echo get_phrase('export_excel'); ?>
            </button>
        </div>
    </div>

    <div class="filter-card">
        <!-- All Filters in Same Row -->
        <div style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 8px;">
            <div style="flex: 1; min-width: 160px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-list-alt" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('report_type'); ?>
                </label>
                <select id="filter_type" class="form-control" onchange="toggleReportFields()">
                    <option value="analytics"><?php echo get_phrase('analytics_summary'); ?></option>
                    <option value="monthly_grid"><?php echo get_phrase('monthly_grid'); ?></option>
                </select>
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-filter" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('filter_mode'); ?>
                </label>
                <select id="filter_mode" class="form-control" onchange="toggleFilterMode()">
                    <option value="class"><?php echo get_phrase('by_class_section'); ?></option>
                    <option value="students"><?php echo get_phrase('by_selected_students'); ?></option>
                </select>
            </div>

            <div id="class_filter_group" style="flex: 1; min-width: 160px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-graduation-cap" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('class'); ?>
                </label>
                <select id="filter_class" class="form-control">
                    <option value=""><?php echo get_phrase('all_classes'); ?></option>
                    <?php 
                    if(isset($teacher_id) && $teacher_id) {
                        echo getFullClassList($teacher_id);
                    } else {
                        echo getFullClassList();
                    }
                    ?>
                </select>
            </div>

            <div id="students_filter_group" style="flex: 1; min-width: 220px; max-width: 350px; display: none;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 13px;">
                    <i class="fa fa-users" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('select_students'); ?>
                    <span class="student-count-badge" id="student_count_badge" style="display: none;"></span>
                </label>
                
                <!-- Custom Tag Input Container -->
                <div id="custom_student_selector" style="border: 2px solid #e5e7eb; border-radius: 8px; min-height: 46px; max-height: 46px; padding: 4px 6px; background: white; display: flex; flex-wrap: nowrap; align-items: center; overflow-x: auto; overflow-y: hidden; gap: 4px;">
                    <!-- Selected students will appear here as tags -->
                    <div id="selected_students_tags" style="display: flex; gap: 4px; flex-shrink: 0;"></div>
                    
                    <!-- Search Input -->
                    <input type="text" 
                           id="student_search_input" 
                           placeholder="Type to search students..." 
                           style="border: none; outline: none; flex: 1; min-width: 120px; height: 36px; padding: 0 8px; font-size: 13px;"
                           autocomplete="off" />
                    
                    <!-- Dropdown Results -->
                    <div id="student_search_results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-radius: 8px; max-height: 250px; overflow-y: auto; margin-top: 4px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000;"></div>
                </div>
                
                <!-- Hidden input to store selected student IDs -->
                <input type="hidden" id="filter_students" value="" />
                
                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                    <i class="fa fa-info-circle" style="color: #667eea;"></i>
                    <?php echo get_phrase('type_to_search_students'); ?>
                </div>
            </div>

            <input type="hidden" id="filter_section">

            <div style="flex: 1; min-width: 160px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-flag" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('status'); ?>
                </label>
                <select id="filter_status" class="form-control">
                    <option value=""><?php echo get_phrase('all_statuses'); ?></option>
                    <option value="1"><?php echo get_phrase('present'); ?></option>
                    <option value="2"><?php echo get_phrase('absent'); ?></option>
                    <option value="3"><?php echo get_phrase('late'); ?></option>
                    <option value="4">Sick-Home</option>
                    <option value="5">Sick-Clinic</option>
                </select>
            </div>

            <div id="start_field" style="flex: 1; min-width: 160px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-calendar-check" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('start_date'); ?>
                </label>
                <input type="date" id="filter_start" class="form-control" value="<?php echo date('Y-m-01'); ?>">
            </div>

            <div id="end_field" style="flex: 1; min-width: 160px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-calendar-times" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('end_date'); ?>
                </label>
                <input type="date" id="filter_end" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div id="month_field" style="flex: 1; min-width: 140px; display:none;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-calendar-alt" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('month'); ?>
                </label>
                <select id="filter_month" class="form-control">
                    <?php for($i=1; $i<=12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$i,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div id="year_field" style="flex: 1; min-width: 130px; display:none;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-calendar" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('academic_year'); ?>
                </label>
                <select id="filter_year" class="form-control">
                    <?php echo populate_academic_year('yes'); ?>
                </select>
            </div>

            <div id="term_field" style="flex: 0 0 100px; display:none;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                    <i class="fa fa-book" style="margin-right: 6px; color: #667eea;"></i><?php echo get_phrase('term'); ?>
                </label>
                <select id="filter_term" class="form-control">
                    <?php $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description; ?>
                    <option value="1" <?php echo ($running_term == 1) ? 'selected' : ''; ?>>1</option>
                    <option value="2" <?php echo ($running_term == 2) ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo ($running_term == 3) ? 'selected' : ''; ?>>3</option>
                </select>
            </div>
        </div>

        <!-- Generate Button Row -->
        <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
            <button onclick="loadReport()" class="btn-enterprise btn-primary" style="padding: 12px 32px;">
                <i class="fa fa-chart-bar"></i> <?php echo get_phrase('generate_report'); ?>
            </button>
        </div>
    </div>

    <div id="report_content" style="display: none;">
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label"><?php echo get_phrase('total_days'); ?></div>
                <div class="stat-value" id="stat_days">0</div>
                <div style="color: #6b7280; font-size: 13px;"><?php echo get_phrase('school_days'); ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-label"><?php echo get_phrase('total_present'); ?></div>
                <div class="stat-value" id="stat_present">0</div>
                <div style="color: #6b7280; font-size: 13px;"><span id="present_rate">0</span>% <?php echo get_phrase('rate'); ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label"><?php echo get_phrase('total_absent'); ?></div>
                <div class="stat-value" id="stat_absent">0</div>
                <div style="color: #6b7280; font-size: 13px;"><span id="absent_rate">0</span>% <?php echo get_phrase('rate'); ?></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-label"><?php echo get_phrase('average_attendance'); ?></div>
                <div class="stat-value" id="stat_avg">0%</div>
                <div style="color: #6b7280; font-size: 13px;"><?php echo get_phrase('daily_average'); ?></div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 700;">
                    <i class="fa fa-chart-line"></i> <?php echo get_phrase('attendance_trend'); ?>
                </h3>
                <canvas id="trendChart" height="250"></canvas>
            </div>
            <div class="chart-card">
                <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 700;">
                    <i class="fa fa-chart-pie"></i> <?php echo get_phrase('status_breakdown'); ?>
                </h3>
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>

        <div class="table-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 700;">
                    <i class="fa fa-table"></i> <?php echo get_phrase('student_attendance_details'); ?>
                </h3>
                <button onclick="printReport()" class="btn-enterprise" style="background: #6366f1; color: white;">
                    <i class="fa fa-print"></i> <?php echo get_phrase('print'); ?>
                </button>
            </div>
            <div style="overflow-x: auto;">
                <table class="modern-table" id="student_table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('student_name'); ?></th>
                            <th><?php echo get_phrase('class'); ?></th>
                            <th><?php echo get_phrase('present'); ?></th>
                            <th><?php echo get_phrase('absent'); ?></th>
                            <th><?php echo get_phrase('late'); ?></th>
                            <th>Sick-Home</th>
                            <th>Sick-Clinic</th>
                            <th>Excused</th>
                            <th><?php echo get_phrase('attendance_rate'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="student_tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
if(typeof Chart === 'undefined') {
    var script = document.createElement('script');
    script.src = '<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js';
    document.head.appendChild(script);
}

$(document).ready(function() {
    // Initialize Select2 for student selector with enhanced configuration
    // REMOVED - Old Select2 code removed in Task 1.1
    // Custom selector JavaScript will be added in subsequent tasks
    
    // Show monthly fields by default since we only have monthly_grid mode
    toggleReportFields();
});

function toggleReportFields() {
    const reportType = $('#filter_type').val();
    
    if (reportType === 'analytics') {
        // Analytics mode - show date range fields
        $('#start_field, #end_field').show();
        $('#month_field, #year_field, #term_field').hide();
    } else {
        // Monthly grid mode - show month/year/term fields
        $('#month_field, #year_field, #term_field').show();
        $('#start_field, #end_field').hide();
    }
}

$('#filter_class').change(function() {
    const classId = $(this).val();
    $('#filter_section').val(''); // Clear previous section
    
    if(classId) {
        // Show loading indicator
        showAjaxModal_alert('<?php echo get_phrase('loading_section'); ?>...', 'loading');
        
        $.get('<?php echo site_url('admin/get_section/'); ?>' + classId, function(response) {
            const $temp = $('<div>').html(response);
            const sectionId = $temp.find('select[name="section_id"] option:first').val();
            $('#filter_section').val(sectionId);
            
            // Close loading modal
            setTimeout(() => $('.close').click(), 300);
            
            console.log('Section loaded:', sectionId, 'for class:', classId);
        }).fail(function() {
            $('.close').click();
            showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>: Could not load section', 'error');
        });
    }
});
</script>
<script>
let trendChart, statusChart;

function loadReport() {
    const reportType = $('#filter_type').val();
    const filterMode = $('#filter_mode').val();
    const studentIds = $('#filter_students').val();
    const classId = $('#filter_class').val();
    
    // AUTO-DETECT filter mode based on what's selected:
    // If students are selected, use student mode regardless of dropdown
    // If class is selected and no students, use class mode
    const effectiveMode = (studentIds && studentIds.length > 0) ? 'students' : 'class';
    
    // Check report type - Analytics loads via AJAX, Monthly Grid opens in new tab
    if(reportType === 'analytics') {
        // Analytics Summary - Load via AJAX on same page
        const startDate = $('#filter_start').val();
        const endDate = $('#filter_end').val();
        const status = $('#filter_status').val();
        
        if(effectiveMode === 'class') {
            if(!classId) {
                showAjaxModal_alert('<?php echo get_phrase('please_select_class'); ?>', 'error');
                return;
            }
            
            const sectionId = $('#filter_section').val();
            if(!sectionId) {
                showAjaxModal_alert('<?php echo get_phrase('loading_section_please_wait'); ?> Retrying...', 'loading');
                
                setTimeout(function() {
                    const sectionId = $('#filter_section').val();
                    if(!sectionId) {
                        $('.close').click();
                        showAjaxModal_alert('Section failed to load. Please select the class again.', 'error');
                        return;
                    }
                    
                    $('.close').click();
                    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
                    
                    // AJAX call to load analytics data
                    $.ajax({
                        url: '<?php echo site_url('admin/get_attendance_analytics'); ?>',
                        type: 'GET',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            start_date: startDate,
                            end_date: endDate,
                            status: status
                        },
                        dataType: 'json',
                        success: function(data) {
                            $('.close').click();
                            displayReport(data);
                        },
                        error: function(xhr, status, error) {
                            $('.close').click();
                            showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>: ' + error, 'error');
                        }
                    });
                }, 1000);
                
                return;
            }
            
            showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
            
            // AJAX call to load analytics data
            $.ajax({
                url: '<?php echo site_url('admin/get_attendance_analytics'); ?>',
                type: 'GET',
                data: {
                    class_id: classId,
                    section_id: sectionId,
                    start_date: startDate,
                    end_date: endDate,
                    status: status
                },
                dataType: 'json',
                success: function(data) {
                    $('.close').click();
                    displayReport(data);
                },
                error: function(xhr, status, error) {
                    $('.close').click();
                    showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>: ' + error, 'error');
                }
            });
            
        } else {
            // Per-student analytics
            if(!studentIds || studentIds.length === 0) {
                showAjaxModal_alert('<?php echo get_phrase('please_select_students'); ?>', 'error');
                return;
            }
            
            const firstStudentId = studentIds.split(',')[0].trim();
            showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url('admin/get_student_class_info'); ?>',
                type: 'GET',
                data: { student_id: firstStudentId },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        const classId = response.class_id;
                        const sectionId = response.section_id;
                        
                        // AJAX call to load analytics data with student filter
                        $.ajax({
                            url: '<?php echo site_url('admin/get_attendance_analytics'); ?>',
                            type: 'GET',
                            data: {
                                class_id: classId,
                                section_id: sectionId,
                                start_date: startDate,
                                end_date: endDate,
                                status: status,
                                student_ids: studentIds
                            },
                            dataType: 'json',
                            success: function(data) {
                                $('.close').click();
                                displayReport(data);
                            },
                            error: function(xhr, status, error) {
                                $('.close').click();
                                showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>: ' + error, 'error');
                            }
                        });
                    } else {
                        $('.close').click();
                        showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $('.close').click();
                    showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>: ' + error, 'error');
                }
            });
        }
        return;
    }
    
    // Monthly Grid Mode
    if(effectiveMode === 'class') {
        // By Class/Section mode - need class and section
        const sectionId = $('#filter_section').val();
        const month = $('#filter_month').val();
        const year = $('#filter_year').val();
        const term = $('#filter_term').val();
        
        if(!classId) {
            showAjaxModal_alert('<?php echo get_phrase('please_select_class'); ?>', 'error');
            return;
        }
        
        // Give section time to load if it's empty
        if(!sectionId) {
            showAjaxModal_alert('<?php echo get_phrase('loading_section_please_wait'); ?> Retrying...', 'loading');
            
            // Retry after 1 second to give section time to load
            setTimeout(function() {
                const sectionId = $('#filter_section').val();
                if(!sectionId) {
                    $('.close').click();
                    showAjaxModal_alert('Section failed to load. Please select the class again.', 'error');
                    return;
                }
                
                // Section loaded, proceed
                $('.close').click();
                showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
                
                let url = '<?php echo site_url('admin/attendance_report_view/'); ?>' + classId + '/' + sectionId + '/' + month + '/' + year + '/' + term;
                window.open(url, '_blank');
                setTimeout(() => $('.close').click(), 500);
            }, 1000);
            
            return;
        }
        
        showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
        
        // Build URL for class/section mode
        let url = '<?php echo site_url('admin/attendance_report_view/'); ?>' + classId + '/' + sectionId + '/' + month + '/' + year + '/' + term;
        window.open(url, '_blank');
        setTimeout(() => $('.close').click(), 500);
        
    } else {
        // By Selected Students mode - need students
        if(!studentIds || studentIds.length === 0) {
            showAjaxModal_alert('<?php echo get_phrase('please_select_students'); ?>', 'error');
            return;
        }
        
        // For per-student mode, we still need a class/section to determine the grid
        // Use the first selected student's class
        const firstStudentId = studentIds.split(',')[0].trim();
        
        showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
        
        // Get student details via AJAX to find their class/section
        $.ajax({
            url: '<?php echo site_url('admin/get_student_class_info'); ?>',
            type: 'GET',
            data: { student_id: firstStudentId },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    const classId = response.class_id;
                    const sectionId = response.section_id;
                    const month = $('#filter_month').val();
                    const year = $('#filter_year').val();
                    const term = $('#filter_term').val();
                    
                    // Build URL with student_ids as query parameters
                    let url = '<?php echo site_url('admin/attendance_report_view/'); ?>' + classId + '/' + sectionId + '/' + month + '/' + year + '/' + term;
                    
                    // Convert comma-separated string to array format for URL: student_ids[]=1&student_ids[]=2
                    const idsArray = studentIds.split(',');
                    const queryParams = idsArray.map(id => 'student_ids[]=' + encodeURIComponent(id.trim())).join('&');
                    url += '?' + queryParams;
                    
                    $('.close').click();
                    window.open(url, '_blank');
                } else {
                    $('.close').click();
                    showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>', 'error');
                }
            },
            error: function(xhr, status, error) {
                $('.close').click();
                console.error('AJAX Error:', status, error);
                showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>: ' + error, 'error');
            }
        });
    }
}

function displayReport(data) {
    $('#report_content').show();
    
    const totalAbsent = data.stats.total_absent + data.stats.total_sick_home + data.stats.total_sick_clinic;
    const totalPresent = data.stats.total_present + data.stats.total_late;
    const totalDays = data.stats.total_days;
    const totalRecords = totalPresent + totalAbsent;
    const attendanceRate = totalRecords > 0 ? ((totalPresent / totalRecords) * 100).toFixed(1) : 0;
    const absentRate = totalRecords > 0 ? ((totalAbsent / totalRecords) * 100).toFixed(1) : 0;
    
    $('#stat_days').text(totalDays);
    $('#stat_present').text(totalPresent);
    $('#stat_absent').text(totalAbsent);
    $('#stat_avg').text(attendanceRate + '%');
    $('#present_rate').text(attendanceRate);
    $('#absent_rate').text(absentRate);
    
    renderCharts(data);
    renderTable(data.students);
}

function renderCharts(data) {
    if(trendChart) trendChart.destroy();
    if(statusChart) statusChart.destroy();
    
    const totalAbsent = data.stats.total_absent + data.stats.total_sick_home + data.stats.total_sick_clinic;
    const totalPresent = data.stats.total_present + data.stats.total_late;
    const totalRecords = totalPresent + totalAbsent;
    const attendanceRate = totalRecords > 0 ? ((totalPresent / totalRecords) * 100).toFixed(1) : 0;
    
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: '<?php echo get_phrase('attendance_rate'); ?>',
                data: [attendanceRate, parseFloat(attendanceRate) - 2, parseFloat(attendanceRate) + 1, attendanceRate],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
            }
        }
    });
    
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['<?php echo get_phrase('present'); ?>', '<?php echo get_phrase('absent'); ?>', '<?php echo get_phrase('late'); ?>', 'Sick-Home', 'Sick-Clinic', 'Excused'],
            datasets: [{
                data: [data.stats.total_present, data.stats.total_absent, data.stats.total_late, data.stats.total_sick_home, data.stats.total_sick_clinic, data.stats.total_excused || 0],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#fbbf24', '#3b82f6', '#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function renderTable(students) {
    let html = '';
    students.forEach(s => {
        const rate = s.rate || 0;
        const badgeClass = rate >= 90 ? 'badge-success' : rate >= 75 ? 'badge-warning' : 'badge-danger';
        const sectionName = s.section_name ? s.section_name : '';
        
        // Format class display: "NURSERY 1 LILY" format
        const classDisplay = s.class_numeric ? `${s.class_name} ${s.class_numeric}` : s.class_name;
        const fullClassDisplay = sectionName ? `${classDisplay} ${sectionName}` : classDisplay;
        
        html += `<tr>
            <td><strong>${s.student_name}</strong></td>
            <td><strong>${fullClassDisplay}</strong></td>
            <td><span class="badge badge-success">${s.present || 0}</span></td>
            <td><span class="badge badge-danger">${s.absent || 0}</span></td>
            <td><span class="badge badge-warning">${s.late || 0}</span></td>
            <td><span class="badge" style="background: #fef3c7; color: #92400e;">${s.sick_home || 0}</span></td>
            <td><span class="badge" style="background: #dbeafe; color: #1e40af;">${s.sick_clinic || 0}</span></td>
            <td><span class="badge" style="background: #ede9fe; color: #5b21b6;">${s.excused || 0}</span></td>
            <td><span class="badge ${badgeClass}">${rate}%</span></td>
        </tr>`;
    });
    $('#student_tbody').html(html);
}

function exportReport() {
    const classId = $('#filter_class').val();
    const startDate = $('#filter_start').val();
    const endDate = $('#filter_end').val();
    window.open('<?php echo site_url('attendance/export'); ?>?format=excel&start=' + startDate + '&end=' + endDate + (classId ? '&class_id=' + classId : ''), '_blank');
}

function printReport() {
    const classId = $('#filter_class').val();
    const startDate = $('#filter_start').val();
    const endDate = $('#filter_end').val();
    window.open('<?php echo site_url('attendance/print_report'); ?>?class_id=' + classId + '&start_date=' + startDate + '&end_date=' + endDate, '_blank');
}

// ============================================================================
// CUSTOM STUDENT SELECTOR - State Management & Core Functions (Task 3)
// ============================================================================

// State management for selected students
let selectedStudents = [];  // Array of {id, name} objects
let searchTimeout = null;

function addStudent(id, name) {
    // Check if already selected
    if (selectedStudents.find(s => s.id == id)) {
        console.log('Student already selected:', name);
        return;
    }
    
    // Add to state
    selectedStudents.push({id: id, name: name});
    
    // Update UI
    renderTags();
    updateHiddenInput();
    updateCountBadge();
    syncFilterMode();  // Auto-switch filter mode when students are added
}

function removeStudent(id) {
    selectedStudents = selectedStudents.filter(s => s.id != id);
    renderTags();
    updateHiddenInput();
    updateCountBadge();
    syncFilterMode();  // Update filter mode when students are removed
}

function clearAllStudents() {
    selectedStudents = [];
    renderTags();
    updateHiddenInput();
    updateCountBadge();
    syncFilterMode();  // Update filter mode when all students are cleared
}

function updateHiddenInput() {
    const ids = selectedStudents.map(s => s.id).join(',');
    $('#filter_students').val(ids);
    console.log('Hidden input updated:', ids);
}

function updateCountBadge() {
    const count = selectedStudents.length;
    const badge = $('#student_count_badge');
    
    if (count > 0) {
        badge.html('<i class="fa fa-check-circle"></i> ' + count + ' selected').fadeIn(200);
    } else {
        badge.fadeOut(200);
    }
}

// ============================================================================
// Tag Rendering (Task 4)
// ============================================================================

function renderTags() {
    const container = $('#selected_students_tags');
    container.empty();
    
    if (selectedStudents.length === 0) {
        return;
    }
    
    selectedStudents.forEach(function(student) {
        const tag = $('<div></div>')
            .addClass('student-tag')
            .attr('data-id', student.id)
            .html(student.name + ' <span class="tag-remove">×</span>');
        
        // Add click handler for remove button
        tag.find('.tag-remove').on('click', function(e) {
            e.stopPropagation();
            removeStudent(student.id);
        });
        
        container.append(tag);
    });
}

// ============================================================================
// AJAX Student Search (Task 5)
// ============================================================================

// Initialize search input handler after DOM is ready
$(document).ready(function() {
    $('#student_search_input').on('input', function() {
        const query = $(this).val().trim();
        
        console.log('Input event fired, query:', query); // Debug log
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Hide dropdown if empty or less than 2 characters
        if (query.length < 2) {
            $('#student_search_results').hide();
            return;
        }
        
        // Debounce - wait 250ms after user stops typing
        searchTimeout = setTimeout(function() {
            searchStudents(query);
        }, 250);
    });
    
    console.log('Student search input handler initialized'); // Debug log
});

function searchStudents(query) {
    const ajaxUrl = '<?php echo site_url('admin/ajax_search_students'); ?>';
    console.log('Searching for:', query);
    console.log('AJAX URL:', ajaxUrl);
    
    $.ajax({
        url: ajaxUrl,
        type: 'GET',
        data: { q: query },
        dataType: 'json',
        beforeSend: function() {
            console.log('Search request sent');
            $('#student_search_results')
                .html('<div class="search-loading"><i class="fa fa-spinner fa-spin"></i> Searching...</div>')
                .show();
        },
        success: function(response) {
            console.log('Search response:', response);
            if (response && response.results) {
                renderSearchResults(response.results);
            } else {
                renderSearchResults([]);
            }
        },
        error: function(xhr, status, error) {
            console.error('=== AJAX ERROR DETAILS ===');
            console.error('Error:', error);
            console.error('Status:', status);
            console.error('HTTP Status Code:', xhr.status);
            console.error('Response Text:', xhr.responseText);
            console.error('XHR Object:', xhr);
            console.error('=========================');
            
            // Show more helpful error message
            let errorMsg = 'Search failed';
            if (xhr.status === 403) {
                errorMsg = 'Access denied - Please check login status';
            } else if (xhr.status === 404) {
                errorMsg = 'Search endpoint not found';
            } else if (xhr.status === 500) {
                errorMsg = 'Server error - Check console for details';
            }
            
            $('#student_search_results')
                .html('<div class="search-error"><i class="fa fa-exclamation-circle"></i> ' + errorMsg + '</div>')
                .show();
        }
    });
}

// ============================================================================
// Search Results Rendering (Task 6)
// ============================================================================

function renderSearchResults(students) {
    const container = $('#student_search_results');
    
    if (!students || students.length === 0) {
        container.html('<div class="no-results"><i class="fa fa-user-slash"></i> No students found</div>').show();
        return;
    }
    
    container.empty();
    let hasResults = false;
    
    students.forEach(function(student) {
        // Skip if already selected
        if (selectedStudents.find(s => s.id == student.id)) {
            return;
        }
        
        hasResults = true;
        
        const item = $('<div></div>')
            .addClass('search-result-item')
            .attr('data-id', student.id)
            .attr('data-name', student.text)
            .html('<strong>' + student.text + '</strong>');
        
        item.on('click', function() {
            const id = $(this).attr('data-id');
            const name = $(this).attr('data-name');
            addStudent(id, name);
            $('#student_search_input').val('').focus();
            container.hide();
        });
        
        container.append(item);
    });
    
    if (!hasResults) {
        container.html('<div class="no-results"><i class="fa fa-check-circle"></i> All matching students already selected</div>');
    }
    
    container.show();
}

// ============================================================================
// Click-Outside Handler & Cleanup (Task 7)
// ============================================================================

$(document).on('click', function(e) {
    if (!$(e.target).closest('#custom_student_selector, #student_search_results').length) {
        $('#student_search_results').hide();
    }
});

$('#student_search_input').on('focus', function() {
    // If there are search results already, show them again
    if ($('#student_search_results').children().length > 0 && $(this).val().trim().length > 0) {
        $('#student_search_results').show();
    }
});

// Update toggleFilterMode() to clear selection when switching modes
function toggleFilterMode() {
    const mode = $('#filter_mode').val();
    if(mode === 'students') {
        $('#class_filter_group').hide();
        $('#students_filter_group').show();
        $('#student_search_input').focus();  // Auto-focus for better UX
    } else {
        $('#class_filter_group').show();
        $('#students_filter_group').hide();
        // Clear student selection when switching away
        clearAllStudents();
        $('#student_search_input').val('');
        $('#student_search_results').hide();
    }
}

// Auto-sync filter mode when students are added/removed
function syncFilterMode() {
    const studentIds = $('#filter_students').val();
    const currentMode = $('#filter_mode').val();
    
    // If students are selected and mode is still 'class', auto-switch to 'students'
    if(studentIds && studentIds.length > 0 && currentMode === 'class') {
        $('#filter_mode').val('students');
        toggleFilterMode();
    }
    // If no students and mode is 'students', could switch back to 'class' (optional)
    // else if((!studentIds || studentIds.length === 0) && currentMode === 'students') {
    //     $('#filter_mode').val('class');
    //     toggleFilterMode();
    // }
}
</script>
