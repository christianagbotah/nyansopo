<!doctype html>
<?php
$system_name  = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$admin_email = $this->db->get_where('admin', array('level' => 1))->first_row()->email;
$theme_primary = $this->db->get_where('settings', array('type' => 'theme_primary'))->row()->description ?? '#667eea';
$theme_secondary = $this->db->get_where('settings', array('type' => 'theme_secondary'))->row()->description ?? '#764ba2';

function valid_email_address($email_address) {
    return (bool) filter_var($email_address, FILTER_VALIDATE_EMAIL);
}
?>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Login | <?php echo $system_name; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo base_url('uploads/school_logo.png');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.css');?>">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/css/font-awesome-6.4.0.min.css">
    <link href="<?php echo base_url(); ?>assets/cdn/fonts/inter.css" rel="stylesheet">
    <script src="<?php echo base_url('assets/login_page/js/vendor/jquery-3.3.1.min.js');?>"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        *::-webkit-scrollbar { width: 0; height: 0; }
        * { scrollbar-width: none; -ms-overflow-style: none; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { display: flex; max-width: 1000px; width: 100%; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 95vh; }
        .login-left { flex: 1; background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%); padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; position: relative; overflow: hidden; }
        .login-left::before { content: ''; position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; top: -100px; right: -100px; }
        .login-left::after { content: ''; position: absolute; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; bottom: -50px; left: -50px; }
        .school-icons { position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0.15; z-index: 0; }
        .school-icons i { position: absolute; color: white; }
        .school-icons .icon-1 { font-size: 80px; top: 15%; left: 10%; }
        .school-icons .icon-2 { font-size: 60px; top: 60%; right: 15%; }
        .school-icons .icon-3 { font-size: 50px; bottom: 20%; left: 20%; }
        .school-icons .icon-4 { font-size: 70px; top: 35%; right: 10%; }
        .school-icons .icon-5 { font-size: 45px; bottom: 35%; right: 25%; }
        .logo-container { background: white; border-radius: 50%; padding: 20px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 1; }
        .logo-container img { width: 100px; height: 100px; object-fit: contain; }
        .welcome-text { z-index: 1; text-align: center; }
        .welcome-text h1 { font-size: 32px; font-weight: 700; margin-bottom: 15px; }
        .welcome-text p { font-size: 16px; opacity: 0.9; line-height: 1.6; }
        .login-right { flex: 1; padding: 60px 50px; overflow-y: auto; -ms-overflow-style: none; scrollbar-width: none; }
        .login-right::-webkit-scrollbar { display: none; }
        .login-header h2 { font-size: 28px; font-weight: 700; color: #1a202c; margin-bottom: 10px; }
        .login-header p { color: #718096; font-size: 14px; margin-bottom: 40px; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
        .form-input { width: 100%; padding: 14px 16px 14px 45px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 15px; transition: all 0.3s; background: #f9fafb; }
        .form-input:focus { outline: none; border-color: <?php echo $theme_primary; ?>; background: white; box-shadow: 0 0 0 4px rgba(<?php echo hexdec(substr($theme_primary, 1, 2)); ?>, <?php echo hexdec(substr($theme_primary, 3, 2)); ?>, <?php echo hexdec(substr($theme_primary, 5, 2)); ?>, 0.1); }
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(<?php echo hexdec(substr($theme_primary, 1, 2)); ?>, <?php echo hexdec(substr($theme_primary, 3, 2)); ?>, <?php echo hexdec(substr($theme_primary, 5, 2)); ?>, 0.4); }
        .btn-login:active { transform: translateY(0); }
        .forgot-password { text-align: center; margin-top: 20px; }
        .forgot-password a { color: <?php echo $theme_primary; ?>; text-decoration: none; font-size: 14px; font-weight: 500; }
        .forgot-password a:hover { text-decoration: underline; }
        .alert-box { padding: 14px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee; border: 1px solid #fcc; color: #c33; }
        .alert-success { background: #efe; border: 1px solid #cfc; color: #3c3; }
        .alert-box i { font-size: 18px; }
        .close-alert { margin-left: auto; cursor: pointer; opacity: 0.6; }
        .close-alert:hover { opacity: 1; }
        .auth-key-input { text-align: center; font-size: 20px; font-weight: 700; letter-spacing: 4px; padding: 16px; }
        .auth-status { padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-size: 14px; font-weight: 500; position: relative; }
        .auth-status.success { background: #10b981; color: white; }
        .auth-status.error { background: #ef4444; color: white; }
        .auth-status.loading { background: #f3f4f6; color: #6b7280; }
        .auth-status .close-auth { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.8; font-size: 18px; }
        .auth-status .close-auth:hover { opacity: 1; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #9ca3af; }
        .footer a { color: #667eea; text-decoration: none; }
        @media (max-width: 768px) {
            body { overflow-y: auto; height: auto; }
            .login-container { flex-direction: column; max-height: none; }
            .login-left { padding: 30px 20px; }
            .login-right { padding: 30px 20px; overflow-y: visible; }
            .welcome-text h1 { font-size: 22px; }
            .welcome-text p { font-size: 14px; }
            .logo-container { padding: 15px; margin-bottom: 20px; }
            .logo-container img { width: 80px; height: 80px; }
            .login-header h2 { font-size: 24px; }
            .footer { margin-top: 20px; font-size: 9px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="school-icons">
                <i class="fas fa-graduation-cap icon-1"></i>
                <i class="fas fa-book-open icon-2"></i>
                <i class="fas fa-chalkboard-teacher icon-3"></i>
                <i class="fas fa-user-graduate icon-4"></i>
                <i class="fas fa-pencil-alt icon-5"></i>
            </div>
            <div class="logo-container">
                <img src="<?php echo base_url('uploads/school_logo.png');?>" alt="Logo">
            </div>
            <div class="welcome-text">
                <h1><?php echo $system_name; ?></h1>
                <p>Secure access to your school management system. Please authenticate to continue.</p>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Enter your credentials to access your account</p>
            </div>

            <?php if(validation_errors()) :?>
                <div class="alert-box alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo validation_errors(); ?></span>
                    <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif;?>

            <?php if(isset($page_info) && $page_info == 'blocked') {?>
                <div class="alert-box alert-danger">
                    <i class="fas fa-ban"></i>
                    <span>Your account has been blocked. Please contact your administrator.</span>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="mailto:<?php echo $admin_email; ?>" style="color: #667eea; font-weight: 500;">
                        <i class="fas fa-envelope"></i> <?php echo $admin_email; ?>
                    </a>
                </div>
            <?php } else { ?>

                <div class="alert-box alert-danger" id="fail_login" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="fail_message"></span>
                    <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>

                <div id="admin_mail2" style="display: none; text-align: center; margin-bottom: 20px;">
                    <a href="mailto:<?php echo $admin_email; ?>" style="color: #667eea; font-weight: 500;">
                        <i class="fas fa-envelope"></i> <?php echo $admin_email; ?>
                    </a>
                </div>

                <?php echo form_open('', array('id' => 'form_block', 'style' => 'display: none')); ?>
                    <div class="form-group">
                        <label class="form-label">Email or Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="text" class="form-input" name="email_block" id="email_block" placeholder="Enter your email or username" required>
                        </div>
                    </div>
                    <button type="button" id="send_mail" class="btn-login">Send Email</button>
                </form>

                <div id="auth_verification_holder"></div>

                <?php echo form_open('', array('id' => 'auth_form')); ?>
                    <div class="form-group" id="auth_key_holder">
                        <label class="form-label">Authentication Key</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" class="form-input auth-key-input" name="auth_key" id="auth_key" maxlength="5" placeholder="XXXXX" required autocomplete="off" onkeyup="auth_verification(this.value)">
                        </div>
                    </div>
                </form>

                <?php echo form_open(site_url('login/validate_login'), array('id' => 'form_login', 'style' => 'display: none')); ?>
                    <div class="form-group">
                        <label class="form-label">Email / Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-input" id="username_email" name="email" placeholder="Enter your email or username" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-input" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <input type="hidden" name="auth_key2" value="" id="auth_key2">
                    <button type="submit" class="btn-login" id="login_btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div class="forgot-password">
                    <a href="<?php echo site_url('login/forgot_password');?>">
                        <i class="fas fa-question-circle"></i> Forgot Your Password?
                    </a>
                </div>

            <?php } ?>

            <div class="footer">
                &copy; <?php echo date('Y'); ?> <?php echo $system_name; ?>. All rights reserved.<br>
                Powered by <a href="https://www.lightworldtech.com" target="_blank" style="color: <?php echo $theme_primary; ?>;">Lightworldtech</a>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm" role="document" style="margin-top: 150px;">
            <div class="modal-content" style="background: white; border-radius: 16px; border: none;">
                <div class="modal-body text-center" style="padding: 40px;">
                    <div style="width: 50px; height: 50px; border: 4px solid #f3f4f6; border-top-color: <?php echo $theme_primary; ?>; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <p style="font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 8px;">Authenticating...</p>
                    <p style="font-size: 14px; color: #6b7280;">Please wait while we verify your credentials</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <script src="<?php echo base_url('assets/js/bootstrap-notify.js');?>"></script>
    <script src="<?php echo base_url('assets/js/bootstrap.js');?>"></script>

    <?php if ($this->session->flashdata('login_error') != '') { ?>
        <script>
            $.notify({
                title: '<strong>Error!</strong>',
                message: '<?php echo $this->session->flashdata('login_error');?>'
            },{
                type: 'danger'
            });
        </script>
    <?php } ?>

    <script>
        var fail_counter = 1;

        function auth_verification(auth_key) {
            if(auth_key.length < 5) return;
            
            $.ajax({
                url: '<?php echo site_url('login/auth_verification/') ?>' + auth_key,
                success: function(response) {
                    if(auth_key.length == 5) {
                        if(response == 'blocked') {
                            $('#auth_verification_holder').html('<div class="auth-status loading"><i class="fas fa-spinner fa-spin"></i> Authenticating...</div>');
                            setTimeout(() => {
                                $('#auth_key_holder').hide();
                                $('#auth_verification_holder').hide();
                                $('#fail_login').show().find('#fail_message').text('Your account has been blocked. Please contact your administrator.');
                                $('#admin_mail2').show();
                            }, 2000);
                        } else if(response == 'true' || response == 'true_student') {
                            $('#auth_key2').val(auth_key);
                            $('#auth_verification_holder').html('<div class="auth-status loading"><i class="fas fa-spinner fa-spin"></i> Authenticating...</div>');
                            
                            setTimeout(() => {
                                $('#auth_verification_holder').html('<div class="auth-status success"><i class="fas fa-check-circle"></i> Authentication successful!</div>');
                                $('#auth_key').prop('readonly', true);
                            }, 2000);

                            setTimeout(() => {
                                $('#auth_verification_holder').fadeOut(300, function() {
                                    $('#auth_form').hide();
                                    $('#form_login').fadeIn(300);
                                    
                                    if(response == 'true_student') {
                                        $('#username_email').attr({
                                            'type': 'text',
                                            'name': 'username',
                                            'placeholder': 'Enter your username'
                                        });
                                    }
                                });
                            }, 3500);
                        } else {
                            $('#auth_verification_holder').html('<div class="auth-status loading"><i class="fas fa-spinner fa-spin"></i> Authenticating...</div>');
                            
                            setTimeout(() => {
                                $('#auth_verification_holder').html('<div class="auth-status error"><i class="fas fa-times-circle"></i> Authentication failed<span class="close-auth" onclick="$(this).parent().fadeOut()">&times;</span></div>');
                                
                                if(fail_counter == 2) {
                                    $('#fail_login').show().find('#fail_message').text('2 failed attempts. Your account will be blocked after 3rd failure.');
                                } else if(fail_counter == 3) {
                                    $('#auth_form').hide();
                                    $('#form_block').show();
                                    $('#fail_login').hide();
                                } else {
                                    $('#fail_login').hide();
                                }
                                fail_counter++;
                            }, 2000);
                        }
                    }
                }
            });
        }

        $('#send_mail').click(function() {
            var email_block = $('#form_block').serialize();
            $.ajax({
                url: '<?php echo site_url('login/block_account/'); ?>',
                data: email_block,
                success: function(block_email) {
                    if(block_email == 0) {
                        $('#fail_login').show().find('#fail_message').text('No match found. Please enter correct email or username.');
                    } else {
                        $('#form_block').hide();
                        $('#fail_login').show().find('#fail_message').text('Your account has been blocked. Please contact your administrator.');
                    }
                }
            });
        });

        $('#form_login').on('submit', function(e) {
            $('#loginModal').modal('show');
        });

        $(window).on('pageshow', function() {
            if($('#loginModal').hasClass('show')) {
                $('#loginModal').modal('hide');
            }
        });
    </script>
</body>
</html>
