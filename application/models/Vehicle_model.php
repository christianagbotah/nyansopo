<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Vehicle model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Vehicle_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}

	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}

	//Select all Vehicle types - generic
	function getAllVehicleTypes() {
		$this->db->order_by('type_name', 'asc');
		$all_vehicle_type = $this->db->get('v_vehicle_type');

		return $all_vehicle_type;
	}

	//Select Vehicle type - by id
	function getVehicleTypeById($type_id) {
		$this->db->where('type_id', $type_id);
		$vehicle_type = $this->db->get('v_vehicle_type')->row()->type_name;

		return $vehicle_type;
	}

    //Select all Vehicle transportes - generic
    function getAllTransporter() {
        $this->db->order_by('transporter_name', 'asc');
        $all_transporter = $this->db->get('v_transporter');

        return $all_transporter;
    }

    //Select Vehicle transporter - by id
    function getTransporterById($transporter_id) {
        $this->db->where('transporter_id', $transporter_id);
        $transporter = $this->db->get('v_transporter')->row()->transporter_name;

        return $transporter;
    }

	//Select all Vehicle models - generic
	function getAllVehicleModels() {
		$this->db->order_by('model_name', 'asc');
		$all_vehicle_model = $this->db->get('v_vehicle_model');

		return $all_vehicle_model;
	}

	//Select Vehicle model - by id
	function getVehicleModelById($model_id) {
		$this->db->where('model_id', $model_id);
		$vehicle_model = $this->db->get('v_vehicle_model')->row()->model_name;

		return $vehicle_model;
	}

	//Select all Vehicle - generic
	function getAllVehicles() {
		$this->db->order_by('registration_number', 'asc');
		$all_vehicle = $this->db->get('v_vehicle');

		return $all_vehicle;
	}

    //Select all available Vehicle - generic
    function getAllAvailableVehicles() {
        $this->db->where('driver_id', '0');
        $this->db->order_by('registration_number', 'asc');
        $all_available_vehicle = $this->db->get('v_vehicle');

        return $all_available_vehicle;
    }

	//Select Vehicle - by id
	function getVehicleById($vehicle_id) {
		$this->db->where('vehicle_id', $vehicle_id);
        $vehicle_query = $this->db->get('v_vehicle');

		$registration_number = $vehicle_query->row()->registration_number;
		$type_id = $vehicle_query->row()->type_id;
		$model_id = $vehicle_query->row()->model_id;

		$vehicle = $registration_number. ' '.$this->getVehicleTypeById($type_id).' '.$this->getVehicleModelById($model_id);

		return $vehicle;
	}

    //Select Vehicle - by id
    function getVehicleRegistrationNumberById($vehicle_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        $vehicle_query = $this->db->get('v_vehicle');

        $registration_number = $vehicle_query->row()->registration_number;

        return $registration_number;
    }

    //Select Vehicle - by id
    function getTransporterByVehicleId($vehicle_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        $vehicle_query = $this->db->get('v_vehicle');

        $transporter_id = $vehicle_query->row()->transporter_id;

        return $transporter_id;
    }

    //Select Vehicle - by id in array
    function getVehicleByIdArray($vehicle_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        $vehicle = $this->db->get('v_vehicle')->result();

        foreach($vehicle as $v) {
            return $v;
        }
    }

    //Select Vehicle type - by id in array
    function getVehicleTypeByIdArray($type_id) {
        $this->db->where('type_id', $type_id);
        $type = $this->db->get('v_vehicle_type')->result();

        foreach($type as $t) {
            return $t;
        }
    }

    //Select Vehicle model - by id in array
    function getVehicleModelByIdArray($model_id) {
        $this->db->where('model_id', $model_id);
        $model = $this->db->get('v_vehicle_model')->result();

        foreach($model as $m) {
            return $m;
        }
    }


	//Add Vehicle
	function add_vehicle($vehicle_data) {
		$this->db->insert('v_vehicle', $vehicle_data);
		$vehicle_id = $this->db->insert_id();

		if($this->db->affected_rows() > 0) {
			return $vehicle_id; //successful
		} else {
			return 0; //failed
		}
	}

    //Edit Vehicle
    function edit_vehicle($vehicle_data, $vehicle_id) {
        $this->db->where('vehicle_id', $vehicle_id);
        $this->db->update('v_vehicle', $vehicle_data);

        return 1;
    }

    //Delete Vehicle
    function delete_vehicle($vehicle_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;
        $num_rows_exp = $this->db->get_where('v_expenditure', array('vehicle_id' => $vehicle_id))->num_rows();

        $num_rows = $this->db->get_where('v_loading', array('vehicle_id' => $vehicle_id))->num_rows();

        if($num_rows_exp > 0) { 
            $can_delete = false;
        } else {

            if($num_rows > 0) {
                //deny deleting
                $can_delete = false;

            } else {
                //grant deleting
                $this->db->where('vehicle_id', $vehicle_id);
                $this->db->delete('v_vehicle');
            }
        }
        
        return $can_delete;
        
    }

	//Add Vehicle Model
	function add_vehicle_model($vehicle_model_data) {
		$this->db->insert('v_vehicle_model', $vehicle_model_data);
		$model_id = $this->db->insert_id();

		if($this->db->affected_rows() > 0) {
			return $model_id; //successful
		} else {
			return 0; //failed
		}
	}

    //Edit Vehicle Model
    function edit_vehicle_model($vehicle_model_data, $model_id) {
        $this->db->where('model_id', $model_id);
        $this->db->update('v_vehicle_model', $vehicle_model_data);

        return 1;
    }

    //Delete Vehicle Model
    function delete_vehicle_model($model_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;

        $num_rows = $this->db->get_where('v_vehicle', array('model_id' => $model_id))->num_rows();


        if($num_rows > 0) {
            //deny deleting
            $can_delete = false;

        } else {
            //grant deleting
            $this->db->where('model_id', $model_id);
            $this->db->delete('v_vehicle_model');
        }
        
        return $can_delete;
        
    }

	//Add Vehicle type
	function add_vehicle_type($vehicle_type_data) {
		$this->db->insert('v_vehicle_type', $vehicle_type_data);
		$type_id = $this->db->insert_id();

		if($this->db->affected_rows() > 0) {
			return $type_id; //successful
		} else {
			return 0; //failed
		}
	}

    //Edit Vehicle type
    function edit_vehicle_type($vehicle_type_data, $type_id) {
        $this->db->where('type_id', $type_id);
        $this->db->update('v_vehicle_type', $vehicle_type_data);

        return 1;
    }


    //Delete Vehicle Type
    function delete_vehicle_type($type_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;

        $num_rows_model = $this->db->get_where('v_vehicle_model', array('type_id' => $type_id))->num_rows();
        $num_rows = $this->db->get_where('v_vehicle', array('type_id' => $type_id))->num_rows();

        if($num_rows_model > 0) {
            //deny deleting
            $can_delete = false;

        } else {
            if($num_rows > 0) {
                //deny deleting
                $can_delete = false;

            } else {
                //grant deleting
                $this->db->where('type_id', $type_id);
                $this->db->delete('v_vehicle_type');
            }
        }
        
        return $can_delete;
        
    }

	//get vehicle image
  function getVehicleImageById($vehicle_id = '') {
    $data = array();
  	$data['image_url'] = base_url('uploads/vehicle_image/' . $vehicle_id . '.jpg');
    $data['file_exists'] = file_exists('uploads/vehicle_image/' . $vehicle_id . '.jpg');

  	return json_encode($data);
  }

  function getVehicleImageUrlById($vehicle_id = '') {
    $image_url = base_url('uploads/vehicle_image/' . $vehicle_id . '.jpg');

    return $image_url;
  }

  //FILTER VEHICLE LIST
  function getAllVehiclesDataTable() {

		$vehicle_array = array();
		$data = array();


        $columns = array(
            0 => 'vehicle_id',
            1 => 'sn',
            2 => 'registration_number',
            3 => 'type_name',
            4 => 'model_name',
            5 => 'date_created',
            6 => 'date_modified',
            6 => 'driver_name',
            7 => 'status',
            8 => 'transporter',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allVehicleCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $vehicle_query = $this->ajaxload->getAllVehiclesDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $vehicle_query =  $this->ajaxload->vehicleSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->vehicleSearchCount($search);
        }
        

        if(!empty($vehicle_query)) {
        		$sn = 1;
            foreach ($vehicle_query as $vehicle) {

                if($vehicle->status == 'Active') {
                    $status_button = 'success';
                } else if($vehicle->status == 'Under Maintenance') {
                    $status_button = 'danger';
                }
                

                $nestedData['sn'] = $sn;
                $nestedData['registration_number'] = $vehicle->registration_number;
                $nestedData['type_name'] = $this->db->get_where('v_vehicle_type', array('type_id' => $vehicle->type_id))->row()->type_name;
                $nestedData['model_name'] = $this->db->get_where('v_vehicle_model', array('model_id' => $vehicle->model_id))->row()->model_name;
                $nestedData['transporter'] = $this->vehicle_model->getTransporterById($vehicle->transporter_id);
                $nestedData['driver_name'] = $this->driver_model->getDriverNameById($vehicle->driver_id);
                $nestedData['date_modified'] = date('d-m-Y', $vehicle->date_modified);
                $nestedData['status'] = '<button class="btn btn-'.$status_button.'">'.$vehicle->status.'</button>';

                $nestedData['DT_RowId'] = 'vehicle_id_'.$vehicle->vehicle_id;
                $nestedData['onclick'] = "populate_links('".$vehicle->vehicle_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $vehicle_array[] = $vehicle->vehicle_id;

							$sn++;               
            }

				   /* $vehicles = $sn > 1 ? 'Vehicles' : 'Vehicle';
				    $bt_row['bottom_row'] = '
										            <div class="col-md-6 col-sm-6 text-lg-left"><strong>'.number_format($sn, 0, '.', ',') . ' '.$vehicles.'</strong></div>';*/

        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "vehicle_ids"     => $vehicle_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }


 	//FILTER VEHICLE TYPE LIST
  function getAllVehicleTypesDataTable() {

		$type_array = array();
		$data = array();


        $columns = array(
            0 => 'type_id',
            1 => 'sn',
            2 => 'vehicle_type',
            3 => 'date_created',
            4 => 'date_modified',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allVehicleTypeCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $type_query = $this->ajaxload->getAllVehicleTypesDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $type_query =  $this->ajaxload->vehicleTypeSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->vehicleTypeSearchCount($search);
        }
        

        if(!empty($type_query)) {
        		$sn = 1;
            foreach ($type_query as $vtype) {

                $nestedData['sn'] = $sn;
                $nestedData['vehicle_type'] = $vtype->type_name;
                $nestedData['date_created'] = date('d-m-Y', $vtype->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $vtype->date_modified);

                $nestedData['DT_RowId'] = 'type_id_'.$vtype->type_id;
                $nestedData['onclick'] = "populate_links('".$vtype->type_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $type_array[] = $vtype->type_id;

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
            "type_ids"   => $type_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }


  //FILTER VEHICLE MODLE LIST
  function getAllVehicleModelsDataTable() {

		$vehicle_model_array = array();
		$data = array();


        $columns = array(
            0 => 'model_id',
            1 => 'sn',
            2 => 'vehicle_model',
            3 => 'vehicle_type',
            4 => 'date_created',
            5 => 'date_modified',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allVehicleModelCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $model_query = $this->ajaxload->getAllVehicleModelsDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $model_query =  $this->ajaxload->vehicleModelSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->vehicleModelSearchCount($search);
        }
        

        if(!empty($model_query)) {
        		$sn = 1;
            foreach ($model_query as $vmodel) {

                $nestedData['sn'] = $sn;
                $nestedData['vehicle_model'] = $vmodel->model_name;
                $nestedData['vehicle_type'] = $this->db->get_where('v_vehicle_type', array('type_id' => $vmodel->type_id))->row()->type_name;
                $nestedData['date_created'] = date('d-m-Y', $vmodel->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $vmodel->date_modified);

                $nestedData['DT_RowId'] = 'model_id_'.$vmodel->model_id;
                $nestedData['onclick'] = "populate_links('".$vmodel->model_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $model_array[] = $vmodel->model_id;

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
            "model_ids"   => $model_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }


}