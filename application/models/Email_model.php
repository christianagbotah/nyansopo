<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Email_model extends CI_Model {

	function __construct()
    {
        parent::__construct();
    }

	function account_opening_email($account_type = '' , $email = '', $password = '', $auth_key = '', $id = '', $username = '')
	{
		$system_name	=	$this->db->get_where('settings' , array('type' => 'system_name'))->row()->description;

		$email_msg		=	"Welcome to ".$system_name.".<br />";
		$email_msg		.=	"Your account type: ".$account_type.".<br />";
		$email_msg		.=	"Your login username: ". $email .",<br />";
		$email_msg		.=	"Your login password: ". $password .",<br />";
		$email_msg		.=	"Your authentication Key: ". $auth_key .",<br />";
		
		if($id != '' || $id != null) {
		    if($account_type == 'student') {
		       $email_msg		.=	"Your login username: ". $username .",<br />";
		       $email_msg .= "Your Student ID: ". $id .",<br />"; 
		    } else if($account_type == 'teacher') {
		       $email_msg .= "Your Staff ID: ". $id .",<br />"; 
		    }
		}

		$email_msg		.=	"Kindly change your password on your first login.<br />";
		$email_msg		.=	"Login Here: ".base_url()."<br />";

		$email_sub		=	"Account opening email";
		$email_to		=	$email;

		$this->do_email($email_msg , $email_sub , $email_to);
	}

	function password_reset_email($new_password = '' , $account_type = '' , $email = '')
	{
		$query			=	$this->db->get_where($account_type , array('email' => $email));
		if($query->num_rows() > 0)
		{

			$name = ucwords(strtolower($query->row()->name));
			$email_msg	=	"Dear ". $name.", your password reset request has been successful.<br />";
			$email_msg	.=	"Your new password is : <strong>".$new_password."</strong><br />";

			$email_sub	=	"Password reset request";
			$email_to	=	$email;
			$this->do_email($email_msg , $email_sub , $email_to);
			return true;
		}
		else
		{
			return false;
		}
	}

	function password_changed_email($new_password = '', $account_type = '' , $email = '')
	{
		$query			=	$this->db->get_where($account_type , array('email' => $email));
		if($query->num_rows() > 0)
		{
			$name = ucwords(strtolower($query->row()->name));
			$email_msg	=	"Dear ". $name.", your password change request has been successful.<br />";
			$email_msg	.=	"Your new password is : <strong>".$new_password."</strong><br />";

			$email_sub	=	"Password change request";
			$email_to	=	$email;
			$this->do_email($email_msg , $email_sub , $email_to);
			return true;
		}
		else
		{
			return false;
		}
	}

	function contact_message_email($email_from, $email_to, $email_message) {
		$email_sub = "Message from School Software";
		$this->do_email($email_message, $email_sub, $email_to, $email_from);
	}

    function personal_message_email($email_from, $email_to, $email_message) {
        $email_sub = "Message from School Software";
        $this->do_email($email_message, $email_sub, $email_to, $email_from);
    }

	/***custom email sender****/
	function do_email($msg=NULL, $sub=NULL, $to=NULL, $from=NULL)
	{
		$system_name	=	$this->db->get_where('settings' , array('type' => 'system_name'))->row()->description;

		$config = array();
        $config['useragent']	= $system_name;
        $config['mailpath']		= "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"
        $config['protocol']		= "sendmail";
        $config['smtp_host']	= "lightworldtech.com";
        $config['smtp_user']    =  $from;
        $config['smtp_pass']    = "~Myjesus4me2018";
        $config['smtp_port']	=  465;
        $config['smtp_crypto'] = 'ssl';
        $config['smtp_timeout']	= "";
        $config['mailtype']		= "html";
        $config['charset']		= "utf-8";
        $config['newline']		= "\r\n";
        $config['wordwrap']		= TRUE;
        $config['validate']     = FALSE;

        $this->load->library('email');

        $this->email->initialize($config);

		if($from == NULL)
			$from		=	$this->db->get_where('settings' , array('type' => 'system_email'))->row()->description;

		//$this->email->from($from, $system_name);
		$this->email->from($from, $system_name);
		$this->email->to($to);
		$this->email->subject($sub);
		
		$login_ad = '<h5>For more details, please login '.anchor(site_url('login'), '<u>here</u>') .'.</h5>';

		$msg	=	$msg."<center><hr>". $login_ad."<a href=\"https://www.lightworldtech.com\"> Powered By: Lightworldtech</a></center>";
		$this->email->message($msg);

		$this->email->send();

		//echo $this->email->print_debugger();
	}
}
