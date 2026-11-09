<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Send approval request notifications to all super admins
 * 
 * @param int $request_id The request ID
 * @param string $request_type Type of request (edit/delete)
 * @param string $request_description Description of the request
 * @param int $requester_id ID of the admin making the request
 * @return bool Success status
 */
function notify_super_admins_approval_request($request_id, $request_type, $request_description, $requester_id) {
    $CI =& get_instance();
    $CI->load->database();
    $CI->load->model('sms_model');
    $CI->load->model('email_model');
    
    // Get requester info
    $requester = $CI->db->where('admin_id', $requester_id)->get('admin')->row();
    if(!$requester) return false;
    
    // Get school name
    $school_name = $CI->db->get_where('settings', array('type' => 'system_name'))->row()->description;
    
    // Get all super admins
    $super_admins = $CI->db->where('level', 1)->get('admin')->result();
    
    if(empty($super_admins)) return false;
    
    // Generate approval token
    $request = $CI->db->where('request_id', $request_id)->get('invoice_requests')->row();
    $token = md5($request_id . $request->request_created_timestamp . 'approval_secret');
    
    // Approval links
    $approve_link = site_url('admin/manageRequestApproval/quick_action/' . $request_id . '/Approved?token=' . $token);
    $decline_link = site_url('admin/manageRequestApproval/quick_action/' . $request_id . '/Declined?token=' . $token);
    $view_link = site_url('admin/manageRequestApproval');
    
    foreach($super_admins as $admin) {
        // In-app notification
        $CI->db->insert('notifications', [
            'user_id' => $admin->admin_id,
            'user_type' => 'admin',
            'title' => 'Approval Required',
            'message' => $requester->name . ' requested ' . $request_type . ' approval: ' . $request_description,
            'type' => 'approval_request',
            'created_at' => date('Y-m-d H:i:s'),
            'link' => $view_link
        ]);
        
        // SMS notification
        $active_sms = $CI->db->get_where('settings', array('type' => 'active_sms_service'))->row();
        if($active_sms && $active_sms->description != 'disabled' && !empty($admin->phone)) {
            $sms_message = "[$school_name] Approval needed: {$requester->name} requested {$request_type}. View: {$view_link}";
            $CI->sms_model->send_sms($sms_message, [$admin->phone]);
        }
        
        // Email notification with approval buttons
        if(!empty($admin->email)) {
            $subject = 'Approval Request - ' . $request_type;
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                    <h2 style='color: white; margin: 0;'>Approval Required</h2>
                </div>
                <div style='padding: 30px; background: #f9fafb;'>
                    <p style='font-size: 16px; color: #374151;'>Dear {$admin->name},</p>
                    <p style='font-size: 16px; color: #374151;'><strong>{$requester->name}</strong> has submitted a request that requires your approval.</p>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                        <p style='margin: 5px 0; color: #6b7280;'><strong>Request Type:</strong> {$request_type}</p>
                        <p style='margin: 5px 0; color: #6b7280;'><strong>Request ID:</strong> #{$request_id}</p>
                        <p style='margin: 5px 0; color: #6b7280;'><strong>Description:</strong> {$request_description}</p>
                        <p style='margin: 5px 0; color: #6b7280;'><strong>Requested by:</strong> {$requester->name}</p>
                        <p style='margin: 5px 0; color: #6b7280;'><strong>Date:</strong> " . date('F j, Y g:i A') . "</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <p style='color: #6b7280; margin-bottom: 16px;'>Take action on this request:</p>
                        <a href='{$approve_link}' style='display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 0 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);'>
                            ✓ Approve Request
                        </a>
                        <a href='{$decline_link}' style='display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 0 8px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);'>
                            ✗ Decline Request
                        </a>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='{$view_link}' style='color: #667eea; text-decoration: none; font-size: 14px;'>
                            Or view all pending requests →
                        </a>
                    </div>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                        <p style='font-size: 12px; color: #9ca3af; margin: 0;'>
                            <strong>Note:</strong> These approval links are secure and can only be used once. 
                            If you prefer, you can also approve/decline from the admin dashboard.
                        </p>
                    </div>
                    
                    <p style='font-size: 14px; color: #6b7280; margin-top: 30px;'>Best regards,<br>{$school_name}</p>
                </div>
                <div style='background: #1f2937; padding: 20px; text-align: center;'>
                    <p style='color: #9ca3af; font-size: 12px; margin: 0;'>
                        This is an automated notification from {$school_name}
                    </p>
                </div>
            </div>";
            
            $CI->email_model->do_email($message, $subject, $admin->email, $school_name);
        }
    }
    
    return true;
}

/**
 * Create an invoice edit/delete request
 * 
 * @param int $invoice_id The invoice ID
 * @param string $request_type Type of request (edit/delete)
 * @param string $description Description of the request
 * @param int $requester_id ID of the admin making the request
 * @return int|bool Request ID on success, false on failure
 */
function create_invoice_request($invoice_id, $request_type, $description, $requester_id) {
    $CI =& get_instance();
    $CI->load->database();
    
    // Check if user is super admin (they don't need approval)
    $user = $CI->db->where('admin_id', $requester_id)->get('admin')->row();
    if($user && $user->level == 1) {
        return true; // Super admins don't need approval
    }
    
    // Create request
    $request_data = [
        'invoice_id' => $invoice_id,
        'request_type' => $request_type,
        'request_description' => $description,
        'request_issuer_id' => $requester_id,
        'approval_status' => 'Pending',
        'request_created_timestamp' => date('Y-m-d H:i:s')
    ];
    
    $CI->db->insert('invoice_requests', $request_data);
    $request_id = $CI->db->insert_id();
    
    if($request_id) {
        // Send notifications to super admins
        notify_super_admins_approval_request($request_id, $request_type, $description, $requester_id);
        return $request_id;
    }
    
    return false;
}
