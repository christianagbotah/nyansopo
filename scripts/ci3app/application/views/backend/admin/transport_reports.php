<style>
.report-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    transition: all 0.3s;
}
.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.report-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.report-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}
.filter-section {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}
.filter-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.filter-control {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
}
.filter-control:focus {
    border-color: #3b82f6;
    outline: none;
}
.btn-generate {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white;
    padding: 12px 32px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-generate:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}
.report-result {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-top: 20px;
}
@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border: none;">
                <h4 style="margin: 0; color: white; font-weight: 700;">
                    <i class="entypo-chart-line"></i> <?php echo get_phrase('transport_reports'); ?>
                </h4>
            </div>
            <div class="panel-body" style="background: #f5f7fa; padding: 24px;">
                
                <!-- Report Type Selection -->
                <div class="row">
                    <!-- Student Transport Report -->
                    <div class="col-md-6">
                        <div class="report-card">
                            <div class="report-header">
                                <div class="report-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                                    <i class="entypo-user"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-weight: 700;"><?php echo get_phrase('student_transport_report'); ?></h4>
                                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 13px;">Individual student transport usage and payment history</p>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('select_student'); ?></label>
                                        <select id="student_report_student" class="filter-control select2-student">
                                            <option value="">-- <?php echo get_phrase('select'); ?> --</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('year'); ?></label>
                                        <select id="student_report_year" class="filter-control">
                                            <?php populate_academic_year('yes'); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('term'); ?></label>
                                        <select id="student_report_term" class="filter-control">
                                            <option value="all"><?php echo get_phrase('all'); ?></option>
                                            <option value="1" <?php echo (get_settings('running_term') == '1') ? 'selected' : ''; ?>>1</option>
                                            <option value="2" <?php echo (get_settings('running_term') == '2') ? 'selected' : ''; ?>>2</option>
                                            <option value="3" <?php echo (get_settings('running_term') == '3') ? 'selected' : ''; ?>>3</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="filter-label">&nbsp;</label>
                                        <button onclick="generateStudentReport()" class="btn-generate" style="width: 100%;">
                                            <i class="entypo-doc-text"></i> <?php echo get_phrase('generate'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Route Summary Report -->
                    <div class="col-md-6">
                        <div class="report-card">
                            <div class="report-header">
                                <div class="report-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                    <i class="entypo-direction"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-weight: 700;"><?php echo get_phrase('route_summary_report'); ?></h4>
                                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 13px;">Students, attendance, and revenue per route</p>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('select_route'); ?></label>
                                        <select id="route_report_route" class="filter-control">
                                            <option value="all"><?php echo get_phrase('all_routes'); ?></option>
                                            <?php 
                                            $routes = $this->db->get('transport')->result_array();
                                            foreach($routes as $route): 
                                            ?>
                                            <option value="<?php echo $route['transport_id']; ?>"><?php echo $route['route_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('year'); ?></label>
                                        <select id="route_report_year" class="filter-control">
                                            <?php populate_academic_year('yes'); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('term'); ?></label>
                                        <select id="route_report_term" class="filter-control">
                                            <option value="all"><?php echo get_phrase('all'); ?></option>
                                            <option value="1" <?php echo (get_settings('running_term') == '1') ? 'selected' : ''; ?>>1</option>
                                            <option value="2" <?php echo (get_settings('running_term') == '2') ? 'selected' : ''; ?>>2</option>
                                            <option value="3" <?php echo (get_settings('running_term') == '3') ? 'selected' : ''; ?>>3</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="filter-label">&nbsp;</label>
                                        <button onclick="generateRouteReport()" class="btn-generate" style="width: 100%;">
                                            <i class="entypo-doc-text"></i> <?php echo get_phrase('generate'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row: Revenue and Attendance Reports -->
                <div class="row">
                    <!-- Revenue Report -->
                    <div class="col-md-6">
                        <div class="report-card">
                            <div class="report-header">
                                <div class="report-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                                    <i class="entypo-credit-card"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-weight: 700;"><?php echo get_phrase('revenue_report'); ?></h4>
                                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 13px;">Transport income analysis and trends</p>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('from_date'); ?></label>
                                        <input type="text" id="revenue_from_date" class="filter-control datepicker" placeholder="dd/mm/yyyy" readonly>
                                    </div>
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('to_date'); ?></label>
                                        <input type="text" id="revenue_to_date" class="filter-control datepicker" placeholder="dd/mm/yyyy" readonly>
                                    </div>
                                </div>
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('group_by'); ?></label>
                                        <select id="revenue_group_by" class="filter-control">
                                            <option value="day"><?php echo get_phrase('day'); ?></option>
                                            <option value="week"><?php echo get_phrase('week'); ?></option>
                                            <option value="month" selected><?php echo get_phrase('month'); ?></option>
                                            <option value="route"><?php echo get_phrase('route'); ?></option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="filter-label">&nbsp;</label>
                                        <button onclick="generateRevenueReport()" class="btn-generate" style="width: 100%;">
                                            <i class="entypo-doc-text"></i> <?php echo get_phrase('generate'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Report -->
                    <div class="col-md-6">
                        <div class="report-card">
                            <div class="report-header">
                                <div class="report-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                    <i class="entypo-calendar"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-weight: 700;"><?php echo get_phrase('attendance_report'); ?></h4>
                                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 13px;">Daily boarding statistics and trends</p>
                                </div>
                            </div>
                            
                            <div class="filter-section">
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('select_route'); ?></label>
                                        <select id="attendance_route" class="filter-control">
                                            <option value="all"><?php echo get_phrase('all_routes'); ?></option>
                                            <?php foreach($routes as $route): ?>
                                            <option value="<?php echo $route['transport_id']; ?>"><?php echo $route['route_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('from_date'); ?></label>
                                        <input type="text" id="attendance_from_date" class="filter-control datepicker" placeholder="dd/mm/yyyy" readonly>
                                    </div>
                                </div>
                                <div class="filter-row">
                                    <div>
                                        <label class="filter-label"><?php echo get_phrase('to_date'); ?></label>
                                        <input type="text" id="attendance_to_date" class="filter-control datepicker" placeholder="dd/mm/yyyy" readonly>
                                    </div>
                                    <div>
                                        <label class="filter-label">&nbsp;</label>
                                        <button onclick="generateAttendanceReport()" class="btn-generate" style="width: 100%;">
                                            <i class="entypo-doc-text"></i> <?php echo get_phrase('generate'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Results Container -->
                <div id="reportResults" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 for student dropdown first
    $('#student_report_student').select2({
        placeholder: '-- <?php echo get_phrase('select'); ?> --',
        allowClear: true,
        width: '100%'
    });
    
    // Load students for dropdown
    loadTransportStudents();
    
    // Initialize datepickers with dd/mm/yyyy format
    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });
    
    // Set default dates
    var today = new Date();
    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#revenue_from_date').datepicker('setDate', firstDay);
    $('#revenue_to_date').datepicker('setDate', today);
    $('#attendance_from_date').datepicker('setDate', firstDay);
    $('#attendance_to_date').datepicker('setDate', today);
});

// Helper function to convert dd/mm/yyyy to yyyy-mm-dd
function convertDateToISO(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('/');
    if (parts.length !== 3) return dateStr;
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}

function loadTransportStudents() {
    $.ajax({
        url: '<?php echo site_url("admin/get_all_transport_students"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Transport students response:', response);
            if (response.data && response.data.length > 0) {
                var select = $('#student_report_student');
                select.empty();
                select.append('<option value="">-- <?php echo get_phrase('select'); ?> --</option>');
                response.data.forEach(function(student) {
                    select.append('<option value="' + student.student_id + '">' + student.student_code + ' - ' + student.name + ' (' + student.class + ')</option>');
                });
                select.select2({
                    placeholder: '-- <?php echo get_phrase('select'); ?> --',
                    allowClear: true,
                    width: '100%'
                });
            } else {
                console.warn('No transport students found');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading transport students:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

function generateStudentReport() {
    var student_id = $('#student_report_student').val();
    var year = $('#student_report_year').val();
    var term = $('#student_report_term').val();
    
    if (!student_id) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_student'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/generate_student_transport_report"); ?>',
        type: 'POST',
        data: { student_id: student_id, year: year, term: term },
        dataType: 'json',
        success: function(response) {
            $('#modal_alert').modal('hide');
            if (response.status === 'success') {
                displayStudentTransportReport(response.data);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
        }
    });
}

function generateRouteReport() {
    var route_id = $('#route_report_route').val();
    var year = $('#route_report_year').val();
    var term = $('#route_report_term').val();
    
    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/generate_route_summary_report"); ?>',
        type: 'POST',
        data: { route_id: route_id, year: year, term: term },
        dataType: 'json',
        success: function(response) {
            $('#modal_alert').modal('hide');
            if (response.status === 'success') {
                displayRouteSummaryReport(response.data);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
        }
    });
}

function generateRevenueReport() {
    var from_date = $('#revenue_from_date').val();
    var to_date = $('#revenue_to_date').val();
    var group_by = $('#revenue_group_by').val();
    
    if (!from_date || !to_date) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_date_range'); ?>', 'error');
        return;
    }
    
    // Convert dates from dd/mm/yyyy to yyyy-mm-dd
    from_date = convertDateToISO(from_date);
    to_date = convertDateToISO(to_date);
    
    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/generate_revenue_report"); ?>',
        type: 'POST',
        data: { from_date: from_date, to_date: to_date, group_by: group_by },
        dataType: 'json',
        success: function(response) {
            $('#modal_alert').modal('hide');
            if (response.status === 'success') {
                displayRevenueReport(response.data);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
        }
    });
}

function generateAttendanceReport() {
    var route_id = $('#attendance_route').val();
    var from_date = $('#attendance_from_date').val();
    var to_date = $('#attendance_to_date').val();
    
    if (!from_date || !to_date) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_date_range'); ?>', 'error');
        return;
    }
    
    // Convert dates from dd/mm/yyyy to yyyy-mm-dd
    from_date = convertDateToISO(from_date);
    to_date = convertDateToISO(to_date);
    
    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/generate_attendance_report"); ?>',
        type: 'POST',
        data: { route_id: route_id, from_date: from_date, to_date: to_date },
        dataType: 'json',
        success: function(response) {
            $('#modal_alert').modal('hide');
            if (response.status === 'success') {
                displayAttendanceReport(response.data);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
        }
    });
}

// Display functions for reports using system modal pattern
function displayStudentTransportReport(data) {
    var html = '<div style="padding: 10px;">';
    html += '<h3 style="margin: 0 0 20px 0; color: #1e293b; text-align: center;"><i class="entypo-user"></i> Student Transport Report</h3>';
    
    // Student Info Card
    html += '<div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px;">';
    html += '<h4 style="margin: 0 0 10px 0; color: white;">' + data.student.name + '</h4>';
    html += '<p style="margin: 0; opacity: 0.9;">Student Code: ' + data.student.student_code + ' | Class: ' + data.student.class + '</p>';
    if (data.student.route_name) {
        html += '<p style="margin: 5px 0 0 0; opacity: 0.9;">Route: ' + data.student.route_name + ' | Fare: ₵' + parseFloat(data.student.route_fare).toFixed(2) + '</p>';
    }
    html += '</div>';
    
    // Statistics Cards
    html += '<div class="row" style="margin-bottom: 20px;">';
    
    html += '<div class="col-md-3"><div style="background: #10b981; color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + data.stats.total_days + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;">Total Days</div>';
    html += '</div></div>';
    
    html += '<div class="col-md-3"><div style="background: #3b82f6; color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + data.stats.present + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;">Present (' + data.stats.present_percent + '%)</div>';
    html += '</div></div>';
    
    html += '<div class="col-md-3"><div style="background: #ef4444; color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + data.stats.absent + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;">Absent (' + data.stats.absent_percent + '%)</div>';
    html += '</div></div>';
    
    html += '<div class="col-md-3"><div style="background: #8b5cf6; color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">₵' + parseFloat(data.payment.total_paid || 0).toFixed(2) + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;">Total Paid</div>';
    html += '</div></div>';
    
    html += '</div>';
    
    // Attendance Table
    if (data.attendance && data.attendance.length > 0) {
        html += '<h4 style="margin: 20px 0 10px 0;">Attendance History</h4>';
        html += '<div style="overflow-x: auto;"><table class="table table-bordered table-striped">';
        html += '<thead><tr><th>Date</th><th>Status</th><th>Route</th></tr></thead><tbody>';
        data.attendance.forEach(function(record) {
            var statusClass = record.status === 'present' ? 'success' : 'danger';
            html += '<tr><td>' + record.date + '</td>';
            html += '<td><span class="label label-' + statusClass + '">' + record.status + '</span></td>';
            html += '<td>' + (record.route_name || '-') + '</td></tr>';
        });
        html += '</tbody></table></div>';
    }
    
    html += '</div>';
    
    // Set modal body max height and scrolling
    $('#modal_display .modal-dialog').addClass('modal-lg');
    $('#modal_display .modal-body').css({'max-height': '70vh', 'overflow-y': 'auto'});
    
    // Update footer with action buttons
    $('#modal_display .modal-footer').html(`
        <button type="button" class="btn modern-btn modern-btn-primary" onclick="printTransportReport()">
            <i class="entypo-print"></i> Print
        </button>
        <button type="button" class="btn modern-btn modern-btn-success" onclick="exportTransportReportToExcel()">
            <i class="entypo-download"></i> Export to Excel
        </button>
        <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
    `);
    
    showAjaxModalDisplay(html);
}

function displayRouteSummaryReport(data) {
    var html = '<div style="padding: 10px;">';
    
    // Summary Cards
    var totalStudents = 0;
    var totalRevenue = 0;
    data.forEach(function(route) {
        totalStudents += parseInt(route.student_count);
        totalRevenue += parseFloat(route.total_revenue);
    });
    
    html += '<div class="row" style="margin-bottom: 20px;">';
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + data.length + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('total_routes'); ?></div>';
    html += '</div></div>';
    
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + totalStudents + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('total_students'); ?></div>';
    html += '</div></div>';
    
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">₵' + totalRevenue.toFixed(2) + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('total_revenue'); ?></div>';
    html += '</div></div>';
    html += '</div>';
    
    // Routes Table
    html += '<div style="overflow-x: auto;"><table class="table table-bordered table-hover">';
    html += '<thead style="background: #f1f5f9;"><tr>';
    html += '<th><?php echo get_phrase('route_name'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('route_fare'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('students'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('revenue'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('expected_revenue'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('collection_rate'); ?></th>';
    html += '</tr></thead><tbody>';
    
    data.forEach(function(route) {
        var expectedRevenue = parseFloat(route.route_fare) * parseInt(route.student_count) * 3; // 3 months per term
        var collectionRate = expectedRevenue > 0 ? ((parseFloat(route.total_revenue) / expectedRevenue) * 100).toFixed(1) : 0;
        var rateClass = collectionRate >= 80 ? 'success' : (collectionRate >= 50 ? 'warning' : 'danger');
        
        html += '<tr>';
        html += '<td><strong>' + route.route_name + '</strong></td>';
        html += '<td class="text-right">₵' + parseFloat(route.route_fare).toFixed(2) + '</td>';
        html += '<td class="text-right">' + route.student_count + '</td>';
        html += '<td class="text-right"><strong>₵' + parseFloat(route.total_revenue).toFixed(2) + '</strong></td>';
        html += '<td class="text-right">₵' + expectedRevenue.toFixed(2) + '</td>';
        html += '<td class="text-right"><span class="label label-' + rateClass + '">' + collectionRate + '%</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    html += '</div>';
    
    // Set modal body max height and scrolling
    $('#modal_display .modal-dialog').addClass('modal-lg');
    $('#modal_display .modal-body').css({'max-height': '70vh', 'overflow-y': 'auto'});
    
    // Update footer with action buttons
    $('#modal_display .modal-footer').html(`
        <button type="button" class="btn modern-btn modern-btn-primary" onclick="printTransportReport()">
            <i class="entypo-print"></i> Print
        </button>
        <button type="button" class="btn modern-btn modern-btn-success" onclick="exportTransportReportToExcel()">
            <i class="entypo-download"></i> Export to Excel
        </button>
        <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
    `);
    
    showAjaxModalDisplay(html);
}

function displayRevenueReport(data) {
    var html = '<div style="padding: 10px;">';
    
    if (data.length === 0) {
        html += '<div style="background: #fef3c7; border: 1px solid #fbbf24; color: #92400e; padding: 20px; border-radius: 8px; text-align: center;">';
        html += '<i class="entypo-info" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>';
        html += '<h4 style="margin: 0 0 8px 0;">No Transport Revenue Data Found</h4>';
        html += '<p style="margin: 0;">There are no transport payments recorded for the selected date range.</p>';
        html += '</div></div>';
        
        // Set modal body max height and scrolling
        $('#modal_display .modal-dialog').addClass('modal-lg');
        $('#modal_display .modal-body').css({'max-height': '70vh', 'overflow-y': 'auto'});
        
        // Update footer with action buttons
        $('#modal_display .modal-footer').html(`
            <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
        `);
        
        showAjaxModalDisplay(html);
        return;
    }
    
    // Calculate totals
    var totalRevenue = 0;
    data.forEach(function(record) {
        totalRevenue += parseFloat(record.daily_revenue || 0);
    });
    var avgPerDay = data.length > 0 ? (totalRevenue / data.length).toFixed(2) : 0;
    
    // Summary Cards
    html += '<div class="row" style="margin-bottom: 20px;">';
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">₵' + totalRevenue.toFixed(2) + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('total_revenue'); ?></div>';
    html += '</div></div>';
    
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">₵' + avgPerDay + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('average_per_day'); ?></div>';
    html += '</div></div>';
    
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + data.length + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('days_with_payments'); ?></div>';
    html += '</div></div>';
    html += '</div>';
    
    // Info message
    html += '<div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">';
    html += '<strong><?php echo get_phrase('note'); ?>:</strong> This report shows only days when transport payments were collected.';
    html += '</div>';
    
    // Revenue Table
    html += '<div style="overflow-x: auto;"><table class="table table-bordered table-hover">';
    html += '<thead style="background: #f1f5f9;"><tr>';
    html += '<th><?php echo get_phrase('date'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('revenue'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('students'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('average_per_student'); ?></th>';
    html += '</tr></thead><tbody>';
    
    data.forEach(function(record) {
        var avgPerStudent = record.student_count > 0 ? (parseFloat(record.daily_revenue) / parseInt(record.student_count)).toFixed(2) : 0;
        html += '<tr>';
        html += '<td>' + record.payment_date + '</td>';
        html += '<td class="text-right"><strong>₵' + parseFloat(record.daily_revenue).toFixed(2) + '</strong></td>';
        html += '<td class="text-right">' + record.student_count + '</td>';
        html += '<td class="text-right">₵' + avgPerStudent + '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    html += '</div>';
    
    // Set modal body max height and scrolling
    $('#modal_display .modal-dialog').addClass('modal-lg');
    $('#modal_display .modal-body').css({'max-height': '70vh', 'overflow-y': 'auto'});
    
    // Update footer with action buttons
    $('#modal_display .modal-footer').html(`
        <button type="button" class="btn modern-btn modern-btn-primary" onclick="printTransportReport()">
            <i class="entypo-print"></i> Print
        </button>
        <button type="button" class="btn modern-btn modern-btn-success" onclick="exportTransportReportToExcel()">
            <i class="entypo-download"></i> Export to Excel
        </button>
        <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
    `);
    
    showAjaxModalDisplay(html);
}

function displayAttendanceReport(data) {
    var html = '<div style="padding: 10px;">';
    
    if (data.length === 0) {
        html += '<div style="background: #fef3c7; border: 1px solid #fbbf24; color: #92400e; padding: 20px; border-radius: 8px; text-align: center;">';
        html += '<i class="entypo-info" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>';
        html += '<h4 style="margin: 0 0 8px 0;">No Attendance Data Found</h4>';
        html += '<p style="margin: 0;">There are no attendance records for the selected date range.</p>';
        html += '</div></div>';
        
        // Set modal body max height and scrolling
        $('#modal_display .modal-dialog').addClass('modal-lg');
        $('#modal_display .modal-body').css({'max-height': '70vh', 'overflow-y': 'auto'});
        
        // Update footer with action buttons
        $('#modal_display .modal-footer').html(`
            <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
        `);
        
        showAjaxModalDisplay(html);
        return;
    }
    
    // Calculate totals
    var totalBoarded = 0;
    var totalExpected = 0;
    data.forEach(function(record) {
        totalBoarded += parseInt(record.boarded_count || 0);
        totalExpected += parseInt(record.expected_count || 0);
    });
    var attendanceRate = totalExpected > 0 ? ((totalBoarded / totalExpected) * 100).toFixed(1) : 0;
    
    // Summary Cards
    html += '<div class="row" style="margin-bottom: 20px;">';
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + totalBoarded + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('total_boarded'); ?></div>';
    html += '</div></div>';
    
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + totalExpected + '</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('expected_students'); ?></div>';
    html += '</div></div>';
    
    html += '<div class="col-md-4"><div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">';
    html += '<div style="font-size: 32px; font-weight: 700;">' + attendanceRate + '%</div>';
    html += '<div style="opacity: 0.9; margin-top: 5px;"><?php echo get_phrase('attendance_rate'); ?></div>';
    html += '</div></div>';
    html += '</div>';
    
    // Attendance Table
    html += '<div style="overflow-x: auto;"><table class="table table-bordered table-hover">';
    html += '<thead style="background: #f1f5f9;"><tr>';
    html += '<th><?php echo get_phrase('date'); ?></th>';
    html += '<th><?php echo get_phrase('route'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('boarded'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('expected'); ?></th>';
    html += '<th class="text-right"><?php echo get_phrase('rate'); ?></th>';
    html += '</tr></thead><tbody>';
    
    data.forEach(function(record) {
        var rate = record.expected_count > 0 ? ((parseInt(record.boarded_count) / parseInt(record.expected_count)) * 100).toFixed(1) : 0;
        var rateClass = rate >= 80 ? 'success' : (rate >= 50 ? 'warning' : 'danger');
        
        html += '<tr>';
        html += '<td>' + record.attendance_date + '</td>';
        html += '<td>' + (record.route_name || 'All Routes') + '</td>';
        html += '<td class="text-right"><strong>' + record.boarded_count + '</strong></td>';
        html += '<td class="text-right">' + record.expected_count + '</td>';
        html += '<td class="text-right"><span class="label label-' + rateClass + '">' + rate + '%</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    html += '</div>';
    
    // Set modal body max height and scrolling
    $('#modal_display .modal-dialog').addClass('modal-lg');
    $('#modal_display .modal-body').css({'max-height': '70vh', 'overflow-y': 'auto'});
    
    // Update footer with action buttons
    $('#modal_display .modal-footer').html(`
        <button type="button" class="btn modern-btn modern-btn-primary" onclick="printTransportReport()">
            <i class="entypo-print"></i> Print
        </button>
        <button type="button" class="btn modern-btn modern-btn-success" onclick="exportTransportReportToExcel()">
            <i class="entypo-download"></i> Export to Excel
        </button>
        <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
    `);
    
    showAjaxModalDisplay(html);
}

// Print function for transport reports
function printTransportReport() {
    var printContents = $('#modal_display .modal-body').html();
    
    // Create print window content
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Transport Report</title>');
    printWindow.document.write('<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.css">');
    printWindow.document.write('<style>');
    printWindow.document.write('body { padding: 20px; font-family: Arial, sans-serif; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
    printWindow.document.write('th { background-color: #f1f5f9; font-weight: bold; }');
    printWindow.document.write('.label { padding: 4px 8px; border-radius: 4px; font-size: 12px; }');
    printWindow.document.write('.label-success { background-color: #10b981; color: white; }');
    printWindow.document.write('.label-warning { background-color: #f59e0b; color: white; }');
    printWindow.document.write('.label-danger { background-color: #ef4444; color: white; }');
    printWindow.document.write('@media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Export to Excel function
function exportTransportReportToExcel() {
    // Get the modal content
    var tableHtml = $('#modal_display .modal-body').html();
    
    // Create a temporary div to manipulate the content
    var tempDiv = $('<div>').html(tableHtml);
    
    // Remove non-table elements and styling
    tempDiv.find('[style*="gradient"]').removeAttr('style');
    tempDiv.find('.label').each(function() {
        $(this).replaceWith($(this).text());
    });
    
    // Get all tables
    var tables = tempDiv.find('table');
    
    if (tables.length === 0) {
        showAjaxModal_alert('No table data to export', 'Error');
        return;
    }
    
    // Create Excel content
    var excelContent = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    excelContent += '<head><meta charset="UTF-8"><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
    excelContent += '<x:Name>Transport Report</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions>';
    excelContent += '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';
    excelContent += tempDiv.html();
    excelContent += '</body></html>';
    
    // Create download link
    var blob = new Blob([excelContent], { type: 'application/vnd.ms-excel' });
    var url = URL.createObjectURL(blob);
    var downloadLink = document.createElement('a');
    downloadLink.href = url;
    downloadLink.download = 'Transport_Report_' + new Date().getTime() + '.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    URL.revokeObjectURL(url);
    
    showAjaxModal_alert('Report exported successfully!', 'Success');
}
</script>
