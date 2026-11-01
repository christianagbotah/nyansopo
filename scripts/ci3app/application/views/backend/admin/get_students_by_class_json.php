<?php
$class_id = $this->uri->segment(3);

$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

// Get active students in the class
$this->db->select('student.student_id, student.name, student.student_code');
$this->db->from('enroll');
$this->db->join('student', 'student.student_id = enroll.student_id');
$this->db->where('enroll.class_id', $class_id);
$this->db->where('enroll.mute', '0');
$this->db->where('enroll.year', $running_year);
$this->db->where('enroll.term', $running_term);
$this->db->order_by('student.name', 'ASC');

$students = $this->db->get()->result_array();

$result = array();
foreach ($students as $student) {
    $result[] = array(
        'student_id' => $student['student_id'],
        'name' => $student['name'] . ' (' . $student['student_code'] . ')'
    );
}


echo json_encode($result);
?>
