<?php

$invoice_data = $this->db->get_where('invoice', array('student_id' => $param2));

// Get student's current class
$this->db->order_by('enroll_id', 'desc');
$this->db->limit(1);
$enroll_data = $this->db->get_where('enroll', array('student_id' => $param2))->row();
$class_id = $enroll_data ? $enroll_data->class_id : 0;
$class_name = $this->crud_model->get_class_name($class_id);




$edit_data = $invoice_data->result_array();

//Let's try to do bulk selection
$bulk_inv_code = $this->db
                            ->select('invoice_code')
                            ->distinct()
                            ->where('due >', 0)
                            ->where('student_id', $param2)
                            ->order_by('invoice_code', 'desc')
                            ->where('can_delete !=', 'trash')
                            ->get('invoice');

//select the items title distinctly
$distinct_titles = $this->db
                            ->select('title')
                            ->distinct()
                            ->where('due >', 0)
                            ->where('student_id', $param2)
                            ->where('can_delete !=', 'trash')
                            ->order_by('title', 'desc')
                            ->get('invoice');

//doing some assigning
if($distinct_titles->num_rows() > 0) {
    $title_array = $distinct_titles->result_array();
    $titles_collected = array();
    $title_counter = 0;

    foreach($title_array as $title) {
        $titles_collected[$title_counter] = $title['title'];
        $title_counter++;
    }
}


//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;

$invoice_due_period = $this->db->get_where('settings', array('type' => 'invoice_due'))->row()->description;
$total_invoice_duration = 86400 * $invoice_due_period;

$next_term_begins = $this->db->get_where('settings', array('type' => 'next_term_begins'))->row()->description;

$next_sem_begins = $this->db->get_where('settings', array('type' => 'next_sem_begins'))->row()->description;


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
            width: 21cm;
            height: 29.7cm;
        }

        body, page {
            margin: 0;
            box-shadow: 0;
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
                        <td align="right">
                        <!--    <h4><?php echo get_phrase('invoice_#:'); ?> : <?php echo $param2;?></h4>
                            <h5><?php echo get_phrase('creation_date'); ?> : <?php echo date('d M, Y', $invoice_data->row()->creation_timestamp);?></h5> -->
                            <!--check the status of this invoice code-->
                            <h2>BULK INVOICE</h2>
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

                                /**
                                if($status_counter > 0) {
                                    $inv_status = 'Unpaid';
                                } else {
                                    if($part_payment > 0) {
                                        $inv_status = 'Partially Paid';
                                    } else {
                                        $inv_status = 'Paid';
                                    }
                                }*/


                            ?>
                            <!-- <h5><?php //echo get_phrase('status'); ?> : <?php //echo $inv_status; ?></h5>
                            <strong style="color: #3b7ded;"><?php //echo get_phrase('year'); ?> : <?php //echo $param3; ?> | <?php //echo get_phrase('term'); ?> : <?php //echo $param4; ?></strong> -->
                        </td>
                    </tr>
                </table>

        <table width="100%" border="0" class="table">    
            <tr>
                <td align="left"><h4><?php echo get_phrase('payment_to'); ?> </h4></td>
                <td align="right"><h4><?php echo get_phrase('bill_to'); ?> </h4></td>
            </tr>

            <tr>
                <td align="left" valign="top">
                    <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?><br>
                    <?php echo $this->db->get_where('settings', array('type' => 'address'))->row()->description; ?><br>
                    <?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?><br>
                    <?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?><br>            
                </td>
                <td align="right" valign="top">
                    <?php echo $this->db->get_where('student', array('student_id' => $param2))->row()->name; ?><br>
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

        <!--Invoice Details here--->
        <table width="100%" class="table table-bordered table-hover table-striped table-active" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align: left;"><?php echo get_phrase('description'); ?></th>
                    <th style="text-align: right;"><?php echo get_phrase('amount'); ?></th>
                    <th style="text-align: right;"><?php echo get_phrase('paid'); ?></th>

                    <?php
                        if($class_name == 'JHSS') {
                            ?>
                            <th style="text-align: right;"><?php echo get_phrase('year_|_semester'); ?></th>
                            <?php
                        } else {
                            ?>
                            <th style="text-align: right;"><?php echo get_phrase('year_|_term'); ?></th>
                            <?php
                        }
                    ?>
                    
                    <th style="text-align: right"><?php echo get_phrase('balance'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_amount = 0;
                $total_amount_paid = 0;
                $total_balance = 0;

                $inv_ids = array();
                for($in = 0; $in < count($titles_collected); $in++):
                    $query_inv = $this->db
                                          ->where('title', $titles_collected[$in])
                                          //->where('due >', 0)
                                          ->where('student_id', $status_row['student_id'])
                                          ->where('can_delete !=', 'trash')
                                          ->get('invoice')->result_array();

                 foreach ($query_inv as $row):
                ?>
   
                <tr>
                    <td><?php echo $row['title'];?></td>
                 
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount_paid'], $currency);?></td>
                    
                    <?php
                        if($class_name == 'JHSS') {
                            ?>
                            <td style="text-align: right;"><?php echo explode('-', $row['year'])[1].'|'.$row['sem']; ?></td>
                            <?php
                        } else {
                            ?>
                            <td style="text-align: right;"><?php echo  $row['year'].'|'.$row['term']; ?></td>
                            <?php
                        }
                    ?>
                    <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['due'], $currency); ?></td>
                </tr>

                <?php 
                    $total_amount += $row['amount']; 
                    $total_amount_paid += $row['amount_paid']; 
                    $total_balance += $row['due']; 
            endforeach; 

                endfor; 


                // Get daily fees arrears from new daily_fee_wallet table
                $wallet = $this->db->get_where('daily_fee_wallet', array('student_id' => $param2))->row();
                
                // Get current session info
                $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
                $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
                $running_sem = $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description;
                
                // Calculate arrears
                $tf_owe = $wallet ? $wallet->feeding_arrears : 0;
                $tc_owe = $wallet ? $wallet->classes_arrears : 0;
                $tt_owe = $wallet ? $wallet->transport_arrears : 0;

                ?>

            <?php
                //For feeding
                if($tf_owe > 0):
            ?>
            <tr>
                    <td>FEEDING FEE</td>
                 
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tf_owe, $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, 0, $currency);?></td>
                    
                    <?php
                        if($class_name == 'JHSS') {
                            ?>
                            <td style="text-align: right;"><?php echo explode('-', $running_year)[1].'|'.$running_sem; ?></td>
                            <?php
                        } else {
                            ?>
                            <td style="text-align: right;"><?php echo $running_year.'|'.$running_term; ?></td>
                            <?php
                        }
                    ?>
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
                    <td>CLASSES FEE</td>
                 
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tc_owe, $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, 0, $currency);?></td>
                    
                    <?php
                        if($class_name == 'JHSS') {
                            ?>
                            <td style="text-align: right;"><?php echo explode('-', $running_year)[1].'|'.$running_sem; ?></td>
                            <?php
                        } else {
                            ?>
                            <td style="text-align: right;"><?php echo $running_year.'|'.$running_term; ?></td>
                            <?php
                        }
                    ?>
                    <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tc_owe, $currency); ?></td>
                </tr>
                <?php

                    $total_amount += $tc_owe; 
                    $total_balance += $tc_owe; 
                    endif;
                ?>

                <?php
                //For Transport
                if($tt_owe > 0):
            ?>
            <tr>
                    <td>TRANSPORT FARE</td>
                 
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tt_owe, $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, 0, $currency);?></td>
                    
                    <?php
                        if($class_name == 'JHSS') {
                            ?>
                            <td style="text-align: right;"><?php echo explode('-', $running_year)[1].'|'.$running_sem; ?></td>
                            <?php
                        } else {
                            ?>
                            <td style="text-align: right;"><?php echo $running_year.'|'.$running_term; ?></td>
                            <?php
                        }
                    ?>
                    <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $tt_owe, $currency); ?></td>
                </tr>
                <?php

                    $total_amount += $tt_owe; 
                    $total_balance += $tt_owe; 
                    endif;
                ?>



                <!--SHOW TOTAL -->
            <tr>
                <td><strong><?php echo get_phrase('total_amount'); ?></strong></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></strong></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_amount_paid, $currency); ?></strong></td>
                <td></td>
                <td align="right"><strong><?php echo numfmt_format_currency($fmt, $total_balance, $currency); ?></strong></td>
            </tr>

            </tbody>
        </table><br>


       <!-- <table width="100%" border="0" class="table" cellspacing="0" cellpadding="0" style="line-height: 1px;">    
            <tr>
                <td align="right" width="80%"><h5><?php echo get_phrase('total_amount'); ?> :</h5></td>
                <td align="right"><h5><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></h5></td>
            </tr>
            <tr>
                <td align="right" width="80%"><h5><?php echo get_phrase('total_amount_paid'); ?> :</h5></td>
                <td align="right"><h5><?php echo numfmt_format_currency($fmt, $total_amount_paid, $currency); ?></h5></td>
            </tr>
            <tr>
                <td align="right" width="80%"><h5><?php echo get_phrase('total_balance'); ?> :</h5></td>
                <td align="right"><h5 class="total_balance"><?php echo numfmt_format_currency($fmt, $total_balance, $currency); ?></h5></td>
            </tr>
            
        </table> -->

        <hr>
        <!--Accounts description here--><!--
        <?php
            $c_f = $this->db->get_where('class', array('class_id' => $class_id))->row();
            $feeding_fee = $c_f->feeding_fee; //per day
            $classes_fee = $c_f->classes_fee; //per day
        ?>
        <h4>Feeding Fee is <?=numfmt_format_currency($fmt, $feeding_fee * 5, $currency); ?> per Week.</h4>
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

            
            <?php
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
        mywindow.document.write('<html><head><title>Invoice</title><style>table th, table td {font-size: 10px; }</style>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<\/head><body style="font-size: 10px !important">');
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