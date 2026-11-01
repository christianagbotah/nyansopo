<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verify Controller
 * 
 * Public controller for verifying student report cards via QR code
 * NO LOGIN REQUIRED - accessible to parents/guardians
 */
class Verify extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('crud_model');
        $this->load->database();
        $this->load->helper('url');
    }

    /**
     * Verify student report card
     * 
     * @param string $student_code Student unique code
     * @param int $exam_id Exam ID
     * @param string $year Academic year
     */
    public function index($student_code = '', $exam_id = '', $year = '')
    {
        // Validate parameters
        if (empty($student_code) || empty($exam_id) || empty($year)) {
            show_error('Invalid verification link. Please scan the QR code again.', 400);
            return;
        }

        // Get student information
        $student = $this->db->get_where('student', array('student_code' => $student_code))->row();
        
        if (!$student) {
            show_error('Student record not found. Please contact the school.', 404);
            return;
        }

        // Get exam information
        $exam = $this->db->get_where('exam', array('exam_id' => $exam_id))->row();
        
        if (!$exam) {
            show_error('Exam record not found. Please contact the school.', 404);
            return;
        }

        // Get class information from enrollment (more reliable than student.class_id)
        $this->db->select('class.*');
        $this->db->from('enroll');
        $this->db->join('class', 'class.class_id = enroll.class_id');
        $this->db->where('enroll.student_id', $student->student_id);
        $this->db->where('enroll.year', $year);
        $this->db->limit(1);
        $class = $this->db->get()->row();

        if (!$class || !isset($class->class_id)) {
            show_error('Class information not found for this student. Please contact the school.', 404);
            return;
        }

        $class_id = $class->class_id;
        $section = $this->db->get_where('section', array('class_id' => $class_id))->row();

        // Get school information
        $school_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
        $school_address = $this->db->get_where('settings', array('type' => 'address'))->row()->description;
        $school_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
        $school_email = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;

        // Get marks with subject information
        $this->db->select('mark.*, subject.name as subject_name');
        $this->db->from('mark');
        $this->db->join('subject', 'subject.subject_id = mark.subject_id');
        $this->db->where('mark.student_id', $student->student_id);
        $this->db->where('mark.exam_id', $exam_id);
        $this->db->where('mark.year', $year);
        $this->db->order_by('subject.name', 'ASC');
        $marks = $this->db->get()->result();

        // Calculate totals
        $total_marks = 0;
        $total_possible = 0;
        foreach ($marks as $mark) {
            // Convert to numeric to avoid string concatenation
            $total_marks += (float)$mark->mark_obtained;
            
            // Use mark_total if available, otherwise use subject's exam_mark (total possible for subject)
            if (!empty($mark->mark_total) && $mark->mark_total > 0) {
                $total_possible += (float)$mark->mark_total;
            } elseif (!empty($mark->subject_total) && $mark->subject_total > 0) {
                $total_possible += (float)$mark->subject_total;
            } else {
                // Fallback: calculate from exam components (class_score + exam_score or sub_total + term_exam)
                if (!empty($mark->class_score) || !empty($mark->exam_score)) {
                    // If using class_score/exam_score system, total is usually 100
                    $total_possible += 100;
                } elseif (!empty($mark->sub_total) || !empty($mark->term_exam)) {
                    // If using sub_total/term_exam system, total is usually 100
                    $total_possible += 100;
                } else {
                    // Default fallback: 100
                    $total_possible += 100;
                }
            }
        }

        $percentage = $total_possible > 0 ? round(($total_marks / $total_possible) * 100, 2) : 0;
        $marks_exist = count($marks) > 0;

        // Prepare data for view
        $page_data = array(
            'student' => $student,
            'exam' => $exam,
            'class' => $class,
            'section' => $section,
            'class_id' => $class_id,
            'year' => $year,
            'school_name' => $school_name,
            'school_address' => $school_address,
            'school_phone' => $school_phone,
            'school_email' => $school_email,
            'marks' => $marks,
            'marks_exist' => $marks_exist,
            'total_marks' => $total_marks,
            'total_possible' => $total_possible,
            'percentage' => $percentage,
            'verification_time' => date('l, F j, Y g:i A')
        );

        $this->load->view('verify_report', $page_data);
    }
}
