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

    $submitted_answer_script_details = $this->db->get_where('online_exam_result', array('online_exam_id' => $param2, 'student_id' => $this->session->userdata('login_user_id')))->row_array();
    $submitted_answer_script = json_decode($submitted_answer_script_details['answer_script'], true);
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
                        <b><?php echo $class.' '.$class_numeric;?> | <?php echo get_phrase('section');?>: <?php echo $section;?></b><br>
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
    <?php
        $count = 1; foreach ($submitted_answer_script as $row):
        $question_type = $this->crud_model->get_question_details_by_id($row['question_bank_id'], 'type');
        $question_title = $this->crud_model->get_question_details_by_id($row['question_bank_id'], 'question_title');
        $mark = $this->crud_model->get_question_details_by_id($row['question_bank_id'], 'mark');
        $submitted_answer = "";
    ?>
    <div style="height: auto;">
        <div style="width: 95%; float: left;">
             <br>
             <?php echo $count++;?>. <?php echo $question_type == 'fill_in_the_blanks' ? str_replace('-', '__________', $question_title) : $question_title;?>
             <br>
             <?php if ($question_type == 'multiple_choice'):
                 $options_json = $this->crud_model->get_question_details_by_id($row['question_bank_id'], 'options');
                 $number_of_options = $this->crud_model->get_question_details_by_id($row['question_bank_id'], 'number_of_options');
                 if ( $options_json != '' || $options_json != null)
                     $options = json_decode($options_json);
                 else
                     $options = array();
                 ?>
                 <ul>
                     <?php for ($i = 0; $i < $number_of_options; $i++): ?>
                         <li><?php echo $options[$i];?></li>
                     <?php endfor; ?>
                 </ul>
                 <?php
                 if ($row['submitted_answer'] != "" || $row['submitted_answer'] != null) {
                     $submitted_answer = json_decode($row['submitted_answer']);
                     $r = '';
                     for ($i = 0; $i < count($submitted_answer); $i++) {
                         $x = $submitted_answer[$i];
                         $r .= $options[$x-1].',';
                     }
                 } else {
                     $submitted_answer = array();
                     $r = get_phrase('not_answered.');
                 }
                  ?>
                  <i><strong>[<?php echo get_phrase('submitted_answers');?> - <?php echo rtrim(trim($r), ',');?>]</strong></i>
                  <br>
                 <?php
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

             <?php elseif($question_type == 'fill_in_the_blanks'):
                 if ($row['submitted_answer'] != "" || $row['submitted_answer'] != " ") {
                     $submitted_answer = implode(',', json_decode($row['submitted_answer']));
                 }
                 else{
                     $submitted_answer = get_phrase('not_answered.');
                 }
                 $suitable_words   = implode(',', json_decode($row['correct_answers']));
              ?>
              <br>
              <i><strong>[<?php echo get_phrase('submitted_answers');?> - <?php echo $submitted_answer;?>]</strong></i><br/>
              <i><strong>[<?php echo get_phrase('correct_answers');?> - <?php echo $suitable_words;?>]</strong></i>

          <?php elseif($question_type == 'true_false'):
              if ($row['submitted_answer'] != "") {
                  $submitted_answer = $row['submitted_answer'];
              }
              else{
                  $submitted_answer = get_phrase('not_answered');
              }
              ?>

              <i><strong>[<?php echo get_phrase('submitted_answer');?> - <?php echo $submitted_answer;?>]</strong></i><br>
              <i><strong>[<?php echo get_phrase('correct_answer');?> - <?php echo $row['correct_answers'];?>]</strong></i>
             <?php endif; ?>
        </div>
        <div style="width: 5%; float: right; text-align: right;">
            <b><?php echo $mark;?></b>
        </div>
    </div>
    <div style="height: 80px;"></div>
<?php endforeach;?>
</div>
</body>
</htm>
