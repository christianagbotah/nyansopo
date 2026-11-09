<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budget_integration {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Link expense to budget line
     */
    public function link_expense_to_budget($expense_id, $budget_line_id = null) {
        $expense = $this->CI->db->where('id', $expense_id)->get('expenses_enhanced')->row_array();
        
        if (!$expense) return false;
        
        // Auto-match budget line if not provided
        if (!$budget_line_id) {
            $budget_line_id = $this->find_matching_budget_line($expense);
        }
        
        if (!$budget_line_id) return false;
        
        // Update expense with budget line
        $this->CI->db->where('id', $expense_id)->update('expenses_enhanced', [
            'budget_line_id' => $budget_line_id,
            'linked_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update budget line utilization
        $this->update_budget_utilization($budget_line_id);
        
        return true;
    }
    
    /**
     * Find matching budget line for expense
     */
    private function find_matching_budget_line($expense) {
        // Get active budget for current year
        $budget = $this->CI->db->where('status', 'active')
                              ->where('fiscal_year', date('Y'))
                              ->get('budgets')
                              ->row_array();
        
        if (!$budget) return null;
        
        // Find budget line matching expense category
        $budget_line = $this->CI->db->where('budget_id', $budget['id'])
                                    ->where('category_id', $expense['category_id'])
                                    ->get('budget_lines')
                                    ->row_array();
        
        return $budget_line ? $budget_line['id'] : null;
    }
    
    /**
     * Update budget line utilization
     */
    public function update_budget_utilization($budget_line_id) {
        // Get total approved expenses for this budget line
        $total = $this->CI->db->select_sum('amount')
                             ->where('budget_line_id', $budget_line_id)
                             ->where('status', 'approved')
                             ->get('expenses_enhanced')
                             ->row()->amount;
        
        $total = $total ?: 0;
        
        // Get budget line
        $budget_line = $this->CI->db->where('id', $budget_line_id)->get('budget_lines')->row_array();
        
        // Calculate utilization percentage
        $utilization = ($total / $budget_line['allocated_amount']) * 100;
        
        // Update budget line
        $this->CI->db->where('id', $budget_line_id)->update('budget_lines', [
            'utilized_amount' => $total,
            'utilization_percentage' => $utilization,
            'remaining_amount' => $budget_line['allocated_amount'] - $total
        ]);
        
        return $utilization;
    }
    
    /**
     * Check if expense exceeds budget
     */
    public function check_budget_limit($expense_amount, $category_id) {
        $budget_line_id = $this->find_matching_budget_line(['category_id' => $category_id]);
        
        if (!$budget_line_id) {
            return ['status' => 'warning', 'message' => 'No budget allocated for this category'];
        }
        
        $budget_line = $this->CI->db->where('id', $budget_line_id)->get('budget_lines')->row_array();
        
        if (($budget_line['utilized_amount'] + $expense_amount) > $budget_line['allocated_amount']) {
            return [
                'status' => 'error',
                'message' => 'Expense exceeds budget limit',
                'available' => $budget_line['remaining_amount'],
                'requested' => $expense_amount
            ];
        }
        
        return ['status' => 'success', 'message' => 'Within budget'];
    }
    
    /**
     * Get budget vs actual report
     */
    public function get_budget_vs_actual($budget_id) {
        $this->CI->db->select('bl.*, ec.name as category_name, 
                              COALESCE(SUM(e.amount), 0) as actual_amount');
        $this->CI->db->from('budget_lines bl');
        $this->CI->db->join('expense_categories_enhanced ec', 'bl.category_id = ec.id');
        $this->CI->db->join('expenses_enhanced e', 'bl.id = e.budget_line_id AND e.status = "approved"', 'left');
        $this->CI->db->where('bl.budget_id', $budget_id);
        $this->CI->db->group_by('bl.id');
        
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Get budget alerts
     */
    public function get_budget_alerts() {
        $this->CI->db->select('bl.*, b.name as budget_name, ec.name as category_name');
        $this->CI->db->from('budget_lines bl');
        $this->CI->db->join('budgets b', 'bl.budget_id = b.id');
        $this->CI->db->join('expense_categories_enhanced ec', 'bl.category_id = ec.id');
        $this->CI->db->where('b.status', 'active');
        $this->CI->db->where('bl.utilization_percentage >', 80);
        
        return $this->CI->db->get()->result_array();
    }
}
