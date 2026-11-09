<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Public_invoice extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function view($token) {
        // Validate token
        $token_data = $this->db->where('token', $token)
            ->where('expires_at >', time())
            ->get('invoice_access_tokens')->row();
        
        if(!$token_data) {
            $this->load->view('public/invoice_expired');
            return;
        }
        
        // Load invoice
        $param2 = $token_data->invoice_code;
        $this->load->view('backend/admin/modal_view_invoice_professional', ['param2' => $param2]);
    }
}
