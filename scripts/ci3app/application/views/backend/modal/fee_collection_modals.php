<?php
// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');
?>
<script>
// Use existing modal system from modal.php
function showFeeLoadingModal(title, message) {
    showAjaxModal_alert(message || 'Processing...', 'loading');
}

function showFeeSuccessModal(title, message, autoClose) {
    showAjaxModal_alert(message || 'Operation completed successfully', 'success', autoClose);
}

function showFeeErrorModal(title, message) {
    showAjaxModal_alert(message || 'An error occurred', 'error');
}

function showFeeWarningModal(title, message) {
    showAjaxModal_alert(message || 'Please review this warning', 'warning');
}

function showFeeReceiptModal(receiptData) {
    var html = '<div style="font-family: monospace; font-size: 14px;">';
    html += '<div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 15px;">';
    html += '<h4 style="margin: 0 0 5px 0;">PAYMENT RECEIPT</h4>';
    html += '<div style="font-size: 12px; color: #666;">' + receiptData.school_name + '</div>';
    html += '</div>';
    
    html += '<table style="width: 100%; margin-bottom: 15px;">';
    html += '<tr><td><strong>Receipt No:</strong></td><td>' + receiptData.receipt_number + '</td></tr>';
    html += '<tr><td><strong>Date:</strong></td><td>' + receiptData.date + '</td></tr>';
    html += '<tr><td><strong>Time:</strong></td><td>' + receiptData.time + '</td></tr>';
    html += '<tr><td><strong>Student:</strong></td><td>' + receiptData.student_name + '</td></tr>';
    html += '<tr><td><strong>Student ID:</strong></td><td>' + receiptData.student_code + '</td></tr>';
    html += '<tr><td><strong>Class:</strong></td><td>' + receiptData.class_name + '</td></tr>';
    html += '</table>';
    
    html += '<div style="border-top: 1px solid #ddd; padding-top: 15px; margin-bottom: 15px;">';
    html += '<strong>PAYMENT DETAILS</strong>';
    html += '</div>';
    
    html += '<table style="width: 100%; margin-bottom: 15px;">';
    if (receiptData.feeding_amount > 0) {
        html += '<tr><td>Feeding</td><td style="text-align: right;">GHS ' + parseFloat(receiptData.feeding_amount).toFixed(2) + '</td></tr>';
    }
    if (receiptData.breakfast_amount > 0) {
        html += '<tr><td>Breakfast</td><td style="text-align: right;">GHS ' + parseFloat(receiptData.breakfast_amount).toFixed(2) + '</td></tr>';
    }
    if (receiptData.classes_amount > 0) {
        html += '<tr><td>Classes</td><td style="text-align: right;">GHS ' + parseFloat(receiptData.classes_amount).toFixed(2) + '</td></tr>';
    }
    if (receiptData.water_amount > 0) {
        html += '<tr><td>Water</td><td style="text-align: right;">GHS ' + parseFloat(receiptData.water_amount).toFixed(2) + '</td></tr>';
    }
    if (receiptData.transport_amount > 0) {
        html += '<tr><td>Transport</td><td style="text-align: right;">GHS ' + parseFloat(receiptData.transport_amount).toFixed(2) + '</td></tr>';
    }
    html += '</table>';
    
    html += '<div style="border-top: 2px solid #333; padding-top: 10px; margin-top: 10px;">';
    html += '<table style="width: 100%;">';
    html += '<tr style="font-size: 18px; font-weight: bold;"><td>TOTAL PAID</td><td style="text-align: right;">GHS ' + parseFloat(receiptData.total_amount).toFixed(2) + '</td></tr>';
    html += '</table>';
    html += '</div>';
    
    html += '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px;">';
    html += '<div><strong>Payment Method:</strong> ' + receiptData.payment_method + '</div>';
    html += '<div><strong>Payment Type:</strong> ' + receiptData.payment_type + '</div>';
    html += '<div><strong>Collected By:</strong> ' + receiptData.collected_by + '</div>';
    html += '</div>';
    
    html += '<div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666;">';
    html += 'Thank you for your payment!<br>Keep this receipt for your records';
    html += '</div>';
    
    html += '</div>';
    
    showAjaxModalDisplay(html);
}

function closeFeeModal(type) {
    $('.close').click();
}

function printReceipt() {
    var printContent = $('#modal_display .modal-body').html();
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Receipt</title>');
    printWindow.document.write('<style>body{font-family:Arial,sans-serif;padding:20px;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

<!-- Bulk Rate Assignment Modal -->
<div class="modal fade" id="bulkRateModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-layer-group"></i> <?php echo get_phrase('bulk_assign_rates'); ?></h4>
            </div>
            <?php echo form_open('', ['id' => 'bulkRateForm']); ?>
                <div class="modal-body" style="padding:30px 30px 0 30px;">
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <i class="fa fa-info-circle"></i> <strong>Tip:</strong> Set rates for multiple classes at once. Leave blank to skip a class.
                    </div>
                </div>
                <div style="max-height:50vh; overflow-y:auto; padding:0 30px 30px 30px;">
                    <table class="table table-bordered" style="margin-bottom: 0;">
                        <thead style="background:#667eea; color:white; position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <tr>
                                <th style="width:25%;"><?php echo get_phrase('class'); ?></th>
                                <?php if($feeding_enabled): ?>
                                <th><?php echo get_phrase('feeding'); ?></th>
                                <?php endif; ?>
                                <?php if($breakfast_enabled): ?>
                                <th><?php echo get_phrase('breakfast'); ?></th>
                                <?php endif; ?>
                                <?php if($classes_enabled): ?>
                                <th><?php echo get_phrase('classes'); ?></th>
                                <?php endif; ?>
                                <?php if($water_enabled): ?>
                                <th><?php echo get_phrase('water'); ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody style="background: white;">
                            <?php
                            $running_year = get_settings('running_year');
                            $running_term = get_settings('running_term');
                            $classes = $this->db->get('class')->result_array();
                            foreach ($classes as $class):
                                $sections = $this->db->get_where('section', ['class_id' => $class['class_id']])->result_array();
                                if (empty($sections)) {
                                    $rate = $this->db->get_where('daily_fee_rates', [
                                        'class_id' => $class['class_id'],
                                        'year' => $running_year,
                                        'term' => $running_term
                                    ])->row();
                            ?>
                            <tr>
                                <td style="font-weight:600; vertical-align:middle;">
                                    <i class="fa fa-graduation-cap" style="color:#667eea;"></i> <?php echo $class['name'] . ' ' . $class['name_numeric']; ?>
                                    <input type="hidden" name="class_ids[]" value="<?php echo $class['class_id']; ?>">
                                    <input type="hidden" name="rate_ids[]" value="<?php echo $rate ? $rate->id : 0; ?>">
                                </td>
                                <?php if($feeding_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="feeding_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->feeding_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                                <?php if($breakfast_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="breakfast_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->breakfast_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                                <?php if($classes_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="classes_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->classes_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                                <?php if($water_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="water_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->water_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php
                                } else {
                                    foreach ($sections as $section):
                                        $rate = $this->db->get_where('daily_fee_rates', [
                                            'class_id' => $class['class_id'],
                                            'year' => $running_year,
                                            'term' => $running_term
                                        ])->row();
                            ?>
                            <tr style="background:#fffbeb;">
                                <td style="font-weight:600; vertical-align:middle;">
                                    <i class="fa fa-graduation-cap" style="color:#f59e0b;"></i> <?php echo $class['name'] . ' ' . $class['name_numeric']; ?> <span style="color:#f59e0b;">- <?php echo $section['name']; ?></span>
                                    <input type="hidden" name="class_ids[]" value="<?php echo $class['class_id']; ?>">
                                    <input type="hidden" name="rate_ids[]" value="<?php echo $rate ? $rate->id : 0; ?>">
                                </td>
                                <?php if($feeding_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="feeding_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->feeding_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                                <?php if($breakfast_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="breakfast_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->breakfast_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                                <?php if($classes_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="classes_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->classes_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                                <?php if($water_enabled): ?>
                                <td>
                                    <input type="number" step="0.01" name="water_rates[]" class="bulk-input" placeholder="0.00" value="<?php echo $rate ? $rate->water_rate : ''; ?>">
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php
                                    endforeach;
                                }
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn modern-btn modern-btn-success"><i class="fa fa-save"></i> <?php echo get_phrase('save_all_rates'); ?></button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Daily Fee Rates Modal -->
<style>
.modern-input {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    transition: all 0.3s;
    width: 100%;
}
.modern-input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}
.modern-checkbox {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    margin-top: 8px;
}
.modern-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}
.modern-checkbox .checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    margin-right: 8px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modern-checkbox input:checked ~ .checkmark {
    background: #667eea;
    border-color: #667eea;
}
.modern-checkbox .checkmark:after {
    content: "✓";
    color: white;
    font-size: 14px;
    display: none;
}
.modern-checkbox input:checked ~ .checkmark:after {
    display: block;
}
</style>
<div class="modal fade" id="rateModal">
    <div class="modal-dialog" style="max-width:550px;">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header" style="padding:15px 20px;">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;font-size:16px;"><?php echo get_phrase('set_daily_fee_rates'); ?></h4>
            </div>
            <?php echo form_open('', ['id' => 'rateForm']); ?>
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" name="class_id" id="class_id" value="">
                    <input type="hidden" name="rate_id" id="rate_id" value="">
                    
                    <div class="row">
                        <?php if($feeding_enabled): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;"><?php echo get_phrase('feeding_rate'); ?> (<?php echo get_phrase('per_day'); ?>)</label>
                                <input type="number" step="0.01" name="feeding_rate" id="feeding_rate" class="modern-input" placeholder="0.00" required>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($breakfast_enabled): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;"><?php echo get_phrase('breakfast_rate'); ?> (<?php echo get_phrase('per_day'); ?>)</label>
                                <input type="number" step="0.01" name="breakfast_rate" id="breakfast_rate" class="modern-input" placeholder="0.00">
                                <label class="modern-checkbox">
                                    <input type="checkbox" name="breakfast_enabled" id="breakfast_enabled" value="1">
                                    <span class="checkmark"></span>
                                    <span style="font-size:13px;color:#6b7280;"><?php echo get_phrase('breakfast_available'); ?></span>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <?php if($classes_enabled): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;"><?php echo get_phrase('classes_rate'); ?> (<?php echo get_phrase('per_day'); ?>)</label>
                                <input type="number" step="0.01" name="classes_rate" id="classes_rate" class="modern-input" placeholder="0.00" required>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($water_enabled): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;"><?php echo get_phrase('water_rate'); ?> (<?php echo get_phrase('per_week'); ?>)</label>
                                <input type="number" step="0.01" name="water_rate" id="water_rate" class="modern-input" placeholder="0.00">
                                <label class="modern-checkbox">
                                    <input type="checkbox" name="water_enabled" id="water_enabled" value="1" checked>
                                    <span class="checkmark"></span>
                                    <span style="font-size:13px;color:#6b7280;"><?php echo get_phrase('water_charge_enabled'); ?></span>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($transport_enabled): ?>
                    <div class="alert alert-info" style="font-size:11px;padding:8px;margin-top:10px;margin-bottom:0;">
                        <i class="fa fa-info-circle"></i> Transport fares in <a href="<?php echo site_url('admin/transport'); ?>">Transport Routes</a>.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:12px 20px;background:#f8f9fa;">
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn modern-btn modern-btn-success"><i class="fa fa-save"></i> <?php echo get_phrase('save'); ?></button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
function showBulkRateModal() {
    // Refresh bulk modal data before showing
    $.ajax({
        url: '<?php echo site_url('admin/get_current_rates_json'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(rates) {
            // Update each input with current rate
            $('input[name="feeding_rates[]"]').each(function(index) {
                var classId = $('input[name="class_ids[]"]').eq(index).val();
                if(rates[classId]) {
                    $(this).val(rates[classId].feeding_rate || '');
                }
            });
            $('input[name="breakfast_rates[]"]').each(function(index) {
                var classId = $('input[name="class_ids[]"]').eq(index).val();
                if(rates[classId]) {
                    $(this).val(rates[classId].breakfast_rate || '');
                }
            });
            $('input[name="classes_rates[]"]').each(function(index) {
                var classId = $('input[name="class_ids[]"]').eq(index).val();
                if(rates[classId]) {
                    $(this).val(rates[classId].classes_rate || '');
                }
            });
            $('input[name="water_rates[]"]').each(function(index) {
                var classId = $('input[name="class_ids[]"]').eq(index).val();
                if(rates[classId]) {
                    $(this).val(rates[classId].water_rate || '');
                }
            });
            
            // Show modal after data is refreshed
            $('#bulkRateModal').modal('show');
        },
        error: function() {
            // Show modal anyway if refresh fails
            $('#bulkRateModal').modal('show');
        }
    });
}

$('#bulkRateForm').submit(function(e) {
    e.preventDefault();
    $('#bulkRateModal').modal('hide');
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/daily_fee_rates_bulk_save'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(() => {
                $('.close').click();
                refreshRates();
            }, 1500);
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});

function showRateModal(classId, rateId) {
    // Clear form fields manually instead of reset
    $('#feeding_rate').val('');
    $('#breakfast_rate').val('');
    $('#classes_rate').val('');
    $('#water_rate').val('');
    $('#breakfast_enabled').prop('checked', false);
    $('#water_enabled').prop('checked', true);
    
    // Set hidden fields
    $('#class_id').val(classId);
    $('#rate_id').val(rateId);
    
    if(rateId > 0) {
        $.get('<?php echo site_url('admin/get_rate_data/'); ?>' + rateId, function(data) {
            var rate = JSON.parse(data);
            $('#feeding_rate').val(rate.feeding_rate);
            $('#breakfast_rate').val(rate.breakfast_rate);
            $('#classes_rate').val(rate.classes_rate);
            $('#water_rate').val(rate.water_rate);
            $('#breakfast_enabled').prop('checked', rate.breakfast_enabled == 1);
            $('#water_enabled').prop('checked', rate.water_enabled == 1);
        });
    }
    
    $('#rateModal').modal('show');
}

function closeRateModal() {
    $('#rateModal').modal('hide');
}

$('#rateForm').submit(function(e) {
    e.preventDefault();
    
    var classId = $('#class_id').val();
    var rateId = $('#rate_id').val();
    
    if(!classId) {
        showAjaxModal_alert('<?php echo get_phrase('class_id_missing'); ?>', 'error');
        return false;
    }
    
    closeRateModal();
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    var url = rateId > 0 ? '<?php echo site_url('admin/daily_fee_rates/do_update/'); ?>' + rateId : '<?php echo site_url('admin/daily_fee_rates/create'); ?>';
    
    var formData = new FormData(this);
    formData.set('class_id', classId);
    formData.set('rate_id', rateId);
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(() => {
                $('.close').click();
                refreshRates();
            }, 1500);
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});
</script>


<!-- Import Rates from Previous Term Modal -->
<div class="modal fade" id="importModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-file-import"></i> <?php echo get_phrase('import_rates_from_previous_term'); ?></h4>
            </div>
            <div class="modal-body" style="padding:30px;">
                <!-- Step 1: Select Term -->
                <div id="import_step_1">
                    <div class="alert alert-info" style="border-left: 4px solid #10b981; margin-bottom: 20px;">
                        <i class="fa fa-info-circle"></i>
                        <strong><?php echo get_phrase('select_source_term'); ?>:</strong> 
                        <?php echo get_phrase('choose_the_term_to_import_rates_from'); ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 14px;"><?php echo get_phrase('year'); ?>:</label>
                                <select id="import_year" class="form-control" style="height: 45px; font-size: 16px;">
                                    <?php
                                    $current_year = get_settings('running_year');
                                    $years = explode('-', $current_year);
                                    $start_year = intval($years[0]);
                                    
                                    // Show last 3 years
                                    for ($i = 0; $i < 3; $i++) {
                                        $year_val = ($start_year - $i) . '-' . ($start_year - $i + 1);
                                        echo '<option value="' . $year_val . '">' . $year_val . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 14px;"><?php echo get_phrase('term'); ?>:</label>
                                <select id="import_term" class="form-control" style="height: 45px; font-size: 16px;">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-lg btn-primary" onclick="loadPreviousRates()" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;">
                        <i class="fa fa-download"></i> <?php echo get_phrase('load_rates'); ?>
                    </button>
                </div>
                
                <!-- Step 2: Review and Edit -->
                <div id="import_step_2" style="display: none;">
                    <div class="alert alert-success" style="border-left: 4px solid #10b981; margin-bottom: 20px;">
                        <i class="fa fa-check-circle"></i>
                        <strong><?php echo get_phrase('review_and_edit'); ?>:</strong> 
                        <?php echo get_phrase('review_rates_below_edit_if_needed_then_import'); ?>
                    </div>
                    
                    <div style="max-height: 50vh; overflow-y: auto;">
                        <div id="preview_rates_container"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <div id="import_step_1_footer">
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                </div>
                <div id="import_step_2_footer" style="display: none;">
                    <button type="button" class="btn modern-btn modern-btn-cancel" onclick="backToStep1()">
                        <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back'); ?>
                    </button>
                    <button type="button" class="btn modern-btn modern-btn-success" onclick="importRates()" style="padding: 12px 30px; font-size: 16px; font-weight: 600;">
                        <i class="fa fa-check"></i> <?php echo get_phrase('confirm_import'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadPreviousRates() {
    var year = $('#import_year').val();
    var term = $('#import_term').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_previous_term_rates'); ?>',
        type: 'POST',
        data: { year: year, term: term },
        dataType: 'json',
        beforeSend: function() {
            showAjaxModal_alert('<?php echo get_phrase('loading_rates'); ?>...', 'loading');
        },
        success: function(response) {
            closeAjaxModal();
            
            if (response.status === 'success') {
                if (response.rates.length === 0) {
                    showAjaxModal_alert('<?php echo get_phrase('no_rates_found_for_selected_term'); ?>', 'error');
                } else {
                    $('#preview_rates_container').html(response.html);
                    $('#import_step_1').hide();
                    $('#import_step_2').show();
                    $('#import_step_1_footer').hide();
                    $('#import_step_2_footer').show();
                }
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_loading_rates'); ?>', 'error');
        }
    });
}

function backToStep1() {
    $('#import_step_2').hide();
    $('#import_step_1').show();
    $('#import_step_2_footer').hide();
    $('#import_step_1_footer').show();
}

function importRates() {
    // Gather all edited rates
    var rates = [];
    $('.import-rate-row').each(function() {
        var classId = $(this).data('class-id');
        rates.push({
            class_id: classId,
            feeding_rate: $(this).find('.feeding-rate').val(),
            breakfast_rate: $(this).find('.breakfast-rate').val(),
            classes_rate: $(this).find('.classes-rate').val(),
            water_rate: $(this).find('.water-rate').val()
        });
    });
    
    $('#importModal').modal('hide');
    showAjaxModal_alert('<?php echo get_phrase('importing_rates'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/import_daily_fee_rates'); ?>',
        type: 'POST',
        data: { rates: JSON.stringify(rates) },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showAjaxModal_alert(response.message, 'success', false);
                setTimeout(function() {
                    $('.close').click();
                    refreshRates();
                }, 1500);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_importing_rates'); ?>', 'error');
        }
    });
}
</script>
