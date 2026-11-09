<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<style>
  .exam_chart { width: 100%; height: 265px; font-size: 11px; }
  .tab-btn { transition: all 0.2s; }
  .tab-btn.active { border-bottom: 3px solid #3b82f6; }
</style>

<?php
  $student_info = $this->db->get_where('student', array('student_id' => $student_id))->result_array();
  foreach ($student_info as $row):
    $enroll_info = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'year' => $running_year));
    $class_id = $enroll_info->row()->class_id;
    $exams = $this->crud_model->get_student_exams($student_id);
    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;
    $class_name_numeric = $this->db->get_where('class', array('class_id' => $enroll_info->row()->class_id))->row()->name_numeric;
    $class_name = $this->db->get_where('class', array('class_id' => $enroll_info->row()->class_id))->row()->name;
    $section_name = $this->db->get_where('section', array('section_id' => $enroll_info->row()->section_id))->row()->name;
?>

<div class="bg-white rounded-lg shadow-sm">
  <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-t-lg">
    <div class="flex flex-col md:flex-row items-center gap-6">
      <img src="<?php echo $this->crud_model->get_image_url('student', $student_id, $gender);?>" class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover" />
      <div class="text-center md:text-left">
        <h2 class="text-3xl font-bold mb-2"><?php echo $row['name']; ?></h2>
        <a href="<?php echo site_url('teacher/student_information/'.$enroll_info->row()->class_id);?>" class="inline-block bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
          <?php echo $class_name.' '.$class_name_numeric. ' | '. get_phrase('section').' - '.$section_name; ?>
        </a>
      </div>
    </div>
  </div>

  <div class="p-6">

    <div class="flex flex-wrap gap-2 border-b mb-6">
      <button onclick="showTab('tab1')" class="tab-btn active px-6 py-3 font-medium text-blue-600" id="btn-tab1">
        <i class="entypo-home md:hidden"></i>
        <span class="hidden md:inline"><?php echo get_phrase('basic_info'); ?></span>
      </button>
      <button onclick="showTab('tab2')" class="tab-btn px-6 py-3 font-medium text-gray-600 hover:text-blue-600" id="btn-tab2">
        <i class="entypo-user md:hidden"></i>
        <span class="hidden md:inline"><?php echo get_phrase('parent_info'); ?></span>
      </button>
      <button onclick="showTab('tab3')" class="tab-btn px-6 py-3 font-medium text-gray-600 hover:text-blue-600" id="btn-tab3">
        <i class="entypo-mail md:hidden"></i>
        <span class="hidden md:inline"><?php echo get_phrase('exam_marks'); ?></span>
      </button>
      <button onclick="showTab('tab5')" class="tab-btn px-6 py-3 font-medium text-gray-600 hover:text-blue-600" id="btn-tab5">
        <i class="entypo-credit-card md:hidden"></i>
        <span class="hidden md:inline"><?php echo get_phrase('payments'); ?></span>
      </button>
    </div>

    <div class="tab-content">
      <div class="tab-pane" id="tab1">
        <?php
          // Get enrollment info for transport and dormitory
          $enrollment_query = $this->crud_model->getStudentCurrentEnrollmentStatusRow($row['student_id']);
          
          $basic_info_titles = ['name','parent', 'class', 'section', 'email', 'phone', 'address', 'blood_group', 'gender', 'birthday', 'transport', 'dormitory', 'special_diet'];
          $basic_info_values = [
            $row['name'], 
            $row['parent_id'] == NULL ? '' : $this->db->get_where('parent', array('parent_id' => $row['parent_id']))->row()->name,
            $class_name.' '.$class_name_numeric, 
            $section_name, 
            $row['email'], 
            $row['phone'] == NULL ? '' : $row['phone'], 
            $row['address'] == NULL ? '' : $row['address'], 
            $row['blood_group'] == NULL ? '' : $row['blood_group'], 
            $row['sex'] == NULL ? '' : $row['sex'], 
            $row['birthday'],
            (isset($enrollment_query->transport_id) && $enrollment_query->transport_id) ? $this->db->get_where('transport', array('transport_id' => $enrollment_query->transport_id))->row()->route_name : '',
            (isset($enrollment_query->dormitory_id) && $enrollment_query->dormitory_id) ? $this->db->get_where('dormitory', array('dormitory_id' => $enrollment_query->dormitory_id))->row()->name : '',
            $row['special_diet'] == 0 ? 'NO' : 'YES'
          ];
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <?php for ($i=0; $i < count($basic_info_titles); $i++) { ?>
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="text-sm text-gray-600 mb-1"><?php echo get_phrase($basic_info_titles[$i]); ?></div>
              <div class="font-medium text-gray-900"><?php echo $basic_info_values[$i] ?: 'N/A'; ?></div>
            </div>
          <?php } ?>
        </div>
      </div>
      <div class="tab-pane hidden" id="tab2">
        <?php if ($row['parent_id'] == NULL) { ?>
          <div class="text-center py-12 bg-gray-50 rounded-lg">
            <i class="entypo-user text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-600"><?php echo get_phrase('parent_information_is_not_available'); ?></p>
          </div>
        <?php } else {
            $parent_info = $this->db->get_where('parent', array('parent_id' => $row['parent_id']))->result_array();
            $parent_info_titles = ['name', 'email', 'phone', 'address', 'profession'];
            foreach ($parent_info as $info) {
              $parent_info_values = [$info['name'], $info['email'], $info['phone'] == NULL ? '' : $info['phone'],
              $info['address'] == NULL ? '' : $info['address'], $info['profession'] == NULL ? '' : $info['profession']];
            }
          ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php for ($i=0; $i < count($parent_info_titles); $i++) { ?>
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-600 mb-1"><?php echo get_phrase($parent_info_titles[$i]); ?></div>
                <div class="font-medium text-gray-900"><?php echo $parent_info_values[$i] ?: 'N/A'; ?></div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>


       <?php 
          if($class_name == 'CRECHE') { ?>
            <div class="tab-pane hidden" id="tab3">
        <?php foreach ($exams as $row2) {

          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_term = $this->crud_model->get_exams_term($row2['exam_id']);

          $this_class_id = $this->crud_model->get_exams_class_id($row2['exam_id'], $student_id);
          $this_class_numeric = $this->crud_model->get_class_name_numeric($this_class_id);
          $this_class_name = $this->crud_model->get_class_name($this_class_id);
          $this_sec_name = $this->crud_model->get_class_section($this_class_id);

          $full_class_name = $this_class_name.' '.$this_class_numeric.$this_sec_name;

          

          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                                                echo (is_array($grade) && isset($grade['grade_point'])) ? $grade['grade_point'] : 'N/A';
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
                                                echo (is_array($grade) && isset($grade['name'])) ? $grade['name'] : 'N/A';
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
               <a href="<?php echo site_url('teacher/student_marksheet_print_view_creche/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#"
               class="btn btn-info" target="_self" data-url="<?php echo site_url('teacher/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
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
            <div class="tab-pane hidden" id="tab3">
              <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"></div>
              <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4">
                <a class="btn btn-success btn-lg" href="<?php echo site_url('admin/cummulative_reports/' .$student_id) ?>" target="_blank">GenerateCummulative Reports</a>
              </div>
        <?php foreach ($exams as $row2) { 
          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_sem = $this->crud_model->get_exams_sem($row2['exam_id']);
          $exam_class_id = $this->crud_model->get_exams_class_id($row2['exam_id'], $student_id);
          $exam_class_numeric = $this->crud_model->get_class_name_numeric($exam_class_id);
          $exam_class_name = $this->crud_model->get_class_name($exam_class_id);

          $exam_sec_name = $this->crud_model->get_class_section($exam_class_id);
          $full_class_name = $this_class_name.' '.$exam_class_numeric.$this_sec_name;

          //INCASE THE STUDENT HAD BEEN IN CRECHE AND  BASIC SCHOOLS
          //INCASE THIS STUDENT WAS IN CRECHE BEFORE
          if($exam_class_name == 'CRECHE') {
            ?>
              <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                            'class_id' => $exam_class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
                        ))->result_array();
                        foreach($subjects_creche as $subj): ?>
                    <tr>
                      <td><?= $subj['name']; ?></td>
                      <td style="letter-spacing: 5px;">
                        <?php 
                            $grading = $this->db->get_where('mark' , array(
                                                    'subject_id' => $subj['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
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
    if($this->db->get_where('subject_creche' , array('class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
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
                                'class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo (is_array($grade) && isset($grade['grade_point'])) ? $grade['grade_point'] : 'N/A';
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
                                                echo (is_array($grade) && isset($grade['name'])) ? $grade['name'] : 'N/A';
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
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
               <a href="<?php echo site_url('teacher/student_marksheet_print_view_creche/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#"
               class="btn btn-info" target="_self" data-url="<?php echo site_url('teacher/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
            <?php
            //END OF CRECHE EXAMS FOR BASICS
          } else if($exam_class_name == 'BASIC') {
          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                                'class_id' => $exam_class_id , 'year' => $running_year, 'term' => $running_term
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo (is_array($grade) && isset($grade['grade_point'])) ? $grade['grade_point'] : 'N/A';
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
                                                echo (is_array($grade) && isset($grade['name'])) ? $grade['name'] : 'N/A';
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
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
               <a href="<?php echo site_url('teacher/student_marksheet_print_view/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#" data-url="<?php echo site_url('teacher/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" class="btn btn-info" onclick="loadExamResults($(this).attr('data-url'))" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>

           <!--END OF BASIC SCHOOL RESULTS-->
        <?php } else {

          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('semester:').' '.$running_sem. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                                'class_id' => $exam_class_id , 'year' => $running_year, 'sem' => $running_sem
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo (is_array($grade) && isset($grade['grade_point'])) ? $grade['grade_point'] : 'N/A';
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
                                                echo (is_array($grade) && isset($grade['name'])) ? $grade['name'] : 'N/A';
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_sem);
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
               <a href="<?php echo site_url('teacher/student_marksheet_print_view/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#" data-url="<?php echo site_url('teacher/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_sem.'/'.$running_year);?>" class="btn btn-info" target="_self" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php } 
        //END OF JHS RESULTS
        } ?>
      </div>
            <?php
          } //end of JHS

          //for the general ones....
          else {
            ?>
            <div class="tab-pane hidden" id="tab3">
              <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"></div>
              <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4">
                <a class="btn btn-success btn-lg" href="<?php echo site_url('admin/cummulative_reports/' .$student_id) ?>" target="_blank">GenerateCummulative Reports</a>
              </div>
              
        <?php foreach ($exams as $row2) { 
          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_term = $this->crud_model->get_exams_term($row2['exam_id']);

          $exam_class_id = $this->crud_model->get_exams_class_id($row2['exam_id'], $student_id);
          $exam_class_numeric = $this->crud_model->get_class_name_numeric($exam_class_id);
          $exam_class_name = $this->crud_model->get_class_name($exam_class_id);

          $exam_sec_name = $this->crud_model->get_class_section($exam_class_id);
          $full_class_name = $exam_class_name.' '.$exam_class_numeric.$exam_sec_name;

          //INCASE THIS STUDENT WAS IN CRECHE BEFORE
          if($exam_class_name == 'CRECHE') {
            ?>
              <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                            'class_id' => $exam_class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
                        ))->result_array();
                        foreach($subjects_creche as $subj): ?>
                    <tr>
                      <td><?= $subj['name']; ?></td>
                      <td style="letter-spacing: 5px;">
                        <?php 
                            $grading = $this->db->get_where('mark' , array(
                                                    'subject_id' => $subj['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
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
    if($this->db->get_where('subject_creche' , array('class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
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
                                'class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo (is_array($grade) && isset($grade['grade_point'])) ? $grade['grade_point'] : 'N/A';
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
                                                echo (is_array($grade) && isset($grade['name'])) ? $grade['name'] : 'N/A';
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
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
               <a href="<?php echo site_url('teacher/student_marksheet_print_view_creche/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#"
               class="btn btn-info" target="_self" data-url="<?php echo site_url('teacher/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
            <?php
            //END OF CRECHE EXAMS FOR BASICS
          } else {
          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
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
                                'class_id' => $exam_class_id , 'year' => $running_year, 'term' => $running_term
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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
                                                            'class_id' => $exam_class_id,
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

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo (is_array($grade) && isset($grade['grade_point'])) ? $grade['grade_point'] : 'N/A';
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
                                                echo (is_array($grade) && isset($grade['name'])) ? $grade['name'] : 'N/A';
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
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
               <a href="<?php echo site_url('teacher/student_marksheet_print_view/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#" data-url="<?php echo site_url('teacher/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" class="btn btn-info" onclick="loadExamResults($(this).attr('data-url'))" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php }
        //END OF BASIC
        } ?>
      </div>
            <?php
          }

      ?>
      <div class="tab-pane hidden" id="tab5">
        <?php
          $this->db->where('can_delete !=', 'trash');
          $payments = $this->db->get_where('payment', array(
            'student_id' => $row['student_id'], 'year' => $running_year, 'term' => $running_term
          ))->result_array();
         ?>
         <div class="overflow-x-auto">
         <table class="table table-bordered w-full" id="payment_ta">
           <thead>
             <tr>
               <th>#</th>
               <th><?php echo get_phrase('title'); ?></th>
               <th><?php echo get_phrase('amount'); ?></th>
               <th><?php echo get_phrase('date'); ?></th>
               <th><?php echo get_phrase('year'); ?></th>
               <th><?php echo get_phrase('options'); ?></th>
             </tr>
           </thead>
           <tfoot>
             <tr>
               <th>#</th>
               <th><?php echo get_phrase('title'); ?></th>
               <th><?php echo get_phrase('amount'); ?></th>
               <th><?php echo get_phrase('date'); ?></th>
               <th><?php echo get_phrase('year'); ?></th>
               <th><?php echo get_phrase('options'); ?></th>
             </tr>
           </tfoot>
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
                  <td><?php echo date('Y', $payment['timestamp']); ?></td>
                  <td>
                    <?php 
                      if($payment['invoice_code'] != null) { ?>


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
  </div>
</div>
<?php endforeach; ?>

<script>
$(function() { $('#payment_ta').dataTable(); });

function showTab(tabId) {
  $('.tab-pane').addClass('hidden');
  $('#' + tabId).removeClass('hidden');
  $('.tab-btn').removeClass('active text-blue-600').addClass('text-gray-600');
  $('#btn-' + tabId).addClass('active text-blue-600').removeClass('text-gray-600');
}

function loadExamResults(url) {
  $('html, body').animate({ scrollTop: ($('#top').offset().top) }, 1000);
  $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px;">Fetching Exam Results...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');
  $.ajax({ url: url, type: 'POST', dataType: 'html', cache: false })
    .done(function(data) { $('#main_page').html(data); });
}

$(document).ready(function() { showTab('tab1'); });
</script>
