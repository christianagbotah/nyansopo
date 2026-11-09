<div id="pre_notice"><center><h3 style="color: #fff;"><i class="fa fa-spinner fa-pulse"></i> Loading please wait...</h3><p style="color: #b3aeae;">Getting attendance page ready</p></center>
</div>

<style type="text/css">
    #pre_notice {
      position: fixed;
      z-index: 99999;
      top: 0;
      left: 0;
      bottom: 0;
      right: 0;
      background: rgba(0, 0, 0, 0.9);
      transition: 1s 0.4s;
    }

    #pre_notice h3 {
        margin-top: 45vh;
    }
</style>

<?php
    
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    
    $_level = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->level;

    $students_ids = explode('-', $student_id); //selected student(s)


    $class_name = $this->crud_model->get_class_name($class_id);

    ?>
        <hr />
<?php 

//total fees received today
$this->db->select_sum('feeding_paid');
$this->db->from('feeding_fee');
$this->db->where('timestamp', $timestamp);
$this->db->where('class_id', $class_id);
$feedingPaidToday = $this->db->get()->row()->feeding_paid;

$this->db->select_sum('classes_paid');
$this->db->from('feeding_fee');
$this->db->where('timestamp', $timestamp);
$this->db->where('class_id', $class_id);
$classesPaidToday = $this->db->get()->row()->classes_paid;


$timestamp_today = strtotime(date('d-m-Y'));

$this->db->select('timestamp');
$this->db->from('feeding_fee');
$this->db->where('class_id', $class_id);
//$this->db->where('year', $running_year);
//$this->db->where('term', $running_term);
$this->db->where('timestamp <', $timestamp_today);
$this->db->order_by('timestamp', 'desc');
$this->db->limit(1);
$fc_timestamp_query1 = $this->db->get();

if($fc_timestamp_query1->num_rows() > 0) {
    $fc_timestamp_query = $fc_timestamp_query1->row();
    $previous_day_timestamp = $fc_timestamp_query->timestamp; //selecting just the previously entered timestamp for this particular class

} else {
    $previous_day_timestamp = $today_timestamp;
}


//selecting the first date recorded for feeding and classes fees
$this->db->select('timestamp');
$this->db->from('feeding_fee');
$this->db->where('class_id', $class_id);
$this->db->where('year', $running_year);
$this->db->where('term', $running_term);
$this->db->order_by('timestamp', 'asc');
$this->db->limit(1);
$fc_query1 = $this->db->get();

if($fc_query1->num_rows() > 0) {
    $fc_query = $fc_query1->row();
    $first_day_timestamp_fc = $fc_query->timestamp; //selecting just the previously entered timestamp for this particular student

} else {
    $first_day_timestamp_fc = $timestamp; //strtotime(date('d-m-Y'));
}


//general
$feeding_query = $this->db->get_where('class' , array('class_id' => $class_id) )->row();
$feeding_fee_charged    =   $feeding_query->feeding_fee;
$classes_fee_charged    =   $feeding_query->classes_fee;


echo form_open(site_url('admin/attendance_selector/'), array('id' => 'att_selector_form'));?>
<div class="row">
    <?php
        if($account_type == 'teacher') {
            ?>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class'); ?></label>
                    <select name="class_id" class="form-control select2" onchange="select_section(this.value); select_students(this.value)"  id = "class_selection">
                        <option value=""><?php echo get_phrase('select_class'); ?></option>
                        <?php

                    getFullClassList($this->session->userdata('teacher_id'), $class_id);
                ?>
                    </select>
                </div>
            </div>
            <?php
        }else {
    ?>
    <div class="col-md-3">
        <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class'); ?></label>
            <select name="class_id" class="form-control select2" onchange="select_section(this.value); select_students(this.value)"  id = "class_selection">
                <option value=""><?php echo get_phrase('select_class'); ?></option>
                <?php
                    $teacher_id = '';
                    getFullClassList($teacher_id, $class_id);
                ?>
            </select>
        </div>
    </div>

<?php } ?>
<div id="section_holder">
    
    <div class="col-md-3">

        <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section'); ?></label>
            <select name="section_id" id="section_id" class="form-control select2">
                <?php
                $sections = $this->db->get_where('section', array(
                            'class_id' => $class_id
                        ))->result_array();
                foreach ($sections as $row):
                    ?>
                    <option value="<?php echo $row['section_id']; ?>"
                            <?php if ($section_id == $row['section_id']) echo 'selected'; ?>>
                            <?php echo $row['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>
</div>
    <div class="col-md-3">
        <div class="form-group">
            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('date'); ?></label>
            <input type="text" id="time_picker" class="form-control datepicker" name="timestamp" data-end-date="<?= date('d-m-Y');?>" data-format="dd-mm-yyyy"
                   value="<?php echo date("d-m-Y", $timestamp); ?>"/>
        </div>
    </div>

    <input type="hidden" name="year" value="<?php echo $running_year; ?>">
    <input type="hidden" name="term" value="<?php echo $running_term; ?>">

    <div class="col-md-3" style="margin-top: 20px;">
        <button type="submit" id = "submit" class="btn btn-info"><?php echo get_phrase('manage_attendance'); ?></button>
    </div>

    <hr>
<hr>
<br>
<br>
<div id="students_holder" style="display: none" class="col-md-6 col-lg-6 col-sm-12 col-xs-12"></div>

    <div class="col-md-6 col-lg-6 col-sm-12 col-xs-12">
        
        <div class="row" style="text-align: center;">
            <div class="col-sm-12">
                <div class="tile-stats tile-gray">
                    <div class="icon"><i class="entypo-chart-area" style="color: #8082ec;"></i></div>

                    <h3 style="color: #696969;">ATTENDANCE FOR <?php echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric; ?></h3>
                    <h4 style="color: #696969;">
                        <?php echo get_phrase('section'); ?> <?php echo $this->db->get_where('section', array('section_id' => $section_id))->row()->name; ?>
                    </h4>
                    <h4 style="color: #696969;">
                        <?php echo date("d M Y", $timestamp); ?>
                    </h4>

                    <div class="row col-md-6 col-sm-8 col-xs-8 text-right">
                        FEEDING FEE TOTAL: <span id="feeding_total" style="font-weight: bold;"><?=numfmt_format_currency($fmt, $feedingPaidToday, $currency); ?></span>
                    </div>

                    <div class="row col-md-6 col-sm-8 col-xs-8 text-right">
                        CLASSES FEE TOTAL: <span id="feeding_total" style="font-weight: bold;"><?=numfmt_format_currency($fmt, $classesPaidToday, $currency); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <center>
            <a class="btn btn-success" id="present_btn" onclick="mark_all_present()">
                <i class="entypo-check"></i> <?php echo get_phrase('mark_all_present'); ?>
            </a>
            <a class="btn btn-danger" id="absent_btn" onclick="mark_all_absent()">
                <i class="entypo-cancel"></i> <?php echo get_phrase('mark_all_absent'); ?>
            </a>
        </center><hr>


        <div class="row">
            <center>
            <a class="btn btn-info sel_btns"onclick="mark_all_paid_feeding()">
                <i class="entypo-check"></i> <?php echo get_phrase('bulk_feeding_fee'); ?>
            </a>

            <a class="btn btn-primary sel_btns" onclick="mark_all_paid_classes()">
                <i class="entypo-check"></i> <?php echo get_phrase('bulk_classes_fee'); ?>
            </a>
        <!--
            <a class="btn btn-primary sel_btns"  onclick="mark_all_paid_transport()">
                <i class="entypo-check"></i> <?php //echo get_phrase('mark_all_paid_transport_fare'); ?>
            </a> 
        </center>
        </div><br>-->
            </center>

        </div>
    </div>
    <?php echo form_close(); ?>
    <hr />
</div>


<div class="row" style="margin-top: 10px">

    <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12">

        <div class="row" id="wrong_date_alert" style="display: none;">
        <div class="alert alert-danger alert-dismissable" role="alert">
            <strong style="text-align: center;">Selected Date is below the first date you collected fees. System cannot allow you to do any changes to fees on this date.</strong>
        </div>
    </div>

        <?php echo form_open(site_url('admin/attendance_update/'. $class_id . '/' . $section_id . '/' . $timestamp), array('id' => 'attendance_form')); ?>
        <div id="attendance_update">
            <table class="table table-bordered" id="export_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <!-- <th><?php //echo 'ID No.'; ?></th> -->
                        <th><?php echo get_phrase('name'); ?></th>
                        <th><?php echo get_phrase('status'); ?></th>
                        <th><?php echo get_phrase('feeding_fee_paid'); ?></th>
                        <th><?php echo get_phrase('amount_owe'); ?></th>
                        <th><?php echo get_phrase('classes_fee_paid'); ?></th>
                        <th><?php echo get_phrase('amount_owe'); ?></th>
                        <!-- <th><?php //echo get_phrase('transport_fare_paid'); ?></th>
                        <th><?php //echo get_phrase('amount_owe'); ?></th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 1;
                    $select_id = 0;
                    $att_id_array = array();

                    $this->db->where_in('attendance.student_id', $students_ids);
                    $this->db->join('student', 'student.student_id = attendance.student_id');
                    $this->db->order_by('name', 'asc');
                    $attendance_of_students = $this->db->get_where('attendance', array(
                                'class_id' => $class_id,
                                'section_id' => $section_id,
                                'year' => $running_year,
                                'term' => $running_term,
                                'timestamp' => $timestamp
                            ));

                    $attendance_of_students_array = $attendance_of_students->result_array();
                    if($attendance_of_students->num_rows() < 1) {
                        if($running_term == 1) {
                            $this->session->set_flashdata('error_message', get_phrase('be_sure_you_have_promoted_students_during_term_3._please_contact_the_administrator_for_assistance'));

                            echo '<div margin-top: 10px" class="alert alert-danger alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                                    </button>
                                   <strong>'.get_phrase('be_sure_you_have_promoted_students_during_term_3._if_you_have_not_done_it,_please_contact_the_administrator_for_assistance').'</strong>
                                </div>';
                        }else{
                            $this->session->set_flashdata('error_message', get_phrase('no_record_was_found_for_this_class'));

                            echo '<div margin-top: 10px" class="alert alert-danger alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                                    </button>
                                   <strong>'.get_phrase('no_record_was_found_for_this_class.').' Possible Reason: Maybe You Have Not Admitted Any Student In This Class Yet.</strong>
                                </div>';
                        }
                    }
                    
        if($attendance_of_students->num_rows() > 0) {

                    foreach ($attendance_of_students_array as $row):

                         $studentData = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
                         $special_diet = $studentData->special_diet;
                         $studentBenCatIds = explode(',', $studentData->benefit_status);

                       /* if($special_diet == 1) {
                            $feeding_fee_charged = 0;
                            $feeding_fee_charged_b = 0;
                        }*/


                        //for beneficiaries
                        $isBoth = 0;

                        for($i=0; $i < count($studentBenCatIds); $i++) {

                            $this->db->where('category_id', $studentBenCatIds[$i]);
                            $benefiary_query = $this->db->get('benefit_category')->row();
                            
                            $catName = trim(strtolower($benefiary_query->name));

                            if($catName == 'classes fees only') {

                                 $classes_fee_charged_b = 0;
                                 $feeding_fee_charged_b = $feeding_fee_charged;

                                 $isBoth++; //counted for classes fees only

                            } else if(strtolower($benefiary_query->name) == 'feeding fees only') {

                                 $feeding_fee_charged_b = 0;
                                 $classes_fee_charged_b = $classes_fee_charged;

                                 $isBoth++; //counted for feeding fees only
                            } else {


                            }
                        }
                        
                        if($isBoth > 1) {

                            //meaning both
                            $feeding_fee_charged_b = 0;
                            $classes_fee_charged_b = 0;
                        }





                        //for selecting feeding fee and classes fee payers
                        $feeding_paid_rows = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id, 'student_id'=> $row['student_id'], 'timestamp'=>$timestamp));

                        if($feeding_paid_rows->num_rows() > 0) {
                            $feeding_paid = $feeding_paid_rows->row()->feeding_paid;
                            $classes_paid = $feeding_paid_rows->row()->classes_paid;
                        } else {
                            $feeding_paid = 0;
                            $classes_paid = 0;
                        }

                        //feeding and classes fee owe as at the previous day
                        $feeding_owe_query1 = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id, 'student_id'=> $row['student_id'], 'timestamp'=>$previous_day_timestamp));

                        if($feeding_owe_query1->num_rows() > 0) {
                            $feeding_owe_query = $feeding_owe_query1->row();
                            $feeding_fee_owe = $feeding_owe_query->due;

                            $classes_fee_owe = $feeding_owe_query->cdue;

                        } else {
                            $feeding_fee_owe = 0;

                            $classes_fee_owe = 0;
                        }



                        

                        $feeding_owe_now = $this->db->get_where('feeding_fee' , array(
            'class_id'=>$class_id, 'student_id'=> $row['student_id'], 'timestamp'=>$timestamp));

                        $benefit_status = $this->db->get_where('student', array(
            'student_id'=> $row['student_id']))->row()->benefit_status;

                        if($feeding_owe_now->num_rows() > 0) {

                            //for feeding fee
                            if($feeding_owe_now->row()->due == 0) {
                                $feeding_owe = '0';
                            } else if($feeding_owe_now->row()->due > 0 || $feeding_owe_now->row()->due < 0) {

                            $feeding_owe_now_after_payment = $feeding_owe_now->row()->due;
                                $feeding_owe = $feeding_owe_now_after_payment;
                            }   

                            //for classes fee
                            if($feeding_owe_now->row()->cdue == 0) {
                                $classes_owe = '0';
                            } else if($feeding_owe_now->row()->cdue > 0 || $feeding_owe_now->row()->cdue < 0) {

                                $classes_owe_now_after_payment = $feeding_owe_now->row()->cdue;
                                $classes_owe = $classes_owe_now_after_payment;
                            }                        
                        } else {
                            //checking if the student is a beneficiary or not
                            if($benefit_status == 0) {
                                $feeding_owe = $feeding_fee_owe + $feeding_fee_charged;
                                $classes_owe = $classes_fee_owe + $classes_fee_charged;
                            } else {
                                $feeding_owe = $feeding_fee_owe + $feeding_fee_charged_b;
                                $classes_owe = $classes_fee_owe + $classes_fee_charged_b;
                            }
                        }

                        //for transportation
                        //for selecting transport fare payers
                        /*$transport_paid_rows = $this->db->get_where('transport_fare' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$timestamp));

                        if($transport_paid_rows->num_rows() > 0) {
                            $fare_paid = $transport_paid_rows->row()->amount_paid;
                        } else {
                            $fare_paid = 0;
                        }

                        //transport fare owe as at the previous day
                        $transport_fare_owe = $this->db->get_where('transport_fare' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$previous_day_timestamp))->row()->due;

                        //transport id from student table
                        $transport_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->transport_id;

                        //transport fare from transport table
                        $transport_fare_charged = 0; //$this->db->get_where('transport', array('transport_id' => $transport_id))->row()->route_fare;

                        $fare_owe_now = $this->db->get_where('transport_fare' , array(
            'class_id'=>$class_id,'section_id'=>$section_id, 'student_id'=> $row['student_id'], 'year'=>$running_year, 'term'=>$running_term, 'timestamp'=>$timestamp));

                        if($fare_owe_now->num_rows() > 0) {

                            if($fare_owe_now->row()->due == 0) {
                                $fare_owe = '0';
                            } else if($fare_owe_now->row()->due > 0 || $fare_owe_now->row()->due < 0) {

                            $fare_owe_now_after_payment = $fare_owe_now->row()->due;
                                $fare_owe = $fare_owe_now_after_payment;
                            }                           
                        } else {
                                $fare_owe = $transport_fare_owe + $transport_fare_charged;
                        }*/



                        ?>

                        <tr>
                            <td><?php echo $count++; ?></td>
                            <!-- <td>
                                <?php //echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->student_code; ?>
                            </td> -->
                            <td <?php if(isset($student_id) && $row['student_id'] == $student_id) echo 'style="background: yellow"'; ?>>
                                <a href="<?=site_url('admin/student_profile/'.$row['student_id'].'#tab6')?>" target="_blank" title="View student profile"><?php echo $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?></a>
                            </td>
                            <td>
                                <select class="form-control" onchange="is_student_present('<?= $row['student_id']; ?>', '<?=$special_diet; ?>')" name="status_<?php echo $row['attendance_id']; ?>" id="status_<?php echo $row['student_id']; ?>">

                                    <option value="1" <?php if ($row['status'] == 1) echo 'selected'; ?>><?php echo get_phrase('present'); ?></option>
                                    
                                    <option value="2" <?php if ($row['status'] == 2) echo 'selected'; ?>><?php echo get_phrase('absent'); ?></option>

                                    
                                </select>
                            </td>
                            <?php
                                $special_diet = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->special_diet;
                            ?>
                            <td>

                                <?php

                                    if($account_type == 'teacher') {

                                        if(getTeacherPermissions('Can receive feeding & classes fees') == 1):
                                            ?>

                                            <input type="text" size="10" onkeyup="student_paid_feeding('<?= $row['student_id']; ?>')" name="feeding_paid_<?php echo $row['student_id']; ?>" class="form-control" value="<?= $special_diet == 1 ? 0 : $feeding_paid; ?>" id="feeding_paid_<?php echo $row['student_id']; ?>" <?php if($special_diet == 1) echo 'disabled="disabled"';  ?>>

                                            <?php

                                        else:
                                            echo '<span class="text-muted">Not Allowed!</span>';
                                        endif;



                                    } else {


                                    
                                    if(getAdminPermissions($_level, 'Can receive feeding & classes fees') == 1):
                                ?>

                                <input type="text" size="10" onkeyup="student_paid_feeding('<?= $row['student_id']; ?>')" name="feeding_paid_<?php echo $row['student_id']; ?>" class="form-control" value="<?= $special_diet == 1 ? 0 : $feeding_paid; ?>" id="feeding_paid_<?php echo $row['student_id']; ?>" <?php if($special_diet == 1) echo 'disabled="disabled"';  ?>>

                                <?php

                                    else:
                                        echo '<span class="text-muted">Not Allowed!</span>';
                                    endif;
                                }
                                    ?>

                            </td>

                            <td><input type="text" size="10" onchange="change_color()" value="<?= $special_diet == 1 ? 0 : $feeding_owe; ?>" name="feeding_owe_<?php echo $row['student_id']; ?>" class="form-control" readonly="true" id="feeding_owe_<?php echo $row['student_id']; ?>" <?php if($special_diet == 1) echo 'disabled="disabled"'; ?>></td>

                            <td>

                                <?php
                                    if($account_type == 'teacher') {

                                        if(getTeacherPermissions('Can receive feeding & classes fees') == 1):
                                            ?>
                                            <input type="text" size="10" onkeyup="student_paid_classes('<?= $row['student_id']; ?>')" name="classes_paid_<?php echo $row['student_id']; ?>" class="form-control" value="<?= $classes_paid; ?>" id="classes_paid_<?php echo $row['student_id']; ?>">

                                            <?php
                                        else:
                                            echo '<span class="text-muted">Not Allowed!</span>';
                                        endif;
                                
                                    } else {

                                        if(getAdminPermissions($_level, 'Can receive feeding & classes fees') == 1):
                                            ?>
                                            <input type="text" size="10" onkeyup="student_paid_classes('<?= $row['student_id']; ?>')" name="classes_paid_<?php echo $row['student_id']; ?>" class="form-control" value="<?= $classes_paid; ?>" id="classes_paid_<?php echo $row['student_id']; ?>">

                                            <?php
                                                else:
                                                    echo '<span class="text-muted">Not Allowed!</span>';
                                                endif;
                                        }
                                ?>

                            </td>

                            <td><input type="text" size="10" onchange="change_color()" value="<?= $classes_owe; ?>" name="classes_owe_<?php echo $row['student_id']; ?>" class="form-control" readonly="true" id="classes_owe_<?php echo $row['student_id']; ?>"></td>

                            <!-- <td><input type="text" size="10" onkeyup="student_paid_transport('<?//= $row['student_id']; ?>')" value="<?= $fare_paid; ?>" name="transport_paid_<?php //echo $row['student_id']; ?>" class="form-control" id="transport_paid_<?php //echo $row['student_id']; ?>"></td>

                            <td><input type="text" size="10" onchange="change_color()" value="<?= $fare_owe; ?>" name="transport_owe_<?php //echo $row['student_id']; ?>" class="form-control" id="transport_owe_<?php //echo $row['student_id']; ?>">

                                <input type="hidden" size="10" value="<?= $transport_fare_charged; ?>" name="transport_fare_charged_<?php //echo $row['student_id']; ?>" class="form-control" readonly="true" id="transport_fare_charged_<?php //echo $row['student_id']; ?>">
                            </td> -->
                        </tr>
                    <?php
                    $st_id_array[$select_id] = $row['student_id'];
                    $select_id++;
                    endforeach; 

                }
                    ?>


                </tbody>
            </table>
        </div>

        <center><input type="hidden" name="payment_time" value="<?php echo date('H:i:s');?>">

            <input type="hidden" name="students_ids" value="<?php echo $student_id;?>">
            <button type="submit" class="btn btn-info" id="submit_button">
                <i class="entypo-thumbs-up"></i> <?php echo get_phrase('save_changes'); ?>
            </button><br>
            <br>
            <div id="notifier" style="font-weight: bold; padding: 5px; background-color: #000; color: #fff; display: none">
                <p>Processing Data. Please Wait <i class="fa fa-spinner fa-pulse"></i></p>

                <p><em>If this takes too much time, then please check your network connection and try again.</em></p>
            </div>

            <div class="row" id="empty_error" style="display: none;">
                <div class="alert alert-danger alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">&times;</button>
                    <strong>Some Students' Attendance Status Is Unknown. Please Make Sure You Set The "Show Entries" Box At The Top Left Corner Of The Form To A Number More Than The Total Number Of Students In The Class, Then Mark Attendance Again. E.g. You Can Set It To 25, 50, 100 etc. <i>Note: The Default Entry is 10.</i></strong>
                </div>
                
            </div>
        </center>
        <?php echo form_close(); ?>

    </div>

</div>
    

<script type="text/javascript">
    
    $(function() {

    //disable submit button on page load
    $('#submit_button').attr('disabled', 'disabled');

    $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 

    select_students(<?=$class_id ?>); //show class members
    is_student_present_onload();

    //feeding fee owe calculator
   // feeding_owe_calculator();

        //$('#export_table').dataTable();

//remove the pre notifier after page has fully loaded

    $(window).on("load", function() {
        //disable submit button on page load
        $('#submit_button').attr('disabled', 'disabled');

        $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        });
    
    });

    
});

    //for firefox browser
    window.onload = function() {
        //disable submit button on page load
        $('#submit_button').attr('disabled', 'disabled');

        $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 
    };

//check if there's internet connectivity
const checkOnlineStatus = async () => {
    try {
        const response = await fetch('https://jsonplaceholder.typicode.com/posts?id=1', 
        );
        return response.status >= 200 && response.status < 300;

    } catch(err) {
        return false;
    }
}


//before submitting the attendance data, for these:
//1. if a student was not checked-in, yet marked present by teacher, abort update
//2. if a student was checked in but marked absent by teacher here, abort update

function verify_check_in() {

    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

      showAjaxModal_alert('Please wait, we are working on it <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

    let form_data = $('#attendance_form').serialize();

    $.ajax({
        url: '<?php echo site_url('admin/verify_check_in/'.$class_id .'/'. $timestamp) ?>',
        type: 'POST',
        dataType: 'html',
        data: form_data,
    })
    .done(function(response) {

        if(response.length > 0) {
            showAjaxModal_alert(response, 'Warning');
            return false;
        } else {
            //go ahead
            $.ajax({
                url: '<?php echo site_url('admin/attendance_update/'.$class_id .'/'. $section_id.'/'. $timestamp) ?>',
                type: 'POST',
                dataType: 'json',
                data: form_data,
            })
            .done(function(data) {
                showAjaxModal_alert(data.message, 'Success');
                
                setTimeout(() => {
                    $('#att_selector_form').submit();
                    $('.close').click();
                    
                    //window.location.reload();
                }, 3000);
            })
            .fail(function(err) {
                showAjaxModal_alert(err.responseText, 'Error');
            });
        }
    })
    .fail(function() {
        showAjaxModal_alert('An error occurred while processing your request.', 'Error', false, true);
    });
    
}



//to check if a student is marked absent or undefined, if so, disable that student's payment fields
function is_student_present(id, special_diet, counter) {

    //disable submit button on page load and show notifier
    $('#submit_button').attr('disabled', 'disabled');
    $('#notifier').slideDown('slow');

    var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;
    var timestamp = <?php echo $timestamp; ?>;
    let first_day_timestamp = Number(<?php echo $first_day_timestamp_fc; ?>);
    let timestamp2 = Number(<?php echo $timestamp; ?>);

    let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
    let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
    let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);
    let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

    if(special_diet == 1) {

        feeding_fee_charged = 0;
        feeding_fee_charged_b = 0;
    }

    //check if the current timestamp already exists in the feeding table
    let feeding_owe_now_rows = <?php echo $feeding_owe_now->num_rows(); ?>;
    //let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

    //feeding
    let feeding_paid = Number($('#feeding_paid_' + id).val());
    let feeding_owe =  Number($('#feeding_owe_' + id).val());

    //classes
    let classes_paid = Number($('#classes_paid_' + id).val());
    let classes_owe =  Number($('#classes_owe_' + id).val());


    /*//transport
    let fare_paid = Number($('#transport_paid_' + id).val())
    let transport_fare_charged = Number($('#transport_fare_charged_' + id).val());
    let fare_owe =  Number($('#transport_owe_' + id).val());*/


    let att_status = $('#status_' + id).val();

    var class_id = Number(<?php echo $class_id; ?>);
    var previous_day_timestamp = Number(<?php echo $previous_day_timestamp; ?>);
    var feeding_paid_now = Number($('#feeding_paid_' + id).val());
    //var fare_paid_now = Number($('#transport_paid_' + id).val());

    if(first_day_timestamp > timestamp2) { //disable all input fields if user selects a date less than the 1st date
            $('#wrong_date_alert').css('display', 'block');
            $('#submit_button').attr('disabled', 'disabled');

            $('#submit_button').click(function(event) {
                return false;
            });

            $('#feeding_paid_' + id).attr('readonly', 'true');
            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
            $('#feeding_paid_' + id).val(''); 
            $('#feeding_owe_' + id).attr('readonly', 'true');
            $('#feeding_owe_' + id).attr('placeholder', 'Not Available');
            $('#feeding_owe_' + id).val(''); 
            $('#feeding_owe_' + id).removeAttr('style');

            $('#classes_paid_' + id).attr('readonly', 'true');
            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
            $('#classes_paid_' + id).val(''); 
            $('#classes_owe_' + id).attr('readonly', 'true');
            $('#classes_owe_' + id).attr('placeholder', 'Not Available');
            $('#classes_owe_' + id).val('');
            $('#classes_owe_' + id).removeAttr('style');

            /*$('#transport_paid_' + id).attr('readonly', 'true');
            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
            $('#transport_paid_' + id).val('');
            $('#transport_owe_' + id).attr('readonly', 'true');
            $('#transport_owe_' + id).attr('placeholder', 'Not Available');
            $('#transport_owe_' + id).val('');
            $('#transport_owe_' + id).removeAttr('style');*/

        } else {
            $('#wrong_date_alert').css('display', 'none');
        


            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + id,

                success: function(bf_response) {
                    if(bf_response == 'no') {
                        //feeding ajax     

                            $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status == '2') {

                                            if(feeding_owe == 0 || feeding_owe == '') {
                                                $('#feeding_paid_' + id).attr('readonly', 'true');
                                                $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#feeding_paid_' + id).val(''); 
                                                $('#feeding_owe_' + id).val(Number(response));//show amount owe as at today 

                                                /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#transport_paid_' + id).val('');*/
                                                //$('#transport_owe_' + id).val(Number(response));//show amount owe as at today

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            } else {


                                                $('#feeding_paid_' + id).attr('readonly', 'true');
                                                $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#feeding_paid_' + id).val(''); 

                                                if(feeding_owe < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                                    //$('#feeding_owe_' + id).val(feeding_owe + feeding_fee_charged);//show amount owe as at today

                                                    //if the student paid in advance but decides not to come to school, we don't charge
                                                    $('#feeding_owe_' + id).val(feeding_owe - feeding_fee_charged);//show amount owe as at today 
                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled'); 

                                                } else {
                                                    $('#feeding_owe_' + id).val(feeding_owe - feeding_fee_charged);//show amount owe as at today 
                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled'); 
                                                }
                                                

                                                /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#transport_paid_' + id).val('');*/
                                                //$('#transport_owe_' + id).val(Number(response));//show amount owe as at today

                                                }
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged + '/' + 'no',

                                                success: function(fee_paid) {

                                                    if(feeding_owe == 0 || feeding_owe == '') {

                                                        $('#feeding_paid_' + id).val(fee_paid);
                                                        $('#feeding_paid_' + id).removeAttr('readonly');
                                                        $('#feeding_paid_' + id).removeAttr('placeholder');

                                                        if(fee_paid == 0 && response == 0) {
                                                            $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged);//Add today's charge 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        } else {
                                                            $('#feeding_owe_' + id).val(Number(response));//show the amount owe 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        }
                                                        

                                                       /* $('#transport_paid_' + id).removeAttr('readonly');
                                                        $('#transport_paid_' + id).removeAttr('placeholder');*/
 

                                                    } else {
                                                        $('#feeding_paid_' + id).val(fee_paid);
                                                        $('#feeding_paid_' + id).removeAttr('readonly');
                                                        $('#feeding_paid_' + id).removeAttr('placeholder');
                                                        $('#feeding_owe_' + id).val(Number(response));//show the amount owe 

                                                        /*$('#transport_paid_' + id).removeAttr('readonly');
                                                        $('#transport_paid_' + id).removeAttr('placeholder');*/

                                                        //enable the submit button
                                                        $('#submit_button').removeAttr('disabled'); 

                                                    }

                                                }
                                            });

                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status == '2') {

                                            let f_owing = Number(response);

                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);

                                            if(f_owing < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                               // $('#feeding_owe_' + id).val(Number(response));//just show what the student owes as at today (commulative)


                                                 //if the student paid in advance but decides not to come to school, we still charge
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_fee_charged);//just show what the student owes as at today (commulative)

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            } else {
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_fee_charged);//just show what the student owes as at today (commulative)

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            }

                                             

                                            /*$('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');*/


                                            } else if(att_status == '1') {

                                            $('#feeding_paid_' + id).val(feeding_paid);
                                            $('#feeding_paid_' + id).removeAttr('readonly');
                                            $('#feeding_paid_' + id).removeAttr('placeholder');
                                           // $('#feeding_owe_' + id).val(0);
                                            $('#feeding_owe_' + id).val(Number(response));//Add today's charge 

                                            /*$('#transport_paid_' + id).removeAttr('readonly');
                                            $('#transport_paid_' + id).removeAttr('placeholder');*/

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled'); 

                                        }  

                                    }                     
                                }
                            });
                    
                    //classes ajax     

                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status == '2') {

                                            if(classes_owe == 0 || classes_owe == '') {
                                                $('#classes_paid_' + id).attr('readonly', 'true');
                                                $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#classes_paid_' + id).val(''); 
                                                $('#classes_owe_' + id).val(Number(response));//show amount owe as at today 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
             
                                            } else {


                                                $('#classes_paid_' + id).attr('readonly', 'true');
                                                $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#classes_paid_' + id).val('');

                                                if(classes_owe < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                                   // $('#classes_owe_' + id).val(classes_owe + classes_fee_charged);//show amount owe as at today

                                                   //if the student paid in advance but decides not to come to school, we don't charge
                                                    $('#classes_owe_' + id).val(classes_owe - classes_fee_charged);//show amount owe as at today

                                                        //enable the submit button
                                                        $('#submit_button').removeAttr('disabled'); 
                                                } else {
                                                    $('#classes_owe_' + id).val(classes_owe - classes_fee_charged);//show amount owe as at today

                                                        //enable the submit button
                                                        $('#submit_button').removeAttr('disabled'); 
                                                }

                                                 
             
                                                }
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged + '/' + 'no',

                                                success: function(class_fee_paid) {

                                                    if(classes_owe == 0 || classes_owe == '') {

                                                        $('#classes_paid_' + id).val(class_fee_paid);
                                                        $('#classes_paid_' + id).removeAttr('readonly');
                                                        $('#classes_paid_' + id).removeAttr('placeholder');

                                                        if(class_fee_paid == 0 && response == 0) {
                                                            $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);//Add today's charge 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 

                                                        } else {
                                                            $('#classes_owe_' + id).val(Number(response));//show the amount owe 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        }
                                                        
                                                    } else {
                                                        $('#classes_paid_' + id).val(class_fee_paid);
                                                        $('#classes_paid_' + id).removeAttr('readonly');
                                                        $('#classes_paid_' + id).removeAttr('placeholder');
                                                        $('#classes_owe_' + id).val(Number(response));//show the amount owe 

                                                        //enable the submit button
                                                        $('#submit_button').removeAttr('disabled'); 
                                                    }

                                                }
                                            });
                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status == '2') {

                                            let c_owing = Number(response);

                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);

                                            if(c_owing < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                               // $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);//just show what the student owes as at today (commulative) 

                                                //if the student paid in advance but decides not to come to school, we don't charge
                                                $('#classes_owe_' + id).val(Number(response) - classes_fee_charged);//just show what the student owes as at today (commulative) 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            } else {
                                                $('#classes_owe_' + id).val(Number(response) - classes_fee_charged);//just show what the student owes as at today (commulative) 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            }

                                            

                                            } else if(att_status == '1') {

                                            $('#classes_paid_' + id).val(feeding_paid);
                                            $('#classes_paid_' + id).removeAttr('readonly');
                                            $('#classes_paid_' + id).removeAttr('placeholder');
                                           // $('#feeding_owe_' + id).val(0);
                                            $('#classes_owe_' + id).val(Number(response));//Add today's charge 

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled'); 
                                        }  

                                    }                     
                                }
                            });
                    } else {
                        $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status == '2') {

                                            if(feeding_owe == 0 || feeding_owe == '') {
                                                $('#feeding_paid_' + id).attr('readonly', 'true');
                                                $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#feeding_paid_' + id).val(''); 
                                                $('#feeding_owe_' + id).val(Number(response));//show amount owe as at today 

                                                /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#transport_paid_' + id).val('');*/
                                                //$('#transport_owe_' + id).val(Number(response));//show amount owe as at today 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 

                                            } else {
                                                $('#feeding_paid_' + id).attr('readonly', 'true');
                                                $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#feeding_paid_' + id).val(''); 

                                                if(feeding_owe < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                                   // $('#feeding_owe_' + id).val(feeding_owe + feeding_fee_charged_b);//show amount owe as at today 

                                                   //if the student paid in advance but decides not to come to school, we don't charge
                                                    $('#feeding_owe_' + id).val(feeding_owe - feeding_fee_charged_b);//show amount owe as at today 

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled'); 

                                                } else {
                                                    $('#feeding_owe_' + id).val(feeding_owe - feeding_fee_charged_b);//show amount owe as at today 

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled'); 
                                                }

                                                

                                               /* $('#transport_paid_' + id).attr('readonly', 'true');
                                                $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#transport_paid_' + id).val('');*/
                                                //$('#transport_owe_' + id).val(Number(response));//show amount owe as at today 


                                                }
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b + '/' + 'no',

                                                success: function(fee_paid) {

                                                    if(feeding_owe == 0 || feeding_owe == '') {

                                                        $('#feeding_paid_' + id).val(fee_paid);
                                                        $('#feeding_paid_' + id).removeAttr('readonly');
                                                        $('#feeding_paid_' + id).removeAttr('placeholder');

                                                        if(fee_paid == 0 && response == 0) {
                                                            $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);//Add today's charge 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        } else {
                                                            $('#feeding_owe_' + id).val(Number(response));//show the amount owe 
                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        }
                                                        

                                                       /* $('#transport_paid_' + id).removeAttr('readonly');
                                                        $('#transport_paid_' + id).removeAttr('placeholder');*/
                                                    } else {
                                                        $('#feeding_paid_' + id).val(fee_paid);
                                                        $('#feeding_paid_' + id).removeAttr('readonly');
                                                        $('#feeding_paid_' + id).removeAttr('placeholder');
                                                        $('#feeding_owe_' + id).val(Number(response));//show the amount owe 

                                                        /*$('#transport_paid_' + id).removeAttr('readonly');
                                                        $('#transport_paid_' + id).removeAttr('placeholder');*/
                                                        //enable the submit button
                                                        $('#submit_button').removeAttr('disabled'); 

                                                    }

                                                }
                                            });

                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status == '2') {

                                            let f_owing = Number(response);

                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);

                                            if(f_owing < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                                //$('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);//just show what the student owes as at today (commulative)


                                                //if the student paid in advance but decides not to come to school, we still charge
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_fee_charged_b);//just show what the student owes as at today (commulative) 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            } else {
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_fee_charged_b);//just show what the student owes as at today (commulative) 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            }
                                            

                                            /*$('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');*/

                                            } else if(att_status == '1') {

                                            $('#feeding_paid_' + id).val(feeding_paid);
                                            $('#feeding_paid_' + id).removeAttr('readonly');
                                            $('#feeding_paid_' + id).removeAttr('placeholder');
                                           // $('#feeding_owe_' + id).val(0);
                                            $('#feeding_owe_' + id).val(Number(response));//Add today's charge 

                                            /*$('#transport_paid_' + id).removeAttr('readonly');
                                            $('#transport_paid_' + id).removeAttr('placeholder');*/

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled'); 
                                        }  

                                    }                     
                                }
                            });
                    
                    //classes ajax     

                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator_is_present/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists
                                        if(att_status == '2') {

                                            if(classes_owe == 0 || classes_owe == '') {
                                                $('#classes_paid_' + id).attr('readonly', 'true');
                                                $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#classes_paid_' + id).val(''); 
                                                $('#classes_owe_' + id).val(Number(response));//show amount owe as at today 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
             
                                            } else {
                                                $('#classes_paid_' + id).attr('readonly', 'true');
                                                $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                $('#classes_paid_' + id).val(''); 

                                                if(classes_owe < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                                    //$('#classes_owe_' + id).val(classes_owe + classes_fee_charged_b);//show amount owe as at today

                                                    //if the student paid in advance but decides not to come to school, we don't charge
                                                    $('#classes_owe_' + id).val(classes_owe - classes_fee_charged_b);//show amount owe as at today  

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled'); 
                                                } else {
                                                    $('#classes_owe_' + id).val(classes_owe - classes_fee_charged_b);//show amount owe as at today 

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled'); 
                                                }
                                                
             
                                                }
                                        } else if(att_status == '1') {

                                            $.ajax({ //trying to get the feeding fee paid directly from the database
                                                url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b + '/' + 'no',

                                                success: function(class_fee_paid) {

                                                    if(classes_owe == 0 || classes_owe == '') {

                                                        $('#classes_paid_' + id).val(class_fee_paid);
                                                        $('#classes_paid_' + id).removeAttr('readonly');
                                                        $('#classes_paid_' + id).removeAttr('placeholder');

                                                        if(class_fee_paid == 0 && response == 0) {
                                                            $('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);//Add today's charge 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        } else {
                                                            $('#classes_owe_' + id).val(Number(response));//show the amount owe 

                                                            //enable the submit button
                                                            $('#submit_button').removeAttr('disabled'); 
                                                        }
                                                        
                                                    } else {
                                                        $('#classes_paid_' + id).val(class_fee_paid);
                                                        $('#classes_paid_' + id).removeAttr('readonly');
                                                        $('#classes_paid_' + id).removeAttr('placeholder');
                                                        $('#classes_owe_' + id).val(Number(response));//show the amount owe 

                                                        //enable the submit button
                                                        $('#submit_button').removeAttr('disabled'); 
                                                    }

                                                }
                                            });
                                            
                                        }  
                                    } else {
                                        //current timestamp does not exist
                                        if(att_status == '2') {

                                            let c_owing = Number(response);

                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);

                                            if(c_owing < 0) { //if the student paid in advance but decides not to come to school, we still charge
                                                //$('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);//just show what the student owes as at today (commulative)


                                                //if the student paid in advance but decides not to come to school, we don't charge
                                                $('#classes_owe_' + id).val(Number(response) - classes_fee_charged_b);//just show what the student owes as at today (commulative)  

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            } else {
                                                $('#classes_owe_' + id).val(Number(response) - classes_fee_charged_b);//just show what the student owes as at today (commulative) 

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled'); 
                                            }

                                            

                                            } else if(att_status == '1') {

                                            $('#classes_paid_' + id).val(feeding_paid);
                                            $('#classes_paid_' + id).removeAttr('readonly');
                                            $('#classes_paid_' + id).removeAttr('placeholder');
                                           // $('#feeding_owe_' + id).val(0);
                                            $('#classes_owe_' + id).val(Number(response));//Add today's charge 

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled'); 
                                        }  

                                    }                     
                                }
                            });
                    }
                }
            });
       

        //transport ajax
             /**   $.ajax({
                    url: '<?php //echo site_url('admin/fare_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + transport_fare_charged,

                    success: function(response) {
                        if(feeding_owe_now_rows > 0 && fare_owe_now_rows > 0) {
                            //current timestamp already exists
                            if(att_status == '2' && fare_owe != 0) {

                                if(fare_owe == 0 || fare_owe == '') {
                                    $('#transport_owe_' + id).val(Number(response));//show amount owe as at today 
                                } else {
                                    $('#transport_owe_' + id).val(fare_owe - transport_fare_charged);//show amount owe as at today 
                                }

                            } else if(att_status == '1') {

                                $.ajax({ //trying to get the fare paid directly from the database
                                    url: '<?php //echo site_url('admin/fare_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + transport_fare_charged + '/'+ 'no',

                                    success: function(t_paid) {


                                        if(fare_owe == 0 || fare_owe == '') { 
                                             $('#transport_paid_' + id).val(t_paid);
                                             
                                             if(t_paid == 0 && response == 0) {
                                                $('#transport_owe_' + id).val(Number(response) + transport_fare_charged);//add today's charge  
                                            } else {
                                                $('#transport_owe_' + id).val(Number(response));//show amount owe as at today  
                                            }
                                        } else {
                                             $('#transport_paid_' + id).val(t_paid);
                                             $('#transport_owe_' + id).val(Number(response));//show amount owe as at today  
                                        }
                                    }
                                });
                            }  
                        } else {
                            //current timestamp does not exist
                            if(att_status == '2') {

                            $('#transport_owe_' + id).val(Number(response) - transport_fare_charged);//just show what the student owes as at today (commulative) 

                            } else if(att_status == '1') {

                                $('#transport_paid_' + id).val(fare_paid);
                                $('#transport_owe_' + id).val(Number(response));//Add today's charge 
                            }  
                        }
                                             
                    }
                }); **/
            }

    //feeding fee amount owe calculator
   // feeding_owe_calculator();

   //hide notifier
   if(counter != undefined)  {
    if(counter == st_id_array.length -1) {
        $('#notifier').slideUp('slow');
    }
   } else {
    $('#notifier').slideUp('slow');
   }
    
}

//to check if a student is marked absent or undefined, if so, disable that student's payment fields
function is_student_present_onload() {
    var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;  
    
    let first_day_timestamp = Number(<?php echo $first_day_timestamp_fc; ?>);
    let timestamp = Number(<?php echo $timestamp; ?>);

    let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
    let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
    let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);
    let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

    for(var i = 0; i < st_id_array.length; i++) {
        let att_status = $('#status_' + st_id_array[i]).val();


        //for feeding
        let feeding_paid = Number($('#feeding_paid_' + st_id_array[i]).val());
        let feeding_owe =  Number($('#feeding_owe_' + st_id_array[i]).val());

        if(feeding_owe > 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(feeding_owe == 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(feeding_owe < 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }

        //for classes
        let classes_paid = Number($('#classes_paid_' + st_id_array[i]).val());
        let classes_owe =  Number($('#classes_owe_' + st_id_array[i]).val());

        if(classes_owe > 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(classes_owe == 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(classes_owe < 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }

        //transport
        /*let fare_paid = Number($('#transport_paid_' + st_id_array[i]).val());
        let fare_owe =  Number($('#transport_owe_' + st_id_array[i]).val());
        let transport_fare_charged = Number($('#transport_fare_charged_' + st_id_array[i]).val());*/



        /*if(fare_owe > 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(fare_owe == 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(fare_owe < 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }*/


        if(att_status == '2') {
            $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#feeding_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#feeding_paid_' + st_id_array[i]).val(''); 
            $('#feeding_owe_' + st_id_array[i]).val(feeding_owe);//show today's charge 

            $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#classes_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#classes_paid_' + st_id_array[i]).val(''); 
            $('#classes_owe_' + st_id_array[i]).val(classes_owe);//show today's charge 

            /*$('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#transport_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#transport_paid_' + st_id_array[i]).val('');
            $('#transport_owe_' + st_id_array[i]).val(fare_owe);//show today's charge */


        } else if(att_status == '1') {
            $('#feeding_paid_' + st_id_array[i]).val(feeding_paid);
            $('#feeding_paid_' + st_id_array[i]).removeAttr('readonly');
            $('#feeding_paid_' + st_id_array[i]).removeAttr('placeholder');
            $('#feeding_owe_' + st_id_array[i]).val(feeding_owe);//Add today's charge 

            $('#classes_paid_' + st_id_array[i]).val(classes_paid);
            $('#classes_paid_' + st_id_array[i]).removeAttr('readonly');
            $('#classes_paid_' + st_id_array[i]).removeAttr('placeholder');
            $('#classes_owe_' + st_id_array[i]).val(classes_owe);//Add today's charge 

            /*$('#transport_paid_' + st_id_array[i]).val(fare_paid);
            $('#transport_paid_' + st_id_array[i]).removeAttr('readonly');
            $('#transport_paid_' + st_id_array[i]).removeAttr('placeholder');
            $('#transport_owe_' + st_id_array[i]).val(fare_owe);//Add today's charge */

        }   

        if(first_day_timestamp > timestamp) { //disable all input fields if user selects a date less than the 1st date

            $('#wrong_date_alert').css('display', 'block');

            $('#submit_button').attr('disabled', 'disabled');

            $('#submit_button').click(function(event) {
                return false;
            });

            $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#feeding_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#feeding_paid_' + st_id_array[i]).val(''); 
            $('#feeding_owe_' + st_id_array[i]).attr('readonly', 'true');
            $('#feeding_owe_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#feeding_owe_' + st_id_array[i]).val(''); 
            $('#feeding_owe_' + st_id_array[i]).removeAttr('style');

            $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#classes_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#classes_paid_' + st_id_array[i]).val(''); 
            $('#classes_owe_' + st_id_array[i]).attr('readonly', 'true');
            $('#classes_owe_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#classes_owe_' + st_id_array[i]).val('');
            $('#classes_owe_' + st_id_array[i]).removeAttr('style');

            /*$('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            $('#transport_paid_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#transport_paid_' + st_id_array[i]).val('');
            $('#transport_owe_' + st_id_array[i]).attr('readonly', 'true');
            $('#transport_owe_' + st_id_array[i]).attr('placeholder', 'Not Available');
            $('#transport_owe_' + st_id_array[i]).val('');
            $('#transport_owe_' + st_id_array[i]).removeAttr('style');*/


        } else {
            $('#wrong_date_alert').css('display', 'none');
        }

        
        //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
        $.ajax({
            url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + st_id_array[i],
            async: false,

            success: function(bf_response) {
                if(bf_response == 'no') {
                    //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
                    if(feeding_fee_charged == 0 || feeding_fee_charged == '' || feeding_fee_charged == null) {
                        //$('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
                    }

                    if(classes_fee_charged == 0 || classes_fee_charged == '' || classes_fee_charged == null) {
                        $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');

                    }

                } else {
                    //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
                    if(feeding_fee_charged_b == 0 || feeding_fee_charged_b == '' || feeding_fee_charged_b == null) {
                      //  $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');


                    }

                    if(classes_fee_charged_b == 0 || classes_fee_charged_b == '' || classes_fee_charged_b == null) {
                        $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');

                    }
                }
            }
        });

        /** if(transport_fare_charged == 0 || transport_fare_charged == '' || transport_fare_charged == null) {
                        $('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            } //end**/
        
    }

}



//updating feeding owe when a student pays an amount
async function student_paid_feeding(id, counter) {

    //check if user has internet access at the moment
    const hasInternet = await checkOnlineStatus();


    if(hasInternet == false) {

        $('#modal_alert .modal-content').css({
            marginTop: window.scrollY + 100 + 'px',
        });

        $('#submit_button').attr('disabled', 'disabled');
        showAjaxModal_alert('<i class="fa fa-info-circle"></i> No internet. Check your internet connectivity!', 'Error');
        return false;
    } //checking for online is done

    //disable submit button on page load and show notifier
    $('#submit_button').attr('disabled', 'disabled');
    $('#notifier').slideDown('slow');
    
    var st_id_array = <?php echo json_encode($st_id_array); ?>;
     var timestamp = <?php echo $timestamp; ?>;


            var class_id = Number(<?php echo $class_id; ?>);
            var previous_day_timestamp = Number(<?php echo $previous_day_timestamp; ?>);
            var att_status = $('#status_' + id).val();

            let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
            let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);


            //check if the current timestamp already exists in the feeding table
            let feeding_owe_now_rows = <?php echo $feeding_owe_now->num_rows(); ?>;
            //let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

            //for feeding
            var feeding_owe =  Number($('#feeding_owe_' + id).val());
            var feeding_paid_now = Number($('#feeding_paid_' + id).val());


            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + id,

                success: function(bf_response) {
                    if(bf_response == 'no') {
                        //feeding ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the feeding fee paid directly from the database
                                        url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged + '/' + 'yes',

                                            success: function(fee_owe) {

                                                if(att_status == 1) {
                                                    /*if(fee_paid == 0 || fee_paid == '') {

                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            
                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#feeding_owe_' + id).val((Number(response) + feeding_fee_charged) - feeding_paid_now);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);
                                                            }
                                                        }
                                                    } else {
                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged);
                                                        } else {
                                                            $('#feeding_owe_' + id).val((Number(response)  + feeding_fee_charged) - feeding_paid_now);
                                                        }
                                                    }*/

                                                    $('#feeding_owe_' + id).val((fee_owe - feeding_paid_now) + feeding_fee_charged);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#feeding_paid_' + id).attr('readonly', 'true');
                                                    $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#feeding_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                                    /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                    $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#transport_paid_' + id).val('');
*/
                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                $('#feeding_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            } else {
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');
                                            }
                                        } else if(att_status == 2) {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                            /*$('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');*/

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled');
                                        }
                                    }
                                    
                                }
                            });
                    } else {
                       //feeding ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/feeding_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b,

                                success: function(response) {

                                    if(feeding_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the feeding fee paid directly from the database
                                        url: '<?php echo site_url('admin/feeding_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + feeding_fee_charged_b + '/' + 'yes',

                                            success: function(fee_owe) {

                                                if(att_status == 1) {
                                                    /*if(fee_paid == 0 || fee_paid == '') {

                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            
                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#feeding_owe_' + id).val((Number(response) + feeding_fee_charged_b) - feeding_paid_now);
                                                            } else {
                                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);
                                                            }
                                                        }
                                                    } else {
                                                        if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                            $('#feeding_owe_' + id).val(Number(response) + feeding_fee_charged_b);
                                                        } else {
                                                            $('#feeding_owe_' + id).val((Number(response)  + feeding_fee_charged_b) - feeding_paid_now);
                                                        }
                                                    }*/

                                                    $('#feeding_owe_' + id).val((fee_owe -feeding_paid_now) + feeding_fee_charged_b);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#feeding_paid_' + id).attr('readonly', 'true');
                                                    $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#feeding_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                                    /*$('#transport_paid_' + id).attr('readonly', 'true');
                                                    $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#transport_paid_' + id).val('');
*/
                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(feeding_paid_now == 0 || feeding_paid_now == '') {
                                                $('#feeding_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');
                                            } else {
                                                $('#feeding_owe_' + id).val(Number(response) - feeding_paid_now);

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');
                                            }
                                        } else if(att_status == 2) {
                                            $('#feeding_paid_' + id).attr('readonly', 'true');
                                            $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#feeding_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#feeding_owe_' + id).val(Number(response));//subtract today's charge 

                                            /*$('#transport_paid_' + id).attr('readonly', 'true');
                                            $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#transport_paid_' + id).val('');*/

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled');
                                        }
                                    }
                                    
                                }
                            });
                    }
                }
            });

    //hide notifier
   if(counter != undefined)  {
    if(counter == st_id_array.length -1) {
        $('#notifier').slideUp('slow');
    }
   } else {
    $('#notifier').slideUp('slow');
   }

   $('#modal_alert button').show();

   $('.close').click();
}

//updating classes owe when a student pays an amount
async function student_paid_classes(id, counter) {

    //check if user has internet access at the moment
    const hasInternet = await checkOnlineStatus();
    if(hasInternet == false) {

        $('#modal_alert .modal-content').css({
            marginTop: window.scrollY + 100 + 'px',
        });

        $('#submit_button').attr('disabled', 'disabled');
        showAjaxModal_alert('<i class="fa fa-info-circle"></i> No internet. Check your internet connectivity!', 'Error');
        return false;
    } //checking for online is done


    //disable submit button on page load and show notifier
    $('#submit_button').attr('disabled', 'disabled');
    $('#notifier').slideDown('slow');
    
    var st_id_array = <?php echo json_encode($st_id_array); ?>;
     var timestamp = <?php echo $timestamp; ?>;

            var class_id = Number(<?php echo $class_id; ?>);
            var previous_day_timestamp = Number(<?php echo $previous_day_timestamp; ?>);
            var att_status = $('#status_' + id).val();

            //classes fee

            let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);

            let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);


            //check if the current timestamp already exists in the feeding table
            let classes_owe_now_rows = <?php echo $feeding_owe_now->num_rows(); ?>;
            //let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

            //for classes
            var classes_owe =  Number($('#classes_owe_' + id).val());
            var classes_paid_now = Number($('#classes_paid_' + id).val());

            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + id,

                success: function(bf_response) {
                    if(bf_response == 'no') {
                        //classes ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged,

                                success: function(response) {

                                    if(classes_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the classes fee paid directly from the database
                                        url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged + '/' + 'yes',

                                            success: function(class_fee_owe) {

                                                if(att_status == 1) {
                                                    /*if(class_fee_paid == 0 || class_fee_paid == '') {

                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            
                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#classes_owe_' + id).val((Number(response) + classes_fee_charged) - classes_paid_now);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                            }
                                                        }

                                                        

                                                    } else {
                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            $('#classes_owe_' + id).val(Number(response) + classes_fee_charged);
                                                        } else {
                                                            $('#classes_owe_' + id).val((Number(response)  + classes_fee_charged) - classes_paid_now);
                                                        }
                                                    }*/
                                                    $('#classes_owe_' + id).val((class_fee_owe -classes_paid_now) + classes_fee_charged);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#classes_paid_' + id).attr('readonly', 'true');
                                                    $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#classes_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#classes_owe_' + id).val(Number(response));

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(classes_paid_now == 0 || classes_paid_now == '') {
                                                $('#classes_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            } else {
                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            }
                                        } else if(att_status == 2) {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#classes_owe_' + id).val(Number(response));

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled'); 
                                        }
                                    }
                                    
                                }
                            });
                    } else {
                        //classes ajax
                            $.ajax({
                                url: '<?php echo site_url('admin/classes_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b,

                                success: function(response) {

                                    if(classes_owe_now_rows > 0) {
                                        //current timestamp already exists

                                        $.ajax({ //trying to get the classes fee paid directly from the database
                                        url: '<?php echo site_url('admin/classes_fee_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + classes_fee_charged_b + '/' + 'yes',

                                            success: function(class_fee_paid) {

                                                if(att_status == 1) {
                                                    /*if(class_fee_paid == 0 || class_fee_paid == '') {

                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            
                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge
                                                                $('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response));
                                                            }
                                                        } else {

                                                            if(class_fee_paid == 0 && response == 0) { //if fee paid from the table is zero, add the fee owe from the table to the charge and...
                                                                $('#classes_owe_' + id).val((Number(response) + classes_fee_charged_b) - classes_paid_now);
                                                            } else {
                                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                            }
                                                        }
                                                    } else {
                                                        if(classes_paid_now == 0 || classes_paid_now == '') {
                                                            $('#classes_owe_' + id).val(Number(response) + classes_fee_charged_b);
                                                        } else {
                                                            $('#classes_owe_' + id).val((Number(response)  + classes_fee_charged_b) - classes_paid_now);
                                                        }
                                                    }*/

                                                    $('#classes_owe_' + id).val((fee_owe -classes_paid_now) + classes_fee_charged_b);

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');

                                                } else if(att_status == 2) {
                                                    $('#classes_paid_' + id).attr('readonly', 'true');
                                                    $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                                    $('#classes_paid_' + id).val(''); 
                                                    //$('#feeding_owe_' + id).val(0);
                                                    $('#classes_owe_' + id).val(Number(response));//subtract today's charge 

                                                    //enable the submit button
                                                    $('#submit_button').removeAttr('disabled');
                                                }
                                            }
                                        });

                                    } else {
                                        if(att_status == 1) {
                                            if(classes_paid_now == 0 || classes_paid_now == '') {
                                                $('#classes_owe_' + id).val(Number(response));

                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            } else {
                                                $('#classes_owe_' + id).val(Number(response) - classes_paid_now);
                                                //enable the submit button
                                                $('#submit_button').removeAttr('disabled');

                                            }
                                        } else if(att_status == 2) {
                                            $('#classes_paid_' + id).attr('readonly', 'true');
                                            $('#classes_paid_' + id).attr('placeholder', 'Not Available');
                                            $('#classes_paid_' + id).val(''); 
                                            //$('#feeding_owe_' + id).val(0);
                                            $('#classes_owe_' + id).val(Number(response));//subtract today's charge 

                                            //enable the submit button
                                            $('#submit_button').removeAttr('disabled');

                                        }
                                    }
                                    
                                }
                            });
                    }
                }
            });
        
        //hide notifier
       if(counter != undefined)  {
        if(counter == st_id_array.length -1) {
            $('#notifier').slideUp('slow');
        }
       } else {
        $('#notifier').slideUp('slow');
       }

      $('#modal_alert button').show();

        $('.close').click();
}

//updating transport owe when a student pays an amount
/*function student_paid_transport(id, counter) {

    //enable submit button
    $('#submit_button').removeAttr('disabled');

    
    var st_id_array = <?php //echo json_encode($st_id_array); ?>;
    var timestamp = <?php //echo $timestamp; ?>;

            var class_id = Number(<?php //echo $class_id; ?>);
            var previous_day_timestamp = Number(<?php //echo $previous_day_timestamp; ?>);
            var att_status = $('#status_' + id).val();

            //check if the current timestamp already exists in the feeding table
            let feeding_owe_now_rows = <?php //echo $feeding_owe_now->num_rows(); ?>;
            let fare_owe_now_rows = <?php //echo $fare_owe_now->num_rows(); ?>;

            //transport
            var fare_paid_now = Number($('#transport_paid_' + id).val());
            let transport_fare_charged = Number($('#transport_fare_charged_' + id).val());
            let fare_owe =  Number($('#transport_owe_' + id).val());

            //transport ajax
              *  $.ajax({
                    url: '<?php //echo site_url('admin/fare_owe_updator/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + transport_fare_charged + '/' + 'no',

                    success: function(response) {
                        if(feeding_owe_now_rows > 0 && fare_owe_now_rows > 0) {
                            //current timestamp already exists

                            $.ajax({ //trying to get the fare paid paid directly from the database
                            url: '<?php //echo site_url('admin/fare_paid/'); ?>' + id + '/' + class_id + '/' + previous_day_timestamp + '/' + timestamp + '/' + transport_fare_charged + '/' + 'yes',

                                success: function(t_paid) {

                                    if(att_status == 1) {
                                        if(t_paid == 0 || t_paid == '') {

                                            if(fare_paid_now == 0 || fare_paid_now == '') {
                                                
                                                if(t_paid == 0 && response == 0) { //if fare paid from the table is zero, add the fee owe from the table to the charge
                                                    $('#transport_owe_' + id).val(Number(response) + transport_fare_charged);
                                                } else {
                                                    $('#transport_owe_' + id).val(Number(response));
                                                }
                                            } else {

                                                if(t_paid == 0 && response == 0) { //if fare paid from the table is zero, add the fee owe from the table to the charge and...
                                                    $('#transport_owe_' + id).val((Number(response) + transport_fare_charged) - fare_paid_now);
                                                } else {
                                                    $('#transport_owe_' + id).val(Number(response) - fare_paid_now);
                                                }
                                            }
                                        } else {
                                            if(fare_paid_now == 0 || fare_paid_now == '') {
                                                $('#transport_owe_' + id).val(Number(response) + transport_fare_charged);
                                            } else {
                                                $('#transport_owe_' + id).val((Number(response)  + transport_fare_charged) - fare_paid_now);
                                            }
                                        }

                                        $('#transport_owe_' + id).val((t_paid -fare_paid_now) + transport_fare_charged);

                                    } else if(att_status == 2) {
                                        $('#feeding_paid_' + id).attr('readonly', 'true');
                                        $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                        $('#feeding_paid_' + id).val(''); 
                                        //$('#feeding_owe_' + id).val(0);
                                        

                                        $('#transport_paid_' + id).attr('readonly', 'true');
                                        $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                        $('#transport_paid_' + id).val('');
                                        $('#transport_owe_' + id).val(Number(response));//subtract today's charge 
                                    }
                                }
                            });

                        } else {
                            if(att_status == 1) {
                                if(fare_paid_now == 0 || fare_paid_now == '') {
                                    $('#transport_owe_' + id).val(Number(response));
                                } else {
                                    $('#transport_owe_' + id).val(Number(response) - fare_paid_now);
                                }
                            } else if(att_status == 2) {
                                $('#feeding_paid_' + id).attr('readonly', 'true');
                                $('#feeding_paid_' + id).attr('placeholder', 'Not Available');
                                $('#feeding_paid_' + id).val(''); 
                                //$('#feeding_owe_' + id).val(0);
                               

                                $('#transport_paid_' + id).attr('readonly', 'true');
                                $('#transport_paid_' + id).attr('placeholder', 'Not Available');
                                $('#transport_paid_' + id).val('');
                                $('#transport_owe_' + id).val(Number(response));//subtract today's charge 
                            }
                        }
                        
                    }
                });*

                $('#modal_alert button').show();

                            $('.close').click();

        
}*/

//check if attendance status field is empty
$('#attendance_form').submit(async function(event) {

    event.preventDefault();

    //check if user has internet access at the moment
    const hasInternet = await checkOnlineStatus();
    if(hasInternet == false) {

        $('#modal_alert .modal-content').css({
            marginTop: window.scrollY + 100 + 'px',
        });


        showAjaxModal_alert('<i class="fa fa-info-circle"></i> No internet. Check your internet connectivity!', 'Error');
        return false;
    } //checking for online is done


    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

    var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;

    for(var i = 0; i < st_id_array.length; i++) {
        let att_status = $('#status_' + st_id_array[i]).val();

        if(att_status == '' || att_status == null) {
            showAjaxModal_alert(
                'Some Students\' Attendance Status Is Unknown.<br><br>' +
                'Please Make Sure You Set The "Show Entries" Box At The Top Left Corner ' +
                'Of The Form To A Number More Than The Total Number Of Students In The Class, ' +
                'Then Mark Attendance Again.<br><br>' +
                'E.g. You Can Set It To 25, 50, 100 etc.<br>' +
                'Note: The Default Entry is 10.',
                'Warning',
                false,
                false
            );
            $('#empty_error').css('display', 'block');
            return false;
        } else {
            $('#empty_error').css('display', 'none');

        }
    }

    //verification of check-in
    verify_check_in();
});


function change_color() {
     var count = <?php echo count($attendance_of_students_array); ?>;
    var st_id_array = <?php echo json_encode($st_id_array); ?>;


    for(var i = 0; i < st_id_array.length; i++) {
        let att_status = $('#status_' + st_id_array[i]).val();

        //for feeding
        let feeding_owe =  Number($('#feeding_owe_' + st_id_array[i]).val());

        if(feeding_owe > 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(feeding_owe == 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(feeding_owe < 0) {
            $('#feeding_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }

        //for classes
        let classes_owe =  Number($('#classes_owe_' + st_id_array[i]).val());

        if(classes_owe > 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(classes_owe == 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(classes_owe < 0) {
            $('#classes_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }

        //transport
        /*let fare_owe =  Number($('#transport_owe_' + st_id_array[i]).val());

        if(fare_owe > 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': '#e73636', 'color': '#fff'});
        } else if(fare_owe == 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': 'green', 'color': '#fff'});
        } else if(fare_owe < 0) {
            $('#transport_owe_' + st_id_array[i]).css({'background-color': '#000', 'color': '#f76262'});
        }*/
    }
}


var class_selection = "";
$(function($) {
   // $('#submit').attr('disabled', 'disabled');
});

function select_section(class_id) {
        if (class_id !== '') {
        $.ajax({
            url: '<?php echo site_url('teacher/get_section/'); ?>' + class_id,
            success:function (response)
            {
                $('#section_holder').html(response);
            }
        });
    }
}

function select_students(class_id) {
    if(class_id !== ''){

        $.ajax({
            url: '<?php echo site_url('admin/get_multi_select_students/'); ?>' + class_id + '/<?=$student_id ?>',
            success:function (response)
            {

            jQuery('#students_holder').slideDown('slow');
            jQuery('#students_holder').html(response);
            }
        });

    }   else {
        jQuery('#students_holder').slideUp('slow');
    }
}
    function mark_all_present() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

        $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');
       // var count = <?php echo count($attendance_of_students_array); ?>;
       //enable all the fee selection buttons
       $('.sel_btns').removeAttr('disabled');

       //disable this button
        $('#present_btn').attr('disabled', 'disabled');
        //enable present button
        $('#absent_btn').removeAttr('disabled');

        let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
        let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
        let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);
        let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

        var st_id_array = <?php echo json_encode($st_id_array); ?>;

        for(var i = 0; i < st_id_array.length; i++) {
            $('#status_' + st_id_array[i]).val("1");
            //let transport_fare_charged = Number($('#transport_fare_charged_' + st_id_array[i]).val());
            is_student_present(st_id_array[i], '0', i);

            //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
            if(feeding_fee_charged == 0 || feeding_fee_charged == '' || feeding_fee_charged == null) {
                $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            }

            if(classes_fee_charged == 0 || classes_fee_charged == '' || classes_fee_charged == null) {
                $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            }


            //disable feeding, classes fee and transport fare input field if amount charge is 0 or empty
            if(feeding_fee_charged_b == 0 || feeding_fee_charged_b == '' || feeding_fee_charged_b == null) {
                $('#feeding_paid_' + st_id_array[i]).attr('readonly', 'true');
            }

            if(classes_fee_charged_b == 0 || classes_fee_charged_b == '' || classes_fee_charged_b == null) {
                $('#classes_paid_' + st_id_array[i]).attr('readonly', 'true');
            }

            /*if(transport_fare_charged == 0 || transport_fare_charged == '' || transport_fare_charged == null) {
                            $('#transport_paid_' + st_id_array[i]).attr('readonly', 'true');
            } *///end
        }

        $('#modal_alert button').show();

            $('.close').click();

        
    }

    function mark_all_absent() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

            $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

        //var count = <?php echo count($attendance_of_students_array); ?>;
        ////disable all the fee selection buttons
        $('.sel_btns').attr('disabled', 'disabled');

        //disable this button
        $('#absent_btn').attr('disabled', 'disabled');
        //enable present button
        $('#present_btn').removeAttr('disabled');

        var st_id_array = <?php echo json_encode($st_id_array); ?>;

        for(var i = 0; i < st_id_array.length; i++) {
            $('#status_' + st_id_array[i]).val("2");

            is_student_present(st_id_array[i], '0', i);
        }

        $('#modal_alert button').show();

            $('.close').click();
    }

    //feeding fee
    var counter = 0;
    function mark_all_paid_feeding() {

            $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

            $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');
       // var count = <?php echo count($attendance_of_students_array); ?>;
        var st_id_array = <?php echo json_encode($st_id_array); ?>;

        //feeding
        let feeding_fee_charged = Number(<?php echo $feeding_fee_charged; ?>);
        let feeding_fee_charged_b = Number(<?php echo $feeding_fee_charged_b; ?>);

        for(var i = 0; i < st_id_array.length; i++) {

            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + st_id_array[i],
                async: false,
                success: function(bf_response) {

                    if(bf_response == 'no') {
                        $('#feeding_paid_' + st_id_array[i]).val(feeding_fee_charged);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');

                    } else {
                        $('#feeding_paid_' + st_id_array[i]).val(feeding_fee_charged_b);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');
                    }
                }
            });


            
            var att_status = $('#status_' + st_id_array[i]).val();
            student_paid_feeding(st_id_array[i], i);

            if(att_status == '2') {
              is_student_present(st_id_array[i], '0', i);
            }
           
        }       

    }

    //feeding fee
    function mark_all_paid_classes() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

        $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

        /*showAjaxModal_alert('Please wait...<i class="fa fa-3x fa-spinner fa-pulse"></i>', 'Loading');
        $('.modal-dialog').css('marginTop', '200px');*/

       // var count = <?php //echo count($attendance_of_students_array); ?>;
        var st_id_array = <?php echo json_encode($st_id_array); ?>;

    //classes
    let classes_fee_charged = Number(<?php echo $classes_fee_charged; ?>);
    let classes_fee_charged_b = Number(<?php echo $classes_fee_charged_b; ?>);

        for(var i = 0; i < st_id_array.length; i++) {

            //let's see if this student is a beneficiary or not so we know which feeding and classes fee charges to use
            $.ajax({
                url: '<?php echo site_url('admin/beneficiary_checker/'); ?>' + st_id_array[i],
                async: false,
                success: function(bf_response) {
                    if(bf_response == 'no') {
                        $('#classes_paid_' + st_id_array[i]).val(classes_fee_charged);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');

                    } else {
                        $('#classes_paid_' + st_id_array[i]).val(classes_fee_charged_b);

                        //enable the submit button
                        $('#submit_button').removeAttr('disabled');

                    }
                }
            });

            
            var att_status = $('#status_' + st_id_array[i]).val();
            student_paid_classes(st_id_array[i], i);

            if(att_status == '2') {
              is_student_present(st_id_array[i], '0', i);
            }
           
        } 

        //$('.close').click();      
    }

     //feeding fee
    /*function mark_all_paid_transport() {

        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

        $('#modal_alert button').hide();
        $('#modal_alert').css({
            marginTop: '250px',

        });

        $('#modal_alert').modal({
            backdrop: 'static',
            keyboard: false,
 
        });

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; ">Updating, please wait...<i class="fa fa-spinner fa-pulse"></i></div></center>', 'Loading');

       // var count = <?php //echo count($attendance_of_students_array); ?>;
        var st_id_array = <?php //echo json_encode($st_id_array); ?>;

        for(var i = 0; i < st_id_array.length; i++) {
            let transport_fare_charged = Number($('#transport_fare_charged_' + st_id_array[i]).val());

            $('#transport_paid_' + st_id_array[i]).val(transport_fare_charged);
            var att_status = $('#status_' + st_id_array[i]).val();
            student_paid_transport(st_id_array[i], i);

            if(att_status == '2') {
              is_student_present(st_id_array[i], '0', i);
            }
        }        
    }*/

function check_validation(){
    if(class_selection !== ''){
        $('#submit').removeAttr('disabled')
    }
    else{
        $('#submit').attr('disabled', 'disabled');
    }
}

$('#class_selection').change(function(){
    class_selection = $('#class_selection').val();
    check_validation();
});

$('#time_picker').change(function() {
    $('#submit').removeAttr('disabled');
});




//selecting student for attendance
/*$('#att_selector_form').submit(function(event) {

    let item_checked = $('.check').filter(':checked').length;
    if(item_checked < 1) {

        event.preventDefault();

        //Scroll to the top
          $('html, body').animate({
              scrollTop: ($('#top').offset().top )
          }, 1000); 

        showAjaxModal_alert('No student was selected!', 'Error');
        return false;
    }
});
*/

//ajax
$('#att_selector_form').submit(function(event) {
    /* Act on the event */

    event.preventDefault();

    let item_checked = $('.check').filter(':checked').length;
    if(item_checked < 1) {
        showAjaxModal_alert('No student was selected!', 'Error');
        return false;
    }

    $('#main_page').empty();

    //SHOW LOADER
  $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px; ">Fetching Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');


    $.ajax({
      url: '<?php echo site_url('admin/attendance_selector/'); ?>',
      type: 'POST',
      dataType: 'html',
      data: new FormData(this),
      cache: false,
      contentType: false,
      processData: false
  })
  .done(function(data) {
    if(data == 'promotion error term') {
        showAjaxModal_alert('Make sure students were promoted during the previous term. For further assistance, kindly contact the system administrator.', 'Error');

        navigation('<?php echo site_url('admin/manage_attendance'); ?>');
      
    } else if(data == 'promotion error sem') {
        showAjaxModal_alert('Make sure students were promoted during the previous semester. For further assistance, kindly contact the system administrator.', 'Error');

        navigation('<?php echo site_url('admin/manage_attendance'); ?>')
      
    } else {
        $('#main_page').empty();

        //$('#main_page').html(data);
        navigation(data);

        $('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 
        
    }
  });
});
</script>
