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
class Insurance extends CI_Controller {

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

    //add insurance
    function add_insurance() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $insurance_data['date_created'] = strtotime(date('d-m-Y'));
        $insurance_data['date_modified'] = strtotime(date('d-m-Y'));
        $insurance_data['insurance_name'] = strtoupper($this->input->post('insurance_name'));
        $insurance_data['insurance_company'] = strtoupper($this->input->post('insurance_company'));


        //Validate the form inputs
        if (empty($insurance_data['insurance_name'])) {
            $errors['insurance_name'] = 'Insurance Name Required!';
        }

        if (empty($insurance_data['insurance_company'])) {
            $errors['insurance_company'] = 'Insurance Company Required!';
        }

        //Duplicate Validation
        if(!insurance_duplicate($insurance_data['insurance_name'], $insurance_data['insurance_company'], 'add')) {
            //duplicate found so reject the submission
            $errors['insurance_duplicate'] = 'Duplicate found: The same insurance already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->insurance_model->add_insurance($insurance_data);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Insurance name:  '.$insurance_data['insurance_name']. ' from '.$insurance_data['insurance_company'].' added successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
            } 

            echo json_encode($ajax_data);  
        }

    }

    //edit insurance
    function edit_insurance($insurance_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $insurance_data['date_modified'] = strtotime(date('d-m-Y'));
        $insurance_data['insurance_name'] = strtoupper($this->input->post('insurance_name'));
        $insurance_data['insurance_company'] = strtoupper($this->input->post('insurance_company'));


        //Validate the form inputs
        if (empty($insurance_data['insurance_name'])) {
            $errors['insurance_name'] = 'Insurance Name Required!';
        }

        if (empty($insurance_data['insurance_company'])) {
            $errors['insurance_company'] = 'Insurance Company Required!';
        }

        //Duplicate Validation
        if(!insurance_duplicate($insurance_data['insurance_name'], $insurance_data['insurance_company'], 'update')) {
            //duplicate found so reject the submission
            $errors['insurance_duplicate'] = 'Duplicate found: The same insurance already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->insurance_model->edit_insurance($insurance_data, $insurance_id);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Insurance name:  '.$insurance_data['insurance_name']. ' from '.$insurance_data['insurance_company'].' updated successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'We could not detect any changes in the form you submitted. Kindly close this form if you do not want to edit further!';
            } 

            echo json_encode($ajax_data);  
        }

    }

    //delete Insurance
    function delete_insurance($insurance_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->insurance_model->delete_insurance($insurance_id);

        if($result == true) {
            $data['success'] = true;
            $data['user'] = 'Insurance';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Insurance could not be deleted because it has a record with one or more vehicles!';
        }
        
        
        echo json_encode($data);

    }


    //FILTER INSURANCE LIST
    function getAllVehicleInsuranceDataTable() {

        $this->insurance_model->getAllVehicleInsuranceDataTable();

    }

    //verify vehicle insurance expiry date
    function verifyInsuranceExpiryDate($date) {
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

}
