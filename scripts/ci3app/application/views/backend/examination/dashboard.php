<?php
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
?>

<style>
.dashboard-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.dashboard-header h1 {
    color: #2d3748;
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
}

.dashboard-header p {
    color: #718096;
    font-size: 1rem;
    margin: 0.5rem 0 0 0;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    border-left: 4px solid #3182ce;
    transition: all 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-left-color: #2c5282;
}

.stat-card:nth-child(2) .stat-card {
    border-left-color: #38a169;
}

.stat-card:nth-child(2) .stat-card:hover {
    border-left-color: #2f855a;
}

.stat-card:nth-child(3) .stat-card {
    border-left-color: #d69e2e;
}

.stat-card:nth-child(3) .stat-card:hover {
    border-left-color: #b7791f;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    margin-bottom: 1rem;
}

.stat-icon.primary { background: #3182ce; }
.stat-icon.info { background: #38a169; }
.stat-icon.warning { background: #d69e2e; }

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: #718096;
    font-size: 0.85rem;
    font-weight: 500;
    margin: 0.5rem 0 0 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.content-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    border-left: 4px solid #3182ce;
    height: 100%;
}

.content-card h3 {
    color: #2d3748;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 1.5rem 0;
}

.exam-card {
    background: #f7fafc;
    border-radius: 8px;
    padding: 0.875rem;
    margin-bottom: 0.75rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.exam-card:hover {
    background: #edf2f7;
    border-color: #cbd5e0;
}

.exam-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 0.25rem 0;
}

.exam-meta {
    color: #718096;
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
}

.exam-status {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-upcoming { background: #fed7d7; color: #c53030; }
.status-completed { background: #c6f6d5; color: #2f855a; }

.progress-bar {
    background: #e2e8f0;
    border-radius: 6px;
    height: 4px;
    overflow: hidden;
    margin: 0.5rem 0 0.25rem 0;
}

.progress-fill {
    background: #3182ce;
    height: 100%;
    border-radius: 8px;
    transition: width 0.3s ease;
}

.action-btn {
    background: #3182ce;
    color: white;
    border: none;
    padding: 0.4rem 0.875rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    font-size: 0.8rem;
}

.action-btn:hover {
    background: #2c5282;
    color: white;
    text-decoration: none;
}

.quick-action-btn {
    display: block;
    width: 100%;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    padding: 0.875rem;
    border-radius: 8px;
    text-align: left;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #2d3748;
}

.quick-action-btn:hover {
    background: #edf2f7;
    border-color: #3182ce;
    color: #2d3748;
    text-decoration: none;
    transform: translateX(5px);
}

.quick-action-btn i {
    color: #3182ce;
    margin-right: 0.75rem;
    width: 18px;
}

.loading-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(59,130,246,.3);
    border-radius: 50%;
    border-top-color: #3b82f6;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #718096;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fa fa-graduation-cap"></i> Examination Management</h1>
                <p>Academic Year <?php echo $running_year; ?> • <?php echo $running_term; ?></p>
            </div>
            <div class="col-md-4 text-right">
                <button class="action-btn" onclick="refreshDashboard()">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="stats-container">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fa fa-clipboard-list"></i>
                </div>
                <h2 class="stat-number" id="total-exams">-</h2>
                <p class="stat-label">Total Exams</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h2 class="stat-number" id="completed-exams">-</h2>
                <p class="stat-label">Completed Exams</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fa fa-users"></i>
                </div>
                <h2 class="stat-number" id="total-students">-</h2>
                <p class="stat-label">Total Students</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Exams -->
        <div class="col-md-8">
            <div class="content-card">
                <h3><i class="fa fa-clock"></i> Recent Examinations</h3>
                <div id="recent-exams-container">
                    <div class="text-center py-4">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading examinations...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="content-card">
                <h3><i class="fa fa-bolt"></i> Quick Actions</h3>
                
                <a href="<?php echo site_url('admin/exam#add'); ?>" class="quick-action-btn">
                    <i class="fa fa-plus-circle"></i>
                    Create New Exam
                </a>
                
                <a href="<?php echo site_url('admin/marks_manage'); ?>" class="quick-action-btn">
                    <i class="fa fa-edit"></i>
                    Manage Marks
                </a>
                
                <a href="<?php echo site_url('admin/tabulation_sheet'); ?>" class="quick-action-btn">
                    <i class="fa fa-table"></i>
                    Tabulation Sheet
                </a>
                
                <a href="<?php echo site_url('admin/exam'); ?>" class="quick-action-btn">
                    <i class="fa fa-list"></i>
                    All Examinations
                </a>
                
                <a href="<?php echo site_url('admin/grade'); ?>" class="quick-action-btn">
                    <i class="fa fa-award"></i>
                    Grading System
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadDashboardData();
});

function loadDashboardData() {
    $.ajax({
        url: '<?php echo site_url("examination/get_dashboard_data"); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                updateStats(response.stats);
                updateRecentExams(response.recent_exams);
            }
        },
        error: function() {
            $('#recent-exams-container').html('<div class="empty-state"><i class="fa fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
        }
    });
}

function updateStats(stats) {
    console.log('Dashboard Stats:', stats);
    
    $('#total-exams').text(stats.total_exams || 0);
    $('#completed-exams').text(stats.completed_exams || 0);
    $('#total-students').text(stats.total_students || 0);
    
    if(stats.debug_info) {
        console.log('Debug Info:', stats.debug_info);
        console.log('Search Year:', stats.debug_info.search_year);
        console.log('Search Term:', stats.debug_info.search_term);
        console.log('All Exams in DB:', stats.debug_info.all_exams);
    }
}

function updateRecentExams(exams) {
    const container = $('#recent-exams-container');
    
    if(!exams || exams.length === 0) {
        container.html('<div class="empty-state"><i class="fa fa-clipboard-list"></i><p>No examinations found for this term</p></div>');
        return;
    }
    
    let html = '';
    exams.forEach(function(exam) {
        const examDate = new Date(exam.date * 1000);
        const now = new Date();
        const isUpcoming = examDate > now;
        
        let statusClass = 'status-completed';
        let statusText = 'Completed';
        
        if(isUpcoming) {
            statusClass = 'status-upcoming';
            statusText = 'Upcoming';
        }
        
        html += `
            <div class="exam-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="exam-title">${exam.name}</h4>
                        <div class="exam-meta">
                            <i class="fa fa-calendar"></i> ${examDate.toLocaleDateString()}
                            <span class="ml-3"><i class="fa fa-clock"></i> ${exam.year} - ${exam.term}</span>
                        </div>
                    </div>
                    <div class="ml-3 d-flex align-items-center">
                        <small class="text-muted mr-3" style="font-size: 0.7rem; white-space: nowrap;">Progress: ${exam.progress || 0}%</small>
                        <span class="exam-status ${statusClass} mr-3">${statusText}</span>
                        <a href="<?php echo site_url('admin/marks_manage/'); ?>${exam.exam_id}" class="action-btn">
                            <i class="fa fa-edit"></i> Manage
                        </a>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function refreshDashboard() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="loading-spinner"></div> Refreshing...';
    btn.disabled = true;
    
    loadDashboardData();
    
    setTimeout(function() {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}
</script>