<style type="text/css">
	p{
		font-size: 16px;
		text-align: justify;
	}
</style>

<?php
    $param2 = str_replace('-', '&', $param2); //let's try and replace the '-' with '&' back.
	$param2 = explode(' ', $param2);
 ?>
<p style="text-align: left;">One or more of some parents' names and numbers you are submitting match with the following existing info::</p> <hr>
<p><strong>

	<?php 
		for($i = 0; $i < count($param2); $i++) {
			if(is_numeric(substr($param2[$i], -1, 1))) {
				 echo $param2[$i].', '; //if the last digit is a numeric, bring a comma (,) sign and space.
			}else{
				echo $param2[$i].' '; //else just bring a space.
			}
			
		}
	?>
	

</strong> do you still want to proceed anyway? </p><hr>
<p>Click <mark style="background-color: green; color: #ffffff" class="rounded-md p-1">Yes</mark> if you want to update this parent's information in the system. It means you are registering another child for these same parents.</p> 
<p>Click <mark style="background-color: red; color: #ffffff;" class="rounded-md p-1">No</mark> if this is an error! This allows you to correct the parent's information before proceeding. </p>
	            	           


