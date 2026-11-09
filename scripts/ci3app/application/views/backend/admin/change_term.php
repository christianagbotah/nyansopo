<?php echo form_open(site_url('admin/change_term') , array('id' => 'term_change'))
;
$running_year = get_settings('running_year');
?>
<li>
	
	<div class="form-group">
		<select name="running_term" id="change_running_term" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
		  	<?php $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
		  	<option value="" disabled="disabled"><?php echo get_phrase('select_running_term');?></option>
		  	<?php for($i = 1; $i <= 3; $i++):?>
		      	<option value="<?php echo $i;?>"
		        <?php if($running_term == $i) echo 'selected';?>>
		          	<?php echo $i;?>
		      	</option>
		  <?php endfor;?>
		</select>
	</div>
	
	
</li>
<?php echo form_close();?>



<script type="text/javascript">
    $('#change_running_term').change(function(evt) {
    	evt.preventDefault();   	

    	var url = '<?php echo site_url('admin/change_term') ?>';
    	var formValue = $(this).val();
    	var running_term = Number('<?php echo $running_term; ?>');

    	if(formValue == 1) {
    		url = '<?php echo site_url('admin/change_session/yes') ?>';

    		showAjaxModal_prompt('Are you changing to a new academic year and term?\nIf you choose YES, we will attempt to update the year and do all the necessary updates such as:\nenrolling students and subjects, adding examination etc.');

    		$('#prompt_yes').click(function(e) {
    			$(this).attr('data-dismiss', 'modal');
    			url = '<?php echo site_url('admin/change_session/yes/enroll') ?>';
    			startWork(formValue, url);//do the work
    		});

    		$('#prompt_cancel').click(function(e) {
    			url = '<?php echo site_url('admin/change_session/yes') ?>';
    			startWork(formValue, url);//do the work
    		});
    	} else {

    		if(formValue > running_term) {
    			//check if this term and year already exist
    			$.ajax({
    				url: '<?php echo site_url('admin/year_term_exist/'.$running_year.'/'); ?>' + formValue,
    				type: 'post',

    			})
    			.done(function(r) {
    				if(r > 0) {
    					//no need asking user to confirm if enrolling to a new term or not
    					startWork(formValue, url);//do the work
    				} else {

    					//find out if user wants to effect new enrollment to another term
		    			showAjaxModal_prompt('Are you changing to a new academic term?\nIf you choose YES, we will attempt to do all the necessary updates such as:\nenrolling students and subjects, adding examination etc.');

			    		$('#prompt_yes').click(function(e) {
			    			$(this).attr('data-dismiss', 'modal');
			    			url = '<?php echo site_url('admin/change_term/yes'); ?>';
			    			startWork(formValue, url);//do the work
			    		});

			    		$('#prompt_cancel').click(function(e) {
			    			url = '<?php echo site_url('admin/change_term'); ?>';
			    			startWork(formValue, url);//do the work
			    		});
    				}
    			});

    		} else {
    			startWork(formValue, url);//do the work
    		}
    		
    	}
    });

    function startWork(formValue, url) {

    	var previousJsonResponseLength = false;
    	var jsonParseResponse;

    	$.ajax({
    		url: url,
  			type: 'post',
  			data: {running_term: formValue},
  			//contentType: false,
  			//processData: false,
  			cache: false,



    		xhrFields: {
    			onprogress: function(ev) {
    				var response = ev.currentTarget.response;
    				var jsonResponse;

    				if(previousJsonResponseLength === false) {
    					jsonResponse = response;
    					previousJsonResponseLength = response.length;

    				} else {
    					jsonResponse = response.substring(previousJsonResponseLength);
    					previousJsonResponseLength = response.length;
    				}

    				//process the response now
    				jsonParseResponse = JSON.parse(jsonResponse);
    				showAjaxModal_alert(jsonParseResponse.message + ' <i class="fa fa-spinner fa-pulse"></i>', 'Loading');
    			}
    		}
    	})
    	.done(function(data) {
    		$('#modal_alert i .fa-spinner').css('display', 'none');


    		if(jsonParseResponse.success == false) {

    			$('#modal_alert .modal-header button span i').removeAttr('style');
    			showAjaxModal_alert(jsonParseResponse.message, 'Error');

    			$('.close').click(function() {
    				location.reload();
    			});

    		} else if(jsonParseResponse.success == true) {
    			showAjaxModal_alert(jsonParseResponse.message, 'Success');

    			setTimeout(() => {
	    				location.reload();
	    				$('.close').click(); //close the alert
	    			}, 5000);

    		} else {
    			$('#modal_alert .modal-header button span i').removeAttr('style');
    			showAjaxModal_alert('We cannot identify the problem. Please try again later!', 'Error');

    			$('.close').click(function() {
    				location.reload();
    			});
    		}
    	})
    	.fail(function(err) {
    		$('#modal_alert .modal-header button span i').removeAttr('style');
    		showAjaxModal_alert('Error: ' + err.responseText, 'Error');
    	});
    }
</script>