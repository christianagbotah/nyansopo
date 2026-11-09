<!doctype html>
<html>
	<head>
		<title>Transport Fare Payment Receipt</title>
        <style type="text/css">
             @media Print
             {
                page {
                background: #ffffff;
                display: block;
                margin: 0 auto;
                margin-bottom: 0.5cm;
                box-shadow: 0;
                }
                page[size="A6"] {
                    width: 80mm;
                }
                body, page {
                    margin: 0;
                    box-shadow: 0;
                }
                table {
                    width: 100%; 
                    border-collapse: collapse; 
                    padding: 5px 0px;
                }
            }
        </style>
	</head>
	<body>
		<?php
			$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
			$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
			$system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
			$system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
			$system_slogan = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
			
			// Get payment record
			$payment = $this->db->get_where('transport_fare', array('receipt_number' => $receipt_number))->row();
			
			if (!$payment) {
				echo '<h3>Receipt not found</h3>';
				return;
			}
			
			// Get student info
			$student = $this->db->get_where('student', array('student_id' => $payment->student_id))->row();
			$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
			$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
			
			// Get class info
			$enroll = $this->db->get_where('enroll', array('student_id' => $payment->student_id, 'year' => $payment->year, 'term' => $payment->term))->row();
			$class_name = '';
			if ($enroll && $enroll->class_id) {
				$class = $this->db->get_where('class', array('class_id' => $enroll->class_id))->row();
				if ($class) {
					$section = $this->db->get_where('section', array('section_id' => $enroll->section_id))->row();
					$class_name = $class->name . ' ' . $class->name_numeric . ($section ? ' ' . $section->name : '');
				}
			}
			
			// Get transport info
			$transport = $this->db->get_where('transport', array('transport_id' => $payment->transport_id))->row();
			
			// Get total paid
			$total_paid_result = $this->db->select_sum('amount_paid')
				->where('student_id', $payment->student_id)
				->where('transport_id', $payment->transport_id)
				->where('year', $payment->year)
				->where('term', $payment->term)
				->get('transport_fare')->row();
			$total_paid = $total_paid_result->amount_paid ? $total_paid_result->amount_paid : 0;
			
			// Calculate balance
			$balance = $transport->route_fare - $total_paid;
			
			// Get issuer info
			$issuer_name = 'Not Available';
			if ($payment->created_by) {
				$admin = $this->db->get_where('admin', array('admin_id' => $payment->created_by))->row();
				if ($admin) {
					$issuer_name = $admin->name;
				}
			}
			
			$payment_method_text = ucwords(str_replace('_', ' ', $payment->payment_method));
        ?>
        
        <center>
        <page size="A6" id="r_print">
        <div id="main_receipt">
            <table style="border: 1px solid #d0cccc; padding: 5px;">
            <tr>
            <td align="center">
            <div><img src="<?php echo base_url('uploads/school_logo.png'); ?>" style="max-height : 120px;"></div><br>
            <div><strong><?php echo $system_name; ?></strong></div>
            <div><strong><?php echo $system_phone .' | '. $system_mail; ?></strong></div>

            <div class="row" style="padding: 8px;" >
                <center><strong id="official" style="background-color: #000; width: 200px; padding: 6px; border-radius: 9px; color: #fff; font-size: 23px">Transport Fare Receipt</strong></center>
            </div>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left">Received From:</th>
                            <th align="right" colspan="2"><?php echo ucwords(strtolower($student->name)); ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Student Code:</th>
                            <th align="right" colspan="2"><?php echo $student->student_code; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Year | Term:</th>
                            <th align="right" colspan="2"><?php echo explode('-', $payment->year)[1]. ' | '. $payment->term; ?></th>
                        </tr> 
                        <tr>
                            <th style="text-align: left">Class/Form:</th>
                            <th align="right" colspan="2"><?php echo $class_name; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Route:</th>
                            <th align="right" colspan="2"><?php echo $transport->route_name; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Paid On:</th>
                            <th align="right" colspan="2"><?php echo date('d/m/Y', strtotime($payment->payment_date)); ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Receipt No.:</th>
                            <th align="right" colspan="2"><?php echo $payment->receipt_number; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Currency:</th>
                            <th style="text-align: right" colspan="2"><?php echo $currency; ?></th>
                        </tr>
                        <tr>
                          <th style="text-align: left">Issued On:</th>
                          <th align="right" colspan="2"><?php echo date('d/m/Y H:i:s', strtotime($payment->created_at)); ?></th>
                        </tr>
                    </thead>
                </table>
            </div><br>
            <table>
                <thead>
                    <tr >
                        <th width="40" align="left">S/N</th>
                        <th width="180" align="left">ITEM</th>
                        <th></th>
                        <th width="120" align="right">AMOUNT</th>
                    </tr>
                    <tr><th colspan="4"><hr></th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td align="left" colspan="2" style="font-size: 12px">Transport Fare Payment</td>                    
                        <td align="right"><?php echo number_format($payment->amount_paid, 2, '.', ','); ?></td>
                    </tr>
                    <?php if (!empty($payment->remarks)): ?>
                    <tr>
                        <td colspan="4" style="font-size: 11px; color: #666;">
                            <strong>Remarks:</strong> <?php echo $payment->remarks; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($payment->payment_method == 'mobile_money'): ?>
                    <tr>
                        <td colspan="4" style="font-size: 11px; color: #666;">
                            <strong>MoMo Number:</strong> <?php echo $payment->momo_number; ?><br>
                            <strong>Transaction ID:</strong> <?php echo $payment->transaction_id; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($payment->payment_method == 'cheque'): ?>
                    <tr>
                        <td colspan="4" style="font-size: 11px; color: #666;">
                            <strong>Bank:</strong> <?php echo $payment->bank_name; ?><br>
                            <strong>Cheque No:</strong> <?php echo $payment->cheque_number; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                	<tr></tr>
                    <tr></tr>
                    <tr></tr>
                    <tr><td colspan="4"><hr></td></tr>
                    <tr>
                        <td align="right" colspan="3"><strong>Amount Paid:</strong></td>
                        <td align="right" style="font-weight: bolder;"><?php echo number_format($payment->amount_paid, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Route Fare:</td>
                        <td align="right"><?php echo number_format($transport->route_fare, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Total Paid (This Term):</td>
                        <td align="right"><?php echo number_format($total_paid, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                    	<td align="right" colspan="3"><strong>Balance Owing:</strong></td>
                        <td align="right" style="font-weight: bolder; color: <?php echo $balance > 0 ? '#e73636' : '#000'; ?>;"><?php echo number_format($balance, 2, '.', ','); ?></td>
                    </tr>

                    <tr><td><br></td></tr>
                    <tr>
                        <td colspan="2">
                            <strong>Cashier: </strong><br><?php echo ucwords(strtolower($issuer_name)); ?>
                            <hr>
                        </td>
                        <td colspan="2" align="right">
                            <strong>Method: </strong><br><?php echo $payment_method_text; ?>
                            <hr>
                        </td>
                    </tr>
                    <tr><td colspan="4" align="center">
                        <p><?php echo $system_slogan."!!!"; ?></p>
                        <p><strong>Thank You</strong></p>
                    </td></tr>
                </tbody>
            </table>
        </td>
        </tr>
        </table>
        </div>
     </page><br>
        <button onclick="PrintElem('#r_print')" style="border: 1 solid #0cc; padding: 10px; cursor: pointer; background-color: green; color: #fff;">Print</button>
        <button onclick="window.close()" style="position:relative; border: 1 solid #0cc; cursor: pointer; padding: 10px; background-color: red; color: #fff;">Close</button>
        </center>
    
    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
	</body>
</html>

<script type="text/javascript">
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Receipt', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>" type="text/css" />');
        mywindow.document.write('</head><body style="font-size: 14px">');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
        }
    }
    
    // Auto print on load if opened in new window
    if (window.opener) {
        setTimeout(function() {
            PrintElem('#r_print');
        }, 500);
    }
</script>


