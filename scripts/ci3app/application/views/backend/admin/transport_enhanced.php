<style>
.transport-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    cursor: pointer;
    border-left: 4px solid;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}
.transport-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}
.stat-content {
    text-align: right;
    flex: 1;
}
.stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}
.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}
.action-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    white-space: nowrap;
    margin-bottom: 10px;
    display: inline-block;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
#transport_table {
    border-collapse: separate;
    border-spacing: 0;
}
#transport_table thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    color: #1e293b;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
}
#transport_table tbody td {
    padding: 10px 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
    color: #374151;
    vertical-align: middle;
    line-height: 1.4;
}
#transport_table tbody tr {
    background: white;
    transition: all 0.2s ease;
}
#transport_table tbody tr:hover {
    background: #f8fafc !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
#transport_table tbody tr td:first-child {
    font-weight: 700;
    color: #3b82f6;
}
.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.3s;
}
.dataTables_wrapper .dataTables_length select:focus,
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 6px;
    padding: 8px 14px;
    margin: 0 2px;
    transition: all 0.2s;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white !important;
    border: none;
}
.report-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-top: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.header-actions-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.header-btn-mobile {
    padding: 10px 16px !important;
    font-size: 14px !important;
}
@media print {
    .no-print { display: none !important; }
    .report-section { box-shadow: none; }
}
@media (max-width: 768px) {
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }
    .stat-value {
        font-size: 24px;
    }
    .stat-value sup {
        font-size: 14px !important;
    }
    .stat-label {
        font-size: 12px;
    }
    .transport-stat-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    .action-btn {
        width: 100%;
        text-align: center;
        margin-right: 0 !important;
        padding: 12px 16px;
        margin-bottom: 8px;
    }
    .header-actions-container {
        flex-direction: column;
        width: 100%;
    }
    .header-btn-mobile {
        width: 100%;
        text-align: center;
        margin-right: 0 !important;
        margin-bottom: 8px;
    }
    .panel-heading > div {
        flex-direction: column !important;
        gap: 15px;
    }
    .panel-heading h4 {
        font-size: 18px;
        text-align: center;
    }
    #transport_table {
        font-size: 12px;
    }
    #transport_table thead th {
        padding: 8px 6px;
        font-size: 11px;
    }
    #transport_table tbody td {
        padding: 8px 6px;
        font-size: 12px;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        text-align: center;
        margin-bottom: 10px;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        text-align: center;
        margin-top: 10px;
    }
    .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding: 12px !important;
    }
    .report-section {
        padding: 16px;
    }
    .col-xs-12 {
        padding-left: 8px;
        padding-right: 8px;
    }
    .panel-body {
        padding: 10px !important;
    }
}
    .dataTables_wrapper .dataTables_paginate {
        text-align: center;
        margin-top: 10px;
    }
    .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
@media (max-width: 480px) {
    .stat-value {
        font-size: 20px;
    }
    .stat-icon {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
    #transport_table thead th {
        padding: 6px 4px;
        font-size: 10px;
    }
    #transport_table tbody td {
        padding: 6px 4px;
        font-size: 11px;
    }
    .header-btn-mobile {
        padding: 10px 12px !important;
        font-size: 13px !important;
    }
    .panel-heading h4 {
        font-size: 16px;
    }
    .row {
        margin-left: 0;
        margin-right: 0;
    }
    .table-responsive-wrapper {
        padding: 8px !important;
    }
}
/* Improve table scrolling on mobile */
.dataTables_wrapper {
    width: 100%;
}
/* Make sure action buttons stack nicely on very small screens */
@media (max-width: 360px) {
    .stat-value {
        font-size: 18px;
    }
    .stat-label {
        font-size: 11px;
    }
    .header-btn-mobile {
        font-size: 12px !important;
        padding: 8px 10px !important;
    }
    #transport_table thead th,
    #transport_table tbody td {
        font-size: 10px;
        padding: 4px 3px;
    }
}

</style>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <h4 style="margin: 0; color: white; font-weight: 700;">
                        <i class="fa fa-bus"></i> <?php echo get_phrase('transport_management'); ?>
                    </h4>
                    <div class="no-print header-actions-container">
                        <button onclick="navigation('<?php echo site_url('admin/transport_reports'); ?>')" class="btn header-btn-mobile" style="background: white; color: #3b82f6; font-weight: 600; font-size: 14px; padding: 10px 20px; margin-right: 10px; border: 2px solid #3b82f6; border-radius: 8px;">
                            <i class="entypo-chart-line"></i> <span class="hidden-xs">View Reports</span><span class="visible-xs">Reports</span>
                        </button>
                        <button onclick="exportTemplate()" class="btn header-btn-mobile" style="background: #8b5cf6; color: white; font-weight: 600; font-size: 14px; padding: 10px 20px; margin-right: 10px; border: none; border-radius: 8px;">
                            <i class="fa fa-download"></i> <span class="hidden-xs">Export Template</span><span class="visible-xs">Template</span>
                        </button>
                        <button onclick="showImportModal()" class="btn header-btn-mobile" style="background: #f59e0b; color: white; font-weight: 600; font-size: 14px; padding: 10px 20px; margin-right: 10px; border: none; border-radius: 8px;">
                            <i class="fa fa-upload"></i> <span class="hidden-xs">Import Excel</span><span class="visible-xs">Import</span>
                        </button>
                        <button onclick="window.print()" class="btn header-btn-mobile" style="background: rgba(255,255,255,0.2); color: white; font-weight: 600; font-size: 14px; padding: 10px 20px; margin-right: 10px; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px;">
                            <i class="fa fa-print"></i> <span class="hidden-xs">Print</span><span class="visible-xs">Print</span>
                        </button>
                        <button onclick="exportToExcel()" class="btn header-btn-mobile" style="background: #10b981; color: white; font-weight: 600; font-size: 14px; padding: 10px 20px; border: none; border-radius: 8px;">
                            <i class="fa fa-file-excel"></i> <span class="hidden-xs">Export Excel</span><span class="visible-xs">Excel</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="background: #f5f7fa;">
                <!-- Statistics Cards -->
                <div class="row">
                    <?php 
                    $total_routes = $this->db->count_all('transport');
                    $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
                    $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
                    $students_count = $this->db->where('transport_id IS NOT NULL', null, false)
                        ->where('year', $running_year)
                        ->where('term', $running_term)
                        ->from('enroll')->count_all_results();
                    $assigned_routes = $this->db->query("SELECT COUNT(DISTINCT transport_id) as count FROM enroll WHERE transport_id IS NOT NULL AND year = '$running_year' AND term = '$running_term'")->row()->count;
                    $total_collected = $this->db->query("SELECT COALESCE(SUM(transport_amount), 0) as total FROM daily_fee_transactions WHERE year = '$running_year' AND term = '$running_term'")->row()->total;
                    ?>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="transport-stat-card" style="border-left-color: #3b82f6;" onclick="showStatModal('routes')">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white;">
                                <i class="fa fa-route"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" style="color: #3b82f6;"><?php echo $total_routes; ?></div>
                                <div class="stat-label"><?php echo get_phrase('total_routes'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="transport-stat-card" style="border-left-color: #10b981;" onclick="showStatModal('students')">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" style="color: #10b981;"><?php echo $students_count; ?></div>
                                <div class="stat-label"><?php echo get_phrase('students'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="transport-stat-card" style="border-left-color: #f59e0b;" onclick="showStatModal('assigned')">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                                <i class="fa fa-bus-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" style="color: #f59e0b;"><?php echo $assigned_routes; ?></div>
                                <div class="stat-label"><?php echo get_phrase('active_routes'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="transport-stat-card" style="border-left-color: #8b5cf6;" onclick="showStatModal('collected')">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white;">
                                <i class="fa fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" style="color: #8b5cf6;"><sup style="font-size: 18px; font-weight: 600;">GHS</sup> <?php echo number_format($total_collected, 2); ?></div>
                                <div class="stat-label"><?php echo get_phrase('fare_collected/term'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Action Buttons -->
                <div class="no-print" style="margin-bottom: 20px;">
                    <button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_transport_add'); ?>')" class="action-btn" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; margin-right: 10px;">
                        <i class="fa fa-plus"></i> <?php echo get_phrase('add_route'); ?>
                    </button>
                    <button onclick="navigation('<?php echo site_url('admin/assign_transport'); ?>')" class="action-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; margin-right: 10px;">
                        <i class="fa fa-user-plus"></i> <?php echo get_phrase('assign_students'); ?>
                    </button>
                    <button onclick="navigation('<?php echo site_url('admin/transport_reports'); ?>')" class="action-btn" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; margin-right: 10px;">
                        <i class="entypo-chart-line"></i> <?php echo get_phrase('view_reports'); ?>
                    </button>
                    <button onclick="navigation('<?php echo site_url('fee_collection'); ?>')" class="action-btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                        <i class="fa fa-hand-holding-usd"></i> <?php echo get_phrase('collect_fare'); ?>
                    </button>
                </div>

                <!-- Transport Routes Table -->
                <div class="table-responsive-wrapper" style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow-x: auto;">
                    <table class="table table-bordered datatable" id="transport_table" style="width:100%; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th style="width: 80px; min-width: 60px;"><?php echo get_phrase('route_id'); ?></th>
                                <th style="min-width: 120px;"><?php echo get_phrase('route_name'); ?></th>
                                <th style="width: 100px; text-align: center; min-width: 80px;"><?php echo get_phrase('vehicles'); ?></th>
                                <th style="width: 120px; min-width: 90px;"><?php echo get_phrase('fare'); ?></th>
                                <th style="min-width: 150px;"><?php echo get_phrase('description'); ?></th>
                                <th style="width: 100px; text-align: center; min-width: 80px;"><?php echo get_phrase('students'); ?></th>
                                <th class="no-print" style="width: 120px; text-align: center; min-width: 100px;"><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>
<script>
$(document).ready(function() {
    $('#transport_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '<?php echo site_url("admin/get_transports"); ?>', type: 'POST' },
        columns: [
            { data: 'transport_id', className: 'font-semibold' },
            { data: 'route_name' },
            { data: 'number_of_vehicle', className: 'text-center' },
            { data: 'route_fare' },
            { data: 'description' },
            { data: 'students_count', className: 'text-center' },
            { data: 'options', orderable: false, className: 'text-center no-print' }
        ],
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "Search routes:",
            lengthMenu: "Show _MENU_ routes",
            info: "Showing _START_ to _END_ of _TOTAL_ routes",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
});

function transport_edit_modal(transport_id) {
    showAjaxModal('<?php echo site_url("admin/modal_transport_edit/"); ?>' + transport_id);
}

function transport_delete_confirm(transport_id) {
    showConfirmModal(
        'Confirm Delete',
        'Are you sure you want to delete this transport route?',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/transport/delete/"); ?>' + transport_id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if (response.message == 'done') {
                    showAjaxModal_alert('Deleted successfully', 'success');
                    setTimeout(() => $('#transport_table').DataTable().ajax.reload(null, false), 2000);
                } else {
                    showAjaxModal_alert('Delete failed', 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Delete',
        'danger'
    );
}

function showStatModal(type) {
    if(type === 'collected') {
        showCollectedFaresModal();
    } else {
        showAjaxModal('<?php echo site_url("admin/modal_transport_stats/"); ?>' + type, 'big');
    }
}

function showCollectedFaresModal() {
    showAjaxModal_alert('Loading data...', 'loading');
    $.ajax({
        url: '<?php echo site_url("admin/get_transport_collected_fares_by_term"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if(response.status === 'success') {
            var html = '<div id="collectedFaresContent">';

            html += '<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;" class="no-print">';
            html += '<h4 style="margin: 0; color: #1f2937; font-weight: 700;">Transport Fares Collected - <?php echo get_settings("running_term"); ?> Term, <?php echo get_settings("running_year"); ?></h4>';
            html += '<button onclick="printCollectedFares()" class="btn btn-primary" style="background: #3b82f6; border: none; padding: 10px 20px; border-radius: 8px;">';
            html += '<i class="fa fa-print"></i> Print</button></div>';
            html += '<div style="background: #f0fdf4; padding: 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981;">';
            html += '<div style="font-size: 24px; font-weight: 700; color: #10b981;">Total: GHS ' + parseFloat(response.total).toFixed(2) + '</div>';
            html += '<div style="color: #6b7280; margin-top: 4px;">' + response.students.length + ' students made payments</div></div>';
            html += '<table class="table table-bordered" style="background: white;"><thead style="background: #f3f4f6;"><tr>';
            html += '<th style="padding: 12px;">#</th><th style="padding: 12px;">Student Code</th><th style="padding: 12px;">Student Name</th>';
            html += '<th style="padding: 12px;">Class</th><th style="padding: 12px; text-align: right;">Amount Paid (GHS)</th></tr></thead><tbody>';
            
            response.students.forEach(function(student, index) {
                html += '<tr><td style="padding: 12px;">' + (index + 1) + '</td>';
                html += '<td style="padding: 12px; font-weight: 600;">' + student.student_code + '</td>';
                html += '<td style="padding: 12px;">' + student.student_name + '</td>';
                html += '<td style="padding: 12px;">' + student.class_name + (student.section_name ? ' ' + student.section_name : '') + '</td>';
                html += '<td style="padding: 12px; text-align: right; font-weight: 700; color: #10b981;">' + parseFloat(student.total_paid).toFixed(2) + '</td></tr>';
            });
            
            html += '</tbody></table></div>';
            
            showModalWithContent('detailsModal', '<i class="fa fa-money-bill-wave"></i> Transport Fares Collected This Term', html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load data', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function printCollectedFares() {
    var content = document.getElementById('collectedFaresContent').innerHTML;
    var printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write('<html><head><title>Transport Fares Collected</title>');
    printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 20px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }');
    printWindow.document.write('th { background: #f3f4f6; font-weight: 700; }');
    printWindow.document.write('.no-print { display: none !important; }');
    printWindow.document.write('h4 { color: #1f2937; }</style></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('<script>window.print(); window.close();<\/script></body></html>');
    printWindow.document.close();
}

function exportToExcel() {
    showAjaxModal_alert('Preparing Excel export...', 'loading');
    $.ajax({
        url: '<?php echo site_url("admin/get_transport_report_data"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(data) {
        const wb = XLSX.utils.book_new();
        
        // Summary sheet
        const summaryData = [
            ['Transport Management Report'],
            ['Generated:', new Date().toLocaleString()],
            [''],
            ['Summary Statistics'],
            ['Total Routes', data.total_routes],
            ['Total Students', data.total_students],
            ['Active Routes', data.active_routes],
            ['Total Collected (GHS)', parseFloat(data.total_collected).toFixed(2)]
        ];
        const summaryWs = XLSX.utils.aoa_to_sheet(summaryData);
        XLSX.utils.book_append_sheet(wb, summaryWs, 'Summary');
        
        // Routes sheet
        const routesData = [['Route Name', 'Vehicles', 'Fare (GHS)', 'Students', 'Description']];
        data.routes.forEach(route => {
            routesData.push([
                route.route_name,
                route.number_of_vehicle,
                parseFloat(route.route_fare).toFixed(2),
                route.students_count,
                route.description || '-'
            ]);
        });
        const routesWs = XLSX.utils.aoa_to_sheet(routesData);
        XLSX.utils.book_append_sheet(wb, routesWs, 'Routes');
        
        XLSX.writeFile(wb, 'Transport_Report_' + new Date().toISOString().split('T')[0] + '.xlsx');
        showAjaxModal_alert('Excel file downloaded successfully', 'success', false);
    }).fail(function() {
        showAjaxModal_alert('Failed to export to Excel', 'error');
    });
}

function exportTemplate() {
    showAjaxModal_alert('Generating template...', 'loading');
    window.location.href = '<?php echo site_url("admin/get_transport_template_data"); ?>';
    setTimeout(function() {
        showAjaxModal_alert('Template downloaded successfully!<br>Check your Downloads folder', 'success', false);
    }, 10000);
}

function showImportModal() {
    var content = '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 20px; border-radius: 8px;">';
    content += '<p style="margin: 0; color: #92400e; font-size: 14px;"><i class="fa fa-info-circle"></i> <strong>Instructions:</strong><br>';
    content += '1. Download template &rarr; 2. Fill data &rarr; 3. Upload file</p></div>';
    content += '<form id="importForm" enctype="multipart/form-data">';
    content += '<input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">';
    content += '<div class="row"><div class="col-md-7"><div class="form-group">';
    content += '<label style="font-weight: 600; color: #374151;">Select Excel File (.xlsx)</label>';
    content += '<input type="file" name="excel_file" id="excel_file" accept=".xlsx" class="form-control" required style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">';
    content += '</div></div><div class="col-md-5"><div class="form-group">';
    content += '<label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">Options</label>';
    content += '<label style="display: flex; align-items: center; cursor: pointer; margin-top: 10px;">';
    content += '<input type="checkbox" name="create_routes" value="1" checked style="margin-right: 8px; width: 18px; height: 18px;">';
    content += '<span style="color: #374151;">Create new routes</span></label></div></div></div>';
    content += '<button type="button" onclick="processImport()" class="btn btn-primary btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 12px; font-size: 16px; font-weight: 600;">';
    content += '<i class="fa fa-upload"></i> Import Data</button></form>';
    showModalWithContent('createModal', '<i class="fa fa-upload"></i> Import Transport Data', content);
}

function processImport() {
    const fileInput = document.getElementById('excel_file');
    if (!fileInput.files.length) {
        showAjaxModal_alert('Please select a file', 'error');
        return;
    }
    
    $('.close').click();
    showAjaxModal_alert('Processing import...', 'loading');
    
    const formData = new FormData(document.getElementById('importForm'));
    
    $.ajax({
        url: '<?php echo site_url("admin/import_transport_data"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            $('#transport_table').DataTable().ajax.reload(null, false);
            showAjaxModal_alert(response.message + '<br>Routes: ' + response.routes_created + ' created, ' + response.routes_updated + ' updated<br>Students: ' + response.students_assigned + ' assigned', 'success');
            setTimeout(() => location.reload(), 3000);
        } else {
            showAjaxModal_alert(response.message || 'Import failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred during import', 'error');
    });
}
</script>



