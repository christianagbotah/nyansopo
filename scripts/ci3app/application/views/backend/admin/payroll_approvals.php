<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Approvals</title>
    <link rel="stylesheet" href="<?=base_url('node_modules/flowbite/dist/flowbite.min.css')?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/fontawesome/6.7.2/css/all.min.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/tailwindcss/output.css');?>">
    
    <!--jQuery-->
    <script src="<?php echo base_url('assets/js/jquery-3.4.1.js');?>" type="text/javascript"></script>
    <script src="<?php echo base_url('assets/tailwindcss/tailwindcss.js');?>"></script>
    <script src="<?=base_url('node_modules/flowbite/dist/flowbite.min.js')?>"></script>

    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-pending_approval { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-paid { background: #dbeafe; color: #1e40af; }
        
        .approval-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .approval-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-12 h-8 bg-red-600 mr-2"></div>
                    <div class="w-12 h-8 bg-yellow-400 mr-2"></div>
                    <div class="w-12 h-8 bg-green-600"></div>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= get_phrase($page_title);?></h1>
                <p class="text-gray-600">Review and approve staff payroll submissions</p>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex gap-4 items-center w-full sm:w-auto">
                        <label for="statusFilter" class="text-sm font-medium text-gray-700">Filter by Status:</label>
                        <select id="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <option value="all">All Statuses</option>
                            <option value="pending_approval" selected>Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="draft">Draft</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <!-- View Toggle Buttons -->
                        <div class="inline-flex rounded-md shadow-sm mr-2" role="group">
                            <button type="button" id="gridViewBtn" onclick="switchView('grid')" class="px-4 py-2.5 text-sm font-medium text-blue-700 bg-white border border-blue-700 rounded-l-lg hover:bg-blue-700 hover:text-white focus:z-10 focus:ring-2 focus:ring-blue-700">
                                <i class="fas fa-th-large mr-2"></i> Grid
                            </button>
                            <button type="button" id="tableViewBtn" onclick="switchView('table')" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border-t border-b border-r border-gray-300 rounded-r-lg hover:bg-gray-100 focus:z-10 focus:ring-2 focus:ring-gray-300">
                                <i class="fas fa-table mr-2"></i> Table
                            </button>
                        </div>
                        
                        <button onclick="refreshApprovals()" class="text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                        <a href="<?= site_url('admin/payroll'); ?>" class="text-green-700 hover:text-white border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fas fa-plus mr-2"></i> Create Payroll
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
                    <div class="text-yellow-600 text-sm font-semibold mb-1">Pending</div>
                    <div class="text-2xl font-bold text-gray-900" id="stat-pending">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
                    <div class="text-green-600 text-sm font-semibold mb-1">Approved</div>
                    <div class="text-2xl font-bold text-gray-900" id="stat-approved">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
                    <div class="text-red-600 text-sm font-semibold mb-1">Rejected</div>
                    <div class="text-2xl font-bold text-gray-900" id="stat-rejected">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-gray-500">
                    <div class="text-gray-600 text-sm font-semibold mb-1">Draft</div>
                    <div class="text-2xl font-bold text-gray-900" id="stat-draft">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
                    <div class="text-blue-600 text-sm font-semibold mb-1">Paid</div>
                    <div class="text-2xl font-bold text-gray-900" id="stat-paid">0</div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="text-center py-12 hidden">
                <div role="status">
                    <svg aria-hidden="true" class="inline w-12 h-12 text-gray-200 animate-spin fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                    </svg>
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-4 text-gray-600">Loading approvals...</p>
            </div>

            <!-- Approvals Grid -->
            <div id="approvalsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Cards will be dynamically inserted here -->
            </div>

            <!-- Approvals Table (Hidden by default) -->
            <div id="approvalsTable" class="hidden bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Employee</th>
                                <th scope="col" class="px-6 py-3">Period</th>
                                <th scope="col" class="px-6 py-3">Category</th>
                                <th scope="col" class="px-6 py-3">Gross Salary</th>
                                <th scope="col" class="px-6 py-3">Net Salary</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Rows will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="text-center py-12 hidden">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No payrolls found</h3>
                <p class="mt-2 text-sm text-gray-500">No payroll records match the selected status filter.</p>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal (Task 11.4) -->
    <div id="rejectModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Reject Payroll
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="rejectModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5 space-y-4">
                    <div>
                        <label for="rejectionReason" class="block mb-2 text-sm font-medium text-gray-900">
                            Rejection Reason <span class="text-red-600">*</span>
                        </label>
                        <textarea id="rejectionReason" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Please provide a detailed reason for rejection (minimum 10 characters)..." required></textarea>
                        <p id="reasonCharCount" class="mt-1 text-xs text-gray-500">0 / 10 characters minimum</p>
                        <p id="reasonError" class="mt-1 text-xs text-red-600 hidden">Rejection reason must be at least 10 characters</p>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                    <button id="confirmRejectBtn" type="button" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Reject Payroll
                    </button>
                    <button data-modal-hide="rejectModal" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Confirmation Modal -->
    <div id="approveModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Approve Payroll
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="approveModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5 space-y-4">
                    <p class="text-base leading-relaxed text-gray-700">
                        Are you sure you want to approve this payroll? This action will move the payroll to approved status.
                    </p>
                    <div>
                        <label for="approvalComments" class="block mb-2 text-sm font-medium text-gray-900">
                            Comments (Optional)
                        </label>
                        <textarea id="approvalComments" rows="3" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Add any comments about this approval..."></textarea>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                    <button id="confirmApproveBtn" type="button" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Approve Payroll
                    </button>
                    <button data-modal-hide="approveModal" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentPayrollId = null;
        let allPayrolls = [];
        let currentView = 'grid'; // Track current view mode
        const userCanApprove = <?= $can_approve ? 'true' : 'false' ?>;
        const userCanMarkPaid = <?= $can_mark_paid ? 'true' : 'false' ?>;

        // Initialize modals
        const rejectModal = new Modal(document.getElementById('rejectModal'));
        const approveModal = new Modal(document.getElementById('approveModal'));

        // Load all payrolls on page load
        $(document).ready(function() {
            loadApprovals();
            
            // Filter change handler
            $('#statusFilter').on('change', function() {
                filterApprovals($(this).val());
            });
            
            // Rejection reason character count
            $('#rejectionReason').on('input', function() {
                const length = $(this).val().length;
                $('#reasonCharCount').text(length + ' / 10 characters minimum');
                
                if (length >= 10) {
                    $('#reasonError').addClass('hidden');
                    $('#reasonCharCount').removeClass('text-red-600').addClass('text-green-600');
                } else {
                    $('#reasonCharCount').removeClass('text-green-600').addClass('text-red-600');
                }
            });
        });

        // Switch between grid and table views
        function switchView(view) {
            currentView = view;
            
            if (view === 'grid') {
                $('#approvalsGrid').removeClass('hidden');
                $('#approvalsTable').addClass('hidden');
                $('#gridViewBtn').removeClass('text-gray-700 bg-white border-gray-300').addClass('text-blue-700 bg-white border-blue-700');
                $('#tableViewBtn').removeClass('text-blue-700 bg-white border-blue-700').addClass('text-gray-700 bg-white border-gray-300');
            } else {
                $('#approvalsGrid').addClass('hidden');
                $('#approvalsTable').removeClass('hidden');
                $('#gridViewBtn').removeClass('text-blue-700 bg-white border-blue-700').addClass('text-gray-700 bg-white border-gray-300');
                $('#tableViewBtn').removeClass('text-gray-700 bg-white border-gray-300').addClass('text-blue-700 bg-white border-blue-700');
            }
            
            // Re-apply current filter
            const currentFilter = $('#statusFilter').val();
            filterApprovals(currentFilter);
        }

        // Load all payrolls from the database
        function loadApprovals() {
            $('#loadingIndicator').removeClass('hidden');
            $('#approvalsGrid').addClass('hidden');
            $('#approvalsTable').addClass('hidden');
            $('#emptyState').addClass('hidden');

            $.ajax({
                url: '<?= site_url("admin/payroll_pending_approvals"); ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        allPayrolls = response.data;
                        updateStats();
                        
                        // Apply current filter
                        const currentFilter = $('#statusFilter').val();
                        filterApprovals(currentFilter);
                    } else {
                        showError('Failed to load approvals');
                    }
                    $('#loadingIndicator').addClass('hidden');
                },
                error: function(xhr, status, error) {
                    console.error('Load error:', error);
                    showError('Error loading approvals: ' + error);
                    $('#loadingIndicator').addClass('hidden');
                    $('#emptyState').removeClass('hidden');
                }
            });
        }

        // Filter approvals by status
        function filterApprovals(status) {
            let filtered = allPayrolls;
            
            if (status !== 'all') {
                filtered = allPayrolls.filter(p => p.approval_status === status);
            }
            
            if (currentView === 'grid') {
                displayApprovalsGrid(filtered);
            } else {
                displayApprovalsTable(filtered);
            }
        }

        // Display approvals in the grid view
        function displayApprovalsGrid(payrolls) {
            const grid = $('#approvalsGrid');
            grid.empty();
            
            if (payrolls.length === 0) {
                $('#emptyState').removeClass('hidden');
                grid.addClass('hidden');
                return;
            }
            
            $('#emptyState').addClass('hidden');
            grid.removeClass('hidden');
            
            payrolls.forEach(payroll => {
                const card = createApprovalCard(payroll);
                grid.append(card);
            });
        }

        // Display approvals in the table view
        function displayApprovalsTable(payrolls) {
            const tbody = $('#tableBody');
            tbody.empty();
            
            if (payrolls.length === 0) {
                $('#emptyState').removeClass('hidden');
                $('#approvalsTable').addClass('hidden');
                return;
            }
            
            $('#emptyState').addClass('hidden');
            $('#approvalsTable').removeClass('hidden');
            
            payrolls.forEach(payroll => {
                const row = createApprovalTableRow(payroll);
                tbody.append(row);
            });
        }

        // Create approval table row HTML
        function createApprovalTableRow(payroll) {
            const statusClass = `status-${payroll.approval_status || 'draft'}`;
            const statusText = (payroll.approval_status || 'draft').replace('_', ' ').toUpperCase();
            
            // Determine which action buttons to show - only approval actions
            let actionButtons = '';
            
            if (payroll.approval_status === 'pending_approval' && userCanApprove) {
                actionButtons = `
                    <button onclick="approvePayroll(${payroll.pay_id})" 
                            class="text-green-700 hover:text-white border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center mr-1">
                        <i class="fas fa-check mr-1"></i> Approve
                    </button>
                    <button onclick="rejectPayroll(${payroll.pay_id})" 
                            class="text-red-700 hover:text-white border border-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                `;
            } else if (payroll.approval_status === 'approved' && userCanMarkPaid) {
                actionButtons = `
                    <button onclick="markAsPaid(${payroll.pay_id})" 
                            class="text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center">
                        <i class="fas fa-money-bill-wave mr-1"></i> Mark Paid
                    </button>
                `;
            } else {
                // For other statuses, show a placeholder or nothing
                actionButtons = `<span class="text-xs text-gray-500 italic">No actions available</span>`;
            }
            
            return `
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">${payroll.employee_name || 'Unknown'}</div>
                        <div class="text-xs text-gray-500">${payroll.employee_code || 'N/A'}</div>
                    </td>
                    <td class="px-6 py-4">
                        ${getMonthName(payroll.month)} ${payroll.year}
                    </td>
                    <td class="px-6 py-4">
                        ${payroll.employment_category || 'N/A'}
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-green-600 font-medium">GH¢ ${parseFloat(payroll.gross_salary || 0).toLocaleString('en-GH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-blue-600 font-bold">GH¢ ${parseFloat(payroll.net_salary || 0).toLocaleString('en-GH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-wrap justify-center gap-1">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        }

        // Create approval card HTML
        function createApprovalCard(payroll) {
            const statusClass = `status-${payroll.approval_status || 'draft'}`;
            const statusText = (payroll.approval_status || 'draft').replace('_', ' ').toUpperCase();
            
            // Determine which action buttons to show - only approval actions
            let actionButtons = '';
            if (payroll.approval_status === 'pending_approval' && userCanApprove) {
                actionButtons = `
                    <div class="flex gap-2 pt-4 border-t border-gray-200">
                        <button onclick="approvePayroll(${payroll.pay_id})" 
                                class="flex-1 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center">
                            <i class="fas fa-check mr-2"></i> Approve
                        </button>
                        <button onclick="rejectPayroll(${payroll.pay_id})" 
                                class="flex-1 text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center">
                            <i class="fas fa-times mr-2"></i> Reject
                        </button>
                    </div>
                `;
            } else if (payroll.approval_status === 'approved' && userCanMarkPaid) {
                actionButtons = `
                    <div class="flex gap-2 pt-4 border-t border-gray-200">
                        <button onclick="markAsPaid(${payroll.pay_id})" 
                                class="flex-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center">
                            <i class="fas fa-money-bill-wave mr-2"></i> Mark as Paid
                        </button>
                    </div>
                `;
            }
            
            return `
                <div class="approval-card bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">${payroll.employee_name || 'Unknown'}</h3>
                                <p class="text-sm text-gray-600">${payroll.employee_code || 'N/A'}</p>
                            </div>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Period:</span>
                                <span class="font-medium">${getMonthName(payroll.month)} ${payroll.year}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Gross Salary:</span>
                                <span class="font-medium text-green-600">GH¢ ${parseFloat(payroll.gross_salary || 0).toLocaleString('en-GH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Net Salary:</span>
                                <span class="font-bold text-blue-600">GH¢ ${parseFloat(payroll.net_salary || 0).toLocaleString('en-GH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                            ${payroll.employment_category ? `
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Category:</span>
                                <span class="font-medium">${payroll.employment_category}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${actionButtons}
                    </div>
                </div>
            `;
        }

        // Update statistics
        function updateStats() {
            const stats = {
                pending_approval: 0,
                approved: 0,
                rejected: 0,
                draft: 0,
                paid: 0
            };
            
            allPayrolls.forEach(p => {
                const status = p.approval_status || 'draft';
                if (stats.hasOwnProperty(status)) {
                    stats[status]++;
                }
            });
            
            $('#stat-pending').text(stats.pending_approval);
            $('#stat-approved').text(stats.approved);
            $('#stat-rejected').text(stats.rejected);
            $('#stat-draft').text(stats.draft);
            $('#stat-paid').text(stats.paid);
        }

        // Approve payroll
        function approvePayroll(payId) {
            currentPayrollId = payId;
            $('#approvalComments').val('');
            approveModal.show();
        }

        // Confirm approve
        $('#confirmApproveBtn').on('click', function() {
            if (!currentPayrollId) return;
            
            const comments = $('#approvalComments').val();
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Approving...');
            
            $.ajax({
                url: '<?= site_url("admin/payroll_approve/"); ?>' + currentPayrollId,
                type: 'POST',
                data: { comments: comments },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccess(response.message || 'Payroll approved successfully');
                        approveModal.hide();
                        loadApprovals(); // Reload data
                    } else {
                        showError(response.message || 'Failed to approve payroll');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Approve error:', error);
                    showError('Error approving payroll: ' + error);
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-check mr-2"></i> Approve Payroll');
                }
            });
        });

        // Reject payroll
        function rejectPayroll(payId) {
            currentPayrollId = payId;
            $('#rejectionReason').val('');
            $('#reasonCharCount').text('0 / 10 characters minimum');
            $('#reasonError').addClass('hidden');
            rejectModal.show();
        }

        // Confirm reject
        $('#confirmRejectBtn').on('click', function() {
            if (!currentPayrollId) return;
            
            const reason = $('#rejectionReason').val();
            
            // Validate reason length
            if (reason.length < 10) {
                $('#reasonError').removeClass('hidden');
                return;
            }
            
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Rejecting...');
            
            $.ajax({
                url: '<?= site_url("admin/payroll_reject/"); ?>' + currentPayrollId,
                type: 'POST',
                data: { reason: reason },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccess(response.message || 'Payroll rejected successfully');
                        rejectModal.hide();
                        loadApprovals(); // Reload data
                    } else {
                        showError(response.message || 'Failed to reject payroll');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reject error:', error);
                    showError('Error rejecting payroll: ' + error);
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-times mr-2"></i> Reject Payroll');
                }
            });
        });

        // Mark as paid (for finance role)
        function markAsPaid(payId) {
            showCustomConfirm(
                'Are you sure you want to mark this payroll as paid? This action confirms payment has been processed.',
                function() {
                    // On Yes - proceed with marking as paid
                    $.ajax({
                        url: '<?= site_url("admin/payroll_mark_paid/"); ?>' + payId,
                        type: 'POST',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                showSuccess(response.message || 'Payroll marked as paid successfully');
                                loadApprovals(); // Reload data
                            } else {
                                showError(response.message || 'Failed to mark payroll as paid');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Mark paid error:', error);
                            showError('Error marking payroll as paid: ' + error);
                        }
                    });
                }
                // On No - do nothing (modal closes automatically)
            );
        }

        // Refresh approvals
        function refreshApprovals() {
            loadApprovals();
        }

        // Helper function to get month name
        function getMonthName(month) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[parseInt(month) - 1] || 'Unknown';
        }

        // Show success message
        function showSuccess(message) {
            showAjaxModal_alert(message, 'Success', false, true);
        }

        // Show error message
        function showError(message) {
            showAjaxModal_alert(message, 'Error', false, true);
        }
    </script>
</body>
</html>
