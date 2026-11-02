<?php
$readonly = 'readonly';
//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$currency_len = strlen($currency);
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');

$student_id =  $param2;

$this->db->select('invoice_code');
$this->db->distinct();
$invoices_found  = $this->db->get_where('invoice' , array('student_id' => $student_id, 'due <' => 0))->num_rows();

$edit_data_array  = $this->db->get_where('invoice' , array('student_id' => $student_id, 'due <' => 0));
$class_id  = $this->db->get_where('enroll' , array('student_id' => $student_id, 'mute' => '0', 'year' => $running_year))->row()->class_id;
$edit_data	= $edit_data_array->result_array();

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

<div class="row" style="overflow: scroll">
    <div align="center"><strong><?php echo $this->db->get_where('student', array('student_id' => $student_id))->row()->name; ?></strong></div>
</div>

<?php if($edit_data_array->num_rows() > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-success panel-shadow" data-collapsed="0">
            <?php if($invoices_found == 1) { ?>
            <div class="panel-heading">
                <div class="panel-title" style="color: #3018e4;"><?php echo get_phrase('make_refund');?><strong class="pull-right" style="color: #3018e4;"><span id="err"></span> INVOICE #: <?php echo $inv_row['invoice_code']; ?> | INVOICE YEAR: <?php echo explode('-', $this->db->get_where('invoice' , array('invoice_code' => $inv_row['invoice_code']))->row()->year)[1]; ?> | INVOICE TERM: <?php echo $running_term ?></strong></div>
            </div>

        <?php } else {

            ?>
            <div class="panel-heading">
                <div class="panel-title" style="color: #3018e4;"><?php echo get_phrase('make_refund');?><strong  style="color: #3018e4;"><span id="err" style="margin-left: 420px"></span></strong><p class="pull-right" style="font-size: 30px">MULTI-INVOICES</p></div>
            </div>
            <?php
        } ?>
            <div class="panel-body">
                    <?php echo form_open(site_url('admin/invoice/make_refund') , array(
                    'class' => 'form-groups-bordered validate', 'id' => 'payment_form'));?>
                <table class="table table-bordered table-hover table-striped table-active">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('invoice_code');?></th>
                            <th><?php echo get_phrase('title_/_item');?></th>
                            <th><?php echo get_phrase('year_|_term');?></th>
                            <th style="text-align: right;"><?php echo get_phrase('total_amount');?></th>
                            <th style="text-align: right;"><?php echo get_phrase('received');?></th>
                            <th style="text-align: right;"><?php echo get_phrase('overpaid');?></th>
                            <th style="text-align: right;"><?php echo get_phrase('amount_refunded');?></th>
                            <th><?php echo get_phrase('method');?></th>
                            <th><?php echo get_phrase('status');?></th>
                            <th><?php echo get_phrase('payment_date');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $id_array = array();
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
                                    <strong><?php echo explode('-', $year)[1].'|'. $running_term; ?></strong>
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
                                    <input type="number" id="payment_<?= $row['invoice_id']; ?>" class="form-control" name="amount_<?= $row['invoice_id']; ?>"  onkeyup="status_check('<?= $row['invoice_id']; ?>')" 
                                     placeholder="<?php echo get_phrase('amount_refunded');?>" autofocus="true" />
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
                                            $banks = $this->db->get_where('accounts', array('account_type' => 1))->result_array();
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
                                    $id_array[$i] = $row['invoice_id'];

                                    $i++;
                                ?>
                        <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php $ids = explode(',', $id_array); $ids = implode('-', $id_array); ?>
                            <input type="hidden" name="id" value="<?= $ids; ?>">
                            <td colspan="6">

                            </td>
                            <td colspan="4">
                                <div class="form-group">
                                    <div class="pull-right">
                                        <button type="submit" class="btn btn-info" id="make_refund"><?php echo get_phrase('make_refund');?></button>
                                    </div>
                                </div>
                            </td>
                        </tr>                         
                    </tbody>  
                </table>
                 <?php echo form_close();?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="row">
        <div class="col-md-2 col-sm-2"></div>
        <div class="col-md-8 col-sm-8">
            <div class="panel panel-info panel-shadow" data-collapsed="0">
                <div class="panel-heading" style="background-color: #6cb7d8">
                    <div class="panel-title" style="color: #fff;"><?php echo get_phrase('payments_|_receipts_history');?></div>
                </div>
                <div class="panel-body">

                    <table class="table table-bordered" id="exp_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('receipt_no.');?></th>
                                <th style="text-align: right"><?php echo get_phrase('amount_received');?></th>
                                <th><?php echo get_phrase('method');?></th>
                                <th><?php echo get_phrase('date_paid');?></th>
                                <th><?php echo get_phrase('action');?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $count = 1;
                            $total_amount_received = 0;
                            
                            if($param3 == '') {
                                $this->db->select('receipt_code');
                            $this->db->distinct();
                            $this->db->from('payment');
                            $this->db->where('can_delete !=', 'trash');
                            //$this->db->where('invoice_code =', 0);
                            //$this->db->or_where('invoice_code !=', '');
                            $this->db->where('invoice_code !=', null);
                            $this->db->where('student_id', $student_id);
                            $this->db->order_by('receipt_code', 'desc');
                            $rec_code = $this->db->get()->result_array();

                        } else if($param3 != '' && $param4 != '') {
                            $this->db->select('receipt_code');
                            $this->db->distinct();
                            $this->db->from('payment');
                            $this->db->where('can_delete !=', 'trash');
                            //$this->db->where('invoice_code =', 0);
                            //$this->db->or_where('invoice_code !=', '');
                            $this->db->where('invoice_code !=', null);
                            $this->db->where('student_id', $student_id);
                            $this->db->where('year', $param3);
                            $this->db->where('term', $param4);
                            $this->db->order_by('receipt_code', 'desc');
                            $rec_code = $this->db->get()->result_array();

                        } else {
                            $this->db->select('receipt_code');
                            $this->db->distinct();
                            $this->db->from('payment');
                            $this->db->where('can_delete !=', 'trash');
                            //$this->db->where('invoice_code =', 0);
                            //$this->db->or_where('invoice_code !=', '');
                            $this->db->where('invoice_code !=', null);
                            $this->db->where('student_id', $student_id);
                            $this->db->where('day_timestamp', $param3);
                            $this->db->order_by('receipt_code', 'desc');
                            $rec_code = $this->db->get()->result_array();
                        }

                            foreach($rec_code as $rec):

                                $payments = $this->db->get_where('payment' , array(
                                'receipt_code' => $rec['receipt_code']
                            ))->result_array();

                            foreach ($payments as $row2):
                                $total_amount_received += $row2['amount'];
                            endforeach;

                            $timestamp = $this->db->get_where('payment' , array(
                                'receipt_code' => $rec['receipt_code']
                            ))->row()->timestamp;

                            $year = $this->db->get_where('payment' , array(
                                'receipt_code' => $rec['receipt_code']
                            ))->row()->year;

                            $term = $this->db->get_where('payment' , array(
                                'receipt_code' => $rec['receipt_code']
                            ))->row()->term;

                            $method = $this->db->get_where('payment' , array(
                                'receipt_code' => $rec['receipt_code']
                            ))->row()->method;
                        ?>
                            <tr>
                                <td><?php echo $count++;?></td>
                                <td><?php echo $rec['receipt_code'];?></td>
                                <td style="font-weight: bolder; text-align: right"><?php echo numfmt_format_currency($fmt, $total_amount_received, $currency); ?></td>
                                <td>
                                    <?php
                                        if ($method == 1)
                                            echo get_phrase('cash');
                                        if ($method == 2)
                                            echo get_phrase('cheque');
                                        if ($method == 3)
                                            echo get_phrase('card');
                                        if ($method == 4)
                                            echo 'Mobile Money';
                                    ?>
                                </td>
                                <td><?php echo date('d M, Y H:i:s', $timestamp);?></td>

                                <?php
                                    //convert zero invoice numbers
                                    if($param2[0] == 0) {
                                        $in_code = '_'.$param2;
                                    } else {
                                        $in_code = $param2;
                                    }
                                ?>
                                <td><button class="btn btn-info" onclick="modal_view_receipt(<?php echo $rec['receipt_code'].','. $student_id.','. $total_amount_received.','. $timestamp; ?>)"><i class="entypo-eye"></i><em> View Receipt</em></button></td>
                            </tr>
                        <?php 

                            //reset the total amount received value back to 0
                            $total_amount_received = 0;
                            endforeach;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-2"></div>
</div>

<script type="text/javascript">

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


        if(receipt_style == 'style_1') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'make_refund');

        } else if(receipt_style == 'style_2') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_2/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'make_refund');

        } else if(receipt_style == 'style_3') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_3/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'make_refund');
        } else if(receipt_style == 'style_thermal') {
            window.open('<?php echo site_url('admin/receipt_print/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, '_blank');
        }
    }
</script>
<script type="text/javascript">


    $('#total_amount_1').textContent;
    let max_invoice_id = <?php echo $this->crud_model->get_max_invoice_id(); ?>;
    $(document).ready(function() {
        onload_status_checker();
        check_input();
        
    });

    $('#payment_form').submit(function(event) {

        let invoice_id_array = <?php echo json_encode($id_array) ?>;

        let receipt_option = $('#print_receipt').val();
        if(receipt_option == 1) {
            $('#payment_form').attr('target', '_blank');
        } //this set blank target for the form to open the generated receipt in a new tab.

        let counter = 0; 
        let i = 0;

        for(i = 0; i < invoice_id_array.length; i++) {
             let payment_received = $('#payment_' + invoice_id_array[i]).val();

             if(payment_received == '' || payment_received == null || payment_received < 1) {
                counter++;
             }
        }
       

        if(counter == i) {
            event.preventDefault();
            $('#err').css({
                display: 'inline',
                color: 'red',
                marginRight: '20px'
            });
            alert('No Payment Made!');
            $('#err').text('No Payment Made!');
        } else {
            $('.in').click();
            window.location.reload();
        }     
    });

    function check_input() {
        let invoice_id = <?php echo $row['invoice_id']; ?>;
        let payment_received = Number($('#payment_' + invoice_id).val());
        if(payment_received == '' || payment_received == null || payment_received < 1) {
           // $('#make_refund').attr('disabled', 'disabled');
            
            
        } else {
           // $('#make_refund').removeAttr('disabled');
            $('#err').css('display', 'none');
        }
    }

    function value_change(val) {
       let print_receipt = $('#print_receipt').filter(':checked').length;
       if(print_receipt > 0) {
            $('#print_receipt').val(1);
            $('#yes_no').text('YES');
            $('#yes_no').css('color', '#000000');
       } else {
        $('#print_receipt').val(0);
        $('#yes_no').text('NO');
        $('#yes_no').css('color', '#b3afaf');
       }
    }

    function status_check(invoice_id) {
            var total_amount = $('#total_amount_' + invoice_id).val();
            var amount_paid  = $('#amount_paid_' + invoice_id).val();
            var amount_due   = Number($('#amount_due_' + invoice_id).val());
            var payment      = Number($('#payment_' + invoice_id).val());


            if(amount_due == -payment) {
                $('#status_' + invoice_id).val('Refunded');
                $('#status_' + invoice_id).css({'background-color': 'green', 'color': '#ffffff'});
            }else if(amount_due < -payment) {
                $('#status_' + invoice_id).val('Unrefunded');
                $('#status_' + invoice_id).css({'background-color': 'red', 'color': '#ffffff'});
            }else if(amount_due > -payment) {
               $('#status_' + invoice_id).val('Over refunded');
               $('#status_' + invoice_id).css({'background-color': '#000', 'color': '#ffffff'});
           }

    }

    function onload_status_checker() {
        for(invoice_id = 1; invoice_id <= max_invoice_id; invoice_id++) {
            var total_amount = $('#total_amount_' + invoice_id).val();
            var amount_paid  = $('#amount_paid_' + invoice_id).val();
            var amount_due   = Number($('#amount_due_' + invoice_id).val());
            var payment      = Number($('#payment_' + invoice_id).val());


            if(amount_due == -payment) {
                $('#status_' + invoice_id).val('Refunded');
                $('#payment_' + invoice_id).attr('readonly', 'readonly');
                $('#method_' + invoice_id).attr('readonly', 'readonly');
                $('#timestamp_' + invoice_id).attr('readonly', 'readonly');
                $('#status_' + invoice_id).css({'background-color': 'green', 'color': '#ffffff'});
            }else if(amount_due < -payment) {
                $('#status_' + invoice_id).val('Unrefunded');
                $('#status_' + invoice_id).css({'background-color': 'red', 'color': '#ffffff'});
            }else if(amount_due > -payment) {
               $('#status_' + invoice_id).val('Over refunded');
               $('#status_' + invoice_id).css({'background-color': '#000', 'color': '#ffffff'});
           }
       }
    }

    

    $(function() {
        $('#exp_table').dataTable();
    });
</script>