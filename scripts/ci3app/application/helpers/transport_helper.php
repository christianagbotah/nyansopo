<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

//add & edit vehicle duplicate validation
if (!function_exists('vehicle_duplicate')) {
	function vehicle_duplicate($registration_number = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('registration_number', $registration_number);
		$num_rows = $ci->db->get('v_vehicle')->num_rows();

		if($mode == 'add') { //do this when adding vehicle
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}

	}
}

//add loading duplicate validation
if (!function_exists('loading_add_duplicate')) {
	function loading_add_duplicate($waybill_number = '', $delv_type = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('waybill_number', $waybill_number);
		$num_query = $ci->db->get('v_loading');
		$num_rows = $num_query->num_rows();

			if ($num_rows == 0) {
				return 1;
			} else if ($num_rows > 0) {
				//check and see if the one existing is a multiple or single type of delivery
				$delivery_type = $num_query->row()->delivery_type;
				if($delivery_type == 1) {//check if the coming delivery or loading is set to multiple
					//this is multiple
					if($delv_type == $delivery_type) {
						return 1;
					} else {
						return 'not-allowed';
					}

				} else {
					//this is single
					return 0;
					
				}
			}
	}
}

//edit loading duplicate validation
if (!function_exists('loading_edit_duplicate')) {
	function loading_edit_duplicate($waybill_number = '', $delv_type = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('waybill_number', $waybill_number);
		$num_query = $ci->db->get('v_loading');
		$num_rows = $num_query->num_rows();

			if ($num_rows <= 1) {
				return 1;
			} else if ($num_rows > 1) {
				//check and see if the one existing is a multiple or single type of delivery
				$delivery_type = $num_query->row()->delivery_type;
				if($delivery_type == $delivery_type) {
					//this is multiple
					if($delv_type == 1) { //check if the coming delivery or loading is set to multiple
						return 1;
					} else {
						return 'not-allowed';
					}

				} else {
					//this is single
					return 0;
					
				}
			}
	}
}

//add & edit insurance duplicate validation
if (!function_exists('insurance_duplicate')) {
	function insurance_duplicate($insurance_name = '', $insurance_company = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('insurance_name', $insurance_name);
		$ci->db->where('insurance_company', $insurance_company);
		$num_rows = $ci->db->get('v_car_insurance')->num_rows();

		if($mode == 'add') { //do this when adding insurance
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}
	}
}

//add & edit vehicle model duplicate validation
if (!function_exists('vehicle_model_duplicate')) {
	function vehicle_model_duplicate($model_name = '', $type_id = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('model_name', $model_name);
		$ci->db->where('type_id', $type_id);
		$num_rows = $ci->db->get('v_vehicle_model')->num_rows();

		if($mode == 'add') { //do this when adding vehicle model
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}
	}
}

//add & edit vehicle type duplicate validation
if (!function_exists('vehicle_type_duplicate')) {
	function vehicle_type_duplicate($type_name = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('type_name', $type_name);
		$num_rows = $ci->db->get('v_vehicle_type')->num_rows();

		if($mode == 'add') { //do this when adding vehicle type
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}
	}
}

//add & edit department duplicate validation
if (!function_exists('department_duplicate')) {
	function department_duplicate($department_name = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('department_name', $department_name);
		$num_rows = $ci->db->get('v_department')->num_rows();

		if($mode == 'add') { //do this when adding department
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}
	}
}

//add & edit destination duplicate validation
if (!function_exists('destination_duplicate')) {
	function destination_duplicate($destination_name = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('destination_name', $destination_name);
		$num_rows = $ci->db->get('v_destination')->num_rows();

		if($mode == 'add') { //do this when adding destination
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}
	}
}

//add & edit driver duplicate validation
if (!function_exists('driver_duplicate')) {
	function driver_duplicate($mobile_1 = '', $mobile_2 = '', $email = '', $mode = '') {

		$ci = &get_instance();
		$num_rows = 0;
		
		$ci->db->where('mobile_1', $mobile_1);
		$num_rows = $ci->db->get('v_driver')->num_rows();

		if($mobile_2 != '') {
			if($num_rows == 0) {
				$ci->db->where('mobile_2', $mobile_2);
				$num_rows = $ci->db->get('v_driver')->num_rows();
			}
		}

		if($email != '') {
			$ci->db->where('email', $email);
			$num_rows = $ci->db->get('v_driver')->num_rows();
		}

		if($mode == 'add') { //do this when adding driver
			if ($num_rows == 0) {
				return true;
			} else if ($num_rows > 0) {
				return false;
			}
		} else if($mode == 'update') { //do this when editing or updating the data
			if ($num_rows <= 1) {
				return true;
			} else if ($num_rows > 1) {
				return false;
			}
		}

	}
}


//verify expiry date
if (!function_exists('expiryDateChecker')) {
  function expiryDateChecker($date, $item = '') {
    $now = strtotime(date('d-m-Y'));
    $expirydate = $date;

    $result = '';

    if($now >= $expirydate) {
        $result = '<button class="btn btn-danger">'.date('d-m-Y', $date).'. Expired</button>';
    } 

    $now2 = date('Y-m-d');
    $expirydate2 = date('Y-m-d', $date);

    $diff = date_diff(date_create($now2), date_create($expirydate2));
    $age_y = $diff->format('%y');
    $age_m = $diff->format('%m');
    $age_d = $diff->format('%d');

    $y = 'years';
    $m = 'months';
    $d = 'days';

    if($age_y < 2) $y = 'year';
    if($age_m < 2) $m = 'month';
    if($age_d < 2) $d = 'day';

    //for license
    if($item == 'license') {
    	if($age_y < 1) {

    		$duration = $age_m.$m.' '.$age_d.$d;
    		$result = '<button class="btn btn-warning">'.date('d-m-Y', $date).' ('. $duration .' more!)</button>';
        
    	} else {
    		$result = '<button class="btn btn-success">'.date('d-m-Y', $date).'</button>';
    	}

    	//for roadworthy and insurance
    } else if($item == 'roadworthy') {
    	if($age_m < 9 && $age_y < 1) {

    		$duration = $age_m.$m.' '.$age_d.$d;
    		$result = '<button class="btn btn-warning">'.date('d-m-Y', $date).' ('. $duration .' more!)</button>';
        
    	} else {
    		$result = '<button class="btn btn-success">'.date('d-m-Y', $date).'</button>';
    	}
    }
    
    return $result;
  } 
}