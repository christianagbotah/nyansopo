<style type="text/css">
	p{
		font-size: 16px;
		text-align: justify;
	}
</style>

<?php
	$param2 = explode(' ', $param2);
 ?>
<p style="text-align: left;">One or more of some staffs' names and the numbers you are submitting match with the following existing info::</p> <hr>
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
<p>If you want to update this staff's information in the system, kindly pick the staff's data from the system and do the update there.</p> 
	            	           


