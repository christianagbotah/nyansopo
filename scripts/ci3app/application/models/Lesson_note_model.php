<?php
/**
 * Lesson Note Model
 * 
 * Handles all database operations related to GES-compliant lesson notes
 * including CRUD operations, status management, and reporting.
 * 
 * Requirements: 1.1-1.9, 2.5, 3.2-3.5, 4.4, 5.5, 6.5, 9.1-9.7, 10.4-10.6, 
 *               11.2-11.7, 12.1-12.8, 13.1-13.8, 16.1-16.2
 * 
 * @package     GES Lesson Note System
 * @subpackage  Models
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lesson note model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Lesson_note_model extends MY_Model {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================

    /**
     * Create a new lesson note
     * 
     * @param array $data Lesson note data
     * @return int|false Inserted lesson note ID or false on failure
     * 
     * Requirements: 1.9, 9.1, 16.1
     */
    public function create_lesson_note($data) {
        // Validate required fields
        $required_fields = array('teacher_id', 'class_id', 'subject_id', 'title', 'week_number', 'term', 'lesson_date');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        // Validate week_number (1-12)
        if ($data['week_number'] < 1 || $data['week_number'] > 12) {
            return false;
        }

        // Validate term (1-3)
        if ($data['term'] < 1 || $data['term'] > 3) {
            return false;
        }

        $lesson_note_data = array(
            'teacher_id' => $data['teacher_id'],
            'class_id' => $data['class_id'],
            'subject_id' => $data['subject_id'],
            'strand_id' => $data['strand_id'] ?? null,
            'sub_strand_id' => $data['sub_strand_id'] ?? null,
            'content_standard_id' => $data['content_standard_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'week_number' => $data['week_number'],
            'term' => $data['term'],
            'lesson_date' => $data['lesson_date'],
            'lesson_objectives' => $data['lesson_objectives'] ?? null,
            'lesson_activities' => $data['lesson_activities'] ?? null,
            'lesson_content' => $data['lesson_content'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'file_name' => $data['file_name'] ?? null,
            'file_type' => $data['file_type'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'source_lesson_note_id' => $data['source_lesson_note_id'] ?? null,
            'version' => 1
        );

        $this->db->insert('lesson_notes', $lesson_note_data);
        $lesson_note_id = $this->db->insert_id();

        if ($lesson_note_id) {
            // Create revision record
            $this->create_revision_record($lesson_note_id, 'create', $data['teacher_id'], array(
                'action' => 'create',
                'new_status' => $lesson_note_data['status']
            ));
        }

        return $lesson_note_id;
    }

    /**
     * Update an existing lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param array $data Updated data
     * @param int $user_id User making the update
     * @return bool True on success, false on failure
     * 
     * Requirements: 1.1, 16.1
     */
    public function update_lesson_note($lesson_note_id, $data, $user_id = null) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Check ownership if teacher_id is provided
        if (isset($data['teacher_id'])) {
            $existing = $this->get_lesson_note($lesson_note_id);
            if (!$existing || $existing->teacher_id != $data['teacher_id']) {
                return false;
            }
        }

        $update_data = array();
        $allowed_fields = array(
            'title', 'description', 'strand_id', 'sub_strand_id', 'content_standard_id',
            'week_number', 'term', 'lesson_date', 'lesson_objectives', 'lesson_activities',
            'lesson_content', 'file_path', 'file_name', 'file_type', 'status', 'feedback'
        );
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false;
        }

        // Increment version
        $update_data['version'] = $this->db->query(
            "SELECT version + 1 FROM lesson_notes WHERE lesson_note_id = ?", 
            array($lesson_note_id)
        )->row()->{'version + 1'};

        $update_data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('lesson_note_id', $lesson_note_id);
        $result = $this->db->update('lesson_notes', $update_data);

        if ($result && $user_id) {
            $this->create_revision_record($lesson_note_id, 'update', $user_id, array(
                'changed_fields' => json_encode($update_data)
            ));
        }

        return $result;
    }

    /**
     * Delete a lesson note (soft delete)
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return bool True on success, false on failure
     * 
     * Requirements: 1.1
     */
    public function delete_lesson_note($lesson_note_id) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Soft delete
        $this->db->where('lesson_note_id', $lesson_note_id);
        return $this->db->update('lesson_notes', array('deleted_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Get a single lesson note by ID
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return object|null Lesson note object with related data or null
     * 
     * Requirements: 1.1
     */
    public function get_lesson_note($lesson_note_id) {
        if (empty($lesson_note_id)) {
            return null;
        }

        $this->db->select('ln.*, 
            t.name as teacher_name, t.teacher_code,
            c.name as class_name,
            s.name as subject_name,
            cs.name as strand_name,
            css.name as sub_strand_name,
            ccs.code as content_standard_code, ccs.description as content_standard_description');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id', 'left');
        $this->db->join('class c', 'c.class_id = ln.class_id', 'left');
        $this->db->join('subject s', 's.subject_id = ln.subject_id', 'left');
        $this->db->join('curriculum_strands cs', 'cs.strand_id = ln.strand_id', 'left');
        $this->db->join('curriculum_sub_strands css', 'css.sub_strand_id = ln.sub_strand_id', 'left');
        $this->db->join('curriculum_content_standards ccs', 'ccs.content_standard_id = ln.content_standard_id', 'left');
        $this->db->where('ln.lesson_note_id', $lesson_note_id);
        $this->db->where('ln.deleted_at', NULL);
        
        $query = $this->db->get();
        $lesson_note = $query->row();

        if ($lesson_note) {
            // Get related data
            $lesson_note->learning_indicators = $this->get_lesson_note_indicators($lesson_note_id);
            $lesson_note->core_competencies = $this->get_lesson_note_competencies($lesson_note_id);
            $lesson_note->resources = $this->get_lesson_note_resources($lesson_note_id);
            $lesson_note->assessments = $this->get_lesson_note_assessments($lesson_note_id);
            $lesson_note->references = $this->get_lesson_note_references($lesson_note_id);
        }

        return $lesson_note;
    }

    /**
     * Get lesson notes by teacher ID
     * 
     * @param int $teacher_id Teacher ID
     * @param array $filters Optional filters (status, subject_id, class_id, term, week_number)
     * @return array Array of lesson note objects
     * 
     * Requirements: 1.1
     */
    public function get_lesson_notes_by_teacher($teacher_id, $filters = array()) {
        if (empty($teacher_id)) {
            return array();
        }

        $this->db->select('ln.*, s.name as subject_name, c.name as class_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('subject s', 's.subject_id = ln.subject_id', 'left');
        $this->db->join('class c', 'c.class_id = ln.class_id', 'left');
        $this->db->where('ln.teacher_id', $teacher_id);
        $this->db->where('ln.deleted_at', NULL);

        // Apply filters
        if (!empty($filters['status'])) {
            $this->db->where('ln.status', $filters['status']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('ln.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('ln.class_id', $filters['class_id']);
        }
        if (!empty($filters['term'])) {
            $this->db->where('ln.term', $filters['term']);
        }
        if (!empty($filters['week_number'])) {
            $this->db->where('ln.week_number', $filters['week_number']);
        }

        $this->db->order_by('ln.created_at', 'DESC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get lesson notes by class ID
     * 
     * @param int $class_id Class ID
     * @param array $filters Optional filters
     * @return array Array of lesson note objects
     */
    public function get_lesson_notes_by_class($class_id, $filters = array()) {
        if (empty($class_id)) {
            return array();
        }

        $this->db->select('ln.*, t.name as teacher_name, s.name as subject_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id', 'left');
        $this->db->join('subject s', 's.subject_id = ln.subject_id', 'left');
        $this->db->where('ln.class_id', $class_id);
        $this->db->where('ln.deleted_at', NULL);
        $this->db->where('ln.status', 'approved'); // Only approved for class view

        $this->db->order_by('ln.week_number', 'ASC');
        $this->db->order_by('ln.lesson_date', 'DESC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get lesson notes for approval/review
     * 
     * @param array $filters Optional filters (teacher_id, subject_id, class_id, term, week_number, status)
     * @return array Array of lesson note objects
     * 
     * Requirements: 9.1, 9.2, 9.3
     */
    public function get_lesson_notes_for_approval($filters = array()) {
        $this->db->select('ln.*, t.name as teacher_name, s.name as subject_name, c.name as class_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id', 'left');
        $this->db->join('subject s', 's.subject_id = ln.subject_id', 'left');
        $this->db->join('class c', 'c.class_id = ln.class_id', 'left');
        $this->db->where('ln.deleted_at', NULL);

        // Apply filters
        if (!empty($filters['teacher_id'])) {
            $this->db->where('ln.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('ln.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('ln.class_id', $filters['class_id']);
        }
        if (!empty($filters['term'])) {
            $this->db->where('ln.term', $filters['term']);
        }
        if (!empty($filters['week_number'])) {
            $this->db->where('ln.week_number', $filters['week_number']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('ln.status', $filters['status']);
        } else {
            // Default to pending and hod_reviewed if no status specified
            $this->db->where_in('ln.status', array('pending', 'hod_reviewed'));
        }

        $this->db->order_by('ln.created_at', 'DESC');
        
        $query = $this->db->get();
        return $query->result();
    }

    // ============================================
    // STATUS MANAGEMENT METHODS
    // ============================================

    /**
     * Submit lesson note for review
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param int $teacher_id Teacher ID (for ownership verification)
     * @return bool True on success, false on failure
     * 
     * Requirements: 1.9, 9.1
     */
    public function submit_for_review($lesson_note_id, $teacher_id = null) {
        if (empty($lesson_note_id)) {
            return false;
        }

        $existing = $this->get_lesson_note($lesson_note_id);
        if (!$existing) {
            return false;
        }

        // Verify ownership if teacher_id provided
        if ($teacher_id && $existing->teacher_id != $teacher_id) {
            return false;
        }

        // Can only submit drafts or revision_requested notes
        if (!in_array($existing->status, array('draft', 'revision_requested'))) {
            return false;
        }

        $this->db->where('lesson_note_id', $lesson_note_id);
        $result = $this->db->update('lesson_notes', array(
            'status' => 'pending',
            'updated_at' => date('Y-m-d H:i:s')
        ));

        if ($result) {
            $this->create_revision_record($lesson_note_id, 'submit', $teacher_id ?? $existing->teacher_id, array(
                'previous_status' => $existing->status,
                'new_status' => 'pending'
            ));
        }

        return $result;
    }

    /**
     * Approve a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param int $admin_id Admin ID
     * @param string|null $feedback Optional feedback
     * @return bool True on success, false on failure
     * 
     * Requirements: 9.4, 9.7
     */
    public function approve_lesson_note($lesson_note_id, $admin_id, $feedback = null) {
        if (empty($lesson_note_id) || empty($admin_id)) {
            return false;
        }

        $existing = $this->get_lesson_note($lesson_note_id);
        if (!$existing) {
            return false;
        }

        // Can only approve pending or hod_reviewed notes
        if (!in_array($existing->status, array('pending', 'hod_reviewed'))) {
            return false;
        }

        $this->db->where('lesson_note_id', $lesson_note_id);
        $result = $this->db->update('lesson_notes', array(
            'status' => 'approved',
            'admin_id' => $admin_id,
            'admin_review_date' => date('Y-m-d H:i:s'),
            'feedback' => $feedback,
            'updated_at' => date('Y-m-d H:i:s')
        ));

        if ($result) {
            $this->create_revision_record($lesson_note_id, 'approve', $admin_id, array(
                'previous_status' => $existing->status,
                'new_status' => 'approved',
                'feedback' => $feedback
            ), 'admin');

            // Create notification for teacher
            $this->create_notification($existing->teacher_id, 'teacher', 'Lesson Note Approved', 
                "Your lesson note '{$existing->title}' has been approved.", $lesson_note_id);
        }

        return $result;
    }

    /**
     * Decline a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param int $admin_id Admin ID
     * @param string $feedback Required feedback
     * @return bool True on success, false on failure
     * 
     * Requirements: 9.5, 9.6, 9.7
     */
    public function decline_lesson_note($lesson_note_id, $admin_id, $feedback) {
        if (empty($lesson_note_id) || empty($admin_id) || empty($feedback)) {
            return false;
        }

        $existing = $this->get_lesson_note($lesson_note_id);
        if (!$existing) {
            return false;
        }

        // Can only decline pending or hod_reviewed notes
        if (!in_array($existing->status, array('pending', 'hod_reviewed'))) {
            return false;
        }

        $this->db->where('lesson_note_id', $lesson_note_id);
        $result = $this->db->update('lesson_notes', array(
            'status' => 'declined',
            'admin_id' => $admin_id,
            'admin_review_date' => date('Y-m-d H:i:s'),
            'feedback' => $feedback,
            'updated_at' => date('Y-m-d H:i:s')
        ));

        if ($result) {
            $this->create_revision_record($lesson_note_id, 'decline', $admin_id, array(
                'previous_status' => $existing->status,
                'new_status' => 'declined',
                'feedback' => $feedback
            ), 'admin');

            // Create notification for teacher
            $this->create_notification($existing->teacher_id, 'teacher', 'Lesson Note Declined', 
                "Your lesson note '{$existing->title}' has been declined. Feedback: {$feedback}", $lesson_note_id);
        }

        return $result;
    }

    /**
     * Endorse a lesson note (HOD)
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param int $hod_id HOD teacher ID
     * @return bool True on success, false on failure
     * 
     * Requirements: 10.4
     */
    public function endorse_lesson_note($lesson_note_id, $hod_id) {
        if (empty($lesson_note_id) || empty($hod_id)) {
            return false;
        }

        $existing = $this->get_lesson_note($lesson_note_id);
        if (!$existing || $existing->status != 'pending') {
            return false;
        }

        $this->db->where('lesson_note_id', $lesson_note_id);
        $result = $this->db->update('lesson_notes', array(
            'status' => 'hod_reviewed',
            'hod_id' => $hod_id,
            'hod_review_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ));

        if ($result) {
            $this->create_revision_record($lesson_note_id, 'endorse', $hod_id, array(
                'previous_status' => 'pending',
                'new_status' => 'hod_reviewed'
            ), 'hod');

            // Create notification for admin
            $this->create_notification(1, 'admin', 'Lesson Note Endorsed', 
                "Lesson note '{$existing->title}' has been endorsed by HOD and is ready for approval.", $lesson_note_id);
        }

        return $result;
    }

    /**
     * Request revision (HOD)
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param int $hod_id HOD teacher ID
     * @param string $feedback Required feedback
     * @return bool True on success, false on failure
     * 
     * Requirements: 10.5, 10.6
     */
    public function request_revision($lesson_note_id, $hod_id, $feedback) {
        if (empty($lesson_note_id) || empty($hod_id) || empty($feedback)) {
            return false;
        }

        $existing = $this->get_lesson_note($lesson_note_id);
        if (!$existing || $existing->status != 'pending') {
            return false;
        }

        $this->db->where('lesson_note_id', $lesson_note_id);
        $result = $this->db->update('lesson_notes', array(
            'status' => 'revision_requested',
            'hod_id' => $hod_id,
            'hod_review_date' => date('Y-m-d H:i:s'),
            'hod_feedback' => $feedback,
            'updated_at' => date('Y-m-d H:i:s')
        ));

        if ($result) {
            $this->create_revision_record($lesson_note_id, 'request_revision', $hod_id, array(
                'previous_status' => 'pending',
                'new_status' => 'revision_requested',
                'feedback' => $feedback
            ), 'hod');

            // Create notification for teacher
            $this->create_notification($existing->teacher_id, 'teacher', 'Revision Requested', 
                "Your lesson note '{$existing->title}' requires revision. Feedback: {$feedback}", $lesson_note_id);
        }

        return $result;
    }

    // ============================================
    // COPY OPERATIONS
    // ============================================

    /**
     * Copy a lesson note
     * 
     * @param int $source_id Source lesson note ID
     * @param array $new_data New data (week_number, lesson_date, etc.)
     * @return int|false New lesson note ID or false on failure
     * 
     * Requirements: 11.4, 11.5, 11.7
     */
    public function copy_lesson_note($source_id, $new_data) {
        if (empty($source_id)) {
            return false;
        }

        $source = $this->get_lesson_note($source_id);
        if (!$source) {
            return false;
        }

        // Create new lesson note with source data
        $copy_data = array(
            'teacher_id' => $source->teacher_id,
            'class_id' => $source->class_id,
            'subject_id' => $source->subject_id,
            'strand_id' => $source->strand_id,
            'sub_strand_id' => $source->sub_strand_id,
            'content_standard_id' => $source->content_standard_id,
            'title' => $source->title,
            'description' => $source->description,
            'week_number' => $new_data['week_number'] ?? $source->week_number,
            'term' => $new_data['term'] ?? $source->term,
            'lesson_date' => $new_data['lesson_date'] ?? date('Y-m-d'),
            'lesson_objectives' => $source->lesson_objectives,
            'lesson_activities' => $source->lesson_activities,
            'lesson_content' => $source->lesson_content,
            'file_path' => $source->file_path,
            'file_name' => $source->file_name,
            'file_type' => $source->file_type,
            'status' => 'draft',
            'source_lesson_note_id' => $source_id
        );

        $this->db->insert('lesson_notes', $copy_data);
        $new_id = $this->db->insert_id();

        if ($new_id) {
            // Copy learning indicators
            if (!empty($source->learning_indicators)) {
                foreach ($source->learning_indicators as $indicator) {
                    $this->db->insert('lesson_note_indicators', array(
                        'lesson_note_id' => $new_id,
                        'indicator_id' => $indicator->indicator_id
                    ));
                }
            }

            // Copy core competencies
            if (!empty($source->core_competencies)) {
                foreach ($source->core_competencies as $competency) {
                    $this->db->insert('lesson_note_competencies', array(
                        'lesson_note_id' => $new_id,
                        'competency_id' => $competency->competency_id
                    ));
                }
            }

            // Copy resources
            if (!empty($source->resources)) {
                foreach ($source->resources as $resource) {
                    $this->db->insert('lesson_note_resources', array(
                        'lesson_note_id' => $new_id,
                        'resource_name' => $resource->resource_name,
                        'resource_details' => $resource->resource_details,
                        'quantity' => $resource->quantity,
                        'is_custom' => $resource->is_custom
                    ));
                }
            }

            // Copy assessments
            if (!empty($source->assessments)) {
                foreach ($source->assessments as $assessment) {
                    $this->db->insert('lesson_note_assessments', array(
                        'lesson_note_id' => $new_id,
                        'method_name' => $assessment->method_name,
                        'notes' => $assessment->notes,
                        'is_custom' => $assessment->is_custom
                    ));
                }
            }

            // Copy references
            if (!empty($source->references)) {
                foreach ($source->references as $reference) {
                    $this->db->insert('lesson_note_references', array(
                        'lesson_note_id' => $new_id,
                        'title' => $reference->title,
                        'author' => $reference->author,
                        'publisher' => $reference->publisher,
                        'year' => $reference->year,
                        'page_numbers' => $reference->page_numbers,
                        'url' => $reference->url,
                        'reference_type' => $reference->reference_type
                    ));
                }
            }

            // Create revision record
            $this->create_revision_record($new_id, 'create', $source->teacher_id, array(
                'action' => 'copy',
                'source_lesson_note_id' => $source_id
            ));
        }

        return $new_id;
    }

    // ============================================
    // JUNCTION TABLE OPERATIONS
    // ============================================

    /**
     * Save learning indicators for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param array $indicator_ids Array of indicator IDs
     * @return bool True on success
     * 
     * Requirements: 2.5
     */
    public function save_learning_indicators($lesson_note_id, $indicator_ids) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Delete existing indicators
        $this->db->where('lesson_note_id', $lesson_note_id);
        $this->db->delete('lesson_note_indicators');

        // Insert new indicators
        if (!empty($indicator_ids) && is_array($indicator_ids)) {
            foreach ($indicator_ids as $indicator_id) {
                $this->db->insert('lesson_note_indicators', array(
                    'lesson_note_id' => $lesson_note_id,
                    'indicator_id' => $indicator_id
                ));
            }
        }

        return true;
    }

    /**
     * Save core competencies for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param array $competency_ids Array of competency IDs
     * @return bool True on success
     * 
     * Requirements: 3.5
     */
    public function save_core_competencies($lesson_note_id, $competency_ids) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Delete existing competencies
        $this->db->where('lesson_note_id', $lesson_note_id);
        $this->db->delete('lesson_note_competencies');

        // Insert new competencies
        if (!empty($competency_ids) && is_array($competency_ids)) {
            foreach ($competency_ids as $competency_id) {
                $this->db->insert('lesson_note_competencies', array(
                    'lesson_note_id' => $lesson_note_id,
                    'competency_id' => $competency_id
                ));
            }
        }

        return true;
    }

    /**
     * Save teaching resources for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param array $resources Array of resource data
     * @return bool True on success
     * 
     * Requirements: 4.4
     */
    public function save_teaching_resources($lesson_note_id, $resources) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Delete existing resources
        $this->db->where('lesson_note_id', $lesson_note_id);
        $this->db->delete('lesson_note_resources');

        // Insert new resources
        if (!empty($resources) && is_array($resources)) {
            foreach ($resources as $resource) {
                $this->db->insert('lesson_note_resources', array(
                    'lesson_note_id' => $lesson_note_id,
                    'resource_name' => $resource['resource_name'],
                    'resource_details' => $resource['resource_details'] ?? null,
                    'quantity' => $resource['quantity'] ?? null,
                    'is_custom' => $resource['is_custom'] ?? 0
                ));
            }
        }

        return true;
    }

    /**
     * Save assessment methods for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param array $methods Array of assessment method data
     * @return bool True on success
     * 
     * Requirements: 5.5
     */
    public function save_assessment_methods($lesson_note_id, $methods) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Delete existing assessments
        $this->db->where('lesson_note_id', $lesson_note_id);
        $this->db->delete('lesson_note_assessments');

        // Insert new assessments
        if (!empty($methods) && is_array($methods)) {
            foreach ($methods as $method) {
                $this->db->insert('lesson_note_assessments', array(
                    'lesson_note_id' => $lesson_note_id,
                    'method_name' => $method['method_name'],
                    'notes' => $method['notes'] ?? null,
                    'is_custom' => $method['is_custom'] ?? 0
                ));
            }
        }

        return true;
    }

    /**
     * Save reference materials for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param array $references Array of reference data
     * @return bool True on success
     * 
     * Requirements: 6.5
     */
    public function save_reference_materials($lesson_note_id, $references) {
        if (empty($lesson_note_id)) {
            return false;
        }

        // Delete existing references
        $this->db->where('lesson_note_id', $lesson_note_id);
        $this->db->delete('lesson_note_references');

        // Insert new references
        if (!empty($references) && is_array($references)) {
            foreach ($references as $reference) {
                $this->db->insert('lesson_note_references', array(
                    'lesson_note_id' => $lesson_note_id,
                    'title' => $reference['title'],
                    'author' => $reference['author'] ?? null,
                    'publisher' => $reference['publisher'] ?? null,
                    'year' => $reference['year'] ?? null,
                    'page_numbers' => $reference['page_numbers'] ?? null,
                    'url' => $reference['url'] ?? null,
                    'reference_type' => $reference['reference_type'] ?? 'supplementary'
                ));
            }
        }

        return true;
    }

    // ============================================
    // PROGRESS TRACKING METHODS
    // ============================================

    /**
     * Get completion progress for a teacher
     * 
     * @param int $teacher_id Teacher ID
     * @param int $term Term (1-3)
     * @param int $year Year
     * @return array Progress data
     * 
     * Requirements: 12.1-12.8
     */
    public function get_completion_progress($teacher_id, $term, $year) {
        $progress = array(
            'total_expected' => 0,
            'submitted' => 0,
            'pending' => 0,
            'approved' => 0,
            'declined' => 0,
            'completion_percentage' => 0,
            'by_subject' => array(),
            'missing_weeks' => array(),
            'last_submission_date' => null,
            'last_approval_date' => null
        );

        // Get teacher's subjects and classes
        $this->db->select('ts.subject_id, ts.class_id, s.name as subject_name, c.name as class_name');
        $this->db->from('teacher_subject ts');
        $this->db->join('subject s', 's.subject_id = ts.subject_id');
        $this->db->join('class c', 'c.class_id = ts.class_id');
        $this->db->where('ts.teacher_id', $teacher_id);
        $assignments = $this->db->get()->result();

        // Calculate expected lesson notes (12 weeks per term per subject/class)
        foreach ($assignments as $assignment) {
            $expected = 12; // 12 weeks per term
            $progress['total_expected'] += $expected;
            
            $progress['by_subject'][$assignment->subject_id] = array(
                'subject_name' => $assignment->subject_name,
                'class_name' => $assignment->class_name,
                'expected' => $expected,
                'submitted' => 0,
                'approved' => 0
            );
        }

        // Get actual counts
        $this->db->select('status, subject_id, COUNT(*) as count, MAX(created_at) as last_date');
        $this->db->from('lesson_notes');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->where('term', $term);
        $this->db->where('YEAR(lesson_date)', $year);
        $this->db->where('deleted_at', NULL);
        $this->db->group_by('status, subject_id');
        $counts = $this->db->get()->result();

        foreach ($counts as $count) {
            $progress['submitted'] += $count->count;
            
            switch ($count->status) {
                case 'pending':
                case 'hod_reviewed':
                    $progress['pending'] += $count->count;
                    break;
                case 'approved':
                    $progress['approved'] += $count->count;
                    $progress['last_approval_date'] = $count->last_date;
                    break;
                case 'declined':
                    $progress['declined'] += $count->count;
                    break;
            }

            if (isset($progress['by_subject'][$count->subject_id])) {
                $progress['by_subject'][$count->subject_id]['submitted'] += $count->count;
                if ($count->status == 'approved') {
                    $progress['by_subject'][$count->subject_id]['approved'] += $count->count;
                }
            }
        }

        // Get last submission date
        $this->db->select('MAX(created_at) as last_submission');
        $this->db->from('lesson_notes');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->where('term', $term);
        $this->db->where('YEAR(lesson_date)', $year);
        $this->db->where('deleted_at', NULL);
        $last = $this->db->get()->row();
        $progress['last_submission_date'] = $last->last_submission ?? null;

        // Calculate completion percentage
        if ($progress['total_expected'] > 0) {
            $progress['completion_percentage'] = round(($progress['approved'] / $progress['total_expected']) * 100, 1);
        }

        // Find missing weeks
        $progress['missing_weeks'] = $this->get_missing_lesson_notes($teacher_id, $term, $year);

        return $progress;
    }

    /**
     * Get missing lesson notes for a teacher
     * 
     * @param int $teacher_id Teacher ID
     * @param int $term Term
     * @param int $year Year
     * @return array Array of missing week/subject combinations
     * 
     * Requirements: 12.6
     */
    public function get_missing_lesson_notes($teacher_id, $term, $year) {
        $missing = array();

        // Get existing lesson notes
        $this->db->select('subject_id, week_number');
        $this->db->from('lesson_notes');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->where('term', $term);
        $this->db->where('YEAR(lesson_date)', $year);
        $this->db->where('deleted_at', NULL);
        $this->db->where_in('status', array('pending', 'hod_reviewed', 'approved'));
        $existing = $this->db->get()->result();

        $existing_keys = array();
        foreach ($existing as $e) {
            $existing_keys[] = $e->subject_id . '_' . $e->week_number;
        }

        // Get teacher's subjects
        $this->db->select('ts.subject_id, s.name as subject_name');
        $this->db->from('teacher_subject ts');
        $this->db->join('subject s', 's.subject_id = ts.subject_id');
        $this->db->where('ts.teacher_id', $teacher_id);
        $subjects = $this->db->get()->result();

        // Check each week for each subject
        foreach ($subjects as $subject) {
            for ($week = 1; $week <= 12; $week++) {
                $key = $subject->subject_id . '_' . $week;
                if (!in_array($key, $existing_keys)) {
                    $missing[] = array(
                        'subject_id' => $subject->subject_id,
                        'subject_name' => $subject->subject_name,
                        'week_number' => $week
                    );
                }
            }
        }

        return $missing;
    }

    /**
     * Get last submission date for a teacher
     * 
     * @param int $teacher_id Teacher ID
     * @return string|null Last submission date or null
     */
    public function get_last_submission($teacher_id) {
        $this->db->select('created_at');
        $this->db->from('lesson_notes');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->where('deleted_at', NULL);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get()->row();
        
        return $result ? $result->created_at : null;
    }

    /**
     * Get last approval date for a teacher
     * 
     * @param int $teacher_id Teacher ID
     * @return string|null Last approval date or null
     */
    public function get_last_approval($teacher_id) {
        $this->db->select('admin_review_date');
        $this->db->from('lesson_notes');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->where('status', 'approved');
        $this->db->where('deleted_at', NULL);
        $this->db->order_by('admin_review_date', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get()->row();
        
        return $result ? $result->admin_review_date : null;
    }

    // ============================================
    // REPORTING METHODS
    // ============================================

    /**
     * Get compliance report data
     * 
     * @param array $filters Filters (term, year, teacher_id, subject_id, class_id)
     * @return array Compliance report data
     * 
     * Requirements: 13.1-13.8
     */
    public function get_compliance_report($filters = array()) {
        $report = array(
            'overall_compliance' => 0,
            'by_teacher' => array(),
            'by_subject' => array(),
            'by_class' => array(),
            'zero_submission_teachers' => array(),
            'high_decline_rate_teachers' => array()
        );

        // Build base query
        $this->db->select('t.teacher_id, t.name as teacher_name, 
            s.subject_id, s.name as subject_name,
            c.class_id, c.name as class_name,
            COUNT(*) as total,
            SUM(CASE WHEN ln.status = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN ln.status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN ln.status = "declined" THEN 1 ELSE 0 END) as declined,
            SUM(CASE WHEN ln.status = "hod_reviewed" THEN 1 ELSE 0 END) as hod_reviewed');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id');
        $this->db->join('subject s', 's.subject_id = ln.subject_id');
        $this->db->join('class c', 'c.class_id = ln.class_id');
        $this->db->where('ln.deleted_at', NULL);

        // Apply filters
        if (!empty($filters['term'])) {
            $this->db->where('ln.term', $filters['term']);
        }
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(ln.lesson_date)', $filters['year']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('ln.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('ln.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('ln.class_id', $filters['class_id']);
        }

        $this->db->group_by('ln.teacher_id, ln.subject_id, ln.class_id');
        $results = $this->db->get()->result();

        $total_expected = 0;
        $total_approved = 0;

        foreach ($results as $row) {
            $expected = 12; // 12 weeks per term
            $total_expected += $expected;
            $total_approved += $row->approved;

            $compliance_rate = $expected > 0 ? round(($row->approved / $expected) * 100, 1) : 0;
            $decline_rate = $row->total > 0 ? round(($row->declined / $row->total) * 100, 1) : 0;

            // By teacher
            if (!isset($report['by_teacher'][$row->teacher_id])) {
                $report['by_teacher'][$row->teacher_id] = array(
                    'teacher_name' => $row->teacher_name,
                    'total_submissions' => 0,
                    'approved' => 0,
                    'declined' => 0,
                    'compliance_rate' => 0
                );
            }
            $report['by_teacher'][$row->teacher_id]['total_submissions'] += $row->total;
            $report['by_teacher'][$row->teacher_id]['approved'] += $row->approved;
            $report['by_teacher'][$row->teacher_id]['declined'] += $row->declined;

            // By subject
            if (!isset($report['by_subject'][$row->subject_id])) {
                $report['by_subject'][$row->subject_id] = array(
                    'subject_name' => $row->subject_name,
                    'total_submissions' => 0,
                    'approved' => 0
                );
            }
            $report['by_subject'][$row->subject_id]['total_submissions'] += $row->total;
            $report['by_subject'][$row->subject_id]['approved'] += $row->approved;

            // By class
            if (!isset($report['by_class'][$row->class_id])) {
                $report['by_class'][$row->class_id] = array(
                    'class_name' => $row->class_name,
                    'total_submissions' => 0,
                    'approved' => 0
                );
            }
            $report['by_class'][$row->class_id]['total_submissions'] += $row->total;
            $report['by_class'][$row->class_id]['approved'] += $row->approved;

            // Track high decline rate teachers
            if ($decline_rate > 30) {
                $report['high_decline_rate_teachers'][] = array(
                    'teacher_id' => $row->teacher_id,
                    'teacher_name' => $row->teacher_name,
                    'decline_rate' => $decline_rate
                );
            }
        }

        // Calculate overall compliance
        $report['overall_compliance'] = $total_expected > 0 ? 
            round(($total_approved / $total_expected) * 100, 1) : 0;

        // Calculate teacher compliance rates
        foreach ($report['by_teacher'] as $teacher_id => &$teacher_data) {
            $teacher_data['compliance_rate'] = $teacher_data['total_submissions'] > 0 ?
                round(($teacher_data['approved'] / $teacher_data['total_submissions']) * 100, 1) : 0;
        }

        // Find zero submission teachers
        $subquery = '(SELECT DISTINCT teacher_id FROM lesson_notes WHERE deleted_at IS NULL';
        if (!empty($filters['term'])) {
            $subquery .= ' AND term = ' . $this->db->escape($filters['term']);
        }
        if (!empty($filters['year'])) {
            $subquery .= ' AND YEAR(lesson_date) = ' . $this->db->escape($filters['year']);
        }
        $subquery .= ')';
        
        $this->db->select('t.teacher_id, t.name as teacher_name');
        $this->db->from('teacher t');
        $this->db->where("t.teacher_id NOT IN $subquery", NULL, FALSE);
        $zero_submissions = $this->db->get()->result();

        foreach ($zero_submissions as $teacher) {
            $report['zero_submission_teachers'][] = array(
                'teacher_id' => $teacher->teacher_id,
                'teacher_name' => $teacher->teacher_name
            );
        }

        return $report;
    }

    /**
     * Get submission statistics
     * 
     * @param array $filters Filters
     * @return array Statistics data
     */
    public function get_submission_stats($filters = array()) {
        $stats = array(
            'total' => 0,
            'by_status' => array(),
            'by_term' => array()
        );

        $this->db->select('status, term, COUNT(*) as count');
        $this->db->from('lesson_notes');
        $this->db->where('deleted_at', NULL);

        if (!empty($filters['year'])) {
            $this->db->where('YEAR(lesson_date)', $filters['year']);
        }

        $this->db->group_by('status, term');
        $results = $this->db->get()->result();

        foreach ($results as $row) {
            $stats['total'] += $row->count;
            
            if (!isset($stats['by_status'][$row->status])) {
                $stats['by_status'][$row->status] = 0;
            }
            $stats['by_status'][$row->status] += $row->count;

            if (!isset($stats['by_term'][$row->term])) {
                $stats['by_term'][$row->term] = array();
            }
            $stats['by_term'][$row->term][$row->status] = $row->count;
        }

        return $stats;
    }

    /**
     * Get teacher compliance list for the compliance report table
     * 
     * @param array $filters Filters
     * @return array Array of teacher compliance data
     */
    public function get_teacher_compliance_list($filters = array()) {
        // Build query to get teacher compliance data
        $this->db->select('
            t.teacher_id,
            t.name as teacher_name,
            COUNT(DISTINCT ln.subject_id) as subjects_count,
            COUNT(DISTINCT CASE WHEN ln.status != "declined" THEN ln.lesson_note_id END) as expected_notes,
            COUNT(ln.lesson_note_id) as submitted_notes,
            SUM(CASE WHEN ln.status = "approved" THEN 1 ELSE 0 END) as approved_notes,
            SUM(CASE WHEN ln.status = "declined" THEN 1 ELSE 0 END) as declined_notes,
            ROUND((SUM(CASE WHEN ln.status = "approved" THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT CASE WHEN ln.status != "declined" THEN ln.lesson_note_id END), 0)) * 100, 1) as compliance_rate
        ', FALSE);
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id');
        $this->db->where('ln.deleted_at', NULL);

        // Apply filters
        if (!empty($filters['term'])) {
            $this->db->where('ln.term', $filters['term']);
        }
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(ln.lesson_date)', $filters['year']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('t.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('ln.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('ln.class_id', $filters['class_id']);
        }

        $this->db->group_by('t.teacher_id');
        $this->db->order_by('compliance_rate', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Get submission summary statistics
     * 
     * @param array $filters Filters
     * @return array Summary statistics
     */
    public function get_submission_summary($filters = array()) {
        $summary = array(
            'total_expected' => 0,
            'total_submitted' => 0,
            'compliance_rate' => 0,
            'missing_submissions' => 0
        );

        // Get total teachers and expected submissions
        $this->db->select('COUNT(DISTINCT teacher_id) as teacher_count');
        $this->db->from('teacher');
        $teacher_count = $this->db->get()->row()->teacher_count;

        // Assuming 12 weeks per term and average of 3 subjects per teacher
        $summary['total_expected'] = $teacher_count * 12 * 3;

        // Get actual submissions
        $this->db->select('COUNT(*) as total');
        $this->db->from('lesson_notes');
        $this->db->where('deleted_at', NULL);

        if (!empty($filters['term'])) {
            $this->db->where('term', $filters['term']);
        }
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(lesson_date)', $filters['year']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('class_id', $filters['class_id']);
        }

        $summary['total_submitted'] = $this->db->get()->row()->total;
        $summary['missing_submissions'] = max(0, $summary['total_expected'] - $summary['total_submitted']);
        $summary['compliance_rate'] = $summary['total_expected'] > 0 ? 
            round(($summary['total_submitted'] / $summary['total_expected']) * 100, 1) : 0;

        return $summary;
    }

    /**
     * Get teachers with zero submissions
     * 
     * @param array $filters Filters
     * @return array Array of teachers with no submissions
     */
    public function get_zero_submission_teachers($filters = array()) {
        // Build subquery for teachers who have submitted
        $subquery = '(SELECT DISTINCT teacher_id FROM lesson_notes WHERE deleted_at IS NULL';
        if (!empty($filters['term'])) {
            $subquery .= ' AND term = ' . $this->db->escape($filters['term']);
        }
        if (!empty($filters['year'])) {
            $subquery .= ' AND YEAR(lesson_date) = ' . $this->db->escape($filters['year']);
        }
        $subquery .= ')';

        // Get teachers not in the subquery
        $this->db->select('t.teacher_id, t.name, COUNT(DISTINCT s.subject_id) as subjects_count');
        $this->db->from('teacher t');
        $this->db->join('subject s', 's.teacher_id = t.teacher_id', 'left');
        $this->db->where("t.teacher_id NOT IN $subquery", NULL, FALSE);
        $this->db->group_by('t.teacher_id');

        return $this->db->get()->result();
    }

    /**
     * Get teachers with high decline rates
     * 
     * @param array $filters Filters
     * @return array Array of teachers with high decline rates
     */
    public function get_high_decline_teachers($filters = array()) {
        $this->db->select('
            t.teacher_id,
            t.name,
            COUNT(ln.lesson_note_id) as submitted_notes,
            SUM(CASE WHEN ln.status = "declined" THEN 1 ELSE 0 END) as declined_notes,
            ROUND((SUM(CASE WHEN ln.status = "declined" THEN 1 ELSE 0 END) / COUNT(ln.lesson_note_id)) * 100, 1) as decline_rate
        ');
        $this->db->from('teacher t');
        $this->db->join('lesson_notes ln', 'ln.teacher_id = t.teacher_id');
        $this->db->where('ln.deleted_at', NULL);

        // Apply filters
        if (!empty($filters['term'])) {
            $this->db->where('ln.term', $filters['term']);
        }
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(ln.lesson_date)', $filters['year']);
        }

        $this->db->group_by('t.teacher_id');
        $this->db->having('decline_rate >', 30); // More than 30% decline rate
        $this->db->order_by('decline_rate', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Get weekly breakdown of submissions
     * 
     * @param array $filters Filters
     * @return array Array of weekly data
     */
    public function get_weekly_breakdown($filters = array()) {
        $this->db->select('
            week_number,
            COUNT(*) as submitted,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined
        ');
        $this->db->from('lesson_notes');
        $this->db->where('deleted_at', NULL);

        // Apply filters
        if (!empty($filters['term'])) {
            $this->db->where('term', $filters['term']);
        }
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(lesson_date)', $filters['year']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('class_id', $filters['class_id']);
        }

        $this->db->group_by('week_number');
        $this->db->order_by('week_number', 'ASC');
        $results = $this->db->get()->result();

        // Calculate expected and compliance rate for each week
        foreach ($results as $week) {
            $week->expected = 100; // Placeholder - should be calculated based on teachers/subjects
            $week->compliance_rate = $week->expected > 0 ? 
                round(($week->approved / $week->expected) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Get compliance trend data
     * 
     * @param array $filters Filters
     * @return array Array of trend data
     */
    public function get_compliance_trend($filters = array()) {
        $this->db->select('
            term,
            COUNT(*) as submitted,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
            ROUND((SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as compliance_rate
        ');
        $this->db->from('lesson_notes');
        $this->db->where('deleted_at', NULL);

        if (!empty($filters['year'])) {
            $this->db->where('YEAR(lesson_date)', $filters['year']);
        }

        $this->db->group_by('term');
        $this->db->order_by('term', 'ASC');

        return $this->db->get()->result();
    }

    // ============================================
    // HELPER METHODS FOR RELATED DATA
    // ============================================

    /**
     * Get learning indicators for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return array Array of indicator objects
     */
    private function get_lesson_note_indicators($lesson_note_id) {
        $this->db->select('lni.*, cli.code, cli.description');
        $this->db->from('lesson_note_indicators lni');
        $this->db->join('curriculum_learning_indicators cli', 'cli.indicator_id = lni.indicator_id');
        $this->db->where('lni.lesson_note_id', $lesson_note_id);
        return $this->db->get()->result();
    }

    /**
     * Get core competencies for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return array Array of competency objects
     */
    private function get_lesson_note_competencies($lesson_note_id) {
        $this->db->select('lnc.*, cc.name, cc.description');
        $this->db->from('lesson_note_competencies lnc');
        $this->db->join('core_competencies cc', 'cc.competency_id = lnc.competency_id');
        $this->db->where('lnc.lesson_note_id', $lesson_note_id);
        return $this->db->get()->result();
    }

    /**
     * Get teaching resources for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return array Array of resource objects
     */
    private function get_lesson_note_resources($lesson_note_id) {
        $this->db->where('lesson_note_id', $lesson_note_id);
        return $this->db->get('lesson_note_resources')->result();
    }

    /**
     * Get assessment methods for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return array Array of assessment objects
     */
    private function get_lesson_note_assessments($lesson_note_id) {
        $this->db->where('lesson_note_id', $lesson_note_id);
        return $this->db->get('lesson_note_assessments')->result();
    }

    /**
     * Get reference materials for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return array Array of reference objects
     */
    private function get_lesson_note_references($lesson_note_id) {
        $this->db->where('lesson_note_id', $lesson_note_id);
        return $this->db->get('lesson_note_references')->result();
    }

    // ============================================
    // REVISION HISTORY METHODS
    // ============================================

    /**
     * Create a revision record
     * 
     * @param int $lesson_note_id Lesson note ID
     * @param string $action Action taken
     * @param int $user_id User ID
     * @param array $data Additional data
     * @param string $user_type User type (teacher, hod, admin)
     * @return int|false Revision ID or false
     * 
     * Requirements: 18.1-18.4
     */
    public function create_revision_record($lesson_note_id, $action, $user_id, $data = array(), $user_type = 'teacher') {
        if (empty($lesson_note_id) || empty($action) || empty($user_id)) {
            return false;
        }

        $revision_data = array(
            'lesson_note_id' => $lesson_note_id,
            'user_id' => $user_id,
            'user_type' => $user_type,
            'action' => $action,
            'changed_fields' => isset($data['changed_fields']) ? $data['changed_fields'] : null,
            'feedback' => isset($data['feedback']) ? $data['feedback'] : null,
            'previous_status' => isset($data['previous_status']) ? $data['previous_status'] : null,
            'new_status' => isset($data['new_status']) ? $data['new_status'] : null
        );

        $this->db->insert('lesson_note_revisions', $revision_data);
        return $this->db->insert_id();
    }

    /**
     * Get revision history for a lesson note
     * 
     * @param int $lesson_note_id Lesson note ID
     * @return array Array of revision objects
     * 
     * Requirements: 18.5-18.7
     */
    public function get_revision_history($lesson_note_id) {
        if (empty($lesson_note_id)) {
            return array();
        }

        $this->db->where('lesson_note_id', $lesson_note_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('lesson_note_revisions')->result();
    }

    // ============================================
    // NOTIFICATION METHODS
    // ============================================

    /**
     * Create a notification
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int $reference_id Reference ID (lesson note ID)
     * @return int|false Notification ID or false
     * 
     * Requirements: 17.1-17.5
     */
    public function create_notification($user_id, $user_type, $title, $message, $reference_id) {
        if (empty($user_id) || empty($title) || empty($message)) {
            return false;
        }

        $notification_data = array(
            'user_id' => $user_id,
            'user_type' => $user_type,
            'title' => $title,
            'message' => $message,
            'reference_id' => $reference_id
        );

        $this->db->insert('lesson_note_notifications', $notification_data);
        return $this->db->insert_id();
    }

    /**
     * Get notifications for a user
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param bool $unread_only Get only unread notifications
     * @return array Array of notification objects
     * 
     * Requirements: 17.6
     */
    public function get_notifications($user_id, $user_type, $unread_only = false) {
        $this->db->where('user_id', $user_id);
        $this->db->where('user_type', $user_type);
        
        if ($unread_only) {
            $this->db->where('is_read', 0);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(50);
        return $this->db->get('lesson_note_notifications')->result();
    }

    /**
     * Get unread notification count
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return int Unread count
     * 
     * Requirements: 17.6
     */
    public function get_unread_notification_count($user_id, $user_type) {
        $this->db->where('user_id', $user_id);
        $this->db->where('user_type', $user_type);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results('lesson_note_notifications');
    }

    /**
     * Mark notification as read
     * 
     * @param int $notification_id Notification ID
     * @return bool True on success
     * 
     * Requirements: 17.7
     */
    public function mark_notification_read($notification_id) {
        if (empty($notification_id)) {
            return false;
        }

        $this->db->where('notification_id', $notification_id);
        return $this->db->update('lesson_note_notifications', array('is_read' => 1));
    }

    /**
     * Mark all notifications as read for a user
     * 
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool True on success
     */
    public function mark_all_notifications_read($user_id, $user_type) {
        $this->db->where('user_id', $user_id);
        $this->db->where('user_type', $user_type);
        return $this->db->update('lesson_note_notifications', array('is_read' => 1));
    }

    // ============================================
    // BULK OPERATIONS
    // ============================================

    /**
     * Bulk approve lesson notes
     * 
     * @param array $lesson_note_ids Array of lesson note IDs
     * @param int $admin_id Admin ID
     * @return array Result with success and failed counts
     * 
     * Requirements: 19.1, 19.5
     */
    public function bulk_approve($lesson_note_ids, $admin_id) {
        $result = array(
            'success' => 0,
            'failed' => 0
        );

        // Limit to 50 records
        if (count($lesson_note_ids) > 50) {
            return $result;
        }

        foreach ($lesson_note_ids as $lesson_note_id) {
            if ($this->approve_lesson_note($lesson_note_id, $admin_id)) {
                $result['success']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Bulk decline lesson notes
     * 
     * @param array $lesson_note_ids Array of lesson note IDs
     * @param int $admin_id Admin ID
     * @param string $feedback Shared feedback
     * @return array Result with success and failed counts
     * 
     * Requirements: 19.2, 19.5
     */
    public function bulk_decline($lesson_note_ids, $admin_id, $feedback) {
        $result = array(
            'success' => 0,
            'failed' => 0
        );

        // Limit to 50 records
        if (count($lesson_note_ids) > 50) {
            return $result;
        }

        foreach ($lesson_note_ids as $lesson_note_id) {
            if ($this->decline_lesson_note($lesson_note_id, $admin_id, $feedback)) {
                $result['success']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    // ============================================
    // PENDING LESSON NOTES FOR APPROVAL
    // ============================================

    /**
     * Get pending lesson notes for admin approval
     * 
     * @param array $filters Optional filters
     * @return array Array of pending lesson notes
     * 
     * Requirements: 9.2, 9.8
     */
    public function get_pending_for_approval($filters = array()) {
        $this->db->select('ln.*, t.name as teacher_name, s.name as subject_name, c.name as class_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id');
        $this->db->join('subject s', 's.subject_id = ln.subject_id');
        $this->db->join('class c', 'c.class_id = ln.class_id');
        $this->db->where_in('ln.status', array('pending', 'hod_reviewed'));
        $this->db->where('ln.deleted_at', NULL);

        // Apply filters
        if (!empty($filters['teacher_id'])) {
            $this->db->where('ln.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('ln.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $this->db->where('ln.class_id', $filters['class_id']);
        }
        if (!empty($filters['term'])) {
            $this->db->where('ln.term', $filters['term']);
        }
        if (!empty($filters['week_number'])) {
            $this->db->where('ln.week_number', $filters['week_number']);
        }

        $this->db->order_by('ln.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get pending lesson notes for HOD review
     * 
     * @param int $hod_id HOD teacher ID
     * @return array Array of pending lesson notes
     * 
     * Requirements: 10.2
     */
    public function get_pending_for_hod($hod_id) {
        // Get subjects managed by HOD
        $this->db->select('subject_id');
        $this->db->from('hod_subjects');
        $this->db->where('teacher_id', $hod_id);
        $subjects = $this->db->get()->result();
        
        if (empty($subjects)) {
            return array();
        }

        $subject_ids = array_column($subjects, 'subject_id');

        $this->db->select('ln.*, t.name as teacher_name, s.name as subject_name, c.name as class_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('teacher t', 't.teacher_id = ln.teacher_id');
        $this->db->join('subject s', 's.subject_id = ln.subject_id');
        $this->db->join('class c', 'c.class_id = ln.class_id');
        $this->db->where('ln.status', 'pending');
        $this->db->where('ln.deleted_at', NULL);
        $this->db->where_in('ln.subject_id', $subject_ids);
        $this->db->order_by('ln.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Get approved lesson notes for students
     * 
     * @param int $class_id Class ID
     * @param array $subject_ids Array of subject IDs
     * @return array Array of approved lesson notes
     * 
     * Requirements: 15.1-15.6
     */
    public function get_approved_for_students($class_id, $subject_ids = array()) {
        $this->db->select('ln.lesson_note_id, ln.title, ln.week_number, ln.term, ln.lesson_date,
            ln.file_path, ln.file_name, ln.file_type, s.name as subject_name');
        $this->db->from('lesson_notes ln');
        $this->db->join('subject s', 's.subject_id = ln.subject_id');
        $this->db->where('ln.class_id', $class_id);
        $this->db->where('ln.status', 'approved');
        $this->db->where('ln.deleted_at', NULL);

        if (!empty($subject_ids)) {
            $this->db->where_in('ln.subject_id', $subject_ids);
        }

        $this->db->order_by('s.name', 'ASC');
        $this->db->order_by('ln.week_number', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Get status counts
     * 
     * @param array $filters Optional filters
     * @return array Status counts
     * 
     * Requirements: 9.9
     */
    public function get_status_counts($filters = array()) {
        $counts = array(
            'pending' => 0,
            'hod_reviewed' => 0,
            'approved' => 0,
            'declined' => 0,
            'revision_requested' => 0,
            'draft' => 0
        );

        $this->db->select('status, COUNT(*) as count');
        $this->db->from('lesson_notes');
        $this->db->where('deleted_at', NULL);

        if (!empty($filters['teacher_id'])) {
            $this->db->where('teacher_id', $filters['teacher_id']);
        }

        $this->db->group_by('status');
        $results = $this->db->get()->result();

        foreach ($results as $row) {
            $counts[$row->status] = $row->count;
        }

        return $counts;
    }
}
