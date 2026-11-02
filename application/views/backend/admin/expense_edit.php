<style>
.expense-edit-container { padding: 10px; }
.form-group { margin-bottom: 20px; }
.control-label { font-weight: 600; color: #991b1b; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 1px solid #fecaca; border-radius: 6px; padding: 12px 14px; font-size: 14px; transition: all 0.2s; height: 46px; }
.form-control:focus { border-color: #dc2626; outline: none; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1); }
select.form-control { height: 46px; }
.btn-update { background: #dc2626; color: #fff; border: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s; height: 46px; }
.btn-update:hover { background: #b91c1c; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
.btn-update:disabled { background: #9ca3af; cursor: not-allowed; transform: none; box-shadow: none; }
</style>

<?php 
	$edit_data = $this->db->get_where('payment', array(
		'payment_id' => $param2
	))->result_array();
	foreach ($edit_data as $row):
?>

<div class="expense-edit-container">
	<div class="panel panel-primary" data-collapsed="0">
		<div class="panel-heading">
			<div class="panel-title">
				<i class="entypo-pencil"></i>
				<?php echo get_phrase('edit_expense');?>
			</div>
		</div>
		<div class="panel-body">
			<?php echo form_open(site_url('admin/expense_edit_ajax'), array('class' => 'form-horizontal validate', 'id' => 'expenseEditForm'));?>
				<input type="hidden" name="payment_id" value="<?php echo $row['payment_id'];?>">
				
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo get_phrase('title');?></label>
					<div class="col-sm-9">
						<input type="text" class="form-control" name="title" data-validate="required" 
							data-message-required="<?php echo get_phrase('value_required');?>" 
							value="<?php echo $row['title'];?>" required>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo get_phrase('category');?></label>
					<div class="col-sm-9">
						<select name="expense_category_id" class="form-control" required>
							<option value=""><?php echo get_phrase('select_expense_category');?></option>
							<?php 
								$categories = $this->db->get('expense_category')->result_array();
								foreach ($categories as $row2):
							?>
							<option value="<?php echo $row2['expense_category_id'];?>"
								<?php if ($row['expense_category_id'] == $row2['expense_category_id']) echo 'selected';?>>
								<?php echo $row2['name'];?>
							</option>
							<?php endforeach;?>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo get_phrase('description');?></label>
					<div class="col-sm-9">
						<input type="text" class="form-control" name="description" value="<?php echo $row['description'] ?? '';?>">
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo get_phrase('amount');?></label>
					<div class="col-sm-9">
						<input type="number" step="0.01" class="form-control" name="amount" value="<?php echo $row['amount'];?>" 
							data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" required>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo get_phrase('method');?></label>
					<div class="col-sm-9">
						<select name="method" class="form-control">
							<option value="1" <?php if ($row['payment_method'] == '1' || strtolower($row['payment_method']) == 'cash') echo 'selected';?>><?php echo get_phrase('cash');?></option>
							<option value="2" <?php if ($row['payment_method'] == '2' || strtolower($row['payment_method']) == 'cheque') echo 'selected';?>><?php echo get_phrase('cheque');?></option>
							<option value="3" <?php if ($row['payment_method'] == '3' || strtolower($row['payment_method']) == 'card') echo 'selected';?>><?php echo get_phrase('card');?></option>
							<option value="4" <?php if ($row['payment_method'] == '4' || strtolower($row['payment_method']) == 'mobile money') echo 'selected';?>><?php echo get_phrase('mobile_money');?></option>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo get_phrase('date');?></label>
					<div class="col-sm-9">
						<input type="text" class="datepicker form-control" name="timestamp"
							value="<?php echo date('d M, Y', $row['day_timestamp']);?>"
							data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" required>
					</div>
				</div>

				<div class="form-group">
					<div class="col-sm-offset-3 col-sm-9">
						<button type="submit" class="btn btn-update" id="updateBtn">
							<i class="fa fa-check"></i> <?php echo get_phrase('update');?>
						</button>
					</div>
				</div>
			<?php echo form_close();?>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	$('.datepicker').datepicker({
		format: 'dd M, yyyy',
		autoclose: true
	});
	
	$('#expenseEditForm').submit(function(e) {
		e.preventDefault();
		
		// Show loading modal immediately
		showAjaxModal_alert('Updating expense...', 'loading');
		
		var $btn = $('#updateBtn');
		var originalText = $btn.html();
		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
		
		$.ajax({
			url: $(this).attr('action'),
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json'
		}).done(function(response) {
			if(response.status === 'success') {
				// Close the modal first
				$('#modal_ajax').modal('hide');
				
				// Show success message after modal closes
				setTimeout(function() {
					showAjaxModal_alert(response.message, 'success', false);
				}, 300);
				
				// Reload the expenses DataTable
				if(typeof $('#expenses').DataTable === 'function') {
					var table = $('#expenses').DataTable();
					if(table) {
						table.ajax.reload(null, false);
					}
				}
				
				// Update total expenses display
				if(response.totalExpenses) {
					$('#totalExpenses').text(response.totalExpenses);
				}
			} else {
				showAjaxModal_alert(response.message || 'An error occurred', 'error');
				$btn.prop('disabled', false).html(originalText);
			}
		}).fail(function(xhr, status, error) {
			console.log('AJAX Error:', xhr.responseText);
			showAjaxModal_alert('An error occurred. Please try again.', 'error');
			$btn.prop('disabled', false).html(originalText);
		});
	});
});
</script>

<?php endforeach;?>
