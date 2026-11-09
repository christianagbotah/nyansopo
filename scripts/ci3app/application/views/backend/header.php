<?php 
  $this->session->unset_userdata('flash_message');
  $this->session->unset_userdata('error_message');

    if($this->session->userdata('login_type') == '') {
        redirect(site_url('login'), 'refresh');
    }
	//Global variables start
	$current_user     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');
	$current_user_id  = $this->session->userdata('login_user_id');

    $boarding_system = $this->db->get_where('settings' , array('type'=>'boarding_system'))->row()->description;
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $receipt_style = $this->db->get_where('settings' , array('type'=>'receipt_style'))->row()->description;
    $active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;

    /*Global variables ending*/

	$term_notice = '';
    $sem_notice = '';



  /*if($running_sem == 2 && $account_type == 'admin' || $running_sem == 2 && $account_type == 'teacher') {
    $sem_notice = 'Promotion Semester for JHS!';
  }*/
	if($running_term == 3 && $account_type == 'admin' || $running_term == 3 && $account_type == 'teacher') {
		$term_notice = 'Promotion term!';
	}

 ?>
 <style type="text/css">
 	#notice_term {
 		position: absolute;
 		left: 5px;
 		top: -15px;
 		color: red;
 		font-weight: bold;
 		animation-name: blink;
 		animation-duration: 1s;
 		animation-iteration-count: infinite;
 		animation-timing-function: all;
 	}

    #notice_sem {
        position: absolute;
        left: 200px;
        top: -15px;
        color: red;
        font-weight: bold;
        animation-name: blink2;
        animation-duration: 1s;
        animation-iteration-count: infinite;
        animation-timing-function: all;
    }


  #att_alert  h3{
    font-weight: bold;
    animation-name: blink;
    animation-duration: 1s;
    animation-iteration-count: infinite;
    animation-timing-function: all;
    cursor: pointer;
  }

  #students_holder .names_holder {
    max-height: 200px !important;
    overflow-y: scroll !important;
  }

 	@keyframes blink {

 		50%{
 			opacity: 0;
 			color: #ffffff;
 		}
 		100%{
 			opacity: 1;

 		}
 	}

    @keyframes blink2 {

        50%{
            opacity: 1;
            
        }
        100%{
            opacity: 0;
            color: #ffffff;

        }
    }

 	#mega_m .dropdown-menu {
 		top: 0px !important;
 	}

  .coming_s, .coming_s a {
    opacity: 0.6;
    cursor: not-allowed;
  }


  /*datatable buttons customized styles*/
  .dt-button {
    height: 4rem !important;
    font-weight: bold;
    font-size: 12px !important;
    color: #ffffff !important;
  }

  .dt-button:hover {
    opacity: 0.5;
  }


  div.dt-buttons > .buttons-colvis {
    background: #4a5565 !important;
  }

  div.dt-buttons > .buttons-excel {
    background: #008236 !important;
  }

  div.dt-buttons > .buttons-copy {
    background: #0069a8 !important;
  }

  div.dt-buttons > .buttons-pdf {
    background: #ef5350  !important;
  }

  div.dt-button-collection .dt-button {
    color: #000000 !important;
  }

  div.dt-container .dt-search input {

    height: 4rem !important;
    font-weight: bold;
    font-size: 12px !important;
    
  }

  .select2-container-multi {

    margin-bottom: 25px !important;
  }

  .dt-start .dt-length  select {
    height: 4rem !important;
    width: 4.5rem !important;
  }

  .dt-start .dt-length  label {
    display: none;
  }

 </style>

<?php
// Get theme colors from database
$theme_primary = $this->db->get_where('settings', array('type' => 'theme_primary'))->row()->description ?? '#667eea';
$theme_secondary = $this->db->get_where('settings', array('type' => 'theme_secondary'))->row()->description ?? '#764ba2';
$theme_accent = $this->db->get_where('settings', array('type' => 'theme_accent'))->row()->description ?? '#f093fb';
?>
<style>
/* Dynamic Theme Colors */
.panel-primary > .panel-heading,
.btn-primary,
.modern-modal-header,
.notification-header,
.settings-header,
#top,
.sidebar-menu {
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%) !important;
}

.btn-primary:hover,
.notification-icon,
#main-menu li.active > a {
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%) !important;
}

#main-menu li a:hover {
    background: rgba(<?php echo hexdec(substr($theme_accent, 1, 2)); ?>, <?php echo hexdec(substr($theme_accent, 3, 2)); ?>, <?php echo hexdec(substr($theme_accent, 5, 2)); ?>, 0.2) !important;
}

#main-menu li > ul {
    background: rgba(255, 255, 255, 0.08) !important;
}

#main-menu ul ul {
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

#main-menu li a {
    border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
}

.sidebar-menu .logo-env {
    background: linear-gradient(135deg, 
        rgb(<?php echo max(0, hexdec(substr($theme_primary, 1, 2)) - 40); ?>, <?php echo max(0, hexdec(substr($theme_primary, 3, 2)) - 40); ?>, <?php echo max(0, hexdec(substr($theme_primary, 5, 2)) - 40); ?>) 0%, 
        rgb(<?php echo max(0, hexdec(substr($theme_secondary, 1, 2)) - 40); ?>, <?php echo max(0, hexdec(substr($theme_secondary, 3, 2)) - 40); ?>, <?php echo max(0, hexdec(substr($theme_secondary, 5, 2)) - 40); ?>) 100%) !important;
}

.text-primary,
a.text-primary:hover {
    color: <?php echo $theme_primary; ?> !important;
}

.bg-primary {
    background-color: <?php echo $theme_primary; ?> !important;
}

.border-primary {
    border-color: <?php echo $theme_primary; ?> !important;
}
</style>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/offline-sync.css">
<!-- Offline detector disabled for performance optimization -->
<!-- <script src="<?php echo base_url(); ?>assets/js/simple-offline-detector.js"></script> -->

<!-- Metro Toggle Button Styles - Always available -->
<style>
.metro-toggle-btn { 
    width: 25px; 
    height: 50px; 
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05)); 
    border: 2px solid rgba(255, 255, 255, 0.3); 
    border-top-left-radius: 8px; 
    border-bottom-left-radius: 8px; 
    border-right: none; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-size: 14px; 
    transition: all 0.3s ease; 
    backdrop-filter: blur(10px); 
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2); 
}
.metro-toggle-btn:hover { 
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.1)); 
    width: 28px; 
}
.metro-toggle-btn i { 
    transition: all 0.3s ease; 
}
</style>

<!-- Mobile Sidebar Backdrop Styles - iOS Fix -->
<style>
/* Mobile sidebar backdrop for iOS compatibility */
.sidebar-mobile-backdrop {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: rgba(0, 0, 0, 0.5);
	z-index: 999; /* Below sidebar but above content */
	display: block;
	-webkit-tap-highlight-color: transparent; /* Remove tap highlight on iOS */
	touch-action: none; /* Prevent scrolling on iOS */
}

/* Prevent body scroll when mobile menu is open */
body.sidebar-mobile-open {
	overflow: hidden;
	position: fixed;
	width: 100%;
	height: 100%;
}

/* Ensure sidebar is above backdrop */
.sidebar-menu {
	z-index: 1000 !important;
}

.page-container .sidebar-menu .main-menu {
	z-index: 1000 !important;
}

/* iOS-specific touch improvements */
@supports (-webkit-touch-callout: none) {
	/* iOS-only styles */
	.sidebar-mobile-backdrop {
		-webkit-transform: translateZ(0); /* Force hardware acceleration */
		transform: translateZ(0);
	}
	
	.sidebar-menu .main-menu {
		-webkit-overflow-scrolling: touch;
		-webkit-transform: translateZ(0);
		transform: translateZ(0);
	}
}

/* Collapsed sidebar hover submenu styles */
.page-container.sidebar-collapsed #main-menu > li > ul {
	display: none;
}

.page-container.sidebar-collapsed #main-menu > li > ul.hover-submenu-visible {
	display: block !important;
	position: fixed !important;
	left: 70px !important;
	min-width: 200px;
	max-width: 280px;
	background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
	border-radius: 8px;
	box-shadow: 0 8px 32px rgba(0,0,0,0.4);
	border: 1px solid rgba(255,255,255,0.1);
	z-index: 1001 !important;
	padding: 8px 0;
	margin: 0 !important;
	list-style: none;
}

.page-container.sidebar-collapsed #main-menu > li > ul.hover-submenu-visible li {
	position: relative;
	display: block !important;
	width: 100%;
	float: none !important;
}

.page-container.sidebar-collapsed #main-menu > li > ul.hover-submenu-visible li a {
	display: block !important;
	padding: 10px 20px !important;
	color: #e2e8f0 !important;
	text-decoration: none !important;
	transition: all 0.3s ease;
	white-space: nowrap;
}

.page-container.sidebar-collapsed #main-menu > li > ul.hover-submenu-visible li a:hover {
	background: rgba(255,255,255,0.1);
	color: #fff !important;
	padding-left: 25px !important;
}

.page-container.sidebar-collapsed #main-menu > li > ul.hover-submenu-visible li a i {
	margin-right: 10px;
	width: 20px;
	text-align: center;
}
</style>

<div class="flex flex-col w-full max-w-full py-3 md:py-5 fixed top-0 left-0 right-0 px-4 md:px-10 border-b-4 border-red-500" style="background-color: #0a0069; z-index: 100;" id="top">
	<div class="col-md-12 col-sm-12 col-xs-12 clearfix" style="text-align:center; position: relative;">
		<h2 id="sys_name"  style="font-weight:200; margin:0px; color: #fff !important;"><small><span id="notice_term" class="pull-left text-lg font-bold mt-4"><?php echo $term_notice; ?></span></small> <small><span id="notice_sem2" class="pull-left"><?php echo $sem_notice; ?></span></small> <span class="font-bold md:font-extrabold text-gray-300 text-base sm:text-lg md:text-2xl lg:text-4xl"><?php echo $system_name;?></span> </h2>
		
		<?php 
	    // Show metro toggle button for all admin pages (not just dashboard)
	    if($account_type == 'admin' && $admin_level < 4):
	    ?>
		<!-- Metro Menu Toggle Button -->
		<button class="metro-toggle-btn" id="metro_toggle" style="position: fixed; right: 0; top: 30px; z-index: 999996;">
			<i class="fa fa-th-large"></i>
		</button>
		<?php endif; ?>
    </div>

    <?php 
    // Include the metro menu on all admin pages (not just dashboard)
    if($account_type == 'admin' && $admin_level < 4) {
        $data = array(
            'account_type' => $account_type,
            'admin_level' => $admin_level
        );
        $this->load->view('backend/admin/metro_menu', $data);
    }
    ?>  

	<!-- Raw Links -->
	<div class="flex flex-wrap gap-2 md:gap-4 w-full max-w-full col-md-12 col-sm-12 col-xs-12 clearfix" style="margin-top: 20px;">

		<!-- Year and Term in one row -->
		<div class="flex gap-2 flex-shrink-0">
	        <ul class="list-inline links-list">
	        	<div id="session_static" style="background-color: #21a9e1;" class="p-2 rounded-lg">
		           <li>
		           		<h4 class="m-0">
		           			<?php 
		           			$admin_level = 0;
		           			if($account_type == 'admin') {
		           				$admin_level = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->level;
		           			}
		           			if($account_type == 'admin' && $admin_level < 4): ?>
		           			<a href="#" style="color: #eadfdf !important;" onclick="get_session_changer()" class="font-bold text-base md:text-2xl">
		           				Year : <?php echo $running_year.' ';?><i class="entypo-down-dir"></i>
		           			</a>
		           			<?php else: ?>
		           			<span style="color: #eadfdf !important;" class="font-bold text-base md:text-2xl">
		           				Year : <?php echo $running_year; ?>
		           			</span>
		           			<?php endif; ?>
		           		</h4>
		           </li>
	           </div>
	        </ul>
	        
	        <ul class="list-inline links-list">
	        	<div id="term_static" style="background-color: #21a9e1;" class="p-2 rounded-lg">
		           <li>
		           		<h4 class="m-0">
		           			<?php if($account_type == 'admin' && $admin_level < 4): ?>
		           			<a href="#" style="color: #eadfdf !important;" onclick="get_term_changer()" class="font-bold text-base md:text-2xl">
		           				Term : <?php echo $running_term.' ';?><i class="entypo-down-dir"></i>
		           			</a>
		           			<?php else: ?>
		           			<span style="color: #eadfdf !important;" class="font-bold text-base md:text-2xl">
		           				Term : <?php echo $running_term; ?>
		           			</span>
		           			<?php endif; ?>
		           		</h4>
		           </li>
	           </div>
	        </ul>
	    </div>
        <!--
        &nbsp; &nbsp;
        <ul class="list-inline links-list pull-left">

          <div id="sem_static" style="background-color: #21a9e1; padding: 4px; border-radius: 30px; margin-left: 10px;">
             <li>
                <h4>
                  <a href="#" style="color: #eadfdf !important;"
                    <?php //if($account_type == 'admin'):?>
                    onclick="get_sem_changer()"
                  <?php //endif;?>>
                    Sem : <?php //echo $running_sem.' ';?><i class="entypo-down-dir"></i>
                  </a>
                </h4>
             </li>
           </div>
        </ul>-->
		
		<!-- Mobile Hamburger Menu -->
		<div class="sidebar-menu  visible:xs visible:sm md:hidden">
			<header class="border-b border-white/10 block md:block">
				
				<div class="sidebar-mobile-menu mt-5">
					<a href="#" class="with-animation">
						<i class="fa fa-bars text-4xl text-white"></i>
					</a>
				</div>
			</header>
		</div>
		<ul class="list-inline links-list flex flex-wrap items-center justify-end gap-2 md:gap-4 ml-auto" style="margin-bottom: 15px;">
						
		  <?php 
		  if($account_type == 'admin') {
			  $admin_level = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->level;
			  if($admin_level < 4): // Not cashier
		  ?>
		  
		  <!-- Drive List Button -->
		  <li>
			<button onclick="window.open('<?php echo site_url('admin/drive_list_page'); ?>', '_blank')" class="px-3 md:px-6 py-2 md:py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm md:text-base">
				<i class="fa fa-exclamation-triangle text-base md:text-lg"></i>
				<span class="hidden md:inline">Drive List</span>
			</button>
		  </li>
		  
		  <!-- Billing Button -->
		  <li>
			<button onclick="navigation('<?php echo site_url('admin/student_invoice'); ?>')" class="px-3 md:px-6 py-2 md:py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm md:text-base">
				<i class="fa fa-file-invoice text-base md:text-lg"></i>
				<span class="hidden md:inline">Billing</span>
			</button>
		  </li>
		  
		  <!-- Take Payment Button -->
		  <li>
			<button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/0'); ?>', 'take_payment')" class="px-3 md:px-6 py-2 md:py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm md:text-base">
				<i class="fa fa-money-bill-wave text-base md:text-lg"></i>
				<span class="hidden md:inline">Take Payment</span>
			</button>
		  </li>
		  <?php endif; }?>

		  <!-- Notification Bell - Only for Super Admin -->
		  <?php 
		  $show_notifications = true;
		  if($account_type == 'admin') {
			  $admin_level = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->level;
			  if($admin_level == 4) { // Cashier
				  $show_notifications = false;
			  }
		  }
		  if($show_notifications): 
		  ?>
		  <li class="dropdown" id="notification_bell" style="display: none;">
			<a href="#" class="notification-bell-trigger" data-toggle="dropdown" aria-label="Notifications">
				<div class="notification-icon-wrapper">
					<i class="fa fa-bell"></i>
					<span id="notification_badge" class="notification-badge">0</span>
				</div>
			</a>
			<ul class="dropdown-menu notification-dropdown">
				<li class="notification-header">
					<h4>Notifications</h4>
					<span id="notification_count" class="notification-count-text">0 new</span>
				</li>
				<li class="notification-body">
					<div id="notification_list" class="notification-list">
						<div class="notification-empty">
							<i class="fa fa-bell-slash"></i>
							<p>No notifications</p>
						</div>
					</div>
				</li>
				<li class="notification-footer">
					<div class="notification-actions">
						<button onclick="markAllAsRead()" class="btn-mark-all" id="btn_mark_all">
							<i class="fa fa-check-double"></i> Mark All as Read
						</button>
						<button onclick="clearAllNotifications()" class="btn-clear-all" id="btn_clear_all">
							<i class="fa fa-trash-alt"></i> Clear All
						</button>
					</div>
				</li>
			</ul>
		  </li>
		  <?php endif; ?>

		  <!-- User Profile Dropdown -->
		  <li class="dropdown" id="info">
			<?php
				$name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
				$initials = strtoupper(substr($name, 0, 1));
			?>
			<a href="#" class="dropdown-toggle flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition-all duration-300" data-toggle="dropdown" id="user_profile_btn" style="text-decoration: none;">
				<!-- Avatar Column -->
				<div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold shadow-lg text-lg flex-shrink-0">
					<?=$initials;?>
				</div>
				<!-- Name and Role Column - Hidden on Mobile -->
				<div class="hidden md:flex flex-col" style="line-height: 1.3;">
					<span class="text-white text-sm opacity-80" style="white-space: nowrap;"><?=ucfirst($account_type);?></span>
					<span class="text-white font-semibold text-sm" style="white-space: nowrap;"><?=$name; ?></span>
				</div>
				<!-- Dropdown Arrow Column -->
				<i class="fa fa-chevron-down text-white text-xs"></i>
			</a>

				<?php if ($account_type != 'parent'):?>
				<ul class="dropdown-menu modern-dropdown pull-right" style="min-width: 250px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: none; padding: 8px; right: 0; left: auto;">
					<li class="dropdown-header" style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
						<div class="flex items-center gap-3">
							<div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg">
								<?=$initials;?>
							</div>
							<div>
								<div class="font-bold text-gray-800"><?=$name;?></div>
								<div class="text-sm text-gray-500"><?=ucfirst($account_type);?></div>
							</div>
						</div>
					</li>
					<li style="margin: 4px 0;">
						<a href="<?php echo site_url($account_type . '/manage_profile');?>" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;" class="dropdown-item-modern">
                        	<i class="fa fa-user-circle" style="width: 20px;"></i>
							<span>Edit Profile</span>
						</a>
					</li>
					<li style="margin: 4px 0;">
						<a href="<?php echo site_url($account_type . '/manage_profile');?>" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;" class="dropdown-item-modern">
                        	<i class="fa fa-key" style="width: 20px;"></i>
							<span>Change Password</span>
						</a>
					</li>
					<?php
						if ($account_type == 'admin') {
							$admin_data = $this->db->get_where('admin', array('name' => $name))->row();
							$admin_level = $admin_data ? $admin_data->level : 0;
						} else {
							$admin_level = 0;
						}
					?>
					<?php if ($account_type == 'admin' && $admin_level <= 3):?>
					<li style="margin: 4px 0;">
						<a href="#" onclick="navigation('<?php echo site_url('admin/notification_settings'); ?>')" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;" class="dropdown-item-modern">
                        	<i class="fa fa-cog" style="width: 20px;"></i>
							<span>Notification Settings</span>
						</a>
					</li>
					<?php endif; ?>
					<?php if ($account_type == 'admin' && $admin_level == 1):?>
					<li style="margin: 4px 0;">
						<a href="javascript:;" onclick="$.ajax({url: '<?php echo site_url('modal/popup/modal_admin_add/');?>', type: 'GET', success: function(response) { $('#modal_ajax').html(response); $('#modal_ajax').modal('show', {backdrop: 'static'}); }});" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;" class="dropdown-item-modern">
                        	<i class="fa fa-user-plus" style="width: 20px;"></i>
							<span>Add New Admin</span>
						</a>
					</li>
					<?php endif; ?>
					<li style="margin: 8px 0 4px 0; border-top: 1px solid #e5e7eb; padding-top: 8px;">
						<a href="<?php echo site_url('login/logout');?>" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s; color: #ef4444;" class="dropdown-item-modern logout-item">
							<i class="fa fa-sign-out-alt" style="width: 20px;"></i>
							<span>Log Out</span>
						</a>
					</li>
				</ul>
				<?php endif;?>
				<?php if ($account_type == 'parent'):?>
				<ul class="dropdown-menu modern-dropdown pull-right" style="min-width: 250px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: none; padding: 8px; right: 0; left: auto;">
					<li class="dropdown-header" style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
						<div class="flex items-center gap-3">
							<div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg">
								<?=$initials;?>
							</div>
							<div>
								<div class="font-bold text-gray-800"><?=$name;?></div>
								<div class="text-xs text-gray-500">Parent</div>
							</div>
						</div>
					</li>
					<li style="margin: 4px 0;">
						<a href="<?php echo site_url('parents/manage_profile');?>" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;" class="dropdown-item-modern">
                        	<i class="fa fa-user-circle" style="width: 20px;"></i>
							<span>Edit Profile</span>
						</a>
					</li>
					<li style="margin: 4px 0;">
						<a href="<?php echo site_url('parents/manage_profile');?>" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;" class="dropdown-item-modern">
                        	<i class="fa fa-key" style="width: 20px;"></i>
							<span>Change Password</span>
						</a>
					</li>
					<li style="margin: 8px 0 4px 0; border-top: 1px solid #e5e7eb; padding-top: 8px;">
						<a href="<?php echo site_url('login/logout');?>" style="padding: 10px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; transition: all 0.2s; color: #ef4444;" class="dropdown-item-modern logout-item">
							<i class="fa fa-sign-out-alt" style="width: 20px;"></i>
							<span>Log Out</span>
						</a>
					</li>
				</ul>
				<?php endif;?>
			</li>
		</ul>
		
	</div>
    <div class="flex gap-10 items-center hidden">
    	<div class="">
            <em id="lgw" style="color: #d97f02; font-weight: bold; font-family: serif; font-size: 17px">LiSofts School Manager</em>
        </div>

        <div class="row" id="msgs">
        <!--message alert menu-->
            <ul style="list-style: none; display: inline-flex;" class="" id="note_alert">
                <!--general message alert menu-->
                <li class="dropdown dropdown-list">
                  <a href="#" data-toggle="dropdown" data-play="bounceIn" class="dropdown-toggle">
                     <em class="fa fa-bell" id="bell"></em>
                     <div class="badge badge-danger" id="badge2" style="background-color: red;"></div>
                  </a>
                  <!-- START Dropdown menu-->
                  <ul class="dropdown-menu">
                     <li>
                        <!-- START list group-->
                        <div class="list-group">
                           <!-- list item-->
                           <div class="list-group-item" style="background-color: #dedede;">
                                <div class="media">
                                 <div class="pull-left">
                                    <em class="fa fa-envelope-o fa-2x text-success"></em>
                                 </div>
                                 <div class="media-body clearfix">
                                    <div class="media-heading"><h4>Unread messages</h4></div>
                                    <p class="m0">
                                       <small ></small>
                                    </p>
                                 </div>
                              </div>
                           </div>
                              
                           <!-- last list item -->
                           <div id="message_list_holder">
                              
                           </div>
                        </div>
                        <!-- END list group-->
                     </li>
                  </ul>
                  <!-- END Dropdown menu-->
               </li>
               <?php  if($account_type == 'admin' || $account_type == 'accountant' || $account_type == 'parent' || $account_type == 'student'): ?>
               <!--mobile money message alert menu-->
                <li class="dropdown dropdown-list ml-10">
                  <a href="#" data-toggle="dropdown" data-play="bounceIn" class="dropdown-toggle">
                     <em class="glyphicon glyphicon-credit-card" id="momo"></em>
                     <div class="badge badge-danger" id="badge1" style="background-color: red;"></div>
                  </a>
                  <!-- START Dropdown menu-->
                  <ul class="dropdown-menu">
                     <li>
                        <!-- START list group-->
                        <div class="list-group">
                           <!-- list item-->
                           <div class="list-group-item" style="background-color: #dedede;">
                                <div class="media">
                                 <div class="pull-left">
                                    <em class="fa fa-envelope-o fa-2x text-success"></em>
                                 </div>
                                 <div class="media-body clearfix">
                                    <div class="media-heading"><h4>Unread messages</h4></div>
                                    <p class="m0">
                                       <small ></small>
                                    </p>
                                 </div>
                              </div>
                           </div>
                              
                           <!-- last list item -->
                           <div id="momo_message_list_holder">
                              
                           </div>
                        </div>
                        <!-- END list group-->
                     </li>
                  </ul>
                  <!-- END Dropdown menu-->
               </li>
           <?php endif; ?>
            </ul>
            
       </div>
    </div>
</div>
<style type="text/css">
	#user_profile_btn {
		color: #ffffff !important;
		cursor: pointer;
	}

	.glyphicon-dashboard {
		color: #ffffff;
		font-size: 20px;
	}

	#user_profile_btn:hover {
		background: rgba(255, 255, 255, 0.15) !important;
	}

	#info.dropdown.open .dropdown-menu {
		display: block !important;
	}

	.dropdown-item-modern {
		text-decoration: none !important;
		color: #374151 !important;
	}

	.dropdown-item-modern:hover {
		background: #f3f4f6 !important;
		color: #1f2937 !important;
		text-decoration: none !important;
	}

	.dropdown-item-modern.logout-item {
		color: #ef4444 !important;
	}

	.dropdown-item-modern.logout-item:hover {
		background: #fee2e2 !important;
		color: #dc2626 !important;
	}

	.modern-dropdown {
		animation: slideDown 0.3s ease-out;
		margin-top: 8px !important;
	}
	
	.sidebar-mobile-menu-btn {
		border: none !important;
		outline: none !important;
		background: transparent !important;
		box-shadow: none !important;
	}
	
	.sidebar-mobile-menu-btn:hover,
	.sidebar-mobile-menu-btn:focus,
	.sidebar-mobile-menu-btn:active {
		background: transparent !important;
		outline: none !important;
	}

	@keyframes slideDown {
		from {
			opacity: 0;
			transform: translateY(-10px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@media (max-width: 640px) {
		.hidden-xs {
			display: none !important;
		}
		.list-inline.links-list {
			width: 100%;
			justify-content: center;
		}
		.list-inline.links-list li button {
			min-width: auto;
		}
	}

	#msgs {
			right: 400px !important;
		}

	.list-inline.links-list {
		margin: 0;
		padding: 0;
	}
	@media only screen and (max-width: 1255px) {
		#note_alert{
			margin-left: 0px !important;
		}

		#sys_name {
			font-size: 18px !important;
		}
	}

	@media only screen and (max-width: 625px) {
		#msgs{
			right: 290px !important;
		}

		#lgw {
			font-size: 13px !important;
		}
	}

	@media only screen and (max-width: 768px) {
		#sys_name {
			font-size: 18px !important;
		}
	}

	@media only screen and (max-width: 480px) {
		#sys_name {
			font-size: 16px !important;
		}
	}

	@media only screen and (max-width: 398px) {
		#lgw {
			font-size: 11px !important;
		}

		ul li .info {
			font-size: 10px !important;
		}

		#msgs {
			right: 270px !important;
			top: 100px !important;
		}

		#sys_name {
			font-size: 14px !important;
		}
	}
	/* Modern Notification Bell Styles - Fixed Version */
	#notification_bell {
		position: relative;
	}

	.notification-bell-trigger {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 8px 12px;
		border-radius: 10px;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		text-decoration: none !important;
		cursor: pointer;
	}

	.notification-bell-trigger:hover {
		background: rgba(255, 255, 255, 0.1);
		transform: translateY(-2px);
	}

	.notification-icon-wrapper {
		position: relative;
		display: inline-block;
	}

	.notification-icon-wrapper .fa-bell {
		font-size: 22px;
		color: #ffffff;
		transition: all 0.3s ease;
	}

	.notification-bell-trigger:hover .fa-bell {
		animation: bellRing 0.5s ease-in-out;
	}

	@keyframes bellRing {
		0%, 100% { transform: rotate(0deg); }
		10%, 30% { transform: rotate(-10deg); }
		20%, 40% { transform: rotate(10deg); }
		50% { transform: rotate(0deg); }
	}

	.notification-badge {
		position: absolute;
		top: -6px;
		right: -6px;
		background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
		color: #ffffff;
		font-size: 10px;
		font-weight: 700;
		min-width: 18px;
		height: 18px;
		display: none;
		align-items: center;
		justify-content: center;
		border-radius: 9px;
		padding: 0 5px;
		box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
		animation: badgePulse 2s infinite;
	}

	.notification-badge.active {
		display: flex;
	}

	@keyframes badgePulse {
		0%, 100% { transform: scale(1); }
		50% { transform: scale(1.1); }
	}

	.notification-dropdown {
		position: absolute !important;
		top: 100% !important;
		right: 0 !important;
		left: auto !important;
		width: 400px;
		max-width: calc(100vw - 32px);
		background: #ffffff;
		border-radius: 16px;
		box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
		border: none;
		padding: 0;
		margin-top: 8px !important;
		animation: dropdownSlide 0.25s cubic-bezier(0.4, 0, 0.2, 1);
		overflow: hidden;
		z-index: 1060 !important;
		transform-origin: top right;
		display: none;
		max-height: calc(100vh - 100px);
	}

	#notification_bell.open .notification-dropdown {
		display: flex !important;
		flex-direction: column;
	}

	@keyframes dropdownSlide {
		from {
			opacity: 0;
			transform: translateY(-10px) scale(0.95);
		}
		to {
			opacity: 1;
			transform: translateY(0) scale(1);
		}
	}

	.notification-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 18px 20px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border-bottom: none;
		flex-shrink: 0;
	}

	.notification-header h4 {
		margin: 0;
		font-size: 17px;
		font-weight: 700;
		color: #ffffff;
		letter-spacing: 0.3px;
	}

	.notification-count-text {
		font-size: 12px;
		color: rgba(255, 255, 255, 0.95);
		background: rgba(255, 255, 255, 0.2);
		padding: 5px 12px;
		border-radius: 12px;
		font-weight: 600;
		backdrop-filter: blur(10px);
	}

	.notification-body {
		padding: 0;
		margin: 0;
		flex: 1;
		overflow: hidden;
		display: flex;
		flex-direction: column;
	}

	.notification-list {
		flex: 1;
		overflow-y: auto;
		overflow-x: hidden;
		scrollbar-width: thin;
		scrollbar-color: #cbd5e1 #f1f5f9;
	}

	.notification-list::-webkit-scrollbar {
		width: 6px;
	}

	.notification-list::-webkit-scrollbar-track {
		background: #f1f5f9;
	}

	.notification-list::-webkit-scrollbar-thumb {
		background: #cbd5e1;
		border-radius: 3px;
	}

	.notification-list::-webkit-scrollbar-thumb:hover {
		background: #94a3b8;
	}

	.notification-item {
		display: flex;
		align-items: flex-start;
		gap: 12px;
		padding: 14px 20px;
		border-bottom: 1px solid #f1f5f9;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		cursor: pointer;
		position: relative;
		color: #1e293b !important;
	}

	.notification-item:hover {
		background: rgba(102, 126, 234, 0.02);
		transform: translateX(-3px);
		color: #0f172a !important;
		box-shadow: 0 1px 3px rgba(102, 126, 234, 0.05);
	}

	.notification-item:hover .notification-icon {
		background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
		transform: scale(1.05);
		box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
	}

	.notification-item:hover .notification-title {
		color: #667eea !important;
		font-weight: 700;
	}

	.notification-item:hover .notification-message {
		color: #1e293b !important;
		font-weight: 500;
	}

	.notification-item:hover .notification-time {
		color: #475569 !important;
		font-weight: 600;
	}

	.notification-item:last-child {
		border-bottom: none;
	}

	.notification-item.unread {
		background: #eff6ff;
	}

	.notification-item.unread::before {
		content: '';
		position: absolute;
		left: 0;
		top: 0;
		bottom: 0;
		width: 3px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	}

	.notification-action-btn {
		display: inline-block;
		margin-top: 8px;
		padding: 6px 12px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white !important;
		border-radius: 4px;
		font-size: 12px;
		font-weight: 500;
		text-decoration: none;
		transition: all 0.3s ease;
	}

	.notification-action-btn:hover {
		background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
		transform: translateY(-1px);
		box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
		color: white !important;
	}

	.notification-icon {
		flex-shrink: 0;
		width: 40px;
		height: 40px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 10px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #ffffff;
		font-size: 18px;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
	}

	.notification-content {
		flex: 1;
		min-width: 0;
	}

	.notification-title {
		font-size: 14px;
		font-weight: 600;
		color: #1e293b !important;
		margin: 0 0 4px 0;
		line-height: 1.4;
	}

	.notification-message {
		font-size: 13px;
		color: #64748b !important;
		margin: 0 0 6px 0;
		line-height: 1.5;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}

	.notification-time {
		font-size: 11px;
		color: #94a3b8 !important;
		display: flex;
		align-items: center;
		gap: 4px;
	}

	.notification-time i {
		font-size: 10px;
	}

	.notification-unread-dot {
		flex-shrink: 0;
		width: 8px;
		height: 8px;
		background: #3b82f6;
		border-radius: 50%;
		margin-top: 6px;
	}

	.notification-empty {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 48px 20px;
		text-align: center;
	}

	.notification-empty i {
		font-size: 48px;
		color: #cbd5e1;
		margin-bottom: 12px;
	}

	.notification-empty p {
		margin: 0;
		font-size: 14px;
		color: #94a3b8;
		font-weight: 500;
	}

	.notification-footer {
		padding: 12px 20px;
		border-top: 1px solid #e5e7eb;
		background: #f8fafc;
		margin: 0;
	}

	.notification-actions {
		display: flex;
		gap: 8px;
	}

	.btn-mark-all,
	.btn-clear-all {
		flex: 1;
		padding: 10px 16px;
		color: #ffffff;
		border: none;
		border-radius: 8px;
		font-size: 13px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
	}

	.btn-mark-all {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	}

	.btn-mark-all:hover {
		background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
		transform: translateY(-1px);
		box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
	}

	.btn-clear-all {
		background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
	}

	.btn-clear-all:hover {
		background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
		transform: translateY(-1px);
		box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
	}

	.btn-mark-all:active,
	.btn-clear-all:active {
		transform: translateY(0);
	}

	.btn-mark-all:disabled,
	.btn-clear-all:disabled {
		opacity: 0.5;
		cursor: not-allowed;
		background: #cbd5e1;
	}

	/* Responsive Design */
	@media (max-width: 768px) {
		.notification-dropdown {
			width: 360px;
			right: 8px !important;
			max-width: calc(100vw - 16px);
		}

		.notification-item {
			padding: 12px 16px;
		}

		.notification-icon {
			width: 36px;
			height: 36px;
			font-size: 16px;
		}

		.notification-title {
			font-size: 13px;
		}

		.notification-message {
			font-size: 12px;
		}
	}

	@media (max-width: 480px) {
		.notification-dropdown {
			width: calc(100vw - 16px);
			right: 8px !important;
		}

		.notification-header {
			padding: 14px 16px;
		}

		.notification-header h4 {
			font-size: 15px;
		}

		.notification-actions {
			flex-direction: column;
		}

		.btn-mark-all,
		.btn-clear-all {
			width: 100%;
		}
	}

	/* Dark mode support (optional) */
	@media (prefers-color-scheme: dark) {
		.notification-dropdown {
			background: #1e293b;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
		}

		.notification-item {
			border-bottom-color: #334155;
			color: #e2e8f0 !important;
		}

		.notification-item:hover {
			background: rgba(102, 126, 234, 0.1);
		}

		.notification-item.unread {
			background: #1e3a5f;
		}

		.notification-title {
			color: #f1f5f9 !important;
		}

		.notification-message {
			color: #cbd5e1 !important;
		}

		.notification-time {
			color: #94a3b8 !important;
		}

		.notification-footer {
			background: #0f172a;
			border-top-color: #334155;
		}

		.notification-empty i {
			color: #475569;
		}

		.notification-empty p {
			color: #64748b;
		}
	}

</style>

<hr style="margin-top:0px;" />
<table class="table table-h"></table>

<script type="text/javascript">
	$ = jQuery;

	//var block_int = null;

	

	/**$(function() {

		//remove table-responsive class from all tables
		$('.table').removeClass('table-responsive');
		$('.table').removeClass('table-striped');
		$('.table').removeClass('table-hover');
		$('.table').removeClass('table-active');

		$('.table').addClass('table-striped');
		$('.table').addClass('table-hover');
		$('.table').addClass('table-active');
		//add checkbox class to checkbox input types
		$('input[type="checkbox"]').removeClass('form-control');
		$('input[type="checkbox"]').addClass('checkbox');

		block_int = setInterval(() => {
		  //account_status();
		}, 4000);

		//log user out after 10 mins of inactiveness
		var LOG_TIME_OUT = 10 * 60;
		var INACTIVE_TIME_SECONDS = 0;

		document.onclick = function() {
			INACTIVE_TIME_SECONDS = 0;
		}

		document.onkeypress = function() {
			INACTIVE_TIME_SECONDS = 0;
		}


		document.onmousemove = function() {
			INACTIVE_TIME_SECONDS = 0;
		}


		setInterval(() => {
		  check_idle_time();
		}, 1000);

		function check_idle_time() {
			INACTIVE_TIME_SECONDS++;
			var user_name = '<?php //echo $name; ?>';

			if(INACTIVE_TIME_SECONDS == LOG_TIME_OUT) {
				showAjaxModal_idle_user('<?php echo site_url('modal/popup_idle_user/idle_user_logged_out/'); ?>' + user_name);
				clearTimeout();

				//redirect user to the login page after the login here button is clicked
				$(document).on('hidden.bs.modal', '#modal_idle_user', function() {
					window.location.href = '<?php echo site_url('login/logout'); ?>';
				});			
			}
		}		
	}); **/

  $('#mega_link').click(function(event) {
    event.preventDefault();
    $('#main_div').addClass('sidebar-collapsed');
  });

	$(function() {

        DataTable.ext.errMode = 'none'; /*disable datatable errors*/

		//called to update the crsf name and key
             $.ajaxSetup({
                //cache: false,
                data: {
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                } 
             }); 
/**
         check_internal_m();
         setInterval(() => {
               check_internal_m(); internal_m(); attendance_update();
             }, 3000); **/
        //called to check if current user has an internal message alert

      /**  function check_internal_m() {
        	var current_user = '<?php //echo $current_user; ?>';

        	$.ajax({
        		url: '<?php //echo site_url('admin/check_internal_m/') ?>' + current_user,
        		success: function(response) {
					$('#badge_message').text(response);
        		}
        	});
        } **/

        //called to check if current user has an internal message alert2
      /**  function internal_m() {
        	var current_user = '<?php echo $current_user; ?>';

        	$.ajax({
        		url: '<?php echo site_url('admin/internal_m/') ?>' + current_user,
        		success: function(response) {
					$('.badge_message2').html(response);
        		}
        	});
        }
          **/
        

       });



    //check if there's internet connectivity
    const checkOnlineStatus = async () => {
        try {
            const response = await fetch('https://jsonplaceholder.typicode.com/posts?id=1', 
            );
            console.log(response.status);
            return true;

        } catch(err) {

            console.log('No internet');
            return false;
        }
    }

	function get_session_changer()
	{

		$.ajax({
            url: '<?php echo site_url('admin/get_session_changer');?>',
            success: function(response)
            {
                jQuery('#session_static').html(response);
            }
        });
	}

	function get_term_changer()
	{
        
		$.ajax({
            url: '<?php echo site_url('admin/get_term_changer');?>',
            success: function(response)
            {
                jQuery('#term_static').html(response);
            }
        });
	}

  function get_sem_changer()
  {

    $.ajax({
            url: '<?php echo site_url('admin/get_sem_changer');?>',
            success: function(response)
            {
                jQuery('#sem_static').html(response);
            }
        });
  }

	$(document).ready(function() {
		$('#webs').click(function() {
			$('#webs').css('color', 'red');
			var account_type = '<?php echo $account_type; ?>';
			if(account_type == 'admin') {
				$('#webs').text('You have not requested for this service.');
			}else {
				$('#webs').text('This service is not available.');
			}
			
		});
	});


		//update payment alert for admin
	/**	setInterval(function() {
			mobile_money_payment_alert_rows(); //mobile_money_payment_alert_show();
		}, 600); **/

		/**function mobile_money_payment_alert_rows() {
			$.ajax({
				url: '<?php // echo site_url('admin/mobile_money_payment_alert_rows'); ?>',
				success: function(num_rows) {
					if(num_rows > 0) {
						$('#badge1').text(num_rows);
						$('#badge1').css('display', 'inline-flex');
						$('#momo').css({'color': '#ffffff', 'font-weight': 'bold'});
					}else{
						$('#badge1').css('display', 'none');
						$('#momo').css({'color': '#1325ab'});
					}
				}
			});
		} **/

	/**	function mobile_money_payment_alert_show() {
			var page_name = '<?php echo $page_name; ?>';
			$.ajax({
				url: '<?php echo site_url('admin/mobile_money_payment_alert_show/'); ?>' + page_name,
				success: function(message) {
					$('#momo_message_list_holder').html(message);
				}
			});
		} **/

		/**update payment alert for admin
		setInterval(function() {
			notifications(); notifications_rows();
		}, 600); 

		var marked_read = '';
		var user_id = '';

		//function notifications_rows() {
			//the id of the closed notice item when the modal is closed
			/**$(document).on('hidden.bs.modal', '#modal_ajax', function() {
			 marked_read = $('#marked_read').filter(':checked').val();
			 user_id = $('#user_id').val();
			});

			if(marked_read == '' || marked_read == null) {
				marked_read = 0;
			}
			if(user_id == '' || user_id == null) {
				user_id = 0;
			}

			var logged_in_id = '<?php echo $this->session->userdata($account_type.'_id') ?>';

			$.ajax({
				url: '<?php echo site_url('admin/notifications_rows/'); ?>' + marked_read + '/' + user_id + '/' + logged_in_id,
				success: function(num_rows) {
					if(num_rows > 0) {
						$('#badge2').text(num_rows);
						$('#badge2').css('display', 'inline-flex');
						$('#bell').css({'color': '#ffffff', 'font-weight': 'bold'});
					}else{
						$('#badge2').css('display', 'none');
						$('#bell').css({'color': '#1325ab'});
					}
				}
				
			});
		}**/

		//function notifications() {
			//the id of the closed notice item when the modal is closed
		/**	$(document).on('hidden.bs.modal', '#modal_ajax', function() {
			 marked_read = $('#marked_read').filter(':checked').val();
			 user_id = $('#user_id').val();
			});

			if(marked_read == '' || marked_read == null) {
				marked_read = 0;
			}
			if(user_id == '' || user_id == null) {
				user_id = 0;
			}

			var logged_in_id = '<?php echo $this->session->userdata($account_type.'_id') ?>';
			var page_name = '<?php echo $page_name; ?>';

			$.ajax({
				url: '<?php echo site_url('admin/notifications/'); ?>' + page_name + '/' + marked_read + '/' + user_id + '/' + logged_in_id,
				success: function(notifications) {
					$('#message_list_holder').html(notifications);
				}
			});
		}**/

		function noticeboard(notice_id) {
			showAjaxModal('<?php echo site_url('modal/popup/modal_view_notice/'); ?>' + notice_id);
		}

		//frequently check if user's account has been blocked
	/**	function account_status() {
			var account_type = '<?php echo $account_type; ?>';
			var current_user_id = '<?php echo $current_user_id; ?>';
			var current_user_name = '<?php echo $name; ?>';

			$.ajax({
				url: '<?php echo site_url('login/account_status/'); ?>' + account_type + '/' + current_user_id,
				success: function(response) {
					if(response == 'blocked') {
						showAjaxModal_user_blocked('<?php echo site_url('modal/popup_idle_user/account_blocked/'); ?>' + current_user_name);

						clearInterval(block_int);

						$(document).on('hidden.bs.modal', '#modal_user_blocked', function() {
							window.location.href = '<?php echo site_url('login/logout'); ?>';
						});
					}
				}
			})
		}

    **/

		//check and update attendance status whether all attendance were taken today
    /**function attendance_update() {
      $.ajax({
        url: '<?php echo site_url('admin/att_update'); ?>',
        success: function(response) {
          if(response == 1) {
            $('#att_alert h3').fadeIn('500');
          } else {

          }     
        }
      });

    }

    **/

    //$('#att_alert h3').mouseenter(function(event) {
      /* Act on the event */
    /**  $.ajax({
        url: '<?php echo site_url('admin/att_update_show'); ?>',
        success: function(response) {
          $('#unmarked_tb').fadeIn('500');
          $('table #att_list_holder').html(response);       
        }
      });
    }); **/

   // $('#unmarked_tb').mouseleave(function(event) {
      /* Act on the event */
  //    $('#unmarked_tb').fadeOut('slow');
  //  });
  //   $('#att_alert').click(function(event) {
      /* Act on the event */
  //    $('#unmarked_tb').fadeOut('slow');
  //  });
   //  $('body').click(function(event) {
      /* Act on the event */
   //   $('#unmarked_tb').fadeOut('slow');
 //   }); 


             
</script>

<!-- Global Base URL for JavaScript -->
<script>
    // Make base_url available globally to all JavaScript files
    // This ensures compatibility across different school installations
    var base_url = '<?php echo base_url(); ?>';
</script>

<script src="<?php echo base_url(); ?>assets/js/offline-db.js"></script>
<script src="<?php echo base_url(); ?>assets/js/offline-sync.js"></script>
<script src="<?php echo base_url(); ?>assets/js/offline-crud.js"></script>
<script src="<?php echo base_url(); ?>assets/js/offline-cache.js"></script>
<script src="<?php echo base_url(); ?>assets/js/sync-helpers.js"></script>
<script>
// Initialize offline system
document.addEventListener('DOMContentLoaded', function() {
    // Set user ID for multi-user support
    const userId = '<?php echo $this->session->userdata("login_user_id"); ?>';
    if (userId && syncManager) {
        syncManager.setUserId(userId);
    }
    
    // Auto-add data-sync to all forms
    document.querySelectorAll('form').forEach(form => {
        if (!form.hasAttribute('data-no-sync')) {
            form.setAttribute('data-sync', '');
        }
    });
    
    // Service worker removed - using local WAMP + cloud sync approach instead
    
    // Test connection status on load
    setTimeout(() => {
        if (typeof syncManager !== 'undefined') {
            console.log('Sync Manager Status:', {
                online: syncManager.isOnline,
                userId: syncManager.userId
            });
        }
    }, 2000);
});

// Add CSS for pulse animation if not exists
if (!document.getElementById('offline-pulse-style')) {
    const style = document.createElement('style');
    style.id = 'offline-pulse-style';
    style.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }
    `;
    document.head.appendChild(style);
}
</script>

<script>
// Fix dropdown functionality
$(document).ready(function() {
	$('#user_profile_btn').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#info').toggleClass('open');
	});
	
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#info').length) {
			$('#info').removeClass('open');
		}
	});
});

function showDriveListModal() {
	$('#driveListModal .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
	$('#driveListModal').modal('show');
	$.ajax({
		url: '<?php echo site_url('modal/popup/drive_list_options'); ?>',
		success: function(response) {
			$('#driveListModal .modal-body').html(response);
		}
	});
}

// Modern Notification Bell System
function loadNotifications() {
	$.ajax({
		url: '<?php echo site_url($account_type . '/get_notifications'); ?>',
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			if(response.status === 'success') {
				const count = response.count || 0;
				const notifications = response.notifications || [];
				
				const $badge = $('#notification_badge');
				if(count > 0) {
					$badge.text(count > 99 ? '99+' : count).addClass('active');
					$('#notification_count').text(count + ' new');
					$('#btn_mark_all').show();
				} else {
					$badge.removeClass('active');
					$('#notification_count').text('No new notifications');
					$('#btn_mark_all').hide();
				}
				
				if(notifications.length > 0) {
					$('#btn_clear_all').show();
				} else {
					$('#btn_clear_all').hide();
				}
				
				if(notifications.length > 0) {
					let html = '';
					notifications.forEach(function(notif) {
						const iconMap = {
							'admission': 'fa-user-plus',
							'payment': 'fa-money-bill-wave',
							'discount': 'fa-percent',
							'discount_approval': 'fa-percent',
							'invoice_delete_approval': 'fa-trash-alt',
							'alert': 'fa-exclamation-triangle',
							'info': 'fa-info-circle',
							'success': 'fa-check-circle'
						};
						const icon = iconMap[notif.type] || 'fa-bell';
						const timeAgo = notif.time_ago || 'Just now';
						
						let actionLink = '';
						if(notif.type === 'discount_approval') {
							actionLink = `<a href="<?php echo site_url('admin/discount_approvals'); ?>" class="notification-action-btn" onclick="event.stopPropagation();"><i class="fa fa-external-link-alt"></i> Review Discount</a>`;
						} else if(notif.type === 'invoice_delete_approval') {
							actionLink = `<a href="<?php echo site_url('admin/manageRequestApproval'); ?>" class="notification-action-btn" onclick="event.stopPropagation();"><i class="fa fa-external-link-alt"></i> Review Request</a>`;
						}
						
						html += `
							<div class="notification-item ${notif.is_read == 0 ? 'unread' : ''}" onclick="showNotificationDetails(${notif.notification_id})">
								<div class="notification-icon">
									<i class="fa ${icon}"></i>
								</div>
								<div class="notification-content">
									<p class="notification-title">${notif.title}</p>
									<p class="notification-message">${notif.message}</p>
									${actionLink}
									<div class="notification-time">
										<i class="fa fa-clock"></i>
										<span>${timeAgo}</span>
									</div>
								</div>
								${notif.is_read == 0 ? '<div class="notification-unread-dot"></div>' : ''}
							</div>
						`;
					});
					$('#notification_list').html(html);
				} else {
					$('#notification_list').html(`
						<div class="notification-empty">
							<i class="fa fa-bell-slash"></i>
							<p>No notifications yet</p>
						</div>
					`);
				}
			}
		},
		error: function() {
			$('#notification_badge').removeClass('active');
		}
	});
}



function showNotificationDetails(notificationId) {
	$('#notification_bell').removeClass('open');
	
	// Show loading state
	if(typeof showAjaxModal_alert !== 'function') {
		$('#notificationDetailsModal .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
		$('#notificationDetailsModal').modal('show');
	} else {
		showAjaxModal_alert('Loading notification...', 'loading');
	}
	
	$.ajax({
		url: '<?php echo site_url($account_type . '/get_notification_details/'); ?>' + notificationId,
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			if(typeof showAjaxModal_alert === 'function') {
				$('#modal_alert').modal('hide');
			}
			
			if(response.status === 'success') {
				const notif = response.notification;
				const routeUrl = response.route_url;
				const actionLabel = response.action_label || 'View Details';
				const data = notif.data ? (typeof notif.data === 'string' ? JSON.parse(notif.data) : notif.data) : {};
				
				// If route URL exists, navigate directly
				if(routeUrl) {
					window.location.href = routeUrl;
					return;
				}
				
				const iconMap = {
					'admission': 'user-plus',
					'payment': 'money-bill-wave',
					'discount': 'percent',
					'alert': 'exclamation-triangle',
					'info': 'info-circle',
					'success': 'check-circle'
				};
				const icon = iconMap[notif.type] || 'bell';
				
				let content = `
					<div style="padding:20px;">
						<div style="display:flex;align-items:center;gap:15px;margin-bottom:20px;">
							<div style="width:60px;height:60px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:15px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(102,126,234,0.3);">
								<i class="fa fa-${icon} fa-2x" style="color:white;"></i>
							</div>
							<div style="flex:1;">
								<h3 style="margin:0;color:#1e293b;font-size:20px;font-weight:700;">${notif.title}</h3>
								<p style="margin:5px 0 0;color:#64748b;font-size:13px;"><i class="fa fa-clock"></i> ${notif.time_ago}</p>
							</div>
						</div>
						<div style="background:#f8fafc;padding:20px;border-radius:12px;margin-bottom:20px;border-left:4px solid #667eea;">
							<p style="color:#334155;font-size:15px;line-height:1.6;margin:0;">${notif.message}</p>
						</div>
				`;
				
				if(data.student_name) {
					content += `
						<div style="border-top:1px solid #e2e8f0;padding-top:20px;">
							<h4 style="color:#1e293b;font-size:16px;font-weight:600;margin:0 0 15px;"><i class="fa fa-info-circle"></i> Details</h4>
							<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;">
								<div style="background:#f8fafc;padding:12px;border-radius:8px;">
									<p style="color:#64748b;font-size:12px;margin:0;">Student Name</p>
									<p style="color:#1e293b;font-size:14px;font-weight:600;margin:5px 0 0;">${data.student_name}</p>
								</div>
								<div style="background:#f8fafc;padding:12px;border-radius:8px;">
									<p style="color:#64748b;font-size:12px;margin:0;">Class</p>
									<p style="color:#1e293b;font-size:14px;font-weight:600;margin:5px 0 0;">${data.class_name || 'N/A'}</p>
								</div>
								<div style="background:#f8fafc;padding:12px;border-radius:8px;">
									<p style="color:#64748b;font-size:12px;margin:0;">Residence Type</p>
									<p style="color:#1e293b;font-size:14px;font-weight:600;margin:5px 0 0;">${data.residence_type || 'Day'}</p>
								</div>
								<div style="background:#f8fafc;padding:12px;border-radius:8px;">
									<p style="color:#64748b;font-size:12px;margin:0;">Total Billed</p>
									<p style="color:#1e293b;font-size:14px;font-weight:600;margin:5px 0 0;">GH₵ ${parseFloat(data.total_billed || 0).toFixed(2)}</p>
								</div>
							</div>
							${data.discount_profile ? `
								<div style="margin-top:15px;background:#fef3c7;padding:15px;border-radius:8px;border-left:4px solid #f59e0b;">
									<h5 style="color:#92400e;font-size:14px;font-weight:600;margin:0 0 10px;"><i class="fa fa-percent"></i> Discount Applied</h5>
									<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
										<div>
											<p style="color:#78350f;font-size:11px;margin:0;">Profile</p>
											<p style="color:#92400e;font-size:13px;font-weight:600;margin:3px 0 0;">${data.discount_profile}</p>
										</div>
										<div>
											<p style="color:#78350f;font-size:11px;margin:0;">Discount</p>
											<p style="color:#92400e;font-size:13px;font-weight:600;margin:3px 0 0;">${data.discount_details}</p>
										</div>
										<div>
											<p style="color:#78350f;font-size:11px;margin:0;">Amount</p>
											<p style="color:#92400e;font-size:13px;font-weight:600;margin:3px 0 0;">GH₵ ${parseFloat(data.discount_amount || 0).toFixed(2)}</p>
										</div>
									</div>
								</div>
							` : ''}
							<div style="margin-top:15px;background:#dcfce7;padding:15px;border-radius:8px;border-left:4px solid #16a34a;">
								<div style="display:flex;justify-content:space-between;align-items:center;">
									<span style="color:#166534;font-size:14px;font-weight:600;">Balance After Discount</span>
									<span style="color:#166534;font-size:18px;font-weight:700;">GH₵ ${parseFloat(data.balance || data.total_billed || 0).toFixed(2)}</span>
								</div>
							</div>
						</div>
					`;
				}
				
				content += '</div>';
				
				let actions = '';
				if(notif.type === 'admission' && data.student_id) {
					actions = `
						<button class="btn modern-btn modern-btn-primary" onclick="viewStudent(${data.student_id})" style="margin-right:10px;">
							<i class="fa fa-eye"></i> View Student
						</button>
					`;
				}
				
				$('#notificationDetailsModal .modal-body').html(content);
				$('#notificationDetailsModal .modal-footer').html(`
					${actions}
					<button class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Close</button>
				`);
				$('#notificationDetailsModal').modal('show');
				
				if(notif.is_read == 0) {
					$.post('<?php echo site_url($account_type . '/mark_as_read/'); ?>' + notificationId, function() {
						loadNotifications();
					});
				}
			} else {
				showAjaxModal_alert(response.message || 'Failed to load notification', 'error');
			}
		},
		error: function() {
			if(typeof showAjaxModal_alert === 'function') {
				$('#modal_alert').modal('hide');
			}
			showAjaxModal_alert('Error loading notification details', 'error');
		}
	});
}

function viewStudent(studentId) {
	$('#notificationDetailsModal').modal('hide');
	navigation('<?php echo site_url('admin/student_profile/'); ?>' + studentId);
}

function markAllAsRead() {
	const $btn = $('#btn_mark_all');
	$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Marking...');
	
	$.ajax({
		url: '<?php echo site_url('admin/mark_all_read'); ?>',
		type: 'POST',
		dataType: 'json',
		success: function(response) {
			if(response.status === 'success') {
				loadNotifications();
				$('#notification_bell').removeClass('open');
			}
			$btn.prop('disabled', false).html('<i class="fa fa-check-double"></i> Mark All as Read');
		},
		error: function() {
			$btn.prop('disabled', false).html('<i class="fa fa-check-double"></i> Mark All as Read');
			showAjaxModal_alert('Failed to mark notifications as read', 'error');
		}
	});
}

function clearAllNotifications() {
	showConfirmModal(
		'Confirm Clear All',
		'Are you sure you want to permanently delete all notifications? This cannot be undone.',
		function() {
			const $btn = $('#btn_clear_all');
			$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Clearing...');
			
			$.ajax({
				url: '<?php echo site_url('admin/clear_all_notifications'); ?>',
				type: 'POST',
				data: {
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				},
				dataType: 'json',
				success: function(response) {
					if(response.status === 'success') {
						$('#notification_list').html('<div class="text-center p-3"><i class="fa fa-check-circle text-success"></i><br>No notifications</div>');
						$('#notification_count').text('0').hide();
						$('#notification_bell').removeClass('open');
						showAjaxModal_alert(response.message, 'success', false);
					} else {
						showAjaxModal_alert(response.message || 'Failed to clear notifications', 'error');
					}
					$btn.prop('disabled', false).html('<i class="fa fa-trash-alt"></i> Clear All');
				},
				error: function() {
					$btn.prop('disabled', false).html('<i class="fa fa-trash-alt"></i> Clear All');
					showAjaxModal_alert('Failed to clear notifications', 'error');
				}
			});
		},
		'Clear All',
		'danger'
	);
}

// Initialize notification system
$(document).ready(function() {
	// Initialize notification system
	loadNotifications();
	//setInterval(loadNotifications, 30000);
	
	// Handle dropdown toggle
	$('.notification-bell-trigger').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#notification_bell').toggleClass('open');
	});
	
	// Close dropdown when clicking outside
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#notification_bell').length) {
			$('#notification_bell').removeClass('open');
		}
	});
});

// Handle notification link clicks intelligently
function handleNotificationClick(event, tabId) {
	event.preventDefault();
	event.stopPropagation();
	
	// Close notification dropdown
	$('#notification_bell').removeClass('open');
	
	// Check if we're already on the student_invoice page
	const currentUrl = window.location.href;
	const targetUrl = '<?php echo site_url('admin/student_invoice'); ?>';
	
	if (currentUrl.includes('student_invoice')) {
		// Already on the page, just activate the tab
		$('a[href="#' + tabId + '"]').tab('show');
	} else {
		// Navigate to the page with the tab hash
		window.location.href = targetUrl + '#' + tabId;
	}
}
</script>


<script>
// Responsive Select2 Handler - Disable on mobile, enable on desktop
(function() {
    function handleSelect2Responsive() {
        const isMobile = window.innerWidth < 768;
        
        if (isMobile) {
            // Destroy select2 on mobile devices
            // IMPORTANT: Only target actual <select> elements, not generated containers
            $('select.select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
        } else {
            // Initialize select2 on desktop devices
            // IMPORTANT: Only target actual <select> elements, not generated containers
            $('select.select2').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'classic',
                        width: '100%'
                    });
                }
            });
        }
    }
    
    // Run on page load
    $(document).ready(function() {
        handleSelect2Responsive();
    });
    
    // Run on window resize with debounce
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleSelect2Responsive, 250);
    });
    
    // Run on AJAX complete for dynamically loaded content
    $(document).ajaxComplete(function() {
        setTimeout(handleSelect2Responsive, 100);
    });
})();
</script>

