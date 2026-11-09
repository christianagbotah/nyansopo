<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_email extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('email');
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

        // Collect emails based on selected recipients
        $emails = [];
        $missing = [];
        
        foreach($recipients as $recipient) {
            switch($recipient) {
                case 'student':
                    if(!empty($student->email)) $emails[] = $student->email;
                    else $missing[] = 'Student';
                    break;
                case 'guardian':
                    if(!empty($student->guardian_email)) $emails[] = $student->guardian_email;
                    else $missing[] = 'Guardian';
                    break;
                case 'father':
                    if(!empty($student->father_email)) $emails[] = $student->father_email;
                    else $missing[] = 'Father';
                    break;
                case 'mother':
                    if(!empty($student->mother_email)) $emails[] = $student->mother_email;
                    else $missing[] = 'Mother';
                    break;
            }
        }
        
        $emails = array_unique($emails);
        
        if(empty($emails)) {
            $msg = 'No email addresses found for selected recipient(s): ' . implode(', ', $missing);
            echo json_encode(['status' => 'error', 'message' => $msg]);
            return;
        }

        // Generate invoice HTML
        $invoice_html = $this->generate_invoice_html($invoice_code);
        
        // Email configuration
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = get_settings('smtp_host') ?: 'smtp.gmail.com';
        $config['smtp_port'] = get_settings('smtp_port') ?: 587;
        $config['smtp_user'] = get_settings('smtp_user');
        $config['smtp_pass'] = get_settings('smtp_pass');
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8';
        $config['newline'] = "\r\n";
        
        $this->email->initialize($config);
        
        $this->email->from(get_settings('system_email'), get_settings('system_name'));
        $this->email->to($emails);
        $this->email->subject('Invoice #' . $invoice_code . ' - ' . get_settings('system_name'));
        $this->email->message($invoice_html);
        
        if ($this->email->send()) {
            // Log email sent
            foreach($emails as $email) {
                $this->db->insert('email_log', [
                    'recipient' => $email,
                    'subject' => 'Invoice #' . $invoice_code,
                    'type' => 'invoice',
                    'reference_id' => $invoice_code,
                    'sent_at' => time(),
                    'status' => 'sent'
                ]);
            }
            
            $msg = 'Invoice emailed successfully to ' . count($emails) . ' recipient(s)';
            if(!empty($missing)) $msg .= '. Note: Missing email for ' . implode(', ', $missing);
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => get_phrase('email_send_failed')]);
        }
    }

    private function generate_invoice_html($invoice_code) {
        $data['invoice_code'] = $invoice_code;
        return $this->load->view('email_templates/invoice_email', $data, TRUE);
    }

    public function bulk_send() {
        $invoice_codes = $this->input->post('invoice_codes');
        
        if (empty($invoice_codes)) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('no_invoices_selected')]);
            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($invoice_codes as $code) {
            $result = $this->send_single($code);
            if ($result) $sent++;
            else $failed++;
        }

        echo json_encode([
            'status' => 'success',
            'message' => "$sent " . get_phrase('invoices_emailed') . ", $failed " . get_phrase('failed')
        ]);
    }

    private function send_single($invoice_code) {
        $invoice_data = $this->db->get_where('invoice', ['invoice_code' => $invoice_code])->row();
        if (!$invoice_data) return false;

        $student = $this->db->get_where('student', ['student_id' => $invoice_data->student_id])->row();
        if (!$student || empty($student->email)) return false;

        $invoice_html = $this->generate_invoice_html($invoice_code);
        
        $config['mailtype'] = 'html';
        $this->email->initialize($config);
        $this->email->from(get_settings('system_email'), get_settings('system_name'));
        $this->email->to($student->email);
        $this->email->subject('Invoice #' . $invoice_code);
        $this->email->message($invoice_html);
        
        return $this->email->send();
    }
}
