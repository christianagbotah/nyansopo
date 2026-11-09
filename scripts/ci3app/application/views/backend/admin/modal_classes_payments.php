<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get year and term from URL parameters
$term = $this->uri->segment(4);
$year = $this->uri->segment(5);

// Get classes fee payments from daily_fee_transactions
$this->db->select('dft.*, s.name as student_name, c.name as class_name, c.name_numeric');
$this->db->from('daily_fee_transactions dft');
$this->db->join('student s', 's.student_id = dft.student_id');
$this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = dft.year AND e.term = dft.term');
$this->db->join('class c', 'c.class_id = e.class_id');
$this->db->where('dft.year', $year);
$this->db->where('dft.term', $term);
$this->db->where('dft.classes_amount >', 0);
$this->db->order_by('dft.created_at', 'DESC');
$this->db->order_by('s.name', 'ASC');
$payments = $this->db->get()->result_array();

$total_amount = array_sum(array_column($payments, 'classes_amount'));
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-chalkboard-teacher"></i> Classes Fee Payments - Year: <?php echo explode('-', $year)[1]; ?> | Term: <?php echo $term; ?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="alert alert-info">
                    <strong>Total Amount Collected:</strong> <?php echo $currency . number_format($total_amount, 2); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-success">
                    <strong>Total Transactions:</strong> <?php echo count($payments); ?>
                </div>
            </div>
        </div>

        <?php if(count($payments) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="classes_payments_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Amount Paid</th>
                        <th>Receipt Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach($payments as $payment): 
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></td>
                        <td><?php echo $payment['student_name']; ?></td>
                        <td><?php echo $payment['class_name'] . ' ' . $payment['name_numeric']; ?></td>
                        <td><?php echo $currency . number_format($payment['classes_amount'], 2); ?></td>
                        <td><?php echo $payment['receipt_code']; ?></td>
                        <td>
                            <a href="javascript:;" onclick="showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt/'.$payment['receipt_code'].'/'.$payment['student_id'].'/'.$payment['classes_amount'].'/'.strtotime($payment['created_at'])); ?>')" class="btn btn-xs btn-info" title="View Receipt">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            No classes fee payments found for this period.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#classes_payments_table').DataTable({
        "pageLength": 25,
        "order": [[ 1, "desc" ]],
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>