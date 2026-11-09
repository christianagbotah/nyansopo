<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Insurance model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Insurance_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}

	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}

	//Select all Insurance - generic
	function getAllInsurance() {
		$all_insurance = $this->db->get('v_car_insurance');

		return $all_insurance;
	}

	//Select Vehicle - by id in array
  function getInsuranceByIdArray($insurance_id) {
      $this->db->where('insurance_id', $insurance_id);
      $insurance = $this->db->get('v_car_insurance')->result();

      foreach($insurance as $in) {
      	return $in;
      }
      
  }

  //FILTER VEHICLE INSURANCE LIST
  function getAllVehicleInsuranceDataTable() {

		$insurance_array = array();
		$data = array();


        $columns = array(
            0 => 'insurance_id',
            1 => 'sn',
            2 => 'insurance_name',
            3 => 'insurance_company',
            4 => 'date_created',
            5 => 'date_modified',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allVehicleInsuranceCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $insurance_query = $this->ajaxload->getAllVehiclesInsuranceDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $insurance_query =  $this->ajaxload->vehicleInsuranceSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->vehicleInsuranceSearchCount($search);
        }
        

        if(!empty($insurance_query)) {
        		$sn = 1;
            foreach ($insurance_query as $insurance) {

                $nestedData['sn'] = $sn;
                $nestedData['insurance_name'] = $insurance->insurance_name;
                $nestedData['insurance_company'] = $insurance->insurance_company;
                $nestedData['date_created'] = date('d-m-Y', $insurance->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $insurance->date_modified);

                $nestedData['DT_RowId'] = 'insurance_id_'.$insurance->insurance_id;
                $nestedData['onclick'] = "populate_links('".$insurance->insurance_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $insurance_array[] = $insurance->insurance_id;

							$sn++;               
            }

				    /*$insurance = $sn > 1 ? 'Insurance' : 'Insurance';
				    $bt_row['bottom_row'] = '
										            <div class="col-md-6 col-sm-6 text-lg-left"><strong>'.number_format($sn, 0, '.', ',') . ' '.$insurance.'</strong></div>';*/

        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "insurance_ids"   => $insurance_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }

    //Add Insurance
	function add_insurance($insurance_data) {
		$this->db->insert('v_car_insurance', $insurance_data);
		$insurance_id = $this->db->insert_id();

		if($this->db->affected_rows() > 0) {
			return $insurance_id; //successful
		} else {
			return 0; //failed
		}
	}

    //Edit Insurance
    function edit_insurance($insurance_data, $insurance_id) {
        $this->db->where('insurance_id', $insurance_id);
        $this->db->update('v_car_insurance', $insurance_data);

        return 1;
    }

    //Delete Insurance
    function delete_insurance($insurance_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;

        $num_rows = $this->db->get_where('v_vehicle', array('insurance_id' => $insurance_id))->num_rows();
        if($num_rows > 0) {
            //deny deleting
            $can_delete = false;

        } else {
            //grant deleting
            $this->db->where('insurance_id', $insurance_id);
            $this->db->delete('v_car_insurance');
        }
        
        return $can_delete;      
    }
}