<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fa fa-sync"></i> Daily Fee Accounting Sync Status</h4>
            </div>
            <div class="card-body">
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>Total Transactions</h5>
                                <h2><?php echo $total; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Synced</h5>
                                <h2><?php echo $synced; ?></h2>
                                <small><?php echo $total > 0 ? round(($synced/$total)*100, 1) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5>Unsynced</h5>
                                <h2><?php echo $unsynced; ?></h2>
                                <?php if($unsynced > 0): ?>
                                <button class="btn btn-sm btn-light mt-2" onclick="syncAll()">
                                    <i class="fa fa-sync"></i> Sync All Now
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Synced Transactions -->
                <h5>Recently Synced Transactions</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Transaction Code</th>
                                <th>Amount</th>
                                <th>Synced At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recent_synced)): ?>
                                <?php foreach($recent_synced as $trans): ?>
                                <tr>
                                    <td><?php echo $trans['id']; ?></td>
                                    <td><?php echo $trans['transaction_code']; ?></td>
                                    <td>GHS <?php echo number_format($trans['total_amount'], 2); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $trans['synced_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No synced transactions yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Unsynced Transactions -->
                <?php if($unsynced > 0): ?>
                <h5>Unsynced Transactions</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Transaction Code</th>
                                <th>Amount</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($unsynced_list as $trans): ?>
                            <tr id="trans-<?php echo $trans['id']; ?>">
                                <td><?php echo $trans['id']; ?></td>
                                <td><?php echo $trans['transaction_code']; ?></td>
                                <td>GHS <?php echo number_format($trans['total_amount'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $trans['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="syncOne(<?php echo $trans['id']; ?>)">
                                        <i class="fa fa-sync"></i> Sync
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function syncAll() {
    if(!confirm('Sync all unsynced transactions to accounting module?')) return;
    
    showAjaxModal_alert('Syncing transactions...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("sync_daily_fees/sync_all"); ?>',
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message || 'Sync failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred during sync', 'error');
    });
}

function syncOne(id) {
    showAjaxModal_alert('Syncing transaction...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("sync_daily_fees/sync_one/"); ?>' + id,
        type: 'GET',
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert('Transaction synced successfully', 'success');
            $('#trans-' + id).fadeOut();
        } else {
            showAjaxModal_alert(response.message || 'Sync failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred during sync', 'error');
    });
}
</script>
