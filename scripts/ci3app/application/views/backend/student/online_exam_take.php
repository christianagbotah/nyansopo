<hr>
<?php
	/**session_start();
	$_SESSION['id'] = $student_id;
	$_SESSION['user'];
	$data = array($_SESSION['user']);
	if(isset($_SESSION['user'])) {
		$session_count = $_SESSION['user'] + 1;
	}else{
		$session_count = 0;
	} **/

	$running_year = $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description;
    $running_term = $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description;
	$exam_ends_timestamp = strtotime(date('d-M-Y', $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id, 'running_year' => $running_year, 'term' => $running_term))->row()->exam_date)." ".$this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id, 'running_year' => $running_year, 'term' => $running_term))->row()->time_end);
	$current_timestamp = strtotime("now");
	$exam_info = $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id));

	$total_duration 	=	$exam_ends_timestamp - $current_timestamp;
	$total_hour 		= 	intval($total_duration / 3600);
	$total_duration 	-=	$total_hour * 3600;
	$total_minute 		=	intval($total_duration / 60);
	$total_second 		=	intval($total_duration % 60);

	$online_exam_row = $exam_info->row();
	$questions = $this->db->get_where('question_bank', array('online_exam_id' => $online_exam_id))->result_array();
	$total_marks = 0;
	foreach ($questions as $row) {
		$total_marks += $row['mark'];
	}
?>

<style type="text/css">
	#heading_area {
		background-color: #000000; 
		padding: 5px;  
		border-radius: 5px/3px;"
	}

	#heading_area h4 b{
				color: #62cc76;
	}

	 #heading_area h4 {
	 	color: #d2d2d2 !important;
	 }

	 #heading_area h3, #heading_area h5 {
	 	color: #f90b0b;
	 }

	 #clock_area {
	 	border: 2px groove black;
	 	padding: 10px;
	 	box-shadow: 0px 2px 4px 1px black;
	 	font-family: 'Orbitron', sans-serif;
	 	z-index: -1;
	 }
</style>
<div class="row">
	<div class="col-md-12 text-center">
		<div id="heading_area">
			<h3><?php echo $online_exam_row->title;?></h3>
			<h4>
				<b><?php echo get_phrase('subject');?></b>: <?php echo $this->db->get_where('subject', array('subject_id' => $online_exam_row->subject_id))->row()->name;?>
			</h4>
			<h4>
				<b><?php echo get_phrase('total_marks');?></b>: <?php echo $total_marks;?><b> | <?php echo get_phrase('time');?></b>: <?php echo ($online_exam_row->duration / 60).' '.get_phrase('minutes');?>
			</h4>

			<h4>
				<b><?php echo get_phrase('exam_has_to_be_submitted_within').': '; ?></b>: <?php echo date('D d-M-Y h:i:s', $exam_ends_timestamp);?>
			</h4>
			<hr>
			<h5>
				<b><?php echo get_phrase('instructions');?></b>: <?php echo $online_exam_row->instruction;?>
			</h5>
			<hr>
		</div>	
	</div>
</div>

<div class="row" style="position: sticky; position: -webkit-sticky; position: -moz-sticky; position: -o-sticky; top: 110px; z-index: 99; background-color: #ffffff;">
			<div class="col-md-4">
				<!--Error alert here-->
				<div class="alert alert-danger alert-dismissible" role="alert" id="update_alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
				</button>
				<strong>System has detected an update in the time allocated for this exam. Your page will be refreshed in <span id="update_counter" style="color: red;">00</span> seconds to update your time. <i>Don't worry, your works will not be affected.</i></strong>
				</div>

			</div>
			<div class="col-md-6 col-md-offset-3 col-sm-8 col-sm-2 col-lg-6 col-lg-offset-3 col-xs-12" id="clock_area" >
				<center>
					<div style="height:50px; font-size:30px; font-weight:400; color: green;" id="timer_value">

						<!-- HOUR TIMER -->
						<span id="hour_timer"> 0 </span>
						<span style="font-size:20px;" id="hour_name">Hour </span>

						<!-- SEPARATOR -->
						<span class="blink_text">:</span>

						<!-- MINUTE TIMER -->
						<span id="minute_timer"> 0 </span>
						<span style="font-size:20px;" id="minute_name">Minute </span>

						<!-- SEPARATOR -->
						<span class="blink_text">:</span>

						<!-- SECOND TIMER -->
						<span id="second_timer"> 0 </span>
						<span style="font-size:20px;" id="second_name">Second </span>
					</div>
				</center>
			</div>
		</div>
<hr>

	<?php echo form_open(site_url('student/submit_online_exam/'.$online_exam_id) , array('id' => 'answer_script', 'enctype' => 'multipart/form-data'));?>

	<?php $count = 1; foreach ($questions as $question):?>
	<div class="row">
		<div class="col-md-11">
			<h4><b><?php echo $count++;?>.</b>  <?php echo ($question['type'] == 'fill_in_the_blanks') ? str_replace('-', '__________', $question['question_title']) : $question['question_title'];?></h4>
		</div>
		<div class="col-md-1 text-right">
			<h4><b><?php echo $question['mark'];?></b></h4>
		</div>
	</div>
	<div class="row" style="padding: 15px;">
		<!-- multiple choice -->
		<?php if ($question['type'] == 'multiple_choice'): ?>
			<?php
	            if ($question['options'] != '' || $question['options'] != null)
	            	$options = json_decode($question['options']);
	            else
	            	$options = array();
	            for ($i = 0; $i < $question['number_of_options']; $i++):
			?>
			<div class="col-md-12" style="margin-bottom: 15px;">
				<div class="checkbox checkbox-replace color-green">
				    <input type="checkbox" id="chk-23" name="<?php echo $question['question_bank_id'].'[]'; ?>" value="<?php echo $i + 1;?>">
				    <label style="color: #373e4a; font-size: 15px;">
				    	<?php echo $options[$i];?>
				    </label>
			    </div>
			</div>
		<?php endfor; endif;?>
		<!-- true / false -->
		<?php if ($question['type'] == 'true_false'): ?>
			<div class="col-md-12" style="margin-bottom: 15px;">
				<div class="checkbox checkbox-replace color-green">
				    <input type="radio" id="chk-23" name="<?php echo $question['question_bank_id'].'[]'; ?>" value="true">
				    <label style="color: #373e4a; font-size: 15px;">
				    	<?php echo get_phrase('true');?>
				    </label>
			    </div>
			</div>
			<div class="col-md-12" style="margin-bottom: 15px;">
				<div class="checkbox checkbox-replace color-green">
				    <input type="radio" id="chk-23" name="<?php echo $question['question_bank_id'].'[]'; ?>" value="false">
				    <label style="color: #373e4a; font-size: 15px;">
				    	<?php echo get_phrase('false');?>
				    </label>
			    </div>
			</div>
		<?php endif; ?>
		<!-- fill in the blanks -->
		<?php if ($question['type'] == 'fill_in_the_blanks'): ?>
			<div class="col-md-3">
				<div class="form-group">
					<input type="text" name="<?php echo $question['question_bank_id'].'[]'; ?>" value="" class="form-control" placeholder="<?php echo get_phrase('answer');?>">
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php endforeach;?>
	<div class="row">
		<div class="col-md-3">
			<button type="submit" class="btn btn-success btn-block">
				<?php echo get_phrase('finish_exam');?>
			</button>
		</div>
	</div>
</form>

<!--Time-out modal alert here-->
			<div class="modal fade" id="modal_time_out" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="z-index: 999999; opacity: 1; position: sticky; -webkit-position: sticky; top: 5px;">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header" style="background-color: red;">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h2 style="color: #fff;" class="modal-title" id="myModalLabel" align="center">Time Out!!!</h2>
						</div>
						<div class="modal-body">
							<h4 style="text-align: justify; color: red;">You have exhausted the allocated time for this examination. Your answer sheet will be submitted to the examiner in the next <span style="font-size: 25px; color: #000; font-weight: 200px;" id="time_down">00</span> seconds.</h4>
							<center>
								<h4>Thank You For Participating. Good Bye!!</h4>
							</center>
					
						</div>
						<div class="modal-footer">
							<center><small>Check your portal for your results.</small></center>
							<hr>
							<button type="button" class="btn btn-danger" id="close_modal" data-dismiss="modal">Close</button>
						</div>
					</div>
					<!-- /.modal-content -->
				</div>
				<!-- /.modal-dialog -->
			</div>
			<!-- /.modal -->

<!--students taking exam online modal alert here-->
			<div class="modal fade" id="modal_students_online" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header" style="background-color: red;">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h3 style="color: #fff;" class="modal-title" id="myModalLabel" align="center">Total: <span><?php echo $session_count; ?></span></h3>
						</div>
						<div class="modal-body">
							<center>
								<h4>Students currently online taking this Examination</h4>
							</center>
							<hr><hr>
							<table class="table table-bordered table-responsive table-striped table-active">
								<thead>
									<tr>
										<th>Student ID#</th>
										<th>Student Name</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>1000</td>
										<td>Christian Agbotah</td>
									</tr>
								</tbody>
								
							</table>
						</div>
						<div class="modal-footer">
							<center><small></small></center>
							<hr>
							<button type="button" class="btn btn-danger" id="close_modal" data-dismiss="modal">Close</button>
						</div>
					</div>
					<!-- /.modal-content -->
				</div>
				<!-- /.modal-dialog -->
			</div>
			<!-- /.modal -->

<script type="text/javascript">
	
	// SET THE INITIAL VALUES TO TIMER PLACES
	var timer_starting_hour 	=	<?php echo $total_hour;?>;
	document.getElementById("hour_timer").innerHTML = timer_starting_hour;
	var timer_starting_minute 	=	<?php echo $total_minute;?>;
	document.getElementById("minute_timer").innerHTML = timer_starting_minute;
	var timer_starting_second 	=	<?php echo $total_second;?>;
	document.getElementById("second_timer").innerHTML = timer_starting_second;

	// INITIALIZE THE TIMER WITH SECOND DELAY
	var timer = timer_starting_second;
	var mytimer	=	setInterval(function () {run_timer()}, 1000);

	function run_timer() {

		if(timer_starting_hour == 0 && timer_starting_minute == 0 && timer_starting_second < 59) {
			$('#timer_value').css({'color': 'red'});
		}

		if(timer_starting_hour == 0 && timer_starting_minute < 5) {
			$('#timer_value').css({'color': 'yellow', 'background-color': 'red'});

		}

		if(timer_starting_hour > 1){
			$('#hour_name').text('Hours');
		}else{
			$('#hour_name').text('Hour');
		}
		if(timer_starting_minute > 1) {
			$('#minute_name').text('Minutes');
		}else{
			$('#minute_name').text('Minute');
		}
		if(timer_starting_second > 1){
			$('#second_name').text('Seconds');
		}else{
			$('#second_name').text('Second');
		}


		if (timer == 0 && timer_starting_minute == 0 && timer_starting_hour == 0) {
				$(function() {
			$('#modal_time_out').modal('show');	

			
		//time out
		var $time_count = 10;
		$('#time_down').text($time_count);

		setInterval(
			function() {
				time_down();
			}, 1000
			);

		function time_down() {
			if($time_count <= 10) {
				$time_count--;

				$('#time_down').text($time_count);

				
			}

			if($time_count == 0) {
				$('#close_modal')[0].click();
				$("#answer_script").submit();
			}
		}

		});

				
		}
		else {

			timer--;

		    if (timer < 0)
		    {
		    	timer = 59;
		    	timer_starting_minute--;
				if (timer_starting_minute >= 0) {
					document.getElementById("minute_timer").innerHTML = timer_starting_minute;
				}
		    }

		    if (timer_starting_minute < 0)
		    {
				timer_starting_minute = 59;
				document.getElementById("minute_timer").innerHTML = timer_starting_minute;
		    	timer_starting_hour--;
		    	document.getElementById("hour_timer").innerHTML = timer_starting_hour;
		    }

		    document.getElementById("second_timer").innerHTML = timer;
		}
	}

	//check if examiner has changed the time and reload the page
	//hide the alert
	$(document).ready(function() {
		$('#update_alert').css('display', 'none');
	});

	setInterval(
		function() {reload_page()},
		30000
		);

	function reload_page(){
		$(document).ready(function() {
		var exam_ends_timestamp = <?php echo $exam_ends_timestamp; ?>;
		var online_exam_id = <?php echo $online_exam_id; ?>;
		$.ajax({
			url: '<?php echo site_url('student/get_exam_ends_timestamp/');?>' + exam_ends_timestamp + '/' + online_exam_id,
			success: function(response) {
				if(response == 'Changed') {
					//if there is a change in time, display the alert part
					//$('#update_alert').css('display', 'block');
					
					//count from 15 to 0 second
					var update_counter = 15;
					$('#update_counter').text(update_counter);

					setInterval(function() {update_loader()}, 1000);
					function update_loader(){
						if(update_counter <= 15) {
							update_counter--;
							$('#update_counter').text(update_counter);
						}
						
						//reload the page after the count down is done
						if(update_counter == 0) {
							$('#update_alert').css('display', 'none');
							//location.reload('true');

						}
					}
					
				}else{
					$('#update_alert').css('display', 'none');
				}

				
			}
		});
	});
	}
	
</script>

<style type="text/css">
.blink_text {

        -webkit-animation-name: blinker;
 -webkit-animation-duration: 1s;
 -webkit-animation-timing-function: linear;
 -webkit-animation-iteration-count: infinite;

 -moz-animation-name: blinker;
 -moz-animation-duration: 1s;
 -moz-animation-timing-function: linear;
 -moz-animation-iteration-count: infinite;
 animation-name: blinker;
 animation-duration: 1s;
 animation-timing-function: linear;
    animation-iteration-count: infinite;
}

@-moz-keyframes blinker {
    0% { opacity: 1.0; }
    50% { opacity: 0.0; }
    100% { opacity: 1.0; }
}

@-webkit-keyframes blinker {
    0% { opacity: 1.0; }
    50% { opacity: 0.0; }
    100% { opacity: 1.0; }
}

@keyframes blinker {
    0% { opacity: 1.0; }
    50% { opacity: 0.0; }
    100% { opacity: 1.0; }
}
</style>
