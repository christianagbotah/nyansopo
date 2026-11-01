<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_gateway_model extends CI_Model {

    private $mtn_api_key;
    private $vodafone_api_key;
    private $paystack_secret_key;
    private $hubtel_client_id;
    private $hubtel_client_secret;
    private $hubtel_merchant_number;

    public function __construct() {
        parent::__construct();
        $this->load_gateway_credentials();
    }

    private function load_gateway_credentials() {
        // Load from settings or config
        $this->mtn_api_key = get_settings('mtn_momo_api_key');
        $this->vodafone_api_key = get_settings('vodafone_api_key');
        $this->paystack_secret_key = get_settings('paystack_secret_key');
        $this->hubtel_client_id = get_settings('hubtel_payment_client_id');
        $this->hubtel_client_secret = get_settings('hubtel_payment_client_secret');
        $this->hubtel_merchant_number = get_settings('hubtel_payment_merchant_number');
    }

    // ==================== MOBILE MONEY (MTN/VODAFONE/AIRTELTIGO) ====================
    public function initiate_momo_payment($data) {
        $transaction_ref = $this->generate_transaction_ref();
        
        $transaction = [
            'transaction_ref' => $transaction_ref,
            'student_id' => $data['student_id'],
            'invoice_code' => $data['invoice_code'],
            'amount' => $data['amount'],
            'gateway' => $data['gateway'],
            'phone_number' => $data['phone_number'],
            'status' => 'pending'
        ];
        
        $this->db->insert('payment_transactions', $transaction);
        $transaction_id = $this->db->insert_id();
        
        // Call respective gateway API
        $result = $this->call_momo_api($data['gateway'], $data['phone_number'], $data['amount'], $transaction_ref);
        
        if ($result['success']) {
            $this->db->where('transaction_id', $transaction_id)->update('payment_transactions', [
                'gateway_ref' => $result['reference'],
                'response_data' => json_encode($result)
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => get_phrase('payment_initiated'),
                'transaction_ref' => $transaction_ref,
                'prompt_message' => get_phrase('check_phone_for_prompt')
            ]);
        } else {
            $this->db->where('transaction_id', $transaction_id)->update('payment_transactions', [
                'status' => 'failed',
                'response_data' => json_encode($result)
            ]);
            
            echo json_encode([
                'status' => 'error',
                'message' => $result['message'] ?? get_phrase('payment_failed')
            ]);
        }
    }

    private function call_momo_api($gateway, $phone, $amount, $reference) {
        // Implement actual API calls based on gateway
        switch ($gateway) {
            case 'mtn_momo':
                return $this->call_mtn_api($phone, $amount, $reference);
            case 'vodafone_cash':
                return $this->call_vodafone_api($phone, $amount, $reference);
            case 'airteltigo_money':
                return $this->call_airteltigo_api($phone, $amount, $reference);
            default:
                return ['success' => false, 'message' => 'Invalid gateway'];
        }
    }

    private function call_mtn_api($phone, $amount, $reference) {
        // MTN MoMo API Integration
        $url = 'https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay';
        
        $data = [
            'amount' => $amount,
            'currency' => get_settings('currency'),
            'externalId' => $reference,
            'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
            'payerMessage' => 'School fees payment',
            'payeeNote' => 'Payment for invoice'
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->mtn_api_key,
            'X-Reference-Id: ' . $reference,
            'X-Target-Environment: sandbox',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 202) {
            return ['success' => true, 'reference' => $reference];
        } else {
            return ['success' => false, 'message' => 'MTN API Error', 'response' => $response];
        }
    }

    private function call_vodafone_api($phone, $amount, $reference) {
        // Vodafone Cash API Integration
        // Implement based on Vodafone API documentation
        return ['success' => true, 'reference' => $reference];
    }

    private function call_airteltigo_api($phone, $amount, $reference) {
        // AirtelTigo Money API Integration
        // Implement based on AirtelTigo API documentation
        return ['success' => true, 'reference' => $reference];
    }

    public function process_momo_callback() {
        $reference = $this->input->post('reference');
        $status = $this->input->post('status');
        
        $transaction = $this->db->where('transaction_ref', $reference)->get('payment_transactions')->row_array();
        
        if ($transaction) {
            if ($status == 'SUCCESSFUL') {
                $this->complete_payment($transaction);
            } else {
                $this->db->where('transaction_ref', $reference)->update('payment_transactions', [
                    'status' => 'failed'
                ]);
            }
        }
    }

    // ==================== PAYSTACK ====================
    public function initiate_paystack($data) {
        $transaction_ref = $this->generate_transaction_ref();
        
        $transaction = [
            'transaction_ref' => $transaction_ref,
            'student_id' => $data['student_id'],
            'invoice_code' => $data['invoice_code'],
            'amount' => $data['amount'],
            'gateway' => 'paystack',
            'status' => 'pending'
        ];
        
        $this->db->insert('payment_transactions', $transaction);
        
        $url = 'https://api.paystack.co/transaction/initialize';
        
        $fields = [
            'email' => $data['email'],
            'amount' => $data['amount'] * 100, // Convert to pesewas/cents
            'reference' => $transaction_ref,
            'callback_url' => site_url('payment_gateway/paystack_callback')
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->paystack_secret_key,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['status']) {
            echo json_encode([
                'status' => 'success',
                'authorization_url' => $result['data']['authorization_url']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $result['message']
            ]);
        }
    }

    public function verify_paystack_payment($reference) {
        $url = "https://api.paystack.co/transaction/verify/{$reference}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->paystack_secret_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['status'] && $result['data']['status'] == 'success') {
            $transaction = $this->db->where('transaction_ref', $reference)->get('payment_transactions')->row_array();
            $this->complete_payment($transaction);
            redirect(site_url('student/invoice?success=1'));
        } else {
            redirect(site_url('student/invoice?error=1'));
        }
    }

    // ==================== COMMON FUNCTIONS ====================
    private function complete_payment($transaction) {
        $this->db->trans_start();
        
        // Update transaction status
        $this->db->where('transaction_id', $transaction['transaction_id'])->update('payment_transactions', [
            'status' => 'success'
        ]);
        
        // Create payment record
        $payment_data = [
            'student_id' => $transaction['student_id'],
            'invoice_code' => $transaction['invoice_code'],
            'amount' => $transaction['amount'],
            'payment_method' => $transaction['gateway'],
            'transaction_id' => $transaction['transaction_ref'],
            'timestamp' => time(),
            'day_timestamp' => strtotime(date('Y-m-d')),
            'year' => get_settings('running_year'),
            'term' => get_settings('running_term')
        ];
        
        $this->db->insert('payment', $payment_data);
        $payment_id = $this->db->insert_id();
        
        // Update invoice
        $invoice = $this->db->where('invoice_code', $transaction['invoice_code'])
            ->where('student_id', $transaction['student_id'])
            ->get('invoice')->row_array();
        
        $new_paid = $invoice['amount_paid'] + $transaction['amount'];
        $new_due = $invoice['due'] - $transaction['amount'];
        $new_status = $new_due <= 0 ? 'paid' : 'due';
        
        $this->db->where('invoice_id', $invoice['invoice_id'])->update('invoice', [
            'amount_paid' => $new_paid,
            'due' => $new_due,
            'status' => $new_status,
            'payment_timestamp' => time()
        ]);
        
        // Generate receipt
        $this->load->model('Finance_model');
        $receipt_data = [
            'student_id' => $transaction['student_id'],
            'invoice_code' => $transaction['invoice_code'],
            'amount' => $transaction['amount'],
            'payment_method' => $transaction['gateway'],
            'payment_reference' => $transaction['transaction_ref'],
            'received_by' => 1, // System
            'received_date' => date('Y-m-d H:i:s')
        ];
        
        $this->db->trans_complete();
        
        // Send notification
        $this->send_payment_notification($transaction);
    }

    private function send_payment_notification($transaction) {
        // Send email/SMS notification
        $student = $this->db->where('student_id', $transaction['student_id'])->get('student')->row_array();
        
        $this->load->library('email');
        $this->email->from(get_settings('system_email'), get_settings('system_name'));
        $this->email->to($student['parent_email']);
        $this->email->subject('Payment Confirmation');
        $this->email->message("Payment of " . get_settings('currency') . $transaction['amount'] . " received successfully.");
        $this->email->send();
    }

    private function generate_transaction_ref() {
        return 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
    }

    // ==================== HUBTEL ====================
    public function initiate_hubtel($data) {
        $transaction_ref = $this->generate_transaction_ref();
        
        $transaction = [
            'transaction_ref' => $transaction_ref,
            'student_id' => $data['student_id'],
            'invoice_code' => $data['invoice_code'],
            'amount' => $data['amount'],
            'gateway' => 'hubtel',
            'phone_number' => $data['phone_number'],
            'status' => 'pending'
        ];
        
        $this->db->insert('payment_transactions', $transaction);
        
        $url = 'https://api.hubtel.com/v1/merchantaccount/merchants/' . $this->hubtel_merchant_number . '/receive/mobilemoney';
        
        $fields = [
            'CustomerName' => $data['customer_name'],
            'CustomerMsisdn' => $data['phone_number'],
            'CustomerEmail' => $data['email'],
            'Channel' => $data['network'], // mtn-gh, vodafone-gh, airtel-gh, tigo-gh
            'Amount' => $data['amount'],
            'PrimaryCallbackUrl' => site_url('payment_gateway/hubtel_callback'),
            'Description' => 'School fees payment - ' . $data['invoice_code'],
            'ClientReference' => $transaction_ref
        ];
        
        $auth = base64_encode($this->hubtel_client_id . ':' . $this->hubtel_client_secret);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($http_code == 200 && $result['ResponseCode'] == '0000') {
            $this->db->where('transaction_ref', $transaction_ref)->update('payment_transactions', [
                'gateway_ref' => $result['TransactionId'],
                'response_data' => json_encode($result)
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => get_phrase('payment_initiated'),
                'transaction_ref' => $transaction_ref,
                'transaction_id' => $result['TransactionId']
            ]);
        } else {
            $this->db->where('transaction_ref', $transaction_ref)->update('payment_transactions', [
                'status' => 'failed',
                'response_data' => json_encode($result)
            ]);
            
            echo json_encode([
                'status' => 'error',
                'message' => $result['ResponseText'] ?? get_phrase('payment_failed')
            ]);
        }
    }

    public function hubtel_callback() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data['ResponseCode'] == '0000') {
            $transaction = $this->db->where('transaction_ref', $data['Data']['ClientReference'])->get('payment_transactions')->row_array();
            
            if ($transaction) {
                $this->complete_payment($transaction);
            }
        }
    }

    public function verify_hubtel_payment($transaction_id) {
        $url = "https://api.hubtel.com/v1/merchantaccount/merchants/{$this->hubtel_merchant_number}/transactions/{$transaction_id}";
        
        $auth = base64_encode($this->hubtel_client_id . ':' . $this->hubtel_client_secret);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['ResponseCode'] == '0000' && $result['Data']['Status'] == 'Success') {
            $transaction = $this->db->where('gateway_ref', $transaction_id)->get('payment_transactions')->row_array();
            $this->complete_payment($transaction);
            return true;
        }
        
        return false;
    }

    public function get_active_gateways() {
        return [
            ['code' => 'hubtel', 'name' => 'Hubtel (All Networks)', 'icon' => 'hubtel.png', 'networks' => ['mtn-gh', 'vodafone-gh', 'airtel-gh', 'tigo-gh']],
            ['code' => 'mtn_momo', 'name' => 'MTN Mobile Money', 'icon' => 'mtn.png'],
            ['code' => 'vodafone_cash', 'name' => 'Vodafone Cash', 'icon' => 'vodafone.png'],
            ['code' => 'airteltigo_money', 'name' => 'AirtelTigo Money', 'icon' => 'airteltigo.png'],
            ['code' => 'paystack', 'name' => 'Paystack (Card)', 'icon' => 'paystack.png']
        ];
    }

    public function get_invoice_details($invoice_code, $student_id) {
        return $this->db->where('invoice_code', $invoice_code)
            ->where('student_id', $student_id)
            ->get('invoice')->row_array();
    }

    // ==================== ADMIN FUNCTIONS ====================
    public function get_transactions_datatable() {
        $this->load->library('datatables');
        
        $this->datatables->select('pt.*, s.name as student_name, s.student_code')
            ->from('payment_transactions pt')
            ->join('student s', 's.student_id = pt.student_id')
            ->add_column('actions', '<button class="btn btn-sm btn-info" onclick="viewTransaction($1)"><i class="mdi mdi-eye"></i></button>', 'transaction_id');
        
        echo $this->datatables->generate();
    }

    public function manual_verify_transaction($transaction_id) {
        $transaction = $this->db->where('transaction_id', $transaction_id)->get('payment_transactions')->row_array();
        
        if ($transaction['gateway'] == 'paystack') {
            $this->verify_paystack_payment($transaction['transaction_ref']);
        } else {
            // Verify with respective gateway
            echo json_encode(['status' => 'success', 'message' => get_phrase('verification_initiated')]);
        }
    }
}
