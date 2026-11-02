<style>
.transport-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; }
.transport-tabs button { padding: 15px 30px; border: none; background: none; cursor: pointer; font-size: 16px; font-weight: 600; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s; }
.transport-tabs button.active { color: #667eea; border-bottom-color: #667eea; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.student-list { max-height: 600px; overflow-y: auto; }
.student-item { background: white; border-radius: 10px; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
.student-item .info { flex: 1; }
.student-item .name { font-size: 16px; font-weight: 600; color: #333; }
.student-item .details { font-size: 13px; color: #666; margin-top: 5px; }
.student-item .actions { display: flex; gap: 10px; }
.btn-sm { padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.3s; }
.btn-primary { background: #667eea; color: white; }
.btn-success { background: #38ef7d; color: white; }
.btn-warning { background: #ffa502; color: white; }
.btn-danger { background: #f5576c; color: white; }
.btn-sm:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.balance-badge { padding: 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.balance-positive { background: #d4edda; color: #155724; }
.balance-negative { background: #f8d7da; color: #721c24; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-box { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
.stat-box .value { font-size: 28px; font-weight: 700; margin: 10px 0; }
.stat-box .label { color: #666; font-size: 14px; }
.filter-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.filter-bar select, .filter-bar input { padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; margin-right: 10px; }
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3"><i class="fa fa-bus"></i> <?php echo get_phrase('transport_management'); ?></h4>
                
                <div class="transport-tabs">
                    <button class="active" onclick="switchTab('daily')"><i class="fa fa-calendar-day"></i> <?php echo get_phrase('daily_operations'); ?></button>
                    <button onclick="switchTab('reconciliation')"><i class="fa fa-calculator"></i> <?php echo get_phrase('reconciliation'); ?></button>
                    <button onclick="switchTab('history')"><i class="fa fa-history"></i> <?php echo get_phrase('history'); ?></button>
                    <button onclick="switchTab('wallets')"><i class="fa fa-wallet"></i> <?php echo get_phrase('wallets'); ?></button>
                </div>

                <!-- Daily Operations Tab -->
                <div id="tab-daily" class="tab-content active">
                    <div class="stats-grid">
                        <div class="stat-box" style="border-left: 4px solid #667eea;">
                            <div class="label"><?php echo get_phrase('total_students'); ?></div>
                            <div class="value" id="stat_total_students">0</div>
                        </div>
                        <div class="stat-box" style="border-left: 4px solid #38ef7d;">
                            <div class="label"><?php echo get_phrase('recorded_today'); ?></div>
                            <div class="value" id="stat_recorded">0</div>
                        </div>
                        <div class="stat-box" style="border-left: 4px solid #ffa502;">
                            <div class="label"><?php echo get_phrase('boarded_in'); ?></div>
                            <div class="value" id="stat_boarded_in">0</div>
                        </div>
                        <div class="stat-box" style="border-left: 4px solid #f5576c;">
                            <div class="label"><?php echo get_phrase('boarded_out'); ?></div>
                            <div class="value" id="stat_boarded_out">0</div>
                        </div>
                    </div>

                    <div class="filter-bar">
                        <select id="filter_route" onchange="loadDailyOperations()">
                            <option value=""><?php echo get_phrase('all_routes'); ?></option>
                            <?php
                            $routes = $this->db->get('transport')->result_array();
                            foreach ($routes as $route):
                            ?>
                            <option value="<?php echo $route['transport_id']; ?>"><?php echo $route['route_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter_status" onchange="loadDailyOperations()">
                            <option value=""><?php echo get_phrase('all_status'); ?></option>
                            <option value="recorded"><?php echo get_phrase('recorded'); ?></option>
                            <option value="not_recorded"><?php echo get_phrase('not_recorded'); ?></option>
                            <option value="boarded"><?php echo get_phrase('boarded'); ?></option>
                        </select>
                        <button class="btn btn-primary" onclick="loadDailyOperations()"><i class="fa fa-sync"></i> <?php echo get_phrase('refresh'); ?></button>
                    </div>

                    <div class="student-list" id="daily_operations_list"></div>
                </div>

                <!-- Reconciliation Tab -->
                <div id="tab-reconciliation" class="tab-content">
                    <div class="filter-bar">
                        <input type="date" id="recon_date" value="<?php echo date('Y-m-d'); ?>" onchange="loadReconciliation()">
                        <select id="recon_collector" onchange="loadReconciliation()">
                            <option value=""><?php echo get_phrase('all_collectors'); ?></option>
                            <?php
                            $collectors = $this->db->query("
                                SELECT DISTINCT u.user_id, u.name, u.role 
                                FROM users u 
                                WHERE u.role IN ('admin', 'cashier', 'conductor', 'teacher')
                                ORDER BY u.name
                            ")->result_array();
                            foreach ($collectors as $collector):
                            ?>
                            <option value="<?php echo $collector['user_id']; ?>"><?php echo $collector['name']; ?> (<?php echo $collector['role']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" onclick="loadReconciliation()"><i class="fa fa-sync"></i> <?php echo get_phrase('refresh'); ?></button>
                    </div>

                    <div id="reconciliation_report"></div>
                </div>

                <!-- History Tab -->
                <div id="tab-history" class="tab-content">
                    <div class="filter-bar">
                        <input type="text" id="history_student" placeholder="<?php echo get_phrase('search_student'); ?>" style="width: 300px;">
                        <input type="date" id="history_from" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                        <input type="date" id="history_to" value="<?php echo date('Y-m-d'); ?>">
                        <button class="btn btn-primary" onclick="loadHistory()"><i class="fa fa-search"></i> <?php echo get_phrase('search'); ?></button>
                    </div>

                    <div id="history_list"></div>
                </div>

                <!-- Wallets Tab -->
                <div id="tab-wallets" class="tab-content">
                    <div class="filter-bar">
                        <select id="wallet_filter" onchange="loadWallets()">
                            <option value="all"><?php echo get_phrase('all_students'); ?></option>
                            <option value="positive"><?php echo get_phrase('positive_balance'); ?></option>
                            <option value="negative"><?php echo get_phrase('negative_balance'); ?></option>
                            <option value="zero"><?php echo get_phrase('zero_balance'); ?></option>
                        </select>
                        <input type="text" id="wallet_search" placeholder="<?php echo get_phrase('search_student'); ?>" style="width: 300px;">
                        <button class="btn btn-primary" onclick="loadWallets()"><i class="fa fa-search"></i> <?php echo get_phrase('search'); ?></button>
                    </div>

                    <div class="student-list" id="wallets_list"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    $('.transport-tabs button').removeClass('active');
    event.currentTarget.classList.add('active');
    $('.tab-content').removeClass('active');
    $('#tab-' + tab).addClass('active');
    
    if (tab === 'daily') loadDailyOperations();
    else if (tab === 'reconciliation') loadReconciliation();
    else if (tab === 'history') loadHistory();
    else if (tab === 'wallets') loadWallets();
}

function loadDailyOperations() {
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.get('<?php echo site_url('transport/get_daily_operations'); ?>', {
        route: $('#filter_route').val(),
        status: $('#filter_status').val()
    }, function(response) {
        $('.close')[0].click();
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            $('#stat_total_students').text(data.stats.total);
            $('#stat_recorded').text(data.stats.recorded);
            $('#stat_boarded_in').text(data.stats.boarded_in);
            $('#stat_boarded_out').text(data.stats.boarded_out);
            
            var html = '';
            data.students.forEach(function(s) {
                var statusBadge = '';
                if (s.recorded) {
                    statusBadge = '<span class="badge badge-success">' + s.direction.toUpperCase() + '</span>';
                    if (s.boarded_in) statusBadge += ' <span class="badge badge-info">IN</span>';
                    if (s.boarded_out) statusBadge += ' <span class="badge badge-info">OUT</span>';
                } else {
                    statusBadge = '<span class="badge badge-secondary">Not Recorded</span>';
                }
                
                html += '<div class="student-item">';
                html += '<div class="info">';
                html += '<div class="name">' + s.name + ' (' + s.student_code + ')</div>';
                html += '<div class="details">' + s.class_name + ' | ' + s.route_name + ' | GHS ' + s.route_fare + ' ' + statusBadge + '</div>';
                html += '</div>';
                html += '<div class="actions">';
                if (!s.recorded) {
                    html += '<button class="btn-sm btn-primary" onclick="quickRecord(' + s.student_id + ')"><i class="fa fa-plus"></i> Record</button>';
                } else {
                    if (!s.boarded_in && (s.direction === 'in' || s.direction === 'both')) {
                        html += '<button class="btn-sm btn-success" onclick="quickBoard(' + s.student_id + ', \'in\')"><i class="fa fa-check"></i> Board IN</button>';
                    }
                    if (!s.boarded_out && (s.direction === 'out' || s.direction === 'both')) {
                        html += '<button class="btn-sm btn-warning" onclick="quickBoard(' + s.student_id + ', \'out\')"><i class="fa fa-check"></i> Board OUT</button>';
                    }
                }
                html += '</div></div>';
            });
            
            $('#daily_operations_list').html(html || '<p class="text-center text-muted">No students found</p>');
        }
    });
}

function quickRecord(studentId) {
    // Open modal for recording
    window.location.href = '<?php echo site_url('fee_collection'); ?>?student=' + studentId;
}

function quickBoard(studentId, direction) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_boarding'); ?>',
        '<?php echo get_phrase('mark_student_as_boarded'); ?> ' + direction.toUpperCase() + '?',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('processing'); ?>...', 'loading');
            $.post('<?php echo site_url('fee_collection/mark_boarded'); ?>', {
                student_id: studentId,
                direction: direction
            }, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.status === 'success') {
                    showAjaxModal_alert(data.message, 'success');
                    setTimeout(() => loadDailyOperations(), 2000);
                } else {
                    showAjaxModal_alert(data.message, 'error');
                }
            });
        },
        '<?php echo get_phrase('confirm'); ?>',
        'success'
    );
}

function loadReconciliation() {
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.get('<?php echo site_url('transport/get_reconciliation'); ?>', {
        date: $('#recon_date').val(),
        collector: $('#recon_collector').val()
    }, function(response) {
        $('.close')[0].click();
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            var html = '<div class="stats-grid">';
            html += '<div class="stat-box" style="border-left: 4px solid #667eea;"><div class="label">Total Collected</div><div class="value">GHS ' + data.summary.total.toFixed(2) + '</div></div>';
            html += '<div class="stat-box" style="border-left: 4px solid #38ef7d;"><div class="label">From Prepaid</div><div class="value">GHS ' + data.summary.prepaid.toFixed(2) + '</div></div>';
            html += '<div class="stat-box" style="border-left: 4px solid #ffa502;"><div class="label">Cash Collected</div><div class="value">GHS ' + data.summary.cash.toFixed(2) + '</div></div>';
            html += '<div class="stat-box" style="border-left: 4px solid #f5576c;"><div class="label">Transactions</div><div class="value">' + data.summary.count + '</div></div>';
            html += '</div>';
            
            html += '<table class="table table-bordered mt-3"><thead><tr><th>Time</th><th>Student</th><th>Direction</th><th>Amount</th><th>Method</th><th>Collector</th></tr></thead><tbody>';
            data.transactions.forEach(function(t) {
                html += '<tr>';
                html += '<td>' + new Date(t.payment_time * 1000).toLocaleTimeString() + '</td>';
                html += '<td>' + t.student_name + '</td>';
                html += '<td>' + t.transport_direction.toUpperCase() + '</td>';
                html += '<td>GHS ' + parseFloat(t.total_fare).toFixed(2) + '</td>';
                html += '<td>' + t.payment_source + '</td>';
                html += '<td>' + t.collector_name + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            
            $('#reconciliation_report').html(html);
        }
    });
}

function loadHistory() {
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.get('<?php echo site_url('transport/get_history'); ?>', {
        student: $('#history_student').val(),
        from: $('#history_from').val(),
        to: $('#history_to').val()
    }, function(response) {
        $('.close')[0].click();
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            var html = '<table class="table table-bordered"><thead><tr><th>Date</th><th>Student</th><th>Route</th><th>Direction</th><th>Fare</th><th>Status</th><th>Collector</th></tr></thead><tbody>';
            data.history.forEach(function(h) {
                html += '<tr>';
                html += '<td>' + new Date(h.attendance_date * 1000).toLocaleDateString() + '</td>';
                html += '<td>' + h.student_name + '</td>';
                html += '<td>' + h.route_name + '</td>';
                html += '<td>' + h.transport_direction.toUpperCase() + '</td>';
                html += '<td>GHS ' + parseFloat(h.total_fare).toFixed(2) + '</td>';
                html += '<td><span class="badge badge-' + (h.payment_status === 'paid' ? 'success' : 'danger') + '">' + h.payment_status + '</span></td>';
                html += '<td>' + h.collector_name + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            
            $('#history_list').html(html);
        }
    });
}

function loadWallets() {
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.get('<?php echo site_url('transport/get_wallets'); ?>', {
        filter: $('#wallet_filter').val(),
        search: $('#wallet_search').val()
    }, function(response) {
        $('.close')[0].click();
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            var html = '';
            data.wallets.forEach(function(w) {
                var balanceClass = w.transport_balance > 0 ? 'balance-positive' : (w.transport_balance < 0 ? 'balance-negative' : '');
                html += '<div class="student-item">';
                html += '<div class="info">';
                html += '<div class="name">' + w.name + ' (' + w.student_code + ')</div>';
                html += '<div class="details">' + w.class_name + ' | Route: ' + (w.route_name || 'Not Assigned') + '</div>';
                html += '</div>';
                html += '<div class="actions">';
                html += '<span class="balance-badge ' + balanceClass + '">Balance: GHS ' + parseFloat(w.transport_balance).toFixed(2) + '</span>';
                if (w.transport_arrears > 0) {
                    html += '<span class="balance-badge balance-negative">Arrears: GHS ' + parseFloat(w.transport_arrears).toFixed(2) + '</span>';
                }
                html += '<button class="btn-sm btn-primary" onclick="topUpWallet(' + w.student_id + ')"><i class="fa fa-plus"></i> Top Up</button>';
                html += '</div></div>';
            });
            
            $('#wallets_list').html(html || '<p class="text-center text-muted">No students found</p>');
        }
    });
}

function topUpWallet(studentId) {
    window.location.href = '<?php echo site_url('fee_collection'); ?>?student=' + studentId;
}

$(document).ready(function() {
    loadDailyOperations();
});
</script>
