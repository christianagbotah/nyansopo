<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transport_auto_billing extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Transport_billing_model');
    }
    
    // Run daily to check and bill students
    public function process_daily_billing($date = null) {
        $date = $date ?? date('Y-m-d');
        
        // Get all students with active transport
        $students = $this->db->select('e.student_id, e.transport_id, t.route_fare')
            ->from('enroll e')
            ->join('transport t', 't.transport_id = e.transport_id')
            ->where('e.transport_id IS NOT NULL')
            ->where('e.transport_id >', 0)
            ->get()->result_array();
        
        foreach ($students as $student) {
            $this->check_and_bill_student($student['student_id'], $student['transport_id'], $date);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Daily billing processed']);
    }
    
    // Check individual student and bill if necessary
    private function check_and_bill_student($student_id, $transport_id, $date) {
        // Check if already billed
        $existing = $this->db->get_where('transport_auto_billing', [
            'student_id' => $student_id,
            'billing_date' => $date
        ])->row();
        
        if ($existing) return;
        
        // Check attendance
        $attendance = $this->db->get_where('attendance', [
            'student_id' => $student_id,
            'timestamp' => strtotime($date)
        ])->row();
        
        $was_present = $attendance && $attendance->status == 1 ? 'yes' : 'no';
        
        // Check bus conductor log
        $bus_log = $this->db->get_where('transport_daily_log', [
            'student_id' => $student_id,
            'log_date' => $date
        ])->row();
        
        $boarded_bus = $bus_log && $bus_log->boarded_bus == 'yes' ? 'yes' : 'no';
        $paid_fare = $bus_log && $bus_log->paid_fare == 'yes' ? 'yes' : 'no';
        
        // Bill if present AND boarded bus AND didn't pay
        if ($was_present == 'yes' && $boarded_bus == 'yes' && $paid_fare == 'no') {
            $transport = $this->db->get_where('transport', ['transport_id' => $transport_id])->row();
            
            $this->db->insert('transport_auto_billing', [
                'student_id' => $student_id,
                'transport_id' => $transport_id,
                'billing_date' => $date,
                'fare_amount' => $transport->route_fare,
                'was_present' => $was_present,
                'boarded_bus' => $boarded_bus,
                'billing_status' => 'confirmed'
            ]);
            
            // Update transport_fare balance
            $this->update_transport_fare_balance($student_id, $transport_id, $transport->route_fare);
        }
    }
    
    // Recalculate past records when attendance changes
    public function recalculate_billing($student_id, $date) {
        $billing = $this->db->get_where('transport_auto_billing', [
            'student_id' => $student_id,
            'billing_date' => $date
        ])->row();
        
        if (!$billing) return;
        
        // Re-check attendance
        $attendance = $this->db->get_where('attendance', [
            'student_id' => $student_id,
            'timestamp' => strtotime($date)
        ])->row();
        
        $was_present = $attendance && $attendance->status == 1 ? 'yes' : 'no';
        
        // Re-check bus log
        $bus_log = $this->db->get_where('transport_daily_log', [
            'student_id' => $student_id,
            'log_date' => $date
        ])->row();
        
        $boarded_bus = $bus_log && $bus_log->boarded_bus == 'yes' ? 'yes' : 'no';
        
        // If conditions no longer met, reverse billing
        if ($was_present == 'no' || $boarded_bus == 'no') {
            $this->db->where('billing_id', $billing->billing_id)
                ->update('transport_auto_billing', [
                    'billing_status' => 'reversed',
                    'was_present' => $was_present,
                    'boarded_bus' => $boarded_bus
                ]);
            
            // Reverse the fare charge
            $this->update_transport_fare_balance($student_id, $billing->transport_id, -$billing->fare_amount);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Billing recalculated']);
    }
    
    private function update_transport_fare_balance($student_id, $transport_id, $amount) {
        $fare = $this->db->get_where('transport_fare', [
            'student_id' => $student_id,
            'transport_id' => $transport_id
        ])->row();
        
        if ($fare) {
            $this->db->where('student_id', $student_id)
                ->where('transport_id', $transport_id)
                ->set('balance', 'balance + ' . $amount, FALSE)
                ->update('transport_fare');
        }
    }
}
