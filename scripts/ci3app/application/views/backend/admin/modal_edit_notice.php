<?php 
$edit_data		=	$this->db->get_where('noticeboard' , array('notice_id' => $param2) )->result_array();
?>
<div class="tab-pane box active" id="edit" style="padding: 5px">
    <div class="box-content">
        <?php foreach($edit_data as $row):?>
        <?php echo form_open(site_url('admin/noticeboard/do_update/'.$row['notice_id']), array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
            <div class="padded">
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('title');?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="notice_title" value="<?php echo $row['notice_title'];?>" required/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('notice');?></label>
                    <div class="col-sm-5">
                        <div class="box closable-chat-box">
                            <div class="box-content padded">
                                    <div class="chat-message-box">
                                    <textarea name="notice" id="ttt" rows="5" class="form-control"
                                    	placeholder="<?php echo get_phrase('add_notice');?>" required><?php echo $row['notice'];?></textarea>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('date');?></label>
                    <div class="col-sm-5">
                        <input type="text" class="datepicker form-control" name="create_timestamp" value="<?php echo date('d , Y',$row['create_timestamp']);?>" required/>
                    </div>
                </div>

                <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo 'Send SMS'; ?></label>
                        <div class="col-sm-4">
                            <select class="form-control select2" name="check_sms" id="check_sms">
                                <option value="1"><?php echo get_phrase('yes'); ?></option>
                                <option value="2"><?php echo get_phrase('no'); ?></option>
                            </select>
                            <br>
                            <span class="badge badge-primary" id="sms_active">
                                <?php
                                if ($active_sms_service == 'hubtel')
                                    echo 'Hubtel-SMS ' . get_phrase('activated');
                                if ($active_sms_service == 'twilio')
                                    echo 'Twilio ' . get_phrase('activated');
                                if ($active_sms_service == '' || $active_sms_service == 'disabled')
                                    echo 'SMS Service Not Activated';
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="form-group" style="display: none" id="sms_target_holder">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('send_sMS_to...'); ?></label>
                        <div class="col-sm-4">
                            <select class="form-control select2" name="sms_target">
                                <option value="1"><?php echo get_phrase('all_(_parents,_teachers,_students)'); ?></option>
                                <option value="2"><?php echo get_phrase('parents_only'); ?></option>
                                <option value="3"><?php echo get_phrase('teachers_only'); ?></option>
                                <option value="4"><?php echo get_phrase('students_only'); ?></option>
                            </select>
                            
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('send_email-_alert_to_all'); ?></label>
                        <div class="col-sm-4">
                            <select class="form-control select2" name="check_email">
                                <option value="1"><?php echo get_phrase('yes'); ?></option>
                                <option value="2"><?php echo get_phrase('no'); ?></option>
                            </select>
                            
                        </div>
                    </div>

            </div>
            <div class="form-group">
              <div class="col-sm-offset-3 col-sm-5">
                  <button type="submit" class="btn btn-info"><?php echo get_phrase('edit_notice');?></button>
              </div>
            </div>
        </form>
        <?php endforeach;?>
    </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    show_sms_target();
    
    var active_sms_service = '<?php echo $active_sms_service; ?>';
    if(active_sms_service == '' || active_sms_service == 'disabled') {
      $('#sms_active').css('background-color', 'red');
      $('#check_sms').val(2);
      $('#check_sms').attr('disabled', 'disabled');
    }else{
      $('#sms_active').css('background-color', 'green');
    }
  });

  $('#check_sms').change(function(event) {
      show_sms_target();
  });

  function show_sms_target() {
    var check_sms = $('#check_sms').val();
    if(check_sms == 1) {
        $('#sms_target_holder').removeAttr('style');
    } else {
        $('#sms_target_holder').css('display', 'none');
    }
  }
</script>