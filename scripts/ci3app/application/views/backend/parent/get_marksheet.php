            <?php 

                $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
                $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;

                $class_id3  = $this->db->get_where('enroll' , array('student_id' => $student_id3 , 'year' => $year, 'term' => $term))->row()->class_id;

                $class_name_numeric = $this->db->get_where('class', array(
                  'class_id' => $class_id3
                ))->row()->name_numeric;

                $class_name = $this->db->get_where('class', array(
                  'class_id' => $class_id3
                ))->row()->name;

                $section_id3  = $this->db->get_where('enroll' , array('class_id' => $class_id3, 'student_id' => $student_id3, 'year' => $year, 'term' => $term))->row()->section_id;

                $exam_id = $exam_id3;
                $class_id = $class_id3;
                $student_id = $student_id3;

                $class_score_total = 0;
                $exam_score_total =0; 
                $total_marks = 0;

                $display = 'block';


                //conduct checks
                $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                     $ret_remarks = '';

                     $this->db->where('category_id', '2');
                     $exams = $this->db->get('exam')->result_array();
                     

                foreach ($exams as $exam):
                    $this->db->where('exam_id' , $exam['exam_id']);
                    $this->db->where('student_id' , $student_id3);
                    $this->db->where('class_id' , $class_id3);
                    $this->db->where('section_id' , $section_id3);
                    $this->db->where('year' , $year);
                    $this->db->where('term' , $term);
                    $get_marks = $this->db->get('mark');
                    $marks = $get_marks->result_array();

                    if(count($marks) > 0):
            ?>
                <div class="tab-pane" id="<?php echo $exam['exam_id'];?>">

                    <?php if($class_name == 'CRECHE' || ($class_name == 'NURSERY' && $class_name_numeric == 1)) { ?>

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
                    'class_id' => $class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
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
                                                        'exam_id' => $exam_id,
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
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>
                   <?php 
                    }
                   ?>

                        <?php }
                            else {
                        ?>
                    <table class="table table-bordered responsive">
                        <thead>
                            <tr>
                                <th width="15%" style="font-weight: bold;">CLASS</th>
                                <th style="font-weight: bold;">SUBJECT</th>
                                <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                                <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                                <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                                <th style="text-align: center; font-weight: bold;">GRADE</th>
                                <th style="text-align: center; font-weight: bold;">REMARK</th>
                                <th style="text-align: center; font-weight: bold;">POSITION</th>
                            </tr>
                            <tr>
                                <?php 

                                    if($get_marks->num_rows() < 1) {

                                        $total_aggregate = 'N/A';
                                        $sum_core = 0;
                                        $sum_best2 = 0;
                                       // $display = 'none';
                                        ?>
                                        <td colspan="8" style="background-color: #f2f2f2;"><h4 style="text-align: center; color: red;">No Record Found!</h4></td>
                                        <?php
                                    }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($marks as $mark):

                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $mark['class_id']))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $mark['name'], 'name_numeric' => $mark['name_numeric']))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                            ?>
                            <tr>

                                <td>
                                    <?php echo $this->db->get_where('class' , array(
                                        'class_id' => $mark['class_id']
                                    ))->row()->name.' '.$this->db->get_where('class' , array(
                                        'class_id' => $mark['class_id']
                                    ))->row()->name_numeric.$sec_name;?>
                                </td>
                                <td>
                                    <?php $subj = $this->db->get_where('subject' , array(
                                        'subject_id' => $mark['subject_id'], 'year' => $year, 'term' => $term
                                    ))->row()->name;
                                    if($subj == 'Ict') {
                                        echo strtoupper($subj);
                                    }else {
                                        echo $subj;
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;"><?php echo $mark['class_score']; $class_score_total +=$mark['class_score']; $total_marks +=$mark['class_score']; ?></td>
                                <td style="text-align: center;"><?php echo $mark['exam_score']; $exam_score_total +=$mark['exam_score']; $total_marks +=$mark['exam_score'];?></td>
                                <td style="text-align: center;"><?php echo $mark['mark_obtained']; $total_marks;?></td>
                                <td style="text-align: center;"><?php 
                                    $grade = $this->crud_model->get_grade($mark['mark_obtained']);
                                    echo $grade['grade_point'];
                                ?></td>
                                <td style="text-align: center;"><?php 
                                    $grade = $this->crud_model->get_grade($mark['mark_obtained']);
                                    echo $grade['name'];
                                ?></td>
                                <td style="text-align: center;">
                                    <?php
                                 $this->crud_model->get_total_score($exam['exam_id'] , $mark['class_id'] , $mark['subject_id'], $student_id3, $year, $term);
                                 ?></td>
                            </tr>

                            
                        <?php endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php if($get_marks->num_rows() < 1) {echo 'N/A';}else{echo $class_score_total;}  ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php if($get_marks->num_rows() < 1) {echo 'N/A';}else{echo $exam_score_total;} ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php if($get_marks->num_rows() < 1) {echo 'N/A';}else{echo $total_marks;} ?></td>
                            <td colspan="3"></td>
                        </tr>
                        </tbody>
                    </table>

                     <!--Conducts-->
                     <?php }
                                $conducts = $this->db->get_where('aggregation', array('student_id' => $student_id3, 'class_id' => $class_id3, 'exam_id' => $exam['exam_id'], 'term' => $term, 'year' => $year));
                                $conducts_data = $conducts->result_array();
                        
                        

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
                        <div class="row" style="display: <?= $display; ?>">
                            <div class="col-lg-12 col-md-12">
                                <?php echo form_open(site_url('admin/conducts_update'), array('id' => 'c_form'));?>
                                    <div class="table table-responsive">
                                        <table class="table table-bordered table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1">
                                            <caption align="center">CONDUCTS OF <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id3))->row()->name);?></b></caption>
                                            <tbody>
                                                <tr>
                                                    <td><input type="checkbox" id="ex" class="" name="conducts[]" value="Exemplinary Behaviour" <?php echo $checked1; ?> disabled></td>
                                                    <td><label for="ex" class="form-control-label">Exemplinary Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="wm" class="" name="conducts[]" value="Well Mannered" <?php echo $checked2;?> disabled></td>
                                                    <td><label for="wm" class="form-control-label">Well Mannered</label></td>
                                                    
                                                    <td><input type="checkbox" id="hn" class="" name="conducts[]" value="Honest" <?php echo $checked3;?> disabled></td>
                                                    <td><label for="hn" class="form-control-label">Honest</label></td>
                                                   
                                                    <td><input type="checkbox" id="tw" class="" name="conducts[]" value="Trustworthy" <?php echo $checked4;?> disabled></td>
                                                     <td><label for="tw" class="form-control-label">Trustworthy</label></td>

                                                     <td><input type="checkbox" id="relu" class="" name="conducts[]" value="Reluctant" <?php echo $checked13;?> disabled></td>
                                                    <td><label for="relu" class="form-control-label">Reluctant</label></td>
                                                </tr>
                                                <tr> 
                                                    <td><input type="checkbox" id="sb" class="" name="conducts[]" value="Satisfactory Behaviour" <?php echo $checked5;?> disabled></td>
                                                    <td><label for="sb" class="form-control-label">Satisfactory Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="vn" class="" name="conducts[]" value="Very Neat" <?php echo $checked6;?> disabled></td>
                                                    <td><label for="vn" class="form-control-label">Very Neat</label></td>
                                                    
                                                    <td><input type="checkbox" id="pu" class="" name="conducts[]" value="Puntual" <?php echo $checked7;?> disabled></td>
                                                    <td><label for="pu" class="form-control-label">Puntual</label></td>

                                                    <td><input type="checkbox" id="inn" class="" name="conducts[]" value="Innovative" <?php echo $checked8;?> disabled></td>
                                                    <td><label for="inn" class="form-control-label">Innovative</label></td>

                                                    <td><input type="checkbox" id="rese" class="" name="conducts[]" value="Reserved" <?php echo $checked14;?> disabled></td>
                                                    <td><label for="rese" class="form-control-label">Reserved</label></td>
                                                    
                                                </tr>
                                                <tr>    
                                                    <td><input type="checkbox" id="rs" class="" name="conducts[]" value="Resourceful" <?php echo $checked9;?> disabled></td>
                                                    <td><label for="rs" class="form-control-label">Resourceful</label></td>
                                                    
                                                    <td><input type="checkbox" id="fr" class="" name="conducts[]" value="Friendly" <?php echo $checked10;?> disabled></td>
                                                    <td><label for="fr" class="form-control-label">Friendly</label></td>
                                                    
                                                    <td><input type="checkbox" id="ind" class="" name="conducts[]" value="Industrious" <?php echo $checked11;?> disabled></td>
                                                    <td><label for="ind" class="form-control-label">Industrious</label></td>
                                                    
                                                    <td><input type="checkbox" id="ob" class="" name="conducts[]" value="Obedient" <?php echo $checked12;?> disabled></td>
                                                    <td><label for="ob" class="form-control-label">Obedient</label></td>

                                                    <td><input type="checkbox" id="pro" class="" name="conducts[]" value="Promising" <?php echo $checked15;?> disabled></td>
                                                    <td><label for="pro" class="form-control-label">Promising</label></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="form-group">
                                            <label class="form-control-label">Class Teacher's Remarks:</label>
                                            <textarea class="form-control" disabled name="teacher_remarks" rows="3"><?php echo $ret_remarks;?></textarea>
                                        </div>
                                    </div>
                                <?php echo form_close();?>
                            </div>
                        </div>
                    <a href="<?php echo site_url('parents/student_marksheet_print_view/'.$student_id3.'/'.$exam['exam_id']);?>"
                        class="btn btn-primary" target="_blank">
                        <?php echo get_phrase('print_marksheet');?>
                    </a>
                </div>

                <?php 
                //Now, clear all the variables before loading other info
                $class_score_total = 0;
                $exam_score_total =0; 
                $total_marks = 0;

                $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                    $ret_remarks = '';
                ?>
            <?php
                else:
                    echo '<h4 style="color: red; text-align: center">No Data Available!</h4>';
                    break;
                endif;
             endforeach;?>

<script type="text/javascript">

        var tab_id = '<?php echo $exam_id3; ?>';
        var year = '<?php echo $student_id3; ?>';

        if(tab_id != '' || tab_id != null) {
         $('#' + tab_id).removeClass('tab-pane');
         //alert(year);
        }else{
            toastr.error('No Record Found');
        }

       

</script>

