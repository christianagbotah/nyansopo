<table class="table table-responsive table-bordered datatable">
    <thead>
        <tr>
            <th><div><?php echo get_phrase('iD_no');?></div></th>
            <th><div><?php echo get_phrase('photo');?></div></th>
            <th><div><?php echo get_phrase('name');?></div></th>
            <th><div><?php echo get_phrase('class');?></div></th>
            <th class="span3"><div><?php echo get_phrase('address');?></div></th>
            <th><div><?php echo get_phrase('Contact');?></div></th>
            <th><div><?php echo get_phrase('Guardian Contact');?></div></th>
            <th><div><?php echo get_phrase('options');?></div></th>
        </tr>
    </thead>
    <tbody>

        <?php
        if($rows != 'none'):
            foreach($request_query as $row):

                //get running year and term
                    $running_year = $this->db->get_where('attendance' , array('timestamp' => $timestamp))->row()->year;

                    //get class and section ids from the enroll table
                    $class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0', 'year' => $running_year))->row()->class_id;
                    $section_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0', 'year' => $running_year))->row()->section_id;


                    //getting class info
                    $class_name   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
                    $class_name_numeric   = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;

                    //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = $section_name;
                    }

                    $full_class_name = $class_name.' '.$class_name_numeric.' '.$sec_name;

        ?>
        <tr>
            <td><?php echo $this->db->get_where('student' , array(
                    'student_id' => $row['student_id']
                ))->row()->student_code;?></td>
            <?php    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;
                     
                    $parent_id = $this->db->get_where('student' , array(
                        'student_id' => $row['student_id']
                    ))->row()->parent_id;
                ?>
            <td><img src="<?php echo $this->crud_model->get_image_url('student',$row['student_id'], $gender);?>" class="img-circle" width="30" /></td>
            <td>
                <?php
                    echo $this->db->get_where('student' , array(
                        'student_id' => $row['student_id']
                    ))->row()->name;
                ?>
            </td>

            <td>
                <?php
                    echo $full_class_name;
                ?>
            </td>

            <td>
                <?php
                    echo $this->db->get_where('student' , array(
                        'student_id' => $row['student_id']
                    ))->row()->address;
                ?>
            </td>
            <td>
               <a href="tel:<?php  echo $this->db->get_where('student' , array(
                        'student_id' => $row['student_id']
                    ))->row()->phone;?>" target="_blank"> <?php
                    echo $this->db->get_where('student' , array(
                        'student_id' => $row['student_id']
                    ))->row()->phone;
                ?></a>
            </td>

            <td>
                
               <a href="tel:<?php  echo $this->db->get_where('parent' , array(
                        'parent_id' => $parent_id
                    ))->row()->phone;?>" target="_blank"> <?php
                    echo $this->db->get_where('parent' , array(
                        'parent_id' => $parent_id
                    ))->row()->phone;
                ?></a>
            </td>

            <td>

                <div class="btn-group">
                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                        Action <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-default pull-right" role="menu">
                        
                        <!-- SMS LINK -->
                        <li>
                            <a href="<?php echo site_url('admin/message/sms_send?si='.$row['student_id']); ?>" class="pt_link" onclick="check_sms_status()" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>
                                <?php echo get_phrase('SMS_student'); ?> 
                            </a>
                        </li>

                        <li class="divider"></li>
                        <li>
                            <a href="<?php echo site_url('admin/message/sms_send?pi=parent_'.$parent_id); ?>" class="pt_link" onclick="check_sms_status()" style="color: magenta;"><i class="glyphicon glyphicon-envelope"></i>
                                <?php echo get_phrase('SMS_guardian'); ?> 
                            </a>
                        </li>

                        <li class="divider"></li>

                        <!-- STUDENT PROFILE LINK -->
                        <li>
                            <a href="<?php echo site_url('admin/student_profile/'.$row['student_id']);?>" style='color: #0029ff;'>
                                <i class="entypo-user"></i>
                                    <?php echo get_phrase('profile');?>
                                </a>
                        </li>
                        <li class="divider"></li>

                        <!-- STUDENT EDITING LINK -->
                        <li>
                            <a href="#" style='color: red;' onclick="cancel_scanning('<?=$row['student_id']?>', '<?=$category?>', '<?=$timestamp?>')">
                                <i class="entypo-cancel"></i>
                                    <?php echo get_phrase('cancel');?>
                                </a>
                        </li>
                    </ul>
                </div>

            </td>
        </tr>

        <?php
            endforeach;

        else:
            echo '<tr><td style="color: red; text-align: center" colspan="7">No Record Found!</td></tr>';
        endif;
        ?>
    </tbody>
</table>

<script type="text/javascript">
    
    jQuery(document).ready(function($) {
        $('.datatable').DataTable();
    });


    //cancelling scanning
    function cancel_scanning(student_id, cancel_type, timestamp_cancel) {

        let text = '';

        if(cancel_type == 'check_in') {
            text = 'CHECKED-IN';

        } else if(cancel_type == 'check_out') {
            text = 'CHECKED-OUT';

        } else if(cancel_type == 'feeding') {
            text = 'FEEDING FEE';
            
        } else if(cancel_type == 'classes') {
            text = 'CLASSES FEE';
            
        } else if(cancel_type == 'transport') {
            text = 'TRANSPORT FARE';
            
        }

        $.ajax({
        url: '<?php echo site_url('admin/barcode_scanner/cancel/') ?>' + student_id + '/' + cancel_type + '/' + timestamp_cancel,
        type: 'POST',
        dataType: 'json',
      })
      .done(function(response) {
        showAjaxModal_alert(text + ' FOR ' + response.student_name + ' HAS BEEN CANCELLED SUCCESSFULLY!.', 'Success');

        //reload page
        setTimeout(() => {
          window.location.reload();
        }, 3000);
      })
      .fail(function(err) {
        showAjaxModal_alert('ERROR: ' + err.responseText, 'Error');
      });
    }


</script>