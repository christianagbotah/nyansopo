<style>
.filter-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
.filter-group { display: flex; flex-direction: column; gap: 8px; }
.filter-label { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px; }
.filter-input, .filter-select, .filter-btn { height: 48px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 0 16px; font-size: 15px; transition: all 0.3s; }
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.filter-btn { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
.filter-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
.action-btns { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.btn-action { height: 48px; padding: 0 24px; border-radius: 8px; font-weight: 600; font-size: 15px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; }
.btn-present { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-absent { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
.btn-save { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; margin-left: auto; }
.btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.attendance-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.attendance-table table { width: 100%; border-collapse: collapse; }
.attendance-table thead { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
.attendance-table th { padding: 16px; text-align: left; font-weight: 700; font-size: 14px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
.attendance-table td { padding: 16px; border-bottom: 1px solid #e5e7eb; font-size: 15px; color: #374151; }
.attendance-table tbody tr:hover { background: #f8fafc; }
.attendance-select { height: 40px; border: 2px solid #e5e7eb; border-radius: 6px; padding: 0 12px; font-size: 14px; font-weight: 600; min-width: 120px; }
.attendance-select:focus { border-color: #3b82f6; outline: none; }
@media (max-width: 768px) {
    .filter-grid { grid-template-columns: 1fr; }
    .action-btns { flex-direction: column; }
    .btn-action { width: 100%; justify-content: center; }
    .btn-save { margin-left: 0; }
    .attendance-table { overflow-x: auto; }
}
</style>

<div style="padding: 24px;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 24px; border-radius: 12px 12px 0 0;">
            <h2 style="margin: 0; color: white; font-size: 28px; font-weight: 700;">
                <i class="entypo-clipboard"></i> <?php echo get_phrase('transport_attendance'); ?>
            </h2>
        </div>
        
        <div style="padding: 24px;">
            <!-- Filters -->
            <div class="filter-card">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;" class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('select_route'); ?></label>
                        <select id="transport_id" class="filter-select">
                            <option value="">-- <?php echo get_phrase('select_route'); ?> --</option>
                            <option value="all"><?php echo get_phrase('all_routes'); ?></option>
                            <?php foreach ($transports as $transport): ?>
                            <option value="<?php echo $transport['transport_id']; ?>"><?php echo $transport['route_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('from_date'); ?></label>
                        <input type="date" id="from_date" value="<?php echo date('Y-m-d'); ?>" class="filter-input">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('to_date'); ?></label>
                        <input type="date" id="to_date" value="<?php echo date('Y-m-d'); ?>" class="filter-input">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('session'); ?></label>
                        <select id="attendance_type" class="filter-select">
                            <option value="both"><?php echo get_phrase('both'); ?></option>
                            <option value="morning"><?php echo get_phrase('morning'); ?></option>
                            <option value="afternoon"><?php echo get_phrase('afternoon'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="justify-content: flex-end;">
                        <label class="filter-label" style="opacity: 0;">Load</label>
                        <button onclick="loadAttendance()" class="filter-btn">
                            <i class="fa fa-search"></i> <?php echo get_phrase('load'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student Report Section -->
            <div class="filter-card" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #3b82f6;">
                <h3 style="color: #1e40af; margin-bottom: 16px; font-weight: 700;">
                    <i class="entypo-user"></i> <?php echo get_phrase('student_transport_report'); ?>
                </h3>
                <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 16px; align-items: end;" class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('select_student'); ?></label>
                        <select id="report_student_id" class="filter-select select2-student" style="border-color: #3b82f6; width: 100%;">
                            <option value="">-- <?php echo get_phrase('select_student'); ?> --</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('year'); ?></label>
                        <select id="report_year" class="filter-select" style="border-color: #3b82f6;">
                            <?php populate_academic_year('yes'); ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('term'); ?></label>
                        <select id="report_term" class="filter-select" style="border-color: #3b82f6;">
                            <option value="all"><?php echo get_phrase('all'); ?></option>
                            <option value="1" <?php echo ($this->db->get_where('settings', array('type' => 'running_term'))->row()->description == '1') ? 'selected' : ''; ?>>1</option>
                            <option value="2" <?php echo ($this->db->get_where('settings', array('type' => 'running_term'))->row()->description == '2') ? 'selected' : ''; ?>>2</option>
                            <option value="3" <?php echo ($this->db->get_where('settings', array('type' => 'running_term'))->row()->description == '3') ? 'selected' : ''; ?>>3</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end; margin-top: 16px;" class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('from_date'); ?></label>
                        <input type="date" id="report_from_date" class="filter-input" style="border-color: #3b82f6;">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label"><?php echo get_phrase('to_date'); ?></label>
                        <input type="date" id="report_to_date" class="filter-input" style="border-color: #3b82f6;">
                    </div>
                    
                    <div class="filter-group">
                        <button onclick="generateStudentReport()" class="filter-btn" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                            <i class="entypo-doc-text"></i> <?php echo get_phrase('generate_report'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attendance Form -->
            <form id="attendanceForm">
                <input type="hidden" id="form_transport_id" name="transport_id">
                <input type="hidden" id="form_from_date" name="from_date">
                <input type="hidden" id="form_to_date" name="to_date">
                <input type="hidden" id="form_attendance_type" name="attendance_type">
                
                <div id="attendanceTableContainer" style="display: none;">
                    <div class="action-btns">
                        <button type="button" onclick="markAll('present')" class="btn-action btn-present">
                            <i class="fa fa-check-circle"></i> <?php echo get_phrase('mark_all_present'); ?>
                        </button>
                        <button type="button" onclick="markAll('absent')" class="btn-action btn-absent">
                            <i class="fa fa-times-circle"></i> <?php echo get_phrase('mark_all_absent'); ?>
                        </button>
                        <button type="submit" class="btn-action btn-save">
                            <i class="fa fa-save"></i> <?php echo get_phrase('save_attendance'); ?>
                        </button>
                    </div>
                    
                    <div class="attendance-table">
                        <table id="attendance_table">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('student_code'); ?></th>
                                    <th><?php echo get_phrase('name'); ?></th>
                                    <th><?php echo get_phrase('class'); ?></th>
                                    <th id="route_header" style="display: none;"><?php echo get_phrase('route'); ?></th>
                                    <th><?php echo get_phrase('status'); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Load all transport students for dropdown
$(document).ready(function() {
    loadStudentsForReport();
    
    // Initialize Select2 for student search
    $('.select2-student').select2({
        placeholder: '-- <?php echo get_phrase('select_student'); ?> --',
        allowClear: true,
        width: '100%',
        theme: 'default'
    });
});

function loadStudentsForReport() {
    $.ajax({
        url: '<?php echo site_url("admin/get_all_transport_students"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if (response.data && response.data.length > 0) {
            var select = $('#report_student_id');
            select.empty();
            select.append('<option value="">-- <?php echo get_phrase('select_student'); ?> --</option>');
            response.data.forEach(function(student) {
                select.append('<option value="' + student.student_id + '">' + student.student_code + ' - ' + student.name + ' (' + student.class + ')</option>');
            });
            
            // Reinitialize Select2 after populating
            select.select2({
                placeholder: '-- <?php echo get_phrase('select_student'); ?> --',
                allowClear: true,
                width: '100%',
                theme: 'default'
            });
        }
    });
}

function generateStudentReport() {
    var student_id = $('#report_student_id').val();
    var year = $('#report_year').val();
    var term = $('#report_term').val();
    var from_date = $('#report_from_date').val();
    var to_date = $('#report_to_date').val();
    
    if (!student_id) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_student'); ?>', 'error');
        return;
    }
    
    if (!from_date || !to_date) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_date_range'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('Generating report...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/get_student_transport_report"); ?>',
        type: 'POST',
        data: { 
            student_id: student_id, 
            year: year,
            term: term,
            from_date: from_date,
            to_date: to_date
        },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if (response.status === 'success') {
            displayStudentReport(response.data);
        } else {
            showAjaxModal_alert(response.message || 'Failed to generate report', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function displayStudentReport(data) {
    var html = '<div id="studentReportContent" style="padding: 24px;">';
    
    // Header
    html += '<div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #3b82f6; padding-bottom: 20px;">';
    html += '<h2 style="color: #1e40af; margin: 0 0 10px 0; font-weight: 700;">Transport Attendance Report</h2>';
    html += '<p style="color: #6b7280; font-size: 16px; margin: 0;">' + data.student.student_code + ' - ' + data.student.name + '</p>';
    html += '<p style="color: #6b7280; font-size: 14px; margin: 4px 0 0 0;">' + data.student.class + '</p>';
    html += '</div>';
    
    // Student Info & Statistics
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">';
    
    // Student Info Card
    html += '<div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6;">';
    html += '<h4 style="color: #1e40af; margin: 0 0 12px 0; font-weight: 700;"><i class="entypo-user"></i> Student Information</h4>';
    html += '<table style="width: 100%; font-size: 14px;">';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Route:</strong></td><td style="color: #1f2937;">' + (data.student.route_name || 'Not Assigned') + '</td></tr>';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Route Fare:</strong></td><td style="color: #1f2937;">GHS ' + (data.student.route_fare ? parseFloat(data.student.route_fare).toFixed(2) : '0.00') + '</td></tr>';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Report Period:</strong></td><td style="color: #1f2937;">' + data.period_label + '</td></tr>';
    html += '</table></div>';
    
    // Statistics Card
    html += '<div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #10b981;">';
    html += '<h4 style="color: #059669; margin: 0 0 12px 0; font-weight: 700;"><i class="entypo-chart-bar"></i> Statistics</h4>';
    html += '<table style="width: 100%; font-size: 14px;">';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Total Days:</strong></td><td style="color: #1f2937; font-weight: 700;">' + data.stats.total_days + '</td></tr>';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Present:</strong></td><td style="color: #10b981; font-weight: 700;">' + data.stats.present + ' (' + data.stats.present_percent + '%)</td></tr>';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Absent:</strong></td><td style="color: #ef4444; font-weight: 700;">' + data.stats.absent + ' (' + data.stats.absent_percent + '%)</td></tr>';
    html += '<tr><td style="padding: 6px 0; color: #6b7280;"><strong>Late:</strong></td><td style="color: #f59e0b; font-weight: 700;">' + data.stats.late + ' (' + data.stats.late_percent + '%)</td></tr>';
    html += '</table></div></div>';
    
    // Payment Information
    if (data.payments && data.payments.total_paid > 0) {
        html += '<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b; margin-bottom: 30px;">';
        html += '<h4 style="color: #d97706; margin: 0 0 12px 0; font-weight: 700;"><i class="entypo-credit-card"></i> Payment Summary</h4>';
        html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; text-align: center;">';
        html += '<div><div style="font-size: 24px; font-weight: 700; color: #d97706;">GHS ' + parseFloat(data.payments.total_paid).toFixed(2) + '</div><div style="color: #92400e; font-size: 13px; margin-top: 4px;">Total Paid</div></div>';
        html += '<div><div style="font-size: 24px; font-weight: 700; color: #d97706;">' + data.payments.payment_count + '</div><div style="color: #92400e; font-size: 13px; margin-top: 4px;">Payments Made</div></div>';
        html += '<div><div style="font-size: 24px; font-weight: 700; color: #d97706;">GHS ' + parseFloat(data.payments.expected).toFixed(2) + '</div><div style="color: #92400e; font-size: 13px; margin-top: 4px;">Expected (Term)</div></div>';
        html += '</div></div>';
    }
    
    // Attendance Details Table
    if (data.attendance && data.attendance.length > 0) {
        html += '<div style="margin-bottom: 20px;"><h4 style="color: #1f2937; margin-bottom: 16px; font-weight: 700;"><i class="entypo-calendar"></i> Attendance Details</h4>';
        html += '<table class="table table-bordered" style="background: white; font-size: 14px;"><thead style="background: #f3f4f6;"><tr>';
        html += '<th style="padding: 12px;">Date</th><th style="padding: 12px;">Session</th><th style="padding: 12px;">Status</th><th style="padding: 12px;">Marked By</th></tr></thead><tbody>';
        
        data.attendance.forEach(function(record) {
            var statusColor = record.status === 'present' ? '#10b981' : (record.status === 'absent' ? '#ef4444' : '#f59e0b');
            var statusIcon = record.status === 'present' ? 'check' : (record.status === 'absent' ? 'cancel' : 'clock');
            html += '<tr>';
            html += '<td style="padding: 12px;">' + record.attendance_date + '</td>';
            html += '<td style="padding: 12px; text-transform: capitalize;">' + record.attendance_type + '</td>';
            html += '<td style="padding: 12px;"><span style="color: ' + statusColor + '; font-weight: 600;"><i class="entypo-' + statusIcon + '"></i> ' + record.status.toUpperCase() + '</span></td>';
            html += '<td style="padding: 12px;">' + (record.marked_by_name || 'System') + '</td></tr>';
        });
        
        html += '</tbody></table></div>';
    } else {
        html += '<div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center; color: #92400e; margin-bottom: 20px;">';
        html += '<i class="entypo-info-circled" style="font-size: 24px; margin-bottom: 8px;"></i><br>';
        html += 'No attendance records found for the selected period.</div>';
    }
    
    // Action Buttons
    html += '<div class="no-print" style="display: flex; gap: 12px; justify-content: center; margin-top: 30px;">';
    html += '<button onclick="printStudentReport()" class="btn btn-primary" style="background: #3b82f6; border: none; padding: 12px 32px; border-radius: 8px; font-weight: 600;">';
    html += '<i class="entypo-print"></i> Print Report</button>';
    html += '<button onclick="exportStudentReportPDF()" class="btn btn-success" style="background: #10b981; border: none; padding: 12px 32px; border-radius: 8px; font-weight: 600;">';
    html += '<i class="entypo-doc"></i> Export PDF</button>';
    html += '<button onclick="closeStudentReport()" class="btn btn-default" style="padding: 12px 32px; border-radius: 8px; font-weight: 600;">';
    html += '<i class="entypo-cancel"></i> Close</button></div>';
    
    html += '</div>';
    
    showModalWithContent('largeModal', '<i class="entypo-chart-line"></i> Student Transport Report', html);
}

function printStudentReport() {
    var content = document.getElementById('studentReportContent').innerHTML;
    var printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write('<html><head><title>Student Transport Report</title>');
    printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 20px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }');
    printWindow.document.write('th { background: #f3f4f6; font-weight: 700; }');
    printWindow.document.write('.no-print { display: none !important; }');
    printWindow.document.write('h2, h4 { color: #1f2937; }</style></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('<script>window.print(); window.close();<\/script></body></html>');
    printWindow.document.close();
}

function exportStudentReportPDF() {
    showAjaxModal_alert('PDF export feature coming soon!', 'info');
}

function closeStudentReport() {
    $('#largeModal').modal('hide');
}

function loadAttendance() {
    var transport_id = $('#transport_id').val();
    var from_date = $('#from_date').val();
    var to_date = $('#to_date').val();
    var attendance_type = $('#attendance_type').val();
    
    if (!transport_id) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_route'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('Loading...', 'loading');
    
    $('#form_transport_id').val(transport_id);
    $('#form_from_date').val(from_date);
    $('#form_to_date').val(to_date);
    $('#form_attendance_type').val(attendance_type);
    
    $.ajax({
        url: '<?php echo site_url("admin/get_transport_attendance_students"); ?>',
        type: 'POST',
        data: { 
            transport_id: transport_id, 
            attendance_date: from_date,
            attendance_type: attendance_type 
        },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if (response.data && response.data.length > 0) {
            renderAttendanceTable(response.data, transport_id === 'all');
            $('#attendanceTableContainer').show();
        } else {
            showAjaxModal_alert('<?php echo get_phrase('no_students_found'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('Failed to load students', 'error');
    });
}

function renderAttendanceTable(students, showRoute) {
    var tbody = $('#attendance_table tbody');
    tbody.empty();
    
    // Show/hide route column header
    if (showRoute) {
        $('#route_header').show();
    } else {
        $('#route_header').hide();
    }
    
    students.forEach(function(student) {
        var row = '<tr>';
        row += '<td style="font-weight: 700; color: #3b82f6;">' + student.student_code + '</td>';
        row += '<td>' + student.name + '</td>';
        row += '<td>' + student.class + '</td>';
        
        // Show route column if "all" routes selected
        if (showRoute) {
            row += '<td>' + (student.route || '-') + '</td>';
        }
        
        row += '<td>';
        row += '<input type="hidden" name="transport_ids[' + student.student_id + ']" value="' + student.transport_id + '">';
        row += '<select name="attendance[' + student.student_id + ']" class="attendance-select">';
        row += '<option value="present"' + (student.status === 'present' ? ' selected' : '') + '><?php echo get_phrase('present'); ?></option>';
        row += '<option value="absent"' + (student.status === 'absent' ? ' selected' : '') + '><?php echo get_phrase('absent'); ?></option>';
        row += '<option value="late"' + (student.status === 'late' ? ' selected' : '') + '><?php echo get_phrase('late'); ?></option>';
        row += '</select></td></tr>';
        tbody.append(row);
    });
}

function markAll(status) {
    $('.attendance-select').val(status);
}

$('#attendanceForm').on('submit', function(e) {
    e.preventDefault();
    showAjaxModal_alert('Saving...', 'loading');
    
    // Build data manually to include transport_ids
    var formData = {
        attendance: {},
        transport_ids: {},
        attendance_date: $('#form_from_date').val(),
        attendance_type: $('#form_attendance_type').val()
    };
    
    $('select[name^="attendance"]').each(function() {
        var name = $(this).attr('name');
        var student_id = name.match(/\[(\d+)\]/)[1];
        formData.attendance[student_id] = $(this).val();
    });
    
    $('input[name^="transport_ids"]').each(function() {
        var name = $(this).attr('name');
        var student_id = name.match(/\[(\d+)\]/)[1];
        formData.transport_ids[student_id] = $(this).val();
    });
    
    $.ajax({
        url: '<?php echo site_url("admin/transport_attendance/mark_attendance"); ?>',
        type: 'POST',
        data: formData,
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showAjaxModal_alert(response.message + ' (' + response.count + ' students)', 'success');
            setTimeout(() => loadAttendance(), 2000);
        } else {
            showAjaxModal_alert(response.message || 'Failed to save', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});
</script>
