<div class="grid grid-cols-2 gap-2 w-full max-w-full">
	<div class="w-full">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
			<select name="section_id" id="section_id" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase">
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

	<div class="w-full" id="category_holder">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject_category');?></label>
			<select name="category_id" id="category_id" class="select2 visible bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase">
			    <option value="">SELECT CATEGORY</option>
				<option value="0">SUBJECTS WITH NO CATEGORY</option>
				<?php 
					$categories = $this->db->get('subject_category_creche')->result_array();
					foreach($categories as $row):
				?>
				<option value="<?php echo $row['category_id'];?>"><?php echo $row['name'];?></option>
				<?php endforeach;?>
			</select>
		</div>
		<span style="color:red; display: none;" id="error_notec"></span>
	</div>

	<div class="col-span-2 flex gap-4" id="subject_holder2">
	    <div class="w-full">
	    	<div class="form-group">
	    	<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
	    		<select name="subject_id" id="subject_id" class="select2 visible bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase">

	    			<option value="">SELECT CATEGORY FIRST</option>


	    		</select>
	    		
	    	</div>
	    	<span style="color:red; display: none;" id="error_note"></span>
	    </div>
	    
	    <div class="" style="margin-top: 20px">

			<?php
				echo get_button('submit', "Manage Marks", '', 'submit')
			?>
		</div>
	</div>
</div>



<script type="text/javascript">
	$(document).ready(function() {

		$('#category_id, #subject_id').select2();
		
		//remove action from the form
		$('form').removeAttr('action');
		
		//now add a new action attribute for creche mark selection
		$('form').attr('action','<?php echo site_url('admin/marks_selector_creche'); ?>');
		
		//update subjects if category field changes
		$('#category_id').on("change", function() {
		    $('#error_note').css('display', 'none');//clear error if there was any
		    $('#error_notec').css('display', 'none');//clear error if there was any
		   let cat_id = $('#category_id').val();
		   const class_id = '<?php echo $class_id; ?>';
		   
		   //send via ajax
		   $.ajax({
		      url: '<?php echo site_url('admin/update_subjects_creche/'); ?>' + cat_id + '/' + class_id,
		      success: function(response) {
		          $('#subject_id').html(response);
		      }
		   });
		});
		
		//check if subject is selected
		$('#btn_marks').on("click", function() {
		   if($('#subject_id').val() == '' || $('#subject_id').val() == null) {
		       $('#error_note').css('display', 'block');
		       $('#error_note').text('No subject was selected for the category. Change the category to update the subject field!');
		       return false;
		   } if($('#category_id').val() == '' || $('#category_id').val() == null) {
		       $('#error_notec').css('display', 'block');
		       $('#error_notec').text('Select a category!');
		       return false;
		   } else {
		       $('#error_note').css('display', 'none');
		       $('#error_notec').css('display', 'none');
		   }
		});
    });
	
</script>