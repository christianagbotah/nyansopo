<style>
.reports-container { max-width:1400px; margin:0 auto; padding:24px; background:#fef2f2; min-height:100vh; }
.page-header { background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius:12px; padding:32px; margin-bottom:32px; box-shadow:0 4px 20px rgba(220,38,38,0.2); color:white; }
.page-title { font-size:32px; font-weight:700; margin-bottom:8px; }
.reports-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(350px, 1fr)); gap:24px; margin-bottom:32px; }
.report-card { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); transition:all 0.3s; cursor:pointer; }
.report-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,0.12); }
.report-icon { width:64px; height:64px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:32px; margin-bottom:20px; }
.report-icon.red { background:#fee2e2; color:#dc2626; }
.report-icon.blue { background:#dbeafe; color:#3b82f6; }
.report-icon.green { background:#d1fae5; color:#10b981; }
.report-icon.purple { background:#ede9fe; color:#8b5cf6; }
.report-title { font-size:20px; font-weight:700; color:#111827; margin-bottom:8px; }
.report-desc { font-size:14px; color:#6b7280; margin-bottom:20px; line-height:1.6; }
.report-btn { background:#dc2626; color:white; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; border:none; cursor:pointer; transition:all 0.2s; width:100%; }
.report-btn:hover { background:#b91c1c; }
.custom-report { background:white; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
.form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:20px; }
.form-group label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
.form-control { width:100%; padding:14px; border:1px solid #e5e7eb; border-radius:8px; font-size:14px; min-height:46px; }
.form-control:focus { border-color:#dc2626; outline:none; box-shadow:0 0 0 3px rgba(220,38,38,0.1); }
</style>

<div class="reports-container">
    <div class="page-header">
        <div class="page-title"><i class="fa fa-file-alt"></i> Expenditure Reports</div>
        <div style="font-size:16px; opacity:0.95;">Generate comprehensive financial reports and analytics</div>
    </div>

    <!-- Pre-defined Reports -->
    <div class="reports-grid">
        <div class="report-card">
            <div class="report-icon red"><i class="fa fa-calendar-alt"></i></div>
            <div class="report-title">Monthly Summary</div>
            <div class="report-desc">Detailed breakdown of all expenses for the current month with category analysis</div>
            <button class="report-btn" onclick="generateReport('monthly')">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon blue"><i class="fa fa-chart-line"></i></div>
            <div class="report-title">Quarterly Analysis</div>
            <div class="report-desc">Comprehensive quarterly expenditure trends and comparisons</div>
            <button class="report-btn" onclick="generateReport('quarterly')">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon green"><i class="fa fa-calendar-check"></i></div>
            <div class="report-title">Annual Report</div>
            <div class="report-desc">Complete annual financial expenditure summary with year-over-year analysis</div>
            <button class="report-btn" onclick="generateReport('annual')">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon purple"><i class="fa fa-folder"></i></div>
            <div class="report-title">Category Report</div>
            <div class="report-desc">Detailed analysis by expense category with spending patterns</div>
            <button class="report-btn" onclick="generateReport('category')">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon red"><i class="fa fa-credit-card"></i></div>
            <div class="report-title">Payment Method Analysis</div>
            <div class="report-desc">Breakdown of expenses by payment method (Cash, Bank, Mobile Money)</div>
            <button class="report-btn" onclick="generateReport('payment_method')">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon blue"><i class="fa fa-chart-pie"></i></div>
            <div class="report-title">Budget vs Actual</div>
            <div class="report-desc">Compare budgeted amounts against actual expenditure</div>
            <button class="report-btn" onclick="generateReport('budget_comparison')">Generate Report</button>
        </div>
    </div>

    <!-- Custom Report Generator -->
    <div class="custom-report">
        <h3 style="font-size:24px; font-weight:700; color:#111827; margin-bottom:24px;">
            <i class="fa fa-sliders-h"></i> Custom Report Generator
        </h3>
        
        <?php echo form_open('admin/get_custom_expenditure_report_data', ['id' => 'customReportForm']); ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Report Type</label>
                    <select name="report_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="summary">Summary Report</option>
                        <option value="detailed">Detailed Report</option>
                        <option value="comparative">Comparative Analysis</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date Range</label>
                    <select name="date_range" class="form-control" required onchange="toggleCustomDates(this)">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <div class="form-group" id="startDateGroup" style="display:none;">
                    <label>Start Date</label>
                    <input type="text" name="start_date" class="form-control datepicker">
                </div>

                <div class="form-group" id="endDateGroup" style="display:none;">
                    <label>End Date</label>
                    <input type="text" name="end_date" class="form-control datepicker">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php
                        $categories = $this->db->get('expense_category')->result_array();
                        foreach($categories as $cat):
                        ?>
                        <option value="<?php echo $cat['expense_category_id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="">All Methods</option>
                        <option value="1">Cash</option>
                        <option value="2">Cheque</option>
                        <option value="3">Card</option>
                        <option value="4">Mobile Money</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Export Format</label>
                    <select name="format" class="form-control" required>
                        <option value="both">Print & Excel</option>
                        <option value="print">Print Only</option>
                        <option value="excel">Excel Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Include Charts</label>
                    <select name="include_charts" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="report-btn" style="max-width:300px;">
                <i class="fa fa-download"></i> Generate Custom Report
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<script>
function toggleCustomDates(select) {
    if(select.value === 'custom') {
        $('#startDateGroup, #endDateGroup').show();
    } else {
        $('#startDateGroup, #endDateGroup').hide();
    }
}

function generateReport(type) {
    
    $.ajax({
        url: '<?php echo site_url('admin/get_expenditure_report_data/'); ?>' + type,
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            printReport(response.data, response.title);
            exportToExcel(response.data, response.title);
        } else {
            showAjaxModal_alert('Failed to generate report', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

$('#customReportForm').submit(function(e) {
    e.preventDefault();
    const format = $('select[name="format"]').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_custom_expenditure_report_data'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            if(format === 'print') {
                printReport(response.data, response.title);
            } else if(format === 'excel') {
                exportToExcel(response.data, response.title);
            } else {
                printReport(response.data, response.title);
                exportToExcel(response.data, response.title);
            }
        } else {
            showAjaxModal_alert('Failed to generate report', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});

function printReport(data, title) {
    let html = `
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #dc2626; border-bottom: 3px solid #dc2626; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #dc2626; color: white; padding: 12px; text-align: left; }
                td { padding: 10px; border-bottom: 1px solid #ddd; }
                tr:hover { background: #f9fafb; }
                @media print { button { display: none; } }
            </style>
        </head>
        <body>
            <h1>${title}</h1>
            <table>
                <thead><tr>`;
    
    Object.keys(data[0]).forEach(key => {
        html += `<th>${key}</th>`;
    });
    
    html += `</tr></thead><tbody>`;
    
    data.forEach(row => {
        html += '<tr>';
        Object.values(row).forEach(val => {
            html += `<td>${val}</td>`;
        });
        html += '</tr>';
    });
    
    html += `</tbody></table>
            <button onclick="window.print()" style="margin-top:20px; padding:10px 20px; background:#dc2626; color:white; border:none; border-radius:5px; cursor:pointer;">Print</button>
        </body></html>`;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(html);
    printWindow.document.close();
}

function exportToExcel(data, title) {
    const ws = XLSX.utils.json_to_sheet(data);
    
    // Set column widths
    ws['!cols'] = [
        {wch: 15}, {wch: 30}, {wch: 20}, {wch: 15}, {wch: 20}, {wch: 30}
    ];
    
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    
    const filename = title + '.xlsx';
    XLSX.writeFile(wb, filename);
}

$(document).ready(function() {
    $('.datepicker').datepicker({format: 'dd-mm-yyyy', autoclose: true});
});
</script>

<script src="<?php echo base_url(); ?>assets/sheetjs-master/xlsx.full.min.js"></script>
<script>
</script>
