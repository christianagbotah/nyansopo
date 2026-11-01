<hr />
<?php
    $active_payment_service = $this->db->get_where('settings' , array(
        'type' => 'active_payment_service'
    ))->row()->description;
?>
<div class="row">
    <div class="col-md-12">

        <div class="tabs-vertical-env">

            <ul class="nav tabs-vertical">
            <li class="active" id="defa_ult"><a href="#b-profile" data-toggle="tab">Select A Payment Service</a></li>
                <li id="hub_tel">
                    <a href="#v-home" data-toggle="tab">
                        Hubtel Payment Settings
                        <?php if ($active_payment_service == 'hubtel'):?>
                            <span class="badge badge-info" style="background-color: green;"><?php echo get_phrase('active');?></span>
                        <?php endif;?>
                    </a>
                </li>
                <!--
                <li>
                    <a href="#v-home" data-toggle="tab">
                        Clickatell Settings
                        <?php if ($active_payment_service == 'clickatell'):?>
                            <span class="badge badge-success"><?php echo get_phrase('active');?></span>
                        <?php endif;?>
                    </a>
                </li>
                <li>
                    <a href="#v-profile" data-toggle="tab">
                        Twilio Settings
                        <?php if ($active_payment_service == 'twilio'):?>
                            <span class="badge badge-success"><?php echo get_phrase('active');?></span>
                        <?php endif;?>
                    </a>
                </li>
                <li>
                    <a href="#v-msg91-profile" data-toggle="tab">
                        MSG91 Settings
                        <?php if ($active_payment_service == 'msg91'):?>
                            <span class="badge badge-success"><?php echo get_phrase('active');?></span>
                        <?php endif;?>
                    </a>
                </li>//-->
            </ul>

            <div class="tab-content">

                <div class="tab-pane active" id="b-profile">

                    <?php echo form_open(site_url('admin/payment_settings/active_service') ,
                        array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('select_a_service');?></label>
                        <div class="col-sm-5">
                            <select name="active_payment_service" class="form-control select2">
                                <option value="hubtel"
                                    <?php if ($active_payment_service == 'hubtel') echo 'selected';?>>
                                        Hubtel-Payment
                                </option>
                                <!--<option value="clickatell"
                                    <?php if ($active_payment_service == 'clickatell') echo 'selected';?>>
                                        Clickatell
                                </option>
                                <option value="twilio"
                                    <?php if ($active_payment_service == 'twilio') echo 'selected';?>>
                                        Twilio
                                </option>
                                                        <option value="msg91"
                                    <?php if ($active_payment_service == 'msg91') echo 'selected';?>>
                                        MSG91
                                </option>
                                --//-->
                                <option value="disabled"<?php if ($active_payment_service == 'disabled' || $active_payment_service == '' || $active_payment_service == null) echo 'selected';?>>
                                    <?php echo get_phrase('disabled');?>
                                </option>
                          </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-5">
                            <button type="submit" class="btn btn-info"><?php echo get_phrase('save');?></button>
                        </div>
                    </div>
                <?php echo form_close();?>
                </div>

                <div class="tab-pane" id="v-home">
                    <?php echo form_open(site_url('admin/payment_settings/hubtel_payment') ,
                        array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'hubtel_form'));?>
                        
                        <h4 class="text-primary">Payment Gateway Credentials</h4>
                        <hr>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Client ID</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="hubtel_payment_client_id" 
                                    value="<?php echo $this->db->get_where('settings' , array('type' =>'hubtel_payment_client_id'))->row()->description;?>" 
                                    placeholder="Enter Hubtel Client ID" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Client Secret</label>
                            <div class="col-sm-5">
                                <input type="password" class="form-control" name="hubtel_payment_client_secret"
                                    value="<?php echo $this->db->get_where('settings' , array('type' =>'hubtel_payment_client_secret'))->row()->description;?>" 
                                    placeholder="Enter Hubtel Client Secret" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Merchant Number</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="hubtel_payment_merchant_number" 
                                    value="<?php echo $this->db->get_where('settings' , array('type' =>'hubtel_payment_merchant_number'))->row()->description;?>" 
                                    placeholder="HM0000000" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Enable Gateway</label>
                            <div class="col-sm-5">
                                <select name="hubtel_payment_enabled" class="form-control">
                                    <?php $enabled = $this->db->get_where('settings', array('type'=>'hubtel_payment_enabled'))->row(); ?>
                                    <option value="1" <?php if($enabled && $enabled->description == '1') echo 'selected';?>>Enabled</option>
                                    <option value="0" <?php if(!$enabled || $enabled->description == '0') echo 'selected';?>>Disabled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Test Mode</label>
                            <div class="col-sm-5">
                                <select name="hubtel_payment_test_mode" class="form-control">
                                    <?php $test_mode = $this->db->get_where('settings', array('type'=>'hubtel_payment_test_mode'))->row(); ?>
                                    <option value="1" <?php if($test_mode && $test_mode->description == '1') echo 'selected';?>>Test Mode</option>
                                    <option value="0" <?php if(!$test_mode || $test_mode->description == '0') echo 'selected';?>>Live Mode</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-5">
                                <button type="submit" class="btn btn-info btn-block"><?php echo get_phrase('save');?></button>
                            </div>
                        </div>
                    <?php echo form_close();?>
                </div>

            </div>

        </div>

    </div>
</div>
<?php
    if(isset($_GET['cid']) && $_GET['cid'] == 1) { ?>
        <script type="text/javascript">
            $(function() {
               $('#defa_ult').removeClass('active');
               $('#b-profile').removeClass('active');
               $('#hub_tel').addClass('active');
               $('#v-home').addClass('active');
              // $('#hub_tel').click();
            });
        </script>
    <?php
    

    }
?>


<script type="text/javascript">
    $('#hubtel_form').submit(function(e) {
        e.preventDefault();
        showAjaxModal_alert('Saving...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(data.message || 'Operation failed', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    });
</script>