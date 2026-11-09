<style>
.audit-header { background: linear-gradient(135deg, #434343 0%, #000000 100%); padding: 32px; border-radius: 16px; color: white; margin-bottom: 24px; }
.action-create { color: #10b981; font-weight: 600; }
.action-update { color: #3b82f6; font-weight: 600; }
.action-delete { color: #ef4444; font-weight: 600; }
.action-post { color: #8b5cf6; font-weight: 600; }
.action-void { color: #f59e0b; font-weight: 600; }
</style>

<div class="audit-header">
    <div>
        <h2 class="mb-2"><i class="fa fa-history mr-2"></i><?php echo get_phrase('audit_trail'); ?></h2>
        <p class="mb-0 opacity-90">Complete audit log of all financial transactions</p>
    </div>
</div>

<div class="modern-card">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label><?php echo get_phrase('from_date'); ?></label>
                        <input type="date" id="fromDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label><?php echo get_phrase('to_date'); ?></label>
                        <input type="date" id="toDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label><?php echo get_phrase('action_type'); ?></label>
                        <select id="actionType" class="form-control">
                            <option value=""><?php echo get_phrase('all'); ?></option>
                            <option value="create"><?php echo get_phrase('create'); ?></option>
                            <option value="update"><?php echo get_phrase('update'); ?></option>
                            <option value="delete"><?php echo get_phrase('delete'); ?></option>
                            <option value="post"><?php echo get_phrase('post'); ?></option>
                            <option value="void"><?php echo get_phrase('void'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label><?php echo get_phrase('user'); ?></label>
                        <select id="userId" class="form-control">
                            <option value=""><?php echo get_phrase('all_users'); ?></option>
                        </select>
                    </div>
                </div>

                <table id="auditTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('date_time'); ?></th>
                            <th><?php echo get_phrase('user'); ?></th>
                            <th><?php echo get_phrase('action'); ?></th>
                            <th><?php echo get_phrase('module'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th><?php echo get_phrase('ip_address'); ?></th>
                            <th><?php echo get_phrase('details'); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="auditDetailsModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('audit_details'); ?></h4>
            </div>
            <div class="modal-body">
                <div id="auditDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#auditTable').DataTable({
        ajax: {
            url: '<?php echo site_url("accounts/audit_trail"); ?>',
            data: function(d) {
                d.get_data = 1;
                d.from_date = $('#fromDate').val();
                d.to_date = $('#toDate').val();
                d.action_type = $('#actionType').val();
                d.user_id = $('#userId').val();
            }
        },
        columns: [
            { data: 'created_at' },
            { data: 'user_name' },
            { data: 'action' },
            { data: 'module' },
            { data: 'description' },
            { data: 'ip_address' },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-info" onclick="viewDetails(' + row.id + ')"><i class="fa fa-eye"></i></button>';
                }
            }
        ],
        order: [[0, 'desc']]
    });

    $('#fromDate, #toDate, #actionType, #userId').on('change', function() {
        table.ajax.reload();
    });
});

function viewDetails(id) {
    showAjaxModal_alert('<?php echo get_phrase("loading"); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("accounts/audit_trail/details/"); ?>' + id,
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            var html = '<table class="table table-bordered">';
            html += '<tr><th width="30%"><?php echo get_phrase("field"); ?></th><th><?php echo get_phrase("old_value"); ?></th><th><?php echo get_phrase("new_value"); ?></th></tr>';
            
            if(response.changes) {
                $.each(response.changes, function(field, values) {
                    html += '<tr>';
                    html += '<td><strong>' + field + '</strong></td>';
                    html += '<td>' + (values.old || '-') + '</td>';
                    html += '<td>' + (values.new || '-') + '</td>';
                    html += '</tr>';
                });
            }
            
            html += '</table>';
            $('#auditDetailsContent').html(html);
            $('.close').click();
            $('#auditDetailsModal').modal('show');
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
    });
}
</script>
