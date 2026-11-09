<style>
    .exam_chart {
    width           : 100%;
        height      : 265px;
        font-size   : 11px;
}
  #hint_table{
    position: relative;
    left: 50px;
    top: 30px;
    border: 0px;
  }

  #hint_table tr th{
    width: 10px;
  }

  #circle1, #circle2{
    border: 2px solid #000000;
    width: 15px;
    height: 15px;
    border-radius: 14px;
  }

  #circle1{
    background-color: #7f8c8d;
    border: 2px solid #7f8c8d;
  }

  #circle2{
    background-color: #34495e;
    border: 2px solid #34495e;
  }
</style>

<?php
    echo '<script src="'. base_url(). 'assets/js/neon-custom-ajax.js"></script>';

    $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
    $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;

    $student_info = $this->crud_model->get_student_info($student_id);

     
   // $exams         = $this->crud_model->get_exams();
    foreach ($student_info as $row1):
   // foreach ($exams as $row2):
?>
<hr>
<div class="row">

    <div class="col-md-12 -col-sm-12 col-lg-12" style="display: flex;">
        <div class="col-md-6 -col-sm-6 col-lg-6"></div>
        <div class="col-md-6 -col-sm-6 col-lg-6 form-group row" style="padding-bottom: 10px; display: flex;">
            <label class="col-md-4 -col-sm-4 col-lg-4 control-label pt-50">STUDENTS LIST:</label>
            <select class="col-md-8 -col-sm-8 col-lg-8 form-control select2" id="other_students">
            <?php
                foreach($other_students as $os) {?>
                    <option value="<?=$os['student_id']?>" <?=$student_id == $os['student_id'] ? 'selected' : ''; ?>><?=$this->crud_model->getStudentNameById($os['student_id'])?></option>
            <?php
                }
            ?>
            </select>
        </div>
    </div>

    <div class="col-md-12">
        <?php echo form_open(site_url('admin/student_results_sheet_creche'), ['id' => 'results_form']);?>
        <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label"><?php echo get_phrase('class');?></label>
                    <select name="class_id" class="form-control select2" id="class_id">
                        <option value=""><?php echo get_phrase('select_a_class');?></option>
                        <?php 
                        $classes = $this->db->get_where('class', array('name' => 'CRECHE'))->result_array();
                        foreach($classes as $row):
                        ?>
                        <?php
                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                        ?>
                            <option value="<?php echo $row['class_id'];?>"
                                <?php if ($class_id == $row['class_id']) echo 'selected';?>>
                                    <?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?>
                            </option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label  class="control-label"><?php echo get_phrase('selected_term');?></label>
                      <select name="term" class="form-control select2"  onchange="get_exam_type()" id="term">
                      <option value="" disabled="true"><?php echo get_phrase('select_term');?></option>
                      <?php for($i = 1; $i <= 3; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($term == $i) echo 'selected';?>>
                              <?php echo $i;?>
                          </option>
                      <?php endfor;?>
                      </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                  <label  class="control-label"><?php echo get_phrase('selected_year');?></label>
                      <select name="year" class="form-control select2"  onchange="get_exam_type()" id="year">
                      <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
                      <?php
                          echo populate_academic_year('', $year);
                        ?>
                      </select>
                </div>
            </div>
            <div id="exam_holder">
            <div class="col-md-3">
                <div class="form-group">
                <label class="control-label"><?php echo get_phrase('exam');?></label>
                 <select name="exam_id" class="form-control select2" id="exam_id">
                    <?php 
                    $exams = $this->db->get_where('exam', array('category_id !=' => '1'));
                     $array_exams  = $exams->result_array();

                    if($exams->num_rows() < 1) {
                       ?>
                       <option value=""><?php echo get_phrase('no_exam_was_found'); ?></option>
                       <?php
                    }
                    ?>
                        <option value=""><?php echo get_phrase('select_exam_to_view'); ?></option>
                    <?php
                    foreach($array_exams as $row):
                    ?>
                        <option value="<?php echo $row['exam_id'];?>"
                            <?php if ($exam_id == $row['exam_id']) echo 'selected';?>>
                                <?php echo $row['name'];?>
                        </option>
                    <?php
                    endforeach;
                    ?>
                </select>
                </div>
            </div>
            <input type="hidden" name="operation" value="selection">
            <input type="hidden" name="student_id" id="student_id" value="<?php echo $student_id; ?>">
            <div class="col-md-3" style="margin-top: 20px;">
                <button type="submit" id="submit" class="btn btn-info"><?php echo get_phrase('view_results');?></button>
            </div>
        </div>
        <?php echo form_close();?>
    </div>
</div>
<hr><!--//end of results check-->



<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-shadow" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $this->crud_model->get_exams_name_for_results($exam_id). ' - ['.getFullClassName($class_id, $section_id) .']';?></div>
            </div>

            <?php if($exam_rows != 0) { ?>
            <div class="panel-body">
                
                
               <div class="col-md-12">

                <div class="row">
  <div>
    <table class="table table-bordered" style="margin-top: 10px;">
      <thead>
          <tr>
              <th style="font-size: 16px">ABBREVIATION</th>
              <th style="font-size: 16px">MEANING</th>
          </tr>
      </thead>
      <tbody>
        <?php 
          $grading_sys = $this->db->get('grade_creche')->result_array();
          foreach($grading_sys as $grade): 
        ?>
        <tr>
          <td style="font-size: 16px"><?= $grade['abbrev']; ?></td>
          <td style="font-size: 16px"><?= $grade['full_name']; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
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
                    'class_id' => $class_id , 'category_id' => $cat['category_id'], 'year' => $year, 'term' => $term
                ))->result_array();
                foreach($subjects_creche as $subj): ?>
            <tr>
              <td><?= $subj['name']; ?></td>
              <td style="letter-spacing: 5px;">
                <?php 
                    $grading = $this->db->get_where('mark' , array(
                                            'subject_id' => $subj['subject_id'],
                                                'exam_id' => $exam_id,
                                                    'class_id' => $class_id,
                                                        'student_id' => $student_id , 
                                                            'year' => $year,
                                                                'term' => $term));
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

                            $sn = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $sn;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $exam_id,
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $year,
                                                                        'term' => $term));
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
                                                        'exam_id' => $exam_id,
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $year,
                                                                        'term' => $term));
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
                                                        'exam_id' => $exam_id,
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $year,
                                                                        'term' => $term));
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
                                 $this->crud_model->get_total_score($exam_id , $class_id , $row3['subject_id'], $row1['student_id'], $year, $term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        
                          $sn++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>

                   <?php 
                      }
                   ?>

                   <hr />

                  <!-- <?php// echo get_phrase('total_marks');?> : <?php echo $total_marks;?>
                   <br>
                   <?php //echo get_phrase('average_grade_point');?> : 
                        <?php 
                           /** $this->db->where('class_id' , $class_id);
                            $this->db->where('year' , $running_year);
                            $this->db->from('subject');
                            $number_of_subjects = $this->db->count_all_results();
                            echo ($total_grade_point / $number_of_subjects);
                            **/
                        ?>.//Hide the total and agpa-->

                        <?php
                        $conducts_data = $this->db->get_where('aggregation', array('student_id' => $row1['student_id'], 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $term, 'year' => $year))->result_array();

                          $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                          $ret_remarks = '';

                          $days_opened = $this->db->get_where('aggregation', array('student_id' => $row1['student_id'], 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $term, 'year' => $year))->row()->days_opened;

                          $days_present = $this->db->get_where('aggregation', array('student_id' => $row1['student_id'], 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $term, 'year' => $year))->row()->days_present;

                        foreach ($conducts_data as $conduct) {

                            $ret_remarks = $conduct['remarks'];
                            $exam_id2 = $conduct['exam_id'];
                            $student_id2 = $conduct['student_id'];
                            $class_id2 = $conduct['class_id'];
                            $term2 = $conduct['term'];
                            $year2 = $conduct['year'];
                            
                            for($i = 1; $i <= 15; $i++){
                                    
                                    if($conduct['c'.$i] == 'Exemplinary Behaviour'){
                                        $checked1 = 'checked';
                                    }elseif($conduct['c'.$i] == 'Well Mannered'){
                                        $checked2 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Honest') {
                                        $checked3 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Trustworthy') {
                                        $checked4 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Satisfactory Behaviour') {
                                        $checked5 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Very Neat') {
                                        $checked6 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Puntual') {
                                        $checked7 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Innovative') {
                                        $checked8 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Resourceful') {
                                        $checked9 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Friendly') {
                                        $checked10 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Industrious') {
                                        $checked11 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Obedient') {
                                        $checked12 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Reluctant') {
                                        $checked13 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Reserved') {
                                        $checked14 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Promising') {
                                        $checked15 = 'checked';
                                    }
                                }   
                           
                        }

                        ?>
                     <!--Conducts-->
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <?php echo form_open(site_url('admin/conducts_update'));?>
                                    <div class="table table-responsive">
                                        <table class="table table-bordered table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1">
                                            <caption>CONDUCTS OF <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></b></caption>
                                            <tbody>
                                                <tr>
                                                    <td><input type="checkbox" id="ex" class="form-check" name="conducts[]" value="Exemplinary Behaviour" <?php echo $checked1; ?> disabled></td>
                                                    <td><label for="ex" class="form-control-label">Exemplinary Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="wm" class="form-check" name="conducts[]" value="Well Mannered" <?php echo $checked2;?> disabled></td>
                                                    <td><label for="wm" class="form-control-label">Well Mannered</label></td>
                                                    
                                                    <td><input type="checkbox" id="hn" class="form-check" name="conducts[]" value="Honest" <?php echo $checked3;?> disabled></td>
                                                    <td><label for="hn" class="form-control-label">Honest</label></td>
                                                   
                                                    <td><input type="checkbox" id="tw" class="form-check" name="conducts[]" value="Trustworthy" <?php echo $checked4;?> disabled></td>
                                                     <td><label for="tw" class="form-control-label">Trustworthy</label></td>

                                                     <td><input type="checkbox" id="relu" class="form-check" name="conducts[]" value="Reluctant" <?php echo $checked13;?> disabled></td>
                                                    <td><label for="relu" class="form-control-label">Reluctant</label></td>
                                                </tr>
                                                <tr> 
                                                    <td><input type="checkbox" id="sb" class="form-check" name="conducts[]" value="Satisfactory Behaviour" <?php echo $checked5;?> disabled></td>
                                                    <td><label for="sb" class="form-control-label">Satisfactory Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="vn" class="form-check" name="conducts[]" value="Very Neat" <?php echo $checked6;?> disabled></td>
                                                    <td><label for="vn" class="form-control-label">Very Neat</label></td>
                                                    
                                                    <td><input type="checkbox" id="pu" class="form-check" name="conducts[]" value="Puntual" <?php echo $checked7;?> disabled></td>
                                                    <td><label for="pu" class="form-control-label">Puntual</label></td>

                                                    <td><input type="checkbox" id="inn" class="form-check" name="conducts[]" value="Innovative" <?php echo $checked8;?> disabled></td>
                                                    <td><label for="inn" class="form-control-label">Innovative</label></td>

                                                    <td><input type="checkbox" id="rese" class="form-check" name="conducts[]" value="Reserved" <?php echo $checked14;?> disabled></td>
                                                    <td><label for="rese" class="form-control-label">Reserved</label></td>
                                                    
                                                </tr>
                                                <tr>    
                                                    <td><input type="checkbox" id="rs" class="form-check" name="conducts[]" value="Resourceful" <?php echo $checked9;?> disabled></td>
                                                    <td><label for="rs" class="form-control-label">Resourceful</label></td>
                                                    
                                                    <td><input type="checkbox" id="fr" class="form-check" name="conducts[]" value="Friendly" <?php echo $checked10;?> disabled></td>
                                                    <td><label for="fr" class="form-control-label">Friendly</label></td>
                                                    
                                                    <td><input type="checkbox" id="ind" class="form-check" name="conducts[]" value="Industrious" <?php echo $checked11;?> disabled></td>
                                                    <td><label for="ind" class="form-control-label">Industrious</label></td>
                                                    
                                                    <td><input type="checkbox" id="ob" class="form-check" name="conducts[]" value="Obedient" <?php echo $checked12;?> disabled></td>
                                                    <td><label for="ob" class="form-control-label">Obedient</label></td>

                                                    <td><input type="checkbox" id="pro" class="form-check" name="conducts[]" value="Promising" <?php echo $checked15;?> disabled></td>
                                                    <td><label for="pro" class="form-control-label">Promising</label></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!--Attendance by input method-->
                                        <table class="table table-bordered table-striped" >
                                          <caption><h4>Student's Attendance</h4></caption>
                                          <thead>
                                            <tr>
                                              <th>Number of Days Opened</th>
                                              <th>Number of Days Present</th>
                                              <th>Number of Days Absent</th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                            <tr>
                                              <td><input type="number" size="20" value="<?php echo $days_opened; ?>"  onkeyup="change_days_absent()" class="form-control" name="days_opened" id="days_opened"></td>
                                            
                                              <td><input type="number" size="20" value="<?php echo $days_present; ?>"  onkeyup="change_days_absent()" class="form-control" name="days_present" id="days_present"></td>
                                              
                                              <td><input type="number" size="20" value="<?php echo $days_opened - $days_present; ?>" class="form-control" name="days_absent" id="days_absent" readonly="readonly"></td>
                                            </tr>
                              
                                          </tbody>
                                        </table>
                                        <!--add simple script here-->
                                        <script type="text/javascript">
                                          function change_days_absent() {
                                            let days_opened = Number($('td #days_opened').val());
                                            let days_present = Number($('#days_present').val());
                                            let days_absent = days_opened - days_present;

                                            $('#days_absent').val(days_absent);  
                                          }
                                        </script>


                                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                                        <input type="hidden" name="student_id" value="<?php echo $row1['student_id']; ?>">
                                        <input type="hidden" name="class_id" value="<?php echo $class_id;?>">
                                        <div class="form-group">
                                            <label class="form-control-label">Class Teacher's Remarks:</label>
                                            <textarea class="form-control" name="teacher_remarks" rows="3" disabled><?php echo $ret_remarks;?></textarea>
                                        </div>

                                       <!--======No more editing of results since they have been issued already===== -->
                                       <div id="submit_con">
                                           <input type="submit" name="save_conducts" class="btn btn-primary btn-lg btn-md btn-sm" value="<?php echo get_phrase('save_conducts_&_remarks');?>" style="position: relative; float: right; bottom: 20px; right: 10px; margin-top: 30px;">
                                       <!-- --//Cannot edit results here-->
                                       </div>
                                        
                                    </div>
                                <?php echo form_close();?>
                            </div>
                        </div>

                        <?php

                        ?>

                    <br> <br>
               </div>

               <div class="col-md-12">
                   <div id="chartdiv<?php echo $exam_id;?>" class="exam_chart"></div>
                       <script type="text/javascript">
                        var chart<?php echo $exam_id;?> = AmCharts.makeChart("chartdiv<?php echo $exam_id;?>", {
                                "theme": "none",
                                "type": "serial",
                                "dataProvider": [
                                        <?php 
                                            foreach ($subjects as $subject) :
                                        ?>
                                        {
                                            "subject": "<?php echo substr($subject['name'], 0, 3);?>",
                                            "class_score": 
                                            <?php
                                                $class_mark = $this->crud_model->get_class_score( $exam_id , $class_id , $subject['subject_id'] , $row1['student_id']);
                                                echo $class_mark;
                                            ?>,
                                            "exam_score": 
                                            <?php
                                                $exam_mark = $this->crud_model->get_exam_score( $exam_id , $class_id , $subject['subject_id'], $row1['student_id'] );
                                                echo $exam_mark;
                                            ?>
                                        },
                                        <?php 
                                            endforeach;

                                        ?>
                                    
                                ],
                                "valueAxes": [{
                                    "stackType": "3d",
                                    "unit": "%",
                                    "position": "left",
                                    "title": "Class score vs Exam Score"
                                }],
                                "startDuration": 1,
                                "graphs": [{
                                    "balloonText": "Class score in [[category]]: <b>[[value]]</b>",
                                    "fillAlphas": 0.9,
                                    "lineAlpha": 0.2,
                                    "title": "2004",
                                    "type": "column",
                                    "fillColors":"#7f8c8d",
                                    "valueField": "class_score"
                                }, {
                                    "balloonText": "Exam score in [[category]]: <b>[[value]]</b>",
                                    "fillAlphas": 0.9,
                                    "lineAlpha": 0.2,
                                    "title": "2005",
                                    "type": "column",
                                    "fillColors":"#34495e",
                                    "valueField": "exam_score"
                                }],
                                "plotAreaFillAlphas": 0.1,
                                "depth3D": 20,
                                "angle": 45,
                                "categoryField": "subject",
                                "categoryAxis": {
                                    "gridPosition": "start"
                                },
                                "exportConfig":{
                                    "menuTop":"20px",
                                    "menuRight":"20px",
                                    "menuItems": [{
                                        "format": 'png'   
                                    }]  
                                }
                            });
                      
                    </script>
                    
                    
               </div>
                
               <div class="row">
                        <div class="col-lg-2 col-md-3 col-sm-4 col-xs-5">
                            <table class="table" id="hint_table">
                                <caption>Hints</caption>
                                <tr>
                                   <th><div id="circle1"></th> 
                                   <td><b><i>Class Score</i></b></td>
                                </tr>
                                <tr>
                                   <th><div id="circle2"></th> 
                                   <td><b><i>Exam Score</i></b></td>
                                </tr>
                            </table>
                        </div>
                    </div>
            </div>

            <br><br> <br><br>
                <a href="<?php echo site_url('admin/student_marksheet_print_view_creche/'.$student_id.'/'.$exam_id);?>" 
                        class="btn btn-primary" target="_blank" style="position: relative; float: right; bottom: 20px; right: 10px;">
                        <?php echo get_phrase('print_marksheet');?>
                    </a>
            <br><br> 

            <?php
            } else {
                echo '<center><h3 style="color: red">No Record Found!</h3></center>';
            }
        ?>          
        </div>  
    </div>
</div>
<?php
   // endforeach;
        endforeach;
?>

<script type="text/javascript">
    function reload_exams() {
        var exam_id = $('#exam_id')
        var submit_button = $('#submit')
        submit_button.html("Reload Exams");
        submit_button.addClass("btn btn-danger");

    }

    //redirect if a student is changed
    $('#other_students').change(function(event) {
        /* Act on the event */
        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
        }, 1000); 

        //assign the student id
        $('#student_id').val($(this).val());
        $('#results_form').submit();
        
    });

    //when results form is submitted
    $('#results_form').submit(function(event) {
        event.preventDefault();

       /* let exam_id = '<?php echo $exam_id; ?>';
        if(exam_id == '' || exam_id == null) {
            event.preventDefault();
            $('#alert_error').css('display', 'block');
            $('#submit_c').val('Sorry you can\'t submit');
            return false;
        } else {*/

          $('#main_page').empty();

            //SHOW LOADER
          $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px; ">Fetching Exam Results...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');

            let formUrl = $(this).attr('action');

            $.ajax({
              url: formUrl,
              type: 'POST',
              dataType: 'html',
              data: new FormData(this),
              cache: false,
              contentType: false,
              processData: false
          })
          .done(function(data) {

            $('#main_page').empty();
            $('#main_page').html(data);
          });
        //}
    });

    //if term is the same as the running term, remove the disabled attr
    $(document).ready(function() {
        $(".select2").select2({
            theme: "classic"
        });
        
        var selected_term = <?php echo $term; ?>;
        var running_term = <?php echo $running_term; ?>;
        var selected_year = '<?php echo $year; ?>';
        var running_year = '<?php echo $running_year; ?>';
        $('#submit_con').css('display', 'none');

        if(selected_term == running_term && selected_year == running_year) {
            $('input[type="checkbox"]').removeAttr('disabled');
            $('textarea').removeAttr('disabled');
            $('#submit_con').css('display', 'block');
        }
    });

    function get_exam_type() {
            var $term = $('#term').val();
            var $year = $('#year').val();

            $.ajax({
                url: '<?php echo site_url('admin/get_exam_type/');?>'+ $term +'/'+ $year,
                success: function(response) {
                    $('#exam_id').html(response);

                    let exam_val = $('#exam_id').val();
                    if(exam_val == '') {
                      $('#submit').attr('disabled', 'disabled');
                      $('#submit').text('Nothing to search');

                    } else {
                      $('#submit').removeAttr('disabled');
                      $('#submit').text('View Results');
                    }
                }
            });
        }
</script>
