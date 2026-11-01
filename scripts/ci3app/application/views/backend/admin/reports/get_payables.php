

 <?php     

  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;                                       
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
  

    if(count($report_data) > 0){

      $sn = 1;
      //report_data has arrived
        $grandInvoiceTotal = 0;
        foreach($report_data as $student):
              $student_data = $this->crud_model->getStudentInfoById($student['student_id']);
              $student_name = $student_data->name;
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
                    <img src="<?php echo $student_image;?>" class="img-circle"
          style="width: 90%;" />
                  </div>

              </td>
              <td width="300">                
                    <div class="form-group row"><strong style="font-size: 16px; margin-left: -80px"><?=$student_name; ?></strong></div>
                    <div class="form-group row"><span class="text-muted">CURRENT CLASS: </span><span><?=$student_class; ?></span></div>
                    <div class="form-group row"><span class="text-muted">PARENT: </span><span><?=$parent_name; ?></span></div>
                    <div class="form-group row"><span class="text-muted">CONTACT: </span><span><?=$parent_contact; ?></span></div>
              </td>

              <td>

                <?php
                  //getting the invoices here
                  $invoice_codes = $this->financial_report_model->getBillInvoicesPayableByStudentId($start_date, $end_date, $student['student_id']);
                  //each invoice code
                  $allInvoiceTotal = 0;


                  foreach($invoice_codes as $code):
                    $invoiceTotal = 0;

                    $bills = $this->financial_report_model->getBillPayableByInvoiceCode($code['invoice_code']);
                    $billsRow = $this->financial_report_model->getBillPayableRowByInvoiceCode($code['invoice_code']);

                    //show the invoice code
                    ?>
                    <div class="form-group row">
                      <div>
                        <strong style="font-size: 16px">
                          <?=$code['invoice_code']; ?>
                          <span class="label label-danger"><?=$billsRow->year.'|'.$billsRow->term; ?></span>
                          </strong>
                      </div>
                    <?php
                     //find all Payables in this invoice code
                    $counter = 0;
                    $margin_top = 'style="margin-top: -25px"';
                    foreach($bills as $bill):

                      $invoiceTotal += abs($bill['due']); //summing them up
                      $invoiceTitle = strtoupper($bill['title']);

                      ?>
                      <div class="form-group row" <?=$counter > 0 ? $margin_top : ''; ?>>
                        <div class="col-md-8 col-lg-8">
                          <?=$invoiceTitle; ?>
                        </div>
                        <div class="col-md-4 col-lg-4 text-right">
                          <?=number_format(abs($bill['due']), 2, '.', ','); ?>
                        </div>
                      </div>
                      <?php
                      $counter++;
                    endforeach;

                    //end of invoice code here
                    ?>
                    </div> 

                    <div class="form-group row" style="border-top: 2px; border-top-style: solid; margin-top: -30px">
                        
                        <div class="text-right">
                          <strong><?=number_format($invoiceTotal, 2, '.', ','); ?></strong>
                        </div>
                    </div>

                    <?php
                    $allInvoiceTotal += $invoiceTotal;
                  endforeach;

                ?>                
                
              </td>

              <td  width="120" align="right" style="vertical-align: bottom"><strong><?=number_format($allInvoiceTotal, 2, '.', ','); ?></strong></td>

            </tr>

           <?php
           $grandInvoiceTotal += $allInvoiceTotal;
           $sn++;
        endforeach;
        
        ?>
        <tr>
           <td align="left" colspan="3"><h4><strong>TOTAL</strong></h4></td>
           <td align="right" style="border-top: 1px solid #000; border-style: double none;"><h4><strong><?=numfmt_format_currency($fmt, $grandInvoiceTotal, $currency); ?></strong></h4></td>
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