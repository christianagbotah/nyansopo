<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {
    
    // Get dashboard statistics
    public function get_dashboard_stats() {
        $stats = [
            'total_products' => $this->db->count_all('inventory_products'),
            'low_stock' => $this->db->where('quantity <= reorder_level')->where('status', 1)->count_all_results('inventory_products'),
            'today_sales' => $this->db->where('DATE(sale_date)', date('Y-m-d'))->select_sum('total_amount')->get('inventory_sales')->row()->total_amount ?? 0,
            'inventory_value' => $this->db->select('SUM(cost_price * quantity) as value')->where('status', 1)->get('inventory_products')->row()->value ?? 0
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
    
    // Get sales history
    public function get_sales() {
        return $this->db->select('s.*, st.name as student_name, a.name as admin_name')
            ->from('inventory_sales s')
            ->join('student st', 'st.student_id = s.student_id', 'left')
            ->join('admin a', 'a.admin_id = s.served_by', 'left')
            ->order_by('s.id', 'DESC')
            ->get()->result();
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
            
            $this->db->set('quantity', 'quantity - ' . $item['quantity'], FALSE)
                ->where('id', $item['product_id'])
                ->update('inventory_products');
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
        $data = [
            'action_type' => $action_type,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'old_values' => json_encode($old_values),
            'new_values' => json_encode($new_values),
            'performed_by' => $this->session->userdata('admin_id'),
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
            
            // Insert return items and update stock
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
                
                // Update product quantity (add back to stock)
                $this->db->set('quantity', 'quantity + ' . (int)$item['quantity'], FALSE);
                $this->db->where('id', $item['product_id']);
                $this->db->update('inventory_products');
                
                // Create stock movement record
                $movement = [
                    'product_id' => $item['product_id'],
                    'movement_type' => 'return',
                    'quantity' => $item['quantity'],
                    'notes' => 'Return from Sale #' . $return_data['original_sale_id'] . ' - Reason: ' . $return_data['return_reason'],
                    'performed_by' => $this->session->userdata('admin_id'),
                    'movement_date' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('inventory_stock_movements', $movement);
            }
            
            // Call financial integration for refund
            $this->load->library('Financial_integration_hooks');
            $refund_result = $this->financial_integration_hooks->record_refund([
                'transaction_type' => 'Inventory Refund',
                'amount' => $total_refund,
                'student_id' => $return_data['student_id'] ?? null,
                'reference' => 'Return #' . $return_id . ' for Sale #' . $return_data['original_sale_id'],
                'date' => date('Y-m-d'),
                'payment_method' => $return_data['refund_method'],
                'notes' => $return_data['return_notes'] ?? ''
            ]);
            
            if (!$refund_result['success']) {
                throw new Exception('Financial integration failed: ' . $refund_result['message']);
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
        $summary = [
            'total_count' => count($returns),
            'total_refund_amount' => array_sum(array_column($returns, 'total_refund_amount'))
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
        $this->db->where('m.movement_type', 'return');
        $this->db->like('m.notes', 'Sale #' . $return['sale_id']);
        
        $return['stock_movements'] = $this->db->get()->result_array();
        
        return $return;
    }
    
    /**
     * Calculate return statistics for reporting
     * @param array $filters - start_date, end_date
     * @return array
     */
    public function get_return_statistics($filters = []) {
        // Base query for date filtering
        $this->db->select('r.*, ri.product_id, ri.quantity_returned, p.name as product_name, c.name as category_name');
        $this->db->from('inventory_returns r');
        $this->db->join('inventory_return_items ri', 'ri.return_id = r.id', 'left');
        $this->db->join('inventory_products p', 'p.id = ri.product_id', 'left');
        $this->db->join('inventory_categories c', 'c.id = p.category_id', 'left');
        
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
        
        // Calculate total refund amount
        $stats['total_refund_amount'] = array_sum(array_unique_key($all_returns, 'id', 'total_refund_amount'));
        
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
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($po_items as $item) {
                $total_amount += ($item['cost_price'] * $item['quantity']);
            }
            
            // Insert purchase order header
            $po_data['total_amount'] = $total_amount;
            $po_data['status'] = 'pending';
            $po_data['created_by'] = $this->session->userdata('admin_id');
            $po_data['created_at'] = date('Y-m-d H:i:s');
            
            $this->db->insert('inventory_purchases', $po_data);
            $purchase_id = $this->db->insert_id();
            
            // Insert purchase order items
            foreach ($po_items as $item) {
                $purchase_item_data = [
                    'purchase_id' => $purchase_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'total_cost' => $item['cost_price'] * $item['quantity']
                ];
                $this->db->insert('inventory_purchase_items', $purchase_item_data);
            }
            
            // Log audit trail
            $this->log_audit('purchase_order_created', 'inventory_purchases', $purchase_id, null, $po_data);
            
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
     * @param array $filters - start_date, end_date, supplier_id, status, created_by
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
            'purchase_orders' => $purchase_orders,
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
            $this->db->like('m.notes', 'Purchase Order #' . $po_id);
            
            $purchase_order['stock_movements'] = $this->db->get()->result_array();
        }
        
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
                    'movement_type' => 'purchase',
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
     * Delete a purchase order (only if pending or cancelled)
     * @param int $po_id
     * @return bool
     */
    public function delete_purchase_order($po_id) {
        // Verify PO is not received
        $po = $this->db->get_where('inventory_purchases', ['id' => $po_id])->row();
        
        if (!$po || $po->status == 'received') {
            return false;
        }
        
        // Delete purchase order (cascade deletes items)
        $result = $this->db->where('id', $po_id)->delete('inventory_purchases');
        
        if ($result) {
            $this->log_audit('purchase_order_deleted', 'inventory_purchases', $po_id, (array)$po, null);
        }
        
        return $result;
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
}
