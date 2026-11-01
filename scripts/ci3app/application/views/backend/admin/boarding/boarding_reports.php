<?php
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
?>
<style>
.modern-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 24px;
    margin-bottom: 20px;
}
.report-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: transform 0.3s;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.report-card:hover {
    transform: translateY(-5px);
}
.report-card h4 {
    color: white;
    font-weight: 600;
}
.report-card p {
    color: rgba(255,255,255,0.9);
}
</style>

<div class="container-fluid">
    <div class="modern-card">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fa fa-chart-bar" style="color:#667eea;"></i> Boarding Reports & Analytics
        </h1>
        <p class="text-gray-600">View comprehensive reports and statistics</p>
    </div>

    <!-- Report Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="report-card" onclick="generateReport('occupancy')">
                <i class="fa fa-chart-pie fa-3x mb-3"></i>
                <h4>Occupancy Report</h4>
                <p>View house and dormitory occupancy rates</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="report-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" onclick="generateReport('students')">
                <i class="fa fa-users fa-3x mb-3"></i>
                <h4>Student List</h4>
                <p>List of all boarding students</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="report-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" onclick="generateReport('available')">
                <i class="fa fa-bed fa-3x mb-3"></i>
                <h4>Available Beds</h4>
                <p>View all available beds</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="report-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);" onclick="generateReport('summary')">
                <i class="fa fa-file-alt fa-3x mb-3"></i>
                <h4>Summary Report</h4>
                <p>Complete boarding summary</p>
            </div>
        </div>
    </div>

    <!-- Report Display Area -->
    <div class="modern-card">
        <div class="row mb-3">
            <div class="col-md-6">
                <h3 id="report_title">Select a report to view</h3>
            </div>
            <div class="col-md-6 text-right">
                <button class="btn btn-success" onclick="exportReport('excel')">
                    <i class="fa fa-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger" onclick="exportReport('pdf')">
                    <i class="fa fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>
        <div id="report_content">
            <div class="text-center text-gray-500 py-5">
                <i class="fa fa-chart-bar fa-5x mb-3"></i>
                <p>Click on a report card above to generate a report</p>
            </div>
        </div>
    </div>
</div>

<script>
function generateReport(type) {
    $('#report_content').html('<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Generating report...</p></div>');
    
    var titles = {
        'occupancy': 'Occupancy Report',
        'students': 'Boarding Students List',
        'available': 'Available Beds Report',
        'summary': 'Boarding Summary Report'
    };
    $('#report_title').text(titles[type]);
    
    $.ajax({
        url: '<?php echo site_url("admin/boarding_reports/"); ?>' + type,
        type: 'GET',
        timeout: 10000,
        success: function(data) {
            if(data && data.trim() !== '') {
                $('#report_content').html(data);
            } else {
                $('#report_content').html('<div class="text-center py-5"><i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i><p class="text-gray-600">No data available for this report</p></div>');
            }
        },
        error: function(xhr, status, error) {
            $('#report_content').html('<div class="text-center py-5"><i class="fa fa-times-circle fa-3x text-danger mb-3"></i><p class="text-gray-600">Error generating report.</p><p class="text-sm text-gray-500">Status: ' + status + '</p></div>');
        }
    });
}

function exportReport(format) {
    var reportType = $('#report_title').text();
    if(reportType == 'Select a report to view') {
        showAjaxModal_alert('Please generate a report first','warning');
        return;
    }
    window.print();
}
</script>
