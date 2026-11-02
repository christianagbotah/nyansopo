<?php
/**
 * Vehicle / Transport / Haulage module — server-side DataTable backends.
 *
 * These global functions implement the ajaxload->...() calls made by Driver,
 * Vehicle, VehicleType, VehicleModel, Insurance, Department, Expenditure and
 * Loading models. They are dispatched from Ajaxdataload_model::__call() and
 * receive ($ci, $args) where $ci is the model instance (for ->db access).
 *
 * Table map (live DB, v_ prefix = vehicle module):
 *   v_driver, v_vehicle, v_vehicle_type, v_vehicle_model, v_transporter,
 *   v_car_insurance, v_department, v_expenditure, v_destination, v_location,
 *   v_loading, suppliers, customers, items
 */

/* ======================= DRIVERS ======================= */

function vajax_allDriverCount($ci, $args) {
    return $ci->db->count_all('v_driver');
}

function vajax_getAllDriversDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $ci->db->order_by($order, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_driver')->result();
}

function vajax_driverSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->like('first_name', $search);
    $ci->db->or_like('last_name', $search);
    $ci->db->or_like('middle_name', $search);
    $ci->db->or_like('mobile_1', $search);
    $ci->db->or_like('email', $search);
    $ci->db->order_by($order, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_driver')->result();
}

function vajax_driverSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->like('first_name', $search);
    $ci->db->or_like('last_name', $search);
    $ci->db->or_like('middle_name', $search);
    $ci->db->or_like('mobile_1', $search);
    $ci->db->or_like('email', $search);
    return $ci->db->get('v_driver')->num_rows();
}

/* ======================= VEHICLES ======================= */

function vajax_allVehicleCount($ci, $args) {
    return $ci->db->count_all('v_vehicle');
}

function vajax_getAllVehiclesDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $ci->db->select('v.*, t.type_name, m.model_name, CONCAT(d.first_name, " ", d.last_name) AS driver_name, tr.transporter_name');
    $ci->db->from('v_vehicle v');
    $ci->db->join('v_vehicle_type t', 't.type_id = v.type_id', 'left');
    $ci->db->join('v_vehicle_model m', 'm.model_id = v.model_id', 'left');
    $ci->db->join('v_driver d', 'd.driver_id = v.driver_id', 'left');
    $ci->db->join('v_transporter tr', 'tr.transporter_id = v.transporter_id', 'left');
    // Map display columns to real columns for ordering
    $order_map = array(
        'registration_number' => 'v.registration_number',
        'type_name' => 't.type_name',
        'model_name' => 'm.model_name',
        'driver_name' => 'driver_name',
        'transporter' => 'tr.transporter_name',
        'status' => 'v.status',
        'date_created' => 'v.date_created',
        'date_modified' => 'v.date_modified'
    );
    $order_col = isset($order_map[$order]) ? $order_map[$order] : 'v.vehicle_id';
    $ci->db->order_by($order_col, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_vehicleSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->select('v.*, t.type_name, m.model_name, CONCAT(d.first_name, " ", d.last_name) AS driver_name, tr.transporter_name');
    $ci->db->from('v_vehicle v');
    $ci->db->join('v_vehicle_type t', 't.type_id = v.type_id', 'left');
    $ci->db->join('v_vehicle_model m', 'm.model_id = v.model_id', 'left');
    $ci->db->join('v_driver d', 'd.driver_id = v.driver_id', 'left');
    $ci->db->join('v_transporter tr', 'tr.transporter_id = v.transporter_id', 'left');
    $ci->db->group_start();
    $ci->db->like('v.registration_number', $search);
    $ci->db->or_like('t.type_name', $search);
    $ci->db->or_like('m.model_name', $search);
    $ci->db->or_like('tr.transporter_name', $search);
    $ci->db->or_like('d.first_name', $search);
    $ci->db->or_like('d.last_name', $search);
    $ci->db->group_end();
    $ci->db->order_by('v.registration_number', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_vehicleSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->from('v_vehicle v');
    $ci->db->join('v_vehicle_type t', 't.type_id = v.type_id', 'left');
    $ci->db->join('v_vehicle_model m', 'm.model_id = v.model_id', 'left');
    $ci->db->join('v_driver d', 'd.driver_id = v.driver_id', 'left');
    $ci->db->join('v_transporter tr', 'tr.transporter_id = v.transporter_id', 'left');
    $ci->db->group_start();
    $ci->db->like('v.registration_number', $search);
    $ci->db->or_like('t.type_name', $search);
    $ci->db->or_like('m.model_name', $search);
    $ci->db->or_like('tr.transporter_name', $search);
    $ci->db->or_like('d.first_name', $search);
    $ci->db->or_like('d.last_name', $search);
    $ci->db->group_end();
    return $ci->db->count_all_results();
}

/* ======================= VEHICLE TYPES ======================= */

function vajax_allVehicleTypeCount($ci, $args) {
    return $ci->db->count_all('v_vehicle_type');
}

function vajax_getAllVehicleTypesDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $order_col = $order === 'vehicle_type' ? 'type_name' : $order;
    $ci->db->order_by($order_col, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_vehicle_type')->result();
}

function vajax_vehicleTypeSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->like('type_name', $search);
    $ci->db->order_by('type_name', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_vehicle_type')->result();
}

function vajax_vehicleTypeSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->like('type_name', $search);
    return $ci->db->get('v_vehicle_type')->num_rows();
}

/* ======================= VEHICLE MODELS ======================= */

function vajax_allVehicleModelCount($ci, $args) {
    return $ci->db->count_all('v_vehicle_model');
}

function vajax_getAllVehicleModelsDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $ci->db->select('m.*, t.type_name');
    $ci->db->from('v_vehicle_model m');
    $ci->db->join('v_vehicle_type t', 't.type_id = m.type_id', 'left');
    $order_map = array('vehicle_model' => 'm.model_name', 'vehicle_type' => 't.type_name');
    $order_col = isset($order_map[$order]) ? $order_map[$order] : 'm.model_id';
    $ci->db->order_by($order_col, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_vehicleModelSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->select('m.*, t.type_name');
    $ci->db->from('v_vehicle_model m');
    $ci->db->join('v_vehicle_type t', 't.type_id = m.type_id', 'left');
    $ci->db->like('m.model_name', $search);
    $ci->db->or_like('t.type_name', $search);
    $ci->db->order_by('m.model_name', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_vehicleModelSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->from('v_vehicle_model m');
    $ci->db->join('v_vehicle_type t', 't.type_id = m.type_id', 'left');
    $ci->db->like('m.model_name', $search);
    $ci->db->or_like('t.type_name', $search);
    return $ci->db->count_all_results();
}

/* ======================= VEHICLE INSURANCE ======================= */

function vajax_allVehicleInsuranceCount($ci, $args) {
    return $ci->db->count_all('v_car_insurance');
}

function vajax_getAllVehiclesInsuranceDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $ci->db->order_by($order, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_car_insurance')->result();
}

function vajax_vehicleInsuranceSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->like('insurance_name', $search);
    $ci->db->or_like('insurance_company', $search);
    $ci->db->order_by('insurance_name', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_car_insurance')->result();
}

function vajax_vehicleInsuranceSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->like('insurance_name', $search);
    $ci->db->or_like('insurance_company', $search);
    return $ci->db->get('v_car_insurance')->num_rows();
}

/* ======================= DEPARTMENTS ======================= */

function vajax_allDepartmentsCount($ci, $args) {
    return $ci->db->count_all('v_department');
}

function vajax_getAllDepartmentsDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $order_col = $order === 'department' ? 'department_name' : $order;
    $ci->db->order_by($order_col, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_department')->result();
}

function vajax_departmentSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->like('department_name', $search);
    $ci->db->order_by('department_name', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_department')->result();
}

function vajax_departmentSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->like('department_name', $search);
    return $ci->db->get('v_department')->num_rows();
}

/* ======================= EXPENDITURE ======================= */

function _vajax_expenditure_filter($ci, $from, $to, $type, $vehicle, $driver) {
    if ($from != '' && $to != '') {
        $ci->db->where('expenditure_date >=', $from);
        $ci->db->where('expenditure_date <=', $to);
    }
    if ($type != '' && $type != '0') {
        $ci->db->where('expenditure_type', $type);
    }
    if ($vehicle != '' && $vehicle != 0) {
        $ci->db->where('vehicle_id', $vehicle);
    }
    if ($driver != '' && $driver != 0) {
        $ci->db->where('driver_id', $driver);
    }
}

function vajax_allExpenditureCount($ci, $args) {
    list($from, $to, $type, $vehicle, $driver) = array_pad($args, 5, '');
    _vajax_expenditure_filter($ci, $from, $to, $type, $vehicle, $driver);
    return $ci->db->count_all_results('v_expenditure');
}

function vajax_getAllExpendituresDataTable($ci, $args) {
    list($limit, $start, $order, $dir, $from, $to, $type, $vehicle, $driver) = array_pad($args, 9, '');
    $order_map = array(
        'name' => 'expenditure_name',
        'amount' => 'expenditure_amount',
        'date' => 'expenditure_date',
        'type' => 'expenditure_type',
        'date_created' => 'date_created',
        'date_modified' => 'date_modified'
    );
    $order_col = isset($order_map[$order]) ? $order_map[$order] : 'expenditure_id';
    _vajax_expenditure_filter($ci, $from, $to, $type, $vehicle, $driver);
    $ci->db->order_by($order_col, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_expenditure')->result();
}

function vajax_expenditureSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir, $from, $to, $type, $vehicle, $driver) = array_pad($args, 10, '');
    _vajax_expenditure_filter($ci, $from, $to, $type, $vehicle, $driver);
    $ci->db->group_start();
    $ci->db->like('expenditure_name', $search);
    $ci->db->or_like('expenditure_type', $search);
    $ci->db->group_end();
    $ci->db->order_by('expenditure_date', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_expenditure')->result();
}

function vajax_expenditureSearchCount($ci, $args) {
    list($search, $from, $to, $type, $vehicle, $driver) = array_pad($args, 6, '');
    _vajax_expenditure_filter($ci, $from, $to, $type, $vehicle, $driver);
    $ci->db->group_start();
    $ci->db->like('expenditure_name', $search);
    $ci->db->or_like('expenditure_type', $search);
    $ci->db->group_end();
    return $ci->db->count_all_results('v_expenditure');
}

/* ======================= DESTINATIONS ======================= */

function vajax_allDestinationsCount($ci, $args) {
    return $ci->db->count_all('v_destination');
}

function vajax_getAllDestinationsDataTable($ci, $args) {
    list($limit, $start, $order, $dir) = $args;
    $ci->db->order_by($order, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_destination')->result();
}

function vajax_destinationSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir) = $args;
    $ci->db->like('destination_name', $search);
    $ci->db->order_by('destination_name', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get('v_destination')->result();
}

function vajax_destinationSearchCount($ci, $args) {
    list($search) = $args;
    $ci->db->like('destination_name', $search);
    return $ci->db->get('v_destination')->num_rows();
}

/* ======================= LOADINGS (with full filter set) ======================= */

function _vajax_loading_select($ci) {
    $ci->db->select('l.*, s.supplier_name, loc.location_name, dest.destination_name, v.registration_number,
        CONCAT(d.first_name, " ", d.last_name) AS driver_name, c.customer_name, i.item_name');
    $ci->db->from('v_loading l');
    $ci->db->join('suppliers s', 's.supplier_id = l.supplier_id', 'left');
    $ci->db->join('v_location loc', 'loc.location_id = l.location_id', 'left');
    $ci->db->join('v_destination dest', 'dest.destination_id = l.destination_id', 'left');
    $ci->db->join('v_vehicle v', 'v.vehicle_id = l.vehicle_id', 'left');
    $ci->db->join('v_driver d', 'd.driver_id = l.driver_id', 'left');
    $ci->db->join('customers c', 'c.customer_id = l.customer_id', 'left');
    $ci->db->join('items i', 'i.item_id = l.item_id', 'left');
}

function _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) {
    if ($from != '' && $to != '') {
        $ci->db->where('l.loading_date >=', $from);
        $ci->db->where('l.loading_date <=', $to);
    }
    if ($vehicle_id != '' && $vehicle_id != 0) $ci->db->where('l.vehicle_id', $vehicle_id);
    if ($driver_id != '' && $driver_id != 0) $ci->db->where('l.driver_id', $driver_id);
    if ($destination_id != '' && $destination_id != 0) $ci->db->where('l.destination_id', $destination_id);
    if ($location_id != '' && $location_id != 0) $ci->db->where('l.location_id', $location_id);
    if ($customer_id != '' && $customer_id != 0) $ci->db->where('l.customer_id', $customer_id);
    if ($supplier_id != '' && $supplier_id != 0) $ci->db->where('l.supplier_id', $supplier_id);
    // item_id may arrive as "id-name" composite or an array from explode()
    if (is_array($item_id)) {
        $item_id = reset($item_id);
    }
    if ($item_id != '' && $item_id != 0 && strpos($item_id, '-') !== false) {
        $item_id = explode('-', $item_id);
        $item_id = $item_id[0];
    }
    if ($item_id != '' && $item_id != 0) $ci->db->where('l.item_id', $item_id);
    if ($waybill_number != '' && $waybill_number != 0) $ci->db->where('l.waybill_number', $waybill_number);
}

function vajax_allLoadingCount($ci, $args) {
    list($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 10, '');
    _vajax_loading_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    return $ci->db->count_all_results();
}

function vajax_getAllLoadingsDataTable($ci, $args) {
    list($limit, $start, $order, $dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 14, '');
    $order_map = array(
        'loading_date' => 'l.loading_date',
        'supplier' => 's.supplier_name',
        'location' => 'loc.location_name',
        'destination' => 'dest.destination_name',
        'registration_number' => 'v.registration_number',
        'driver' => 'driver_name',
        'waybill_number' => 'l.waybill_number',
        'quantity' => 'l.quantity',
        'transporter_rate' => 'l.transporter_rate',
        'supplier_rate' => 'l.supplier_rate',
        'total_amount' => 'l.total_amount',
        'customer' => 'c.customer_name',
        'item' => 'i.item_name',
        'td' => 'l.transport_difference'
    );
    $order_col = isset($order_map[$order]) ? $order_map[$order] : 'l.loading_id';
    _vajax_loading_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    $ci->db->order_by($order_col, $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_loadingSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 15, '');
    _vajax_loading_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    $ci->db->group_start();
    $ci->db->like('l.waybill_number', $search);
    $ci->db->or_like('s.supplier_name', $search);
    $ci->db->or_like('c.customer_name', $search);
    $ci->db->or_like('v.registration_number', $search);
    $ci->db->or_like('dest.destination_name', $search);
    $ci->db->or_like('i.item_name', $search);
    $ci->db->group_end();
    $ci->db->order_by('l.loading_date', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_loadingSearchCount($ci, $args) {
    list($search, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 11, '');
    _vajax_loading_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    $ci->db->group_start();
    $ci->db->like('l.waybill_number', $search);
    $ci->db->or_like('s.supplier_name', $search);
    $ci->db->or_like('c.customer_name', $search);
    $ci->db->or_like('v.registration_number', $search);
    $ci->db->or_like('dest.destination_name', $search);
    $ci->db->or_like('i.item_name', $search);
    $ci->db->group_end();
    return $ci->db->count_all_results();
}

/* ======================= COMPANY CLAIMS (grouped by waybill) ======================= */

function _vajax_claims_select($ci) {
    $ci->db->select('l.waybill_number, MIN(l.loading_date) as loading_date, MIN(l.supplier_id) as supplier_id,
        MIN(l.location_id) as location_id, MIN(l.destination_id) as destination_id, MIN(l.vehicle_id) as vehicle_id,
        MIN(l.driver_id) as driver_id, MIN(l.customer_id) as customer_id, MIN(l.item_id) as item_id,
        SUM(l.quantity) as quantity, MIN(l.transporter_rate) as transporter_rate, MIN(l.supplier_rate) as supplier_rate,
        SUM(l.total_amount) as total_amount, MIN(l.transport_difference) as transport_difference,
        s.supplier_name, loc.location_name, dest.destination_name, v.registration_number,
        CONCAT(d.first_name, " ", d.last_name) AS driver_name, c.customer_name, i.item_name');
    $ci->db->from('v_loading l');
    $ci->db->join('suppliers s', 's.supplier_id = l.supplier_id', 'left');
    $ci->db->join('v_location loc', 'loc.location_id = l.location_id', 'left');
    $ci->db->join('v_destination dest', 'dest.destination_id = l.destination_id', 'left');
    $ci->db->join('v_vehicle v', 'v.vehicle_id = l.vehicle_id', 'left');
    $ci->db->join('v_driver d', 'd.driver_id = l.driver_id', 'left');
    $ci->db->join('customers c', 'c.customer_id = l.customer_id', 'left');
    $ci->db->join('items i', 'i.item_id = l.item_id', 'left');
    $ci->db->group_by('l.waybill_number');
}

function vajax_allClaimsCount($ci, $args) {
    list($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 10, '');
    _vajax_claims_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    return $ci->db->count_all_results();
}

function vajax_getAllClaimsDataTable($ci, $args) {
    list($limit, $start, $order, $dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 14, '');
    _vajax_claims_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    $ci->db->order_by('loading_date', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_claimsSearch($ci, $args) {
    list($limit, $start, $search, $order, $dir, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 15, '');
    _vajax_claims_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    $ci->db->group_start();
    $ci->db->like('l.waybill_number', $search);
    $ci->db->or_like('s.supplier_name', $search);
    $ci->db->or_like('c.customer_name', $search);
    $ci->db->or_like('v.registration_number', $search);
    $ci->db->or_like('dest.destination_name', $search);
    $ci->db->group_end();
    $ci->db->order_by('loading_date', $dir);
    $ci->db->limit($limit, $start);
    return $ci->db->get()->result();
}

function vajax_claimsSearchCount($ci, $args) {
    list($search, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number) = array_pad($args, 11, '');
    _vajax_claims_select($ci);
    _vajax_loading_filter($ci, $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $customer_id, $supplier_id, $item_id, $waybill_number);
    $ci->db->group_start();
    $ci->db->like('l.waybill_number', $search);
    $ci->db->or_like('s.supplier_name', $search);
    $ci->db->or_like('c.customer_name', $search);
    $ci->db->or_like('v.registration_number', $search);
    $ci->db->or_like('dest.destination_name', $search);
    $ci->db->group_end();
    return $ci->db->count_all_results();
}
