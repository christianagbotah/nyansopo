<?php
if (!isset($student_id) || !isset($student)) {
    echo '<div class="alert alert-danger">Student information not found</div>';
    return;
}

// Get cumulative balances from daily_fee_wallet table (sum across all years/terms)
$this->db->select('
    SUM(feeding_balance) as feeding_balance,
    SUM(breakfast_balance) as breakfast_balance,
    SUM(classes_balance) as classes_balance,
    SUM(water_balance) as water_balance,
    SUM(transport_balance) as transport_balance,
    SUM(feeding_arrears) as feeding_arrears,
    SUM(breakfast_arrears) as breakfast_arrears,
    SUM(classes_arrears) as classes_arrears,
    SUM(water_arrears) as water_arrears,
    SUM(transport_arrears) as transport_arrears
');
$this->db->from('daily_fee_wallet');
$this->db->where('student_id', $student_id);
$wallet = $this->db->get()->row();

// Build cumulative array
$cumulative = array();
foreach (['feeding', 'breakfast', 'classes', 'water', 'transport'] as $fee_type) {
    $balance_col = $fee_type . '_balance';
    $arrears_col = $fee_type . '_arrears';
    $cumulative[$fee_type] = array(
        'balance' => $wallet ? floatval($wallet->$balance_col) : 0,
        'arrears' => $wallet ? floatval($wallet->$arrears_col) : 0
    );
}
?>

<style>
.adjustment-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    table-layout: auto;
}
.adjustment-table th {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    padding: 8px 6px;
    text-align: center;
    font-weight: 700;
    border: 1px solid #7c3aed;
    font-size: 11px;
    white-space: nowrap;
}
.adjustment-table th.fee-header {
    font-size: 12px;
    border-bottom: 2px solid white;
}
.adjustment-table th.sub-header {
    font-size: 10px;
    font-weight: 600;
}
.adjustment-table td {
    padding: 10px 6px;
    text-align: center;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}
.adjustment-table td.label-cell {
    text-align: left;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    width: 140px;
    font-size: 11px;
}
.adjustment-input-field {
    width: 100%;
    min-width: 85px;
    padding: 7px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    text-align: center;
    font-size: 12px;
    background: white;
    font-weight: 600;
}
.adjustment-input-field:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}
.current-balance {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
    min-width: 70px;
}
.balance-prepaid {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}
.balance-arrears {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}
.balance-zero {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
}
</style>

<!-- Modern Header with Gradient -->
<div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 1.25rem; margin: -20px -20px 0 -20px; border-radius: 12px 12px 0 0; position: relative;">
    <!-- Enlarged Close Button (Danger) -->
    <button type="button" class="close" data-dismiss="modal" style="position: absolute; top: 12px; right: 12px; background: #ef4444; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); opacity: 1; text-shadow: none;" onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1) rotate(90deg)'; this.style.boxShadow='0 6px 16px rgba(239, 68, 68, 0.6)'" onmouseout="this.style.background='#ef4444'; this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)'">&times;</button>
    <div style="display: flex; align-items: center; gap: 12px; color: white;">
        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; backdrop-filter: blur(10px);">
            <i class="fa fa-edit" style="font-size: 24px;"></i>
        </div>
        <div style="flex: 1;">
            <h2 style="margin: 0; font-size: 16px; font-weight: 700; color: white;">Adjust Balance: <?php echo $student->name; ?></h2>
            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 12px;">
                <i class="fa fa-id-card"></i> <?php echo $student->student_code; ?>
                <span style="margin-left: 12px;"><i class="fa fa-info-circle"></i> Adjust prepaid and arrears balances across all fee types</span>
            </p>
        </div>
    </div>
</div>

<div class="modal-body" style="padding: 18px; background: #f8f9fa;">
    <form id="adjustBalanceForm">
        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
        <input type="hidden" name="year" value="<?php echo $running_year; ?>">
        <input type="hidden" name="term" value="<?php echo $running_term; ?>">

        <div style="background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                <i class="fa fa-table" style="color: #8b5cf6; font-size: 14px; margin-right: 8px;"></i>
                <h4 style="margin: 0; font-weight: 700; color: #2d3748; font-size: 13px;">Balance Adjustment Table</h4>
            </div>

            <div style="overflow-x: auto;">
                <table class="adjustment-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width: 140px;">Balance Type</th>
                            <?php foreach ($active_modules as $module): ?>
                                <th colspan="2" class="fee-header" style="min-width: 180px;"><?php echo strtoupper($module); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($active_modules as $module): ?>
                                <th class="sub-header" style="min-width: 90px;">Prepaid</th>
                                <th class="sub-header" style="min-width: 90px;">Arrears</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Current Balance Row -->
                        <tr>
                            <td class="label-cell"><i class="fa fa-info-circle"></i> Current Balance</td>
                            <?php foreach ($active_modules as $module): ?>
                                <?php
                                $prepaid = $cumulative[$module]['balance'];
                                $arrears = $cumulative[$module]['arrears'];
                                ?>
                                <td>
                                    <?php if ($prepaid > 0): ?>
                                        <span class="current-balance balance-prepaid">GH₵<?php echo number_format($prepaid, 2); ?></span>
                                    <?php else: ?>
                                        <span class="current-balance balance-zero">GH₵0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($arrears > 0): ?>
                                        <span class="current-balance balance-arrears">GH₵<?php echo number_format($arrears, 2); ?></span>
                                    <?php else: ?>
                                        <span class="current-balance balance-zero">GH₵0.00</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Adjustment Amount Row -->
                        <tr>
                            <td class="label-cell"><i class="fa fa-money-bill"></i> Adjustment Amount</td>
                            <?php foreach ($active_modules as $module): ?>
                                <td>
                                    <input type="number" 
                                           class="adjustment-input-field adjustment-amount" 
                                           name="amount_<?php echo $module; ?>_prepaid" 
                                           step="0.01" 
                                           min="0" 
                                           placeholder="0.00"
                                           data-fee-type="<?php echo $module; ?>"
                                           data-adjustment-type="prepaid">
                                </td>
                                <td>
                                    <input type="number" 
                                           class="adjustment-input-field adjustment-amount" 
                                           name="amount_<?php echo $module; ?>_arrears" 
                                           step="0.01" 
                                           min="0" 
                                           placeholder="0.00"
                                           data-fee-type="<?php echo $module; ?>"
                                           data-adjustment-type="arrears">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-top: 12px;">
            <label style="display: block; margin-bottom: 7px; font-weight: 700; font-size: 12px; color: #2d3748;">
                <i class="fa fa-comment"></i> Reason for Adjustment
            </label>
            <textarea class="form-control" name="reason" rows="3" required placeholder="Provide a detailed explanation for why this adjustment is being made..." style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px; font-size: 12px;"></textarea>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
            <button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 9px 20px; font-size: 13px; font-weight: 600;">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-success" onclick="submitAdjustments()" style="padding: 9px 20px; font-size: 13px; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                <i class="fa fa-check"></i> Apply Adjustments
            </button>
        </div>
    </form>
</div>

<script>
function submitAdjustments() {
    console.log('submitAdjustments called'); // Debug line
    
    var adjustments = [];
    var hasAnyAdjustment = false;
    
    // Collect all filled adjustments (including 0)
    $('.adjustment-amount').each(function() {
        var value = $(this).val();
        // Check if the field has been touched (has a value, even if 0)
        if (value !== '' && value !== null) {
            var amount = parseFloat(value) || 0;
            hasAnyAdjustment = true;
            adjustments.push({
                fee_type: $(this).data('fee-type'),
                adjustment_type: $(this).data('adjustment-type'),
                amount: amount
            });
        }
    });
    
    console.log('Adjustments collected:', adjustments); // Debug line
    console.log('Has any adjustment:', hasAnyAdjustment); // Debug line
    
    if (!hasAnyAdjustment) {
        showConfirmModal(
            'Validation Error',
            'Please enter at least one adjustment amount (including 0 to clear a balance)',
            null,
            'OK',
            'warning'
        );
        return;
    }
    
    var reason = $('textarea[name="reason"]').val();
    if (!reason.trim()) {
        showConfirmModal(
            'Validation Error',
            'Please provide a reason for the adjustment',
            null,
            'OK',
            'warning'
        );
        return;
    }
    
    // Show confirmation before proceeding
    var adjustmentCount = adjustments.length;
    var confirmMessage = 'You are about to apply <strong>' + adjustmentCount + ' adjustment(s)</strong> for this student.<br><br>' +
                        'This action will be logged in the audit trail. Do you want to proceed?';
    
    console.log('Showing confirmation modal'); // Debug
    
    showConfirmModal(
        'Confirm Adjustments',
        confirmMessage,
        function() {
            console.log('Confirmation accepted, calling processAdjustments'); // Debug
            processAdjustments(adjustments, reason);
        },
        'Yes, Apply Adjustments',
        'warning'
    );
}

function processAdjustments(adjustments, reason) {
    console.log('processAdjustments called with:', adjustments.length, 'adjustments'); // Debug
    
    // Disable button during submission
    var btn = $('button[onclick="submitAdjustments()"]');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    
    // Submit each adjustment separately
    var completed = 0;
    var failed = 0;
    var total = adjustments.length;
    
    adjustments.forEach(function(adj) {
        console.log('Sending AJAX for:', adj); // Debug
        
        $.ajax({
            url: '<?php echo base_url(); ?>admin/adjust_student_balance',
            type: 'POST',
            data: {
                student_id: <?php echo $student_id; ?>,
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
                if (!response.success) {
                    failed++;
                }
                if (completed === total) {
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply Adjustments');
                    
                    if (failed === 0) {
                        showConfirmModal(
                            'Success',
                            '<strong>' + total + ' adjustment(s)</strong> applied successfully',
                            function() {
                                $('.modal').modal('hide');
                                if (typeof loadStudents === 'function') {
                                    loadStudents();
                                }
                            },
                            'OK',
                            'success'
                        );
                    } else {
                        showConfirmModal(
                            'Partial Success',
                            '<strong>' + (total - failed) + ' of ' + total + '</strong> adjustments were applied successfully',
                            function() {
                                $('.modal').modal('hide');
                                if (typeof loadStudents === 'function') {
                                    loadStudents();
                                }
                            },
                            'OK',
                            'warning'
                        );
                    }
                }
            },
            error: function() {
                completed++;
                failed++;
                if (completed === total) {
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply Adjustments');
                    showConfirmModal(
                        'Error',
                        'Some adjustments failed. Please check and try again.',
                        function() {
                            $('.modal').modal('hide');
                            if (typeof loadStudents === 'function') {
                                loadStudents();
                            }
                        },
                        'OK',
                        'danger'
                    );
                }
            }
        });
    });
}
</script>
