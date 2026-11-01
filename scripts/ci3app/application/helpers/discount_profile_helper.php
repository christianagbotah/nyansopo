<?php
/**
 * Helper function to get actual bill items/fee types from discount profile
 * Handles the special '*' marker for "all items"
 * 
 * Add this to billing_helper.php or create new discount_helper.php
 */

if (!function_exists('get_profile_items')) {
    /**
     * Get actual items for a discount profile
     * 
     * @param object $profile - Discount profile object
     * @return array - Array of item IDs (for invoice) or fee names (for daily_fees)
     */
    function get_profile_items($profile) {
        $CI =& get_instance();
        
        if ($profile->discount_category == 'invoice') {
            if ($profile->bill_item_ids == '*') {
                $items = $CI->db->select('id')
                                ->where('school_id', $CI->session->userdata('school_id'))
                                ->get('bill_item')->result();
                return array_column($items, 'id');
            } else {
                return explode(',', $profile->bill_item_ids);
            }
        } else {
            // Handle daily fees
            if ($profile->discount_type == '*') {
                // Get ALL enabled daily fees dynamically
                $enabled_fees = array();
                $fee_types = array('feeding', 'classes', 'water', 'breakfast', 'transport');
                
                foreach($fee_types as $fee_type) {
                    $setting = $CI->db->get_where('settings', array(
                        'type' => 'fee_module_' . $fee_type
                    ))->row();
                    
                    if($setting && $setting->description == '1') {
                        $enabled_fees[] = $fee_type;
                    }
                }
                
                return $enabled_fees;
            } else {
                // Return specific fee types
                return explode(',', $profile->discount_type);
            }
        }
    }
}

if (!function_exists('profile_applies_to_item')) {
    /**
     * Check if discount profile applies to a specific item
     * 
     * @param object $profile - Discount profile object
     * @param mixed $item_id_or_name - Bill item ID (for invoice) or fee name (for daily_fees)
     * @return bool
     */
    function profile_applies_to_item($profile, $item_id_or_name) {
        $items = get_profile_items($profile);
        return in_array($item_id_or_name, $items);
    }
}

if (!function_exists('get_profile_display_items')) {
    /**
     * Get display names for profile items (for UI display)
     * 
     * @param object $profile - Discount profile object
     * @return array - Array of display names
     */
    function get_profile_display_items($profile) {
        $CI =& get_instance();
        
        if ($profile->discount_category == 'invoice') {
            if ($profile->bill_item_ids == '*') {
                return array('All Bill Items');
            } else {
                $ids = explode(',', $profile->bill_item_ids);
                $items = $CI->db->select('title')
                               ->where_in('id', $ids)
                               ->where('school_id', $CI->session->userdata('school_id'))
                               ->get('bill_item')
                               ->result();
                return array_column($items, 'title');
            }
        } else {
            if ($profile->discount_type == '*') {
                return array('All Daily Fees');
            } else {
                $types = explode(',', $profile->discount_type);
                return array_map('ucfirst', $types);
            }
        }
    }
}
