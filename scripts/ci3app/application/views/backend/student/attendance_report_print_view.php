<?php 
  $class_name           = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
  $class_name_numeric     = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
  $section_name         = $this->db->get_where('section' , array('section_id' => $section_id))->row()->name;
  $system_name            = $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
  $running_year           = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
  $running_term           =   $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
        if($month == 1) $m = 'January';
        else if($month == 2) $m='February';
        else if($month == 3) $m='March';
        else if($month == 4) $m='April';
        else if($month == 5) $m='May';
        else if($month == 6) $m='June';
        else if($month == 7) $m='July';
        else if($month == 8) $m='August';
        else if($month == 9) $m='Sepetember';
        else if($month == 10) $m='October';
        else if($month == 11) $m='November';
        else if($month == 12) $m='December';
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
              <script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js');?>"></script>
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
                                    top: 65px;
                                }

                                #top_row{
                                    margin-top: -40px;
                                }

                            }
              </style>

                <center>  
                    <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px;"><?php echo $system_name;?></h3>
                </center>
                <div class="row" id="top_row">
                    <div class="col-lg-3 col-md-3 col-sm-3">
                       <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br> 
                    </div>
                     <center>
                        <br>
                    <div class="col-lg-6 col-md-6 col-sm-6">
                       
                        <span align="center" id="term"><?php echo get_phrase('attendance_sheet');?></span>
                        <h3 align="center"><?php echo $class_name.' '.$class_name_numeric;?><br></h3>
                        <b align='center' style="font-size: 25px;"><?php echo get_phrase('term').' '.$term;?> |</b> <b align='center' style="font-size: 25px;"><?php echo $m . ', ' . explode('-', $sessional_year)[1]; ?></b> <br>
                        
                    </div>
                    </center>
                    <div class="col-lg-3 col-md-3 col-sm-3"></div>
                    
                </div> 
                <hr>
                    
                      <table class="table table-bordered table-responsive table-striped table-hover table-active" border="1" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <td style="text-align: center;">
                <?php echo get_phrase('students'); ?> <i class="entypo-down-thin"></i> | <?php echo get_phrase('date'); ?> <i class="entypo-right-thin"></i>
                                    </td>
                <?php
                $year = explode('-', $running_year);
                $days = cal_days_in_month(CAL_GREGORIAN, $month, explode('-', $sessional_year)[1]);
                for ($i = 1; $i <= $days; $i++) {
                    ?>
                                        <td style="text-align: center;"><?php echo $i; ?></td>
                                <?php } ?>

                                </tr>
                            </thead>

                <tbody>
                            <?php
                            $data = array();

                            $students = $this->db->get_where('enroll', array('student_id' => $student_id, 'class_id' => $class_id, 'year' => $running_year, 'section_id' => $section_id))->result_array();

                            foreach ($students as $row):
                                ?>
                        <tr>
                            <td style="text-align: center;">
                            <?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>
                            </td>
                            <?php
                            $status = 0;
                            for ($i = 1; $i <= $days; $i++) {
                                $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[1]);
                                //$this->db->group_by('timestamp');
                                $attendance = $this->db->get_where('attendance', array('section_id' => $section_id, 'class_id' => $class_id, 'year' => $running_year, 'timestamp' => $timestamp, 'student_id' => $row['student_id']))->result_array();


                                foreach ($attendance as $row1):
                                    $month_dummy = date('m', $row1['timestamp']);
                                    if ($i == $month_dummy)
                                        ;
                                    $status = $row1['status'];
                                endforeach;
                                ?>
                                <td style="text-align: center;" data-class="">
            <?php if ($status == 1) { ?>
                                    <div style="color: #00a651">P</div>
                            <?php } else if ($status == 2) { ?>
                                    <div style="color: #ff3030">A</div>
            <?php }$status=0; ?>
                                </td>

        <?php } ?>
    <?php endforeach; ?>

                    </tr>

    <?php ?>

                </tbody>
            </table>
</div>


<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
        <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
    </body>
 <html>



<script type="text/javascript">

  jQuery(document).ready(function($)
  {
    var elem = $('#print');
    PrintElem(elem);
    Popup(data);

  });

    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data) 
    {
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title></title>');
        //mywindow.document.write('<link rel="stylesheet" href="assets/css/print.css" type="text/css" />');
        mywindow.document.write('</head><body >');
        //mywindow.document.write('<style>.print{border : 1px;}</style>');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10

        mywindow.print();
        mywindow.close();

        return true;
    }
</script>