<div class="row">
        <div class="col-md-2 col-sm-2"></div>
        <div class="col-md-8 col-sm-8">
            <div class="panel panel-info panel-shadow" data-collapsed="0">
                <div class="panel-heading" style="background-color: #6cb7d8">
                    <div class="panel-title" style="color: #fff;"><?php echo get_phrase('payments_|_receipts_history'); ?></div>
                </div>
                <div class="panel-body">

                    <table class="table table-bordered" id="exp_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('receipt_no.'); ?></th>
                                <th style="text-align: right"><?php echo get_phrase('amount_received'); ?></th>
                                <th><?php echo get_phrase('method'); ?></th>
                                <th><?php echo get_phrase('date_paid'); ?></th>
                                <th><?php echo get_phrase('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $count = 1;
                            $total_amount_received = 0;
                            
                            if ($param3 == '') {
                            	$this->db->select('receipt_code');
                            	$this->db->distinct();
                            	$this->db->from('payment');
                            	//$this->db->where('invoice_code =', 0);
                            	//$this->db->or_where('invoice_code !=', '');
                            	$this->db->where('invoice_code !=', null);
                            	$this->db->where('student_id', $student_id);
                            	$this->db->order_by('receipt_code', 'desc');
                            	$rec_code = $this->db->get()->result_array();
                            
                            } else if ($param3 != '' && $param4 != '') {
                            	$this->db->select('receipt_code');
                            	$this->db->distinct();
                            	$this->db->from('payment');
                            	//$this->db->where('invoice_code =', 0);
                            	//$this->db->or_where('invoice_code !=', '');
                            	$this->db->where('invoice_code !=', null);
                            	$this->db->where('student_id', $student_id);
                            	$this->db->where('year', $param3);
                            
                            	if ($class_name == 'JHSS') {
                            		$this->db->where('sem', $param4);
                            	} else {
                            		$this->db->where('term', $param4);
                            	}
                            
                            	$this->db->order_by('receipt_code', 'desc');
                            	$rec_code = $this->db->get()->result_array();
                            
                            } else {
                            	$this->db->select('receipt_code');
                            	$this->db->distinct();
                            	$this->db->from('payment');
                            	//$this->db->where('invoice_code =', 0);
                            	//$this->db->or_where('invoice_code !=', '');
                            	$this->db->where('invoice_code !=', null);
                            	$this->db->where('student_id', $student_id);
                            	//$this->db->where('day_timestamp', $param3);
                            	$this->db->order_by('receipt_code', 'desc');
                            	$rec_code = $this->db->get()->result_array();
                            }
                            
                            foreach ($rec_code as $rec):
                            
                            	$payments = $this->db->get_where('payment', array(
                            		'receipt_code' => $rec['receipt_code'],
                            	))->result_array();
                            

                            		$total_amount_received += array_column($payments, 'amount');

                            
                            	$timestamp = $this->db->get_where('payment', array(
                            		'receipt_code' => $rec['receipt_code'],
                            	))->row()->timestamp;
                            
                            	$year = $this->db->get_where('payment', array(
                            		'receipt_code' => $rec['receipt_code'],
                            	))->row()->year;
                            
                            	if ($class_name == 'JHSS') {
                            		$sem = $this->db->get_where('payment', array(
                            			'receipt_code' => $rec['receipt_code'],
                            		))->row()->sem;
                            	} else {
                            		$term = $this->db->get_where('payment', array(
                            			'receipt_code' => $rec['receipt_code'],
                            		))->row()->term;
                            	}
                            
                            	$method = $this->db->get_where('payment', array(
                            		'receipt_code' => $rec['receipt_code'],
                            	))->row()->method;
                            	?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo $rec['receipt_code']; ?></td>
                            <td style="font-weight: bolder; text-align: right"><?php echo numfmt_format_currency($fmt, $total_amount_received, $currency); ?></td>
                            <td>
                                <?php
                            	if ($method == 1) {
                            		echo get_phrase('cash');
                            	}
                            
                            	if ($method == 2) {
                            		echo get_phrase('cheque');
                            	}
                            
                            	if ($method == 3) {
                            		echo get_phrase('card');
                            	}
                            
                            	if ($method == 4) {
                            		echo 'Mobile Money';
                            	}
                            
                            	?>
                    </td>
                    <td><?php echo date('d M, Y H:i:s', $timestamp); ?></td>

                    <?php
                    	//convert zero invoice numbers
                    	if ($param2[0] == 0) {
                    		$in_code = '_' . $param2;
                    	} else {
                    		$in_code = $param2;
                    	}
                    	?>
	                                <td><button class="btn btn-info" onclick="modal_view_receipt('<?php echo $rec['receipt_code'] ?>', <?=$student_id . ',' . $total_amount_received . ',' . $timestamp;?>)"><i class="entypo-eye"></i><em> View Receipt</em></button></td>
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

        <a target="_blank" id="open_receipt_page"></a>

        <?php


?>
</div>

<script type="text/javascript">
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
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');
        } else if(receipt_style == 'style_2') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_2/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');
        } else if(receipt_style == 'style_3') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_3/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');
        } else if(receipt_style == 'style_thermal') {
            window.open('<?php echo site_url('admin/receipt_print/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, '_blank');
        }

    }
</script>