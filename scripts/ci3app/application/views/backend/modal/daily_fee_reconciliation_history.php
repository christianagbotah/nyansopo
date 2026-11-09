<?php
if (!isset($student_id) || !isset($student)) {
    echo '<div class="alert alert-danger">Student information not found</div>';
    return;
}
?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 20px;">
            <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">&times;</button>
            <h4 class="modal-title"><i class="fa fa-history"></i> Reconciliation History: <?php echo $student->name; ?></h4>
        </div>

        <div class="modal-body" style="padding: 30px;">
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <i class="fa fa-info-circle"></i> <strong>Student Code:</strong> <?php echo $student->student_code; ?>
            </div>

            <div id="reconciliation-history-content">
                <div class="text-center" style="padding: 40px;">
                    <i class="fa fa-spinner fa-spin fa-3x" style="color: #8b5cf6;"></i>
                    <p style="margin-top: 15px; color: #64748b;">Loading history...</p>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="padding: 15px 30px; background: #f8fafc;">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<script>
// Load reconciliation history when modal opens
$(document).ready(function() {
    $.ajax({
        url: '<?php echo base_url(); ?>admin/reconciliation_history',
        type: 'POST',
        data: { student_id: <?php echo $student_id; ?> },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.history.length > 0) {
                var html = '<div class="table-responsive"><table class="table table-striped">';
                html += '<thead><tr>';
                html += '<th>Date</th>';
                html += '<th>Fee Type</th>';
                html += '<th>Adjustment</th>';
                html += '<th>Balance Change</th>';
                html += '<th>Performed By</th>';
                html += '<th>Notes</th>';
                html += '</tr></thead><tbody>';
                
                response.history.forEach(function(record) {
                    var balanceChange = '';
                    if (record.adjustment_type === 'arrears') {
                        var arrearsChange = parseFloat(record.arrears_after) - parseFloat(record.arrears_before);
                        balanceChange = '<span style="color: #dc2626;">Arrears: GH₵' + Math.abs(arrearsChange).toFixed(2) + '</span>';
                    } else {
                        var prepaidChange = parseFloat(record.balance_after) - parseFloat(record.balance_before);
                        balanceChange = '<span style="color: #059669;">Prepaid: GH₵' + Math.abs(prepaidChange).toFixed(2) + '</span>';
                    }
                    
                    html += '<tr>';
                    html += '<td>' + record.created_at + '</td>';
                    html += '<td>' + record.fee_type.charAt(0).toUpperCase() + record.fee_type.slice(1) + '</td>';
                    html += '<td>' + record.adjustment_type.charAt(0).toUpperCase() + record.adjustment_type.slice(1) + '</td>';
                    html += '<td>' + balanceChange + '</td>';
                    html += '<td>' + record.performed_by + ' (' + record.performed_by_role + ')</td>';
                    html += '<td>' + record.notes + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                $('#reconciliation-history-content').html(html);
            } else {
                $('#reconciliation-history-content').html(
                    '<div class="text-center" style="padding: 40px; color: #94a3b8;">' +
                    '<i class="fa fa-inbox fa-3x" style="color: #cbd5e1;"></i>' +
                    '<p style="margin-top: 15px;">No reconciliation history found for this student</p>' +
                    '</div>'
                );
            }
        },
        error: function() {
            $('#reconciliation-history-content').html(
                '<div class="alert alert-danger">' +
                '<i class="fa fa-exclamation-triangle"></i> Failed to load reconciliation history' +
                '</div>'
            );
        }
    });
});
</script>
