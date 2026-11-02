<!doctype html>
<html>
    <head>
        <title>STUDENTS PAYMENTS REPORT</title>
        <?php include 'includes/includes_top.php';?>

    </head>
    <body> 
        <?php
            $boarding_system = $this->db->get_where('settings' , array('type'=>'boarding_system'))->row()->description;
        ?>

        <div class="<?=$boarding_system == 'yes' ? 'container-fluid' : 'container';?>" id="top"  <?=$boarding_system == 'yes' ? 'style="margin: 15px"' : '';?>>
            
            <!-- Page Title -->
            <div class="page-title-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                    <i class="fa fa-file-text" style="margin-right: 12px;"></i>Payments Report
                </h1>
                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500;">
                    View and track all student payment transactions and history
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
                <a href="<?php echo site_url('admin/financial_reports/payments'); ?>" class="tab-link active" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
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
                .filters-grid { display:grid; grid-template-columns:repeat(1,minmax(0,1fr)); gap:12px; }
                @media (min-width: 640px) { .filters-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
                @media (min-width: 1024px) { .filters-grid { grid-template-columns:repeat(4,minmax(0,1fr)); } }
                .filters-secondary { display:grid; grid-template-columns:repeat(1,minmax(0,1fr)); gap:12px; margin-top:16px; }
                @media (min-width: 640px) { .filters-secondary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
                @media (min-width: 1024px) { .filters-secondary { grid-template-columns:repeat(3,minmax(0,1fr)); } }
            </style>
            <div class="row" id="selectors_div">
                <div class="col-sm-12">
                    <div class="filters-card">
                        <!-- Header Row with Title and Action Buttons -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                            <h3 style="margin:0; font-size:20px; font-weight:700; color:#1f2937;">
                                <i class="fa fa-money" style="color:#3b82f6;"></i> Students Payments Report
                            </h3>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button type="button" onclick="$('#payments_load').submit();" class="btn-modern btn-success-modern">
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
                        <?php echo form_open(site_url('admin/financial_reports/payments/load') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'payments_load'));?>

                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="start_date"><i class="fa fa-calendar"></i> Start Date</label>
                                <input type="text" name="start_date" id="start_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                            </div>

                            <div class="filter-group">
                                <label for="end_date"><i class="fa fa-calendar"></i> End Date</label>
                                <input type="text" name="end_date" id="end_date" class="modern-input datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>">
                                <div id="desc"></div>
                            </div>

                            <div class="filter-group">
                                <label for="search_by_type"><i class="fa fa-list"></i> Type</label>
                                <select class="modern-input" name="search_by_type" id="search_by_type">
                                    <option value="1">Billed Invoice</option>
                                    <option value="2">Daily Fees (Feeding, Breakfast, Classes, Water, Transport)</option>
                                </select>
                            </div>

                            <input type="hidden" name="boarding_system" value="<?=$boarding_system;?>">
                            <?php if($boarding_system == 'yes'):?>
                            <div class="filter-group">
                                <label for="search_by_residence_type"><i class="fa fa-home"></i> Residence Type</label>
                                <select class="modern-input" name="search_by_residence_type" id="search_by_residence_type">
                                    <option value="0">All</option>
                                    <option value="Day">Day Students</option>
                                    <option value="Boarding">Boarding Students</option>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="filter-group">
                                <label for="search_by_payment_method"><i class="fa fa-credit-card"></i> Payment Method</label>
                                <select class="modern-input" name="search_by_payment_method" id="search_by_payment_method">
                                    <option value="0">All Payment Methods</option>
                                    <?php
                                        $payment_methods = get_payment_methods();
                                        if(!empty($payment_methods)) {
                                            foreach($payment_methods as $method) {
                                                echo '<option value="' . $method->id . '">' . $method->name . '</option>';
                                            }
                                        } else {
                                            // Fallback if database table doesn't exist
                                            echo '<option value="1">Cash</option>';
                                            echo '<option value="2">Cheque</option>';
                                            echo '<option value="3">Mobile Money</option>';
                                            echo '<option value="4">Bank Transfer</option>';
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="search_by_bill_item"><i class="fa fa-file-text"></i> Bill Item</label>
                                <select class="modern-input" name="search_by_bill_item" id="search_by_bill_item">
                                    <option value="0" selected>All Bill Items</option>
                                    <?php
                                        $allBillItems = $this->financial_report_model->getAllBillItems();

                                        foreach($allBillItems as $item):
                                            ?>
                                            <option value="<?=$item['title'];?>"><?=$item['title'];?></option>
                                            <?php

                                        endforeach;
                                    ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="search_by_category"><i class="fa fa-filter"></i> Category</label>
                                <select class="modern-input" name="search_by_category" id="search_by_category">
                                    <option value="0" selected>All Students</option>
                                    <option value="1">Filter By Class</option>
                                </select>
                            </div>
                            <div class="filter-group" id="toggle_class" style="display: none;">
                                <label for="search_by_class"><i class="fa fa-building"></i> Filter By Class</label>
                                <select class="modern-input" name="search_by_class" id="search_by_class">
                                    <option value="0">All Classes</option>
                                    <?php getFullClassList(); ?>
                                </select>
                            </div>
                            <div class="filter-group" id="toggle_selected_class" style="display: none;">
                                <label for="search_by_student"><i class="fa fa-user"></i> Students In Selected Class</label>
                                <select class="modern-input boxit" name="search_by_student" id="search_by_student">
                                    <option value="0">Please select class first</option>
                                </select>
                            </div> 
                        </div>
                        <div class="filters-secondary">
                            <div id="search_by_report_type_holder" style="display: none;">
                                <label for="search_by_report_type"><i class="fa fa-chart-bar"></i> Report Type</label>
                                <select class="modern-input" name="search_by_report_type" id="search_by_report_type">
                                    <option value="1" selected>By General Dates</option>
                                    <option value="2">By Weekly</option>
                                </select>
                            </div>

                            <div id="termWeekHolder" style="display: none">
                                <div class="filters-secondary">
                                    <div>
                                        <label for="term"><i class="fa fa-calendar"></i> Select Term</label>
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

                                    <div>
                                        <label for="year"><i class="fa fa-calendar"></i> Select Year</label>
                                        <select class="modern-input" name="year" id="year">
                                            <option value="" selected>Select year</option>
                                            <?php echo populate_academic_year('yes'); ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="week"><i class="fa fa-calendar"></i> Select Week</label>
                                        <select class="modern-input" name="week" id="week">
                                            <option value="1">Week 1</option>
                                            <option value="2">Week 2</option>
                                            <option value="3">Week 3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </form>
                    </div>
                </div>
            </div>

        <div class="container">
            <br><br>
            <div class="row">
                <div class="col-lg-12">
                    <div id="print" style="width: 100% !important">
                        <?php
                        //Account section
                        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
                        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

                        $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
                        $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
                        $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
                        $phone            =   $this->db->get_where('settings' , array('type'=>'phone'))->row()->description;

                        // Initialize dates if not set
                        if(!isset($date_start)) {
                            $date_start = strtotime(date('d-m-Y'));
                        }
                        if(!isset($date_end)) {
                            $date_end = strtotime(date('d-m-Y'));
                        }

                        ?>

                       

                        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
                        <!--Chartjs-->
                        <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>
                        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>

                        <style type="text/css">
                            #invoice_table { width:100%; border-collapse:collapse; margin-top:20px; }
                            #invoice_table thead { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; }
                            #invoice_table thead th { color:white !important; padding:15px; font-weight:600; text-align:left; background:transparent !important; }
                            #invoice_table thead tr th { color:white !important; }
                            #invoice_table tbody td { padding:12px; border-bottom:1px solid #e5e7eb; color:#374151; }
                            #invoice_table tbody tr:hover { background:#f9fafb; }
                            #top_row { border:none; }
                            #top_row td { border:none; padding:10px; }
                            .report-header { font-weight:700; font-size:20px; letter-spacing:2px; color:#1f2937; }
                            .report-subheader { font-size:14px; color:#6b7280; margin-top:5px; }

                            #selectors_div {
                                padding-top: 10px;
                                position: sticky;
                                top: 0px;
                                z-index: 100;
                                background-color: #f9fafb;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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

                            @media print {
                                @page { margin: 0.3cm; }
                                * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                                html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; font-size: 9px !important; color: #000 !important; }
                                #selectors_div { display:none !important; }
                                #logo_ { width:60px !important; }
                                .filters-card { display:none !important; }
                                .page-title-header { display:none !important; }
                                .row:has(canvas) { display:none !important; }
                                canvas { display:none !important; }
                                .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
                                .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
                                .col-lg-12, .col-md-12, .col-sm-12, [class*="col-"] { padding: 0 !important; width: 100% !important; max-width: none !important; }
                                #print { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
                                page { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; display: block !important; }
                                center { width: 100% !important; max-width: none !important; display: block !important; }
                                table { width: 100% !important; max-width: none !important; border-collapse: collapse !important; }
                                /* Compact Bank Statement Style - All Columns Fit */
                                #invoice_table { 
                                    width: 100% !important; 
                                    max-width: 100% !important; 
                                    font-size: 9px !important; 
                                    table-layout: fixed !important; 
                                    border-collapse: collapse !important;
                                }
                                #invoice_table th, #invoice_table td { 
                                    padding: 3px 4px !important; 
                                    word-wrap: break-word !important;
                                    word-break: break-word !important;
                                    border: 1px solid #ccc !important; 
                                    font-size: 9px !important; 
                                    line-height: 1.2 !important;
                                    overflow-wrap: break-word !important;
                                    hyphens: auto !important;
                                }
                                #invoice_table thead { 
                                    background: #f3f4f6 !important; 
                                }
                                #invoice_table thead th { 
                                    background: #f3f4f6 !important; 
                                    color: #111827 !important; 
                                }
                                #invoice_table th { 
                                    background: #f3f4f6 !important; 
                                    color: #111827 !important; 
                                    font-weight: bold !important; 
                                    font-size: 9px !important; 
                                    padding: 4px 3px !important;
                                    white-space: normal !important;
                                    border: 1px solid #666 !important;
                                }
                                #invoice_table td { 
                                    vertical-align: top !important;
                                    white-space: normal !important;
                                }
                                /* Optimized column widths - Total 100% */
                                #invoice_table th:nth-child(1), #invoice_table td:nth-child(1) { 
                                    width: 3% !important; 
                                    text-align: center !important; 
                                    font-size: 8px !important; 
                                } /* S/N */
                                #invoice_table th:nth-child(2), #invoice_table td:nth-child(2) { 
                                    width: 10% !important; 
                                    font-size: 8px !important;
                                    white-space: nowrap !important;
                                } /* Student ID */
                                #invoice_table th:nth-child(3), #invoice_table td:nth-child(3) { 
                                    width: 25% !important; 
                                    font-size: 8px !important;
                                    max-width: 25% !important;
                                    white-space: nowrap !important;
                                    overflow: hidden !important;
                                    text-overflow: ellipsis !important;
                                } /* Student Name - Increased */
                                #invoice_table th:nth-child(4), #invoice_table td:nth-child(4) { 
                                    width: 17% !important; 
                                    font-size: 8px !important;
                                    max-width: 17% !important;
                                    white-space: nowrap !important;
                                    overflow: hidden !important;
                                    text-overflow: ellipsis !important;
                                } /* Class - Increased */
                                #invoice_table th:nth-child(5), #invoice_table td:nth-child(5) { 
                                    width: 15% !important; 
                                    font-size: 8px !important;
                                    max-width: 15% !important;
                                    white-space: nowrap !important;
                                    overflow: hidden !important;
                                    text-overflow: ellipsis !important;
                                } /* Method - Reduced */
                                #invoice_table th:nth-child(6), #invoice_table td:nth-child(6) { 
                                    width: 15% !important; 
                                    text-align: right !important; 
                                    font-size: 8px !important; 
                                    white-space: nowrap !important;
                                } /* Total Paid - Reduced */
                                #invoice_table th:nth-child(7), #invoice_table td:nth-child(7) { 
                                    width: 15% !important; 
                                    text-align: right !important; 
                                    font-size: 8px !important;
                                    white-space: nowrap !important;
                                } /* Total Due - Reduced */
                                /* Ensure period/date range shows */
                                #top_row { width: 100% !important; max-width: none !important; margin-bottom: 8px !important; display: table !important; visibility: visible !important; }
                                #top_row td { padding: 3px !important; font-size: 9px !important; display: table-cell !important; visibility: visible !important; }
                                #top_row td strong { display: inline !important; visibility: visible !important; font-size: 9px !important; }
                                #top_row span { display: inline !important; font-size: 9px !important; visibility: visible !important; }
                                #top_row tr { display: table-row !important; visibility: visible !important; }
                                #top_row table { display: table !important; visibility: visible !important; }
                                #top_row thead { display: table-header-group !important; visibility: visible !important; }
                                #top_row tbody { display: table-row-group !important; visibility: visible !important; }
                                #periodArea { display: none !important; }
                                #dateInterval { display: inline !important; visibility: visible !important; }
                                #fromDate, #toDate { display: inline !important; visibility: visible !important; opacity: 1 !important; }
                                #periodDisplay { display: block !important; visibility: visible !important; font-size: 9px !important; text-align: right !important; }
                                /* Ensure all header content prints */
                                #print table:first-of-type, #print center > table { display: table !important; }
                                .report-header { font-size: 12px !important; margin: 0 !important; font-weight: bold !important; }
                                .report-subheader { font-size: 9px !important; margin: 0 !important; }
                                .table-responsive { overflow: visible !important; width: 100% !important; max-width: none !important; }
                                div { width: auto !important; max-width: none !important; display: block !important; font-size: inherit !important; color: inherit !important; }
                                #top_row div { display: block !important; visibility: visible !important; font-size: 14px !important; color: #000 !important; }
                                #top_row { color: #000 !important; font-size: 9px !important; }
                                #periodDisplay { font-size: 14px !important; color: #000 !important; line-height: 1.5 !important; }
                                h3, h4 { font-size: 11px !important; margin: 4px 0 !important; display: block !important; visibility: visible !important; }
                                #top_row h3 { display: block !important; visibility: visible !important; font-size: 11px !important; font-weight: bold !important; }
                                #top_row h4 { display: block !important; visibility: visible !important; font-size: 9px !important; }
                                #top_row img { display: inline-block !important; visibility: visible !important; }
                                #top_row pre { display: block !important; visibility: visible !important; white-space: pre-wrap !important; margin: 0 !important; }
                                h4 u { font-size: 10px !important; }
                                strong { font-size: 9px !important; display: inline !important; visibility: visible !important; }
                                center h4 { font-size: 11px !important; }
                                tr[style*="border-top"] td { font-size: 9px !important; padding: 3px !important; }
                                /* Summary section */
                                tbody tr td[colspan] { font-size: 8px !important; padding: 2px 3px !important; }

                                .label-danger {
                                    background-color: #d9534f !important;
                                }

                                table:not(#top_row):not(#top_row table) {
                                    width: 100% !important;
                                }
                                #top_row, #top_row table {
                                    width: 100% !important;
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
                                    width: 60px !important;
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
                                    width: 100% !important;
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

                            <table class="table" id="top_row" style="width:100%; border-collapse:collapse; display:table !important; visibility:visible !important;">
                                <tbody style="display:table-row-group !important; visibility:visible !important;">
                                    <tr style="display:table-row !important; visibility:visible !important;">
                                        <td width="60%" style="display:table-cell !important; visibility:visible !important; padding:5px;">
                                           <table class="table" style="margin-top: -10px; border-collapse:collapse; display:table !important; width:100%;">
                                                <tbody style="display:table-row-group !important;">
                                                    <tr style="display:table-row !important;">
                                                    <td style="display:table-cell !important; padding:5px;">
                                                       <img class="" id="logo_" src="<?php echo base_url(); ?>uploads/school_logo.png" width="110px"> 
                                                    </td>
                                                    <td style="display:table-cell !important; padding:5px;">
                                                        <h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px; margin: 0 0 5px 0 !important; display:block !important; visibility:visible !important;"><?php echo $system_name;?></h3>
                                                        <pre style="display:block !important; visibility:visible !important; margin: 0 !important; padding: 0 !important; white-space: pre-wrap !important; font-family: Nunito !important; font-size: 14px !important; line-height: 1.3 !important;"><?php echo trim($address);?></pre>
                                                    </td> 
                                                    </tr>
                                                    <tr style="border-top: 2px solid #1b1b69; display:table-row !important;">
                                                        <td colspan="2" style="display:table-cell !important;"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>

                    
                                        <td width="40%" style="display:table-cell !important; visibility:visible !important; padding:5px;">
                                            <table class="table" style="width: 100%; border-collapse:collapse; display:table !important;">
                                                <tbody style="display:table-row-group !important;">
                                                    <tr style="display:table-row !important;">
                                                        <td align="right" colspan="2" style="display:table-cell !important; visibility:visible !important; padding:5px;">
                                                            <h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px; display:block !important; visibility:visible !important;">STUDENTS PAYMENT REPORTS</h3>
                                                        </td>
                                                    </tr>
                                                    <tr style="display:table-row !important;">
                                                        <td align="right" colspan="2" style="display:table-cell !important; visibility:visible !important; padding:5px !important; overflow:visible !important;">
                                                            <div id="periodDisplay" style="display: block !important; visibility: visible !important; text-align: right !important; font-size: 11px !important; font-weight: bold !important; padding: 5px !important; overflow: visible !important; width: 100% !important;">
                                                                <b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;">Period: From: <?=date('M, d Y', $date_start); ?> To: <?=date('M, d Y', $date_end); ?></b>
                                                            </div>
                                                        </td> 
                                                    </tr>
                                                    <tr style="border-top: 2px solid #1b1b69; display:table-row !important;">
                                                        <td colspan="2" style="display:table-cell !important;"></td>
                                                    </tr>
                                                </tbody>
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
                                        <u id="captionHeader" style="letter-spacing: 3px">PAYMENTS REPORT GENERATED FOR ALL STUDENTS</u><br><br>
                                        <u id="captionHeaderFct" style="letter-spacing: 3px"></u>
                                    </strong></h4></center>
                                    <table class="table" id="invoice_table" style="width:100%; border-collapse:collapse;">
                                        <thead style="padding: 30px;">
                                            <tr>
                                                <th style="text-align: left"><strong>S/N</strong></th>
                                                <th style="text-align: left"><strong>STUDENT ID</strong></th>
                                                <th style="text-align: left"><strong>STUDENTS</strong></th>
                                                <th style="text-align: left"><strong>CLASS</strong></th>
                                                <th style="text-align: left"><strong>METHOD</strong></th>
                                                <th style="text-align: right"><strong>TOTAL PAID</strong></th>
                                                <th style="text-align: right"><strong>TOTAL DUE</strong></th>  
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>

                                    <div id="fct_type_holder" style="display: none"></div>
                                </div>
                                
                                <!-- <div class="container-fluid">
                                    <center>
                                    <footer class="footer" style="position: fixed; bottom: -20px">

                                        <div style="width: 100%; height: 30px; margin-bottom: 20px; padding: 5px; text-align: left"><smal style="color:#1d1c1c; text-shadow: 1px 1px 0px #504949; position: relative; top: 5px; letter-spacing: 0.3px"><i>Software Developed & Designed by: Lightworld Technologies Ltd. Contact us on 0243618186 | 0247612799 - Accra</i></smal></smal>
                                    </div>
            
                                </footer>
                                </center>
                                </div> -->
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

<style>
    @media print {
        /* Hide modal completely in print */
        .modal, .modal-backdrop, [role="dialog"] { display: none !important; }
    }
</style>

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

      	 // Ensure mutually exclusive selection between 'All Classes' (0) and specific classes
      	//  $('#search_by_class').on('select2:select', function (e) {
      	//  	 var selectedValues = $(this).val() || [];
      	//  	 var selectedId = e.params && e.params.data ? String(e.params.data.id) : null;
      	//  	 if (selectedId === '0') {
      	//  	 	 // If 'All Classes' was just selected, clear other selections
      	//  	 	 $(this).val(['0']).trigger('change.select2');
      	//  	 } else {
      	//  	 	 // If a specific class was selected, remove 'All Classes' if present
      	//  	 	 var cleaned = selectedValues.filter(function(v){ return String(v) !== '0'; });
      	//  	 	 if (cleaned.length !== selectedValues.length) {
      	//  	 	 	 $(this).val(cleaned).trigger('change.select2');
      	//  	 	 }
      	//  	 }
      	//  });

      	 // Also handle deselect to avoid leaving inconsistent states
      	//  $('#search_by_class').on('select2:unselect', function () {
      	//  	 var selectedValues = $(this).val() || [];
      	//  	 // If nothing left selected, reset to 'All Classes' implicitly if needed by UX (skip auto-select)
      	//  });


     
      /*Toggle class selection when category changes*/
      $('#search_by_category').change(function(ev) {

        let bill_item = $('#search_by_bill_item').val();
            
        let bill_text = '';
        if(bill_item != '0') {
            bill_text = '(' + bill_item + ')';
        }

        $('#invoice_table tbody').html('');
        /*show or hide the toggle row*/
        if($(this).val() == 0) {
            $('#captionHeader').html(bill_text + ' PAYMENTS REPORT GENERATED FOR ALL STUDENTS');
            /*all students selected so slideup*/
            $('#search_by_class').prop('selectedIndex', 0);
            $('#toggle_row').slideUp('slow');
            $('#toggle_class').slideUp('slow');
            
        } else {
            $('#toggle_row').slideDown('slow');
            $('#toggle_class').slideDown('slow');
            
            $('#captionHeader').html('');
        }
        
      });

      /*if feeding, classes or transport is selected, we don't need date*/
      $('#search_by_type').change(function(ev) {

        $('#invoice_table tbody').html('');
        /*show or hide the toggle row*/
        if($(this).val() == 2) {

            /*all students selected so slideup*/
            $('#captionHeaderFct').text('DAILY FEES (FEEDING, BREAKFAST, CLASSES, WATER & TRANSPORT)');

            $('#lastHeading').html('');
            $('#lastHeading').html('<div class="col-md-12 col-lg-12 text-right"><strong>TOTAL PAID</strong></div>');
            $('#toggle_row').slideDown('slow');
            $('#search_by_report_type_holder').slideDown('slow');

            

        } else {

            $('#toggle_row').slideUp('slow');
            $('#search_by_report_type_holder').slideUp('slow');

            $('#captionHeaderFct').text('');
            $('#lastHeading').html('');
            $('#lastHeading').html('<div class="col-md-6 col-lg-6 text-right"><strong>PAID</strong></div><div class="col-md-6 col-lg-6 text-right"><strong>DUE</strong></div>');

        }
        
      });


      $('#search_by_report_type').change(function(ev) {

        if($(this).val() == 2) {

            //$('#termWeekHolder').slideDown('slow');

        } else {

            // $('#termWeekHolder').slideUp('slow');

        }
        
      });

      

      /*Toggle Stucents selection when Class changes*/
      $('#search_by_class').change(function(ev) {

        let student = $('#search_by_student').val();
        let bill_item = $('#search_by_bill_item').val();
            
        let bill_text = '';
        if(bill_item != '0') {
            bill_text = '(' + bill_item + ')';
        }

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
                    $('#captionHeader').html(bill_text + ' PAYMENTS REPORT GENERATED FOR ' + headingData + ' STUDENTS');
                    
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

      //bill item changed
      $('#search_by_bill_item').change(function(ev) {

            //update the heading
            let student_id = $('#search_by_student').val();
            let class_id = $('#search_by_class').val();

            let bill_item = $('#search_by_bill_item').val();
            
            let bill_text = '';
            if(bill_item != '0') {
                bill_text = '(' + bill_item + ')';
            }

            $.ajax({
                url: '<?php echo site_url('admin/getReportCaption/') ?>' + class_id + '/' + student_id,
                type: 'post',
                dataType: 'text',
            })
            .done(function(headingData) {
                if(student_id == 0) {
                    $('#captionHeader').html(bill_text + ' PAYMENTS REPORT GENERATED FOR ' + headingData + ' STUDENTS');
                } else {
                    //STUDENT SELECTED
                    $('#captionHeader').html(bill_text + ' PAYMENTS REPORT GENERATED FOR ' + headingData);
                }
                
                
            })
            .fail(function(error) {
                showAjaxModal_alert('Some data were not updated properly!<br>Please refresh the page and try again.', 'Error');
            });

            //submit the form
            $('#payments_load').submit();
      })


      //student changed
      $('#search_by_student').change(function(ev) {
            //update the heading
            let student_id = $('#search_by_student').val();
            let class_id = $('#search_by_class').val();

            let bill_item = $('#search_by_bill_item').val();
            
            let bill_text = '';
            if(bill_item != '0') {
                bill_text = '(' + bill_item + ')';
            }

            $.ajax({
                url: '<?php echo site_url('admin/getReportCaption/') ?>' + class_id + '/' + student_id,
                type: 'post',
                dataType: 'text',
            })
            .done(function(headingData) {
                if(student_id == 0) {
                    $('#captionHeader').html(bill_text + ' PAYMENTS REPORT GENERATED FOR ' + headingData + ' STUDENTS');
                } else {
                    //STUDENT SELECTED
                    $('#captionHeader').html(bill_text + ' PAYMENTS REPORT GENERATED FOR ' + headingData);
                }
                
                
            })
            .fail(function(error) {
                showAjaxModal_alert('Some data were not updated properly!<br>Please refresh the page and try again.', 'Error');
            });

            //submit the form
            $('#payments_load').submit();
      })
      
      //get selected date and update the period
      $('#start_date').change(function(ev) {
        const startVal = $(this).val();
        const endVal = $('#end_date').val();
        
        $.ajax({
            url: '<?php echo site_url('admin/getDate/'); ?>' + startVal,
            dataType: 'html',
            type: 'post',
        })
        .done(function(startDate) {
            // Get end date
            $.ajax({
                url: '<?php echo site_url('admin/getDate/'); ?>' + endVal,
                dataType: 'html',
                type: 'post',
            })
            .done(function(endDate) {
                $('#periodDisplay').html('<b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;">Period: From: ' + startDate + ' To: ' + endDate + '</b>');
            })
            .fail(function(err) {
                $('#periodDisplay').html('<b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;">Period: From: ' + startDate + ' To: ' + endDate + '</b>');
            });
        })
        .fail(function(err) {
            showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
        })
      });

      $('#end_date').change(function(ev) {
        const startVal = $('#start_date').val();
        const endVal = $(this).val();
        
        $.ajax({
            url: '<?php echo site_url('admin/getDate/'); ?>' + endVal,
            dataType: 'html',
            type: 'post',
        })
        .done(function(endDate) {
            // Get start date
            $.ajax({
                url: '<?php echo site_url('admin/getDate/'); ?>' + startVal,
                dataType: 'html',
                type: 'post',
            })
            .done(function(startDate) {
                $('#periodDisplay').html('<b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;">Period: From: ' + startDate + ' To: ' + endDate + '</b>');
            })
            .fail(function(err) {
                $('#periodDisplay').html('<b style="font-size: 11px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important;">Period: From: ' + startDate + ' To: ' + endDate + '</b>');
            });
        })
        .fail(function(err) {
            showAjaxModal_alert('Date selection failed!<br>Please try again.', 'Error');
        })
      });

      //Load data here
      $('#payments_load').submit(function(ev) {
        ev.preventDefault();

        //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

        let category = $('#search_by_category').val();
        let class_id = $('#search_by_class').val();
        let bill_item = $('#search_by_bill_item').val();

        let fct_type = $('#search_by_report_type').val();



        // if(category == 1 && class_id == 0) {
        //     showAjaxModal_alert('Action Terminated!<br>Please select a class.', 'Error');
        //     return false;
        // }
        if(category == 1 && (class_id == 0 || class_id == '0')) {
            $('#invoice_table thead').css('display', 'none');
            $('#captionHeader').html('PAYMENTS REPORT GENERATED BY CLASSES & BILL ITEMS');

        } else {
            $('#invoice_table thead').css('display', 'table-header-group');
        }
        showAjaxModal_alert('Fetching Data. Please wait... <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

        var residenceType = $('#search_by_residence_type').val();
        var categoryText = category == 0 ? 'ALL STUDENTS' : $('#search_by_class option:selected').text();
        var residenceText = residenceType == 0 ? '' : ' - ' + $('#search_by_residence_type option:selected').text();

            var dataUrl = (category == 1 && (class_id == 0 || class_id == '0')) ? '<?php echo site_url('admin/financial_reports/payments/load/load_pivot'); ?>' : '<?php echo site_url('admin/financial_reports/payments/load'); ?>';

            console.log('url used:' + dataUrl + ' category:' + category + ' class_id:' + class_id);

            $.ajax({
              url: dataUrl,
              type: 'POST',
              dataType: 'html',
              data: new FormData(this),
              cache: false,
              contentType: false,
              processData: false
            })
            .done(function(data) {

                // update the table caption
                $('#captionHeader').html('PAYMENTS REPORT GENERATED FOR ' + categoryText + ' ' + residenceText.toUpperCase());

                /*Display data-at the background*/
                if(fct_type == 2) {
                    //$('#invoice_table').css('display', 'none');
                    $('#invoice_table').slideUp('slow');
                    $('#fct_type_holder').slideDown('slow');
                    $('#fct_type_holder').html(data);
                } else {

                    $('#fct_type_holder').slideUp('slow');
                    $('#invoice_table').slideDown('slow');
                    $('#invoice_table tbody').html(data);
                }
                


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
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Error loading data!</div></center>', 'Error');
            });
      });

    });
    
    function dashboard() {
            location.href = '<?=site_url('admin/dashboard'); ?>';
        }
   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>' + $('#captionHeader').text() + '</title>');
        
        mywindow.document.write('<link rel="stylesheet" type="text/css" media="print" href="<?php echo base_url('assets/css/neon-forms.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" media="print" href="<<?php echo base_url('assets/css/neon-core.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" media="print" href="<?php echo base_url('assets/css/bootstrap.css');?>" />');

        mywindow.document.write('<style type="text\/css">.img-circle {width: 50% !important;}<\/style>');

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

    // Excel Export Function
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
        XLSX.utils.book_append_sheet(wb, ws, 'Payments Report');

        const filename = 'Payments_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx';
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

    // Popup Window Function with Comprehensive Landscape Print Styles
    function Popup(data) {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>Students Payments Report</title>');
        
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-forms.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/neon-core.css');?>" />');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/bootstrap.css');?>" />');

        // Add inline print styles to ensure landscape fills the page
        mywindow.document.write('<style type="text/css">');
        mywindow.document.write('@page { margin: 0.3cm; }');
        mywindow.document.write('* { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }');
        mywindow.document.write('html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; font-size: 9px !important; }');
        mywindow.document.write('page { display: block !important; width: 100% !important; }');
        mywindow.document.write('center { display: block !important; width: 100% !important; text-align: center !important; }');
        mywindow.document.write('.container { width: 100% !important; padding: 0 !important; margin: 0 !important; }');
        mywindow.document.write('.row { margin: 0 !important; width: 100% !important; }');
        mywindow.document.write('table { width: 100% !important; border-collapse: collapse !important; }');
        mywindow.document.write('#invoice_table { width: 100% !important; max-width: 100% !important; font-size: 9px !important; table-layout: fixed !important; }');
        mywindow.document.write('#invoice_table th, #invoice_table td { padding: 3px 4px !important; word-wrap: break-word !important; word-break: break-word !important; border: 1px solid #ccc !important; font-size: 9px !important; line-height: 1.2 !important; overflow-wrap: break-word !important; hyphens: auto !important; }');
        mywindow.document.write('#invoice_table thead { background: #f3f4f6 !important; }');
        mywindow.document.write('#invoice_table thead th { background: #f3f4f6 !important; color: #111827 !important; }');
        mywindow.document.write('#invoice_table th { background: #f3f4f6 !important; color: #111827 !important; font-weight: bold !important; font-size: 9px !important; padding: 4px 3px !important; white-space: normal !important; border: 1px solid #666 !important; }');
        mywindow.document.write('#invoice_table td { vertical-align: top !important; white-space: normal !important; }');
        mywindow.document.write('#invoice_table th:nth-child(1), #invoice_table td:nth-child(1) { width: 3% !important; text-align: center !important; font-size: 8px !important; }');
        mywindow.document.write('#invoice_table th:nth-child(2), #invoice_table td:nth-child(2) { width: 8% !important; font-size: 8px !important; }');
        mywindow.document.write('#invoice_table th:nth-child(3), #invoice_table td:nth-child(3) { width: 24% !important; font-size: 9px !important; max-width: 24% !important; }');
        mywindow.document.write('#invoice_table th:nth-child(4), #invoice_table td:nth-child(4) { width: 14% !important; font-size: 8px !important; max-width: 14% !important; }');
        mywindow.document.write('#invoice_table th:nth-child(5), #invoice_table td:nth-child(5) { width: 17% !important; font-size: 8px !important; max-width: 17% !important; }');
        mywindow.document.write('#invoice_table th:nth-child(6), #invoice_table td:nth-child(6) { width: 17% !important; text-align: right !important; font-size: 9px !important; white-space: nowrap !important; }');
        mywindow.document.write('#invoice_table th:nth-child(7), #invoice_table td:nth-child(7) { width: 17% !important; text-align: right !important; font-size: 9px !important; white-space: nowrap !important; }');
        mywindow.document.write('#top_row { width: 100% !important; display: table !important; margin-bottom: 8px !important; visibility: visible !important; }');
        mywindow.document.write('#top_row table { display: table !important; width: 100% !important; }');
        mywindow.document.write('#top_row thead { display: table-header-group !important; }');
        mywindow.document.write('#top_row tbody { display: table-row-group !important; }');
        mywindow.document.write('#top_row td { padding: 3px !important; font-size: 9px !important; display: table-cell !important; visibility: visible !important; }');
        mywindow.document.write('#top_row td strong { display: inline !important; visibility: visible !important; }');
        mywindow.document.write('#top_row span { display: inline !important; font-size: 9px !important; visibility: visible !important; }');
        mywindow.document.write('#top_row tr { display: table-row !important; visibility: visible !important; }');
        mywindow.document.write('#periodArea { display: none !important; }');
        mywindow.document.write('#dateInterval, #fromDate, #toDate { display: inline !important; visibility: visible !important; opacity: 1 !important; }');
        mywindow.document.write('#periodDisplay { display: inline !important; visibility: visible !important; font-size: 9px !important; }');
        mywindow.document.write('#dateInterval { display: inline !important; }');
        mywindow.document.write('h3, h4 { font-size: 11px !important; margin: 4px 0 !important; display: block !important; visibility: visible !important; }');
        mywindow.document.write('#top_row h3 { font-size: 11px !important; display: block !important; visibility: visible !important; font-weight: bold !important; }');
        mywindow.document.write('#top_row h4 { font-size: 9px !important; display: block !important; visibility: visible !important; }');
        mywindow.document.write('pre { display: block !important; visibility: visible !important; white-space: pre-wrap !important; font-family: inherit !important; margin: 0 !important; }');
        mywindow.document.write('strong { font-size: 9px !important; display: inline !important; visibility: visible !important; }');
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
</script>

