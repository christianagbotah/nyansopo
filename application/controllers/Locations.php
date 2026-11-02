<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Locations Controller
 * 
 * Handles location management for the multi-location bidirectional sync system.
 * Allows administrators to register, edit, and manage school branch locations.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 7, 8 - Location Registry and Management
 */
class Locations extends CI_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Load model first
        $this->load->model('Location_registry_model');
        
        // Try to load library, but don't fail if it doesn't work
        // We'll use the model directly as fallback
        $this->load->library('Location_manager');
        
        // If library failed to load, create a simple wrapper using the model
        if (!isset($this->Location_manager) || $this->Location_manager === null) {
            log_message('error', 'Location_manager library failed to load, using model directly');
            $this->Location_manager = $this->create_location_manager_wrapper();
        }
    }
    
    /**
     * Create a simple wrapper for location management using the model directly
     * This is a fallback if the library fails to load
     */
    private function create_location_manager_wrapper() {
        $model = $this->Location_registry_model;
        $wrapper = new class($model, $this) {
            private $model;
            private $ci;
            
            public function __construct($model, $ci) {
                $this->model = $model;
                $this->ci = $ci;
            }
            
            public function get_all_locations() {
                return $this->ci->db->order_by('priority', 'DESC')
                                   ->get('location_registry')
                                   ->result_array();
            }
            
            public function get_location_stats() {
                return [
                    'total' => $this->ci->db->where('status', 'active')->count_all_results('location_registry'),
                    'active' => $this->ci->db->where(['status' => 'active', 'sync_enabled' => 1])->count_all_results('location_registry'),
                    'inactive' => $this->ci->db->where('status', 'inactive')->count_all_results('location_registry'),
                    'suspended' => $this->ci->db->where('status', 'suspended')->count_all_results('location_registry'),
                    'stale' => 0
                ];
            }
            
            public function get_sync_summary() {
                return $this->get_all_locations();
            }
            
            public function get_location($id) {
                return $this->model->get_location($id);
            }
            
            public function register_location($data) {
                return $this->model->register_location($data);
            }
            
            public function update_location($id, $data) {
                return $this->ci->db->where('id', $id)->update('location_registry', $data);
            }
            
            public function activate_location($id) {
                return $this->model->activate_location($id);
            }
            
            public function deactivate_location($id) {
                return $this->model->deactivate_location($id);
            }
            
            public function suspend_location($id, $reason = '') {
                return $this->model->suspend_location($id, $reason);
            }
            
            public function test_connection($id) {
                return ['success' => false, 'message' => 'Connection test not available in fallback mode'];
            }
            
            public function generate_device_id() {
                return $this->model->generate_device_id();
            }
        };
        
        return $wrapper;
    }
    
    /**
     * Location list page
     */
    public function index() {
        $page_data['locations'] = $this->Location_manager->get_all_locations();
        $page_data['stats'] = $this->Location_manager->get_location_stats();
        $page_data['sync_summary'] = $this->Location_manager->get_sync_summary();
        $page_data['page_name'] = 'locations_list';
        $page_data['page_title'] = get_phrase('locations');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Add location page
     */
    public function add() {
        if ($this->input->post('submit')) {
            $data = [
                'location_name' => $this->input->post('location_name'),
                'device_id' => $this->input->post('device_id') ?: $this->Location_manager->generate_device_id(),
                'api_endpoint' => $this->input->post('api_endpoint'),
                'contact_email' => $this->input->post('contact_email'),
                'contact_phone' => $this->input->post('contact_phone'),
                'timezone' => $this->input->post('timezone'),
                'priority' => $this->input->post('priority') ?: 0,
                'description' => $this->input->post('description'),
                'status' => 'active'
            ];
            
            $location_id = $this->Location_manager->register_location($data);
            
            if ($location_id) {
                $this->session->set_flashdata('flash_message', get_phrase('location_added_successfully'));
                redirect(site_url('locations'));
            } else {
                $this->session->set_flashdata('error_message', get_phrase('failed_to_add_location'));
            }
        }
        
        $page_data['page_name'] = 'locations_form';
        $page_data['page_title'] = get_phrase('add_location');
        $page_data['form_action'] = 'add';
        $page_data['location'] = null;
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Edit location page
     * 
     * @param int $id Location ID
     */
    public function edit($id) {
        $location = $this->Location_manager->get_location($id);
        
        if (!$location) {
            $this->session->set_flashdata('error_message', get_phrase('location_not_found'));
            redirect(site_url('locations'));
        }
        
        if ($this->input->post('submit')) {
            $data = [
                'location_name' => $this->input->post('location_name'),
                'api_endpoint' => $this->input->post('api_endpoint'),
                'contact_email' => $this->input->post('contact_email'),
                'contact_phone' => $this->input->post('contact_phone'),
                'timezone' => $this->input->post('timezone'),
                'priority' => $this->input->post('priority') ?: 0,
                'description' => $this->input->post('description')
            ];
            
            if ($this->Location_manager->update_location($id, $data)) {
                $this->session->set_flashdata('flash_message', get_phrase('location_updated_successfully'));
                redirect(site_url('locations'));
            } else {
                $this->session->set_flashdata('error_message', get_phrase('failed_to_update_location'));
            }
        }
        
        $page_data['page_name'] = 'locations_form';
        $page_data['page_title'] = get_phrase('edit_location');
        $page_data['form_action'] = 'edit';
        $page_data['location'] = $location;
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Activate location
     * 
     * @param int $id Location ID
     */
    public function activate($id) {
        if ($this->Location_manager->activate_location($id)) {
            $this->session->set_flashdata('flash_message', get_phrase('location_activated'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('failed_to_activate_location'));
        }
        
        redirect(site_url('locations'));
    }
    
    /**
     * Deactivate location
     * 
     * @param int $id Location ID
     */
    public function deactivate($id) {
        if ($this->Location_manager->deactivate_location($id)) {
            $this->session->set_flashdata('flash_message', get_phrase('location_deactivated'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('failed_to_deactivate_location'));
        }
        
        redirect(site_url('locations'));
    }
    
    /**
     * Suspend location
     * 
     * @param int $id Location ID
     */
    public function suspend($id) {
        $reason = $this->input->post('reason') ?: 'Suspended by administrator';
        
        if ($this->Location_manager->suspend_location($id, $reason)) {
            $this->session->set_flashdata('flash_message', get_phrase('location_suspended'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('failed_to_suspend_location'));
        }
        
        redirect(site_url('locations'));
    }
    
    /**
     * Test connection to location
     * 
     * @param int $id Location ID
     */
    public function test_connection($id) {
        $result = $this->Location_manager->test_connection($id);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Get location status (AJAX)
     * 
     * @param int $id Location ID
     */
    public function status($id) {
        $location = $this->Location_manager->get_location($id);
        
        if (!$location) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Location not found']);
            return;
        }
        
        $status = [
            'id' => $location->id,
            'name' => $location->location_name,
            'device_id' => $location->device_id,
            'status' => $location->status,
            'last_sync' => $location->last_sync_at,
            'last_sync_status' => $location->last_sync_status,
            'sync_enabled' => $location->sync_enabled
        ];
        
        header('Content-Type: application/json');
        echo json_encode($status);
    }
    
    /**
     * Get all locations status (AJAX)
     */
    public function status_all() {
        $summary = $this->Location_manager->get_sync_summary();
        
        header('Content-Type: application/json');
        echo json_encode($summary);
    }
    
    /**
     * Generate new device ID (AJAX)
     */
    public function generate_device_id() {
        $device_id = $this->Location_manager->generate_device_id();
        
        header('Content-Type: application/json');
        echo json_encode(['device_id' => $device_id]);
    }
}
