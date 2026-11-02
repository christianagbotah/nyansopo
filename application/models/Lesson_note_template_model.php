<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lesson note template model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Lesson_note_template_model extends MY_Model {
    
    private $template_table = 'lesson_note_templates';
    private $tag_table = 'lesson_note_template_tags';
    private $tag_assignment_table = 'lesson_note_template_tag_assignments';
    private $favorite_table = 'lesson_note_template_favorites';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get templates for a teacher
     * Includes both their own templates and public templates
     */
    public function get_teacher_templates($teacher_id, $filters = []) {
        $this->db->select('t.*, 
            teacher.name as teacher_name,
            subject.name as subject_name,
            class.name as class_name,
            COUNT(DISTINCT f.favorite_id) as is_favorited,
            GROUP_CONCAT(DISTINCT tag.tag_name ORDER BY tag.tag_name SEPARATOR ", ") as tags,
            GROUP_CONCAT(DISTINCT tag.tag_color ORDER BY tag.tag_name SEPARATOR ",") as tag_colors');
        $this->db->from($this->template_table . ' t');
        $this->db->join('teacher', 'teacher.teacher_id = t.teacher_id', 'left');
        $this->db->join('subject', 'subject.subject_id = t.subject_id', 'left');
        $this->db->join('class', 'class.class_id = t.class_id', 'left');
        $this->db->join($this->favorite_table . ' f', 'f.template_id = t.template_id AND f.teacher_id = ' . (int)$teacher_id, 'left');
        $this->db->join($this->tag_assignment_table . ' ta', 'ta.template_id = t.template_id', 'left');
        $this->db->join($this->tag_table . ' tag', 'tag.tag_id = ta.tag_id', 'left');
        
        // Show own templates + public templates
        $this->db->group_start();
        $this->db->where('t.teacher_id', $teacher_id);
        $this->db->or_where('t.is_public', 1);
        $this->db->group_end();
        
        $this->db->where('t.is_active', 1);
        
        // Apply filters
        if (!empty($filters['subject_id'])) {
            $this->db->group_start();
            $this->db->where('t.subject_id', $filters['subject_id']);
            $this->db->or_where('t.subject_id IS NULL');
            $this->db->group_end();
        }
        
        if (!empty($filters['class_id'])) {
            $this->db->group_start();
            $this->db->where('t.class_id', $filters['class_id']);
            $this->db->or_where('t.class_id IS NULL');
            $this->db->group_end();
        }
        
        if (!empty($filters['tag_id'])) {
            $this->db->where('ta.tag_id', $filters['tag_id']);
        }
        
        if (!empty($filters['favorites_only'])) {
            $this->db->where('f.favorite_id IS NOT NULL');
        }
        
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('t.template_name', $search);
            $this->db->or_like('t.description', $search);
            $this->db->group_end();
        }
        
        $this->db->group_by('t.template_id');
        
        // Sorting
        $sort_by = $filters['sort_by'] ?? 'usage_count';
        $sort_order = $filters['sort_order'] ?? 'DESC';
        
        if ($sort_by === 'usage_count') {
            $this->db->order_by('t.usage_count', $sort_order);
        } elseif ($sort_by === 'name') {
            $this->db->order_by('t.template_name', $sort_order);
        } elseif ($sort_by === 'created_at') {
            $this->db->order_by('t.created_at', $sort_order);
        }
        
        $this->db->order_by('t.template_id', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get single template by ID
     */
    public function get_template($template_id, $teacher_id = null) {
        $this->db->select('t.*, 
            teacher.name as teacher_name,
            subject.name as subject_name,
            class.name as class_name');
        $this->db->from($this->template_table . ' t');
        $this->db->join('teacher', 'teacher.teacher_id = t.teacher_id', 'left');
        $this->db->join('subject', 'subject.subject_id = t.subject_id', 'left');
        $this->db->join('class', 'class.class_id = t.class_id', 'left');
        $this->db->where('t.template_id', $template_id);
        
        // Access control: own template or public template
        if ($teacher_id) {
            $this->db->group_start();
            $this->db->where('t.teacher_id', $teacher_id);
            $this->db->or_where('t.is_public', 1);
            $this->db->group_end();
        }
        
        $template = $this->db->get()->row();
        
        if ($template) {
            // Get tags
            $template->tags = $this->get_template_tags($template_id);
        }
        
        return $template;
    }
    
    /**
     * Create new template
     */
    public function create_template($data) {
        $template_data = [
            'teacher_id' => $data['teacher_id'],
            'template_name' => $data['template_name'],
            'description' => $data['description'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'teaching_methods' => isset($data['teaching_methods']) ? json_encode($data['teaching_methods']) : null,
            'learning_activities' => isset($data['learning_activities']) ? json_encode($data['learning_activities']) : null,
            'assessment_methods' => isset($data['assessment_methods']) ? json_encode($data['assessment_methods']) : null,
            'resources' => isset($data['resources']) ? json_encode($data['resources']) : null,
            'differentiation_strategies' => $data['differentiation_strategies'] ?? null,
            'homework_assignment' => $data['homework_assignment'] ?? null,
            'reflection_notes' => $data['reflection_notes'] ?? null,
            'is_public' => $data['is_public'] ?? 0,
            'created_by' => $data['teacher_id'],
            'is_active' => 1
        ];
        
        $this->db->insert($this->template_table, $template_data);
        $template_id = $this->db->insert_id();
        
        // Add tags if provided
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            $this->assign_tags($template_id, $data['tag_ids']);
        }
        
        return $template_id;
    }
    
    /**
     * Update template
     */
    public function update_template($template_id, $data, $teacher_id) {
        // Verify ownership
        $template = $this->get_template($template_id);
        if (!$template || $template->teacher_id != $teacher_id) {
            return false;
        }
        
        $template_data = [
            'template_name' => $data['template_name'],
            'description' => $data['description'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'teaching_methods' => isset($data['teaching_methods']) ? json_encode($data['teaching_methods']) : null,
            'learning_activities' => isset($data['learning_activities']) ? json_encode($data['learning_activities']) : null,
            'assessment_methods' => isset($data['assessment_methods']) ? json_encode($data['assessment_methods']) : null,
            'resources' => isset($data['resources']) ? json_encode($data['resources']) : null,
            'differentiation_strategies' => $data['differentiation_strategies'] ?? null,
            'homework_assignment' => $data['homework_assignment'] ?? null,
            'reflection_notes' => $data['reflection_notes'] ?? null,
            'is_public' => $data['is_public'] ?? 0,
            'updated_by' => $teacher_id
        ];
        
        $this->db->where('template_id', $template_id);
        $this->db->update($this->template_table, $template_data);
        
        // Update tags
        if (isset($data['tag_ids'])) {
            $this->db->where('template_id', $template_id);
            $this->db->delete($this->tag_assignment_table);
            
            if (is_array($data['tag_ids']) && !empty($data['tag_ids'])) {
                $this->assign_tags($template_id, $data['tag_ids']);
            }
        }
        
        return true;
    }
    
    /**
     * Delete template (soft delete)
     */
    public function delete_template($template_id, $teacher_id) {
        // Verify ownership
        $template = $this->get_template($template_id);
        if (!$template || $template->teacher_id != $teacher_id) {
            return false;
        }
        
        $this->db->where('template_id', $template_id);
        $this->db->update($this->template_table, ['is_active' => 0]);
        
        return true;
    }
    
    /**
     * Increment usage count
     */
    public function increment_usage($template_id) {
        $this->db->where('template_id', $template_id);
        $this->db->set('usage_count', 'usage_count + 1', FALSE);
        $this->db->update($this->template_table);
    }
    
    /**
     * Toggle favorite
     */
    public function toggle_favorite($template_id, $teacher_id) {
        $existing = $this->db->get_where($this->favorite_table, [
            'template_id' => $template_id,
            'teacher_id' => $teacher_id
        ])->row();
        
        if ($existing) {
            // Remove favorite
            $this->db->where('favorite_id', $existing->favorite_id);
            $this->db->delete($this->favorite_table);
            return false; // Not favorited
        } else {
            // Add favorite
            $this->db->insert($this->favorite_table, [
                'template_id' => $template_id,
                'teacher_id' => $teacher_id
            ]);
            return true; // Favorited
        }
    }
    
    /**
     * Get all tags
     */
    public function get_all_tags() {
        $this->db->order_by('tag_name', 'ASC');
        return $this->db->get($this->tag_table)->result();
    }
    
    /**
     * Get tags for a template
     */
    public function get_template_tags($template_id) {
        $this->db->select('tag.*');
        $this->db->from($this->tag_table . ' tag');
        $this->db->join($this->tag_assignment_table . ' ta', 'ta.tag_id = tag.tag_id');
        $this->db->where('ta.template_id', $template_id);
        $this->db->order_by('tag.tag_name', 'ASC');
        return $this->db->get()->result();
    }
    
    /**
     * Assign tags to template
     */
    private function assign_tags($template_id, $tag_ids) {
        $batch_data = [];
        foreach ($tag_ids as $tag_id) {
            $batch_data[] = [
                'template_id' => $template_id,
                'tag_id' => $tag_id
            ];
        }
        
        if (!empty($batch_data)) {
            $this->db->insert_batch($this->tag_assignment_table, $batch_data);
        }
    }
    
    /**
     * Get template statistics for teacher
     */
    public function get_teacher_stats($teacher_id) {
        $stats = [];
        
        // Total templates created
        $stats['total_templates'] = $this->db->where('teacher_id', $teacher_id)
            ->where('is_active', 1)
            ->count_all_results($this->template_table);
        
        // Public templates
        $stats['public_templates'] = $this->db->where('teacher_id', $teacher_id)
            ->where('is_public', 1)
            ->where('is_active', 1)
            ->count_all_results($this->template_table);
        
        // Total usage count
        $this->db->select_sum('usage_count');
        $this->db->where('teacher_id', $teacher_id);
        $this->db->where('is_active', 1);
        $result = $this->db->get($this->template_table)->row();
        $stats['total_usage'] = $result->usage_count ?? 0;
        
        // Favorite count
        $stats['favorites'] = $this->db->where('teacher_id', $teacher_id)
            ->count_all_results($this->favorite_table);
        
        return $stats;
    }
    
    /**
     * Duplicate template (create copy)
     */
    public function duplicate_template($template_id, $teacher_id) {
        $template = $this->get_template($template_id, $teacher_id);
        if (!$template) {
            return false;
        }
        
        $new_data = [
            'teacher_id' => $teacher_id,
            'template_name' => $template->template_name . ' (Copy)',
            'description' => $template->description,
            'subject_id' => $template->subject_id,
            'class_id' => $template->class_id,
            'teaching_methods' => json_decode($template->teaching_methods, true),
            'learning_activities' => json_decode($template->learning_activities, true),
            'assessment_methods' => json_decode($template->assessment_methods, true),
            'resources' => json_decode($template->resources, true),
            'differentiation_strategies' => $template->differentiation_strategies,
            'homework_assignment' => $template->homework_assignment,
            'reflection_notes' => $template->reflection_notes,
            'is_public' => 0, // Copies are private by default
            'tag_ids' => array_column($template->tags, 'tag_id')
        ];
        
        return $this->create_template($new_data);
    }
}
