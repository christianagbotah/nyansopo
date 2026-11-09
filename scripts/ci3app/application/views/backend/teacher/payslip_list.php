<style type="text/css">
  .dt-buttons {
    margin-left: 10px !important;
    margin-top: 2px !important;
  }

  /*@media print {
      .no-print { display: none !important; }
      .print-break { page-break-after: always; }
      body { font-size: 12px; }
      .payslip-container { 
          width: 100% !important; 
          max-width: none !important;
          margin: 0 !important;
          padding: 20px !important;
          box-shadow: none !important;
      }
  }*/
  
</style>

<!-- Payslip Container -->
<div class="payslip-container bg-white shadow-2xl p-4 mt-10 rounded-lg overflow-x-scroll" id="printableDiv">
  
  <table class="text-xl text-left text-gray-600 dark:text-gray-400 datatable" id="payrollTable">
    <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
      <tr>
          <th scope="col" class="px-4 py-3">reference</th>
          <th scope="col" class="px-4 py-3">ID No</th>
          <th scope="col" class="px-4 py-3">Name</th>
          <th scope="col" class="px-4 py-3 whitespace-nowrap">Account #</th>
          <th scope="col" class="px-4 py-3">month</th>
          <th scope="col" class="px-4 py-3">year</th>
          <th scope="col" class="px-4 py-3 text-right whitespace-nowrap" style="text-align: right !important;">basic salary</th>
          <th scope="col" class="px-4 py-3 text-right whitespace-nowrap" style="text-align: right !important;">total allowances</th>
          <th scope="col" class="px-4 py-3 text-right whitespace-nowrap" style="text-align: right !important;">gross salary</th>
          <th scope="col" class="px-4 py-3 text-right whitespace-nowrap" style="text-align: right !important;">total deductions</th>
          <th scope="col" class="px-4 py-3 text-right whitespace-nowrap" style="text-align: right !important;">net salary</th>
          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">action</th>

      </tr>
    </thead>
    <tbody>

      <?php
        // Task 17.1: Optimize staff selection query with JOIN - Load all staff info in single batch query
        $staff_info_map = $this->crud_model->get_staff_info_batch($staffPayrollData);
        
        foreach($staffPayrollData as $data):

          /*Staff information - Retrieved from batch query to avoid N+1 problem*/
          // FIX: Convert 'administrator' to 'admin' for proper key lookup (model uses 'admin_' format)
          $employment_category_key = ($data['employment_category'] === 'administrator') ? 'admin' : $data['employment_category'];
          $staff_key = $employment_category_key . '_' . $data['employee_code'];
          $staffRow = isset($staff_info_map[$staff_key]) ? $staff_info_map[$staff_key] : null;
          
          // Handle case where staff info not found
          if ($staffRow === null) {
            $staffName = 'Unknown Staff';
            $staffAccountNumber = 'N/A';
          } else {
            $staffName = ucwords(strtolower($staffRow->name));
            $staffAccountNumber = $staffRow->account_number;
          }
      ?>

      <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
        <td class="px-4 py-3"><a href="<?= site_url('admin/payslip_preview/'.$data['employee_code'].'/'.$data['month'].'/'.$data['year'].'/'.$data['employment_category']) ?>" target="_blank" class="py-2 px-3 font-medium"><?= $data['reference'];?></a></td>
        <td class="px-4 py-3 whitespace-nowrap"><?= $data['employee_code'];?></td>
        <td class="px-4 py-3 whitespace-nowrap"><?= $staffName;?></td>
        <td class="px-4 py-3"><?= $staffAccountNumber;?></td>
        <td class="px-4 py-3"><?= $data['month'];?></td>
        <td class="px-4 py-3"><?= $data['year'];?></td>
        <td class="px-4 py-3 text-right"><?= number_format($data['basic_salary'], 2, '.', ',');?></td>
        <td class="px-4 py-3 text-right"><?= number_format($data['total_allowances'], 2, '.', ',');?></td>
        <td class="px-4 py-3 text-right"><?= number_format($data['gross_salary'], 2, '.', ',');?></td>
        <td class="px-4 py-3 text-right"><?= number_format($data['total_deductions'], 2, '.', ',');?></td>
        <td class="px-4 py-3 text-right"><?= number_format($data['net_salary'], 2, '.', ',');?></td>
        <td class="px-4 py-3 text-center">
           <a href="<?= site_url('admin/payslip_preview/'.$data['employee_code'].'/'.$data['month'].'/'.$data['year'].'/'.$data['employment_category']) ?>" target="_blank" class="py-2 px-3 font-medium text-center text-white rounded-lg bg-blue-600 hover:bg-blue-700 focus:ring-4 font-semibold focus:outline-none focus:ring-blue-300">Preview</a>
        </td>
      </tr>

      <?php
        endforeach;
      ?>
    </tbody>
  </table>
</div>

<script>
  $(function() {
    $('#payrollTable').dataTable({

      layout: {
                topCenter: {
                    buttons: [
                        'colvis',
                        {
                            extend: 'copyHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        /*{
                            extend: 'pdfHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },*/
                        
                    ]
                }
            }
    });

  })

  document.getElementById('downloadPdfButton').addEventListener('click', function() {
      const content = document.getElementById('printableDiv');

      html2canvas(content).then(canvas => {
          const imgData = canvas.toDataURL('image/png');
          const { jsPDF } = window.jspdf;
          const pdf = new jsPDF({
              orientation: 'portrait',
              unit: 'px',
              format: [canvas.width, canvas.height] // Set PDF dimensions to match canvas
          });

          pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
          pdf.save('<?= ucwords($system_name) ?> - Employee Payslip Table.pdf');
      });
  });


</script>