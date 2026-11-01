<?php
$class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
$class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
$exam_name          =   $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
$system_name        =   $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;

// Use passed variables from controller (fetched from exam table), not settings
// $running_year and $running_term are already set by controller from exam record

$location           =   $this->db->get_where('settings' , array('type'=>'location'))->row()->description;
$address            =   $this->db->get_where('settings' , array('type'=>'address'))->row()->description;

// Fetch term dates from terms table using exam's year and term
$termRows = getArchivedTermStartsEndsDate($running_term, $running_year);
if($termRows && $termRows->term_ending != '' && !empty($termRows->term_ending)) {
    $term_ending = $termRows->term_ending;
    $next_term_begins = $termRows->next_term_begins;
} else {
    $term_ending = $this->db->get_where('settings', array('type'=>'term_ending'))->row()->description;
    $next_term_begins = $this->db->get_where('settings', array('type'=>'next_term_begins'))->row()->description;
}

//incase the admin did not set correct term ending, let's use current date
$current_date = strtotime(date('d-m-Y'));
if(strtotime($term_ending) < $current_date) {
    $term_ending = date('d-m-Y');
}

$no_on_roll         =   $this->db->get_where('enroll', array('class_id' => $class_id, 'term' => $running_term, 'mute' => '0', 'section_id' => $section_id, 'year' => $running_year))->num_rows();
$teacher_remarks            =   $this->db->get_where('aggregation', array('student_id' => $student_id, 'class_id' => $class_id, 'exam_id' => $exam_id, 'term' => $running_term, 'year' => $running_year))->row()->remarks;

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

$grading_e = $this->db->get('grade')->result_array();

$gender = $this->db->get_where('student', array('student_id' => $student_id))->row()->sex;

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
$this->db->where('can_delete !=', 'trash');
$invoices_owe_row =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)))->num_rows();

$this->db->where('can_delete !=', 'trash');
$invoices_owe =  $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => '0', 'creation_timestamp <=' => strtotime($term_ending)))->result_array();

/*//feeding fee owe
$feeding_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
$feeding_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;

//classes fee owe
$classes_owe_row =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->num_rows();
$classes_owe =  $this->db->get_where('feeding_fee', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'cdue !=' => '0'))->row()->cdue;

//transport fee owe
$transport_owe_row =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->num_rows();
$transport_owe =  $this->db->get_where('transport_fare', array('student_id' => $student_id, 'timestamp' => $previous_day_timestamp, 'due !=' => '0'))->row()->due;
//arrears end*/

$feeding_owe_row = 0;
$classes_owe_row = 0;
$transport_owe_row = 0;

//Next term bills

//invoices owe for next term
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

?>
<!doctype html>
<html>
    <head>

        <link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive_table.css');?>">

    </head>
    <body >  
        
        <?php include(APPPATH . 'views/backend/includes/loading_screen.php'); ?>
        
        <div id="main-content">
        <div class="container">
            <div class="row" style="padding-top: 15px;">
                <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
                       Print Report Sheet
                        <i class="glyphicon glyphicon-print"></i>
                </a>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div id="print">

                        <script src="<?php echo base_url('assets/js/jquery-3.3.1.min.js');?>"></script>
                        <style type="text/css">
                             #mark_print th, #grade_table th, #conducts th, .creche th, .info_st {
                                background-color: grey;
                                border: 2px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 2px solid red;
                             }

                             .info_st {
                                background-color: grey;
                                border: 1px solid #e2dddd;
                                padding: 3px;
                                color: white;
                                border-bottom: 2px solid #000000;
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

                            .account_tb th {
                                background-color: grey;
                                border: 1px solid #e2dddd;
                                padding: 5px;
                                color: white;
                                border-bottom: 1px solid red;
                                text-align: left;
                             }


                            div #photo_passport {
                                margin-top: -20px;
                                border-radius: 16% 6%;
                            }


                            @media Print{
                                page {
                                background: #ffffff;
                                display: block;
                                margin: 0 auto;
                                margin-bottom: 0.5cm;
                                box-shadow: 0;
                                page-break-after: always;
                                position: relative;

                                }

                                page[size="A5"] {
                                    width: 5.83in;
                                    height: 21cm;
                                }

                                body, page {
                                    margin: 0;
                                    box-shadow: 0;
                                }

                                div #logo{
                                    position: absolute;
                                    top: 82px;
                                    left: 10px;
                                }

                                div #photo_passport{
                                    position: absolute;
                                    top: 91px;
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

                                .sign {
                                    position: fixed;
                                    bottom: 12px;
                                }

                                #container {
                                    height: 740px;
                                }

                                #front_page_image_holder {
                                    height: 70px;
                                    margin-top: 20px;
                                }
                            }
                        </style>
                        <!--<page size="A4"> -->
            <div style="display: none">                        
                        <center>  
                            <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px;"></h3>
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
    </div>                     

      <?php
        $page_counter = 0;
        $rows_counter = 0;
       ?>

       <!-- FRONT PAGE -->
       <page size="A5" style="font-size: 14px">
            <div class="row" id="container" style="border: 15px inset #000; padding: 25px;">
                <div id="fornt_page_container" style="border: 4px dashed #000; padding: 15px; height: 700px;">
                    <center>
                        <div class="row">
                            <h1>Play Group Assessment</h1>
                            <h2>Progress Record Book</h2>
                            <h4>(Between 1 and 2 years)</h4>
                        </div>
                        <div class="row" id="front_page_image_holder" style="height: 350px; padding-top: 10px; padding-bottom: 10px;">
                            <img src="<?= base_url('uploads/creche/images/creche2.jpg'); ?>" alt="Kings Ridge International School" width="445px" height="340px">
                        </div>
                    </center>
                    <div class="row">
                        <div id="student_info" style="margin-left: 60px;">
                            <table class="table">
                                <thead></thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Name of the school</strong></td>
                                        <td><strong>:</strong></td>
                                        <td align="left"><strong><?php echo ucwords(strtolower($system_name));?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Name of pupil</strong></td>
                                        <td><strong>:</strong></td>
                                        <td align="left"><strong><?php echo ucwords(strtolower($this->db->get_where('student' , array('student_id' => $student_id))->row()->name));?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Class</strong></td>
                                        <td><strong>:</strong></td>
                                        <?php if($class_name == 'CRECHE') {
                                            $creche_name = ucwords(strtolower($class_name)).$sec_name;
                                        } else {
                                            $creche_name = ucwords(strtolower($class_name)). ' '. $class_name_numeric.$sec_name;
                                        } ?>
                                        <td align="left"><strong><?php echo $creche_name; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Gender</strong></td>
                                        <td><strong>:</strong></td>
                                        <td align="left"><strong><?php echo ucwords(strtolower($this->db->get_where('student' , array('student_id' => $student_id))->row()->sex));?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Age</strong></td>
                                        <td><strong>:</strong></td>
                                        <?php 
                                            $year = '';
                                            $month = '';
                                            $day = '';

                                            $date_array = array();
                                            
                                            $dob =  date_format(date_create(str_replace(',', '',$this->db->get_where('student' , array('student_id' => $student_id))->row()->birthday)), 'Y-m-d');
                                            $today = date('Y-m-d');

                                            $diff = date_diff(date_create($dob), date_create($today));
                                           // $age = $diff->format('%y years, %m months and %d days old');
                                           $age2 = $diff->format('%y, %m, %d');
                                           $age2 = explode(',', $age2);
                                           
                                           $pos = 0;
                                            for($i = 0; $i < count($age2); $i++) {
                                                if(is_numeric($age2[$i])) {
                                                    $date_array[$pos] = $age2[$i];
                                                    $pos++;
                                                }
                                            }
                                            
                                            //Adjustment for year or years
                                           if($date_array[0] > 1) {
                                               $year = 'years';
                                           } else {
                                               $year = 'year';
                                           }
                                           
                                           //Adjustment for month or months
                                           if($date_array[1] > 1) {
                                               $month = 'months';
                                           } else {
                                               $month = 'month';
                                           }
                                           
                                           //Adjustment for day or days
                                           if($date_array[2] > 1) {
                                               $day = 'days';
                                           } else {
                                               $day = 'day';
                                           }
                                           
                                           $age = $date_array[0].$year.', '.$date_array[1].$month. ' and '.$date_array[2].$day. ' old';
                                            
                                        ?>
                                        <td align="left"><strong><?= $age; ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sign" style="position: absolute; margin-left: 120px; margin-bottom: -38px; z-index: 999; color: white">
                <small><em><strong>Software: Developed & Designed By Lightworld Technologies Ltd Team : +233243618186</strong></em></small>
            </div>
       </page> <!-- FRONT PAGE ENDS -->

       <!-- ASSESSMENT FORM -->
       <page size="A5" style="font-size: 14px">

        <!--Grading system for creche -->
        <div class="row">
              <table class="table table-striped table-hover table-active creche" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
                <caption style="text-align: left; font-size: 18px;">Grading System</caption>
                <thead>
                    <tr>
                        <th style="text-align: left">ABBREVIATION</th>
                        <th style="text-align: left">MEANING</th>
                    </tr>
                </thead>
                <tbody>
                  <?php 
                    $grading_sys = $this->db->get('grade_creche')->result_array();
                    foreach($grading_sys as $grade): 
                  ?>
                  <tr>
                    <td style="font-weight: bold"><?= $grade['abbrev']; ?></td>
                    <td style="font-weight: bold"><?= $grade['full_name']; ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
          </div><br>

        <?php 
      $subj_category = $this->db->get('subject_category_creche')->result_array();
      $subj_category_rows = $this->db->get('subject_category_creche')->num_rows();

      foreach($subj_category as $cat): ?>

      <div class="row">
          <table class="table table-bordered table-striped table-hover table-active creche" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
            <thead>   
              <tr>
                <th style="text-align: left"><?= $cat['name']; ?></th>
                <th width="30" style="text-align: left"><?= 'GRADING'; ?></th>
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
      </div><br>

      <?php 
      $rows_counter++;
      $page_counter++; 

      if($page_counter == 2) {

       /** echo '<div class="sign" style="margin-left: 185px;">
                <small><em><strong>Software: Developed & Designed By Lightworld Technologies Ltd Team : +233243618186</strong></em></small>
            </div>'; **/
            
        echo '</page>';
        $page_counter = 0;

        if($rows_counter != $subj_category_rows) {
            echo '<page size="A5" style="font-size: 14px;">';
            echo '<!--Grading system for creche -->
        <div class="row">
              <table class="table table-striped table-hover table-active creche" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
              <caption style="text-align: left; font-size: 18px;">Grading System</caption>
                <thead>
                    <tr>
                        <th style="text-align: left">ABBREVIATION</th>
                        <th style="text-align: left">MEANING</th>
                    </tr></thead><tbody>';

                  
                    $grading_sys = $this->db->get('grade_creche')->result_array();
                    foreach($grading_sys as $grade): 


                  echo '<tr>
                    <td style="font-weight: bold">'. $grade['abbrev'] .'</td>
                    <td style="font-weight: bold">'. $grade['full_name'] .'</td>
                  </tr>';

                 endforeach;

                echo '</tbody>
              </table>
        </div><br>';
        }
        
      }

      ?>

     
  <?php endforeach; ?> <!-- ASSESSMENT FORM ENDS-->

  <?php 
    if($this->db->get_where('subject_creche' , array('class_id' => $class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
  ?>

            <page size="A5" style="font-size: 14px;"> <!--DISPLAY EXAMS WITH MARKS, IF ANY -->
                    <!--Grading scale-->
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="table table-responsive">
                                <table class="table table-bordered table-striped" id="grade_table" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
                                    <caption><u><h4>Written Examination (Grading System & Marks)</h4></u></caption>
                                    <thead>
                                        <tr>
                                        <?php $grades = 1; 
                                        foreach($grading_e as $gradepoint_row): ?>
                                        
                                            <th style="text-align: center; font-weight: bold;"><?php echo strtoupper($gradepoint_row['grade_point']); ?></th>
                                        
                                    <?php endforeach; ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php $grades = 1; foreach($grading_e as $grademark_row): ?>
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
                                   <!--  <td style="text-align: center;"><?php echo $i;?></td> -->
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
                                                                                        'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
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
                                                                                        'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
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
                                                                                        'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
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
                                            $this->crud_model->get_total_score($row4['exam_id'] , $class_id , $row3['subject_id'], $row4['student_id'], $running_year, $running_term);
                                        ?>
                                    </td>
                                </tr>
                            <?php 
                            $i++;
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
                </page> <!--EXAMS WITH MARKS ENDS -->

            <?php } ?>

                <!-- OBSERVATION GUIDE -->  
                <page size="A5" style="font-size: 14px">
                    <div class="row">
                        <div id="observation">
                            <table class="table" style="width:100%; border-collapse:collapse; margin-top: 10px;">
                                <caption><u><h4>OBSERVATION GUIDE</h4></u></caption>
                                <thead></thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Class Teacher</strong></td>
                                        <td><strong>:</strong></td>
                                        <?php 
                                            $teacher_id = $this->db->get_where('class', array('class_id' => $class_id, 'name' => $class_name, 'name_numeric' => $class_name_numeric))->row()->teacher_id;
                                            $teacher_name = $this->db->get_where('teacher', array('teacher_id' => $teacher_id))->row()->name;
                                            if($teacher_name != '' || $teacher_name != null) {
                                                $teacher_name = $teacher_name;
                                            } else {
                                                $teacher_name = 'Not Assigned';
                                            }
                                        ?>
                                        <td><strong><?= $teacher_name; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong><?= date('l F d, Y'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Time</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong><?= date('H:i:s'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Location</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong>Classroom</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Classroom or Setting</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong>..............................</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Class Teacher's General Remarks:</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong><strong><?php echo ucwords(strtolower($teacher_remarks));?></strong></strong></td>
                                    </tr>
                                </tbody>
                            </table><br>

                            <div class="row">
                                <u><h3 style="text-align: center;">Conducts, Observations and Outstanding Behaviours of <?php echo ucwords(strtolower(($this->db->get_where('student' , array('student_id' => $student_id))->row()->name)));?></h3></u>
                            </div>

                            <?php //STUDENT CONDUCTS EVALUATION STARTS

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
                                        <tbody>
                                            <tr>

                                            <?php
                                
                                for($i = 1; $i <= 15; $i++){

                                    if($conduct['c'.$i] != '' || $conduct['c'.$i] != Null) {
                                    ?>
                                        <td><input type="checkbox" id="ex" class="form-control" name="conducts[]" value="<?= $conduct['c'.$i];?>" checked></td>
                                        <td><label for="ex" class="form-control-label"><?= $conduct['c'.$i];?></label></td>
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
                       

                        <?php
                            }
                        } else {
                            echo 'Not Available';
                        }

                        ?>  
                        </div>
                    </div> <!--Conducts Ends-->

                <!-- SOME SPACE-->
                <div style="height: 250px;"></div><hr>
                <!-- SIGNATURES-->
                    <div class="row" style="position: relative; margin-left: 120px; margin-bottom: 5px;">
                        <div id="signature">
                            <table class="table" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;">
                                <thead></thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Class Teacher's Signature</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong>...........................................................</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong><?= date('l F d, Y'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Head Teacher's Signature</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong>...........................................................</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date</strong></td>
                                        <td><strong>:</strong></td>
                                        <td><strong><?= date('l F d, Y'); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </page>
                <!--signatures of the teacher and principal-->
                 
         <page size="A5" style="font-size: 14px"> <!--Attendance, Term info and physical development of student-->
            <hr>
            <hr>
            <div class="row">
                <table class="table" style="width:100%; border-collapse:collapse; margin-top: 30px;">
                    <thead></thead>
                    <tbody>
                        <tr>
                            <td class="info_st"><strong>Name of pupil</strong></td>
                            <td><strong>:</strong></td>
                            <td align="left" colspan="2"><strong><?php echo ucwords(strtolower($this->db->get_where('student' , array('student_id' => $student_id))->row()->name));?></strong></td>
                            <td><strong></strong></td>
                        </tr>
                        <tr>
                            <td class="info_st"><strong>Class</strong></td>
                            <td><strong>:</strong></td>
                            <?php if($class_name == 'CRECHE') {
                                $creche_name = ucwords(strtolower($class_name)).$sec_name;
                            } else {
                                $creche_name = ucwords(strtolower($class_name)). ' '. $class_name_numeric.$sec_name;
                            } ?>
                            <td align="left" colspan="2"><strong><?php echo $creche_name; ?></strong></td>
                            <td><strong></strong></td>
                        </tr>
                        <tr>
                            <td class="info_st"><strong>Gender</strong></td>
                            <td><strong>:</strong></td>
                            <td align="left"><strong><?php echo ucwords(strtolower($this->db->get_where('student' , array('student_id' => $student_id))->row()->sex));?></strong></td>
                            <td class="info_st"><strong>Age</strong></td>
                            <td><strong>:</strong></td>
                            <?php 
                                $dob =  date_format(date_create(str_replace(',', '',$this->db->get_where('student' , array('student_id' => $student_id))->row()->birthday)), 'Y-m-d');
                                $today = date('Y-m-d');

                                $diff = date_diff(date_create($dob), date_create($today));
                                
                                //Let's make sure the singular and plural rules are followed accordingly. E.g 1year, 3years etc.
                                           $year = '';
                                           $month = '';
                                           $day = '';

                                           $date_array = array();
                                            
                                           $age2 = $diff->format('%y, %m, %d');
                                           $age2 = explode(',', $age2);
                                           
                                           $pos = 0;
                                            for($i = 0; $i < count($age2); $i++) {
                                                if(is_numeric($age2[$i])) {
                                                    $date_array[$pos] = $age2[$i];
                                                    $pos++;
                                                }
                                            }
                                            
                                            //Adjustment for year or years
                                           if($date_array[0] > 1) {
                                               $year = 'years';
                                           } else {
                                               $year = 'year';
                                           }
                                           
                                           //Adjustment for month or months
                                           if($date_array[1] > 1) {
                                               $month = 'months';
                                           } else {
                                               $month = 'month';
                                           }
                                           
                                           //Adjustment for day or days
                                           if($date_array[2] > 1) {
                                               $day = 'days';
                                           } else {
                                               $day = 'day';
                                           }
                                           
                                           $age = $date_array[0].$year.', '.$date_array[1].$month. ' old';
                                
                            ?>
                            <td align="left"><strong><?= $age; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="info_st"><strong>Academic Year</strong></td>
                            <td><strong>:</strong></td>
                            <td><strong><?php echo explode('-', $running_year)[1];?></strong></td>
                            <td class="info_st"><strong>Trimester</strong></td>
                            <td><strong>:</strong></td>
                            <td><strong><?php echo $running_term;?></strong></td>

                        </tr>
                        <tr>
                            <td class="info_st"><strong>No. On Rolls</strong></td>
                            <td><strong>:</strong></td>
                            <td><strong><?php echo $no_on_roll;?></strong></td>
                            <td class="info_st"><strong>Position</strong></td>
                            <td><strong>:</strong></td>
                            <td><strong><?php /**
                             $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_term) ? $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $student_id, $running_year, $running_term) : 'N/A'; **/ ?>Not Available</strong></td>

                        </tr>
                        <tr>
                            <td class="info_st"><strong>Trimester Ending</strong></td>
                            <td><strong>:</strong></td>
                            <td><strong><?php $date = date_create($term_ending); echo date_format($date, 'F d, Y'); ?></strong></td>
                            <td class="info_st"><strong>Next Trimester Begins</strong></td>
                            <td><strong>:</strong></td>
                            <td><strong><?php $date = date_create($next_term_begins); echo date_format($date, 'F d, Y'); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="info_st"><strong>Promoted to</strong></td>
                            <td><strong>:</strong></td>
                            <td colspan="2"><strong><?php echo $promoted_to_class; ?></strong></td>
                            <td><strong></strong></td>
                            
                        </tr>
                        <tr>
                            <td class="info_st"><strong>Teacher's Name</strong></td>
                            <td><strong>:</strong></td>
                            <?php 
                                $teacher_id = $this->db->get_where('class', array('class_id' => $class_id, 'name' => $class_name, 'name_numeric' => $class_name_numeric))->row()->teacher_id;
                                $teacher_name = $this->db->get_where('teacher', array('teacher_id' => $teacher_id))->row()->name;
                                if($teacher_name != '' || $teacher_name != null) {
                                    $teacher_name = $teacher_name;
                                } else {
                                    $teacher_name = 'Not Assigned';
                                }
                            ?>
                            <td colspan="2"><strong><?= $teacher_name; ?></strong></td>
                            <td><strong></strong></td>                                        
                       </tr>
                    </tbody>
                </table>
            </div>
            <hr>

            <!-- Attendance-->
             <div class="row" style="margin-top: 50px; margin-bottom: 50px">
                <table class="table table-striped table-hover table-active creche" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
                    <caption><u><h3>Record of Attendance</h3></u></caption>
                    <thead>
                        <tr>
                            <?php
                                if($running_term == 1) {
                                    $trimester = $running_term.'st';
                                } else if($running_term == 2) {
                                    $trimester = $running_term.'nd';
                                } else if($running_term == 3) {
                                    $trimester = $running_term.'rd';
                                }

                            ?>
                            <th style="text-align: center;" colspan="2"><strong><?= $trimester; ?> Trimester</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Number of Days Opened</strong></td>
                            <td><strong><?php echo $att_total; ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Number of Days Present</strong></td>
                            <td><strong><?php echo $att_present;?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Number of Days Absent</strong></td>
                            <td><strong><?php echo $att_total - $att_present; ?></strong></td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <hr>
            <!-- Physical Development-->
             <div class="row" style="margin-top: 30px">
                <table class="table table-striped table-hover table-active creche" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
                    <caption><u><h3>Pupil's Physical Development Measurement Table</h3></u></caption>
                    <thead>
                    </thead>
                    <tbody>
                        <tr>
                            <td width="120"><strong>Date</strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Height</strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Weight</strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                            <td><strong></strong></td>
                        </tr>

                    </tbody>
                </table>
            </div>

            </page> <!-- ATTENDANCE AND OTHER RECORDS END --> 

            <page size="A5" style="font-size: 14px"> <!--Account-->
            <hr>
            <hr>

            <!--ACCOUNTS SECTION-BILLING-->
            <div class="row" style="margin-top: 30px">
                <table style="width: 100%">

                    <caption><u><h3>ACCOUNTS AND BILLING</h3></u></caption>

                    <tbody>
                        <tr>
                            
                            <td width="50%" align="right" valign="top" id="arrears_tb">
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
                                    </thead>
                                    <tbody>
                                        <?php 
                                        //Invoices owe (arrears)
                                        $invoice_arrears_total = 0;

                                        $rows_counter = 0;

                                        if($invoices_owe_row > 0):
                                            foreach($invoices_owe as $inv_row):



                                                //show only if it is not next term and next years' bill
                                                if($inv_row['term'] != $next_term || $inv_row['year'] != $next_year) {

                                        

                                                    $rows_counter++; //increment

                                                    $exp = explode('-',$inv_row['year']);
                                        ?>
                                        <tr>
                                            <td><?= $inv_row['title'].' <strong>[Term|Year: '.$inv_row['term'].'|'.$exp[1].']</strong>'; ?></td>
                                            <td align="right"><?= numfmt_format_currency($fmt, $inv_row['due'], $currency); ?></td>
                                        </tr>
                                        <?php 
                                            $invoice_arrears_total = $invoice_arrears_total + $inv_row['due'];
                                                } //end of 'if' test to see if the selected term and year is not the same as the next term and year respectively
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

                                     </tbody>
                                </table>
                            </td>
                            <td width="50%" align="right" valign="top">
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
                                    </thead>
                                    <tbody>
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

                                     </tbody>
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
            </div> <!--end of account section div-->
            
       </page> <!-- ACCOUNTS END -->     
       </div>
   </div>
</div>
</div>
</div>



        </div><!-- End main-content -->
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
        PrintElem(elem);
        Popup(data);

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
        var mywindow = window.open('', 'REPORT SHEET', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-size: 14px" >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }
</script>

