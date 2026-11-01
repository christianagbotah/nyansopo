<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Department model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Department_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}

	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}

	//Select all Departments - generic
	function getAllDepartments() {
		$all_departments = $this->db->get('v_department');

		return $all_departments;
	}

    //Select department - by id
    function getDepartmentById($department_id) {
        $this->db->where('department_id', $department_id);
        $department = $this->db->get('v_department')->row()->department_name;

        return $department;
    }

    //Select Department - by id in array
    function getDepartmentByIdArray($department_id) {
        $this->db->where('department_id', $department_id);
        $department = $this->db->get('v_department')->result();

        foreach($department as $d) {
            return $d;
        }
    }

	//Add Department
	function add_department($department_data) {
		$this->db->insert('v_department', $department_data);
		$department_id = $this->db->insert_id();

		if($this->db->affected_rows() > 0) {
			return $department_id; //successful
		} else {
			return 0; //failed
		}
	}

    //Edit Department
    function edit_department($department_data, $department_id) {
        $this->db->where('department_id', $department_id);
        $this->db->update('v_department', $department_data);

        return 1;
    }

    //Delete Department
    function delete_department($department_id) {
        $this->clear_cache();
        //check if it is in the loading table
        $can_delete = true;
        $num_rows_driver = $this->db->get_where('v_driver', array('department_id' => $department_id))->num_rows();

        $num_rows = $this->db->get_where('v_vehicle', array('department_id' => $department_id))->num_rows();

        if($num_rows_driver > 0) { 
            $can_delete = false;
        } else {

            if($num_rows > 0) {
                //deny deleting
                $can_delete = false;

            } else {
                //grant deleting
                $this->db->where('department_id', $department_id);
                $this->db->delete('v_department');
            }
        }
        
        return $can_delete;
        
    }

	//FILTER DEPARTMENT LIST
  function getAllDepartmentsDataTable() {

		$department_array = array();
		$data = array();


        $columns = array(
            0 => 'department_id',
            1 => 'sn',
            2 => 'department_name',
            3 => 'date_created',
            4 => 'date_modified',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allDepartmentsCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $department_query = $this->ajaxload->getAllDepartmentsDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $department_query =  $this->ajaxload->departmentSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->departmentSearchCount($search);
        }
        

        if(!empty($department_query)) {
        		$sn = 1;
            foreach ($department_query as $department) {

                $nestedData['sn'] = $sn;
                $nestedData['department_name'] = $department->department_name;
                $nestedData['date_created'] = date('d-m-Y', $department->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $department->date_modified);

                $nestedData['DT_RowId'] = 'department_id_'.$department->department_id;
                $nestedData['onclick'] = "populate_links('".$department->department_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $department_array[] = $department->department_id;

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
            "department_ids"   => $department_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }

}