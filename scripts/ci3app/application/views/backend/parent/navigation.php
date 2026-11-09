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

    <div style=""></div>
    <ul id="main-menu" class="list-group">
        <!-- add class "multiple-expanded" to allow multiple submenus to open -->
        <!-- class "auto-inherit-active-class" will automatically add "active" class for parent elements who are marked already with class "active" -->


        <!-- DASHBOARD -->
        <li class="<?php if ($page_name == 'dashboard') echo 'active'; ?> ">
            <a href="<?php echo site_url('parents/dashboard'); ?>">
                <i class="entypo-gauge"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>



        <!-- TEACHER -->
        <li class="<?php if ($page_name == 'class_masters' || $page_name == 'subject_teachers') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-users"></i>
                <span><?php echo get_phrase('teachers'); ?></span>
            </a>
            <ul>
                <li class="<?php if ($page_name == 'class_masters') echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/class_master'); ?>">
                        <span><i class="entypo-dot"></i> <?php echo get_phrase('class_masters');?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'subject_teachers') echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/subject_teacher'); ?>">
                        <span><i class="entypo-dot"></i> <?php echo get_phrase('subject_teachers');?></span>
                    </a>
                </li>
            </ul>
        </li>


        <!-- ACADEMIC SYLLABUS -->
        <li class="<?php if ($page_name == 'academic_syllabus') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-doc"></i>
                <span><?php echo get_phrase('academic_syllabus'); ?></span>
            </a>
            <ul>
            <?php
                $children_of_parent = $this->db->get_where('student' , array(
                    'parent_id' => $this->session->userdata('parent_id')
                ))->result_array();
                foreach ($children_of_parent as $row):
            ?>
                <li class="<?php if ($page_name == 'academic_syllabus') echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/academic_syllabus/'.$row['student_id']); ?>">
                        <span><i class="entypo-dot"></i> <?php echo $row['name'];?></span>
                    </a>
                </li>
            <?php endforeach;?>
            </ul>
        </li>

        <!-- CLASS ROUTINE -->
        <li class="<?php if ($page_name == 'class_routine') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-target"></i>
                <span><?php echo get_phrase('class_time_table'); ?></span>
            </a>
            <ul>
            <?php
                $children_of_parent = $this->db->get_where('student' , array(
                    'parent_id' => $this->session->userdata('parent_id')
                ))->result_array();
                foreach ($children_of_parent as $row):
            ?>
                <li class="<?php if ($page_name == 'class_routine') echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/class_routine/'.$row['student_id']); ?>">
                        <span><i class="entypo-dot"></i> <?php echo $row['name'];?></span>
                    </a>
                </li>
            <?php endforeach;?>
            </ul>
        </li>

        <!-- ATTENDANCE VIEW FOR CHILDREN -->
        <li class="<?php if ($page_name == 'attendance_report' || $page_name == 'attendance_report_view') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-chart-area"></i>
                <span><?php echo get_phrase('student_attendance'); ?></span>
            </a>
            <ul>
            <?php
                $children_of_parent = $this->db->get_where('student' , array(
                    'parent_id' => $this->session->userdata('parent_id')
                ))->result_array();
                foreach ($children_of_parent as $row):
            ?>
                <li class="<?php if ($page_name == 'attendance_report') echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/attendance_report/'.$row['student_id']); ?>">
                        <span><i class="entypo-dot"></i> <?php echo $row['name'];?></span>
                    </a>
                </li>
            <?php endforeach;?>
            </ul>
        </li>

        <!-- EXAMS -->
        <li class="<?php
        if ($page_name == 'marks') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-graduation-cap"></i>
                <span><?php echo get_phrase('exam_marks'); ?></span>
            </a>
            <ul>
            <?php
                foreach ($children_of_parent as $row):
            ?>
                <li class="<?php if ($page_name == 'marks' && $student_id == $row['student_id']) echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/marks/'.$row['student_id']); ?>">
                        <span><i class="entypo-dot"></i> <?php echo $row['name'];?></span>
                    </a>
                </li>
            <?php endforeach;?>
            </ul>
        </li>

        <!-- PAYMENT -->
        <li class="<?php if ($page_name == 'invoice' || $page_name == 'pay_with_payumoney') echo 'opened active';?> ">
            <a href="#">
                <i class="entypo-credit-card"></i>
                <span><?php echo get_phrase('payment'); ?></span>
            </a>
            <ul>
            <?php
                foreach ($children_of_parent as $row):
            ?>
                <li class="<?php if ($page_name == 'invoice') echo 'active'; ?> ">
                    <a href="<?php echo site_url('parents/invoice/'.$row['student_id']); ?>">
                        <span><i class="entypo-dot"></i> <?php echo $row['name'];?></span>
                    </a>
                </li>
            <?php endforeach;?>
            </ul>
        </li>


        <!-- LIBRARY -->
        <li class="<?php if ($page_name == 'book') echo 'active'; ?> ">
            <a href="<?php echo site_url('parents/book'); ?>">
                <i class="entypo-book"></i>
                <span><?php echo get_phrase('library'); ?></span>
            </a>
        </li>

        <!-- TRANSPORT -->
        <li class="<?php if ($page_name == 'transport') echo 'active'; ?> ">
            <a href="<?php echo site_url('parents/transport'); ?>">
                <i class="entypo-location"></i>
                <span><?php echo get_phrase('transport'); ?></span>
            </a>
        </li>

        <!-- NOTICEBOARD -->
        <li class="<?php if ($page_name == 'noticeboard') echo 'active'; ?> ">
            <a href="<?php echo site_url('parents/noticeboard'); ?>">
                <i class="entypo-doc-text-inv"></i>
                <span><?php echo get_phrase('noticeboard'); ?></span>
            </a>
        </li>

        <!-- MESSAGE -->

        <li class="<?php if ($page_name == 'message' || $page_name == 'group_message') echo 'active'; ?> ">
            <a href="<?php echo site_url('parents/message'); ?>">
                <i class="entypo-mail"></i>
                <span><?php echo get_phrase('message'); ?></span>
                <sup><div class="badge badge-danger" id="badge_message" style="background-color: red;"></div></sup>
            </a>
        </li>


        <!-- ACCOUNT -->
        <li class="<?php if ($page_name == 'manage_profile') echo 'active'; ?> ">
            <a href="<?php echo site_url('parents/manage_profile'); ?>">
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