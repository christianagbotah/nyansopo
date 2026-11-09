<?php
	$query = $this->db->get_where('section' , array('class_id' => $class_id));
	if($query->num_rows() > 0):
		$sections = $query->result_array();
?>

<label class="form-label-modern">
	<i class="glyphicon glyphicon-list-alt"></i> Section <span style="color: #ef4444;">*</span>
</label>
<select name="section_id" id="section_id" class="modern-select">
	<?php foreach($sections as $row):?>
	<option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
	<?php endforeach;?>
</select>

<?php endif;?>

<script>
$(document).ready(function() {
	$('.select2').select2();
});
</script>
