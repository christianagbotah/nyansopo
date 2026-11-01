<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="Lightworldtech Admin Panel" />
	<meta name="author" content="" />

	<title>Installation | LiSofts School Manager</title>
	<?php include 'styles.php'; ?>
</head>
<body class="page-body" data-url="https://www.lightworldtech.com">

<div class="page-container horizontal-menu">


	<header class="navbar navbar-fixed-top"
    style="min-height: 80px; background-color: black;">

		<div class="navbar-inner">
			<!-- logo -->
			<div class="navbar-brand">
				<a href="#">
					<img src="<?php echo base_url('uploads/school_logo.png');?>"  style="max-height:100px; margin-top: -30px;"/>
				</a>
			</div>
      <div class="navbar-brand">
        <h3 style="margin-top: 13px; margin-left: -22px;  color: #d0c8c8;">
        	LiSofts School Manager
    	</h3>
      </div>
      <div class="navbar-brand pull-right"
        style="margin-top: 13px; color: #ffffff;">
        Installation
      </div>
		</div>
	</header>
	<div class="main-content">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
          <?php include 'main/'.$page_name.'.php'; ?>
          <?php include 'footer.php'; ?>
				</div>
			</div>
		</div>
	</div>

<?php include 'scripts.php'; ?>

</body>
</html>
