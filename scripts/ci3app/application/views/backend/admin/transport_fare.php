<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-8 py-6 rounded-t-lg shadow-md">
            <div class="flex justify-between items-center">
            <h2 class="text-3xl font-bold text-gray-50 drop-shadow-lg"><?php echo get_phrase('transport_fare_collection'); ?></h2>
                <button onclick="openBulkPaymentModal()" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fa fa-plus"></i> <?php echo get_phrase('bulk_payment'); ?>
                </button>
            </div>
        </div>
        
        <div class="p-8">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table id="transport_fare_table" class="w-full text-left text-gray-700">
                    <thead class="text-sm text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-base"><input type="checkbox" id="select_all" class="w-5 h-5 cursor-pointer"></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('student_code'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('name'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('class'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('route'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('fare'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('paid'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('balance'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Payment Modal with Balance and History -->
<div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900"><?php echo get_phrase('record_payment'); ?></h3>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <!-- Balance Summary Card -->
            <div id="balance_summary" class="mb-4 p-4 bg-gray-50 rounded-lg border-2">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <label class="text-sm text-gray-600"><?php echo get_phrase('route_fare'); ?></label>
                        <div id="route_fare_display" class="text-xl font-bold text-gray-800">GHS 0.00</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600"><?php echo get_phrase('total_paid'); ?></label>
                        <div id="total_paid_display" class="text-xl font-bold text-blue-600">GHS 0.00</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600"><?php echo get_phrase('balance'); ?></label>
                        <div id="balance_display" class="text-xl font-bold text-red-600">GHS 0.00</div>
                    </div>
                </div>
            </div>
            
            <!-- Payment History -->
            <div id="payment_history_section" class="mb-4">
                <h4 class="text-sm font-bold text-gray-700 mb-2"><?php echo get_phrase('payment_history'); ?></h4>
                <div id="payment_history" class="max-h-40 overflow-y-auto border rounded p-2 bg-gray-50">
                    <div class="text-center text-gray-500 text-sm"><?php echo get_phrase('loading'); ?>...</div>
                </div>
            </div>
            
            <?php echo form_open('admin/transport_fare/record_payment', array('id' => 'paymentForm')); ?>
                <input type="hidden" id="student_id" name="student_id">
                <input type="hidden" id="transport_id" name="transport_id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('student'); ?></label>
                    <input type="text" id="student_name" readonly class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('amount'); ?></label>
                    <input type="number" step="0.01" name="amount_paid" id="amount_paid" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" onchange="updateBalancePreview()">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('payment_date'); ?></label>
                    <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('payment_method'); ?></label>
                    <select name="payment_method" id="payment_method" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value="cash"><?php echo get_phrase('cash'); ?></option>
                        <option value="bank_transfer"><?php echo get_phrase('bank_transfer'); ?></option>
                        <option value="mobile_money"><?php echo get_phrase('mobile_money'); ?></option>
                        <option value="cheque"><?php echo get_phrase('cheque'); ?></option>
                    </select>
                </div>
                
                <div id="momo_fields" class="hidden">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('mobile_money_number'); ?></label>
                        <input type="text" name="momo_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('transaction_id'); ?></label>
                        <input type="text" name="transaction_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                </div>
                
                <div id="cheque_fields" class="hidden">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('bank_name'); ?></label>
                        <input type="text" name="bank_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('cheque_number'); ?></label>
                        <input type="text" name="cheque_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('remarks'); ?></label>
                    <textarea name="remarks" rows="2" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex-1">
                        <?php echo get_phrase('record_payment'); ?>
                    </button>
                    <button type="button" onclick="closePaymentModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        <?php echo get_phrase('cancel'); ?>
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Bulk Payment Modal -->
<div id="bulkPaymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900"><?php echo get_phrase('bulk_payment'); ?></h3>
                <button onclick="closeBulkPaymentModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <?php echo form_open('admin/transport_fare/bulk_payment', array('id' => 'bulkPaymentForm')); ?>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('selected_students'); ?></label>
                    <div id="selected_students_count" class="p-2 bg-blue-100 rounded text-blue-800 font-bold">0 <?php echo get_phrase('students_selected'); ?></div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('amount'); ?> (<?php echo get_phrase('per_student'); ?>)</label>
                    <input type="number" step="0.01" name="amount_paid" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('payment_date'); ?></label>
                    <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('payment_method'); ?></label>
                    <select name="payment_method" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value="cash"><?php echo get_phrase('cash'); ?></option>
                        <option value="bank_transfer"><?php echo get_phrase('bank_transfer'); ?></option>
                        <option value="mobile_money"><?php echo get_phrase('mobile_money'); ?></option>
                        <option value="cheque"><?php echo get_phrase('cheque'); ?></option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('remarks'); ?></label>
                    <textarea name="remarks" rows="2" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex-1">
                        <?php echo get_phrase('record_payments'); ?>
                    </button>
                    <button type="button" onclick="closeBulkPaymentModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        <?php echo get_phrase('cancel'); ?>
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
var selectedStudents = [];
var currentRouteFare = 0;
var currentTotalPaid = 0;

$(document).ready(function() {
    var table = $('#transport_fare_table').DataTable({
        ajax: {
            url: '<?php echo site_url("admin/get_transport_fare_students"); ?>',
            dataSrc: 'data'
        },
        columns: [
            {
                data: null,
                className: 'px-6 py-4',
                orderable: false,
                render: function(data) {
                    return '<input type="checkbox" class="student_checkbox w-5 h-5 cursor-pointer" data-student-id="' + data.student_id + '" data-transport-id="' + data.transport_id + '">';
                }
            },
            { data: 'student_code', className: 'px-6 py-4 font-semibold text-gray-900 text-base' },
            { data: 'name', className: 'px-6 py-4 text-base' },
            { data: 'class', className: 'px-6 py-4 text-base' },
            { data: 'route_name', className: 'px-6 py-4 text-base' },
            { data: 'route_fare', className: 'px-6 py-4 text-base' },
            { data: 'total_paid', className: 'px-6 py-4 text-base' },
            { 
                data: 'balance', 
                className: 'px-6 py-4 text-base',
                render: function(data) {
                    var color = parseFloat(data.replace(',', '')) > 0 ? 'text-red-600' : 'text-green-600';
                    return '<span class="font-semibold ' + color + '">' + data + '</span>';
                }
            },
            {
                data: null,
                className: 'px-6 py-4',
                render: function(data) {
                    var routeFare = parseFloat(data.route_fare.replace(/,/g, '')) || 0;
                    var nameEscaped = data.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    var buttons = '<button onclick="openPaymentModal(' + data.student_id + ', \'' + nameEscaped + '\', ' + data.transport_id + ', ' + routeFare + ')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm mr-1"><?php echo get_phrase('pay'); ?></button>';
                    buttons += '<button onclick="printReceipt(' + data.student_id + ')" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-sm"><?php echo get_phrase('receipt'); ?></button>';
                    return buttons;
                }
            }
        ],
        pageLength: 25,
        createdRow: function(row, data, dataIndex) {
            $(row).addClass('hover:bg-gray-100 cursor-pointer');
            if (dataIndex % 2 === 0) {
                $(row).addClass('bg-gray-50');
            }
        }
    });
    
    // Row click to toggle checkbox
    $('#transport_fare_table tbody').on('click', 'tr', function(e) {
        if (!$(e.target).is('input, button, a')) {
            var checkbox = $(this).find('.student_checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });
    
    // Select all checkbox
    $('#select_all').on('change', function() {
        $('.student_checkbox').prop('checked', $(this).prop('checked'));
        updateSelectedStudents();
    });
    
    // Individual checkbox change
    $(document).on('change', '.student_checkbox', function() {
        updateSelectedStudents();
    });
});

function updateSelectedStudents() {
    selectedStudents = [];
    $('.student_checkbox:checked').each(function() {
        selectedStudents.push({
            student_id: $(this).data('student-id'),
            transport_id: $(this).data('transport-id')
        });
    });
    $('#selected_students_count').text(selectedStudents.length + ' <?php echo get_phrase('students_selected'); ?>');
}

function openPaymentModal(studentId, studentName, transportId, routeFare) {
    $('#student_id').val(studentId);
    $('#student_name').val(studentName);
    $('#transport_id').val(transportId);
    currentRouteFare = parseFloat(routeFare || 0);
    
    // Load balance info and payment history
    loadStudentPaymentInfo(studentId, transportId);
    
    $('#paymentModal').removeClass('hidden');
}

function loadStudentPaymentInfo(studentId, transportId) {
    $.ajax({
        url: '<?php echo site_url("admin/transport_fare/get_student_payment_info"); ?>',
        type: 'POST',
        data: {
            student_id: studentId,
            transport_id: transportId
        },
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) { return; }
            }
            
            if (response) {
                currentTotalPaid = parseFloat(response.total_paid || 0);
                var balance = currentRouteFare - currentTotalPaid;
                
                $('#route_fare_display').text('GHS ' + parseFloat(currentRouteFare).toFixed(2));
                $('#total_paid_display').text('GHS ' + parseFloat(currentTotalPaid).toFixed(2));
                $('#balance_display').text('GHS ' + parseFloat(balance).toFixed(2));
                $('#balance_display').removeClass('text-red-600 text-green-600').addClass(balance > 0 ? 'text-red-600' : 'text-green-600');
                
                // Display payment history
                if (response.history && response.history.length > 0) {
                    var historyHtml = '<table class="w-full text-sm">';
                    historyHtml += '<tr class="font-bold border-b"><td>Date</td><td>Amount</td><td>Method</td><td>Receipt</td></tr>';
                    response.history.forEach(function(payment) {
                        historyHtml += '<tr class="border-b"><td>' + payment.date + '</td><td>GHS ' + parseFloat(payment.amount).toFixed(2) + '</td><td>' + payment.method + '</td><td><a href="javascript:void(0)" onclick="printReceiptByNumber(\'' + payment.receipt_number + '\')" class="text-blue-600">' + payment.receipt_number + '</a></td></tr>';
                    });
                    historyHtml += '</table>';
                    $('#payment_history').html(historyHtml);
                } else {
                    $('#payment_history').html('<div class="text-center text-gray-500 text-sm"><?php echo get_phrase('no_payment_history'); ?></div>');
                }
            }
        }
    });
}

function updateBalancePreview() {
    var amountPaid = parseFloat($('#amount_paid').val() || 0);
    var newBalance = (currentRouteFare - currentTotalPaid) - amountPaid;
    $('#balance_display').text('GHS ' + parseFloat(newBalance).toFixed(2));
    $('#balance_display').removeClass('text-red-600 text-green-600').addClass(newBalance > 0 ? 'text-red-600' : 'text-green-600');
}

function closePaymentModal() {
    $('#paymentModal').addClass('hidden');
    $('#paymentForm')[0].reset();
    $('#momo_fields').addClass('hidden');
    $('#cheque_fields').addClass('hidden');
    selectedStudents = [];
}

function openBulkPaymentModal() {
    updateSelectedStudents();
    if (selectedStudents.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_students_first'); ?>', 'error');
        return;
    }
    $('#bulkPaymentModal').removeClass('hidden');
}

function closeBulkPaymentModal() {
    $('#bulkPaymentModal').addClass('hidden');
    $('#bulkPaymentForm')[0].reset();
}

function printReceipt(studentId) {
    // Get latest receipt for student
    $.ajax({
        url: '<?php echo site_url("admin/transport_fare/get_latest_receipt"); ?>',
        type: 'POST',
        data: { student_id: studentId },
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) { return; }
            }
            if (response && response.receipt_number) {
                printReceiptByNumber(response.receipt_number);
            } else {
                showAjaxModal_alert('<?php echo get_phrase('no_receipt_found'); ?>', 'error');
            }
        }
    });
}

function printReceiptByNumber(receiptNumber) {
    window.open('<?php echo site_url("admin/transport_fare/transport_fare_receipt"); ?>/' + receiptNumber, '_blank');
}

$('#payment_method').on('change', function() {
    var method = $(this).val();
    $('#momo_fields').addClass('hidden');
    $('#cheque_fields').addClass('hidden');
    
    if (method === 'mobile_money') {
        $('#momo_fields').removeClass('hidden');
    } else if (method === 'cheque') {
        $('#cheque_fields').removeClass('hidden');
    }
});

$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    
    var amount = parseFloat($('input[name="amount_paid"]').val());
    if (isNaN(amount) || amount <= 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_enter_valid_amount'); ?>', 'error');
        return false;
    }
    
    if (!$('input[name="payment_date"]').val()) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_payment_date'); ?>', 'error');
        return false;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('processing_payment'); ?>...', 'loading');
    
    var formData = $(this).serialize();
    
    $.ajax({
        url: '<?php echo site_url("admin/transport_fare/record_payment"); ?>',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) { response = { status: 'error', message: response }; }
            }
            if (response && response.status === 'success') {
                var message = response.message + '<br>Receipt: ' + (response.receipt_number || '');
                message += '<br><button onclick="printReceiptByNumber(\'' + response.receipt_number + '\')" class="mt-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded"><?php echo get_phrase('print_receipt'); ?></button>';
                showAjaxModal_alert(message, 'success');
                closePaymentModal();
                setTimeout(function() {
                    $('#transport_fare_table').DataTable().ajax.reload(null, false);
                }, 2000);
            } else {
                var errorMsg = (response && response.message) ? response.message : '<?php echo get_phrase('error_occurred'); ?>';
                showAjaxModal_alert(errorMsg, 'error');
            }
        },
        error: function(xhr, status, error) {
            var errorMsg = '<?php echo get_phrase('failed_to_record_payment'); ?>. ';
            if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    } else {
                        errorMsg += '<?php echo get_phrase('server_error'); ?>: ' + xhr.status;
                    }
                } catch(e) {
                    errorMsg += '<?php echo get_phrase('check_connection'); ?>.';
                }
            } else {
                errorMsg += '<?php echo get_phrase('try_again'); ?>.';
            }
            showAjaxModal_alert(errorMsg, 'error');
        }
    });
});

$('#bulkPaymentForm').on('submit', function(e) {
    e.preventDefault();
    
    if (selectedStudents.length === 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_students'); ?>', 'error');
        return false;
    }
    
    var amount = parseFloat($('#bulkPaymentForm input[name="amount_paid"]').val());
    if (isNaN(amount) || amount <= 0) {
        showAjaxModal_alert('<?php echo get_phrase('please_enter_valid_amount'); ?>', 'error');
        return false;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('processing_payments'); ?>...', 'loading');
    
    var formData = $(this).serialize();
    formData += '&student_ids=' + JSON.stringify(selectedStudents);
    
    $.ajax({
        url: '<?php echo site_url("admin/transport_fare/bulk_payment"); ?>',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) { response = { status: 'error', message: response }; }
            }
            if (response && response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                closeBulkPaymentModal();
                $('.student_checkbox').prop('checked', false);
                $('#select_all').prop('checked', false);
                selectedStudents = [];
                setTimeout(function() {
                    $('#transport_fare_table').DataTable().ajax.reload(null, false);
                }, 2000);
            } else {
                var errorMsg = (response && response.message) ? response.message : '<?php echo get_phrase('error_occurred'); ?>';
                showAjaxModal_alert(errorMsg, 'error');
            }
        },
        error: function(xhr, status, error) {
            showAjaxModal_alert('<?php echo get_phrase('failed_to_record_payments'); ?>', 'error');
        }
    });
});
</script>
