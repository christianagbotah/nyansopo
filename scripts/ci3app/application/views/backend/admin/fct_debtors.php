<?php
$timestamp = $this->uri->segment(3);
$fee_type = $this->uri->segment(4);
$date = date('Y-m-d', $timestamp);

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-list"></i>
                    <?php echo get_phrase('debtors_list'); ?> - <?php echo date('d/m/Y', $timestamp); ?>
                </div>
            </div>
            <div class="panel-body">
                <?php if($fee_type == 'fo'): ?>
                    <h4>Feeding Fee Debtors</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="table_export">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Amount Owed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $this->db->select('s.name, s.student_id, e.class_id, ffp.due');
                                $this->db->from('feeding_fee_payment ffp');
                                $this->db->join('student s', 's.student_id = ffp.student_id');
                                $this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = "' . $running_year . '"');
                                $this->db->where('ffp.day_timestamp', $timestamp);
                                $this->db->where('ffp.due >', 0);
                                $this->db->where('ffp.can_delete !=', 'trash');
                                $debtors = $this->db->get()->result_array();
                                
                                $counter = 1;
                                foreach($debtors as $debtor):
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo $debtor['name']; ?></td>
                                    <td><?php echo getFullClassName($debtor['class_id']); ?></td>
                                    <td>GHS <?php echo number_format($debtor['due'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif($fee_type == 'co'): ?>
                    <h4>Classes Fee Debtors</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="table_export">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Amount Owed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $this->db->select('s.name, s.student_id, e.class_id, cfp.due');
                                $this->db->from('classes_fee_payment cfp');
                                $this->db->join('student s', 's.student_id = cfp.student_id');
                                $this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = "' . $running_year . '"');
                                $this->db->where('cfp.day_timestamp', $timestamp);
                                $this->db->where('cfp.due >', 0);
                                $this->db->where('cfp.can_delete !=', 'trash');
                                $debtors = $this->db->get()->result_array();
                                
                                $counter = 1;
                                foreach($debtors as $debtor):
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo $debtor['name']; ?></td>
                                    <td><?php echo getFullClassName($debtor['class_id']); ?></td>
                                    <td>GHS <?php echo number_format($debtor['due'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif($fee_type == 'to'): ?>
                    <h4>Transport Fee Debtors</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="table_export">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Amount Owed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $this->db->select('s.name, s.student_id, e.class_id, tfp.due');
                                $this->db->from('transport_fare_payment tfp');
                                $this->db->join('student s', 's.student_id = tfp.student_id');
                                $this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = "' . $running_year . '"');
                                $this->db->where('tfp.day_timestamp', $timestamp);
                                $this->db->where('tfp.due >', 0);
                                $this->db->where('tfp.can_delete !=', 'trash');
                                $debtors = $this->db->get()->result_array();
                                
                                $counter = 1;
                                foreach($debtors as $debtor):
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo $debtor['name']; ?></td>
                                    <td><?php echo getFullClassName($debtor['class_id']); ?></td>
                                    <td>GHS <?php echo number_format($debtor['due'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="entypo-info"></i>
                        Please select a fee type to view debtors.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table_export').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>