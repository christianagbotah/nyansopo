<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Head Teacher Remarks Model
 * 
 * Manages configurable head teacher remark ranges based on score percentages.
 * Provides automatic remark assignment, overlap validation, and CRUD operations.
 * 
 * @package    School Management System
 * @subpackage Models
 * @category   Academic
 * @author     School Management System
 * @version    1.0
 */
class Head_teacher_remarks_model extends CI_Model {

    /**
     * Table name
     */
    private $table = 'head_teacher_remarks_ranges';
    
    /**
     * Cache key for active remarks
     */
    private $cache_key = 'head_teacher_remarks_active';
    
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
     * Get all remark ranges
     * 
     * @param bool $include_inactive Include inactive ranges
     * @return array Array of remark range objects
     */
    public function get_all($include_inactive = true) {
        $this->db->select('*');
        $this->db->from($this->table);
        
        if (!$include_inactive) {
            $this->db->where('is_active', 1);
        }
        
        $this->db->order_by('display_order', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Get only active remark ranges (cached)
     * 
     * @return array Array of active remark range objects
     */
    public function get_active() {
        // Try to get from cache
        $cached = $this->cache->get($this->cache_key);
        
        if ($cached !== FALSE) {
            return $cached;
        }
        
        // Query database
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        $this->db->order_by('display_order', 'ASC');
        
        $result = $this->db->get()->result();
        
        // Cache the result
        $this->cache->save($this->cache_key, $result, $this->cache_ttl);
        
        return $result;
    }

    /**
     * Get remark range by ID
     * 
     * @param int $id Remark range ID
     * @return object|null Remark range object or null if not found
     */
    public function get_by_id($id) {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('id', $id);
        
        return $this->db->get()->row();
    }

    /**
     * Find appropriate remark for a given percentage score
     * 
     * Uses active ranges only. Returns first matching range based on display_order.
     * Returns null if no range matches (gap in ranges or score outside all ranges).
     * 
     * @param float $percentage Score percentage (0-100)
     * @return object|null Object with remark_id, remark_text, or null if no match
     */
    public function find_by_percentage($percentage) {
        // Get active ranges from cache
        $ranges = $this->get_active();
        
        // Find matching range
        foreach ($ranges as $range) {
            if ($percentage >= $range->min_percentage && $percentage <= $range->max_percentage) {
                return (object) array(
                    'remark_id' => $range->id,
                    'remark_text' => $range->remark_text
                );
            }
        }
        
        return null; // No matching range found
    }

    /**
     * Create new remark range
     * 
     * Validates for overlaps before inserting.
     * 
     * @param array $data Associative array with keys: min_percentage, max_percentage, remark_text, display_order, is_active
     * @return array Result array with 'success' boolean and 'message' or 'id'
     */
    public function create($data) {
        // Validate required fields
        if (!isset($data['min_percentage']) || !isset($data['max_percentage']) || !isset($data['remark_text'])) {
            return array(
                'success' => false,
                'message' => 'Missing required fields: min_percentage, max_percentage, remark_text'
            );
        }

        // Validate range
        if ($data['min_percentage'] > $data['max_percentage']) {
            return array(
                'success' => false,
                'message' => 'Minimum percentage cannot be greater than maximum percentage'
            );
        }

        // Validate percentage bounds
        if ($data['min_percentage'] < 0 || $data['max_percentage'] > 100) {
            return array(
                'success' => false,
                'message' => 'Percentages must be between 0 and 100'
            );
        }

        // Set defaults
        if (!isset($data['display_order'])) {
            $data['display_order'] = $this->get_next_display_order();
        }
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        // Check for overlaps (only for active ranges)
        if ($data['is_active'] == 1) {
            $overlap = $this->check_overlap($data['min_percentage'], $data['max_percentage']);
            if ($overlap) {
                $overlapping_range = $this->get_overlapping_range($data['min_percentage'], $data['max_percentage']);
                return array(
                    'success' => false,
                    'message' => 'Range overlaps with existing active range: ' . 
                                $overlapping_range->min_percentage . '% - ' . 
                                $overlapping_range->max_percentage . '%',
                    'overlapping_range' => $overlapping_range
                );
            }
        }

        // Insert
        $insert_data = array(
            'min_percentage' => $data['min_percentage'],
            'max_percentage' => $data['max_percentage'],
            'remark_text' => $data['remark_text'],
            'display_order' => $data['display_order'],
            'is_active' => $data['is_active']
        );

        $this->db->insert($this->table, $insert_data);
        
        if ($this->db->affected_rows() > 0) {
            $insert_id = $this->db->insert_id();
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'id' => $insert_id,
                'message' => 'Remark range created successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to create remark range'
            );
        }
    }

    /**
     * Update existing remark range
     * 
     * Validates for overlaps before updating (excluding current range).
     * 
     * @param int $id Remark range ID
     * @param array $data Associative array with fields to update
     * @return array Result array with 'success' boolean and 'message'
     */
    public function update($id, $data) {
        // Check if range exists
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => 'Remark range not found'
            );
        }

        // Validate range if percentages are being updated
        if (isset($data['min_percentage']) || isset($data['max_percentage'])) {
            $min = isset($data['min_percentage']) ? $data['min_percentage'] : $existing->min_percentage;
            $max = isset($data['max_percentage']) ? $data['max_percentage'] : $existing->max_percentage;

            if ($min > $max) {
                return array(
                    'success' => false,
                    'message' => 'Minimum percentage cannot be greater than maximum percentage'
                );
            }

            if ($min < 0 || $max > 100) {
                return array(
                    'success' => false,
                    'message' => 'Percentages must be between 0 and 100'
                );
            }

            // Check for overlaps (only if being set to active)
            $is_active = isset($data['is_active']) ? $data['is_active'] : $existing->is_active;
            if ($is_active == 1) {
                $overlap = $this->check_overlap($min, $max, $id);
                if ($overlap) {
                    $overlapping_range = $this->get_overlapping_range($min, $max, $id);
                    return array(
                        'success' => false,
                        'message' => 'Range overlaps with existing active range: ' . 
                                    $overlapping_range->min_percentage . '% - ' . 
                                    $overlapping_range->max_percentage . '%',
                        'overlapping_range' => $overlapping_range
                    );
                }
            }
        }

        // Update
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);

        if ($this->db->affected_rows() > 0 || $this->db->error()['code'] == 0) {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Remark range updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update remark range or no changes made'
            );
        }
    }

    /**
     * Delete remark range
     * 
     * Note: This does not delete associated records in aggregation table.
     * Remark text is preserved in aggregation for backward compatibility.
     * 
     * @param int $id Remark range ID
     * @return array Result array with 'success' boolean and 'message'
     */
    public function delete($id) {
        // Check if range exists
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => 'Remark range not found'
            );
        }

        // Delete
        $this->db->where('id', $id);
        $this->db->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Remark range deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to delete remark range'
            );
        }
    }

    /**
     * Check if a range overlaps with any active ranges
     * 
     * Two ranges overlap if: A_min < B_max AND B_min < A_max
     * Touching boundaries (e.g., 70.00 and 70.00) are NOT considered overlaps.
     * 
     * @param float $min_percentage Minimum percentage
     * @param float $max_percentage Maximum percentage
     * @param int $exclude_id Optional ID to exclude from overlap check (for updates)
     * @return bool True if overlap exists, false otherwise
     */
    public function check_overlap($min_percentage, $max_percentage, $exclude_id = null) {
        $this->db->select('id');
        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        $this->db->where('min_percentage <', $max_percentage);
        $this->db->where('max_percentage >', $min_percentage);
        
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        
        $result = $this->db->get();
        
        return $result->num_rows() > 0;
    }

    /**
     * Get the overlapping range (for detailed error messages)
     * 
     * @param float $min_percentage Minimum percentage
     * @param float $max_percentage Maximum percentage
     * @param int $exclude_id Optional ID to exclude from search
     * @return object|null First overlapping range object or null
     */
    public function get_overlapping_range($min_percentage, $max_percentage, $exclude_id = null) {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        $this->db->where('min_percentage <', $max_percentage);
        $this->db->where('max_percentage >', $min_percentage);
        
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        
        $this->db->order_by('display_order', 'ASC');
        $this->db->limit(1);
        
        return $this->db->get()->row();
    }

    /**
     * Update display order for multiple ranges
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
     * Toggle active status of a remark range
     * 
     * Validates for overlaps before activating.
     * 
     * @param int $id Remark range ID
     * @return array Result array with 'success' boolean and 'message'
     */
    public function toggle_active($id) {
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => 'Remark range not found'
            );
        }

        $new_status = $existing->is_active == 1 ? 0 : 1;

        // If activating, check for overlaps
        if ($new_status == 1) {
            $overlap = $this->check_overlap($existing->min_percentage, $existing->max_percentage, $id);
            if ($overlap) {
                $overlapping_range = $this->get_overlapping_range($existing->min_percentage, $existing->max_percentage, $id);
                return array(
                    'success' => false,
                    'message' => 'Cannot activate: Range overlaps with existing active range: ' . 
                                $overlapping_range->min_percentage . '% - ' . 
                                $overlapping_range->max_percentage . '%',
                    'overlapping_range' => $overlapping_range
                );
            }
        }

        // Update status
        $this->db->where('id', $id);
        $this->db->update($this->table, array('is_active' => $new_status));

        if ($this->db->affected_rows() > 0 || $this->db->error()['code'] == 0) {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => $new_status == 1 ? 'Remark range activated' : 'Remark range deactivated',
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
     * Get next display order number
     * 
     * @return int Next display order
     */
    private function get_next_display_order() {
        $this->db->select_max('display_order');
        $this->db->from($this->table);
        $result = $this->db->get()->row();
        
        return ($result && $result->display_order) ? $result->display_order + 1 : 1;
    }

    /**
     * Initialize default remark ranges
     * 
     * Creates 5 default ranges if table is empty.
     * Should only be called during initial setup.
     * 
     * @return array Result array with 'success' boolean and 'message'
     */
    public function initialize_defaults() {
        // Check if ranges already exist
        $existing = $this->get_all();
        if (!empty($existing)) {
            return array(
                'success' => false,
                'message' => 'Default ranges already exist'
            );
        }

        $default_ranges = array(
            array('min_percentage' => 0, 'max_percentage' => 50, 'remark_text' => 'Poor performance. Needs significant improvement.', 'display_order' => 1, 'is_active' => 1),
            array('min_percentage' => 51, 'max_percentage' => 60, 'remark_text' => 'Fair performance. More effort required.', 'display_order' => 2, 'is_active' => 1),
            array('min_percentage' => 61, 'max_percentage' => 70, 'remark_text' => 'Good performance. Keep up the good work.', 'display_order' => 3, 'is_active' => 1),
            array('min_percentage' => 71, 'max_percentage' => 80, 'remark_text' => 'Very good performance. Continue to excel.', 'display_order' => 4, 'is_active' => 1),
            array('min_percentage' => 81, 'max_percentage' => 100, 'remark_text' => 'Excellent performance. Outstanding achievement!', 'display_order' => 5, 'is_active' => 1)
        );

        $this->db->trans_start();
        
        foreach ($default_ranges as $range) {
            $this->db->insert($this->table, $range);
        }
        
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array(
                'success' => false,
                'message' => 'Failed to initialize default ranges'
            );
        } else {
            $this->invalidate_cache();
            
            return array(
                'success' => true,
                'message' => 'Default remark ranges initialized successfully',
                'count' => count($default_ranges)
            );
        }
    }

    /**
     * Invalidate cache
     * 
     * Called after any create/update/delete/toggle operation
     */
    private function invalidate_cache() {
        $this->cache->delete($this->cache_key);
    }
}
