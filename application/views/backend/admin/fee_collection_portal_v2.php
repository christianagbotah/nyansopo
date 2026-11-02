<?php
// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');
?>
<style>
.pos-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 20px;
    padding: 20px;
    min-height: 100vh;
    background: #f5f7fa;
}

.left-panel {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.right-panel {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.panel-header {
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e6ed;
}

.student-search-box {
    margin-bottom: 20px;
}

.saved-transactions-list {
    max-height: 400px;
    overflow-y: auto;
}

.saved-item {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.saved-item:hover {
    background: #ffe69c;
    transform: translateX(4px);
}

.saved-item .student-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.saved-item .details {
    font-size: 12px;
    color: #666;
}

.transaction-interface {
    display: none;
}

.transaction-interface.active {
    display: block;
}

.student-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.student-info-card h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
}

.student-info-card .meta {
    font-size: 14px;
    opacity: 0.9;
}

.fee-items-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.fee-item {
    background: #f8f9fa;
    border: 2px solid #e0e6ed;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.fee-item.selected {
    border-color: #667eea;
    background: #eff3ff;
}

.fee-item .icon {
    font-size: 28px;
    margin-bottom: 8px;
}

.fee-item .label {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.fee-item .amount-input {
    width: 100%;
    padding: 8px;
    border: 2px solid #e0e6ed;
    border-radius: 6px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
}

.fee-item.selected .amount-input {
    border-color: #667eea;
}

.summary-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e6ed;
}

.summary-row.total {
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
    border-bottom: none;
    padding-top: 15px;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn-save {
    background: #ffc107;
    color: #333;
    border: none;
    padding: 15px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save:hover {
    background: #ffb300;
    transform: translateY(-2px);
}

.btn-process {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-process:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(17,153,142,0.3);
}

.payment-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.payment-options select {
    padding: 12px;
    border: 2px solid #e0e6ed;
    border-radius: 8px;
    font-size: 14px;
}

@media (max-width: 1200px) {
    .pos-layout {
        grid-template-columns: 1fr;
    }
    
    .left-panel {
        position: relative;
        top: 0;
    }
    
    .fee-items-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="pos-layout">
    <!-- Left Panel: Student Selection & Saved Transactions -->
    <div class="left-panel">
        <div class="panel-header">
            <i class="fa fa-search"></i> Select Student
        </div>
        
        <div class="student-search-box">
            <select class="form-control select2" id="student_selector" style="width: 100%;">
                <option value="">Search student...</option>
                <?php
                $running_year = get_settings('running_year');
                $running_term = get_settings('running_term');
                
                $students = $this->db->query("
                    SELECT s.student_id, s.name, s.student_code, c.name as class_name
                    FROM student s
                    INNER JOIN enroll e ON s.student_id = e.student_id
                    INNER JOIN class c ON e.class_id = c.class_id
                    WHERE e.year = ? AND e.term = ?
                    ORDER BY s.name
                ", [$running_year, $running_term])->result_array();
                
                foreach ($students as $student):
                ?>
                <option value="<?php echo $student['student_id']; ?>">
                    <?php echo $student['student_code'] . ' - ' . $student['name'] . ' (' . $student['class_name'] . ')'; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="panel-header" style="margin-top: 30px;">
            <i class="fa fa-clock"></i> Saved Transactions (<span id="saved_count">0</span>)
        </div>
        
        <div class="saved-transactions-list" id="saved_list">
            <p style="text-align:center;color:#999;padding:20px;">No saved transactions</p>
        </div>
    </div>
    
    <!-- Right Panel: Transaction Interface -->
    <div class="right-panel">
        <div class="transaction-interface" id="transaction_interface">
            <div class="student-info-card" id="student_info">
                <!-- Student info will be loaded here -->
            </div>
            
            <div class="fee-items-grid" id="fee_items">
                <!-- Fee items will be loaded here -->
            </div>
            
            <div class="payment-options">
                <select id="payment_method">
                    <?php
                    $payment_methods = $this->db->where('is_active', 1)->order_by('display_order')->get('payment_methods')->result_array();
                    foreach($payment_methods as $method):
                    ?>
                    <option value="<?= $method['id'] ?>"><?= $method['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" id="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="summary-section">
                <div class="summary-row">
                    <span>Feeding:</span>
                    <span id="sum_feeding">GHS 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Classes:</span>
                    <span id="sum_classes">GHS 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Transport:</span>
                    <span id="sum_transport">GHS 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>TOTAL:</span>
                    <span id="sum_total">GHS 0.00</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn-save" onclick="saveTransaction()">
                    <i class="fa fa-save"></i> Save for Later
                </button>
                <button class="btn-process" onclick="processPayment()">
                    <i class="fa fa-check-circle"></i> Process Payment
                </button>
            </div>
        </div>
        
        <div id="empty_state" style="text-align:center;padding:100px 20px;color:#999;">
            <i class="fa fa-hand-pointer" style="font-size:64px;margin-bottom:20px;opacity:0.3;"></i>
            <h3>Select a student to begin transaction</h3>
            <p>Search and select a student from the left panel or load a saved transaction</p>
        </div>
    </div>
</div>

<script>
let currentTransaction = {
    student_id: null,
    feeding: 0,
    classes: 0,
    transport: 0
};

$(document).ready(function() {
    $('#student_selector').select2({
        placeholder: 'Search student...',
        allowClear: true
    }).on('change', function() {
        loadStudent($(this).val());
    });
    
    loadSavedTransactions();
});

function loadStudent(studentId) {
    if (!studentId) {
        $('#transaction_interface').removeClass('active');
        $('#empty_state').show();
        return;
    }
    
    $.get('<?php echo site_url('admin/get_student_outstanding_fees'); ?>', {student_id: studentId, payment_date: $('#payment_date').val()}, function(response) {
        const data = JSON.parse(response);
        if (data.status === 'success') {
            currentTransaction.student_id = studentId;
            displayTransaction(data);
        }
    });
}

function displayTransaction(data) {
    $('#empty_state').hide();
    $('#transaction_interface').addClass('active');
    
    // Student info
    let infoHtml = `
        <h3>${data.student.name}</h3>
        <div class="meta">${data.student.student_code} | ${data.class_name} ${data.class_numeric}</div>
    `;
    $('#student_info').html(infoHtml);
    
    // Fee items
    let feeHtml = '';
    if (!data.hide_feeding) {
        feeHtml += `
            <div class="fee-item" onclick="selectFee('feeding')">
                <div class="icon" style="color:#f093fb;"><i class="fa fa-utensils"></i></div>
                <div class="label">Feeding</div>
                <div style="font-size:12px;color:#999;margin-bottom:8px;">Due: GHS ${data.feeding_due.toFixed(2)}</div>
                <input type="number" class="amount-input" id="feeding_amount" value="0" step="0.01" onchange="updateSummary()">
            </div>
        `;
    }
    
    if (!data.hide_classes) {
        feeHtml += `
            <div class="fee-item" onclick="selectFee('classes')">
                <div class="icon" style="color:#30cfd0;"><i class="fa fa-book"></i></div>
                <div class="label">Classes</div>
                <div style="font-size:12px;color:#999;margin-bottom:8px;">Due: GHS ${data.classes_due.toFixed(2)}</div>
                <input type="number" class="amount-input" id="classes_amount" value="0" step="0.01" onchange="updateSummary()">
            </div>
        `;
    }
    
    if (!data.hide_transport) {
        feeHtml += `
            <div class="fee-item" onclick="selectFee('transport')">
                <div class="icon" style="color:#667eea;"><i class="fa fa-bus"></i></div>
                <div class="label">Transport</div>
                <div style="font-size:12px;color:#999;margin-bottom:8px;">Due: GHS ${data.transport_due.toFixed(2)}</div>
                <input type="number" class="amount-input" id="transport_amount" value="0" step="0.01" onchange="updateSummary()">
            </div>
        `;
    }
    
    $('#fee_items').html(feeHtml);
    updateSummary();
}

function selectFee(type) {
    $('.fee-item').removeClass('selected');
    $(`#${type}_amount`).closest('.fee-item').addClass('selected');
    $(`#${type}_amount`).focus();
}

function updateSummary() {
    const feeding = parseFloat($('#feeding_amount').val() || 0);
    const classes = parseFloat($('#classes_amount').val() || 0);
    const transport = parseFloat($('#transport_amount').val() || 0);
    
    $('#sum_feeding').text('GHS ' + feeding.toFixed(2));
    $('#sum_classes').text('GHS ' + classes.toFixed(2));
    $('#sum_transport').text('GHS ' + transport.toFixed(2));
    $('#sum_total').text('GHS ' + (feeding + classes + transport).toFixed(2));
    
    currentTransaction.feeding = feeding;
    currentTransaction.classes = classes;
    currentTransaction.transport = transport;
}

function saveTransaction() {
    if (!currentTransaction.student_id) return;
    
    $.post('<?php echo site_url('admin/save_fee_transaction'); ?>', {
        student_id: currentTransaction.student_id,
        fees: currentTransaction,
        payment_method: $('#payment_method').val(),
        payment_date: $('#payment_date').val()
    }, function(response) {
        const data = JSON.parse(response);
        showAjaxModal_alert(data.message, data.status, false);
        if (data.status === 'success') {
            resetTransaction();
            loadSavedTransactions();
        }
    });
}

function processPayment() {
    if (!currentTransaction.student_id) return;
    
    showAjaxModal_alert('Processing...', 'loading');
    $.post('<?php echo site_url('admin/process_fee_payment'); ?>', {
        student_id: currentTransaction.student_id,
        feeding_amount: currentTransaction.feeding,
        classes_amount: currentTransaction.classes,
        transport_amount: currentTransaction.transport,
        payment_method: $('#payment_method').val(),
        payment_date: $('#payment_date').val()
    }).done(function(response) {
        const data = JSON.parse(response);
        if (data.status === 'success') {
            showAjaxModal_alert(data.message, 'success', false);
            setTimeout(() => {
                $('.close')[0].click();
                resetTransaction();
            }, 2000);
        } else {
            showAjaxModal_alert(data.message, 'error', false);
        }
    });
}

function resetTransaction() {
    currentTransaction = {student_id: null, feeding: 0, classes: 0, transport: 0};
    $('#student_selector').val('').trigger('change');
    $('#transaction_interface').removeClass('active');
    $('#empty_state').show();
}

function loadSavedTransactions() {
    $.get('<?php echo site_url('admin/get_saved_transactions'); ?>', function(response) {
        const data = JSON.parse(response);
        $('#saved_count').text(data.transactions.length);
        
        if (data.transactions.length === 0) {
            $('#saved_list').html('<p style="text-align:center;color:#999;padding:20px;">No saved transactions</p>');
            return;
        }
        
        let html = '';
        data.transactions.forEach(t => {
            html += `
                <div class="saved-item" onclick="loadSavedTransaction(${t.id})">
                    <div class="student-name">${t.student_name}</div>
                    <div class="details">${t.date} | Total: GHS ${t.total}</div>
                </div>
            `;
        });
        $('#saved_list').html(html);
    });
}

function loadSavedTransaction(id) {
    $.post('<?php echo site_url('admin/load_saved_transaction'); ?>', {id: id}, function(response) {
        const data = JSON.parse(response);
        if (data.status === 'success') {
            displayTransaction(data);
            $('#feeding_amount').val(data.feeding_paid_today || 0);
            $('#classes_amount').val(data.classes_paid_today || 0);
            $('#transport_amount').val(data.transport_paid_today || 0);
            updateSummary();
        }
    });
}
</script>
