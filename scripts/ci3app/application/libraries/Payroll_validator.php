<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll Validator Library
 * 
 * Centralized input validation for all payroll operations.
 * Implements comprehensive validation rules to prevent SQL injection,
 * data corruption, and business logic violations.
 * 
 * @package    SchoolManager
 * @subpackage Libraries
 * @category   Validation
 * @author     Kiro AI Assistant
 * @version    1.0.0
 * @since      June 3, 2026
 */
class Payroll_validator {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    protected $CI;
    
    /**
     * Array of validation errors
     * @var array
     */
    protected $errors = array();
    
    /**
     * Array of validation warnings (non-blocking)
     * @var array
     */
    protected $warnings = array();
    
    /**
     * Validation rules configuration
     * @var array
     */
    protected $rules = array(
        'basic_salary' => array(
            'min' => 0,
            'max' => 100000,
            'required' => true
        ),
        'allowances' => array(
            'min' => 0,
            'max' => 50000,
            'required' => false
        ),
        'deductions' => array(
            'min' => 0,
            'max' => 100,  // Percentage
            'required' => false
        ),
        'month' => array(
            'valid_months' => array(
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ),
            'required' => true
        ),
        'year' => array(
            'min' => 2020,
            'max' => null,  // Set dynamically to current year + 1
            'required' => true
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Set dynamic max year (current year + 1)
        $this->rules['year']['max'] = date('Y') + 1;
    }
    
    /**
     * Validate complete payroll data
     * 
     * @param array $data Payroll data to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validate_payroll_data($data) {
        // Reset errors and warnings
        $this->errors = array();
        $this->warnings = array();
        
        // Required fields validation
        $required_fields = array('employee_code', 'month', 'year', 'basic_salary', 'employment_category');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // If required fields missing, return early
        if (!empty($this->errors)) {
            return $this->errors;
        }
        
        // Employee code validation
        if (!$this->validate_employee_code($data['employee_code'], $data['employment_category'])) {
            $this->errors['employee_code'] = 'Invalid employee code for selected category';
        }
        
        // Period validation
        if (!$this->validate_period($data['month'], $data['year'])) {
            // Errors already set in validate_period method
        }
        
        // Basic salary validation
        if (!$this->validate_salary_amount($data['basic_salary'], 'basic_salary')) {
            // Error already set in validate_salary_amount method
        }
        
        // Allowances validation
        $allowance_fields = array(
            'market_premium_allowance',
            'teaching_allowance',
            'responsibility_allowance',
            'rural_allowance',
            'extra_class_allowance',
            'other_allowances'
        );
        
        foreach ($allowance_fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                if (!$this->validate_salary_amount($data[$field], $field, false)) {
                    // Error already set in validate_salary_amount method
                }
            }
        }
        
        // Deductions validation
        $deduction_fields = array(
            'ssnit',
            'tier2_contribution',  // Database field (was 'petra')
            'loan',
            'income_tax',
            'get_fund',
            'gnat_dues',
            'salary_advance',
            'nhil',
            'welfare_dues',
            'other_deductions'
        );
        
        foreach ($deduction_fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                if (!$this->validate_salary_amount($data[$field], $field, false)) {
                    // Error already set in validate_salary_amount method
                }
            }
        }
        
        // Task 24.1: Validate tier2_provider_id - optional when Tier 2 = 0
        if (isset($data['tier2_contribution']) && floatval($data['tier2_contribution']) > 0) {
            // If Tier 2 contribution is > 0, provider must be selected
            if (!isset($data['tier2_provider_id']) || $data['tier2_provider_id'] === '' || $data['tier2_provider_id'] === null) {
                $this->errors['tier2_provider_id'] = 'Tier 2 provider is required when Tier 2 contribution is greater than 0';
            }
        }
        // If Tier 2 = 0, provider is optional - skip validation
        
        // Calculate gross and net salary for validation
        $gross_salary = $this->calculate_gross_salary($data);
        $total_deductions = $this->calculate_total_deductions($data);
        $net_salary = $gross_salary - $total_deductions;
        
        // Net salary validation
        if (!$this->validate_net_salary($net_salary)) {
            $this->errors['net_salary'] = 'Net salary cannot be negative. Total deductions (' . 
                                          number_format($total_deductions, 2) . 
                                          ') exceed gross salary (' . 
                                          number_format($gross_salary, 2) . ')';
        }
        
        // Attendance validation (if provided)
        if (isset($data['working_days']) && isset($data['days_present'])) {
            if (!$this->validate_attendance($data['working_days'], $data['days_present'])) {
                // Error already set in validate_attendance method
            }
        }
        
        // Warning: Check for unusually high salary (20% above previous month)
        $this->check_salary_anomaly($data['employee_code'], $data['month'], $data['year'], $gross_salary);
        
        return $this->errors;
    }
    
    /**
     * Validate salary amount
     * 
     * @param float $amount Amount to validate
     * @param string $field_name Field name for error messaging
     * @param bool $required Whether field is required
     * @return bool
     */
    public function validate_salary_amount($amount, $field_name = 'salary', $required = true) {
        // Check if required
        if ($required && ($amount === '' || $amount === null)) {
            $this->errors[$field_name] = ucfirst(str_replace('_', ' ', $field_name)) . ' is required';
            return false;
        }
        
        // If not required and empty, skip validation
        if (!$required && ($amount === '' || $amount === null || $amount === 0)) {
            return true;
        }
        
        // Check if numeric
        if (!is_numeric($amount)) {
            $this->errors[$field_name] = ucfirst(str_replace('_', ' ', $field_name)) . ' must be a valid number';
            return false;
        }
        
        // Convert to float
        $amount = floatval($amount);
        
        // Check if positive
        if ($amount < 0) {
            $this->errors[$field_name] = ucfirst(str_replace('_', ' ', $field_name)) . ' must be a positive number';
            return false;
        }
        
        // Check maximum (basic salary has different max than allowances)
        $max_amount = ($field_name === 'basic_salary') ? $this->rules['basic_salary']['max'] : $this->rules['allowances']['max'];
        if ($amount > $max_amount) {
            $this->errors[$field_name] = ucfirst(str_replace('_', ' ', $field_name)) . 
                                         ' cannot exceed ' . number_format($max_amount, 2);
            return false;
        }
        
        // Check decimal places (max 2)
        if (floor($amount * 100) != $amount * 100) {
            $this->errors[$field_name] = ucfirst(str_replace('_', ' ', $field_name)) . 
                                         ' can have maximum 2 decimal places';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate deduction percentage
     * 
     * @param float $percentage Percentage to validate
     * @return bool
     */
    public function validate_deduction_percentage($percentage) {
        if (!is_numeric($percentage)) {
            return false;
        }
        
        $percentage = floatval($percentage);
        return ($percentage >= 0 && $percentage <= 100);
    }
    
    /**
     * Validate employee code exists in database
     * 
     * @param string $code Employee code
     * @param string $category Employment category (teacher/administrator/non_teaching_staff)
     * @return bool
     */
    public function validate_employee_code($code, $category) {
        // Sanitize inputs
        $code = $this->CI->db->escape_str($code);
        $category = $this->CI->db->escape_str($category);
        
        // Determine which table to check based on category
        switch ($category) {
            case 'teacher':
                $table = 'teacher';
                $code_field = 'teacher_code';
                break;
            case 'administrator':
                $table = 'admin';
                $code_field = 'admin_code';
                break;
            case 'non_teaching_staff':
                // Assuming non-teaching staff use teacher table with category field
                // Adjust this based on your actual schema
                $table = 'teacher';  // or 'staff' if you have a separate table
                $code_field = 'teacher_code';
                break;
            default:
                $this->errors['employment_category'] = 'Invalid employment category';
                return false;
        }
        
        // Check if employee exists
        $query = $this->CI->db->where($code_field, $code)->get($table);
        return ($query->num_rows() > 0);
    }
    
    /**
     * Validate month and year period
     * 
     * @param string $month Month name
     * @param int $year Year
     * @return bool
     */
    public function validate_period($month, $year) {
        $valid = true;
        
        // Validate month
        if (!in_array($month, $this->rules['month']['valid_months'])) {
            $this->errors['month'] = 'Invalid month. Must be a valid month name (e.g., January)';
            $valid = false;
        }
        
        // Validate year
        if (!is_numeric($year)) {
            $this->errors['year'] = 'Year must be a valid number';
            $valid = false;
        } else {
            $year = intval($year);
            if ($year < $this->rules['year']['min'] || $year > $this->rules['year']['max']) {
                $this->errors['year'] = 'Year must be between ' . $this->rules['year']['min'] . 
                                        ' and ' . $this->rules['year']['max'];
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate net salary is not negative
     * 
     * @param float $net_salary Calculated net salary
     * @return bool
     */
    public function validate_net_salary($net_salary) {
        return ($net_salary >= 0);
    }
    
    /**
     * Validate attendance days
     * 
     * @param int $working_days Total working days
     * @param int $days_present Days present
     * @return bool
     */
    protected function validate_attendance($working_days, $days_present) {
        $valid = true;
        
        // Check if numeric
        if (!is_numeric($working_days) || !is_numeric($days_present)) {
            $this->errors['attendance'] = 'Working days and days present must be numbers';
            return false;
        }
        
        $working_days = intval($working_days);
        $days_present = intval($days_present);
        
        // Check if positive
        if ($working_days < 0 || $days_present < 0) {
            $this->errors['attendance'] = 'Working days and days present must be positive numbers';
            $valid = false;
        }
        
        // Check if days present doesn't exceed working days
        if ($days_present > $working_days) {
            $this->errors['attendance'] = 'Days present (' . $days_present . 
                                          ') cannot exceed working days (' . $working_days . ')';
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Calculate gross salary from data
     * 
     * @param array $data Payroll data
     * @return float
     */
    protected function calculate_gross_salary($data) {
        $gross = floatval($data['basic_salary']);
        
        $allowance_fields = array(
            'market_premium_allowance',
            'teaching_allowance',
            'responsibility_allowance',
            'rural_allowance',
            'extra_class_allowance',
            'other_allowances'
        );
        
        foreach ($allowance_fields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $gross += floatval($data[$field]);
            }
        }
        
        return $gross;
    }
    
    /**
     * Calculate total deductions from data
     * 
     * @param array $data Payroll data
     * @return float
     */
    protected function calculate_total_deductions($data) {
        $total = 0;
        
        $deduction_fields = array(
            'ssnit',
            'tier2_contribution',  // Database field (was 'petra')
            'loan',
            'income_tax',
            'get_fund',
            'gnat_dues',
            'salary_advance',
            'nhil',
            'welfare_dues',
            'other_deductions'
        );
        
        foreach ($deduction_fields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $total += floatval($data[$field]);
            }
        }
        
        return $total;
    }
    
    /**
     * Check for salary anomalies (warnings, not errors)
     * 
     * @param string $employee_code Employee code
     * @param string $month Current month
     * @param int $year Current year
     * @param float $current_gross Current gross salary
     * @return void
     */
    protected function check_salary_anomaly($employee_code, $month, $year, $current_gross) {
        // Get previous month's payroll
        $previous_month = date('F', strtotime('-1 month', strtotime("$year-$month-01")));
        $previous_year = ($month === 'January') ? $year - 1 : $year;
        
        $query = $this->CI->db
            ->select('basic_salary, market_premium_allowance, teaching_allowance, responsibility_allowance, rural_allowance, extra_class_allowance, other_allowances')
            ->where('employee_code', $employee_code)
            ->where('month', $previous_month)
            ->where('year', $previous_year)
            ->get('pay_salary');
        
        if ($query->num_rows() > 0) {
            $prev_data = $query->row_array();
            $prev_gross = $this->calculate_gross_salary($prev_data);
            
            // Check if current gross is 20% higher than previous
            $increase_threshold = $prev_gross * 1.20;
            if ($current_gross > $increase_threshold) {
                $increase_percent = (($current_gross - $prev_gross) / $prev_gross) * 100;
                $this->warnings[] = 'Salary is ' . number_format($increase_percent, 1) . 
                                   '% higher than last month (GHS ' . number_format($prev_gross, 2) . ')';
            }
        }
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function get_validation_errors() {
        return $this->errors;
    }
    
    /**
     * Get validation warnings
     * 
     * @return array
     */
    public function get_validation_warnings() {
        return $this->warnings;
    }
    
    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function is_valid() {
        return empty($this->errors);
    }
    
    /**
     * Sanitize string input to prevent SQL injection
     * 
     * @param string $input Input string
     * @return string
     */
    public function sanitize_string($input) {
        // Remove SQL keywords
        $sql_keywords = array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'EXEC', 'UNION');
        $input = str_ireplace($sql_keywords, '', $input);
        
        // Remove special characters except letters, numbers, spaces, and common punctuation
        $input = preg_replace('/[^a-zA-Z0-9\s\-_.,]/', '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        return $input;
    }
}
