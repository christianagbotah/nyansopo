<?php
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$currency = get_settings('currency');

// If no student_id provided, show selection interface
if(empty($student_id)) {
?>
<style>
.enterprise-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  overflow: hidden;
}
.enterprise-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 32px;
  color: white;
}
.enterprise-body {
  padding: 32px;
}
.form-group-modern {
  margin-bottom: 24px;
}
.form-group-modern label {
  display: block;
  font-weight: 600;
  font-size: 14px;
  color: #374151;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.form-control-modern {
  height: 56px !important;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 0 16px;
  font-size: 15px;
  font-weight: 500;
  transition: all 0.3s;
  background: #f9fafb;
}
.form-control-modern:focus {
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  outline: none;
}
.form-control-modern:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}
.select2-container--default .select2-selection--single {
  height: 56px !important;
  border: 2px solid #e5e7eb !important;
  border-radius: 12px !important;
  background: #f9fafb !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 52px !important;
  padding-left: 16px !important;
  font-size: 15px !important;
  font-weight: 500 !important;
  color: #374151 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 52px !important;
  right: 12px !important;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
  border-color: #667eea !important;
  background: white !important;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
}
.btn-enterprise {
  height: 56px;
  padding: 0 32px;
  font-size: 15px;
  font-weight: 700;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.btn-enterprise:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
  color: white;
}
.btn-enterprise:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}
.info-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-left: 4px solid #0ea5e9;
  border-radius: 8px;
  font-size: 14px;
  color: #0c4a6e;
  font-weight: 500;
}
</style>

<div class="row">
  <div class="col-md-12">
    <div class="enterprise-card">
      <div class="enterprise-header">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div>
            <h2 style="margin: 0; font-size: 32px; font-weight: 700; color: white;">
              <i class="fa fa-tag"></i> <?php echo get_phrase('apply_discount'); ?>
            </h2>
            <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 16px;">Select student and invoice to apply discount profile</p>
          </div>
          <div class="info-badge" style="background: rgba(255,255,255,0.2); border: none; color: white;">
            <i class="fa fa-info-circle"></i>
            <span>Only unpaid invoices shown</span>
          </div>
        </div>
      </div>
      
      <div class="enterprise-body">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group-modern">
              <label><i class="fa fa-calendar"></i> <?php echo get_phrase('academic_year'); ?></label>
              <select id="filter_year" class="form-control-modern select2" required>
                <?php echo populate_academic_year('yes'); ?>
              </select>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="form-group-modern">
              <label><i class="fa fa-calendar-check"></i> <?php echo get_phrase('term'); ?></label>
              <select id="filter_term" class="form-control-modern" required>
                <option value=""><?php echo get_phrase('select_term'); ?></option>
                <?php for($i = 1; $i <= 3; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if($running_term == $i) echo 'selected'; ?>>Term <?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="form-group-modern">
              <label><i class="fa fa-user-graduate"></i> <?php echo get_phrase('student'); ?></label>
              <select id="filter_student" class="form-control-modern select2" disabled>
                <option value=""><?php echo get_phrase('select_year_term_first'); ?></option>
              </select>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="form-group-modern">
              <label><i class="fa fa-file-invoice"></i> <?php echo get_phrase('invoice_code'); ?></label>
              <select id="filter_invoice" class="form-control-modern select2" disabled>
                <option value=""><?php echo get_phrase('select_student_first'); ?></option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="row" style="margin-top: 32px;">
          <div class="col-md-12">
            <div style="display: flex; justify-content: flex-end; align-items: center;">
              <div class="info-badge">
                <i class="fa fa-lightbulb"></i>
                <span>Select student and invoice to view discount application below</span>
              </div>
            </div>
          </div>
        </div>
        
        <div id="invoice_details_section" style="display: none; margin-top: 30px;">
          <div class="enterprise-card">
            <div class="enterprise-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <h4 style="margin: 0; font-size: 24px; font-weight: 700; color: white;">
                    <i class="fa fa-file-invoice-dollar"></i> Invoice & Discount Application
                  </h4>
                  <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">Review invoice details and apply discount profiles</p>
                </div>
                <div style="display: flex; gap: 10px;">
                  <button type="button" id="view-invoice-btn-new" class="btn btn-info" style="font-weight: bold; display: none;">
                    <i class="fa fa-file-invoice"></i> <?php echo get_phrase('view_invoice'); ?>
                  </button>
                  <button type="button" id="take-payment-btn-new" class="btn btn-success" style="font-weight: bold; display: none;">
                    <i class="fa fa-credit-card"></i> <?php echo get_phrase('take_payment'); ?>
                  </button>
                </div>
              </div>
            </div>
            <div class="enterprise-body">
              <div id="invoice_details_content"></div>
            </div>
          </div>
        </div>
        
        <!-- Discount Profile Section -->
        <div id="discount-profile-section" style="display: none; margin-top: 30px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 16px; padding: 25px;">
          <h5 style="color: #0c4a6e; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fa fa-tag" style="color: #0ea5e9;"></i>
            <?php echo get_phrase('assign_discount_profile'); ?>
          </h5>
          <?php echo form_open('', array('id' => 'profile-discount-form')); ?>
            <input type="hidden" name="student_id" id="profile_student_id">
            <input type="hidden" name="invoice_code" id="profile_invoice_code">
            <div class="form-group">
              <label style="font-weight: 600; color: #374151; margin-bottom: 10px;"><?php echo get_phrase('select_discount_profile'); ?></label>
              <select name="profile_id" id="discount_profile_select" class="form-control" style="height: 50px; border: 2px solid #3b82f6; border-radius: 10px; font-weight: 600;" required>
                <option value=""><?php echo get_phrase('select_profile'); ?></option>
                <?php
                $profiles = $this->db->where('is_active', 1)->where('discount_category', 'invoice')->get('discount_profiles')->result_array();
                foreach($profiles as $profile):
                  $method_display = $profile['discount_method'] === 'percentage' ? $profile['discount_value'] . '%' : $currency . ' ' . number_format($profile['discount_value'], 2);
                ?>
                <option value="<?php echo $profile['profile_id']; ?>" 
                        data-method="<?php echo $profile['discount_method']; ?>" 
                        data-value="<?php echo $profile['discount_value']; ?>">
                  <?php echo $profile['profile_name'] . ' (' . $method_display . ')'; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="profile-preview" style="display: none; background: white; border-radius: 10px; padding: 20px; margin: 15px 0; border: 2px solid #10b981; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);"></div>
            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 30px; font-weight: 700; border-radius: 10px;">
              <i class="fa fa-check-circle"></i> <?php echo get_phrase('assign_profile'); ?>
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  
  // Auto-load students on page load
  var initialYear = $('#filter_year').val();
  var initialTerm = $('#filter_term').val();
  if(initialYear && initialTerm) {
    loadStudents();
  }
  
  $('#filter_year, #filter_term').change(function() {
    var year = $('#filter_year').val();
    var term = $('#filter_term').val();
    if(year && term) {
      loadStudents();
    }
  });
  
  function loadStudents() {
    var year = $('#filter_year').val();
    var term = $('#filter_term').val();
    
    if(!year || !term) return;
    
    $('#filter_student').html('<option value="">Loading...</option>').prop('disabled', true);
    
    $.ajax({
      url: '<?php echo site_url('admin/get_students_with_unpaid_invoices'); ?>',
      type: 'POST',
      data: {year: year, term: term},
      success: function(response) {
        $('#filter_student').html(response).prop('disabled', false);
        if($('#filter_student').hasClass('select2-hidden-accessible')) {
          $('#filter_student').select2('destroy');
        }
        $('#filter_student').select2();
      },
      error: function(xhr, status, error) {
        $('#filter_student').html('<option value="">Error loading</option>').prop('disabled', false);
      }
    });
  }
  
  $(document).on('change', '#filter_student', function() {
    var student_id = $(this).val();
    var year = $('#filter_year').val();
    var term = $('#filter_term').val();
    
    if(student_id) {
      $('#filter_invoice').html('<option value="">Loading...</option>').prop('disabled', true);
      if($('#filter_invoice').hasClass('select2-hidden-accessible')) {
        $('#filter_invoice').select2('destroy');
      }
      
      $.ajax({
        url: '<?php echo site_url('admin/get_student_unpaid_invoices'); ?>',
        type: 'POST',
        data: {student_id: student_id, year: year, term: term},
        success: function(response) {
          $('#filter_invoice').html(response).prop('disabled', false).select2();
          
          var firstInvoice = $('#filter_invoice option:eq(1)').val();
          if(firstInvoice) {
            $('#filter_invoice').val(firstInvoice).trigger('change');
          }
        },
        error: function(xhr, status, error) {
          $('#filter_invoice').html('<option value="">Error loading</option>').prop('disabled', false).select2();
        }
      });
    } else {
      $('#filter_invoice').html('<option value=""><?php echo get_phrase('select_student_first'); ?></option>').prop('disabled', true);
      $('#load_invoice_btn').prop('disabled', true);
    }
  });
  
  $(document).on('change', '#filter_invoice', function() {
    var invoice_code = $(this).val();
    var student_id = $('#filter_student').val();
    
    if(invoice_code && student_id) {
      $('#invoice_details_section').show();
      $('#invoice_details_content').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
      $('#profile_student_id').val(student_id);
      $('#profile_invoice_code').val(invoice_code);
      
      $('#view-invoice-btn-new').show().off('click').on('click', function() {
        viewInvoiceDetails(invoice_code);
      });
      
      $('#take-payment-btn-new').show().off('click').on('click', function() {
        invoice_pay_modal(student_id);
      });
      
      // Load invoice details
      $.ajax({
        url: '<?php echo site_url('admin/get_invoice_details'); ?>',
        type: 'POST',
        data: {invoice_code: invoice_code, student_id: student_id},
        success: function(invoiceHtml) {
          $('#invoice_details_content').html(invoiceHtml);
          $('#discount-profile-section').show();
          
          // Load discount summary
          $.ajax({
            url: '<?php echo site_url('admin/get_invoice_discount_summary'); ?>',
            type: 'POST',
            data: {invoice_code: invoice_code},
            dataType: 'json',
            success: function(discountData) {
              if(discountData.has_discount) {
                var hasPending = discountData.details.some(d => d.status === 'pending');
                var bgGradient = hasPending ? 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)' : 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                var borderColor = hasPending ? '#f59e0b' : '#10b981';
                var iconColor = hasPending ? '#92400e' : '#065f46';
                var titleColor = hasPending ? '#92400e' : '#065f46';
                var titleText = hasPending ? '<i class="fa fa-exclamation-triangle"></i> Discount Awaiting Approval' : 'Active Discount Applied';
                
                var badge = '<div style="margin-bottom: 15px; padding: 15px; background: ' + bgGradient + '; border-left: 4px solid ' + borderColor + '; border-radius: 8px;">';
                badge += '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">';
                badge += '<i class="fa fa-tag" style="color: ' + iconColor + '; font-size: 20px;"></i>';
                badge += '<div style="font-weight: 700; color: ' + titleColor + '; font-size: 16px;">' + titleText + '</div>';
                badge += '</div>';
                
                discountData.details.forEach(function(disc) {
                  var itemBorder = disc.status === 'approved' ? '#10b981' : '#f59e0b';
                  var itemBg = disc.status === 'pending' ? '#fffbeb' : 'white';
                  badge += '<div style="background: ' + itemBg + '; padding: 12px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid ' + itemBorder + ';">';
                  badge += '<div style="display: flex; justify-content: space-between; align-items: start; gap: 15px;">';
                  badge += '<div style="flex: 1;">';
                  badge += '<div style="font-weight: 700; color: #065f46; font-size: 14px; margin-bottom: 4px;">' + disc.profile_name + '</div>';
                  badge += '<div style="color: #047857; font-size: 13px; margin-bottom: 3px;"><strong>Method:</strong> ' + (disc.method === 'percentage' ? 'Percentage' : 'Fixed') + '</div>';
                  badge += '<div style="color: #047857; font-size: 13px; margin-bottom: 3px;"><strong>Value:</strong> ' + (disc.method === 'percentage' ? disc.value + '%' : disc.currency + ' ' + parseFloat(disc.value).toFixed(2)) + '</div>';
                  badge += '<div style="color: #047857; font-size: 13px;"><strong>Applies to:</strong> ' + disc.applies_to + '</div>';
                  badge += '</div>';
                  badge += '<div style="text-align: right;">';
                  var statusBg = disc.status === 'approved' ? '#10b981' : '#f59e0b';
                  badge += '<div style="background: ' + statusBg + '; color: white; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 12px; margin-bottom: 5px;">' + disc.status.toUpperCase() + '</div>';
                  badge += '<div style="font-size: 18px; font-weight: 800; color: #065f46;">' + disc.currency + ' ' + parseFloat(disc.amount).toFixed(2) + '</div>';
                  badge += '</div></div></div>';
                });
                
                if(discountData.approved_amount > 0 || discountData.pending_amount > 0) {
                  badge += '<div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid ' + borderColor + '; display: flex; justify-content: space-between; font-weight: 700;">';
                  if(discountData.approved_amount > 0) {
                    badge += '<span style="color: #065f46;">Total Approved: <?php echo $currency; ?>' + parseFloat(discountData.approved_amount).toFixed(2) + '</span>';
                  }
                  if(discountData.pending_amount > 0) {
                    badge += '<span style="color: #92400e;">Total Pending: <?php echo $currency; ?>' + parseFloat(discountData.pending_amount).toFixed(2) + '</span>';
                  }
                  badge += '</div>';
                }
                badge += '</div>';
                $('#invoice_details_content').prepend(badge);
              }
            }
          });
        }
      });
    } else {
      $('#invoice_details_section').hide();
      $('#discount-profile-section').hide();
      $('#view-invoice-btn-new').hide();
      $('#take-payment-btn-new').hide();
    }
  });
  
  $(document).on('change', '#discount_profile_select', function() {
    var profileId = $(this).val();
    var invoiceCode = $('#profile_invoice_code').val();
    var studentId = $('#profile_student_id').val();
    
    if(profileId && invoiceCode && studentId) {
      $.ajax({
        url: '<?php echo site_url("admin/get_student_class_residence"); ?>',
        type: 'POST',
        data: { student_id: studentId },
        dataType: 'json',
        success: function(studentData) {
          $.ajax({
            url: '<?php echo site_url("admin/getDiscountProfileDetails"); ?>',
            type: 'POST',
            data: { 
              profile_id: profileId, 
              invoice_code: invoiceCode,
              class_id: studentData.class_id,
              residence_type: studentData.residence_type
            },
            dataType: 'json',
            success: function(response) {
              if(response.status === 'success' && response.profile) {
                var methodText = response.profile.discount_method === 'percentage' ? 'Percentage Discount' : 'Fixed Amount Discount';
                var valueText = response.profile.discount_method === 'percentage' 
                  ? '<span style="font-size: 32px; font-weight: 800; color: #059669;">' + response.profile.discount_value + '%</span>' 
                  : '<span style="font-size: 32px; font-weight: 800; color: #059669;"><?php echo $currency; ?> ' + parseFloat(response.profile.discount_value).toFixed(2) + '</span>';
                
                var html = '<div style="display: flex; gap: 20px; align-items: start;">';
                html += '<div style="flex: 0 0 200px; text-align: center; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 10px; border: 2px solid #10b981;">';
                html += '<div style="font-size: 12px; color: #065f46; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">' + methodText + '</div>';
                html += valueText;
                html += '</div>';
                html += '<div style="flex: 1;">';
                html += '<h6 style="color: #065f46; font-weight: 700; margin-bottom: 12px; font-size: 16px;"><i class="fa fa-info-circle"></i> Profile Details</h6>';
                html += '<div style="font-size: 14px; color: #047857; line-height: 1.8;">';
                html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Profile Name:</strong> <span style="font-weight: 600;">' + response.profile.profile_name + '</span></div>';
                
                if(response.profile.bill_item_ids === '*') {
                  html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Applies to:</strong> <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">All Bill Items</span></div>';
                } else if(response.profile.items && response.profile.items.length > 0) {
                  html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Applies to:</strong></div>';
                  html += '<ul style="margin: 5px 0 0 20px; font-size: 13px;">';
                  response.profile.items.forEach(function(item) {
                    html += '<li style="margin-bottom: 4px;"><span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">' + item.discount_type + '</span></li>';
                  });
                  html += '</ul>';
                }
                html += '</div></div></div>';
                $('#profile-preview').html(html).slideDown(300);
              }
            }
          });
        }
      });
    } else {
      $('#profile-preview').slideUp(300);
    }
  });
  
  $(document).on('submit', '#profile-discount-form', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var profileId = $('#discount_profile_select').val();
    var invoiceCode = $('#profile_invoice_code').val();
    var studentId = $('#profile_student_id').val();
    
    $.ajax({
      url: '<?php echo site_url("admin/check_existing_profile"); ?>',
      type: 'POST',
      data: { student_id: studentId, invoice_code: invoiceCode },
      dataType: 'json',
      success: function(check) {
        if(check.has_profile) {
          if(check.profile_id == profileId) {
            showAjaxModal_alert('This profile is already assigned to this invoice', 'warning');
          } else {
            showConfirmModal(
              'Replace Existing Profile?',
              'Student already has "' + check.profile_name + '" assigned to this invoice. The old profile will be REMOVED and replaced with the new one. Continue?',
              function() {
                processProfileAssignment(formData, 'replace');
              },
              'Replace Profile',
              'warning'
            );
          }
        } else {
          processProfileAssignment(formData, 'add');
        }
      }
    });
  });
  
  function processProfileAssignment(formData, action) {
    showAjaxModal_alert('Assigning profile...', 'loading');
    
    $.ajax({
      url: '<?php echo site_url('admin/assign_profile_to_invoice'); ?>',
      type: 'POST',
      data: formData + '&action=' + action,
      dataType: 'json',
      success: function(response) {
        if(response.status === 'success') {
          showAjaxModal_alert(response.message, 'success');
          setTimeout(function() {
            location.reload();
          }, 2000);
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function() {
        showAjaxModal_alert('An error occurred', 'error');
      }
    });
  }
});

function viewInvoiceDetails(invoiceCode) {
  showAjaxModal('<?php echo site_url('modal/popup_professional/modal_view_invoice_professional/'); ?>' + invoiceCode, 'large');
}

function invoice_pay_modal(student_id, date = '', term = '') {
  if(date != '' && term == '') {
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date, 'take_payment');
  } else if(date != '' && term != '') {
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
  } else {
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
  }
}
</script>
<?php
  return;
}

// Continue with existing code if student_id is provided
$student_info = $this->db->get_where('student', array('student_id' => $student_id))->row();
if(!$student_info) {
    echo '<div class="alert alert-danger">Student not found!</div>';
    return;
}
$this->db->select('invoice_code, term, year');
$this->db->distinct();
$this->db->where('student_id', $student_id);
$this->db->where('can_delete !=', 'trash');
$invoice_codes = $this->db->get('invoice')->result_array();
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get discount info for each invoice
foreach($invoice_codes as &$inv) {
    $discount_query = $this->db->select_sum('discount_amount')
        ->where('invoice_code', $inv['invoice_code'])
        ->where('status', 'approved')
        ->get('invoice_discounts');
    $inv['discount_amount'] = $discount_query->row()->discount_amount ?? 0;
    
    // Check for pending discounts
    $pending_query = $this->db->select_sum('discount_amount')
        ->where('invoice_code', $inv['invoice_code'])
        ->where('status', 'pending')
        ->get('invoice_discounts');
    $inv['pending_discount'] = $pending_query->row()->discount_amount ?? 0;
}
?>

<style>
.invoice-card {
  background: #fff;
  border-radius: 8px;
  padding: 15px 20px;
  margin-bottom: 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.3s;
  border-left: 4px solid #3498db;
  font-weight: 600;
  color: #2c3e50;
  position: relative;
}
.invoice-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}
.invoice-card.active {
  background: #3498db;
  color: #fff;
  box-shadow: 0 4px 12px rgba(52,152,219,0.4);
}
.invoice-card.active span,
.invoice-card.active div {
  color: rgba(255,255,255,0.8) !important;
}
.invoice-card.active .pending-discount-badge {
  color: #92400e !important;
  background: #fef3c7 !important;
}
.invoice-card.active .approved-discount-text {
  color: #166534 !important;
}
.invoice-card.active:after {
  content: '→';
  position: absolute;
  right: 15px;
  font-size: 20px;
  font-weight: bold;
}
.bill-item-card {
  background: #fff;
  border-radius: 8px;
  padding: 12px 18px;
  margin-bottom: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  border-left: 4px solid #27ae60;
}
.bill-total-card {
  background: #ecf0f1;
  border-left-color: #e74c3c;
  font-weight: bold;
  padding: 14px 18px;
}
</style>

<div class="row" style="margin-bottom: 15px;">
  <div class="col-md-12">
    <a href="<?php echo site_url('admin/student_invoice'); ?>" class="btn btn-primary">
      <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back_to_invoices'); ?>
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-3">
    <h4 style="margin-bottom: 20px; color: #2c3e50; font-weight: 600;"><?php echo get_phrase('invoice_codes'); ?></h4>
    <div id="invoice-list">
      <?php if (count($invoice_codes) > 0): ?>
        <?php foreach ($invoice_codes as $inv): ?>
          <div class="invoice-card" data-invoice="<?php echo $inv['invoice_code']; ?>">
            <div><?php echo $inv['invoice_code']; ?> <span style="font-size: 11px; color: #7f8c8d; font-weight: 500;">- [<?php echo $inv['term'].'/'.$inv['year']; ?>]</span></div>
            <?php if($inv['discount_amount'] > 0): ?>
            <div class="approved-discount-text" style="font-size: 11px; color: #27ae60; margin-top: 4px;">
              <i class="fa fa-tag"></i> Discount: <?php echo $currency . number_format($inv['discount_amount'], 2); ?>
            </div>
            <?php endif; ?>
            <?php if($inv['pending_discount'] > 0): ?>
            <div class="pending-discount-badge" style="font-size: 11px; color: #92400e; margin-top: 4px; background: #fef3c7; padding: 3px 6px; border-radius: 4px;">
              <i class="fa fa-clock"></i> Pending: <?php echo $currency . number_format($inv['pending_discount'], 2); ?>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted"><?php echo get_phrase('no_invoices_found'); ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-9">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h4 style="margin: 0; color: #2c3e50; font-weight: 600;"><?php echo get_phrase('bill_details'); ?> - <?php echo $student_info ? $student_info->name : 'Student'; ?></h4>
      <div style="display: flex; gap: 10px;">
        <button type="button" id="view-invoice-btn" class="btn btn-info" style="font-weight: bold; display: none;">
          <i class="fa fa-file-invoice"></i> <?php echo get_phrase('view_invoice'); ?>
        </button>
        <button type="button" onclick="invoice_pay_modal(<?php echo $student_id; ?>)" class="btn btn-success" style="font-weight: bold;">
          <i class="fa fa-credit-card"></i> <?php echo get_phrase('take_payment'); ?>
        </button>
      </div>
    </div>
    <div id="bill-details">
      <p class="text-muted" style="padding: 40px; text-align: center;"><?php echo get_phrase('select_an_invoice_code_from_the_left'); ?></p>
    </div>
    
    <!-- Discount Profile Section -->
    <div id="discount-profile-section" style="display: none; margin-top: 30px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 16px; padding: 25px;">
      <h5 style="color: #0c4a6e; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa fa-tag" style="color: #0ea5e9;"></i>
        <?php echo get_phrase('assign_discount_profile'); ?>
      </h5>
      <?php echo form_open('', array('id' => 'profile-discount-form')); ?>
        <input type="hidden" name="student_id" id="profile_student_id" value="<?php echo $student_id; ?>">
        <input type="hidden" name="invoice_code" id="profile_invoice_code">
        <div class="form-group">
          <label style="font-weight: 600; color: #374151; margin-bottom: 10px;"><?php echo get_phrase('select_discount_profile'); ?></label>
          <select name="profile_id" id="discount_profile_select" class="form-control" style="height: 50px; border: 2px solid #3b82f6; border-radius: 10px; font-weight: 600;" required>
            <option value=""><?php echo get_phrase('select_profile'); ?></option>
            <?php
            $profiles = $this->db->where('is_active', 1)->where('discount_category', 'invoice')->get('discount_profiles')->result_array();
            foreach($profiles as $profile):
              $method_display = $profile['discount_method'] === 'percentage' ? $profile['discount_value'] . '%' : $currency . ' ' . number_format($profile['discount_value'], 2);
            ?>
            <option value="<?php echo $profile['profile_id']; ?>" 
                    data-method="<?php echo $profile['discount_method']; ?>" 
                    data-value="<?php echo $profile['discount_value']; ?>">
              <?php echo $profile['profile_name'] . ' (' . $method_display . ')'; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="profile-preview" style="display: none; background: white; border-radius: 10px; padding: 20px; margin: 15px 0; border: 2px solid #10b981; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);"></div>
        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 30px; font-weight: 700; border-radius: 10px;">
          <i class="fa fa-check-circle"></i> <?php echo get_phrase('assign_profile'); ?>
        </button>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  $('.invoice-card').click(function() {
    $('.invoice-card').removeClass('active');
    $(this).addClass('active');
    
    var invoiceCode = $(this).data('invoice');
    $('#profile_invoice_code').val(invoiceCode);
    
    $('#view-invoice-btn').show().off('click').on('click', function() {
      viewInvoiceDetails(invoiceCode);
    });
    
    $.ajax({
      url: '<?php echo site_url("admin/get_invoice_details"); ?>',
      type: 'POST',
      data: {invoice_code: invoiceCode, student_id: <?php echo $student_id; ?>},
      success: function(response) {
        $('#bill-details').html(response);
        $('#discount-profile-section').show();
      }
    });
  });

  $(document).on('submit', '#discount-form', function(e) {
    e.preventDefault();
    showAjaxModal_alert('<?php echo get_phrase("applying_discount"); ?>...', 'loading');
    
    $.ajax({
      url: '<?php echo site_url("admin/apply_invoice_discount"); ?>',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function(response) {
        if(response.status === 'success') {
          showAjaxModal_alert(response.message, 'success');
          setTimeout(function() {
            location.reload();
          }, 2000);
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function(err) {
        showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
      }
    });
  });

  $('#discount_profile_select').change(function() {
    var profileId = $(this).val();
    var invoiceCode = $('#profile_invoice_code').val();
    var selectedOption = $(this).find('option:selected');
    var method = selectedOption.data('method');
    var value = selectedOption.data('value');
    
    if(profileId && invoiceCode) {
      // Get student's class and residence type
      var studentId = $('#profile_student_id').val();
      
      $.ajax({
        url: '<?php echo site_url("admin/get_student_class_residence"); ?>',
        type: 'POST',
        data: { student_id: studentId },
        dataType: 'json',
        success: function(studentData) {
          $.ajax({
            url: '<?php echo site_url("admin/getDiscountProfileDetails"); ?>',
            type: 'POST',
            data: { 
              profile_id: profileId, 
              invoice_code: invoiceCode,
              class_id: studentData.class_id,
              residence_type: studentData.residence_type
            },
            dataType: 'json',
            success: function(response) {
              if(response.status === 'success' && response.profile) {
            var methodText = response.profile.discount_method === 'percentage' ? 'Percentage Discount' : 'Fixed Amount Discount';
            var valueText = response.profile.discount_method === 'percentage' 
              ? '<span style="font-size: 32px; font-weight: 800; color: #059669;">' + response.profile.discount_value + '%</span>' 
              : '<span style="font-size: 32px; font-weight: 800; color: #059669;">' + response.profile.currency + ' ' + parseFloat(response.profile.discount_value).toFixed(2) + '</span>';
            
            var html = '<div style="display: flex; gap: 20px; align-items: start;">';
            
            // Left side - Discount Value
            html += '<div style="flex: 0 0 200px; text-align: center; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 10px; border: 2px solid #10b981;">';
            html += '<div style="font-size: 12px; color: #065f46; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">' + methodText + '</div>';
            html += valueText;
            html += '</div>';
            
            // Right side - Details
            html += '<div style="flex: 1;">';
            html += '<h6 style="color: #065f46; font-weight: 700; margin-bottom: 12px; font-size: 16px;"><i class="fa fa-info-circle"></i> Profile Details</h6>';
            html += '<div style="font-size: 14px; color: #047857; line-height: 1.8;">';
            html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Profile Name:</strong> <span style="font-weight: 600;">' + response.profile.profile_name + '</span></div>';
            
            if(response.profile.bill_item_ids === '*') {
              html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Applies to:</strong> <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">All Bill Items</span></div>';
            } else if(response.profile.items && response.profile.items.length > 0) {
              html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Applies to:</strong></div>';
              html += '<ul style="margin: 5px 0 0 20px; font-size: 13px;">';
              response.profile.items.forEach(function(item) {
                html += '<li style="margin-bottom: 4px;"><span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">' + item.discount_type + '</span></li>';
              });
              html += '</ul>';
            }
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
                $('#profile-preview').html(html).slideDown(300);
              }
            }
          });
        }
      });
    } else {
      $('#profile-preview').slideUp(300);
    }
  });

  $('#profile-discount-form').submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var profileId = $('#discount_profile_select').val();
    var invoiceCode = $('#profile_invoice_code').val();
    
    // Check if student already has a profile for this invoice
    $.ajax({
      url: '<?php echo site_url("admin/check_existing_profile"); ?>',
      type: 'POST',
      data: { student_id: <?php echo $student_id; ?>, invoice_code: invoiceCode },
      dataType: 'json',
      success: function(check) {
        if(check.has_profile) {
          if(check.profile_id == profileId) {
            showAjaxModal_alert('This profile is already assigned to this invoice', 'warning');
          } else {
            showConfirmModal(
              'Replace Existing Profile?',
              'Student already has "' + check.profile_name + '" assigned to this invoice. The old profile will be REMOVED and replaced with the new one. Continue?',
              function() {
                processProfileAssignment(formData, 'replace');
              },
              'Replace Profile',
              'warning'
            );
          }
        } else {
          processProfileAssignment(formData, 'add');
        }
      }
    });
  });
  
  function processProfileAssignment(formData, action) {
    showAjaxModal_alert('<?php echo get_phrase("assigning_profile"); ?>...', 'loading');
    
    $.ajax({
      url: '<?php echo site_url("admin/assign_profile_to_invoice"); ?>',
      type: 'POST',
      data: formData + '&action=' + action,
      dataType: 'json',
      success: function(response) {
        if(response.status === 'success') {
          showAjaxModal_alert(response.message, 'success');
          setTimeout(function() {
            location.reload();
          }, 2000);
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function() {
        showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
      }
    });
  }

  <?php if (!empty($invoice_code)): ?>
  $('.invoice-card[data-invoice="<?php echo $invoice_code; ?>"]').click();
  <?php endif; ?>
});

function viewInvoiceDetails(invoiceCode) {
  showAjaxModal('<?php echo site_url('modal/popup_professional/modal_view_invoice_professional/'); ?>' + invoiceCode, 'large');
}

function invoice_pay_modal(student_id, date = '', term = '') {
  if(date != '' && term == '') {
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date, 'take_payment');
  } else if(date != '' && term != '') {
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
  } else {
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
  }
  
  $('#modal_ajax').on('hidden.bs.modal', function() {
    $('.invoice-card.active').click();
    $(this).off('hidden.bs.modal');
  });
}

</script>
