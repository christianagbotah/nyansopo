<?php include APPPATH . 'views/backend/components/enterprise_ui_components.php'; ?>

<style>
.fiscal-year-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; border-left: 4px solid #667eea; }
.fiscal-year-card.active { border-left-color: #10b981; background: linear-gradient(to right, #f0fdf4 0%, white 100%); }
.fiscal-year-card.closed { border-left-color: #ef4444; opacity: 0.7; }
</style>

<?php render_page_header(
    get_phrase('fiscal_year_management'),
    get_phrase('manage_accounting_periods_and_year_end_closing'),
    '',
    '<button class="inline-flex items-center px-5 py-3 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 rounded-lg shadow-lg" onclick="showAddFiscalYearModal()"><i class="fa fa-plus mr-2"></i>' . get_phrase('create_fiscal_year') . '</button>'
); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="row">
        <?php if(isset($fiscal_years) && !empty($fiscal_years)): ?>
            <?php foreach($fiscal_years as $fy): ?>
            <div class="col-md-6 mb-3">
                <div class="fiscal-year-card <?php echo $fy['is_active'] == 1 ? 'active' : ($fy['is_closed'] == 1 ? 'closed' : ''); ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="mb-1"><?php echo $fy['year_name']; ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fa fa-calendar mr-1"></i>
                                <?php echo date('d M Y', strtotime($fy['start_date'])); ?> - 
                                <?php echo date('d M Y', strtotime($fy['end_date'])); ?>
                            </p>
                        </div>
                        <div>
                            <?php if($fy['is_active'] == 1): ?>
                                <span class="badge badge-success"><i class="fa fa-check mr-1"></i><?php echo get_phrase('active'); ?></span>
                            <?php elseif($fy['is_closed'] == 1): ?>
                                <span class="badge badge-danger"><i class="fa fa-lock mr-1"></i><?php echo get_phrase('closed'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo get_phrase('inactive'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($fy['is_active'] == 1 && $fy['is_closed'] == 0): ?>
                    <button class="btn btn-danger btn-sm" onclick="closeFiscalYear(<?php echo $fy['id']; ?>)">
                        <i class="fa fa-lock mr-1"></i><?php echo get_phrase('close_year'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fa fa-calendar-alt fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted"><?php echo get_phrase('no_fiscal_years_found'); ?></h5>
                    <button class="btn btn-primary mt-3" onclick="showAddFiscalYearModal()">
                        <i class="fa fa-plus mr-2"></i><?php echo get_phrase('create_fiscal_year'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
    <h5 class="text-xl font-bold text-gray-900 mb-4"><i class="fa fa-list mr-2"></i>All Fiscal Years</h5>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('fiscal_year'); ?></th>
                                <th><?php echo get_phrase('start_date'); ?></th>
                                <th><?php echo get_phrase('end_date'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($fiscal_years) && !empty($fiscal_years)): ?>
                                <?php foreach($fiscal_years as $fy): ?>
                                <tr>
                                    <td><?php echo $fy['year_name']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($fy['start_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($fy['end_date'])); ?></td>
                                    <td>
                                        <?php if($fy['is_active'] == 1): ?>
                                            <span class="badge badge-success"><?php echo get_phrase('active'); ?></span>
                                        <?php elseif($fy['is_closed'] == 1): ?>
                                            <span class="badge badge-danger"><?php echo get_phrase('closed'); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?php echo get_phrase('inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($fy['is_active'] == 1 && $fy['is_closed'] == 0): ?>
                                            <button class="btn btn-sm btn-danger" onclick="closeFiscalYear(<?php echo $fy['id']; ?>)">
                                                <i class="fa fa-lock"></i> <?php echo get_phrase('close_year'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo get_phrase('no_fiscal_years_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="addFiscalYearModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('create_fiscal_year'); ?></h4>
            </div>
            <form id="addFiscalYearForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo get_phrase('fiscal_year_name'); ?></label>
                        <input type="text" name="year_name" class="form-control" placeholder="e.g., 2024-2025" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('start_date'); ?></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo get_phrase('end_date'); ?></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1">
                            <?php echo get_phrase('set_as_active'); ?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo get_phrase('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#addFiscalYearForm').submit(function(e) {
        e.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('<?php echo get_phrase("creating"); ?>...', 'loading');
        
        $.ajax({
            url: '<?php echo site_url("accounts/fiscal_year/create"); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(response.message, 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
        });
    });
});

function showAddFiscalYearModal() {
    $('#addFiscalYearModal').modal('show');
}

function closeFiscalYear(id) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_close_fiscal_year"); ?>',
        '<?php echo get_phrase("close_fiscal_year_warning"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("closing_fiscal_year"); ?>...', 'loading');
            $.ajax({
                url: '<?php echo site_url("accounts/fiscal_year/close/"); ?>' + id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
            });
        },
        '<?php echo get_phrase("close_year"); ?>',
        'danger'
    );
}
</script>
