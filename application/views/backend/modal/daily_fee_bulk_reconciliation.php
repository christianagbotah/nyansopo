<?php
// Active modules are passed from the controller
?>

<style>
.bulk-student-row {
    border-bottom: 2px solid #e2e8f0;
}
.bulk-student-row:last-child {
    border-bottom: none;
}
.student-name-cell {
    font-weight: 700;
    color: #1e293b;
    background: #f8fafc;
    padding: 12px !important;
    vertical-align: middle;
    position: sticky;
    left: 0;
    z-index: 10;
    border-right: 2px solid #e2e8f0;
    min-width: 240px;
    max-width: 240px;
    text-align: left;
}
.student-name-cell .student-code-display {
    font-size: 10px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 600;
    display: block;
    white-space: nowrap;
    text-align: left;
}
.student-name-cell .student-name-display {
    font-size: 12px;
    color: #1e293b;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    text-align: left;
}
.bulk-recon-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    font-size: 11px;
}
.bulk-recon-table thead th {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    padding: 10px 8px;
    text-align: center;
    font-weight: 700;
    border: 1px solid #7c3aed;
    font-size: 10px;
    white-space: nowrap;
}
.bulk-recon-table thead th.fee-header {
    font-size: 11px;
    border-bottom: 2px solid white;
}
.bulk-recon-table thead th.sub-header {
    font-size: 9px;
    font-weight: 600;
    padding: 6px 4px;
}
.bulk-recon-table thead th.sub-header.prepaid-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}
.bulk-recon-table thead th.sub-header.arrears-header {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}
.bulk-recon-table tbody td {
    padding: 8px 6px;
    text-align: center;
    border: 1px solid #e2e8f0;
    background: white;
}
.bulk-input {
    width: 100%;
    min-width: 70px;
    padding: 6px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    background: white;
}
.bulk-input.prepaid-input {
    border-color: #10b981;
    background: #f0fdf4;
}
.bulk-input.arrears-input {
    border-color: #ef4444;
    background: #fef2f2;
}
.bulk-input:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}
.bulk-input.prepaid-input:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
.bulk-input.arrears-input:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}
.bulk-input.changed {
    background: #fef3c7;
    border-color: #f59e0b;
    font-weight: 700;
}
.sticky-action-bar {
    position: sticky;
    top: 0;
    z-index: 50;
    background: white;
    padding: 20px;
    border-radius: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin: 0 0 15px 0;
    border-bottom: 2px solid #e2e8f0;
}
.sticky-action-bar.scrolled {
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}
.modal-body-scrollable {
    max-height: 80vh;
    overflow-y: auto;
    overflow-x: hidden;
}
</style>

<!-- Modal Header -->
<div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 1.5rem 2rem; margin: -20px -20px 0 -20px; border-radius: 12px 12px 0 0; position: relative;">
    <button type="button" class="close" data-dismiss="modal" style="position: absolute; top: 12px; right: 12px; background: #ef4444; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); opacity: 1; text-shadow: none;" onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1) rotate(90deg)'" onmouseout="this.style.background='#ef4444'; this.style.transform='scale(1) rotate(0deg)'">&times;</button>
    <div style="display: flex; align-items: center; gap: 15px; color: white;">
        <div style="background: rgba(255,255,255,0.2); padding: 14px; border-radius: 12px; backdrop-filter: blur(10px);">
            <i class="fa fa-users" style="font-size: 26px;"></i>
        </div>
        <div style="flex: 1;">
            <h2 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 700; color: white; line-height: 1.3;">Bulk Balance Reconciliation</h2>
            <p style="margin: 0; opacity: 0.95; font-size: 13px; line-height: 1.4;">
                <i class="fa fa-info-circle" style="margin-right: 4px;"></i> Adjust balances for <strong><span id="bulk_selected_count">0</span> selected students</strong> - modify values and click Apply
            </p>
        </div>
    </div>
</div>

<div class="modal-body modal-body-scrollable" style="padding: 0; background: #f8f9fa;">
    
    <!-- Sticky Action Bar with Filter and Buttons -->
    <div class="sticky-action-bar">
        <div class="row" style="align-items: center; margin: 0;">
            <div class="col-md-6">
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 700; font-size: 11px; color: #2d3748;">
                        <i class="fa fa-search"></i> Quick Filter by Student Name
                        <span id="bulk_filter_count" style="margin-left: 8px; background: #8b5cf6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; display: none;"></span>
                    </label>
                    <input type="text" id="bulk_filter_student" class="form-control" placeholder="Type to filter students in table below..." style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px; font-size: 12px; height: 40px;" onkeyup="filterBulkStudents()">
                </div>
            </div>
            <div class="col-md-6" style="text-align: right;">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 10px 24px; font-size: 13px; font-weight: 600; margin-right: 8px;">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="applyBulkReconciliation()" style="padding: 10px 24px; font-size: 13px; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                    <i class="fa fa-check"></i> Apply Adjustments
                </button>
            </div>
        </div>
    </div>
    
    <div style="padding: 0 20px 20px 20px;">
        <!-- Preloader -->
        <div id="bulk_loading_preloader" style="display: none; text-align: center; padding: 60px 20px;">
            <div style="display: inline-block;">
                <i class="fa fa-spinner fa-spin" style="font-size: 48px; color: #8b5cf6; margin-bottom: 20px;"></i>
                <h3 style="color: #1e293b; font-weight: 600; margin: 0 0 10px 0;">Loading Students...</h3>
                <p style="color: #64748b; font-size: 14px; margin: 0;">Please wait while we load the selected student data</p>
            </div>
        </div>
        
        <div style="background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 15px;">
            <div style="overflow-x: auto;">
                <table class="bulk-recon-table" id="bulk_adjustment_table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width: 240px; max-width: 240px; left: 0; z-index: 30; text-align: left;">Student</th>
                            <?php foreach ($active_modules as $module): ?>
                                <th colspan="2" class="fee-header"><?php echo strtoupper($module); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($active_modules as $module): ?>
                                <th class="sub-header prepaid-header">Prepaid</th>
                                <th class="sub-header arrears-header">Arrears</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="bulk_students_tbody">
                        <tr>
                            <td colspan="<?php echo (count($active_modules) * 2) + 1; ?>" style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fa fa-info-circle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                Select students from the main page using checkboxes, then click "Bulk Adjust" button
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; color: #2d3748;">
                <i class="fa fa-comment"></i> Reason for Bulk Adjustment
            </label>
            <textarea id="bulk_reason_textarea" class="form-control" rows="2" required placeholder="Provide a reason for these adjustments..." style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px; font-size: 12px;"></textarea>
        </div>

    </div>
</div>

<!-- Progress Modal Overlay -->
<div id="bulk_progress_modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; width: 90%; text-align: center;">
        <div style="margin-bottom: 20px;">
            <i class="fa fa-cog fa-spin" style="font-size: 48px; color: #8b5cf6;"></i>
        </div>
        <h3 style="margin: 0 0 10px 0; color: #1e293b; font-weight: 700;">Processing Adjustments</h3>
        <p style="color: #64748b; margin-bottom: 20px; font-size: 13px;">Please wait while we save the changes...</p>
        
        <div class="progress" style="height: 30px; border-radius: 15px; background: #f1f5f9; overflow: hidden;">
            <div class="progress-bar progress-bar-striped active" role="progressbar" id="bulk_progress_bar_main" style="width: 0%; font-size: 13px; font-weight: 700; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); line-height: 30px;"></div>
        </div>
        
        <p id="bulk_progress_text_main" style="margin-top: 15px; font-size: 12px; color: #475569; font-weight: 600;"></p>
        
        <div id="bulk_progress_details" style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 10px; font-size: 11px; color: #64748b;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span>Successful:</span>
                <span id="bulk_success_count" style="color: #10b981; font-weight: 700;">0</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Failed:</span>
                <span id="bulk_failed_count" style="color: #ef4444; font-weight: 700;">0</span>
            </div>
        </div>
    </div>
</div>

<script>
var bulkStudentsData = [];

// Add scroll effect for sticky bar
$(document).on('shown.bs.modal', '#bulkReconciliationModal', function () {
    var modalBody = $(this).find('.modal-body-scrollable');
    
    modalBody.off('scroll').on('scroll', function() {
        if ($(this).scrollTop() > 20) {
            $('.sticky-action-bar').addClass('scrolled');
        } else {
            $('.sticky-action-bar').removeClass('scrolled');
        }
    });
});

function loadBulkStudentsData(selectedIds) {
    console.log('loadBulkStudentsData called with:', selectedIds);
    
    // Show preloader
    $('#bulk_loading_preloader').show();
    $('#bulk_adjustment_table').hide();
    
    if (!selectedIds || selectedIds.length === 0) {
        $('#bulk_loading_preloader').hide();
        $('#bulk_adjustment_table').show();
        $('#bulk_students_tbody').html('<tr><td colspan="<?php echo (count($active_modules) * 2) + 1; ?>" style="text-align: center; padding: 40px; color: #64748b;"><i class="fa fa-info-circle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>No students selected</td></tr>');
        return;
    }
    
    $('#bulk_selected_count').text(selectedIds.length);
    
    // Get student data from main page table
    var tbody = $('#bulk_students_tbody');
    tbody.empty();
    
    bulkStudentsData = [];
    
    selectedIds.forEach(function(studentId) {
        // Find student data from main table
        var studentRow = $('.bulk-select-checkbox[data-student-id="' + studentId + '"]').closest('tr');
        console.log('Processing student ID:', studentId, 'Found row:', studentRow.length);
        
        var studentName = studentRow.find('td').eq(2).text().trim();
        var studentCode = studentRow.find('.student-code').text().trim();
        
        console.log('Student:', studentCode, studentName);
        
        var studentData = {
            student_id: studentId,
            name: studentName,
            code: studentCode,
            adjustments: {}
        };
        
        var row = '<tr class="bulk-student-row">';
        row += '<td class="student-name-cell">';
        row += '<span class="student-code-display">' + studentCode + '</span>';
        row += '<span class="student-name-display" title="' + studentName + '">' + studentName + '</span>';
        row += '</td>';
        
        <?php foreach ($active_modules as $module): ?>
            // Get current values from main table using data attributes
            var <?php echo $module; ?>BalanceElem = studentRow.find('.<?php echo $module; ?>-balance-value');
            var <?php echo $module; ?>ArrearsElem = studentRow.find('.<?php echo $module; ?>-arrears-value');
            
            var <?php echo $module; ?>Balance = parseFloat(<?php echo $module; ?>BalanceElem.data('balance')) || 0;
            var <?php echo $module; ?>Arrears = parseFloat(<?php echo $module; ?>ArrearsElem.data('arrears')) || 0;
            
            console.log('<?php echo $module; ?>: balance=', <?php echo $module; ?>Balance, 'arrears=', <?php echo $module; ?>Arrears);
            
            row += '<td><input type="number" class="bulk-input prepaid-input" data-student-id="' + studentId + '" data-fee-type="<?php echo $module; ?>" data-adjustment-type="prepaid" step="0.01" min="0" value="' + <?php echo $module; ?>Balance.toFixed(2) + '" onchange="markChanged(this)"></td>';
            row += '<td><input type="number" class="bulk-input arrears-input" data-student-id="' + studentId + '" data-fee-type="<?php echo $module; ?>" data-adjustment-type="arrears" step="0.01" min="0" value="' + <?php echo $module; ?>Arrears.toFixed(2) + '" onchange="markChanged(this)"></td>';
        <?php endforeach; ?>
        
        row += '</tr>';
        tbody.append(row);
        
        bulkStudentsData.push(studentData);
    });
    
    // Hide preloader and show table
    setTimeout(function() {
        $('#bulk_loading_preloader').hide();
        $('#bulk_adjustment_table').show();
    }, 300); // Small delay to ensure rendering is complete
}

function markChanged(input) {
    console.log('markChanged called for:', input);
    $(input).addClass('changed');
    console.log('Classes after adding changed:', $(input).attr('class'));
}

function filterBulkStudents() {
    var searchTerm = $('#bulk_filter_student').val().toLowerCase().trim();
    var visibleCount = 0;
    var totalCount = $('#bulk_students_tbody tr').length;
    
    if (searchTerm === '') {
        // Show all rows
        $('#bulk_students_tbody tr').show();
        $('#bulk_filter_count').hide();
        visibleCount = totalCount;
    } else {
        // Filter rows
        $('#bulk_students_tbody tr').each(function() {
            var studentName = $(this).find('.student-name-cell').text().toLowerCase();
            
            if (studentName.includes(searchTerm)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        // Show filter badge
        $('#bulk_filter_count').text('Showing ' + visibleCount + ' of ' + totalCount).show();
    }
}

function applyBulkReconciliation() {
    var reason = $('#bulk_reason_textarea').val().trim();
    if (!reason) {
        showConfirmModal('Validation Error', 'Please provide a reason for the adjustments', null, 'OK', 'warning');
        return;
    }
    
    // Collect ONLY changed adjustments
    var adjustments = [];
    var studentsAffected = new Set();
    
    $('#bulk_students_tbody tr').each(function() {
        var studentId = $(this).find('.bulk-input').first().data('student-id');
        
        if (!studentId) return; // Skip empty rows
        
        // Collect ONLY changed inputs for this student
        $(this).find('.bulk-input.changed').each(function() {
            var input = $(this);
            var feeType = input.data('fee-type');
            var adjustmentType = input.data('adjustment-type');
            var amount = parseFloat(input.val()) || 0;
            
            adjustments.push({
                student_id: studentId,
                fee_type: feeType,
                adjustment_type: adjustmentType,
                amount: amount
            });
            
            studentsAffected.add(studentId);
        });
    });
    
    if (adjustments.length === 0) {
        showConfirmModal('No Changes', 'No values have been modified. Edit the fields you want to change.', null, 'OK', 'info');
        return;
    }
    
    console.log('Total adjustments to process:', adjustments.length);
    console.log('Students affected:', studentsAffected.size);
    
    // Confirm before applying
    console.log('Showing bulk confirmation modal'); // Debug
    
    showConfirmModal(
        'Confirm Bulk Adjustments',
        'You are about to apply reconciliation for <strong>' + studentsAffected.size + ' student(s)</strong>.<br>' +
        'Total adjustments: <strong>' + adjustments.length + '</strong><br><br>Do you want to proceed?',
        function() {
            console.log('Bulk confirmation accepted, calling processBulkAdjustments'); // Debug
            processBulkAdjustments(adjustments, reason);
        },
        'Yes, Apply',
        'warning'
    );
}

function processBulkAdjustments(adjustments, reason) {
    console.log('processBulkAdjustments called with:', adjustments.length, 'adjustments'); // Debug
    
    // Show blocking progress modal
    $('#bulk_progress_modal').css('display', 'flex');
    
    // Disable all controls
    $('button').prop('disabled', true);
    $('.bulk-input').prop('disabled', true);
    $('#bulk_reason_textarea').prop('disabled', true);
    
    var completed = 0;
    var succeeded = 0;
    var failed = 0;
    var total = adjustments.length;
    var errors = [];
    
    // Update initial counts
    $('#bulk_success_count').text('0');
    $('#bulk_failed_count').text('0');
    
    // Process adjustments sequentially to avoid overwhelming the server
    var index = 0;
    
    function processNext() {
        if (index >= adjustments.length) {
            // All done
            finishBulkAdjustments(total, succeeded, failed, errors);
            return;
        }
        
        var adj = adjustments[index];
        index++;
        
        $.ajax({
            url: '<?php echo base_url(); ?>admin/adjust_student_balance',
            type: 'POST',
            data: {
                student_id: adj.student_id,
                year: '<?php echo $running_year; ?>',
                term: <?php echo $running_term; ?>,
                fee_type: adj.fee_type,
                adjustment_type: adj.adjustment_type,
                amount: adj.amount,
                reason: reason
            },
            dataType: 'json',
            success: function(response) {
                completed++;
                if (response.success) {
                    succeeded++;
                    $('#bulk_success_count').text(succeeded);
                } else {
                    failed++;
                    $('#bulk_failed_count').text(failed);
                    errors.push({
                        student_id: adj.student_id,
                        fee_type: adj.fee_type,
                        message: response.message || 'Unknown error'
                    });
                }
                updateBulkProgress(completed, total);
                
                // Process next after a small delay
                setTimeout(processNext, 50);
            },
            error: function(xhr, status, error) {
                completed++;
                failed++;
                $('#bulk_failed_count').text(failed);
                errors.push({
                    student_id: adj.student_id,
                    fee_type: adj.fee_type,
                    message: 'Network error: ' + error
                });
                updateBulkProgress(completed, total);
                
                // Continue even on error
                setTimeout(processNext, 50);
            }
        });
    }
    
    // Start processing
    processNext();
}

function updateBulkProgress(completed, total) {
    var percent = Math.round((completed / total) * 100);
    $('#bulk_progress_bar_main').css('width', percent + '%').text(percent + '%');
    $('#bulk_progress_text_main').text('Processing: ' + completed + ' of ' + total + ' adjustments...');
}

function getStudentNameFromId(studentId) {
    var row = $('.bulk-input[data-student-id="' + studentId + '"]').closest('tr');
    return row.find('.student-name-display').text() || 'Unknown';
}

function finishBulkAdjustments(total, succeeded, failed, errors) {
    // Hide progress modal
    $('#bulk_progress_modal').css('display', 'none');
    
    // Re-enable controls
    $('.bulk-input').prop('disabled', false);
    $('button').prop('disabled', false);
    $('#bulk_reason_textarea').prop('disabled', false);
    
    var message = '<div style="text-align: left;">';
    message += '<p><strong>Results:</strong></p>';
    message += '<ul style="margin: 10px 0; padding-left: 20px;">';
    message += '<li><span style="color: #10b981; font-weight: bold;">✓ Successful:</span> ' + succeeded + '</li>';
    
    if (failed > 0) {
        message += '<li><span style="color: #ef4444; font-weight: bold;">✗ Failed:</span> ' + failed + '</li>';
    }
    
    message += '</ul>';
    
    // Show error details if any
    if (errors.length > 0 && errors.length <= 5) {
        message += '<p style="margin-top: 10px;"><strong>Errors:</strong></p>';
        message += '<ul style="font-size: 11px; color: #64748b; padding-left: 20px;">';
        errors.forEach(function(err) {
            message += '<li>Student ID ' + err.student_id + ' (' + err.fee_type + '): ' + err.message + '</li>';
        });
        message += '</ul>';
    } else if (errors.length > 5) {
        message += '<p style="margin-top: 10px; font-size: 11px; color: #64748b;">Multiple errors occurred. Check the logs for details.</p>';
    }
    
    message += '</div>';
    
    if (failed === 0) {
        showConfirmModal('Success', message, function() {
            $('#bulkReconciliationModal').modal('hide');
            if (typeof loadStudents === 'function') {
                loadStudents();
            }
        }, 'OK', 'success');
    } else if (succeeded > 0) {
        showConfirmModal('Partial Success', message, function() {
            $('#bulkReconciliationModal').modal('hide');
            if (typeof loadStudents === 'function') {
                loadStudents();
            }
        }, 'OK', 'warning');
    } else {
        showConfirmModal('All Failed', message, null, 'OK', 'danger');
    }
}
</script>
