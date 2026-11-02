<?php
$student_id = $param1;
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
?>

<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color: white; opacity: 1;">&times;</button>
    <h4 class="modal-title" style="font-weight: bold; font-size: 20px;">
        <i class="fa fa-file-invoice"></i> Student Bill Report
    </h4>
</div>

<div class="modal-body" style="padding: 0; max-height: 80vh; overflow-y: auto;">
    <iframe id="bill_report_frame" src="<?php echo site_url('admin/student_bill_report/'.$student_id); ?>" style="width: 100%; height: 75vh; border: none;"></iframe>
</div>

<div class="modal-footer" style="background: #f8f9fa; padding: 15px;">
    <button type="button" class="btn btn-default" data-dismiss="modal" style="font-size: 14px; padding: 10px 20px;">
        <i class="fa fa-times"></i> Close
    </button>
    <button type="button" onclick="printBillReport()" class="btn btn-primary" style="font-size: 14px; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
        <i class="fa fa-print"></i> Print Bill Report
    </button>
</div>

<script>
function printBillReport() {
    document.getElementById('bill_report_frame').contentWindow.print();
}
</script>
