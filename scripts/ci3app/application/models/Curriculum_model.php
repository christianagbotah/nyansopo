<?php
/**
 * Curriculum Model
 * 
 * Handles all database operations related to GES curriculum data including
 * strands, sub-strands, content standards, and learning indicators.
 * 
 * Requirements: 2.1-2.8, 8.1-8.10
 * 
 * @package     GES Lesson Note System
 * @subpackage  Models
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Curriculum model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Curriculum_model extends MY_Model {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ============================================
    // STRAND OPERATIONS
    // ============================================

    /**
     * Create a new curriculum strand
     * 
     * @param array $data Strand data (name, description, subject_id, class_level, display_order)
     * @return int|false Inserted strand ID or false on failure
     * 
     * Requirements: 8.1, 8.5
     */
    public function create_strand($data) {
        // Validate required fields
        if (empty($data['name']) || empty($data['subject_id'])) {
            return false;
        }

        // Check for uniqueness within subject and class level
        if ($this->strand_exists($data['name'], $data['subject_id'], $data['class_level'] ?? null)) {
            return false;
        }

        $strand_data = array(
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'subject_id' => $data['subject_id'],
            'class_level' => $data['class_level'] ?? null,
            'display_order' => $data['display_order'] ?? 0
        );

        $this->db->insert('curriculum_strands', $strand_data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing strand
     * 
     * @param int $strand_id Strand ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.1
     */
    public function update_strand($strand_id, $data) {
        if (empty($strand_id)) {
            return false;
        }

        $update_data = array();
        $allowed_fields = array('name', 'description', 'class_level', 'display_order');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $update_data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('strand_id', $strand_id);
        return $this->db->update('curriculum_strands', $update_data);
    }

    /**
     * Delete a strand (cascades to sub-strands, content standards, learning indicators)
     * 
     * @param int $strand_id Strand ID
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.1
     */
    public function delete_strand($strand_id) {
        if (empty($strand_id)) {
            return false;
        }

        $this->db->where('strand_id', $strand_id);
        return $this->db->delete('curriculum_strands');
    }

    /**
     * Get a single strand by ID
     * 
     * @param int $strand_id Strand ID
     * @return object|null Strand object or null
     */
    public function get_strand($strand_id) {
        if (empty($strand_id)) {
            return null;
        }

        $this->db->where('strand_id', $strand_id);
        $query = $this->db->get('curriculum_strands');
        return $query->row();
    }

    /**
     * Get strands by subject and class level
     * 
     * @param int $subject_id Subject ID
     * @param string|null $class_level Optional class level filter
     * @return array Array of strand objects
     * 
     * Requirements: 2.1
     */
    public function get_strands_by_subject_class($subject_id, $class_level = null) {
        $this->db->where('subject_id', $subject_id);
        
        if ($class_level !== null) {
            $this->db->where('class_level', $class_level);
        }
        
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get('curriculum_strands');
        return $query->result();
    }

    /**
     * Get all strands with subject and class information
     * 
     * @return array Array of strand objects with subject info
     */
    public function get_all_strands() {
        $this->db->select('cs.*, s.name as subject_name, c.name as class_name, c.name_numeric as class_numeric, 
                          (SELECT sec.name FROM section sec WHERE sec.class_id = cs.class_level LIMIT 1) as section_name');
        $this->db->from('curriculum_strands cs');
        $this->db->join('subject s', 's.subject_id = cs.subject_id', 'left');
        $this->db->join('class c', 'c.class_id = cs.class_level', 'left');
        $this->db->order_by('s.name', 'ASC');
        $this->db->order_by('c.name', 'ASC');
        $this->db->order_by('cs.display_order', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Check if a strand exists with the given name, subject, and class level
     * 
     * @param string $name Strand name
     * @param int $subject_id Subject ID
     * @param string|null $class_level Class level
     * @return bool True if exists, false otherwise
     */
    private function strand_exists($name, $subject_id, $class_level = null) {
        $this->db->where('name', $name);
        $this->db->where('subject_id', $subject_id);
        
        if ($class_level !== null) {
            $this->db->where('class_level', $class_level);
        }
        
        $query = $this->db->get('curriculum_strands');
        return $query->num_rows() > 0;
    }

    // ============================================
    // SUB-STRAND OPERATIONS
    // ============================================

    /**
     * Create a new sub-strand
     * 
     * @param array $data Sub-strand data (name, description, strand_id, display_order)
     * @return int|false Inserted sub-strand ID or false on failure
     * 
     * Requirements: 8.2, 8.6
     */
    public function create_sub_strand($data) {
        // Validate required fields
        if (empty($data['name']) || empty($data['strand_id'])) {
            return false;
        }

        // Check for uniqueness within strand
        if ($this->sub_strand_exists($data['name'], $data['strand_id'])) {
            return false;
        }

        $sub_strand_data = array(
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'strand_id' => $data['strand_id'],
            'display_order' => $data['display_order'] ?? 0
        );

        $this->db->insert('curriculum_sub_strands', $sub_strand_data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing sub-strand
     * 
     * @param int $sub_strand_id Sub-strand ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.2
     */
    public function update_sub_strand($sub_strand_id, $data) {
        if (empty($sub_strand_id)) {
            return false;
        }

        $update_data = array();
        $allowed_fields = array('name', 'description', 'strand_id', 'display_order');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $update_data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('sub_strand_id', $sub_strand_id);
        return $this->db->update('curriculum_sub_strands', $update_data);
    }

    /**
     * Delete a sub-strand
     * 
     * @param int $sub_strand_id Sub-strand ID
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.2
     */
    public function delete_sub_strand($sub_strand_id) {
        if (empty($sub_strand_id)) {
            return false;
        }

        $this->db->where('sub_strand_id', $sub_strand_id);
        return $this->db->delete('curriculum_sub_strands');
    }

    /**
     * Get sub-strands by strand ID
     * 
     * @param int $strand_id Strand ID
     * @return array Array of sub-strand objects
     * 
     * Requirements: 2.2
     */
    public function get_sub_strands_by_strand($strand_id) {
        if (empty($strand_id)) {
            return array();
        }

        $this->db->where('strand_id', $strand_id);
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get('curriculum_sub_strands');
        return $query->result();
    }

    /**
     * Get a single sub-strand by ID with strand information
     * 
     * @param int $sub_strand_id Sub-strand ID
     * @return object|null Sub-strand object or null
     */
    public function get_sub_strand($sub_strand_id) {
        if (empty($sub_strand_id)) {
            return null;
        }

        $this->db->select('css.*, cs.name as strand_name, s.name as subject_name, c.name as class_name, c.name_numeric as class_numeric');
        $this->db->from('curriculum_sub_strands css');
        $this->db->join('curriculum_strands cs', 'cs.strand_id = css.strand_id', 'left');
        $this->db->join('subject s', 's.subject_id = cs.subject_id', 'left');
        $this->db->join('class c', 'c.class_id = cs.class_level', 'left');
        $this->db->where('css.sub_strand_id', $sub_strand_id);
        
        $query = $this->db->get();
        return $query->row();
    }

    /**
     * Check if a sub-strand exists with the given name and strand
     * 
     * @param string $name Sub-strand name
     * @param int $strand_id Strand ID
     * @return bool True if exists, false otherwise
     */
    private function sub_strand_exists($name, $strand_id) {
        $this->db->where('name', $name);
        $this->db->where('strand_id', $strand_id);
        $query = $this->db->get('curriculum_sub_strands');
        return $query->num_rows() > 0;
    }

    /**
     * Get all sub-strands with strand and subject information
     * 
     * @param int|null $strand_id Optional strand filter
     * @return array Array of sub-strand objects with strand info
     * 
     * Requirements: 8.2
     */
    public function get_all_sub_strands($strand_id = null) {
        $this->db->select('css.*, cs.name as strand_name, s.name as subject_name');
        $this->db->from('curriculum_sub_strands css');
        $this->db->join('curriculum_strands cs', 'cs.strand_id = css.strand_id', 'left');
        $this->db->join('subject s', 's.subject_id = cs.subject_id', 'left');
        
        if ($strand_id !== null) {
            $this->db->where('css.strand_id', $strand_id);
        }
        
        $this->db->order_by('s.name', 'ASC');
        $this->db->order_by('cs.name', 'ASC');
        $this->db->order_by('css.display_order', 'ASC');
        
        $query = $this->db->get();
        $sub_strands = $query->result();
        
        // Add content standards count for each sub-strand
        foreach ($sub_strands as $sub_strand) {
            $this->db->where('sub_strand_id', $sub_strand->sub_strand_id);
            $sub_strand->content_standards_count = $this->db->count_all_results('curriculum_content_standards');
        }
        
        return $sub_strands;
    }

    // ============================================
    // CONTENT STANDARD OPERATIONS
    // ============================================

    /**
     * Create a new content standard
     * 
     * @param array $data Content standard data (code, description, sub_strand_id, display_order)
     * @return int|false Inserted content standard ID or false on failure
     * 
     * Requirements: 8.3, 8.7
     */
    public function create_content_standard($data) {
        // Validate required fields
        if (empty($data['code']) || empty($data['description']) || empty($data['sub_strand_id'])) {
            return false;
        }

        // Check for uniqueness within sub-strand
        if ($this->content_standard_exists($data['code'], $data['sub_strand_id'])) {
            return false;
        }

        $standard_data = array(
            'code' => $data['code'],
            'description' => $data['description'],
            'sub_strand_id' => $data['sub_strand_id'],
            'display_order' => $data['display_order'] ?? 0
        );

        $this->db->insert('curriculum_content_standards', $standard_data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing content standard
     * 
     * @param int $content_standard_id Content standard ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.3
     */
    public function update_content_standard($content_standard_id, $data) {
        if (empty($content_standard_id)) {
            return false;
        }

        $update_data = array();
        $allowed_fields = array('code', 'description', 'sub_strand_id', 'display_order');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $update_data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('content_standard_id', $content_standard_id);
        return $this->db->update('curriculum_content_standards', $update_data);
    }

    /**
     * Delete a content standard
     * 
     * @param int $content_standard_id Content standard ID
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.3
     */
    public function delete_content_standard($content_standard_id) {
        if (empty($content_standard_id)) {
            return false;
        }

        $this->db->where('content_standard_id', $content_standard_id);
        return $this->db->delete('curriculum_content_standards');
    }

    /**
     * Get content standards by sub-strand ID
     * 
     * @param int $sub_strand_id Sub-strand ID
     * @return array Array of content standard objects
     * 
     * Requirements: 2.3
     */
    public function get_content_standards_by_sub_strand($sub_strand_id) {
        if (empty($sub_strand_id)) {
            return array();
        }

        $this->db->where('sub_strand_id', $sub_strand_id);
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('code', 'ASC');
        
        $query = $this->db->get('curriculum_content_standards');
        return $query->result();
    }

    /**
     * Get a single content standard by ID with hierarchy information
     * 
     * @param int $content_standard_id Content standard ID
     * @return object|null Content standard object or null
     */
    public function get_content_standard($content_standard_id) {
        if (empty($content_standard_id)) {
            return null;
        }

        $this->db->select('ccs.*, css.name as sub_strand_name, cs.name as strand_name, s.name as subject_name');
        $this->db->from('curriculum_content_standards ccs');
        $this->db->join('curriculum_sub_strands css', 'css.sub_strand_id = ccs.sub_strand_id', 'left');
        $this->db->join('curriculum_strands cs', 'cs.strand_id = css.strand_id', 'left');
        $this->db->join('subject s', 's.subject_id = cs.subject_id', 'left');
        $this->db->where('ccs.content_standard_id', $content_standard_id);
        
        $query = $this->db->get();
        return $query->row();
    }

    /**
     * Check if a content standard exists with the given code and sub-strand
     * 
     * @param string $code Content standard code
     * @param int $sub_strand_id Sub-strand ID
     * @return bool True if exists, false otherwise
     */
    private function content_standard_exists($code, $sub_strand_id) {
        $this->db->where('code', $code);
        $this->db->where('sub_strand_id', $sub_strand_id);
        $query = $this->db->get('curriculum_content_standards');
        return $query->num_rows() > 0;
    }

    /**
     * Get all content standards with sub-strand, strand, and subject information
     * 
     * @param int|null $sub_strand_id Optional sub-strand filter
     * @return array Array of content standard objects with hierarchy info
     * 
     * Requirements: 8.3
     */
    public function get_all_content_standards($sub_strand_id = null) {
        $this->db->select('ccs.*, css.name as sub_strand_name, cs.name as strand_name, s.name as subject_name');
        $this->db->from('curriculum_content_standards ccs');
        $this->db->join('curriculum_sub_strands css', 'css.sub_strand_id = ccs.sub_strand_id', 'left');
        $this->db->join('curriculum_strands cs', 'cs.strand_id = css.strand_id', 'left');
        $this->db->join('subject s', 's.subject_id = cs.subject_id', 'left');
        
        if ($sub_strand_id !== null) {
            $this->db->where('ccs.sub_strand_id', $sub_strand_id);
        }
        
        $this->db->order_by('s.name', 'ASC');
        $this->db->order_by('cs.name', 'ASC');
        $this->db->order_by('css.name', 'ASC');
        $this->db->order_by('ccs.code', 'ASC');
        
        $query = $this->db->get();
        $content_standards = $query->result();
        
        // Add learning indicators count for each content standard
        foreach ($content_standards as $standard) {
            $this->db->where('content_standard_id', $standard->content_standard_id);
            $standard->learning_indicators_count = $this->db->count_all_results('curriculum_learning_indicators');
        }
        
        return $content_standards;
    }

    // ============================================
    // LEARNING INDICATOR OPERATIONS
    // ============================================

    /**
     * Create a new learning indicator
     * 
     * @param array $data Learning indicator data (code, description, content_standard_id, display_order)
     * @return int|false Inserted indicator ID or false on failure
     * 
     * Requirements: 8.4, 8.8
     */
    public function create_learning_indicator($data) {
        // Validate required fields
        if (empty($data['code']) || empty($data['description']) || empty($data['content_standard_id'])) {
            return false;
        }

        // Check for uniqueness within content standard
        if ($this->learning_indicator_exists($data['code'], $data['content_standard_id'])) {
            return false;
        }

        $indicator_data = array(
            'code' => $data['code'],
            'description' => $data['description'],
            'content_standard_id' => $data['content_standard_id'],
            'display_order' => $data['display_order'] ?? 0
        );

        $this->db->insert('curriculum_learning_indicators', $indicator_data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing learning indicator
     * 
     * @param int $indicator_id Learning indicator ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.4
     */
    public function update_learning_indicator($indicator_id, $data) {
        if (empty($indicator_id)) {
            return false;
        }

        $update_data = array();
        $allowed_fields = array('code', 'description', 'display_order');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $update_data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('indicator_id', $indicator_id);
        return $this->db->update('curriculum_learning_indicators', $update_data);
    }

    /**
     * Delete a learning indicator
     * 
     * @param int $indicator_id Learning indicator ID
     * @return bool True on success, false on failure
     * 
     * Requirements: 8.4
     */
    public function delete_learning_indicator($indicator_id) {
        if (empty($indicator_id)) {
            return false;
        }

        $this->db->where('indicator_id', $indicator_id);
        return $this->db->delete('curriculum_learning_indicators');
    }

    /**
     * Get learning indicators by content standard ID
     * 
     * @param int $content_standard_id Content standard ID
     * @return array Array of learning indicator objects
     * 
     * Requirements: 2.4
     */
    public function get_learning_indicators_by_content_standard($content_standard_id) {
        if (empty($content_standard_id)) {
            return array();
        }

        $this->db->where('content_standard_id', $content_standard_id);
        $this->db->order_by('display_order', 'ASC');
        $this->db->order_by('code', 'ASC');
        
        $query = $this->db->get('curriculum_learning_indicators');
        return $query->result();
    }

    /**
     * Get a single learning indicator by ID
     * 
     * @param int $indicator_id Learning indicator ID
     * @return object|null Learning indicator object or null
     */
    public function get_learning_indicator($indicator_id) {
        if (empty($indicator_id)) {
            return null;
        }

        $this->db->where('indicator_id', $indicator_id);
        $query = $this->db->get('curriculum_learning_indicators');
        return $query->row();
    }

    /**
     * Get multiple learning indicators by IDs
     * 
     * @param array $indicator_ids Array of indicator IDs
     * @return array Array of learning indicator objects
     */
    public function get_learning_indicators_by_ids($indicator_ids) {
        if (empty($indicator_ids) || !is_array($indicator_ids)) {
            return array();
        }

        $this->db->where_in('indicator_id', $indicator_ids);
        $this->db->order_by('display_order', 'ASC');
        
        $query = $this->db->get('curriculum_learning_indicators');
        return $query->result();
    }

    /**
     * Check if a learning indicator exists with the given code and content standard
     * 
     * @param string $code Learning indicator code
     * @param int $content_standard_id Content standard ID
     * @return bool True if exists, false otherwise
     */
    private function learning_indicator_exists($code, $content_standard_id) {
        $this->db->where('code', $code);
        $this->db->where('content_standard_id', $content_standard_id);
        $query = $this->db->get('curriculum_learning_indicators');
        return $query->num_rows() > 0;
    }

    /**
     * Get all learning indicators with content standard, sub-strand, strand, and subject information
     * 
     * @param int|null $content_standard_id Optional content standard filter
     * @return array Array of learning indicator objects with hierarchy info
     * 
     * Requirements: 8.4
     */
    public function get_all_learning_indicators($content_standard_id = null) {
        $this->db->select('cli.*, ccs.code as content_standard_code, ccs.description as content_standard_description, css.name as sub_strand_name, cs.name as strand_name, s.name as subject_name');
        $this->db->from('curriculum_learning_indicators cli');
        $this->db->join('curriculum_content_standards ccs', 'ccs.content_standard_id = cli.content_standard_id', 'left');
        $this->db->join('curriculum_sub_strands css', 'css.sub_strand_id = ccs.sub_strand_id', 'left');
        $this->db->join('curriculum_strands cs', 'cs.strand_id = css.strand_id', 'left');
        $this->db->join('subject s', 's.subject_id = cs.subject_id', 'left');
        
        if ($content_standard_id !== null) {
            $this->db->where('cli.content_standard_id', $content_standard_id);
        }
        
        $this->db->order_by('s.name', 'ASC');
        $this->db->order_by('cs.name', 'ASC');
        $this->db->order_by('css.name', 'ASC');
        $this->db->order_by('ccs.code', 'ASC');
        $this->db->order_by('cli.code', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    // ============================================
    // HIERARCHY AND UTILITY METHODS
    // ============================================

    /**
     * Get full curriculum hierarchy for a subject and class
     * 
     * @param int $subject_id Subject ID
     * @param string|null $class_level Optional class level filter
     * @return array Nested array of curriculum data
     * 
     * Requirements: 2.6
     */
    public function get_curriculum_hierarchy($subject_id, $class_level = null) {
        $hierarchy = array();
        
        // Get strands
        $strands = $this->get_strands_by_subject_class($subject_id, $class_level);
        
        foreach ($strands as $strand) {
            $strand_data = array(
                'strand_id' => $strand->strand_id,
                'name' => $strand->name,
                'description' => $strand->description,
                'sub_strands' => array()
            );
            
            // Get sub-strands for each strand
            $sub_strands = $this->get_sub_strands_by_strand($strand->strand_id);
            
            foreach ($sub_strands as $sub_strand) {
                $sub_strand_data = array(
                    'sub_strand_id' => $sub_strand->sub_strand_id,
                    'name' => $sub_strand->name,
                    'description' => $sub_strand->description,
                    'content_standards' => array()
                );
                
                // Get content standards for each sub-strand
                $content_standards = $this->get_content_standards_by_sub_strand($sub_strand->sub_strand_id);
                
                foreach ($content_standards as $standard) {
                    $standard_data = array(
                        'content_standard_id' => $standard->content_standard_id,
                        'code' => $standard->code,
                        'description' => $standard->description,
                        'learning_indicators' => array()
                    );
                    
                    // Get learning indicators for each content standard
                    $indicators = $this->get_learning_indicators_by_content_standard($standard->content_standard_id);
                    
                    foreach ($indicators as $indicator) {
                        $standard_data['learning_indicators'][] = array(
                            'indicator_id' => $indicator->indicator_id,
                            'code' => $indicator->code,
                            'description' => $indicator->description
                        );
                    }
                    
                    $sub_strand_data['content_standards'][] = $standard_data;
                }
                
                $strand_data['sub_strands'][] = $sub_strand_data;
            }
            
            $hierarchy[] = $strand_data;
        }
        
        return $hierarchy;
    }

    /**
     * Validate curriculum chain (strand → sub-strand → standard → indicator)
     * 
     * @param int $strand_id Strand ID
     * @param int $sub_strand_id Sub-strand ID
     * @param int $content_standard_id Content standard ID
     * @param int $indicator_id Learning indicator ID
     * @return bool True if chain is valid, false otherwise
     * 
     * Requirements: 2.6
     */
    public function validate_curriculum_chain($strand_id, $sub_strand_id = null, $content_standard_id = null, $indicator_id = null) {
        // Validate strand exists
        $strand = $this->get_strand($strand_id);
        if (!$strand) {
            return false;
        }
        
        if ($sub_strand_id === null) {
            return true;
        }
        
        // Validate sub-strand belongs to strand
        $sub_strand = $this->get_sub_strand($sub_strand_id);
        if (!$sub_strand || $sub_strand->strand_id != $strand_id) {
            return false;
        }
        
        if ($content_standard_id === null) {
            return true;
        }
        
        // Validate content standard belongs to sub-strand
        $content_standard = $this->get_content_standard($content_standard_id);
        if (!$content_standard || $content_standard->sub_strand_id != $sub_strand_id) {
            return false;
        }
        
        if ($indicator_id === null) {
            return true;
        }
        
        // Validate indicator belongs to content standard
        $indicator = $this->get_learning_indicator($indicator_id);
        if (!$indicator || $indicator->content_standard_id != $content_standard_id) {
            return false;
        }
        
        return true;
    }

    // ============================================
    // BULK IMPORT METHODS
    // ============================================

    /**
     * Import curriculum data from CSV
     * 
     * @param string $file_path Path to CSV file
     * @param string $type Type of data (strand, sub_strand, content_standard, learning_indicator)
     * @return array Result array with success count and errors
     * 
     * Requirements: 8.9, 8.10
     */
    public function import_curriculum_from_csv($file_path, $type) {
        $result = array(
            'success' => 0,
            'errors' => array(),
            'duplicates' => 0
        );
        
        if (!file_exists($file_path)) {
            $result['errors'][] = 'File not found: ' . $file_path;
            return $result;
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $result['errors'][] = 'Unable to open file';
            return $result;
        }
        
        // Get header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            $result['errors'][] = 'Empty or invalid CSV file';
            fclose($handle);
            return $result;
        }
        
        // Normalize headers
        $headers = array_map('strtolower', array_map('trim', $headers));
        
        $row_number = 1; // Header is row 1
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Combine headers with row data
            $data = array_combine($headers, $row);
            
            if ($data === false) {
                $result['errors'][] = "Row $row_number: Column count mismatch";
                continue;
            }
            
            // Validate and insert based on type
            switch ($type) {
                case 'strand':
                    $insert_result = $this->create_strand($data);
                    break;
                case 'sub_strand':
                    $insert_result = $this->create_sub_strand($data);
                    break;
                case 'content_standard':
                    $insert_result = $this->create_content_standard($data);
                    break;
                case 'learning_indicator':
                    $insert_result = $this->create_learning_indicator($data);
                    break;
                default:
                    $result['errors'][] = "Row $row_number: Unknown import type";
                    continue 2;
            }
            
            if ($insert_result === false) {
                $result['duplicates']++;
            } else {
                $result['success']++;
            }
        }
        
        fclose($handle);
        return $result;
    }

    /**
     * Validate import data before processing
     * 
     * @param array $data Data to validate
     * @param string $type Type of data
     * @return array Array with 'valid' boolean and 'errors' array
     * 
     * Requirements: 8.10
     */
    public function validate_import_data($data, $type) {
        $errors = array();
        
        switch ($type) {
            case 'strand':
                if (empty($data['name'])) {
                    $errors[] = 'Name is required';
                }
                if (empty($data['subject_id'])) {
                    $errors[] = 'Subject ID is required';
                }
                break;
                
            case 'sub_strand':
                if (empty($data['name'])) {
                    $errors[] = 'Name is required';
                }
                if (empty($data['strand_id'])) {
                    $errors[] = 'Strand ID is required';
                }
                break;
                
            case 'content_standard':
                if (empty($data['code'])) {
                    $errors[] = 'Code is required';
                }
                if (empty($data['description'])) {
                    $errors[] = 'Description is required';
                }
                if (empty($data['sub_strand_id'])) {
                    $errors[] = 'Sub-strand ID is required';
                }
                break;
                
            case 'learning_indicator':
                if (empty($data['code'])) {
                    $errors[] = 'Code is required';
                }
                if (empty($data['description'])) {
                    $errors[] = 'Description is required';
                }
                if (empty($data['content_standard_id'])) {
                    $errors[] = 'Content standard ID is required';
                }
                break;
                
            default:
                $errors[] = 'Unknown data type';
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    // ============================================
    // CACHING METHODS
    // ============================================

    /**
     * Get cached curriculum data
     * 
     * @param int $subject_id Subject ID
     * @param string|null $class_level Optional class level
     * @return array|null Cached data or null if not cached
     * 
     * Requirements: 2.8
     */
    public function get_cached_curriculum($subject_id, $class_level = null) {
        $cache_key = 'curriculum_' . $subject_id . '_' . ($class_level ?? 'all');
        
        // Try to get from CodeIgniter cache if available
        $cached = $this->cache->get($cache_key);
        
        if ($cached === false) {
            return null;
        }
        
        return $cached;
    }

    /**
     * Clear curriculum cache
     * 
     * @param int $subject_id Subject ID
     * @param string|null $class_level Optional class level
     * @return bool True on success
     * 
     * Requirements: 2.8
     */
    public function clear_curriculum_cache($subject_id, $class_level = null) {
        $cache_key = 'curriculum_' . $subject_id . '_' . ($class_level ?? 'all');
        
        return $this->cache->delete($cache_key);
    }

    // ============================================
    // CORE COMPETENCIES METHODS
    // ============================================

    /**
     * Get all core competencies
     * 
     * @return array Array of core competency objects
     * 
     * Requirements: 3.1
     */
    public function get_core_competencies() {
        $this->db->order_by('display_order', 'ASC');
        $query = $this->db->get('core_competencies');
        return $query->result();
    }

    /**
     * Get a single core competency by ID
     * 
     * @param int $competency_id Competency ID
     * @return object|null Competency object or null
     */
    public function get_core_competency($competency_id) {
        if (empty($competency_id)) {
            return null;
        }

        $this->db->where('competency_id', $competency_id);
        $query = $this->db->get('core_competencies');
        return $query->row();
    }

    // ============================================
    // TEACHING RESOURCES METHODS
    // ============================================

    /**
     * Get all teaching resources (TLRs)
     * 
     * @return array Array of teaching resource objects
     * 
     * Requirements: 4.1
     */
    public function get_teaching_resources() {
        $this->db->order_by('display_order', 'ASC');
        $query = $this->db->get('teaching_resources_master');
        return $query->result();
    }

    // ============================================
    // ASSESSMENT METHODS METHODS
    // ============================================

    /**
     * Get all assessment methods
     * 
     * @return array Array of assessment method objects
     * 
     * Requirements: 5.1
     */
    public function get_assessment_methods() {
        $this->db->order_by('display_order', 'ASC');
        $query = $this->db->get('assessment_methods_master');
        return $query->result();
    }
}
