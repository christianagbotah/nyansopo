<?php 
	$mobile_money_account_name = $this->db->get_where('settings', array('type' => 'mo_account_name'))->row()->description;
	$mobile_money_account_number = $this->db->get_where('settings', array('type' => 'mo_account_number'))->row()->description;

	$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    $invoice = $this->db->get_where('mobile_money_payment' , array('student_id' => $student_id, 'invoice_id' => $invoice_id, 'timestamp' => $timestamp))->result_array();
    $invoice2 = $this->db->get_where('invoice' , array('student_id' => $student_id, 'invoice_id' => $invoice_id))->result_array();

    foreach($invoice2 as $inv2) {

    }
    $amount_due = $inv2['due'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Mobile Money Payment Alert</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css');?>">
</head>
<body>
<div class="row">

    <!--payment status modal alert here-->
            <div class="modal fade modal-lg modal-sm" id="mobile_money_payment" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 100%" >
                <div class="modal-dialog">
                    <div class="modal-content modal-lg modal-sm" style="width: 100%;">
                        <div class="modal-header" style="background-color: red;">
                            <h4 style="color: #fff;" class="modal-title" id="myModalLabel" align="center">Mobile Money Payment Alert!</h4>
                        </div>
                        <div class="modal-body">
					        <div class="row">
								<div class="col-sm-12">
									<div class=" panel panel-info">
										<div class="panel-heading"><h4 class="panel-title">Mobile Money Payment Rquest Details</h4></div>
										<div class="panel-body">
											<strong>Invoice Details:</strong>
											<table class="table table-bordered table-hover table-active">
												<tbody>
													<?php foreach($invoice as $inv): ?>

													<?php endforeach; ?>
														<tr>
															<td colspan="6" align="center"><strong>INVOICE TITLE: <?= $inv['title']; ?></strong></td>
														</tr>
														<tr>
															<td align="right">Invoice#:</td>
															<td style="font-weight: bold;"><?php echo $inv['invoice_code']; ?></td>

															<td align="right">Year:</td>
															<td style="font-weight: bold;"><?php echo explode('-', $inv['year'])[1]; ?></td>

															<td align="right">Term:</td>
															<td style="font-weight: bold;"><?php echo $inv['term'];?></td>


														</tr>

														<tr>
															<td align="right">Student:</td>
															<td colspan="2" style="font-weight: bold;"><?php echo ucwords(strtolower($this->db->get_where('student', array('student_id' => $student_id))->row()->name)); ?></td>

															<td align="right">Parent:</td>
															<td colspan="2" style="font-weight: bold;">
																<?php 
																	$parent_id =$this->db->get_where('student', array('student_id' => $student_id))->row()->parent_id;
																	echo ucwords(strtolower($this->db->get_where('parent', array('parent_id' => $parent_id))->row()->name))
																?>		
															</td>															
														</tr>

														<tr>
															<td align="right">Amount Paid:</td>
															<td style="font-weight: bold;"><?php echo numfmt_format_currency($fmt, $inv['mo_a_paid'], $currency);?></td>

															<td align="right">Date & Time:</td>
															<td colspan="3" style="font-weight: bold;">
																<?php 
																	echo  $inv['date_time'];

																 ?>

															</td>															
														</tr>
												</tbody>
											</table>
											<hr>
											<div class="panel panel-danger" data-toggle="collapse" data-target="#info1">
												<div class="panel-heading"><p>Click here for a Quick Guide. <span class="glyphicon glyphicon-info-sign pull-right"></span></p></div>
												<div class="panel-body collapse" id="info1">
													<em>
														<small>
															<ol>
																<li>Carefully review the invoice details above</li>
																<li>Compare the amount paid and the amount you received on your mobile phone</li>
																<li>Enter the Transaction ID on your mobile phone in the field provided below</li>
																<li>Click on the 'Approve Payment' button to approve the transaction</li>
															</ol>
														</small>
													</em>
												</div>
											</div>

											<?php if(validation_errors()) :?>
												<div class="alert alert-danger alert-dismissible" role="alert">
												    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
												    </button>
												   <strong> <?php echo validation_errors(); ?></strong>
												</div>
											<?php endif;?>	

											<?php echo form_open(site_url('admin/modal_mobile_money_checkout/confirm_t_id/'.$invoice_id), array('class' => 'form-horizontal form-group-bordered')) ?>
												<div class="form-group">
													<label class="form-control-label col-sm-6">Transaction ID from Parent:</label>
													<div class="col-sm-6">
														<h4 id="t_id"><?php echo $inv['t_id']; ?></h4>
													</div>
												</div>
												<div class="form-group">
													<label class="form-control-label col-sm-6">Enter Transaction ID Received:</label>
													<div class="col-sm-6">
														<input type="text" name="confirm_t_id" id="confirm_t_id" class="form-control" maxlength="10" onkeyup="confirm_transaction()" minlength="10" required="required" autocomplete="false">
													</div>	
												</div>

												<input type="hidden" name="invoice_id" value="<?php echo $invoice_id;?>">
						                    	<input type="hidden" name="student_id" value="<?php echo $student_id;?>">
												<input type="hidden" name="timestamp" value="<?php echo $inv['timestamp']; ?>">
												<input type="hidden" name="date_time" value="<?php echo $inv['date_time']; ?>">
												<input type="hidden" name="invoice_code" value="<?php echo $inv['invoice_code'];?>"/>
												<input type="hidden" name="year" value="<?php echo $inv['year'];?>">
						                    	<input type="hidden" name="term" value="<?php echo $inv['term'];?>">
						                    	<input type="hidden" name="amount_paid" value="<?php echo $inv['mo_a_paid'];?>">
						                    	<input type="hidden" name="title" value="<?php echo $inv2['title'];?>">
						                    	<input type="hidden" name="description" value="<?php echo $inv2['description'];?>">
						                    	<input type="hidden" name="status" id="status" value="">

												<div class="row" >
													<div class="col-sm-12 col-xs-12" id="t_id_alert"></div>
													<div class="col-sm-12 col-xs-12">
														<div class="col-sm-6 col-xs-6" align="right">
															<input type="submit" id="confirm_btn" class="btn btn-primary" name="confirm_transaction_id" value="Confirm Transaction ID">
														</div>
														<div class="col-sm-6 col-xs-6">
															<a href="<?php echo site_url('admin/'.$page_name); ?>" class="btn btn-danger">Cancel Transaction</a>
														</div>
													</div>
												</div>
												
											</form>
										</div>
									</div>
								</div>	
							</div>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

	<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.min.js'); ?>"></script>

</body>
</html>

<script type="text/javascript">
    $(document).ready(function() {
        $('#mobile_money_payment').modal('show');

        $('#confirm_btn').attr('disabled', 'disabled')
    });

    $('#mobile_money_payment').modal({
        	backdrop: 'static',
        	keyboard: 'false'
        });

     //use ajax to confirm transaction id
 	function confirm_transaction() {
 		var student_id = '<?php echo $student_id; ?>';
 		var invoice_id = '<?php echo $invoice_id; ?>';
 		var parent_t_id = '<?php echo $inv['t_id']; ?>';
 		var admin_t_id  =  $('#confirm_t_id').val();

 		//send request to via ajax
 		$.ajax({
 			url: '<?php echo site_url('admin/confirm_transaction/') ?>' + student_id + '/' + invoice_id + '/' + parent_t_id + '/' + admin_t_id,
 			success: function(confirmation_t) {

 					if(admin_t_id.length == 10 && confirmation_t == 'TRUE') {
 						$('#t_id_alert').html('<div class="alert alert-success" role="alert">'+
                            '<span class="glyphicon glyphicon-ok"></span> Transaction ID confirmation successful.'+
                           ' Click the "Approve Payment" to process payment. </div>');
 						$('#confirm_btn').removeAttr('disabled');
 						$('#confirm_btn').val('Approve Payment');

 					}else if(admin_t_id.length == 10 && confirmation_t == 'FALSE') {
 						$('#t_id_alert').html('<div class="alert alert-danger" role="alert">'+
                            '<span class="glyphicon glyphicon-remove"></span> Transaction ID confirmation failed. No such Transaction ID found. </div>');
 						$('#confirm_btn').attr('disabled', 'disabled');
 						$('#confirm_btn').val('Confirmation Failed!');

 					}else{
 						$('#t_id_alert').html('');
 						$('#confirm_btn').attr('disabled', 'disabled');
 						$('#confirm_btn').val('Confirm Transaction ID');
 					}

 			}
 		});
 	}

 	//payment status...
 	
 	$(document).ready(function() {
 		var amount_due = '<?php echo $amount_due; ?>';
        var amount_paid  = '<?php echo $inv['mo_a_paid'];?>';
        var bal   = Number(amount_due) - Number(amount_paid);


            if(bal == 0) {
                $('#status').val('Paid');
            }else if(bal > 0) {
                $('#status').val('Unpaid');
            }else if(bal < 0) {
               $('#status').val('Over paid');
            }
 	});

</script>
