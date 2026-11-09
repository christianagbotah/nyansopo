<?php
/**
 * Conduct Items Controller
 * 
 * Manages admin CRUD operations for conduct items that appear on student
 * report cards. Provides list view, create/edit forms, delete functionality,
 * drag-and-drop reordering, and active/inactive toggling.
 * 
 * Access restricted to admin users only.
 * 
 * @package     Controllers
 * @author      School Manager System
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Conduct_items extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load database and session libraries
        $this->load->database();
        $this->load->library('session');
        
        // Load the Conduct_items_model
        $this->load->model('Conduct_items_model');
        
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
     * Index - List all conduct items
     * 
     * Displays a table of all conduct items (active and inactive) with
     * options to add, edit, delete, reorder, and toggle active status.
     * 
     * Requirements: 6.1, 6.11
     */
    public function index() {
        // Get all conduct items including inactive ones for admin view
        $data['conduct_items'] = $this->Conduct_items_model->get_all_items(true);
        
        // Set page title
        $data['page_title'] = 'Manage Conduct Items';
        $data['page_name'] = 'conduct_items';
        
        // Load the admin view with the data
        $this->load->view('backend/index', $data);
    }

    /**
     * Create - Display form to create new conduct item (GET) or handle creation (POST)
     * 
     * GET: Displays a form for creating a new conduct item with name input field
     * POST: Validates and creates new conduct item
     * 
     * Requirements: 1.2, 6.3, 7.1, 7.3, 7.5
     */
    public function create() {
        // Check if this is a POST request
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            return $this->create_post();
        }
        
        // GET request - show form
        $data['page_title'] = 'Add Conduct Item';
        $data['page_name'] = 'conduct_items/create';
        
        $this->load->view('backend/index', $data);
    }

    /**
     * Create POST - Handle form submission for creating conduct item
     * 
     * Validates input and creates new conduct item in database.
     * Returns JSON for AJAX calls or redirects with flash message.
     * 
     * Requirements: 1.2, 6.3, 7.1, 7.3, 7.5
     */
    private function create_post() {
        // Check if it's an AJAX request
        $is_ajax = $this->input->is_ajax_request();
        
        // Log the request
        log_message('debug', 'Conduct Items Create POST called. AJAX: ' . ($is_ajax ? 'Yes' : 'No'));
        log_message('debug', 'POST Data: ' . print_r($this->input->post(), true));
        
        // Load form validation library
        $this->load->library('form_validation');
        
        // Set validation rules
        $this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[100]');
        
        if ($this->form_validation->run() === FALSE) {
            $error_msg = validation_errors();
            log_message('error', 'Conduct Items validation failed: ' . $error_msg);
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $error_msg]);
                return;
            }
            // Validation failed
            $this->session->set_flashdata('error_message', $error_msg);
            redirect(site_url('admin/conduct_items/create'), 'refresh');
            return;
        }
        
        // Prepare data for creation
        $name = $this->input->post('name', TRUE);
        $display_order = $this->input->post('display_order', TRUE) ?: 1;
        $is_active = $this->input->post('is_active') ? 1 : 0;
        
        log_message('debug', 'Creating conduct item: ' . $name);
        
        // Check for duplicate name using model method
        if ($this->Conduct_items_model->name_exists($name)) {
            $error_msg = 'A conduct item with this name already exists.';
            log_message('error', 'Duplicate conduct item name: ' . $name);
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $error_msg]);
                return;
            }
            $this->session->set_flashdata('error_message', $error_msg);
            redirect(site_url('admin/conduct_items/create'), 'refresh');
            return;
        }
        
        // Create the conduct item
        $data = array(
            'name' => $name,
            'display_order' => $display_order,
            'is_active' => $is_active
        );
        
        $result = $this->Conduct_items_model->create($data);
        
        log_message('debug', 'Create result: ' . ($result ? 'Success' : 'Failed'));
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Conduct item created successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create conduct item']);
            }
            return;
        }
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Conduct item created successfully.');
            redirect(site_url('admin/conduct_items'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to create conduct item. Please try again.');
            redirect(site_url('admin/conduct_items/create'), 'refresh');
        }
    }

    /**
     * Edit - Display form to edit existing conduct item (GET) or handle update (POST)
     * 
     * GET: Displays pre-filled form for editing conduct item
     * POST: Validates and updates conduct item
     * 
     * @param int $id Conduct item ID
     * 
     * Requirements: 1.3, 6.5, 7.1, 7.3, 7.5
     */
    public function edit($id = null) {
        // Validate ID
        if (!$id || !is_numeric($id)) {
            $this->session->set_flashdata('error_message', 'Invalid conduct item ID.');
            redirect(site_url('admin/conduct_items'), 'refresh');
            return;
        }
        
        // Check if this is a POST request
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            return $this->edit_post($id);
        }
        
        // GET request - show form with pre-filled data
        $conduct_item = $this->Conduct_items_model->get_by_id($id);
        
        if (!$conduct_item) {
            $this->session->set_flashdata('error_message', 'Conduct item not found.');
            redirect(site_url('admin/conduct_items'), 'refresh');
            return;
        }
        
        $data['conduct_item'] = $conduct_item;
        $data['page_title'] = 'Edit Conduct Item';
        $data['page_name'] = 'conduct_items/edit';
        
        $this->load->view('backend/index', $data);
    }

    /**
     * Edit POST - Handle form submission for updating conduct item
     * 
     * Validates input and updates conduct item in database.
     * Returns JSON for AJAX calls or redirects with flash message.
     * 
     * @param int $id Conduct item ID
     * 
     * Requirements: 1.3, 6.5, 7.1, 7.3, 7.5
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
            redirect(site_url('admin/conduct_items/edit/' . $id), 'refresh');
            return;
        }
        
        // Prepare data for update
        $name = $this->input->post('name', TRUE);
        $display_order = $this->input->post('display_order', TRUE) ?: 1;
        $is_active = $this->input->post('is_active') ? 1 : 0;
        
        // Check for duplicate name (excluding current item)
        if ($this->Conduct_items_model->name_exists($name, $id)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'A conduct item with this name already exists.']);
                return;
            }
            $this->session->set_flashdata('error_message', 'A conduct item with this name already exists.');
            redirect(site_url('admin/conduct_items/edit/' . $id), 'refresh');
            return;
        }
        
        // Update the conduct item
        $data = array(
            'name' => $name,
            'display_order' => $display_order,
            'is_active' => $is_active
        );
        
        $result = $this->Conduct_items_model->update($id, $data);
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Conduct item updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update conduct item']);
            }
            return;
        }
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Conduct item updated successfully.');
            redirect(site_url('admin/conduct_items'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to update conduct item. Please try again.');
            redirect(site_url('admin/conduct_items/edit/' . $id), 'refresh');
        }
    }

    /**
     * Delete - Remove a conduct item
     * 
     * Permanently deletes a conduct item from the database. This is a POST-only
     * method that returns JSON for AJAX calls. Orphaned references in the
     * aggregation table will be handled gracefully by displaying "N/A" in reports.
     * 
     * Requirements: 1.4, 6.7, 6.9, 7.7
     * 
     * @param int $id Conduct item ID
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
                'message' => 'Invalid conduct item ID'
            ]);
            return;
        }
        
        // Attempt to delete the item
        $result = $this->Conduct_items_model->delete($id);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Conduct item deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete conduct item. Item may not exist.'
            ]);
        }
    }

    /**
     * Reorder - Update display order of conduct items
     * 
     * Accepts an order_map array that maps item IDs to their new display_order
     * values. This is typically called after drag-and-drop reordering in the UI.
     * Returns JSON response for AJAX calls.
     * 
     * Requirements: 1.5, 6.11
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
        $result = $this->Conduct_items_model->update_order($order_map);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Conduct items reordered successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to reorder conduct items'
            ]);
        }
    }

    /**
     * Toggle - Toggle active/inactive status
     * 
     * Switches the is_active status of a conduct item between 0 and 1.
     * Active items (is_active=1) appear in the teacher interface, while
     * inactive items (is_active=0) are hidden but retained in the database.
     * Returns JSON response for AJAX calls.
     * 
     * Requirements: 1.5, 6.11
     * 
     * @param int $id Conduct item ID
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
                'message' => 'Invalid conduct item ID'
            ]);
            return;
        }
        
        // Attempt to toggle active status
        $result = $this->Conduct_items_model->toggle_active($id);
        
        if ($result) {
            // Get the updated item to determine new status
            $item = $this->Conduct_items_model->get_by_id($id);
            $status_text = $item->is_active ? 'activated' : 'deactivated';
            
            echo json_encode([
                'status' => 'success',
                'message' => "Conduct item {$status_text} successfully",
                'is_active' => $item->is_active,
                'new_status' => $item->is_active // Add new_status for JS compatibility
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to toggle conduct item status. Item may not exist.'
            ]);
        }
    }
    
    /**
     * Get Form - Load modal form for add/edit
     * 
     * Returns the modal form HTML for creating or editing a conduct item.
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
            $item = $this->Conduct_items_model->get_by_id($id);
            if (!$item) {
                echo '<p style="color:red;text-align:center;padding:20px;">Item not found</p>';
                return;
            }
            $data['conduct_item'] = $item;
        }
        
        $this->load->view('backend/admin/modal_conduct_form', $data);
    }
}
