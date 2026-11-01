<hr />
<?php echo form_open(base_url() . 'index.php/student/attendance_report_selector/'.$student_id); ?>
<div class="row">

    <div class="col-md-offset-2 col-md-2">
         <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('month'); ?></label>
            <select name="month" class="form-control select2">
                <?php
                for ($i = 1; $i <= 12; $i++):
                    if ($i == 1)
                        $m = 'january';
                    else if ($i == 2)
                        $m = 'february';
                    else if ($i == 3)
                        $m = 'march';
                    else if ($i == 4)
                        $m = 'april';
                    else if ($i == 5)
                        $m = 'may';
                    else if ($i == 6)
                        $m = 'june';
                    else if ($i == 7)
                        $m = 'july';
                    else if ($i == 8)
             <hr />
<?php echo form_open(base_url() . 'index.php/student/attendance_report_selector/'.$student_id); ?>
<div class="row">

    <div class="col-md-offset-2 col-md-2">
         <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('month'); ?></label>
            <select name="month" class="form-control select2">
                <?php
                for ($i = 1; $i <= 12; $i++):
                    if ($i == 1)
                        $m = 'january';
                    else if ($i == 2)
                        $m = 'february';
                    else if ($i == 3)
                        $m = 'march';
                    else if ($i == 4)
                        $m = 'april';
                    else if ($i == 5)
                        $m = 'may';
                    else if ($i == 6)
                        $m = 'june';
                    else if ($i == 7)
                        $m = 'july';
                    else if ($i == 8)
                        $m = 'august';
                    else if ($i == 9)
                        $m = 'september';
                    else if ($i == 10)
                        $m = 'october';
                    else if ($i == 11)
                        $m = 'november';
                    else if ($i == 12)
                        $m = 'december';
                    ?>
                    <option value="<?php echo $i; ?>"
                          <?php if($month == $i) echo 'selected'; ?>  >
                                <?php echo get_phrase($m); ?>
                    </option>
                    <?php
                endfor;
                ?>
            </select>
         </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('sessional_year'); ?></label>
            <select class="form-control select2" name="sessional_year">
                <?php
                $sessional_year_options = explode('-', $running_year); ?>
                <option value="<?php echo $sessional_year_options[0]; ?>"><?php echo $sessional_year_options[0]; ?></option>
                <option value="<?php echo $sessional_year_options[1]; ?>"><?php echo $sessional_year_options[1]; ?></option>
            </select>
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('term'); ?></label>
            <select class="form-control select2" name="term">
                <?php
                    for($j = 1; $j <= 3; $j++){
                        ?>
                        <option value="<?php echo $j; ?>" <?php if($term == $j) echo 'selected';?>><?php echo $j;?></option>
                        <?php
                    }
                ?>
            </select>
        </div>
    </div>

    <input type="hidden" name="operation" value="selection">
    <input type="hidden" name="year" value="<?php echo $running_year;?>">

	<div class="col-md-2" style="margin-top: 20px;">
		<button type="submit" class="btn btn-info"><?php echo get_phrase('show_report');?></button>
	</div>
</div>

<?php echo form_close(); ?>


<!-- Attendance Table starts from here -->
<?php 
?>
<?php if ($class_id != '' && $section_id != '' && $month != '' && $sessional_year != '' && $student_id != '' && $term != ''): ?>

    <br>
    <div class="row">
        <div class="col-md-4"></div>
        <div class="col-md-4" style="text-align: center;">
            <div class="tile-stats tile-gray">
                <div class="icon"><i class="entypo-docs"></i></div>
                <h3 style="color: #696969;">
                    <?php
                    $section_name = $this->db->get_where('section', array('section_id' => $section_id))->row()->name;
                    $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                    $class_name_numric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
                    if ($month == 1)
                        $m = 'January';
                    else if ($month == 2)
                        $m = 'February';
                    else if ($month == 3)
                        $m = 'March';
                    else if ($month == 4)
                        $m = 'April';
                    else if ($month == 5)
                        $m = 'May';
                    else if ($month == 6)
                        $m = 'June';
                    else if ($month == 7)
                        $m = 'July';
                    else if ($month == 8)
                        $m = 'August';
                    else if ($month == 9)
                        $m = 'Sepetember';
                    else if ($month == 10)
                        $m = 'October';
                    else if ($month == 11)
                        $m = 'November';
                    else if ($month == 12)
                        $m = 'December';
                    echo get_phrase('attendance_sheet');
                    ?>
                </h3>
                <h4 style="color: #696969;">
    <?php echo $class_name.' '.$class_name_numric; ?> : <?php echo get_phrase('section');?> <?php echo $section_name; ?><br>
    <?php echo 'Term '.$term. '|'. $m . ', ' . $sessional_year; ?>
                </h4>
            </div>
        </div>
        <div class="col-md-4"></div>
    </div>


    <hr />

    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered" id="my_table">
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
                    if($term == 1) {

                            $data = array();

                            $students = $this->db->get_where('enroll', array('student_id' => $student_id,'class_id' => $class_id, 'year' => $running_year, 'term' => 1, 'section_id' => $section_id));

                            if($students->num_rows() < 1) {
                                ?>
                                    <tr>
                                        <td colspan="32">
                                            <h4 style="color: red; text-align: center;">No Record Found!</h4>
                                        </td>
                                    </tr>
                                <?php
                            }

                            $students_array = $students->result_array();
                            if (sizeof($students_array) > 0):
                            foreach ($students_array as $row):
                                ?>
                        <tr>
                            <td style="text-align: center;">
                            <?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>
                            </td>
                            <?php
                            $counter = 0;
                            $status = 0;
                            for ($i = 1; $i <= $days; $i++) {
                                $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[1]);
                                //$this->db->group_by('timestamp');
                                $attendance = $this->db->get_where('attendance', array('section_id' => $section_id, 'class_id' => $class_id, 'year' => $running_year, 'term' => 1, 'timestamp' => $timestamp, 'student_id' => $row['student_id']));

                                $attendance_array = $attendance->result_array();

                                if($attendance->num_rows() > 0) {
                                    $counter++;
                                }


                                foreach ($attendance_array as $row1):
                                    $month_dummy = date('d', $row1['timestamp']);

                                    if ($i == $month_dummy)
                                    $status = $row1['status'];


                                endforeach;
                                ?>
                                <td style="text-align: center;">
                                <?php if ($status == 1) { ?>
                                                            <i class="entypo-record" style="color: #00a651;"></i>
                                                <?php  } if($status == 2)  { ?>
                                                            <i class="entypo-record" style="color: #ee4749;"></i>
                                <?php  } $status =0;?>


                                                    </td>

                            <?php } ?>

                        <?php endforeach; ?>
                         <h4 style="color: red; text-align: center;"><?php if($counter < 1) echo 'No Attendance Record Found For This Month';?></h4>
                      <?php endif; ?>

                    </tr>
                   <?php }elseif($term == 2) {

                            $data = array();

                            $students = $this->db->get_where('enroll', array('student_id' => $student_id,'class_id' => $class_id, 'year' => $running_year, 'term' => 2, 'section_id' => $section_id));

                            if($students->num_rows() < 1) {
                                ?>
                                    <tr>
                                        <td colspan="32">
                                            <h4 style="color: red; text-align: center;">No Record Found!</h4>
                                        </td>
                                    </tr>
                                <?php
                            }

                            $students_array = $students->result_array();
                            if (sizeof($students_array) > 0):
                            foreach ($students_array as $row):
                                ?>
                        <tr>
                            <td style="text-align: center;">
                            <?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>
                            </td>
                            <?php
                            $counter = 0;
                            $status = 0;
                            for ($i = 1; $i <= $days; $i++) {
                                $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[1]);
                                //$this->db->group_by('timestamp');
                                $attendance = $this->db->get_where('attendance', array('section_id' => $section_id, 'class_id' => $class_id, 'year' => $running_year, 'term' => 2, 'timestamp' => $timestamp, 'student_id' => $row['student_id']));

                                $attendance_array = $attendance->result_array();

                                if($attendance->num_rows() > 0) {
                                    $counter++;
                                }


                                foreach ($attendance_array as $row1):
                                    $month_dummy = date('d', $row1['timestamp']);

                                    if ($i == $month_dummy)
                                    $status = $row1['status'];


                                endforeach;
                                ?>
                                <td style="text-align: center;">
                                <?php if ($status == 1) { ?>
                                                            <i class="entypo-record" style="color: #00a651;"></i>
                                                <?php  } if($status == 2)  { ?>
                                                            <i class="entypo-record" style="color: #ee4749;"></i>
                                <?php  } $status =0;?>


                                                    </td>

                            <?php } ?>

                        <?php endforeach; ?>
                         <h4 style="color: red; text-align: center;"><?php if($counter < 1) echo 'No Attendance Record Found For This Month';?></h4>
                      <?php endif; ?>

                    </tr>

                   <?php }elseif($term == 3) {

                            $data = array();

                            $students = $this->db->get_where('enroll', array('student_id' => $student_id,'class_id' => $class_id, 'year' => $running_year, 'term' => 3, 'section_id' => $section_id));

                            if($students->num_rows() < 1) {
                                ?>
                                    <tr>
                                        <td colspan="32">
                                            <h4 style="color: red; text-align: center;">No Record Found!</h4>
                                        </td>
                                    </tr>
                                <?php
                            }

                            $students_array = $students->result_array();
                            if (sizeof($students_array) > 0):
                            foreach ($students_array as $row):
                                ?>
                        <tr>
                            <td style="text-align: center;">
                            <?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>
                            </td>
                            <?php
                            $counter = 0;
                            $status = 0;
                            for ($i = 1; $i <= $days; $i++) {
                                $timestamp = strtotime($i . '-' . $month . '-' . explode('-', $sessional_year)[1]);
                                //$this->db->group_by('timestamp');
                                $attendance = $this->db->get_where('attendance', array('section_id' => $section_id, 'class_id' => $class_id, 'year' => $running_year, 'term' => 3, 'timestamp' => $timestamp, 'student_id' => $row['student_id']));

                                $attendance_array = $attendance->result_array();

                                if($attendance->num_rows() > 0) {
                                    $counter++;
                                }


                                foreach ($attendance_array as $row1):
                                    $month_dummy = date('d', $row1['timestamp']);

                                    if ($i == $month_dummy)
                                    $status = $row1['status'];


                                endforeach;
                                ?>
                                <td style="text-align: center;">
                                <?php if ($status == 1) { ?>
                                                            <i class="entypo-record" style="color: #00a651;"></i>
                                                <?php  } if($status == 2)  { ?>
                                                            <i class="entypo-record" style="color: #ee4749;"></i>
                                <?php  } $status =0;?>


                                                    </td>

                            <?php } ?>

                        <?php endforeach; ?>
                         <h4 style="color: red; text-align: center;"><?php if($counter < 1) echo 'No Attendance Record Found For This Month';?></h4>
                      <?php endif; ?>

                    </tr>

                   <?php }; ?>

                </tbody>
            </table>
            <center>
                <a href="<?php echo base_url(); ?>index.php/student/attendance_report_print_view/<?php echo $class_id; ?>/<?php echo $section_id; ?>/<?php echo $month; ?>/<?php echo $sessional_year; ?>/<?php echo $student_id; ?>/<?php echo $term; ?>"
                   class="btn btn-primary" target="_blank">
    <?php echo get_phrase('print_attendance_sheet'); ?>
                </a>
            </center>
        </div>
    </div>
<?php endif; ?>
