<!DOCTYPE html>
<?php
    /*Data arriving here*/
    /*
    *$staffCode
    *$payMonth
    *$payYear
    *$employmentCategory == $table
    */
    $table = $employmentCategory;

    /*School information*/
    $schoolName = ucwords(strtolower(get_settings('system_name')));
    $schoolTagline = ucwords(strtolower(get_settings('system_title')));
    $schoolAddress = ucwords(strtolower(get_settings('address')));
    $schoolPhone = get_settings('phone');
    $schoolEmail = strtolower(get_settings('system_email'));
    $schoolBox = get_settings('box_number');
    $schoolLocation = ucwords(strtolower(get_settings('location')));
    $schoolDigitalAddress = get_settings('digital_address');
    $schoolWebsite = strtolower(get_settings('website_address'));

    /*Staff information*/
    $staffRow = $this->crud_model->getStaffInfo($table, $staffCode);

    // Validation for missing staff record
    if (empty($staffRow)) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Staff Not Found</title>';
        echo '<link rel="stylesheet" href="' . base_url('assets/tailwindcss/output.css') . '"></head><body>';
        echo '<div class="flex items-center justify-center min-h-screen bg-gray-100">';
        echo '<div class="bg-white p-8 rounded-lg shadow-lg text-center">';
        echo '<i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>';
        echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">Staff Information Not Found</h2>';
        echo '<p class="text-gray-600 mb-6">The staff record for code "' . htmlspecialchars($staffCode) . '" could not be found.</p>';
        echo '<a href="' . base_url('admin/payroll') . '" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">';
        echo '<i class="fas fa-arrow-left mr-2"></i>Go Back to Payroll</a>';
        echo '</div></div></body></html>';
        exit;
    }

    $staffName = ucwords(strtolower($staffRow->name));
    $staffSex = $staffRow->sex ?? '';
    $staffAddress = $staffRow->address ?? '';
    $staffPhone = $staffRow->phone ?? '';
    $staffQualification = $staffRow->designation ?? '';
    $staffAccountNumber = $staffRow->account_number ?? '';
    $staffAccountDetails = $staffRow->account_details ?? '';
    
    // Set staff category based on employment category
    $staffCategory = 'Staff';
    if($employmentCategory == 'teacher') {
        $staffCategory = 'Teacher';
    } else if($employmentCategory == 'administrator') {
        $staffCategory = 'Administration';
    } else if($employmentCategory == 'non_teaching_staff') {
        $staffCategory = 'Non-Teaching Staff';
    }

    /*Currency settings*/
    $currency = get_settings('currency') ?: 'GH₵';

    /*Helper function for conditional display - hides zero/null values*/
    if (!function_exists('shouldDisplayLine')) {
        function shouldDisplayLine($value) {
            // Handle NULL values by treating as zero
            $numericValue = $value ?? 0;
            
            // Check if value > 0
            return floatval($numericValue) > 0;
        }
    }

    /*Helper function for currency formatting*/
    if (!function_exists('formatCurrency')) {
        function formatCurrency($amount, $currencySymbol = null) {
            // Retrieve currency symbol from system settings
            if ($currencySymbol === null) {
                $currencySymbol = get_settings('currency');
            }
            
            // Apply fallback to 'GH₵' if currency setting is missing
            if (empty($currencySymbol)) {
                $currencySymbol = 'GH₵';
            }
            
            // Format amounts with 2 decimal places and thousand separators
            return $currencySymbol . ' ' . number_format($amount, 2, '.', ',');
        }
    }
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=210mm, initial-scale=1.0">
    <title><?= ucfirst($schoolName) ?> - <?= $staffName;?> - Payslip <?= $payMonth. ' '. $payYear;?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url('assets/fontawesome/6.7.2/css/all.min.css');?>">
    
    <!-- jQuery -->
    <script src="<?php echo base_url('assets/js/jquery-3.4.1.js');?>" type="text/javascript"></script>

    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
        }

        /* Screen preview container */
        .payslip-container {
            width: 210mm;
            height: 148mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 8mm;
            box-sizing: border-box;
        }

        /* Print media styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .payslip-container {
                width: 100% !important;
                height: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 8mm !important;
                box-shadow: none !important;
                page-break-after: avoid;
            }
        }

        /* Screen view specific */
        @media screen {
            body {
                background-color: #f0f2f5;
                padding: 20px 0;
            }
        }

        /* Header Styling */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-container {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #2563eb;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .school-info h1 {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 2px;
        }

        .school-info h2 {
            font-size: 10pt;
            color: #475569;
            margin-bottom: 1px;
        }

        .school-info p {
            font-size: 8pt;
            color: #64748b;
        }

        .header-right {
            text-align: right;
        }

        .payslip-title {
            font-size: 16pt;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 4px;
        }

        .pay-period {
            font-size: 10pt;
            color: #475569;
            margin-bottom: 2px;
        }

        .reference {
            font-size: 8pt;
            color: #64748b;
        }

        /* Employee Info Section */
        .info-section {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 4px;
        }

        .info-column {
            flex: 1;
        }

        .info-column h3 {
            font-size: 10pt;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 8.5pt;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            color: #1e293b;
            font-weight: 600;
        }

        /* Earnings and Deductions Section */
        .salary-section {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .earnings-column, .deductions-column {
            flex: 1;
        }

        .section-header {
            font-size: 11pt;
            font-weight: bold;
            padding: 5px 8px;
            margin-bottom: 6px;
            border-radius: 3px;
        }

        .earnings-header {
            background: #dcfce7;
            color: #15803d;
            border-left: 4px solid #16a34a;
        }

        .deductions-header {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
        }

        .line-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8.5pt;
        }

        .line-item:last-child {
            border-bottom: none;
        }

        .line-item.thick-border {
            border-bottom: 3px solid #475569;
        }

        .line-item.ssnit-tier1-separator {
            border-bottom: 2px solid #475569;
        }

        .item-label {
            color: #475569;
        }

        .item-value {
            font-weight: 600;
            color: #1e293b;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 8px;
            font-weight: bold;
            margin-top: 6px;
            border-radius: 3px;
            font-size: 10pt;
        }

        .earnings-total {
            background: #16a34a;
            color: white;
        }

        .deductions-total {
            background: #dc2626;
            color: white;
        }

        /* Net Pay Section */
        .net-pay-section {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            justify-between;
            align-items: center;
        }

        .net-pay-left h3 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .net-pay-left p {
            font-size: 8pt;
            opacity: 0.9;
        }

        .net-pay-right {
            text-align: right;
        }

        .net-amount {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .bank-details {
            font-size: 8pt;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            display: flex;
            justify-content: space-between;
            padding-top: 8px;
            border-top: 2px solid #e2e8f0;
            font-size: 7.5pt;
            color: #64748b;
        }

        .footer-left ul {
            list-style: none;
            padding: 0;
        }

        .footer-left li {
            margin-bottom: 3px;
        }

        .footer-right {
            text-align: right;
        }

        /* Print Button */
        .print-button-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(37, 99, 235, 0.4);
        }

        .print-btn i {
            margin-right: 8px;
        }

        .print-instruction {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
            font-style: italic;
        }

        .print-instruction strong {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <?php
        // Safety check: Ensure staffPayrollData exists and is not empty
        if (!isset($staffPayrollData) || count($staffPayrollData) === 0) {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Payroll Data Not Found</title>';
            echo '<link rel="stylesheet" href="' . base_url('assets/tailwindcss/output.css') . '"></head><body>';
            echo '<div class="flex items-center justify-center min-h-screen bg-gray-100">';
            echo '<div class="bg-white p-8 rounded-lg shadow-lg text-center">';
            echo '<i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>';
            echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">Payroll Data Not Found</h2>';
            echo '<p class="text-gray-600 mb-6">No payroll record was found for the specified period.</p>';
            echo '<a href="' . base_url('admin/payroll') . '" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">';
            echo '<i class="fas fa-arrow-left mr-2"></i>Go Back to Payroll</a>';
            echo '</div></div></body></html>';
            exit;
        }
        
        foreach($staffPayrollData as $row):
            // Use saved rates if available (for historical accuracy), otherwise use current rates
            $use_saved_rates = isset($row->rate_ssnit_tier1_employer) && $row->rate_ssnit_tier1_employer !== null;
            
            if ($use_saved_rates) {
                // Use rates that were saved when this payroll was created
                $ssnit_tier1_employer_rate = $row->rate_ssnit_tier1_employer;
                $ssnit_tier1_employee_rate = $row->rate_ssnit_tier1_employee;
                $ssnit_tier2_rate = $row->rate_ssnit_tier2;
                $getfund_rate = $row->rate_getfund;
                $nhil_rate = $row->rate_nhil;
            } else {
                // Fallback to current rates for old payroll records (before rates were saved)
                $ssnit_tier1_employer_rate = isset($statutory_rates['ssnit_tier1_employer']['value']) ? $statutory_rates['ssnit_tier1_employer']['value'] : 13.5;
                $ssnit_tier1_employee_rate = isset($statutory_rates['ssnit_tier1_employee']['value']) ? $statutory_rates['ssnit_tier1_employee']['value'] : 5.5;
                $ssnit_tier2_rate = isset($statutory_rates['ssnit_tier2']['value']) ? $statutory_rates['ssnit_tier2']['value'] : 5.0;
                $getfund_rate = isset($statutory_rates['getfund']['value']) ? $statutory_rates['getfund']['value'] : 2.5;
                $nhil_rate = isset($statutory_rates['nhil']['value']) ? $statutory_rates['nhil']['value'] : 2.5;
            }
            
            // Prepare earnings array (only non-zero values)
            $earnings = [];
            if (shouldDisplayLine($row->basic_salary)) $earnings[] = ['label' => 'Basic Salary', 'value' => $row->basic_salary];
            if (shouldDisplayLine($row->market_premium_allowance)) $earnings[] = ['label' => 'Market Premium', 'value' => $row->market_premium_allowance];
            if (shouldDisplayLine($row->teaching_allowance)) $earnings[] = ['label' => 'Teaching Allowance', 'value' => $row->teaching_allowance];
            if (shouldDisplayLine($row->responsibility_allowance)) $earnings[] = ['label' => 'Responsibility Allowance', 'value' => $row->responsibility_allowance];
            if (shouldDisplayLine($row->extra_class_allowance)) $earnings[] = ['label' => 'Extra Classes', 'value' => $row->extra_class_allowance];
            if (shouldDisplayLine($row->rural_allowance)) $earnings[] = ['label' => 'Rural Allowance', 'value' => $row->rural_allowance];
            if (shouldDisplayLine($row->other_allowances)) $earnings[] = ['label' => 'Other Allowances', 'value' => $row->other_allowances];

            // Prepare employer contributions array (non-deductible from employee)
            $employer_contributions = [];
            // SSNIT Tier 1 - Employer contribution (13%, not deducted from employee)
            if (shouldDisplayLine($row->ssnit)) {
                $employer_contributions[] = ['label' => 'SSNIT Tier 1 - Employer Contribution (' . number_format($ssnit_tier1_employer_rate, 1) . '%)', 'value' => $row->ssnit];
            }
            
            // Prepare employee deductions array (deducted from employee earnings)
            $deductions = [];
            
            // SSNIT Tier 2 - Fetch tier2 provider name and show tier2 amount
            // Check all possible field names for backward compatibility
            $tier2Amount = 0;
            $tier2Field = null;
            
            // Priority order: tier2_contribution (new), petra (legacy), tier2_pension (alternate)
            if (isset($row->tier2_contribution) && $row->tier2_contribution !== null) {
                $tier2Amount = $row->tier2_contribution;
                $tier2Field = 'tier2_contribution';
            } elseif (isset($row->petra) && $row->petra !== null) {
                $tier2Amount = $row->petra;
                $tier2Field = 'petra';
            } elseif (isset($row->tier2_pension) && $row->tier2_pension !== null) {
                $tier2Amount = $row->tier2_pension;
                $tier2Field = 'tier2_pension';
            }
            
            // DEBUG: Temporarily show what fields exist and their values (remove after verification)
            // echo "<!-- DEBUG Tier2: Field={$tier2Field}, Amount={$tier2Amount}, Provider ID=" . ($row->tier2_provider_id ?? 'NULL') . " -->";
            
            if (shouldDisplayLine($tier2Amount)) {
                $tier2Label = 'TIER 2 PENSION';
                $tier2Sublabel = 'SSNIT Tier 2 (' . number_format($ssnit_tier2_rate, 1) . '%)';
                
                // Fetch provider company name from database
                if (isset($row->tier2_provider_id) && !empty($row->tier2_provider_id)) {
                    $providerQuery = $this->db->where('provider_id', $row->tier2_provider_id)->get('pension_tier2_providers');
                    if ($providerQuery->num_rows() > 0) {
                        $provider = $providerQuery->row();
                        if (!empty($provider->provider_name)) {
                            $tier2Label = strtoupper(trim($provider->provider_name));
                        }
                    }
                }
                
                $deductions[] = ['label' => $tier2Label, 'value' => $tier2Amount, 'sublabel' => $tier2Sublabel];
            }
            
            if (shouldDisplayLine($row->income_tax)) $deductions[] = ['label' => 'Income Tax (PAYE)', 'value' => $row->income_tax];
            if (shouldDisplayLine($row->get_fund)) $deductions[] = ['label' => 'GETFund (' . number_format($getfund_rate, 1) . '%)', 'value' => $row->get_fund];
            if (shouldDisplayLine($row->nhil)) $deductions[] = ['label' => 'NHIL (' . number_format($nhil_rate, 1) . '%)', 'value' => $row->nhil];
            if (shouldDisplayLine($row->salary_advance)) $deductions[] = ['label' => 'Salary Advance', 'value' => $row->salary_advance];
            if (shouldDisplayLine($row->loan)) $deductions[] = ['label' => 'Loan Repayment', 'value' => $row->loan];
            if (shouldDisplayLine($row->welfare_dues)) $deductions[] = ['label' => 'Welfare Dues', 'value' => $row->welfare_dues];
            if (shouldDisplayLine($row->gnat_dues)) $deductions[] = ['label' => 'GNAT Dues', 'value' => $row->gnat_dues];
            if (shouldDisplayLine($row->other_deductions)) $deductions[] = ['label' => 'Other Deductions', 'value' => $row->other_deductions];
    ?>

    <!-- Print Button (Only visible on screen and not in bulk print mode) -->
    <?php if (!isset($is_bulk_print) || !$is_bulk_print): ?>
    <div class="no-print print-button-container">
        <button onclick="window.print()" class="print-btn">
            <i class="fas fa-print"></i>Print Payslip
        </button>
        <div class="print-instruction">
            <i class="fas fa-info-circle"></i> If content extends to next page, use <strong>Scale/Zoom</strong> in print dialog (try 90-95%)
        </div>
    </div>
    <?php endif; ?>

    <!-- Payslip Container -->
    <div class="payslip-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-container">
                    <img src="<?=base_url('uploads/school_logo.png');?>" alt="School Logo">
                </div>
                <div class="school-info">
                    <h1><?= $schoolName;?></h1>
                    <h2><?= $schoolTagline;?></h2>
                    <p><?= $schoolPhone;?> | <?= $schoolEmail;?></p>
                </div>
            </div>
            <div class="header-right">
                <div class="payslip-title">PAYSLIP</div>
                <div class="pay-period">Period: <?= $payMonth . ' ' . $payYear;?></div>
                <div class="reference">Ref: <?= $row->reference;?></div>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="info-section">
            <div class="info-column">
                <h3>Employee Details</h3>
                <div class="info-row">
                    <span class="info-label">Staff ID:</span>
                    <span class="info-value"><?= $staffCode;?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?= $staffName;?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category & Phone:</span>
                    <span class="info-value"><?= $staffCategory;?> | <?= $staffPhone;?></span>
                </div>
            </div>
            <div class="info-column">
                <h3>Payment Details</h3>
                <div class="info-row">
                    <span class="info-label">Attendance:</span>
                    <span class="info-value">Working Days: <?= $row->working_days;?> | Days Present: <?= $row->days_present;?> | Days Absent: <?= $row->days_absent;?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bank/MoMo:</span>
                    <span class="info-value"><?= $staffAccountDetails; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account:</span>
                    <span class="info-value"><?= substr($staffAccountNumber, 0, 2);?>*-****-***<?= substr($staffAccountNumber, -3);?></span>
                </div>
            </div>
        </div>

        <!-- Earnings and Deductions -->
        <div class="salary-section">
            <!-- Earnings Column -->
            <div class="earnings-column">
                <div class="section-header earnings-header">
                    <i class="fas fa-plus-circle"></i> EARNINGS
                </div>
                <div>
                    <?php foreach ($earnings as $item): ?>
                        <div class="line-item">
                            <span class="item-label"><?= $item['label']; ?></span>
                            <span class="item-value"><?= formatCurrency($item['value'], $currency); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($earnings)): ?>
                        <div class="line-item">
                            <span class="item-label">No earnings recorded</span>
                            <span class="item-value">-</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="total-line earnings-total">
                        <span>TOTAL EARNINGS</span>
                        <span><?= formatCurrency($row->gross_salary, $currency); ?></span>
                    </div>
                </div>
                
                <!-- Net Pay Card (Inside Earnings Column) -->
                <div style="margin-top: 15px; border: 3px solid #000; border-radius: 8px; padding: 12px 15px; background: #f9fafb;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.8em; font-weight: 600; color: #000; margin-bottom: 3px; text-transform: uppercase;">Net Pay</div>
                            <div style="font-size: 1.6em; font-weight: 700; color: #000; line-height: 1.1;"><?= formatCurrency($row->net_salary, $currency); ?></div>
                            <div style="font-size: 0.7em; color: #374151; margin-top: 3px;">Amount payable to employee</div>
                        </div>
                        <div style="text-align: right;">
                            <i class="fas fa-money-check-alt" style="font-size: 2em; color: #d1d5db;"></i>
                            <div style="font-size: 0.65em; margin-top: 6px; color: #374151;">via <?= $staffAccountDetails ? $staffAccountDetails : 'Bank Transfer'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deductions Column -->
            <div class="deductions-column">
                <div class="section-header deductions-header">
                    <i class="fas fa-minus-circle"></i> DEDUCTIONS
                </div>
                <div>
                    <!-- Employer Contributions Section (Above the separator line) -->
                    <?php if (!empty($employer_contributions)): ?>
                        <div style="background: #f5f5f5; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; border: 2px solid #000;">
                            <div style="font-size: 0.75em; color: #000; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">
                                <i class="fas fa-building"></i> Employer Contributions (Not Deducted)
                            </div>
                            <?php foreach ($employer_contributions as $item): ?>
                                <div class="line-item" style="padding: 4px 0; border-bottom: none;">
                                    <span class="item-label" style="font-size: 0.95em; color: #000; font-weight: 500;"><?= $item['label']; ?></span>
                                    <span class="item-value" style="font-weight: 700; color: #000;"><?= formatCurrency($item['value'], $currency); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Separator Line -->
                        <div style="border-bottom: 2px solid #000; margin: 12px 0; position: relative;">
                            <span style="position: absolute; top: -10px; right: 0; background: white; padding: 0 8px; font-size: 0.75em; color: #000; font-weight: 700;">
                                EMPLOYEE DEDUCTIONS BELOW
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Employee Deductions (Below the separator line) -->
                    <?php foreach ($deductions as $item): ?>
                        <div class="line-item <?= isset($item['css_class']) ? $item['css_class'] : ''; ?>">
                            <span class="item-label">
                                <?= $item['label']; ?>
                                <?php if (isset($item['sublabel'])): ?>
                                    <span style="display: block; font-weight: 400; font-size: 0.9em; color: #555; margin-top: 2px;"><?= $item['sublabel']; ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="item-value"><?= formatCurrency($item['value'], $currency); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($deductions)): ?>
                        <div class="line-item">
                            <span class="item-label">No deductions applied</span>
                            <span class="item-value">-</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="total-line deductions-total">
                        <span>TOTAL DEDUCTIONS</span>
                        <span><?= formatCurrency($row->total_deductions, $currency); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-left">
                <ul>
                    <li>• This payslip is computer-generated and requires no signature</li>
                    <li>• Report discrepancies to HR within 30 days of receipt</li>
                    <li>• <?= $schoolAddress;?> | <?= $schoolBox;?></li>
                </ul>
            </div>
            <div class="footer-right">
                <p>Generated: <strong><?= date('d F Y'); ?></strong></p>
                <p>GPS Address: <?= $schoolDigitalAddress;?></p>
                <p style="margin-top: 5px; font-style: italic;">Keep this payslip for your records</p>
            </div>
        </div>
    </div>

    <?php
        endforeach;
    ?>
</body>
</html>