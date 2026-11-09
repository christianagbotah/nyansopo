<!doctype html>
<html>
    <head>
        <title>Monthly Payment Report by Invoice Item</title>
        <?php include 'includes/includes_top.php';?>
    </head>
    <body> 
        <div class="container" id="top">
            <!-- Page Title Header -->
            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:30px; border-radius:15px; margin-bottom:25px; box-shadow:0 8px 20px rgba(102,126,234,0.3);" class="no-print">
                <h1 style="color:white; margin:0; font-size:32px; font-weight:700; letter-spacing:1px;">
                    <i class="fa fa-chart-bar" style="margin-right:12px;"></i>Monthly Payment Report
                </h1>
                <p style="color:rgba(255,255,255,0.9); margin:8px 0 0 0; font-size:16px;">
                    Track monthly payment collections by invoice item
                </p>
            </div>

            <!-- Tab Navigation -->
            <div style="background:#ffffff; border-radius:12px; padding:8px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);" class="no-print">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" 
                       style="padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; transition:all 0.3s; background:#f3f4f6; color:#374151;">
                        <i class="fa fa-arrow-down" style="margin-right:8px;"></i>Receivables
                    </a>
                    <a href="<?php echo site_url('admin/financial_reports/payables'); ?>" 
                       style="padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; transition:all 0.3s; background:#f3f4f6; color:#374151;">
                        <i class="fa fa-arrow-up" style="margin-right:8px;"></i>Payables
                    </a>
                    <a href="<?php echo site_url('admin/financial_reports/payments'); ?>" 
                       style="padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; transition:all 0.3s; background:#f3f4f6; color:#374151;">
                        <i class="fa fa-money" style="margin-right:8px;"></i>Payments
                    </a>
                    <a href="<?php echo site_url('admin/financial_reports/income-expenditure'); ?>" 
                       style="padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; transition:all 0.3s; background:#f3f4f6; color:#374151;">
                        <i class="fa fa-chart-line" style="margin-right:8px;"></i>Income & Expenditure
                    </a>
                    <a href="<?php echo site_url('admin/financial_reports/monthly-payment-by-item'); ?>" 
                       style="padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; transition:all 0.3s; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; box-shadow:0 4px 12px rgba(102,126,234,0.3);">
                        <i class="fa fa-chart-bar" style="margin-right:8px;"></i>Monthly Payment Report
                    </a>
                </div>
            </div>

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
                #report_table { width:100%; border-collapse:collapse; margin-top:20px; }
                #report_table thead { background:#f3f4f6; }
                #report_table thead th { color:#111827; padding:15px; font-weight:600; text-align:left; }
                #report_table tbody td { padding:12px; border-bottom:1px solid #e5e7eb; color:#374151; }
                #report_table tbody tr:hover { background:#f9fafb; }
                #top_row { border:none; }
                #top_row td { border:none; padding:10px; }
                .report-header { font-weight:700; font-size:20px; letter-spacing:2px; color:#1f2937; }
                .report-subheader { font-size:14px; color:#6b7280; margin-top:5px; }
                .modal { margin-top: 100px !important; }
                .modal-dialog { margin: 100px auto !important; }
                .bootbox.modal { top: 100px !important; }
                
                @media print {
                    @page { margin: 0.3cm; }
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                    * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
                    html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
                    #selectors_div { display:none !important; }
                    .no-print { display:none !important; }
                    #logo_ { width:80px !important; }
                    .filters-card { display:none !important; }
                    .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
                    .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
                    .col-lg-12, .col-md-12, .col-sm-12, [class*="col-"] { padding: 0 !important; width: 100% !important; max-width: none !important; }
                    #print { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
                    page { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; display: block !important; }
                    center { width: 100% !important; max-width: none !important; display: block !important; }
                    table { width: 100% !important; max-width: none !important; border-collapse: collapse !important; }
                    #report_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 9px !important; table-layout: auto !important; }
                    #report_table th, #report_table td { padding: 3px 4px !important; word-wrap: break-word !important; border: 1px solid #ddd !important; font-size: 9px !important; line-height: 1.3 !important; }
                    #report_table thead { background: #f3f4f6 !important; }
                    #report_table thead th { background: #f3f4f6 !important; color: #111827 !important; font-weight: bold !important; }
                    #top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; }
                    #top_row td { padding: 5px !important; }
                    .report-header { font-size: 14px !important; margin: 0 !important; font-weight: bold !important; }
                    .report-subheader { font-size: 9px !important; margin: 0 !important; }
                    .table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }
                    div { width: auto !important; max-width: none !important; }
                }
            </style>

            <!-- Filter Section -->
            <div class="row" id="selectors_div">
                <div class="col-sm-12">
                    <div class="filters-card">
                        <!-- Header Row with Title and Action Buttons -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                            <h3 style="margin:0; font-size:20px; font-weight:700; color:#1f2937;">
                                <i class="fa fa-chart-bar" style="color:#3b82f6;"></i> Monthly Payment Report by Invoice Item
                            </h3>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" onclick="$('#monthly_payment_load').submit();" class="btn-modern btn-success-modern">
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
                        <?php echo form_open(site_url('admin/financial_reports/monthly-payment-by-item/load'), array('id' => 'monthly_payment_load'));?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> Start Month</label>
                                    <select name="start_month" class="modern-input" required>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> End Month</label>
                                    <select name="end_month" class="modern-input" required>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12" selected>December</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <label><i class="fa fa-graduation-cap"></i> Academic Year</label>
                                    <select name="academic_year" class="modern-input" required>
                                        <?php if(isset($academic_years) && !empty($academic_years)): ?>
                                            <?php foreach($academic_years as $year): ?>
                                                <option value="<?php echo $year['year']; ?>"><?php echo $year['year']; ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="">No academic years available</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <br><br>

            <!-- Report Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div id="print">
                        <?php
                        $system_name = $this->db->get_where('settings', array('type'=>'system_name'))->row()->description;
                        $address = $this->db->get_where('settings', array('type'=>'address'))->row()->description;
                        ?>

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
                                                <h3 class="report-header">MONTHLY PAYMENT REPORT BY INVOICE ITEM</h3>
                                                <p class="report-subheader" style="text-align:right;">
                                                    <b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;"><strong>Period:</strong> <span id="reportPeriod">-</span></b>
                                                    <b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;"><strong>Academic Year:</strong> <span id="reportYear">-</span></b>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </center>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-responsive">
                                        <table id="report_table">
                                            <thead>
                                                <tr>
                                                    <th>Invoice Item</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="100" style="text-align:center; color:#6b7280; padding:40px;">
                                                        <i class="fa fa-info-circle" style="font-size:48px; margin-bottom:10px; color:#3b82f6;"></i><br>
                                                        <strong>Please select filters and click Load to view the report</strong>
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

            <?php include 'includes/modal.php'; ?>
        </div>

        <?php include 'includes/includes_bottom.php';?>
    </body>
</html>

<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Update CSRF token for AJAX requests
        $.ajaxSetup({
            data: {
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            }
        });

        // Load data on form submission
        $('#monthly_payment_load').submit(function(ev) {
            ev.preventDefault();

            // Validate start month <= end month
            var startMonth = parseInt($('select[name="start_month"]').val());
            var endMonth = parseInt($('select[name="end_month"]').val());

            if (startMonth > endMonth) {
                showAjaxModal_alert('Start month cannot be after end month', 'error');
                return;
            }

            // Scroll to top
            $('html, body').animate({
                scrollTop: ($('#top').offset().top)
            }, 1000);

            showAjaxModal_alert('Fetching Data. Please wait... <i class="fa fa-spinner fa-pulse"></i>', 'loading');

            $.ajax({
                url: '<?php echo site_url('admin/financial_reports/monthly-payment-by-item/load'); ?>',
                type: 'POST',
                dataType: 'html',
                data: new FormData(this),
                cache: false,
                contentType: false,
                processData: false
            })
            .done(function(data) {
                // The response now contains both thead and tbody rows
                // Split the response to separate header from data rows
                var tempDiv = $('<div>').html(data);
                var rows = tempDiv.find('tr');
                
                if (rows.length > 0) {
                    // First row is the header
                    var headerRow = rows.first();
                    $('#report_table thead').html(headerRow);
                    
                    // Remaining rows are data rows
                    rows.first().remove();
                    $('#report_table tbody').html(rows);
                } else {
                    // Fallback: insert all as tbody
                    $('#report_table tbody').html(data);
                }
                
                // Update report header with selected filters
                updateReportHeader();

                // Show success message
                setTimeout(() => {
                    showAjaxModal_alert('Data Fetched Successfully', 'success');
                }, 2000);

                // Close the modal
                setTimeout(() => {
                    $('.close').click();
                }, 3500);
            })
            .fail(function(err) {
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px;">Error loading data!</div></center>', 'error');
            });
        });
    });

    function updateReportHeader() {
        var startMonth = $('select[name="start_month"] option:selected').text();
        var endMonth = $('select[name="end_month"] option:selected').text();
        var year = $('select[name="academic_year"]').val();

        $('#reportPeriod').html(startMonth + ' to ' + endMonth);
        $('#reportYear').html(year);
    }

    function exportToExcel() {
        const table = document.getElementById('report_table');
        const tbody = table.querySelector('tbody');
        
        // Check if data is loaded (not the initial message or empty state)
        if (!tbody || tbody.querySelector('td[colspan]')) {
            showAjaxModal_alert('No data available to export. Please load data first.', 'warning');
            return;
        }

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Monthly Payment Report');

        const filename = 'Monthly_Payment_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }

    function dashboard() {
        location.href = '<?php echo site_url('admin/dashboard'); ?>';
    }

    function PrintElem(elem) {
        const table = document.getElementById('report_table');
        const tbody = table.querySelector('tbody');
        
        // Check if data is loaded (not the initial message or empty state)
        if (!tbody || tbody.querySelector('td[colspan]')) {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        window.print();
    }

    function Popup(data) {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>Monthly Payment Report by Invoice Item</title>');
        
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-forms.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-core.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/bootstrap.css');?>" />');

        // Add inline print styles to ensure landscape fills the page
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
        mywindow.document.write('#report_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 11px !important; table-layout: auto !important; }');
        mywindow.document.write('#report_table th, #report_table td { padding: 5px 6px !important; word-wrap: break-word !important; border: 1px solid #ddd !important; font-size: 11px !important; }');
        mywindow.document.write('#report_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; }');
        mywindow.document.write('#top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; }');
        mywindow.document.write('#top_row td { padding: 5px !important; }');
        mywindow.document.write('.report-header { font-size: 16px !important; margin: 0 !important; font-weight: bold !important; }');
        mywindow.document.write('.report-subheader { font-size: 11px !important; margin: 0 !important; }');
        mywindow.document.write('.table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }');
        mywindow.document.write('.img-circle { width: 80px !important; }');
        mywindow.document.write('#logo_ { width: 80px !important; }');
        mywindow.document.write('div { width: auto !important; max-width: none !important; }');
        mywindow.document.write('</style>');

        mywindow.document.write('<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>" type="text/javascript"><\/script>');
        mywindow.document.write('<script src="<?php echo base_url('assets/js/neon-custom.js');?>" type="text/javascript"><\/script>');
        mywindow.document.write('<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('</head><body style="font-size: 13px; margin: 0; padding: 0; width: 100%;">');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload = function() {
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
    }
</script>
