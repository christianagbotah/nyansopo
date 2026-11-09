

 <?php     

  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;                                      
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
  

    if(count($report_data) > 0){

      $sn = 1;
      //report_data has arrived
        $grandInvoiceTotal = 0;
        $grandInvoiceTotalDue = 0;

        $grandTotalDayStudentPayment = 0;
        $grandTotalBoardingStudentPayment = 0;

        foreach($report_data as $student):

              $totalDayStudentPayment = 0;
              $totalBoardingStudentPayment = 0;

              $student_data = $this->crud_model->getStudentInfoById($student['student_id']);
              $student_name = $student_data->name;
              $student_code = $student_data->student_code;
              $student_class = getStudentCurrentClassByStudentId($student['student_id']);
              $student_gender = $student_data->sex;
              $student_image = $this->crud_model->get_image_url('student', $student['student_id'], $student_gender);

              
              // Get payment methods used by this student for FCT
              $paymentMethodsUsed = $this->financial_report_model->getPaymentMethodsUsedByStudentFct($start_date, $end_date, $student['student_id'], isset($residence_type) ? $residence_type : '0', isset($payment_method) ? $payment_method : '0');
              
              // Format payment methods as comma-separated string
              $paymentMethodNames = array();
              foreach($paymentMethodsUsed as $pm) {
                  $paymentMethodNames[] = get_payment_method_name($pm['payment_method']);
              }
              $paymentMethodDisplay = implode(', ', $paymentMethodNames);

              if($boarding_system == 'yes') { /*school is running boarding system*/

                if($residence_type == 0) {
                  /*all selected*/
                  $totalDayStudentPayment = $this->financial_report_model->getfctPaidByStudentIdAndResidenceType($student['student_id'], $start_date, $end_date, 'Day');
                  $totalBoardingStudentPayment = $this->financial_report_model->getfctPaidByStudentIdAndResidenceType($student['student_id'], $start_date, $end_date, 'Boarding');

                  $amountPaid = $totalDayStudentPayment + $totalBoardingStudentPayment;

                } else {

                  $amountPaid = $this->financial_report_model->getfctPaidByStudentIdAndResidenceType($student['student_id'], $start_date, $end_date, $residence_type);

                }

                
              } else { /*school is not running boarding system*/

                $amountPaid = $this->financial_report_model->getfctPaidByStudentId($student['student_id'], $start_date, $end_date);

              }

              $amountOwe = $this->financial_report_model->getSumfctOwingByStudentId($student['student_id'], $start_date, $end_date);

            ?>

            <tr style="border-bottom: 1px solid #ccc">
              <td><?=$sn;?></td>
              <td>
                <?= $student_code;?>
                <!-- <img src="<?php //echo $student_image;?>" class="img-circle" style="width: 50%;" /> -->
              </td>
              <td width="280">
                <?=$student_name; ?>
              </td>  
              <td>
                <?=$student_class; ?>
              </td>
              <td>
                <?=$paymentMethodDisplay;?>
              </td>                   
              <td align="right">
                 <?=number_format($amountPaid, 2, '.', ',');?>
              </td>

              <td align="right">
                 <?=number_format($amountOwe, 2, '.', ',');?>
              </td>

            </tr>

           <?php
           $grandInvoiceTotal += $amountPaid;
           $grandInvoiceTotalDue += $amountOwe;

           $grandTotalDayStudentPayment += $totalDayStudentPayment;
           $grandTotalBoardingStudentPayment += $totalBoardingStudentPayment;
           $sn++;
        endforeach;
        
        ?>
        <tr width="150">
           <td align="right" style="border-top: 1px solid #000; border-style: double none;" colspan="5"><strong>TOTAL (<?=$currency;?>)</strong></td>
           <td align="right" style="border-top: 1px solid #000; border-style: double none;"><?=number_format($grandInvoiceTotal, 2, '.', ',');?>
            </td>
            <td align="right" style="border-top: 1px solid #000; border-style: double none;"><?=number_format($grandInvoiceTotalDue, 2, '.', ',');?></td>
        </tr>

        <?php

    } else {
      ?>
      <tr>
        <td colspan="7" class="text-center"><span class="text-danger">No record found!</span></td>
      </tr>
      <?php

    }
          ?>
