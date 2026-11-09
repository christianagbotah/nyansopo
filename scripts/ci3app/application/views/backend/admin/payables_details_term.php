<?php
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
$fee_type = $param2;
$year = $param3;
$term = $param4;

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

// Get latest timestamp for this term
$this->db->select('day_timestamp');
$this->db->where('year', $year);
$this->db->where('term', $term);
$this->db->where('can_delete !=', 'trash');
$this->db->order_by('day_timestamp', 'DESC');
$this->db->limit(1);
$latest_date = $this->db->get($table)->row();
$latest_timestamp = $latest_date ? $latest_date->day_timestamp : 0;

// Get students with negative owing (payables)
$this->db->select('student_id, due, amount');
$this->db->where('can_delete !=', 'trash');
$this->db->where('due <', 0);
$this->db->where('day_timestamp', $latest_timestamp);
$this->db->order_by('due', 'ASC');
$payables = $this->db->get($table)->result_array();
?>

<div class="modal-header" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>
    <h4 class="modal-title"><i class="fa fa-coins"></i> <?php echo $label; ?> - Advance Payments (Payables)</h4>
</div>

<div class="modal-body">
    <div class="mb-3 d-flex justify-content-between align-items-center" style="margin-bottom: 15px;">
        <div>
            <strong>Year:</strong> <?php echo $year; ?> | <strong>Term:</strong> <?php echo $term; ?>
            <br><strong>Total Students:</strong> <?php echo count($payables); ?>
        </div>
    </div>

    <?php if (empty($payables)): ?>
        <div class="alert alert-info text-center">
            <i class="fa fa-info-circle"></i> No advance payments found for this term.
        </div>
    <?php else: ?>
        <div class="mb-3">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" id="payables_search" class="form-control" placeholder="🔍 Search by student name, code, or class..." style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                </div>
                <div class="col-md-6">
                    <select id="filter_payables_class" class="form-control" style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                        <option value="">All Classes</option>
                        <?php getFullClassList(); ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="payables_table" style="width:100%">
                <thead style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
                    <tr>
                        <th style="border: none;">#</th>
                        <th style="border: none;">Student Code</th>
                        <th style="border: none;">Student Name</th>
                        <th style="border: none;">Class</th>
                        <th style="border: none; text-align: right;">Advance Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    foreach ($payables as $index => $row): 
                        $student = $this->db->get_where('student', ['student_id' => $row['student_id']])->row();
                        $enroll = $this->db->get_where('enroll', ['student_id' => $row['student_id'], 'mute' => '0'])->row();
                        $class = $this->db->get_where('class', ['class_id' => $enroll->class_id])->row();
                        $advance = abs($row['due']);
                        $total += $advance;
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;" data-class="<?php echo $enroll->class_id; ?>">
                        <td style="padding: 12px 8px;"><?php echo $index + 1; ?></td>
                        <td style="padding: 12px 8px;"><strong><?php echo $student->student_code; ?></strong></td>
                        <td style="padding: 12px 8px;"><?php echo $student->name; ?></td>
                        <td style="padding: 12px 8px;"><span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px;"><?php echo $class->name . ' ' . $class->name_numeric; ?></span></td>
                        <td style="text-align: right; color: #06b6d4; font-weight: bold; font-size: 1.05em; padding: 12px 8px;">
                            <?php echo $currency . number_format($advance, 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: #f0fdfa; font-weight: bold; border-top: 2px solid #06b6d4;">
                    <tr>
                        <td colspan="4" style="text-align: right; padding: 12px; font-size: 1.1em;">TOTAL:</td>
                        <td style="text-align: right; color: #06b6d4; font-size: 1.2em; padding: 12px;">
                            <?php echo $currency . number_format($total, 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#payables_table')) {
                $('#payables_table').DataTable().destroy();
            }
            
            var table = $('#payables_table').DataTable({
                "pageLength": 25,
                "order": [[4, "desc"]],
                "dom": 'lrtip',
                "language": {
                    "lengthMenu": "Show _MENU_ students"
                }
            });
            
            var customFilterIndex = $.fn.dataTable.ext.search.length;
            
            var customFilter = function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'payables_table') return true;
                
                var classId = $('#filter_payables_class').val();
                var row = table.row(dataIndex).node();
                var $row = $(row);
                
                if (classId && $row.attr('data-class') != classId) return false;
                
                return true;
            };
            
            $.fn.dataTable.ext.search.push(customFilter);
            
            $('#payables_search').on('keyup', function() {
                table.search(this.value).draw();
            });
            
            $('#filter_payables_class').on('change', function() {
                table.draw();
            });
            
            $('#modal_ajax').on('hidden.bs.modal', function() {
                if ($.fn.DataTable.isDataTable('#payables_table')) {
                    $('#payables_table').DataTable().destroy();
                }
                if (customFilterIndex < $.fn.dataTable.ext.search.length) {
                    $.fn.dataTable.ext.search.splice(customFilterIndex, 1);
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
