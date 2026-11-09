<?php 
    if(isset($_GET['pi'])) {
        $pi = $_GET['pi'];
    } else if(isset($_GET['ti'])) {
        $ti = $_GET['ti'];
    } else if(isset($_GET['si'])) {
        $si = $_GET['si'];
    }

    if(isset($_GET['success']) && $_GET['success'] == 1) {
?>
    <div class="alert alert-success alert-dismissable" role="alert">
        <button type="button" data-dismiss="alert" class="close" aria-label="close">&times;</button>
        <strong style="text-align: center;">SMS Sent Successfully!</strong>
    </div>
<?php 
    } else if(isset($_GET['success']) && $_GET['success'] == 2) {
        ?>
        <div class="alert alert-danger alert-dismissable" role="alert">
        <button type="button" data-dismiss="alert" class="close" aria-label="close">&times;</button>
        <strong style="text-align: center;">Ooops! Something happened, so SMS was not sent. It seems you do not have enough credit in your SMS Wallet or your Sender ID infos are incorrect!</strong>
    </div>
        <?php

    }
?>

<style type="text/css">
    input[type="tel"] {
        border-color: #0c0108d4 !important;
    }
</style>
<div class="mail-header" style="padding-bottom: 27px ;">
    <!-- title -->
    <h3 class="mail-title">
        <?php echo get_phrase('write_new_sMS_message'); ?>
    </h3>
</div>

<div class="mail-compose">

    <?php echo form_open(site_url('admin/message/sms_send/'), array('class' => 'form', 'enctype' => 'multipart/form-data', 'id' => 'sms_send_form')); ?>


    <div class="form-group">
        <label for="subject"><?php echo get_phrase('recipient'); ?>:</label>
        <br><br>
        <select class="form-control select2" id="bulk" name="bulk" onchange="send_to()" required="required">
            <option value="1">Send Individually</option>
            <option value="2">All Active Teachers</option>
            <option value="3">All Active Students</option>
            <option value="4">All Active Parents</option>
            <option value="5">Enter Phone Number</option>
        </select><br>

        <div id="indiv" style="display: none;">
           <select class="form-control select2"  name="reciever[]" multiple="multiple" required>

                <option value=""><?php echo get_phrase('select_a_user'); ?></option>

                            <option value=""> </option>

                    <!-- ACTIVE STUDENTS -->
                   <optgroup label="<?php echo get_phrase('active_student'); ?>">
                    <?php
                    $this->db->where('term', $running_term);
                    $this->db->or_where('sem', $running_sem);
                    $students = $this->db->get_where('enroll', array('year' => $running_year, 'mute' => '0'))->result_array();
                    foreach ($students as $row):

                        $student_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->student_id;
                        $student_name = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name;
                        ?>

                        <option value="student-<?php echo $student_id; ?>" <?php if($si == $student_id) echo 'selected'; ?>>
                            - <?php echo $student_name; ?></option>

                    <?php endforeach; ?>

                    </optgroup> 

                <!-- ACTIVE TEACHERS -->
                <optgroup label="<?php echo get_phrase('active_teachers'); ?>">
                    <?php
                    $teachers = $this->db->get_where('teacher', array('block_limit' => '0'))->result_array();
                    foreach ($teachers as $row):
                        ?>

                        <option value="teacher-<?php echo $row['teacher_id']; ?>" <?php if($ti == $row['teacher_id']) echo 'selected'; ?>>
                            - <?php echo $row['name']; ?></option>

                    <?php endforeach; ?>
                </optgroup>

                <!-- ACTIVE PARENTS -->
                <optgroup label="<?php echo get_phrase('active_parent'); ?>">
                    <?php
                    $this->db->select('student_id');
                    $this->db->from('enroll');
                    $this->db->where('mute', '0');
                    $this->db->where('year', $running_year);
                    $this->db->where('term', $running_term);
                    $this->db->or_where('sem', $running_sem);
                    $st_ids =  $this->db->get()->result_array();

                    $st_ids_array = array();
                    $i = 0;
                    foreach($st_ids as $row) {
                        $st_ids_array[$i] = $row['student_id'];
                        $i++;
                    }

                    $this->db->select('parent_id');
                    $this->db->from('student');
                    $this->db->where_in('student_id', $st_ids_array);
                    $pt_ids = $this->db->get()->result_array();

                    $pt_ids_array = array();
                    $j = 0;
                    foreach($pt_ids as $row2) {
                        $pt_ids_array[$j] = $row2['parent_id'];
                        $j++;
                    }
                    $this->db->where_in('parent_id', $pt_ids_array);
                    $parents = $this->db->get('parent')->result_array();
                    foreach ($parents as $row):
                        ?>

                        <option value="parent-<?php echo $row['parent_id']; ?>" <?php if($pi == $row['parent_id']) echo 'selected'; ?>>
                            - <?php echo $row['name']; ?></option>

                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        <div class="form-group" id="phone" style="display: none; font-size: 20px">
            <span style="color: red">Separate each number with a comma (,)</span>
            <input type="tel" name="phone[]" class="form-control" value="" data-role="tagsinput" placeholder="Enter Phone Number...">
        </div>
    </div>


    <div class="compose-message-editor">
        <textarea row="2" class="form-control wysihtml5" data-stylesheet-url="<?php echo base_url('assets/css/wysihtml5-color.css');?>"
            name="message" placeholder="<?php echo get_phrase('write_your_message'); ?>"
            id="sample_wysiwyg" required></textarea>
    </div>
    <br>

    <hr>

    <button type="submit" class="btn btn-success btn-block pull-right">
        <?php echo get_phrase('send_sMS'); ?>
    </button>
</form>

</div>

<script type="text/javascript">

    $(function() {
        //call the send_to() as soon as the page is loaded
        send_to();
        $('#modal_alert .modal-content').css({
            backgroundColor: '#ffffff !important',
        })
    });

    

    function send_to() {
        //values from bulk select element
        let select_val = $('#bulk').val();

        //check if any of the $_GET values is set
        let parent_id = '<?php echo $pi; ?>';
        let student_id = '<?php echo $si; ?>';
        let teacher_id = '<?php echo $ti; ?>';

    
        if(select_val == 1) {
            $('#indiv').css('display', 'block');
            $('#indiv select').attr('required', 'required');

            $('#phone').css('display', 'none');
            //$('#phone input').removeAttr('required');

        } else if(select_val == 2) {
            $('#indiv').css('display', 'none');
            $('#indiv select').removeAttr('required');

            $('#phone').css('display', 'none');
            //$('#phone input').removeAttr('required');

        } else if(select_val == 3) {
            $('#indiv').css('display', 'none');
            $('#indiv select').removeAttr('required');

            $('#phone').css('display', 'none');
            //$('#phone input').removeAttr('required');

        } else if(select_val == 4) {
            $('#indiv').css('display', 'none');
            $('#indiv select').removeAttr('required');

            $('#phone').css('display', 'none');
            //$('#phone input').removeAttr('required');

        } else if(select_val == 5) {
            $('#indiv').css('display', 'none');
            $('#indiv select').removeAttr('required');

            $('#phone').css('display', 'block');
            //$('#phone input').attr('autofocus', 'autofocus');
            //$('#phone input').attr('required', 'required');
        }
        

        
    }


     //ajax
$('#sms_send_form').submit(function(event) {
    /* Act on the event */

    event.preventDefault();

    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

    showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Sending SMS...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
        //$('.modal-dialog').css('marginTop', '200px');
    $.ajax({
      url: '<?php echo site_url('admin/message/sms_send/sms_submitted'); ?>',
      type: 'POST',
      dataType: 'json',
      data: new FormData(this),
      cache: false,
      contentType: false,
      processData: false
  })
  .done(function(data) {   
      let response = data.send_sms;

    if(response == 'failed') {
        //error
        showAjaxModal_alert('<span style="font-size: 14px">SMS NOT SENT!<br><br> PLEASE MAKE SURE YOU HAVE ENOUGHT BUNDLE IN YOUR HUBTEL WALLET OR CHECK IF YOUR SMS API ID AND KEYS ARE ENTERED CORRECTLY. <br>LOGIN TO YOUR HUBTEL ACCOUNT <a href="https://bo.hubtel.com/login?handler=Google" target="_blank" class="btn btn-success btn-lg">HERE</a> AND CHECK.</span>', 'Error');

        $('.close').click(function(e) {
            navigation('<?php echo site_url('admin/message/sms_send/'); ?>');
         });

    } else if(response.substring(0, 6) == 'FAILED') {
        showAjaxModal_alert('<span style="font-size: 14px">' + response + '</span>', 'Error');

        $('.close').click(function(e) {
            navigation('<?php echo site_url('admin/message/sms_send/'); ?>');
         });
    } else {
        
        //success
        showAjaxModal_alert(response, 'Success');
        

         $('.close').click(function(e) {
            navigation('<?php echo site_url('admin/message/sms_send/'); ?>');
         });
        
    }
  })
  .fail(function(err) {
    showAjaxModal_alert(err.responseText, 'Error');
    
  });
});
</script>