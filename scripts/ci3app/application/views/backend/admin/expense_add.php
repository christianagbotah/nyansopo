<style>
/* Modern Enterprise Expense Form */
.expense-form-container {
	background: #ffffff;
	border-radius: 12px;
	box-shadow: 0 4px 20px rgba(220, 38, 38, 0.08);
	overflow: hidden;
}

.expense-form-header {
	background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
	padding: 24px 32px;
	border-bottom: 3px solid #b91c1c;
}

.expense-form-title {
	color: white;
	font-size: 24px;
	font-weight: 700;
	margin: 0;
	display: flex;
	align-items: center;
	gap: 12px;
}

.expense-form-title i {
	font-size: 28px;
}

.expense-form-body {
	padding: 32px;
	max-height: 65vh;
	overflow-y: auto;
}

.expense-card {
	background: #fef2f2;
	border: 2px solid #fecaca;
	border-radius: 12px;
	padding: 24px;
	margin-bottom: 20px;
	position: relative;
	transition: all 0.3s;
}

.expense-card:hover {
	border-color: #fca5a5;
	box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
}

.expense-card-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	padding-bottom: 16px;
	border-bottom: 2px solid #fecaca;
}

.expense-card-number {
	font-size: 18px;
	font-weight: 700;
	color: #991b1b;
	display: flex;
	align-items: center;
	gap: 8px;
}

.expense-card-number i {
	color: #dc2626;
}

.remove-expense-btn {
	background: #fee2e2;
	color: #dc2626;
	border: 2px solid #dc2626;
	padding: 8px 16px;
	border-radius: 8px;
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s;
	display: inline-flex;
	align-items: center;
	gap: 6px;
}

.remove-expense-btn:hover {
	background: #dc2626;
	color: white;
	transform: translateY(-1px);
	box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
}

.form-row {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20px;
	margin-bottom: 20px;
}

.form-row.full {
	grid-template-columns: 1fr;
}

.form-field {
	display: flex;
	flex-direction: column;
}

.form-field label {
	font-size: 13px;
	font-weight: 600;
	color: #991b1b;
	margin-bottom: 8px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.form-field label .required {
	color: #dc2626;
	margin-left: 2px;
}

.form-field input,
.form-field select,
.form-field textarea {
	width: 100%;
	padding: 12px 16px;
	border: 2px solid #fecaca;
	border-radius: 8px;
	font-size: 15px;
	font-family: inherit;
	transition: all 0.2s;
	background: white;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
	border-color: #dc2626;
	outline: none;
	box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
}

.form-field textarea {
	resize: vertical;
	min-height: 80px;
}

.expense-form-footer {
	background: #fef2f2;
	padding: 24px 32px;
	border-top: 2px solid #fecaca;
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 16px;
}

.btn-modern {
	padding: 10px 20px;
	border-radius: 6px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s;
	border: none;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.btn-modern:hover {
	transform: translateY(-1px);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.btn-modern i {
	font-size: 14px;
}

.btn-add {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	color: white;
}

.btn-add:hover {
	background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.btn-save {
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
	color: white;
}

.btn-save:hover {
	background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

@media (max-width: 768px) {
	.form-row {
		grid-template-columns: 1fr;
	}
	
	.expense-form-footer {
		flex-direction: column;
	}
	
	.btn-modern {
		width: 100%;
		justify-content: center;
	}
}
</style>

<?php echo form_open(site_url('admin/expense_bulk_create/'), array('id' => 'bulkExpenseForm'));?>
<div class="expense-form-container">
	<div class="expense-form-header">
		<h2 class="expense-form-title">
			<i class="fa fa-plus-circle"></i>
			<?php echo get_phrase('add_expense');?>
		</h2>
	</div>
	
	<div class="expense-form-body" id="expenseRows">
		<!-- Expense Card 1 -->
		<div class="expense-card" data-row="1">
			<div class="expense-card-header">
				<div class="expense-card-number">
					<i class="fa fa-receipt"></i>
					Expense #<span class="row-number">1</span>
				</div>
				<button type="button" class="remove-expense-btn" onclick="window.removeExpenseRow(1)" style="display:none;">
					<i class="fa fa-times"></i> Remove
				</button>
			</div>
			
			<div class="form-row">
				<div class="form-field">
					<label><?php echo get_phrase('title');?> <span class="required">*</span></label>
					<input type="text" name="expenses[1][title]" placeholder="Enter expense title" required>
				</div>
				
				<div class="form-field">
					<label><?php echo get_phrase('category');?> <span class="required">*</span></label>
					<select name="expenses[1][category_id]" required>
						<option value=""><?php echo get_phrase('select_expense_category');?></option>
						<?php 
							$categories = $this->db->get('expense_category')->result_array();
							foreach ($categories as $row):
						?>
						<option value="<?php echo $row['expense_category_id'];?>"><?php echo $row['name'];?></option>
						<?php endforeach;?>
					</select>
				</div>
			</div>
			
			<div class="form-row full">
				<div class="form-field">
					<label><?php echo get_phrase('description');?></label>
					<textarea name="expenses[1][description]" placeholder="Enter expense description (optional)"></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<div class="form-field">
					<label><?php echo get_phrase('amount');?> <span class="required">*</span></label>
					<input type="number" step="0.01" name="expenses[1][amount]" placeholder="0.00" required>
				</div>
				
				<div class="form-field">
					<label><?php echo get_phrase('method');?> <span class="required">*</span></label>
					<select name="expenses[1][payment_method]" required>
						<option value="1"><?php echo get_phrase('cash');?></option>
						<option value="2"><?php echo get_phrase('cheque');?></option>
						<option value="3"><?php echo get_phrase('card');?></option>
						<option value="4"><?php echo get_phrase('mobile_money');?></option>
					</select>
				</div>
			</div>
			
			<div class="form-row">
				<div class="form-field">
					<label><?php echo get_phrase('date');?> <span class="required">*</span></label>
					<input type="text" class="datepicker" name="expenses[1][date]" placeholder="Select date" required>
				</div>
			</div>
		</div>
	</div>
	
	<div class="expense-form-footer">
		<button type="button" class="btn-modern btn-add" onclick="window.addExpenseRow()">
			<i class="fa fa-plus"></i> Add Another Expense
		</button>
		<button type="submit" class="btn-modern btn-save">
			<i class="fa fa-check"></i> Save All Expenses
		</button>
	</div>
</div>
<?php echo form_close();?>

<script>
let rowCount = 1;
const categories = <?php echo json_encode($categories); ?>;

// Define functions globally
window.addExpenseRow = function() {
	rowCount++;
	const newCard = `
		<div class="expense-card" data-row="${rowCount}">
			<div class="expense-card-header">
				<div class="expense-card-number">
					<i class="fa fa-receipt"></i>
					Expense #<span class="row-number">${rowCount}</span>
				</div>
				<button type="button" class="remove-expense-btn" onclick="window.removeExpenseRow(${rowCount})">
					<i class="fa fa-times"></i> Remove
				</button>
			</div>
			
			<div class="form-row">
				<div class="form-field">
					<label><?php echo get_phrase('title');?> <span class="required">*</span></label>
					<input type="text" name="expenses[${rowCount}][title]" placeholder="Enter expense title" required>
				</div>
				
				<div class="form-field">
					<label><?php echo get_phrase('category');?> <span class="required">*</span></label>
					<select name="expenses[${rowCount}][category_id]" required>
						<option value=""><?php echo get_phrase('select_expense_category');?></option>
						${categories.map(c => `<option value="${c.expense_category_id}">${c.name}</option>`).join('')}
					</select>
				</div>
			</div>
			
			<div class="form-row full">
				<div class="form-field">
					<label><?php echo get_phrase('description');?></label>
					<textarea name="expenses[${rowCount}][description]" placeholder="Enter expense description (optional)"></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<div class="form-field">
					<label><?php echo get_phrase('amount');?> <span class="required">*</span></label>
					<input type="number" step="0.01" name="expenses[${rowCount}][amount]" placeholder="0.00" required>
				</div>
				
				<div class="form-field">
					<label><?php echo get_phrase('method');?> <span class="required">*</span></label>
					<select name="expenses[${rowCount}][payment_method]" required>
						<option value="1"><?php echo get_phrase('cash');?></option>
						<option value="2"><?php echo get_phrase('cheque');?></option>
						<option value="3"><?php echo get_phrase('card');?></option>
						<option value="4"><?php echo get_phrase('mobile_money');?></option>
					</select>
				</div>
			</div>
			
			<div class="form-row">
				<div class="form-field">
					<label><?php echo get_phrase('date');?> <span class="required">*</span></label>
					<input type="text" class="datepicker" name="expenses[${rowCount}][date]" placeholder="Select date" required>
				</div>
			</div>
		</div>
	`;
	
	$('#expenseRows').append(newCard);
	$('.datepicker').datepicker({
		format: 'dd-mm-yyyy',
		autoclose: true,
		container: '#modal_ajax .modal-body',
		zIndexOffset: 100020
	});
	
	// Show remove button on first card if more than 1 card
	if(rowCount > 1) {
		$('.expense-card[data-row="1"] .remove-expense-btn').show();
	}
};

window.removeExpenseRow = function(rowNum) {
	if($('.expense-card').length > 1) {
		$(`.expense-card[data-row="${rowNum}"]`).fadeOut(300, function() {
			$(this).remove();
			
			// Hide remove button on first card if only 1 card left
			if($('.expense-card').length === 1) {
				$('.expense-card[data-row="1"] .remove-expense-btn').hide();
			}
			
			// Renumber cards
			$('.expense-card').each(function(index) {
				$(this).find('.row-number').text(index + 1);
			});
		});
	}
};

$(document).ready(function() {
	// Initialize datepicker
	$('.datepicker').datepicker({
		format: 'dd-mm-yyyy',
		autoclose: true,
		container: '#modal_ajax .modal-body',
		zIndexOffset: 100020
	});
	
	// Form submission
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
});
</script>
