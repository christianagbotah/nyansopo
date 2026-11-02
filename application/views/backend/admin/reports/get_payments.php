

 <?php     

  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

  

    if(count($report_data) > 0) {

      $sn = 1;
      //report_data has arrived
        $grandInvoiceTotal = 0;
        $grandInvoiceTotalDue = 0;

        foreach($report_data as $student):

              $student_data = $this->crud_model->getStudentInfoById($student['student_id']);
              $student_name = $student_data->name;
              $student_code = $student_data->student_code;
              $student_class = getStudentCurrentClassByStudentId($student['student_id']);
              $student_gender = $student_data->sex;
              $student_image = $this->crud_model->get_image_url('student', $student['student_id'], $student_gender);

              //parent's data
              $parent_data = getStudentParentByStudentId($student['student_id']);
              $parent_name = $parent_data ? strtoupper($parent_data->name) : 'No Parent Assigned';
              $parent_contact = $parent_data ? $parent_data->phone : 'N/A';

              // Get total amount paid for this student
              // If residence_type filter is applied, only count payments for that residence type
              if(isset($residence_type) && $residence_type != '0') {
                  $totalAmountPaid = $this->financial_report_model->getTotalAmountPaidForBilledInvoiceByResidenceType($start_date, $end_date, $residence_type, '0', $student['student_id'], $bill_item, isset($payment_method) ? $payment_method : '0');
              } else {
                  $totalAmountPaid = $this->financial_report_model->getTotalAmountPaidForBilledInvoiceByStudentId($start_date, $end_date, $student['student_id'], $bill_item, isset($payment_method) ? $payment_method : '0');
              }
              
              // Get payment methods used by this student
              $paymentMethodsUsed = $this->financial_report_model->getPaymentMethodsUsedByStudent($start_date, $end_date, $student['student_id'], $bill_item, isset($payment_method) ? $payment_method : '0', isset($residence_type) ? $residence_type : '0');
              
              // Format payment methods as comma-separated string
              $paymentMethodNames = array();
              foreach($paymentMethodsUsed as $pm) {
                  $paymentMethodNames[] = get_payment_method_name($pm['payment_method']);
              }
              $paymentMethodDisplay = implode(', ', $paymentMethodNames);
              
              $totalOwingByStudent = $this->financial_report_model->getTotalOwingByStudentId($student['student_id']);
              
              ?>
              <tr style="border-bottom: 1px solid #ccc;">
                <td><?= $sn;?></td>
                <td>
                  <?= $student_code;?>
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
                  <?=number_format($totalAmountPaid, 2, '.', ',');?>
                </td>
                <td align="right">
                  <?=number_format($totalOwingByStudent, 2, '.', ','); ?>
                </td>
              </tr>
              <?php

            $grandInvoiceTotal += $totalAmountPaid;
            $grandInvoiceTotalDue += $totalOwingByStudent;
            $sn++;
        endforeach;
        
        ?>
        <tr>
           <td align="right" colspan="5" style="border-top: 1px solid #000; border-style: double none;"><strong>TOTAL (<?=$currency;?>)</strong></td>
           <td align="right" style="border-top: 1px solid #000; border-style: double none;">
              <strong><?=number_format($grandInvoiceTotal, 2, '.', ',');?></strong>
            </td>
            <td align="right" style="border-top: 1px solid #000; border-style: double none;">
              <strong><?=number_format($grandInvoiceTotalDue, 2, '.', ','); ?></strong>
            </td>
        </tr>
        <tr style="padding-top: 30px"><td></td></tr>

        <!-- Summary -->
        <tr><td colspan="7" align="left"><strong style="letter-spacing: 6px; font-size: 14px;">SUMMARY<strong></td></tr>

          <?php

            if($boarding_system == 'yes') { /*school is running boarding system*/

              if($residence_type == '0') {
                /*all selected*/
                $totalDayStudentPayment = $this->financial_report_model->getTotalAmountPaidForBilledInvoiceByResidenceType($start_date, $end_date, 'Day', $class_id, $student_id, $bill_item, isset($payment_method) ? $payment_method : '0');
                $totalBoardingStudentPayment = $this->financial_report_model->getTotalAmountPaidForBilledInvoiceByResidenceType($start_date, $end_date, 'Boarding', $class_id, $student_id, $bill_item, isset($payment_method) ? $payment_method : '0');

                ?>
                <tr>
                  <td colspan="5" align="right">DAY STUDENTS TOTAL</td>
                    <td align="right"><strong><?=numfmt_format_currency($fmt, $totalDayStudentPayment, $currency)?><strong></td>
                      <td></td>
                </tr>
                <tr>
                  <td colspan="5" align="right">BOARDING STUDENTS TOTAL</td>
                    <td align="right"><strong><?=numfmt_format_currency($fmt, $totalBoardingStudentPayment, $currency)?><strong></td>
                      <td></td>
                </tr>
                <?php

              } else {

                $totalAmountPaid = $this->financial_report_model->getTotalAmountPaidForBilledInvoiceByResidenceType($start_date, $end_date, $residence_type, $class_id, $student_id, $bill_item, isset($payment_method) ? $payment_method : '0');

                ?>

                <tr>
                <td colspan="5" align="right"><?=strtoupper(strtolower($residence_type));?> STUDENTS TOTAL</td>
                  <td align="right"><strong><?=numfmt_format_currency($fmt, $totalAmountPaid, $currency)?><strong></td>
                    <td></td>
              </tr>
              <?php

              } 

              ?>
              <tr style="border-top: 2px solid #363639"><td colspan="7"></td></tr>
              <?php
            


                //get the data here
                $itemsPaidForArray = $this->financial_report_model->getAllInvoiceItemsReceived($start_date, $end_date, $residence_type, $class_id, $student_id, $bill_item, isset($payment_method) ? $payment_method : '0');
                $grandTotalReceived = 0;

                foreach($itemsPaidForArray as $item):
                  $amountReceived = $this->financial_report_model->getTotalAmountReceivedForItem($start_date, $end_date, $item['title'], $residence_type, $class_id, $student_id, isset($payment_method) ? $payment_method : '0');

                  $grandTotalReceived += $amountReceived;

                  ?>
                  <tr>
                    <td colspan="5" align="right"><?=$item['title'];?></td>
                      <td align="right"><strong><?=numfmt_format_currency($fmt, $amountReceived, $currency)?><strong></td>
                        <td></td>
                  </tr>
                  <?php
                endforeach;
               ?>
                <tr style="border-top: 2px solid #363639">
                  <td colspan="5" align="right"><strong>GRAND TOTAL</strong></td>
                    <td align="right"><strong><?=numfmt_format_currency($fmt, $grandTotalReceived, $currency)?><strong></td>
                      <td></td>
                </tr>
                
                <?php 
                // Add payment method breakdown if no specific payment method is selected
                if(!isset($payment_method) || $payment_method == '0') {
                    $paymentMethodBreakdown = $this->financial_report_model->getPaymentMethodBreakdown($start_date, $end_date, $residence_type, $class_id, $student_id, $bill_item);
                    if(!empty($paymentMethodBreakdown)) {
                        ?>
                        <tr style="border-top: 1px solid #ccc"><td colspan="7"></td></tr>
                        <tr><td colspan="7" align="left"><strong style="letter-spacing: 3px; font-size: 12px;">PAYMENT METHOD BREAKDOWN</strong></td></tr>
                        <?php
                        foreach($paymentMethodBreakdown as $pm) {
                            $method_name = get_payment_method_name($pm['payment_method']);
                            ?>
                            <tr>
                                <td colspan="5" align="right"><em><?=$method_name;?></em></td>
                                <td align="right"><?=numfmt_format_currency($fmt, $pm['total_amount'], $currency);?></td>
                                <td></td>
                            </tr>
                            <?php
                        }
                    }
                }
                ?>
              <?php

            } else {
              /*not running boarding system*/

              //get the data here
              $itemsPaidForArray = $this->financial_report_model->getAllInvoiceItemsReceived($start_date, $end_date, $class_id, $student_id, $bill_item, isset($payment_method) ? $payment_method : '0');
              $grandTotalReceived = 0;

              foreach($itemsPaidForArray as $item):
                $amountReceived = $this->financial_report_model->getTotalAmountReceivedForItem($start_date, $end_date, $item['title'], $class_id, $student_id, isset($payment_method) ? $payment_method : '0');

                $grandTotalReceived += $amountReceived;

                ?>
                <tr>
                  <td colspan="5" align="right"><?=$item['title'];?></td>
                    <td align="right"><strong><?=numfmt_format_currency($fmt, $amountReceived, $currency)?><strong></td>
                      <td></td>
                </tr>
                <?php
              endforeach;
             ?>
              <tr style="border-top: 2px solid #363639">
                <td colspan="5" align="right"><strong>GRAND TOTAL</strong></td>
                  <td align="right"><strong><?=numfmt_format_currency($fmt, $grandTotalReceived, $currency)?><strong></td>
                    <td></td>
              </tr>
              
              <?php 
              // Add payment method breakdown if no specific payment method is selected
              if(!isset($payment_method) || $payment_method == '0') {
                  $paymentMethodBreakdown = $this->financial_report_model->getPaymentMethodBreakdown($start_date, $end_date, '0', $class_id, $student_id, $bill_item);
                  if(!empty($paymentMethodBreakdown)) {
                      ?>
                      <tr style="border-top: 1px solid #ccc"><td colspan="7"></td></tr>
                      <tr><td colspan="7" align="left"><strong style="letter-spacing: 3px; font-size: 12px;">PAYMENT METHOD BREAKDOWN</strong></td></tr>
                      <?php
                      foreach($paymentMethodBreakdown as $pm) {
                          $method_name = get_payment_method_name($pm['payment_method']);
                          ?>
                          <tr>
                              <td colspan="5" align="right"><em><?=$method_name;?></em></td>
                              <td align="right"><?=numfmt_format_currency($fmt, $pm['total_amount'], $currency);?></td>
                              <td></td>
                          </tr>
                          <?php
                      }
                  }
              }
              ?>
          <?php
          }

    } else {
      ?>
      <tr>
        <td colspan="7" class="text-center"><span class="text-danger">No record found! <?=count($report_data);?></span></td>
      </tr>
      <?php

    }
          ?>
