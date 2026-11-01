<div class="modal-header bg-info text-white">
    <h4><i class="fa fa-coins"></i> <?php echo $fee_type; ?> Fee Payables (Prepaid)</h4>
</div>
<div class="modal-body p-0">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Code</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Prepaid Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($payables)): ?>
                <tr><td colspan="4" class="text-center py-4">No prepaid balances</td></tr>
                <?php else: foreach($payables as $p): ?>
                <tr>
                    <td><?php echo $p['student_code']; ?></td>
                    <td><?php echo $p['student_name']; ?></td>
                    <td><?php echo $p['class_name'] . ' ' . $p['name_numeric']; ?></td>
                    <td><strong class="text-success"><?php echo $currency . number_format($p['prepaid_balance'], 2); ?></strong></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-default" data-dismiss="modal">Close</button>
    <button class="btn btn-success" onclick="export_payables_excel('<?php echo strtolower($fee_type); ?>')">
        <i class="fa fa-file-excel"></i> Export
    </button>
</div>
