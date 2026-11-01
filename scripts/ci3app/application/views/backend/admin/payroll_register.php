<!-- Payroll Register Report (Task 14.1) -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-doc-text"></i>
                    <?php echo get_phrase('payroll_register'); ?>
                </div>
                <div class="panel-options">
                    <button class="btn btn-sm btn-success" id="exportExcelBtn">
                        <i class="entypo-download"></i> <?php echo get_phrase('export_to_excel'); ?>
                    </button>
                    <button class="btn btn-sm btn-danger" id="exportPdfBtn">
                        <i class="entypo-print"></i> <?php echo get_phrase('export_to_pdf'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-body">
                
                <!-- Filters (Task 14.1) -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('month'); ?>:</label>
                            <select name="filter_month" id="filter_month" class="form-control">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($m == date('n')) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?php echo get_phrase('year'); ?>:</label>
                            <select name="filter_year" id="filter_year" class="form-control">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('employment_category'); ?>:</label>
                            <select name="filter_category" id="filter_category" class="form-control">
                                <option value=""><?php echo get_phrase('all_categories'); ?></option>
                                <option value="teacher"><?php echo get_phrase('teachers'); ?></option>
                                <option value="administrator"><?php echo get_phrase('administrators'); ?></option>
                                <option value="non_teaching_staff"><?php echo get_phrase('non_teaching_staff'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?php echo get_phrase('status'); ?>:</label>
                            <select name="filter_status" id="filter_status" class="form-control">
                                <option value=""><?php echo get_phrase('all_statuses'); ?></option>
                                <option value="draft"><?php echo get_phrase('draft'); ?></option>
                                <option value="pending_approval"><?php echo get_phrase('pending_approval'); ?></option>
                                <option value="approved"><?php echo get_phrase('approved'); ?></option>
                                <option value="paid"><?php echo get_phrase('paid'); ?></option>
                                <option value="rejected"><?php echo get_phrase('rejected'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="applyFiltersBtn">
                                <i class="entypo-search"></i> <?php echo get_phrase('apply'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Payroll Register Table (Task 14.2) -->
                <table class="table table-bordered table-striped datatable" id="payroll_register_table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('staff_name'); ?></th>
                            <th><?php echo get_phrase('staff_code'); ?></th>
                            <th><?php echo get_phrase('category'); ?></th>
                            <th><?php echo get_phrase('gross_salary'); ?></th>
                            <th><?php echo get_phrase('total_deductions'); ?></th>
                            <th><?php echo get_phrase('net_salary'); ?></th>
                            <th><?php echo get_phrase('status'); ?></th>
                            <th><?php echo get_phrase('payment_method'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background-color: #f5f5f5;">
                            <td colspan="3"><?php echo get_phrase('totals'); ?>:</td>
                            <td id="total_gross">GH¢ 0.00</td>
                            <td id="total_deductions">GH¢ 0.00</td>
                            <td id="total_net">GH¢ 0.00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>

            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Payroll Register -->
<script type="text/javascript">
$(document).ready(function() {
    // Initialize DataTable
    var registerTable = $('#payroll_register_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo base_url(); ?>index.php?admin/get_payroll_register_data',
            type: 'POST',
            data: function(d) {
                d.month = $('#filter_month').val();
                d.year = $('#filter_year').val();
                d.category = $('#filter_category').val();
                d.status = $('#filter_status').val();
                d.<?php echo $this->security->get_csrf_token_name(); ?> = '<?php echo $this->security->get_csrf_hash(); ?>';
            },
            dataSrc: function(json) {
                // Update totals
                if (json.totals) {
                    $('#total_gross').text('GH¢ ' + formatNumber(json.totals.gross));
                    $('#total_deductions').text('GH¢ ' + formatNumber(json.totals.deductions));
                    $('#total_net').text('GH¢ ' + formatNumber(json.totals.net));
                }
                return json.data;
            }
        },
        columns: [
            { data: 'staff_name' },
            { data: 'staff_code' },
            { data: 'category' },
            { 
                data: 'gross_salary',
                render: function(data) {
                    return 'GH¢ ' + formatNumber(data);
                }
            },
            { 
                data: 'total_deductions',
                render: function(data) {
                    return 'GH¢ ' + formatNumber(data);
                }
            },
            { 
                data: 'net_salary',
                render: function(data) {
                    return 'GH¢ ' + formatNumber(data);
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    var badges = {
                        'draft': 'badge-secondary',
                        'pending_approval': 'badge-warning',
                        'approved': 'badge-success',
                        'paid': 'badge-primary',
                        'rejected': 'badge-danger'
                    };
                    var badgeClass = badges[data] || 'badge-secondary';
                    return '<span class="badge ' + badgeClass + '">' + data.replace('_', ' ').toUpperCase() + '</span>';
                }
            },
            { data: 'payment_method' }
        ],
        order: [[0, 'asc']], // Order by staff name
        pageLength: 50,
        footerCallback: function() {
            // Footer already updated via dataSrc callback
        }
    });
    
    // Apply filters button
    $('#applyFiltersBtn').on('click', function() {
        registerTable.ajax.reload();
    });
    
    // Export to Excel button (Task 14.3)
    $('#exportExcelBtn').on('click', function() {
        var month = $('#filter_month').val();
        var year = $('#filter_year').val();
        var category = $('#filter_category').val();
        var status = $('#filter_status').val();
        
        var params = ['month=' + month, 'year=' + year];
        if (category) params.push('category=' + category);
        if (status) params.push('status=' + status);
        
        var url = '<?php echo base_url(); ?>index.php?admin/export_payroll_register_excel&' + params.join('&');
        window.location.href = url;
    });
    
    // Export to PDF button (Task 14.4)
    $('#exportPdfBtn').on('click', function() {
        var month = $('#filter_month').val();
        var year = $('#filter_year').val();
        var category = $('#filter_category').val();
        var status = $('#filter_status').val();
        
        var params = ['month=' + month, 'year=' + year];
        if (category) params.push('category=' + category);
        if (status) params.push('status=' + status);
        
        var url = '<?php echo base_url(); ?>index.php?admin/export_payroll_register_pdf&' + params.join('&');
        window.open(url, '_blank');
    });
    
    // Format number helper
    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
});
</script>

<!-- Custom CSS -->
<style>
    .badge {
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-secondary { background-color: #6c757d; color: white; }
    .badge-warning { background-color: #f0ad4e; color: white; }
    .badge-success { background-color: #5cb85c; color: white; }
    .badge-primary { background-color: #337ab7; color: white; }
    .badge-danger { background-color: #d9534f; color: white; }
    
    #payroll_register_table tfoot tr {
        border-top: 2px solid #333;
    }
</style>
