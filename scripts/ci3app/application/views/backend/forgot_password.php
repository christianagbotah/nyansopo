<!doctype html>
<?php
$system_name  = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
?>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?php echo get_phrase('forgot_password'); ?> | <?php echo $system_name; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo base_url('uploads/school_logo.png');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.css');?>">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/css/font-awesome-6.4.0.min.css">
    <link href="<?php echo base_url(); ?>assets/cdn/fonts/inter.css" rel="stylesheet">
    <script src="<?php echo base_url('assets/login_page/js/vendor/jquery-1.12.0.min.js');?>"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        *::-webkit-scrollbar { width: 0; height: 0; }
        * { scrollbar-width: none; -ms-overflow-style: none; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .reset-container { display: flex; max-width: 900px; width: 100%; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 95vh; }
        .reset-left { flex: 1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; position: relative; overflow: hidden; }
        .reset-left::before { content: ''; position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; top: -100px; right: -100px; }
        .reset-left::after { content: ''; position: absolute; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; bottom: -50px; left: -50px; }
        .illustration { z-index: 1; text-align: center; }
        .illustration i { font-size: 120px; margin-bottom: 30px; opacity: 0.9; }
        .illustration h2 { font-size: 28px; font-weight: 700; margin-bottom: 15px; }
        .illustration p { font-size: 15px; opacity: 0.9; line-height: 1.6; }
        .reset-right { flex: 1; padding: 60px 50px; overflow-y: auto; -ms-overflow-style: none; scrollbar-width: none; }
        .reset-right::-webkit-scrollbar { display: none; }
        .reset-header { margin-bottom: 40px; }
        .reset-header h2 { font-size: 28px; font-weight: 700; color: #1a202c; margin-bottom: 10px; }
        .reset-header p { color: #718096; font-size: 14px; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
        .form-input { width: 100%; padding: 14px 16px 14px 45px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 15px; transition: all 0.3s; background: #f9fafb; }
        .form-input:focus { outline: none; border-color: #667eea; background: white; box-shadow: 0 0 0 4px rgba(102,126,234,0.1); }
        .btn-reset { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-reset:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.4); }
        .btn-reset:active { transform: translateY(0); }
        .back-to-login { text-align: center; margin-top: 24px; }
        .back-to-login a { color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
        .back-to-login a:hover { text-decoration: underline; }
        .alert-box { padding: 14px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee; border: 1px solid #fcc; color: #c33; }
        .alert-success { background: #efe; border: 1px solid #cfc; color: #3c3; }
        .alert-box i { font-size: 18px; }
        .close-alert { margin-left: auto; cursor: pointer; opacity: 0.6; }
        .close-alert:hover { opacity: 1; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #9ca3af; }
        .footer a { color: #667eea; text-decoration: none; }
        @media (max-width: 768px) {
            body { overflow-y: auto; height: auto; }
            .reset-container { flex-direction: column; max-height: none; }
            .reset-left { padding: 30px 20px; }
            .reset-right { padding: 30px 20px; overflow-y: visible; }
            .illustration i { font-size: 80px; margin-bottom: 20px; }
            .illustration h2 { font-size: 22px; }
            .illustration p { font-size: 14px; }
            .reset-header h2 { font-size: 24px; }
            .footer { margin-top: 20px; font-size: 9px; }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-left">
            <div class="illustration">
                <i class="fas fa-key"></i>
                <h2>Forgot Password?</h2>
                <p>No worries! Enter your email address and we'll send you instructions to reset your password.</p>
            </div>
        </div>

        <div class="reset-right">
            <div class="reset-header">
                <h2>Reset Password</h2>
                <p>Enter your registered email address</p>
            </div>

            <?php if(isset($_GET['succ']) && $_GET['succ'] == 1) { ?>
                <div class="alert-box alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>
                        <?php if($this->session->userdata('sms_result') == 'success'): ?>
                            Password reset successful! Check your SMS or email inbox for your password.
                        <?php else: ?>
                            <?=$this->session->flashdata('reset_success');?>
                        <?php endif; ?>
                    </span>
                    <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php } else if(isset($_GET['err']) && $_GET['err'] == 1) { ?>
                <div class="alert-box alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Password reset failed. Email address not found.</span>
                    <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php } ?>

            <?php if(validation_errors()) :?>
                <div class="alert-box alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo validation_errors(); ?></span>
                    <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif;?>

            <?php echo form_open(site_url('login/reset_password'), array('id' => 'form_reset')); ?>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-input" name="email" placeholder="Enter your email address" required autocomplete="off">
                    </div>
                </div>
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>

            <div class="back-to-login">
                <a href="<?php echo site_url('login');?>">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>

            <div class="footer">
                &copy; <?php echo date('Y'); ?> <?php echo $system_name; ?>. All rights reserved.<br>
                Powered by <a href="https://www.lightworldtech.com" target="_blank">Lightworldtech</a>
            </div>
        </div>
    </div>

    <script src="<?php echo base_url('assets/js/bootstrap-notify.js');?>"></script>

    <?php if ($this->session->flashdata('reset_error') != '') { ?>
        <script>
            $.notify({
                title: '<strong>Error!</strong>',
                message: '<?php echo $this->session->flashdata('reset_error');?>'
            },{
                type: 'danger'
            });
        </script>
    <?php } ?>
    <?php if ($this->session->flashdata('reset_success') != '') { ?>
        <script>
            $.notify({
                title: '<strong>Success!</strong>',
                message: '<?php echo $this->session->flashdata('reset_success');?>'
            },{
                type: 'success'
            });
        </script>
    <?php } ?>
</body>
</html>
