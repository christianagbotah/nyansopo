
            
            <?php 
                 $class_id3  = $this->db->get_where('enroll' , array('student_id' => $student_id3 , 'year' => $year, 'term' => $term))->row()->class_id;

                $section_id3  = $this->db->get_where('enroll' , array('class_id' => $class_id3, 'student_id' => $student_id3, 'year' => $year, 'term' => $term))->row()->section_id;


                $class_score_total = 0;
                $exam_score_total =0; 
                $total_marks = 0;

                //aggregation of core subjects 4
                $sum_core = 0;
                $raw_score = 0;
                $total_aggregate = 0;

                //aggregation of best 2
                $sum_best2 = 0;

                 $display = 'block';

                //conduct checks
                $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                     $ret_remarks = '';

                     $exams = $this->db->get('exam')->result_array();
    
                foreach ($exams as $exam):
                    
                    $this->db->where('exam_id' , $exam['exam_id']);
                    $this->db->where('student_id' , $student_id3);
                    $this->db->where('year' , $year);
                    $this->db->where('term' , $term);
                    $this->db->where('class_id' , $class_id3);
                    $this->db->where('section_id' , $section_id3);
                    $get_marks = $this->db->get('mark');
                    $marks = $get_marks->result_array();

                     //AGGREGATION OF MARKS
                       $this->db->where('exam_id', $exam['exam_id']);
                       $this->db->where('class_id', $class_id3);
                       $this->db->where('section_id', $section_id3);
                       $this->db->where('student_id', $student_id3);
                       $this->db->where('year', $year);
                       $this->db->where('term', $term);
                       $this->db->where('status', '1');
                       $this->db->limit(4);
                       //$this->db->order_by("mark_obtained", "desc")
                       $marks_a =  $this->db->get('mark');
                       $marks_agg = $marks_a->result_array();

                        foreach ($marks_agg as $rowc) {
                            $sum_core += $rowc['mark_obtained'];

                        }

                        //aggregation of best 2
                       $this->db->where('exam_id', $exam['exam_id']);
                       $this->db->where('class_id', $class_id3);
                       $this->db->where('section_id', $section_id3);
                       $this->db->where('student_id', $student_id3);
                       $this->db->where('year', $year);
                       $this->db->where('term', $term);
                       $this->db->where('status', '0');
                       $this->db->limit(2);
                       $this->db->order_by("mark_obtained", "desc");
                       
                       $marks_agg2 = $this->db->get('mark')->result_array();

                        foreach ($marks_agg2 as $rowb) {
                            $sum_best2 += $rowb['mark_obtained'];

                            //finding the aggregate for the best 2 subjects
                            $core_grade = $this->crud_model->get_raw_score_grade($rowb['mark_obtained']);
                            $total_aggregate += $core_grade['grade_point'];  
                        }

                            
?>
                <div class="tab-pane" id="<?php echo $exam['exam_id'];?>">
                    <table class="table table-bordered responsive">
                        <thead>
                            <tr>
                                <th width="15%" style="font-weight: bold;">CLASS</th>
                                <th style="font-weight: bold;">SUBJECT</th>
                                <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                                <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                                <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                                <th style="text-align: center; font-weight: bold;">GRADE</th>
                                <th style="text-align: center; font-weight: bold;">REMARK</th>
                                <th style="text-align: center; font-weight: bold;">POSITION</th>
                            </tr>
                            <tr>
                                <?php 
                                    if($get_marks->num_rows() < 1) {

                                        $total_aggregate = 'N/A';
                                        $sum_core = 0;
                                        $sum_best2 = 0;
                                        $display = 'none';
                                        ?>
                                        <td colspan="8" style="background-color: #f2f2f2;"><h4 style="text-align: center; color: red;">No Record Found!</h4></td>
                                        <?php
                                    }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($marks as $mark):?>
                            <tr>

                                <td>
                                    <?php echo $this->db->get_where('class' , array(
                                        'class_id' => $mark['class_id']
                                    ))->row()->name.' '.$this->db->get_where('class' , array(
                                        'class_id' => $mark['class_id']
                                    ))->row()->name_numeric;?>
                                </td>
                                <td>
                                    <?php $subj = $this->db->get_where('subject' , array(
                                        'subject_id' => $mark['subject_id'], 'year' => $year, 'term' => $term
                                    ))->row()->name;
                                    if($subj == 'Ict') {
                                        echo strtoupper($subj);
                                    }else {
                                        echo $subj;
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;"><?php echo $mark['class_score']; $class_score_total +=$mark['class_score']; $total_marks +=$mark['class_score']; ?></td>
                                <td style="text-align: center;"><?php echo $mark['exam_score']; $exam_score_total +=$mark['exam_score']; $total_marks +=$mark['exam_score'];?></td>
                                <td style="text-align: center;"><?php echo $mark['mark_obtained']; $total_marks;?></td>
                                <td style="text-align: center;"><?php 
                                    $grade = $this->crud_model->get_raw_score_grade($mark['mark_obtained']);
                                    echo $grade['grade_point'];
                                                
                                ?></td>
                                <?php
                                    //finding aggregate for core subjects
                                    $subj_st = $this->db->get_where('subject' , array(
                                    'subject_id' => $mark['subject_id'], 'year' => $year, 'term' => $term))->result_array();
                                    foreach($subj_st as $row3){
                                        if($row3['status'] == 1) {
                                                $core_grade = $this->crud_model->get_raw_score_grade($mark['mark_obtained']);
                                                $total_aggregate += $core_grade['grade_point'];
                                            }
                                    }
                                ?>
                                <td style="text-align: center;"><?php 
                                    $grade = $this->crud_model->get_raw_score_grade($mark['mark_obtained']);
                                    echo $grade['name'];
                                ?></td>
                                <td style="text-align: center;">
                                    <?php
                                 $this->crud_model->get_total_score($exam['exam_id'] , $mark['class_id'] , $mark['subject_id'], $student_id3, $year, $term);
                                 ?></td>
                            </tr>

                             <?php 


                        $conducts_data = $this->db->get_where('aggregation', array('student_id' => $student_id3, 'class_id' => $class_id3, 'exam_id' => $exam['exam_id'], 'term' => $term, 'year' => $year))->result_array();
                        
                        

                        foreach ($conducts_data as $conduct) {

                            $ret_remarks = $conduct['remarks'];
                            $exam_id2 = $conduct['exam_id'];
                            $student_id2 = $conduct['student_id'];
                            $class_id2 = $conduct['class_id'];
                            $term2 = $conduct['term'];
                            $year2 = $conduct['year'];
                            
                            for($i = 1; $i <= 15; $i++){
                                    
                                    if($conduct['c'.$i] == 'Exemplinary Behaviour'){
                                        $checked1 = 'checked';
                                    }elseif($conduct['c'.$i] == 'Well Mannered'){
                                        $checked2 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Honest') {
                                        $checked3 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Trustworthy') {
                                        $checked4 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Satisfactory Behaviour') {
                                        $checked5 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Very Neat') {
                                        $checked6 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Puntual') {
                                        $checked7 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Innovative') {
                                        $checked8 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Resourceful') {
                                        $checked9 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Friendly') {
                                        $checked10 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Industrious') {
                                        $checked11 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Obedient') {
                                        $checked12 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Reluctant') {
                                        $checked13 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Reserved') {
                                        $checked14 = 'checked';
                                    }elseif ($conduct['c'.$i] == 'Promising') {
                                        $checked15 = 'checked';
                                    }
                                }   
                           
                        }
                         endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL/AGGREGATE</th>
                            <td style="text-align: center; font-weight: bold;"><?php if($get_marks->num_rows() < 1) {echo 'N/A';}else{echo $class_score_total;}  ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php if($get_marks->num_rows() < 1) {echo 'N/A';}else{echo $exam_score_total;} ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php if($get_marks->num_rows() < 1) {echo 'N/A';}else{echo $total_marks;} ?></td>
                            <td style="text-align: center; background-color: #e3e2f1;"><h4 id="total_aggregate_<?php echo $exam['exam_id']; ?>"><?php echo $total_aggregate;  ?></h4></td>
                            <td colspan="3"></td>
                        </tr>
                        </tbody>
                    </table>


<div class="row">
    <h4>Raw Score: <u><?php $raw_score = $sum_core + $sum_best2; echo $raw_score; ?></u></h4>
    <h4>Total Aggregate: <u><?php echo $total_aggregate; ?></u></h4>
</div><hr>

        <!--Conducts-->
                        <div class="row" style="display: <?= $display; ?>">
                            <div class="col-lg-12 col-md-12">
                                <?php echo form_open(site_url('admin/conducts_update'), array('id' => 'c_form'));?>
                                    <div class="table table-responsive">
                                        <table class="table table-bordered table-striped" id="conducts" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1">
                                            <caption align="center">CONDUCTS OF <b><?php echo strtoupper($this->db->get_where('student' , array('student_id' => $student_id3))->row()->name);?></b></caption>
                                            <tbody>
                                                <tr>
                                                    <td><input type="checkbox" id="ex" class="form-control" name="conducts[]" value="Exemplinary Behaviour" <?php echo $checked1; ?> disabled></td>
                                                    <td><label for="ex" class="form-control-label">Exemplinary Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="wm" class="form-control" name="conducts[]" value="Well Mannered" <?php echo $checked2;?> disabled></td>
                                                    <td><label for="wm" class="form-control-label">Well Mannered</label></td>
                                                    
                                                    <td><input type="checkbox" id="hn" class="form-control" name="conducts[]" value="Honest" <?php echo $checked3;?> disabled></td>
                                                    <td><label for="hn" class="form-control-label">Honest</label></td>
                                                   
                                                    <td><input type="checkbox" id="tw" class="form-control" name="conducts[]" value="Trustworthy" <?php echo $checked4;?> disabled></td>
                                                     <td><label for="tw" class="form-control-label">Trustworthy</label></td>

                                                     <td><input type="checkbox" id="relu" class="form-control" name="conducts[]" value="Reluctant" <?php echo $checked13;?> disabled></td>
                                                    <td><label for="relu" class="form-control-label">Reluctant</label></td>
                                                </tr>
                                                <tr> 
                                                    <td><input type="checkbox" id="sb" class="form-control" name="conducts[]" value="Satisfactory Behaviour" <?php echo $checked5;?> disabled></td>
                                                    <td><label for="sb" class="form-control-label">Satisfactory Behaviour</label></td>
                                                    
                                                    <td><input type="checkbox" id="vn" class="form-control" name="conducts[]" value="Very Neat" <?php echo $checked6;?> disabled></td>
                                                    <td><label for="vn" class="form-control-label">Very Neat</label></td>
                                                    
                                                    <td><input type="checkbox" id="pu" class="form-control" name="conducts[]" value="Puntual" <?php echo $checked7;?> disabled></td>
                                                    <td><label for="pu" class="form-control-label">Puntual</label></td>

                                                    <td><input type="checkbox" id="inn" class="form-control" name="conducts[]" value="Innovative" <?php echo $checked8;?> disabled></td>
                                                    <td><label for="inn" class="form-control-label">Innovative</label></td>

                                                    <td><input type="checkbox" id="rese" class="form-control" name="conducts[]" value="Reserved" <?php echo $checked14;?> disabled></td>
                                                    <td><label for="rese" class="form-control-label">Reserved</label></td>
                                                    
                                                </tr>
                                                <tr>    
                                                    <td><input type="checkbox" id="rs" class="form-control" name="conducts[]" value="Resourceful" <?php echo $checked9;?> disabled></td>
                                                    <td><label for="rs" class="form-control-label">Resourceful</label></td>
                                                    
                                                    <td><input type="checkbox" id="fr" class="form-control" name="conducts[]" value="Friendly" <?php echo $checked10;?> disabled></td>
                                                    <td><label for="fr" class="form-control-label">Friendly</label></td>
                                                    
                                                    <td><input type="checkbox" id="ind" class="form-control" name="conducts[]" value="Industrious" <?php echo $checked11;?> disabled></td>
                                                    <td><label for="ind" class="form-control-label">Industrious</label></td>
                                                    
                                                    <td><input type="checkbox" id="ob" class="form-control" name="conducts[]" value="Obedient" <?php echo $checked12;?> disabled></td>
                                                    <td><label for="ob" class="form-control-label">Obedient</label></td>

                                                    <td><input type="checkbox" id="pro" class="form-control" name="conducts[]" value="Promising" <?php echo $checked15;?> disabled></td>
                                                    <td><label for="pro" class="form-control-label">Promising</label></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="form-group">
                                            <label class="form-control-label">Class Teacher's Remarks:</label>
                                            <textarea class="form-control" disabled name="teacher_remarks" rows="3"><?php echo $ret_remarks;?></textarea>
                                        </div>
                                    </div>
                                <?php echo form_close();?>
                            </div>
                        </div>

                    <a href="<?php echo site_url('parents/student_marksheet_print_view/'.$student_id3.'/'.$exam['exam_id']);?>"
                        class="btn btn-primary" target="_blank">
                        <?php echo get_phrase('print_marksheet');?>
                    </a>
                </div>

            <?php 

            //Now, clear all the variables before loading other info
             $class_score_total = 0;
                $exam_score_total =0; 
                $total_marks = 0;
                $sum_core = 0;
                $total_aggregate = 0;
                $sum_best2 = 0;

                $checked1 = ''; $checked2 = ''; $checked3 = ''; $checked4 = ''; $checked5 = ''; $checked6 = ''; $checked7 = ''; $checked8 = ''; $checked9 = ''; $checked10 = ''; $checked11 = ''; $checked12 = '';  $checked13 = ''; $checked14 = ''; $checked15 = '';
                    $ret_remarks = '';
        endforeach;

            ?>

<script type="text/javascript">

        var tab_id = '<?php echo $exam_id3; ?>';
        var year = '<?php echo $student_id3; ?>';

        if(tab_id != '' || tab_id != null) {
         $('#' + tab_id).removeClass('tab-pane');
         //alert(year);
        }else{
            toastr.error('No Record Found');
        }

       

</script>
<script type="text/javascript">
    $(document).ready(function() {
        var max_exam_id = '<?php echo $this->crud_model->get_max_exam_id($exam['exam_id']); ?>';
        for(i = 1; i <= max_exam_id; i++) {
            var total_aggregate = $('#total_aggregate_'+i).text();
            if(total_aggregate >= 15) {
                $('#total_aggregate_'+i).css('color', 'red');
            }else{
                $('#total_aggregate_'+i).css('color', 'green');
            }
        }
    });
</script>