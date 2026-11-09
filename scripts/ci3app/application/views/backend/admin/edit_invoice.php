<?php
$currency = $this->db->get_where('settings', array('type' => 'system_currency'))->row()->description;
?>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-primary" data-collapsed="0">
      <div class="panel-heading">
        <div class="panel-title">
          <i class="fa fa-edit"></i> <?php echo get_phrase('edit_invoice'); ?> #<?php echo $invoice_code; ?>
        </div>
      </div>
      <div class="panel-body">
        
        <!-- Student Information -->
        <div class="bg-blue-50 p-4 rounded-lg mb-6">
          <h3 class="text-xl font-bold mb-3"><i class="fa fa-user"></i> <?php echo get_phrase('student_information'); ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <strong><?php echo get_phrase('name'); ?>:</strong> <?php echo $student_info->name; ?>
            </div>
            <div>
              <strong><?php echo get_phrase('student_code'); ?>:</strong> <?php echo $student_info->student_code; ?>
            </div>
            <div>
              <strong><?php echo get_phrase('invoice_code'); ?>:</strong> #<?php echo $invoice_code; ?>
            </div>
          </div>
        </div>

        <!-- Invoice Items Form -->
        <?php echo form_open(site_url('admin/mass_invoice_create/update'), array('id' => 'edit_invoice_form', 'class' => 'form-horizontal')); ?>
        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
        <input type="hidden" name="invoice_code" value="<?php echo $invoice_code; ?>">
        <input type="hidden" name="year" value="<?php echo $year; ?>">
        <input type="hidden" name="term" value="<?php echo $term; ?>">
        
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
              <tr>
                <th width="5%">#</th>
                <th width="30%"><?php echo get_phrase('item'); ?></th>
                <th width="20%"><?php echo get_phrase('amount'); ?></th>
                <th width="20%"><?php echo get_phrase('amount_paid'); ?></th>
                <th width="20%"><?php echo get_phrase('due'); ?></th>
                <th width="5%"><?php echo get_phrase('action'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $count = 1;
              $total_amount = 0;
              $total_paid = 0;
              $total_due = 0;
              
              foreach($invoice_items as $item): 
                $total_amount += $item['amount'];
                $total_paid += $item['amount_paid'];
                $total_due += $item['due'];
              ?>
              <tr>
                <td><?php echo $count++; ?></td>
                <td>
                  <input type="hidden" name="<?php echo $item['invoice_id']; ?>_title" value="<?php echo $item['title']; ?>">
                  <input type="text" value="<?php echo $item['title']; ?>" class="form-control" readonly>
                </td>
                <td>
                  <div class="input-group">
                    <span class="input-group-addon"><?php echo $currency; ?></span>
                    <input type="number" step="0.01" name="<?php echo $item['invoice_id']; ?>_amount" value="<?php echo $item['amount']; ?>" 
                           class="form-control amount-input" data-row="<?php echo $count-2; ?>" data-id="<?php echo $item['invoice_id']; ?>" required>
                  </div>
                </td>
                <td>
                  <div class="input-group">
                    <span class="input-group-addon"><?php echo $currency; ?></span>
                    <input type="number" step="0.01" name="amount_paid[]" value="<?php echo $item['amount_paid']; ?>" 
                           class="form-control paid-input" data-row="<?php echo $count-2; ?>" readonly>
                  </div>
                </td>
                <td>
                  <div class="input-group">
                    <span class="input-group-addon"><?php echo $currency; ?></span>
                    <input type="number" step="0.01" name="due[]" value="<?php echo $item['due']; ?>" 
                           class="form-control due-input" data-row="<?php echo $count-2; ?>" readonly>
                  </div>
                </td>
                <td class="text-center">
                  <?php if($item['amount_paid'] == 0): ?>
                  <button type="button" class="btn btn-danger btn-sm delete-item" data-id="<?php echo $item['invoice_id']; ?>">
                    <i class="fa fa-trash"></i>
                  </button>
                  <?php else: ?>
                  <span class="text-muted" title="<?php echo get_phrase('cannot_delete_paid_item'); ?>">
                    <i class="fa fa-lock"></i>
                  </span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-100">
              <tr class="font-bold">
                <td colspan="2" class="text-right"><?php echo get_phrase('total'); ?>:</td>
                <td>
                  <div class="input-group">
                    <span class="input-group-addon"><?php echo $currency; ?></span>
                    <input type="text" id="total_amount" value="<?php echo number_format($total_amount, 2); ?>" class="form-control font-bold" readonly>
                  </div>
                </td>
                <td>
                  <div class="input-group">
                    <span class="input-group-addon"><?php echo $currency; ?></span>
                    <input type="text" id="total_paid" value="<?php echo number_format($total_paid, 2); ?>" class="form-control font-bold" readonly>
                  </div>
                </td>
                <td>
                  <div class="input-group">
                    <span class="input-group-addon"><?php echo $currency; ?></span>
                    <input type="text" id="total_due" value="<?php echo number_format($total_due, 2); ?>" class="form-control font-bold" readonly>
                  </div>
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="form-group mt-4">
          <div class="col-md-12 text-right">
            <button type="button" onclick="history.back()" class="btn btn-default btn-lg">
              <i class="fa fa-arrow-left"></i> <?php echo get_phrase('cancel'); ?>
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fa fa-save"></i> <?php echo get_phrase('save_changes'); ?>
            </button>
          </div>
        </div>

        <?php echo form_close(); ?>

      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  
  // Calculate due when amount changes
  $('.amount-input').on('input', function() {
    const row = $(this).data('row');
    const amount = parseFloat($(this).val()) || 0;
    const paid = parseFloat($('.paid-input[data-row="'+row+'"]').val()) || 0;
    const due = amount - paid;
    $('.due-input[data-row="'+row+'"]').val(due.toFixed(2));
    updateTotals();
  });

  // Update totals
  function updateTotals() {
    let totalAmount = 0;
    let totalPaid = 0;
    let totalDue = 0;

    $('.amount-input').each(function() {
      totalAmount += parseFloat($(this).val()) || 0;
    });

    $('.paid-input').each(function() {
      totalPaid += parseFloat($(this).val()) || 0;
    });

    $('.due-input').each(function() {
      totalDue += parseFloat($(this).val()) || 0;
    });

    $('#total_amount').val(totalAmount.toFixed(2));
    $('#total_paid').val(totalPaid.toFixed(2));
    $('#total_due').val(totalDue.toFixed(2));
  }

  // Delete item
  $('.delete-item').on('click', function() {
    const invoiceId = $(this).data('id');
    const row = $(this).closest('tr');
    
    showConfirmModal(
      '<?php echo get_phrase('confirm_delete'); ?>',
      '<?php echo get_phrase('are_you_sure_delete_invoice_item'); ?>',
      function() {
        row.fadeOut(300, function() {
          $(this).remove();
          updateTotals();
        });
      },
      '<?php echo get_phrase('delete'); ?>',
      'danger'
    );
  });

  // Form submission
  $('#edit_invoice_form').on('submit', function(e) {
    e.preventDefault();
    
    showAjaxModal_alert('<?php echo get_phrase('updating_invoice'); ?>...', 'loading');
    
    // Build the serialized IDs string
    let ids = [];
    $('.amount-input').each(function() {
      ids.push($(this).data('id') + '_' + Date.now());
    });
    
    let formData = $(this).serialize() + '&serialized_ids=' + ids.join('-') + 
                   '&student_id=<?php echo $student_id; ?>' +
                   '&invoice_code=<?php echo $invoice_code; ?>' +
                   '&year=<?php echo $year; ?>' +
                   '&term=<?php echo $term; ?>';
    
    $.ajax({
      url: '<?php echo site_url('admin/mass_invoice_create/update/'); ?><?php echo $student_id; ?>/<?php echo $invoice_code; ?>/<?php echo $year; ?>/<?php echo $term; ?>',
      type: 'POST',
      data: formData,
      success: function(response) {
        if(response.includes('UPDATED SUCCESSFULLY') || response.includes('SUCCESS')) {
          showAjaxModal_alert('<?php echo get_phrase('invoice_updated_successfully'); ?>', 'success');
          setTimeout(() => {
            window.location.href = '<?php echo site_url('admin/student_payment'); ?>';
          }, 2000);
        } else {
          showAjaxModal_alert(response || '<?php echo get_phrase('update_failed'); ?>', 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('<?php echo get_phrase('error_occurred'); ?>: ' + xhr.responseText, 'error');
      }
    });
  });

});
</script>
