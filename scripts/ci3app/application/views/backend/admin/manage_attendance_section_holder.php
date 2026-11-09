<label class="block text-sm font-bold text-gray-700 mb-2">Section</label>
<select name="section_id" id="section_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg h-[46px]">
	<?php 
		$sections = $this->db->get_where('section' , array(
			'class_id' => $class_id 
		))->result_array();
		foreach($sections as $row):
	?>
	<option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
	<?php endforeach;?>
</select>
