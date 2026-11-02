<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');


// *************************************************************************
// *                                                                       *
// * Lisofts School Manager                                                 *
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


class Api extends CI_Controller
{
    function __construct()
    {
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

        
    }

    /***default function, redirects to login page if no admin logged in yet***/

    public function addUser()
    {
       $table = 'flutter_user';

       $json = json_decode(file_get_contents('php://input'));

       $data['flutter_name'] = $json->fullName;
       $data['flutter_phone'] = $json->phone;
       $data['flutter_email'] = $json->email;
       $data['flutter_password'] = $json->password;


       $duplicate = $this->db->get_where('flutter_user', array('flutter_phone' => $data['flutter_phone']))->num_rows();
       if($duplicate > 0) {
        //duplicate found
            $response['validate'] = 'duplicate';
            $response['message'] = 'User '.$data['flutter_name'].' already exists';
       } else {
           //insert
           $this->db->insert($table, $data);

           if($this->db->affected_rows()) {
            $response['validate'] = 'success';
            $response['message'] = 'User '.$data['flutter_name'].' added successfully';
           } else {
            $response['validate'] = 'failed';
            $response['message'] = 'User adding failed';
           }
       }
       

       echo json_encode($response);
       
    }

    //log user in
    function authenticateUser() {
        $table = 'flutter_user';

        $json = json_decode(file_get_contents('php://input'));

        $login['flutter_email'] = $json->userName;
        $login['flutter_password'] = $json->password;

        $response['email'] = $json->username;
        $response['password'] = $json->password;
        //query
        $user_name_query = $this->db->get_where($table, array('flutter_email' => $login['flutter_email']));
        $password_query = $this->db->get_where($table, array('flutter_password' => $login['flutter_password'], 'flutter_email' => $login['flutter_email']));


        if($password_query->num_rows() > 0 && $user_name_query->num_rows() > 0) {
            $row = $user_name_query->row();
            $user_id = $row->id;
            $user_name = $row->flutter_name;

            //set sessions
            $this->session->set_userdata('admin_login', '1');
            $this->session->set_userdata('admin_id', $user_id);
            $this->session->set_userdata('login_user_id',  $user_id);
            $this->session->set_userdata('name', $user_name);
            $this->session->set_userdata('login_type', 'admin');

            $response['email'] = $json->userName;

            $response['msg'] = 'success';
        } else {

            if($password_query->num_rows() < 1 && $user_name_query->num_rows() < 1) {
                $response['msg'] = 'failed';

            } else {
                
                if($user_name_query->num_rows() < 1) {
                    $response['msg'] = 'username failed';
                }

                if($password_query->num_rows() < 1) {
                    $response['msg'] = 'password failed';
                }

            }
            
            
        }

        //return json to the caller
        echo json_encode($response);

    }

    //View user
    function viewUser($user_id = '') {
      $table = 'flutter_user';
      $data = array();

      if($user_id == '') {
        //we want all
        $users = $this->db->get($table)->result_array();


      } else {
        //we want a specific user by id
        $users = $this->db->get_where($table, array('flutter_id' => $user_id))->result();
        
      }

      echo json_encode($users);
    }
    
    //all regions in Ghana on map
    function getAllRegionsInGhana() {
        $file = 'uploads/regions_in_ghana.json';
        $file_content = file_get_contents($file);

        echo $file_content;
    }

    function students_list() {
        $table = 'student';

        $this->db->select('student_code, name, sex, phone');
        $this->db->limit(20);
        $array = $this->db->get($table)->result();
         echo json_encode($array);
    }

    function testCronjobs() {

        //invoice, payment,student,enroll, benefit_category,bill_item,bill_items_history,boarding_bed,boarding_dormitory,boarding_house,parent,

        /*do something here*/
        $data['flutter_name'] = 'Christian'.rand(1, 999);
        $data['flutter_phone'] = '024361818'.rand(1, 99);
        $data['flutter_email'] = 'christian'.rand(1, 99).'@gmail.com';
        $data['flutter_password'] = mt_rand(1, 99999999);

        //$this->db->insert('flutter_user', $data);

        //$remoteDB = $this->load->database('remote_db', TRUE);
        /*do something here*/
        //$remoteDB->insert('flutter_user', $data);


    }
    
}