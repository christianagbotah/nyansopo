<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transport_model extends CI_Model {

    // ROUTES
    public function create_route($data) {
        return $this->db->insert('transport_routes', $data);
    }

    public function update_route($route_id, $data) {
        $this->db->where('route_id', $route_id);
        return $this->db->update('transport_routes', $data);
    }

    public function delete_route($route_id) {
        $this->db->where('route_id', $route_id);
        return $this->db->delete('transport_routes');
    }

    public function get_all_routes() {
        return $this->db->get('transport_routes')->result_array();
    }

    public function update_route_stops_count($route_id) {
        $count = $this->db->where('route_id', $route_id)->count_all_results('transport_stops');
        $this->db->where('route_id', $route_id);
        $this->db->update('transport_routes', array('total_stops' => $count));
    }

    // STOPS
    public function add_stop($data) {
        return $this->db->insert('transport_stops', $data);
    }

    public function get_route_stops($route_id) {
        $this->db->where('route_id', $route_id);
        $this->db->order_by('stop_order', 'ASC');
        return $this->db->get('transport_stops')->result_array();
    }

    // VEHICLES
    public function create_vehicle($data) {
        return $this->db->insert('transport_vehicles', $data);
    }

    public function update_vehicle($vehicle_id, $data) {
        $this->db->where('vehicle_id', $vehicle_id);
        return $this->db->update('transport_vehicles', $data);
    }

    public function delete_vehicle($vehicle_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        return $this->db->delete('transport_vehicles');
    }

    public function get_all_vehicles() {
        $this->db->select('v.*, d.name as driver_name, r.route_name as assigned_route');
        $this->db->from('transport_vehicles v');
        $this->db->join('transport_assignments a', 'v.vehicle_id = a.vehicle_id AND a.status = "active"', 'left');
        $this->db->join('transport_drivers d', 'a.driver_id = d.driver_id', 'left');
        $this->db->join('transport_routes r', 'a.route_id = r.route_id', 'left');
        return $this->db->get()->result_array();
    }

    public function log_maintenance($data) {
        return $this->db->insert('transport_vehicle_maintenance', $data);
    }

    // DRIVERS
    public function create_driver($data) {
        return $this->db->insert('transport_drivers', $data);
    }

    public function update_driver($driver_id, $data) {
        $this->db->where('driver_id', $driver_id);
        return $this->db->update('transport_drivers', $data);
    }

    public function delete_driver($driver_id) {
        $this->db->where('driver_id', $driver_id);
        return $this->db->delete('transport_drivers');
    }

    public function get_all_drivers() {
        $this->db->select('d.*, v.vehicle_number');
        $this->db->from('transport_drivers d');
        $this->db->join('transport_assignments a', 'd.driver_id = a.driver_id AND a.status = "active"', 'left');
        $this->db->join('transport_vehicles v', 'a.vehicle_id = v.vehicle_id', 'left');
        return $this->db->get()->result_array();
    }

    // ASSIGNMENTS
    public function create_assignment($data) {
        return $this->db->insert('transport_assignments', $data);
    }

    public function assign_student($data) {
        return $this->db->insert('transport_student_assignment', $data);
    }

    public function get_students_by_route($route_id) {
        $this->db->select('s.*, tsa.stop_id, ts.stop_name, tsa.transport_type');
        $this->db->from('transport_student_assignment tsa');
        $this->db->join('student s', 'tsa.student_id = s.student_id');
        $this->db->join('transport_stops ts', 'tsa.stop_id = ts.stop_id');
        $this->db->where('tsa.route_id', $route_id);
        $this->db->where('tsa.status', 'active');
        return $this->db->get()->result_array();
    }

    // ATTENDANCE
    public function mark_attendance($data) {
        $existing = $this->db->where(array(
            'student_id' => $data['student_id'],
            'attendance_date' => $data['attendance_date']
        ))->get('transport_attendance')->row();

        if ($existing) {
            $this->db->where('attendance_id', $existing->attendance_id);
            return $this->db->update('transport_attendance', $data);
        } else {
            return $this->db->insert('transport_attendance', $data);
        }
    }

    public function get_attendance($date, $route_id = null) {
        $this->db->select('ta.*, s.name as student_name, c.name as class_name, tr.route_name, ts.stop_name');
        $this->db->from('transport_attendance ta');
        $this->db->join('student s', 'ta.student_id = s.student_id');
        $this->db->join('class c', 's.class_id = c.class_id');
        $this->db->join('transport_routes tr', 'ta.route_id = tr.route_id');
        $this->db->join('transport_stops ts', 'ta.stop_id = ts.stop_id');
        $this->db->where('ta.attendance_date', $date);
        
        if ($route_id) {
            $this->db->where('ta.route_id', $route_id);
        }
        
        return $this->db->get()->result_array();
    }

    // TRACKING
    public function update_tracking($data) {
        return $this->db->insert('transport_vehicle_tracking', $data);
    }

    public function get_active_vehicle_positions() {
        $this->db->select('v.vehicle_id, v.vehicle_number, r.route_name, d.name as driver_name, 
                          t.latitude, t.longitude, t.speed, 
                          (SELECT COUNT(*) FROM transport_student_assignment WHERE route_id = a.route_id AND status = "active") as student_count,
                          v.seating_capacity as capacity');
        $this->db->from('transport_vehicles v');
        $this->db->join('transport_assignments a', 'v.vehicle_id = a.vehicle_id AND a.status = "active"');
        $this->db->join('transport_routes r', 'a.route_id = r.route_id');
        $this->db->join('transport_drivers d', 'a.driver_id = d.driver_id');
        $this->db->join('(SELECT vehicle_id, latitude, longitude, speed FROM transport_vehicle_tracking 
                         WHERE (vehicle_id, timestamp) IN 
                         (SELECT vehicle_id, MAX(timestamp) FROM transport_vehicle_tracking GROUP BY vehicle_id)) t', 
                         'v.vehicle_id = t.vehicle_id', 'left');
        $this->db->where('v.status', 'active');
        
        return $this->db->get()->result_array();
    }

    // NOTIFICATIONS
    public function create_notification($data) {
        return $this->db->insert('transport_notifications', $data);
    }

    // INCIDENTS
    public function log_incident($data) {
        return $this->db->insert('transport_incidents', $data);
    }

    public function get_incidents($filters = array()) {
        $this->db->select('i.*, v.vehicle_number, d.name as driver_name, r.route_name');
        $this->db->from('transport_incidents i');
        $this->db->join('transport_vehicles v', 'i.vehicle_id = v.vehicle_id', 'left');
        $this->db->join('transport_drivers d', 'i.driver_id = d.driver_id', 'left');
        $this->db->join('transport_routes r', 'i.route_id = r.route_id', 'left');
        
        if (!empty($filters)) {
            $this->db->where($filters);
        }
        
        $this->db->order_by('i.incident_date', 'DESC');
        return $this->db->get()->result_array();
    }

    // PAYMENTS
    public function record_payment($data) {
        return $this->db->insert('transport_payments', $data);
    }

    public function get_student_payment_history($student_id) {
        $this->db->where('student_id', $student_id);
        $this->db->order_by('payment_date', 'DESC');
        return $this->db->get('transport_payments')->result_array();
    }
}
