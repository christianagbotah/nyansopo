
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

        <?php
            if(isset($sem)) {
                //for JHS
                ?>



<div class="container">
<div class="row" style="margin-top: 15px; position: fixed; z-index: 9999; background: white; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" id="print_div">
    <a href="javascript:void(0);" onClick="page_reload()" style="margin-right: 15px;">Print Bulk Report Sheet</a>
    <button id="toggleAccountBtn" onclick="toggleAccountSection()" style="padding: 5px 15px; cursor: pointer; background: #520452; color: white; border: none; border-radius: 3px;">
        <span id="toggleText">Hide Account/Billing Section</span>
    </button>
</div>
<div class="row">
<div class="col-lg-12">
<div id="print">

<?php

 $data_array2 =  array(
    'class_id' => $class_id, 
        'section_id' => $section_id, 
            'year' => $year,
                'status' => 'close', 
                    'sem' => $sem
            );

    $this->db->select('*');
    $this->db->where($data_array2);
    $this->db->where('mute', '0');
    $this->db->from('enroll');
    $num_r = $this->db->get()->num_rows();

    

/***************************************************************************************/
 $data_array =  array(
    'class_id' => $class_id, 
        'section_id' => $section_id, 
            'year' => $year, 
                'exam_id' => $exam_id,
                     'sem' => $sem
            );

    $this->db->select('student_id');
    $this->db->distinct();
    $this->db->where($data_array);
    $this->db->from('mark');
    $this->db->limit($num_r);
   // $this->db->join('mark', 'mark.exam_id ='. $exam_id);
    $students_marks = $this->db->get()->result_array();

    foreach($students_marks as $student_row):

    $student_id = $student_row['student_id'];

    $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
    $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
    $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
    $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
    $running_year       =   $year;
    $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
    $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
    
    // Fetch semester dates from terms table using year and semester from exam
    $termRows = getArchivedTermStartsEndsDate($sem, $year);
    if($termRows && $termRows->term_ending != '' && !empty($termRows->term_ending)) {
        $sem_ending = $termRows->term_ending;
        $next_sem_begins = $termRows->next_term_begins;
    } else {
        // Fallback to settings if term record not found
        $sem_ending = $this->db->get_where('settings', array('type'=>'sem_ending'))->row()->description;
        $next_sem_begins = $this->db->get_where('settings', array('type'=>'next_sem_begins'))->row()->description;
    }
    
    $running_sem       =   $sem;
    $no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', 'sem' => $running_sem, 'section_id' => $section_id, 'year' => $running_year))->num_rows();
    
    // Query aggregation - uses 'term' column (aggregation table doesn't have 'sem')
    $teacher_remarks_row    =   $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_sem, 'year' => $running_year))->row();
    
    // Load remark helper for ID/Text conversion
    $this->load->helper('remark');
    
    $teacher_remarks_raw    =   $teacher_remarks_row ? $teacher_remarks_row->remarks : '';
    $teacher_remarks        =   get_class_teacher_remark($teacher_remarks_raw);
    
    $head_teacher_remarks_raw   =   $teacher_remarks_row && isset($teacher_remarks_row->head_teacher_remarks) ? $teacher_remarks_row->head_teacher_remarks : '';
    $head_teacher_remarks       =   get_head_teacher_remark($head_teacher_remarks_raw);
    
    // Auto head teacher remark if empty
    if (empty($head_teacher_remarks) || trim($head_teacher_remarks) == '') {
        if (!isset($Head_teacher_remarks_model_loaded)) {
            $this->load->model('Head_teacher_remarks_model');
            $Head_teacher_remarks_model_loaded = true;
        }
        
        $student_percentage = $teacher_remarks_row && isset($teacher_remarks_row->student_percentage) 
            ? round($teacher_remarks_row->student_percentage) 
            : 0;
        
        if ($student_percentage > 0) {
            $auto_head_remark = $this->Head_teacher_remarks_model->find_by_percentage($student_percentage);
            
            if ($auto_head_remark && !empty($auto_head_remark->remark_text)) {
                $head_teacher_remarks = $auto_head_remark->remark_text;
            }
        }
    }
    
    // Load helper for interest display (handles both ID and text)
    $this->load->helper('report_display');
    $interest_raw           =   $teacher_remarks_row && isset($teacher_remarks_row->interest) ? $teacher_remarks_row->interest : '';
    $interest               =   get_interest_display($interest_raw);


    //incase the admin did not set correct sem ending or term ending, let's use current date

    //for sem
    if(strtotime($sem_ending) < $current_date) {
        $sem_ending = date('d-m-Y');
    }

    /** $att_undefined = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'sem' => $running_sem, 'status' => '0'))->num_rows();
    $att_present = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'sem' => $running_sem, 'status' => '1'))->num_rows();
    $att_absent = $this->db->get_where('attendance', array('class_id' => $class_id, 'student_id' => $student_id, 'section_id' => $section_id, 'year' => $running_year, 'sem' => $running_sem, 'status' => '2'))->num_rows();

    $att_total = $att_undefined + $att_present + $att_absent;
    **/

    // Fetch days_opened from terms table (using term from running_term, not sem)
    $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $running_term))->row();
    $att_total = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : 0;

    // Calculate days_present from attendance table
    $this->db->select('COUNT(*) as days_present');
    $this->db->from('attendance');
    $this->db->where('student_id', $student_id);
    $this->db->where('year', $running_year);
    $this->db->where('sem', $running_sem);
    $this->db->where_in('status', array(1, 3)); // Status: 1=Present, 3=Late
    $att_present_query = $this->db->get();
    $att_present = $att_present_query->row() ? (int)$att_present_query->row()->days_present : 0;


    $gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;

    $grading = $this->db->get('grade')->result_array();

    //Account section
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    $next_sem = $running_sem + 1;
    $next_year = $running_year;

    if($next_sem == 3) {
        $next_sem = 1;

        $p1 = explode('-', $running_year)[0] + 1;
        $p2 = explode('-', $running_year)[1] + 1;

        $next_year = $p1 .'-'. $p2;
    }

    //feeding and classes owe - using daily_fee_wallet instead of deprecated feeding_fee table
    // Get the most recent wallet balance for this student
    $wallet_row = $this->db->get_where('daily_fee_wallet', array('student_id' => $student_id))->row();
    $previous_day_timestamp = $wallet_row ? $wallet_row->last_updated : 0;


    //Arrears
    //invoices owe
    $invoices_owe_query = $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($sem_ending)));
    $invoices_owe_row =  $invoices_owe_query->num_rows();
    $invoices_owe =  $invoices_owe_query->result_array();

    //feeding fee owe
    /*$feeding_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
    $feeding_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;

    //classes fee owe
    $classes_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->num_rows();
    $classes_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->row()->cdue;

    //transport fee owe
    $transport_owe_row =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
    $transport_owe =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;*/

    $feeding_owe_row = 0;
    $classes_owe_row = 0;
    $transport_owe_row = 0;
    //arrears end

    //Next semester bills
    //invoices owe
    $invoices_next_sem_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'sem' => $next_sem, 'year' => $next_year))->num_rows();
    $invoices_next_sem =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'sem' => $next_sem, 'year' => $next_year))->result_array();

    //promotion
    $promoted_to = '';
    if($running_sem == 2) {
        $explode_running_year = explode('-', $running_year);
        $year_part1 = $explode_running_year[0] + 1;
        $year_part2 = $explode_running_year[1] + 1;

        $promoted_to_year = $year_part1. '-'. $year_part2;
        $promoted_to_sem = 1;

        $promoted_enrollment = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'sem' => $promoted_to_sem))->row();
        if($promoted_enrollment && isset($promoted_enrollment->class_id)){
            $promoted_to_class_id = $promoted_enrollment->class_id;
            $promoted_to_section_id = $promoted_enrollment->section_id;
            
            $promoted_class_row = $this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row();
            if($promoted_class_row){
                $promoted_to_class = $promoted_class_row->name. ' '.$promoted_class_row->name_numeric;
                
                $promoted_section_row = $this->db->get_where('section', array('section_id' => $promoted_to_section_id))->row();
                if($promoted_section_row) {
                    $promoted_to_class .= ' ('.$promoted_section_row->name.')';
                }
            }else{
                $promoted_to_class = 'N/A';
            }
        }else{
            $promoted_to_class = 'N/A';
        }
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

        div .logo{
            position: absolute;
            margin-top: 52px;
            left: 10px;
        }

        div .photo_passport {
            position: absolute;
            margin-top: -140px;
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
            position: relative;
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

            #top_row{
                margin-top: -55px;
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

            page {
                page-break-after: always;
            }

            .sign {
                position: absolute;
                bottom: 0;
                left: 15px;
                right: 15px;
                width: calc(100% - 30px);
            }
        }
    </style>
<page size="A4">
    <center>  
        <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px; margin-top: 10px"><?php echo $system_name;?></h3>
    </center>
    <div class="row" id="top_row">
        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
           <img class="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br> 
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
            <center>
            <h4 align='center' id="add"><?php echo $location;?></h4>
            <h4 align='center' id="box"><?php echo $address;?></h4>
            <h3 align="center" id="gh"><u>GHANA EDUCATION SERVICE</u></h3>
            <span align="center" id="term">SEMESTER REPORT</span>

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
            <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="img-circle photo_passport" width="120" height="120" />
        </div>                         
    </div> 
      <hr>

    <!--Details of the student here-->
    <div class="row">
        <div class="col-lg-12" id="student_details">

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
                                        <th><?=strtoupper($class_name). ' '. $class_name_numeric.' '.$sec_name; ?></th>
                                    </tr>

                                    <tr>
                                        <th>No. ON ROLL:</th>
                                        <th><?php echo $no_on_roll;?></th>
                                    </tr>

                                    <tr>
                                        <th>INTEREST:</th>
                                        <th><?php 
                                            $interest_row = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_sem, 'year' => $running_year))->row();
                                            if ($interest_row && $interest_row->interest) {
                                                $this->load->helper('report_display');
                                                echo strtoupper(get_interest_display($interest_row->interest));
                                            }
                                        ?></th>
                                    </tr>

                                    <tr>
                                        <!--<th>POSITION:</th>
                                        <th><?php
                                            //$this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_sem);?>     
                                        </th>-->
                                    </tr>
                                </thead>
                            </table>
                        </td> <!--left hand side ends-->

                        <td> <!--right hand side -->
                            <table style="width: 100%">
                                <thead align="right">
                                    <tr>
                                        <th>ACADEMIC YEAR:</th>
                                        <th><?php echo $running_year;?></th>
                                    </tr>

                                    <tr>
                                        <th>SEMESTER:</th>
                                        <th><?php echo $running_sem;?></th>
                                    </tr>

                                    <tr>
                                        <th>SEMESTER ENDING:</th>
                                        <th><?php $date = date_create($sem_ending); echo date_format($date, 'd-m-Y'); ?></th>
                                    </tr>

                                    <tr>
                                        <th>NEXT SEMESTER BEGINS:</th>
                                        <th><?php $date = date_create($next_sem_begins); echo date_format($date, 'd-m-Y'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </td>
                    </tr>
                </thead>
            </table>

        </div>
    </div> 

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
                </td> -->
                <td style="text-align: center;">
                    <?php
                        $this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $student_id, $running_year, $running_sem);
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

        } else if(str_word_count($row3['name'], 1)[0] == 'Ghanaian' || str_word_count($row3['name'], 1)[0] == 'GHANAIAN') {
            $subject_name = 'Ghanaian Lang.';

        } else if(str_word_count($row3['name'], 1)[0] == 'French' || str_word_count($row3['name'], 1)[0] == 'FRENCH') {
            $subject_name = 'French';

        } else if(str_word_count($row3['name'], 1)[0] == 'Language' || str_word_count($row3['name'], 1)[0] == 'LANGUAGE') {
            $subject_name = 'Lang & Lit';

        } else if(str_word_count($row3['name'], 1)[0] == 'Writing' || str_word_count($row3['name'], 1)[0] == 'WRITING') {
            $subject_name = 'Writing';
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
    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" style="position: relative; float: right; text-align: right;" id="promote">
        <strong>Promoted to:</strong> <b><?php echo $promoted_to_class; ?></b>
    </div>
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
$this->db->where('sem', $running_sem);
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

$conducts_data = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'section_id' => $section_id, 'sem' => $running_sem, 'year' => $running_year))->result_array();

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

                if($conduct['c'.$i] != '' || $conduct['c'.$i] != Null) {
                ?>
                    <td><input type="checkbox" id="ex" class="form-control" name="conducts[]" value="<?= $conduct['c'.$i];?>" checked></td>
                    <td><label for="ex" class="form-control-label"><?= get_conduct_display_value($conduct['c'.$i]);?></label></td>
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
                    <td><label class="form-control-label"><?= get_conduct_display_value($conduct['c'.$i]);?></label></td>
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
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; padding-right: 10px; vertical-align: top;">
                        Class Teacher's Remark: <em><strong><?php echo ucwords(strtolower($teacher_remarks));?></strong></em>
                    </td>
                    <td style="width: 50%; padding-left: 10px; vertical-align: top; text-align: right;">
                        Principal's Remark: <em><strong><?php echo ucwords(strtolower($head_teacher_remarks));?></strong></em>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!--Charts and Account Section-->
    <div class="row">
        <div>
            <table class="table table-bordered table-striped table-hover table-active chart_account_section" style="width:100%; border-collapse:collapse;" border="0" id="chart_account_sem">
                <thead></thead>
                <tbody>
                    <tr>
                        <td width="15%">
                            <!--Chart Section here-->
                            <canvas id="marks_chart_<?= $student_id; ?>"></canvas>
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
                                        <th colspan="2">PREVIOUS ARREARS [Sem | Year]</th>
                                    </tr>
                                    <?php 
                                    //Invoices owe (arrears)
                                    $invoice_arrears_total = 0;

                                    if($invoices_owe_row == 0 && $feeding_owe_row == 0 && $classes_owe_row == 0 && $transport_owe_row == 0) {
                                        /*echo '<tr><td colspan="2">No Arrears From Last Semester</td></tr>';*/
                                    }

                                    $rows_counter = 0;

                                    if($invoices_owe_row > 0):
                                        foreach($invoices_owe as $inv_row):

                                            //show only if it is not next sem and next years' bill
                                            if($inv_row['sem'] != $next_sem || $inv_row['year'] != $next_year) {

                                                $rows_counter++; //increment
                                                $exp = explode('-',$inv_row['year']);

                                                if($inv_row['due'] == 0) {
                                                    continue;
                                                }
                                    ?>
                                    <tr>
                                        <td><?= $inv_row['title'].' <strong>['.$inv_row['sem'].'|'.$exp[1].']</strong>'; ?></td>
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
                                            echo '<tr><td colspan="2">No Arrears From Last Semester</td></tr>';
                                        }
                                    ?>

                                 </thead>
                            </table>
                        </td>
                        <td width="25%" align="right" valign="top">
                            <!--Account Section here (next semester billing)-->
                            <table class="table table-bordered table-striped table-hover table-active account_tb" style="width: 100%; border-collapse:collapse;border: 1px solid #000; font-size: 10px" border="1">
                                 <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th style="text-align: right">AMOUNT</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2">NEXT SSEMESTER BILLS [Sem | Year]</th>
                                    </tr>
                                    <?php 
                                    //Invoices owe (next semester)
                                    $invoice_next_sem_total = 0;

                                    if($invoices_next_sem_row > 0) {
                                        foreach($invoices_next_sem as $next_row):
                                    
                                    $exp = explode('-',$next_row['year']);
                                    ?>
                                    <tr>
                                        <td><?= $next_row['title'].' <strong>['.$next_row['sem'].'|'.$exp[1].']</strong>'; ?></td>
                                        <td align="right"><?= numfmt_format_currency($fmt, $next_row['due'], $currency); ?></td>
                                    </tr>
                                    <?php 
                                        $invoice_next_sem_total = $invoice_next_sem_total + $next_row['due'];
                                        endforeach;

                                        } else {
                                            echo '<tr><td colspan="2">No Bills Found For Next Semester</td></tr>';
                                        }
                                    ?>
                                    <tr><th>SUB-TOTAL</th><td align="right"><strong><?= numfmt_format_currency($fmt, $invoice_next_sem_total, $currency); ?></strong></td></tr>

                                 </thead>
                            </table>

                             <!--Summation of the arrears and the bills for next semester-->
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
                                        <td><?= numfmt_format_currency($fmt, $invoice_next_sem_total, $currency); ?></td>
                                        <td><strong><?= numfmt_format_currency($fmt, $arrears_total + $invoice_next_sem_total, $currency); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row sign">
        <div class="col-lg-12 col-md-12">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;">Class Teacher's Signature:.....................................</td>
                    <td style="width: 50%; text-align: right;">........................................Signature and Stamp of Principal</td>
                </tr>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        
        
            //get the subjects and their respective total marks
            let subjects_array_<?php echo $student_id; ?> = <?php echo json_encode($subjects_array); ?>;
            let marks_array_<?php echo $student_id; ?> = <?php echo json_encode($marks_array); ?>;
            
            //Chart
            var ctx_<?php echo $student_id; ?> = document.getElementById('marks_chart_<?php echo $student_id; ?>').getContext('2d');
            var marks_chart_<?php echo $student_id; ?> = new Chart(ctx_<?php echo $student_id; ?>, {
                type: 'bar',
                data: {
                    labels: subjects_array_<?php echo $student_id; ?>,       
                    datasets: [{
                        label: 'Semester Report Chart',
                        data: marks_array_<?php echo $student_id; ?>,
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
        
    
</script>

</page>
    <?php endforeach; ?>

</div>
</div>
</div>
</div>
<?php

} else if(isset($term)) {
?>

<div class="container">
<div class="row" style="margin-top: 15px; position: fixed; z-index: 9999; background: white; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" id="print_div">
    <a href="javascript:void(0);" onClick="page_reload()" style="margin-right: 15px;">Print Bulk Report Sheet</a>
    <button id="toggleAccountBtn" onclick="toggleAccountSection()" style="padding: 5px 15px; cursor: pointer; background: #520452; color: white; border: none; border-radius: 3px;">
        <span id="toggleText">Hide Account/Billing Section</span>
    </button>
</div>
<div class="row">
<div class="col-lg-12">
<div id="print">

<?php

 $data_array2 =  array(
    'class_id' => $class_id, 
        'section_id' => $section_id, 
            'year' => $year,
                'mute' => '0',
                'status' => 'close', 
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
                'mute' => '0',
                'exam_id' => $exam_id,
                     'term' => $term
            );

    $this->db->select('student_id');
    $this->db->distinct();
    $this->db->where($data_array);
    $this->db->from('mark');
    $this->db->limit($num_r);
   // $this->db->join('mark', 'mark.exam_id ='. $exam_id);
    $students_marks = $this->db->get()->result_array();

    foreach($students_marks as $student_row):

            $student_id = $student_row['student_id'];

    $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
    $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
    $exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
    $system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
    $running_year       =   $year;
    $location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
    $address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;
    
    // Fetch term dates from terms table using year and term from exam
    $termRows = getArchivedTermStartsEndsDate($term, $year);
    if($termRows && $termRows->term_ending != '' && !empty($termRows->term_ending)) {
        $term_ending = $termRows->term_ending;
        $next_term_begins = $termRows->next_term_begins;
    } else {
        // Fallback to settings if term record not found
        $term_ending = $this->db->get_where('settings', array('type'=>'term_ending'))->row()->description;
        $next_term_begins = $this->db->get_where('settings', array('type'=>'next_term_begins'))->row()->description;
    }
    
    $running_term       =   $term;
    $no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', 'term' => $running_term, 'section_id' => $section_id, 'year' => $running_year))->num_rows();
    $teacher_remarks_row    =   $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->row();
    
    // Load remark helper for ID/Text conversion
    $this->load->helper('remark');
    
    $teacher_remarks_raw    =   $teacher_remarks_row ? $teacher_remarks_row->remarks : '';
    $teacher_remarks        =   get_class_teacher_remark($teacher_remarks_raw);
    
    $head_teacher_remarks_raw   =   $teacher_remarks_row && isset($teacher_remarks_row->head_teacher_remarks) ? $teacher_remarks_row->head_teacher_remarks : '';
    $head_teacher_remarks       =   get_head_teacher_remark($head_teacher_remarks_raw);
    
    // Auto head teacher remark if empty
    if (empty($head_teacher_remarks) || trim($head_teacher_remarks) == '') {
        if (!isset($Head_teacher_remarks_model_loaded)) {
            $this->load->model('Head_teacher_remarks_model');
            $Head_teacher_remarks_model_loaded = true;
        }
        
        $student_percentage = $teacher_remarks_row && isset($teacher_remarks_row->student_percentage) 
            ? round($teacher_remarks_row->student_percentage) 
            : 0;
        
        if ($student_percentage > 0) {
            $auto_head_remark = $this->Head_teacher_remarks_model->find_by_percentage($student_percentage);
            
            if ($auto_head_remark && !empty($auto_head_remark->remark_text)) {
                $head_teacher_remarks = $auto_head_remark->remark_text;
            }
        }
    }
    
    //incase the admin did not set correct sem ending or term ending, let's use current date
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

    $grading = $this->db->get('grade')->result_array();

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

    //feeding and classes owe - using daily_fee_wallet instead of deprecated feeding_fee table
    // Get the most recent wallet balance for this student
    $wallet_row = $this->db->get_where('daily_fee_wallet', array('student_id' => $student_id))->row();
    $previous_day_timestamp = $wallet_row ? $wallet_row->last_updated : 0;


    //Arrears
    //invoices owe
    $invoices_owe_query = $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)));
    $invoices_owe_row =  $invoices_owe_query->num_rows();

    $invoices_owe =  $invoices_owe_query->result_array();

    if($invoices_owe_row > 5):
        $this->db->select('title');
        $this->db->distinct();
        $this->db->from('invoice');
        $this->db->where('student_id', $student_id);
        $this->db->where('due !=', '0');
        $this->db->where('creation_timestamp <=', strtotime($term_ending));
        $invoices_owe = $this->db->get()->result_array();
    endif;
    

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
    $invoices_next_term_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->num_rows();
    $invoices_next_term =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'term' => $next_term, 'year' => $next_year))->result_array();

    //promotion
    $promoted_to = '';
    if($running_term == 3) {
        $explode_running_year = explode('-', $running_year);
        $year_part1 = $explode_running_year[0] + 1;
        $year_part2 = $explode_running_year[1] + 1;

        $promoted_to_year = $year_part1. '-'. $year_part2;
        $promoted_to_term = 1;

        $promoted_enrollment = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $promoted_to_year, 'term' => $promoted_to_term))->row();
        if($promoted_enrollment && isset($promoted_enrollment->class_id)){
            $promoted_to_class_id = $promoted_enrollment->class_id;
            $promoted_to_section_id = $promoted_enrollment->section_id;
            
            $promoted_class_row = $this->db->get_where('class', array('class_id' => $promoted_to_class_id))->row();
            if($promoted_class_row){
                $promoted_to_class = $promoted_class_row->name. ' '.$promoted_class_row->name_numeric;
                
                $promoted_section_row = $this->db->get_where('section', array('section_id' => $promoted_to_section_id))->row();
                if($promoted_section_row) {
                    $promoted_to_class .= ' ('.$promoted_section_row->name.')';
                }
            }else{
                $promoted_to_class = 'N/A';
            }
        }else{
            $promoted_to_class = 'N/A';
        }
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

        div .logo{
            position: absolute;
            margin-top: 52px;
            left: 10px;
        }

        div .photo_passport {
            position: absolute;
            margin-top: -135px;
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
            position: relative;
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

            #top_row{
                margin-top: -55px;
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

            page {
                page-break-after: always;
            }

            .sign {
                position: absolute;
                bottom: 0;
                left: 15px;
                right: 15px;
                width: calc(100% - 30px);
            }
        }
    </style>
<page size="A4">
    <center>  
        <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px; margin-top: 10px"><?php echo $system_name;?></h3>
    </center>
    <div class="row" id="top_row">
        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
           <img class="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br> 
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
            <img src="<?php echo $this->crud_model->get_image_url('student',$student_id, $gender);?>" class="img-circle photo_passport" width="120" height="120" />
        </div>                         
    </div> 
      <hr>

    <!--Details of the student here-->
    <div class="row">
        <div class="col-lg-12" id="student_details">

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
                                        <th><?=strtoupper($class_name). ' '. $class_name_numeric.' '.$sec_name; ?></th>
                                    </tr>

                                    <tr>
                                        <th>No. ON ROLL:</th>
                                        <th><?php echo $no_on_roll;?></th>
                                    </tr>

                                    <tr>
                                        <th>INTEREST:</th>
                                        <th><?php 
                                            $interest_row = $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->row();
                                            if ($interest_row && $interest_row->interest) {
                                                $this->load->helper('report_display');
                                                echo strtoupper(get_interest_display($interest_row->interest));
                                            }
                                        ?></th>
                                    </tr>

                                    <tr>
                                        <!--<th>POSITION:</th>
                                        <th><?php
                                            //$this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_term);?>     
                                        </th>-->
                                    </tr>
                                </thead>
                            </table>
                        </td> <!--left hand side ends-->

                        <td> <!--right hand side -->
                            <table style="width: 100%">
                                <thead align="right">
                                    <tr>
                                        <th>ACADEMIC YEAR:</th>
                                        <th><?php echo $running_year;?></th>
                                    </tr>

                                    <tr>
                                        <th>TERM:</th>
                                        <th><?php echo $running_term;?></th>
                                    </tr>

                                    <tr>
                                        <th>TERM ENDING:</th>
                                        <th><?php $date = date_create($term_ending); echo date_format($date, 'd-m-Y'); ?></th>
                                    </tr>

                                    <tr>
                                        <th>NEXT TERM BEGINS:</th>
                                        <th><?php $date = date_create($next_term_begins); echo date_format($date, 'd-m-Y'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </td>
                    </tr>
                </thead>
            </table>

        </div>
    </div> 

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

                <td style="text-align: center;">
                    <?php
                        $row4['mark_obtained'] = $obtained_mark_query->row()->mark_obtained;
                        
                        if($obtained_mark_query->num_rows() > 0){
                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                echo $grade['grade_point'];
                               // $total_grade_point += $grade['grade_point'];
                            }
                            
                            if($row4['mark_obtained'] == NULL) {
                                    echo "N/A";
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
                            
                            if($row4['mark_obtained'] == NULL) {
                                    echo "N/A";
                                }
                        }else{
                          echo "N/A";
                        }
                    ?>
                </td> -->
                <td style="text-align: center;">
                    <?php
                        $this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $student_id, $running_year, $running_term);
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

        } else if(str_word_count($row3['name'], 1)[0] == 'Ghanaian' || str_word_count($row3['name'], 1)[0] == 'GHANAIAN') {
            $subject_name = 'Ghanaian Lang.';

        } else if(str_word_count($row3['name'], 1)[0] == 'French' || str_word_count($row3['name'], 1)[0] == 'FRENCH') {
            $subject_name = 'French';

        } else if(str_word_count($row3['name'], 1)[0] == 'Language' || str_word_count($row3['name'], 1)[0] == 'LANGUAGE') {
            $subject_name = 'Lang & Lit';

        } else if(str_word_count($row3['name'], 1)[0] == 'Writing' || str_word_count($row3['name'], 1)[0] == 'WRITING') {
            $subject_name = 'Writing';
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
    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" style="position: relative; float: right; text-align: right;" id="promote">
        <strong>Promoted to:</strong> <b><?php echo $promoted_to_class; ?></b>
    </div>
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

                if($conduct['c'.$i] != '' || $conduct['c'.$i] != Null) {
                ?>
                    <td><input type="checkbox" id="ex" class="form-control" name="conducts[]" value="<?= $conduct['c'.$i];?>" checked></td>
                    <td><label for="ex" class="form-control-label"><?= get_conduct_display_value($conduct['c'.$i]);?></label></td>
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
                    <td><label class="form-control-label"><?= get_conduct_display_value($conduct['c'.$i]);?></label></td>
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
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; padding-right: 10px; vertical-align: top;">
                        Class Teacher's Remark: <em><strong><?php echo ucwords(strtolower($teacher_remarks));?></strong></em>
                    </td>
                    <td style="width: 50%; padding-left: 10px; vertical-align: top; text-align: right;">
                        Principal's Remark: <em><strong><?php echo ucwords(strtolower($head_teacher_remarks));?></strong></em>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!--Charts and Account Section-->
    <div class="row">
        <div>
            <table class="table table-bordered table-striped table-hover table-active chart_account_section" style="width:100%; border-collapse:collapse;" border="0" id="chart_account_term">
                <thead></thead>
                <tbody>
                    <tr>
                        <td width="15%">
                            <!--Chart Section here-->
                            <canvas id="marks_chart_<?= $student_id; ?>"></canvas>
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
                                        <th colspan="2">PREVIOUS ARREARS<?=$invoices_owe_row > 5 ? '' : ' [Term | Year]'; ?></th>
                                    </tr>
                                    <?php 
                                    //Invoices owe (arrears)
                                    $invoice_arrears_total = 0;

                                    if($invoices_owe_row == 0 && $feeding_owe_row == 0 && $classes_owe_row == 0 && $transport_owe_row == 0) {
                                        /*echo '<tr><td colspan="2">No Arrears Found!</td></tr>';*/
                                    }

                                    $rows_counter = 0;

                                    if($invoices_owe_row > 0):

                                        if($invoices_owe_row > 5):

                                            foreach($invoices_owe as $inv_row):

                                                //let's get the first occurrence of the next term bill
                                                $this->db->where('term', $next_term);
                                                $this->db->where('year', $next_year);
                                                $this->db->where('student_id', $student_id);
                                                $this->db->where('title', $inv_row['title']);
                                                $this->db->where('due >', '0');
                                                $invoice_id_query = $this->db->get('invoice');

                                                $invoice_id = $invoice_id_query->first_row()->invoice_id;


                                                $rows_counter++; //increment

                                                $this->db->select_sum('due');
                                                $this->db->where('student_id', $student_id);
                                                $this->db->where('title', $inv_row['title']);
                                                $this->db->where('due !=', '0');

                                                if($invoice_id_query->num_rows() < 1) {
                                                    $this->db->where('creation_timestamp <=', strtotime($term_ending));
                                                } else {
                                                    $this->db->where('invoice_id <', $invoice_id);
                                                }
                                                
                                                $total_sum = $this->db->get('invoice')->row()->due;

                                                if($total_sum == 0) {

                                                    continue;
                                                }
                                    ?>
                                    <tr>
                                        <td><?= $inv_row['title']; ?></td>
                                        <td align="right"><?= numfmt_format_currency($fmt, $total_sum, $currency); ?></td>
                                    </tr>
                                    <?php 
                                        $invoice_arrears_total = $invoice_arrears_total + $total_sum;
                                            
                                        endforeach; //arrears rows are more than 5

                                        else:
                                        
                                        foreach($invoices_owe as $inv_row):

                                            //show only if it is not next term and next years' bill
                                            if($inv_row['due'] == 0) {

                                                continue;
                                            }

                                            if($inv_row['term'] != $next_term || $inv_row['year'] != $next_year) {

                                                $rows_counter++; //increment

                                                $exp = explode('-',$inv_row['year']);
                                                ?>
                                                <tr>
                                                    <td><?= $inv_row['title'].' <strong>['.$inv_row['term'].'|'.$exp[1].']</strong>'; ?></td>
                                                    <td align="right"><?= numfmt_format_currency($fmt, $inv_row['due'], $currency); ?></td>
                                                </tr>
                                                <?php 
                                                    $invoice_arrears_total = $invoice_arrears_total + $inv_row['due'];
                                            }
                                        endforeach;
                                        endif;
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
                                        <th colspan="2">NEXT TERM BILLS [Term | Year]</th>
                                    </tr>
                                    <?php 
                                    //Invoices owe (next term)
                                    $invoice_next_term_total = 0;

                                    if($invoices_next_term_row > 0) {
                                        foreach($invoices_next_term as $next_row):
                                    
                                    $exp = explode('-',$next_row['year']);
                                    ?>
                                    <tr>
                                        <td><?= $next_row['title'].' <strong>['.$next_row['term'].'|'.$exp[1].']</strong>'; ?></td>
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

    <div class="row sign">
        <div class="col-lg-12 col-md-12">

            <table width="100%">
                <tbody>
                    <tr>
                        <td>
                            Class Teacher's Signature:......................................
                        </td>
                        <td >
                            Signature and Stamp of Principal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img src="<?php echo $this->crud_model->get_head_teacher_signature();?>" style="vertical-align: middle" width="100" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        
        
            //get the subjects and their respective total marks
            let subjects_array_<?php echo $student_id; ?> = <?php echo json_encode($subjects_array); ?>;
            let marks_array_<?php echo $student_id; ?> = <?php echo json_encode($marks_array); ?>;
            
            //Chart
            var ctx_<?php echo $student_id; ?> = document.getElementById('marks_chart_<?php echo $student_id; ?>').getContext('2d');
            var marks_chart_<?php echo $student_id; ?> = new Chart(ctx_<?php echo $student_id; ?>, {
                type: 'bar',
                data: {
                    labels: subjects_array_<?php echo $student_id; ?>,       
                    datasets: [{
                        label: 'Terminal Report Chart',
                        data: marks_array_<?php echo $student_id; ?>,
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
        
    
</script>

</page>
    <?php endforeach; ?>

</div>
</div>
</div>
</div>

<?php
            }
        ?>
    

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


    // Toggle Account/Billing Section with localStorage persistence (Button version)
    var showingAccount = true; // Track state
    
    $(document).ready(function() {
        // Restore toggle state from localStorage (default: true/show)
        var showAccount = localStorage.getItem('showAccountSection');
        if (showAccount === null) {
            showAccount = 'true';
        }
        
        showingAccount = (showAccount === 'true');
        
        // Apply initial state
        if (showingAccount) {
            $('.chart_account_section').show();
            $('#toggleText').text('Hide Account/Billing Section');
        } else {
            $('.chart_account_section').hide();
            $('#toggleText').text('Show Account/Billing Section');
        }
    });
    
    // Toggle function
    function toggleAccountSection() {
        showingAccount = !showingAccount;
        
        localStorage.setItem('showAccountSection', showingAccount.toString());
        
        if (showingAccount) {
            $('.chart_account_section').show();
            $('#toggleText').text('Hide Account/Billing Section');
        } else {
            $('.chart_account_section').hide();
            $('#toggleText').text('Show Account/Billing Section');
        }
    }

    function print_page() {
        print();
    }

    function page_reload() {
        print();
    }

    $(function() {
        setTimeout(() => {
            print_page();
        }, 1000);
    });
</script>

