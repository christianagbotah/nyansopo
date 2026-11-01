<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Create a notification for a user
 */
function create_notification($user_id, $user_type, $title, $message, $icon = 'fa-bell') {
    $CI =& get_instance();
    
    $data = array(
        'user_id' => $user_id,
        'user_type' => $user_type,
        'title' => $title,
        'message' => $message,
        'icon' => $icon,
        'is_read' => 0,
        'created_at' => time()
    );
    
    return $CI->db->insert('notifications', $data);
}

/**
 * Notify all admins
 */
function notify_all_admins($title, $message, $icon = 'fa-bell') {
    $CI =& get_instance();
    $admins = $CI->db->get('admin')->result();
    
    foreach($admins as $admin) {
        create_notification($admin->admin_id, 'admin', $title, $message, $icon);
    }
}

/**
 * Notify specific user types
 */
function notify_user_type($user_type, $title, $message, $icon = 'fa-bell') {
    $CI =& get_instance();
    $table = $user_type;
    $id_field = $user_type . '_id';
    
    $users = $CI->db->get($table)->result();
    
    foreach($users as $user) {
        create_notification($user->$id_field, $user_type, $title, $message, $icon);
    }
}

/**
 * Log student admission and create audit trail
 */
function log_admission($student_id, $admitted_by_id, $class_id, $section_id, $residence_type, $bills, $total_amount, $admission_date = '') {
    $CI =& get_instance();
    
    // Create admission log entry
    $log_data = array(
        'student_id' => $student_id,
        'admitted_by' => $admitted_by_id,
        'class_id' => $class_id,
        'section_id' => $section_id,
        'residence_type' => $residence_type,
        'bills_generated' => json_encode($bills),
        'total_amount' => $total_amount,
        'admission_date' => date('Y-m-d', $admission_date)
    );
    
    // Only insert if admission_log table exists
    if($CI->db->table_exists('admission_log')) {
        $CI->db->insert('admission_log', $log_data);
    }
}

/**
 * Notify all admins about new student admission
 */
function notify_admins_new_admission($student_name, $class_name, $admitted_by, $student_id) {
    $CI =& get_instance();
    
    // Get full class details with numeric and section
    $enroll = $CI->db->where('student_id', $student_id)
        ->where('mute', '0')
        ->order_by('enroll_id', 'DESC')
        ->limit(1)
        ->get('enroll')->row();
    
    $full_class_name = ucwords(strtolower($class_name));
    $residence_type = 'Day';
    
    if($enroll) {
        $class = $CI->db->where('class_id', $enroll->class_id)->get('class')->row();
        $section = $CI->db->where('section_id', $enroll->section_id)->get('section')->row();
        $residence_type = ucwords(strtolower($enroll->residence_type ?? 'Day'));
        
        if($class && $section) {
            $full_class_name = ucwords(strtolower($class->name)) . ' ' . $class->name_numeric . ' ' . ucwords(strtolower($section->name));
        }
    }
    
    // Get invoice details
    $running_year = $CI->db->get_where('settings', ['type' => 'running_year'])->row()->description;
    $running_term = $CI->db->get_where('settings', ['type' => 'running_term'])->row()->description;
    
    $invoices = $CI->db->where('student_id', $student_id)
        ->where('year', $running_year)
        ->where('term', $running_term)
        ->get('invoice')->result_array();
    
    $total_billed = 0;
    foreach($invoices as $inv) {
        $total_billed += $inv['amount'];
    }
    
    // Get discount details
    $discount_info = $CI->db->select('id.discount_amount, dp.profile_name, id.discount_method, id.discount_value')
        ->from('invoice_discounts id')
        ->join('discount_profiles dp', 'dp.profile_id = id.profile_id', 'left')
        ->where('id.student_id', $student_id)
        ->where('id.year', $running_year)
        ->where('id.term', $running_term)
        ->where('id.discount_category', 'invoice')
        ->where('id.status', 'approved')
        ->get()->row();
    
    $discount_amount = 0;
    $discount_profile = null;
    $discount_details = null;
    
    $currency = $CI->db->get_where('settings', ['type' => 'currency'])->row()->description;
    
    if($discount_info) {
        $discount_amount = $discount_info->discount_amount;
        $discount_profile = $discount_info->profile_name;
        if($discount_info->discount_method == 'percentage') {
            $discount_details = $discount_info->discount_value . '% discount';
        } else {
            $discount_details = $currency . ' ' . number_format($discount_info->discount_value, 2) . ' discount';
        }
    }
    
    $total_billed += $discount_amount;
    $balance = $total_billed - $discount_amount;
    
    // Format names
    $formatted_student_name = ucwords(strtolower($student_name));
    $formatted_admitted_by = ucwords(strtolower($admitted_by));
    
    $title = 'New Student Admitted';
    $message = "{$formatted_student_name} has been admitted to {$full_class_name} by {$formatted_admitted_by}";
    
    // Store additional data as JSON
    $data = [
        'student_id' => $student_id,
        'student_name' => $formatted_student_name,
        'class_name' => $full_class_name,
        'residence_type' => $residence_type,
        'total_billed' => $total_billed,
        'discount_profile' => $discount_profile,
        'discount_details' => $discount_details,
        'discount_amount' => $discount_amount,
        'balance' => $balance,
        'admitted_by' => $formatted_admitted_by
    ];
    
    // Notify all admins
    $admins = $CI->db->get('admin')->result();
    foreach($admins as $admin) {
        $CI->db->insert('notifications', [
            'user_id' => $admin->admin_id,
            'user_type' => 'admin',
            'title' => $title,
            'message' => $message,
            'type' => 'admission',
            'data' => json_encode($data),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
