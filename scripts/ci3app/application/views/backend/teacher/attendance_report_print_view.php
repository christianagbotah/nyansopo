<?php
$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
$section_name = $this->db->get_where('section', array('section_id' => $section_id))->row()->name;
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;

if ($month == 1) {
    $m = 'January';
} else if ($month == 2) {
    $m = 'February';
} else if ($month == 3) {
    $m = 'March';
} else if ($month == 4) {
    $m = 'April';
} else if ($month == 5) {
    $m = 'May';
} else if ($month == 6) {
    $m = 'June';
} else if ($month == 7) {
    $m = 'July';
} else if ($month == 8) {
    $m = 'August';
} else if ($month == 9) {
    $m = 'Sepetember';
} else if ($month == 10) {
    $m = 'October';
} else if ($month == 11) {
    $m = 'November';
} else if ($month == 12) {
    $m = 'December';
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

    </head>
    <body style="background-color: #fcfcfd !important;">

        <div class="container">
            <div class="row" style="padding-top: 15px;">
                <a onClick="PrintElem('#print')" class="btn btn-info btn-icon icon-left hidden-print pull-right">
                       Print Attendance Report Sheet
                        <i class="glyphicon glyphicon-print"></i>
                </a>
            </div>

            <div id="print">
                <script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js'); ?>"></script>
                <style type="text/css">
                    td {
                        padding: 5px;
                    }

                     #term{
                            background-color: black;
                            padding: 5px 15px 5px 15px;
                            border-radius: 9px;
                            -webkit-border-radius: 9px;
                            -moz-border-radius: 9px;
                            -o-border-radius: 9px;
                            font-size: 24px;
                            letter-spacing: 5px;
                            color: #ffffff;
                            font-weight: bold;

                            }


                            @media Print{
                                div #logo{
                                    position: absolute;
                                    top: 15px;
                                }

                                #top_row{
                                    margin-top: 0px;
                                }

                            }
                </style>

                
                <div class="row" id="top_row">
                    <div class="col-md-12">
                        <center>
                            <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px;"><?php echo $system_name; ?></h3>
                        </center>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="col-lg-3 col-md-3 col-sm-3">
                           <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br>
                        </div>
                        
                        <div class="col-lg-6 col-md-6 col-sm-6">

                            <center>
                                <span align="center" id="term"><?php echo get_phrase('attendance_sheet'); ?></span>
                                <h3 align="center"><?php echo $class_name . ' ' . $class_name_numeric; ?><br></h3>
                                <b align='center' style="font-size: 25px;"><?php echo $class_name == 'JHSS' ? get_phrase('semester') . ' ' . $sem : get_phrase('term') . ' ' . $term; ?> |</b> <b align='center' style="font-size: 25px;"><?php echo $m . ', ' . $sessional_year; ?></b> <br>

                            </center>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3"></div>
                    </div>

                </div>
                <hr>
                <div class="col-md-12">
                    <table class="table table-bordered" id="listing" style="width:100%;">
                        <tbody>
                            <tr>
                                <?php
                                    for($a = 1; $a <= 5; $a++) {

                                        ?>
                                        <td align="center" valign="middle"><strong><?=getAttendanceStatusCode($a); ?> = <?=getAttendanceStatusPhrase($a); ?></strong></td>
                                        <?php
                                    }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <hr>

                <table class="table table-bordered table-responsive table-striped table-hover table-active"  border="1" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;">
                    <thead>
                    <tr>
                    <td style="text-align: center;">
                    <?php echo get_phrase('students'); ?> <i class="entypo-down-thin"></i> | <?php echo get_phrase('date'); ?> <i class="entypo-right-thin"></i>
                    </td>
                    <?php

                    $days = cal_days_in_month(CAL_GREGORIAN, $month, explode('-', $sessional_year)[1]);
                    
                    if($term == 1) {
                        $days = cal_days_in_month(CAL_GREGORIAN, $month, explode('-', $sessional_year)[0]);
                    }
                    for ($i = 1; $i <= $days; $i++) {
                    ?>
                                <td style="text-align: center;"><?php echo $i; ?></td>
                        <?php }?>

                        </tr>
                    </thead>

                    <tbody>
                                <?php
                        $data = array();

                        $students = $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', 'year' => $sessional_year, 'term' => $term, 'section_id' => $section_id))->result_array();

                        foreach ($students as $row):
                        ?>
                            <tr>

                                <td>
                                <?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>
                                </td>
                                <?php

                        $status = '-';
                        for ($i = 1; $i <= $days; $i++) {
                            $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[1]);
                            if($term == 1) {
                                $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[0]);
                            }
                        
                        //$this->db->group_by('timestamp');
                        $attendance = $this->db->get_where('attendance', array('section_id' => $section_id, 'class_id' => $class_id, 'year' => $sessional_year, 'term' => $term, 'timestamp' => $timestamp, 'student_id' => $row['student_id']))->result_array();

                        foreach ($attendance as $row1):
                        $month_dummy = date('d', $row1['timestamp']);
                        
                        if ($i == $month_dummy) {
                         

                            $status = getAttendanceStatusCode($row1['status']);
                        }

                            endforeach;
                            ?>
                        <td style="text-align: center;">
                           <?php 
                            echo '<strong>'.$status.'</strong>';
                            $status = '-';
                            ?>
                        </td>

                        <?php }?>
                        <?php endforeach;?>

                            </tr>

                        <?php ?>

                    </tbody>
                </table>    

            </div>
        </div>

        <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
        <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
    </body>
 <html>



<script type="text/javascript">

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