<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Teacher Remarks Templates Controller
 * 
 * Manages CRUD operations for configurable teacher remark templates.
 * Admin-only access for managing templates used in dropdown selection.
 * 
 * @package    School Management System
 * @subpackage Controllers
 * @category   Academic Configuration
 * @author     School Management System
 * @version    1.0
 */
class Teacher_remarks_templates extends CI_Controller {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
        
        $this->load->model('teacher_remarks_templates_model');
        $this->load->library('form_validation');
        $this->load->helper('permission');
    }

    /**
     * Main index page - displays list of all templates
     */
    public function index() {
        // Check permission or admin access
        if (!has_admin_or_permission('teacher_remarks_templates', 'view', 1)) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_permission_to_access_this_module'));
            redirect(site_url($this->session->userdata('login_type') . '/dashboard'), 'refresh');
        }
        
        $page_data['page_name'] = 'teacher_remarks_templates';
        $page_data['page_title'] = get_phrase('teacher_remarks_templates');
        $page_data['templates'] = $this->teacher_remarks_templates_model->get_all(true);
        $page_data['templates_by_category'] = $this->teacher_remarks_templates_model->get_by_category(true);
        
        // Pass user permissions to view
        $page_data['can_add'] = has_admin_or_permission('teacher_remarks_templates', 'add', 1);
        $page_data['can_edit'] = has_admin_or_permission('teacher_remarks_templates', 'edit', 1);
        $page_data['can_delete'] = has_admin_or_permission('teacher_remarks_templates', 'delete', 1);
        $page_data['can_manage'] = has_admin_or_permission('teacher_remarks_templates', 'manage', 1);
        
        $this->load->view('backend/index', $page_data);
    }

    /**
     * Get all templates (AJAX endpoint for DataTables)
     */
    public function get_all_ajax() {
        $include_inactive = $this->input->get('include_inactive') == 'true';
        $category = $this->input->get('category');
        
        $templates = $this->teacher_remarks_templates_model->get_all($include_inactive, $category);
        
        echo json_encode($templates);
    }

    /**
     * Get templates grouped by category (AJAX)
     */
    public function get_by_category_ajax() {
        $active_only = $this->input->get('active_only') != 'false';
        $templates = $this->teacher_remarks_templates_model->get_by_category($active_only);
        
        echo json_encode($templates);
    }

    /**
     * Get single template by ID (AJAX)
     */
    public function get_by_id($id) {
        $template = $this->teacher_remarks_templates_model->get_by_id($id);
        
        if ($template) {
            echo json_encode(array('success' => true, 'data' => $template));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Template not found'));
        }
    }

    /**
     * Get form for add/edit modal (AJAX)
     */
    public function get_form() {
        $id = $this->input->post('id');
        $data = array();
        
        if ($id) {
            $data['template'] = $this->teacher_remarks_templates_model->get_by_id($id);
        }
        
        $this->load->view('backend/admin/modal_teacher_remark_template_form', $data);
    }

    /**
     * Get active templates for Select2 dropdown (AJAX)
     * Returns format suitable for Select2: [{id, text}]
     */
    public function get_for_select2() {
        $category = $this->input->get('category');
        $templates = $this->teacher_remarks_templates_model->get_active($category);
        
        $select2_data = array();
        foreach ($templates as $template) {
            $select2_data[] = array(
                'id' => $template->id,
                'text' => $template->remark_text,
                'category' => $template->category
            );
        }
        
        echo json_encode($select2_data);
    }

    /**
     * Create new template (AJAX)
     */
    public function create() {
        // Check permission
        require_permission('teacher_remarks_templates', 'add', true);
        
        // Validate input
        $this->form_validation->set_rules('remark_text', 'Remark Text', 'required');
        $this->form_validation->set_rules('category', 'Category', 'max_length[50]');
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
            'remark_text' => $this->input->post('remark_text'),
            'category' => $this->input->post('category') ?: null,
            'display_order' => $this->input->post('display_order'),
            'is_active' => $this->input->post('is_active') !== null ? $this->input->post('is_active') : 1
        );

        // Create
        $result = $this->teacher_remarks_templates_model->create($data);
        
        echo json_encode($result);
    }

    /**
     * Update existing template (AJAX)
     */
    public function update($id) {
        // Check permission
        require_permission('teacher_remarks_templates', 'edit', true);
        
        // Validate input
        $this->form_validation->set_rules('remark_text', 'Remark Text', 'trim');
        $this->form_validation->set_rules('category', 'Category', 'max_length[50]');
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
        
        if ($this->input->post('remark_text') !== null && $this->input->post('remark_text') !== '') {
            $data['remark_text'] = $this->input->post('remark_text');
        }
        if ($this->input->post('category') !== null) {
            $data['category'] = $this->input->post('category') ?: null;
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
        $result = $this->teacher_remarks_templates_model->update($id, $data);
        
        echo json_encode($result);
    }

    /**
     * Delete template (AJAX)
     */
    public function delete($id) {
        // Check permission
        require_permission('teacher_remarks_templates', 'delete', true);
        
        $result = $this->teacher_remarks_templates_model->delete($id);
        echo json_encode($result);
    }

    /**
     * Toggle active status (AJAX)
     */
    public function toggle_active($id) {
        // Check permission
        require_permission('teacher_remarks_templates', 'edit', true);
        
        $result = $this->teacher_remarks_templates_model->toggle_active($id);
        echo json_encode($result);
    }

    /**
     * Update display order for multiple items (AJAX)
     * Expects POST data: order_data (array of {id, display_order})
     */
    public function update_order() {
        // Check permission
        require_permission('teacher_remarks_templates', 'edit', true);
        
        $order_data = $this->input->post('order_data');
        
        if (!$order_data || !is_array($order_data)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid order data'
            ));
            return;
        }

        $result = $this->teacher_remarks_templates_model->update_order($order_data);
        echo json_encode($result);
    }

    /**
     * Initialize default templates
     * Should only be called once during initial setup
     */
    public function initialize_defaults() {
        // Check permission (only admins or users with manage permission can initialize)
        if (!has_admin_or_permission('teacher_remarks_templates', 'manage', 1)) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_permission_to_perform_this_action'));
            redirect(site_url('teacher_remarks_templates'), 'refresh');
        }
        
        $result = $this->teacher_remarks_templates_model->initialize_defaults();
        
        if ($result['success']) {
            $this->session->set_flashdata('flash_message', $result['message']);
        } else {
            $this->session->set_flashdata('error_message', $result['message']);
        }
        
        redirect(site_url('teacher_remarks_templates'), 'refresh');
    }

    /**
     * Bulk create templates (AJAX)
     * Accepts array of template data
     */
    public function bulk_create() {
        // Check permission
        require_permission('teacher_remarks_templates', 'add', true);
        
        $templates_data = $this->input->post('templates');
        
        if (!$templates_data || !is_array($templates_data)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid templates data'
            ));
            return;
        }

        $created = 0;
        $errors = array();

        foreach ($templates_data as $template_data) {
            $result = $this->teacher_remarks_templates_model->create($template_data);
            
            if ($result['success']) {
                $created++;
            } else {
                $errors[] = $result['message'];
            }
        }

        echo json_encode(array(
            'success' => true,
            'created' => $created,
            'total' => count($templates_data),
            'errors' => $errors
        ));
    }

    /**
     * Export templates as JSON (for backup/transfer)
     */
    public function export_json() {
        $templates = $this->teacher_remarks_templates_model->get_all(true);
        
        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="teacher_remarks_templates_' . date('Y-m-d') . '.json"')
            ->set_output(json_encode($templates, JSON_PRETTY_PRINT));
    }

    /**
     * Import templates from JSON (for restore/transfer)
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
        $templates = json_decode($file_content, true);

        if (!$templates || !is_array($templates)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid JSON format'
            ));
            return;
        }

        $imported = 0;
        $errors = array();

        foreach ($templates as $template) {
            $data = array(
                'remark_text' => $template['remark_text'] ?? '',
                'category' => $template['category'] ?? null,
                'display_order' => $template['display_order'] ?? 0,
                'is_active' => isset($template['is_active']) ? $template['is_active'] : 1
            );

            $result = $this->teacher_remarks_templates_model->create($data);
            
            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = $result['message'];
            }
        }

        echo json_encode(array(
            'success' => true,
            'imported' => $imported,
            'total' => count($templates),
            'errors' => $errors
        ));
    }

    /**
     * Get category list (AJAX)
     * Returns distinct categories from existing templates
     */
    public function get_categories() {
        $this->db->select('category');
        $this->db->distinct();
        $this->db->from('teacher_remarks_templates');
        $this->db->where('category IS NOT NULL');
        $this->db->where('category !=', '');
        $categories = $this->db->get()->result_array();
        
        $category_list = array_column($categories, 'category');
        
        echo json_encode($category_list);
    }
}
