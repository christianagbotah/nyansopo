<?php

use PHPUnit\Framework\TestCase;
use Eris\Generator;

/**
 * Property-Based Tests for Payroll Parser Required Fields Validation
 * 
 * Property 10: Parser Required Fields Validation
 * Validates: Requirements 17.2
 * 
 * Tests that the parser correctly identifies and reports missing required fields
 * when parsing payroll JSON data.
 * 
 * Required fields: employee_code, month, year, basic_salary
 */
class Payroll_parser_property_test extends TestCase {
    
    use Eris\TestTrait;
    
    private $parser;
    private $required_fields = ['employee_code', 'month', 'year', 'basic_salary'];
    
    public function setUp(): void {
        parent::setUp();
        
        // Load the Payroll_parser library directly
        require_once __DIR__ . '/../../libraries/Payroll_parser.php';
        $this->parser = new Payroll_parser();
    }
    
    /**
     * Property 10: Parser Required Fields Validation
     * 
     * Tests that the parser returns errors when required fields are missing.
     * 
     * For any valid JSON payroll object with one or more required fields missing,
     * the parser must:
     * 1. Return success = false
     * 2. Return data = null
     * 3. Return errors array containing messages for all missing fields
     */
    public function test_parser_rejects_payroll_with_missing_required_fields() {
        $this->forAll(
            // Generate a field name to remove (one of the required fields)
            Generator\elements(...$this->required_fields),
            
            // Generate a valid payroll base object
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000),
                'employment_category' => Generator\constant('teaching_staff'),
                'total_allowances' => Generator\constant(500.00),
                'total_deductions' => Generator\constant(200.00),
                'gross_salary' => Generator\constant(5500.00),
                'net_salary' => Generator\constant(5300.00)
            ])
        )
        ->then(function($field_to_remove, $payroll_data) {
            // Ensure employee_code is not empty
            if (empty(trim($payroll_data['employee_code']))) {
                $payroll_data['employee_code'] = 'EMP001';
            }
            
            // Remove one required field
            unset($payroll_data[$field_to_remove]);
            
            // Parse the JSON
            $json = json_encode($payroll_data);
            $result = $this->parser->parse($json);
            
            // Assertions
            $this->assertFalse(
                $result['success'],
                "Parser should reject payroll with missing field: {$field_to_remove}"
            );
            
            $this->assertNull(
                $result['data'],
                "Parser should return null data when required fields are missing"
            );
            
            $this->assertNotEmpty(
                $result['errors'],
                "Parser should return error messages for missing fields"
            );
            
            // Verify error message mentions the missing field
            $error_message = implode(' ', $result['errors']);
            $this->assertStringContainsString(
                $field_to_remove,
                $error_message,
                "Error message should mention the missing field: {$field_to_remove}"
            );
        });
    }
    
    /**
     * Property: Parser rejects payroll with multiple missing required fields
     * 
     * Tests that all missing required fields are reported in the errors array.
     */
    public function test_parser_reports_all_missing_required_fields() {
        $this->forAll(
            // Generate a subset of required fields to keep (0 to 3 fields)
            Generator\bind(
                Generator\choose(0, 3),
                function($num_fields_to_keep) {
                    // Randomly select which fields to keep
                    return Generator\seq(
                        Generator\constant($num_fields_to_keep),
                        Generator\subset($this->required_fields)
                    );
                }
            ),
            
            // Generate valid values for the fields
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($fields_config, $field_values) {
            list($num_fields_to_keep, $fields_to_keep) = $fields_config;
            
            // Only keep selected fields
            $payroll_data = [];
            foreach ($fields_to_keep as $field) {
                if (isset($field_values[$field])) {
                    $payroll_data[$field] = $field_values[$field];
                }
            }
            
            // Calculate missing fields
            $missing_fields = array_diff($this->required_fields, array_keys($payroll_data));
            
            // If all fields present, skip this test case
            if (empty($missing_fields)) {
                $this->assertTrue(true);
                return;
            }
            
            // Parse the JSON
            $json = json_encode($payroll_data);
            $result = $this->parser->parse($json);
            
            // Assertions
            $this->assertFalse(
                $result['success'],
                "Parser should reject payroll with missing fields: " . implode(', ', $missing_fields)
            );
            
            $this->assertNull(
                $result['data'],
                "Parser should return null data when required fields are missing"
            );
            
            // Verify each missing field is mentioned in errors
            $error_message = implode(' ', $result['errors']);
            
            foreach ($missing_fields as $missing_field) {
                $this->assertStringContainsString(
                    $missing_field,
                    $error_message,
                    "Error message should mention missing field: {$missing_field}"
                );
            }
            
            // Verify the number of errors matches the number of missing fields
            $this->assertCount(
                count($missing_fields),
                $result['errors'],
                "Parser should return one error per missing required field"
            );
        });
    }
    
    /**
     * Property: Parser accepts payroll with all required fields present
     * 
     * Tests that the parser succeeds when all required fields are present and valid.
     */
    public function test_parser_accepts_payroll_with_all_required_fields() {
        $this->forAll(
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($field_values) {
            // Ensure basic_salary is positive and numeric
            $field_values['basic_salary'] = abs($field_values['basic_salary']);
            
            // Ensure employee_code is not empty
            if (empty(trim($field_values['employee_code']))) {
                $field_values['employee_code'] = 'EMP001';
            }
            
            // Parse the JSON
            $json = json_encode($field_values);
            $result = $this->parser->parse($json);
            
            // Assertions
            $this->assertTrue(
                $result['success'],
                "Parser should accept payroll with all required fields present. Errors: " . implode(', ', $result['errors'])
            );
            
            $this->assertNotNull(
                $result['data'],
                "Parser should return data when all required fields are present"
            );
            
            $this->assertEmpty(
                $result['errors'],
                "Parser should return no errors when all required fields are present"
            );
            
            // Verify all required fields are in the result
            foreach ($this->required_fields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $result['data'],
                    "Parsed data should contain required field: {$field}"
                );
            }
        });
    }
    
    /**
     * Property: Parser rejects payroll with null required fields
     * 
     * Tests that null values in required fields are treated as missing.
     */
    public function test_parser_rejects_payroll_with_null_required_fields() {
        $this->forAll(
            Generator\elements(...$this->required_fields),
            
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($field_to_null, $field_values) {
            // Set one required field to null
            $field_values[$field_to_null] = null;
            
            // Parse the JSON
            $json = json_encode($field_values);
            $result = $this->parser->parse($json);
            
            // Assertions
            $this->assertFalse(
                $result['success'],
                "Parser should reject payroll with null required field: {$field_to_null}"
            );
            
            $this->assertNull(
                $result['data'],
                "Parser should return null data when required fields are null"
            );
            
            $this->assertNotEmpty(
                $result['errors'],
                "Parser should return error messages for null required fields"
            );
            
            // Verify error message mentions the null field
            $error_message = implode(' ', $result['errors']);
            $this->assertStringContainsString(
                $field_to_null,
                $error_message,
                "Error message should mention the null field: {$field_to_null}"
            );
        });
    }
    
    /**
     * Property: Parser rejects payroll with empty string required fields
     * 
     * Tests that empty strings in required fields are treated as missing.
     */
    public function test_parser_rejects_payroll_with_empty_string_required_fields() {
        $this->forAll(
            Generator\elements(...$this->required_fields),
            
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($field_to_empty, $field_values) {
            // Set one required field to empty string
            $field_values[$field_to_empty] = '';
            
            // Parse the JSON
            $json = json_encode($field_values);
            $result = $this->parser->parse($json);
            
            // Assertions
            $this->assertFalse(
                $result['success'],
                "Parser should reject payroll with empty string required field: {$field_to_empty}"
            );
            
            $this->assertNull(
                $result['data'],
                "Parser should return null data when required fields are empty strings"
            );
            
            $this->assertNotEmpty(
                $result['errors'],
                "Parser should return error messages for empty string required fields"
            );
        });
    }
}