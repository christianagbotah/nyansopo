<?php


$class_id = $this->db->get_where('invoice', array('invoice_code' => $param2))->row()->class_id;
$class_name = $this->crud_model->get_class_name($class_id);


if($class_name == 'JHSS') {
  
    //using semesters

$invoice_data = $this->db->get_where('invoice', array('invoice_code' => $param2));
$edit_data = $invoice_data->result_array();

//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$next_sem_begins = $this->db->get_where('settings', array('type' => 'next_sem_begins'))->row()->description;
$invoice_due_period = $this->db->get_where('settings', array('type' => 'invoice_due'))->row()->description;
$total_invoice_duration = 86400 * $invoice_due_period;

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
?>
<div class=" row col-md-12">
    <a onClick="PrintElem('#invoice_print2')" id="print_btn" class="btn btn-default btn-icon icon-left hidden-print pull-right">
        Print Invoice
        <i class="entypo-print"></i>
    </a>

    <style type="text/css">

        #invoice_det th {
            background-color: grey;
            border: 2px solid #e2dddd;
            padding: 5px;
            color: white;
            border-bottom: 2px solid red;
         }

        @media Print{

        page {
        background: #ffffff;
        display: block;
        margin: 0 auto;
        margin-bottom: 0.5cm;
        box-shadow: 0;

        }

        page[size="A4"] {
            width: 14.85cm;
            height: 21cm;
        }

        body, page {
            margin: 0;
            box-shadow: 0;
        }

        table tbody tr td strong {
            /*font-size: 11px !important;*/
        }
    }

    </style>

</div><br><br>

    <hr><hr>

    <br>
    <div id="invoice_print1"></div>
    <div id="invoice_print2">
        <page size="A4">

                <table width="100%" border="0" id="invoice_det">
                    <tr>
                        <td align="left">
                             <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 80px;"><br>
                        </td>

                        <td align="left" valign="top">
                            <h4><?php echo get_phrase('payment_to'); ?> </h4>
                            <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?><br>
                            <?php echo $this->db->get_where('settings', array('type' => 'address'))->row()->description; ?><br>
                            <?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?><br>
                            <?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?><br>            
                        </td>

                            <!--check the status of this invoice code-->
                            <?php 
                                $status_counter = 0;
                                $part_payment = 0;
                                foreach($edit_data as $status_row) {
                                    /*if($status_row['status'] == 'unpaid') {
                                        $status_counter++;
                                    }

                                    if($status_row['status'] == 'partially paid') {
                                        $part_payment++;
                                    } */
                                }

                                /*
                                if($status_counter > 0) {
                                    $inv_status = 'Unpaid';
                                } else {
                                    if($part_payment > 0) {
                                        $inv_status = 'Partially Paid';
                                    } else {
                                        $inv_status = 'Paid';
                                    }
                                } **/

                                $inv_status = ucwords($this->db->get_where('invoice', array('invoice_code' => $param2))->row()->status);

                                $param3 = $this->db->get_where('invoice', array('invoice_code' => $param2))->row()->year;

                                $param4 = $this->db->get_where('invoice', array('invoice_code' => $param2))->row()->sem;
                            ?>

                        <td align="right">
                            <h4><?php echo get_phrase('bill_to'); ?> </h4>
                            <?php echo $this->db->get_where('student', array('student_id' => $status_row['student_id']))->row()->name; ?><br>
                    <?php 
                        $class_id = $this->db->get_where('enroll' , array(
                            'student_id' => $status_row['student_id'],
                                'year' => $param3, 'sem' => $param4
                        ))->row()->class_id;
                        //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class', array('class_id' => $class_id))->row()->name, 'name_numeric' => $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }

                            $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                        echo $class_name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric.$sec_name;?><br>

                        </td>
                    </tr>
                </table>

                <hr>

                <table width="100%" border="0">
                    <tr>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('invoice_#:'); ?> : <?php echo $param2;?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('creation_date'); ?> : <?php echo date('d M, Y', $invoice_data->row()->creation_timestamp);?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('status'); ?> : <?php echo $inv_status; ?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('year'); ?> : <?php echo explode('-', $param3)[1]; ?> | <?php echo get_phrase('semester'); ?> : <?php echo $param4; ?></strong></td>

                    </tr>
                </table>
        
        <hr>

        <!--Invoice Details here--->
        <table width="100%" class="table table-bordered table-hover table-striped table-active" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;">
            <thead>
                <tr>
                   <!-- <th style="text-align: left;"><?php echo get_phrase('title'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('description'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('amount'); ?></th>-->
                    <th style="text-align: left;"><?php echo get_phrase('description'); ?></th>
                    <th style="text-align: right;"><?php echo get_phrase('amount'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_amount = 0;
                $total_amount_paid = 0;
                $total_balance = 0;

                $inv_ids = array();

                 foreach ($edit_data as $row):
                    array_push($inv_ids, $row['invoice_id']);
                ?>
   
                <tr>
                    <td><?php echo $row['title'];?></td>
                   <!-- <td><?php echo $row['description'];?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount_paid'], $currency);?></td> -->
                    <td width="200" class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency); ?></td>
                </tr>

                <?php 
                    $total_amount += $row['amount']; 
                    $total_amount_paid += $row['amount_paid']; 
                    $total_balance += $row['due']; 
            endforeach;?>
            </tbody>

            <?php
            // Check for discounts
            $discount_query = $this->db->get_where('invoice_discounts', array(
                'invoice_code' => $param2,
                'status' => 'approved'
            ));
            $total_discount = 0;
            if($discount_query->num_rows() > 0) {
                foreach($discount_query->result_array() as $disc) {
                    $total_discount += $disc['discount_amount'];
                }
            }
            ?>

            <!--SHOW TOTAL -->
            <tr>
                <td><strong><?php echo get_phrase('total_amount'); ?></strong></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></strong></td>
            </tr>
            <?php if($total_discount > 0): ?>
            <tr style="color: #28a745;">
                <td><strong><?php echo get_phrase('discount'); ?></strong></td>
                <td align="right"><strong>- <?php echo numfmt_format_currency($fmt, $total_discount, $currency); ?></strong></td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td><strong><?php echo get_phrase('amount_after_discount'); ?></strong></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_amount - $total_discount, $currency); ?></strong></td>
            </tr>
            <?php endif; ?>
        </table><br>


        <hr>
        <!--Accounts description here-->
        <?php

        

            $c_f = $this->db->get_where('class', array('class_id' => $class_id))->row();
            $feeding_fee = $c_f->feeding_fee; //per day
            $classes_fee = $c_f->classes_fee; //per day
        ?>

        <!--
        <h4>Feeding Fee is <?=numfmt_format_currency($fmt, $feeding_fee * 5, $currency); ?> per Week. </h4>
        <h4><em>Cash Payments Accepted. Otherwise pay into our bank accounts with details below;</em></h4>

        <table class="table" width="100%" border="0" style="border-collapse:collapse;">
            <thead></thead>
            <tbody>
                <?php
                    $this->db->where('account_is_bank', 1);
                    $this->db->order_by('account_id', 'asc');
                    $this->db->limit(1);
                    $main_account = $this->db->get('accounts')->row();
                ?>

                <tr>
                    <td>Bank</td>
                    <td><strong><?=$main_account->bank_name; ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Branch</td>
                    <td><strong><?=$main_account->branch; ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Account Name</td>
                    <td><strong><?=$main_account->account_name; ?></strong></td>
                </tr>

                <tr>
                    <td>Account Number</td>
                    <td><strong><?=$main_account->account_number; ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <hr>

        <ul>
            <?php 
                $half_payment = $this->db->get_where('settings' , array('type'=>'half_payment_week'))->row()->description;
                
                if($class_name == 'JHSS') {
                    $full_payment = $this->db->get_where('sems' , array('year' => $param3, 'sem' => $param4))->row()->full_payment_date;
                } else {
                    $full_payment = $this->db->get_where('terms' , array('year' => $param3, 'term' => $param4))->row()->full_payment_date;
                }

                if($full_payment == '') {
                    $full_payment_date = '';

                } else {
                    $full_payment_date = date('l M d, Y', (strtotime($full_payment)));
                }

                ?>

            <li>Part (Half) Payment of school fees is expected by <strong><?=substr($half_payment, 0, -4);?> Semester</strong></li>
            <li>Full Payment of school fees is expected by <strong><?=$full_payment_date; ?></strong></li>
            <li>Next Semester Begins on <strong><?=date('l M d, Y', strtotime($next_sem_begins)); ?></strong></li>
        </ul>-->

        <div style="text-align: right">
            <p>..........................................</p>
            <p>For: Finance Department</p>
        </div>
        <div style="text-align: center;">
            <strong>Thank You For Choosing <?=ucwords(strtolower($system_name)); ?></strong>
            <p style="color: #6f686c; margin-top: 5px">Software Developed By: Lightworldtech-0243618186</p>
        </div>
    </page>
    </div>
    <hr>


        <!-- payment history -->
        <!--<h4><?php echo get_phrase('payment_history'); ?></h4>
        <table class="table table-bordered" width="100%" border="1" style="border-collapse:collapse;" id="payment_ta">
            <thead>
                <tr>
                    <th style="text-align: left;"><?php echo get_phrase('date'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('amount_paid'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('method'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($edit_data as $row3):
            
                $payment_history = $this->db->get_where('payment', array('invoice_id' => $row3['invoice_id']))->result_array();
                foreach ($payment_history as $row2):
                    ?>
                    <tr>
                        <td><?php echo date("d M, Y", $row2['timestamp']); ?></td>
                        <td><?php echo numfmt_format_currency($fmt, $row2['amount'], $currency); ?></td>
                        <td>
                            <?php 
                                if ($row2['method'] == 1)
                                    echo get_phrase('cash');
                                if ($row2['method'] == 2)
                                    echo get_phrase('cheque');
                                if ($row2['method'] == 3)
                                    echo get_phrase('card');
                                if ($row2['method'] == 4)
                                    echo 'Mobile Money';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            <tbody>
        </table>-->

        <?php
} else {

    //using terms
$invoice_data = $this->db->get_where('invoice', array('invoice_code' => $param2));
$edit_data = $invoice_data->result_array();

//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$next_term_begins = $this->db->get_where('settings', array('type' => 'next_term_begins'))->row()->description;
$invoice_due_period = $this->db->get_where('settings', array('type' => 'invoice_due'))->row()->description;
$total_invoice_duration = 86400 * $invoice_due_period;

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
?>
<div class=" row col-md-12">
    <a onClick="PrintElem('#invoice_print2')" id="print_btn" class="btn btn-default btn-icon icon-left hidden-print pull-right">
        Print Invoice
        <i class="entypo-print"></i>
    </a>

    <style type="text/css">

        th {
            background-color: grey;
            border: 2px solid #e2dddd;
            padding: 5px;
            color: white;
            border-bottom: 2px solid red;
         }

        @media Print{

        page {
        background: #ffffff;
        display: block;
        margin: 0 auto;
        margin-bottom: 0.5cm;
        box-shadow: 0;

        }

        page[size="A4"] {
            width: 14.85cm;
            height: 21cm;
        }

        body, page {
            margin: 0;
            box-shadow: 0;
        }

        table tbody tr td strong {
            /*font-size: 11px !important;*/
        }
    }

    </style>

</div><br><br>

    <hr><hr>

    <br>
    <div id="invoice_print1"></div>
    <div id="invoice_print2">
        <page size="A4">

                <table width="100%" border="0" id="invoice_det">
                    <tr>
                        <td align="left">
                             <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 80px;"><br>
                        </td>

                        <td align="left" valign="top">
                            <h4><?php echo get_phrase('payment_to'); ?> </h4>
                            <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?><br>
                            <?php echo $this->db->get_where('settings', array('type' => 'address'))->row()->description; ?><br>
                            <?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?><br>
                            <?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?><br>            
                        </td>

                            <!--check the status of this invoice code-->
                            <?php 
                                $status_counter = 0;
                                $part_payment = 0;
                                foreach($edit_data as $status_row) {
                                    /*if($status_row['status'] == 'unpaid') {
                                        $status_counter++;
                                    }

                                    if($status_row['status'] == 'partially paid') {
                                        $part_payment++;
                                    } */
                                }

                                /*
                                if($status_counter > 0) {
                                    $inv_status = 'Unpaid';
                                } else {
                                    if($part_payment > 0) {
                                        $inv_status = 'Partially Paid';
                                    } else {
                                        $inv_status = 'Paid';
                                    }
                                } **/

                                $inv_status = ucwords($this->db->get_where('invoice', array('invoice_code' => $param2))->row()->status);
                                $param3 = $this->db->get_where('invoice', array('invoice_code' => $param2))->row()->year;

                                $param4 = $this->db->get_where('invoice', array('invoice_code' => $param2))->row()->term;
                            ?>

                        <td align="right">
                            <h4><?php echo get_phrase('bill_to'); ?> </h4>
                            <?php echo $this->db->get_where('student', array('student_id' => $status_row['student_id']))->row()->name; ?><br>
                    <?php 
                        $this->db->order_by('enroll_id', 'desc');
                        $this->db->limit(1);
                        $class_id = $this->db->get_where('enroll' , array(
                            'student_id' => $status_row['student_id']))->row()->class_id;
                        //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class', array('class_id' => $class_id))->row()->name, 'name_numeric' => $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }

                            $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                        echo $class_name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric.$sec_name;?><br>

                        </td>
                    </tr>
                </table>

                <hr>

                <table width="100%" border="0">
                    <tr>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('invoice_#:'); ?> : <?php echo $param2;?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('creation_date'); ?> : <?php echo date('d M, Y', $invoice_data->row()->creation_timestamp);?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('status'); ?> : <?php echo $inv_status; ?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding-right: 5px;"><strong><?php echo get_phrase('year'); ?> : <?php echo explode('-', $param3)[1]; ?> | <?php echo get_phrase('term'); ?> : <?php echo $param4; ?></strong></td>

                    </tr>
                </table>
        

        <hr>

        <!--Invoice Details here--->
        <table width="100%" class="table table-bordered table-hover table-striped table-active" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;">
            <thead>
                <tr>
                   <!-- <th style="text-align: left;"><?php echo get_phrase('title'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('description'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('amount'); ?></th>-->
                    <th style="text-align: left;"><?php echo get_phrase('description'); ?></th>
                    <th style="text-align: right;"><?php echo get_phrase('amount'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_amount = 0;
                $total_amount_paid = 0;
                $total_balance = 0;

                $inv_ids = array();

                 foreach ($edit_data as $row):
                    array_push($inv_ids, $row['invoice_id']);
                ?>
   
                <tr>
                    <td><?php echo $row['title'];?></td>
                   <!-- <td><?php echo $row['description'];?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount_paid'], $currency);?></td> -->
                    <td width="200" class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency); ?></td>
                </tr>

                <?php 
                    $total_amount += $row['amount']; 
                    $total_amount_paid += $row['amount_paid']; 
                    $total_balance += $row['due']; 
            endforeach;?>
            </tbody>

            <?php
            // Check for discounts
            $discount_query = $this->db->get_where('invoice_discounts', array(
                'invoice_code' => $param2,
                'status' => 'approved'
            ));
            $total_discount = 0;
            if($discount_query->num_rows() > 0) {
                foreach($discount_query->result_array() as $disc) {
                    $total_discount += $disc['discount_amount'];
                }
            }
            ?>

            <!--SHOW TOTAL -->
            <tr>
                <td><strong><?php echo get_phrase('total_amount'); ?></strong></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></strong></td>
            </tr>
            <?php if($total_discount > 0): ?>
            <tr style="color: #28a745;">
                <td><strong><?php echo get_phrase('discount'); ?></strong></td>
                <td align="right"><strong>- <?php echo numfmt_format_currency($fmt, $total_discount, $currency); ?></strong></td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td><strong><?php echo get_phrase('amount_after_discount'); ?></strong></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_amount - $total_discount, $currency); ?></strong></td>
            </tr>
            <?php endif; ?>
        </table><br>

<!--
        <table width="100%" border="0" class="table" cellspacing="0" cellpadding="0" style="line-height: 1px;">    
            <tr>
                <td align="right" width="80%"><h5><?php echo get_phrase('total_amount'); ?> :</h5></td>
                <td align="right"><h5><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></h5></td>
            </tr>
            <tr>
                <td align="right" width="80%"><h5><?php echo get_phrase('total_amount_paid'); ?> :</h5></td>
                <td align="right"><h5><?php echo numfmt_format_currency($fmt, $total_amount_paid, $currency); ?></h5></td>
            </tr>
            <tr>
                <td align="right" width="80%"><h5><?php echo get_phrase('total_arrears'); ?> :</h5></td>
                <td align="right"><h5 class="total_balance"><?php echo numfmt_format_currency($fmt, $total_balance, $currency); ?></h5></td>
            </tr>
            
        </table> -->

        <hr>
        <!--Accounts description here-->
        <?php

            $c_f = $this->db->get_where('class', array('class_id' => $class_id))->row();
            $feeding_fee = $c_f->feeding_fee; //per day
            $classes_fee = $c_f->classes_fee; //per day


        ?>

        <!--
        <h4>Feeding Fee is <?=numfmt_format_currency($fmt, $feeding_fee * 5, $currency); ?> per Week. </h4>
        <h4><em>Cash Payments Accepted. Otherwise pay into our bank accounts with details below;</em></h4>

        <table class="table" width="100%" border="0" style="border-collapse:collapse;">
            <thead></thead>
            <tbody>
                <?php
                    $this->db->where('account_is_bank', 1);
                    $this->db->order_by('account_id', 'asc');
                    $this->db->limit(1);
                    $main_account = $this->db->get('accounts')->row();
                ?>

                <tr>
                    <td>Bank</td>
                    <td><strong><?=$main_account->bank_name; ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Branch</td>
                    <td><strong><?=$main_account->branch; ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Account Name</td>
                    <td><strong><?=$main_account->account_name; ?></strong></td>
                </tr>

                <tr>
                    <td>Account Number</td>
                    <td><strong><?=$main_account->account_number; ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <hr>

        <ul>
            <?php 
                $half_payment = $this->db->get_where('settings' , array('type'=>'half_payment_week'))->row()->description;
                $full_payment = $this->db->get_where('settings' , array('type'=>'full_payment_date'))->row()->description;

                if($full_payment == '') {
                    $full_payment_date = '';

                } else {
                    $full_payment_date = date('l M d, Y', (strtotime($full_payment)));
                }

                ?>

            <li>Part (Half) Payment of school fees is expected by <strong><?=$half_payment;?></strong></li>
            <li>Full Payment of school fees is expected by <strong><?=$full_payment_date; ?></strong></li>
            <li>Next Term Begins on <strong><?=date('l M d, Y', strtotime($next_term_begins)); ?></strong></li>
        </ul>-->

        <div style="text-align: right">
            <p>..........................................</p>
            <p>For: Finance Department</p>
        </div>
        <div style="text-align: center;">
            <strong>Thank You For Choosing <?=ucwords(strtolower($system_name)); ?></strong>
            <p style="color: #6f686c; margin-top: 5px">Software Developed By: Lightworldtech-0243618186</p>
        </div>
    </page>
    </div>
    <hr>


        <!-- payment history -->
        <!--<h4><?php echo get_phrase('payment_history'); ?></h4>
        <table class="table table-bordered" width="100%" border="1" style="border-collapse:collapse;" id="payment_ta">
            <thead>
                <tr>
                    <th style="text-align: left;"><?php echo get_phrase('date'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('amount_paid'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('method'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($edit_data as $row3):
            
                $payment_history = $this->db->get_where('payment', array('invoice_id' => $row3['invoice_id']))->result_array();
                foreach ($payment_history as $row2):
                    ?>
                    <tr>
                        <td><?php echo date("d M, Y", $row2['timestamp']); ?></td>
                        <td><?php echo numfmt_format_currency($fmt, $row2['amount'], $currency); ?></td>
                        <td>
                            <?php 
                                if ($row2['method'] == 1)
                                    echo get_phrase('cash');
                                if ($row2['method'] == 2)
                                    echo get_phrase('cheque');
                                if ($row2['method'] == 3)
                                    echo get_phrase('card');
                                if ($row2['method'] == 4)
                                    echo 'Mobile Money';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            <tbody>
        </table>-->

        <?php
}

?>



<script type="text/javascript">

    $(function() {
        $('#payment_ta').dataTable();
     });

    $(document).ready(function() {
        var due = '<?php echo $row['due']; ?>';

            if(due <= 0) {
            $('.due_amount').css('color', 'green');
            }else{
                $('.due_amount').css('color', 'red');
            }

        

        var total_balance = '<?php echo $total_balance; ?>';
        if(total_balance <= 0) {
            $('.total_balance').css('color', 'green');
        }else{
            $('.total_balance').css('color', 'red');
        }
        
    });

    // print invoice function
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'invoice', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Invoice</title><style>table th, table td {font-size: 12px; }</style>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<\/head><body style="font-size: 12px !important">');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        var is_chrome = Boolean(mywindow.chrome);
        if (is_chrome) {
            setTimeout(function() {
                mywindow.print();
                mywindow.close();

                return true;
            }, 250);
        }
        else {
            mywindow.print();
            mywindow.close();

            return true;
        }

        return true;
    }

</script>