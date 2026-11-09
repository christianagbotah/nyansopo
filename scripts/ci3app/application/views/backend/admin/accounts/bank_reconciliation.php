<style>
@import url('<?php echo base_url(); ?>assets/cdn/fonts/inter.css');
* { font-family: 'Inter', sans-serif; }
.modern-container { max-width: 1600px; margin: 0 auto; padding: 24px; }
.page-header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(139, 92, 246, 0.2); }
.page-title { font-size: 32px; font-weight: 700; color: white; margin: 0 0 8px 0; letter-spacing: -0.5px; }
.page-subtitle { font-size: 16px; color: rgba(255,255,255,0.9); margin: 0; }
.reconciliation-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; margin-bottom: 20px; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.summary-box { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 20px; border-radius: 12px; border-left: 4px solid; }
.summary-box.book { border-left-color: #3b82f6; }
.summary-box.bank { border-left-color: #10b981; }
.summary-box.difference { border-left-color: #ef4444; }
.summary-box.reconciled { border-left-color: #8b5cf6; }
.summary-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
.summary-value { font-size: 28px; font-weight: 700; color: #111827; }
.transaction-item { background: #f9fafb; padding: 16px; border-radius: 12px; margin-bottom: 12px; border-left: 4px solid #e5e7eb; transition: all 0.2s; cursor: pointer; }
.transaction-item:hover { background: #f3f4f6; border-left-color: #3b82f6; }
.transaction-item.selected { background: #eff6ff; border-left-color: #3b82f6; }
.transaction-item.reconciled { background: #f0fdf4; border-left-color: #10b981; opacity: 0.7; }
.transaction-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.transaction-date { font-size: 13px; color: #6b7280; font-weight: 600; }
.transaction-amount { font-size: 18px; font-weight: 700; }
.transaction-amount.debit { color: #ef4444; }
.transaction-amount.credit { color: #10b981; }
.transaction-description { font-size: 14px; color: #374151; }
.btn-modern { padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.reconciliation-actions { position: sticky; bottom: 20px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 -4px 20px rgba(0,0,0,0.1); }
</style>

<div class="modern-container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">🏦 Bank Reconciliation</h1>
                <p class="page-subtitle">Match bank statements with book records for accurate financial reporting</p>
            </div>
            <button onclick="showNewReconciliationModal()" class="btn-modern btn-primary">
                <i class="fa fa-plus"></i> New Reconciliation
            </button>
        </div>
    </div>

    <div class="reconciliation-card">
        <div class="row mb-4">
            <div class="col-md-4">
                <label>Bank Account</label>
                <select id="bankAccountSelect" class="form-control">
                    <option value="">Select Bank Account</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Statement Date</label>
                <input type="date" id="statementDate" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Statement Balance</label>
                <input type="number" id="statementBalance" class="form-control" step="0.01" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button onclick="loadTransactions()" class="btn btn-primary btn-block">
                    <i class="fa fa-search"></i> Load
                </button>
            </div>
        </div>

        <div id="reconciliationArea" style="display:none;">
            <div class="summary-grid">
                <div class="summary-box book">
                    <div class="summary-label">📚 Book Balance</div>
                    <div class="summary-value" id="bookBalance">GHS 0.00</div>
                </div>
                <div class="summary-box bank">
                    <div class="summary-label">🏦 Bank Balance</div>
                    <div class="summary-value" id="bankBalance">GHS 0.00</div>
                </div>
                <div class="summary-box difference">
                    <div class="summary-label">⚠️ Difference</div>
                    <div class="summary-value" id="difference">GHS 0.00</div>
                </div>
                <div class="summary-box reconciled">
                    <div class="summary-label">✅ Reconciled</div>
                    <div class="summary-value" id="reconciledAmount">GHS 0.00</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="reconciliation-card">
                        <h5 class="mb-3">
                            <i class="fa fa-book"></i> Book Transactions
                            <button onclick="selectAllBook()" class="btn btn-sm btn-primary float-right">
                                <i class="fa fa-check-double"></i> Select All
                            </button>
                        </h5>
                        <div id="bookTransactions"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="reconciliation-card">
                        <h5 class="mb-3">
                            <i class="fa fa-university"></i> Bank Transactions
                            <button onclick="selectAllBank()" class="btn btn-sm btn-success float-right">
                                <i class="fa fa-check-double"></i> Select All
                            </button>
                        </h5>
                        <div id="bankTransactions"></div>
                    </div>
                </div>
            </div>

            <div class="reconciliation-actions">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Selected Items:</strong> <span id="selectedCount">0</span> |
                        <strong>Selected Amount:</strong> <span id="selectedAmount">GHS 0.00</span>
                    </div>
                    <div>
                        <button onclick="clearSelection()" class="btn-modern btn-warning">
                            <i class="fa fa-times"></i> Clear Selection
                        </button>
                        <button onclick="saveReconciliation()" class="btn-modern btn-success">
                            <i class="fa fa-save"></i> Save Reconciliation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="reconciliation-card">
        <h5 class="mb-3">📋 Recent Reconciliations</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bank Account</th>
                    <th>Statement Balance</th>
                    <th>Book Balance</th>
                    <th>Difference</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="recentReconciliations"></tbody>
        </table>
    </div>
</div>

<script>
let selectedTransactions = [];

$(document).ready(function() {
    loadBankAccounts();
    loadRecentReconciliations();
});

function loadBankAccounts() {
    $.ajax({
        url: '<?php echo site_url("admin/bank_reconciliation/get_accounts"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            let options = '<option value="">Select Bank Account</option>';
            response.accounts.forEach(function(acc) {
                options += `<option value="${acc.id}">${acc.bank_name} - ${acc.account_number}</option>`;
            });
            $('#bankAccountSelect').html(options);
        }
    });
}

function loadTransactions() {
    let accountId = $('#bankAccountSelect').val();
    let statementDate = $('#statementDate').val();
    let statementBalance = $('#statementBalance').val();

    if(!accountId || !statementDate || !statementBalance) {
        showAjaxModal_alert('Please fill all fields', 'error');
        return;
    }

    showAjaxModal_alert('Loading transactions...', 'loading');

    $.ajax({
        url: '<?php echo site_url("admin/bank_reconciliation/get_transactions"); ?>',
        type: 'POST',
        data: {
            account_id: accountId,
            statement_date: statementDate,
            statement_balance: statementBalance
        },
        dataType: 'json'
    }).done(function(response) {
        $('.close')[0].click();
        if(response.status === 'success') {
            $('#reconciliationArea').show();
            $('#bookBalance').text('GHS ' + parseFloat(response.book_balance).toFixed(2));
            $('#bankBalance').text('GHS ' + parseFloat(statementBalance).toFixed(2));
            updateDifference();
            renderTransactions(response.book_transactions, response.bank_transactions);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function renderTransactions(bookTxns, bankTxns) {
    let bookHtml = '';
    bookTxns.forEach(function(txn) {
        let amountClass = txn.type === 'debit' ? 'debit' : 'credit';
        let reconciledClass = txn.reconciled ? 'reconciled' : '';
        bookHtml += `
            <div class="transaction-item ${reconciledClass}" data-id="${txn.id}" data-amount="${txn.amount}" data-type="book" onclick="toggleTransaction(this)">
                <div class="transaction-header">
                    <span class="transaction-date">${txn.date}</span>
                    <span class="transaction-amount ${amountClass}">
                        ${txn.type === 'debit' ? '-' : '+'}GHS ${parseFloat(txn.amount).toFixed(2)}
                    </span>
                </div>
                <div class="transaction-description">${txn.description}</div>
                ${txn.reconciled ? '<small class="text-success"><i class="fa fa-check"></i> Already Reconciled</small>' : ''}
            </div>
        `;
    });
    $('#bookTransactions').html(bookHtml || '<p class="text-muted">No transactions found</p>');

    let bankHtml = '';
    bankTxns.forEach(function(txn) {
        let amountClass = txn.type === 'debit' ? 'debit' : 'credit';
        let reconciledClass = txn.reconciled ? 'reconciled' : '';
        bankHtml += `
            <div class="transaction-item ${reconciledClass}" data-id="${txn.id}" data-amount="${txn.amount}" data-type="bank" onclick="toggleTransaction(this)">
                <div class="transaction-header">
                    <span class="transaction-date">${txn.date}</span>
                    <span class="transaction-amount ${amountClass}">
                        ${txn.type === 'debit' ? '-' : '+'}GHS ${parseFloat(txn.amount).toFixed(2)}
                    </span>
                </div>
                <div class="transaction-description">${txn.description}</div>
                ${txn.reconciled ? '<small class="text-success"><i class="fa fa-check"></i> Already Reconciled</small>' : ''}
            </div>
        `;
    });
    $('#bankTransactions').html(bankHtml || '<p class="text-muted">No transactions found</p>');
}

function toggleTransaction(element) {
    if($(element).hasClass('reconciled')) return;
    
    $(element).toggleClass('selected');
    
    let id = $(element).data('id');
    let amount = parseFloat($(element).data('amount'));
    let type = $(element).data('type');
    
    let index = selectedTransactions.findIndex(t => t.id === id && t.type === type);
    if(index > -1) {
        selectedTransactions.splice(index, 1);
    } else {
        selectedTransactions.push({id, amount, type});
    }
    
    updateSelection();
}

function updateSelection() {
    let count = selectedTransactions.length;
    let total = selectedTransactions.reduce((sum, t) => sum + t.amount, 0);
    
    $('#selectedCount').text(count);
    $('#selectedAmount').text('GHS ' + total.toFixed(2));
    $('#reconciledAmount').text('GHS ' + total.toFixed(2));
    
    updateDifference();
}

function updateDifference() {
    let bookBalance = parseFloat($('#bookBalance').text().replace('GHS ', ''));
    let bankBalance = parseFloat($('#bankBalance').text().replace('GHS ', ''));
    let reconciledAmount = parseFloat($('#reconciledAmount').text().replace('GHS ', ''));
    
    let difference = Math.abs(bankBalance - (bookBalance - reconciledAmount));
    $('#difference').text('GHS ' + difference.toFixed(2));
}

function selectAllBook() {
    $('#bookTransactions .transaction-item:not(.reconciled)').each(function() {
        if(!$(this).hasClass('selected')) {
            $(this).click();
        }
    });
}

function selectAllBank() {
    $('#bankTransactions .transaction-item:not(.reconciled)').each(function() {
        if(!$(this).hasClass('selected')) {
            $(this).click();
        }
    });
}

function clearSelection() {
    $('.transaction-item.selected').removeClass('selected');
    selectedTransactions = [];
    updateSelection();
}

function saveReconciliation() {
    if(selectedTransactions.length === 0) {
        showAjaxModal_alert('Please select transactions to reconcile', 'error');
        return;
    }

    showConfirmModal(
        'Save Reconciliation',
        'Are you sure you want to save this reconciliation?',
        function() {
            showAjaxModal_alert('Saving...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/bank_reconciliation/save"); ?>',
                type: 'POST',
                data: {
                    account_id: $('#bankAccountSelect').val(),
                    statement_date: $('#statementDate').val(),
                    statement_balance: $('#statementBalance').val(),
                    transactions: JSON.stringify(selectedTransactions)
                },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Save',
        'success'
    );
}

function loadRecentReconciliations() {
    $.ajax({
        url: '<?php echo site_url("admin/bank_reconciliation/get_recent"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            let html = '';
            response.reconciliations.forEach(function(rec) {
                let statusBadge = rec.status === 'completed' ? 
                    '<span class="badge badge-success">Completed</span>' : 
                    '<span class="badge badge-warning">Pending</span>';
                html += `
                    <tr>
                        <td>${rec.date}</td>
                        <td>${rec.bank_account}</td>
                        <td>GHS ${parseFloat(rec.statement_balance).toFixed(2)}</td>
                        <td>GHS ${parseFloat(rec.book_balance).toFixed(2)}</td>
                        <td>GHS ${parseFloat(rec.difference).toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button onclick="viewReconciliation(${rec.id})" class="btn btn-sm btn-info">
                                <i class="fa fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#recentReconciliations').html(html || '<tr><td colspan="7" class="text-center">No reconciliations found</td></tr>');
        }
    });
}
</script>
