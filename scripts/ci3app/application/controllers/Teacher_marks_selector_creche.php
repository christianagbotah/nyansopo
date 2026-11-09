<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teacher extends CI_Controller {
    
    public function marks_selector_creche() {
        $data['exam_id'] = $this->input->post('exam_id');
        $data['class_id'] = $this->input->post('class_id');
        $data['section_id'] = $this->input->post('section_id');
        $data['subject_id'] = $this->input->post('subject_id');
        $data2['category_id'] = $this->input->post('category_id');
        $data['year'] = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $data['term'] = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        if ($data['subject_id'] == '') {
            echo 'no subject';
            return false;
        }

        if ($data['class_id'] != '' && $data['exam_id'] != '') {
            $query = $this->db->get_where('mark', array(
                'exam_id' => $data['exam_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'subject_id' => $data['subject_id'],
                'year' => $data['year'],
                'term' => $data['term'],
            ));

            $students = $this->db->get_where('enroll', array(
                'class_id' => $data['class_id'], 'mute' => '0', 'section_id' => $data['section_id'], 'year' => $data['year'], 'term' => $data['term'],
            ));

            if ($query->num_rows() < 1) {
                if ($students->num_rows() < 1) {
                    echo 'no enrollment';
                    return false;
                } else {
                    $array_students = $students->result_array();
                    foreach ($array_students as $row) {
                        $data['student_id'] = $row['student_id'];
                        $this->db->insert('mark', $data);

                        $this->db->where('student_id', $row['student_id']);
                        $this->db->update('enroll', array('status' => 'close'));

                        $subject_status = $this->db->get_where('subject_creche', array('subject_id' => $data['subject_id'], 'class_id' => $data['class_id'], 'year' => $data['year'], 'term' => $data['term']))->row()->status;

                        $this->db->where('subject_id', $data['subject_id']);
                        $this->db->where('class_id', $data['class_id']);
                        $this->db->where('year', $data['year']);
                        $this->db->where('term', $data['term']);
                        $this->db->where('section_id', $data['section_id']);
                        $this->db->update('mark', array('status' => $subject_status));
                    }
                }
            } elseif ($query->num_rows() > 0) {
                $sIds = [];
                foreach($query->result_array() as $st) {
                    array_push($sIds, $st['student_id']);
                }

                $this->db->where_not_in('student_id', $sIds);
                $students = $this->db->get_where('enroll', array(
                    'class_id' => $data['class_id'], 'mute' => '0', 'section_id' => $data['section_id'], 'year' => $data['year'], 'term' => $data['term']
                ))->result_array();

                foreach ($students as $row) {
                    $data['student_id'] = $row['student_id'];
                    $this->db->insert('mark', $data);

                    $this->db->where('student_id', $row['student_id']);
                    $this->db->update('enroll', array('status' => 'close'));

                    $subject_status = $this->db->get_where('subject', array('subject_id' => $data['subject_id'], 'class_id' => $data['class_id'], 'year' => $data['year'], 'term' => $data['term']))->row()->status;

                    $this->db->where('subject_id', $data['subject_id']);
                    $this->db->where('class_id', $data['class_id']);
                    $this->db->where('year', $data['year']);
                    $this->db->where('term', $data['term']);
                    $this->db->where('section_id', $data['section_id']);
                    $this->db->update('mark', array('status' => $subject_status));
                }
            }

            if ($data2['category_id'] == '0') {
                echo site_url('teacher/marks_manage_view_creche2/' . $data['exam_id'] . '/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['subject_id'] . '/' . $data2['category_id']);
            } else {
                echo site_url('teacher/marks_manage_view_creche/' . $data['exam_id'] . '/' . $data['class_id'] . '/' . $data['section_id'] . '/' . $data['subject_id'] . '/' . $data2['category_id']);
            }
        }
    }
}
