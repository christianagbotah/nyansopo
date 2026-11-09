<style>
.conductor-portal {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.conductor-header {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.conductor-header h1 {
    margin: 0;
    color: #667eea;
    font-size: 32px;
    font-weight: 700;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card .icon {
    font-size: 40px;
    margin-bottom: 15px;
}

.stat-card .value {
    font-size: 32px;
    font-weight: 700;
    margin: 10px 0;
}

.stat-card .label {
    color: #666;
    font-size: 14px;
}

.transport-panel {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.quick-search {
    margin-bottom: 30px;
}

.quick-search input {
    width: 100%;
    padding: 18px 20px;
    border: 3px solid #e0e0e0;
    border-radius: 15px;
    font-size: 18px;
}

.quick-search input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
    outline: none;
}

.student-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-left: 5px solid #667eea;
    transition: all 0.3s;
}

.student-card:hover {
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.student-card .student-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.student-card .name {
    font-size: 20px;
    font-weight: 700;
    color: #333;
}

.student-card .code {
    color: #666;
    font-size: 14px;
}

.student-card .route-badge {
    background: #667eea;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
}

.direction-selector {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.direction-btn {
    padding: 20px;
    border: 3px solid #e0e0e0;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.direction-btn:hover {
    border-color: #667eea;
    background: rgba(102,126,234,0.05);
}

.direction-btn.active {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.direction-btn .icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.direction-btn .label {
    font-size: 16px;
    font-weight: 600;
}

.fare-display {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    margin: 20px 0;
}

.fare-display .label {
    font-size: 14px;
    opacity: 0.9;
}

.fare-display .amount {
    font-size: 36px;
    font-weight: 700;
}

.btn-collect-transport {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    border-radius: 12px;
    padding: 18px 40px;
    font-size: 18px;
    font-weight: 700;
    color: white;
    width: 100%;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.btn-collect-transport:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}

.payment-method-quick {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 20px 0;
}

.payment-method-quick button {
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-method-quick button.active {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.payment-method-quick button:hover {
    border-color: #667eea;
}

@media (max-width: 768px) {
    .direction-selector {
        grid-template-columns: 1fr;
    }
    
    .payment-method-quick {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php $this->load->view('backend/modal/fee_collection_modals'); ?>

<div class="conductor-portal">
    <div class="conductor-header">
        <h1><i class="fa fa-bus"></i> <?php echo get_phrase('transport_collection'); ?></h1>
        <div style="margin-top: 10px; color: #666;">
            <i class="fa fa-user"></i> <?php echo $this->session->userdata('name'); ?> 
            <span style="margin-left: 20px;"><i class="fa fa-calendar"></i> <?php echo date('l, F j, Y'); ?></span>
            <span style="margin-left: 20px;"><i class="fa fa-clock"></i> <?php echo date('h:i A'); ?></span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card" style="border-left: 5px solid #667eea;">
            <div class="icon" style="color: #667eea;"><i class="fa fa-money-bill-wave"></i></div>
            <div class="value" id="stat_collected">GHS 0.00</div>
            <div class="label"><?php echo get_phrase('collected_today'); ?></div>
        </div>
        <div class="stat-card" style="border-left: 5px solid #38ef7d;">
            <div class="icon" style="color: #38ef7d;"><i class="fa fa-users"></i></div>
            <div class="value" id="stat_students">0</div>
            <div class="label"><?php echo get_phrase('students_paid'); ?></div>
        </div>
        <div class="stat-card" style="border-left: 5px solid #f5576c;">
            <div class="icon" style="color: #f5576c;"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="value" id="stat_pending">0</div>
            <div class="label"><?php echo get_phrase('pending_payments'); ?></div>
        </div>
    </div>

    <!-- Tabs -->
    <div style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <div style="display: flex; gap: 10px; border-bottom: 2px solid #e0e0e0;">
            <button onclick="switchTab('collect')" id="tab_collect" style="padding: 15px 30px; border: none; background: #667eea; color: white; font-size: 16px; font-weight: 600; border-radius: 10px 10px 0 0; cursor: pointer;">
                <i class="fa fa-hand-holding-usd"></i> Collect Payment
            </button>
            <button onclick="switchTab('board')" id="tab_board" style="padding: 15px 30px; border: none; background: transparent; color: #666; font-size: 16px; font-weight: 600; border-radius: 10px 10px 0 0; cursor: pointer;">
                <i class="fa fa-bus"></i> Mark Boarded
            </button>
        </div>
    </div>

    <!-- Transport Collection Panel -->
    <div class="transport-panel" id="panel_collect">
        <h2 style="margin-bottom: 25px; color: #667eea;"><i class="fa fa-hand-holding-usd"></i> <?php echo get_phrase('collect_transport_fare'); ?></h2>
        
        <!-- Quick Search -->
        <div class="quick-search">
            <select class="form-control select2" id="student_id" style="width: 100%; height: 60px; font-size: 18px;">
                <option value=""><?php echo get_phrase('search_student_by_name_or_code'); ?></option>
                <?php
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                
                $students = $this->db->query("
                    SELECT s.student_id, s.name, s.student_code, c.name as class_name, 
                           tr.route_id, tr.route_name, tr.route_fare
                    FROM student s
                    INNER JOIN enroll e ON s.student_id = e.student_id
                    INNER JOIN class c ON e.class_id = c.class_id
                    LEFT JOIN transport_routes tr ON s.transport_id = tr.route_id
                    WHERE e.year = ? AND e.term = ? AND s.transport_id IS NOT NULL
                    ORDER BY s.name
                ", [$running_year, $running_term])->result_array();
                
                foreach ($students as $student):
                ?>
                <option value="<?php echo $student['student_id']; ?>" 
                        data-route="<?php echo $student['route_name']; ?>"
                        data-fare="<?php echo $student['route_fare']; ?>">
                    <?php echo $student['student_code'] . ' - ' . $student['name'] . ' (' . $student['class_name'] . ')'; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Collection Form -->
        <form id="transportCollectionForm" style="display: none;">
            <input type="hidden" name="student_id" id="form_student_id">
            
            <div class="student-card">
                <div class="student-info">
                    <div>
                        <div class="name" id="selected_student_name"></div>
                        <div class="code" id="selected_student_code"></div>
                    </div>
                    <div class="route-badge" id="selected_route"></div>
                </div>

                <!-- Direction Selector -->
                <label style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 15px;">
                    <i class="fa fa-route"></i> <?php echo get_phrase('select_direction'); ?>
                </label>
                <div class="direction-selector">
                    <div class="direction-btn" onclick="selectDirection('in')">
                        <div class="icon"><i class="fa fa-arrow-right"></i></div>
                        <div class="label"><?php echo get_phrase('morning_only'); ?></div>
                        <div style="font-size: 12px; margin-top: 5px;"><?php echo get_phrase('to_school'); ?></div>
                    </div>
                    <div class="direction-btn" onclick="selectDirection('out')">
                        <div class="icon"><i class="fa fa-arrow-left"></i></div>
                        <div class="label"><?php echo get_phrase('afternoon_only'); ?></div>
                        <div style="font-size: 12px; margin-top: 5px;"><?php echo get_phrase('from_school'); ?></div>
                    </div>
                    <div class="direction-btn" onclick="selectDirection('both')">
                        <div class="icon"><i class="fa fa-arrows-alt-h"></i></div>
                        <div class="label"><?php echo get_phrase('both_ways'); ?></div>
                        <div style="font-size: 12px; margin-top: 5px;"><?php echo get_phrase('to_and_from'); ?></div>
                    </div>
                    <div class="direction-btn" onclick="selectDirection('none')">
                        <div class="icon"><i class="fa fa-times"></i></div>
                        <div class="label"><?php echo get_phrase('not_using'); ?></div>
                        <div style="font-size: 12px; margin-top: 5px;"><?php echo get_phrase('no_transport'); ?></div>
                    </div>
                </div>
                <input type="hidden" name="transport_direction" id="transport_direction" value="">

                <!-- Fare Display -->
                <div class="fare-display">
                    <div class="label"><?php echo get_phrase('amount_to_collect'); ?></div>
                    <div class="amount" id="fare_display">GHS 0.00</div>
                    <div style="margin-top: 15px;">
                        <label style="color: white; font-size: 14px; opacity: 0.9;"><?php echo get_phrase('or_enter_custom_amount'); ?>:</label>
                        <input type="number" step="0.01" min="0" name="transport_amount" id="transport_amount" 
                               style="width: 100%; padding: 12px; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; text-align: center;" 
                               placeholder="0.00" onchange="updateFareDisplay()">
                    </div>
                </div>
                <input type="hidden" id="base_fare" value="0">

                <!-- Payment Type Selector -->
                <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 15px; margin: 15px 0;">
                    <label style="color: white; font-size: 14px; font-weight: 600; margin-bottom: 10px; display: block;">
                        <i class="fa fa-tag"></i> <?php echo get_phrase('payment_type'); ?>
                    </label>
                    <select name="payment_type" id="payment_type" style="width: 100%; padding: 12px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600;">
                        <option value="current"><?php echo get_phrase('current_day_fare'); ?></option>
                        <option value="advance"><?php echo get_phrase('load_wallet_advance'); ?></option>
                    </select>
                </div>

                <!-- Payment Method Quick Select -->
                <label style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 10px;">
                    <i class="fa fa-credit-card"></i> <?php echo get_phrase('payment_method'); ?>
                </label>
                <div class="payment-method-quick">
                    <?php
                    $payment_methods = $this->db->where('is_active', 1)->order_by('display_order')->get('payment_methods')->result_array();
                    $first = true;
                    foreach($payment_methods as $method):
                    ?>
                    <button type="button" <?= $first ? 'class="active"' : '' ?> onclick="selectPaymentMethod(<?= $method['id'] ?>, this)">
                        <i class="fa fa-<?= $method['icon'] ?>"></i><br><?= $method['name'] ?>
                    </button>
                    <?php $first = false; endforeach; ?>
                </div>
                <input type="hidden" name="payment_method" id="payment_method" value="<?= $payment_methods[0]['id'] ?? 1 ?>">

                <!-- Submit Button -->
                <button type="submit" class="btn-collect-transport" id="submitBtn" disabled>
                    <i class="fa fa-check-circle"></i> <?php echo get_phrase('collect_payment'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Mark Boarded Panel -->
    <div class="transport-panel" id="panel_board" style="display: none;">
        <h2 style="margin-bottom: 25px; color: #667eea;"><i class="fa fa-bus"></i> <?php echo get_phrase('mark_students_boarded'); ?></h2>
        
        <div class="quick-search">
            <select class="form-control select2" id="student_id_board" style="width: 100%; height: 60px; font-size: 18px;">
                <option value=""><?php echo get_phrase('search_student_by_name_or_code'); ?></option>
                <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['student_id']; ?>" 
                        data-route="<?php echo $student['route_name']; ?>"
                        data-fare="<?php echo $student['route_fare']; ?>">
                    <?php echo $student['student_code'] . ' - ' . $student['name'] . ' (' . $student['class_name'] . ')'; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="boardingForm" style="display: none;">
            <div class="student-card">
                <div class="student-info">
                    <div>
                        <div class="name" id="board_student_name"></div>
                        <div class="code" id="board_student_code"></div>
                    </div>
                    <div class="route-badge" id="board_route"></div>
                </div>

                <label style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 15px;">
                    <i class="fa fa-route"></i> <?php echo get_phrase('select_direction'); ?>
                </label>
                <div class="direction-selector">
                    <div class="direction-btn" onclick="selectBoardDirection('in')">
                        <div class="icon"><i class="fa fa-arrow-right"></i></div>
                        <div class="label"><?php echo get_phrase('morning_only'); ?></div>
                    </div>
                    <div class="direction-btn" onclick="selectBoardDirection('out')">
                        <div class="icon"><i class="fa fa-arrow-left"></i></div>
                        <div class="label"><?php echo get_phrase('afternoon_only'); ?></div>
                    </div>
                    <div class="direction-btn" onclick="selectBoardDirection('both')">
                        <div class="icon"><i class="fa fa-arrows-alt-h"></i></div>
                        <div class="label"><?php echo get_phrase('both_ways'); ?></div>
                    </div>
                    <div class="direction-btn" onclick="selectBoardDirection('none')">
                        <div class="icon"><i class="fa fa-times"></i></div>
                        <div class="label"><?php echo get_phrase('not_using'); ?></div>
                    </div>
                </div>
                <input type="hidden" id="board_direction" value="">
                <input type="hidden" id="board_student_id" value="">

                <button type="button" onclick="markStudentBoarded()" class="btn-collect-transport" id="boardBtn" disabled>
                    <i class="fa fa-check-circle"></i> <?php echo get_phrase('mark_as_boarded'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var baseFare = 0;

$(document).ready(function() {
    $('#student_id, #student_id_board').select2({
        placeholder: '<?php echo get_phrase('search_student_by_name_or_code'); ?>',
        allowClear: true,
        width: '100%'
    });
    
    loadStats();
    setInterval(loadStats, 30000);
});

function switchTab(tab) {
    if (tab === 'collect') {
        $('#tab_collect').css({'background': '#667eea', 'color': 'white'});
        $('#tab_board').css({'background': 'transparent', 'color': '#666'});
        $('#panel_collect').show();
        $('#panel_board').hide();
    } else {
        $('#tab_board').css({'background': '#667eea', 'color': 'white'});
        $('#tab_collect').css({'background': 'transparent', 'color': '#666'});
        $('#panel_board').show();
        $('#panel_collect').hide();
    }
}

function loadStats() {
    $.get('<?php echo site_url('fee_collection/dashboard_data'); ?>', function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.status === 'success') {
            $('#stat_collected').text('GHS ' + parseFloat(data.collections.transport || 0).toFixed(2));
            $('#stat_students').text(data.collections.count || 0);
        }
    });
}

$('#student_id').change(function() {
    var studentId = $(this).val();
    if (!studentId) {
        $('#transportCollectionForm').hide();
        return;
    }
    
    var selected = $(this).find(':selected');
    var route = selected.data('route');
    var fare = parseFloat(selected.data('fare'));
    
    $('#form_student_id').val(studentId);
    $('#selected_student_name').text(selected.text().split(' - ')[1].split(' (')[0]);
    $('#selected_student_code').text(selected.text().split(' - ')[0]);
    $('#selected_route').text(route);
    baseFare = fare;
    
    // Reset form
    $('.direction-btn').removeClass('active');
    $('#transport_direction').val('');
    $('#fare_display').text('GHS 0.00');
    $('#transport_amount').val(0);
    $('#submitBtn').prop('disabled', true);
    
    $('#transportCollectionForm').show();
});

function selectDirection(direction) {
    $('.direction-btn').removeClass('active');
    event.currentTarget.classList.add('active');
    $('#transport_direction').val(direction);
    
    var amount = 0;
    if (direction == 'in' || direction == 'out') {
        amount = baseFare;
    } else if (direction == 'both') {
        amount = baseFare * 2;
    }
    
    $('#fare_display').text('GHS ' + amount.toFixed(2));
    $('#transport_amount').val(amount.toFixed(2));
    $('#submitBtn').prop('disabled', direction === '');
}

$('#payment_type').change(function() {
    var paymentType = $(this).val();
    
    if (paymentType === 'advance') {
        $('#fare_display').text('Enter Amount');
        $('#transport_amount').val('').focus();
    } else {
        var direction = $('#transport_direction').val();
        if (direction && direction !== 'none') {
            selectDirection(direction);
        } else {
            $('#fare_display').text('GHS 0.00');
            $('#transport_amount').val('0');
        }
    }
});

function updateFareDisplay() {
    var amount = parseFloat($('#transport_amount').val() || 0);
    $('#fare_display').text('GHS ' + amount.toFixed(2));
}

function selectPaymentMethod(method, btn) {
    $('.payment-method-quick button').removeClass('active');
    $(btn).addClass('active');
    $('#payment_method').val(method);
}

$('#transportCollectionForm').submit(function(e) {
    e.preventDefault();
    
    var paymentType = $('#payment_type').val();
    var direction = $('#transport_direction').val();
    var amount = parseFloat($('#transport_amount').val());
    
    if (!direction) {
        showFeeErrorModal('<?php echo get_phrase('direction_required'); ?>', '<?php echo get_phrase('please_select_direction'); ?>');
        return;
    }
    
    if (amount <= 0 && direction !== 'none') {
        showFeeErrorModal('<?php echo get_phrase('invalid_amount'); ?>', '<?php echo get_phrase('please_enter_valid_amount'); ?>');
        return;
    }
    
    showFeeLoadingModal('<?php echo get_phrase('processing_payment'); ?>', '<?php echo get_phrase('please_wait'); ?>...');
    
    $.ajax({
        url: '<?php echo site_url('fee_collection/collect'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showFeeSuccessModal('<?php echo get_phrase('payment_collected'); ?>', response.message);
            
            // Show receipt if available
            if (response.receipt_data) {
                setTimeout(() => {
                    showFeeReceiptModal(response.receipt_data);
                }, 2500);
            }
            
            setTimeout(() => {
                $('#transportCollectionForm')[0].reset();
                $('#student_id').val('').trigger('change');
                $('#transportCollectionForm').hide();
                loadStats();
            }, 2000);
        } else {
            showFeeErrorModal('<?php echo get_phrase('payment_failed'); ?>', response.message || '<?php echo get_phrase('operation_failed'); ?>');
        }
    }).fail(function() {
        showFeeErrorModal('<?php echo get_phrase('connection_error'); ?>', '<?php echo get_phrase('an_error_occurred'); ?>');
    });
});

$('#student_id_board').change(function() {
    var studentId = $(this).val();
    if (!studentId) {
        $('#boardingForm').hide();
        return;
    }
    
    var selected = $(this).find(':selected');
    var route = selected.data('route');
    
    $('#board_student_id').val(studentId);
    $('#board_student_name').text(selected.text().split(' - ')[1].split(' (')[0]);
    $('#board_student_code').text(selected.text().split(' - ')[0]);
    $('#board_route').text(route);
    
    $('.direction-btn').removeClass('active');
    $('#board_direction').val('');
    $('#boardBtn').prop('disabled', true);
    
    $('#boardingForm').show();
});

function selectBoardDirection(direction) {
    $('.direction-btn').removeClass('active');
    event.currentTarget.classList.add('active');
    $('#board_direction').val(direction);
    $('#boardBtn').prop('disabled', false);
}

function markStudentBoarded() {
    var studentId = $('#board_student_id').val();
    var direction = $('#board_direction').val();
    
    if (!direction || direction === 'none') {
        showFeeErrorModal('<?php echo get_phrase('direction_required'); ?>', '<?php echo get_phrase('please_select_direction'); ?>');
        return;
    }
    
    showFeeLoadingModal('<?php echo get_phrase('marking_boarded'); ?>', '<?php echo get_phrase('please_wait'); ?>...');
    
    $.ajax({
        url: '<?php echo site_url('fee_collection/mark_boarded'); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            direction: direction
        },
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showFeeSuccessModal('<?php echo get_phrase('student_boarded'); ?>', response.message || '<?php echo get_phrase('student_marked_as_boarded'); ?>');
            setTimeout(() => {
                $('#student_id_board').val('').trigger('change');
                $('#boardingForm').hide();
                loadStats();
            }, 2000);
        } else {
            showFeeErrorModal('<?php echo get_phrase('boarding_failed'); ?>', response.message || '<?php echo get_phrase('operation_failed'); ?>');
        }
    }).fail(function() {
        showFeeErrorModal('<?php echo get_phrase('connection_error'); ?>', '<?php echo get_phrase('an_error_occurred'); ?>');
    });
}
</script>
