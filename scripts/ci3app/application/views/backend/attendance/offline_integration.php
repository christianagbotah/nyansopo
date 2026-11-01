<!-- Add to mark_attendance.php head section -->
<script src="<?php echo base_url('assets/js/attendance_offline.js'); ?>"></script>

<script>
// Offline-capable attendance saving
async function saveAttendanceOffline() {
    const records = [];
    
    $('.student-card').each(function() {
        const studentId = $(this).data('student-id');
        const status = $(this).find('.status-select').val();
        
        records.push({
            student_id: studentId,
            class_id: $('#class_id').val(),
            section_id: $('#section_id').val(),
            date: $('#attendance_date').val(),
            status: status,
            timestamp: new Date().toISOString()
        });
    });
    
    if (navigator.onLine) {
        // Online: Save to server
        saveToServer(records);
    } else {
        // Offline: Save to IndexedDB
        try {
            await AttendanceDB.save(records);
            showAjaxModal_alert('Saved offline. Will sync when online.', 'success', false);
        } catch (error) {
            showAjaxModal_alert('Failed to save offline: ' + error.message, 'error');
        }
    }
}

function saveToServer(records) {
    showAjaxModal_alert('Saving...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("attendance_enterprise/save"); ?>',
        type: 'POST',
        data: { records: records },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('Network error. Saving offline...', 'warning');
        AttendanceDB.save(records);
    });
}

// Network status indicator
function updateNetworkStatus() {
    const indicator = $('#network_status');
    if (navigator.onLine) {
        indicator.html('<i class="fa fa-wifi"></i> Online').css('color', '#10b981');
    } else {
        indicator.html('<i class="fa fa-wifi-slash"></i> Offline').css('color', '#ef4444');
    }
}

$(document).ready(function() {
    updateNetworkStatus();
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
});
</script>

<!-- Network status indicator -->
<div id="network_status" style="position: fixed; top: 10px; right: 10px; padding: 8px 15px; background: white; border-radius: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-weight: 600; z-index: 1000;">
    <i class="fa fa-wifi"></i> Online
</div>
