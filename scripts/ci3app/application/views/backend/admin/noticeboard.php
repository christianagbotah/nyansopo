<hr />
<?php 
    if(isset($_GET['suc']) && $_GET['suc'] == 1) { ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="close"><span aria-hiddent="true">&times;</span></button>
            <h3 align="center">Notice Was Created Successfully and Messages Were Sent</h3>
        </div>
        <?php
    } else if(isset($_GET['up']) && $_GET['up'] == 1) { ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="close"><span aria-hiddent="true">&times;</span></button>
            <h3 align="center">Notice Was Updated Successfully and Messages Were Sent</h3>
        </div>
        <?php
    } 
?>
<hr>
<div class="row">
    <div class="col-md-12">

        <!------CONTROL TABS START------>
        <ul class="nav nav-tabs bordered">
            <li class="active">
                <a href="#list" data-toggle="tab"><i class="entypo-menu"></i>
                    <?php echo get_phrase('noticeboard_list'); ?>
                </a></li>
            <li>
                <a href="#add" data-toggle="tab"><i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('add_noticeboard'); ?>
                </a></li>
        </ul>
        <!------CONTROL TABS END------>


        <div class="tab-content">
            <br>
            <!----TABLE LISTING STARTS-->
            <div class="tab-pane box active" id="list">
                <div class="row">

                    <div class="col-md-12">

                        <ul class="nav nav-tabs bordered"><!-- available classes "bordered", "right-aligned" -->
                            <li class="active">
                                <a href="#running" data-toggle="tab">
                                    <span><i class="entypo-home"></i>
                                        <?php echo get_phrase('running'); ?></span>
                                </a>
                            </li>
                            <li class="">
                                <a href="#archived" data-toggle="tab">
                                    <span><i class="entypo-archive"></i>
                                        <?php echo get_phrase('archived'); ?></span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <br>
                            <div class="tab-pane active" id="running">

                                <?php include 'running_noticeboard.php'; ?>

                            </div>
                            <div class="tab-pane" id="archived">

                                <?php include 'archived_noticeboard.php'; ?>

                            </div>
                        </div>


                    </div>

                </div>
            </div>
            <!----TABLE LISTING ENDS--->


            <!----CREATION FORM STARTS---->
            <div class="tab-pane box" id="add" style="padding: 5px">
                <div class="box-content">
                    <?php echo form_open(site_url('admin/noticeboard/create') , array(
                      'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data',
                        'target' => '_top')); ?>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('title'); ?></label>
                        <div class="col-sm-5">
                            <input type="text" class="form-control" name="notice_title" required />
                        </div>
                    </div>
                    <div class="form-group">
                      <label class="col-sm-3 control-label"><?php echo get_phrase('notice'); ?></label>
                  		<div class="col-sm-5">
                  		  <textarea class="form-control" rows="5" name="notice"></textarea>
                  		</div>
                  	</div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('event_date'); ?></label>
                        <div class="col-sm-5">
                            <input type="text" class="datepicker form-control" name="create_timestamp"
                              value="<?php echo date('d M, Y');?>" required />
                        </div>
                    </div>

                    <div class="form-group">
                      <label for="field-1" class="col-sm-3 control-label"><?php echo get_phrase('image');?></label>
                      <div class="col-sm-7">
                        <div class="fileinput fileinput-new" data-provides="fileinput">
                          <div class="fileinput-new thumbnail" style="width: 300px; height: 150px;" data-trigger="fileinput">
                            <img src="<?php echo base_url(); ?>uploads/placeholder.png" alt="...">
                          </div>
                          <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px"></div>
                          <div>
                            <span class="btn btn-white btn-file">
                              <span class="fileinput-new"><?php echo get_phrase('select_image'); ?></span>
                              <span class="fileinput-exists"><?php echo get_phrase('change'); ?></span>
                              <input type="file" name="image" accept="image/*">
                            </span>
                            <a href="#" class="btn btn-orange fileinput-exists" data-dismiss="fileinput"><?php echo get_phrase('remove'); ?></a>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!--<div class="form-group">
          						<label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('show_on_website');?></label>
          						<div class="col-sm-4">
          							<select name="show_on_website" class="form-control select2">
                            <option value="1"><?php echo get_phrase('yes');?></option>
                            <option value="0"><?php echo get_phrase('no');?></option>
                        </select>
          						</div>
          					</div> //-->

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

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-5">
                            <input type="hidden" name="notice_timestamp" value="<?php echo date('d-M-Y, H:i:s');?>"/>
                            <button type="submit" id="submit_button" class="btn btn-info"><?php echo get_phrase('add_notice'); ?></button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
            <!----CREATION FORM ENDS-->

        </div>
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
