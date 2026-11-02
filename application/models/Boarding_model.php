<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Boarding model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Boarding_model extends MY_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }  

    function getAdmissionFeeByResidentialStatus($res) {

        if($res == 'Day') {

            $this->db->select_sum('amount');
            $this->db->from('bill_item');
            $this->db->where('bill_category_id', '2');
            $amount = $this->db->get()->row()->amount;

        } else {

            $this->db->select_sum('amount');
            $this->db->from('bill_item');
            $this->db->where('bill_category_id', '3');
            $amount = $this->db->get()->row()->amount;
        }

        return $amount;
    }

    function getAdmissionItemIdByResidentialStatus($res) {

        if($res == 'Day') {
            $item_id = 9; /*for day students admission*/

        } else {

            $item_id = 8; /*for boarding admission*/
        }

        return $item_id;
    }


    /*get house, dormitory and bed*/
    function getAllAvailableHouses() {

        return $this->db->get_where('boarding_house', ['house_status' =>'Available'])->result_array();
    }

    function getAllAvailableDormitories() {

        return $this->db->get_where('boarding_dormitory', ['dormitory_status' => 'Available'])->result_array();
    }

    function getAllAvailableBeds() {

        return $this->db->get_where('boarding_bed', ['bed_status' => 'Available'])->result_array();
    }

    /*get all dorms by house id*/
    function getAllAvailableDormitoriesByHouseId($house_id) {

        return $this->db->get_where('boarding_dormitory', ['dormitory_status' => 'Available', 'house_id' => $house_id])->result_array();
    }


    /*get all beds by dorm id*/
    function getAllAvailableBedsByDormitoryId($dormitory_id) {

        return $this->db->get_where('boarding_bed', ['bed_status' => 'Available', 'dormitory_id' => $dormitory_id])->result_array();
    }

    /*CREATIONS, UPDATES AND DELETES*/
    function manageBoardingHouse($param, $data, $house_id = null) {
        if($param == 'create') {
            return $this->db->insert('boarding_house', $data);
        } elseif($param == 'update' && $house_id) {
            $this->db->where('house_id', $house_id);
            return $this->db->update('boarding_house', $data);
        } elseif($param == 'delete' && $house_id) {
            $this->db->where('house_id', $house_id);
            return $this->db->delete('boarding_house');
        }
        return false;
    }

    function manageBoardingDormitory($param, $data, $dormitory_id = null) {
        if($param == 'create') {
            return $this->db->insert('boarding_dormitory', $data);
        } elseif($param == 'update' && $dormitory_id) {
            $this->db->where('dormitory_id', $dormitory_id);
            return $this->db->update('boarding_dormitory', $data);
        } elseif($param == 'delete' && $dormitory_id) {
            $this->db->where('dormitory_id', $dormitory_id);
            return $this->db->delete('boarding_dormitory');
        }
        return false;
    }

    function manageBoardingBed($param, $data, $bed_id = null) {
        if($param == 'create') {
            return $this->db->insert('boarding_bed', $data);
        } elseif($param == 'update' && $bed_id) {
            $this->db->where('bed_id', $bed_id);
            return $this->db->update('boarding_bed', $data);
        } elseif($param == 'delete' && $bed_id) {
            $this->db->where('bed_id', $bed_id);
            return $this->db->delete('boarding_bed');
        }
        return false;
    }

    function getHouseById($house_id) {
        return $this->db->get_where('boarding_house', ['house_id' => $house_id])->row_array();
    }

    function getDormitoryById($dormitory_id) {
        return $this->db->get_where('boarding_dormitory', ['dormitory_id' => $dormitory_id])->row_array();
    }

    function getBedById($bed_id) {
        return $this->db->get_where('boarding_bed', ['bed_id' => $bed_id])->row_array();
    }

    function getAllHouses() {
        return $this->db->get('boarding_house')->result_array();
    }

    function getAllDormitories() {
        return $this->db->get('boarding_dormitory')->result_array();
    }

    function getDormitoriesByHouse($house_id) {
        return $this->db->get_where('boarding_dormitory', ['house_id' => $house_id])->result_array();
    }

    function getBedsByDormitory($dormitory_id) {
        return $this->db->get_where('boarding_bed', ['dormitory_id' => $dormitory_id])->result_array();
    }

    function assignStudentToBed($student_id, $bed_id, $house_id, $dormitory_id) {
        $this->db->trans_start();
        
        // Update bed status
        $this->db->where('bed_id', $bed_id);
        $this->db->update('boarding_bed', [
            'bed_status' => 'Assigned',
            'student_id' => $student_id,
            'assigned_date' => date('Y-m-d')
        ]);
        
        // Create residence record
        $this->db->insert('student_residence', [
            'student_id' => $student_id,
            'residence_type' => 'Boarding',
            'house_id' => $house_id,
            'dormitory_id' => $dormitory_id,
            'bed_id' => $bed_id,
            'year' => get_settings('running_year'),
            'term' => get_settings('running_term'),
            'status' => 'Active',
            'assigned_date' => date('Y-m-d')
        ]);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    function unassignStudentFromBed($bed_id) {
        $this->db->trans_start();
        
        $bed = $this->getBedById($bed_id);
        if($bed && $bed['student_id']) {
            // Update residence status
            $this->db->where('student_id', $bed['student_id']);
            $this->db->where('bed_id', $bed_id);
            $this->db->where('status', 'Active');
            $this->db->update('student_residence', ['status' => 'Inactive']);
            
            // Free the bed
            $this->db->where('bed_id', $bed_id);
            $this->db->update('boarding_bed', [
                'bed_status' => 'Available',
                'student_id' => null,
                'assigned_date' => null
            ]);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    function getStudentResidence($student_id) {
        $this->db->where('student_id', $student_id);
        $this->db->where('year', get_settings('running_year'));
        $this->db->where('term', get_settings('running_term'));
        $this->db->where('status', 'Active');
        return $this->db->get('student_residence')->row_array();
    }

    function getOccupiedBedsCount($dormitory_id = null) {
        $this->db->where('bed_status', 'Assigned');
        if($dormitory_id) {
            $this->db->where('dormitory_id', $dormitory_id);
        }
        return $this->db->count_all_results('boarding_bed');
    }

    function getAvailableBedsCount($dormitory_id = null) {
        $this->db->where('bed_status', 'Available');
        if($dormitory_id) {
            $this->db->where('dormitory_id', $dormitory_id);
        }
        return $this->db->count_all_results('boarding_bed');
    }

    /*Get residence type*/
    function get_residence_type($student_id) {
        $this->db->order_by('enroll_id', 'desc');
        $residence_type = $this->db->get_where('enroll', ['student_id' => $student_id])->first_row()->residence_type;

        return $residence_type;
    }

    /*all boarders count*/
    function allBoardersCount() {

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        return $this->db->get_where('enroll', ['year' => $running_year, 'term' => $running_term, 'mute' => '0', 'residence_type' => 'Boarding'])->num_rows();
    }

    /*all day students count*/
    function allDayStudentsCount() {

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        return $this->db->get_where('enroll', ['year' => $running_year, 'term' => $running_term, 'mute' => '0', 'residence_type' => 'Day'])->num_rows();
    }

    /*all boarders count by class*/
    function classBoardersCount($class_id) {

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        return $this->db->get_where('enroll', ['year' => $running_year, 'term' => $running_term, 'mute' => '0', 'residence_type' => 'Boarding', 'class_id' => $class_id])->num_rows();
    }

    /*all day students count by*/
    function classDayStudentsCount($class_id) {

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        return $this->db->get_where('enroll', ['year' => $running_year, 'term' => $running_term, 'mute' => '0', 'residence_type' => 'Day', 'class_id' => $class_id])->num_rows();
    }

    /*WE GET THE BILL ITEM CATEGORY ID BY BILL ITEM ID*/
    function getBillItemCategoryIdByBillItemId($bill_item_id) {

            $this->db->where('id', $bill_item_id);
            $cat_id = $this->db->get('bill_item')->row()->bill_category_id;

        return $cat_id;
    }
}
