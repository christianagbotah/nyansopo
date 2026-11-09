<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tax Calculator Library
 * 
 * Automated PAYE (Pay As You Earn) calculation based on Ghana Revenue Authority
 * progressive tax brackets. Implements configurable tax bracket system with
 * historical tracking and detailed breakdown generation.
 * 
 * @package    SchoolManager
 * @subpackage Libraries
 * @category   Tax
 * @author     Kiro AI Assistant
 * @version    1.0.0
 * @since      June 3, 2026
 */
class Tax_calculator {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    protected $CI;
    
    /**
     * Active tax brackets cache
     * @var array
     */
    protected $tax_brackets = null;
    
    /**
     * Tax breakdown for last calculation
     * @var array
     */
    protected $last_breakdown = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Load active tax brackets from database
     * 
     * @param string $country Country code (default: Ghana)
     * @param string $date Effective date (default: today)
     * @return array Array of tax brackets
     */
    public function load_active_tax_brackets($country = 'Ghana', $date = null) {
        // Use cached brackets if available
        if ($this->tax_brackets !== null) {
            return $this->tax_brackets;
        }
        
        // Default to today if no date specified
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        // Query active tax brackets
        $this->CI->db
            ->where('country', $country)
            ->where('is_active', 1)
            ->where('effective_from <=', $date)
            ->where('(effective_to IS NULL OR effective_to >=', $date . ')', false)
            ->order_by('min_income', 'ASC');
        
        $query = $this->CI->db->get('tax_brackets');
        
        if ($query->num_rows() === 0) {
            // Fallback: Load Ghana 2024 brackets if no active brackets found
            log_message('warning', 'No active tax brackets found. Using fallback Ghana 2024 brackets.');
            $this->tax_brackets = $this->get_fallback_brackets();
        } else {
            $this->tax_brackets = $query->result_array();
        }
        
        return $this->tax_brackets;
    }
    
    /**
     * Calculate annual PAYE based on progressive tax brackets
     * 
     * Ghana PAYE Calculation (GRA Method):
     * 1. Calculate Annual Taxable Income = (Annual Gross - SSNIT Employee Contribution)
     *    Note: Only EMPLOYEE portion of SSNIT is deducted (5.5% of basic salary)
     * 2. Apply progressive tax brackets using cumulative method
     * 3. Return total annual tax
     * 
     * Progressive Calculation Method:
     * - For each bracket, calculate: (Income in bracket) × (Tax rate)
     * - Use fixed_amount (cumulative tax from previous brackets) for efficiency
     * - Sum all bracket taxes = Total PAYE
     * 
     * @param float $annual_gross Annual gross salary
     * @param float $annual_ssnit_deduction Annual SSNIT employee deduction (5.5% of basic)
     * @param string $country Country code (default: Ghana)
     * @return float Annual PAYE amount
     */
    public function calculate_paye($annual_gross, $annual_ssnit_deduction, $country = 'Ghana') {
        // Calculate annual taxable income
        // Taxable Income = Gross Salary - SSNIT Employee Contribution
        $annual_taxable = $annual_gross - $annual_ssnit_deduction;
        
        // If taxable income is zero or negative, no tax
        if ($annual_taxable <= 0) {
            $this->last_breakdown = array();
            return 0;
        }
        
        // Load tax brackets
        $brackets = $this->load_active_tax_brackets($country);
        
        // Calculate tax progressively using GRA method
        $total_tax = 0;
        $this->last_breakdown = array();
        
        foreach ($brackets as $bracket) {
            $min_income = floatval($bracket['min_income']);
            $max_income = ($bracket['max_income'] !== null) ? floatval($bracket['max_income']) : PHP_FLOAT_MAX;
            $tax_rate = floatval($bracket['tax_rate']) / 100;  // Convert percentage to decimal
            
            // Skip if income doesn't reach this bracket
            if ($annual_taxable <= $min_income) {
                break;
            }
            
            // Calculate taxable amount in this bracket
            // This is the portion of income that falls within this bracket's range
            $bracket_max = min($max_income, $annual_taxable);
            $taxable_in_bracket = $bracket_max - $min_income;
            
            // Calculate tax for this bracket
            $tax_in_bracket = $taxable_in_bracket * $tax_rate;
            $total_tax += $tax_in_bracket;
            
            // Store breakdown for detailed reporting
            $this->last_breakdown[] = array(
                'bracket_name' => $bracket['bracket_name'],
                'min_income' => $min_income,
                'max_income' => ($bracket['max_income'] !== null) ? $max_income : 'No limit',
                'income_range' => ($bracket['max_income'] !== null) 
                    ? 'GH¢' . number_format($min_income, 2) . ' - GH¢' . number_format($max_income, 2)
                    : 'Above GH¢' . number_format($min_income, 2),
                'taxable_amount' => round($taxable_in_bracket, 2),
                'tax_rate' => $bracket['tax_rate'],
                'tax_amount' => round($tax_in_bracket, 2)
            );
            
            // If we've processed all income, stop
            if ($annual_taxable <= $max_income) {
                break;
            }
        }
        
        // Round to 2 decimal places
        return round($total_tax, 2);
    }
    
    /**
     * Calculate monthly PAYE from monthly gross and SSNIT
     * 
     * Convenience method that converts monthly values to annual,
     * calculates PAYE, then converts back to monthly.
     * 
     * @param float $monthly_gross Monthly gross salary
     * @param float $monthly_ssnit Monthly SSNIT deduction
     * @param string $country Country code (default: Ghana)
     * @return float Monthly PAYE amount
     */
    public function calculate_monthly_paye($monthly_gross, $monthly_ssnit, $country = 'Ghana') {
        // Convert to annual
        $annual_gross = $monthly_gross * 12;
        $annual_ssnit = $monthly_ssnit * 12;
        
        // Calculate annual PAYE
        $annual_paye = $this->calculate_paye($annual_gross, $annual_ssnit, $country);
        
        // Convert to monthly
        $monthly_paye = $annual_paye / 12;
        
        return round($monthly_paye, 2);
    }
    
    /**
     * Get detailed tax breakdown for last calculation
     * 
     * Returns array with breakdown by tax bracket including:
     * - Bracket name and range
     * - Taxable amount in bracket
     * - Tax rate
     * - Tax amount calculated
     * 
     * @return array Tax breakdown
     */
    public function get_tax_breakdown() {
        return $this->last_breakdown;
    }
    
    /**
     * Calculate and return complete tax calculation details
     * 
     * @param float $monthly_gross Monthly gross salary
     * @param float $monthly_ssnit Monthly SSNIT deduction
     * @param string $country Country code (default: Ghana)
     * @return array Complete calculation details
     */
    public function get_detailed_calculation($monthly_gross, $monthly_ssnit, $country = 'Ghana') {
        // Calculate values
        $annual_gross = $monthly_gross * 12;
        $annual_ssnit = $monthly_ssnit * 12;
        $annual_taxable = $annual_gross - $annual_ssnit;
        $annual_paye = $this->calculate_paye($annual_gross, $annual_ssnit, $country);
        $monthly_paye = $annual_paye / 12;
        
        return array(
            'monthly_gross' => round($monthly_gross, 2),
            'monthly_ssnit' => round($monthly_ssnit, 2),
            'monthly_taxable' => round($annual_taxable / 12, 2),
            'monthly_paye' => round($monthly_paye, 2),
            'annual_gross' => round($annual_gross, 2),
            'annual_ssnit' => round($annual_ssnit, 2),
            'annual_taxable' => round($annual_taxable, 2),
            'annual_paye' => round($annual_paye, 2),
            'breakdown' => $this->last_breakdown
        );
    }
    
    /**
     * Calculate SSNIT Tier 1 deduction using dynamic rates from database
     * 
     * In Ghana, SSNIT Tier 1 is split between employee and employer.
     * Rates are fetched from payroll_statutory_settings table.
     * 
     * @param float $basic_salary Basic salary (NOT gross salary)
     * @return array Array with employee and employer contributions
     */
    public function calculate_ssnit_tier1($basic_salary) {
        // Load Payroll_statutory_model to get current rates
        $this->CI->load->model('Payroll_statutory_model');
        $rates = $this->CI->Payroll_statutory_model->get_rates_array();
        
        // Get rates from database (no fallbacks - use database values only)
        $employer_rate = $rates['ssnit_tier1_employer'] / 100;
        $employee_rate = $rates['ssnit_tier1_employee'] / 100;
        
        $total_contribution = $basic_salary * ($employer_rate + $employee_rate);
        $employee_contribution = $basic_salary * $employee_rate;
        $employer_contribution = $basic_salary * $employer_rate;
        
        return array(
            'total' => round($total_contribution, 2),
            'employee' => round($employee_contribution, 2),
            'employer' => round($employer_contribution, 2),
            'employee_rate' => $rates['ssnit_tier1_employee'],
            'employer_rate' => $rates['ssnit_tier1_employer']
        );
    }
    
    /**
     * Calculate SSNIT Tier 2 deduction using dynamic rate from database
     * 
     * In Ghana, SSNIT Tier 2 is employee pension contribution.
     * Rate is fetched from payroll_statutory_settings table.
     * 
     * @param float $basic_salary Basic salary (NOT gross salary)
     * @return float SSNIT Tier 2 amount
     */
    public function calculate_ssnit_tier2($basic_salary) {
        // Load Payroll_statutory_model to get current rates
        $this->CI->load->model('Payroll_statutory_model');
        $rates = $this->CI->Payroll_statutory_model->get_rates_array();
        
        // Get rate from database (no fallback - use database value only)
        $tier2_rate = $rates['ssnit_tier2'] / 100;
        
        return round($basic_salary * $tier2_rate, 2);
    }
    
    /**
     * Calculate total SSNIT deduction (employee portion only)
     * 
     * @param float $basic_salary Basic salary (NOT gross salary)
     * @return array Array with Tier 1, Tier 2, and total
     */
    public function calculate_total_ssnit($basic_salary) {
        $tier1 = $this->calculate_ssnit_tier1($basic_salary);
        $tier2 = $this->calculate_ssnit_tier2($basic_salary);
        
        return array(
            'tier1_employee' => $tier1['employee'],
            'tier2' => $tier2,
            'total' => round($tier1['employee'] + $tier2, 2)
        );
    }
    
    /**
     * Get fallback tax brackets (Ghana 2024 Latest - from GRA Revised Annual PAYE Schedule)
     * 
     * Used when database brackets are not available.
     * Based on GRA monthly schedule × 12 for annual amounts.
     * 
     * @return array Hardcoded Ghana 2024 latest tax brackets
     */
    protected function get_fallback_brackets() {
        return array(
            array(
                'bracket_name' => 'First Bracket - Tax Free',
                'min_income' => 0,
                'max_income' => 7056,
                'tax_rate' => 0,
                'fixed_amount' => 0
            ),
            array(
                'bracket_name' => 'Second Bracket - 5%',
                'min_income' => 7056.01,
                'max_income' => 8016,
                'tax_rate' => 5,
                'fixed_amount' => 0
            ),
            array(
                'bracket_name' => 'Third Bracket - 10%',
                'min_income' => 8016.01,
                'max_income' => 9216,
                'tax_rate' => 10,
                'fixed_amount' => 48
            ),
            array(
                'bracket_name' => 'Fourth Bracket - 17.5%',
                'min_income' => 9216.01,
                'max_income' => 44016,
                'tax_rate' => 17.5,
                'fixed_amount' => 168
            ),
            array(
                'bracket_name' => 'Fifth Bracket - 25%',
                'min_income' => 44016.01,
                'max_income' => 236016,
                'tax_rate' => 25,
                'fixed_amount' => 6258
            ),
            array(
                'bracket_name' => 'Sixth Bracket - 30%',
                'min_income' => 236016.01,
                'max_income' => 600000,
                'tax_rate' => 30,
                'fixed_amount' => 54258
            ),
            array(
                'bracket_name' => 'Seventh Bracket - 35%',
                'min_income' => 600000.01,
                'max_income' => null,
                'tax_rate' => 35,
                'fixed_amount' => 163453.20
            )
        );
    }
    
    /**
     * Clear tax brackets cache
     * 
     * Force reload of brackets on next calculation
     * 
     * @return void
     */
    public function clear_cache() {
        $this->tax_brackets = null;
    }
    
    /**
     * Validate tax calculation with known example
     * 
     * GRA Example: Monthly gross GHS 6,000 should yield GHS 980 PAYE
     * 
     * @return array Validation result
     */
    public function validate_calculation() {
        // Known GRA example
        $monthly_gross = 6000;
        $monthly_ssnit = 330;  // 5.5% of 6000
        $expected_paye = 980;
        
        $calculated_paye = $this->calculate_monthly_paye($monthly_gross, $monthly_ssnit);
        
        $difference = abs($calculated_paye - $expected_paye);
        $is_valid = ($difference < 1);  // Allow 1 GHS tolerance for rounding
        
        return array(
            'is_valid' => $is_valid,
            'expected' => $expected_paye,
            'calculated' => $calculated_paye,
            'difference' => $difference,
            'message' => $is_valid ? 'Tax calculation validated successfully' : 'Tax calculation validation failed'
        );
    }
}
