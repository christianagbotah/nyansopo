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
            <div class="row" style="margin-top: 40px; margin-bottom: 50px;" id="selectors_div">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <div class="col-sm-10 col-md-10 col-lg-10">
                        <?php echo form_open(site_url('admin/financial_reports/payments/load') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'payments_load'));?>

                            <style>
        .filters-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .filter-group label { display:block; font-weight:600; margin-bottom:8px; color:#111827; font-size:14px; }
        .modern-input { width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:14px; transition:all 0.3s; height:42px; color:#111827; font-weight:500; }
        .modern-input:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .btn-modern { padding:10px 20px; border-radius:10px; font-size:14px; font-weight:600; border:none; cursor:pointer; transition:all 0.3s; height:42px; display:inline-flex; align-items:center; gap:8px; }
        .btn-success-modern { background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
        .btn-success-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,0.4); }
        .btn-primary-modern { background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; box-shadow:0 4px 12px rgba(59,130,246,0.3); }
        .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(59,130,246,0.4); }
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
        @media print {
            @page { size: landscape; margin: 0.3cm; }
            * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
            html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
            #selectors_div { display:none !important; }
            #logo_ { width:80px !important; }
            .filters-card { display:none !important; }
            .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
            .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
            #invoice_table { width: 100% !important; max-width: none !important; font-size: 11px !important; }
            #invoice_table th, #invoice_table td { padding: 5px 6px !important; font-size: 11px !important; }
            #invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; }
        }
                                .filters-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; }
                                .filters-grid { display:grid; grid-template-columns:repeat(1,minmax(0,1fr)); gap:12px; }
                                @media (min-width: 640px) { .filters-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
                                @media (min-width: 1024px) { .filters-grid { grid-template-columns:repeat(4,minmax(0,1fr)); } }
                                .filter-group label { display:block; font-weight:600; margin-bottom:6px; color:#111827; }
                                .filter-actions { display:flex; align-items:center; gap:8px; margin-top:8px; }
                                .filter-actions .btn { min-width:120px; }
                            </style>

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
                                <div class="filters-grid">
                                    <div class="filter-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="text" name="start_date" id="start_date" class="form-control datepicker modern-input" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>" style="font-size: 14px; font-weight: 600; color: #111;">
                                    </div>

                                    <div class="filter-group">
                                        <label for="end_date">End Date</label>
                                        <input type="text" name="end_date" id="end_date" class="form-control datepicker modern-input" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y'); ?>" style="font-size: 14px; font-weight: 600; color: #111;">
                                        <div id="desc"></div>
                                    </div>

                                    <div class="filter-group">
                                        <label for="search_by_type">Type</label>
                                        <select class="form-control modern-input" name="search_by_type" id="search_by_type">
                                            <option value="1">Billed Invoice</option>
                                            <option value="2">Daily Fees (Feeding, Breakfast, Classes, Water, Transport)</option>
                                        </select>
                                    </div>

                                    <input type="hidden" name="boarding_system" value="<?=$boarding_system;?>">
                                    <?php if($boarding_system == 'yes'):?>
                                    <div class="filter-group">
                                        <label for="search_by_residence_type">Residence Type</label>
                                        <select class="form-control modern-input" name="search_by_residence_type" id="search_by_residence_type">
                                            <option value="0">All</option>
                                            <option value="Day">Day Students</option>
                                            <option value="Boarding">Boarding Students</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>

                                    <div class="filter-group">
                                        <label for="search_by_payment_method">Payment Method</label>
                                        <select class="form-control modern-input" name="search_by_payment_method" id="search_by_payment_method">
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
                                        <label for="search_by_bill_item">Bill Item Selection</label>
                                        <select class="form-control modern-input" name="search_by_bill_item" id="search_by_bill_item">
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
                                        <label for="search_by_category">Category of Selection</label>
                                        <select class="form-control modern-input" name="search_by_category" id="search_by_category">
                                            <option value="0" selected>All Students</option>
                                            <option value="1">Filter By Class</option>
                                        </select>
                                    </div>
                                    <div class="filter-group" id="toggle_class" style="display: none;">
                                        <label for="search_by_class"><strong>Filter By Class</strong></label>
                                        <select class="form-control modern-input" name="search_by_class" id="search_by_class">
                                            <option value="0">All Classes</option>
                                            <?php getFullClassList(); ?>
                                        </select>
                                    </div>
                                    <div class="filter-group" id="toggle_selected_class" style="display: none;">
                                        <label for="search_by_student"><strong>Students In Selected Class</strong></label>
                                        <select class="form-control boxit" name="search_by_student" id="search_by_student">
                                            <option value="0">Please select class first</option>
                                        </select>
                                    </div> 
                                </div>
                                <div class="filters-secondary">
                                        <div id="search_by_report_type_holder" style="display: none;">
                                            <label for="search_by_report_type"><strong>Report Type</strong></label>
                                            <select class="form-control modern-input" name="search_by_report_type" id="search_by_report_type">
                                                <option value="1" selected>By General Dates</option>
                                                <option value="2">By Weekly</option>
                                            </select>
                                        </div>

                                        <div id="termWeekHolder" style="display: none">
                                            <div class="filters-secondary">
                                                <div>
                                                    <label for="term"><strong>Select Term</strong></label>
                                                    <select class="form-control modern-input" name="term" id="term">
                                                        <?php
                                                            for($i = 1; $i <= 3; $i++) {
                                                                ?>
                                                                <option value="<?=$i;?>" <?=$i == get_settings('running_term') ? 'selected' : ''; ?>><?=$i;?></option>
                                                                <?php
                                                            }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="filters-secondary">
                                                    <label for="year"><strong>Select Year</strong></label>
                                                    <select class="form-control modern-input" name="year" id="year">
                                                        <option value="" selected>Select year</option>
                                                        <?php echo populate_academic_year('yes'); ?>
                                                    </select>
                                                </div>

                                                <div class="filters-secondary">
                                                    <label for="week"><strong>Select Week</strong></label>
                                                    <select class="form-control modern-input" name="week" id="week">
                                                        <option value="1">Week 1</option>
                                                        <option value="2">Week 2</option>
                                                        <option value="3">Week 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <div class="filter-actions float-right" style="position: sticky; right: 0px; top: -10px;">
                                    <input class="btn btn-success" type="submit" style="font-size: 20px" value="Load" name="load_payments"/>
                                </div>
                            </div>

                            <style>
        .filters-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .filter-group label { display:block; font-weight:600; margin-bottom:8px; color:#111827; font-size:14px; }
        .modern-input { width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:14px; transition:all 0.3s; height:42px; color:#111827; font-weight:500; }
        .modern-input:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .btn-modern { padding:10px 20px; border-radius:10px; font-size:14px; font-weight:600; border:none; cursor:pointer; transition:all 0.3s; height:42px; display:inline-flex; align-items:center; gap:8px; }
        .btn-success-modern { background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
        .btn-success-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,0.4); }
        .btn-primary-modern { background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; box-shadow:0 4px 12px rgba(59,130,246,0.3); }
        .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(59,130,246,0.4); }
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
        @media print {
            @page { size: landscape; margin: 0.3cm; }
            * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
            html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
            #selectors_div { display:none !important; }
            #logo_ { width:80px !important; }
            .filters-card { display:none !important; }
            .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
            .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
            #invoice_table { width: 100% !important; max-width: none !important; font-size: 11px !important; }
            #invoice_table th, #invoice_table td { padding: 5px 6px !important; font-size: 11px !important; }
            #invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; }
        }
                                .filters-secondary { display:grid; grid-template-columns:repeat(1,minmax(0,1fr)); gap:12px; }
                                @media (min-width: 640px) { .filters-secondary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
                                @media (min-width: 1024px) { .filters-secondary { grid-template-columns:repeat(3,minmax(0,1fr)); } }
                                .action-bar { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; align-items:center; }
                                .action-bar .btn { min-width:140px; }
                            </style>

                            <div class="form-group row" id="toggle_row" style="display: none;">

                               <div class="col-sm-7 col-md-7 col-lg-7 grid grid-cols-4 gap-2 mb-3">

                                    <!-- Select a fct report type -->
                                    

                               </div>

                                <div class="col-sm-5 col-md-5 col-lg-5">
                                    <div class="form-group row">
                                         
                                    </div>
                               </div>
                           </div>

                        </form>
                    </div>
                    
                </div>   

                <hr>
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


                        ?>

                       

                        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
                        <!--Chartjs-->
                        <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>
                        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>

                        <style type="text/css">
        .filters-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .filter-group label { display:block; font-weight:600; margin-bottom:8px; color:#111827; font-size:14px; }
        .modern-input { width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:14px; transition:all 0.3s; height:42px; color:#111827; font-weight:500; }
        .modern-input:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .btn-modern { padding:10px 20px; border-radius:10px; font-size:14px; font-weight:600; border:none; cursor:pointer; transition:all 0.3s; height:42px; display:inline-flex; align-items:center; gap:8px; }
        .btn-success-modern { background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
        .btn-success-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,0.4); }
        .btn-primary-modern { background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; box-shadow:0 4px 12px rgba(59,130,246,0.3); }
        .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(59,130,246,0.4); }
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
        @media print {
            @page { size: landscape; margin: 0.3cm; }
            * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
            html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
            #selectors_div { display:none !important; }
            #logo_ { width:80px !important; }
            .filters-card { display:none !important; }
            .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
            .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
            #invoice_table { width: 100% !important; max-width: none !important; font-size: 11px !important; }
            #invoice_table th, #invoice_table td { padding: 5px 6px !important; font-size: 11px !important; }
            #invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; }
        }

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

                                table {
                                    width: 130% !important;
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
                                        <td width="50%">
                                           <table class="table" style="margin-top: -10px; border-collapse:collapse;">
                                                <thead>
                                                    <tr>
                                                    <td>
                                                       <img class="" id="logo_" src="<?php echo base_url(); ?>uploads/school_logo.png" width="110px"> 
                                                    </td>
                                                    <td>
                                                        <h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px;"><?php echo $system_name;?></h3>
                                                        <pre>
                                                            <h4 style="font-family: Nunito" align='left'><?php echo trim($address);?></h4>
                                                        </pre>
                                                    </td> 
                                                    </tr>
                                                    <tr style="border-top: 2px solid #1b1b69;">
                                                        <td colspan="2"></td>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </td>

                    
                                        <td width="50%">
                                            <table class="table" style="width: 100%; margin-top: -15px; border-collapse:collapse;">
                                                <thead>
                                                    <tr>
                                                        <td align="right" colspan="2"><h3 style="font-weight: bold; font-size: 20px; letter-spacing: 2px;">STUDENTS PAYMENT REPORTS</h3></td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right">Period: </td>
                                                        <td align="right"><strong>
                                                            <span id="periodArea" style="display: none">AS AT TODAY</span>

                                                            <span id="dateInterval">
                                                                From: <span id="fromDate"><?=date('M, d Y', $date_start); ?></span> To: <span id="toDate"><?=date('M, d Y', $date_end); ?>
                                                        </span>
                                                            </span>
                                                            
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
                                                <th style="text-align: left"><strong>PAYMENT METHOD</strong></th>
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


<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>

<script type="text/javascript">
function exportToExcel() {
    const table = document.getElementById('invoice_table');
    const tbody = table.querySelector('tbody');
    
    // Check if data is loaded
    if (!tbody || tbody.querySelector('td[colspan]') || tbody.children.length === 0) {
        showAjaxModal_alert('No data available to export. Please load data first.', 'warning');
        return;
    }

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(wb, ws, 'Students Payments Report');

    const filename = 'Payments_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx';
    XLSX.writeFile(wb, filename);
}

// Enhanced print function with validation
function PrintElem(elem) {
    const table = document.getElementById('invoice_table');
    const tbody = table.querySelector('tbody');
    
    // Check if data is loaded
    if (!tbody || tbody.querySelector('td[colspan]') || tbody.children.length === 0) {
        showAjaxModal_alert('No data available to print. Please load data first.', 'warning');
        return;
    }
    
    Popup($(elem).html());
}

function Popup(data) {
    var mywindow = window.open('', '', '');
    mywindow.document.write('<!doctype html><html><head><title>Students Payments Report</title>');
    
    mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url(\'assets/css/neon-forms.css\');?>" />');
    mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url(\'assets/css/neon-core.css\');?>" />');
    mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url(\'assets/css/bootstrap.css\');?>" />');

    // Add inline print styles
    mywindow.document.write('<style type="text/css">');
    mywindow.document.write('@page { size: landscape; margin: 0.3cm; }');
    mywindow.document.write('* { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }');
    mywindow.document.write('html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; }');
    mywindow.document.write('#invoice_table { width: 100% !important; font-size: 11px !important; }');
    mywindow.document.write('#invoice_table th, #invoice_table td { padding: 5px 6px !important; font-size: 11px !important; }');
    mywindow.document.write('#invoice_table th { background: #3b82f6 !important; color: white !important; }');
    mywindow.document.write('#logo_ { width: 80px !important; }');
    mywindow.document.write('</style>');

    mywindow.document.write('</head><body style="font-size: 13px;">');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');
    mywindow.document.close();

    mywindow.onload = function() {
        mywindow.focus();
        mywindow.print();
        mywindow.close();
    }
}

function dashboard() {
    location.href = '<?php echo site_url(\'admin/dashboard\'); ?>';
}
</script>
</body>
</html>    
    
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

        mywindow.document.write('<style type="text\/css">
        .filters-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .filter-group label { display:block; font-weight:600; margin-bottom:8px; color:#111827; font-size:14px; }
        .modern-input { width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:14px; transition:all 0.3s; height:42px; color:#111827; font-weight:500; }
        .modern-input:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .btn-modern { padding:10px 20px; border-radius:10px; font-size:14px; font-weight:600; border:none; cursor:pointer; transition:all 0.3s; height:42px; display:inline-flex; align-items:center; gap:8px; }
        .btn-success-modern { background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; box-shadow:0 4px 12px rgba(16,185,129,0.3); }
        .btn-success-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,0.4); }
        .btn-primary-modern { background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; box-shadow:0 4px 12px rgba(59,130,246,0.3); }
        .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(59,130,246,0.4); }
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
        @media print {
            @page { size: landscape; margin: 0.3cm; }
            * { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
            html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
            #selectors_div { display:none !important; }
            #logo_ { width:80px !important; }
            .filters-card { display:none !important; }
            .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
            .row { margin: 0 !important; width: 100% !important; max-width: none !important; }
            #invoice_table { width: 100% !important; max-width: none !important; font-size: 11px !important; }
            #invoice_table th, #invoice_table td { padding: 5px 6px !important; font-size: 11px !important; }
            #invoice_table th { background: #3b82f6 !important; color: white !important; font-weight: bold !important; }
        }.img-circle {width: 50% !important;}<\/style>');

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

