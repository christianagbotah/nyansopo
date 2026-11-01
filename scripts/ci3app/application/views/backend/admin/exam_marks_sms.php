<hr />
<?php 
    //feedback
$display = 'none';
$alert_type = 'success';
if(isset($_GET['msg'])) {
    $msg_val = $_GET['msg'];
    if($msg_val == 1) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = get_phrase('message_not_sent:_parent\'s_phone_number_not_found');
    } else if($msg_val == 2) {
        $display = 'block';
        $alert_type = 'success';
        $feedback = get_phrase('messages_sent');
    } else if($msg_val == 3) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = get_phrase('your_sMS_menu_has_been_disabled._please_enable_it_on_the_sYSTEM_sETTINGS_menu_and_try_again!');
    } 

}


    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
?>
<div style="display: <?= $display; ?>; margin-top: 10px" class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
    </button>
   <strong> <?php echo $feedback; ?></strong>
</div>


<div class="row">
     <div class="col-md-6 pull-right" id="mgs" style="display: none; position: relative; padding-bottom: 10px;"></div>
</div>

<div class="row">

	<?php echo form_open(site_url('admin/exam_marks_sms/send_sms'));?>

		<div class="col-md-4 col-sm-4">
            <div class="form-group">
            <label class="control-label"><?php echo get_phrase('exam');?></label>
                <select name="exam_id" class="form-control select2">
            	<?php 
            		$exams = $this->db->get_where('exam' , array('category_id !=' => '1'))->result_array();
            		foreach ($exams as $row):
            	?>
                	<option value="<?php echo $row['exam_id'];?>"><?php echo $row['name'];?></option>
                <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="col-md-2 col-sm-2">
            <div class="form-group">
            <label class="control-label"><?php echo get_phrase('class');?></label>
                <select name="class_id" class="form-control select2" id="class_id">
                <?php 
                	$classes = $this->db->get('class')->result_array();
                	foreach ($classes as $row):
                        $class_id2 = $row['class_id'];

                        //add section A or B if the class has more than one section
                        $class_name = $this->db->get_where('class', array('class_id' => $row['class_id']))->row()->name;
                        $class_name_numeric = $this->db->get_where('class', array('class_id' => $row['class_id']))->row()->name_numeric;
                        $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                        $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                        $sec_name = '';
                        if($class_has_more_sections > 1) {
                            $sec_name = $section_name;
                        }

                        $class = '';
                        if($class_name == 'CRECHE') {
                            $class = $class_name;
                        } else {
                            $class = $class_name. ' '. $class_name_numeric.$sec_name;
                        }
                ?>
                	<option value="<?php echo $row['class_id'];?>"><?php echo $class;?></option>
                <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="col-md-3 col-sm-3">
            <div class="form-group">
            <label class="control-label"><?php echo get_phrase('receiver');?></label>
                <select name="receiver" class="form-control select2" id="receiver">
                	<option value="" disabled="true"><?php echo get_phrase('select_receiver');?></option>
                	<option value="student"><?php echo get_phrase('students');?></option>
                	<option value="parent"><?php echo get_phrase('parents');?></option>
                    <option value="single_parent"><?php echo get_phrase('one_parent_at_a_time');?></option>
                </select>
            </div>
        </div>

        <div class="col-md-3 col-sm-3" style="margin-top: 20px;" id="sub_1">
          <button type="submit" class="btn btn-primary"><?php echo get_phrase('send_marks');?> via SMS / Email</button>
        </div>
        
          
</div>
<div class="row">
    <div class="col-sm-3 col-md-3"></div>
    <div class="col-sm-3 col-md-3"></div>
    <div class="col-sm-3 col-md-3">
        <div class="form-group" style="display: none;" id="show_students_by_class_holder">
            <label class="control-label"><?php echo get_phrase('select_student');?></label>
            <select name="student_id" id="student_id" class="form-control select2" required>
                  '<option value="">Choose Student</option>
            </select>
        </div>
    </div>

<div class="col-md-3 col-sm-3" style="margin-top: 20px; display: none;" id="sub_2">
  <button type="submit" class="btn btn-primary"><?php echo get_phrase('send_marks');?> via SMS / Email</button>
</div>
</div>
 <?php echo form_close();?>


<script type="text/javascript">

     $(function() {
        get_students_for_exam();
        show_students();
    });

    

    $( "form" ).submit(function( event ) {
        var receiver = $('#receiver').val();
        var sms_active = '<?php echo $active_sms_service; ?>';

        if(sms_active == 'disabled'){
            event.preventDefault();
            $('#mgs').fadeIn('600');
            $('#mgs').css('color', 'red');
            $('#mgs').text('Your SMS seems to have been disabled. Please enable it at the System Settings menu and try again.');
            toastr.error('<?php echo get_phrase('SMS_NOT_ACTIVE._ENABLE_IT_AT_THE_SYSTEM_SETTINGS_MENU_AND_TRY_AGAIN!');?>');
        } else if(receiver == ''){
            event.preventDefault();
            $('#mgs').fadeIn('600');
            $('#mgs').css('color', 'red');
            $('#mgs').text('Please select the target receiver before you proceed.');
            toastr.error('<?php echo get_phrase('please_select_receiver');?>');
            
        } else {
            return true;
        }  
      
    });

   



    $('#class_id').change(function(event) {
        get_students_for_exam();
    });

    $('#receiver').change(function(event) {
       setTimeout(() => {
         show_students();
       }, 500);
    });

    function get_students_for_exam() {
        var class_id = $('#class_id').val();

        $.ajax({
            url: '<?php echo site_url('admin/get_students_for_exam/') ?>' + class_id,
            success: function(response) {
                $('#student_id').html(response);
            }
        });
    }

    function show_students() {
         var receiver_ = $('#receiver').val();
        if(receiver_ == 'single_parent') {
            $('#show_students_by_class_holder').removeAttr('style');
            $('#sub_1').css('display', 'none');
            $('#sub_2').removeAttr('style');
            $('#sub_2').css('margin-top', '20px');
        } else {
            $('#show_students_by_class_holder').css('display', 'none');
            $('#sub_1').css('display', 'block');
            $('#sub_2').css('display', 'none');
        }
    }
</script>