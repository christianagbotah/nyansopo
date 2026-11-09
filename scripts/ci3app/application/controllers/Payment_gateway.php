<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_gateway extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    // ==================== STUDENT PORTAL ====================
    public function pay_invoice($invoice_code = '') {
        if ($this->session->userdata('student_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        $student_id = $this->session->userdata('student_id');
        $page_data['invoice'] = $this->Payment_gateway_model->get_invoice_details($invoice_code, $student_id);
        $page_data['gateways'] = $this->Payment_gateway_model->get_active_gateways();
        $page_data['page_name'] = 'payment_gateway/pay_invoice';
        $page_data['page_title'] = get_phrase('pay_invoice');
        $this->load->view('backend/index', $page_data);
    }

    // ==================== MOBILE MONEY ====================
    public function initiate_momo_payment() {
        $data = [
            'student_id' => $this->session->userdata('student_id'),
            'invoice_code' => $this->input->post('invoice_code'),
            'amount' => $this->input->post('amount'),
            'gateway' => $this->input->post('gateway'),
            'phone_number' => $this->input->post('phone_number')
        ];
        
        $this->Payment_gateway_model->initiate_momo_payment($data);
    }

    public function momo_callback() {
        $this->Payment_gateway_model->process_momo_callback();
    }

    // ==================== PAYSTACK ====================
    public function initiate_paystack() {
        $data = [
            'student_id' => $this->session->userdata('student_id'),
            'invoice_code' => $this->input->post('invoice_code'),
            'amount' => $this->input->post('amount'),
            'email' => $this->input->post('email')
        ];
        
        $this->Payment_gateway_model->initiate_paystack($data);
    }

    public function paystack_callback() {
        $reference = $this->input->get('reference');
        $this->Payment_gateway_model->verify_paystack_payment($reference);
    }

    // ==================== HUBTEL ====================
    public function initiate_hubtel() {
        $data = [
            'student_id' => $this->session->userdata('student_id'),
            'invoice_code' => $this->input->post('invoice_code'),
            'amount' => $this->input->post('amount'),
            'phone_number' => $this->input->post('phone_number'),
            'network' => $this->input->post('network'),
            'customer_name' => $this->input->post('customer_name'),
            'email' => $this->input->post('email')
        ];
        
        $this->Payment_gateway_model->initiate_hubtel($data);
    }

    public function hubtel_callback() {
        $this->Payment_gateway_model->hubtel_callback();
    }

    public function verify_hubtel($transaction_id) {
        $result = $this->Payment_gateway_model->verify_hubtel_payment($transaction_id);
        echo json_encode([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? get_phrase('payment_verified') : get_phrase('verification_failed')
        ]);
    }

    public function check_status($transaction_ref) {
        $transaction = $this->db->where('transaction_ref', $transaction_ref)->get('payment_transactions')->row_array();
        if ($transaction) {
            echo json_encode(['status' => $transaction['status']]);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
    }

    // ==================== ADMIN - TRANSACTIONS ====================
    public function transactions($action = '', $id = '') {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        if ($action == 'list' || $action == '') {
            $page_data['page_name'] = 'payment_gateway/transactions';
            $page_data['page_title'] = get_phrase('payment_transactions');
            $this->load->view('backend/index', $page_data);
        } elseif ($action == 'get_data') {
            $this->Payment_gateway_model->get_transactions_datatable();
        } elseif ($action == 'verify') {
            $this->Payment_gateway_model->manual_verify_transaction($id);
        }
    }

    // ==================== WEBHOOK HANDLERS ====================
    public function webhook_mtn() {
        $this->Payment_gateway_model->handle_mtn_webhook();
    }

    public function webhook_vodafone() {
        $this->Payment_gateway_model->handle_vodafone_webhook();
    }

    public function webhook_paystack() {
        $this->Payment_gateway_model->handle_paystack_webhook();
    }

    public function webhook_hubtel() {
        $this->hubtel_callback();
    }
}
