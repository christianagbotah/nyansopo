<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Inventory_model');
        $this->load->library('session');
        
        // Check if user is logged in
        if($this->session->userdata('admin_login') != 1)
            redirect(site_url('login'), 'refresh');
        
        // Get admin level
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', array('admin_id' => $admin_id))->row();
        
        // Only Super Admin (1), Admin (2), Accountant (3), and Shop Attendant (6) can access
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            show_error('Access Denied: You do not have permission to access the inventory module.');
        }
    }
    
    /**
     * Helper function: Convert dd/mm/yyyy to yyyy-mm-dd for database
     * @param string $date Date in dd/mm/yyyy format
     * @return string|null Date in yyyy-mm-dd format or null if invalid
     */
    private function convert_date_to_mysql($date) {
        if (empty($date)) {
            return null;
        }
        
        // If already in yyyy-mm-dd format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Convert dd/mm/yyyy to yyyy-mm-dd
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        
        return null;
    }
    
    /**
     * Helper function: Convert yyyy-mm-dd to dd/mm/yyyy for display
     * @param string $date Date in yyyy-mm-dd format
     * @return string|null Date in dd/mm/yyyy format or null if invalid
     */
    private function convert_date_to_display($date) {
        if (empty($date)) {
            return null;
        }
        
        // Convert yyyy-mm-dd to dd/mm/yyyy
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches)) {
            return $matches[3] . '/' . $matches[2] . '/' . $matches[1];
        }
        
        return $date;
    }
    
    // Dashboard
    public function index() {
        $page_data['page_name'] = 'inventory/dashboard';
        $page_data['page_title'] = get_phrase('inventory_dashboard');
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    // Products
    public function products() {
        $page_data['page_name'] = 'inventory/products';
        $page_data['page_title'] = get_phrase('products');
        $page_data['products'] = $this->Inventory_model->get_products();
        $page_data['categories'] = $this->Inventory_model->get_categories();
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    // Categories
    public function categories() {
        $page_data['page_name'] = 'inventory/categories';
        $page_data['page_title'] = get_phrase('categories');
        $page_data['categories'] = $this->Inventory_model->get_categories();
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    // Sales
    public function sales() {
        $page_data['page_name'] = 'inventory/sales';
        $page_data['page_title'] = get_phrase('sales');
        $page_data['sales'] = $this->Inventory_model->get_sales();
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    // POS
    public function pos() {
        $page_data['page_name'] = 'inventory/pos';
        $page_data['page_title'] = get_phrase('point_of_sale');
        $page_data['products'] = $this->Inventory_model->get_active_products();
        $page_data['students'] = $this->Inventory_model->get_students();
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    // AJAX: Get dashboard stats
    public function get_stats() {
        $stats = $this->Inventory_model->get_dashboard_stats();
        echo json_encode($stats);
    }
    
    // AJAX: Create product
    public function create_product() {
        $data = [
            'name' => $this->input->post('name'),
            'sku' => $this->input->post('sku'),
            'category_id' => $this->input->post('category_id'),
            'description' => $this->input->post('description'),
            'cost_price' => $this->input->post('cost_price'),
            'selling_price' => $this->input->post('selling_price'),
            'quantity' => $this->input->post('quantity'),
            'reorder_level' => $this->input->post('reorder_level'),
            'status' => 1
        ];
        
        if($this->Inventory_model->create_product($data)) {
            echo json_encode(['status' => 'success', 'message' => get_phrase('product_created_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }
    
    // AJAX: Update product
    public function update_product() {
        $id = $this->input->post('id');
        $data = [
            'name' => $this->input->post('name'),
            'sku' => $this->input->post('sku'),
            'category_id' => $this->input->post('category_id'),
            'description' => $this->input->post('description'),
            'cost_price' => $this->input->post('cost_price'),
            'selling_price' => $this->input->post('selling_price'),
            'quantity' => $this->input->post('quantity'),
            'reorder_level' => $this->input->post('reorder_level')
        ];
        
        if($this->Inventory_model->update_product($id, $data)) {
            echo json_encode(['status' => 'success', 'message' => get_phrase('product_updated_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }
    
    // AJAX: Delete product
    public function delete_product($id) {
        try {
            if (!$id || !is_numeric($id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid product ID'
                ]);
                return;
            }
            
            $result = $this->Inventory_model->delete_product($id);
            echo json_encode($result);
        } catch (Exception $e) {
            log_message('error', 'Delete product error: ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred while deleting the product: ' . $e->getMessage()
            ]);
        }
    }
    
    // AJAX: Toggle product status (activate/deactivate)
    public function toggle_product_status($id) {
        try {
            if (!$id || !is_numeric($id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid product ID'
                ]);
                return;
            }
            
            // Get current status
            $product = $this->db->get_where('inventory_products', ['id' => $id])->row();
            
            if (!$product) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
                return;
            }
            
            // Toggle status
            $new_status = $product->status == 1 ? 0 : 1;
            $this->db->where('id', $id);
            $this->db->update('inventory_products', ['status' => $new_status]);
            
            echo json_encode([
                'status' => 'success',
                'message' => $new_status == 1 ? 'Product activated successfully' : 'Product deactivated successfully',
                'new_status' => $new_status
            ]);
        } catch (Exception $e) {
            log_message('error', 'Toggle product status error: ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
    
    // AJAX: Create category
    public function create_category() {
        $data = [
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description')
        ];
        
        if($this->Inventory_model->create_category($data)) {
            echo json_encode(['status' => 'success', 'message' => get_phrase('category_created_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('operation_failed')]);
        }
    }
    
    // Category form
    public function category_form($id = null) {
        $category = null;
        if($id) {
            $category = $this->db->get_where('inventory_categories', ['id' => $id])->row_array();
        }
        
        $html = form_open($id ? 'inventory/update_category' : 'inventory/create_category', ['id' => 'category_form']);
        if($id) $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<div class="form-group">';
        $html .= '<label>Category Name *</label>';
        $html .= '<input type="text" name="name" value="' . ($category['name'] ?? '') . '" required class="form-control">';
        $html .= '</div>';
        $html .= '<div class="form-group">';
        $html .= '<label>Description</label>';
        $html .= '<textarea name="description" rows="2" class="form-control">' . ($category['description'] ?? '') . '</textarea>';
        $html .= '</div>';
        $html .= '<div class="text-right mt-3">';
        $html .= '<button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button> ';
        $html .= '<button type="submit" class="btn btn-primary">' . ($id ? 'Update' : 'Create') . '</button>';
        $html .= '</div>';
        $html .= form_close();
        $html .= '<script>';
        $html .= '$("#category_form").submit(function(e) {';
        $html .= 'e.preventDefault();';
        $html .= '$("#category_modal").modal("hide");';
        $html .= 'showAjaxModal_alert("Saving...", "loading");';
        $html .= '$.ajax({url: $(this).attr("action"), type: "POST", data: new FormData(this), cache: false, contentType: false, processData: false, dataType: "json"}).done(function(response) {';
        $html .= 'if(response.status === "success") { showAjaxModal_alert(response.message, "success", false); setTimeout(() => loadCategories(), 2000); }';
        $html .= 'else { showAjaxModal_alert(response.message, "error"); }';
        $html .= '}).fail(function() { showAjaxModal_alert("An error occurred", "error"); });';
        $html .= '});';
        $html .= '</script>';
        
        echo $html;
    }
    
    // Update category
    public function update_category() {
        $id = $this->input->post('id');
        $data = [
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description')
        ];
        
        if($this->db->update('inventory_categories', $data, ['id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Category updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Operation failed']);
        }
    }
    
    // Delete category
    public function delete_category($id) {
        $check = $this->db->get_where('inventory_products', ['category_id' => $id])->num_rows();
        if($check > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete category with products']);
        } else {
            if($this->db->delete('inventory_categories', ['id' => $id])) {
                echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Operation failed']);
            }
        }
    }
    
    // AJAX: Process sale
    public function process_sale() {
        $customer_ids = json_decode($this->input->post('customer_ids'), true);
        $items = json_decode($this->input->post('items'), true);
        $selection_type = $this->input->post('selection_type');
        $walkin_customer_name = $this->input->post('walkin_customer_name'); // Get custom walk-in name
        
        $this->db->trans_start();
        
        // Generate unique receipt code for this batch
        $receipt_code = 'RCP' . date('Ymd') . substr(uniqid(), -6);
        $last_sale_id = null;
        
        foreach($customer_ids as $customer_id) {
            // Determine if this is a walk-in customer
            $is_walkin = (empty($customer_id) || $customer_id === 'walk-in' || $customer_id === '');
            
            $sale_data = [
                'student_id' => $is_walkin ? NULL : $customer_id,
                'customer_name' => $is_walkin && !empty($walkin_customer_name) ? trim($walkin_customer_name) : NULL,
                'total_amount' => array_sum(array_map(function($item) { return $item['price'] * $item['quantity']; }, $items)),
                'payment_method' => 1,
                'served_by' => $this->session->userdata('admin_id'),
                'sale_date' => date('Y-m-d H:i:s'),
                'receipt_code' => $receipt_code
            ];
            
            $this->db->insert('inventory_sales', $sale_data);
            $sale_id = $this->db->insert_id();
            $last_sale_id = $sale_id;
            
            foreach($items as $item) {
                $this->db->insert('inventory_sale_items', [
                    'sale_id' => $sale_id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity']
                ]);
            }
        }
        
        foreach($items as $item) {
            $this->db->set('quantity', 'quantity - ' . ($item['quantity'] * count($customer_ids)), FALSE)
                ->where('id', $item['id'])
                ->update('inventory_products');
        }
        
        $this->db->trans_complete();
        
        if($this->db->trans_status()) {
            echo json_encode([
                'status' => 'success', 
                'message' => count($customer_ids) . ' sale(s) completed successfully',
                'sale_id' => $last_sale_id,
                'receipt_code' => $receipt_code
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Operation failed']);
        }
    }
    
    // Print Sale Receipt
    public function sale_receipt($receipt_code) {
        if(empty($receipt_code)) {
            show_error('Invalid receipt code', 400);
            return;
        }
        
        // Get running year for enroll join
        $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        
        // Get all sales with this receipt code
        // Include class and section info for students
        $this->db->select('is.*, COALESCE(is.customer_name, s.name) as customer_name, s.student_code, c.name as class_name, c.name_numeric, sec.name as section_name');
        $this->db->from('inventory_sales is');
        $this->db->join('student s', 's.student_id = is.student_id', 'left');
        $this->db->join('enroll e', 'e.student_id = is.student_id AND e.year = "' . $running_year . '"', 'left');
        $this->db->join('class c', 'c.class_id = e.class_id', 'left');
        $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
        $this->db->where('is.receipt_code', $receipt_code);
        $this->db->order_by('is.id', 'ASC');
        $sales = $this->db->get()->result();
        
        if(empty($sales)) {
            show_error('Receipt not found', 404);
            return;
        }
        
        // Get sale items for the first sale (they're all the same for a batch)
        $first_sale_id = $sales[0]->id;
        $this->db->select('isi.*, ip.name as product_name, ip.sku');
        $this->db->from('inventory_sale_items isi');
        $this->db->join('inventory_products ip', 'ip.id = isi.product_id');
        $this->db->where('isi.sale_id', $first_sale_id);
        $items = $this->db->get()->result();
        
        // Get served by (admin/staff name)
        $served_by = 'N/A';
        if($sales[0]->served_by) {
            $admin = $this->db->get_where('admin', ['admin_id' => $sales[0]->served_by])->row();
            $served_by = $admin ? $admin->name : 'N/A';
        }
        
        // Get school settings
        $school_name = $this->db->get_where('settings', ['type' => 'system_name'])->row()->description;
        $school_address = $this->db->get_where('settings', ['type' => 'address'])->row()->description;
        $school_phone = $this->db->get_where('settings', ['type' => 'phone'])->row()->description;
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        $page_data = [
            'sales' => $sales,
            'items' => $items,
            'receipt_code' => $receipt_code,
            'served_by' => $served_by,
            'school_name' => $school_name,
            'school_address' => $school_address,
            'school_phone' => $school_phone,
            'currency' => $currency
        ];
        
        $this->load->view('backend/admin/inventory/sale_receipt', $page_data);
    }
    
    // Stock Movements
    public function stock_movements() {
        $page_data['page_name'] = 'inventory/stock_movements';
        $page_data['page_title'] = 'Stock Movements';
        $this->load->view('backend/main', $page_data);
    }
    
    // Suppliers
    public function suppliers() {
        $page_data['page_name'] = 'inventory/suppliers';
        $page_data['page_title'] = 'Suppliers';
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Supplier form modal
     * Returns HTML form for creating/editing suppliers
     */
    public function supplier_form($id = null) {
        $supplier = null;
        if($id) {
            $supplier = $this->db->get_where('inventory_suppliers', ['id' => $id])->row_array();
        }
        
        // Add custom CSS to reduce modal width
        $html = '<style>';
        $html .= '#createModal .modal-dialog { max-width: 650px !important; }';
        $html .= '</style>';
        
        $html .= '<div class="p-4">';
        $html .= form_open($id ? 'inventory/update_supplier' : 'inventory/create_supplier', ['id' => 'supplier_form']);
        
        if($id) {
            $html .= '<input type="hidden" name="supplier_id" value="' . $id . '">';
        }
        
        // Grid layout for Supplier Name and Contact Person
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">';
        
        // Supplier Name
        $html .= '<div>';
        $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Supplier Name <span class="text-red-500">*</span></label>';
        $html .= '<input type="text" name="name" value="' . ($supplier['name'] ?? '') . '" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3rem !important;" placeholder="Enter supplier name">';
        $html .= '</div>';
        
        // Contact Person
        $html .= '<div>';
        $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Contact Person</label>';
        $html .= '<input type="text" name="contact_person" value="' . ($supplier['contact_person'] ?? '') . '" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3rem !important;" placeholder="Enter contact person">';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Grid layout for Phone and Email
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">';
        
        // Phone
        $html .= '<div>';
        $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Phone</label>';
        $html .= '<input type="text" name="phone" value="' . ($supplier['phone'] ?? '') . '" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3rem !important;" placeholder="Enter phone">';
        $html .= '</div>';
        
        // Email
        $html .= '<div>';
        $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Email</label>';
        $html .= '<input type="email" name="email" value="' . ($supplier['email'] ?? '') . '" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3rem !important;" placeholder="Enter email">';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Address (full width)
        $html .= '<div class="mb-4">';
        $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Address</label>';
        $html .= '<textarea name="address" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important;" placeholder="Enter address">' . ($supplier['address'] ?? '') . '</textarea>';
        $html .= '</div>';
        
        // Buttons
        $html .= '<div class="flex justify-end gap-3 mt-4 pt-3 border-t border-gray-200">';
        $html .= '<button type="button" onclick="$(\'#createModal\').modal(\'hide\')" class="px-5 py-2 font-semibold rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors" style="font-size: 1.125rem !important;">Cancel</button>';
        $html .= '<button type="submit" class="px-5 py-2 font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors" style="font-size: 1.125rem !important;"><i class="fa fa-save mr-2"></i> ' . ($id ? 'Update' : 'Create') . '</button>';
        $html .= '</div>';
        
        $html .= form_close();
        $html .= '</div>';
        
        // JavaScript
        $html .= '<script>';
        $html .= '$("#supplier_form").submit(function(e) {';
        $html .= 'e.preventDefault();';
        $html .= '$("#createModal").modal("hide");';
        $html .= 'showAjaxModal_alert("Saving supplier...", "loading");';
        $html .= '$.ajax({';
        $html .= 'url: $(this).attr("action"),';
        $html .= 'type: "POST",';
        $html .= 'data: new FormData(this),';
        $html .= 'cache: false,';
        $html .= 'contentType: false,';
        $html .= 'processData: false,';
        $html .= 'dataType: "json"';
        $html .= '}).done(function(response) {';
        $html .= 'if(response.status === "success") {';
        $html .= 'showAjaxModal_alert(response.message, "success", false);';
        $html .= 'setTimeout(() => loadSuppliers(), 2000);';
        $html .= '} else {';
        $html .= 'showAjaxModal_alert(response.message, "error");';
        $html .= '}';
        $html .= '}).fail(function() {';
        $html .= 'showAjaxModal_alert("An error occurred", "error");';
        $html .= '});';
        $html .= '});';
        $html .= '</script>';
        
        echo $html;
    }
    
    /**
     * Supplier details modal
     * Returns HTML for viewing supplier details
     */
    public function supplier_details($id) {
        $supplier = $this->db->get_where('inventory_suppliers', ['id' => $id])->row_array();
        
        if(!$supplier) {
            echo '<div class="p-4 text-center text-red-600">Supplier not found</div>';
            return;
        }
        
        // Get product count
        $product_count = $this->db->where('supplier_id', $id)->count_all_results('inventory_products');
        
        $html = '<div class="p-4">';
        
        // Supplier Name
        $html .= '<div class="mb-4 pb-3 border-b border-gray-200">';
        $html .= '<h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900">' . $supplier['name'] . '</h3>';
        $html .= '<span class="inline-flex px-3 py-1 mt-2 text-sm font-semibold rounded-full ' . ($supplier['status'] == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . '">' . ($supplier['status'] == 1 ? 'Active' : 'Inactive') . '</span>';
        $html .= '</div>';
        
        // Details Grid
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">';
        
        // Contact Person
        $html .= '<div>';
        $html .= '<label style="font-size: 1rem !important;" class="block font-semibold text-gray-600 mb-1">Contact Person</label>';
        $html .= '<p style="font-size: 1.125rem !important;" class="text-gray-900">' . ($supplier['contact_person'] ?: '-') . '</p>';
        $html .= '</div>';
        
        // Phone
        $html .= '<div>';
        $html .= '<label style="font-size: 1rem !important;" class="block font-semibold text-gray-600 mb-1">Phone</label>';
        $html .= '<p style="font-size: 1.125rem !important;" class="text-gray-900">' . ($supplier['phone'] ?: '-') . '</p>';
        $html .= '</div>';
        
        // Email
        $html .= '<div>';
        $html .= '<label style="font-size: 1rem !important;" class="block font-semibold text-gray-600 mb-1">Email</label>';
        $html .= '<p style="font-size: 1.125rem !important;" class="text-gray-900">' . ($supplier['email'] ?: '-') . '</p>';
        $html .= '</div>';
        
        // Products
        $html .= '<div>';
        $html .= '<label style="font-size: 1rem !important;" class="block font-semibold text-gray-600 mb-1">Products</label>';
        $html .= '<p style="font-size: 1.125rem !important;" class="text-gray-900 font-semibold">' . $product_count . '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Address
        if($supplier['address']) {
            $html .= '<div class="mb-4">';
            $html .= '<label style="font-size: 1rem !important;" class="block font-semibold text-gray-600 mb-1">Address</label>';
            $html .= '<p style="font-size: 1.125rem !important;" class="text-gray-900">' . nl2br($supplier['address']) . '</p>';
            $html .= '</div>';
        }
        
        // Action Buttons
        $html .= '<div class="flex justify-end gap-3 mt-4 pt-3 border-t border-gray-200">';
        $html .= '<button type="button" onclick="$(\'#detailsModal\').modal(\'hide\')" class="px-5 py-2 font-semibold rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300 transition-colors" style="font-size: 1rem !important;">Close</button>';
        $html .= '<button type="button" onclick="$(\'#detailsModal\').modal(\'hide\'); editSupplier(' . $id . ');" class="px-5 py-2 font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors" style="font-size: 1rem !important;"><i class="fa fa-edit mr-2"></i>Edit</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        echo $html;
    }
    
    // Reports
    public function reports() {
        $page_data['page_name'] = 'inventory/reports';
        $page_data['page_title'] = 'Inventory Reports';
        $this->load->view('backend/main', $page_data);
    }
    
    // AJAX: Get products with pagination
    public function get_products_ajax() {
        $search = $this->input->get('search');
        $category = $this->input->get('category');
        $status = $this->input->get('status');
        $page = $this->input->get('page') ?: 1;
        $limit = $this->input->get('limit') ?: 20;
        
        $result = $this->Inventory_model->get_products_paginated($search, $category, $status, $page, $limit);
        echo json_encode($result);
    }
    
    // AJAX: Search products
    public function search_products() {
        $query = $this->input->get('q');
        
        $this->db->select('*');
        $this->db->from('inventory_products');
        $this->db->where('status', 1);
        $this->db->group_start();
        $this->db->like('name', $query);
        $this->db->or_like('sku', $query);
        $this->db->group_end();
        $this->db->limit(20);
        
        echo json_encode($this->db->get()->result_array());
    }
    
    // AJAX: Get sales
    public function get_sales() {
        $start = $this->input->get('start_date');
        $end = $this->input->get('end_date');
        $customer = $this->input->get('customer');
        
        // Convert dates from dd/mm/yyyy to yyyy-mm-dd for MySQL
        if($start) $start = $this->convert_date_to_mysql($start);
        if($end) $end = $this->convert_date_to_mysql($end);
        
        // Select customer_name from sales table first (for walk-ins with custom names), fallback to student name
        $this->db->select('s.*, 
            CASE 
                WHEN s.customer_name IS NOT NULL AND s.customer_name != "" THEN s.customer_name 
                WHEN st.name IS NOT NULL THEN st.name 
                ELSE "Walk-in Customer" 
            END as customer_name, 
            COUNT(si.id) as item_count', FALSE);
        $this->db->from('inventory_sales s');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->join('inventory_sale_items si', 'si.sale_id = s.id', 'left');
        
        if($start) $this->db->where('DATE(s.sale_date) >=', $start);
        if($end) $this->db->where('DATE(s.sale_date) <=', $end);
        
        // Handle customer filter
        if($customer !== null && $customer !== '') {
            if($customer == '0') {
                // All walk-in customers (student_id is NULL or 0)
                $this->db->group_start();
                $this->db->where('s.student_id', NULL);
                $this->db->or_where('s.student_id', 0);
                $this->db->group_end();
            } elseif(strpos($customer, 'walkin_') === 0) {
                // Specific walk-in customer by custom name
                $custom_name = substr($customer, 7); // Remove 'walkin_' prefix
                $this->db->where('s.customer_name', $custom_name);
                $this->db->group_start();
                $this->db->where('s.student_id', NULL);
                $this->db->or_where('s.student_id', 0);
                $this->db->group_end();
            } else {
                // Specific student
                $this->db->where('s.student_id', $customer);
            }
        }
        
        $this->db->group_by('s.id');
        $this->db->order_by('s.sale_date', 'DESC');
        $sales = $this->db->get()->result_array();
        
        // Calculate summary
        $total = array_sum(array_column($sales, 'total_amount'));
        $count = count($sales);
        $avg = $count > 0 ? $total / $count : 0;
        
        // Calculate total unique products sold
        $this->db->select('COUNT(DISTINCT si.product_id) as items_sold');
        $this->db->from('inventory_sale_items si');
        $this->db->join('inventory_sales s', 's.id = si.sale_id');
        if($start) $this->db->where('DATE(s.sale_date) >=', $start);
        if($end) $this->db->where('DATE(s.sale_date) <=', $end);
        
        if($customer !== null && $customer !== '') {
            if($customer == '0') {
                $this->db->group_start();
                $this->db->where('s.student_id', NULL);
                $this->db->or_where('s.student_id', 0);
                $this->db->group_end();
            } elseif(strpos($customer, 'walkin_') === 0) {
                $custom_name = substr($customer, 7);
                $this->db->where('s.customer_name', $custom_name);
                $this->db->group_start();
                $this->db->where('s.student_id', NULL);
                $this->db->or_where('s.student_id', 0);
                $this->db->group_end();
            } else {
                $this->db->where('s.student_id', $customer);
            }
        }
        $items_sold = $this->db->get()->row()->items_sold ?: 0;
        
        echo json_encode([
            'sales' => $sales,
            'summary' => [
                'total' => $total, 
                'count' => $count, 
                'average' => $avg, 
                'items_sold' => $items_sold
            ]
        ]);
    }
    
    // AJAX: Get suppliers
    public function get_suppliers() {
        echo json_encode($this->Inventory_model->get_suppliers());
    }
    
    // AJAX: Get categories
    public function get_categories() {
        $this->db->select('c.*, COUNT(p.id) as product_count');
        $this->db->from('inventory_categories c');
        $this->db->join('inventory_products p', 'p.category_id = c.id', 'left');
        $this->db->group_by('c.id');
        
        echo json_encode($this->db->get()->result_array());
    }
    
    // AJAX: Get movements
    public function get_movements() {
        $product_id = $this->input->get('product_id');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        
        echo json_encode($this->Inventory_model->get_stock_movements($product_id, $start_date, $end_date));
    }
    
    // AJAX: Get low stock
    public function get_low_stock() {
        echo json_encode($this->Inventory_model->get_low_stock_items());
    }
    
    // AJAX: Stock in
    public function stock_in() {
        $data = [
            'product_id' => $this->input->post('product_id'),
            'quantity' => $this->input->post('quantity'),
            'supplier_id' => $this->input->post('supplier_id'),
            'cost_price' => $this->input->post('cost_price'),
            'notes' => $this->input->post('notes')
        ];
        
        if($this->Inventory_model->stock_in($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Stock received successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Operation failed']);
        }
    }
    
    // AJAX: Stock out
    public function stock_out() {
        $data = [
            'product_id' => $this->input->post('product_id'),
            'quantity' => $this->input->post('quantity'),
            'issued_to' => $this->input->post('issued_to'),
            'purpose' => $this->input->post('purpose'),
            'notes' => $this->input->post('notes')
        ];
        
        if($this->Inventory_model->stock_out($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Stock issued successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient stock']);
        }
    }
    
    // AJAX: Delete supplier
    public function delete_supplier($id) {
        if($this->Inventory_model->delete_supplier($id)) {
            echo json_encode(['status' => 'success', 'message' => 'Supplier deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete']);
        }
    }
    
    // AJAX: Generate report
    public function generate_report($type) {
        $data = $this->Inventory_model->get_inventory_report();
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        $html = '<table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">';
        
        foreach($data as $item) {
            $value = $item['quantity'] * $item['cost_price'];
            $html .= '<tr>
                <td class="px-6 py-4 text-sm">'.$item['name'].'</td>
                <td class="px-6 py-4 text-sm">'.($item['category_name'] ?: '-').'</td>
                <td class="px-6 py-4 text-sm text-right">'.$item['quantity'].'</td>
                <td class="px-6 py-4 text-sm text-right"><sup style="font-size: 0.7em;">'.$currency.'</sup> '.number_format($value, 2).'</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        echo json_encode([
            'status' => 'success',
            'title' => 'Stock Valuation Report',
            'html' => $html
        ]);
    }
    
    // AJAX: Get students by class (multiple classes)
    public function get_students_by_class() {
        $classes = $this->input->post('classes');
        
        if(empty($classes)) {
            echo json_encode(['student_ids' => []]);
            return;
        }
        
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $student_ids = [];
        
        foreach($classes as $class_section) {
            list($class_id, $section_id) = explode('-', $class_section);
            
            $this->db->select('student_id');
            $this->db->from('enroll');
            $this->db->where('class_id', $class_id);
            $this->db->where('section_id', $section_id);
            $this->db->where('year', $year);
            $this->db->where('term', $term);
            $this->db->where('mute', '0');
            
            $results = $this->db->get()->result_array();
            foreach($results as $row) {
                $student_ids[] = $row['student_id'];
            }
        }
        
        echo json_encode(['student_ids' => array_unique($student_ids)]);
    }
    
    // AJAX: Get students by residential type
    public function get_students_by_residential() {
        $types = $this->input->post('types');
        
        if(empty($types)) {
            echo json_encode(['student_ids' => []]);
            return;
        }
        
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $this->db->select('e.student_id');
        $this->db->from('enroll e');
        $this->db->where('e.year', $year);
        $this->db->where('e.term', $term);
        $this->db->where('e.mute', '0');
        $this->db->where_in('e.dormitory_id', $types);
        
        $results = $this->db->get()->result_array();
        $student_ids = array_column($results, 'student_id');
        
        echo json_encode(['student_ids' => $student_ids]);
    }
    
    // Export products to CSV
    public function export_products() {
        $search = $this->input->get('search');
        $category = $this->input->get('category');
        $status = $this->input->get('status');
        
        $result = $this->Inventory_model->get_products_paginated($search, $category, $status, 1, 10000);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product Name', 'SKU', 'Category', 'Stock', 'Cost Price', 'Selling Price', 'Status']);
        
        foreach($result['data'] as $product) {
            fputcsv($output, [
                $product['name'],
                $product['sku'],
                $product['category_name'] ?: 'Uncategorized',
                $product['quantity'],
                $product['cost_price'],
                $product['selling_price'],
                $product['status'] == 1 ? 'Active' : 'Inactive'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // AJAX: Get daily stats for POS
    public function get_daily_stats() {
        $today = date('Y-m-d');
        
        $this->db->select('SUM(total_amount) as total_sales, COUNT(*) as transaction_count, COUNT(DISTINCT student_id) as customer_count');
        $this->db->from('inventory_sales');
        $this->db->where('DATE(sale_date)', $today);
        $stats = $this->db->get()->row_array();
        
        $stats['total_sales'] = $stats['total_sales'] ?: 0;
        $stats['transaction_count'] = $stats['transaction_count'] ?: 0;
        $stats['customer_count'] = $stats['customer_count'] ?: 0;
        $stats['avg_sale'] = $stats['transaction_count'] > 0 ? $stats['total_sales'] / $stats['transaction_count'] : 0;
        $stats['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        echo json_encode($stats);
    }
    
    // AJAX: Get recent transactions
    public function get_recent_transactions() {
        $today = date('Y-m-d');
        
        $this->db->select('s.id, s.sale_date, s.total_amount, s.receipt_code, COALESCE(s.customer_name, st.name) as customer_name, COUNT(si.id) as item_count, TIME(s.sale_date) as time');
        $this->db->from('inventory_sales s');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->join('inventory_sale_items si', 'si.sale_id = s.id', 'left');
        $this->db->where('DATE(s.sale_date)', $today);
        $this->db->group_by('s.id');
        $this->db->order_by('s.sale_date', 'DESC');
        $this->db->limit(20);
        
        $transactions = $this->db->get()->result_array();
        
        foreach($transactions as &$t) {
            $t['customer_name'] = $t['customer_name'] ?: 'Walk-in Customer';
        }
        
        echo json_encode($transactions);
    }
    
    // AJAX: Get items sold breakdown
    public function get_items_sold_breakdown() {
        $start = $this->input->get('start_date');
        $end = $this->input->get('end_date');
        $customer = $this->input->get('customer');
        
        // Convert dates from dd/mm/yyyy to yyyy-mm-dd for MySQL
        if($start) $start = $this->convert_date_to_mysql($start);
        if($end) $end = $this->convert_date_to_mysql($end);
        
        $this->db->select('p.name as product_name, SUM(si.quantity) as total_quantity');
        $this->db->from('inventory_sale_items si');
        $this->db->join('inventory_products p', 'p.id = si.product_id');
        $this->db->join('inventory_sales s', 's.id = si.sale_id');
        
        if($start) $this->db->where('DATE(s.sale_date) >=', $start);
        if($end) $this->db->where('DATE(s.sale_date) <=', $end);
        
        if($customer !== null && $customer !== '') {
            if($customer == '0') {
                $this->db->group_start();
                $this->db->where('s.student_id', NULL);
                $this->db->or_where('s.student_id', 0);
                $this->db->group_end();
            } elseif(strpos($customer, 'walkin_') === 0) {
                $custom_name = substr($customer, 7);
                $this->db->where('s.customer_name', $custom_name);
                $this->db->group_start();
                $this->db->where('s.student_id', NULL);
                $this->db->or_where('s.student_id', 0);
                $this->db->group_end();
            } else {
                $this->db->where('s.student_id', $customer);
            }
        }
        
        $this->db->group_by('si.product_id');
        $this->db->order_by('total_quantity', 'DESC');
        
        echo json_encode($this->db->get()->result_array());
    }
    
    // Sale details view
    public function get_sale_for_print($sale_id) {
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $school = $this->db->get_where('settings', ['type' => 'system_name'])->row()->description;
        $slogan = $this->db->get_where('settings', ['type' => 'system_title'])->row()->description;
        
        $this->db->select('s.*, COALESCE(s.customer_name, st.name) as customer_name');
        $this->db->from('inventory_sales s');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->where('s.id', $sale_id);
        $sale = $this->db->get()->row_array();
        
        if (!$sale) {
            echo json_encode(['error' => 'Sale not found']);
            return;
        }
        
        $this->db->select('si.*, p.name as product_name');
        $this->db->from('inventory_sale_items si');
        $this->db->join('inventory_products p', 'p.id = si.product_id');
        $this->db->where('si.sale_id', $sale_id);
        $items = $this->db->get()->result_array();
        
        echo json_encode([
            'sale' => $sale,
            'items' => $items,
            'currency' => $currency,
            'school' => $school,
            'slogan' => $slogan
        ]);
    }
    
    public function sale_details($id) {
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $school = $this->db->get_where('settings', ['type' => 'system_name'])->row()->description;
        $slogan = $this->db->get_where('settings', ['type' => 'system_title'])->row()->description;
        
        $this->db->select('s.*, COALESCE(s.customer_name, st.name) as customer_name');
        $this->db->from('inventory_sales s');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->where('s.id', $id);
        $sale = $this->db->get()->row_array();
        
        $this->db->select('si.*, p.name as product_name');
        $this->db->from('inventory_sale_items si');
        $this->db->join('inventory_products p', 'p.id = si.product_id');
        $this->db->where('si.sale_id', $id);
        $items = $this->db->get()->result_array();
        
        $itemsJson = json_encode($items);
        $saleJson = json_encode($sale);
        
        $html = '<div style="font-size: 1.125rem;">';
        $html .= '<div class="mb-3"><button onclick="printReceiptNow(' . $id . ', \'' . addslashes($currency) . '\', \'' . addslashes($school) . '\', \'' . addslashes($slogan) . '\')" class="btn btn-primary" style="font-size: 1.125rem;"><i class="fa fa-print mr-2"></i>Print Receipt</button></div>';
        $html .= '<p><strong>Date:</strong> ' . date('d M, Y H:i', strtotime($sale['sale_date'])) . '</p>';
        $html .= '<p><strong>Customer:</strong> ' . ($sale['customer_name'] ?: 'Walk-in Customer') . '</p>';
        $html .= '<p><strong>Total:</strong> ' . $currency . ' ' . number_format($sale['total_amount'], 2) . '</p>';
        $html .= '<hr style="margin: 15px 0;">';
        $html .= '<h4>Items:</h4>';
        $html .= '<table class="table table-bordered" style="width: 100%; margin-top: 10px;">';
        $html .= '<thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>';
        
        foreach($items as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $item['product_name'] . '</td>';
            $html .= '<td>' . $item['quantity'] . '</td>';
            $html .= '<td>' . $currency . ' ' . number_format($item['unit_price'], 2) . '</td>';
            $html .= '<td>' . $currency . ' ' . number_format($item['total_price'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '<script>';
        $html .= 'window.currentSale = ' . $saleJson . ';';
        $html .= 'window.currentItems = ' . $itemsJson . ';';
        $html .= '</script>';
        $html .= '</div>';
        
        echo $html;
    }
    
    public function sale_form($id = null) {
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        $sale = null;
        $items = [];
        
        if($id) {
            // Load existing sale
            $this->db->select('s.*, COALESCE(s.customer_name, st.name) as customer_name, s.customer_name as custom_walkin_name, st.student_id');
            $this->db->from('inventory_sales s');
            $this->db->join('student st', 'st.student_id = s.student_id', 'left');
            $this->db->where('s.id', $id);
            $sale = $this->db->get()->row_array();
            
            // Load sale items
            $this->db->select('si.*, p.name as product_name, p.selling_price as current_price');
            $this->db->from('inventory_sale_items si');
            $this->db->join('inventory_products p', 'p.id = si.product_id');
            $this->db->where('si.sale_id', $id);
            $items = $this->db->get()->result_array();
        }
        
        // Get active products for dropdown
        $products = $this->db->select('id, name, selling_price, quantity')
            ->where('status', 1)
            ->order_by('name', 'ASC')
            ->get('inventory_products')
            ->result_array();
        
        // Get students for customer dropdown
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        $this->db->select('s.student_id, s.name, c.name as class_name, c.name_numeric, sec.name as section_name');
        $this->db->from('student s');
        $this->db->join('enroll e', 'e.student_id = s.student_id');
        $this->db->join('class c', 'c.class_id = e.class_id', 'left');
        $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
        $this->db->where('e.year', $year);
        $this->db->where('e.term', $term);
        $this->db->where('e.mute', '0');
        $this->db->order_by('s.name');
        $students = $this->db->get()->result_array();
        
        $html = '
        <link href="' . base_url('assets/cdn/css/select2-4.1.0.min.css') . '" rel="stylesheet" />
        <script src="' . base_url('assets/cdn/js/select2-4.1.0.min.js') . '"></script>
        <style>
        .sale-form-container { font-size: 1.125rem; }
        .form-section { background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .form-section h4 { color: #1e40af; font-weight: 700; margin-bottom: 15px; font-size: 1.25rem; }
        .sale-item-row { background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 10px; transition: all 0.3s; }
        .sale-item-row:hover { border-color: #3b82f6; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1); }
        .btn-add-item { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: white; font-weight: 600; min-height: 3rem; }
        .btn-add-item:disabled { background: #d1d5db; cursor: not-allowed; }
        .btn-remove-item { background: #ef4444; border: none; color: white; width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .total-section { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 20px; border-radius: 12px; font-size: 1.5rem; font-weight: 700; }
        
        /* Uniform field heights */
        .form-control, .form-select { 
            font-size: 1.125rem !important; 
            height: 48px !important; 
            min-height: 48px !important;
            line-height: 1.5 !important;
            padding: 0.5rem 0.75rem !important; 
            border: 2px solid #e5e7eb !important; 
            border-radius: 8px !important;
        }
        .form-control:focus, .form-select:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important; }
        .form-label { font-weight: 600; color: #374151; margin-bottom: 8px; display: block; }
        
        /* Select2 uniform height */
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { 
            height: 48px !important; 
            min-height: 48px !important;
            padding: 0.5rem 0.75rem !important; 
            border: 2px solid #e5e7eb !important; 
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            line-height: 32px !important; 
            font-size: 1.125rem !important;
            padding-left: 0 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { 
            height: 46px !important;
            top: 1px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single { 
            border-color: #3b82f6 !important; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        .select2-results__option { font-size: 1.125rem !important; padding: 0.5rem 0.75rem !important; }
        .select2-dropdown { border: 2px solid #3b82f6 !important; border-radius: 8px !important; }
        </style>
        
        <div class="sale-form-container">';
        
        $html .= form_open('inventory/' . ($id ? 'update_sale' : 'create_sale'), ['id' => 'sale_form', 'class' => 'needs-validation']);
        
        if($id) {
            $html .= '<input type="hidden" name="sale_id" value="' . $id . '">';
        }
        
        // Customer Section
        $html .= '<div class="form-section">';
        $html .= '<h4><i class="fa fa-user mr-2"></i>Customer Information</h4>';
        $html .= '<div class="row">';
        
        $html .= '<div class="col-md-8 mb-3">';
        $html .= '<label class="form-label">Customer</label>';
        $html .= '<select name="student_id" id="edit_customer_select" class="form-select" style="font-size: 1.125rem;">';
        $html .= '<option value="walk-in"' . (($sale && !$sale['student_id']) ? ' selected' : '') . '>Walk-in Customer</option>';
        foreach($students as $student) {
            $selected = ($sale && $sale['student_id'] == $student['student_id']) ? 'selected' : '';
            $class_full = trim($student['class_name'] . ' ' . $student['name_numeric'] . ' ' . $student['section_name']);
            $html .= '<option value="' . $student['student_id'] . '" ' . $selected . '>' . $student['name'] . ' - ' . $class_full . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-4 mb-3">';
        $html .= '<label class="form-label">Sale Date & Time</label>';
        $html .= '<input type="datetime-local" name="sale_date" class="form-control" value="' . ($sale ? date('Y-m-d\TH:i', strtotime($sale['sale_date'])) : date('Y-m-d\TH:i')) . '" required>';
        $html .= '</div>';
        
        // Walk-in customer name input (shown only for walk-ins)
        $is_walkin = ($sale && !$sale['student_id']);
        $html .= '<div class="col-md-12 mb-3" id="edit_walkin_name_container" style="display: ' . ($is_walkin ? 'block' : 'none') . ';">';
        $html .= '<label class="form-label"><i class="fa fa-user-tag mr-2 text-blue-600"></i>Walk-in Customer Name (Optional)</label>';
        $html .= '<input type="text" name="walkin_customer_name" id="edit_walkin_customer_name" class="form-control" placeholder="Enter customer name (leave blank for \'Walk-in Customer\')" value="' . ($sale && $sale['custom_walkin_name'] ? htmlspecialchars($sale['custom_walkin_name']) : '') . '">';
        $html .= '<small class="text-muted"><i class="fa fa-info-circle mr-1"></i>If left blank, will be saved as "Walk-in Customer"</small>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Items Section
        $html .= '<div class="form-section">';
        $html .= '<h4><i class="fa fa-shopping-cart mr-2"></i>Sale Items</h4>';
        $html .= '<div id="sale_items_container">';
        
        if(!empty($items)) {
            foreach($items as $index => $item) {
                $html .= $this->generate_modern_sale_item_row($index, $item, $products, $currency);
            }
        } else {
            $html .= $this->generate_modern_sale_item_row(0, null, $products, $currency);
        }
        
        $html .= '</div>';
        $html .= '<button type="button" onclick="addSaleItemRow()" id="btn_add_item" class="btn btn-add-item mt-2" disabled style="font-size: 1.125rem; min-height: 3rem; padding: 0 20px;"><i class="fa fa-plus mr-2"></i>Add Another Item</button>';
        $html .= '</div>';
        
        // Total Section
        $html .= '<div class="total-section text-center mb-4">';
        $html .= '<div class="mb-2" style="font-size: 1rem; opacity: 0.9;">TOTAL AMOUNT</div>';
        $html .= '<div id="sale_total" style="font-size: 2.5rem;"><sup style="font-size: 0.5em;">' . $currency . '</sup> 0.00</div>';
        $html .= '</div>';
        
        // Action Buttons
        $html .= '<div class="text-right">';
        $html .= '<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal" style="font-size: 1.125rem; min-height: 3rem; padding: 0 30px;">Cancel</button>';
        $html .= '<button type="submit" class="btn btn-primary" style="font-size: 1.125rem; min-height: 3rem; padding: 0 30px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: none; font-weight: 600;"><i class="fa fa-save mr-2"></i>' . ($id ? 'Update Sale' : 'Create Sale') . '</button>';
        $html .= '</div>';
        
        $html .= form_close();
        $html .= $this->generate_sale_form_scripts($items, $products, $currency);
        $html .= '</div>';
        
        echo $html;
    }
    
    private function generate_modern_sale_item_row($index, $item, $products, $currency) {
        $html = '<div class="sale-item-row" id="sale_item_' . $index . '">';
        $html .= '<div class="row align-items-center">';
        
        // Product Select
        $html .= '<div class="col-md-5 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Product</small></label>';
        $html .= '<select name="product_id[]" class="form-select" required onchange="updatePriceFromProduct(' . $index . ')">';
        $html .= '<option value="">Select Product</option>';
        
        foreach($products as $p) {
            $selected = ($item && $item['product_id'] == $p['id']) ? 'selected' : '';
            $stock_info = $p['quantity'] > 0 ? ' (Stock: ' . $p['quantity'] . ')' : ' (Out of Stock)';
            $html .= '<option value="' . $p['id'] . '" data-price="' . $p['selling_price'] . '" ' . $selected . '>' . $p['name'] . ' - ' . $currency . ' ' . number_format($p['selling_price'], 2) . $stock_info . '</option>';
        }
        
        $html .= '</select>';
        $html .= '</div>';
        
        // Quantity
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Quantity</small></label>';
        $html .= '<input type="number" name="quantity[]" class="form-control text-center" placeholder="Qty" min="1" value="' . ($item ? $item['quantity'] : '1') . '" required onchange="calculateItemTotal(' . $index . ')">';
        $html .= '</div>';
        
        // Unit Price
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Unit Price</small></label>';
        $html .= '<input type="number" name="unit_price[]" class="form-control text-center" placeholder="Price" step="0.01" value="' . ($item ? $item['unit_price'] : '0') . '" required onchange="calculateItemTotal(' . $index . ')">';
        $html .= '</div>';
        
        // Item Total (readonly)
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Total</small></label>';
        $html .= '<input type="number" name="item_total[]" class="form-control text-center font-weight-bold" placeholder="Total" readonly value="' . ($item ? $item['total_price'] : '0') . '" style="background: #f3f4f6;">';
        $html .= '</div>';
        
        // Remove Button
        $html .= '<div class="col-md-1 mb-2 text-center">';
        $html .= '<label class="form-label mb-1"><small>&nbsp;</small></label>';
        $html .= '<button type="button" onclick="removeSaleItem(' . $index . ')" class="btn btn-remove-item" title="Remove Item"><i class="fa fa-trash"></i></button>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function generate_sale_form_scripts($items, $products, $currency) {
        $html = '<script>';
        $html .= 'var saleItemIndex = ' . (count($items) > 0 ? count($items) : 1) . ';';
        $html .= 'var products = ' . json_encode($products) . ';';
        $html .= 'var currency = "' . $currency . '";';
        $html .= '
        // Get all selected product IDs
        function getSelectedProductIds() {
            var selected = [];
            $("select[name=\'product_id[]\']").each(function() {
                var val = $(this).val();
                if(val) selected.push(val);
            });
            return selected;
        }
        
        // Check if add button should be enabled
        function checkAddButtonState() {
            var allSelected = true;
            $("select[name=\'product_id[]\']").each(function() {
                if(!$(this).val()) {
                    allSelected = false;
                    return false; // break
                }
            });
            $("#btn_add_item").prop("disabled", !allSelected);
        }
        
        // Filter products for dropdown (exclude already selected)
        function getAvailableProducts() {
            var selectedIds = getSelectedProductIds();
            return products.filter(function(p) {
                return !selectedIds.includes(p.id.toString());
            });
        }
        
        // Initialize Select2 on a specific select element
        function initializeSelect2(selectElement, currentValue) {
            var selectedIds = getSelectedProductIds();
            var availableProducts = products.filter(function(p) {
                return !selectedIds.includes(p.id.toString()) || p.id.toString() === currentValue;
            });
            
            // Destroy existing Select2 if present
            if($(selectElement).data("select2")) {
                $(selectElement).select2("destroy");
            }
            
            // Clear and rebuild options
            $(selectElement).empty().append("<option value=\'\'>Select Product</option>");
            availableProducts.forEach(function(p) {
                var stockInfo = p.quantity > 0 ? " (Stock: " + p.quantity + ")" : " (Out of Stock)";
                var selected = p.id.toString() === currentValue ? " selected" : "";
                $(selectElement).append(`<option value="${p.id}" data-price="${p.selling_price}"${selected}>${p.name} - ${currency} ${parseFloat(p.selling_price).toFixed(2)}${stockInfo}</option>`);
            });
            
            // Initialize Select2
            $(selectElement).select2({
                placeholder: "Search products...",
                allowClear: true,
                width: "100%",
                dropdownParent: $("#modal_edit_sale")
            });
        }
        
        // Refresh all Select2 dropdowns to exclude selected products
        function refreshAllProductDropdowns() {
            $("select[name=\'product_id[]\']").each(function() {
                var currentValue = $(this).val();
                initializeSelect2(this, currentValue);
            });
            checkAddButtonState();
        }
        
        // Toggle walk-in name input
        $("#edit_customer_select").on("change", function() {
            if($(this).val() === "walk-in" || $(this).val() === "") {
                $("#edit_walkin_name_container").slideDown();
            } else {
                $("#edit_walkin_name_container").slideUp();
                $("#edit_walkin_customer_name").val("");
            }
        });
        
        function addSaleItemRow() {
            var html = `
                <div class="sale-item-row" id="sale_item_${saleItemIndex}">
                    <div class="row align-items-center">
                        <div class="col-md-5 mb-2">
                            <label class="form-label mb-1"><small>Product</small></label>
                            <select name="product_id[]" class="form-select product-select" data-index="${saleItemIndex}" required>
                                <option value="">Select Product</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label mb-1"><small>Quantity</small></label>
                            <input type="number" name="quantity[]" class="form-control text-center" placeholder="Qty" min="1" value="1" required data-index="${saleItemIndex}">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label mb-1"><small>Unit Price</small></label>
                            <input type="number" name="unit_price[]" class="form-control text-center" placeholder="Price" step="0.01" value="0" required data-index="${saleItemIndex}">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label mb-1"><small>Total</small></label>
                            <input type="number" name="item_total[]" class="form-control text-center font-weight-bold" placeholder="Total" readonly value="0" style="background: #f3f4f6;" data-index="${saleItemIndex}">
                        </div>
                        <div class="col-md-1 mb-2 text-center">
                            <label class="form-label mb-1"><small>&nbsp;</small></label>
                            <button type="button" class="btn btn-remove-item" data-index="${saleItemIndex}" title="Remove Item"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            `;
            
            $("#sale_items_container").append(html);
            
            // Initialize Select2 on new row
            var newSelect = $("#sale_item_" + saleItemIndex).find("select[name=\'product_id[]\']");
            initializeSelect2(newSelect[0], "");
            
            // Attach event handlers
            attachRowEventHandlers(saleItemIndex);
            
            saleItemIndex++;
            checkAddButtonState();
        }
        
        // Attach event handlers to a row
        function attachRowEventHandlers(index) {
            // Product change event
            $("#sale_item_" + index).find("select[name=\'product_id[]\']").on("change", function() {
                updatePriceFromProduct(index);
                refreshAllProductDropdowns();
            });
            
            // Quantity/Price change events
            $("#sale_item_" + index).find("input[name=\'quantity[]\'], input[name=\'unit_price[]\']").on("input change", function() {
                calculateItemTotal(index);
            });
            
            // Remove button
            $("#sale_item_" + index).find(".btn-remove-item").on("click", function() {
                removeSaleItem(index);
            });
        }
        
        function updatePriceFromProduct(index) {
            var select = $("#sale_item_" + index).find("select[name=\'product_id[]\']");
            var price = select.find("option:selected").data("price") || 0;
            $("#sale_item_" + index).find("input[name=\'unit_price[]\']").val(price);
            calculateItemTotal(index);
        }
        
        function calculateItemTotal(index) {
            var qty = parseFloat($("#sale_item_" + index).find("input[name=\'quantity[]\']").val() || 0);
            var price = parseFloat($("#sale_item_" + index).find("input[name=\'unit_price[]\']").val() || 0);
            var total = qty * price;
            $("#sale_item_" + index).find("input[name=\'item_total[]\']").val(total.toFixed(2));
            calculateSaleTotal();
        }
        
        function removeSaleItem(index) {
            if($(".sale-item-row").length > 1) {
                $("#sale_item_" + index).fadeOut(300, function() {
                    $(this).remove();
                    calculateSaleTotal();
                    refreshAllProductDropdowns();
                });
            } else {
                showAjaxModal_alert("At least one item is required", "warning");
            }
        }
        
        function calculateSaleTotal() {
            let total = 0;
            $("input[name^=\'item_total\']").each(function() {
                total += parseFloat($(this).val() || 0);
            });
            $("#sale_total").html("<sup style=\'font-size: 0.5em;\'>" + currency + "</sup> " + total.toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }
        
        // Form submission
        $("#sale_form").submit(function(e) {
            e.preventDefault();
            
            // Validate at least one item
            if($(".sale-item-row").length === 0) {
                showAjaxModal_alert("Please add at least one item", "warning");
                return;
            }
            
            // Validate all items have products selected
            var hasEmptyProduct = false;
            $("select[name=\'product_id[]\']").each(function() {
                if(!$(this).val()) {
                    hasEmptyProduct = true;
                    return false;
                }
            });
            
            if(hasEmptyProduct) {
                showAjaxModal_alert("Please select a product for all items", "warning");
                return;
            }
            
            // Show loading
            var submitBtn = $(this).find("button[type=submit]");
            var originalText = submitBtn.html();
            submitBtn.prop("disabled", true).html("<i class=\'fa fa-spinner fa-spin mr-2\'></i>Processing...");
            
            $.ajax({
                url: $(this).attr("action"),
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if(response.status === "success") {
                        showAjaxModal_alert(response.message, "success");
                        setTimeout(function() {
                            $(".close").click();
                            if(typeof loadSales === "function") loadSales();
                            if(typeof loadRecentTransactions === "function") loadRecentTransactions();
                        }, 1500);
                    } else {
                        showAjaxModal_alert(response.message, "error");
                        submitBtn.prop("disabled", false).html(originalText);
                    }
                },
                error: function(xhr) {
                    var errorMsg = "An error occurred while processing the request";
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showAjaxModal_alert(errorMsg, "error");
                    submitBtn.prop("disabled", false).html(originalText);
                }
            });
        });
        
        // Initialize on load
        $(document).ready(function() {
            // Initialize Select2 on existing rows
            $("select[name=\'product_id[]\']").each(function(index) {
                var currentValue = $(this).val();
                initializeSelect2(this, currentValue);
                attachRowEventHandlers(index);
            });
            
            // Initial calculation
            calculateSaleTotal();
            checkAddButtonState();
        });
        ';
        $html .= '</script>';
        
        return $html;
    }
    
    private function generate_sale_item_row($index, $item, $products, $currency) {
        $html = '<div class="sale-item-row" id="sale_item_' . $index . '">';
        $html .= '<div class="row align-items-center">';
        
        // Product select
        $html .= '<div class="col-md-5 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Product</small></label>';
        $html .= '<select name="product_id[]" class="form-select product-select" data-index="' . $index . '" required>';
        $html .= '<option value="">Select Product</option>';
        foreach($products as $product) {
            $selected = ($item && $item['product_id'] == $product['id']) ? 'selected' : '';
            $stock_info = $product['quantity'] > 0 ? ' (Stock: ' . $product['quantity'] . ')' : ' (Out of Stock)';
            $html .= '<option value="' . $product['id'] . '" data-price="' . $product['selling_price'] . '" ' . $selected . '>' 
                   . $product['name'] . ' - ' . $currency . ' ' . number_format($product['selling_price'], 2) . $stock_info . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        
        // Quantity
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Quantity</small></label>';
        $html .= '<input type="number" name="quantity[]" class="form-control text-center" placeholder="Qty" min="1" value="' . ($item ? $item['quantity'] : 1) . '" required data-index="' . $index . '">';
        $html .= '</div>';
        
        // Unit Price
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Unit Price</small></label>';
        $html .= '<input type="number" name="unit_price[]" class="form-control text-center" placeholder="Price" step="0.01" value="' . ($item ? $item['unit_price'] : 0) . '" required data-index="' . $index . '">';
        $html .= '</div>';
        
        // Item Total
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="form-label mb-1"><small>Total</small></label>';
        $html .= '<input type="number" name="item_total[]" class="form-control text-center font-weight-bold" placeholder="Total" readonly value="' . ($item ? $item['total_price'] : 0) . '" style="background: #f3f4f6;" data-index="' . $index . '">';
        $html .= '</div>';
        
        // Remove button
        $html .= '<div class="col-md-1 mb-2 text-center">';
        $html .= '<label class="form-label mb-1"><small>&nbsp;</small></label>';
        $html .= '<button type="button" class="btn btn-remove-item" data-index="' . $index . '" title="Remove Item"><i class="fa fa-trash"></i></button>';
        $html .= '</div>';
        
        $html .= '</div>'; // row
        $html .= '</div>'; // sale-item-row
        
        return $html;
    }
    
    /**
     * AJAX: Create new sale
     * Access: Admin levels 1, 2, 3, 6
     */
    public function create_sale() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $sale_date = $this->input->post('sale_date');
        $product_ids = $this->input->post('product_id');
        $quantities = $this->input->post('quantity');
        $unit_prices = $this->input->post('unit_price');
        
        if(empty($product_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No products selected']);
            return;
        }
        
        $this->db->trans_start();
        
        try {
            // Calculate total
            $total_amount = 0;
            for($i = 0; $i < count($product_ids); $i++) {
                $total_amount += $unit_prices[$i] * $quantities[$i];
            }
            
            // Insert sale header
            $sale_data = [
                'student_id' => !empty($student_id) ? $student_id : NULL,
                'total_amount' => $total_amount,
                'sale_date' => date('Y-m-d H:i:s', strtotime($sale_date)),
                'served_by' => $admin_id
            ];
            
            $this->db->insert('inventory_sales', $sale_data);
            $sale_id = $this->db->insert_id();
            
            // Insert sale items and update stock
            for($i = 0; $i < count($product_ids); $i++) {
                $item_data = [
                    'sale_id' => $sale_id,
                    'product_id' => $product_ids[$i],
                    'quantity' => $quantities[$i],
                    'unit_price' => $unit_prices[$i],
                    'total_price' => $unit_prices[$i] * $quantities[$i]
                ];
                
                $this->db->insert('inventory_sale_items', $item_data);
                
                // Get product name for movement log
                $product = $this->db->get_where('inventory_products', ['id' => $product_ids[$i]])->row();
                
                // Deduct stock
                $this->db->set('quantity', 'quantity - ' . $quantities[$i], FALSE)
                    ->where('id', $product_ids[$i])
                    ->update('inventory_products');
                
                // Create stock movement record
                $movement = [
                    'product_id' => $product_ids[$i],
                    'movement_type' => 'out',
                    'quantity' => $quantities[$i],
                    'notes' => 'Sale #' . $sale_id . ' - ' . ($product ? $product->name : 'Product'),
                    'performed_by' => $admin_id,
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // Financial Integration: Record revenue
            $this->load->library('Financial_integration_hooks');
            $revenue_result = $this->financial_integration_hooks->record_income([
                'transaction_type' => 'Inventory Sale',
                'amount' => $total_amount,
                'student_id' => $student_id,
                'reference' => 'Sale #' . $sale_id,
                'date' => date('Y-m-d', strtotime($sale_date)),
                'payment_method' => 1, // Cash
                'notes' => 'Inventory sale revenue',
                'source_type' => 'inventory_sale',
                'source_id' => $sale_id
            ]);
            
            if(!$revenue_result['success']) {
                throw new Exception('Financial integration failed: ' . $revenue_result['message']);
            }
            
            $this->db->trans_complete();
            
            if($this->db->trans_status()) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Sale created successfully',
                    'sale_id' => $sale_id
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create sale']);
            }
            
        } catch(Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Create sale failed: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Update existing sale
     * Access: Admin levels 1, 2, 3, 6
     */
    public function update_sale() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $sale_id = $this->input->post('sale_id');
        
        // Check if sale has any returns - prevent editing if so
        $return_count = $this->db->where('original_sale_id', $sale_id)
                                 ->count_all_results('inventory_returns');
        
        if($return_count > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot edit a sale that has returns. The return transaction must be voided first.'
            ]);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $walkin_customer_name = $this->input->post('walkin_customer_name');
        $sale_date = $this->input->post('sale_date');
        $product_ids = $this->input->post('product_id');
        $quantities = $this->input->post('quantity');
        $unit_prices = $this->input->post('unit_price');
        
        if(empty($sale_id) || empty($product_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid sale data']);
            return;
        }
        
        $this->db->trans_start();
        
        try {
            // Get old sale data to restore stock and reverse financial
            $old_sale = $this->db->get_where('inventory_sales', ['id' => $sale_id])->row();
            $old_items = $this->db->get_where('inventory_sale_items', ['sale_id' => $sale_id])->result_array();
            
            // Restore stock for old items and create reversal movements
            foreach($old_items as $old_item) {
                $this->db->set('quantity', 'quantity + ' . $old_item['quantity'], FALSE)
                    ->where('id', $old_item['product_id'])
                    ->update('inventory_products');
                
                // Create reversal stock movement
                $movement = [
                    'product_id' => $old_item['product_id'],
                    'movement_type' => 'in',
                    'quantity' => $old_item['quantity'],
                    'notes' => 'Reversal for edit: Sale #' . $sale_id,
                    'performed_by' => $admin_id,
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // Calculate new total
            $total_amount = 0;
            for($i = 0; $i < count($product_ids); $i++) {
                $total_amount += $unit_prices[$i] * $quantities[$i];
            }
            
            // Determine if walk-in customer
            $is_walkin = (empty($student_id) || $student_id === 'walk-in' || $student_id === '');
            
            // Update sale
            $sale_data = [
                'student_id' => $is_walkin ? NULL : $student_id,
                'customer_name' => $is_walkin && !empty($walkin_customer_name) ? trim($walkin_customer_name) : NULL,
                'total_amount' => $total_amount,
                'sale_date' => date('Y-m-d H:i:s', strtotime($sale_date))
            ];
            
            $this->db->where('id', $sale_id)->update('inventory_sales', $sale_data);
            
            // Delete old items
            $this->db->where('sale_id', $sale_id)->delete('inventory_sale_items');
            
            // Insert new items and update stock
            for($i = 0; $i < count($product_ids); $i++) {
                $item_data = [
                    'sale_id' => $sale_id,
                    'product_id' => $product_ids[$i],
                    'quantity' => $quantities[$i],
                    'unit_price' => $unit_prices[$i],
                    'total_price' => $unit_prices[$i] * $quantities[$i]
                ];
                
                $this->db->insert('inventory_sale_items', $item_data);
                
                // Get product name
                $product = $this->db->get_where('inventory_products', ['id' => $product_ids[$i]])->row();
                
                // Deduct stock for new items
                $this->db->set('quantity', 'quantity - ' . $quantities[$i], FALSE)
                    ->where('id', $product_ids[$i])
                    ->update('inventory_products');
                
                // Create new stock movement
                $movement = [
                    'product_id' => $product_ids[$i],
                    'movement_type' => 'out',
                    'quantity' => $quantities[$i],
                    'notes' => 'Updated Sale #' . $sale_id . ' - ' . ($product ? $product->name : 'Product'),
                    'performed_by' => $admin_id,
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // Update financial journal entry
            $this->db->where('source_type', 'inventory_sale');
            $this->db->where('source_id', $sale_id);
            $journal_entry = $this->db->get('journal_entries')->row();
            
            if($journal_entry) {
                // Delete old journal entry lines
                $this->db->where('entry_id', $journal_entry->id);
                $this->db->delete('journal_entry_lines');
                
                // Update journal entry
                $this->db->where('id', $journal_entry->id);
                $this->db->update('journal_entries', [
                    'amount' => $total_amount,
                    'description' => 'Updated Inventory Sale #' . $sale_id,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Re-create journal entry lines
                $this->load->library('Financial_integration_hooks');
                $this->financial_integration_hooks->update_journal_entry_lines($journal_entry->id, $total_amount);
            }
            
            $this->db->trans_complete();
            
            if($this->db->trans_status()) {
                echo json_encode(['status' => 'success', 'message' => 'Sale updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update sale']);
            }
            
        } catch(Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Update sale failed: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    // ============================================
    // PURCHASE ORDERS CONTROLLER METHODS
    // ============================================
    
    /**
     * Purchase orders management interface
     * Access: Admin levels 1, 2, 3, 6
     */
    public function purchase_orders() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            show_error('Access Denied: You do not have permission to manage purchase orders.');
        }
        
        $page_data['page_name'] = 'inventory/purchase_orders';
        $page_data['page_title'] = get_phrase('purchase_orders');
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Purchase order form modal
     * Returns HTML form for creating/editing purchase orders
     */
    public function purchase_order_form($id = null) {
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        // Get suppliers
        $suppliers = $this->Inventory_model->get_suppliers();
        
        // Get products
        $products = $this->db->select('id, name, sku, cost_price, quantity')
            ->where('status', 1)
            ->order_by('name', 'ASC')
            ->get('inventory_products')
            ->result_array();
        
        $purchase = null;
        $has_payments = false;
        $payment_debug = '';
        $latest_payment = null;
        if($id) {
            // Editing existing purchase order
            $purchase = $this->Inventory_model->get_purchase_order_details($id);
            // Check if this purchase order has payment records
            $payment_count = $this->db->where('purchase_id', $id)->count_all_results('inventory_purchase_payments');
            $has_payments = ($payment_count > 0);
            
            // Get the most recent payment for populating form fields
            if($has_payments) {
                $latest_payment = $this->db
                    ->where('purchase_id', $id)
                    ->order_by('payment_date', 'DESC')
                    ->order_by('id', 'DESC')
                    ->limit(1)
                    ->get('inventory_purchase_payments')
                    ->row_array();
            }
            
            $payment_debug = '<!-- DEBUG: PO ID=' . $id . ', Payment Count=' . $payment_count . ', Has Payments=' . ($has_payments ? 'YES' : 'NO') . ' -->';
            
            // Debug logging
            log_message('debug', 'Edit PO ' . $id . ': Payment count = ' . $payment_count . ', has_payments = ' . ($has_payments ? 'true' : 'false'));
        }
        
        // Include Select2
        $html = '
        <link href="' . base_url('assets/cdn/css/select2-4.1.0.min.css') . '" rel="stylesheet" />
        <script src="' . base_url('assets/cdn/js/select2-4.1.0.min.js') . '"></script>
        <style>
        /* Select2 uniform height for purchase orders */
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { 
            height: 56px !important; 
            min-height: 56px !important;
            padding: 0.75rem 1rem !important; 
            border: 1px solid #d1d5db !important; 
            border-radius: 0.5rem !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            line-height: 40px !important; 
            font-size: 1.125rem !important;
            padding-left: 0 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { 
            height: 54px !important;
            top: 1px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single { 
            border-color: #3b82f6 !important; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        .select2-results__option { font-size: 1.125rem !important; padding: 0.75rem 1rem !important; }
        .select2-dropdown { border: 2px solid #3b82f6 !important; border-radius: 0.5rem !important; }
        .select2-search__field { font-size: 1.125rem !important; padding: 0.5rem !important; }
        </style>
        
        <div class="p-6">';
        $html .= $payment_debug; // Add debug comment
        $html .= form_open('inventory/create_purchase_order', ['id' => 'purchase_order_form']);
        
        if($id) {
            $html .= '<input type="hidden" name="purchase_id" value="' . $id . '">';
        }
        
        // Single row: Supplier, Created Date, and Expected Delivery Date
        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">';
        
        // Supplier selection with search
        $html .= '<div>';
        $html .= '<label style="font-size: 1.375rem !important;" class="block font-semibold text-gray-700 mb-3">Supplier <span class="text-red-500">*</span></label>';
        $html .= '<div class="relative">';
        $html .= '<div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">';
        $html .= '<i class="fas fa-search text-gray-400 text-lg"></i>';
        $html .= '</div>';
        $html .= '<input type="text" id="supplier_search_input" class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3.5rem !important; height: 3.5rem !important;" placeholder="Type supplier name to search..." autocomplete="off">';
        $html .= '<input type="hidden" name="supplier_id" id="selected_supplier_id" value="' . ($purchase ? $purchase['supplier_id'] : '') . '" required>';
        $html .= '</div>';
        $html .= '<div id="supplier_search_results" class="absolute bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-80 overflow-y-auto z-50" style="display: none; width: calc(33.333% - 1rem);"></div>';
        $html .= '</div>';
        
        // Created date
        $html .= '<div>';
        $html .= '<label style="font-size: 1.375rem !important;" class="block font-semibold text-gray-700 mb-3">Created Date <span class="text-red-500">*</span></label>';
        $created_date = $purchase ? date('d/m/Y', strtotime($purchase['created_at'])) : date('d/m/Y');
        $html .= '<div class="relative">';
        $html .= '<input type="text" name="created_date" id="po_created_date" value="' . $created_date . '" required class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3.5rem !important; height: 3.5rem !important;" placeholder="dd/mm/yyyy" autocomplete="off">';
        $html .= '<div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">';
        $html .= '<i class="far fa-calendar-alt text-gray-400 text-lg"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Expected delivery date
        $html .= '<div>';
        $html .= '<label style="font-size: 1.375rem !important;" class="block font-semibold text-gray-700 mb-3">Expected Delivery Date</label>';
        $expected_date = $purchase ? date('d/m/Y', strtotime($purchase['expected_delivery_date'])) : date('d/m/Y');
        $html .= '<div class="relative">';
        $html .= '<input type="text" name="expected_delivery_date" id="po_expected_date" value="' . $expected_date . '" class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3.5rem !important; height: 3.5rem !important;" placeholder="dd/mm/yyyy" autocomplete="off">';
        $html .= '<div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">';
        $html .= '<i class="far fa-calendar-alt text-gray-400 text-lg"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Items section
        $html .= '<div class="mb-6">';
        $html .= '<label style="font-size: 1.375rem !important;" class="block font-semibold text-gray-700 mb-3">Items <span class="text-red-500">*</span></label>';
        $html .= '<div class="border border-gray-300 rounded-xl p-6 bg-gradient-to-r from-gray-50 to-gray-100">';
        
        // Add item button - Start enabled if no items exist
        $html .= '<button type="button" id="add_item_btn" onclick="addPurchaseItem()" class="px-6 py-3 font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors mb-4" style="font-size: 1.125rem !important; min-height: 3.5rem !important;"><i class="fa fa-plus mr-2"></i> Add Item</button>';
        
        // Items table
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full divide-y divide-gray-200 bg-white rounded-lg shadow-sm" id="purchase_items_table">';
        $html .= '<thead class="bg-gradient-to-r from-gray-50 to-gray-100">';
        $html .= '<tr>';
        $html .= '<th style="font-size: 1.125rem !important; width: 40%;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">Product</th>';
        $html .= '<th style="font-size: 1.125rem !important; width: 15%;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">Quantity</th>';
        $html .= '<th style="font-size: 1.125rem !important; width: 20%;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">Cost Price</th>';
        $html .= '<th style="font-size: 1.125rem !important; width: 20%;" class="px-6 py-4 text-left font-bold text-gray-700 uppercase tracking-wider">Total</th>';
        $html .= '<th style="font-size: 1.125rem !important; width: 5%;" class="px-6 py-4 text-center font-bold text-gray-700 uppercase tracking-wider"></th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="bg-white divide-y divide-gray-200" id="purchase_items_tbody">';
        
        if($purchase && !empty($purchase['items'])) {
            foreach($purchase['items'] as $item) {
                $html .= $this->generate_purchase_item_row($products, $item, $currency);
            }
        } else {
            $html .= '<tr><td colspan="5" style="font-size: 1.125rem !important;" class="px-6 py-10 text-center text-gray-500">No items added yet</td></tr>';
        }
        
        $html .= '</tbody>';
        $html .= '<tfoot class="bg-gradient-to-r from-gray-50 to-gray-100">';
        
        // Subtotal row
        $html .= '<tr>';
        $html .= '<td colspan="3" style="font-size: 1.125rem !important;" class="px-6 py-3 text-right font-semibold text-gray-700">Subtotal:</td>';
        $html .= '<td style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold text-gray-900" id="subtotal_display"><sup style="font-size: 0.6em; vertical-align: super;">' . $currency . '</sup> 0.00</td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        
        // Discount row
        $html .= '<tr>';
        $html .= '<td colspan="3" class="px-6 py-3">';
        $html .= '<div class="flex items-center justify-end gap-4">';
        $html .= '<span style="font-size: 1.125rem !important;" class="font-semibold text-gray-700">Discount:</span>';
        $discount_type = $purchase['discount_type'] ?? '';
        $discount_value = $purchase['discount_value'] ?? 0;
        $html .= '<select name="discount_type" id="discount_type" onchange="calculateDiscount()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3rem !important;">';
        $html .= '<option value="">No Discount</option>';
        $html .= '<option value="percentage"' . ($discount_type === 'percentage' ? ' selected' : '') . '>Percentage (%)</option>';
        $html .= '<option value="fixed"' . ($discount_type === 'fixed' ? ' selected' : '') . '>Fixed Amount</option>';
        $html .= '</select>';
        $html .= '<input type="number" name="discount_value" id="discount_value" min="0" step="0.01" value="' . $discount_value . '" placeholder="0" onchange="calculateDiscount()" class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3rem !important;">';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '<td style="font-size: 1.125rem !important;" class="px-6 py-3 font-semibold text-red-600" id="discount_amount_display">- <sup style="font-size: 0.6em; vertical-align: super;">' . $currency . '</sup> 0.00</td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        
        // Final Total row
        $html .= '<tr class="border-t-2 border-gray-300">';
        $html .= '<td colspan="3" style="font-size: 1.375rem !important;" class="px-6 py-4 text-right font-bold text-gray-900">Total Amount:</td>';
        $html .= '<td style="font-size: 1.375rem !important;" class="px-6 py-4 font-bold text-green-600" id="total_amount_display"><sup style="font-size: 0.6em; vertical-align: super;">' . $currency . '</sup> 0.00</td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Payment Section - RIGHT AFTER ITEMS
        // Get active payment methods
            $payment_methods = $this->db->where('is_active', 1)->order_by('display_order')->get('payment_methods')->result_array();
            
            $html .= '<div class="mb-6">';
            $html .= '<div class="flex items-center mb-4">';
            
            // Toggle Switch
            $html .= '<label class="relative inline-flex items-center cursor-pointer">';
            $html .= '<input type="checkbox" id="record_payment_now" name="record_payment_now" value="1" class="sr-only peer"' . ($has_payments ? ' checked' : '') . '>';
            $html .= '<div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>';
            $html .= '<span style="font-size: 1.375rem !important;" class="ml-4 font-semibold text-gray-700">Record Payment Now</span>';
            $html .= '</label>';
            
            $html .= '</div>';
            
            $html .= '<div id="payment_fields" style="display: ' . ($has_payments ? 'block' : 'none') . ';">';
            $html .= '<div class="bg-blue-50 border border-blue-200 rounded-lg p-6">';
            
            // Show existing payments if editing
            if($has_payments) {
                $html .= '<div class="mb-6 pb-4 border-b border-blue-300">';
                $html .= '<h4 style="font-size: 1.375rem !important;" class="font-bold text-gray-800 mb-4">Existing Payment Records</h4>';
                
                // Calculate total paid
                $total_paid = 0;
                $this->db->select_sum('amount');
                $this->db->where('purchase_id', $id);
                $total_paid_result = $this->db->get('inventory_purchase_payments')->row();
                $total_paid = $total_paid_result->amount ?? 0;
                
                // Get current total (before any edits)
                $current_total = $purchase['total_amount'];
                $balance = $current_total - $total_paid;
                
                // Payment status alert
                if($total_paid >= $current_total) {
                    $status_class = 'bg-green-50 border-green-300 text-green-800';
                    $status_icon = 'check-circle';
                    $status_text = 'Fully Paid';
                } else {
                    $status_class = 'bg-yellow-50 border-yellow-300 text-yellow-800';
                    $status_icon = 'exclamation-circle';
                    $status_text = 'Partially Paid';
                }
                
                $html .= '<div class="' . $status_class . ' border rounded-lg p-4 mb-4" id="payment_status_alert">';
                $html .= '<div class="flex items-center justify-between">';
                $html .= '<div class="flex items-center">';
                $html .= '<i class="fas fa-' . $status_icon . ' text-2xl mr-3" id="payment_status_icon"></i>';
                $html .= '<div>';
                $html .= '<p style="font-size: 1.125rem !important;" class="font-semibold" id="payment_status_text">' . $status_text . '</p>';
                $html .= '<p style="font-size: 1rem !important;" id="payment_summary_text">Total Paid: <strong><sup class="text-xs">' . $currency . '</sup> <span id="total_paid_display">' . number_format($total_paid, 2) . '</span></strong> of <strong><sup class="text-xs">' . $currency . '</sup> <span id="current_total_display">' . number_format($current_total, 2) . '</span></strong></p>';
                if($balance > 0) {
                    $html .= '<p style="font-size: 1rem !important;" id="balance_text">Outstanding Balance: <strong><sup class="text-xs">' . $currency . '</sup> <span id="balance_display">' . number_format($balance, 2) . '</span></strong></p>';
                } else {
                    $html .= '<p style="font-size: 1rem !important; display: none;" id="balance_text">Outstanding Balance: <strong><sup class="text-xs">' . $currency . '</sup> <span id="balance_display">0.00</span></strong></p>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="bg-white rounded-lg p-4">';
                
                // Get payment records
                $this->db->select('pp.*, pm.name as method_name');
                $this->db->from('inventory_purchase_payments pp');
                $this->db->join('payment_methods pm', 'pm.id = pp.payment_method_id', 'left');
                $this->db->where('pp.purchase_id', $id);
                $this->db->order_by('pp.payment_date', 'DESC');
                $existing_payments = $this->db->get()->result_array();
                
                $html .= '<table class="w-full" id="existing_payments_table">';
                $html .= '<thead><tr class="border-b border-gray-200">';
                $html .= '<th style="font-size: 1.125rem !important;" class="text-left py-2 font-semibold">Date</th>';
                $html .= '<th style="font-size: 1.125rem !important;" class="text-left py-2 font-semibold">Method</th>';
                $html .= '<th style="font-size: 1.125rem !important;" class="text-right py-2 font-semibold">Amount</th>';
                $html .= '<th style="font-size: 1.125rem !important;" class="text-right py-2 font-semibold">Reference</th>';
                $html .= '<th style="font-size: 1.125rem !important;" class="text-center py-2 font-semibold">Action</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                
                foreach($existing_payments as $pmt) {
                    $html .= '<tr class="border-b border-gray-100 payment-row" data-payment-id="' . $pmt['id'] . '" data-payment-amount="' . $pmt['amount'] . '">';
                    $html .= '<td style="font-size: 1.125rem !important;" class="py-2">' . date('d/m/Y', strtotime($pmt['payment_date'])) . '</td>';
                    $html .= '<td style="font-size: 1.125rem !important;" class="py-2">' . ($pmt['method_name'] ?: 'N/A') . '</td>';
                    $html .= '<td style="font-size: 1.125rem !important;" class="py-2 text-right font-semibold"><sup class="text-xs">' . $currency . '</sup> ' . number_format($pmt['amount'], 2) . '</td>';
                    $html .= '<td style="font-size: 1.125rem !important;" class="py-2 text-right">' . ($pmt['reference_number'] ?: '-') . '</td>';
                    $html .= '<td class="py-2 text-center">';
                    $html .= '<button type="button" class="px-2 py-1 rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none transition-colors delete-payment-btn" data-payment-id="' . $pmt['id'] . '" data-payment-amount="' . $pmt['amount'] . '" title="Delete payment"><i class="fa fa-times"></i></button>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
                $html .= '</div>';
                $html .= '<p style="font-size: 1rem !important;" class="text-gray-600 mt-3"><i class="fas fa-info-circle mr-1"></i><strong>Tip:</strong> You can delete existing payments and add a new payment record below if the amount has changed.</p>';
                $html .= '</div>';
                
                // Hidden field to track deleted payments
                $html .= '<input type="hidden" name="deleted_payment_ids" id="deleted_payment_ids" value="">';
            }
            
            // Show payment form fields (for new orders or adding payments)
            if(!$has_payments) {
                $html .= '<h4 style="font-size: 1.375rem !important;" class="font-bold text-gray-800 mb-4">Payment Details</h4>';
            }
            
            // Payment fields in a grid
            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
            
            // Payment Date
            $html .= '<div>';
            $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Payment Date</label>';
            $html .= '<input type="text" name="payment_date" id="payment_date" value="' . date('d/m/Y') . '" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 datepicker-dd-mm-yyyy" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" placeholder="dd/mm/yyyy" readonly>';
            $html .= '</div>';
            
            // Amount Paid
            $html .= '<div>';
            $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Amount Paid</label>';
            $html .= '<input type="number" name="payment_amount" id="payment_amount" min="0" step="0.01" placeholder="Enter amount" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3.5rem !important;">';
            $html .= '<p style="font-size: 1rem !important;" class="text-gray-600 mt-1">Leave blank or enter 0 for full amount</p>';
            $html .= '</div>';
            
            // Payment Method
            $html .= '<div>';
            $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Payment Method</label>';
            $html .= '<select name="payment_method_id" id="payment_method_id" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3.5rem !important;">';
            $html .= '<option value="">Select Payment Method</option>';
            foreach($payment_methods as $method) {
                $html .= '<option value="' . $method['id'] . '">' . htmlspecialchars($method['name']) . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
            
            // Reference Number
            $html .= '<div>';
            $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Reference Number</label>';
            $payment_reference = $latest_payment ? htmlspecialchars($latest_payment['reference_number'] ?? '') : '';
            $html .= '<input type="text" name="payment_reference" id="payment_reference" value="' . $payment_reference . '" placeholder="Optional" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important; min-height: 3.5rem !important;">';
            $html .= '</div>';
            
            $html .= '</div>'; // End grid
            
            // Payment Notes (full width)
            $html .= '<div class="mt-4">';
            $html .= '<label style="font-size: 1.25rem !important;" class="block font-semibold text-gray-700 mb-2">Payment Notes</label>';
            $payment_notes = $latest_payment ? htmlspecialchars($latest_payment['notes'] ?? '') : '';
            $html .= '<textarea name="payment_notes" id="payment_notes" rows="2" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important;" placeholder="Optional payment notes">' . $payment_notes . '</textarea>';
            $html .= '</div>';
            
            $html .= '</div>'; // End payment_fields
            $html .= '</div>'; // End payment section
            $html .= '</div>';
        
        // Notes
        $html .= '<div class="mb-6">';
        $html .= '<label style="font-size: 1.375rem !important;" class="block font-semibold text-gray-700 mb-3">Notes</label>';
        $html .= '<textarea name="notes" rows="4" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" style="font-size: 1.125rem !important;" placeholder="Add any additional notes or instructions...">' . ($purchase['notes'] ?? '') . '</textarea>';
        $html .= '</div>';
        
        // Buttons
        $html .= '<div class="flex justify-end gap-4 mt-6">';
        $html .= '<button type="button" onclick="closeModal()" class="px-6 py-3 font-semibold rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors" style="font-size: 1.125rem !important; min-height: 3.5rem !important;">Cancel</button>';
        $html .= '<button type="submit" class="px-6 py-3 font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors" style="font-size: 1.125rem !important; min-height: 3.5rem !important;"><i class="fa fa-save mr-2"></i> ' . ($id ? 'Update' : 'Create') . ' Purchase Order</button>';
        $html .= '</div>';
        
        $html .= form_close();
        $html .= '</div>';
        
        // JavaScript
        $html .= '<script>';
        $html .= 'var productsData = ' . json_encode($products) . ';';
        $html .= 'var suppliersData = ' . json_encode($suppliers) . ';';
        $html .= 'var currency = "' . $currency . '";';
        
        // Number formatting function with thousand separators
        $html .= '
        function formatCurrency(amount) {
            return amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
        }
        
        function closeModal() {
            // Close whichever modal is open (createModal or editModal)
            $("#createModal, #editModal").modal("hide");
            $(".modal-backdrop").remove();
            $("body").removeClass("modal-open").css("overflow", "");
        }
        ';
        
        // Supplier search functionality
        $html .= '
        var supplierSearchTimeout;
        
        $("#supplier_search_input").on("input", function() {
            const searchTerm = $(this).val().trim().toLowerCase();
            clearTimeout(supplierSearchTimeout);
            
            if(searchTerm.length < 1) {
                $("#supplier_search_results").hide().empty();
                $("#selected_supplier_id").val("");
                return;
            }
            
            supplierSearchTimeout = setTimeout(function() {
                const filteredSuppliers = suppliersData.filter(s => 
                    s.name.toLowerCase().includes(searchTerm) || 
                    (s.contact && s.contact.toLowerCase().includes(searchTerm))
                );
                
                if(filteredSuppliers.length > 0) {
                    let html = "";
                    filteredSuppliers.forEach(function(supplier) {
                        html += `
                            <div class="supplier-result-item p-4 border-b border-gray-200 hover:bg-blue-50 cursor-pointer transition-colors" 
                                 data-supplier-id="${supplier.id}" 
                                 data-supplier-name="${supplier.name}">
                                <div class="font-semibold text-gray-800 text-lg">${supplier.name}</div>
                                ${supplier.contact ? `<div class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i>${supplier.contact}</div>` : ""}
                            </div>
                        `;
                    });
                    $("#supplier_search_results").html(html).show();
                } else {
                    $("#supplier_search_results").html(`
                        <div class="p-4 text-center text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>No suppliers found
                        </div>
                    `).show();
                }
            }, 200);
        });
        
        $(document).on("click", ".supplier-result-item", function() {
            const supplierId = $(this).data("supplier-id");
            const supplierName = $(this).data("supplier-name");
            
            $("#selected_supplier_id").val(supplierId);
            $("#supplier_search_input").val(supplierName);
            $("#supplier_search_results").hide().empty();
        });
        
        $(document).on("click", function(e) {
            if(!$(e.target).closest("#supplier_search_input, #supplier_search_results").length) {
                $("#supplier_search_results").hide();
            }
        });
        
        $("#supplier_search_input").on("focus", function() {
            if($("#supplier_search_results").children().length > 0) {
                $("#supplier_search_results").show();
            }
        });
        
        // Set initial supplier name if editing
        ' . ($purchase ? '$("#supplier_search_input").val("' . addslashes($purchase['supplier_name']) . '");' : '') . '
        
        // Delete payment functionality
        var deletedPaymentIds = [];
        var totalPaidAfterDeletions = ' . ($has_payments ? $total_paid : 0) . ';
        
        $(document).on("click", ".delete-payment-btn", function() {
            var paymentId = $(this).data("payment-id");
            var paymentAmount = parseFloat($(this).data("payment-amount"));
            var row = $(this).closest("tr");
            
            showConfirmModal(
                "Delete Payment",
                "Are you sure you want to delete this payment record of <strong>" + currency + " " + formatCurrency(paymentAmount) + "</strong>?<br><br>This action will remove the payment from this purchase order.",
                function() {
                    // Add to deleted list
                    deletedPaymentIds.push(paymentId);
                    $("#deleted_payment_ids").val(deletedPaymentIds.join(","));
                    
                    // Update total paid
                    totalPaidAfterDeletions -= paymentAmount;
                    
                    // Remove row with animation
                    row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if($("#existing_payments_table tbody tr").length === 0) {
                            $("#existing_payments_table tbody").html("<tr><td colspan=\"5\" class=\"text-center py-4 text-gray-500\">No payments remaining</td></tr>");
                            
                            // Automatically uncheck "Record Payment Now" toggle since no payments exist
                            $("#record_payment_now").prop("checked", false).trigger("change");
                        }
                        
                        // Update payment status display
                        updatePaymentStatusDisplay();
                        
                        // Restore modal scrolling after confirm modal closes
                        setTimeout(function() {
                            if(!$("body").hasClass("modal-open")) {
                                $("body").addClass("modal-open");
                            }
                        }, 100);
                    });
                },
                "Delete",
                "danger"
            );
            
            // Also restore scroll if user cancels (clicks outside or closes)
            setTimeout(function() {
                if(!$("body").hasClass("modal-open") && ($("#createModal").hasClass("show") || $("#editModal").hasClass("show"))) {
                    $("body").addClass("modal-open");
                }
            }, 500);
        });
        
        // Function to update payment status display
        function updatePaymentStatusDisplay() {
            // Recalculate totals based on current items
            var subtotal = 0;
            $(".purchase-item-row").each(function() {
                var quantity = parseFloat($(this).find(".quantity-input").val()) || 0;
                var cost = parseFloat($(this).find(".cost-input").val()) || 0;
                subtotal += (quantity * cost);
            });
            
            var discountType = $("#discount_type").val();
            var discountValue = parseFloat($("#discount_value").val()) || 0;
            var discountAmount = 0;
            
            if(discountType === "percentage") {
                discountAmount = (subtotal * discountValue) / 100;
            } else if(discountType === "fixed") {
                discountAmount = discountValue;
            }
            
            var currentTotal = subtotal - discountAmount;
            var balance = currentTotal - totalPaidAfterDeletions;
            
            // Update displays
            $("#total_paid_display").text(formatCurrency(totalPaidAfterDeletions));
            $("#current_total_display").text(formatCurrency(currentTotal));
            $("#balance_display").text(formatCurrency(Math.max(0, balance)));
            
            if(balance > 0) {
                $("#balance_text").show();
            } else {
                $("#balance_text").hide();
            }
            
            // Update status
            var $statusAlert = $("#payment_status_alert");
            var $statusIcon = $("#payment_status_icon");
            var $statusText = $("#payment_status_text");
            
            if(totalPaidAfterDeletions >= currentTotal) {
                $statusAlert.removeClass("bg-yellow-50 border-yellow-300 text-yellow-800")
                    .addClass("bg-green-50 border-green-300 text-green-800");
                $statusIcon.removeClass("fa-exclamation-circle").addClass("fa-check-circle");
                $statusText.text("Fully Paid");
            } else {
                $statusAlert.removeClass("bg-green-50 border-green-300 text-green-800")
                    .addClass("bg-yellow-50 border-yellow-300 text-yellow-800");
                $statusIcon.removeClass("fa-check-circle").addClass("fa-exclamation-circle");
                $statusText.text("Partially Paid");
            }
        }
        
        // Recalculate payment status when totals change
        $(document).on("change", ".quantity-input, .cost-input, #discount_type, #discount_value", function() {
            if($("#payment_status_alert").length > 0) {
                setTimeout(updatePaymentStatusDisplay, 100);
            }
        });
        
        // Payment section toggle
        $("#record_payment_now").change(function() {
            if($(this).is(":checked")) {
                $("#payment_fields").slideDown();
                // Make payment fields required
                $("#payment_method_id").prop("required", true);
            } else {
                $("#payment_fields").slideUp();
                // Remove required attribute
                $("#payment_method_id").prop("required", false);
                // Clear payment fields
                $("#payment_amount").val("");
                $("#payment_method_id").val("");
                $("#payment_reference").val("");
                $("#payment_notes").val("");
            }
        });
        
        // Function to get selected product IDs
        function getSelectedProductIds() {
            var selectedIds = [];
            $(".product-select").each(function() {
                var val = $(this).val();
                if(val) selectedIds.push(val);
            });
            return selectedIds;
        }
        
        // Function to update product dropdowns
        function updateProductDropdowns() {
            var selectedIds = getSelectedProductIds();
            
            $(".product-select").each(function() {
                var currentSelect = $(this);
                var currentValue = currentSelect.val();
                
                // Destroy Select2 if exists
                if(currentSelect.data("select2")) {
                    currentSelect.select2("destroy");
                }
                
                // Rebuild options
                currentSelect.empty().append("<option value=\'\'>Select Product</option>");
                productsData.forEach(function(p) {
                    // Only add if not selected elsewhere OR if it\'s the current value
                    if(!selectedIds.includes(p.id.toString()) || p.id.toString() === currentValue) {
                        var stockInfo = p.quantity > 0 ? " (Stock: " + p.quantity + ")" : " (Out of Stock)";
                        var selected = p.id.toString() === currentValue ? " selected" : "";
                        currentSelect.append(`<option value="${p.id}" data-cost="${p.cost_price}" data-stock="${p.quantity}"${selected}>${p.name} (${p.sku})${stockInfo}</option>`);
                    }
                });
                
                // Reinitialize Select2
                currentSelect.select2({
                    placeholder: "Search products...",
                    allowClear: true,
                    width: "100%",
                    dropdownParent: $("#createModal")
                });
            });
            
            // Check if "Add Item" button should be enabled
            checkAddItemButton();
        }
        
        // Function to check if Add Item button should be enabled
        function checkAddItemButton() {
            var rows = $("#purchase_items_tbody tr.purchase-item-row");
            
            // If there are no item rows, always enable (fresh modal state)
            if(rows.length === 0) {
                $("#add_item_btn").prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");
                return;
            }
            
            // If there are rows, check the last one
            var lastRow = rows.last();
            var lastSelect = lastRow.find(".product-select");
            var selectValue = lastSelect.val();
            
            if(!selectValue || selectValue === "" || selectValue === null) {
                // Last row has no product selected - disable button
                $("#add_item_btn").prop("disabled", true).addClass("opacity-50 cursor-not-allowed");
            } else {
                // Last row has product selected - enable button
                $("#add_item_btn").prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");
            }
        }
        
        function addPurchaseItem() {
            var tbody = $("#purchase_items_tbody");
            if(tbody.find("td[colspan]").length) {
                tbody.empty();
            }
            
            // Get already selected product IDs
            var selectedIds = getSelectedProductIds();
            
            // Build options excluding already selected products
            var optionsHtml = "<option value=\'\'>Select Product</option>";
            productsData.forEach(function(p) {
                if(!selectedIds.includes(p.id.toString())) {
                    var stockInfo = p.quantity > 0 ? " (Stock: " + p.quantity + ")" : " (Out of Stock)";
                    optionsHtml += `<option value="${p.id}" data-cost="${p.cost_price}" data-stock="${p.quantity}">${p.name} (${p.sku})${stockInfo}</option>`;
                }
            });
            
            var row = `
                <tr class="purchase-item-row hover:bg-blue-50 transition-colors">
                    <td class="px-6 py-4">
                        <select name="items[product_id][]" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-select" required style="font-size: 1.125rem !important; min-height: 3.5rem !important;">
                            ${optionsHtml}
                        </select>
                    </td>
                    <td class="px-6 py-4"><input type="number" name="items[quantity][]" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 quantity-input" min="1" value="1" required onchange="calculateRowTotal(this)" style="font-size: 1.125rem !important; min-height: 3.5rem !important;"></td>
                    <td class="px-6 py-4"><input type="number" name="items[cost_price][]" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 cost-input" min="0" step="0.01" required onchange="calculateRowTotal(this)" style="font-size: 1.125rem !important; min-height: 3.5rem !important;"></td>
                    <td class="px-6 py-4 row-total font-semibold text-gray-900" style="font-size: 1.125rem !important;"><sup style="font-size: 0.6em; vertical-align: super;">${currency}</sup> 0.00</td>
                    <td class="px-6 py-4 text-center"><button type="button" class="px-3 py-2 font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                </tr>
            `;
            tbody.append(row);
            
            // Initialize Select2 on new select
            var newSelect = tbody.find("tr:last .product-select");
            newSelect.select2({
                placeholder: "Search products...",
                allowClear: true,
                width: "100%",
                dropdownParent: $("#createModal")
            });
            
            // Attach change handler
            newSelect.on("change", function() {
                if($(this).val()) {
                    updateProductInfo(this);
                    updateProductDropdowns();
                    // Enable Add button
                    $("#add_item_btn").prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");
                }
            });
            
            // Disable Add Item button until product is selected
            $("#add_item_btn").prop("disabled", true).addClass("opacity-50 cursor-not-allowed");
        }
        
        function updateProductInfo(select) {
            var row = $(select).closest("tr");
            var selectedOption = $(select).find("option:selected");
            var costPrice = selectedOption.data("cost");
            
            row.find(".cost-input").val(costPrice);
            calculateRowTotal(row.find(".quantity-input")[0]);
        }
        
        function calculateRowTotal(input) {
            var row = $(input).closest("tr");
            var quantity = parseFloat(row.find(".quantity-input").val()) || 0;
            var cost = parseFloat(row.find(".cost-input").val()) || 0;
            var total = quantity * cost;
            
            row.find(".row-total").html("<sup style=\"font-size: 0.6em; vertical-align: super;\">" + currency + "</sup> " + formatCurrency(total));
            calculateGrandTotal();
        }
        
        function calculateGrandTotal() {
            var subtotal = 0;
            $(".purchase-item-row").each(function() {
                var quantity = parseFloat($(this).find(".quantity-input").val()) || 0;
                var cost = parseFloat($(this).find(".cost-input").val()) || 0;
                subtotal += (quantity * cost);
            });
            
            // Update subtotal display with formatted number
            $("#subtotal_display").html("<sup style=\"font-size: 0.6em; vertical-align: super;\">" + currency + "</sup> " + formatCurrency(subtotal));
            
            // Calculate discount
            calculateDiscount();
        }
        
        function calculateDiscount() {
            var subtotal = 0;
            $(".purchase-item-row").each(function() {
                var quantity = parseFloat($(this).find(".quantity-input").val()) || 0;
                var cost = parseFloat($(this).find(".cost-input").val()) || 0;
                subtotal += (quantity * cost);
            });
            
            var discountType = $("#discount_type").val();
            var discountValue = parseFloat($("#discount_value").val()) || 0;
            var discountAmount = 0;
            
            if(discountType === "percentage") {
                // Percentage discount
                if(discountValue > 100) {
                    showAjaxModal_alert("Percentage discount cannot exceed 100%", "warning");
                    $("#discount_value").val(100);
                    discountValue = 100;
                }
                discountAmount = (subtotal * discountValue) / 100;
            } else if(discountType === "fixed") {
                // Fixed amount discount
                if(discountValue > subtotal) {
                    showAjaxModal_alert("Discount amount cannot exceed subtotal", "warning");
                    $("#discount_value").val(subtotal.toFixed(2));
                    discountValue = subtotal;
                }
                discountAmount = discountValue;
            }
            
            var finalTotal = subtotal - discountAmount;
            
            // Update displays with formatted numbers
            $("#discount_amount_display").html("- <sup style=\"font-size: 0.6em; vertical-align: super;\">" + currency + "</sup> " + formatCurrency(discountAmount));
            $("#total_amount_display").html("<sup style=\"font-size: 0.6em; vertical-align: super;\">" + currency + "</sup> " + formatCurrency(finalTotal));
            
            // Show/hide discount input based on type selection
            if(discountType === "") {
                $("#discount_value").prop("disabled", true).val(0);
            } else {
                $("#discount_value").prop("disabled", false);
            }
        }
        
        // Initialize discount field state
        $(document).ready(function() {
            var discountType = $("#discount_type").val();
            if(discountType === "" || discountType === null) {
                $("#discount_value").prop("disabled", true);
            } else {
                $("#discount_value").prop("disabled", false);
            }
            
            $("#discount_type").change(function() {
                if($(this).val() === "") {
                    $("#discount_value").val(0);
                }
                calculateDiscount();
            });
        });
        
        // Initialize datepickers for date fields
        $("#po_created_date, #po_expected_date").datepicker({
            format: "dd/mm/yyyy",
            autoclose: true,
            todayHighlight: true,
            orientation: "bottom auto"
        });
        
        function removeRow(btn) {
            $(btn).closest("tr").remove();
            if($("#purchase_items_tbody tr").length === 0) {
                $("#purchase_items_tbody").html("<tr><td colspan=\"5\" style=\"font-size: 1.125rem !important;\" class=\"px-6 py-10 text-center text-gray-500\">No items added yet</td></tr>");
                $("#add_item_btn").prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");
            } else {
                updateProductDropdowns();
            }
            calculateGrandTotal();
        }
        
        $("#purchase_order_form").submit(function(e) {
            e.preventDefault();
            
            if($("#purchase_items_tbody").find(".purchase-item-row").length === 0) {
                showAjaxModal_alert("Please add at least one item to the purchase order", "warning");
                // Restore modal scrolling after alert closes
                setTimeout(function() {
                    if(!$("body").hasClass("modal-open")) {
                        $("body").addClass("modal-open");
                    }
                }, 100);
                return false;
            }
            
            if(!$("#selected_supplier_id").val()) {
                showAjaxModal_alert("Please select a supplier", "warning");
                // Restore modal scrolling after alert closes
                setTimeout(function() {
                    if(!$("body").hasClass("modal-open")) {
                        $("body").addClass("modal-open");
                    }
                }, 100);
                $("#supplier_search_input").focus();
                return false;
            }
            
            // Calculate current totals
            var subtotal = 0;
            $(".purchase-item-row").each(function() {
                var quantity = parseFloat($(this).find(".quantity-input").val()) || 0;
                var cost = parseFloat($(this).find(".cost-input").val()) || 0;
                subtotal += (quantity * cost);
            });
            
            var discountType = $("#discount_type").val();
            var discountValue = parseFloat($("#discount_value").val()) || 0;
            var discountAmount = 0;
            
            if(discountType === "percentage") {
                discountAmount = (subtotal * discountValue) / 100;
            } else if(discountType === "fixed") {
                discountAmount = discountValue;
            }
            
            var finalTotal = subtotal - discountAmount;
            
            // Calculate total including new payment if being recorded
            var totalPaymentIncludingNew = totalPaidAfterDeletions;
            if($("#record_payment_now").is(":checked")) {
                var newPaymentAmount = parseFloat($("#payment_amount").val()) || 0;
                totalPaymentIncludingNew += newPaymentAmount;
            }
            
            // Check for payment discrepancies when editing with existing payments
            if($("#payment_status_alert").length > 0 && deletedPaymentIds.length === 0) {
                // Has existing payments and none deleted
                var balance = finalTotal - totalPaymentIncludingNew;
                
                if(Math.abs(balance) > 0.01) { // Allow for small rounding differences
                    var confirmTitle = "";
                    var confirmMessage = "";
                    var iconType = "";
                    
                    if(balance > 0) {
                        // Underpayment
                        confirmTitle = "Underpayment Detected";
                        confirmMessage = "The new order total is <strong>" + currency + " " + formatCurrency(finalTotal) + 
                            "</strong>, but only <strong>" + currency + " " + formatCurrency(totalPaymentIncludingNew) + "</strong> has been paid.<br><br>" +
                            "<div style=\"background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 12px 0; border-radius: 4px;\">" +
                            "<strong>Outstanding Balance:</strong> " + currency + " " + formatCurrency(balance) + "</div>" +
                            "Do you want to continue? You can record additional payments later.";
                        iconType = "warning";
                    } else {
                        // Overpayment
                        var overpayment = Math.abs(balance);
                        confirmTitle = "Overpayment Warning";
                        confirmMessage = "<strong>WARNING:</strong> The total paid (<strong>" + currency + " " + formatCurrency(totalPaymentIncludingNew) + 
                            "</strong>) exceeds the new order total (<strong>" + currency + " " + formatCurrency(finalTotal) + "</strong>).<br><br>" +
                            "<div style=\"background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 12px 0; border-radius: 4px;\">" +
                            "<strong>Overpayment:</strong> " + currency + " " + formatCurrency(overpayment) + "</div>" +
                            "This creates an overpayment situation.<br><br>" +
                            "Do you want to continue? You may need to process a refund or credit.";
                        iconType = "danger";
                    }
                    
                    showConfirmModal(
                        confirmTitle,
                        confirmMessage,
                        function() {
                            // User confirmed, proceed with submission
                            submitPurchaseOrderForm(finalTotal);
                            
                            // Restore modal scrolling after confirm modal closes
                            setTimeout(function() {
                                if(!$("body").hasClass("modal-open")) {
                                    $("body").addClass("modal-open");
                                }
                            }, 100);
                        },
                        "Continue",
                        iconType
                    );
                    
                    // Also restore scroll if user cancels (clicks outside or closes)
                    setTimeout(function() {
                        if(!$("body").hasClass("modal-open") && ($("#createModal").hasClass("show") || $("#editModal").hasClass("show"))) {
                            $("body").addClass("modal-open");
                        }
                    }, 500);
                    return false;
                }
            }
            
            // No discrepancy, validate and submit
            submitPurchaseOrderForm(finalTotal);
        });
        
        function submitPurchaseOrderForm(finalTotal) {
            // Validate payment fields if payment checkbox is checked AND payment fields are visible
            if($("#record_payment_now").is(":checked") && $("#payment_fields").is(":visible")) {
                // Only validate if user is actually trying to enter a new payment
                var hasPaymentMethod = $("#payment_method_id").val();
                var hasPaymentAmount = parseFloat($("#payment_amount").val()) || 0;
                
                // If any payment field has a value, validate all required fields
                if(hasPaymentMethod || hasPaymentAmount > 0) {
                    if(!hasPaymentMethod) {
                        showAjaxModal_alert("Please select a payment method", "warning");
                        // Restore modal scrolling after alert closes
                        setTimeout(function() {
                            if(!$("body").hasClass("modal-open")) {
                                $("body").addClass("modal-open");
                            }
                        }, 100);
                        $("#payment_method_id").focus();
                        return false;
                    }
                    
                    var paymentAmount = hasPaymentAmount;
                    
                    // If payment amount is 0 or empty, set it to final total (after discount)
                    if(paymentAmount === 0) {
                        $("#payment_amount").val(finalTotal.toFixed(2));
                        paymentAmount = finalTotal;
                    }
                    
                    // Validate payment amount doesn\'t exceed final total
                    if(paymentAmount > finalTotal) {
                        showAjaxModal_alert("Payment amount cannot exceed total purchase amount of " + currency + " " + formatCurrency(finalTotal), "warning");
                        // Restore modal scrolling after alert closes
                        setTimeout(function() {
                            if(!$("body").hasClass("modal-open")) {
                                $("body").addClass("modal-open");
                            }
                        }, 100);
                        $("#payment_amount").focus();
                        return false;
                    }
                    
                    // Validate payment date is not in future
                    var paymentDate = new Date($("#payment_date").val());
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if(paymentDate > today) {
                        showAjaxModal_alert("Payment date cannot be in the future", "warning");
                        // Restore modal scrolling after alert closes
                        setTimeout(function() {
                            if(!$("body").hasClass("modal-open")) {
                                $("body").addClass("modal-open");
                            }
                        }, 100);
                        $("#payment_date").focus();
                        return false;
                    }
                }
            }
            
            $("#createModal").modal("hide");
            showAjaxModal_alert("Processing purchase order...", "loading");
            
            $.ajax({
                url: $("#purchase_order_form").attr("action"),
                type: "POST",
                data: new FormData($("#purchase_order_form")[0]),
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json"
            }).done(function(response) {
                if(response.status === "success") {
                    // Close whichever modal is open (createModal or editModal)
                    $("#createModal, #editModal").modal("hide");
                    $(".modal-backdrop").remove();
                    $("body").removeClass("modal-open").css("overflow", "");
                    
                    // Show success message
                    showAjaxModal_alert(response.message, "success", false);
                    
                    // Reload the purchase orders table
                    setTimeout(function() {
                        if(typeof window.loadPurchaseOrders === "function") {
                            window.loadPurchaseOrders();
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    // On error, restore modal scrolling
                    $("#createModal, #editModal").modal("show");
                    $("body").removeClass("modal-open");
                    $("body").css("overflow", "");
                    setTimeout(function() {
                        $("body").addClass("modal-open");
                    }, 100);
                    showAjaxModal_alert(response.message, "error");
                }
            }).fail(function() {
                // On failure, restore modal scrolling
                $("#createModal").modal("show");
                $("body").removeClass("modal-open");
                $("body").css("overflow", "");
                setTimeout(function() {
                    $("body").addClass("modal-open");
                }, 100);
                showAjaxModal_alert("An error occurred", "error");
            });
        }
        
        // Initialize calculation for existing items
        $(document).ready(function() {
            // Initialize Select2 on existing product selects
            $(".product-select").each(function() {
                var $select = $(this);
                $select.select2({
                    placeholder: "Search products...",
                    allowClear: true,
                    width: "100%",
                    dropdownParent: $("#createModal")
                });
                
                $select.on("change", function() {
                    if($(this).val()) {
                        updateProductInfo(this);
                        updateProductDropdowns();
                    }
                });
            });
            
            // Trigger initial calculation after a short delay to ensure DOM is ready
            setTimeout(function() {
                calculateGrandTotal();
                updateProductDropdowns();
                
                // ALWAYS enable Add Item button when modal opens
                $("#add_item_btn").prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");
            }, 100);
        });
        </script>';
        
        echo $html;
    }
    
    /**
     * Helper function to generate purchase item row HTML
     */
    private function generate_purchase_item_row($products, $item, $currency) {
        $html = '<tr class="purchase-item-row hover:bg-blue-50 transition-colors">';
        $html .= '<td class="px-6 py-4">';
        $html .= '<select name="items[product_id][]" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-select" required onchange="updateProductInfo(this)" style="font-size: 1.125rem !important; min-height: 3.5rem !important;">';
        foreach($products as $product) {
            $selected = ($item['product_id'] == $product['id']) ? 'selected' : '';
            $html .= '<option value="' . $product['id'] . '" data-cost="' . $product['cost_price'] . '" data-stock="' . $product['quantity'] . '" ' . $selected . '>' . htmlspecialchars($product['name']) . ' (' . $product['sku'] . ')</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        $html .= '<td class="px-6 py-4"><input type="number" name="items[quantity][]" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 quantity-input" min="1" value="' . $item['quantity'] . '" required onchange="calculateRowTotal(this)" style="font-size: 1.125rem !important; min-height: 3.5rem !important;"></td>';
        $html .= '<td class="px-6 py-4"><input type="number" name="items[cost_price][]" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 cost-input" min="0" step="0.01" value="' . $item['cost_price'] . '" required onchange="calculateRowTotal(this)" style="font-size: 1.125rem !important; min-height: 3.5rem !important;"></td>';
        $html .= '<td class="px-6 py-4 row-total font-semibold text-gray-900" style="font-size: 1.125rem !important;"><sup style="font-size: 0.6em; vertical-align: super;">' . $currency . '</sup> ' . number_format($item['quantity'] * $item['cost_price'], 2) . '</td>';
        $html .= '<td class="px-6 py-4 text-center"><button type="button" class="px-3 py-2 font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>';
        $html .= '</tr>';
        return $html;
    }
    
    /**
     * Recalculate and update payment status for a purchase order
     * Called after payment additions or deletions
     */
    private function recalculate_purchase_order_payment_status($purchase_id) {
        // Get total amount
        $purchase = $this->db->select('total_amount')->where('id', $purchase_id)->get('inventory_purchases')->row();
        
        if(!$purchase) {
            return false;
        }
        
        $total_amount = floatval($purchase->total_amount);
        
        // Calculate total paid from payments table
        $this->db->select_sum('amount');
        $this->db->where('purchase_id', $purchase_id);
        $payments_result = $this->db->get('inventory_purchase_payments')->row();
        $amount_paid = floatval($payments_result->amount ?? 0);
        
        // Determine payment status
        $payment_status = 'unpaid';
        if($amount_paid > 0) {
            if($amount_paid >= $total_amount) {
                $payment_status = 'fully_paid';
            } else {
                $payment_status = 'partially_paid';
            }
        }
        
        // Update purchase order
        $this->db->where('id', $purchase_id);
        $this->db->update('inventory_purchases', [
            'amount_paid' => $amount_paid,
            'payment_status' => $payment_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        log_message('debug', 'Recalculated payment status for PO ' . $purchase_id . ': ' . $payment_status . ' (paid: ' . $amount_paid . ' of ' . $total_amount . ')');
        
        return true;
    }
    
    // ============================================
    // SALES RETURNS & REFUNDS CONTROLLER METHODS
    // ============================================
    
    /**
     * Returns management interface
     * Access: Admin levels 1, 2, 3, 6
     */
    public function returns() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            show_error('Access Denied: You do not have permission to process returns.');
        }
        
        $page_data['page_name'] = 'inventory/returns';
        $page_data['page_title'] = get_phrase('sales_returns');
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Return form modal
     * Returns HTML form for processing returns
     */
    public function return_form() {
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        $html = '
        <link href="' . base_url('assets/cdn/css/select2-4.1.0.min.css') . '" rel="stylesheet" />
        <script src="' . base_url('assets/cdn/js/select2-4.1.0.min.js') . '"></script>
        <style>
        /* Ensure modal body scrolls properly */
        #returnModal .modal-body, 
        #createModal .modal-body { 
            max-height: calc(100vh - 200px); 
            overflow-y: auto !important; 
        }
        .return-form-modern { 
            font-size: 1.25rem !important; 
            padding-bottom: 20px; 
        }
        .search-card { 
            background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%); 
            border-radius: 12px; 
            padding: 28px; 
            margin-bottom: 24px; 
            border: 2px solid #e2e8f0;
        }
        .search-field { 
            height: 60px !important; 
            font-size: 1.25rem !important; 
            border: 2px solid #d1d5db !important; 
            border-radius: 8px !important;
            padding: 0.75rem 1rem !important;
        }
        .search-field:focus { 
            border-color: #3b82f6 !important; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        .search-btn { 
            height: 60px !important; 
            font-size: 1.25rem !important; 
            font-weight: 600 !important;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 0 2rem !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
        }
        .search-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3) !important;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
        }
        .step-header { 
            font-size: 1.75rem !important; 
            font-weight: 700; 
            color: #1e293b; 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .step-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem !important;
            font-weight: 700;
        }
        .info-card {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .info-label {
            font-size: 1rem !important;
            font-weight: 600;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 1.375rem !important;
            font-weight: 700;
            color: #1e293b;
        }
        .return-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .return-table thead {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white !important;
        }
        .return-table thead th {
            font-size: 1.25rem !important;
            font-weight: 700;
            padding: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white !important;
        }
        .return-table tbody td {
            padding: 1.25rem;
            font-size: 1.25rem !important;
            vertical-align: middle;
        }
        .return-table tfoot {
            background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);
            font-weight: 700;
            font-size: 1.375rem !important;
        }
        .return-table tfoot td {
            padding: 1.25rem;
            font-size: 1.375rem !important;
        }
        .form-group-modern label {
            font-size: 1.125rem !important;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: block;
        }
        .form-control-modern {
            height: 60px;
            font-size: 1.25rem !important;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .form-control-modern:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-modern {
            height: 60px;
            font-size: 1.25rem !important;
            font-weight: 600;
            border-radius: 8px;
            padding: 0 2rem;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-back {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-back:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }
        .btn-process {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .btn-process:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }
        </style>
        
        <div class="return-form-modern p-4">';
        
        // Step 1: Search for sale
        $html .= '<div id="step1_search">';
        $html .= '<div class="step-header">';
        $html .= '<div class="step-badge">1</div>';
        $html .= '<span>Find Original Sale</span>';
        $html .= '</div>';
        
        $html .= '<div class="search-card">';
        $html .= '<div class="row">';
        
        // Order ID field
        $html .= '<div class="col-md-4 mb-3 mb-md-0">';
        $html .= '<label style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Order ID <span class="text-muted" style="font-size: 1rem;">(Optional)</span></label>';
        $html .= '<input type="text" id="search_sale_id" placeholder="Enter Order ID..." class="form-control search-field">';
        $html .= '</div>';
        
        // Customer Name field
        $html .= '<div class="col-md-5 mb-3 mb-md-0">';
        $html .= '<label style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Customer Name <span class="text-muted" style="font-size: 1rem;">(Optional)</span></label>';
        $html .= '<input type="text" id="search_customer_name" placeholder="Search by customer name..." class="form-control search-field">';
        $html .= '</div>';
        
        // Search button - empty label to align with input fields
        $html .= '<div class="col-md-3 mb-3 mb-md-0">';
        $html .= '<label style="margin-bottom: 8px; display: block; visibility: hidden;">Search</label>';
        $html .= '<button type="button" onclick="searchSalesForReturn()" class="btn search-btn w-100"><i class="fa fa-search mr-2"></i>Search</button>';
        $html .= '</div>';
        
        $html .= '</div>'; // row
        
        // Helpful hint
        $html .= '<div class="mt-3" style="font-size: 1.125rem; color: #6b7280;">';
        $html .= '<i class="fa fa-info-circle mr-1"></i>';
        $html .= '<span>Enter either <strong>Order ID</strong> or <strong>Customer Name</strong> to search</span>';
        $html .= '</div>';
        
        $html .= '</div>'; // search-card
        
        // Search results table
        $html .= '<div id="search_results" style="display: none;">';
        $html .= '<h5 class="mb-3" style="font-size: 1.5rem; font-weight: 700; color: #1e293b;"><i class="fa fa-list-alt mr-2 text-blue-600"></i>Search Results</h5>';
        $html .= '<div class="table-responsive return-table">';
        $html .= '<table class="table table-hover mb-0">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Order ID</th>';
        $html .= '<th>Date</th>';
        $html .= '<th>Customer</th>';
        $html .= '<th class="text-right">Amount</th>';
        $html .= '<th class="text-center">Action</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody id="search_results_tbody">';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // step1_search
        
        // Step 2: Select items to return
        $html .= '<div id="step2_items" style="display: none;">';
        $html .= '<div class="step-header">';
        $html .= '<div class="step-badge">2</div>';
        $html .= '<span>Select Items to Return</span>';
        $html .= '</div>';
        
        // Sale information card
        $html .= '<div class="info-card">';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-3 col-sm-6 mb-3 mb-md-0">';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-label">Order ID</span>';
        $html .= '<span class="info-value">#<span id="selected_sale_id"></span></span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-3 col-sm-6 mb-3 mb-md-0">';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-label">Customer</span>';
        $html .= '<span class="info-value" id="selected_customer"></span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-3 col-sm-6 mb-3 mb-md-0">';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-label">Date</span>';
        $html .= '<span class="info-value" id="selected_sale_date"></span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-3 col-sm-6">';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-label">Total Amount</span>';
        $html .= '<span class="info-value" id="selected_total"></span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Items table
        $html .= '<div class="table-responsive return-table mb-4">';
        $html .= '<table class="table mb-0">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="text-center" style="width: 50px;"><input type="checkbox" id="select_all_items" onchange="toggleAllItems()" style="width: 20px; height: 20px; cursor: pointer;"></th>';
        $html .= '<th>Product</th>';
        $html .= '<th class="text-center">Sold</th>';
        $html .= '<th class="text-center">Returned</th>';
        $html .= '<th class="text-center">Available</th>';
        $html .= '<th class="text-center">Return Qty</th>';
        $html .= '<th class="text-right">Unit Price</th>';
        $html .= '<th class="text-right">Refund</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody id="return_items_tbody">';
        $html .= '</tbody>';
        $html .= '<tfoot>';
        $html .= '<tr>';
        $html .= '<td colspan="5" class="text-right">Total Items:</td>';
        $html .= '<td class="text-center" id="total_items_count" style="font-size: 1.25rem;">0</td>';
        $html .= '<td class="text-right">Total Refund:</td>';
        $html .= '<td class="text-right" id="total_refund_display" style="font-size: 1.25rem; color: #10b981;"><sup style="font-size: 0.6em;">' . $currency . '</sup> 0.00</td>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Return details
        $html .= '<div class="row mb-4">';
        $html .= '<div class="col-md-6 form-group-modern">';
        $html .= '<label>Return Reason <span class="text-danger">*</span></label>';
        $html .= '<select id="return_reason" required class="form-control form-control-modern">';
        $html .= '<option value="">Select Reason</option>';
        $html .= '<option value="Defective">Defective/Damaged</option>';
        $html .= '<option value="Wrong item">Wrong Item</option>';
        $html .= '<option value="Changed mind">Changed Mind</option>';
        $html .= '<option value="Expired">Expired Product</option>';
        $html .= '<option value="Other">Other</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '<div class="col-md-6 form-group-modern">';
        $html .= '<label>Refund Method <span class="text-danger">*</span></label>';
        $html .= '<select id="refund_method" required class="form-control form-control-modern">';
        $html .= '<option value="">Select Method</option>';
        $html .= '<option value="1">Cash</option>';
        $html .= '<option value="2">Mobile Money</option>';
        $html .= '<option value="3">Bank Transfer</option>';
        $html .= '<option value="4">Store Credit</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="form-group-modern mb-4">';
        $html .= '<label>Notes</label>';
        $html .= '<textarea id="return_notes" rows="3" class="form-control" style="font-size: 1.25rem; border: 2px solid #d1d5db; border-radius: 8px; padding: 0.75rem 1rem;" placeholder="Additional notes about this return..."></textarea>';
        $html .= '</div>';
        
        // Buttons
        $html .= '<div class="d-flex justify-content-end gap-3 mt-4">';
        $html .= '<button type="button" onclick="backToSearch()" class="btn btn-modern btn-back"><i class="fa fa-arrow-left mr-2"></i>Back</button>';
        $html .= '<button type="button" onclick="confirmReturn()" class="btn btn-modern btn-process"><i class="fa fa-check mr-2"></i>Process Return</button>';
        $html .= '</div>';
        
        $html .= '</div>'; // step2_items
        
        $html .= '</div>'; // return-form-modern
        
        // Add JavaScript for return processing
        $html .= '<script>
// Use window scope to avoid redeclaration errors when modal reopens
if (typeof window.currentSaleData === "undefined") {
    window.currentSaleData = null;
}
if (typeof window.selectedItems === "undefined") {
    window.selectedItems = [];
}

// Declare function in window scope
window.searchSalesForReturn = function() {
    console.log("Search button clicked!"); // Debug log
    
    const saleId = $("#search_sale_id").val().trim();
    const customerName = $("#search_customer_name").val().trim();
    
    console.log("Sale ID:", saleId, "Customer Name:", customerName); // Debug log
    
    if(!saleId && !customerName) {
        showAjaxModal_alert("Please enter either Order ID or Customer Name to search", "warning");
        return;
    }
    
    console.log("Sending AJAX request..."); // Debug log
    
    $.ajax({
        url: "' . site_url('inventory/search_sales_for_return') . '",
        type: "GET",
        data: {
            sale_id: saleId,
            customer_name: customerName
        },
        dataType: "json",
        success: function(response) {
            console.log("Response received:", response); // Debug log
            if(response.status === "success") {
                renderSearchResults(response.sales);
            } else {
                showAjaxModal_alert(response.message, "error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error); // Debug log
            showAjaxModal_alert("Error searching for sales", "error");
        }
    });
};

window.renderSearchResults = function(sales) {
    const currency = "' . $currency . '";
    let html = "";
    
    if(sales.length === 0) {
        html = "<tr><td colspan=\\"5\\" class=\\"text-center py-5 text-gray-500\\"><i class=\\"fa fa-info-circle mr-2\\"></i>No sales found matching your criteria</td></tr>";
    } else {
        sales.forEach(sale => {
            html += `
                <tr class="hover:bg-blue-50 transition-colors">
                    <td class="px-4 py-3 font-semibold">#${sale.id}</td>
                    <td class="px-4 py-3">${new Date(sale.sale_date).toLocaleDateString("en-GB")}</td>
                    <td class="px-4 py-3">${sale.customer_name || "Walk-in Customer"}</td>
                    <td class="px-4 py-3 text-right font-semibold"><sup style="font-size:0.6em;">${currency}</sup> ${parseFloat(sale.total_amount).toFixed(2)}</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="selectSaleForReturn(${sale.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold" style="min-height: 40px;">
                            <i class="fa fa-check mr-1"></i>Select
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    $("#search_results_tbody").html(html);
    $("#search_results").slideDown(300);
};

window.selectSaleForReturn = function(saleId) {
    $.ajax({
        url: "' . site_url('inventory/get_sale_for_return/') . '" + saleId,
        type: "GET",
        dataType: "json",
        success: function(response) {
            if(response.status === "success") {
                window.currentSaleData = response.sale;
                window.showStep2();
            } else {
                showAjaxModal_alert(response.message, "error");
            }
        },
        error: function() {
            showAjaxModal_alert("Error loading sale details", "error");
        }
    });
};

window.showStep2 = function() {
    const currency = "' . $currency . '";
    
    // Hide step 1, show step 2
    $("#step1_search").hide();
    $("#step2_items").show();
    
    // Populate sale information
    $("#selected_sale_id").text(window.currentSaleData.id);
    $("#selected_customer").text(window.currentSaleData.customer_name || "Walk-in Customer");
    $("#selected_sale_date").text(new Date(window.currentSaleData.sale_date).toLocaleDateString("en-GB"));
    $("#selected_total").html("<sup style=\\"font-size:0.7em;\\">" + currency + "</sup> " + parseFloat(window.currentSaleData.total_amount).toFixed(2));
    
    // Populate items table
    let html = "";
    window.currentSaleData.items.forEach((item, index) => {
        const returnedQty = item.returned_quantity || 0;
        const available = item.quantity - returnedQty;
        html += `
            <tr data-item-index="${index}">
                <td class="text-center">
                    <input type="checkbox" class="item-checkbox" data-index="${index}" 
                           ${available <= 0 ? "disabled" : ""} 
                           onchange="window.updateReturnCalculation()">
                </td>
                <td>${item.product_name}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-center">${returnedQty}</td>
                <td class="text-center ${available <= 0 ? "text-red-600" : "text-green-600"}">${available}</td>
                <td class="text-center">
                    <input type="number" class="return-qty form-control" data-index="${index}" 
                           min="0" max="${available}" value="0" 
                           ${available <= 0 ? "disabled" : ""}
                           style="width: 100px; font-size: 1.25rem; display: inline-block; height: 48px; padding: 0.5rem;" 
                           oninput="window.updateReturnCalculation()">
                </td>
                <td class="text-right"><sup style="font-size:0.7em;">${currency}</sup> ${parseFloat(item.unit_price).toFixed(2)}</td>
                <td class="text-right refund-amount"><sup style="font-size:0.7em;">${currency}</sup> 0.00</td>
            </tr>
        `;
    });
    
    $("#return_items_tbody").html(html);
    window.selectedItems = [];
    window.updateReturnCalculation();
};

window.toggleAllItems = function() {
    const checked = $("#select_all_items").is(":checked");
    $(".item-checkbox:not(:disabled)").prop("checked", checked);
    
    if(checked) {
        $(".item-checkbox:checked").each(function() {
            const index = $(this).data("index");
            const row = $(`tr[data-item-index="${index}"]`);
            const maxQty = row.find(".return-qty").attr("max");
            row.find(".return-qty").val(maxQty);
        });
    } else {
        $(".return-qty").val(0);
    }
    
    window.updateReturnCalculation();
};

window.updateReturnCalculation = function() {
    const currency = "' . $currency . '";
    window.selectedItems = [];
    let totalItems = 0;
    let totalRefund = 0;
    
    // Loop through all rows to calculate
    $("tr[data-item-index]").each(function() {
        const index = $(this).data("item-index");
        const row = $(this);
        const checkbox = row.find(".item-checkbox");
        const qtyInput = row.find(".return-qty");
        const qty = parseFloat(qtyInput.val()) || 0;
        
        if(!window.currentSaleData || !window.currentSaleData.items[index]) {
            return true; // continue
        }
        
        const unitPrice = parseFloat(window.currentSaleData.items[index].unit_price) || 0;
        const refund = qty * unitPrice;
        
        // Update refund display for this row
        if(qty > 0) {
            row.find(".refund-amount").html("<sup style=\\"font-size:0.7em;\\">" + currency + "</sup> " + refund.toFixed(2));
            
            // Auto-check checkbox if quantity entered
            if(!checkbox.is(":checked")) {
                checkbox.prop("checked", true);
            }
            
            // Add to selected items
            window.selectedItems.push({
                sale_item_id: window.currentSaleData.items[index].sale_item_id || window.currentSaleData.items[index].id,
                product_id: window.currentSaleData.items[index].product_id,
                quantity: qty,
                unit_price: unitPrice
            });
            
            totalItems += qty;
            totalRefund += refund;
        } else {
            row.find(".refund-amount").html("<sup style=\\"font-size:0.7em;\\">" + currency + "</sup> 0.00");
            // Uncheck if quantity is 0
            checkbox.prop("checked", false);
        }
    });
    
    $("#total_items_count").text(totalItems);
    $("#total_refund_display").html("<sup style=\\"font-size:0.7em;\\">" + currency + "</sup> " + totalRefund.toFixed(2));
};

window.backToSearch = function() {
    $("#step2_items").hide();
    $("#step1_search").show();
    window.currentSaleData = null;
    window.selectedItems = [];
};

window.confirmReturn = function() {
    if(window.selectedItems.length === 0) {
        showAjaxModal_alert("Please select items to return", "error");
        return;
    }
    
    const reason = $("#return_reason").val();
    const refundMethod = $("#refund_method").val();
    const notes = $("#return_notes").val();
    
    if(!reason) {
        showAjaxModal_alert("Please select a return reason", "error");
        return;
    }
    
    if(!refundMethod) {
        showAjaxModal_alert("Please select a refund method", "error");
        return;
    }
    
    // Calculate total for confirmation message
    const currency = "' . $currency . '";
    let totalItems = window.selectedItems.reduce((sum, item) => sum + parseFloat(item.quantity), 0);
    let totalRefund = window.selectedItems.reduce((sum, item) => sum + (parseFloat(item.quantity) * parseFloat(item.unit_price)), 0);
    
    // Use custom confirm modal
    showConfirmModal(
        "Confirm Return",
        `<div style="font-size: 1.1rem;">
            <p style="margin-bottom: 15px;">You are about to process a return for:</p>
            <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <div style="margin-bottom: 8px;"><strong>Total Items:</strong> ${totalItems}</div>
                <div style="margin-bottom: 8px;"><strong>Total Refund:</strong> <span style="color: #059669; font-weight: bold;">${currency} ${totalRefund.toFixed(2)}</span></div>
                <div style="margin-bottom: 8px;"><strong>Reason:</strong> ${reason}</div>
            </div>
            <p style="color: #dc2626; font-weight: 600;">This action cannot be undone. Continue?</p>
        </div>`,
        function() {
            // Confirmed - process the return
            processReturnAjax(reason, refundMethod, notes);
        },
        "Process Return",
        "warning"
    );
};

function processReturnAjax(reason, refundMethod, notes) {
    $.ajax({
        url: "' . site_url('inventory/process_return') . '",
        type: "POST",
        data: {
            sale_id: window.currentSaleData.id,
            return_reason: reason,
            refund_method: refundMethod,
            return_notes: notes,
            items: JSON.stringify(window.selectedItems)
        },
        dataType: "json",
        success: function(response) {
            if(response.status === "success") {
                showAjaxModal_alert("Return processed successfully!", "success");
                setTimeout(function() {
                    $(".close").click();
                    if(typeof loadReturns === "function") {
                        loadReturns();
                    }
                }, 2000);
            } else {
                showAjaxModal_alert(response.message, "error");
            }
        },
        error: function() {
            showAjaxModal_alert("Error processing return", "error");
        }
    });
}
</script>';
        
        echo $html;
    }
    
    /**
     * AJAX: Search for sales to process returns
     * Access: Admin levels 1, 2, 3, 6
     */
    public function search_sales_for_return() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $filters = [
            'sale_id' => $this->input->get('sale_id'),
            'customer_name' => $this->input->get('customer_name'),
            'start_date' => $this->input->get('start_date'),
            'end_date' => $this->input->get('end_date'),
            'product_name' => $this->input->get('product_name')
        ];
        
        $sales = $this->Inventory_model->search_sales_for_return($filters);
        echo json_encode(['status' => 'success', 'sales' => $sales]);
    }
    
    /**
     * AJAX: Get sale details for return processing
     * Access: Admin levels 1, 2, 3, 6
     */
    public function get_sale_for_return($sale_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $sale = $this->Inventory_model->get_sale_return_info($sale_id);
        
        if($sale) {
            echo json_encode(['status' => 'success', 'sale' => $sale]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Sale not found']);
        }
    }
    
    /**
     * AJAX: Process a return transaction
     * Access: Admin levels 1, 2, 3, 6
     */
    public function process_return() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        // Get POST data
        $return_data = [
            'original_sale_id' => $this->input->post('sale_id'),
            'return_reason' => $this->input->post('return_reason'),
            'return_notes' => $this->input->post('return_notes'),
            'refund_method' => $this->input->post('refund_method')
        ];
        
        $return_items = json_decode($this->input->post('items'), true);
        
        // Validate required fields
        if(empty($return_data['original_sale_id']) || empty($return_items)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            return;
        }
        
        // Process return (student_id will be fetched from original sale in the model)
        $return_id = $this->Inventory_model->process_return($return_data, $return_items);
        
        if($return_id) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Return processed successfully',
                'return_id' => $return_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to process return. Please check logs for details.'
            ]);
        }
    }
    
    /**
     * AJAX: Get list of returns with filtering
     * Access: Admin levels 1, 2, 3, 6
     */
    public function get_returns() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $filters = [
            'start_date' => $this->input->get('start_date'),
            'end_date' => $this->input->get('end_date'),
            'customer' => $this->input->get('customer'),
            'reason' => $this->input->get('reason'),
            'product_id' => $this->input->get('product_id'),
            'processed_by' => $this->input->get('processed_by')
        ];
        
        $result = $this->Inventory_model->get_returns($filters);
        echo json_encode(['status' => 'success', 'data' => $result]);
    }
    
    /**
     * Return details view
     * Access: Admin levels 1, 2, 3, 6
     */
    public function return_details($return_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            if($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            } else {
                show_error('Access Denied');
            }
            return;
        }
        
        $return = $this->Inventory_model->get_return_details($return_id);
        
        if($this->input->is_ajax_request()) {
            if($return) {
                echo json_encode(['status' => 'success', 'return' => $return]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Return not found']);
            }
        } else {
            $page_data['page_name'] = 'inventory/return_details';
            $page_data['page_title'] = get_phrase('return_details');
            $page_data['return'] = $return;
            $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
            $this->load->view('backend/main', $page_data);
        }
    }
    
    /**
     * Export returns to CSV
     * Access: Admin levels 1, 2, 3, 6
     */
    public function export_returns() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            show_error('Access Denied');
            return;
        }
        
        $filters = [
            'start_date' => $this->input->get('start_date'),
            'end_date' => $this->input->get('end_date'),
            'customer' => $this->input->get('customer'),
            'reason' => $this->input->get('reason'),
            'product_id' => $this->input->get('product_id'),
            'processed_by' => $this->input->get('processed_by')
        ];
        
        $result = $this->Inventory_model->get_returns($filters);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="returns_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Return ID', 'Date', 'Sale ID', 'Customer', 'Items Count', 'Refund Amount', 'Reason', 'Processed By']);
        
        foreach($result['returns'] as $return) {
            fputcsv($output, [
                $return['id'],
                date('Y-m-d H:i', strtotime($return['return_date'])),
                $return['sale_id'],
                $return['customer_name'] ?: 'N/A',
                $return['items_count'],
                $return['total_refund_amount'],
                $return['return_reason'],
                $return['processed_by_name']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // ============================================
    // PURCHASE ORDERS CONTROLLER METHODS
    // ============================================
    
    /**
     * AJAX: Create a new purchase order
     * Access: Admin levels 1, 2, 3
     */
    public function create_purchase_order() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        // Check if this is an update
        $purchase_id = $this->input->post('purchase_id');
        $is_update = !empty($purchase_id);
        
        // Handle deleted payments if updating
        if($is_update) {
            $deleted_payment_ids = $this->input->post('deleted_payment_ids');
            log_message('debug', 'Deleted payment IDs received: ' . $deleted_payment_ids);
            
            if(!empty($deleted_payment_ids)) {
                $deleted_ids_array = explode(',', $deleted_payment_ids);
                foreach($deleted_ids_array as $payment_id) {
                    $payment_id = trim($payment_id);
                    if(!empty($payment_id) && is_numeric($payment_id)) {
                        log_message('debug', 'Deleting payment ID: ' . $payment_id . ' for purchase_id: ' . $purchase_id);
                        
                        $this->db->where('id', $payment_id);
                        $this->db->where('purchase_id', $purchase_id); // Security: ensure payment belongs to this PO
                        $delete_result = $this->db->delete('inventory_purchase_payments');
                        
                        log_message('debug', 'Payment deletion result: ' . ($delete_result ? 'success' : 'failed'));
                        log_message('debug', 'Affected rows: ' . $this->db->affected_rows());
                    }
                }
                
                // Recalculate payment status after deletions
                $this->recalculate_purchase_order_payment_status($purchase_id);
            }
        }
        
        // Get POST data
        $created_date = $this->input->post('created_date');
        $expected_date = $this->input->post('expected_delivery_date');
        
        // Convert dd/mm/yyyy to Y-m-d for database
        if($created_date && strpos($created_date, '/') !== false) {
            $created_date = $this->convert_date_to_mysql($created_date);
        }
        if($expected_date && strpos($expected_date, '/') !== false) {
            $expected_date = $this->convert_date_to_mysql($expected_date);
        }
        
        $po_data = [
            'supplier_id' => $this->input->post('supplier_id'),
            'purchase_date' => $created_date,
            'expected_delivery_date' => $expected_date,
            'reference_number' => $this->input->post('reference_number'),
            'notes' => $this->input->post('notes'),
            'discount_type' => $this->input->post('discount_type') ?: null,
            'discount_value' => $this->input->post('discount_value') ?: 0
        ];
        
        // Get items - check if it's already an array or needs to be decoded
        $items_input = $this->input->post('items');
        if(is_array($items_input)) {
            // Form submitted with array structure (items[product_id][], items[quantity][], etc.)
            $po_items = [];
            if(isset($items_input['product_id']) && is_array($items_input['product_id'])) {
                foreach($items_input['product_id'] as $index => $product_id) {
                    $po_items[] = [
                        'product_id' => $product_id,
                        'quantity' => $items_input['quantity'][$index] ?? 0,
                        'cost_price' => $items_input['cost_price'][$index] ?? 0
                    ];
                }
            }
        } else {
            // JSON string format
            $po_items = json_decode($items_input, true);
        }
        
        // Validate required fields
        if(empty($po_data['purchase_date']) || empty($po_items)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            return;
        }
        
        // Create or update purchase order
        if($is_update) {
            $result = $this->Inventory_model->update_purchase_order($purchase_id, $po_data, $po_items);
            if(!$result) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update purchase order']);
                return;
            }
            $message = 'Purchase order updated successfully';
            
            // Recalculate payment status after update (total may have changed)
            $this->recalculate_purchase_order_payment_status($purchase_id);
        } else {
            $purchase_id = $this->Inventory_model->create_purchase_order($po_data, $po_items);
            if(!$purchase_id) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create purchase order']);
                return;
            }
            $message = 'Purchase order created successfully';
        }
        
        // Get current purchase details
        $purchase = $this->db->get_where('inventory_purchases', ['id' => $purchase_id])->row();
        
        // Get existing payments total
        $existing_payments_total = $this->db
            ->select_sum('amount')
            ->where('purchase_id', $purchase_id)
            ->get('inventory_purchase_payments')
            ->row()->amount ?? 0;
        
        // Check if payment should be recorded/updated
        $record_payment = $this->input->post('record_payment_now');
        
        if($record_payment == '1') {
            if(!$is_update) {
                // CREATING NEW ORDER - Record initial payment
                $payment_method_id = $this->input->post('payment_method_id');
                $payment_amount = $this->input->post('payment_amount');
                
                // Validate payment fields
                if(empty($payment_method_id)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Purchase order created but payment method is required for payment recording'
                    ]);
                    return;
                }
                
                // If payment amount is empty or 0, use full amount
                if(empty($payment_amount) || $payment_amount == 0) {
                    $payment_amount = $purchase->total_amount;
                }
                
                // Validate payment amount
                if($payment_amount > $purchase->total_amount) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Purchase order created but payment amount cannot exceed total amount'
                    ]);
                    return;
                }
                
                // Prepare payment data
                $payment_data = [
                    'purchase_id' => $purchase_id,
                    'payment_date' => $this->input->post('payment_date') ?: date('Y-m-d'),
                    'amount' => $payment_amount,
                    'payment_method_id' => $payment_method_id,
                    'reference_number' => $this->input->post('payment_reference') ?: null,
                    'notes' => $this->input->post('payment_notes') ?: null,
                    'recorded_by' => $admin_id
                ];
                
                // Record payment
                $payment_result = $this->Inventory_model->record_purchase_payment($payment_data);
                
                if($payment_result['status'] === 'success') {
                    $message = 'Purchase order created and payment recorded successfully';
                } else {
                    $message = 'Purchase order created but payment recording failed: ' . $payment_result['message'];
                }
            } else {
                // UPDATING EXISTING ORDER - Record new payment (only if user entered data)
                $payment_method_id = $this->input->post('payment_method_id');
                $payment_amount = floatval($this->input->post('payment_amount') ?: 0);
                
                // Only validate and record if user actually entered payment data
                $has_payment_data = !empty($payment_method_id) || $payment_amount > 0;
                
                if($has_payment_data) {
                    // Get current outstanding balance
                    $balance_due = $purchase->total_amount - $existing_payments_total;
                    
                    // If payment amount is empty or 0, use full balance
                    if($payment_amount == 0) {
                        $payment_amount = $balance_due;
                    }
                    
                    // Validate payment fields
                    if(empty($payment_method_id)) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Purchase order updated but payment method is required for payment recording'
                        ]);
                        return;
                    }
                    
                    // Validate payment amount
                    if($payment_amount > $balance_due) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Payment amount cannot exceed outstanding balance of GHC ' . number_format($balance_due, 2)
                        ]);
                        return;
                    }
                    
                    if($payment_amount > 0) {
                        // Convert payment date from dd/mm/yyyy to yyyy-mm-dd
                        $payment_date_raw = $this->input->post('payment_date');
                        $payment_date = $payment_date_raw ? $this->convert_date_to_mysql($payment_date_raw) : date('Y-m-d');
                        
                        // Prepare payment data
                        $payment_data = [
                            'purchase_id' => $purchase_id,
                            'payment_date' => $payment_date,
                            'amount' => $payment_amount,
                            'payment_method_id' => $payment_method_id,
                            'reference_number' => $this->input->post('payment_reference') ?: null,
                            'notes' => $this->input->post('payment_notes') ?: 'Payment added during purchase order edit',
                            'recorded_by' => $admin_id
                        ];
                        
                        // Record payment
                        $payment_result = $this->Inventory_model->record_purchase_payment($payment_data);
                        
                        if($payment_result['status'] === 'success') {
                            $message = 'Purchase order updated and new payment of GHC ' . number_format($payment_amount, 2) . ' recorded successfully';
                        } else {
                            $message = 'Purchase order updated but payment recording failed: ' . $payment_result['message'];
                        }
                    }
                }
            }
        
        } elseif($is_update && $existing_payments_total > 0) {
            // UPDATING EXISTING ORDER WITH PAYMENTS
            
            // Check if total changed
            $total_changed = ($existing_payments_total != $purchase->total_amount);
            
            if($total_changed) {
                // Total amount changed - need to handle this
                
                if($existing_payments_total > $purchase->total_amount) {
                    // OVERPAYMENT SITUATION
                    $overpayment = $existing_payments_total - $purchase->total_amount;
                    $message .= sprintf(
                        '. WARNING: Total payments (GHC %s) exceed the new order total (GHC %s) by GHC %s. Please review payment records.',
                        number_format($existing_payments_total, 2),
                        number_format($purchase->total_amount, 2),
                        number_format($overpayment, 2)
                    );
                } elseif($existing_payments_total < $purchase->total_amount) {
                    // UNDERPAYMENT - there's a balance remaining
                    $balance = $purchase->total_amount - $existing_payments_total;
                    $message .= sprintf(
                        '. Note: Outstanding balance is GHC %s. You can record additional payments from the purchase orders list.',
                        number_format($balance, 2)
                    );
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'purchase_id' => $purchase_id
        ]);
    }
    
    /**
     * AJAX: Get list of purchase orders with filtering
     * Access: Admin levels 1, 2, 3
     */
    public function get_purchase_orders() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        // Convert dd/mm/yyyy dates to yyyy-mm-dd for database queries
        $filters = [
            'start_date' => $this->convert_date_to_mysql($this->input->get('start_date')),
            'end_date' => $this->convert_date_to_mysql($this->input->get('end_date')),
            'supplier_id' => $this->input->get('supplier_id'),
            'status' => $this->input->get('status'),
            'payment_status' => $this->input->get('payment_status'),
            'created_by' => $this->input->get('created_by')
        ];
        
        $result = $this->Inventory_model->get_purchase_orders($filters);
        echo json_encode(['status' => 'success', 'data' => $result]);
    }
    
    /**
     * Purchase order details view
     * Access: Admin levels 1, 2, 3
     */
    public function purchase_order_details($po_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            if($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            } else {
                show_error('Access Denied');
            }
            return;
        }
        
        $purchase_order = $this->Inventory_model->get_purchase_order_details($po_id);
        
        // ALWAYS load the view for modal display (even for AJAX requests)
        // The loadModalContent function expects HTML, not JSON
        $page_data['po'] = $purchase_order; // Changed from 'purchase_order' to 'po' to match view variable
        $page_data['currency'] = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        // Load view directly without the main layout wrapper (for modal)
        $this->load->view('backend/admin/inventory/purchase_order_details', $page_data);
    }
    
    /**
     * AJAX: Update purchase order status
     * Access: Admin levels 1, 2, 3
     */
    public function update_purchase_status() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $po_id = $this->input->post('po_id');
        $status = $this->input->post('status');
        $cost_update_method = $this->input->post('cost_update_method') ?: 'replace';
        
        // Validate required fields
        if(empty($po_id) || empty($status)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            return;
        }
        
        // Validate status
        if(!in_array($status, ['received', 'cancelled'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            return;
        }
        
        $result = false;
        
        if($status == 'received') {
            $result = $this->Inventory_model->mark_purchase_received($po_id, $cost_update_method);
        } elseif($status == 'cancelled') {
            $result = $this->Inventory_model->cancel_purchase_order($po_id);
        }
        
        if($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Purchase order status updated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update purchase order status'
            ]);
        }
    }
    
    /**
     * AJAX: Delete a purchase order
     * Access: Admin levels 1, 2, 3
     */
    public function delete_purchase_order() {
        // Log the request for debugging
        log_message('info', 'Delete PO request received: ' . json_encode($_POST));
        
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            log_message('error', 'Access denied for admin level: ' . $admin->level);
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $po_id = $this->input->post('po_id');
        
        if(empty($po_id)) {
            log_message('error', 'Delete PO: No PO ID provided');
            echo json_encode(['status' => 'error', 'message' => 'Purchase order ID is required']);
            return;
        }
        
        log_message('info', 'Attempting to delete PO ID: ' . $po_id);
        
        $result = $this->Inventory_model->delete_purchase_order($po_id);
        
        // Check if result is an array with error message
        if(is_array($result) && isset($result['status']) && $result['status'] === false) {
            log_message('error', 'Failed to delete PO: ' . $po_id . ' - ' . $result['message']);
            echo json_encode([
                'status' => 'error',
                'message' => $result['message']
            ]);
            return;
        }
        
        if($result) {
            log_message('info', 'PO deleted successfully: ' . $po_id);
            echo json_encode([
                'status' => 'success',
                'message' => 'Purchase order deleted successfully'
            ]);
        } else {
            log_message('error', 'Failed to delete PO: ' . $po_id);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete purchase order. Please check error logs.'
            ]);
        }
    }
    
    /**
     * AJAX: Purchase order edit form
     * Access: Admin levels 1, 2, 3
     */
    public function purchase_order_edit_form($po_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo 'Access denied';
            return;
        }
        
        // Simply call purchase_order_form with the ID
        $this->purchase_order_form($po_id);
    }
    
    /**
     * AJAX: Update purchase order
     * Access: Admin levels 1, 2, 3
     */
    public function update_purchase_order() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $po_id = $this->input->post('purchase_id');
        
        // Convert dates from dd/mm/yyyy to yyyy-mm-dd
        $purchase_date = $this->input->post('created_date');
        $expected_delivery = $this->input->post('expected_delivery_date');
        
        $po_data = [
            'purchase_date' => $this->convert_date_to_mysql($purchase_date),
            'supplier_id' => $this->input->post('supplier_id') ?: null,
            'expected_delivery_date' => $expected_delivery ? $this->convert_date_to_mysql($expected_delivery) : null,
            'notes' => $this->input->post('notes'),
            'discount_type' => $this->input->post('discount_type') ?: null,
            'discount_value' => floatval($this->input->post('discount_value') ?: 0)
        ];
        
        // Get items from the form
        $product_ids = $this->input->post('items')['product_id'] ?? [];
        $quantities = $this->input->post('items')['quantity'] ?? [];
        $cost_prices = $this->input->post('items')['cost_price'] ?? [];
        
        $po_items = [];
        foreach($product_ids as $index => $product_id) {
            $po_items[] = [
                'product_id' => $product_id,
                'quantity' => $quantities[$index],
                'cost_price' => $cost_prices[$index]
            ];
        }
        
        $result = $this->Inventory_model->update_purchase_order($po_id, $po_data, $po_items);
        
        if($result) {
            // Check if payment should be recorded
            $record_payment = $this->input->post('record_payment_now');
            
            if($record_payment == '1') {
                // Get payment details from form
                $payment_method_id = $this->input->post('payment_method_id');
                $payment_reference = $this->input->post('payment_reference');
                $payment_amount = floatval($this->input->post('payment_amount') ?: 0);
                
                // Get total amount from the purchase order
                $current_po = $this->db->get_where('inventory_purchase_orders', ['id' => $po_id])->row();
                
                // If payment amount is 0 or empty, use full balance
                $balance_due = $current_po->total_amount - ($current_po->amount_paid ?? 0);
                
                if($payment_amount == 0) {
                    $payment_amount = $balance_due;
                }
                
                // Validate payment amount
                if($payment_amount > $balance_due) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Payment amount cannot exceed balance due of GHC ' . number_format($balance_due, 2)
                    ]);
                    return;
                }
                
                if($payment_method_id && $payment_amount > 0) {
                    $payment_data = [
                        'purchase_order_id' => $po_id,
                        'payment_method_id' => $payment_method_id,
                        'amount' => $payment_amount,
                        'reference_number' => $payment_reference ?: null,
                        'notes' => $this->input->post('payment_notes') ?: 'Payment recorded during purchase order edit',
                        'payment_date' => date('Y-m-d'),
                        'recorded_by' => $admin_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->db->insert('inventory_purchase_payments', $payment_data);
                    
                    // Update amount_paid in purchase_orders table
                    $new_amount_paid = ($current_po->amount_paid ?? 0) + $payment_amount;
                    $this->db->where('id', $po_id);
                    $this->db->update('inventory_purchase_orders', ['amount_paid' => $new_amount_paid]);
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Purchase order updated successfully',
                'po_id' => $po_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update purchase order'
            ]);
        }
    }
    
    /**
     * Export purchase orders to CSV
     * Access: Admin levels 1, 2, 3
     */
    public function export_purchase_orders() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            show_error('Access Denied');
            return;
        }
        
        $filters = [
            'start_date' => $this->input->get('start_date'),
            'end_date' => $this->input->get('end_date'),
            'supplier_id' => $this->input->get('supplier_id'),
            'status' => $this->input->get('status'),
            'created_by' => $this->input->get('created_by')
        ];
        
        $result = $this->Inventory_model->get_purchase_orders($filters);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="purchase_orders_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['PO ID', 'Date', 'Supplier', 'Items Count', 'Total Amount', 'Status', 'Created By']);
        
        foreach($result['purchase_orders'] as $po) {
            fputcsv($output, [
                $po['id'],
                $po['purchase_date'],
                $po['supplier_name'] ?: 'N/A',
                $po['items_count'],
                $po['total_amount'],
                ucfirst($po['status']),
                $po['created_by_name']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // ============================================
    // ENHANCED SUPPLIER CONTROLLER METHODS
    // ============================================
    
    /**
     * AJAX: Create a new supplier
     * Access: Admin levels 1, 2, 3
     */
    public function create_supplier() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $data = [
            'name' => $this->input->post('name'),
            'contact_person' => $this->input->post('contact_person'),
            'email' => $this->input->post('email'),
            'phone' => $this->input->post('phone'),
            'address' => $this->input->post('address'),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate required field
        if(empty($data['name'])) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier name is required']);
            return;
        }
        
        $supplier_id = $this->Inventory_model->create_supplier($data);
        
        if($supplier_id) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Supplier created successfully',
                'supplier_id' => $supplier_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create supplier'
            ]);
        }
    }
    
    /**
     * AJAX: Update supplier information
     * Access: Admin levels 1, 2, 3
     */
    public function update_supplier() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $supplier_id = $this->input->post('supplier_id');
        $data = [
            'name' => $this->input->post('name'),
            'contact_person' => $this->input->post('contact_person'),
            'email' => $this->input->post('email'),
            'phone' => $this->input->post('phone'),
            'address' => $this->input->post('address'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate required fields
        if(empty($supplier_id) || empty($data['name'])) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier ID and name are required']);
            return;
        }
        
        $result = $this->Inventory_model->update_supplier($supplier_id, $data);
        
        if($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Supplier updated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update supplier'
            ]);
        }
    }
    
    /**
     * AJAX: Deactivate a supplier
     * Access: Admin levels 1, 2, 3
     */
    public function deactivate_supplier($supplier_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $result = $this->Inventory_model->deactivate_supplier($supplier_id);
        
        if($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Supplier deactivated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to deactivate supplier'
            ]);
        }
    }
    
    /**
     * AJAX: Get supplier details
     * Access: Admin levels 1, 2, 3
     */
    public function get_supplier_details($supplier_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $supplier = $this->Inventory_model->get_supplier_details($supplier_id);
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        if($supplier) {
            echo json_encode(['status' => 'success', 'supplier' => $supplier, 'currency' => $currency]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Supplier not found']);
        }
    }
    
    /**
     * AJAX: Toggle supplier status (activate/deactivate)
     * Access: Admin levels 1, 2, 3
     */
    public function toggle_supplier_status() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $supplier_id = $this->input->post('supplier_id');
        
        if(!$supplier_id) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier ID is required']);
            return;
        }
        
        // Get current status
        $supplier = $this->db->get_where('inventory_suppliers', ['id' => $supplier_id])->row();
        
        if(!$supplier) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier not found']);
            return;
        }
        
        // Toggle status
        $new_status = $supplier->status == 1 ? 0 : 1;
        $action = $new_status == 1 ? 'activated' : 'deactivated';
        
        if($this->db->update('inventory_suppliers', ['status' => $new_status], ['id' => $supplier_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Supplier ' . $action . ' successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update supplier status'
            ]);
        }
    }
    
    /**
     * AJAX: Get return statistics for dashboard
     * Access: Admin levels 1, 2, 3, 6
     */
    /**
     * Void/Delete a return transaction
     * Reverses all changes made by the return
     * Only accessible by admin levels 1, 2, 3
     */
    public function void_return() {
        // Check permissions - only senior admins can void returns
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied. Only senior administrators can void returns.']);
            return;
        }
        
        $return_id = $this->input->post('return_id');
        
        if(empty($return_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Return ID is required']);
            return;
        }
        
        // Get return details
        $return = $this->db->get_where('inventory_returns', ['id' => $return_id])->row();
        
        if(!$return) {
            echo json_encode(['status' => 'error', 'message' => 'Return not found']);
            return;
        }
        
        // Get return items
        $return_items = $this->db->get_where('inventory_return_items', ['return_id' => $return_id])->result_array();
        
        if(empty($return_items)) {
            echo json_encode(['status' => 'error', 'message' => 'No return items found']);
            return;
        }
        
        $this->db->trans_start();
        
        try {
            // Step 1: Restore sale items (reverse the return)
            foreach($return_items as $item) {
                // Check if the sale item still exists
                $sale_item = $this->db->get_where('inventory_sale_items', ['id' => $item['sale_item_id']])->row();
                
                if($sale_item) {
                    // Item exists - add back the returned quantity
                    $new_quantity = $sale_item->quantity + $item['quantity_returned'];
                    $new_total = $new_quantity * $sale_item->unit_price;
                    
                    $this->db->where('id', $item['sale_item_id']);
                    $this->db->update('inventory_sale_items', [
                        'quantity' => $new_quantity,
                        'total_price' => $new_total
                    ]);
                } else {
                    // Item was deleted (fully returned) - recreate it
                    $this->db->insert('inventory_sale_items', [
                        'sale_id' => $return->original_sale_id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity_returned'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['refund_amount']
                    ]);
                }
                
                // Step 2: Reduce stock (reverse the stock restoration)
                $this->db->set('quantity', 'quantity - ' . (int)$item['quantity_returned'], FALSE);
                $this->db->where('id', $item['product_id']);
                $this->db->update('inventory_products');
                
                // Step 3: Create reversal stock movement
                $movement = [
                    'product_id' => $item['product_id'],
                    'movement_type' => 'out',
                    'quantity' => $item['quantity_returned'],
                    'notes' => 'Void Return #' . $return_id . ' - Original Sale #' . $return->original_sale_id,
                    'performed_by' => $admin_id,
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // Step 4: Recalculate sale total
            $this->db->select_sum('total_price');
            $this->db->where('sale_id', $return->original_sale_id);
            $new_sale_total = $this->db->get('inventory_sale_items')->row()->total_price ?? 0;
            
            $this->db->where('id', $return->original_sale_id);
            $this->db->update('inventory_sales', [
                'total_amount' => $new_sale_total
            ]);
            
            // Step 5: Reverse financial refund entry
            $this->load->library('Financial_integration_hooks');
            $reversal_result = $this->financial_integration_hooks->void_refund([
                'return_id' => $return_id,
                'amount' => $return->total_refund_amount,
                'voided_by' => $admin_id,
                'void_date' => date('Y-m-d'),
                'notes' => 'Return #' . $return_id . ' voided by ' . $admin->name
            ]);
            
            // Step 6: Delete return items
            $this->db->where('return_id', $return_id);
            $this->db->delete('inventory_return_items');
            
            // Step 7: Delete return record
            $this->db->where('id', $return_id);
            $this->db->delete('inventory_returns');
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to void return. Transaction rolled back.']);
                return;
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Return voided successfully. Sale has been restored to original state.',
                'sale_id' => $return->original_sale_id
            ]);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Void return failed: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to void return: ' . $e->getMessage()]);
        }
    }
    
    public function get_return_statistics() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $filters = [
            'start_date' => $this->input->get('start_date'),
            'end_date' => $this->input->get('end_date')
        ];
        
        $stats = $this->Inventory_model->get_return_statistics($filters);
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        echo json_encode([
            'status' => 'success',
            'total_returns' => $stats['total_returns_count'] ?? 0,
            'total_refund_amount' => $stats['total_refund_amount'] ?? 0,
            'currency' => $currency
        ]);
    }
    
    /**
     * AJAX: Get purchase order statistics for dashboard
     * Access: Admin levels 1, 2, 3
     */
    public function get_purchase_statistics() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        $status = $this->input->get('status');
        $filters = [];
        
        if($status) {
            $filters['status'] = $status;
        }
        
        $stats = $this->Inventory_model->get_purchase_statistics($filters);
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        
        // Calculate pending orders count and value
        $this->db->select('COUNT(*) as count, SUM(total_amount) as total');
        $this->db->from('inventory_purchases');
        $this->db->where('status', 'pending');
        $pending = $this->db->get()->row();
        
        echo json_encode([
            'status' => 'success',
            'pending_count' => $pending->count ?? 0,
            'pending_amount' => $pending->total ?? 0,
            'total_purchases' => $stats['total_purchases'] ?? 0,
            'total_amount' => $stats['total_amount'] ?? 0,
            'currency' => $currency
        ]);
    }
    
    /**
     * AJAX: Get payment history for a purchase order
     * Returns payment history with running balance
     * Access: Admin levels 1, 2, 3, 6
     */
    public function get_purchase_payments($purchase_id) {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3, 6])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        // Validate purchase order exists
        $purchase = $this->db->get_where('inventory_purchases', ['id' => $purchase_id])->row();
        if(!$purchase) {
            echo json_encode(['status' => 'error', 'message' => 'Purchase order not found']);
            return;
        }
        
        // Get payment history from model
        $result = $this->Inventory_model->get_purchase_payments($purchase_id);
        
        // Format response
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
    }
    
    /**
     * AJAX: Get outstanding payables with filters
     * Returns list of unpaid/partially paid purchase orders
     * Access: Admin levels 1, 2, 3
     */
    public function get_outstanding_payables() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        // Get filters from query parameters
        $filters = [];
        
        if($this->input->get('supplier_id')) {
            $filters['supplier_id'] = $this->input->get('supplier_id');
        }
        
        if($this->input->get('aging')) {
            $filters['aging'] = $this->input->get('aging');
        }
        
        if($this->input->get('start_date')) {
            $filters['start_date'] = $this->input->get('start_date');
        }
        
        if($this->input->get('end_date')) {
            $filters['end_date'] = $this->input->get('end_date');
        }
        
        // Pagination support
        $page = $this->input->get('page') ?: 1;
        $limit = $this->input->get('limit') ?: 50;
        $filters['page'] = $page;
        $filters['limit'] = $limit;
        
        // Get outstanding payables from model
        $result = $this->Inventory_model->get_outstanding_payables($filters);
        
        // Format response
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
    }
    
    /**
     * AJAX: Get outstanding payables summary for dashboard
     * Returns total outstanding amount and count of unpaid orders
     * Access: Admin levels 1, 2, 3
     */
    public function get_outstanding_payables_summary() {
        // Check permissions
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }
        
        // Get summary from model
        $summary = $this->Inventory_model->get_outstanding_payables_summary();
        
        // Format response
        echo json_encode([
            'status' => 'success',
            'data' => $summary
        ]);
    }
    
    /**
     * AJAX: Record payment for a purchase order
     * Creates payment record, updates purchase order status, and creates expenditure entry
     * Access: Super Admin (1), Admin (2), Accountant (3)
     */
    public function record_purchase_payment() {
        // Check permissions - Only Super Admin, Admin, and Accountant can record payments
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if(!in_array($admin->level, [1, 2, 3])) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Access denied. Only Super Admin, Admin, and Accountant can record payments.'
            ]);
            return;
        }
        
        // Validate required fields
        $purchase_id = $this->input->post('purchase_id');
        $payment_date_raw = $this->input->post('payment_date');
        $amount = $this->input->post('amount');
        $payment_method_id = $this->input->post('payment_method_id');
        
        // Convert payment date from dd/mm/yyyy to yyyy-mm-dd if needed
        $payment_date = $this->convert_date_to_mysql($payment_date_raw);
        
        if(empty($purchase_id) || empty($payment_date) || empty($amount) || empty($payment_method_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields. Please provide purchase_id, payment_date, amount, and payment_method_id.'
            ]);
            return;
        }
        
        // Validate purchase order exists
        $purchase = $this->db->get_where('inventory_purchases', ['id' => $purchase_id])->row();
        if(!$purchase) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Purchase order not found.'
            ]);
            return;
        }
        
        // Validate purchase order is not cancelled
        if($purchase->status == 'cancelled') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot record payment for a cancelled purchase order.'
            ]);
            return;
        }
        
        // Validate payment amount
        if(!is_numeric($amount) || $amount <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment amount must be greater than zero.'
            ]);
            return;
        }
        
        // Validate payment amount does not exceed outstanding balance
        $outstanding_balance = $purchase->total_amount - $purchase->amount_paid;
        if($amount > $outstanding_balance) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment amount cannot exceed outstanding balance of ' . number_format($outstanding_balance, 2) . '.'
            ]);
            return;
        }
        
        // Validate payment method exists and is active
        $payment_method = $this->db->get_where('payment_methods', ['id' => $payment_method_id])->row();
        if(!$payment_method) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid payment method selected.'
            ]);
            return;
        }
        
        if($payment_method->is_active != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Selected payment method is not active.'
            ]);
            return;
        }
        
        // Validate payment date is not in the future
        $payment_timestamp = strtotime($payment_date);
        $today_timestamp = strtotime(date('Y-m-d'));
        
        if($payment_timestamp > $today_timestamp) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment date cannot be in the future.'
            ]);
            return;
        }
        
        // Optional: Validate payment date is not too far in the past (e.g., 90 days)
        $ninety_days_ago = strtotime('-90 days');
        if($payment_timestamp < $ninety_days_ago) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment date cannot be more than 90 days in the past.'
            ]);
            return;
        }
        
        // Prepare payment data
        $payment_data = [
            'purchase_id' => $purchase_id,
            'payment_date' => $payment_date,
            'amount' => $amount,
            'payment_method_id' => $payment_method_id,
            'reference_number' => $this->input->post('reference_number') ?: null,
            'notes' => $this->input->post('notes') ?: null,
            'recorded_by' => $admin_id
        ];
        
        // Call model method to record payment
        $result = $this->Inventory_model->record_purchase_payment($payment_data);
        
        // Return response
        if($result['status'] == 'success') {
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'payment_id' => $result['payment_id'],
                    'new_status' => $result['new_status'],
                    'outstanding_balance' => $result['outstanding_balance'],
                    'amount_paid' => $result['amount_paid']
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $result['message'] ?? 'Failed to record payment. Please try again.'
            ]);
        }
    }
    
    /**
     * Delete a purchase order payment
     * Endpoint: DELETE /inventory/delete_purchase_payment/{payment_id}
     * 
     * Only Super Admin can delete payments
     * Deletes payment record, updates purchase order status, and removes expenditure entry
     * 
     * @param int $payment_id Payment ID to delete
     * @return JSON response with status and message
     * 
     * Access: Super Admin (1) only
     */
    public function delete_purchase_payment($payment_id) {
        // Check permissions - Only Super Admin can delete payments
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if($admin->level != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin can delete payments.'
            ]);
            return;
        }
        
        // Validate payment_id
        if(empty($payment_id) || !is_numeric($payment_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid payment ID.'
            ]);
            return;
        }
        
        // Validate payment exists
        $payment = $this->db->get_where('inventory_purchase_payments', ['id' => $payment_id])->row();
        if(!$payment) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment not found.'
            ]);
            return;
        }
        
        // Get deletion reason (optional but recommended for audit trail)
        $reason = $this->input->post('reason') ?: 'No reason provided';
        
        // Call model method to delete payment
        $result = $this->Inventory_model->delete_purchase_payment($payment_id, $reason);
        
        // Return response
        if($result['status'] == 'success') {
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment deleted successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $result['message'] ?? 'Failed to delete payment. Please try again.'
            ]);
        }
    }
}
