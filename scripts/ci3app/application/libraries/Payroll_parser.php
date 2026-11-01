<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll Parser - Parse JSON to Payroll objects
 * 
 * Handles conversion between JSON format and internal payroll data structures.
 * Validates required fields and data types during parsing.
 * 
 * @package    SchoolManager
 * @subpackage Libraries
 * @category   Payroll
 * @author     School Manager Team
 */
class Payroll_parser {
    
    private $CI;
    private $required_fields = ['employee_code', 'month', 'year', 'basic_salary'];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        log_message('info', 'Payroll_parser Library Initialized');
    }
    
    /**
     * Parse JSON string to Payroll array
     * 
     * @param string $json JSON string
     * @return array ['success' => bool, 'data' => array|null, 'errors' => array]
     */
    public function parse($json) {
        // Validate JSON structure
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'data' => null,
                'errors' => ['Invalid JSON: ' . json_last_error_msg()]
            ];
        }
        
        // Validate required fields
        $errors = [];
        
        foreach ($this->required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[] = "Required field missing: {$field}";
            }
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'data' => null,
                'errors' => $errors
            ];
        }
        
        // Validate data types and values
        if (!is_numeric($data['basic_salary']) || $data['basic_salary'] <= 0) {
            $errors[] = "basic_salary must be a positive number";
        }
        
        if (!is_numeric($data['year']) || $data['year'] < 2020) {
            $errors[] = "year must be a valid year >= 2020";
        }
        
        if (!is_numeric($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
            $errors[] = "month must be between 1 and 12";
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'data' => null,
                'errors' => $errors
            ];
        }
        
        // Normalize null values to 0 for numeric fields
        $numeric_fields = [
            'market_premium_allowance', 'teaching_allowance', 'responsibility_allowance',
            'extra_class_allowance', 'rural_allowance', 'other_allowances',
            'ssnit', 'tier2_contribution', 'income_tax', 'get_fund', 'nhil',
            'salary_advance', 'loans', 'welfare_dues', 'gnat_dues', 'other_deductions',
            'working_days', 'days_present', 'days_absent',
            'total_allowances', 'total_deductions', 'gross_salary', 'net_salary'
        ];
        
        foreach ($numeric_fields as $field) {
            if (!isset($data[$field]) || is_null($data[$field])) {
                $data[$field] = 0;
            } else {
                // Ensure numeric
                $data[$field] = floatval($data[$field]);
            }
        }
        
        // Normalize string fields
        $string_fields = ['employee_code', 'employment_category', 'approval_status', 'status', 'reference'];
        
        foreach ($string_fields as $field) {
            if (!isset($data[$field]) || is_null($data[$field])) {
                $data[$field] = '';
            }
        }
        
        return [
            'success' => true,
            'data' => $data,
            'errors' => []
        ];
    }
    
    /**
     * Parse batch of JSON payroll records
     * 
     * @param string $json_array JSON array string
     * @return array ['success' => bool, 'data' => array, 'errors' => array]
     */
    public function parse_batch($json_array) {
        $array = json_decode($json_array, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['Invalid JSON array: ' . json_last_error_msg()]
            ];
        }
        
        if (!is_array($array)) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['JSON must be an array of payroll records']
            ];
        }
        
        $results = [];
        $errors = [];
        
        foreach ($array as $index => $item) {
            $result = $this->parse(json_encode($item));
            
            if ($result['success']) {
                $results[] = $result['data'];
            } else {
                $errors[] = "Record {$index}: " . implode(', ', $result['errors']);
            }
        }
        
        return [
            'success' => empty($errors),
            'data' => $results,
            'errors' => $errors,
            'total' => count($array),
            'parsed' => count($results),
            'failed' => count($errors)
        ];
    }
    
    /**
     * Validate payroll data structure (without JSON parsing)
     * 
     * @param array $data Payroll data array
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($data) {
        $errors = [];
        
        // Check required fields
        foreach ($this->required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[] = "Required field missing: {$field}";
            }
        }
        
        // Validate basic_salary
        if (isset($data['basic_salary']) && (!is_numeric($data['basic_salary']) || $data['basic_salary'] <= 0)) {
            $errors[] = "basic_salary must be a positive number";
        }
        
        // Validate year
        if (isset($data['year']) && (!is_numeric($data['year']) || $data['year'] < 2020)) {
            $errors[] = "year must be >= 2020";
        }
        
        // Validate month
        if (isset($data['month']) && (!is_numeric($data['month']) || $data['month'] < 1 || $data['month'] > 12)) {
            $errors[] = "month must be between 1 and 12";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
