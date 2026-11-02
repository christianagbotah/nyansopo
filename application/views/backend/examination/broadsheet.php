<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <h3 class="panel-title" style="color: white;">
                    <i class="fa fa-table"></i> Examination Broadsheet
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Exam Info -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h4><?php echo $exam['exam_name']; ?></h4>
                        <p>Class: <strong><?php echo $exam['class_name']; ?></strong> | 
                           Year: <strong><?php echo $exam['year']; ?></strong> | 
                           Term: <strong><?php echo $exam['term']; ?></strong></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fa fa-print"></i> Print
                        </button>
                        <button onclick="exportExcel()" class="btn btn-success">
                            <i class="fa fa-file-excel"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Broadsheet Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="broadsheet-table">
                        <thead style="background: #667eea; color: white;">
                            <tr>
                                <th rowspan="2" class="text-center">#</th>
                                <th rowspan="2">Student Name</th>
                                <?php foreach($subjects as $subject): ?>
                                <th class="text-center"><?php echo $subject['name']; ?></th>
                                <?php endforeach; ?>
                                <th rowspan="2" class="text-center">Total</th>
                                <th rowspan="2" class="text-center">Average</th>
                                <th rowspan="2" class="text-center">Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $position = 1;
                            foreach($students as $student): 
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $position++; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <?php foreach($subjects as $subject): ?>
                                <td class="text-center">
                                    <?php 
                                    $mark = isset($student['marks'][$subject['subject_id']]) ? $student['marks'][$subject['subject_id']] : '-';
                                    echo $mark;
                                    ?>
                                </td>
                                <?php endforeach; ?>
                                <td class="text-center"><strong><?php echo number_format($student['total'], 2); ?></strong></td>
                                <td class="text-center"><strong><?php echo number_format($student['average'], 2); ?></strong></td>
                                <td class="text-center"><strong><?php echo $student['position']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>
<script>
function exportExcel() {
    const table = document.getElementById('broadsheet-table');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Broadsheet"});
    XLSX.writeFile(wb, 'Broadsheet_<?php echo $exam['exam_name']; ?>.xlsx');
}
</script>

<style>
@media print {
    .panel-heading, .btn { display: none; }
    table { font-size: 10px; }
}
</style>
