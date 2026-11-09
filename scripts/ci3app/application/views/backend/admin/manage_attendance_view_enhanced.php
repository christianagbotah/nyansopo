<?php
// Enhanced Attendance Management View with Bulk Payment Support
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
$students_ids = explode('-', $student_id);
$class_name = $this->crud_model->get_class_name($class_id);

// Get class charges from daily_fee_rates
$year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$fee_rates = $this->db->get_where('daily_fee_rates', array('class_id' => $class_id, 'year' => $year, 'term' => $term))->row();
$feeding_fee_charged = ($fee_rates && isset($fee_rates->feeding_fee)) ? $fee_rates->feeding_fee : 0;
$classes_fee_charged = ($fee_rates && isset($fee_rates->classes_fee)) ? $fee_rates->classes_fee : 0;
?>

<!-- Loading Overlay -->
<div id="pre_notice">
    <center>
        <h3 style="color: #fff;"><i class="fa fa-spinner fa-pulse"></i> Loading please wait...</h3>
        <p style="color: #b3aeae;">Getting attendance page ready</p>
    </center>
</div>

<style>
#pre_notice {
    position: fixed; z-index: 99999; top: 0; left: 0; bottom: 0; right: 0;
    background: rgba(0, 0, 0, 0.9); transition: 1s 0.4s;
}
#pre_notice h3 { margin-top: 45vh; }
.benefit-badge {
    display: inline-block; padding: 2px 8px; border-radius: 12px;
    font-size: 11px; font-weight: bold; background: #fbbf24; color: #78350f;
}
.bulk-select-checkbox {
    width: 20px; height: 20px; cursor: pointer;
}
</style>

<!-- Bulk Payment Modal -->
<div id="bulk_payment_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-blue-600 text-white">
                <h5 class="modal-title">Bulk Payment Processing</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="mb-4">Enter payment amounts for <strong><span id="selected_count_modal">0</span></strong> selected students:</p>
                
                <div class="form-group">
                    <label class="font-bold">Feeding Fee Amount</label>
                    <input type="number" id="bulk_feeding_amount" class="form-control" placeholder="0.00" step="0.01">
                </div>
                
                <div class="form-group">
                    <label class="font-bold">Classes Fee Amount</label>
                    <input type="number" id="bulk_classes_amount" class="form-control" placeholder="0.00" step="0.01">
                </div>
                
                <div class="form-group">
                    <label class="font-bold">Transport Fee Amount</label>
                    <input type="number" id="bulk_transport_amount" class="form-control" placeholder="0.00" step="0.01">
                </div>
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> This amount will be applied to all selected students who are marked present.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processBulkPayment()">
                    <i class="fa fa-check"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container-fluid">
    
    <!-- Header Section -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-blue-600 p-3 rounded-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">ATTENDANCE & BILLING FOR <?php echo strtoupper($class_name); ?></h3>
                <p class="text-base text-gray-600"><?php echo date("d M Y", (int)$timestamp); ?></p>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-green-50 rounded-lg p-4 border-2 border-green-200">
                <p class="text-sm font-semibold text-green-800 mb-1">FEEDING FEE COLLECTED</p>
                <p class="text-2xl font-bold text-green-600"><?php echo numfmt_format_currency($fmt, 0, $currency); ?></p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                <p class="text-sm font-semibold text-blue-800 mb-1">CLASSES FEE COLLECTED</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo numfmt_format_currency($fmt, 0, $currency); ?></p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 border-2 border-purple-200">
                <p class="text-sm font-semibold text-purple-800 mb-1">TRANSPORT FEE COLLECTED</p>
                <p class="text-2xl font-bold text-purple-600"><?php echo numfmt_format_currency($fmt, 0, $currency); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions Bar -->
    <div id="bulk_actions_bar" class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6" style="display: none;">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-lg font-bold"><span id="selected_students_count">0</span> students selected</span>
            </div>
            <div class="flex gap-3">
                <button type="button" class="btn btn-info" onclick="selectAllPresent()">
                    <i class="fa fa-check-square"></i> Select All Present
                </button>
                <button type="button" class="btn btn-warning" onclick="deselectAll()">
                    <i class="fa fa-times"></i> Deselect All
                </button>
                <button type="button" class="btn btn-primary" onclick="openBulkPaymentModal()">
                    <i class="fa fa-money"></i> Process Bulk Payment
                </button>
            </div>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
        <input type="text" id="attendance_search" placeholder="Search by name or student code..." 
               class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5">
    </div>
    
    <!-- Attendance Form -->
    <?php echo form_open(site_url('admin/attendance_update/'.$class_id.'/'.$section_id.'/'.$timestamp), array('id' => 'attendance_form')); ?>
    
    <div id="attendance_grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $count = 1;
        $this->db->where_in('attendance.student_id', $students_ids);
        $this->db->join('student', 'student.student_id = attendance.student_id');
        $this->db->order_by('name', 'asc');
        $attendance_query = $this->db->get_where('attendance', array(
            'class_id' => $class_id,
            'section_id' => $section_id,
            'year' => $running_year,
            'term' => $running_term,
            'timestamp' => $timestamp
        ));
        
        foreach($attendance_query->result_array() as $row):
            $student = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
            
            // Get benefit category if applicable
            $benefit_category = null;
            $feeding_charge = $feeding_fee_charged;
            $classes_charge = $classes_fee_charged;
            
            if(property_exists($student, 'benefit_status') && $student->benefit_status == 1 && property_exists($student, 'benefit_category_id') && $student->benefit_category_id) {
                $benefit_category = $this->db->get_where('benefit_category', 
                    array('category_id' => $student->benefit_category_id))->row();
                
                // Calculate discounted charges
                if($benefit_category->discount_type == 'percentage') {
                    $feeding_charge = $feeding_fee_charged - ($feeding_fee_charged * ($benefit_category->feeding_discount / 100));
                    $classes_charge = $classes_fee_charged - ($classes_fee_charged * ($benefit_category->classes_discount / 100));
                } else {
                    $feeding_charge = max(0, $feeding_fee_charged - $benefit_category->feeding_discount);
                    $classes_charge = max(0, $classes_fee_charged - $benefit_category->classes_discount);
                }
            }
            
            // Get transport charge if assigned
            $transport_charge = 0;
            if(property_exists($student, 'transport_id') && $student->transport_id) {
                $transport = $this->db->get_where('transport', 
                    array('transport_id' => $student->transport_id))->row();
                if($transport) {
                    $transport_charge = $transport->route_fare;
                }
            }
            
            // Get existing payment data from unified table
            $payment = $this->db->get_where('daily_fee_transactions', array(
                'student_id' => $row['student_id'],
                'payment_date' => $timestamp
            ))->row();
            
            $feeding_paid = ($payment && isset($payment->feeding_amount)) ? $payment->feeding_amount : 0;
            $classes_paid = ($payment && isset($payment->classes_amount)) ? $payment->classes_amount : 0;
            $feeding_owe = $feeding_charge - $feeding_paid;
            $classes_owe = $classes_charge - $classes_paid;
            
            // Transport payment from same unified table
            $transport_paid = ($payment && isset($payment->transport_amount)) ? $payment->transport_amount : 0;
            $transport_owe = $transport_charge - $transport_paid;
            
            $cardColors = [
                'bg-blue-50 border-blue-200', 'bg-green-50 border-green-200',
                'bg-purple-50 border-purple-200', 'bg-orange-50 border-orange-200'
            ];
            $colorIndex = ($count - 1) % count($cardColors);
            $cardColor = $cardColors[$colorIndex];
        ?>
        
        <div class="<?php echo $cardColor; ?> rounded-xl shadow-md border-2 p-6 hover:shadow-lg transition-shadow attendance-card" 
             data-student-id="<?php echo $row['student_id']; ?>"
             data-student-name="<?php echo $student->name; ?>"
             data-student-code="<?php echo $student->student_code; ?>">
            
            <!-- Header with Checkbox and Student Info -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <input type="checkbox" class="bulk-select-checkbox" 
                           data-student-id="<?php echo $row['student_id']; ?>"
                           onchange="toggleBulkSelection(<?php echo $row['student_id']; ?>)">
                    <div class="bg-blue-100 text-blue-800 font-bold rounded-full w-10 h-10 flex items-center justify-center">
                        <?php echo $count++; ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900"><?php echo $student->name; ?></h3>
                        <p class="text-base font-semibold text-gray-700"><?php echo $student->student_code; ?></p>
                        <?php if($benefit_category): ?>
                            <span class="benefit-badge"><?php echo $benefit_category->category_name; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Attendance Status -->
                <select class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg p-2.5 font-semibold attendance-status" 
                        name="status_<?php echo $row['attendance_id']; ?>" 
                        id="status_<?php echo $row['student_id']; ?>"
                        onchange="handleAttendanceChange(<?php echo $row['student_id']; ?>)">
                    <option value="1" <?php if($row['status'] == 1) echo 'selected'; ?>>Present (P)</option>
                    <option value="2" <?php if($row['status'] == 2) echo 'selected'; ?>>Absent (A)</option>
                    <option value="3" <?php if($row['status'] == 3) echo 'selected'; ?>>Busy (B)</option>
                    <option value="4" <?php if($row['status'] == 4) echo 'selected'; ?>>Sick-Home (S)</option>
                    <option value="5" <?php if($row['status'] == 5) echo 'selected'; ?>>Sick-Clinic (C)</option>
                </select>
            </div>
            
            <!-- Payment Fields -->
            <div class="space-y-3">
                <!-- Feeding Fee -->
                <div class="bg-green-50 rounded-lg p-3 border-2 border-green-200">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-bold text-green-800">Feeding Fee</span>
                        <span class="text-xs text-green-700">Charge: <?php echo number_format($feeding_charge, 2); ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-green-800 mb-1">Paid</label>
                            <input type="number" 
                                   name="feeding_paid_<?php echo $row['student_id']; ?>" 
                                   id="feeding_paid_<?php echo $row['student_id']; ?>"
                                   class="fee-input bg-white border-2 border-green-300 text-gray-900 text-base rounded-lg block w-full p-2"
                                   value="<?php echo $feeding_paid; ?>"
                                   step="0.01"
                                   onkeyup="calculateBalance(<?php echo $row['student_id']; ?>, 'feeding', <?php echo $feeding_charge; ?>)">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-green-800 mb-1">Balance</label>
                            <input type="number" 
                                   name="feeding_owe_<?php echo $row['student_id']; ?>"
                                   id="feeding_owe_<?php echo $row['student_id']; ?>"
                                   class="fee-input bg-white border-2 border-green-300 text-gray-900 text-base rounded-lg block w-full p-2 font-bold"
                                   value="<?php echo $feeding_owe; ?>"
                                   readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Classes Fee -->
                <div class="bg-blue-50 rounded-lg p-3 border-2 border-blue-200">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-bold text-blue-800">Classes Fee</span>
                        <span class="text-xs text-blue-700">Charge: <?php echo number_format($classes_charge, 2); ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-blue-800 mb-1">Paid</label>
                            <input type="number" 
                                   name="classes_paid_<?php echo $row['student_id']; ?>"
                                   id="classes_paid_<?php echo $row['student_id']; ?>"
                                   class="fee-input bg-white border-2 border-blue-300 text-gray-900 text-base rounded-lg block w-full p-2"
                                   value="<?php echo $classes_paid; ?>"
                                   step="0.01"
                                   onkeyup="calculateBalance(<?php echo $row['student_id']; ?>, 'classes', <?php echo $classes_charge; ?>)">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-blue-800 mb-1">Balance</label>
                            <input type="number" 
                                   name="classes_owe_<?php echo $row['student_id']; ?>"
                                   id="classes_owe_<?php echo $row['student_id']; ?>"
                                   class="fee-input bg-white border-2 border-blue-300 text-gray-900 text-base rounded-lg block w-full p-2 font-bold"
                                   value="<?php echo $classes_owe; ?>"
                                   readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Transport Fee (if applicable) -->
                <?php if($transport_charge > 0): ?>
                <div class="bg-purple-50 rounded-lg p-3 border-2 border-purple-200">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-bold text-purple-800">Transport Fee</span>
                        <span class="text-xs text-purple-700">Charge: <?php echo number_format($transport_charge, 2); ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-purple-800 mb-1">Paid</label>
                            <input type="number" 
                                   name="transport_paid_<?php echo $row['student_id']; ?>"
                                   id="transport_paid_<?php echo $row['student_id']; ?>"
                                   class="fee-input bg-white border-2 border-purple-300 text-gray-900 text-base rounded-lg block w-full p-2"
                                   value="<?php echo $transport_paid; ?>"
                                   step="0.01"
                                   onkeyup="calculateBalance(<?php echo $row['student_id']; ?>, 'transport', <?php echo $transport_charge; ?>)">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-purple-800 mb-1">Balance</label>
                            <input type="number" 
                                   name="transport_owe_<?php echo $row['student_id']; ?>"
                                   id="transport_owe_<?php echo $row['student_id']; ?>"
                                   class="fee-input bg-white border-2 border-purple-300 text-gray-900 text-base rounded-lg block w-full p-2 font-bold"
                                   value="<?php echo $transport_owe; ?>"
                                   readonly>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Hidden fields for charges -->
            <input type="hidden" id="feeding_charge_<?php echo $row['student_id']; ?>" value="<?php echo $feeding_charge; ?>">
            <input type="hidden" id="classes_charge_<?php echo $row['student_id']; ?>" value="<?php echo $classes_charge; ?>">
            <input type="hidden" id="transport_charge_<?php echo $row['student_id']; ?>" value="<?php echo $transport_charge; ?>">
        </div>
        
        <?php endforeach; ?>
    </div>
    
    <!-- Hidden Fields -->
    <input type="hidden" name="students_ids" value="<?php echo $student_id; ?>">
    <input type="hidden" name="payment_time" value="<?php echo date('H:i:s'); ?>">
    
    <!-- Save Button -->
    <div class="fixed bottom-6 right-6 z-50">
        <button type="submit" id="submit_button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-lg text-lg transition-all shadow-lg hover:shadow-xl">
            <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save All Changes
        </button>
    </div>
    
    <?php echo form_close(); ?>
</div>

<script>
// Bulk selection management
let selectedStudents = [];

function toggleBulkSelection(studentId) {
    const checkbox = $(`.bulk-select-checkbox[data-student-id="${studentId}"]`);
    const index = selectedStudents.indexOf(studentId);
    
    if(checkbox.is(':checked')) {
        if(index === -1) selectedStudents.push(studentId);
    } else {
        if(index > -1) selectedStudents.splice(index, 1);
    }
    
    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    $('#selected_students_count').text(selectedStudents.length);
    $('#selected_count_modal').text(selectedStudents.length);
    
    if(selectedStudents.length > 0) {
        $('#bulk_actions_bar').slideDown();
    } else {
        $('#bulk_actions_bar').slideUp();
    }
}

function selectAllPresent() {
    selectedStudents = [];
    $('.attendance-card').each(function() {
        const studentId = $(this).data('student-id');
        const status = $(`#status_${studentId}`).val();
        if(status == '1') {
            selectedStudents.push(studentId);
            $(`.bulk-select-checkbox[data-student-id="${studentId}"]`).prop('checked', true);
        }
    });
    updateBulkActionsBar();
}

function deselectAll() {
    selectedStudents = [];
    $('.bulk-select-checkbox').prop('checked', false);
    updateBulkActionsBar();
}

function openBulkPaymentModal() {
    if(selectedStudents.length === 0) {
        alert('Please select at least one student');
        return;
    }
    $('#bulk_payment_modal').modal('show');
}

function processBulkPayment() {
    const feedingAmount = parseFloat($('#bulk_feeding_amount').val()) || 0;
    const classesAmount = parseFloat($('#bulk_classes_amount').val()) || 0;
    const transportAmount = parseFloat($('#bulk_transport_amount').val()) || 0;
    
    if(feedingAmount === 0 && classesAmount === 0 && transportAmount === 0) {
        alert('Please enter at least one payment amount');
        return;
    }
    
    // Apply amounts to all selected students
    selectedStudents.forEach(studentId => {
        if(feedingAmount > 0) {
            $(`#feeding_paid_${studentId}`).val(feedingAmount);
            calculateBalance(studentId, 'feeding', parseFloat($(`#feeding_charge_${studentId}`).val()));
        }
        if(classesAmount > 0) {
            $(`#classes_paid_${studentId}`).val(classesAmount);
            calculateBalance(studentId, 'classes', parseFloat($(`#classes_charge_${studentId}`).val()));
        }
        if(transportAmount > 0) {
            $(`#transport_paid_${studentId}`).val(transportAmount);
            calculateBalance(studentId, 'transport', parseFloat($(`#transport_charge_${studentId}`).val()));
        }
    });
    
    $('#bulk_payment_modal').modal('hide');
    alert(`Bulk payment applied to ${selectedStudents.length} students. Click "Save All Changes" to confirm.`);
}

function calculateBalance(studentId, feeType, charge) {
    const paid = parseFloat($(`#${feeType}_paid_${studentId}`).val()) || 0;
    const balance = charge - paid;
    $(`#${feeType}_owe_${studentId}`).val(balance.toFixed(2));
    
    // Color coding
    const oweField = $(`#${feeType}_owe_${studentId}`);
    if(balance > 0) {
        oweField.css({'background-color': '#fee2e2', 'color': '#991b1b', 'border-color': '#ef4444'});
    } else if(balance === 0) {
        oweField.css({'background-color': '#d1fae5', 'color': '#065f46', 'border-color': '#10b981'});
    } else {
        oweField.css({'background-color': '#dbeafe', 'color': '#1e40af', 'border-color': '#3b82f6'});
    }
}

function handleAttendanceChange(studentId) {
    const status = $(`#status_${studentId}`).val();
    const isPresent = (status == '1');
    
    // Enable/disable payment fields based on attendance
    $(`#feeding_paid_${studentId}`).prop('readonly', !isPresent);
    $(`#classes_paid_${studentId}`).prop('readonly', !isPresent);
    $(`#transport_paid_${studentId}`).prop('readonly', !isPresent);
    
    if(!isPresent) {
        // Clear payments if marked absent
        $(`#feeding_paid_${studentId}`).val(0);
        $(`#classes_paid_${studentId}`).val(0);
        $(`#transport_paid_${studentId}`).val(0);
        
        // Recalculate balances
        calculateBalance(studentId, 'feeding', parseFloat($(`#feeding_charge_${studentId}`).val()));
        calculateBalance(studentId, 'classes', parseFloat($(`#classes_charge_${studentId}`).val()));
        const transportCharge = parseFloat($(`#transport_charge_${studentId}`).val());
        if(transportCharge > 0) {
            calculateBalance(studentId, 'transport', transportCharge);
        }
        
        // Uncheck bulk selection
        $(`.bulk-select-checkbox[data-student-id="${studentId}"]`).prop('checked', false);
        const index = selectedStudents.indexOf(studentId);
        if(index > -1) selectedStudents.splice(index, 1);
        updateBulkActionsBar();
    }
}

// Search functionality
$('#attendance_search').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('.attendance-card').each(function() {
        const studentName = $(this).data('student-name').toLowerCase();
        const studentCode = $(this).data('student-code').toLowerCase();
        if(studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Initialize on page load
$(document).ready(function() {
    $('#pre_notice').fadeOut(400, function() {
        $(this).remove();
    });
    
    // Initialize balance colors
    $('.attendance-card').each(function() {
        const studentId = $(this).data('student-id');
        const feedingCharge = parseFloat($(`#feeding_charge_${studentId}`).val());
        const classesCharge = parseFloat($(`#classes_charge_${studentId}`).val());
        const transportCharge = parseFloat($(`#transport_charge_${studentId}`).val());
        
        calculateBalance(studentId, 'feeding', feedingCharge);
        calculateBalance(studentId, 'classes', classesCharge);
        if(transportCharge > 0) {
            calculateBalance(studentId, 'transport', transportCharge);
        }
        
        // Disable fields for absent students
        handleAttendanceChange(studentId);
    });
});
</script>
