<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test_sync extends CI_Controller {
    
    public function index() {
        $this->load->view('test_sync_view');
    }
    
    public function test_endpoint() {
        header('Content-Type: application/json');
        
        $data = [
            'name' => $this->input->post('name'),
            'email' => $this->input->post('email'),
            'amount' => $this->input->post('amount'),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Data received successfully',
            'data' => $data
        ]);
    }
}
