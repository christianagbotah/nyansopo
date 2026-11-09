<?php
$staffInfo = $staffInfoData;
$staffPayroll = $staffPayrollData;

// Determine staff type for display
$staffType = '';
$staffTable = '';
if (isset($staffInfo->admin_id)) {
    $staffType = 'Administrator';
    $staffTable = 'admin';
    $staffId = $staffInfo->admin_id;
    $staffCode = $staffInfo->admin_code;
    $staffName = $staffInfo->name;
    $staffEmail = $staffInfo->email;
    $staffPhone = $staffInfo->phone;
    $staffAddress = $staffInfo->address;
    $staffPhoto = $staffInfo->photo ?? 'default.jpg';
    $staffGender = ucfirst($staffInfo->sex ?? 'N/A');
    $staffDOB = $staffInfo->birthday ?? 'N/A';
    $staffLevel = $staffInfo->level ?? 'N/A';
} elseif (isset($staffInfo->teacher_id)) {
    $staffType = 'Teacher';
    $staffTable = 'teacher';
    $staffId = $staffInfo->teacher_id;
    $staffCode = $staffInfo->teacher_code;
    $staffName = $staffInfo->name;
    $staffEmail = $staffInfo->email;
    $staffPhone = $staffInfo->phone;
    $staffAddress = $staffInfo->address;
    $staffPhoto = $staffInfo->photo ?? 'default.jpg';
    $staffGender = ucfirst($staffInfo->sex ?? 'N/A');
    $staffDOB = $staffInfo->birthday ?? 'N/A';
    $staffLevel = 'N/A';
} elseif (isset($staffInfo->staff_id)) {
    $staffType = 'Non-Teaching Staff';
    $staffTable = 'non_teaching_staff';
    $staffId = $staffInfo->staff_id;
    $staffCode = $staffInfo->staff_code;
    $staffName = $staffInfo->name;
    $staffEmail = $staffInfo->email;
    $staffPhone = $staffInfo->phone;
    $staffAddress = $staffInfo->address;
    $staffPhoto = $staffInfo->photo ?? 'default.jpg';
    $staffGender = ucfirst($staffInfo->sex ?? 'N/A');
    $staffDOB = $staffInfo->birthday ?? 'N/A';
    $staffLevel = 'N/A';
}

// Bank details - Parse account_details field format: "Account Name | Bank/Provider | Account Type/Method"
$account_details = $staffInfo->account_details ?? '';
$details_parts = array_map('trim', explode('|', $account_details));

$account_name = isset($details_parts[0]) && !empty($details_parts[0]) ? $details_parts[0] : $staffName;
$bankName = isset($details_parts[1]) && !empty($details_parts[1]) ? $details_parts[1] : ($staffInfo->bank_name ?? 'N/A');
$accountType = isset($details_parts[2]) && !empty($details_parts[2]) ? $details_parts[2] : ($staffInfo->account_type ?? 'N/A');
$accountNumber = $staffInfo->account_number ?? 'N/A';

// Format account type for display
$accountTypeDisplay = ($accountType !== 'N/A') ? ucwords(str_replace('_', ' ', $accountType)) : 'N/A';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-6">
        <button onclick="window.history.back()" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            Back
        </button>
    </div>

    <!-- Staff Profile Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
            <!-- Profile Photo -->
            <div class="flex-shrink-0">
                <img src="<?php echo base_url('uploads/admin_image/'.$staffPhoto); ?>" 
                     alt="<?php echo $staffName; ?>" 
                     class="w-32 h-32 rounded-full object-cover border-4 border-gray-200"
                     onerror="this.src='<?php echo base_url('uploads/admin_image/default.jpg'); ?>'">
            </div>
            
            <!-- Basic Info -->
            <div class="flex-grow">
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo $staffName; ?></h1>
                <p class="text-lg text-gray-600 mb-4"><?php echo $staffType; ?></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm font-semibold text-gray-700">Staff Code:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo $staffCode; ?></span>
                    </div>
                    <div>
                        <span class="text-sm font-semibold text-gray-700">Gender:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo $staffGender; ?></span>
                    </div>
                    <?php if ($staffLevel !== 'N/A'): ?>
                    <div>
                        <span class="text-sm font-semibold text-gray-700">Level:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo $staffLevel; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Contact Information -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-address-book text-blue-600 mr-2"></i>
                Contact Information
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Email</label>
                    <p class="text-gray-900"><?php echo $staffEmail; ?></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Phone</label>
                    <p class="text-gray-900"><?php echo $staffPhone; ?></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Address</label>
                    <p class="text-gray-900"><?php echo $staffAddress; ?></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Date of Birth</label>
                    <p class="text-gray-900"><?php echo $staffDOB; ?></p>
                </div>
            </div>
        </div>

        <!-- Bank Details -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-university text-green-600 mr-2"></i>
                Bank Details
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Account Holder</label>
                    <p class="text-gray-900"><?php echo $account_name; ?></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Bank Name</label>
                    <p class="text-gray-900"><?php echo $bankName; ?></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Account Type</label>
                    <p class="text-gray-900"><?php echo $accountTypeDisplay; ?></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Account Number</label>
                    <p class="text-gray-900 font-mono"><?php echo $accountNumber; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Information -->
    <?php if ($staffPayroll && count($staffPayroll) > 0): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-money-bill-wave text-purple-600 mr-2"></i>
            Payroll Information
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($staffPayroll as $payroll): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('F', mktime(0, 0, 0, $payroll->month, 1)); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $payroll->year; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            GH₵ <?php echo number_format($payroll->basic_salary, 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                            GH₵ <?php echo number_format($payroll->net_salary, 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="<?php echo site_url('admin/payslip_view/'.$payroll->pay_id); ?>" 
                               class="text-blue-600 hover:text-blue-800 hover:underline">
                                View Payslip
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Ensure metro menu doesn't auto-open on this page */
body.metro-visible {
    /* Override any auto-open behavior */
}
</style>
