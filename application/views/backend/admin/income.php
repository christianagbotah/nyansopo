<hr />
<div class="row">
	<div class="col-md-12">
		<a href="<?php echo site_url($this->session->userdata('login_type') . '/invoices_show');?>" class="btn btn-<?php if($inner == 'invoices' || $inner == 'invoices_loaded') { echo 'success'; } else { echo 'info'; }; ?>">
			<?php echo get_phrase('invoices');?>
		</a>
		<a href="<?php echo site_url($this->session->userdata('login_type') . '/income/payment_history');?>" class="btn btn-<?php echo $inner == 'payment_history' ? 'success' : 'info'; ?>">
			<?php echo get_phrase('payment_history');?>
		</a>
		<a href="<?php echo site_url($this->session->userdata('login_type') . '/income/student_specific_payment_history');?>" class="btn btn-<?php echo $inner == 'student_specific_payment_history' ? 'success' : 'info'; ?>">
			<?php echo get_phrase('student_specific_payment_history');?>
		</a>
	</div>	
</div>
<hr>
<?php include $inner.'.php'; ?>

