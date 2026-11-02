<?php 

    //student code with Alpha-numric
    //generate student id 
    $student_code_prefix      = $this->db->get_where('settings', array('type'=>'student_code_prefix'))->row()->description;
    $student_code       = $this->db->get_where('settings', array('type'=>'student_id_format'))->row()->description;


    $this->db->select('student_code');
    $this->db->order_by('student_code', 'desc');
    $this->db->limit(1);
    $st_query = $this->db->get('student');
    $st_id    = $st_query->row()->student_code;

    if($s_query->num_rows() > 0) {
        //extract the numeric out and increase it by 1
        $first_num = ''; //the first occurence of a number after the string STAFF-....
        $position_of_first_num = ''; //the position of the first number

        $i = 0;
        for($i=0; $i<strlen($st_id); $i++) {
            if(is_numeric($st_id[$i])) {
                $first_num = $st_id[$i];
                break; 
            }
        }

        //find the position
        $position_of_first_num = strpos($st_id, $first_num);

        //now let's do the extraction
        $n_stid = substr($st_id, $position_of_first_num, strlen($st_id) - $i);

        $row[1]       = $n_stid + 1;
        
        if($first_num == 0) {
            $old_len = strlen($st_id);
            $new_len = strlen($student_code);
            $act_len = ($old_len - $new_len);
            $student_code = substr($st_id, 0, $act_len).$student_code;
            echo $student_code;
        }else {
            $student_code = $student_code;
            echo $student_code;
        }
    }else{
        $student_code       = $student_code_prefix.$student_code;
        echo $student_code;
    }

 ?>