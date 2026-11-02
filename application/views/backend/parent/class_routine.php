<?php 
    $child_of_parent = $this->db->get_where('enroll' , array(
        'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
    ))->result_array();

if(count($child_of_parent) > 0):
    foreach ($child_of_parent as $row):
        $class_id = $row['class_id'];
        $section_id = $row['section_id'];
?>
<hr />
<div class="label label-primary pull-right" style="font-size: 14px; font-weight: 100;">
    <i class="entypo-user"></i> <?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
</div>
<br><br>
<div class="row">
    
    <div class="col-md-12">

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading" >
                <div class="panel-title" style="font-size: 16px; color: white; text-align: center;">
                    <?php echo $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;?> : 
                    <?php echo get_phrase('section');?> - <?php echo $this->db->get_where('section' , array('section_id' => $row['section_id']))->row()->name;?>
                    <a href="<?php echo site_url('parents/class_routine_print_view/'.$class_id.'/'.$row['section_id'].'/'.$student_id);?>" 
                        class="btn btn-primary btn-xs pull-right" target="_blank">
                            <i class="entypo-print"></i> <?php echo get_phrase('print');?>
                    </a>
                </div>
            </div>
            <div class="panel-body">
                
                <table cellpadding="0" cellspacing="0" border="0"  class="table table-bordered">
                    <tbody>
                        <?php 
                        for($d=1;$d<=7;$d++):
                        
                        if($d==1)$day='sunday';
                        else if($d==2)$day='monday';
                        else if($d==3)$day='tuesday';
                        else if($d==4)$day='wednesday';
                        else if($d==5)$day='thursday';
                        else if($d==6)$day='friday';
                        else if($d==7)$day='saturday';
                        ?>
                        <tr class="gradeA">
                            
                            <td width="100"><?php echo strtoupper($day);?></td>
                            <td>
                                <?php
                                $this->db->order_by("time_start", "asc");
                                $this->db->where('day' , $day);
                                $this->db->where('class_id' , $class_id);
                                $this->db->where('section_id' , $section_id);
                                $this->db->where('year' , $running_year);
                                $this->db->where('term' , $running_term);
                                $routines   =   $this->db->get('class_routine');
                                $routines_array   =  $routines->result_array();

                                if(count($routines_array) > 0):
                                    foreach($routines_array as $row2):
                                    ?>
                                    <div class="btn-group">
                                        <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                            <?php echo $this->crud_model->get_subject_name_by_id($row2['subject_id']);?>
                                            <?php
                                                if ($row2['time_start_min'] == 0 && $row2['time_end_min'] == 0) 
                                                    echo '('.$row2['time_start'].'-'.$row2['time_end'].')';
                                                if ($row2['time_start_min'] != 0 || $row2['time_end_min'] != 0)
                                                    echo '('.$row2['time_start'].':'.$row2['time_start_min'].'-'.$row2['time_end'].':'.$row2['time_end_min'].')';
                                            ?>
                                            <span class="caret"></span>
                                        </button>
                                    </div>
                                    <?php endforeach;?>

                                    <?php
                                        else:
                                            ?>
                                            <tr>
                                                <td colspan="2">
                                                    <div class="alert alert-danger">
                                                        <strong>No record found for this day!</strong>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        endif;
                                    ?>

                            </td>
                            
                        </tr>
                        <?php endfor;?>
                        
                    </tbody>
                </table>
                
            </div>
        </div>

    </div>

</div>


<?php endforeach;?>

<?php 
    else:
        ?>
        <div class="container">
            <div class="row">
                <div class="alert alert-danger">
                    <strong>No child found for the current session!</strong>
                </div>
            </div>
        </div>
        <?php
    endif;
?>
