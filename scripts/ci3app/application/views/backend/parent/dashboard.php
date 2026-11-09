<?php 
    $parent_id = $this->session->userdata('parent_id');
    $this->db->select('student_id');
    $this->db->distinct();
    $this->db->from('student');
    $this->db->where('mute', '0');
    $this->db->where('parent_id', $parent_id);
    $sql = $this->db->get();
    $query = $sql->result_array();

   
    $this->db->select('student_id');
    $this->db->distinct();
    $this->db->from('student');
    $this->db->where('mute', '1');
    $this->db->where('parent_id', $parent_id);
    $inactive_sql = $this->db->get();
    $inactive_query = $inactive_sql->num_rows();

    $rows = $sql->num_rows();

        $data_array = array();
        $active_array = array();

        $i = 0;
        foreach($query as $row) {
                $data_array[$i] = $row['student_id'];
                $active_array[$i] = $row['student_id'];
                $i++;
        }

        foreach($inactive_sql->result_array() as $row) {
                $data_array[$i] = $row['student_id'];
                $i++;
        }


        $query_rows = sizeof($active_array);

        $all_query_rows = sizeof($data_array);
        
    if($all_query_rows != 0) {    
        if($all_query_rows == 1) {
            $ward_2 = 'your_child';
            $ward_3 = 'Child';
        }else if($all_query_rows > 1) {
            $ward_2 = 'your_children';
            $ward_3 = 'Children';
        }

        //number of class masters/subject teachers
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where_in('student_id', $active_array);
        $class_ids = $this->db->get('enroll')->result_array();

        if(count($class_ids) > 0) {
            $j = 0;
            $class_id_array = array();
            foreach($class_ids as $row2) {
                $class_id_array[$j] = $row2['class_id'];
                $j++;
            }
            //search through the class table for the teachers ids that match with the class ids
            $this->db->select('teacher_id');
            $this->db->from('class');
            $this->db->distinct();
            $this->db->where_in('class_id', $class_id_array);
            $t_ids = $this->db->get()->result_array(); 

            $h = 0;
            $teachers_ids_array = array();
            foreach($t_ids as $row3) {
                $teachers_ids_array[$h] = $row3['teacher_id'];
                $h++;
            }

            //search through the teacher table for the teachers ids that match with the teacher ids
            $this->db->select('teacher_id');
            $this->db->from('teacher');
            $this->db->where_in('teacher_id', $teachers_ids_array);
            $n_teachers = $this->db->get()->num_rows(); 

             if($n_teachers == 1) {
                $form_master = 'Class Master';
            }else if($n_teachers > 1) {
                $form_master = 'Class Masters';
            } else {
                $form_master = 'No Class Master Found';
            }

            
                //subject teachers
                $this->db->select('teacher_id');
                $this->db->distinct();
                $this->db->from('subject');
                $this->db->where('year', $running_year);
                $this->db->where('term', $running_term);
                $this->db->where_in('teacher_id', $teachers_ids_array);
                $this->db->where_in('class_id', $class_id_array);
                $subj_rows = $this->db->get()->num_rows();

                if($subj_rows == 1) {
                    $s_teacher = 'teacher';
                }else if($subj_rows > 1) {
                    $s_teacher = 'teachers';
                }

            } else {
                $n_teachers = 0;
                $form_master = 'No Class Master Found';
                $s_teacher = 'teacher';

            }
        
    }


    $this->db->where('parent_id', $parent_id);
    $allChildren = $this->db->get('student')->result_array();
    
?>
<hr>
<div class="row">
	<div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading"> <h4>General Information:</h4></div>
        </div>
		<div class="row">

            <a href="#" onclick="allChildren()">
            <div class="col-md-3">

                <div class="tile-stats tile-red">
                    <div class="icon"><i class="fa fa-group"></i></div>
                        <div class="form-group row">
                            <div class="num col-md-6 col-lg-6 col-sm-6 col-xs-6" data-start="0" data-end="<?=$query_rows;?>"
                            data-postfix="" data-duration="1500" data-delay="0">0 </div>
                            <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6" style="font-size: 38px; font-weight: bold; color: #fff;"><?=$inactive_query;?></div>

                            <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6 text-white">Active</div>
                            <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6 text-white">Inactive</div>
                        </div>

                   <?php if($query_rows != 0) {    ?>
                    <h3><?php echo get_phrase($ward_2);?></h3>
                   
                   <?php } else { ?>
                    <h3><?php echo get_phrase('children');?></h3>
                  
                     <?php
                    } ?>
                </div>

            </div>
        </a>

        <a href="<?php echo site_url('parents/class_master');?>">
            <div class="col-md-3">

                <div class="tile-stats tile-green">
                    <div class="icon"><i class="entypo-users"></i></div>
                    <div class="num" data-start="0" data-end="<?php echo $n_teachers;?>"
                    		data-postfix="" data-duration="800" data-delay="0">0</div>
                    <?php if($query_rows != 0) {    ?>
                    <h3><?php echo $form_master; ?></h3>
                   <p><?php echo $form_master; ?></p>
               <?php } else { ?>
                <h3><?php echo 'Form Master'; ?></h3>
                <p><?php echo 'Form Master'; ?></p>

                <?php
               } ?>
                </div>

            </div>
        </a>

        <a href="<?php echo site_url('parents/subject_teacher');?>">
            <div class="col-md-3">

                <div class="tile-stats tile-aqua">
                    <div class="icon"><i class="entypo-user"></i></div>
                    <div class="num" data-start="0" data-end="<?php echo $subj_rows;?>"
                    		data-postfix="" data-duration="500" data-delay="0">0</div>
                    <?php if($query_rows != 0) {    ?>
                    <h3><?php echo get_phrase('subject_'.$s_teacher);?></h3>
                   <p>Total subject <?php echo get_phrase($s_teacher); ?></p>
                   <?php } else { ?>

                    <h3><?php echo get_phrase('subject_teacher');?></h3>
                    <p>Total subject <?php echo get_phrase('teacher'); ?></p>
                      <?php
               } ?>
                </div>

            </div>
        </a>
            <div class="col-md-3">

                <div class="tile-stats tile-blue">
                    <div class="icon"><i class="entypo-chart-bar"></i></div>
                    <?php
                    $ward = 'Total Attendance Today';
                        $this->db->where('timestamp', strtotime(date('Y-m-d')));
                        $this->db->where('status', '1');
                        $this->db->where('year', $running_year);
                        $this->db->where('term', $running_term);
                        $this->db->where_in('student_id', $active_array);
                        $att_array = $this->db->get('attendance');
                        $present_today = $att_array->num_rows();

                        if($present_today == 1) {
                            $ward = 'Child present today';
                        }else if($present_today > 1) {
                            $ward = 'Total children present today';
                        }

						?>
                    <div class="num" data-start="0" data-end="<?php echo $present_today;?>"
                    		data-postfix="" data-duration="500" data-delay="0">0</div>
                    <div class="pull-right" style="position: absolute; top: 0px; right: 5px;">
                        <?php 
                            foreach($att_array->result_array() as $st_pr):
                        ?>

                            <p><small style="color: #ffffff;"><?php echo $this->db->get_where('student', array('student_id' => $st_pr['student_id']))->row()->name; ?></small></p>

                    <?php endforeach; ?>
                    </div>

                    <h3><?php echo get_phrase('attendance');?></h3>
                   <p><?php echo $ward; ?></p>
                </div>

            </div>
    	</div>
         <hr>
    </div>

   
    <div class="col-md-12">
        <div class="row">
            <!-- CALENDAR-->
            <div class="col-md-12 col-xs-12">
                <div class="panel panel-info " data-collapsed="0">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="fa fa-calendar"></i>
                            <?php echo get_phrase('event_schedule');?>
                        </div>
                    </div>
                    <div class="panel-body" style="padding:0px;">
                        <div class="calendar-env">
                            <div class="calendar-body">
                                <div id="notice_calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



    <script>

  $(document).ready(function() {

	  var calendar = $('#notice_calendar');

				$('#notice_calendar').fullCalendar({
					header: {
						left: 'title',
						right: 'today prev,next'
					},

					//defaultView: 'basicWeek',

					editable: false,
					firstDay: 1,
					height: 530,
					droppable: false,

					events: [
						<?php
						$notices	=	$this->db->get('noticeboard')->result_array();
						foreach($notices as $row):
						?>
						{
							title: "<?php echo $row['notice_title'];?>",
							start: new Date(<?php echo date('Y',$row['create_timestamp']);?>, <?php echo date('m',$row['create_timestamp'])-1;?>, <?php echo date('d',$row['create_timestamp']);?>),
							end:	new Date(<?php echo date('Y',$row['create_timestamp']);?>, <?php echo date('m',$row['create_timestamp'])-1;?>, <?php echo date('d',$row['create_timestamp']);?>)
						},
						<?php
						endforeach
						?>

                        //birthday-students
                        <?php
                        $students_bd  = $this->db->get_where('student', array('parent_id' => $this->session->userdata('parent_id')))->result_array();
                        foreach($students_bd as $row_s):
                        ?>
                        {
                          title: ("<?php echo $row_s['name'].'\'s birthday';?>").toLocaleUpperCase(),
                          start: new Date(<?php echo date('Y', time());?>, <?php echo date('m',strtotime($row_s['birthday']))-1;?>, <?php echo date('d',strtotime($row_s['birthday']));?>),
                          end:  new Date(<?php echo date('Y', time());?>, <?php echo date('m',strtotime($row_s['birthday']))-1;?>, <?php echo date('d',strtotime($row_s['birthday']));?>)
                        },
                        <?php
                        endforeach
                        ?>

                        //birthday-teacher
                        <?php
                        $this->db->where_in('teacher_id', $teachers_ids_array);
                        $teachers_bd  = $this->db->get('teacher')->result_array();
                        foreach($teachers_bd as $row_t):
                        ?>
                        {
                          title: ("<?php echo $row_t['name'].'\'s birthday';?>").toLocaleUpperCase(),
                          start: new Date(<?php echo date('Y', time());?>, <?php echo date('m',strtotime($row_t['birthday']))-1;?>, <?php echo date('d',strtotime($row_t['birthday']));?>),
                          end:  new Date(<?php echo date('Y', time());?>, <?php echo date('m',strtotime($row_t['birthday']))-1;?>, <?php echo date('d',strtotime($row_t['birthday']));?>)
                        },
                        <?php
                        endforeach
                        ?>

					]
				});
	});

  //load all children
  function allChildren() {
    let url = '<?php echo site_url('parents/children_list');?>';
    let data = <?php echo json_encode($allChildren); ?>;

    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'html',
        data: {'data': data}
    })
    .done(function(resp) {
        showAjaxModalDisplay(resp, 'Success');
    })
    .fail(function(err) {
        showAjaxModal_alert(err.responseText, 'Error');
    })
  }
  </script>
