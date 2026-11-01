<?php 
$payroll = $this->db->get_where('pay_salary', array('pay_id' => $pay_id))->row();
if(!$payroll) {
    echo '<div class="text-center p-8"><div class="text-red-500 text-lg font-semibold">Payroll record not found.</div></div>';
    return;
}

// Get staff info
$staffRow = $this->crud_model->getStaffInfo($payroll->employment_category, $payroll->employee_code);
?>

<style>
    .payroll-edit-form {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }
    
    .pef-header {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        margin: -30px -30px 24px -30px;
        padding: 24px 30px;
        border-radius: 16px 16px 0 0;
    }
    
    .pef-header-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .pef-avatar {
        width: 56px;
        height: 56px;
        background: rgba(255,255,255,0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .pef-avatar i {
        font-size: 24px;
        color: white;
    }
    
    .pef-title {
        color: white;
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }
    
    .pef-subtitle {
        color: rgba(255,255,255,0.85);
        font-size: 14px;
        margin: 4px 0 0 0;
    }
    
    .pef-info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        background: #f8fafc;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    
    .pef-info-item {
        text-align: center;
        padding: 8px;
    }
    
    .pef-info-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 4px;
    }
    
    .pef-info-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }
    
    .pef-section {
        margin-bottom: 24px;
    }
    
    .pef-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .pef-section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    
    .pef-section-icon.green {
        background: #dcfce7;
        color: #16a34a;
    }
    
    .pef-section-icon.red {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .pef-section-icon.blue {
        background: #dbeafe;
        color: #2563eb;
    }
    
    .pef-section-icon.purple {
        background: #f3e8ff;
        color: #9333ea;
    }
    
    .pef-section-title {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .pef-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    
    .pef-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
    
    .pef-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .pef-label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .pef-label .currency {
        font-size: 10px;
        background: #e2e8f0;
        padding: 2px 6px;
        border-radius: 4px;
        color: #64748b;
    }
    
    .pef-input {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        color: #1e293b;
        transition: all 0.2s;
    }
    
    .pef-input:focus {
        outline: none;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }
    
    .pef-input:hover {
        border-color: #cbd5e1;
    }
    
    .pef-summary {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
    }
    
    .pef-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
    }
    
    .pef-summary-col {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .pef-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }
    
    .pef-summary-row.border-top {
        border-top: 1px solid #e2e8f0;
        padding-top: 12px;
        margin-top: 4px;
    }
    
    .pef-summary-label {
        font-size: 13px;
        color: #64748b;
    }
    
    .pef-summary-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }
    
    .pef-summary-value.green {
        color: #16a34a;
    }
    
    .pef-summary-value.blue {
        color: #2563eb;
    }
    
    .pef-summary-value.red {
        color: #dc2626;
    }
    
    .pef-summary-value.large {
        font-size: 18px;
    }
    
    .pef-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    
    .pef-btn {
        flex: 1;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .pef-btn-primary {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }
    
    .pef-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4);
    }
    
    .pef-btn-secondary {
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }
    
    .pef-btn-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    
    .pef-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }
    
    @media (max-width: 768px) {
        .pef-info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .pef-grid, .pef-summary-grid {
            grid-template-columns: 1fr;
        }
        
        .pef-grid-3 {
            grid-template-columns: 1fr;
        }
    }
    
    /* Approval History Styles */
    .approval-history-container {
        background: white;
        border-radius: 8px;
        padding: 16px;
    }
    
    .approval-timeline {
        position: relative;
        padding-left: 40px;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 24px;
    }
    
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    
    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -29px;
        top: 30px;
        width: 2px;
        height: calc(100% - 10px);
        background: #e2e8f0;
    }
    
    .timeline-icon {
        position: absolute;
        left: -40px;
        top: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .timeline-icon.submitted {
        background: #fef3c7;
        color: #92400e;
    }
    
    .timeline-icon.approved {
        background: #d1fae5;
        color: #065f46;
    }
    
    .timeline-icon.rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .timeline-icon.paid {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .timeline-content {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px 16px;
        border-left: 3px solid #e2e8f0;
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 8px;
    }
    
    .timeline-action {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
    }
    
    .timeline-user {
        font-size: 13px;
        color: #64748b;
    }
    
    .timeline-timestamp {
        font-size: 12px;
        color: #94a3b8;
        white-space: nowrap;
    }
    
    .timeline-comments {
        margin-top: 8px;
        padding: 8px 12px;
        background: white;
        border-radius: 6px;
        font-size: 13px;
        color: #475569;
        border-left: 2px solid #cbd5e1;
    }
    
    .timeline-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 8px;
    }
    
    .timeline-badge.submitted {
        background: #fef3c7;
        color: #92400e;
    }
    
    .timeline-badge.approved {
        background: #d1fae5;
        color: #065f46;
    }
    
    .timeline-badge.rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .timeline-badge.paid {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .timeline-empty {
        text-align: center;
        padding: 32px;
        color: #94a3b8;
    }
    
    .timeline-empty i {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }
</style>

<div class="payroll-edit-form">
    <!-- Header -->
    <div class="pef-header">
        <div class="pef-header-content">
            <div class="pef-avatar">
                <i class="fa fa-user"></i>
            </div>
            <div>
                <h2 class="pef-title">Edit Payroll Record</h2>
                <p class="pef-subtitle">Modify salary details and deductions</p>
            </div>
        </div>
    </div>
    
    <!-- Staff Info Grid -->
    <div class="pef-info-grid">
        <div class="pef-info-item">
            <div class="pef-info-label">Staff Name</div>
            <div class="pef-info-value"><?= $staffRow->name ?? 'N/A'; ?></div>
        </div>
        <div class="pef-info-item">
            <div class="pef-info-label">Staff Code</div>
            <div class="pef-info-value"><?= $payroll->employee_code; ?></div>
        </div>
        <div class="pef-info-item">
            <div class="pef-info-label">Pay Period</div>
            <div class="pef-info-value"><?= $payroll->month; ?> <?= $payroll->year; ?></div>
        </div>
        <div class="pef-info-item">
            <div class="pef-info-label">Reference</div>
            <div class="pef-info-value"><?= $payroll->reference; ?></div>
        </div>
    </div>

    <?php echo form_open(site_url('admin/payroll_update_ajax'), array('id' => 'payrollEditForm'));?>
        <input type="hidden" name="pay_id" value="<?= $payroll->pay_id; ?>">
        
        <!-- Basic Salary & Allowances Section -->
        <div class="pef-section">
            <div class="pef-section-header">
                <div class="pef-section-icon green">
                    <i class="fa fa-money-bill-wave"></i>
                </div>
                <h3 class="pef-section-title">Basic Salary & Allowances</h3>
            </div>
            <div class="pef-grid">
                <div class="pef-field">
                    <label class="pef-label">Basic Salary <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="basic_salary" value="<?= $payroll->basic_salary; ?>" required>
                </div>
                <div class="pef-field">
                    <label class="pef-label">Market Premium <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="market_premium_allowance" value="<?= $payroll->market_premium_allowance; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Teaching Allowance <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="teaching_allowance" value="<?= $payroll->teaching_allowance; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Responsibility Allowance <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="responsibility_allowance" value="<?= $payroll->responsibility_allowance; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Extra Classes <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="extra_class_allowance" value="<?= $payroll->extra_class_allowance; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Rural Allowance <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="rural_allowance" value="<?= $payroll->rural_allowance; ?>">
                </div>
                <div class="pef-field" style="grid-column: 2;">
                    <label class="pef-label">Other Allowances <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="other_allowances" value="<?= $payroll->other_allowances; ?>">
                </div>
            </div>
        </div>

        <!-- Deductions Section -->
        <div class="pef-section">
            <div class="pef-section-header">
                <div class="pef-section-icon red">
                    <i class="fa fa-minus-circle"></i>
                </div>
                <h3 class="pef-section-title">Deductions</h3>
            </div>
            <div class="pef-grid">
                <div class="pef-field">
                    <label class="pef-label">SSNIT (Tier 1) <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="ssnit" value="<?= $payroll->ssnit; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Income Tax (PAYE) <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="income_tax" value="<?= $payroll->income_tax; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">
                        <?php 
                        $tier2_rate = isset($statutory_rates['ssnit_tier2']) ? $statutory_rates['ssnit_tier2'] : 5.0;
                        ?>
                        SSNIT Tier 2 (<?= number_format($tier2_rate, 1); ?>%)
                        <?php 
                        // Get provider info from database if tier2_provider_id exists
                        $provider_name = 'Not Set';
                        $member_id_display = '';
                        if (!empty($payroll->tier2_provider_id)) {
                            $provider = $this->db->get_where('pension_tier2_providers', ['provider_id' => $payroll->tier2_provider_id])->row();
                            if ($provider) {
                                $provider_name = $provider->provider_name;
                            }
                        }
                        echo "- $provider_name";
                        ?>
                        <span class="currency">GH₵</span>
                    </label>
                    <input type="number" step="0.01" class="pef-input" name="petra" value="<?= $payroll->petra; ?>">
                    
                    <?php
                    // Task 24.2 & 24.3: Check approval status for provider field
                    $approval_status = $payroll->approval_status ?? 'draft';
                    $can_edit_provider = in_array($approval_status, ['draft', 'rejected', null]);
                    ?>
                    
                    <?php if ($can_edit_provider): ?>
                        <!-- Task 24.2: Allow provider change for draft and rejected payrolls -->
                        <label class="pef-label" style="margin-top: 8px;">Tier 2 Provider</label>
                        <select class="pef-input" name="tier2_provider_id" id="tier2_provider_select">
                            <option value="">Not Set</option>
                            <?php
                            $providers = $this->db->get_where('pension_tier2_providers', ['is_active' => 1])->result();
                            foreach ($providers as $provider):
                            ?>
                                <option value="<?= $provider->provider_id; ?>" <?= ($payroll->tier2_provider_id == $provider->provider_id) ? 'selected' : ''; ?>>
                                    <?= $provider->provider_name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <!-- Task 24.3: Display provider as read-only for approved and paid payrolls -->
                        <div style="margin-top: 8px; padding: 10px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; color: #64748b;">
                            <i class="fa fa-lock" style="margin-right: 6px;"></i>
                            <strong>Provider:</strong> <?= $provider_name; ?>
                            <span style="display: block; margin-top: 4px; font-size: 11px; color: #94a3b8;">
                                <i class="fa fa-info-circle"></i> Provider cannot be changed for <?= ucfirst($approval_status); ?> payroll
                            </span>
                        </div>
                        <input type="hidden" name="tier2_provider_id" value="<?= $payroll->tier2_provider_id; ?>">
                    <?php endif; ?>
                </div>
                <div class="pef-field">
                    <label for="getfund" class="pef-label">
                        <?php 
                        $getfund_rate = isset($statutory_rates['get_fund']) ? $statutory_rates['get_fund'] : 2.5;
                        ?>
                        GETFund (<?= number_format($getfund_rate, 1); ?>%) <span class="currency">GH₵</span>
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="getfundToggle" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-2 text-sm font-medium text-gray-700">Apply</span>
                    </label>
                    <input type="number" step="0.01" class="pef-input" id="getfund" name="get_fund" value="<?= $payroll->get_fund; ?>">
                </div>
                <div class="pef-field">
                    <label for="nhil" class="pef-label">
                        <?php 
                        $nhil_rate = isset($statutory_rates['nhil']) ? $statutory_rates['nhil'] : 2.5;
                        ?>
                        NHIL (<?= number_format($nhil_rate, 1); ?>%) <span class="currency">GH₵</span>
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="nhilToggle" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-2 text-sm font-medium text-gray-700">Apply</span>
                    </label>
                    <input type="number" step="0.01" class="pef-input" id="nhil" name="nhil" value="<?= $payroll->nhil; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Salary Advance <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="salary_advance" value="<?= $payroll->salary_advance; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Loan <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="loan" value="<?= $payroll->loan; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Welfare Dues <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="welfare_dues" value="<?= $payroll->welfare_dues; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">GNAT Dues <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="gnat_dues" value="<?= $payroll->gnat_dues; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Other Deductions <span class="currency">GH₵</span></label>
                    <input type="number" step="0.01" class="pef-input" name="other_deductions" value="<?= $payroll->other_deductions; ?>">
                </div>
            </div>
        </div>

        <!-- Attendance Section -->
        <div class="pef-section">
            <div class="pef-section-header">
                <div class="pef-section-icon blue">
                    <i class="fa fa-calendar-check"></i>
                </div>
                <h3 class="pef-section-title">Attendance Information</h3>
            </div>
            <div class="pef-grid-3">
                <div class="pef-field">
                    <label class="pef-label">Working Days</label>
                    <input type="number" class="pef-input" name="working_days" value="<?= $payroll->working_days; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Days Present</label>
                    <input type="number" class="pef-input" name="days_present" value="<?= $payroll->days_present; ?>">
                </div>
                <div class="pef-field">
                    <label class="pef-label">Days Absent</label>
                    <input type="number" class="pef-input" name="days_absent" value="<?= $payroll->days_absent; ?>">
                </div>
            </div>
        </div>

        <!-- Payroll Summary -->
        <div class="pef-section">
            <div class="pef-section-header">
                <div class="pef-section-icon purple">
                    <i class="fa fa-calculator"></i>
                </div>
                <h3 class="pef-section-title">Payroll Summary</h3>
            </div>
            <div class="pef-summary">
                <div class="pef-summary-grid">
                    <div class="pef-summary-col">
                        <div class="pef-summary-row">
                            <span class="pef-summary-label">Basic Salary</span>
                            <span class="pef-summary-value" id="summaryBasic">GH₵ <?= number_format($payroll->basic_salary, 2); ?></span>
                        </div>
                        <div class="pef-summary-row">
                            <span class="pef-summary-label">Total Allowances</span>
                            <span class="pef-summary-value green" id="summaryAllowances">GH₵ <?= number_format($payroll->total_allowances, 2); ?></span>
                        </div>
                        <div class="pef-summary-row border-top">
                            <span class="pef-summary-label" style="font-weight: 600;">Gross Salary</span>
                            <span class="pef-summary-value blue" id="grossSalaryDisplay">GH₵ <?= number_format($payroll->gross_salary, 2); ?></span>
                        </div>
                    </div>
                    <div class="pef-summary-col">
                        <div class="pef-summary-row">
                            <span class="pef-summary-label">Statutory Deductions</span>
                            <span class="pef-summary-value red" id="summaryStatutory">GH₵ <?= number_format($payroll->income_tax + $payroll->petra + $payroll->get_fund + $payroll->nhil, 2); ?></span>
                        </div>
                        <div class="pef-summary-row">
                            <span class="pef-summary-label">Other Deductions</span>
                            <span class="pef-summary-value red" id="summaryOther">GH₵ <?= number_format($payroll->salary_advance + $payroll->loan + $payroll->welfare_dues + $payroll->gnat_dues + $payroll->other_deductions, 2); ?></span>
                        </div>
                        <div class="pef-summary-row border-top">
                            <span class="pef-summary-label" style="font-weight: 600;">Net Salary</span>
                            <span class="pef-summary-value green large" id="netSalaryDisplay">GH₵ <?= number_format($payroll->net_salary, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden inputs for calculated values -->
            <input type="hidden" name="total_allowances" id="edit_total_allowances" value="<?= $payroll->total_allowances; ?>">
            <input type="hidden" name="total_deductions" id="edit_total_deductions" value="<?= $payroll->total_deductions; ?>">
            <input type="hidden" name="gross_salary" id="edit_gross_salary" value="<?= $payroll->gross_salary; ?>">
            <input type="hidden" name="net_salary" id="edit_net_salary" value="<?= $payroll->net_salary; ?>">
        </div>

        <!-- Action Buttons -->
        <div class="pef-actions">
            <button type="button" id="calculateEditBtn" class="pef-btn pef-btn-secondary">
                <i class="fa fa-calculator"></i> Calculate
            </button>
            <button type="submit" id="updatePayrollBtn" class="pef-btn pef-btn-primary">
                <i class="fa fa-check"></i> Update Payroll
            </button>
        </div>
    <?php echo form_close();?>
    
    <!-- Approval History Section (Task 11.5) -->
    <div class="pef-section" id="approvalHistorySection">
        <div class="pef-section-header">
            <div class="pef-section-icon purple">
                <i class="fa fa-history"></i>
            </div>
            <h3 class="pef-section-title">Approval History</h3>
        </div>
        <div id="approvalHistoryContent" class="approval-history-container">
            <div class="text-center py-4">
                <i class="fa fa-spinner fa-spin text-gray-400 text-2xl"></i>
                <p class="text-gray-500 mt-2">Loading approval history...</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load statutory rates from PHP
    const STATUTORY_RATES = <?php echo isset($statutory_rates_json) ? $statutory_rates_json : '{}'; ?>;
    
    // Load approval history on page load
    loadApprovalHistory();
    
    // Initialize toggle states based on existing values
    const getfundToggle = document.getElementById('getfundToggle');
    const getfundInput = document.getElementById('getfund');
    const nhilToggle = document.getElementById('nhilToggle');
    const nhilInput = document.getElementById('nhil');

    // Initialize GETFund toggle
    if (parseFloat(getfundInput.value) > 0) {
        getfundToggle.checked = true;
        getfundInput.disabled = false;
    } else {
        getfundToggle.checked = false;
        getfundInput.disabled = true;
        getfundInput.value = '0.00';
    }

    // Initialize NHIL toggle
    if (parseFloat(nhilInput.value) > 0) {
        nhilToggle.checked = true;
        nhilInput.disabled = false;
    } else {
        nhilToggle.checked = false;
        nhilInput.disabled = true;
        nhilInput.value = '0.00';
    }
    
    // GETFund toggle handler
    getfundToggle.addEventListener('change', function() {
        if (this.checked) {
            getfundInput.disabled = false;
            calculateTotals(); // Recalculate with GETFund included
        } else {
            getfundInput.disabled = true;
            getfundInput.value = '0.00';
            calculateTotals(); // Recalculate with GETFund excluded
        }
    });

    // NHIL toggle handler
    nhilToggle.addEventListener('change', function() {
        if (this.checked) {
            nhilInput.disabled = false;
            calculateTotals(); // Recalculate with NHIL included
        } else {
            nhilInput.disabled = true;
            nhilInput.value = '0.00';
            calculateTotals(); // Recalculate with NHIL excluded
        }
    });
    
    // Auto-calculate totals on input change
    $('input[type="number"]').on('input', function() {
        calculateTotals();
    });
    
    // Task 24.2: Update Tier 2 label when provider changes
    $('#tier2_provider_select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var providerName = selectedOption.text() || 'Not Set';
        var tier2Rate = STATUTORY_RATES.ssnit_tier2 || 5.0;
        
        // Update the label text with dynamic rate
        var $label = $('input[name="petra"]').closest('.pef-field').find('.pef-label');
        var labelHtml = 'SSNIT Tier 2 (' + tier2Rate.toFixed(1) + '%) - ' + providerName + ' <span class="currency">GH₵</span>';
        $label.html(labelHtml);
    });
    
    function calculateTotals() {
        var basic = parseFloat($('input[name="basic_salary"]').val()) || 0;
        var market = parseFloat($('input[name="market_premium_allowance"]').val()) || 0;
        var teaching = parseFloat($('input[name="teaching_allowance"]').val()) || 0;
        var responsibility = parseFloat($('input[name="responsibility_allowance"]').val()) || 0;
        var extra = parseFloat($('input[name="extra_class_allowance"]').val()) || 0;
        var rural = parseFloat($('input[name="rural_allowance"]').val()) || 0;
        var other_allow = parseFloat($('input[name="other_allowances"]').val()) || 0;
        
        var total_allowances = market + teaching + responsibility + extra + rural + other_allow;
        var gross = basic + total_allowances;
        
        var ssnit = parseFloat($('input[name="ssnit"]').val()) || 0;
        var tax = parseFloat($('input[name="income_tax"]').val()) || 0;
        var petra = parseFloat($('input[name="petra"]').val()) || 0;
        // Only include GETFund if toggle is checked
        var getfund = document.getElementById('getfundToggle').checked ? 
            (parseFloat($('input[name="get_fund"]').val()) || 0) : 0;
        // Only include NHIL if toggle is checked
        var nhil = document.getElementById('nhilToggle').checked ? 
            (parseFloat($('input[name="nhil"]').val()) || 0) : 0;
        var advance = parseFloat($('input[name="salary_advance"]').val()) || 0;
        var loan = parseFloat($('input[name="loan"]').val()) || 0;
        var welfare = parseFloat($('input[name="welfare_dues"]').val()) || 0;
        var gnat = parseFloat($('input[name="gnat_dues"]').val()) || 0;
        var other_ded = parseFloat($('input[name="other_deductions"]').val()) || 0;
        
        var statutory = ssnit + tax + petra + getfund + nhil;
        var other_deductions = advance + loan + welfare + gnat + other_ded;
        var total_deductions = statutory + other_deductions;
        var net = gross - total_deductions;
        
        // Update hidden inputs
        $('#edit_total_allowances').val(total_allowances.toFixed(2));
        $('#edit_total_deductions').val(total_deductions.toFixed(2));
        $('#edit_gross_salary').val(gross.toFixed(2));
        $('#edit_net_salary').val(net.toFixed(2));
        
        // Update display
        $('#summaryBasic').text('GH₵ ' + formatNumber(basic));
        $('#summaryAllowances').text('GH₵ ' + formatNumber(total_allowances));
        $('#grossSalaryDisplay').text('GH₵ ' + formatNumber(gross));
        $('#summaryStatutory').text('GH₵ ' + formatNumber(statutory));
        $('#summaryOther').text('GH₵ ' + formatNumber(other_deductions));
        $('#netSalaryDisplay').text('GH₵ ' + formatNumber(net));
    }
    
    function formatNumber(num) {
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // Calculate button
    $('#calculateEditBtn').click(function() {
        // Get base values
        var basic = parseFloat($('input[name="basic_salary"]').val()) || 0;
        var market = parseFloat($('input[name="market_premium_allowance"]').val()) || 0;
        var teaching = parseFloat($('input[name="teaching_allowance"]').val()) || 0;
        var responsibility = parseFloat($('input[name="responsibility_allowance"]').val()) || 0;
        var extra = parseFloat($('input[name="extra_class_allowance"]').val()) || 0;
        var rural = parseFloat($('input[name="rural_allowance"]').val()) || 0;
        var other_allow = parseFloat($('input[name="other_allowances"]').val()) || 0;
        
        var total_allowances = market + teaching + responsibility + extra + rural + other_allow;
        var gross = basic + total_allowances;
        
        // Use dynamic statutory rates with fallbacks
        var getfundRate = (STATUTORY_RATES.get_fund || 2.5) / 100;
        var nhilRate = (STATUTORY_RATES.nhil || 2.5) / 100;
        
        // Calculate gross before statutory deductions
        var ssnit = parseFloat($('input[name="ssnit"]').val()) || 0;
        var grossBeforeDeductions = gross - ssnit;
        
        // Only calculate GETFund if toggle is checked
        if (document.getElementById('getfundToggle').checked) {
            document.getElementById('getfund').value = (grossBeforeDeductions * getfundRate).toFixed(2);
        } else {
            document.getElementById('getfund').value = '0.00';
        }
        
        // Only calculate NHIL if toggle is checked
        if (document.getElementById('nhilToggle').checked) {
            document.getElementById('nhil').value = (grossBeforeDeductions * nhilRate).toFixed(2);
        } else {
            document.getElementById('nhil').value = '0.00';
        }
        
        // Recalculate totals
        calculateTotals();
        
        // Show success feedback
        var btn = $(this);
        var originalHtml = btn.html();
        btn.html('<i class="fa fa-check"></i> Calculated!').css('background', '#dcfce7').css('color', '#16a34a');
        setTimeout(function() {
            btn.html(originalHtml).removeAttr('style');
        }, 1500);
    });
    
    // Form submission
    $('#payrollEditForm').submit(function(e) {
        e.preventDefault();
        
        var $btn = $('#updatePayrollBtn');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                $('#modal_payroll_edit').modal('hide');
                showAjaxModal_alert(response.message || 'Payroll updated successfully.', 'Success', 'no');
                
                // Reload the table content via AJAX
                reloadPayslipTable();
            } else {
                showAjaxModal_alert('Error: ' + (response.message || 'Failed to update payroll.'), 'Error');
                $btn.prop('disabled', false).html(originalHtml);
            }
        }).fail(function(xhr) {
            showAjaxModal_alert('An error occurred. Please try again.', 'Error');
            $btn.prop('disabled', false).html(originalHtml);
        });
    });
    
    // Function to reload the payslip table via AJAX
    function reloadPayslipTable() {
        // Call the global function if it exists
        if (typeof window.reloadPayslipTable === 'function') {
            window.reloadPayslipTable();
        } else {
            // Fallback: reload via AJAX directly
            $.ajax({
                url: '<?php echo site_url("admin/payslipList"); ?>',
                type: 'GET',
                dataType: 'html'
            }).done(function(response) {
                var $response = $(response);
                var $newTable = $response.find('#printableDiv');
                
                if ($newTable.length) {
                    if ($.fn.DataTable.isDataTable('#payrollTable')) {
                        $('#payrollTable').DataTable().destroy();
                    }
                    
                    $('#printableDiv').replaceWith($newTable);
                    
                    $('#payrollTable').dataTable({
                        layout: {
                            topCenter: {
                                buttons: [
                                    'colvis',
                                    {
                                        extend: 'copyHtml5',5',
                                        exportOptions: {
                                            columns: ':visible'
                                        }
                                    },
                                    {
                                        extend: 'excelHtml5',
                                        exportOptions: {
                                            columns: ':visible'
                                        }
                                    },
                                ]
                            }
                        }
                    });
                }
            }).fail(function(xhr) {
                location.reload();
            });
        }
    }
    
    // Function to load approval history
    function loadApprovalHistory() {
        var pay_id = <?= $pay_id; ?>;
        
        $.ajax({
            url: '<?= site_url("admin/payroll/approval_history/"); ?>' + pay_id,
            type: 'GET',
            dataType: 'json'
        }).done(function(response) {
            if (response.status === 'success' && response.data && response.data.length > 0) {
                renderApprovalHistory(response.data);
            } else {
                renderEmptyApprovalHistory();
            }
        }).fail(function(xhr) {
            renderErrorApprovalHistory();
        });
    }
    
    // Function to render approval history timeline
    function renderApprovalHistory(history) {
        var html = '<div class="approval-timeline">';
        
        $.each(history, function(index, item) {
            var iconClass = getActionIconClass(item.action);
            var icon = getActionIcon(item.action);
            var actionLabel = formatActionLabel(item.action);
            var badgeClass = getActionBadgeClass(item.action);
            
            html += '<div class="timeline-item">';
            html += '  <div class="timeline-icon ' + iconClass + '">';
            html += '    <i class="fa ' + icon + '"></i>';
            html += '  </div>';
            html += '  <div class="timeline-content">';
            html += '    <div class="timeline-header">';
            html += '      <div>';
            html += '        <div class="timeline-action">' + actionLabel;
            html += '          <span class="timeline-badge ' + badgeClass + '">' + item.action + '</span>';
            html += '        </div>';
            html += '        <div class="timeline-user">';
            html += '          <i class="fa fa-user-circle"></i> ' + (item.approver_name || 'System');
            if (item.approver_role) {
                html += ' <span style="color: #94a3b8;">(' + item.approver_role + ')</span>';
            }
            html += '        </div>';
            html += '      </div>';
            html += '      <div class="timeline-timestamp">';
            html += '        <i class="fa fa-clock"></i> ' + formatTimestamp(item.action_date);
            html += '      </div>';
            html += '    </div>';
            
            // Add comments if present
            if (item.comments && item.comments.trim() !== '') {
                html += '    <div class="timeline-comments">';
                html += '      <i class="fa fa-comment-dots" style="color: #94a3b8; margin-right: 6px;"></i>';
                html += '      <strong>Comments:</strong> ' + escapeHtml(item.comments);
                html += '    </div>';
            }
            
            html += '  </div>';
            html += '</div>';
        });
        
        html += '</div>';
        
        $('#approvalHistoryContent').html(html);
    }
    
    // Function to render empty state
    function renderEmptyApprovalHistory() {
        var html = '<div class="timeline-empty">';
        html += '  <i class="fa fa-history"></i>';
        html += '  <p><strong>No approval history yet</strong></p>';
        html += '  <p style="font-size: 13px; margin-top: 4px;">Approval actions will appear here once the workflow begins.</p>';
        html += '</div>';
        
        $('#approvalHistoryContent').html(html);
    }
    
    // Function to render error state
    function renderErrorApprovalHistory() {
        var html = '<div class="timeline-empty" style="color: #ef4444;">';
        html += '  <i class="fa fa-exclamation-triangle"></i>';
        html += '  <p><strong>Failed to load approval history</strong></p>';
        html += '  <p style="font-size: 13px; margin-top: 4px;">Please try refreshing the page.</p>';
        html += '</div>';
        
        $('#approvalHistoryContent').html(html);
    }
    
    // Helper function to get action icon class
    function getActionIconClass(action) {
        switch(action.toLowerCase()) {
            case 'submitted':
                return 'submitted';
            case 'approved':
                return 'approved';
            case 'rejected':
                return 'rejected';
            case 'marked as paid':
            case 'paid':
                return 'paid';
            default:
                return 'submitted';
        }
    }
    
    // Helper function to get action icon
    function getActionIcon(action) {
        switch(action.toLowerCase()) {
            case 'submitted':
                return 'fa-paper-plane';
            case 'approved':
                return 'fa-check-circle';
            case 'rejected':
                return 'fa-times-circle';
            case 'marked as paid':
            case 'paid':
                return 'fa-money-bill-wave';
            default:
                return 'fa-circle';
        }
    }
    
    // Helper function to get action badge class
    function getActionBadgeClass(action) {
        switch(action.toLowerCase()) {
            case 'submitted':
                return 'submitted';
            case 'approved':
                return 'approved';
            case 'rejected':
                return 'rejected';
            case 'marked as paid':
            case 'paid':
                return 'paid';
            default:
                return 'submitted';
        }
    }
    
    // Helper function to format action label
    function formatActionLabel(action) {
        switch(action.toLowerCase()) {
            case 'submitted':
                return 'Submitted for Approval';
            case 'approved':
                return 'Approved';
            case 'rejected':
                return 'Rejected';
            case 'marked as paid':
            case 'paid':
                return 'Marked as Paid';
            default:
                return action;
        }
    }
    
    // Helper function to format timestamp
    function formatTimestamp(timestamp) {
        if (!timestamp) return 'N/A';
        
        var date = new Date(timestamp);
        var now = new Date();
        var diffMs = now - date;
        var diffMins = Math.floor(diffMs / 60000);
        var diffHours = Math.floor(diffMs / 3600000);
        var diffDays = Math.floor(diffMs / 86400000);
        
        // Relative time for recent events
        if (diffMins < 60) {
            return diffMins <= 1 ? 'Just now' : diffMins + ' minutes ago';
        } else if (diffHours < 24) {
            return diffHours === 1 ? '1 hour ago' : diffHours + ' hours ago';
        } else if (diffDays < 7) {
            return diffDays === 1 ? 'Yesterday' : diffDays + ' days ago';
        }
        
        // Absolute date for older events
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var day = date.getDate();
        var month = months[date.getMonth()];
        var year = date.getFullYear();
        var hours = date.getHours();
        var mins = date.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        mins = mins < 10 ? '0' + mins : mins;
        
        return day + ' ' + month + ' ' + year + ', ' + hours + ':' + mins + ' ' + ampm;
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
