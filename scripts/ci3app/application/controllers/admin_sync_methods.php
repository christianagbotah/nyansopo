// Add these methods to application/controllers/Admin.php

// Sync Dashboard
public function sync_dashboard()
{
    if($this->session->userdata('admin_login') != 1)
        redirect(site_url('login'), 'refresh');
    
    $page_data['page_name']  = 'sync_dashboard';
    $page_data['page_title'] = 'Multi-Device Sync Dashboard';
    $this->load->view('backend/index', $page_data);
}

// Get Sync Statistics (AJAX)
public function get_sync_stats()
{
    $stats = [
        'total_devices' => $this->db->where('status', 'ACTIVE')->count_all_results('sync_devices'),
        'pending_changes' => $this->db->where('synced', FALSE)->count_all_results('sync_queue'),
        'conflicts' => $this->db->where('resolution', 'PENDING')->count_all_results('sync_conflicts'),
        'last_sync' => $this->db->select_max('last_sync_at')->get('sync_devices')->row()->last_sync_at
    ];
    
    echo json_encode(['status' => 'success', 'data' => $stats]);
}

// Get Device List (AJAX)
public function get_device_list()
{
    $this->db->select('d.*, a.name as admin_name, t.name as teacher_name');
    $this->db->from('sync_devices d');
    $this->db->join('admin a', 'd.user_id = a.admin_id AND d.user_type = "admin"', 'left');
    $this->db->join('teacher t', 'd.user_id = t.teacher_id AND d.user_type = "teacher"', 'left');
    $devices = $this->db->get()->result_array();
    
    foreach($devices as &$device) {
        $device['pending'] = $this->db->where('device_id', $device['device_id'])
                                      ->where('synced', FALSE)
                                      ->count_all_results('sync_queue');
    }
    
    echo json_encode(['status' => 'success', 'devices' => $devices]);
}

// Force Sync Device (AJAX)
public function force_sync_device()
{
    $device_id = $this->input->post('device_id');
    
    $this->load->library('Sync_service');
    $synced = $this->sync_service->push_changes($device_id);
    
    echo json_encode(['status' => 'success', 'synced' => $synced]);
}
