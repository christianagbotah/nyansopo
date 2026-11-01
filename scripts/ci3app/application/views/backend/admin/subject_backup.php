<?php 
    $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;

?>

<style type="text/css">
	#preloader2{
		width: 100%; 
		min-height: 1020px; 
		background-color: #fff; 
		text-align: center; 
		z-index: 99999; 
		position: absolute;
		top: 350px;
	}
	
	.ajax_alert {
	    position: relative;
	    top: 100px;
	}
</style>
<div id="loader_holder_subj" style="display: none">
    <div id="preloader2"  style="display: none">
    <img id="loader_logo" src="<?php echo base_url();?>assets/images/lightworldtech.png" width="100px">
    <img id="loader" src="<?php echo base_url();?>assets/images/validate.gif" width="64px">
    <p id="loading_txt" style="padding-top: 15px; font-weight: bold;">Importing your subjects, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span></p>
    </div>
</div>

	
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
                    		<th><div><?php echo get_phrase('class');?></div></th>
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
							<td><?php echo $row['name'];?></td>
							<td><a href="<?php echo site_url($account_type.'/teacher'); ?>"><?php echo $this->crud_model->get_type_name_by_id('teacher',$row['teacher_id']);?></a></td>
							<td>
                            <div class="btn-group">
                                <?=get_action_button();?>
                                <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                                    <!-- EDITING LINK -->
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_edit_subject/'.$row['subject_id'].'/'.$class_name);?>');" id="sub_edit" style="color: green;">
                                            <i class="entypo-pencil"></i>
                                                <?php echo get_phrase('edit');?>
                                            </a>
                                                    </li>
                                    <li class="divider"></li>

                                    <!-- DELETION LINK -->
                                    <li>
                                        <a href="#" onclick="confirm_modal('<?php echo site_url('admin/subject/delete/'.$row['subject_id'].'/'.$class_id);?>');" style="color: red;">
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
                                <strong><em>Use this button to import all the subjects from the previous academic year for all the classes!</em></strong>
                                <?php
                                    echo form_open(site_url('admin/subject/import') , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subj_import_form_mass','target'=>'_top'));
                                ?>
                                <div class="form-group">
                                <div class="col-sm-4">
                                      <button type="submit" class="btn btn-primary" id="subj_import"><?php echo get_phrase('import_all_subjects');?></button>
                                  </div>
                                 </div>
                                
                                <div id="same_class_id_error_mass" style="color: red; display: none;"></div>
                                </form>
                            </div>    
                
                <?php
                    if($subjects_rows != 0) {
                        //Display a button for user to use to add same subjects for another class
                        ?>
                            <div class="col-sm-6 pull-right">
                                <strong><em>You can use this button to create the same subjects for another class!</em></strong>
                                <?php
                                    echo form_open(site_url('admin/subject/import') , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subj_import_form','target'=>'_top'));
                                ?>
                                <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('class');?></label>
                                <div class="col-sm-5">
                                    <select name="class_id" class="form-control select2" id="new_class_id" style="width:100%;" required>
                                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                                    	<?php

                    getFullClassList();
                ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                      <button type="submit" class="btn btn-danger" id="subj_import"><?php echo get_phrase('add_subjects');?></button>
                                  </div>
                                </div>
                                <div id="same_class_id_error" style="color: red; display: none;"></div>
                                </form>
                            </div>
                        <?php
                    }
                ?>
                </div>
			</div>
            <!----TABLE LISTING ENDS--->


			<!----CREATION FORM STARTS---->
			<div class="tab-pane box" id="add" style="padding: 5px">
                <div class="box-content">
                	<?php echo form_open(site_url('admin/subject/create') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
                        <div class="padded">
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('name');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="name" autofocus="true" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                                </div>
                            </div>
                            <div class="form-group" id="status">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('it_is_a_core_subject');?></label>
                                <div class="col-sm-2">
                                    <input type="checkbox" class="form-control" name="status" value="1">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('class');?></label>
                                <div class="col-sm-5">
                                    <select name="class_id" class="form-control select2" style="width:100%;" required>
                                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                                    	<?php

                    getFullClassList();
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

        var class_name = '<?php echo $class_name; ?>';
        var raw_score = '<?php echo $raw_score; ?>';
        if(class_name === 'FORM' && raw_score == 'Yes') {
            $('#status').css('display', 'block');
        }else{
            $('#status').css('display', 'none');
        }
	});
	
	$('#subj_import_form').submit(function(e) {
	    e.preventDefault();
	    //get new class and old class ids, year and term
	    const new_class_id = $('#new_class_id').val();
	    const old_class_id = '<?php echo $class_id; ?>';
	    const year = '<?php echo $year; ?>';
	    let term = '<?php echo $term; ?>';
        let class_name = '<?php echo $class_name; ?>';

        
        

        if(class_name == 'JHSS') {
            term = '<?php echo $sem; ?>';
        }


	    if(new_class_id == old_class_id) {
	        $('#same_class_id_error').css('display', 'block');
	        $('#same_class_id_error').text('Error! It seems you have selected the same class. Please select a different class');
	        
	        return false;
	    }
	    
	    //send info via ajax for processing
	    $.ajax({
	       url: '<?php echo site_url('admin/do_subjects_import/') ?>' + new_class_id + '/' + old_class_id + '/' + year + '/' + term + '/' + class_name, 
	       type: 'POST',
	       beforeSend: function(){
	           //show the preloader
	           $('#loader_holder_subj').css('display', 'block');
	           $('#preloader2').css('display', 'block');
				$('#main_div').click(function(event) {
					event.preventDefault();
					return false;
				});
				$('#main_div').css('cursor', 'not-allowed');
	       },
	       
	       success: function(response) {
	           //if it returns success
	           if(response == 'success') {
	               //show success message
        			$('#preloader2').html(
        				'<div class="alert alert-success alert-dismissible ajax_alert" role="alert" aria-label="alert"><button class="close" data-dismiss="alert">&times;</button><h3>Subjects Successfully Added.</h3><h4 align="center">Please wait, you are being redirected to the class page!</h4></div>'
        			);
        			
        			setTimeout(() => {
    	    	     window.location.href = '<?php echo site_url('admin/subject/'); ?>' + new_class_id;
    	    	    }, 3000);
	           } else if(response == 'failed') {
	               //show success message
        			$('#preloader2').html(
        				'<div class="alert alert-danger alert-dismissible ajax_alert" role="alert" aria-label="alert"><button class="close" data-dismiss="alert">&times;</button><h3>Subjects Were Not Added.</h3><h4 align="center">Please try again!</h4></div>'
        			);
        			
        			setTimeout(() => {
    	    	     window.location.reload();
    	    	    }, 4000);
	           } else if(response == 'error') {
	               //show success message
	                $('#preloader2').css('padding', '20px');
        			$('#preloader2').html(
        				'<div class="alert alert-danger alert-dismissible ajax_alert" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="close" onclick=" return window.location.reload();"><span aria-hidden="true">&times;</span></button><h3>This Class Already Has Subjects Registered For This Term/Semester</h3><h4 align="center">If you are not sure, please check by clicking on this class under Subject menu.</h4><h4 align="center">In case you want to UPDATE or ADD new subjects to this class, kindly do it individually.</h4></div>'
        			);
        			
        			$('#main_div').click(function(event) {
					event.returnValue = true;
				});
				$('#main_div').css('cursor', 'arrow');

	           }
	       },
	       
	       error: function(error_s) {
	          
    			
    			//show error message
    			$('#preloader2').html(
    				'<div class="alert alert-danger alert-dismissible ajax_alert" role="alert" ><button type="button" class="close" data-dismiss="alert" aria-label="close">&times;</button><h3 align="center">Sorry, Subjects Could Not Be Added.</h3><h4 align="center">Please try again!</h4></div>'
    			);
    			
    			
    			 setTimeout(() => {
	    	    window.location.reload();
	    	 }, 4000);
	       }
	       
	       
	    });
	});

//mass import-yearly(academic yearly)
    
	$('#subj_import_form_mass').submit(function(e) {
	    e.preventDefault();
	    
	    //send info via ajax for processing
	    $.ajax({
	       url: '<?php echo site_url('admin/do_subjects_import_mass') ?>', 
	       type: 'POST',
	       beforeSend: function(){
	           //show the preloader
	           $('#loader_holder_subj').css('display', 'block');
	           $('#preloader2').css('display', 'block');
				$('#main_div').click(function(event) {
					event.preventDefault();
					return false;
				});
				$('#main_div').css('cursor', 'not-allowed');
	       },
	       
	       success: function(response) {

	               if(response == 'error') {
	                   //show success message
	                   $('#preloader2').css('padding', '20px');
            			$('#preloader2').html(
            				'<div class="alert alert-danger alert-dismissible ajax_alert" role="alert" aria-label="alert"><button class="close" data-dismiss="alert" onclick="return window.location.reload()"><span aria-hidden="true">&times;</span></button><h3>Subjects Were Already Registered For This Year.</h3><h4 align="center">If you are not sure, please check by clicking on each class under Subject menu.</h4><h4 align="center">In case you want to UPDATE or ADD new subjects to a class, kindly do it individually.</h4></div>'
            			);
            			
            			$('#main_div').click(function(event) {
        					event.returnValue = true;
        				});
        				$('#main_div').css('cursor', 'arrow');
	               }else {
	                   //show success message
            			$('#preloader2').html(
            				'<div class="alert alert-success alert-dismissible ajax_alert" role="alert" aria-label="alert"><button class="close" data-dismiss="alert">&times;</button><h3>Subjects Successfully Imported.</h3></div>'
            			);
            			
            			setTimeout(() => {
        	    	     window.location.reload();
        	    	    }, 2000);
	               }
	       },
	       
	       error: function(error_s) {
	          
    			
    			//show error message
    			
    			$('#preloader2').html(
    				'<div class="alert alert-danger alert-dismissible ajax_alert" role="alert" ><button type="button" class="close" data-dismiss="alert" aria-label="close">&times;</button><h3 align="center">Sorry, Subjects Could Not Be Imported.</h3><h4 align="center">Please try again!</h4></div>'
    			);
    			
    			
    			 setTimeout(() => {
	    	    window.location.reload();
	    	 }, 4000);
	       }
	       
	       
	    });
	});
   
</script>
