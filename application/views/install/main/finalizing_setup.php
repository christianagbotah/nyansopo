<div class="row"
  style="margin-top: 30px;">
  <div class="col-md-8 col-md-offset-2">
    <div class="panel panel-success" data-collapsed="0"
      style="border-color: #dedede;">
      <!-- panel body -->
      <div class="panel-body" style="font-size: 14px;">
        <center>
          <i class="entypo-thumbs-up" style="font-size: 32px; color: green;"></i>
          <h3 style="color: green;">Congratulations!! The installation was successfull</h3>
        </center>
        <br>
        <center>
          <div style="background-color: green; padding: 10px; color: white;">
          <strong>
            Before you start using your application, make it yours. Set your application name and title, admin login email and
            password. Remember the login credentials which you will need later on for signing into your account. After this step,
            you will be redirected to application's login page.
          </strong>
        </div>
        </center>
        <br>
        <div class="row">
          <div class="col-md-12">
            
            <?php if(validation_errors()) :?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                    </button>
                   <strong> <?php echo validation_errors(); ?></strong>
                </div>
                <?php endif;?>

              <?php echo form_open(site_url('install/finalizing_setup/setup_admin'), array('class' => 'form-horizontal form-groups')); ?>
              <hr>
              <div class="form-group">
        				<label class="col-sm-3 control-label">School Name</label>
        				<div class="col-sm-5">
        					<input type="text" class="form-control" value="<?php echo set_value('system_name'); ?>" name="system_name" placeholder=""
                    required autofocus>
        				</div>
                <div class="col-sm-4" style="font-size: 12px;">
                  The name of your application
                </div>
        			</div>
              <hr>
              <div class="form-group">
        				<label class="col-sm-3 control-label">Admin Name</label>
        				<div class="col-sm-5">
        					<input type="text" class="form-control" name="name" value="<?php echo set_value('name'); ?>" placeholder="" required>
        				</div>
                <div class="col-sm-4" style="font-size: 12px;">
                  Full name of Administrator
                </div>
        			</div>
              <hr>
              <div class="form-group">
                <label class="col-sm-3 control-label">Admin Phone</label>
                <div class="col-sm-5">
                  <input type="text" class="form-control" name="phone[]" value="<?php echo set_value('phone'); ?>" placeholder="" required>
                </div>
                <div class="col-sm-4" style="font-size: 12px;">
                  Administrator's Phone
                </div>
              </div>
              <hr>
              <div class="form-group">
        				<label class="col-sm-3 control-label">Admin Email</label>
        				<div class="col-sm-5">
        					<input type="email" class="form-control" name="email" value="<?php echo set_value('email'); ?>" placeholder="" required>
        				</div>
                <div class="col-sm-4" style="font-size: 12px;">
                  Email address for administrator login
                </div>
        			</div>
              <hr>
              <div class="form-group">
        				<label class="col-sm-3 control-label">Password</label>
        				<div class="col-sm-5">
        					<input type="password" class="form-control" name="password"  placeholder=""
                    required>
        				</div>
                <div class="col-sm-4" style="font-size: 12px;">
                  Admin login password
                </div>
        			</div>
              <hr>
              <div class="form-group">
        				<label class="col-sm-3 control-label"></label>
        				<div class="col-sm-7">
        					<button type="submit" class="btn btn-info">Set me up</button>
        				</div>
        			</div>
           <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
