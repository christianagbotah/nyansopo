
<?php
$term = $this->graphassessment_model->getPortfolioAssessmentRowData('term', $start_week, $class_id);

$year = (explode('-', $start_week)[0] - 1).'-'.explode('-', $start_week)[0];


$account_type = $this->session->userdata('login_type');
$class_name = $this->crud_model->get_class_name($class_id);
//let's get the teacher's id and compare to see if he is just a subject teacher or a class teacher
$t_id = $this->db->get_where('class', array('class_id' => $class_id))->row()->teacher_id;

$counter = 0;

if ($account_type == 'teacher') {

		?>
				<div class="input-group">
				  <div class="input-group-prepend">
				      <div class="input-group-text bg-primary text-light"><strong>Subject:</strong></div>
				  </div>
				  <select name="search_by_subject" id="search_by_subject" class="form-control" required="required">
				  	
							<?php
//grant access to all subjects if this teacher is a class teacher else just grant access to a particular subject
		if ($t_id == $this->session->userdata('teacher_id')) {
			$subjects = $this->db->get_where('subject', array(
				'class_id' => $class_id, 'year' => $year, 'term' => $term))->result_array();
		} else {
			$subjects = $this->db->get_where('subject', array(
				'class_id' => $class_id, 'year' => $year, 'term' => $term, 'teacher_id' => $this->session->userdata('teacher_id'),
			))->result_array();
		}

		if(count($subjects) > 0) {
			echo '<option value="0">All Subjects</option>';
				foreach ($subjects as $row):
				?>
							<option value="<?php echo $row['subject_id']; ?>"><?php echo $row['name']; ?></option>
							<?php endforeach;
		} else {
			echo '<option value="">No subject found</option>';
			$counter++;
		}
							?>
						</select>
					</div>
			<?php

} else {
	?>

<div class="input-group">
  <div class="input-group-prepend">
      <div class="input-group-text bg-primary text-light"><strong>Subject:</strong></div>
  </div>
  <select name="search_by_subject" id="search_by_subject" class="form-control" required="required">
  	
			<?php


		$subjects = $this->db->get_where('subject', array(
			'class_id' => $class_id, 'year' => $year, 'term' => $term,
		))->result_array();

	if(count($subjects) > 0) {
			echo '<option value="0">All Subjects</option>';
			foreach ($subjects as $row):
			?>
			<option value="<?php echo $row['subject_id']; ?>"><?php echo $row['name']; ?></option>
			<?php endforeach;

	} else {
		echo '<option value="">No subject found</option>';
		$counter++;
	}
			?>
		</select>
	</div>
<?php }?>



<script type="text/javascript">
	$(document).ready(function() {
			updator();//update the subject border if there is error
			//change effect: no need to click on the generate button to refresh the data. Once a field is changed, update the view immediately
      $('#search_by_subject').change(function(e) {
       // if(generateClickCounter > 0) { //meaning we had at least one successful submission already
            callSubmission(); //form submission
       // }
       

      });

      function callSubmission() {
        $('#assessment_graph').submit();
      }

      function updator() {
      	let counter = Number(<?=$counter;?>);

      	if(counter > 0) {
	      	//error occured
	      	$('#search_by_subject').css({
	      		border: '2px solid red'
	      	});
	      }
	      
      }

    });

</script>