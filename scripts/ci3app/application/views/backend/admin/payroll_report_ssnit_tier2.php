<?php
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$monthYear = explode('-', $month);
$year = $monthYear[0];
$monthName = date('F', mktime(0, 0, 0, $monthYear[1], 1));

$this->db->where('year', $year);
$this->db->where('month', $monthName);
$payrollData = $this->db->get('pay_salary')->result_array();

// Get Tier 2 rate from first payroll record (if available), otherwise use current rate
$display_rate = null;
if (!empty($payrollData) && isset($payrollData[0]['rate_ssnit_tier2']) && $payrollData[0]['rate_ssnit_tier2'] !== null) {
    // Use saved rate from payroll records
    $display_rate = $payrollData[0]['rate_ssnit_tier2'];
} else {
    // Fallback to current rate for old records or when no data
    $display_rate = isset($statutory_rates['ssnit_tier2']['value']) ? $statutory_rates['ssnit_tier2']['value'] : 5.0;
}

// Get the first active Tier 2 provider
$tier2Provider = null;
if ($this->db->table_exists('pension_tier2_providers')) {
    $tier2Provider = $this->db->get_where('pension_tier2_providers', ['is_active' => 1])->row();
}
$tier2ProviderName = $tier2Provider ? $tier2Provider->provider_name : 'N/A';

$schoolName = get_settings('system_name');
$ssnitNumber = get_settings('ssnit_number');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Tier 2 Contribution Table</title>
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
      <div class="flex-1 text-center">
        <p class="text-xl font-bold text-gray-800 uppercase"><?= strtoupper($tier2ProviderName) ?></p>
        <p class="text-xl font-bold text-gray-800 uppercase">Tier 2 Contribution Report (<?php echo number_format($display_rate, 1); ?>%)</p>
        <p class="text-xl font-bold text-gray-800 uppercase">Year & Month: <?= strtoupper($year . ' ' . $monthName) ?></p>
      </div>
    </div>

    <div class="mb-4 no-print">
      <form method="GET" class="flex gap-2">
        <div class="relative" style="cursor: pointer;" onclick="this.querySelector('input').showPicker()">
          <input type="month" name="month" value="<?= $month ?>" class="border rounded px-3 py-2" style="cursor: pointer;">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
        <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Print</button>
        <button type="button" onclick="exportToExcel()" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">Export Excel</button>
      </form>
    </div>

    <div class="relative overflow-x-auto shadow-sm rounded-lg">
      <table id="reportTable" class="w-full text-sm text-left text-gray-700 border border-gray-300">
        <thead class="bg-blue-700 text-white uppercase text-xs">
          <tr>
            <th scope="col" class="px-3 py-2 border">S/N</th>
            <th scope="col" class="px-3 py-2 border">Tier 2 Member ID / SSNIT No.</th>
            <th scope="col" class="px-3 py-2 border">Last Name</th>
            <th scope="col" class="px-3 py-2 border">First Name</th>
            <th scope="col" class="px-3 py-2 border">Other Names</th>
            <th scope="col" class="px-3 py-2 border text-right">Basic Salary</th>
            <th scope="col" class="px-3 py-2 border text-right">Tier 2 Contribution (<?php echo number_format($display_rate, 1); ?>%)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sn = 1;
          $totalSalary = 0;
          $totalTier2 = 0;
          
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
            $otherNames = isset($nameParts[2]) ? implode(' ', array_slice($nameParts, 2)) : '';
            
            $ssnitNumber = $staffInfo->ssnit_id ?? 'N/A';
            $tier2MemberId = $staffInfo->tier2_member_id ?? 'N/A';
            $displayId = $tier2MemberId !== 'N/A' ? $tier2MemberId : $ssnitNumber;
            
            $basicSalary = floatval($row['basic_salary']);
            $tier2Contribution = floatval($row['tier2_contribution']);
            
            $totalSalary += $basicSalary;
            $totalTier2 += $tier2Contribution;
          ?>
          <tr class="bg-white hover:bg-gray-50 border-b">
            <td class="px-3 py-2 border"><?= $sn++ ?></td>
            <td class="px-3 py-2 border"><?= $displayId ?></td>
            <td class="px-3 py-2 border"><?= strtoupper($lastName) ?></td>
            <td class="px-3 py-2 border"><?= strtoupper($firstName) ?></td>
            <td class="px-3 py-2 border"><?= strtoupper($otherNames) ?></td>
            <td class="px-3 py-2 border text-right"><?= number_format($basicSalary, 2) ?></td>
            <td class="px-3 py-2 border text-right"><?= number_format($tier2Contribution, 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="bg-gray-100 font-bold">
            <td colspan="5" class="px-3 py-2 border text-right">TOTAL:</td>
            <td class="px-3 py-2 border text-right"><?= number_format($totalSalary, 2) ?></td>
            <td class="px-3 py-2 border text-right"><?= number_format($totalTier2, 2) ?></td>
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
      var tier2Rate = '<?php echo number_format($display_rate, 1); ?>';
      var tier2ProviderName = '<?= strtoupper($tier2ProviderName) ?>';

      // Clone and prepare table
      var tableClone = table.cloneNode(true);
      tableClone.removeAttribute('id');

      // Uppercase header cells
      var ths = tableClone.querySelectorAll('thead th');
      ths.forEach(function(th){ th.textContent = (th.textContent || '').toUpperCase(); });

      // Add fixed layout and borders via attributes/styles Excel understands
      var tableHtml = tableClone.outerHTML;
      tableHtml = tableHtml.replace('<table', '<table border="1" style="border-collapse:collapse; table-layout:fixed; width:100%"');

      // Add column widths
      var colgroup = '\n        <colgroup>\n' +
        '          <col style="width:60px">\n' + // S/N
        '          <col style="width:200px">\n' + // Tier 2 Member ID / SSNIT No.
        '          <col style="width:160px">\n' + // Last Name
        '          <col style="width:160px">\n' + // First Name
        '          <col style="width:200px">\n' + // Other Names
        '          <col style="width:140px">\n' + // Basic Salary
        '          <col style="width:180px">\n' + // Tier 2 Contribution
        '        </colgroup>';
      
      // Create header with tier 2 provider name (similar to Tier 1)
      const headerInner = `
        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <td style="border:none; text-align:center; vertical-align:middle">
              <div style="font-weight:bold; text-transform:uppercase; font-size:16px;">${tier2ProviderName}</div>
              <div style="font-weight:bold; text-transform:uppercase; font-size:16px;">Tier 2 Contribution Report (${tier2Rate}%)</div>
              <div style="font-weight:bold; text-transform:uppercase;">Year & Month: ${(year + ' ' + monthName).toUpperCase()}</div>
            </td>
          </tr>
        </table>`;

      const headerRow = `<tr><td colspan="7" style="border:none; text-align:center; padding:6px;">${headerInner}</td></tr>`;

      tableHtml = tableHtml.replace('>', '>' + colgroup + headerRow);

      // Right-align numeric columns (6 and 7) via inline styles on TD
      tableHtml = tableHtml.replace(/<td([^>]*)class=\"([^\"]*)text-right([^\"]*)\"/g, '<td$1class="$2$3" style="text-align:right"');

      var styles = '\n        <style>\n' +
        '          table, th, td { border:1px solid #000; }\n' +
        '          th, td { padding:6px; vertical-align:middle; }\n' +
        '          thead th { font-weight:bold; }\n' +
        '        </style>';

      var html = '\n        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">\n' +
        '          <head>\n' +
        '            <meta charset="utf-8"/>\n' +
        styles +
        '          </head>\n' +
        '          <body>\n' +
        tableHtml +
        '          </body>\n' +
        '        </html>';

      var fileName = 'tier2_contribution_' + month + '.xls';
      var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
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


