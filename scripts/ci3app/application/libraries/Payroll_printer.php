<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll Printer - Format Payroll objects to JSON
 * 
 * Handles conversion from internal payroll data structures to JSON format.
 * Ensures all fields are present and null values are converted to appropriate defaults.
 * 
 * @package    SchoolManager
 * @subpackage Libraries
 * @category   Payroll
 * @author     School Manager Team
 */
class Payroll_printer {
    
    private $CI;
    private $schema_fields = [
        'pay_id', 'employee_code', 'employment_category', 'month', 'year',
        'basic_salary', 'market_premium_allowance', 'teaching_allowance',
        'responsibility_allowance', 'rural_allowance', 'extra_class_allowance',
        'other_allowances', 'ssnit', 'tier2_contribution', 'tier2_provider_id',
        'income_tax', 'get_fund', 'nhil', 'salary_advance', 'loans',
        'welfare_dues', 'gnat_dues', 'other_deductions', 'working_days',
        'days_present', 'days_absent', 'total_allowances', 'total_deductions',
        'gross_salary', 'net_salary', 'approval_status', 'status', 'reference',
        'created_at', 'updated_at', 'created_by', 'approved_by', 'approved_at'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        log_message('info', 'Payroll_printer Library Initialized');
    }
    
    /**
     * Print Payroll array to JSON string
     * 
     * @param array $payroll_data Payroll data array
     * @param bool $pretty Pretty print JSON
     * @return string JSON string
     */
    public function print($payroll_data, $pretty = false) {
        if (!is_array($payroll_data)) {
            return json_encode(['error' => 'Invalid payroll data']);
        }
        
        // Ensure all schema fields are present
        $output = [];
        
        foreach ($this->schema_fields as $field) {
            if (isset($payroll_data[$field])) {
                $value = $payroll_data[$field];
                
                // Convert null to appropriate defaults
                if (is_null($value)) {
                    $value = $this->get_default_value($field);
                }
                
                $output[$field] = $value;
            } else {
                $output[$field] = $this->get_default_value($field);
            }
        }
        
        $options = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }
        
        return json_encode($output, $options);
    }
    
    /**
     * Print batch of Payroll records to JSON array
     * 
     * @param array $payroll_records Array of payroll data
     * @param bool $pretty Pretty print JSON
     * @return string JSON array string
     */
    public function print_batch($payroll_records, $pretty = false) {
        if (!is_array($payroll_records)) {
            return json_encode(['error' => 'Invalid payroll records']);
        }
        
        $output = [];
        
        foreach ($payroll_records as $record) {
            $output[] = json_decode($this->print($record, false), true);
        }
        
        $options = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }
        
        return json_encode($output, $options);
    }
    
    /**
     * Get default value for field based on type
     * 
     * This is a helper method that returns appropriate default values
     * when fields are null. Used for data normalization and CSV export.
     * 
     * @param string $field Field name
     * @return mixed Default value (0 for numeric fields, '' for text fields)
     */
    public function get_default_value($field) {
        // Numeric fields default to 0
        $numeric_fields = [
            'pay_id', 'tier2_provider_id', 'basic_salary', 
            'market_premium_allowance', 'teaching_allowance',
            'responsibility_allowance', 'rural_allowance', 'extra_class_allowance',
            'other_allowances', 'ssnit', 'tier2_contribution', 'income_tax',
            'get_fund', 'nhil', 'salary_advance', 'loans', 'welfare_dues',
            'gnat_dues', 'other_deductions', 'working_days', 'days_present',
            'days_absent', 'total_allowances', 'total_deductions',
            'gross_salary', 'net_salary'
        ];
        
        if (in_array($field, $numeric_fields)) {
            return 0;
        }
        
        // Integer fields that can be null
        if (in_array($field, ['month', 'year'])) {
            return null;
        }
        
        // String fields default to empty string
        return '';
    }
    
    /**
     * Print summary of payroll data (minimal fields)
     * 
     * @param array $payroll_data Payroll data array
     * @param bool $pretty Pretty print JSON
     * @return string JSON string
     */
    public function print_summary($payroll_data, $pretty = false) {
        $summary_fields = [
            'pay_id', 'employee_code', 'month', 'year',
            'basic_salary', 'total_allowances', 'gross_salary',
            'total_deductions', 'net_salary', 'approval_status'
        ];
        
        $output = [];
        
        foreach ($summary_fields as $field) {
            if (isset($payroll_data[$field])) {
                $output[$field] = $payroll_data[$field];
            } else {
                $output[$field] = $this->get_default_value($field);
            }
        }
        
        $options = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }
        
        return json_encode($output, $options);
    }
    
    /**
     * Print payroll data as CSV string
     * 
     * @param array $payroll_records Array of payroll data
     * @param array $fields Fields to include (optional, default: all)
     * @return string CSV string
     */
    public function print_csv($payroll_records, $fields = null) {
        if (!is_array($payroll_records) || empty($payroll_records)) {
            return '';
        }
        
        // Use all schema fields if not specified
        if ($fields === null) {
            $fields = $this->schema_fields;
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write header row
        fputcsv($output, $fields);
        
        // Write data rows
        foreach ($payroll_records as $record) {
            $row = [];
            foreach ($fields as $field) {
                $value = isset($record[$field]) ? $record[$field] : $this->get_default_value($field);
                $row[] = $value;
            }
            fputcsv($output, $row);
        }
        
        // Get CSV content
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
