<?php
$readonly = 'readonly';
//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$currency_len = strlen($currency);
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$running_sem = get_settings('running_sem');

$class_id = $param2;

$class_name = $this->crud_model->get_class_name($param2);
if($class_name == 'JHSS') {
    $this->db->select('student_id');
    $this->db->distinct();
    $students   =   $this->db->get_where('enroll' , array(
    'class_id' => $class_id, 'mute' => '0', 'year' => $running_year, 'sem' => $running_sem
))->result_array();

} else {

    $this->db->select('student_id');
    $this->db->distinct();
    $students   =   $this->db->get_where('enroll' , array(
    'class_id' => $class_id , 'year' => $running_year, 'mute' => '0', 'term' => $running_term
))->result_array();
}

$students_ids = array();

?>
    <hr>
        <div class="row">
            <div class="col-lg-7 col-md-7 col-sm-7"></div>
            <div class="col-lg-5 col-md-5 col-sm-5">
                <input type="search" class="form-control" name="student_search" id="student_search" placeholder="Search Student Name..." style="height: 35px">
            </div>
        </div>
      
      <hr>
<?php

echo form_open(site_url('admin/invoice/take_payment_bulk') , array(
                            'class' => 'form-groups-bordered validate', 'id' => 'payment_form_'));

//each student
foreach($students as $st):

        $id_array = array();

        $student_id =  $st['student_id'];//$this->db->get_where('invoice' , array('invoice_code' => $param2))->row()->student_id;
        /**$invoice_year = $this->db->get_where('invoice' , array('invoice_code' => $param2))->row()->year;
        $invoice_term = $this->db->get_where('invoice' , array('invoice_code' => $param2))->row()->term; **/

        $this->db->select('invoice_code');
        $this->db->distinct();
        $invoices_found  = $this->db->get_where('invoice' , array('student_id' => $student_id, 'due !=' => 0))->num_rows();

        if($invoices_found == 0) {
            continue;
        }

        $edit_data_array  = $this->db->get_where('invoice' , array('student_id' => $student_id, 'due !=' => 0));
        $class_id  = $this->db->get_where('enroll' , array('student_id' => $student_id, 'mute' => '0', 'year' => $running_year))->row()->class_id;
        $edit_data  = $edit_data_array->result_array();

        $class_name = $this->crud_model->get_class_name($class_id);

        $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
        $inv_number_len = strlen($invoice_code_f);

        $distinct_invoices_array = array();
        foreach($edit_data as $inv_row) {
            $query = $this->db
                        ->select('invoice_code')
                        ->distinct()
                        ->where('invoice_code', $inv_row['invoice_code'])
                        ->where('can_delete !=', 'trash')
                        ->get('invoice');

            array_push($distinct_invoices_array, $inv_row['invoice_code']);
        }

        ?>
    <div class="container major_container">
        <div class="row" style="overflow: scroll">
            <div align="center"><strong><?php echo $this->db->get_where('student', array('student_id' => $student_id))->row()->name; ?></strong></div>
        </div>

        <?php if($edit_data_array->num_rows() > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-success panel-shadow" data-collapsed="0">
                    <?php if($invoices_found == 1) { 

                        if($class_name == 'JHSS') {
                            ?>
                            <div class="panel-heading">
                                <div class="panel-title" style="color: #3018e4;"><?php echo get_phrase('take_payment');?><strong class="pull-right" style="color: #3018e4;"><span id="err"></span> INVOICE #: <?php echo $inv_row['invoice_code']; ?> | INVOICE YEAR: <?php echo explode('-', $this->db->get_where('invoice' , array('invoice_code' => $inv_row['invoice_code']))->row()->year)[1]; ?> | INVOICE SEMESTER: <?php echo $this->db->get_where('invoice' , array('invoice_code' => $inv_row['invoice_code']))->row()->sem; ?></strong></div>
                            </div>

                    <?php
                        } else {
                            ?>
                            <div class="panel-heading">
                                <div class="panel-title" style="color: #3018e4;"><?php echo get_phrase('take_payment');?><strong class="pull-right" style="color: #3018e4;"><span id="err"></span> INVOICE #: <?php echo $inv_row['invoice_code']; ?> | INVOICE YEAR: <?php echo explode('-', $this->db->get_where('invoice' , array('invoice_code' => $inv_row['invoice_code']))->row()->year)[1]; ?> | INVOICE TERM: <?php echo $this->db->get_where('invoice' , array('invoice_code' => $inv_row['invoice_code']))->row()->term; ?></strong></div>
                            </div>
                    <?php
                        }
                        ?>
                    

                <?php } else {

                    ?>
                    <div class="panel-heading">
                        <div class="panel-title" style="color: #3018e4;"><?php echo get_phrase('take_payment');?><strong  style="color: #3018e4;"><span id="err" style="margin-left: 420px"></span></strong><p class="pull-right" style="font-size: 30px">MULTI-INVOICES</p></div>
                    </div>
                    <?php
                } ?>
                    <div class="panel-body">
                            
                        <table class="table table-bordered table-hover table-striped table-active">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('invoice_code');?></th>
                                    <th><?php echo get_phrase('title_/_item');?></th>
                                    

                                    <?php
                                    if($class_name == 'JHSS') {
                                        ?>
                                        <th><?php echo get_phrase('year|Sem');?></th>
                                        <?php
                                    } else {
                                        ?>
                                        <th><?php echo get_phrase('year|Term');?></th>

                                        <?php
                                    }
                                    ?>
                                    <th style="text-align: right;"><?php echo get_phrase('total_amount');?></th>
                                    <th style="text-align: right;"><?php echo get_phrase('amount_paid');?></th>
                                    <th style="text-align: right;"><?php echo get_phrase('due');?></th>
                                    <th style="text-align: right;"><?php echo get_phrase('payment_received');?></th>
                                    <th><?php echo get_phrase('method');?></th>
                                    <th><?php echo get_phrase('status');?></th>
                                    <th><?php echo get_phrase('payment_date');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $i = 0;
                                    foreach($edit_data as $row):
                                ?>
                                <tr>
                                    <td width="100">
                                        <div class="form-group" style="padding-top: 6px;">
                                            <strong><?= $row['invoice_code']; ?></strong>
                                        </div>
                                    </td>
                                    <td width="150">
                                        <div class="form-group" style="padding-top: 6px;">
                                            <strong><?= $row['title']; ?></strong>
                                        </div>
                                    </td>
                                    <td width="120" style="text-align: right;">
                                        <div class="form-group" style="padding-top: 6px;">
                                            <?php
                                                if($class_name == 'JHSS') { 
                                                    ?>
                                                    <strong><?php echo explode('-', $row['year'])[1].'|'.$row['sem'];?></strong>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <strong><?php echo explode('-', $row['year'])[1].'|'.$row['term']; ?></strong>
                                                    <?php
                                                }
                                            ?>
                                            
                                        </div>
                                    </td>
                                    <td width="120" style="text-align: right;">
                                        <div class="form-group" style="padding-top: 6px;">
                                            <strong><?php echo numfmt_format_currency($fmt, $row['amount'], $currency); ?></strong>
                                        </div>
                                    </td>
                                    <td width="120" style="text-align: right;">
                                        <div class="form-group" style="padding-top: 6px;">
                                            <strong><?php echo numfmt_format_currency($fmt, $row['amount_paid'], $currency);?></strong>
                                        </div>
                                    </td>
                                    <td width="120" style="text-align: right;">
                                        <div class="form-group" style="padding-top: 6px;">
                                            <strong><?php echo numfmt_format_currency($fmt, $row['due'], $currency); ?></strong>
                                        </div>
                                    </td>
                                    <td width="150" style="text-align: right;">
                                        <div class="form-group">
                                            <input type="number" min="1" step="any" required id="payment_<?= $row['invoice_id']; ?>" class="form-control" name="amount_<?= $row['invoice_id']; ?>"  onkeyup="status_check('<?= $row['invoice_id']; ?>')" 
                                             placeholder="<?php echo get_phrase('payment_received');?>" autofocus="true" />
                                        </div>
                                    </td>
                                    <td width="200">
                                        <div class="form-group">
                                            <select name="method_<?= $row['invoice_id']; ?>" onchange="chooseBank($(this).val(), '<?= $row['invoice_id']; ?>')" id="method_<?= $row['invoice_id']; ?>" class="form-control" required>
                                                <option value="1"><?php echo get_phrase('cash');?></option>
                                                <option value="2"><?php echo get_phrase('cheque');?></option>
                                                <option value="3"><?php echo get_phrase('bank');?></option>
                                                <option value="4"><?php echo get_phrase('mobile_money');?></option>
                                            </select>
                                        </div> <hr>

                                        <!--Hidden for selecting the bank to pay to if bank option is selected-->
                                        <div class="form-group" id="bank_holder_<?= $row['invoice_id']; ?>" style="display: none">
                                            <select name="bank_<?= $row['invoice_id']; ?>" id="bank_<?= $row['invoice_id']; ?>" class="form-control" required>
                                                <option value="0"><?php echo get_phrase('select_Bank');?></option>
                                                <?php 
                                                    $banks = $this->db->get_where('accounts', array('account_is_bank' => 1))->result_array();
                                                    foreach($banks as $bank):

                                                        ?>
                                                        <option value="<?=$bank['account_id']?>"><?php echo $bank['account_name']?></option>
                                                        <?php
                                                    endforeach;
                                                ?>
                                            </select>
                                        </div>
                                    </td>
                                    <td width="100">
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="status_<?= $row['invoice_id']; ?>" name="status_<?= $row['invoice_id']; ?>" value="<?php echo ucwords($row['status']); ?>" <?php echo $readonly; ?>>
                                        </div>
                                    </td>
                                    <td width="120">
                                        <div class="form-group">
                                            <input type="text" class="datepicker form-control" name="timestamp_<?= $row['invoice_id']; ?>" id="timestamp_<?= $row['invoice_id']; ?>"
                                         value="<?php 
                                            if($row['due'] == 0) {
                                                echo date('d M, Y', $row['payment_timestamp']);
                                            } else {
                                                echo date('m/d/Y');
                                            }
                                                ?>" readonly="readonly"/>
                                        </div>
                                    </td>
                                        <input type="hidden" id="total_amount_<?= $row['invoice_id']; ?>" value="<?php echo $row['amount'];?>">
                                        <input type="hidden" id="amount_paid_<?= $row['invoice_id']; ?>" value="<?php echo $row['amount_paid'];?>">
                                        <input type="hidden" id="amount_due_<?= $row['invoice_id']; ?>" value="<?php echo $row['due'];?>">

                                        <input type="hidden" name="invoice_id_<?php echo $row['invoice_id'];?>" value="<?php echo $row['invoice_id'];?>">
                                        <input type="hidden" name="student_id" value="<?php echo $row['student_id'];?>">
                                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                                        <input type="hidden" name="title_<?php echo $row['invoice_id'];?>" value="<?php echo $row['title'];?>">
                                        <input type="hidden" name="description_<?php echo $row['invoice_id'];?>" value="<?php echo $row['description'];?>">
                                        <input type="hidden" name="invoice_code" value="<?php echo $row['invoice_code'];?>">
                                        <input type="hidden" name="payment_time" value="<?php echo date('H:i:s');?>">
                                       <!-- <input type="hidden" name="invoice_year" value="<?php echo $invoice_year;?>">
                                        <input type="hidden" name="invoice_term" value="<?php echo $invoice_term;?>"> -->

                                        <?php
                                            array_push($id_array, $row['invoice_id']);

                                            $i++;
                                        ?>
                                <?php endforeach; ?>
                                </tr>
                                <!--
                                <tr>
                                    <?php //$ids = explode(',', $id_array); $ids = implode('-', $id_array); ?>
                                    <input type="hidden" name="id" value="<?= $ids; ?>">
                                    <td colspan="6">
                                        <div class="form-group pull-right">
                                        <label class="control-label col-sm-8 col-xs-12">Print Receipt? </label>  

                                        <div class="switch-button  showcase-switch-button">
                                        <input id="print_receipt_<?=$student_id;?>"  type="checkbox"  value="1" checked name="print_receipt_<?=$student_id;?>" onchange="value_change(<?=$student_id;?>)">
                                        <label for="print_receipt_<?=$student_id;?>" ></label>   <strong style="font-size: 22px;" id="yes_no_<?=$student_id;?>">YES</strong>                               
                                        </div>           
                                    </div>
                                    </td>
                                </tr>  -->                       
                            </tbody>  
                        </table>
                         <?php //echo form_close();?>
                         <div class="col-lg-4 col-md-4 pull-right">
                            <input type="text" class="form-control" name="receipt_no_<?= $student_id; ?>"
                             placeholder="<?php echo get_phrase('receipt number');?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="<?=$student_id;?>_invoice_ids_array" value="<?php echo implode('-', $id_array); ?>">

        <?php 

        array_push($students_ids, $student_id);
        endif;

    //echo '<hr><hr>';
    endforeach;
 ?>

 <input type="hidden" name="students_ids" value="<?php echo implode('-', $students_ids);?>">

 <div class="form-group">
            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-12">
                
            </div>
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                <button type="submit" class="btn btn-info" id="take_payment"><?php echo get_phrase('take_payment');?></button>
            </div>
        </div>
    <?php echo form_close();?>


<?php 
    $receipt_style = $this->db->get_where('settings' , array('type'=>'receipt_style'))->row()->description;

?>

<script type="text/javascript">

    $(function() {
        //to filter the items
        $("#student_search").on("keyup", function() {
          var value = $(this).val().toLowerCase();
          $(".major_container").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
          });
        });
    });

    $('#payment_form_').submit(function(event) {

        event.preventDefault();


        //Scroll to the top
          $('html, body').animate({
              scrollTop: ($('#top').offset().top )
          }, 1000);

        /*let invoice_id_array = [];
        let students_ids = [];

        invoice_id_array = <?php echo json_encode($id_array) ?>;
        students_ids = <?php echo json_encode($students_ids) ?>;

        let receipt_option = $('#print_receipt').val();
        if(receipt_option == 1) {
            $('#payment_form_').attr('target', '_blank');
        } //this sets blank target for the form to open the generated receipt in a new tab.

        let counter = 0; 
        let track_counter = 0; 
        let i = 0;

        for(j = 0; j < students_ids.length; j++) {
            for(i = 0; i < invoice_id_array.length; i++) {
                 let payment_received = $('#payment_' + invoice_id_array[i]).val();

                 if(payment_received == '' || payment_received == null || payment_received < 1) {
                    counter++;
                 }

                 track_counter++;
            }
        }
       


        if(counter == track_counter) {
            $('#err').css({
                display: 'inline',
                color: 'red',
                marginRight: '20px'
            });

            showAjaxModal_alert('No Payment Made!', 'Error');
            $('#err').text('No Payment Made!');
        } else {*/
            
            //const url = '<?php //echo site_url($this->session->userdata('login_type') . '/income?id=/' . $class_id); ?>';

            //reload that student's invoices
            //invoice_load(<?//=$student_id;?>);
            //location.replace(url);

            //ajax

                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
                $.ajax({
                  url: '<?php echo site_url('admin/invoice/take_payment_bulk'); ?>',
                  type: 'POST',
                  dataType: 'json',
                  data: new FormData(this),
                  cache: false,
                  contentType: false,
                  processData: false
              })
              .done(function(data) {   

                if(data.message == 1) {
                    //success
                    
                    $('.in').click();
                    showAjaxModal_alert('Payment made successfully.', 'Success');
                    navigation('<?php echo site_url('admin/invoices_show'); ?>');

                    if(data.print_receipt == 1) { //wants to print receipt

                        //add href to the anchor
                        $('#open_receipt_page').attr('href', data.url);
                        
                        showAjaxModal_alert('<center><div style="font-size: 16px; font-weight: bolder; margin-top: 0px; ">Redirecting to receipt page...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
                        
                        setTimeout(() => {
                            $('#open_receipt_page')[0].click();//open receipt page on new tab
                            
                        }, 3000);

                        setTimeout(() => {

                            class_selected_load(<?=$class_id;?>); //load this class list

                            $('.close').click(); //close alert modal
                            
                        }, 5000);
                    } else {
                        $('.in').click();
                        //not printing receipt
                        setTimeout(() => {
                            class_selected_load(<?=$class_id;?>); //load this class list

                            $('.close').click(); //close alert modal
                            
                        }, 5000);
                    }
                } else {

                    //error
                    $(function(){
                        $.each(data, function(index, val) {
                          $('#list_err_bulk_payment_create').append('<li style="font-size: 16px">' + val + '</li>');
                        }); 
                    });

                    //$('.close').click();
                    showAjaxModal_alert('<ul id="list_err_bulk_payment_create"></ul>', 'Error');
                }
              })
              .fail(function(err) {
                showAjaxModal_alert(err.responseText, 'Error');
                
              });

       // }     
    });

    //option for bank selection if bank option is selected
    function chooseBank(value, invoice_id) {
        if(value == 3) { //bank option was selected
            $('#bank_holder_' + invoice_id + ' select').attr('required', 'required');
            $('#bank_holder_' + invoice_id).fadeIn('slow');
        } else {
            $('#bank_holder_' + invoice_id + ' select').removeAttr('required');
            $('#bank_holder_' + invoice_id).fadeOut('slow');
        }
    }
    
    function modal_view_receipt(receipt_code, student_id, total_amount_received, date_time) {

       /** invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        } **/

        let receipt_style = '<?php echo $receipt_style; ?>';

        if(receipt_style == 'style_1') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt/');?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');

        } else if(receipt_style == 'style_2') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_2/');?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');

        } else if(receipt_style == 'style_3') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_3/');?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');
        } else if(receipt_style == 'style_thermal') {
            window.open('<?php echo site_url('admin/receipt_print/');?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, '_blank');
        }

                 
    }



   /* $('#total_amount_1').textContent;
    let max_invoice_id = <?php //echo $this->crud_model->get_max_invoice_id(); ?>;
    $(document).ready(function() {
        onload_status_checker();
        check_input();
        
    });*/

    

    function check_input() {
        let invoice_id = <?php echo $row['invoice_id']; ?>;
        let payment_received = Number($('#payment_' + invoice_id).val());
        if(payment_received == '' || payment_received == null || payment_received < 1) {
           // $('#take_payment').attr('disabled', 'disabled');
            
            
        } else {
           // $('#take_payment').removeAttr('disabled');
            $('#err').css('display', 'none');
        }
    }

    function value_change(student_id) {
       let print_receipt = $('#print_receipt_' + student_id).filter(':checked').length;
       if(print_receipt > 0) {
            $('#print_receipt_' + student_id).val(1);
            $('#yes_no_' + student_id).text('YES');
            $('#yes_no_' + student_id).css('color', '#000000');
       } else {
        $('#print_receipt_' + student_id).val(0);
        $('#yes_no_' + student_id).text('NO');
        $('#yes_no_' + student_id).css('color', '#b3afaf');
       }
    }

    function status_check(invoice_id) {
            var total_amount = $('#total_amount_' + invoice_id).val();
            var amount_paid  = $('#amount_paid_' + invoice_id).val();
            var amount_due   = Number($('#amount_due_' + invoice_id).val());
            var payment      = Number($('#payment_' + invoice_id).val());


            if(amount_due == payment) {
                $('#status_' + invoice_id).val('Paid');
                $('#status_' + invoice_id).css({'background-color': 'green', 'color': '#ffffff'});
            }else if(amount_due > payment) {
                //do two things here: nothing paid or part payment made
                if(payment > 0) {
                    $('#status_' + invoice_id).val('Partially paid');
                    $('#status_' + invoice_id).css({'background-color': 'blue', 'color': '#ffffff'});
                } else if(payment == 0) {
                    $('#status_' + invoice_id).val('Unpaid');
                    $('#status_' + invoice_id).css({'background-color': 'red', 'color': '#ffffff'});
                }
            }else if(amount_due < payment) {
               $('#status_' + invoice_id).val('Over paid');
               $('#status_' + invoice_id).css({'background-color': '#000', 'color': '#ffffff'});
           }

    }

    function onload_status_checker() {
        for(invoice_id = 1; invoice_id <= max_invoice_id; invoice_id++) {
            var total_amount = $('#total_amount_' + invoice_id).val();
            var amount_paid  = $('#amount_paid_' + invoice_id).val();
            var amount_due   = Number($('#amount_due_' + invoice_id).val());
            var payment      = Number($('#payment_' + invoice_id).val());


            if(amount_due == 0) {
                $('#status_' + invoice_id).val('Paid');
                $('#payment_' + invoice_id).attr('readonly', 'readonly');
                $('#method_' + invoice_id).attr('readonly', 'readonly');
                $('#timestamp_' + invoice_id).attr('readonly', 'readonly');
                $('#status_' + invoice_id).css({'background-color': 'green', 'color': '#ffffff'});
            }else if(amount_due > amount_paid) {
                //do two things here: nothing paid or part payment made
                if(amount_paid > 0 && amount_paid < total_amount) {
                    $('#status_' + invoice_id).val('Partially paid');
                    $('#status_' + invoice_id).css({'background-color': 'blue', 'color': '#ffffff'});
                } else if(amount_paid == 0) {
                    $('#status_' + invoice_id).val('Unpaid');
                    $('#status_' + invoice_id).css({'background-color': 'red', 'color': '#ffffff'});
                }
            }else if(amount_due < 0) {
               $('#status_' + invoice_id).val('Over paid');
               $('#status_' + invoice_id).css({'background-color': '#000', 'color': '#ffffff'});
           }
       }
    }

    

    $(function() {
        $('#exp_table').dataTable();
    });
</script>