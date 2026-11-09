<!doctype html>
<html>
    <head>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>


        <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.css');?>">



    </head>
    <body style="font-size: 16px;">
<?php
//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$next_term_begins = $this->db->get_where('settings', array('type' => 'next_term_begins'))->row()->description;
$next_sem_begins = $this->db->get_where('settings', array('type' => 'next_sem_begins'))->row()->description;
$invoice_due_period = $this->db->get_where('settings', array('type' => 'invoice_due'))->row()->description;
$total_invoice_duration = 86400 * $invoice_due_period;

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


?>
<div class="container">
    <div class=" row col-md-12">
        <div class="row" style="margin-top: 15px; position: fixed; z-index: 9999" id="print_div">
                <a href="javascript:void(0);" onClick="page_reload()" class="btn btn-default"><i class="entypo-print"></i>Print Bulk Invoice</a>
        </div>



</div><br><br>

    <hr><hr>

    <br>

    <?php 

        $class_name = $this->crud_model->get_class_name($class_id);

                        $this->db->select('student_id');
                        $this->db->distinct();
                        $this->db->where('class_id', $class_id);
                        $this->db->where('mute', '0');
                        $this->db->where('year', $year);

                        if($class_name == 'JHSS') {
                            $this->db->where('sem', $sem);
                        } else {
                            $this->db->where('term', $term);
                        }
                        
                        
                        $this->db->from('enroll');
                        $students_rolls = $this->db->get()->result_array();
                        
                        ?>
    <div id="print_body">

        <?php

        foreach($students_rolls as $student_row):

                            $this->db->where('student_id', $student_row['student_id']);
                            $this->db->where('year', $year);

                            if($class_name == 'JHSS') {
                                $this->db->where('sem', $sem);
                            } else {
                                $this->db->where('term', $term);
                            }
                            $this->db->where('can_delete !=', 'trash');
                            $invoice_data = $this->db->get('invoice');
                            $edit_data = $invoice_data->result_array();

                            

                            if($class_name == 'JHSS') {
                                $inv_codes_array = $this->db
                                                        ->select('invoice_code')
                                                        ->distinct()
                                                        ->where('student_id', $student_row['student_id'])
                                                        ->where('year', $year)
                                                        ->where('sem', $sem)
                                                        ->where('can_delete !=', 'trash')
                                                        ->get('invoice')->result_array();
                            } else {
                                $inv_codes_array = $this->db
                                                        ->select('invoice_code')
                                                        ->distinct()
                                                        ->where('student_id', $student_row['student_id'])
                                                        ->where('year', $year)
                                                        ->where('term', $term)
                                                        ->where('can_delete !=', 'trash')
                                                        ->get('invoice')->result_array();
                            }

                            $invoice_codes_extracted = array_column($inv_codes_array, 'invoice_code');

                        /*foreach($inv_codes_array as $in_code)://based on invoice codes
                            $to_use = $this->db->get_where('invoice', array('invoice_code' => $in_code['invoice_code']));*/

                            $this->db->where_in('invoice_code', $invoice_codes_extracted);
                            $to_use = $this->db->get_where('invoice');


    ?> 

    <style type="text/css">

        th {
            background-color: #dadada;
            border: 2px solid #e2dddd;
            padding: 5px;
            color: black;
            border-bottom: 2px solid #817e7e;
         }

         page[size="A4"] {
                width: 21cm;
                height: 29.7cm;
                page-break-after: always;
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
                    width: 21cm;
                    height: 29.7cm;
                    page-break-after: always;
                }


                page[size="A4"] {
                    width: 21cm;
                    height: 29.7cm;
                    page-break-after: always;
                }

                body, page {
                    margin: 0;
                    box-shadow: 0;
                }

                table, #bursor_signature, #thank_you {
                    width: 130% !important;
                }

                table td{
                    font-size: 22px !important;*/
                }
            }

    </style>

        <page size="A4">

                <table width="100%" border="0" id="invoice_det">
                    <tr>
                        <td align="left">
                             <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br>
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
                                    if($status_row['status'] == 'unpaid') {
                                        $status_counter++;
                                    }

                                    if($status_row['status'] == 'partially paid') {
                                        $part_payment++;
                                    } 
                                }

                                /*if($status_counter > 0) {
                                    $inv_status = 'Unpaid';
                                } else {
                                    if($part_payment > 0) {
                                        $inv_status = 'Partially Paid';
                                    } else {
                                        $inv_status = 'Paid';
                                    }
                                }*/


                                $inv_status = ucwords($to_use->last_row()->status);
                            ?>

                        <td align="right">
                            <h4><?php echo get_phrase('bill_to'); ?> </h4>
                            <?php echo $this->db->get_where('student', array('student_id' => $status_row['student_id']))->row()->name; ?><br>
                    <?php 
                        
                        if($class_name == 'JHSS') {
                            $class_id = $this->db->get_where('enroll' , array(
                            'student_id' => $status_row['student_id'],
                                'year' => $year, 'sem' => $sem
                        ))->row()->class_id;

                        } else {
                            $class_id = $this->db->get_where('enroll' , array(
                            'student_id' => $status_row['student_id'],
                                'year' => $year, 'term' => $term
                        ))->row()->class_id;
                        }
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

                <table width="100%" border="0" style="font-size: 16px;" id="details">
                    <tr>
                        
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding: 10px 5px 10px 5px;">

                            <?php
                                if($class_name == 'JHSS') {  ?>
                                    <strong><?php echo get_phrase('YEAR'); ?> : <?php echo $year;; ?> | <?php echo get_phrase('SEMESTER'); ?> : <?php echo $sem; ?></strong>
                                    <?php

                                } else {
                                    ?>
                                    <strong><?php echo get_phrase('YEAR'); ?> : <?php echo $year; ?> | <?php echo get_phrase('TERM'); ?> : <?php echo $term; ?></strong>
                                    <?php
                                }
                            ?>
                        </td>

                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding: 10px 5px 10px 5px;"><strong><?php echo get_phrase('CREATION DATE'); ?> : <?php echo date('d M, Y', $to_use->last_row()->creation_timestamp);?></strong></td>
                        <td align="right" style="border-right: 3px; border-style: none solid none none; padding: 10px 5px 10px 5px; "><strong><?php echo get_phrase('STATUS'); ?> : <?php echo $inv_status; ?></strong></td>
                        
                    </tr>
                    <tr>
                        <td align="right" style="padding: 10px 5px 10px 5px; "><strong><?php echo get_phrase('INVOICE_#:'); ?> : <?php echo implode(', ', $invoice_codes_extracted);?></strong></td>
                    </tr>

                </table>
        

        <hr>
        <br><br>

        <!--Invoice Details here--->
        <table width="130%" class="table table-hover table-striped table-active" border="2" cellspacing="0" cellpadding="4" style="border-collapse:collapse; width: 130% !important; border-left: 0px; border-right: 0px;">

            <tbod>
                <tr>
                    <td style="width: 50% !important" style="padding: 10px 5px 10px 5px; border-right: 2px solid #000000">
                        <table style="width: 100% !important">
                            <tbody>
                                    <tr style="border: 1px solid #000000;">
                                        <th style="text-align: left; padding: 10px 5px 10px 5px"><?php echo get_phrase('description'); ?></th>
                                        <th style="text-align: right; padding: 10px 5px 10px 5px"><?php echo get_phrase('amount'); ?></th>
                                    </tr>

                                    <?php
                                    $total_amount = 0;
                                    $total_amount_paid = 0;
                                    $total_balance = 0;

                                    $inv_ids = array();

                                     foreach ($to_use->result_array() as $row):
                                        array_push($inv_ids, $row['invoice_id']);
                                    ?>
                       
                                    <tr>
                                        <td style="padding: 15px 5px 15px 5px"><?php echo $row['title'];?></td>
                                        <td width="200" class="due_amount" style="text-align: right; padding: 15px 5px 15px 5px" ><?php echo numfmt_format_currency($fmt, $row['amount'], $currency); ?></td>
                                    </tr>

                                    <?php 
                                        $total_amount += $row['amount']; 
                                        $total_amount_paid += $row['amount_paid']; 
                                        $total_balance += $row['due']; 
                                    endforeach;

                                    //bringing the feeding, classes fee and transport charges here
                                    //latest timestamp in the feeding table
                                    //$this->db->where('due >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('mute', '0');
                                    $this->db->order_by('timestamp', 'desc');
                                    $this->db->limit(1);
                                    $feeding_timestamp = $this->db->get('feeding_fee')->row()->timestamp;

                                    //latest timestamp in the transport fare table
                                    $this->db->where('due >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('mute', '0');
                                    $this->db->order_by('timestamp', 'desc');
                                    $this->db->limit(1);
                                    $transport_timestamp = $this->db->get('transport_fare')->row()->timestamp;

                                    //feeding owe
                                    $this->db->select_sum('due');
                                    $this->db->from('feeding_fee');
                                    $this->db->where('due >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('timestamp', $feeding_timestamp);
                                    $this->db->where('mute', '0');
                                    $tf_owe = $this->db->get()->row()->due;

                                    $this->db->where('due >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('timestamp', $feeding_timestamp);
                                    $this->db->where('mute', '0');
                                    $tf_query = $this->db->get('feeding_fee')->row();


                                    //classes owe
                                    $this->db->select_sum('cdue');
                                    $this->db->from('feeding_fee');
                                    $this->db->where('cdue >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('timestamp', $feeding_timestamp);
                                    $this->db->where('mute', '0');
                                    $tc_owe = $this->db->get()->row()->cdue;

                                    $this->db->where('cdue >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('timestamp', $feeding_timestamp);
                                    $this->db->where('mute', '0');
                                    $tc_query = $this->db->get('feeding_fee')->row();


                                    //transport owe
                                    $this->db->select_sum('due');
                                    $this->db->from('transport_fare');
                                    $this->db->where('due >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('timestamp', $transport_timestamp);
                                    $this->db->where('mute', '0');
                                    $tt_owe = $this->db->get()->row()->due;

                                    $this->db->where('due >', 0);
                                    $this->db->where('student_id', $status_row['student_id']);
                                    $this->db->where('timestamp', $transport_timestamp);
                                    $this->db->where('mute', '0');
                                    $tt_query = $this->db->get('transport_fare')->row();

                                    ?>

                                    <?php
                                        //For feeding
                                        if($tf_owe > 0):
                                    ?>
                                    <tr>
                                        <td style="padding: 10px 5px 10px 5px">FEEDING FEE                    
                                            <?php
                                                /*if($class_name == 'JHSS') {
                                                    ?>
                                                    <strong style="text-align: right;"><?php echo explode('-', $tf_query->year)[1].'|'.$tf_query->sem; ?></strong>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <strong style="text-align: right;"><?php echo explode('-', $tf_query->year)[1].'|'.$tf_query->term; ?></strong>
                                                    <?php
                                                }*/
                                            ?>
                                        </td>
                                        <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tf_owe, $currency); ?></td>
                                    </tr>
                                    <?php

                                        $total_amount += $tf_owe; 
                                        $total_balance += $tf_owe; 
                                        endif;
                                    ?>

                                    <?php
                                    //For Classes
                                    if($tc_owe > 0):
                                    ?>
                                    <tr>
                                        <td style="padding: 10px 5px 10px 5px">CLASSES FEE
                                        
                                            <?php
                                                /*if($class_name == 'JHSS') {
                                                    ?>
                                                    <strong style="text-align: right;"><?php echo explode('-', $tc_query->year)[1].'|'.$tc_query->sem; ?></strong>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <strong style="text-align: right;"><?php echo explode('-', $tc_query->year)[1].'|'.$tc_query->term; ?></strong>
                                                    <?php
                                                }*/
                                            ?>
                                        </td>
                                        <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tc_owe, $currency); ?></td>
                                    </tr>
                                    <?php

                                        $total_amount += $tc_owe; 
                                        $total_balance += $tc_owe; 
                                        endif;
                                    ?>

                                    <?php
                                    //For Classes
                                    if($tt_owe > 0):
                                    ?>
                                    <tr style="padding: 10px 5px 10px 5px">
                                        <td>TRANSPORT FARE
                                        
                                            <?php
                                                /*if($class_name == 'JHSS') {
                                                    ?>
                                                    <strong style="text-align: right;"><?php echo explode('-', $tt_query->year)[1].'|'.$tt_query->sem; ?></strong>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <strong style="text-align: right;"><?php echo explode('-', $tt_query->year)[1].'|'.$tt_query->term; ?></strong>
                                                    <?php
                                                }*/
                                            ?>
                                        </td>
                                        <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tt_owe, $currency); ?></td>
                                    </tr>
                                    <?php

                                        $total_amount += $tt_owe; 
                                        $total_balance += $tt_owe; 
                                        endif;
                                    ?>


                                    <!--SHOW TOTAL -->
                                    <tr>
                                        <td style="padding: 10px 5px 10px 5px"><strong><?php echo get_phrase('total_amount'); ?></strong></td>
                                        <td align="right" style="padding: 10px 5px 10px 5px"><strong><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>


                    </td><!-- left column -->
                    <td style="width: 50% !important">
                        <table style="width: 100% !important">
                            <tbody>
                                <tr>
                                    <td>coming</td>
                                </tr>
                            </tbody>
                        </table>
                    </td><!-- right column -->
                </tr><!-- main row -->
            </tbod>
           
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

        <!-- <hr> -->
        <!--Accounts description here-->
        <?php
            //$c_f = $this->db->get_where('class', array('class_id' => $class_id))->row();
            //$feeding_fee = $c_f->feeding_fee; //per day
            //$classes_fee = $c_f->classes_fee; //per day
        ?>

        <!--
        <h4>Feeding Fee is <?//=numfmt_format_currency($fmt, $feeding_fee * 5, $currency); ?> per WeeK.</h4>
        <h4><em>Cash Payments Accepted. Otherwise pay into our bank accounts with details below;</em></h4>

        <table class="table" width="100%" border="0" style="border-collapse:collapse;">
            <thead></thead>
            <tbody>
                <?php
                    //$this->db->where('account_is_bank', 1);
                    //$this->db->order_by('account_id', 'asc');
                    //$this->db->limit(1);
                    //$main_account = $this->db->get('accounts')->row();
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
-->
        <div class="flex gap-2" style="position: absolute; margin-left: 5px;">
            <strong><em>NB:</em></strong>
            <ul style="margin-top: -8px;">
                <?php 
                    $half_payment = $this->db->get_where('settings' , array('type'=>'half_payment_week'))->row()->description;


                    if($class_name == 'JHSS') {
                        $full_payment = $this->db->get_where('sems' , array('year' => $year, 'sem' => $sem))->row()->full_payment_date;
                    } else {
                        $full_payment = $this->db->get_where('terms' , array('year' => $year, 'term' => $term))->row()->full_payment_date;
                    }
                    
                    

                    if($full_payment == '') {
                        $full_payment_date = '';

                    } else {
                        $full_payment_date = date('l M d, Y', (strtotime($full_payment)));
                    }



                if($class_name == 'JHSS') {
                        ?>
                        <li>Part (Half) Payment of school fees is expected by <strong><?=substr($half_payment, 0, -4);?> Semester</strong></li>

                        <?php
                    } else {
                        ?>
                        <li>Part (Half) Payment of school fees is expected by <strong><?=$half_payment;?></strong></li>
                        <?php
                    }
                 ?>

                <li>Full Payment of school fees is expected by <strong><?=$full_payment_date; ?></strong></li>
                 
                 <?php
                    if($class_name == 'JHSS') {
                        ?>
                        <li>Next Semester Begins on <strong><?=date('l M d, Y', strtotime($next_sem_begins)); ?></strong></li>

                        <?php
                    } else {
                        ?>
                        <li>Next Term Begins on <strong><?=date('l M d, Y', strtotime($next_term_begins)); ?></strong></li>
                        <?php
                    }
                 ?>
                
            </ul>
        </div>


        <div id="bursor_signature" style="text-align: right">
            <p>..........................................</p>
            <p>For: Finance Department</p>
        </div>
        <div id="thank_you" style="text-align: center;">
            <hr>
            <strong>Thank You For Choosing <?=ucwords(strtolower($system_name)); ?></strong>
           <!--  <p style="color: #6f686c; margin-top: 5px">Software Developed By: Lightworldtech-0243618186</p> -->

           
        </div>
        
    </page>

<?php 
       // endforeach;
    endforeach; ?>
    </div>
</div>

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

        <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>
<script type="text/javascript">


    $(document).ready(function() {
        print_page();
        
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
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title>Bulk Invoices For <?=$this->db->get_where('class', array('class_id' => $class_id))->row()->name . ' ' . $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric . $sec_name . ' (' . $year . '|' . $term . ')';?></title> <style>table th, table td {font-size: 16px; }</style>');
        //mywindow.document.write('<link rel="stylesheet" href="assets/css/chartjs/dist/Chart.min.css" type="text\/css" \/>');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.css'); ?>" />');
        mywindow.document.write('<script src="<?php echo base_url('assets/css/chartjs/dist/Chart.js'); ?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 16px !important">');
        mywindow.document.write(data);

        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }

    }

    function print_page() {
        //window.location.reload();
        //print();
        PrintElem('#print_body')
       }

       function page_reload() {
        window.location.reload();
       }


        /*$(function() {
            setTimeout(() => {
              print_page();
            }, 1000);

        });*/

</script>