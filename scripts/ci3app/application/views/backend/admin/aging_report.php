<style>
@import url('<?php echo base_url(); ?>assets/cdn/fonts/inter.css');
* { font-family: 'Inter', sans-serif; }
.modern-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
.page-header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(220, 38, 38, 0.2); }
.page-title { font-size: 32px; font-weight: 700; color: white; margin: 0 0 8px 0; letter-spacing: -0.5px; }
.page-subtitle { font-size: 16px; color: rgba(255,255,255,0.9); margin: 0; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; border-left: 4px solid; transition: all 0.3s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.stat-card.current { border-left-color: #10b981; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); }
.stat-card.days-30 { border-left-color: #f59e0b; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%); }
.stat-card.days-60 { border-left-color: #ef4444; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%); }
.stat-card.days-90 { border-left-color: #991b1b; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%); }
.stat-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.stat-value { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 4px; }
.stat-meta { font-size: 13px; color: #9ca3af; }
.currency { font-size: 9px; font-weight: 500; opacity: 0.7; margin-right: 2px; }
.data-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.card-title { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
.btn-modern { padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
.table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
.table-modern thead th { background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%); padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
.table-modern tbody tr { transition: all 0.2s; }
.table-modern tbody tr:hover { background: #f9fafb; }
.table-modern tbody td { padding: 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
</style>

<div class="modern-container">
<div class="page-header">
    <h1 class="page-title">⏰ Accounts Receivable Aging</h1>
    <p class="page-subtitle">Track outstanding balances by age and identify collection priorities</p>
</div>

<div class="stats-grid">
    <div class="stat-card current">
        <div class="stat-label">✅ Current (0-30 days)</div>
        <div class="stat-value" id="current-amount">GHS 0.00</div>
        <div class="stat-meta"><span id="current-count">0</span> students</div>
    </div>
    <div class="stat-card days-30">
        <div class="stat-label">⚠️ 31-60 Days</div>
        <div class="stat-value" id="days30-amount">GHS 0.00</div>
        <div class="stat-meta"><span id="days30-count">0</span> students</div>
    </div>
    <div class="stat-card days-60">
        <div class="stat-label">🔴 61-90 Days</div>
        <div class="stat-value" id="days60-amount">GHS 0.00</div>
        <div class="stat-meta"><span id="days60-count">0</span> students</div>
    </div>
    <div class="stat-card days-90">
        <div class="stat-label">🚨 Over 90 Days</div>
        <div class="stat-value" id="days90-amount">GHS 0.00</div>
        <div class="stat-meta"><span id="days90-count">0</span> students</div>
    </div>
</div>

<div class="data-card">
    <div class="card-header">
        <h3 class="card-title">📊 Detailed Breakdown</h3>
        <button onclick="exportAging()" class="btn-modern btn-success">
            <i class="fa fa-file-excel"></i> Export Report
        </button>
    </div>
    <table class="table-modern datatable">
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Invoice Code</th>
                <th>Invoice Date</th>
                <th>Days Outstanding</th>
                <th style="text-align: right;">Amount Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="aging-tbody"></tbody>
    </table>
</div>
</div>

<script>
$(function() {
    loadAgingReport();
});

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function loadAgingReport() {
    showAjaxModal_alert('Loading...', 'loading');
    $.ajax({
        url: '<?php echo base_url(); ?>admin/get_aging_report',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        $('.close')[0].click();
        if(response.status === 'success') {
            $('#current-amount').html('<sup class="currency">GHS</sup> ' + formatNumber(response.summary.current));
            $('#current-count').text(response.summary.current_count);
            $('#days30-amount').html('<sup class="currency">GHS</sup> ' + formatNumber(response.summary.days30));
            $('#days30-count').text(response.summary.days30_count);
            $('#days60-amount').html('<sup class="currency">GHS</sup> ' + formatNumber(response.summary.days60));
            $('#days60-count').text(response.summary.days60_count);
            $('#days90-amount').html('<sup class="currency">GHS</sup> ' + formatNumber(response.summary.days90));
            $('#days90-count').text(response.summary.days90_count);
            
            let tbody = '';
            response.details.forEach(function(row) {
                let statusClass = row.days_outstanding <= 30 ? 'success' : 
                                 row.days_outstanding <= 60 ? 'warning' : 
                                 row.days_outstanding <= 90 ? 'danger' : 'danger';
                tbody += '<tr>' +
                    '<td>' + row.student_name + '</td>' +
                    '<td>' + row.class_name + '</td>' +
                    '<td>' + row.invoice_code + '</td>' +
                    '<td>' + row.invoice_date + '</td>' +
                    '<td>' + row.days_outstanding + ' days</td>' +
                    '<td style="text-align: right;"><sup class="currency">GHS</sup> ' + formatNumber(row.amount_due) + '</td>' +
                    '<td><span class="label label-' + statusClass + '">' + row.age_category + '</span></td>' +
                    '</tr>';
            });
            $('#aging-tbody').html(tbody);
            $('.datatable').DataTable();
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function exportAging() {
    window.location.href = '<?php echo base_url(); ?>admin/export_aging_report';
}
</script>
