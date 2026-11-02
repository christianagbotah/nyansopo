<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// *************************************************************************
// *                                                                       *
// * Lisoft School Manager                                                 *
// * Copyright (c) Lightworld Technologies Limited. All Rights Reserved    *
// *                                                                       *
// *************************************************************************
// * @author : Lightworldtech                                              *
// * date        : August 11, 2019                                         *
// * description : For managing different levels of schools                *
// * Email   : softmail@lisoft.com                                         *
// * Website : https://www.lisoft.com                                      *
// * Support : https://www.support.lisoft.com                              *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * This software is furnished under a license and may be used and copied *
// * only  in  accordance  with  the  terms  of such  license and with the *
// * inclusion of the above copyright notice.                              *
// *                                                                       *
// *************************************************************************
class Loading extends CI_Controller {

	protected $theme;

	// constructor
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->library('session');

		$this->load->model(array('Ajaxdataload_model' => 'ajaxload'));

		/*cache control*/
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');

		//email control
		$this->load->helper('email');
		// $this->load->config('email');
		$this->load->library('email');

		//load form validation library and helper
		$this->load->library('form_validation');

		//load encryption library
		$this->load->library('encryption');

		//load user agent class
		$this->load->library('user_agent');

	}

	//add Loading
	function add_loading() {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		//declare some arrays to handle the data
		$errors = array();
		$ajax_data = array();

		//get the inputs from the form
		$loading_data['date_created'] = strtotime(date('d-m-Y'));
		$loading_data['date_modified'] = strtotime(date('d-m-Y'));
		$loading_data['loading_date'] = strtotime($this->input->post('loading_date'));
		$loading_data['supplier_id'] = $this->input->post('supplier');
		$loading_data['item_id'] = $this->input->post('item');
		$loading_data['customer_id'] = $this->input->post('customer');
		$loading_data['location_id'] = $this->input->post('location');
		$loading_data['destination_id'] = $this->input->post('destination');
		$loading_data['vehicle_id'] = $this->input->post('vehicle_add');
		$loading_data['driver_id'] = $this->input->post('driver_add');
		$loading_data['waybill_number'] = strtoupper($this->input->post('waybill_number'));
		$loading_data['quantity'] = $this->input->post('quantity');
		$loading_data['supplier_rate'] = $this->input->post('supplier_rate_hidden');
		$loading_data['transporter_rate'] = $this->input->post('transporter_rate_hidden');
		$loading_data['total_amount'] = $this->input->post('total_amount_hidden');
		$loading_data['transport_difference'] = $this->input->post('transport_difference');
		$loading_data['delivery_type'] = $this->input->post('delivery_type');
		$loading_data['fuel_usage'] = $this->input->post('fuel_usage');
		$loading_data['created_user'] = $this->session->userdata('login_user_id');
		$loading_data['edited_user'] = $this->session->userdata('login_user_id');

		//get the delivery type id
		$loading_data['delivery_type_id'] = $this->db->get_where('items', array('item_id' => $loading_data['item_id']))->row()->delivery_type;

		//Validate the form inputs
		if (empty($loading_data['loading_date'])) {
			$errors['loading_date'] = 'Loading Date Required!';
		}

		if (empty($loading_data['supplier_id'])) {
			$errors['supplier_id'] = 'Select A supplier!';
		}

		if (empty($loading_data['item_id'])) {
			$errors['item_id'] = 'Select Item!';
		}

		/*if (empty($loading_data['customer_id'])) {
			$errors['customer_id'] = 'Select A customer!';
		}*/

		if (empty($loading_data['location_id'])) {
			$errors['location_id'] = 'Make sure location is selected!';
		}

		if (empty($loading_data['destination_id'])) {
			$errors['destination_id'] = 'Select Load Destination!';
		}

		if (empty($loading_data['vehicle_id'])) {
			$error['vehicle_id'] = 'Vehicle Required!';
		}

		if (empty($loading_data['driver_id'])) {
			$errors['driver_id'] = 'Check if a driver is assigned to the selected vehicle!';
		}

		if (empty($loading_data['waybill_number'])) {
			$errors['waybill_number'] = 'Waybill Number Required!';
		}

		if (empty($loading_data['quantity'])) {
			$errors['quantity'] = 'Quantity Required!';
		}

		if ($loading_data['quantity'] < 0) {
			$errors['quantity_not_negative'] = 'Quantity Must Be A Positive Integer!';
		}

		if (!is_numeric($loading_data['quantity'])) {
			$errors['quantity_numeric'] = 'Quantity Must Be A Number!';
		}

		if (!empty($loading_data['transport_difference'])) {
			if ($loading_data['transport_difference'] < 0) {
				$errors['transport_difference'] = 'Transport Difference Must Be A Positive Integer!';
			}

			if (!is_numeric($loading_data['transport_difference'])) {
				$errors['transport_difference_n'] = 'Transport Difference Must Be A Number!';
			}
		}

		if (empty($loading_data['supplier_rate'])) {
			$errors['supplier_rate'] = 'Rate Required!';
		}

		if ($loading_data['delivery_type'] == '') {
			$errors['delivery_type'] = 'Please select the type of delivery: Either "Single" or "Multiple"';
		}

		if ($loading_data['fuel_usage'] == '') {
			$errors['fuel_usage_em'] = 'Fuel Usage Required!';
		}

		if ($loading_data['fuel_usage'] < 0) {
			$errors['fuel_usage_n'] = 'Fuel Usage Amount Must Be A Positive Integer!';
		}

		if (!is_numeric($loading_data['fuel_usage'])) {
			$errors['fuel_usage_numeric'] = 'Fuel usage Amount Must Be A Number!';
		}

		//Duplicate Validation
		$dup_result = loading_add_duplicate($loading_data['waybill_number'], $loading_data['delivery_type']);

		if ($dup_result == 'not-allowed') {
			//duplicate found so reject the submission
			$errors['multiple_delivery_type_error'] = 'This Waybill Number already exists with a multiple delivery, but your current or previous delivery is set to "Single" instead of "Multiple". Kindly correct it and try again!';

		} else if ($dup_result == 0) {
			//duplicate found so reject the submission
			$errors['loading_duplicate'] = 'Duplicate found: Loading with the same Waybill Number already exists!';
		}

		//let's echo the errors now if any, else, we go ahead with our data processing
		if (!empty($errors)) {
			//we have some errors
			echo json_encode($errors); //send it back to the page

		} else {
			//bravo! No errors found... go ahead--->
			//insert data
			$result = $this->loading_model->add_loading($loading_data);

			if ($result != 0) {

				//prepare feedback - success
				$ajax_data['success'] = true;
				$ajax_data['message'] = 'Loading with Waybil Number ' . $loading_data['waybill_number'] . ' added successfully!';
			} else {
				//prepare feedback - fail
				$ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
			}

			echo json_encode($ajax_data);
		}
	}

	//edit Loading
	function edit_loading($loading_id) {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		//declare some arrays to handle the data
		$errors = array();
		$ajax_data = array();

		//get the inputs from the form
		$loading_data['date_modified'] = strtotime(date('d-m-Y'));
		$loading_data['loading_date'] = strtotime($this->input->post('loading_date'));
		$loading_data['supplier_id'] = $this->input->post('supplier');
		$loading_data['item_id'] = $this->input->post('item');
		$loading_data['customer_id'] = $this->input->post('customer');
		$loading_data['location_id'] = $this->input->post('location');
		$loading_data['destination_id'] = $this->input->post('destination');
		$loading_data['vehicle_id'] = $this->input->post('vehicle_edit');
		$loading_data['driver_id'] = $this->input->post('driver_edit');
		$loading_data['waybill_number'] = strtoupper($this->input->post('waybill_number'));
		$loading_data['quantity'] = $this->input->post('quantity');
		$loading_data['supplier_rate'] = $this->input->post('supplier_rate_hidden');
		$loading_data['transporter_rate'] = $this->input->post('transporter_rate_hidden');
		$loading_data['total_amount'] = $this->input->post('total_amount_hidden');
		$loading_data['transport_difference'] = $this->input->post('transport_difference');
		$loading_data['delivery_type'] = $this->input->post('delivery_type');
		$loading_data['fuel_usage'] = $this->input->post('fuel_usage');
		$loading_data['edited_user'] = $this->session->userdata('login_user_id');

		//get the delivery type id
		$loading_data['delivery_type_id'] = $this->db->get_where('items', array('item_id' => $loading_data['item_id']))->row()->delivery_type;

		//Validate the form inputs
		if (empty($loading_data['loading_date'])) {
			$errors['loading_date'] = 'Loading Date Required!';
		}

		if (empty($loading_data['supplier_id'])) {
			$errors['supplier_id'] = 'Select A supplier!';
		}

		if (empty($loading_data['item_id'])) {
			$errors['item_id'] = 'Select Item!';
		}

		/*if (empty($loading_data['customer_id'])) {
			$errors['customer_id'] = 'Select A customer!';
		}*/

		if (empty($loading_data['location_id'])) {
			$errors['location_id'] = 'Make sure location is selected!';
		}

		if (empty($loading_data['destination_id'])) {
			$errors['destination_id'] = 'Select Load Destination!';
		}

		if (empty($loading_data['vehicle_id'])) {
			$error['vehicle_id'] = 'Vehicle Required!';
		}

		if (empty($loading_data['driver_id'])) {
			$errors['driver_id'] = 'Check if a driver is assigned to the selected vehicle!';
		}

		if (empty($loading_data['waybill_number'])) {
			$errors['waybill_number'] = 'Waybill Number Required!';
		}

		if (empty($loading_data['quantity'])) {
			$errors['quantity'] = 'Quantity Required!';
		}

		if ($loading_data['quantity'] < 0) {
			$errors['quantity_not_negative'] = 'Quantity Must Be A Positive Integer!';
		}

		if (!is_numeric($loading_data['quantity'])) {
			$errors['quantity_numeric'] = 'Quantity Must Be A Number!';
		}

		if (!empty($loading_data['transport_difference'])) {
			if ($loading_data['transport_difference'] < 0) {
				$errors['transport_difference'] = 'Transport Difference Must Be A Positive Integer!';
			}

			if (!is_numeric($loading_data['transport_difference'])) {
				$errors['transport_difference_n'] = 'Transport Difference Must Be A Number!';
			}
		}

		if (empty($loading_data['supplier_rate'])) {
			$errors['supplier_rate'] = 'Rate Required!';
		}

		if ($loading_data['delivery_type'] == '') {
			$errors['delivery_type'] = 'Please select the type of delivery: Either "Single" or "Multiple"';
		}

		if ($loading_data['fuel_usage'] == '') {
			$errors['fuel_usage_em'] = 'Fuel Usage Required!';
		}

		if ($loading_data['fuel_usage'] < 0) {
			$errors['fuel_usage_n'] = 'Fuel Usage Amount Must Be A Positive Integer!';
		}

		if (!is_numeric($loading_data['fuel_usage'])) {
			$errors['fuel_usage_numeric'] = 'Fuel usage Amount Must Be A Number!';
		}

		//Duplicate Validation
		$dup_result = loading_edit_duplicate($loading_data['waybill_number'], $loading_data['delivery_type']);

		if ($dup_result == 'not-allowed') {
			//duplicate found so reject the submission
			$errors['multiple_delivery_type_error'] = 'This Waybill Number already exists with a multiple delivery, but your current or previous delivery is set to "Single" instead of "Multiple". Kindly correct it and try again!';

		} else if (!$dup_result) {
			//duplicate found so reject the submission
			$errors['loading_duplicate'] = 'Duplicate found: Loading with the same Waybill Number already exists!';

		}

		//let's echo the errors now if any, else, we go ahead with our data processing
		if (!empty($errors)) {
			//we have some errors
			echo json_encode($errors); //send it back to the page

		} else {
			//bravo! No errors found... go ahead--->
			//insert data
			$result = $this->loading_model->edit_loading($loading_data, $loading_id);

			if ($result != 0) {

				//prepare feedback - success
				$ajax_data['success'] = true;
				$ajax_data['message'] = 'Loading with Waybil Number ' . $loading_data['waybill_number'] . ' updated successfully!';
			} else {
				//prepare feedback - fail
				$ajax_data['message'] = 'We did not detect any change in the form submitted. If you changed the image, we have processed it, so close the form if you do not want to edit further!';
			}

			echo json_encode($ajax_data);
		}
	}

	//delete Loading
	function delete_loading($loading_id) {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		$this->loading_model->delete_loading($loading_id);

		$data['success'] = true;
		$data['user'] = 'Loading';

		echo json_encode($data);

	}

	//add destination
	function add_destination($suppliers_id, $transporters_id) {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		//declare some arrays to handle the data
		$errors = array();
		$ajax_data = array();

		$suppliers_id = explode('-', $suppliers_id);
		$transporters_id = explode('-', $transporters_id);

		//get the inputs from the form
		$destination_data['date_created'] = strtotime(date('d-m-Y'));
		$destination_data['date_modified'] = strtotime(date('d-m-Y'));
		$destination_data['destination_name'] = trim(strtoupper($this->input->post('destination_name')));
		//$destination_data['fuel_usage'] = $this->input->post('fuel_usage');

		//Validate the form inputs
		if (empty($destination_data['destination_name'])) {
			$errors['destination_name'] = 'Destination Required!';
		}

		/*if (empty($destination_data['fuel_usage'])) {
			$errors['fuel_usage'] = 'Estimated Fuel Usage Required!';
		}

		if (!is_numeric($destination_data['fuel_usage'])) {
			$errors['fuel_usage_nu'] = 'Estimated Fuel Usage Must Be A Number!';
		}
*/
		//Duplicate Validation
		if (!destination_duplicate($destination_data['destination_name'], 'add')) {
			//duplicate found so reject the submission
			$errors['destination_name_duplicate'] = 'Duplicate found: The same destination already exists!';
		}

		if (!empty($errors)) {
			//we have some errors
			echo json_encode($errors); //send it back to the page
			return false;

		} else {
			$destination_id = $this->loading_model->add_destination($destination_data, 'destination');
		}

		//main work starts here
		$error_counter = 0;
		$rate_ids = array();
		for ($su = 0; $su < sizeof($suppliers_id); $su++) {

			//getting the rates for each item of this this supplier
			$items = $this->loading_model->getAllItemsBySupplierId($suppliers_id[$su])->result_array();
			foreach ($items as $it) {
				$rate_data['destination_id'] = $destination_id;
				$rate_data['supplier_id'] = $suppliers_id[$su];
				$rate_data['item_id'] = $it['item_id'];
				$rate_data['supplier_rate'] = $this->input->post('destination_' . $rate_data['supplier_id'] . '_' . $rate_data['item_id']);
				//for transporters
				$t_counter = 1;
				for ($tr = 0; $tr < sizeof($transporters_id); $tr++) {
					$t_column = 'transporter_' . $t_counter;
					$rate_data[$t_column] = $transporters_id[$tr] . '_' . $this->input->post('transporter_' . $transporters_id[$tr] . '_' . $rate_data['item_id']);

					//validate inputs
					if (!is_numeric($this->input->post('transporter_' . $transporters_id[$tr] . '_' . $rate_data['item_id']))) {
						$error_counter++;
					}

					$t_counter++;
				}

				//validate inputs
				if (!is_numeric($rate_data['supplier_rate'])) {
					$error_counter++;
				}

				//insert now
				$rate_id = $this->loading_model->add_destination($rate_data, 'rate');
				array_push($rate_ids, $rate_id);
			}
		}

		//show error if any
		if ($error_counter > 0) {
			//there was some errors
			//delete all entered records
			$this->db->where('destination_id', $destination_id);
			$this->db->delete('v_destination');

			$this->db->where_in('delivery_id', $rate_ids);
			$this->db->delete('v_delivery_rates');

			$errors['numeric_errors'] = 'Only numeric values are allowed for supplier and transporters rates!';

			echo json_encode($errors);

		} else {
			//bravo! No errors found... go ahead--->

			//prepare feedback - success
			$ajax_data['success'] = true;
			$ajax_data['message'] = 'Destination:  ' . $destination_data['destination_name'] . ' added successfully!';

			echo json_encode($ajax_data);
		}

	}

	//edit destination
	function edit_destination($suppliers_id, $transporters_id, $destination_id) {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		//declare some arrays to handle the data
		$errors = array();
		$ajax_data = array();

		$suppliers_id = explode('-', $suppliers_id);
		$transporters_id = explode('-', $transporters_id);

		$new_items_ids = array();
		$new_transporters_ids = array();

		//get the inputs from the form
		//$destination_data['date_created'] = strtotime(date('d-m-Y'));
		$destination_data['date_modified'] = strtotime(date('d-m-Y'));
		$destination_data['destination_name'] = trim(strtoupper($this->input->post('destination_name')));
		//$destination_data['fuel_usage'] = $this->input->post('fuel_usage');

		//Validate the form inputs
		if (empty($destination_data['destination_name'])) {
			$errors['destination_name'] = 'Destination Required!';
		}

	/*	if (empty($destination_data['fuel_usage'])) {
			$errors['fuel_usage'] = 'Estimated Fuel Usage Required!';
		}

		if (!is_numeric($destination_data['fuel_usage'])) {
			$errors['fuel_usage_nu'] = 'Estimated Fuel Usage Must Be A Number!';
		}
*/
		//Duplicate Validation
		if (!destination_duplicate($destination_data['destination_name'], 'update')) {
			//duplicate found so reject the submission
			$errors['destination_name_duplicate'] = 'Duplicate found: The same destination already exists!';
		}

		if (!empty($errors)) {
			//we have some errors
			echo json_encode($errors); //send it back to the page
			return false;

		} else {
			$this->loading_model->edit_destination($destination_data, $destination_id, $supplier_id = '', $item_id = '', 'destination');
		}

		//main work starts here
		$error_counter = 0;
		$rate_ids = array();

		$new_error_counter = 0;
		$new_rate_ids = array();
		for ($su = 0; $su < sizeof($suppliers_id); $su++) {

			//getting the rates for each item of this this supplier
			$items = $this->loading_model->getAllItemsBySupplierId($suppliers_id[$su])->result_array();
			foreach ($items as $it) {
				$rate_data['destination_id'] = $destination_id;
				$rate_data['supplier_id'] = $suppliers_id[$su];
				$rate_data['item_id'] = $it['item_id'];
				$rate_data['supplier_rate'] = $this->input->post('destination_' . $rate_data['supplier_id'] . '_' . $rate_data['item_id']);

				//are there newly added items?
				$item_rows = $this->db->get_where('v_delivery_rates', array('destination_id' => $destination_id, 'supplier_id' => $suppliers_id[$su], 'item_id' => $it['item_id']))->num_rows();

				if ($item_rows < 1) {
					//this item is new, insert it
					//for transporters
					$new_t_counter = 1;
					for ($tr = 0; $tr < sizeof($transporters_id); $tr++) {
						$t_column = 'transporter_' . $new_t_counter;
						$rate_data[$t_column] = $transporters_id[$tr] . '_' . $this->input->post('transporter_' . $transporters_id[$tr] . '_' . $rate_data['item_id']);

						$new_t_counter++;
					}

					//insert now
					$rate_id = $this->loading_model->add_destination($rate_data, 'rate');

				} else {
					//this item exists, just edit it
					//for transporters
					$t_counter = 1;
					for ($tr = 0; $tr < sizeof($transporters_id); $tr++) {
						$t_column = 'transporter_' . $t_counter;

						$rate_data[$t_column] = $transporters_id[$tr] . '_' . $this->input->post('transporter_' . $transporters_id[$tr] . '_' . $rate_data['item_id']);

						$t_counter++;
					}

					//insert now
					$rate_id = $this->loading_model->edit_destination($rate_data, $destination_id, $suppliers_id[$su], $it['item_id'], 'rate');
				}

			}
		}

		//prepare feedback - success
		$ajax_data['success'] = true;
		$ajax_data['message'] = 'Destination:  ' . $destination_data['destination_name'] . ' edited successfully!';

		echo json_encode($ajax_data);

	}

	//delete Destination
	function delete_destination($destination_id) {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		$result = $this->loading_model->delete_destination($destination_id);

		if ($result == true) {
			$data['success'] = true;
			$data['user'] = 'Destination';
		} else {
			$data['success'] = false;
			$data['err_message'] = 'Destination could not be deleted because it used in loading records!';
		}

		echo json_encode($data);

	}

	//FILTER DESTINATION
	function getAllDestinationsDataTable() {

		$this->loading_model->getAllDestinationsDataTable();

	}

	//get html form of location using supplier id
	function locationSelectionBySupplierId() {
		$supplier_id = $this->input->post('supplier_id');
		echo $this->loading_model->locationSelectionBySupplierId($supplier_id);
	}

	//get supplier and transporter rate by item_id and destination_id
	function getRatesByIds() {
		$destination_id = $this->input->post('destination_id');
		$item_id = $this->input->post('item_id');
		$transporter_id = $this->input->post('transporter_id');

		echo $this->loading_model->getRatesByIds($destination_id, $item_id, $transporter_id);

	}

	//get customer using destination id
	function getCustomersForSelectedDestination() {
		$destination_id = $this->input->post('destination_id');
		echo $this->loading_model->getCustomersForSelectedDestination($destination_id);

	}

	//FILTER LOADINGS
	function getAllLoadingsDataTable($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

		$this->loading_model->getAllLoadingsDataTable($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $supplier_id, $customer_id, $item_id, $waybill_number);

	}

	//FILTER CLAIMS
	function getAllCompanyClaims($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

		$this->loading_model->getAllCompanyClaims($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $supplier_id, $customer_id, $item_id, $waybill_number);

	}

	//FILTER LOADINGS
	function getClaims($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $page = '', $item_id = '', $waybill_number = '') {

		$page_data['claim_date'] = strtotime($this->input->post('claim_date'));
		$page_data['claim_no'] = $this->input->post('claim_no');
		$page_data['claim_manager'] = trim(strtoupper($this->input->post('claim_manager')));
		$page_data['claim_invoice'] = trim(strtoupper($this->input->post('claim_invoice')));
		$page_data['claim_type'] = $this->input->post('claim_type');
		$page_data['from'] = $from;
		$page_data['to'] = $to;
		$page_data['vehicle_id'] = $vehicle_id;
		$page_data['driver_id'] = $driver_id;
		$page_data['destination_id'] = $destination_id;
		$page_data['location_id'] = $location_id;
		$page_data['supplier_id'] = $supplier_id;
		$page_data['customer_id'] = $customer_id;
		$page_data['item_id'] = $item_id;
		$page_data['waybill_number'] = $waybill_number;

		if ($page == 'All') {
			$page_data['page_name'] = 'printAllLoadings';
			$page_data['page_heading'] = 'LOADING DATA';
		} else {
			if ($page_data['claim_type'] == 'transporter') {
				$page_data['page_name'] = 'transporter_claim';
				$page_data['page_heading'] = 'TRANSPORTER CLAIM ' . $page_data['claim_no'] . ' FOR ' . $this->driver_model->getDriverNameById($driver_id);

			} else if ($page_data['claim_type'] == 'company') {
				$page_data['page_name'] = 'company_claim';
				$page_data['page_heading'] = 'CLAIM ' . $page_data['claim_no'];

			}
		}

		$this->load->view('backend/admin/pages/vehicle/' . $page_data['page_name'] . '.php', $page_data);

	}

	//loading chart
	function drawChart($direction = '', $date = '') {
		echo $this->loading_model->drawChart($direction, $date);

	}

	//getAllTrucksSalesReport
	function getAllTrucksSalesReport($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

		$page_data['from'] = $from;
		$page_data['to'] = $to;
		$page_data['vehicle_id'] = $vehicle_id;
		$page_data['driver_id'] = $driver_id;
		$page_data['destination_id'] = $destination_id;
		$page_data['location_id'] = $location_id;
		$page_data['supplier_id'] = $supplier_id;
		$page_data['customer_id'] = $customer_id;
		$page_data['item_id'] = $item_id;
		$page_data['waybill_number'] = $waybill_number;

		$this->load->view('backend/admin/pages/vehicle/getAllTrucksSalesReport.php', $page_data);
	}

	//getIndividualTruckSalesReport
	/*function getIndividualTruckSalesReport($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '') {

	        $page_data['from'] = $from;
	        $page_data['to'] = $to;
	        $page_data['vehicle_id'] = $vehicle_id;
	        $page_data['driver_id'] = $driver_id;
	        $page_data['destination_id'] = $destination_id;
	        $page_data['location_id'] = $location_id;
	        $page_data['supplier_id'] = $supplier_id;
	        $page_data['customer_id'] = $customer_id;
	        $page_data['item_id'] = $item_id;

	        $this->load->view('backend/admin/pages/vehicle/getIndividualTruckSalesReport.php', $page_data);
*/

	//get fuel usage assessment eport
	function fuelUsageAssessmentReport($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

		$page_data['from'] = $from;
		$page_data['to'] = $to;
		$page_data['vehicle_id'] = $vehicle_id;
		$page_data['driver_id'] = $driver_id;
		$page_data['destination_id'] = $destination_id;
		$page_data['location_id'] = $location_id;
		$page_data['supplier_id'] = $supplier_id;
		$page_data['customer_id'] = $customer_id;
		$page_data['item_id'] = $item_id;
		$page_data['waybill_number'] = $waybill_number;

		$this->load->view('backend/admin/pages/vehicle/fuelUsageAssessmentReport.php', $page_data);
	}

	//load trucks loading
	function getTrucksLoading() {
		$this->load->view('backend/admin/pages/vehicle/getTrucksLoading.php');
	}

	//save fuel usage assessment report
	function saveFuelUsageAssessmentReport() {
		if ($this->session->userdata('admin_login') != 1) {
			redirect(site_url('login'), 'refresh');
		}

		//declare some arrays to handle the data
		$ajax_data = array();
		$vehicle_ids = $this->input->post('vehicle_ids');//vehicle ids

		for($i = 0; $i < sizeof($vehicle_ids); $i++) {
			//get the inputs from the form
			$loading_data['from_date'] = strtotime($this->input->post($vehicle_ids[$i].'_from'));
			$loading_data['to_date'] = strtotime($this->input->post($vehicle_ids[$i].'_to'));
			$loading_data['saved_date'] = strtotime(date('d-m-Y'));
			$loading_data['supplier_id'] = $this->input->post($vehicle_ids[$i].'_supplier_id');
			$item_id = $this->input->post($vehicle_ids[$i].'_item_id');
			$loading_data['customer_id'] = $this->input->post($vehicle_ids[$i].'_customer_id');
			$loading_data['location_id'] = $this->input->post($vehicle_ids[$i].'_location_id');
			$loading_data['destination_id'] = $this->input->post($vehicle_ids[$i].'_destination_id');
			$loading_data['vehicle_id'] = $vehicle_ids[$i];
			$loading_data['driver_id'] = $this->input->post($vehicle_ids[$i].'_driver_id');
			$loading_data['actual_usage'] = $this->input->post($vehicle_ids[$i].'_total_actual_usage');

			$item_id = explode('-', $item_id);
			for($t = 0; $t < count($item_id); $t++) {
				$loading_data['item_id'] = $item_id[$t];

				$result = $this->loading_model->add_fuel_usage($loading_data, $vehicle_ids[$i]);
			}
  
		}

		if ($result == 'added') {
		 	$ajax_data['message'] = 'Report Added Successfully.';
		} else if ($result == 'updated') {
			$ajax_data['message'] = 'Report Updated Successfully.';
		}

		echo json_encode($ajax_data);

	}

	//load fuel actual usage data
	function fetchActualUsage($supplier_id = '', $customer_id = '', $item_id = '', $destination_id = '', $location_id = '', $from = '', $to = '', $driver_id = '', $v_id = '') {

		$item_id = explode('-', $item_id);

		$this->db->where('from_date', strtotime($from));
    $this->db->where('to_date', strtotime($to));
    $this->db->where('supplier_id', $supplier_id);
    $this->db->where('customer_id', $customer_id);
    $this->db->where('location_id', $location_id);
    $this->db->where('destination_id', $destination_id);
    $this->db->where_in('item_id', $item_id);
    $this->db->where('driver_id', $driver_id);
    $this->db->where('vehicle_id', $v_id);

    $query = $this->db->get(v_fuel_usage_report);

    $data['actual_usage'] = $query->row()->actual_usage;

    echo json_encode($data);
	}

	//print sales report
	function printSalesReport($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

		$page_data['from'] = $from;
		$page_data['to'] = $to;
		$page_data['vehicle_id'] = $vehicle_id;
		$page_data['driver_id'] = $driver_id;
		$page_data['destination_id'] = $destination_id;
		$page_data['location_id'] = $location_id;
		$page_data['supplier_id'] = $supplier_id;
		$page_data['customer_id'] = $customer_id;
		$page_data['item_id'] = $item_id;
		$page_data['waybill_number'] = $waybill_number;


		$page_data['page_name'] = 'printSalesReport';
		$page_data['page_heading'] = 'SALES REPORT';
		

		$this->load->view('backend/admin/pages/vehicle/' . $page_data['page_name'] . '.php', $page_data);

	}

	//print fuel usage report
	function printFuelUsageReport($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

		$page_data['from'] = $from;
		$page_data['to'] = $to;
		$page_data['vehicle_id'] = $vehicle_id;
		$page_data['driver_id'] = $driver_id;
		$page_data['destination_id'] = $destination_id;
		$page_data['location_id'] = $location_id;
		$page_data['supplier_id'] = $supplier_id;
		$page_data['customer_id'] = $customer_id;
		$page_data['item_id'] = $item_id;
		$page_data['waybill_number'] = $waybill_number;


		$page_data['page_name'] = 'printFuelUsageReport';
		$page_data['page_heading'] = 'FUEL USAGE ASSESSEMENT REPORT';
		

		$this->load->view('backend/admin/pages/vehicle/' . $page_data['page_name'] . '.php', $page_data);

	}


	//has fuel usage report saved before user wants to print it?
   function hasBeenSaved($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '') {
   		echo $this->loading_model->hasBeenSaved($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $supplier_id, $customer_id, $item_id);
	}

}
