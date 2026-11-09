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
class Department extends CI_Controller {

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

    //add department
    function add_department() {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $department_data['date_created'] = strtotime(date('d-m-Y'));
        $department_data['date_modified'] = strtotime(date('d-m-Y'));
        $department_data['department_name'] = strtoupper($this->input->post('department_name'));

        //Validate the form inputs
        if (empty($department_data['department_name'])) {
            $errors['department_name'] = 'Department Name Required!';
        }

        //Duplicate Validation
        if(!department_duplicate($department_data['department_name'], 'add')) {
            //duplicate found so reject the submission
            $errors['department_name_duplicate'] = 'Duplicate found: The same department already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //insert data
            $result = $this->department_model->add_department($department_data);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Department:  '.$department_data['department_name']. ' added successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'Sorry, we are unable to process your submitted data. Please check your entries and try again!';
            } 

            echo json_encode($ajax_data);  
        }

    }

    //edit department
    function edit_department($department_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        //declare some arrays to handle the data
        $errors = array();
        $ajax_data = array();

        //get the inputs from the form
        $department_data['date_modified'] = strtotime(date('d-m-Y'));
        $department_data['department_name'] = strtoupper($this->input->post('department_name'));

        //Validate the form inputs
        if (empty($department_data['department_name'])) {
            $errors['department_name'] = 'Department Name Required!';
        }

        //Duplicate Validation
        if(!department_duplicate($department_data['department_name'], 'update')) {
            //duplicate found so reject the submission
            $errors['department_name_duplicate'] = 'Duplicate found: The same department already exists!';
        }

        //let's echo the errors now if any, else, we go ahead with our data processing
        if(!empty($errors)) { //we have some errors
            echo json_encode($errors); //send it back to the page

        } else { //bravo! No errors found... go ahead--->
            //update data
            $result = $this->department_model->edit_department($department_data, $department_id);

            if($result != 0) {
                //prepare feedback - success
                $ajax_data['success'] = true;
                $ajax_data['message'] = 'Department:  '.$department_data['department_name']. ' updated successfully!';
            } else {
                //prepare feedback - fail
                $ajax_data['message'] = 'We could not detect any changes to your submitted form. Kindly close this form if you do not want to edit further!';
            } 

            echo json_encode($ajax_data);  
        }

    }

    //delete Department
    function delete_department($department_id) {
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }

        $result = $this->department_model->delete_department($department_id);

        if($result == true) {
            $data['success'] = true;
            $data['user'] = 'Department';
        } else {
            $data['success'] = false;
            $data['err_message'] = 'Department could not be deleted because it is used in either Vehicle or Driver records!';
        }
        
        
        echo json_encode($data);

    }

    //FILTER DEPARTMENT LIST
    function getAllDepartmentsDataTable() {

        $this->department_model->getAllDepartmentsDataTable();

    }

}
