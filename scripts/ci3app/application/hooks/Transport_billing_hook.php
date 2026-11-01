<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transport_billing_hook {
    
    // Call this after attendance is saved/updated
    public function recalculate_transport_billing($student_id, $date) {
        $CI =& get_instance();
        $CI->load->database();
        
        // Check if student has transport
        $enroll = $CI->db->select('transport_id')
            ->from('enroll')
            ->where('student_id', $student_id)
            ->where('transport_id IS NOT NULL')
            ->get()->row();
        
        if ($enroll && $enroll->transport_id) {
            // Trigger recalculation
            $url = base_url('transport_auto_billing/recalculate_billing/' . $student_id . '/' . $date);
            
            // Use cURL to call the recalculation endpoint
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
