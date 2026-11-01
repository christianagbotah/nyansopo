/**
 * Sync offline data to database
 * Add this method to Attendance_enterprise controller
 */
public function sync() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (empty($data['records'])) {
        echo json_encode(['status' => 'error', 'message' => 'No records to sync']);
        return;
    }
    
    $this->db->trans_start();
    
    foreach ($data['records'] as $record) {
        unset($record['id']);
        unset($record['synced']);
        $this->db->replace('attendance', $record);
    }
    
    $this->db->trans_complete();
    
    echo json_encode([
        'status' => $this->db->trans_status() ? 'success' : 'error',
        'message' => $this->db->trans_status() ? 'Synced successfully' : 'Sync failed'
    ]);
}
