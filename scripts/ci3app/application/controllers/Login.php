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
// * Email   : softmail@lisofts.com                                         *
// * Website : https://www.lisofts.com                                      *
// * Support : https://www.support.lisofts.com                              *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * This software is furnished under a license and may be used and copied *
// * only  in  accordance  with  the  terms  of such  license and with the *
// * inclusion of the above copyright notice.                              *
// *                                                                       *
// *************************************************************************

class Login extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        
        /* cache control */
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 2010 05:00:00 GMT");

        //load encryption library
        $this->load->library('encryption');
        $this->load->library('form_validation');
    }

    //Default function, redirects to logged in user area
    public function index() {

        if ($this->session->userdata('admin_login') == 1)
            redirect(site_url('admin/dashboard'), 'refresh');

        if ($this->session->userdata('teacher_login') == 1)
            redirect(site_url('teacher/dashboard'), 'refresh');

        if ($this->session->userdata('student_login') == 1)
            redirect(site_url('student/dashboard'), 'refresh');

        if ($this->session->userdata('parent_login') == 1)
            redirect(site_url('parents/dashboard'), 'refresh');

        if ($this->session->userdata('librarian_login') == 1)
            redirect(site_url('librarian/dashboard'), 'refresh');

        if ($this->session->userdata('accountant_login') == 1)
            redirect(site_url('accountant/dashboard'), 'refresh');

        $this->load->view('backend/login');
    }

    function auth_verification($auth_key) {
      $this->crud_model->auth_verification($auth_key);
    }

    //block account
    function block_account() {
    
      $email_block = $_REQUEST['email_block'];
      $tables = array('admin', 'accountant', 'librarian', 'student', 'parent', 'teacher');
      $rows_counter = 0;
      for($j = 0; $j < sizeof($tables); $j++) {
        $n_block_email = $this->db->get_where($tables[$j], array('email' => $email_block))->num_rows();
        if($n_block_email > 0) {
          $rows_counter++;
        }
      }

      if($rows_counter > 0) {
        //insert 3 into the table column block_limit
        for($i = 0; $i < sizeof($tables); $i++) {
          $this->db->where('email', $email_block);
          $this->db->set('block_limit', 3);
          $this->db->update($tables[$i]);
        }
        echo 1;
      }else {
        echo 0;
      }

    }

    //Validating login from ajax request
    function account_status($account_type, $current_user_id) {

        $account_st = $this->db->get_where($account_type, array($account_type.'_id' => $current_user_id))->row()->block_limit;

        if($account_st == 3) {
          echo 'blocked';
        }
    }

    function validate_login() {

      //validate form

      if($this->input->post('email') != '' || $this->input->post('email') != null) {
        $this->form_validation->set_rules('email', 'Email/Username', 'trim|required');
      }

      /*
      if($this->input->post('username') != '' || $this->input->post('username') != null) {
        $this->form_validation->set_rules('username', 'Username', 'trim|required');
      }*/
      
      $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');

      if($this->form_validation->run() === FALSE) {
        $this->load->view('backend/login');
      }

     
      $username = $this->input->post('username');

      if($username != '' || $username != null) {
         $email = $username;
      } else {
         $email = $this->input->post('email');
      }
      

      //check account status to see if it has been marked as blocked or active
      $tables = array('admin', 'accountant', 'librarian', 'student', 'parent', 'teacher');
      $rows_counter = 0;
      for($j = 0; $j < sizeof($tables); $j++) {
        $block_query = $this->db->get_where($tables[$j], array('email' => $email));

        if($block_query->num_rows() < 1 && $tables[$j] == 'student') { //search through the username field in student table
          $block_query = $this->db->get_where($tables[$j], array('username' => $email));
        }

        if($block_query->num_rows() > 0) {
          $block_row = $block_query->row()->block_limit;
          if($block_row == 3) {
            $rows_counter++;
          }
        }
      }

      if($rows_counter > 0) {
        $page_data['page_info'] = 'blocked';
        $this->load->view('backend/login', $page_data);
      }else {
        $password = $this->input->post('password');
        $auth_key2 = $this->input->post('auth_key2');
        $credential = array('email' => $email, 'authentication_key' => $auth_key2);

        // Checking login credential for admin
        $query = $this->db->get_where('admin', $credential);
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $hash_password = $row->password;
            if(password_verify($password, $hash_password) == 1) {
              $this->session->set_userdata('admin_login', '1');
              $this->session->set_userdata('admin_id', $row->admin_id);
              $this->session->set_userdata('login_user_id', $row->admin_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('login_type', 'admin');

              $this->session->set_userdata('user_login_access', '1');
              $this->session->set_userdata('user_login_id', $row->admin_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('email', $row->email);
              //$this->session->set_userdata('user_image', $row->em_image);
              $this->session->set_userdata('user_type', $row->level);
              $this->session->set_userdata('level', $row->level);
              redirect(site_url('admin/dashboard'), 'refresh');
           }
        }

        // Checking login credential for teacher
        $query = $this->db->get_where('teacher', $credential);
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $hash_password = $row->password;
            if(password_verify($password, $hash_password) == 1) {
              $this->session->set_userdata('teacher_login', '1');
              $this->session->set_userdata('teacher_id', $row->teacher_id);
              $this->session->set_userdata('login_user_id', $row->teacher_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('login_type', 'teacher');
              redirect(site_url('teacher/dashboard'), 'refresh');
            }
        }


        // Checking login credential for student
        $this->db->where('email', $username);
        $this->db->or_where('username', $username);
        $this->db->where('authentication_key', $auth_key2);
        $query = $this->db->get('student');

        if ($query->num_rows() > 0) {
            $row = $query->row();
            $hash_password = $row->password;
            if(password_verify($password, $hash_password) == 1) {
              $this->session->set_userdata('student_login', '1');
              $this->session->set_userdata('student_id', $row->student_id);
              $this->session->set_userdata('login_user_id', $row->student_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('login_type', 'student');
              redirect(site_url('student/dashboard'), 'refresh');
            }
        }

        // Checking login credential for parent
        $query = $this->db->get_where('parent', $credential);
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $hash_password = $row->password;
            if(password_verify($password, $hash_password) == 1) {
              $this->session->set_userdata('parent_login', '1');
              $this->session->set_userdata('parent_id', $row->parent_id);
              $this->session->set_userdata('login_user_id', $row->parent_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('login_type', 'parent');
              redirect(site_url('parents/dashboard'), 'refresh');
            }
        }

        // Checking login credential for librarian
        $query = $this->db->get_where('librarian', $credential);
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $hash_password = $row->password;
            if(password_verify($password, $hash_password) == 1) {
              $this->session->set_userdata('librarian_login', '1');
              $this->session->set_userdata('librarian_id', $row->librarian_id);
              $this->session->set_userdata('login_user_id', $row->librarian_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('login_type', 'librarian');
              redirect(site_url('librarian/dashboard'), 'refresh');
            }
        }

        // Checking login credential for accountant
        $query = $this->db->get_where('accountant', $credential);
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $hash_password = $row->password;
            if(password_verify($password, $hash_password) == 1) {
              $this->session->set_userdata('accountant_login', '1');
              $this->session->set_userdata('accountant_id', $row->accountant_id);
              $this->session->set_userdata('login_user_id', $row->accountant_id);
              $this->session->set_userdata('name', $row->name);
              $this->session->set_userdata('login_type', 'accountant');
              redirect(site_url('accountant/dashboard'), 'refresh');
            }
        }

        $this->session->set_flashdata('login_error', get_phrase('invalid_login'));
        redirect(site_url('login'), 'refresh');
        }
    }

    /*     * *DEFAULT NOT FOUND PAGE**** */

    function four_zero_four() {
        $this->load->view('four_zero_four');
    }

    // PASSWORD RESET BY EMAIL
    function forgot_password()
    {
        $this->load->view('backend/forgot_password');
    }

    function reset_password()
    {
        $counter = 0;

        $email = $this->input->post('email');

        $this->form_validation->set_rules('email', 'Email/Username', 'trim|required');

      if($this->form_validation->run() === FALSE) {
        $this->load->view('backend/forgot_password');
      }
        $reset_account_type     = '';
        $phone_num = array();
        //resetting user password here
        $new_password           =   substr( md5( rand(100000000,20000000000) ) , 0,7);

        $message_sms = 'Your new password is: '.$new_password. '. Please use it to login into your account and feel free to change it.';

        // Checking credential for admin
        $query = $this->db->get_where('admin' , array('email' => $email));
        if ($query->num_rows() > 0)
        {
            $counter++;

            $reset_account_type     =   'admin';
            $this->db->where('email' , $email);
            $this->db->update('admin' , array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
            // send new password to user email
            $this->email_model->password_reset_email($message_sms , $reset_account_type , $email);

            $phone_num[] = $this->db->get_where('admin', ['email' => $email])->row()->phone;
            
            $result = $this->sms_model->send_sms_password_reset($message_sms, $phone_num);
            $this->session->set_userdata('sms_result', $result);

            if($result == 'success') {
                $this->session->set_flashdata('reset_success', get_phrase('please_check_your_email/Message_for_the_new_password'));
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');

            } else {

                $this->session->set_flashdata('reset_success', 'Your new password is: <strong>'.$new_password.'</strong>. We could not send you a message due to insufficient SMS bundle.');
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');
            }
            
        } 

        // Checking credential for student
        $query = $this->db->get_where('student' , array('email' => $email));

        $t_field = 'email';
        if ($query->num_rows() == 0) {
          $query = $this->db->get_where('student' , array('username' => $email));

          $t_field = 'username';
        }
        if ($query->num_rows() > 0)
        {
            $counter++;

            $reset_account_type     =   'student';
            $this->db->where($t_field , $email);
            $this->db->update('student' , array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
            // send new password to user email
            $this->email_model->password_reset_email($message_sms , $reset_account_type , $email);

            $phone_num[] = $this->db->get_where('student', [$t_field => $email])->row()->phone;

            $result = $this->sms_model->send_sms_password_reset($message_sms, $phone_num);
            $this->session->set_userdata('sms_result', $result);

            if($result == 'success') {
                $this->session->set_flashdata('reset_success', get_phrase('please_check_your_email/Message_for_the_new_password'));
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');

            } else {

                $this->session->set_flashdata('reset_success', 'Your new password is: <strong>'.$new_password.'</strong> We could not send you a message due to insufficient SMS bundle.');
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');
            }
        }

        // Checking credential for teacher
        $query = $this->db->get_where('teacher' , array('email' => $email));
        if ($query->num_rows() > 0)
        {
            $counter++;

            $reset_account_type     =   'teacher';
            $this->db->where('email' , $email);
            $this->db->update('teacher' , array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
            // send new password to user email
            $this->email_model->password_reset_email($message_sms , $reset_account_type , $email);

            $phone_num[] = $this->db->get_where('teacher', ['email' => $email])->row()->phone;
            
            $result = $this->sms_model->send_sms_password_reset($message_sms, $phone_num);
            $this->session->set_userdata('sms_result', $result);

            if($result == 'success') {
                $this->session->set_flashdata('reset_success', get_phrase('please_check_your_email/Message_for_the_new_password'));
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');

            } else {

                $this->session->set_flashdata('reset_success', 'Your new password is: <strong>'.$new_password.'</strong> We could not send you a message due to insufficient SMS bundle.');
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');
            }
        }

        // Checking credential for parent
        $query = $this->db->get_where('parent' , array('email' => $email));
        if ($query->num_rows() > 0)
        {
            $counter++;

            $reset_account_type     =   'parent';
            $this->db->where('email' , $email);
            $this->db->update('parent' , array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
            // send new password to user email
            $this->email_model->password_reset_email($message_sms , $reset_account_type , $email);

            $phone_num[] = $this->db->get_where('parent', ['email' => $email])->row()->phone;
            
            $result = $this->sms_model->send_sms_password_reset($message_sms, $phone_num);
            $this->session->set_userdata('sms_result', $result);

            if($result == 'success') {
                $this->session->set_flashdata('reset_success', get_phrase('please_check_your_email/Message_for_the_new_password'));
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');

            } else {

                $this->session->set_flashdata('reset_success', 'Your new password is: <strong>'.$new_password.'</strong> We could not send you a message due to insufficient SMS bundle.');
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');
            }
        }


        // Checking credential for librarian
        $query = $this->db->get_where('librarian' , array('email' => $email));
        if ($query->num_rows() > 0)
        {
            $counter++;

            $reset_account_type     =   'librarian';
            $this->db->where('email' , $email);
            $this->db->update('librarian' , array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
            // send new password to user email
            $this->email_model->password_reset_email($message_sms , $reset_account_type , $email);

            $phone_num[] = $this->db->get_where('librarian', ['email' => $email])->row()->phone;
            
            $result = $this->sms_model->send_sms_password_reset($message_sms, $phone_num);
            $this->session->set_userdata('sms_result', $result);

            if($result == 'success') {
                $this->session->set_flashdata('reset_success', get_phrase('please_check_your_email/Message_for_the_new_password'));
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');

            } else {

                $this->session->set_flashdata('reset_success', 'Your new password is: <strong>'.$new_password.'</strong> We could not send you a message due to insufficient SMS bundle.');
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');
            }
        }

        // Checking credential for accountant
        $query = $this->db->get_where('accountant' , array('email' => $email));
        if ($query->num_rows() > 0)
        {
            $counter++;

            $reset_account_type     =   'accountant';
            $this->db->where('email' , $email);
            $this->db->update('accountant' , array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
            // send new password to user email
            $this->email_model->password_reset_email($message_sms , $reset_account_type , $email);

            $phone_num[] = $this->db->get_where('accountant', ['email' => $email])->row()->phone;
            
            $result = $this->sms_model->send_sms_password_reset($message_sms, $phone_num);
            $this->session->set_userdata('sms_result', $result);

            if($result == 'success') {
                $this->session->set_flashdata('reset_success', get_phrase('please_check_your_email/Message_for_the_new_password'));
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');

            } else {

                $this->session->set_flashdata('reset_success', 'Your new password is: <strong>'.$new_password.'</strong> We could not send you a message due to insufficient SMS bundle.');
                redirect(site_url('login/forgot_password?succ=1'), 'refresh');
            }
        }

        if($counter == 0) {

             $this->session->set_flashdata('reset_fail', get_phrase('sorry,_your_email_was_not_found_in_our_system!'));
            redirect(site_url('login/forgot_password?err=1'), 'refresh');

        }


    }

    /*     * *****LOGOUT FUNCTION ****** */

    function logout() {
        $this->session->sess_destroy();
        $this->session->set_flashdata('logout_notification', 'logged_out');
        
        redirect(site_url('login'), 'refresh');
        $this->session->sess_destroy();
    }

}
