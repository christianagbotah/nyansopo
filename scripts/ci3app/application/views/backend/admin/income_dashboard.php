<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0fdf4; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.dashboard-container { max-width:1600px; margin:0 auto; padding:24px; }
.page-header { background:linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius:12px; padding:32px; margin-bottom:32px; box-shadow:0 4px 20px rgba(16,185,129,0.2); color:white; }
.page-title { font-size:32px; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:16px; }
.page-subtitle { font-size:16px; opacity:0.95; }
.stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:24px; margin-bottom:32px; }
@media (max-width: 1200px) { .stats-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 768px) { .stats-grid { grid-template-columns:1fr; } }
.stat-card { background:white; border-radius:12px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,0.08); position:relative; overflow:hidden; transition:all 0.3s; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,0.12); }
.stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; }
.stat-card.green::before { background:#10b981; }
.stat-card.blue::before { background:#3b82f6; }
.stat-card.purple::before { background:#8b5cf6; }
.stat-card.orange::before { background:#f59e0b; }
.stat-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; }
.stat-icon { width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:28px; }
.stat-icon.green { background:#d1fae5; color:#10b981; }
.stat-icon.blue { background:#dbeafe; color:#3b82f6; }
.stat-icon.purple { background:#ede9fe; color:#8b5cf6; }
.stat-icon.orange { background:#fef3c7; color:#f59e0b; }
.stat-label { font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
.stat-value { font-size:36px; font-weight:700; color:#111827; margin-bottom:8px; }
.stat-change { font-size:13px; font-weight:600; display:flex; align-items:center; gap:4px; }
.stat-change.up { color:#10b981; }
.stat-change.down { color:#dc2626; }
.chart-grid { display:grid; grid-template-columns:2fr 1fr; gap:24px; margin-bottom:32px; }
.chart-card { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
.chart-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; }
.quick-actions { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:32px; }
.action-btn { background:white; border:2px solid #d1fae5; border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:all 0.3s; text-decoration:none; display:block; }
.action-btn:hover { border-color:#10b981; background:#f0fdf4; transform:translateY(-2px); box-shadow:0 4px 12px rgba(16,185,129,0.15); }
.action-btn i { font-size:32px; color:#10b981; margin-bottom:12px; display:block; }
.action-btn span { font-size:14px; font-weight:600; color:#111827; display:block; }
.table-card { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
.filter-bar { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.filter-select { padding:10px 16px; border:1px solid #e5e7eb; border-radius:8px; font-size:14px; min-width:150px; }
.btn { padding:10px 20px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.btn-success { background:#10b981; color:white; }
.btn-success:hover { background:#059669; }
@media (max-width: 1024px) { .chart-grid { grid-template-columns:1fr; } }
</style>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title">
            <i class="fa fa-chart-line"></i> Income & Revenue Analytics Dashboard
        </div>
        <div class="page-subtitle">Comprehensive financial income tracking and revenue analysis</div>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value" id="totalRevenue"><sup style="font-size:0.5em;"><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?></sup>0.00</div>
                    <div class="stat-change up">
                        <i class="fa fa-arrow-up"></i> <span id="revenueChange">0%</span> vs last month
                    </div>
                </div>
                <div class="stat-icon green"><i class="fa fa-coins"></i></div>
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
                    <div class="stat-value" id="monthRevenue"><sup style="font-size:0.5em;"><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?></sup>0.00</div>
                    <div class="stat-change up">
                        <i class="fa fa-calendar"></i> <span id="monthCount">0</span> transactions
                    </div>
                </div>
                <div class="stat-icon blue"><i class="fa fa-calendar-check"></i></div>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Outstanding</div>
                    <div class="stat-value" id="outstanding"><sup style="font-size:0.5em;"><?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?></sup>0.00</div>
                    <div class="stat-change">
                        <i class="fa fa-clock"></i> <span id="outstandingCount">0</span> unpaid invoices
                    </div>
                </div>
                <div class="stat-icon purple"><i class="fa fa-file-invoice-dollar"></i></div>
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-header">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <div class="stat-label" style="margin:0;">Academic Year</div>
                        <select id="academicYearFilter" class="filter-select" style="padding:4px 8px; font-size:12px; min-width:100px;" onchange="loadDashboardData()">
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
                        <i class="fa fa-calendar-alt"></i> <span id="yearLabel">Year</span> Total
                    </div>
                </div>
                <div class="stat-icon orange"><i class="fa fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="<?php echo site_url('admin/student_invoice'); ?>" class="action-btn">
            <i class="fa fa-file-invoice"></i>
            <span>Create Invoice</span>
        </a>
        <a href="<?php echo site_url('fee_collection'); ?>" class="action-btn">
            <i class="fa fa-hand-holding-usd"></i>
            <span>Collect Fees</span>
        </a>
        <a href="<?php echo site_url('admin/income_reports'); ?>" class="action-btn">
            <i class="fa fa-file-alt"></i>
            <span>Generate Reports</span>
        </a>
        <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="action-btn">
            <i class="fa fa-arrow-down"></i>
            <span>Receivables</span>
        </a>
    </div>
    
    <!-- Filter Bar -->
    <div style="background:white; border-radius:12px; padding:20px; margin-bottom:24px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <label style="font-weight:600; color:#374151;">Academic Year:</label>
            <select id="yearFilter" class="filter-select" onchange="loadDashboard()">
                <?php
                $years = $this->db->distinct()->select('year')->from('invoice')->order_by('year', 'desc')->get()->result_array();
                foreach($years as $y) {
                    $selected = $y['year'] == get_settings('running_year') ? 'selected' : '';
                    echo '<option value="'.$y['year'].'" '.$selected.'>'.$y['year'].'</option>';
                }
                ?>
            </select>
            <label style="font-weight:600; color:#374151; margin-left:16px;">From:</label>
            <input type="date" id="startDate" class="filter-select" onchange="loadDashboard()">
            <label style="font-weight:600; color:#374151;">To:</label>
            <input type="date" id="endDate" class="filter-select" onchange="loadDashboard()">
            <button class="btn btn-success" onclick="loadDashboard()"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>

    <!-- Charts -->
    <div class="chart-grid">
        <div class="chart-card">
            <div class="chart-title">
                <span><i class="fa fa-chart-bar"></i> Revenue Sources Breakdown</span>
            </div>
            <div id="revenueSourcesDisplay" style="padding:20px;"></div>
        </div>
        
        <div class="chart-card">
            <div class="chart-title">
                <span><i class="fa fa-list"></i> Revenue Summary</span>
            </div>
            <div style="padding:20px;">
                <div style="margin-bottom:20px; padding:16px; background:#f0fdf4; border-radius:8px; border-left:4px solid #10b981;">
                    <div style="font-size:13px; color:#6b7280; margin-bottom:4px;">Billed Invoices</div>
                    <div style="font-size:24px; font-weight:700; color:#10b981;" id="invoiceAmount">GH₵ 0.00</div>
                </div>
                <div style="margin-bottom:20px; padding:16px; background:#eff6ff; border-radius:8px; border-left:4px solid #3b82f6;">
                    <div style="font-size:13px; color:#6b7280; margin-bottom:4px;">Daily Fees</div>
                    <div style="font-size:24px; font-weight:700; color:#3b82f6;" id="dailyAmount">GH₵ 0.00</div>
                </div>
                <div style="padding:16px; background:#f5f3ff; border-radius:8px; border-left:4px solid #8b5cf6;">
                    <div style="font-size:13px; color:#6b7280; margin-bottom:4px;">Inventory Sales</div>
                    <div style="font-size:24px; font-weight:700; color:#8b5cf6;" id="inventoryAmount">GH₵ 0.00</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/sheetjs-master/xlsx.full.min.js"></script>
<script>
$(document).ready(function() {
    loadDashboard();
});

function loadDashboard() {
    loadDashboardData();
    loadRevenueSourcesDisplay();
}

function loadDashboardData() {
    const year = $('#yearFilter').val();
    const start_date = $('#startDate').val();
    const end_date = $('#endDate').val();
    const month = $('#monthFilter').val();
    const academic_year = $('#academicYearFilter').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_income_stats'); ?>',
        type: 'POST',
        data: {year: year, start_date: start_date, end_date: end_date, month: month, academic_year: academic_year},
        dataType: 'json',
        success: function(data) {
            const currency = data.currency || 'GH₵';
            $('#totalRevenue').html(formatCurrency(data.total, currency));
            $('#monthRevenue').html(formatCurrency(data.month, currency));
            $('#outstanding').html(formatCurrency(data.outstanding, currency));
            $('#yearTotal').html(formatCurrency(data.year_total, currency));
            $('#revenueChange').text(data.change_percent + '%');
            $('#monthCount').text(data.month_count);
            $('#outstandingCount').text(data.outstanding_count);
            $('#yearLabel').text(academic_year);
        }
    });
}

function formatCurrency(amount, currency) {
    return '<sup style="font-size:0.5em; vertical-align:super;">' + currency + '</sup>' + formatNumber(amount);
}

function loadRevenueSourcesDisplay() {
    const year = $('#yearFilter').val();
    const start_date = $('#startDate').val();
    const end_date = $('#endDate').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_revenue_sources'); ?>',
        type: 'POST',
        data: {year: year, start_date: start_date, end_date: end_date},
        dataType: 'json',
        success: function(response) {
            const currency = '<?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?>';
            
            // Update summary cards
            $('#invoiceAmount').text(currency + ' ' + formatNumber(response.values[0]));
            $('#dailyAmount').text(currency + ' ' + formatNumber(response.values[1]));
            $('#inventoryAmount').text(currency + ' ' + formatNumber(response.values[2]));
            
            // Create simple bar display
            const total = response.values[0] + response.values[1] + response.values[2];
            let html = '';
            const colors = ['#10b981', '#3b82f6', '#8b5cf6'];
            
            response.labels.forEach((label, i) => {
                const value = response.values[i];
                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                html += '<div style="margin-bottom:16px;">';
                html += '<div style="display:flex; justify-content:space-between; margin-bottom:8px;">';
                html += '<span style="font-weight:600; color:#374151;">' + label + '</span>';
                html += '<span style="font-weight:700; color:' + colors[i] + ';">' + percent + '%</span>';
                html += '</div>';
                html += '<div style="background:#e5e7eb; height:12px; border-radius:6px; overflow:hidden;">';
                html += '<div style="background:' + colors[i] + '; height:100%; width:' + percent + '%; transition:width 0.3s;"></div>';
                html += '</div>';
                html += '</div>';
            });
            
            $('#revenueSourcesDisplay').html(html);
        }
    });
}

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
