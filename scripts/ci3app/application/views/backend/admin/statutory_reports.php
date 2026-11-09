<!-- Statutory Compliance Reports (Task 14.6) -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-doc-text"></i>
                    <?php echo get_phrase('statutory_compliance_reports'); ?>
                </div>
            </div>
            <div class="panel-body">
                
                <!-- Filters -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('month'); ?>:</label>
                            <select name="stat_filter_month" id="stat_filter_month" class="form-control">
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
                            <select name="stat_filter_year" id="stat_filter_year" class="form-control">
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
                            <button type="button" class="btn btn-primary btn-block" id="validateStaffBtn">
                                <i class="entypo-check"></i> <?php echo get_phrase('validate_staff'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-success btn-block" id="generateReportsBtn" disabled>
                                <i class="entypo-docs"></i> <?php echo get_phrase('generate_reports'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Validation Results -->
                <div id="validationResults" style="display: none; margin-bottom: 20px;">
                    <div class="alert" id="validationAlert">
                        <h4><i class="entypo-attention"></i> <span id="validationTitle"></span></h4>
                        <div id="validationMessage"></div>
                    </div>
                </div>

                <!-- Tabs for Different Reports -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="active">
                        <a href="#ssnit_tier1_tab" role="tab" data-toggle="tab">
                            <i class="entypo-doc-text"></i> <?php echo get_phrase('ssnit_tier_1_report'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#ssnit_tier2_tab" role="tab" data-toggle="tab">
                            <i class="entypo-doc-text"></i> <?php echo get_phrase('ssnit_tier_2_report'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#paye_tab" role="tab" data-toggle="tab">
                            <i class="entypo-doc-text"></i> <?php echo get_phrase('paye_report'); ?>
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" style="padding-top: 20px;">
                    
                    <!-- SSNIT Tier 1 Report Tab -->
                    <div class="tab-pane active" id="ssnit_tier1_tab">
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-12">
                                <button class="btn btn-success pull-right" id="exportTier1ExcelBtn" disabled>
                                    <i class="entypo-download"></i> <?php echo get_phrase('export_to_excel'); ?>
                                </button>
                                <h4><?php echo get_phrase('ssnit_tier_1_contributions_135'); ?></h4>
                            </div>
                        </div>
                        
                        <!-- School Information Header -->
                        <div id="tier1_school_info" style="display: none; margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #337ab7;">
                            <h5 style="margin-top: 0;"><strong><?php echo get_phrase('school_information'); ?></strong></h5>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('school_name'); ?>:</strong> <span id="tier1_school_name"></span></p>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('ssnit_number'); ?>:</strong> <span id="tier1_ssnit_number"></span></p>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('period'); ?>:</strong> <span id="tier1_period"></span></p>
                        </div>
                        
                        <table class="table table-bordered table-striped" id="ssnit_tier1_table">
                            <thead>
                                <tr style="background-color: #337ab7; color: white;">
                                    <th><?php echo get_phrase('staff_name'); ?></th>
                                    <th><?php echo get_phrase('ssnit_number'); ?></th>
                                    <th><?php echo get_phrase('gross_salary'); ?> (GH¢)</th>
                                    <th><?php echo get_phrase('tier_1_contribution'); ?> (13.5%)</th>
                                </tr>
                            </thead>
                            <tbody id="ssnit_tier1_body">
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #999;">
                                        <?php echo get_phrase('click_generate_reports_to_load_data'); ?>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="tier1_total_footer" style="display: none;">
                                <tr style="font-weight: bold; background-color: #f5f5f5; border-top: 3px solid #333;">
                                    <td colspan="2"><?php echo get_phrase('total'); ?>:</td>
                                    <td id="tier1_total_gross">GH¢ 0.00</td>
                                    <td id="tier1_total_contribution">GH¢ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- SSNIT Tier 2 Report Tab -->
                    <div class="tab-pane" id="ssnit_tier2_tab">
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-12">
                                <button class="btn btn-success pull-right" id="exportTier2ExcelBtn" disabled>
                                    <i class="entypo-download"></i> <?php echo get_phrase('export_to_excel'); ?>
                                </button>
                                <h4><?php echo get_phrase('ssnit_tier_2_contributions_5'); ?></h4>
                            </div>
                        </div>
                        
                        <!-- School Information Header -->
                        <div id="tier2_school_info" style="display: none; margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #5cb85c;">
                            <h5 style="margin-top: 0;"><strong><?php echo get_phrase('school_information'); ?></strong></h5>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('school_name'); ?>:</strong> <span id="tier2_school_name"></span></p>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('ssnit_number'); ?>:</strong> <span id="tier2_ssnit_number"></span></p>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('period'); ?>:</strong> <span id="tier2_period"></span></p>
                        </div>
                        
                        <table class="table table-bordered table-striped" id="ssnit_tier2_table">
                            <thead>
                                <tr style="background-color: #5cb85c; color: white;">
                                    <th><?php echo get_phrase('staff_name'); ?></th>
                                    <th><?php echo get_phrase('ssnit_number'); ?></th>
                                    <th><?php echo get_phrase('gross_salary'); ?> (GH¢)</th>
                                    <th><?php echo get_phrase('tier_2_contribution'); ?> (5%)</th>
                                </tr>
                            </thead>
                            <tbody id="ssnit_tier2_body">
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #999;">
                                        <?php echo get_phrase('click_generate_reports_to_load_data'); ?>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="tier2_total_footer" style="display: none;">
                                <tr style="font-weight: bold; background-color: #f5f5f5; border-top: 3px solid #333;">
                                    <td colspan="2"><?php echo get_phrase('total'); ?>:</td>
                                    <td id="tier2_total_gross">GH¢ 0.00</td>
                                    <td id="tier2_total_contribution">GH¢ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- PAYE Report Tab -->
                    <div class="tab-pane" id="paye_tab">
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-12">
                                <button class="btn btn-success pull-right" id="exportPayeExcelBtn" disabled>
                                    <i class="entypo-download"></i> <?php echo get_phrase('export_to_excel'); ?>
                                </button>
                                <h4><?php echo get_phrase('paye_tax_report'); ?></h4>
                            </div>
                        </div>
                        
                        <!-- School Information Header -->
                        <div id="paye_school_info" style="display: none; margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #f0ad4e;">
                            <h5 style="margin-top: 0;"><strong><?php echo get_phrase('school_information'); ?></strong></h5>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('school_name'); ?>:</strong> <span id="paye_school_name"></span></p>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('tin'); ?>:</strong> <span id="paye_tin"></span></p>
                            <p style="margin: 5px 0;"><strong><?php echo get_phrase('period'); ?>:</strong> <span id="paye_period"></span></p>
                        </div>
                        
                        <table class="table table-bordered table-striped" id="paye_table">
                            <thead>
                                <tr style="background-color: #f0ad4e; color: white;">
                                    <th><?php echo get_phrase('staff_name'); ?></th>
                                    <th><?php echo get_phrase('tin'); ?></th>
                                    <th><?php echo get_phrase('gross_salary'); ?> (GH¢)</th>
                                    <th><?php echo get_phrase('taxable_income'); ?> (GH¢)</th>
                                    <th><?php echo get_phrase('paye_tax'); ?> (GH¢)</th>
                                </tr>
                            </thead>
                            <tbody id="paye_body">
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #999;">
                                        <?php echo get_phrase('click_generate_reports_to_load_data'); ?>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="paye_total_footer" style="display: none;">
                                <tr style="font-weight: bold; background-color: #f5f5f5; border-top: 3px solid #333;">
                                    <td colspan="2"><?php echo get_phrase('total'); ?>:</td>
                                    <td id="paye_total_gross">GH¢ 0.00</td>
                                    <td id="paye_total_taxable">GH¢ 0.00</td>
                                    <td id="paye_total_tax">GH¢ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Statutory Reports -->
<script type="text/javascript">
var reportData = {
    tier1: [],
    tier2: [],
    paye: [],
    schoolInfo: {}
};

$(document).ready(function() {
    
    // Validate staff before generating reports
    $('#validateStaffBtn').on('click', function() {
        var month = $('#stat_filter_month').val();
        var year = $('#stat_filter_year').val();
        
        $(this).prop('disabled', true).html('<i class="entypo-arrows-ccw"></i> Validating...');
        
        $.ajax({
            url: '<?php echo base_url(); ?>index.php?admin/validate_statutory_staff',
            type: 'POST',
            data: {
                month: month,
                year: year,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                $('#validateStaffBtn').prop('disabled', false).html('<i class="entypo-check"></i> Validate Staff');
                
                if (response.valid) {
                    $('#validationResults').show();
                    $('#validationAlert').removeClass('alert-danger').addClass('alert-success');
                    $('#validationTitle').text('Validation Successful');
                    $('#validationMessage').html('<p><strong>All staff have required identifiers.</strong></p><p>' + response.staff_count + ' staff members validated successfully.</p>');
                    $('#generateReportsBtn').prop('disabled', false);
                } else {
                    $('#validationResults').show();
                    $('#validationAlert').removeClass('alert-success').addClass('alert-danger');
                    $('#validationTitle').text('Validation Failed');
                    
                    var html = '<p><strong>The following staff members are missing required information:</strong></p><ul>';
                    $.each(response.missing_data, function(i, staff) {
                        html += '<li><strong>' + staff.name + '</strong>: Missing ' + staff.missing.join(', ') + '</li>';
                    });
                    html += '</ul><p>Please update staff information before generating statutory reports.</p>';
                    
                    $('#validationMessage').html(html);
                    $('#generateReportsBtn').prop('disabled', true);
                }
            },
            error: function() {
                $('#validateStaffBtn').prop('disabled', false).html('<i class="entypo-check"></i> Validate Staff');
                alert('Error validating staff. Please try again.');
            }
        });
    });
    
    // Generate all reports
    $('#generateReportsBtn').on('click', function() {
        var month = $('#stat_filter_month').val();
        var year = $('#stat_filter_year').val();
        
        $(this).prop('disabled', true).html('<i class="entypo-arrows-ccw"></i> Generating...');
        
        $.ajax({
            url: '<?php echo base_url(); ?>index.php?admin/generate_statutory_reports',
            type: 'POST',
            data: {
                month: month,
                year: year,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                $('#generateReportsBtn').prop('disabled', false).html('<i class="entypo-docs"></i> Generate Reports');
                
                if (response.success) {
                    reportData = response.data;
                    renderTier1Report(response.data.tier1, response.data.school_info, month, year);
                    renderTier2Report(response.data.tier2, response.data.school_info, month, year);
                    renderPayeReport(response.data.paye, response.data.school_info, month, year);
                    
                    // Enable export buttons
                    $('#exportTier1ExcelBtn, #exportTier2ExcelBtn, #exportPayeExcelBtn').prop('disabled', false);
                } else {
                    alert('Failed to generate reports: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                $('#generateReportsBtn').prop('disabled', false).html('<i class="entypo-docs"></i> Generate Reports');
                alert('Network error generating reports. Please try again.');
            }
        });
    });
    
    // Render SSNIT Tier 1 Report
    function renderTier1Report(data, schoolInfo, month, year) {
        if (!data || data.length === 0) {
            $('#ssnit_tier1_body').html('<tr><td colspan="4" style="text-align: center; padding: 30px; color: #999;">No data available for this period.</td></tr>');
            $('#tier1_school_info, #tier1_total_footer').hide();
            return;
        }
        
        // Display school info
        var monthName = new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long' });
        $('#tier1_school_name').text(schoolInfo.name);
        $('#tier1_ssnit_number').text(schoolInfo.ssnit_number || 'N/A');
        $('#tier1_period').text(monthName + ' ' + year);
        $('#tier1_school_info').show();
        
        // Render table
        var html = '';
        var totalGross = 0;
        var totalContribution = 0;
        
        $.each(data, function(i, row) {
            totalGross += parseFloat(row.gross_salary);
            totalContribution += parseFloat(row.tier1_contribution);
            
            html += '<tr>';
            html += '<td>' + row.staff_name + '</td>';
            html += '<td>' + row.ssnit_number + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.gross_salary) + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.tier1_contribution) + '</td>';
            html += '</tr>';
        });
        
        $('#ssnit_tier1_body').html(html);
        
        // Update totals
        $('#tier1_total_gross').text('GH¢ ' + formatNumber(totalGross));
        $('#tier1_total_contribution').text('GH¢ ' + formatNumber(totalContribution));
        $('#tier1_total_footer').show();
    }
    
    // Render SSNIT Tier 2 Report
    function renderTier2Report(data, schoolInfo, month, year) {
        if (!data || data.length === 0) {
            $('#ssnit_tier2_body').html('<tr><td colspan="4" style="text-align: center; padding: 30px; color: #999;">No data available for this period.</td></tr>');
            $('#tier2_school_info, #tier2_total_footer').hide();
            return;
        }
        
        // Display school info
        var monthName = new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long' });
        $('#tier2_school_name').text(schoolInfo.name);
        $('#tier2_ssnit_number').text(schoolInfo.ssnit_number || 'N/A');
        $('#tier2_period').text(monthName + ' ' + year);
        $('#tier2_school_info').show();
        
        // Render table
        var html = '';
        var totalGross = 0;
        var totalContribution = 0;
        
        $.each(data, function(i, row) {
            totalGross += parseFloat(row.gross_salary);
            totalContribution += parseFloat(row.tier2_contribution);
            
            html += '<tr>';
            html += '<td>' + row.staff_name + '</td>';
            html += '<td>' + row.ssnit_number + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.gross_salary) + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.tier2_contribution) + '</td>';
            html += '</tr>';
        });
        
        $('#ssnit_tier2_body').html(html);
        
        // Update totals
        $('#tier2_total_gross').text('GH¢ ' + formatNumber(totalGross));
        $('#tier2_total_contribution').text('GH¢ ' + formatNumber(totalContribution));
        $('#tier2_total_footer').show();
    }
    
    // Render PAYE Report
    function renderPayeReport(data, schoolInfo, month, year) {
        if (!data || data.length === 0) {
            $('#paye_body').html('<tr><td colspan="5" style="text-align: center; padding: 30px; color: #999;">No data available for this period.</td></tr>');
            $('#paye_school_info, #paye_total_footer').hide();
            return;
        }
        
        // Display school info
        var monthName = new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long' });
        $('#paye_school_name').text(schoolInfo.name);
        $('#paye_tin').text(schoolInfo.tin || 'N/A');
        $('#paye_period').text(monthName + ' ' + year);
        $('#paye_school_info').show();
        
        // Render table
        var html = '';
        var totalGross = 0;
        var totalTaxable = 0;
        var totalTax = 0;
        
        $.each(data, function(i, row) {
            totalGross += parseFloat(row.gross_salary);
            totalTaxable += parseFloat(row.taxable_income);
            totalTax += parseFloat(row.paye_tax);
            
            html += '<tr>';
            html += '<td>' + row.staff_name + '</td>';
            html += '<td>' + row.tin + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.gross_salary) + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.taxable_income) + '</td>';
            html += '<td>GH¢ ' + formatNumber(row.paye_tax) + '</td>';
            html += '</tr>';
        });
        
        $('#paye_body').html(html);
        
        // Update totals
        $('#paye_total_gross').text('GH¢ ' + formatNumber(totalGross));
        $('#paye_total_taxable').text('GH¢ ' + formatNumber(totalTaxable));
        $('#paye_total_tax').text('GH¢ ' + formatNumber(totalTax));
        $('#paye_total_footer').show();
    }
    
    // Export to Excel handlers
    $('#exportTier1ExcelBtn').on('click', function() {
        exportToExcel('tier1');
    });
    
    $('#exportTier2ExcelBtn').on('click', function() {
        exportToExcel('tier2');
    });
    
    $('#exportPayeExcelBtn').on('click', function() {
        exportToExcel('paye');
    });
    
    function exportToExcel(reportType) {
        var month = $('#stat_filter_month').val();
        var year = $('#stat_filter_year').val();
        
        var url = '<?php echo base_url(); ?>index.php?admin/export_statutory_report_excel&type=' + reportType + '&month=' + month + '&year=' + year;
        window.location.href = url;
    }
    
    // Helper: Format number
    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
});
</script>

<!-- Custom CSS -->
<style>
    .nav-tabs > li > a {
        font-weight: 600;
    }
    
    .tab-content {
        border: 1px solid #ddd;
        border-top: none;
        padding: 20px;
        background-color: white;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
