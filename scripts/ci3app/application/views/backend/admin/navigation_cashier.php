<?php
/**
 * CASHIER NAVIGATION
 * Simplified navigation for cashier role (admin level 4 & 5)
 * Inherits dynamic theme from header.php
 */
$_level = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->level;
$name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
?>
<!-- Cashier navigation inherits all styles from main navigation in header.php -->
<style>
/* Only cashier-specific overrides if needed - all theme styles inherited from header.php */
.sidebar-menu .logo-env { padding-bottom: 2rem !important; }
.sidebar-menu .logo-env .logo img { max-height: 50px !important; }
#main-menu li#search { margin-top: 0; }

@media (max-width: 768px) {
    .sidebar-mobile-menu { display: block !important; visibility: visible !important; }
    .sidebar-menu { position: fixed !important; left: -100% !important; top: 0 !important; width: 280px !important; height: 100vh !important; z-index: 1000 !important; transition: left 0.3s ease !important; overflow-y: auto !important; display: flex !important; flex-direction: column !important; }
    .sidebar-menu.mobile-open { left: 0 !important; }
    .sidebar-menu .logo-env { display: flex !important; justify-content: flex-start !important; }
    .sidebar-menu.mobile-open .logo-env { justify-content: flex-end !important; }
    .sidebar-menu .logo-env .logo a { background: #ffffff !important; padding: 12px !important; border-radius: 9999px !important; display: inline-block !important; }
    .sidebar-menu #main-menu { flex: 1 !important; overflow-y: auto !important; display: block !important; visibility: visible !important; }
    .sidebar-menu #main-menu #search { position: sticky !important; top: 0 !important; z-index: 101 !important; background: #c62828 !important; }
    .sidebar-menu .logo-env { position: sticky !important; top: 0 !important; z-index: 101 !important; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); }
    .page-container { margin-left: 0 !important; }
    .main-content { margin-left: 0 !important; width: 100% !important; }
}

@media (min-width: 769px) {
    .sidebar-mobile-menu { display: none !important; visibility: hidden !important; }
}

.nav-section-header { margin-top: 2rem !important; margin-bottom: 0.75rem !important; padding-bottom: 0.5rem !important; border-bottom: 1px solid rgba(148,163,184,0.3); font-size: 0.8125rem !important; }
.page-container.sidebar-collapsed .nav-section-header { display: none; }
#main-menu li { border: none !important; }
#main-menu li a { display: flex; align-items: center; padding: 0.75rem 1.5rem; margin: 0.125rem 0.625rem; border-radius: 0.5rem; transition: all 0.2s; color: #cbd5e1; border: none !important; box-shadow: 0 1px 0 rgba(0,0,0,0.05); }
#main-menu li a:hover { background: rgba(59,130,246,0.1); color: #fff; box-shadow: 0 2px 4px rgba(59,130,246,0.1); }
#main-menu li.active > a { background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(37,99,235,0.1)); color: #fff; font-weight: 600; box-shadow: 0 2px 8px rgba(59,130,246,0.2); }
#main-menu li.opened { background: transparent; }
#main-menu li.opened > a { background: rgba(255,255,255,0.03); }
#main-menu ul ul { background: rgba(0,0,0,0.2); border-radius: 0.5rem; margin: 0.25rem 0.625rem; padding: 0.25rem 0; }
#main-menu ul ul li a { padding-left: 3rem; margin: 0.125rem 0.5rem; box-shadow: none; }
#main-menu ul ul ul li a { padding-left: 4rem; }
#main-menu i { margin-right: 0.625rem; width: 1.25rem; text-align: center; }
</style>

<div class="sidebar-menu md:sticky md:fixed my-0 overflow-y-scroll md:h-screen md:min-h-screen md:max-h-screen bg-gradient-to-b from-slate-800 to-slate-900 shadow-lg">
    <header class="logo-env border-b border-white/10 block md:block pb-5">
        <div class="logo">
            <a href="<?php echo site_url(); ?>">
                <img src="<?php echo base_url('uploads/school_logo.png');?>"/>
            </a>
        </div>
        <div class="sidebar-collapse">
            <a href="#" class="sidebar-collapse-icon with-animation">
                <i class="entypo-menu text-4xl text-white"></i>
            </a>
        </div>
        <div class="sidebar-mobile-menu visible:xs visible:sm md:hidden">
            <a href="#" class="with-animation">
                <i class="fa fa-times text-4xl text-white"></i>
            </a>
        </div>
    </header>

    <ul id="main-menu" class="list-group">
        <!-- Search Student -->
        <li id="search">
            <?php echo form_open(site_url($account_type . '/student_details'));?>
                <input type="text" class="search-input" name="student_identifier" placeholder="<?php echo get_phrase('student_name').' / '.get_phrase('iD_no').'...'; ?>" required>
                <button type="submit"><i class="entypo-search"></i></button>
            </form>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Cashier Portal</div>
        
        <!-- Dashboard -->
        <li class="<?php if ($page_name == 'dashboard') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/dashboard/ajax'); ?>')">
                <i class="entypo-gauge"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Fee Collection</div>

        <!-- Fee Collection Portal -->
        <li class="<?php if ($page_name == 'fee_collection') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('fee_collection'); ?>')">
                <i class="fa fa-hand-holding-usd"></i>
                <span><?php echo get_phrase('collect_fees'); ?></span>
            </a>
        </li>

        <!-- My Collections Today -->
        <li class="<?php if ($page_name == 'my_collections') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/my_collections'); ?>')">
                <i class="fa fa-list-alt"></i>
                <span><?php echo get_phrase('my_collections_today'); ?></span>
            </a>
        </li>

        <!-- Print Receipts -->
        <li class="<?php if ($page_name == 'print_receipts') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/print_receipts'); ?>')">
                <i class="fa fa-print"></i>
                <span><?php echo get_phrase('print_receipts'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Fee Management</div>

        <!-- Daily Fees Rates -->
        <li class="<?php if ($page_name == 'daily_fee_rates') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/daily_fee_rates'); ?>')">
                <i class="fa fa-tags"></i>
                <span><?php echo get_phrase('daily_fees_rates'); ?></span>
            </a>
        </li>

        <!-- Discount Assignments -->
        <li class="<?php if ($page_name == 'manage_discount_assignments' || $page_name == 'assign_student_discount') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/manage_discount_assignments'); ?>')">
                <i class="fa fa-user-tag"></i>
                <span><?php echo get_phrase('discount_assignments'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Students</div>

        <!-- Student Information (View Only) -->
        <li class="<?php if ($page_name == 'student_information') echo 'opened has-sub'; ?>">
            <a href="#">
                <i class="fa fa-user-graduate"></i>
                <span><?php echo get_phrase('view_students'); ?></span>
            </a>
            <ul>
                <?php
                $class_groups = array('CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS');
                foreach($class_groups as $group):
                ?>
                <li>
                    <a href="#">
                        <span><i class="fa fa-layer-group"></i> <b><?php echo get_phrase($group); ?></b></span>
                    </a>
                    <ul>
                        <?php
                        $this->db->order_by('name_numeric', 'asc');
                        $classes = $this->db->get_where('class', array('name' => $group))->result_array();
                        foreach ($classes as $row):
                        ?>
                        <li>
                            <a href="#" onclick="navigation('<?php echo site_url('admin/student_information/' . $row['class_id']); ?>')">
                                <span><?php echo $row['name'].' '.$row['name_numeric'].' '.$this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>

        <?php if (is_fee_module_enabled('transport')): ?>
        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Transport</div>

        <!-- Full Transportation Management -->
        <li class="<?php if (in_array($page_name, array('transport', 'transport_enhanced', 'transportation', 'assign_transport', 'transport_reports', 'transport_fare_report', 'transport_attendance'))) echo 'opened has-sub'; ?>">
            <a href="#">
                <i class="fa fa-bus"></i>
                <span><?php echo get_phrase('transport_management'); ?></span>
            </a>
            <ul>
                <!-- Transport Overview -->
                <li class="<?php if ($page_name == 'transportation' || $page_name == 'transport_enhanced') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('admin/transportation'); ?>')">
                        <i class="fa fa-tachometer-alt"></i>
                        <span><?php echo get_phrase('overview'); ?></span>
                    </a>
                </li>
                <!-- Assign Students to Transport -->
                <li class="<?php if ($page_name == 'assign_transport') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('admin/assign_transport'); ?>')">
                        <i class="fa fa-users"></i>
                        <span><?php echo get_phrase('assign_students'); ?></span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Reports</div>

        <!-- Daily Summary -->
        <li class="<?php if ($page_name == 'cashier_daily_summary') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/cashier_daily_summary'); ?>')">
                <i class="fa fa-chart-bar"></i>
                <span><?php echo get_phrase('daily_summary'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Account</div>

        <!-- Profile -->
        <li class="<?php if ($page_name == 'staff_details' || $page_name == 'manage_profile') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php 
                $current_admin_id = $this->session->userdata('admin_id');
                echo site_url('admin/admin_details/'.$current_admin_id); 
            ?>')">
                <i class="fa fa-user-circle"></i>
                <span><?php echo get_phrase('my_profile'); ?></span>
            </a>
        </li>

        <!-- Logout -->
        <li>
            <a href="<?php echo site_url('login/logout'); ?>">
                <i class="fa fa-sign-out-alt"></i>
                <span><?php echo get_phrase('logout'); ?></span>
            </a>
        </li>
    </ul>
</div>

<script>
$(function() {
    $('#main-menu li a').addClass('list-group-item');
    $('.list-group-item').css('background-color', 'inherit');
    
    // Collapse all menus except the one containing active item
    $('#main-menu li.has-sub').not(':has(li.active)').removeClass('opened');
    
    // Scroll to active menu item only on mobile
    var $activeItem = $('#main-menu li.active');
    if ($activeItem.length && window.matchMedia('(max-width: 768px)').matches) {
        setTimeout(function() {
            $('.sidebar-menu').animate({
                scrollTop: $activeItem.offset().top - $('.sidebar-menu').offset().top - 100
            }, 500);
        }, 300);
    }
    
    // Mobile drawer toggle
    $('.sidebar-mobile-menu a').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if ($('.sidebar-menu').hasClass('mobile-open')) {
            $('.sidebar-menu').removeClass('mobile-open');
            $('.mobile-backdrop').remove();
            $('body').removeClass('sidebar-open');
        } else {
            // Clean up any existing backdrop first
            $('.mobile-backdrop').remove();
            
            $('body').addClass('sidebar-open');
            $('body').append('<div class="mobile-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10;"></div>');
            setTimeout(function() {
                $('.sidebar-menu').addClass('mobile-open');
                $('#main-menu').css({'display': 'block', 'visibility': 'visible'}).show();
            }, 10);
        }
    });
    
    // Close drawer on backdrop click
    $(document).on('click', '.mobile-backdrop', function() {
        $('.sidebar-menu').removeClass('mobile-open');
        $('body').removeClass('sidebar-open');
        $(this).remove();
    });
});

function navigation(url, type) {
    // Close mobile sidebar when navigating
    const isMobile = window.matchMedia("only screen and (max-width: 768px)").matches;
    if (isMobile) {
        $('.sidebar-menu').removeClass('mobile-open');
        $('.mobile-backdrop').remove();
        $('body').removeClass('sidebar-open');
        // Don't hide the menu - just close the sidebar
    }
    ajaxFunction(url, type);
}

function ajaxFunction(url, type) {
    $('#main_page').empty();
    $('html, body').animate({scrollTop: ($('#top').offset().top)}, 1000);
    if(type != undefined) {
        $('#' + type).html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px;">Loading...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');
    } else {
        $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px;">Loading...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');
    }
    window.location.assign(url);
}
</script>
