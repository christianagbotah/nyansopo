<?php $currency = get_settings('currency'); ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0 font-size-18"><?php echo get_phrase('student_account_statement'); ?></h4>
            <div class="btn-group">
                <button class="btn btn-primary" onclick="printStatement()">
                    <i class="mdi mdi-printer"></i> <?php echo get_phrase('print'); ?>
                </button>
                <button class="btn btn-info" onclick="downloadPDF()">
                    <i class="mdi mdi-download"></i> <?php echo get_phrase('download_pdf'); ?>
                </button>
                <button class="btn btn-success" onclick="emailStatement()">
                    <i class="mdi mdi-email"></i> <?php echo get_phrase('email'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row" id="statement-content">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                $student = $this->db->where('student_id', $student_id)->get('student')->row_array();
                $enroll = $this->db->where('student_id', $student_id)->get('enroll')->row_array();
                $class = $this->db->where('class_id', $enroll['class_id'])->get('class')->row_array();
                ?>
                
                <!-- Student Info Header -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h3><?php echo $student['name']; ?></h3>
                        <p class="mb-1"><strong><?php echo get_phrase('student_id'); ?>:</strong> <?php echo $student['student_code']; ?></p>
                        <p class="mb-1"><strong><?php echo get_phrase('class'); ?>:</strong> <?php echo $class['name']; ?></p>
                        <p class="mb-1"><strong><?php echo get_phrase('phone'); ?>:</strong> <?php echo $student['phone']; ?></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <p><strong><?php echo get_phrase('statement_date'); ?>:</strong> <?php echo date('d M Y'); ?></p>
                        <p><strong><?php echo get_phrase('academic_year'); ?>:</strong> <?php echo get_settings('running_year'); ?></p>
                    </div>
                </div>

                <hr>

                <!-- Financial Summary Cards -->
                <div class="row mb-4">
                    <?php
                    $year = get_settings('running_year');
                    $term = get_settings('running_term');
                    
                    $summary = $this->db->select('SUM(amount) as total_billed, SUM(amount_paid) as total_paid, SUM(due) as total_due')
                        ->where('student_id', $student_id)
                        ->where('year', $year)
                        ->where('term', $term)
                        ->get('invoice')->row_array();
                    ?>
                    
                    <div class="col-md-4">
                        <div class="card bg-primary text-white mb-0">
                            <div class="card-body">
                                <h6 class="text-white-50"><?php echo get_phrase('total_billed'); ?></h6>
                                <h3 class="mb-0"><?php echo $currency . number_format($summary['total_billed'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white mb-0">
                            <div class="card-body">
                                <h6 class="text-white-50"><?php echo get_phrase('total_paid'); ?></h6>
                                <h3 class="mb-0"><?php echo $currency . number_format($summary['total_paid'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white mb-0">
                            <div class="card-body">
                                <h6 class="text-white-50"><?php echo get_phrase('outstanding'); ?></h6>
                                <h3 class="mb-0"><?php echo $currency . number_format($summary['total_due'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoices Table -->
                <h5 class="mb-3"><?php echo get_phrase('invoice_details'); ?></h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th><?php echo get_phrase('invoice_code'); ?></th>
                                <th><?php echo get_phrase('description'); ?></th>
                                <th class="text-right"><?php echo get_phrase('amount'); ?></th>
                                <th class="text-right"><?php echo get_phrase('paid'); ?></th>
                                <th class="text-right"><?php echo get_phrase('balance'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $invoices = $this->db->where('student_id', $student_id)
                                ->where('year', $year)
                                ->where('term', $term)
                                ->where('can_delete !=', 'trash')
                                ->order_by('creation_timestamp', 'DESC')
                                ->get('invoice')->result_array();
                            
                            foreach ($invoices as $invoice):
                            ?>
                            <tr>
                                <td><strong><?php echo $invoice['invoice_code']; ?></strong></td>
                                <td><?php echo $invoice['title']; ?></td>
                                <td class="text-right"><?php echo $currency . number_format($invoice['amount'], 2); ?></td>
                                <td class="text-right text-success"><?php echo $currency . number_format($invoice['amount_paid'], 2); ?></td>
                                <td class="text-right text-danger"><strong><?php echo $currency . number_format($invoice['due'], 2); ?></strong></td>
                                <td>
                                    <?php if ($invoice['status'] == 'paid'): ?>
                                        <span class="badge badge-success"><?php echo get_phrase('paid'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?php echo get_phrase('pending'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="font-weight-bold">
                            <tr>
                                <td colspan="2" class="text-right"><?php echo get_phrase('total'); ?>:</td>
                                <td class="text-right"><?php echo $currency . number_format($summary['total_billed'], 2); ?></td>
                                <td class="text-right text-success"><?php echo $currency . number_format($summary['total_paid'], 2); ?></td>
                                <td class="text-right text-danger"><?php echo $currency . number_format($summary['total_due'], 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment History -->
                <h5 class="mb-3 mt-4"><?php echo get_phrase('payment_history'); ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('date'); ?></th>
                                <th><?php echo get_phrase('invoice'); ?></th>
                                <th><?php echo get_phrase('method'); ?></th>
                                <th class="text-right"><?php echo get_phrase('amount'); ?></th>
                                <th><?php echo get_phrase('receipt'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $payments = $this->db->select('p.*, r.receipt_number')
                                ->from('payment p')
                                ->join('receipts r', 'r.receipt_id = p.receipt_id', 'left')
                                ->where('p.student_id', $student_id)
                                ->where('p.year', $year)
                                ->where('p.term', $term)
                                ->where('p.can_delete !=', 'trash')
                                ->order_by('p.day_timestamp', 'DESC')
                                ->get()->result_array();
                            
                            foreach ($payments as $payment):
                            ?>
                            <tr>
                                <td><?php echo date('d M Y', $payment['day_timestamp']); ?></td>
                                <td><?php echo $payment['invoice_code']; ?></td>
                                <td><span class="badge badge-info"><?php echo strtoupper(str_replace('_', ' ', $payment['payment_method'])); ?></span></td>
                                <td class="text-right text-success"><strong><?php echo $currency . number_format($payment['amount'], 2); ?></strong></td>
                                <td><?php echo $payment['receipt_number'] ?: '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Note -->
                <div class="alert alert-info mt-4">
                    <i class="mdi mdi-information"></i> 
                    <?php echo get_phrase('statement_footer_note'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printStatement() {
    const content = document.getElementById('statement-content').innerHTML;
    const printWindow = window.open('', '', 'height=800,width=1000');
    printWindow.document.write(`
        <html>
        <head>
            <title>Student Statement</title>
            <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>">
            <style>
                body { padding: 20px; }
                @media print {
                    .btn-group { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="container-fluid">${content}</div>
            <script>window.print();</script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function downloadPDF() {
    window.open('<?php echo site_url('finance/download_statement/' . $student_id . '/pdf'); ?>', '_blank');
}

function emailStatement() {
    showConfirmModal(
        '<?php echo get_phrase('confirm_email'); ?>',
        '<?php echo get_phrase('send_statement_to_parent_email'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('sending'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/email_statement/' . $student_id); ?>', function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
            });
        },
        '<?php echo get_phrase('send'); ?>',
        'primary'
    );
}
</script>

<style>
@media print {
    .page-title-box, .btn-group { display: none !important; }
    .card { border: none; box-shadow: none; }
}
</style>
