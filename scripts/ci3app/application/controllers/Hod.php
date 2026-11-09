<?php
/**
 * HOD (Head of Department) Controller
 * 
 * Handles HOD-specific functionality including lesson note review,
 * endorsement, and revision requests for assigned subjects.
 * 
 * @package     GES Lesson Note System
 * @subpackage  Controllers
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Hod extends CI_Controller {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->model('Lesson_note_model');
        
        // Check authentication
        if ($this->session->userdata('teacher_login') != 1) {
            redirect(base_url());
        }
        
        // Verify HOD role
        $teacher_id = $this->session->userdata('teacher_id');
        if (!$this->is_hod($teacher_id)) {
            $this->session->set_flashdata('error_message', 'Access denied. HOD privileges required.');
            redirect(base_url() . 'teacher/dashboard');
        }
    }

    // ============================================
    // AUTHENTICATION AND HELPERS
    // ============================================

    /**
     * Check if teacher is an HOD
     * 
     * @param int $teacher_id Teacher ID
     * @return bool True if HOD, false otherwise
     */
    private function is_hod($teacher_id) {
        $this->db->where('teacher_id', $teacher_id);
        $count = $this->db->count_all_results('hod_subjects');
        return $count > 0;
    }

    /**
     * Get subjects managed by HOD
     * 
     * @param int $teacher_id HOD teacher ID
     * @return array Array of subject IDs
     */
    private function get_hod_subjects($teacher_id) {
        $this->db->select('subject_id');
        $this->db->from('hod_subjects');
        $this->db->where('teacher_id', $teacher_id);
        $results = $this->db->get()->result();
        return array_column($results, 'subject_id');
    }

    // ============================================
    // DASHBOARD
    // ============================================

    /**
     * HOD Dashboard
     */
    public function dashboard() {
        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Get HOD's subjects
        $subject_ids = $this->get_hod_subjects($teacher_id);

        // Get pending lesson notes count
        $this->db->where_in('subject_id', $subject_ids);
        $this->db->where('status', 'pending');
        $this->db->where('deleted_at IS NULL');
        $page_data['pending_count'] = $this->db->count_all_results('lesson_notes');

        // Get endorsed count this term
        $this->db->where_in('subject_id', $subject_ids);
        $this->db->where('status', 'hod_reviewed');
        $this->db->where('term', $running_term);
        $this->db->where('deleted_at IS NULL');
        $page_data['endorsed_count'] = $this->db->count_all_results('lesson_notes');

        // Get revision requested count
        $this->db->where_in('subject_id', $subject_ids);
        $this->db->where('status', 'revision_requested');
        $this->db->where('term', $running_term);
        $this->db->where('deleted_at IS NULL');
        $page_data['revision_count'] = $this->db->count_all_results('lesson_notes');

        // Get managed subjects
        $page_data['subjects'] = $this->db->query(
            "SELECT s.subject_id, s.name, c.name as class_name
             FROM hod_subjects hs
             JOIN subject s ON s.subject_id = hs.subject_id
             JOIN class c ON c.class_id = s.class_id
             WHERE hs.teacher_id = ?
             ORDER BY s.name",
            array($teacher_id)
        )->result();

        $page_data['page_name'] = 'dashboard';
        $page_data['page_title'] = get_phrase('hod_dashboard');
        $page_data['account_type'] = 'hod';

        $this->load->view('backend/main', $page_data);
    }

    // ============================================
    // LESSON NOTE REVIEW METHODS
    // ============================================

    /**
     * List pending lesson notes for HOD review
     * 
     * Requirements: 10.2, 10.3
     */
    public function lesson_notes_pending() {
        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        // Get filters from GET
        $filters = array(
            'subject_id' => $this->input->get('subject_id'),
            'class_id' => $this->input->get('class_id'),
            'term' => $this->input->get('term') ?: $running_term,
            'week_number' => $this->input->get('week_number')
        );

        // Get pending lesson notes for HOD's subjects
        $page_data['lesson_notes'] = $this->Lesson_note_model->get_pending_for_hod($teacher_id);
        
        // Apply additional filters
        if (!empty($filters['subject_id'])) {
            $page_data['lesson_notes'] = array_filter($page_data['lesson_notes'], function($ln) use ($filters) {
                return $ln->subject_id == $filters['subject_id'];
            });
        }
        if (!empty($filters['class_id'])) {
            $page_data['lesson_notes'] = array_filter($page_data['lesson_notes'], function($ln) use ($filters) {
                return $ln->class_id == $filters['class_id'];
            });
        }
        if (!empty($filters['term'])) {
            $page_data['lesson_notes'] = array_filter($page_data['lesson_notes'], function($ln) use ($filters) {
                return $ln->term == $filters['term'];
            });
        }
        if (!empty($filters['week_number'])) {
            $page_data['lesson_notes'] = array_filter($page_data['lesson_notes'], function($ln) use ($filters) {
                return $ln->week_number == $filters['week_number'];
            });
        }

        $page_data['teacher_id'] = $teacher_id;
        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['filters'] = $filters;

        // Get HOD's subjects for filters
        $page_data['subjects'] = $this->db->query(
            "SELECT DISTINCT s.subject_id, s.name 
             FROM hod_subjects hs
             JOIN subject s ON s.subject_id = hs.subject_id
             WHERE hs.teacher_id = ?
             ORDER BY s.name",
            array($teacher_id)
        )->result();

        // Get classes for HOD's subjects
        $page_data['classes'] = $this->db->query(
            "SELECT DISTINCT c.class_id, c.name, c.name_numeric 
             FROM class c 
             JOIN subject s ON s.class_id = c.class_id 
             JOIN hod_subjects hs ON hs.subject_id = s.subject_id
             WHERE hs.teacher_id = ?
             ORDER BY c.name_numeric",
            array($teacher_id)
        )->result();

        $page_data['page_name'] = 'lesson_notes_pending';
        $page_data['page_title'] = get_phrase('pending_lesson_notes');
        $page_data['account_type'] = 'hod';

        $this->load->view('backend/main', $page_data);
    }

    /**
     * View lesson note details for review
     * 
     * @param int $lesson_note_id Lesson note ID
     */
    public function lesson_note_review($lesson_note_id = null) {
        if (empty($lesson_note_id)) {
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        $teacher_id = $this->session->userdata('teacher_id');
        $subject_ids = $this->get_hod_subjects($teacher_id);

        // Get lesson note
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);
        
        if (!$lesson_note) {
            $this->session->set_flashdata('error_message', 'Lesson note not found.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Verify HOD has access to this subject
        if (!in_array($lesson_note->subject_id, $subject_ids)) {
            $this->session->set_flashdata('error_message', 'Access denied. You do not manage this subject.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Verify status is pending
        if ($lesson_note->status != 'pending') {
            $this->session->set_flashdata('error_message', 'This lesson note is not pending review.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Get revision history
        $page_data['revision_history'] = $this->Lesson_note_model->get_revision_history($lesson_note_id);

        $page_data['lesson_note'] = $lesson_note;
        $page_data['page_name'] = 'lesson_note_review';
        $page_data['page_title'] = get_phrase('review_lesson_note');
        $page_data['account_type'] = 'hod';

        $this->load->view('backend/main', $page_data);
    }

    /**
     * Endorse a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * 
     * Requirements: 10.4
     */
    public function lesson_note_endorse($lesson_note_id = null) {
        if (empty($lesson_note_id)) {
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        $teacher_id = $this->session->userdata('teacher_id');
        $subject_ids = $this->get_hod_subjects($teacher_id);

        // Get lesson note
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);
        
        if (!$lesson_note) {
            $this->session->set_flashdata('error_message', 'Lesson note not found.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Verify HOD has access
        if (!in_array($lesson_note->subject_id, $subject_ids)) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Endorse
        $result = $this->Lesson_note_model->endorse_lesson_note($lesson_note_id, $teacher_id);

        if ($result) {
            $this->session->set_flashdata('flash_message', 'Lesson note endorsed successfully. It has been forwarded to admin for final approval.');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to endorse lesson note.');
        }

        redirect(base_url() . 'hod/lesson_notes_pending');
    }

    /**
     * Request revision for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * 
     * Requirements: 10.5
     */
    public function lesson_note_request_revision($lesson_note_id = null) {
        if (empty($lesson_note_id)) {
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        $teacher_id = $this->session->userdata('teacher_id');
        $subject_ids = $this->get_hod_subjects($teacher_id);

        // Get lesson note
        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);
        
        if (!$lesson_note) {
            $this->session->set_flashdata('error_message', 'Lesson note not found.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Verify HOD has access
        if (!in_array($lesson_note->subject_id, $subject_ids)) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(base_url() . 'hod/lesson_notes_pending');
        }

        // Get feedback from POST
        $feedback = $this->input->post('feedback');
        
        if (empty($feedback)) {
            $this->session->set_flashdata('error_message', 'Feedback is required when requesting revision.');
            redirect(base_url() . 'hod/lesson_note_review/' . $lesson_note_id);
        }

        // Request revision
        $result = $this->Lesson_note_model->request_revision($lesson_note_id, $teacher_id, $feedback);

        if ($result) {
            $this->session->set_flashdata('flash_message', 'Revision requested successfully. The teacher has been notified.');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to request revision.');
        }

        redirect(base_url() . 'hod/lesson_notes_pending');
    }

    /**
     * View review history
     */
    public function lesson_note_review_history() {
        $teacher_id = $this->session->userdata('teacher_id');
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

        $subject_ids = $this->get_hod_subjects($teacher_id);

        // Get lesson notes reviewed by this HOD
        $this->db->select('ln.*, t.name as teacher_name, s.name as subject_name, c.name as class_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id');
        $this->db->join('subject s', 's.subject_id = ln.subject_id');
        $this->db->join('class c', 'c.class_id = ln.class_id');
        $this->db->where_in('ln.subject_id', $subject_ids);
        $this->db->where('ln.hod_id', $teacher_id);
        $this->db->where('ln.deleted_at IS NULL');
        $this->db->where_in('ln.status', array('hod_reviewed', 'approved', 'revision_requested'));
        $this->db->order_by('ln.hod_review_date', 'DESC');
        
        $page_data['lesson_notes'] = $this->db->get()->result();

        $page_data['running_year'] = $running_year;
        $page_data['running_term'] = $running_term;
        $page_data['page_name'] = 'lesson_note_review_history';
        $page_data['page_title'] = get_phrase('review_history');
        $page_data['account_type'] = 'hod';

        $this->load->view('backend/main', $page_data);
    }

    // ============================================
    // AJAX METHODS
    // ============================================

    /**
     * Get lesson note details via AJAX
     */
    public function ajax_get_lesson_note($lesson_note_id) {
        $teacher_id = $this->session->userdata('teacher_id');
        $subject_ids = $this->get_hod_subjects($teacher_id);

        $lesson_note = $this->Lesson_note_model->get_lesson_note($lesson_note_id);
        
        if (!$lesson_note || !in_array($lesson_note->subject_id, $subject_ids)) {
            echo json_encode(array('success' => false, 'message' => 'Access denied'));
            return;
        }

        echo json_encode(array('success' => true, 'data' => $lesson_note));
    }
    
    /**
     * Get lesson note notifications for HOD
     * AJAX endpoint for notification system
     * 
     * Requirements: 17.6, 17.7, 20.4
     */
    public function get_lesson_note_notifications() {
        if (!$this->is_hod($this->session->userdata('teacher_id'))) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        $limit = $this->input->get('limit') ?? 10;
        
        // Get notifications
        $notifications = $this->Lesson_note_model->get_notifications_for_user($teacher_id, 'hod', $limit);
        
        // Get unread count
        $unread_count = $this->Lesson_note_model->get_unread_notification_count($teacher_id, 'hod');
        
        // Add URLs to notifications
        foreach ($notifications as &$notification) {
            $notification->url = $this->generate_notification_url($notification);
        }
        
        echo json_encode([
            'status' => 'success',
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
    }
    
    /**
     * Mark notification as read
     * 
     * Requirements: 17.7
     */
    public function mark_notification_read() {
        if (!$this->is_hod($this->session->userdata('teacher_id'))) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $this->load->model('Lesson_note_model');
        
        $notification_id = $this->input->post('notification_id');
        $teacher_id = $this->session->userdata('teacher_id');
        
        if (empty($notification_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Notification ID required']);
            return;
        }
        
        $result = $this->Lesson_note_model->mark_notification_read($notification_id, $teacher_id, 'hod');
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark notification as read']);
        }
    }
    
    /**
     * Mark all notifications as read
     * 
     * Requirements: 17.7
     */
    public function mark_all_notifications_read() {
        if (!$this->is_hod($this->session->userdata('teacher_id'))) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        
        $result = $this->Lesson_note_model->mark_all_notifications_read($teacher_id, 'hod');
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark notifications as read']);
        }
    }
    
    /**
     * View all notifications page
     * 
     * Requirements: 17.7
     */
    public function notifications() {
        if (!$this->is_hod($this->session->userdata('teacher_id'))) {
            redirect(base_url());
        }
        
        $this->load->model('Lesson_note_model');
        
        $teacher_id = $this->session->userdata('teacher_id');
        
        // Get all notifications (paginated)
        $page = $this->input->get('page') ?? 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $notifications = $this->Lesson_note_model->get_notifications_for_user($teacher_id, 'hod', $per_page, $offset);
        $total_count = $this->Lesson_note_model->get_notification_count($teacher_id, 'hod');
        
        // Add URLs to notifications
        foreach ($notifications as &$notification) {
            $notification->url = $this->generate_notification_url($notification);
        }
        
        $page_data['notifications'] = $notifications;
        $page_data['total_count'] = $total_count;
        $page_data['current_page'] = $page;
        $page_data['total_pages'] = ceil($total_count / $per_page);
        $page_data['page_name'] = 'notifications';
        $page_data['page_title'] = get_phrase('notifications');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Generate URL for notification based on type
     * 
     * @param object $notification
     * @return string
     */
    private function generate_notification_url($notification) {
        $base = base_url();
        
        switch ($notification->reference_type) {
            case 'lesson_note_submitted':
                return $base . 'hod/lesson_note_review/' . $notification->reference_id;
            
            default:
                return $base . 'hod/lesson_notes_pending';
        }
    }
}
