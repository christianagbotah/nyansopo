<?php
/**
 * ENTERPRISE: Admin view of cashier dashboards with filtering
 */
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
$cashiers = $this->db->get_where('admin', ['level' => 4])->result_array();
?>

<style>
.filter-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; width: 100%; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 42px; white-space: nowrap; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
#dashboard-container { min-height: 400px; }
@media (max-width: 768px) {
    .filter-card { padding: 15px; }
    .btn-modern { width: 100%; justify-content: center; }
}
</style>

<div class="p-4">
    <!-- Filter Card -->
    <div class="filter-card">
        <h2 style="margin-bottom: 25px; color: #1f2937; font-size: 20px; font-weight: 700;">
            <i class="fa fa-filter" style="color: #3b82f6;"></i> Filter Cashier Dashboard
        </h2>
        <div class="row">
            <div class="col-lg-4 col-md-6 col-12">
                <label class="form-label"><i class="fa fa-user"></i> Cashier</label>
                <select id="cashier_filter" class="form-control">
                    <option value="all">All Cashiers</option>
                    <?php foreach ($cashiers as $cashier): ?>
                        <option value="<?php echo $cashier['admin_id']; ?>"><?php echo $cashier['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label"><i class="fa fa-calendar"></i> Date From</label>
                <input type="text" id="date_from" class="form-control air-datepicker" placeholder="Select start date" data-position="bottom left" value="<?php echo date('M j, Y'); ?>">
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label"><i class="fa fa-calendar"></i> Date To</label>
                <input type="text" id="date_to" class="form-control air-datepicker" placeholder="Select end date" data-position="bottom left" value="<?php echo date('M j, Y'); ?>">
            </div>
            <div class="col-lg-2 col-md-6 col-12">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn-modern btn-primary-modern" style="width: 100%;" onclick="loadDashboard()">
                    <i class="fa fa-search"></i> Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Dashboard Container -->
    <div id="dashboard-container">
        <div style="text-align: center; padding: 80px 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 120px; height: 120px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);">
                <i class="fa fa-chart-bar" style="font-size: 60px; color: white;"></i>
            </div>
            <h3 style="color: #2d3748; font-weight: 700; margin-bottom: 15px; font-size: 24px;">Cashier Performance Dashboard</h3>
            <p style="color: #718096; font-size: 16px; max-width: 500px; margin: 0 auto 30px;">Select a cashier from the dropdown above to view their performance metrics, or choose "All Cashiers" to see combined statistics.</p>
            <div style="display: flex; gap: 20px; justify-content: center; margin-top: 40px;">
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; background: #e0e7ff; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="fa fa-money-bill-wave" style="font-size: 24px; color: #667eea;"></i>
                    </div>
                    <p style="color: #4a5568; font-size: 13px; font-weight: 600;">Collections</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; background: #d1fae5; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="fa fa-receipt" style="font-size: 24px; color: #11998e;"></i>
                    </div>
                    <p style="color: #4a5568; font-size: 13px; font-weight: 600;">Transactions</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; background: #dbeafe; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="fa fa-users" style="font-size: 24px; color: #4facfe;"></i>
                    </div>
                    <p style="color: #4a5568; font-size: 13px; font-weight: 600;">Students</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadDashboard() {
    const cashierId = $('#cashier_filter').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    
    if (cashierId === 'all') {
        loadAllCashiersDashboard(dateFrom, dateTo);
    } else {
        loadSingleCashierDashboard(cashierId, dateFrom, dateTo);
    }
}

function loadSingleCashierDashboard(cashierId, dateFrom, dateTo) {
    console.log('Loading single cashier dashboard...');
    console.log('Cashier ID:', cashierId);
    console.log('Date From:', dateFrom);
    console.log('Date To:', dateTo);
    
    $('#dashboard-container').html('<center><div style="padding: 60px;"><i class="fa fa-spinner fa-spin fa-3x" style="color: #3b82f6;"></i><p style="margin-top: 20px; color: #718096; font-weight: 600;">Loading dashboard...</p></div></center>');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_cashier_dashboard_data'); ?>',
        type: 'POST',
        data: { cashier_id: cashierId, date_from: dateFrom, date_to: dateTo },
        dataType: 'json'
    }).done(function(response) {
        console.log('Response received:', response);
        if (response.debug) {
            console.log('Debug info:', response.debug);
        }
        if (response.status === 'success') {
            $('#dashboard-container').html(response.html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load dashboard', 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX failed:', status, error);
        console.error('Response text:', xhr.responseText);
        showAjaxModal_alert('An error occurred while loading dashboard', 'error');
    });
}

function loadAllCashiersDashboard(dateFrom, dateTo) {
    $('#dashboard-container').html('<center><div style="padding: 60px;"><i class="fa fa-spinner fa-spin fa-3x" style="color: #3b82f6;"></i><p style="margin-top: 20px; color: #718096; font-weight: 600;">Loading combined dashboard...</p></div></center>');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_all_cashiers_dashboard'); ?>',
        type: 'POST',
        data: { date_from: dateFrom, date_to: dateTo },
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            $('#dashboard-container').html(response.html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load dashboard', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred while loading dashboard', 'error');
    });
}

// Auto-load first cashier on page load
$(document).ready(function() {
    // Initialize Bootstrap Datepicker with correct format
    $('.air-datepicker').datepicker({
        format: 'M d, yyyy',
        autoclose: true,
        todayHighlight: true
    });
    
    // Select "All Cashiers" option and load dashboard
    $('#cashier_filter').val('all');
    loadDashboard();
});

// Use event delegation to prevent double-click issues
$(document).on('click', '.fee-card', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const feeType = $(this).data('fee-type');
    const category = $(this).data('category');
    const cashierId = $(this).data('cashier-id');
    
    if (cashierId === 'all') {
        if (category === 'arrears') {
            showAllCashiersArrearsDetails(feeType);
        } else {
            showAllCashiersFeeDetails(feeType);
        }
    } else {
        showFeeDetails(feeType, category, cashierId);
    }
});

// Global functions for fee details modals
function showFeeDetails(feeType, category, cashierId) {
    showAjaxModal_alert('Loading details...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_fee_details'); ?>',
        type: 'POST',
        data: {
            fee_type: feeType,
            category: category,
            cashier_id: cashierId,
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if(response.status === 'success') {
            showModalWithContent('detailsModal', '<i class="fa fa-list"></i> Fee Details', response.html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load details', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function showAllCashiersFeeDetails(feeType) {
    showAjaxModal_alert('Loading combined details...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_fee_details'); ?>',
        type: 'POST',
        data: {
            fee_type: feeType,
            category: 'collected',
            cashier_id: 'all',
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if(response.status === 'success') {
            showModalWithContent('detailsModal', '<i class="fa fa-list"></i> All Cashiers - ' + feeType.charAt(0).toUpperCase() + feeType.slice(1) + ' Details', response.html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load details', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function showAllCashiersArrearsDetails(feeType) {
    showAjaxModal_alert('Loading arrears details...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_fee_details'); ?>',
        type: 'POST',
        data: {
            fee_type: feeType,
            category: 'arrears',
            cashier_id: 'all'
        },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if(response.status === 'success') {
            showModalWithContent('detailsModal', '<i class="fa fa-exclamation-triangle"></i> Outstanding - ' + feeType.charAt(0).toUpperCase() + feeType.slice(1), response.html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load details', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}
</script>
