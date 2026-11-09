<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.reports-container { padding: 24px; background: #f8f9fa; min-height: 100vh; }
.reports-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3); }
.reports-header h1 { margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: white; }
.reports-header p { margin: 0; opacity: 0.9; font-size: 16px; }
.report-tabs { background: white; border-radius: 12px; padding: 8px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 8px; }
.tab-btn { flex: 1; padding: 16px; border: none; background: transparent; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s; color: #6b7280; }
.tab-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.tab-btn:hover:not(.active) { background: #f3f4f6; }
.filters-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.filters-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
.filter-group { flex: 1; min-width: 200px; }
.filter-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-select { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; height: 48px; }
.btn-generate { padding: 12px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s; height: 48px; }
.btn-generate:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.purple { border-color: #667eea; }
.stat-card.green { border-color: #10b981; }
.stat-card.orange { border-color: #f59e0b; }
.stat-card.blue { border-color: #3b82f6; }
.stat-value { font-size: 28px; font-weight: 700; margin: 8px 0; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; word-wrap: break-word; }
.report-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.report-card h3 { margin: 0 0 20px 0; font-size: 20px; font-weight: 700; color: #1a202c; }
.table { width: 100%; border-collapse: collapse; }
.table thead { background: #f9fafb; }
.table th { padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
.table td { padding: 16px; border-bottom: 1px solid #f3f4f6; }
.table tbody tr:hover { background: #f9fafb; }
.badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
.badge-purple { background: #ede9fe; color: #5b21b6; }
.badge-green { background: #d1fae5; color: #065f46; }
.badge-orange { background: #fef3c7; color: #92400e; }
.badge-blue { background: #dbeafe; color: #1e40af; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
.chart-container { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
.export-btn { padding: 12px 20px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; height: 48px; font-size: 15px; }
.export-btn:hover { background: #059669; }
</style>

<div class="reports-container">
    <div class="reports-header">
        <h1><i class="fa fa-chart-bar"></i> <?php echo get_phrase('discount_reports'); ?></h1>
        <p><?php echo get_phrase('comprehensive_discount_analytics_and_insights'); ?></p>
    </div>

    <div class="report-tabs">
        <button class="tab-btn active" onclick="switchTab('by_class')">
            <i class="fa fa-school"></i> <?php echo get_phrase('by_class'); ?>
        </button>
        <button class="tab-btn" onclick="switchTab('by_profile')">
            <i class="fa fa-tags"></i> <?php echo get_phrase('by_profile'); ?>
        </button>
        <button class="tab-btn" onclick="switchTab('by_student')">
            <i class="fa fa-user-graduate"></i> <?php echo get_phrase('by_student'); ?>
        </button>
    </div>

    <div class="filters-card">
        <div class="filters-row">
            <div class="filter-group">
                <label><?php echo get_phrase('academic_year'); ?></label>
                <select id="filterYear" class="filter-select">
                    <?php 
                    $years = populate_academic_year();
                    if($years && is_array($years)):
                        foreach($years as $year): 
                    ?>
                        <option value="<?php echo $year; ?>" <?php echo $year == get_settings('running_year') ? 'selected' : ''; ?>><?php echo $year; ?></option>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <option value="<?php echo get_settings('running_year'); ?>" selected><?php echo get_settings('running_year'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><?php echo get_phrase('term'); ?></label>
                <select id="filterTerm" class="filter-select">
                    <option value="1" <?php echo get_settings('running_term') == 1 ? 'selected' : ''; ?>><?php echo get_phrase('term_1'); ?></option>
                    <option value="2" <?php echo get_settings('running_term') == 2 ? 'selected' : ''; ?>><?php echo get_phrase('term_2'); ?></option>
                    <option value="3" <?php echo get_settings('running_term') == 3 ? 'selected' : ''; ?>><?php echo get_phrase('term_3'); ?></option>
                </select>
            </div>
            <button class="btn-generate" onclick="generateReport()">
                <i class="fa fa-sync"></i> <?php echo get_phrase('generate_report'); ?>
            </button>
            <button class="export-btn" onclick="exportReport()">
                <i class="fa fa-download"></i> <?php echo get_phrase('export'); ?>
            </button>
        </div>
    </div>

    <div class="stats-grid" id="statsGrid" style="display:none;">
        <div class="stat-card purple">
            <div class="stat-label"><?php echo get_phrase('total_records'); ?></div>
            <div class="stat-value" id="stat-total">0</div>
        </div>
        <div class="stat-card green">
            <div class="stat-label"><?php echo get_phrase('total_students'); ?></div>
            <div class="stat-value" id="stat-students">0</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label"><?php echo get_phrase('avg_discount'); ?></div>
            <div class="stat-value" id="stat-avg">0%</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label"><?php echo get_phrase('total_discount'); ?></div>
            <div class="stat-value" id="stat-sum">0%</div>
        </div>
    </div>

    <div class="report-card">
        <h3 id="reportTitle"><?php echo get_phrase('select_report_type_and_generate'); ?></h3>
        <div id="reportContent">
            <div class="empty-state">
                <i class="fa fa-chart-line"></i>
                <div><?php echo get_phrase('click_generate_to_view_report'); ?></div>
            </div>
        </div>
    </div>
</div>

<script>
let currentTab = 'by_class';
let reportData = [];

function switchTab(tab) {
    currentTab = tab;
    $('.tab-btn').removeClass('active');
    event.target.closest('.tab-btn').classList.add('active');
    $('#reportContent').html(`
        <div class="empty-state">
            <i class="fa fa-chart-line"></i>
            <div><?php echo get_phrase('click_generate_to_view_report'); ?></div>
        </div>
    `);
    $('#statsGrid').hide();
}

function generateReport() {
    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/discount_reports/get_data'); ?>',
        type: 'GET',
        data: {
            type: currentTab,
            year: $('#filterYear').val(),
            term: $('#filterTerm').val()
        },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            reportData = response.data;
            renderReport();
            $('.close').click();
        } else {
            showAjaxModal_alert('<?php echo get_phrase('failed_to_generate_report'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
    });
}

function renderReport() {
    if(reportData.length === 0) {
        $('#reportContent').html(`
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <div><?php echo get_phrase('no_data_found'); ?></div>
            </div>
        `);
        $('#statsGrid').hide();
        return;
    }

    if(currentTab === 'by_class') {
        renderClassReport();
    } else if(currentTab === 'by_profile') {
        renderProfileReport();
    } else if(currentTab === 'by_student') {
        renderStudentReport();
    }
    
    $('#statsGrid').show();
}

function renderClassReport() {
    $('#reportTitle').html('<i class="fa fa-school"></i> <?php echo get_phrase('discount_report_by_class'); ?>');
    
    let totalStudents = reportData.reduce((sum, item) => sum + parseInt(item.student_count), 0);
    let avgDiscount = reportData.reduce((sum, item) => sum + parseFloat(item.avg_discount), 0) / reportData.length;
    let totalDiscount = reportData.reduce((sum, item) => sum + parseFloat(item.total_discount), 0);
    
    $('#stat-total').text(reportData.length);
    $('#stat-students').text(totalStudents);
    $('#stat-avg').text(avgDiscount.toFixed(1) + '%');
    $('#stat-sum').text(totalDiscount.toFixed(0) + '%');
    
    let html = `
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo get_phrase('class'); ?></th>
                    <th><?php echo get_phrase('students_with_discount'); ?></th>
                    <th><?php echo get_phrase('average_discount'); ?></th>
                    <th><?php echo get_phrase('total_discount'); ?></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    reportData.forEach(item => {
        html += `
            <tr>
                <td><span class="badge badge-purple">${item.class_name} ${item.name_numeric} ${item.section_name || ''}</span></td>
                <td><strong>${item.student_count}</strong></td>
                <td><span class="badge badge-green">${parseFloat(item.avg_discount).toFixed(1)}%</span></td>
                <td><span class="badge badge-orange">${parseFloat(item.total_discount).toFixed(0)}%</span></td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    $('#reportContent').html(html);
}

function renderProfileReport() {
    $('#reportTitle').html('<i class="fa fa-tags"></i> <?php echo get_phrase('discount_report_by_profile'); ?>');
    
    let totalStudents = reportData.reduce((sum, item) => sum + parseInt(item.student_count || 0), 0);
    let totalAssignments = reportData.reduce((sum, item) => sum + parseInt(item.assignment_count || 0), 0);
    let avgDiscount = reportData.reduce((sum, item) => sum + parseFloat(item.discount_value || 0), 0) / reportData.length;
    
    $('#stat-total').text(reportData.length);
    $('#stat-students').text(totalStudents);
    $('#stat-avg').text(avgDiscount.toFixed(1) + '%');
    $('#stat-sum').text(totalAssignments);
    $('.stat-card.blue .stat-label').text('<?php echo get_phrase('total_assignments'); ?>');
    
    let html = `
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo get_phrase('profile_name'); ?></th>
                    <th><?php echo get_phrase('category'); ?></th>
                    <th><?php echo get_phrase('discount_value'); ?></th>
                    <th><?php echo get_phrase('students'); ?></th>
                    <th><?php echo get_phrase('assignments'); ?></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    reportData.forEach(item => {
        let category = item.discount_category ? item.discount_category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
        let discountValue = item.discount_method === 'percentage' 
            ? parseFloat(item.discount_value).toFixed(1) + '%'
            : 'GHS ' + parseFloat(item.discount_value).toFixed(2);
        
        html += `
            <tr>
                <td><strong>${item.profile_name}</strong></td>
                <td><span class="badge badge-purple">${category}</span></td>
                <td><span class="badge badge-green">${discountValue}</span></td>
                <td><strong>${item.student_count || 0}</strong></td>
                <td><strong>${item.assignment_count || 0}</strong></td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    $('#reportContent').html(html);
}

function renderStudentReport() {
    $('#reportTitle').html('<i class="fa fa-user-graduate"></i> <?php echo get_phrase('discount_report_by_student'); ?>');
    
    let uniqueStudents = [...new Set(reportData.map(item => item.student_id))].length;
    let avgDiscount = reportData.reduce((sum, item) => sum + parseFloat(item.discount_value), 0) / reportData.length;
    let totalBenefitAmount = reportData.reduce((sum, item) => sum + parseFloat(item.total_benefit_amount || 0), 0);
    
    $('#stat-total').text(reportData.length);
    $('#stat-students').text(uniqueStudents);
    $('#stat-avg').text(avgDiscount.toFixed(1) + '%');
    $('#stat-sum').text(totalBenefitAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#stat-sum').prepend('<sup style="font-size: 14px; vertical-align: super;">GHS</sup> ');
    $('.stat-card.blue .stat-label').text('<?php echo get_phrase('total_benefit'); ?>');
    
    let html = `
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo get_phrase('student'); ?></th>
                    <th><?php echo get_phrase('class'); ?></th>
                    <th><?php echo get_phrase('profile'); ?></th>
                    <th><?php echo get_phrase('category'); ?></th>
                    <th><?php echo get_phrase('discount_value'); ?></th>
                    <th><?php echo get_phrase('total_benefit'); ?></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    reportData.forEach((item, index) => {
        let category = item.discount_category ? item.discount_category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
        
        let discountValue = item.discount_method === 'percentage' 
            ? parseFloat(item.discount_value).toFixed(1) + '%'
            : 'GHS ' + parseFloat(item.discount_value).toFixed(2);
        
        html += `
            <tr>
                <td><strong>${index + 1}</strong></td>
                <td>
                    <div style="font-weight: 600;">${item.student_name}</div>
                    <div style="font-size: 12px; color: #6b7280;">${item.student_code}</div>
                </td>
                <td><span class="badge badge-purple">${item.class_name}</span></td>
                <td><strong>${item.profile_name}</strong></td>
                <td><span class="badge badge-blue">${category}</span></td>
                <td><span class="badge badge-green">${discountValue}</span></td>
                <td><span class="badge badge-orange">GHS ${parseFloat(item.total_benefit_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot>
                <tr style="background: #f9fafb; font-weight: 700;">
                    <td colspan="6" style="text-align: right; padding: 16px;">GRAND TOTAL:</td>
                    <td><span class="badge badge-orange" style="font-size: 14px;">GHS ${totalBenefitAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></td>
                </tr>
            </tfoot>
        </table>
    `;
    $('#reportContent').html(html);
}

function exportReport() {
    if(reportData.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('no_data_to_export'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('generating_excel'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/discount_reports_export'); ?>',
        type: 'POST',
        data: {
            type: currentTab,
            data: JSON.stringify(reportData),
            year: $('#filterYear').val(),
            term: $('#filterTerm').val()
        },
        xhrFields: {
            responseType: 'blob'
        }
    }).done(function(blob) {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `discount_report_${currentTab}_${new Date().getTime()}.xlsx`;
        a.click();
        window.URL.revokeObjectURL(url);
        $('.close').click();
        showAjaxModal_alert('<?php echo get_phrase('report_exported_successfully'); ?>', 'success', false);
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('export_failed'); ?>', 'error');
    });
}
</script>






