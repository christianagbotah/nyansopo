<?php 
$page_name = 'admit_student';
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title">
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('add_parent');?>
            	</div>
            </div>
			<div class="panel-body">
				
                <?php echo form_open(site_url('admin/parent/create/'.$page_name) , array('class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data', 'id' => 'parent_add_st_form'));?>
                    
					<div class="form-group">
						<label for="field-1" class="col-sm-3 control-label"><?php echo get_phrase('name');?></label>
                        
						<div class="col-sm-5">
							<input type="text" class="form-control" name="name" id="p_name" data-validate="required" required="required" data-message-required="<?php echo get_phrase('value_required');?>" autofocus value="">
						</div>
					</div>
					
					<div class="form-group">
						<label for="guardian_relationship" class="col-sm-3 control-label">Guardian is the <span style="color:red">*</span></label>
						<div class="col-sm-5">
							<select name="guardian_is_the" class="form-control" id="guardian_relationship" required>
								<option value="">Select Relationship</option>
								<option value="father">Father</option>
								<option value="mother">Mother</option>
								<option value="other">Other Person</option>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label for="guardian_gender" class="col-sm-3 control-label">Guardian Gender</label>
						<div class="col-sm-5">
							<select name="guardian_gender" class="form-control" id="guardian_gender">
								<option value="Male">Male</option>
								<option value="Female">Female</option>
							</select>
						</div>
					</div>
                    
					<div class="form-group">
						<label for="field-1" class="col-sm-3 control-label"><?php echo get_phrase('email');?></label>
						<div class="col-sm-5">
							<input type="email" class="form-control" name="email" value="">
						</div>
					</div>
					
					<div class="form-group">
						<label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('password');?></label>
                        
						<div class="col-sm-5">
							<input type="password" class="form-control" name="password" value="">
						</div>
					</div>
					
					<div class="form-group">
						<label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('phone');?></label>
                        
						<div class="col-sm-5">
							<input type="tel" class="form-control" name="phone[]" value="" required="required">
						</div>
					</div>
					
					<div class="form-group">
						<label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('address');?></label>
                        
						<div class="col-sm-5">
							<input type="text" class="form-control" name="address" value="" required="required">
						</div>
					</div>
					
					<div class="form-group">
						<label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('profession');?></label>
                        
						<div class="col-sm-5">
							<input type="text" class="form-control" name="profession" value="">
						</div>
					</div>
           
           <div class="form-group">
						<label for="field-2" class="col-sm-3 control-label">A PTA Executive</label>
                        
						<div class="col-sm-5">
							<input type="checkbox" class="form-check-inline" name="executive" id="executive" value="0" onchange="value_change()"> <span id="yes_no" style="color: #b3afaf">No</span>
						</div>
					</div>   

					<div class="form-group" id="designation_holder" style="display: none">
						<label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('designation');?></label>
                        
						<div class="col-sm-5">
							<input type="text" class="form-control" name="designation" value="">
						</div>
					</div>

          <div class="form-group">
						<div class="col-sm-offset-3 col-sm-5">
							<button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-plus-sign"></i> <?php echo get_phrase('add_parent');?></button>
						</div>
					</div>
                <?php echo form_close();?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	$(function() {
		value_change();
	});
	
	function value_change() {
       let is_executive = $('#executive').filter(':checked').length;
       if(is_executive > 0) {
            $('#executive').val(1);
            $('#yes_no').removeAttr('style');
            $('#designation').attr('required', 'required');
            $('#designation_holder').fadeIn('slow');
            $('#yes_no').text('YES');
            $('#yes_no').css('color', '#000000');
       } else {
        $('#executive').val(0);
        		$('#designation').removeAttr('required');
        		$('#designation_holder').fadeOut('slow');
        		$('#yes_no').text('NO');
        		$('#yes_no').css('color', '#b3afaf');
       }
    }

$('#parent_add_st_form').submit(function(event) {
    event.preventDefault();
    showAjaxModal_alert('Saving parent...', 'loading');
    
    var guardianRelationship = $('#guardian_relationship').val();

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        dataType: 'json',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false
    })
    .done(function(data) {
        if(data.status === 'success') {
            showAjaxModal_alert('Parent added successfully', 'success', false);
            setTimeout(() => {
                $('#modal_ajax .close').click();
                $('#modal_alert .close').click();
                reloadParentDropdownWithRelationship(data.parent_id, guardianRelationship);
            }, 2000);
        } else {
            showAjaxModal_alert(data.message || 'Failed to add parent', 'error');
        }
    })
    .fail(function() {
        showAjaxModal_alert('An error occurred. Please try again.', 'error');
    });
});

function reloadParentDropdownWithRelationship(newParentId, relationship) {
    $.ajax({
        url: '<?php echo site_url('admin/get_parent_dropdown'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                let options = '<option value=""></option>';
                options += '<option value="new">➕ Register New Guardian</option>';
                
                response.parents.forEach(function(parent) {
                    let selected = parent.parent_id == newParentId ? 'selected' : '';
                    let phone = parent.phone ? ' • ' + parent.phone : '';
                    let profession = parent.profession ? ' • ' + parent.profession : '';
                    options += '<option value="' + parent.parent_id + '" ' + selected;
                    options += ' data-name="' + (parent.name || '') + '"';
                    options += ' data-phone="' + (parent.phone || '') + '"';
                    options += ' data-email="' + (parent.email || '') + '"';
                    options += ' data-address="' + (parent.address || '') + '"';
                    options += ' data-profession="' + (parent.profession || '') + '"';
                    options += '>' + parent.name + phone + profession + '</option>';
                });
                
                $('#parent_id').html(options).val(newParentId);
                
                if(typeof $.fn.select2 !== 'undefined') {
                    $('#parent_id').select2('destroy').select2({
                        allowClear: true,
                        placeholder: 'Search or select guardian...',
                        width: '100%'
                    });
                }
                
                $('#guardian_type_field').slideDown();
                $('#guardian_type').val(relationship).trigger('change');
            }
        }
    });
}

</script>
