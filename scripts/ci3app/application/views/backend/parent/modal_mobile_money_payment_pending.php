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
    <title>Mobile Money Payment Status</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css');?>">
</head>
<body>
<div class="row">

    <!--payment status modal alert here-->
            <div class="modal fade modal-lg modal-sm" id="mobile_money_payment2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 100%" >
                <div class="modal-dialog">
                    <div class="modal-content modal-lg modal-sm" style="width: 100%;">
                        <div class="modal-header" style="background-color: red;">
                            <h4 style="color: #fff;" class="modal-title" id="myModalLabel" align="center">Mobile Money Payment Status</h4>
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
															<td style="font-weight: bold;"><?php echo $inv['year']; ?></td>

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
											<?php echo form_open(site_url('#'), array('class' => 'form-horizontal form-group-bordered')) ?>
												<div class="form-group">
													<div class="form-control-label col-sm-6">Transaction ID Submitted:</div>
													<div class="col-sm-6">
														<h4 id="t_id"><?php echo $inv['t_id']; ?></h4>
													</div>
												</div>
												<div class="form-group">
													<div class="form-control-label col-sm-6">Transaction Status:</div>
													<div class="col-sm-6">
														<h4 style="color: red;">Pending Approval...</h4>
													</div>	
												</div>
												<div class="row">
													<div class="col-sm-12 ">
														<a href="<?php echo site_url($this->session->userdata('login_type') == 'parent'?'parents/'.$page_name : 'student/'.$page_name);?>" class="btn btn-danger pull-right">Back</a>
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
        $('#mobile_money_payment2').modal('show');
    });

    $('#mobile_money_payment2').modal({
        	backdrop: 'static',
        	keyboard: 'false'
        });


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
