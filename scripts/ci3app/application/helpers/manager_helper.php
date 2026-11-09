<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


if ( ! function_exists('manager'))
{
    function manager($total_rows, $per_page_item) {
    $config['per_page']        = $per_page_item;
    $config['num_links']       = 2;
    $config['total_rows']      = $total_rows;
    $config['full_tag_open']   = '<ul class="pagination justify-content-end">';
    $config['full_tag_close']  = '</ul>';
    $config['prev_link']       = '<span class="page-link">Previous</span>';
    $config['prev_tag_open']   = '<li class="page-item">';
    $config['prev_tag_close']  = '</li>';
    $config['next_link']       = '<span class="page-link">Next</span>';
    $config['next_tag_open']   = '<li class="page-item">';
    $config['next_tag_close']  = '</li>';
    $config['cur_tag_open']    = '<li class="page-item active"><span class="page-link">';
    $config['cur_tag_close']   = '<span class="sr-only">(current)</span></span></li>';
    $config['num_tag_open']    = '<li class="page-item"><span class="page-link">';
    $config['num_tag_close']   = '</span></li>';
    // $config['first_tag_open']  = '<span class="page-link">';
    // $config['first_tag_close'] = '</span>';
    // $config['last_tag_open']   = '<span class="page-link">';
    // $config['last_tag_close']  = '</span>';
    // $config['first_link']      = 'First';
    // $config['last_link']       = 'Last';
        $config['first_link'] = false;
        $config['last_link'] = false;
    return $config;
  }
}


if ( ! function_exists('get_settings'))
{

    function get_settings($type)
    {
        $CI = &get_instance();
        $CI->load->database();
        $result = $CI->db->get_where('settings', array('type' => $type))->row();
        
        if ($result && isset($result->description)) {
            return $result->description;
        }
        
        return '';
    }
}

if ( ! function_exists('year_term_exist'))
{

    function year_term_exist($year = '', $term = '')
    {

        $CI = &get_instance();
        $CI->load->database();
        $CI->db->where('year', $year);
        $CI->db->where('term', $term);
        $query = $CI->db->get('enroll')->num_rows();

        return $query;
    }
}

//populate the academic years
if ( ! function_exists('populate_academic_year'))
{

    function populate_academic_year($isHeader = '', $year = '')
    {
        $running_year = get_settings('running_year');

        $CI = &get_instance();
        $CI->load->database();

        $CI->db->order_by('enroll_id');
        $CI->db->limit(1);
        $first_row = $CI->db->get('enroll')->first_row();
        
        if (!$first_row || !isset($first_row->year)) {
            return;
        }
        
        $first_year = $first_row->year;

        $explode_first_year = explode('-', $first_year);
        $base_year1 = $explode_first_year[0];
        $base_year2 = $explode_first_year[1];
        $this_year = date('Y', strtotime('now'));
        $diffr = $this_year - $base_year2;

       

        if($isHeader == 'yes') {
            
            $diffr = $diffr + 3;
        } else {

            //don't display the next year
             $diffr = $diffr + 2;
        }
        
        // Build years array first
        $years = array();
        $year1 = $base_year1;
        $year2 = $base_year2;
        
        for($i = 1; $i < $diffr; $i++) {
            $years[] = array(
                'year1' => $year1,
                'year2' => $year2,
                'full_y' => $year1.'-'.$year2
            );
            $year1 += 1;
            $year2 += 1;
        }
        
        // Reverse array for descending order
        $years = array_reverse($years);
        
        // Output options in descending order
        foreach($years as $y) {
            $selected = '';
            if($year != '') {
                if($year == $y['full_y']) {
                    $selected = 'selected';
                }
            } else {
                if($running_year == $y['full_y']) {
                    $selected = 'selected';
                }
            }
            
            echo '<option value="'.$y['year1'].'-'.$y['year2'].'"'.$selected.'>'.$y['year1'].'-'.$y['year2'].'</option>';
        }

    }
}

if ( ! function_exists('getFullClassName'))
{

    function getFullClassName($class_id = '', $section_id = '')
    {

        $ci = &get_instance();


        $class_name = $ci->crud_model->get_class_name($class_id);
        $class_name_numeric = $ci->crud_model->get_class_name_numeric($class_id);
        

        //adding sections to class names
        //add section A or B if the class has more than one section
        $class_section = $ci->crud_model->get_class_section($class_id);
        $class_has_more_sections = $ci->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();

        $sec_name = '';
        //if($class_has_more_sections > 1) {
            $sec_name = $class_section;
        //}

        $class = '';
        /*if($class_name == 'CRECHE') {
            $class = $class_name.$sec_name;
        } else {
            $class = $class_name. ' '. $class_name_numeric.$sec_name;
        }*/

        $class = $class_name. ' '. $class_name_numeric. ' '.$sec_name;

        return $class;
    }

    
}


if(!function_exists('getArchivedTermStartsEndsDate')) {

    ////////
    //term ending and starting row
    //////////////
    function getArchivedTermStartsEndsDate($term, $year) {
        $CI = &get_instance();
        $CI->load->database();

        $CI->db->where('term', $term);
        $CI->db->where('year', $year);
        $row = $CI->db->get('terms')->last_row();

        return $row;
    }

}

if ( ! function_exists('getAdminPermissions'))
{

    function getAdminPermissions($admin_level, $field)
    {

        $ci = &get_instance();

        if($admin_level == 1) {

            $adminPermissions = 1;
            //this is super admin, so grant all privileges
        } else {

            $adminPermissions = $ci->db->get_where('user_permission', ['user_type' => 'admin', 'user_level' => '2', 'permission_title' => $field])->row()->permission_status;
        }

        

        return $adminPermissions;

    }
}


if ( ! function_exists('getTeacherPermissions'))
{

    function getTeacherPermissions($field)
    {
        $ci = &get_instance();

        $teacherPermissions = $ci->db->get_where('user_permission', ['user_type' => 'teacher', 'permission_title' => $field])->row()->permission_status;

        return $teacherPermissions;

    }
}


if ( ! function_exists('cleanStrings'))
{
    function cleanStrings($string) {
       $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
       $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

       return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }
}

/*FOR ATTENDANCE STATUS*/
if(!function_exists('getAttendanceStatusCode')) {
    function getAttendanceStatusCode($num) {
        
        switch($num) {
            case 1:
                return 'P';
                break;
                
            case 2:
                return 'A';
                break;
            
            case 3:
                return 'L';
                break;
            
            case 4:
                return 'S';
                break;
                
            case 5:
                return 'C';
                break;
            
            case 6:
                return 'E';
                break;
                
            default:
                return '-';
                break;
        }
    }
}

if(!function_exists('getAttendanceStatusPhrase')) {
    
    function getAttendanceStatusPhrase($num) {
        
        switch($num) {
            case 1:
                return 'Present';
                break;
                
            case 2:
                return 'Absent';
                break;
            
            case 3:
                return 'Late';
                break;
            
            case 4:
                return 'Sick-Home';
                break;
                
            case 5:
                return 'Sick-Clinic';
                break;
            
            case 6:
                return 'Excused';
                break;
                
            default:
                return '-';
                break;
        }
    }
}

if(!function_exists('getMonthInWords')) {
    
    function getMonthInWords($num) {
        
        switch($num) {
            case '01':
                return 'January';
                break;
                
            case '02':
                return 'February';
                break;
            
            case '03':
                return 'March';
                break;
            
            case '04':
                return 'April';
                break;
                
            case '05':
                return 'May';
                break;

            case '06':
                return 'June';
                break;

            case '07':
                return 'July';
                break;

            case '08':
                return 'August';
                break;

            case '09':
                return 'September';
                break;

            case '10':
                return 'October';
                break;

            case '11':
                return 'November';
                break;

            case '12':
                return 'December';
                break;
                
            default:
                return '';
                break;
        }
    }
}

if ( ! function_exists('getStudentAttendancePresentDays'))
{
    function getStudentAttendancePresentDays($student_id, $term, $year) {
        $ci = &get_instance();
        
        $ci->db->where('student_id', $student_id);
        $ci->db->where('term', $term);
        $ci->db->where('year', $year);
        $ci->db->where('status', '1');
        $count = $ci->db->count_all_results('attendance');
        
        return $count;
    }
}


                                    

/**
 * Get conduct item name by ID or return text if legacy
 * Supports backward compatibility for old text-based conducts
 * 
 * @param mixed $value - Can be numeric ID (new system) or text (legacy)
 * @return string - Conduct item name
 */
if ( ! function_exists('get_conduct_name'))
{
    function get_conduct_name($value)
    {
        if(empty($value)) {
            return '';
        }
        
        // If numeric, it's an ID - lookup from database
        if(is_numeric($value)) {
            $CI = &get_instance();
            $CI->load->model('Conduct_items_model');
            $name = $CI->Conduct_items_model->get_name_by_id($value);
            return $name ? $name : ''; // Return empty if ID not found (deleted item)
        }
        
        // Otherwise it's legacy text - return as is
        return $value;
    }
}

if ( ! function_exists('get_conduct_display_value'))
{
    /**
     * Get conduct display value
     * If the value is numeric, fetch the name from conduct_items table
     * Otherwise, return the text as-is
     * 
     * @param string $conduct_value The conduct value (could be ID or text)
     * @return string The display value
     */
    function get_conduct_display_value($conduct_value)
    {
        // Return empty if value is null or empty
        if ($conduct_value === null || $conduct_value === '' || $conduct_value === 'Null') {
            return '';
        }
        
        // Trim the value
        $conduct_value = trim($conduct_value);
        
        // Check if the value is numeric (is an ID)
        if (is_numeric($conduct_value)) {
            // Fetch the name from conduct_items table
            $CI = &get_instance();
            $CI->load->database();
            
            $conduct_item = $CI->db->get_where('conduct_items', array('id' => $conduct_value))->row();
            
            if ($conduct_item && isset($conduct_item->name)) {
                return $conduct_item->name;
            }
            
            // If ID not found, return empty
            return '';
        }
        
        // If not numeric, return the text as-is
        return $conduct_value;
    }
}
