<!-- VIEWPORT META TAG INSURANCE: Ensure viewport is set for mobile rendering -->
<!-- Even though viewport meta tag exists in main.php, this redundant declaration ensures mobile breakpoints trigger correctly -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<style>
/* ========================================
   FEE COLLECTION MOBILE RESPONSIVE STYLES
   Last Updated: 2026-09-25 - Mobile First Approach
   Cache Version: v2.0
   ======================================== */

/* ========================================
   BASE LAYOUT - DESKTOP FIRST
   ======================================== */
.pos-container { 
    display: grid; 
    grid-template-columns: 300px 1fr 400px; 
    gap: 20px; 
    padding: 0 20px; 
}

.pos-left { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    overflow-y: auto; 
    max-height: calc(100vh - 40px); 
}

.pos-middle { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    overflow-y: auto; 
}

.pos-right { 
    background: #1e293b; 
    border-radius: 12px; 
    padding: 20px; 
    color: white; 
    display: flex; 
    flex-direction: column; 
    position: sticky; 
    top: 20px; 
    height: fit-content; 
    max-height: calc(100vh - 40px); 
}

/* ========================================
   FEE ITEMS & CARDS
   ======================================== */
.fee-item { 
    background: #f8fafc; 
    border: 2px solid #e2e8f0; 
    border-radius: 8px; 
    padding: 15px; 
    margin-bottom: 10px; 
    cursor: pointer; 
    transition: all 0.2s; 
}

.fee-item.active { 
    border-color: #3b82f6; 
    background: #eff6ff; 
}

.fee-item:hover {
    border-color: #3b82f6;
    transform: translateX(2px);
}

/* ========================================
   KEYPAD & POS ELEMENTS
   ======================================== */
.pos-display { 
    background: #0f172a; 
    padding: 20px; 
    border-radius: 8px; 
    margin-bottom: 20px; 
}

.pos-keypad { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 10px; 
    margin-top: auto; 
}

.pos-key { 
    background: #334155; 
    padding: 20px; 
    border-radius: 8px; 
    text-align: center; 
    font-size: 20px; 
    font-weight: bold; 
    cursor: pointer; 
    transition: all 0.2s; 
    user-select: none; 
}

.pos-key:hover { 
    background: #475569; 
    transform: scale(1.05); 
}

.pos-key:active { 
    transform: scale(0.95); 
}

.pos-key.clear { 
    background: #dc2626; 
}

.pos-key.enter { 
    background: #16a34a; 
    grid-column: span 2; 
}

/* ========================================
   OTHER ELEMENTS
   ======================================== */
.student-card { 
    background: #f8fafc; 
    border: 2px solid #e2e8f0; 
    border-radius: 8px; 
    padding: 12px; 
    margin-bottom: 8px; 
    cursor: pointer; 
    transition: all 0.2s; 
}

.student-card:hover { 
    border-color: #3b82f6; 
    background: #eff6ff; 
    transform: translateX(4px); 
}

.saved-txn { 
    background: #fef3c7; 
    border-left: 4px solid #f59e0b; 
    padding: 10px; 
    margin-bottom: 10px; 
    cursor: pointer; 
}

.saved-txn:hover { 
    background: #fde68a; 
}

.payment-history { 
    background: #fff; 
    border: 1px solid #e2e8f0; 
    border-radius: 8px; 
    padding: 12px; 
    margin-top: 15px; 
    max-height: 300px; 
    overflow-y: auto; 
}

.payment-item { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 8px; 
    border-bottom: 1px solid #f1f5f9; 
}

.payment-item:last-child { 
    border-bottom: none; 
}

.payment-item:hover { 
    background: #f8fafc; 
}

.mobile-keypad-toggle { 
    display: none; 
}

.transaction-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 16px; 
    flex-wrap: wrap; 
    gap: 12px; 
}

.transaction-header-buttons { 
    display: flex; 
    gap: 8px; 
    flex-wrap: wrap; 
}

.transaction-buttons-container { 
    display: flex; 
    gap: 12px; 
}

.transaction-buttons-container button { 
    flex: 1; 
}

/* ========================================
   TABLET RESPONSIVE (768px - 1024px)
   ======================================== */
@media (max-width: 1024px) {
    .pos-container {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
        padding: 10px !important;
    }
    
    .pos-left, .pos-middle {
        max-height: none !important;
    }
    
    .pos-right {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        top: auto !important;
        border-radius: 16px 16px 0 0 !important;
        max-height: 70vh !important;
        transform: translateY(calc(100% - 60px)) !important;
        transition: transform 0.3s ease !important;
        z-index: 999999 !important;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.3) !important;
    }
    
    .pos-right.expanded {
        transform: translateY(0) !important;
    }
    
    .mobile-keypad-toggle {
        display: block !important;
        text-align: center !important;
        padding: 10px !important;
        background: rgba(255,255,255,0.1) !important;
        margin: -20px -20px 15px -20px !important;
        cursor: pointer !important;
        border-radius: 16px 16px 0 0 !important;
        user-select: none !important;
    }
    
    .mobile-keypad-toggle:after {
        content: "▲ Tap to use Keypad" !important;
        font-size: 14px !important;
        font-weight: bold !important;
    }
    
    .pos-right.expanded .mobile-keypad-toggle:after {
        content: "▼ Hide Keypad" !important;
    }
}

/* ========================================
   MOBILE RESPONSIVE (max 768px)
   ======================================== */
@media (max-width: 768px) {
    /* Container & Sections */
    .pos-left, .pos-middle {
        padding: 15px !important;
        border-radius: 10px !important;
    }
    
    /* Headers */
    .pos-left h2, .pos-middle h2 {
        font-size: 18px !important;
        margin-bottom: 12px !important;
        font-weight: 700 !important;
    }
    
    /* Transaction Header - STACK VERTICALLY */
    .transaction-header {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
        background: #f8fafc !important;
        padding: 12px !important;
        border-radius: 10px !important;
    }
    
    .transaction-header h2 {
        margin-bottom: 0 !important;
    }
    
    .transaction-header-buttons {
        width: 100% !important;
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
    }
    
    .transaction-header-buttons button {
        font-size: 13px !important;
        padding: 10px 8px !important;
        white-space: nowrap !important;
        min-height: 44px !important;
    }
    
    /* Student Info Card */
    #student_info {
        padding: 14px !important;
        border-radius: 10px !important;
        margin-bottom: 14px !important;
    }
    
    #student_info .font-bold.text-lg {
        font-size: 16px !important;
        line-height: 1.3 !important;
        margin-bottom: 5px !important;
    }
    
    #student_info .text-sm {
        font-size: 13px !important;
        line-height: 1.4 !important;
    }
    
    /* FEE CARDS - SINGLE COLUMN LAYOUT */
    #fee_items {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
    }
    
    .fee-item {
        padding: 14px !important;
        margin-bottom: 0 !important;
        border-radius: 10px !important;
    }
    
    /* Fee Card Content */
    .fee-item > div.flex {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 12px !important;
    }
    
    .fee-item > div.flex > div:first-child {
        flex: 1 !important;
        min-width: 0 !important;
    }
    
    .fee-item .font-bold {
        font-size: 15px !important;
        font-weight: 700 !important;
        margin-bottom: 4px !important;
    }
    
    .fee-item .text-sm.text-red-600 {
        font-size: 12px !important;
        font-weight: 600 !important;
    }
    
    .fee-item > div.flex > div:last-child {
        flex-shrink: 0 !important;
        min-width: 80px !important;
        text-align: right !important;
    }
    
    .fee-item .text-2xl {
        font-size: 19px !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
    }
    
    /* Transport Dropdown */
    .fee-item select#transport_direction {
        width: 100% !important;
        padding: 10px !important;
        font-size: 14px !important;
        border-radius: 8px !important;
        margin-top: 8px !important;
    }
    
    /* Payment Summary */
    .bg-gray-100.p-4 {
        padding: 14px !important;
        border-radius: 10px !important;
        margin-top: 14px !important;
    }
    
    .bg-gray-100 .flex.justify-between {
        display: flex !important;
        justify-content: space-between !important;
        margin-bottom: 10px !important;
    }
    
    .bg-gray-100 .flex.justify-between:last-child {
        margin-bottom: 0 !important;
    }
    
    .bg-gray-100 .flex.justify-between .text-xl {
        font-size: 16px !important;
        font-weight: 800 !important;
    }
    
    .bg-gray-100 .flex.justify-between .text-lg {
        font-size: 14px !important;
        font-weight: 700 !important;
    }
    
    /* Payment Method & Date - SINGLE COLUMN */
    #transaction_area .grid.grid-cols-2 {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        margin-top: 14px !important;
    }
    
    #payment_method, #payment_date {
        width: 100% !important;
        padding: 12px !important;
        font-size: 15px !important;
        border-width: 2px !important;
        border-radius: 8px !important;
    }
    
    /* Amount Tendered Input */
    .bg-gray-50.border-2 {
        padding: 12px !important;
        margin-top: 12px !important;
        border-radius: 8px !important;
    }
    
    .bg-gray-50.border-2 label {
        font-size: 14px !important;
        font-weight: 600 !important;
        margin-bottom: 8px !important;
    }
    
    #amount_tendered_input {
        width: 100% !important;
        padding: 12px !important;
        font-size: 16px !important;
        border-width: 2px !important;
        border-radius: 8px !important;
    }
    
    /* Transaction Buttons - STACK VERTICALLY */
    .transaction-buttons-container {
        flex-direction: column !important;
        gap: 10px !important;
        margin-top: 16px !important;
    }
    
    .transaction-buttons-container button {
        width: 100% !important;
        padding: 14px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        min-height: 48px !important;
        border-radius: 8px !important;
    }
    
    /* Search Inputs */
    input#student_search, select#class_search {
        padding: 12px !important;
        font-size: 16px !important;
        border-width: 2px !important;
        border-radius: 8px !important;
    }
    
    /* Student Cards in Left Panel */
    .student-card {
        padding: 12px !important;
        margin-bottom: 10px !important;
    }
    
    .student-card .font-bold {
        font-size: 14px !important;
    }
    
    .student-card .text-xs {
        font-size: 11px !important;
    }
    
    /* Keypad */
    .pos-display {
        padding: 14px !important;
        margin-bottom: 14px !important;
    }
    
    .pos-display .text-sm {
        font-size: 11px !important;
        margin-bottom: 6px !important;
    }
    
    .pos-display .text-xl {
        font-size: 14px !important;
    }
    
    .pos-display .text-2xl {
        font-size: 16px !important;
    }
    
    #keypad_display {
        font-size: 28px !important;
        font-weight: 800 !important;
    }
    
    .pos-keypad {
        gap: 8px !important;
    }
    
    .pos-key {
        padding: 15px !important;
        font-size: 18px !important;
    }
}

/* ========================================
   SMALL MOBILE (max 480px)
   ======================================== */
@media (max-width: 480px) {
    .pos-container {
        padding: 8px !important;
        gap: 12px !important;
    }
    
    .transaction-header-buttons {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .transaction-header-buttons button {
        font-size: 12px !important;
        padding: 9px 6px !important;
    }
    
    .fee-item .font-bold {
        font-size: 14px !important;
    }
    
    .fee-item .text-2xl {
        font-size: 18px !important;
    }
    
    .pos-key {
        padding: 13px !important;
        font-size: 17px !important;
    }
}
</style>

<div class="pos-container">
    <div class="pos-left">
        <div class="mb-4">
            <h2 class="text-xl font-bold mb-3">Select Student</h2>
            <select id="class_search" class="w-full px-4 py-3 border-2 rounded-lg mb-2">
                <option value="" selected>General Search (All Classes)</option>
                <optgroup label="Search by Specific Class">
                <?php
                $classes = getFullClassList();
                if($classes && is_array($classes)):
                    foreach($classes as $class):
                ?>
                <option value="<?php echo $class['class_id']; ?>"><?php echo $class['class_name']; ?></option>
                <?php 
                    endforeach;
                endif;
                ?>
                </optgroup>
            </select>
            <input type="text" id="student_search" placeholder="Search student by name or code..." class="w-full px-4 py-3 border-2 rounded-lg" onkeyup="filterStudentList()" autofocus>
        </div>
        <div id="search_results"></div>
        <div id="payment_history" style="display:none;"></div>
    </div>

    <div class="pos-middle">
        <div class="transaction-header">
            <h2 class="text-xl font-bold">Transaction</h2>
            <div class="transaction-header-buttons">
                <button onclick="showCouponModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fa fa-ticket"></i> Print Coupon
                </button>
                <button onclick="showSavedTransactions()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                    <i class="fa fa-clock"></i> Saved (<span id="saved_count">0</span>)
                </button>
            </div>
        </div>

        <div id="transaction_area" style="display:none;">
            <div id="student_info" class="bg-blue-50 p-4 rounded-lg mb-4"></div>
            
            <div id="fee_items"></div>
            
            <div class="bg-gray-100 p-4 rounded-lg mt-4">
                <div class="flex justify-between text-xl font-bold mb-2">
                    <span>TOTAL DUE:</span>
                    <span id="total_due">GHS 0.00</span>
                </div>
                <div class="flex justify-between text-lg">
                    <span>Amount Paying:</span>
                    <span id="amount_paying_display" class="text-blue-600 font-bold">GHS 0.00</span>
                </div>
                <div class="flex justify-between text-lg">
                    <span>Amount Tendered:</span>
                    <span id="amount_tendered_display" class="text-green-600 font-bold">GHS 0.00</span>
                </div>
                <div class="flex justify-between text-lg">
                    <span>Change:</span>
                    <span id="change_display" class="text-red-600 font-bold">GHS 0.00</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <select id="payment_method" class="px-4 py-3 border-2 rounded-lg" onkeydown="if(event.key==='Enter') navigateNext()">
                    <option value="1">Cash</option>
                    <option value="3">Mobile Money</option>
                    <option value="4">Bank Transfer</option>
                    <option value="2">Cheque</option>
                </select>
                <div class="relative">
                    <input type="text" id="payment_date" class="datepicker px-4 py-3 border-2 rounded-lg w-full" value="<?php echo date('d-m-Y'); ?>" onkeydown="if(event.key==='Enter') navigateNext()" onchange="handleDateChange()">
                    <div id="date_indicator" class="absolute top-1 right-1 px-2 py-1 text-xs font-bold rounded" style="display:none;"></div>
                </div>
            </div>
            <div id="backdate_warning" class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-3" style="display:none;">
                <div class="flex items-center">
                    <i class="fa fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    <div>
                        <p class="text-sm font-bold text-yellow-800">Backdated Transaction</p>
                        <p class="text-xs text-yellow-700">This will recalculate balances from <span id="backdate_from"></span> to today</p>
                    </div>
                </div>
            </div>

            <div class="transaction-buttons-container mt-4">
                <button type="button" onclick="saveTransaction(); return false;" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg flex-1" onkeydown="if(event.key==='Enter') navigateNext()">
                    <i class="fa fa-save"></i> Save for Later
                </button>
                <button type="button" onclick="processPayment(); return false;" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg flex-1" onkeydown="if(event.key==='Enter') navigateNext()">
                    <i class="fa fa-check"></i> Complete Payment
                </button>
            </div>
        </div>
    </div>

    <div class="pos-right">
        <div class="mobile-keypad-toggle"></div>
        <div class="pos-display">
            <div class="text-sm text-gray-400 mb-2">Selected Fee Type</div>
            <div id="selected_fee_type" class="text-xl font-bold mb-4">None</div>
            <div class="text-sm text-gray-400 mb-2">Amount Due</div>
            <div id="selected_fee_due" class="text-2xl font-bold mb-4">GHS 0.00</div>
            <div class="text-sm text-gray-400 mb-2">Amount Entering</div>
            <div id="keypad_display" class="text-4xl font-bold text-green-400">0.00</div>
        </div>

        <div class="pos-keypad">
            <div class="pos-key" onclick="addDigit('7')">7</div>
            <div class="pos-key" onclick="addDigit('8')">8</div>
            <div class="pos-key" onclick="addDigit('9')">9</div>
            <div class="pos-key" onclick="addDigit('4')">4</div>
            <div class="pos-key" onclick="addDigit('5')">5</div>
            <div class="pos-key" onclick="addDigit('6')">6</div>
            <div class="pos-key" onclick="addDigit('1')">1</div>
            <div class="pos-key" onclick="addDigit('2')">2</div>
            <div class="pos-key" onclick="addDigit('3')">3</div>
            <div class="pos-key" onclick="addDigit('0')">0</div>
            <div class="pos-key" onclick="addDigit('.')">.</div>
            <div class="pos-key clear" onclick="clearKeypad()">C</div>
            <div class="pos-key enter" onclick="applyAmount()">ENTER</div>
        </div>
    </div>
</div>



<script>
let currentTransaction = { student_id: null, fees: {}, tendered: 0 };
let selectedFeeType = null;
let keypadValue = '0';
let visibleFeeTypes = [];
let navigationElements = [];
let currentNavIndex = -1;

function searchByClass() {
    const classId = $('#class_search').val();
    if(!classId) { $('#search_results').html(''); return; }
    const searchTerm = $('#student_search').val();
    
    $.post('<?php echo site_url('admin/search_students_by_class'); ?>', { class_id: classId, search: searchTerm }, function(response) {
        const data = JSON.parse(response);
        if(data.status === 'success') {
            let html = '';
            data.students.forEach(s => {
                html += `<div class="student-card" onclick="selectStudent(${s.student_id})">
                    <div class="font-bold text-sm">${s.name}</div>
                    <div class="text-xs text-gray-600">${s.student_code}</div>
                </div>`;
            });
            $('#search_results').html(html || '<p style="text-align:center;color:#7f8c8d;padding:20px;">No students found</p>');
        }
    });
}

function filterStudentList() {
    const classId = $('#class_search').val();
    const searchTerm = $('#student_search').val();
    
    if(classId) {
        searchByClass();
    } else if(searchTerm.length >= 2) {
        $.post('<?php echo site_url('admin/search_student_for_fees'); ?>', { search_term: searchTerm }, function(response) {
            const data = JSON.parse(response);
            if(data.status === 'success') {
                let html = '';
                data.students.forEach(s => {
                    html += `<div class="student-card" onclick="selectStudent(${s.student_id})">
                        <div class="font-bold text-sm">${s.name}</div>
                        <div class="text-xs text-gray-600">${s.student_code} | ${s.class_name || 'N/A'}</div>
                    </div>`;
                });
                $('#search_results').html(html || '<p style="text-align:center;color:#7f8c8d;padding:20px;">No students found</p>');
            }
        });
    } else if(searchTerm.length === 0 && !classId) {
        $('#search_results').html('');
    }
}

function selectStudent(studentId) {
    $.post('<?php echo site_url('admin/get_student_outstanding_fees'); ?>', { student_id: studentId, payment_date: $('#payment_date').val() }, function(response) {
        const data = JSON.parse(response);
        if(data.status === 'success') {
            $.post('<?php echo site_url('admin/check_today_payment'); ?>', { student_id: studentId, payment_date: $('#payment_date').val() }, function(checkRes) {
                const check = JSON.parse(checkRes);
                if(check.exists) {
                    showConfirmModal('Payment Exists', 'Payment already recorded for this student today. Override?', function() {
                        loadTransaction(data);
                        loadPaymentHistory(studentId);
                    }, 'Override', 'warning');
                } else {
                    loadTransaction(data);
                    loadPaymentHistory(studentId);
                }
            });
        }
    });
}

function loadPaymentHistory(studentId) {
    $.post('<?php echo site_url('admin/get_student_payment_history'); ?>', { student_id: studentId }, function(response) {
        try {
            const data = JSON.parse(response);
            if(data.status === 'success' && data.history && data.history.length > 0) {
                let html = '<div class="payment-history"><div style="font-weight:bold;margin-bottom:10px;">Recent Payments</div>';
                data.history.forEach(h => {
                    let feeDetails = h.fees.map(f => `${f.type}: ${parseFloat(f.amount).toFixed(2)}`).join(', ');
                    html += `<div class="payment-item">
                        <div>
                            <div style="font-size:12px;font-weight:600;">${h.date}</div>
                            <div style="font-size:10px;color:#64748b;">${feeDetails}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:bold;color:#16a34a;">${h.total}</div>
                            <button onclick="printReceipt(${studentId}, ${h.timestamp})" class="btn btn-xs btn-primary" style="margin-top:2px;padding:2px 8px;font-size:10px;"><i class="fa fa-print"></i></button>
                        </div>
                    </div>`;
                });
                html += '</div>';
                $('#payment_history').html(html).show();
            } else {
                $('#payment_history').hide();
            }
        } catch(e) {
            console.error('Payment history error:', e, response);
            $('#payment_history').hide();
        }
    }).fail(function(xhr, status, error) {
        console.error('Payment history AJAX failed:', status, error);
        $('#payment_history').hide();
    });
}

function printReceipt(studentId, timestamp) {
    window.open('<?php echo site_url('admin/print_fee_receipt'); ?>/' + studentId + '/' + timestamp, '_blank');
}

function loadTransaction(data) {
    currentTransaction = {
        student_id: data.student.student_id,
        fees: {
            feeding: { due: parseFloat(data.feeding_due), amount: parseFloat(data.feeding_paid_today || 0) },
            classes: { due: parseFloat(data.classes_due), amount: parseFloat(data.classes_paid_today || 0) },
            transport: { due: parseFloat(data.transport_due), amount: parseFloat(data.transport_paid_today || 0), route_fare: parseFloat(data.route_fare || 0) }
        },
        tendered: 0,
        transport_direction: data.transport_direction || ''
    };
    
    // Auto-populate amounts for fees with arrears (if no prepaid balance)
    ['feeding', 'classes', 'transport'].forEach(type => {
        const due = currentTransaction.fees[type].due;
        const prepaidKey = `${type}_balance`;
        const prepaid = parseFloat(data[prepaidKey] || 0);
        
        // If student owes money and has NO prepaid balance, auto-fill the amount
        if (due > 0 && prepaid === 0) {
            currentTransaction.fees[type].amount = due;
        }
    });
    
    let studentInfoHtml = `<div class="font-bold text-lg">${data.student.name}</div>
        <div class="text-sm">${data.student.student_code} | ${data.class_name} ${data.class_numeric} ${data.section || ''}</div>`;
    
    if(data.is_beneficiary) {
        studentInfoHtml += `<div class="bg-green-50 border border-green-200 rounded p-2 mt-2">
            <p class="text-xs font-bold text-green-800"><i class="fa fa-gift"></i> Beneficiary Student</p>`;
        
        if(data.feeding_discount > 0 && !data.hide_feeding) {
            const originalFee = parseFloat(data.feeding_due) + parseFloat(data.feeding_discount);
            studentInfoHtml += `<p class="text-xs text-green-700">Feeding: GH₵${originalFee.toFixed(2)} - GH₵${parseFloat(data.feeding_discount).toFixed(2)} = GH₵${parseFloat(data.feeding_due).toFixed(2)}</p>`;
        } else if(data.hide_feeding) {
            studentInfoHtml += `<p class="text-xs text-green-700">Feeding: 100% Discount (Free)</p>`;
        }
        
        if(data.classes_discount > 0 && !data.hide_classes) {
            const originalFee = parseFloat(data.classes_due) + parseFloat(data.classes_discount);
            studentInfoHtml += `<p class="text-xs text-green-700">Classes: GH₵${originalFee.toFixed(2)} - GH₵${parseFloat(data.classes_discount).toFixed(2)} = GH₵${parseFloat(data.classes_due).toFixed(2)}</p>`;
        } else if(data.hide_classes) {
            studentInfoHtml += `<p class="text-xs text-green-700">Classes: 100% Discount (Free)</p>`;
        }
        
        if(data.feeding_discount == 0 && data.classes_discount == 0 && !data.hide_feeding && !data.hide_classes) {
            studentInfoHtml += `<p class="text-xs text-orange-700">No discount configured for this class</p>`;
        }
        
        studentInfoHtml += `</div>`;
    }
    
    $('#student_info').html(studentInfoHtml);
    
    let feeHtml = '';
    visibleFeeTypes = [];
    ['feeding', 'classes', 'transport'].forEach(type => {
        const fee = currentTransaction.fees[type];
        const hideKey = `hide_${type}`;
        if(data[hideKey]) return; // Skip if fee should be hidden (disabled module, 100% discount, or no transport)
        
        visibleFeeTypes.push(type);
        const feeLabel = type === 'transport' ? 'Transport Fare' : `${type.charAt(0).toUpperCase() + type.slice(1)} Fee`;
        
        // Add transport direction selector for transport fee
        let transportDirectionHtml = '';
        if(type === 'transport' && data.route_fare) {
            transportDirectionHtml = `
                <div class="mt-2">
                    <select id="transport_direction" class="w-full px-2 py-1 border rounded text-sm" onchange="updateTransportFare()">
                        <option value="">Select Usage</option>
                        <option value="none">Not Using Today (GHS 0.00)</option>
                        <option value="in">Morning Only - To School (GHS ${parseFloat(data.route_fare).toFixed(2)})</option>
                        <option value="out">Afternoon Only - From School (GHS ${parseFloat(data.route_fare).toFixed(2)})</option>
                        <option value="both">Both Ways (GHS ${(parseFloat(data.route_fare) * 2).toFixed(2)})</option>
                    </select>
                </div>`;
        }
        
        feeHtml += `<div class="fee-item" id="fee_${type}" onclick="selectFee('${type}')">
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-bold">${feeLabel}</div>
                    <div class="text-sm text-red-600 font-semibold">Due: GHS ${fee.due.toFixed(2)}</div>
                    ${transportDirectionHtml}
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-green-600" id="amount_${type}">${fee.amount.toFixed(2)}</div>
                </div>
            </div>
        </div>`;
    });
    
    // Add amount tendered field after fee items
    feeHtml += `<div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-4 mt-4">
        <label class="block text-sm font-bold text-gray-700 mb-2">Amount Tendered</label>
        <input type="number" id="amount_tendered_input" step="0.01" placeholder="0.00" 
               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-lg font-bold" 
               onchange="updateTotals()" oninput="updateTotals()" 
               onfocus="clearFeeSelection()" onblur="restoreFeeSelection()" 
               onkeydown="if(event.key==='Enter') navigateNext()">
    </div>`;
    
    $('#fee_items').html(feeHtml);
    $('#transaction_area').show();
    $('#search_results').html('');
    
    // Adjust grid columns based on visible fee types
    $('.pos-container').removeClass('cols-1 cols-2 cols-3').addClass('cols-' + visibleFeeTypes.length);
    
    setupNavigation();
    updateTotals();
}

let lastSelectedFeeType = null;

function selectFee(type) {
    $('.fee-item').removeClass('active');
    $(`#fee_${type}`).addClass('active');
    selectedFeeType = type;
    lastSelectedFeeType = type;
    const feeLabel = type === 'transport' ? 'Transport Fare' : `${type.charAt(0).toUpperCase() + type.slice(1)} Fee`;
    $('#selected_fee_type').text(feeLabel);
    $('#selected_fee_due').text('GHS ' + currentTransaction.fees[type].due.toFixed(2));
    keypadValue = currentTransaction.fees[type].amount.toString();
    $('#keypad_display').text(keypadValue);
}

function clearFeeSelection() {
    $('.fee-item').removeClass('active');
    selectedFeeType = null;
    $('#selected_fee_type').text('Amount Tendered');
    $('#selected_fee_due').text('GHS 0.00');
    $('#keypad_display').text($('#amount_tendered_input').val() || '0.00');
}

function restoreFeeSelection() {
    if(lastSelectedFeeType && currentTransaction.fees[lastSelectedFeeType]) {
        selectFee(lastSelectedFeeType);
    }
}

function addDigit(digit) {
    if($('#amount_tendered_input').is(':focus')) {
        const currentVal = $('#amount_tendered_input').val() || '0';
        let newVal = currentVal === '0' && digit !== '.' ? digit : currentVal + digit;
        if(digit === '.' && currentVal.includes('.')) return;
        $('#amount_tendered_input').val(newVal);
        updateTotals();
        return;
    }
    if(!selectedFeeType) { showAjaxModal_alert('Select a fee type first', 'warning'); return; }
    if(keypadValue === '0' && digit !== '.') keypadValue = digit;
    else if(digit === '.' && keypadValue.includes('.')) return;
    else keypadValue += digit;
    $('#keypad_display').text(keypadValue);
}

function clearKeypad() {
    if($('#amount_tendered_input').is(':focus')) {
        $('#amount_tendered_input').val('0');
        updateTotals();
        return;
    }
    keypadValue = '0';
    $('#keypad_display').text(keypadValue);
}

function setupNavigation() {
    navigationElements = [];
    visibleFeeTypes.forEach(type => navigationElements.push(`fee_${type}`));
    navigationElements.push('amount_tendered_input', 'payment_method', 'payment_date', 'save_btn', 'process_btn');
    currentNavIndex = -1;
}

function navigateNext() {
    currentNavIndex++;
    if(currentNavIndex >= navigationElements.length) currentNavIndex = 0;
    
    const element = navigationElements[currentNavIndex];
    if(element.startsWith('fee_')) {
        const feeType = element.replace('fee_', '');
        selectFee(feeType);
    } else if(element === 'amount_tendered_input') {
        $('#amount_tendered_input').focus();
    } else if(element === 'payment_method') {
        $('#payment_method').focus();
    } else if(element === 'payment_date') {
        $('#payment_date').focus();
    } else if(element === 'save_btn') {
        $('button[onclick="saveTransaction()"]').focus();
    } else if(element === 'process_btn') {
        $('button[onclick="processPayment()"]').focus();
    }
}

function applyAmount() {
    if($('#amount_tendered_input').is(':focus')) {
        navigateNext();
        return;
    }
    if(!selectedFeeType) return;
    const amount = parseFloat(keypadValue) || 0;
    currentTransaction.fees[selectedFeeType].amount = amount;
    $(`#amount_${selectedFeeType}`).text(amount.toFixed(2));
    updateTotals();
    
    navigateNext();
}

function updateTotals() {
    let totalDue = 0, totalPaying = 0;
    Object.values(currentTransaction.fees).forEach(f => {
        totalDue += f.due;
        totalPaying += f.amount;
    });
    
    const amountTendered = parseFloat($('#amount_tendered_input').val()) || 0;
    
    $('#total_due').text('GHS ' + totalDue.toFixed(2));
    $('#amount_paying_display').text('GHS ' + totalPaying.toFixed(2));
    $('#amount_tendered_display').text('GHS ' + amountTendered.toFixed(2));
    
    // Only calculate and show change if amount tendered is entered
    if(amountTendered > 0) {
        const change = amountTendered - totalPaying;
        $('#change_display').text('GHS ' + Math.abs(change).toFixed(2));
        $('#change_display').removeClass('text-red-600 text-green-600').addClass(change >= 0 ? 'text-green-600' : 'text-red-600');
    } else {
        $('#change_display').text('GHS 0.00');
        $('#change_display').removeClass('text-red-600 text-green-600').addClass('text-red-600');
    }
    
    currentTransaction.tendered = amountTendered;
}

function saveTransaction() {
    if(!currentTransaction.student_id) return false;
    $.post('<?php echo site_url('admin/save_fee_transaction'); ?>', {
        ...currentTransaction,
        payment_method: $('#payment_method').val(),
        payment_date: $('#payment_date').val()
    }, function(response) {
        const data = JSON.parse(response);
        showAjaxModal_alert(data.message, data.status, false);
        if(data.status === 'success') {
            setTimeout(() => {
                $('.close')[0].click();
                resetForNextStudent();
                loadSavedCount();
            }, 1500);
        }
    });
    return false;
}

function processPayment() {
    if(!currentTransaction.student_id) return false;
    
    // Validate transport direction if transport fee is being collected
    if(currentTransaction.fees.transport.amount > 0 && !currentTransaction.transport_direction) {
        showAjaxModal_alert('Please select transport usage (In/Out/Both/None)', 'warning', false);
        return false;
    }
    
    showAjaxModal_alert('Processing...', 'loading');
    $.post('<?php echo site_url('admin/process_fee_payment'); ?>', {
        student_id: currentTransaction.student_id,
        feeding_amount: currentTransaction.fees.feeding.amount,
        classes_amount: currentTransaction.fees.classes.amount,
        transport_amount: currentTransaction.fees.transport.amount,
        feeding_due: currentTransaction.fees.feeding.due,
        classes_due: currentTransaction.fees.classes.due,
        transport_due: currentTransaction.fees.transport.due,
        transport_direction: currentTransaction.transport_direction,
        payment_method: $('#payment_method').val(),
        payment_date: $('#payment_date').val()
    }).done(function(response) {
        const data = JSON.parse(response);
        $('.close')[0].click();
        if(data.status === 'success') {
            showAjaxModal_alert(data.message, 'success', false);
            setTimeout(function() {
                $('.close')[0].click();
                showPrintPrompt(data);
            }, 2000);
        } else {
            showAjaxModal_alert(data.message, 'error', false);
        }
    }).fail(function() {
        $('.close')[0].click();
        showAjaxModal_alert('An error occurred', 'error', false);
    });
    return false;
}

let printWindowOpened = false;

function showPrintPrompt(data) {
    printWindowOpened = false;
    showConfirmModal(
        'Payment Successful',
        'Do you want to print the receipt?',
        function() {
            if(!printWindowOpened) {
                printWindowOpened = true;
                window.open('<?php echo site_url('admin/print_fee_receipt'); ?>/' + data.student_id + '/' + data.day_timestamp, '_blank');
            }
            $('.close')[0].click();
            resetForNextStudent();
        },
        'Print Receipt',
        'primary'
    );
    
    setTimeout(function() {
        $('#confirm_modal .modal-footer .btn-default').remove();
        $('#confirm_modal .modal-footer').append('<button type="button" class="btn btn-default" onclick="$(\'.close\')[0].click(); resetForNextStudent();">Skip</button>');
    }, 100);
}

function resetForNextStudent() {
    currentTransaction = { student_id: null, fees: {}, tendered: 0 };
    selectedFeeType = null;
    lastSelectedFeeType = null;
    keypadValue = '0';
    $('#transaction_area').hide();
    $('#student_search').val('').focus();
    $('#search_results').html('');
    $('#payment_history').hide();
    $('#keypad_display').text('0.00');
    $('#selected_fee_type').text('None');
    $('#selected_fee_due').text('GHS 0.00');
}

function showSavedTransactions() {
    $.get('<?php echo site_url('admin/get_saved_transactions'); ?>', function(response) {
        const data = JSON.parse(response);
        let html = '';
        data.transactions.forEach(t => {
            html += `<div class="saved-txn" onclick="loadSavedTransaction(${t.id})">
                <div class="font-bold">${t.student_name}</div>
                <div class="text-sm">${t.date} | Total: GHS ${t.total}</div>
            </div>`;
        });
        $('#saved_transactions_list').html(html || '<p style="text-align:center;color:#7f8c8d;padding:40px;">No saved transactions</p>');
        $('#modal_saved_transactions').modal('show');
    });
}

function loadSavedTransaction(id) {
    $('#modal_saved_transactions').modal('hide');
    $.post('<?php echo site_url('admin/load_saved_transaction'); ?>', { id: id }, function(response) {
        const data = JSON.parse(response);
        if(data.status === 'success') {
            loadTransaction(data);
        }
    });
}

function loadSavedCount() {
    $.get('<?php echo site_url('admin/get_saved_transactions'); ?>', function(response) {
        const data = JSON.parse(response);
        $('#saved_count').text(data.transactions.length);
    });
}

$(document).ready(function() {
    $('.datepicker').datepicker({ 
        format: 'dd-mm-yyyy', 
        autoclose: true, 
        todayHighlight: true,
        endDate: '0d'
    }).on('changeDate', function() {
        handleDateChange();
    });
    loadSavedCount();
    handleDateChange();
    
    $('#class_search').select2({
        placeholder: 'Search by Class...',
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        searchByClass();
    });
    
    $(document).on('keydown', function(e) {
        if(!selectedFeeType || $('#student_search').is(':focus') || $('#payment_method').is(':focus') || $('#payment_date').is(':focus') || $('.select2-search__field').is(':focus')) return;
        if(/^[0-9.]$/.test(e.key)) { e.preventDefault(); addDigit(e.key); }
        else if(e.key === 'Backspace' || e.key === 'Delete') { e.preventDefault(); clearKeypad(); }
        else if(e.key === 'Enter') { e.preventDefault(); applyAmount(); }
    });
    
    // Mobile keypad toggle functionality
    $('.mobile-keypad-toggle').on('click', function() {
        $('.pos-right').toggleClass('expanded');
    });
    
    // Auto-collapse keypad when scrolling on mobile
    if(window.innerWidth <= 1024) {
        let scrollTimeout;
        $('.pos-left, .pos-middle').on('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                if($('.pos-right').hasClass('expanded')) {
                    $('.pos-right').removeClass('expanded');
                }
            }, 150);
        });
    }
});

function handleDateChange() {
    const selectedDate = $('#payment_date').val();
    if(!selectedDate) return;
    
    const parts = selectedDate.split('-');
    const selected = new Date(parts[2], parts[1] - 1, parts[0]);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selected.setHours(0, 0, 0, 0);
    
    const diffDays = Math.floor((today - selected) / (1000 * 60 * 60 * 24));
    
    if(diffDays > 0) {
        $('#date_indicator').text('BACKDATE').css({
            'background-color': '#fbbf24',
            'color': '#78350f',
            'display': 'block'
        });
        $('#backdate_warning').show();
        $('#backdate_from').text(selectedDate);
    } else if(diffDays < 0) {
        $('#date_indicator').text('FUTURE').css({
            'background-color': '#ef4444',
            'color': '#fff',
            'display': 'block'
        });
        $('#backdate_warning').hide();
        showAjaxModal_alert('Future dates are not allowed', 'warning', false);
        $('#payment_date').val('<?php echo date('d-m-Y'); ?>');
        setTimeout(() => $('.close')[0].click(), 2000);
    } else {
        $('#date_indicator').hide();
        $('#backdate_warning').hide();
    }
    
    if(currentTransaction.student_id) {
        selectStudent(currentTransaction.student_id);
    }
}

function updateTransportFare() {
    const direction = $('#transport_direction').val();
    const routeFare = currentTransaction.fees.transport.route_fare;
    let amount = 0;
    
    if(direction === 'in' || direction === 'out') {
        amount = routeFare;
    } else if(direction === 'both') {
        amount = routeFare * 2;
    }
    
    currentTransaction.fees.transport.amount = amount;
    currentTransaction.fees.transport.due = amount;
    currentTransaction.transport_direction = direction;
    
    $('#amount_transport').text(amount.toFixed(2));
    $('#fee_transport .text-red-600').text('Due: GHS ' + amount.toFixed(2));
    
    if(selectedFeeType === 'transport') {
        $('#selected_fee_due').text('GHS ' + amount.toFixed(2));
        keypadValue = amount.toString();
        $('#keypad_display').text(keypadValue);
    }
    
    updateTotals();
}

// Coupon Modal Functions
function showCouponModal() {
    const modalHtml = `
        <div id="coupon_modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Print Dining Coupon</h3>
                <div class="mb-4">
                    <label class="block text-base font-bold mb-2">Select Date</label>
                    <input type="date" id="coupon_date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border-2 rounded-lg text-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-base font-bold mb-2">Class Filter</label>
                    <select id="coupon_class_filter" class="w-full px-4 py-3 border-2 rounded-lg text-lg">
                        <option value="all">All Classes</option>
                        <?php getFullClassList(); ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button onclick="closeCouponModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 rounded-lg text-lg">Cancel</button>
                    <button onclick="generateCashierCoupon()" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg text-lg">Generate</button>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHtml);
}

function closeCouponModal() {
    $('#coupon_modal').remove();
}

function generateCashierCoupon() {
    const date = $('#coupon_date').val();
    const classFilter = $('#coupon_class_filter').val();
    
    if(!date) {
        alert('Please select a date');
        return;
    }
    
    closeCouponModal();
    
    $.ajax({
        url: '<?php echo site_url("dining_coupon/generateCashierCoupon"); ?>',
        type: 'POST',
        data: { date: date, class_filter: classFilter },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(response.html);
            printWindow.document.close();
            setTimeout(function() {
                printWindow.print();
            }, 500);
        } else if(response.status === 'warning') {
            alert(response.message);
        } else {
            alert(response.message || 'An error occurred');
        }
    }).fail(function() {
        alert('An error occurred');
    });
}

// Mobile Keypad Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const keypadToggle = document.querySelector('.mobile-keypad-toggle');
    const posRight = document.querySelector('.pos-right');
    
    if (keypadToggle && posRight) {
        keypadToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            posRight.classList.toggle('expanded');
        });
        
        // Close keypad when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!posRight.contains(e.target) && posRight.classList.contains('expanded')) {
                    posRight.classList.remove('expanded');
                }
            }
        });
    }
});
</script>

<!-- ★★★ MOBILE RESPONSIVE CSS v2.1 - LAST UPDATE: 2026-09-25 ★★★ -->
<!-- If you can see this comment in browser source, the file is loading correctly -->
<style>
@media (max-width: 768px) {
    /* Page Title and Headers */
    .pos-left h2.text-xl,
    .pos-middle h2.text-xl,
    .transaction-header h2.text-xl {
        font-size: 19px !important;
        font-weight: 800 !important;
        margin-bottom: 14px !important;
        line-height: 1.25 !important;
        color: #0f172a !important;
        letter-spacing: -0.02em !important;
    }
    
    /* Transaction Header Layout */
    .transaction-header {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
        margin-bottom: 18px !important;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        padding: 14px !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
    }
    
    .transaction-header-buttons {
        width: 100% !important;
        display: flex !important;
        flex-direction: row !important;
        gap: 8px !important;
    }
    
    .transaction-header-buttons button {
        flex: 1 !important;
        font-size: 13px !important;
        padding: 10px 8px !important;
        white-space: nowrap !important;
        min-height: 44px !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    /* Student Info Card */
    #student_info {
        padding: 16px !important;
        border-radius: 12px !important;
        margin-bottom: 16px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
        border: 1px solid #e0e7ff !important;
    }
    
    #student_info div.font-bold.text-lg,
    #student_info .font-bold.text-lg {
        font-size: 17px !important;
        line-height: 1.3 !important;
        margin-bottom: 6px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
    }
    
    #student_info div.text-sm,
    #student_info .text-sm {
        font-size: 13px !important;
        line-height: 1.5 !important;
        color: #64748b !important;
    }
    
    /* FEE CARDS - CRITICAL MOBILE IMPROVEMENTS */
    /* Fee Items Container */
    #fee_items {
        display: flex !important;
        flex-direction: column !important;
        gap: 14px !important;
        margin-bottom: 18px !important;
        padding: 0 !important;
    }
    
    /* Individual Fee Card - Enhanced Card Design */
    .fee-item {
        display: flex !important;
        flex-direction: column !important;
        padding: 16px !important;
        margin-bottom: 0 !important;
        border-radius: 12px !important;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border: 2px solid #e2e8f0 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
        transition: all 0.25s ease !important;
    }
    
    .fee-item:active {
        transform: scale(0.98) !important;
    }
    
    .fee-item.active {
        border-color: #3b82f6 !important;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
        box-shadow: 0 4px 12px rgba(59,130,246,0.25) !important;
        transform: translateY(-2px) !important;
    }
    
    /* Fee Card Inner Flex Container */
    .fee-item > div.flex {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        gap: 14px !important;
    }
    
    /* Fee Card Left Side (Title & Due Amount) */
    .fee-item > div.flex > div:first-child {
        flex: 1 !important;
        min-width: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 5px !important;
    }
    
    /* Fee Title - Enhanced */
    .fee-item > div.flex > div:first-child > div.font-bold,
    .fee-item .font-bold {
        font-size: 16px !important;
        font-weight: 800 !important;
        margin-bottom: 0 !important;
        line-height: 1.3 !important;
        color: #0f172a !important;
        letter-spacing: -0.01em !important;
    }
    
    /* Due Amount Label - Enhanced Visibility */
    .fee-item > div.flex > div:first-child > div.text-sm,
    .fee-item .text-sm.text-red-600 {
        font-size: 13px !important;
        line-height: 1.3 !important;
        font-weight: 700 !important;
        color: #dc2626 !important;
        margin-top: 3px !important;
        background: #fee2e2 !important;
        padding: 3px 8px !important;
        border-radius: 4px !important;
        display: inline-block !important;
        width: fit-content !important;
    }
    
    /* Transport Dropdown inside Fee Card */
    .fee-item > div.flex > div:first-child > div.mt-2,
    .fee-item .mt-2 {
        margin-top: 10px !important;
        width: 100% !important;
    }
    
    .fee-item select#transport_direction {
        width: 100% !important;
        padding: 10px !important;
        font-size: 14px !important;
        border-radius: 8px !important;
        border: 2px solid #cbd5e1 !important;
        background: white !important;
        font-weight: 600 !important;
    }
    
    /* Fee Card Right Side (Payment Amount) - Enhanced Badge */
    .fee-item > div.flex > div:last-child,
    .fee-item > div.flex > div.text-right {
        flex-shrink: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 90px !important;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%) !important;
        padding: 10px 12px !important;
        border-radius: 10px !important;
        border: 2px solid #86efac !important;
    }
    
    .fee-item.active > div.flex > div:last-child {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
        border-color: #60a5fa !important;
    }
    
    /* Payment Amount Display - Larger and Bolder */
    .fee-item > div.flex > div:last-child > div.text-2xl,
    .fee-item .text-2xl.font-bold,
    .fee-item div[id^="amount_"] {
        font-size: 22px !important;
        font-weight: 900 !important;
        line-height: 1.1 !important;
        color: #15803d !important;
        white-space: nowrap !important;
        letter-spacing: -0.02em !important;
    }
    
    .fee-item.active div[id^="amount_"] {
        color: #1d4ed8 !important;
    }
    
    /* PAYMENT SUMMARY SECTION - Enhanced Card Design */
    .bg-gray-100.p-4 {
        padding: 18px !important;
        border-radius: 12px !important;
        margin-top: 16px !important;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%) !important;
        border: 2px solid #e5e7eb !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    }
    
    /* Payment Summary Rows */
    .bg-gray-100 .flex.justify-between {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 12px !important;
        padding: 6px 0 !important;
    }
    
    .bg-gray-100 .flex.justify-between:last-child {
        margin-bottom: 0 !important;
    }
    
    /* Total Due Row (Prominent) - Enhanced */
    .bg-gray-100 .flex.justify-between.text-xl {
        padding: 12px 0 !important;
        margin-bottom: 14px !important;
        border-bottom: 3px solid #d1d5db !important;
        background: linear-gradient(90deg, rgba(239,246,255,0.5) 0%, transparent 100%) !important;
        padding-left: 8px !important;
        margin-left: -8px !important;
        margin-right: -8px !important;
        padding-right: 8px !important;
        border-radius: 6px !important;
    }
    
    .bg-gray-100 .flex.justify-between.text-xl > span {
        font-size: 17px !important;
        font-weight: 900 !important;
        color: #0f172a !important;
        letter-spacing: -0.01em !important;
    }
    
    /* Other Payment Summary Rows */
    .bg-gray-100 .flex.justify-between.text-lg > span:first-child {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #374151 !important;
    }
    
    .bg-gray-100 .flex.justify-between.text-lg > span:last-child {
        font-size: 15px !important;
        font-weight: 800 !important;
    }
    
    /* Specific Amount Colors - Enhanced with Backgrounds */
    #amount_paying_display {
        color: #1d4ed8 !important;
        background: #dbeafe !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
    }
    
    #amount_tendered_display {
        color: #15803d !important;
        background: #dcfce7 !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
    }
    
    #change_display {
        color: #dc2626 !important;
        background: #fee2e2 !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
    }
    
    /* AMOUNT TENDERED INPUT FIELD - Enhanced */
    .bg-gray-50.border-2 {
        padding: 14px !important;
        margin-top: 16px !important;
        border-radius: 12px !important;
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%) !important;
        border: 2px solid #cbd5e1 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;
    }
    
    .bg-gray-50.border-2 label {
        font-size: 15px !important;
        font-weight: 800 !important;
        margin-bottom: 10px !important;
        color: #1f2937 !important;
        display: block !important;
        letter-spacing: -0.01em !important;
    }
    
    #amount_tendered_input {
        width: 100% !important;
        padding: 14px !important;
        font-size: 17px !important;
        font-weight: 700 !important;
        border-width: 2px !important;
        border-radius: 10px !important;
        border-color: #9ca3af !important;
        background: white !important;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05) !important;
    }
    
    #amount_tendered_input:focus {
        border-color: #3b82f6 !important;
        outline: none !important;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.15) !important;
    }
    
    /* PAYMENT METHOD GRID - Enhanced */
    #transaction_area .grid.grid-cols-2 {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 12px !important;
        margin-top: 16px !important;
    }
    
    #payment_method,
    #payment_date {
        padding: 14px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        border-width: 2px !important;
        border-radius: 10px !important;
        width: 100% !important;
        background: white !important;
        border-color: #9ca3af !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
    }
    
    #payment_method:focus,
    #payment_date:focus {
        border-color: #3b82f6 !important;
        outline: none !important;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.15) !important;
    }
    
    /* TRANSACTION BUTTONS - Enhanced with Gradients */
    .transaction-buttons-container {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        margin-top: 18px !important;
    }
    
    .transaction-buttons-container button {
        width: 100% !important;
        padding: 16px !important;
        font-size: 15px !important;
        font-weight: 800 !important;
        min-height: 52px !important;
        border-radius: 10px !important;
        transition: all 0.2s !important;
        box-shadow: 0 4px 10px rgba(0,0,0,0.12) !important;
        letter-spacing: -0.01em !important;
    }
    
    .transaction-buttons-container button:active {
        transform: scale(0.97) !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important;
    }
    
    /* Input Fields - Enhanced */
    input#student_search,
    select#class_search {
        padding: 14px !important;
        font-size: 16px !important;
        border-width: 2px !important;
        border-radius: 10px !important;
        font-weight: 500 !important;
    }
    
    /* Keypad Display */
    .pos-display {
        padding: 14px !important;
        margin-bottom: 16px !important;
        border-radius: 10px !important;
    }
    
    .pos-display .text-sm {
        font-size: 11px !important;
        margin-bottom: 7px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }
    
    #selected_fee_type.text-xl {
        font-size: 14px !important;
        font-weight: 700 !important;
    }
    
    #selected_fee_due.text-2xl {
        font-size: 16px !important;
        font-weight: 800 !important;
    }
    
    #keypad_display {
        font-size: 26px !important;
        font-weight: 800 !important;
    }
    
    /* Keypad Keys - Enhanced */
    .pos-keypad {
        gap: 8px !important;
    }
    
    .pos-key {
        padding: 14px 10px !important;
        font-size: 18px !important;
        font-weight: 700 !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important;
    }
    
    .pos-key:active {
        transform: scale(0.95) !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12) !important;
    }
}
</style>
