<?php
$readonly = 'readonly';
//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$currency_len = strlen($currency);
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');

$param4 = urldecode($param4);
$student_id =  $param2;//$this->db->get_where('invoice' , array('invoice_code' => $param2))->row()->student_id;
/**$invoice_year = $this->db->get_where('invoice' , array('invoice_code' => $param2))->row()->year;
$invoice_term = $this->db->get_where('invoice' , array('invoice_code' => $param2))->row()->term; **/

$data['ids_selected'] = $student_id;
$data['class_id'] = $this->db->get_where('enroll', array('year' => $running_year, 'term' => $running_term, 'student_id' => $student_id))->row()->class_id;
$data['timestamp'] = $param3;

?>


<div class="row">
    <div><center><h4><?php echo $this->db->get_where('student', array('student_id' => $param2))->row()->name; ?></h4></center></div>
        <div class="col-md-12 col-sm-12">
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
                            
                            if($param4 == 'Feeding Fee') {
                            $this->db->select('receipt_code');
                            $this->db->distinct();
                            $this->db->from('payment');
                            $this->db->where('can_delete !=', 'trash');
                            //$this->db->where('invoice_code =', 0);
                            //$this->db->or_where('invoice_code !=', '');
                            $this->db->where('invoice_code', null);
                            $this->db->where('title', 'Feeding Fee');
                            $this->db->where('student_id', $student_id);
                            $this->db->where('day_timestamp', $param3);
                            $rec_code = $this->db->get()->result_array();

                        } else if($param4 == 'Classes Fee') {
                            $this->db->select('receipt_code');
                            $this->db->distinct();
                            $this->db->from('payment');
                            $this->db->where('can_delete !=', 'trash');
                            //$this->db->where('invoice_code =', 0);
                            //$this->db->or_where('invoice_code !=', '');
                            $this->db->where('invoice_code', null);
                            $this->db->where('title', 'Classes Fee');
                            $this->db->where('student_id', $student_id);
                            $this->db->where('day_timestamp', $param3);
                            $rec_code = $this->db->get()->result_array();

                        } else if($param4 == 'Transport Fare') {
                            $this->db->select('receipt_code');
                            $this->db->distinct();
                            $this->db->from('payment');
                            $this->db->where('can_delete !=', 'trash');
                            //$this->db->where('invoice_code =', 0);
                            //$this->db->or_where('invoice_code !=', '');
                            $this->db->where('invoice_code', null);
                            $this->db->where('title', 'Transport Fare');
                            $this->db->where('student_id', $student_id);
                            $this->db->where('day_timestamp', $param3);
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
                                <td><a href="<?php echo site_url('admin/cft_student_receipt/ajax/'.$data['ids_selected'].'/'.$data['class_id'].'/'.$data['timestamp']);?>" class="btn btn-info" target="_blank"><i class="entypo-eye"></i><em> View Receipt</em></button></td>
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
</div>

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
        let payment_received = $('#payment_' + invoice_id).val();
        if(payment_received == '' || payment_received == null || payment_received < 1) {
           // $('#take_payment').attr('disabled', 'disabled');
            
            
        } else {
           // $('#take_payment').removeAttr('disabled');
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


            if(amount_due == payment) {
                $('#status_' + invoice_id).val('Paid');
                $('#status_' + invoice_id).css({'background-color': 'green', 'color': '#ffffff'});
            }else if(amount_due > payment) {
                $('#status_' + invoice_id).val('Unpaid');
                $('#status_' + invoice_id).css({'background-color': 'red', 'color': '#ffffff'});
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


            if(amount_due == payment) {
                $('#status_' + invoice_id).val('Paid');
                $('#payment_' + invoice_id).attr('readonly', 'readonly');
                $('#method_' + invoice_id).attr('readonly', 'readonly');
                $('#timestamp_' + invoice_id).attr('readonly', 'readonly');
                $('#status_' + invoice_id).css({'background-color': 'green', 'color': '#ffffff'});
            }else if(amount_due > payment) {
                $('#status_' + invoice_id).val('Unpaid');
                $('#status_' + invoice_id).css({'background-color': 'red', 'color': '#ffffff'});
            }else if(amount_due < payment) {
               $('#status_' + invoice_id).val('Over paid');
               $('#status_' + invoice_id).css({'background-color': '#000', 'color': '#ffffff'});
           }
       }
    }

    

    $(function() {
        $('#exp_table').dataTable();
    });
</script>