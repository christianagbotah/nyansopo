<?php
    $online_exam_info = $this->db->get_where('online_exam', array('online_exam_id' => $param2))->row();
    $class = $this->db->get_where('class', array('class_id' => $online_exam_info->class_id))->row()->name;
    $class_numeric = $this->db->get_where('class', array('class_id' => $online_exam_info->class_id))->row()->name_numeric;
    $section = $this->db->get_where('section', array('section_id' => $online_exam_info->section_id))->row()->name;
    $subject = $this->db->get_where('subject', array('subject_id' => $online_exam_info->subject_id))->row()->name;
    $questions = $this->db->get_where('question_bank', array('online_exam_id' => $param2))->result_array();
    $answers = "answers";
    $running_year       =   $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
    $running_term       =   $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
    // calculate total marks
    $total_marks = 0;
    foreach ($questions as $question)
        $total_marks += $question['mark'];
?>
<htm>
    <head>
        
        <style type="text/css">
                #term{
                        background-color: black; 
                        padding: 5px 15px 5px 15px; 
                        border-radius: 9px;
                        -webkit-border-radius: 9px;
                        -moz-border-radius: 9px;
                        -o-border-radius: 9px;
                        font-size: 11px;
                        letter-spacing: 5px;
                        color: #ffffff;
                        font-weight: bold;

                        }

                        div #logo{
                            margin-top: -5px;
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
    </head>
    <body>
 <div style="text-align: center;">
                 <center>  
                    <h3 style="font-weight: bold; font-size: 16px; letter-spacing: 2px;"><?php echo get_settings('system_name');?></h3>
                </center>
                <div class="row" id="top_row">
                    <div class="col-lg-3 col-md-3 col-sm-3">
                       <img id="logo" src="<?php echo base_url(); ?>uploads/logo.png" style="max-height : 160px;"><br> 
                    </div>
                     
                        <br>
                    <div class="col-lg-6 col-md-6 col-sm-6">
                       
                        <span align="center" id="term"><?php echo  $online_exam_info->title;?></span><br><br>
                        <b><?php echo get_phrase('class');?>: <?php echo $class.' '.$class_numeric;?> | <?php echo get_phrase('section');?>: <?php echo $section;?></b><br>
                        <b><?php echo get_phrase('subject');?>: <?php echo $subject;?></b><br>
                        <b><?php echo get_phrase('total_marks');?>: <?php echo $total_marks.' | '.get_phrase('time');?>: <?php echo ($online_exam_info->duration / 60) . ' ' . get_phrase('minutes');?></b><br>

                        <b align='center' style="font-size: 11px;"><?php echo get_phrase('term').' '.$running_term;?> |</b> <b align='center' style="font-size: 11px;"><?php echo get_phrase('sessional_year:').' '. $running_year; ?></b> <br>
                    </div>
                    
                    <div class="col-lg-3 col-md-3 col-sm-3"></div>
                   
                </div> 
                 <hr>

                <p><b><?php echo get_phrase('instructions');?>:</b> <?php echo $online_exam_info->instruction;?></p>
            </div>
<div style="margin: 50px 20px 20px 20px;">
    <?php $count = 1; foreach ($questions as $row): ?>
    <div style="height: auto;">
        <div style="width: 95%; float: left;">
            <?php echo $count++;?>. <?php echo $row['type'] == 'fill_in_the_blanks' ? str_replace('^', '__________', $row['question_title']) : $row['question_title'];?>
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
