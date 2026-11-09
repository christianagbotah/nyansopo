<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Head Teacher Remarks Controller
 * 
 * Manages CRUD operations for configurable head teacher remark ranges.
 * Admin-only access for managing score-based remark assignments.
 * 
 * @package    School Management System
 * @subpackage Controllers
 * @category   Academic Configuration
 * @author     School Management System
 * @version    1.0
 */
class Head_teacher_remarks extends CI_Controller {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
        
        $this->load->model('head_teacher_remarks_model');
        $this->load->library('form_validation');
        $this->load->helper('permission');
    }

    /**
     * Main index page - displays list of all remark ranges
     */
    public function index() {
        // Check permission or admin access
        if (!has_admin_or_permission('head_teacher_remarks', 'view', 1)) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_permission_to_access_this_module'));
            redirect(site_url($this->session->userdata('login_type') . '/dashboard'), 'refresh');
        }
        
        $page_data['page_name'] = 'head_teacher_remarks';
        $page_data['page_title'] = get_phrase('head_teacher_remarks_ranges');
        $page_data['remarks'] = $this->head_teacher_remarks_model->get_all(true);
        
        // Pass user permissions to view
        $page_data['can_add'] = has_admin_or_permission('head_teacher_remarks', 'add', 1);
        $page_data['can_edit'] = has_admin_or_permission('head_teacher_remarks', 'edit', 1);
        $page_data['can_delete'] = has_admin_or_permission('head_teacher_remarks', 'delete', 1);
        $page_data['can_manage'] = has_admin_or_permission('head_teacher_remarks', 'manage', 1);
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * Get all remarks (AJAX endpoint for DataTables)
     */
    public function get_all_ajax() {
        $include_inactive = $this->input->get('include_inactive') == 'true';
        $remarks = $this->head_teacher_remarks_model->get_all($include_inactive);
        
        echo json_encode($remarks);
    }

    /**
     * Get single remark by ID (AJAX)
     */
    public function get_by_id($id) {
        $remark = $this->head_teacher_remarks_model->get_by_id($id);
        
        if ($remark) {
            echo json_encode(array('success' => true, 'data' => $remark));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Remark range not found'));
        }
    }

    /**
     * Create new remark range (AJAX)
     */
    public function create() {
        // Check permission
        require_permission('head_teacher_remarks', 'add', true);
        
        // Validate input
        $this->form_validation->set_rules('min_percentage', 'Minimum Percentage', 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('max_percentage', 'Maximum Percentage', 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('remark_text', 'Remark Text', 'required|max_length[255]');
        $this->form_validation->set_rules('display_order', 'Display Order', 'integer');
        $this->form_validation->set_rules('is_active', 'Active Status', 'in_list[0,1]');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(array(
                'success' => false,
                'message' => validation_errors()
            ));
            return;
        }

        // Prepare data
        $data = array(
            'min_percentage' => $this->input->post('min_percentage'),
            'max_percentage' => $this->input->post('max_percentage'),
            'remark_text' => $this->input->post('remark_text'),
            'display_order' => $this->input->post('display_order'),
            'is_active' => $this->input->post('is_active') !== null ? $this->input->post('is_active') : 1
        );

        // Create
        $result = $this->head_teacher_remarks_model->create($data);
        
        echo json_encode($result);
    }

    /**
     * Update existing remark range (AJAX)
     */
    public function update($id) {
        // Check permission
        require_permission('head_teacher_remarks', 'edit', true);
        
        // Validate input
        $this->form_validation->set_rules('min_percentage', 'Minimum Percentage', 'numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('max_percentage', 'Maximum Percentage', 'numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('remark_text', 'Remark Text', 'max_length[255]');
        $this->form_validation->set_rules('display_order', 'Display Order', 'integer');
        $this->form_validation->set_rules('is_active', 'Active Status', 'in_list[0,1]');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(array(
                'success' => false,
                'message' => validation_errors()
            ));
            return;
        }

        // Prepare data (only include fields that are set)
        $data = array();
        
        if ($this->input->post('min_percentage') !== null && $this->input->post('min_percentage') !== '') {
            $data['min_percentage'] = $this->input->post('min_percentage');
        }
        if ($this->input->post('max_percentage') !== null && $this->input->post('max_percentage') !== '') {
            $data['max_percentage'] = $this->input->post('max_percentage');
        }
        if ($this->input->post('remark_text') !== null && $this->input->post('remark_text') !== '') {
            $data['remark_text'] = $this->input->post('remark_text');
        }
        if ($this->input->post('display_order') !== null && $this->input->post('display_order') !== '') {
            $data['display_order'] = $this->input->post('display_order');
        }
        if ($this->input->post('is_active') !== null) {
            $data['is_active'] = $this->input->post('is_active');
        }

        if (empty($data)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'No data to update'
            ));
            return;
        }

        // Update
        $result = $this->head_teacher_remarks_model->update($id, $data);
        
        echo json_encode($result);
    }

    /**
     * Delete remark range (AJAX)
     */
    public function delete($id) {
        // Check permission
        require_permission('head_teacher_remarks', 'delete', true);
        
        $result = $this->head_teacher_remarks_model->delete($id);
        echo json_encode($result);
    }

    /**
     * Toggle active status (AJAX)
     */
    public function toggle_active($id) {
        // Check permission
        require_permission('head_teacher_remarks', 'edit', true);
        
        $result = $this->head_teacher_remarks_model->toggle_active($id);
        echo json_encode($result);
    }

    /**
     * Update display order for multiple items (AJAX)
     * Expects POST data: order_data (array of {id, display_order})
     */
    public function update_order() {
        // Check permission
        require_permission('head_teacher_remarks', 'edit', true);
        
        $order_data = $this->input->post('order_data');
        
        if (!$order_data || !is_array($order_data)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid order data'
            ));
            return;
        }

        $result = $this->head_teacher_remarks_model->update_order($order_data);
        echo json_encode($result);
    }

    /**
     * Check for overlaps (AJAX)
     * Used for real-time validation in UI
     */
    public function check_overlap() {
        $min = $this->input->post('min_percentage');
        $max = $this->input->post('max_percentage');
        $exclude_id = $this->input->post('exclude_id');

        if ($min === null || $max === null) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Missing percentage values'
            ));
            return;
        }

        $has_overlap = $this->head_teacher_remarks_model->check_overlap($min, $max, $exclude_id);
        
        $result = array('has_overlap' => $has_overlap);
        
        if ($has_overlap) {
            $overlapping = $this->head_teacher_remarks_model->get_overlapping_range($min, $max, $exclude_id);
            $result['overlapping_range'] = $overlapping;
        }
        
        echo json_encode($result);
    }

    /**
     * Initialize default remark ranges
     * Should only be called once during initial setup
     */
    public function initialize_defaults() {
        // Check permission (only admins or users with manage permission can initialize)
        if (!has_admin_or_permission('head_teacher_remarks', 'manage', 1)) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_permission_to_perform_this_action'));
            redirect(site_url('head_teacher_remarks'), 'refresh');
        }
        
        $result = $this->head_teacher_remarks_model->initialize_defaults();
        
        if ($result['success']) {
            $this->session->set_flashdata('flash_message', $result['message']);
        } else {
            $this->session->set_flashdata('error_message', $result['message']);
        }
        
        redirect(site_url('head_teacher_remarks'), 'refresh');
    }

    /**
     * Test remark assignment for a given percentage (AJAX)
     * Useful for admins to test their configured ranges
     */
    public function test_percentage() {
        $percentage = $this->input->post('percentage');
        
        if ($percentage === null || $percentage === '') {
            echo json_encode(array(
                'success' => false,
                'message' => 'Percentage is required'
            ));
            return;
        }

        $remark = $this->head_teacher_remarks_model->find_by_percentage($percentage);
        
        if ($remark) {
            echo json_encode(array(
                'success' => true,
                'percentage' => $percentage,
                'remark_id' => $remark->remark_id,
                'remark_text' => $remark->remark_text
            ));
        } else {
            echo json_encode(array(
                'success' => true,
                'percentage' => $percentage,
                'remark_id' => null,
                'remark_text' => 'No matching remark range found (gap in ranges)',
                'is_gap' => true
            ));
        }
    }

    /**
     * Export remarks as JSON (for backup/transfer)
     */
    public function export_json() {
        $remarks = $this->head_teacher_remarks_model->get_all(true);
        
        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="head_teacher_remarks_' . date('Y-m-d') . '.json"')
            ->set_output(json_encode($remarks, JSON_PRETTY_PRINT));
    }

    /**
     * Import remarks from JSON (for restore/transfer)
     */
    public function import_json() {
        if (!isset($_FILES['json_file'])) {
            echo json_encode(array(
                'success' => false,
                'message' => 'No file uploaded'
            ));
            return;
        }

        $file_content = file_get_contents($_FILES['json_file']['tmp_name']);
        $remarks = json_decode($file_content, true);

        if (!$remarks || !is_array($remarks)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid JSON format'
            ));
            return;
        }

        $imported = 0;
        $errors = array();

        foreach ($remarks as $remark) {
            $data = array(
                'min_percentage' => $remark['min_percentage'] ?? 0,
                'max_percentage' => $remark['max_percentage'] ?? 0,
                'remark_text' => $remark['remark_text'] ?? '',
                'display_order' => $remark['display_order'] ?? 0,
                'is_active' => isset($remark['is_active']) ? $remark['is_active'] : 1
            );

            $result = $this->head_teacher_remarks_model->create($data);
            
            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = $result['message'];
            }
        }

        echo json_encode(array(
            'success' => true,
            'imported' => $imported,
            'total' => count($remarks),
            'errors' => $errors
        ));
    }

    /**
     * Get Form - Load modal form for add/edit
     * 
     * Returns the modal form HTML for creating or editing a head teacher remark range.
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
            $remark = $this->head_teacher_remarks_model->get_by_id($id);
            if (!$remark) {
                echo '<p style="color:red;text-align:center;padding:20px;">Remark range not found</p>';
                return;
            }
            $data['remark'] = $remark;
        }
        
        $this->load->view('backend/admin/modal_head_teacher_remark_form', $data);
    }
}
