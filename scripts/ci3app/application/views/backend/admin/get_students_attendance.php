<?php

    $running_year       =   $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
    $running_term       =   $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
    $running_sem       =   $this->db->get_where('settings' , array('type'=>'running_sem'))->row()->description;

    //total attendance marked on this date
    $this->db->where('mute', '0');
    
    // Only filter by status if it's provided and valid
    if($att_status != 'undefined' && $att_status != '' && $att_status !== null) {
        $this->db->where('status', $att_status);
    }
    
    $att_query = $this->db->get_where('attendance', array('timestamp' => $timestamp, 'status !=' => '0'));

    $counter = 0;

    if($att_query->num_rows() > 0):
        //getting those not marked yet
        $att_marked_array = $this->db->get_where('attendance', array('timestamp' => $timestamp, 'status !=' => '0'))->result_array();
        $students_ids = array_column($att_marked_array, 'student_id');
        $selected_year = $att_query->row()->year;
        $selected_term = $att_query->row()->term;
        
        //total attendance not marked
        $this->db->where('year', $selected_year);
        $this->db->where('term', $selected_term);
        $this->db->where_not_in('student_id', $students_ids);
        $this->db->where('mute', '0');
        $not_marked = $this->db->get_where('enroll');

        if($att_status == '0' || $att_status === 0) {
            $att_query = $not_marked;
            $att_array = $not_marked->result_array();
        }

        $counter++;
    endif;

    ?>
    <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <table class="table table-bordered table-hover table-striped table-active" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;">
              <thead>
                  <tr>
                    <th width="220" style="text-align: right"><strong style="color: green; font-size: 16px">PRESENT:</strong></th>
                      <th width="" style="text-align: left; font-size: 16px"><strong><?= $this->db->get_where('attendance', array('timestamp' => $timestamp, 'status' => 1))->num_rows()?$this->db->get_where('attendance', array('timestamp' => $timestamp, 'status' => 1))->num_rows():0; ?></strong>

                        <div class="pull-right p-1 rounded-lg ring-1 ring-sky-300 hover:ring-2 hover:bg-sky-200"><a href="#" class="btn btn-outline-info" onclick="loadAttendanceData('1')">View <i class="entypo-list"></i></a></div>

                      </th>
                  </tr>
                  <tr>
                    <th style="text-align: right"><strong style="color: red; font-size: 16px">ABSENT:</strong></th>
                      <th width="" style="text-align: left; font-size: 16px"><strong><?= $this->db->get_where('attendance', array('timestamp' => $timestamp, 'status' => 2))->num_rows()?$this->db->get_where('attendance', array('timestamp' => $timestamp, 'status' => 2))->num_rows(): 0; ?></strong>

                        <div class="pull-right p-1 rounded-lg ring-1 hover:ring-2 hover:bg-red-200"><a href="#" class="btn btn-outline-danger" onclick="loadAttendanceData('2')">View <i class="entypo-list"></i></a></div>
                      </th>
                  </tr>

                  <tr>
                    <th style="text-align: right"><strong style="font-size: 16px">NOT MARKED:</strong></th>
                      <th width="" style="text-align: left; font-size: 16px"><strong><?=$counter > 0 ? $not_marked->num_rows() : 0; ?></strong>

                        <div class="pull-right p-1 rounded-lg ring-1 hover:ring-2 hover:bg-green-200"><a href="#" class="btn btn-outline-secondary" onclick="loadAttendanceData('0')">View <i class="entypo-list"></i></a></div>
                      </th>
                  </tr>


                  
              </thead>
            </table>

            </div>
            <div class="col-md-4"></div>
        </div>
        <div id="table_holder">
        <?php


    
    if($att_query->num_rows() > 0):
        $att_array = $att_query->result_array();

        ?>
        
        
        <caption><?php echo get_phrase('all_students\'_attendance');?></caption>
        <table class="table table-bordered table-responsive table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" id="attendance_print">
            <thead>
                <tr>
                    <th align="center" width="80"><div align="center"><?php echo get_phrase('iD_no');?></div></th>
                    <th width="80"><div align="center"><?php echo get_phrase('photo');?></div></th>
                    <th><div><?php echo get_phrase('name');?></div></th>
                    <th><div><?php echo get_phrase('attendance_status');?></div></th>
                    <th width="180"><div><?php echo get_phrase('address');?></div></th>
                    <th><div><?php echo get_phrase('class');?></div></th>
                    <th><div><?php echo get_phrase('parent_name');?></div></th>
                    <th><div><?php echo get_phrase('contact');?></div></th>
                    
                </tr>
            </thead>
            <tbody>
        <?php


    if($att_query->num_rows() > 0):
        
        foreach($att_array as $att_row):

            if($att_status != 0) { //only if attendance status is not 0
                $att_status = $att_row['status'];
            }
            
            $query = $this->db->get_where('student', array('student_id' => $att_row['student_id']));
            if ($query->num_rows() > 0):
                $students = $query->result_array();
                foreach ($students as $row):?>

                    <?php
                        $class_id   =   $this->db->get_where('enroll' , array('student_id'=>$row['student_id'] , 'year' => $running_year))->row()->class_id;


                        $section_id   =   $this->db->get_where('enroll' , array('student_id'=>$row['student_id'], 'class_id' => $class_id, 'year' => $running_year))->row()->section_id;

                            $student_ids   =   $this->db->get_where('enroll' , array('year' => $running_year))->result_array();
                            

                            foreach($student_ids as $s_id){

                            if($s_id['student_id'] == $row['student_id']) {
                                $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex; ?>
                            <tr>
                                <td><?php echo $this->db->get_where('student' , array(
                                        'student_id' => $row['student_id']
                                    ))->row()->student_code;?></td>
                                <td><img src="<?php echo $this->crud_model->get_image_url('student',$row['student_id'], $gender);?>" class="img-circle" width="40" /></td>
                                <td class="al">
                                    <?php
                                        echo $this->db->get_where('student' , array(
                                            'student_id' => $row['student_id']
                                        ))->row()->name;
                                    ?>
                                </td>
                                <td class="al">
                                    <?php
                                        if($att_status == 1) {
                                            echo '<strong style="color: green;">Present</strong>';
                                        } else if($att_status == 2) {
                                            echo '<strong style="color: red;">Absent</strong>';
                                        } else if($att_status == 0) {
                                            echo '<strong style="color: grey;">Not marked</strong>';
                                        }
                                    ?>
                                </td>
                                <td class="al">
                                    <?php
                                        echo $this->db->get_where('student' , array(
                                            'student_id' => $row['student_id']
                                        ))->row()->address;
                                    ?>
                                </td>
                                <td class="al">
                                    <?php
                                        echo $this->db->get_where('class' , array('class_id' => $class_id
                                        ))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id
                                        ))->row()->name_numeric.$this->db->get_where('section' , array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;;
                                    ?>
                                </td>   
                                <td class="al">
                                    <?php
                                    $parent_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->parent_id;
                                        echo $this->db->get_where('parent' , array(
                                            'parent_id' => $parent_id
                                        ))->row()->name;
                                    ?>
                                </td> 
                                <td class="al">
                                    <?php
                                    $parent_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->parent_id;
                                        echo $this->db->get_where('parent' , array(
                                            'parent_id' => $parent_id
                                        ))->row()->phone;
                                    ?>
                                </td> 
                        
                            </tr>

                                <?php

                                break;
                            }

                            }

                            ?>

                    <?php endforeach;?>
                    <?php endif;
                endforeach;
        endif; ?>
            </tbody>
        </table>
    
    <?php
        elseif($att_status == 1 && $counter > 0):
            echo '<h5 class="text-red-400 text-center">No attendance was marked yet</h5>';

        elseif($att_status == 2 && $counter > 0):
            echo '<h5 class="text-red-400 text-center">No student marked absent</h5>';

        elseif($att_status == 0 && $counter > 0):
            echo '<h5 class="text-red-400 text-center">No record found! Attendance was taken for all students.</h5>';

        else:
            echo '<h5 class="text-red-400 text-center">No record found! Attendance not taken yet.</h5>';
        endif;
    ?>

    </div>