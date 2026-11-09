<?php
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$monthYear = explode('-', $month);
$year = $monthYear[0];
$monthName = date('F', mktime(0, 0, 0, $monthYear[1], 1));

$this->db->where('year', $year);
$this->db->where('month', $monthName);
$payrollData = $this->db->get('pay_salary')->result_array();

// Get SSNIT rate from first payroll record (if available), otherwise use current rate
$display_rate = null;
if (!empty($payrollData) && isset($payrollData[0]['rate_ssnit_tier1_employer']) && $payrollData[0]['rate_ssnit_tier1_employer'] !== null) {
    // Use saved rate from payroll records
    $display_rate = $payrollData[0]['rate_ssnit_tier1_employer'];
} else {
    // Fallback to current rate for old records or when no data
    $display_rate = isset($statutory_rates['ssnit_tier1_employer']['value']) ? $statutory_rates['ssnit_tier1_employer']['value'] : 13.5;
}

$schoolName = get_settings('system_name');
$ssnitNumber = get_settings('ssnit_number');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SSNIT Contribution Report</title>
  <!-- <link href="<?php echo base_url(); ?>assets/cdn/css/flowbite.min.css" rel="stylesheet" />
  <script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script> -->
  <link rel="stylesheet" href="<?=base_url('node_modules/flowbite/dist/flowbite.min.css')?>">
  <script src="<?php echo base_url('assets/tailwindcss/tailwindcss.js');?>" type="text/javascript"></script>
  <style>
    body { font-family: 'Times New Roman', Times, serif; }
    @media print {
      body { background: white !important; padding: 0 !important; font-family: 'Times New Roman', Times, serif; }
      .no-print { display: none !important; }
      .print-container { box-shadow: none !important; border-radius: 0 !important; margin: 0 !important; max-width: 100% !important; }
    }
  </style>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-7xl mx-auto bg-white shadow-md rounded-xl p-6 print-container">
    <div class="flex items-start mb-6">
      <div class="mr-6">
        <img src="<?php echo base_url();?>uploads/ssnit_logo.png" alt="SSNIT Logo" style="width: 120px; height: auto;">
      </div>
      <div class="flex-1 text-center">
        <p class="text-xl font-bold text-gray-800 uppercase">Social Security & National Insurance Trust Contribution Report</p>
        <p class="text-xl font-bold text-gray-800 uppercase">Establishment Name: <?= strtoupper($schoolName) ?></p>
        <p class="text-xl font-bold text-gray-800 uppercase">Establishment Registration Number: <?= $ssnitNumber ?></p>
        <p class="text-xl font-bold text-gray-800 uppercase">Year & Month: <?= strtoupper($year . ' ' . $monthName) ?></p>
      </div>
    </div>

    <div class="mb-4 no-print">
      <form method="GET" class="flex gap-2">
        <div onclick="this.querySelector('input').showPicker()" style="cursor: pointer;">
          <input type="month" name="month" value="<?= $month ?>" class="border rounded px-3 py-2" style="cursor: pointer;">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
        <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Print</button>
        <!-- <button type="button" onclick="exportToExcel()" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">Export Excel</button> -->
      </form>
    </div>

    <div class="relative overflow-x-auto shadow-sm rounded-lg">
      <table id="reportTable" class="w-full text-sm text-left text-gray-700 border border-gray-300">
        <thead class="text-xs uppercase text-gray-700">
          <tr>
            <th scope="col" class="px-3 py-2 border" rowspan="2">S/N</th>
            <th scope="col" class="px-3 py-2 border" rowspan="2">Ghana Card</th>
            <th scope="col" class="px-3 py-2 border text-center" colspan="6">LAST KNOWN SALARIES AS AT <?= $year - 1 . ' ' . $monthName ?></th>
            <th scope="col" class="px-3 py-2 border text-right">FILL IN PERIODS</th>  
        </tr>
          <tr>
            <th scope="col" class="px-3 py-2 border">SSNIT No.</th>
            <th scope="col" class="px-3 py-2 border">Last Name</th>
            <th scope="col" class="px-3 py-2 border">First Name</th>
            <th scope="col" class="px-3 py-2 border">Option</th>
            <th scope="col" class="px-3 py-2 border">Hazd.</th>
            <th scope="col" class="px-3 py-2 border text-right">Salary</th>
            <th scope="col" class="px-3 py-2 border text-right">SSNIT (<?php echo number_format($display_rate, 1); ?>%)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sn = 1;
          $totalSalary = 0;
          $totalSsnit = 0;
          
          // Task 17.1: Optimize staff selection query with single JOIN
          // Fetch all staff info in one query instead of N+1 queries
          $staff_info_map = $this->crud_model->get_staff_info_batch($payrollData);
          
          foreach($payrollData as $row): 
            $staff_key = $row['employment_category'] . '_' . $row['employee_code'];
            $staffInfo = isset($staff_info_map[$staff_key]) ? $staff_info_map[$staff_key] : null;
            
            if (!$staffInfo) {
                // Fallback to original method if staff not found
                $staffInfo = $this->crud_model->getStaffInfo($row['employment_category'], $row['employee_code']);
            }
            
            $nameParts = explode(' ', $staffInfo->name);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
            
            $ghanaCard = $staffInfo->ghana_card_id ?? 'N/A';
            $ssnitNumber = $staffInfo->ssnit_id ?? 'N/A';
            
            $totalSalary += $row['basic_salary'];
            $totalSsnit += $row['ssnit'];
          ?>
          <tr class="bg-white hover:bg-gray-50 border-b">
            <td class="px-3 py-2 border"><?= $sn++ ?></td>
            <td class="px-3 py-2 border"><?= $ghanaCard ?></td>
            <td class="px-3 py-2 border"><?= $ssnitNumber ?></td>
            <td class="px-3 py-2 border"><?= strtoupper($lastName) ?></td>
            <td class="px-3 py-2 border"><?= strtoupper($firstName) ?></td>
            <td class="px-3 py-2 border">ACT 766</td>
            <td class="px-3 py-2 border">NO</td>
            <td class="px-3 py-2 border text-right"><?= number_format($row['basic_salary'], 2) ?></td>
            <td class="px-3 py-2 border text-right"><?= number_format($row['ssnit'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="bg-gray-100 font-bold">
            <td colspan="7" class="px-3 py-2 border text-right">TOTAL:</td>
            <td class="px-3 py-2 border text-right"><?= number_format($totalSalary, 2) ?></td>
            <td class="px-3 py-2 border text-right"><?= number_format($totalSsnit, 2) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- <script src="<?php echo base_url(); ?>assets/cdn/js/flowbite.min.js"></script> -->
  <script src="<?=base_url('node_modules/flowbite/dist/flowbite.min.js')?>"></script>
  <script src="<?php echo base_url('assets/sheetjs-master/xlsx.full.min.js')?>"></script>
  <script>
    function exportToExcel() {
      var table = document.getElementById('reportTable');
      if (!table) return;

      var month = '<?= $month ?>';
      var logoUrl = '<?= base_url(); ?>uploads/ssnit_logo.png';
      var schoolName = '<?= strtoupper($schoolName) ?>';
      var ssnitNumber = '<?= $ssnitNumber ?>';
      var year = '<?= $year ?>';
      var monthName = '<?= $monthName ?>';
      var ssnitRate = '<?php echo number_format($display_rate, 1); ?>';

      const colgroup = `
        <colgroup>
          <col style="width:50px">
          <col style="width:160px">
          <col style="width:140px">
          <col style="width:160px">
          <col style="width:160px">
          <col style="width:90px">
          <col style="width:70px">
          <col style="width:120px">
          <col style="width:120px">
        </colgroup>`;

      var tableClone = table.cloneNode(true);
      tableClone.removeAttribute('id');
      var tableHtml = tableClone.outerHTML;
      tableHtml = tableHtml.replace('<table', '<table border="1" style="border-collapse:collapse; table-layout:fixed; width:100%"');
      tableHtml = tableHtml.replace('>', '>' + colgroup);

      const headerInner = `
        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <td style="width:100px; border:none; text-align:left; vertical-align:middle">
              <img src="${logoUrl}" alt="SSNIT Logo" style="height:60px"/>
            </td>
            <td style="border:none; text-align:center; vertical-align:middle">
              <div style="font-weight:bold; text-transform:uppercase; font-size:16px;">Social Security & National Insurance Trust Contribution Report</div>
              <div style="font-weight:bold; text-transform:uppercase;">Establishment Name: ${schoolName}</div>
              <div style="font-weight:bold; text-transform:uppercase;">Establishment Registration Number: ${ssnitNumber}</div>
              <div style="font-weight:bold; text-transform:uppercase;">Year & Month: ${(year + ' ' + monthName).toUpperCase()}</div>
            </td>
          </tr>
        </table>`;

      const headerRow = `<tr><td colspan="9" style="border:none; text-align:center; padding:6px;">${headerInner}</td></tr>`;

      tableHtml = tableHtml.replace(colgroup, colgroup + headerRow);

      const styles = `
        <style>
          table, th, td { border: 1px solid #000; }
          th, td { padding: 6px; vertical-align: middle; }
          td:nth-child(8), th:nth-child(8), td:nth-child(9), th:nth-child(9) { text-align: right; }
        </style>`;

      const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
          <head>
            <meta charset="utf-8"/>
            ${styles}
          </head>
          <body>
            ${tableHtml}
          </body>
        </html>`;

      var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
      var fileName = 'ssnit_contribution_' + month + '.xls';
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      setTimeout(function(){
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }, 0);
    }
  </script>
</body>
</html>
