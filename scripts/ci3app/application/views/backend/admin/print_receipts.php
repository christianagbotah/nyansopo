<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
.filter-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 42px; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
.btn-print-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-print-modern:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4); transform: translateY(-2px); }
.btn-print-student { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3); padding: 6px 12px; font-size: 12px; height: auto; border: none; border-radius: 8px; cursor: pointer; }
.btn-print-student:hover { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); transform: translateY(-1px); }
.receipt-container { display: flex; flex-wrap: wrap; gap: 20px; padding: 20px; }
.receipt { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; border: 1px solid #ddd; padding: 5mm; background: white; page-break-inside: avoid; }
.header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 8px; }
.header img { display: block; margin: 0 auto 5px auto; max-height: 40px; }
.header h3 { font-size: 16px; margin: 3px 0; }
.header p { font-size: 10px; margin: 2px 0; }
.info { margin: 8px 0; font-size: 11px; }
.info-row { display: flex; justify-content: space-between; margin: 3px 0; }
.divider { border-top: 1px dashed #000; margin: 8px 0; }
.items { margin: 8px 0; }
.item-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 11px; }
.total-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }
.footer { text-align: center; font-size: 9px; margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }
@media print {
    .no-print, button, .btn-modern, .btn-print-student, .filter-card, .container-fluid { display: none !important; }
    header, .navbar, .sidebar, .page-sidebar, nav, .main-header, .main-sidebar, .content-wrapper { display: none !important; }
    body { margin: 0; padding: 0; }
    .receipt-container { display: block !important; padding: 0; }
    .receipt { width: 80mm; margin: 0 auto 10mm; border: none; page-break-inside: avoid; }
    @page { size: 80mm auto; margin: 0; }
}
</style>

<div class="container-fluid no-print">
    <div class="filter-card">
        <h2 style="margin-bottom: 25px; color: #1f2937; font-size: 24px; font-weight: 700;"><i class="fa fa-print" style="color: #3b82f6;"></i> Print Payment Receipts</h2>
        
        <?php echo form_open('', array('id' => 'filter_form')); ?>
        <div class="row">
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="text" name="date" id="date_filter" class="form-control datepicker" value="<?php echo date('d-m-Y'); ?>" required>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Item Type</label>
                <select name="item_type" id="item_type" class="form-control">
                    <option value="all">All Items</option>
                    <option value="Feeding Fee">Feeding Fee</option>
                    <option value="Breakfast Fee">Breakfast Fee</option>
                    <option value="Classes Fee">Classes Fee</option>
                    <option value="Water Fee">Water Fee</option>
                    <option value="Transport Fare">Transport Fare</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Class</label>
                <select name="class_id" id="class_filter" class="form-control">
                    <option value="">All Classes</option>
                    <?php getFullClassList(); ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Student Name</label>
                <input type="text" name="student_name" id="student_name" class="form-control" placeholder="Search by name">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="button" onclick="loadReceipts()" class="btn-modern btn-primary-modern">
                        <i class="fa fa-search"></i> Load Receipts
                    </button>
                    <button type="button" onclick="printAllReceipts()" class="btn-modern btn-print-modern" id="print_btn" style="display:none; margin-left: 10px; margin-top: 10px;">
                        <i class="fa fa-print"></i> Print All
                    </button>
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="receipt-container" id="receipts_holder">
    <div class="text-center w-full max-w-full" style="padding: 40px; color: #9ca3af;">
        <i class="fa fa-receipt" style="font-size: 48px; margin-bottom: 15px;"></i>
        <p>Select filters and click "Load Receipts" to view payment receipts</p>
    </div>
</div>

<script>
function loadReceipts() {
    const date = $('#date_filter').val();
    const itemType = $('#item_type').val();
    const classId = $('#class_filter').val();
    const studentName = $('#student_name').val();
    
    if(!date) {
        toastr.error('Please select a date', 'Error');
        return;
    }
    
    console.log('Loading receipts with date:', date);
    toastr.info('Loading receipts...', 'Please wait');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_thermal_receipts'); ?>',
        type: 'POST',
        data: { date, item_type: itemType, class_id: classId, student_name: studentName },
        dataType: 'json'
    }).done(function(response) {
        console.log('Response:', response);
        if(response.status === 'success') {
            $('#receipts_holder').html(response.html);
            $('#print_btn').show();
            toastr.success('Receipts loaded successfully', 'Success');
        } else if(response.status === 'warning') {
            $('#receipts_holder').html('<div class="text-center" style="padding: 40px; color: #f59e0b;"><i class="fa fa-info-circle" style="font-size: 48px; margin-bottom: 15px;"></i><p style="font-size: 16px; font-weight: 600;">'+response.message+'</p></div>');
            $('#print_btn').hide();
            toastr.warning(response.message, 'No Results');
        } else {
            $('#receipts_holder').html('<div class="text-center" style="padding: 40px; color: #ef4444;"><i class="fa fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i><p style="font-size: 16px; font-weight: 600;">'+response.message+'</p></div>');
            $('#print_btn').hide();
            toastr.error(response.message, 'Error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', xhr.responseText);
        $('#receipts_holder').html('<div class="text-center" style="padding: 40px; color: #ef4444;"><i class="fa fa-times-circle" style="font-size: 48px; margin-bottom: 15px;"></i><p style="font-size: 16px; font-weight: 600;">Connection error. Please check your internet and try again.</p></div>');
        $('#print_btn').hide();
        toastr.error('Connection error. Please try again.', 'Error');
    });
}

function printReceipt(studentId) {
    const studentReceipt = document.getElementById('student_receipt_' + studentId);
    if(!studentReceipt) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<!DOCTYPE html><html><head><title>Print Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('* { margin: 0; padding: 0; box-sizing: border-box; }');
    printWindow.document.write('body { font-family: "Courier New", monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5mm; }');
    printWindow.document.write('.receipt { width: 100%; }');
    printWindow.document.write('.header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 8px; }');
    printWindow.document.write('.header img { display: block; margin: 0 auto 5px auto; max-height: 40px; }');
    printWindow.document.write('.header h3 { font-size: 16px; margin: 3px 0; }');
    printWindow.document.write('.header p { font-size: 10px; margin: 2px 0; }');
    printWindow.document.write('.info { margin: 8px 0; font-size: 11px; }');
    printWindow.document.write('button, .btn-modern, .btn-print-student, .no-print { display: none !important; }');
    printWindow.document.write('@page { size: 80mm auto; margin: 0; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(studentReceipt.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

function printStudentReceipt(studentId) {
    const studentReceipt = document.getElementById('student_receipt_' + studentId);
    if(!studentReceipt) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<!DOCTYPE html><html><head><title>Print Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('* { margin: 0; padding: 0; box-sizing: border-box; }');
    printWindow.document.write('body { font-family: "Courier New", monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5mm; }');
    printWindow.document.write('.receipt { width: 100%; }');
    printWindow.document.write('.header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 8px; }');
    printWindow.document.write('.header img { display: block; margin: 0 auto 5px auto; max-height: 40px; }');
    printWindow.document.write('.header h3 { font-size: 16px; margin: 3px 0; }');
    printWindow.document.write('.header p { font-size: 10px; margin: 2px 0; }');
    printWindow.document.write('.info { margin: 8px 0; font-size: 11px; }');
    printWindow.document.write('.info-row { display: flex; justify-content: space-between; margin: 3px 0; }');
    printWindow.document.write('.divider { border-top: 1px dashed #000; margin: 8px 0; }');
    printWindow.document.write('.items { margin: 8px 0; }');
    printWindow.document.write('.item-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 11px; }');
    printWindow.document.write('.total-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }');
    printWindow.document.write('.footer { text-align: center; font-size: 9px; margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }');
    printWindow.document.write('button, .btn-modern, .btn-print-student, .no-print { display: none !important; }');
    printWindow.document.write('@page { size: 80mm auto; margin: 0; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(studentReceipt.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

function printAllReceipts() {
    const receiptsContent = document.getElementById('receipts_holder').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<!DOCTYPE html><html><head><title>Print All Receipts</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('* { margin: 0; padding: 0; box-sizing: border-box; }');
    printWindow.document.write('body { font-family: "Courier New", monospace; font-size: 12px; margin: 0; padding: 0; }');
    printWindow.document.write('.receipt-container { display: block; padding: 0; }');
    printWindow.document.write('.receipt { font-family: "Courier New", monospace; font-size: 12px; width: 80mm; margin: 0 auto 10mm; padding: 5mm; background: white; page-break-inside: avoid; }');
    printWindow.document.write('.header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 8px; }');
    printWindow.document.write('.header img { display: block; margin: 0 auto 5px auto; max-height: 40px; }');
    printWindow.document.write('.header h3 { font-size: 16px; margin: 3px 0; }');
    printWindow.document.write('.header p { font-size: 10px; margin: 2px 0; }');
    printWindow.document.write('.info { margin: 8px 0; font-size: 11px; }');
    printWindow.document.write('.info-row { display: flex; justify-content: space-between; margin: 3px 0; }');
    printWindow.document.write('.divider { border-top: 1px dashed #000; margin: 8px 0; }');
    printWindow.document.write('.items { margin: 8px 0; }');
    printWindow.document.write('.item-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 11px; }');
    printWindow.document.write('.total-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }');
    printWindow.document.write('.footer { text-align: center; font-size: 9px; margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }');
    printWindow.document.write('button, .btn-modern, .btn-print-student, .no-print { display: none !important; }');
    printWindow.document.write('@page { size: 80mm auto; margin: 0; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(receiptsContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

$(document).ready(function() {
    $('.datepicker').datepicker({ 
        format: 'dd-mm-yyyy', 
        autoclose: true,
        todayHighlight: true 
    }).datepicker('setDate', new Date());
});
</script>
