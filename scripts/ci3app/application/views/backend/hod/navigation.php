<?php
/**
 * HOD Navigation View
 * 
 * Navigation menu for Head of Department portal.
 * 
 * Requirements: 10.1
 */
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$teacher_id = $this->session->userdata('teacher_id');

// Get theme color
$theme_color = $this->db->get_where('settings', array('type' => 'theme_color'))->row()->description ?? '#3b82f6';

// Get pending count for badge
$this->db->select('hs.subject_id');
$this->db->from('hod_subjects hs');
$this->db->where('hs.teacher_id', $teacher_id);
$subject_ids = array_column($this->db->get()->result(), 'subject_id');

$pending_count = 0;
if (!empty($subject_ids)) {
    $this->db->where_in('subject_id', $subject_ids);
    $this->db->where('status', 'pending');
    $this->db->where('deleted_at IS NULL');
    $pending_count = $this->db->count_all_results('lesson_notes');
}
?>

<style>
.sidebar-menu .logo-env { padding-bottom: 2rem !important; }
.sidebar-menu .logo-env .logo img { max-height: 50px !important; }
#main-menu li#search { margin-top: 0; }

@media (max-width: 768px) {
    .sidebar-mobile-menu { display: block !important; visibility: visible !important; }
    .sidebar-menu { position: fixed !important; left: -100% !important; top: 0 !important; width: 280px !important; height: 100vh !important; z-index: 100 !important; transition: left 0.3s ease !important; overflow-y: auto !important; display: flex !important; flex-direction: column !important; }
    .sidebar-menu.mobile-open { left: 0 !important; }
    .sidebar-menu .logo-env { display: flex !important; justify-content: flex-start !important; }
    .sidebar-menu.mobile-open .logo-env { justify-content: flex-end !important; }
    .sidebar-menu .logo-env .logo a { background: #ffffff !important; padding: 12px !important; border-radius: 9999px !important; display: inline-block !important; }
    .sidebar-menu #main-menu { flex: 1 !important; overflow-y: auto !important; }
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
#main-menu i { margin-right: 0.625rem; width: 1.25rem; text-align: center; }
.badge-hod { background: #f39c12; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 5px; }
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
        
        <!-- Lesson Note Notifications -->
        <div style="padding: 10px 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <?php $this->load->view('backend/shared/notification_bell'); ?>
        </div>
    </header>

    <ul id="main-menu" class="list-group">
        <li id="search">
            <?php echo form_open(site_url('teacher/student_details'));?>
                <input type="text" class="search-input" name="student_identifier" placeholder="<?php echo get_phrase('student_name').' / '.get_phrase('iD_no').'...'; ?>" required>
                <button type="submit"><i class="entypo-search"></i></button>
            </form>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">
            HOD Panel <span class="badge-hod">HOD</span>
        </div>
        
        <li class="<?php if ($page_name == 'dashboard') echo 'active'; ?>">
            <a href="<?php echo site_url('hod/dashboard'); ?>">
                <i class="entypo-gauge"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'lesson_notes_pending' || $page_name == 'lesson_note_review') echo 'active'; ?>">
            <a href="<?php echo site_url('hod/lesson_notes_pending'); ?>">
                <i class="fa fa-file-text-o"></i>
                <span><?php echo get_phrase('pending_lesson_notes'); ?></span>
                <?php if ($pending_count > 0): ?>
                    <span class="badge bg-yellow pull-right"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="<?php if ($page_name == 'lesson_note_review_history') echo 'active'; ?>">
            <a href="<?php echo site_url('hod/lesson_note_review_history'); ?>">
                <i class="fa fa-history"></i>
                <span><?php echo get_phrase('review_history'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Teacher Panel</div>

        <li class="<?php if ($page_name == 'teacher_dashboard') echo 'active'; ?>">
            <a href="<?php echo site_url('teacher/dashboard'); ?>">
                <i class="fa fa-chalkboard-teacher"></i>
                <span><?php echo get_phrase('teacher_dashboard'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'lesson_notes' || $page_name == 'lesson_note_create' || $page_name == 'lesson_note_edit') echo 'active'; ?>">
            <a href="<?php echo site_url('teacher/lesson_notes'); ?>">
                <i class="fa fa-book"></i>
                <span><?php echo get_phrase('my_lesson_notes'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'study_material') echo 'active'; ?>">
            <a href="<?php echo site_url('teacher/study_material'); ?>">
                <i class="fa fa-folder-open"></i>
                <span><?php echo get_phrase('study_material'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'attendance') echo 'active'; ?>">
            <a href="<?php echo site_url('teacher/attendance'); ?>">
                <i class="fa fa-calendar-check-o"></i>
                <span><?php echo get_phrase('attendance'); ?></span>
            </a>
        </li>

        <li class="<?php if ($page_name == 'marks') echo 'active'; ?>">
            <a href="<?php echo site_url('teacher/marks'); ?>">
                <i class="fa fa-graduation-cap"></i>
                <span><?php echo get_phrase('marks'); ?></span>
            </a>
        </li>

        <div class="nav-section-header text-slate-400 text-xs font-bold uppercase tracking-wider px-6">Account</div>

        <li>
            <a href="<?php echo site_url('teacher/manage_profile'); ?>">
                <i class="fa fa-user"></i>
                <span><?php echo get_phrase('profile'); ?></span>
            </a>
        </li>

        <li>
            <a href="<?php echo site_url('login/logout'); ?>">
                <i class="fa fa-sign-out"></i>
                <span><?php echo get_phrase('logout'); ?></span>
            </a>
        </li>
    </ul>
</div>
