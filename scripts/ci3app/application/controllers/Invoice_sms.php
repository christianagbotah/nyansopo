<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_sms extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function send($invoice_code) {
        $recipients = $this->input->post('recipients');
        
        if(empty($recipients)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select at least one recipient']);
            return;
        }
        
        $recipients = array_unique($recipients);
        
        $invoice_data = $this->db->get_where('invoice', ['invoice_code' => $invoice_code])->row();
        
        if (!$invoice_data) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('invoice_not_found')]);
            return;
        }

        $student = $this->db->get_where('student', ['student_id' => $invoice_data->student_id])->row();
        
        if (!$student) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('student_not_found')]);
            return;
        }

        // Generate secure token for invoice access
        $token = bin2hex(random_bytes(32));
        $expires_at = time() + (7 * 24 * 60 * 60); // 7 days
        
        // Store token in database
        $this->db->insert('invoice_access_tokens', [
            'invoice_code' => $invoice_code,
            'token' => $token,
            'expires_at' => $expires_at,
            'created_at' => time()
        ]);
        
        // Generate invoice link
        $invoice_link = site_url('public_invoice/view/' . $token);
        
        // Collect phone numbers based on selected recipients
        $phones = [];
        $missing = [];
        
        foreach($recipients as $recipient) {
            switch($recipient) {
                case 'student':
                    if(!empty($student->phone)) $phones[] = $student->phone;
                    else $missing[] = 'Student';
                    break;
                case 'guardian':
                    if(!empty($student->guardian_phone)) $phones[] = $student->guardian_phone;
                    else $missing[] = 'Guardian';
                    break;
                case 'father':
                    if(!empty($student->father_phone)) $phones[] = $student->father_phone;
                    else $missing[] = 'Father';
                    break;
                case 'mother':
                    if(!empty($student->mother_phone)) $phones[] = $student->mother_phone;
                    else $missing[] = 'Mother';
                    break;
            }
        }
        
        $phones = array_unique($phones);
        
        if(empty($phones)) {
            $msg = 'No phone numbers found for selected recipient(s): ' . implode(', ', $missing);
            echo json_encode(['status' => 'error', 'message' => $msg]);
            return;
        }

        // SMS message
        $school_name = get_settings('system_name');
        $message = "Invoice #$invoice_code from $school_name. View & download: $invoice_link (Valid for 7 days)";
        
        // Send SMS
        $sent = 0;
        $failed = 0;
        
        foreach($phones as $phone) {
            if($this->send_sms($phone, $message)) {
                $sent++;
                // Log SMS sent
                $this->db->insert('invoice_sms_log', [
                    'recipient' => $phone,
                    'message' => $message,
                    'type' => 'invoice',
                    'reference_id' => $invoice_code,
                    'sent_at' => time(),
                    'status' => 'sent'
                ]);
            } else {
                $failed++;
            }
        }
        
        if($sent > 0) {
            $msg = "SMS sent successfully to $sent recipient(s)";
            if($failed > 0) $msg .= ", $failed failed";
            if(!empty($missing)) $msg .= '. Note: Missing phone for ' . implode(', ', $missing);
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send SMS']);
        }
    }

    private function send_sms($phone, $message) {
        // SMS API configuration
        $api_key = get_settings('sms_api_key');
        $sender_id = get_settings('sms_sender_id');
        $api_url = get_settings('sms_api_url');
        
        if(empty($api_key) || empty($api_url)) {
            return false;
        }
        
        // Format phone number (remove spaces, add country code if needed)
        $phone = preg_replace('/\s+/', '', $phone);
        
        // Send SMS via API (adjust based on your SMS provider)
        $data = [
            'api_key' => $api_key,
            'sender_id' => $sender_id,
            'phone' => $phone,
            'message' => $message
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return !empty($response);
    }
}
