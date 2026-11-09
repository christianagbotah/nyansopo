<div class="row">
    <div class="col-md-12">
        
        <!-- Statistics Cards -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-3">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="entypo-mobile"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalAutomations">0</div>
                        <div class="stat-label"><?php echo get_phrase('total_automations'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="entypo-check"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="activeAutomations">0</div>
                        <div class="stat-label"><?php echo get_phrase('active'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="entypo-paper-plane"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalSent">0</div>
                        <div class="stat-label"><?php echo get_phrase('messages_sent'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="entypo-gauge"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="successRate">0%</div>
                        <div class="stat-label"><?php echo get_phrase('success_rate'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Panel -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="entypo-mobile"></i> <?php echo get_phrase('sms_automation'); ?></span>
                    <button class="btn-create-automation" onclick="showAjaxModal('<?php echo base_url(); ?>sms_automation/modal')">
                        <i class="entypo-plus"></i> <?php echo get_phrase('create_automation'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-body" style="padding: 20px;">
                
                <!-- Automations Grid -->
                <div id="automationsContainer" class="row"></div>

            </div>
        </div>

        <!-- Activity Chart -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="entypo-chart-line"></i> <?php echo get_phrase('activity_last_30_days'); ?></h4>
            </div>
            <div class="panel-body">
                <canvas id="activityChart" height="80"></canvas>
            </div>
        </div>

    </div>
</div>





<style>
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-right: 15px;
}

.stat-primary .stat-icon { background: #e3f2fd; color: #1976d2; }
.stat-success .stat-icon { background: #e8f5e9; color: #388e3c; }
.stat-info .stat-icon { background: #e1f5fe; color: #0288d1; }
.stat-warning .stat-icon { background: #fff3e0; color: #f57c00; }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
}

.automation-card {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.automation-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.automation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.automation-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.automation-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-active { background: #e8f5e9; color: #388e3c; }
.badge-inactive { background: #ffebee; color: #d32f2f; }

.automation-meta {
    display: flex;
    gap: 20px;
    margin: 15px 0;
    font-size: 13px;
    color: #666;
}

.automation-message {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    color: #555;
    margin: 15px 0;
    border-left: 3px solid #667eea;
}

.automation-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.btn-action {
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-test { background: #667eea; color: white; }
.btn-test:hover { background: #5568d3; }

.btn-logs { background: #0288d1; color: white; }
.btn-logs:hover { background: #0277bd; }

.btn-edit { background: #f59e0b; color: white; }
.btn-edit:hover { background: #d97706; }

.btn-toggle { background: #10b981; color: white; }
.btn-toggle:hover { background: #059669; }

.log-status {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-sent { background: #e8f5e9; color: #388e3c; }
.status-failed { background: #ffebee; color: #d32f2f; }
.status-pending { background: #fff3e0; color: #f57c00; }

.btn-create-automation {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-create-automation:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.btn-create-automation:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-create-automation i {
    font-size: 16px;
}
</style>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>

<script>
let automationsData = [];
let activityChart = null;

$(document).ready(function() {
    loadAutomations();
    loadStatistics();
});

function loadAutomations() {
    $.ajax({
        url: '<?php echo base_url(); ?>sms_automation/get_automations',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                automationsData = response.data;
                renderAutomations(automationsData);
            }
        }
    });
}

function renderAutomations(automations) {
    const container = $('#automationsContainer');
    container.empty();
    
    if (automations.length === 0) {
        container.html('<div class="col-md-12"><p class="text-center text-muted"><?php echo get_phrase('no_automations_found'); ?></p></div>');
        return;
    }
    
    automations.forEach(auto => {
        const card = `
            <div class="col-md-6">
                <div class="automation-card">
                    <div class="automation-header">
                        <div class="automation-title">${auto.name}</div>
                        <span class="automation-badge badge-${auto.is_active == 1 ? 'active' : 'inactive'}">
                            ${auto.is_active == 1 ? '<?php echo get_phrase('active'); ?>' : '<?php echo get_phrase('inactive'); ?>'}
                        </span>
                    </div>
                    
                    <div class="automation-meta">
                        <span><i class="entypo-flash"></i> ${auto.trigger_event.replace(/_/g, ' ')}</span>
                        <span><i class="entypo-users"></i> ${auto.recipients}</span>
                        <span><i class="entypo-paper-plane"></i> ${auto.total_sent} sent</span>
                    </div>
                    
                    <div class="automation-message">${auto.message_template}</div>
                    
                    <div class="automation-actions">
                        <button class="btn-action btn-test" onclick="showTestModal(${auto.id})">
                            <i class="entypo-paper-plane"></i> <?php echo get_phrase('test'); ?>
                        </button>
                        <button class="btn-action btn-logs" onclick="showLogs(${auto.id})">
                            <i class="entypo-list"></i> <?php echo get_phrase('logs'); ?>
                        </button>
                        <button class="btn-action btn-edit" onclick="showAjaxModal('<?php echo base_url(); ?>sms_automation/modal/${auto.id}')">
                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
                        </button>
                        <button class="btn-action btn-toggle" onclick="toggleStatus(${auto.id})">
                            <i class="entypo-${auto.is_active == 1 ? 'block' : 'check'}"></i> 
                            ${auto.is_active == 1 ? '<?php echo get_phrase('deactivate'); ?>' : '<?php echo get_phrase('activate'); ?>'}
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.append(card);
    });
}

function loadStatistics() {
    $.ajax({
        url: '<?php echo base_url(); ?>sms_automation/get_statistics',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const stats = response.data;
                $('#totalAutomations').text(stats.total_automations);
                $('#activeAutomations').text(stats.active_automations);
                $('#totalSent').text(stats.total_sent);
                $('#successRate').text(stats.success_rate + '%');
                
                renderActivityChart(stats.activity);
            }
        }
    });
}

function renderActivityChart(activity) {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    if (activityChart) {
        activityChart.destroy();
    }
    
    const labels = activity.map(a => a.date);
    const data = activity.map(a => a.count);
    
    activityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '<?php echo get_phrase('messages_sent'); ?>',
                data: data,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}



function toggleStatus(automationId) {
    showAjaxModal_alert('<?php echo get_phrase('updating'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo base_url(); ?>sms_automation/toggle_status/' + automationId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => {
                    loadAutomations();
                    loadStatistics();
                }, 2000);
            }
        }
    });
}

function showTestModal(automationId) {
    showAjaxModal('<?php echo base_url(); ?>sms_automation/test_modal/' + automationId);
}

function showLogs(automationId) {
    showAjaxModal('<?php echo base_url(); ?>sms_automation/logs_modal/' + automationId, 'big');
}

function showPlaceholders() {
    $.ajax({
        url: '<?php echo base_url(); ?>sms_automation/get_available_placeholders',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '<div style="padding: 15px;"><h5><?php echo get_phrase('available_placeholders'); ?>:</h5><ul>';
                for (let key in response.data) {
                    html += `<li><code>{${key}}</code> - ${response.data[key]}</li>`;
                }
                html += '</ul></div>';
                
                showAjaxModal_alert(html, 'success');
            }
        }
    });
}
</script>
