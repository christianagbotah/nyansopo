<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Graphassessment_model extends CI_Model {
    const HEXCHARACTERS = [0,1,2,3,4,5,6,7,8,9,"A","B","C","D","E","F"];

    public function __construct() {
        parent::__construct();
    }
    
    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    function getPortfolioAssessmentData($class_id="", $student_id="", $subject_id="", $start_week="", $end_week="", $order_by="", $single_student="") {


        $this->db->where('week BETWEEN "' . $start_week . '" AND "' . $end_week . '"');

        $this->db->where('portfolio_assessment.class_id', $class_id);
        if($student_id != 0) {
            $this->db->where('student_id', $student_id);
        }
        if($subject_id != 0) {
            $this->db->where('portfolio_assessment.subject_id', $subject_id);
        }
        if($order_by == 'student_name') {
            $this->db->join('student', 'student.student_id = portfolio_assessment.student_id');
            $this->db->order_by('name', 'asc');

        } else if($order_by == 'subject_name') {
            $this->db->join('subject', 'subject.subject_id = portfolio_assessment.subject_id');
            $this->db->order_by('name', 'asc');
        }

        //single student and single subject
        if($single_student == 'yes') {
            //$this->db->join('student', 'student.student_id = portfolio_assessment.student_id');
            $this->db->join('subject', 'subject.subject_id = portfolio_assessment.subject_id');
        }

        //all subjects and all students
        if($single_student == 'no') {
            $this->db->join('subject', 'subject.subject_id = portfolio_assessment.subject_id');
            $this->db->order_by('name', 'asc');
        }

        $query_array = $this->db->get('portfolio_assessment')->result_array();

        return $query_array;
        
    }

    function getTotalStrandScore($class_id="", $student_id="", $subject_id="", $start_week="", $end_week="") {

        $this->db->select_sum('strand_score');
        $this->db->where('week BETWEEN "' . $start_week . '" AND "' . $end_week . '"');
        $this->db->where('class_id', $class_id);
        $this->db->where('student_id', $student_id);
        $this->db->where('subject_id', $subject_id);
        $strand_score = $this->db->get('portfolio_assessment')->row()->strand_score;

        return $strand_score;       
    }

    function getSubjectNamebyId($subject_id) {
        $this->db->where('subject_id', $subject_id);
        $name = $this->db->get('subject')->row()->name;

        return $name;
    }

    function getPortfolioAssessmentRow($start_week, $end_week, $class_id, $subject_id, $type="") {
        //find the total number of entries made so far
        $this->db->select('timestamp');
        $this->db->distinct();
        $this->db->where('strand_score >', '0');
        $this->db->where('class_id', $class_id);

        if($type == 'week') {
            $this->db->where('week', $subject_id);
        } else {
            $this->db->where('subject_id', $subject_id);
        }
        
        $this->db->where('week BETWEEN "' . $start_week . '" AND "' . $end_week . '"');
        //$this->db->where('exam_id', $data['exam_id']);
        /*$this->db->where('year', $data['year']);
        $this->db->where('term', $data['term']);*/
        $num_rows = $this->db->get('portfolio_assessment')->num_rows();

        return $num_rows;
    }

    function getPortfolioAssessmentRowData($rowName="", $week="", $class_id="") {
        $this->db->where('week', $week);
        $this->db->where('class_id', $class_id);
        $rowData = $this->db->get('portfolio_assessment')->first_row()->$rowName;

        return $rowData;
    }

    function getAllStudents($class_id, $week) {
        $term = $this->getPortfolioAssessmentRowData('term', $week, $class_id);

        $year = (explode('-', $week)[0] - 1).'-'.explode('-', $week)[0];


        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $query = $this->db->get_where('enroll', array('class_id' => $class_id));
        return $query->result_array();

    }


    function getAllStudentsID($class_id, $start_week) {

        $term = $this->getPortfolioAssessmentRowData('term', $start_week, $class_id);

        $year = (explode('-', $start_week)[0] - 1).'-'.explode('-', $start_week)[0];

        $this->db->select('student_id');
        $this->db->distinct();
        $this->db->from('enroll');
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $this->db->where('class_id', $class_id);
        $query = $this->db->get();

        return $query->result_array();

    }

    function getAllSubjectsID($class_id, $start_week, $end_week) {

        $term = $this->getPortfolioAssessmentRowData('term', $start_week, $class_id);

        $year = (explode('-', $start_week)[0] - 1).'-'.explode('-', $start_week)[0];


        $this->db->select('subject_id');
        $this->db->distinct();
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $this->db->from('subject');
        $this->db->where('class_id', $class_id);
        //$this->db->where('week BETWEEN "' . $start_week . '" AND "' . $end_week . '"');
        $query = $this->db->get();
        return $query->result_array();

    }

    function getAllStudentsData($class_id='', $week) {

        $studentCounter = 0;
        $allStudents = $this->getAllStudents($class_id, $week);

        if(count($allStudents) > 0) {
            echo '<option value="0">All Students</option>';
            foreach($allStudents as $student) {
                $student_detail = $this->db->get_where('student', ['student_id' => $student['student_id']])->row();
                echo '<option value="'.$student['student_id'].'">'.$student_detail->name.'</option>';
            }
        } else {
            echo '<option value="">No student found</option>';
            $studentCounter++;
        }

        echo '<script type="text/javascript">
            $(function() {
                updator();//function called

                function updator() {
                let studentCounter = Number('.$studentCounter.');
                if(studentCounter > 0) {
                    //error occured
                    $("#search_by_student").css({
                        border: "2px solid red"
                    });   
                }
                
                  
              }
                });
        </script>';
        
    }

    //get random hexcolors for the graph
    
    function getHexIndex($index) {
        return self::HEXCHARACTERS[$index];
    }

    function generateNewHexColor() {
        $hexRep = "#";
        $max = (sizeof(self::HEXCHARACTERS) - 1);

        for($i = 0; $i < 6; $i++) {

            $randomPosition = rand(0, $max);
            $hexRep .= self::getHexIndex($randomPosition);
        }

        return $hexRep;
    }

    
}
