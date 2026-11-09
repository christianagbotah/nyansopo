<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Get discount types based on category
 * Returns bill items for invoice category or enabled daily fees for daily_fees category
 */
function get_discount_types() {
    $category = $this->input->get('category');
    $response = array('status' => 'error', 'message' => 'Invalid category', 'data' => array());
    
    if($category === 'invoice') {
        // Get all invoice items from bill_item table
        $this->db->select('id, title');
        $this->db->from('bill_item');
        $this->db->where('school_id', $this->session->userdata('school_id'));
        $this->db->order_by('title', 'ASC');
        $query = $this->db->get();
        
        if($query->num_rows() > 0) {
            $items = array();
            
            // Add "All Bill Items" option with special marker
            $items[] = array(
                'value' => '*',
                'label' => '✓ All Bill Items (Dynamic)'
            );
            
            foreach($query->result() as $row) {
                $items[] = array(
                    'value' => $row->id,
                    'label' => ucwords($row->title)
                );
            }
            
            $response = array(
                'status' => 'success',
                'message' => 'Invoice items loaded',
                'data' => $items
            );
        } else {
            $response = array(
                'status' => 'success',
                'message' => 'No bill items found',
                'data' => array()
            );
        }
        
    } elseif($category === 'daily_fees') {
        // Get enabled daily fee modules from settings
        $items = array();
        
        // Add "All Daily Fees" option with special marker
        $items[] = array(
            'value' => '*',  // Special marker for ALL fees
            'label' => '✓ All Daily Fees (Dynamic)'
        );
        
        $fee_types = array('feeding', 'classes', 'water', 'breakfast', 'transport');
        
        foreach($fee_types as $fee_type) {
            $setting = $this->db->get_where('settings', array(
                'type' => 'fee_module_' . $fee_type
            ))->row();
            
            // Only include if enabled (value = 1)
            if($setting && $setting->description == '1') {
                $items[] = array(
                    'value' => $fee_type,
                    'label' => ucfirst($fee_type)
                );
            }
        }
        
        $response = array(
            'status' => 'success',
            'message' => 'Daily fees loaded',
            'data' => $items
        );
    }
    
    echo json_encode($response);
}
