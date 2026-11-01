<!doctype html>
<html>
    <head>

        <link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive_table.css');?>">

    </head>
    <body>
<?php 
      $user_name = urldecode($user_name);
      if($logout_id == 1) { ?>
        <script type="text/javascript">
          var user_name = '<?php echo $user_name; ?>';

            $(function() {
                


              //redirect user to the login page after the login here button is clicked
              /**$(document).on('hidden.bs.modal', '#modal_idle_user', function() {
                window.location.href = '<?php echo site_url('login/logout'); ?>';
              });**/
            });

        </script>
     <?php   
      }
    ?>

    <?php include 'modal.php';?>

    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
  </body>
</html>

<script type="text/javascript">
  $(function() {
    showAjaxModal_idle_user('<?php echo site_url('modal/popup_idle_user/idle_user_logged_out/'); ?>' + 'Chris');
  });
</script>