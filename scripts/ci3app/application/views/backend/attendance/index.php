<?php
$login_type = $this->session->userdata('login_type');
$account_type = $login_type;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_phrase('attendance_enterprise'); ?> | <?php echo get_settings('system_name'); ?></title>
    
    <link rel="shortcut icon" href="<?php echo base_url(); ?>uploads/favicon.png">
    <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/style.css" rel="stylesheet">
    
    <script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
</head>
<body class="page-body">
    <div class="page-container">
        <?php include APPPATH.'views/backend/'.$login_type.'/navigation.php'; ?>
        
        <div class="main-content">
            <?php include APPPATH.'views/backend/'.$login_type.'/header.php'; ?>
            
            <div id="main_page">
                <?php $this->load->view('backend/attendance/dashboard'); ?>
            </div>
            
            <?php include APPPATH.'views/backend/footer.php'; ?>
        </div>
    </div>
    
    <?php include APPPATH.'views/backend/modal.php'; ?>
</body>
</html>
