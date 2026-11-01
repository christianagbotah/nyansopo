<style>
.expense-table { width:100%; border-collapse:collapse; }
.expense-table th { background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color:white; padding:12px 8px; font-weight:600; font-size:13px; border:1px solid #fecaca; position:sticky; top:0; z-index:10; }
.expense-table td { padding:8px; border:1px solid #fee2e2; vertical-align:middle; }
.expense-table tbody tr:hover { background:#fef2f2; }
.expense-table input, .expense-table select { width:100%; padding:6px 8px; border:1px solid #fecaca; border-radius:4px; font-size:13px; }
.expense-table input:focus, .expense-table select:focus { border-color:#dc2626; outline:none; box-shadow:0 0 0 3px rgba(220,38,38,0.1); }
.select-checkbox { width:20px; height:20px; cursor:pointer; accent-color:#dc2626; }
</style>

<?php if(empty($expenses)): ?>
<div style="text-align:center; padding:40px; color:#6b7280;">
	<i class="entypo-info" style="font-size:48px; color:#d1d5db;"></i>
	<p style="margin-top:16px; font-size:16px;">No expenses found. Try adjusting the date filter.</p>
</div>
<?php else: ?>

<?php echo form_open('admin/expense_bulk_update', array('id' => 'bulkEditForm'));?>
<div style="max-height:65vh; overflow-y:auto;">
	<table class="expense-table">
		<thead>
			<tr>
				<th style="width:5%;"><input type="checkbox" id="selectAll" class="select-checkbox"></th>
				<th style="width:20%;">Title *</th>
				<th style="width:15%;">Category *</th>
				<th style="width:20%;">Description</th>
				<th style="width:10%;">Amount *</th>
				<th style="width:12%;">Method *</th>
				<th style="width:13%;">Date *</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($expenses as $index => $exp): ?>
			<tr>
				<td>
					<input type="checkbox" name="selected[]" value="<?php echo $exp['payment_id']; ?>" class="row-checkbox select-checkbox">
					<input type="hidden" name="expenses[<?php echo $index; ?>][id]" value="<?php echo $exp['payment_id']; ?>">
				</td>
				<td><input type="text" name="expenses[<?php echo $index; ?>][title]" value="<?php echo $exp['title']; ?>" required></td>
				<td>
					<select name="expenses[<?php echo $index; ?>][category_id]" required>
						<?php foreach($categories as $cat): ?>
						<option value="<?php echo $cat['expense_category_id']; ?>" <?php echo $cat['expense_category_id'] == $exp['expense_category_id'] ? 'selected' : ''; ?>>
							<?php echo $cat['name']; ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
				<td><input type="text" name="expenses[<?php echo $index; ?>][description]" value="<?php echo $exp['description']; ?>"></td>
				<td><input type="number" step="0.01" name="expenses[<?php echo $index; ?>][amount]" value="<?php echo $exp['amount']; ?>" required></td>
				<td>
					<select name="expenses[<?php echo $index; ?>][method]" required>
						<?php
						$this->load->helper('payment_method');
						$payment_methods = get_payment_methods();
						foreach($payment_methods as $method):
						?>
						<option value="<?php echo $method->id; ?>" <?php echo $exp['payment_method'] == $method->id ? 'selected' : ''; ?>>
							<?php echo $method->name; ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
				<td><input type="text" class="datepicker" name="expenses[<?php echo $index; ?>][date]" value="<?php echo date('d-m-Y', $exp['day_timestamp']); ?>" required></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div style="margin-top:20px; padding-top:20px; border-top:2px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
	<button type="button" class="btn btn-danger" onclick="bulkDelete()">
		<i class="entypo-trash"></i> Delete Selected
	</button>
	<div style="color:#6b7280; font-size:14px;">
		Showing <?php echo count($expenses); ?> expenses (max 50)
	</div>
	<button type="submit" class="btn btn-info">
		<i class="entypo-check"></i> Update All
	</button>
</div>
<?php echo form_close();?>

<script>
// Change modal header to expense theme
$('#modal_ajax .modal-header').css({
	'background': 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)',
	'border': 'none'
});
$('#modal_ajax .modal-title').html('<i class="fa fa-edit"></i> Bulk Edit Expenses');

$(document).ready(function() {
	$('.datepicker').datepicker({format: 'dd-mm-yyyy', autoclose: true});
});

$('#selectAll').change(function() {
	$('.row-checkbox').prop('checked', $(this).prop('checked'));
});

$('#bulkEditForm').submit(function(e) {
	e.preventDefault();
	
	var $btn = $(this).find('button[type="submit"]');
	var originalText = $btn.html();
	$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
	
	$.ajax({
		url: $(this).attr('action'),
		type: 'POST',
		data: $(this).serialize(),
		dataType: 'json'
	}).done(function(response) {
		if(response.status === 'success') {
			// Close modal
			$('#modal_ajax').modal('hide');
			
			// Show success message
			setTimeout(function() {
				showAjaxModal_alert(response.message, 'success', false);
			}, 300);
			
			// Reload DataTable
			if(typeof $('#expenses').DataTable === 'function') {
				var table = $('#expenses').DataTable();
				if(table) {
					table.ajax.reload(null, false);
					
					// Update total after reload
					table.on('xhr', function() {
						var json = table.ajax.json();
						if(json.totalExpenses) {
							$('#totalExpenses').text(json.totalExpenses);
						}
					});
				}
			}
		} else {
			showAjaxModal_alert(response.message || 'An error occurred', 'error');
			$btn.prop('disabled', false).html(originalText);
		}
	}).fail(function(xhr) {
		showAjaxModal_alert('An error occurred. Please try again.', 'error');
		$btn.prop('disabled', false).html(originalText);
	});
});

function bulkDelete() {
	const selected = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
	
	if(selected.length === 0) {
		showAjaxModal_alert('Please select expenses to delete', 'warning');
		return;
	}
	
	showConfirmModal(
		'Delete Expenses',
		'Are you sure you want to delete ' + selected.length + ' expense(s)? This action cannot be undone.',
		function() {
			// User confirmed, proceed with delete
			showAjaxModal_alert('Deleting expenses...', 'loading');
			
			$.ajax({
				url: '<?php echo site_url('admin/expense_bulk_delete'); ?>',
				type: 'POST',
				data: { ids: selected },
				dataType: 'json'
			}).done(function(response) {
				if(response.status === 'success') {
					// Close modal
					$('#modal_ajax').modal('hide');
					
					// Show success message
					setTimeout(function() {
						showAjaxModal_alert(response.message, 'success', false);
					}, 300);
					
					// Reload DataTable
					if(typeof $('#expenses').DataTable === 'function') {
						var table = $('#expenses').DataTable();
						if(table) {
							table.ajax.reload(null, false);
							
							// Update total after reload
							table.on('xhr', function() {
								var json = table.ajax.json();
								if(json.totalExpenses) {
									$('#totalExpenses').text(json.totalExpenses);
								}
							});
						}
					}
				} else {
					showAjaxModal_alert(response.message || 'An error occurred', 'error');
				}
			}).fail(function(xhr) {
				showAjaxModal_alert('An error occurred. Please try again.', 'error');
			});
		},
		'Delete',
		'danger'
	);
}
</script>

<?php endif; ?>
