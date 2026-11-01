<?php
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$teacher_code = $this->db->get_where('teacher', ['teacher_id' => $this->session->userdata('teacher_id')])->row()->teacher_code;
?>

<style>
.modern-sidebar{background:linear-gradient(180deg,#1e293b 0%,#0f172a 100%);position:fixed;top:0;left:0;height:100vh;width:280px;overflow-y:auto;transition:transform .3s;z-index:1000;transform:translateX(-100%)}@media(min-width:768px){.modern-sidebar{transform:translateX(0)}}.modern-sidebar.active{transform:translateX(0)}.modern-sidebar .menu-item{padding:12px 20px;color:#cbd5e1;transition:all .2s;cursor:pointer;border-left:3px solid transparent}.modern-sidebar .menu-item:hover{background:rgba(59,130,246,.1);color:#fff;border-left-color:#3b82f6}.modern-sidebar .menu-item.active{background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;border-left-color:#1e40af;font-weight:600}.modern-sidebar .submenu{max-height:0;overflow:hidden;transition:max-height .3s ease}.modern-sidebar .submenu.open{max-height:1000px}.mobile-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999}.mobile-overlay.active{display:block}@media(min-width:768px){.mobile-overlay{display:none!important}}
</style>

<div class="mobile-overlay" onclick="toggleMobileSidebar()"></div>
<div class="modern-sidebar">
    <header class="logo-env" style="background:rgba(15,23,42,.95);border-bottom:1px solid rgba(59,130,246,.2);padding:1rem">
        <div class="logo text-center">
            <a href="<?php echo base_url(); ?>">
                <img src="<?php echo base_url('uploads/school_logo.png');?>" style="max-height:70px;width:auto"/>
            </a>
        </div>
        <button onclick="toggleMobileSidebar()" class="md:hidden absolute top-4 right-4 text-white text-2xl">
            <i class="fas fa-times"></i>
        </button>
    </header>

    <div class="p-4">
        <?php echo form_open(site_url('teacher/student_details'));?>
        <input type="text" name="student_identifier" class="search-input" placeholder="<?php echo get_phrase('search_student'); ?>..." required>
        <?php echo form_close();?>
    </div>

    <nav class="py-2">
        <!-- Dashboard -->
        <div class="menu-item <?php if($page_name == 'dashboard') echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/dashboard/ajax'); ?>')">
            <i class="entypo-gauge mr-3"></i><?php echo get_phrase('dashboard'); ?>
        </div>

        <!-- Students -->
        <div class="menu-item <?php if(in_array($page_name, ['student_information', 'student_profile', 'student_marksheet', 'student_promotion'])) echo 'active'; ?>" onclick="toggleSubmenu('students')">
            <i class="fa fa-users mr-3"></i><?php echo get_phrase('students'); ?>
            <i class="fas fa-chevron-down float-right mt-1"></i>
        </div>
        <div id="students-submenu" class="submenu bg-gray-800">
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/student_information'); ?>')">
                <?php echo get_phrase('student_information'); ?>
            </div>
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/student_promotion'); ?>')">
                <?php echo get_phrase('student_promotion'); ?>
            </div>
        </div>

        <!-- Attendance -->
        <div class="menu-item <?php if(in_array($page_name, ['manage_attendance', 'manage_attendance_view', 'attendance_report'])) echo 'active'; ?>" onclick="toggleSubmenu('attendance')">
            <i class="entypo-chart-area mr-3"></i><?php echo get_phrase('attendance'); ?>
            <i class="fas fa-chevron-down float-right mt-1"></i>
        </div>
        <div id="attendance-submenu" class="submenu bg-gray-800">
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/manage_attendance'); ?>')">
                <?php echo get_phrase('take_attendance'); ?>
            </div>
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/attendance_report'); ?>')">
                <?php echo get_phrase('attendance_report'); ?>
            </div>
        </div>

        <!-- Examination -->
        <div class="menu-item <?php if(in_array($page_name, ['marks_manage', 'marks_manage_view', 'manage_online_exam'])) echo 'active'; ?>" onclick="toggleSubmenu('exam')">
            <i class="entypo-pencil mr-3"></i><?php echo get_phrase('examination'); ?>
            <i class="fas fa-chevron-down float-right mt-1"></i>
        </div>
        <div id="exam-submenu" class="submenu bg-gray-800">
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/marks_manage'); ?>')">
                <?php echo get_phrase('manage_marks'); ?>
            </div>
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/manage_online_exam'); ?>')">
                <?php echo get_phrase('online_exam'); ?>
            </div>
        </div>

        <!-- Academics -->
        <div class="menu-item <?php if(in_array($page_name, ['subject', 'class_routine_view', 'study_material', 'academic_syllabus'])) echo 'active'; ?>" onclick="toggleSubmenu('academics')">
            <i class="entypo-graduation-cap mr-3"></i><?php echo get_phrase('academics'); ?>
            <i class="fas fa-chevron-down float-right mt-1"></i>
        </div>
        <div id="academics-submenu" class="submenu bg-gray-800">
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/subject'); ?>')">
                <?php echo get_phrase('subjects'); ?>
            </div>
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/class_routine_view'); ?>')">
                <?php echo get_phrase('timetable'); ?>
            </div>
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/study_material'); ?>')">
                <?php echo get_phrase('study_material'); ?>
            </div>
            <div class="menu-item pl-12" onclick="navigation('<?php echo site_url('teacher/academic_syllabus'); ?>')">
                <?php echo get_phrase('syllabus'); ?>
            </div>
        </div>

        <!-- Teachers -->
        <div class="menu-item <?php if($page_name == 'teacher') echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/teacher_list'); ?>')">
            <i class="entypo-users mr-3"></i><?php echo get_phrase('teachers'); ?>
        </div>

        <!-- Library -->
        <div class="menu-item <?php if($page_name == 'book') echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/book'); ?>')">
            <i class="entypo-book mr-3"></i><?php echo get_phrase('library'); ?>
        </div>

        <!-- Transport -->
        <div class="menu-item <?php if($page_name == 'transport') echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/transport'); ?>')">
            <i class="entypo-location mr-3"></i><?php echo get_phrase('transport'); ?>
        </div>

        <!-- Noticeboard -->
        <div class="menu-item <?php if($page_name == 'noticeboard') echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/noticeboard'); ?>')">
            <i class="entypo-doc-text-inv mr-3"></i><?php echo get_phrase('noticeboard'); ?>
        </div>

        <!-- Messages -->
        <div class="menu-item <?php if(in_array($page_name, ['message', 'group_message'])) echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/message'); ?>')">
            <i class="entypo-mail mr-3"></i><?php echo get_phrase('messages'); ?>
            <span id="badge_message" class="float-right bg-red-500 text-white text-xs px-2 py-1 rounded-full"></span>
        </div>

        <!-- Payslips -->
        <div class="menu-item <?php if($page_name == 'payslip_list') echo 'active'; ?>" onclick="window.location.href='<?php echo site_url('teacher/payslipList/'.$teacher_code); ?>'">
            <i class="fa fa-hand-holding-usd mr-3"></i><?php echo get_phrase('my_payslips'); ?>
        </div>

        <!-- Profile -->
        <div class="menu-item <?php if($page_name == 'manage_profile') echo 'active'; ?>" onclick="navigation('<?php echo site_url('teacher/manage_profile'); ?>')">
            <i class="entypo-lock mr-3"></i><?php echo get_phrase('profile'); ?>
        </div>
    </nav>
</div>

<script>
function toggleSubmenu(id) {
    const submenu = document.getElementById(id + '-submenu');
    submenu.classList.toggle('open');
}

function toggleMobileSidebar() {
    document.querySelector('.modern-sidebar').classList.toggle('active');
    document.querySelector('.mobile-overlay').classList.toggle('active');
}

function navigation(url) {
    if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        toggleMobileSidebar();
    }
    window.location.assign(url);
}
</script>
