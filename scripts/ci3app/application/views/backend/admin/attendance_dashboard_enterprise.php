<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.enterprise-container { background: #f8fafc; min-height: 100vh; padding: 24px; }
.dashboard-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px; }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
.stat-card.present { border-color: #10b981; }
.stat-card.absent { border-color: #ef4444; }
.stat-card.late { border-color: #f59e0b; }
.stat-card.total { border-color: #667eea; }
.stat-value { font-size: 36px; font-weight: 700; margin: 8px 0; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.tab-container { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
.tab-buttons { display: flex; gap: 8px; border-bottom: 2px solid #e5e7eb; margin-bottom: 24px; }
.tab-btn { padding: 12px 24px; border: none; background: transparent; color: #6b7280; font-weight: 600; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; }
.tab-btn.active { color: #667eea; border-bottom-color: #667eea; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.action-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); cursor: pointer; transition: all 0.2s; text-align: center; }
.action-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.action-icon { font-size: 32px; margin-bottom: 12px; }
.btn-enterprise { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-success { background: #10b981; color: white; }
.btn-danger { background: #ef4444; color: white; }
.attendance-table { width: 100%; border-collapse: collapse; }
.attendance-table th { background: #f9fafb; padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
.attendance-table td { padding: 16px; border-bottom: 1px solid #f3f4f6; }
.attendance-table tbody tr:hover { background: #f9fafb; }
.status-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
.status-present { background: #d1fae5; color: #065f46; }
.status-absent { background: #fee2e2; color: #991b1b; }
.status-late { background: #fef3c7; color: #92400e; }
.filter-bar { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
.search-box { flex: 1; min-width: 300px; }
.search-box input { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; }
.filter-select { padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; min-width: 180px; }
</style>

<div class="enterprise-container">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700;">
                    <i class="fa fa-calendar-check-o"></i> Attendance Management
                </h1>
                <p style="margin: 0; opacity: 0.9; font-size: 16px;">
                    <?php echo date('l, F j, Y'); ?> • <?php echo get_settings('system_name'); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button class="btn-enterprise btn-success" onclick="quickMarkAttendance()">
                    <i class="fa fa-bolt"></i> Quick Mark
                </button>
                <button class="btn-enterprise btn-primary" onclick="generateReport()">
                    <i class="fa fa-file-pdf-o"></i> Generate Report
                </button>
            </div>
        </div>
    </div>

    <div class="stats-grid" id="statsGrid">
        <div class="stat-card total">
            <div class="stat-label">Total Expected</div>
            <div class="stat-value" id="stat-total">0</div>
            <div style="color: #6b7280; font-size: 13px;">Students & Staff</div>
        </div>
        <div class="stat-card present">
            <div class="stat-label">Present</div>
            <div class="stat-value" id="stat-present">0</div>
            <div style="color: #6b7280; font-size: 13px;"><span id="present-percent">0</span>% Attendance</div>
        </div>
        <div class="stat-card absent">
            <div class="stat-label">Absent</div>
            <div class="stat-value" id="stat-absent">0</div>
            <div style="color: #6b7280; font-size: 13px;"><span id="absent-percent">0</span>% Absenteeism</div>
        </div>
        <div class="stat-card late">
            <div class="stat-label">Late Arrivals</div>
            <div class="stat-value" id="stat-late">0</div>
            <div style="color: #6b7280; font-size: 13px;">Needs Follow-up</div>
        </div>
    </div>

    <div class="quick-actions">
        <div class="action-card" onclick="markStudentAttendance()">
            <div class="action-icon" style="color: #667eea;"><i class="fa fa-users"></i></div>
            <div style="font-weight: 600; font-size: 16px;">Student Attendance</div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;">Mark by class</div>
        </div>
        <div class="action-card" onclick="markStaffAttendance()">
            <div class="action-icon" style="color: #10b981;"><i class="fa fa-id-badge"></i></div>
            <div style="font-weight: 600; font-size: 16px;">Staff Attendance</div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;">Clock in/out</div>
        </div>
        <div class="action-card" onclick="viewReports()">
            <div class="action-icon" style="color: #f59e0b;"><i class="fa fa-bar-chart"></i></div>
            <div style="font-weight: 600; font-size: 16px;">Analytics</div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;">View trends</div>
        </div>
        <div class="action-card" onclick="sendNotifications()">
            <div class="action-icon" style="color: #ef4444;"><i class="fa fa-bell"></i></div>
            <div style="font-weight: 600; font-size: 16px;">Notifications</div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;">Alert parents</div>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="switchTab('students')">
                <i class="fa fa-graduation-cap"></i> Students
            </button>
            <button class="tab-btn" onclick="switchTab('staff')">
                <i class="fa fa-users"></i> Staff
            </button>
            <button class="tab-btn" onclick="switchTab('history')">
                <i class="fa fa-history"></i> History
            </button>
            <button class="tab-btn" onclick="switchTab('reports')">
                <i class="fa fa-file-text"></i> Reports
            </button>
        </div>

        <div id="students-tab" class="tab-content active">
            <div class="filter-bar">
                <div class="filter-row">
                    <div class="search-box">
                        <input type="text" id="studentSearch" placeholder="Search students by name or code...">
                    </div>
                    <select id="classFilter" class="filter-select">
                        <option value="">All Classes</option>
                        <?php getFullClassList(); ?>
                    </select>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                    </select>
                    <input type="date" id="dateFilter" class="filter-select" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div id="studentAttendanceTable"></div>
        </div>

        <div id="staff-tab" class="tab-content">
            <div class="filter-bar">
                <div class="filter-row">
                    <div class="search-box">
                        <input type="text" id="staffSearch" placeholder="Search staff by name...">
                    </div>
                    <select id="departmentFilter" class="filter-select">
                        <option value="">All Departments</option>
                        <option value="teaching">Teaching Staff</option>
                        <option value="admin">Administrative</option>
                        <option value="support">Support Staff</option>
                    </select>
                    <input type="date" id="staffDateFilter" class="filter-select" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div id="staffAttendanceTable"></div>
        </div>

        <div id="history-tab" class="tab-content">
            <p style="text-align: center; color: #6b7280; padding: 40px;">Attendance history will be displayed here</p>
        </div>

        <div id="reports-tab" class="tab-content">
            <p style="text-align: center; color: #6b7280; padding: 40px;">Reports and analytics will be displayed here</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadDashboardStats();
    loadStudentAttendance();
});

function switchTab(tab) {
    $('.tab-btn').removeClass('active');
    $('.tab-content').removeClass('active');
    $(`button:contains('${tab.charAt(0).toUpperCase() + tab.slice(1)}')`).addClass('active');
    $(`#${tab}-tab`).addClass('active');
    
    if(tab === 'staff') loadStaffAttendance();
}

function loadDashboardStats() {
    $.ajax({
        url: '<?php echo site_url('attendance/get_stats'); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('#stat-total').text(response.data.total);
            $('#stat-present').text(response.data.present);
            $('#stat-absent').text(response.data.absent);
            $('#stat-late').text(response.data.late);
            $('#present-percent').text(response.data.present_percent);
            $('#absent-percent').text(response.data.absent_percent);
        }
    });
}

function loadStudentAttendance() {
    $('#studentAttendanceTable').html('<div style="text-align: center; padding: 40px;"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    
    $.ajax({
        url: '<?php echo site_url('attendance/get_student_attendance_data'); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            renderStudentTable(response.data);
        }
    });
}

function renderStudentTable(data) {
    let html = '<table class="attendance-table"><thead><tr>';
    html += '<th>Student</th><th>Class</th><th>Status</th><th>Time</th><th>Actions</th>';
    html += '</tr></thead><tbody>';
    
    data.forEach(student => {
        html += `<tr>
            <td><strong>${student.name}</strong><br><small>${student.code}</small></td>
            <td>${student.class}</td>
            <td><span class="status-badge status-${student.status}">${student.status}</span></td>
            <td>${student.time || '-'}</td>
            <td><button class="btn-enterprise btn-primary" onclick="editAttendance(${student.id})"><i class="fa fa-edit"></i></button></td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    $('#studentAttendanceTable').html(html);
}

function markStudentAttendance() {
    navigation('<?php echo site_url('attendance/mark'); ?>');
}

function markStaffAttendance() {
    showAjaxModal_alert('Staff attendance module coming soon', 'warning');
}

function quickMarkAttendance() {
    loadModalContent('modal_ajax', '<?php echo site_url('attendance/quick_mark_attendance'); ?>', '<i class="fa fa-bolt"></i> Quick Mark Attendance');
}

function generateReport() {
    loadModalContent('modal_ajax', '<?php echo site_url('attendance/attendance_report_generator'); ?>', '<i class="fa fa-file-pdf-o"></i> Generate Report');
}

function viewReports() {
    navigation('<?php echo site_url('admin/attendance_report'); ?>');
}

function sendNotifications() {
    showConfirmModal(
        'Send Notifications',
        'Send absence notifications to all parents?',
        function() {
            showAjaxModal_alert('Sending notifications...', 'loading');
            $.ajax({
                url: '<?php echo site_url('attendance/send_absence_notifications'); ?>',
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                showAjaxModal_alert(response.message, response.status);
            });
        },
        'Send',
        'primary'
    );
}
</script>
