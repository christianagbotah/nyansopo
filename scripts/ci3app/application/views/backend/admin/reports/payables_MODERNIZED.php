<!doctype html>
<html>
    <head>
        <title>ACCOUNTS PAYABLES</title>
        <?php include 'includes/includes_top.php';?>
    </head>
    <body> 
        <?php
        // Fetch enabled fees for use throughout the page
        $rates = $this->db->get('daily_fee_rates')->row();
        $enabled_fees = [];
        $enabled_labels = [];
        if($rates) {
            if($rates->feeding_enabled == 1) {
                $enabled_fees[] = 'Feeding';
                $enabled_labels[] = 'FEEDING';
            }
            if($rates->breakfast_enabled == 1) {
                $enabled_fees[] = 'Breakfast';
                $enabled_labels[] = 'BREAKFAST';
            }
            if($rates->classes_enabled == 1) {
                $enabled_fees[] = 'Classes';
                $enabled_labels[] = 'CLASSES';
            }
            if($rates->water_enabled == 1) {
                $enabled_fees[] = 'Water';
                $enabled_labels[] = 'WATER';
            }
            $enabled_fees[] = 'Transport';
            $enabled_labels[] = 'TRANSPORT';
        }
        ?>

        <div class="container" id="top">
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
                .filter-grid { display:grid; gap:12px; grid-template-columns:1fr; }
                @media (min-width: 640px) { .filter-grid { grid-template-columns:repeat(2,1fr); } }
                @media (min-width: 1024px) { .filter-grid { grid-template-columns:repeat(3,1fr); } }
            </style>

            <!-- Filter Section -->
            <div class="row" id="selectors_div">
                <div class="col-sm-12">
                    <div class="filters-card">
                        <!-- Header Row with Title and Action Buttons -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                            <h3 style="margin:0; font-size:20px; font-weight:700; color:#1f2937;">
                                <i class="fa fa-file-invoice-dollar" style="color:#3b82f6;"></i> Accounts Payables Report
                            </h3>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" onclick="$('#payables_load').submit();" class="btn-modern btn-success-modern">
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
                        <?php echo form_open(site_url('admin/financial_reports/payables/load'), array('id' => 'payables_load'));?>
                        <div class="filter-grid">
                            <div class="filter-group date">
                                <label><i class="fa fa-calendar"></i> Start Date</label>
                                <input type="text" name="start_date" id="start_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                            </div>

                            <div class="filter-group date">
                                <label><i class="fa fa-calendar"></i> End Date</label>
                                <input type="text" name="end_date" id="end_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                                <div id="desc"></div>
                            </div>

                            <div class="filter-group">
                                <label><i class="fa fa-tag"></i> Type</label>
                                <select class="modern-input" name="search_by_type" id="search_by_type">
                                    <option value="1">Billed Invoice</option>
                                    <option value="2">Daily Fees (<?php echo implode(', ', $enabled_fees); ?>)</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label><i class="fa fa-filter"></i> Category of Selection</label>
                                <select class="modern-input" name="search_by_category" id="search_by_category">
                                    <option value="0" selected>All Students</option>
                                    <option value="1">Filter By Class</option>
                                </select>
                            </div>

                            <div class="filter-group toggle_row" style="display: none;">
                                <label><i class="fa fa-school"></i> Filter By Class</label>
                                <select class="modern-input" name="search_by_class" id="search_by_class">
                                    <option value="0" selected>Select a class</option>
                                    <?php getFullClassList(); ?>
                                </select>
                            </div>

                            <div class="filter-group toggle_row" id="toggle_selected_class" style="display: none;">
                                <label><i class="fa fa-user-graduate"></i> Students In Selected Class</label>
                                <select class="modern-input" name="search_by_student" id="search_by_student">
                                    <option value="0">Please select class first</option>
                                </select>
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
                        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
                        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
                        $system_name = $this->db->get_where('settings', array('type'=>'system_name'))->row()->description;
                        $address = $this->db->get_where('settings', array('type'=>'address'))->row()->description;
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
                                @page { size: landscape; margin: 0.3cm; }
                                * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
                                html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
                                #selectors_div { display:none !important; }
                                #logo_ { width:80px !important; }
                                .filters-card { display:none !important; }
                                .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
                                .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
                                .col-lg-12, .col-md-12, .col-sm-12, [class*="col-"] { padding: 0 !important; width: 100% !important; max-width: none !important; }
                                #print { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
                                page { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; display: block !important; }
                                center { width: 100% !important; max-width: none !important; display: block !important; }
                                table { width: 100% !important; max-width: none !important; border-collapse: collapse !important; }
                                #invoice_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 11px !important; table-layout: auto !important; }
                                #invoice_table th, #invoice_table td { padding: 5px 6px !important; word-wrap: break-word !important; border: 1px solid #ddd !important; font-size: 11px !important; }
                                #invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; font-size: 16px !important; }
                                #top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; }
                                #top_row td { padding: 5px !important; }
                                .report-header { font-size: 16px !important; margin: 0 !important; font-weight: bold !important; }
                                .report-subheader { font-size: 11px !important; margin: 0 !important; }
                                .table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }
                                div { width: auto !important; max-width: none !important; }
                            }
                        </style>

                        <page size="A4">
                            <center>
                                <table id="top_row">
                                    <tbody>
                                        <tr>
                                            <td width="30%" style="vertical-align:top;">
                                                <img id="logo_" src="<?php echo base_url(); ?>uploads/school_logo.png" width="100px">
                                            </td>
                                            <td width="70%" style="text-align:right; vertical-align:top;">
                                                <h3 class="report-header">ACCOUNTS PAYABLES REPORT</h3>
                                                <p class="report-subheader">
                                                    <strong>Period:</strong>
                                                    <span id="periodArea" style="display: none">AS AT TODAY</span>
                                                    <span id="dateInterval">
                                                        From: <span id="fromDate"><?=date('M, d Y', $date_start); ?></span> 
                                                        To: <span id="toDate"><?=date('M, d Y', $date_end); ?></span>
                                                    </span>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" colspan="2" style="text-align:center; vertical-align:top;">
                                                <h3 class="report-header"><?php echo $system_name; ?></h3>
                                                <p class="report-subheader"><?php echo trim($address); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </center>

                            <div class="row">
                                <div class="col-lg-12">
                                    <center>
                                        <h4><strong>
                                            <u id="captionHeader" style="letter-spacing: 3px">PAYABLES GENERATED FOR ALL STUDENTS</u><br><br>
                                            <u id="captionHeaderFct" style="letter-spacing: 3px"></u>
                                        </strong></h4>
                                    </center>
                                    <div class="table-responsive">
                                        <table id="invoice_table">
                                            <thead>
                                                <tr>
                                                    <th colspan="2" style="text-align: left">STUDENTS (CREDITORS)</th>
                                                    <th style="text-align: left">INVOICES</th>
                                                    <th style="text-align: right">TOTAL (<?=$currency; ?>)</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
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

        $('select').select2();

        // Toggle class selection when category changes
        $('#search_by_category').change(function(ev) {
            $('#invoice_table tbody').html('');
            let classId = $('#search_by_class').val();
            
            if($(this).val() == 0) {
                $('#captionHeader').html('PAYABLES GENERATED FOR ALL STUDENTS');
                $('.toggle_row').slideUp('slow');
            } else {
                $('.toggle_row').slideDown('slow');
                if(classId == 0) {
                    $('#toggle_selected_class').slideUp('slow');
                } else {
                    $('#toggle_selected_class').slideDown('slow');
                }
                $('#captionHeader').html('');
            }
        });

        // If feeding, classes or transport is selected, we don't need date
        $('#search_by_type').change(function(ev) {
            $('#invoice_table tbody').html('');
            
            if($(this).val() == 2) {
                $('.date').slideUp('slow');
                $('#dateInterval').slideUp('slow');
                $('#captionHeaderFct').text('<?php echo implode(', ', $enabled_labels) . ' FEES'; ?>');
                setTimeout(() => {
                    $('#desc').html('This shows owing to current date');
                    $('#periodArea').slideDown('slow');
                }, 1000);
            } else {
                $('#periodArea').slideUp('slow');
                $('#captionHeaderFct').text('');
                $('#desc').html('');
                $('.date').slideDown('slow');
                $('#dateInterval').slideDown('slow');
            }
        });

        // Toggle Students selection when Class changes
        $('#search_by_class').change(function(ev) {
            let student = $('#search_by_student').val();
            
            if($(this).val() == 0) {
                $('#toggle_selected_class').slideUp('slow');
                $('#captionHeader').html('');
            } else {
                $.ajax({
                    url: '<?php echo site_url('admin/getAllStudentsInClass/'); ?>' + $(this).val(),
                    dataType: 'html',
                    type: 'post',
                })
                .done(function(data) {
                    $('#search_by_student').html(data);
                    $('#toggle_selected_class').slideDown('slow');
                    $('#captionHeader').html('PAYABLES GENERATED FOR SELECTED CLASS');
                })
                .fail(function(err) {
                    showAjaxModal_alert('Failed to load students!<br>Please try again.', 'error');
                });
            }
        });

        // Update caption when student is selected
        $('#search_by_student').change(function(ev) {
            if($(this).val() == 0) {
                $('#captionHeader').html('PAYABLES GENERATED FOR SELECTED CLASS');
            } else {
                $('#captionHeader').html('PAYABLES GENERATED FOR SELECTED STUDENT');
            }
        });

        // Get selected date and update the period
        $('#start_date').change(function(ev) {
            $.ajax({
                url: '<?php echo site_url('admin/getDate/'); ?>' + $(this).val(),
                dataType: 'html',
                type: 'post',
            })
            .done(function(data) {
                $('#fromDate').html(data);
            })
            .fail(function(err) {
                showAjaxModal_alert('Date selection failed!<br>Please try again.', 'error');
            });
        });

        $('#end_date').change(function(ev) {
            $.ajax({
                url: '<?php echo site_url('admin/getDate/'); ?>' + $(this).val(),
                dataType: 'html',
                type: 'post',
            })
            .done(function(data) {
                $('#toDate').html(data);
            })
            .fail(function(err) {
                showAjaxModal_alert('Date selection failed!<br>Please try again.', 'error');
            });
        });

        // Load data here
        $('#payables_load').submit(function(ev) {
            ev.preventDefault();

            // Scroll to the top
            $('html, body').animate({
                scrollTop: ($('#top').offset().top)
            }, 1000);

            showAjaxModal_alert('Fetching Data. Please wait... <i class="fa fa-spinner fa-pulse"></i>', 'loading');

            $.ajax({
                url: '<?php echo site_url('admin/financial_reports/payables/load'); ?>',
                type: 'POST',
                dataType: 'html',
                data: new FormData(this),
                cache: false,
                contentType: false,
                processData: false
            })
            .done(function(data) {
                $('#invoice_table tbody').html(data);

                setTimeout(() => {
                    showAjaxModal_alert('Data Fetched Successfully', 'success');
                }, 2000);

                setTimeout(() => {
                    $('.close').click();
                }, 3500);
            })
            .fail(function(err) {
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px;">Error loading data!</div></center>', 'error');
            });
        });
    });

    function exportToExcel() {
        const table = document.getElementById('invoice_table');
        if(!table.querySelector('tbody tr')) {
            showAjaxModal_alert('Please load data first', 'warning');
            return;
        }
        
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Payables');
        
        const filename = 'Payables_' + new Date().toISOString().slice(0,10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }

    function dashboard() {
        location.href = '<?php echo site_url('admin/dashboard'); ?>';
    }

    function PrintElem(elem) {
        const table = document.getElementById('invoice_table');
        const tbody = table.querySelector('tbody');
        
        if (!tbody || !tbody.querySelector('tr') || tbody.querySelector('td[colspan]')) {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        Popup($(elem).html());
    }

    function Popup(data) {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>ACCOUNTS PAYABLES</title>');
        
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
        mywindow.document.write('#invoice_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 11px !important; table-layout: auto !important; }');
        mywindow.document.write('#invoice_table th, #invoice_table td { padding: 5px 6px !important; word-wrap: break-word !important; border: 1px solid #ddd !important; font-size: 11px !important; }');
        mywindow.document.write('#invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; }');
        mywindow.document.write('#top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; }');
        mywindow.document.write('#top_row td { padding: 5px !important; }');
        mywindow.document.write('.report-header { font-size: 16px !important; margin: 0 !important; font-weight: bold !important; }');
        mywindow.document.write('.report-subheader { font-size: 11px !important; margin: 0 !important; }');
        mywindow.document.write('.table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }');
        mywindow.document.write('.img-circle { width: 80px !important; }');
        mywindow.document.write('#logo_ { width: 80px !important; }');
        mywindow.document.write('div { width: auto !important; max-width: none !important; }');
        mywindow.document.write('</style>');

        mywindow.document.write('<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script src="<?php echo base_url('assets/js/neon-custom.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 13px; margin: 0; padding: 0; width: 100%;">');
        mywindow.document.write(data);
        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload = function() {
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
    }
</script>
