<script type="text/javascript">
$(document).ready(function() {
    <?php 
    if(isset($_GET['add']) && $_GET['add'] == 1) { ?>
        showAjaxModal_alert('Subject Added Successfully', 'Success');
    <?php } else if(isset($_GET['update']) && $_GET['update'] == 1) { ?>
        showAjaxModal_alert('Subject Updated Successfully', 'Success');
    <?php } else if(isset($_GET['delete']) && $_GET['delete'] == 1) { ?>
        showAjaxModal_alert('Subject For This Term Deleted Successfully', 'Success');
    <?php } ?>
});
</script>
<hr />
<div class="row">
	<div class="col-md-12">

    	<!---CONTROL TABS START------>
		<ul class="nav nav-tabs bordered">
			<li class="active">
            	<a href="#list" data-toggle="tab"><i class="entypo-menu"></i>
					<?php echo get_phrase('subject_list');?>
                    	</a></li>
			<li>
            	<a href="#add_cat" data-toggle="tab"><i class="entypo-plus-circled"></i>
					<?php echo get_phrase('add_subject_category');?>
                    	</a></li>
            <li>
            	<a href="#add" data-toggle="tab"><i class="entypo-plus-circled"></i>
					<?php echo get_phrase('add_subject');?>
                    	</a></li>
		</ul>
    	<!---CONTROL TABS END------>
		<div class="tab-content">
        <br>
            <!---TABLE LISTING STARTS-->
            <div class="tab-pane box active" id="list">

                <table class="table table-bordered datatable" id="table_export">
                	<thead>
                		<tr>
                    		<th width="80"><div><?php echo get_phrase('class');?></div></th>
                    		<th width="180"><div><?php echo get_phrase('subject_category');?></div></th>
                    		<th><div><?php echo get_phrase('subject_name');?></div></th>
                    		<th><div><?php echo get_phrase('teacher');?></div></th>
                    		<th><div><?php echo get_phrase('options');?></div></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php $count = 1;
											foreach($subjects as $row):
                                $class = $this->db->get_where('class', array('class_id' => $row['class_id']))->result_array();
                                                foreach ($class as $c):
                                                    
                                                
                                                ?>
                                <?php
                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $c['class_id']))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $c['name'], 'name_numeric' => $c['name_numeric']))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                        ?>
                        <tr>
							<td><?php echo $this->crud_model->get_type_name_by_id('class',$row['class_id']).' '.$c['name_numeric'].$sec_name;?></td>
							<td><?php echo $this->db->get_where('subject_category_creche', array('category_id' => $row['category_id']))->row()->name;?></td>
							<td><?php echo $row['name'];?></td>
							<td><a href="<?php echo site_url($account_type.'/teacher'); ?>"><?php echo $this->crud_model->get_type_name_by_id('teacher',$row['teacher_id']);?></a></td>
							<td>
                            <div class="btn-group">
                                <?=get_action_button();?>
                                <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                                    <!-- EDITING LINK -->
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_subject_creche/'.$row['subject_id'].'/'.$class_name);?>');" id="sub_edit" style="color: green;">
                                            <i class="entypo-pencil"></i>
                                                <?php echo get_phrase('edit');?>
                                            </a>
                                                    </li>
                                    <li class="divider"></li>

                                    <!-- DELETION LINK -->
                                    <li>
                                        <a href="#" onclick="deleteSubject('<?php echo $row['subject_id'];?>', '<?php echo $class_id;?>');" style="color: red;">
                                            <i class="entypo-trash"></i>
                                                <?php echo get_phrase('delete');?>
                                            </a>
                                                    </li>
                                </ul>
                            </div>
        					</td>
                        </tr>
                        <?php endforeach;
                    endforeach?>
                    </tbody>
                </table>
                <hr>
                <div class="row">
                    <div class="col-sm-6 pull-left">
                                <strong><em>Use this button to import all the previous academic year's subjects for all the classes!</em></strong>
                                <?php
                                    echo form_open(site_url('admin/subject_creche/import') , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subj_import_form_mass','target'=>'_top'));
                                ?>
                                <div class="form-group">
                                <div class="col-sm-4">
                                      <button type="submit" class="btn btn-primary" id="subj_import"><?php echo get_phrase('import_all_subjects');?></button>
                                  </div>
                                 </div>
                                </form>
                            </div>    
                
                <?php
                    if($subjects_rows != 0) {
                        //Display a button for user to use to add same subjects for another class
                        ?>
                            <div class="col-sm-6 pull-right">
                                <strong><em>You can use this button to create the same subjects for another class!</em></strong>
                                <?php
                                    echo form_open(site_url('admin/subject_creche/import') , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subj_import_form','target'=>'_top'));
                                ?>
                                <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('class');?></label>
                                <div class="col-sm-5">
                                    <select name="class_id" class="form-control select2" id="new_class_id" style="width:100%;" required>
                                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                                    	<?php
                                    	$name_array = array('CRECHE');
                                    	$this->db->where_in('name', $name_array);
										$classes = $this->db->get('class')->result_array();
										foreach($classes as $row):
										?>

                                         <?php
                                            //add section A or B if the class has more than one section
                                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                                            $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                                            $sec_name = '';
                                            if($class_has_more_sections > 1) {
                                                $sec_name = $section_name;
                                            }
                                        ?>
                                    		<option value="<?php echo $row['class_id'];?>">
                                                    <?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?>
                                            </option>
                                        <?php
										endforeach;
										?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                      <button type="submit" class="btn btn-danger" id="subj_import"><?php echo get_phrase('add_subjects');?></button>
                                  </div>
                                </div>
                                </form>
                            </div>
                        <?php
                    }
                ?>
                </div>
			</div>
            <!----TABLE LISTING ENDS--->
            
            <!----CATEGORY CREATION FORM STARTS---->
			<div class="tab-pane box" id="add_cat" style="padding: 5px">
                <div class="box-content">
                	<?php echo form_open(site_url('admin/subject_category/create') , array('id' => 'cat_form', 'class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
                        <div class="padded">
                            <div class="form-group">
                                <label class="col-sm-3 col-xs-3 control-label"><?php echo get_phrase('category_name');?></label>
                                <div class="col-sm-5 col-xs-5">
                                    <input type="text" class="form-control" id="cat_name" name="name" autofocus="true" required="required" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                                </div>
                              <div class="col-sm-4 col-xs-4">
                                  <button type="submit" class="btn btn-info"><?php echo get_phrase('add_category');?></button>
                              </div>
					        </div>
				   	    </div> 
                    </form><hr>
                    
                    <table class="table table-responsive" id="category_tbl">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>CATEGORY NAME</th>
                                <th>EDIT</th>
                                <th>DELETE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                               $all_cat =  $this->db->get('subject_category_creche')->result_array();
                               $sn = 1;
                                foreach($all_cat as $row):
                            ?>
                            <tr>
                                <td><?= $sn; ?></td>
                                <td>
                                    <div id="raw_<?= $row['category_id']; ?>"><?= $row['name']; ?></div>
                                    <div id="edit_<?= $row['category_id']; ?>" style="display: none;">
                                        <?php echo form_open(site_url('admin/subject_category/do_update/'.$row['category_id']) , array('class' => 'form-horizontal form-groups-bordered validate'));?>
                                            <input type="text" class="form-control" size="40" id="edit_category_name_<?= $row['category_id']; ?>" name="name" value="<?php echo $row['name'];?>" autofocus="true" required/>
                                		    <input type="hidden" value="<?= $row['category_id']; ?>" id="cat_id_<?= $row['category_id']; ?>">
                                		    <a href="javascript:void(0);" class="btn btn-info" onclick="edit_category('<?= $row['category_id']; ?>')">Update</a>
                                		</form>
                                    </div>
                                
                                </td>
                                <td><button class="btn btn-success btn-sm" id="btn_<?= $row['category_id']; ?>" onclick="show_cat_edit('<?= $row['category_id']; ?>')"><i class="entypo-pencil"></i></button></td>
                                <td><button class="btn btn-danger btn-sm" onclick="delete_cat('<?= $row['category_id']; ?>')"><i class="entypo-trash"></i></button></td>
                            </tr>
                            <?php 
                            $sn++;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
			</div>
			<!----CATEGORY CREATION FORM ENDS-->


			<!----CREATION FORM STARTS---->
			<div class="tab-pane box" id="add" style="padding: 5px">
                <div class="box-content">
                	<?php echo form_open(site_url('admin/subject_creche/create') , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subject_add_form'));?>
                        <div class="padded">
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('subject_name');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="name" autofocus="true" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('subject_category');?></label>
                                <div class="col-sm-5">
                                    <select name="category_id" class="form-control select2" style="width:100%;">
                                        <option value=""><?php echo get_phrase('select_category');?></option>
                                    	<?php
										$categories = $this->db->get('subject_category_creche')->result_array();
										foreach($categories as $row):
										?>
                                    		<option value="<?php echo $row['category_id'];?>"><?php echo $row['name'];?></option>
                                        <?php
										endforeach;
										?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('class');?></label>
                                <div class="col-sm-5">
                                    <select name="class_id" class="form-control select2" style="width:100%;" required>
                                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                                    	<?php
                                    	$name_array = array('CRECHE');
                                    	$this->db->where_in('name', $name_array);
										$classes = $this->db->get('class')->result_array();
										foreach($classes as $row):
										?>

                                         <?php
                                            //add section A or B if the class has more than one section
                                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                                            $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                                            $sec_name = '';
                                            if($class_has_more_sections > 1) {
                                                $sec_name = $section_name;
                                            }
                                        ?>
                                    		<option value="<?php echo $row['class_id'];?>"
                                                <?php if($row['class_id'] == $class_id) echo 'selected';?>>
                                                    <?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?>
                                            </option>
                                        <?php
										endforeach;
										?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('teacher');?></label>
                                <div class="col-sm-5">
                                    <select name="teacher_id" class="form-control select2" style="width:100%;">
                                        <option value=""><?php echo get_phrase('select_teacher');?></option>
                                    	<?php
										$teachers = $this->db->get('teacher')->result_array();
										foreach($teachers as $row):
										?>
                                    		<option value="<?php echo $row['teacher_id'];?>"><?php echo $row['name'];?></option>
                                        <?php
										endforeach;
										?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                              <div class="col-sm-offset-3 col-sm-5">
                                  <button type="submit" class="btn btn-info"><?php echo get_phrase('add_subject');?></button>
                              </div>
					   </div>
					   	   
                    </form>
                </div>
			</div>
			<!----CREATION FORM ENDS-->

		</div>
	</div>
</div>


<!-----  DATA TABLE EXPORT CONFIGURATIONS ---->
<script type="text/javascript">

	jQuery(document).ready(function($)
	{
		var datatable = $("#table_export").dataTable();
		$('#category_tbl').dataTable();

        var class_name = '<?php echo $class_name; ?>';
        var raw_score = '<?php echo $raw_score; ?>';
        if(class_name === 'FORM' && raw_score == 'Yes') {
            $('#status').css('display', 'block');
        }else{
            $('#status').css('display', 'none');
        }
	});
	//delete category
	function delete_cat(id) {
	    showConfirmModal(
	        'Delete Category',
	        'If you delete this category, all subjects and students records under this category will be affected. Are you sure you really want to delete it?',
	        function() {
	            // On confirm - show loading and make AJAX call
	            showAjaxModal_alert('Deleting category, please wait...', 'Loading');
	            
	            $.ajax({
	               url: '<?php echo site_url('admin/subject_category/delete/') ?>' + id, 
	               success: function(response) {
	                  showAjaxModal_alert('Category Successfully Deleted', 'Success', true);
	               },
	               error: function(err) {
	                  showAjaxModal_alert('Failed to delete category: ' + err.responseText, 'Error');
	               }
	            });
	        },
	        '<?php echo get_phrase('delete');?>',
	        'danger'
	    );
	}
	
	//delete subject
	function deleteSubject(subject_id, class_id) {
	    showConfirmModal(
	        'Delete Subject',
	        'Are you sure you want to delete this subject?',
	        function() {
	            // On confirm - show loading and make AJAX call
	            showAjaxModal_alert('Deleting subject, please wait...', 'Loading');
	            
	            $.ajax({
	                url: '<?php echo site_url('admin/subject_creche/delete/') ?>' + subject_id + '/' + class_id,
	                type: 'POST',
	                dataType: 'json',
	                success: function(response) {
	                    // Close any existing modals first
	                   // $('.modal').modal('hide');
	                    
	                    if(response.status === 'success') {
	                        // Show success modal, then reload after user closes it or after delay
	                        showAjaxModal_alert(response.message || 'Subject Deleted Successfully', 'Success', false, false);
	                        
	                        // Reload page after 2 seconds to show the updated list
	                        setTimeout(function() {
	                            window.location.reload();
	                        }, 2000);
	                    } else {
	                        showAjaxModal_alert(response.message || 'Failed to delete subject', 'Error');
	                    }
	                },
	                error: function(xhr, status, error) {
	                    console.error('AJAX error:', status, error);
	                    console.error('Response text:', xhr.responseText);
	                    showAjaxModal_alert('An error occurred while deleting the subject. Please try again.', 'Error');
	                }
	            });
	        },
	        '<?php echo get_phrase('delete');?>',
	        'danger'
	    );
	}
	
	//show edit field
	function show_cat_edit(id) {
	    $('#raw_' + id).css('display', 'none');
	    $('#edit_' + id).css('display', 'block');
	    
	    //remove the onclick on the button
	   // $('#btn_' + id).removeAtrr('onclick');
	    //$('#btn_' + id i).text('Update');
	}
	//edit category
	function edit_category(id) {
	    const cat_name = $('#edit_category_name_' + id).val();
        const cat_id = $('#cat_id_' + id).val();
        
        if(!cat_name || cat_name.trim() === '') {
            showAjaxModal_alert('Category name cannot be empty', 'Error');
            return false;
        }
        
        showAjaxModal_alert('Updating category, please wait...', 'Loading');
        
	    $.ajax({
	       url: '<?php echo site_url('admin/subject_category/do_update/') ?>' + cat_name + '/' + cat_id, 
	       success: function(response) {
	          showAjaxModal_alert('Category Successfully Updated', 'Success', true);
	       },
	       error: function(err) {
	          showAjaxModal_alert('Failed to update category: ' + err.responseText, 'Error');
	       }
	    });
	}
	
	//add category
	$('#cat_form').submit(function(e) {
	    e.preventDefault();
	    
	    const cat_name = $('#cat_name').val();
	    
	    if(cat_name == '' || cat_name == null) {
	        showAjaxModal_alert('Category name cannot be empty!', 'Error');
	        return false;
	    }
	    
	    showAjaxModal_alert('Creating category, please wait...', 'Loading');
	    
	    $.ajax({
	       url: '<?php echo site_url('admin/subject_category/create/') ?>' + cat_name, 
	       success: function(response) {
	          showAjaxModal_alert('Category Successfully Created', 'Success', true);
	       },
	       error: function(err) {
	          showAjaxModal_alert('Failed to create category: ' + err.responseText, 'Error');
	       }
	    });
	});
	
	$('#subj_import_form').submit(function(e) {
	    e.preventDefault();
	    
	    //get new class and old class ids, year and term
	    const new_class_id = $('#new_class_id').val();
	    const old_class_id = '<?php echo $class_id; ?>';
	    const year = '<?php echo $year; ?>';
	    const term = '<?php echo $term; ?>';
	    
	    if(new_class_id == old_class_id) {
	        showAjaxModal_alert('Error! It seems you have selected the same class. Please select a different class', 'Error');
	        return false;
	    }
	    
	    showAjaxModal_alert('Importing subjects, please wait...', 'Loading');
	    
	    //send info via ajax for processing
	    $.ajax({
	       url: '<?php echo site_url('admin/do_subjects_import_creche/') ?>' + new_class_id + '/' + old_class_id + '/' + year + '/' + term, 
	       type: 'POST',
	       success: function(response) {
	           //if it returns success
	           if(response == 'success') {
	               showAjaxModal_alert('Subjects Successfully Added. Redirecting to class page...', 'Success', false);
	               
	               setTimeout(() => {
    	    	     window.location.href = '<?php echo site_url('admin/subject_creche/'); ?>' + new_class_id;
    	    	    }, 2000);
	           } else if(response == 'failed') {
	               showAjaxModal_alert('Subjects Were Not Added. Please try again!', 'Error');
	           } else if(response == 'error') {
	               showAjaxModal_alert('This Class Already Has Subjects Registered For This Term. If you are not sure, please check by clicking on this class under Subject menu. In case you want to UPDATE or ADD new subjects to this class, kindly do it individually.', 'Warning');
	           }
	       },
	       error: function(error_s) {
    			showAjaxModal_alert('Sorry, Subjects Could Not Be Added. Please try again!', 'Error');
	       }
	    });
	});

//mass import-yearly(academic yearly)
	$('#subj_import_form_mass').submit(function(e) {
	    e.preventDefault();
	    
	    showAjaxModal_alert('Importing all subjects, please wait...', 'Loading');
	    
	    //send info via ajax for processing
	    $.ajax({
	       url: '<?php echo site_url('admin/do_subjects_import_mass_creche') ?>', 
	       type: 'POST',
	       success: function(response) {
	           if(response == 'error') {
	               showAjaxModal_alert('Subjects Were Already Registered For This Term/Year. If you are not sure, please check by clicking on each class under Subject menu. In case you want to UPDATE or ADD new subjects to a class, kindly do it individually.', 'Warning');
	           } else {
	               showAjaxModal_alert('Subjects Successfully Imported', 'Success', true);
	           }
	       },
	       error: function(error_s) {
	           showAjaxModal_alert('Sorry, Subjects Could Not Be Imported. Please try again!', 'Error');
	       }
	    });
	});

	// AJAX handler for add subject form
	$('#subject_add_form').submit(function(e) {
	    e.preventDefault();
	    
	    // Validate required fields
	    const subjectName = $(this).find('input[name="name"]').val();
	    const classId = $(this).find('select[name="class_id"]').val();
	    
	    if(!subjectName || subjectName.trim() === '') {
	        showAjaxModal_alert('Subject name is required', 'Error');
	        return false;
	    }
	    
	    if(!classId || classId === '') {
	        showAjaxModal_alert('Please select a class', 'Error');
	        return false;
	    }
	    
	    // Get form data and URL
	    const formData = $(this).serialize();
	    const formUrl = $(this).attr('action');
	    
	    // Show loading modal
	    showAjaxModal_alert('Adding subject, please wait...', 'Loading');
	    
	    $.ajax({
	        url: formUrl,
	        type: 'POST',
	        data: formData,
	        beforeSend: function() {
	            // Disable submit button to prevent double submission
	            $('#subject_add_form button[type="submit"]').prop('disabled', true).text('Adding...');
	        },
	        success: function(responseText) {
	            try {
	                // Try to parse JSON response
	                const response = JSON.parse(responseText);
	                
	                if(response.status === 'success') {
	                    // Show success message with auto-reload
	                    showAjaxModal_alert('Subject Added Successfully', 'Success', true);
	                } else {
	                    showAjaxModal_alert(response.message || 'Failed to add subject', 'Error');
	                    $('#subject_add_form button[type="submit"]').prop('disabled', false).text('<?php echo get_phrase('add_subject');?>');
	                }
	            } catch(e) {
	                // If not JSON, check for simple text responses
	                if(responseText.trim() === 'success' || responseText.indexOf('success') !== -1) {
	                    showAjaxModal_alert('Subject Added Successfully', 'Success', true);
	                } else {
	                    console.error('Response parse error:', e);
	                    console.error('Response text:', responseText);
	                    showAjaxModal_alert('Failed to add subject. Please try again.', 'Error');
	                    $('#subject_add_form button[type="submit"]').prop('disabled', false).text('<?php echo get_phrase('add_subject');?>');
	                }
	            }
	        },
	        error: function(xhr, status, error) {
	            console.error('AJAX error:', status, error);
	            showAjaxModal_alert('An error occurred while adding the subject. Please try again.', 'Error');
	            $('#subject_add_form button[type="submit"]').prop('disabled', false).text('<?php echo get_phrase('add_subject');?>');
	        }
	    });
	});
   
</script>
