<?php
/**
 * Report Display Helper
 * 
 * Provides smart detection functions for displaying conduct, interest, and remarks
 * on report cards. Automatically detects if values are IDs (numeric) or text,
 * and handles both old and new data formats.
 * 
 * Usage in views:
 * $this->load->helper('report_display');
 * echo get_conduct_display($aggregation_row->c1);
 * echo get_interest_display($aggregation_row->interest);
 */

if (!function_exists('get_conduct_display')) {
    /**
     * Get conduct display value with smart detection
     * 
     * @param string|int|null $conduct_value Value from c1-c15 columns
     * @return string Display value (name from table or original text)
     */
    function get_conduct_display($conduct_value) {
        if (empty($conduct_value)) {
            return '';
        }
        
        // Check if numeric (ID)
        if (is_numeric($conduct_value)) {
            // It's an ID - fetch from conduct_items table
            $CI =& get_instance();
            $CI->db->where('id', $conduct_value);
            $conduct = $CI->db->get('conduct_items')->row();
            
            if ($conduct) {
                return $conduct->name;
            } else {
                // ID not found in table - might be old data, return as-is
                return $conduct_value;
            }
        } else {
            // It's old text data - display as-is
            return $conduct_value;
        }
    }
}

if (!function_exists('get_all_conducts_display')) {
    /**
     * Get all conducts from c1-c15 columns as array
     * 
     * @param object $aggregation_row Aggregation table row object
     * @return array Array of conduct display values
     */
    function get_all_conducts_display($aggregation_row) {
        $conducts = [];
        
        for ($i = 1; $i <= 15; $i++) {
            $column = 'c' . $i;
            if (isset($aggregation_row->$column) && !empty($aggregation_row->$column)) {
                $conduct = get_conduct_display($aggregation_row->$column);
                if (!empty($conduct)) {
                    $conducts[] = $conduct;
                }
            }
        }
        
        return $conducts;
    }
}

if (!function_exists('get_all_conducts_display_string')) {
    /**
     * Get all conducts from c1-c15 columns as comma-separated string
     * 
     * @param object $aggregation_row Aggregation table row object
     * @param string $separator Separator between conducts (default: ', ')
     * @return string Comma-separated conduct names
     */
    function get_all_conducts_display_string($aggregation_row, $separator = ', ') {
        $conducts = get_all_conducts_display($aggregation_row);
        return implode($separator, $conducts);
    }
}

if (!function_exists('get_interest_display')) {
    /**
     * Get interest display value with smart detection
     * 
     * @param string|null $interest_value Value from interest column
     * @param string $separator Separator for multiple interests (default: ', ')
     * @return string Display value (names from table or original text)
     */
    function get_interest_display($interest_value, $separator = ', ') {
        if (empty($interest_value)) {
            return '';
        }
        
        // Check if contains only numbers and commas (comma-separated IDs)
        if (preg_match('/^[\d,\s]+$/', $interest_value)) {
            // It's comma-separated IDs - fetch from interest_items table
            $ids = explode(',', $interest_value);
            $names = [];
            $CI =& get_instance();
            
            foreach ($ids as $id) {
                $id = trim($id);
                if (empty($id)) continue;
                
                $CI->db->where('id', $id);
                $interest = $CI->db->get('interest_items')->row();
                
                if ($interest) {
                    $names[] = $interest->name;
                }
            }
            
            return implode($separator, $names);
        } else {
            // It's old text data - display as-is
            return $interest_value;
        }
    }
}

if (!function_exists('get_interest_display_array')) {
    /**
     * Get interest display values as array
     * 
     * @param string|null $interest_value Value from interest column
     * @return array Array of interest names
     */
    function get_interest_display_array($interest_value) {
        if (empty($interest_value)) {
            return [];
        }
        
        // Check if contains only numbers and commas (comma-separated IDs)
        if (preg_match('/^[\d,\s]+$/', $interest_value)) {
            // It's comma-separated IDs - fetch from interest_items table
            $ids = explode(',', $interest_value);
            $names = [];
            $CI =& get_instance();
            
            foreach ($ids as $id) {
                $id = trim($id);
                if (empty($id)) continue;
                
                $CI->db->where('id', $id);
                $interest = $CI->db->get('interest_items')->row();
                
                if ($interest) {
                    $names[] = $interest->name;
                }
            }
            
            return $names;
        } else {
            // It's old text data - split by comma
            return array_map('trim', explode(',', $interest_value));
        }
    }
}

if (!function_exists('get_head_teacher_remark_display')) {
    /**
     * Get head teacher remark display value with smart detection
     * 
     * @param string|int|null $remark_value Value from head_teacher_remarks column
     * @return string Display value (text from table or original text)
     */
    function get_head_teacher_remark_display($remark_value) {
        if (empty($remark_value)) {
            return '';
        }
        
        // Check if numeric (ID)
        if (is_numeric($remark_value)) {
            // It's an ID - fetch from head_teacher_remarks table
            $CI =& get_instance();
            $CI->db->where('remark_id', $remark_value);
            $remark = $CI->db->get('head_teacher_remarks')->row();
            
            if ($remark) {
                return $remark->remark_text;
            } else {
                // ID not found - might be old numeric data, return as-is
                return $remark_value;
            }
        } else {
            // It's text data - display as-is
            return $remark_value;
        }
    }
}

if (!function_exists('get_class_teacher_remark_display')) {
    /**
     * Get class teacher remark display value
     * 
     * Checks both 'remarks' and 'class_teacher_remarks' columns
     * 
     * @param object $aggregation_row Aggregation table row object
     * @param string $column_preference Which column to prefer ('remarks' or 'class_teacher_remarks')
     * @return string Display value
     */
    function get_class_teacher_remark_display($aggregation_row, $column_preference = 'remarks') {
        if ($column_preference == 'class_teacher_remarks') {
            // Prefer class_teacher_remarks first
            if (isset($aggregation_row->class_teacher_remarks) && !empty($aggregation_row->class_teacher_remarks)) {
                return $aggregation_row->class_teacher_remarks;
            } elseif (isset($aggregation_row->remarks) && !empty($aggregation_row->remarks)) {
                return $aggregation_row->remarks;
            }
        } else {
            // Prefer remarks first (default)
            if (isset($aggregation_row->remarks) && !empty($aggregation_row->remarks)) {
                return $aggregation_row->remarks;
            } elseif (isset($aggregation_row->class_teacher_remarks) && !empty($aggregation_row->class_teacher_remarks)) {
                return $aggregation_row->class_teacher_remarks;
            }
        }
        
        return '';
    }
}

if (!function_exists('format_conducts_for_print')) {
    /**
     * Format conducts for print view with custom HTML
     * 
     * @param object $aggregation_row Aggregation table row object
     * @param string $prefix HTML prefix for each conduct
     * @param string $suffix HTML suffix for each conduct
     * @return string Formatted HTML string
     */
    function format_conducts_for_print($aggregation_row, $prefix = '<li>', $suffix = '</li>') {
        $conducts = get_all_conducts_display($aggregation_row);
        
        if (empty($conducts)) {
            return '';
        }
        
        $output = '';
        foreach ($conducts as $conduct) {
            $output .= $prefix . htmlspecialchars($conduct) . $suffix;
        }
        
        return $output;
    }
}

if (!function_exists('format_interests_for_print')) {
    /**
     * Format interests for print view with custom HTML
     * 
     * @param string $interest_value Value from interest column
     * @param string $prefix HTML prefix for each interest
     * @param string $suffix HTML suffix for each interest
     * @return string Formatted HTML string
     */
    function format_interests_for_print($interest_value, $prefix = '<li>', $suffix = '</li>') {
        $interests = get_interest_display_array($interest_value);
        
        if (empty($interests)) {
            return '';
        }
        
        $output = '';
        foreach ($interests as $interest) {
            $output .= $prefix . htmlspecialchars($interest) . $suffix;
        }
        
        return $output;
    }
}

/* End of file report_display_helper.php */
/* Location: ./application/helpers/report_display_helper.php */
