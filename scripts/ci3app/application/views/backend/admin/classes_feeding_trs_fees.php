<?php 
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $un_year = $running_year;
    $un_term = $running_term;
    $date_timestamp = strtotime(date('d-m-Y'));
    
    // Get fee mode
    $fee_mode_result = $this->db->get_where('settings', array('type' => 'fee_collection_mode'));
    $fee_mode = ($fee_mode_result->num_rows() > 0) ? $fee_mode_result->row()->description : 'integrated';
    
    // Check enabled modules
    $show_feeding = is_fee_module_enabled('feeding');
    $show_classes = is_fee_module_enabled('classes');
    $show_transport = is_fee_module_enabled('transport');
    $show_breakfast = is_fee_module_enabled('breakfast');
    $show_water = is_fee_module_enabled('water');
    $enabled_count = ($show_feeding ? 1 : 0) + ($show_classes ? 1 : 0) + ($show_transport ? 1 : 0) + ($show_breakfast ? 1 : 0) + ($show_water ? 1 : 0);
    
    if($enabled_count == 1) $col_class = 'col-md-12';
    elseif($enabled_count == 2) $col_class = 'col-md-6';
    elseif($enabled_count == 3) $col_class = 'col-md-4';
    elseif($enabled_count == 4) $col_class = 'col-md-6 col-lg-3';
    else $col_class = 'col-md-6 col-lg-4 col-xl-20';
    
    // Module configurations
    $modules = [
        'feeding' => ['icon' => 'utensils', 'color' => '#3b82f6', 'label' => 'Feeding Fee'],
        'classes' => ['icon' => 'chalkboard-teacher', 'color' => '#10b981', 'label' => 'Classes Fee'],
        'transport' => ['icon' => 'bus', 'color' => '#f59e0b', 'label' => 'Transport Fare'],
        'breakfast' => ['icon' => 'coffee', 'color' => '#ec4899', 'label' => 'Breakfast Fee'],
        'water' => ['icon' => 'tint', 'color' => '#06b6d4', 'label' => 'Water Fee']
    ];
?>

<style>
.fees-container {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}
.modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s;
}
.modern-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.header-card {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    text-align: center;
    padding: 2rem;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    border-left: 4px solid;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px -6px rgba(0,0,0,0.15);
}
.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.9;
}
.stat-amount {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0.5rem 0;
    color: #1f2937;
}
.currency-symbol {
    font-size: 0.5em;
    opacity: 0.7;
    vertical-align: super;
    margin-right: 2px;
}
.stat-label {
    font-size: 1rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
}
.stat-subtitle {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-top: 0.5rem;
}
.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.badge-mode {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-left: 1rem;
}
.badge-separated {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}
.badge-integrated {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}
.col-xl-20 {
    flex: 0 0 20%;
    max-width: 20%;
}
.d-flex {
    display: flex !important;
}
.align-items-center {
    align-items: center !important;
}
.gap-2 {
    gap: 0.5rem !important;
}
@media (max-width: 1199px) {
    .col-xl-20 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
}
@media (max-width: 768px) {
    .stat-amount { font-size: 1.25rem; }
    .stat-icon { font-size: 2rem; }
    .col-xl-20 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    .d-flex.align-items-center {
        flex-direction: column;
        gap: 0.75rem !important;
    }
    .d-flex.align-items-center > * {
        width: 100%;
    }
}
</style>

<div class="fees-container">
    <div class="container-fluid">
        <!-- Header -->
        <div class="modern-card header-card">
            <h1 class="mb-2" style="font-size: 2rem; font-weight: 800;">
                <i class="fa fa-money-bill-wave"></i> Daily Fees Management
            </h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">Comprehensive management of all daily fee collections</p>
            <span class="badge-mode <?php echo $fee_mode == 'separated' ? 'badge-separated' : 'badge-integrated'; ?>">
                <i class="fa fa-cog"></i> <?php echo ucfirst($fee_mode); ?> Mode
            </span>
        </div>

        <!-- Search Filters -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl shadow-lg p-4 hover:shadow-xl transition-all" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 16px; color: white;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 700; color: white;">Search by Date</h3>
                    <?php echo form_open('', array('id' => 'by_date_form')); ?>
                    <div class="d-flex align-items-center gap-2">
                        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 10px;">
                            <i class="fa fa-calendar" style="font-size: 1.25rem;"></i>
                        </div>
                        <input type="text" name="date_sel" id="date_sel" class="form-control datepicker" value="<?php echo date('d-m-Y'); ?>" placeholder="Select date" style="flex: 1; border-radius: 10px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.95); font-weight: 500;">
                        <button type="submit" class="btn text-white" style="background: rgba(255,255,255,0.2); white-space: nowrap; border-radius: 10px; padding: 0.5rem 1.25rem; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl shadow-lg p-4 hover:shadow-xl transition-all" style="background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%); border-radius: 16px; color: white;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 700; color: white;">Search by Term/Year</h3>
                    <?php echo form_open('', array('id' => 'byterm_form')); ?>
                    <div class="d-flex align-items-center gap-2">
                        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 10px;">
                            <i class="fa fa-graduation-cap" style="font-size: 1.25rem;"></i>
                        </div>
                        <select name="term" id="term" class="form-control" style="border-radius: 10px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.95); font-weight: 500;">
                            <option value="">Select Term</option>
                            <?php for($i = 1; $i <= 3; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($un_term == $i) echo 'selected'; ?>>Term <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="year" id="year" class="form-control" style="border-radius: 10px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.95); font-weight: 500;">
                            <option value="">Select Year</option>
                            <?php echo populate_academic_year(); ?>
                        </select>
                        <button type="submit" class="btn text-white" style="background: rgba(255,255,255,0.2); white-space: nowrap; border-radius: 10px; padding: 0.5rem 1.25rem; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>

        <!-- By Date Stats -->
        <div id="by_date">
            <div class="section-title">
                <i class="fa fa-chart-line"></i> Fees Collected (By Date)
            </div>
            <div class="row" style="row-gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($modules as $key => $module): ?>
                    <?php if(${'show_' . $key}): ?>
                    <div class="<?php echo $col_class; ?> mb-4">
                        <a href="javascript:;" onclick="show_payments_modal('<?php echo $key; ?>')" class="text-decoration-none">
                            <div class="stat-card" style="border-color: <?php echo $module['color']; ?>;">
                                <div class="stat-icon" style="color: <?php echo $module['color']; ?>;"><i class="fa fa-<?php echo $module['icon']; ?>"></i></div>
                                <div class="stat-label"><?php echo $module['label']; ?> Collected</div>
                                <div class="stat-amount" id="<?php echo $key; ?>_fee_tile"><sup class="currency-symbol"><?php echo $currency; ?></sup>0.00</div>
                                <div class="stat-subtitle" id="<?php echo $key[0]; ?>date">Select date to view</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="section-title" style="margin-top: 2rem;">
                <i class="fa fa-exclamation-triangle"></i> Outstanding Fees
            </div>
            <div class="row" style="row-gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($modules as $key => $module): ?>
                    <?php if(${'show_' . $key}): ?>
                    <div class="<?php echo $col_class; ?> mb-4">
                        <a href="javascript:;" onclick="show_outstanding_modal('<?php echo $key; ?>')" class="text-decoration-none">
                            <div class="stat-card" style="border-color: #ef4444;">
                                <div class="stat-icon" style="color: #ef4444;"><i class="fa fa-exclamation-circle"></i></div>
                                <div class="stat-label"><?php echo $module['label']; ?> Outstanding</div>
                                <div class="stat-amount" id="<?php echo $key; ?>_fee_tile_owe"><sup class="currency-symbol"><?php echo $currency; ?></sup>0.00</div>
                                <div class="stat-subtitle" id="<?php echo $key[0]; ?>date_owe">Click to view debtors</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="section-title" style="margin-top: 2rem;">
                <i class="fa fa-hand-holding-usd"></i> Advance Payments (Payables)
            </div>
            <div class="row" style="row-gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($modules as $key => $module): ?>
                    <?php if(${'show_' . $key}): ?>
                    <div class="<?php echo $col_class; ?> mb-4">
                        <a href="javascript:;" onclick="show_payables_modal('<?php echo $key; ?>')" class="text-decoration-none">
                            <div class="stat-card" style="border-color: #06b6d4;">
                                <div class="stat-icon" style="color: #06b6d4;"><i class="fa fa-coins"></i></div>
                                <div class="stat-label"><?php echo $module['label']; ?> Payables</div>
                                <div class="stat-amount" id="<?php echo $key; ?>_fee_tile_payable"><sup class="currency-symbol"><?php echo $currency; ?></sup>0.00</div>
                                <div class="stat-subtitle" id="<?php echo $key[0]; ?>date_payable">Students paid in advance</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- By Term Stats -->
        <div id="by_term" style="display: none;">
            <div class="section-title">
                <i class="fa fa-chart-bar"></i> Fees Collected (By Term)
            </div>
            <div class="row" style="row-gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($modules as $key => $module): ?>
                    <?php if(${'show_' . $key}): ?>
                    <div class="<?php echo $col_class; ?> mb-4">
                        <a href="javascript:;" onclick="show_payments_modal_term('<?php echo $key; ?>')" class="text-decoration-none">
                            <div class="stat-card" style="border-color: <?php echo $module['color']; ?>;">
                                <div class="stat-icon" style="color: <?php echo $module['color']; ?>;"><i class="fa fa-<?php echo $module['icon']; ?>"></i></div>
                                <div class="stat-label"><?php echo $module['label']; ?> Collected</div>
                                <div class="stat-amount" id="<?php echo $key; ?>_fee_tile_term"><sup class="currency-symbol"><?php echo $currency; ?></sup>0.00</div>
                                <div class="stat-subtitle" id="<?php echo $key[0]; ?>date2">Select term to view</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="section-title" style="margin-top: 2rem;">
                <i class="fa fa-exclamation-triangle"></i> Outstanding Fees
            </div>
            <div class="row" style="row-gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($modules as $key => $module): ?>
                    <?php if(${'show_' . $key}): ?>
                    <div class="<?php echo $col_class; ?> mb-4">
                        <a href="javascript:;" onclick="show_outstanding_modal_term('<?php echo $key; ?>')" class="text-decoration-none">
                            <div class="stat-card" style="border-color: #ef4444;">
                                <div class="stat-icon" style="color: #ef4444;"><i class="fa fa-exclamation-circle"></i></div>
                                <div class="stat-label"><?php echo $module['label']; ?> Outstanding</div>
                                <div class="stat-amount" id="<?php echo $key; ?>_fee_tile_owe_term"><sup class="currency-symbol"><?php echo $currency; ?></sup>0.00</div>
                                <div class="stat-subtitle" id="<?php echo $key[0]; ?>date_owe2">Click to view debtors</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="section-title" style="margin-top: 2rem;">
                <i class="fa fa-hand-holding-usd"></i> Advance Payments (Payables)
            </div>
            <div class="row" style="row-gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($modules as $key => $module): ?>
                    <?php if(${'show_' . $key}): ?>
                    <div class="<?php echo $col_class; ?> mb-4">
                        <a href="javascript:;" onclick="show_payables_modal_term('<?php echo $key; ?>')" class="text-decoration-none">
                            <div class="stat-card" style="border-color: #06b6d4;">
                                <div class="stat-icon" style="color: #06b6d4;"><i class="fa fa-coins"></i></div>
                                <div class="stat-label"><?php echo $module['label']; ?> Payables</div>
                                <div class="stat-amount" id="<?php echo $key; ?>_fee_tile_payable_term"><sup class="currency-symbol"><?php echo $currency; ?></sup>0.00</div>
                                <div class="stat-subtitle" id="<?php echo $key[0]; ?>date_payable2">Students paid in advance</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.datepicker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true
    });
    
    get_fct_by_date();
});

$('#by_date_form').submit(function(e) {
    e.preventDefault();
    get_fct_by_date();
    $('#by_date').show();
    $('#by_term').hide();
});

$('#byterm_form').submit(function(e) {
    e.preventDefault();
    
    let year = $('#year').val();
    let term = $('#term').val();
    
    if(!year || !term) {
        showAjaxModal_alert('Please select both year and term', 'error');
        return;
    }
    
    get_fct_by_term();
    $('#by_term').show();
    $('#by_date').hide();
});

function get_fct_by_date() {
    let date = $('#date_sel').val();
    $.ajax({
        url: '<?php echo site_url('admin/get_fct_bydate/search/'); ?>' + date,
        type: 'POST',
        dataType: 'json',
        success: function(r) {
            // Update all module tiles with smaller currency symbols
            $('#feeding_fee_tile').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_feeding_paid || '0.00'));
            $('#classes_fee_tile').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_classes_paid || '0.00'));
            $('#transport_fee_tile').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_fare_paid || '0.00'));
            $('#breakfast_fee_tile').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_breakfast_paid || '0.00'));
            $('#water_fee_tile').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_water_paid || '0.00'));
            $('#fdate, #cdate, #tdate, #bdate, #wdate').text(r[0].date_chosen);
            
            $('#feeding_fee_tile_owe').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_feeding_owe || '0.00'));
            $('#classes_fee_tile_owe').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_classes_owe || '0.00'));
            $('#transport_fee_tile_owe').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_fare_owe || '0.00'));
            $('#breakfast_fee_tile_owe').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_breakfast_owe || '0.00'));
            $('#water_fee_tile_owe').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_water_owe || '0.00'));
            $('#fdate_owe, #cdate_owe, #tdate_owe, #bdate_owe, #wdate_owe').text(r[0].date_chosen);
            
            $('#feeding_fee_tile_payable').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_feeding_payable || '0.00'));
            $('#classes_fee_tile_payable').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_classes_payable || '0.00'));
            $('#transport_fee_tile_payable').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_transport_payable || '0.00'));
            $('#breakfast_fee_tile_payable').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_breakfast_payable || '0.00'));
            $('#water_fee_tile_payable').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_water_payable || '0.00'));
            $('#fdate_payable, #cdate_payable, #tdate_payable, #bdate_payable, #wdate_payable').text(r[0].date_chosen);
        }
    });
}

function get_fct_by_term() {
    let year = $('#year').val();
    let term = $('#term').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/get_fct_byterm/search/'); ?>' + year + '/' + term,
        type: 'POST',
        dataType: 'json',
        success: function(r) {
            if(r && r[0]) {
                $('#feeding_fee_tile_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_feeding_paid || '0.00'));
                $('#classes_fee_tile_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_classes_paid || '0.00'));
                $('#transport_fee_tile_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_fare_paid || '0.00'));
                $('#breakfast_fee_tile_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_breakfast_paid || '0.00'));
                $('#water_fee_tile_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_water_paid || '0.00'));
                $('#fdate2, #cdate2, #tdate2, #bdate2, #wdate2').text(r[0].duration_chosen || 'No data');
                
                $('#feeding_fee_tile_owe_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_feeding_owe || '0.00'));
                $('#classes_fee_tile_owe_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_classes_owe || '0.00'));
                $('#transport_fee_tile_owe_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_fare_owe || '0.00'));
                $('#breakfast_fee_tile_owe_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_breakfast_owe || '0.00'));
                $('#water_fee_tile_owe_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_water_owe || '0.00'));
                $('#fdate_owe2, #cdate_owe2, #tdate_owe2, #bdate_owe2, #wdate_owe2').text(r[0].duration_chosen || 'No data');
                
                $('#feeding_fee_tile_payable_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_feeding_payable || '0.00'));
                $('#classes_fee_tile_payable_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_classes_payable || '0.00'));
                $('#transport_fee_tile_payable_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_transport_payable || '0.00'));
                $('#breakfast_fee_tile_payable_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_breakfast_payable || '0.00'));
                $('#water_fee_tile_payable_term').html('<sup class="currency-symbol"><?php echo $currency; ?></sup>' + (r[0].total_water_payable || '0.00'));
                $('#fdate_payable2, #cdate_payable2, #tdate_payable2, #bdate_payable2, #wdate_payable2').text(r[0].duration_chosen || 'No data');
            }
        }
    });
}

let current_date = '';

function show_payables_modal(fee_type) {
    let date = $('#date_sel').val();
    current_date = date;
    $.ajax({
        url: '<?php echo site_url('modal/popup/payables_details/'); ?>' + fee_type + '/' + date,
        success: function(response) {
            $('#modal_ajax .modal-dialog').css('max-width', '90%');
            $('#modal_ajax').modal('show', {backdrop: 'static'});
            $('#modal_ajax .modal-body').html(response);
        }
    });
}

function show_outstanding_modal(fee_type) {
    let date = $('#date_sel').val();
    current_date = date;
    $.ajax({
        url: '<?php echo site_url('modal/popup/outstanding_details/'); ?>' + fee_type + '/' + date,
        success: function(response) {
            $('#modal_ajax .modal-dialog').css('max-width', '90%');
            $('#modal_ajax').modal('show', {backdrop: 'static'});
            $('#modal_ajax .modal-body').html(response);
        }
    });
}

function show_payments_modal(fee_type) {
    let date = $('#date_sel').val();
    current_date = date;
    $.ajax({
        url: '<?php echo site_url('modal/popup/payments_details/'); ?>' + fee_type + '/' + date,
        success: function(response) {
            $('#modal_ajax .modal-dialog').css('max-width', '90%');
            $('#modal_ajax').modal('show', {backdrop: 'static'});
            $('#modal_ajax .modal-body').html(response);
        }
    });
}

function show_payments_modal_term(fee_type) {
    let year = $('#year').val();
    let term = $('#term').val();
    
    if(!year || !term) {
        showAjaxModal_alert('Please search by term first', 'error');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url('modal/popup/payments_details_term/'); ?>' + fee_type + '/' + year + '/' + term,
        success: function(response) {
            $('#modal_ajax .modal-dialog').css('max-width', '90%');
            $('#modal_ajax').modal('show', {backdrop: 'static'});
            $('#modal_ajax .modal-body').html(response);
        }
    });
}

function show_outstanding_modal_term(fee_type) {
    let year = $('#year').val();
    let term = $('#term').val();
    
    if(!year || !term) {
        showAjaxModal_alert('Please search by term first', 'error');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url('modal/popup/outstanding_details_term/'); ?>' + fee_type + '/' + year + '/' + term,
        success: function(response) {
            $('#modal_ajax .modal-dialog').css('max-width', '90%');
            $('#modal_ajax').modal('show', {backdrop: 'static'});
            $('#modal_ajax .modal-body').html(response);
        }
    });
}

function show_payables_modal_term(fee_type) {
    let year = $('#year').val();
    let term = $('#term').val();
    
    if(!year || !term) {
        showAjaxModal_alert('Please search by term first', 'error');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url('modal/popup/payables_details_term/'); ?>' + fee_type + '/' + year + '/' + term,
        success: function(response) {
            $('#modal_ajax .modal-dialog').css('max-width', '90%');
            $('#modal_ajax').modal('show', {backdrop: 'static'});
            $('#modal_ajax .modal-body').html(response);
        }
    });
}
</script>
