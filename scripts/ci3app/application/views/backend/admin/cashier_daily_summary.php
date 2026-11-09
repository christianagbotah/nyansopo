<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$school_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
?>

<style>
.enterprise-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; border-radius: 16px; margin-bottom: 30px; color: white; text-align: center; }
.enterprise-header h1 { font-size: 32px; font-weight: 700; margin: 0 0 8px 0; color: white; }
.enterprise-header p { font-size: 18px; opacity: 0.95; margin: 0; color: white; }
.filter-section { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.filter-section h3 { font-size: 20px; font-weight: 700; color: #1f2937; margin: 0 0 25px 0; }
.form-group-modern { margin-bottom: 20px; }
.form-group-modern label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
.form-group-modern label i { color: #667eea; margin-right: 6px; }
.modern-input { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; transition: all 0.3s; height: 42px; }
.modern-input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.modern-btn { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; height: 42px; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary-modern { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }
.btn-success-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-success-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
.btn-info-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.btn-info-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.4); }
.stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px; margin-bottom: 30px; }
@media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.total { border-left-color: #667eea; }
.stat-card.feeding { border-left-color: #f59e0b; }
.stat-card.breakfast { border-left-color: #8b5cf6; }
.stat-card.classes { border-left-color: #3b82f6; }
.stat-card.water { border-left-color: #06b6d4; }
.stat-card.transport { border-left-color: #10b981; }
.stat-card h4 { font-size: 14px; font-weight: 600; color: #6b7280; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-card .amount { font-size: 28px; font-weight: 700; color: #1f2937; }
.breakdown-section { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.breakdown-section h3 { font-size: 20px; font-weight: 700; color: #1f2937; margin: 0 0 25px 0; padding-bottom: 15px; border-bottom: 3px solid #e5e7eb; }
.payment-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
@media (max-width: 992px) { .payment-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .payment-grid { grid-template-columns: 1fr; } }
.payment-item { padding: 20px; background: #f9fafb; border-radius: 12px; text-align: center; }
.payment-item .icon { font-size: 32px; margin-bottom: 10px; }
.payment-item .label { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 8px; text-transform: uppercase; }
.payment-item .value { font-size: 22px; font-weight: 700; color: #1f2937; }
.action-bar { text-align: center; padding: 20px; }
@media print {
    * { visibility: hidden; }
    .enterprise-header, .enterprise-header *, .stats-grid, .stats-grid *, .breakdown-section, .breakdown-section * { visibility: visible; }
    body { position: absolute; left: 0; top: 0; width: 100%; background: white; margin: 0; padding: 15px; }
    .enterprise-header { position: relative; padding: 20px; margin-bottom: 15px; }
    .enterprise-header h1 { font-size: 20px; margin-bottom: 5px; }
    .enterprise-header p { font-size: 14px; }
    .stats-grid { position: relative; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 15px; }
    .stat-card { padding: 12px; border-radius: 8px; }
    .stat-card h4 { font-size: 10px; margin-bottom: 5px; }
    .stat-card .amount { font-size: 18px; }
    .breakdown-section { position: relative; padding: 15px; margin-bottom: 15px; border-radius: 8px; }
    .breakdown-section h3 { font-size: 14px; margin-bottom: 10px; }
    .payment-grid { grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .payment-item { padding: 10px; }
    .payment-item .label { font-size: 10px; }
    .payment-item .value { font-size: 16px; }
    .enterprise-header, .stats-grid, .stat-card, .breakdown-section, .payment-grid, .payment-item { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { size: A4; margin: 10mm; }
}
</style>

<div class="enterprise-header no-print">
    <h1><?php echo $school_name; ?></h1>
    <p><?php echo get_phrase('cashier_daily_summary'); ?></p>
</div>

<div class="filter-section no-print">
    <h3><i class="fa fa-filter"></i> <?php echo get_phrase('filters'); ?></h3>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group-modern">
                <label><i class="fa fa-calendar"></i> <?php echo get_phrase('date'); ?></label>
                <input type="text" id="summary_date" value="<?php echo date('d/m/Y'); ?>" class="modern-input datepicker" placeholder="dd/mm/yyyy">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group-modern">
                <label><i class="fa fa-user"></i> <?php echo get_phrase('cashier'); ?></label>
                <select id="cashier_id" class="modern-input">
                    <option value=""><?php echo get_phrase('all_cashiers'); ?></option>
                    <?php
                    $admins = $this->db->select('admin_id, name')->from('admin')->order_by('name')->get()->result_array();
                    foreach($admins as $admin):
                    ?>
                    <option value="<?= $admin['admin_id'] ?>"><?= $admin['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group-modern">
                <label>&nbsp;</label>
                <button onclick="loadSummary()" class="modern-btn btn-primary-modern" style="width: 100%;">
                    <i class="fa fa-search"></i> <?php echo get_phrase('load_summary'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card feeding">
        <h4><i class="fa fa-utensils"></i> <?php echo get_phrase('feeding'); ?></h4>
        <div class="amount" id="feeding_total"><?php echo $currency; ?> 0.00</div>
    </div>
    <div class="stat-card breakfast">
        <h4><i class="fa fa-coffee"></i> <?php echo get_phrase('breakfast'); ?></h4>
        <div class="amount" id="breakfast_total"><?php echo $currency; ?> 0.00</div>
    </div>
    <div class="stat-card classes">
        <h4><i class="fa fa-book"></i> <?php echo get_phrase('classes'); ?></h4>
        <div class="amount" id="classes_total"><?php echo $currency; ?> 0.00</div>
    </div>
    <div class="stat-card water">
        <h4><i class="fa fa-tint"></i> <?php echo get_phrase('water'); ?></h4>
        <div class="amount" id="water_total"><?php echo $currency; ?> 0.00</div>
    </div>
    <div class="stat-card transport">
        <h4><i class="fa fa-bus"></i> <?php echo get_phrase('transport'); ?></h4>
        <div class="amount" id="transport_total"><?php echo $currency; ?> 0.00</div>
    </div>
    <div class="stat-card total">
        <h4><i class="fa fa-coins"></i> <?php echo get_phrase('total'); ?></h4>
        <div class="amount" id="total_amount"><sup style="font-size: 16px; font-weight: 500;"><?php echo $currency; ?></sup> 0.00</div>
    </div>
</div>

<div class="breakdown-section">
    <h3><i class="fa fa-credit-card"></i> <?php echo get_phrase('payment_methods'); ?></h3>
    <div class="payment-grid">
        <div class="payment-item">
            <div class="icon" style="color: #10b981;"><i class="fa fa-money-bill-wave"></i></div>
            <div class="label"><?php echo get_phrase('cash'); ?></div>
            <div class="value" id="cash_total"><?php echo $currency; ?> 0.00</div>
        </div>
        <div class="payment-item">
            <div class="icon" style="color: #3b82f6;"><i class="fa fa-university"></i></div>
            <div class="label"><?php echo get_phrase('bank_transfer'); ?></div>
            <div class="value" id="bank_total"><?php echo $currency; ?> 0.00</div>
        </div>
        <div class="payment-item">
            <div class="icon" style="color: #8b5cf6;"><i class="fa fa-mobile-alt"></i></div>
            <div class="label"><?php echo get_phrase('mobile_money'); ?></div>
            <div class="value" id="momo_total"><?php echo $currency; ?> 0.00</div>
        </div>
        <div class="payment-item">
            <div class="icon" style="color: #f59e0b;"><i class="fa fa-file-invoice"></i></div>
            <div class="label"><?php echo get_phrase('cheque'); ?></div>
            <div class="value" id="cheque_total"><?php echo $currency; ?> 0.00</div>
        </div>
    </div>
</div>

<div class="breakdown-section">
    <h3><i class="fa fa-chart-bar"></i> <?php echo get_phrase('transaction_statistics'); ?></h3>
    <div class="row">
        <div class="col-md-6">
            <div class="payment-item">
                <div class="icon" style="color: #667eea;"><i class="fa fa-receipt"></i></div>
                <div class="label"><?php echo get_phrase('total_transactions'); ?></div>
                <div class="value" id="transaction_count">0</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="payment-item">
                <div class="icon" style="color: #10b981;"><i class="fa fa-users"></i></div>
                <div class="label"><?php echo get_phrase('students_served'); ?></div>
                <div class="value" id="students_count">0</div>
            </div>
        </div>
    </div>
</div>

<div class="action-bar no-print">
    <button onclick="printReport()" class="modern-btn btn-success-modern">
        <i class="fa fa-print"></i> <?php echo get_phrase('print_summary'); ?>
    </button>
    <button onclick="exportSummary()" class="modern-btn btn-info-modern">
        <i class="fa fa-file-excel"></i> <?php echo get_phrase('export_excel'); ?>
    </button>
</div>

<script>
$(document).ready(function() {
    // Initialize datepicker with dd/mm/yyyy format
    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });
    
    loadSummary();
});

function convertDateToISO(dateStr) {
    // Convert dd/mm/yyyy to yyyy-mm-dd for backend
    if (!dateStr) return '';
    var parts = dateStr.split('/');
    if (parts.length !== 3) return dateStr;
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}

function loadSummary() {
    var dateInput = $('#summary_date').val();
    var date = convertDateToISO(dateInput);
    var cashier_id = $('#cashier_id').val();
    
    showAjaxModal_alert('Loading summary...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/get_cashier_daily_summary"); ?>',
        type: 'POST',
        data: { date: formatDate(date), cashier_id: cashier_id },
        dataType: 'json',
        success: function(response) {
            $('.close').click();
            if(response.status === 'success') {
                var data = response.data;
                var currency = '<?php echo $currency; ?>';
                
                $('#total_amount').html('<sup style="font-size: 16px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.total || 0).toFixed(2));
                $('#feeding_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.feeding || 0).toFixed(2));
                $('#breakfast_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.breakfast || 0).toFixed(2));
                $('#classes_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.classes || 0).toFixed(2));
                $('#water_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.water || 0).toFixed(2));
                $('#transport_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.transport || 0).toFixed(2));
                
                $('#cash_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.cash || 0).toFixed(2));
                $('#bank_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.bank || 0).toFixed(2));
                $('#momo_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.momo || 0).toFixed(2));
                $('#cheque_total').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.cheque || 0).toFixed(2));
                
                $('#transaction_count').text(data.transaction_count || 0);
                $('#students_count').text(data.students_count || 0);
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to load summary', 'error');
        }
    });
}

function formatDate(dateStr) {
    var d = new Date(dateStr);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ', ' + d.getFullYear();
}

function exportSummary() {
    var dateInput = $('#summary_date').val();
    var date = convertDateToISO(dateInput);
    var cashier_id = $('#cashier_id').val();
    window.location.href = '<?php echo site_url("admin/export_cashier_summary"); ?>?date=' + formatDate(date) + '&cashier_id=' + cashier_id;
}

function printReport() {
    window.print();
}
</script>
