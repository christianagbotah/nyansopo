

 <?php     

  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;                                       
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
  

    if(count($report_data) > 0){

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
              $parent_name = strtoupper($parent_data->name);
              $parent_contact = $parent_data->phone;

            ?>

            <tr style="border-bottom: 1px solid #000">
              <td width="93">
                <div style="padding-top: 45px">
                  <span class="badge badge-success" style="position: absolute; left: 15px"><?=$sn;?></span>
                    <!-- <img src="<?php //echo $student_image;?>" class="img-circle"
          style="width: 90%;" /> -->
                    <?= $student_code;?>
                  </div>

              </td>
              <td width="280">                
                    <div class="form-group row"><strong style="font-size: 16px; margin-left: -80px"><?=$student_name; ?></strong></div>
                    <div class="form-group row"><span class="text-muted">CURRENT CLASS: </span><span><?=$student_class; ?></span></div>
                    <div class="form-group row"><span class="text-muted">PARENT: </span><span><?=$parent_name; ?></span></div>
                    <div class="form-group row"><span class="text-muted">CONTACT: </span><span><?=$parent_contact; ?></span></div>
              </td>

              <td>

                <?php
                  //getting the invoices here
                  $invoice_codes = $this->financial_report_model->getBillInvoicesPaymentByStudentId($start_date, $end_date, $student['student_id']);
                  //each invoice code
                  $allInvoiceTotal = 0;
                  $allInvoiceTotalDue = 0;

                  $totalOwingByStudent = $this->financial_report_model->getTotalOwingByStudentId($student['student_id']);


                  foreach($invoice_codes as $code):
                    $invoiceTotal = 0;

                    $bills = $this->financial_report_model->getBillPaymentByInvoiceCode($code['invoice_code'], $start_date, $end_date);
                    $billsRow = $this->financial_report_model->getBillPaymentRowByInvoiceCode($code['invoice_code'], $start_date, $end_date);

                    //show the invoice code
                    ?>
                    <div class="form-group row">
                      <div class="col-md-6 col-lg-6">
                        <strong style="font-size: 16px">
                          <?=$code['invoice_code']; ?> 
                          <span class="label label-danger"><?=substr($billsRow->year,  5).'|'.$billsRow->term; ?></span>
                          </strong>
                      </div>

                      <div class="col-md-3 col-lg-3 text-right">
                        <strong class="text-right" style="font-size: 16px">PAID</strong>
                      </div>

                      <div class="col-md-3 col-lg-3 text-right">
                        <strong style="font-size: 16px">DUE</strong>
                      </div>
                    </div> 
                    <?php
                     //find all owings in this invoice code
                    $counter = 0;
                    $margin_top = 'style="margin-top: -25px"';
                    foreach($bills as $bill):

                      $invoiceTotal += $bill['amount']; //summing them up

                      $invoiceTitle = strtoupper($bill['title']);

                      ?>
                      <div class="form-group row" <?=$counter > 0 ? $margin_top : ''; ?>>
                        <div class="col-md-6 col-lg-6">
                          <?=$invoiceTitle; ?>
                        </div>
                        <div class="col-md-3 col-lg-3 text-right">
                          <?=number_format($bill['amount'], 2, '.', ','); ?>
                        </div>
                        <div class="col-md-3 col-lg-3 text-right">
                          <?=number_format($bill['due'], 2, '.', ','); ?>
                        </div>
                      </div>
                      <?php
                      $counter++;
                    endforeach;

                    //end of invoice code here
                    ?>
                    

                    <div class="form-group row" style="border-top: 2px; border-top-style: solid; margin-top: -10px">

                        <!-- <div class="col-md-6 col-lg-6 text-right"></div>
                        <div class="col-md-3 col-lg-3 text-right">
                          <strong><?=number_format($invoiceTotal, 2, '.', ','); ?></strong>
                        </div>  
                        <div class="col-md-3 col-lg-3 text-right">
                          <strong><?=number_format($totalOwingByStudent, 2, '.', ','); ?></strong>
                        </div>  -->
                    </div>

                    <?php
                    $allInvoiceTotal += $invoiceTotal;
                    
                  endforeach;

                ?>                
                
              </td>

              <td align="right" style="vertical-align: bottom;">
                <div class="form-group row" style="margin-top: -10px">
                  <div class="col-md-6 col-lg-6">
                    <strong><?=number_format($allInvoiceTotal, 2, '.', ','); ?></strong>
                  </div>
                  <div class="col-md-6 col-lg-6">
                    <strong><?=number_format($totalOwingByStudent, 2, '.', ','); ?></strong>
                  </div>
                </div>
                </td>

            </tr>

           <?php
           $grandInvoiceTotal += $allInvoiceTotal;
           $grandInvoiceTotalDue += $totalOwingByStudent;
           $sn++;
        endforeach;
        
        ?>
        <tr width="150">
           <td align="left" colspan="3"><h4><strong>TOTAL (<?=$currency;?>)</strong></h4></td>
           <td align="right" style="border-top: 1px solid #000; border-style: double none;">

              <div class="form-group row">
                <div class="col-md-6 col-print-6 col-lg-6">
                  <h4><strong><?=number_format($grandInvoiceTotal, 2, '.', ',');?></strong></h4>
                </div>

                <div class="col-md-6 col-print-6 col-lg-6">
                  <h4><strong><?=number_format($grandInvoiceTotalDue, 2, '.', ','); ?></strong></h4>
                </div>
              </div>

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