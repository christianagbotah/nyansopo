

 <?php     

  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;                                       
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
  
  // Get school information
  $system_name = $this->db->get_where('settings', array('type'=>'system_name'))->row()->description;
  $address = $this->db->get_where('settings', array('type'=>'address'))->row()->description;

  ?>
  
  <!-- Report Header -->
  <?php
    // Build filter text - show exact filter selected
    $filter_text = 'ALL STUDENTS';
    
    if(isset($category) && $category == 1 && isset($class_id) && $class_id > 0) {
        // Filter by class - show full class name directly using the helper method
        $filter_text = strtoupper($this->crud_model->getFullClassName($class_id));
    }
    
    if(isset($student) && $student > 0) {
        // Filter by specific student - show full student name
        $student_info = $this->crud_model->getStudentInfoById($student);
        if($student_info) {
            $filter_text = strtoupper($student_info->name);
        }
    }
    
    // Add residence type if boarding system
    $residence_suffix = '';
    if(isset($residence_type) && $residence_type != 0) {
        $residence_suffix = ' - ' . strtoupper($residence_type);
    }
    
    $caption = 'RECEIVABLES GENERATED FOR ' . $filter_text . $residence_suffix;
  ?>
  <style>
    @media print {
        #export_table_header { display: block !important; }
        #export_table_header img { width: 60px !important; }
        #export_table_header h3 { font-size: 12px !important; }
        #export_table_header pre { font-size: 8px !important; }
        #export_table_header p { font-size: 8px !important; }
    }
  </style>
  
  <table id="export_table_header" style="width:100%; border-collapse:collapse; margin-bottom: 5px;">
    <tbody>
        <tr>
            <td width="60%" style="vertical-align:top; padding:5px;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="width:70px; padding: 3px;">
                            <img src="<?php echo base_url(); ?>uploads/school_logo.png" width="60px">
                        </td>
                        <td style="vertical-align:top; padding: 3px;">
                            <h3 style="font-weight: bold; font-size: 16px; letter-spacing: 1px; margin: 0 0 5px 0 !important;"><?php echo $system_name;?></h3>
                            <pre style="margin: 0 !important; padding: 0 !important; white-space: pre-wrap !important; font-family: inherit !important; font-size: 12px !important; line-height: 1.3 !important;"><?php echo trim($address);?></pre>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="40%" style="text-align:right; vertical-align:top; padding:5px;">
                <div style="text-align:right !important; margin: 0;">
                    <strong style="font-size: 12px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important; font-weight: bold !important;">ACCOUNTS RECEIVABLE REPORT</strong>
                    <strong style="font-size: 10px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important; margin-top: 5px !important;"><strong>Term / Year:</strong> <?php echo $term . ' / ' . $year; ?></strong>
                    <strong style="font-size: 9px !important; color: #000 !important; display: block !important; white-space: normal !important; word-wrap: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important; text-align: right !important; margin-top: 3px !important;"><?php echo $caption; ?></strong>
                </div>
            </td>
        </tr>
    </tbody>
  </table>
  
  <table class="table" id="export_table" style="width:100%; border-collapse:collapse;">
    <thead style="padding: 30px;">
        <tr style="height: 30px;">
            <th style="text-align: left"><strong>Student ID</strong></th>
            <th style="text-align: left"><strong>Student Name</strong></th>
            <th style="text-align: left"><strong>Class</strong></th>
            <th style="text-align: right"><strong>Arrears</strong></th>
            <th style="text-align: right"><strong>Term's Bill</strong></th>
            <th style="text-align: right"><strong>Total Owe</strong></th>
            <th style="text-align: right"><strong>Paid</strong></th>
            <th style="text-align: right"><strong>Balance Due</strong></th>
            
        </tr>
    </thead>
    <tbody>

      <?php
  
    if(count($report_data) > 0){

      $sn = 1;
      //report_data has arrived
        $grandTotalArrears = 0;
        $grandTotalTermBill = 0;
        $grandTotalAmount = 0;
        $grandTotalPayment = 0;
        $grandTotalBalanceDue = 0;

        foreach($report_data as $student):
              $student_data = $this->crud_model->getStudentInfoById($student['student_id']);
              $student_name = $student_data->name;
              $student_code = $student_data->student_code;
              $student_code = $student_data->student_code;
              $student_class = getStudentCurrentClassByStudentId($student['student_id']);
              $student_gender = $student_data->sex;
              $student_image = $this->crud_model->get_image_url('student', $student['student_id'], $student_gender);

              //parent's data
              /*$parent_data = getStudentParentByStudentId($student['student_id']);
              $parent_name = strtoupper($parent_data->name);
              $parent_contact = $parent_data->phone;*/

              // Calculate balance first to check if student should be displayed
              if($all_customize == 0) { //all dates
                $term = get_settings('running_term');
                $year = get_settings('running_year');
                $arrears_array = $this->financial_report_model->getArrearsByStudentId($year, $student['student_id']);
                $thisTermBill = $this->financial_report_model->getThisTermBillByStudentId($term, $year, $student['student_id']);
              } else { //customized dates
                $arrears_array = $this->financial_report_model->getArrearsByStudentId($year, $student['student_id']);
                $thisTermBill = $this->financial_report_model->getThisTermBillByStudentId($term, $year, $student['student_id']);
              }

              // Calculate totals to check if student has any balance
              $totalArrears = 0;
              $totalArrearsTillSelectedTerm = 0;
              foreach($arrears_array as $ar) {
                $totalArrearsTillSelectedTerm += $ar['due'];
                if($ar['term'] == $term && $ar['year'] == $year) {
                  continue;
                }
                $totalArrears += $ar['due'];
              }
              $totalOwe = $totalArrears + $thisTermBill;
              $balanceDue = $totalArrearsTillSelectedTerm;

              // Only display student if they have a balance > 0
              if ($balanceDue <= 0) {
                continue; // Skip this student
              }

            ?>

            <tr style="border-bottom: 1px solid #000">
              <td style="padding: 5px;">                
                    <?=$student_code; ?>
              </td>
              <td style="padding: 5px;">                
                    <?=$student_name; ?>
              </td>
              <td style="padding: 5px;"><?=$student_class; ?></td>

                <?php
                  // Recalculate for display (already calculated above for filtering)
                  if($all_customize == 0) { //all dates
                    $term = get_settings('running_term');
                    $year = get_settings('running_year');
                    $arrears_array = $this->financial_report_model->getArrearsByStudentId($year, $student['student_id']);
                    $thisTermBill = $this->financial_report_model->getThisTermBillByStudentId($term, $year, $student['student_id']);
                  } else { //customized dates
                    $arrears_array = $this->financial_report_model->getArrearsByStudentId($year, $student['student_id']);
                    $thisTermBill = $this->financial_report_model->getThisTermBillByStudentId($term, $year, $student['student_id']);
                  }

                  //working out the arrears
                  $totalArrears = 0;
                  $totalArrearsTillSelectedTerm = 0;
                  $totalOwe = 0;
                  $balanceDue = 0;

                  foreach($arrears_array as $ar) {
                    $totalArrearsTillSelectedTerm += $ar['due'];
                    if($ar['term'] == $term && $ar['year'] == $year) {
                      continue;
                    }
                    $totalArrears += $ar['due'];
                  }

                  /*Totals*/
                  $totalOwe = $totalArrears + $thisTermBill;
                  $balanceDue = $totalArrearsTillSelectedTerm;
                  $amountPaid = $totalOwe - $balanceDue;

                  /*Grand Totals*/
                  $grandTotalArrears += $totalArrears;
                  $grandTotalTermBill += $thisTermBill;
                  $grandTotalAmount += $totalOwe;
                  $grandTotalPayment += $amountPaid;
                  $grandTotalBalanceDue += $balanceDue;

                  ?>            
                

              <td align="right" style="vertical-align: bottom"><strong><?=number_format($totalArrears, 2, '.', ','); ?></strong></td>
              <td align="right" style="vertical-align: bottom"><strong><?=number_format($thisTermBill, 2, '.', ','); ?></strong></td>
              <td align="right" style="vertical-align: bottom"><strong><?=number_format($totalOwe, 2, '.', ','); ?></strong></td>
              <td align="right" style="vertical-align: bottom"><strong><?=number_format($amountPaid, 2, '.', ','); ?></strong></td>
              <td align="right" style="vertical-align: bottom"><strong><?=number_format($balanceDue, 2, '.', ','); ?></strong></td>

            </tr>

           <?php

           $sn++;
        endforeach;
        

    }
          ?>

    </tbody>

    <?php

    if(count($report_data) > 0){ ?>
    <tfoot>
      <tr>
         <td></td>
         <td></td>
         <td align="right"><strong>TOTAL</strong></td>
         <td align="right" style="border-top: 1px solid #000; border-style: double none;"><strong><?=numfmt_format_currency($fmt, $grandTotalArrears, $currency); ?></strong></td>
         <td align="right" style="border-top: 1px solid #000; border-style: double none;"><strong><?=numfmt_format_currency($fmt, $grandTotalTermBill, $currency); ?></strong></td>
         <td align="right" style="border-top: 1px solid #000; border-style: double none;"><strong><?=numfmt_format_currency($fmt, $grandTotalAmount, $currency); ?></strong></td>
         <td align="right" style="border-top: 1px solid #000; border-style: double none;"><strong><?=numfmt_format_currency($fmt, $grandTotalPayment, $currency); ?></strong></td>
         <td align="right" style="border-top: 1px solid #000; border-style: double none;"><strong><?=numfmt_format_currency($fmt, $grandTotalBalanceDue, $currency); ?></strong></td>
      </tr>
    </tfoot>

    <?php
      }

    ?>
  </table>


<script>
 

  $('#export_table').DataTable(
      {
          paging: false,
          layout: {
            topStart: {
                buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
            }
          },
          columnDefs: [
            { targets: '_all', orderable: false }
          ]
      }
  );
      
	
</script>

