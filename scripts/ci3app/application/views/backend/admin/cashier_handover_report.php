<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$school_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;

// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');
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
.report-section { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.report-section h3 { font-size: 18px; font-weight: 700; color: white; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 20px; border-radius: 10px; margin: -30px -30px 25px -30px; }
.info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
@media (max-width: 992px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .info-grid { grid-template-columns: 1fr; } }
.info-item { padding: 15px; background: #f9fafb; border-radius: 10px; border-left: 4px solid #667eea; }
.info-item .label { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; }
.info-item .value { font-size: 16px; font-weight: 700; color: #1f2937; }
.amount-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #e5e7eb; }
.amount-row:last-child { border-bottom: none; }
.amount-row .label { font-size: 15px; font-weight: 600; color: #4b5563; }
.amount-row .value { font-size: 18px; font-weight: 700; color: #10b981; }
.total-row { background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; margin-top: 20px; }
.total-row .label { font-size: 18px; font-weight: 700; color: #1f2937; }
.total-row .value { font-size: 28px; font-weight: 700; color: #10b981; }
.signature-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-top: 50px; }
.signature-box { padding: 30px; background: #f9fafb; border-radius: 12px; border: 2px dashed #d1d5db; text-align: center; }
.signature-box .title { font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 60px; }
.signature-box .line { border-top: 2px solid #1f2937; padding-top: 10px; margin-top: 10px; }
.signature-box .name { font-size: 15px; font-weight: 600; color: #4b5563; }
.signature-box .date { font-size: 13px; color: #9ca3af; margin-top: 5px; }
.action-bar { text-align: center; padding: 20px; }
@media print {
    * { visibility: hidden; }
    .enterprise-header, .enterprise-header *, .report-section, .report-section *, .signature-grid, .signature-grid * { visibility: visible; }
    body { position: absolute; left: 0; top: 0; width: 100%; background: white; margin: 0; padding: 15px; }
    .enterprise-header { position: relative; padding: 20px; margin-bottom: 15px; }
    .enterprise-header h1 { font-size: 20px; margin-bottom: 5px; }
    .enterprise-header p { font-size: 14px; }
    .report-section { position: relative; padding: 15px; margin-bottom: 15px; border-radius: 8px; }
    .report-section h3 { font-size: 14px; margin-bottom: 10px; }
    .info-grid { grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .info-item { padding: 10px; }
    .info-item .label { font-size: 10px; }
    .info-item .value { font-size: 13px; }
    .amount-row { padding: 10px 0; }
    .amount-row .label { font-size: 12px; }
    .amount-row .value { font-size: 15px; }
    .signature-grid { position: relative; gap: 15px; margin-top: 20px; }
    .signature-box { padding: 15px; }
    .signature-box .title { font-size: 13px; margin-bottom: 40px; }
    .enterprise-header, .report-section, .info-grid, .info-item, .signature-grid, .signature-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { size: A4; margin: 10mm; }
}
</style>

<div class="enterprise-header no-print">
    <h1><?php echo $school_name; ?></h1>
    <p><?php echo get_phrase('cashier_handover_report'); ?></p>
</div>

<div class="filter-section no-print">
    <h3><i class="fa fa-cog"></i> <?php echo get_phrase('report_settings'); ?></h3>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group-modern">
                <label><i class="fa fa-calendar"></i> <?php echo get_phrase('date'); ?></label>
                <input type="date" id="handover_date" value="<?php echo date('Y-m-d'); ?>" class="modern-input">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group-modern">
                <label><i class="fa fa-user"></i> <?php echo get_phrase('cashier'); ?></label>
                <select id="handover_cashier" class="modern-input">
                    <?php
                    $admins = $this->db->select('admin_id, name')->from('admin')->order_by('name')->get()->result_array();
                    foreach($admins as $admin):
                    ?>
                    <option value="<?= $admin['admin_id'] ?>"><?= $admin['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group-modern">
                <label><i class="fa fa-user-tie"></i> <?php echo get_phrase('received_by'); ?></label>
                <select id="receiver_id" class="modern-input">
                    <?php foreach($admins as $admin): ?>
                    <option value="<?= $admin['admin_id'] ?>"><?= $admin['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group-modern">
                <label>&nbsp;</label>
                <button onclick="loadHandover()" class="modern-btn btn-primary-modern" style="width: 100%;">
                    <i class="fa fa-sync"></i> <?php echo get_phrase('generate'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="report-section">
    <h3><i class="fa fa-info-circle"></i> <?php echo get_phrase('report_details'); ?></h3>
    <div class="info-grid">
        <div class="info-item">
            <div class="label"><?php echo get_phrase('date'); ?></div>
            <div class="value" id="report_date"><?php echo date('d M, Y'); ?></div>
        </div>
        <div class="info-item">
            <div class="label"><?php echo get_phrase('cashier'); ?></div>
            <div class="value" id="cashier_name">-</div>
        </div>
        <div class="info-item">
            <div class="label"><?php echo get_phrase('shift'); ?></div>
            <div class="value">Full Day</div>
        </div>
        <div class="info-item">
            <div class="label"><?php echo get_phrase('generated'); ?></div>
            <div class="value"><?php echo date('H:i A'); ?></div>
        </div>
    </div>
</div>

<div class="report-section">
    <h3><i class="fa fa-coins"></i> <?php echo get_phrase('collections_summary'); ?></h3>
    <?php if($feeding_enabled): ?>
    <div class="amount-row">
        <span class="label"><i class="fa fa-utensils"></i> <?php echo get_phrase('feeding'); ?></span>
        <span class="value" id="h_feeding"><?php echo $currency; ?> 0.00</span>
    </div>
    <?php endif; ?>
    <?php if($breakfast_enabled): ?>
    <div class="amount-row">
        <span class="label"><i class="fa fa-coffee"></i> <?php echo get_phrase('breakfast'); ?></span>
        <span class="value" id="h_breakfast"><?php echo $currency; ?> 0.00</span>
    </div>
    <?php endif; ?>
    <?php if($classes_enabled): ?>
    <div class="amount-row">
        <span class="label"><i class="fa fa-book"></i> <?php echo get_phrase('classes'); ?></span>
        <span class="value" id="h_classes"><?php echo $currency; ?> 0.00</span>
    </div>
    <?php endif; ?>
    <?php if($water_enabled): ?>
    <div class="amount-row">
        <span class="label"><i class="fa fa-tint"></i> <?php echo get_phrase('water'); ?></span>
        <span class="value" id="h_water"><?php echo $currency; ?> 0.00</span>
    </div>
    <?php endif; ?>
    <?php if($transport_enabled): ?>
    <div class="amount-row">
        <span class="label"><i class="fa fa-bus"></i> <?php echo get_phrase('transport'); ?></span>
        <span class="value" id="h_transport"><?php echo $currency; ?> 0.00</span>
    </div>
    <?php endif; ?>
    <div class="total-row">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span class="label"><?php echo get_phrase('total'); ?></span>
            <span class="value" id="h_total"><sup style="font-size: 18px; font-weight: 500;"><?php echo $currency; ?></sup> 0.00</span>
        </div>
    </div>
</div>

<div class="report-section">
    <h3><i class="fa fa-credit-card"></i> <?php echo get_phrase('payment_methods'); ?></h3>
    <div class="row">
        <div class="col-md-6">
            <div class="amount-row">
                <span class="label"><i class="fa fa-money-bill-wave"></i> <?php echo get_phrase('cash'); ?></span>
                <span class="value" id="h_cash"><?php echo $currency; ?> 0.00</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="amount-row">
                <span class="label"><i class="fa fa-university"></i> <?php echo get_phrase('bank_transfer'); ?></span>
                <span class="value" id="h_bank"><?php echo $currency; ?> 0.00</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="amount-row">
                <span class="label"><i class="fa fa-mobile-alt"></i> <?php echo get_phrase('mobile_money'); ?></span>
                <span class="value" id="h_momo"><?php echo $currency; ?> 0.00</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="amount-row">
                <span class="label"><i class="fa fa-file-invoice"></i> <?php echo get_phrase('cheque'); ?></span>
                <span class="value" id="h_cheque"><?php echo $currency; ?> 0.00</span>
            </div>
        </div>
    </div>
</div>

<div class="report-section">
    <h3><i class="fa fa-chart-bar"></i> <?php echo get_phrase('statistics'); ?></h3>
    <div class="row">
        <div class="col-md-6">
            <div class="info-item">
                <div class="label"><?php echo get_phrase('total_transactions'); ?></div>
                <div class="value" id="h_transaction_count" style="color: #3b82f6;">0</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-item">
                <div class="label"><?php echo get_phrase('students_served'); ?></div>
                <div class="value" id="h_students_count" style="color: #8b5cf6;">0</div>
            </div>
        </div>
    </div>
</div>

<div class="signature-grid">
    <div class="signature-box">
        <div class="title"><?php echo get_phrase('handed_over_by'); ?></div>
        <div class="line">
            <div class="name" id="handover_by_name">Cashier Name</div>
            <div class="date"><?php echo get_phrase('signature_date'); ?>: _________________</div>
        </div>
    </div>
    <div class="signature-box">
        <div class="title"><?php echo get_phrase('received_by'); ?></div>
        <div class="line">
            <div class="name" id="received_by_name">Receiver Name</div>
            <div class="date"><?php echo get_phrase('signature_date'); ?>: _________________</div>
        </div>
    </div>
</div>

<div class="action-bar no-print">
    <button onclick="printReport()" class="modern-btn btn-success-modern">
        <i class="fa fa-print"></i> <?php echo get_phrase('print_report'); ?>
    </button>
    <button onclick="saveHandover()" class="modern-btn btn-primary-modern">
        <i class="fa fa-save"></i> <?php echo get_phrase('save_handover'); ?>
    </button>
</div>

<script>
$(document).ready(function() {
    loadHandover();
});

function loadHandover() {
    var date = $('#handover_date').val();
    var cashier_id = $('#handover_cashier').val();
    
    showAjaxModal_alert('Loading report...', 'loading');
    
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
                
                $('#report_date').text(formatDate(date));
                $('#cashier_name').text($('#handover_cashier option:selected').text());
                
                $('#h_feeding').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.feeding || 0).toFixed(2));
                $('#h_breakfast').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.breakfast || 0).toFixed(2));
                $('#h_classes').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.classes || 0).toFixed(2));
                $('#h_water').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.water || 0).toFixed(2));
                $('#h_transport').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.transport || 0).toFixed(2));
                $('#h_total').html('<sup style="font-size: 18px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.total || 0).toFixed(2));
                
                $('#h_cash').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.cash || 0).toFixed(2));
                $('#h_bank').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.bank || 0).toFixed(2));
                $('#h_momo').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.momo || 0).toFixed(2));
                $('#h_cheque').html('<sup style="font-size: 14px; font-weight: 500;">' + currency + '</sup> ' + parseFloat(data.cheque || 0).toFixed(2));
                
                $('#h_transaction_count').text(data.transaction_count || 0);
                $('#h_students_count').text(data.students_count || 0);
                
                $('#handover_by_name').text($('#handover_cashier option:selected').text());
                $('#received_by_name').text($('#receiver_id option:selected').text());
            }
        },
        error: function() {
            showAjaxModal_alert('Failed to load report', 'error');
        }
    });
}

function formatDate(dateStr) {
    var d = new Date(dateStr);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ', ' + d.getFullYear();
}

function saveHandover() {
    showAjaxModal_alert('<?php echo get_phrase("saving"); ?>...', 'loading');
    
    var data = {
        date: formatDate($('#handover_date').val()),
        cashier_id: $('#handover_cashier').val(),
        receiver_id: $('#receiver_id').val(),
        total_amount: $('#h_total').text().replace('<?php echo $currency; ?> ', ''),
        cash_amount: $('#h_cash').text().replace('<?php echo $currency; ?> ', '')
    };
    
    $.ajax({
        url: '<?php echo site_url("admin/save_handover_report"); ?>',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
            } else {
                showAjaxModal_alert(response.message || 'Failed to save', 'error');
            }
        },
        error: function() {
            showAjaxModal_alert('An error occurred', 'error');
        }
    });
}

function printReport() {
    window.print();
}
</script>
