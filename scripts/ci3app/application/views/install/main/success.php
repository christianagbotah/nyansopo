<?php 
  $admin_email = $this->db->get('admin')->row()->email;
  $admin_auth_key = $this->db->get('admin')->row()->authentication_key;
  $phone = $this->db->get('admin')->row()->phone;
?>

<div class="row"
  style="margin-top: 30px;">
  <div class="col-md-8 col-md-offset-2">
    <div class="panel panel-default" data-collapsed="0"
      style="border: 2px solid #dedede; box-shadow: 2px 5px 2px 2px #dedede; background-color: green; padding: 10px; color: white;">
			<!-- panel body -->
			<div class="panel-body" style="font-size: 14px;">
        <h2 style="color: white;">Success!!</h2>
        <hr>
        <br>
        <p style="font-size: 14px;">
          <strong>Installation was successfull. Please login to continue..</strong>
        </p>
        <br>
        <table>
          <tbody>
            <tr>
              <td style="padding: 12px;" align="right"><strong>Administrator Email |</strong></td>
              <td style="padding: 12px;"><?php echo $admin_email; ?></td>
            </tr>
            <tr>
              <td style="padding: 12px;" align="right"><strong>Password |</strong></td>
              <td style="padding: 12px;">Your chosen password</td>
            </tr>
            <tr>
              <td style="padding: 12px;" align="right"><strong>Phone |</strong></td>
              <td style="padding: 12px;"><?php echo $phone ?></td>
            </tr>
            <tr>
              <td style="padding: 12px;" align="right"><strong>Authentication Key |</strong></td>
              <td style="padding: 12px;"><?php echo $admin_auth_key ?></td>
            </tr>
          </tbody>
        </table>
        <hr>
        <br>
        <p>
          <a href="<?php echo site_url('install/success/login');?>" class="btn btn-info">
            <i class="entypo-login"></i> &nbsp; Log In
          </a>
        </p>
			</div>
		</div>
  </div>
</div>
