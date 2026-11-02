<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get year and term from URL parameters
$year = $this->uri->segment(4);
$term = $this->uri->segment(5);

// Get unpaid balances from daily_fee_wallet
$this->db->select('dfw.*, s.name as student_name, c.name as class_name, c.name_numeric');
$this->db->from('daily_fee_wallet dfw');
$this->db->join('student s', 's.student_id = dfw.student_id');
$this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = dfw.year AND e.term = dfw.term');
$this->db->join('class c', 'c.class_id = e.class_id');
$this->db->where('dfw.year', $year);
$this->db->where('dfw.term', $term);
$this->db->where('(dfw.feeding_arrears > 0 OR dfw.classes_arrears > 0 OR dfw.transport_arrears > 0 OR dfw.water_arrears > 0 OR dfw.breakfast_arrears > 0)');
$this->db->order_by('s.name', 'ASC');
$debtors = $this->db->get()->result_array();

$feeding_debtors = array();
$classes_debtors = array();
$transport_debtors = array();
$water_debtors = array();
$breakfast_debtors = array();

foreach($debtors as $debtor) {
    if($debtor['feeding_arrears'] > 0) {
        $feeding_debtors[] = array_merge($debtor, ['due' => $debtor['feeding_arrears']]);
    }
    if($debtor['classes_arrears'] > 0) {
        $classes_debtors[] = array_merge($debtor, ['due' => $debtor['classes_arrears']]);
    }
    if($debtor['transport_arrears'] > 0) {
        $transport_debtors[] = array_merge($debtor, ['due' => $debtor['transport_arrears']]);
    }
    if($debtor['water_arrears'] > 0) {
        $water_debtors[] = array_merge($debtor, ['due' => $debtor['water_arrears']]);
    }
    if($debtor['breakfast_arrears'] > 0) {
        $breakfast_debtors[] = array_merge($debtor, ['due' => $debtor['breakfast_arrears']]);
    }
}

$total_feeding_owe = array_sum(array_column($feeding_debtors, 'due'));
$total_classes_owe = array_sum(array_column($classes_debtors, 'due'));
$total_transport_owe = array_sum(array_column($transport_debtors, 'due'));
$total_water_owe = array_sum(array_column($water_debtors, 'due'));
$total_breakfast_owe = array_sum(array_column($breakfast_debtors, 'due'));
?>

<div class="panel panel-danger">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-exclamation-triangle"></i> Unpaid Balances - Year: <?php echo explode('-', $year)[1]; ?> | Term: <?php echo $term; ?>
        </h4>
    </div>
    <div class="panel-body">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="alert alert-danger">
                    <h5><i class="fa fa-utensils"></i> Feeding Fee Outstanding</h5>
                    <h3><?php echo $currency . number_format($total_feeding_owe, 2); ?></h3>
                    <small><?php echo count($feeding_debtors); ?> students</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-warning">
                    <h5><i class="fa fa-chalkboard-teacher"></i> Classes Fee Outstanding</h5>
                    <h3><?php echo $currency . number_format($total_classes_owe, 2); ?></h3>
                    <small><?php echo count($classes_debtors); ?> students</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-info">
                    <h5><i class="fa fa-bus"></i> Transport Fare Outstanding</h5>
                    <h3><?php echo $currency . number_format($total_transport_owe, 2); ?></h3>
                    <small><?php echo count($transport_debtors); ?> students</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-primary">
                    <h5><i class="fa fa-tint"></i> Water Fee Outstanding</h5>
                    <h3><?php echo $currency . number_format($total_water_owe, 2); ?></h3>
                    <small><?php echo count($water_debtors); ?> students</small>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="alert alert-success">
                    <h5><i class="fa fa-coffee"></i> Breakfast Fee Outstanding</h5>
                    <h3><?php echo $currency . number_format($total_breakfast_owe, 2); ?></h3>
                    <small><?php echo count($breakfast_debtors); ?> students</small>
                </div>
            </div>
        </div>

        <!-- Tabs for different fee types -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#feeding_tab" aria-controls="feeding_tab" role="tab" data-toggle="tab">
                    <i class="fa fa-utensils"></i> Feeding Fee (<?php echo count($feeding_debtors); ?>)
                </a>
            </li>
            <li role="presentation">
                <a href="#classes_tab" aria-controls="classes_tab" role="tab" data-toggle="tab">
                    <i class="fa fa-chalkboard-teacher"></i> Classes Fee (<?php echo count($classes_debtors); ?>)
                </a>
            </li>
            <li role="presentation">
                <a href="#transport_tab" aria-controls="transport_tab" role="tab" data-toggle="tab">
                    <i class="fa fa-bus"></i> Transport Fare (<?php echo count($transport_debtors); ?>)
                </a>
            </li>
            <li role="presentation">
                <a href="#water_tab" aria-controls="water_tab" role="tab" data-toggle="tab">
                    <i class="fa fa-tint"></i> Water Fee (<?php echo count($water_debtors); ?>)
                </a>
            </li>
            <li role="presentation">
                <a href="#breakfast_tab" aria-controls="breakfast_tab" role="tab" data-toggle="tab">
                    <i class="fa fa-coffee"></i> Breakfast Fee (<?php echo count($breakfast_debtors); ?>)
                </a>
            </li>
        </ul>

        <div class="tab-content" style="margin-top: 15px;">
            <!-- Feeding Fee Tab -->
            <div role="tabpanel" class="tab-pane active" id="feeding_tab">
                <?php if(count($feeding_debtors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="feeding_debtors_table">
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
                            $counter = 1;
                            foreach($feeding_debtors as $debtor): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo $debtor['student_name']; ?></td>
                                <td><?php echo $debtor['class_name'] . ' ' . $debtor['name_numeric']; ?></td>
                                <td><?php echo $currency . number_format($debtor['due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> No outstanding feeding fee balances!
                </div>
                <?php endif; ?>
            </div>

            <!-- Classes Fee Tab -->
            <div role="tabpanel" class="tab-pane" id="classes_tab">
                <?php if(count($classes_debtors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="classes_debtors_table">
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
                            $counter = 1;
                            foreach($classes_debtors as $debtor): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo $debtor['student_name']; ?></td>
                                <td><?php echo $debtor['class_name'] . ' ' . $debtor['name_numeric']; ?></td>
                                <td><?php echo $currency . number_format($debtor['due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> No outstanding classes fee balances!
                </div>
                <?php endif; ?>
            </div>

            <!-- Transport Fare Tab -->
            <div role="tabpanel" class="tab-pane" id="transport_tab">
                <?php if(count($transport_debtors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="transport_debtors_table">
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
                            $counter = 1;
                            foreach($transport_debtors as $debtor): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo $debtor['student_name']; ?></td>
                                <td><?php echo $debtor['class_name'] . ' ' . $debtor['name_numeric']; ?></td>
                                <td><?php echo $currency . number_format($debtor['due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> No outstanding transport fare balances!
                </div>
                <?php endif; ?>
            </div>

            <!-- Water Fee Tab -->
            <div role="tabpanel" class="tab-pane" id="water_tab">
                <?php if(count($water_debtors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="water_debtors_table">
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
                            $counter = 1;
                            foreach($water_debtors as $debtor): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo $debtor['student_name']; ?></td>
                                <td><?php echo $debtor['class_name'] . ' ' . $debtor['name_numeric']; ?></td>
                                <td><?php echo $currency . number_format($debtor['due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> No outstanding water fee balances!
                </div>
                <?php endif; ?>
            </div>

            <!-- Breakfast Fee Tab -->
            <div role="tabpanel" class="tab-pane" id="breakfast_tab">
                <?php if(count($breakfast_debtors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="breakfast_debtors_table">
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
                            $counter = 1;
                            foreach($breakfast_debtors as $debtor): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo $debtor['student_name']; ?></td>
                                <td><?php echo $debtor['class_name'] . ' ' . $debtor['name_numeric']; ?></td>
                                <td><?php echo $currency . number_format($debtor['due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> No outstanding breakfast fee balances!
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#feeding_debtors_table, #classes_debtors_table, #transport_debtors_table, #water_debtors_table, #breakfast_debtors_table').DataTable({
        "pageLength": 25,
        "order": [[ 3, "desc" ]],
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>