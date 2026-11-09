<?php
/**
 * Interest Items Controller
 * 
 * Manages admin CRUD operations for interest items that appear on student
 * report cards. Provides list view, create/edit forms, delete functionality,
 * drag-and-drop reordering, and active/inactive toggling.
 * 
 * Access restricted to admin users only.
 * 
 * @package     Controllers
 * @author      School Manager System
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Interest_items extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load database and session libraries
        $this->load->database();
        $this->load->library('session');
        
        // Load the Interest_items_model
        $this->load->model('Interest_items_model');
        
        // Cache control - prevent caching of admin pages
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        
        // Check if user is logged in
        if ($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
        
        // Check if user has admin role
        if ($this->session->userdata('admin_login') != 1) {
            // User is not an admin, redirect to their appropriate dashboard
            $login_type = $this->session->userdata('login_type');
            
            if ($login_type == 'teacher') {
                redirect(site_url('teacher'), 'refresh');
            } elseif ($login_type == 'parent') {
                redirect(site_url('parents'), 'refresh');
            } elseif ($login_type == 'student') {
                redirect(site_url('student'), 'refresh');
            } else {
                redirect(site_url('login'), 'refresh');
            }
        }
    }

    /**
     * Index - List all interest items
     * 
     * Displays a table of all interest items (active and inactive) with
     * options to add, edit, delete, reorder, and toggle active status.
     * 
     * Requirements: 6.2, 6.12
     */
    public function index() {
        // Get all interest items including inactive ones for admin view
        $data['interest_items'] = $this->Interest_items_model->get_all_items(true);
        
        // Set page title
        $data['page_title'] = 'Manage Interest Items';
        $data['page_name'] = 'interest_items';
        
        // Load the admin view with the data
        $this->load->view('backend/index', $data);
    }

    /**
     * Create - Display form to create new interest item (GET) or handle creation (POST)
     * 
     * GET: Displays a form for creating a new interest item with name input field
     * POST: Validates and creates new interest item
     * 
     * Requirements: 2.2, 6.4, 7.2, 7.4, 7.6
     */
    public function create() {
        // Check if this is a POST request
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            return $this->create_post();
        }
        
        // GET request - show form
        $data['page_title'] = 'Add Interest Item';
        $data['page_name'] = 'interest_items/create';
        
        $this->load->view('backend/index', $data);
    }

    /**
     * Create POST - Handle form submission for creating interest item
     * 
     * Validates input and creates new interest item in database.
     * Returns JSON for AJAX calls or redirects with flash message.
     * 
     * Requirements: 2.2, 6.4, 7.2, 7.4, 7.6
     */
    private function create_post() {
        // Check if it's an AJAX request
        $is_ajax = $this->input->is_ajax_request();
        
        // Load form validation library
        $this->load->library('form_validation');
        
        // Set validation rules
        $this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[100]');
        
        if ($this->form_validation->run() === FALSE) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => validation_errors()]);
                return;
            }
            // Validation failed
            $this->session->set_flashdata('error_message', validation_errors());
            redirect(site_url('admin/interest_items/create'), 'refresh');
            return;
        }
        
        // Prepare data for creation
        $name = $this->input->post('name', TRUE);
        $display_order = $this->input->post('display_order', TRUE) ?: 1;
        $is_active = $this->input->post('is_active') ? 1 : 0;
        
        // Check for duplicate name using model method
        if ($this->Interest_items_model->name_exists($name)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'An interest item with this name already exists.']);
                return;
            }
            $this->session->set_flashdata('error_message', 'An interest item with this name already exists.');
            redirect(site_url('admin/interest_items/create'), 'refresh');
            return;
        }
        
        // Create the interest item
        $data = array(
            'name' => $name,
            'display_order' => $display_order,
            'is_active' => $is_active
        );
        
        $result = $this->Interest_items_model->create($data);
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Interest item created successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create interest item']);
            }
            return;
        }
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Interest item created successfully.');
            redirect(site_url('admin/interest_items'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to create interest item. Please try again.');
            redirect(site_url('admin/interest_items/create'), 'refresh');
        }
    }

    /**
     * Edit - Display form to edit existing interest item (GET) or handle update (POST)
     * 
     * GET: Displays pre-filled form for editing interest item
     * POST: Validates and updates interest item
     * 
     * @param int $id Interest item ID
     * 
     * Requirements: 2.3, 6.6, 7.2, 7.4, 7.6
     */
    public function edit($id = null) {
        // Validate ID
        if (!$id || !is_numeric($id)) {
            $this->session->set_flashdata('error_message', 'Invalid interest item ID.');
            redirect(site_url('admin/interest_items'), 'refresh');
            return;
        }
        
        // Check if this is a POST request
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            return $this->edit_post($id);
        }
        
        // GET request - show form with pre-filled data
        $interest_item = $this->Interest_items_model->get_by_id($id);
        
        if (!$interest_item) {
            $this->session->set_flashdata('error_message', 'Interest item not found.');
            redirect(site_url('admin/interest_items'), 'refresh');
            return;
        }
        
        $data['interest_item'] = $interest_item;
        $data['page_title'] = 'Edit Interest Item';
        $data['page_name'] = 'interest_items/edit';
        
        $this->load->view('backend/index', $data);
    }

    /**
     * Edit POST - Handle form submission for updating interest item
     * 
     * Validates input and updates interest item in database.
     * Returns JSON for AJAX calls or redirects with flash message.
     * 
     * @param int $id Interest item ID
     * 
     * Requirements: 2.3, 6.6, 7.2, 7.4, 7.6
     */
    private function edit_post($id) {
        // Check if it's an AJAX request
        $is_ajax = $this->input->is_ajax_request();
        
        // Load form validation library
        $this->load->library('form_validation');
        
        // Set validation rules
        $this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[100]');
        
        if ($this->form_validation->run() === FALSE) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => validation_errors()]);
                return;
            }
            // Validation failed
            $this->session->set_flashdata('error_message', validation_errors());
            redirect(site_url('admin/interest_items/edit/' . $id), 'refresh');
            return;
        }
        
        // Prepare data for update
        $name = $this->input->post('name', TRUE);
        $display_order = $this->input->post('display_order', TRUE) ?: 1;
        $is_active = $this->input->post('is_active') ? 1 : 0;
        
        // Check for duplicate name (excluding current item)
        if ($this->Interest_items_model->name_exists($name, $id)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'An interest item with this name already exists.']);
                return;
            }
            $this->session->set_flashdata('error_message', 'An interest item with this name already exists.');
            redirect(site_url('admin/interest_items/edit/' . $id), 'refresh');
            return;
        }
        
        // Update the interest item
        $data = array(
            'name' => $name,
            'display_order' => $display_order,
            'is_active' => $is_active
        );
        
        $result = $this->Interest_items_model->update($id, $data);
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Interest item updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update interest item']);
            }
            return;
        }
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Interest item updated successfully.');
            redirect(site_url('admin/interest_items'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to update interest item. Please try again.');
            redirect(site_url('admin/interest_items/edit/' . $id), 'refresh');
        }
    }

    /**
     * Delete - Remove an interest item
     * 
     * Permanently deletes an interest item from the database. This is a POST-only
     * method that returns JSON for AJAX calls. Orphaned references in the
     * aggregation table will be handled gracefully by displaying "N/A" in reports.
     * 
     * Requirements: 2.4, 6.8, 6.10, 7.8
     * 
     * @param int $id Interest item ID
     * @return void Outputs JSON response
     */
    public function delete($id = null) {
        // Set JSON header for AJAX response
        header('Content-Type: application/json');
        
        // Validate that this is a POST request
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }
        
        // Validate ID parameter
        if (!$id || !is_numeric($id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid interest item ID'
            ]);
            return;
        }
        
        // Attempt to delete the item
        $result = $this->Interest_items_model->delete($id);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Interest item deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete interest item. Item may not exist.'
            ]);
        }
    }

    /**
     * Reorder - Update display order of interest items
     * 
     * Accepts an order_map array that maps item IDs to their new display_order
     * values. This is typically called after drag-and-drop reordering in the UI.
     * Returns JSON response for AJAX calls.
     * 
     * Requirements: 2.5, 6.12
     * 
     * POST Parameters:
     *   - order_map: Associative array mapping item_id => new_display_order
     *     Example: {'1': 1, '2': 2, '3': 3}
     * 
     * @return void Outputs JSON response
     */
    public function reorder() {
        // Set JSON header for AJAX response
        header('Content-Type: application/json');
        
        // Validate that this is a POST request
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }
        
        // Get order_map from POST data
        $order_map = $this->input->post('order_map');
        
        // Validate order_map exists and is an array
        if (!$order_map || !is_array($order_map) || empty($order_map)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid order data. Please provide an order_map array.'
            ]);
            return;
        }
        
        // Attempt to update order
        $result = $this->Interest_items_model->update_order($order_map);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Interest items reordered successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to reorder interest items'
            ]);
        }
    }

    /**
     * Toggle - Toggle active/inactive status
     * 
     * Switches the is_active status of an interest item between 0 and 1.
     * Active items (is_active=1) appear in the teacher interface, while
     * inactive items (is_active=0) are hidden but retained in the database.
     * Returns JSON response for AJAX calls.
     * 
     * Requirements: 2.5, 6.12
     * 
     * @param int $id Interest item ID
     * @return void Outputs JSON response
     */
    public function toggle($id = null) {
        // Set JSON header for AJAX response
        header('Content-Type: application/json');
        
        // Validate that this is a POST request
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }
        
        // Validate ID parameter
        if (!$id || !is_numeric($id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid interest item ID'
            ]);
            return;
        }
        
        // Attempt to toggle active status
        $result = $this->Interest_items_model->toggle_active($id);
        
        if ($result) {
            // Get the updated item to determine new status
            $item = $this->Interest_items_model->get_by_id($id);
            $status_text = $item->is_active ? 'activated' : 'deactivated';
            
            echo json_encode([
                'status' => 'success',
                'message' => "Interest item {$status_text} successfully",
                'is_active' => $item->is_active,
                'new_status' => $item->is_active // Add new_status for JS compatibility
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to toggle interest item status. Item may not exist.'
            ]);
        }
    }
    
    /**
     * Get Form - Load modal form for add/edit
     * 
     * Returns the modal form HTML for creating or editing an interest item.
     * Used by AJAX to display the form in the centralized modal system.
     * 
     * POST Parameters:
     *   - id (optional): If provided, loads edit form with existing data
     * 
     * @return void Outputs HTML view
     */
    public function get_form()
    {
        $id = $this->input->post('id');
        $data = [];
        
        if ($id) {
            $item = $this->Interest_items_model->get_by_id($id);
            if (!$item) {
                echo '<p style="color:red;text-align:center;padding:20px;">Item not found</p>';
                return;
            }
            $data['interest_item'] = $item;
        }
        
        $this->load->view('backend/admin/modal_interest_form', $data);
    }

    /**
     * Get AJAX list for Select2 dropdown
     * 
     * Returns JSON list of active interest items for use in Select2 dropdowns.
     * Used in student_marksheet.php for multi-select interest field.
     * 
     * GET Parameters:
     *   - q (optional): Search term to filter items
     * 
     * @return void Outputs JSON response
     */
    public function get_ajax_list() {
        header('Content-Type: application/json');
        
        $search_term = $this->input->get('q', TRUE);
        
        // Get active items
        $items = $this->Interest_items_model->get_active();
        
        // Filter by search term if provided
        if ($search_term) {
            $items = array_filter($items, function($item) use ($search_term) {
                return stripos($item->name, $search_term) !== false;
            });
        }
        
        // Format for Select2
        $results = array();
        foreach ($items as $item) {
            $results[] = array(
                'id' => $item->id,
                'text' => $item->name
            );
        }
        
        echo json_encode(['results' => $results]);
    }
}
