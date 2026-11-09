<?php
// Expected vars: $start_date, $end_date, $bill_item, $boarding_system, $residence_type (optional)
// Build a pivot: columns = bill items, rows = classes, values = sum(amount) from payment (billed invoices only), filtered by date range and optional residence type

$ci =& get_instance();
$ci->load->database();

// Get all distinct bill items from invoice table within the window (respect $bill_item if specified)
$ci->db->select('title');
$ci->db->distinct();
$ci->db->from('invoice');
$ci->db->where('creation_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
$ci->db->where('can_delete !=', 'trash');
if (!empty($bill_item) && $bill_item !== '0') {
    $ci->db->where('title', $bill_item);
}
$billItemsRows = $ci->db->get()->result_array();
$billTitles = array_map(function($r){ return $r['title']; }, $billItemsRows);
sort($billTitles);


// Fallback: if no bill items found, show message
if (count($billTitles) === 0) {
    echo '<div class="alert alert-info">No invoices found for the selected filters.</div>';
    return;
}

// Get all classes in proper order (CRECHE, NURSERY, KG, BASIC, JHS)
$class_names = ['CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS'];
$classes = [];
foreach ($class_names as $name) {
    $ci->db->order_by('name_numeric', 'ASC');
    $class_rows = $ci->db->get_where('class', array('name' => $name))->result_array();
    foreach ($class_rows as $c) {
        $classes[] = $c;
    }
}

// Helper to get expected amount for class and bill title
function getExpectedForClassAndBill($class_id, $title, $start_date, $end_date, $boarding_system, $residence_type) {
    $ci =& get_instance();
    $ci->db->select_sum('invoice.amount', 'total_expected');
    $ci->db->from('invoice');
    $ci->db->join('student', 'student.student_id = invoice.student_id', 'left');
    $ci->db->join('enroll', 'enroll.student_id = student.student_id', 'left');
    $ci->db->where('invoice.creation_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
    $ci->db->where('enroll.class_id', $class_id);
    $ci->db->where('invoice.title', $title);
    $ci->db->where('invoice.can_delete !=', 'trash');
    if ($boarding_system === 'yes' && !empty($residence_type) && $residence_type !== '0') {
        $ci->db->where('student.residence_type', $residence_type);
    }
    $row = $ci->db->get()->row();
    return $row && $row->total_expected ? floatval($row->total_expected) : 0.0;
}

// Helper to get actual amount paid for class and bill title
function getActualForClassAndBill($class_id, $title, $start_date, $end_date, $boarding_system, $residence_type) {
    $ci =& get_instance();
    $ci->db->select_sum('payment.amount', 'total_actual');
    $ci->db->from('payment');
    $ci->db->join('student', 'student.student_id = payment.student_id', 'left');
    $ci->db->join('enroll', 'enroll.student_id = student.student_id', 'left');
    $ci->db->where('payment.day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
    $ci->db->where('payment.invoice_id IS NOT NULL');
    $ci->db->where('payment.invoice_code IS NOT NULL');
    $ci->db->where('enroll.class_id', $class_id);
    $ci->db->where('payment.title', $title);
    $ci->db->where('payment.can_delete !=', 'trash');
    if ($boarding_system === 'yes' && !empty($residence_type) && $residence_type !== '0') {
        $ci->db->where('student.residence_type', $residence_type);
    }
    $row = $ci->db->get()->row();
    return $row && $row->total_actual ? floatval($row->total_actual) : 0.0;
}

// Render table
?>
<style>
#invoice_table th, #invoice_table td {
    border: 1px solid #000 !important;
    padding: 8px !important;
}
</style>
<div style="margin-bottom: 15px;">
  <button onclick="exportTableToExcel('invoice_table', 'Payment_Report')" class="btn btn-success">
    <i class="fa fa-file-excel-o"></i> Export to Excel
  </button>
</div>
<div class="table-responsive">
   <table class="table table-bordered" id="invoice_table" style="width:100%; border-collapse:collapse; border: 1px solid #000;">
    <thead>
      <tr>
        <th rowspan="2"><strong>CLASS</strong></th>
        <?php foreach ($billTitles as $title): ?>
          <th colspan="3" style="text-align:center;"><strong><?= htmlspecialchars(strtoupper($title)) ?></strong></th>
        <?php endforeach; ?>
        <th colspan="3" style="text-align:center;"><strong>TOTAL</strong></th>
      </tr>
      <tr>
        <?php foreach ($billTitles as $title): ?>
          <th style="text-align:right;"><strong>Expected</strong></th>
          <th style="text-align:right;"><strong>Actual</strong></th>
          <th style="text-align:right;"><strong>Balance</strong></th>
        <?php endforeach; ?>
        <th style="text-align:right;"><strong>Expected</strong></th>
        <th style="text-align:right;"><strong>Actual</strong></th>
        <th style="text-align:right;"><strong>Balance</strong></th>
      </tr>
    </thead>
     <tbody>
       <?php
       $grandExpected = array_fill_keys($billTitles, 0.0);
       $grandActual = array_fill_keys($billTitles, 0.0);
       $grandBalance = array_fill_keys($billTitles, 0.0);
       $grandRowExpected = 0.0;
       $grandRowActual = 0.0;
       $grandRowBalance = 0.0;
       foreach ($classes as $cls):
         $rowExpected = 0.0;
         $rowActual = 0.0;
         $rowBalance = 0.0;
         // Pre-calculate to check if class has any data
         foreach ($billTitles as $title):
             $exp = getExpectedForClassAndBill($cls['class_id'], $title, $start_date, $end_date, $boarding_system, isset($residence_type) ? $residence_type : '0');
             $act = getActualForClassAndBill($cls['class_id'], $title, $start_date, $end_date, $boarding_system, isset($residence_type) ? $residence_type : '0');
             $rowExpected += $exp;
             $rowActual += $act;
         endforeach;
         
         // Skip classes with zero amounts
         if ($rowExpected <= 0 && $rowActual <= 0) continue;
         
         // Reset for actual rendering
         $rowExpected = 0.0;
         $rowActual = 0.0;
         $rowBalance = 0.0;
       ?>
         <tr>
           <td><?= htmlspecialchars(getFullClassName($cls['class_id'])) ?></td>
           <?php foreach ($billTitles as $title):
               $exp = getExpectedForClassAndBill($cls['class_id'], $title, $start_date, $end_date, $boarding_system, isset($residence_type) ? $residence_type : '0');
               $act = getActualForClassAndBill($cls['class_id'], $title, $start_date, $end_date, $boarding_system, isset($residence_type) ? $residence_type : '0');
               $bal = $exp - $act;
               $rowExpected += $exp;
               $rowActual += $act;
               $rowBalance += $bal;
               $grandExpected[$title] += $exp;
               $grandActual[$title] += $act;
               $grandBalance[$title] += $bal;
           ?>
             <td style="text-align:right;"><?= number_format($exp, 2) ?></td>
             <td style="text-align:right;"><?= number_format($act, 2) ?></td>
             <td style="text-align:right;"><?= number_format($bal, 2) ?></td>
           <?php endforeach; ?>
           <td style="text-align:right; font-weight:bold;"><?= number_format($rowExpected, 2) ?></td>
           <td style="text-align:right; font-weight:bold;"><?= number_format($rowActual, 2) ?></td>
           <td style="text-align:right; font-weight:bold;"><?= number_format($rowBalance, 2) ?></td>
         </tr>
       <?php
         $grandRowExpected += $rowExpected;
         $grandRowActual += $rowActual;
         $grandRowBalance += $rowBalance;
       endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th style="text-align:right;">GRAND TOTAL</th>
        <?php foreach ($billTitles as $title): ?>
          <th style="text-align:right;"><?= number_format($grandExpected[$title], 2) ?></th>
          <th style="text-align:right;"><?= number_format($grandActual[$title], 2) ?></th>
          <th style="text-align:right;"><?= number_format($grandBalance[$title], 2) ?></th>
        <?php endforeach; ?>
        <th style="text-align:right;"><?= number_format($grandRowExpected, 2) ?></th>
        <th style="text-align:right;"><?= number_format($grandRowActual, 2) ?></th>
        <th style="text-align:right;"><?= number_format($grandRowBalance, 2) ?></th>
      </tr>
    </tfoot>
  </table>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/xlsx.full.min.js"></script>
<script>
function exportTableToExcel(tableID, filename) {
    try {
        var table = document.getElementById(tableID);
        if (!table) {
            alert('Table not found');
            return;
        }
        
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.table_to_sheet(table);
        
        // Set column widths
        var range = XLSX.utils.decode_range(ws['!ref']);
        var wscols = [{ wch: 20 }];
        for(var i = 1; i <= range.e.c; i++) {
            wscols.push({ wch: 15 });
        }
        ws['!cols'] = wscols;
        
        XLSX.utils.book_append_sheet(wb, ws, "Payment Report");
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch(e) {
        console.error('Export error:', e);
        alert('Error exporting to Excel: ' + e.message);
    }
}

</script>


