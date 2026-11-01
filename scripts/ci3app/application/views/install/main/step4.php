<?php if(isset($error)) { ?>
  <div class="row"
    style="margin-top: 20px;">
    <div class="col-md-8 col-md-offset-2">
      <div class="alert alert-danger">
        <strong><?php echo $error; ?></strong>
      </div>
    </div>
  </div>
<?php } ?>
<div class="row"
  style="margin-top: 30px;">
  <div class="col-md-8 col-md-offset-2">
    <div class="panel panel-default" data-collapsed="0"
      style="border-color: #dedede;">
      <!-- panel body -->
      <div class="panel-body" style="font-size: 14px;">
        <p style="font-size: 14px;">
          <strong>Your database is successfully connected</strong>. All you need to do now is
          <strong>hit the 'Install' button</strong>.
          The auto installer will run the sql file, will do all the tiresome works and set up your application automatically.
        </p>
        <br>
        <div class="row">
          <div class="col-md-12">
            <button type="button" id="install_button" class="btn btn-info">
              <i class="entypo-install"></i> &nbsp; Install
            </button>
            <div id="loader" style="margin-top: 20px;">
              <img src="<?php echo base_url('assets/images/lightworldtech.png');?>" style="color: green;" alt="" width="20">
              <img src="<?php echo base_url('assets/load.gif');?>" style="color: green; position: relative;" alt="" width="20">
              &nbsp; Importing database ....
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    $('#loader').hide();
    $('#install_button').click(function() {
      $('#ft p').fadeOut('3000', function() {
      	$('#ft p').hide();
      });

      setTimeout(() => {
        show_warning();
      }, 3000);
      $('#install_button').attr('readonly(enabled)');
      $('#install_button').html('<i class="entypo-install"></i> &nbsp; Installing...');
      $('#loader').fadeIn();
      window.location.href = '<?php echo site_url('install/step4/confirm_install');?>';
    });
  });

  function show_warning() {
    	$('#ft').html('<div><strong style="color: red;">Please do not refresh the page, as doing so will interrupt the installation process. <br>Please wait patiently, we will be done soon!</strong></div>')
    }
</script>
