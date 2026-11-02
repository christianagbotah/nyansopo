<?php
// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');
?>
<style>
.fee-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 20px; color: white; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.fee-card h3 { margin: 0 0 10px 0; font-size: 16px; opacity: 0.9; }
.fee-card .amount { font-size: 32px; font-weight: bold; }
.fee-toggle { background: rgba(255,255,255,0.2); border-radius: 10px; padding: 15px; margin: 10px 0; cursor: pointer; transition: all 0.3s; }
.fee-toggle:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
.fee-toggle.active { background: rgba(255,255,255,0.4); border: 2px solid white; }
.fee-input { background: rgba(255,255,255,0.9); border: none; border-radius: 8px; padding: 12px; font-size: 16px; width: 100%; }
.total-display { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 15px; padding: 25px; text-align: center; color: white; font-size: 36px; font-weight: bold; margin: 20px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.btn-collect { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none; border-radius: 12px; padding: 18px 40px; font-size: 18px; font-weight: bold; color: white; width: 100%; box-shadow: 0 8px 20px rgba(0,0,0,0.2); transition: all 0.3s; }
.btn-collect:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }
.student-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.badge-custom { padding: 8px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel" style="border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 15px;">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0; padding: 25px;">
                <h2 style="margin: 0; font-weight: bold;"><i class="fa fa-cash-register"></i> <?php echo get_phrase('daily_fee_collection'); ?></h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;"><?php echo date('l, d F Y'); ?></p>
            </div>
            <div class="panel-body" style="padding: 30px;">
                
                <!-- Student Selection -->
                <div class="student-card">
                    <label style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 10px;">
                        <i class="fa fa-user-graduate"></i> <?php echo get_phrase('select_student'); ?>
                    </label>
                    <select class="form-control select2" id="student_id" style="height: 50px; font-size: 16px;">
                        <option value=""><?php echo get_phrase('search_student_by_name_or_code'); ?></option>
                        <?php
                        $students = $this->db->query("
                            SELECT s.student_id, s.name, s.student_code, s.transport_id,
                                   c.name as class_name, c.class_id
                            FROM student s
                            JOIN enroll e ON s.student_id = e.student_id
                            JOIN class c ON e.class_id = c.class_id
                            WHERE e.year = ? AND e.term = ? AND e.mute = 0
                            ORDER BY s.name
                        ", [get_settings('running_year'), get_settings('running_term')])->result_array();
                        
                        foreach ($students as $student):
                        ?>
                        <option value="<?php echo $student['student_id']; ?>" 
                                data-class="<?php echo $student['class_id']; ?>"
                                data-route="<?php echo $student['transport_id']; ?>">
                            <?php echo $student['student_code'] . ' - ' . $student['name'] . ' (' . $student['class_name'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Collection Form -->
                <div id="collection_form" style="display:none;">
                    <form id="feeCollectionForm">
                        <input type="hidden" name="student_id" id="form_student_id">
                        <input type="hidden" name="collection_date" value="<?php echo date('Y-m-d'); ?>">
                        
                        <div class="row">
                            <!-- Feeding -->
                            <?php if($feeding_enabled): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="fee-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <h3><i class="fa fa-utensils"></i> <?php echo get_phrase('feeding'); ?></h3>
                                    <div class="amount" id="feeding_amount">GHS 0.00</div>
                                    <div class="fee-toggle" onclick="toggleFee('feeding')">
                                        <input type="checkbox" name="collect_feeding" id="collect_feeding" value="1">
                                        <label for="collect_feeding" style="margin: 0; cursor: pointer;"><?php echo get_phrase('collect_today'); ?></label>
                                    </div>
                                    <input type="hidden" name="feeding_amount" id="feeding_rate" value="0">
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Breakfast -->
                            <?php if($breakfast_enabled): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="fee-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                    <h3><i class="fa fa-coffee"></i> <?php echo get_phrase('breakfast'); ?></h3>
                                    <div class="amount" id="breakfast_amount">GHS 0.00</div>
                                    <div class="fee-toggle" onclick="toggleFee('breakfast')">
                                        <input type="checkbox" name="collect_breakfast" id="collect_breakfast" value="1">
                                        <label for="collect_breakfast" style="margin: 0; cursor: pointer;"><?php echo get_phrase('collect_today'); ?></label>
                                    </div>
                                    <input type="hidden" name="breakfast_amount" id="breakfast_rate" value="0">
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Classes -->
                            <?php if($classes_enabled): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="fee-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                                    <h3><i class="fa fa-book"></i> <?php echo get_phrase('classes'); ?></h3>
                                    <div class="amount" id="classes_amount">GHS 0.00</div>
                                    <div class="fee-toggle" onclick="toggleFee('classes')">
                                        <input type="checkbox" name="collect_classes" id="collect_classes" value="1">
                                        <label for="collect_classes" style="margin: 0; cursor: pointer;"><?php echo get_phrase('collect_today'); ?></label>
                                    </div>
                                    <input type="hidden" name="classes_amount" id="classes_rate" value="0">
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Water -->
                            <?php if($water_enabled): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="fee-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                    <h3><i class="fa fa-tint"></i> <?php echo get_phrase('water'); ?></h3>
                                    <div class="amount" id="water_amount">GHS 0.00</div>
                                    <div class="fee-toggle" onclick="toggleFee('water')">
                                        <input type="checkbox" name="collect_water" id="collect_water" value="1">
                                        <label for="collect_water" style="margin: 0; cursor: pointer;"><?php echo get_phrase('collect_this_week'); ?></label>
                                    </div>
                                    <input type="hidden" name="water_amount" id="water_rate" value="0">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Transport Section -->
                        <?php if($transport_enabled): ?>
                        <div class="student-card" id="transport_section" style="display:none;">
                            <h4 style="color: #667eea; margin-bottom: 20px;"><i class="fa fa-bus"></i> <?php echo get_phrase('transport'); ?></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <label><?php echo get_phrase('route'); ?></label>
                                    <input type="text" class="fee-input" id="route_name" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label><?php echo get_phrase('direction_today'); ?></label>
                                    <select class="fee-input" name="transport_direction" id="transport_direction" onchange="calculateTotal()">
                                        <option value="none"><?php echo get_phrase('not_using'); ?></option>
                                        <option value="in"><?php echo get_phrase('morning_only'); ?></option>
                                        <option value="out"><?php echo get_phrase('afternoon_only'); ?></option>
                                        <option value="both"><?php echo get_phrase('both_ways'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div style="margin-top: 15px; padding: 15px; background: rgba(102,126,234,0.1); border-radius: 10px;">
                                <strong><?php echo get_phrase('transport_fare'); ?>:</strong> 
                                <span id="transport_amount" style="font-size: 20px; color: #667eea; font-weight: bold;">GHS 0.00</span>
                            </div>
                            <input type="hidden" name="transport_amount" id="transport_rate" value="0">
                            <input type="hidden" id="route_fare_base" value="0">
                        </div>
                        <?php endif; ?>

                        <!-- Total Display -->
                        <div class="total-display">
                            <div style="font-size: 16px; opacity: 0.9; margin-bottom: 5px;"><?php echo get_phrase('total_amount'); ?></div>
                            <div id="total_amount">GHS 0.00</div>
                        </div>

                        <!-- Payment Method -->
                        <div class="student-card">
                            <label style="font-size: 16px; font-weight: 600; color: #333;"><?php echo get_phrase('payment_method'); ?></label>
                            <select class="fee-input" name="payment_method" required>
                                <option value="1"><?php echo get_phrase('cash'); ?></option>
                                <option value="3"><?php echo get_phrase('mobile_money'); ?></option>
                                <option value="2"><?php echo get_phrase('cheque'); ?></option>
                                <option value="4"><?php echo get_phrase('bank_transfer'); ?></option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-collect">
                            <i class="fa fa-check-circle"></i> <?php echo get_phrase('collect_payment'); ?>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
var rates = {};
var routeFare = 0;

$('#student_id').change(function() {
    var studentId = $(this).val();
    if (!studentId) {
        $('#collection_form').hide();
        return;
    }
    
    var classId = $(this).find(':selected').data('class');
    var routeId = $(this).find(':selected').data('route');
    
    $('#form_student_id').val(studentId);
    
    // Load fee rates
    $.get('<?php echo site_url('admin/get_class_rates/'); ?>' + classId, function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        rates = data;
        
        $('#feeding_amount').text('GHS ' + parseFloat(data.feeding_rate || 0).toFixed(2));
        $('#feeding_rate').val(data.feeding_rate || 0);
        
        $('#breakfast_amount').text('GHS ' + parseFloat(data.breakfast_rate || 0).toFixed(2));
        $('#breakfast_rate').val(data.breakfast_rate || 0);
        
        $('#classes_amount').text('GHS ' + parseFloat(data.classes_rate || 0).toFixed(2));
        $('#classes_rate').val(data.classes_rate || 0);
        
        $('#water_amount').text('GHS ' + parseFloat(data.water_rate || 0).toFixed(2));
        $('#water_rate').val(data.water_rate || 0);
        
        // Reset checkboxes
        $('input[type=checkbox]').prop('checked', false);
        $('.fee-toggle').removeClass('active');
        
        $('#collection_form').show();
    });
    
    // Load transport info
    if (routeId) {
        $.get('<?php echo site_url('admin/get_route_info/'); ?>' + routeId, function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.status === 'success') {
                $('#route_name').val(data.route.route_name);
                routeFare = parseFloat(data.route.route_fare);
                $('#route_fare_base').val(routeFare);
                $('#transport_section').show();
            }
        });
    } else {
        $('#transport_section').hide();
    }
});

function toggleFee(type) {
    var checkbox = $('#collect_' + type);
    checkbox.prop('checked', !checkbox.prop('checked'));
    
    if (checkbox.prop('checked')) {
        checkbox.closest('.fee-toggle').addClass('active');
    } else {
        checkbox.closest('.fee-toggle').removeClass('active');
    }
    
    calculateTotal();
}

function calculateTotal() {
    var total = 0;
    
    if ($('#collect_feeding').prop('checked')) {
        total += parseFloat($('#feeding_rate').val() || 0);
    }
    if ($('#collect_breakfast').prop('checked')) {
        total += parseFloat($('#breakfast_rate').val() || 0);
    }
    if ($('#collect_classes').prop('checked')) {
        total += parseFloat($('#classes_rate').val() || 0);
    }
    if ($('#collect_water').prop('checked')) {
        total += parseFloat($('#water_rate').val() || 0);
    }
    
    // Transport
    var direction = $('#transport_direction').val();
    var transportAmount = 0;
    if (direction == 'in' || direction == 'out') {
        transportAmount = routeFare;
    } else if (direction == 'both') {
        transportAmount = routeFare * 2;
    }
    
    $('#transport_amount').text('GHS ' + transportAmount.toFixed(2));
    $('#transport_rate').val(transportAmount);
    total += transportAmount;
    
    $('#total_amount').text('GHS ' + total.toFixed(2));
}

$('#feeCollectionForm').submit(function(e) {
    e.preventDefault();
    
    var total = parseFloat($('#total_amount').text().replace('GHS ', ''));
    if (total <= 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_at_least_one_fee'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('processing_payment'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/collect_daily_fees'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => {
                $('#feeCollectionForm')[0].reset();
                $('#student_id').val('').trigger('change');
                $('#collection_form').hide();
            }, 2000);
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});

$(document).ready(function() {
    $('.select2').select2();
});
</script>
