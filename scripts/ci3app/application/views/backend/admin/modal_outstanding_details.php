<div class="modal-header bg-danger text-white">
    <h4><i class="fa fa-exclamation-triangle"></i> <?php echo $fee_type; ?> Fee Outstanding</h4>
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
                    <th>Amount Owed</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($outstanding)): ?>
                <tr><td colspan="4" class="text-center py-4">No outstanding fees</td></tr>
                <?php else: foreach($outstanding as $o): ?>
                <tr>
                    <td><?php echo $o['student_code']; ?></td>
                    <td><?php echo $o['student_name']; ?></td>
                    <td><?php echo $o['class_name'] . ' ' . $o['name_numeric']; ?></td>
                    <td><strong class="text-danger"><?php echo $currency . number_format($o['amount_owed'], 2); ?></strong></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-default" data-dismiss="modal">Close</button>
    <button class="btn btn-success" onclick="export_outstanding_excel('<?php echo strtolower($fee_type); ?>')">
        <i class="fa fa-file-excel"></i> Export
    </button>
</div>
