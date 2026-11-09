<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventory model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Inventory_model extends MY_Model {
    
    // Get dashboard statistics
    public function get_dashboard_stats() {
        // Get currency
        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description ?? 'GHC';
        
        // Get total products count
        $total_products = $this->db->count_all('inventory_products');
        
        // Get low stock count
        $low_stock_count = $this->db->where('quantity <=', 'reorder_level', FALSE)
            ->where('status', 1)
            ->count_all_results('inventory_products');
        
        // Get stock value (cost_price * quantity for all active products)
        $stock_value = $this->db->select('SUM(cost_price * quantity) as value')
            ->where('status', 1)
            ->get('inventory_products')
            ->row()->value ?? 0;
        
        // Get recent sales (last 30 days)
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        $recent_sales = $this->db->where('DATE(sale_date) >=', $thirty_days_ago)
            ->count_all_results('inventory_sales');
        
        $stats = [
            'total_products' => $total_products,
            'low_stock_count' => $low_stock_count,
            'stock_value' => $stock_value,
            'recent_sales' => $recent_sales,
            'currency' => $currency
        ];
        
        return $stats;
    }
    
    // Get all products
    public function get_products() {
        return $this->db->select('p.*, c.name as category_name')
            ->from('inventory_products p')
            ->join('inventory_categories c', 'c.id = p.category_id', 'left')
            ->order_by('p.id', 'DESC')
            ->get()->result();
    }
    
    // Get active products only
    public function get_active_products() {
        return $this->db->where('status', 1)->where('quantity >', 0)->get('inventory_products')->result();
    }
    
    // Get all categories
    public function get_categories() {
        return $this->db->order_by('name', 'ASC')->get('inventory_categories')->result();
    }
    
    // Get sales history with return status
    public function get_sales() {
        return $this->db->select('s.*, st.name as student_name, a.name as admin_name')
            ->from('inventory_sales s')
            ->join('student st', 'st.student_id = s.student_id', 'left')
            ->join('admin a', 'a.admin_id = s.served_by', 'left')
            ->group_by('s.id')
            ->order_by('s.sale_date', 'DESC')
            ->get()
            ->result_array();
    }
    
    // Get students
    public function get_students() {
        return $this->db->select('student_id, name')->where('active_status', 1)->order_by('name', 'ASC')->get('student')->result();
    }
    
    // Create product
    public function create_product($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('inventory_products', $data);
    }
    
    // Update product
    public function update_product($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('inventory_products', $data);
    }
    
    // Delete product
    public function delete_product($id) {
        // Check if product has sales history
        $this->db->where('product_id', $id);
        $sales_count = $this->db->count_all_results('inventory_sale_items');
        
        if($sales_count > 0) {
            // Product has sales history - deactivate instead of delete
            $updated = $this->db->where('id', $id)->update('inventory_products', ['status' => 0]);
            
            if($updated) {
                return [
                    'status' => 'warning',
                    'message' => 'Product has sales history and cannot be deleted. It has been deactivated instead.',
                    'action' => 'deactivated'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Operation failed'
                ];
            }
        } else {
            // No sales history - safe to delete completely
            $deleted = $this->db->where('id', $id)->delete('inventory_products');
            
            if($deleted) {
                return [
                    'status' => 'success',
                    'message' => 'Product deleted successfully',
                    'action' => 'deleted'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Operation failed'
                ];
            }
        }
    }
    
    // Create category
    public function create_category($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('inventory_categories', $data);
    }
    
    // Process sale with transaction
    public function process_sale($sale_data, $items) {
        $this->db->trans_start();
        
        $sale_data['sale_date'] = date('Y-m-d H:i:s');
        $this->db->insert('inventory_sales', $sale_data);
        $sale_id = $this->db->insert_id();
        
        foreach($items as $item) {
            $this->db->insert('inventory_sale_items', [
                'sale_id' => $sale_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price']
            ]);
            
            // Update product quantity
            $this->db->set('quantity', 'quantity - ' . $item['quantity'], FALSE)
                ->where('id', $item['product_id'])
                ->update('inventory_products');
            
            // Create stock movement record for sale (out)
            $movement = [
                'product_id' => $item['product_id'],
                'movement_type' => 'out',
                'quantity' => $item['quantity'],
                'notes' => 'Sale #' . $sale_id . ($sale_data['customer_name'] ? ' - Customer: ' . $sale_data['customer_name'] : ' - Walk-in'),
                'performed_by' => $this->session->userdata('admin_id'),
                'movement_date' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('inventory_stock_movements', $movement);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    // Get total sales revenue (for revenue dashboard integration)
    public function get_total_sales_revenue($start_date = null, $end_date = null) {
        $this->db->select_sum('total_amount');
        
        if($start_date) {
            $this->db->where('DATE(sale_date) >=', $start_date);
        }
        if($end_date) {
            $this->db->where('DATE(sale_date) <=', $end_date);
        }
        
        $result = $this->db->get('inventory_sales')->row();
        return $result->total_amount ?? 0;
    }
    
    // Get products with pagination
    public function get_products_paginated($search = '', $category = '', $status = '', $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $this->db->select('p.*, c.name as category_name, s.name as supplier_name');
        $this->db->from('inventory_products p');
        $this->db->join('inventory_categories c', 'c.id = p.category_id', 'left');
        $this->db->join('inventory_suppliers s', 's.id = p.supplier_id', 'left');
        
        if($search) {
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.sku', $search);
            $this->db->or_like('p.barcode', $search);
            $this->db->group_end();
        }
        
        if($category) {
            $this->db->where('p.category_id', $category);
        }
        
        if($status !== '') {
            $this->db->where('p.status', $status);
        }
        
        $total = $this->db->count_all_results('', FALSE);
        
        $this->db->limit($limit, $offset);
        $this->db->order_by('p.id', 'DESC');
        $products = $this->db->get()->result_array();
        
        return [
            'data' => $products,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ];
    }
    
    // Stock in
    public function stock_in($data) {
        $this->db->trans_start();
        
        $this->db->set('quantity', 'quantity + ' . (int)$data['quantity'], FALSE);
        $this->db->set('cost_price', $data['cost_price']);
        $this->db->set('last_restocked', date('Y-m-d'));
        $this->db->where('id', $data['product_id']);
        $this->db->update('inventory_products');
        
        $movement = [
            'product_id' => $data['product_id'],
            'movement_type' => 'in',
            'quantity' => $data['quantity'],
            'notes' => $data['notes'],
            'performed_by' => $this->session->userdata('admin_id')
        ];
        $this->db->insert('inventory_stock_movements', $movement);
        
        $this->log_audit('stock_in', 'inventory_products', $data['product_id'], null, $data);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    // Stock out
    public function stock_out($data) {
        $product = $this->db->get_where('inventory_products', ['id' => $data['product_id']])->row();
        
        if(!$product || $product->quantity < $data['quantity']) {
            return false;
        }
        
        $this->db->trans_start();
        
        $this->db->set('quantity', 'quantity - ' . (int)$data['quantity'], FALSE);
        $this->db->where('id', $data['product_id']);
        $this->db->update('inventory_products');
        
        $movement = [
            'product_id' => $data['product_id'],
            'movement_type' => 'out',
            'quantity' => $data['quantity'],
            'notes' => $data['notes'] . ' | Issued to: ' . $data['issued_to'] . ' | Purpose: ' . $data['purpose'],
            'performed_by' => $this->session->userdata('admin_id')
        ];
        $this->db->insert('inventory_stock_movements', $movement);
        
        $this->log_audit('stock_out', 'inventory_products', $data['product_id'], null, $data);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    // Get stock movements
    public function get_stock_movements($product_id = null, $start_date = null, $end_date = null) {
        $this->db->select('m.*, p.name as product_name, a.name as performed_by_name');
        $this->db->from('inventory_stock_movements m');
        $this->db->join('inventory_products p', 'p.id = m.product_id', 'left');
        $this->db->join('admin a', 'a.admin_id = m.performed_by', 'left');
        
        if($product_id) {
            $this->db->where('m.product_id', $product_id);
        }
        
        if($start_date) {
            $this->db->where('DATE(m.movement_date) >=', $start_date);
        }
        
        if($end_date) {
            $this->db->where('DATE(m.movement_date) <=', $end_date);
        }
        
        $this->db->order_by('m.movement_date', 'DESC');
        $this->db->order_by('m.id', 'DESC');
        $this->db->limit(100);
        
        return $this->db->get()->result_array();
    }
    
    // Get low stock items
    public function get_low_stock_items() {
        $this->db->select('p.*, c.name as category_name');
        $this->db->from('inventory_products p');
        $this->db->join('inventory_categories c', 'c.id = p.category_id', 'left');
        $this->db->where('p.quantity <=', 'p.reorder_level', FALSE);
        $this->db->where('p.status', 1);
        $this->db->order_by('p.quantity', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    // Get inventory report
    public function get_inventory_report() {
        $this->db->select('p.*, c.name as category_name, s.name as supplier_name');
        $this->db->from('inventory_products p');
        $this->db->join('inventory_categories c', 'c.id = p.category_id', 'left');
        $this->db->join('inventory_suppliers s', 's.id = p.supplier_id', 'left');
        $this->db->order_by('c.name', 'ASC');
        $this->db->order_by('p.name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    // Log audit
    public function log_audit($action_type, $table_name, $record_id, $old_values, $new_values) {
        // Capture current user ID from session
        $user_id = $this->session->userdata('admin_id');
        
        // PHP 8.x Fix: Validate user_id is not null before insertion
        if (empty($user_id)) {
            log_message('error', 'Inventory audit: No admin_id in session for ' . $action_type . ' operation on ' . $table_name);
            $user_id = 0; // System default for edge case where no valid session exists
        }
        
        $data = [
            'action_type' => $action_type,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'old_values' => json_encode($old_values),
            'new_values' => json_encode($new_values),
            'performed_by' => $user_id, // Now guaranteed to be non-null
            'ip_address' => $this->input->ip_address()
        ];
        
        $this->db->insert('inventory_audit_log', $data);
    }
    
    // Get suppliers
    public function get_suppliers() {
        $this->db->select('s.*, COUNT(p.id) as product_count');
        $this->db->from('inventory_suppliers s');
        $this->db->join('inventory_products p', 'p.supplier_id = s.id', 'left');
        $this->db->group_by('s.id');
        $this->db->order_by('s.name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    // Create supplier
    public function create_supplier($data) {
        return $this->db->insert('inventory_suppliers', $data);
    }
    
    // Update supplier
    public function update_supplier($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('inventory_suppliers', $data);
    }
    
    // Delete supplier
    public function delete_supplier($id) {
        $this->db->where('id', $id);
        return $this->db->delete('inventory_suppliers');
    }
    
    // ============================================
    // SALES RETURNS & REFUNDS METHODS
    // ============================================
    
    /**
     * Search for sales that can be returned
     * @param array $filters - sale_id, customer_name, start_date, end_date, product_name
     * @return array - Sales with items and return eligibility
     */
    public function search_sales_for_return($filters = []) {
        $this->db->select('s.*, st.name as customer_name, st.student_id, a.name as served_by_name');
        $this->db->from('inventory_sales s');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->join('admin a', 'a.admin_id = s.served_by', 'left');
        
        // Apply filters
        if (!empty($filters['sale_id'])) {
            $this->db->where('s.id', $filters['sale_id']);
        }
        
        if (!empty($filters['customer_name'])) {
            $this->db->like('st.name', $filters['customer_name']);
        }
        
        if (!empty($filters['start_date'])) {
            $this->db->where('DATE(s.sale_date) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('DATE(s.sale_date) <=', $filters['end_date']);
        }
        
        $this->db->order_by('s.sale_date', 'DESC');
        $this->db->limit(50);
        
        $sales = $this->db->get()->result_array();
        
        // Get items for each sale with return eligibility
        foreach ($sales as &$sale) {
            $sale['items'] = $this->get_sale_items_with_return_info($sale['id'], $filters);
        }
        
        return $sales;
    }
    
    /**
     * Get sale items with return information
     * @param int $sale_id
     * @param array $filters - product_name filter
     * @return array
     */
    private function get_sale_items_with_return_info($sale_id, $filters = []) {
        $this->db->select('si.*, p.name as product_name, p.sku, 
            COALESCE(SUM(ri.quantity_returned), 0) as already_returned,
            (si.quantity - COALESCE(SUM(ri.quantity_returned), 0)) as returnable_quantity');
        $this->db->from('inventory_sale_items si');
        $this->db->join('inventory_products p', 'p.id = si.product_id', 'left');
        $this->db->join('inventory_return_items ri', 'ri.sale_item_id = si.id', 'left');
        $this->db->where('si.sale_id', $sale_id);
        
        if (!empty($filters['product_name'])) {
            $this->db->like('p.name', $filters['product_name']);
        }
        
        $this->db->group_by('si.id');
        $this->db->having('returnable_quantity >', 0);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get complete sale information for return processing
     * @param int $sale_id
     * @return array|null
     */
    public function get_sale_return_info($sale_id) {
        // Get sale header
        $this->db->select('s.*, st.name as customer_name, st.student_id, a.name as served_by_name');
        $this->db->from('inventory_sales s');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->join('admin a', 'a.admin_id = s.served_by', 'left');
        $this->db->where('s.id', $sale_id);
        
        $sale = $this->db->get()->row_array();
        
        if (!$sale) {
            return null;
        }
        
        // Get sale items with return information
        $sale['items'] = $this->get_sale_items_with_return_info($sale_id);
        
        // Get return history for this sale
        $this->db->select('r.*, a.name as processed_by_name');
        $this->db->from('inventory_returns r');
        $this->db->join('admin a', 'a.admin_id = r.processed_by', 'left');
        $this->db->where('r.original_sale_id', $sale_id);
        $this->db->order_by('r.return_date', 'DESC');
        
        $sale['return_history'] = $this->db->get()->result_array();
        
        return $sale;
    }

    /**
     * Process a complete return transaction
     * @param array $return_data - Return header information
     * @param array $return_items - Array of items being returned
     * @return int|false - Return ID on success, false on failure
     */
    public function process_return($return_data, $return_items) {
        $this->db->trans_start();
        
        try {
            // Get customer info from original sale
            $original_sale = $this->db->get_where('inventory_sales', ['id' => $return_data['original_sale_id']])->row();
            $student_id_for_refund = $original_sale ? $original_sale->student_id : null;
            $customer_name_for_refund = null;
            
            // Get customer name for walk-in customers or students
            if($original_sale) {
                if($original_sale->customer_name) {
                    // Walk-in customer with custom name
                    $customer_name_for_refund = $original_sale->customer_name;
                } else if($original_sale->student_id) {
                    // Student customer - get student name
                    $student = $this->db->get_where('student', ['student_id' => $original_sale->student_id])->row();
                    $customer_name_for_refund = $student ? $student->name : 'Unknown Customer';
                } else {
                    // Walk-in without custom name
                    $customer_name_for_refund = 'Walk-in Customer';
                }
            }
            
            // Validate return quantities
            foreach ($return_items as $item) {
                $sale_item = $this->db->get_where('inventory_sale_items', ['id' => $item['sale_item_id']])->row();
                
                if (!$sale_item) {
                    throw new Exception('Sale item not found');
                }
                
                // Calculate already returned quantity
                $this->db->select_sum('quantity_returned');
                $this->db->where('sale_item_id', $item['sale_item_id']);
                $already_returned = $this->db->get('inventory_return_items')->row()->quantity_returned ?? 0;
                
                $available_to_return = $sale_item->quantity - $already_returned;
                
                if ($item['quantity'] > $available_to_return) {
                    throw new Exception('Cannot return more items than originally purchased');
                }
            }
            
            // Calculate total refund amount
            $total_refund = 0;
            foreach ($return_items as $item) {
                $total_refund += ($item['unit_price'] * $item['quantity']);
            }
            
            // Insert return header
            $return_data['total_refund_amount'] = $total_refund;
            $return_data['return_date'] = date('Y-m-d H:i:s');
            $return_data['processed_by'] = $this->session->userdata('admin_id');
            
            $this->db->insert('inventory_returns', $return_data);
            $return_id = $this->db->insert_id();
            
            // Insert return items and update stock + MODIFY ORIGINAL SALE
            foreach ($return_items as $item) {
                // Insert return item
                $return_item_data = [
                    'return_id' => $return_id,
                    'sale_item_id' => $item['sale_item_id'],
                    'product_id' => $item['product_id'],
                    'quantity_returned' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'refund_amount' => $item['unit_price'] * $item['quantity']
                ];
                $this->db->insert('inventory_return_items', $return_item_data);
                
                // **UPDATE ORIGINAL SALE ITEM - Reduce quantity**
                $sale_item = $this->db->get_where('inventory_sale_items', ['id' => $item['sale_item_id']])->row();
                $new_quantity = $sale_item->quantity - $item['quantity'];
                
                if ($new_quantity > 0) {
                    // Partially returned - update quantity and total
                    $new_total = $new_quantity * $sale_item->unit_price;
                    $this->db->where('id', $item['sale_item_id']);
                    $this->db->update('inventory_sale_items', [
                        'quantity' => $new_quantity,
                        'total_price' => $new_total
                    ]);
                } else {
                    // Fully returned - delete the item
                    $this->db->where('id', $item['sale_item_id']);
                    $this->db->delete('inventory_sale_items');
                }
                
                // Update product quantity (add back to stock)
                $this->db->set('quantity', 'quantity + ' . (int)$item['quantity'], FALSE);
                $this->db->where('id', $item['product_id']);
                $this->db->update('inventory_products');
                
                // Create stock movement record
                $movement = [
                    'product_id' => $item['product_id'],
                    'movement_type' => 'in',
                    'quantity' => $item['quantity'],
                    'notes' => 'Return from Sale #' . $return_data['original_sale_id'] . ' - Reason: ' . $return_data['return_reason'],
                    'performed_by' => $this->session->userdata('admin_id'),
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // **UPDATE ORIGINAL SALE TOTAL - Recalculate from remaining items**
            $this->db->select_sum('total_price');
            $this->db->where('sale_id', $return_data['original_sale_id']);
            $new_sale_total = $this->db->get('inventory_sale_items')->row()->total_price ?? 0;
            
            $this->db->where('id', $return_data['original_sale_id']);
            $this->db->update('inventory_sales', [
                'total_amount' => $new_sale_total
            ]);
            
            // Call financial integration for refund
            $this->load->library('Financial_integration_hooks');
            $refund_result = $this->financial_integration_hooks->record_refund([
                'transaction_type' => 'Inventory Refund',
                'amount' => $total_refund,
                'student_id' => $student_id_for_refund,
                'customer_name' => $customer_name_for_refund,
                'return_id' => $return_id,
                'reference' => 'Return #' . $return_id . ' for Sale #' . $return_data['original_sale_id'],
                'date' => date('Y-m-d'),
                'payment_method' => $return_data['refund_method'],
                'notes' => $return_data['return_notes'] ?? ''
            ]);
            
            if ($refund_result['status'] !== 'success') {
                throw new Exception('Financial integration failed: ' . ($refund_result['message'] ?? 'Unknown error'));
            }
            
            // Log audit trail
            $this->log_audit('return_processed', 'inventory_returns', $return_id, null, $return_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return false;
            }
            
            return $return_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Return processing failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get list of returns with filtering
     * @param array $filters - start_date, end_date, customer, reason, product_id, processed_by
     * @return array - Returns list with summary statistics
     */
    public function get_returns($filters = []) {
        $this->db->select('r.*, s.id as sale_id, st.name as customer_name, a.name as processed_by_name,
            COUNT(ri.id) as items_count');
        $this->db->from('inventory_returns r');
        $this->db->join('inventory_sales s', 's.id = r.original_sale_id', 'left');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->join('admin a', 'a.admin_id = r.processed_by', 'left');
        $this->db->join('inventory_return_items ri', 'ri.return_id = r.id', 'left');
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $this->db->where('DATE(r.return_date) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('DATE(r.return_date) <=', $filters['end_date']);
        }
        
        if (!empty($filters['customer'])) {
            $this->db->like('st.name', $filters['customer']);
        }
        
        if (!empty($filters['reason'])) {
            $this->db->where('r.return_reason', $filters['reason']);
        }
        
        if (!empty($filters['product_id'])) {
            $this->db->where('ri.product_id', $filters['product_id']);
        }
        
        if (!empty($filters['processed_by'])) {
            $this->db->where('r.processed_by', $filters['processed_by']);
        }
        
        $this->db->group_by('r.id');
        $this->db->order_by('r.return_date', 'DESC');
        
        $returns = $this->db->get()->result_array();
        
        // Calculate summary statistics
        $total_items = 0;
        foreach($returns as $return) {
            $total_items += (int)$return['items_count'];
        }
        
        $summary = [
            'total_count' => count($returns),
            'total_refund' => array_sum(array_column($returns, 'total_refund_amount')),
            'total_items' => $total_items
        ];
        
        return [
            'returns' => $returns,
            'summary' => $summary
        ];
    }

    /**
     * Get complete return information
     * @param int $return_id
     * @return array|null
     */
    public function get_return_details($return_id) {
        // Get return header
        $this->db->select('r.*, s.id as sale_id, s.sale_date, s.total_amount as sale_total,
            st.name as customer_name, st.student_id, a.name as processed_by_name');
        $this->db->from('inventory_returns r');
        $this->db->join('inventory_sales s', 's.id = r.original_sale_id', 'left');
        $this->db->join('student st', 'st.student_id = s.student_id', 'left');
        $this->db->join('admin a', 'a.admin_id = r.processed_by', 'left');
        $this->db->where('r.id', $return_id);
        
        $return = $this->db->get()->row_array();
        
        if (!$return) {
            return null;
        }
        
        // Get return items
        $this->db->select('ri.*, p.name as product_name, p.sku');
        $this->db->from('inventory_return_items ri');
        $this->db->join('inventory_products p', 'p.id = ri.product_id', 'left');
        $this->db->where('ri.return_id', $return_id);
        
        $return['items'] = $this->db->get()->result_array();
        
        // Get related stock movements
        $this->db->select('m.*, p.name as product_name');
        $this->db->from('inventory_stock_movements m');
        $this->db->join('inventory_products p', 'p.id = m.product_id', 'left');
        $this->db->where('m.movement_type', 'in');
        $this->db->like('m.notes', 'Return from Sale #' . $return['original_sale_id']);
        
        $return['stock_movements'] = $this->db->get()->result_array();
        
        return $return;
    }
    
    /**
     * Calculate return statistics for reporting
     * @param array $filters - start_date, end_date
     * @return array
     */
    public function get_return_statistics($filters = []) {
        // Check if inventory_categories table exists first
        $tables = $this->db->list_tables();
        $has_categories_table = in_array('inventory_categories', $tables);
        
        // Base query for date filtering
        if ($has_categories_table) {
            $this->db->select('r.*, ri.product_id, ri.quantity_returned, p.name as product_name, c.name as category_name');
        } else {
            $this->db->select('r.*, ri.product_id, ri.quantity_returned, p.name as product_name, NULL as category_name');
        }
        
        $this->db->from('inventory_returns r');
        $this->db->join('inventory_return_items ri', 'ri.return_id = r.id', 'left');
        $this->db->join('inventory_products p', 'p.id = ri.product_id', 'left');
        
        if ($has_categories_table) {
            $this->db->join('inventory_categories c', 'c.id = p.category_id', 'left');
        }
        
        if (!empty($filters['start_date'])) {
            $this->db->where('DATE(r.return_date) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('DATE(r.return_date) <=', $filters['end_date']);
        }
        
        $all_returns = $this->db->get()->result_array();
        
        // Calculate statistics
        $stats = [
            'total_returns_count' => 0,
            'total_refund_amount' => 0,
            'most_returned_products' => [],
            'returns_by_reason' => [],
            'return_rate_by_product' => []
        ];
        
        // Get unique returns count
        $return_ids = array_unique(array_column($all_returns, 'id'));
        $stats['total_returns_count'] = count($return_ids);
        
        // Calculate total refund amount (sum unique returns only)
        $unique_returns = [];
        foreach ($all_returns as $return) {
            if (!isset($unique_returns[$return['id']])) {
                $unique_returns[$return['id']] = $return['total_refund_amount'];
            }
        }
        $stats['total_refund_amount'] = array_sum($unique_returns);
        
        // Most returned products
        $product_returns = [];
        foreach ($all_returns as $item) {
            if (!isset($product_returns[$item['product_id']])) {
                $product_returns[$item['product_id']] = [
                    'product_name' => $item['product_name'],
                    'quantity' => 0
                ];
            }
            $product_returns[$item['product_id']]['quantity'] += $item['quantity_returned'];
        }
        arsort($product_returns);
        $stats['most_returned_products'] = array_slice($product_returns, 0, 10, true);
        
        // Returns by reason
        $reason_counts = [];
        foreach ($all_returns as $item) {
            $reason = $item['return_reason'];
            if (!isset($reason_counts[$reason])) {
                $reason_counts[$reason] = 0;
            }
            $reason_counts[$reason]++;
        }
        $stats['returns_by_reason'] = $reason_counts;
        
        return $stats;
    }
    
    /**
     * Helper function to get unique values by key
     */
    private function array_unique_key($array, $key, $value_key) {
        $unique = [];
        $seen = [];
        foreach ($array as $item) {
            if (!in_array($item[$key], $seen)) {
                $unique[] = $item[$value_key];
                $seen[] = $item[$key];
            }
        }
        return $unique;
    }

    // ============================================
    // PURCHASE ORDERS METHODS
    // ============================================
    
    /**
     * Generate unique PO code in format PO-YEAR-NUMBER (e.g., PO-2026-0001)
     * @param string $purchase_date - Purchase date to extract year
     * @return string - Generated PO code
     */
    private function generate_po_code($purchase_date) {
        $year = date('Y', strtotime($purchase_date));
        
        // Get the last PO code for this year
        $this->db->select('po_code');
        $this->db->from('inventory_purchases');
        $this->db->like('po_code', 'PO-' . $year . '-', 'after');
        $this->db->order_by('po_code', 'DESC');
        $this->db->limit(1);
        $last_po = $this->db->get()->row();
        
        if ($last_po && $last_po->po_code) {
            // Extract the number from the last PO code
            $parts = explode('-', $last_po->po_code);
            $last_number = isset($parts[2]) ? intval($parts[2]) : 0;
            $new_number = $last_number + 1;
        } else {
            // First PO for this year
            $new_number = 1;
        }
        
        // Format: PO-2026-0001
        return 'PO-' . $year . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create a new purchase order
     * @param array $po_data - Purchase order header information
     * @param array $po_items - Array of items being purchased
     * @return int|false - Purchase order ID on success, false on failure
     */
    public function create_purchase_order($po_data, $po_items) {
        $this->db->trans_start();
        
        try {
            // Validate required fields
            if (empty($po_items)) {
                throw new Exception('Purchase order must have at least one item');
            }
            
            // Calculate subtotal (before discount)
            $subtotal = 0;
            foreach ($po_items as $item) {
                $subtotal += ($item['cost_price'] * $item['quantity']);
            }
            
            // Calculate discount
            $discount_type = $po_data['discount_type'] ?? null;
            $discount_value = floatval($po_data['discount_value'] ?? 0);
            $discount_amount = 0;
            
            if ($discount_type === 'percentage' && $discount_value > 0) {
                $discount_amount = ($subtotal * $discount_value) / 100;
            } elseif ($discount_type === 'fixed' && $discount_value > 0) {
                $discount_amount = $discount_value;
            }
            
            // Calculate final total (after discount)
            $final_total = $subtotal - $discount_amount;
            
            // Generate PO code
            $po_code = $this->generate_po_code($po_data['purchase_date']);
            
            // Insert purchase order header - IMMEDIATELY MARK AS RECEIVED
            $po_data['po_code'] = $po_code;
            $po_data['subtotal'] = $subtotal;
            $po_data['discount_amount'] = $discount_amount;
            $po_data['final_total'] = $final_total;
            $po_data['total_amount'] = $final_total; // For compatibility
            $po_data['status'] = 'received'; // Changed from 'pending' to 'received'
            $po_data['created_by'] = $this->session->userdata('admin_id');
            $po_data['received_by'] = $this->session->userdata('admin_id'); // Same as creator
            $po_data['received_date'] = date('Y-m-d H:i:s'); // Mark received immediately
            $po_data['created_at'] = date('Y-m-d H:i:s');
            $po_data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->insert('inventory_purchases', $po_data);
            $purchase_id = $this->db->insert_id();
            
            // Insert purchase order items and UPDATE STOCK IMMEDIATELY
            foreach ($po_items as $item) {
                // Insert purchase item
                $purchase_item_data = [
                    'purchase_id' => $purchase_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'total_cost' => $item['cost_price'] * $item['quantity']
                ];
                $this->db->insert('inventory_purchase_items', $purchase_item_data);
                
                // Get current product info
                $product = $this->db->get_where('inventory_products', ['id' => $item['product_id']])->row();
                
                if ($product) {
                    // Update product stock and cost price
                    $new_quantity = $product->quantity + $item['quantity'];
                    $new_cost = $item['cost_price']; // Use purchase cost as new cost price
                    
                    $this->db->where('id', $item['product_id']);
                    $this->db->update('inventory_products', [
                        'quantity' => $new_quantity,
                        'cost_price' => $new_cost,
                        'last_restocked' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Create stock movement record
                    $movement = [
                        'product_id' => $item['product_id'],
                        'movement_type' => 'in',
                        'quantity' => $item['quantity'],
                        'notes' => 'Purchase Order ' . $po_code . ' from ' . ($po_data['supplier_id'] ? 'Supplier' : 'Market'),
                        'performed_by' => $this->session->userdata('admin_id'),
                        'movement_date' => date('Y-m-d H:i:s')
                    ];
                    $this->db->insert('inventory_stock_movements', $movement);
                }
            }
            
            // Record financial transaction IMMEDIATELY (use final_total after discount)
            $this->load->library('Financial_integration_hooks');
            $expense_result = $this->financial_integration_hooks->record_expense([
                'transaction_type' => 'Inventory Purchase',
                'purchase_id' => $purchase_id,
                'amount' => $final_total, // Use final total after discount
                'supplier_id' => $po_data['supplier_id'] ?? null,
                'reference' => 'Purchase Order ' . $po_code . ($discount_amount > 0 ? ' (Discount Applied)' : ''),
                'date' => $po_data['purchase_date'] ?? date('Y-m-d'),
                'category' => 'Inventory',
                'notes' => $po_data['notes'] ?? 'Auto-received purchase order'
            ]);
            
            if (!$expense_result['status'] || $expense_result['status'] !== 'success') {
                throw new Exception('Financial integration failed: ' . ($expense_result['message'] ?? 'Unknown error'));
            }
            
            // Log audit trail
            $this->log_audit('purchase_order_created_and_received', 'inventory_purchases', $purchase_id, null, $po_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return false;
            }
            
            return $purchase_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Purchase order creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get list of purchase orders with filtering
     * @param array $filters - start_date, end_date, supplier_id, status, payment_status, created_by
     * @return array - Purchase orders list with summary
     */
    public function get_purchase_orders($filters = []) {
        $this->db->select('p.*, s.name as supplier_name, a.name as created_by_name,
            COUNT(pi.id) as items_count');
        $this->db->from('inventory_purchases p');
        $this->db->join('inventory_suppliers s', 's.id = p.supplier_id', 'left');
        $this->db->join('admin a', 'a.admin_id = p.created_by', 'left');
        $this->db->join('inventory_purchase_items pi', 'pi.purchase_id = p.id', 'left');
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $this->db->where('p.purchase_date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('p.purchase_date <=', $filters['end_date']);
        }
        
        if (!empty($filters['supplier_id'])) {
            $this->db->where('p.supplier_id', $filters['supplier_id']);
        }
        
        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }
        
        if (!empty($filters['payment_status'])) {
            $this->db->where('p.payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['created_by'])) {
            $this->db->where('p.created_by', $filters['created_by']);
        }
        
        $this->db->group_by('p.id');
        $this->db->order_by('p.purchase_date', 'DESC');
        
        $purchase_orders = $this->db->get()->result_array();
        
        // Calculate summary statistics
        $summary = [
            'total_count' => count($purchase_orders),
            'total_amount' => array_sum(array_column($purchase_orders, 'total_amount')),
            'pending_count' => count(array_filter($purchase_orders, function($po) { return $po['status'] == 'pending'; })),
            'pending_amount' => array_sum(array_column(array_filter($purchase_orders, function($po) { return $po['status'] == 'pending'; }), 'total_amount'))
        ];
        
        return [
            'orders' => $purchase_orders,
            'summary' => $summary
        ];
    }

    /**
     * Get complete purchase order information
     * @param int $po_id
     * @return array|null
     */
    public function get_purchase_order_details($po_id) {
        // Get purchase order header
        $this->db->select('p.*, s.name as supplier_name, s.contact_person, s.phone, s.email,
            a1.name as created_by_name, a2.name as received_by_name');
        $this->db->from('inventory_purchases p');
        $this->db->join('inventory_suppliers s', 's.id = p.supplier_id', 'left');
        $this->db->join('admin a1', 'a1.admin_id = p.created_by', 'left');
        $this->db->join('admin a2', 'a2.admin_id = p.received_by', 'left');
        $this->db->where('p.id', $po_id);
        
        $purchase_order = $this->db->get()->row_array();
        
        if (!$purchase_order) {
            return null;
        }
        
        // Get purchase order items
        $this->db->select('pi.*, pr.name as product_name, pr.sku, pr.quantity as current_stock');
        $this->db->from('inventory_purchase_items pi');
        $this->db->join('inventory_products pr', 'pr.id = pi.product_id', 'left');
        $this->db->where('pi.purchase_id', $po_id);
        
        $purchase_order['items'] = $this->db->get()->result_array();
        
        // Get related stock movements (if received)
        if ($purchase_order['status'] == 'received') {
            $this->db->select('m.*, p.name as product_name');
            $this->db->from('inventory_stock_movements m');
            $this->db->join('inventory_products p', 'p.id = m.product_id', 'left');
            $this->db->where('m.movement_type', 'purchase');
            $this->db->like('m.notes', 'Purchase Order');
            $this->db->like('m.notes', $purchase_order['po_code']);
            
            $purchase_order['stock_movements'] = $this->db->get()->result_array();
        }
        
        // Get payments for this purchase order
        $purchase_order['payments'] = $this->get_purchase_payments($po_id);
        
        return $purchase_order;
    }
    
    /**
     * Mark purchase order as received and update stock
     * @param int $po_id
     * @param string $cost_update_method - 'replace', 'weighted_average', 'keep_existing'
     * @return bool
     */
    public function mark_purchase_received($po_id, $cost_update_method = 'replace') {
        $this->db->trans_start();
        
        try {
            // Verify PO is in pending status
            $po = $this->db->get_where('inventory_purchases', ['id' => $po_id])->row();
            
            if (!$po || $po->status != 'pending') {
                throw new Exception('Purchase order is not in pending status');
            }
            
            // Get all purchase items
            $this->db->select('pi.*, p.quantity as current_quantity, p.cost_price as current_cost');
            $this->db->from('inventory_purchase_items pi');
            $this->db->join('inventory_products p', 'p.id = pi.product_id', 'left');
            $this->db->where('pi.purchase_id', $po_id);
            
            $items = $this->db->get()->result_array();
            
            // Update stock for each item
            foreach ($items as $item) {
                $new_quantity = $item['current_quantity'] + $item['quantity'];
                $new_cost = $item['cost_price'];
                
                // Calculate cost price based on method
                if ($cost_update_method == 'weighted_average') {
                    $new_cost = $this->calculate_weighted_average_cost(
                        $item['product_id'],
                        $item['quantity'],
                        $item['cost_price']
                    );
                } elseif ($cost_update_method == 'keep_existing') {
                    $new_cost = $item['current_cost'];
                }
                
                // Update product
                $this->db->where('id', $item['product_id']);
                $this->db->update('inventory_products', [
                    'quantity' => $new_quantity,
                    'cost_price' => $new_cost,
                    'last_restocked' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Create stock movement record
                $movement = [
                    'product_id' => $item['product_id'],
                    'movement_type' => 'in',
                    'quantity' => $item['quantity'],
                    'notes' => 'Purchase Order #' . $po_id . ' from ' . ($po->supplier_id ? 'Supplier' : 'Market'),
                    'performed_by' => $this->session->userdata('admin_id'),
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // Update purchase order status
            $this->db->where('id', $po_id);
            $this->db->update('inventory_purchases', [
                'status' => 'received',
                'received_by' => $this->session->userdata('admin_id'),
                'received_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Call financial integration for expense recording
            $this->load->library('Financial_integration_hooks');
            $expense_result = $this->financial_integration_hooks->record_expense([
                'transaction_type' => 'Inventory Purchase',
                'purchase_id' => $po_id, // FIX: Add purchase_id for source_id
                'amount' => $po->total_amount,
                'supplier_id' => $po->supplier_id,
                'reference' => 'Purchase Order #' . $po_id . ($po->reference_number ? ' - Ref: ' . $po->reference_number : ''),
                'date' => date('Y-m-d'),
                'category' => 'Inventory',
                'notes' => $po->notes ?? ''
            ]);
            
            if (!$expense_result['success']) {
                throw new Exception('Financial integration failed: ' . $expense_result['message']);
            }
            
            // Log audit trail
            $this->log_audit('purchase_order_received', 'inventory_purchases', $po_id, ['status' => 'pending'], ['status' => 'received']);
            
            $this->db->trans_complete();
            
            return $this->db->trans_status();
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Purchase order receipt failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a purchase order
     * @param int $po_id
     * @return bool
     */
    public function cancel_purchase_order($po_id) {
        // Verify PO is in pending status
        $po = $this->db->get_where('inventory_purchases', ['id' => $po_id])->row();
        
        if (!$po || $po->status != 'pending') {
            return false;
        }
        
        // Update status to cancelled
        $this->db->where('id', $po_id);
        $result = $this->db->update('inventory_purchases', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $this->log_audit('purchase_order_cancelled', 'inventory_purchases', $po_id, ['status' => 'pending'], ['status' => 'cancelled']);
        }
        
        return $result;
    }
    
    /**
     * Update an existing purchase order
     * Updates items, quantities, and re-processes stock and financial records
     * @param int $po_id - Purchase order ID to update
     * @param array $po_data - Updated purchase order header data
     * @param array $po_items - Updated purchase order items
     * @return bool - True on success, false on failure
     */
    public function update_purchase_order($po_id, $po_data, $po_items) {
        $this->db->trans_start();
        
        try {
            // Validate required fields
            if (empty($po_items)) {
                throw new Exception('Purchase order must have at least one item');
            }
            
            // Get existing purchase order
            $existing_po = $this->db->get_where('inventory_purchases', ['id' => $po_id])->row();
            
            if (!$existing_po) {
                throw new Exception('Purchase order not found');
            }
            
            // Get existing items
            $existing_items = $this->db->get_where('inventory_purchase_items', ['purchase_id' => $po_id])->result_array();
            
            // STEP 1: Reverse existing stock quantities
            foreach ($existing_items as $item) {
                $product = $this->db->get_where('inventory_products', ['id' => $item['product_id']])->row();
                
                if ($product) {
                    // Reduce quantity
                    $new_quantity = $product->quantity - $item['quantity'];
                    if ($new_quantity < 0) {
                        throw new Exception('Cannot update: Some stock has already been sold');
                    }
                    
                    $this->db->where('id', $item['product_id']);
                    $this->db->update('inventory_products', [
                        'quantity' => $new_quantity,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Create reversal stock movement
                    $movement = [
                        'product_id' => $item['product_id'],
                        'movement_type' => 'out',
                        'quantity' => $item['quantity'],
                        'notes' => 'Reversal for edit: PO ' . $existing_po->po_code,
                        'performed_by' => $this->session->userdata('admin_id'),
                        'movement_date' => date('Y-m-d H:i:s')
                    ];
                    $this->db->insert('inventory_stock_movements', $movement);
                }
            }
            
            // STEP 2: Delete existing purchase items
            $this->db->where('purchase_id', $po_id);
            $this->db->delete('inventory_purchase_items');
            
            // STEP 3: Calculate new totals (subtotal, discount, final total)
            $subtotal = 0;
            foreach ($po_items as $item) {
                $subtotal += ($item['cost_price'] * $item['quantity']);
            }
            
            // Calculate discount
            $discount_type = $po_data['discount_type'] ?? null;
            $discount_value = floatval($po_data['discount_value'] ?? 0);
            $discount_amount = 0;
            
            if ($discount_type === 'percentage' && $discount_value > 0) {
                $discount_amount = ($subtotal * $discount_value) / 100;
            } elseif ($discount_type === 'fixed' && $discount_value > 0) {
                $discount_amount = $discount_value;
            }
            
            $final_total = $subtotal - $discount_amount;
            
            // STEP 4: Update purchase order header
            $po_data['subtotal'] = $subtotal;
            $po_data['discount_amount'] = $discount_amount;
            $po_data['final_total'] = $final_total;
            $po_data['total_amount'] = $final_total; // For compatibility
            $po_data['updated_at'] = date('Y-m-d H:i:s');
            // Keep existing status and po_code
            unset($po_data['status'], $po_data['po_code']);
            
            $this->db->where('id', $po_id);
            $this->db->update('inventory_purchases', $po_data);
            
            // STEP 5: Insert new purchase items and update stock
            foreach ($po_items as $item) {
                // Insert purchase item
                $purchase_item_data = [
                    'purchase_id' => $po_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'total_cost' => $item['cost_price'] * $item['quantity']
                ];
                $this->db->insert('inventory_purchase_items', $purchase_item_data);
                
                // Get current product info
                $product = $this->db->get_where('inventory_products', ['id' => $item['product_id']])->row();
                
                if ($product) {
                    // Update product stock and cost price
                    $new_quantity = $product->quantity + $item['quantity'];
                    $new_cost = $item['cost_price'];
                    
                    $this->db->where('id', $item['product_id']);
                    $this->db->update('inventory_products', [
                        'quantity' => $new_quantity,
                        'cost_price' => $new_cost,
                        'last_restocked' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Create stock movement record
                    $movement = [
                        'product_id' => $item['product_id'],
                        'movement_type' => 'in',
                        'quantity' => $item['quantity'],
                        'notes' => 'Updated Purchase Order ' . $existing_po->po_code,
                        'performed_by' => $this->session->userdata('admin_id'),
                        'movement_date' => date('Y-m-d H:i:s')
                    ];
                    $this->db->insert('inventory_stock_movements', $movement);
                }
            }
            
            // STEP 6: Update financial transaction
            // Find existing journal entry
            $this->db->where('source_type', 'inventory_purchase');
            $this->db->where('source_id', $po_id);
            $journal_entry = $this->db->get('journal_entries')->row();
            
            if ($journal_entry && isset($journal_entry->id)) {
                // Delete old journal entry lines
                $this->db->where('entry_id', $journal_entry->id);
                $this->db->delete('journal_entry_lines');
                
                // Update journal entry with new description
                $this->db->where('id', $journal_entry->id);
                $this->db->update('journal_entries', [
                    'description' => 'Updated Purchase Order ' . $existing_po->po_code . ($discount_amount > 0 ? ' (Discount Applied)' : ''),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Re-create journal entry lines with new amount
                $this->load->library('Financial_integration_hooks');
                $this->financial_integration_hooks->update_journal_entry_lines($journal_entry->id, $final_total);
            }
            
            // Log audit trail
            $this->log_audit('purchase_order_updated', 'inventory_purchases', $po_id, (array)$existing_po, $po_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Purchase order update failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a purchase order (only if pending or cancelled)
     * @param int $po_id
     * @return bool
     */
    public function delete_purchase_order($po_id) {
        log_message('info', '=== Starting PO deletion for ID: ' . $po_id);
        
        // Increase execution time for deletion operations (max 60 seconds)
        set_time_limit(60);
        
        try {
            // Start explicit transaction
            $this->db->trans_begin();
            log_message('info', '=== Transaction started');
            
            // Get purchase order details
            $po = $this->db->get_where('inventory_purchases', ['id' => $po_id])->row();
            log_message('info', 'PO found: ' . ($po ? 'YES' : 'NO'));
            
            if (!$po) {
                throw new Exception('Purchase order not found');
            }
            
            log_message('info', 'PO Status: ' . $po->status);
            
            // Get any payments recorded (will be deleted as part of reversal)
            $payments = $this->db->get_where('inventory_purchase_payments', ['purchase_id' => $po_id])->result();
            log_message('info', 'Payments found: ' . count($payments));
            
            // Get all purchase items to reverse stock
            $items = $this->db->get_where('inventory_purchase_items', ['purchase_id' => $po_id])->result();
            log_message('info', 'Items found: ' . count($items));
            
            // Reverse stock levels if order was received
            if ($po->status == 'received') {
                foreach ($items as $item) {
                    $product = $this->db->get_where('inventory_products', ['id' => $item->product_id])->row();
                    
                    if ($product) {
                        // Calculate what the new quantity would be after reversal
                        $new_quantity = $product->quantity - $item->quantity;
                        
                        // Only prevent deletion if reversing would result in negative stock
                        if ($new_quantity < 0) {
                            throw new Exception('Cannot delete: Reversing purchase of "' . $product->name . '" would result in negative stock. Current stock: ' . $product->quantity . ', Purchase quantity: ' . $item->quantity);
                        }
                        
                        $this->db->where('id', $item->product_id);
                        $this->db->update('inventory_products', [
                            'quantity' => $new_quantity,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        // Create reversal stock movement record
                        $movement = [
                            'product_id' => $item->product_id,
                            'movement_type' => 'out',
                            'quantity' => $item->quantity,
                            'notes' => 'Reversal: Purchase Order ' . $po->po_code . ' deleted',
                            'performed_by' => $this->session->userdata('admin_id'),
                            'movement_date' => date('Y-m-d H:i:s')
                        ];
                        $this->db->insert('inventory_stock_movements', $movement);
                    }
                }
                
                // Reverse financial transaction
                // Delete journal entry and lines
                $this->db->where('source_type', 'inventory_purchase');
                $this->db->where('source_id', $po_id);
                $journal_entry = $this->db->get('journal_entries')->row();
                
                if ($journal_entry) {
                    // Delete journal entry lines
                    $this->db->where('entry_id', $journal_entry->entry_id);
                    $this->db->delete('journal_entry_lines');
                    
                    // Delete journal entry
                    $this->db->where('entry_id', $journal_entry->entry_id);
                    $this->db->delete('journal_entries');
                }
            }
            
            // Delete payment records if any exist
            if (!empty($payments)) {
                // First, delete from the payment table (expenditure records)
                foreach ($payments as $payment) {
                    if (isset($payment->expenditure_payment_id) && !empty($payment->expenditure_payment_id)) {
                        $this->db->where('payment_id', $payment->expenditure_payment_id);
                        $this->db->delete('payment');
                        log_message('info', '=== Deleted payment table record: ' . $payment->expenditure_payment_id);
                    }
                }
                
                // Then delete purchase payment records (FK will auto-set expenditure_payment_id to NULL if needed)
                $this->db->where('purchase_id', $po_id);
                $this->db->delete('inventory_purchase_payments');
                log_message('info', '=== Deleted ' . count($payments) . ' purchase payment records');
            }
            
            // Delete purchase order items - use query builder to maintain deletion tracking
            $this->db->where('purchase_id', $po_id);
            $this->db->delete('inventory_purchase_items');
            log_message('info', '=== Deleted purchase items');
            
            // Delete purchase order
            $this->db->where('id', $po_id);
            $this->db->delete('inventory_purchases');
            log_message('info', '=== Deleted purchase order record');
            
            // Commit transaction IMMEDIATELY - no audit logging to slow things down
            $this->db->trans_commit();
            log_message('info', '=== Transaction committed successfully');
            
            // Log audit trail AFTER transaction completes (non-blocking)
            try {
                $this->log_audit('purchase_order_deleted', 'inventory_purchases', $po_id, (array)$po, null);
            } catch (Exception $audit_error) {
                log_message('warning', 'Audit logging failed (non-critical): ' . $audit_error->getMessage());
            }
            
            log_message('info', '=== PO deletion completed successfully');
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->trans_rollback();
            log_message('error', '=== Purchase order deletion failed: ' . $e->getMessage());
            log_message('error', '=== Stack trace: ' . $e->getTraceAsString());
            // Return array with error message instead of just false
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate weighted average cost for a product
     * @param int $product_id
     * @param int $new_quantity
     * @param float $new_cost
     * @return float
     */
    public function calculate_weighted_average_cost($product_id, $new_quantity, $new_cost) {
        $product = $this->db->get_where('inventory_products', ['id' => $product_id])->row();
        
        if (!$product) {
            return $new_cost;
        }
        
        $old_quantity = $product->quantity;
        $old_cost = $product->cost_price;
        
        $total_quantity = $old_quantity + $new_quantity;
        
        if ($total_quantity == 0) {
            return $new_cost;
        }
        
        $weighted_average = (($old_quantity * $old_cost) + ($new_quantity * $new_cost)) / $total_quantity;
        
        return round($weighted_average, 2);
    }
    
    /**
     * Calculate purchase statistics for reporting
     * @param array $filters - start_date, end_date
     * @return array
     */
    public function get_purchase_statistics($filters = []) {
        $this->db->select('p.*, pi.product_id, pi.quantity, pi.cost_price, pr.name as product_name,
            s.name as supplier_name');
        $this->db->from('inventory_purchases p');
        $this->db->join('inventory_purchase_items pi', 'pi.purchase_id = p.id', 'left');
        $this->db->join('inventory_products pr', 'pr.id = pi.product_id', 'left');
        $this->db->join('inventory_suppliers s', 's.id = p.supplier_id', 'left');
        
        if (!empty($filters['start_date'])) {
            $this->db->where('p.purchase_date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('p.purchase_date <=', $filters['end_date']);
        }
        
        $all_purchases = $this->db->get()->result_array();
        
        // Calculate statistics
        $stats = [
            'total_purchases_count' => 0,
            'total_purchase_amount' => 0,
            'average_purchase_value' => 0,
            'most_purchased_products' => [],
            'purchases_by_supplier' => [],
            'cost_trends' => []
        ];
        
        // Get unique purchases count
        $purchase_ids = array_unique(array_column($all_purchases, 'id'));
        $stats['total_purchases_count'] = count($purchase_ids);
        
        // Calculate total purchase amount
        $unique_purchases = [];
        foreach ($all_purchases as $item) {
            if (!isset($unique_purchases[$item['id']])) {
                $unique_purchases[$item['id']] = $item['total_amount'];
            }
        }
        $stats['total_purchase_amount'] = array_sum($unique_purchases);
        $stats['average_purchase_value'] = $stats['total_purchases_count'] > 0 ? 
            $stats['total_purchase_amount'] / $stats['total_purchases_count'] : 0;
        
        // Most purchased products
        $product_purchases = [];
        foreach ($all_purchases as $item) {
            if (!isset($product_purchases[$item['product_id']])) {
                $product_purchases[$item['product_id']] = [
                    'product_name' => $item['product_name'],
                    'quantity' => 0
                ];
            }
            $product_purchases[$item['product_id']]['quantity'] += $item['quantity'];
        }
        arsort($product_purchases);
        $stats['most_purchased_products'] = array_slice($product_purchases, 0, 10, true);
        
        // Purchases by supplier
        $supplier_purchases = [];
        foreach ($all_purchases as $item) {
            $supplier = $item['supplier_name'] ?? 'No Supplier';
            if (!isset($supplier_purchases[$supplier])) {
                $supplier_purchases[$supplier] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            if (!isset($unique_purchases['seen_' . $item['id']])) {
                $supplier_purchases[$supplier]['count']++;
                $supplier_purchases[$supplier]['amount'] += $item['total_amount'];
                $unique_purchases['seen_' . $item['id']] = true;
            }
        }
        $stats['purchases_by_supplier'] = $supplier_purchases;
        
        return $stats;
    }
    
    // ============================================
    // ENHANCED SUPPLIER METHODS
    // ============================================
    
    /**
     * Deactivate a supplier (instead of deleting)
     * @param int $supplier_id
     * @return bool
     */
    public function deactivate_supplier($supplier_id) {
        $result = $this->db->where('id', $supplier_id)->update('inventory_suppliers', [
            'status' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $this->log_audit('supplier_deactivated', 'inventory_suppliers', $supplier_id, ['status' => 1], ['status' => 0]);
        }
        
        return $result;
    }
    
    /**
     * Get complete supplier information
     * @param int $supplier_id
     * @return array|null
     */
    public function get_supplier_details($supplier_id) {
        // Get supplier info
        $supplier = $this->db->get_where('inventory_suppliers', ['id' => $supplier_id])->row_array();
        
        if (!$supplier) {
            return null;
        }
        
        // Count products from this supplier
        $supplier['product_count'] = $this->db->where('supplier_id', $supplier_id)->count_all_results('inventory_products');
        
        // Get purchase order history
        $this->db->select('p.*, COUNT(pi.id) as items_count');
        $this->db->from('inventory_purchases p');
        $this->db->join('inventory_purchase_items pi', 'pi.purchase_id = p.id', 'left');
        $this->db->where('p.supplier_id', $supplier_id);
        $this->db->group_by('p.id');
        $this->db->order_by('p.purchase_date', 'DESC');
        $this->db->limit(10);
        
        $supplier['recent_purchases'] = $this->db->get()->result_array();
        
        // Calculate total purchase amount
        $this->db->select_sum('total_amount');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('status', 'received');
        $supplier['total_purchase_amount'] = $this->db->get('inventory_purchases')->row()->total_amount ?? 0;
        
        // Get last purchase date
        $this->db->select('purchase_date');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->order_by('purchase_date', 'DESC');
        $this->db->limit(1);
        $last_purchase = $this->db->get('inventory_purchases')->row();
        $supplier['last_purchase_date'] = $last_purchase ? $last_purchase->purchase_date : null;
        
        return $supplier;
    }
    
    // ============================================
    // PURCHASE ORDER PAYMENT METHODS
    // ============================================
    
    /**
     * Record a payment for a purchase order
     * @param array $data Payment data (purchase_id, payment_date, amount, payment_method_id, reference_number, notes)
     * @return array Result with status, message, and payment details
     */
    public function record_purchase_payment($data) {
        $this->db->trans_start();
        
        try {
            // Validate purchase order exists
            $purchase = $this->db->get_where('inventory_purchases', ['id' => $data['purchase_id']])->row();
            
            if (!$purchase) {
                throw new Exception('Purchase order not found');
            }
            
            // Validate purchase order is not cancelled
            if ($purchase->status === 'cancelled') {
                throw new Exception('Cannot record payment for cancelled purchase order');
            }
            
            // Calculate outstanding balance
            $outstanding_balance = $purchase->total_amount - $purchase->amount_paid;
            
            // Validate payment amount
            if ($data['amount'] <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }
            
            if ($data['amount'] > $outstanding_balance) {
                throw new Exception('Payment amount cannot exceed outstanding balance of GHS ' . number_format($outstanding_balance, 2));
            }
            
            // Validate payment method exists
            $payment_method = $this->db->get_where('payment_methods', ['id' => $data['payment_method_id']])->row();
            if (!$payment_method) {
                throw new Exception('Invalid payment method');
            }
            
            // Validate payment date is not in future
            $payment_date = strtotime($data['payment_date']);
            $today = strtotime(date('Y-m-d'));
            
            if ($payment_date > $today) {
                throw new Exception('Payment date cannot be in the future');
            }
            
            // Check for potential duplicate payment
            $this->db->where('purchase_id', $data['purchase_id']);
            $this->db->where('payment_date', $data['payment_date']);
            $this->db->where('amount', $data['amount']);
            if (!empty($data['reference_number'])) {
                $this->db->where('reference_number', $data['reference_number']);
            }
            $duplicate = $this->db->get('inventory_purchase_payments')->row();
            
            if ($duplicate) {
                throw new Exception('A similar payment already exists. Please verify before proceeding.');
            }
            
            // Get supplier name for expenditure notes
            $supplier = $this->db->get_where('inventory_suppliers', ['id' => $purchase->supplier_id])->row();
            $supplier_name = $supplier ? $supplier->name : 'Unknown Supplier';
            
            // Create expenditure entry in payment table
            $expenditure_data = [
                'payment_type' => 'expense',
                'title' => 'Inventory Purchases',
                'amount' => $data['amount'],
                'day_timestamp' => strtotime($data['payment_date']),
                'payment_method' => $payment_method->name,
                'description' => 'Purchase Order #' . $data['purchase_id'] . ' - ' . $supplier_name,
                'can_delete' => 'declined',
                'timestamp' => time()
            ];
            
            $this->db->insert('payment', $expenditure_data);
            $expenditure_payment_id = $this->db->insert_id();
            
            // Insert payment record
            $payment_record = [
                'purchase_id' => $data['purchase_id'],
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $this->session->userdata('admin_id'),
                'expenditure_payment_id' => $expenditure_payment_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('inventory_purchase_payments', $payment_record);
            $payment_id = $this->db->insert_id();
            
            // Update purchase order payment status
            $new_amount_paid = $purchase->amount_paid + $data['amount'];
            $new_status = $this->calculate_payment_status($data['purchase_id']);
            
            $this->db->where('id', $data['purchase_id']);
            $this->db->update('inventory_purchases', [
                'amount_paid' => $new_amount_paid,
                'payment_status' => $new_status,
                'last_payment_date' => $data['payment_date']
            ]);
            
            // Log audit trail
            $this->log_audit('purchase_payment_recorded', 'inventory_purchase_payments', $payment_id, null, [
                'purchase_id' => $data['purchase_id'],
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'],
                'recorded_by' => $this->session->userdata('admin_id')
            ]);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            return [
                'status' => 'success',
                'message' => 'Payment recorded successfully',
                'data' => [
                    'payment_id' => $payment_id,
                    'new_status' => $new_status,
                    'outstanding_balance' => $purchase->total_amount - $new_amount_paid
                ]
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Purchase payment recording failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate and return payment status for a purchase order
     * @param int $purchase_id
     * @return string Payment status (unpaid, partially_paid, fully_paid)
     */
    private function calculate_payment_status($purchase_id) {
        $purchase = $this->db->get_where('inventory_purchases', ['id' => $purchase_id])->row();
        
        if (!$purchase) {
            return 'unpaid';
        }
        
        // Calculate total amount paid
        $this->db->select_sum('amount');
        $this->db->where('purchase_id', $purchase_id);
        $total_paid = $this->db->get('inventory_purchase_payments')->row()->amount ?? 0;
        
        // Determine status based on amount paid
        if ($total_paid == 0) {
            return 'unpaid';
        } elseif ($total_paid >= $purchase->total_amount) {
            return 'fully_paid';
        } else {
            return 'partially_paid';
        }
    }
    
    /**
     * Get payment history for a purchase order
     * @param int $purchase_id
     * @return array Payment history with running balance
     */
    public function get_purchase_payments($purchase_id) {
        // Get purchase order details
        $purchase = $this->db->get_where('inventory_purchases', ['id' => $purchase_id])->row();
        
        if (!$purchase) {
            return [];
        }
        
        // Get payment history
        $this->db->select('pp.*, pm.name as method_name, a.name as recorded_by_name');
        $this->db->from('inventory_purchase_payments pp');
        $this->db->join('payment_methods pm', 'pm.id = pp.payment_method_id', 'left');
        $this->db->join('admin a', 'a.admin_id = pp.recorded_by', 'left');
        $this->db->where('pp.purchase_id', $purchase_id);
        $this->db->order_by('pp.payment_date', 'ASC');
        $this->db->order_by('pp.created_at', 'ASC');
        
        $payments = $this->db->get()->result_array();
        
        // Calculate running balance for each payment
        $running_balance = $purchase->total_amount;
        foreach ($payments as &$payment) {
            $running_balance -= $payment['amount'];
            $payment['running_balance'] = $running_balance;
        }
        
        return $payments;
    }
    
    /**
     * Get outstanding payables report
     * @param array $filters Optional filters (supplier_id, aging, start_date, end_date)
     * @return array Payables list with summary
     */
    public function get_outstanding_payables($filters = []) {
        $this->db->select('p.id as purchase_id, p.purchase_date, p.total_amount, p.amount_paid, 
            p.payment_status, p.last_payment_date,
            s.name as supplier_name, s.id as supplier_id,
            DATEDIFF(CURDATE(), p.purchase_date) as days_outstanding');
        $this->db->from('inventory_purchases p');
        $this->db->join('inventory_suppliers s', 's.id = p.supplier_id', 'left');
        $this->db->where_in('p.payment_status', ['unpaid', 'partially_paid']);
        $this->db->where('p.status !=', 'cancelled');
        
        // Apply filters
        if (!empty($filters['supplier_id'])) {
            $this->db->where('p.supplier_id', $filters['supplier_id']);
        }
        
        if (!empty($filters['start_date'])) {
            $this->db->where('p.purchase_date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('p.purchase_date <=', $filters['end_date']);
        }
        
        $this->db->order_by('p.purchase_date', 'ASC');
        
        $payables = $this->db->get()->result_array();
        
        // Calculate outstanding balance and aging category for each
        foreach ($payables as &$payable) {
            $payable['outstanding_balance'] = $payable['total_amount'] - $payable['amount_paid'];
            
            // Determine aging category
            $days = $payable['days_outstanding'];
            if ($days <= 30) {
                $payable['aging_category'] = '0-30 days';
            } elseif ($days <= 60) {
                $payable['aging_category'] = '31-60 days';
            } else {
                $payable['aging_category'] = '60+ days';
            }
        }
        
        // Apply aging filter if specified
        if (!empty($filters['aging'])) {
            $payables = array_filter($payables, function($p) use ($filters) {
                $days = $p['days_outstanding'];
                switch($filters['aging']) {
                    case '0-30':
                        return $days <= 30;
                    case '31-60':
                        return $days > 30 && $days <= 60;
                    case '60+':
                        return $days > 60;
                    default:
                        return true;
                }
            });
        }
        
        // Calculate summary statistics
        $summary = [
            'total_outstanding' => array_sum(array_column($payables, 'outstanding_balance')),
            'count' => count($payables),
            'aging_0_30' => 0,
            'aging_31_60' => 0,
            'aging_60_plus' => 0
        ];
        
        foreach ($payables as $payable) {
            $days = $payable['days_outstanding'];
            if ($days <= 30) {
                $summary['aging_0_30'] += $payable['outstanding_balance'];
            } elseif ($days <= 60) {
                $summary['aging_31_60'] += $payable['outstanding_balance'];
            } else {
                $summary['aging_60_plus'] += $payable['outstanding_balance'];
            }
        }
        
        return [
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'payables' => array_values($payables) // Re-index array after filtering
            ]
        ];
    }
    
    /**
     * Get total purchase order payments for date range (for financial reports)
     * @param string $start_date Y-m-d format
     * @param string $end_date Y-m-d format
     * @return float Total amount paid
     */
    public function get_purchase_order_expenditure($start_date, $end_date) {
        $this->db->select_sum('amount');
        $this->db->from('inventory_purchase_payments');
        $this->db->where('DATE(payment_date) >=', $start_date);
        $this->db->where('DATE(payment_date) <=', $end_date);
        
        $result = $this->db->get()->row();
        return $result->amount ?? 0;
    }
    
    /**
     * Delete a payment (Super Admin only)
     * @param int $payment_id
     * @param string $reason Reason for deletion
     * @return array Result with status and message
     */
    public function delete_purchase_payment($payment_id, $reason) {
        $this->db->trans_start();
        
        try {
            // Get payment details
            $payment = $this->db->get_where('inventory_purchase_payments', ['id' => $payment_id])->row();
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Get purchase order
            $purchase = $this->db->get_where('inventory_purchases', ['id' => $payment->purchase_id])->row();
            
            if (!$purchase) {
                throw new Exception('Purchase order not found');
            }
            
            // Delete expenditure entry from payment table
            if ($payment->expenditure_payment_id) {
                $this->db->where('payment_id', $payment->expenditure_payment_id);
                $this->db->delete('payment');
            }
            
            // Log audit trail before deletion
            $this->log_audit('purchase_payment_deleted', 'inventory_purchase_payments', $payment_id, 
                (array)$payment, [
                    'deleted_by' => $this->session->userdata('admin_id'),
                    'reason' => $reason
                ]
            );
            
            // Delete payment record
            $this->db->where('id', $payment_id);
            $this->db->delete('inventory_purchase_payments');
            
            // Recalculate payment status
            $new_status = $this->calculate_payment_status($payment->purchase_id);
            
            // Calculate new amount paid
            $this->db->select_sum('amount');
            $this->db->where('purchase_id', $payment->purchase_id);
            $new_amount_paid = $this->db->get('inventory_purchase_payments')->row()->amount ?? 0;
            
            // Get last payment date
            $this->db->select('payment_date');
            $this->db->where('purchase_id', $payment->purchase_id);
            $this->db->order_by('payment_date', 'DESC');
            $this->db->limit(1);
            $last_payment = $this->db->get('inventory_purchase_payments')->row();
            $last_payment_date = $last_payment ? $last_payment->payment_date : null;
            
            // Update purchase order
            $this->db->where('id', $payment->purchase_id);
            $this->db->update('inventory_purchases', [
                'amount_paid' => $new_amount_paid,
                'payment_status' => $new_status,
                'last_payment_date' => $last_payment_date
            ]);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            return [
                'status' => 'success',
                'message' => 'Payment deleted successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Purchase payment deletion failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get outstanding payables summary for dashboard
     * @return array Summary with total outstanding and count
     */
    public function get_outstanding_payables_summary() {
        $this->db->select('COUNT(*) as count, SUM(total_amount - amount_paid) as total_outstanding');
        $this->db->from('inventory_purchases');
        $this->db->where_in('payment_status', ['unpaid', 'partially_paid']);
        $this->db->where('status !=', 'cancelled');
        
        $result = $this->db->get()->row();
        
        return [
            'count' => $result->count ?? 0,
            'total_outstanding' => $result->total_outstanding ?? 0
        ];
    }
}
