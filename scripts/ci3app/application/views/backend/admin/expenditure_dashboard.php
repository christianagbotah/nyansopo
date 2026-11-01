<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fef2f2; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.dashboard-container { max-width:1600px; margin:0 auto; padding:24px; }
.page-header { background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius:12px; padding:32px; margin-bottom:32px; box-shadow:0 4px 20px rgba(220,38,38,0.2); color:white; }
.page-title { font-size:32px; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:16px; }
.page-subtitle { font-size:16px; opacity:0.95; }
.stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:24px; margin-bottom:32px; }
@media (max-width: 1200px) { .stats-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 768px) { .stats-grid { grid-template-columns:1fr; } }
.stat-card { background:white; border-radius:12px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,0.08); position:relative; overflow:hidden; transition:all 0.3s; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,0.12); }
.stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; }
.stat-card.red::before { background:#dc2626; }
.stat-card.blue::before { background:#3b82f6; }
.stat-card.green::before { background:#10b981; }
.stat-card.orange::before { background:#f59e0b; }
.stat-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; }
.stat-icon { width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:28px; }
.stat-icon.red { background:#fee2e2; color:#dc2626; }
.stat-icon.blue { background:#dbeafe; color:#3b82f6; }
.stat-icon.green { background:#d1fae5; color:#10b981; }
.stat-icon.orange { background:#fef3c7; color:#f59e0b; }
.stat-label { font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
.stat-value { font-size:36px; font-weight:700; color:#111827; margin-bottom:8px; }
.stat-change { font-size:13px; font-weight:600; display:flex; align-items:center; gap:4px; }
.stat-change.up { color:#dc2626; }
.stat-change.down { color:#10b981; }
.chart-grid { display:grid; grid-template-columns:2fr 1fr; gap:24px; margin-bottom:32px; }
.chart-card { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
.chart-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; }
.quick-actions { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:32px; }
.action-btn { background:white; border:2px solid #fee2e2; border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:all 0.3s; text-decoration:none; display:block; }
.action-btn:hover { border-color:#dc2626; background:#fef2f2; transform:translateY(-2px); box-shadow:0 4px 12px rgba(220,38,38,0.15); }
.action-btn i { font-size:32px; color:#dc2626; margin-bottom:12px; display:block; }
.action-btn span { font-size:14px; font-weight:600; color:#111827; display:block; }
.table-card { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
.filter-bar { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.filter-select { padding:10px 16px; border:1px solid #e5e7eb; border-radius:8px; font-size:14px; min-width:150px; }
.btn { padding:10px 20px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.btn-primary { background:#dc2626; color:white; }
.btn-primary:hover { background:#b91c1c; }
@media (max-width: 1024px) { .chart-grid { grid-template-columns:1fr; } }
</style>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title">
            <i class="fa fa-chart-line"></i> Expenditure Analytics Dashboard
        </div>
        <div class="page-subtitle">Comprehensive financial expenditure tracking and analysis</div>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card red">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Expenditure</div>
                    <div class="stat-value" id="totalExpenditure"><sup style="font-size:0.5em;"><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?></sup>0.00</div>
                    <div class="stat-change up">
                        <i class="fa fa-arrow-up"></i> <span id="expChange">0%</span> vs last month
                    </div>
                </div>
                <div class="stat-icon red"><i class="fa fa-money-bill-wave"></i></div>
            </div>
        </div>

        <div class="stat-card blue">
            <div class="stat-header">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <div class="stat-label" style="margin:0;">Month</div>
                        <select id="monthFilter" class="filter-select" style="padding:4px 8px; font-size:12px; min-width:100px;" onchange="loadDashboardData()">
                            <?php
                            for($m = 1; $m <= 12; $m++) {
                                $selected = $m == date('n') ? 'selected' : '';
                                echo '<option value="'.$m.'" '.$selected.'>'.date('M', mktime(0,0,0,$m,1)).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="stat-value" id="monthExpenditure"><sup style="font-size:0.5em;"><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?></sup>0.00</div>
                    <div class="stat-change up">
                        <i class="fa fa-calendar"></i> <span id="monthCount">0</span> transactions
                    </div>
                </div>
                <div class="stat-icon blue"><i class="fa fa-calendar-alt"></i></div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Expenses</div>
                    <div class="stat-value" id="expenseCount">0</div>
                    <div class="stat-change">
                        <i class="fa fa-receipt"></i> All time
                    </div>
                </div>
                <div class="stat-icon green"><i class="fa fa-file-invoice"></i></div>
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-header">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <div class="stat-label" style="margin:0;">Academic Year</div>
                        <select id="yearFilter" class="filter-select" style="padding:4px 8px; font-size:12px; min-width:120px;" onchange="loadDashboardData()">
                            <?php
                            $years = $this->db->distinct()->select('year')->from('invoice')->order_by('year', 'desc')->get()->result_array();
                            foreach($years as $y) {
                                $selected = $y['year'] == get_settings('running_year') ? 'selected' : '';
                                echo '<option value="'.$y['year'].'" '.$selected.'>'.$y['year'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="stat-value" id="yearTotal"><sup style="font-size:0.5em;"><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?></sup>0.00</div>
                    <div class="stat-change">
                        <i class="fa fa-calendar-alt"></i> <span id="yearLabel"><?php echo get_settings('running_year'); ?></span> Total
                    </div>
                </div>
                <div class="stat-icon orange"><i class="fa fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="<?php echo site_url('admin/expense'); ?>" class="action-btn">
            <i class="fa fa-list"></i>
            <span>View All Expenses</span>
        </a>
        <a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('admin/expense_bulk_add');?>', 'xlarge');" class="action-btn">
            <i class="fa fa-plus-circle"></i>
            <span>Add Expenses</span>
        </a>
        <a href="<?php echo site_url('admin/expenditure_reports'); ?>" class="action-btn">
            <i class="fa fa-file-alt"></i>
            <span>Generate Reports</span>
        </a>
        <a href="<?php echo site_url('admin/expense_category'); ?>" class="action-btn">
            <i class="fa fa-folder"></i>
            <span>Manage Categories</span>
        </a>
    </div>

    <!-- Charts -->
    <div class="chart-grid">
        <div class="chart-card">
            <div class="chart-title">
                <span><i class="fa fa-chart-bar"></i> Category Breakdown</span>
            </div>
            <div id="categoryDisplay" style="padding:20px;"></div>
        </div>
        
        <div class="chart-card">
            <div class="chart-title">
                <span><i class="fa fa-list"></i> Top Categories</span>
            </div>
            <div id="topCategoriesSummary" style="padding:20px;"></div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/sheetjs-master/xlsx.full.min.js"></script>
<script>
$(document).ready(function() {
    loadDashboardData();
    loadCategoryDisplay();
});

function loadDashboardData() {
    const month = $('#monthFilter').val();
    const year = $('#yearFilter').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_expenditure_stats'); ?>',
        type: 'POST',
        data: {month: month, year: year},
        dataType: 'json',
        success: function(data) {
            const currency = data.currency || 'GH₵';
            $('#totalExpenditure').html(formatCurrency(data.total, currency));
            $('#monthExpenditure').html(formatCurrency(data.month, currency));
            $('#expenseCount').text(data.expense_count);
            $('#yearTotal').html(formatCurrency(data.year_total, currency));
            $('#expChange').text(data.change_percent + '%');
            $('#monthCount').text(data.month_count);
            $('#yearLabel').text(year);
        }
    });
}

function formatCurrency(amount, currency) {
    return '<sup style="font-size:0.5em; vertical-align:super;">' + currency + '</sup>' + formatNumber(amount);
}

function loadCategoryDisplay() {
    $.ajax({
        url: '<?php echo site_url('admin/get_category_breakdown'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const total = data.values.reduce((a, b) => a + b, 0);
            const colors = ['#dc2626', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];
            
            let html = '';
            data.labels.forEach((label, i) => {
                const value = data.values[i];
                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                html += '<div style="margin-bottom:16px;">';
                html += '<div style="display:flex; justify-content:space-between; margin-bottom:8px;">';
                html += '<span style="font-weight:600; color:#374151;">' + label + '</span>';
                html += '<span style="font-weight:700; color:' + colors[i] + ';">' + percent + '%</span>';
                html += '</div>';
                html += '<div style="background:#e5e7eb; height:12px; border-radius:6px; overflow:hidden;">';
                html += '<div style="background:' + colors[i] + '; height:100%; width:' + percent + '%;"></div>';
                html += '</div>';
                html += '</div>';
            });
            
            $('#categoryDisplay').html(html);
            
            // Top 3 categories summary
            let summaryHtml = '';
            for(let i = 0; i < Math.min(3, data.labels.length); i++) {
                summaryHtml += '<div style="margin-bottom:16px; padding:12px; background:#fef2f2; border-radius:8px; border-left:4px solid ' + colors[i] + ';">';
                summaryHtml += '<div style="font-size:13px; color:#6b7280; margin-bottom:4px;">' + data.labels[i] + '</div>';
                summaryHtml += '<div style="font-size:20px; font-weight:700; color:' + colors[i] + ';">GH₵ ' + formatNumber(data.values[i]) + '</div>';
                summaryHtml += '</div>';
            }
            $('#topCategoriesSummary').html(summaryHtml);
        }
    });
}

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
