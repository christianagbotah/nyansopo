<?php
	$class_name		 	= 	$this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
	$class_name_numeric     = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;
	$exam_name  		= 	$this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
	$system_name        =	$this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
	$running_year       =	$year;
	
	if($class_name == 'JHSS') {
		$running_sem       =	$term;

} else {
	$running_term       =	$term;

}
?>

<!doctype html>
<html>
    <head>

        <link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive_table.css');?>">

    </head>
    <body style="background-color: #fcfcfd !important;">  

        <div class="container">
            <div class="row" style="padding-top: 15px;">
                <a onClick="PrintElem('#print')" class="btn btn-default btn-icon icon-left hidden-print pull-right">
                       Print Report Sheet
                        <i class="glyphicon glyphicon-print"></i>
                </a>
            </div>
			<div id="print">
				<script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js'); ?>"></script>
				<style type="text/css">
					td {
						padding: 5px;
					}

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


                            @media Print{
                                div #logo{
                                    position: absolute;
                                    top: 80px;
                                }

                                #top_row{
                                    margin-top: -40px;
                                }

                            }
            	</style>

			                <center>  
			                    <h3 style="font-weight: bold; font-size: 25px; letter-spacing: 2px;"><?php echo $system_name;?></h3>
			                </center>
			                <div class="row" id="top_row">
			                    <div class="col-lg-3 col-md-3 col-sm-3">
			                       <img id="logo" src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 120px;"><br> 
			                    </div>
			                     <center>
			                        <br>
			                    <div class="col-lg-6 col-md-6 col-sm-6">
			                       
			                        <span align="center" id="term"><?php echo get_phrase('exam_tabulation_sheet');?></span>

			                        <?php
				                            //add section A or B if the class has more than one section
				                            $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
				                            $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
				                            $sec_name = '';
				                            if($class_has_more_sections > 1) {
				                                $sec_name = $section_name;
				                            }
				                        ?>
			                        <h3 align="center"><?php echo get_phrase('class:') . ' ' . $class_name.' '.$class_name_numeric.$sec_name;?><br></h3>
			                        <b align='center' style="font-size: 25px;"><?php echo $class_name == 'JHSS' ? get_phrase('semester').' '.$running_sem : get_phrase('term').' '.$running_term;?> |</b> <b align='center' style="font-size: 25px;"><?php echo get_phrase('academic_year:').' '. $running_year; ?></b> <br>
			                    </div>
			                    </center>
			                    <div class="col-lg-3 col-md-3 col-sm-3"></div>
			                    <hr>
			                </div> 



				<?php
					if($class_name == 'JHSS') {
						?>
							<table style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
					<thead>
						<tr>
						<td style="text-align: center;">
							<?php echo get_phrase('students');?> <i class="entypo-down-thin"></i> | <?php echo get_phrase('subjects');?> <i class="entypo-right-thin"></i>
						</td>
						<?php
							$subjects = $this->db->get_where('subject' , array('class_id' => $class_id , 'year' => $running_year, 'sem' => $running_sem))->result_array();
							foreach($subjects as $row):
							?>
								<td style="text-align: center;">
									<?php if(strlen($row['name']) <= 4) {
			                                    echo strtoupper($row['name']);
			                                }else{ 
			                                    echo $row['name'];
			                                };?></td>
							<?php endforeach;?>
						<td style="text-align: center;"><?php echo get_phrase('total');?></td>
						<td style="text-align: center;"><?php echo get_phrase('postion_in_class');?></td>
						</tr>
					</thead>
					<tbody>
					<?php

						$students = $this->db->get_where('enroll' , array('class_id' => $class_id , 'year' => $running_year, 'sem' => $running_sem))->result_array();
							foreach($students as $row):
					?>
						<tr>
							<td>
								<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
							</td>
						<?php
							$total_marks = 0;
							$total_grade_point = 0;
							foreach($subjects as $row2):
						?>
							<td style="text-align: center;">
								<?php
									$obtained_mark_query = 	$this->db->get_where('mark' , array(
															'class_id' => $class_id ,
																'exam_id' => $exam_id ,
																	'subject_id' => $row2['subject_id'] ,
																		'student_id' => $row['student_id'],
																			'year' => $running_year,
																				'sem' => $running_sem
														));
									if ( $obtained_mark_query->num_rows() > 0) {
										$obtained_marks = round($obtained_mark_query->row()->mark_obtained, 2);
										echo $obtained_marks;
										if ($obtained_marks >= 0 && $obtained_marks != '') {
											$grade = $this->crud_model->get_grade($obtained_marks);
											$total_grade_point += $grade['grade_point'];
										}
										$total_marks += $obtained_marks;
									}


									?>
								</td>
							<?php endforeach;?>
							<td style="text-align: center;"><b><?php echo $total_marks;?></b></td>
							<td style="text-align: center;">
								<?php
									
           							 $section_id = $this->db->get_where('section' , array('class_id' => $class_id))->row()->section_id;
          			 				 $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $row['student_id'], $running_year, $running_sem);
								?>
							</td>
							</tr>

						<?php endforeach;?>

						</tbody>
					</table>
						<?php //jhs ends
					} else {
						?>
							<table style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
					<thead>
						<tr>
						<td style="text-align: center;">
							<?php echo get_phrase('students');?> <i class="entypo-down-thin"></i> | <?php echo get_phrase('subjects');?> <i class="entypo-right-thin"></i>
						</td>
						<?php
							$subjects = $this->db->get_where('subject' , array('class_id' => $class_id , 'year' => $running_year, 'term' => $running_term))->result_array();
							foreach($subjects as $row):
							?>
								<td style="text-align: center;">
									<?php if(strlen($row['name']) <= 4) {
			                                    echo strtoupper($row['name']);
			                                }else{ 
			                                    echo $row['name'];
			                                };?></td>
							<?php endforeach;?>
						<td style="text-align: center;"><?php echo get_phrase('total');?></td>
						<td style="text-align: center;"><?php echo get_phrase('postion_in_class');?></td>
						</tr>
					</thead>
					<tbody>
					<?php

						$students = $this->db->get_where('enroll' , array('class_id' => $class_id , 'year' => $running_year, 'mute' => '0', 'term' => $running_term))->result_array();
							foreach($students as $row):
					?>
						<tr>
							<td>
								<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
							</td>
						<?php
							$total_marks = 0;
							$total_grade_point = 0;
							foreach($subjects as $row2):
						?>
							<td style="text-align: center;">
								<?php
									$obtained_mark_query = 	$this->db->get_where('mark' , array(
															'class_id' => $class_id ,
																'exam_id' => $exam_id ,
																	'subject_id' => $row2['subject_id'] ,
																		'student_id' => $row['student_id'],
																			'year' => $running_year,
																				'term' => $running_term
														));
									if ( $obtained_mark_query->num_rows() > 0) {
										$obtained_marks = round($obtained_mark_query->row()->mark_obtained, 2);
										echo $obtained_marks;
										if ($obtained_marks >= 0 && $obtained_marks != '') {
											$grade = $this->crud_model->get_grade($obtained_marks);
											$total_grade_point += $grade['grade_point'];
										}
										$total_marks += $obtained_marks;
									}


									?>
								</td>
							<?php endforeach;?>
							<td style="text-align: center;"><b><?php echo $total_marks;?></b></td>
							<td style="text-align: center;">
								<?php
									
           							 $section_id = $this->db->get_where('section' , array('class_id' => $class_id))->row()->section_id;
          			 				 $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $row['student_id'], $running_year, $running_term);
								?>
							</td>
							</tr>

						<?php endforeach;?>

						</tbody>
					</table>
						<?php
					}
				?>
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
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-size: 13px" >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }
</script>