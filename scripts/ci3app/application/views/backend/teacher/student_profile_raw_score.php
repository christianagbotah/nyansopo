<style>
  .exam_chart {
    width       : 100%;
    height      : 265px;
    font-size   : 11px;
  }
</style>

<?php

  //AGGREGATION OF MARKS
     //aggregation of core subjects 4
    $total_aggregate = 0;
    $sum_core = 0;
       $this->db->where('exam_id', $exam_id);
       $this->db->where('class_id', $class_id);
       $this->db->where('section_id', $section_id);
       $this->db->where('student_id', $student_id);
       $this->db->where('year', $running_year);
       $this->db->where('sem', $running_sem);
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
       $this->db->where('sem', $running_sem);
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

  $student_info = $this->db->get_where('student', array('student_id' => $student_id))->result_array();
  foreach ($student_info as $row):
    $enroll_info = $this->db->get_where('enroll', array(
      'student_id' => $row['student_id'], 'year' => $running_year));
    $class_id = $enroll_info->row()->class_id;
    $exams = $this->crud_model->get_student_exams($student_id);

    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;
?>
<div class="profile-env">
	<header class="row">
		<div class="col-md-3">
			<center>
        <a href="#">
  				<img src="<?php echo $this->crud_model->get_image_url('student', $student_id, $gender) ;?>" class="img-circle"
          style="width: 60%;" />
  			</a>
        <br>
        <h3>
          <?php echo $row['name']; ?>
        </h3>
        <br>
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
          <a href="<?php echo site_url('teacher/student_information/'.$enroll_info->row()->class_id);?>">
            <?php echo $class_name.' '. $class_name_numeric. ' | '. get_phrase('section').' - '.$section_name; ?>
          </a>
        </span>
      </center>
		</div>
    <div class="col-md-9">

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
				<a href="#tab3" data-toggle="tab" class="btn btn-danger">
					<span class="visible-xs"><i class="entypo-mail"></i></span>
					<span class="hidden-xs"><?php echo get_phrase('exam_marks'); ?></span>
				</a>
			</li>

			<!-- <li class="">
				<a href="#tab4" data-toggle="tab" class="btn btn-default">
					<span class="visible-xs"><i class="entypo-cog"></i></span>
					<span class="hidden-xs"><?php //echo get_phrase('attendance'); ?></span>
				</a>
			</li> -->
      <li class="">
				<a href="#tab5" data-toggle="tab" class="btn btn-default">
					<span class="visible-xs"><i class="entypo-cog"></i></span>
					<span class="hidden-xs"><?php echo get_phrase('payments'); ?></span>
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

			<?php 
          if($class_name == 'CRECHE') { ?>
            <div class="tab-pane" id="tab3">
        <?php foreach ($exams as $row2) {

          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_term = $this->crud_model->get_exams_term($row2['exam_id']);

          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-5 col-lg-5 col-sm-5 col-xs-5"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']); ?></h3></div>
            <div class="col-md-7 col-lg-7 col-sm-7 col-xs-7"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>

          <div class="row">
            <div style="text-align: center">
              <table class="table">
                <thead>
                  <?php 
                    $grading_sys = $this->db->get('grade_creche')->result_array();
                    foreach($grading_sys as $grade): 
                  ?>
                  <tr>
                    <th style="font-size: 16px"><?= $grade['abbrev']; ?></th>
                    <th style="font-size: 16px">-</th>
                    <th style="font-size: 16px"><?= $grade['full_name']; ?></th>
                  </tr>
                <?php endforeach; ?>
                </thead>
              </table>
            </div>
          </div>

           <?php 
              $subj_category = $this->db->get('subject_category_creche')->result_array();
              foreach($subj_category as $cat): ?>

              <div class="row">
                  <table class="table table-bordered">
                    <thead>   
                      <tr>
                        <th><?= $cat['name']; ?></th>
                        <th width="80"><?= 'GRADING'; ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $subjects_creche = $this->db->get_where('subject_creche' , array(
                            'class_id' => $class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
                        ))->result_array();
                        foreach($subjects_creche as $subj): ?>
                    <tr>
                      <td><?= $subj['name']; ?></td>
                      <td style="letter-spacing: 5px;">
                        <?php 
                            $grading = $this->db->get_where('mark' , array(
                                                    'subject_id' => $subj['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $grading->num_rows() > 0) {
                                            $grading_array = $grading->result_array();
                                            foreach ($grading_array as $row4) {
                                                echo $this->db->get_where('grade_creche', array('grade_id' => $row4['test1']))->row()->abbrev;
                                            }
                                        }else{
                                          echo "Pending...";
                                        }
                          ?>
                      </td>
                    </tr>

                  <?php endforeach; ?>
                    </tbody>
                    
                  </table>
              </div>

          <?php endforeach; ?>

          <?php 
    if($this->db->get_where('subject_creche' , array('class_id' => $class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
  ?>

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
                            $subjects = $this->db->get_where('subject_creche' , array(
                                'class_id' => $class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term
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
                   </table><br>

                 <?php } ?>
                 
           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_results_sheet_preview_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>"
               class="btn btn-info" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php } ?>
      </div>
            <?php
          } 

          //JHS
          else if($class_name == 'JHSS') {
            ?>
            <div class="tab-pane" id="tab3">
              <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"></div>
              <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4">
                <a class="btn btn-success btn-lg" href="<?php echo site_url('admin/cummulative_reports/' .$student_id) ?>" target="_blank">GenerateCummulative Reports</a>
              </div>

        <?php foreach ($exams as $row2) { 
          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_sem = $this->crud_model->get_exams_sem($row2['exam_id']);
          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-5 col-lg-5 col-sm-5 col-xs-5"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']); ?></h3></div>
            <div class="col-md-7 col-lg-7 col-sm-7 col-xs-7"><h3 style="text-align: right;"><?php echo get_phrase('semester:').' '.$running_sem. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                                'class_id' => $class_id , 'year' => $running_year, 'sem' => $running_sem
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
                                                                        'sem' => $running_sem));
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
                                                                        'sem' => $running_sem));
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
                                                                        'sem' => $running_sem));
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
                                 $this->crud_model->get_total_score($row2['exam_id'] , $class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_sem);
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
            <?php
          } //end of JHS

          //for the general ones....
          else {
            ?>
            <div class="tab-pane" id="tab3">
              <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"></div>
              <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4">
                <a class="btn btn-success btn-lg" href="<?php echo site_url('admin/cummulative_reports/' .$student_id) ?>" target="_blank">GenerateCummulative Reports</a>
              </div>
              
        <?php foreach ($exams as $row2) { 
          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_term = $this->crud_model->get_exams_term($row2['exam_id']);
          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-5 col-lg-5 col-sm-5 col-xs-5"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']); ?></h3></div>
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
               <a href="<?php echo site_url('admin/student_results_sheet_print_view/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>"
               class="btn btn-info" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php } ?>
      </div>
            <?php
          }

      ?>

			<!-- <div class="tab-pane" id="tab4">
				attendance
			</div> -->
			<div class="tab-pane" id="tab5">
				<?php
          $payments = $this->db->get_where('payment', array(
            'student_id' => $row['student_id'], 'year' => $running_year, 'term' => $running_term
          ))->result_array();
         ?>
         <table class="table table-bordered" style="margin-top: 20px;" id="payment_ta">
           <thead>
             <tr>
               <th>#</th>
               <th><?php echo get_phrase('title'); ?></th>
               <th><?php echo get_phrase('amount'); ?></th>
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
                  <td><?php echo $payment['amount']; ?></td>
                  <td><?php echo date('d M Y', $payment['timestamp']); ?></td>
                  <td>
                    <a href="#" class="btn btn-info"
                      onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/'.$payment['invoice_code'].'/'.$running_year.'/'.$running_term);?>')">
                      <?php echo get_phrase('view_invoice'); ?>
                    </a>
                  </td>
                </tr>
            <?php endforeach; ?>
           </tbody>
         </table>
			</div>
		</div>

		<br>

	</div>
	</header>
</div>
<?php endforeach; 
  
  //insert the raw score into aggregation table
       $this->db->set('raw_score', $raw_score);
       $this->db->where('exam_id', $exam_id);
       $this->db->where('class_id', $class_id);
       $this->db->where('section_id', $section_id);
       $this->db->where('student_id', $student_id);
       $this->db->where('year', $running_year);
       $this->db->where('term', $running_term);
       $this->db->update('aggregation');
?>

<script type="text/javascript">

 $(function() {
     $('#payment_ta').dataTable();
 });

   $(document).ready(function() {
        var total_aggregate = $('.total_aggregate').text();
        if(total_aggregate >= 15) {
            $('.total_aggregate').css('color', 'red');
        }else{
            $('.total_aggregate').css('color', 'green');
        }
    });
</script>