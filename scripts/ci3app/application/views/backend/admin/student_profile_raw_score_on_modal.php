<style>
  .exam_chart {
    width       : 100%;
    height      : 265px;
    font-size   : 11px;
  }
  .profile-env > header {
    position: relative;
    z-index: 20;
    margin-top: 0px;
}

div.dataTables_wrapper div.dataTables_filter input {
    width: 70% !important;
  }
</style>

<?php
  
  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

  $this->db->select_max('timestamp');
  $att_timestamp = $this->db->get('attendance')->row()->timestamp;

  //AGGREGATION OF MARKS
     //aggregation of core subjects 4
    $total_aggregate = 0;
    $sum_core = 0;
       $this->db->where('exam_id', $exam_id);
       $this->db->where('class_id', $class_id);
       $this->db->where('section_id', $section_id);
       $this->db->where('student_id', $student_id);
       $this->db->where('year', $running_year);
       $this->db->where('term', $running_term);
       $this->db->where('status', '1');
       $this->db->limit(4);
       //$this->db->order_by("mark_obtained", "desc");
       
       $marks = $this->db->get('mark')->result_array();

        foreach ($marks as $row) {
            $sum_core += $row['mark_obtained'];
        }
     

     //aggregation of best 2
    $sum_best2 = 0;
       $this->db->where('exam_id', $exam_id);
       $this->db->where('class_id', $class_id);
       $this->db->where('section_id', $section_id);
       $this->db->where('student_id', $student_id);
       $this->db->where('year', $running_year);
       $this->db->where('term', $running_term);
       $this->db->where('status', '0');
       $this->db->limit(2);
       $this->db->order_by("mark_obtained", "desc");
       
       $marks = $this->db->get('mark')->result_array();

        foreach ($marks as $row) {
            $sum_best2 += $row['mark_obtained'];

            //finding the aggregate for the best 2 subjects
            $core_grade = $this->crud_model->get_raw_score_grade($row['mark_obtained']);
            $total_aggregate += $core_grade['grade_point'];  
        }

$student_id = $param2;
$running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
$running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
  $student_info = $this->db->get_where('student', array('student_id' => $student_id))->result_array();
  foreach ($student_info as $row):
    $enroll_info = $this->db->get_where('enroll', array(
      'student_id' => $row['student_id'], 'year' => $running_year
    ));
    $class_id = $enroll_info->row()->class_id;

    $section_id = $this->db->get_where('section', array('class_id' => $class_id))->row()->section_id;

    $exams = $this->crud_model->get_exams();

    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;
?>

<div class="row">
  <div class="col-sm-9 col-xs-8"></div>
  <div class="col-sm-3 col-xs-4">
    <button class="btn btn-block btn-info btn-sm pull-right"  onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_student_edit/'.$student_id);?>');">
      <a href="#" style='color: #ffffff; font-weight: bolder; letter-spacing: 3px;'>
      <i class="entypo-pencil"></i>
          <?php echo get_phrase('edit');?>
      </a>
    </button>
  </div>
</div>
<div class="profile-env">
	<header class="row">
		<div class="col-md-12" style="margin-bottom: 15px;">
			<center>
        <a href="#">
  				<img src="<?php echo $this->crud_model->get_image_url('student', $student_id, $gender) ;?>" class="img-circle" height="100px;"/>
  			</a>
        <br>
        <h4>
          <?php echo $row['name']; ?>
        </h4>
        <span>
          <?php
            $class_name_numeric = $this->db->get_where('class', array(
              'class_id' => $enroll_info->row()->class_id
            ))->row()->name_numeric;
            $class_name = $this->db->get_where('class', array(
              'class_id' => $enroll_info->row()->class_id
            ))->row()->name;
            $section_name = $this->db->get_where('section', array(
              'section_id' => $enroll_info->row()->section_id
            ))->row()->name;
          ?>
          <?php echo get_phrase('class').' - '.$class_name.' '.$class_name_numeric. ' | '. get_phrase('section').' - '.$section_name; ?>
        </span>
      </center>
		</div>
    <div class="col-md-12">

		<ul class="nav nav-tabs">
      <li class="active"><a href="#tab1" data-toggle="tab" class="btn btn-primary">
          <span class="visible-xs"><i class="entypo-home"></i></span>
          <span class="hidden-xs"><?php echo get_phrase('basic_info'); ?></span>
        </a>
      </li>
      <li class="">
        <a href="#tab2" data-toggle="tab" class="btn btn-info">
          <span class="visible-xs"><i class="entypo-user"></i></span>
          <span class="hidden-xs"><?php echo get_phrase('parent_info'); ?></span>
        </a>
      </li>
      <li class="">
        <a href="#tab3" data-toggle="tab" class="btn btn-default">
          <span class="visible-xs"><i class="entypo-mail"></i></span>
          <span class="hidden-xs"><?php echo get_phrase('exam_marks'); ?></span>
        </a>
      </li>
      <li class="">
        <a href="#tab4" data-toggle="tab" class="btn btn-success">
          <span class="visible-xs"><i class="entypo-mail"></i></span>
          <span class="hidden-xs"><?php echo get_phrase('Login_credentials'); ?></span>
        </a>
      </li>
      <!-- <li class="">
        <a href="#tab4" data-toggle="tab" class="btn btn-default">
          <span class="visible-xs"><i class="entypo-cog"></i></span>
          <span class="hidden-xs"><?php //echo get_phrase('attendance'); ?></span>
        </a>
      </li> 
      <li class="">
        <a href="#tab5" data-toggle="tab" class="btn btn-default">
          <span class="visible-xs"><i class="entypo-cog"></i></span>
          <span class="hidden-xs"><?php echo get_phrase('payments'); ?></span>
        </a>
      </li> -->

      <li class="">
        <a href="#tab6" data-toggle="tab" class="btn btn-danger">
          <span class="visible-xs"><i class="entypo-suitcase"></i></span>
          <span class="hidden-xs"><?php echo get_phrase('accounts'); ?></span>
        </a>
      </li>
    </ul>

		<div class="tab-content">
			<div class="tab-pane active" id="tab1">
        <?php
          $basic_info_titles = ['name','parent', 'class', 'section', 'email', 'phone', 'address', 'blood_group', 'gender', 'birthday', 'transport', 'dormitory', 'special_diet'];
          $basic_info_values = [$row['name'], $row['parent_id'] == NULL ? '' : $this->db->get_where('parent', array('parent_id' => $row['parent_id']))->row()->name,
          $class_name.' '.$class_name_numeric, $section_name, $row['email'], $row['phone'] == NULL ? '' : $row['phone'], $row['address'] == NULL ? '' : $row['address'], $row['blood_group'] == NULL ? '' : $row['blood_group'], $row['sex'] == NULL ? '' : $row['sex'], $row['birthday'],
          $row['transport_id'] == NULL ? '' : $this->db->get_where('transport', array('transport_id' => $row['transport_id']))->row()->route_name,
          $row['dormitory_id'] == NULL ? '' : $this->db->get_where('dormitory', array('dormitory_id' => $row['dormitory_id']))->row()->name,
          $row['special_diet'] == 0 ? 'NO' : 'YES'];
        ?>
        <table class="table table-bordered" style="margin-top: 20px;">
          <tbody>
          <?php for ($i=0; $i < count($basic_info_titles) ; $i++) { ?>
            <tr>
              <td width="30%">
                <strong><?php echo get_phrase($basic_info_titles[$i]); ?></strong>
              </td>
              <td><?php echo $basic_info_values[$i]; ?></td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
			</div>
			<div class="tab-pane" id="tab2">
        <?php if ($row['parent_id'] == NULL) { ?>
          <div style="margin-top: 20px;">
            <center>
              <?php echo get_phrase('parent_information_is_not_available'); ?>
            </center>
          </div>
        <?php } else {
            $parent_info = $this->db->get_where('parent', array('parent_id' => $row['parent_id']))->result_array();
            $parent_info_titles = ['name', 'email', 'phone', 'address', 'profession'];
            foreach ($parent_info as $info) {
              $parent_info_values = [$info['name'], $info['email'], $info['phone'] == NULL ? '' : $info['phone'],
              $info['address'] == NULL ? '' : $info['address'], $info['profession'] == NULL ? '' : $info['profession']];
            }
          ?>
          <table class="table table-bordered" style="margin-top: 20px;">
            <tbody>
              <?php for ($i=0; $i < count($parent_info_titles); $i++) { ?>
                <tr>
                  <td width="30%"><strong><?php echo get_phrase($parent_info_titles[$i]); ?></strong></td>
                  <td><?php echo $parent_info_values[$i]; ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        <?php } ?>
			</div>


            <div class="tab-pane" id="tab3">
        <?php foreach ($exams as $row2) { ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-5 col-lg-5 col-sm-5 col-xs-5"><h3><?php echo $row2['name']; ?></h3></div>
            <div class="col-md-7 col-lg-7 col-sm-7 col-xs-7"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>
          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject' , array(
                                'class_id' => $class_id , 'year' => $running_year, 'term' => $running_term
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>
 <br>
           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_results_sheet_print_view/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_sem.'/'.$running_year);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_sem.'/'.$running_year);?>"
               class="btn btn-info" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php } ?>
      </div>


      <!-- <div class="tab-pane" id="tab4">
        attendance
      </div> -->
      <div class="tab-pane" id="tab4">
        
          <table class="table table-bordered" style="margin-top: 20px;">
            <tbody>
                <tr>
                  <td width="30%"><strong><?php echo get_phrase('authentication_key'); ?></strong></td>
                  <td><strong style="letter-spacing: 3px"><?php echo $row['authentication_key']; ?></strong></td>
                </tr>
                <tr>
                  <td width="30%"><strong><?php echo get_phrase('username'); ?></strong></td>
                  <td><strong><?php echo $row['username']; ?></strong></td>
                </tr>
                <tr>
                  <td width="30%"><strong><?php echo get_phrase('Password'); ?></strong></td>
                  <td><?php echo '<strong style="color: red;">Not Available.</strong> In case of password lost, ask the student to use his/her email to request for a new password'; ?></td>
                </tr>
            </tbody>
          </table>
      </div>

			<!--<div class="tab-pane" id="tab5">
        <?php
          $payments = $this->db->get_where('payment', array(
            'student_id' => $row['student_id']))->result_array();
         ?>
         <table class="table table-bordered" style="margin-top: 20px;" id="payment_ta">
           <thead>
             <tr>
               <th>#</th>
               <th><?php echo get_phrase('title'); ?></th>
               <th style="text-align: right"><?php echo get_phrase('amount'); ?></th>
               <th><?php echo get_phrase('date'); ?></th>
               <th><?php echo get_phrase('options'); ?></th>
             </tr>
           </thead>
           <tbody>
             <?php
                $count = 1;
                foreach ($payments as $payment):
              ?>
                <tr>
                  <td><?php echo $count++; ?></td>
                  <td><?php echo $payment['title']; ?></td>
                  <td align="right"><strong><?php echo numfmt_format_currency($fmt, $payment['amount'], $currency); ?></strong></td>
                  <td><?php echo date('d M Y', $payment['timestamp']); ?></td>
                  <td>
                    <?php 
                      if($payment['invoice_code'] != null) {
                        ?>

                        <a href="#" class="btn btn-info"
                          onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/'.$payment['invoice_code'].'/'.$payment['year'].'/'.$payment['term']);?>')">
                          <?php echo get_phrase('view_invoice'); ?>
                        </a>
                    <?php
                      }
                    ?>
                  </td>
                </tr>
            <?php endforeach; ?>
           </tbody>
         </table>
      </div>-->

      <div class="tab-pane" id="tab6">
        <?php
          $this->db->select('invoice_code');
          $this->db->distinct();
          $this->db->where('can_delete !=', 'trash');
          $payables_array = $this->db->get_where('invoice', array(
            'student_id' => $row['student_id'], 'due <' => 0))->result_array();

          $this->db->select('invoice_code');
          $this->db->distinct();
          $this->db->where('can_delete !=', 'trash');
          $receivables_array = $this->db->get_where('invoice', array(
            'student_id' => $row['student_id'], 'due >' => 0))->result_array();


          $timestamp = strtotime(date('d-m-Y'));
          //is this date available
          $received_date = $this->db->get_where('feeding_fee', array('timestamp' => $timestamp))->num_rows();
          if($received_date > 0) {
              $timestamp = $timestamp;
          } else {
              //let's select the current date in the tables
              $available_timestamp_rows = $this->db
                                          ->select('timestamp')
                                          ->distinct()
                                          ->order_by('timestamp', 'desc')
                                          ->limit(1)
                                          ->get('feeding_fee');
              $available_timestamp = $available_timestamp_rows->row()->timestamp;

              if($available_timestamp_rows->num_rows() > 0) {
                  $timestamp = $available_timestamp;
              }                          
          }
          //if($amount > 0) {
            //feeding owe
            $this->db->select_sum('due');
            $this->db->from('feeding_fee');
            $this->db->where('due >', 0);
            $this->db->where('student_id', $row['student_id']);
            $this->db->where('timestamp', $timestamp);
            $tf_owe = $this->db->get()->row()->due;
            
            $this->db->select_sum('due');
            $this->db->from('feeding_fee');
            $this->db->where('due <', 0);
            $this->db->where('student_id', $row['student_id']);
            $this->db->where('timestamp', $timestamp);
            $tf_refund = $this->db->get()->row()->due;

       // if($amount > 0) {
            //classes owe
            $this->db->select_sum('cdue');
            $this->db->from('feeding_fee');
            $this->db->where('cdue >', 0);
            $this->db->where('student_id', $row['student_id']);
            $this->db->where('timestamp', $timestamp);
            $tc_owe = $this->db->get()->row()->cdue;
            
            $this->db->select_sum('cdue');
            $this->db->from('feeding_fee');
            $this->db->where('cdue <', 0);
            $this->db->where('student_id', $row['student_id']);
            $this->db->where('timestamp', $timestamp);
            $tc_refund = $this->db->get()->row()->cdue;

       //if($amount > 0) {
            //transport owe
            $this->db->select_sum('due');
            $this->db->from('transport_fare');
            $this->db->where('due >', 0);
            $this->db->where('student_id', $row['student_id']);
            $this->db->where('timestamp', $timestamp);
            $tt_owe = $this->db->get()->row()->due;
        
            $this->db->select_sum('due');
            $this->db->from('transport_fare');
            $this->db->where('due <', 0);
            $this->db->where('student_id', $row['student_id']);
            $this->db->where('timestamp', $timestamp);
            $tt_refund = $this->db->get()->row()->due;

        
                                    
         ?>
         <div class="row">
             <table class="table table-bordered" style="margin-top: 20px;" id="accounts">
               <thead>
                 <tr>
                   <th><?php echo get_phrase('receivables'); ?></th>
                   <th><?php echo get_phrase('payables'); ?></th>
                 </tr>
               </thead>
               <tbody>
                <tr>
                  <td>
                    <table class="table table-bordered" style="margin-top: 20px;" id="receivables">
                       <thead>
                         <tr>
                           <th><?php echo get_phrase('invoice'); ?></th>
                           <th style="text-align: right"><?php echo get_phrase('amount'); ?></th>
                           <th><?php echo get_phrase('date'); ?></th>
                           <th><?php echo get_phrase('options'); ?></th>
                         </tr>
                       </thead>
                       <tbody>
                         <?php
                            $count = 1;
                            foreach ($receivables_array as $rec):

                              $title = $this->db->get_where('invoice', array('invoice_code' => $rec['invoice_code'], 'student_id' => $row['student_id'], 'due >' => 0))->row()->title;
                              $this->db->select_sum('due');
                              $amount_rec = $this->db->get_where('invoice', array('invoice_code' => $rec['invoice_code'], 'student_id' => $row['student_id'], 'due >' => 0))->row()->due;
                              $rdate = $this->db->get_where('invoice', array('invoice_code' => $rec['invoice_code'], 'student_id' => $row['student_id'], 'due >' => 0))->row()->creation_timestamp;
                          ?>
                            <tr>
                              <td><?php echo $rec['invoice_code']; ?></td>
                              <td align="right"><strong><?php echo numfmt_format_currency($fmt, $amount_rec, $currency); ?></strong></td>
                              <td><?php echo date('d M Y', $rdate); ?></td>
                              <td>
                                  <a href="#" class="btn btn-success btn-sm" onclick="invoice_pay_modal('<?=$row['student_id'] ?>')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                        <?php endforeach;

                          if($tf_owe != 0) {
                         ?>

                            <tr>
                              <td><?php echo 'FEEDING FEE'; ?></td>
                              <td align="right"><strong><?php echo numfmt_format_currency($fmt, $tf_owe, $currency); ?></strong></td>
                              <td><?php echo 'As at today'; ?></td>
                              <td>
                                  <a href="<?php echo site_url('admin/manage_attendance_view/'.$class_id.'/'.$section_id.'/'.$att_timestamp.'/'.$row['student_id']) ?>" class="btn btn-success btn-sm" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                          <?php }

                          if($tc_owe != 0) {
                          ?>
                            <tr>
                              <td><?php echo 'CLASSES FEE'; ?></td>
                              <td align="right"><strong><?php echo numfmt_format_currency($fmt, $tc_owe, $currency); ?></strong></td>
                              <td><?php echo 'As at today'; ?></td>
                              <td>
                                  <a href="<?php echo site_url('admin/manage_attendance_view/'.$class_id.'/'.$section_id.'/'.$att_timestamp.'/'.$row['student_id']) ?>" class="btn btn-success btn-sm" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                          <?php }

                          if($tt_owe != 0) {
                          ?>
                            <tr>
                              <td><?php echo 'TRANSPORT FARE'; ?></td>
                              <td align="right"><strong><?php echo numfmt_format_currency($fmt, $tt_owe, $currency); ?></strong></td>
                              <td><?php echo 'As at today'; ?></td>
                              <td>
                                  <a href="<?php echo site_url('admin/manage_attendance_view/'.$class_id.'/'.$section_id.'/'.$att_timestamp.'/'.$row['student_id']) ?>" class="btn btn-success btn-sm" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                            <?php
                              }
                            ?>
                       </tbody>
                     </table>
                </td>

                <td>
                  <table class="table table-bordered" style="margin-top: 20px;" id="payables">
                     <thead>
                       <tr>
                         <th><?php echo get_phrase('invoice'); ?></th>
                         <th style="text-align: right"><?php echo get_phrase('amount'); ?></th>
                         <th><?php echo get_phrase('date'); ?></th>
                         <th><?php echo get_phrase('options'); ?></th>
                       </tr>
                     </thead>
                     <tbody>
                       <?php
                          foreach ($payables_array as $pay):

                            $title_pay = $this->db->get_where('invoice', array('invoice_code' => $pay['invoice_code'], 'student_id' => $row['student_id'], 'due <' => 0))->row()->title;
                            $this->db->select_sum('due');
                            $amount_pay = abs($this->db->get_where('invoice', array('invoice_code' => $pay['invoice_code'], 'student_id' => $row['student_id'], 'due <' => 0))->row()->due);
                            $pdate = $this->db->get_where('invoice', array('invoice_code' => $pay['invoice_code'], 'student_id' => $row['student_id'], 'due <' => 0))->row()->creation_timestamp;
                        ?>
                          <tr>
                            <td><?php echo $pay['invoice_code']; ?></td>
                            <td align="right"><strong><?php echo numfmt_format_currency($fmt, $amount_pay, $currency); ?></strong></td>
                            <td><?php echo date('d M Y', $pdate); ?></td>
                            <td>
                                <a href="#" class="btn btn-danger btn-sm" onclick="invoice_refund_modal('<?=$row['student_id'] ?>')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Refund</a>
                            </td>
                          </tr>
                      <?php endforeach; 

                      if($tf_refund != 0) {
                       ?>

                          <tr>
                            <td><?php echo 'FEEDING FEE'; ?></td>
                            <td align="right"><strong><?php echo numfmt_format_currency($fmt, $tf_refund, $currency); ?></strong></td>
                            <td><?php echo 'As at today'; ?></td>
                            <td>
                                <a href="#" class="btn btn-danger btn-sm" onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'feeding')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                            </td>
                          </tr>
                        <?php }

                        if($tc_refund != 0) {
                        ?>
                          <tr>
                            <td><?php echo 'CLASSES FEE'; ?></td>
                            <td align="right"><strong><?php echo numfmt_format_currency($fmt, $tc_refund, $currency); ?></strong></td>
                            <td><?php echo 'As at today'; ?></td>
                            <td>
                                <a href="#" class="btn btn-danger btn-sm" onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'classes')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                            </td>
                          </tr>
                        <?php }

                        if($tt_refund != 0) {
                        ?>
                          <tr>
                            <td><?php echo 'TRANSPORT FARE'; ?></td>
                            <td align="right"><strong><?php echo numfmt_format_currency($fmt, $tt_refund, $currency); ?></strong></td>
                            <td><?php echo 'As at today'; ?></td>
                            <td>
                                <a href="#" class="btn btn-danger btn-sm" onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'transport')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                            </td>
                          </tr>
                          <?php
                            }
                          ?>
                     </tbody>
                   </table>
                </td>
              </tr>
             </tbody>
           </table>
         </div>

         <!--Payment -->
         <div class="row">
          <?php
            $payments = $this->db->get_where('payment', array(
            'student_id' => $row['student_id']))->result_array();
         ?>
         <h3>Payments:</h3>
         <table class="table table-bordered" style="margin-top: 20px;" id="payment_ta">
           <thead>
             <tr>
               <th>#</th>
               <th><?php echo get_phrase('title'); ?></th>
               <th style="text-align: right"><?php echo get_phrase('amount'); ?></th>
               <th><?php echo get_phrase('date'); ?></th>
               <th><?php echo get_phrase('options'); ?></th>
             </tr>
           </thead>
           <tbody>
             <?php
                $count = 1;
                foreach ($payments as $payment):
              ?>
                <tr>
                  <td><?php echo $count++; ?></td>
                  <td><?php echo $payment['title']; ?></td>
                  <td align="right"><strong><?php echo numfmt_format_currency($fmt, $payment['amount'], $currency); ?></strong></td>
                  <td><?php echo date('d M Y', $payment['timestamp']); ?></td>
                  <td>
                    <?php 
                      if($payment['invoice_code'] != null) {
                        ?>

                        <a href="#" class="btn btn-info"
                          onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/'.$payment['invoice_code']);?>')">
                          <?php echo get_phrase('view_invoice'); ?>
                        </a>
                    <?php
                      }
                    ?>
                  </td>
                </tr>
            <?php endforeach; ?>
           </tbody>
         </table>
      </div>
      </div>
    </div>

    <br>

  </div>
  </header>
</div>
<?php endforeach; ?>

<script type="text/javascript">
 $(function() {
     $('#receivables').dataTable();
     $('#payables').dataTable();
     $('#payment_ta').dataTable();
 });

 function invoice_pay_modal(student_id, date = '', term = '') {

     /** invoice_code = invoice_code.toString();
      let invoice_original_len = '<?php echo $inv_number_len; ?>';
      let current_invoice_len = invoice_code.length;

      if(invoice_code.substring(0, 1) == '_') {
          invoice_code = invoice_code.substring(1);
      } else {
          invoice_code = invoice_code;
      } **/
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date, 'take_payment');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
      }
  }

  function invoice_refund_modal(student_id, date = '', term = '') {

     /** invoice_code = invoice_code.toString();
      let invoice_original_len = '<?php echo $inv_number_len; ?>';
      let current_invoice_len = invoice_code.length;

      if(invoice_code.substring(0, 1) == '_') {
          invoice_code = invoice_code.substring(1);
      } else {
          invoice_code = invoice_code;
      } **/
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_refund/');?>' + student_id + '/' + date, 'make_refund');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_refund/');?>' + student_id + '/' + date + '/' + term, 'make_refund');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_refund/');?>' + student_id, 'make_refund');
      }
  }

  function fct_pay_modal(student_id, type = '') {

    showAjaxModal('<?php echo site_url('modal/popup/modal_fct_pay/');?>' + student_id + '/' + type, 'make_refund');

  }

  function fct_refund_modal(student_id, type = '') {

    showAjaxModal('<?php echo site_url('modal/popup/modal_fct_refund/');?>' + student_id + '/' + type, 'make_refund');

  }
</script>
