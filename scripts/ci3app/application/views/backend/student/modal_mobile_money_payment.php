<?php 
	$mobile_money_account_name = $this->db->get_where('settings', array('type' => 'mo_account_name'))->row()->description;
	$mobile_money_account_number = $this->db->get_where('settings', array('type' => 'mo_account_number'))->row()->description;

	$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    $invoice = $this->db->get_where('invoice' , array('student_id' => $param3, 'invoice_id' => $param2))->result_array();

    $student_id = $param3;
    $invoice_id = $param2;

?>

<div class="row">
	<div class="col-sm-12">
		<div class=" panel panel-info" data-toggle="collapse" data-target="#p_instructions" style="cursor: pointer" title="Click to read the instructions!">
		<div class="panel-heading"><h4 class="panel-title"><span class="glyphicon glyphicon-info-sign"></span> Payment Via Mobile Money. Click here to read the instructions</h4></div>
		<div class="panel-body collapse" id="p_instructions">
			<strong>Mobile Money Account Name: <?php echo $mobile_money_account_name; ?><br>Mobile Money Account No.: <?php echo $mobile_money_account_number; ?></strong>
			<hr>
			<center><strong><em>Please follow these instructions carefully to make your payment!</em></strong></center>
			<hr>

			<ol>
				<li class="list-item">When on this page, initiate the payment on your mobile phone and select <strong>MOBILE AGENT</strong></li>
				<li class="list-item">When asked for receiver's number, enter this mobile number: <strong style="letter-spacing: 3px;"><?php echo $mobile_money_account_number; ?></strong></li>
				<li class="list-item">Carefully compare the receiver's name on your phone with this name: <strong><?php echo $mobile_money_account_name; ?></strong> and once they are the same, authorize your payment</li>
				<li class="list-item">When payment is done, carefully enter the <strong>Transaction ID and the exact amount you paid</strong> in the respective Transaction ID and Amount fields provided below</li>
				<li class="list-item">Click on the submit button to send your payment information to the school accountant</li>
				<li class="list-item">Wait patiently for some minutes for your payment to be approved. You will receive an alert as soon as it is done</li>
				<li class="list-item">That is all.</li>
			</ol>
		</div>
		</div>
		</div>
</div>
<div class="row">
	<div class="col-sm-12">
		<div class=" panel panel-danger">
			<div class="panel-heading"><h4 class="panel-title"><span class="glyphicon glyphicon-credit-card"></span> Enter Payment Details Here</h4></div>
			<div class="panel-body">
				<strong>Invoice Details:</strong>
				<table class="table table-bordered table-hover table-active">
					<tbody>
						<?php foreach($invoice as $inv): ?>
							<tr>
								<td colspan="6" align="center"><strong>INVOICE TITLE: <?= $inv['title']; ?></strong></td>
							</tr>
							<tr>
								<td align="right">Invoice#:</td>
								<td style="font-weight: bold;"><?php echo $inv['invoice_code']; ?></td>

								<td align="right">Year:</td>
								<td style="font-weight: bold;"><?php echo $inv['year']; ?></td>

								<td align="right">Term:</td>
								<td style="font-weight: bold;"><?php echo $inv['term'];?></td>


							</tr>

							<tr>
								<td align="right">Name:</td>
								<td colspan="3" style="font-weight: bold;"><?php echo ucwords(strtolower($this->db->get_where('student', array('student_id' => $param3))->row()->name)); ?></td>

								<td align="right">Amount Due:</td>
								<td style="font-weight: bold;"><?php echo numfmt_format_currency($fmt, $inv['due'], $currency);?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<hr>

				<?php if(validation_errors()) :?>
				<div class="alert alert-danger alert-dismissible" role="alert">
				    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
				    </button>
				   <strong> <?php echo validation_errors(); ?></strong>
				</div>
			<?php endif;?>

				<?php echo form_open(site_url('student/mobile_money_checkout/'.$student_id), array('class' => 'form-horizontal form-group-bordered')) ?>
					<div class="form-group">
						<label class="form-control-label col-sm-3">Transaction ID:</label>
						<div class="col-sm-9">
							<input type="text" name="t_id" class="form-control" required="required" minlength="10" maxlength="10" >
						</div>
					</div>
					<div class="form-group">
						<label class="form-control-label col-sm-3">Amount Paid:</label>
						<div class="col-sm-9">
							<input type="text" name="mo_a_paid" class="form-control" required="required">
						</div>	
					</div>
					<div class="col-sm-4 col-sm-offset-4">
						<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
						<input type="hidden" name="mo_timestamp" value="<?php echo date('d-M-Y, H:i:s');?>"/>
						<input type="hidden" name="title" value="<?php echo $inv['title'];?>">
                    	<input type="hidden" name="description" value="<?php echo $inv['description'];?>">

						<input type="submit" class="btn btn-primary" id="submit_payment" name="submit_payment" value="Submit Payment">
						
					</div>
				</form>
			</div>
		</div>
	</div>	
</div>

