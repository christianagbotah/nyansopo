<?php
/**
 * Shared Notification Bell Component
 * Can be included in any navigation file
 * 
 * Requirements: 17.6, 17.7, 20.4
 * 
 * Usage:
 * <?php $this->load->view('backend/shared/notification_bell'); ?>
 */

// Determine user type from session
$user_type = '';
if ($this->session->userdata('teacher_login') == 1) {
    $user_type = 'teacher';
} elseif ($this->session->userdata('admin_login') == 1) {
    $user_type = 'admin';
} elseif ($this->session->userdata('student_login') == 1) {
    $user_type = 'student';
} elseif ($this->session->userdata('hod_login') == 1) {
    $user_type = 'hod';
}

// Only show if user is logged in
if (empty($user_type)) {
    return;
}
?>

<!-- Lesson Note Notification Bell -->
<div id="lesson-note-notification-container">
    <a href="#" id="lesson-note-notification-bell" title="<?php echo get_phrase('notifications'); ?>">
        <i class="fa fa-bell"></i>
        <span id="lesson-note-notification-badge" class="badge">0</span>
    </a>
    
    <div id="lesson-note-notification-menu">
        <div class="notification-header">
            <h4><?php echo get_phrase('notifications'); ?></h4>
            <a href="#" id="mark-all-notifications-read" class="mark-all-read">
                <?php echo get_phrase('mark_all_as_read'); ?>
            </a>
        </div>
        
        <ul id="lesson-note-notification-dropdown">
            <li class="notification-loading">
                <i class="fa fa-spinner fa-spin"></i>
                <p><?php echo get_phrase('loading_notifications'); ?>...</p>
            </li>
        </ul>
    </div>
</div>

<!-- Initialize notification system -->
<script>
    var lesson_note_user_type = '<?php echo $user_type; ?>';
</script>

<!-- Include notification CSS and JS if not already included -->
<?php if (!isset($notification_assets_loaded)): ?>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/lesson_note_notifications.css">
    <script src="<?php echo base_url(); ?>assets/js/lesson_note_notifications.js"></script>
    <?php $notification_assets_loaded = true; ?>
<?php endif; ?>
