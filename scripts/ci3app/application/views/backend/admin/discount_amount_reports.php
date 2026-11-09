<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.amount-reports-container { padding: 24px; background: #f8f9fa; min-height: 100vh; }
.amount-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3); }
.amount-header h1 { margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: white !important; }
.amount-header p { margin: 0; opacity: 0.9; font-size: 16px; color: white; }
.filters-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.filters-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
.filter-group { flex: 1; min-width: 200px; }
.filter-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-select { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; height: 48px; }
.btn-generate { padding: 12px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s; height: 48px; }
.btn-generate:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.purple { border-color: #667eea; }
.stat-card.green { border-color: #10b981; }
.stat-card.orange { border-color: #f59e0b; }
.stat-card.blue { border-color: #3b82f6; }
.stat-value { font-size: 24px; font-weight: 700; margin: 8px 0; color: #1a202c; }
.stat-value sup { font-size: 0.4em; font-weight: 600; margin-right: 4px; }
.stat-label { color: #6b7280; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.report-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
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
.export-btn { padding: 12px 20px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; height: 48px; font-size: 15px; }
.export-btn:hover { background: #059669; }
</style>

<div class="amount-reports-container">
    <div class="amount-header">
        <h1><i class="fa fa-money-bill-wave"></i> <?php echo get_phrase('discount_amount_reports'); ?></h1>
        <p><?php echo get_phrase('actual_monetary_value_of_discounts_applied'); ?></p>
    </div>

    <div class="filters-card">
        <div class="filters-row">
            <div class="filter-group">
                <label><?php echo get_phrase('category'); ?></label>
                <select id="filterCategory" class="filter-select">
                    <option value="">All Categories</option>
                    <option value="invoice">Invoice Discounts</option>
                    <option value="daily_fees">Daily Fees Discounts</option>
                </select>
            </div>
            <div class="filter-group">
                <label><?php echo get_phrase('academic_year'); ?></label>
                <select id="filterYear" class="filter-select">
                    <?php 
                    $years = populate_academic_year();
                    foreach($years as $year): 
                    ?>
                        <option value="<?php echo $year; ?>" <?php echo $year == get_settings('running_year') ? 'selected' : ''; ?>><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><?php echo get_phrase('term'); ?></label>
                <select id="filterTerm" class="filter-select">
                    <option value="">All Terms</option>
                    <option value="1" <?php echo get_settings('running_term') == 1 ? 'selected' : ''; ?>>Term 1</option>
                    <option value="2" <?php echo get_settings('running_term') == 2 ? 'selected' : ''; ?>>Term 2</option>
                    <option value="3" <?php echo get_settings('running_term') == 3 ? 'selected' : ''; ?>>Term 3</option>
                </select>
            </div>
            <button class="btn-generate" onclick="generateAmountReport()">
                <i class="fa fa-sync"></i> <?php echo get_phrase('generate'); ?>
            </button>
            <button class="export-btn" onclick="exportAmountReport()">
                <i class="fa fa-download"></i> <?php echo get_phrase('export'); ?>
            </button>
        </div>
    </div>

    <div class="stats-grid" id="statsGrid" style="display:none;">
        <div class="stat-card purple">
            <div class="stat-label"><?php echo get_phrase('total_applications'); ?></div>
            <div class="stat-value" id="stat-applications">0</div>
        </div>
        <div class="stat-card green">
            <div class="stat-label"><?php echo get_phrase('total_discount_amount'); ?></div>
            <div class="stat-value" id="stat-discount">GHS 0</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label"><?php echo get_phrase('original_amount'); ?></div>
            <div class="stat-value" id="stat-original">GHS 0</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label"><?php echo get_phrase('unique_students'); ?></div>
            <div class="stat-value" id="stat-students">0</div>
        </div>
    </div>

    <div class="report-card" id="reportCard">
        <h3><?php echo get_phrase('select_filters_and_generate_report'); ?></h3>
        <div id="reportContent"></div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/sheetjs-master/xlsx.full.min.js"></script>
<script>
let reportData = [];

function generateAmountReport() {
    showAjaxModal_alert('<?php echo get_phrase('generating_report'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_discount_amount_report'); ?>',
        type: 'GET',
        data: {
            category: $('#filterCategory').val(),
            year: $('#filterYear').val(),
            term: $('#filterTerm').val()
        },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            reportData = response.data;
            renderAmountReport(response.summary);
            $('.close').click();
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('failed_to_generate_report'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>', 'error');
    });
}

function renderAmountReport(summary) {
    $('#stat-applications').text(summary.total_applications || 0);
    $('#stat-discount').html('<sup style="font-size: 0.6em; font-weight: 600;">GHS</sup> ' + parseFloat(summary.total_discount_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#stat-original').html('<sup style="font-size: 0.6em; font-weight: 600;">GHS</sup> ' + parseFloat(summary.total_original_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#stat-students').text(summary.unique_students || 0);
    $('#statsGrid').show();
    
    let html = `
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo get_phrase('student'); ?></th>
                    <th><?php echo get_phrase('profile'); ?></th>
                    <th><?php echo get_phrase('category'); ?></th>
                    <th><?php echo get_phrase('original_amount'); ?></th>
                    <th><?php echo get_phrase('discount'); ?></th>
                    <th><?php echo get_phrase('discount_amount'); ?></th>
                    <th><?php echo get_phrase('final_amount'); ?></th>
                    <th><?php echo get_phrase('date'); ?></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    reportData.forEach((item, index) => {
        let discountDisplay = item.discount_method === 'percentage' 
            ? `${parseFloat(item.discount_percentage).toFixed(1)}%` 
            : `<sup style="font-size: 0.7em;">GHS</sup> ${parseFloat(item.discount_value_display).toFixed(2)}`;
        
        html += `
            <tr>
                <td><strong>${index + 1}</strong></td>
                <td>
                    <div style="font-weight: 600;">${item.student_name}</div>
                    <div style="font-size: 12px; color: #6b7280;">${item.student_code}</div>
                </td>
                <td><strong>${item.profile_name}</strong></td>
                <td><span class="badge badge-${item.discount_category === 'invoice' ? 'purple' : 'blue'}">${item.discount_category}</span></td>
                <td><strong><sup style="font-size: 0.7em;">GHS</sup> ${parseFloat(item.original_amount).toFixed(2)}</strong></td>
                <td><span class="badge badge-orange">${discountDisplay}</span></td>
                <td><span class="badge badge-green"><sup style="font-size: 0.7em;">GHS</sup> ${parseFloat(item.discount_amount).toFixed(2)}</span></td>
                <td><strong><sup style="font-size: 0.7em;">GHS</sup> ${parseFloat(item.final_amount).toFixed(2)}</strong></td>
                <td>${new Date(item.applied_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    $('#reportContent').html(html);
}

function exportAmountReport() {
    if(reportData.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('no_data_to_export'); ?>', 'error');
        return;
    }
    
    var ws_data = [['#', 'Student Name', 'Student Code', 'Profile', 'Category', 'Original Amount (GHS)', 'Discount', 'Discount Amount (GHS)', 'Final Amount (GHS)']];
    
    reportData.forEach(function(item, index) {
        var discountDisplay = item.discount_method === 'percentage' 
            ? parseFloat(item.discount_percentage).toFixed(1) + '%' 
            : 'GHS ' + parseFloat(item.discount_value_display).toFixed(2);
        
        ws_data.push([
            index + 1,
            item.student_name,
            item.student_code,
            item.profile_name || 'N/A',
            item.discount_category.charAt(0).toUpperCase() + item.discount_category.slice(1),
            parseFloat(item.original_amount),
            discountDisplay,
            parseFloat(item.discount_amount),
            parseFloat(item.final_amount)
        ]);
    });
    
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    
    ws['!cols'] = [
        {wch: 5}, {wch: 25}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 22}, {wch: 20}
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Discount Report');
    XLSX.writeFile(wb, 'Discount_Amount_Report_' + new Date().toISOString().split('T')[0] + '.xlsx');
}
</script>
