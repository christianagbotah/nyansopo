<?php
/**
 * CASHIER ROLE HELPER
 * Add this to application/helpers/cashier_helper.php
 */

if (!function_exists('is_cashier')) {
    /**
     * Check if current user is a cashier
     * Cashiers are admins with level 4
     */
    function is_cashier() {
        $CI =& get_instance();
        $admin_id = $CI->session->userdata('admin_id');
        
        if (!$admin_id) {
            return false;
        }
        
        $admin = $CI->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        // Level 4 = Cashier
        return ($admin && $admin->level == 4);
    }
}

if (!function_exists('get_cashier_permissions')) {
    /**
     * Get cashier-specific permissions
     */
    function get_cashier_permissions() {
        return [
            'can_collect_fees' => true,
            'can_view_students' => true,
            'can_print_receipts' => true,
            'can_view_own_collections' => true,
            'can_view_daily_reports' => true,
            'can_manage_profile' => true,
            
            // Restricted
            'can_view_all_reports' => false,
            'can_manage_discounts' => false,
            'can_manage_invoices' => false,
            'can_manage_expenses' => false,
            'can_manage_settings' => false,
            'can_manage_users' => false,
            'can_manage_academics' => false,
        ];
    }
}
?>
