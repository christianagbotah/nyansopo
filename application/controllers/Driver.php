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
class Driver extends CI_Controller {

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

    //get driver image
    function getDriverImageById($driver_id = '') {
        if($driver_id == '') {
            $driver_id = $this->input->post('id');
        }

        echo $this->driver_model->getDriverImageById($driver_id);
    }

    //add driver
    function add_driver() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $driver_data['date_created'] = strtotime(date('d-m-Y'));
        $driver_data['date_modified'] = strtotime(date('d-m-Y'));
        $driver_data['driver_code'] = 'D'.date('Y').substr(strtotime(date('d-m-Y H:i:s')), -3);
        $driver_data['first_name'] = strtoupper($this->input->post('first_name'));
        $driver_data['last_name'] = strtoupper($this->input->post('last_name'));
        $driver_data['middle_name'] = strtoupper($this->input->post('middle_name'));
        $driver_data['date_of_birth'] = strtotime($this->input->post('date_of_birth'));
        $driver_data['number_of_dependants'] = $this->input->post('number_of_dependants');
        $driver_data['gender_id'] = $this->input->post('gender');
        $driver_data['mobile_1'] = $this->input->post('mobile_1');
        $driver_data['mobile_2'] = $this->input->post('mobile_2');
        $driver_data['religion'] = strtoupper($this->input->post('religion'));
        $driver_data['marital_status_id'] = $this->input->post('marital_status');
        $driver_data['residential_address'] = strtoupper($this->input->post('address'));
        $driver_data['email'] = strtoupper($this->input->post('email'));
        $driver_data['date_employed'] = strtotime($this->input->post('date_employed'));
        $driver_data['vehicle_id'] = $this->input->post('vehicle');
        $driver_data['license_id'] = $this->input->post('license');
        $driver_data['license_expiry_date'] = strtotime($this->input->post('license_expiry_date'));
        $driver_data['department_id'] = $this->input->post('department');

        //Validate the form inputs
        if (empty($driver_data['first_name'])) {
            $errors['first_name'] = 'First Name Required!';
        }

        if (empty($driver_data['last_name'])) {
            $errors['last_name'] = 'Last Name Required!';
        }

        if (empty($driver_data['date_of_birth'])) {
            $errors['date_of_birth'] = 'Date Of Birth Required!';
        }

        if (empty($driver_data['gender_id'])) {
            $errors['gender_id'] = 'Gender Required!';
        }

        if (empty($driver_data['mobile_1'])) {
            $errors['mobile_1'] = 'Mobile Number 1 Required!';
        }

        if (strlen($driver_data['mobile_1']) < 10 || strlen($driver_data['mobile_1']) > 10) {
            $errors['mobile_1_invalid'] = 'Invalid Mobile 1 Number! (Avoid +233)';
        }

        if(!empty($driver_data['mobile_2'])) {
            if (strlen($driver_data['mobile_2']) < 10 || strlen($driver_data['mobile_2']) > 10) {
                $errors['mobile_2_invalid'] = 'Invalid Mobile 2 Number! (Avoid +233)';
            }
        }

        if (empty($driver_data['marital_status_id'])) {
            $errors['marital_status_id'] = 'Marital Status Required!';
        }

        if (empty($driver_data['residential_address'])) {
            $errors['residential_address'] = 'Residential Address Required!';
        }

        if (empty($driver_data['vehicle_id'])) {
            $errors['vehicle_id'] = 'You must select a vehicle!';
        }

        if (empty($driver_data['license_id'])) {
            $errors['license_id'] = 'You must select a license!';
        }

        if (empty($driver_data['license_expiry_date'])) {
            $errors['license_expiry_date'] = 'License Expiry Date Required!';
        }

        //validate email address
        if (!empty($driver_data['email'])) {
            if(!valid_email($driver_data['email'])) {
                $errors['email'] = 'Invalid Email Address!';
            }
        }

        //Duplicate Validation
        if(!driver_duplicate($driver_data['mobile_1'], $driver_data['mobile_2'], $driver_data['email'], 'add')) {
            //duplicate found so reject the submission
            $errors['driver_duplicate'] = 'Duplicate found: This driver already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->driver_model->add_driver($driver_data);

            if($result != 0) {
                //upload the driver image to  uploads/driver_image directory
                if(isset($_FILES['driver_image']['name'])) {
                    $upload_image = move_uploaded_file($_FILES['driver_image']['tmp_name'], 'uploads/driver_image/'.$result.'.jpg');
                } 

                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Driver\'s name: '.$driver_data['first_name'].' '.$driver_data['last_name'].' '.$driver_data['middle_name']. ' added successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
            } 

            echo json_encode($ajax_data);  
        }
    }

    //edit driver
    function edit_driver($driver_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $driver_data['date_modified'] = strtotime(date('d-m-Y'));
        $driver_data['first_name'] = strtoupper($this->input->post('first_name'));
        $driver_data['last_name'] = strtoupper($this->input->post('last_name'));
        $driver_data['middle_name'] = strtoupper($this->input->post('middle_name'));
        $driver_data['date_of_birth'] = strtotime($this->input->post('date_of_birth'));
        $driver_data['number_of_dependants'] = $this->input->post('number_of_dependants');
        $driver_data['gender_id'] = $this->input->post('gender');
        $driver_data['mobile_1'] = $this->input->post('mobile_1');
        $driver_data['mobile_2'] = $this->input->post('mobile_2');
        $driver_data['religion'] = strtoupper($this->input->post('religion'));
        $driver_data['marital_status_id'] = $this->input->post('marital_status');
        $driver_data['residential_address'] = strtoupper($this->input->post('address'));
        $driver_data['email'] = strtoupper($this->input->post('email'));
        $driver_data['date_employed'] = strtotime($this->input->post('date_employed'));
        $driver_data['vehicle_id'] = $this->input->post('vehicle');
        $driver_data['license_id'] = $this->input->post('license');
        $driver_data['license_expiry_date'] = strtotime($this->input->post('license_expiry_date'));
        $driver_data['department_id'] = $this->input->post('department');

        //Validate the form inputs
        if (empty($driver_data['first_name'])) {
            $errors['first_name'] = 'First Name Required!';
        }

        if (empty($driver_data['last_name'])) {
            $errors['last_name'] = 'Last Name Required!';
        }

        if (empty($driver_data['date_of_birth'])) {
            $errors['date_of_birth'] = 'Date Of Birth Required!';
        }

        if (empty($driver_data['gender_id'])) {
            $errors['gender_id'] = 'Gender Required!';
        }

        if (empty($driver_data['mobile_1'])) {
            $errors['mobile_1'] = 'Mobile Number 1 Required!';
        }

        if (strlen($driver_data['mobile_1']) < 10 || strlen($driver_data['mobile_1']) > 10) {
            $errors['mobile_1_invalid'] = 'Invalid Mobile 1 Number! (Avoid +233)';
        }

        if(!empty($driver_data['mobile_2'])) {
            if (strlen($driver_data['mobile_2']) < 10 || strlen($driver_data['mobile_2']) > 10) {
                $errors['mobile_2_invalid'] = 'Invalid Mobile 2 Number! (Avoid +233)';
            }
        }

        if (empty($driver_data['marital_status_id'])) {
            $errors['marital_status_id'] = 'Marital Status Required!';
        }

        if (empty($driver_data['residential_address'])) {
            $errors['residential_address'] = 'Residential Address Required!';
        }

        if ($driver_data['vehicle_id'] == '') {
            $errors['vehicle_id'] = 'You must select a vehicle! ' .$driver_data['vehicle_id'];
        }

        if (empty($driver_data['license_id'])) {
            $errors['license_id'] = 'You must select a license!';
        }

        if (empty($driver_data['license_expiry_date'])) {
            $errors['license_expiry_date'] = 'License Expiry Date Required!';
        }

        //validate email address
        if (!empty($driver_data['email'])) {
            if(!valid_email($driver_data['email'])) {
                $errors['email'] = 'Invalid Email Address!';
            }
        }

        //Duplicate Validation
        if(!driver_duplicate($driver_data['mobile_1'], $driver_data['mobile_2'], $driver_data['email'], 'update')) {
            //duplicate found so reject the submission
            $errors['driver_duplicate'] = 'Duplicate found: This driver already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            
            //upload the driver image to  uploads/driver_image directory
            if(isset($_FILES['driver_image']['name'])) {
                $upload_image = move_uploaded_file($_FILES['driver_image']['tmp_name'], 'uploads/driver_image/'.$driver_id.'.jpg');
            } 

            //update data 
            $result = $this->driver_model->edit_driver($driver_data, $driver_id);

            if($result != 0) {

                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Driver\'s name: '.$driver_data['first_name'].' '.$driver_data['last_name'].' '.$driver_data['middle_name']. ' updated successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'We could not detect any changes in your submitted form. If you have changed the image, we have processed it accordingly. Kindly close the form if you do not want to edit it further!';
            } 

            echo json_encode($ajax_data);  
        }
    }

    //delete Driver
    function delete_driver($driver_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->driver_model->delete_driver($driver_id);

        if($result == true) {
            //delete the image from the folder
            $image_path = 'uploads/driver_image/'.$driver_id.'.jpg';
            if(file_exists($image_path)) {
                unlink($image_path);
            }

            $data['success'] = true;
            $data['user'] = 'Driver';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Driver could not be deleted because this driver\'s records are found in either loading or expenditure records!';
        }
        
        
        echo json_encode($data);

    }

    //verify driver's birth date
    function verifyBirthDate($date) {
        $now = date('Y-m-d');
        $birthdate = date_format(date_create($date), 'Y-m-d');

        $diff = date_diff(date_create($birthdate), date_create($now));
        $age = $diff->format('%y');

        $eligible = 0;

        if($age > 17) {
            $eligible = 1;
        } 

        echo $eligible;
    }

    //verify driver's employed date
    function verifyEmployedDate($date) {
        $now = strtotime(date('d-m-Y'));
        $employeddate = strtotime(date($date));

        $eligible = 0;

        if($now >= $employeddate) {
            $eligible = 1;
        } 

        echo $eligible;
    } 

    //verify driver's license expiry date
    function verifyLicenseExpiryDate($date) {
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

    //FILTER DRIVER LIST
    function getAllDriversDataTable() {

        $this->driver_model->getAllDriversDataTable();

    }

    //get html form of driver using vehicle id
    function driverSelectionByVehicleId($vehicle_id) {
       echo $this->driver_model->driverSelectionByVehicleId($vehicle_id);
    }

    //print drivers
    function printAllDrivers() {
        
        $page_data['page_title'] = 'Print All Drivers';
        $page_data['page_heading'] = 'LIST OF DRIVERS';
        $page_data['allDrivers'] = $this->driver_model->getAllDrivers()->result_array();
        $this->load->view('backend/admin/pages/vehicle/printAllDrivers.php', $page_data);

    }

}
