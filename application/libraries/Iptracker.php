<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*********************************************************************
 * File: IP Tracker for CodeIgniter Built Website
 * Description: Tracks the number of website visits everyday. 
 * Version: 1.0.0
 * Date Created: January 09, 2015
 * Author: Glenn Tan Gevero
 * Website: http://app-arsenal.com
 * File: IP Tracker Library File
 
 Change line 48 to the name of your db table.
 
 DB Table name must consist the following columns.
 
 `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  `page_view` text,
  `date` int(11) NOT NULL,
  
How to use: Simply load the library in application/config/autoload.php
under autload library line.
**********************************************************************/

class Iptracker{
		
	private $sys = null;
	
	public function __construct(){

		$this->sys	=& get_instance();	
	    $this->sys->load->database();
	}
	
	public static function get_ip_address(){		
        $ip = getenv('HTTP_CLIENT_IP')?:
        getenv('HTTP_X_FORWARDED_FOR')?:
        getenv('HTTP_X_FORWARDED')?:
        getenv('HTTP_FORWARDED_FOR')?:
        getenv('HTTP_FORWARDED')?:
        getenv('REMOTE_ADDR');
        
        return $ip;
	}
	
	public static function get_page_visit(){
		return current_url();
	}
	
	public function save_site_visit(){
		$ip 	= self::get_ip_address();
		$page	= self::get_page_visit();		
		$seg = explode("-", $page);
		
		if(!in_array('dimiyo', $seg)){			
			$data = array(
				'ip'		=> $ip,
				'page_view'	=> $page,
				'date'		=> time()
			);
			
			$ip_found = $this->sys->db->get_where('visitor_tracker', array('ip'=> $ip))->num_rows();
			if($ip_found == 0) {
			   $this->sys->db->insert('visitor_tracker', $data); 
			}
						
		}
	}
}

$tracker = new Iptracker();
$tracker->save_site_visit();
