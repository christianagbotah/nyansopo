<style type="text/css">
    /* Modern and Professional Sidebar Highlighting */
    .sidebar-menu li.active {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border-left: 4px solid #1e40af;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        transition: all 0.3s ease;
    }

    .sidebar-menu li.active a {
        color: white !important;
        font-weight: 600;
    }

    .sidebar-menu li.opened {
        background-color: #f8fafc;
        border-left: 4px solid #3b82f6;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .sidebar-menu li.opened a {
        color: #1e293b !important;
        font-weight: 500;
    }

    .sidebar-menu li:hover {
        background-color: rgba(0, 0, 0, 0.1);
        transition: background-color 0.2s ease;
    }

    .sidebar-menu li.active:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .sidebar-menu li.opened:hover {
        background-color: #e2e8f0;
    }

    /* Submenu styling */
    .sidebar-menu ul li.active {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        border-left: 3px solid #1d4ed8;
    }

    .sidebar-menu ul li.opened {
        background-color: #f1f5f9;
        border-left: 3px solid #60a5fa;
    }
</style>
<div class="sidebar-menu fixed my-0 overflow-y-scroll h-0 min-h-0 max-h-0 md:h-screen md:min-h-screen md:max-h-screen">
    <header class="logo-env" >

        <!-- logo -->
        <div class="logo" style="">
            <a href="<?php echo base_url(); ?>">
                <img src="<?php echo base_url('uploads/school_logo.png');?>"  style="max-height:80px;"/>
            </a>
        </div>

        <!-- logo collapse icon -->
        <div class="sidebar-collapse" style="">
            <a href="#" class="sidebar-collapse-icon with-animation">

                <i class="entypo-menu text-4xl"></i>
            </a>
        </div>

        <!-- open/close menu icon (do not remove if you want to enable menu on mobile devices) -->
        <div class="sidebar-mobile-menu visible-xs">
            <a href="#" class="with-animation">
                <i class="entypo-menu text-5xl"></i>
            </a>
        </div>
    </header>

    <div style=""></div>	
    <ul id="main-menu" class="">
        <!-- add class "multiple-expanded" to allow multiple submenus to open -->
        <!-- class "auto-inherit-active-class" will automatically add "active" class for parent elements who are marked already with class "active" -->


        <!-- DASHBOARD -->
        <li class="<?php if ($page_name == 'dashboard') echo 'active'; ?> ">
            <a href="<?php echo site_url('librarian/dashboard'); ?>">
                <i class="entypo-gauge"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>

        <!-- LIBRARY -->
        <li class="<?php if ($page_name == 'book' || $page_name == 'book_request') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-book"></i>
                <span><?php echo get_phrase('library'); ?></span>
            </a>
            <ul>
                <li class="<?php if ($page_name == 'book') echo 'active'; ?> ">
                    <a href="<?php echo site_url('librarian/book'); ?>">
                        <i class="entypo-dot"></i>
                        <span><?php echo get_phrase('book_list'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'book_request') echo 'active'; ?> ">
                    <a href="<?php echo site_url('librarian/book_request'); ?>">
                        <i class="entypo-dot"></i>
                        <span><?php echo get_phrase('book_requests'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- ACCOUNT -->
        <li class="<?php if ($page_name == 'manage_profile') echo 'active'; ?> ">
            <a href="<?php echo site_url('librarian/manage_profile'); ?>">
                <i class="entypo-lock"></i>
                <span><?php echo get_phrase('account'); ?></span>
            </a>
        </li>

    </ul>

</div>