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
class Vehicle extends CI_Controller {

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

    //get vehicle image
    function getVehicleImageById($vehicle_id = '') {
        if($vehicle_id == '') {
            $vehicle_id = $this->input->post('id');
        }

        echo $this->vehicle_model->getVehicleImageById($vehicle_id);
    }


    //add vehicle
    function add_vehicle() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $vehicle_data['date_created'] = strtotime(date('d-m-Y'));
        $vehicle_data['date_modified'] = strtotime(date('d-m-Y'));
        $vehicle_data['transporter_id'] = strtoupper($this->input->post('transporter'));
        $vehicle_data['registration_number'] = strtoupper($this->input->post('registration_number'));
        $vehicle_data['type_id'] = $this->input->post('vehicle_type');
        $vehicle_data['model_id'] = $this->input->post('vehicle_model');
        //$vehicle_data['driver_id'] = $this->input->post('driver');
        $vehicle_data['department_id'] = $this->input->post('department');
        $vehicle_data['road_worthy_amount'] = $this->input->post('road_worthy_amount');
        $vehicle_data['road_worthy_expiry_date'] = strtotime($this->input->post('road_worthy_expiry_date'));
        $vehicle_data['insurance_id'] = $this->input->post('insurance_type');
        $vehicle_data['insurance_amount'] = $this->input->post('insurance_amount');
        $vehicle_data['insurance_expiry_date'] = strtotime($this->input->post('insurance_expiry_date'));
        $vehicle_data['status'] = 'Active';

        //Validate the form inputs
        if (empty($vehicle_data['registration_number'])) {
            $errors['registration_number'] = 'Vehicle Registration Number Required!';
        }

        if (empty($vehicle_data['type_id'])) {
            $errors['type_id'] = 'Select The Vehicle Type!';
        }

        if (empty($vehicle_data['model_id'])) {
            $errors['model_id'] = 'Select The Vehicle Model!';
        }

       // if (empty($vehicle_data['driver_id'])) {
          //  $errors['driver_id'] = 'Select Driver For This Vehicle!';
      //  }
        if (empty($vehicle_data['road_worthy_amount'])) {
            $error['road_worthy_amount'] = 'Road Worthy Amount Required!';
        }

        if (!is_numeric($vehicle_data['road_worthy_amount'])) {
            $errors['road_worthy_amount_numeric'] = 'Road Worthy Amount Must Be A Number!';
        }

        if (empty($vehicle_data['road_worthy_expiry_date'])) {
            $errors['road_worthy_expiry_date'] = 'Expiry Date For The Road Worthy Required!';
        }

        if (!is_numeric($vehicle_data['insurance_amount'])) {
            $errors['insurance_amount_numeric'] = 'Insurance Amount Must Be A Number!';
        }

        if (empty($vehicle_data['insurance_id'])) {
            $errors['insurance_id'] = 'Insurance Required!';
        }

        if (empty($vehicle_data['insurance_amount'])) {
            $errors['insurance_amount'] = 'Insurance Amount Required!';
        }



        if (empty($vehicle_data['insurance_expiry_date'])) {
            $errors['insurance_expiry_date'] = 'Expiry Date For The Insurance Required!';
        }

        //Duplicate Validation
        if(!vehicle_duplicate($vehicle_data['registration_number'], 'add')) {
            //duplicate found so reject the submission
            $errors['vehicle_duplicate'] = 'Duplicate found: Vehicle with the same registration number already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->vehicle_model->add_vehicle($vehicle_data);

            if($result != 0) {
                //upload the vehicle image to  uploads/vehicle_image directory
                if(isset($_FILES['vehicle_image']['name'])) {
                    $upload_image = move_uploaded_file($_FILES['vehicle_image']['tmp_name'], 'uploads/vehicle_image/'.$result.'.jpg');
                } 

                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Vehicle with registration number '.$vehicle_data['registration_number']. ' added successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
            } 

            echo json_encode($ajax_data);  
        }
    }

    //edit vehicle
    function edit_vehicle($vehicle_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $vehicle_data['date_modified'] = strtotime(date('d-m-Y'));
        $vehicle_data['transporter_id'] = strtoupper($this->input->post('transporter'));
        $vehicle_data['registration_number'] = strtoupper($this->input->post('registration_number'));
        $vehicle_data['type_id'] = $this->input->post('vehicle_type');
        $vehicle_data['model_id'] = $this->input->post('vehicle_model');
        //$vehicle_data['driver_id'] = $this->input->post('driver');
        $vehicle_data['department_id'] = $this->input->post('department');
        $vehicle_data['road_worthy_amount'] = $this->input->post('road_worthy_amount');
        $vehicle_data['road_worthy_expiry_date'] = strtotime($this->input->post('road_worthy_expiry_date'));
        $vehicle_data['insurance_id'] = $this->input->post('insurance_type');
        $vehicle_data['insurance_amount'] = $this->input->post('insurance_amount');
        $vehicle_data['insurance_expiry_date'] = strtotime($this->input->post('insurance_expiry_date'));
        $vehicle_data['status'] = $this->input->post('vehicle_status');

        //Validate the form inputs
        if (empty($vehicle_data['registration_number'])) {
            $errors['registration_number'] = 'Vehicle Registration Number Required!';
        }

        if (empty($vehicle_data['type_id'])) {
            $errors['type_id'] = 'Select The Vehicle Type!';
        }

        if (empty($vehicle_data['model_id'])) {
            $errors['model_id'] = 'Select The Vehicle Model!';
        }

       // if (empty($vehicle_data['driver_id'])) {
          //  $errors['driver_id'] = 'Select Driver For This Vehicle!';
      //  }
        if (empty($vehicle_data['road_worthy_amount'])) {
            $error['road_worthy_amount'] = 'Road Worthy Amount Required!';
        }

        if (empty($vehicle_data['road_worthy_expiry_date'])) {
            $errors['road_worthy_expiry_date'] = 'Expiry Date For The Road Worthy Required!';
        }

        if (!is_numeric($vehicle_data['insurance_amount'])) {
            $errors['insurance_amount_numeric'] = 'Insurance Amount Must Be A Number!';
        }

        if (empty($vehicle_data['road_worthy_expiry_date'])) {
            $errors['road_worthy_expiry_date'] = 'Expiry Date For The Road Worthy Required!';
        }

        if (empty($vehicle_data['insurance_id'])) {
            $errors['insurance_id'] = 'Insurance Required!';
        }

        if (empty($vehicle_data['insurance_amount'])) {
            $errors['insurance_amount'] = 'Insurance Amount Required!';
        }

        if (empty($vehicle_data['insurance_expiry_date'])) {
            $errors['insurance_expiry_date'] = 'Expiry Date For The Insurance Required!';
        }

        //Duplicate Validation
        if(!vehicle_duplicate($vehicle_data['registration_number'], 'update')) {
            //duplicate found so reject the submission
            $errors['vehicle_duplicate'] = 'Duplicate found: Vehicle with the same registration number already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->vehicle_model->edit_vehicle($vehicle_data, $vehicle_id);

            //upload the vehicle image to  uploads/vehicle_image directory
            if(isset($_FILES['vehicle_image']['name'])) {
                $upload_image = move_uploaded_file($_FILES['vehicle_image']['tmp_name'], 'uploads/vehicle_image/'.$vehicle_id.'.jpg');
            }

            if($result != 0) {
                
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Vehicle with registration number '.$vehicle_data['registration_number']. ' updated successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'We did not detect any change in the form submitted. If you changed the image, we have processed it, so close the form if you do not want to edit further!';
            } 

            echo json_encode($ajax_data);  
        }
    }

    //delete Vehicle
    function delete_vehicle($vehicle_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->vehicle_model->delete_vehicle($vehicle_id);

        if($result == true) {
            //delete the image from the folder
            $image_path = 'uploads/vehicle_image/'.$vehicle_id.'.jpg';
            if(file_exists($image_path)) {
                unlink($image_path);
            }

            $data['success'] = true;
            $data['user'] = 'Vehicle';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Vehicle could not be deleted because it is used in either loading or expenditure records!';
        }
        
        
        echo json_encode($data);

    }


    //add vehicle type
    function add_vehicle_type() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $vehicle_type_data['date_created'] = strtotime(date('d-m-Y'));
        $vehicle_type_data['date_modified'] = strtotime(date('d-m-Y'));
        $vehicle_type_data['type_name'] = strtoupper($this->input->post('vehicle_type'));

        //Validate the form inputs
        if (empty($vehicle_type_data['type_name'])) {
            $errors['type_name'] = 'Vehicle Type Required!';
        }

        //Duplicate Validation
        if(!vehicle_type_duplicate($vehicle_type_data['type_name'], 'add')) {
            //duplicate found so reject the submission
            $errors['vehicle_type_duplicate'] = 'Duplicate found: The same vehicle type already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->vehicle_model->add_vehicle_type($vehicle_type_data);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Vehicle type:  '.$vehicle_type_data['type_name']. ' added successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
            } 

            echo json_encode($ajax_data);  
        }
    }

    //edit vehicle type
    function edit_vehicle_type($type_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $vehicle_type_data['date_modified'] = strtotime(date('d-m-Y'));
        $vehicle_type_data['type_name'] = strtoupper($this->input->post('vehicle_type'));

        //Validate the form inputs
        if (empty($vehicle_type_data['type_name'])) {
            $errors['type_name'] = 'Vehicle Type Required!';
        }

        //Duplicate Validation
        if(!vehicle_type_duplicate($vehicle_type_data['type_name'], 'update')) {
            //duplicate found so reject the submission
            $errors['vehicle_type_duplicate'] = 'Duplicate found: The same vehicle type already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //update data
            $result = $this->vehicle_model->edit_vehicle_type($vehicle_type_data, $type_id);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Vehicle type:  '.$vehicle_type_data['type_name']. ' updated successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'We could not detect any changes in the form you submitted. Kindly close this form if you do not want to edit further!';
            } 

            echo json_encode($ajax_data);  
        }
    }

    //delete Vehicle Type
    function delete_vehicle_type($type_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->vehicle_model->delete_vehicle_type($type_id);

        if($result == true) {
            $data['success'] = true;
            $data['user'] = 'Vehicle Type';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Vehicle Type could not be deleted because it is used in either Vehicle or Vehicle Model records!';
        }
        
        
        echo json_encode($data);

    }

    //add vehicle model
    function add_vehicle_model() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $vehicle_model_data['date_created'] = strtotime(date('d-m-Y'));
        $vehicle_model_data['date_modified'] = strtotime(date('d-m-Y'));
        $vehicle_model_data['model_name'] = strtoupper($this->input->post('vehicle_model'));
        $vehicle_model_data['type_id'] = $this->input->post('vehicle_type');


        //Validate the form inputs
        if (empty($vehicle_model_data['model_name'])) {
            $errors['model_name'] = 'Model Name Required!';
        }

        if (empty($vehicle_model_data['type_id'])) {
            $errors['type_id'] = 'Vehicle Type Required!';
        }

        //Duplicate Validation
        if(!vehicle_model_duplicate($vehicle_model_data['model_name'], $vehicle_model_data['type_id'], 'add')) {
            //duplicate found so reject the submission
            $errors['model_name_duplicate'] = 'Duplicate found: The same vehicle model already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->vehicle_model->add_vehicle_model($vehicle_model_data);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Vehicle model:  '.$vehicle_model_data['model_name']. ' of type '.$this->vehicle_model->getVehicleTypeById($vehicle_model_data['type_id']).' added successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
            } 

            echo json_encode($ajax_data);  
        }

    }

    //edit vehicle model
    function edit_vehicle_model($model_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $vehicle_model_data['date_modified'] = strtotime(date('d-m-Y'));
        $vehicle_model_data['model_name'] = strtoupper($this->input->post('vehicle_model'));
        $vehicle_model_data['type_id'] = $this->input->post('vehicle_type');


        //Validate the form inputs
        if (empty($vehicle_model_data['model_name'])) {
            $errors['model_name'] = 'Model Name Required!';
        }

        if (empty($vehicle_model_data['type_id'])) {
            $errors['type_id'] = 'Vehicle Type Required!';
        }

        //Duplicate Validation
        if(!vehicle_model_duplicate($vehicle_model_data['model_name'], $vehicle_model_data['type_id'], 'update')) {
            //duplicate found so reject the submission
            $errors['model_name_duplicate'] = 'Duplicate found: The same vehicle model already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //update data
            $result = $this->vehicle_model->edit_vehicle_model($vehicle_model_data, $model_id);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Vehicle model:  '.$vehicle_model_data['model_name']. ' of type '.$this->vehicle_model->getVehicleTypeById($vehicle_model_data['type_id']).' updated successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'We could not detect any changes in your submitted form. Kindly close this form if you do not want to edit further!';
            } 

            echo json_encode($ajax_data);  
        }

    }

    //delete Vehicle Model
    function delete_vehicle_model($model_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->vehicle_model->delete_vehicle_model($model_id);

        if($result == true) {
            $data['success'] = true;
            $data['user'] = 'Vehicle Model';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Vehicle Model could not be deleted because it is used in Vehicle records!';
        }
        
        
        echo json_encode($data);

    }

    //verify vehicle roadworthy expiry date
    function verifyRoadworthyExpiryDate($date) {
        $now = strtotime(date('d-m-Y'));
        $expirydate = strtotime(date($date));

        $ajax_data = array();
        $ajax_data['expired'] = 0;
        $ajax_data['will_expire'] = 0;

        if($now >= $expirydate) {
            $ajax_data['expired'] = 1;
        } 

        $now2 = date('Y-m-d');
        $expirydate2 = date_format(date_create($date), 'Y-m-d');

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

        if($age_y < 2) {
            $ajax_data['will_expire'] = 1;
            $ajax_data['duration'] = $age_y. $y.' '. $age_m.$m.' '.$age_d.$d;
        }



        echo json_encode($ajax_data);
    } 

    //FILTER VEHICLE LIST
    function getAllVehiclesDataTable() {

        $this->vehicle_model->getAllVehiclesDataTable();

    }


    //FILTER VEHICLE TYPE LIST
    function getAllVehicleTypesDataTable() {

        $this->vehicle_model->getAllVehicleTypesDataTable();

    }

    //FILTER VEHICLE MODEL LIST
    function getAllVehicleModelsDataTable() {

        $this->vehicle_model->getAllVehicleModelsDataTable();

    }

    //print vehicles
    function printAllVehicles() {
        
        $page_data['page_title'] = 'Print All Vehicles';
        $page_data['page_heading'] = 'LIST OF VEHICLES';
        $page_data['allVehicles'] = $this->vehicle_model->getAllVehicles()->result_array();
        $this->load->view('backend/admin/pages/vehicle/printAllVehicles.php', $page_data);

    }


}
