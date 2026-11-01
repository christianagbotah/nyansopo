<?php
$term = 2;

  // Get distinct payment dates from daily_fee_transactions
  $this->db->select('DATE(FROM_UNIXTIME(payment_date)) as date_str, payment_date');
  $this->db->distinct();
  $this->db->from('daily_fee_transactions');
  $this->db->where('payment_date >=', $start_date);
  $this->db->where('payment_date <=', $end_date);
  $this->db->order_by('payment_date', 'ASC');
  $daysTimestamp = $this->db->get()->result_array();

?>
<table class="table" id="fct_payments" style="width:100%; border-collapse:collapse;" border="1">
  <thead style="padding: 30px;" class="bg-gray-200">
      <tr>
          <th style="text-align: left"><strong>S/N</strong></th>
          <th style="text-align: left"><strong>ID</strong></th>
          <th style="text-align: left"><strong>NAME</strong></th>
          <th style="text-align: left"><strong>CLASS</strong></th>
          <?php 

            foreach($daysTimestamp as $timestamp):

              $day = strtoupper(strtolower(date('D', $timestamp['payment_date'])));
              ?>
              <th style="">
                <table border="1" style="width:100%; border-collapse:collapse;">
                  <tbody>
                    <tr>
                      <td colspan="5" align="center"><strong><?=$day;?></strong></td>
                    </tr>
                    <tr>
                      <td align="center"><strong>F</strong></td>
                      <td align="center"><strong>B</strong></td>
                      <td align="center"><strong>C</strong></td>
                      <td align="center"><strong>W</strong></td>
                      <td align="center"><strong>T</strong></td>
                    </tr>
                  </tbody>
                </table>
              </th>
              <?php
            endforeach;
          ?>
          
      </tr>
  </thead>
  <tbody>
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


            ?>

            <tr style="border-bottom: 1px solid #000">
              <td><?=$sn;?></td>
              <td>
                <?=$student_code;?>
              </td>
              <td>
                <?=$student_name; ?>
              </td>  
              <td>
                <?=$student_class; ?>
              </td>            
                   
              <?php 
                foreach($daysTimestamp as $timestamp):
                  $payment_date = $timestamp['payment_date'];
                  
                  // Get payment for this specific date
                  $payment = $this->db->select('feeding_amount, breakfast_amount, classes_amount, water_amount, transport_amount')
                    ->where('student_id', $student['student_id'])
                    ->where('payment_date', $payment_date)
                    ->get('daily_fee_transactions')->row();

                  $feedingPaid = $payment ? $payment->feeding_amount : 0;
                  $breakfastPaid = $payment ? $payment->breakfast_amount : 0;
                  $classesPaid = $payment ? $payment->classes_amount : 0;
                  $waterPaid = $payment ? $payment->water_amount : 0;
                  $transportPaid = $payment ? $payment->transport_amount : 0;
                  
                  ?>
                  <th style="">
                    <table border="1" style="width:100%; border-collapse:collapse;">
                      <tbody>
                        <tr>
                          <td align="center" width="20%"><?=number_format($feedingPaid, 0, '.', ',')?></td>
                          <td align="center" width="20%"><?=number_format($breakfastPaid, 0, '.', ',')?></td>
                          <td align="center" width="20%"><?=number_format($classesPaid, 0, '.', ',')?></td>
                          <td align="center" width="20%"><?=number_format($waterPaid, 0, '.', ',')?></td>
                          <td align="center" width="20%"><?=number_format($transportPaid, 0, '.', ',')?></td>
                        </tr>
                      </tbody>
                    </table>
                  </th>
                  <?php
                endforeach;
              ?>

            </tr>

           <?php
           $sn++;
        endforeach;
        
        ?>
        <tr>
           <td align="right" valign="center" style="border-top: 1px solid #000; border-style: double none;" colspan="4"><strong>TOTAL (<?=$currency;?>)</strong></td>
           

            <?php 
                $feedingGrandTotal = 0;
                $breakfastGrandTotal = 0;
                $classesGrandTotal = 0;
                $waterGrandTotal = 0;
                $transportGrandTotal = 0;

                foreach($daysTimestamp as $timestamp):
                  $payment_date = $timestamp['payment_date'];
                  
                  // Get totals for this date
                  $totals = $this->db->select_sum('feeding_amount')
                    ->select_sum('breakfast_amount')
                    ->select_sum('classes_amount')
                    ->select_sum('water_amount')
                    ->select_sum('transport_amount')
                    ->where('payment_date', $payment_date)
                    ->get('daily_fee_transactions')->row();

                  $totalFeedingPaid = $totals->feeding_amount ?? 0;
                  $totalBreakfastPaid = $totals->breakfast_amount ?? 0;
                  $totalClassesPaid = $totals->classes_amount ?? 0;
                  $totalWaterPaid = $totals->water_amount ?? 0;
                  $totalTransportPaid = $totals->transport_amount ?? 0;

                  ?>

                  <th style="">
                    <table border="1" style="width:100%; border-collapse:collapse;">
                      <tbody>
                        <tr>
                         <td align="right" width="20%" style="border-top: 1px solid #000; border-style: double none;"><strong><?=number_format($totalFeedingPaid, 2, '.', ',');?></strong></td>
                         <td align="right" width="20%" style="border-top: 1px solid #000; border-style: double none;"><strong><?=number_format($totalBreakfastPaid, 2, '.', ',');?></strong></td>
                         <td align="right" width="20%" style="border-top: 1px solid #000; border-style: double none;"><strong><?=number_format($totalClassesPaid, 2, '.', ',');?></strong></td>
                         <td align="right" width="20%" style="border-top: 1px solid #000; border-style: double none;"><strong><?=number_format($totalWaterPaid, 2, '.', ',');?></strong></td>
                         <td align="right" width="20%" style="border-top: 1px solid #000; border-style: double none;"><strong><?=number_format($totalTransportPaid, 2, '.', ',');?></strong></td>
                        </tr>
                      </tbody>
                    </table>
                  </th>

                  <?php

                  $feedingGrandTotal += $totalFeedingPaid;
                  $breakfastGrandTotal += $totalBreakfastPaid;
                  $classesGrandTotal += $totalClassesPaid;
                  $waterGrandTotal += $totalWaterPaid;
                  $transportGrandTotal += $totalTransportPaid;

                endforeach;

                  ?>
  
        </tr>
        <tr>
          <td align="right" valign="center" style="border-top: 1px solid #000; border-style: double none;" colspan="4"><strong>GRAND TOTAL (<?=$currency;?>)</strong></td>
          <td colspan="<?php echo count($daysTimestamp); ?>" align="center">
            <h3><strong><?=number_format($feedingGrandTotal + $breakfastGrandTotal + $classesGrandTotal + $waterGrandTotal + $transportGrandTotal, 2, '.', ',');?></strong></h3>
            <hr>
            <small>Feeding: <?=number_format($feedingGrandTotal, 2, '.', ',');?> | Breakfast: <?=number_format($breakfastGrandTotal, 2, '.', ',');?> | Classes: <?=number_format($classesGrandTotal, 2, '.', ',');?> | Water: <?=number_format($waterGrandTotal, 2, '.', ',');?> | Transport: <?=number_format($transportGrandTotal, 2, '.', ',');?></small>
          </td>
        </tr>

        <?php

    } else {
      ?>
      <tr>
        <td colspan="4" class="text-center"><span class="text-danger">No record found!</span></td>
      </tr>
      <?php

    }
          ?>
  </tbody>
</table>
