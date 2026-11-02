<!doctype html>
<html>
    <head>
        <title>ACCOUNTS RECEIVABLES REPORT</title>
        <?php include 'includes/includes_top.php';?>

        <style type="text/css">
            .mb-2 {
                margin-bottom: 5px;
            }
        </style>

    </head>
    <body> 
        <?php
            $boarding_system = $this->db->get_where('settings' , array('type'=>'boarding_system'))->row()->description;
        ?>

        <div class="<?=$boarding_system == 'yes' ? 'container-fluid' : 'container';?>" id="top"  <?=$boarding_system == 'yes' ? 'style="margin: 15px"' : '';?>>
            
            <!-- Page Title -->
            <div class="page-title-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                    <i class="fa fa-file-text" style="margin-right: 12px;"></i>Accounts Receivables Report
                </h1>
                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500;">
                    View and manage outstanding student balances and receivables
                </p>
            </div>

            <!-- Report Navigation Tabs -->
            <div class="report-tabs" style="background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="tab-link active" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                    <i class="fa fa-file-text"></i> Receivables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payables'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
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
                .btn-sms-modern { background:linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color:white; box-shadow:0 4px 12px rgba(99,102,241,0.3); }
                .btn-sms-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(99,102,241,0.4); }
                .sms-wiz-modal .modal-dialog { width:640px; }
                #smsWizardHolder { min-height:220px; }
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
                    html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; font-size: 8px !important; color: #000 !important; }
                    #selectors_div { display:none !important; }
                    #logo_ { width:60px !important; }
                    .filters-card { display:none !important; }
                    .page-title-header { display:none !important; }
                    .report-tabs { display:none !important; }
                    /* Hide footer */
                    .footer { display: none !important; }
                    footer { display: none !important; }
                    .container-fluid:has(.footer) { display: none !important; }
                    .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
                    .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
                    .col-lg-12, .col-md-12, .col-sm-12, [class*="col-"] { padding: 0 !important; width: 100% !important; max-width: none !important; }
                    #print { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
                    page { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; display: block !important; }
                    center { width: 100% !important; max-width: none !important; display: block !important; }
                    table { width: 100% !important; max-width: none !important; border-collapse: collapse !important; }
                    /* Bank Statement Style - 8px fonts */
                    #invoice_table { width: 100% !important; max-width: none !important; min-width: 100% !important; font-size: 8px !important; table-layout: fixed !important; }
                    #invoice_table th, #invoice_table td { padding: 3px 4px !important; word-wrap: break-word !important; border: 1px solid #ccc !important; font-size: 8px !important; line-height: 1.2 !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
                    /* Column widths - minimize Student Code and Name */
                    #invoice_table th:nth-child(1), #invoice_table td:nth-child(1) { width: 8% !important; white-space: nowrap !important; } /* Student Code */
                    #invoice_table th:nth-child(2), #invoice_table td:nth-child(2) { width: 25% !important; white-space: normal !important; word-wrap: break-word !important; } /* Student Name */
                    #invoice_table th:nth-child(3), #invoice_table td:nth-child(3) { width: 47% !important; white-space: normal !important; word-wrap: break-word !important; } /* Invoices */
                    #invoice_table th:nth-child(4), #invoice_table td:nth-child(4) { width: 20% !important; text-align: right !important; white-space: nowrap !important; } /* Total */
                    #invoice_table thead { background: #f3f4f6 !important; }
                    #invoice_table thead th { background: #f3f4f6 !important; color: #111827 !important; }
                    #invoice_table th { background: #f3f4f6 !important; color: #111827 !important; font-weight: bold !important; }
                    /* Hide underline boxes */
                    u { text-decoration: none !important; border: none !important; }
                    #captionHeader, #captionHeaderFct { display: none !important; }
                    /* Period display fix */
                    div { width: auto !important; max-width: none !important; display: block !important; font-size: inherit !important; color: inherit !important; }
                    #top_row { width: 100% !important; max-width: none !important; margin-bottom: 8px !important; color: #000 !important; font-size: 8px !important; }
                    #top_row div { display: block !important; visibility: visible !important; font-size: 10px !important; color: #000 !important; }
                    #top_row td { padding: 3px !important; }
                    #top_row h3 { display: block !important; visibility: visible !important; font-size: 10px !important; font-weight: bold !important; }
                    #top_row h4 { display: block !important; visibility: visible !important; font-size: 8px !important; }
                    #top_row img { display: inline-block !important; visibility: visible !important; }
                    #top_row pre { display: block !important; visibility: visible !important; white-space: pre-wrap !important; margin: 0 !important; }
                    #dateInterval b { font-size: 10px !important; color: #000 !important; display: block !important; }
                    .report-header { font-size: 12px !important; margin: 0 !important; font-weight: bold !important; }
                    .report-subheader { font-size: 8px !important; margin: 0 !important; }
                    .table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }
                    h3, h4 { font-size: 10px !important; margin: 2px 0 !important; display: block !important; visibility: visible !important; }
                    strong { font-size: 8px !important; display: inline !important; visibility: visible !important; }
                    /* Hide center titles with underlines */
                    center h4 { display: none !important; }
                    /* Show export table and header in print */
                    #export-table-holder { display: block !important; }
                    #export_table_header { display: block !important; width: 100% !important; margin-bottom: 10px !important; }
                    #export_table_header img { width: 60px !important; }
                    #export_table_header h3 { font-size: 12px !important; margin: 0 0 5px 0 !important; display: block !important; visibility: visible !important; }
                    #export_table_header pre { font-size: 8px !important; line-height: 1.3 !important; display: block !important; visibility: visible !important; }
                    #export_table_header p { font-size: 8px !important; display: block !important; visibility: visible !important; }
                    #export_table_header b { font-size: 10px !important; display: block !important; visibility: visible !important; color: #000 !important; border: none !important; background: transparent !important; }
                    #export_table_header strong { font-size: 10px !important; display: inline !important; visibility: visible !important; color: #000 !important; border: none !important; background: transparent !important; }
                    #export_table_header div { display: block !important; visibility: visible !important; border: none !important; background: transparent !important; }
                    #export_table_header td { padding: 3px !important; display: table-cell !important; visibility: visible !important; }
                    #export_table_header tr { display: table-row !important; visibility: visible !important; }
                    #export_table_header tbody { display: table-row-group !important; visibility: visible !important; }
                    #export_table { display: table !important; width: 100% !important; font-size: 8px !important; margin-top: 0 !important; }
                    #export_table th, #export_table td { font-size: 8px !important; padding: 3px 4px !important; }
                    /* Optimize column widths for export table */
                    #export_table th:nth-child(1), #export_table td:nth-child(1) { width: 10% !important; } /* Student ID */
                    #export_table th:nth-child(2), #export_table td:nth-child(2) { width: 20% !important; } /* Student Name - Extended */
                    #export_table th:nth-child(3), #export_table td:nth-child(3) { width: 12% !important; } /* Class */
                    #export_table th:nth-child(4), #export_table td:nth-child(4) { width: 11% !important; text-align: right !important; } /* Arrears */
                    #export_table th:nth-child(5), #export_table td:nth-child(5) { width: 12% !important; text-align: right !important; } /* Term Bill */
                    #export_table th:nth-child(6), #export_table td:nth-child(6) { width: 12% !important; text-align: right !important; } /* Total Owe */
                    #export_table th:nth-child(7), #export_table td:nth-child(7) { width: 11% !important; text-align: right !important; } /* Paid */
                    #export_table th:nth-child(8), #export_table td:nth-child(8) { width: 12% !important; text-align: right !important; } /* Balance Due */
                    #export_table thead { background: #f3f4f6 !important; }
                    #export_table thead th { background: #f3f4f6 !important; color: #111827 !important; }
                    /* Hide DataTables buttons and controls */
                    .dt-buttons { display: none !important; }
                    .dataTables_wrapper { display: contents !important; }
                    .dataTables_length { display: none !important; }
                    .dataTables_filter { display: none !important; }
                    .dataTables_info { display: none !important; }
                    .dataTables_paginate { display: none !important; }
                    div.dt-container { display: contents !important; }
                    /* Hide any button elements */
                    #export-table-holder button { display: none !important; }
                    #export-table-holder .btn { display: none !important; }
                    /* Hide empty cells that might show as boxes */
                    #export_table td:empty { border: none !important; background: transparent !important; }
                    #export_table tfoot td:empty { display: none !important; }
                    /* Aggressively remove all borders and backgrounds in export area */
                    #export-table-holder * { border: none !important; }
                    #export-table-holder div:not(#export_table_header):not(#export_table_header *) { background: transparent !important; border: none !important; }
                    /* Only allow borders on the actual table */
                    #export_table, #export_table th, #export_table td { border: 1px solid #ccc !important; }
                    #export_table_header table, #export_table_header td, #export_table_header tr { border: none !important; }
                    /* Hide the regular page header when export table is visible */
                    #export-table-holder:not(:empty) ~ * #top_row { display: none !important; }
                    body:has(#export_table) #top_row { display: none !important; }
                    body:has(#export_table) #print > center { display: none !important; }
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
                                <i class="fa fa-file-text" style="color:#3b82f6;"></i> Accounts Receivables Report
                            </h3>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" id="export-button" onclick="reloadForm()" class="btn-modern btn-success-modern">
                                    <i class="fa fa-sync"></i> Load
                                </button>
                                <button type="button" onclick="exportToExcel()" class="btn-modern btn-excel-modern">
                                    <i class="fa fa-file-excel-o"></i> Excel
                                </button>
                                <button type="button" onclick="PrintElem('#print')" class="btn-modern btn-primary-modern">
                                    <i class="fa fa-print"></i> Print
                                </button>
                                <button type="button" onclick="openSmsWizard()" class="btn-modern btn-sms-modern" title="Send fee reminder SMS to parents of debtors">
                                    <i class="fa fa-comment"></i> SMS Debtors
                                </button>
                                <button type="button" onclick="dashboard()" class="btn-modern btn-danger-modern">
                                    <i class="fa fa-home"></i> Dashboard
                                </button>
                            </div>
                        </div>

                        <!-- Filter Form -->
                        <?php echo form_open(site_url('admin/financial_reports/receivables/load') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'receivables_load'));?>
                        <input type="hidden" name="export" id="export" value="0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> Period</label>
                                    <select class="modern-input" name="all_customize" id="all_customize">
                                        <option value="1" selected>Customize</option>
                                        <option value="0">All</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> Select Term</label>
                                    <select class="modern-input" name="term" id="term">
                                        <?php
                                            for($i = 1; $i <= 3; $i++) {
                                                ?>
                                                <option value="<?=$i;?>" <?=$i == get_settings('running_term') ? 'selected' : ''; ?>><?=$i;?></option>
                                                <?php
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-calendar"></i> Select Year</label>
                                    <select class="modern-input" name="year" id="year">
                                        <option value="" selected>Select year</option>
                                        <?php echo populate_academic_year('yes'); ?>
                                    </select>
                                    <div id="desc"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-list"></i> Type</label>
                                    <select class="modern-input" name="search_by_type" id="search_by_type">
                                        <option value="1">Billed Invoice</option>
                                        <option value="2">Daily Fees (Feeding, Breakfast, Classes, Water, Transport)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <input type="hidden" name="boarding_system" value="<?=$boarding_system;?>">
                            <?php if($boarding_system == 'yes'):?>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-home"></i> Residence Type</label>
                                    <select class="modern-input" name="search_by_residence_type" id="search_by_residence_type">
                                        <option value="0">All</option>
                                        <option value="Day">Day Students</option>
                                        <option value="Boarding">Boarding Students</option>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label><i class="fa fa-filter"></i> Category of Selection</label>
                                    <select class="modern-input" name="search_by_category" id="search_by_category">
                                        <option value="0" selected>All Students</option>
                                        <option value="1">Filter By Class</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 toggle_row" style="display: none;">
                                <div class="filter-group">
                                    <label><i class="fa fa-building"></i> Filter By Class</label>
                                    <select class="modern-input" name="search_by_class" id="search_by_class">
                                        <option value="0" selected>Select a class</option>
                                        <?php getFullClassList(); ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 toggle_row" id="toggle_selected_class" style="display: none;">
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
                                border-radius: 5px;

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

                                .dt-start, .dt-end {

                                    display: none !important;
                                }

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
                                                        <td align="right" colspan="2"><h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px;">ACCOUNTS RECEIVABLE REPORT</h3></td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right" style="text-align: right !important;">
                                                        <span id="period">Term / Year: </span>
                                                            <strong>
                                                                <span id="periodArea" style="display: none">AS AT TODAY</span>

                                                                <span id="dateInterval">
                                                                    <b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;"><span id="fromDate"><?=get_settings('running_term'); ?></span> / <span id="toDate"><?=get_settings('running_year'); ?></span></b>
                                                                </span>

                                                                <span id="allDate"></span>
                                                                
                                                            </strong>
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
                                        <u id="captionHeader" style="letter-spacing: 3px">RECEIVABLES GENERATED FOR ALL STUDENTS</u><br><br>
                                        <u id="captionHeaderFct" style="letter-spacing: 3px"></u>
                                    </strong></h4></center>
                                    

                                    <div id="table-holder">
                                        
                                        <table class="table" id="invoice_table" style="width:100%; border-collapse:collapse;">
                                            <thead style="padding: 30px;">
                                                <tr style="height: 30px;">
                                                    <th colspan="2" style="text-align: left"><strong>STUDENTS (DEBTORS)</strong></th>
                                                    <th style="text-align: left"><strong>INVOICES</strong></th>
                                                    <!-- <th style="text-align: right"><strong>SUB-TOTAL</strong></th> -->
                                                    <th style="text-align: right"><strong>TOTAL (<?=$currency; ?>)</strong></th>
                                                    
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>

                                        <!-- export section -->
                                        <div id="export-table-holder"></div>
                                        
                                               
                                                
                                                
                                            
                                    </div>
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

    //$('#export_table_wrapper').css('display', 'none');

        
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
            $('#captionHeader').html('RECEIVABLES GENERATED FOR ALL STUDENTS');
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
      

      $('#all_customize').change(function(ev) {
            let valSelected = $(this).val();
            let term = $('#term').val();
            let year = $('#year').val();

            if(valSelected == 0) {
                $('#term, #year').slideUp('slow');

                 $.ajax({
                    url: '<?php echo site_url('admin/getDate/0'); ?>',
                    dataType: 'html',
                    type: 'post',
                })
                .done(function(data) {
                    $('#allDate').html(data);
                    $('#dateInterval').slideUp('slow');
                    $('#allDate').slideDown('slow');
                })
                .fail(function(err) {
                    showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
                })


            } else {
                $('#term, #year').slideDown('slow');

                 $.ajax({
                    url: '<?php echo site_url('admin/getDate/1/'); ?>' + term + '/' + year,
                    dataType: 'json',
                    type: 'post',
                })
                .done(function(data) {
                    $('#toDate').html(data.term);
                    $('#toDate').html(data.year);

                    $('#allDate').slideUp('slow');
                    $('#dateInterval').slideDown('slow');
                    
                })
                .fail(function(err) {
                    showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
                })
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
            $('#captionHeaderFct').text('DAILY FEES (FEEDING, BREAKFAST, CLASSES, WATER & TRANSPORT)');
            setTimeout(() => {
                $('#desc').html('This shows owing to current date');
                $('#periodArea').slideDown('slow');
            }, 1000);
            $('#export-button').attr('disabled', 'disabled');
            $('#period').slideUp('slow');
        } else {
            $('#periodArea').slideUp('slow');
            $('#captionHeaderFct').text('');
            $('#desc').html('');
            $('.date').slideDown('slow');
            $('#period').slideDown('slow');
            $('#export-button').removeAttr('disabled');
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
                    $('#captionHeader').html('RECEIVABLES GENERATED FOR ' + headingData + ' STUDENTS');
                    
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
                    $('#captionHeader').html('RECEIVABLES GENERATED FOR ' + headingData + ' STUDENTS');
                } else {
                    //STUDENT SELECTED
                    $('#captionHeader').html('RECEIVABLES GENERATED FOR ' + headingData);
                }
                
                
            })
            .fail(function(error) {
                showAjaxModal_alert('Some data were not updated properly!<br>Please refresh the page and try again.', 'Error');
            });

            //call the new load method
            reloadForm();
      })
      
      //get selected date and update the period
      $('#term').change(function(ev) {
        $.ajax({
            url: '<?php echo site_url('admin/getDate/'); ?>' + $(this).val(),
            dataType: 'html',
            type: 'post',
        })
        .done(function(data) {
            $('#fromDate').html(data);
        })
        .fail(function(err) {
            showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
        })
      });

      $('#year').change(function(ev) {
        $.ajax({
            url: '<?php echo site_url('admin/getDate/'); ?>' + $(this).val(),
            dataType: 'html',
            type: 'post',
        })
        .done(function(data) {
            $('#toDate').html(data);
        })
        .fail(function(err) {
            showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
        })
      });

      //Load data here
      $('#receivables_load').submit(function(ev) {
        ev.preventDefault();

         //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

        let category = $('#search_by_category').val();
        let class_id = $('#search_by_class').val();


        let exportVal = $('#export').val();


        if(category == 1 && class_id == 0) {
            showAjaxModal_alert('Action Terminated!<br>Please select a class.', 'Error');
            return false;
        }
        showAjaxModal_alert('Fetching Data. Please wait... <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

        var residenceType = $('#search_by_residence_type').val();
        var categoryText = category == 0 ? 'ALL STUDENTS' : $('#search_by_class option:selected').text();
        var residenceText = residenceType == 0 ? '' : ' - ' + $('#search_by_residence_type option:selected').text();
        
            $.ajax({
              url: '<?php echo site_url('admin/financial_reports/receivables/load/'); ?>' + exportVal,
              type: 'POST',
              dataType: 'html',
              data: new FormData(this),
              cache: false,
              contentType: false,
              processData: false
            })
            .done(function(data) {
                // update the table caption
                $('#captionHeader').html('RECEIVABLES GENERATED FOR ' + categoryText + ' ' + residenceText.toUpperCase());
                if(exportVal == 'export') {
                    $('#export-table-holder').html(data); //give the data to this table

                    $('#invoice_table').slideUp('slow'); //hide this table
                    $('#export-table-holder').slideDown('slow'); //show this table
                    $('#export_table_wrapper').slideDown('slow'); //show this table



                } else {
                    $('#invoice_table tbody').html(data); //give the data to this table
                    $('#export-table-holder').slideUp('slow'); //hide this table
                    $('#export_table_wrapper').slideUp('slow'); //hide this table
                    $('#invoice_table').slideDown('slow'); //show this table
                }


                /*Show done message*/
                setTimeout(() => {
                    showAjaxModal_alert('Data Fetched Successfully', 'Success');

                }, 2000);

                /*Close the modal*/
                setTimeout(() => {
                    $('.close').click();//close modal

                    //reset the export value to default = 0
                    $('#export').val('0');

                }, 3500);
                
            })
            .fail(function(err) {
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Error loading data!' + err.responseText + '</div></center>', 'Error');
            });
      });

    });

   //reload form for export
  function reloadForm() {

      //set export value to 'export'
      $('#export').val('export');

      //Load data here
      $('#receivables_load').submit();

  }
    
    function dashboard() {
            location.href = '<?=site_url('admin/dashboard'); ?>';
        }
   
    // Print Function - Use native browser print
    function PrintElem(elem) {
        // Check if regular table has data
        const table = document.getElementById('invoice_table');
        const tbody = table ? table.querySelector('tbody') : null;
        const hasRegularData = tbody && tbody.innerHTML && tbody.innerHTML.trim() !== '' && tbody.querySelectorAll('tr').length > 0;
        
        // Check if export table has data
        const exportHolder = document.getElementById('export-table-holder');
        const exportTable = exportHolder ? exportHolder.querySelector('#export_table') : null;
        const exportTbody = exportTable ? exportTable.querySelector('tbody') : null;
        const hasExportData = exportTbody && exportTbody.innerHTML && exportTbody.innerHTML.trim() !== '' && exportTbody.querySelectorAll('tr').length > 0;
        
        // If neither table has data, show warning
        if (!hasRegularData && !hasExportData) {
            showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
            return;
        }
        
        // Use native browser print
        window.print();
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>ACCOUNTS RECEIVABLE REPORT</title>');
        
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-forms.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<<?php echo base_url('assets/css/neon-core.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/bootstrap.css');?>" />');

        mywindow.document.write('<style type="text\/css">.img-circle {width: 90% !important;}<\/style>');

        mywindow.document.write('<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script src="<?php echo base_url('assets/js/neon-custom.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');
        

        mywindow.document.write('<\/head><body style="font-size: 13px">');
        mywindow.document.write(data);

        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }


    /*function print_page() {
        //window.location.reload();
        print();
       }

       function page_reload() {
        window.location.reload();
       }*/


        /*$(function() {
            setTimeout(() => {
              print_page();
            }, 1000);
            
        });*/
</script>


<!-- SMS Debtors wizard modal shell -->
<div class="modal fade sms-wiz-modal" id="smsWizardModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border-radius:14px 14px 0 0; padding:16px 22px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:.9;">&times;</button>
                <h4 class="modal-title" style="font-weight:700;"><i class="fa fa-comment"></i> SMS Fee Reminders — Debtors</h4>
            </div>
            <div class="modal-body" id="smsWizardHolder" style="padding:22px; max-height:70vh; overflow-y:auto;">
                <div style="text-align:center; padding:40px; color:#6b7280;">
                    <i class="fa fa-spinner fa-spin" style="font-size:26px; color:#6366f1;"></i>
                    <div style="margin-top:10px; font-weight:600;">Loading wizard…</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function openSmsWizard() {
    jQuery('#smsWizardHolder').html('<div style="text-align:center;padding:40px;color:#6b7280;"><i class="fa fa-spinner fa-spin" style="font-size:26px;color:#6366f1;"></i><div style="margin-top:10px;font-weight:600;">Loading wizard…</div></div>');
    jQuery('#smsWizardModal').modal('show');
    jQuery.ajax({
        url: '<?php echo site_url('admin/getReceivablesSMSStep/1'); ?>',
        type: 'GET',
        success: function(html) {
            jQuery('#smsWizardHolder').html(html);
        },
        error: function() {
            jQuery('#smsWizardHolder').html('<div style="text-align:center;padding:30px;color:#b91c1c;font-weight:600;">Could not load the wizard. Please try again.</div>');
        }
    });
}
</script>
