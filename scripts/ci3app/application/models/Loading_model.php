<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Loading_model extends CI_Model {

	function __construct() {
		parent::__construct();
	}

	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}

    //Add Loading
    function add_loading($loading_data) {
        $this->db->insert('v_loading', $loading_data);
        $loading_id = $this->db->insert_id();

        if($this->db->affected_rows() > 0) {
            return $loading_id; //successful
        } else {
            return 0; //failed
        }
    }

    //Add Fuel Usage
    function add_fuel_usage($loading_data, $vehicle_id) {
        $this->db->where('from_date', $loading_data['from_date']);
        $this->db->where('to_date', $loading_data['to_date']);
        $this->db->where('supplier_id', $loading_data['supplier_id']);
        $this->db->where('customer_id', $loading_data['customer_id']);
        $this->db->where('location_id', $loading_data['location_id']);
        $this->db->where('destination_id', $loading_data['destination_id']);
        $this->db->where_in('item_id', $loading_data['item_id']);
        $this->db->where('driver_id', $loading_data['driver_id']);
        $this->db->where('vehicle_id', $vehicle_id);

        $query = $this->db->get(v_fuel_usage_report);

        if($query->num_rows() < 1) {
            //we insert new
            $loading_data['created_user'] = $this->session->userdata('login_user_id');
            $loading_data['edited_user'] = $this->session->userdata('login_user_id');
            
            $this->db->insert('v_fuel_usage_report', $loading_data);

            return 'added';

        } else {
            //we update old
            $loading_data['edited_user'] = $this->session->userdata('login_user_id');

            $this->db->where('from_date', $loading_data['from_date']);
            $this->db->where('to_date', $loading_data['to_date']);
            $this->db->where('supplier_id', $loading_data['supplier_id']);
            $this->db->where('customer_id', $loading_data['customer_id']);
            $this->db->where('location_id', $loading_data['location_id']);
            $this->db->where('destination_id', $loading_data['destination_id']);
            $this->db->where('item_id', $loading_data['item_id']);
            $this->db->where('driver_id', $loading_data['driver_id']);
            $this->db->where('vehicle_id', $vehicle_id);

            $this->db->set('actual_usage', $loading_data['actual_usage']);
            $this->db->set('edited_user', $loading_data['edited_user']);
            $this->db->update('v_fuel_usage_report');

            return 'updated';
        }
    }

    //Edit Loading
    function edit_loading($loading_data, $loading_id) {
        $this->db->where('loading_id', $loading_id);
        $this->db->update('v_loading', $loading_data);

        
        return 1;
    }

    //has fuel usage report saved before user wants to print it?
    function hasBeenSaved($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '') {
        $this->db->where('from_date', strtotime($from));
        $this->db->where('to_date', strtotime($to));
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('customer_id', $customer_id);
        $this->db->where('location_id', $location_id);
        $this->db->where('destination_id', $destination_id);
        $this->db->where('item_id', $item_id);
        $this->db->where('driver_id', $driver_id);
        
        if($vehicle_id != 0) {
            $this->db->where('vehicle_id', $vehicle_id);
        }

        $query = $this->db->get(v_fuel_usage_report);

        if($query->num_rows() > 0) {

            return 'true';

        } else {
            return 'false';
        }
    }

    //Delete Loading
    function delete_loading($loading_id) {

        $this->clear_cache();

        $this->db->where('loading_id', $loading_id);
        $this->db->delete('v_loading');

       // return $this->db->affected_rows();

        /*if($this->db->affected_rows() > 0) {
            return 1; //successful
        } else {
            return 0; //failed
        }*/
    }

    //Add Destination
    function add_destination($data, $what = '') {
        if($what == 'destination') {
            $this->db->insert('v_destination', $data);
            $destination_id = $this->db->insert_id();

            return $destination_id;

        } else if($what == 'rate') {
            $this->db->insert('v_delivery_rates', $data);
            $rate_id = $this->db->insert_id();

            return $rate_id;
        }
        /*if($this->db->affected_rows() > 0) {
            return $destination_id; //successful
        } else {
            return 0; //failed
        }*/
    }

    //Edit destination
    function edit_destination($data, $destination_id, $supplier_id = '', $item_id = '', $what) {
        if($what == 'destination') {
            $this->db->where('destination_id', $destination_id);
            $this->db->update('v_destination', $data);

            return 1;

        } else if($what == 'rate') {
            $this->db->where('destination_id', $destination_id);
            $this->db->where('supplier_id', $supplier_id);
            $this->db->where('item_id', $item_id);
            $this->db->update('v_delivery_rates', $data);

            return 1;
        }
    }

    //Delete Destination
    function delete_destination($destination_id) {

        $this->clear_cache();
        
        //check if it is in the loading table
        $can_delete = true;
        $num_rows = $this->db->get_where('v_loading', array('destination_id' => $destination_id))->num_rows();

        if($num_rows > 0) {
            //deny deleting
            $can_delete = false;

        } else {
            //grant deleting
            $this->db->where('destination_id', $destination_id);
            $this->db->delete('v_destination');
        }

        return $can_delete;
        
    }

    

	//Select all Destination - generic
	function getAllDestinations() {
        $this->db->order_by('destination_name', 'asc');
		$all_destinations = $this->db->get('v_destination');

		return $all_destinations;
	}

    //Select all Waybill - generic
    function getAllWaybillNumbers() {
        $this->db->select('waybill_number');
        $this->db->distinct();
        $this->db->order_by('waybill_number', 'asc');
        $all_waybill_numbers = $this->db->get('v_loading');

        return $all_waybill_numbers;
    }

    //Select Destination - by id
    function getDestinationById($destination_id) {
        $this->db->where('destination_id', $destination_id);
        $destination = $this->db->get('v_destination')->row()->destination_name;

        return $destination;
    }

    //Select Destination - by id
    function getLocationById($location_id) {
        $this->db->where('location_id', $location_id);
        $location = $this->db->get('v_location')->row()->location_name;

        return $location;
    }

    //Select destination - by id in array
    function getDestinationByIdArray($destination_id) {
        $this->db->where('destination_id', $destination_id);
        $destination = $this->db->get('v_destination')->result();

        foreach($destination as $d) {
            return $d;
        }
    }

    //get supplier and transporter rate by item_id and destination_id
    function getRatesByIds($destination_id, $item_id, $transporter_id) {

        $data = array();

        $data['supplier_rate'] = $this->getSupplierRate($destination_id, $item_id);
        $data['transporter_rate'] = $this->getTransporterRate($destination_id, $item_id, $transporter_id);

        echo json_encode($data);
     
    }

    //Select delivery rates - by destination id in array
    function getDeliveryRatesByDestinationIdArray($destination_id) {
        $this->db->where('destination_id', $destination_id);
        $delivery_rates = $this->db->get('v_delivery_rates')->result();

        foreach($delivery_rates as $dr) {
            return $dr;
        }
    }


	//FILTER DESTINATION LIST
  function getAllDestinationsDataTable() {

		$destination_array = array();
		$data = array();

        //currency
        $currency = get_settings('currency');
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


        $columns = array(
            0 => 'destination_id',
            1 => 'sn',
            2 => 'destination_name',
            3 => 'supplier_rate',
            4 => 'transporter_rate',
            5 => 'date_created',
            6 => 'date_modified',
            7 => 'fuel_usage',
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allDestinationsCount();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $destination_query = $this->ajaxload->getAllDestinationsDataTable($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $destination_query =  $this->ajaxload->destinationSearch($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->destinationSearchCount($search);
        }
        

        if(!empty($destination_query)) {
        		$sn = 1;
            foreach ($destination_query as $destination) {

                $nestedData['sn'] = $sn;
                $nestedData['destination_name'] = $destination->destination_name;
                $nestedData['supplier_rate'] = numfmt_format_currency($fmt, $destination->supplier_rate, $currency);
                $nestedData['transporter_rate'] = numfmt_format_currency($fmt, $destination->transporter_rate, $currency);
                $nestedData['fuel_usage'] = numfmt_format_currency($fmt, $destination->fuel_usage, $currency);
                $nestedData['date_created'] = date('d-m-Y', $destination->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $destination->date_modified);

                $nestedData['DT_RowId'] = 'destination_id_'.$destination->destination_id;
                $nestedData['onclick'] = "populate_links('".$destination->destination_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $destination_array[] = $destination->destination_id;

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
            "destination_ids"   => $destination_array,
           // "bt_row"     		  => $bt_row
        );

        echo json_encode($json_data);
    }

    ///Select all Location - generic
    function getAllLocations() {
        $this->db->order_by('location_name', 'asc');
        $all_location = $this->db->get('v_location');

        return $all_location;
    }

    ///Select name by id - by id
    function getLocationNameById($location_id) {
        $this->db->where('location_id', $location_id);
        $location_name = $this->db->get('v_location')->row()->location_name;

        return $location_name;
    }

    //Select all Supplier - generic
    function getAllSupplier() {
        $all_suppliers = $this->db->get('suppliers');

        return $all_suppliers;
    }

    //Select all Supplier by destination id- generic
    function getAllSuppliersByDestination_id($destination_id) {

        $suppliers_ids = array();

        $this->db->select('supplier_id');
        $this->db->distinct();
        $this->db->where('destination_id', $destination_id);
        $delivery_rates_query = $this->db->get('v_delivery_rates')->result();

        foreach($delivery_rates_query as $sp) {
            array_push($suppliers_ids, $sp->supplier_id);
        }


        //query the supplier table now
        $this->db->where_in('supplier_id', $suppliers_ids);
        $all_suppliers = $this->db->get('suppliers');

        return $all_suppliers;
    }

    //Select all items by supplier id
    function getAllItemsBySupplierId($supplier_id) {
        $this->db->where('supplier_id', $supplier_id);
        $items = $this->db->get('items');

        return $items;
    }

    //Select all items by supplier id not in delivery rates table
    function getAllItemsBySupplierIdNotInDeliveryRatesTable($supplier_id) {
        $item_ids = array();

        $this->db->select('item_id');
        $this->db->distinct();
        $this->db->from('v_delivery_rates');
        $this->db->where('supplier_id', $supplier_id);
        $item_array = $this->db->get()->result_array();

        foreach($item_array as $item) {
            array_push($item_ids, $item['item_id']);
        }

        $this->db->where('supplier_id', $supplier_id);
        $this->db->where_not_in('item_id', $item_ids);
        $items = $this->db->get('items');

        return $items;
    }


    //Select  Supplier by id - by id
    function getSupplierById($supplier_id) {
        $this->db->where('supplier_id', $supplier_id);
        $supplier = $this->db->get('suppliers')->row();

        return $supplier;
    }


    //get location by supplier_id
    function getLocationBySupplierId($supplier_id) {
        $this->db->where('supplier_id', $supplier_id);
        $supplier = $this->db->get('suppliers')->row();

        return $supplier;     
    }

    //Select all customer - generic
    function getAllCustomer() {
        $all_customers = $this->db->get('customers');

        return $all_customers;
    }

    //Select  customer by id - by id
    function getCustomerById($customer_id) {
        $this->db->where('customer_id', $customer_id);
        $customer = $this->db->get('customers')->row();

        return $customer;
    }

    //Select  item by id - by id
    function getItemById($item_id) {
        $this->db->where('item_id', $item_id);
        $item = $this->db->get('items')->row();

        return $item;
    }

    //get location by customer_id
    function getLocationByCustomerId($customer_id) {
        $this->db->where('customer_id', $customer_id);
        $customer = $this->db->get('customers')->row();

        return $customer;     
    }

    //Select all Loading - generic
    function getAllloadings() {
        $all_loadings = $this->db->get('v_loading');

        return $all_loadings;
    }

    //returning html select form of location details by supplier id
    function locationSelectionBySupplierId($supplier_id) {

        $data = array();
        $item_array = [];

        $supplier_query = $this->getLocationBySupplierId($supplier_id);

        if($supplier_query != '') {
            $location_id = $supplier_query->location_id;
            $location_name = $this->getLocationNameById($location_id);

            array_push($item_array, '<option value="0">Select Item</option><option value="0">All Items</option>');

            $items_query = $this->getAllItemsBySupplierId($supplier_id);

            if($items_query->num_rows() > 0) {
                $items_array = $items_query->result();
                foreach($items_array as $item) {
                    $data['items'] = '<option value="'.$item->item_id.'">'.$item->item_name.'</option>';
                    array_push($item_array, $data['items']);
                }
            } else {
                $data['items'] = '<option value="">No Item Found!</option>';
            }

            $data['location'] = '<option value="'.$location_id.'">'.$location_name.'</option>';

        } else {
            $data['location'] = '<option value="">No Location Found!</option>';
        }


        $data['item_arr'] = $item_array;

        echo json_encode($data);
    }

    //returning customers details by destination id
    function getCustomersForSelectedDestination($destination_id) {

        $this->db->where('location_id', $destination_id);
        $customer_query = $this->db->get('customers');

            if($customer_query->num_rows() > 0) {
                foreach($customer_query->result_array() as $c) {
                    echo '<option value="'.$c['customer_id'].'">'.$c['customer_name'].'</option>';
                }
            } else {
                return '<option value="">No Customer Found!</option>';
            }

            

        
    }

    //Select loading - by id in array
    function getLoadingByIdArray($loading_id) {
        $this->db->where('loading_id', $loading_id);
        $loading = $this->db->get('v_loading')->result();

        foreach($loading as $l) {
            return $l;
        }
    }

    //FILTER LOADING LIST
  function getAllLoadingsDataTable($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

        $loading_array = array();
        $data = array();
        $total = 0;
        $driver_total = 0;
        $total_quantity = 0;
        $total_td = 0;

        $item_id = explode('-', $item_id);

        //currency
        $currency = get_settings('currency');
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


        $columns = array(
            0 => 'loading_id',
            1 => 'sn',
            2 => 'loading_date',
            3 => 'supplier',
            4 => 'location',
            5 => 'destination',
            6 => 'registration_number',
            7 => 'driver',
            8 => 'waybill_number',
            9 => 'quantity',
            10 => 'transporter_rate',
            11 => 'supplier_rate',
            12 => 'total_amount',
            13 => 'customer',
            14 => 'item',
            15 => 'td',
            
        );


        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allLoadingCount($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $loading_query = $this->ajaxload->getAllLoadingsDataTable($limit,$start,$order,$dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
        }
        else {
            $search = $this->input->post('search')['value'];
            $loading_query =  $this->ajaxload->loadingSearch($limit,$start,$search,$order,$dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
            $totalFiltered = $this->ajaxload->loadingSearchCount($search, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
        }
        

        if(!empty($loading_query)) {
                $sn = 1;
            foreach ($loading_query as $loading) {
                
                $total += $loading->total_amount; 
                $driver_total += $loading->transporter_rate * $loading->quantity;
                $total_quantity += $loading->quantity; 
                $total_td += $loading->transport_difference; 

                $nestedData['sn'] = $sn;
                $nestedData['loading_date'] = date('d-m-Y', $loading->loading_date);
                $nestedData['supplier'] = $this->getSupplierById($loading->supplier_id)->supplier_name;
                $nestedData['customer'] = $this->getCustomerById($loading->customer_id)->customer_name;
                $nestedData['location'] = $this->getLocationNameById($loading->location_id);
                $nestedData['item'] = $this->getItemById($loading->item_id)->item_name;
                $nestedData['destination'] = $this->getDestinationById($loading->destination_id);
                $nestedData['registration_number'] = $this->vehicle_model->getVehicleRegistrationNumberById($loading->vehicle_id);
                $nestedData['driver'] = $this->driver_model->getDriverNameById($loading->driver_id);
                $nestedData['waybill_number'] = $loading->waybill_number;
                $nestedData['quantity'] = number_format($loading->quantity, 0, '.', ',');
                $nestedData['td'] = number_format($loading->transport_difference, 2, '.', ',');
                
                
                $nestedData['transporter_rate'] = number_format($loading->transporter_rate, 2, '.', ',');
                $nestedData['supplier_rate'] = number_format($loading->supplier_rate, 2, '.', ',');
                $nestedData['total_amount'] = number_format($loading->total_amount, 2, '.', ',');
                $nestedData['driver_total_amount'] = number_format(($loading->transporter_rate * $loading->quantity), 2, '.', ',');
                $nestedData['date_created'] = date('d-m-Y', $loading->date_created);
                $nestedData['date_modified'] = date('d-m-Y', $loading->date_modified);

                $nestedData['DT_RowId'] = 'loading_id_'.$loading->loading_id;
                $nestedData['onclick'] = "populate_links('".$loading->loading_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $loading_array[] = $loading->loading_id;

                            $sn++;  
                                     
            }
                    $bt_row['bottom_row'] = '
                                                    <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL AMOUNT: ' .numfmt_format_currency($fmt, $total, $currency).'</strong></div>';

                    $bt_row['driver_bottom_row'] = '
                                                    <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL: ' .numfmt_format_currency($fmt, $driver_total, $currency).'</strong></div>';
                    $bt_row['td_bottom_row'] = '
                                                    <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL TRANSPORT DIFF: ' .numfmt_format_currency($fmt, $total_td, $currency).'</strong></div>';

                    $grand_quantity = number_format($total_quantity, 0, '.', ',');
                    $grand_amount = numfmt_format_currency($fmt, $total, $currency);
                    $driver_grand_amount = numfmt_format_currency($fmt, $driver_total, $currency);
                    $td_grand_amount = numfmt_format_currency($fmt, $total_td, $currency);

        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "loading_ids"     => $loading_array,
            "bt_row"          => $bt_row,
            "grand_quantity"  => $grand_quantity,
            "grand_amount"    => $grand_amount,
            "driver_grand_amount"    => $driver_grand_amount,
            "td_grand_amount"    => $td_grand_amount
        );

        echo json_encode($json_data);
    }

    //for generating company claims
    function getAllCompanyClaims($from = '', $to = '', $vehicle_id = '', $driver_id = '', $destination_id = '', $location_id = '', $supplier_id = '', $customer_id = '', $item_id = '', $waybill_number = '') {

        $loading_array = array();
        $data = array();
        $total = 0;
        $driver_total = 0;
        $total_quantity = 0;
        $total_td = 0;

        $item_id = explode('-', $item_id);

        //currency
        $currency = get_settings('currency');
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


        $columns = array(
            0 => 'loading_id',
            1 => 'sn',
            2 => 'loading_date',
            3 => 'supplier',
            4 => 'location',
            5 => 'destination',
            6 => 'registration_number',
            7 => 'driver',
            8 => 'waybill_number',
            9 => 'quantity',
            10 => 'transporter_rate',
            11 => 'supplier_rate',
            12 => 'total_amount',
            13 => 'customer',
            14 => 'item',
            15 => 'td',
            
        );


        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->allClaimsCount($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $loading_query = $this->ajaxload->getAllClaimsDataTable($limit,$start,$order,$dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
        }
        else {
            $search = $this->input->post('search')['value'];
            $loading_query =  $this->ajaxload->claimsSearch($limit,$start,$search,$order,$dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
            $totalFiltered = $this->ajaxload->claimsSearchCount($search, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
        }
        

        if(!empty($loading_query)) {
                $sn = 1;
            foreach ($loading_query as $loading) { //dealing with waybill numbers here
                //quantity
                $this->db->select_sum('quantity');
                $this->db->where('waybill_number', $loading->waybill_number);
                $quantity_query = $this->db->get('v_loading')->row();

                //amount
                $this->db->select_sum('total_amount');
                $this->db->where('waybill_number', $loading->waybill_number);
                $amount_query = $this->db->get('v_loading')->row();

                //find others
                $this->db->where('waybill_number', $loading->waybill_number);
                $this->db->order_by('loading_id', 'asc');
                $main_query = $this->db->get('v_loading')->row();

                $quantity = $quantity_query->quantity;
                $total_amount = $main_query->supplier_rate * $quantity;

                $total += $total_amount; 
                //$driver_total += $loading->transporter_rate * $loading->quantity;
                $total_quantity += $quantity; 
                //$total_td += $loading->transport_difference; 

                

                $nestedData['sn'] = $sn;
                $nestedData['loading_date'] = date('d-m-Y', $main_query->loading_date);
                $nestedData['supplier'] = $this->getSupplierById($main_query->supplier_id)->supplier_name;
                //$nestedData['customer'] = $this->getCustomerById($main_query->customer_id)->customer_name;
                $nestedData['location'] = $this->getLocationNameById($main_query->location_id);
                //$nestedData['item'] = $this->getItemById($main_query->item_id)->item_name;
                $nestedData['destination'] = $this->getDestinationById($main_query->destination_id);
                $nestedData['registration_number'] = $this->vehicle_model->getVehicleRegistrationNumberById($main_query->vehicle_id);
                $nestedData['driver'] = $this->driver_model->getDriverNameById($main_query->driver_id);
                $nestedData['waybill_number'] = $loading->waybill_number;
                $nestedData['quantity'] = number_format($quantity, 0, '.', ',');
                //$nestedData['td'] = number_format($loading->transport_difference, 2, '.', ',');
                
                
                //$nestedData['transporter_rate'] = number_format($loading->transporter_rate, 2, '.', ',');
                $nestedData['supplier_rate'] = number_format($main_query->supplier_rate, 2, '.', ',');
                $nestedData['total_amount'] = number_format($total_amount, 2, '.', ',');
                //$nestedData['driver_total_amount'] = number_format(($loading->transporter_rate * $loading->quantity), 2, '.', ',');
                //$nestedData['date_created'] = date('d-m-Y', $main_query->date_created);
                //$nestedData['date_modified'] = date('d-m-Y', $main_query->date_modified);

                $nestedData['DT_RowId'] = 'loading_id_'.$main_query->loading_id;
                $nestedData['onclick'] = "populate_links('".$main_query->loading_id."')";
                $nestedData['ondblclick'] = "deselect_all()";

                $data[] = $nestedData;
                $loading_array[] = $loading->loading_id;

                            $sn++;  
                                     
            }
                    /*$bt_row['bottom_row'] = '
                                                    <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL AMOUNT: ' .numfmt_format_currency($fmt, $total, $currency).'</strong></div>';

                    $bt_row['driver_bottom_row'] = '
                                                    <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL: ' .numfmt_format_currency($fmt, $driver_total, $currency).'</strong></div>';*/
                    $bt_row['td_bottom_row'] = '
                                                    <div class="col-md-6 col-sm-6 text-lg-left"><strong>TOTAL TRANSPORT DIFF: ' .numfmt_format_currency($fmt, $total_td, $currency).'</strong></div>';

                    $grand_quantity = number_format($total_quantity, 0, '.', ',');
                    $grand_amount = numfmt_format_currency($fmt, $total, $currency);
                    $driver_grand_amount = numfmt_format_currency($fmt, $driver_total, $currency);
                    $td_grand_amount = numfmt_format_currency($fmt, $total_td, $currency);

        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "loading_ids"     => $loading_array,
            "bt_row"          => $bt_row,
            "grand_quantity"  => $grand_quantity,
            "grand_amount"    => $grand_amount,
            //"driver_grand_amount"    => $driver_grand_amount,
            //"td_grand_amount"    => $td_grand_amount
        );

        echo json_encode($json_data);
    }
    //end of company claims

    //load chart
    function drawChart($direction = '', $date = '') {
        $data = array();
        $days_array = array();
        $vehicle_reg = array();
        $all_vehicle_loading_rows = array();
        

        if($direction == 'right') {
            $new_month = date('F Y', strtotime('+1 month', $date));
            $new_month_digit = date('n', strtotime('+1 month', $date));
            $new_year_digit = date('Y', strtotime('+1 month', $date));
        } else if($direction == 'left') {
            $new_month = date('F Y', strtotime('-1 month', $date));
            $new_month_digit = date('n', strtotime('-1 month', $date));
            $new_year_digit = date('Y', strtotime('-1 month', $date));
        } else {
            $new_month = date('F Y', $date);
            $new_month_digit = date('n', $date);
            $new_year_digit = date('Y', $date);
        }

        //number of days in this month
        $data['num_of_days'] = cal_days_in_month(CAL_GREGORIAN, $new_month_digit, $new_year_digit);
        for($i = 1; $i <= $data['num_of_days']; $i++) {
            array_push($days_array, $i);
        }

        $vehicle_array = $this->vehicle_model->getAllVehicles()->result_array();
        $n = 1;
        foreach($vehicle_array as $v) {
            //$vehicle_loading_rows = array();

            for($i = 1; $i <= $data['num_of_days']; $i++) {
                $looped_date = strtotime(date($i.'-'.$new_month_digit.'-'.$new_year_digit));
                $loading_rows = $this->db->get_where('v_loading', array('vehicle_id' => $v['vehicle_id'], 'loading_date' => $looped_date))->num_rows();
                array_push($vehicle_loading_rows, $loading_rows); //push loadings found
                array_push($all_vehicle_loading_rows, $loading_rows);
            }

            array_push($vehicle_reg, $v['registration_number']);//push vehicle registration number
            //$data['vehicle_loading_rows'.$n] = $vehicle_loading_rows;

            $n++;
        }

        //first and last date
        $data['first_date'] = strtotime('first day of '.strtolower($new_month));
        $data['last_date'] = strtotime('last day of '.strtolower($new_month));
        $data['days_array'] = $days_array;
        $data['vehicle_reg'] = $vehicle_reg;
        $data['date_month'] = $new_month;
        $data['date'] = strtotime($new_month);
       // $data['vehicle_count'] = $n;
        $data['all_vehicle_loading_rows'] = array_chunk($all_vehicle_loading_rows, $data['num_of_days']);



        
        return json_encode($data);

        
    }

    //GET ALL TRANSPORTERS
    function getAllTransporters() {
        $this->db->order_by('transporter_id', 'asc');
        $all_transporters = $this->db->get('v_transporter');

        return $all_transporters;
    }

    //GET ALL TRANSPORTERS BY DESTINATION ID
    function getAllTransportersByDestinationId($destination_id) {
        $transporters_ids = array();

        $this->db->where('destination_id', $destination_id);
        $delivery_rates_query = $this->db->get('v_delivery_rates')->row();

        $all_transporters_row = $this->db->get('v_transporter')->num_rows();

        for($i = 1; $i <= $all_transporters_row; $i++) {
            $t_column = 'transporter_'.$i;
            if($delivery_rates_query->$t_column != NULL) {
                $transporter_id = substr($delivery_rates_query->$t_column, 0, strpos($delivery_rates_query->$t_column, '_'));

                array_push($transporters_ids, $transporter_id);
            }
        }

        //now query the transporters table
        $this->db->where_in('transporter_id', $transporters_ids);
        $this_transporters = $this->db->get('v_transporter')->result_array();

        return $this_transporters;
    }

    //get supplier rate
    function getSupplierRate($destination_id, $item_id) {
        $this->db->where('destination_id', $destination_id);
        $this->db->where('item_id', $item_id);
        $supplier_rate = $this->db->get('v_delivery_rates')->row()->supplier_rate;

        return $supplier_rate;

    }

    //get transporter rate
    function getTransporterRate($destination_id, $item_id, $transporter_id) {
        $all_transporters_row = $this->db->get('v_transporter')->num_rows();

        for($i = 1; $i <= $all_transporters_row; $i++) {
            $t_column = 'transporter_'.$i;

            $this->db->where('destination_id', $destination_id);
            $this->db->where('item_id', $item_id);
            $raw_transporter_rate = $this->db->get('v_delivery_rates')->row()->$t_column;

            if(substr($raw_transporter_rate, 0, strpos($raw_transporter_rate, '_')) == $transporter_id) {

                $transporter_rate = substr($raw_transporter_rate, strpos($raw_transporter_rate, '_') + 1);

                //exit the loop since we have found what we wanted
                break;
            }
        }
        

        return $transporter_rate;

    }

}