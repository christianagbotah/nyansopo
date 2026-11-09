<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Locations Controller
 * 
 * Manages location registry for multi-location sync support.
 * Provides CRUD operations for location management.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 */
class Sync_locations extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Check if sync module is enabled
        $this->check_sync_enabled();
        
        // Load model
        $this->load->model('Location_registry_model');
    }
    
    /**
     * Check if sync module is enabled
     * 
     * Redirects to dashboard with error message if sync is disabled.
     */
    private function check_sync_enabled() {
        // Load sync configuration
        $this->config->load('sync', TRUE);
        
        // Check database setting first
        $setting = $this->db->get_where('settings', ['type' => 'offline_online_mode'])->row();
        
        if ($setting) {
            $sync_enabled = ($setting->description === '1' || $setting->description === 1);
        } else {
            // Fall back to config file
            $sync_enabled = $this->config->item('sync_enabled', 'sync') ?? TRUE;
        }
        
        if (!$sync_enabled) {
            // Sync module is disabled
            $this->session->set_flashdata('error_message', get_phrase('sync_module_disabled'));
            redirect(site_url('admin/dashboard'), 'refresh');
        }
    }
    
    /**
     * Location list/index page
     */
    public function index() {
        // Get all locations
        $locations = $this->db->order_by('priority', 'DESC')
                             ->order_by('created_at', 'DESC')
                             ->get('location_registry')
                             ->result_array();
        
        // Get statistics
        $stats = $this->Location_registry_model->get_location_stats();
        
        $page_data['locations'] = $locations;
        $page_data['stats'] = $stats;
        $page_data['page_name'] = 'sync_locations';
        $page_data['page_title'] = get_phrase('sync_locations');
        
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Add new location (modal view)
     */
    public function add() {
        $this->load->view('backend/admin/modal_sync_location_add');
    }
    
    /**
     * Modal popup for adding location
     */
    public function modal_add() {
        $this->load->view('backend/admin/modal_sync_location_add');
    }
    
    /**
     * Create new location (POST handler)
     */
    public function create() {
        // Load form validation library
        $this->load->library('form_validation');
        
        // Validate input
        $this->form_validation->set_rules('location_name', 'Location Name', 'required|trim');
        $this->form_validation->set_rules('device_id', 'Device ID', 'required|trim|is_unique[location_registry.device_id]');
        $this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive,suspended]');
        $this->form_validation->set_rules('priority', 'Priority', 'required|integer|greater_than[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('sync_enabled', 'Sync Enabled', 'required|in_list[0,1]');
        
        if ($this->form_validation->run() == FALSE) {
            // Check if it's an AJAX request
            if ($this->input->is_ajax_request()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => validation_errors()
                ]);
                return;
            }
            
            $this->session->set_flashdata('error_message', validation_errors());
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Prepare data
        $data = [
            'location_name' => $this->input->post('location_name', TRUE),
            'device_id' => $this->input->post('device_id', TRUE),
            'description' => $this->input->post('description', TRUE),
            'api_endpoint' => $this->input->post('api_endpoint', TRUE),
            'status' => $this->input->post('status', TRUE),
            'priority' => (int)$this->input->post('priority', TRUE),
            'sync_enabled' => (int)$this->input->post('sync_enabled', TRUE),
            'sync_interval' => $this->input->post('sync_interval', TRUE) ? (int)$this->input->post('sync_interval', TRUE) : 15,
            'contact_email' => $this->input->post('contact_email', TRUE),
            'contact_phone' => $this->input->post('contact_phone', TRUE),
            'timezone' => $this->input->post('timezone', TRUE) ?: 'UTC',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert location
        $result = $this->Location_registry_model->insert($data);
        
        // Check if it's an AJAX request
        if ($this->input->is_ajax_request()) {
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location added successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to add location. Please try again.'
                ]);
            }
            return;
        }
        
        // Regular form submission
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Location added successfully');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to add location. Please try again.');
        }
        
        redirect(site_url('admin/sync_locations'), 'refresh');
    }
    
    /**
     * Edit location
     */
    public function edit($location_id = null) {
        if (!$location_id) {
            $this->session->set_flashdata('error_message', 'Invalid location ID');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Get location
        $location = $this->Location_registry_model->get_location($location_id);
        
        if (!$location) {
            $this->session->set_flashdata('error_message', 'Location not found');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        $page_data['location'] = $location;
        $page_data['page_name'] = 'sync_location_edit';
        $page_data['page_title'] = get_phrase('edit_location');
        
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Modal popup for editing location
     */
    public function modal_edit($location_id = null) {
        if (!$location_id) {
            echo '<div class="alert alert-danger">Invalid location ID</div>';
            return;
        }
        
        // Get location
        $location = $this->Location_registry_model->get_location($location_id);
        
        if (!$location) {
            echo '<div class="alert alert-danger">Location not found</div>';
            return;
        }
        
        $data['location'] = $location;
        $this->load->view('backend/admin/modal_sync_location_edit', $data);
    }
    
    /**
     * Update location (POST handler)
     */
    public function update($location_id = null) {
        if (!$location_id) {
            // Check if it's an AJAX request
            if ($this->input->is_ajax_request()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid location ID'
                ]);
                return;
            }
            
            $this->session->set_flashdata('error_message', 'Invalid location ID');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Validate input
        $this->load->library('form_validation');
        $this->form_validation->set_rules('location_name', 'Location Name', 'required|trim');
        
        if ($this->form_validation->run() == FALSE) {
            // Check if it's an AJAX request
            if ($this->input->is_ajax_request()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => validation_errors()
                ]);
                return;
            }
            
            $this->session->set_flashdata('error_message', validation_errors());
            redirect(site_url('admin/sync_locations/edit/' . $location_id), 'refresh');
            return;
        }
        
        // Prepare data
        $data = [
            'location_name' => $this->input->post('location_name'),
            'api_endpoint' => $this->input->post('api_endpoint'),
            'status' => $this->input->post('status', TRUE),
            'priority' => $this->input->post('priority', TRUE),
            'contact_email' => $this->input->post('contact_email'),
            'contact_phone' => $this->input->post('contact_phone'),
            'timezone' => $this->input->post('timezone'),
            'sync_enabled' => $this->input->post('sync_enabled') ? 1 : 0,
            'sync_interval' => $this->input->post('sync_interval', TRUE) ? (int)$this->input->post('sync_interval', TRUE) : 15,
            'description' => $this->input->post('description')
        ];
        
        // Update location
        $result = $this->Location_registry_model->update($location_id, $data);
        
        // Check if it's an AJAX request
        if ($this->input->is_ajax_request()) {
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location updated successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update location'
                ]);
            }
            return;
        }
        
        // Regular form submission
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Location updated successfully');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to update location');
        }
        
        redirect(site_url('admin/sync_locations'), 'refresh');
    }
    
    /**
     * View location details
     */
    public function view($location_id = null) {
        if (!$location_id) {
            $this->session->set_flashdata('error_message', 'Invalid location ID');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Get location
        $location = $this->Location_registry_model->get_location($location_id);
        
        if (!$location) {
            $this->session->set_flashdata('error_message', 'Location not found');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Get sync metrics for this location (using device_id)
        $sync_history = [];
        if (!empty($location->device_id)) {
            $sync_history = $this->db->where('source_device_id', $location->device_id)
                                     ->or_where('target_device_id', $location->device_id)
                                     ->order_by('synced_at', 'DESC')
                                     ->limit(50)
                                     ->get('sync_audit_log')
                                     ->result_array();
        }
        
        // Get conflicts for this location (if location_id column exists)
        $conflicts = [];
        if ($this->db->field_exists('location_id', 'sync_conflicts')) {
            $conflicts = $this->db->where('location_id', $location_id)
                                 ->where('status', 'pending')
                                 ->order_by('detected_at', 'DESC')
                                 ->get('sync_conflicts')
                                 ->result_array();
        }
        
        $page_data['location'] = $location;
        $page_data['sync_history'] = $sync_history;
        $page_data['conflicts'] = $conflicts;
        $page_data['page_name'] = 'sync_location_view';
        $page_data['page_title'] = $location->location_name;
        
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Delete location
     */
    public function delete($location_id = null) {
        if (!$location_id) {
            $this->session->set_flashdata('error_message', 'Invalid location ID');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Check if this is the current location
        $current_location = $this->Location_registry_model->get_current_location();
        if ($current_location && $current_location->id == $location_id) {
            $this->session->set_flashdata('error_message', 'Cannot delete the current location');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Delete location
        $result = $this->db->where('id', $location_id)->delete('location_registry');
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Location deleted successfully');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to delete location');
        }
        
        redirect(site_url('admin/sync_locations'), 'refresh');
    }
    
    /**
     * Activate location
     */
    public function activate($location_id = null) {
        if (!$location_id) {
            $this->session->set_flashdata('error_message', 'Invalid location ID');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        $result = $this->Location_registry_model->activate_location($location_id);
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Location activated successfully');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to activate location');
        }
        
        redirect(site_url('admin/sync_locations'), 'refresh');
    }
    
    /**
     * Deactivate location
     */
    public function deactivate($location_id = null) {
        if (!$location_id) {
            $this->session->set_flashdata('error_message', 'Invalid location ID');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        // Check if this is the current location
        $current_location = $this->Location_registry_model->get_current_location();
        if ($current_location && $current_location->id == $location_id) {
            $this->session->set_flashdata('error_message', 'Cannot deactivate the current location');
            redirect(site_url('admin/sync_locations'), 'refresh');
            return;
        }
        
        $result = $this->Location_registry_model->deactivate_location($location_id);
        
        if ($result) {
            $this->session->set_flashdata('flash_message', 'Location deactivated successfully');
        } else {
            $this->session->set_flashdata('error_message', 'Failed to deactivate location');
        }
        
        redirect(site_url('admin/sync_locations'), 'refresh');
    }
    
    /**
     * Test connection to location
     */
    public function test_connection($location_id = null) {
        if (!$location_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid location ID']);
            return;
        }
        
        $location = $this->Location_registry_model->get_location($location_id);
        
        if (!$location) {
            echo json_encode(['success' => false, 'message' => 'Location not found']);
            return;
        }
        
        if (empty($location->api_endpoint)) {
            echo json_encode(['success' => false, 'message' => 'No API endpoint configured']);
            return;
        }
        
        // Test connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $location->api_endpoint . '/sync_server/health_check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            echo json_encode(['success' => true, 'message' => 'Connection successful', 'response' => $response]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Connection failed (HTTP ' . $http_code . ')']);
        }
    }
}
