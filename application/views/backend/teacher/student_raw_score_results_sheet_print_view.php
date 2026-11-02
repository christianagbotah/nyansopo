<?php

    $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';

    $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
    $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
    $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
    $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
    $running_year       =   $year;
    $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
    $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
    
    // Fetch term dates from terms table
    $termRows = getArchivedTermStartsEndsDate($term, $year);
    if($termRows->term_ending == '' || empty($termRows->term_ending)) {
        $term_ending = '-';
        $next_term_begins = '-';
    } else {
        $term_ending = $termRows->term_ending;
        $next_term_begins = $termRows->next_term_begins;
    }
    
    $running_term       =   $term;
    $no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0','term' => $running_term, 'section_id' => $section_id, 'year' => $running_year))->num_rows();
    $teacher_remarks            =   $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->row()->remarks;

    /** $att_undefined = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'term' => $running_term, 'status' => '0'))->num_rows();
    $att_present = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'term' => $running_term, 'status' => '1'))->num_rows();
    $att_absent = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'term' => $running_term, 'status' => '2'))->num_rows();

    $att_total = $att_undefined + $att_present + $att_absent;
    **/

    // Fetch days_opened from terms table
    $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $running_term))->row();
    $att_total = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : 0;

    // Calculate days_present from attendance table
    $this->db->select('COUNT(*) as days_present');
    $this->db->from('attendance');
    $this->db->where('student_id', $student_id);
    $this->db->where('year', $running_year);
    $this->db->where('term', $running_term);
    $this->db->where_in('status', array(1, 3)); // Status: 1=Present, 3=Late
    $att_present_query = $this->db->get();
    $att_present = $att_present_query->row() ? (int)$att_present_query->row()->days_present : 0;
                        

    $gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;
    $raw_score_grading = $this->db->get('raw_score_grade')->result_array();

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
    $this->db->where('can_delete !=', 'trash');
    $invoices_owe_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)))->num_rows();

    $this->db->where('can_delete !=', 'trash');
    $invoices_owe =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)))->result_array();

    //feeding fee owe
    $feeding_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
    $feeding_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;

    //classes fee owe
    $classes_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->num_rows();
    $classes_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->row()->cdue;

    //transport fee owe
    $transport_owe_row =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
    $transport_owe =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;
    //arrears end

    //Next term bills

    //invoices owe
    $this->db->where('can_delete !=', 'trash');
    $invoices_next_term_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->num_rows();

    $this->db->where('can_delete !=', 'trash');
    $invoices_next_term =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->result_array();


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

                                #aggregate {
                                    margin-left: 550px;
                                    margin-top:  -32px;
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
                                    top: 31px;
                                    right: 10px;

                                }

                                #top_row{
                                    margin-top: -55px;
                                }

                                #student_details p{
                                    line-height: 30px;
                                }

                                #promote{
                                    margin-top: -32px;
                                }

                                #aggregate {
                                    margin-left: 300px;
                                    margin-top:  -32px;
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
                                <span align="center" id="term">TERMINAL REPORT</span>

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

                                <h4 align='center' style="letter-spacing: 6px;"><?php echo $level;?></h4>
                            </div>
                            </center>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="img-circle" id="photo_passport" width="120" height="120"/>
                            </div>                         
                        </div> 
                          <hr>

                        <!--Details of the student here-->
                        <div class="row">
                            <div class="col-lg-12" id="student_details">
                                <p>This is to Certify that <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id))->row()->name);?></b> Position <b><?php
                            $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_term);?></b> of <?php echo ' <b>' . strtoupper($class_name). ' '. $class_name_numeric.$sec_name;?></b> has completed term <b><?php echo $running_term;?></b> 

                               of the <b><?php echo explode('-', $running_year)[1];?></b> academic year. No. on roll <b><?php echo $no_on_roll;?>.</b> Term ending <b><?php $date = date_create($term_ending); echo date_format($date, 'd/m/Y'); ?>.</b> Next Term Begins <b><?php $date = date_create($next_term_begins); echo date_format($date, 'd/m/Y'); ?>.</b></p>

                            </div>
                        </div> 


                        <!--Grading scale-->
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="table table-responsive">
                                    <table class="table table-bordered table-striped" id="grade_table" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
                                        <thead>
                                            <tr>
                                            <?php $grades = 1; foreach($raw_score_grading as $gradepoint_row): ?>
                                            
                                                <th style="text-align: center; font-weight: bold;"><?php echo $gradepoint_row['grade_point']; ?></th>
                                            
                                        <?php endforeach; ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <?php $grades = 1; foreach($raw_score_grading as $grademark_row): ?>
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
                                  <!--   <th style="text-align: center; font-weight: bold;">REMARK</th> -->
                                    <th style="text-align: center; font-weight: bold;">POSITION</th>
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
                                                    $grade = $this->crud_model->get_raw_score_grade($row4['mark_obtained']);
                                                    echo $grade['grade_point'];
                                                   // $total_grade_point += $grade['grade_point'];
                                                }
                                            }else{
                                              echo "N/A";
                                            }
                                        ?>
                                    </td>

                                    <?php
                                        //finding aggregat
                                                //for core subjects
                                               if($row3['status'] == 1) {
                                                    $core_grade = $this->crud_model->get_raw_score_grade($row4['mark_obtained']);
                                                    $total_aggregate += $core_grade['grade_point'];
                                                }
                                    ?>
                                 <!--    <td style="text-align: center;">
                                        <?php
                                            if($obtained_mark_query->num_rows() > 0){
                                                if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                    $grade = $this->crud_model->get_raw_score_grade($row4['mark_obtained']);
                                                    echo $grade['name'];
                                                   // $total_grade_point += $grade['grade_point'];
                                                }
                                            }else{
                                              echo "N/A";
                                            }
                                        ?>
                                    </td> -->
                                    <td style="text-align: center;">
                                        <?php
                                            $this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $row4['student_id'], $running_year, $running_term);
                                        ?>
                                    </td>
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
                                <td style="text-align: center; background-color: #e3e2f1;"><strong id="total_aggregate"><?php echo $total_aggregate;  ?></strong></td>
                                <td colspan="1"></td>
                            </tr>
                        </tbody>
                       </table>
                    </div>

                    <!--Attendance -->
                    <div class="row" style="margin-top: 8px;">
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <table class="table">
                                <tr>
                                    <td>Attendance</td>
                                    <td><b><?php echo $att_present;?></b></td>
                                    <td>Out of</td>
                                    <td><b><?php echo $att_total; ?>.</b></td>
                                </tr>
                            </table>
                            
                        </div><!--//Attendance ends-->

                        <!--Aggregate and Raw Score -->
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" id="aggregate">
                            <table class="table">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>Raw Score: <strong><u><?php $raw_score = $sum_core + $sum_best2; echo $raw_score; ?></u></strong></td>
                                    <td>Total Aggregate: <strong><u><?php echo $total_aggregate; ?></u></strong></td>
                                </tr>
                            </table>
                        </div><!--//Aggregate and Raw Score ends-->

                        <!--Promotion -->
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3" style="position: relative; float: right" id="promote">
                             <table class="table">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>Promoted to</td>
                                    <td><b><?php echo $promoted_to_class; ?></b></td>
                                </tr>
                            </table>
                        </div><!--//Promotion ends-->
                    </div> <!-- Promotion ends-->

                <?php 

                    $conducts_counter = 0;
                      $num_columns = 0;

                    $selection = array('c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9', 'c10', 'c11', 'c12', 'c13', 'c14', 'c15');

                    //run query
                    $this->db->select($selection);
                    $this->db->from('aggregation');
                    $this->db->where('exam_id', $exam_id);
                    $this->db->where('student_id', $student_id);
                    $this->db->where('class_id', $class_id);
                    $this->db->where('section_id', $section_id);
                    $this->db->where('term', $running_term);
                    $this->db->where('year', $running_year);
                    $checked_conducts = $this->db->get()->result_array();

                    $ci_counter = 0;
                    foreach($checked_conducts as $c) {
                        for($cj = 1; $cj <= 15; $cj++) {
                            if($c['c'.$cj] != null || $c['c'.$cj] != '') {
                                $ci_counter++;
                            }
                        }
                    }

                    $conducts_data = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'term' => $running_term, 'year' => $running_year))->result_array();

                    if($ci_counter != 0) {
                     
                    foreach ($conducts_data as $key => $conduct) {       
                            ?>
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <?php echo form_open(site_url('admin/conducts_update'));?>
                                <div class="table table-responsive">
                                    <table class="table table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 0px solid #000; margin-top: 10px;" border="0">
                                        <caption><u><em>Conducts of <strong><?php echo ucwords(strtolower(($this->db->get_where('student' , array('student_id' => $student_id))->row()->name)));?></strong></em></u></caption>
                                        <tbody>
                                            <tr>

                                            <?php
                                
                                for($i = 1; $i <= 15; $i++){

                                    if($conduct['c'.$i] != '' || $conduct['c'.$i] != null) {
                                    ?>
                                        <td><input type="checkbox" id="ex" class="form-control" name="conducts[]" value="<?= $conduct['c'.$i];?>" checked></td>
                                        <td><label class="form-control-label"><?= $conduct['c'.$i];?></label></td>
                                    <?php 
                                    $conducts_counter++;

                                    //check for the number of columns checked
                                    if($ci_counter <= 5 || $ci_counter == 10 || $ci_counter == 9 ||  $ci_counter == 13 ||  $ci_counter == 14 ||  $ci_counter == 15) {
                                        $num_columns = 5;
                                    } else if($ci_counter == 6) {
                                        $num_columns = 3;
                                    } else if($ci_counter <= 8 || $ci_counter == 11 || $ci_counter == 12) {
                                        $num_columns = 4;
                                    }
                                    //draw another row if the number of conduct on the first row reaches 5
                                    if($conducts_counter == $num_columns) {
                                        //reset the value of $conducts_counter to 1;
                                        $conducts_counter = 1;
                                        $i++; //starts the listing from the next value

                                        //check if the next c value is empty
                                        if($conduct['c'.$i] == '' || $conduct['c'.$i] == Null) {
                                            break; //breaks the loop if the next c value is empty or returns null.
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="form-control" name="conducts[]" value="<?= $conduct['c'.$i];?>" <?php echo 'checked'; ?>></td>
                                        <td><label class="form-control-label"><?= $conduct['c'.$i];?></label></td>
                                        <?php
                                         }
                                     } ?>
                                        
                                    <?php
                                }
                              
                            ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><hr>
                <!--Conducts-->

                        <?php
                            }
                        } else {
                            echo 'Conduct Not Available';
                        }

                        ?>
                        <!--Remarks and signatures of the teacher and principal-->
                        <div class="row">
                            <div class="col-lg-12 col-md-12" id="remark">
                                <p>Class Teacher's Remark: <em><strong><?php echo ucwords(strtolower($teacher_remarks));?></strong></em></p>
                            </div>
                        </div>

                        <!--Charts and Account Section-->
                        <div class="row">
                            <div>
                                <table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;" border="0" id="chart_account">
                                    <thead></thead>
                                    <tbody>
                                        <tr>
                                            <td width="15%">
                                                <!--Chart Section here-->
                                                <canvas id="marks_chart"></canvas>
                                                <!--Chart-->
                                               
                                            </td>
                                            <td width="35%" align="right" valign="top" id="arrears_tb">
                                                <!--Account Section here (arrears)-->
                                                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 10px" border="1">
                                                     <thead>
                                                        <tr>
                                                            <th>ITEM</th>
                                                            <th style="text-align: right">AMOUNT</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2">PREVIOUS ARREARS</th>
                                                        </tr>
                                                        <?php 
                                                        //Invoices owe (arrears)
                                                        $invoice_arrears_total = 0;

                                                        if($invoices_owe_row == 0 && $feeding_owe_row == 0 && $classes_owe_row == 0 && $transport_owe_row == 0) {
                                                            /*echo '<tr><td colspan="2">No Arrears Found!</td></tr>';*/
                                                        }

                                                        $rows_counter = 0;

                                                        if($invoices_owe_row > 0):
                                                            foreach($invoices_owe as $inv_row):

                                                                //show only if it is not next term and next years' bill
                                                                if($inv_row['term'] != $next_term && $inv_row['year'] != $next_year) {

                                                                    $rows_counter++; //increment
                                                                    $exp = explode('-',$inv_row['year']);
                                                        ?>
                                                        <tr>
                                                            <td><?= $inv_row['title'].' <strong>[Term|Year: '.$inv_row['term'].'|'.$exp[1].']'; ?></td>
                                                            <td align="right"><?= numfmt_format_currency($fmt, $inv_row['due'], $currency); ?></td>
                                                        </tr>
                                                        <?php 
                                                            $invoice_arrears_total = $invoice_arrears_total + $inv_row['due'];

                                                                    }
                                                            endforeach;
                                                        endif;
                                                        //Feeding, Classes and Transport in arrears
                                                            if($feeding_owe_row > 0):
                                                        ?>
                                                        <tr>
                                                            <td>FEEDING FEE</td>
                                                            <td align="right"><?= numfmt_format_currency($fmt, $feeding_owe, $currency); ?></td>
                                                        </tr>
                                                        <?php 
                                                            endif;
                                                            if($classes_owe_row > 0):
                                                        ?>
                                                        <tr>
                                                            <td>CLASSES FEE</td>
                                                            <td align="right"><?= numfmt_format_currency($fmt, $classes_owe, $currency); ?></td>
                                                        </tr>
                                                        <?php 
                                                            endif;
                                                            if($transport_owe_row > 0):
                                                        ?>
                                                        <tr>
                                                            <td>TRANSPORT FARE</td>
                                                            <td align="right"><?= numfmt_format_currency($fmt, $transport_owe, $currency); ?></td>
                                                        </tr>
                                                        <?php 
                                                            endif;

                                                            $arrears_total = $invoice_arrears_total + $feeding_owe + $classes_owe + $transport_owe;
                                                        ?>
                                                        <tr>
                                                            <th>SUB-TOTAL</th>
                                                            <td align="right"><strong><?= numfmt_format_currency($fmt, $arrears_total, $currency); ?></strong></td>
                                                        </tr>

                                                        <?php
                                                            if($rows_counter == 0 && $feeding_owe_row == 0 && $classes_owe_row == 0 && $transport_owe_row == 0) {
                                                                echo '<tr><td colspan="2">No Arrears Found!</td></tr>';
                                                            }
                                                        ?>

                                                     </thead>
                                                </table>
                                            </td>
                                            <td width="25%" align="right" valign="top">
                                                <!--Account Section here (next term billing)-->
                                                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 10px" border="1">
                                                     <thead>
                                                        <tr>
                                                            <th>ITEM</th>
                                                            <th style="text-align: right">AMOUNT</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2">NEXT TERM BILLS</th>
                                                        </tr>
                                                        <?php 
                                                        //Invoices owe (next term)
                                                        $invoice_next_term_total = 0;

                                                        if($invoices_next_term_row > 0) {
                                                            foreach($invoices_next_term as $next_row):
                                                        
                                                        ?>
                                                        <tr>
                                                            <td><?= $next_row['title']; ?></td>
                                                            <td align="right"><?= numfmt_format_currency($fmt, $next_row['due'], $currency); ?></td>
                                                        </tr>
                                                        <?php 
                                                            $invoice_next_term_total = $invoice_next_term_total + $next_row['due'];
                                                            endforeach;

                                                            } else {
                                                                echo '<tr><td colspan="2">No Bills Found For Next Term</td></tr>';
                                                            }
                                                        ?>
                                                        <tr><th>SUB-TOTAL</th><td align="right"><strong><?= numfmt_format_currency($fmt, $invoice_next_term_total, $currency); ?></strong></td></tr>

                                                     </thead>
                                                </table>

                                                 <!--Summation of the arrears and the bills for next term-->
                                                <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 10px" border="1">
                                                     <thead>
                                                        <tr>
                                                            <th>ARREARS</th>
                                                            <th>BILLS</th>
                                                            <th>TOTAL</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><?= numfmt_format_currency($fmt, $arrears_total, $currency); ?></td>
                                                            <td><?= numfmt_format_currency($fmt, $invoice_next_term_total, $currency); ?></td>
                                                            <td><strong><?= numfmt_format_currency($fmt, $arrears_total + $invoice_next_term_total, $currency); ?></strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row sign" style="font-weight: bold;">
                            <div class="col-lg-12 col-md-12">
                                <p>Class Teacher's Signature:...................................... &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Signature and Stamp of Principal........................................</p>
                                
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

        <?php 
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

    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>    
    
<script type="text/javascript">

    $(document).ready(function() {
        var total_aggregate = $('#total_aggregate').text();
        if(total_aggregate >= 15) {
            $('#total_aggregate').css('color', 'red');
        }else{
            $('#total_aggregate').css('color', 'green');
        }
    });
    
    $('input[type="checkbox"]').click(function() {
        return false;
    });

    $(document).ready(function() {
        var total_aggregate = $('#total_aggregate').text();
        if(total_aggregate >= 15) {
            $('#total_aggregate').css('color', 'red');
        }else{
            $('#total_aggregate').css('color', 'green');
        }
    });

    jQuery(document).ready(function($)
    {

        //add checkbox class to checkbox input types
        $('input[type="checkbox"]').removeClass('form-control');
        $('input[type="checkbox"]').addClass('checkbox');

        var elem = $('#print');
        //PrintElem(elem);
        //Popup(data);

    });

   
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        vvar mywindow = window.open('', '', '');
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
    //get the subjects and their respective total marks
    let subjects_array = <?php echo json_encode($subjects_array); ?>;
    let marks_array = <?php echo json_encode($marks_array); ?>;

    
    //Chart
    var ctx = document.getElementById('marks_chart').getContext('2d');
    var marks_chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: subjects_array,       
            datasets: [{
                label: 'Terminal Report Chart',
                data: marks_array,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(76, 76, 76, 0.7)',
                    'rgba(236, 23,  162, 0.7)',
                    'rgba(31,  146, 39, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(76, 76, 76, 1)',
                    'rgba(236, 23,  162, 1)',
                    'rgba(31,  146, 39, 1)',
                    'rgba(54, 162, 235, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    }); 

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

