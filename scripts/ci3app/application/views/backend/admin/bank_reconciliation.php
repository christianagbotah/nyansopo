<div class="row">
    <div class="col-md-12">
        
        <!-- Statistics Cards -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-3">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="entypo-database"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalBalance">GHS 0</div>
                        <div class="stat-label"><?php echo get_phrase('total_balance'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="entypo-docs"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalReconciliations">0</div>
                        <div class="stat-label"><?php echo get_phrase('reconciliations'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="entypo-clock"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="pendingCount">0</div>
                        <div class="stat-label"><?php echo get_phrase('pending'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="entypo-check"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="completedCount">0</div>
                        <div class="stat-label"><?php echo get_phrase('completed'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Panel -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="entypo-database"></i> <?php echo get_phrase('bank_reconciliation'); ?></span>
                    <button class="btn btn-success btn-sm" onclick="showCreateReconciliationModal()">
                        <i class="entypo-plus"></i> <?php echo get_phrase('new_reconciliation'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-body" style="padding: 20px;">
                
                <!-- Filters -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-4">
                        <select id="filterBankAccount" class="form-control" onchange="loadReconciliations()">
                            <option value=""><?php echo get_phrase('all_accounts'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="filterStatus" class="form-control" onchange="filterReconciliations()">
                            <option value=""><?php echo get_phrase('all_status'); ?></option>
                            <option value="pending"><?php echo get_phrase('pending'); ?></option>
                            <option value="completed"><?php echo get_phrase('completed'); ?></option>
                            <option value="reviewed"><?php echo get_phrase('reviewed'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="searchReconciliation" class="form-control" placeholder="<?php echo get_phrase('search'); ?>..." onkeyup="filterReconciliations()">
                    </div>
                </div>

                <!-- Reconciliations Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('date'); ?></th>
                                <th><?php echo get_phrase('bank_account'); ?></th>
                                <th><?php echo get_phrase('statement_balance'); ?></th>
                                <th><?php echo get_phrase('book_balance'); ?></th>
                                <th><?php echo get_phrase('difference'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="reconciliationsTableBody"></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Reconciliation Modal -->
<div class="modal fade" id="reconciliationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="reconciliationModalTitle"><?php echo get_phrase('new_reconciliation'); ?></h4>
            </div>
            <form id="reconciliationForm" onsubmit="saveReconciliation(event)">
                <div class="modal-body">
                    <input type="hidden" id="reconciliation_id" name="reconciliation_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('bank_account'); ?> *</label>
                                <select class="form-control" name="bank_account_id" required id="bankAccountSelect"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('reconciliation_date'); ?> *</label>
                                <input type="date" class="form-control" name="reconciliation_date" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('statement_date'); ?> *</label>
                                <input type="date" class="form-control" name="statement_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('statement_balance'); ?> *</label>
                                <input type="number" step="0.01" class="form-control" name="statement_balance" required id="statementBalance" onchange="calculateDifference()">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('book_balance'); ?> *</label>
                                <input type="number" step="0.01" class="form-control" name="book_balance" required id="bookBalance" onchange="calculateDifference()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('difference'); ?></label>
                                <input type="text" class="form-control" id="differenceDisplay" readonly style="font-weight: bold; color: #d32f2f;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('notes'); ?></label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
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

<!-- Reconciliation Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" style="width: 95%;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title" id="detailsTitle"></h4>
            </div>
            <div class="modal-body" id="detailsContent" style="padding: 30px;"></div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
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
    margin-right: 15px;
}

.stat-primary .stat-icon { background: #e3f2fd; color: #1976d2; }
.stat-info .stat-icon { background: #e1f5fe; color: #0288d1; }
.stat-warning .stat-icon { background: #fff3e0; color: #f57c00; }
.stat-success .stat-icon { background: #e8f5e9; color: #388e3c; }

.stat-content { flex: 1; }

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
}

.recon-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3e0; color: #f57c00; }
.status-completed { background: #e8f5e9; color: #388e3c; }
.status-reviewed { background: #e3f2fd; color: #1976d2; }

.difference-positive { color: #388e3c; }
.difference-negative { color: #d32f2f; }

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.item-matched {
    border-left-color: #4caf50;
    background: #f1f8f4;
}

.item-unmatched {
    border-left-color: #f44336;
    background: #fff5f5;
}
</style>

<script>
let reconciliationsData = [];
let bankAccountsData = [];
let currentReconciliationId = null;

$(document).ready(function() {
    loadBankAccounts();
    loadReconciliations();
    loadStatistics();
});

function loadBankAccounts() {
    $.ajax({
        url: '<?php echo base_url(); ?>bank_reconciliation/get_bank_accounts',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                bankAccountsData = response.data;
                
                let options = '<option value=""><?php echo get_phrase('select_account'); ?></option>';
                response.data.forEach(acc => {
                    options += `<option value="${acc.bank_account_id}">${acc.account_name} - ${acc.bank_name}</option>`;
                });
                
                $('#filterBankAccount').html('<option value=""><?php echo get_phrase('all_accounts'); ?></option>' + options.replace('<?php echo get_phrase('select_account'); ?>', ''));
                $('#bankAccountSelect').html(options);
            }
        }
    });
}

function loadReconciliations() {
    const bankAccountId = $('#filterBankAccount').val();
    
    $.ajax({
        url: '<?php echo base_url(); ?>bank_reconciliation/get_reconciliations',
        type: 'GET',
        data: { bank_account_id: bankAccountId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                reconciliationsData = response.data;
                renderReconciliations(reconciliationsData);
            }
        }
    });
}

function renderReconciliations(reconciliations) {
    const tbody = $('#reconciliationsTableBody');
    tbody.empty();
    
    if (reconciliations.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center text-muted"><?php echo get_phrase('no_reconciliations_found'); ?></td></tr>');
        return;
    }
    
    reconciliations.forEach(recon => {
        const difference = parseFloat(recon.difference);
        const diffClass = difference >= 0 ? 'difference-positive' : 'difference-negative';
        
        const row = `
            <tr>
                <td>${recon.reconciliation_date}</td>
                <td>${recon.account_name}<br><small class="text-muted">${recon.bank_name}</small></td>
                <td><strong>GHS ${parseFloat(recon.statement_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><strong>GHS ${parseFloat(recon.book_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td class="${diffClass}"><strong>GHS ${Math.abs(difference).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><span class="recon-status status-${recon.status}">${recon.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewDetails(${recon.id})" title="<?php echo get_phrase('view_details'); ?>">
                        <i class="entypo-eye"></i>
                    </button>
                    ${recon.status === 'pending' ? `
                        <button class="btn btn-sm btn-success" onclick="completeReconciliation(${recon.id})" title="<?php echo get_phrase('complete'); ?>">
                            <i class="entypo-check"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function filterReconciliations() {
    const search = $('#searchReconciliation').val().toLowerCase();
    const status = $('#filterStatus').val();
    
    const filtered = reconciliationsData.filter(r => {
        const matchSearch = r.account_name.toLowerCase().includes(search) || r.bank_name.toLowerCase().includes(search);
        const matchStatus = !status || r.status === status;
        return matchSearch && matchStatus;
    });
    
    renderReconciliations(filtered);
}

function loadStatistics() {
    $.ajax({
        url: '<?php echo base_url(); ?>bank_reconciliation/get_statistics',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const stats = response.data;
                $('#totalBalance').text('GHS ' + parseFloat(stats.total_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#totalReconciliations').text(stats.total_reconciliations);
                $('#pendingCount').text(stats.pending_count);
                $('#completedCount').text(stats.completed_count);
            }
        }
    });
}

function showCreateReconciliationModal() {
    $('#reconciliationForm')[0].reset();
    $('#reconciliation_id').val('');
    $('#differenceDisplay').val('');
    $('#reconciliationModalTitle').text('<?php echo get_phrase('new_reconciliation'); ?>');
    $('#reconciliationModal').modal('show');
}

function calculateDifference() {
    const statement = parseFloat($('#statementBalance').val()) || 0;
    const book = parseFloat($('#bookBalance').val()) || 0;
    const diff = statement - book;
    $('#differenceDisplay').val('GHS ' + Math.abs(diff).toLocaleString('en-US', {minimumFractionDigits: 2}));
}

function saveReconciliation(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    const reconciliationId = $('#reconciliation_id').val();
    const url = reconciliationId ? 
        '<?php echo base_url(); ?>bank_reconciliation/update/' + reconciliationId :
        '<?php echo base_url(); ?>bank_reconciliation/create';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: $('#reconciliationForm').serialize(),
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => {
                    loadReconciliations();
                    loadStatistics();
                    if (response.reconciliation_id) {
                        viewDetails(response.reconciliation_id);
                    }
                }, 2000);
            }
        }
    });
}

function viewDetails(reconciliationId) {
    currentReconciliationId = reconciliationId;
    showAjaxModal_alert('<?php echo get_phrase('loading'); ?>...', 'loading');
    
    const recon = reconciliationsData.find(r => r.id == reconciliationId);
    if (!recon) return;
    
    $('.close')[0].click();
    
    $('#detailsTitle').html(`<i class="entypo-database"></i> ${recon.account_name} - ${recon.reconciliation_date}`);
    
    let html = `
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;"><?php echo get_phrase('statement_balance'); ?></div>
                    <div style="font-size: 28px; font-weight: 700;">GHS ${parseFloat(recon.statement_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="col-md-3 text-center">
                    <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;"><?php echo get_phrase('book_balance'); ?></div>
                    <div style="font-size: 28px; font-weight: 700;">GHS ${parseFloat(recon.book_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="col-md-3 text-center">
                    <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;"><?php echo get_phrase('difference'); ?></div>
                    <div style="font-size: 28px; font-weight: 700;">GHS ${Math.abs(parseFloat(recon.difference)).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="col-md-3 text-center">
                    <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;"><?php echo get_phrase('status'); ?></div>
                    <div style="font-size: 20px; font-weight: 700; text-transform: uppercase;">${recon.status}</div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4><?php echo get_phrase('reconciliation_items'); ?></h4>
            <div>
                <button class="btn btn-warning btn-sm" onclick="autoMatch(${reconciliationId})">
                    <i class="entypo-flash"></i> <?php echo get_phrase('auto_match'); ?>
                </button>
                ${recon.status === 'pending' ? `
                    <button class="btn btn-success btn-sm" onclick="completeReconciliation(${reconciliationId})">
                        <i class="entypo-check"></i> <?php echo get_phrase('complete'); ?>
                    </button>
                ` : ''}
            </div>
        </div>
        
        <div id="itemsContainer"></div>
    `;
    
    $('#detailsContent').html(html);
    $('#detailsModal').modal('show');
    
    loadReconciliationItems(reconciliationId);
}

function loadReconciliationItems(reconciliationId) {
    $.ajax({
        url: '<?php echo base_url(); ?>bank_reconciliation/get_reconciliation_items/' + reconciliationId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                renderItems(response.data);
            }
        }
    });
}

function renderItems(items) {
    const container = $('#itemsContainer');
    container.empty();
    
    if (items.length === 0) {
        container.html('<p class="text-center text-muted"><?php echo get_phrase('no_items_found'); ?></p>');
        return;
    }
    
    items.forEach(item => {
        const itemClass = item.matched == 1 ? 'item-matched' : 'item-unmatched';
        const html = `
            <div class="item-row ${itemClass}">
                <div style="flex: 1;">
                    <div style="font-weight: 600;">${item.description}</div>
                    <small class="text-muted">${item.transaction_date} | ${item.transaction_type}</small>
                </div>
                <div style="text-align: right; margin-right: 20px;">
                    <div style="font-size: 18px; font-weight: 700;">GHS ${parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div>
                    <button class="btn btn-sm ${item.matched == 1 ? 'btn-warning' : 'btn-success'}" onclick="toggleMatch(${item.id})">
                        <i class="entypo-${item.matched == 1 ? 'cancel' : 'check'}"></i>
                    </button>
                </div>
            </div>
        `;
        container.append(html);
    });
}

function toggleMatch(itemId) {
    $.ajax({
        url: '<?php echo base_url(); ?>bank_reconciliation/toggle_match/' + itemId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                loadReconciliationItems(currentReconciliationId);
            }
        }
    });
}

function autoMatch(reconciliationId) {
    showAjaxModal_alert('<?php echo get_phrase('matching'); ?>...', 'loading');
    
    $.ajax({
        url: '<?php echo base_url(); ?>bank_reconciliation/auto_match/' + reconciliationId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            showAjaxModal_alert(response.message, response.status);
            if (response.status === 'success') {
                setTimeout(() => loadReconciliationItems(reconciliationId), 2000);
            }
        }
    });
}

function completeReconciliation(reconciliationId) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_completion'); ?>',
        '<?php echo get_phrase('are_you_sure'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('completing'); ?>...', 'loading');
            $.ajax({
                url: '<?php echo base_url(); ?>bank_reconciliation/complete/' + reconciliationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    showAjaxModal_alert(response.message, response.status);
                    if (response.status === 'success') {
                        setTimeout(() => {
                            loadReconciliations();
                            loadStatistics();
                            $('.close')[0].click();
                        }, 2000);
                    }
                }
            });
        },
        '<?php echo get_phrase('complete'); ?>',
        'success'
    );
}
</script>
