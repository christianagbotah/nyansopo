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
<div class="sidebar-menu sticky md:fixed my-0 overflow-y-scroll md:h-screen md:min-h-screen md:max-h-screen">
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

    <div style="border-top:1px solid rgba(69, 74, 84, 0.7);"></div>
    <ul id="main-menu" class="list-group">
        <!-- add class "multiple-expanded" to allow multiple submenus to open -->
        <!-- class "auto-inherit-active-class" will automatically add "active" class for parent elements who are marked already with class "active" -->


        <!-- DASHBOARD -->
        <li class="<?php if ($page_name == 'dashboard') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/dashboard'); ?>">
                <i class="entypo-gauge"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>



        <!-- TEACHER -->
        <li class="<?php if ($page_name == 'teacher') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/teacher_list'); ?>">
                <i class="entypo-users"></i>
                <span><?php echo get_phrase('teacher'); ?></span>
            </a>
        </li>



        <!-- SUBJECT -->
        <li class="<?php if ($page_name == 'subject') echo ' active'; ?> ">
            <a href="<?php echo site_url($account_type.'/subject'); ?>">
                <i class="entypo-docs"></i>
                <span><?php echo get_phrase('subject'); ?></span>
            </a>
        </li>

        <!-- CLASS ROUTINE -->
        <li class="<?php if ($page_name == 'class_routine') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/class_routine'); ?>">
                <i class="entypo-target"></i>
                <span><?php echo get_phrase('class_time_table'); ?></span>
            </a>
        </li>

        <!-- Attendance -->
        <li class="<?php if ($page_name == 'manage_attendance') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/manage_attendance'); ?>">
                <i class="fa fa-line-chart"></i>
                <span><?php echo get_phrase('attendance'); ?></span>
            </a>
        </li>

		<!-- STUDY MATERIAL -->
        <li class="<?php if ($page_name == 'study_material') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/study_material'); ?>">
                <i class="entypo-book-open"></i>
                <span><?php echo get_phrase('study_material'); ?></span>
            </a>
        </li>

        <!-- ACADEMIC SYLLABUS -->
        <li class="<?php if ($page_name == 'academic_syllabus') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/academic_syllabus/'.$this->session->userdata('login_user_id')); ?>">
                <i class="entypo-doc"></i>
                <span><?php echo get_phrase('academic_syllabus'); ?></span>
            </a>
        </li>

        <!-- Exam marks -->
        <li class="<?php if ($page_name == 'student_marksheet') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/student_marksheet/'.$this->session->userdata('login_user_id')); ?>">
                <i class="entypo-graduation-cap"></i>
                <span><?php echo get_phrase('exam_marks'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'online_exam' || $page_name == 'online_exam_take') echo 'active'; ?> ">
            <a href="<?php echo site_url('student/online_exam');?>">
                <i class="fa fa-feed"></i>
                <span><?php echo get_phrase('online_exam'); ?></span>
            </a>
        </li>

        <!-- PAYMENT -->
        <li class="<?php if ($page_name == 'invoice' || $page_name == 'pay_with_payumoney') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/invoice'); ?>">
                <i class="entypo-credit-card"></i>
                <span><?php echo get_phrase('payment'); ?></span>
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
                    <a href="<?php echo site_url($account_type.'/book'); ?>">
                        <i class="entypo-dot"></i>
                        <span><?php echo get_phrase('book_list'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'book_request') echo 'active'; ?> ">
                    <a href="<?php echo site_url($account_type.'/book_request'); ?>">
                        <i class="entypo-dot"></i>
                        <span><?php echo get_phrase('my_book_requests'); ?></span>
                    </a>
                </li>
                 <li class="<?php if ($page_name == 'book_request_add') echo 'active'; ?> ">
                    <a href="<?php echo site_url($account_type.'/book_request_add'); ?>">
                        <i class="entypo-dot"></i>
                        <span><?php echo get_phrase('request_for_a_book'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- TRANSPORT -->
        <li class="<?php if ($page_name == 'transport') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/transport'); ?>">
                <i class="entypo-location"></i>
                <span><?php echo get_phrase('transport'); ?></span>
            </a>
        </li>

        <!-- NOTICEBOARD -->
        <li class="<?php if ($page_name == 'noticeboard') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/noticeboard'); ?>">
                <i class="entypo-doc-text-inv"></i>
                <span><?php echo get_phrase('noticeboard'); ?></span>
            </a>
        </li>

        <!-- MESSAGE -->
        
        <li class="<?php if ($page_name == 'message' || $page_name == 'group_message') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/message'); ?>">
                <i class="entypo-mail"></i>
                <span><?php echo get_phrase('message'); ?></span>
                <sup><div class="badge badge-danger" id="badge_message" style="background-color: red;"></div></sup>
            </a>
        </li>
    

        <!-- ACCOUNT -->
        <li class="<?php if ($page_name == 'manage_profile') echo 'active'; ?> ">
            <a href="<?php echo site_url($account_type.'/manage_profile'); ?>">
                <i class="entypo-lock"></i>
                <span><?php echo get_phrase('account'); ?></span>
            </a>
        </li>

    </ul>

</div>

<script type="text/javascript">
    $(function() {
        $('#main-menu li a').addClass('list-group-item');
        $('.list-group-item').css('background-color', 'inherit');
    });
</script>