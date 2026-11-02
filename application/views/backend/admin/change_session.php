<?php echo form_open(site_url('admin/change_session') , array('id' => 'session_change'));?>
<li>
	
	<div class="form-group">
		<select name="running_year" id="change_running_year" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
		  	<option value="" disabled="disabled"><?php echo get_phrase('select_running_session');?></option>
		  	<?php
		  		echo populate_academic_year('yes');
		  	?>
		</select>
	</div>

</li>
<?php echo form_close();?>



<script type="text/javascript">


    	$('#session_change').change(function(e) {
    		//e.preventDefault();
    		formSubmitted();
    	});

    	///
    	function forced_proceed() {

    	}

    	function formSubmitted(force='no') {

    		var previousResponseLength = false;
    		var jsonParseResponse;
    		var val = $('#change_running_year').val();
    		var formUrl = '<?php echo site_url('admin/change_session'); ?>';

    		if(force == 'yes') {
    			formUrl = '<?php echo site_url('admin/change_session/no/no/yes'); ?>';
    		}

    		$.ajax({
    			url: formUrl,
    			type: 'post',
    			data: {'running_year': val},
    			//contentType: false,
    			//processData: false,
    			cache: false,
    			xhrFields: {
    				onprogress: function(ev) {

    					var jsonResponse;
    					var response = ev.currentTarget.response;

    					if(previousResponseLength === false) {
    						jsonResponse = response;
    						previousResponseLength = response.length;

    					} else {
    						jsonResponse = response.substring(previousResponseLength);
    						previousResponseLength = response.length;
    					}

    					jsonParseResponse = JSON.parse(jsonResponse);

    					showAjaxModal_alert(jsonParseResponse.message + ' <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

    				}
    			}
    		})
    		.done(function(data) {
    			$('#modal_alert i').css('display', 'none');

    			if(jsonParseResponse.success === false) {
    				$('#modal_alert .modal-header button span i').removeAttr('style');
    				showAjaxModal_alert(jsonParseResponse.message, 'Error');

    				$('.close').click(function() {
	    				location.reload();
	    			});

    			} else {
    				showAjaxModal_alert(jsonParseResponse.message, 'Success');
    				setTimeout(() => {
	    				location.reload();
	    				$('.close').click(); //close the alert
	    			}, 5000);
    			}


    		})
    		.fail(function(err) {
    			$('#modal_alert .modal-header button span i').removeAttr('style');
    			showAjaxModal_alert('Error: ' + err.responseText, 'Error');
    		})

    	}



    	
    	
	
</script>