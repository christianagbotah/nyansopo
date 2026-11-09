<style>
.expense-table { width:100%; border-collapse:collapse; font-size:14px; }
.expense-table th { background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color:white; padding:14px 10px; font-weight:700; font-size:14px; border:1px solid #fecaca; }
.expense-table td { padding:12px 10px; border:1px solid #fee2e2; vertical-align:middle; }
.expense-table tbody tr:hover { background:#fef2f2; }
.expense-table input, .expense-table select { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; font-family:inherit; }
.expense-table input:focus, .expense-table select:focus { border-color:#dc2626; outline:none; box-shadow:0 0 0 3px rgba(220,38,38,0.1); }
.remove-btn { color:#dc2626; cursor:pointer; font-size:20px; transition:all 0.2s; }
.remove-btn:hover { color:#991b1b; transform:scale(1.2); }
</style>

<?php echo form_open('admin/expense_bulk_create', array('id' => 'bulkExpenseForm'));?>
<div style="max-height:65vh; overflow-y:auto;">
	<table class="expense-table">
		<thead>
			<tr>
				<th style="width:5%;">#</th>
				<th style="width:20%;">Title *</th>
				<th style="width:15%;">Category *</th>
				<th style="width:20%;">Description</th>
				<th style="width:10%;">Amount *</th>
				<th style="width:12%;">Method *</th>
				<th style="width:13%;">Date *</th>
				<th style="width:5%;"></th>
			</tr>
		</thead>
		<tbody id="expenseRows">
			<tr data-row="1">
				<td>1</td>
				<td><input type="text" name="expenses[0][title]" required></td>
				<td>
					<select name="expenses[0][category_id]" required>
						<option value="">Select</option>
						<?php 
						$categories = $this->db->get('expense_category')->result_array();
						foreach ($categories as $row):
						?>
						<option value="<?php echo $row['expense_category_id'];?>"><?php echo $row['name'];?></option>
						<?php endforeach;?>
					</select>
				</td>
				<td><input type="text" name="expenses[0][description]"></td>
				<td><input type="number" step="0.01" name="expenses[0][amount]" required></td>
				<td>
					<select name="expenses[0][payment_method]" required>
						<option value="1">Cash</option>
						<option value="2">Cheque</option>
						<option value="3">Card</option>
						<option value="4">Mobile Money</option>
					</select>
				</td>
				<td><input type="text" class="datepicker" name="expenses[0][date]" required></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</div>

<div style="margin-top:20px; padding-top:20px; border-top:2px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
	<button type="button" class="btn btn-default" onclick="addRow()">
		<i class="entypo-plus"></i> Add Row
	</button>
	<button type="submit" class="btn btn-info">
		<i class="entypo-check"></i> Save All Expenses
	</button>
</div>
<?php echo form_close();?>

<script>
let rowCount = 1;

// Safely get categories data
let categories = [];
try {
	categories = <?php echo json_encode($categories); ?>;
} catch(e) {
	console.error('Error loading categories:', e);
	categories = [];
}

// Define addRow function globally - MUST be accessible from onclick
function addRow() {
	const newRow = `
		<tr data-row="${rowCount}">
			<td>${rowCount + 1}</td>
			<td><input type="text" name="expenses[${rowCount}][title]" required></td>
			<td>
				<select name="expenses[${rowCount}][category_id]" required>
					<option value="">Select</option>
					${categories.map(c => `<option value="${c.expense_category_id}">${c.name}</option>`).join('')}
				</select>
			</td>
			<td><input type="text" name="expenses[${rowCount}][description]"></td>
			<td><input type="number" step="0.01" name="expenses[${rowCount}][amount]" required></td>
			<td>
				<select name="expenses[${rowCount}][payment_method]" required>
					<option value="1">Cash</option>
					<option value="2">Cheque</option>
					<option value="3">Card</option>
					<option value="4">Mobile Money</option>
				</select>
			</td>
			<td><input type="text" class="datepicker" name="expenses[${rowCount}][date]" required></td>
			<td><i class="entypo-cancel remove-btn" onclick="removeRow(this)"></i></td>
		</tr>
	`;
	
	$('#expenseRows').append(newRow);
	$('.datepicker').datepicker({
		format: 'dd-mm-yyyy', 
		autoclose: true,
		container: '#modal_ajax .modal-body',
		zIndexOffset: 100020
	});
	rowCount++;
	updateRowNumbers();
}

function removeRow(btn) {
	if($('#expenseRows tr').length > 1) {
		$(btn).closest('tr').remove();
		updateRowNumbers();
	}
}

function updateRowNumbers() {
	$('#expenseRows tr').each(function(index) {
		$(this).find('td:first').text(index + 1);
	});
}

// Change modal header to expense theme
$(document).ready(function() {
	$('#modal_ajax .modal-header').css({
		'background': 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)',
		'border': 'none'
	});
	$('#modal_ajax .modal-title').html('<i class="fa fa-plus-circle"></i> Bulk Add Expenses');
	
	// Initialize datepicker
	$('.datepicker').datepicker({
		format: 'dd-mm-yyyy', 
		autoclose: true,
		container: '#modal_ajax .modal-body',
		zIndexOffset: 100020
	});
});

$('#bulkExpenseForm').submit(function(e) {
	e.preventDefault();
	
	// Show loading modal immediately
	showAjaxModal_alert('Creating expenses...', 'loading');
	
	var $btn = $(this).find('button[type="submit"]');
	var originalText = $btn.html();
	$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
	
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
</script>
