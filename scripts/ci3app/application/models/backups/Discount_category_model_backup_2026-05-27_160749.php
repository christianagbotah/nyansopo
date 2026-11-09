<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount Category Model
 * 
 * Manages discount categories (Invoice vs Daily Fees)
 * 
 * @package SchoolManager
 * @subpackage Models
 * @category Categories
 * @author Enterprise Team
 */
class Discount_category_model extends CI_Model {

    /**
     * Get all discount categories
     * 
     * @return array Array of category objects
     */
    public function get_all() {
        return $this->db
            ->order_by('category_id', 'ASC')
            ->get('discount_categories')
            ->result();
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
