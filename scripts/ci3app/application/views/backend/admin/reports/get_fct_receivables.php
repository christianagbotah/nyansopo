
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
              <td width="100">
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
                  //getting the owings here from wallet
                  $feeding_fee = $this->financial_report_model->getfctOwingByStudentId($student['student_id'], 'feeding', '');
                  $breakfast_fee = $this->financial_report_model->getfctOwingByStudentId($student['student_id'], 'breakfast', '');
                  $classes_fee = $this->financial_report_model->getfctOwingByStudentId($student['student_id'], 'classes', '');
                  $water_fee = $this->financial_report_model->getfctOwingByStudentId($student['student_id'], 'water', '');
                  $transport_fare = $this->financial_report_model->getfctOwingByStudentId($student['student_id'], 'transport', '');

                  $invoiceTotal = $feeding_fee + $breakfast_fee + $classes_fee + $water_fee + $transport_fare;
                  ?>
                  
                  <?php if($feeding_fee > 0): ?>
                  <div class="form-group row">
                    <div class="col-md-8 col-lg-8">FEEDING FEE</div>
                    <div class="col-md-4 col-lg-4 text-right"><?=number_format($feeding_fee, 2, '.', ','); ?></div>
                  </div>
                  <?php endif; ?>
                  
                  <?php if($breakfast_fee > 0): ?>
                  <div class="form-group row">
                    <div class="col-md-8 col-lg-8">BREAKFAST FEE</div>
                    <div class="col-md-4 col-lg-4 text-right"><?=number_format($breakfast_fee, 2, '.', ','); ?></div>
                  </div>
                  <?php endif; ?>
                  
                  <?php if($classes_fee > 0): ?>
                  <div class="form-group row">
                    <div class="col-md-8 col-lg-8">CLASSES FEE</div>
                    <div class="col-md-4 col-lg-4 text-right"><?=number_format($classes_fee, 2, '.', ','); ?></div>
                  </div>
                  <?php endif; ?>
                  
                  <?php if($water_fee > 0): ?>
                  <div class="form-group row">
                    <div class="col-md-8 col-lg-8">WATER FEE</div>
                    <div class="col-md-4 col-lg-4 text-right"><?=number_format($water_fee, 2, '.', ','); ?></div>
                  </div>
                  <?php endif; ?>
                  
                  <?php if($transport_fare > 0): ?>
                  <div class="form-group row">
                    <div class="col-md-8 col-lg-8">TRANSPORT FARE</div>
                    <div class="col-md-4 col-lg-4 text-right"><?=number_format($transport_fare, 2, '.', ','); ?></div>
                  </div>
                  <?php endif; ?>

                    <div class="form-group row" style="border-top: 2px solid #ddd; padding-top: 10px; margin-top: 10px;">
                        <div class="col-md-8 col-lg-8"><strong>TOTAL</strong></div>
                        <div class="col-md-4 col-lg-4 text-right"><strong><?=number_format($invoiceTotal, 2, '.', ','); ?></strong></div>
                    </div>               
                
              </td>

              <td  width="120" align="right" style="vertical-align: bottom"><strong><?=number_format($invoiceTotal, 2, '.', ','); ?></strong></td>

            </tr>

           <?php
           $grandInvoiceTotal += $invoiceTotal;
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