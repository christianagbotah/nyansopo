<!doctype html>
<html>
    <head>
        <title>INCOME AND EXPENDITURE ACCOUNT</title>
        <?php include 'includes/includes_top.php';?>

    </head>
    <body> 
        <?php
        
        ?>

        <div class="container" id="top">
            
            <!-- Page Title -->
            <div class="page-title-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                    <i class="fa fa-bar-chart" style="margin-right: 12px;"></i>Income & Expenditure Report
                </h1>
                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500;">
                    View and analyze income and expenditure transactions
                </p>
            </div>

            <!-- Report Navigation Tabs -->
            <div class="report-tabs" style="background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-file-text"></i> Receivables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payables'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-file-text"></i> Payables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payments'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-money"></i> Payments
                </a>
                <a href="<?php echo site_url('admin/financial_reports/income-expenditure'); ?>" class="tab-link active" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                    <i class="fa fa-bar-chart"></i> Income & Expenditure
                </a>
                <a href="<?php echo site_url('admin/financial_reports/monthly-payment-by-item'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-chart-bar"></i> Monthly Payment Report
                </a>
            </div>

            <style>
                .tab-link:hover:not(.active) {
                    background: #e5e7eb !important;
                    color: #1f2937 !important;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                @media print {
                    .report-tabs { display: none !important; }
                    .page-title-header { display: none !important; }
                }
            </style>
            <style>
                .filters-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
                .filter-group label { display:block; font-weight:600; margin-bottom:8px; color:#111827; font-size:14px; }
                .modern-input { width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:14px; transition:all 0.3s; height:42px; color:#111827; font-weight:500; }
                .modern-input:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
                .btn-modern { padding:10px 20px; border-radius:10px; font-size:14px; font-weight:600; border:none; cursor:pointer; transition:all 0.3s; height:42px; display:inline-flex; align-items:center; gap:8px; }
                .btn-primary-modern { background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; box-shadow:0 4px 12px rgba(59,130,246,0.3); }
                .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(59,130,246,0.4); }
                .btn-success-modern { background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
                .btn-success-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,0.4); }
                .btn-danger-modern { background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:white; box-shadow:0 4px 12px rgba(239,68,68,0.3); }
                .btn-danger-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(239,68,68,0.4); }
                .btn-excel-modern { background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
                .btn-excel-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,0.4); }
                #selectors_div { padding:20px 0; position:sticky; top:0; z-index:100; background:#f9fafb; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
            </style>
            <div class="row" id="selectors_div">
                <div class="col-sm-12">
                    <div class="filters-card">
                        <!-- Header Row with Title and Action Buttons -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                            <h3 style="margin:0; font-size:20px; font-weight:700; color:#1f2937;">
                                <i class="fa fa-chart-line" style="color:#3b82f6;"></i> Income & Expenditure Report
                            </h3>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" onclick="$('#incomeExpenditure_load').submit();" class="btn-modern btn-success-modern">
                                    <i class="fa fa-sync"></i> Load
                                </button>
                                <button type="button" onclick="exportToExcel()" class="btn-modern btn-excel-modern">
                                    <i class="fa fa-file-excel"></i> Excel
                                </button>
                                <button type="button" onclick="PrintElem('#print')" class="btn-modern btn-primary-modern">
                                    <i class="fa fa-print"></i> Print
                                </button>
                                <button type="button" onclick="dashboard()" class="btn-modern btn-danger-modern">
                                    <i class="fa fa-home"></i> Dashboard
                                </button>
                            </div>
                        </div>

                        <!-- Filter Form -->
                        <?php echo form_open(site_url('admin/financial_reports/income-expenditure/load'), array('id' => 'incomeExpenditure_load'));?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> Start Date</label>
                                    <input type="text" name="start_date" id="start_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> End Date</label>
                                    <input type="text" name="end_date" id="end_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <br><br>
            <div class="row">
                <div class="col-lg-12">
                    <div id="print">
                        <?php
                        //Account section
                        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
                        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

                        $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
                        $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
                        $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
                        $phone            =   $this->db->get_where('settings' , array('type'=>'phone'))->row()->description;


                        ?>

                       

                        <style>
                            #invoice_table { width:100%; border-collapse:collapse; margin-top:20px; }
                            #invoice_table thead { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
                            #invoice_table thead th { color:white; padding:15px; font-weight:600; text-align:left; }
                            #invoice_table tbody td { padding:12px; border-bottom:1px solid #e5e7eb; color:#374151; }
                            #invoice_table tbody tr:hover { background:#f9fafb; }
                            #top_row { border:none; }
                            #top_row td { border:none; padding:10px; }
                            .report-header { font-weight:700; font-size:20px; letter-spacing:2px; color:#1f2937; }
                            .report-subheader { font-size:14px; color:#6b7280; margin-top:5px; }
                            @media print {
                                @page { margin: 0.3cm; }
                                * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                                html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; font-size: 9px !important; color: #000 !important; }
                                #selectors_div { display:none !important; }
                                #logo_ { width:80px !important; }
                                .filters-card { display:none !important; }
                                .row:has(canvas) { display:none !important; }
                                canvas { display:none !important; }
                                .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
                                .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
                                .col-lg-12, .col-md-12, .col-sm-12, [class*="col-"] { padding: 0 !important; width: 100% !important; max-width: none !important; }
                                #print { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
                                page { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; display: block !important; }
                                center { width: 100% !important; max-width: none !important; display: block !important; }
                                table { width: 100% !important; max-width: none !important; border-collapse: collapse !important; }
                                /* Bank Statement Style - 9px fonts */
                                #invoice_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 9px !important; table-layout: auto !important; }
                                #invoice_table th, #invoice_table td { padding: 4px 5px !important; word-wrap: break-word !important; border: 1px solid #ccc !important; font-size: 9px !important; line-height: 1.2 !important; }
                                #invoice_table thead { background: #f3f4f6 !important; }
                                #invoice_table thead th { background: #f3f4f6 !important; color: #111827 !important; }
                                #invoice_table th { background: #f3f4f6 !important; color: #111827 !important; font-weight: bold !important; font-size: 9px !important; }
                                /* Period display fix */
                                div { width: auto !important; max-width: none !important; display: block !important; font-size: inherit !important; color: inherit !important; }
                                #top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; }
                                #top_row td { padding: 5px !important; }
                                #top_row h3 { display: block !important; visibility: visible !important; font-size: 11px !important; font-weight: bold !important; }
                                #top_row p { display: block !important; visibility: visible !important; font-size: 9px !important; }
                                #top_row img { display: inline-block !important; visibility: visible !important; }
                                #reportPeriod { display: inline !important; visibility: visible !important; font-size: 11px !important; color: #000 !important; }
                                .report-header { font-size: 16px !important; margin: 0 !important; font-weight: bold !important; }
                                .report-subheader { font-size: 9px !important; margin: 0 !important; }
                                .table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }
                                h3 { font-size: 11px !important; margin: 4px 0 !important; display: block !important; visibility: visible !important; }
                                strong { font-size: 9px !important; display: inline !important; visibility: visible !important; }
                            }
                            
                            /* Fix modal position - move down from top */
                            .modal {
                                margin-top: 100px !important;
                            }
                            .modal-dialog {
                                margin: 100px auto !important;
                            }
                            .bootbox.modal {
                                top: 100px !important;
                            }
                        </style>
                    <page size="A4">
                        <center>
                            <table id="top_row">
                                <tbody>
                                    <tr>
                                        <td width="60%" style="vertical-align:top;">
                                            <table style="width:100%; border-collapse:collapse;">
                                                <tr>
                                                    <td style="width:80px;">
                                                        <img id="logo_" src="<?php echo base_url(); ?>uploads/school_logo.png" width="60px">
                                                    </td>
                                                    <td style="vertical-align:top;">
                                                        <h3 class="report-header" style="margin: 0 0 5px 0 !important;"><?php echo $system_name; ?></h3>
                                                        <pre style="margin: 0 !important; padding: 0 !important; white-space: pre-wrap !important; font-family: inherit !important; font-size: 12px !important; line-height: 1.3 !important;"><?php echo trim($address); ?></pre>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="40%" style="text-align:right; vertical-align:top;">
                                            <h3 class="report-header">INCOME AND EXPENDITURE REPORT</h3>
                                            <p class="report-subheader" style="text-align:right;">
                                                <strong>Period:</strong> <b id="reportPeriod" style="font-size: 11px !important; color: #000 !important; display: inline !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important;">-</b>
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </center>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="table-responsive">
                                    <table id="invoice_table">
                                        <thead>
                                            <tr>
                                                <th width="60%">ITEMS</th>
                                                <th style="text-align:left;">SUB-TOTAL</th>
                                                <th style="text-align:right;">GRAND TOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" style="text-align:center; color:#6b7280; padding:40px;">
                                                    <i class="fa fa-info-circle" style="font-size:48px; margin-bottom:10px; color:#3b82f6;"></i><br>
                                                    <strong>Please select date range and click Load to view the report</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </page>
                    </div>
                 </div>
            </div>

            <?php
                include 'includes/modal.php';
            ?>

        </div>

    
    <?php include 'includes/includes_bottom.php';?>

</body>
</html>    

<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>
    
<script type="text/javascript">
    jQuery(document).ready(function($)
    {
         
         //called to update the crsf name and key
         $.ajaxSetup({
            //cache: false,
            data: {
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            } 
         }); 

      //Load data here
      $('#incomeExpenditure_load').submit(function(ev) {
        ev.preventDefault();

        // Validate date inputs
        var startDate = $('input[name="start_date"]').val();
        var endDate = $('input[name="end_date"]').val();

        if (!startDate || !endDate) {
            showAjaxModal_alert('Please select both start date and end date', 'error');
            return;
        }

        // Validate date range (basic check - start should not be after end)
        var startParts = startDate.split('-');
        var endParts = endDate.split('-');
        var startDateObj = new Date(startParts[2], startParts[1] - 1, startParts[0]);
        var endDateObj = new Date(endParts[2], endParts[1] - 1, endParts[0]);

        if (startDateObj > endDateObj) {
            showAjaxModal_alert('Start date cannot be after end date', 'error');
            return;
        }

        // Scroll to the top for better visibility
        $('html, body').animate({
            scrollTop: ($('#top').offset().top)
        }, 1000);

        // Show loading state with spinner
        showAjaxModal_alert('Fetching Data. Please wait... <i class="fa fa-spinner fa-pulse"></i>', 'loading');

        $.ajax({
            url: '<?php echo site_url('admin/financial_reports/income-expenditure/load'); ?>',
            type: 'POST',
            dataType: 'html',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        })
        .done(function(data) {
            // Check if data is empty or contains error message
            if (!data || data.trim() === '') {
                $('#invoice_table tbody').html('<tr><td colspan="3" style="text-align:center; color:#6b7280; padding:40px;"><i class="fa fa-exclamation-circle" style="font-size:48px; margin-bottom:10px; color:#f59e0b;"></i><br><strong>No data found for the selected date range</strong></td></tr>');
                
                // Show warning message after brief delay
                setTimeout(function() {
                    showAjaxModal_alert('No data found for the selected period', 'warning');
                }, 500);

                // Auto-close modal
                setTimeout(function() {
                    $('.close').click();
                }, 3000);
                
                return;
            }

            // Display data in table
            $('#invoice_table tbody').html(data);

            // Update report header with selected filters
            updateReportHeader();

            // Show success message after 2 seconds
            setTimeout(function() {
                showAjaxModal_alert('Data Fetched Successfully', 'success');
            }, 2000);

            // Auto-close modal after 3.5 seconds
            setTimeout(function() {
                $('.close').click();
            }, 3500);
        })
        .fail(function(err) {
            console.error('AJAX Error:', err);
            
            // Display error message in table
            $('#invoice_table tbody').html('<tr><td colspan="3" style="text-align:center; color:#ef4444; padding:40px;"><i class="fa fa-times-circle" style="font-size:48px; margin-bottom:10px;"></i><br><strong>Error loading data. Please try again.</strong></td></tr>');
            
            // Show error modal
            showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px;">Error loading data!</div></center>', 'error');
        });
      });

    });
    
    function updateReportHeader() {
        var startDate = $('input[name="start_date"]').val();
        var endDate = $('input[name="end_date"]').val();
        
        $('#reportPeriod').text(startDate + ' to ' + endDate);
    }
    
    function exportToExcel() {
        const table = document.getElementById('invoice_table');
        const tbody = table.querySelector('tbody');
        
        // Check if tbody exists and has rows
        if (!tbody || !tbody.querySelector('tr')) {
            showAjaxModal_alert('No data available to export. Please load data first.', 'warning');
            return;
        }
        
        // Check for the specific placeholder message (not just any colspan)
        const placeholderCell = tbody.querySelector('td[colspan]');
        if (placeholderCell) {
            const cellText = placeholderCell.textContent.trim();
            // Check if it contains the placeholder message text
            if (cellText.includes('Please select date range') || cellText.includes('No data found')) {
                showAjaxModal_alert('No data available to export. Please load data first.', 'warning');
                return;
            }
        }
        
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Income & Expenditure');
        
        const filename = 'Income_Expenditure_' + new Date().toISOString().slice(0,10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }
    
    function dashboard() {
        location.href = '<?=site_url('admin/dashboard'); ?>';
    }
   
    // Print Function - Use native browser print
    function PrintElem(elem) {
        const table = document.getElementById('invoice_table');
        const tbody = table.querySelector('tbody');
        
        // Enhanced data validation before printing
        if (!tbody) {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        // Check if tbody has any rows
        const rows = tbody.querySelectorAll('tr');
        if (rows.length === 0) {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        // Check for the specific placeholder message
        const placeholderCell = tbody.querySelector('td[colspan]');
        if (placeholderCell) {
            const cellText = placeholderCell.textContent.trim();
            if (cellText.includes('Please select date range') || cellText.includes('No data found')) {
                showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
                return;
            }
        }
        
        // Validate that we have actual data rows
        let hasData = false;
        rows.forEach(function(row) {
            const cells = row.querySelectorAll('td');
            cells.forEach(function(cell) {
                const text = cell.textContent.trim();
                if (text.includes('INCOME') || text.includes('EXPENDITURE') || text.includes('TOTAL')) {
                    hasData = true;
                }
            });
        });
        
        if (!hasData) {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        // All validations passed, use native browser print
        window.print();
    }

    // Auto-close datepicker when date is selected
    $(document).ready(function() {
        $('#start_date, #end_date').on('changeDate', function() {
            $(this).datepicker('hide');
        });
        
        // Alternative method for bootstrap-datepicker
        $('#start_date, #end_date').on('change', function() {
            if($(this).data('datepicker')) {
                $(this).datepicker('hide');
            }
        });
    });

    function Popup(data)
    {
        // Improved print window handling
        var mywindow = window.open('', '', '');
        
        // Check if window was successfully opened (popup blocker check)
        if (!mywindow) {
            showAjaxModal_alert('Print window was blocked. Please allow popups for this site.', 'error');
            return;
        }
        
        mywindow.document.write('<!doctype html><html><head><title>INCOME AND EXPENDITURE ACCOUNT</title>');
        
        // Include necessary stylesheets for proper rendering
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-forms.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-core.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/bootstrap.css');?>" />');

        // Add comprehensive inline print styles for landscape optimization
        mywindow.document.write('<style type="text/css">');
        mywindow.document.write('@page { size: landscape; margin: 0.3cm; }');
        mywindow.document.write('* { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }');
        mywindow.document.write('html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }');
        mywindow.document.write('.container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }');
        mywindow.document.write('.row { margin: 0 !important; width: 100% !important; max-width: none !important; }');
        mywindow.document.write('.col-lg-12, .col-md-12, .col-sm-12, [class*="col-"] { padding: 0 !important; width: 100% !important; max-width: none !important; }');
        mywindow.document.write('page { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; display: block !important; }');
        mywindow.document.write('center { width: 100% !important; max-width: none !important; display: block !important; }');
        mywindow.document.write('table { width: 100% !important; max-width: none !important; border-collapse: collapse !important; }');
        mywindow.document.write('#invoice_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 11px !important; table-layout: auto !important; }');
        mywindow.document.write('#invoice_table th, #invoice_table td { padding: 5px 6px !important; word-wrap: break-word !important; border: 1px solid #ddd !important; font-size: 11px !important; }');
        mywindow.document.write('#invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; font-size: 16px !important; }');
        mywindow.document.write('#top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; }');
        mywindow.document.write('#top_row td { padding: 5px !important; }');
        mywindow.document.write('.report-header { font-size: 16px !important; margin: 0 !important; font-weight: bold !important; }');
        mywindow.document.write('.report-subheader { font-size: 11px !important; margin: 0 !important; }');
        mywindow.document.write('.table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }');
        mywindow.document.write('.img-circle { width: 80px !important; }');
        mywindow.document.write('#logo_ { width: 80px !important; }');
        mywindow.document.write('div { width: auto !important; max-width: none !important; }');
        mywindow.document.write('</style>');

        // Include JavaScript libraries for proper functionality
        mywindow.document.write('<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script src="<?php echo base_url('assets/js/neon-custom.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 13px; margin: 0; padding: 0; width: 100%;">');
        mywindow.document.write(data);
        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        // Improved print window handling with error handling
        mywindow.onload = function() {
            try {
                mywindow.focus();
                mywindow.print();
                mywindow.close();
            } catch (error) {
                console.error('Print error:', error);
                // Don't close the window if there's an error, let user manually close it
            }
        }
        
    }
</script>
