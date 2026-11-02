<?php
	$class_name		= $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
    $class_numeric     = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
	$section_name  		= $this->db->get_where('section' , array('section_id' => $section_id))->row()->name;
	$system_name        =	$this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
	$running_year       =	$this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
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
    <body>
<div class=" row col-md-12" style="margin-top: 20px;">
    <a onClick="PrintElem('#print')" id="print_btn" class="btn btn-default btn-icon icon-left hidden-print pull-right">
        Print Attendance
        <i class="entypo-print"></i>
    </a>

</div><br><br>

<div id="print">
	<script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js'); ?>"></script>
	<style type="text/css">
		td {
			padding: 5px;
		}
	</style>

	<center>
		<img src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br>
		<h3 style="font-weight: 100;"><?php echo $system_name;?></h3>
        <strong><?php echo strtoupper(get_phrase('attendance_sheet_for:')).' '. $this->db->get_where('student', array('student_id' => $student_id))->row()->name;?></strong><br>
		<?php echo $class_name.' '.$class_numeric;?> || <?php echo get_phrase('section').' '.$section_name;?><br>
        <?php echo $m . ', ' . explode('-', $sessional_year)[1]; ?>

	</center>

          <table border="1" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;">
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
                                ?>
                        <tr>
                            <td style="text-align: center;">
                            <?php echo $this->db->get_where('student', array('student_id' => $student_id))->row()->name; ?>
                            </td>
                            <?php
                            $status = 0;
                            for ($i = 1; $i <= $days; $i++) {
                                $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[1]);
                                //$this->db->group_by('timestamp');
                                $attendance = $this->db->get_where('attendance', array('section_id' => $section_id, 'class_id' => $class_id, 'year' => $sessional_year, 'timestamp' => $timestamp, 'student_id' => $student_id))->result_array();


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
            <?php } else { ?>
                                    <div>N/A</div>
            <?php }

            $status=0; ?>
                                </td>

        <?php } ?>

                    </tr>

    <?php ?>

                </tbody>
            </table>
</div>

<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>



<script type="text/javascript">
 // print invoice function
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
        var mywindow = window.open('ATTENDANCE', 'my div', 'height=400,width=600');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body >');
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
