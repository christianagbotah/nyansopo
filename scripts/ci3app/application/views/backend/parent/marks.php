<style type="text/css">
    .tabs-vertical {
        background: #444453 !important;
    }
    a {
        color: #a9aeca !important;
    }
    .tabs-vertical > li.active > a {
        color: #0f3679 !important;
    }
</style>
<hr />
    <div class="label label-primary pull-right" style="font-size: 14px; font-weight: 100;">
        <i class="entypo-user"></i> <?php echo $this->db->get_where('student' , array('student_id' => $student_id))->row()->name;?>
    </div>
<br><br>
<div class="row">
    <div class="col-md-12">
    
        <div class="tabs-vertical-env">
        
            <ul class="nav tabs-vertical">
                <?php 
                    $this->db->where('category_id', '2');
                    $exams = $this->db->get('exam')->result_array();
                    $tracker = 0;

                    foreach ($exams as $row2):

                        $this->db->where('exam_id' , $row2['exam_id']);
                        $this->db->where('student_id' , $student_id);
                        $get_marks = $this->db->get('mark');
                        $marks = $get_marks->result_array();

                       


                        $active = '';
                        if($tracker == 0) {
                            $active = 'class="active"';

                            echo "<script type='text/javascript'>
                                $(function(e) {
                                    let year = '".$row2['year']."';
                                    let term = ".$row2['term'].";
                                    let exam_id = ".$row2['exam_id'].";
                                    get_marksheet(year, term, exam_id);
                                    });
                            </script>";
                        }

                        $ex_counter = 0;

                         if(count($marks) > 0):
                ?>
                <li id="l_<?php echo $row2['exam_id'];?>" <?=$active; ?>>
                    <a href="#<?php echo $row2['exam_id'];?>" onclick="get_marksheet($('#year_<?php echo $row2['exam_id'];?>').text(), $('#term_<?php echo $row2['exam_id'];?>').text(), <?php echo $row2['exam_id']; ?>)" data-toggle="tab" title="<?= $row2['name']; ?>"><strong>
                        <?php echo $row2['name'];?></strong><br/> Year: <span id="year_<?php echo $row2['exam_id']; ?>"><?=$row2['year'];?></span> | Term: <span id="term_<?php echo $row2['exam_id']; ?>"><?=$row2['term'];?></span> <br> <small>( <?php echo $row2['date'];?> )</small>
                    </a>
                </li>
            <?php 
                $tracker++;
                $ex_counter++;
            endif;
            endforeach;

            if($ex_counter == 0) {
                echo '<li style="padding: 6px; text-align: center"><h4 style="color: #fff">Exam list is empty</h4></li>';
            }
            ?>

            </ul>
            
            <div class="tab-content" id="marksheet_holder">
            <!--Put tab-contents here-->

            <center>
                Loading result sheet please wait...<i class="fa fa-spinner fa-pulse"></i>
            </center>
            </div>
            
        </div>  
    
    </div>
</div>

<script type="text/javascript">
  function get_marksheet(year, term, exam_id) {

        //clear holder
        $('#marksheet_holder').html('<center>Loading result sheet please wait...<i class="fa fa-spinner fa-pulse"></i></center>');
        //scroll to top
        $('html, body').animate({
            scrollTop: $('#top').offset().top
        }, 1000);

        var student_id = '<?php echo $student_id; ?>';

        $.ajax({
            url: '<?php echo site_url('parents/get_marksheet/');?>' + year + '/' + term + '/' + student_id + '/' + exam_id,
            success: function(response) {
                
                $('#marksheet_holder').html(response);
                
            }
        });
    }
</script>