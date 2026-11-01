<style>
.audit-filters {
    background: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}
.audit-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}
.action-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.action-lock { background: #fee2e2; color: #991b1b; }
.action-lock::before { content: '🔒'; }
.action-unlock { background: #d1fae5; color: #065f46; }
.action-unlock::before { content: '🔓'; }
.action-edit { background: #dbeafe; color: #1e40af; }
.action-edit::before { content: '✏️'; }
.action-create { background: #fef3c7; color: #92400e; }
.action-create::before { content: '➕'; }
.action-delete { background: #fee2e2; color: #991b1b; }
.action-delete::before { content: '🗑️'; }
.audit-filters .form-control {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 10px 14px;
    transition: all 0.2s;
}
.audit-filters .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.audit-filters .btn {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.2s;
}
.audit-filters .btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
}
.audit-filters .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.audit-filters .btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
}
.audit-filters label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
    font-size: 13px;
}
#auditTable thead th {
    background: #f9fafb;
    color: #374151;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    padding: 16px 12px;
    border-bottom: 2px solid #e5e7eb;
}
#auditTable tbody td {
    padding: 14px 12px;
    vertical-align: middle;
    color: #4b5563;
}
#auditTable tbody tr {
    transition: background 0.2s;
}
#auditTable tbody tr:hover {
    background: #f9fafb;
}
</style>

<?php include 'audit_stats_widget.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-history"></i> <?php echo get_phrase('audit_trail'); ?></h3>
            </div>
            <div class="panel-body">
                <div class="audit-filters">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Record Type</label>
                            <select id="filter_type" class="form-control">
                                <option value="">All</option>
                                <option value="invoice">Invoice</option>
                                <option value="payment">Payment</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Action</label>
                            <select id="filter_action" class="form-control">
                                <option value="">All</option>
                                <option value="lock">Lock</option>
                                <option value="unlock">Unlock</option>
                                <option value="edit">Edit</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Date From</label>
                            <input type="date" id="filter_date_from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Date To</label>
                            <input type="date" id="filter_date_to" class="form-control">
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button onclick="filterAudit()" class="btn btn-primary"><i class="fa fa-filter"></i> Apply Filters</button>
                        <button onclick="resetFilters()" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
                        <button onclick="exportAudit()" class="btn btn-success pull-right"><i class="fa fa-download"></i> Export</button>
                    </div>
                </div>

                <div class="audit-table">
                    <table id="auditTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Record Type</th>
                                <th>Record ID</th>
                                <th>Action</th>
                                <th>Performed By</th>
                                <th>IP Address</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var auditTable;
$(document).ready(function() {
    auditTable = $('#auditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo site_url('admin/get_audit_trail'); ?>',
            type: 'POST',
            data: function(d) {
                d.filter_type = $('#filter_type').val();
                d.filter_action = $('#filter_action').val();
                d.filter_date_from = $('#filter_date_from').val();
                d.filter_date_to = $('#filter_date_to').val();
            }
        },
        columns: [
            { data: 'timestamp', width: '15%' },
            { data: 'record_type', width: '10%' },
            { data: 'record_id', width: '8%' },
            { data: 'action', width: '10%' },
            { data: 'performed_by', width: '15%' },
            { data: 'ip_address', width: '12%' },
            { data: 'notes', width: '30%' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });
});

function filterAudit() {
    auditTable.ajax.reload();
}

function resetFilters() {
    $('#filter_type, #filter_action').val('');
    $('#filter_date_from, #filter_date_to').val('');
    auditTable.ajax.reload();
}

function exportAudit() {
    const params = new URLSearchParams({
        type: $('#filter_type').val(),
        action: $('#filter_action').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val()
    });
    window.open('<?php echo site_url('admin/export_audit_trail'); ?>?' + params.toString());
}
</script>
