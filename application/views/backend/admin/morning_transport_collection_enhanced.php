<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
.mobile-container { max-width: 600px; margin: 0 auto; padding: 15px; }
.header { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.header h1 { color: #667eea; font-size: 24px; margin-bottom: 10px; }
.header .info { color: #666; font-size: 14px; }
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
.stat-card { background: white; border-radius: 15px; padding: 15px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.stat-card .value { font-size: 24px; font-weight: 700; margin: 5px 0; }
.stat-card .label { font-size: 12px; color: #666; }
.search-box { background: white; border-radius: 15px; padding: 15px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.search-box input { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; }
.student-list { max-height: calc(100vh - 400px); overflow-y: auto; }
.student-card { background: white; border-radius: 15px; padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.student-card .name { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 5px; }
.student-card .details { font-size: 14px; color: #666; margin-bottom: 10px; }
.student-card .route { background: #667eea; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; margin-bottom: 10px; }
.direction-btns { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 10px; }
.direction-btn { padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; background: white; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-align: center; }
.direction-btn.active { border-color: #667eea; background: #667eea; color: white; }
.fare-display { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; padding: 15px; text-align: center; margin-bottom: 10px; }
.fare-display .amount { font-size: 28px; font-weight: 700; }
.balance-info { background: #f0f0f0; border-radius: 10px; padding: 10px; margin-bottom: 10px; font-size: 13px; }
.balance-info.sufficient { background: #d4edda; color: #155724; }
.balance-info.insufficient { background: #f8d7da; color: #721c24; }
.action-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.btn { padding: 15px; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; }
.btn-primary { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn:active { transform: scale(0.95); }
.status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
.status-paid { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
</style>

<div class="mobile-container">
    <div class="header">
        <h1><i class="fa fa-bus"></i> Morning Transport</h1>
        <div class="info">
            <i class="fa fa-user"></i> <?php echo $this->session->userdata('name'); ?> | 
            <i class="fa fa-calendar"></i> <?php echo date('M j, Y'); ?> | 
            <i class="fa fa-clock"></i> <span id="current_time"><?php echo date('h:i A'); ?></span>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card" style="border-left: 4px solid #667eea;">
            <div class="value" id="stat_collected">GHS 0</div>
            <div class="label">Collected</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #38ef7d;">
            <div class="value" id="stat_students">0</div>
            <div class="label">Students</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f5576c;">
            <div class="value" id="stat_pending">0</div>
            <div class="label">Pending</div>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="search" placeholder="Search student by name or code..." autocomplete="off">
    </div>

    <div class="student-list" id="student_list"></div>
</div>

<script>
var students = [];
var filteredStudents = [];

$(document).ready(function() {
    loadStudents();
    loadStats();
    setInterval(updateTime, 1000);
    setInterval(loadStats, 30000);
    
    $('#search').on('input', function() {
        var query = $(this).val().toLowerCase();
        if (query.length === 0) {
            filteredStudents = students;
        } else {
            filteredStudents = students.filter(s => 
                s.name.toLowerCase().includes(query) || 
                s.student_code.toLowerCase().includes(query)
            );
        }
        displayStudents();
    });
});

function updateTime() {
    $('#current_time').text(new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
}

function loadStudents() {
    $.get('<?php echo site_url('transport/get_morning_students'); ?>', function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.status === 'success') {
            students = data.students;
            filteredStudents = students;
            displayStudents();
        }
    });
}

function displayStudents() {
    var html = '';
    filteredStudents.forEach(function(s) {
        html += '<div class="student-card" id="card_' + s.student_id + '">';
        html += '<div class="name">' + s.name + '</div>';
        html += '<div class="details">' + s.student_code + ' | ' + s.class_name + '</div>';
        html += '<div class="route">' + s.route_name + ' - GHS ' + s.route_fare + '</div>';
        
        if (s.recorded) {
            html += '<div class="status-badge status-paid"><i class="fa fa-check"></i> Recorded - ' + s.direction.toUpperCase() + '</div>';
        } else {
            html += '<div class="direction-btns">';
            html += '<button class="direction-btn" onclick="selectDirection(' + s.student_id + ', \'in\', ' + s.route_fare + ')">IN</button>';
            html += '<button class="direction-btn" onclick="selectDirection(' + s.student_id + ', \'out\', ' + s.route_fare + ')">OUT</button>';
            html += '<button class="direction-btn" onclick="selectDirection(' + s.student_id + ', \'both\', ' + (s.route_fare * 2) + ')">BOTH</button>';
            html += '<button class="direction-btn" onclick="selectDirection(' + s.student_id + ', \'none\', 0)">NONE</button>';
            html += '</div>';
            html += '<div id="details_' + s.student_id + '" style="display:none;"></div>';
        }
        
        html += '</div>';
    });
    
    $('#student_list').html(html || '<p style="text-align:center;color:white;padding:20px;">No students found</p>');
}

function selectDirection(studentId, direction, fare) {
    $('#card_' + studentId + ' .direction-btn').removeClass('active');
    event.currentTarget.classList.add('active');
    
    $.get('<?php echo site_url('transport/get_student_wallet'); ?>/' + studentId, function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        var balance = parseFloat(data.transport_balance || 0);
        var sufficient = balance >= fare;
        
        var html = '<div class="fare-display"><div class="amount">GHS ' + fare.toFixed(2) + '</div></div>';
        html += '<div class="balance-info ' + (sufficient ? 'sufficient' : 'insufficient') + '">';
        html += '<i class="fa fa-wallet"></i> Balance: GHS ' + balance.toFixed(2);
        if (sufficient) {
            html += ' <i class="fa fa-check-circle"></i> Sufficient';
        } else {
            html += ' <i class="fa fa-exclamation-triangle"></i> Collect Cash: GHS ' + (fare - balance).toFixed(2);
        }
        html += '</div>';
        html += '<div class="action-btns">';
        html += '<button class="btn btn-secondary" onclick="cancelSelection(' + studentId + ')">Cancel</button>';
        html += '<button class="btn btn-primary" onclick="recordDecision(' + studentId + ', \'' + direction + '\', ' + fare + ')">Record</button>';
        html += '</div>';
        
        $('#details_' + studentId).html(html).slideDown();
    });
}

function cancelSelection(studentId) {
    $('#card_' + studentId + ' .direction-btn').removeClass('active');
    $('#details_' + studentId).slideUp();
}

function recordDecision(studentId, direction, fare) {
    $.ajax({
        url: '<?php echo site_url('fee_collection/record_transport_decision'); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            direction: direction,
            collection_point: 'bus'
        },
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            $('#card_' + studentId).html('<div class="status-badge status-paid"><i class="fa fa-check"></i> Recorded - ' + direction.toUpperCase() + '</div>');
            loadStats();
            
            // Vibrate if supported
            if (navigator.vibrate) {
                navigator.vibrate(200);
            }
        } else {
            alert(response.message || 'Failed to record');
        }
    });
}

function loadStats() {
    $.get('<?php echo site_url('transport/get_morning_stats'); ?>', function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.status === 'success') {
            $('#stat_collected').text('GHS ' + data.stats.collected.toFixed(0));
            $('#stat_students').text(data.stats.students);
            $('#stat_pending').text(data.stats.pending);
        }
    });
}
</script>
