<?php
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
$fee_type = $param2;
$date = $param3;
$timestamp = strtotime($date);

$fee_labels = [
    'feeding' => 'Feeding Fee',
    'classes' => 'Classes Fee',
    'transport' => 'Transport Fare'
];

$table_map = [
    'feeding' => 'feeding_fee_payment',
    'classes' => 'classes_fee_payment',
    'transport' => 'transport_fare_payment'
];

$table = $table_map[$fee_type];
$label = $fee_labels[$fee_type];

// Get latest timestamp
$this->db->select('day_timestamp');
$this->db->where('can_delete !=', 'trash');
$this->db->where('day_timestamp <=', $timestamp);
$this->db->order_by('day_timestamp', 'DESC');
$this->db->limit(1);
$latest_date = $this->db->get($table)->row();
$latest_timestamp = $latest_date ? $latest_date->day_timestamp : $timestamp;

// Get students with positive owing (outstanding)
$this->db->select('student_id, due, amount');
$this->db->where('can_delete !=', 'trash');
$this->db->where('due >', 0);
$this->db->where('day_timestamp', $latest_timestamp);
$this->db->order_by('due', 'DESC');
$outstanding = $this->db->get($table)->result_array();
?>

<div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>
    <h4 class="modal-title"><i class="fa fa-exclamation-circle"></i> <?php echo $label; ?> - Outstanding Fees</h4>
</div>

<div class="modal-body">
    <div class="mb-3 d-flex justify-content-between align-items-center" style="margin-bottom: 15px;">
        <div>
            <strong>Date:</strong> <?php echo date('l, F d, Y', $latest_timestamp); ?>
            <br><strong>Total Students:</strong> <?php echo count($outstanding); ?>
        </div>
        <div class="btn-group">
            <button onclick="export_outstanding_excel('<?php echo $fee_type; ?>')" class="btn btn-success btn-sm">
                <i class="fa fa-file-excel"></i> Export Excel
            </button>
            <button onclick="export_outstanding_pdf('<?php echo $fee_type; ?>')" class="btn btn-danger btn-sm">
                <i class="fa fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="print_outstanding('<?php echo $fee_type; ?>')" class="btn btn-primary btn-sm">
                <i class="fa fa-print"></i> Print
            </button>
        </div>
    </div>

    <?php if (empty($outstanding)): ?>
        <div class="alert alert-success text-center">
            <i class="fa fa-check-circle"></i> No outstanding fees found. All students are up to date!
        </div>
    <?php else: ?>
        <div class="mb-3">
            <input type="text" id="outstanding_search" class="form-control" placeholder="🔍 Search by student name, code, or class..." style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="outstanding_table" style="width:100%">
                <thead style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                    <tr>
                        <th style="border: none;">#</th>
                        <th style="border: none;">Student Code</th>
                        <th style="border: none;">Student Name</th>
                        <th style="border: none;">Class</th>
                        <th style="border: none; text-align: right;">Outstanding Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    foreach ($outstanding as $index => $row): 
                        $student = $this->db->get_where('student', ['student_id' => $row['student_id']])->row();
                        $enroll = $this->db->get_where('enroll', ['student_id' => $row['student_id'], 'mute' => '0'])->row();
                        $class = $this->db->get_where('class', ['class_id' => $enroll->class_id])->row();
                        $owe = $row['due'];
                        $total += $owe;
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px 8px;"><?php echo $index + 1; ?></td>
                        <td style="padding: 12px 8px;"><strong><?php echo $student->student_code; ?></strong></td>
                        <td style="padding: 12px 8px;"><?php echo $student->name; ?></td>
                        <td style="padding: 12px 8px;"><span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px;"><?php echo $class->name . ' ' . $class->name_numeric; ?></span></td>
                        <td style="text-align: right; color: #ef4444; font-weight: bold; font-size: 1.05em; padding: 12px 8px;">
                            <?php echo $currency . number_format($owe, 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: #fef2f2; font-weight: bold; border-top: 2px solid #ef4444;">
                    <tr>
                        <td colspan="4" style="text-align: right; padding: 12px; font-size: 1.1em;">TOTAL:</td>
                        <td style="text-align: right; color: #ef4444; font-size: 1.2em; padding: 12px;">
                            <?php echo $currency . number_format($total, 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#outstanding_table')) {
                $('#outstanding_table').DataTable().destroy();
            }
            
            var table = $('#outstanding_table').DataTable({
                "pageLength": 25,
                "order": [[4, "desc"]],
                "dom": 'lrtip',
                "language": {
                    "lengthMenu": "Show _MENU_ students"
                }
            });
            
            $('#outstanding_search').on('keyup', function() {
                table.search(this.value).draw();
            });
            
            $('#modal_ajax').on('hidden.bs.modal', function() {
                if ($.fn.DataTable.isDataTable('#outstanding_table')) {
                    $('#outstanding_table').DataTable().destroy();
                }
            });
        });
        </script>
    <?php endif; ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>

<script>
$('#modal_ajax').one('hidden.bs.modal', function() {
    location.reload();
});
</script>
