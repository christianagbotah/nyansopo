<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Crud Model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status for all CRUD operations.
 * All INSERT, UPDATE, and DELETE operations will now automatically:
 * - Set sync_status = 'PENDING'
 * - Update last_modified_at timestamp
 * - Set device_id
 * - Track last_modified_by user
 * 
 * This ensures that any changes made offline or online will be properly synced.
 */
class Crud_model extends MY_Model {

    function __construct() {
        parent::__construct();
    }

    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    function get_type_name_by_id($type = '', $type_id = '', $field = 'name') {
        if ($type_id != null && $type_id != 0){
            $result = $this->db->get_where($type, array($type.'_id' => $type_id))->row();
            return $result ? $result->$field : '';
        }
        return '';
    }

    ////////STUDENT/////////////
    function get_students($class_id) {
        $year = $this->crud_model->get_settings('running_year');
        $term = $this->crud_model->get_settings('running_term');

        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $query = $this->db->get_where('enroll', array('class_id' => $class_id));
        return $query->result_array();
    }

    function get_student_info($student_id) {
        $query = $this->db->get_where('student', array('student_id' => $student_id));
        return $query->result_array();
    }

    function get_student_info_by_id($student_id) {
        $query = $this->db->get_where('student', array('student_id' => $student_id))->row_array();
        return $query;
    }

    function getStudentInfoById($student_id) {
        $query = $this->db->get_where('student', array('student_id' => $student_id))->row();
        return $query;
    }

    /*Admin*/
    function getAdminInfoById($admin_id) {
        $query = $this->db->get_where('admin', array('admin_id' => $admin_id))->row();
        return $query;
    }

    function get_admins() {
        $query = $this->db->get('admin');
        return $query->result_array();
    }

    /////////TEACHER/////////////
    function get_teachers() {
        $query = $this->db->get('teacher');
        return $query->result_array();
    }

    function get_teacher_name($teacher_id) {
        $query = $this->db->get_where('teacher', array('teacher_id' => $teacher_id));
        $res = $query->result_array();
        foreach ($res as $row)
            return $row['name'];
    }

    function get_teacher_info($teacher_id) {
        $query = $this->db->get_where('teacher', array('teacher_id' => $teacher_id));
        return $query->result_array();
    }

    function getStaffInfo($table, $staff_code) {
        // FIX: Map 'administrator' to 'admin' table name
        $actual_table = ($table === 'administrator') ? 'admin' : $table;
        
        // Determine the correct column name for the staff code based on table
        $code_column = '';
        if ($actual_table == 'teacher') {
            $code_column = 'teacher_code';
        } elseif ($actual_table == 'admin') {
            $code_column = 'admin_code';
        } elseif ($actual_table == 'non_teaching_staff') {
            $code_column = 'staff_code';
        } else {
            // Fallback to table_code pattern
            $code_column = $actual_table . '_code';
        }
        
        // Join with pension_tier2_providers to get provider details
        $this->db->select($actual_table . '.*, pension_tier2_providers.provider_name, pension_tier2_providers.provider_code');
        $this->db->from($actual_table);
        $this->db->join('pension_tier2_providers', $actual_table . '.tier2_provider_id = pension_tier2_providers.provider_id', 'left');
        $this->db->where($actual_table . '.' . $code_column, $staff_code);
        
        return $this->db->get()->row();
    }

    function getStaffInfoById($table, $staff_id) {
        // FIX: Map 'administrator' to 'admin' table name
        $actual_table = ($table === 'administrator') ? 'admin' : $table;
        
        // Determine the correct column name for the staff ID based on table
        $id_column = '';
        if ($actual_table == 'teacher') {
            $id_column = 'teacher_id';
        } elseif ($actual_table == 'admin') {
            $id_column = 'admin_id';
        } elseif ($actual_table == 'non_teaching_staff') {
            $id_column = 'staff_id';
        } else {
            // Fallback to table_id pattern
            $id_column = $actual_table . '_id';
        }
        
        // Join with pension_tier2_providers to get provider details
        $this->db->select($actual_table . '.*, pension_tier2_providers.provider_name, pension_tier2_providers.provider_code');
        $this->db->from($actual_table);
        $this->db->join('pension_tier2_providers', $actual_table . '.tier2_provider_id = pension_tier2_providers.provider_id', 'left');
        $this->db->where($actual_table . '.' . $id_column, $staff_id);
        
        return $this->db->get()->row();
    }
    
    /**
     * Task 17.1: Optimized staff selection query with JOIN
     * 
     * Fetches staff information for multiple payroll records in a single query,
     * avoiding N+1 query problem. Includes staff name, code, and department.
     * 
     * @param array $payroll_records Array of payroll records with employee_code and employment_category
     * @return array Staff information keyed by "{employment_category}_{employee_code}"
     */
    function get_staff_info_batch($payroll_records) {
        if (empty($payroll_records)) {
            return [];
        }
        
        // Group records by employment category
        $staff_by_category = [
            'teacher' => [],
            'administrator' => [],
            'non_teaching_staff' => []
        ];
        
        foreach ($payroll_records as $record) {
            $category = $record['employment_category'];
            $code = $record['employee_code'];
            
            if (isset($staff_by_category[$category]) && !in_array($code, $staff_by_category[$category])) {
                $staff_by_category[$category][] = $code;
            }
        }
        
        $staff_info = [];
        
        // Fetch teachers in single query
        if (!empty($staff_by_category['teacher'])) {
            $this->db->select('teacher.*, pension_tier2_providers.provider_name, pension_tier2_providers.provider_code, "teacher" as employment_category');
            $this->db->from('teacher');
            $this->db->join('pension_tier2_providers', 'teacher.tier2_provider_id = pension_tier2_providers.provider_id', 'left');
            $this->db->where_in('teacher.teacher_code', $staff_by_category['teacher']);
            $teachers = $this->db->get()->result();
            
            foreach ($teachers as $teacher) {
                $key = 'teacher_' . $teacher->teacher_code;
                $staff_info[$key] = $teacher;
            }
        }
        
        // Fetch administrators in single query
        if (!empty($staff_by_category['administrator'])) {
            $this->db->select('admin.*, pension_tier2_providers.provider_name, pension_tier2_providers.provider_code, "administrator" as employment_category');
            $this->db->from('admin');
            $this->db->join('pension_tier2_providers', 'admin.tier2_provider_id = pension_tier2_providers.provider_id', 'left');
            $this->db->where_in('admin.admin_code', $staff_by_category['administrator']);
            $admins = $this->db->get()->result();
            
            foreach ($admins as $admin) {
                $key = 'admin_' . $admin->admin_code;
                $staff_info[$key] = $admin;
            }
        }
        
        // Fetch non-teaching staff in single query
        if (!empty($staff_by_category['non_teaching_staff']) && $this->db->table_exists('non_teaching_staff')) {
            $this->db->select('non_teaching_staff.*, pension_tier2_providers.provider_name, pension_tier2_providers.provider_code, "non_teaching_staff" as employment_category');
            $this->db->from('non_teaching_staff');
            $this->db->join('pension_tier2_providers', 'non_teaching_staff.tier2_provider_id = pension_tier2_providers.provider_id', 'left');
            $this->db->where_in('non_teaching_staff.staff_code', $staff_by_category['non_teaching_staff']);
            $non_teaching = $this->db->get()->result();
            
            foreach ($non_teaching as $staff) {
                $key = 'non_teaching_staff_' . $staff->staff_code;
                $staff_info[$key] = $staff;
            }
        }
        
        return $staff_info;
    } 


    //////////////get parent's info////
    function get_guardian_data($parent_id) {

        $dataQueryRow = $this->db->get_where('parent', ['parent_id' => $parent_id])->row();
        return $dataQueryRow;
    }

    //////////SUBJECT/////////////
    function get_subjects() {
        $query = $this->db->get('subject');
        return $query->result_array();
    }

    function get_subject_info($subject_id) {
        $query = $this->db->get_where('subject', array('subject_id' => $subject_id));
        return $query->result_array();
    }

    function get_subjects_by_class($class_id) {

    		$class_name = $this->get_class_name($class_id);

        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        $running_sem = $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description;

        if($class_name == 'JHSS') {
        	$query = $this->db->get_where('subject', array('class_id' => $class_id, 'year' => $running_year, 'sem' => $running_sem));
        } else {
        	$query = $this->db->get_where('subject', array('class_id' => $class_id, 'year' => $running_year, 'term' => $running_term));
        }
        
        return $query->result_array();
    }

    function get_subject_name_by_id($subject_id) {
        if(empty($subject_id)) {
            return '';
        }
        
        $query = $this->db->get_where('subject', array('subject_id' => $subject_id))->row();
        return $query ? $query->name : '';
    }

    ////////////CLASS & SECTION///////////
    function get_class_name($class_id) {
        if(empty($class_id)) {
            return '';
        }
        
        $query = $this->db->get_where('class', array('class_id' => $class_id));
        $res = $query->result_array();
        foreach ($res as $row)
            return $row['name'];
        return '';
    }

    function get_class_section($class_id) {
        
        $query = $this->db->get_where('section', array('class_id' => $class_id));
        $res = $query->result_array();
        foreach ($res as $row)
            return $row['name'];
    }

    function get_class_name_numeric($class_id) {
        $query = $this->db->get_where('class', array('class_id' => $class_id));
        $res = $query->result_array();
        foreach ($res as $row)
            return $row['name_numeric'];
    }

    function getFullClassName($class_id) {
        $className = $this->crud_model->get_class_name($class_id);
        $classNameNumeric = $this->crud_model->get_class_name_numeric($class_id);
        $sectionName = $this->crud_model->get_class_section($class_id);

        $classFullName = $className.' '.$classNameNumeric.' '.$sectionName;

        return $classFullName;
    }

    function get_classes() {
        $query = $this->db->get('class');
        return $query->result_array();
    }

    function get_class_info($class_id) {
        $query = $this->db->get_where('class', array('class_id' => $class_id));
        return $query->result_array();
    }

    function get_class_teacher_id($class_id) {
        $query = $this->db->get_where('class', array('class_id' => $class_id));
        $res = $query->result_array();
        foreach ($res as $row)
            return $row['teacher_id'];
    }

    function getStudentCurrentClassIdByStudentId($student_id) {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        $this->db->where('class_id IS NOT NULL');
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->order_by('enroll_id', 'asc');
        $this->db->limit(1);
        $class_id = $this->db->get_where('enroll', ['student_id' => $student_id])->first_row()->class_id;

        return $class_id;
    }

    function getStudentClassId($student_id, $year, $term) {
        // Get running year for comparison
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        
        // First, try to get enrollment for the specific year and term
        $this->db->where('class_id IS NOT NULL');
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $this->db->where('student_id', $student_id);
        $this->db->order_by('enroll_id', 'desc');
        $this->db->limit(1);
        $enrollment = $this->db->get('enroll')->row();
        
        // If enrollment exists for this specific year/term, use it
        if ($enrollment) {
            return $enrollment->class_id;
        }
        
        // No enrollment found for this year/term
        // Check if this is a DIFFERENT academic year (not the running year)
        if ($year != $running_year) {
            // This is a different academic year - student MUST be promoted/enrolled first
            // Return null to indicate enrollment is required
            return null;
        }
        
        // This is the running year but no enrollment for this term yet
        // Use the most recent enrollment record (student stays in same class)
        $this->db->where('class_id IS NOT NULL');
        $this->db->where('student_id', $student_id);
        $this->db->order_by('enroll_id', 'desc');
        $this->db->limit(1);
        $recent_enrollment = $this->db->get('enroll')->row();
        
        if ($recent_enrollment) {
            return $recent_enrollment->class_id;
        }
        
        // No enrollment found at all
        return null;
    }

    function getStudentLastEnrollmentRow($student_id) {

        $this->db->where('class_id IS NOT NULL');
        $this->db->order_by('enroll_id', 'desc');
        $this->db->limit(1);
        $enrollment_row = $this->db->get_where('enroll', ['student_id' => $student_id])->first_row();

        return $enrollment_row;
    }

    //////////EXAMS/////////////
    function get_exams() {
        $running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
    	$running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
    	$running_sem = $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description;

    		$this->db->where('year', $running_year);
    		$this->db->where('term', $running_term);
    		$this->db->or_where('sem', $running_sem);
        $query = $this->db->get('exam');
        return $query->result_array();
    }

    //get all the exams ids for this particular student
    function get_student_exams($student_id) {
    	$this->db->select('exam_id');
    	$this->db->distinct();
    	$this->db->where('student_id', $student_id);
    	$query = $this->db->get('mark');

    	return $query->result_array();
    }

    function get_exams_name($exam_id = '') {
        if(empty($exam_id)) {
            return '';
        }
        
    		$this->db->where('exam_id', $exam_id);
        $query = $this->db->get('exam');
        $result = $query->row();
        
        return $result ? $result->name : '';
    }

    function get_exams_year($exam_id = '') {
        if(empty($exam_id)) {
            return '';
        }
        
    		$this->db->where('exam_id', $exam_id);
        $query = $this->db->get('exam');
        $result = $query->row();
        
        return $result ? $result->year : '';
    }

    function get_exams_term($exam_id = '') {
        if(empty($exam_id)) {
            return '';
        }
        
    		$this->db->where('exam_id', $exam_id);
        $query = $this->db->get('exam');
        $result = $query->row();
        
        return $result ? $result->term : '';
    }

    function get_exams_sem($exam_id = '') {
        if(empty($exam_id)) {
            return '';
        }
        
    		$this->db->where('exam_id', $exam_id);
        $query = $this->db->get('exam');
        $result = $query->row();
        
        return $result ? $result->sem : '';
    }

    function get_exams_class_id($exam_id = '', $student_id = '') {

            $this->db->where('exam_id', $exam_id);
            $this->db->where('student_id', $student_id);
            $this->db->limit(1);
            $query = $this->db->get('mark');
            return $query->row()->class_id;
    }

    function get_exams_name_for_results($exam_id = '') {
    		$this->db->where('exam_id', $exam_id);
        $query = $this->db->get('exam');
        return $query->row()->name;
    }

    function get_exam_info($exam_id) {
        $query = $this->db->get_where('exam', array('exam_id' => $exam_id));
        return $query->result_array();
    }

    //////////GRADES/////////////
    function get_grade_level($grade_point) {

        if($grade_point == 'A') {
            $grade_level = 'LEVEL 1 - ' . $grade_point;

        } else if($grade_point == 'P') {
            $grade_level = 'LEVEL 2 - ' . $grade_point;

        } else if($grade_point == 'AP') {
            $grade_level = 'LEVEL 3 - ' . $grade_point;

        } else if($grade_point == 'D') {
            $grade_level = 'LEVEL 4 - ' . $grade_point;
            
        } else if($grade_point == 'B') {
            $grade_level = 'LEVEL 5 - ' .$grade_point;
            
        }

        return $grade_level;
             
    }

    function get_grades() {
        $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;

        if($terminal_report_style == 'style_1') {
            $query = $this->db->get('grade');
        } else {
            $query = $this->db->get('grade_2');
        }
        
        return $query->result_array();
    }

    function get_grade_info($grade_id) {

        $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;

        if($terminal_report_style == 'style_1') {
            $query = $this->db->get_where('grade', array('grade_id' => $grade_id));
        } else {
            $query = $this->db->get_where('grade_2', array('grade_id' => $grade_id));
        }
        
        
        return $query->result_array();
    }

     function get_class_score( $exam_id , $class_id , $subject_id , $student_id) {
        $marks = $this->db->get_where('mark' , array(
                                    'subject_id' => $subject_id,
                                        'exam_id' => $exam_id,
                                            'class_id' => $class_id,
                                                'student_id' => $student_id))->result_array();

        foreach ($marks as $row) {
            echo $row['class_score'];
        }
    }

    function get_exam_score( $exam_id , $class_id , $subject_id , $student_id) {
        $marks = $this->db->get_where('mark' , array(
                                    'subject_id' => $subject_id,
                                        'exam_id' => $exam_id,
                                            'class_id' => $class_id,
                                                'student_id' => $student_id))->result_array();

        foreach ($marks as $row) {
            echo $row['exam_score'];
        }
    }

    function get_obtained_marks( $exam_id , $class_id , $subject_id , $student_id) {
        $marks = $this->db->get_where('mark' , array(
                                    'subject_id' => $subject_id,
                                        'exam_id' => $exam_id,
                                            'class_id' => $class_id,
                                                'student_id' => $student_id))->result_array();

        foreach ($marks as $row) {
            echo $row['mark_obtained'];
        }
    }

    function get_twobest_scores( $exam_id , $class_id , $section_id, $student_id, $year, $sem, $status) {
       $this->db->where('exam_id', $exam_id);
       $this->db->where('class_id', $class_id);
       $this->db->where('section_id', $section_id);
       $this->db->where('student_id', $student_id);
       $this->db->where('year', $year);
       $this->db->where('sem', $sem);
       $this->db->where('status', $status);
      //$this->db->limit(2);
       //$this->db->order_by("mark_obtained", "desc");
       
       $marks = $this->db->get('mark')->result_array();

        foreach ($marks as $row) {
            return $row['mark_obtained'];
        }
    }

    function create_barcode($student_id)
    {

        $barcode = $this->Barcode_model->create_barcode($student_id);
        echo $barcode;



    }


    function get_total_score( $exam_id , $class_id , $subject_id, $student_id, $running_year, $running_term = '') {

    		 $class_name = $this->get_class_name($class_id);
    		 if($class_name == 'JHSS') {

    		 		$data['data1'] = $this->db->get_where('mark' , array(
                                    'subject_id' => $subject_id,
                                        'exam_id' => $exam_id,
                                            'year' => $running_year,
                                                'sem' => $running_term,
                                                    'class_id' => $class_id,
                                                        'student_id' => $student_id))->result_array();


		        $this->db->where('exam_id' , $exam_id);
		        $this->db->where('class_id' , $class_id);
		        $this->db->where('subject_id' , $subject_id);
		        $this->db->where('year' , $running_year);
		        $this->db->where('sem' , $running_term);
		        $this->db->order_by("mark_obtained", "desc");
		        $data['data2'] = $this->db->get('mark')->result_array();
		                                                    
		        if($data['data2'] &&  $data['data1']){

		            
		            $position; //The real position of each mark
		            
		            $old_score = 0; //Acts as the the first or previous total score for each subject
		            $rank = 1; //Ranks the positions of each mark in each subject
		            $counter = 0; //Keeps track of the number of marks in each subjects
		            $tracker = 1; //Tracks the ranks whenever $old_score == $total_score
		           

		            foreach ($data['data1'] as $row1) {
		                foreach ($data['data2'] as $row2) {

		                    $total_score =$row2['mark_obtained'];
		                    $counter++;

		                    if($old_score != $total_score){ //checks if old_score == total_score
		                        $diff = $counter - $rank; //finds the difference btn counter and rank
		                        if($tracker == $diff){
		                            $rank++; //increases the rank by one anytime tracker == diff
		                        }else{
		                            $rank = $counter;
		                        }
		                        
		                    }else{
		                        $tracker++; //Tracks the ranks whenever $old_score == $total_score
		                    }

		                    $old_score = $total_score; //Reassign the value of the old_score

		                    $i = $rank % 10;
		                    $j = $rank % 100;

		                    if($i == 1 && $j != 11){
		                        $nth_postion = 'ST';
		                    }elseif($i == 2 && $j != 12){
		                        $nth_postion = 'ND';
		                    }elseif($i == 3 && $j != 13){
		                        $nth_postion = 'RD';
		                    }else{
		                         $nth_postion = 'TH'; //Assigns the nth position to the ranks
		                    }
		                    
		                   
		                    $position = ' '.$rank.$nth_postion.' ';
		 

		                    //compare marks
		                    if($row1['mark_obtained'] == $row2['mark_obtained'] && $row2['student_id'] == $student_id){
		                      
		                            if($row1['mark_obtained'] == NULL || $row2['mark_obtained'] == NULL) {
		                                echo "";
		                            } else {
		                                echo $position;
		                            }
		                        
		                       
		                    }


		                }
		             
		            }

		        } //end of JHS

    		 } else {

    		 		$data['data1'] = $this->db->get_where('mark' , array(
                                    'subject_id' => $subject_id,
                                        'exam_id' => $exam_id,
                                            'year' => $running_year,
                                                'term' => $running_term,
                                                    'class_id' => $class_id,
                                                        'student_id' => $student_id))->result_array();


		        $this->db->where('exam_id' , $exam_id);
		        $this->db->where('class_id' , $class_id);
		        $this->db->where('subject_id' , $subject_id);
		        $this->db->where('year' , $running_year);
		        $this->db->where('term' , $running_term);
		        $this->db->order_by("mark_obtained", "desc");
		        $data['data2'] = $this->db->get('mark')->result_array();
		                                                    
		        if($data['data2'] &&  $data['data1']){

		            
		            $position; //The real position of each mark
		            
		            $old_score = 0; //Acts as the the first or previous total score for each subject
		            $rank = 1; //Ranks the positions of each mark in each subject
		            $counter = 0; //Keeps track of the number of marks in each subjects
		            $tracker = 1; //Tracks the ranks whenever $old_score == $total_score
		           

		            foreach ($data['data1'] as $row1) {
		                foreach ($data['data2'] as $row2) {

		                    $total_score =$row2['mark_obtained'];
		                    $counter++;

		                    if($old_score != $total_score){ //checks if old_score == total_score
		                        $diff = $counter - $rank; //finds the difference btn counter and rank
		                        if($tracker == $diff){
		                            $rank++; //increases the rank by one anytime tracker == diff
		                        }else{
		                            $rank = $counter;
		                        }
		                        
		                    }else{
		                        $tracker++; //Tracks the ranks whenever $old_score == $total_score
		                    }

		                    $old_score = $total_score; //Reassign the value of the old_score

		                    $i = $rank % 10;
		                    $j = $rank % 100;

		                    if($i == 1 && $j != 11){
		                        $nth_postion = 'ST';
		                    }elseif($i == 2 && $j != 12){
		                        $nth_postion = 'ND';
		                    }elseif($i == 3 && $j != 13){
		                        $nth_postion = 'RD';
		                    }else{
		                         $nth_postion = 'TH'; //Assigns the nth position to the ranks
		                    }
		                    
		                   
		                    $position = ' '.$rank.$nth_postion.' ';
		 

		                    //compare marks
		                    if($row1['mark_obtained'] == $row2['mark_obtained'] && $row2['student_id'] == $student_id){
		                            if($row1['mark_obtained'] == NULL || $row2['mark_obtained'] == NULL) {
		                                echo "";
		                            } else {
		                                echo $position;
		                            }
		                            
		                        
		                       
		                    }


		                }
		             
		            }

		        } //end of non JHS

    		 }
         
    }


    function get_aggregate_marks( $exam_id , $class_id , $section_id, $student_id, $running_year, $running_term = '') {
       $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
       $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;

       //$raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
       
        if($raw_score == 'Yes') {
                
             //marks for each student
            $data['data1'] = $this->db->get_where('aggregation' , array(
                                        'section_id' => $section_id,
                                            'exam_id' => $exam_id,
                                                'class_id' => $class_id,
                                                    'year' => $running_year,
                                                    'sem' => $running_term,
                                                        'student_id' => $student_id))->result_array();

                //marks for all students
                $this->db->where('exam_id' , $exam_id);
                $this->db->where('class_id' , $class_id);
                $this->db->where('section_id' , $section_id);
                $this->db->where('year' , $running_year);
                $this->db->where('sem' , $running_term);
                $this->db->order_by("aggregate_mark", "desc");
                $data['data2'] = $this->db->get('aggregation')->result_array();
                                                                
                    if($data['data2'] && $data['data1']){

                        $position; //The real position of each mark
                        
                        $old_score = 0; //Acts as the the first or previous total aggregate for each subject
                        $rank = 1; //Ranks the positions of each mark in each subject
                        $counter = 0; //Keeps track of the number of marks in each subjects
                        $tracker = 1; //Tracks the ranks whenever $old_score == $total_aggregate
                       

                        foreach ($data['data1'] as $row1) {
                            foreach ($data['data2'] as $row2) {

                                $total_score =$row2['aggregate_mark'];
                                $counter++;

                                if($old_score != $total_score){ //checks if old_score == total_aggregate
                                    $diff = $counter - $rank; //finds the difference btn counter and rank
                                    if($tracker == $diff){
                                        $rank++; //increases the rank by one anytime tracker == diff
                                    }else{
                                        $rank = $counter;
                                    }
                                    
                                }else{
                                    $tracker++; //Tracks the ranks whenever $old_score == $total_aggregate
                                }

                                $old_score = $total_score; //Reassign the value of the old_score

                                $i = $rank % 10;
                                $j = $rank % 100;

                                if($i == 1 && $j != 11){
                                    $nth_postion = 'ST';
                                }elseif($i == 2 && $j != 12){
                                    $nth_postion = 'ND';
                                }elseif($i == 3 && $j != 13){
                                    $nth_postion = 'RD';
                                }else{
                                     $nth_postion = 'TH'; //Assigns the nth position to the other ranks
                                }
                                

                                $position = ' '.$rank.$nth_postion.' ';
             

                                //compare marks
                                if($row1['aggregate_mark'] == $row2['aggregate_mark'] && $row2['student_id'] == $student_id){
                                  
                                        echo $position;
                                    
                                   
                                }
                            }
                        }   

                    }

            }
        elseif($raw_score == 'No') {

        		if($class_name == 'JHSS'){

        			//for JHS
        			//marks for each student
            $data['data1'] = $this->db->get_where('aggregation' , array(
                                        'section_id' => $section_id,
                                            'exam_id' => $exam_id,
                                                'class_id' => $class_id,
                                                'year' => $running_year,
                                                    'sem' => $running_term,
                                                        'student_id' => $student_id))->result_array();

                //marks for all students
                $this->db->where('exam_id' , $exam_id);
                $this->db->where('class_id' , $class_id);
                $this->db->where('section_id' , $section_id);
                $this->db->where('year' , $running_year);
                $this->db->where('sem' , $running_term);
                $this->db->order_by("aggregate_mark", "desc");
                $data['data2'] = $this->db->get('aggregation')->result_array();
                                                                
                    if($data['data2'] && $data['data1']){

                        $position; //The real position of each mark
                        
                        $old_score = 0; //Acts as the the first or previous total aggregate for each subject
                        $rank = 1; //Ranks the positions of each mark in each subject
                        $counter = 0; //Keeps track of the number of marks in each subjects
                        $tracker = 1; //Tracks the ranks whenever $old_score == $total_aggregate
                       

                        foreach ($data['data1'] as $row1) {
                            foreach ($data['data2'] as $row2) {

                                $total_score =$row2['aggregate_mark'];
                                $counter++;

                                if($old_score != $total_score){ //checks if old_score == total_aggregate
                                    $diff = $counter - $rank; //finds the difference btn counter and rank
                                    if($tracker == $diff){
                                        $rank++; //increases the rank by one anytime tracker == diff
                                    }else{
                                        $rank = $counter;
                                    }
                                    
                                }else{
                                    $tracker++; //Tracks the ranks whenever $old_score == $total_aggregate
                                }

                                $old_score = $total_score; //Reassign the value of the old_score

                                $i = $rank % 10;
                                $j = $rank % 100;

                                if($i == 1 && $j != 11){
                                    $nth_postion = 'ST';
                                }elseif($i == 2 && $j != 12){
                                    $nth_postion = 'ND';
                                }elseif($i == 3 && $j != 13){
                                    $nth_postion = 'RD';
                                }else{
                                     $nth_postion = 'TH'; //Assigns the nth position to the other ranks
                                }
                                

                                $position = ' '.$rank.$nth_postion.' ';
             

                                //compare marks
                                if($row1['aggregate_mark'] == $row2['aggregate_mark'] && $row2['student_id'] == $student_id){
                                  
                                        echo $position;
                                    
                                   
                                }
                            }
                        }   

                    }

        		} else {

        			//for non JHS
        			//marks for each student
            $data['data1'] = $this->db->get_where('aggregation' , array(
                                        'section_id' => $section_id,
                                            'exam_id' => $exam_id,
                                                'class_id' => $class_id,
                                                'year' => $running_year,
                                                    'term' => $running_term,
                                                        'student_id' => $student_id))->result_array();

                //marks for all students
                $this->db->where('exam_id' , $exam_id);
                $this->db->where('class_id' , $class_id);
                $this->db->where('section_id' , $section_id);
                $this->db->where('year' , $running_year);
                $this->db->where('term' , $running_term);
                $this->db->order_by("aggregate_mark", "desc");
                $data['data2'] = $this->db->get('aggregation')->result_array();
                                                                
                    if($data['data2'] && $data['data1']){

                        $position; //The real position of each mark
                        
                        $old_score = 0; //Acts as the the first or previous total aggregate for each subject
                        $rank = 1; //Ranks the positions of each mark in each subject
                        $counter = 0; //Keeps track of the number of marks in each subjects
                        $tracker = 1; //Tracks the ranks whenever $old_score == $total_aggregate
                       

                        foreach ($data['data1'] as $row1) {
                            foreach ($data['data2'] as $row2) {

                                $total_score =$row2['aggregate_mark'];
                                $counter++;

                                if($old_score != $total_score){ //checks if old_score == total_aggregate
                                    $diff = $counter - $rank; //finds the difference btn counter and rank
                                    if($tracker == $diff){
                                        $rank++; //increases the rank by one anytime tracker == diff
                                    }else{
                                        $rank = $counter;
                                    }
                                    
                                }else{
                                    $tracker++; //Tracks the ranks whenever $old_score == $total_aggregate
                                }

                                $old_score = $total_score; //Reassign the value of the old_score

                                $i = $rank % 10;
                                $j = $rank % 100;

                                if($i == 1 && $j != 11){
                                    $nth_postion = 'ST';
                                }elseif($i == 2 && $j != 12){
                                    $nth_postion = 'ND';
                                }elseif($i == 3 && $j != 13){
                                    $nth_postion = 'RD';
                                }else{
                                     $nth_postion = 'TH'; //Assigns the nth position to the other ranks
                                }
                                

                                $position = ' '.$rank.$nth_postion.' ';
             

                                //compare marks
                                if($row1['aggregate_mark'] == $row2['aggregate_mark'] && $row2['student_id'] == $student_id){
                                  
                                        echo $position;
                                    
                                   
                                }
                            }
                        }   

                    }
        		}
        }
    }


    function get_conducts($class_id='', $student_id='', $term='', $year=''){
         $class_name = $this->get_class_name($class_id);
         if($class_name == 'JHSS') {
	         	$conducts_data = $this->db->get_where('aggregation', array(
	                'class_id' => $class_id,
	                      'year' => $year,
	                            'sem' => $term,
	                                'student_id' => $student_id
	        ));

         } else {
         	$conducts_data = $this->db->get_where('aggregation', array(
	                'class_id' => $class_id,
	                      'year' => $year,
	                            'term' => $term,
	                                'student_id' => $student_id
	        ));
         }
         return $conducts_data->result_array();
    }

  

    function get_highest_marks( $exam_id , $class_id , $subject_id ) {
        $this->db->where('exam_id' , $exam_id);
        $this->db->where('class_id' , $class_id);
        $this->db->where('subject_id' , $subject_id);
        $this->db->select_max('mark_obtained');
        $highest_marks = $this->db->get('mark')->result_array();
        foreach($highest_marks as $row) {
            echo $row['mark_obtained'];
        }
    }

    function get_max_student_id( $class_id , $subject_id) {
        $this->db->where('class_id' , $class_id);
        $this->db->where('subject_id' , $subject_id);
        $this->db->select_max('student_id');
        $max_id = $this->db->get('mark')->result_array();
        foreach($max_id as $row) {
            echo $row['student_id'];
        }
    }

    function get_max_invoice_id() {
        $this->db->select_max('invoice_id');
        $this->db->where('can_delete !=', 'trash');
        $max_id = $this->db->get('invoice')->result_array();
        foreach($max_id as $row) {
            echo $row['invoice_id'];
        }
    }

    function get_max_student_id2( $class_id , $section_id) {
        $this->db->where('class_id' , $class_id);
        $this->db->where('section_id' , $section_id);
        $this->db->select_max('student_id');
        $max_id = $this->db->get('mark')->result_array();
        foreach($max_id as $row) {
            echo $row['student_id'];
        }
    }

    function get_max_exam_id($exam_id) {
        $this->db->where('exam_id' , $exam_id);
        $this->db->select_max('exam_id');
        $max_id = $this->db->get('exam')->result_array();
        foreach($max_id as $row) {
            echo $row['exam_id'];
        }
    }

    function get_max_student_id_by_class( $class_id, $running_year) {
        $this->db->where('class_id' , $class_id);
        $this->db->where('year' , $running_year);
        $this->db->where('mute', '0');
        $this->db->select_max('student_id');
        $max_id = $this->db->get('enroll')->result_array();
        foreach($max_id as $row) {
            echo $row['student_id'];
        }
    }

    function get_grade($mark_obtained) {

        $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;

        if($terminal_report_style == 'style_1') {
            $query = $this->db->get('grade');
        } else {
            $query = $this->db->get('grade_2');
        }

        
        $grades = $query->result_array();
        foreach ($grades as $row) {
            if ($mark_obtained >= $row['mark_from'] && $mark_obtained <= $row['mark_upto'])
                return $row;
        }
    }

    function get_raw_score_grade($mark_obtained) {
        $query = $this->db->get('raw_score_grade');
        $grades = $query->result_array();
        foreach ($grades as $row) {
            if ($mark_obtained >= $row['mark_from'] && $mark_obtained <= $row['mark_upto'])
                return $row;
        }
    }


    function get_system_settings() {
        $query = $this->db->get('settings');
        return $query->result_array();
    }

    ////////BACKUP RESTORE/////////
    function create_backup($type) {
        $this->load->dbutil();


        $options = array(
            'format' => 'txt', // gzip, zip, txt
            'add_drop' => TRUE, // Whether to add DROP TABLE statements to backup file
            'add_insert' => TRUE, // Whether to add INSERT data to backup file
            'newline' => "\n"               // Newline character used in backup file
        );


        if ($type == 'all') {
            $tables = array('');
            $file_name = 'system_backup';
        } else {
            $tables = array('tables' => array($type));
            $file_name = 'backup_' . $type;
        }

        $backup = & $this->dbutil->backup(array_merge($options, $tables));


        $this->load->helper('download');
        force_download($file_name . '.sql', $backup);
    }

    /////////RESTORE TOTAL DB/ DB TABLE FROM UPLOADED BACKUP SQL FILE//////////
    function restore_backup() {
        //move_uploaded_file($_FILES['userfile']['tmp_name'], 'uploads/backup.sql');
        $this->load->dbutil();


        $prefs = array(
            'filepath' => base_url().'uploads/backup.sql',
            'delete_after_upload' => TRUE,
            'delimiter' => ';'
        );
        $restore = & $this->dbutil->restore($prefs);
        unlink($prefs['filepath']);
    }

    /////////DELETE DATA FROM TABLES///////////////
    function truncate($type) {
        if ($type == 'all') {
            $this->db->truncate('student');
            $this->db->truncate('mark');
            $this->db->truncate('teacher');
            $this->db->truncate('subject');
            $this->db->truncate('class');
            $this->db->truncate('exam');
            $this->db->truncate('grade');
        } else {
            $this->db->truncate($type);
        }
    }

    ////////IMAGE URL//////////
    function get_image_url($type = '', $id = '', $gender = '') {
        if (file_exists('uploads/' . $type . '_image/' . $id . '.jpg')) {
            $image_url = base_url('uploads/' . $type . '_image/' . $id . '.jpg');
        }
        else {
            if($gender == 'Male') {
                $image_url = base_url('uploads/user_male.jpg');
            }else if($gender == 'Female') {
                $image_url = base_url('uploads/user_female.jpg');
            }else {
                $image_url = base_url('uploads/user.jpg');
            }
            
        }

        return $image_url;
    }

    function get_head_teacher_signature() {
        if (file_exists('uploads/signature/admin/head_teacher.png')) {
            $image_url = base_url('uploads/signature/admin/head_teacher.png');
        }

        return $image_url;
    }

    ////////STUDY MATERIAL//////////
    function save_study_material_info()
    {
        $timestamp_input = $this->input->post('timestamp');
        $data['timestamp'] = strtotime($timestamp_input);
        
        // Validate that timestamp is today's date
        $today_start = strtotime('today');
        $today_end = strtotime('tomorrow') - 1;
        
        if ($data['timestamp'] < $today_start || $data['timestamp'] > $today_end) {
            throw new Exception('Creation date must be today\'s date. You cannot backdate or future-date study materials.');
        }
        
        $data['title']             = strtoupper($this->input->post('title')); // Uppercase
        
        // Handle teacher_id: use session teacher_id if available, otherwise use posted teacher_id or NULL
        if ($this->session->userdata('teacher_id')) {
            $data['teacher_id'] = $this->session->userdata('teacher_id');
        } elseif ($this->input->post('teacher_id')) {
            $data['teacher_id'] = $this->input->post('teacher_id');
        } else {
            $data['teacher_id'] = NULL;
        }
        
        $data['description']       = strtoupper($this->input->post('description')); // Uppercase
        $data['file_name']         = $_FILES["file_name"]["name"];
        $data['file_type']         = $this->input->post('file_type');
        $data['class_id']          = $this->input->post('class_id');
        $data['start_date']        = strtotime($this->input->post('start'));
        $data['end_date']          = strtotime($this->input->post('end'));
        $data['subject_id']        = $this->input->post('subject_id');

        $this->db->insert('document',$data);

        $document_id            = $this->db->insert_id();
        $uploadPath = "uploads/documents/lesson_notes/";
        $file_path = $uploadPath . $document_id.'_'.$_FILES["file_name"]["name"];

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        move_uploaded_file($_FILES["file_name"]["tmp_name"], $file_path);


        /*update with file path*/
        $this->db->where('document_id', $document_id);
        $this->db->set('file_path', $file_path);
        $this->db->update('document');
    }

    function update_study_material_status() {
        $status = $this->input->post('status');
        $document_id = $this->input->post('id');

        $this->db->where('document_id', $document_id);
        $this->db->set('status', $status);
        $this->db->update('document');
    }

    function select_study_material_info($filters = array())
    {
        // Apply filters if provided
        if (!empty($filters['class_id'])) {
            $this->db->where('class_id', $filters['class_id']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['start_date'])) {
            $this->db->where('start_date >=', strtotime($filters['start_date']));
        }
        if (!empty($filters['end_date'])) {
            $this->db->where('end_date <=', strtotime($filters['end_date']));
        }
        
        // Order by status: Pending first, Declined middle, Approved last
        // Then by timestamp ascending (oldest first)
        $this->db->order_by("FIELD(status, 'Pending', 'Declined', 'Approved')", "", FALSE);
        $this->db->order_by("timestamp", "asc");
        return $this->db->get_where('document')->result_array();
    }
    //selecting study material info for specific teacher

    function select_study_material_info_for_teacher()
    {
        $this->db->order_by("timestamp", "desc");
        return $this->db->get_where('document',array('teacher_id'=>$this->session->userdata('teacher_id')))->result_array();
    }

    function select_study_material_info_for_student()
    {
        $student_id = $this->session->userdata('student_id');
        $class_id   = $this->db->get_where('enroll', array(
            'student_id' => $student_id, 'mute' => '0',
                'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description))->row()->class_id;
        $this->db->order_by("timestamp", "desc");
        return $this->db->get_where('document', array('class_id' => $class_id))->result_array();
    }

    function update_study_material_info($document_id)
    {
        $timestamp_input = $this->input->post('timestamp');
        $data['timestamp'] = strtotime($timestamp_input);
        
        // Validate that timestamp is today's date
        $today_start = strtotime('today');
        $today_end = strtotime('tomorrow') - 1;
        
        if ($data['timestamp'] < $today_start || $data['timestamp'] > $today_end) {
            throw new Exception('Creation date must be today\'s date. You cannot backdate or future-date study materials.');
        }
        
        $data['title']             = strtoupper($this->input->post('title')); // Uppercase
        $data['description']       = strtoupper($this->input->post('description')); // Uppercase
        $data['class_id']          = $this->input->post('class_id');
        $data['start_date']        = strtotime($this->input->post('start'));
        $data['end_date']          = strtotime($this->input->post('end'));
        $data['subject_id']        = $this->input->post('subject_id');
        
        // Only update file if a new one is uploaded
        if (!empty($_FILES["file_name"]["name"])) {
            $data['file_name']     = $_FILES["file_name"]["name"];
            $data['file_type']     = $this->input->post('file_type');
            
            $uploadPath = "uploads/documents/lesson_notes/";
            $file_path = $uploadPath . $document_id.'_'.$_FILES["file_name"]["name"];

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete old file if it exists
            $old_file = $this->db->get_where('document', array('document_id' => $document_id))->row();
            if ($old_file && !empty($old_file->file_path) && file_exists($old_file->file_path)) {
                unlink($old_file->file_path);
            }

            // Upload new file
            move_uploaded_file($_FILES["file_name"]["tmp_name"], $file_path);
            
            // Update file path
            $data['file_path'] = $file_path;
        } else if ($this->input->post('file_type')) {
            // Update file type only if provided
            $data['file_type'] = $this->input->post('file_type');
        }

        $this->db->where('document_id',$document_id);
        $this->db->update('document',$data);
    }

    function delete_study_material_info($document_id)
    {
        $this->db->where('document_id',$document_id);
        $document = $this->db->get('document')->row();
        
        if ($document) {
            $file_path = $document->file_path;
            
            // Delete from database
            $this->db->where('document_id',$document_id);
            $this->db->delete('document');

            // Delete the file if it exists
            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    ////////private message//////
    function send_new_private_message() {
        $message    = $this->input->post('message');
        $timestamp  = strtotime(date("Y-m-d H:i:s"));

        $reciever   = $this->input->post('reciever');
        $sender     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');

        //check if the thread between those 2 users exists, if not create new thread
        $num1 = $this->db->get_where('message_thread', array('sender' => $sender, 'reciever' => $reciever))->num_rows();
        $num2 = $this->db->get_where('message_thread', array('sender' => $reciever, 'reciever' => $sender))->num_rows();

        //check if file is attached or not
        if ($_FILES['attached_file_on_messaging']['name'] != "") {
          $data_message['attached_file_name'] = $_FILES['attached_file_on_messaging']['name'];
        }

        if ($num1 == 0 && $num2 == 0) {
            $message_thread_code                        = substr(md5(rand(100000000, 20000000000)), 0, 15);
            $data_message_thread['message_thread_code'] = $message_thread_code;
            $data_message_thread['sender']              = $sender;
            $data_message_thread['reciever']            = $reciever;
            $this->db->insert('message_thread', $data_message_thread);
        }
        if ($num1 > 0)
            $message_thread_code = $this->db->get_where('message_thread', array('sender' => $sender, 'reciever' => $reciever))->row()->message_thread_code;
        if ($num2 > 0)
            $message_thread_code = $this->db->get_where('message_thread', array('sender' => $reciever, 'reciever' => $sender))->row()->message_thread_code;


        $data_message['message_thread_code']    = $message_thread_code;
        $data_message['message']                = $message;
        $data_message['sender']                 = $sender;
        $data_message['timestamp']              = $timestamp;
        $this->db->insert('message', $data_message);

        // notify email to email reciever
//        $this->email_model->notify_email('new_message_notification', $this->db->insert_id());

        return $message_thread_code;
    }

    function send_reply_message($message_thread_code) {
        $message    = $this->input->post('message');
        $timestamp  = strtotime(date("Y-m-d H:i:s"));
        $sender     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
        //check if file is attached or not
        if ($_FILES['attached_file_on_messaging']['name'] != "") {
          $data_message['attached_file_name'] = $_FILES['attached_file_on_messaging']['name'];
        }
        $data_message['message_thread_code']    = $message_thread_code;
        $data_message['message']                = $message;
        $data_message['sender']                 = $sender;
        $data_message['timestamp']              = $timestamp;
        $this->db->insert('message', $data_message);

        // notify email to email reciever
        //$this->email_model->notify_email('new_message_notification', $this->db->insert_id());
    }

    function send_reply_group_message($message_thread_code) {
        $message    = $this->input->post('message');
        $timestamp  = strtotime(date("Y-m-d H:i:s"));
        $sender     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
        //check if file is attached or not
        if ($_FILES['attached_file_on_messaging']['name'] != "") {
          $data_message['attached_file_name'] = $_FILES['attached_file_on_messaging']['name'];
        }
        $data_message['group_message_thread_code']    = $message_thread_code;
        $data_message['message']                = $message;
        $data_message['sender']                 = $sender;
        $data_message['timestamp']              = $timestamp;
        $this->db->insert('group_message', $data_message);
    }

    function mark_thread_messages_read($message_thread_code) {
        // mark read only the oponnent messages of this thread, not currently logged in user's sent messages
        $current_user = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
        $this->db->where('sender !=', $current_user);
        $this->db->where('message_thread_code', $message_thread_code);
        $this->db->update('message', array('read_status' => 1));
    }

    function count_unread_message_of_thread($message_thread_code) {
        $unread_message_counter = 0;
        $current_user = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
        $messages = $this->db->get_where('message', array('message_thread_code' => $message_thread_code))->result_array();
        foreach ($messages as $row) {
            if ($row['sender'] != $current_user && $row['read_status'] == '0')
                $unread_message_counter++;
        }
        return $unread_message_counter;
    }

    // QUESTION PAPER
    function create_question_paper()
    {
        $data['title']          = $this->input->post('title');
        $data['class_id']       = $this->input->post('class_id');
        $data['exam_id']        = $this->input->post('exam_id');
        $data['question_paper'] = $this->input->post('question_paper');
        $data['teacher_id']     = $this->session->userdata('login_user_id');

        $this->db->insert('question_paper', $data);
    }

    function update_question_paper($question_paper_id = '')
    {
        $data['title']          = $this->input->post('title');
        $data['class_id']       = $this->input->post('class_id');
        $data['exam_id']        = $this->input->post('exam_id');
        $data['question_paper'] = $this->input->post('question_paper');

        $this->db->update('question_paper', $data, array('question_paper_id' => $question_paper_id));
    }

    function delete_question_paper($question_paper_id = '')
    {
        $this->db->where('question_paper_id', $question_paper_id);
        $this->db->delete('question_paper');
    }

    // BOOK REQUEST
    function create_book_request()
    {
        $data['book_id']            = $this->input->post('book_id');
        $data['student_id']         = $this->session->userdata('login_user_id');
        $data['issue_start_date']   = strtotime($this->input->post('issue_start_date'));
        $data['issue_end_date']     = strtotime($this->input->post('issue_end_date'));

        $this->db->insert('book_request', $data);
    }


    function curl_request($code = '') {

        $product_code = $code;

        $personal_token = "FkA9UyDiQT0YiKwYLK3ghyFNRVV9SeUn";
        $url = "https://api.envato.com/v3/market/author/sale?code=".$product_code;
        $curl = curl_init($url);

        //setting the header for the rest of the api
        $bearer   = 'bearer ' . $personal_token;
        $header   = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json; charset=utf-8';
        $header[] = 'Authorization: ' . $bearer;

        $verify_url = 'https://api.envato.com/v1/market/private/user/verify-purchase:'.$product_code.'.json';
        $ch_verify = curl_init( $verify_url . '?code=' . $product_code );

        curl_setopt( $ch_verify, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch_verify, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch_verify, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch_verify, CURLOPT_CONNECTTIMEOUT, 2 ); // Reduced from 5 to 2 seconds for faster offline detection
        curl_setopt( $ch_verify, CURLOPT_TIMEOUT, 3 ); // Added: maximum 3 seconds total execution time
        curl_setopt( $ch_verify, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $cinit_verify_data = curl_exec( $ch_verify );
        $curl_error = curl_error($ch_verify); // Capture error for logging
        curl_close( $ch_verify );
        
        // Log if verification failed due to connectivity issues (offline mode)
        if ($curl_error) {
            log_message('info', 'License verification skipped - System appears to be offline: ' . $curl_error);
        }

        $response = json_decode($cinit_verify_data, true);

        if (count($response['verify-purchase']) > 0) {
            return true;
        } else {
            return false;
        }

    }
    


    /**
     * ENTERPRISE-GRADE: Permanently delete student and ALL associated records
     * Comprehensive deletion across all tables in the system
     */
    function delete_student($student_id) {
        // Start transaction for data integrity
        $this->db->trans_start();
        
        try {
            // Get student info before deletion
            $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
            if (!$student) {
                return array('status' => 'error', 'message' => get_phrase('student_not_found'));
            }
            
            $student_name = $student->name;
            $student_code = $student->student_code;
            
            // Comprehensive list of ALL tables with student_id
            $tables_to_delete = array(
                // Core student tables
                'enroll',
                'attendance',
                'mark',
                'aggregation',
                
                // Financial tables
                'invoice',
                'payment',
                'student_discount_assignments',
                
                // Discount tables (new system)
                'invoice_discounts',
                'invoice_discount_items',
                
                // Daily fees tables (new system)
                'daily_fee_wallet',
                'daily_fee_transactions',
                
                // Academic tables
                'online_exam_result',
                'book_request',
                
                // Boarding tables
                'beneficiary_list',
                
                // Audit and logs (new system)
                'admission_logs',
                'student_ledger'
            );
            
            // Delete from all tables
            foreach ($tables_to_delete as $table) {
                if ($this->db->table_exists($table)) {
                    $this->db->where('student_id', $student_id);
                    $this->db->delete($table);
                }
            }
            
            // Delete messages where student is sender or receiver
            $threads = $this->db->get('message_thread')->result_array();
            foreach ($threads as $row) {
                $sender = explode('-', $row['sender']);
                $receiver = explode('-', $row['reciever']);
                if (($sender[0] == 'student' && $sender[1] == $student_id) || 
                    ($receiver[0] == 'student' && $receiver[1] == $student_id)) {
                    $thread_code = $row['message_thread_code'];
                    $this->db->delete('message', array('message_thread_code' => $thread_code));
                    $this->db->delete('message_thread', array('message_thread_code' => $thread_code));
                }
            }
            
            // Delete student image
            $image_path = 'uploads/student_image/' . $student_id . '.jpg';
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
            
            // Delete student barcode
            $barcode_path = 'uploads/barcodes/students/' . $student_code . '.png';
            if (file_exists($barcode_path)) {
                @unlink($barcode_path);
            }
            
            // Finally, delete the student record itself
            $this->db->where('student_id', $student_id);
            $this->db->delete('student');
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return array('status' => 'error', 'message' => get_phrase('deletion_failed'));
            }
            
            // Log the deletion
            $log_data = array(
                'type' => 'student_deletion',
                'item_id' => $student_id,
                'description' => 'Permanently deleted student: ' . $student_name . ' (' . $student_code . ')',
                'timestamp' => time()
            );
            
            return array(
                'status' => 'success', 
                'message' => get_phrase('student_deleted_successfully') . ': ' . $student_name
            );
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return array('status' => 'error', 'message' => get_phrase('deletion_error') . ': ' . $e->getMessage());
        }
    }

    // Group messaging portion
    function create_group(){

      $yes_no = $this->input->post('yes_no');
      if($yes_no == 'yes') {
        $data['name'] = $this->input->post('member_name');
        $data['phone'] = $this->input->post('member_contact');

        $data['group_message_thread_code'] = substr(md5(rand(100000000, 20000000000)), 0, 15);
        $data['created_timestamp'] = strtotime(date("Y-m-d H:i:s"));
        $data['group_name'] = $this->input->post('group_name');

        array_push($_POST['user'], $this->session->userdata('login_type').'_'.$this->session->userdata('admin_id'));
        $data['members'] = json_encode($_POST['user']);
        echo $data['members'];
        

       // $this->db->insert('group_message_other', $data);



      } else {
          $data = array();
          $data['group_message_thread_code'] = substr(md5(rand(100000000, 20000000000)), 0, 15);
          $data['created_timestamp'] = strtotime(date("Y-m-d H:i:s"));
          $data['group_name'] = $this->input->post('group_name');
          if(!empty($_POST['user'])) {
              array_push($_POST['user'], $this->session->userdata('login_type').'_'.$this->session->userdata('admin_id'));
              $data['members'] = json_encode($_POST['user']);
          }
          else{
            $_POST['user'] = array();
            array_push($_POST['user'], $this->session->userdata('login_type').'_'.$this->session->userdata('admin_id'));
            $data['members'] = json_encode($_POST['user']);
          }
          $this->db->insert('group_message_thread', $data);
      }
      
      redirect(site_url('admin/group_message'), 'refresh');
    }
    // Group messaging portion
    function update_group($thread_code = ""){
      $data = array();
      $data['group_name'] = $this->input->post('group_name');
      if(!empty($_POST['user'])) {
          array_push($_POST['user'], $this->session->userdata('login_type').'_'.$this->session->userdata('admin_id'));
          $data['members'] = json_encode($_POST['user']);
      }
      else{
        $_POST['user'] = array();
        array_push($_POST['user'], $this->session->userdata('login_type').'_'.$this->session->userdata('admin_id'));
        $data['members'] = json_encode($_POST['user']);
      }
      $this->db->where('group_message_thread_code', $thread_code);
      $this->db->update('group_message_thread', $data);
        redirect(site_url('admin/group_message'), 'refresh');
    }

    function get_settings($type)
    {
        $des = $this->db->get_where('settings', array('type' => $type))->row()->description;
        return $des;
    }

    function update_payumoney_keys(){
      $data['description'] = $this->input->post('payumoney_merchant_key');
      $this->db->where('type' , 'payumoney_merchant_key');
      $this->db->update('settings' , $data);

      $data['description'] = $this->input->post('payumoney_salt_id');
      $this->db->where('type' , 'payumoney_salt_id');
      $this->db->update('settings' , $data);
    }

    // update paypal keys
    function update_paypal_keys() {
        $info = array();

        $paypal['active'] = $this->input->post('paypal_active');
        $paypal['mode'] = $this->input->post('paypal_mode');
        $paypal['sandbox_client_id'] = $this->input->post('sandbox_client_id');
        $paypal['production_client_id'] = $this->input->post('production_client_id');

        array_push($info, $paypal);

        $data['description']    =   json_encode($info);
        $this->db->where('type', 'paypal');
        $this->db->update('settings', $data);
    }

    // update stripe keys
    function update_stripe_keys() {
        $info = array();

        $stripe['active'] = $this->input->post('stripe_active');
        $stripe['testmode'] = $this->input->post('testmode');
        $stripe['public_key'] = $this->input->post('public_key');
        $stripe['secret_key'] = $this->input->post('secret_key');
        $stripe['public_live_key'] = $this->input->post('public_live_key');
        $stripe['secret_live_key'] = $this->input->post('secret_live_key');

        array_push($info, $stripe);

        $data['description']    =   json_encode($info);
        $this->db->where('type', 'stripe_keys');
        $this->db->update('settings', $data);
    }


    function create_online_exam(){
        $data['code']  = substr(md5(uniqid(rand(), true)), 0, 7);
        $data['title'] = $this->input->post('exam_title');
        $data['class_id'] = $this->input->post('class_id');
        $data['section_id'] = $this->input->post('section_id');
        $data['subject_id'] = $this->input->post('subject_id');
        $data['minimum_percentage'] = $this->input->post('minimum_percentage');
        $data['instruction'] = $this->input->post('instruction');
        $data['exam_date'] = strtotime($this->input->post('exam_date'));
        $data['time_start'] = $this->input->post('time_start');
        $data['time_end'] = $this->input->post('time_end');
        $data['duration'] = strtotime(date('Y-m-d', $data['exam_date']).' '.$data['time_end']) - strtotime(date('Y-m-d', $data['exam_date']).' '.$data['time_start']);
        $data['running_year'] = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;

        $class_name = $this->get_class_name($data['class_id']);

        if($class_name == 'JHSS') {
        	$data['sem'] = $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description;
        } else {
        	$data['term'] = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
        }
        

        /*print_r($data);
        echo '<br/>';
        echo gmdate("H:i:s", '18305');
        die();*/
        $this->db->insert('online_exam', $data);
    }

    function update_online_exam(){

        $data['title'] = $this->input->post('exam_title');
        $data['class_id'] = $this->input->post('class_id');
        $data['section_id'] = $this->input->post('section_id');
        $data['subject_id'] = $this->input->post('subject_id');
        $data['minimum_percentage'] = $this->input->post('minimum_percentage');
        $data['instruction'] = $this->input->post('instruction');
        $data['exam_date'] = strtotime($this->input->post('exam_date'));
        $data['time_start'] = $this->input->post('time_start');
        $data['time_end'] = $this->input->post('time_end');
        $data['duration'] = strtotime(date('Y-m-d', $data['exam_date']).' '.$data['time_end']) - strtotime(date('Y-m-d', $data['exam_date']).' '.$data['time_start']);
        $class_name = $this->get_class_name($data['class_id']);

        if($class_name == 'JHSS') {
            $data['sem'] = $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description;
            $data['term'] = NULL;
        } else {
            $data['term'] = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
            $data['sem'] = NULL;
        }

        $this->db->where('online_exam_id', $this->input->post('online_exam_id'));
        $this->db->update('online_exam', $data);

        //$this->session->set_flashdata('flash_message', $this->input->post('exam_date'));
    }

    // multiple_choice_question crud functions
    function add_multiple_choice_question_to_online_exam($online_exam_id){
        if (sizeof($this->input->post('options')) != $this->input->post('number_of_options')) {
            $this->session->set_flashdata('error_message' , get_phrase('no_options_can_be_blank'));
            return;
        }
        foreach ($this->input->post('options') as $option) {
            if ($option == "") {
                $this->session->set_flashdata('error_message' , get_phrase('no_options_can_be_blank'));
                return;
            }
        }
        if (sizeof($this->input->post('correct_answers')) == 0) {
            $correct_answers = [""];
        }
        else{
            $correct_answers = $this->input->post('correct_answers');
        }
        $data['online_exam_id']     = $online_exam_id;
        $data['question_title']     = $this->input->post('question_title');
        $data['mark']               = $this->input->post('mark');
        $data['number_of_options']  = $this->input->post('number_of_options');
        $data['type']               = 'multiple_choice';
        $data['options']            = json_encode($this->input->post('options'));
        $data['correct_answers']    = json_encode($correct_answers);
        $this->db->insert('question_bank', $data);
        $this->session->set_flashdata('flash_message' , get_phrase('question_added'));
    }

    function update_multiple_choice_question($question_id){
        if (sizeof($this->input->post('options')) != $this->input->post('number_of_options')) {
            $this->session->set_flashdata('error_message' , get_phrase('no_options_can_be_blank'));
            return;
        }
        foreach ($this->input->post('options') as $option) {
            if ($option == "") {
                $this->session->set_flashdata('error_message' , get_phrase('no_options_can_be_blank'));
                return;
            }
        }

        if (sizeof($this->input->post('correct_answers')) == 0) {
            $correct_answers = [""];
        }
        else{
            $correct_answers = $this->input->post('correct_answers');
        }

        $data['question_title']     = $this->input->post('question_title');
        $data['mark']               = $this->input->post('mark');
        $data['number_of_options']  = $this->input->post('number_of_options');
        $data['options']            = json_encode($this->input->post('options'));
        $data['correct_answers']    = json_encode($correct_answers);
        $this->db->where('question_bank_id', $question_id);
        $this->db->update('question_bank', $data);
        $this->session->set_flashdata('flash_message' , get_phrase('question_updated'));
    }

    // true false questions crud functions
    function add_true_false_question_to_online_exam($online_exam_id){
        $data['online_exam_id']     = $online_exam_id;
        $data['question_title']     = $this->input->post('question_title');
        $data['type']               = 'true_false';
        $data['mark']               = $this->input->post('mark');
        $data['correct_answers']    = $this->input->post('true_false_answer');
        $this->db->insert('question_bank', $data);
        $this->session->set_flashdata('flash_message' , get_phrase('question_added'));
    }
    function update_true_false_question($question_id){
        $data['question_title']     = $this->input->post('question_title');
        $data['mark']               = $this->input->post('mark');
        $data['correct_answers']    = $this->input->post('true_false_answer');

        $this->db->where('question_bank_id', $question_id);
        $this->db->update('question_bank', $data);
        $this->session->set_flashdata('flash_message' , get_phrase('question_updated'));
    }

    // fill in the blanks question portion
    function add_fill_in_the_blanks_question_to_online_exam($online_exam_id){
        $suitable_words_array = explode(',', $this->input->post('suitable_words'));
        $suitable_words = array();
        foreach ($suitable_words_array as $row) {
          array_push($suitable_words, strtolower($row));
        }
        $data['online_exam_id']     = $online_exam_id;
        $data['question_title']     = $this->input->post('question_title');
        $data['type']               = 'fill_in_the_blanks';
        $data['mark']               = $this->input->post('mark');
        $data['correct_answers']    = json_encode(array_map('trim',$suitable_words));
        $this->db->insert('question_bank', $data);
        $this->session->set_flashdata('flash_message' , get_phrase('question_added'));
    }
    function update_fill_in_the_blanks_question($question_id){
        $suitable_words_array = explode(',', $this->input->post('suitable_words'));
        $suitable_words = array();
        foreach ($suitable_words_array as $row) {
          array_push($suitable_words, strtolower($row));
        }
        $data['question_title']     = $this->input->post('question_title');
        $data['mark']               = $this->input->post('mark');
        $data['correct_answers']    = json_encode(array_map('trim',$suitable_words));

        $this->db->where('question_bank_id', $question_id);
        $this->db->update('question_bank', $data);
        $this->session->set_flashdata('flash_message' , get_phrase('question_updated'));
    }
    function delete_question_from_online_exam($question_id){
        $this->db->where('question_bank_id', $question_id);
        $done = $this->db->delete('question_bank');

        return $done;
    }
    function manage_online_exam_status($online_exam_id = "", $status = ""){
        $checker = array(
            'online_exam_id' => $online_exam_id
        );
        $updater = array(
            'status' => $status
        );

        $this->db->where($checker);
        $this->db->update('online_exam', $updater);
        $this->session->set_flashdata('flash_message' , get_phrase('exam').' '.$status);
    }

    function available_exams($student_id) {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        $running_sem = get_settings('running_sem');

        $class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0', 'year' => $running_year))->row()->class_id;
        $section_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0',))->row()->section_id;

        $class_name = $this->get_class_name($class_id);
        if($class_name == 'JHSS') {
        	 $match = array('running_year' => $running_year, 'sem' => $running_sem, 'class_id' => $class_id, 'section_id' => $section_id, 'status' => 'published');
        	} else {
        		 $match = array('running_year' => $running_year, 'term' => $running_term, 'class_id' => $class_id, 'section_id' => $section_id, 'status' => 'published');
        	}
       
        $this->db->order_by("exam_date", "desc");
        $exams = $this->db->where($match)->get('online_exam')->result_array();
        return $exams;
    }

    function change_online_exam_status_to_attended_for_student($online_exam_id = ""){

        $checker = array(
            'online_exam_id' => $online_exam_id,
            'student_id' => $this->session->userdata('login_user_id')
        );

        if($this->db->get_where('online_exam_result', $checker)->num_rows() == 0){
            $inserted_array = array(
                'status' => 'attended',
                'online_exam_id' => $online_exam_id,
                'student_id' => $this->session->userdata('login_user_id'),
                'exam_started_timestamp' => strtotime("now")
            );
            $this->db->insert('online_exam_result', $inserted_array);
        }
    }

    function submit_online_exam($online_exam_id = "", $answer_script = ""){

        $checker = array(
            'online_exam_id' => $online_exam_id,
            'student_id' => $this->session->userdata('login_user_id')
        );
        $updated_array = array(
            'status' => 'submitted',
            'answer_script' => $answer_script
        );

        $this->db->where($checker);
        $this->db->update('online_exam_result', $updated_array);

        $this->calculate_exam_mark($online_exam_id);
    }

    function calculate_exam_mark($online_exam_id) {

        $checker = array(
            'online_exam_id' => $online_exam_id,
            'student_id' => $this->session->userdata('login_user_id')
        );


        $obtained_marks = 0;
        $online_exam_result = $this->db->get_where('online_exam_result', $checker);
        if ($online_exam_result->num_rows() == 0) {

            $data['obtained_mark'] = 0;
        }
        else{
            $results = $online_exam_result->row_array();
            $answer_script = json_decode($results['answer_script'], true);
            foreach ($answer_script as $row) {

                if ($row['submitted_answer'] == $row['correct_answers']) {

                    $obtained_marks = $obtained_marks + $this->get_question_details_by_id($row['question_bank_id'], 'mark');
                }
            }
            $data['obtained_mark'] = $obtained_marks;
        }
        $total_mark = $this->get_total_mark($online_exam_id);
        $query = $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id))->row_array();
        $minimum_percentage = $query['minimum_percentage'];

        $minumum_required_marks = ($total_mark * $minimum_percentage) / 100;
        if ($minumum_required_marks > $obtained_marks) {
            $data['result'] = 'fail';
        }
        else {
            $data['result'] = 'pass';
        }
        $this->db->where($checker);
        $this->db->update('online_exam_result', $data);
    }

    function get_total_mark($online_exam_id){
        $added_question_info = $this->db->get_where('question_bank', array('online_exam_id' => $online_exam_id))->result_array();
        $total_mark = 0;
        if (sizeof($added_question_info) > 0){
            foreach ($added_question_info as $single_question) {
                $total_mark = $total_mark + $single_question['mark'];
            }
        }
        return $total_mark;
    }

    function get_question_details_by_id($question_bank_id, $column_name = "") {

        return $this->db->get_where('question_bank', array('question_bank_id' => $question_bank_id))->row()->$column_name;
    }

    function check_availability_for_student($online_exam_id){

        $result = $this->db->get_where('online_exam_result', array('online_exam_id' => $online_exam_id, 'student_id' => $this->session->userdata('login_user_id')))->row_array();

        return $result['status'];
    }

    function get_correct_answer($question_bank_id = ""){

        $question_details = $this->db->get_where('question_bank', array('question_bank_id' => $question_bank_id))->row_array();
        return $question_details['correct_answers'];
    }

    function get_online_exam_result($student_id){
        $match = array('student_id' => $student_id, 'status' => 'submitted');
        $exams = $this->db->where($match)->get('online_exam_result')->result_array();
        return $exams;
    }

    function verify_id($id) {
         $student_code_pref       = $this->db->get_where('settings', array('type'=>'student_code_prefix'))->row()->description;
        $student_code_f       = $student_code_pref.$this->db->get_where('settings', array('type'=>'student_code_format'))->row()->description;

        $student_code = $this->db->get_where('student', array('student_code' => $id));
        if(strlen($id) < strlen($student_code_f) || strlen($id) > strlen($student_code_f) || substr($id, 0, strlen($student_code_pref)) !== $student_code_pref) {
            echo '<span style="color:red;">Invalid Student ID. <i class="glyphicon glyphicon-remove"></i></span>';
        }elseif($student_code->num_rows() < 1) {
            echo '<span style="color:green;">Available <i class="glyphicon glyphicon-ok"></i></span>';
        }else{
            echo '<span style="color:red;">Not Available <i class="glyphicon glyphicon-remove"></i></span>';
        }
        
    }

    function verify_tid($id) {
        $teacher_code_pref       = $this->db->get_where('settings', array('type'=>'teacher_code_prefix'))->row()->description;
        $teacher_code_format     = $this->db->get_where('settings', array('type'=>'teacher_code_format'))->row()->description;
        
        $prefix_length = strlen($teacher_code_pref);
        $format_length = strlen($teacher_code_format);
        $min_length = $prefix_length + 1; // At least prefix + 1 digit
        $max_length = $prefix_length + $format_length; // Prefix + full format length

        $teacher_code = $this->db->get_where('teacher', array('teacher_code' => $id));
        
        // Validation checks:
        // 1. Check if ID starts with correct prefix
        // 2. Check if ID length is within valid range (min to max)
        // 3. Check if the part after prefix is numeric
        if(substr($id, 0, $prefix_length) !== $teacher_code_pref) {
            echo '<span style="color:red;">Invalid Staff ID. <i class="glyphicon glyphicon-remove"></i></span>';
        } elseif(strlen($id) < $min_length || strlen($id) > $max_length) {
            echo '<span style="color:red;">Invalid Staff ID. <i class="glyphicon glyphicon-remove"></i></span>';
        } elseif(!is_numeric(substr($id, $prefix_length))) {
            echo '<span style="color:red;">Invalid Staff ID. <i class="glyphicon glyphicon-remove"></i></span>';
        } elseif($teacher_code->num_rows() < 1) {
            echo '<span style="color:green;">Available <i class="glyphicon glyphicon-ok"></i></span>';
        } else {
            echo '<span style="color:red;">Not Available <i class="glyphicon glyphicon-remove"></i></span>';
        }
        
    }

    //reload student take exam page for update in times
    function load_exam_ends_timestamp($exam_ends_timestamp, $online_exam_id) {
        $running_year                    = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
        $running_term                    = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;

        $exam_ends_timestamp_now = strtotime(date('d-M-Y', $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id, 'running_year' => $running_year))->row()->exam_date)." ".$this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id, 'running_year' => $running_year))->row()->time_end);

        if($exam_ends_timestamp_now != $exam_ends_timestamp) {
            echo 'Changed';
        }else{
            echo 'noo';
        }
    }

    //book's availability
    function verify_book($book_id) {
        $status = $this->db->get_where('book', array('book_id' => $book_id))->row()->status;
        if($status == 'Unavailable') {
            echo 'Unavailable';
        }
    }

    //mobile payment alert for admin
    function mobile_money_payment_alert_rows() {
        //admin and accountant
        $account_type       =   $this->session->userdata('login_type');
        $query = $this->db->get('mobile_money_payment');

        if($account_type == 'admin' || $account_type == 'accountant' || $account_type == 'teacher' || $account_type == 'librarian') {
        echo $query->num_rows();
        }else{
            //parents  
            $counter = 0;       
            $student_id_array = $query->result_array();
            foreach ($student_id_array as $row_st) {
                if($this->db->get_where('student', array('student_id' => $row_st['student_id']))->row()->parent_id == $this->session->userdata('parent_id') || $row_st['student_id'] == $this->session->userdata('student_id')) {
                    $student_id = $row_st['student_id'];
                    $parent_id = $this->db->get_where('student', array('student_id' => $student_id))->row()->parent_id;

                    if($account_type == 'parent' && $parent_id == $this->session->userdata('parent_id') || $account_type == 'student' && $student_id == $this->session->userdata('student_id')) {
                        $this->db->select('student_id');
                        $this->db->distinct();
                        $query_payment = $this->db->get_where('mobile_money_payment', array('student_id' => $student_id))->num_rows();

                        $counter += $query_payment;
                    }
                }

            }
            
        echo $counter;
        }

    }

    function mobile_money_payment_alert_show($page_name) {
        $this->db->order_by('timestamp', 'desc');
        $query_rows = $this->db->get('mobile_money_payment');
        $query = $query_rows->result_array();

        $account_type       =   $this->session->userdata('login_type');

        foreach($query as $row) {
            $parent_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->parent_id;
            $parent_name = $this->db->get_where('parent', array('parent_id' => $parent_id))->row()->name;

            $student_name = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name;
            $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

            //time calculation
            $sent_time = $row['timestamp'];
            $current_time = strtotime(date('d-M-Y, H:i:s'));
            $duration = $current_time - $sent_time;
            $hour = ($duration / 3600);
            $minute = ($hour * 60);
            $second = ($minute * 60);
            $time = '';
            if($second <= 59) {
                $time = 'Just now';
            }else if($second >= 60 && $second <= 119) {
                $time = '1 minute ago';
            }else if($minute <= 59) {
                $time = intval($minute). ' minutes ago';
            }else if($minute >= 60 && $minute <= 119) {
                $time = '1 hour ago';
            }else if($hour <= 23) {
                $time = intval($hour). ' hours ago';
            }else if( $hour >= 24 && $hour <= 47) {
                $time = '1 day ago';
            }else if( $hour >= 48 && $hour <= 167) {
                $time = intval($hour / 24). ' days ago';
            }else if($hour >= 168 && ($hour / 24) <= 13) {
                $time = '1 week ago';
            }else if(($hour / 24) >= 14 && ($hour / 24) <= 29) {
                $time = intval(($hour / 24) / 7). ' weeks ago';
            }else if(($hour / 24) >= 30 && ($hour / 24) <= 59) {
                $time = '1 month ago';
            }else if(($hour / 24) >= 60 && ($hour / 24) <= 364) {
                $time = intval(($hour / 24) / 30). ' months ago';
            }else if(($hour / 24) >= 365 && ($hour / 24) <= 731) {
                $time = '1 year ago';
            }else if(($hour / 24) >= 732) {
                $time = intval(($hour / 24) / 365). ' years ago';
            }

                if($account_type == 'admin') {

                   echo '<a href="'.site_url('admin/modal_mobile_money_checkout/view/'.$row['invoice_id'].'/'.$row['student_id']).'/'.$page_name.'/'.$row['timestamp'].'" class="list-group-item">
                          <div class="media">
                            <div class="user-status busy pull-left">
                            <img class="media-object img-circle pull-left" src="'.$this->crud_model->get_image_url('student',$row['student_id'], $gender).'" alt="'.$student_name.'" width="40">
                            </div>
                            <div class="media-body">
                              <h5 class="media-heading">Payment from '.$parent_name. ' for <br>'.$student_name.'</h5>
                              <small class="text-muted">'.$time.'</small>
                            </div>
                          </div>
                        </a>';

                }elseif($account_type == 'accountant') {
                    echo '<a href="'.site_url('accountant/modal_mobile_money_checkout/view/'.$row['invoice_id'].'/'.$row['student_id']).'/'.$page_name.'/'.$row['timestamp'].'" class="list-group-item">
                          <div class="media">
                            <div class="user-status busy pull-left">
                            <img class="media-object img-circle pull-left" src="'.$this->crud_model->get_image_url('student',$row['student_id'], $gender).'" alt="'.$student_name.'" width="40">
                            </div>
                            <div class="media-body">
                              <h5 class="media-heading">Payment from '.$parent_name. ' for <br>'.$student_name.'</h5>
                              <small class="text-muted">'.$time.'</small>
                            </div>
                          </div>
                        </a>';
                }elseif($account_type == 'parent' && $parent_id == $this->session->userdata('parent_id') || $account_type == 'student' && $row['student_id'] == $this->session->userdata('student_id')) {
                    echo '<a href="'.site_url('parents/mobile_money_checkout/'.$row['student_id']).'/view/'.$row['invoice_id'].'/'.$page_name.'/'.$row['timestamp'].'" class="list-group-item">
                          <div class="media">
                            <div class="user-status busy pull-left">
                            <img class="media-object img-circle pull-left" src="'.$this->crud_model->get_image_url('student',$row['student_id'], $gender).'" alt="'.$student_name.'" width="40">
                            </div>
                            <div class="media-body">
                              <h5 class="media-heading">Payment for '.$student_name.' is pending...</h5>
                              <small class="text-muted">'.$time.'</small>
                            </div>
                          </div>
                        </a>';
                }
                
        }
    }

    //Transaction ID Confrimation
    function confirm_transaction($student_id, $invoice_id, $parent_t_id, $admin_t_id) {
        $query_rows = $this->db->get_where('mobile_money_payment', array('student_id' => $student_id, 'invoice_id' => $invoice_id, 't_id' => $parent_t_id));
        if($query_rows->num_rows() > 0) {
            $query_array = $query_rows->result_array();
            foreach ($query_array as $row) {
                if($row['t_id'] == $admin_t_id) {
                    echo 'TRUE';
                }else{
                    echo 'FALSE';
                }
            }
        }
    }

    function notifications($page_name, $marked_read= '', $user_id = '', $logged_in_id = '') {
        $account_type       =   $this->session->userdata('login_type');

        //check if the user who logged in
            if($account_type == 'admin') {
                $session_id =  $this->session->userdata('admin_id');
            }elseif($account_type == 'accountant') {
                $session_id =  $this->session->userdata('accountant_id');
            }elseif($account_type == 'parent') {
                $session_id =  $this->session->userdata('parent_id');
            }elseif($account_type == 'teacher') {
                $session_id =  $this->session->userdata('teacher_id');
            }elseif($account_type == 'student') {
                $session_id =  $this->session->userdata('student_id');
            }elseif($account_type == 'librarian') {
                $session_id =  $this->session->userdata('librarian_id');
            }//end


        if($marked_read == 0 ) {
        
            if($logged_in_id == $session_id) {
                
                //upadate user's table with the notice id
                
                if($account_type == $account_type) {
                    //$this->db->select('read_notice_ids');
                    $this->db->where($account_type.'_id', $logged_in_id);
                    $prev_ids = $this->db->get($account_type)->row()->read_notice_ids;
                    $notice_query = $this->db->get_where('noticeboard', array('status' => '1')); 
                    $notice = $notice_query->result_array();
                    $xp_prev_ids = explode(',', $prev_ids);

                    $found_ids_array = array();
                    foreach($notice as $row) {

                        for($i = 0; $i < count($xp_prev_ids); $i++) {
                            if($xp_prev_ids[$i] == $row['notice_id']) {
                                
                                $found_ids_array[$i] = $xp_prev_ids[$i];
                            }
                        }
                    }
                    
                    //load noticeboard table where notice_ids != $found_ids_array elements
                    $found_ids_array;
                    
                    if(count($found_ids_array) > 0) {
                        $this->db->where_not_in('notice_id', $found_ids_array);
                        $this->db->where('status', '1');
                        $n_id_query = $this->db->get('noticeboard')->result_array();
                        

                        foreach($n_id_query as $row) {

                            //time calculation
                            $sent_time = $row['created_on'];
                            $current_time = strtotime(date('d-M-Y, H:i:s'));
                            $duration = $current_time - $sent_time;
                            $hour = ($duration / 3600);
                            $minute = ($hour * 60);
                            $second = ($minute * 60);
                            $time = '';
                            if($second <= 59) {
                                $time = 'Just now';
                            }else if($second >= 60 && $second <= 119) {
                                $time = '1 minute ago';
                            }else if($minute <= 59) {
                                $time = intval($minute). ' minutes ago';
                            }else if($minute >= 60 && $minute <= 119) {
                                $time = '1 hour ago';
                            }else if($hour <= 23) {
                                $time = intval($hour). ' hours ago';
                            }else if( $hour >= 24 && $hour <= 47) {
                                $time = '1 day ago';
                            }else if( $hour >= 48 && $hour <= 167) {
                                $time = intval($hour / 24). ' days ago';
                            }else if($hour >= 168 && ($hour / 24) <= 13) {
                                $time = '1 week ago';
                            }else if(($hour / 24) >= 14 && ($hour / 24) <= 29) {
                                $time = intval(($hour / 24) / 7). ' weeks ago';
                            }else if(($hour / 24) >= 30 && ($hour / 24) <= 59) {
                                $time = '1 month ago';
                            }else if(($hour / 24) >= 60 && ($hour / 24) <= 364) {
                                $time = intval(($hour / 24) / 30). ' months ago';
                            }else if(($hour / 24) >= 365 && ($hour / 24) <= 731) {
                                $time = '1 year ago';
                            }else if(($hour / 24) >= 732) {
                                $time = intval(($hour / 24) / 365). ' years ago';
                            }

                            echo '<a href="#" onclick="noticeboard('.$row['notice_id'].')" class="list-group-item">
                                  <div class="media">
                                    <div class="user-status busy pull-left">
                                    <img class="media-object img-square pull-left" src="'.base_url().'uploads/frontend/noticeboard/'.$row['image'].'" width="100">
                                    </div>
                                    <div class="media-body">
                                      <h4 class="media-heading message_list" style="margin-left: 5px;">'.$row['notice_title'].'...</h4>
                                      <small class="text-muted" style="margin-left: 5px;">'.$time.'</small>
                                    </div>
                                  </div>
                                </a>';
                        }
                    }else {
                        $n_id_query = $this->db->get_where('noticeboard', array('status' => '1'))->result_array();
                        foreach($n_id_query as $row) {

                            //time calculation
                            $sent_time = $row['created_on'];
                            $current_time = strtotime(date('d-M-Y, H:i:s'));
                            $duration = $current_time - $sent_time;
                            $hour = ($duration / 3600);
                            $minute = ($hour * 60);
                            $second = ($minute * 60);
                            $time = '';
                            if($second <= 59) {
                                $time = 'Just now';
                            }else if($second >= 60 && $second <= 119) {
                                $time = '1 minute ago';
                            }else if($minute <= 59) {
                                $time = intval($minute). ' minutes ago';
                            }else if($minute >= 60 && $minute <= 119) {
                                $time = '1 hour ago';
                            }else if($hour <= 23) {
                                $time = intval($hour). ' hours ago';
                            }else if( $hour >= 24 && $hour <= 47) {
                                $time = '1 day ago';
                            }else if( $hour >= 48 && $hour <= 167) {
                                $time = intval($hour / 24). ' days ago';
                            }else if($hour >= 168 && ($hour / 24) <= 13) {
                                $time = '1 week ago';
                            }else if(($hour / 24) >= 14 && ($hour / 24) <= 29) {
                                $time = intval(($hour / 24) / 7). ' weeks ago';
                            }else if(($hour / 24) >= 30 && ($hour / 24) <= 59) {
                                $time = '1 month ago';
                            }else if(($hour / 24) >= 60 && ($hour / 24) <= 364) {
                                $time = intval(($hour / 24) / 30). ' months ago';
                            }else if(($hour / 24) >= 365 && ($hour / 24) <= 731) {
                                $time = '1 year ago';
                            }else if(($hour / 24) >= 732) {
                                $time = intval(($hour / 24) / 365). ' years ago';
                            }

                           echo '<a href="#" onclick="noticeboard('.$row['notice_id'].')" class="list-group-item">
                                  <div class="media">
                                    <div class="user-status busy pull-left">
                                    <img class="media-object img-square pull-left" src="'.base_url().'uploads/frontend/noticeboard/'.$row['image'].'" width="100">
                                    </div>
                                    <div class="media-body">
                                      <h4 class="media-heading message_list" style="margin-left: 5px;">'.$row['notice_title'].'...</h4>
                                      <small class="text-muted" style="margin-left: 5px;">'.$time.'</small>
                                    </div>
                                  </div>
                                </a>';
                        }
                    }
                    
                }
            }   

        }else {

            if($user_id == $session_id) {
                
                //upadate user's table with the notice id
                
                if($account_type == $account_type) {
                    //$this->db->select('read_notice_ids');
                    $this->db->where($account_type.'_id', $logged_in_id);
                    $prev_ids = $this->db->get($account_type)->row()->read_notice_ids;
                    $notice_query = $this->db->get_where('noticeboard', array('status' => '1')); 
                    $notice = $notice_query->result_array();
                    $xp_prev_ids = explode(',', $prev_ids);

                    $found_ids_array = array();
                    foreach($notice as $row) {

                        for($i = 0; $i < count($xp_prev_ids); $i++) {
                            if($xp_prev_ids[$i] == $row['notice_id']) {
                                
                                $found_ids_array[$i] = $xp_prev_ids[$i];
                            }
                        }
                    }
                    
                    //load noticeboard table where notice_ids != $found_ids_array elements
                    $found_ids_array;
                    if(count($found_ids_array) > 0) {
                        $this->db->where_not_in('notice_id', $found_ids_array);
                        $this->db->where('status', '1');
                        $n_id_query = $this->db->get('noticeboard')->result_array();

                        foreach($n_id_query as $row) {
                            //time calculation
                            $sent_time = $row['created_on'];
                            $current_time = strtotime(date('d-M-Y, H:i:s'));
                            $duration = $current_time - $sent_time;
                            $hour = ($duration / 3600);
                            $minute = ($hour * 60);
                            $second = ($minute * 60);
                            $time = '';
                            if($second <= 59) {
                                $time = 'Just now';
                            }else if($second >= 60 && $second <= 119) {
                                $time = '1 minute ago';
                            }else if($minute <= 59) {
                                $time = intval($minute). ' minutes ago';
                            }else if($minute >= 60 && $minute <= 119) {
                                $time = '1 hour ago';
                            }else if($hour <= 23) {
                                $time = intval($hour). ' hours ago';
                            }else if( $hour >= 24 && $hour <= 47) {
                                $time = '1 day ago';
                            }else if( $hour >= 48 && $hour <= 167) {
                                $time = intval($hour / 24). ' days ago';
                            }else if($hour >= 168 && ($hour / 24) <= 13) {
                                $time = '1 week ago';
                            }else if(($hour / 24) >= 14 && ($hour / 24) <= 29) {
                                $time = intval(($hour / 24) / 7). ' weeks ago';
                            }else if(($hour / 24) >= 30 && ($hour / 24) <= 59) {
                                $time = '1 month ago';
                            }else if(($hour / 24) >= 60 && ($hour / 24) <= 364) {
                                $time = intval(($hour / 24) / 30). ' months ago';
                            }else if(($hour / 24) >= 365 && ($hour / 24) <= 731) {
                                $time = '1 year ago';
                            }else if(($hour / 24) >= 732) {
                                $time = intval(($hour / 24) / 365). ' years ago';
                            }

                           echo '<a href="#" onclick="noticeboard('.$row['notice_id'].')" class="list-group-item">
                                  <div class="media">
                                    <div class="user-status busy pull-left">
                                    <img class="media-object img-square pull-left" src="'.base_url().'uploads/frontend/noticeboard/'.$row['image'].'" width="100">
                                    </div>
                                    <div class="media-body">
                                      <h4 class="media-heading message_list" style="margin-left: 5px;">'.$row['notice_title'].'...</h4>
                                      <small class="text-muted" style="margin-left: 5px;">'.$time.'</small>
                                    </div>
                                  </div>
                                </a>';
                        }
                    }else {
                        $n_id_query = $this->db->get_where('noticeboard', array('status' => '1'))->result_array();
                        foreach($n_id_query as $row) {

                            //time calculation
                            $sent_time = $row['created_on'];
                            $current_time = strtotime(date('d-M-Y, H:i:s'));
                            $duration = $current_time - $sent_time;
                            $hour = ($duration / 3600);
                            $minute = ($hour * 60);
                            $second = ($minute * 60);
                            $time = '';
                            if($second <= 59) {
                                $time = 'Just now';
                            }else if($second >= 60 && $second <= 119) {
                                $time = '1 minute ago';
                            }else if($minute <= 59) {
                                $time = intval($minute). ' minutes ago';
                            }else if($minute >= 60 && $minute <= 119) {
                                $time = '1 hour ago';
                            }else if($hour <= 23) {
                                $time = intval($hour). ' hours ago';
                            }else if( $hour >= 24 && $hour <= 47) {
                                $time = '1 day ago';
                            }else if( $hour >= 48 && $hour <= 167) {
                                $time = intval($hour / 24). ' days ago';
                            }else if($hour >= 168 && ($hour / 24) <= 13) {
                                $time = '1 week ago';
                            }else if(($hour / 24) >= 14 && ($hour / 24) <= 29) {
                                $time = intval(($hour / 24) / 7). ' weeks ago';
                            }else if(($hour / 24) >= 30 && ($hour / 24) <= 59) {
                                $time = '1 month ago';
                            }else if(($hour / 24) >= 60 && ($hour / 24) <= 364) {
                                $time = intval(($hour / 24) / 30). ' months ago';
                            }else if(($hour / 24) >= 365 && ($hour / 24) <= 731) {
                                $time = '1 year ago';
                            }else if(($hour / 24) >= 732) {
                                $time = intval(($hour / 24) / 365). ' years ago';
                            }

                           echo '<a href="#" onclick="noticeboard('.$row['notice_id'].')" class="list-group-item">
                                  <div class="media">
                                    <div class="user-status busy pull-left">
                                    <img class="media-object img-square pull-left" src="'.base_url().'uploads/frontend/noticeboard/'.$row['image'].'" width="100">
                                    </div>
                                    <div class="media-body">
                                      <h4 class="media-heading message_list" style="margin-left: 5px;">'.$row['notice_title'].'...</h4>
                                      <small class="text-muted" style="margin-left: 5px;">' .$time.'</small>
                                    </div>
                                  </div>
                                </a>';
                        }
                    }
                    
                }
            }   
        }
        
    }

    function notifications_rows($marked_read = '', $user_id = '', $logged_in_id) {

        $account_type       =   $this->session->userdata('login_type');

        //check if the user who logged in
            if($account_type == 'admin') {
                $session_id =  $this->session->userdata('admin_id');
            }elseif($account_type == 'accountant') {
                $session_id =  $this->session->userdata('accountant_id');
            }elseif($account_type == 'parent') {
                $session_id =  $this->session->userdata('parent_id');
            }elseif($account_type == 'teacher') {
                $session_id =  $this->session->userdata('teacher_id');
            }elseif($account_type == 'student') {
                $session_id =  $this->session->userdata('student_id');
            }elseif($account_type == 'librarian') {
                $session_id =  $this->session->userdata('librarian_id');
            }//end

        if($marked_read == 0 ) {

            if($logged_in_id == $session_id) {

                //upadate user's table with the notice id
                
                if($account_type == $account_type) {
                    //$this->db->select('read_notice_ids');
                    $this->db->where($account_type.'_id', $logged_in_id);
                    $prev_ids = $this->db->get($account_type)->row()->read_notice_ids;
                    $notice_query = $this->db->get_where('noticeboard', array('status' => '1')); 
                    $notice = $notice_query->result_array();
                    $xp_prev_ids = explode(',', $prev_ids);

                    $found_ids_array = array();
                    foreach($notice as $row) {
                        for($i = 0; $i < count($xp_prev_ids); $i++) {
                            if($xp_prev_ids[$i] == $row['notice_id']) {
                                
                                $found_ids_array[$i] = $xp_prev_ids[$i];
                            }
                        }
                    }
                    
                   if(count($found_ids_array > 0)) {
                         echo $notice_query->num_rows() - count($found_ids_array);
                   }else {
                         echo $notice_query->num_rows();
                   }
                }   

            }
        }else{

            if($user_id == $session_id) {
                
                //upadate user's table with the notice id
                
                if($account_type == $account_type) {
                //$this->db->select('read_notice_ids');
                $this->db->where($account_type.'_id', $user_id);
                $prev_ids = $this->db->get($account_type)->row()->read_notice_ids;
                $xp_prev_ids = explode(',', $prev_ids);
                $counter = 0; //set counter to count the number of times the current marked_read is repeated in the table
                                //if it counts 1, dupplicate, don't update, if it counts 0, update
                    for($i = 0; $i < count($xp_prev_ids); $i++) {

                        if($xp_prev_ids[$i] === $marked_read) {
                            $counter++;
                        }
                    }

                    if($counter <= 0) {
                        $this->db->where($account_type.'_id', $user_id);
                        $this->db->set('read_notice_ids', $prev_ids.','.$marked_read);
                        $this->db->limit(1);
                        $this->db->update($account_type);
                    }
                    
                    //now update the number of rows status on the message alert area
                    $this->db->where($account_type.'_id', $user_id);
                    $prev_ids = $this->db->get($account_type)->row()->read_notice_ids;
                    $notice_query = $this->db->get_where('noticeboard', array('status' => '1')); 
                    $notice = $notice_query->result_array();
                    $xp_prev_ids = explode(',', $prev_ids);

                    $found_ids_array = array();
                    foreach($notice as $row) {
                        for($i = 0; $i < count($xp_prev_ids); $i++) {
                            if($xp_prev_ids[$i] == $row['notice_id']) {
                                
                                $found_ids_array[$i] = $xp_prev_ids[$i];
                            }
                        }
                    }
                    
                    if(count($found_ids_array > 0)) {
                         echo $notice_query->num_rows() - count($found_ids_array);
                   }else {
                         echo $notice_query->num_rows();
                   }
                }
            
            }
        }
    }

    function auth_verification($auth_key) {
        $tables = array('admin', 'accountant', 'librarian', 'parent', 'student', 'teacher');
        
        $t_counter = 0;
        $block_counter = 0;
        $student_table = 0;

        for($i = 0; $i < count($tables); $i++) {
             $user_row = $this->db->get_where($tables[$i], array('authentication_key' => $auth_key))->row();
             $query = $this->db->get_where($tables[$i], array('authentication_key' => $auth_key))->num_rows();
             
             if($user_row && $user_row->block_limit == 3) {
                $block_counter++;
             } else {
                if($query > 0) {
                    $t_counter++;

                    //check if the it was verified from students' table
                    if($tables[$i] == 'student') {
                        $student_table = 1;
                    }
                }
             }
        }

        if($block_counter > 0) {
            echo 'blocked';
        } else {
            if($t_counter > 0 && $student_table == 1) {
                echo 'true_student';
            } elseif($t_counter > 0 && $student_table == 0 ){
                echo 'true';
            }else {
                echo 'false';
            }
        }
    }

    //check internal messaging alert
    function check_internal_m($current_user) {
       $this->db->where('sender', $current_user);
       $this->db->or_where('reciever', $current_user);
       $query = $this->db->get('message_thread')->result_array();

       $this->db->where('sender', $current_user);
       $this->db->or_where('reciever', $current_user);
       $query_rows = $this->db->get('message_thread')->num_rows();

       $data_array = array();
       $i = 0;
       foreach($query as $row) {

               $data_array[$i] = $row['message_thread_code'];
               $i++;
        }

        $this->db->where('read_status', NULL);
        $this->db->where('sender !=', $current_user);
        $this->db->where_in('message_thread_code', $data_array);
        $query2 = $this->db->get('message');
        $num = $query2->num_rows();

        echo $num;
    }

    //check internal messaging alert2
    function internal_m($current_user) {

       $this->db->where('sender', $current_user);
       $this->db->or_where('reciever', $current_user);
       $query = $this->db->get('message_thread')->result_array();

       $this->db->where('sender', $current_user);
       $this->db->or_where('reciever', $current_user);
       $query_rows = $this->db->get('message_thread')->num_rows();

       $data_array = array();
       $i = 0;
       foreach($query as $row) {

               $data_array[$i] = $row['message_thread_code'];
               $i++;
        }

        $this->db->where('read_status', NULL);
        $this->db->where('sender', $current_user);
        $this->db->where_in('message_thread_code', $data_array);
        $query2 = $this->db->get('message');
        $num = $query2->num_rows();

        //echo $num;
    }
    
    //for importing subjects
   function do_subjects_import($new_class_id, $old_class_id, $year, $term, $class_name='') {

    if($class_name == 'JHSS') {
        ///select all the subjects from the subject table where class_id == old_class_id and year and the sem ==...
        $data_array = array(
            'class_id' => $old_class_id,
            'year'     => $year,
            'sem'     => $term
            );
        
        $data_array2 = array(
            'class_id' => $new_class_id,
            'year'     => $year,
            'sem'     => $term
            );
            
        $subjects_rows = $this->db->get_where('subject', $data_array2)->num_rows();
        
        //check if this class already exists in the subject table with same year and sem
        if($subjects_rows > 0) { //send an error message because user is trying to run this query for a class that already has registered subjects
            echo 'error';
        } else { //go ahead
        
           $subjects = $this->db->get_where('subject', $data_array)->result_array();
        
            //now let's do the insertion
            $ij = 0;
            foreach($subjects as $row) {
                $num_sems = 0;
                $sem_create = 0;
                $data['sem']       = $row['sem'];///current sem
                
                //Number of times to loop
                if($data['sem'] == 1) {
                    $num_sems = 2;
                    $sem_create = 1;
                } else if($data['sem'] == 2) {
                    $num_sems = 1;
                    $sem_create = 2;
                }
                
                //do the looping now
                for($i = 1; $i <= $num_sems; $i++) {
                    
                     $data['name']       = $row['name'];
                     $data['status']     = $row['status'];
                     $data['class_id']   = $new_class_id;
                     $data['year']       = $row['year'];
                     $data['sem']       = $sem_create;
                     $data['teacher_id'] = $row['teacher_id'];
                     
                     if($this->db->insert('subject', $data)) {
                         $ij++;
                    };
                    
                    //increase semester by 1
                     $sem_create++;
                }
                 
            } 
            
            if($ij > 0) {
                echo 'success';
            } else {
                echo 'failed';
            }
         }
    } else {
        ///select all the subjects from the subject table where class_id == old_class_id and year and the term ==...
        $data_array = array(
            'class_id' => $old_class_id,
            'year'     => $year,
            'term'     => $term
            );
        
        $data_array2 = array(
            'class_id' => $new_class_id,
            'year'     => $year,
            'term'     => $term
            );
            
        $subjects_rows = $this->db->get_where('subject', $data_array2)->num_rows();
        
        //check if this class already exists in the subject table with same year and term
        if($subjects_rows > 0) { //send an error message because user is trying to run this query for a class that already has registered subjects
            echo 'error';
        } else { //go ahead
        
           $subjects = $this->db->get_where('subject', $data_array)->result_array();
        
            //now let's do the insertion
            $ij = 0;
            foreach($subjects as $row) {
                $num_terms = 0;
                $term_create = 0;
                $data['term']       = $row['term'];///current term
                
                //Number of times to loop
                if($data['term'] == 1) {
                    $num_terms = 3;
                    $term_create = 1;
                } else if($data['term'] == 2) {
                    $num_terms = 2;
                    $term_create = 2;
                } else if($data['term'] == 3) {
                    $num_terms = 1;
                    $term_create = 3;
                }
                
                //do the looping now
                for($i = 1; $i <= $num_terms; $i++) {
                    
                     $data['name']       = $row['name'];
                     $data['status']     = $row['status'];
                     $data['class_id']   = $new_class_id;
                     $data['year']       = $row['year'];
                     $data['term']       = $term_create;
                     $data['teacher_id'] = $row['teacher_id'];
                     
                     if($this->db->insert('subject', $data)) {
                         $ij++;
                    };
                    
                    //increase term by 1
                     $term_create++;
                }
                 
            } 
            
            if($ij > 0) {
                echo 'success';
            } else {
                echo 'failed';
            }
         }
    }
    
   }
   
   
   //for importing subjects creche
   function do_subjects_import_creche($new_class_id, $old_class_id, $year, $term) {
    ///select all the subjects from the subject_creche table where class_id == old_class_id and year and the term ==...
    $data_array = array(
        'class_id' => $old_class_id,
        'year'     => $year,
        'term'     => $term
        );
    
    $data_array2 = array(
        'class_id' => $new_class_id,
        'year'     => $year,
        'term'     => $term
        );
        
    $subjects_rows = $this->db->get_where('subject_creche', $data_array2)->num_rows();
    
    //check if this class already exists in the subject table with same year and term
    if($subjects_rows > 0) { //send an error message because user is trying to run this query for a class that already has registered subjects
        echo 'error';
    } else { //go ahead
    
       $subjects = $this->db->get_where('subject_creche', $data_array)->result_array();
    
        //now let's do the insertion
        $ij = 0;
        foreach($subjects as $row) {
            $num_terms = 0;
            $term_create = 0;
            $data['term']       = $row['term'];///current term
            
            //Number of times to loop
            if($data['term'] == 1) {
                $num_terms = 3;
                $term_create = 1;
            } else if($data['term'] == 2) {
                $num_terms = 2;
                $term_create = 2;
            } else if($data['term'] == 3) {
                $num_terms = 1;
                $term_create = 3;
            }
            
            //do the looping now
            for($i = 1; $i <= $num_terms; $i++) {
                
                 $data['name']       = $row['name'];
                 $data['category_id']       = $row['category_id'];
                 $data['status']     = $row['status'];
                 $data['class_id']   = $new_class_id;
                 $data['year']       = $row['year'];
                 $data['term']       = $term_create;
                 $data['teacher_id'] = $row['teacher_id'];
                 
                 if($this->db->insert('subject_creche', $data)) {
                     $ij++;
                };
                
                //increase term by 1
                 $term_create++;
            }
             
        } 
        
        if($ij > 0) {
            echo 'success';
        } else {
            echo 'failed';
        }
     }
   }
   
   
   //mass subject importation
   function do_subjects_import_mass($fromTerm = '') {
       $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
       $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;

       $counter = 0;
      // $running_sem = $this->db->get_where('settings' , array('type'=>'running_sem'))->row()->description;
       
       //run this query to check if subjects were already registered for this year and term
       $this->db->select('class_id');
       $class_ids = $this->db->get('class')->result_array();

       foreach($class_ids as $class_id):
       $check_subjects = $this->db->get_where('subject', array('class_id' => $class_id['class_id'], 'year' => $running_year, 'term' => $running_term))->num_rows();
       //$check_subjects_jhs = $this->db->get_where('subject', array('year' => $running_year, 'sem' => $running_sem))->num_rows();

      if($check_subjects > 0) { //error
           $counter++;
       } else {
        //termly
          //  if($check_subjects < 1) {
               $prev_year_explode = explode('-', $running_year);
               $prev_year_dg1 = $prev_year_explode[0] - 1;
               $prev_year_dg2 = $prev_year_explode[1] - 1;
               $prev_year = trim($prev_year_dg1.'-'.$prev_year_dg2);
               
               //run query
               //$prev_term = $this->db->get_where('subject', array('year' => $prev_year))->row()->term;
               if($fromTerm == 'yes') {
                //just for this term
                $all_subjects = $this->db->get_where('subject', array('class_id' => $class_id['class_id'], 'year' => $running_year, 'term' => $running_term - 1));

               } else {
                //for the whole year - get a representative set of subjects from any term in previous year
                // We'll use this as a template to create subjects for all 3 terms in the new year
                $all_subjects = $this->db->get_where('subject', array('class_id' => $class_id['class_id'], 'year' => $prev_year));
               }
               

               if($all_subjects->num_rows() > 0) { //if result is greater than 0, then do the dubbing
                 $all_subjects_array = $all_subjects->result_array();
                 
                 if($fromTerm == 'yes') {
                    // Term change within same year - just copy to current term
                    foreach($all_subjects_array as $row) {
                        $data['name']       = $row['name'];
                        $data['status']     = $row['status'];
                        $data['class_id']   = $row['class_id'];
                        $data['year']       = $running_year;
                        $data['term']       = $running_term;
                        $data['teacher_id'] = $row['teacher_id'];

                        $check_array = [
                            'name' => $row['name'],
                            'class_id' => $row['class_id'],
                            'year' => $running_year,
                            'term' => $running_term,
                        ];

                        $sub_exists_row = $this->db->get_where('subject', $check_array)->num_rows();

                        if($sub_exists_row > 0) {
                            continue;
                        } else {
                            $this->db->insert('subject', $data);
                        }
                    }
                 } else {
                    // Year change - create subjects for ALL 3 terms intelligently
                    // First, get unique subjects (distinct by name) from previous year
                    $unique_subjects = [];
                    foreach($all_subjects_array as $row) {
                        $subject_key = $row['name'] . '_' . $row['class_id'];
                        if(!isset($unique_subjects[$subject_key])) {
                            $unique_subjects[$subject_key] = $row;
                        }
                    }
                    
                    // Now create these subjects for all 3 terms in the new year
                    foreach($unique_subjects as $row) {
                        for($term = 1; $term <= 3; $term++) {
                            $data['name']       = $row['name'];
                            $data['status']     = $row['status'];
                            $data['class_id']   = $row['class_id'];
                            $data['year']       = $running_year;
                            $data['term']       = $term;
                            $data['teacher_id'] = $row['teacher_id'];

                            $check_array = [
                                'name' => $row['name'],
                                'class_id' => $row['class_id'],
                                'year' => $running_year,
                                'term' => $term,
                            ];

                            $sub_exists_row = $this->db->get_where('subject', $check_array)->num_rows();

                            if($sub_exists_row > 0) {
                                continue;
                            } else {
                                $this->db->insert('subject', $data);
                            }
                        }
                    }
                 }


               }   
           }
         endforeach;

         if($counter > 0) {
            echo 'error';
         }
           //semester
         /*  if($check_subjects_jhs < 1) {
               $prev_year_explode = explode('-', $running_year);
               $prev_year_dg1 = $prev_year_explode[0] - 1;
               $prev_year_dg2 = $prev_year_explode[1] - 1;
               $prev_year = trim($prev_year_dg1.'-'.$prev_year_dg2);
               
               //run query
               $prev_sem = $this->db->get_where('subject', array('year' => $prev_year))->row()->sem;
               $all_subjects = $this->db->get_where('subject', array('year' => $prev_year, 'sem' => $prev_sem));

               if($all_subjects->num_rows() > 0) { //if result is greater than 0, then do the dubbing
                 $all_subjects_array = $all_subjects->result_array();
                 foreach($all_subjects_array as $row) {
                     $data['name']       = $row['name'];
                     $data['status']     = $row['status'];
                     $data['class_id']   = $row['class_id'];
                     $data['year']       = $running_year;
                     $data['sem']       = $row['sem'];
                     $data['teacher_id'] = $row['teacher_id'];
                     
                     //Now let's insert them
                     $this->db->insert('subject', $data);
                 }
               }  
           }**/
      // }
       
       
   }
   
   //mass subject importation for creche
   function do_subjects_import_mass_creche($fromTerm = '') {
       $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
       $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
       
       //run this query to check if subjects were already registered for this year and term
       $check_subjects = $this->db->get_where('subject_creche', array('year' => $running_year, 'term' => $running_term))->num_rows();
       if($check_subjects > 0) { //error
            echo 'error';
       } else {
           $prev_year_explode = explode('-', $running_year);
           $prev_year_dg1 = $prev_year_explode[0] - 1;
           $prev_year_dg2 = $prev_year_explode[1] - 1;
           $prev_year = trim($prev_year_dg1.'-'.$prev_year_dg2);
           
           //run query
           if($fromTerm == 'yes') {
                //just for this term
                $all_subjects = $this->db->get_where('subject_creche', array('year' => $running_year, 'term' => $running_term - 1));

               } else {
                //for the whole year
                $all_subjects = $this->db->get_where('subject_creche', array('year' => $prev_year));
               }

           if($all_subjects->num_rows() > 0) { //if result is greater than 0, then do the dubbing
             $all_subjects_array = $all_subjects->result_array();
             
             if($fromTerm == 'yes') {
                // Term change within same year - just copy to current term
                foreach($all_subjects_array as $row) {
                    $data['name']       = $row['name'];
                    $data['category_id']   = $row['category_id'];
                    $data['status']     = $row['status'];
                    $data['class_id']   = $row['class_id'];
                    $data['year']       = $running_year;
                    $data['term']       = $running_term;
                    $data['teacher_id'] = $row['teacher_id'];
                    
                    $check_array = [
                        'name' => $row['name'],
                        'class_id' => $row['class_id'],
                        'year' => $running_year,
                        'term' => $running_term,
                    ];

                    $sub_exists_row = $this->db->get_where('subject_creche', $check_array)->num_rows();

                    if($sub_exists_row > 0) {
                        continue;
                    } else {
                        $this->db->insert('subject_creche', $data);
                    }
                }
             } else {
                // Year change - create subjects for ALL 3 terms intelligently
                // First, get unique subjects (distinct by name and category_id)
                $unique_subjects = [];
                foreach($all_subjects_array as $row) {
                    $subject_key = $row['name'] . '_' . $row['category_id'] . '_' . $row['class_id'];
                    if(!isset($unique_subjects[$subject_key])) {
                        $unique_subjects[$subject_key] = $row;
                    }
                }
                
                // Now create these subjects for all 3 terms in the new year
                foreach($unique_subjects as $row) {
                    for($term = 1; $term <= 3; $term++) {
                        $data['name']       = $row['name'];
                        $data['category_id']   = $row['category_id'];
                        $data['status']     = $row['status'];
                        $data['class_id']   = $row['class_id'];
                        $data['year']       = $running_year;
                        $data['term']       = $term;
                        $data['teacher_id'] = $row['teacher_id'];
                        
                        $check_array = [
                            'name' => $row['name'],
                            'class_id' => $row['class_id'],
                            'year' => $running_year,
                            'term' => $term,
                        ];

                        $sub_exists_row = $this->db->get_where('subject_creche', $check_array)->num_rows();

                        if($sub_exists_row > 0) {
                            continue;
                        } else {
                            $this->db->insert('subject_creche', $data);
                        }
                    }
                }
             }
           }  
       }      
   }

   function load_receipt($receipt_code, $invoice_code, $student_id, $year, $term) {

        $this->db->where('can_delete !=', 'trash');
        $receipt_querry = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'invoice_code' => $invoice_code, 'student_id' => $student_id, 'year' => $year, 'term' => $term))->result_array();


        $i = 1;
        foreach($receipt_querry as $row) {
             
          echo  '
                <tr>
                    <td>'.$i.'</td>
                    <td align="left">'. $row['title'].'</td>
                    <td></td>
                    <td align="right">'. $row['amount'].'</td>
                </tr>
            ';

            $i++;

        }

        
   }

   function find_student($search) {
        $search = urldecode($search);
        //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

       /** $this->db->select('invoice_code');
        $this->db->distinct();
        $num_invoice_rows = $this->db->get('invoice')->num_rows();
        $limit = $num_invoice_rows; **/ //i have reset the limit value to the number of rows returned for an invoice code per search.

                $query = $this
                ->db
                /**->like('invoice_id', $search)
                ->or_like('title', $search)
                ->or_like('amount', $search)
                ->or_like('year', $search)
                ->or_like('term', $search)**/
                //->like('invoice_code', $search)
                //->or_like('status', $search)
                ->like('name', $search, 'before')
                ->or_like('name', $search, 'after')
                ->join('student', 'student.student_id = invoice.student_id')
                ->select('invoice_code') //let's select just unique invoice code at a time
                ->distinct() //distinct invoice codes selection
                //->limit($limit)
                ->where('can_delete !=', 'trash')
                ->order_by('invoice_code', 'desc')
                ->get('invoice');
        
        if($query->num_rows() > 0) {
            $invoices = $query->result();

            foreach ($invoices as $row) {

                if($row->invoice_code[0] == 0) {
                        $in_code = '_'.$row->invoice_code;
                } else {
                    $in_code = $row->invoice_code;
                }

                //amount due
                $this->db->select_sum('due');
                $a_due = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->due;

                //total amount
                $this->db->select_sum('amount');
                $total_amount = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->amount;

                //total amount paid
                $this->db->select_sum('amount_paid');
                $amount_paid = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->amount_paid;

                //year and term
                $year = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->year;
                //year and term
                $term = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->term;

                //creation date
                $creation_timestamp = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->creation_timestamp;

                //student_id
                $student_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->student_id;

                //current_class id
                $class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'mute' => '0', 'year' => $running_year, 'term' => $running_term))->row()->class_id;
                $class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
                $class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

                //add section A or B if the class has more than one section
                $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                $sec_name = '';
                if($class_has_more_sections > 1) {
                    $sec_name = $section_name;
                }
                $student_class = $class_name.' '. $class_name_numeric.$sec_name;

                //invoice_id
                $invoice_id = $this->db->get_where('invoice', array('invoice_code' => $row->invoice_code))->row()->invoice_id;
                

                if ($a_due == 0) {
                    $status = '<button class="btn btn-success btn-xs">'.get_phrase('paid').'</button>';
                    //$payment_text = 'View Receipts';
                }elseif ($a_due < 0) {
                    $status = '<button class="btn btn-warning btn-xs">'.get_phrase('over_paid').'</button>';
                    //$payment_text = 'View Receipts';
                } else {
                    $status = '<button class="btn btn-danger btn-xs">'.get_phrase('unpaid').'</button>';
                    
                    //$payment_text = 'Take Payment';
                }

                $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$student_id.')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a></li><li class="divider"></li><li><a href="#" onclick="view_receipts_modal('.$student_id.')" style="color: #d803f8;"><i class="entypo-eye"></i>&nbsp;View Receipts</a></li><li class="divider"></li>';

                $bulk_invoice_sel = '
                        <input type="checkbox" class="checkbox" onclick="boxChecked2()" name="invoices_sel[]" value="'.$row->invoice_code.'">
                        
                        ';
                    
                
                $options = '<div class="btn-group"><button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                                    Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$in_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a></li><li class="divider"></li>

                                    <li><a href="#" onclick="bulk_invoice_view_modal('.$student_id.')" style="color: black;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_bulk_invoice').'</a></li><li class="divider"></li>

                                    <li class="divider"></li><li><a href="#" onclick="invoice_edit_modal(\''.$in_code.'\')" style="color: green;"><i class="entypo-pencil"></i>&nbsp;'.get_phrase('edit').'</a></li><li class="divider"></li><li><a href="#" onclick="invoice_delete_confirm('.$in_code.')" style="color: red;"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';
                $tbody = '<tr>
                            <td>'.$bulk_invoice_sel.'</td>
                            <td>'.$row->invoice_code.'</td>
                            <td>'.$this->crud_model->get_type_name_by_id('student',$student_id).'</td>
                            <td>'.$student_class.'</td>
                            <td>'.$year.'|'.$term.'</td>
                            <td>'.numfmt_format_currency($fmt, $total_amount, $currency).'</td>
                            <td>'.numfmt_format_currency($fmt, $amount_paid, $currency).'</td>
                            <td>'.$status.'</td>
                            <td>'.date('d M, Y', $creation_timestamp).'</td>
                            <td>'.$options.'</td>
                          </tr>

                ';

                echo $tbody;
            }
        } else {
            echo '<tr>
                    <td colspan="10" align="center"><h4 style="color: red">No Record Found For This Search! Please Try Again With Either The First or The Last Name</h4></td>
                  </tr>';
        }
   }

   function get_class_by_id($class_id) {

        $classes = $this->db->get('class')->result_array();
        foreach($classes as $row):

            //add section A or B if the class has more than one section
            $class_name         =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
            $class_name_numeric =   $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
            $sec_name = '';
            if($class_has_more_sections > 1) {
                $sec_name = $section_name;
            }

            if($class_name == 'CRECHE') {
                $creche_name = ucwords(strtolower($class_name)).$sec_name;
            } else {
                $creche_name = ucwords(strtolower($class_name)). ' '. $class_name_numeric.$sec_name;
            }

             return $creche_name;
         endforeach;
    }


    //select all students in a particular class for this date and enter them into the attendance table in the database
    function attendance_selector($class_id='', $section_id='', $year='', $term='') {

            $data['class_id']   = $class_id;
            $data['year']       = $year;
            $data['term']       = $term;
            $data['timestamp']  = strtotime(date('d-m-Y'));//we won't send this from the scanner page.
            $data['section_id'] = $section_id;


        //check the attendance table and see if these records match any
        $query = $this->db->get_where('attendance' ,array(
            'class_id'=>$data['class_id'],
                'section_id'=>$data['section_id'],
                    'year'=>$data['year'],
                        'term'=>$data['term'],
                            'timestamp'=>$data['timestamp']
        ));

        if($query->num_rows() < 1) {//no record match found

            /**Run a query from the enroll table with current year and current term
            *if no match record is found, it means no student has been enrolled for this term
            *thus, we need to do new enrollment for this term, else, we continue with normal daily attendance mgt
            **/
                $students_old = $this->db->get_where('enroll' , array(
                    'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'term' => $data['term']
                ));
                $students_old_array = $students_old->result_array();

                if($students_old->num_rows() < 1) { //no record match found
                    //Let's do new enrollment into a new term with same year

                    /**if term is 1 and the running year is the same as the year retrieved from the enroll table, it means user is **trying to enroll students to term 1 instead of doing promotion in the previous term. inform the user to contact *the system administrator for help**/
                    $running_term = $data['term'];
                    $running_year = $this->db->get_where('enroll' , array(
                        'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'term' => $running_term))->row()->year;

                    if($running_term == 1 && $running_year == $data['year']) {
                        $errors['error_message'] = get_phrase('make_sure_you_promote_students_during_term_3._please_contact_the_administrator_for_assistance');
                    }
                    
                    /**if not, let's now enroll them into new term by creating new enrollment for them from the 
                    *previous term and insert them into the enroll table
                    *and subsequently entering their details into the attendance table
                    **/

                    $students_new = $this->db->get_where('enroll' , array(
                    'class_id' => $data['class_id'] , 'mute' => '0', 'section_id' => $data['section_id'] , 'year' => $data['year'], 'term' => $data['term']-1
                    ))->result_array();

                    foreach($students_new as $row) {
                        $enroll_data['enroll_code']= substr(md5(rand(0, 1000000)), 0, 7);
                        $enroll_data['class_id']   = $data['class_id'];
                        $enroll_data['year']       = $data['year'];
                        $enroll_data['term']       = $data['term'];
                        $enroll_data['section_id'] = $data['section_id'];
                        $enroll_data['student_id'] = $row['student_id'];
                        $enroll_data['date_added'] =   strtotime(date("Y-m-d H:i:s"));
                        
                        $this->db->insert('enroll' , $enroll_data);

                        $attn_data['class_id']   = $data['class_id'];
                        $attn_data['year']       = $data['year'];
                        $attn_data['term']       = $data['term'];
                        $attn_data['timestamp']  = $data['timestamp'];
                        $attn_data['section_id'] = $data['section_id'];
                        $attn_data['student_id'] = $row['student_id'];

                        $this->db->insert('attendance' , $attn_data);

                        $this->db->where('student_id' , $row['student_id']);
                        $this->db->update('enroll', array('status_attendance' => 'close'));

                    }
                }elseif($students_old->num_rows() > 0) {
                        //it is a normal daily attendance management. pull data from enroll table and insert them to att. tb
                        foreach($students_old_array as $row) {

                        $attn_data['class_id']   = $data['class_id'];
                        $attn_data['year']       = $data['year'];
                        $attn_data['term']       = $data['term'];
                        $attn_data['timestamp']  = $data['timestamp'];
                        $attn_data['section_id'] = $data['section_id'];
                        $attn_data['student_id'] = $row['student_id'];

                        $this->db->insert('attendance' , $attn_data);

                        $this->db->where('student_id' , $row['student_id']);
                        $this->db->update('enroll', array('status_attendance' => 'close'));
                        
                    }
                }

        }elseif ($query->num_rows() > 0) {
            /**if we find some rows, it means these rows are the recent attendance marked and want to be updated. But let's find out if there are additional students that were enrolled within the term and needs
            *to be added to the attendance register for further management processes
            **/
             $students_lagged = $this->db->get_where('enroll' , array(
                'class_id' => $data['class_id'] , 'section_id' => $data['section_id'] , 'mute' => '0', 'year' => $data['year'], 'term' => $data['term'], 'status_attendance' => 'open' 
            ))->result_array();
            foreach($students_lagged as $row) {
                $attn_data['class_id']   = $data['class_id'];
                $attn_data['year']       = $data['year'];
                $attn_data['term']       = $data['term'];
                $attn_data['timestamp']  = $data['timestamp'];
                $attn_data['section_id'] = $data['section_id'];
                $attn_data['student_id'] = $row['student_id'];
                $this->db->insert('attendance' , $attn_data);

                $this->db->where('student_id' , $row['student_id']);
                $this->db->update('enroll', array('status_attendance' => 'close'));
            }
           
        }

        //clear the cached database
        $this->db->cache_delete('admin', 'student_marksheet');
        $this->db->cache_delete('admin', 'student_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('admin', 'student_marksheet_creche');
        $this->db->cache_delete('admin', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('admin', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet_creche');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('admin', 'student_results_sheet');
        $this->db->cache_delete('admin', 'student_results_sheet_print_view');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet');
        $this->db->cache_delete('admin', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('teacher', 'student_marksheet');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('teacher', 'student_marksheet_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('teacher', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet_creche');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('teacher', 'student_results_sheet');
        $this->db->cache_delete('teacher', 'student_results_sheet_print_view');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet');
        $this->db->cache_delete('teacher', 'student_raw_score_results_sheet_print_view');

        //clear the cached database
        $this->db->cache_delete('student', 'student_marksheet');
        $this->db->cache_delete('student', 'student_marksheet_print_view');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_marksheet_bulk_print_view');

        $this->db->cache_delete('student', 'student_marksheet_creche');
        $this->db->cache_delete('student', 'student_marksheet_print_view_creche');
        $this->db->cache_delete('student', 'student_marksheet_bulk_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet_creche');
        $this->db->cache_delete('student', 'student_results_sheet_print_view_creche');

        $this->db->cache_delete('student', 'student_results_sheet');
        $this->db->cache_delete('student', 'student_results_sheet_print_view');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet');
        $this->db->cache_delete('student', 'student_raw_score_results_sheet_print_view');

        $this->db->cache_delete('admin', 'student_information');
        $this->db->cache_delete('admin', 'student_profile');
        $this->db->cache_delete('admin', 'student_profile_raw_score');

        $this->db->cache_delete('teacher', 'student_information');
        $this->db->cache_delete('teacher', 'student_profile');
        $this->db->cache_delete('teacher', 'student_profile_raw_score');

        $this->db->cache_delete('student', 'student_information');
        $this->db->cache_delete('student', 'student_profile');
        $this->db->cache_delete('student', 'student_profile_raw_score');

        if(!empty($errors)) {
            return json_encode($errors);
        }
    }

    //for barcode scanner
    function get_all_barcode_scanned_students($category, $timestamp) {

        //for check-in
        if($category == 'check_in') {
            $check_in_query = $this->db->get_where('attendance', array('timestamp' => $timestamp, 'checked_in' => 1));
            if($check_in_query->num_rows() > 0) {
                $request_array = $check_in_query->result_array();

            } else {
                $rows = 'none';
            }
        }

        //for check
        else if($category == 'check_out') {
            $check_out_query = $this->db->get_where('attendance', array('timestamp' => $timestamp, 'checked_out' => 1));
            if($check_out_query->num_rows() > 0) {
                $request_array = $check_out_query->result_array();

            } else {
                $rows = 'none';
            }
        }

        //for feeding
        else if($category == 'feeding') {
            $feeding_query = $this->db->get_where('feeding_fee_payment', array('day_timestamp' => $timestamp));
            if($feeding_query->num_rows() > 0) {
                $request_array = $feeding_query->result_array();

            } else {
                $rows = 'none';
            }
        }

        //for classes
        else if($category == 'classes') {
            $classes_query = $this->db->get_where('classes_fee_payment', array('day_timestamp' => $timestamp));
            if($classes_query->num_rows() > 0) {
                $request_array = $classes_query->result_array();

            } else {
                $rows = 'none';
            }
        }

        //for transport
        else if($category == 'transport') {
            $transport_query = $this->db->get_where('transport_fare_payment', array('day_timestamp' => $timestamp));
            if($transport_query->num_rows() > 0) {
                $request_array = $transport_query->result_array();

            } else {
                $rows = 'none';
            }
        }
        //end of the queries

        //now, let's send the array to the page for further processing
        $page_data['request_query'] = $request_array;
        $page_data['rows'] = $rows;
        $page_data['category'] = $category;
        $page_data['timestamp'] = $timestamp;

        $this->load->view('backend/admin/get_barcode_scanner_view', $page_data);
    }

    //get exam category
    function get_exam_category($category_id) {
        $category = $this->db->get_where('exam_category', array('category_id' => $category_id))->row();
        return $category && isset($category->category_name) ? $category->category_name : 'Unknown Category';
    }

    //get students here
    function getStudentNameById($student_id) {
        return $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
    }

    //trying to automate next term given the year
    function automateNextTerm($running_year, $force="no") {
        $this->db->where('year', $running_year);
        $year_row_query = $this->db->get('enroll');
        $year_row = $year_row_query->num_rows();

        if($year_row > 0) {

            //user wants a new enrollment into Academic year and term
            //let's find out if students were promoted from the third term, else we deny
            $running_term = get_settings('running_term');
            if($running_term == 3) {
                //if the user is changing academic session, we want to cross check
                //and see if all promotions were done before we proceed

                //if user forces for change of academic year, we proceed
                if($force == "yes") {
                    $students_enrolled = true;
                } else {
                    $students_enrolled = $this->students_enrolled($running_year, '1');
                }
                

                if(is_array($students_enrolled)) {
                    return 'not_enrolled';
                } else {
                    //let's see if the user is changing the whole academic season
                    $last_row_term = $year_row_query->last_row();
                    if($last_row_term->term == 1) {
                        //yes, we need to change everything and do all the updates
                        //update the table now
                        //promotions were done for next academic season already so let's update everything
                      $data_t['description'] = 1;
                      $this->db->where('type', 'running_term');
                      $this->db->update('settings', $data_t);

                      return 1; //update update everything

                    } else {
                       return false; //update update the year
                    }
                }
            } else {
                //all enrollment is done, let's go ahead
                return false;
            }
            

            
        } else {
                      
            $exp_r = explode('-', get_settings('running_year'));
            $exp_r2 = $exp_r[1]; //running year second value
            $exp_s = explode('-', $running_year);
            $exp_s2 = $exp_s[1]; //selected year second value

            if($exp_r2 > $exp_s2) {

                //invalid year selected if the selected year does not exist in the enroll table
                //and it is lesser than the currently running year
                return 'year not found';
            }

            //let's select the prev year and get the term to be updated
            $exploded_year = explode('-', $running_year);
            $prev_y1 = $exploded_year[0] - 1;
            $prev_y2 = $exploded_year[1] - 1;
            $prev_year = $prev_y1.'-'.$prev_y2;

            $this->db->where('year', $prev_year);
            $get_pre_year = $this->db->get('enroll');
            $prev_year_row = $get_pre_year->num_rows();

            

            if($prev_year_row < 1) {
                //invalid year selected
                return 'invalid year';

            } else {
                //all is correct now
                $prev_term = $get_pre_year->last_row()->term;
                
                if($prev_term == 3) { //can only update if the term found is 3
                    $next_term = '1';

                    //user wants a new enrollment into Academic year and term
                    //let's find out if students were promoted from the third term, else we deny
                    $students_enrolled = $this->students_enrolled($running_year, $next_term);
                    if(is_array($students_enrolled)) {
                        return 'not_enrolled';
                    }

                    //update the table now
                    $data['description'] = $next_term;
                    $this->db->where('type', 'running_term');
                    $this->db->update('settings', $data);

                    return $next_term;

                } else {
                    return 'year not allowed';
                }
            }          
        }
    }

    //trying automate next year given the term
    function automateNextYear($running_term) {
        if($running_term == 1) {
            //try changing the year only if this is term 1
            $running_year = get_settings('running_year');

            $this->db->where('year', $running_year);
            $prev_term = $this->db->get('enroll')->last_row()->term;

            if($prev_term != 3) {
                //it exists so no need to do anything
                return false;
            } else {
                //new academic term so we need to update the year too
                //let's select the prev year and get the next year
                $exploded_year = explode('-', $running_year);
                $next_y1 = $exploded_year[0] + 1;
                $next_y2 = $exploded_year[1] + 1;
                $next_year = $next_y1.'-'.$next_y2;

                //update the table now
                $data['description'] = $next_year;
                $this->db->where('type', 'running_year');
                $this->db->update('settings', $data);

                return $next_year;            
            }
        } else {
            return false;
        }
        
    }

    //automatic enrollment when year or term changes
    function addExams($term, $year) {
        //does the exam exist
        $exam_category_query = $this->db->get('exam_category');
        $exam_category_array = $exam_category_query->result_array();

        $counter = 0;

        foreach($exam_category_array as $cat) {
            $this->db->where('year', $year);
            $this->db->where('term', $term);
            $this->db->where('category_id', $cat['category_id']);
            $exam_query = $this->db->get('exam');

            if($exam_query->num_rows() < 1) {

                $exam_data['year'] = $year;
                $exam_data['term'] = $term;
                $exam_data['category_id'] = $cat['category_id'];
                $exam_data['date'] = date('m/d/Y');
                $exam_data['name'] = 'TERM ' .$term. ' EXAMINATION - ' .$year;

                if($exam_data['category_id'] == 1) {
                    $exam_data['name'] = 'TERM ' .$term. ' PORTFOLIO ASSESSMENT - ' .$year;
                }

                $this->db->insert('exam', $exam_data);

                $counter += $this->db->affected_rows();
                
            }
        }

        if($counter > 0) {
            return true;
        } else {
            return false;
        }
    }

    function termlyEnrollment($term, $year) {

        $enrollment_exists_row = $this->db->get_where('enroll', ['year' => $year, 'term' => $term])->num_rows();
        if($enrollment_exists_row > 0) {
            //exists, so exit
            return false;
        } else {

            //we only do this if the term is not 1 since that term's enrollment 
            //is done from term 3 via promotion
            if($term != 1) {
                //all active students from the previous term
                $students = $this->db->get_where('enroll', array('mute' => '0', 'year' => $year, 'term' => $term - 1))->result_array();

                foreach ($students as $row) {

                    $enroll_data['enroll_code'] = substr(md5(rand(0, 1000000)), 0, 7);
                    $enroll_data['class_id'] = $row['class_id'];
                    $enroll_data['year'] = $year;
                    $enroll_data['term'] = $term;
                    $enroll_data['section_id'] = $row['section_id'];
                    $enroll_data['student_id'] = $row['student_id'];
                    $enroll_data['date_added'] = strtotime(date("Y-m-d H:i:s"));

                    $enroll_data['transport_id'] = $row['transport_id'];
                    $enroll_data['residence_type'] = $row['residence_type'];
                    $enroll_data['house_id'] = $row['house_id'];
                    $enroll_data['dormitory_id'] = $row['dormitory_id'];
                    $enroll_data['bed_id'] = $row['bed_id'];

                    $this->db->insert('enroll', $enroll_data);

                    $attn_data['class_id'] = $row['class_id'];
                    $attn_data['year'] = $year;
                    $attn_data['term'] = $term;
                    $attn_data['timestamp'] = strtotime(date("Y-m-d"));
                    $attn_data['section_id'] = $row['section_id'];
                    $attn_data['student_id'] = $row['student_id'];

                    $this->db->insert('attendance', $attn_data);

                    $this->db->where('student_id', $row['student_id']);
                    $this->db->update('enroll', array('status_attendance' => 'close'));

                }

                return true;

            } 

        }
    }

    function students_enrolled($year, $term) {

        //$counter = 0;
        /*If all the classes from the previous current academic year and term
        *have been fully enrolled, then we can go ahead
        */
        
        // Calculate the previous year based on the target year being checked
        $year_parts = explode('-', $year);
        $prev_year = ($year_parts[0] - 1) . '-' . ($year_parts[1] - 1);
        $last_term = 3;

        //previous session classes - get distinct classes from term 3 of previous year
        $this->db->select('class_id');
        $this->db->distinct();
        $this->db->where('year', $prev_year);
        $this->db->where('term', $last_term);
        $this->db->where('mute', '0');
        $allTerm3ClassesArray = $this->db->get('enroll')->result_array();

        // If no records found in calculated previous year, return true (no promotion check needed)
        if(empty($allTerm3ClassesArray)) {
            return true;
        }

        $notPromotedClassesIds = [];

        foreach($allTerm3ClassesArray as $row):
            // Get students from this class in previous year term 3
            $prev_students = $this->db->select('student_id')
                ->where('class_id', $row['class_id'])
                ->where('year', $prev_year)
                ->where('term', $last_term)
                ->where('mute', '0')
                ->get('enroll')
                ->result_array();

            if(empty($prev_students)) {
                continue; // No students in this class, skip
            }

            // Check if ANY of these students have been enrolled in the new year/term
            // (They could be in the same class, promoted to higher class, or repeated)
            $promoted_count = 0;
            foreach($prev_students as $student) {
                $promoted_check = $this->db->where('student_id', $student['student_id'])
                    ->where('year', $year)
                    ->where('term', $term)
                    ->get('enroll')
                    ->num_rows();
                
                if($promoted_check > 0) {
                    $promoted_count++;
                }
            }

            // If NO students from this class were promoted/enrolled in new term, flag the class
            if($promoted_count == 0) {
                $notPromotedClassesIds[] = $row['class_id'];
            }

        endforeach;

        if(sizeof($notPromotedClassesIds) > 0) { //false
            return $notPromotedClassesIds;
        } else {
            return true; //true
        }



        /*
        $counter = 0;
        $ids = [];
        $this->db->select('class_id');
        $this->db->distinct();
        $this->db->where('year', $last_year);
        $this->db->where('term', $last_term);
        $all_classes = $this->db->get('enroll')->result_array();

        foreach($all_classes as $class):
        $this->db->where('class_id', $class['class_id']);
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $row = $this->db->get('enroll')->num_rows();

        if($row > 0) {
          $counter++;
        } else {

          /*maybe because of A & B sections, a section of a class does not have *enrollment yet, so we find out its correponding class that have *enrollment and count it instead...
          */
          /*$class_name = $this->crud_model->get_class_name($class['class_id']);
          $class_name_numeric = $this->crud_model->get_class_name_numeric($class['class_id']);
          //get the corresponding section classes
          $this->db->select('class_id');
          $this->db->where('name', $class_name);
          $this->db->where('name_numeric', $class_name_numeric);
          $this->db->where('class_id !=', $class['class_id']);
          $get_class_id = $this->db->get('class')->result();

          foreach($get_class_id as $sid):
            //try searching again
            $this->db->where('class_id', $sid->class_id);
            $this->db->where('year', $year);
            $this->db->where('term', $term);
            $srow = $this->db->get('enroll')->num_rows();

            if($srow > 0) {
              $counter++;
              break; //exit this loop and move on with the other loop
            }
          endforeach;

          array_push($ids, $class['class_id']);
        }

        endforeach;

        //maybe a new class received enrollment so it has been added to the next term due to the previous term's promotion
        $this->db->select('class_id');
        $this->db->distinct();
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $allTermOneRow = $this->db->get('enroll')->num_rows();

        $total_classes = count($all_classes);
        $excluding_creche = $total_classes - 1; //minus 1 because unless some students
            //are repeated in creche, there will be no promotion to creche or no record
            //of creche will be found in the enroll table untill a new enrollment is done.

        if($counter ==  $excluding_creche) { 
            return true;
        } else {
            if($allTermOneRow > $total_classes) {
                //if the current number of classes enrolled is greater than the previous session, then it's probably means a new class has a new enrollment this time round
                return true;
            } else {
                return false;
            }

            
        }*/
    }

    //HEAD MASTER'S REMARKS BASED ON STUDENT'S AVERAGE SCORE
    function headMasterRemarks($student_score, $class_id, $exam_id, $year, $term) {

        $data_array =  array(
            'class_id' => $class_id, 
                    'year' => $year, 
                        'exam_id' => $exam_id,
                             'term' => $term
                    );
        //let's get total number of subjects they actually did in the term
        $this->db->select('subject_id');
        $this->db->distinct();
        $this->db->where($data_array);
        $this->db->where('class_id IS NOT NULL');
        $this->db->where('section_id IS NOT NULL');
        $this->db->from('mark');
        //$this->db->limit(5);
        $total_subjects = $this->db->get()->num_rows();
        $result = 'mark';
        $av_score = 0;

        
        //Calculations
        if($total_subjects > 0) {

            $grandScore = $total_subjects * 100;

            if($student_score > 0) {
               
                $student_score = $student_score * 100;
                $student_average = $student_score / $grandScore;

                $mark = $student_average;
            } 

            if($mark >= 0 && $mark <= 50) {
                $result = 'NEEDS MORE ASSISTANCE AT HOME';

            } else if($mark >= 51 && $mark <= 60) {
                $result = 'NEEDS ASSISTANCE AT HOME';

            } else if($mark >= 61 && $mark <= 70) {
                $result = 'NEEDS MORE ATTENTION';

            } else if($mark >= 71 && $mark <= 80) {
                $result = 'MORE ROOM FOR IMPROVEMENT';

            } else if($mark >= 81 && $mark <= 100) {
                $result = 'NEEDS TO BE ENCOURAGED';
            }

        }

        return $result;
         
    }

    function getAllStudentsIdsExCreche() {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        $creche_id = $this->db->get_where('class', ['name' => 'CRECHE'])->row()->class_id;

        $this->db->select('student_id, class_id');
        $this->db->distinct();
        $this->db->from('enroll');
        $this->db->where('year', $running_year);
        $this->db->where('class_id IS NOT NULL');
        $this->db->where('section_id IS NOT NULL');
        $this->db->where('mute', '0');
        $this->db->where('class_id !=', $creche_id);
        $this->db->where('term', $running_term);
        $allStudentIds = $this->db->get()->result_array();

        return $allStudentIds;

    }

    /*NEW ADDITIONS 2025*/
    /*REQUEST APPROVAL*/
    function createRequest($data) {

        $result = $this->db->insert('request', $data);

        return $result;
    }

    function updateSingleInvoiceRequest($request_id, $status) {

        $this->db->where('request_id', $request_id);
        $this->db->set('approval_status', $status);
        $this->db->set('response_timestamp', strtotime('now'));
        $this->db->set('approved_by_id', $this->session->userdata('login_user_id'));
        $requestResult = $this->db->update('request', $requestData);

        $invoice_codes_query = $this->requestIDsByID($request_id);
        $invoice_codes_array = explode(',', $invoice_codes_query);

        /*Update invoice table*/
        $this->db->where_in('invoice_code', $invoice_codes_array);
        $this->db->set('can_delete', strtolower($status));
        $invoiceResult = $this->db->update('invoice');

        if($requestResult && $invoiceResult) {

            return true;
            
        } else {

            return false;
        }
        
    }

    function requestIDsByID($request_id) {

        $data = $this->db->get_where('request', ['request_id' => $request_id])->row()->request_ids;

        return $data;
    }

    function getAllRequests() {
        $this->db->order_by('request_created_timestamp', 'asc');
        $data = $this->db->get('request')->result_array();

        return $data;
    }

    /*get all bill category*/
    function getAllBillCategory() {

       return $this->db->get('bill_category')->result_array();
    }

     function getBillCategoryNameById($cat_id) {
        $result = $this->db->get_where('bill_category', ['bill_category_id' => $cat_id])->row();
        return $result ? $result->bill_category_name : null;
    }


    function getBillCategoryIdByItemTitle($itemTitle) {
        $result = $this->db->get_where('bill_item', ['title' => $itemTitle])->row();
        return $result ? $result->bill_category_id : null;
    }

    function getBillItemRowById($bill_item_id) {

       return $this->db->get_where('bill_item', ['id' => $bill_item_id])->row();
    }

    /*get student's current enrollment status*/
    function getStudentCurrentEnrollmentStatusRow($student_id) {

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where('student_id', $student_id);
        $this->db->where('class_id IS NOT NULL');
        $row = $this->db->get('enroll')->last_row();

        return $row;

    }

    /**/
    /*get total amount*/

    /*update payment table*/
    /*function resetReceiptCodes() {

        $this->db->select('payment_id');
        $this->db->from('payment');
        $this->db->where('receipt_code', '10000');
        $arr = $this->db->get()->result_array();

        $this->db->select_max('receipt_code');
        $this->db->from('payment');
        $max_r = $this->db->get()->row()->receipt_code;
        foreach($arr as $r) {

            

            /*update*/
            /*$this->db->where('payment_id', $r['payment_id']);
            $this->db->set('receipt_code', $max_r + 1);
            $this->db->update('payment');

        }

        return 'Done';
    }*/

    function updateEnrollment() {

        $this->db->where('year', '2024-2025');
        $this->db->where('term', 3);
        $this->db->where('residence_type', 'Boarding');
        $arr = $this->db->get('enroll')->result_array();

        foreach($arr as $st) {

            $this->db->where('student_id', $st['student_id']);
            $this->db->where('year', '2025-2026');
            $this->db->where('term', 1);
            $this->db->set('residence_type', 'Boarding');
            $this->db->update('enroll');    
        }

        return 'success';
    }

    function deleteDuplicateBillItemFromInvoiceTable($class_ids=array(), $bill_item, $year, $term) {

        for($i = 0; $i < count($class_ids); $i++):

            $this->db->select('student_id');
            $this->db->distinct();
            $this->db->from('invoice');
            $this->db->where('class_id', $class_ids[$i]);
            $this->db->where('year', $year);
            $this->db->where('term', $term);
            $students = $this->db->get();

            if($students->num_rows() > 0) {

                foreach($students->result_array() as $st) {

                    $this->db->select('invoice_id');
                    $this->db->distinct();
                    $this->db->where('class_id', $class_ids[$i]);
                    $this->db->where('year', $year);
                    $this->db->where('student_id', $st['student_id']);
                    $this->db->where('term', $term);
                    $this->db->where('title', $bill_item);
                    $rows_query = $this->db->get('invoice');

                    if($rows_query->num_rows() > 1) {

                        $invoice_ids = array_column($rows_query->result_array(), 'invoice_id');

                        array_shift($invoice_ids); //remove the first occurrence

                        /*we delete the duplicated rows*/
                        $this->db->where_in('invoice_id', $invoice_ids);
                        $this->db->delete('invoice');

                    } else {

                        continue; //move on to the next student
                    }
                }

            } else {

                //return 'No students found';
            }
        endfor;

        return 'success';
    }


    /*get student's previous bill*/
    function getClassPreviousBill($class_id, $term, $year) {

        $this->db->where('class_id', $class_id);
        $this->db->where('term', $term);
        $this->db->where('year', $year);
        $bill = $this->db->get('bill_item_history')->result_array();

        return $bill;
    }

    /*get all students ids for the a class in a particular period*/
    function getStudentsIdsByClass($class_id, $year = null, $term = null) {

        $running_year = $year ? $year : get_settings('running_year');
        $running_term = $term ? $term : get_settings('running_term');

        $this->db->select('student_id');
        $this->db->distinct();
        $this->db->from('enroll');
        $this->db->where('class_id', $class_id);
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where('mute', '0');

        $ids = $this->db->get()->result_array();

        return $ids;
    }

    function getActiveStudentsIds() {

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        $this->db->select('student_id');
        $this->db->distinct();
        $this->db->from('enroll');
        $this->db->where('year', $running_year);
        $this->db->where('term', $running_term);
        $this->db->where('mute', '0');

        $ids = $this->db->get()->result_array();

        return $ids;
    }


    function getAllActiveParentsIds() {

        $activeStudentsArray = $this->getActiveStudentsIds();
        $activeStudentsIds = array_column($activeStudentsArray, 'student_id');

        $this->db->select('parent_id');
        $this->db->distinct();
        $this->db->from('student');
        $this->db->where_in('student_id', $activeStudentsIds);

        $ids = $this->db->get()->result_array();

        return $ids;
    }

    function getParentRowById($parent_id) {

        $parentRow = $this->db->get_where('parent', ['parent_id' => $parent_id])->row();

        return $parentRow;
    }

    function getAllActiveTeachers() {

        return $this->db->get_where('teacher', ['active_status' => '1'])->result_array();

    }

    ////////ATTENDANCE BILLING METHODS////////

    // Get benefit category for student
    public function get_student_benefit_category($student_id) {
        $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
        if($student && $student->benefit_status == 1 && $student->benefit_category_id) {
            return $this->db->get_where('benefit_category',
                array('category_id' => $student->benefit_category_id))->row();
        }
        return null;
    }

    // Calculate actual charge based on benefit (using JSON structure)
    public function calculate_charge($base_amount, $benefit_category, $fee_type, $class_id = null) {
        if(!$benefit_category) return $base_amount;

        // Parse JSON details
        $details = json_decode($benefit_category->details, true);
        if(!$details || !isset($details['classes'])) return $base_amount;

        // Get discount for this class
        $discount = 0;
        if($class_id && isset($details['classes'][$class_id])) {
            $field = $fee_type . '_charged';
            $discount = $details['classes'][$class_id][$field] ?? 0;
        }

        // Apply discount
        $discount_type = $details['discount_type'] ?? 'percentage';
        if($discount_type == 'percentage') {
            return $base_amount - ($base_amount * ($discount / 100));
        } else {
            return max(0, $base_amount - $discount);
        }
    }

    // Get or create daily billing record
    public function get_or_create_billing($student_id, $class_id, $section_id, $timestamp, $year, $term) {
        $existing = $this->db->get_where('feeding_fee_payment', array(
            'student_id' => $student_id,
            'day_timestamp' => $timestamp,
            'class_id' => $class_id
        ))->row();

        if($existing) return $existing;

        // Create new billing record
        $class = $this->db->get_where('class', array('class_id' => $class_id))->row();
        $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
        $benefit = $this->get_student_benefit_category($student_id);

        // Calculate charges
        $feeding_charge = $this->calculate_charge($class->feeding_fee, $benefit, 'feeding', $class_id);

        // Get previous balance
        $previous = $this->db->select('due, cdue')
            ->where('student_id', $student_id)
            ->where('class_id', $class_id)
            ->where('day_timestamp <', $timestamp)
            ->order_by('day_timestamp', 'desc')
            ->limit(1)
            ->get('feeding_fee_payment')->row();



        $previous_feeding_due = $previous ? $previous->due : 0;

        $data = array(
            'student_id' => $student_id,
            'class_id' => $class_id,
            'section_id' => $section_id,
            'day_timestamp' => $timestamp,
            'year' => $year,
            'term' => $term,
            'amount' => 0,
            'due' => $previous_feeding_due + $feeding_charge,
        );

        $this->db->insert('feeding_fee_payment', $data);

        return $this->db->get_where('feeding_fee', array(
            'student_id' => $student_id,
            'day_timestamp' => $timestamp
        ))->row();
    }


    public function get_or_create_billing_classes($student_id, $class_id, $section_id, $timestamp, $year, $term) {
        $existing = $this->db->get_where('classes_fee_payment', array(
            'student_id' => $student_id,
            'day_timestamp' => $timestamp,
            'class_id' => $class_id
        ))->row();

        if($existing) return $existing;

        // Create new billing record
        $class = $this->db->get_where('class', array('class_id' => $class_id))->row();
        $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
        $benefit = $this->get_student_benefit_category($student_id);

        // Calculate charges
        $classes_charge = $this->calculate_charge($class->classes_fee, $benefit, 'classes', $class_id);

        // Transport charge

        // Get previous balance


        $previous_classes = $this->db->select('due, cdue')
            ->where('student_id', $student_id)
            ->where('class_id', $class_id)
            ->where('day_timestamp <', $timestamp)
            ->order_by('day_timestamp', 'desc')
            ->limit(1)
            ->get('classes_fee_payment')->row();


        $previous_classes_due = $previous_classes ? $previous_classes->due : 0;



        $dataClasses = array(
            'student_id' => $student_id,
            'class_id' => $class_id,
            'section_id' => $section_id,
            'day_timestamp' => $timestamp,
            'year' => $year,
            'term' => $term,
            'amount' => 0,
            'due' => $previous_classes_due + $classes_charge,
        );

        $this->db->insert('classes_fee_payment', $dataClasses);
        return $this->db->get_where('classes_fee_payment', array(
            'student_id' => $student_id,
            'day_timestamp' => $timestamp
        ))->row();
    }

    // Get or create transport billing record
    public function get_or_create_transport_billing($student_id, $class_id, $section_id, $timestamp, $year, $term) {
        $existing = $this->db->get_where('transport_fare_payment', array(
            'student_id' => $student_id,
            'day_timestamp' => $timestamp,
            'class_id' => $class_id
        ))->row();

        if($existing) return $existing;

        // Get student transport fare
        $student = $this->db->get_where('student', array('student_id' => $student_id))->row();
        $transport_fare = 0;

        if($student->transport_id) {
            $transport = $this->db->get_where('transport', array('transport_id' => $student->transport_id))->row();
            $transport_fare = $transport ? $transport->route_fare : 0;
        }

        // Create new transport billing record
        $transport_data = array(
            'student_id' => $student_id,
            'class_id' => $class_id,
            'section_id' => $section_id,
            'day_timestamp' => $timestamp,
            'year' => $year,
            'term' => $term,
            'amount' => 0,
            'due' => $transport_fare
        );

        $this->db->insert('transport_fare_payment', $transport_data);
        return $this->db->get_where('transport_fare_payment', array(
            'student_id' => $student_id,
            'day_timestamp' => $timestamp
        ))->row();
    }


}
