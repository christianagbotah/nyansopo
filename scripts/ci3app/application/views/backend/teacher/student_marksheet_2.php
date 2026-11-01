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

<!-- ApexCharts Library -->
<script src="<?php echo base_url('assets/apexchart/apexcharts.min.js'); ?>?v=<?php echo time(); ?>"></script>
<script src="<?php echo base_url('assets/js/modern-marksheet-chart.js'); ?>?v=<?php echo time(); ?>"></script>

<?php 

    $year = $running_year;
    $term = $running_term;
    $sem  = $running_Sem;

    $class_name = $this->crud_model->get_class_name($class_id);


    $student_info = $this->crud_model->get_student_info($student_id);


    foreach ($student_info as $row1):
   // foreach ($exams as $row2):
?>


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

                                   // $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $class_id , $row3['subject_id'] );
                                   // echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                         
                                                echo $this->crud_model->get_grade_level($grade['grade_point']);
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            } 
                                            if($row4['mark_obtained'] == NULL || $row4['mark_obtained'] == '') {
                                                            echo "N/A";
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

                  <hr />

                   <!-- Adjustment started -->

                        <?php

                          $conducts_data = $this->db->get_where('aggregation', array('student_id' => $row1['student_id'], 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->row();

                          // Use 'remarks' column (same as style 1), not 'class_teacher_remarks'
                          $class_teacher_remarks = $conducts_data->remarks ?? '';
                          $head_teacher_remarks = $conducts_data->head_teacher_remarks ?? '';
                          $attitude = $conducts_data->attitude ?? '';
                          $interest = $conducts_data->interest ?? '';
                          $conduct = $conducts_data->conduct ?? '';
                          $days_present = $conducts_data->days_present ?? 0;
                          $days_opened = $conducts_data->days_opened ?? 0;
                          
                          // Calculate student percentage and get auto head teacher remark
                          // Only count subjects where marks have been entered (N/A excluded)
                          $subjects_with_marks = 0;
                          $student_total_marks = 0;
                          
                          $subjects = $this->db->get_where('subject', array(
                              'class_id' => $class_id,
                              'year' => $running_year,
                              'term' => $running_term
                          ))->result_array();
                          
                          foreach ($subjects as $subject) {
                              $mark_query = $this->db->get_where('mark', array(
                                  'subject_id' => $subject['subject_id'],
                                  'exam_id' => $exam_id,
                                  'class_id' => $class_id,
                                  'student_id' => $row1['student_id'],
                                  'year' => $running_year,
                                  'term' => $running_term
                              ));
                              
                              if ($mark_query->num_rows() > 0) {
                                  $mark_row = $mark_query->row();
                                  // Only count if mark_obtained is set (not NULL/empty)
                                  if (isset($mark_row->mark_obtained) && $mark_row->mark_obtained !== '') {
                                      $subjects_with_marks++;
                                      $student_total_marks += $mark_row->mark_obtained;
                                  }
                              }
                          }
                          
                          // Calculate percentage only from subjects with marks (handles missing exams correctly)
                          $student_percentage = ($subjects_with_marks > 0) ? ($student_total_marks / ($subjects_with_marks * 100)) * 100 : 0;
                          $student_percentage = round($student_percentage); // Round to nearest whole number
                          
                          // Get head teacher remark text - handle both ID (new) and text (legacy)
                          $head_teacher_remark_text = '';
                          $head_teacher_remark_id = '';
                          
                          if (!empty($head_teacher_remarks)) {
                              // Check if it's numeric ID (new system) or text (legacy)
                              if (is_numeric($head_teacher_remarks)) {
                                  // It's an ID - fetch the remark text from database
                                  $this->load->model('Head_teacher_remarks_model');
                                  $remark_record = $this->Head_teacher_remarks_model->get_by_id($head_teacher_remarks);
                                  if ($remark_record) {
                                      $head_teacher_remark_text = $remark_record->remark_text;
                                      $head_teacher_remark_id = $remark_record->remark_id;
                                  }
                              } else {
                                  // It's legacy text - use it directly
                                  $head_teacher_remark_text = $head_teacher_remarks;
                              }
                          } else {
                              // No saved remark - get auto-generated one based on percentage
                              $this->load->model('Head_teacher_remarks_model');
                              $auto_head_remark = $this->Head_teacher_remarks_model->find_by_percentage($student_percentage);
                              if ($auto_head_remark) {
                                  $head_teacher_remark_text = $auto_head_remark->remark_text;
                                  $head_teacher_remark_id = $auto_head_remark->remark_id;
                              }
                          }
                          
                          // Get selected interest ID (single selection)
                          $selected_interest_id = '';
                          if(!empty($interest)) {
                              // Check if it's numeric ID (new system) or text (legacy)
                              if(is_numeric($interest)) {
                                  $selected_interest_id = $interest;
                              } else {
                                  // Legacy text - try to match by name
                                  foreach($interest_items as $item) {
                                      if($item->name == $interest) {
                                          $selected_interest_id = $item->id;
                                          break;
                                      }
                                  }
                              }
                          }
                          
                          // Get selected conduct ID (single selection for style 2)
                          $selected_conduct_id = '';
                          if(!empty($conduct)) {
                              // Check if it's numeric ID (new system) or text (legacy)
                              if(is_numeric($conduct)) {
                                  $selected_conduct_id = $conduct;
                              } else {
                                  // Legacy text - try to match by name
                                  foreach($conduct_items as $item) {
                                      if($item->name == $conduct) {
                                          $selected_conduct_id = $item->id;
                                          break;
                                      }
                                  }
                              }
                          }
                        ?>
                     <!--Conducts-->
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <?php echo form_open(site_url('admin/conducts_update_2'), array('id' => 'conducts_form'));?>
                                    <div class="table table-responsive">
                                        

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

                                        <!-- Conducts -->
                                        <table class="table table-bordered table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1">

                                            <tbody>
                                                <tr>

                                                    <td valign="middle" style="background-color: #520452; color: #fff; padding-top: 15px"><label for="attitude" class="form-control-label">ATTITUDE</label></td>

                                                    <td><input type="text" id="attitude" class="form-control" name="attitude" placeholder="DEPENDABLE" value="<?php echo $attitude; ?>" ></td>
                                                                                                        
                                                    <td valign="middle" style="background-color: #520452; color: #fff; padding-top: 15px"><label for="conduct_id" class="form-control-label">CONDUCT</label></td>

                                                    <td>
                                                        <select id="conduct_id" name="conduct_id" class="form-control select2-conduct" style="width: 100%;">
                                                            <option value="">Select Conduct</option>
                                                            <?php foreach($conduct_items as $item): ?>
                                                                <option value="<?php echo $item->id; ?>" <?php echo ($item->id == $selected_conduct_id || $item->name == $conduct) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($item->name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <!-- Keep old conduct field hidden for backward compatibility -->
                                                        <input type="hidden" name="conduct" value="<?php echo htmlspecialchars($conduct); ?>">
                                                    </td>

                                                    <td valign="middle" style="background-color: #520452; color: #fff; padding-top: 15px"><label for="interest_id" class="form-control-label">INTEREST</label></td>

                                                    <td>
                                                        <select id="interest_id" name="interest_id" class="form-control select2-interest" style="width: 100%;">
                                                            <option value="">Select Interest</option>
                                                            <?php foreach($interest_items as $item): ?>
                                                                <option value="<?php echo $item->id; ?>" <?php echo ($item->id == $selected_interest_id) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($item->name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <!-- Keep old interest field hidden for backward compatibility -->
                                                        <input type="hidden" name="interest" value="<?php echo htmlspecialchars($interest); ?>">
                                                    </td>
                                                    
                                                    
                                                </tr>
                                            </tbody>
                                        </table>
                                        
                                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                                        <input type="hidden" name="student_id" value="<?php echo $row1['student_id']; ?>">
                                        <input type="hidden" name="class_id" value="<?php echo $class_id;?>">
                                        
                                        <div class="form-group row">
                                            <div class="form-group col-md-6">
                                                <label class="form-control-label">Class Teacher's Remarks:</label>
                                                <select id="class_teacher_remarks" class="form-control select2 select2-teacher-remarks" name="class_teacher_remarks" 
                                                        data-tags="true" data-placeholder="<?php echo get_phrase('select_or_type_remark'); ?>" 
                                                        style="width: 100%;">
                                                    <option value=""></option>
                                                    <?php foreach($teacher_remark_templates as $template): ?>
                                                        <option value="<?php echo $template->id; ?>"
                                                                data-remark-text="<?php echo htmlspecialchars($template->remark_text); ?>"
                                                                <?php if($class_teacher_remarks == $template->id || $class_teacher_remarks == $template->remark_text) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($template->remark_text); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                    <?php 
                                                    // Check if current remark is a custom text (not ID)
                                                    if($class_teacher_remarks && !is_numeric($class_teacher_remarks)): 
                                                        $template_ids = array_map(function($t) { return $t->id; }, $teacher_remark_templates);
                                                        $template_texts = array_map(function($t) { return $t->remark_text; }, $teacher_remark_templates);
                                                        if(!in_array($class_teacher_remarks, $template_ids) && !in_array($class_teacher_remarks, $template_texts)): 
                                                    ?>
                                                        <!-- Custom remark not in templates -->
                                                        <option value="<?php echo htmlspecialchars($class_teacher_remarks); ?>" selected>
                                                            <?php echo htmlspecialchars($class_teacher_remarks); ?>
                                                        </option>
                                                    <?php endif; endif; ?>
                                                </select>
                                                <input type="hidden" id="class_teacher_remark_id" name="class_teacher_remark_id" value="">
                                                <small class="text-muted"><?php echo get_phrase('select_template_or_type_custom_remark'); ?></small>
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label class="form-control-label">Head Teacher's Remarks:</label>
                                                <input type="text" id="head_teacher_remarks" name="head_teacher_remarks" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($head_teacher_remark_text); ?>" 
                                                       readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                                                <small class="text-muted">
                                                    <?php echo get_phrase('auto_generated_based_on_performance'); ?> 
                                                    (<?php echo number_format($student_percentage, 2); ?>%)
                                                </small>
                                            </div>
                                        </div>
                                        <!-- Adjustment ended -->


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
                        // ApexCharts implementation
                        $(document).ready(function() {
                            var chartData<?php echo $exam_id;?> = {
                                subjects: [
                                    <?php 
                                        $chartSubjects = [];
                                        $classScores = [];
                                        $examScores = [];
                                        foreach ($subjects as $subject) :
                                            $class_mark = $this->crud_model->get_class_score($exam_id, $class_id, $subject['subject_id'], $row1['student_id']);
                                            $exam_mark = $this->crud_model->get_exam_score($exam_id, $class_id, $subject['subject_id'], $row1['student_id']);
                                            
                                            $chartSubjects[] = '"' . substr($subject['name'], 0, 3) . '"';
                                            $classScores[] = $class_mark;
                                            $examScores[] = $exam_mark;
                                        endforeach;
                                        
                                        echo implode(',', $chartSubjects);
                                    ?>
                                ],
                                classScores: [<?php echo implode(',', $classScores); ?>],
                                examScores: [<?php echo implode(',', $examScores); ?>]
                            };
                            
                            // Render chart
                            if (typeof renderModernMarksheetChart === 'function' && typeof ApexCharts !== 'undefined') {
                                renderModernMarksheetChart('chartdiv<?php echo $exam_id;?>', chartData<?php echo $exam_id;?>);
                            } else {
                                console.error('Chart dependencies not loaded. ApexCharts:', typeof ApexCharts, 'Function:', typeof renderModernMarksheetChart);
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

        navigation(url);
        
    });

    //when form is submitted
    $('#conducts_form').submit(function(event) {

        //Scroll to the top
        $('html, body').animate({
            scrollTop: ($('#top').offset().top )
        }, 1000);

        event.preventDefault();

        let exam_id = '<?php echo $exam_id; ?>';

        if(exam_id == '' || exam_id == null) {

            $('#alert_error').css('display', 'block');
            $('#submit_c').val('Sorry you can\'t submit');
            return false;
        } else {

            showAjaxModal_alert('<?=get_phrase('Saving Data...'); ?>', 'Loading');

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

            if(data == 'no exam') {
                showAjaxModal_alert('<?=get_phrase('no exam was selected or found for this entry. Please try again.'); ?>', 'Error');
                setTimeout(() => {
                    navigation('<?php echo site_url('admin/student_marksheet/'.$student_id); ?>');
                }, 3000);
              
            } if(data == 'no mark') {
                showAjaxModal_alert('<?=get_phrase('please_enter marks before proceeding with conducts.'); ?>', 'Error');
                setTimeout(() => {
                    navigation('<?php echo site_url('admin/student_marksheet/'.$student_id); ?>');
                }, 4000);
              
            }  else {
                showAjaxModal_alert(data, 'Success');

                setTimeout(() => {
                    navigation('<?php echo site_url('admin/student_marksheet/'.$student_id); ?>');
                }, 3000);
            }
          })
          .fail(function(err) {
            showAjaxModal_alert(err.responseText, 'Error');
          })
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

    // Initialize Select2 for teacher remarks with template loading
    $(document).ready(function() {
        // Check if device is mobile (detect once for all fields)
        var isMobile = /iPhone|iPad|iPod|Android|webOS|BlackBerry|Windows Phone/i.test(navigator.userAgent) || window.innerWidth <= 768;
        
        if ($('.select2-teacher-remarks').length > 0 && !isMobile) {
            // Load templates first, then initialize Select2
            $.ajax({
                url: '<?php echo site_url('teacher_remarks_templates/get_for_select2'); ?>',
                dataType: 'json',
                success: function(templates) {
                    var groupedData = [];
                    var categories = {
                        'positive': {text: '<?php echo get_phrase('positive_remarks'); ?>', children: []},
                        'neutral': {text: '<?php echo get_phrase('neutral_remarks'); ?>', children: []},
                        'negative': {text: '<?php echo get_phrase('negative_remarks'); ?>', children: []},
                        'other': {text: '<?php echo get_phrase('other'); ?>', children: []}
                    };
                    
                    templates.forEach(function(item) {
                        var cat = item.category || 'other';
                        if (categories[cat]) {
                            categories[cat].children.push({
                                id: item.id,  // Use ID as value
                                text: item.text
                            });
                        }
                    });
                    
                    for (var key in categories) {
                        if (categories[key].children.length > 0) {
                            groupedData.push(categories[key]);
                        }
                    }
                    
                    // Initialize Select2 with loaded data
                    $('.select2-teacher-remarks').select2({
                        data: groupedData,
                        tags: true,
                        tokenSeparators: [','],
                        placeholder: '<?php echo get_phrase('select_or_type_remark'); ?>',
                        allowClear: true,
                        width: '100%'
                    });
                },
                error: function() {
                    // Fallback: Initialize without templates (tags-only mode)
                    $('.select2-teacher-remarks').select2({
                        tags: true,
                        tokenSeparators: [','],
                        placeholder: '<?php echo get_phrase('select_or_type_remark'); ?>',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        }
        
        // Handle remark selection - the value is already ID for templates, text for custom
        $('#class_teacher_remarks').on('change', function() {
            var selectedValue = $(this).val();
            // If numeric, it's an ID; if text, it's a custom remark
            if (selectedValue && is_numeric(selectedValue)) {
                $('#class_teacher_remark_id').val(selectedValue);
            } else {
                $('#class_teacher_remark_id').val('');
            }
        });
        
        // Initialize Select2 for interest items (single select, not multi) - desktop only
        if ($('.select2-interest').length > 0 && !isMobile) {
            $('.select2-interest').select2({
                placeholder: '<?php echo get_phrase('select_one_interest'); ?>',
                allowClear: true,
                width: '100%'
            });
        }
        
        // Initialize Select2 for conduct items - desktop only
        if ($('.select2-conduct').length > 0 && !isMobile) {
            $('.select2-conduct').select2({
                placeholder: '<?php echo get_phrase('select_conduct'); ?>',
                allowClear: true,
                width: '100%'
            });
        }
        
        // Handle conduct selection - set the ID in hidden field
        $('#conduct_id').on('change', function() {
            var selectedValue = $(this).val();
            // Update hidden field for backward compatibility
            if (selectedValue && is_numeric(selectedValue)) {
                $('#conduct_id').closest('form').find('input[name="conduct"]').val(selectedValue);
            }
        });
    });
    
    function is_numeric(str) {
        return /^\d+$/.test(str);
    }
</script>
