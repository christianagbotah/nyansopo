<?php

use PHPUnit\Framework\TestCase;
use Eris\Generator;

/**
 * Property-Based Tests for Payroll Printer
 * 
 * Property 11: Parser-Printer Round Trip Preservation
 * Property 12: Printer Null-to-Zero Conversion
 * 
 * Validates: Requirements 17.6, 17.7
 */
class Payroll_printer_property_test extends TestCase {
    
    use Eris\TestTrait;
    
    private $CI;
    private $parser;
    private $printer;
    
    public function setUp(): void {
        parent::setUp();
        
        // Get CI instance - this will use our mock
        $this->CI = get_instance();
        
        if (!is_object($this->CI)) {
            throw new \Exception("Could not initialize CI instance");
        }
        
        // Load libraries
        $this->CI->load_library('payroll_parser');
        $this->CI->load_library('payroll_printer');
        
        $this->parser = $this->CI->payroll_parser;
        $this->printer = $this->CI->payroll_printer;
    }
    
    /**
     * Property 12: Printer Null-to-Zero Conversion
     * 
     * Tests that printer outputs 0 for null numeric fields.
     * 
     * For any payroll object with null numeric fields, the printer must:
     * 1. Convert null numeric fields to 0
     * 2. Preserve non-null values
     * 3. Convert null string fields to empty strings
     */
    public function test_printer_converts_null_numeric_fields_to_zero() {
        $this->forAll(
            // Generate a random subset of numeric fields to set to null
            Generator\subset([
                'market_premium_allowance', 'teaching_allowance',
                'responsibility_allowance', 'extra_class_allowance',
                'rural_allowance', 'other_allowances',
                'ssnit', 'tier2_contribution', 'income_tax',
                'get_fund', 'nhil', 'salary_advance',
                'loans', 'welfare_dues', 'gnat_dues',
                'other_deductions', 'working_days',
                'days_present', 'days_absent',
                'total_allowances', 'total_deductions'
            ]),
            
            // Generate a valid payroll base
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000),
                'gross_salary' => Generator\choose(5000, 60000),
                'net_salary' => Generator\choose(4000, 55000)
            ])
        )
        ->then(function($fields_to_null, $payroll_base) {
            // Build payroll data with some null fields
            $payroll_data = $payroll_base;
            
            // Set selected fields to null
            foreach ($fields_to_null as $field) {
                $payroll_data[$field] = null;
            }
            
            // Print to JSON
            $json = $this->printer->print($payroll_data);
            $decoded = json_decode($json, true);
            
            // Assertions
            $this->assertNotNull($decoded, "Printer should produce valid JSON");
            
            // Verify null numeric fields are converted to 0
            foreach ($fields_to_null as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $decoded,
                    "Printed JSON should contain field: {$field}"
                );
                
                $this->assertEquals(
                    0,
                    $decoded[$field],
                    "Null numeric field '{$field}' should be converted to 0"
                );
            }
            
            // Verify non-null fields are preserved
            $this->assertEquals(
                $payroll_base['employee_code'],
                $decoded['employee_code'],
                "Non-null employee_code should be preserved"
            );
            
            $this->assertEquals(
                $payroll_base['basic_salary'],
                $decoded['basic_salary'],
                "Non-null basic_salary should be preserved"
            );
        });
    }
    
    /**
     * Property: Printer converts null string fields to empty strings
     */
    public function test_printer_converts_null_string_fields_to_empty_string() {
        $this->forAll(
            // Generate a random subset of string fields to set to null
            Generator\subset([
                'employment_category', 'approval_status',
                'status', 'reference'
            ]),
            
            // Generate a valid payroll base
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($fields_to_null, $payroll_base) {
            // Build payroll data with null string fields
            $payroll_data = $payroll_base;
            
            foreach ($fields_to_null as $field) {
                $payroll_data[$field] = null;
            }
            
            // Print to JSON
            $json = $this->printer->print($payroll_data);
            $decoded = json_decode($json, true);
            
            // Assertions
            $this->assertNotNull($decoded, "Printer should produce valid JSON");
            
            // Verify null string fields are converted to empty strings
            foreach ($fields_to_null as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $decoded,
                    "Printed JSON should contain field: {$field}"
                );
                
                $this->assertEquals(
                    '',
                    $decoded[$field],
                    "Null string field '{$field}' should be converted to empty string"
                );
            }
        });
    }
    
    /**
     * Property 11: Parser-Printer Round Trip Preservation
     * 
     * Tests that parse → print → parse produces equivalent object.
     * 
     * For any valid payroll JSON:
     * 1. Parse to object
     * 2. Print back to JSON
     * 3. Parse again
     * 4. Result should be equivalent to original parsed object
     */
    public function test_parse_print_parse_preserves_data() {
        $this->forAll(
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000),
                'market_premium_allowance' => Generator\choose(0, 5000),
                'teaching_allowance' => Generator\choose(0, 3000),
                'responsibility_allowance' => Generator\choose(0, 2000),
                'extra_class_allowance' => Generator\choose(0, 1500),
                'rural_allowance' => Generator\choose(0, 1000),
                'other_allowances' => Generator\choose(0, 2000),
                'ssnit' => Generator\choose(0, 5000),
                'tier2_contribution' => Generator\choose(0, 2000),
                'income_tax' => Generator\choose(0, 5000),
                'get_fund' => Generator\choose(0, 1000),
                'nhil' => Generator\choose(0, 1000),
                'salary_advance' => Generator\choose(0, 5000),
                'loans' => Generator\choose(0, 5000),
                'welfare_dues' => Generator\choose(0, 500),
                'gnat_dues' => Generator\choose(0, 500),
                'other_deductions' => Generator\choose(0, 2000),
                'employment_category' => Generator\elements('teaching_staff', 'non_teaching_staff'),
                'approval_status' => Generator\elements('draft', 'pending', 'approved', 'rejected')
            ])
        )
        ->then(function($payroll_data) {
            // Ensure employee_code is not empty
            if (empty(trim($payroll_data['employee_code']))) {
                $payroll_data['employee_code'] = 'EMP001';
            }
            
            // Ensure basic_salary is positive
            $payroll_data['basic_salary'] = abs($payroll_data['basic_salary']);
            
            // First parse (input validation)
            $json1 = json_encode($payroll_data);
            $parsed1 = $this->parser->parse($json1);
            
            $this->assertTrue(
                $parsed1['success'],
                "Initial parse should succeed. Errors: " . implode(', ', $parsed1['errors'])
            );
            
            // Print to JSON
            $json2 = $this->printer->print($parsed1['data']);
            
            // Second parse (round-trip test)
            $parsed2 = $this->parser->parse($json2);
            
            $this->assertTrue(
                $parsed2['success'],
                "Second parse should succeed. Errors: " . implode(', ', $parsed2['errors'])
            );
            
            // Compare key fields (required + numeric fields that should be preserved)
            $key_fields = [
                'employee_code', 'month', 'year', 'basic_salary',
                'market_premium_allowance', 'teaching_allowance',
                'responsibility_allowance', 'extra_class_allowance',
                'rural_allowance', 'other_allowances',
                'ssnit', 'tier2_contribution', 'income_tax',
                'get_fund', 'nhil', 'salary_advance',
                'loans', 'welfare_dues', 'gnat_dues',
                'other_deductions', 'employment_category',
                'approval_status'
            ];
            
            foreach ($key_fields as $field) {
                // Convert to comparable values (handle numeric string comparison)
                $value1 = isset($parsed1['data'][$field]) ? $parsed1['data'][$field] : null;
                $value2 = isset($parsed2['data'][$field]) ? $parsed2['data'][$field] : null;
                
                // For numeric fields, compare as floats
                if (is_numeric($value1) && is_numeric($value2)) {
                    $this->assertEquals(
                        floatval($value1),
                        floatval($value2),
                        "Field '{$field}' should be preserved through round-trip"
                    );
                } else {
                    $this->assertEquals(
                        $value1,
                        $value2,
                        "Field '{$field}' should be preserved through round-trip"
                    );
                }
            }
        });
    }
    
    /**
     * Property: Printer produces valid JSON for all inputs
     */
    public function test_printer_always_produces_valid_json() {
        $this->forAll(
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($payroll_data) {
            $json = $this->printer->print($payroll_data);
            
            // Attempt to decode
            $decoded = json_decode($json, true);
            
            // Assertions
            $this->assertNotNull(
                $decoded,
                "Printer should always produce valid JSON. JSON error: " . json_last_error_msg()
            );
            
            $this->assertEquals(
                JSON_ERROR_NONE,
                json_last_error(),
                "JSON should have no errors"
            );
            
            $this->assertIsArray(
                $decoded,
                "Decoded JSON should be an array"
            );
        });
    }
    
    /**
     * Property: Pretty print produces valid and formatted JSON
     */
    public function test_printer_pretty_print_produces_formatted_json() {
        $this->forAll(
            Generator\associative([
                'employee_code' => Generator\string(),
                'month' => Generator\choose(1, 12),
                'year' => Generator\choose(2020, 2030),
                'basic_salary' => Generator\choose(1000, 50000)
            ])
        )
        ->then(function($payroll_data) {
            $json = $this->printer->print($payroll_data, true);
            
            // Assertions
            $this->assertNotEmpty($json, "Pretty print should produce output");
            
            // Check for newlines (indicator of pretty print)
            $this->assertStringContainsString(
                "\n",
                $json,
                "Pretty print should contain newlines"
            );
            
            // Should still be valid JSON
            $decoded = json_decode($json, true);
            $this->assertNotNull($decoded, "Pretty printed JSON should be valid");
        });
    }
    
    /**
     * Property: Batch printer produces valid JSON array
     */
    public function test_printer_batch_produces_valid_json_array() {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'employee_code' => Generator\string(),
                    'month' => Generator\choose(1, 12),
                    'year' => Generator\choose(2020, 2030),
                    'basic_salary' => Generator\choose(1000, 50000)
                ])
            ) // Generate sequence of payroll records
        )
        ->then(function($payroll_records) {
            $json = $this->printer->print_batch($payroll_records);
            
            // Decode
            $decoded = json_decode($json, true);
            
            // Assertions
            $this->assertNotNull(
                $decoded,
                "Batch printer should produce valid JSON"
            );
            
            $this->assertIsArray(
                $decoded,
                "Batch printer should produce an array"
            );
            
            $this->assertCount(
                count($payroll_records),
                $decoded,
                "Batch printer should output same number of records as input"
            );
        });
    }
}
