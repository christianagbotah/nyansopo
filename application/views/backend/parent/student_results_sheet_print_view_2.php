<!doctype html>
<html>
    <head>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>

        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/apexchart/apexcharts.css');?>"/>



        <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
        <!--Chartjs-->
        <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>

        <script src="<?php echo base_url('assets/apexchart/apexcharts.min.js');?>" type="text/javascript"></script>

    </head>
    <body>  

        <div class="container">
        <div class="row" style="margin-top: 15px; position: fixed; z-index: 9999" id="print_div">
        <a href="javascript:void(0);" onClick="page_reload()">Print Bulk Report Sheet</a>
        </div>
        <div class="row">
        <div class="col-lg-12">
        <div id="print">

        <?php
            

            $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
            $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
            $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
            $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
            $system_email        =   $this->db->get_where('settings' , array('type'=>'system_email'))->row()->description;
            $system_postal_address        =   $this->db->get_where('settings' , array('type'=>'box_number'))->row()->description;
            $system_digital_address        =   $this->db->get_where('settings' , array('type'=>'digital_address'))->row()->description;
            $system_website_address        =   $this->db->get_where('settings' , array('type'=>'website_address'))->row()->description;
            $system_phone        =   $this->db->get_where('settings' , array('type'=>'phone'))->row()->description;
            $system_location        =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
            $running_year       =   $year;
            $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
            $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
            
            $running_term       =   $term;

            $no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', 'term' => $running_term, 'section_id' => $section_id, 'year' => $running_year))->num_rows();


             $data_array2 =  array(
            'class_id' => $class_id, 
                'section_id' => $section_id, 
                    'year' => $year,
                        'status' => 'close', 
                            'mute' => '0',
                                'term' => $term
                    );

            $this->db->select('*');
            $this->db->where($data_array2);
            $this->db->from('enroll');
            $num_r = $this->db->get()->num_rows();

        /***************************************************************************************/
         $data_array =  array(
            'class_id' => $class_id, 
                'section_id' => $section_id, 
                    'year' => $year, 
                        'exam_id' => $exam_id,
                            'mute' => '0',
                                 'term' => $term
                    );

            $this->db->select('student_id');
            $this->db->distinct();
            $this->db->where($data_array);
            $this->db->from('mark');
            $this->db->limit($num_r);
            //$this->db->limit(5);
           // $this->db->join('mark', 'mark.exam_id ='. $exam_id);
            $students_marks = $this->db->get()->result_array();

            //let's get total number of subjects they actually did in the term
            $this->db->select('subject_id');
            $this->db->distinct();
            $this->db->where($data_array);
            $this->db->where('class_id IS NOT NULL');
            $this->db->where('section_id IS NOT NULL');
            $this->db->from('mark');
            //$this->db->limit(5);
            $total_subjects = $this->db->get()->num_rows();

            $grandScores = [];

            $termRows = getArchivedTermStartsEndsDate($term, $year);

            if($termRows->term_ending == '' || empty($termRows->term_ending)) {

                $term_ending        =   '-';
                $next_term_begins   =   '-';

            } else {
                $term_ending        =   $termRows->term_ending;
                $next_term_begins   =   $termRows->next_term_begins;
            }


            //GET PREVIOUS TERM'S PERFORMANCE
              if($running_term == 1) {
                $prev_term = 3; //last year 3rd term
                $pYearExplode = explode('-', $running_year);
                $prev_year1 = $pYearExplode[0] - 1;
                $prev_year2 = $pYearExplode[1] - 1;

                $prev_year = $prev_year1.'-'.$prev_year2;

              } else if($running_term == 2) {
                $prev_term = 1; //same year 1st term
                $prev_year = $running_year;

              } else if($running_term == 3) {
                $prev_term = 2; //same year 2nd term
                $prev_year = $running_year;
              }


              $grand_total_score =   $this->db->get_where('aggregation', array('class_id' => $class_id, 'term' => $running_term, 'year' => $running_year))->first_row()->grand_total;


            //run this only once
            $student_prev_class =   $this->db->get_where('enroll', array('student_id' => $student_id, 'term' => $prev_term, 'year' => $prev_year))->row()->class_id;

            $prev_no_on_roll =   $this->db->get_where('enroll', array('class_id' => $student_prev_class, 'term' => $prev_term, 'year' => $prev_year))->num_rows();

            $prev_grand_total_score =   $this->db->get_where('aggregation', array('class_id' => $student_prev_class, 'term' => $prev_term, 'year' => $prev_year))->first_row()->grand_total;


            $conducts_data = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'term' => $running_term, 'year' => $running_year))->row();

              $class_teacher_remarks = $conducts_data->class_teacher_remarks;
              $head_teacher_remarks = $conducts_data->head_teacher_remarks;
              $attitude = $conducts_data->attitude;
              $interest = $conducts_data->interest;
              $conduct = $conducts_data->conduct;
              $att_present = $conducts_data->days_present;
              $att_total = $conducts_data->days_opened;


              $previous_term_data = $this->db->get_where('aggregation', array('student_id' => $student_id, 'term' => $prev_term, 'year' => $prev_year));

              if($previous_term_data->num_rows() < 1) {
                $previous_term_grand_score = 0;
              } else {
                $previous_term_grand_score = $previous_term_data->row()->aggregate_mark;
              }
              
              $prev_data_array =  array(
                        'year' => $prev_year, 
                            'student_id' => $student_id,
                                 'term' => $prev_term,
                        );


                //let's get total number of subjects they actually did in the term
                $this->db->select('subject_id');
                $this->db->distinct();
                $this->db->where($prev_data_array);
                $this->db->where('class_id IS NOT NULL');
                $this->db->where('section_id IS NOT NULL');
                $this->db->from('mark');
               // $this->db->limit(5);
                $prev_total_subjects = $this->db->get()->num_rows();

                if($prev_total_subjects == 0) {
                    $prev_totalMark = 0;
                    $prev_markPerc = 0;
                } else {
                    $prev_totalMark = $prev_total_subjects * 100;
                    $prev_markPerc = $previous_term_grand_score * 100;
                }

                if($prev_markPerc == 0) {
                    $prev_student_average = 0;
                } else {
                    $prev_student_average = $prev_markPerc / $prev_totalMark;
                }
                

                $prev_student_grade = $this->crud_model->get_grade($prev_student_average);

                $prev_student_grade_level = $this->crud_model->get_grade_level($prev_student_grade['grade_point']);

                //class averages
                if($prev_grand_total_score == 0) {
                    $prev__average2 = 0;
                } else {
                    $prev__average1 = $prev_grand_total_score / $prev_no_on_roll;
                    $prev__average2 = $prev__average1 / $prev_total_subjects;
                }

                if($prev_total_subjects == 0) {
                    $prev__average2 = 0;
                }

                $prev_class_grade = $this->crud_model->get_grade($prev__average2);
                $prev_class_grade_level = $this->crud_model->get_grade_level($prev_class_grade['grade_point']);


                $current_average1 = $grand_total_score / $no_on_roll;
                $current_average2 = $current_average1 / $total_subjects;

                $class_grade = $this->crud_model->get_grade($current_average2);
                $class_grade_level = $this->crud_model->get_grade_level($class_grade['grade_point']);



            
            //incase the admin did not set correct or term ending, let's use current date
            $current_date = strtotime(date('d-m-Y'));
            //for term
            if(strtotime($term_ending) < $current_date) {
                $term_ending = date('d-m-Y');
            }
            
            /** $att_undefined = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'term' => $running_term, 'status' => '0'))->num_rows();
            $att_present = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'term' => $running_term, 'status' => '1'))->num_rows();
            $att_absent = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'term' => $running_term, 'status' => '2'))->num_rows();

            $att_total = $att_undefined + $att_present + $att_absent;
            **/



            $gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;

            $grading = $this->db->get('grade_2')->result_array();

            //Account section
            $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
            $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

            $next_term = $running_term + 1;
            $next_year = $running_year;

            if($next_term == 4) {
                $next_term = 1;

                $p1 = explode('-', $running_year)[0] + 1;
                $p2 = explode('-', $running_year)[1] + 1;

                $next_year = $p1 .'-'. $p2;
            }

            //feeding and classes owe
            $this->db->select('timestamp');
            $this->db->from('feeding_fee');
            $this->db->where('student_id', $student_id);
            //$this->db->where('year', $running_year);
            //$this->db->where('term', $running_term);
            $this->db->order_by('timestamp', 'desc');
            $this->db->limit(1);
            $previous_day_timestamp = $this->db->get()->row()->timestamp; //selecting just the previously entered timestamp for this particular class


            //Arrears
            //invoices owe

            /*$arrears = $this->financial_report_model->getBillArrearsFeesByStudentId($running_year, $running_term, $student_id, 'yes'); 


            //Tuition fees for next term
            $this->db->select_sum('due');
            $this->db->where('title', 'SCHOOL FEES');
            $tuition_next_term =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->row()->due;

            //printing fees for next term
            $this->db->select_sum('due');
            $this->db->where('title', 'PRINTING FEES');
            $printing_next_term =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->row()->due;

            //others fees for next term
            $this->db->select_sum('due');
            $this->db->where_not_in('title', ['PRINTING FEES', 'SCHOOL FEES']);
            $others_next_term =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->row()->due;*/



            //feeding fee owe
            /*$feeding_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
            $feeding_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;*/

            //classes fee owe
            /*$classes_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->num_rows();
            $classes_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->row()->cdue;*/

            //transport fee owe
            /*$transport_owe_row =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
            $transport_owe =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;*/

            $feeding_owe_row = 0;
            $classes_owe_row = 0;
            $transport_owe_row = 0;
            //arrears end

            //Next term bills

            //invoices owe
            

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
            }else{
                $promoted_to_class = 'N/A';
            }
        ?>

            <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.min.css');?>"/>
            <!--Chartjs-->
            <script src="<?php echo base_url('assets/css/chartjs/dist/Chart.min.js');?>" type="text/javascript"></script>
            <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>

            <style type="text/css">
                
                 #mark_print th, #grade_table th, #conducts th {
                    background-color: grey;
                    border: 2px solid #e2dddd;
                    padding: 3px 5px 3px 5px;
                    color: white;
                    border-bottom: 2px solid red;
                 }

                 body {
                    font-size: 9px !important;
                 }

                 .account_tb th {
                    background-color: grey;
                    border: 1px solid #e2dddd;
                    padding: 3px 5px 3px 5px;
                    color: white;
                    border-bottom: 1px solid red;
                    text-align: left;
                 }

                td {
                    padding: 3px 5px 3px 5px;
                }

                #grade_table tbody tr td{
                    line-height: 25px;
                }

                #term{
                    background-color: #520452; 
                    padding: 3px 5px 3px 5px 15px 5px 15px; 
                    font-size: 20px;
                    letter-spacing: 5px;
                    color: #ffffff;

                }

                div .logo{
                    position: absolute;
                    margin-top: 52px;
                    left: 10px;
                }

                div .photo_passport {
                    position: absolute;
                    margin-top: -180px;
                    right: 10px;
                    border-radius: 16% 6%;
                }

                page[size="A4"] {
                        width: 21cm;
                        height: 29.7cm;
                        page-break-after: always;
                    }

                    #print_div a {
                    background-color: black;
                    color: #fff;
                    padding: 3px 5px 3px 5px;
                    border-radius: 14px;
                    font-size: 15px;
                    font-weight: bold;
                }

                #print_div a:hover {
                    background-color: green;
                    color: #fff;
                }

                
                #promote{
                    position: absolute;
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

                    page[size="A4"] {
                        width: 21cm;
                        height: 29.7cm;
                        page-break-after: always;
                    }

                    body, page {
                        margin: 0;
                        box-shadow: 0;
                    }
                    
                    #print_div {
                        display: none;
                    }


                    div .logo{
                        position: absolute;
                        margin-top: 32px;
                        left: 10px;
                    }

                    div .photo_passport{
                        position: absolute;
                        margin-top: -131px;
                        right: 10px;

                    }

              
                    #student_details p{
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


                /*
                    NEW STYLES DEFINED

                */
                #top_row {
                    border-top:  2px solid #520452;
                    border-left:  2px solid #520452;
                    border-right:  4px solid #520452;
                    border-bottom:  4px solid #520452;
                    padding-top: 3px;
                    padding-bottom: 10px;
                   
                }

                #second_row {
                    border:  3px solid #b90606;
                    padding: 3px 5px 3px 5px;
                    margin-top: 8px;
                    font-size:  11px;
                }

                /*Table td border and background*/
                .td-border {
                    border:  2px solid #520452;
                }

                .td-background {
                    background-color: #520452;
                    color: #fff;
                }

                #result_table {
                    border-spacing: 20px;
                }

               table tr {
                    margin-top: -30px !important;
                }
            </style>

            <!--Either Upper or Lower Primary or JHS-->
            <?php 
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

                //TERM IN WORDS
                $termInWords = '';
                if($running_term == 1) {
                    $termInWords = 'ONE';
                } else if($running_term == 2) {
                    $termInWords = 'TWO';
                } else if($running_term == 3) {
                    $termInWords = 'THREE';
                }
            ?>

            <?php
                //add section A or B if the class has more than one section
                $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
                $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                $sec_name = '';
                if($class_has_more_sections > 1) {
                    $sec_name = $section_name;
                }
            ?>

        <page size="A4">
            <div class="row" id="top_row">
                <center>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        
                        <h2 style="font-weight: bold; margin-top:  0px; font-size: 23px; letter-spacing: 2px; ">GHANA EDUCATION SERVICE</h2>
                        <h3 style="font-weight: bold; font-size: 15px; margin-top: -15px;"><?php echo $system_name;?></h3>                


                        <img src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 65px; margin-top: -15px; margin-bottom: 5px"><br> 

                    </div>
                </center>                        
            </div> 

            <div style="margin-top: -25px;">
                <center>
                    <span align="center" class="td-background" id="term" style="font-weight: bold">TERMINAL REPORT</span>
                </center>
            </div>

            <div class="row" id="second_row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <table class="table table-responsive" style="width: 100%;">
                        <tbody>
                            <tr>
                                <td width="100" align="center" class="td-border td-background"><strong>VACATION DATE</strong></td>
                                <td width="80" align="center" class="td-border"><strong style="color: #b90606"><?php $date = date_create($term_ending); echo date_format($date, 'd-M-Y'); ?></strong></td>
                                <td align="center" class="td-border td-background"><strong>YEAR</strong></td>
                                <td align="center" class="td-border"><strong style="color: #b90606"><?=date('Y', strtotime($term_ending)); ?></strong></td>
                                <td width="30" align="center" class="td-border td-background"><strong>GRAND SCORE</strong></td>
                                <td align="center" class="td-border" width="60" rowspan="2"><strong style="color: #520452; font-size: 18px" id="grandTotal_<?=$student_id?>"></strong></td>
                                <td align="center" class="td-border td-background" width="30"><strong>GRADE LEVEL</strong></td>
                                <td></td>
                                <td align="center" style="font-size: 18px !important; padding: 0px;" class="td-border td-background" rowspan="2" style="border-left-color: #fff; border-left-width: 3px;"><strong id="gradeLevel_<?=$student_id?>"></strong></td>
                            </tr>
                            
                            <tr>
                                <td align="center" class="td-border"><strong style="color: #520452">CLASS NAME</strong></td>
                                <td align="center" class="td-border td-background"><strong><?=strtoupper($class_name). ' '. $class_name_numeric.$sec_name; ?></strong></td>
                                <td align="center" class="td-border td-background"><strong>TERM</strong></td>
                                <td width="60" align="center" class="td-border"><strong style="color: #b90606"><?php echo $termInWords;?></strong></td>
                                
                            </tr>

                            <tr>
                                <td colspan="9"></td>
                            </tr>

                            
                            <tr>
                                <!-- Exams scores row -->
                                <td colspan="8" style="border: 3px solid #520452">
                                    <table id="result_table" class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse; margin-top: 10px;">
                                       <thead>

                                            <tr>
                                              <!--  <th style="text-align: center; font-weight: bold;">S/N</th>-->
                                                <th width="80" class="td-background" style="text-align: center; font-weight: bold; padding: 4px; border: 2px solid #fff">SUBJECTS</th>
                                                <th width="50" class="td-background" style="text-align: center; font-weight: bold; padding: 4px; border: 2px solid #fff">CLASS SCORE 50%</th>
                                                <th width="50" class="td-background" style="text-align: center; font-weight: bold; padding: 4px; border: 2px solid #fff">EXAM SCORE 50%</th>
                                                <th width="65" class="td-background" style="text-align: center; font-weight: bold; padding: 4px; border: 2px solid #fff">TOTAL SCORE 100%</th>
                                                <th width="55" class="td-background" style="text-align: center; font-weight: bold; padding: 4px; border: 2px solid #fff">GRADE LEVEL</th>
                                                <th width="60" class="td-background" style="text-align: center; font-weight: bold; padding: 4px; border: 2px solid #fff">REMARKS</th>
                                                <!-- <th class="td-background" style="text-align: center; font-weight: bold;">POSITION</th> -->
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td></td></tr>
                                                <tr><td></td></tr>
                                            <?php

                                                $ar = 0;
                                                $subjects_array = array();
                                                $subject_name = '';
                                                $marks_array = array();

                                                $class_score_total = 0;
                                                $exam_score_total =0; 
                                                $total_marks = 0;
                                                $total_grade_point = 0;

                                                //$this->db->limit(5);
                                                $subjects = $this->db->get_where('subject' , array(
                                                    'class_id' => $class_id , 'year' => $running_year, 'term' => $running_term
                                                ))->result_array();
                                                $i = 1;
                                                foreach ($subjects as $row3):
                                                    $subj = $row3['name'];

                                                    if($row3['name'] == 'OUR WORLD OUR PEOPLE' || $row3['name'] == 'Our World Our People') {
                                                        $subj = 'OWOP';

                                                    } else if($row3['name'] == 'Religious And Moral Education' || $row3['name'] == 'Religious & Moral Education') {
                                                        $subj = 'R.M.E';
                                                    } 
                                            ?>
                                            <tr>
                                               <!--  <td style="text-align: center;"><?php echo $i;?></td> -->
                                                <td style="border: 2px solid #520452"><?php if(strlen($subj) <= 4) {
                                                echo strtoupper($subj);
                                            }else{ 
                                                echo strtoupper($subj);
                                            };?></td>
                                                <td style="text-align: center; border: 2px solid #520452">
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
                                                                if($row4['class_score'] == NULL) {
                                                                    echo "N/A";
                                                                } else {
                                                                    echo $row4['class_score'];
                                                                    $class_score_total += $row4['class_score'];
                                                                    $total_marks += $row4['class_score'];
                                                                }
                                                            }
                                                        }else{
                                                          echo "N/A";
                                                        }
                                                    ?>
                                                </td>
                                                <td style="text-align: center; border: 2px solid #520452">
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
                                                                if($row4['exam_score'] == NULL) {
                                                                    echo "N/A";
                                                                } else {
                                                                    echo $row4['exam_score'];
                                                                    $exam_score_total += $row4['exam_score'];
                                                                    $total_marks += $row4['exam_score'];
                                                                }
                                                            }
                                                        }else{
                                                          echo "N/A";
                                                        }
                                                    ?>
                                                </td>
                                                <td style="text-align: center; border: 2px solid #520452">
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
                                                                if($row4['mark_obtained'] == NULL) {
                                                                    echo "N/A";
                                                                } else {
                                                                    echo $row4['mark_obtained'];
                                                                    $total_marks;
                                                                }
                                                            }
                                                        }else{
                                                          echo "N/A";
                                                        }
                                                    ?>
                                                </td>

                                                <td style="text-align: center; border: 2px solid #520452">
                                                    <?php
                                                        $row4['mark_obtained'] = $obtained_mark_query->row()->mark_obtained;
                                                        
                                                        if($obtained_mark_query->num_rows() > 0){
                                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                                $grade_level = $this->crud_model->get_grade_level($grade['grade_point']);

                                                                echo $grade_level;
                                                               // $total_grade_point += $grade['grade_point'];
                                                            }
                                                            
                                                            if($row4['mark_obtained'] == NULL || $row4['mark_obtained'] == '') {
                                                                    echo "N/A";
                                                                }
                                                        }else{
                                                          echo "N/A";
                                                        }
                                                    ?>
                                                </td>
                                             
                                                <td style="text-align: center; border: 2px solid #520452">

                                                    <?php
                                                        if($obtained_mark_query->num_rows() > 0) {
                                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);

                                                                if($grade['name'] == 'APPROACHING PROFICIENCY') {
                                                                    $grade['name'] = 'APPROACHING P.';
                                                                }

                                                                echo $grade['name'];
                                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                                            }

                                                            if($row4['mark_obtained'] == NULL || $row4['mark_obtained'] == '') {
                                                                    echo "N/A";
                                                                }
                                                        }else{
                                                          echo "N/A";
                                                        }
                                                    ?>

                                                    <?php
                                                        /*$this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $student_id, $running_year, $running_term);*/
                                                    ?>
                                                </td>
                                            </tr>

                                            <?php

                                            if(count($subjects) <= 5) {
                                                echo '<tr><td></td></tr>
                                                <tr><td></td></tr>
                                                <tr><td></td></tr>';
                                            } else {
                                                echo '
                                                <tr><td></td></tr>
                                            ';
                                            }
                                            
                                    
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

                                        if($subj_mark == null || $subj_mark == '' || $subj_mark == 0) {
                                            $marks_array[$ar] = '0';
                                        } else {
                                            $marks_array[$ar] = $subj_mark;
                                        }
                                        

                                        $ar++;

                                    endforeach;?>

                                        <!-- <tr>
                                            <th colspan="" style="text-align: center;">TOTAL</th>
                                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                                            <td colspan="2"></td>
                                        </tr>
         -->
                                            


                                        <?php
                                            $grandTotal = $total_marks?$total_marks:'N/A';
                                            $student_average = 0;

                                            if($grandTotal != 'N/A' || $grandTotal != '0') {
                                                $totalMark = $total_subjects * 100;
                                                $markPerc = $grandTotal * 100;
                                                $student_average = $markPerc / $totalMark;

                                                $student_grade = $this->crud_model->get_grade($student_average);

                                                $student_grade_level = $this->crud_model->get_grade_level($student_grade['grade_point']);
                                            } else {
                                                $student_grade_level = 'N/A';
                                            }  
                                        ?>

                                        <script type="text/javascript">

                                            $(function(e) {

                                                $('#grandTotal_<?=$student_id;?>').text('<?=$grandTotal;?>');
                                                $('#gradeLevel_<?=$student_id;?>').text('<?=$student_grade_level;?>');

                                                /*Graph display*/
                                                let prev_average = Number('<?=round($prev_student_average, 2);?>');
                                                let cur_average = Number('<?=round($student_average, 2);?>');


                                                var options = {
                                                  series: [{
                                                  data: [prev_average, cur_average]
                                                }],
                                                  chart: {
                                                  type: 'bar',
                                                  height: 150,
                                                  toolbar: {
                                                    tools: {
                                                        download: false,
                                                    }
                                                  }
                                                },
                                                plotOptions: {
                                                  bar: {
                                                    barHeight: '100%',
                                                    distributed: true,
                                                    horizontal: true,
                                                    dataLabels: {
                                                        enabled: false,
                                                      position: 'bottom'
                                                    },
                                                  }
                                                },
                                                colors: ['#33b2df', '#b90606',
                                                ],
                                                dataLabels: {
                                                  enabled: true,
                                                  textAnchor: 'start',
                                                  style: {
                                                    colors: ['#fff']
                                                  },
                                                  formatter: function (val, opt) {
                                                    return opt.w.globals.labels[opt.dataPointIndex] +  ' ' + val + '%'
                                                  },
                                                  offsetX: 0,
                                                  dropShadow: {
                                                    enabled: true
                                                  },
                                                  
                                                },
                                                stroke: {
                                                  width: 1,
                                                  colors: ['#fff']
                                                },
                                                xaxis: {
                                                    categories: ['', ''],
                                                    labels: {
                                                        show: true,
                                                        rotate: -45,
                                                        rotateAlways: true,
                                                        formatter: function (value) {
                                                          return value + "%";
                                                        },
                                                        style: {
                                                            fontSize: '10px',
                                                        }
                                                      },
                                                      min: 0,
                                                      max: 100,
                                                  
                                                },
                                                yaxis: {
                                                  labels: {
                                                    show: false
                                                  }
                                                },
                                                title: {
                                                    text: '',
                                                    align: 'center',
                                                    floating: true
                                                },
                                                subtitle: {
                                                    text: '',
                                                    align: 'center',
                                                },
                                                legend: {
                                                    customLegendItems: ['PREVIOUS', 'CURRENT'],
                                                    fontSize: '8px',
                                                    floating: true,
                                                    position: 'top',
                                                    size: 10,
                                                },
                                                tooltip: {
                                                  theme: 'dark',
                                                  x: {
                                                    show: false
                                                  },
                                                  y: {
                                                    title: {
                                                      formatter: function () {
                                                        return ''
                                                      }
                                                    }
                                                  }
                                                }
                                                };

                                                var chart = new ApexCharts(document.querySelector('#chart_<?=$student_id;?>'), options);
                                                chart.render();
                                            });
                                        </script>

                                    </tbody>
                                   </table>

                                </td>

                                <td valign="top" align="center" width="150">
                                    <div class="text-center" style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; margin-top:  -5px; color: #fff"><strong>PUPILS</strong></div>

                                    <div class="text-center" style="height: 180px; width: 180px; border: 2px solid #520452; margin-top: 5px">
                                        <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="img-circle" width="180" height="180"/>
                                    </div>

                                    <?php
                                        $student_name = $this->db->get_where('student' , array('student_id' => $student_id))->row()->name;
                                    ?>
                                    <div class="text-center" <?=strlen($student_name) < 26 ? 'style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; margin-top: 5px; color: #fff"' : 'style="padding: 3px 0px 3px 0px; font-size: 8px; height: 10px; background: #520452; margin-top: 5px; color: #fff"'; ?>><?php echo strtoupper($student_name);?><strong></strong></div>

                                    <div class="text-center" style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; margin-top: 5px; color: #520452"><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->student_code);?><strong></strong></div>

                                    <div class="text-center" style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; margin-top: 5px; color: #8b758b"><strong>HEAD MASTER'S SIGNATURE</strong></div>

                                    <div class="text-center" style="padding: 3px; height: 50px; margin-top: -10px">
                                        <img src="<?php echo $this->crud_model->get_head_teacher_signature();?>" width="100" />
                                    </div>

                                    
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5"></td>
                                <td>
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        PROMOTION
                                    </div>
                                </td>
                                <td width="80" align="center">
                                    <div class="text-center" style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;"><strong><?php echo $promoted_to_class; ?></strong></div>      
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="border: 2px solid #520452;"></div>

                    <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse; margin-top: 0px;">
                       <tbody>
                            <tr>
                                <td>
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        ATTENDANCE
                                    </div>
                                </td>
                                <td align="center" width="100">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;"><?php echo $att_present;?></div>
                                </td>
                                <td align="center" width="100">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;"><?php echo $att_total;?></div>
                                </td>
                                <td width="20"></td>
                                <td width="150" align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        <strong>CONDUCT</strong>
                                    </div>
                                </td>
                                <td width="40"></td>
                                <td width="130">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        TEACHER'S REMARKS
                                    </div>
                                </td>
                                <td></td>
                            </tr>

                            <tr>
                                <td width="40">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        ATTITUDE
                                    </div>
                                </td>
                                <td align="center" colspan="2">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;">
                                        <?=$attitude; ?>
                                    </div>
                                </td>
                                <td width="20"></td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;">
                                        <?=$conduct; ?>
                                    </div>
                                </td>
                                <td width="40"></td>
                                <td colspan="2" align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;">
                                        <?=$class_teacher_remarks; ?>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td valign="middle" align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 20px; background: #520452; color: #fff">
                                        <strong>NEXT TERM RESUMES</strong>
                                    </div>
                                </td>
                                <td align="center" colspan="2">
                                    <div  style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;">
                                        <strong>
                                            <?php $date = date_create($next_term_begins); echo date_format($date, 'l F d, Y'); ?>
                                                
                                        </strong>
                                    </div>
                                </td>
                                <td width="20"></td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        <strong>INTEREST</strong>
                                    </div>
                                </td>
                                <td width="40"></td>
                                <td>
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        TEACHER
                                    </div>
                                </td>
                                <td></td>
                            </tr>

                            <tr>
                                <td colspan="3"></td>
                                <td width="20"></td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;">
                                        <?=$interest; ?>
                                    </div>
                                </td>
                                <td width="40"></td>
                                <td align="center" colspan="2">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;">
                                        <strong>
                                            <?php
                                                $teacher_id = $this->db->get_where('class', ['class_id' => $class_id])->row()->teacher_id;
                                                $teacher_name = $this->db->get_where('teacher', ['teacher_id' => $teacher_id])->row()->name;

                                                echo strtoupper(strtolower($teacher_name));
                                            ?>
                                        </strong>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                               <td colspan="2">
                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                        HEAD TEACHER'S REMARKS
                                    </div>
                               </td>
                               <td align="center" colspan="6">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;">
                                        <?=$head_teacher_remarks; ?>
                                    </div>
                                </td>
                            </tr>
                       </tbody>
                   </table>
                </div>
            </div>

            <div>
                <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse; margin-top: 0px;">
                       <tbody>
                        <tr>
                            <td>
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div style="padding: 3px 5px 3px 5px; margin-bottom: -8px; height: 10px; background: #b90606; color: #fff">
                                                    <strong>PREVIOUS TERM'S PERFORMANCE</strong>
                                                </div>
                                            </td>                                
                                        </tr>
                                        <tr class="performances" >
                                            <td style="border: 2px solid #b90606">
                                                    <table>
                                                        <tbody>
                                                            <tr>
                                                              
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>RAW SCORE</strong>
                                                                    </div>
                                                               </td>
                                                               <td width="30" align="center">
                                                                    <div style="padding: 3px 0px 3px 0px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=$previous_term_grand_score; ?></strong></div>
                                                                </td>
                                                                <td align="center" style="padding: 0px"><strong>OF</strong></td>
                                                                <td width="30" align="center">
                                                                    <div style="padding: 3px 0px 3px 0px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=$prev_totalMark; ?></strong></div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>AVERAGE</strong>
                                                                    </div>
                                                               </td>
                                                               <td align="center" colspan="3">
                                                                    <div style="padding: 3px 0px 3px 0px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                                                        <?=number_format($prev_student_average, 2, '.', ','); ?>%
                                                                    </strong></div>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td></td>
                                                            </tr>

                                                            <tr>
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>LEVEL AND GRADE</strong>
                                                                    </div>
                                                               </td>
                                                               <td width="100" align="center" colspan="3">
                                                                    <div style="padding: 3px 0px 3px 0px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                                                        <?=$prev_student_grade_level; ?>
                                                                    </strong></div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>CLASS AVERAGE</strong>
                                                                    </div>
                                                               </td>
                                                               <td align="center" colspan="3">
                                                                    <div style="padding: 3px 0px 3px 0px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=number_format($prev__average2, 2, '.', ','); ?>%</strong></div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td width="350" colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>CLASS LEVEL AND GRADE</strong>
                                                                    </div>
                                                               </td>
                                                               <td align="center" colspan="3">
                                                                    <div style="padding: 3px 0px 3px 0px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=$prev_class_grade_level; ?></strong>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td><!-- Previous performance -->

                            <td>
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div style="padding: 3px 5px 3px 5px; margin-bottom: -8px; height: 10px; background: #b90606; color: #fff">
                                                    <strong>CURRENT TERM'S PERFORMANCE</strong>
                                                </div>
                                            </td>                                
                                        </tr>
                                        <tr class="performances" >
                                            <td style="border: 2px solid #b90606">
                                                    <table>
                                                        <tbody>
                                                            <tr>
                                                              
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>RAW SCORE</strong>
                                                                    </div>
                                                               </td>
                                                               <td  width="30" align="center">
                                                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=$grandTotal; ?></strong></div>
                                                                </td>
                                                                <td align="center" style="padding: 0px;"><strong>OF</strong></td>
                                                                <td width="30" align="center">
                                                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=$totalMark; ?></strong></div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>AVERAGE</strong>
                                                                    </div>
                                                               </td>
                                                               <td align="center" colspan="3">
                                                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                                                        <?=number_format($student_average, 2, '.', ','); ?>%
                                                                    </strong></div>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td></td>
                                                            </tr>

                                                            <tr>
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>LEVEL AND GRADE</strong>
                                                                    </div>
                                                               </td>
                                                               <td  width="100" align="center" colspan="3">
                                                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                                                        <?=$student_grade_level; ?>
                                                                    </strong></div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>CLASS AVERAGE</strong>
                                                                    </div>
                                                               </td>
                                                               <td align="center" colspan="3">
                                                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=number_format($current_average2, 2, '.', ','); ?>%</strong></div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td width="300" colspan="2">
                                                                   <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                                        <strong>CLASS LEVEL AND GRADE</strong>
                                                                    </div>
                                                               </td>
                                                               <td align="center" colspan="3">
                                                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?=$class_grade_level; ?></strong>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td><!-- Current performance -->

                            <td width="220" rowspan="2">
                                <div style="padding: 3px 5px 3px 5px; height: 208px; border: 2px solid #520452; color: #520452;">
                                    <div style="margin-top: 15px" id="chart_<?=$student_id;?>"></div>
                                </div>
                            </td><!-- Graph view -->
                        </tr>

                        <!-- TOTALS -->
                        <tr>
                            <td colspan="2">
                                <table style="width: 100%">
                                    <tbody>
                                        <tr>
                                            <td colspan="3"></td>

                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; background: #9b9595; color: #b90606">
                                                    <strong>GRAND SCORE</strong>
                                                </div>
                                            </td>
                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; background: #9b9595; color: #b90606">
                                                    <strong>AVERAGE</strong>
                                                </div>
                                            </td>
                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; background: #9b9595; color: #b90606">
                                                    <strong>LEVEL GRADE</strong>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"></td>
                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; background: #520452; color: #fff">
                                                    <strong>TERM 1 - 3</strong>
                                                </div>
                                            </td>
                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;"><strong><?=$grandTotal; ?></strong>
                                                </div>
                                            </td>
                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;"><strong><?=number_format($student_average, 2, '.', ','); ?>%</strong>
                                                </div>
                                            </td>
                                            <td align="center">
                                                <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #520452;"><strong><?=$student_grade_level; ?></strong>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            
                        </tr>
                       </tbody>
                   </table>


                   <!--Next Term Billing -->
                   <!-- <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
                        <thead>
                            <tr>
                                <td colspan="2" style="padding: 0px">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; margin-top: -12px; background-color: #b90606; color: #fff"><strong>NEXT TERM BILL</strong>
                                    </div>
                                </td>
                                <td style="padding: 0px 5px 0px 0px" colspan="6">
                                    <div style="border: 2px solid #520452; width: 100%"></div>
                                </td>
                            </tr>

                            <tr>
                                <td width="50">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #520452; color: #fff"><strong>ARREARS</strong>
                                    </div>
                                </td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                        <?//=numfmt_format_currency($fmt, $arrears, $currency);
                                        ?>
                                    </strong>
                                    </div>
                                </td>

                                <td width="120">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #520452; color: #fff"><strong>TERM TUITION</strong>
                                    </div>
                                </td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong><?//=numfmt_format_currency($fmt, $tuition_next_term, $currency);
                                        ?></strong>
                                    </div>
                                </td>

                                <td width="100">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #520452; color: #fff"><strong>OTHER FEES</strong>
                                    </div>
                                </td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                        <?//=numfmt_format_currency($fmt, $others_next_term, $currency);
                                        ?></strong>
                                    </div>
                                </td>

                                <td width="100">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #520452; color: #fff"><strong>PRINTING FEE</strong>
                                    </div>
                                </td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                        <?//=numfmt_format_currency($fmt, $printing_next_term, $currency);
                                        ?></strong>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6"></td>
                                <td width="100">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #b90606; color: #fff"><strong>TOTAL</strong>
                                    </div>
                                </td>
                                <td align="center">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; border: 2px solid #520452; color: #b90606;"><strong>
                                        <?php
                                            //$totalOwing = $arrears + $tuition_next_term + $others_next_term + $printing_next_term;

                                            //echo numfmt_format_currency($fmt, $totalOwing, $currency);
                                        
                                        ?>
                                    </strong>
                                    </div>
                                </td>
                            </tr>
                        </thead>
                    </table> -->

                    <br><br>
                    <!-- Interpretation -->
                    <div style="border: 2px solid red;">
                    <table style="width: 100%; border-collapse: collapse">
                        <thead>
                            <tr>
                                <!-- <td colspan="2" style="padding: 0px">
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; margin-top: -17px; background-color: #520452; color: #fff"><strong>INTERPRETATION</strong>
                                    </div>
                                </td> -->
                                <td colspan="2" style="padding: 0px">
                                    <div style="padding: 3px 5px 3px 5px; height: -10px; margin-top: 10px; background-color: #520452; color: #fff"><strong>INTERPRETATION</strong>
                                    </div>
                                </td>
                                <td style="padding: 0px 5px 0px 0px" colspan="6">
                                    <div></div>
                                </td>
                            </tr>

                            <tr style="margin-top: -40px !important;"><!-- row 1 -->
                                <?php 

                                $grades = 1;
                                $td_counter = 0; 
                                $td_counter2 = 0;
                                $first_row_full = true;

                                foreach($grading as $grademark_row): 

                                    if($td_counter == 3) {
                                        $td_counter = 3;

                                        if($first_row_full) {
                                    ?>
                                    </tr> <!-- First row is full, move to the next one -->
                                <?php } 
                                    $first_row_full = false;

                                    if($td_counter2 == 0) {
                                        /*print the row only once at a time*/
                                        echo '<tr>';/*next row*/
                                    }
                                ?>
                                    
                                        <?php
                                            if($td_counter2 == 1) {
                                                echo '<td></td>';
                                            } /*Insert 1 column after the first column*/
                                        ?>
                                        <td>
                                            <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #997272; color: #fff">
                                                <strong>
                                                    <?php echo $this->crud_model->get_grade_level($grademark_row['grade_point']).' (' .$grademark_row['mark_from'].'% - '. $grademark_row['mark_upto'].'%) - '.$grademark_row['name'].'('.$grademark_row['grade_point'].')';
                                            
                                                    
                                                    ?>
                                                </strong>

                                            </div>
                                        </td>
                                    
                                <?php
                                    $td_counter2++;

                                    if($td_counter2 == 3) {
                                        echo '</tr>';
                                        $td_counter2 = 0;
                                    }
                                    
                                 } else {?>

                                <?php
                                    if($td_counter == 1) {
                                        echo '<td></td>';
                                    }
                                ?>
                                <td>
                                    <div style="padding: 3px 5px 3px 5px; height: 10px; background-color: #997272; color: #fff">
                                        <strong>
                                            <?php echo $this->crud_model->get_grade_level($grademark_row['grade_point']).' (' .$grademark_row['mark_from'].'% - '. $grademark_row['mark_upto'].'%) - '.$grademark_row['name'].'('.$grademark_row['grade_point'].')';
                                    
                                            $td_counter++;
                                            ?>
                                        </strong>

                                    </div>
                                </td>
                                <?php } endforeach; ?>
                                
                            </tr>

                        </thead>
                    </table><!-- End of interpretation -->
                </div>

                <div style="padding: 3px; height: 26px; background-color: #520452; color: #fff; margin-top: 2px">
                    <table style="width: 100%; border-collapse: collapse">
                        <thead>
                            <tr>
                                <th align="left"><strong>POSTAL ADDRESS:</strong></th>
                                <th align="left"><strong><?=strtoupper($system_postal_address); ?></strong></th>
                                <th align="left"><strong>EMAIL ADDRESS:</strong></th>
                                <th align="left"><strong><?=strtoupper($system_email); ?></strong></th>
                                <th align="left"><strong>LOCATION:</strong></th>
                                <th align="left"><strong></strong><?=$system_location; ?></th>
                            </tr>

                            <tr>
                                <th></th>
                            </tr>
                            <tr>
                                <th></th>
                            </tr>

                            <tr>
                                <th align="left"><strong>TELEPHONE NUMBER:</strong></th>
                                <th align="left"><strong><?=$system_phone; ?></strong></th>
                                <th align="left"><strong>WEBSITE:</strong></th>
                                <th align="left"><strong><?=strtoupper($system_website_address); ?></strong></th>
                                <th align="left"><strong>DIGITAL ADDRESS:</strong></th>
                                <th align="left"><strong><?=strtoupper($system_digital_address); ?></strong></th>
                            </tr>

                        </thead>
                    </table>
                </div>
            </div> 


            <div class="row sign">
                <div class="col-lg-12 col-md-12">
                    <div style="position: relative; left: 0px; color: #c1700e">
                        <small><em><strong>Software: Developed & Designed By Lightworld Technologies Ltd Team : +233243618186 | 0247612799</strong></em></small>
                    </div>
                </div>
            </div>


        </page>
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

        //add checkbox class to checkbox input types
        $('input[type="checkbox"]').removeClass('form-control');
        $('input[type="checkbox"]').addClass('checkbox');

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
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/apexchart/apexcharts.css');?>"/>');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.css');?>" />');

        mywindow.document.write('<script src="<?php echo base_url('assets/apexchart/apexcharts.min.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 9px">');
        mywindow.document.write(data);

        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }


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