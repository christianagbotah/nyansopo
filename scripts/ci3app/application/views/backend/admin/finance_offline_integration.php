<div id="finance_network_status" style="position: fixed; top: 10px; right: 10px; padding: 10px 20px; color: white; border-radius: 25px; font-weight: 600; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
    <i class="fa fa-wifi"></i> Online
</div>

<script src="<?php echo base_url('assets/js/finance_offline.js'); ?>"></script>
<script>
// Daily Fee Collection - Offline Capable
async function collectDailyFeeOffline(studentId, amounts) {
    const record = {
        student_id: studentId,
        feeding_amount: amounts.feeding || 0,
        breakfast_amount: amounts.breakfast || 0,
        classes_amount: amounts.classes || 0,
        water_amount: amounts.water || 0,
        transport_amount: amounts.transport || 0,
        total_amount: (parseFloat(amounts.feeding || 0) + parseFloat(amounts.breakfast || 0) + 
                      parseFloat(amounts.classes || 0) + parseFloat(amounts.water || 0) + 
                      parseFloat(amounts.transport || 0)),
        payment_date: Math.floor(Date.now() / 1000),
        collected_by: <?php echo $this->session->userdata('login_user_id'); ?>,
        payment_method: amounts.payment_method || 1,
        collection_point: 'classroom'
    };
    
    if (navigator.onLine) {
        return saveToServer('admin/save_daily_fee', record);
    } else {
        await FinanceDB.save('daily_fees', record);
        showAjaxModal_alert('Saved offline. Will sync when online.', 'success', false);
        return { status: 'success' };
    }
}

// Payment Recording - Offline Capable
async function recordPaymentOffline(paymentData) {
    const record = {
        student_id: paymentData.student_id,
        invoice_id: paymentData.invoice_id,
        amount: paymentData.amount,
        payment_method: paymentData.payment_method,
        payment_date: new Date().toISOString(),
        received_by: <?php echo $this->session->userdata('login_user_id'); ?>
    };
    
    if (navigator.onLine) {
        return saveToServer('admin/create_payment', record);
    } else {
        await FinanceDB.save('payments', record);
        showAjaxModal_alert('Payment saved offline', 'success', false);
        return { status: 'success' };
    }
}

// Expense Recording - Offline Capable
async function recordExpenseOffline(expenseData) {
    const record = {
        expense_category_id: expenseData.category_id,
        title: expenseData.title,
        amount: expenseData.amount,
        description: expenseData.description,
        payment_method: expenseData.payment_method,
        payment_type: 'expense',
        timestamp: Math.floor(Date.now() / 1000),
        issuer_id: <?php echo $this->session->userdata('login_user_id'); ?>
    };
    
    if (navigator.onLine) {
        return saveToServer('admin/expense/create', record);
    } else {
        await FinanceDB.save('expenses', record);
        showAjaxModal_alert('Expense saved offline', 'success', false);
        return { status: 'success' };
    }
}

// Generic server save
function saveToServer(url, data) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '<?php echo site_url(""); ?>' + url,
            type: 'POST',
            data: data,
            dataType: 'json'
        }).done(function(response) {
            if (response.status === 'success') {
                showAjaxModal_alert(response.message, 'success');
                resolve(response);
            } else {
                showAjaxModal_alert(response.message, 'error');
                reject(response);
            }
        }).fail(async function() {
            // Network error - save offline
            const storeName = url.includes('daily_fee') ? 'daily_fees' : 
                            url.includes('payment') ? 'payments' : 'expenses';
            await FinanceDB.save(storeName, data);
            showAjaxModal_alert('Network error. Saved offline.', 'warning');
            resolve({ status: 'offline' });
        });
    });
}

// Manual sync trigger
async function triggerManualSync() {
    if (!navigator.onLine) {
        showAjaxModal_alert('No internet connection', 'error');
        return;
    }
    
    showAjaxModal_alert('Syncing offline records...', 'loading');
    const results = await FinanceDB.syncAll();
    showAjaxModal_alert('Sync completed', 'success', false);
}

// Show sync status
async function showOfflineStatus() {
    const stats = await FinanceDB.getStats();
    const total = Object.values(stats).reduce((a, b) => a + b, 0);
    
    let html = `
    <div style="padding: 20px;">
        <h4>Offline Records</h4>
        <table class="table">
            <tr><td>Admissions</td><td>${stats.admissions}</td></tr>
            <tr><td>Invoices</td><td>${stats.invoices}</td></tr>
            <tr><td>Payments</td><td>${stats.payments}</td></tr>
            <tr><td>Daily Fees</td><td>${stats.daily_fees}</td></tr>
            <tr><td>Expenses</td><td>${stats.expenses}</td></tr>
            <tr><th>Total</th><th>${total}</th></tr>
        </table>
        ${total > 0 ? '<button onclick="triggerManualSync()" class="btn btn-primary">Sync Now</button>' : ''}
    </div>`;
    
    showModalWithContent('detailsModal', 'Offline Status', html);
}
</script>
