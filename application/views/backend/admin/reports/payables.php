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
            
            <!-- Page Title -->
            <div class="page-title-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                    <i class="fa fa-file-text" style="margin-right: 12px;"></i>Accounts Payables Report
                </h1>
                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500;">
                    View and manage outstanding payables and vendor balances
                </p>
            </div>

            <!-- Report Navigation Tabs -->
            <div class="report-tabs" style="background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-file-text"></i> Receivables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payables'); ?>" class="tab-link active" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                    <i class="fa fa-file-text"></i> Payables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payments'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-money"></i> Payments
                </a>
                <a href="<?php echo site_url('admin/financial_reports/income-expenditure'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-bar-chart"></i> Income & Expenditure
                </a>
                <a href="<?php echo site_url('admin/financial_reports/monthly-payment-by-item'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-chart-bar"></i> Monthly Payment Report
                </a>
            </div>
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
                    .page-title-header { display:none !important; }
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
                    #invoice_table th { background: #f3f4f6 !important; color: #111827 !important; font-weight: bold !important; }
                    /* Period display fix */
                    div { width: auto !important; max-width: none !important; display: block !important; font-size: inherit !important; color: inherit !important; }
                    #top_row { width: 100% !important; max-width: none !important; margin-bottom: 10px !important; color: #000 !important; font-size: 9px !important; }
                    #top_row div { display: block !important; visibility: visible !important; font-size: 11px !important; color: #000 !important; }
                    #top_row td { padding: 5px !important; }
                    #top_row h3 { display: block !important; visibility: visible !important; font-size: 11px !important; font-weight: bold !important; }
                    #top_row h4 { display: block !important; visibility: visible !important; font-size: 9px !important; }
                    #top_row img { display: inline-block !important; visibility: visible !important; }
                    #top_row pre { display: block !important; visibility: visible !important; white-space: pre-wrap !important; margin: 0 !important; }
                    #periodDisplay { display: block !important; visibility: visible !important; font-size: 11px !important; color: #000 !important; }
                    #periodDisplay b { font-size: 11px !important; color: #000 !important; display: block !important; }
                    .report-header { font-size: 16px !important; margin: 0 !important; font-weight: bold !important; }
                    .report-subheader { font-size: 9px !important; margin: 0 !important; }
                    .table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }
                    h3, h4 { font-size: 11px !important; margin: 4px 0 !important; display: block !important; visibility: visible !important; }
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

            <!-- Filter Section -->
            <div class="row" id="selectors_div">
                <div class="col-sm-12">
                    <div class="filters-card">
                        <!-- Header Row with Title and Action Buttons -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                            <h3 style="margin:0; font-size:20px; font-weight:700; color:#1f2937;">
                                <i class="fa fa-file-text" style="color:#3b82f6;"></i> Accounts Payables Report
                            </h3>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" onclick="$('#payables_load').submit();" class="btn-modern btn-success-modern">
                                    <i class="fa fa-sync"></i> Load
                                </button>
                                <button type="button" onclick="exportToExcel()" class="btn-modern btn-excel-modern">
                                    <i class="fa fa-file-excel-o"></i> Excel
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
                        <?php echo form_open(site_url('admin/financial_reports/payables/load') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'payables_load'));?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> Start Date</label>
                                    <input type="text" name="start_date" id="start_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> End Date</label>
                                    <input type="text" name="end_date" id="end_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                                    <div id="desc"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-list"></i> Type</label>
                                    <select class="modern-input" name="search_by_type" id="search_by_type">
                                        <option value="1">Billed Invoice</option>
                                        <option value="2">Daily Fees (<?php echo implode(', ', $enabled_fees); ?>)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-filter"></i> Category of Selection</label>
                                    <select class="modern-input" name="search_by_category" id="search_by_category">
                                        <option value="0" selected>All Students</option>
                                        <option value="1">Filter By Class</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row toggle_row" style="display: none; margin-top:15px;">
                            <div class="col-md-6">
                                <div class="filter-group">
                                    <label><i class="fa fa-building"></i> Filter By Class</label>
                                    <select class="modern-input" name="search_by_class" id="search_by_class">
                                        <option value="0" selected>Select a class</option>
                                        <?php getFullClassList(); ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="toggle_selected_class" style="display: none;">
                                <div class="filter-group">
                                    <label><i class="fa fa-user"></i> Students In Selected Class</label>
                                    <select class="modern-input boxit" name="search_by_student" id="search_by_student">
                                        <option value="0">Please select class first</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <br><br>
            
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

                       

                        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
                        <!--Chartjs-->
                        <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>
                        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>

                        <style type="text/css">

                            #selectors_div {
                                padding-top: 10px;
                                position: sticky;
                                top: 0px;
                                z-index: 1;
                                background-color: #b2b3b6;

                                box-shadow: 1px 8px 10px 0px #888888;
                            }
                            
                             #mark_print th, #grade_table th, #conducts th {
                                /*background-color: grey;*/
                                padding: 5px;
                                color: #000000;
                                border-bottom: 2px solid #e2dddd;
                             }

                             /*#invoice_table thead tr th {
                                color: #fff !important;
                             }*/

                              #invoice_table thead {
                                padding: 10px 0px !important;
                              }

                             pre {
                                    display: block;
                                    font-family: monospace;
                                    white-space: pre-wrap;
                                    margin: -40px 0px;
                                    line-height: 20px;
                                }

                             body {
                                font-size: 14px !important;
                             }

                             .account_tb th {
                                background-color: grey;
                                border: 1px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 1px solid red;
                                text-align: left;
                             }

                            td {
                                padding: 5px;
                            }

                            #grade_table tbody tr td{
                                line-height: 25px;
                            }

                            #term{
                                background-color: black; 
                                padding: 5px 15px 5px 15px; 
                                border-radius: 9px;
                                font-size: 24px;
                                letter-spacing: 5px;
                                color: #ffffff;

                            }

                            div .logo{
                                position: absolute;
                                margin-top: 20px;
                                left: 10px;
                            }

                            div .photo_passport {
                                position: absolute;
                                margin-top: -100px;
                                right: 10px;
                                border-radius: 16% 6%;
                            }

                            page[size="A4"] {
                                width: auto;
                                height: auto;
                                    page-break-after: always;
                                }

                                #print_div a {
                                background-color: black;
                                color: #fff;
                                padding: 5px;
                                border-radius: 14px;
                                font-size: 15px;
                                font-weight: bold;
                            }

                            #print_div a:hover {
                                background-color: green;
                                color: #fff;
                            }

                            .footer {
                                    position: fixed;
                                    bottom: 0px;
                                }
                            
                            #promote{
                                position: absolute;
                                    margin-top: -32px;
                                }

                            .footer {
                                    position: relative;
                                    bottom: 0px;
                                }
                            #status {
                                position: relative;
                                margin-top: -120px;
                                transform: rotate(-45deg);
                            }

                            pre {

                                background-color: #ffffff !important;
                                border: 0px !important;
                                border-radius: 0px !important;
                                }

                            .table-responsive {
                                overflow-x: hidden !important;
                            }


                            @media Print{

                                .label-danger {
                                    background-color: #d9534f !important;
                                }
                                .label {
                                    display: inline !important;
                                    padding: 0.2em 0.6em 0.3em !important;
                                    font-size: 75% !important;
                                    font-weight: bold !important;
                                    line-height: 1 !important;
                                    color: #fff !important;
                                    text-align: center !important;
                                    white-space: nowrap !important;
                                    vertical-align: baseline !important;
                                    border-radius: 0.25em !important;
                                }

                                .badge {
                                    display: inline-block !important;
                                    min-width: 10px !important;
                                    padding: 3px 7px !important;
                                    font-size: 12px !important;
                                    font-weight: bold !important;
                                    line-height: 1 !important;
                                    color: #fff !important;
                                    text-align: center !important;
                                    white-space: nowrap !important;
                                    vertical-align: middle !important;
                                    background-color: #777 !important;
                                    border-radius: 10px !important;
                                }


                              .col-md-1,.col-md-2,.col-md-3,.col-md-4,
                              .col-md-5,.col-md-6,.col-md-7,.col-md-8, 
                              .col-md-9,.col-md-10,.col-md-11,.col-md-12 {
                                float: left;
                              }

                              .col-md-1 {
                                width: 8%;
                              }
                              .col-md-2 {
                                width: 16%;
                              }
                              .col-md-3 {
                                width: 25%;
                              }
                              .col-md-4 {
                                width: 33%;
                              }
                              .col-md-5 {
                                width: 42%;
                              }
                              .col-md-6 {
                                width: 50%;
                              }
                              .col-md-7 {
                                width: 58%;
                              }
                              .col-md-8 {
                                width: 66%;
                              }
                              .col-md-9 {
                                width: 75%;
                              }
                              .col-md-10 {
                                width: 83%;
                              }
                              .col-md-11 {
                                width: 92%;
                              }
                              .col-md-12 {
                                width: 100%;
                              }

                                #logo_ {
                                    padding-top: 30px;
                                    width: 300px !important;
                                }
                                #selectors_div {
                                    display:  none !important;
                                }

                                pre {

                                background-color: #ffffff !important;
                                border: 0px !important;
                                border-radius: 0px !important;
                                }


                                .footer {
                                    position: fixed;
                                    bottom: 0px;
                                }
                                page {
                                background: #ffffff;
                                display: block;
                                margin: 0 auto;
                                margin-bottom: 0.5cm;
                                box-shadow: 0;

                                }

                                 #invoice_table, #hr_line, #top_row, .footer {
                                    width: 130% !important;
                                }

                                pre {
                                    display: block;
                                    font-family: monospace;
                                    white-space: pre-wrap;
                                    margin: -40px 0px;
                                    line-height: 20px;
                                }

                                page[size="A4"] {
                                    width: auto;
                                    height: auto;
                                    page-break-after: always;
                                }

                                body, page {
                                    margin: 0;
                                    box-shadow: 0;
                                }
                                
                                #print_div {
                                    display: none;
                                }


                                div .logo{
                                    position: absolute;
                                    margin-top: 20px;
                                    left: 10px;
                                }

                                div .photo_passport{
                                    position: absolute;
                                    margin-top: -75px !important;
                                    right: 10px;

                                }

                               
                                #student_details p{
                                    line-height: 30px;
                                }

                                #promote{
                                    margin-top: -32px;
                                }

                                #box{
                                    margin-top: -10px;
                                }

                                #gh{
                                    margin-top: -15px;
                                }

                                .sign {
                                    position: fixed;
                                    bottom: 5px;
                                }
                            }
                        </style>
                    <page size="A4">
                        <center> 

                            <table class="table" id="top_row" style="width:100%; border-collapse:collapse;">
                                <thead>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td width="60%">
                                           <table class="table" style="margin-top: -10px; border-collapse:collapse;">
                                                <thead>
                                                    <tr>
                                                    <td>
                                                       <img class="" id="logo_" src="<?php echo base_url(); ?>uploads/school_logo.png" width="60px"> 
                                                    </td>
                                                    <td>
                                                        <h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px; margin: 0 0 5px 0 !important;"><?php echo $system_name;?></h3>
                                                        <pre style="margin: 0 !important; padding: 0 !important; white-space: pre-wrap !important; font-family: Nunito !important; font-size: 14px !important; line-height: 1.3 !important;"><?php echo trim($address);?></pre>
                                                    </td> 
                                                    </tr>
                                                    <tr style="border-top: 2px solid #1b1b69;">
                                                        <td colspan="2"></td>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </td>

                    
                                        <td width="40%">
                                            <table class="table" style="width: 100%; margin-top: -15px; border-collapse:collapse;">
                                                <thead>
                                                    <tr>
                                                        <td align="right" colspan="2"><h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px;">ACCOUNTS PAYABLES REPORT</h3></td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right" colspan="2" style="display:table-cell !important; visibility:visible !important; padding:5px !important; overflow:visible !important; text-align: right !important;">
                                                            <div id="periodDisplay" style="display: block !important; visibility: visible !important; text-align: right !important; font-size: 11px !important; font-weight: bold !important; padding: 5px !important; overflow: visible !important; width: 100% !important;">
                                                                <b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;">Period: From: <?=date('M, d Y', $date_start); ?> To: <?=date('M, d Y', $date_end); ?></b>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr style="border-top: 2px solid #1b1b69;">
                                                        <td colspan="2"></td>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </center>



                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="table table-responsive">
                                    <center><h4><strong>
                                        <u id="captionHeader" style="letter-spacing: 3px">PAYABLES GENERATED FOR ALL STUDENTS</u><br><br>
                                        <u id="captionHeaderFct" style="letter-spacing: 3px"></u>
                                    </strong></h4></center>
                                    <table class="table" id="invoice_table" style="width:100%; border-collapse:collapse;">
                                        <thead style="padding: 30px;">
                                            <tr style="height: 30px;">
                                                <th colspan="2" style="text-align: left"><strong>STUDENTS (CREDITORS)</strong></th>
                                                <th style="text-align: left"><strong>INVOICES</strong></th>
                                                <!-- <th style="text-align: right"><strong>SUB-TOTAL</strong></th> -->
                                                <th style="text-align: right"><strong>TOTAL (<?=$currency; ?>)</strong></th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                           
                                            
                                            
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="container-fluid">
                                    <center>
                                    <footer class="footer" style="position: fixed; bottom: -20px">

                                        <div style="width: 100%; height: 30px; margin-bottom: 20px; padding: 5px; text-align: left"><smal style="color:#1d1c1c; text-shadow: 1px 1px 0px #504949; position: relative; top: 5px; letter-spacing: 0.3px"><i>Software Developed & Designed by: Lightworld Technologies Ltd. Contact us on 0243618186 | 0247612799 - Accra</i></smal></smal>
                                    </div>
            
                                </footer>
                                </center>
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

         $('select').select2();
      /*Toggle class selection when category changes*/
      $('#search_by_category').change(function(ev) {

        $('#invoice_table tbody').html('');
        let classId = $('#search_by_class').val();
        /*show or hide the toggle row*/
        if($(this).val() == 0) {
            $('#captionHeader').html('PAYABLES GENERATED FOR ALL STUDENTS');
            /*all students selected so slideup*/
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

      /*if feeding, classes or transport is selected, we don't need date*/
      $('#search_by_type').change(function(ev) {

        $('#invoice_table tbody').html('');
        /*show or hide the toggle row*/
        if($(this).val() == 2) {
            /*all students selected so slideup*/
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

      

      /*Toggle Stucents selection when Class changes*/
      $('#search_by_class').change(function(ev) {

        let student = $('#search_by_student').val();
        /*show or hide the toggle row*/
        if($(this).val() == 0) {
            /*No class is selected so slideup*/
            $('#toggle_selected_class').slideUp('slow');
            $('#captionHeader').html('');
        } else {
            $.ajax({
                url: '<?php echo site_url('admin/getAllStudents/') ?>' + $(this).val(),
                type: 'post',
                dataType: 'html'
            })
            .done(function(data) {
                $('#search_by_student').html('<option value="0">All Students</option>' + data);
                $('#toggle_selected_class').slideDown('slow');

                //update the heading
                let student_id = $('#search_by_student').val();
                let class_id = $('#search_by_class').val();
                $.ajax({
                    url: '<?php echo site_url('admin/getReportCaption/') ?>' + class_id + '/' + student_id,
                    type: 'post',
                    dataType: 'text',
                })
                .done(function(headingData) {
                    //CLASS SELECTED
                    $('#captionHeader').html('PAYABLES GENERATED FOR ' + headingData + ' STUDENTS');
                    
                })
                .fail(function(error) {
                    showAjaxModal_alert('Some data were not updated properly!<br>Please refresh the page and try again.', 'Error');
                })
            })
            .fail(function(err) {

                $(this).html('Unable to load data!');
            })
            
        }
        
      });


      //student changed
      $('#search_by_student').change(function(ev) {
            //update the heading
            let student_id = $('#search_by_student').val();
            let class_id = $('#search_by_class').val();
            $.ajax({
                url: '<?php echo site_url('admin/getReportCaption/') ?>' + class_id + '/' + student_id,
                type: 'post',
                dataType: 'text',
            })
            .done(function(headingData) {
                if(student_id == 0) {
                    $('#captionHeader').html('PAYABLES GENERATED FOR ' + headingData + ' STUDENTS');
                } else {
                    //STUDENT SELECTED
                    $('#captionHeader').html('PAYABLES GENERATED FOR ' + headingData);
                }
                
                
            })
            .fail(function(error) {
                showAjaxModal_alert('Some data were not updated properly!<br>Please refresh the page and try again.', 'Error');
            });

            //submit the form
            $('#payables_load').submit();
      })
      
      //get selected date and update the period
      $('#start_date').change(function(ev) {
        $.ajax({
            url: '<?php echo site_url('admin/getDate/'); ?>' + $(this).val(),
            dataType: 'html',
            type: 'post',
        })
        .done(function(data) {
            // Update both dates together
            const endDateVal = $('#end_date').val();
            $.ajax({
                url: '<?php echo site_url('admin/getDate/'); ?>' + endDateVal,
                dataType: 'html',
                type: 'post',
            })
            .done(function(endDate) {
                $('#periodDisplay').html('<b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important;">Period: From: ' + data + ' To: ' + endDate + '</b>');
            });
        })
        .fail(function(err) {
            showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
        })
      });

      $('#end_date').change(function(ev) {
        $.ajax({
            url: '<?php echo site_url('admin/getDate/'); ?>' + $(this).val(),
            dataType: 'html',
            type: 'post',
        })
        .done(function(data) {
            // Update both dates together
            const startDateVal = $('#start_date').val();
            $.ajax({
                url: '<?php echo site_url('admin/getDate/'); ?>' + startDateVal,
                dataType: 'html',
                type: 'post',
            })
            .done(function(startDate) {
                $('#periodDisplay').html('<b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important;">Period: From: ' + startDate + ' To: ' + data + '</b>');
            });
        })
        .fail(function(err) {
            showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
        })
      });

      //Load data here
      $('#payables_load').submit(function(ev) {
        ev.preventDefault();

         //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

        let category = $('#search_by_category').val();
        let class_id = $('#search_by_class').val();


        if(category == 1 && class_id == 0) {
            showAjaxModal_alert('Action Terminated!<br>Please select a class.', 'Error');
            return false;
        }
        showAjaxModal_alert('Fetching Data. Please wait... <i class="fa fa-spinner fa-pulse"></i>', 'Loading');


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

                /*Display data-at the background*/
                $('#invoice_table tbody').html(data);


                /*Show done message*/
                setTimeout(() => {
                    showAjaxModal_alert('Data Fetched Successfully', 'Success');

                }, 2000);

                /*Close the modal*/
                setTimeout(() => {
                    $('.close').click();//close modal
                }, 3500);
                
            })
            .fail(function(err) {
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Error loading data!<br>' + err.responseText + '</div></center>', 'Error');
            });
      });

    });
    
    function dashboard() {
        location.href = '<?=site_url('admin/dashboard'); ?>';
    }

    function exportToExcel() {
        const table = document.getElementById('invoice_table');
        const tbody = table.querySelector('tbody');
        
        // Check if data is loaded
        if (!tbody || tbody.children.length === 0 || tbody.textContent.trim() === '') {
            showAjaxModal_alert('No data available to export. Please load data first.', 'warning');
            return;
        }

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Payables Report');

        const filename = 'Accounts_Payables_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }

    // Print Function - Use native browser print
    function PrintElem(elem) {
        const table = document.getElementById('invoice_table');
        const tbody = table.querySelector('tbody');
        
        // Check if data is loaded
        if (!tbody || tbody.children.length === 0 || tbody.textContent.trim() === '') {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        // Use native browser print
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

    function Popup(data) {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>Accounts Payables Report</title>');
        
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
