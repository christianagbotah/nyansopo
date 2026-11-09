<?php 

        $this->db->select('student_id');
        $this->db->from('enroll');
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $raw_info = $this->db->get();
        $st_ids =  $raw_info->result_array();

        if($raw_info->num_rows() != 0) {
            $st_ids_array = array();
            $i = 0;
            foreach($st_ids as $row) {
                $st_ids_array[$i] = $row['student_id'];
                $i++;
            }

            $this->db->select('parent_id');
            $this->db->distinct();
            $this->db->from('student');
            $this->db->where_in('student_id', $st_ids_array);
            $pt_ids = $this->db->get()->result_array();

            $pt_ids_array = array();
            $j = 0;
            foreach($pt_ids as $row2) {
                $pt_ids_array[$j] = $row2['parent_id'];
                $j++;
            }
        } else {
            $pt_ids_array = array(0);
        }

        
?>

<hr>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading"> <h4>General Information:</h4></div>
        </div>
        <div class="row">
            <div class="col-md-3">

                <div class="tile-stats tile-red">
                    <div class="icon"><i class="fa fa-group"></i></div>
                    <div class="num" data-start="0" data-end="<?php echo $this->db->get_where('enroll', array('year' => $running_year, 'term' => $running_term))->num_rows();?>"
                            data-postfix="" data-duration="1500" data-delay="0">0</div>

                    <h3><?php echo get_phrase('active_students');?></h3>
                   <p>Total Active students</p>
                </div>

            </div>
            <div class="col-md-3">

                <div class="tile-stats tile-green">
                    <div class="icon"><i class="entypo-users"></i></div>
                    <div class="num" data-start="0" data-end="<?php echo $this->db->count_all('teacher');?>"
                            data-postfix="" data-duration="800" data-delay="0">0</div>

                    <h3><?php echo get_phrase('teachers');?></h3>
                   <p>Total Teachers</p>
                </div>

            </div>
            <div class="col-md-3">

                <div class="tile-stats tile-aqua">
                    <div class="icon"><i class="entypo-user"></i></div>
                    <div class="num" data-start="0" data-end="<?php 
                    $this->db->where_in('parent_id', $pt_ids_array);
                    $all_active_parents = $this->db->get('parent')->num_rows();
                    echo $all_active_parents;?>"
                            data-postfix="" data-duration="500" data-delay="0">0</div>

                    <h3><?php echo get_phrase('active_parents');?></h3>
                   <p>Total Active Parents</p>
                </div>

            </div>
            <div class="col-md-3">

                <div class="tile-stats tile-blue">
                    <div class="icon"><i class="entypo-chart-bar"></i></div>
                    <?php
                            $check   =   array(  'timestamp' => strtotime(date('Y-m-d')) , 'status' => '1' );
                        $query = $this->db->get_where('attendance' , $check);
                        $present_today      =   $query->num_rows();
                        ?>
                    <div class="num" data-start="0" data-end="<?php echo $present_today;?>"
                            data-postfix="" data-duration="500" data-delay="0">0</div>

                    <h3><?php echo get_phrase('attendance');?></h3>
                   <p>Total students present today</p>
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
                        $notices    =   $this->db->get('noticeboard')->result_array();
                        foreach($notices as $row):
                        ?>
                        {
                            title: "<?php echo $row['notice_title'];?>",
                            start: new Date(<?php echo date('Y',$row['create_timestamp']);?>, <?php echo date('m',$row['create_timestamp'])-1;?>, <?php echo date('d',$row['create_timestamp']);?>),
                            end:    new Date(<?php echo date('Y',$row['create_timestamp']);?>, <?php echo date('m',$row['create_timestamp'])-1;?>, <?php echo date('d',$row['create_timestamp']);?>)
                        },
                        <?php
                        endforeach
                        ?>

                         //birthday-students
                        <?php
                        $students_bd  = $this->db->get('student')->result_array();
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
  </script>
