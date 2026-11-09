<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transport model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Transport_model extends MY_Model {

    // Vehicles
    public function get_vehicles($transport_id = null) {
        $this->db->select('v.*, t.route_name, d.name as driver_name');
        $this->db->from('vehicles v');
        $this->db->join('transport t', 'v.transport_id = t.transport_id', 'left');
        $this->db->join('vehicle_assignments va', 'v.vehicle_id = va.vehicle_id AND va.status = "active"', 'left');
        $this->db->join('drivers d', 'va.driver_id = d.driver_id', 'left');
        if ($transport_id) $this->db->where('v.transport_id', $transport_id);
        return $this->db->get()->result();
    }

    public function get_vehicle($vehicle_id) {
        return $this->db->get_where('vehicles', array('vehicle_id' => $vehicle_id))->row();
    }

    // Drivers
    public function get_drivers($status = null) {
        if ($status) $this->db->where('status', $status);
        return $this->db->get('drivers')->result();
    }

    public function get_driver($driver_id) {
        return $this->db->get_where('drivers', array('driver_id' => $driver_id))->row();
    }

    // Insurance
    public function get_insurance($vehicle_id = null) {
        $this->db->select('vi.*, v.vehicle_number');
        $this->db->from('vehicle_insurance vi');
        $this->db->join('vehicles v', 'vi.vehicle_id = v.vehicle_id');
        if ($vehicle_id) $this->db->where('vi.vehicle_id', $vehicle_id);
        $this->db->order_by('vi.expiry_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_expiring_insurance($days = 30) {
        $this->db->select('vi.*, v.vehicle_number');
        $this->db->from('vehicle_insurance vi');
        $this->db->join('vehicles v', 'vi.vehicle_id = v.vehicle_id');
        $this->db->where('vi.status', 'active');
        $this->db->where('vi.expiry_date <=', date('Y-m-d', strtotime("+$days days")));
        $this->db->where('vi.expiry_date >=', date('Y-m-d'));
        return $this->db->get()->result();
    }

    // Maintenance
    public function get_maintenance($vehicle_id = null) {
        $this->db->select('vm.*, v.vehicle_number');
        $this->db->from('vehicle_maintenance vm');
        $this->db->join('vehicles v', 'vm.vehicle_id = v.vehicle_id');
        if ($vehicle_id) $this->db->where('vm.vehicle_id', $vehicle_id);
        $this->db->order_by('vm.maintenance_date', 'DESC');
        return $this->db->get()->result();
    }

    // Assignments
    public function assign_driver($vehicle_id, $driver_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        $this->db->update('vehicle_assignments', array('status' => 'completed'));
        
        $data = array(
            'vehicle_id' => $vehicle_id,
            'driver_id' => $driver_id,
            'assigned_date' => date('Y-m-d'),
            'status' => 'active'
        );
        return $this->db->insert('vehicle_assignments', $data);
    }

    // Statistics
    public function get_stats() {
        $stats = array();
        $stats['total_routes'] = $this->db->count_all('transport');
        $stats['total_vehicles'] = $this->db->count_all('vehicles');
        $stats['active_drivers'] = $this->db->where('status', 'active')->count_all_results('drivers');
        $stats['students_using_transport'] = $this->db->where('transport_id IS NOT NULL')->count_all_results('student');
        return $stats;
    }
}
