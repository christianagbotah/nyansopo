
<div class="col-md-2">
	<div class="form-group">
	<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
		<select name="section_id" id="section_id" class="form-control select2">
			<?php 
				$sections = $this->db->get_where('section' , array(
					'class_id' => $class_id 
				))->result_array();
				foreach($sections as $row):
			?>
			<option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
			<?php endforeach;?>
		</select>
	</div>
</div>

<?php
	$account_type       =	$this->session->userdata('login_type');
	$class_name = $this->crud_model->get_class_name($class_id);
	//let's get the teacher's id and compare to see if he is just a subject teacher or a class teacher
	$t_id = $this->db->get_where('class', array('class_id' => $class_id))->row()->teacher_id;

	if($account_type == 'teacher') {

		if($class_name == 'JHSS') {
		?>
		<div class="col-md-3">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
				<select name="subject_id" id="subject_id" class="form-control select2">
					<?php 
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
					<?php endforeach;?>
				</select>
			</div>
		</div>
		<?php 
		} else {
			?>
				<div class="col-md-3">
					<div class="form-group">
					<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
						<select name="subject_id" id="subject_id" class="form-control select2">
							<?php 
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
							<?php endforeach;?>
						</select>
					</div>
				</div>
			<?php
		}
	} else {
?>

<div class="col-md-3">
	<div class="form-group">
	<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
		<select name="subject_id" id="subject_id" class="form-control select2">
			<?php 
				

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
			<?php endforeach;?>
		</select>
	</div>
</div>
<?php } ?>

<div class="col-md-2" style="margin-top: 20px;">
	<center>
		<button type="submit" class="btn btn-info"><?php echo get_phrase('manage_marks');?></button>
	</center>
</div>


<script type="text/javascript">
	$(document).ready(function() {
        if (typeof $.fn.select2 !== "undefined")
		{
			$("select.select2").each(function(i, el)
			{
				var $this = $(el),
					opts = {
						showFirstOption: attrDefault($this, 'first-option', true),
						'native': attrDefault($this, 'native', false),
						defaultText: attrDefault($this, 'text', ''),
					};
					
				$this.addClass('visible');
				$this.select2({ placeholder: "Select an option", allowClear: true, width: "100%" });
			});
		}
    });
	
</script>