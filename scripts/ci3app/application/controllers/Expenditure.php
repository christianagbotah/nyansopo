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
class Expenditure extends CI_Controller {

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

    //add expenditure
    function add_expenditure() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $expenditure_data['date_created'] = strtotime(date('d-m-Y'));
        $expenditure_data['date_modified'] = strtotime(date('d-m-Y'));
        $expenditure_data['expenditure_name'] = strtoupper($this->input->post('expenditure_name'));
        if($expenditure_data['expenditure_name'] == 'FUEL') {
            $expenditure_data['fuel_type'] = strtoupper($this->input->post('fuel_type'));
        }
        
        $expenditure_data['expenditure_amount'] = $this->input->post('expenditure_amount');
        $expenditure_data['expenditure_type'] = $this->input->post('expenditure_type');
        $expenditure_data['expenditure_date'] = strtotime($this->input->post('expenditure_date'));
        $expenditure_data['vehicle_id'] = $this->input->post('vehicle_expenditure_add');
        $expenditure_data['driver_id'] = $this->input->post('driver_expenditure_add');
        $expenditure_data['account_id'] = $this->input->post('account');

        //Validate the form inputs
        if (empty($expenditure_data['expenditure_name'])) {
            $errors['expenditure_name'] = 'Expenditure Name Required!';
        }

        if (empty($expenditure_data['expenditure_type'])) {
            $errors['expenditure_type'] = 'Expenditure Type Required!';
        }

        if (empty($expenditure_data['expenditure_amount'])) {
            $errors['expenditure_amount'] = 'Amount Required!';
        }

        if (!is_numeric($expenditure_data['expenditure_amount'])) {
            $errors['expenditure_amount_numeric'] = 'Expenditure Amount Must Be A Number!';
        }

        if (empty($expenditure_data['expenditure_date'])) {
            $errors['expenditure_date'] = 'Date Required!';
        }

        if (empty($expenditure_data['vehicle_id'])) {
            $errors['vehicle'] = 'Vehicle Required!';
        }

        if (empty($expenditure_data['driver_id'])) {
            $errors['driver'] = 'Driver Required!';
        }

        if (empty($expenditure_data['account_id'])) {
            $errors['account_id'] = 'You must select a account!';
        }


        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->expenditure_model->add_expenditure($expenditure_data);

            if($result == 'insufficient balance') { 
                //insufficient fund in the account used for the payment
                $errors['insufficient_balance'] = 'You do not have sufficient balance in the selected account for this expenditure. Try again using a different account!';
                echo json_encode($errors); //send it back to the page

            } else {

                if($result != 0) {

                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Expenditure: '.$expenditure_data['expenditure_name']. ' added successfully!';
                } else {
                    //prepare feedback - fail
                    $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
                }
             
            echo json_encode($ajax_data);  
            }
        }
    }


    //Edit expenditure
    function edit_expenditure($expenditure_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $expenditure_data['date_modified'] = strtotime(date('d-m-Y'));
        $expenditure_data['expenditure_name'] = strtoupper($this->input->post('expenditure_name'));
        if($expenditure_data['expenditure_name'] == 'FUEL') {
            $expenditure_data['fuel_type'] = strtoupper($this->input->post('fuel_type'));
        }
        $expenditure_data['expenditure_amount'] = $this->input->post('expenditure_amount');
        $expenditure_data['expenditure_type'] = $this->input->post('expenditure_type_edit');
        $expenditure_data['expenditure_date'] = strtotime($this->input->post('expenditure_date'));
        $expenditure_data['vehicle_id'] = $this->input->post('vehicle_expenditure_edit');
        $expenditure_data['driver_id'] = $this->input->post('driver_expenditure_edit');
        $expenditure_data['account_id'] = $this->input->post('account');

        //Validate the form inputs
        if (empty($expenditure_data['expenditure_name'])) {
            $errors['expenditure_name'] = 'Expenditure Name Required!';
        }

        if (empty($expenditure_data['expenditure_type'])) {
            $errors['expenditure_type'] = 'Expenditure Type Required!';
        }
        

        if (empty($expenditure_data['expenditure_amount'])) {
            $errors['expenditure_amount'] = 'Amount Required!';
        }

        if (!is_numeric($expenditure_data['expenditure_amount'])) {
            $errors['expenditure_amount_numeric'] = 'Expenditure Amount Must Be A Number!';
        }

        if (empty($expenditure_data['expenditure_date'])) {
            $errors['expenditure_date'] = 'Date Required!';
        }

        if (empty($expenditure_data['vehicle_id'])) {
            $errors['vehicle'] = 'Vehicle Required!';
        }

        if (empty($expenditure_data['driver_id'])) {
            $errors['driver'] = 'Driver Required!';
        }

        if (empty($expenditure_data['account_id'])) {
            $errors['account_id'] = 'You must select a account!';
        }


        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->expenditure_model->edit_expenditure($expenditure_data, $expenditure_id);

            if($result == 'insufficient balance') { 
                //insufficient fund in the account used for the payment
                $errors['insufficient_balance'] = 'You do not have sufficient balance in the selected account for this expenditure. Try again using a different account!';
                echo json_encode($errors); //send it back to the page

            } else {

                if($result != 0) {

                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Expenditure: '.$expenditure_data['expenditure_name']. ' updated successfully!';
                } else {
                    //prepare feedback - fail
                    $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
                }
             
            echo json_encode($ajax_data);  
            }
        }
    }


    //delete Expenditure
    function delete_expenditure($expenditure_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->expenditure_model->delete_expenditure($expenditure_id);

        if($result == true) {
            $data['success'] = true;
            $data['user'] = 'Expenditure';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Expenditure could not be deleted! Please try again.';
        }
        
        
        echo json_encode($data);

    }

    
    //FILTER EXPENDITURE LIST
    function getAllExpendituresDataTable($from = '', $to = '', $type = '', $vehicle = '', $driver = '') {

        $this->expenditure_model->getAllExpendituresDataTable($from, $to, $type, $vehicle, $driver);

    }

    //print
    function printAllExpenditure($from='', $to='', $type='', $vehicle = '', $driver = '') {

        $page_data['from']  = $from;
        $page_data['to']  = $to;
        $page_data['type']  = $type;
        $page_data['vehicle']  = $vehicle;
        $page_data['driver']  = $driver;

        if($vehicle != 0 || $driver != 0) {
            if($vehicle == 0) {

                $page_data['page_title'] = get_phrase(strtoupper($type).' EXPENDITURE REPORT FOR '. strtoupper($this->driver_model->getDriverNameById($driver)));
                $page_data['page_heading'] = get_phrase(strtoupper($type).' EXPENDITURE REPORT FOR '. strtoupper($this->driver_model->getDriverNameById($driver)));

            } else {
                $page_data['page_title'] = get_phrase(strtoupper($type).' EXPENDITURE REPORT FOR '. strtoupper($this->vehicle_model->getVehicleRegistrationNumberById($vehicle)));
                $page_data['page_heading'] = get_phrase(strtoupper($type).' EXPENDITURE REPORT FOR '. strtoupper($this->vehicle_model->getVehicleRegistrationNumberById($vehicle)));
            }
        } else {
            $page_data['page_title'] = get_phrase(strtoupper($type).' EXPENDITURE REPORT FOR ALL TRUCKS');
            $page_data['page_heading'] = get_phrase(strtoupper($type).' EXPENDITURE REPORT FOR ALL TRUCKS');
        }

        

        if($type == '0') {

            if($vehicle != 0 || $driver != 0) {

                if($vehicle == 0) {
                    $page_data['page_title'] = get_phrase('ALL EXPENDITURE REPORT FOR '. strtoupper($this->driver_model->getDriverNameById($driver)));
                    $page_data['page_heading'] = get_phrase('ALL EXPENDITURE REPORT FOR '. strtoupper($this->driver_model->getDriverNameById($driver)));
                } else {
                    $page_data['page_title'] = get_phrase('ALL EXPENDITURE REPORT FOR '. strtoupper($this->vehicle_model->getVehicleRegistrationNumberById($vehicle)));
                    $page_data['page_heading'] = get_phrase('ALL EXPENDITURE REPORT FOR '. strtoupper($this->vehicle_model->getVehicleRegistrationNumberById($vehicle)));
                }
                
            } else {
                $page_data['page_title'] = get_phrase('ALL EXPENDITURE REPORT');
                $page_data['page_heading'] = get_phrase('ALL EXPENDITURE REPORT');
            }
        }

        $this->load->view('backend/admin/pages/vehicle/printExpenditure.php', $page_data);
    }

    

}
