<?php
include 'includes_top.php';
	$class_name		 	= 	$this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
	$exam_name  		= 	$this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name;
	$system_name        =	$this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
    $running_year       =   $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
?>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div id="print">

                <script src="<?php echo base_url('assets/js/jquery-1.11.0.min.js'); ?>"></script>
                <style type="text/css">
                    td {
                        padding: 5px;
                    }
                </style>

                <center>
                    <img src="<?php echo base_url(); ?>uploads/school_logo.png" style="max-height : 80px;"><br>
                    <h3 style="font-weight: 100;"><?php echo $system_name;?></h3>
                    <?php echo get_phrase('student_marksheet');?><br>
                    <?php echo $this->db->get_where('student' , array('student_id' => $student_id))->row()->name;?><br>
                    <?php echo get_phrase('class') . ' ' . $class_name;?><br>
                    <?php echo $exam_name;?>
                </center>
                <div class="table table-responsive">
                    <table class="taable table-bordered">
                       <thead>
                            <tr>
                                <th style="text-align: center; font-weight: bold;">S/N</th>
                                <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                                <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                                <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                                <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                                <th style="text-align: center; font-weight: bold;">GRADE</th>
                                <th style="text-align: center; font-weight: bold;">REMARK</th>
                                <th style="text-align: center; font-weight: bold;">POSITION</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                                $class_score_total = 0;
                                $exam_score_total =0; 
                                $total_marks = 0;
                                $total_grade_point = 0;
                                $subjects = $this->db->get_where('subject' , array(
                                    'class_id' => $class_id , 'year' => $running_year
                                ))->result_array();
                                $i = 1;
                                foreach ($subjects as $row3):
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td style="text-align: center;"><?php echo $row3['name'];?></td>
                                <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                                    'subject_id' => $row3['subject_id'],
                                                                        'exam_id' => $exam_id,
                                                                            'class_id' => $class_id,
                                                                                'student_id' => $student_id , 
                                                                                    'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
                                                                ));
                                        if($class_score_query->num_rows() > 0){
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['mark_obtained'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                                    'subject_id' => $row3['subject_id'],
                                                                        'exam_id' => $exam_id,
                                                                            'class_id' => $class_id,
                                                                                'student_id' => $student_id , 
                                                                                    'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
                                                                ));
                                        if($exam_score_query->num_rows() > 0){
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['mark_obtained'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                                    'subject_id' => $row3['subject_id'],
                                                                        'exam_id' => $exam_id,
                                                                            'class_id' => $class_id,
                                                                                'student_id' => $student_id , 
                                                                                    'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description
                                                                ));
                                        if($obtained_mark_query->num_rows() > 0){
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }
                                    ?>
                                </td>

                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0){
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               // $total_grade_point += $grade['grade_point'];
                                            }
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0){
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                               // $total_grade_point += $grade['grade_point'];
                                            }
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    Pending...
                                </td>
                            </tr>
                        <?php endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center;"><?php echo $class_score_total; ?></td>
                            <td style="text-align: center;"><?php echo $exam_score_total; ?></td>
                            <td style="text-align: center;"><?php echo $total_marks; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>
                </div>

            <br>


            </div>
         </div>
    </div>
</div>
    
<?php include 'includes_top.php'; ?>

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