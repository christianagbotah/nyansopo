<?php
// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');
?>
<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.enterprise-card { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 24px; }
.card-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 32px; color: white; }
.card-title { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; display: flex; align-items: center; gap: 12px; }
.card-subtitle { font-size: 14px; opacity: 0.9; font-weight: 400; }
.filter-section { background: linear-gradient(to bottom, #f9fafb 0%, white 100%); padding: 32px; border-bottom: 1px solid #e5e7eb; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-label { font-size: 13px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px; }
.form-control { padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 15px; transition: all 0.3s; background: white; height: 52px; }
.form-control:focus { border-color: #10b981; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.1); }
.btn-enterprise { padding: 14px 28px; border-radius: 12px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; font-size: 15px; height: 52px; }
.btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
.btn-success { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(59,130,246,0.3); }
.btn-export { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.btn-export:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(245,158,11,0.3); }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; padding: 32px; background: linear-gradient(to bottom, #f9fafb 0%, white 100%); }
.stat-card { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: all 0.3s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
.stat-label { font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px; }
.stat-value { font-size: 32px; font-weight: 800; color: #1f2937; margin-top: 12px; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px; }
.icon-students { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.icon-money { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.icon-feeding { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.icon-transport { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
.table-container { padding: 32px; overflow-x: auto; }
.table-modern { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
.table-modern thead th { background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: white; padding: 18px 16px; text-align: left; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; }
.table-modern thead th:first-child { border-radius: 16px 0 0 0; }
.table-modern thead th:last-child { border-radius: 0 16px 0 0; }
.table-modern tbody tr { transition: all 0.2s; }
.table-modern tbody tr:hover { background: #f0fdf4; }
.table-modern tbody td { padding: 18px 16px; border-bottom: 1px solid #e5e7eb; color: #374151; font-size: 14px; }
.table-modern tbody td:first-child { font-weight: 600; color: #6b7280; }
.table-modern tfoot td { padding: 20px 16px; background: linear-gradient(to bottom, #f9fafb 0%, #f3f4f6 100%); font-weight: 800; color: #1f2937; border-top: 3px solid #10b981; font-size: 15px; }
.empty-state { text-align: center; padding: 100px 20px; }
.empty-icon { font-size: 80px; color: #d1d5db; margin-bottom: 20px; }
.empty-text { font-size: 20px; color: #6b7280; font-weight: 600; }
.action-bar { display: flex; gap: 16px; padding: 32px; border-top: 1px solid #e5e7eb; background: #f9fafb; }
.date-range-label { display: inline-block; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 8px; font-size: 13px; font-weight: 500; }
@media print { .filter-section, .action-bar, .card-header { display: none !important; } .enterprise-card { box-shadow: none; } }
@media (max-width: 768px) { .filter-grid { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: 1fr; } .action-bar { flex-direction: column; } }
</style>

<div class="enterprise-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa fa-money-bill-wave"></i>
            <?php echo get_phrase('daily_payment_report'); ?>
        </div>
        <div class="card-subtitle">Generate comprehensive payment reports with date range filtering</div>
    </div>

    <?php echo form_open('#', ['id' => 'payment_report_form', 'onsubmit' => 'loadPayments(); return false;']); ?>
    <div class="filter-section">
        <div class="filter-grid">
            <div class="form-group">
                <label class="form-label"><i class="fa fa-calendar"></i> <?php echo get_phrase('start_date'); ?></label>
                <input type="text" class="form-control datepicker" name="start_date" id="start_date" value="<?php echo date('d M, Y'); ?>" placeholder="Select start date">
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa fa-calendar"></i> <?php echo get_phrase('end_date'); ?></label>
                <input type="text" class="form-control datepicker" name="end_date" id="end_date" value="<?php echo date('d M, Y'); ?>" placeholder="Select end date">
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa fa-users"></i> <?php echo get_phrase('class'); ?></label>
<select class="form-control" name="class_id" id="class_filter">
                <option value=""><?php echo get_phrase('select_class'); ?></option>
                <?php
                $teacher_id = $this->session->userdata('teacher_id');
                $classes = $this->crud_model->get_classes();
                $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
                $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
                
                foreach($classes as $class):
                    $is_class_teacher = ($class['teacher_id'] == $teacher_id);
                    $teaches_subject = $this->db->get_where('subject', array(
                        'teacher_id' => $teacher_id,
                        'class_id' => $class['class_id'],
                        'year' => $running_year,
                        'term' => $running_term
                    ))->num_rows() > 0;
                    
                    if($is_class_teacher || $teaches_subject):
                        $full_class_name = $this->crud_model->getFullClassName($class['class_id']);
                ?>
                <option value="<?php echo $class['class_id']; ?>"><?php echo $full_class_name; ?></option>
                <?php 
                    endif;
                endforeach; 
                ?>
            </select>
            </div>
            <div class="form-group" style="justify-content: flex-end;">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn-enterprise btn-primary">
                    <i class="fa fa-chart-bar"></i> <?php echo get_phrase('generate_report'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>

    <div id="payment_results" style="display: none;">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-students"><i class="fa fa-users"></i></div>
                <div class="stat-label"><?php echo get_phrase('total_students'); ?></div>
                <div class="stat-value" id="stat_students">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-money"><i class="fa fa-money-bill-wave"></i></div>
                <div class="stat-label"><?php echo get_phrase('total_collected'); ?></div>
                <div class="stat-value" id="stat_total">₵ 0.00</div>
            </div>
            <?php if($feeding_enabled): ?>
            <div class="stat-card">
                <div class="stat-icon icon-feeding"><i class="fa fa-utensils"></i></div>
                <div class="stat-label"><?php echo get_phrase('feeding'); ?></div>
                <div class="stat-value" id="stat_feeding">₵ 0.00</div>
            </div>
            <?php endif; ?>
            <?php if($transport_enabled): ?>
            <div class="stat-card">
                <div class="stat-icon icon-transport"><i class="fa fa-bus"></i></div>
                <div class="stat-label"><?php echo get_phrase('transport'); ?></div>
                <div class="stat-value" id="stat_transport">₵ 0.00</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo get_phrase('student_code'); ?></th>
                        <th><?php echo get_phrase('student_name'); ?></th>
                        <?php if($feeding_enabled): ?><th><?php echo get_phrase('feeding'); ?></th><?php endif; ?>
                        <?php if($breakfast_enabled): ?><th><?php echo get_phrase('breakfast'); ?></th><?php endif; ?>
                        <?php if($classes_enabled): ?><th><?php echo get_phrase('classes'); ?></th><?php endif; ?>
                        <?php if($water_enabled): ?><th><?php echo get_phrase('water'); ?></th><?php endif; ?>
                        <?php if($transport_enabled): ?><th><?php echo get_phrase('transport'); ?></th><?php endif; ?>
                        <th><?php echo get_phrase('total'); ?></th>
                    </tr>
                </thead>
                <tbody id="payments_tbody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><?php echo get_phrase('grand_total'); ?>:</td>
                        <?php if($feeding_enabled): ?><td id="total_feeding">₵ 0.00</td><?php endif; ?>
                        <?php if($breakfast_enabled): ?><td id="total_breakfast">₵ 0.00</td><?php endif; ?>
                        <?php if($classes_enabled): ?><td id="total_classes">₵ 0.00</td><?php endif; ?>
                        <?php if($water_enabled): ?><td id="total_water">₵ 0.00</td><?php endif; ?>
                        <?php if($transport_enabled): ?><td id="total_transport">₵ 0.00</td><?php endif; ?>
                        <td id="grand_total">₵ 0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="action-bar">
            <button class="btn-enterprise btn-success" onclick="window.print()">
                <i class="fa fa-print"></i> <?php echo get_phrase('print'); ?>
            </button>
            <button class="btn-enterprise btn-export" onclick="exportToExcel()">
                <i class="fa fa-file-excel"></i> <?php echo get_phrase('export_excel'); ?>
            </button>
            <div style="flex: 1;"></div>
            <span class="date-range-label" id="date_range_display"></span>
        </div>
    </div>

    <div id="no_data" style="display: none;">
        <div class="empty-state">
            <div class="empty-icon"><i class="fa fa-inbox"></i></div>
            <div class="empty-text"><?php echo get_phrase('no_payments_found_for_selected_date'); ?></div>
        </div>
    </div>
</div>

<script>
function loadPayments() {
    var start_date = $('#start_date').val();
    var end_date = $('#end_date').val();
    var class_id = $('#class_filter').val();
    
    if(!start_date || !end_date || !class_id) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_all_fields'); ?>', 'error');
        return;
    }
    
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('teacher/get_students_with_payments'); ?>',
        type: 'POST',
        data: {start_date: start_date, end_date: end_date, class_id: class_id},
        dataType: 'json',
        success: function(response) {
            $('.close').click();
            if(response.status === 'success' && response.data.length > 0) {
                displayPayments(response.data);
                $('#payment_results').slideDown();
                $('#no_data').hide();
            } else {
                $('#payment_results').hide();
                $('#no_data').slideDown();
            }
        },
        error: function() {
            showAjaxModal_alert('<?php echo get_phrase('error_loading_data'); ?>', 'error');
        }
    });
}

function displayPayments(data) {
    var start_date = $('#start_date').val();
    var end_date = $('#end_date').val();
    $('#date_range_display').html('<i class="fa fa-calendar-alt"></i> ' + start_date + ' - ' + end_date);
    
    var tbody = '';
    var totals = {feeding: 0, breakfast: 0, classes: 0, water: 0, transport: 0, grand: 0};
    
    data.forEach(function(row, index) {
        var feeding = parseFloat(row.feeding_paid) || 0;
        var breakfast = parseFloat(row.breakfast_paid) || 0;
        var classes = parseFloat(row.classes_paid) || 0;
        var water = parseFloat(row.water_paid) || 0;
        var transport = parseFloat(row.transport_paid) || 0;
        var total = feeding + breakfast + classes + water + transport;
        
        totals.feeding += feeding;
        totals.breakfast += breakfast;
        totals.classes += classes;
        totals.water += water;
        totals.transport += transport;
        totals.grand += total;
        
        tbody += '<tr>';
        tbody += '<td>' + (index + 1) + '</td>';
        tbody += '<td><strong>' + row.student_code + '</strong></td>';
        tbody += '<td>' + row.name + '</td>';
        tbody += '<td>₵ ' + feeding.toFixed(2) + '</td>';
        tbody += '<td>₵ ' + breakfast.toFixed(2) + '</td>';
        tbody += '<td>₵ ' + classes.toFixed(2) + '</td>';
        tbody += '<td>₵ ' + water.toFixed(2) + '</td>';
        tbody += '<td>₵ ' + transport.toFixed(2) + '</td>';
        tbody += '<td><strong>₵ ' + total.toFixed(2) + '</strong></td>';
        tbody += '</tr>';
    });
    
    $('#payments_tbody').html(tbody);
    $('#total_feeding').text('₵ ' + totals.feeding.toFixed(2));
    $('#total_breakfast').text('₵ ' + totals.breakfast.toFixed(2));
    $('#total_classes').text('₵ ' + totals.classes.toFixed(2));
    $('#total_water').text('₵ ' + totals.water.toFixed(2));
    $('#total_transport').text('₵ ' + totals.transport.toFixed(2));
    $('#grand_total').text('₵ ' + totals.grand.toFixed(2));
    
    $('#stat_students').text(data.length);
    $('#stat_total').text('₵ ' + totals.grand.toFixed(2));
    $('#stat_feeding').text('₵ ' + totals.feeding.toFixed(2));
    $('#stat_transport').text('₵ ' + totals.transport.toFixed(2));
}

function exportToExcel() {
    var table = document.querySelector('.table-modern');
    var html = table.outerHTML;
    var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'payment_report_' + new Date().getTime() + '.xls';
    link.click();
}

$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'dd M, yyyy',
        autoclose: true
    });
});
</script>
