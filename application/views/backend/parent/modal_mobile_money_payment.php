<?php 
	$mobile_money_account_name = $this->db->get_where('settings', array('type' => 'mo_account_name'))->row()->description;
	$mobile_money_account_number = $this->db->get_where('settings', array('type' => 'mo_account_number'))->row()->description;

	$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    
    $invoice = $this->db->get_where('invoice' , array('student_id' => $param3, 'invoice_code' => $param2))->result_array();
    $current_user_id  = $this->session->userdata('login_user_id');

    $student_id = $param3;
    $invoice_code = $param2;

?>

<div class="row">
	<!-- <div class="col-sm-12">
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
		</div> -->
</div>
<div class="row">
	<div class="col-sm-12">
		<div class=" panel panel-danger">
			<div class="panel-heading"><h4 class="panel-title"><span class="glyphicon glyphicon-credit-card"></span> Enter Payment Details Here</h4></div>
			<div class="panel-body">
				<strong>Invoice Details:</strong>
				<table class="table table-bordered table-hover table-active">
					<tbody>
						<tr>
								<td colspan="2" align="left"><strong>INVOICE#: <?=$invoice_code; ?></strong></td>
								<td colspan="4" align="right"><strong>NAME: <?=strtoupper($this->db->get_where('student', array('student_id' => $param3))->row()->name);?></strong></td>
							</tr>
						<?php 
						$total = 0;
						foreach($invoice as $inv): ?>
							
							<tr>
								<td align="right" colspan="2"></td>

								<td align="right">Year:</td>
								<td style="font-weight: bold;"><?php echo $inv['year']; ?></td>

								<td align="right">Term: <?php echo $inv['term'];?></td>
								<td style="font-weight: bold;"></td>


							</tr>

							<tr>
								<td align="right">Title:</td>
								<td colspan="3" style="font-weight: bold;"><?php echo $inv['title']; ?></td>

								<td align="right">Amount Due:</td>
								<td align="right" style="font-weight: bold;"><?php echo numfmt_format_currency($fmt, $inv['due'], $currency);?></td>
							</tr>
						<?php 
						$total += $inv['due'];
						endforeach; ?>
						<tr>
							<td colspan="4"></td>
							<td align="right"><strong>TOTAL:</strong></td>
							<td align="right"><strong><?php echo numfmt_format_currency($fmt, $total, $currency);?></strong></td>
						</tr>
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

				<?php echo form_open(site_url('parents/mobile_money_checkout/'.$student_id), array('class' => 'form-horizontal form-group-bordered', 'id' => 'momo_payment_form')) ?>
					<div class="form-group">
						<label class="form-control-label col-sm-6">Your Registered Momo Name:</label>
						<div class="col-sm-6">
							<input type="text" name="momo_user_name" class="form-control" required="required" >
						</div>
					</div>
					<div class="form-group">
						<label class="form-control-label col-sm-2">Mobile#:</label>
						<div class="col-sm-5">
							<input type="tel" placeholder="Enter your momo number" name="momo_number" class="form-control" required="required">
						</div>

						<label class="form-control-label col-sm-2">Amount#:</label>
						<div class="col-sm-3">
							<input type="number" step="any" placeholder="Enter the amount you want to pay" name="mo_a_paid" min="1" class="form-control" required="required">
						</div>

						<input type="hidden" name="invoice_code" value="<?php echo $invoice_code; ?>">
						<input type="hidden" name="student_name" value="<?php echo strtoupper($this->db->get_where('student', array('student_id' => $param3))->row()->name); ?>">
						<input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
						<input type="hidden" name="mo_timestamp" value="<?php echo date('d-M-Y, H:i:s');?>"/>
						<input type="hidden" name="title" value="<?php echo $inv['title'];?>">
            <input type="hidden" name="description" value="<?php echo $inv['description'];?>">

					</div>

					<div class="form-group">
						<label class="form-control-label col-sm-2">Network:</label>
						<div class="col-sm-5">
							<select name="momo_channel" id="momo_channel" class="form-control" required="required" onChange="updatePaymentLogo($(this).val())">
								<option value=''>Select Network</option>
								<option value='mtn-gh'>MTN Money</option>
								<option value='vodafone-gh'>Vodafone Cash</option>
								<option value='tigo-gh'>AirtelTigo Money</option>
							</select>
						</div>

						<div class="col-sm-2" id="channel_logo"></div>

						<div class="col-sm-2">
							<input type="submit" class="btn btn-primary" id="submit_payment" name="submit_payment" value="Submit Payment">
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>	
</div>

<script type="text/javascript">

	function updatePaymentLogo(val) {
			$.ajax({
				url: '<?= site_url('parents/get_channel_logo/'); ?>' + val,
				type: 'GET',
				dataType: 'html',
				cache: false,
			})
			.done(function(response) {
				$('#channel_logo').html(response);
			})
			.fail(function(err) {
				$('#channel_logo').html(err.responseText);
			})
		}

	$(function(event) {

		let myInterval;

		$('#momo_payment_form').submit(function(ev) {
		ev.preventDefault();
		//Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 
      
      let jsonData;
      let callbackData;

      showAjaxModal_alert('<div style="font-size: 15px; text-align: center; margin-top: -10px;">Sending your request. Please wait...</em></u></div>', 'Loading');

		$.ajax({
			url: $(this).attr('action'),
			type: 'post',
			dataType: 'json',
			data: new FormData(this),
			processData: false,
			contentType: false,
			cache: false
		})
		.done(function(response) {
		    jsonData = JSON.parse(response.json);

		    showAjaxModal_alert('<div style="font-size: 15px;">' + jsonData.Message + '</div>', 'Loading');
		    console.log(jsonData);

		    myInterval = setInterval(() => {
		    	$.ajax({
		    		url:'https://webhook.site/b5c596bc-9a74-4f6f-ae76-99fe048b83f6',
		    		type: 'post',
		    		dataType: 'json',
		    		cache: false,

		    	})
		    	.done(function(resp) {

		    		callbackData = JSON.parse(resp);
		    		console.log('data is here: ' + callbackData);

		    		if(resp != null || resp != '') {

		    			clearInterval(myInterval);
		    		}
		    	})
		    	.fail(function(err) {
		    		console.warn(err.responseText);
		    	})
		    }, 1000);
		    return;
			
			setTimeout(() => {
				window.open(jsonData.data.paylinkUrl, '_blank');

				showAjaxModal_alert('<div style="font-size: 15px; text-align: center; margin-top: -10px;">Waiting for your payment approval.</div>', 'Loading');
			}, 2000);
		})
		.fail(function(err) {
			showAjaxModal_alert('Error: ' + err.responseText, 'Error');
		})

	})
	})
</script>

