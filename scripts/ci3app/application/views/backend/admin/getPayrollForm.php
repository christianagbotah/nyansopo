<?php
    foreach($staffPayrollData as $row):
?>
<style>
/* Enhanced Month Input Clickability */
input[type="month"] {
    cursor: pointer !important;
    transition: all 0.2s ease;
}

input[type="month"]:hover {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

input[type="month"]:focus {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}

/* Make the calendar icon more visible */
input[type="month"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    font-size: 18px;
    padding: 4px;
    margin-left: 8px;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

input[type="month"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}
</style>
<div class="bg-white rounded-lg shadow-lg p-6">
    
    <!-- Customize Form Button -->
    <div style="text-align: right; margin-bottom: 20px;">
        <button type="button" onclick="openPayrollFormCustomizer()" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa fa-sliders"></i> Customize Form Fields
        </button>
    </div>
                        
    <?= form_open(site_url('admin/payroll/create/'.$row->pay_id), ['class' => 'space-y-6', 'id' => 'payrollForm']);?>

        <!-- Section 1: Basic Information -->
        <div class="modern-card section-basic-info">
            <div class="modern-card-header gradient-gray">
                <i class="fas fa-info-circle mr-2"></i>Basic Information
            </div>
            <div class="modern-card-body">
                <div class="payroll-grid payroll-grid-2">
                    <div class="payroll-form-group">
                        <label for="payrollMonth" class="payroll-form-label required">Month & Year</label>
                        <input type="month" id="payrollMonth" name="payrollMonth" value="<?= htmlspecialchars($payrollMonthYear, ENT_QUOTES, 'UTF-8');?>" class="payroll-form-input" style="cursor: pointer;">
                    </div>
                    <div class="payroll-form-group">
                        <label class="payroll-form-label">Selected Period</label>
                        <div class="payroll-form-input bg-gray-50" style="padding: 0.75rem; border: 1px solid #e2e8f0;">
                            <span id="currentMonth" class="font-medium">
                                <?php 
                                // Convert YYYY-MM format to readable month name
                                if (isset($payrollMonthYear) && $payrollMonthYear) {
                                    $dateObj = DateTime::createFromFormat('Y-m', $payrollMonthYear);
                                    echo $dateObj ? $dateObj->format('F Y') : 'Not Set';
                                } else {
                                    echo date('F Y');
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Section -->
        <div class="modern-card section-allowances">
            <div class="modern-card-header gradient-green">
                <i class="fas fa-money-bill-wave mr-2"></i>Earnings - Basic Salary & Allowances
            </div>
            <div class="modern-card-body">
                <div class="payroll-grid payroll-grid-2">
                    <div class="payroll-form-group payroll-field-wrapper" data-field="basicSalary">
                        <label for="basicSalary" class="payroll-form-label required">Basic Salary (GH₵)</label>
                        <input type="number" id="basicSalary" name="basicSalary" step="0.01" class="payroll-form-input" placeholder="2500.00" value="<?= htmlspecialchars($row->basic_salary, ENT_QUOTES, 'UTF-8')?>" required>
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="marketPremium" <?= in_array('marketPremium', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="marketPremium" class="payroll-form-label">Market Premium (GH₵)</label>
                        <input type="number" id="marketPremium" name="marketPremium" step="0.01" class="payroll-form-input" placeholder="300.00" value="<?= htmlspecialchars($row->market_premium_allowance, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="teachingAllowance" <?= in_array('teachingAllowance', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="teachingAllowance" class="payroll-form-label">Teaching Allowance (GH₵)</label>
                        <input type="number" id="teachingAllowance" name="teachingAllowance" step="0.01" class="payroll-form-input" placeholder="200.00" value="<?= htmlspecialchars($row->teaching_allowance, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="responsibilityAllowance" <?= in_array('responsibilityAllowance', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="responsibilityAllowance" class="payroll-form-label">Responsibility Allowance (GH₵)</label>
                        <input type="number" id="responsibilityAllowance" name="responsibilityAllowance" step="0.01" class="payroll-form-input" placeholder="150.00" value="<?= htmlspecialchars($row->responsibility_allowance, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="extraClasses" <?= in_array('extraClasses', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="extraClasses" class="payroll-form-label">Extra Classes (GH₵)</label>
                        <input type="number" id="extraClasses" name="extraClasses" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->extra_class_allowance, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="ruralAllowance" <?= in_array('ruralAllowance', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="ruralAllowance" class="payroll-form-label">Rural Allowance (GH₵)</label>
                        <input type="number" id="ruralAllowance" name="ruralAllowance" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->rural_allowance, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="otherAllowances" <?= in_array('otherAllowances', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="otherAllowances" class="payroll-form-label">Other Allowance (GH₵)</label>
                        <input type="number" id="otherAllowances" name="otherAllowances" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->other_allowances, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Employer Contributions (Blue Header) - Display Only -->
        <div class="modern-card section-employer-contributions">
            <div class="modern-card-header gradient-blue">
                <i class="fas fa-building mr-2"></i>Employer Contributions (Not Deducted from Employee)
            </div>
            <div class="modern-card-body">
                <div class="payroll-grid payroll-grid-2">
                    <div class="payroll-form-group">
                        <label for="ssnit" class="payroll-form-label">
                            SSNIT Tier 1 (<span id="ssnitTier1RateLabel"><?php 
                                $rates = json_decode($statutory_rates_json ?? '{}', true);
                                echo rtrim(rtrim(number_format($rates['ssnit_tier1_employer'] ?? 13, 1), '0'), '.'); 
                            ?>%</span>) - Employer Contribution
                            <span class="payroll-tooltip">
                                <span class="payroll-tooltip-icon">?</span>
                                <span class="payroll-tooltip-text">This is an employer contribution calculated on basic salary and is NOT deducted from the employee's salary</span>
                            </span>
                        </label>
                        <input type="number" id="ssnit" name="ssnit" step="0.01" class="payroll-form-input bg-blue-50" placeholder="0.00" value="<?= htmlspecialchars($row->ssnit, ENT_QUOTES, 'UTF-8')?>" readonly>
                    </div>
                    <div class="payroll-form-group">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Note:</strong> Tier 1 contributions are paid by the employer and do not affect the employee's net salary.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Employee Deductions (Red Header) -->
        <div class="modern-card section-deductions">
            <div class="modern-card-header gradient-red">
                <i class="fas fa-minus-circle mr-2"></i>Employee Deductions (Deducted from Gross Salary)
            </div>
            <div class="modern-card-body">
                <div class="payroll-grid payroll-grid-2">
                    <!-- Tier 2 with Provider -->
                    <div class="payroll-form-group payroll-field-wrapper" data-field="petra" <?= in_array('petra', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="petra" class="payroll-form-label" id="tier2Label">
                            SSNIT Tier 2 (<span id="ssnitTier2RateLabel"><?php 
                                echo rtrim(rtrim(number_format($rates['ssnit_tier2'] ?? 5.5, 1), '0'), '.'); 
                            ?>%</span>)
                            <span class="payroll-tooltip">
                                <span class="payroll-tooltip-icon">?</span>
                                <span class="payroll-tooltip-text">This is an employee contribution calculated on basic salary, deducted from gross salary and sent to the selected Tier 2 provider</span>
                            </span>
                        </label>
                        <input type="number" id="petra" name="petra" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars((isset($row->tier2_contribution) ? $row->tier2_contribution : (isset($row->petra) ? $row->petra : '')), ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group">
                        <label for="tier2ProviderDisplay" class="payroll-form-label">Tier 2 Provider</label>
                        <div class="payroll-form-input bg-gray-50" style="padding: 0.75rem; border: 1px solid #e2e8f0;">
                            <span class="font-medium text-gray-900">
                                <?php echo isset($tier2_provider) && $tier2_provider ? htmlspecialchars($tier2_provider->provider_name) : 'Not Set'; ?>
                            </span>
                        </div>
                        <input type="hidden" name="tier2_provider_id" value="<?php echo isset($tier2_provider) && $tier2_provider ? htmlspecialchars($tier2_provider->provider_id, ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                    
                    <!-- Tax -->
                    <div class="payroll-form-group payroll-field-wrapper" data-field="incomeTax" <?= in_array('incomeTax', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="incomeTax" class="payroll-form-label">Income Tax - PAYE (GH₵)</label>
                        <div class="flex gap-2">
                            <input type="number" id="incomeTax" name="incomeTax" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->income_tax, ENT_QUOTES, 'UTF-8')?>">
                            <button type="button" id="autoCalculatePayeBtn" class="payroll-button payroll-button-primary whitespace-nowrap">
                                <i class="fas fa-calculator"></i> Auto-Calculate
                            </button>
                        </div>
                    </div>
                    
                    <!-- Statutory Deductions with Toggle Switches -->
                    <div class="payroll-form-group" style="display: none;">
                        <div class="flex items-center justify-between mb-2">
                            <label for="getfund" class="payroll-form-label">GETFund (<span id="getfundRateLabel"><?php 
                                echo rtrim(rtrim(number_format($rates['getfund'] ?? 2.5, 1), '0'), '.'); 
                            ?>%</span>) (GH₵)</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="getfundToggle" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-2 text-sm font-medium text-gray-700">Apply</span>
                            </label>
                        </div>
                        <input type="number" id="getfund" name="get_fund" step="0.01" class="payroll-form-input" placeholder="0.00" value="0.00" disabled>
                    </div>
                    <div class="payroll-form-group" style="display: none;">
                        <div class="flex items-center justify-between mb-2">
                            <label for="nhil" class="payroll-form-label">NHIL (<span id="nhilRateLabel"><?php 
                                echo rtrim(rtrim(number_format($rates['nhil'] ?? 2.5, 1), '0'), '.'); 
                            ?>%</span>) (GH₵)</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="nhilToggle" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-2 text-sm font-medium text-gray-700">Apply</span>
                            </label>
                        </div>
                        <input type="number" id="nhil" name="nhil" step="0.01" class="payroll-form-input" placeholder="0.00" value="0.00" disabled>
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="salaryAdvance" <?= in_array('salaryAdvance', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="salaryAdvance" class="payroll-form-label">Salary Advance (GH₵)</label>
                        <input type="number" id="salaryAdvance" name="salaryAdvance" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->salary_advance, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="loans" <?= in_array('loans', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="loans" class="payroll-form-label">Loan Deductions (GH₵)</label>
                        <input type="number" id="loans" name="loans" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->loan, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="welfare" <?= in_array('welfare', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="welfare" class="payroll-form-label">Welfare Deductions (GH₵)</label>
                        <input type="number" id="welfare" name="welfare" step="0.01" class="payroll-form-input" placeholder="0.00">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="gnat" <?= in_array('gnat', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="gnat" class="payroll-form-label">GNAT Dues (GH₵)</label>
                        <input type="number" id="gnat" name="gnat" step="0.01" class="payroll-form-input" placeholder="10.00" value="<?= htmlspecialchars($row->gnat_dues, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group payroll-field-wrapper" data-field="otherDeductions" <?= in_array('otherDeductions', $hidden_fields ?? []) ? 'style="display: none;"' : '' ?>>
                        <label for="otherDeductions" class="payroll-form-label">Other Deductions (GH₵)</label>
                        <input type="number" id="otherDeductions" name="otherDeductions" step="0.01" class="payroll-form-input" placeholder="0.00" value="<?= htmlspecialchars($row->other_deductions, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Section -->
        <div class="modern-card section-attendance">
            <div class="modern-card-header gradient-teal">
                <i class="fas fa-calendar-check mr-2"></i>Attendance Information
            </div>
            <div class="modern-card-body">
                <div class="payroll-grid payroll-grid-3">
                    <div class="payroll-form-group">
                        <label for="workingDays" class="payroll-form-label required">Working Days</label>
                        <input type="number" id="workingDays" name="workingDays" min="1" max="31" class="payroll-form-input" value="<?= htmlspecialchars($row->working_days, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group">
                        <label for="daysPresent" class="payroll-form-label required">Days Present</label>
                        <input type="number" id="daysPresent" name="daysPresent" min="0" max="31" class="payroll-form-input" value="<?= htmlspecialchars($row->days_present, ENT_QUOTES, 'UTF-8')?>">
                    </div>
                    <div class="payroll-form-group">
                        <label for="daysAbsent" class="payroll-form-label">Days Absent</label>
                        <input type="number" id="daysAbsent" name="daysAbsent" min="0" max="31" class="payroll-form-input" value="<?= htmlspecialchars($row->days_absent, ENT_QUOTES, 'UTF-8')?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payroll Summary -->
        <div class="modern-card section-summary">
            <div class="modern-card-header gradient-blue">
                <i class="fas fa-calculator mr-2"></i>Payroll Summary
            </div>
            <div class="modern-card-body">
                <div class="payroll-grid payroll-grid-2">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Basic Salary:</span>
                            <span id="summaryBasic" class="text-sm font-medium">GH₵ <?= number_format($row->basic_salary, '2', '.', ',')?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Allowances:</span>
                            <span id="summaryAllowances" class="text-sm font-medium text-green-600">GH₵ <?= number_format($row->total_allowances, '2', '.', ',')?></span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-sm font-medium text-gray-900">Gross Salary:</span>
                            <span id="grossSalary" class="text-sm font-bold text-blue-600">GH₵ <?= number_format($row->gross_salary, '2', '.', ',')?></span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Statutory Deductions:</span>
                            <span id="summaryStatutory" class="text-sm font-medium text-red-600">GH₵ 0.00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Other Deductions:</span>
                            <span id="summaryOther" class="text-sm font-medium text-red-600">GH₵ <?= number_format($row->other_deductions, '2', '.', ',')?></span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-sm font-medium text-gray-900">Net Salary:</span>
                            <span id="netSalary" class="text-lg font-bold text-green-600">GH₵ <?= number_format($row->net_salary, '2', '.', ',')?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- hidden inputs -->
        <input type="hidden" name="totalAllowancesForDb" id="totalAllowancesForDb" value="<?= htmlspecialchars($row->total_allowances, ENT_QUOTES, 'UTF-8')?>">
        <input type="hidden" name="totalDeductionsForDb" id="totalDeductionsForDb" value="<?= htmlspecialchars($row->total_deductions, ENT_QUOTES, 'UTF-8')?>">
        <input type="hidden" name="grossSalaryForDb" id="grossSalaryForDb" value="<?= htmlspecialchars($row->gross_salary, ENT_QUOTES, 'UTF-8')?>">
        <input type="hidden" name="netSalaryForDb" id="netSalaryForDb" value="<?= htmlspecialchars($row->net_salary, ENT_QUOTES, 'UTF-8')?>">

        <!-- Action Buttons -->
        <div class="payroll-button-group flex justify-end gap-4 bg-white pt-6 sticky bottom-0 z-50">
            <button type="button" id="calculateBtn" class="payroll-button payroll-button-primary flex-1" style="display: none;">
                <i class="fas fa-calculator"></i> Calculate Payroll
            </button>
            <button type="button" id="autoDeductBtn" class="payroll-button payroll-button-danger flex-1" style="display: none;">
                <i class="fas fa-bolt"></i> Auto Calculate Deductions
            </button>
            <button type="submit" class="payroll-button payroll-button-success">
                <i class="fas fa-check-circle"></i> Process Payment
            </button>
            <button onclick="printPayslip('<?php echo $row->employee_code; ?>', '<?php echo $row->month; ?>', '<?php echo $row->year; ?>', '<?php echo $row->employment_category; ?>')" type="button" class="payroll-button payroll-button-secondary">
                <i class="fas fa-print"></i> Print Payslip
            </button>
        </div>
    </form>
</div>

<?php
endforeach;
?>

<script>
        $(function() {
            $.ajaxSetup({
                //cache: false,
                data: {
                    <?php echo json_encode($this->security->get_csrf_token_name()); ?>: <?php echo json_encode($this->security->get_csrf_hash()); ?>
                } 
             }); 

        calculatePayroll();

        window.staffIdArray = [];
        // Set current month
        const selectedDate = $('#payrollMonth').val();
        const splitDate = selectedDate.split("-");
        const newDate = new Date(splitDate[0], splitDate[1] - 1);

        const currentMonthStr = newDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        document.getElementById('currentMonth').textContent = currentMonthStr;
        document.getElementById('payrollMonth').value = newDate.getFullYear() + '-' + String(newDate.getMonth() + 1).padStart(2, '0');

        $('#payrollMonth').change(function(ev) {
            const selectedDate = $(this).val();
            
            // BUGFIX: Validate that we have a valid date value before processing
            if (!selectedDate || selectedDate.trim() === '') {
                console.log('Payroll month change: no valid date selected');
                return; // Exit early to prevent errors
            }
            
            // Optional: Validate date format (YYYY-MM)
            if (!/^\d{4}-\d{2}$/.test(selectedDate)) {
                console.log('Payroll month change: invalid date format - ' + selectedDate);
                return;
            }
            
            const splitDate = selectedDate.split("-");


            const newDate = new Date(splitDate[0], splitDate[1] - 1);
            const selectedMonthStr = newDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
            document.getElementById('currentMonth').textContent = selectedMonthStr;

            /*invoke the method*/
            // BUGFIX: Use native JavaScript to avoid jQuery .val() errors during DOM manipulation
            let staffIds = [];
            const staffIdElement = document.getElementById('staffId');
            
            if (!staffIdElement) {
                console.log('Staff select element not found - skipping reload');
                return;
            }
            
            try {
                const selectedOptions = staffIdElement.selectedOptions;
                if (selectedOptions && selectedOptions.length > 0) {
                    staffIds = Array.from(selectedOptions).map(opt => opt.value);
                } else {
                    staffIds = [];
                }
            } catch (e) {
                console.log('Error reading staffId value:', e);
                staffIds = [];
            }

            // Don't reload if no staff is selected - just update the display
            if(!staffIds || staffIds.length === 0) {
                console.log('No staff selected - month changed to: ' + selectedDate);
                return; // Exit early, preserving the selected month
            }

            var lastStaffCode = '';

            if(staffIds.length > staffIdArray.length) {
                // Find newly added staff codes using vanilla JS
                const newStaffCodes = staffIds.filter(id => !staffIdArray.includes(id));
                lastStaffCode = newStaffCodes[0] || '';

            } else {
                let arrayLength = staffIds.length;
                lastStaffCode = staffIds[arrayLength - 1] || '';
            }

            staffIdArray = staffIds;

            if(lastStaffCode == undefined || lastStaffCode == '') lastStaffCode = '';
            const payrollMonth = $(this).val();

            // Show preloader when month changes
            if(lastStaffCode !== '') {
                $('#modal_alert').modal('hide'); // Close any existing modal
                showAjaxModal_alert('Loading payroll data for selected month...', 'Loading', false, false);
            }

            // Only load if we have a valid staff code
            if(lastStaffCode !== '') {
                loadStaffExistingPayroll(lastStaffCode, payrollMonth);
            }

        })

        // Make the entire month input field clickable (not just the calendar icon)
        $('#payrollMonth').on('click', function(e) {
            // Trigger the native date picker
            this.showPicker && this.showPicker();
        });

        // Search teacher functionality
        const teacherSearchForm = document.getElementById('teacherSearchForm');
        if (teacherSearchForm) {
            teacherSearchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const teacherDetails = document.getElementById('teacherDetails');
                if (teacherDetails) {
                    teacherDetails.classList.remove('hidden');
                }
                // Auto-populate some sample data
                const basicSalaryInput = document.getElementById('basicSalary');
                const marketPremiumInput = document.getElementById('marketPremium');
                const teachingAllowanceInput = document.getElementById('teachingAllowance');
                const responsibilityAllowanceInput = document.getElementById('responsibilityAllowance');
                
                if (basicSalaryInput) basicSalaryInput.value = '2500.00';
                if (marketPremiumInput) marketPremiumInput.value = '300.00';
                if (teachingAllowanceInput) teachingAllowanceInput.value = '200.00';
                if (responsibilityAllowanceInput) responsibilityAllowanceInput.value = '150.00';
                calculatePayroll();
            });
        }

        // Auto calculate deductions based on gross salary
        function autoCalculateDeductions() {
            const basicSalary = parseFloat(document.getElementById('basicSalary').value) || 0;
            const marketPremium = parseFloat(document.getElementById('marketPremium').value) || 0;
            const teachingAllowance = parseFloat(document.getElementById('teachingAllowance').value) || 0;
            const responsibilityAllowance = parseFloat(document.getElementById('responsibilityAllowance').value) || 0;
            const ruralAllowance = parseFloat(document.getElementById('ruralAllowance').value) || 0;
            const extraClasses = parseFloat(document.getElementById('extraClasses').value) || 0;
            const otherAllowances = parseFloat(document.getElementById('otherAllowances').value) || 0;
            
            
            const grossBeforeDeductions = basicSalary + marketPremium + teachingAllowance + responsibilityAllowance + ruralAllowance + extraClasses + otherAllowances;
            
            // ============================================
            // DYNAMIC STATUTORY RATES (From Database)
            // ============================================
            const STATUTORY_RATES = <?php echo isset($statutory_rates_json) ? $statutory_rates_json : '{}'; ?>;
            
            // Helper function to get rate (no fallback - use database values only)
            function getRate(key) {
                if (STATUTORY_RATES[key] === undefined) {
                    console.error('Statutory rate not found for key: ' + key);
                    return 0;
                }
                return STATUTORY_RATES[key] / 100;
            }
            
            const ssnitRate = getRate('ssnit_tier1_employer');
            const tier2Rate = getRate('ssnit_tier2');
            const getfundRate = getRate('getfund');
            const nhilRate = getRate('nhil');
            // ============================================
            
            document.getElementById('ssnit').value = (basicSalary * ssnitRate).toFixed(2);
            document.getElementById('petra').value = (basicSalary * tier2Rate).toFixed(2);
            
            // Only calculate GETFund if toggle is checked, otherwise ensure it's 0
            if (document.getElementById('getfundToggle').checked) {
                document.getElementById('getfund').value = (grossBeforeDeductions * getfundRate).toFixed(2);
            } else {
                document.getElementById('getfund').value = '0.00';
            }
            
            // Only calculate NHIL if toggle is checked, otherwise ensure it's 0
            if (document.getElementById('nhilToggle').checked) {
                document.getElementById('nhil').value = (grossBeforeDeductions * nhilRate).toFixed(2);
            } else {
                document.getElementById('nhil').value = '0.00';
            }

            
            // Calculate PAYE (simplified progressive tax)
            let paye = 0;
            /*const annualGross = grossBeforeDeductions * 12;
            if (annualGross > 4380) { // Annual tax threshold in Ghana
                const taxableAmount = annualGross - 4380;
                if (taxableAmount <= 110) {
                    paye = taxableAmount * 0.05;
                } else if (taxableAmount <= 130) {
                    paye = 110 * 0.05 + (taxableAmount - 110) * 0.10;
                } else if (taxableAmount <= 3000) {
                    paye = 110 * 0.05 + 20 * 0.10 + (taxableAmount - 130) * 0.175;
                } else {
                    paye = 110 * 0.05 + 20 * 0.10 + 2870 * 0.175 + (taxableAmount - 3000) * 0.25;
                }
                paye = paye / 12; // Monthly PAYE
            }*/
            document.getElementById('incomeTax').value = paye.toFixed(2);
        }

        // Calculate attendance-based salary
        function calculateAttendanceAdjustment() {
            const workingDays = parseInt(document.getElementById('workingDays').value) || 22;
            const daysPresent = parseInt(document.getElementById('daysPresent').value) || 22;
            const daysAbsent = workingDays - daysPresent;
            
            document.getElementById('daysAbsent').value = daysAbsent;
            
            return daysPresent / workingDays; // Attendance ratio
        }

        // Calculate payroll totals
        function calculatePayroll() {
            const attendanceRatio = calculateAttendanceAdjustment();
            
            // Get all allowances
            const basicSalary = parseFloat(document.getElementById('basicSalary').value) || 0;
            const marketPremium = parseFloat(document.getElementById('marketPremium').value) || 0;
            const teachingAllowance = parseFloat(document.getElementById('teachingAllowance').value) || 0;
            const responsibilityAllowance = parseFloat(document.getElementById('responsibilityAllowance').value) || 0;
            const ruralAllowance = parseFloat(document.getElementById('ruralAllowance').value) || 0;
            const extraClasses = parseFloat(document.getElementById('extraClasses').value) || 0;
            const otherAllowances = parseFloat(document.getElementById('otherAllowances').value) || 0;
            
            
            // Apply attendance ratio to basic salary only
            const adjustedBasicSalary = basicSalary * attendanceRatio;
            const totalAllowances = marketPremium + teachingAllowance + responsibilityAllowance + ruralAllowance + extraClasses + otherAllowances;
            const grossSalary = adjustedBasicSalary + totalAllowances;
            
            // Get all deductions
            const incomeTax = parseFloat(document.getElementById('incomeTax').value) || 0;
            const ssnit = parseFloat(document.getElementById('ssnit').value) || 0;
            
            // Only include GETFund if toggle is checked
            const getfund = document.getElementById('getfundToggle').checked ? 
                (parseFloat(document.getElementById('getfund').value) || 0) : 0;
            
            // Only include NHIL if toggle is checked
            const nhil = document.getElementById('nhilToggle').checked ? 
                (parseFloat(document.getElementById('nhil').value) || 0) : 0;
            
            const gnat = parseFloat(document.getElementById('gnat').value) || 0;
            const loans = parseFloat(document.getElementById('loans').value) || 0;
            const welfare = parseFloat(document.getElementById('welfare').value) || 0; 
            const advance = parseFloat(document.getElementById('salaryAdvance').value) || 0;
            const petra = parseFloat(document.getElementById('petra').value) || 0;
            const otherDeductions = parseFloat(document.getElementById('otherDeductions').value) || 0;
            
            const statutoryDeductions = incomeTax + petra + getfund + nhil;
            const otherDeds = gnat + loans + welfare + advance + otherDeductions;
            const totalDeductions = statutoryDeductions + otherDeds;
            const netSalary = grossSalary - totalDeductions;
            
            // Update summary
            document.getElementById('summaryBasic').textContent = 'GH₵ ' + adjustedBasicSalary.toFixed(2);
            document.getElementById('summaryAllowances').textContent = 'GH₵ ' + totalAllowances.toFixed(2);
            document.getElementById('grossSalary').textContent = 'GH₵ ' + grossSalary.toFixed(2);
            document.getElementById('summaryStatutory').textContent = 'GH₵ ' + statutoryDeductions.toFixed(2);
            document.getElementById('summaryOther').textContent = 'GH₵ ' + otherDeds.toFixed(2);
            document.getElementById('netSalary').textContent = 'GH₵ ' + netSalary.toFixed(2);

            /*for hidden inputs*/
            document.getElementById('totalAllowancesForDb').value = totalAllowances.toFixed(2);
            document.getElementById('grossSalaryForDb').value = grossSalary.toFixed(2);
            document.getElementById('netSalaryForDb').value = netSalary.toFixed(2);
            
            // Update quick stats
            document.getElementById('quickNetSalary').textContent = 'GH₵ ' + netSalary.toFixed(2);
            document.getElementById('quickGrossSalary').textContent = 'GH₵ ' + grossSalary.toFixed(2);
            document.getElementById('quickDeductions').textContent = 'GH₵ ' + totalDeductions.toFixed(2);

            /*for hidden inputs*/
            document.getElementById('totalDeductionsForDb').value = totalDeductions.toFixed(2);
        }

        // Event listeners
        const calculateBtn = document.getElementById('calculateBtn');
        if (calculateBtn) {
            calculateBtn.addEventListener('click', calculatePayroll);
        }
        
        const autoDeductBtn = document.getElementById('autoDeductBtn');
        if (autoDeductBtn) {
            autoDeductBtn.addEventListener('click', function() {
                autoCalculateDeductions();
                calculatePayroll();
            });
        }
        
        // GETFund and NHIL Toggle Switches - Default OFF, enable fields when toggled ON
        const getfundToggle = document.getElementById('getfundToggle');
        const getfundInput = document.getElementById('getfund');
        const nhilToggle = document.getElementById('nhilToggle');
        const nhilInput = document.getElementById('nhil');

        // Initialize toggle states based on existing values
        if (getfundInput && parseFloat(getfundInput.value) > 0) {
            if (getfundToggle) getfundToggle.checked = true;
            getfundInput.disabled = false;
        }
        if (nhilInput && parseFloat(nhilInput.value) > 0) {
            if (nhilToggle) nhilToggle.checked = true;
            nhilInput.disabled = false;
        }

        // GETFund toggle handler
        if (getfundToggle && getfundInput) {
            getfundToggle.addEventListener('change', function() {
                if (this.checked) {
                    getfundInput.disabled = false;
                    getfundInput.focus();
                } else {
                    getfundInput.disabled = true;
                    getfundInput.value = '0.00';
                    calculatePayroll(); // Recalculate when disabled
                }
            });
        }

        // NHIL toggle handler
        if (nhilToggle && nhilInput) {
            nhilToggle.addEventListener('change', function() {
                if (this.checked) {
                    nhilInput.disabled = false;
                    nhilInput.focus();
                } else {
                    nhilInput.disabled = true;
                    nhilInput.value = '0.00';
                    calculatePayroll(); // Recalculate when disabled
                }
            });
        }

        // PAYE Auto-Calculate Button Handler
        const autoCalculatePayeBtn = document.getElementById('autoCalculatePayeBtn');
        if (autoCalculatePayeBtn) {
            autoCalculatePayeBtn.addEventListener('click', function() {
            // Get gross salary components
            const basicSalary = parseFloat(document.getElementById('basicSalary').value) || 0;
            const marketPremium = parseFloat(document.getElementById('marketPremium').value) || 0;
            const teachingAllowance = parseFloat(document.getElementById('teachingAllowance').value) || 0;
            const responsibilityAllowance = parseFloat(document.getElementById('responsibilityAllowance').value) || 0;
            const ruralAllowance = parseFloat(document.getElementById('ruralAllowance').value) || 0;
            const extraClasses = parseFloat(document.getElementById('extraClasses').value) || 0;
            const otherAllowances = parseFloat(document.getElementById('otherAllowances').value) || 0;
            
            // Calculate gross salary
            const monthlyGross = basicSalary + marketPremium + teachingAllowance + responsibilityAllowance + ruralAllowance + extraClasses + otherAllowances;
            
            // Get SSNIT deductions for PAYE relief calculation
            // Read the value from the Tier 2 input field (petra field)
            const tier2 = parseFloat(document.getElementById('petra').value) || 0;
            const monthlySsnit = tier2;
            
            // Validate inputs
            if (monthlyGross <= 0) {
                showAjaxModal_alert('Please enter salary information first', 'Warning', false, true);
                return;
            }
            
            // Show loading state
            const button = this;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Calculating...';
            button.disabled = true;
            
            // Call AJAX endpoint
            $.ajax({
                url: <?php echo json_encode(site_url('admin/payroll_calculate_paye')); ?>,
                type: 'POST',
                data: {
                    monthly_gross: monthlyGross,
                    monthly_ssnit: monthlySsnit
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Update the Income Tax field
                        document.getElementById('incomeTax').value = response.monthly_paye.toFixed(2);
                        // Recalculate payroll totals
                        calculatePayroll();
                    } else {
                        showAjaxModal_alert('Error calculating PAYE: ' + response.message, 'Error', false, true);
                    }
                },
                error: function(xhr, status, error) {
                    showAjaxModal_alert('Error calculating PAYE: ' + error, 'Error', false, true);
                },
                complete: function() {
                    // Restore button state
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
            });
        }

        // Auto-calculate on input changes
        const inputs = document.querySelectorAll('input[type="number"]');
        inputs.forEach(input => {
            input.addEventListener('input', calculatePayroll);
        });

        // Working days change listener
        const workingDaysInput = document.getElementById('workingDays');
        if (workingDaysInput) {
            workingDaysInput.addEventListener('input', function() {
                const workingDays = parseInt(this.value) || 22;
                const daysPresentInput = document.getElementById('daysPresent');
                if (daysPresentInput) {
                    const daysPresent = parseInt(daysPresentInput.value) || 22;
                    if (daysPresent > workingDays) {
                        daysPresentInput.value = workingDays;
                    }
                }
                calculatePayroll();
            });
        }

        const daysPresentInput = document.getElementById('daysPresent');
        if (daysPresentInput) {
            daysPresentInput.addEventListener('input', function() {
                const workingDaysInput = document.getElementById('workingDays');
                const workingDays = workingDaysInput ? (parseInt(workingDaysInput.value) || 22) : 22;
                const daysPresent = parseInt(this.value) || 0;
                if (daysPresent > workingDays) {
                    this.value = workingDays;
                }
                calculatePayroll();
            });
        }

        // NOTE: Form submission handler has been moved to the bottom of the script
        // to integrate with the confirmation modal. See line ~1141.

        // Modal close
        const successModalToggle = document.querySelector('[data-modal-toggle="successModal"]');
        if (successModalToggle) {
            successModalToggle.addEventListener('click', function() {
                const modal = document.getElementById('successModal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        }

        // Print functionality
        const printBtn = document.getElementById('printBtn');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
                window.print();
            });
        }

        // Initialize
        calculatePayroll();

        /*show staff info*/ 

        $('#staffId').change(function(ev) {

            // BUGFIX: Use native JavaScript to get selected values instead of jQuery .val()
            // This avoids jQuery's internal DOM traversal that causes the toLowerCase error
            let staffIds = [];
            const staffIdElement = document.getElementById('staffId');
            
            if (!staffIdElement) {
                console.log('Staff select element not found');
                return;
            }
            
            try {
                const selectedOptions = staffIdElement.selectedOptions;
                if (selectedOptions && selectedOptions.length > 0) {
                    staffIds = Array.from(selectedOptions).map(opt => opt.value);
                } else {
                    staffIds = [];
                }
            } catch (e) {
                console.log('Error reading staffId during change event:', e);
                staffIds = [];
                return;
            }
            
            // Don't proceed if no staff is selected
            if(!staffIds || staffIds.length === 0) {
                console.log('No staff selected - change handler skipped');
                return; // Exit early
            }
            
            const url = <?php echo json_encode(site_url('admin/getStaffDetails')); ?>;

            $.ajax({
                url: url,
                type: 'post',
                dataType: 'html',
                cache: false,
                data: {staffData: staffIds}

            })
            .done(function(response) {

                $('#teacherDetails').html(response);
                $('#teacherDetails').removeClass('hidden');

                /*invoke the method*/
                var lastStaffCode = '';

                if(staffIds.length > staffIdArray.length) {
                    // Find newly added staff codes using vanilla JS
                    const newStaffCodes = staffIds.filter(id => !staffIdArray.includes(id));
                    lastStaffCode = newStaffCodes[0] || '';

                } else {
                    let arrayLength = staffIds.length;
                    lastStaffCode = staffIds[arrayLength - 1] || '';
                }

                staffIdArray = staffIds;

                if(lastStaffCode == undefined) lastStaffCode = '';
                const payrollMonth = $('#payrollMonth').val();

                loadStaffExistingPayroll(lastStaffCode, payrollMonth);

            })
            .fail(function(err) {

                $('#teacherDetails').html(err.responseText);
            })
        });

        function loadStaffExistingPayroll(staff_code, payrollMonth) {

            const url = <?php echo json_encode(site_url('admin/getPayrollFormByStaffCode/')); ?>;

            $.ajax({
                url: url,
                type: 'post',
                dataType: 'html',
                cache: false,
                data: {staffCode: staff_code, payrollMonthYear: payrollMonth}

            })
            .done(function(response) {
               
                if(response == 'Not found') {

                    location.reload(); //refresh the page
                    
                } else {

                    $('#payrollFormHolder').html(response);
                    $('#modal_alert').modal('hide'); // Close loading modal
                    
                    // CRITICAL FIX: Destroy old calculator instance and reinitialize after DOM update
                    // This ensures event listeners are properly bound to the new form elements
                    setTimeout(function() {
                        if (window.payrollCalculator) {
                            // Destroy previous instance if it exists
                            window.payrollCalculator = null;
                        }
                        // Create new PayrollCalculator instance for the reloaded form
                        window.payrollCalculator = new PayrollCalculator();
                        console.log('PayrollCalculator reinitialized after month change');
                    }, 100);
                    
                    // Check for duplicate payroll after form is loaded
                    checkForDuplicatePayrollOnLoad(staff_code, payrollMonth);
                }

            })
            .fail(function(err) {

                $('#payrollFormHolder').html(err.responseText);
            })

        }

        // Print payslip function is now globally defined in payroll_system.php

        // ========================================
        // Payroll Confirmation Modal - Show Breakdown Before Processing
        // ========================================
        function showPayrollConfirmationModal() {
            const staffIds = $('#staffId').val();
            const staffName = $('#staffId option:selected').text();
            const payrollMonth = $('#payrollMonth').val();
            const [year, month] = payrollMonth.split('-');
            
            // Helper function to check if a field is visible
            const isFieldVisible = (fieldName) => {
                const fieldElement = $(`#${fieldName}`);
                if (fieldElement.length === 0) return false;
                
                // Check if the field wrapper is visible (works for both div and tr structures)
                const fieldWrapper = fieldElement.closest('.payroll-field-wrapper, tr');
                return fieldWrapper.length > 0 && fieldWrapper.is(':visible');
            };
            
            // Get all payroll values
            const basicSalary = parseFloat($('#basicSalary').val()) || 0;
            const marketPremium = parseFloat($('#marketPremium').val()) || 0;
            const teachingAllowance = parseFloat($('#teachingAllowance').val()) || 0;
            const responsibilityAllowance = parseFloat($('#responsibilityAllowance').val()) || 0;
            const extraClasses = parseFloat($('#extraClasses').val()) || 0;
            const ruralAllowance = parseFloat($('#ruralAllowance').val()) || 0;
            const otherAllowances = parseFloat($('#otherAllowances').val()) || 0;
            
            const petra = parseFloat($('#petra').val()) || 0;
            const incomeTax = parseFloat($('#incomeTax').val()) || 0;
            const salaryAdvance = parseFloat($('#salaryAdvance').val()) || 0;
            const loans = parseFloat($('#loans').val()) || 0;
            const welfare = parseFloat($('#welfare').val()) || 0;
            const gnat = parseFloat($('#gnat').val()) || 0;
            const otherDeductions = parseFloat($('#otherDeductions').val()) || 0;
            
            const grossSalary = parseFloat($('#grossSalary').text().replace(/[^0-9.-]+/g, '')) || 0;
            const totalAllowances = basicSalary + marketPremium + teachingAllowance + responsibilityAllowance + extraClasses + ruralAllowance + otherAllowances;
            const totalDeductions = petra + incomeTax + salaryAdvance + loans + welfare + gnat + otherDeductions;
            const netSalary = parseFloat($('#netSalary').text().replace(/[^0-9.-]+/g, '')) || 0;
            
            const workingDays = parseInt($('#workingDays').val()) || 0;
            const daysPresent = parseInt($('#daysPresent').val()) || 0;
            const daysAbsent = parseInt($('#daysAbsent').val()) || 0;
            
            // Format currency
            const formatCurrency = (amount) => {
                return new Intl.NumberFormat('en-GH', { style: 'currency', currency: 'GHS' }).format(amount);
            };
            
            // Get month name
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const monthName = monthNames[parseInt(month) - 1];
            
            // Build earnings breakdown - only include visible fields
            let earningsRows = '';
            
            // Basic Salary is always shown (required field)
            earningsRows += `<tr><td style="padding: 5px 8px;">Basic Salary</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(basicSalary)}</td></tr>`;
            
            if (isFieldVisible('marketPremium')) {
                earningsRows += `<tr><td style="padding: 5px 8px;">Market Premium</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(marketPremium)}</td></tr>`;
            }
            if (isFieldVisible('teachingAllowance')) {
                earningsRows += `<tr><td style="padding: 5px 8px;">Teaching Allowance</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(teachingAllowance)}</td></tr>`;
            }
            if (isFieldVisible('responsibilityAllowance')) {
                earningsRows += `<tr><td style="padding: 5px 8px;">Responsibility Allowance</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(responsibilityAllowance)}</td></tr>`;
            }
            if (isFieldVisible('extraClasses')) {
                earningsRows += `<tr><td style="padding: 5px 8px;">Extra Classes</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(extraClasses)}</td></tr>`;
            }
            if (isFieldVisible('ruralAllowance')) {
                earningsRows += `<tr><td style="padding: 5px 8px;">Rural Allowance</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(ruralAllowance)}</td></tr>`;
            }
            if (isFieldVisible('otherAllowances')) {
                earningsRows += `<tr><td style="padding: 5px 8px;">Other Allowances</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(otherAllowances)}</td></tr>`;
            }
            
            // Build deductions breakdown - only include visible fields
            let deductionsRows = '';
            
            if (isFieldVisible('petra')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">PETRA</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(petra)}</td></tr>`;
            }
            if (isFieldVisible('incomeTax')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">Income Tax</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(incomeTax)}</td></tr>`;
            }
            if (isFieldVisible('salaryAdvance')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">Salary Advance</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(salaryAdvance)}</td></tr>`;
            }
            if (isFieldVisible('loans')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">Loans</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(loans)}</td></tr>`;
            }
            if (isFieldVisible('welfare')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">Welfare</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(welfare)}</td></tr>`;
            }
            if (isFieldVisible('gnat')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">GNAT</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(gnat)}</td></tr>`;
            }
            if (isFieldVisible('otherDeductions')) {
                deductionsRows += `<tr><td style="padding: 5px 8px;">Other Deductions</td><td style="text-align: right; padding: 5px 8px;">${formatCurrency(otherDeductions)}</td></tr>`;
            }
            
            // Build detailed confirmation message with filtered breakdown
            const message = `<div style="text-align: left;">` +
                            `<h4 style="margin-bottom: 15px; color: #2c3e50;">Confirm Payroll Processing</h4>` +
                            `<p style="margin-bottom: 15px;"><strong>Staff:</strong> ${staffName}<br><strong>Period:</strong> ${monthName} ${year}</p>` +
                            
                            `<table class="table table-bordered table-sm" style="margin-bottom: 15px; font-size: 13px;">` +
                            `<thead style="background: #e8f4f8;"><tr><th colspan="2" style="padding: 8px;"><i class="fa fa-plus-circle"></i> Earnings Breakdown</th></tr></thead>` +
                            `<tbody style="background: #fff;">` +
                            earningsRows +
                            `<tr style="background: #d4edda; font-weight: bold;"><td style="padding: 8px;">Gross Salary</td><td style="text-align: right; padding: 8px;">${formatCurrency(grossSalary)}</td></tr>` +
                            `</tbody></table>` +
                            
                            `<table class="table table-bordered table-sm" style="margin-bottom: 15px; font-size: 13px;">` +
                            `<thead style="background: #f8d7da;"><tr><th colspan="2" style="padding: 8px;"><i class="fa fa-minus-circle"></i> Deductions Breakdown</th></tr></thead>` +
                            `<tbody style="background: #fff;">` +
                            deductionsRows +
                            `<tr style="background: #f8d7da; font-weight: bold;"><td style="padding: 8px;">Total Deductions</td><td style="text-align: right; padding: 8px;">${formatCurrency(totalDeductions)}</td></tr>` +
                            `</tbody></table>` +
                            
                            `<div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center; color: white;">` +
                            `<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">NET SALARY</div>` +
                            `<div style="font-size: 28px; font-weight: bold;">${formatCurrency(netSalary)}</div>` +
                            `</div>` +
                            
                            `<div style="background: #e9ecef; padding: 12px; border-radius: 6px; margin-bottom: 15px;">` +
                            `<strong><i class="fa fa-calendar-check-o"></i> Attendance Information:</strong><br>` +
                            `Working Days: ${workingDays} | Present: ${daysPresent} | Absent: ${daysAbsent}` +
                            `</div>` +
                            
                            `<p style="margin-top: 15px; color: #0c5460; font-weight: bold; text-align: center;">Proceed with this payment?</p>` +
                            `</div>`;
            
            // Apply custom width styling to match duplicate detection modal (700px)
            $('#modal_confirm .modal-dialog').css('max-width', '700px');
            
            showCustomConfirm(
                message,
                function() {
                    // On Yes - proceed with submission
                    $('#modal_confirm').modal('hide');
                    // Reset width after use
                    $('#modal_confirm .modal-dialog').css('max-width', '');
                    submitPayrollData();
                },
                function() {
                    // On No - just close, stay on current page
                    $('#modal_confirm').modal('hide');
                    // Reset width after use
                    $('#modal_confirm .modal-dialog').css('max-width', '');
                }
            );
        }

        // Submit payroll data after confirmation
        function submitPayrollData() {
            showAjaxModal_alert('Please wait whilst we process your payments.', 'Loading', false, false);

            const staffIds = $('#staffId').val();
            const url = $('#payrollForm').attr('action');
            const formData = new FormData(document.getElementById('payrollForm'));
            formData.append("staffData", staffIds);

            $.ajax({
                url: url,
                type: 'post',
                dataType: 'text',
                cache: false,
                data: formData,
                processData: false,
                contentType: false,
            })
            .done(function(response) {
                $('#modal_alert').modal('hide');

                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        if(response == 'success') {
                            showAjaxModal_alert('Staff\'s salary has been processed and paid.', 'Success', true, true);
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                            return;
                        }
                    }
                }

                if(response.success === true || response == 'success') {
                    showAjaxModal_alert(response.message || 'Staff\'s salary has been processed and paid.', 'Success', true, true);
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    showAjaxModal_alert(response.message || 'Unable to process this payment. Please try again later.', 'Error', false, true);
                }
            })
            .fail(function(err) {
                $('#modal_alert').modal('hide');
                showAjaxModal_alert(err.responseText || 'An error occurred while processing payment.', 'Error', false, true);
            });
        }

        // Modified form submission handler to show confirmation first
        $('#payrollForm').on('submit', function(e) {
            e.preventDefault();
            showPayrollConfirmationModal();
        });

        // Field visibility management
        function openPayrollFieldCustomizer() {
            const modalContent = $('#payrollFieldCustomizerModal .modal-body');
            
            // Show loading indicator
            modalContent.prepend('<div id="prefsLoadingIndicator" style="text-align: center; padding: 20px;"><i class="fa fa-spinner fa-spin" style="font-size: 20px; color: #3b82f6;"></i><p style="margin-top: 10px; color: #6b7280;">Loading preferences...</p></div>');
            
            $('#payrollFieldCustomizerModal').modal('show');
            
            // Load preferences from database
            $.ajax({
                url: '<?php echo site_url("admin/get_payroll_field_preferences"); ?>',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#prefsLoadingIndicator').remove();
                    if (response.status === 'success') {
                        const prefsMap = {};
                        response.preferences.forEach(pref => {
                            prefsMap[pref.field_name] = pref.is_visible === '1' || pref.is_visible === 1;
                        });
                        
                        // Update checkboxes with loaded preferences
                        $('.field-toggle-checkbox').each(function() {
                            const fieldName = $(this).data('field');
                            if (prefsMap[fieldName] !== undefined) {
                                $(this).prop('checked', prefsMap[fieldName]);
                            }
                        });
                    }
                },
                error: function() {
                    $('#prefsLoadingIndicator').remove();
                }
            });
        }

        function applyPayrollFieldSettings() {
            const settings = {};
            $('.field-toggle-checkbox').each(function() {
                const fieldName = $(this).data('field');
                const isVisible = $(this).is(':checked');
                settings[fieldName] = isVisible;
                
                // Save each preference to database
                $.ajax({
                    url: '<?php echo site_url("admin/save_payroll_field_preference"); ?>',
                    method: 'POST',
                    data: {
                        fieldName: fieldName,
                        isVisible: isVisible ? 1 : 0
                    },
                    dataType: 'json'
                });
            });
            
            applyFieldVisibilitySettings();
            $('#payrollFieldCustomizerModal').modal('hide');
            showSuccessToast('Field preferences saved successfully');
        }

        function resetPayrollFieldSettings() {
            showCustomConfirm(
                'Reset all field visibility preferences to default? This will show all fields.',
                function() {
                    // User clicked Yes - proceed with reset
                    $('#modal_confirm').modal('hide');
                    executeResetPayrollFieldSettings();
                },
                function() {
                    // User clicked No - just close
                    $('#modal_confirm').modal('hide');
                }
            );
        }

        function executeResetPayrollFieldSettings() {
            
            $.ajax({
                url: '<?php echo site_url("admin/reset_payroll_field_preferences"); ?>',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('.field-toggle-checkbox').prop('checked', true);
                        applyFieldVisibilitySettings();
                        showSuccessToast('All preferences reset to default');
                    } else {
                        showErrorToast('Reset failed');
                    }
                },
                error: function() {
                    showErrorToast('Network error during reset');
                }
            });
        }

        function applyFieldVisibilitySettings() {
            // Apply visibility based on current checkbox states
            $('.field-toggle-checkbox').each(function() {
                const fieldName = $(this).data('field');
                const isVisible = $(this).is(':checked');
                const fieldWrapper = $(`.payroll-field-wrapper[data-field="${fieldName}"]`);
                
                if (fieldWrapper.length) {
                    if (isVisible) {
                        fieldWrapper.show();
                    } else {
                        fieldWrapper.hide();
                    }
                }
            });
        }

        function searchPayrollFields() {
            const searchTerm = $('#fieldSearchInput').val().toLowerCase();
            $('.field-toggle-item').each(function() {
                const label = $(this).find('label').text().toLowerCase();
                if (label.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        $(document).ready(function() {
            // Load preferences from database on page load
            $.ajax({
                url: '<?php echo site_url("admin/get_payroll_field_preferences"); ?>',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const prefsMap = {};
                        response.preferences.forEach(pref => {
                            prefsMap[pref.field_name] = pref.is_visible === '1' || pref.is_visible === 1;
                        });
                        
                        // Apply preferences to checkboxes and form fields
                        $('.field-toggle-checkbox').each(function() {
                            const fieldName = $(this).data('field');
                            if (prefsMap[fieldName] !== undefined) {
                                $(this).prop('checked', prefsMap[fieldName]);
                            }
                        });
                        
                        applyFieldVisibilitySettings();
                    }
                },
                error: function() {
                    // Silently fail - default to all fields visible
                    console.log('Could not load field preferences, using defaults');
                }
            });
        });

        // Toast notification helper functions
        function showSuccessToast(message) {
            toastr.success(message, '', {
                timeOut: 3000,
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }

        function showErrorToast(message) {
            toastr.error(message, 'Error', {
                timeOut: 5000,
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }

    })
        
    </script>

<!-- Field Customizer Modal -->
<div class="modal fade" id="payrollFieldCustomizerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width: 700px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title" style="font-weight: 600;">
                    <i class="fa fa-sliders mr-2"></i>Customize Payroll Form Fields
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto; padding: 16px;">
                <div class="mb-3">
                    <input type="text" id="fieldSearchInput" class="form-control" placeholder="🔍 Search fields..." onkeyup="searchPayrollFields()" style="border-radius: 8px; padding: 10px 14px; border: 2px solid #e2e8f0; font-size: 14px;">
                </div>
                
                <div class="field-sections">
                    <!-- Earnings Section -->
                    <div class="mb-3">
                        <h6 style="color: #16a34a; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                            <i class="fas fa-money-bill-wave mr-1"></i>Earnings
                        </h6>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="marketPremium" checked style="display: none;">
                                <span class="toggle-slider" style="display: inline-block; width: 40px; height: 20px; background: #cbd5e1; border-radius: 20px; position: relative; transition: 0.3s;"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Market Premium</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="teachingAllowance" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Teaching Allowance</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="responsibilityAllowance" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Responsibility Allowance</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="extraClasses" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Extra Classes</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="ruralAllowance" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Rural Allowance</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="otherAllowances" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Other Allowances</span>
                            </label>
                        </div>
                    </div>

                    <!-- Employee Deductions Section -->
                    <div class="mb-3">
                        <h6 style="color: #dc2626; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                            <i class="fas fa-minus-circle mr-1"></i>Employee Deductions
                        </h6>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="petra" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">SSNIT Tier 2</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="incomeTax" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Income Tax - PAYE</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="salaryAdvance" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Salary Advance</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="loans" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Loan Deductions</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="welfare" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Welfare Deductions</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="gnat" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">GNAT Dues</span>
                            </label>
                        </div>
                        <div class="field-toggle-item mb-2" style="display: flex; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 6px;">
                            <label class="toggle-switch mb-0" style="flex-grow: 1; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="field-toggle-checkbox" data-field="otherDeductions" checked style="display: none;">
                                <span class="toggle-slider"></span>
                                <span style="margin-left: 10px; font-size: 13px; color: #1e293b;">Other Deductions</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 12px 16px;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="resetPayrollFieldSettings()" style="border-radius: 6px; padding: 6px 16px; font-size: 13px;">
                    <i class="fa fa-undo mr-1"></i>Reset to Default
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="applyPayrollFieldSettings()" style="border-radius: 6px; padding: 6px 16px; font-size: 13px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    <i class="fa fa-check mr-1"></i>Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.toggle-switch input:checked + .toggle-slider {
    background: #3b82f6;
}
.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
.toggle-slider:before {
    content: "";
    position: absolute;
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
}
</style>