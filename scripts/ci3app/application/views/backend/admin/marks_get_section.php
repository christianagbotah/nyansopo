<?php 
	$sections = $this->db->get_where('section' , array(
		'class_id' => $class_id 
	))->result_array();
	foreach($sections as $row):
?>
<option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
<?php endforeach;?>