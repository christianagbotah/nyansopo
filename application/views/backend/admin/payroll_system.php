
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staffs Payroll System</title>
    
    <!-- Bootstrap CSS (REQUIRED for modal.php to work properly) -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.css');?>">
    
    <link rel="stylesheet" href="<?=base_url('node_modules/flowbite/dist/flowbite.min.css')?>">
    <link rel="stylesheet" href="<?=base_url('node_modules/flowbite-datepicker/dist/css/datepicker.min.css')?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/fontawesome/6.7.2/css/all.min.css');?>">

    <!-- Tailwindcss -->
    <link rel="stylesheet" href="<?php echo base_url('assets/tailwindcss/output.css');?>">

    <link rel="stylesheet" href="<?php echo base_url('assets/js/select2/select2-bootstrap.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/js/select2/select2.css');?>">

    <!-- Modern Multi-Select (Select2 Replacement) -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/modern-multiselect.css?v='.time());?>">

    <!-- Payroll Modernization CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/payroll_modern.css?v='.time());?>">

    <!--jQuery-->
    <script src="<?php echo base_url('assets/js/jquery-3.4.1.js');?>" type="text/javascript"></script>
    
    <!-- Bootstrap JS (required for modal.php functions) -->
    <!-- MUST use bootstrap-debug.js (not bootstrap.js) as it includes the modal plugin -->
    <script src="<?php echo base_url('assets/js/bootstrap-debug.js');?>" type="text/javascript"></script>

     <script src="<?php echo base_url('assets/tailwindcss/tailwindcss.js');?>"></script>
     <script src="<?=base_url('node_modules/flowbite/dist/flowbite.min.js')?>"></script>
    <script src="<?=base_url('node_modules/flowbite-datepicker/dist/js/datepicker.min.js')?>"></script>
    
    <!-- Payroll Real-time Calculator Script -->
    <script src="<?php echo base_url('assets/js/payroll_realtime_calculator.js');?>" type="text/javascript"></script>
    
    <!-- Payroll Frontend Validation Script (Wave 6 - Tasks 9.1-9.7) -->
    <script src="<?php echo base_url('assets/js/payroll_frontend_validation.js');?>" type="text/javascript"></script>
    
    <!-- Modern Multi-Select Script (Select2 Replacement) -->
    <script src="<?php echo base_url('assets/js/modern-multiselect.js?v='.time());?>" type="text/javascript"></script>
    
    <!-- Payroll Loading Overlay Script (Task 17.1-17.5) -->
    <script src="<?php echo base_url('assets/js/payroll_loading_overlay.js');?>" type="text/javascript"></script>

    <style>
        /* FIX: Ensure Bootstrap modals stay hidden (Tailwind CSS override) */
        .modal {
            display: none !important;
        }
        .modal.in {
            display: block !important;
        }
        .modal.fade.in {
            display: block !important;
        }
        
        /* FIX: Prevent Tailwind CSS reset from affecting page typography */
        body, html {
            font-size: 14px !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-size: revert !important;
            font-weight: revert !important;
            margin: revert !important;
        }
        
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
        
        input[type="month"]:hover::-webkit-calendar-picker-indicator {
            opacity: 1;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 4px;
        }
        
        /* Category badges for staff selection */
        .badge-admin {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-teacher {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-non-teaching {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Enhanced Select2 styling */
        .select2-container--default .select2-selection--multiple {
            min-height: 45px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .select2-container--default .select2-results__group {
            font-weight: 700;
            color: #1e293b;
            padding: 8px 12px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        .select2-container--default .select2-results__option {
            padding: 10px 16px;
            font-size: 13px;
        }
        
        .select2-container--default .select2-results__option--highlighted {
            background: #eff6ff !important;
            color: #1e293b;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            color: #1e40af;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #3b82f6;
            font-weight: bold;
            margin-right: 6px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #1e40af;
        }
    </style>

    <!-- <link href="<?php echo base_url(); ?>assets/cdn/css/flowbite.min.css" rel="stylesheet" />
    <script src="<?php echo base_url(); ?>assets/cdn/js/flowbite.min.js"></script> -->
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8 sticky top-5 z-50" style="z-index: 999">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-12 h-8 bg-red-600 mr-2"></div>
                    <div class="w-12 h-8 bg-yellow-400 mr-2"></div>
                    <div class="w-12 h-8 bg-green-600"></div>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= get_phrase($page_title);?></h1>
                <!-- <h2 class="text-xl font-semibold text-blue-600 mb-2"><?= get_phrase($page_title);?></h2>
                <p class="text-gray-600">Monthly Salary Payment Management</p> -->
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Staff Information Card -->
                <div class="lg:col-span-1  h-[75vh] max-h-[75vh] overflow-y-scroll">
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Staff Information</h3>
                        <?= form_open(site_url('admin/getStaffDetails'), ['class' => 'space-y-4', 'id' => 'teacherSearchForm']);?>
                            <div>
                                <label for="staffId" class="block mb-2 text-sm font-medium text-gray-900">Staff ID</label>
                                <select id="staffId" multiple name="staffId[]" class="modern-multiselect-auto" placeholder="Select staff members..." required>
                                    <optgroup label="👔 Administrators">
                                        <?php
                                        foreach($this->crud_model->get_admins() as $admin):?>

                                            <option value="admin_<?= $admin['admin_code'] ?>" data-category="administrator"><?= $admin['admin_code'] ?> / <?= $admin['name'] ?></option>
                                        <?php

                                            endforeach;
                                        ?>
                                    </optgroup>
                                    <optgroup label="👨‍🏫 Teachers">
                                        <?php
                                        foreach($this->crud_model->get_teachers() as $teacher):?>

                                            <option value="teacher_<?= $teacher['teacher_code'] ?>" data-category="teacher"><?= $teacher['teacher_code'] ?> / <?= $teacher['name'] ?></option>
                                        <?php

                                            endforeach;
                                        ?>
                                    </optgroup>
                                    <optgroup label="👷 Non-Teaching Staff">
                                        <?php
                                        if($this->db->table_exists('non_teaching_staff')) {
                                            $non_teaching_staff = $this->db->get('non_teaching_staff')->result_array();
                                            foreach($non_teaching_staff as $staff):?>

                                                <option value="non_teaching_staff_<?= $staff['staff_code'] ?>" data-category="non_teaching_staff"><?= $staff['staff_code'] ?> / <?= $staff['name'] ?></option>
                                            <?php

                                            endforeach;
                                        }
                                        ?>
                                    </optgroup>
                                    
                                </select>
                            </div>
                            <!-- <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                Search Teacher
                            </button> -->
                        </form>
                        
                        <!-- Staff Details (shown after search) -->
                        <div id="teacherDetails" class="mt-6 hidden">
                            
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="payroll-quick-summary" id="quickSummaryCard">
                        <h3>Quick Summary</h3>
                        <div class="payroll-summary-item">
                            <span class="payroll-summary-label">Gross Salary</span>
                            <span id="quickGrossSalary" class="payroll-summary-value">GH₵ 0.00</span>
                        </div>
                        <div class="payroll-summary-item">
                            <span class="payroll-summary-label">Total Deductions</span>
                            <span id="quickDeductions" class="payroll-summary-value negative">GH₵ 0.00</span>
                        </div>
                        <div class="payroll-summary-item">
                            <span class="payroll-summary-label">Net Salary</span>
                            <span id="quickNetSalary" class="payroll-summary-value positive">GH₵ 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payroll Form -->
                <div class="lg:col-span-2 h-[75vh] max-h-[75vh] overflow-y-scroll" id="payrollFormHolder">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        

                        <!-- Customize Form Button -->
                        <div style="text-align: right; margin-bottom: 20px;">
                            <button type="button" onclick="openPayrollFormCustomizer()" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa fa-sliders"></i> Customize Form Fields
                            </button>
                        </div>

                        <?= form_open('#', ['class' => 'space-y-6', 'id' => 'payrollForm']);?>

                            <!-- Validation Summary (Task 16.4) -->
                            <div id="validationSummary" class="hidden bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4" role="alert">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <strong class="font-medium">Please fix the following errors:</strong>
                                </div>
                                <ul id="validationErrorList" class="list-disc list-inside text-sm space-y-1">
                                </ul>
                            </div>

                            <!-- TASK 15.3: REORGANIZED 5-SECTION STRUCTURE -->
                            
                            <!-- Section 1: Basic Information -->
                            <div class="modern-card section-basic-info">
                                <div class="modern-card-header gradient-gray">
                                    <i class="fas fa-info-circle mr-2"></i>Basic Information
                                </div>
                                <div class="modern-card-body">
                                    <div class="payroll-grid payroll-grid-2">
                                        <div class="payroll-form-group">
                                            <label for="payrollMonth" class="payroll-form-label required">Month & Year</label>
                                            <input type="month" id="payrollMonth" name="payrollMonth" class="payroll-form-input" style="cursor: pointer;">
                                        </div>
                                        <div class="payroll-form-group">
                                            <label class="payroll-form-label">Selected Period</label>
                                            <div class="payroll-form-input bg-gray-50" style="padding: 0.75rem; border: 1px solid #e2e8f0;">
                                                <span id="currentMonth" class="font-medium"><?php echo date('F Y'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Earnings (Green Header) -->
                            <div class="modern-card section-allowances">
                                <div class="modern-card-header gradient-green">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Earnings - Basic Salary & Allowances
                                </div>
                                <div class="modern-card-body">
                                    <div class="payroll-grid payroll-grid-2">
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="basicSalary">
                                            <label for="basicSalary" class="payroll-form-label required">Basic Salary (GH₵)</label>
                                            <input type="number" id="basicSalary" name="basicSalary" step="0.01" class="payroll-form-input" placeholder="2500.00" required>
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="marketPremium">
                                            <label for="marketPremium" class="payroll-form-label">Market Premium (GH₵)</label>
                                            <input type="number" id="marketPremium" name="marketPremium" step="0.01" class="payroll-form-input" placeholder="300.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="teachingAllowance">
                                            <label for="teachingAllowance" class="payroll-form-label">Teaching Allowance (GH₵)</label>
                                            <input type="number" id="teachingAllowance" name="teachingAllowance" step="0.01" class="payroll-form-input" placeholder="200.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="responsibilityAllowance">
                                            <label for="responsibilityAllowance" class="payroll-form-label">Responsibility Allowance (GH₵)</label>
                                            <input type="number" id="responsibilityAllowance" name="responsibilityAllowance" step="0.01" class="payroll-form-input" placeholder="150.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="extraClasses">
                                            <label for="extraClasses" class="payroll-form-label">Extra Classes (GH₵)</label>
                                            <input type="number" id="extraClasses" name="extraClasses" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="ruralAllowance">
                                            <label for="ruralAllowance" class="payroll-form-label">Rural Allowance (GH₵)</label>
                                            <input type="number" id="ruralAllowance" name="ruralAllowance" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="otherAllowances">
                                            <label for="otherAllowances" class="payroll-form-label">Other Allowance (GH₵)</label>
                                            <input type="number" id="otherAllowances" name="otherAllowances" step="0.01" class="payroll-form-input" placeholder="0.00">
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
                                                    $rates = json_decode($statutory_rates_json, true);
                                                    echo rtrim(rtrim(number_format($rates['ssnit_tier1_employer'], 1), '0'), '.');
                                                ?></span>%) - Employer Contribution
                                                <span class="payroll-tooltip">
                                                    <span class="payroll-tooltip-icon">?</span>
                                                    <span class="payroll-tooltip-text">This is an employer contribution calculated on basic salary and is NOT deducted from the employee's salary</span>
                                                </span>
                                            </label>
                                            <input type="number" id="ssnit" name="ssnit" step="0.01" class="payroll-form-input bg-blue-50" placeholder="0.00" readonly>
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
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="petra">
                                            <label for="petra" class="payroll-form-label">
                                                SSNIT Tier 2 (<span id="ssnitTier2RateLabel"><?php 
                                                    $rates = json_decode($statutory_rates_json, true);
                                                    echo rtrim(rtrim(number_format($rates['ssnit_tier2'], 1), '0'), '.');
                                                ?></span>%)
                                                <span class="payroll-tooltip">
                                                    <span class="payroll-tooltip-icon">?</span>
                                                    <span class="payroll-tooltip-text">This is an employee contribution calculated on basic salary, deducted from gross salary and sent to the selected Tier 2 provider</span>
                                                </span>
                                            </label>
                                            <input type="number" id="petra" name="petra" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                        <div class="payroll-form-group">
                                            <label for="tier2ProviderDisplay" class="payroll-form-label">Tier 2 Provider</label>
                                            <?php
                                            // Get the first active Tier 2 provider
                                            $tier2_provider = null;
                                            if ($this->db->table_exists('pension_tier2_providers')) {
                                                $tier2_provider = $this->db->get_where('pension_tier2_providers', ['is_active' => 1])->row();
                                            }
                                            ?>
                                            <div class="payroll-form-input bg-gray-50" style="padding: 0.75rem; border: 1px solid #e2e8f0;">
                                                <span class="font-medium text-gray-900">
                                                    <?php echo $tier2_provider ? htmlspecialchars($tier2_provider->provider_name) : 'Not Set'; ?>
                                                </span>
                                            </div>
                                            <input type="hidden" name="tier2_provider_id" value="<?php echo $tier2_provider ? $tier2_provider->provider_id : ''; ?>">
                                        </div>
                                        
                                        <!-- Tax -->
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="incomeTax">
                                            <label for="incomeTax" class="payroll-form-label">Income Tax - PAYE (GH₵)</label>
                                            <div class="flex gap-2">
                                                <input type="number" id="incomeTax" name="incomeTax" step="0.01" class="payroll-form-input" placeholder="0.00">
                                                <button type="button" id="autoCalculatePayeBtn" class="payroll-button payroll-button-primary whitespace-nowrap">
                                                    Auto-Calculate
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Statutory Deductions with Toggle Switches - HIDDEN (Default to 0) -->
                                        <div class="payroll-form-group" style="display: none;">
                                            <div class="flex items-center justify-between mb-2">
                                                <label for="getfund" class="payroll-form-label">GETFund (<span id="getfundRateLabel"><?php 
                                                    $rates = json_decode($statutory_rates_json, true);
                                                    echo $rates['getfund'];
                                                ?></span>%) (GH₵)</label>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" id="getfundToggle" class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                                    <span class="ml-2 text-sm font-medium text-gray-700">Apply</span>
                                                </label>
                                            </div>
                                            <input type="number" id="getfund" name="getfund" step="0.01" class="payroll-form-input" placeholder="0.00" value="0.00" disabled>
                                        </div>
                                        <div class="payroll-form-group" style="display: none;">
                                            <div class="flex items-center justify-between mb-2">
                                                <label for="nhil" class="payroll-form-label">NHIL (<span id="nhilRateLabel"><?php 
                                                    $rates = json_decode($statutory_rates_json, true);
                                                    echo $rates['nhil'];
                                                ?></span>%) (GH₵)</label>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" id="nhilToggle" class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                                    <span class="ml-2 text-sm font-medium text-gray-700">Apply</span>
                                                </label>
                                            </div>
                                            <input type="number" id="nhil" name="nhil" step="0.01" class="payroll-form-input" placeholder="0.00" value="0.00" disabled>
                                        </div>
                                        
                                        <!-- Other Deductions -->
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="salaryAdvance">
                                            <label for="salaryAdvance" class="payroll-form-label">Salary Advance (GH₵)</label>
                                            <input type="number" id="salaryAdvance" name="salaryAdvance" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="loans">
                                            <label for="loans" class="payroll-form-label">Loan Deductions (GH₵)</label>
                                            <input type="number" id="loans" name="loans" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="welfare">
                                            <label for="welfare" class="payroll-form-label">Welfare Deductions (GH₵)</label>
                                            <input type="number" id="welfare" name="welfare" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="gnat">
                                            <label for="gnat" class="payroll-form-label">GNAT Dues (GH₵)</label>
                                            <input type="number" id="gnat" name="gnat" step="0.01" class="payroll-form-input" placeholder="10.00">
                                        </div>
                                        <div class="payroll-form-group payroll-field-wrapper" data-field="otherDeductions">
                                            <label for="otherDeductions" class="payroll-form-label">Other Deductions (GH₵)</label>
                                            <input type="number" id="otherDeductions" name="otherDeductions" step="0.01" class="payroll-form-input" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Section (Optional Additional Info) -->
                            <div class="modern-card section-attendance">
                                <div class="modern-card-header gradient-teal">
                                    <i class="fas fa-calendar-check mr-2"></i>Attendance Information
                                </div>
                                <div class="modern-card-body">
                                    <div class="payroll-grid payroll-grid-3">
                                        <div class="payroll-form-group">
                                            <label for="workingDays" class="payroll-form-label required">Working Days</label>
                                            <input type="number" id="workingDays" name="workingDays" min="1" max="31" class="payroll-form-input" value="22">
                                        </div>
                                        <div class="payroll-form-group">
                                            <label for="daysPresent" class="payroll-form-label required">Days Present</label>
                                            <input type="number" id="daysPresent" name="daysPresent" min="0" max="31" class="payroll-form-input" value="22">
                                        </div>
                                        <div class="payroll-form-group">
                                            <label for="daysAbsent" class="payroll-form-label">Days Absent</label>
                                            <input type="number" id="daysAbsent" name="daysAbsent" min="0" max="31" class="payroll-form-input" value="0" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 5: Summary (Blue Header) -->
                            <div class="modern-card section-summary">
                                <div class="modern-card-header gradient-blue">
                                    <i class="fas fa-calculator mr-2"></i>Payroll Summary
                                </div>
                                <div class="modern-card-body">
                                    <div class="payroll-grid payroll-grid-2">
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">Basic Salary:</span>
                                                <span id="summaryBasic" class="text-sm font-medium">GH₵ 0.00</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">Total Allowances:</span>
                                                <span id="summaryAllowances" class="text-sm font-medium text-green-600">GH₵ 0.00</span>
                                            </div>
                                            <div class="flex justify-between items-center border-t border-gray-200 pt-3">
                                                <span class="text-sm font-semibold text-gray-900">Gross Salary:</span>
                                                <span id="grossSalary" class="text-sm font-bold text-blue-600">GH₵ 0.00</span>
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">Statutory Deductions:</span>
                                                <span id="summaryStatutory" class="text-sm font-medium text-red-600">GH₵ 0.00</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">Other Deductions:</span>
                                                <span id="summaryOther" class="text-sm font-medium text-red-600">GH₵ 0.00</span>
                                            </div>
                                            <div class="flex justify-between items-center border-t border-gray-200 pt-3">
                                                <span class="text-sm font-semibold text-gray-900">Net Salary:</span>
                                                <span id="netSalary" class="text-lg font-bold text-green-600">GH₵ 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- hidden inputs -->
                            <input type="hidden" name="totalAllowancesForDb" id="totalAllowancesForDb">
                            <input type="hidden" name="totalDeductionsForDb" id="totalDeductionsForDb">
                            <input type="hidden" name="grossSalaryForDb" id="grossSalaryForDb">
                            <input type="hidden" name="netSalaryForDb" id="netSalaryForDb">

                            <!-- Action Buttons -->
                            <div class="payroll-button-group flex justify-end gap-4 bg-white pt-6 sticky bottom-0 z-50">
                                <button type="button" id="calculateBtn" class="payroll-button payroll-button-primary" style="display: none;">
                                    <i class="fas fa-calculator"></i> Calculate Payroll
                                </button>
                                <button type="button" id="autoDeductBtn" class="payroll-button payroll-button-danger" style="display: none;">
                                    <i class="fas fa-bolt"></i> Auto Calculate Deductions
                                </button>
                                <button type="submit" class="payroll-button payroll-button-success">
                                    <i class="fas fa-check-circle"></i> Process Payment
                                </button>
                                <!-- <button type="button" id="printBtn" class="payroll-button payroll-button-secondary">
                                    <i class="fas fa-print"></i> Printint Payslip
                                </button> --> 
                            </div>
                        </form>

                        <!-- Loading Overlay (Task 17.1-17.2) -->
                        <div class="payroll-loading-overlay" id="payrollLoadingOverlay">
                            <div class="payroll-loading-spinner">
                                <div class="payroll-spinner"></div>
                                <p class="payroll-loading-text">Processing payroll...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PAYE Breakdown Modal -->
    <div id="payeBreakdownModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-3xl h-full md:h-auto">
            <div class="relative p-6 bg-white rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Ghana PAYE Tax Calculation Breakdown</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" onclick="closePayeModal()">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
                
                <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">Monthly Gross Salary:</span>
                            <span id="payeModalGross" class="block text-lg font-semibold text-blue-600">GH₵ 0.00</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Total SSNIT Deductions:</span>
                            <span id="payeModalSsnit" class="block text-lg font-semibold text-blue-600">GH₵ 0.00</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Annual Taxable Income:</span>
                            <span id="payeModalTaxable" class="block text-lg font-semibold text-blue-600">GH₵ 0.00</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Monthly PAYE:</span>
                            <span id="payeModalPaye" class="block text-lg font-bold text-green-600">GH₵ 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-md font-semibold mb-3">Progressive Tax Bracket Breakdown</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3">Tax Bracket</th>
                                    <th class="px-4 py-3">Income Range</th>
                                    <th class="px-4 py-3">Tax Rate</th>
                                    <th class="px-4 py-3">Taxable Amount</th>
                                    <th class="px-4 py-3">Tax Amount</th>
                                </tr>
                            </thead>
                            <tbody id="payeBreakdownTableBody">
                                <!-- Populated by JavaScript -->
                            </tbody>
                            <tfoot class="font-bold bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-right">Total Annual PAYE:</td>
                                    <td id="payeTotalAnnual" class="px-4 py-3 text-green-600">GH₵ 0.00</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-right">Total Monthly PAYE:</td>
                                    <td id="payeTotalMonthly" class="px-4 py-3 text-green-600 text-lg">GH₵ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="button" class="flex-1 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5" onclick="acceptPayeCalculation()">
                        Accept & Use This PAYE
                    </button>
                    <button type="button" class="flex-1 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5" onclick="closePayeModal()">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>



    <script>
        // ===================================
        // STATUTORY RATES (Dynamic from Database)
        // ===================================
        const STATUTORY_RATES = <?php echo isset($statutory_rates_json) ? $statutory_rates_json : '{}'; ?>;
        
        // Helper function to get rate (no fallback - use database values only)
        function getRate(key) {
            if (STATUTORY_RATES[key] === undefined) {
                console.error('Statutory rate not found for key: ' + key);
                return 0;
            }
            return STATUTORY_RATES[key] / 100;
        }
        
        // ===================================
        // IMPORTANT: Modal functions are now defined globally in modal.php
        // Do NOT redefine showAjaxModal_alert() or showCustomConfirm() here
        // ===================================
    
        $(function() {
            $.ajaxSetup({
                //cache: false,
                data: {
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                } 
             });
             
            // Modern Multi-Select is auto-initialized via the 'modern-multiselect-auto' class
            // The placeholder is read from the HTML select element's placeholder attribute
        })

        window.staffIdArray = [];
        
        // ===================================
        // URL Parameter Handling for Edit from Payslip List
        // ===================================
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const staffCode = urlParams.get('staff');
            const period = urlParams.get('period');
            const category = urlParams.get('category');
            
            if (staffCode && period && category) {
                // Convert category to match option value format
                let categoryPrefix = category;
                if (category === 'administrator') {
                    categoryPrefix = 'admin';
                }
                
                // Build the full staff value (e.g., "admin_ADM-1")
                const fullStaffValue = categoryPrefix + '_' + staffCode;
                
                // Set the payroll month
                document.getElementById('payrollMonth').value = period;
                
                // Update the displayed month text
                const [year, month] = period.split('-');
                const monthDate = new Date(year, parseInt(month) - 1);
                const monthStr = monthDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                document.getElementById('currentMonth').textContent = monthStr;
                
                // Function to check and apply staff selection
                function applyStaffSelection(retryCount = 0) {
                    const maxRetries = 10; // Try for up to 2 seconds (10 * 200ms)
                    
                    const staffSelect = document.getElementById('staffId');
                    if (!staffSelect) {
                        if (retryCount < maxRetries) {
                            setTimeout(() => applyStaffSelection(retryCount + 1), 200);
                        } else {
                            console.error('Staff select element not found after retries');
                        }
                        return;
                    }
                    
                    // Verify the option exists
                    const option = staffSelect.querySelector('option[value="' + fullStaffValue + '"]');
                    if (!option) {
                        console.error('Staff option not found: ' + fullStaffValue);
                        return;
                    }
                    
                    // Check if ModernMultiSelect instance exists
                    if (window.modernMultiSelectInstances && window.modernMultiSelectInstances.staffId) {
                        // Use the setValue method to properly update the UI
                        window.modernMultiSelectInstances.staffId.setValue([fullStaffValue]);
                        
                        // Update staffIdArray
                        window.staffIdArray = [fullStaffValue];
                        
                        // CRITICAL: Explicitly trigger jQuery change event to load the form
                        // The setValue() triggers a native change event, but we need jQuery's handler too
                        $(staffSelect).trigger('change');
                        
                        console.log('Auto-selected staff from URL: ' + fullStaffValue + ' for period: ' + period);
                    } else if (retryCount < maxRetries) {
                        // Instance not ready yet, retry
                        console.log('ModernMultiSelect not ready, retrying... (' + (retryCount + 1) + '/' + maxRetries + ')');
                        setTimeout(() => applyStaffSelection(retryCount + 1), 200);
                    } else {
                        console.error('ModernMultiSelect instance not found for staffId after ' + maxRetries + ' retries');
                    }
                }
                
                // Start the retry loop
                applyStaffSelection();
            }
        })();
        
        // Set current month (default when no URL parameters)
        const now = new Date();
        const currentMonthStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        if (document.getElementById('currentMonth').textContent === '') {
            document.getElementById('currentMonth').textContent = currentMonthStr;
        }
        if (document.getElementById('payrollMonth').value === '') {
            document.getElementById('payrollMonth').value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
        }

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
            let staffIds = [];
            
            // CRITICAL FIX: Check if the element exists and is in a stable state before accessing it
            // The error happens when jQuery tries to access the element during ModernMultiSelect reinitialization
            const staffIdElement = document.getElementById('staffId');
            
            if (!staffIdElement) {
                console.log('Staff select element not found - skipping reload');
                return;
            }
            
            // Use native JavaScript to get selected options instead of jQuery .val()
            // This avoids jQuery's internal DOM traversal that causes the toLowerCase error
            try {
                const selectedOptions = staffIdElement.selectedOptions;
                if (selectedOptions && selectedOptions.length > 0) {
                    staffIds = Array.from(selectedOptions).map(opt => opt.value);
                } else {
                    staffIds = [];
                }
            } catch (e) {
                console.log('Error reading staffId value (likely during reinitialization):', e);
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

        // Search teacher functionality - wrapped in DOM-ready to prevent errors
        $(document).ready(function() {
            const teacherSearchForm = document.getElementById('teacherSearchForm');
            if (teacherSearchForm) {
                teacherSearchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    document.getElementById('teacherDetails').classList.remove('hidden');
                    // Auto-populate some sample data
                    document.getElementById('basicSalary').value = '2500.00';
                    document.getElementById('marketPremium').value = '300.00';
                    document.getElementById('teachingAllowance').value = '200.00';
                    document.getElementById('responsibilityAllowance').value = '150.00';
                    calculatePayroll();
                });
            }
        });

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
            
            // Calculate statutory deductions using dynamic rates from database
            const ssnitRate = getRate('ssnit_tier1_employer');
            const tier2Rate = getRate('ssnit_tier2');
            const getfundRate = getRate('getfund');
            const nhilRate = getRate('nhil');
            
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

        // Task 24.2 & 24.3: Provider change rules based on approval status
        // Note: For create form, provider is auto-selected from active provider
        // Wrapped in DOM-ready check to prevent "addEventListener of null" errors
        $(document).ready(function() {
            const getfundToggle = document.getElementById('getfundToggle');
            const getfundInput = document.getElementById('getfund');
            const nhilToggle = document.getElementById('nhilToggle');
            const nhilInput = document.getElementById('nhil');

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
        });
        
        // PAYE Auto-Calculate Button Handler - wrapped in DOM-ready to prevent errors
        $(document).ready(function() {
            const payeBtn = document.getElementById('autoCalculatePayeBtn');
            if (payeBtn) {
                payeBtn.addEventListener('click', function() {
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
                        url: '<?php echo site_url('admin/payroll_calculate_paye'); ?>',
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
        });
        
        // Task 24.2 & 24.3: Provider change rules based on approval status
        // Note: For create form, always allow provider selection (no restrictions)
        // Restrictions apply only in edit mode (handled in payroll_edit_form.php)

        // Event listeners - wrapped in DOM-ready to prevent errors
        $(document).ready(function() {
            const calculateBtn = document.getElementById('calculateBtn');
            if (calculateBtn) {
                calculateBtn.addEventListener('click', calculatePayroll);
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
                    const daysPresent = parseInt(document.getElementById('daysPresent').value) || 22;
                    if (daysPresent > workingDays) {
                        document.getElementById('daysPresent').value = workingDays;
                    }
                    calculatePayroll();
                });
            }

            const daysPresentInput = document.getElementById('daysPresent');
            if (daysPresentInput) {
                daysPresentInput.addEventListener('input', function() {
                    const workingDays = parseInt(document.getElementById('workingDays').value) || 22;
                    const daysPresent = parseInt(this.value) || 0;
                    if (daysPresent > workingDays) {
                        this.value = workingDays;
                    }
                    calculatePayroll();
                });
            }
        });

        // Form submission - wrapped in DOM-ready to prevent errors
        $(document).ready(function() {
            // CRITICAL FIX: Use jQuery event binding to prevent default form submission
            // Remove any existing submit handlers first
            $('#payrollForm').off('submit');
            
            // Bind new submit handler
            $('#payrollForm').on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                console.log('Form submit event fired!'); // Debug log

                // ========================================
                // TASK 9.2: AJAX Validation Before Submission
                // ========================================

                // Validate all fields first
                let hasClientErrors = false;
                Object.keys(validationRules).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        hasClientErrors = true;
                    }
                });

                if (hasClientErrors) {
                    showAjaxModal_alert('Please fix the highlighted errors before submitting.', 'Error', false, true);
                    return false;
                    }

                    // Show processing modal
                    showAjaxModal_alert('Please wait while we verify the information.', 'Loading', false, false);

                    const staffIds = $('#staffId').val();

                    if(staffIds.length < 1) {
                        $('#modal_alert').modal('hide'); // Close previous modal
                        showAjaxModal_alert('No staff selected', 'Error', false, true);
                        return false;
                    }

                    // Server-side AJAX validation
                    // Parse staff ID to extract employee_code and employment_category
                    const lastSelected = staffIds[staffIds.length - 1];
                    const parts = lastSelected.split('_');
                    
                    let employmentCategory = '';
                    let employeeCode = '';
                    
                    if (parts[0] === 'teacher') {
                        employmentCategory = 'teacher';
                        employeeCode = parts.slice(1).join('_');
                    } else if (parts[0] === 'admin') {
                        employmentCategory = 'administrator';
                        employeeCode = parts.slice(1).join('_');
                    } else if (parts[0] === 'non' && parts[1] === 'teaching' && parts[2] === 'staff') {
                        employmentCategory = 'non_teaching_staff';
                        employeeCode = parts.slice(3).join('_');
                    }
                    
                    const validationData = {
                        employee_code: employeeCode,
                        employment_category: employmentCategory,
                        year: $('#payrollMonth').val().split('-')[0],
                        month: $('#payrollMonth').val().split('-')[1],
                        basic_salary: $('#basicSalary').val(),
                        market_premium_allowance: $('#marketPremium').val(),
                        teaching_allowance: $('#teachingAllowance').val(),
                        responsibility_allowance: $('#responsibilityAllowance').val(),
                        extra_class_allowance: $('#extraClasses').val(),
                        rural_allowance: $('#ruralAllowance').val(),
                        other_allowances: $('#otherAllowances').val(),
                        ssnit: $('#ssnit').val(),
                        income_tax: $('#incomeTax').val(),
                        petra: $('#petra').val(),
                        get_fund: $('#getfund').val(),
                        salary_advance: $('#salaryAdvance').val(),
                        nhil: $('#nhil').val(),
                        loan: $('#loans').val(),
                        welfare_dues: $('#welfare').val(),
                        gnat_dues: $('#gnat').val(),
                        other_deductions: $('#otherDeductions').val(),
                        working_days: $('#workingDays').val(),
                        days_present: $('#daysPresent').val(),
                        days_absent: $('#daysAbsent').val(),
                        total_allowances: $('#totalAllowancesForDb').val(),
                        total_deductions: $('#totalDeductionsForDb').val(),
                        gross_salary: $('#grossSalaryForDb').val(),
                        net_salary: $('#netSalaryForDb').val()
                    };

                    // Call server-side validation endpoint
                    $.ajax({
                        url: '<?= base_url() ?>admin/payroll/validate',
                        type: 'POST',
                        dataType: 'json',
                        data: validationData,
                        success: function(response) {
                            if (response.valid) {
                                // Validation passed, check for duplicates before submission
                                $('#modal_alert').modal('hide'); // Close validation modal
                                checkForDuplicatePayroll();
                            } else {
                                // Show validation errors
                                $('#modal_alert').modal('hide'); // Close validation modal
                                displayServerValidationErrors(response.errors);
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#modal_alert').modal('hide'); // Close validation modal
                            showAjaxModal_alert('Unable to validate payroll data. Please try again.', 'Error', false, true);
                        }
                    });
                    
                    return false;
                });
        });

        // Display server validation errors
        function displayServerValidationErrors(errors) {
            let errorHtml = '<div class="space-y-2">';
            Object.keys(errors).forEach(field => {
                errorHtml += `<div class="text-sm text-red-600">• ${errors[field]}</div>`;
                // Highlight field if it exists
                const fieldElement = document.getElementById(field);
                if (fieldElement) {
                    fieldElement.classList.add('border-red-500', 'bg-red-50');
                    showFieldError(field, errors[field]);
                }
            });
            errorHtml += '</div>';

            showAjaxModal_alert(errorHtml, 'Error', false, true);
        }

        // ========================================
        // TASK 12.2: Check for Duplicate Payroll
        // ========================================
        function checkForDuplicatePayroll() {
            showAjaxModal_alert('Please wait while we verify this is not a duplicate.', 'Loading', false, false);

            const staffIds = $('#staffId').val();
            const employee_code = staffIds[staffIds.length - 1]; // Last selected staff
            const monthYear = $('#payrollMonth').val().split('-');
            
            $.ajax({
                url: '<?= base_url() ?>admin/payroll_check_duplicate',
                type: 'POST',
                dataType: 'json',
                data: {
                    employee_code: employee_code,
                    year: monthYear[0],
                    month: monthYear[1]
                },
                success: function(response) {
                    if (response.status === 'duplicate_found' && response.exists) {
                        // Duplicate found - show warning modal
                        $('#modal_alert').modal('hide'); // Close checking modal
                        showDuplicateWarningModal(response.record);
                    } else {
                        // No duplicate - show confirmation modal with payroll details before submission
                        $('#modal_alert').modal('hide'); // Close checking modal
                        showPayrollConfirmationModal();
                    }
                },
                error: function(xhr, status, error) {
                    $('#modal_alert').modal('hide'); // Close checking modal
                    showAjaxModal_alert('Unable to check for duplicate payments. Please try again.', 'Error', false, true);
                }
            });
        }

        // ========================================
        // TASK 12.2: Show Duplicate Warning Modal
        // Requirements: 25.1 (Requirement 26.2-26.4)
        // ========================================
        function showDuplicateWarningModal(existingRecord) {
            const formattedDate = existingRecord.created_at ? new Date(existingRecord.created_at).toLocaleDateString() : 'Unknown';
            const formattedAmount = new Intl.NumberFormat('en-GH', {
                style: 'currency',
                currency: 'GHS'
            }).format(existingRecord.net_salary);
            
            const statusBadge = existingRecord.status === 'Paid' ? 'Paid' : 'Pending';
            
            const message = `A payroll record already exists for ${existingRecord.staff_name} (${existingRecord.employee_code}) for ${existingRecord.month} ${existingRecord.year}.<br><br>` +
                            `<strong>Existing Record Details:</strong><br>` +
                            `Date: ${formattedDate}<br>` +
                            `Net Amount: ${formattedAmount}<br>` +
                            `Status: ${statusBadge}<br><br>` +
                            `<strong>Do you want to overwrite this existing payment?</strong><br>` +
                            `(This action will delete the existing record and allow you to create a new one)`;
            
            showCustomConfirm(
                message,
                function() {
                    // On Yes - overwrite
                    overwriteExistingPayroll(existingRecord.pay_id);
                },
                function() {
                    // On No - just close, stay on current page
                    $('#modal_confirm').modal('hide');
                }
            );
        }

        // ========================================
        // TASK 12.3: Overwrite Existing Payroll
        // Requirements: 25.1 (Requirement 26.5-26.6)
        // ========================================
        function overwriteExistingPayroll(payId) {
            // Close any existing modal
            $('#modal_confirm').modal('hide');
            $('#modal_alert').modal('hide');
        }

        // Execute the overwrite operation after confirmation
        function executeOverwrite(payId) {
            // Show processing message
            showAjaxModal_alert('Deleting existing record...', 'Loading', false, false);
            
            // Make AJAX request to overwrite endpoint
            $.ajax({
                url: '<?php echo site_url('admin/payroll_overwrite_existing'); ?>',
                type: 'POST',
                data: {
                    pay_id: payId,
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Close modal
                        $('#modal_alert').modal('hide');
                        
                        // Show success message
                        showAjaxModal_alert('Record deleted successfully. You can now submit the new payroll data.', 'Success', false, true);
                        
                        // Log the overwrite action for debugging
                        console.log('Payroll overwrite completed:', response.deleted_record);
                    } else {
                        // Show error message
                        $('#modal_alert').modal('hide');
                        showAjaxModal_alert(response.message || 'Overwrite failed', 'Error', false, true);
                    }
                },
                error: function(xhr, status, error) {
                    $('#modal_alert').modal('hide');
                    showAjaxModal_alert('Failed to communicate with the server. Please check your connection and try again.', 'Error', false, true);
                }
            });
        }

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

        // Submit payroll data after validation
        function submitPayrollData() {
            // Show loading overlay (Task 17.1)
            showPayrollLoadingOverlay('Processing payroll payment...');
            
            showAjaxModal_alert('Please wait whilst we process your payments.', 'Loading', false, false);

            const staffIds = $('#staffId').val();
            const url = $('#payrollForm').attr('action');
            const formData = new FormData(document.getElementById('payrollForm'));
            formData.append("staffData", staffIds);
            
            // Extract employee_code and employment_category from selected staff
            // staffIds format: ["teacher_001"] or ["admin_002"] or ["non_teaching_staff_003"]
            if (staffIds && staffIds.length > 0) {
                const lastSelected = staffIds[staffIds.length - 1]; // Get last selected staff
                const parts = lastSelected.split('_');
                
                // Parse the staff selection format
                let employmentCategory = '';
                let employeeCode = '';
                
                if (parts[0] === 'teacher') {
                    employmentCategory = 'teacher';
                    employeeCode = parts.slice(1).join('_'); // Rejoin in case code has underscores
                } else if (parts[0] === 'admin') {
                    employmentCategory = 'administrator';
                    employeeCode = parts.slice(1).join('_');
                } else if (parts[0] === 'non' && parts[1] === 'teaching' && parts[2] === 'staff') {
                    employmentCategory = 'non_teaching_staff';
                    employeeCode = parts.slice(3).join('_');
                }
                
                // Add to form data
                formData.append("employee_code", employeeCode);
                formData.append("employment_category", employmentCategory);
            }
            
            // Set submit button to loading state (Task 17.5)
            const submitBtn = document.querySelector('#payrollForm button[type="submit"]');
            if (submitBtn) {
                setButtonLoading(submitBtn);
            }

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
                // Hide loading overlay
                hidePayrollLoadingOverlay();
                if (submitBtn) {
                    removeButtonLoading(submitBtn);
                }

                $('#modal_alert').modal('hide'); // Close processing modal

                // Parse JSON response if it's a string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        // If parsing fails, treat as old format
                        if(response == 'success') {
                            // Flash success animation (Task 17.3)
                            flashSuccess('payrollFormHolder');
                            
                            showAjaxModal_alert('Staff\'s salary has been processed and paid.', 'Success', true, true);

                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                            return;
                        }
                    }
                }

                // Handle JSON response format
                if(response.success === true || response == 'success') {
                    // Flash success animation (Task 17.3)
                    flashSuccess('payrollFormHolder');
                    
                    showAjaxModal_alert(response.message || 'Staff\'s salary has been processed and paid.', 'Success', true, true);

                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Shake error animation (Task 17.4)
                    shakeError('payrollFormHolder');
                    
                    showAjaxModal_alert(response.message || 'Unable to process this payment. Please try again later.', 'Error', false, true);
                }
            })
            .fail(function(err) {
                // Hide loading overlay
                hidePayrollLoadingOverlay();
                if (submitBtn) {
                    removeButtonLoading(submitBtn);
                }
                
                // Shake error animation (Task 17.4)
                shakeError('payrollFormHolder');
                
                $('#modal_alert').modal('hide'); // Close processing modal
                showAjaxModal_alert(err.responseText || 'An error occurred while processing payment.', 'Error', false, true);
            })
        }


        // Initialize
        calculatePayroll();

        // ========================================
        // TASK 9.1: Field Validation with Visual Feedback
        // ========================================
        
        // Field validation rules
        const validationRules = {
            basicSalary: { min: 0, required: true, message: 'Basic salary must be a positive number' },
            marketPremium: { min: 0, required: false, message: 'Market premium must be a positive number' },
            teachingAllowance: { min: 0, required: false, message: 'Teaching allowance must be a positive number' },
            responsibilityAllowance: { min: 0, required: false, message: 'Responsibility allowance must be a positive number' },
            extraClasses: { min: 0, required: false, message: 'Extra classes must be a positive number' },
            ruralAllowance: { min: 0, required: false, message: 'Rural allowance must be a positive number' },
            otherAllowances: { min: 0, required: false, message: 'Other allowances must be a positive number' },
            ssnit: { min: 0, required: false, message: 'SSNIT must be a positive number' },
            incomeTax: { min: 0, required: false, message: 'Income tax must be a positive number' },
            petra: { min: 0, required: false, message: 'SSNIT Tier 2 must be a positive number' },
            getfund: { min: 0, required: false, message: 'GETFund must be a positive number' },
            salaryAdvance: { min: 0, required: false, message: 'Salary advance must be a positive number' },
            nhil: { min: 0, required: false, message: 'NHIL must be a positive number' },
            welfare: { min: 0, required: false, message: 'Welfare deductions must be a positive number' },
            gnat: { min: 0, required: false, message: 'GNAT dues must be a positive number' },
            loans: { min: 0, required: false, message: 'Loan deductions must be a positive number' },
            otherDeductions: { min: 0, required: false, message: 'Other deductions must be a positive number' },
            workingDays: { min: 1, max: 31, required: true, message: 'Working days must be between 1 and 31' },
            daysPresent: { min: 0, max: 31, required: true, message: 'Days present must be between 0 and 31' }
        };

        // Validate single field
        function validateField(fieldId) {
            const field = document.getElementById(fieldId);
            const rule = validationRules[fieldId];
            
            if (!field || !rule) return true;

            const value = parseFloat(field.value) || 0;
            let isValid = true;
            let errorMessage = '';

            // Check required
            if (rule.required && field.value.trim() === '') {
                isValid = false;
                errorMessage = 'This field is required';
            }
            // Check minimum
            else if (rule.min !== undefined && value < rule.min) {
                isValid = false;
                errorMessage = rule.message;
            }
            // Check maximum
            else if (rule.max !== undefined && value > rule.max) {
                isValid = false;
                errorMessage = `Value cannot exceed ${rule.max}`;
            }

            // Visual feedback
            if (isValid) {
                field.classList.remove('border-red-500', 'bg-red-50');
                field.classList.add('border-green-500', 'bg-green-50');
                removeFieldError(fieldId);
            } else {
                field.classList.remove('border-green-500', 'bg-green-50');
                field.classList.add('border-red-500', 'bg-red-50');
                showFieldError(fieldId, errorMessage);
            }

            return isValid;
        }

        // Show field error message
        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            let errorDiv = document.getElementById(fieldId + '_error');
            
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = fieldId + '_error';
                errorDiv.className = 'text-red-600 text-xs mt-1';
                field.parentElement.appendChild(errorDiv);
            }
            
            errorDiv.textContent = message;
        }

        // Remove field error message
        function removeFieldError(fieldId) {
            const errorDiv = document.getElementById(fieldId + '_error');
            if (errorDiv) {
                errorDiv.remove();
            }
        }

        // Attach blur event handlers to all validated fields - wrapped in DOM-ready
        $(document).ready(function() {
            Object.keys(validationRules).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', function() {
                        validateField(fieldId);
                    });
                    
                    // Remove error styling on focus
                    field.addEventListener('focus', function() {
                        this.classList.remove('border-red-500', 'bg-red-50');
                    });
                }
            });
        });

        /*show staff info*/ 
        
        $('#staffId').change(function(ev) {

            $('#modal_alert').modal('hide'); // Close any existing modal
            showAjaxModal_alert('Please wait...', 'Loading', false, false);

            // CRITICAL FIX: Use native JavaScript to get selected values instead of jQuery .val()
            // This avoids jQuery's internal DOM traversal that causes the toLowerCase error
            let staffIds = [];
            const staffIdElement = document.getElementById('staffId');
            
            if (!staffIdElement) {
                console.log('Staff select element not found');
                $('#modal_alert').modal('hide');
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
                $('#modal_alert').modal('hide');
                return;
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
                var lastStaffCode = [];

                if(staffIds.length > staffIdArray.length) {

                    lastStaffCode = $(staffIds).not(staffIdArray).get();
                    lastStaffCode = lastStaffCode[0];

                } else {
                    let arrayLength = staffIds.length;
                    lastStaffCode = staffIds[arrayLength - 1];
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

        // ========================================
        // Global Print Payslip Function
        // ========================================
        // This function must be globally accessible to work with dynamically loaded buttons
        function printPayslip(staffCode, month, year, employmentCategory) {
            // Build the URL
            const baseUrl = '<?php echo site_url("admin/payslip_preview"); ?>';
            const url = `${baseUrl}/${staffCode}/${month}/${year}/${employmentCategory}`;
            
            // Open in new window
            window.open(url, '_blank');
        }

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

                    // Don't reload - preserve the selected month and show a message instead
                    showAjaxModal_alert('Please select a staff member first before changing the payroll month.', 'Warning', false, true);
                    console.log('No staff selected for payroll month: ' + payrollMonth);

                } else {

                    $('#payrollFormHolder').html(response);
                    $('#modal_alert').modal('hide'); // Close loading modal
                    
                    // Reinitialize calculator after DOM update
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
                $('#modal_alert').modal('hide'); // Close loading modal
            })

        }
        
        // ========================================
        // Check for Duplicate Payroll on Staff/Month Selection
        // ========================================
        function checkForDuplicatePayrollOnLoad(staff_code, payrollMonth) {
            // BUGFIX: Trim prefix from staff_code by finding the LAST underscore
            // Examples:
            // - admin_ADM-1 → ADM-1
            // - teacher_KISPECSN-001 → KISPECSN-001
            // - non_teaching_staff_NTS-00001 → NTS-00001
            let trimmedStaffCode = staff_code;
            if (staff_code && staff_code.includes('_')) {
                const lastUnderscoreIndex = staff_code.lastIndexOf('_');
                if (lastUnderscoreIndex !== -1) {
                    trimmedStaffCode = staff_code.substring(lastUnderscoreIndex + 1); // Take everything after the LAST underscore
                }
            }
            
            const monthYear = payrollMonth.split('-');
            
            $.ajax({
                url: '<?= base_url() ?>admin/payroll_check_duplicate',
                type: 'POST',
                dataType: 'json',
                data: {
                    employee_code: trimmedStaffCode,
                    year: monthYear[0],
                    month: monthYear[1]
                },
                success: function(response) {
                    // BUGFIX: Check 'success' field - false means duplicate found, true means no duplicate
                    if (response.success === false && response.status === 'duplicate_found' && response.exists) {
                        // Duplicate found - show warning modal
                        showDuplicateWarningModalOnLoad(response.record);
                    }

                    console.log('Duplicate check: success=' + response.success + ' / status=' + response.status + ' / exists=' + response.exists);
                    // If no duplicate (success=true), do nothing and let user proceed
                },
                error: function(xhr, status, error) {
                    // Silently fail - don't interrupt user workflow
                    console.error('Duplicate check failed:', error);
                }
            });
        }
        
        // ========================================
        // Show Duplicate Warning Modal (On Load) - NOTIFICATION ONLY
        // ========================================
        function showDuplicateWarningModalOnLoad(existingRecord) {
            const formattedDate = existingRecord.created_at ? new Date(existingRecord.created_at).toLocaleDateString() : 'Unknown';
            const formattedAmount = new Intl.NumberFormat('en-GH', {
                style: 'currency',
                currency: 'GHS'
            }).format(existingRecord.net_salary);
            
            const statusBadge = existingRecord.status === 'Paid' ? '<span style="color: green;">Paid</span>' : '<span style="color: orange;">Pending</span>';
            
            const message = `<div style="text-align: left;">` +
                            `<p style="margin-bottom: 15px;"><strong>⚠️ Duplicate Payroll Detected</strong></p>` +
                            `<p>A payroll record already exists for <strong>${existingRecord.staff_name}</strong> (${existingRecord.employee_code}) for <strong>${existingRecord.month} ${existingRecord.year}</strong>.</p><br>` +
                            `<div style="background: #f8f9fa; padding: 10px; border-left: 3px solid #007bff; margin-bottom: 15px;">` +
                            `<strong>Existing Record Details:</strong><br>` +
                            `📅 Date: ${formattedDate}<br>` +
                            `💰 Net Amount: ${formattedAmount}<br>` +
                            `📊 Status: ${statusBadge}` +
                            `</div>` +
                            `<p style="color: #0c5460; font-size: 0.95em;"><strong>ℹ️ Note:</strong> Please review the existing record before creating a new one.</p>` +
                            `</div>`;
            
            // Use custom wide modal for duplicate warning
            $('#duplicateWarningContent').html(message);
            $('#modal_duplicate_payroll_warning').modal({backdrop: 'static', keyboard: false});
        }

        
    </script>

    <script src="<?php echo base_url('assets/js/select2/select2.min.js');?>"></script>
    
    <!-- Payroll Modernization JavaScript Libraries -->
    <script src="<?php echo base_url('assets/js/payroll_toast.js');?>" type="text/javascript"></script>
    <!-- payroll_realtime_calculator.js is loaded in <head> at line 29 - duplicate removed to fix "Identifier already declared" error -->
    
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize Select2 for staff selection
            $('.select2').select2({
                placeholder: 'Select staff member(s)',
                allowClear: true,
                width: '100%'
            });
            
            // Update Tier 2 label when provider is selected
            $('#tier2Provider').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const providerName = selectedOption.text();
                
                if (providerName && providerName !== 'Not Set') {
                    $('#tier2Label').text('SSNIT Tier 2 (' + STATUTORY_RATES.ssnit_tier2 + '%) - ' + providerName + ' (GH₵)');
                } else {
                    $('#tier2Label').text('SSNIT Tier 2 (' + STATUTORY_RATES.ssnit_tier2 + '%) - Not Set (GH₵)');
                }
            });
            
            // Test toast notification (optional - can be removed after testing)
            // window.toast.info('Payroll system loaded successfully');
        });
    </script>

    <!-- Custom Duplicate Payroll Warning Modal (Wider for better text display) -->
    <div class="modal fade" id="modal_duplicate_payroll_warning" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" style="margin-top:15vh; max-width: 700px;">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border: none; padding: 24px 30px; position: relative;">
                    <button class="close" type="button" data-dismiss="modal" style="color: white !important; opacity: 0.9 !important; font-size: 32px !important; font-weight: 300 !important; text-shadow: none !important; transition: all 0.3s ease !important; position: absolute !important; right: 20px !important; top: 20px !important; z-index: 10 !important;">&times;</button>
                </div>
                <div class="modal-body" style="padding:30px 40px; text-align:left;">
                    <div style="text-align:center; margin-bottom: 20px;">
                        <div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#f39c1215 0%,#e67e2215 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i class="fa fa-exclamation-triangle" style="font-size:50px;color:#f39c12;"></i>
                        </div>
                    </div>
                    <div id="duplicateWarningContent"></div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:15px 30px;background:#f8f9fa;text-align:center;">
                    <button type="button" class="btn" data-dismiss="modal" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; border: 2px solid #bdc3c7; background: white; color: #7f8c8d; min-width:120px;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Customizer Modal - Ultra Compact Version -->
    <div id="payrollFormCustomizerModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7);">
        <div style="background-color: #fefefe; margin: 3% auto; padding: 0; border: none; border-radius: 10px; width: 90%; max-width: 550px; box-shadow: 0 15px 40px rgba(0,0,0,0.35); max-height: 85vh; display: flex; flex-direction: column;">
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 12px 16px; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: white; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                    <i class="fa fa-sliders"></i> Customize Fields
                </h3>
                <button onclick="closePayrollFormCustomizer()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 16px;">&times;</button>
            </div>
            <div style="display: flex; gap: 6px; align-items: center; padding: 10px 14px; border-bottom: 1px solid #e5e7eb;">
                <input type="text" id="payrollFieldSearchInput" placeholder="Search..." onkeyup="filterPayrollFields()" style="flex: 1; padding: 6px 10px; border: 1.5px solid #d1d5db; background: white; border-radius: 5px; font-size: 12px;">
                <button onclick="resetPayrollFormCustomization()" style="padding: 6px 12px; border: 1.5px solid #d1d5db; background: white; color: #374151; border-radius: 5px; font-weight: 500; cursor: pointer; font-size: 12px; white-space: nowrap;">
                    <i class="fa fa-undo"></i> Reset
                </button>
            </div>
            <div style="padding: 14px; overflow-y: auto; flex: 1;">
                <p style="color: #6b7280; margin-bottom: 12px; font-size: 11px;"><i class="fa fa-info-circle"></i> Toggle to show/hide. Required fields can't be hidden.</p>
                <div id="payrollFieldToggles" style="display: grid; gap: 10px;"></div>
            </div>
        </div>
    </div>

<style>
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 42px;
    height: 22px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 22px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #3b82f6;
}

input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<script>
const payrollFormFields = [
    {name: 'payrollMonth', label: 'Month & Year', required: true, section: 'Basic Information'},
    {name: 'basicSalary', label: 'Basic Salary', required: true, section: 'Earnings'},
    {name: 'marketPremium', label: 'Market Premium', required: false, section: 'Earnings'},
    {name: 'teachingAllowance', label: 'Teaching Allowance', required: false, section: 'Earnings'},
    {name: 'responsibilityAllowance', label: 'Responsibility Allowance', required: false, section: 'Earnings'},
    {name: 'extraClasses', label: 'Extra Classes', required: false, section: 'Earnings'},
    {name: 'ruralAllowance', label: 'Rural Allowance', required: false, section: 'Earnings'},
    {name: 'otherAllowances', label: 'Other Allowances', required: false, section: 'Earnings'},
    {name: 'petra', label: 'SSNIT Tier 2', required: false, section: 'Employee Deductions'},
    {name: 'incomeTax', label: 'Income Tax - PAYE', required: false, section: 'Employee Deductions'},
    {name: 'salaryAdvance', label: 'Salary Advance', required: false, section: 'Employee Deductions'},
    {name: 'loans', label: 'Loan Deductions', required: false, section: 'Employee Deductions'},
    {name: 'welfare', label: 'Welfare Deductions', required: false, section: 'Employee Deductions'},
    {name: 'gnat', label: 'GNAT Dues', required: false, section: 'Employee Deductions'},
    {name: 'otherDeductions', label: 'Other Deductions', required: false, section: 'Employee Deductions'}
];

function openPayrollFormCustomizer() {
    const modal = document.getElementById('payrollFormCustomizerModal');
    const container = document.getElementById('payrollFieldToggles');
    
    // Show loading indicator
    container.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fa fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i><p style="margin-top: 10px; color: #6b7280;">Loading preferences...</p></div>';
    modal.style.display = 'block';
    
    // Load preferences from database
    $.ajax({
        url: '<?php echo site_url("admin/get_payroll_field_preferences"); ?>',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                populateFieldToggles(response.preferences);
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><i class="fa fa-exclamation-triangle"></i><p>Failed to load preferences</p></div>';
            }
        },
        error: function() {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><i class="fa fa-exclamation-triangle"></i><p>Network error loading preferences</p></div>';
        }
    });
}

function populateFieldToggles(preferences) {
    const container = document.getElementById('payrollFieldToggles');
    
    // Build preference map for quick lookup
    const prefsMap = {};
    preferences.forEach(pref => {
        prefsMap[pref.field_name] = pref.is_visible === '1' || pref.is_visible === 1;
    });
    
    let sections = {};
    payrollFormFields.forEach(field => {
        if(!sections[field.section]) sections[field.section] = [];
        sections[field.section].push(field);
    });
    
    let html = '';
    Object.keys(sections).forEach(section => {
        html += `<div style="background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">`;
        html += `<h4 style="margin: 0 0 10px 0; color: #374151; font-size: 13px; font-weight: 700;"><i class="fa fa-folder-open" style="font-size: 11px;"></i> ${section}</h4>`;
        sections[section].forEach(field => {
            // Default to visible if no preference exists
            const isVisible = prefsMap[field.name] !== undefined ? prefsMap[field.name] : true;
            const disabled = field.required ? 'disabled' : '';
            const opacity = field.required ? '0.5' : '1';
            html += `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: white; border-radius: 6px; margin-bottom: 6px; opacity: ${opacity};">`;
            html += `<div style="flex: 1;"><span style="font-weight: 600; color: #1f2937; font-size: 12px;">${field.label}</span>${field.required ? ' <span style="color: #ef4444; font-size: 10px;">(Required)</span>' : ''}</div>`;
            html += `<label class="toggle-switch"><input type="checkbox" ${isVisible ? 'checked' : ''} ${disabled} onchange="togglePayrollField('${field.name}', this.checked)"><span class="toggle-slider"></span></label>`;
            html += `</div>`;
        });
        html += `</div>`;
    });
    
    container.innerHTML = html;
}

function closePayrollFormCustomizer() {
    document.getElementById('payrollFormCustomizerModal').style.display = 'none';
    // Reload page to apply field visibility changes
    window.location.reload();
}

function filterPayrollFields() {
    const searchTerm = document.getElementById('payrollFieldSearchInput').value.toLowerCase();
    const sections = document.querySelectorAll('#payrollFieldToggles > div');
    
    sections.forEach(section => {
        const fields = section.querySelectorAll('div[style*="display: flex"]');
        let visibleCount = 0;
        
        fields.forEach(field => {
            const text = field.textContent.toLowerCase();
            if(text.includes(searchTerm)) {
                field.style.display = 'flex';
                visibleCount++;
            } else {
                field.style.display = 'none';
            }
        });
        
        section.style.display = visibleCount > 0 ? '' : 'none';
    });
}

function togglePayrollField(fieldName, isVisible) {
    // Save to database
    $.ajax({
        url: '<?php echo site_url("admin/save_payroll_field_preference"); ?>',
        method: 'POST',
        data: {
            fieldName: fieldName,
            isVisible: isVisible ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Show success toast
                showSuccessToast(fieldName + ' visibility updated');
                
                // Apply visibility change to form
                applyFieldVisibility(fieldName, isVisible);
            } else {
                // Revert toggle state
                revertToggleState(fieldName);
                showErrorToast(response.message || 'Failed to save preference');
            }
        },
        error: function(xhr, status, error) {
            // Revert toggle state
            revertToggleState(fieldName);
            showErrorToast('Failed to save preference');
            console.error('Save error:', error);
        }
    });
}

function applyFieldVisibility(fieldName, isVisible) {
    // Find all field wrappers with matching data-field attribute across all forms
    const fieldWrappers = document.querySelectorAll(`.payroll-field-wrapper[data-field="${fieldName}"]`);
    
    if (fieldWrappers.length > 0) {
        fieldWrappers.forEach(wrapper => {
            if (isVisible) {
                wrapper.style.display = '';
                // Re-enable input if it exists
                const input = wrapper.querySelector('input, select, textarea');
                if (input && input.name === fieldName) {
                    input.removeAttribute('disabled');
                }
            } else {
                wrapper.style.display = 'none';
                // Disable input to prevent submission
                const input = wrapper.querySelector('input, select, textarea');
                if (input && input.name === fieldName) {
                    input.setAttribute('disabled', 'disabled');
                }
            }
        });
        
        console.log(`Applied visibility for ${fieldName}: ${isVisible ? 'visible' : 'hidden'} (${fieldWrappers.length} instances)`);
    } else {
        console.warn(`No field wrapper found for: ${fieldName}`);
    }
}

function revertToggleState(fieldName) {
    const toggle = document.querySelector(`input[onchange*="${fieldName}"]`);
    if (toggle) {
        toggle.checked = !toggle.checked;
    }
}

function resetPayrollFormCustomization() {
    // Use the modern confirm modal from modal/confirm_modal.php
    showConfirmModal(
        'Reset Form Preferences',
        'Reset all field visibility preferences to default? This will show all fields.',
        function() {
            // User clicked Confirm - proceed with reset
            executeResetPayrollFormCustomization();
        },
        'Reset',
        'warning'
    );
}

function executeResetPayrollFormCustomization() {
    
    $.ajax({
        url: '<?php echo site_url("admin/reset_payroll_field_preferences"); ?>',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Check all toggles
                const toggles = document.querySelectorAll('#payrollFieldToggles input[type="checkbox"]');
                toggles.forEach(toggle => {
                    if (!toggle.disabled) {
                        toggle.checked = true;
                    }
                });
                
                // Show all fields
                payrollFormFields.forEach(field => {
                    applyFieldVisibility(field.name, true);
                });
                
                showSuccessToast('All preferences reset to default');
                closePayrollFormCustomizer();
            } else {
                showErrorToast('Reset failed');
            }
        },
        error: function() {
            showErrorToast('Network error during reset');
        }
    });
}

// Apply saved preferences on page load
$(document).ready(function() {
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
                
                // Apply each saved preference
                payrollFormFields.forEach(field => {
                    if (prefsMap[field.name] !== undefined && !field.required) {
                        applyFieldVisibility(field.name, prefsMap[field.name]);
                    }
                });
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
</script>

    <?php 
    // Include global modal system for alert/confirm functions
    // This provides: showAjaxModal_alert(), showCustomConfirm(), #modal_alert, #modal_confirm
    include(APPPATH.'views/backend/modal.php'); 
    ?>
</body>
</html>
