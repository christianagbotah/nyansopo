<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Driver model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Driver_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}

	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}

	//Select all Drivers - generic
	function getAllDrivers() {
		$this->db->order_by('first_name', 'asc');
		$all_drivers = $this->db->get('v_driver');

		return $all_drivers;
	}

	//Select all active Drivers - search where active = 1
	function getAllActiveDrivers() {
		$this->db->order_by('first_name', 'asc');
		$all_active_drivers = $this->db->get_where('v_driver', array('active' => '1'));

		return $all_active_drivers;
	}

	//get driver image
  function getDriverImageById($driver_id = '') {
    $this->db->where('driver_id', $driver_id);
    $gender_id = $this->db->get('v_driver')->row()->gender_id;

    if(file_exists("uploads/driver_image/" . $driver_id . ".jpg")) {
       $image_url = base_url('uploads/driver_image/' . $driver_id . '.jpg'); 
   } else {
       if($gender_id == 1) { //male
            $image_url = base_url('uploads/user_male.jpg');
       } else { // female
            $image_url = base_url('uploads/user_female.jpg');
       }
   }
  	

  	return $image_url;
  }

  //Select all marital status - generic
	function getAllMaritalStatus() {
		$all_marital_status = $this->db->get('v_marital_status');

		return $all_marital_status;
	}

    //Select marital status by id - by id
    function getMaritalStatusById($status_id) {
        $this->db->where('status_id', $status_id);
        $marital_status = $this->db->get('v_marital_status')->row()->status_name;

        return $marital_status;
    }

	//Select gender - generic
	function getAllGender() {
		$all_gender = $this->db->get('v_gender');

		return $all_gender;
	}

    //Select gender by id - by id
    function getAllGenderById($gender_id) {
        $this->db->where('gender_id', $gender_id);
        $gender = $this->db->get('v_gender')->row()->gender_name;

        return $gender;
    }

	//Select license - generic
	function getAllLicenseTypes() {
		$all_license = $this->db->get('v_license_type');

		return $all_license;
	}

	//Add Driver
	function add_driver($driver_data) {
		$this->db->insert('v_driver', $driver_data);
		$driver_id = $this->db->insert_id();

        //update vehicle table
        $this->db->where('vehicle_id', $driver_data['vehicle_id']);
        $this->db->set('driver_id', $driver_id);
        $this->db->update('v_vehicle');

		if($this->db->affected_rows() > 0) {
			return $driver_id; //successful
		} else {
			return 0; //failed
		}
	}

    //Edit Driver
    function edit_driver($driver_data, $driver_id) {
        $this->db->where('driver_id', $driver_id);
        $earlier_vid = $this->db->get('v_driver')->row()->vehicle_id;

        $this->db->where('driver_id', $driver_id);
        $this->db->update('v_driver', $driver_data);

        //update vehicle table
        if($driver_data['vehicle_id'] == 0) {
            //removing vehicle for this driver
            $this->db->where('vehicle_id', $earlier_vid);
            $this->db->set('driver_id', '0');
            $this->db->update('v_vehicle');
        } else {
            $this->db->where('vehicle_id', $earlier_vid);
            $this->db->set('driver_id', $driver_id);
            $this->db->update('v_vehicle');
        }
        

        return 1;
    }

    //Delete Driver
    function delete_driver($driver_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;
        $num_rows_exp = $this->db->get_where('v_expenditure', array('driver_id' => $driver_id))->num_rows();

        $num_rows = $this->db->get_where('v_loading', array('driver_id' => $driver_id))->num_rows();

        if($num_rows_exp > 0) { 
            //deny deleting
            $can_delete = false;
        } else {

            if($num_rows > 0) {
                //deny deleting
                $can_delete = false;

            } else {
                //grant deleting
                $this->db->where('driver_id', $driver_id);
                $this->db->delete('v_driver');
            }
        }
        
        return $can_delete;
        
    }



	//FILTER DRIVER LIST
  function getAllDriversDataTable() {

		$driver_array = array();
		$data = array();


        $columns = array(
            0 => 'driver_id',
            1 => 'sn',
            2 => 'first_name',
            3 => 'last_name',
            4 => 'middle_name',
            5 => 'mobile_1',
            7 => 'email',
            8 => 'date_created',
            9 => 'date_modified',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allDriverCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $driver_query = $this->ajaxload->getAllDriversDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $driver_query =  $this->ajaxload->driverSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->driverSearchCount($search);
        }
        

        if(!empty($driver_query)) {
        		$sn = 1;
            foreach ($driver_query as $driver) {

                $nestedData['sn'] = $sn;
                $nestedData['first_name'] = $driver->first_name;
                $nestedData['last_name'] = $driver->last_name;
                $nestedData['middle_name'] = $driver->middle_name;
                $nestedData['mobile_1'] = '<a href="tel:'.$driver->mobile_1.'" target="_blank" style="color: #21068e">'.$driver->mobile_1.'</a>';
                $nestedData['email'] = '<a href="mailto:'.$driver->email.'" target="_blank" style="color: #21068e">'.$driver->email.'</a>';
                $nestedData['date_created'] = date('d-m-Y', $driver->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $driver->date_modified);

                $nestedData['DT_RowId'] = 'driver_id_'.$driver->driver_id;
                $nestedData['onclick'] = "populate_links('".$driver->driver_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $driver_array[] = $driver->driver_id;

							$sn++;               
            }

				    /*$drivers = $sn > 1 ? 'drivers' : 'driver';
				    $bt_row['bottom_row'] = '
										            <div class="col-md-6 col-sm-6 text-lg-left"><strong>'.number_format($sn, 0, '.', ',') . ' '.$drivers.'</strong></div>';*/

        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "driver_ids"     => $driver_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }


    //get driver by vehicle_id
    function getDriverByVehicleId($vehicle_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        $driver_id = $this->db->get('v_vehicle')->row()->driver_id;

        $this->db->where('driver_id', $driver_id);
        $driver = $this->db->get('v_driver')->row();

        return $driver;

        
    }

    //get driver by driver_id
    function getDriverById($driver_id) {
        $this->db->where('driver_id', $driver_id);
        $driver = $this->db->get('v_driver')->result();

        foreach($driver as $d) {
            return $d;
        }
    }

    //get driver name by driver_id
    function getDriverNameById($driver_id) {
        $this->db->where('driver_id', $driver_id);
        $driver = $this->db->get('v_driver')->row();

        $driver_id = $driver->driver_id;
        $driver_first_name = $driver->first_name;
        $driver_last_name = $driver->last_name;
        $driver_middle_name = $driver->middle_name;

        return $driver_first_name.' '.$driver_last_name.' '.$driver_middle_name;
    }

    //get driver name by driver_id
    function getDriverFirstNameById($driver_id) {
        $this->db->where('driver_id', $driver_id);
        $driver = $this->db->get('v_driver')->row();
        $driver_first_name = $driver->first_name;

        return $driver_first_name;
    }

    //get driver by license_id
    function getLicenseById($license_id) {
        $this->db->where('license_id', $license_id);
        $license = $this->db->get('v_license_type')->row()->license_name;

        return $license;
    }

    //returning html select form of driver details by vehicle id
    function driverSelectionByVehicleId($vehicle_id) {

        $drivers_query = $this->getDriverByVehicleId($vehicle_id);
        $data['transporter_id'] = $this->vehicle_model->getTransporterByVehicleId($vehicle_id);

        if($drivers_query != '') {
            $driver_id = $drivers_query->driver_id;
            $driver_first_name = $drivers_query->first_name;
            $driver_last_name = $drivers_query->last_name;
            $driver_middle_name = $drivers_query->middle_name;

            $data['driver'] = '<option value="'.$driver_id.'">'.$driver_first_name.' '.$driver_last_name.' '.$driver_middle_name.'</option>';

        } else {
            $data['driver'] = '<option value="">No Driver Found!</option>';
        }

        echo json_encode($data);
    }

}