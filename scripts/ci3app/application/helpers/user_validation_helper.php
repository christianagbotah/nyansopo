<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */


//account's full duplication on insert
if (!function_exists('account_name_validation_insert')) {
    function account_name_validation_insert($account_name = '') {

        $ci = &get_instance();
        $num_rows = 0;
        $ci->db->where('account_name', $account_name);
        $num_rows = $ci->db->get('accounts')->num_rows();

        if ($num_rows == 0) {
            return true;
        } else if ($num_rows > 0) {
            return false;
        }

    }
}

//ACCOUNTS DUPLICATE CHECKER
if (!function_exists('account_name_validation_update')) {
    function account_name_validation_update($account_id, $account_name) {
        $ci = &get_instance();
        $num_rows = 0;

        $ci->db->where('account_id !=', $account_id);
        $ci->db->where('account_name', $account_name);
        $num_rows = $ci->db->get('accounts')->num_rows();
        if ($num_rows > 0) {
            return false;
        } else {
            return true;
        }
    }
}

if ( ! function_exists('email_validation'))
{
	function email_validation($email){
		$ci=& get_instance();
		$num_rows = 0;
		$user_array = array('admin', 'teacher', 'parent', 'student', 'accountant', 'librarian');
		$size = sizeof($user_array);

		for($i = 0; $i < $size; $i++){
			$ci->db->where('email', $email);
			$num_rows = $ci->db->get($user_array[$i])->num_rows();
			if($num_rows > 0){
				return 0;
			}
		}
		return 1;
	}
}

if ( ! function_exists('email_validation_for_edit')){
	function email_validation_for_edit($email, $id, $type){
		$num_rows = 0;
		$ci=& get_instance();
		$user_array = array('admin', 'teacher', 'parent', 'student', 'accountant', 'librarian', 'non_teaching_staff');
		$size = sizeof($user_array);
		for($i = 0; $i < $size; $i++){
			if($type == $user_array[$i]){
				// For non_teaching_staff, use staff_id as the primary key
				$id_field = ($user_array[$i] == 'non_teaching_staff') ? 'staff_id' : $user_array[$i].'_id';
				$ci->db->where_not_in($id_field, $id);
				$ci->db->where('email', $email);
				$num_rows = $ci->db->get($user_array[$i])->num_rows();
				if($num_rows == 0){
					return 1;
				}
			}
			else{
				$ci->db->where('email', $email);
				$num_rows = $ci->db->get($user_array[$i])->num_rows();
				if($num_rows > 1){
					return 0;
				}
			}
		}
		return 0;
	}
}

// Section duplication on create
if ( ! function_exists('duplication_of_section_on_create')){
	function duplication_of_section_on_create($class_id, $section_name){
		$ci=& get_instance();
		$num_rows = 0;
		$data = array(
		'class_id' => $class_id,
		'name' => $section_name
		);
		$ci->db->where($data);
		$num_rows = $ci->db->get('section')->num_rows();
		if($num_rows == 0){
			return 1;
		}
		else if($num_rows > 1){
			return 0;
		}
	}
}
// section duplication on edit
if ( ! function_exists('duplication_of_section_on_edit')){
	function duplication_of_section_on_edit($section_id, $class_id, $section_name){
		$ci=& get_instance();
		$num_rows = 0;
		$data = array(
		'class_id' => $class_id,
		'name' => $section_name
		);
		$ci->db->where_not_in('section_id', $section_id);
		$ci->db->where($data);
		$num_rows = $ci->db->get('section')->num_rows();
		if($num_rows == 0){
			return 1;
		}
		else if($num_rows > 1){
			return 0;
		}
	}
}

// class routine duplication on create
if ( ! function_exists('duplication_of_class_routine_on_create')){
	function duplication_of_class_routine_on_create($data){
		$ci=& get_instance();
		$num_rows = 0;
		$ci->db->where($data);
		$num_rows = $ci->db->get('class_routine')->num_rows();
		if($num_rows == 0){
			return 1;
		}
		else if($num_rows > 1){
			return 0;
		}
	}
}

// class routine duplication on edit
if ( ! function_exists('duplication_of_class_routine_on_edit')){
	function duplication_of_class_routine_on_edit($data, $class_routine_id){
		$ci=& get_instance();
		$num_rows = 0;
		$ci->db->where_not_in('class_routine_id', $class_routine_id);
		$ci->db->where($data);
		$num_rows = $ci->db->get('class_routine')->num_rows();
		if($num_rows == 0){
			return 1;
		}
		else if($num_rows > 1){
			return 0;
		}
	}
}

//parent's full duplication on insert
if ( ! function_exists('parent_name_validation_insert')){
    function parent_full_validation_insert($parent_name = '', $phone = ''){
        if($parent_name == '' && $phone == '') {
            return true;
        }else {
            $ci=& get_instance();
            $num_rows = 0;
            $ci->db->where('name', $parent_name);
            $ci->db->where('phone', $phone);
            $num_rows = $ci->db->get('parent')->num_rows();
    
            if($num_rows == 0){
                return true;
            }
            else if($num_rows > 1){
                return false;
            }
        }
        
    }
}

//Teacher's full duplication on insert
if ( ! function_exists('teacher_name_validation_insert')){
    function teacher_name_validation_insert($teacher_name = '', $phone = ''){
        if($teacher_name == '' && $phone == '') {
            return true;
        }else {
            $ci=& get_instance();
            $num_rows = 0;
            $ci->db->where('name', $teacher_name);
            $ci->db->where('phone', $phone);
            $num_rows = $ci->db->get('teacher')->num_rows();
    
            if($num_rows == 0){
                return true;
            }
            else if($num_rows > 1){
                return false;
            }
        }
        
    }
}

//student id duplication on insert
if ( ! function_exists('code_validation_insert')){
    function code_validation_insert($student_code){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('student',array('student_code'=>$student_code))->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 1){
            return false;
        }
    }
}

//teacher code duplication on insert
if ( ! function_exists('teacher_code_validation_insert')){
    function teacher_code_validation_insert($teacher_code){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('teacher',array('teacher_code'=>$teacher_code))->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 1){
            return false;
        }
    }
}

//student authentication_key duplication on insert
if ( ! function_exists('auth_validation_insert')){
    function auth_validation_insert($authentication_key){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('student',array('authentication_key'=>$authentication_key))->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 1){
            return false;
        }
    }
}

//teacher authentication_key duplication on insert
if ( ! function_exists('t_auth_validation_insert')){
    function t_auth_validation_insert($authentication_key){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('teacher',array('authentication_key'=>$authentication_key))->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 1){
            return false;
        }
    }
}

//parent/guardian authentication_key duplication on insert
if ( ! function_exists('g_auth_validation_insert')){
    function g_auth_validation_insert($authentication_key){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('parent',array('authentication_key'=>$authentication_key))->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 1){
            return false;
        }
    }
}

//invoice id duplication on insert
if ( ! function_exists('invoice_code_validation_insert')){
    function invoice_code_validation_insert($invoice_code){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('invoice',array('invoice_code'=>$invoice_code))->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 1){
            return false;
        }
    }
}

//receipt code duplication on insert
if ( ! function_exists('receipt_code_validation_insert')){
    function receipt_code_validation_insert($receipt_code){
        $ci=& get_instance();
        $num_rows = 0;

        $num_rows = $ci->db->get_where('payment',array('receipt_code'=>$receipt_code))->num_rows();
        if($num_rows > 0){
            return false;

        } else {
            return true;
        }
    }
}

//student id duplication in update

if ( ! function_exists('code_validation_update')){
    function code_validation_update($student_code,$student_id){
        $ci=& get_instance();
        $num_rows = 0;
        $ci->db->where('student_id !=', $student_id);
        $ci->db->where('student_code', $student_code);
        $num_rows = $ci->db->get('student')->num_rows();
        if($num_rows == 0){
            return true;
        }
        else if($num_rows > 0){
            return false;
        }

    }
}

// helper to find receivers email from message id
if ( ! function_exists('get_receiver_email')){
    function get_receiver_email($message_id){
        $ci=& get_instance();
        $message_detail = $ci->db->get_where('message', array('message_id' => $message_id))->row();
        $thread_code = $message_detail->message_thread_code;
        $sender = $message_detail->sender;
        $thread_details = $ci->db->get_where('message_thread', array('message_thread_code' => $thread_code))->row();
        if ($sender == $thread_details->sender)
            $receiver = $thread_details->reciever;
        else
            $receiver = $thread_details->sender;

        $receiver = explode('-', $receiver);
        $email = $ci->db->get_where($receiver[0], array($receiver[0].'_id' => $receiver[1]))->row()->email;
        return $email;

    }
}

if ( ! function_exists('show_invoice_items')){
    function show_invoice_items($vi, $itemi = array(), $amounti = array()) {
        for($vi=0; $vi < count($itemi); $vi++) {
            return ' '.$itemi[$vi]. ':'. $amounti[$vi].',';       
        }
    }
}

//converting date format
if ( ! function_exists('date_format_converter')){
    function date_format_converter($date, $format = 'd M Y') {

        $old_date = $date;
        $date_format = $format;

        if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{2}$/", $old_date)) { //***DD-MM-YY
            $new_date =  '<span style="color: red;">This format is ambiguous. 2-digits for all is difficult to differentiate. Please try different format; e.g 25-04-2019!</span>';
        } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{2}$/", $old_date)) { //***DD-MM-YY
            $new_date =  '<span style="color: red;">This format is ambiguous. 2-digits for all is difficult to differentiate. Please try different format; e.g 25/04/2019!</span>';
        } 

        //default formats from the user should be dd/mm/yyyy if both dd and mm are below or equal 12
        else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $old_date)) { //***DD/MM/YYYY
            $old_date_exp = implode('-', explode('/', $old_date));
            $new_date =  date($date_format, strtotime($old_date_exp));
        } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/", $old_date)) { //***DD-MM-YYYY
            $new_date =  date($date_format, strtotime($old_date));
        }

        

        /** - DASH FORMATS **/
        else if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $old_date)) { //***YYYY-MM-DD
            $new_date =  date($date_format, strtotime($old_date));
        } else if (preg_match("/^[0-9]{4}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])$/", $old_date)) { //***YYYY-DD-MM
            $old_date_exp = explode('-', $old_date);
            $y = $old_date_exp[0];
            $d = $old_date_exp[1];
            $m = $old_date_exp[2];
            $new_date = $y.'-'.$m.'-'.$d;
            $new_date =  date($date_format, strtotime($new_date));
        } else if (preg_match("/^[0-9]{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $old_date)) { //***YY-MM-DD
            $new_date =  date($date_format, strtotime($old_date));
        } else if (preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/", $old_date)) { //***MM-DD-YYYY
            $old_date_exp = explode('-', $old_date);
            $m = $old_date_exp[0];
            $d = $old_date_exp[1];
            $y = $old_date_exp[2];
            $new_date = $y.'-'.$m.'-'.$d;
            $new_date =  date($date_format, strtotime($new_date));

        } else if (preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{2}$/", $old_date)) { //***MM-DD-YY
            $old_date_exp = explode('-', $old_date);
            $m = $old_date_exp[0];
            $d = $old_date_exp[1];
            $y = $old_date_exp[2];
            $new_date = $y.'-'.$m.'-'.$d;
            $new_date =  date($date_format, strtotime($new_date));
        } 

        /** / SLASH FORMATS **/
         else if (preg_match("/^[0-9]{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])$/", $old_date)) { //***YYYY/MM/DD
            echo date('d M, Y', strtotime($old_date));
        } else if (preg_match("/^[0-9]{4}\/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])$/", $old_date)) { //***YYYY-DD-MM
            $old_date_exp = explode('/', $old_date);
            $y = $old_date_exp[0];
            $d = $old_date_exp[1];
            $m = $old_date_exp[2];
            $new_date = $y.'/'.$m.'/'.$d;
            $new_date =  date($date_format, strtotime($new_date));
        }else if (preg_match("/^[0-9]{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])$/", $old_date)) { //***YY/MM/DD
            $old_date_exp = implode('-', explode('/', $old_date));
            $new_date =  date($date_format, strtotime($old_date_exp));
        } else if (preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/", $old_date)) { //***MM/DD/YYYY
            $old_date_exp = explode('/', $old_date);
            $m = $old_date_exp[0];
            $d = $old_date_exp[1];
            $y = $old_date_exp[2];
            $new_date = $y.'/'.$m.'/'.$d;
            $new_date =  date($date_format, strtotime($new_date));
        } else if (preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{2}$/", $old_date)) { //***MM/DD/YY
            $old_date_exp = explode('/', $old_date);
            $m = $old_date_exp[0];
            $d = $old_date_exp[1];
            $y = $old_date_exp[2];
            $new_date = '20'.$y.'/'.$m.'/'.$d;
            $new_date =  date($date_format, strtotime($new_date));
        }  else {
            $old_date = date('Y-m-d', strtotime($date));
            $new_date = date($date_format, strtotime($old_date));
            //$new_date = '<span style="color: red;">This type of date is not supported, please try a different one!</span>';
        }

        return $new_date;
    }
}

//getting start and end dates from a week number
if ( ! function_exists('getStartAndEndDate')){

    function getStartAndEndDate($week, $year) {
      $dto = new DateTime();
      $dto->setISODate($year, $week);
      $ret['week_starts'] = $dto->format('d-m-Y');
      $dto->modify('+4 days'); //just for 5 days: Monday - Friday
      $ret['week_ends'] = $dto->format('d-m-Y');
      return $ret;
    }
}

/*Get all classes in order*/
if ( ! function_exists('getAllClassList')){
    function getAllClassList($teacher_id = '') {
        $ci=& get_instance();
        $class_name = [
            'CRECHE',
            'NURSERY',
            'KG',
            'BASIC',
            'JHS'
        ];

        $class_ids_holder = [];

        for($i = 0; $i < sizeof($class_name); $i++) {
            $ci->db->order_by('name_numeric', 'asc');
            if($teacher_id != '') {
                $ci->db->where('teacher_id', $teacher_id);
            }
            $classes = $ci->db->get_where('class', array('name' => $class_name[$i]))->result_array();

            foreach($classes as $c) {
                array_push($class_ids_holder, $c['class_id']);
            }
        }

        return $class_ids_holder;
   
    }
}


/*Get all classes in full*/
if ( ! function_exists('getFullClassList')){
    function getFullClassList($teacher_id = '', $selected_id = '') {
        $ci=& get_instance();


        $class_ids = getAllClassList($teacher_id);
        for($i = 0; $i < sizeof($class_ids); $i++) {

            $class_name = $ci->crud_model->get_class_name($class_ids[$i]);
            $class_name_numeric = $ci->crud_model->get_class_name_numeric($class_ids[$i]);
            

            //adding sections to class names
            //add section A or B if the class has more than one section
            $class_section = $ci->crud_model->get_class_section($class_ids[$i]);
            $class_has_more_sections = $ci->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();

            $sec_name = '';
            //if($class_has_more_sections > 1) {
                $sec_name = $class_section;
            //}

            $class = '';
            // if($class_name == 'CRECHE') {
            //     $class = $class_name.$sec_name;
            // } else {
                $class = $class_name. ' '. $class_name_numeric. ' '. $sec_name;
            //}

            $isSame = false;
            if($selected_id == $class_ids[$i]) {
                $isSame = true;
            }
            
            if($isSame) {
                echo '<option value="'.$class_ids[$i].'" selected>'.$class.'</option>';
            } else {
                echo '<option value="'.$class_ids[$i].'">'.$class.'</option>';
            }
            
           
        }
    }
}


/*Get all students*/
if ( ! function_exists('getAllStudents')){
    function getAllStudents($class_id = '') {
        $ci=& get_instance();

        $allStudents = $ci->crud_model->get_students($class_id);
        foreach($allStudents as $student) {
            $student_detail = $ci->db->get_where('student', ['student_id' => $student['student_id']])->row();
            echo '<option value="'.$student['student_id'].'">'.$student_detail->name.'</option>';
        }
    }
}

/*Get a student's current class by student id*/
if ( ! function_exists('getStudentCurrentClassByStudentId')){
    function getStudentCurrentClassByStudentId($student_id) {
        $ci=& get_instance();

        $class_id = $ci->db->get_where('enroll', ['student_id' => $student_id])->last_row()->class_id;


        $class_name = $ci->crud_model->get_class_name($class_id);
        $class_name_numeric = $ci->crud_model->get_class_name_numeric($class_id);
        $class_section = $ci->crud_model->get_class_section($class_id);

        
        return $class_name. ' '.$class_name_numeric. ' '.$class_section;
           
        
    }
}

/*Get a student's parent data*/
if ( ! function_exists('getStudentParentByStudentId')){
    function getStudentParentByStudentId($student_id) {
        $ci=& get_instance();

        $parent_id = $ci->db->get_where('student', ['student_id' => $student_id])->last_row()->parent_id;


        $parent_data = $ci->db->get_where('parent', ['parent_id' => $parent_id])->row();

        
        return $parent_data;
           
        
    }
}

if ( ! function_exists('numberToWordConverter')){
    function numberToWordConverter($number) {

        
        $ci=& get_instance();

        if (($number < 0) || ($number > 999999999)) {
            throw new Exception("Number is out of range");
        }
        $giga = floor($number / 1000000);
        // Millions (giga)
        $number -= $giga * 1000000;
        $kilo = floor($number / 1000);
        // Thousands (kilo)
        $number -= $kilo * 1000;
        $hecto = floor($number / 100);
        // Hundreds (hecto)
        $number -= $hecto * 100;
        $deca = floor($number / 10);
        // Tens (deca)
        $n = $number % 10;
        // Ones
        $result = "";

        if ($giga) {
            $result .= numberToWordConverter($giga) .  " Million,";
        }
        if ($kilo) {
            $result .= (empty($result) ? "" : " ") .numberToWordConverter($kilo) . " Thousand,";
        }
        if ($hecto) {
            $result .= (empty($result) ? "" : " ") .numberToWordConverter($hecto) . " Hundred";
        }
        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eightteen", "Nineteen");
        $tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");

        

        if ($deca || $n) {
            if (!empty($result)) {
                $result .= " and ";
            }
            if ($deca < 2) {
                $result .= $ones[$deca * 10 + $n];
            } else {
                $result .= $tens[$deca];
                if ($n) {
                    $result .= "-" . $ones[$n];
                }
            }
        }
        if (empty($result)) {
            $result = "zero";
        }
        return $result;
    }
}


if ( ! function_exists('addCurrencyInWords')){
    function addCurrencyInWords($currencyType="", $amount="") {

        $fullAmount = round($amount, 2);

        if( //Ghana Cedis
            $currencyType == 'GHC' || 
            $currencyType == 'GHS' || 
            $currencyType == 'ghc' || 
            $currencyType == 'ghs' || 
            $currencyType == 'GHc' || 
            $currencyType == 'GHs' ||
            $currencyType == '₵' ||
            $currencyType == 'GH₵'
        )
        {
            $majorCurrency = ' Ghana Cedis';
            $minorCurrency = ' Pesewas';
        }

        if(
            $currencyType == '$' || 
            $currencyType == 'usd' || 
            $currencyType == 'USD'
        ) 
        {
            $majorCurrency = ' Dollars';
            $minorCurrency = ' Cents';
        }

        $fullAmountExplode = explode('.', $fullAmount);
        $minorAmount = isset($fullAmountExplode[1]) ? $fullAmountExplode[1] : '00';
        
        // Ensure minor amount is always 2 digits
        $minorAmount = str_pad($minorAmount, 2, '0', STR_PAD_RIGHT);
        $minorAmount = substr($minorAmount, 0, 2); // Take only first 2 digits
        
        $result = $majorCurrency;

        // Always add minor currency part
        $result .= ', ';
        
        $minorAmountInt = intval($minorAmount);
        
        if($minorAmountInt == 0) {
            $result .= 'Zero';
        } else {
            $deca = floor($minorAmountInt / 10);
            $n = $minorAmountInt % 10;

            $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen");
            $tens = array("", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");

            if ($deca < 2) {
                $result .= $ones[$minorAmountInt];
            } else {
                $result .= $tens[$deca];
                if ($n) {
                    $result .= "-" . $ones[$n];
                }
            }
        }

        $result .= $minorCurrency.' Only.';

        return $result;
    }
}

/*Get a student's parent data*/
if ( ! function_exists('validateGhanaPhone')){
    function validateGhanaPhone($receiver_phone) {

        if(strlen($receiver_phone) == 9 && substr($receiver_phone, 0, 1) != '0') {
            $to     = '+233'. substr($receiver_phone[$p], 0, 9);

        } else if(strlen($receiver_phone) == 10 && substr($receiver_phone, 0, 1) == '0') {
            $to     = '+233'. substr($receiver_phone, 1, 9);

        } else if(strlen($receiver_phone) == 12) {
            $to     = '+'. $receiver_phone;

        } else if(strlen($receiver_phone) == 13 && substr($receiver_phone, 3, 1) == 0) {
            $to     = '+233'. substr($receiver_phone, 4, 9);

        } else if(strlen($receiver_phone) == 14 && substr($receiver_phone, 4, 1) == 0 && substr($receiver_phone[$p], 0, 1) == '+') {
            $to     = '+233'. substr($receiver_phone, 5, 9);

        } else if(strlen($receiver_phone) == 13 && substr($receiver_phone, 0, 1) == '+') {
            $to     = $receiver_phone;

        } else {
            $to = false; 
        }

        return $to;
    }
}


/*action button*/
    if ( ! function_exists('get_action_button')){
        function get_action_button() {

            return '<button type="button" data-toggle="dropdown" aria-expanded="false" class="flex items-center justify-center ml-auto text-white bg-blue-700 rounded-full dropdown-toggle w-14 h-14 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:focus:ring-blue-800">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 3">
                            <path d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/>
                        </svg>
                        <span class="sr-only">Open actions menu</span>
                    </button>';
        }
    }

    //explode and join words together (append words)
    //my own functions
    if (!function_exists('append_words')) {
        function append_words($word, $pre_separator) {
            //$pre_separator = '\'' . $pre_separator . '\'';
            $word = explode($pre_separator, $word);

            if (count($word) > 1) {
                $po_code2 = $word[0];

                for ($i = 1; $i < count($word); $i++) {
                    $po_code2 = $po_code2 . ' ' . $word[$i];
                }

                $word = $po_code2;
                return $word;
            } else {
                $word = $word[0];
                return $word;
            }
        }
    }


    /*=================================================================================
    CONNECTION DIAGNOSIS RESUMES
    ==================================================================================*/

    if (!function_exists('getInternetConnectionStatus')) {
        function getInternetConnectionStatus() {
        
            $host_name = 'www.google.com'; // A reliable host to test connectivity
            $port_no = 80; // Standard HTTP port

            // Attempt to open a socket connection
            // The '@' suppresses warnings if the connection fails
            $connection_status = (bool)@fsockopen($host_name, $port_no, $err_no, $err_str, 5); // 5-second timeout

            return $connection_status;
        }
    }

        /*=================================================================================
            CONNECTION DIAGNOSIS ENDS
        ==================================================================================*/


// ------------------------------------------------------------------------
/* End of file User_validation.php */
/* Location: ./system/helpers/User_validation.php */
