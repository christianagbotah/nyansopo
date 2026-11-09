<hr />

<div class="row">

  <!-- Logo upload and update here -->
  <?php echo form_open(site_url('admin/system_settings/upload_logo') , array(
      'class' => 'form-horizontal form-groups-bordered validate','target'=>'_top' , 'enctype' => 'multipart/form-data'));?>

    <div class="col-md-6">
      <div class="panel panel-primary" >

        <div class="panel-heading">
            <div class="panel-title">
                <?php echo get_phrase('upload_logo');?>
            </div>
        </div>

        <div class="panel-body">


            <div class="form-group">
                <label for="field-1" class="col-sm-3 col-xs-3 control-label"><?php echo get_phrase('Logo');?></label>

                <div class="col-sm-9 col-xs-9">
                    <div class="fileinput fileinput-new" data-provides="fileinput">
                        <div class="fileinput-new thumbnail" style="width: 150px; height: 150px;" data-trigger="fileinput">
                            <img src="<?php echo base_url();?>uploads/school_logo.png" alt="...">
                        </div>
                        <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 150px; max-height: 150px"></div>
                        <div>
                            <span class="btn btn-white btn-file">
                                <span class="fileinput-new btn btn-primary btn-block">Select image</span>
                                <span class="fileinput-exists">Change</span>
                                <input type="file" name="userfile" accept="image/*" required="required">
                            </span>
                            <a href="#" class="btn btn-orange fileinput-exists" data-dismiss="fileinput">Remove</a>
                        </div>
                    </div>
                </div>
            </div>


          <div class="form-group">
            <div class="col-sm-offset-2 col-md-8 col-lg-offset-2 col-lg-8 col-xs-4 col-xs-offset-4">
                <button type="submit" class="btn btn-info btn-block col-sm-8 col-sm-offset-2"><?php echo get_phrase('upload_logo');?></button>
            </div>
          </div>

        </div>

    </div>
  </div>

  <?php echo form_close();?>
  <!-- End of Logo upload and update -->


  <!-- Start of Headmaster's Signature upload and update -->
  <?php echo form_open(site_url('admin/system_settings/upload_signature') , array(
  'class' => 'form-horizontal form-groups-bordered validate','target'=>'_top' , 'enctype' => 'multipart/form-data'));?>

    <div class="col-md-6">
      <div class="panel panel-primary" >

        <div class="panel-heading">
            <div class="panel-title">
                <?php echo get_phrase('upload_Signature');?>
            </div>
        </div>

        <div class="panel-body">


            <div class="form-group">
                <label for="field-1" class="col-sm-3 col-xs-3 control-label"><?php echo get_phrase('Signature');?></label>

                <div class="col-sm-9 col-xs-9">
                    <div class="fileinput fileinput-new" data-provides="fileinput">
                        <div class="fileinput-new thumbnail" style="width: 250px; height: 150px;" data-trigger="fileinput">
                            <img src="<?php echo base_url();?>uploads/signature/admin/head_teacher.png" alt="...">
                        </div>
                        <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px"></div>
                        <div>
                            <span class="btn btn-white btn-file">
                                <span class="fileinput-new btn btn-success btn-block">Select image</span>
                                <span class="fileinput-exists">Change</span>
                                <input type="file" name="signature" accept="image/*" required="required">
                            </span>
                            <a href="#" class="btn btn-orange fileinput-exists" data-dismiss="fileinput">Remove</a>
                        </div>
                    </div>
                </div>
            </div>


          <div class="form-group">
            <div class="col-sm-offset-2 col-md-8 col-lg-offset-2 col-lg-8 col-xs-4 col-xs-offset-4">
                <button type="submit" class="btn btn-info btn-block col-sm-8 col-sm-offset-2"><?php echo get_phrase('upload_signature');?></button>
            </div>
          </div>

        </div>

    </div>
  </div>

  <?php echo form_close();?>
  <!-- End of Headmaster's Signature upload and update -->

  <!-- Start of SSNIT Logo upload and update -->
  <?php echo form_open(site_url('admin/system_settings/upload_ssnit_logo') , array(
  'class' => 'form-horizontal form-groups-bordered validate','target'=>'_top' , 'enctype' => 'multipart/form-data'));?>

    <div class="col-md-6">
      <div class="panel panel-primary" >

        <div class="panel-heading">
            <div class="panel-title">
                <?php echo get_phrase('upload_ssnit_logo');?>
            </div>
        </div>

        <div class="panel-body">

            <div class="form-group">
                <label for="field-1" class="col-sm-3 col-xs-3 control-label"><?php echo get_phrase('ssnit_logo');?></label>

                <div class="col-sm-9 col-xs-9">
                    <div class="fileinput fileinput-new" data-provides="fileinput">
                        <div class="fileinput-new thumbnail" style="width: 150px; height: 150px;" data-trigger="fileinput">
                            <img src="<?php echo base_url();?>uploads/ssnit_logo.png" alt="...">
                        </div>
                        <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 150px; max-height: 150px"></div>
                        <div>
                            <span class="btn btn-white btn-file">
                                <span class="fileinput-new btn btn-primary btn-block">Select image</span>
                                <span class="fileinput-exists">Change</span>
                                <input type="file" name="ssnit_logo" accept="image/*" required="required">
                            </span>
                            <a href="#" class="btn btn-orange fileinput-exists" data-dismiss="fileinput">Remove</a>
                        </div>
                    </div>
                </div>
            </div>

          <div class="form-group">
            <div class="col-sm-offset-2 col-md-8 col-lg-offset-2 col-lg-8 col-xs-4 col-xs-offset-4">
                <button type="submit" class="btn btn-info btn-block col-sm-8 col-sm-offset-2"><?php echo get_phrase('upload_ssnit_logo');?></button>
            </div>
          </div>

        </div>

    </div>
  </div>

  <?php echo form_close();?>
  <!-- End of SSNIT Logo upload and update -->

  <!-- Start of System update -->
    <?php
     echo form_open(site_url('admin/system_settings/do_update') ,
      array('class' => 'form-horizontal form-groups-bordered','target'=>'_top', 'id' => 'system_settings_form'));?>
        <div class="col-md-6">

            <div class="panel panel-primary" >

                <div class="panel-heading">
                    <div class="panel-title">
                        <?php echo get_phrase('system_settings_one');?>
                    </div>
                </div>

                <div class="panel-body">

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('system_name');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" required="true" class="form-control" name="system_name"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'system_name'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('school\'s_slogan');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="system_title"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'system_title'))->row()->description;?>" required>
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('location');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="location" value="<?php echo $this->db->get_where('settings' , array('type' =>'location'))->row()->description;?>" required>  
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('address');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="address" value="<?php echo $this->db->get_where('settings' , array('type' =>'address'))->row()->description;?>" required>  
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('Box Number');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="box_number" value="<?php echo $this->db->get_where('settings' , array('type' =>'box_number'))->row()->description;?>">  
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('Digital Address');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="digital_address" value="<?php echo $this->db->get_where('settings' , array('type' =>'digital_address'))->row()->description;?>">  
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('Website Address');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="website_address" value="<?php echo $this->db->get_where('settings' , array('type' =>'website_address'))->row()->description;?>">  
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('phone');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="tel" class="form-control" name="phone[]"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'phone'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-5 control-label"><?php echo get_phrase('ssnit_establishment_number');?></label>
                      <div class="col-sm-9 col-xs-7">
                          <input type="text" class="form-control" name="ssnit_number"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'ssnit_number'))->row()->description;?>">
                      </div>
                  </div>

                  <!-- <div class="form-group">
                      <label  class="col-sm-3 control-label"><?php echo get_phrase('paypal_email');?></label>
                      <div class="col-sm-9">
                          <input type="text" class="form-control" name="paypal_email"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'paypal_email'))->row()->description;?>">
                      </div>
                  </div> -->

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('currency');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <input type="text" class="form-control" name="currency"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'currency'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-5 control-label"><?php echo get_phrase('system_email');?></label>
                      <div class="col-sm-9 col-xs-7">
                          <input type="email" class="form-control" name="system_email"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'system_email'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-4 control-label"><?php echo get_phrase('language');?></label>
                      <div class="col-sm-9 col-xs-8">
                          <select name="language" class="form-control select2">
                                <?php
                  $fields = $this->db->list_fields('language');
                  foreach ($fields as $field)
                  {
                    if ($field == 'phrase_id' || $field == 'phrase')continue;

                    $current_default_language = $this->db->get_where('settings' , array('type'=>'language'))->row()->description;
                    ?>
                                    <option value="<?php echo $field;?>"
                                          <?php if ($current_default_language == $field)echo 'selected';?>> <?php echo $field;?> </option>
                                        <?php
                  }
                  ?>
                           </select>
                      </div>
                  </div>

                  <div class="clearfix"></div>

                  <div class="form-group col-md-6 col-sm-6">
                      <label  class="col-sm-6 col-xs-10 control-label"><?php echo get_phrase('running_session');?></label>
                      <div class="col-sm-6 col-xs-12">
                          <select name="running_year" class="form-control select2" >
                          <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
                          $exp = explode('-', $running_year);
                          $year_display = $exp[1];

                          ?>
                          <option value="<?= $running_year;?>" disabled="true"><?php echo $year_display;?></option>
                          <?php
                         // echo populate_academic_year('yes');
                        ?>
                          </select>
                      </div>
                  </div>

                  <div class="form-group col-md-6 col-sm-6">
                    <label  class="col-sm-6 col-xs-10 control-label"><?php echo get_phrase('running_term');?></label>
                      <div class="col-sm-6 col-xs-12">
                          <select name="running_term" class="form-control select2">
                          <?php $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
                          <option value="<?=$running_term; ?>" disabled="true"><?php echo $running_term;?></option>
                          
                          </select>
                      </div>
                  </div>

                  <div class="clearfix"></div>

                  <!-- <div class="form-group col-md-6 col-sm-6">
                    <label  class="col-sm-7 col-xs-7 control-label"><?php //echo get_phrase('semester');?></label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="running_sem" class="form-control select2">
                          <?php //$running_sem = $this->db->get_where('settings' , array('type'=>'running_sem'))->row()->description;?>
                          <option value="<?=$running_sem; ?>" disabled="true"><?php //echo $running_sem;?></option>
                          
                          </select>
                      </div>
                  </div> -->

                  <div class="form-group col-md-6 col-sm-6">
                    <label for="field-2" class="col-sm-6 col-xs-10 control-label"><?php echo get_phrase('term_ending');?></label>

                    <div class="col-sm-6 col-xs-12">
                      <input type="text" class="form-control datepicker" name="term_ending"  value="<?php echo $this->db->get_where('settings' , array('type' =>'term_ending'))->row()->description;?>" data-start-view="2" required>
                    </div>
                  </div>

                   <div class="form-group col-md-6 col-sm-6">
                    <label for="field-2" class="col-sm-6 col-xs-10 control-label"><?php echo get_phrase('next_term_begins');?></label>

                    <div class="col-sm-6 col-xs-12">
                      <input type="text" class="form-control datepicker" name="next_term_begins"  value="<?php echo $this->db->get_where('settings' , array('type' =>'next_term_begins'))->row()->description;?>" data-start-view="2" required>
                    </div>
                  </div>

                  <div class="clearfix"></div>

              </div>
            </div>
          </div>

<!-- Sub-division of the system settings -->
          <div class="col-md-6">

            <div class="panel panel-primary" >

              <div class="panel-heading">
                  <div class="panel-title">
                      <?php echo get_phrase('system_settings_two');?>
                  </div>
              </div>
              <div class="panel-body">

                  <!-- <div class="form-group">
                    <label for="field-2" class="col-sm-5 col-xs-7 control-label"><?php //echo get_phrase('semester_ending');?></label>

                    <div class="col-sm-7 col-xs-5">
                      <input type="text" class="form-control datepicker" name="sem_ending"  value="<?php //echo $this->db->get_where('settings' , array('type' =>'sem_ending'))->row()->description;?>" data-start-view="2">
                    </div>
                  </div>

                   <div class="form-group">
                    <label for="field-2" class="col-sm-5 col-xs-7 control-label"><?php //echo get_phrase('next_semester_begins');?></label>

                    <div class="col-sm-7 col-xs-5">
                      <input type="text" class="form-control datepicker" name="next_sem_begins"  value="<?php //echo $this->db->get_where('settings' , array('type' =>'next_sem_begins'))->row()->description;?>" data-start-view="2">
                    </div>
                  </div> -->

                  <div class="form-group">
                      <label  class="col-sm-7 col-xs-7 control-label"><?php echo 'Part (Half) Payment of school fees is expected by';?></label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="half_payment_week" class="form-control select2">
                              <?php $half_payment = $this->db->get_where('settings' , array('type'=>'half_payment_week'))->row()->description;?>
                              <option value="First Week of the Term" <?php if ($half_payment == 'First Week of the Term')echo 'selected';?>> First Week of the Term</option>
                              <option value="Second Week of the Term" <?php if ($half_payment == 'Second Week of the Term')echo 'selected';?>> Second Week of the Term</option>
                              <option value="Third Week of the Term" <?php if ($half_payment == 'Third Week of the Term')echo 'selected';?>> Third Week of the Term</option>
                              <option value="Forth Week of the Term" <?php if ($half_payment == 'Forth Week of the Term')echo 'selected';?>> Forth Week of the Term</option>
                          </select>
                      </div>
                  </div>

                  <div class="form-group">
                    <label for="field-2" class="col-sm-7 col-xs-7 control-label"><?php echo 'Full Payment of school fees is expected by';?></label>

                    <div class="col-sm-5 col-xs-5">
                      <input type="text" class="form-control datepicker" name="full_payment_date"  value="<?php echo $this->db->get_where('settings' , array('type' =>'full_payment_date'))->row()->description;?>" data-start-view="2" required>
                    </div>
                  </div>

                 <!--  <div class="form-group">
                    <label for="field-2" class="col-sm-7 col-xs-7 control-label"><?php //echo 'Full Payment of school fees is expected by (JHS)';?></label>

                    <div class="col-sm-5 col-xs-5">
                      <input type="text" class="form-control datepicker" name="full_payment_date_jhs"  value="<?php //echo $this->db->get_where('settings' , array('type' =>'full_payment_date_jhs'))->row()->description;?>" data-start-view="2" required>
                    </div>
                  </div> -->
                  

                  <!-- <div class="form-group">
                      <label  class="col-sm-5 col-xs-7 control-label"><?php //echo 'WAEC Standard Grading';?></label>
                      <div class="col-sm-7 col-xs-5">
                          <select name="raw_score" class="form-control select2">
                              <?php //$raw_score = $this->db->get_where('settings' , array('type'=>'raw_score'))->row()->description;?>
                              <option value="Yes" <?php //if ($raw_score == 'Yes')echo 'selected';?>> Yes</option>
                              <option value="No" <?php //if ($raw_score == 'No')echo 'selected';?>> No</option>
                          </select>
                      </div>
                  </div> -->

                   <!--
                  <div class="form-group">
                      <label  class="col-sm-3 control-label"><?php //echo get_phrase('text_align');?></label>
                      <div class="col-sm-9">
                          <select name="text_align" class="form-control select2">
                              <?php// $text_align = $this->db->get_where('settings' , array('type'=>'text_align'))->row()->description;?>
                              <option value="left-to-right" <?php //if ($text_align == 'left-to-right')echo 'selected';?>> left-to-right</option>
                              <option value="right-to-left" <?php //if ($text_align == 'right-to-left')echo 'selected';?>> right-to-left</option>
                          </select>
                      </div>
                  </div>
                 
                  <div class="form-group">
                      <label  class="col-sm-3 control-label"><?php //echo get_phrase('disable_frontend');?></label>
                      <div class="col-sm-9">
                        <div class="container">
                          <label class="control control--checkbox">
                            <input type="checkbox" name="disable_frontend" <?php //if ($this->db->get_where('settings' , array('type' =>'disable_frontend'))->row()->description == 1) echo 'checked'; ?> />
                            <div class="control__indicator"></div>
                          </label>
                        </div>
                      </div>
                  </div>
                -->
                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-5 control-label"><?php echo get_phrase('mobile_money_account_name');?></label>
                      <div class="col-sm-7 col-xs-7">
                          <input type="text" class="form-control" name="mo_account_name"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'mo_account_name'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-7 control-label"><?php echo get_phrase('mobile_money_account_no:');?></label>
                      <div class="col-sm-7 col-xs-5">
                          <input type="tel" class="form-control" name="mo_account_number"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'mo_account_number'))->row()->description;?>">
                      </div>
                  </div>

                  <!--Staff ID Prefix and Staff ID format-->
                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-6 control-label"><?php echo get_phrase('staff_iD_prefix');?></label>
                      <div class="col-sm-7 col-xs-6">
                          <input type="text" class="form-control" name="teacher_code_prefix"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'teacher_code_prefix'))->row()->description;?>">
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-6 control-label"><?php echo get_phrase('staff_iD_format');?></label>
                      <div class="col-sm-7 col-xs-6">
                          <input type="text" class="form-control" name="teacher_code_format"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'teacher_code_format'))->row()->description;?>" required>
                      </div>
                  </div>

                  <!--Student ID and Invoice Number format-->
                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-6 control-label"><?php echo get_phrase('student_iD_prefix');?></label>
                      <div class="col-sm-7 col-xs-6">
                          <input type="text" class="form-control" name="student_code_prefix"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'student_code_prefix'))->row()->description;?>">
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-6 control-label"><?php echo get_phrase('student_iD_format');?></label>
                      <div class="col-sm-7 col-xs-6">
                          <input type="text" class="form-control" name="student_code_format"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'student_code_format'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-5 col-xs-6 control-label"><?php echo get_phrase('invoice_number_format:');?><span style="color: red">Numeric Only</span></label>
                      <div class="col-sm-7 col-xs-6">
                          <input type="number" class="form-control" name="invoice_number_format"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'invoice_number_format'))->row()->description;?>" required>
                      </div>
                  </div>

                  <!--<div class="form-group">
                      <label  class="col-sm-5 col-xs-6 control-label"><?php echo get_phrase('invoice_full_payment_after');?></label>
                      <div class="col-sm-5 col-xs-4">
                            <input type="number" class="form-control" min="0" name="invoice_due"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'invoice_due'))->row()->description;?>" required>
                      </div>
                      <div class="col-sm-2 col-xs-2"><strong>Days</strong></div>
                  </div>-->

                  

                  <div class="form-group">
                      <label  class="col-sm-7 col-xs-7 control-label"><?php echo 'Select Receipt Style';?></label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="receipt_style" class="form-control select2">
                            <?php $receipt_style = $this->db->get_where('settings' , array('type'=>'receipt_style'))->row()->description;?>

                              <option value="style_1" <?php if ($receipt_style == 'style_1')echo 'selected';?>>Style One (A5 Standard)</option>
                              <option value="style_2" <?php if ($receipt_style == 'style_2')echo 'selected';?>>Style Two (A5 Detailed)</option>
                              <option value="style_3" <?php if ($receipt_style == 'style_3')echo 'selected';?>>Style Three (A5 Professional)</option>
                              <option value="style_thermal" <?php if ($receipt_style == 'style_thermal')echo 'selected';?>>Thermal Receipt (80mm POS)</option>
                          </select>
                      </div>
                  </div>
                  <div class="form-group">
                      <label  class="col-sm-7 col-xs-7 control-label"><?php echo 'Select Terminal Report Style';?></label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="terminal_report_style" class="form-control select2">
                            <?php $terminal_report_style = $this->db->get_where('settings' , array('type'=>'terminal_report_style'))->row()->description;?>
                            
                              <option value="style_1" <?php if ($terminal_report_style == 'style_1')echo 'selected';?>>Style One</option>
                              <option value="style_2" <?php if ($terminal_report_style == 'style_2')echo 'selected';?>>Style Two</option>
                              <option value="style_3" <?php if ($terminal_report_style == 'style_3')echo 'selected';?>>Style Three</option>
                          </select>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-7 col-xs-7 control-label"><?php echo 'School has Boarding';?></label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="boarding_system" class="form-control select2">
                            <?php $boarding_system = $this->db->get_where('settings' , array('type'=>'boarding_system'))->row()->description;?>

                              <option value="yes" <?php if ($boarding_system == 'yes')echo 'selected';?>>Yes</option>
                              <option value="no" <?php if ($boarding_system == 'no')echo 'selected';?>>No</option>
                          </select>
                      </div>
                  </div>

                  <div class="form-group">
                      <label  class="col-sm-7 col-xs-7 control-label"><?php echo 'Fee Collection Mode';?></label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="fee_collection_mode" class="form-control select2">
                            <?php $fee_collection_mode = $this->db->get_where('settings' , array('type'=>'fee_collection_mode'))->row()->description;?>

                              <option value="integrated" <?php if ($fee_collection_mode == 'integrated')echo 'selected';?>>Integrated</option>
                              <option value="separated" <?php if ($fee_collection_mode == 'separated')echo 'selected';?>>Separated</option>
                          </select>
                      </div>
                  </div>

                  <hr style="border-top: 2px solid #5867dd; margin: 20px 0;">
                  
                  <div class="form-group" style="background: #f0f4ff; padding: 15px; border-radius: 6px; margin: 15px 0;">
                      <label  class="col-sm-7 col-xs-7 control-label" style="font-weight: 600; color: #5867dd;">
                          <i class="fa fa-chart-line"></i> <?php echo get_phrase('enable_portfolio_assessment');?>
                      </label>
                      <div class="col-sm-5 col-xs-5">
                          <select name="portfolio_assessment_enabled" class="form-control select2">
                            <?php 
                            $portfolio_check = $this->db->get_where('settings' , array('type'=>'portfolio_assessment_enabled'));
                            $portfolio_assessment_enabled = ($portfolio_check->num_rows() > 0) ? $portfolio_check->row()->description : '0';
                            ?>

                              <option value="1" <?php if ($portfolio_assessment_enabled == '1')echo 'selected';?>>Yes</option>
                              <option value="0" <?php if ($portfolio_assessment_enabled == '0')echo 'selected';?>>No</option>
                          </select>
                      </div>
                  </div>

                  <hr style="border-top: 2px solid #5867dd; margin: 20px 0;">

                  <div class="form-group">
                      <label  class="col-sm-3 col-xs-5 control-label"><?php echo get_phrase('purchase_code');?></label>
                      <div class="col-sm-9 col-xs-7">
                          <input type="text" class="form-control" name="purchase_code"
                              value="<?php echo $this->db->get_where('settings' , array('type' =>'purchase_code'))->row()->description;?>" required>
                      </div>
                  </div>

                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-8 col-xs-4 col-xs-offset-4">
                        <button type="submit" class="btn btn-info btn-block"><?php echo get_phrase('save_settings');?></button>
                    </div>
                  </div>
              </div>
            </div>
          </div>
        <?php echo form_close();?>
  <!-- End of System update -->
  

            

     <!-- <div class="panel panel-primary" data-collapsed="0">

            <div class="panel-heading">
                <div class="panel-title">
                    <?php echo get_phrase('update_product');?>
                </div>
            </div>


            <div class="panel-body form-horizontal form-groups-bordered">
                <?php echo form_open(site_url('updater/update') , array('class' => 'form-horizontal form-groups-bordered', 'enctype' => 'multipart/form-data'));?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('file'); ?></label>

                        <div class="col-sm-5">

                            <input type="file" name="file_name" class="form-control file2 inline btn btn-primary" data-label="<i class='glyphicon glyphicon-file'></i> Browse" />

                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-5">
                            <input type="submit" class="btn btn-info" value="<?php echo get_phrase('install_update'); ?>" />
                        </div>
                    </div>

                <?php echo form_close(); ?>
            </div>

        </div>-->
    </div>
<style media="screen">
.container {

}

.control-group {
display: inline-block;
vertical-align: top;
background: #fff;
text-align: left;
box-shadow: 0 1px 2px rgba(0,0,0,0.1);
padding: 30px;
width: 200px;
height: 210px;
margin: 10px;
}
.control {
display: block;
position: relative;
padding-left: 40px;
margin-bottom: 15px;
cursor: pointer;
font-size: 18px;
}
.control input {
position: absolute;
z-index: -1;
opacity: 0;
}
.control__indicator {
position: absolute;
top: 2px;
left: -11px;
height: 20px;
width: 20px;
background: #e6e6e6;
}
.control--radio .control__indicator {
border-radius: 50%;
}
.control:hover input ~ .control__indicator,
.control input:focus ~ .control__indicator {
background: #ccc;
}
.control input:checked ~ .control__indicator {
background: #2aa1c0;
}
.control:hover input:not([disabled]):checked ~ .control__indicator,
.control input:checked:focus ~ .control__indicator {
background: #0e647d;
}
.control input:disabled ~ .control__indicator {
background: #e6e6e6;
opacity: 0.6;
pointer-events: none;
}
.control__indicator:after {
content: '';
position: absolute;
display: none;
}
.control input:checked ~ .control__indicator:after {
display: block;
}
.control--checkbox .control__indicator:after {
      left: 8px;
      top: 5px;
      width: 4px;
      height: 9px;
      border: 3px solid #fff;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
}
.control--checkbox input:disabled ~ .control__indicator:after {
border-color: #7b7b7b;
}


</style>

<script type="text/javascript">
  $('#system_settings_form').submit(function(e) {
    //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

    showAjaxModal_alert('UPDATING SYSTEM SETTINGS, PLEASE WAIT...', 'Loading');
  })
</script>

