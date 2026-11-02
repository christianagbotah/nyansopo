<?php
    $parent_id = $this->session->userdata('parent_id');
    $this->db->select('*');
    $this->db->from('student');
    $this->db->where('mute', '0');
    $this->db->where('parent_id', $parent_id);
    $sql = $this->db->get();
    $query = $sql->result_array();

    $rows = $sql->num_rows();

        $data_array = array();
        $i = 0;
        foreach($query as $row) {
                $data_array[$i] = $row['student_id'];
                $i++;
        }

        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where('mute', '0');
        $this->db->where_in('student_id', $data_array);
        $query_rows = $this->db->get('enroll')->num_rows();
    if($query_rows != 0) {   

        //number of class masters/subject teachers
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where('mute', '0');
        $this->db->where_in('student_id', $data_array);
        $class_ids = $this->db->get('enroll')->result_array();

        $j = 0;
        $class_id_array = array();
        foreach($class_ids as $roww) {
            $class_id_array[$j] = $roww['class_id'];
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
        $this->db->where_in('teacher_id', $teachers_ids_array);
        $n_teachers = $this->db->get('teacher');
        $teachers_a = $n_teachers->result_array(); 

        if($query_rows == 1) {
            $ward = 'child';
        }else if($query_rows > 1) {
            $ward = 'children';
        }

        //get children's classes
        $this->db->where_in('teacher_id', $teachers_ids_array);
        $n_class = $this->db->get('class');
        $class_a = $n_class->result_array();

        $ic = 0;
        $c_array = array();
        foreach($class_a as $row_c) {
            $c_array[$ic] = $row_c['class_id'];
            $ic++;
        }

        //search enroll table
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where('mute', '0');
        $this->db->from('enroll');
        $this->db->where_in('class_id', $c_array);
        $st_id = $this->db->get()->result();

        $real_ids = array();
        $lag_counter = 0;
        for($tt = 0; $tt < count($st_id); $tt++) {
            for($p = 0; $p < count($data_array); $p++) {
                if($st_id[$tt]->student_id == $data_array[$p]) {
                $real_ids[$lag_counter] = $st_id[$tt]->student_id;

                $lag_counter++; 
                }
            }
        }
    }


?>
<hr />
<div class="row">
    <div class="col-md-12">

        <!------CONTROL TABS START------>
        <ul class="nav nav-tabs bordered">
            <li class="active">
                <a href="#list" data-toggle="tab"><i class="entypo-users"></i>
                    <?php echo get_phrase('class_masters');?>
                        </a></li>
        </ul>
        <!------CONTROL TABS END------>
        <div class="tab-content">
            <!----TABLE LISTING STARTS-->
            <div class="tab-pane box active" id="list">

                <table  class="table table-bordered datatable" id="table_export">
                    <thead>
                        <tr>
                            <th><div><?php echo get_phrase('photo');?></div></th>
                            <th><div><?php echo get_phrase('class_master');?></div></th>
                            <th><div><?php echo get_phrase('phone');?></div></th>
                            <th><div><?php echo get_phrase('email');?></div></th>
                           <!-- <th><div><?php echo get_phrase($ward);?></div></th>-->
                            <th><div><?php echo get_phrase('class');?></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $std = 0;
                            foreach($teachers_a as $row2):
                        $gender = $this->db->get_where('teacher', array('teacher_id' => $row2['teacher_id']))->row()->sex;
                        ?>
                        <tr>
                            <td><img src="<?php echo $this->crud_model->get_image_url('teacher',$row2['teacher_id'], $gender);?>" class="img-circle" width="30" /></td>
                            <td><?php echo $row2['name'];?></td>
                            <td><?php echo $row2['phone'];?></td>
                            <td><a href="mailto:<?php echo $row2['email'] ;?>" target="_blank"><?php echo $row2['email'] ;?></a></td>

                           <!-- <td>
                                <?php
                                   
                                ?>
                                
                            </td>-->

                            <td>
                                <?php
                                   echo $this->db->get_where('class', array('teacher_id' => $row2['teacher_id']))->row()->name.' '. $this->db->get_where('class', array('teacher_id' => $row2['teacher_id']))->row()->name_numeric;

                                    

                                ?>
                                
                            </td>
                        </tr>
                        <?php 
                        $std++;
                    endforeach;?>
                    </tbody>
                </table>
            </div>
            <?php 
                function test_st_id($st_val) {
                    if($st_val == '' || $st_val == null) {
                        test_st_id();
                    }
                }
            ?>
            <!----TABLE LISTING ENDS-->


        </div>
    </div>
</div>


