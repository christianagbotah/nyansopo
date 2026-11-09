<?php
	
	/*GENERALLY CHECK FOR INTERNET CONNECTION BEFORE PROCEEDING WITH ANY REQUEST*/
    $hasInternetConnection = getInternetConnectionStatus();

	//date_default_timezone_set('UTC');
	$system_name        =	$this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
	//$system_title       =	$this->db->get_where('settings' , array('type'=>'system_title'))->row()->description;
	$text_align         =	$this->db->get_where('settings' , array('type'=>'text_align'))->row()->description;
	$account_type       =	$this->session->userdata('login_type');
	$skin_colour        =   $this->db->get_where('settings' , array('type'=>'skin_colour'))->row()->description;
	$active_sms_service =   $this->db->get_where('settings' , array('type'=>'active_sms_service'))->row()->description;
	$running_year 		=   $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
	$running_term 		=   $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;
	$running_sem 		=   $this->db->get_where('settings' , array('type'=>'running_sem'))->row()->description;
	//$mega_collapsed = 'myClass';
	

	
	?>

	<?php if($page_name == 'manage_attendance_view'): 
		$display = 'none';
	endif;
		?>

<!DOCTYPE html>
<html lang="en" dir="<?php if ($text_align == 'right-to-left') echo 'rtl';?>">
<head>



	<title><?php echo $page_title;?> | <?php echo $system_name;?></title>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="Lightworldtech School Management Software - Lightworld Technologies Ltd" />
	<meta name="author" content="Lightworldtech" />

	<?php include 'css.php'; ?>

	<?php include 'includes_top.php';?>
	<style type="text/css">
		iframe.wysihtml5-sandbox{
			height: 100px !important;
		}

		.skeleton-loader{position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column}
		.skeleton-box{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:skeleton-loading 1.5s infinite;border-radius:4px;margin:10px 0}
		@keyframes skeleton-loading{0%{background-position:200% 0}100%{background-position:-200% 0}}
		.skeleton-header{width:80%;max-width:300px;height:40px}
		.skeleton-line{width:90%;max-width:600px;height:20px}
		.skeleton-line-short{width:60%;max-width:400px;height:20px}

		#loader, #loading_txt {
			position: relative;
			top: 300px;
		}

		#loader_logo {
			position: relative;
			top: 300px;
		/**	animation-name: logo_translate;
			animation-duration: 2s;
			animation-timing-function: all;
			animation-iteration-count: infinite;**/
		}

		.collapse {
			visibility: visible !important;
		}

		.checkbox label {
			display: block !important;
		}

		/**@keyframes logo_translate {
			50% {
				position: absolute;
				transform: translate3d(20px, 20px, 20px);
			}

			100% {
				position: absolute;
				transform: translate3d(-20px, -20px, -20px);
				width: 50px;
			}
		}**/

		/**Animate the dot. in front of the wait...**/
		#dot1 {
			position: relative;

			animation-name: blink1;
			animation-duration: 2s;
			animation-iteration-count: infinite;
			animation-timing-function: all;

		}

		@keyframes blink1 {

			50% {
				opacity: 1.0;
			}

			100% {
				opacity: 0.0
			}
		}

		#dot2 {
			position: relative;

			animation-name: blink2;
			animation-duration: 2s;
			animation-iteration-count: infinite;
			animation-timing-function: all;

		}

		@keyframes blink2 {

			50% {
				opacity: 1.0;
			}

			100% {
				opacity: 0.0
			}
		}

		#dot3 {
			position: relative;

			animation-name: blink3;
			animation-duration: 2s;
			animation-iteration-count: infinite;
			animation-timing-function: all;

		}

		@keyframes blink3 {

			50% {
				opacity: 0.0;
			}

			100% {
				opacity: 1.0
			}
		}

		.select2 {
		  visibility: visible;
		}

		.modal {
			z-index: 1050 !important;
		}

		.modal-backdrop {
			z-index: 1040 !important;
		}
	</style>

	<script type="text/javascript">
		// Service worker removed - using local WAMP + cloud sync approach instead
	</script>

</head>
<body class="page-body <?php if ($skin_colour != '') echo 'skin-' . $skin_colour;?>" oncontextmenu="return true" >

	<!-- Sync Status Bar removed - access via Sync Dashboard instead -->

	<script type="text/javascript">

		
		$(function() {

			//$('.select2').select2({

				//theme: 'classic',
			//})

			//$('#modal_preloader').modal('show');
			//$('#modal_confirm .modal-content').css('margin-top', '300px');
			/**$('#modal_confirm .modal-header').html('<strong style="color: #fff;">Please Wait...</strong>');
			$('#modal_confirm .modal-body').html('<div id="preloader" style="width: 100%; min-height: 1020px; background-color: #fff; text-align: center; z-index: 99999; position: absolute;"><img id="loader_logo" src="<?php echo base_url();?>assets/images/lightworldtech.png" width="100px"><img id="loader" src="<?php echo base_url();?>assets/images/validate.gif" width="64px"><p id="loading_txt" style="padding-top: 15px; font-weight: bold;">Loading, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span></p></div>');
			$('#main_div').css('cursor', 'wait');
			$('#modal_confirm .modal-footer').html('');**/

			
			
		})
		
	</script>

	<div id="skeleton-loader" class="skeleton-loader">
		<div class="skeleton-box skeleton-header"></div>
		<div class="skeleton-box skeleton-line"></div>
		<div class="skeleton-box skeleton-line"></div>
		<div class="skeleton-box skeleton-line-short"></div>
	</div>

	<div id="main_div" class="page-container  <?php if ($text_align == 'right-to-left') echo 'right-sidebar';?>
		<?php if($page_name == 'attendance_report_view' || $page_name == 'marks' || $page_name == 'marks_raw_score'  || $page_name == 'student_payment' || $page_name == 'marks_manage' || $page_name == 'marks_manage_view' || $page_name == 'marks_manage_view_creche' || $page_name == 'fct_owe_list' || $page_name == 'exam_marks_sms' || $page_name == 'students_daily_attendance' || $page_name == 'portfolio_assessment_manage_view' || $page_name == 'portfolio_assessment_manage' || $page_name == 'payslip_list' || $page_name == 'transport' || $page_name == 'transport_enhanced') echo 'sidebar-collapsed';?>" >

		<?php include $account_type.'/navigation.php';?>

		<div class="main-content overflow-y-scroll w-screen max-w-screen min-w-screen h-screen min-h-screen max-h-screen p-0">

			<div class="sticky top-0 z-10">
				<?php include 'header.php';?>
			</div>

	     <!--main body/pages-->
	     <div id= "main_page" class="p-10 pt-24">
	     	<?php include 'include_main.php'; ?>
	     </div>
	     

	    </div>
		<?php //include 'chat.php';?>
	</div>
		<?php //include 'footer.php';?>

		 <!-- (Preloader Ajax Modal)-->
		
	    <?php include 'modal.php';?>

	    <script type="text/javascript">
	    	$(document).ready(function() {

	    		// Smart auto-update: only runs when year changes
	    		check_and_update_student_code_format();

	    		$.ajax({
	    			url: '<?php echo site_url('admin/get_raw_score_status'); ?>',
	    			success: function(response) {
	    				if(response == 'Yes') {
	    					$('#raw_nav').css('display', 'block');
	    				}else{
	    					$('#raw_nav').css('display', 'none');
	    				}
	    			}
	    		});
	    	});


	    </script>
	     <?php //include 'js.php'; ?>
	    <?php include 'includes_bottom.php';?>

	    <script type="text/javascript">
	    	$(document).ready(function() {
	    		$(window).on("load", function() {
	    			$('#skeleton-loader').fadeOut(300);
	    			$('#modal_preloader').fadeOut('400', function() {
	    				$('.modal-backdrop').removeClass('modal-backdrop');
	    				$('#modal_preloader').modal('hidden');
	    				$('#modal_preloader').remove();
	    				$('#modal_preloader').css('display', 'none');
	    			});
	    			$('.modal-backdrop').remove();
	    		});
	    	});

	    	window.onload = function() {
	    		$('#skeleton-loader').fadeOut(300);
	    		$('#modal_preloader').fadeOut('400', function() {
	    			$('.modal-backdrop').removeClass('modal-backdrop');
	    			$('#modal_preloader').modal('hidden');
	    			$('#modal_preloader').remove();
	    			$('#modal_preloader').css('display', 'none');
	    		});
	    		$('.modal-backdrop').remove();
	    	};


	    	// Scroll handler for header sizing
	    	$(document).ready(function() {
	    		$('.main-content').scroll(function(ev) {
	    			if($(this).scrollTop() > 7) {
	    				$('.sidebar-mobile-menu a i').removeClass('text-5xl');
	    				$('.sidebar-mobile-menu a i').addClass('text-2xl');
	    				$('.logo a img').css('max-height', '40px');
	    			} else {
	    				$('.sidebar-mobile-menu a i').removeClass('text-2xl');
	    				$('.sidebar-mobile-menu a i').addClass('text-5xl');
	    				$('.logo a img').css('max-height', '80px');
	    			}
	    		});
	    	});

	    	function check_and_update_student_code_format() {
	    		var currentYear = new Date().getFullYear();
	    		var lastUpdatedYear = localStorage.getItem('student_code_format_year');
	    		
	    		// Only update if year has changed or never been set
	    		if(lastUpdatedYear != currentYear) {
	    			$.ajax({
	    				url: '<?php echo site_url('admin/update_student_code_format') ?>',
	    				type: 'POST',
	    				dataType: 'html',
	    			})
	    			.done(function(feedback) {
	    				localStorage.setItem('student_code_format_year', currentYear);
	    				console.log('Student code format updated for year ' + currentYear);
	    			})
	    			.fail(function() {
	    				console.log('Failed to update student code format');
	    			});
	    		}
	    	}
	    </script>

	   
</body>
</html>

