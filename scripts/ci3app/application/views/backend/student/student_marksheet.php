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

    $year = $running_year;
    $term = $running_term;
    $sem  = $running_Sem;

    $class_name = $this->crud_model->get_class_name($class_id);


    $student_info = $this->crud_model->get_student_info($student_id);


   // $exams         = $this->crud_model->get_exams();
    if($class_name == 'JHSS') {


   foreach ($student_info as $row1):
   // foreach ($exams as $row2):
?>

<!--
<hr>
<div class="row">
    <div class="col-md-12">
        <?php echo form_open(site_url('admin/student_results_sheet'));?>
        <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label"><?php echo get_phrase('class');?></label>
                    <select name="class_id" class="form-control select2" id="class_id" onchange="showTermSem($(this).val())">
                        <option value=""><?php echo get_phrase('select_a_class');?></option>
                        <?php 
                        $this->db->select('class_id');
                        $this->db->distinct();
                        $this->db->where('student_id', $student_id);
                        $classes = $this->db->get('mark')->result_array();
                        foreach($classes as $row):
                        ?>
                        <?php
                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                            $c_name = $this->crud_model->get_class_name($row['class_id']);
                            $c_name_numeric = $this->crud_model->get_class_name_numeric($row['class_id']);

                            $class_has_more_sections = $this->db->get_where('class', array('name' => $c_name, 'name_numeric' => $c_name_numeric))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                        ?>
                            <option value="<?php echo $row['class_id'];?>"
                                <?php if ($class_id == $row['class_id']) echo 'selected';?>>
                                    <?php echo $c_name.' '.$c_name_numeric.$sec_name;?>
                            </option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2" id="term_holder">
                <div class="form-group">
                    <label  class="control-label"><?php echo get_phrase('selected_term');?></label>
                      <select name="term" class="form-control select2"  onchange="get_exam_type()" id="term">
                      <option value="" disabled="true"><?php echo get_phrase('select_term');?></option>
                      <?php for($i = 1; $i <= 3; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($term == $i) {echo 'selected'; } else if($running_sem == $i) { echo 'selected';};?>>
                              <?php echo $i;?>
                          </option>
                      <?php endfor;?>
                      </select>
                </div>
            </div>

            <div class="col-md-2" id="sem_holder">
                <div class="form-group">
                    <label  class="control-label"><?php echo get_phrase('selected_semester');?></label>
                      <select name="sem" class="form-control select2"  onchange="get_exam_type_sem()" id="sem">
                      <option value="" disabled="true"><?php echo get_phrase('select_semester');?></option>
                      <?php for($i = 1; $i <= 2; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($sem == $i) echo 'selected';?>>
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
                         // echo populate_academic_year();
                        ?>
                      </select>
                </div>
            </div>
            <div id="exam_holder">
            <div class="col-md-3">
                <div class="form-group" id="exam_holder_g">
                <label class="control-label"><?php echo get_phrase('exam');?></label>
                 <select name="exam_id" class="form-control" id="exam_id">
                    <?php 
                    $exams = $this->db->get_where('exam', array('term' => $term, 'year' => $year, 'category_id !=' => '1'));
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

                <div class="form-group" id="exam_holder_jhs">
                <label class="control-label"><?php echo get_phrase('exam');?></label>
                 <select name="exam_id" class="form-control" id="exam_id_sem">
                    <?php 
                    $exams = $this->db->get_where('exam', array('sem' => $sem, 'year' => $year, 'category_id !=' => '1'));
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
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <div class="col-md-3" style="margin-top: 20px;">
                <button type="submit" id="submit" class="btn btn-info"><?php echo get_phrase('view_results');?></button>
            </div>
        </div>
        <?php echo form_close();?>
    </div>
</div>-->
<hr><!--//end of results check-->


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
        <div class="panel panel-primary panel-shadow" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $this->crud_model->get_exams_name($exam_id);?></div>
            </div>
            <div class="panel-body">
                
                
               <div class="col-md-12">
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
                                                                    'year' => $running_year,
                                                                        'sem' => $running_sem));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo round($row4['class_score'], 2);
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
                                                                    'year' => $running_year,
                                                                        'sem' => $running_sem));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo round($row4['exam_score'], 2);
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
                                                                    'year' => $running_year,
                                                                        'sem' => $running_sem));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo round($row4['mark_obtained'], 2);
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
                                 $this->crud_model->get_total_score($exam_id , $class_id , $row3['subject_id'], $row1['student_id'], $running_year, $running_sem);
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
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?round($class_score_total, 2):'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?round($exam_score_total, 2):'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?round($total_marks, 2):'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>

<div class="row">
    
</div>
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

                        $conducts_data = $this->db->get_where('aggregation', array('student_id' => $row1['student_id'], 'class_id' => $class_id, 'exam_id' => $exam_id, 'sem' => $running_sem, 'year' => $running_year))->result_array();

                         $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                         $ret_remarks = '';

                         // Fetch days_opened from terms table for audit trail
                         $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $running_term))->row();
                         $days_opened = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : 0;

                         // Calculate days_present from attendance table (COUNT present + late statuses)
                         $this->db->select('COUNT(*) as days_present');
                         $this->db->from('attendance');
                         $this->db->where('student_id', $row1['student_id']);
                         $this->db->where('year', $running_year);
                         $this->db->where('term', $running_term);
                         $this->db->where_in('status', array(1, 3)); // Status: 1=Present, 3=Late
                         $attendance_query = $this->db->get();
                         $days_present = $attendance_query->row() ? (int)$attendance_query->row()->days_present : 0;

                        foreach ($conducts_data as $conduct) {

                            $ret_remarks = $conduct['remarks'];
                            $exam_id2 = $conduct['exam_id'];
                            $student_id2 = $conduct['student_id'];
                            $class_id2 = $conduct['class_id'];
                            $sem2 = $conduct['sem'];
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
                                <?php echo form_open(site_url('admin/conducts_update'), array('id' => 'conducts_form'));?>
                                    <div class="table table-responsive">
                                        <table class="table table-bordered table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1">
                                            <caption>CONDUCTS OF <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></b></caption>
                                            <tbody>
                                                <tr>
                                                    <td><input type="checkbox" id="ex" class="form-check" name="conducts[]" value="Exemplinary Behaviour" <?php echo $checked1; ?>></td>
                                                    <td><label for="ex" class="form-control-label">Exemplinary Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="wm" class="form-check" name="conducts[]" value="Well Mannered" <?php echo $checked2;?>></td>
                                                    <td><label for="wm" class="form-control-label">Well Mannered</label></td>
                                                    
                                                    <td><input type="checkbox" id="hn" class="form-check" name="conducts[]" value="Honest" <?php echo $checked3;?>></td>
                                                    <td><label for="hn" class="form-control-label">Honest</label></td>
                                                   
                                                    <td><input type="checkbox" id="tw" class="form-check" name="conducts[]" value="Trustworthy" <?php echo $checked4;?>></td>
                                                     <td><label for="tw" class="form-control-label">Trustworthy</label></td>

                                                     <td><input type="checkbox" id="relu" class="form-check" name="conducts[]" value="Reluctant" <?php echo $checked13;?>></td>
                                                    <td><label for="relu" class="form-control-label">Reluctant</label></td>
                                                </tr>
                                                <tr> 
                                                    <td><input type="checkbox" id="sb" class="form-check" name="conducts[]" value="Satisfactory Behaviour" <?php echo $checked5;?>></td>
                                                    <td><label for="sb" class="form-control-label">Satisfactory Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="vn" class="form-check" name="conducts[]" value="Very Neat" <?php echo $checked6;?>></td>
                                                    <td><label for="vn" class="form-control-label">Very Neat</label></td>
                                                    
                                                    <td><input type="checkbox" id="pu" class="form-check" name="conducts[]" value="Puntual" <?php echo $checked7;?>></td>
                                                    <td><label for="pu" class="form-control-label">Puntual</label></td>

                                                    <td><input type="checkbox" id="inn" class="form-check" name="conducts[]" value="Innovative" <?php echo $checked8;?>></td>
                                                    <td><label for="inn" class="form-control-label">Innovative</label></td>

                                                    <td><input type="checkbox" id="rese" class="form-check" name="conducts[]" value="Reserved" <?php echo $checked14;?>></td>
                                                    <td><label for="rese" class="form-control-label">Reserved</label></td>
                                                    
                                                </tr>
                                                <tr>    
                                                    <td><input type="checkbox" id="rs" class="form-check" name="conducts[]" value="Resourceful" <?php echo $checked9;?>></td>
                                                    <td><label for="rs" class="form-control-label">Resourceful</label></td>
                                                    
                                                    <td><input type="checkbox" id="fr" class="form-check" name="conducts[]" value="Friendly" <?php echo $checked10;?>></td>
                                                    <td><label for="fr" class="form-control-label">Friendly</label></td>
                                                    
                                                    <td><input type="checkbox" id="ind" class="form-check" name="conducts[]" value="Industrious" <?php echo $checked11;?>></td>
                                                    <td><label for="ind" class="form-control-label">Industrious</label></td>
                                                    
                                                    <td><input type="checkbox" id="ob" class="form-check" name="conducts[]" value="Obedient" <?php echo $checked12;?>></td>
                                                    <td><label for="ob" class="form-control-label">Obedient</label></td>

                                                    <td><input type="checkbox" id="pro" class="form-check" name="conducts[]" value="Promising" <?php echo $checked15;?>></td>
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
                                              <td><input type="number" size="20" value="<?php echo $days_opened; ?>" class="form-control" name="days_opened" id="days_opened" readonly style="background-color: #f3f4f6; cursor: not-allowed;" title="Auto-fetched from term settings"></td>
                                            
                                              <td><input type="number" size="20" value="<?php echo $days_present; ?>" class="form-control" name="days_present" id="days_present" readonly style="background-color: #f3f4f6; cursor: not-allowed;" title="Auto-calculated from attendance records"></td>
                                              
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
                                            <textarea class="form-control" name="teacher_remarks" rows="3"><?php echo $ret_remarks;?></textarea>
                                        </div>
                                        <div>
                                            <div class="alert alert-danger alert-dismissable" style="display: none;" id="alert_error">
                                                <strong>Please enter student's marks before proceeding with the conducts. Go to >>Examination >>Manage Exam Marks</strong>
                                            </div>
                                        </div>
                                        <input type="submit" name="save_conducts" id="submit_c" class="btn btn-primary btn-lg btn-md btn-sm" value="<?php echo get_phrase('save_conducts_&_remarks');?>" style="position: relative; float: right; bottom: 20px; right: 10px; margin-top: 30px;">
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
                <a href="<?php echo site_url('admin/student_marksheet_print_view/'.$student_id.'/'.$exam_id);?>" 
                        class="btn btn-primary" target="_blank" style="position: relative; float: right; bottom: 20px; right: 10px;">
                        <?php echo get_phrase('print_marksheet');?>
                    </a>
            <br><br>        
        </div>  
    </div>
</div>
<?php
   // endforeach;
        endforeach;
//end of the JHS
    } else {
    foreach ($student_info as $row1):
   // foreach ($exams as $row2):
?>

<!--
<hr>
<div class="row">
    <div class="col-md-12">
        <?php echo form_open(site_url('admin/student_results_sheet'));?>
        <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label"><?php echo get_phrase('class');?></label>
                    <select name="class_id" class="form-control select2" id="class_id" onchange="showTermSem($(this).val())">
                        <option value=""><?php echo get_phrase('select_a_class');?></option>
                        <?php 
                        $this->db->select('class_id');
                        $this->db->distinct();
                        $this->db->where('student_id', $student_id);
                        $classes = $this->db->get('mark')->result_array();
                        foreach($classes as $row):
                        ?>
                        <?php
                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                            $c_name = $this->crud_model->get_class_name($row['class_id']);
                            $c_name_numeric = $this->crud_model->get_class_name_numeric($row['class_id']);

                            $class_has_more_sections = $this->db->get_where('class', array('name' => $c_name, 'name_numeric' => $c_name_numeric))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                        ?>
                            <option value="<?php echo $row['class_id'];?>"
                                <?php if ($class_id == $row['class_id']) echo 'selected';?>>
                                    <?php echo $c_name.' '.$c_name_numeric.$sec_name;?>
                            </option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2" id="term_holder">
                <div class="form-group">
                    <label  class="control-label"><?php echo get_phrase('selected_term');?></label>
                      <select name="term" class="form-control select2"  onchange="get_exam_type()" id="term">
                      <option value="" disabled="true"><?php echo get_phrase('select_term');?></option>
                      <?php for($i = 1; $i <= 3; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($term == $i) {echo 'selected'; } else if($running_term == $i) { echo 'selected';};?>>
                              <?php echo $i;?>
                          </option>
                      <?php endfor;?>
                      </select>
                </div>
            </div>

            <div class="col-md-2" id="sem_holder">
                <div class="form-group">
                    <label  class="control-label"><?php echo get_phrase('selected_semester');?></label>
                      <select name="sem" class="form-control select2"  onchange="get_exam_type_sem()" id="sem">
                      <option value="" disabled="true"><?php echo get_phrase('select_semester');?></option>
                      <?php for($i = 1; $i <= 2; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($sem == $i) echo 'selected';?>>
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
                         // echo populate_academic_year();
                        ?>
                      </select>
                </div>
            </div>
            <div id="exam_holder">
            <div class="col-md-3">
                <div class="form-group" id="exam_holder_g">
                <label class="control-label"><?php echo get_phrase('exam');?></label>
                 <select name="exam_id" class="form-control" id="exam_id">
                    <?php 
                    $exams = $this->db->get_where('exam', array('term' => $term, 'year' => $year, 'category_id !=' => '1'));
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

                <div class="form-group" id="exam_holder_jhs">
                <label class="control-label"><?php echo get_phrase('exam');?></label>
                 <select name="exam_id" class="form-control" id="exam_id_sem">
                    <?php 
                    $exams = $this->db->get_where('exam', array('sem' => $sem, 'year' => $year, 'category_id !=' => '1'));
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
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <div class="col-md-3" style="margin-top: 20px;">
                <button type="submit" id="submit" class="btn btn-info"><?php echo get_phrase('view_results');?></button>
            </div>
        </div>
        <?php echo form_close();?>
    </div>
</div>-->
<hr><!--//end of results check-->

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
        <div class="panel panel-primary panel-shadow" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $this->crud_model->get_exams_name($exam_id);?></div>
            </div>
            <div class="panel-body">
                
                
               <div class="col-md-12">
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
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo round($row4['class_score'], 2);
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
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo round($row4['exam_score'], 2);
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
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo round($row4['mark_obtained'], 2);
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
                                 $this->crud_model->get_total_score($exam_id , $class_id , $row3['subject_id'], $row1['student_id'], $running_year, $running_term);
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
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?round($class_score_total, 2):'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?round($exam_score_total, 2):'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?round($total_marks, 2):'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>

<div class="row">
    
</div>
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

                        $conducts_data = $this->db->get_where('aggregation', array('student_id' => $row1['student_id'], 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->result_array();

                         $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                         $ret_remarks = '';

                         // Fetch days_opened from terms table
                         $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $running_term))->row();
                         $days_opened = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : 0;

                         // Calculate days_present from attendance table
                         $this->db->select('COUNT(*) as days_present');
                         $this->db->from('attendance');
                         $this->db->where('student_id', $row1['student_id']);
                         $this->db->where('year', $running_year);
                         $this->db->where('term', $running_term);
                         $this->db->where_in('status', array(1, 3)); // Status: 1=Present, 3=Late
                         $attendance_query = $this->db->get();
                         $days_present = $attendance_query->row() ? (int)$attendance_query->row()->days_present : 0;

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
                                <?php echo form_open(site_url('admin/conducts_update'), array('id' => 'conducts_form'));?>
                                    <div class="table table-responsive">
                                        <table class="table table-bordered table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1">
                                            <caption>CONDUCTS OF <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></b></caption>
                                            <tbody>
                                                <tr>
                                                    <td><input type="checkbox" id="ex" class="form-check" name="conducts[]" value="Exemplinary Behaviour" <?php echo $checked1; ?>></td>
                                                    <td><label for="ex" class="form-control-label">Exemplinary Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="wm" class="form-check" name="conducts[]" value="Well Mannered" <?php echo $checked2;?>></td>
                                                    <td><label for="wm" class="form-control-label">Well Mannered</label></td>
                                                    
                                                    <td><input type="checkbox" id="hn" class="form-check" name="conducts[]" value="Honest" <?php echo $checked3;?>></td>
                                                    <td><label for="hn" class="form-control-label">Honest</label></td>
                                                   
                                                    <td><input type="checkbox" id="tw" class="form-check" name="conducts[]" value="Trustworthy" <?php echo $checked4;?>></td>
                                                     <td><label for="tw" class="form-control-label">Trustworthy</label></td>

                                                     <td><input type="checkbox" id="relu" class="form-check" name="conducts[]" value="Reluctant" <?php echo $checked13;?>></td>
                                                    <td><label for="relu" class="form-control-label">Reluctant</label></td>
                                                </tr>
                                                <tr> 
                                                    <td><input type="checkbox" id="sb" class="form-check" name="conducts[]" value="Satisfactory Behaviour" <?php echo $checked5;?>></td>
                                                    <td><label for="sb" class="form-control-label">Satisfactory Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="vn" class="form-check" name="conducts[]" value="Very Neat" <?php echo $checked6;?>></td>
                                                    <td><label for="vn" class="form-control-label">Very Neat</label></td>
                                                    
                                                    <td><input type="checkbox" id="pu" class="form-check" name="conducts[]" value="Puntual" <?php echo $checked7;?>></td>
                                                    <td><label for="pu" class="form-control-label">Puntual</label></td>

                                                    <td><input type="checkbox" id="inn" class="form-check" name="conducts[]" value="Innovative" <?php echo $checked8;?>></td>
                                                    <td><label for="inn" class="form-control-label">Innovative</label></td>

                                                    <td><input type="checkbox" id="rese" class="form-check" name="conducts[]" value="Reserved" <?php echo $checked14;?>></td>
                                                    <td><label for="rese" class="form-control-label">Reserved</label></td>
                                                    
                                                </tr>
                                                <tr>    
                                                    <td><input type="checkbox" id="rs" class="form-check" name="conducts[]" value="Resourceful" <?php echo $checked9;?>></td>
                                                    <td><label for="rs" class="form-control-label">Resourceful</label></td>
                                                    
                                                    <td><input type="checkbox" id="fr" class="form-check" name="conducts[]" value="Friendly" <?php echo $checked10;?>></td>
                                                    <td><label for="fr" class="form-control-label">Friendly</label></td>
                                                    
                                                    <td><input type="checkbox" id="ind" class="form-check" name="conducts[]" value="Industrious" <?php echo $checked11;?>></td>
                                                    <td><label for="ind" class="form-control-label">Industrious</label></td>
                                                    
                                                    <td><input type="checkbox" id="ob" class="form-check" name="conducts[]" value="Obedient" <?php echo $checked12;?>></td>
                                                    <td><label for="ob" class="form-control-label">Obedient</label></td>

                                                    <td><input type="checkbox" id="pro" class="form-check" name="conducts[]" value="Promising" <?php echo $checked15;?>></td>
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
                                              <td><input type="number" size="20" value="<?php echo $days_opened; ?>" class="form-control" name="days_opened" id="days_opened" readonly style="background-color: #f3f4f6; cursor: not-allowed;" title="Auto-fetched from term settings"></td>
                                            
                                              <td><input type="number" size="20" value="<?php echo $days_present; ?>" class="form-control" name="days_present" id="days_present" readonly style="background-color: #f3f4f6; cursor: not-allowed;" title="Auto-calculated from attendance records"></td>
                                              
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
                                            <textarea class="form-control" name="teacher_remarks" rows="3"><?php echo $ret_remarks;?></textarea>
                                        </div>
                                        <div>
                                            <div class="alert alert-danger alert-dismissable" style="display: none;" id="alert_error">
                                                <strong>Please enter student's marks before proceeding with the conducts. Go to >>Examination >>Manage Exam Marks</strong>
                                            </div>
                                        </div>
                                        <input type="submit" name="save_conducts" id="submit_c" class="btn btn-primary btn-lg btn-md btn-sm" value="<?php echo get_phrase('save_conducts_&_remarks');?>" style="position: relative; float: right; bottom: 20px; right: 10px; margin-top: 30px;">
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
                <a href="<?php echo site_url('admin/student_marksheet_print_view/'.$student_id.'/'.$exam_id);?>" 
                        class="btn btn-primary" target="_blank" style="position: relative; float: right; bottom: 20px; right: 10px;">
                        <?php echo get_phrase('print_marksheet');?>
                    </a>
            <br><br>        
        </div>  
    </div>
</div>
<?php
   // endforeach;
        endforeach;
    }
?>

<script type="text/javascript">
    //as soon as page is loaded
    $(function() {

        let exam_id = '<?php echo $exam_id; ?>';
        let class_id = '<?php echo $class_id; ?>';
        if(exam_id == '' || exam_id == null) {
            $('#alert_error').css('display', 'block');
            $('#submit_c').attr('disabled', 'disabled');
        }

        showTermSem(class_id)//called to show either semester or term


    });

    //redirect if a student is changed
    $('#other_students').change(function(event) {
        /* Act on the event */
        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
        }, 1000); 

        const url = '<?php echo site_url('admin/student_marksheet/') ?>' + $(this).val();
        location.replace(url);

        showAjaxModal_alert('Please wait a minute <i class="fa fa-spinner fa-pulse"></i>', 'Loading');
    });

    //when form is submitted
    $('#conducts_form').submit(function(event) {
        let exam_id = '<?php echo $exam_id; ?>';
        if(exam_id == '' || exam_id == null) {
            event.preventDefault();
            $('#alert_error').css('display', 'block');
            $('#submit_c').val('Sorry you can\'t submit');
            return false;
        }
    });
    function reload_exams() {
        var exam_id = $('#exam_id')
        var submit_button = $('#submit')
        submit_button.html("Reload Exams");
        submit_button.addClass("btn btn-danger");

    }

    function get_exam_type() {
            var $term = $('#term').val();
            var $year = $('#year').val();

            $.ajax({
                url: '<?php echo site_url('admin/get_exam_type/');?>'+ $term +'/'+ $year,
                success: function(response) {
                    $('#exam_id').html(response);
                }
            });
        }

    function get_exam_type_sem() {
            var $sem = $('#sem').val();
            var $year = $('#year').val();

            $.ajax({
                url: '<?php echo site_url('admin/get_exam_type_sem/');?>'+ $sem +'/'+ $year,
                success: function(response) {
                    $('#exam_id_sem').html(response);

                    let exam_val = $('#exam_id_sem').val();
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
