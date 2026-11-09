<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount category model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Discount_category_model extends MY_Model {

    /**
     * Get all discount categories
     * 
     * Overrides parent method to provide custom ordering.
     * Maintains compatibility with parent signature.
     * 
     * @param array $where WHERE conditions (optional, not used in this implementation)
     * @param int|null $limit Optional limit
     * @param int|null $offset Optional offset
     * @return array Array of category objects
     */
    public function get_all($where = [], $limit = null, $offset = null) {
        $this->db->order_by('category_id', 'ASC');
        
        // Apply limit and offset if provided
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db->get('discount_categories')->result();
    }

    /**
     * Get category by ID
     * 
     * @param int $category_id Category ID
     * @return object|null Category object or null
     */
    public function get($category_id) {
        return $this->db
            ->where('category_id', $category_id)
            ->get('discount_categories')
            ->row();
    }

    /**
     * Get category by code
     * 
     * @param string $code Category code ('invoice' or 'daily_fees')
     * @return object|null Category object or null
     */
    public function get_by_code($code) {
        return $this->db
            ->where('code', $code)
            ->get('discount_categories')
            ->row();
    }

    /**
     * Get invoice category
     * 
     * @return object Category object
     */
    public function get_invoice_category() {
        return $this->get_by_code('invoice');
    }

    /**
     * Get daily fees category
     * 
     * @return object Category object
     */
    public function get_daily_fees_category() {
        return $this->get_by_code('daily_fees');
    }
}
