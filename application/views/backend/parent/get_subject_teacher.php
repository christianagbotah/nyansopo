
<?php
    $parent_id = $this->session->userdata('parent_id');
    $this->db->select('*');
    $this->db->from('student');
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


    $this->db->where('year', $filtered_year);
    $this->db->where('term', $filtered_term);
    $this->db->where_in('student_id', $data_array);
    $query_rows = $this->db->get('enroll')->num_rows();

    if($query_rows == 1) {
        $ward = 'child';
    }else if($query_rows > 1) {
        $ward = 'children';
    }

?>
<hr />
<div class="row">
    <div class="col-md-12">

        <!------CONTROL TABS START------>
        <ul class="nav nav-tabs bordered">
            <li class="active">
                <a href="#list" data-toggle="tab"><i class="entypo-users"></i>
                    <?php echo get_phrase('subject_teacher');?>
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
                            <th><div><?php echo get_phrase('subject_teacher');?></div></th>
                            <th><div><?php echo get_phrase('phone');?></div></th>
                            <th><div><?php echo get_phrase('email');?></div></th>
                            <th><div><?php echo get_phrase('subject');?></div></th>
                            <th><div><?php echo get_phrase('class');?></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                if($query_rows != 0) { 
                    $f = 0;
                    for($st = 0; $st < sizeof($data_array); $st++):

                          

                        //number of class masters/subject teachers
                        $this->db->where('year', $filtered_year);
                        $this->db->where('term', $filtered_term);
                        $this->db->where('student_id', $data_array[$st]);
                        $class_id = $this->db->get('enroll')->row()->class_id;

                        //search through the class table for the teachers ids that match with the class ids
                        $this->db->select('teacher_id');
                        $this->db->from('class');
                        $this->db->distinct();
                        $this->db->where('class_id', $class_id);
                        $t_ids = $this->db->get()->result_array(); 

                        $h = 0;
                        $teachers_ids_array = array();
                        foreach($t_ids as $row3) {
                            $teachers_ids_array[$h] = $row3['teacher_id'];
                            $h++;
                        }

                        //search through the teacher table for the teachers ids that match with the teacher ids
                        if(sizeof($teachers_ids_array) > 0) {
                            $this->db->where_in('teacher_id', $teachers_ids_array);
                            $n_teachers = $this->db->get('teacher');
                            $teachers_a = $n_teachers->result_array(); 

                            


                            //subjects
                            $this->db->where('year', $filtered_year);
                            $this->db->where('term', $filtered_term);
                            $this->db->where('class_id', $class_id);
                            $this->db->where_in('teacher_id', $teachers_ids_array);
                            $subject_array = $this->db->get('subject')->result_array();
                        }
                        

                        //Student's row as a heading
                        ?>
                        <tr>
                            <td colspan="6">
                                <strong>
                                    <?=$this->db->get_where('student', ['student_id' => $data_array[$st]])->row()->name; ?>
                                </strong>
                            </td>
                        </tr>

                        <?php
                        if(count($subject_array) > 0):
                            foreach($subject_array as $row2) {
                            $gender = $this->db->get_where('teacher', array('teacher_id' => $row2['teacher_id']))->row()->sex;
                            ?>
                            
                            <tr>
                                <td><img src="<?php echo $this->crud_model->get_image_url('teacher',$row2['teacher_id'], $gender);?>" class="img-circle" width="30" /></td>
                                <td><?php echo $this->db->get_where('teacher', array('teacher_id' => $row2['teacher_id']))->row()->name;?></td>
                                <td><?php echo $this->db->get_where('teacher', array('teacher_id' => $row2['teacher_id']))->row()->phone;?></td>
                                <td><a href="mailto:<?php echo $this->db->get_where('teacher', array('teacher_id' => $row2['teacher_id']))->row()->email;?>" target="_blank"><?php echo $this->db->get_where('teacher', array('teacher_id' => $row2['teacher_id']))->row()->email;?></a></td>

                                <td>
                                    <?php
                                       echo $row2['name'];
                                    ?>
                                    
                                </td>

                                <td>
                                    <?php
                                       echo $this->db->get_where('class', array('teacher_id' => $row2['teacher_id']))->row()->name.' '. $this->db->get_where('class', array('teacher_id' => $row2['teacher_id']))->row()->name_numeric;

                                        

                                    ?>
                                    
                                    </td>
                                </tr>
                                <?php 
                              
                            };
                        else:
                            ?>
                            <tr>
                                <td colspan="6"><strong class="text-danger">No record found for this student!</strong></td>
                            </tr>
                            <?php
                        endif;
                           
                    endfor;//each student
                }//end of checking if we have any student for this parent
                else {
                    ?>
                    <tr>
                        <td colspan="6"><strong style="color: red;">No child found!</strong></td>
                    </tr>
                    <?php
                }
                    ?>
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