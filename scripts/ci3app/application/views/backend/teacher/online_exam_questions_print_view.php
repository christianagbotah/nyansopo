<!DOCTYPE html>
<html>
<head>
	<title><?php echo $page_title;?></title>

	<link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>

</head>
<body style="background-color: #fcfcfd !important;">
	<?php
		$online_exam_info = $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id))->row();
		$class = $this->db->get_where('class', array('class_id' => $online_exam_info->class_id))->row()->name;
		$class_numeric = $this->db->get_where('class', array('class_id' => $online_exam_info->class_id))->row()->name_numeric;
		$section = $this->db->get_where('section', array('section_id' => $online_exam_info->section_id))->row()->name;
		$subject = $this->db->get_where('subject', array('subject_id' => $online_exam_info->subject_id))->row()->name;
		$questions = $this->db->get_where('question_bank', array('online_exam_id' => $online_exam_id))->result_array();
		$running_year       =	$this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
		$running_term       =	$this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
		// calculate total marks
		$total_marks = 0;
		foreach ($questions as $question)
			$total_marks += $question['mark'];
	?>
	<div class="container">
            <div class="row" style="padding-top: 15px;">
                <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
                       Print Report Sheet
                        <i class="glyphicon glyphicon-print"></i>
                </a>
            </div>
        <div id="print">  
        	<script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js');?>"></script>
             <style type="text/css">
		    	#term{
			            background-color: black; 
			            padding: 5px 15px 5px 15px; 
			            border-radius: 9px;
			            -webkit-border-radius: 9px;
			            -moz-border-radius: 9px;
			            -o-border-radius: 9px;
			            font-size: 24px;
			            letter-spacing: 5px;
			            color: #ffffff;
			            font-weight: bold;

			            }

			            div #logo{
			                margin-top: -60px;
			            }

			            @media Print{
			                div #logo{
			                    position: absolute;
			                    top: 41px;
			                    left: -40px;
			                }

			                #top_row{
			                    margin-top: -40px;
			                }

			            }
    	
	   		 </style>
			<div style="text-align: center;">
				 <center>  
	                <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px;"><?php echo get_settings('system_name');?></h3>
	            </center>
	            <div class="row" id="top_row">
	                <div class="col-lg-3 col-md-3 col-sm-3">
	                   <img id="logo" src="<?php echo base_url(); ?>uploads/logo.png" style="max-height : 250px;"><br> 
	                </div>
	                 
	                    <br>
	                <div class="col-lg-6 col-md-6 col-sm-6">
	                   
	                    <span align="center" id="term"><?php echo  $online_exam_info->title;?></span><br><br>
	                    <b><?php echo get_phrase('class');?>: <?php echo $class.' '.$class_numeric;?> | <?php echo get_phrase('section');?>: <?php echo $section;?></b><br>
	                    <b><?php echo get_phrase('subject');?>: <?php echo $subject;?></b><br>
	                    <b><?php echo get_phrase('total_marks');?>: <?php echo $total_marks.' | '.get_phrase('time');?>: <?php echo ($online_exam_info->duration / 60) . ' ' . get_phrase('minutes');?></b><br>

	                    <b align='center' style="font-size: 16px;"><?php echo get_phrase('term').' '.$running_term;?> |</b> <b align='center' style="font-size: 15px;"><?php echo get_phrase('sessional_year:').' '. $running_year; ?></b> <br>
	                </div>
	                
	                <div class="col-lg-3 col-md-3 col-sm-3"></div>
	               
	            </div> 
	             <hr>

				<p><b><?php echo get_phrase('instructions');?>:</b> <?php echo $online_exam_info->instruction;?></p>
			</div>
			<hr>
			<div style="margin: 50px 20px 20px 20px;">
				<?php $count = 1; foreach ($questions as $row): ?>
				<div style="height: auto;">
					<div style="width: 95%; float: left;">
					    <?php echo $count++;?>. <?php echo $row['type'] == 'fill_in_the_blanks' ? str_replace('-', '__________', $row['question_title']) : $row['question_title'];?>
					    <p>
					    	<?php if ($row['type'] == 'true_false') { ?>
		                        <ul>
		                        	<li><?php echo get_phrase('true');?></li>
		                        	<li><?php echo get_phrase('false');?></li>
		                        </ul>
		                        <?php if ($answers == 'answers'):?>
									<i><strong>[<?php echo get_phrase('correct_answer');?> - <?php echo $row['correct_answers'];?>]</strong></i>
		                        <?php endif;?>
					    	<?php } else if ($row['type'] == 'fill_in_the_blanks') { ?>
								<b><?php echo get_phrase('answer');?>: </b>
								<?php if ($answers == 'answers'):
									$suitable_words = implode(',', json_decode($row['correct_answers']));
								?>
									<br><br>
									<i><strong>[<?php echo get_phrase('correct_answers');?> - <?php echo $suitable_words;?>]</strong></i>
		                        <?php endif;?>
					    	<?php } else {
					    		if ($row['options'] != '' || $row['options'] != null)
					    			$options = json_decode($row['options']);
					    		else
					    			$options = array();
					    		?>
								<ul>
									<?php for ($i = 0; $i < $row['number_of_options']; $i++): ?>
										<li><?php echo $options[$i];?></li>
									<?php endfor; ?>
								</ul>
								<?php if ($answers == 'answers'):
									if ($row['correct_answers'] != "" || $row['correct_answers'] != null) {
		        						$correct_options = json_decode($row['correct_answers']);
		        						$r = '';
		        						for ($i = 0; $i < count($correct_options); $i++) {
		    								$x = $correct_options[$i];
		    								$r .= $options[$x-1].',';
		    							}
		    						} else {
		        						$correct_options = array();
		        						$r = get_phrase('none_of_them.');
		    						}
								?>
									<i><strong>[<?php echo get_phrase('correct_answer');?> - <?php echo rtrim(trim($r), ',');?>]</strong></i>
		                        <?php endif;?>
					    	<?php } ?>
					    </p>
				    </div>
				    <div style="width: 5%; float: right; text-align: right;">
					    <b><?php echo $row['mark'];?></b>
				    </div>
				</div>
				<div style="height: 80px;"></div>
			<?php endforeach;?>
			</div>
		</div>
	</div>

	<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>
<script type="text/javascript">

	jQuery(document).ready(function($)
	{
		var elem = $('#print');
		PrintElem(elem);
		Popup(data);

	});

    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data) 
    {
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title></title>');
        //mywindow.document.write('<link rel="stylesheet" href="assets/css/print.css" type="text/css" />');
        mywindow.document.write('</head><body >');
        //mywindow.document.write('<style>.print{border : 1px;}</style>');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10

        mywindow.print();
        mywindow.close();

        return true;
    }
</script>
