
<?php 
$checked = 'checked';
$edit_data		=	$this->db->get_where('subject_creche' , array('subject_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
?>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-success" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('edit_subject');?>
            	</div>
            </div>
			<div class="panel-body">
                <?php echo form_open(site_url('admin/subject_creche/do_update/'.$row['subject_id']) , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subject_edit_form'));?>
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('subject_name');?></label>
                    <div class="col-sm-8 controls">
                        <input type="text" class="form-control" name="name" value="<?php echo $row['name'];?>" required/>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('subject_category');?></label>
                    <div class="col-sm-5">
                        <select name="category_id" class="form-control">
                            <option value=""><?php echo get_phrase('select_category');?></option>
                        	<?php
							$categories = $this->db->get('subject_category_creche')->result_array();
							foreach($categories as $row2):
							?>
                        		<option value="<?php echo $row2['category_id'];?>" <?php if($row['category_id'] == $row2['category_id']) echo 'selected';?>><?php echo $row2['name'];?></option>
                            <?php
							endforeach;
							?>
                        </select>
                    </div>
                </div>
                            
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('class');?></label>
                    <div class="col-sm-5 controls">
                        <select name="class_id" class="form-control">
                            <?php 
                            $name_array = array('CRECHE', 'NURSERY');
                        	$this->db->where_in('name', $name_array);
                            $classes = $this->db->get('class')->result_array();
                            foreach($classes as $row2):
                            ?>
                            
                            <?php
                                //add section A or B if the class has more than one section
                                $section_name = $this->db->get_where('section', array('class_id' => $row2['class_id']))->row()->name;
                                $class_has_more_sections = $this->db->get_where('class', array('name' => $row2['name'], 'name_numeric' => $row2['name_numeric']))->num_rows();
                                $sec_name = '';
                                if($class_has_more_sections > 1) {
                                    $sec_name = $section_name;
                                }
                            ?>
                                <option value="<?php echo $row2['class_id'];?>"
                                    <?php if($row['class_id'] == $row2['class_id'])echo 'selected';?>>
                                        <?php echo $row2['name'].' '.$row2['name_numeric'].$sec_name;?>
                                            </option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('teacher');?></label>
                    <div class="col-sm-5 controls">
                        <select name="teacher_id" class="form-control">
                            <?php 
                            $teachers = $this->db->get('teacher')->result_array();
                            foreach($teachers as $row2):
                            ?>
                                <option value="<?php echo $row2['teacher_id'];?>"
                                    <?php if($row['teacher_id'] == $row2['teacher_id'])echo 'selected';?>>
                                        <?php echo $row2['name'];?>
                                            </option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-3 ">
                        <button type="submit" class="btn btn-success"><?php echo get_phrase('edit_subject');?></button>
                    </div>
                 </div>
        		</form>
            </div>
        </div>
    </div>
</div>

<?php
endforeach;
?>





<script type="text/javascript">
$(document).ready(function() {
    // AJAX handler for edit subject form in modal
    $('#subject_edit_form').submit(function(e) {
        e.preventDefault();
        
        // Get form data and URL
        const formData = $(this).serialize();
        const formUrl = $(this).attr('action');
        
        // Show loading modal using system's showAjaxModal_alert
        showAjaxModal_alert('Processing, please wait...', 'Loading');
        
        $.ajax({
           url: formUrl, 
           type: 'POST',
           data: formData,
           beforeSend: function(){
               // Disable submit button to prevent double submission
               $('#subject_edit_form button[type="submit"]').prop('disabled', true).text('Updating...');
           },
           success: function(responseText) {
               try {
                   // Manually parse JSON response
                   const response = JSON.parse(responseText);
                   
                   if(response.status === 'success') {
                       // Show success message with auto-reload
                       showAjaxModal_alert('Subject Updated Successfully', 'Success', true);
                       
                       // The success modal will auto-close and reload after 2 seconds
                   } else {
                       showAjaxModal_alert(response.message || 'Failed to update subject', 'Error');
                       $('#subject_edit_form button[type="submit"]').prop('disabled', false).text('<?php echo get_phrase('edit_subject');?>');
                   }
               } catch(e) {
                   console.error('JSON parse error:', e);
                   console.error('Response text:', responseText);
                   showAjaxModal_alert('Invalid response from server. Please try again.', 'Error');
                   $('#subject_edit_form button[type="submit"]').prop('disabled', false).text('<?php echo get_phrase('edit_subject');?>');
               }
           },
           error: function(xhr, status, error) {
               console.error('AJAX error:', status, error);
               showAjaxModal_alert('An error occurred. Please try again.', 'Error');
               $('#subject_edit_form button[type="submit"]').prop('disabled', false).text('<?php echo get_phrase('edit_subject');?>');
           }
        });
    });
});
</script>
