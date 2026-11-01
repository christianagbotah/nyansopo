<?php
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$running_sem = $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description;
$teacher_id = $this->session->userdata('teacher_id');

// Load permission helper for checking user permissions
$this->load->helper('permission');

// Get theme color
$theme_color = $this->db->get_where('settings', array('type' => 'theme_color'))->row()->description ?? '#3b82f6';
?>

<style>
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
    .sidebar-menu #main-menu #search { position: sticky !important; top: 0 !important; z-index: 101 !important; background: <?php echo $theme_color; ?> !important; }
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
.page-container.sidebar-collapsed .sidebar-menu li > ul { display: none !important; position: fixed !important; left: 70px; background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); min-width: 250px; max-height: 80vh; overflow-y: auto; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.4); z-index: 100; padding: 10px 0; border: 1px solid rgba(255,255,255,0.1); }
.page-container.sidebar-collapsed .sidebar-menu li > ul li a { padding: 10px 20px; margin: 2px 8px; white-space: nowrap; box-shadow: none; }
.page-container.sidebar-collapsed #main-menu > li > a span { display: none; }
</style>

<div class="sidebar-menu md:sticky md:fixed my-0 overflow-y-auto overflow-x-hidden md:h-screen md:min-h-screen md:max-h-screen bg-gradient-to-b from-slate-800 to-slate-900 shadow-lg">
    <header class="logo-env border-b border-white/10 block md:block pb-5">
        <div class="logo">
            <a href="<?php echo site_url(); ?>">
                <img src="<?php echo base_url('uploads/school_logo.png');?>"/>
            </a>
        </div>
        <div class="sidebar-collapse">
            <a href="#" class="sidebar-collapse-icon with-animation">
                <i class="entypo-menu text-3xl text-white"></i>
            </a>
        </div>
        <div class="sidebar-mobile-menu visible:xs visible:sm md:hidden">
            <a href="#" class="with-animation">
                <i class="fa fa-times text-2xl text-white"></i>
            </a>
        </div>
    </header>

    <ul id="main-menu" class="list-group">
        <li id="search">
            <?php echo form_open(site_url($account_type.'/student_details'));?>
                <input type="text" class="search-input" name="student_identifier" placeholder="<?php echo get_phrase('student_name').' / '.get_phrase('iD_no').'...'; ?>" required>
                <button type="submit"><i class="entypo-search"></i></button>
            </form>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Main</div>
        
        <li class="<?php if ($page_name == 'dashboard' || $page_name == 'dashboard_new') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/dashboard/ajax'); ?>')">
                <i class="entypo-gauge"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Students</div>

        <li class="<?php if ($page_name == 'student_information' || $page_name == 'student_profile' || $page_name == 'student_promotion' || $page_name == 'exam_reports') echo 'opened active'; ?>">
            <a href="#">
                <i class="fa fa-user-graduate"></i>
                <span><?php echo get_phrase('students'); ?></span>
            </a>
            <ul>
                <li class="<?php if ($page_name == 'student_information' || $page_name == 'student_profile') echo 'opened'; ?>">
                    <a href="#">
                        <span><i class="fa fa-list"></i> <?php echo get_phrase('student_information'); ?></span>
                    </a>
                    <ul>
                        <?php
                        $class_groups = array('CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS');
                        foreach($class_groups as $group):
                            $this->db->order_by('name_numeric', 'asc');
                            $classes = $this->db->get_where('class', array('name' => $group, 'teacher_id' => $teacher_id))->result_array();
                            if(count($classes) > 0):
                        ?>
                        <li class="<?php if (isset($class_name) && $class_name == $group) echo 'opened'; ?>">
                            <a href="#">
                                <span><i class="fa fa-layer-group"></i> <b><?php echo get_phrase($group); ?></b></span>
                            </a>
                            <ul>
                                <?php foreach ($classes as $row): ?>
                                <li class="<?php if (isset($class_name) && $class_name == $group && isset($class_id) && $class_id == $row['class_id']) echo 'active'; ?>">
                                    <a href="#" onclick="navigation('<?php echo site_url('teacher/student_information/' . $row['class_id']); ?>')">
                                        <span><?php echo $row['name'].' '.$row['name_numeric'].' '.$this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name; ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </li>

                <li class="<?php if ($page_name == 'student_promotion') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/student_promotion'); ?>')">
                        <span><i class="fa fa-level-up-alt"></i> <?php echo get_phrase('student_promotion'); ?></span>
                    </a>
                </li>

                <li class="<?php if ($page_name == 'exam_reports') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/exam_reports'); ?>')">
                        <span><i class="fa fa-archive"></i> <?php echo get_phrase('exam_reports_archives'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="<?php if ($page_name == 'teacher') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/teacher_list'); ?>')">
                <i class="fa fa-chalkboard-teacher"></i>
                <span><?php echo get_phrase('teachers'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Academics</div>

        <li class="<?php if ($page_name == 'subject' || $page_name == 'subject_creche') echo 'opened active'; ?>">
            <a href="#">
                <i class="fa fa-book"></i>
                <span><?php echo get_phrase('subject'); ?></span>
            </a>
            <ul>
                <?php foreach($class_groups as $group): ?>
                <li>
                    <a href="#">
                        <span><i class="fa fa-angle-right"></i> <b><?php echo get_phrase($group); ?></b></span>
                    </a>
                    <ul>
                        <?php
                        $this->db->order_by('name_numeric', 'asc');
                        $classes = $this->db->get_where('class', array('name' => $group, 'teacher_id' => $teacher_id))->result_array();
                        foreach ($classes as $row):
                            $subject_url = ($group == 'CRECHE') ? 'teacher/subject/'.$row['class_id'].'/creche/'.$row['name_numeric'] : 'teacher/subject/'.$row['class_id'];
                        ?>
                        <li>
                            <a href="#" onclick="navigation('<?php echo site_url($subject_url); ?>')">
                                <span><?php echo $row['name'].' '.$row['name_numeric'].$this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>

        <li class="<?php if ($page_name == 'class_routine_view' || $page_name == 'class_routine_add') echo 'opened active'; ?>">
            <a href="#">
                <i class="fa fa-calendar-alt"></i>
                <span><?php echo get_phrase('class_time_table'); ?></span>
            </a>
            <ul>
                <?php foreach($class_groups as $group): ?>
                <li>
                    <a href="#">
                        <span><i class="fa fa-angle-right"></i> <b><?php echo get_phrase($group); ?></b></span>
                    </a>
                    <ul>
                        <?php
                        $this->db->order_by('name_numeric', 'asc');
                        $classes = $this->db->get_where('class', array('name' => $group, 'teacher_id' => $teacher_id))->result_array();
                        foreach ($classes as $row):
                        ?>
                        <li class="<?php if ($page_name == 'class_routine_view' && isset($class_id) && $class_id == $row['class_id']) echo 'active'; ?>">
                            <a href="#" onclick="navigation('<?php echo site_url('teacher/class_routine_view/' . $row['class_id']); ?>')">
                                <span><?php echo $row['name'].' '.$row['name_numeric'].$this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>

        <li class="<?php if ($page_name == 'study_material') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/study_material'); ?>')">
                <i class="fa fa-file-alt"></i>
                <span><?php echo get_phrase('study_material'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'academic_syllabus') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/academic_syllabus'); ?>')">
                <i class="fa fa-book-open"></i>
                <span><?php echo get_phrase('academic_syllabus'); ?></span>
            </a>
        </li>

        <!-- <li class="<?php if ($page_name == 'lesson_notes' || $page_name == 'lesson_note_create' || $page_name == 'lesson_note_edit') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/lesson_notes'); ?>')">
                <i class="fa fa-file-signature"></i>
                <span><?php echo get_phrase('lesson_notes'); ?></span>
            </a>
        </li> -->

        <li class="<?php if ($page_name == 'attendance_dashboard' || $page_name == 'attendance/dashboard' || $page_name == 'mark_attendance' || $page_name == 'barcode_scanner_attendance' || $page_name == 'manage_attendance' || $page_name == 'manage_attendance_view' || $page_name == 'attendance_report' || $page_name == 'attendance_report_view' || $page_name == 'students_daily_attendance_modern') echo 'opened active'; ?>">
            <a href="#">
                <i class="fa fa-calendar-check"></i>
                <span><?php echo get_phrase('attendance'); ?></span>
            </a>
            <ul>
                <li class="<?php if ($page_name == 'attendance_dashboard' || $page_name == 'attendance/dashboard') echo 'active'; ?>">
                    <a href="<?php echo site_url('attendance/dashboard'); ?>">
                        <span><i class="fa fa-tachometer-alt"></i> <?php echo get_phrase('dashboard'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'mark_attendance' || $page_name == 'manage_attendance' || $page_name == 'manage_attendance_view') echo 'opened'; ?>">
                    <a href="#">
                        <span><i class="fa fa-check-circle"></i> <?php echo get_phrase('mark_attendance'); ?></span>
                    </a>
                    <ul>
                        <?php foreach($class_groups as $group):
                            $this->db->order_by('name_numeric', 'asc');
                            $classes = $this->db->get_where('class', array('name' => $group, 'teacher_id' => $teacher_id))->result_array();
                            if(count($classes) > 0):
                        ?>
                        <li>
                            <a href="#">
                                <span><i class="fa fa-angle-right"></i> <b><?php echo get_phrase($group); ?></b></span>
                            </a>
                            <ul>
                                <?php foreach ($classes as $row): ?>
                                <li>
                                    <a href="<?php echo site_url('attendance/mark?class_id='.$row['class_id'].'&date='.date('Y-m-d')); ?>">
                                        <span><?php echo $row['name'].' '.$row['name_numeric'].$this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name; ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </li>
                <li class="<?php if ($page_name == 'attendance_report' || $page_name == 'attendance_report_view') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/attendance_report'); ?>')">
                        <span><i class="fa fa-chart-bar"></i> <?php echo get_phrase('attendance_report'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'daily_payment_report') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/daily_payment_report'); ?>')">
                        <span><i class="fa fa-money-bill-wave"></i> <?php echo get_phrase('daily_payment_report'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="<?php if ($page_name == 'marks_manage' || $page_name == 'marks_manage_view' || $page_name == 'manage_mark' || $page_name == 'question_paper' || $page_name == 'portfolio_assessment_manage_view' || $page_name == 'portfolio_assessment_manage' || $page_name == 'assessment_graph' || $page_name == 'cummulative_reports' || $page_name == 'student_marksheet_list') echo 'opened active'; ?>">
            <a href="#">
                <i class="fa fa-clipboard-list"></i>
                <span><?php echo get_phrase('examination'); ?></span>
            </a>
            <ul>
                <li class="<?php if ($page_name == 'marks_manage' || $page_name == 'marks_manage_view' || $page_name == 'manage_mark') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/marks_manage'); ?>')">
                        <span><i class="fa fa-edit"></i> <?php echo get_phrase('manage_exam_marks'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'student_marksheet_list') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/student_marksheet_list'); ?>')">
                        <span><i class="fa fa-chart-bar"></i> <?php echo get_phrase('student_marksheet'); ?></span>
                    </a>
                </li>
                <li id="raw_nav_teacher" class="<?php if ($page_name == 'raw_score_grade') echo 'active'; ?>" <?php 
                    $waec_enabled = $this->db->get_where('settings', array('type' => 'raw_score'))->row();
                    if (!$waec_enabled || $waec_enabled->description != 'Yes') echo 'style="display: none;"';
                ?>>
                    <a href="#" onclick="navigation('<?php echo site_url('admin/grade/raw_score_grade'); ?>')">
                        <span><i class="fa fa-certificate"></i> WAEC Standard Grading</span>
                    </a>
                </li>
                
                <?php
                // Show Portfolio Assessment menu only if enabled in settings
                $portfolio_enabled = $this->db->get_where('settings', array('type' => 'portfolio_assessment_enabled'))->row();
                if ($portfolio_enabled && $portfolio_enabled->description == '1'):
                ?>
                <li class="<?php if ($page_name == 'portfolio_assessment_manage' || $page_name == 'portfolio_assessment_manage_view') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/portfolio_assessment_manage'); ?>')">
                        <span><i class="fa fa-chart-line"></i> <?php echo get_phrase('portfolio_assessment'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'assessment_graph') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/assessment_graph'); ?>')">
                        <span><i class="fa fa-chart-area"></i> <?php echo get_phrase('assessment_graphs'); ?></span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="<?php if ($page_name == 'cummulative_reports') echo 'active'; ?>">
                    <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_cumulative_report'); ?>')">
                        <span><i class="fa fa-file-alt"></i> <?php echo get_phrase('cumulative_reports'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="<?php if ($page_name == 'manage_online_exam' || $page_name == 'add_online_exam' || $page_name == 'edit_online_exam' || $page_name == 'manage_online_exam_question' || $page_name == 'update_online_exam_question' || $page_name == 'view_online_exam_results') echo 'opened active'; ?>">
            <a href="#">
                <i class="fa fa-laptop"></i>
                <span><?php echo get_phrase('online_exam'); ?></span>
            </a>
            <ul>
                <li class="<?php if ($page_name == 'add_online_exam') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/create_online_exam'); ?>')">
                        <span><i class="fa fa-plus-circle"></i> <?php echo get_phrase('create_online_exam'); ?></span>
                    </a>
                </li>
                <li class="<?php if ($page_name == 'manage_online_exam' || $page_name == 'edit_online_exam' || $page_name == 'manage_online_exam_question' || $page_name == 'view_online_exam_results') echo 'active'; ?>">
                    <a href="#" onclick="navigation('<?php echo site_url('teacher/manage_online_exam'); ?>')">
                        <span><i class="fa fa-tasks"></i> <?php echo get_phrase('manage_online_exam'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Facilities</div>

        <li class="<?php if ($page_name == 'book') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/book'); ?>')">
                <i class="fa fa-book"></i>
                <span><?php echo get_phrase('library'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'transport') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/transport'); ?>')">
                <i class="fa fa-bus"></i>
                <span><?php echo get_phrase('transport'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Communication</div>

        <li class="<?php if ($page_name == 'noticeboard') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/noticeboard'); ?>')">
                <i class="fa fa-bullhorn"></i>
                <span><?php echo get_phrase('noticeboard'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'message' || $page_name == 'group_message') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/message'); ?>')">
                <i class="fa fa-envelope"></i>
                <span><?php echo get_phrase('message'); ?></span>
                <sup><div class="badge badge-danger" id="badge_message" style="background-color: red;"></div></sup>
            </a>
        </li>

        <?php
        // Check if teacher has permissions for report management modules
        $has_head_teacher_remarks = false;
        $has_teacher_remarks_templates = false;
        $has_conduct_items = false;
        $has_interest_items = false;
        $has_attendance_monitoring = false;
        
        if (!empty($teacher_id)) {
            // Get CI instance to access models
            $CI =& get_instance();
            
            // Use permission helper if available
            if (function_exists('user_has_permission')) {
                $has_head_teacher_remarks = user_has_permission($teacher_id, 'teacher', 'head_teacher_remarks', 'view');
                $has_teacher_remarks_templates = user_has_permission($teacher_id, 'teacher', 'teacher_remarks_templates', 'view');
                $has_conduct_items = user_has_permission($teacher_id, 'teacher', 'conduct_items', 'view');
                $has_interest_items = user_has_permission($teacher_id, 'teacher', 'interest_items', 'view');
            } else {
                // Fallback: Direct database check
                if (!isset($CI->user_permissions_model)) {
                    $CI->load->model('User_permissions_model', 'user_permissions_model');
                }
                
                $has_head_teacher_remarks = $CI->user_permissions_model->check_permission($teacher_id, 'teacher', 'head_teacher_remarks', 'view');
                $has_teacher_remarks_templates = $CI->user_permissions_model->check_permission($teacher_id, 'teacher', 'teacher_remarks_templates', 'view');
                $has_conduct_items = $CI->user_permissions_model->check_permission($teacher_id, 'teacher', 'conduct_items', 'view');
                $has_interest_items = $CI->user_permissions_model->check_permission($teacher_id, 'teacher', 'interest_items', 'view');
            }
            
            // Check attendance monitoring privilege
            if (function_exists('can_access_attendance_monitoring')) {
                $has_attendance_monitoring = can_access_attendance_monitoring('teacher', $teacher_id);
            }
        }
        
        // Show Special Permissions section if teacher has any special permissions
        if ($has_head_teacher_remarks || $has_teacher_remarks_templates || $has_conduct_items || $has_interest_items || $has_attendance_monitoring):
        ?>
        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Special Permissions</div>

        <?php if ($has_attendance_monitoring): ?>
        <li class="<?php if ($page_name == 'students_daily_attendance_modern') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('admin/students_att/' . strtotime(date('d-m-Y'))); ?>')">
                <i class="fa fa-school"></i>
                <span><?php echo get_phrase('school_wide_attendance'); ?></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($has_head_teacher_remarks): ?>
        <li class="<?php if ($page_name == 'head_teacher_remarks') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('head_teacher_remarks'); ?>')">
                <i class="fa fa-comment-dots"></i>
                <span><?php echo get_phrase('head_teacher_remarks'); ?></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($has_teacher_remarks_templates): ?>
        <li class="<?php if ($page_name == 'teacher_remarks_templates') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher_remarks_templates'); ?>')">
                <i class="fa fa-file-alt"></i>
                <span><?php echo get_phrase('teacher_remarks_templates'); ?></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($has_conduct_items): ?>
        <li class="<?php if ($page_name == 'conduct_items') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('conduct_items'); ?>')">
                <i class="fa fa-user-check"></i>
                <span><?php echo get_phrase('conduct_items'); ?></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($has_interest_items): ?>
        <li class="<?php if ($page_name == 'interest_items') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('interest_items'); ?>')">
                <i class="fa fa-heart"></i>
                <span><?php echo get_phrase('interest_items'); ?></span>
            </a>
        </li>
        <?php endif; ?>

        <?php endif; ?>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Personal</div>

        <li class="<?php if ($page_name == 'payslip_list') echo 'active'; ?>">
            <a href="<?php echo site_url('teacher/payslipList/'.$this->db->get_where('teacher', ['teacher_id' => $teacher_id])->row()->teacher_code); ?>">
                <i class="fa fa-hand-holding-usd"></i>
                <span><?php echo get_phrase('My_payslips'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'manage_profile') echo 'active'; ?>">
            <a href="#" onclick="navigation('<?php echo site_url('teacher/manage_profile'); ?>')">
                <i class="fa fa-user-circle"></i>
                <span><?php echo get_phrase('profile'); ?></span>
            </a>
        </li>
    </ul>
</div>

<script>
$(function() {
    $('#main-menu li a').addClass('list-group-item');
    $('.list-group-item').css('background-color', 'inherit');
    
    // Collapse all menus except the one containing active item
    $('#main-menu li').not('.active').not(':has(li.active)').removeClass('opened');
    
    // Scroll to active menu item
    var $activeItem = $('#main-menu li.active').first();
    if ($activeItem.length) {
        setTimeout(function() {
            var sidebarTop = $('.sidebar-menu').scrollTop();
            var itemTop = $activeItem.position().top;
            var sidebarHeight = $('.sidebar-menu').height();
            var itemHeight = $activeItem.outerHeight();
            
            if (itemTop < 0 || itemTop + itemHeight > sidebarHeight) {
                $('.sidebar-menu').animate({
                    scrollTop: sidebarTop + itemTop - (sidebarHeight / 2) + (itemHeight / 2)
                }, 300);
            }
        }, 100);
    }
    
    // Position popup menus when sidebar is collapsed - CLICK-BASED for desktop
    var currentOpenMenu = null;
    
    // Click handler for parent menu items in collapsed mode
    $(document).on('click', '.page-container.sidebar-collapsed #main-menu > li:has(ul) > a', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $li = $(this).parent('li');
        var $submenu = $li.children('ul');
        
        // Close any currently open menu
        if (currentOpenMenu && currentOpenMenu[0] !== $submenu[0]) {
            currentOpenMenu.css('display', 'none');
        }
        
        // Toggle current menu
        if ($submenu.css('display') === 'block') {
            $submenu.css('display', 'none');
            currentOpenMenu = null;
        } else {
            // CRITICAL: Keep position:fixed at all times to prevent document flow changes
            // Measure with visibility:hidden instead of moving off-screen
            $submenu.css({
                'display': 'block',
                'visibility': 'hidden',
                'position': 'fixed',
                'left': '70px'
            });
            
            var offset = $li.offset();
            var topPos = offset.top;
            var windowHeight = $(window).height();
            var submenuHeight = $submenu.outerHeight();
            
            // Adjust position if menu would overflow viewport
            if (topPos + submenuHeight > windowHeight) {
                topPos = Math.max(20, windowHeight - submenuHeight - 20);
            }
            
            // Now show it properly positioned
            $submenu.css({
                'top': topPos + 'px',
                'visibility': 'visible'
            });
            currentOpenMenu = $submenu;
        }
        
        return false;
    });
    
    // Close popup menu when clicking outside
    $(document).on('click', function(e) {
        if ($('.page-container').hasClass('sidebar-collapsed')) {
            if (!$(e.target).closest('#main-menu').length) {
                $('#main-menu > li > ul').css('display', 'none');
                currentOpenMenu = null;
            }
        }
    });
    
    // Hover tooltip for non-parent items in collapsed mode
    var hoverTimeout;
    $(document).on('mouseenter', '.page-container.sidebar-collapsed #main-menu > li:not(:has(ul))', function() {
        var $li = $(this);
        var $link = $li.children('a');
        var offset = $li.offset();
        var topPos = offset.top;
        
        clearTimeout(hoverTimeout);
        $('.hover-tooltip').remove();
        
        var title = $link.find('span').text();
        if (title) {
            $('body').append('<span class="hover-tooltip" style="position: fixed; left: 70px; top: ' + topPos + 'px; background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 10px 20px; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.4); z-index: 101; white-space: nowrap; border: 1px solid rgba(255,255,255,0.1); pointer-events: none;">' + title + '</span>');
        }
    }).on('mouseleave', '.page-container.sidebar-collapsed #main-menu > li:not(:has(ul))', function() {
        hoverTimeout = setTimeout(function() {
            $('.hover-tooltip').remove();
        }, 100);
    });
    
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
        $('#' + type).html('<center><div id="skeleton-loader" class="skeleton-loader"><div class="skeleton-box skeleton-header"></div><div class="skeleton-box skeleton-line"></div><div class="skeleton-box skeleton-line"></div><div class="skeleton-box skeleton-line-short"></div></div></center>');
    } else {
        $('#main_page').html('<center><div id="skeleton-loader" class="skeleton-loader"><div class="skeleton-box skeleton-header"></div><div class="skeleton-box skeleton-line"></div><div class="skeleton-box skeleton-line"></div><div class="skeleton-box skeleton-line-short"></div></div></center>');
    }
    window.location.assign(url);
}
</script>
