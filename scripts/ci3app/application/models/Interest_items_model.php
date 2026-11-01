<?php
/**
 * Interest Items Model
 * 
 * Manages interest items for student report cards. Provides CRUD operations,
 * ordering, activation/deactivation, and initialization of default items.
 * 
 * Interest items are displayed to teachers when filling out report cards,
 * and can be configured by administrators through the admin interface.
 * 
 * @package     Models
 * @author      School Manager System
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Interest_items_model extends MY_Model {

    protected $table = 'interest_items';
    protected $primary_key = 'id';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Retrieve all interest items
     * 
     * @param bool $include_inactive Whether to include inactive items (default: true)
     * @return array Array of interest item objects
     */
    public function get_all_items($include_inactive = true) {
        $this->db->order_by('display_order', 'ASC');
        
        if (!$include_inactive) {
            $this->db->where('is_active', 1);
        }
        
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Retrieve only active items ordered by display_order
     * 
     * Used by teacher interface to display available interest items
     * for report card selection.
     * 
     * @return array Array of active interest item objects
     */
    public function get_active() {
        $this->db->where('is_active', 1);
        $this->db->order_by('display_order', 'ASC');
        
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Retrieve single item by ID
     * 
     * @param int $id Interest item ID
     * @return object|null Interest item object or null if not found
     */
    public function get_by_id($id) {
        $query = $this->db->get_where($this->table, array('id' => $id));
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        
        return null;
    }

    /**
     * Create new interest item
     * 
     * Validates that name is not empty, not > 100 characters, and unique.
     * Automatically assigns display_order as the highest existing order + 1.
     * Uses parent::insert() for automatic sync tracking.
     * 
     * @param array $data Array with 'name' key (required), optional 'is_active'
     * @return int|false New item ID on success, false on failure
     */
    public function create($data) {
        // Validate name exists
        if (!isset($data['name']) || trim($data['name']) === '') {
            return false;
        }

        $name = trim($data['name']);

        // Validate name length
        if (strlen($name) > 100) {
            return false;
        }

        // Check for duplicate name
        if ($this->name_exists($name)) {
            return false;
        }

        // Get highest display_order and add 1
        $this->db->select_max('display_order');
        $max_order = $this->db->get($this->table)->row()->display_order ?? 0;

        // Prepare insert data
        $insert_data = array(
            'name' => $name,
            'display_order' => $max_order + 1,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'created_at' => date('Y-m-d H:i:s')
        );

        // Use parent insert for sync tracking
        return parent::insert($insert_data);
    }

    /**
     * Update interest item
     * 
     * Validates that name is not empty, not > 100 characters, and unique
     * (excluding the current item being updated).
     * Uses parent::update() for automatic sync tracking.
     * 
     * @param int $id Interest item ID
     * @param array $data Array with optional 'name', 'display_order', 'is_active' keys
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        // Validate item exists (use parent::get from MY_Model)
        if (!parent::get($id)) {
            return false;
        }

        $update_data = array();

        // Validate and prepare name if provided
        if (isset($data['name'])) {
            $name = trim($data['name']);

            if ($name === '') {
                return false;
            }

            if (strlen($name) > 100) {
                return false;
            }

            // Check for duplicate name (excluding current item)
            if ($this->name_exists($name, $id)) {
                return false;
            }

            $update_data['name'] = $name;
        }

        // Add display_order if provided
        if (isset($data['display_order'])) {
            $update_data['display_order'] = (int)$data['display_order'];
        }

        // Add is_active if provided
        if (isset($data['is_active'])) {
            $update_data['is_active'] = (int)$data['is_active'];
        }

        // Nothing to update
        if (empty($update_data)) {
            return false;
        }

        // Use parent update for sync tracking
        return parent::update($id, $update_data);
    }

    /**
     * Delete interest item (hard delete)
     * 
     * Permanently removes the item from the database. Note that this may
     * create orphaned references in the aggregation table if the item
     * is used in existing report cards. Foreign keys are set to SET NULL
     * to handle this gracefully.
     * Uses parent::delete() for automatic sync tracking (soft delete if table has deleted_at).
     * 
     * @param int $id Interest item ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        // Validate item exists (use parent::get from MY_Model)
        if (!parent::get($id)) {
            return false;
        }

        // Use parent delete for sync tracking
        return parent::delete($id);
    }

    /**
     * Update display order for multiple items
     * 
     * Allows bulk update of display_order values for drag-and-drop
     * reordering functionality in the admin interface.
     * 
     * @param array $order_map Associative array mapping item_id => new_display_order
     * @return bool True on success, false on failure
     */
    public function update_order($order_map) {
        if (empty($order_map) || !is_array($order_map)) {
            return false;
        }

        $this->db->trans_start();

        foreach ($order_map as $id => $order) {
            $this->db->where('id', $id);
            $this->db->update($this->table, array('display_order' => (int)$order));
        }

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Toggle active status
     * 
     * Switches is_active between 0 and 1.
     * 
     * @param int $id Interest item ID
     * @return bool True on success, false on failure
     */
    public function toggle_active($id) {
        $item = $this->get_by_id($id);

        if (!$item) {
            return false;
        }

        $new_status = $item->is_active ? 0 : 1;

        $this->db->where('id', $id);
        $this->db->update($this->table, array('is_active' => $new_status));

        return $this->db->affected_rows() > 0;
    }

    /**
     * Check if name already exists
     * 
     * Used for uniqueness validation during create and update operations.
     * 
     * @param string $name Interest item name to check
     * @param int $exclude_id Optional item ID to exclude (for update validation)
     * @return bool True if name exists, false otherwise
     */
    public function name_exists($name, $exclude_id = null) {
        $this->db->where('name', trim($name));

        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }

        $query = $this->db->get($this->table);
        return $query->num_rows() > 0;
    }

    /**
     * Initialize default interest items
     * 
     * Creates 12 default interest items with sequential display_order.
     * Only creates items if the table is empty to avoid duplicates.
     * 
     * @return bool True on success, false on failure
     */
    public function initialize_defaults() {
        // Check if items already exist
        $existing_count = $this->db->count_all($this->table);
        
        if ($existing_count > 0) {
            return false; // Don't initialize if items already exist
        }

        $default_items = array(
            'Reading',
            'Writing',
            'Mathematics',
            'Science',
            'Arts & Crafts',
            'Music',
            'Sports',
            'Drama',
            'Dancing',
            'Debate',
            'Leadership',
            'Technology'
        );

        $this->db->trans_start();

        $display_order = 1;
        foreach ($default_items as $item_name) {
            $data = array(
                'name' => $item_name,
                'display_order' => $display_order,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            );

            $this->db->insert($this->table, $data);
            $display_order++;
        }

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Get interest item name by ID
     * 
     * Helper method for report card display to retrieve interest name
     * from ID stored in aggregation table.
     * 
     * @param int $id Interest item ID
     * @return string|null Interest item name or null if not found
     */
    public function get_name_by_id($id) {
        $item = $this->get_by_id($id);
        return $item ? $item->name : null;
    }

    /**
     * Get multiple interest names by IDs
     * 
     * Efficiently retrieves names for multiple interest IDs in a single query.
     * Useful for displaying all interests selected for a student.
     * 
     * @param array $ids Array of interest item IDs
     * @return array Associative array mapping id => name
     */
    public function get_names_by_ids($ids) {
        if (empty($ids) || !is_array($ids)) {
            return array();
        }

        // Filter out null/empty values
        $ids = array_filter($ids, function($id) {
            return !is_null($id) && $id !== '';
        });

        if (empty($ids)) {
            return array();
        }

        $this->db->select('id, name');
        $this->db->where_in('id', $ids);
        $query = $this->db->get($this->table);

        $result = array();
        foreach ($query->result() as $row) {
            $result[$row->id] = $row->name;
        }

        return $result;
    }
}
