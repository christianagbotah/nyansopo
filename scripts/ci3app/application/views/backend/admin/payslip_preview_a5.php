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

    // Task 7.1: Validation for missing staff record (Requirements 12.1, 12.2)
    if (empty($staffRow)) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Staff Not Found</title>';
        echo '<link rel="stylesheet" href="' . base_url('assets/tailwindcss/output.css') . '"></head><body>';
        echo '<div class="flex items-center justify-center min-h-screen bg-gray-100">';
        echo '<div class="bg-white p-8 rounded-lg shadow-lg text-center">';
        echo '<i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>';
        echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">Staff Information Not Found</h2>';
        echo '<p class="text-gray-600 mb-6">The staff record for code "' . htmlspecialchars($staffCode) . '" could not be found.</p>';
        echo '<a href="' . base_url('admin/payroll_list') . '" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">';
        echo '<i class="fas fa-arrow-left mr-2"></i>Go Back to Payroll List</a>';
        echo '</div></div></body></html>';
        exit;
    }

    $staffName = ucwords(strtolower($staffRow->name));
    $staffSex = $staffRow->sex;
    $staffAddress = $staffRow->address;
    $staffPhone = $staffRow->phone;
    $staffQualification = $staffRow->designation;
    $staffAccountNumber = $staffRow->account_number;
    $staffAccountDetails = $staffRow->account_details;
    $staffCategory = '';

    if($employmentCategory == 'teacher') {
        $staffCategory = 'Teacher';
    } else if($employmentCategory == 'admin') {
        $staffCategory = 'Administration';
    } else if($employmentCategory == 'non_teaching_staff') {
        $staffCategory = 'Non-Teaching Staff';
    }

    /*Currency settings*/
    $currency = get_settings('currency') ?: 'GH₵';

    /*Helper function for conditional display*/
    /*Requirements 3.1, 3.2, 3.3, 4.1, 4.2, 4.3*/
    function shouldDisplayLine($value) {
        // Handle NULL values by treating as zero (Requirement 3.2, 4.2)
        $numericValue = $value ?? 0;
        
        // Check if value > 0 (Requirements 3.1, 3.3, 4.1, 4.3)
        return floatval($numericValue) > 0;
    }

    /*Helper function for currency formatting*/
    /*Requirements 8.2, 9.1, 9.2, 9.3, 9.4*/
    function formatCurrency($amount, $currencySymbol = null) {
        // Retrieve currency symbol from system settings (Requirement 9.1)
        if ($currencySymbol === null) {
            $currencySymbol = get_settings('currency');
        }
        
        // Apply fallback to 'GH₵' if currency setting is missing (Requirement 9.1)
        if (empty($currencySymbol)) {
            $currencySymbol = 'GH₵';
        }
        
        // Format amounts with 2 decimal places and thousand separators (Requirements 9.2, 9.3, 9.4)
        return $currencySymbol . ' ' . number_format($amount, 2, '.', ',');
    }
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=210mm, initial-scale=1.0">
    <title><?= ucfirst($schoolName) ?> - <?= $staffName;?> - Payslip <?= $payMonth. ' '. $payYear;?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/tailwindcss/output.css');?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url('assets/fontawesome/6.7.2/css/all.min.css');?>">
    
    <!-- jQuery -->
    <script src="<?php echo base_url('assets/js/jquery-3.4.1.js');?>" type="text/javascript"></script>
    
    <!-- PDF Generation Libraries -->
    <script src="<?php echo base_url('assets/js/pdf-plugins/html2canvas.min.js');?>"></script>
    <script src="<?php echo base_url('assets/js/pdf-plugins/jspdf.umd.min.js');?>"></script>

    <style>
        /* A5 Landscape Page Format */
        @page {
            size: A5 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
        }

        /* Screen preview container */
        .payslip-container {
            width: 210mm;
            height: 148mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 10mm;
            box-sizing: border-box;
        }

        /* Print media styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 9px;
            }

            .no-print {
                display: none !important;
            }

            .payslip-container {
                width: 100% !important;
                height: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 10mm !important;
                box-shadow: none !important;
                page-break-after: avoid;
            }

            .print-break {
                page-break-after: always;
            }
        }

        /* Screen view specific */
        @media screen {
            body {
                background-color: #e5e7eb;
                padding: 20px;
            }
        }

        /* Typography */
        .section-header {
            font-size: 12px;
            font-weight: bold;
            border-bottom: 2px solid;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .line-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 8px;
            font-weight: bold;
            margin-top: 8px;
            border: 2px solid;
            border-radius: 4px;
        }

        /* Color scheme */
        .earnings-color { color: #15803d; }
        .deductions-color { color: #b91c1c; }
        .earnings-bg { background-color: #f0fdf4; border-color: #86efac; }
        .deductions-bg { background-color: #fef2f2; border-color: #fca5a5; }
    </style>
</head>
<body>
    <?php 
    // Task 7.2: Validation for missing payroll record (Requirements 12.1, 12.2)
    if (empty($staffPayrollData) || !is_array($staffPayrollData) || count($staffPayrollData) === 0) {
        echo '<div class="flex items-center justify-center min-h-screen bg-gray-100">';
        echo '<div class="bg-white p-8 rounded-lg shadow-lg text-center">';
        echo '<i class="fas fa-exclamation-triangle text-orange-500 text-6xl mb-4"></i>';
        echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">No Payroll Record Found</h2>';
        echo '<p class="text-gray-600 mb-2">No payroll record found for <strong>' . htmlspecialchars($staffName) . '</strong> (Code: ' . htmlspecialchars($staffCode) . ')</p>';
        echo '<p class="text-gray-600 mb-6">for the period: <strong>' . htmlspecialchars($payMonth) . ' ' . htmlspecialchars($payYear) . '</strong></p>';
        echo '<a href="' . base_url('admin/payroll_list') . '" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">';
        echo '<i class="fas fa-arrow-left mr-2"></i>Go Back to Payroll List</a>';
        echo '</div></div></body></html>';
        exit;
    }
    ?>
    <?php foreach($staffPayrollData as $row): ?>
    
    <!-- Print Controls (Hidden during print) -->
    <div class="no-print text-center mb-6">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg transition duration-300 mr-3">
            <i class="fas fa-print mr-2"></i>
            Print Payslip
        </button>
        <button id="downloadPdfButton" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg transition duration-300">
            <i class="fas fa-file-pdf mr-2"></i>
            Download PDF
        </button>
    </div>

    <!-- A5 Landscape Payslip Container -->
    <div class="payslip-container" id="printableDiv">
        
        <!-- Header Section with School Logo and Info -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 3px solid #1f2937;">
            <div style="display: flex; align-items: center;">
                <div style="width: 50px; height: 50px; margin-right: 12px;">
                    <img src="<?=base_url('uploads/school_logo.png');?>" style="width: 50px; height: 50px; border-radius: 50%;" alt="School Logo">
                </div>
                <div>
                    <h1 style="font-size: 16px; font-weight: bold; margin: 0; line-height: 1.2;"><?= $schoolName;?></h1>
                    <div style="font-size: 9px; color: #6b7280;"><?= $schoolAddress;?></div>
                    <div style="font-size: 9px; color: #6b7280;"><?= $schoolPhone;?> | <?= $schoolEmail;?></div>
                </div>
            </div>
            <div style="text-align: right;">
                <h2 style="font-size: 18px; font-weight: bold; margin: 0; color: #1f2937;">PAYSLIP</h2>
                <div style="font-size: 9px; margin-top: 2px;">Period: <strong><?= $payMonth . ' ' . $payYear;?></strong></div>
                <div style="font-size: 9px;">Ref: <strong><?= $row->reference;?></strong></div>
                <div style="font-size: 9px;">Generated: <strong><span class="generated-date"></span></strong></div>
            </div>
        </div>

        <!-- Employee Information Section -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px; padding: 8px; background-color: #f9fafb; border-radius: 4px;">
            <div>
                <div style="font-size: 10px; margin-bottom: 3px;">
                    <span style="font-weight: 600; color: #4b5563;">Staff ID:</span>
                    <span style="font-weight: bold;"><?= $staffCode;?></span>
                </div>
                <div style="font-size: 10px; margin-bottom: 3px;">
                    <span style="font-weight: 600; color: #4b5563;">Name:</span>
                    <span style="font-weight: bold;"><?= $staffName;?></span>
                </div>
                <div style="font-size: 10px;">
                    <span style="font-weight: 600; color: #4b5563;">Category:</span>
                    <span style="font-weight: bold;"><?= $staffCategory;?></span>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 10px; margin-bottom: 3px;">
                    <span style="font-weight: 600; color: #4b5563;">Pay Period:</span>
                    <span style="font-weight: bold;"><?= $payMonth . ' ' . $payYear;?></span>
                </div>
                <div style="font-size: 10px; margin-bottom: 3px;">
                    <span style="font-weight: 600; color: #4b5563;">Qualification:</span>
                    <span style="font-weight: bold;"><?= $staffQualification;?></span>
                </div>
                <div style="font-size: 10px;">
                    <span style="font-weight: 600; color: #4b5563;">Phone:</span>
                    <span style="font-weight: bold;"><?= $staffPhone;?></span>
                </div>
            </div>
        </div>

        <!-- Earnings and Deductions Two-Column Layout -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
            
            <!-- EARNINGS SECTION (LEFT COLUMN) -->
            <!-- Task 2.2: Earnings section with conditional display -->
            <!-- Requirements: 2.1, 2.4, 3.3, 3.5, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6 -->
            <div>
                <div class="section-header earnings-color">EARNINGS</div>
                <div style="min-height: 120px;">
                    
                    <!-- Basic Salary (Always displayed per Requirement 3.4) -->
                    <div class="line-item">
                        <span style="color: #374151;">Basic Salary</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->basic_salary ?? 0, $currency); ?></span>
                    </div>

                    <!-- Conditional Earnings Display (Requirements 3.1, 3.2, 3.3, 5.1-5.6) -->
                    <?php if(shouldDisplayLine($row->market_premium_allowance)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Market Premium</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->market_premium_allowance, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->teaching_allowance)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Teaching Allowance</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->teaching_allowance, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->responsibility_allowance)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Responsibility Allowance</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->responsibility_allowance, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->rural_allowance)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Rural Allowance</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->rural_allowance, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->extra_class_allowance)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Extra Classes</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->extra_class_allowance, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->other_allowances)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Other Allowances</span>
                        <span class="earnings-color" style="font-weight: 600;"><?= formatCurrency($row->other_allowances, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                </div>
                
                <!-- Total Earnings (Calculated from visible items only - Requirement 3.5) -->
                <?php
                    // Calculate total earnings from visible (non-zero) items only
                    // Basic salary always included (Requirement 3.4)
                    $totalEarnings = floatval($row->basic_salary ?? 0);
                    
                    // Add other earnings only if they are visible (value > 0)
                    if (shouldDisplayLine($row->market_premium_allowance)) {
                        $totalEarnings += floatval($row->market_premium_allowance);
                    }
                    if (shouldDisplayLine($row->teaching_allowance)) {
                        $totalEarnings += floatval($row->teaching_allowance);
                    }
                    if (shouldDisplayLine($row->responsibility_allowance)) {
                        $totalEarnings += floatval($row->responsibility_allowance);
                    }
                    if (shouldDisplayLine($row->rural_allowance)) {
                        $totalEarnings += floatval($row->rural_allowance);
                    }
                    if (shouldDisplayLine($row->extra_class_allowance)) {
                        $totalEarnings += floatval($row->extra_class_allowance);
                    }
                    if (shouldDisplayLine($row->other_allowances)) {
                        $totalEarnings += floatval($row->other_allowances);
                    }
                ?>
                <div class="total-line earnings-bg earnings-color">
                    <span>TOTAL EARNINGS</span>
                    <span><?= formatCurrency($totalEarnings, $currency); ?></span>
                </div>
            </div>

            <!-- DEDUCTIONS SECTION (RIGHT COLUMN) -->
            <div>
                <div class="section-header deductions-color">DEDUCTIONS</div>
                <div style="min-height: 120px;">
                    
                    <!-- Conditional Deductions Display (Requirements 4.1, 4.2, 4.3) -->
                    <?php if(shouldDisplayLine($row->income_tax)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Income Tax (PAYE)</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->income_tax, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->ssnit)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">SSNIT (Tier 1)</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->ssnit, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->petra)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">PETRA Dues (Tier 2)</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->petra, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->tier2_contribution)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Tier 2 Contribution</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->tier2_contribution, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->get_fund)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">GETFund</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->get_fund, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->nhil)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">NHIL</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->nhil, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->salary_advance)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Salary Advance</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->salary_advance, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->loan)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Loan Repayment</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->loan, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->welfare_dues)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Welfare Dues</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->welfare_dues, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->gnat_dues)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">GNAT Dues</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->gnat_dues, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if(shouldDisplayLine($row->other_deductions)): ?>
                    <div class="line-item">
                        <span style="color: #374151;">Other Deductions</span>
                        <span class="deductions-color" style="font-weight: 600;"><?= formatCurrency($row->other_deductions, $currency); ?></span>
                    </div>
                    <?php endif; ?>

                </div>
                
                <!-- Total Deductions -->
                <div class="total-line deductions-bg deductions-color">
                    <span>TOTAL DEDUCTIONS</span>
                    <span><?= formatCurrency($row->total_deductions ?? 0, $currency); ?></span>
                </div>
            </div>
        </div>

        <!-- Net Pay Section -->
        <div style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 10px 12px; border-radius: 6px; margin-bottom: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 14px; font-weight: bold;">NET PAY</div>
                    <div style="font-size: 8px; opacity: 0.9;">Amount to be credited</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 20px; font-weight: bold;"><?= formatCurrency($row->net_salary ?? 0, $currency); ?></div>
                    <div style="font-size: 8px; opacity: 0.9;">
                        <?php 
                        // Mask account number for security
                        $maskedAccount = strlen($staffAccountNumber) > 6 
                            ? substr($staffAccountNumber, 0, 2) . '*-****-***' . substr($staffAccountNumber, -3)
                            : $staffAccountNumber;
                        ?>
                        <?= $staffAccountDetails;?> | A/C: <?= $maskedAccount;?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; font-size: 8px; padding: 8px; background-color: #f3f4f6; border-radius: 4px; color: #4b5563;">
            <div>
                <div style="font-weight: bold; margin-bottom: 3px;">Important Notice:</div>
                <div>• This payslip is computer-generated and requires no signature</div>
                <div>• Report discrepancies to HR within 30 days</div>
                <div>• Keep for your records</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: bold;"><?= $schoolName;?></div>
                <div><?= $schoolBox;?></div>
                <div><?= $schoolWebsite;?></div>
                <div style="margin-top: 4px;">Generated: <span class="generated-date"></span></div>
            </div>
        </div>

    </div>

    <script>
        // Generate and set current date
        function formatDate() {
            const now = new Date();
            return now.toLocaleDateString('en-GB', { 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric' 
            });
        }

        // Set all generated date elements
        document.querySelectorAll('.generated-date').forEach(function(element) {
            element.textContent = formatDate();
        });

        // PDF Download functionality
        document.getElementById('downloadPdfButton').addEventListener('click', function() {
            const content = document.getElementById('printableDiv');
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating PDF...';
            
            // Set timeout for PDF generation (30 seconds)
            const timeoutId = setTimeout(() => {
                alert('PDF generation timed out. Please try printing instead.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-file-pdf mr-2"></i>Download PDF';
            }, 30000);

            html2canvas(content, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(canvas => {
                clearTimeout(timeoutId);
                
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                
                // A5 landscape dimensions in mm
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a5'
                });

                // Calculate dimensions to fit A5 landscape
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('<?= ucfirst($schoolName) ?> - <?= $staffName;?> - Payslip <?= $payMonth. " ". $payYear;?>.pdf');
                
                // Reset button state
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-file-pdf mr-2"></i>Download PDF';
            }).catch(error => {
                clearTimeout(timeoutId);
                console.error('PDF generation failed:', error);
                alert('Could not generate PDF. Please use the Print button instead.');
                
                // Reset button state
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-file-pdf mr-2"></i>Download PDF';
            });
        });
    </script>

    <?php endforeach; ?>
</body>
</html>
