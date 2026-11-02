<?php

  $year = $data['year'];
  $term = $data['term'];
  $student_id = $data['student_id'];

  /*we select invoice codes for this student based on the parameter*/
  $this->db->select('invoice_code');
  $this->db->distinct();
  $this->db->from('invoice');
  $this->db->where('student_id', $student_id);
  $this->db->where('term', $term);
  $this->db->where('year', $year);
  $codesQuery = $this->db->get();

  if($codesQuery->num_rows() > 0) {

    $codesArray = $codesQuery->result_array();
    
    foreach($codesArray as $code):

      ?>

      <option value="<?=$code['invoice_code'];?>"><?=$code['invoice_code'];?></option>
      <?php

    endforeach;
  } else {

    ?>

    <option value="">Select Student First</option>

    <?php
  }