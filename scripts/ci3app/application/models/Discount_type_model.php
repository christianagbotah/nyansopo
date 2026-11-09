<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount type model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Discount_type_model extends MY_Model {

    /**
     * Get all discount types
     * 
     * Overrides parent method to provide custom query with joins.
     * Maintains compatibility with parent signature.
     * 
     * @param array $where WHERE conditions (optional, not used in this implementation)
     * @param int|null $limit Optional limit
     * @param int|null $offset Optional offset
     * @return array Array of type objects
     */
    public function get_all($where = [], $limit = null, $offset = null) {
        $this->db
            ->select('dt.*, dc.name as category_name, dc.code as category_code')
            ->from('discount_types dt')
            ->join('discount_categories dc', 'dt.category_id = dc.category_id')
            ->where('dt.is_active', 1)
            ->order_by('dc.category_id', 'ASC')
            ->order_by('dt.name', 'ASC');
        
        // Apply limit and offset if provided
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db->get()->result();
    }

    /**
     * Get types by category
     * 
     * @param int $category_id Category ID
     * @return array Array of type objects
     */
    public function get_by_category($category_id) {
        return $this->db
            ->where('category_id', $category_id)
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get('discount_types')
            ->result();
    }

    /**
     * Get active types by category
     * 
     * @param int $category_id Category ID
     * @return array Array of type objects
     */
    public function get_active_by_category($category_id) {
        return $this->get_by_category($category_id);
    }

    /**
     * Get type by ID
     * 
     * @param int $type_id Type ID
     * @return object|null Type object or null
     */
    public function get($type_id) {
        return $this->db
            ->select('dt.*, dc.name as category_name, dc.code as category_code')
            ->from('discount_types dt')
            ->join('discount_categories dc', 'dt.category_id = dc.category_id')
            ->where('dt.discount_type_id', $type_id)
            ->get()
            ->row();
    }

    /**
     * Get type by code
     * 
     * @param string $code Type code
     * @return object|null Type object or null
     */
    public function get_by_code($code) {
        return $this->db
            ->select('dt.*, dc.name as category_name, dc.code as category_code')
            ->from('discount_types dt')
            ->join('discount_categories dc', 'dt.category_id = dc.category_id')
            ->where('dt.code', $code)
            ->get()
            ->row();
    }

    /**
     * Get invoice types
     * 
     * @return array Array of type objects
     */
    public function get_invoice_types() {
        return $this->db
            ->select('dt.*')
            ->from('discount_types dt')
            ->join('discount_categories dc', 'dt.category_id = dc.category_id')
            ->where('dc.code', 'invoice')
            ->where('dt.is_active', 1)
            ->order_by('dt.name', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Get daily fees types
     * 
     * @return array Array of type objects
     */
    public function get_daily_fees_types() {
        return $this->db
            ->select('dt.*')
            ->from('discount_types dt')
            ->join('discount_categories dc', 'dt.category_id = dc.category_id')
            ->where('dc.code', 'daily_fees')
            ->where('dt.is_active', 1)
            ->order_by('dt.name', 'ASC')
            ->get()
            ->result();
    }
}
