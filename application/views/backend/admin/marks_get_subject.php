
<?php
	$account_type = $this->session->userdata('login_type');
	$class_name = $this->crud_model->get_class_name($class_id);
	//let's get the teacher's id and compare to see if he is just a subject teacher or a class teacher
	$t_id = $this->db->get_where('class', array('class_id' => $class_id))->row()->teacher_id;

	if($account_type == 'teacher') {

		if($class_name == 'JHSS') {
		
 
			//grant access to all subjects if this teacher is a class teacher else just grant access to a particular subject
			if($t_id == $this->session->userdata('teacher_id')) {
				$subjects = $this->db->get_where('subject' , array(
					'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sen' => $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description))->result_array();
			} else {
				$subjects = $this->db->get_where('subject' , array(
					'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description, 'teacher_id' => $this->session->userdata('teacher_id')
				))->result_array();
			}
			foreach($subjects as $row):
		?>
		<option value="<?php echo $row['subject_id'];?>"><?php echo $row['name'];?></option>
		<?php endforeach;

		} else {

			//grant access to all subjects if this teacher is a class teacher else just grant access to a particular subject
			if($t_id == $this->session->userdata('teacher_id')) {
				$subjects = $this->db->get_where('subject' , array(
					'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings', array('type' => 'running_term'))->row()->description))->result_array();
			} else {
				$subjects = $this->db->get_where('subject' , array(
					'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings', array('type' => 'running_term'))->row()->description, 'teacher_id' => $this->session->userdata('teacher_id')
				))->result_array();
			}
			foreach($subjects as $row):
		?>
		<option value="<?php echo $row['subject_id'];?>"><?php echo $row['name'];?></option>
		<?php endforeach;

		}
	} else {


				

		if($class_name == 'JHSS') {

			$subjects = $this->db->get_where('subject' , array(
			'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description
		))->result_array();

		} else {
			$subjects = $this->db->get_where('subject' , array(
			'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
		))->result_array();

		}
		
		foreach($subjects as $row):
	?>
	<option value="<?php echo $row['subject_id'];?>"><?php echo $row['name'];?></option>
	<?php endforeach;

 } ?>

