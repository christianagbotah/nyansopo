<?php 
$collector_name = $this->session->userdata('name');
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
echo '<script>';
echo 'var base_url = "' . base_url() . '";';
echo 'var collectorName = ' . json_encode($collector_name) . ';';
echo 'var currency = ' . json_encode($currency) . ';';
echo '</script>';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
.filter-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; width: 100%; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 42px; white-space: nowrap; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); transform: translateY(-2px); }
.btn-success-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-success-modern:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); }
.stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 4px solid; transition: transform 0.3s; margin-bottom: 15px; }
.stat-card:hover { transform: translateY(-4px); }
.stat-card.feeding { border-left-color: #ec4899; }
.stat-card.breakfast { border-left-color: #f59e0b; }
.stat-card.classes { border-left-color: #3b82f6; }
.stat-card.water { border-left-color: #06b6d4; }
.stat-card.transport { border-left-color: #8b5cf6; }
.stat-card.total { border-left-color: #10b981; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
.stat-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value { font-size: 20px; font-weight: 700; color: #1f2937; margin-top: 8px; }
.stat-value .currency { font-size: 11px; vertical-align: super; font-weight: 600; }
.table-modern { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 800px; }
.table-modern thead th { background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); color: #374151; font-weight: 600; font-size: 13px; padding: 12px 16px; text-align: left; border-bottom: 2px solid #e5e7eb; position: sticky; top: 0; z-index: 10; white-space: nowrap; }
.table-modern tbody td { padding: 12px 16px; font-size: 13px; color: #4b5563; border-bottom: 1px solid #f3f4f6; }
.table-modern tbody tr:hover { background: #f9fafb; }
.table-modern tbody tr.selected { background: #dbeafe; }
.checkbox-modern { width: 18px; height: 18px; cursor: pointer; }
.header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
@media (max-width: 768px) {
    .filter-card { padding: 15px; }
    .stat-card { padding: 15px; }
    .stat-value { font-size: 18px; }
    .btn-modern { padding: 8px 12px; font-size: 12px; height: 38px; }
    .btn-modern i { font-size: 12px; }
    .btn-modern span { display: none; }
    .table-modern { font-size: 11px; }
    .table-modern thead th { padding: 8px; font-size: 11px; }
    .table-modern tbody td { padding: 8px; font-size: 11px; }
    .header-flex { flex-direction: column; align-items: flex-start; }
    .action-buttons { width: 100%; justify-content: flex-start; }
}
@media print {
    .no-print, button, .btn-modern, .checkbox-modern { display: none !important; }
    body { background: white !important; }
    .filter-card { box-shadow: none; border: 1px solid #e5e7eb; }
    .table-modern tbody td:nth-child(4) { font-size: 11px !important; }
    .grand-total-row { background: #e5e7eb !important; }
    .grand-total-row td { font-weight: 700 !important; color: #1f2937 !important; }
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { display: none !important; }
    .dataTables_wrapper { width: 130% !important; }
    table.dataTable { width: 130% !important; margin: 0 !important; }
    #transactions_table { width: 130% !important; }
    .table-modern { width: 130% !important; }
}
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="filter-card" style="margin-bottom: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="color: #1f2937; font-size: 24px; font-weight: 700; margin-bottom: 5px;">
                    <i class="fa fa-chart-line" style="color: #3b82f6;"></i> <?php echo get_phrase('my_collections_report'); ?>
                </h2>
                <p style="color: #6b7280; font-size: 13px;"><?php echo get_phrase('daily_account_rendering_and_reconciliation'); ?></p>
            </div>
            <?php 
            $admin_id = $this->session->userdata('admin_id');
            $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
            $is_admin = $admin && $admin->level < 4;
            
            if($is_admin): 
            ?>
            <div style="text-align: right;">
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;"><?php echo get_phrase('collector'); ?>:</div>
                <select id="collector_filter_top" class="form-control" style="min-width: 200px;">
                    <option value=""><?php echo get_phrase('all_collectors'); ?></option>
                    <?php
                    // Get all admins who can collect fees
                    $collectors = $this->db->select('admin_id, name')
                        ->from('admin')
                        ->order_by('name')
                        ->get()->result_array();
                    
                    // Try to get teachers with fee collection permissions if table exists
                    if($this->db->table_exists('fee_collection_assignments')){
                        $teacher_collectors = $this->db->distinct()
                            ->select('teacher.teacher_id as admin_id, teacher.name')
                            ->from('teacher')
                            ->join('fee_collection_assignments', 'fee_collection_assignments.teacher_id = teacher.teacher_id', 'inner')
                            ->where('(fee_collection_assignments.can_collect_feeding = 1 OR fee_collection_assignments.can_collect_classes = 1 OR fee_collection_assignments.can_collect_transport = 1 OR fee_collection_assignments.can_collect_breakfast = 1 OR fee_collection_assignments.can_collect_water = 1)')
                            ->order_by('teacher.name')
                            ->get()->result_array();
                        $collectors = array_merge($collectors, $teacher_collectors);
                    }
                    
                    foreach($collectors as $collector):
                    ?>
                    <option value="<?= $collector['admin_id'] ?>"><?= $collector['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div style="text-align: right;">
                <div style="font-size: 12px; color: #6b7280;"><?php echo get_phrase('collector'); ?>:</div>
                <div style="font-size: 16px; font-weight: 700; color: #3b82f6;"><?php echo $this->session->userdata('name'); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card no-print">
        <div class="row">
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label"><i class="fa fa-chart-bar"></i> <?php echo get_phrase('report_type'); ?></label>
                <select id="report_type" class="form-control">
                    <option value="detailed"><?php echo get_phrase('detailed_report'); ?></option>
                    <option value="class_summary"><?php echo get_phrase('class_summary'); ?></option>
                </select>
            </div>
            

            
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label"><i class="fa fa-calendar"></i> <?php echo get_phrase('date_from'); ?></label>
                <input type="text" id="date_from" value="<?php echo date('d M, Y'); ?>" class="form-control datepicker">
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label"><i class="fa fa-calendar"></i> <?php echo get_phrase('date_to'); ?></label>
                <input type="text" id="date_to" value="<?php echo date('d M, Y'); ?>" class="form-control datepicker">
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label"><i class="fa fa-school"></i> <?php echo get_phrase('class'); ?></label>
                <select id="class_filter" class="form-control">
                    <option value=""><?php echo get_phrase('all_classes'); ?></option>
                    <?php getFullClassList(); ?>
                </select>
            </div>
            
            <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                <label class="form-label"><i class="fa fa-credit-card"></i> <?php echo get_phrase('payment_method'); ?></label>
                <select id="payment_method_filter" class="form-control">
                    <option value=""><?php echo get_phrase('all_methods'); ?></option>
                    <?php
                    $payment_methods = get_payment_methods();
                    foreach($payment_methods as $method):
                    ?>
                    <option value="<?php echo $method->id; ?>"><?php echo $method->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                <label class="form-label"><i class="fa fa-user"></i> <?php echo get_phrase('student'); ?></label>
                <select id="student_filter" class="form-control select2" style="width: 100%;">
                    <option value=""><?php echo get_phrase('all_students'); ?></option>
                </select>
            </div>
            
            <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button onclick="loadReport()" class="btn-modern btn-primary-modern" style="width: 100%;">
                        <i class="fa fa-search"></i> <?php echo get_phrase('load'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-2 col-sm-4 col-6">
            <div class="stat-card feeding">
                <div class="stat-label"><i class="fa fa-utensils"></i> <?php echo get_phrase('feeding'); ?></div>
                <div class="stat-value" id="total_feeding"><span class="currency"><?php echo $currency; ?></span> 0.00</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="stat-card breakfast">
                <div class="stat-label"><i class="fa fa-coffee"></i> <?php echo get_phrase('breakfast'); ?></div>
                <div class="stat-value" id="total_breakfast"><span class="currency"><?php echo $currency; ?></span> 0.00</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="stat-card classes">
                <div class="stat-label"><i class="fa fa-book"></i> <?php echo get_phrase('classes'); ?></div>
                <div class="stat-value" id="total_classes"><span class="currency"><?php echo $currency; ?></span> 0.00</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="stat-card water">
                <div class="stat-label"><i class="fa fa-tint"></i> <?php echo get_phrase('water'); ?></div>
                <div class="stat-value" id="total_water"><span class="currency"><?php echo $currency; ?></span> 0.00</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="stat-card transport">
                <div class="stat-label"><i class="fa fa-bus"></i> <?php echo get_phrase('transport'); ?></div>
                <div class="stat-value" id="total_transport"><span class="currency"><?php echo $currency; ?></span> 0.00</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="stat-card total">
                <div class="stat-label"><i class="fa fa-coins"></i> <?php echo get_phrase('grand_total'); ?></div>
                <div class="stat-value" id="grand_total" style="color: #10b981;"><span class="currency"><?php echo $currency; ?></span> 0.00</div>
            </div>
        </div>
    </div>

    <!-- Detailed Transactions Table -->
    <div class="filter-card" id="detailed_report">
        <div class="header-flex" style="margin-bottom: 15px;">
            <h3 style="color: #1f2937; font-size: 16px; font-weight: 700; margin: 0;">
                <i class="fa fa-list"></i> <?php echo get_phrase('transactions'); ?> (<span id="transaction_count">0</span>)
            </h3>
            <div class="no-print action-buttons">
                <button onclick="selectAll()" class="btn-modern btn-primary-modern" style="height: 36px; padding: 8px 16px; font-size: 13px;">
                    <i class="fa fa-check-square"></i> <span><?php echo get_phrase('select_all'); ?></span>
                </button>
                <button onclick="printSelectedReceipts()" class="btn-modern btn-success-modern" style="height: 36px; padding: 8px 16px; font-size: 13px;">
                    <i class="fa fa-receipt"></i> <span><?php echo get_phrase('print_receipts'); ?></span>
                </button>
                <button onclick="printReport()" class="btn-modern btn-success-modern" style="height: 36px; padding: 8px 16px; font-size: 13px;">
                    <i class="fa fa-print"></i> <span><?php echo get_phrase('print_report'); ?></span>
                </button>
            </div>
        </div>
        
        <div style="overflow-x: auto; max-height: 600px; overflow-y: auto;">
            <table class="table-modern" id="transactions_table">
                <thead>
                    <tr>
                        <th class="no-print" style="width: 40px;"><input type="checkbox" id="select_all_checkbox" class="checkbox-modern" onchange="toggleSelectAll()"></th>
                        <th style="width: 40px;">#</th>
                        <th><?php echo get_phrase('date'); ?></th>
                        <th><?php echo get_phrase('student'); ?></th>
                        <th><?php echo get_phrase('class'); ?></th>
                        <th style="text-align: right;"><?php echo get_phrase('feeding'); ?></th>
                        <th style="text-align: right;"><?php echo get_phrase('breakfast'); ?></th>
                        <th style="text-align: right;"><?php echo get_phrase('classes'); ?></th>
                        <th style="text-align: right;"><?php echo get_phrase('water'); ?></th>
                        <th style="text-align: right;"><?php echo get_phrase('transport'); ?></th>
                        <th style="text-align: right; font-weight: 700;"><?php echo get_phrase('total'); ?></th>
                        <th style="text-align: center;"><?php echo get_phrase('method'); ?></th>
                    </tr>
                </thead>
                <tbody id="transactions_body">
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 40px; color: #9ca3af;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                                <i class="fa fa-search" style="font-size: 36px;"></i>
                                <span><?php echo get_phrase('select_filters_and_click_load_report'); ?></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Class Summary Report -->
    <div class="filter-card" id="class_summary_report" style="display: none;">
        <div class="header-flex" style="margin-bottom: 20px;">
            <h3 style="color: #1f2937; font-size: 16px; font-weight: 700; margin: 0;">
                <i class="fa fa-chart-pie"></i> <?php echo get_phrase('class_summary'); ?>
            </h3>
            <div class="no-print">
                <button onclick="printReport()" class="btn-modern btn-success-modern" style="height: 36px; padding: 8px 16px; font-size: 13px;">
                    <i class="fa fa-print"></i> <span><?php echo get_phrase('print_report'); ?></span>
                </button>
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="table-modern" id="class_summary_table">
                <thead>
                    <tr>
                        <th style="width: 200px;"><?php echo get_phrase('class'); ?></th>
                        <th style="text-align: center; width: 100px;"><?php echo get_phrase('students'); ?></th>
                        <th style="text-align: right; width: 120px;"><?php echo get_phrase('feeding'); ?></th>
                        <th style="text-align: right; width: 120px;"><?php echo get_phrase('breakfast'); ?></th>
                        <th style="text-align: right; width: 120px;"><?php echo get_phrase('classes'); ?></th>
                        <th style="text-align: right; width: 120px;"><?php echo get_phrase('water'); ?></th>
                        <th style="text-align: right; width: 120px;"><?php echo get_phrase('transport'); ?></th>
                        <th style="text-align: center; width: 100px;"><?php echo get_phrase('method'); ?></th>
                        <th style="text-align: right; width: 150px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); font-weight: 700;"><?php echo get_phrase('total'); ?></th>
                    </tr>
                </thead>
                <tbody id="class_summary_body">
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #9ca3af;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                                <i class="fa fa-search" style="font-size: 36px;"></i>
                                <span><?php echo get_phrase('select_filters_and_click_load_report'); ?></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/backend/js/my_collections.js?v=<?php echo time(); ?>"></script>
