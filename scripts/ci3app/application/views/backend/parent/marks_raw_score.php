<?php 
 $child_of_parent = $this->db->get_where('enroll' , array(
        'student_id' => $student_id , 'year' => $running_year, 'term' => $running_term
    ));
    if($child_of_parent->num_rows() < 1){
        ?>
        <hr>
        <h4 style="text-align: center; color: red;">No record found!</h4>
        <hr>
        <?php
    }
    $child_of_parent_array = $child_of_parent->result_array();
    foreach ($child_of_parent_array as $row):
?>
<hr />
    <div class="label label-primary pull-right" style="font-size: 14px; font-weight: 100;">
        <i class="entypo-user"></i> <?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
    </div>
<br><br>
<div class="row">
    <div class="col-md-12">
    
        <div class="tabs-vertical-env">
        
            <ul class="nav tabs-vertical">
                <?php 
                    $exams = $this->db->get('exam')->result_array();

                    foreach ($exams as $row2):
                ?>
                <li class="" >
                    <a href="#<?php echo $row2['exam_id'];?>" onclick="get_marksheet_raw_score($('#year_<?php echo $row2['exam_id'];?>').text(), $('#term_<?php echo $row2['exam_id'];?>').text(), <?php echo $row2['exam_id']; ?>)" data-toggle="tab" title="<?= $row2['name']; ?>"><strong>
                        <?php echo $row2['name'];?></strong><br/> Year: <span id="year_<?php echo $row2['exam_id']; ?>"><?=$row2['year'];?></span> | Term: <span id="term_<?php echo $row2['exam_id']; ?>"><?=$row2['term'];?></span> <br> <small>( <?php echo $row2['date'];?> )</small>
                    </a>
                </li>
            <?php endforeach;?>
            </ul>
            
            <div class="tab-content" id="marksheet_holder">
            <!--Put tab-contents here-->


            </div>
            
        </div>  
    
    </div>
</div>
<?php endforeach;?>



<script type="text/javascript">
  function get_marksheet_raw_score(year, term, exam_id) {
        var student_id = '<?php echo $student_id; ?>';

        $.ajax({
            url: '<?php echo site_url('parents/get_marksheet_raw_score/');?>' + year + '/' + term + '/' + student_id + '/' + exam_id,
            success: function(response) {
                
                $('#marksheet_holder').html(response);
                
            }
        });
    }
</script>