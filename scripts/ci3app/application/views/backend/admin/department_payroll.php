<!-- Department-wise Payroll Report (Task 14.5) -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-chart-bar"></i>
                    <?php echo get_phrase('department_wise_payroll_report'); ?>
                </div>
                <div class="panel-options">
                    <button class="btn btn-sm btn-success" id="exportDeptExcelBtn">
                        <i class="entypo-download"></i> <?php echo get_phrase('export_to_excel'); ?>
                    </button>
                    <button class="btn btn-sm btn-info" id="refreshReportBtn">
                        <i class="entypo-cycle"></i> <?php echo get_phrase('refresh'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-body">
                
                <!-- Filters -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('month'); ?>:</label>
                            <select name="dept_filter_month" id="dept_filter_month" class="form-control">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($m == date('n')) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('year'); ?>:</label>
                            <select name="dept_filter_year" id="dept_filter_year" class="form-control">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="loadDeptReportBtn">
                                <i class="entypo-search"></i> <?php echo get_phrase('load_report'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="info-box" style="background: #5cb85c; color: white; padding: 15px; border-radius: 4px; text-align: center;">
                            <h4 style="margin: 0;"><?php echo get_phrase('total_departments'); ?></h4>
                            <h2 style="margin: 10px 0;" id="total_departments">0</h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box" style="background: #337ab7; color: white; padding: 15px; border-radius: 4px; text-align: center;">
                            <h4 style="margin: 0;"><?php echo get_phrase('total_staff'); ?></h4>
                            <h2 style="margin: 10px 0;" id="total_staff">0</h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box" style="background: #f0ad4e; color: white; padding: 15px; border-radius: 4px; text-align: center;">
                            <h4 style="margin: 0;"><?php echo get_phrase('total_gross_payroll'); ?></h4>
                            <h2 style="margin: 10px 0;" id="total_gross_summary">GH¢ 0.00</h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box" style="background: #d9534f; color: white; padding: 15px; border-radius: 4px; text-align: center;">
                            <h4 style="margin: 0;"><?php echo get_phrase('total_net_payroll'); ?></h4>
                            <h2 style="margin: 10px 0;" id="total_net_summary">GH¢ 0.00</h2>
                        </div>
                    </div>
                </div>

                <!-- Department Comparison Chart -->
                <div class="row" style="margin-bottom: 30px;">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><?php echo get_phrase('department_cost_comparison'); ?></h4>
                            </div>
                            <div class="panel-body">
                                <canvas id="deptCostChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Summary Table with Expandable Rows -->
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped" id="dept_summary_table">
                            <thead>
                                <tr style="background-color: #337ab7; color: white;">
                                    <th style="width: 40px;"></th>
                                    <th><?php echo get_phrase('department'); ?></th>
                                    <th><?php echo get_phrase('staff_count'); ?></th>
                                    <th><?php echo get_phrase('total_gross_salary'); ?></th>
                                    <th><?php echo get_phrase('total_deductions'); ?></th>
                                    <th><?php echo get_phrase('total_net_salary'); ?></th>
                                    <th><?php echo get_phrase('percentage_of_total'); ?></th>
                                    <th><?php echo get_phrase('avg_per_staff'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="dept_summary_body">
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 30px;">
                                        <i class="entypo-info-circled" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="color: #999; margin-top: 10px;">
                                            <?php echo get_phrase('select_month_and_year_to_load_report'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="dept_grand_total_footer" style="display: none;">
                                <tr style="font-weight: bold; background-color: #f5f5f5; border-top: 3px solid #333;">
                                    <td colspan="2"><?php echo get_phrase('grand_total'); ?>:</td>
                                    <td id="grand_total_staff">0</td>
                                    <td id="grand_total_gross">GH¢ 0.00</td>
                                    <td id="grand_total_deductions">GH¢ 0.00</td>
                                    <td id="grand_total_net">GH¢ 0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Hidden template for expanded staff details -->
<div id="staff_details_template" style="display: none;">
    <table class="table table-condensed staff-details-table" style="margin: 0; background-color: #f9f9f9;">
        <thead>
            <tr style="background-color: #eee;">
                <th><?php echo get_phrase('staff_name'); ?></th>
                <th><?php echo get_phrase('staff_code'); ?></th>
                <th><?php echo get_phrase('category'); ?></th>
                <th><?php echo get_phrase('basic_salary'); ?></th>
                <th><?php echo get_phrase('allowances'); ?></th>
                <th><?php echo get_phrase('gross_salary'); ?></th>
                <th><?php echo get_phrase('deductions'); ?></th>
                <th><?php echo get_phrase('net_salary'); ?></th>
            </tr>
        </thead>
        <tbody class="staff-details-body">
            <!-- Staff rows populated via JavaScript -->
        </tbody>
    </table>
</div>

<!-- JavaScript for Department Report -->
<script type="text/javascript">
var deptChartInstance = null;
var departmentData = {};

$(document).ready(function() {
    
    // Load report button
    $('#loadDeptReportBtn, #refreshReportBtn').on('click', function() {
        loadDepartmentReport();
    });
    
    // Export to Excel button
    $('#exportDeptExcelBtn').on('click', function() {
        var month = $('#dept_filter_month').val();
        var year = $('#dept_filter_year').val();
        
        var url = '<?php echo base_url(); ?>index.php?admin/export_department_payroll_excel&month=' + month + '&year=' + year;
        window.location.href = url;
    });
    
    // Load department report
    function loadDepartmentReport() {
        var month = $('#dept_filter_month').val();
        var year = $('#dept_filter_year').val();
        
        // Show loading
        $('#dept_summary_body').html('<tr><td colspan="8" style="text-align: center; padding: 30px;"><i class="entypo-arrows-ccw" style="font-size: 32px; animation: spin 1s linear infinite;"></i><p style="margin-top: 10px;">Loading report...</p></td></tr>');
        
        $.ajax({
            url: '<?php echo base_url(); ?>index.php?admin/get_department_payroll_data',
            type: 'POST',
            data: {
                month: month,
                year: year,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    departmentData = response.data;
                    renderDepartmentTable(response.data);
                    renderDepartmentChart(response.data);
                    updateSummaryCards(response.data);
                } else {
                    showError('Failed to load report: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Network error loading report. Please try again.');
            }
        });
    }
    
    // Render department summary table
    function renderDepartmentTable(data) {
        if (!data.departments || data.departments.length === 0) {
            $('#dept_summary_body').html('<tr><td colspan="8" style="text-align: center; padding: 30px;"><i class="entypo-attention" style="font-size: 48px; color: #f0ad4e;"></i><p style="color: #999; margin-top: 10px;">No payroll data found for selected period.</p></td></tr>');
            $('#dept_grand_total_footer').hide();
            return;
        }
        
        var html = '';
        var grandTotal = {
            staff_count: 0,
            gross: 0,
            deductions: 0,
            net: 0
        };
        
        $.each(data.departments, function(index, dept) {
            grandTotal.staff_count += parseInt(dept.staff_count);
            grandTotal.gross += parseFloat(dept.total_gross);
            grandTotal.deductions += parseFloat(dept.total_deductions);
            grandTotal.net += parseFloat(dept.total_net);
            
            var avgPerStaff = parseFloat(dept.total_net) / parseInt(dept.staff_count);
            
            html += '<tr class="dept-row" data-dept-id="' + dept.department_id + '" data-dept-name="' + dept.department_name + '" style="cursor: pointer;">';
            html += '<td class="expand-icon"><i class="entypo-right-open-big"></i></td>';
            html += '<td><strong>' + dept.department_name + '</strong></td>';
            html += '<td>' + dept.staff_count + '</td>';
            html += '<td>GH¢ ' + formatNumber(dept.total_gross) + '</td>';
            html += '<td>GH¢ ' + formatNumber(dept.total_deductions) + '</td>';
            html += '<td>GH¢ ' + formatNumber(dept.total_net) + '</td>';
            html += '<td>' + dept.percentage + '%</td>';
            html += '<td>GH¢ ' + formatNumber(avgPerStaff) + '</td>';
            html += '</tr>';
            html += '<tr class="dept-details" data-dept-id="' + dept.department_id + '" style="display: none;"><td colspan="8" style="padding: 0;"></td></tr>';
        });
        
        $('#dept_summary_body').html(html);
        
        // Update grand totals
        $('#grand_total_staff').text(grandTotal.staff_count);
        $('#grand_total_gross').text('GH¢ ' + formatNumber(grandTotal.gross));
        $('#grand_total_deductions').text('GH¢ ' + formatNumber(grandTotal.deductions));
        $('#grand_total_net').text('GH¢ ' + formatNumber(grandTotal.net));
        $('#dept_grand_total_footer').show();
        
        // Attach click handlers for expandable rows
        $('.dept-row').on('click', function() {
            var deptId = $(this).data('dept-id');
            var deptName = $(this).data('dept-name');
            var detailsRow = $('.dept-details[data-dept-id="' + deptId + '"]');
            var icon = $(this).find('.expand-icon i');
            
            if (detailsRow.is(':visible')) {
                // Collapse
                detailsRow.hide();
                icon.removeClass('entypo-down-open-big').addClass('entypo-right-open-big');
            } else {
                // Expand
                if (detailsRow.find('table').length === 0) {
                    loadStaffDetails(deptId, deptName, detailsRow);
                }
                detailsRow.show();
                icon.removeClass('entypo-right-open-big').addClass('entypo-down-open-big');
            }
        });
    }
    
    // Load staff details for a department
    function loadStaffDetails(deptId, deptName, detailsRow) {
        var month = $('#dept_filter_month').val();
        var year = $('#dept_filter_year').val();
        
        detailsRow.find('td').html('<div style="text-align: center; padding: 20px;"><i class="entypo-arrows-ccw" style="animation: spin 1s linear infinite;"></i> Loading staff details...</div>');
        
        $.ajax({
            url: '<?php echo base_url(); ?>index.php?admin/get_department_staff_details',
            type: 'POST',
            data: {
                department_id: deptId,
                month: month,
                year: year,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var staffHtml = '<table class="table table-condensed staff-details-table" style="margin: 0; background-color: #f9f9f9;">';
                    staffHtml += '<thead><tr style="background-color: #eee;">';
                    staffHtml += '<th>Staff Name</th><th>Staff Code</th><th>Category</th><th>Basic Salary</th><th>Allowances</th><th>Gross</th><th>Deductions</th><th>Net</th>';
                    staffHtml += '</tr></thead><tbody>';
                    
                    $.each(response.data, function(i, staff) {
                        staffHtml += '<tr>';
                        staffHtml += '<td>' + staff.staff_name + '</td>';
                        staffHtml += '<td>' + staff.staff_code + '</td>';
                        staffHtml += '<td>' + staff.category + '</td>';
                        staffHtml += '<td>GH¢ ' + formatNumber(staff.basic_salary) + '</td>';
                        staffHtml += '<td>GH¢ ' + formatNumber(staff.total_allowances) + '</td>';
                        staffHtml += '<td>GH¢ ' + formatNumber(staff.gross_salary) + '</td>';
                        staffHtml += '<td>GH¢ ' + formatNumber(staff.total_deductions) + '</td>';
                        staffHtml += '<td>GH¢ ' + formatNumber(staff.net_salary) + '</td>';
                        staffHtml += '</tr>';
                    });
                    
                    staffHtml += '</tbody></table>';
                    detailsRow.find('td').html(staffHtml);
                } else {
                    detailsRow.find('td').html('<div style="text-align: center; padding: 20px; color: #999;">No staff details available.</div>');
                }
            },
            error: function() {
                detailsRow.find('td').html('<div style="text-align: center; padding: 20px; color: #d9534f;">Error loading staff details.</div>');
            }
        });
    }
    
    // Render department comparison chart
    function renderDepartmentChart(data) {
        if (!data.departments || data.departments.length === 0) {
            return;
        }
        
        var labels = [];
        var grossData = [];
        var deductionsData = [];
        var netData = [];
        
        $.each(data.departments, function(index, dept) {
            labels.push(dept.department_name);
            grossData.push(parseFloat(dept.total_gross));
            deductionsData.push(parseFloat(dept.total_deductions));
            netData.push(parseFloat(dept.total_net));
        });
        
        var ctx = document.getElementById('deptCostChart').getContext('2d');
        
        // Destroy existing chart if any
        if (deptChartInstance) {
            deptChartInstance.destroy();
        }
        
        deptChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Gross Salary',
                        data: grossData,
                        backgroundColor: 'rgba(92, 184, 92, 0.7)',
                        borderColor: 'rgba(92, 184, 92, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Deductions',
                        data: deductionsData,
                        backgroundColor: 'rgba(240, 173, 78, 0.7)',
                        borderColor: 'rgba(240, 173, 78, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Net Salary',
                        data: netData,
                        backgroundColor: 'rgba(51, 122, 183, 0.7)',
                        borderColor: 'rgba(51, 122, 183, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'GH¢ ' + formatNumber(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': GH¢ ' + formatNumber(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update summary cards
    function updateSummaryCards(data) {
        $('#total_departments').text(data.departments.length);
        
        var totalStaff = 0;
        var totalGross = 0;
        var totalNet = 0;
        
        $.each(data.departments, function(index, dept) {
            totalStaff += parseInt(dept.staff_count);
            totalGross += parseFloat(dept.total_gross);
            totalNet += parseFloat(dept.total_net);
        });
        
        $('#total_staff').text(totalStaff);
        $('#total_gross_summary').text('GH¢ ' + formatNumber(totalGross));
        $('#total_net_summary').text('GH¢ ' + formatNumber(totalNet));
    }
    
    // Helper: Format number
    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    // Helper: Show error
    function showError(message) {
        $('#dept_summary_body').html('<tr><td colspan="8" style="text-align: center; padding: 30px;"><i class="entypo-attention" style="font-size: 48px; color: #d9534f;"></i><p style="color: #d9534f; margin-top: 10px;">' + message + '</p></td></tr>');
        $('#dept_grand_total_footer').hide();
    }
});
</script>

<!-- Custom CSS -->
<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .dept-row:hover {
        background-color: #f0f8ff !important;
    }
    
    .dept-row .expand-icon {
        text-align: center;
        font-size: 14px;
        color: #337ab7;
    }
    
    .staff-details-table {
        font-size: 12px;
    }
    
    .staff-details-table th,
    .staff-details-table td {
        padding: 5px 8px !important;
    }
    
    .info-box h4 {
        font-size: 14px;
        font-weight: normal;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-box h2 {
        font-size: 28px;
        font-weight: bold;
    }
</style>
