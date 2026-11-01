<?php
    
    $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
    $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
    $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
                        

    $grading = $this->db->get('grade')->result_array();

    $gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;

?>
<!doctype html>
<html>
    <head>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
        <!--Chartjs-->
        <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>

    </head>
    <body>  

        <div class="container">
            <div class="row" style="padding-top: 15px;" id="print_div">
                <a href="javascript:void(0);" onClick="page_reload()">Print Report Sheet</a>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div id="print">


                        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
                        <!--Chartjs-->
                        <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>
                        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>

                        <style type="text/css">

                             #mark_print th, #grade_table th, #conducts th {
                                background-color: grey;
                                border: 2px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 2px solid red;
                             }
                             body {
                                font-size: 14px !important;
                             }

                             .account_tb th {
                                background-color: grey;
                                border: 1px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 1px solid red;
                                text-align: left;
                             }

                            td {
                                padding: 5px;
                            }

                            #grade_table tbody tr td{
                                line-height: 25px;
                            }

                            #term{
                                background-color: black; 
                                padding: 5px 15px 5px 15px; 
                                border-radius: 9px;
                                font-size: 24px;
                                letter-spacing: 5px;
                                color: #ffffff;

                            }

                            div #logo{
                                    position: absolute;
                                    top: 70px;
                                    left: 10px;
                                }

                                div #photo_passport{
                                    position: absolute;
                                    top: 70px;
                                    right: 10px;
                                    border-radius: 16% 6%;

                                }

                                #print_div a {
                                    background-color: black;
                                    color: #fff;
                                    padding: 5px;
                                    border-radius: 14px;
                                    font-size: 15px;
                                    font-weight: bold;
                                }

                                #print_div a:hover {
                                    background-color: green;
                                    color: #fff;
                                }

                                #promote{
                                    margin-top: -32px;
                                }

                            @media Print{
                                page {
                                background: #ffffff;
                                display: block;
                                margin: 0 auto;
                                margin-bottom: 0.5cm;
                                box-shadow: 0;

                                }

                                #print_div {
                                    display: none;
                                }
                                page[size="A4"] {
                                    width: 21cm;
                                    height: 29.7cm;
                                    page-break-after: always;
                                }

                                body, page {
                                    margin: 0;
                                    box-shadow: 0;
                                }

                                div #logo{
                                    position: absolute;
                                    top: 31px;
                                    left: 10px;
                                }

                                div #photo_passport{
                                    position: absolute;
                                    top: 18px;
                                    right: 10px;

                                }

                                #top_row{
                                    margin-top: -55px;
                                }

                                .student_details p{
                                    line-height: 30px;
                                }

                                #promote{
                                    margin-top: -32px;
                                }

                                #box{
                                    margin-top: -10px;
                                }

                                #gh{
                                    margin-top: -15px;
                                }

                                .sign {
                                    position: fixed;
                                    bottom: 5px;
                                }
                            }
                        </style>

                        <page size="A4">
                        <center>  
                            <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px; margin-top: -5px"><?php echo $system_name;?></h3>
                        </center>
                        <div class="row" id="top_row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                               <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br> 
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <center>
                                <h4 align='center' id="add"><?php echo $location;?></h4>
                                <h4 align='center' id="box"><?php echo $address;?></h4>
                                <h3 align="center" id="gh"><u>GHANA EDUCATION SERVICE</u></h3>
                                <span align="center" id="term">CUMMULATIVE REPORT</span>

                                
                            </div>
                            </center>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="img-circle" id="photo_passport" width="120" />
                            </div>                         
                        </div> 
                          <hr>

                        <!--Grading scale-->
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="table table-responsive">
                                    <table class="table table-bordered table-striped" id="grade_table" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
                                        <thead>
                                            <tr>
                                            <?php $grades = 1; foreach($grading as $gradepoint_row): ?>
                                            
                                                <th style="text-align: center; font-weight: bold;"><?php echo strtoupper($gradepoint_row['grade_point']); ?></th>
                                            
                                        <?php endforeach; ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <?php $grades = 1; foreach($grading as $grademark_row): ?>
                                                <td style="text-align: center;">
                                                    <?php echo $grademark_row['mark_from'].' - '. $grademark_row['mark_upto']; ?>% <br>
                                                    <?php echo ucwords(strtolower($grademark_row['name']));?>
                                                </td>
                                                <?php endforeach; ?>
                                                
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!--Either Upper or Lower Primary or JHS-->
                                <?php 
                                $term_counter = 0;
                                $is_first_page = false;

                                foreach($all_years as $year) {

                                    $class_id = $year['class_id'];
                                    $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
                                    $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
                                    

                                    if($class_name == 'JHSS') {
                                        //select with semester


                                        //select with sem
                                        $this->db->select('sem');
                                        $this->db->distinct();
                                        $this->db->where('student_id', $student_id);
                                        $this->db->where('class_id', $year['class_id']);
                                        $this->db->where('year', $year['year']);
                                        $sem_query = $this->db->get('enroll');
                                        $sem_array = $sem_query->result_array();


                                        if($sem_query->num_rows() == 0) {
                                            continue;
                                        }

                                        foreach($sem_array as $sem) {
                                            //main work goes here
                                            $this->db->where('category_id', '2');
                                            $this->db->where('year', $year['year']);
                                            $this->db->where('sem', $sem['sem']);
                                            $exam_id = $this->db->get('exam')->row()->exam_id; //exam id

                                            $running_year = $year['year']; //running year
                                            $running_sem = $sem['sem']; //running sem
                                            $section_id = $this->db->get_where('section', array('class_id' => $class_id))->row()->section_id;

                                            $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
                                            $no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'sem' => $running_sem, 'section_id' => $section_id, 'year' => $running_year))->num_rows();
                                            $teacher_remarks            =   $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'sem' => $running_sem, 'year' => $running_year))->row()->remarks;
                                            $att_total = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'sem' => $running_sem, 'year' => $running_year))->row()->days_opened;

                                            if($att_total == NULL) {
                                                $att_total = 'N/A';
                                            }


                                            $att_present = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'sem' => $running_sem, 'year' => $running_year))->row()->days_present;
                                            if($att_present == NULL) {
                                                $att_present = 'N/A';
                                            }

                                            //promotion
                                            $promoted_to = '';
                                            if($running_sem == 2) {
                                                $explode_running_year = explode('-', $running_year);
                                                $year_part1 = $explode_running_year[0] + 1;
                                                $year_part2 = $explode_running_year[1] + 1;

                                                $promoted_to_year = $year_part1. '-'. $year_part2;
                                                $promoted_to_sem = 1;

                                                $promoted_to_class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'sem' => $promoted_to_sem))->row()->class_id;
                                                $promoted_to_class = $this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row()->name. ' '.$this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row()->name_numeric;

                                                if($promoted_to_class_id == NULL) {
                                                    $promoted_to_class = 'N/A';
                                                }
                                            }else{
                                                $promoted_to_class = 'N/A';
                                            }

                                            $level = '';

                                            if($class_name == 'CLASS' && $class_name_numeric < 4){
                                                $level = 'LOWER PRIMARY';
                                            }elseif ($class_name == 'CLASS' && $class_name_numeric >= 4) {
                                                $level = 'UPPER PRIMARY';
                                            }elseif ($class_name == 'FORM') {
                                                $level = 'JHS';
                                            }elseif ($class_name == 'CRECHE' || $class_name == 'NURSERY' || $class_name == 'KG') {
                                                $level = 'PRESCHOOL';
                                            }

                                            //add section A or B if the class has more than one section
                                            $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
                                            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                                            $sec_name = '';
                                            if($class_has_more_sections > 1) {
                                                $sec_name = $section_name;
                                            }
                                           //===========///put html below//===========

                                            if($term_counter == 2) {
                                                if($is_first_page == false) {
                                                    echo '</page><page>';
                                                }

                                                $is_first_page = true;
                                                
                                            } else if($term_counter == 3) {
                                                echo '</page><page>';
                                                $term_counter = -1;
                                            }
                                            ?>
                                            

                                            <!--Details of the student here-->
                                            <div class="row">
                                                <div class="col-lg-12 col-sm-12 col-md-12 student_details">

                                                    <h4 align='center' style="letter-spacing: 6px;"><?php echo $level;?></h4>

                                                    <table style="width: 100%">
                                                        <thead>
                                                            <tr>
                                                                <td><!--left hand side -->
                                                                    <table style="width: 100%">
                                                                        <thead align="left">
                                                                            <tr>
                                                                                <th>STUDENT'S NAME:</th>
                                                                                <th><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></th>
                                                                            </tr>

                                                                            <tr>
                                                                                <th>CLASS:</th>
                                                                                <th><?=strtoupper($class_name). ' '. $class_name_numeric.$sec_name; ?></th>
                                                                            </tr>

                                                                            <tr>
                                                                                <th>No. ON ROLL:</th>
                                                                                <th><?php echo $no_on_roll;?></th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                </td> <!--left hand side ends-->

                                                                <td> <!--right hand side -->
                                                                    <table style="width: 100%">
                                                                        <thead align="right">
                                                                            <tr>
                                                                                <th>ACADEMIC YEAR:</th>
                                                                                <th><?php echo explode('-', $running_year)[1];?></th>
                                                                            </tr>

                                                                            <tr>
                                                                                <th>sem:</th>
                                                                                <th><?php echo $running_sem;?></th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </thead>
                                                    </table>
                                                    <!--
                                                    <p>This is to Certify that <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></b> Position <b><?php
                                $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_sem);?></b> of <?php echo ' <b>' . strtoupper($class_name). ' '. $class_name_numeric.$sec_name;?></b> has completed sem <b><?php echo $running_sem;?></b> 

                                                   of the <b><?php echo explode('-', $running_year)[1];;?></b> academic year. No. on roll <b><?php echo $no_on_roll;?>.</b> sem ending <b><?php $date = date_create($sem_ending); echo date_format($date, 'd/m/Y'); ?>.</b> Next sem Begins <b><?php $date = date_create($next_sem_begins); echo date_format($date, 'd/m/Y'); ?>.</b></p>

                                               -->

                                                </div>
                                            </div> 
                                        
                                         
                                        <div class="table table-responsive">
                        <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1" id="mark_print">
                           <thead>
                                <tr>
                                  <!--  <th style="text-align: center; font-weight: bold;">S/N</th>-->
                                    <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                                    <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                                    <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                                    <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                                    <th style="text-align: center; font-weight: bold;">GRADE</th>
                                  <!--   <th style="text-align: center; font-weight: bold;">REMARK</th>
                                    <th style="text-align: center; font-weight: bold;">POSITION</th>
                                    -->
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                    $ar = 0;
                                    $subjects_array = array();
                                    $subject_name = '';
                                    $marks_array = array();

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
                                   <!--  <td style="text-align: center;"><?php echo $i;?></td> -->
                                    <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo strtoupper($row3['name']);
                                };?></td>
                                    <td style="text-align: center;">
                                        <?php
                                            $class_score_query = $this->db->get_where('mark' , array(
                                                                        'subject_id' => $row3['subject_id'],
                                                                            'exam_id' => $exam_id,
                                                                                'class_id' => $class_id,
                                                                                    'student_id' => $student_id , 
                                                                                        'year' => $running_year,
                                                                                            'sem' => $running_sem
                                                                    ));
                                            if($class_score_query->num_rows() > 0){
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
                                                                                            'sem' => $running_sem
                                                                    ));
                                            if($exam_score_query->num_rows() > 0){
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
                                                                                            'sem' => $running_sem
                                                                    ));
                                            if($obtained_mark_query->num_rows() > 0){
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

                                    <td style="text-align: center;">
                                        <?php
                                            
                                            $row4['mark_obtained'] = $obtained_mark_query->row()->mark_obtained;
                                            if($obtained_mark_query->num_rows() > 0){
                                                if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                    $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                    echo $grade['grade_point'];
                                                   // $total_grade_point += $grade['grade_point'];
                                                }
                                            }else{
                                              echo "N/A";
                                            }
                                        ?>
                                    </td>
                                 <!--    <td style="text-align: center;">
                                        <?php
                                            if($obtained_mark_query->num_rows() > 0){
                                                if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                    $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                    echo $grade['name'];
                                                   // $total_grade_point += $grade['grade_point'];
                                                }
                                            }else{
                                              echo "N/A";
                                            }
                                        ?>
                                    </td> 
                                    <td style="text-align: center;">
                                        <?php
                                            $this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $student_id, $running_year, $running_sem);
                                        ?>
                                    </td>

                                    -->
                                </tr>
                            <?php 
                            $i++;

                            //abbreviate those subjects with long words
                            $subject_name = $row3['name'];
                            if(str_word_count($row3['name'], 1)[0] == 'Religious' || str_word_count($row3['name'], 1)[0] == 'RELIGIOUS') {
                                $subject_name = 'R.M.E';
                            } else if(str_word_count($row3['name'], 1)[0] == 'English' || str_word_count($row3['name'], 1)[0] == 'ENGLISH') {
                                $subject_name = 'English';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Information' || str_word_count($row3['name'], 1)[0] == 'INFORMATION') {
                                $subject_name = 'ICT';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Health' || str_word_count($row3['name'], 1)[0] == 'HEALTH') {
                                $subject_name = 'H&Safety';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Citizenship' || str_word_count($row3['name'], 1)[0] == 'CITIZENSHIP') {
                                $subject_name = 'C.E';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Mathematics' || str_word_count($row3['name'], 1)[0] == 'MATHEMATICS') {
                                $subject_name = 'Maths';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Creative' || str_word_count($row3['name'], 1)[0] == 'CREATIVE') {
                                $subject_name = 'C.A';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Numeracy' || str_word_count($row3['name'], 1)[0] == 'NUMERACY') {
                                $subject_name = 'Numeracy';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Natural' || str_word_count($row3['name'], 1)[0] == 'NATURAL') {
                                $subject_name = 'Science';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Integrated' || str_word_count($row3['name'], 1)[0] == 'INTEGRATED') {
                                $subject_name = 'Science';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Our' || str_word_count($row3['name'], 1)[0] == 'OUR') {
                                $subject_name = 'O.W.O.P';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Physical' || str_word_count($row3['name'], 1)[0] == 'PHYSICAL') {
                                $subject_name = 'P.E';
                            }

                            //get each subject with its total mark
                            $subjects_array[$ar] = $subject_name;
                            $subj_mark = $row4['mark_obtained'];

                            if($subj_mark == null || $subj_mark == '') {
                                $marks_array[$ar] = '0';
                            } else {
                                $marks_array[$ar] = $subj_mark;
                            }

                            $ar++;

                        endforeach;?>

                            <tr>
                                <th colspan="" style="text-align: center;">TOTAL</th>
                                <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                                <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                                <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                       </table>
                    </div>

                    <!--Attendance -->
                    <div class="row" style="margin-top: 8px;">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <table class="table">
                                <tr>
                                    <td>Attendance</td>
                                    <td><b><?php echo $att_present;?></b></td>
                                    <td>Out of</td>
                                    <td><b><?php echo $att_total; ?>.</b></td>
                                </tr>
                            </table>
                            
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" style="position: relative; float: right" id="promote">
                             <table class="table">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>Promoted to</td>
                                    <td><b><?php echo $promoted_to_class; ?></b></td>
                                </tr>
                            </table>
                        </div>
                    </div> <!-- Promotion ends-->

                                            <?php

                                            $term_counter++;

                                        } //end of each sem


                                    } else {

                                        //select with term
                                        $this->db->select('term');
                                        $this->db->distinct();
                                        $this->db->where('student_id', $student_id);
                                        $this->db->where('class_id', $year['class_id']);
                                        $this->db->where('year', $year['year']);
                                        $term_query = $this->db->get('enroll');
                                        $term_array = $term_query->result_array();


                                        if($term_query->num_rows() == 0) {
                                            continue;
                                        }

                                        foreach($term_array as $term) {
                                            //main work goes here
                                            $this->db->where('category_id', '2');
                                            $this->db->where('year', $year['year']);
                                            $this->db->where('term', $term['term']);
                                            $exam_id = $this->db->get('exam')->row()->exam_id; //exam id

                                            $running_year = $year['year']; //running year
                                            $running_term = $term['term']; //running term
                                            $section_id = $this->db->get_where('section', array('class_id' => $class_id))->row()->section_id;

                                            $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
                                            $no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'term' => $running_term, 'section_id' => $section_id, 'year' => $running_year))->num_rows();
                                            $teacher_remarks            =   $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->row()->remarks;
                                            $att_total = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'term' => $running_term, 'year' => $running_year))->row()->days_opened;

                                            if($att_total == NULL) {
                                                $att_total = 'N/A';
                                            }


                                            $att_present = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'term' => $running_term, 'year' => $running_year))->row()->days_present;
                                            if($att_present == NULL) {
                                                $att_present = 'N/A';
                                            }

                                            //promotion
                                            $promoted_to = '';
                                            if($running_term == 3) {
                                                $explode_running_year = explode('-', $running_year);
                                                $year_part1 = $explode_running_year[0] + 1;
                                                $year_part2 = $explode_running_year[1] + 1;

                                                $promoted_to_year = $year_part1. '-'. $year_part2;
                                                $promoted_to_term = 1;

                                                $promoted_to_class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'term' => $promoted_to_term))->row()->class_id;
                                                $promoted_to_class = $this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row()->name. ' '.$this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row()->name_numeric;

                                                if($promoted_to_class_id == NULL) {
                                                    $promoted_to_class = 'N/A';
                                                }
                                            }else{
                                                $promoted_to_class = 'N/A';
                                            }

                                            $level = '';

                                            if($class_name == 'CLASS' && $class_name_numeric < 4){
                                                $level = 'LOWER PRIMARY';
                                            }elseif ($class_name == 'CLASS' && $class_name_numeric >= 4) {
                                                $level = 'UPPER PRIMARY';
                                            }elseif ($class_name == 'FORM') {
                                                $level = 'JHS';
                                            }elseif ($class_name == 'CRECHE' || $class_name == 'NURSERY' || $class_name == 'KG') {
                                                $level = 'PRESCHOOL';
                                            }

                                            //add section A or B if the class has more than one section
                                            $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
                                            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                                            $sec_name = '';
                                            if($class_has_more_sections > 1) {
                                                $sec_name = $section_name;
                                            }
                                           //===========///put html below//===========

                                            if($term_counter == 2) {
                                                if($is_first_page == false) {
                                                    echo '</page><page>';
                                                }

                                                $is_first_page = true;
                                                
                                            } else if($term_counter == 3) {
                                                echo '</page><page>';
                                                $term_counter = -1;
                                            }
                                            ?>
                                            

                                            <!--Details of the student here-->
                                            <div class="row">
                                                <div class="col-lg-12 col-sm-12 col-md-12 student_details">

                                                    <h4 align='center' style="letter-spacing: 6px;"><?php echo $level;?></h4>

                                                    <table style="width: 100%">
                                                        <thead>
                                                            <tr>
                                                                <td><!--left hand side -->
                                                                    <table style="width: 100%">
                                                                        <thead align="left">
                                                                            <tr>
                                                                                <th>STUDENT'S NAME:</th>
                                                                                <th><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></th>
                                                                            </tr>

                                                                            <tr>
                                                                                <th>CLASS:</th>
                                                                                <th><?=strtoupper($class_name). ' '. $class_name_numeric.$sec_name; ?></th>
                                                                            </tr>

                                                                            <tr>
                                                                                <th>No. ON ROLL:</th>
                                                                                <th><?php echo $no_on_roll;?></th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                </td> <!--left hand side ends-->

                                                                <td> <!--right hand side -->
                                                                    <table style="width: 100%">
                                                                        <thead align="right">
                                                                            <tr>
                                                                                <th>ACADEMIC YEAR:</th>
                                                                                <th><?php echo explode('-', $running_year)[1];?></th>
                                                                            </tr>

                                                                            <tr>
                                                                                <th>TERM:</th>
                                                                                <th><?php echo $running_term;?></th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </thead>
                                                    </table>
                                                    <!--
                                                    <p>This is to Certify that <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></b> Position <b><?php
                                $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_term);?></b> of <?php echo ' <b>' . strtoupper($class_name). ' '. $class_name_numeric.$sec_name;?></b> has completed term <b><?php echo $running_term;?></b> 

                                                   of the <b><?php echo explode('-', $running_year)[1];;?></b> academic year. No. on roll <b><?php echo $no_on_roll;?>.</b> Term ending <b><?php $date = date_create($term_ending); echo date_format($date, 'd/m/Y'); ?>.</b> Next Term Begins <b><?php $date = date_create($next_term_begins); echo date_format($date, 'd/m/Y'); ?>.</b></p>

                                               -->

                                                </div>
                                            </div> 
                                        
                                         
                                        <div class="table table-responsive">
                        <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1" id="mark_print">
                           <thead>
                                <tr>
                                  <!--  <th style="text-align: center; font-weight: bold;">S/N</th>-->
                                    <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                                    <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                                    <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                                    <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                                    <th style="text-align: center; font-weight: bold;">GRADE</th>
                                  <!--   <th style="text-align: center; font-weight: bold;">REMARK</th>
                                    <th style="text-align: center; font-weight: bold;">POSITION</th>
                                    -->
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                    $ar = 0;
                                    $subjects_array = array();
                                    $subject_name = '';
                                    $marks_array = array();

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
                                   <!--  <td style="text-align: center;"><?php echo $i;?></td> -->
                                    <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo strtoupper($row3['name']);
                                };?></td>
                                    <td style="text-align: center;">
                                        <?php
                                            $class_score_query = $this->db->get_where('mark' , array(
                                                                        'subject_id' => $row3['subject_id'],
                                                                            'exam_id' => $exam_id,
                                                                                'class_id' => $class_id,
                                                                                    'student_id' => $student_id , 
                                                                                        'year' => $running_year,
                                                                                            'term' => $running_term
                                                                    ));
                                            if($class_score_query->num_rows() > 0){
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
                                                                                            'term' => $running_term
                                                                    ));
                                            if($exam_score_query->num_rows() > 0){
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
                                                                                            'term' => $running_term
                                                                    ));
                                            if($obtained_mark_query->num_rows() > 0){
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

                                    <td style="text-align: center;">
                                        <?php
                                            
                                            $row4['mark_obtained'] = $obtained_mark_query->row()->mark_obtained;
                                            if($obtained_mark_query->num_rows() > 0){
                                                if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                    $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                    echo $grade['grade_point'];
                                                   // $total_grade_point += $grade['grade_point'];
                                                }
                                            }else{
                                              echo "N/A";
                                            }
                                        ?>
                                    </td>
                                 <!--    <td style="text-align: center;">
                                        <?php
                                            if($obtained_mark_query->num_rows() > 0){
                                                if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                    $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                    echo $grade['name'];
                                                   // $total_grade_point += $grade['grade_point'];
                                                }
                                            }else{
                                              echo "N/A";
                                            }
                                        ?>
                                    </td> 
                                    <td style="text-align: center;">
                                        <?php
                                            $this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $student_id, $running_year, $running_term);
                                        ?>
                                    </td>

                                    -->
                                </tr>
                            <?php 
                            $i++;

                            //abbreviate those subjects with long words
                            $subject_name = $row3['name'];
                            if(str_word_count($row3['name'], 1)[0] == 'Religious' || str_word_count($row3['name'], 1)[0] == 'RELIGIOUS') {
                                $subject_name = 'R.M.E';
                            } else if(str_word_count($row3['name'], 1)[0] == 'English' || str_word_count($row3['name'], 1)[0] == 'ENGLISH') {
                                $subject_name = 'English';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Information' || str_word_count($row3['name'], 1)[0] == 'INFORMATION') {
                                $subject_name = 'ICT';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Health' || str_word_count($row3['name'], 1)[0] == 'HEALTH') {
                                $subject_name = 'H&Safety';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Citizenship' || str_word_count($row3['name'], 1)[0] == 'CITIZENSHIP') {
                                $subject_name = 'C.E';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Mathematics' || str_word_count($row3['name'], 1)[0] == 'MATHEMATICS') {
                                $subject_name = 'Maths';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Creative' || str_word_count($row3['name'], 1)[0] == 'CREATIVE') {
                                $subject_name = 'C.A';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Numeracy' || str_word_count($row3['name'], 1)[0] == 'NUMERACY') {
                                $subject_name = 'Numeracy';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Natural' || str_word_count($row3['name'], 1)[0] == 'NATURAL') {
                                $subject_name = 'Science';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Integrated' || str_word_count($row3['name'], 1)[0] == 'INTEGRATED') {
                                $subject_name = 'Science';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Our' || str_word_count($row3['name'], 1)[0] == 'OUR') {
                                $subject_name = 'O.W.O.P';
                            } else if(str_word_count($row3['name'], 1)[0] == 'Physical' || str_word_count($row3['name'], 1)[0] == 'PHYSICAL') {
                                $subject_name = 'P.E';
                            }

                            //get each subject with its total mark
                            $subjects_array[$ar] = $subject_name;
                            $subj_mark = $row4['mark_obtained'];

                            if($subj_mark == null || $subj_mark == '') {
                                $marks_array[$ar] = '0';
                            } else {
                                $marks_array[$ar] = $subj_mark;
                            }

                            $ar++;

                        endforeach;?>

                            <tr>
                                <th colspan="" style="text-align: center;">TOTAL</th>
                                <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                                <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                                <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                       </table>
                    </div>

                    <!--Attendance -->
                    <div class="row" style="margin-top: 8px;">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <table class="table">
                                <tr>
                                    <td>Attendance</td>
                                    <td><b><?php echo $att_present;?></b></td>
                                    <td>Out of</td>
                                    <td><b><?php echo $att_total; ?>.</b></td>
                                </tr>
                            </table>
                            
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" style="position: relative; float: right" id="promote">
                             <table class="table">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>Promoted to</td>
                                    <td><b><?php echo $promoted_to_class; ?></b></td>
                                </tr>
                            </table>
                        </div>
                    </div> <!-- Promotion ends-->

                                            <?php

                                            $term_counter++;

                                        } //end of each term

                                    } //end of if statement whether JHS or Not
                                } //end of each year

 

                                ?>

                        <div class="row sign" style="font-weight: bold;">
                            <div class="col-lg-12 col-md-12">
                                <div style="position: relative; margin-left: 350px;">
                                    <small><em><strong>Software: Developed & Designed By Lightworld Technologies Ltd Team : +233243618186</strong></em></small>
                                </div>
                                
                            </div>
                        </div>
                        
                    </page>

                    </div>
                 </div>
            </div>
        </div>

    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>    
    
<script type="text/javascript">
    jQuery(document).ready(function($)
    {
        var elem = $('#print');

        //PrintElem(elem);
       //Popup(data);

    });
    
    $('input[type="checkbox"]').click(function() {
        return false;
    });

   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        //mywindow.document.write('<link rel="stylesheet" href="assets/css/chartjs/dist/Chart.min.css" type="text\/css" \/>');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.css');?>" />');
        mywindow.document.write('<script src="<?php echo base_url('assets/css/chartjs/dist/Chart.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 13px">');
        mywindow.document.write(data);

        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }
</script>

<script type="text/javascript">
    

    function print_page() {
        //window.location.reload();
        print();
       }

       function page_reload() {
        window.location.reload();
       }


        $(function() {
            setTimeout(() => {
              print_page();
            }, 1000);
            
        }); 
</script>
