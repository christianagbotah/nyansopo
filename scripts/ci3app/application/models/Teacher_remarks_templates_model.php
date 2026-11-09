<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Teacher Remarks Templates Model
 * 
 * Manages configurable teacher remark templates for dropdown selection.
 * Provides CRUD operations and categorization for teacher remarks.
 * 
 * @package    School Management System
 * @subpackage Models
 * @category   Academic
 * @author     School Management System
 * @version    1.0
 */
class Teacher_remarks_templates_model extends CI_Model {

    /**
     * Table name
     */
    private $table = 'teacher_remarks_templates';
    
    /**
     * Cache key for active templates
     */
    private $cache_key = 'teacher_remarks_templates_active';
    
    /**
     * Cache TTL (10 minutes)
     */
    private $cache_ttl = 600;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->driver('cache', array('adapter' => 'file'));
    }

    /**
     * Get all remark templates
     * 
     * @param bool $include_inactive Include inactive templates
     * @param string $category Optional category filter ('positive', 'neutral', 'negative')
     * @return array Array of remark template objects
     */
    public function get_all($include_inactive = true, $category = null) {
        $this->db->select('*');
        $this->db->from($this->table);
        
        if (!$include_inactive) {
            $this->db->where('is_active', 1);
        }
        
        if ($category !== null) {
            $this->db->where('category', $category);
        }
        
        $this->db->order_by('category', 'ASC');
        $this->db->order_by('display_order', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Get only active remark templates (cached)
     * 
     * @param string $category Optional category filter
     * @return array Array of active remark template objects
     */
    public function get_active($category = null) {
        // Build cache key with category
        $cache_key = $this->cache_key . ($category ? '_' . $category : '');
        
        // Try to get from cache
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== FALSE) {
            return $cached;
        }
        
        // Query database
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        
        if ($category !== null) {
            $this->db->where('category', $category);
        }
        
        $this->db->order_by('category', 'ASC');
        $this->db->order_by('display_order', 'ASC');
        
        $result = $this->db->get()->result();
        
        // Cache the result
        $this->cache->save($cache_key, $result, $this->cache_ttl);
        
        return $result;
    }

    /**
     * Get remark template by ID
     * 
     * @param int $id Template ID
     * @return object|null Template object or null if not found
     */
    public function get_by_id($id) {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('id', $id);
        
        return $this->db->get()->row();
    }

    /**
     * Get templates grouped by category
     * 
     * Returns array with category names as keys, each containing array of templates.
     * 
     * @param bool $active_only Get only active templates
     * @return array Associative array grouped by category
     */
    public function get_by_category($active_only = true) {
        $templates = $active_only ? $this->get_active() : $this->get_all();
        
        $grouped = array();
        foreach ($templates as $template) {
            $category = $template->category ?: 'uncategorized';
            if (!isset($grouped[$category])) {
                $grouped[$category] = array();
            }
            $grouped[$category][] = $template;
        }
        
        return $grouped;
    }

    /**
     * Create new remark template
     * 
     * @param array $data Associative array with keys: remark_text, display_order, is_active, category
     * @return array Result array with 'success' boolean and 'message' or 'id'
     */
    public function create($data) {
        // Validate required fields
        if (!isset($data['remark_text']) || empty(trim($data['remark_text']))) {
            return array(
                'success' => false,
                'message' => 'Remark text is required'
            );
        }

        // Set defaults
        if (!isset($data['display_order'])) {
            $data['display_order'] = $this->get_next_display_order($data['category'] ?? null);
        }
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        if (!isset($data['category'])) {
            $data['category'] = null;
        }

        // Insert
        $insert_data = array(
            'remark_text' => trim($data['remark_text']),
            'display_order' => $data['display_order'],
            'is_active' => $data['is_active'],
            'category' => $data['category']
        );

        $this->db->insert($this->table, $insert_data);
        
        if ($this->db->affected_rows() > 0) {
            $insert_id = $this->db->insert_id();
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'id' => $insert_id,
                'message' => 'Remark template created successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to create remark template'
            );
        }
    }

    /**
     * Update existing remark template
     * 
     * @param int $id Template ID
     * @param array $data Associative array with fields to update
     * @return array Result array with 'success' boolean and 'message'
     */
    public function update($id, $data) {
        // Check if template exists
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => 'Remark template not found'
            );
        }

        // Validate remark text if provided
        if (isset($data['remark_text']) && empty(trim($data['remark_text']))) {
            return array(
                'success' => false,
                'message' => 'Remark text cannot be empty'
            );
        }

        // Trim remark text if provided
        if (isset($data['remark_text'])) {
            $data['remark_text'] = trim($data['remark_text']);
        }

        // Update
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);

        if ($this->db->affected_rows() > 0 || $this->db->error()['code'] == 0) {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Remark template updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update remark template or no changes made'
            );
        }
    }

    /**
     * Delete remark template
     * 
     * @param int $id Template ID
     * @return array Result array with 'success' boolean and 'message'
     */
    public function delete($id) {
        // Check if template exists
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => 'Remark template not found'
            );
        }

        // Delete
        $this->db->where('id', $id);
        $this->db->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Remark template deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to delete remark template'
            );
        }
    }

    /**
     * Update display order for multiple templates
     * 
     * @param array $order_data Array of arrays with 'id' and 'display_order' keys
     * @return array Result array with 'success' boolean and 'message'
     */
    public function update_order($order_data) {
        if (empty($order_data) || !is_array($order_data)) {
            return array(
                'success' => false,
                'message' => 'Invalid order data'
            );
        }

        $this->db->trans_start();
        
        foreach ($order_data as $item) {
            if (isset($item['id']) && isset($item['display_order'])) {
                $this->db->where('id', $item['id']);
                $this->db->update($this->table, array('display_order' => $item['display_order']));
            }
        }
        
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array(
                'success' => false,
                'message' => 'Failed to update display order'
            );
        } else {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Display order updated successfully'
            );
        }
    }

    /**
     * Toggle active status of a remark template
     * 
     * @param int $id Template ID
     * @return array Result array with 'success' boolean and 'message'
     */
    public function toggle_active($id) {
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => 'Remark template not found'
            );
        }

        $new_status = $existing->is_active == 1 ? 0 : 1;

        // Update status
        $this->db->where('id', $id);
        $this->db->update($this->table, array('is_active' => $new_status));

        if ($this->db->affected_rows() > 0 || $this->db->error()['code'] == 0) {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => $new_status == 1 ? 'Remark template activated' : 'Remark template deactivated',
                'new_status' => $new_status
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to toggle active status'
            );
        }
    }

    /**
     * Get next display order number for a category
     * 
     * @param string $category Optional category
     * @return int Next display order
     */
    private function get_next_display_order($category = null) {
        $this->db->select_max('display_order');
        $this->db->from($this->table);
        
        if ($category !== null) {
            $this->db->where('category', $category);
        }
        
        $result = $this->db->get()->row();
        
        return ($result && $result->display_order) ? $result->display_order + 1 : 1;
    }

    /**
     * Initialize default remark templates
     * 
     * Creates 15 default templates if table is empty.
     * Should only be called during initial setup.
     * 
     * @return array Result array with 'success' boolean and 'message'
     */
    public function initialize_defaults() {
        // Check if templates already exist
        $existing = $this->get_all();
        if (!empty($existing)) {
            return array(
                'success' => false,
                'message' => 'Default templates already exist'
            );
        }

        $default_templates = array(
            // Positive remarks
            array('remark_text' => 'Excellent work and outstanding performance throughout the term.', 'category' => 'positive', 'display_order' => 1, 'is_active' => 1),
            array('remark_text' => 'Shows consistent effort and demonstrates strong understanding of concepts.', 'category' => 'positive', 'display_order' => 2, 'is_active' => 1),
            array('remark_text' => 'Active participation in class with excellent results.', 'category' => 'positive', 'display_order' => 3, 'is_active' => 1),
            array('remark_text' => 'Displays good leadership qualities and helps fellow students.', 'category' => 'positive', 'display_order' => 4, 'is_active' => 1),
            array('remark_text' => 'Very attentive in class and completes assignments on time.', 'category' => 'positive', 'display_order' => 5, 'is_active' => 1),
            
            // Neutral remarks
            array('remark_text' => 'Fair performance. More consistent effort required.', 'category' => 'neutral', 'display_order' => 6, 'is_active' => 1),
            array('remark_text' => 'Shows potential but needs to focus more on studies.', 'category' => 'neutral', 'display_order' => 7, 'is_active' => 1),
            array('remark_text' => 'Satisfactory work. Can improve with regular practice.', 'category' => 'neutral', 'display_order' => 8, 'is_active' => 1),
            array('remark_text' => 'Attendance and punctuality need improvement.', 'category' => 'neutral', 'display_order' => 9, 'is_active' => 1),
            array('remark_text' => 'Should participate more actively in class discussions.', 'category' => 'neutral', 'display_order' => 10, 'is_active' => 1),
            
            // Negative remarks (constructive)
            array('remark_text' => 'Needs significant improvement. Parents\' support essential.', 'category' => 'negative', 'display_order' => 11, 'is_active' => 1),
            array('remark_text' => 'Requires extra attention and practice in weak areas.', 'category' => 'negative', 'display_order' => 12, 'is_active' => 1),
            array('remark_text' => 'Shows lack of interest. Must work harder to meet expectations.', 'category' => 'negative', 'display_order' => 13, 'is_active' => 1),
            array('remark_text' => 'Needs to be more disciplined and focused during lessons.', 'category' => 'negative', 'display_order' => 14, 'is_active' => 1),
            array('remark_text' => 'Poor assignment completion rate. Immediate intervention required.', 'category' => 'negative', 'display_order' => 15, 'is_active' => 1)
        );

        $this->db->trans_start();
        
        foreach ($default_templates as $template) {
            $this->db->insert($this->table, $template);
        }
        
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array(
                'success' => false,
                'message' => 'Failed to initialize default templates'
            );
        } else {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Default remark templates initialized successfully',
                'count' => count($default_templates)
            );
        }
    }

    /**
     * Invalidate all caches
     * 
     * Called after any create/update/delete/toggle operation
     */
    private function invalidate_cache() {
        // Clear main cache
        $this->cache->delete($this->cache_key);
        
        // Clear category-specific caches
        $categories = array('positive', 'neutral', 'negative');
        foreach ($categories as $category) {
            $this->cache->delete($this->cache_key . '_' . $category);
        }
    }
}
