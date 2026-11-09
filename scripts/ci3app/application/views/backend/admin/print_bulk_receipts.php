<?php 
  //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        ?>
<!doctype html>
<html>
  <head>
    <title>Payment Receipt</title>

        <style type="text/css">
          #button {
            position: fixed;
            background-color: #337ab7;
            padding: 5px 10px 5px 10px;
            font-weight: bold;
            border-radius: 9px;
          }

          #button a {
            text-decoration: none;
            color: #fff;
          }

             @media Print
             {
                page {
                
                background: #ffffff;
                display: block;
                margin: 0 auto;
                margin-bottom: 0.5cm;
                box-shadow: 0;

                }


                page[size="A6"]{
                    width: 80mm;
                    max-height: 297mm;
        
                }

                body, page {
                    margin: 0;
                    box-shadow: 0;
                }

                table {
                    width: 100%; 
                    border-collapse: collapse; 
                    
                }
            }
        </style>
  </head>
  <body>
    <div class="row">
      <div id="button">
        <a href="<?= site_url($this->session->userdata('login_type').'/cft_student_receipt'); ?>" title="Go Back">Back</a>
      </div>
    </div>
    <center>
      <div id="r_print">
        
    <?php
     $draw_dashed_line = '<div style="border-bottom-width: 2px; border-bottom-style: dashed;"></div><br>';
    //Using for Loop
    for($ir = 0; $ir < count($ids_selected); $ir++) {

          //general
          $feeding_fee_charged    =   $this->db->get_where('class' , array('class_id' => $class_id) )->row()->feeding_fee;
          $classes_fee_charged    =   $this->db->get_where('class' , array('class_id' => $class_id) )->row()->classes_fee;

          //for beneficiaries
          $feeding_fee_charged_b    =   $this->db->get('benefit_category')->row()->feeding_charge;
          $classes_fee_charged_b    =   $this->db->get('benefit_category')->row()->classes_charge;

          //check if student is a beneficiary and effect the corresponding charges
          $benefit_status = $this->db->get_where('student', array('student_id'=> $ids_selected[$ir]))->row()->benefit_status;
          if($benefit_status == 0) {
            $feeding_fee_charged = $feeding_fee_charged;
            $classes_fee_charged = $classes_fee_charged;
          } else {
            $feeding_fee_charged = $feeding_fee_charged_b;
            $classes_fee_charged = $classes_fee_charged_b;
          }

          //transport id from student table
          $transport_id = $this->db->get_where('student', array('student_id' => $ids_selected[$ir]))->row()->transport_id;

          //transport fare from transport table
          $transport_fare_charged = $this->db->get_where('transport', array('transport_id' => $transport_id))->row()->route_fare;

          $ok = 0;

          //get year and term from transport_fare_payment
            $year_term = $this->db->select('year, term')->where(['day_timestamp' => $timestamp, 'student_id' => $ids_selected[$ir]])->get('transport_fare_payment')->row();
            if($year_term) {
              $year  = $year_term->year;
              $term  = $year_term->term;
            } else {
              // Fallback to feeding or classes payment
              $year_term = $this->db->select('year, term')->where(['day_timestamp' => $timestamp, 'student_id' => $ids_selected[$ir]])->get('feeding_fee_payment')->row();
              if(!$year_term) $year_term = $this->db->select('year, term')->where(['day_timestamp' => $timestamp, 'student_id' => $ids_selected[$ir]])->get('classes_fee_payment')->row();
              $year  = $year_term->year ?? '';
              $term  = $year_term->term ?? '';
            }


        $system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
            $system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
            $system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
            $system_slogan = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
            $cashier = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
            $student_name = $this->db->get_where('student', array('student_id' => $ids_selected[$ir]))->row()->name;
            $class_id        = $this->db->get_where('enroll', array('student_id' => $ids_selected[$ir], 'year' => $year))->row()->class_id;
            $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
            $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

            //get the receipt code from the new fee payment tables
            $receipt_data = $this->db->select('receipt_code, payment_date')->where(['student_id' => $ids_selected[$ir], 'day_timestamp' => $timestamp, 'can_delete !=' => 'trash'])->get('feeding_fee_payment')->row();
            if(!$receipt_data) $receipt_data = $this->db->select('receipt_code, payment_date')->where(['student_id' => $ids_selected[$ir], 'day_timestamp' => $timestamp, 'can_delete !=' => 'trash'])->get('classes_fee_payment')->row();
            if(!$receipt_data) $receipt_data = $this->db->select('receipt_code, payment_date')->where(['student_id' => $ids_selected[$ir], 'day_timestamp' => $timestamp, 'can_delete !=' => 'trash'])->get('transport_fare_payment')->row();
            
            $receipt_code = $receipt_data->receipt_code ?? '';
            $date = $receipt_data->payment_date ?? time();

            //get amount paid and owe for feeding fee
            $feeding_payment = $this->db->where(['day_timestamp' => $timestamp, 'student_id' => $ids_selected[$ir], 'can_delete !=' => 'trash'])->get('feeding_fee_payment')->row();
            $feeding_paid = $feeding_payment->amount ?? 0;
            $feeding_owe  = $feeding_payment->due ?? 0;

            //get amount paid and owe for classes fee
            $classes_payment = $this->db->where(['day_timestamp' => $timestamp, 'student_id' => $ids_selected[$ir], 'can_delete !=' => 'trash'])->get('classes_fee_payment')->row();
            $classes_paid = $classes_payment->amount ?? 0;
            $classes_owe  = $classes_payment->due ?? 0;

            //get amount paid and owe for transport fare
            $transport_payment = $this->db->where(['day_timestamp' => $timestamp, 'student_id' => $ids_selected[$ir], 'can_delete !=' => 'trash'])->get('transport_fare_payment')->row();
            $transport_paid = $transport_payment->amount ?? 0;
            $transport_owe  = $transport_payment->due ?? 0;

            //who issued the receipt - check all three tables
            $issuer_data = $feeding_payment ?? $classes_payment ?? $transport_payment ?? null;
            $issuer_id = $issuer_data->issuer_id ?? '';
            $account_type = $issuer_data->account_type ?? '';

            if($issuer_id) {
                if($account_type == 'Teacher') {
                    $issuer_name = $this->db->get_where('teacher', array('teacher_id' => $issuer_id))->row()->name ?? 'Not Available';
                } else {
                    $issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name ?? 'Not Available';
                }
            } else {
                $issuer_name = 'Not Available';
            }

            //add section A or B if the class has more than one section
            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
            $sec_name = '';
            if($class_has_more_sections > 1) {
                $sec_name = $section_name;
            }

            $class = '';
            if($class_name == 'CRECHE') {
                $class = ucwords(strtolower($class_name));
            } else {
                $class = ucwords(strtolower($class_name)). ' '. $class_name_numeric.$sec_name;
            }

            
            //for feeding fee
            if($feeding_owe < 0 && $feeding_fee_charged != 0) { //student over paid (negative number)
                $feeding_owe = abs($feeding_owe);
                $fdays = intval($feeding_owe / $feeding_fee_charged); //No of days student paid for
                $fbal = -($feeding_owe % $feeding_fee_charged); //balance after the no of days
            } else{
                if($feeding_owe == 0) {
                  $fdays = 1;
                  $fbal  = $feeding_owe;
                } elseif($feeding_owe > 0){
                  $fdays = 0;
                  $fbal  = $feeding_owe;
                }
            }

            //for classes fee
            if($classes_owe < 0 && $classes_fee_charged != 0) { //student over paid (negative number)
                $classes_owe = abs($classes_owe);
                $cdays = intval($classes_owe / $classes_fee_charged); //No of days student paid for
                $cbal = -($classes_owe % $classes_fee_charged); //balance after the no of days
            } else{
                if($classes_owe == 0) {
                  $cdays = 1;
                  $cbal  = $classes_owe;
                } elseif($classes_owe > 0){
                  $cdays = 0;
                  $cbal  = $classes_owe;
                }
            }

            //for transport fare
            if($transport_owe < 0 && $transport_fare_charged != 0) { //student over paid (negative number)
                $transport_owe = abs($transport_owe);
                $tdays = intval($transport_owe / $transport_fare_charged); //No of days student paid for
                $tbal = -($transport_owe % $transport_fare_charged); //balance after the no of days
            } else{
                if($transport_owe == 0) {
                  $tdays = 1;
                  $tbal  = $transport_owe;
                } elseif($transport_owe > 0) {
                  $tdays = 0;
                  $tbal  = $transport_owe;
                }
            }


            //summation
            $total_amount_paid = $feeding_paid + $classes_paid + $transport_paid;
            $total_amount_owe  = $fbal + $cbal + $tbal;
    ?>
        
            <page size="A6">
            <div id="main_receipt" style="max-height: 297mm; page-break-after: always">
            <table style="border: 1px solid #d0cccc; padding: 5px 15px;">
            <tr>
            <td align="center">
            <div><img src="<?= base_url('uploads/school_logo.png'); ?>" style="max-height : 120px;"></div><br>
            <div><strong><?= $system_name; ?></strong></div>
            <div><strong><?= $system_phone .' | '. $system_mail; ?></strong></div>

            <div class="row" style="padding: 8px;" >
                <center><strong id="official" style="background-color: #000; width: 200px; padding: 6px; border-radius: 9px; color: #fff; font-size: 23px">Official Receipt</strong></center>
            </div>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left">Received From:</th>
                            <th align="right" colspan="2"><?= ucwords(strtolower($student_name)); ?></th>
                        </tr>
                        
                        <?php if($class_name == 'JHSS') { ?>
                                <tr>
                                  <th style="text-align: left">Year | Semester:</th>
                                    <th align="right" colspan="2"><?= explode('-', $year)[1]. ' | '. $sem; ?></th>
                                </tr>
                            <?php } else { ?>

                                <tr>
                                  <th style="text-align: left">Year | Term:</th>
                                    <th align="right" colspan="2"><?= explode('-', $year)[1]. ' | '. $term; ?></th>
                                </tr>

                            <?php } ?>
                        <tr>
                            <th style="text-align: left">Class:</th>
                            <th align="right" colspan="2"><?= $class; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Receipt No.:</th>
                            <th align="right" colspan="2"><?= $receipt_code; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Currency:</th>
                            <th style="text-align: right" colspan="2"><?= $currency; ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left">Paid On:</th>
                            <th align="right" colspan="2"><?= date('d/m/Y H:i:s', $date) ?></th>
                        </tr>
                        <tr>
                            <th style="text-align: left"></th>
                            <th align="right" colspan="2"></th>
                        </tr>
                        <tr>
                          <th style="text-align: left">Issued On:</th>
                          <th style="text-align: right" colspan="2"><?= date('d/m/Y H:i:s') ?></th>
                          <th align="right"></th>
                        </tr>
                    </thead>
                </table>
            </div><br>
            <table>
                <thead>
                    <tr>
                        <th width="40" align="left" style="font-size: 12px">S/N</th>
                        <th width="180" align="left" style="font-size: 12px">ITEM</th>
                        <th></th>
                        <th width="120" align="center" style="font-size: 12px">DAYS</th>
                        <th width="120" align="right" style="font-size: 12px">PAID</th>
                        <th width="120" align="right" style="font-size: 12px">BAL</th>
                    </tr>
                    <tr><th colspan="6"><hr></th></tr>
                </thead>
                <tbody>

                        <tr>
                            <td>1</td>
                            <td align="left" colspan="2" style="font-size: 13px">FEEDING<br><small style="font-size: 12px">(<?= number_format($feeding_fee_charged, 2, '.', ','); ?> per day)</small></td> 
                            <td align="center"><?= $fdays; ?></td>                   
                            <td align="right"><?= number_format($feeding_paid, 2, '.', ','); ?></td>
                            <td align="right"><?= number_format($fbal, 2, '.', ','); ?></td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td align="left" colspan="2" style="font-size: 13px">CLASSES<br><small style="font-size: 11px">(<?= number_format($classes_fee_charged, 2, '.', ','); ?> per day)</small></td>  
                            <td align="center"><?= $cdays; ?></td>                  
                            <td align="right"><?= number_format($classes_paid, 2, '.', ','); ?></td>
                            <td align="right"><?= number_format($cbal, 2, '.', ','); ?></td>
                        </tr>

                        <tr>
                            <td>3</td>
                            <td align="left" colspan="2" style="font-size: 13px">TRANSPORT<br><small style="font-size: 11px">(<?= number_format($transport_fare_charged, 2, '.', ','); ?> per day)</small></td>
                            <td align="center"><?= $tdays; ?></td>                   
                            <td align="right"><?= number_format($transport_paid, 2, '.', ','); ?></td>
                            <td align="right"><?= number_format($tbal, 2, '.', ','); ?></td>
                        </tr>
               

                  <tr></tr>
                    <tr></tr>
                    <tr></tr>
                    <tr><td colspan="6"><hr></td></tr>
                    <tr>
                        <td></td>
                        <td align="right" colspan="3">Sub-total:</td>
                        <td align="right"><?= number_format($total_amount_paid, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td align="right" colspan="3">Tax:</td>
                        <td align="right"><?= number_format(0, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="4">Net Amount Payable:</td>
                        <td align="right" style="font-weight: bolder;"><?= number_format($total_amount_paid, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                      <td></td>
                      <td align="right" colspan="3">Balance Owe:</td>
                      <td></td>
                      <td align="right" style="font-weight: bolder;"><?= number_format($total_amount_owe, 2, '.', ','); ?></td>
                    </tr>

                    <tr><td><br></td></tr>
                    <tr>
                        <td colspan="4">
                            <strong>Cashier: </strong><br><?= ucwords(strtolower($issuer_name)); ?><small>(<?= $account_type; ?>)</small>
                            <hr>
                        </td>
                        <?php 
                            $method = $issuer_data->payment_method ?? 1;
                            $m = 'Cash';
                            if ($method == 2) $m = 'Cheque';
                            if ($method == 3) $m = 'Mobile Money';
                            if ($method == 4) $m = 'Bank Transfer';
                        ?>

                        <td colspan="2" align="right"><strong>Method: </strong><br><?= $m; ?> <hr></td>
                    </tr>
                    <tr><td colspan="6" align="center">
                        <p><?= $system_slogan."!!!"; ?></p>
                        <p><strong>Thank You</strong></p>
                        <p style="color: #6f686c; margin-top: -10px">Software Developed By: Lightworldtech-0243618186</p>
                    </td></tr>
                </tbody>
            </table>
        </td>
        </tr>
        </table>
        </div>
        </page><br>

     <?php
     if($ir + 1 < count($ids_selected)) {
          echo $draw_dashed_line;
        }
     }
     ?>
     </div>
     
        <button onclick="PrintElem('#r_print')" style="border: 1 solid #0cc; padding: 10px; cursor: pointer; background-color: green; color: #fff;">Print</button>
        <button onclick="load_back()" style="position:relative; border: 1 solid #0cc; cursor: pointer; padding: 10px; background-color: red; color: #fff;">Back</button>
        </center>
    

<script type="text/javascript">
  function load_back() {
    window.location.href = '<?= site_url($this->session->userdata('login_type').'/cft_student_receipt'); ?>';
  }
</script>
    
    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
  </body>
</html>

<script type="text/javascript">
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Receipt', '');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-size: 14px">');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }
</script>