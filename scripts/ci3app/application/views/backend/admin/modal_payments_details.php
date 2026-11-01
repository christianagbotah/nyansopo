<div class="modal-header bg-primary text-white">
    <h4><i class="fa fa-money-bill"></i> <?php echo $fee_type; ?> Fee Payments</h4>
    <p class="mb-0"><?php echo $date ?? $period; ?></p>
</div>
<div class="modal-body p-0">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Code</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($payments)): ?>
                <tr><td colspan="5" class="text-center py-4">No payments found</td></tr>
                <?php else: foreach($payments as $p): ?>
                <tr>
                    <td><?php echo $p['student_code']; ?></td>
                    <td><?php echo $p['student_name']; ?></td>
                    <td><?php echo $p['class_name'] . ' ' . $p['name_numeric']; ?></td>
                    <td><strong><?php echo $currency . number_format($p['amount'] ?? $p['total_amount'], 2); ?></strong></td>
                    <td><?php echo isset($p['payment_date']) ? date('d-m-Y', $p['payment_date']) : '-'; ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-default" data-dismiss="modal">Close</button>
    <button class="btn btn-success" onclick="export_payments_excel('<?php echo strtolower($fee_type); ?>')">
        <i class="fa fa-file-excel"></i> Export
    </button>
</div>
