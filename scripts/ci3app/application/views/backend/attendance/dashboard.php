<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.enterprise-container { background: #f8fafc; min-height: 100vh; padding: 24px; max-width: 1400px; margin: 0 auto; }
.dashboard-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
.stat-card.present { border-color: #10b981; }
.stat-card.absent { border-color: #ef4444; }
.stat-card.late { border-color: #f59e0b; }
.stat-card.total { border-color: #667eea; }
.stat-value { font-size: 36px; font-weight: 700; margin: 8px 0; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.action-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); cursor: pointer; transition: all 0.2s; text-align: center; }
.action-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.action-icon { font-size: 32px; margin-bottom: 12px; }
.btn-enterprise { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-success { background: #10b981; color: white; }
</style>

<?php
$login_type = $this->session->userdata('login_type');
$user_id = $this->session->userdata('login_user_id');
$user_name = '';
if($login_type == 'admin') {
    $user_name = $this->db->get_where('admin', ['admin_id' => $user_id])->row()->name;
} else {
    $user_name = $this->db->get_where('teacher', ['teacher_id' => $user_id])->row()->name;
}
?>
<div class="enterprise-container">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: white;">
                    <i class="fa fa-calendar-check"></i> <?php echo get_phrase('attendance_management'); ?>
                </h1>
                <p style="margin: 0; opacity: 0.9; font-size: 16px; color: white;">
                    <?php echo date('l, F j, Y'); ?> • <?php echo $user_name; ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button class="btn-enterprise btn-success" onclick="quickMarkAttendance()">
                    <i class="fa fa-bolt"></i> <?php echo get_phrase('quick_mark'); ?>
                </button>
                <?php if($login_type == 'admin'): ?>
                <button class="btn-enterprise btn-primary" onclick="generateReport()">
                    <i class="fa fa-file-text"></i> <?php echo get_phrase('generate_report'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="stats-grid" id="statsGrid">
        <div class="stat-card total" onclick="showBreakdown('total')" style="cursor: pointer;">
            <div class="stat-label"><?php echo get_phrase('total_expected'); ?></div>
            <div class="stat-value" id="stat-total">0</div>
            <div style="color: #6b7280; font-size: 13px;"><?php echo get_phrase('students'); ?></div>
        </div>
        <div class="stat-card present" onclick="showBreakdown('present')" style="cursor: pointer;">
            <div class="stat-label"><?php echo get_phrase('present'); ?></div>
            <div class="stat-value" id="stat-present">0</div>
            <div style="color: #6b7280; font-size: 13px;"><span id="present-percent">0</span>% <?php echo get_phrase('attendance'); ?></div>
        </div>
        <div class="stat-card absent" onclick="showBreakdown('absent')" style="cursor: pointer;">
            <div class="stat-label"><?php echo get_phrase('absent'); ?> + <?php echo get_phrase('sick'); ?></div>
            <div class="stat-value" id="stat-absent">0</div>
            <div style="color: #6b7280; font-size: 13px;"><span id="absent-percent">0</span>% <?php echo get_phrase('not_present'); ?></div>
        </div>
        <div class="stat-card late" onclick="showBreakdown('late')" style="cursor: pointer;">
            <div class="stat-label"><?php echo get_phrase('late_arrivals'); ?></div>
            <div class="stat-value" id="stat-late">0</div>
            <div style="color: #6b7280; font-size: 13px;"><?php echo get_phrase('needs_follow_up'); ?></div>
        </div>
    </div>

    <div class="quick-actions">
        <div class="action-card" onclick="markStudentAttendance()">
            <div class="action-icon" style="color: #667eea;"><i class="fa fa-users"></i></div>
            <div style="font-weight: 600; font-size: 16px;"><?php echo get_phrase('student_attendance'); ?></div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;"><?php echo get_phrase('mark_by_class'); ?></div>
        </div>
        <?php if($login_type == 'admin'): ?>
        <div class="action-card" onclick="viewReports()">
            <div class="action-icon" style="color: #f59e0b;"><i class="fa fa-bar-chart"></i></div>
            <div style="font-weight: 600; font-size: 16px;"><?php echo get_phrase('analytics'); ?></div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;"><?php echo get_phrase('view_trends'); ?></div>
        </div>
        <div class="action-card" onclick="sendNotifications()">
            <div class="action-icon" style="color: #ef4444;"><i class="fa fa-bell"></i></div>
            <div style="font-weight: 600; font-size: 16px;"><?php echo get_phrase('notifications'); ?></div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;"><?php echo get_phrase('alert_parents'); ?></div>
        </div>
        <div class="action-card" onclick="exportData()">
            <div class="action-icon" style="color: #10b981;"><i class="fa fa-download"></i></div>
            <div style="font-weight: 600; font-size: 16px;"><?php echo get_phrase('export_data'); ?></div>
            <div style="color: #6b7280; font-size: 13px; margin-top: 4px;"><?php echo get_phrase('excel_pdf'); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
let statsData = {};

$(document).ready(function() {
    loadDashboardStats();
});

function loadDashboardStats() {
    $.ajax({
        url: '<?php echo site_url('attendance/get_stats'); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            statsData = response.data;
            $('#stat-total').text(response.data.total);
            $('#stat-present').text(response.data.present);
            $('#stat-absent').text(response.data.absent);
            $('#stat-late').text(response.data.late);
            $('#present-percent').text(response.data.present_percent);
            $('#absent-percent').text(response.data.absent_percent);
        }
    });
}

function showBreakdown(type) {
    if(!statsData.by_class || statsData.by_class.length === 0) return;
    
    let title = '';
    let content = '<style>';
    content += '.modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }';
    content += '.modern-table thead th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }';
    content += '.modern-table thead th:first-child { border-radius: 8px 0 0 0; }';
    content += '.modern-table thead th:last-child { border-radius: 0 8px 0 0; }';
    content += '.modern-table tbody tr { transition: all 0.2s; }';
    content += '.modern-table tbody tr:hover { background: #f3f4f6; }';
    content += '.modern-table tbody td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }';
    content += '.modern-table tbody tr:last-child td:first-child { border-radius: 0 0 0 8px; }';
    content += '.modern-table tbody tr:last-child td:last-child { border-radius: 0 0 8px 0; }';
    content += '.badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }';
    content += '.badge-success { background: #d1fae5; color: #065f46; }';
    content += '.badge-danger { background: #fee2e2; color: #991b1b; }';
    content += '.badge-warning { background: #fef3c7; color: #92400e; }';
    content += '.badge-info { background: #dbeafe; color: #1e40af; }';
    content += '</style>';
    content += '<div style="max-height: 500px; overflow-y: auto; padding: 4px;">';
    
    if(type === 'total') {
        title = '<i class="fa fa-users"></i> Total Students by Class';
        content += '<table class="modern-table"><thead><tr><th>Class</th><th style="text-align: center;">Total Students</th></tr></thead><tbody>';
        statsData.by_class.forEach(c => {
            content += `<tr><td><strong>${c.class_name}</strong></td><td style="text-align: center;"><span class="badge badge-info">${c.total}</span></td></tr>`;
        });
    } else if(type === 'present') {
        title = '<i class="fa fa-check-circle" style="color: #10b981;"></i> Present Students by Class';
        content += '<table class="modern-table"><thead><tr><th>Class</th><th style="text-align: center;">Count</th><th style="text-align: center;">Rate</th></tr></thead><tbody>';
        statsData.by_class.forEach(c => {
            const pct = c.total > 0 ? ((c.present / c.total) * 100).toFixed(1) : 0;
            let students = '';
            if(c.present_students && c.present_students.length > 0) {
                students = '<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 4px;">';
                c.present_students.forEach(name => {
                    students += `<span style="background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${name}</span>`;
                });
                students += '</div>';
            }
            content += `<tr><td><strong>${c.class_name}</strong>${students}</td><td style="text-align: center;"><span class="badge badge-success">${c.present}/${c.total}</span></td><td style="text-align: center;"><strong>${pct}%</strong></td></tr>`;
        });
    } else if(type === 'absent') {
        title = '<i class="fa fa-times-circle" style="color: #ef4444;"></i> Absent Students Breakdown';
        content += '<table class="modern-table"><thead><tr><th>Class</th><th style="text-align: center;">Absent</th><th style="text-align: center;">Sick-Home</th><th style="text-align: center;">Sick-Clinic</th></tr></thead><tbody>';
        statsData.by_class.forEach(c => {
            let students = '<div style="margin-top: 8px;">';
            if(c.absent_students && c.absent_students.length > 0) {
                students += '<div style="margin-bottom: 4px;"><strong style="font-size: 11px; color: #6b7280;">Absent:</strong></div>';
                students += '<div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">';
                c.absent_students.forEach(name => {
                    students += `<span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${name}</span>`;
                });
                students += '</div>';
            }
            if(c.sick_home_students && c.sick_home_students.length > 0) {
                students += '<div style="margin-bottom: 4px;"><strong style="font-size: 11px; color: #6b7280;">Sick-Home:</strong></div>';
                students += '<div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">';
                c.sick_home_students.forEach(name => {
                    students += `<span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${name}</span>`;
                });
                students += '</div>';
            }
            if(c.sick_clinic_students && c.sick_clinic_students.length > 0) {
                students += '<div style="margin-bottom: 4px;"><strong style="font-size: 11px; color: #6b7280;">Sick-Clinic:</strong></div>';
                students += '<div style="display: flex; flex-wrap: wrap; gap: 4px;">';
                c.sick_clinic_students.forEach(name => {
                    students += `<span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${name}</span>`;
                });
                students += '</div>';
            }
            students += '</div>';
            content += `<tr>`;
            content += `<td><strong>${c.class_name}</strong>${students}</td>`;
            content += `<td style="text-align: center;"><span class="badge badge-danger">${c.absent}</span></td>`;
            content += `<td style="text-align: center;"><span class="badge badge-warning">${c.sick_home}</span></td>`;
            content += `<td style="text-align: center;"><span class="badge badge-info">${c.sick_clinic}</span></td>`;
            content += `</tr>`;
        });
    } else if(type === 'late') {
        title = '<i class="fa fa-clock" style="color: #f59e0b;"></i> Late Arrivals by Class';
        content += '<table class="modern-table"><thead><tr><th>Class</th><th style="text-align: center;">Count</th></tr></thead><tbody>';
        statsData.by_class.forEach(c => {
            let students = '';
            if(c.late_students && c.late_students.length > 0) {
                students = '<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 4px;">';
                c.late_students.forEach(name => {
                    students += `<span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${name}</span>`;
                });
                students += '</div>';
            }
            content += `<tr><td><strong>${c.class_name}</strong>${students}</td><td style="text-align: center;"><span class="badge badge-warning">${c.late}</span></td></tr>`;
        });
    }
    
    content += '</tbody></table></div>';
    showModalWithContent('detailsModal', title, content);
}

function markStudentAttendance() {
    loadModalContent('modal_ajax', '<?php echo site_url('attendance/quick_mark_modal'); ?>', '<i class="fa fa-bolt"></i> <?php echo get_phrase('quick_mark_attendance'); ?>');
}

function quickMarkAttendance() {
    loadModalContent('modal_ajax', '<?php echo site_url('attendance/quick_mark_modal'); ?>', '<i class="fa fa-bolt"></i> <?php echo get_phrase('quick_mark_attendance'); ?>');
}

function generateReport() {
    window.location.href = '<?php echo site_url('attendance/report'); ?>';
}

function viewReports() {
    window.location.href = '<?php echo site_url('attendance/report'); ?>';
}

function sendNotifications() {
    showConfirmModal(
        '<?php echo get_phrase('send_notifications'); ?>',
        '<?php echo get_phrase('send_absence_notifications_to_all_parents'); ?>?',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('sending_notifications'); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url('attendance/send_notifications'); ?>',
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                showAjaxModal_alert(response.message, response.status);
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase('operation_failed'); ?>', 'error');
            });
        },
        '<?php echo get_phrase('send'); ?>',
        'primary'
    );
}

function exportData() {
    loadModalContent('modal_ajax', '<?php echo site_url('attendance/export_modal'); ?>', '<i class="fa fa-download"></i> <?php echo get_phrase('export_attendance_data'); ?>');
}
</script>
