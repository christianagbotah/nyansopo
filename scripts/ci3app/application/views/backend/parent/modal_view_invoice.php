<?php
$invoice_data = $this->db->get_where('invoice', array('invoice_code' => $param2, 'year' => $param3, 'term' => $param4));
$edit_data = $invoice_data->result_array();

//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
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
                            <h4><?php echo get_phrase('invoice_#:'); ?> : <?php echo $param2;?></h4>
                            <h5><?php echo get_phrase('creation_date'); ?> : <?php echo date('d M, Y', $invoice_data->row()->creation_timestamp);?></h5>
                            <!--check the status of this invoice code-->
                            <?php 
                                $status_counter = 0;
                                foreach($edit_data as $status_row) {
                                    if($status_row['status'] == 'unpaid') {
                                        $status_counter++;
                                    }
                                }

                                if($status_counter > 0) {
                                    $inv_status = 'Unpaid';
                                } else {
                                    $inv_status = 'Paid';
                                }
                            ?>
                            <h5><?php echo get_phrase('status'); ?> : <?php echo $inv_status; ?></h5>
                            <strong style="color: #3b7ded;"><?php echo get_phrase('year'); ?> : <?php echo $param3; ?> | <?php echo get_phrase('term'); ?> : <?php echo $param4; ?></strong>
                        </td>
                    </tr>
                </table>
        <hr>
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
                    <?php echo $this->db->get_where('student', array('student_id' => $status_row['student_id']))->row()->name; ?><br>
                    <?php 
                        $class_id = $this->db->get_where('enroll' , array(
                            'student_id' => $status_row['student_id'],
                                'year' => $param3, 'term' => $param4
                        ))->row()->class_id;
                        //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class', array('class_id' => $class_id))->row()->name, 'name_numeric' => $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                        echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric.$sec_name;
                    ?><br>
                </td>
        </table>
        <hr>

        <!--Invoice Details here--->
        <table width="100%" class="table table-bordered table-hover table-striped table-active" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align: left;"><?php echo get_phrase('title'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('description'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('amount'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('amount_paid'); ?></th>
                    <th style="text-align: left;"><?php echo get_phrase('balance'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_amount = 0;
                $total_amount_paid = 0;
                $total_balance = 0;

                 foreach ($edit_data as $row):
                ?>
   
                <tr>
                    <td><?php echo $row['title'];?></td>
                    <td><?php echo $row['description'];?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency);?></td>
                    <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount_paid'], $currency);?></td>
                    <td class="due_amount" style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['due'], $currency); ?></td>
                </tr>

                <?php 
                    $total_amount += $row['amount']; 
                    $total_amount_paid += $row['amount_paid']; 
                    $total_balance += $row['due']; 
            endforeach;;?>
            </tbody>
        </table><br>


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
                <td align="right" width="80%"><h5><?php echo get_phrase('total_balance'); ?> :</h5></td>
                <td align="right"><h5 class="total_balance"><?php echo numfmt_format_currency($fmt, $total_balance, $currency); ?></h5></td>
            </tr>
            
        </table>

        <hr>


        <!-- payment history -->
        <h4><?php echo get_phrase('payment_history'); ?></h4>
        <table class="table table-bordered" width="100%" border="1" style="border-collapse:collapse;">
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
        </table>
    </page>
    </div>



<script type="text/javascript">
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
        mywindow.document.write('<html><head><title>Invoice</title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('</head><body >');
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